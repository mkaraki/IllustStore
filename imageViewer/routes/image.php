<?php
global $klein;
$klein->respond('GET', '/image/[i:imageId]', function ($request, $response, $service, $app) {
    $img = DB::queryFirstRow('SELECT 
        i.path,
        CONVERT(CONV(i.aHash, 10, 16)    , CHAR) as aHash,
        CONVERT(CONV(i.dHash, 10, 16)    , CHAR) as dHash,
        CONVERT(CONV(i.pHash, 10, 16)    , CHAR) as pHash,
        CONVERT(CONV(i.colorHash, 10, 16), CHAR) as colorHash
     FROM illusts i WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $metadataProviders = DB::query('SELECT * FROM metadata_provider');
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

    $neighbor_image = DB::query('SELECT 
            tA2.illustId AS id,
            i.width AS width,
            i.height AS height,
            COUNT(*) AS tag_match_count
        FROM 
            tagAssign tA1
            illusts i
        JOIN 
            tagAssign tA2
        ON
            tA1.tagId = tA2.tagId
        WHERE 
            tA1.illustId = %i
            AND tA2.illustId != %i
            AND tA2.illustId = i.id
        GROUP BY 
            tA2.illustId
        ORDER BY 
            tag_match_count DESC
        LIMIT 10;',
        $request->imageId,
        $request->imageId
    );

    $service->render(__DIR__ . '/../views/image.php', [
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
        'colorHash' => $img['colorHash'],
        'metadata' => $metadata,
        'neighbor_image' => $neighbor_image,
    ]);
});

$klein->respond('GET', '/image/[i:imageId]/duplicate', function ($request, $response, $service, $app) {
    $img = DB::queryFirstRow('SELECT 
        i.path,
        CONVERT(CONV(i.aHash, 10, 16)    , CHAR) as aHash,
        CONVERT(CONV(i.dHash, 10, 16)    , CHAR) as dHash,
        CONVERT(CONV(i.pHash, 10, 16)    , CHAR) as pHash,
        CONVERT(CONV(i.colorHash, 10, 16), CHAR) as colorHash
     FROM illusts i WHERE id = %i', $request->imageId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $exact_size = !empty($_GET['exact_size']);
    $duplicates = [];

    if ($exact_size) {
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
    }
    else {
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
    }

    $exact_query_string = $exact_size ? ' && exact_size' : '';

    $service->render(__DIR__ . '/../views/images.php', [
        'searchParam' => 'duplicate_hash:' . $request->imageId . $exact_query_string,
        'pageType' => 'duplicate',
        'images' => $duplicates,
    ]);
});