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
    $img = DB::queryFirstRow('SELECT * FROM illusts WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $metadataProviders = DB::query('SELECT * FROM metadata_provider');
    foreach ($metadataProviders as $provider) {
        $provider['pathPattern'] = '/' . str_replace('/', '\/', $provider['pathPattern']) . '/';

        if (!preg_match($provider['pathPattern'], $img['path'])) continue;
        
        if ($provider['apiUrlReplacement'] === null || empty($provider['apiUrlReplacement'])) {
            $metadataApiUrl = null;
        } else {
        $metadataApiUrl = preg_replace($provider['pathPattern'], $provider['apiUrlReplacement'], $img['path']);
        }

        if ($provider['providerUrlReplacement'] === null || empty($provider['providerUrlReplacement'])) {
            $metadataProviderUrl = null;
        } else {
        $metadataProviderUrl = preg_replace($provider['pathPattern'], $provider['providerUrlReplacement'], $img['path']);
        }

        $metadataProviderName = $provider['name'];

        if ($provider['sourceUrlReplacement'] !== null) {
            $metadataSourceUrl = preg_replace($provider['pathPattern'], $provider['sourceUrlReplacement'], $img['path']);
        } else {
            $metadataSourceUrl = null;
        }
    }

    $metadata = [
        'metadataProviderName' => $metadataProviderName ?? null,
        'metadataProviderUrl' => $metadataProviderUrl ?? null,
        'metadataSourceUrl' => $metadataSourceUrl ?? null,
        'metadataApiUrl' => $metadataApiUrl ?? null,
        'apiMetadata' => null,
    ];
    if ($metadata['metadataApiUrl'] !== null) {
        $metadata['apiMetadata'] = json_decode(file_get_contents($metadata['metadataApiUrl']), true);
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
        'negativeTags' => DB::query(
            'SELECT
                tNA.tagId AS id,
                t.tagName
            FROM
                tagNegativeAssign tNA,
                tags t
            WHERE
                tNA.tagId = t.id AND
                tNA.illustId = %i',
            $request->imageId,
        ),
        'aHash' => $img['aHash'] ?? null,
        'dHash' => $img['dHash'] ?? null,
        'pHash' => $img['pHash'] ?? null,
        'colorHash' => $img['colorHash'] ?? null,
        'metadata' => $metadata,
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

$klein->respond('/image/[i:imageId]/large', function ($request, $response, $service, $app) {
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

    if ($origImgX > 1920 || $origImgY > 1920) {
        $newX = 0;
        $newY = 0;

        if ($origImgX > $origImgY) {
            $newX = 1920;
            $newY = ($origImgY * (1920.0 / $origImgX));
        } else {
            $newY = 1920;
            $newX = ($origImgX * (1920.0 / $origImgY));
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
    $service->render(__DIR__ . '/views/index.php', [
        'images' => DB::query(
            'SELECT
                id
            FROM
                illusts
            ORDER BY RAND()
            LIMIT 20'
        ),
    ]);
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
    $isNegativeExists = DB::queryFirstRow('SELECT * FROM tagNegativeAssign WHERE illustId = %i AND tagId = %i', $request->illustId, $request->tagId);
    if ($isNegativeExists === null) {
        DB::insert('tagNegativeAssign', [
            'illustId' => $request->illustId,
            'tagId' => $request->tagId,
        ]);
    }

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

    $isNegativeExists = DB::queryFirstRow('SELECT * FROM tagNegativeAssign WHERE illustId = %i AND tagId = %i', $request->illustId, $newTagId);
    if ($isNegativeExists !== null) {
        DB::delete('tagNegativeAssign', [
            'illustId' => $request->illustId,
            'tagId' => $newTagId,
        ]);
    }

    $response->redirect('/image/' . $request->illustId, 303);
});

$klein->respond('GET', '/tag/new', function ($request, $response, $service, $app) {
    $service->render(__DIR__ . '/views/newTag.php');
});

$klein->respond('POST', '/tag/new', function ($request, $response, $service, $app) {
    $tagName = trim($_POST['tagName'] ?? '');
    if (empty($tagName)) {
        $response->code(400);
        return;
    }
    if (str_contains($tagName, ' ') || str_contains($tagName, '　')) {
        $response->code(400);
        return 'Tag Name must not contains white space';
    }

    $searchTag = DB::queryFirstRow('SELECT id FROM tags WHERE tagName = %s', $tagName);
    if ($searchTag !== null) {
        $response->code(400);
        return 'Already exists';
    }

    $tagDanbooru = trim($_POST['tagDanbooru'] ?? '');
    $tagDanbooru = empty($tagDanbooru) ? null : $tagDanbooru;

    $tagPixivJpn = trim($_POST['tagPixivJpn'] ?? '');
    $tagPixivJpn = empty($tagPixivJpn) ? null : $tagPixivJpn;

    $tagPixivEng = trim($_POST['tagPixivEng'] ?? '');
    $tagPixivEng = empty($tagPixivEng) ? null : $tagPixivEng;

    DB::insert('tags', [
        'tagName' => $tagName,
        'tagDanbooru' => $tagDanbooru,
        'tagPixivJpn' => $tagPixivJpn,
        'tagPixivEng' => $tagPixivEng,
    ]);

    $response->redirect('/tag/', 303);
});

$klein->respond('/search/[s:type]/[s:hash]', function ($request, $response, $service, $app) {
    $hashType = null;
    switch ($request->type) {
        case 'aHash':
            $hashType = 'aHash';
            break;
        case 'dHash':
            $hashType = 'dHash';
            break;
        case 'pHash':
            $hashType = 'pHash';
            break;
        case 'colorHash':
            $hashType = 'colorHash';
            break;
        default:
            $response->code(404);
            return;
    }

    $imageCnt = DB::queryFirstField(
        "SELECT COUNT(*) FROM illusts WHERE $hashType = %s",
        $request->hash
    );

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $images = DB::query(
        "SELECT
                id
            FROM
                illusts
            WHERE
                $hashType = %s
            LIMIT 100
            OFFSET %i",
        $request->hash,
        $sttIdx
    );

    $service->render(__DIR__ . '/views/images.php', [
        'searchParam' => $hashType . ':' . $service->escape($request->hash),
        'pageType' => 'hash',
        'images' => $images,
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 100,
    ]);
});

$klein->dispatch();
