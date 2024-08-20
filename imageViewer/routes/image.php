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
    ]);
});