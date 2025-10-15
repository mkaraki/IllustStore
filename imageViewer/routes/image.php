<?php
global $klein;
$klein->respond('GET', '/image/[i:imageId]', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /image/[i:imageId]');
    $span = createAndStartDbSpan($transaction, 'SELECT 
        i.path,
        CONVERT(CONV(i.aHash, 10, 16)    , CHAR) as aHash,
        CONVERT(CONV(i.dHash, 10, 16)    , CHAR) as dHash,
        CONVERT(CONV(i.pHash, 10, 16)    , CHAR) as pHash,
        CONVERT(CONV(i.colorHash, 10, 16), CHAR) as colorHash
     FROM illusts i WHERE id = ?');
    $img = DB::queryFirstRow('SELECT 
        i.path,
        CONVERT(CONV(i.aHash, 10, 16)    , CHAR) as aHash,
        CONVERT(CONV(i.dHash, 10, 16)    , CHAR) as dHash,
        CONVERT(CONV(i.pHash, 10, 16)    , CHAR) as pHash,
        CONVERT(CONV(i.colorHash, 10, 16), CHAR) as colorHash
     FROM illusts i WHERE id = %i', $request->imageId);
    finishSpanAndReturn($transaction, $span);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $span = createAndStartDbSpan($transaction, 'SELECT * FROM metadata_provider');
    $metadataProviders = DB::query('SELECT * FROM metadata_provider');
    finishSpanAndReturn($transaction, $span);
    foreach ($metadataProviders as $provider) {
        $provider['pathPattern'] = '/' . str_replace('/', '\/', $provider['pathPattern']) . '/';

        if (!preg_match($provider['pathPattern'], $img['path'])) continue;

        if (empty($provider['apiUrlReplacement'])) {
            $metadataApiUrl = null;
        } else {
            $metadataApiUrl = preg_replace($provider['pathPattern'], $provider['apiUrlReplacement'], $img['path']);
        }

        if (empty($provider['providerUrlReplacement'])) {
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

    $span = createAndStartDbSpan($transaction, 'SELECT
                tA.tagId AS id,
                t.tagName,
                tA.autoAssigned
            FROM
                tagAssign tA,
                tags t
            WHERE
                tA.tagId = t.id AND
                tA.illustId = ?
            ORDER BY t.tagName');
    $tags = DB::query(
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
    );
    finishSpanAndReturn($transaction, $span);

    $span = createAndStartDbSpan($transaction, 'SELECT
                tNA.tagId AS id,
                t.tagName
            FROM
                tagNegativeAssign tNA,
                tags t
            WHERE
                tNA.tagId = t.id AND
                tNA.illustId = ?');
    $negativeTags = DB::query(
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
    );
    finishSpanAndReturn($transaction, $span);

    $service->render(__DIR__ . '/../views/image.php', [
        'imageId' => $request->imageId,
        'srvPath' => $img['path'],
        'tags' => $tags,
        'negativeTags' => $negativeTags,
        'aHash' => $img['aHash'] ?? null,
        'dHash' => $img['dHash'] ?? null,
        'pHash' => $img['pHash'] ?? null,
        'colorHash' => $img['colorHash'],
        'metadata' => $metadata,
    ]);
    $transaction->finish();
});

$klein->respond('GET', '/image/[i:imageId]/duplicate', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /image/[i:imageId]/duplicate');
    $span = createAndStartDbSpan($transaction, 'SELECT 
        i.path,
        CONVERT(CONV(i.aHash, 10, 16)    , CHAR) as aHash,
        CONVERT(CONV(i.dHash, 10, 16)    , CHAR) as dHash,
        CONVERT(CONV(i.pHash, 10, 16)    , CHAR) as pHash,
        CONVERT(CONV(i.colorHash, 10, 16), CHAR) as colorHash
     FROM illusts i WHERE id = ?');
    $img = DB::queryFirstRow('SELECT 
        i.path,
        CONVERT(CONV(i.aHash, 10, 16)    , CHAR) as aHash,
        CONVERT(CONV(i.dHash, 10, 16)    , CHAR) as dHash,
        CONVERT(CONV(i.pHash, 10, 16)    , CHAR) as pHash,
        CONVERT(CONV(i.colorHash, 10, 16), CHAR) as colorHash
     FROM illusts i WHERE id = %i', $request->imageId);
    finishSpanAndReturn($transaction, $span);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $exact_size = !empty($_GET['exact_size']);
    $duplicates = [];

    if ($exact_size) {
        $span = createAndStartDbSpan($transaction, 'SELECT
                i.id AS id,
                i.width AS width,
                i.height AS height
             FROM illusts i
             WHERE
                i.id != ? AND
                (i.aHash = CONV(?, 16, 10) AND i.dHash = CONV(?, 16, 10) AND i.pHash = CONV(?, 16, 10) AND i.colorHash = CONV(?, 16, 10)) AND
                (i.width = ? AND i.height = ?)');
        $duplicates = DB::query(
            'SELECT
                i.id AS id,
                i.width AS width,
                i.height AS height
             FROM illusts i
             WHERE
                i.id != %i AND
                (i.aHash = CONV(%s, 16, 10) AND i.dHash = CONV(%s, 16, 10) AND i.pHash = CONV(%s, 16, 10) AND i.colorHash = CONV(%s, 16, 10)) AND
                (i.width = %i AND i.height = %i)',
            $request->imageId, $img['aHash'], $img['dHash'], $img['pHash'], $img['colorHash'],
            $img['width'], $img['height']
        );
        finishSpanAndReturn($transaction, $span);
    }
    else {
        $span = createAndStartDbSpan($transaction, 'SELECT
                i.id AS id,
                i.width AS width,
                i.height AS height
             FROM illusts i
             WHERE
                i.id != ? AND
                (i.aHash = CONV(?, 16, 10) AND i.dHash = CONV(?, 16, 10) AND i.pHash = CONV(?, 16, 10) AND i.colorHash = CONV(?, 16, 10))');
        $duplicates = DB::query(
            'SELECT
                i.id AS id,
                i.width AS width,
                i.height AS height
             FROM illusts i
             WHERE
                i.id != %i AND
                (i.aHash = CONV(%s, 16, 10) AND i.dHash = CONV(%s, 16, 10) AND i.pHash = CONV(%s, 16, 10) AND i.colorHash = CONV(%s, 16, 10))',
            $request->imageId, $img['aHash'], $img['dHash'], $img['pHash'], $img['colorHash']
        );
        finishSpanAndReturn($transaction, $span);
    }

    $exact_query_string = $exact_size ? ' && exact_size' : '';

    $service->render(__DIR__ . '/../views/images.php', [
        'searchParam' => 'duplicate_hash:' . $request->imageId . $exact_query_string,
        'pageType' => 'duplicate',
        'images' => $duplicates,
    ]);
    $transaction->finish();
});

$klein->respond('GET', '/image/[i:imageId]/neighbor', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /image/[i:imageId]/neighbor');
    $span = createAndStartDbSpan($transaction, 'WITH TargetTags AS (
            SELECT tagId
            FROM tagAssign
            WHERE illustId = ?
        )
        SELECT 
            tA2.illustId AS id,
            i.width,
            i.height,
            COUNT(*) AS tag_match_count
        FROM 
            targetTags tt
        JOIN 
            tagAssign tA2
        ON
            tt.tagId = tA2.tagId
        JOIN
            illusts i
        ON
            tA2.illustId = i.id
        WHERE 
            tA2.illustId != ?
        GROUP BY 
            tA2.illustId
        ORDER BY 
            tag_match_count DESC
        LIMIT 100');
    $neighbor_image = DB::query('WITH TargetTags AS (
            SELECT tagId
            FROM tagAssign
            WHERE illustId = %i
        )
        SELECT 
            tA2.illustId AS id,
            i.width,
            i.height,
            COUNT(*) AS tag_match_count
        FROM 
            targetTags tt
        JOIN 
            tagAssign tA2
        ON
            tt.tagId = tA2.tagId
        JOIN
            illusts i
        ON
            tA2.illustId = i.id
        WHERE 
            tA2.illustId != %i
        GROUP BY 
            tA2.illustId
        ORDER BY 
            tag_match_count DESC
        LIMIT 100',
        $request->imageId,
        $request->imageId
    );
    finishSpanAndReturn($transaction, $span);

    $service->render(__DIR__ . '/../views/images.php', [
        'searchParam' => 'similar_by_tag:' . $request->imageId,
        'pageType' => 'neighbor',
        'images' => $neighbor_image,
    ]);
    $transaction->finish();
});