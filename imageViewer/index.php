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

$klein->respond('/image/[i:imageId]', function ($request, $response, $service, $app) {
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
                t.tagName
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
    if (imagesx($imgo) > IMG_SCALE_SIZE) {
        $imgo = imagescale($imgo, IMG_SCALE_SIZE);
    }

    if (imagesy($imgo) > IMG_SCALE_SIZE) {
        $imgW = doubleval(imagesx($imgo));
        $imgH = doubleval(imagesy($imgo));
        $yScale = IMG_SCALE_SIZE / $imgH;
        $newW = $imgW * $yScale;
        $imgo = imagescale($imgo, $newW, IMG_SCALE_SIZE);
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

$klein->dispatch();
