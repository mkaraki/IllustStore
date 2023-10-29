<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/_config.php';

const IMG_SCALE_SIZE = 250.0;

$klein = new \Klein\Klein();

function fileMTimeMod($file, $serverDirective, $response)
{
    $filemtime = filemtime($file);
    $curFileDt_str = gmdate('D, d M Y H:i:s', $filemtime) . ' GMT';
    if (isset($serverDirective['HTTP_IF_MODIFIED_SINCE'])) {
        if ($serverDirective['HTTP_IF_MODIFIED_SINCE'] === $curFileDt_str) {
            $response->code(304);
            return true;
        }
    } else {
        header('Last-Modified: ' . $curFileDt_str);
    }

    return false;
}

$klein->respond('/image/', function ($request, $response, $service, $app) {
    $imageCnt = DB::queryFirstField(
        'SELECT COUNT(id) FROM illusts'
    );

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $service->render(__DIR__ . '/views/images.php', [
        'searchParam' => '*',
        'images' => DB::query(
            'SELECT
                *
            FROM
                illusts
            LIMIT 100
            OFFSET %i',
            $request->tagId,
            $sttIdx
        ),
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 100,
    ]);
});

$klein->respond('GET', '/image/[i:imageId]', function ($request, $response, $service, $app) {
    $img = DB::queryFirstRow('SELECT path FROM illusts WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $service->render(__DIR__ . '/views/image.php', [
        'imageId' => $request->imageId,
        'srvPath' => $img['path'],
        'tags' => DB::query(
            'SELECT
                tA.tagId AS id,
                t.tagName,
                tA.autoAssigned
            FROM
                tagAssign tA,
                tags t
            WHERE
                tA.tagId = t.id AND
                tA.illustId = %i
            ORDER BY t.tagName',
            $request->imageId,
        ),
    ]);
});

$klein->respond('/image/[i:imageId]/raw', function ($request, $response, $service, $app) {
    $img = DB::queryFirstField('SELECT path FROM illusts WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    if (fileMTimeMod($img, $_SERVER, $response))
        return;

    $extEval = strtolower($img);
    if (str_ends_with($extEval, '.png')) {
        $response->header('Content-type', 'image/png');
    } else if (str_ends_with($extEval, '.jpg') || str_ends_with($extEval, '.jpeg')) {
        $response->header('Content-type', 'image/jpeg');
    }

    return file_get_contents($img);
});

$klein->respond('/image/[i:imageId]/thumb', function ($request, $response, $service, $app) {
    $img = DB::queryFirstField('SELECT path FROM illusts WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return (var_export($img));
        return;
    }

    if (fileMTimeMod($img, $_SERVER, $response))
        return;

    $imgo = imagecreatefromstring(file_get_contents($img));

    $origImgX = doubleval(imagesx($imgo));
    $origImgY = doubleval(imagesy($imgo));

    if ($origImgX > 250 || $origImgY > 250) {
        $newX = 0;
        $newY = 0;

        if ($origImgX > $origImgY) {
            $newX = 250;
            $newY = ($origImgY * (250.0 / $origImgX));
        } else {
            $newY = 250;
            $newX = ($origImgX * (250.0 / $origImgY));
        }

        $newImgObj = imagecreatetruecolor($newX, $newY);
        imagecopyresampled($newImgObj, $imgo, 0, 0, 0, 0, $newX, $newY, $origImgX, $origImgY);

        $imgo = $newImgObj;
    }

    $response->header('Content-Type', 'image/webp');

    ob_start();
    imagewebp($imgo);
    $response->body(ob_get_contents());
    ob_end_clean();
});

$klein->respond('/tag/', function ($request, $response, $service, $app) {
    $service->render(__DIR__ . '/views/tags.php');
});

$klein->respond('/tag/[i:tagId]', function ($request, $response, $service, $app) {
    $tagData = DB::queryFirstRow('SELECT * FROM tags WHERE id = %i', $request->tagId);

    if ($tagData === null) {
        $response->code(404);
        return;
    }

    $imageCnt = DB::queryFirstField(
        'SELECT COUNT(illustId) FROM tagAssign WHERE tagId = %i',
        $request->tagId
    );

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $images = DB::query(
        'SELECT
                illustId AS id
            FROM
                tagAssign
            WHERE
                tagId = %i
            LIMIT 100
            OFFSET %i',
        $request->tagId,
        $sttIdx
    );

    $service->render(__DIR__ . '/views/images.php', [
        'searchParam' => 'tag:' . $service->escape($tagData['tagName']),
        'pageType' => 'tag',
        'tagId' => $request->tagId,
        'tagName' => $tagData['tagName'],
        'tagDanbooru' => $tagData['tagDanbooru'],
        'tagPixivJpn' => $tagData['tagPixivJpn'],
        'tagPixivEng' => $tagData['tagPixivEng'],
        'images' => $images,
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 100,
    ]);
});

$klein->respond('/search', function ($request, $response, $service, $app) {
    $searchQuery = trim($_GET['q'] ?? '');
    if ($searchQuery === '') {
        $response->redirect('/', 302);
        return;
    }

    $searchTags = [];

    $searchQuery = str_replace('　', ' ', $searchQuery);
    $searchQuerySplitted = explode(' ', $searchQuery);

    foreach ($searchQuerySplitted as $query) {
        $tagId = DB::queryFirstField(
            'SELECT
                id
            FROM
                tags
            WHERE
                LOWER(tagName) = LOWER(%s) OR
                LOWER(tagDanbooru) = LOWER(%s) OR
                LOWER(tagPixivJpn) = LOWER(%s) OR
                LOWER(tagPixivEng) = LOWER(%s)',
            $query,
            $query,
            $query,
            $query
        );

        if ($tagId === null) continue;

        $searchTags[] = $tagId;
    }

    $imageCnt = DB::queryFirstField(
        'SELECT
                COUNT(i.id) OVER()
            FROM
                tagAssign tA,
                tags t,
                illusts i
            WHERE
                tA.tagId = t.id AND
                tA.illustId = i.id AND
                tagId IN %li
            GROUP BY i.id
            HAVING COUNT(i.id) = %i
            LIMIT 1',
        $searchTags,
        count($searchTags),
    );

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $images = DB::query(
        'SELECT
                i.id AS id
            FROM
                tagAssign tA,
                tags t,
                illusts i
            WHERE
                tA.tagId = t.id AND
                tA.illustId = i.id AND
                (tagId IN %li)
            GROUP BY i.id
            HAVING COUNT(i.id) = %i
            LIMIT 100
            OFFSET %i',
        $searchTags,
        count($searchTags),
        $sttIdx
    );

    $service->render(__DIR__ . '/views/images.php', [
        'searchParam' => $searchQuery,
        'pageType' => 'search',
        'searchQuery' => $searchQuery,
        'images' => $images,
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 100,
    ]);
});

$klein->respond('/', function ($request, $response, $service, $app) {
    $service->render(__DIR__ . '/views/index.php');
});

$klein->respond('POST', '/util/tag/complete', function ($request, $response, $service, $app) {
    $queryObj = json_decode($request->body(), true);
    if (!isset($queryObj['w'])) {
        $response->code(400);
        return var_export($request->body());
        return;
    }
    $res = DB::queryFirstColumn("SELECT tagName FROM tags WHERE tagName LIKE %ss", $queryObj['w']);
    $response->json(['sw' => $res]);
});

$klein->respond('POST', '/image/[i:illustId]/tag/[i:tagId]/delete', function ($request, $response, $service, $app) {
    DB::delete('tagAssign', [
        'illustId' => $request->illustId,
        'tagId' => $request->tagId,
    ]);

    $response->redirect('/image/' . $request->illustId, 303);
});

$klein->respond('POST', '/image/[i:illustId]/tag/[i:tagId]/approve', function ($request, $response, $service, $app) {
    DB::update('tagAssign', ['autoAssigned' => false], [
        'illustId' => $request->illustId,
        'tagId' => $request->tagId,
    ]);

    $response->redirect('/image/' . $request->illustId, 303);
});

$klein->respond('GET', '/image/[i:illustId]/tag/new', function ($request, $response, $service, $app) {
    $img = DB::queryFirstRow('SELECT path FROM illusts WHERE id = %i', $request->illustId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $service->render(__DIR__ . '/views/newTagAssign.php', [
        'imageId' => $request->illustId,
        'srvPath' => $img['path'],
        'tags' => DB::query(
            'SELECT
                tA.tagId AS id,
                t.tagName,
                tA.autoAssigned
            FROM
                tagAssign tA,
                tags t
            WHERE
                tA.tagId = t.id AND
                tA.illustId = %i
            ORDER BY t.tagName',
            $request->illustId,
        ),
    ]);
});

$klein->respond('POST', '/image/[i:illustId]/tag/new', function ($request, $response, $service, $app) {

    $newTagId = $_POST['newTagId'] ?? 'UNABLE';
    if (!is_numeric($newTagId)) {
        $response->code(400);
        return;
    }
    $newTagId = intval($newTagId);

    $img = DB::queryFirstRow('SELECT path FROM illusts WHERE id = %i', $request->illustId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $tagData = DB::queryFirstRow('SELECT * FROM tags WHERE id = %i', $newTagId);
    if ($tagData === null) {
        $response->code(404);
        return;
    }

    DB::insert('tagAssign', [
        'illustId' => $request->illustId,
        'tagId' => $newTagId,
        'autoAssigned' => false,
    ]);

    $response->redirect('/image/' . $request->illustId, 303);
});

$klein->dispatch();
