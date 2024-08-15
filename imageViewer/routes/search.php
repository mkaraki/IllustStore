<?php
global $klein;

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
                t.id
            FROM
                tags t
            WHERE
                LOWER(t.tagName) = LOWER(%s) OR
                LOWER(t.tagDanbooru) = LOWER(%s) OR
                LOWER(t.tagPixivJpn) = LOWER(%s) OR
                LOWER(t.tagPixivEng) = LOWER(%s)',
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

    $service->render(__DIR__ . '/../views/images.php', [
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

$klein->respond('/search/[s:type]/[s:hash]', function ($request, $response, $service, $app) {
    $hashType = null;
    switch ($request->type) {
        case 'aHash':
            $hashType = 'aHash';
            $threshold = 10;
            break;
        case 'dHash':
            $hashType = 'dHash';
            $threshold = 10;
            break;
        case 'pHash':
            $hashType = 'pHash';
            $threshold = 10;
            break;
        case 'colorHash':
            $hashType = 'colorHash';
            $threshold = 10;
            break;
        default:
            $response->code(404);
            return;
    }

    if (isset($_GET['threshold']) && is_numeric($_GET['threshold'])) {
        $threshold = intval($_GET['threshold']);
    }

    if ($threshold === 0 || isset($_GET['exact']))
    {
        $threshold = 'exact';
        $exact = true;
    }
    else
    {
        $exact = false;
    }

    if ($exact)
    {
        $imageCnt = DB::queryFirstField(
            "SELECT
                COUNT(i.id)
            FROM
                illusts i
            WHERE
                i.$hashType = CONV(%s, 16, 10)",
            $request->hash,
        );
    }
    else
    {
        $imageCnt = DB::queryFirstField(
            "SELECT
                    COUNT(i.id)
                FROM
                    illusts i
                WHERE
                    BIT_COUNT(i.$hashType ^ CONV(%s, 16, 10)) < %i",
            $request->hash,
            $threshold,
        );
    }

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    if ($exact)
    {
        $images = DB::query(
            "SELECT
                i.id
            FROM
                illusts i
            WHERE
                i.$hashType = CONV(%s, 16, 10)
            LIMIT 100
            OFFSET %i",
            $request->hash,
            $sttIdx
        );
    }
    else
    {
        $images = DB::query(
            "SELECT
                i.id
            FROM
                illusts i
            WHERE
                BIT_COUNT(i.$hashType ^ CONV(%s, 16, 10)) < %i
            LIMIT 100
            OFFSET %i",
            $request->hash,
            $threshold,
            $sttIdx
        );
    }

    $service->render(__DIR__ . '/../views/images.php', [
        'searchParam' => $hashType . ':' . $service->escape($request->hash) . ' threshold:' . $threshold,
        'pageType' => 'hash',
        'images' => $images,
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 100,
    ]);
});

require_once __DIR__ . '/../ImageHash/ImageHash.php';
require_once __DIR__ . '/../ImageHash/ImageHasher.php';
use \mkaraki\ImageHash\ImageHasher;

$klein->respond('POST', '/search/image', function($request, $response, $service, $app) {
    $files = $request->files();

    if (!isset($files['img'])) {
        $response->code(400);
        return 'Something went wrong';
    }

    $filePath = $files['img']['tmp_name'];

    try {
        $im = new Imagick($filePath);
    }
    catch (\ImagickException $e) {
        $response->code(400);
        return "Unsupported file submitted or reload detected.";
    }

    $hasher = new ImageHasher();

    $thumb_img = clone $im;
    $thumb_img->scaleImage(300, 300, true);
    $thumb_img->setCompressionQuality(50);
    $thumb_img->setCompression(imagick::COMPRESSION_JPEG);
    $thumb_img->setImageFormat('jpg');
    $thumb_b64 = base64_encode($thumb_img->getImageBlob());

    $aHash = $hasher->average_hash($im)->hex();
    $dHash = $hasher->difference_hash($im)->hex();
    $pHash = $hasher->perceptual_hash($im)->hex();

    $service->render(__DIR__ . '/../views/client_image_hash_result.php', [
        'image' => 'data:image/png;base64,' . $thumb_b64,
        'aHash' => $aHash,
        'dHash' => $dHash,
        'pHash' => $pHash,
    ]);
});
