<?php
require_once __DIR__ . '/../shared.php';
global $klein;

$klein->respond('GET', '/search', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /search');
    $searchQuery = trim($_GET['q'] ?? '');
    if ($searchQuery === '') {
        $response->redirect('/', 302);
        $transaction->finish();
        return;
    }

    $searchTags = [];

    $searchQuery = str_replace('　', ' ', $searchQuery);
    $searchQuerySplitted = explode(' ', $searchQuery);

    foreach ($searchQuerySplitted as $query) {
        $span = createAndStartDbSpan($transaction, 'SELECT
                t.id
            FROM
                tags t
            WHERE
                LOWER(t.tagName) = LOWER(%s) OR
                LOWER(t.tagDanbooru) = LOWER(%s) OR
                LOWER(t.tagPixivJpn) = LOWER(%s) OR
                LOWER(t.tagPixivEng) = LOWER(%s)');
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
        finishSpanAndReturn($transaction, $span);

        if ($tagId === null) continue;

        $searchTags[] = $tagId;
    }

    $span = createAndStartDbSpan($transaction, 'SELECT
                COUNT(i.id) OVER()
            FROM
                tagAssign tA,
                tags t,
                illusts i
            WHERE
                tA.tagId = t.id AND
                tA.illustId = i.id AND
                tagId IN (?)
            GROUP BY i.id
            HAVING COUNT(i.id) = ?
            LIMIT 1');
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
    finishSpanAndReturn($transaction, $span);

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $span = createAndStartDbSpan($transaction, 
        'SELECT
                i.id AS id,
                i.width AS width,
                i.height AS height
            FROM
                tagAssign tA,
                tags t,
                illusts i
            WHERE
                tA.tagId = t.id AND
                tA.illustId = i.id AND
                (tagId IN (?))
            GROUP BY i.id
            HAVING COUNT(i.id) = ?
            ORDER BY id DESC
            LIMIT 100
            OFFSET ?'
    );
    $images = DB::query(
        'SELECT
                i.id AS id,
                i.width AS width,
                i.height AS height
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
            ORDER BY id DESC
            LIMIT 100
            OFFSET %i',
        $searchTags,
        count($searchTags),
        $sttIdx
    );
    finishSpanAndReturn($transaction, $span);

    $span = createAndStartRenderSpan($transaction);
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
    $transaction->finish();
    finishSpanAndReturn($transaction, $span);
});

$klein->respond('GET', '/search/[s:type]/[s:hash]', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /search/[s:type]/[s:hash]');
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
            $threshold = 3;
            break;
        default:
            $response->code(404);
            $transaction->finish();
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
        $span = createAndStartDbSpan($transaction, "SELECT
                COUNT(i.id)
            FROM
                illusts i
            WHERE
                i.$hashType = CONV(%s, 16, 10)");
        $imageCnt = DB::queryFirstField(
            "SELECT
                COUNT(i.id)
            FROM
                illusts i
            WHERE
                i.$hashType = CONV(%s, 16, 10)",
            $request->hash,
        );
        finishSpanAndReturn($transaction, $span);
    }
    else
    {
        $span = createAndStartDbSpan($transaction, "SELECT
                    COUNT(i.id)
                FROM
                    illusts i
                WHERE
                    BIT_COUNT(i.$hashType ^ CONV(%s, 16, 10)) < %s");
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
        finishSpanAndReturn($transaction, $span);
    }

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    if ($exact)
    {
        $span = createAndStartDbSpan($transaction, "SELECT
                i.id,
                i.width,
                i.height
            FROM
                illusts i
            WHERE
                i.$hashType = CONV(%s, 16, 10)
            ORDER BY i.id DESC
            LIMIT 100
            OFFSET %s");
        $images = DB::query(
            "SELECT
                i.id,
                i.width,
                i.height
            FROM
                illusts i
            WHERE
                i.$hashType = CONV(%s, 16, 10)
            ORDER BY i.id DESC
            LIMIT 100
            OFFSET %i",
            $request->hash,
            $sttIdx
        );
        finishSpanAndReturn($transaction, $span);
    }
    else
    {
        $span = createAndStartDbSpan($transaction, "SELECT
                i.id,
                i.width,
                i.height,
                BIT_COUNT(i.$hashType ^ CONV(%s, 16, 10)) AS similarity
            FROM
                illusts i
            WHERE
                BIT_COUNT(i.$hashType ^ CONV(%s, 16, 10)) < %s
            ORDER BY similarity
            LIMIT 100
            OFFSET %s");
        $images = DB::query(
            "SELECT
                i.id,
                i.width,
                i.height,
                BIT_COUNT(i.$hashType ^ CONV(%s, 16, 10)) AS similarity
            FROM
                illusts i
            WHERE
                BIT_COUNT(i.$hashType ^ CONV(%s, 16, 10)) < %i
            ORDER BY similarity
            LIMIT 100
            OFFSET %i",
            $request->hash,
            $request->hash,
            $threshold,
            $sttIdx
        );
        finishSpanAndReturn($transaction, $span);
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
    $transaction->finish();
});

require_once __DIR__ . '/../ImageHash/ImageHash.php';
require_once __DIR__ . '/../ImageHash/ImageHasher.php';
use \mkaraki\ImageHash\ImageHasher;

$klein->respond('POST', '/search/image', function($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('POST /search/image');
    $files = $request->files();

    if (!isset($files['img'])) {
        $response->code(400);
        $transaction->finish();
        return 'Something went wrong';
    }

    $filePath = $files['img']['tmp_name'];

    try {
        $im = new Imagick($filePath);
    }
    catch (\ImagickException $e) {
        $response->code(400);
        $transaction->finish();
        return "Unsupported file submitted or reload detected.";
    }

    $hasher = new ImageHasher();

    $spanContext = \Sentry\Tracing\SpanContext::make()
        ->setOp('img.thumb.gen')
        ->setDescription('Generate thumbnail');
    
    $thumb_img = \Sentry\trace(function() use ($im) {
        $thumb_img = clone $im;
        $thumb_img->scaleImage(300, 300, true);
        return $thumb_img;
    }, $spanContext);

    $spanContext = \Sentry\Tracing\SpanContext::make()
        ->setOp('img.thumb.b64')
        ->setDescription('Convert thumbnail to base64');

    $thumb_b64 = \Sentry\trace(function() use ($thumb_img) {
        $thumb_img->setCompressionQuality(50);
        $thumb_img->setCompression(imagick::COMPRESSION_JPEG);
        $thumb_img->setImageFormat('jpg');
        return base64_encode($thumb_img->getImageBlob());
    }, $spanContext);

    $aHash = $hasher->average_hash($im)->hex();
    $dHash = $hasher->difference_hash($im)->hex();
    $pHash = $hasher->perceptual_hash($im)->hex();

    $service->render(__DIR__ . '/../views/client_image_hash_result.php', [
        'image' => 'data:image/png;base64,' . $thumb_b64,
        'aHash' => $aHash,
        'dHash' => $dHash,
        'pHash' => $pHash,
    ]);
    $transaction->finish();
});
