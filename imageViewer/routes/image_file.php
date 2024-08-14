<?php
global $klein;

function get_image_load_mode($imagePath): string
{
    $extEval = strtolower($imagePath);
    switch (true) {
        default:
        case str_ends_with($extEval, '.png'):
        case str_ends_with($extEval, '.jpg'):
        case str_ends_with($extEval, '.jpeg'):
        case str_ends_with($extEval, '.webp'):
            return 'default';

        case str_ends_with($extEval, '.lep'):
            return 'lepton';
    }
}

function get_image_data($imagePath, $mode = 'default'): string
{
    switch ($mode) {
        case 'default':
        default:
            return file_get_contents($imagePath);

        case 'lepton':
            return convert_lepton_to_jpeg(file_get_contents($imagePath));
    }
}

function return_raw_image($imagePath, $response): void
{
    $extEval = strtolower($imagePath);
    switch (true) {
        case str_ends_with($extEval, '.png'):
            $response->header('Content-Type', 'image/png');
            break;

        case str_ends_with($extEval, '.jpg'):
        case str_ends_with($extEval, '.jpeg'):
        case str_ends_with($extEval, '.lep'):
            $response->header('Content-Type', 'image/jpeg');
            break;

        case str_ends_with($extEval, '.webp'):
            $response->header('Content-Type', 'image/webp');
            break;
    }

    $imgMode = get_image_load_mode($imagePath);
    $response->body(get_image_data($imagePath, $imgMode));
};

$klein->respond('/image/[i:imageId]/raw', function ($request, $response, $service, $app) {
    $img = DB::queryFirstField('SELECT path FROM illusts WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    if (fileMTimeMod($img, $_SERVER, $response))
        return;

    return_raw_image($img, $response);
});

function resize_image_and_return($request, $response, $service, $app, int $size): void {
    $size_float = (float) $size;

    ini_set("memory_limit", "512M");

    $img = DB::queryFirstField('SELECT path FROM illusts WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    if (fileMTimeMod($img, $_SERVER, $response))
        return;

    try {
        $imgMode = get_image_load_mode($img);
        $imgo = imagecreatefromstring(get_image_data($img, $imgMode));

        $origImgX = doubleval(imagesx($imgo));
        $origImgY = doubleval(imagesy($imgo));

        if ($origImgX > $size || $origImgY > $size) {
            $newX = 0;
            $newY = 0;

            if ($origImgX > $origImgY) {
                $newX = $size;
                $newY = ($origImgY * ($size_float / $origImgX));
            } else {
                $newY = $size;
                $newX = ($origImgX * ($size_float / $origImgY));
            }

            $newImgObj = imagecreatetruecolor($newX, $newY);
            imagecopyresampled($newImgObj, $imgo, 0, 0, 0, 0, $newX, $newY, $origImgX, $origImgY);

            $imgo = $newImgObj;
        }
    } catch (Exception $e) {
        return_raw_image($img, $response);
        return;
    }

    $response->header('Content-Type', 'image/webp');

    ob_start();
    imagewebp($imgo);
    $response->body(ob_get_contents());
    ob_end_clean();
}

$klein->respond('/image/[i:imageId]/large', function ($request, $response, $service, $app) {
    resize_image_and_return($request, $response, $service, $app, 1920);
});

$klein->respond('/image/[i:imageId]/thumb', function ($request, $response, $service, $app) {
    resize_image_and_return($request, $response, $service, $app, 250);
});