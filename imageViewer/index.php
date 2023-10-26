<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/_config.php';

$klein = new \Klein\Klein();

$klein->respond('/image/', function ($request, $response, $service, $app) {
    $service->render(__DIR__ . '/views/images.php', [
        'searchParam' => '*',
        'images' => DB::query('SELECT * FROM illusts')
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
                tA.illustId = %i',
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

    $imgScaleSize = 250.0;

    $imgo = imagecreatefromstring(file_get_contents($img));
    if (imagesx($imgo) > $imgScaleSize) {
        $imgo = imagescale($imgo, $imgScaleSize);
    }

    if (imagesy($imgo) > $imgScaleSize) {
        $imgW = doubleval(imagesx($imgo));
        $imgH = doubleval(imagesy($imgo));
        $yScale = $imgScaleSize / $imgH;
        $newW = $imgW * $yScale;
        $imgo = imagescale($imgo, $newW, $imgScaleSize);
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

    $service->render(__DIR__ . '/views/images.php', [
        'searchParam' => 'tag:' . $service->escape($tagData['tagName']),
        'images' => DB::query(
            'SELECT
                illustId AS id
            FROM
                tagAssign
            WHERE
                tagId = %i',
            $request->tagId
        )
    ]);
});

$klein->dispatch();
