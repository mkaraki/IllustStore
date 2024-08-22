<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/_config.php';

const IMG_SCALE_SIZE = 250.0;

$klein = new \Klein\Klein();

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
                i.id,
                i.width,
                i.height
            FROM
                illusts i
            ORDER BY i.id DESC
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

require __DIR__ . '/routes/image.php';

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
        'SELECT COUNT(tA.illustId) FROM tagAssign tA WHERE tA.tagId = %i',
        $request->tagId
    );

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $images = DB::query(
        'SELECT
                tA.illustId AS id,
                i.width AS width,
                i.height AS height
            FROM
                tagAssign tA,
                illusts i
            WHERE
                tA.tagId = %i AND
                tA.illustId = i.id
            ORDER BY tA.illustId DESC
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

$klein->respond('/', function ($request, $response, $service, $app) {
    $service->render(__DIR__ . '/views/index.php', [
        'images' => DB::query(
            'SELECT
                i.id,
                i.width,
                i.height
            FROM
                illusts i
            ORDER BY RAND()
            LIMIT 20'
        ),
    ]);
});

$klein->respond('POST', '/util/tag/complete', function ($request, $response, $service, $app) {
    $queryObj = json_decode($request->body(), true);
    if (!isset($queryObj['w'])) {
        $response->code(400);
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

    $pendingCode = intval($_POST['pending'] ?? '0');
    if ($pendingCode > 0) {
        $response->redirect('/tag/pending?p=' . $pendingCode, 303);
    } else {
        $response->redirect('/image/' . $request->illustId, 303);
    }
});

$klein->respond('POST', '/image/[i:illustId]/tag/[i:tagId]/approve', function ($request, $response, $service, $app) {
    DB::update('tagAssign', ['autoAssigned' => false], [
        'illustId' => $request->illustId,
        'tagId' => $request->tagId,
    ]);

    $pendingCode = intval($_POST['pending'] ?? '0');
    if ($pendingCode > 0) {
        $response->redirect('/tag/pending?p=' . $pendingCode, 303);
    } else {
        $response->redirect('/image/' . $request->illustId, 303);
    }
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
        'pending' => intval($_GET['pending'] ?? '0'),
    ]);
});

$klein->respond('POST', '/image/[i:illustId]/tag/new', function ($request, $response, $service, $app) {
    $newTag = trim($_POST['newTagId'] ?? '', " \n\r\t\v\x00　");
    if ($newTag === '') {
        $response->code(400);
        return;
    }

    $img = DB::queryFirstRow('SELECT i.id, i.path FROM illusts i WHERE id = %i', $request->illustId);
    if ($img === null) {
        $response->code(404);
        return;
    }

    $tagData = DB::queryFirstRow('SELECT t.id FROM tags t WHERE t.tagName = %s', $newTag);
    if ($tagData === null) {
        $response->code(404);
        return;
    }

    DB::insert('tagAssign', [
        'illustId' => $img['id'],
        'tagId' => $tagData['id'],
        'autoAssigned' => false,
    ]);

    $isNegativeExists = DB::queryFirstRow(
        'SELECT * FROM tagNegativeAssign WHERE illustId = %i AND tagId = %i',
        $img['id'],
        $tagData['id']
    );
    if ($isNegativeExists !== null) {
        DB::delete('tagNegativeAssign', [
            'illustId' => $img['id'],
            'tagId' => $tagData['id'],
        ]);
    }

    $pendingCode = intval($_POST['pending'] ?? '0');
    if ($pendingCode > 0) {
        $response->redirect('/tag/pending?p=' . $pendingCode, 303);
    } else {
        $response->redirect('/image/' . $img['id'], 303);
    }
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

$klein->respond('GET', '/tag/pending', function ($request, $response, $service, $app) {
    $imageCnt = DB::queryFirstField(
        'SELECT COUNT(DISTINCT tA.illustId) FROM tagAssign tA WHERE tA.autoAssigned = TRUE'
    );

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 30;
    $maxPage = ceil(doubleval($imageCnt) / 30.0);

    $pendingTags = DB::query(
        'SELECT
                tA.illustId AS imageId
            FROM
                tagAssign tA
            WHERE
                tA.autoAssigned = TRUE
            GROUP BY
                tA.illustId
            LIMIT 30
            OFFSET %i',
        $sttIdx
    );

    $service->render(__DIR__ . '/views/pendingTags.php', [
        'pendingTags' => $pendingTags,
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 30,
    ]);
});

require __DIR__ . '/routes/search.php';
require __DIR__ . '/routes/metric.php';

$klein->dispatch();
