<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/shared.php';

\Sentry\init([
    'dsn' => SENTRY_DSN,
    'traces_sample_rate' => 0.3,
    'profiles_sample_rate' => 0.3,
]);

const IMG_SCALE_SIZE = 250.0;

$klein = new \Klein\Klein();

$klein->respond('GET', '/image/', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /image/');

    $span = createAndStartDbSpan($transaction, 'SELECT COUNT(id) FROM illusts');
    $imageCnt = DB::queryFirstField(
        'SELECT COUNT(id) FROM illusts'
    );
    finishSpanAndReturn($transaction, $span);

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $span = createAndStartDbSpan($transaction, 'SELECT
                i.id,
                i.width,
                i.height
            FROM
                illusts i
            ORDER BY i.id DESC
            LIMIT 100
            OFFSET ?');
    $images = DB::query(
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
    );
    finishSpanAndReturn($transaction, $span);

    $service->render(__DIR__ . '/views/images.php', [
        'searchParam' => '*',
        'images' => $images,
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 100,
    ]);
    $transaction->finish();
});

require __DIR__ . '/routes/image.php';

$klein->respond('GET', '/tag/', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /tag/');
    $service->render(__DIR__ . '/views/tags.php');
    $transaction->finish();
});

$klein->respond('GET', '/tag/[i:tagId]', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /tag/[i:tagId]');

    $span = createAndStartDbSpan($transaction, 'SELECT * FROM tags WHERE id = ?');
    $tagData = DB::queryFirstRow('SELECT * FROM tags WHERE id = %i', $request->tagId);
    finishSpanAndReturn($transaction, $span);

    if ($tagData === null) {
        $response->code(404);
        $transaction->finish();
        return;
    }

    $span = createAndStartDbSpan($transaction, 'SELECT COUNT(tA.illustId) FROM tagAssign tA WHERE tA.tagId = ?');
    $imageCnt = DB::queryFirstField(
        'SELECT COUNT(tA.illustId) FROM tagAssign tA WHERE tA.tagId = %i',
        $request->tagId
    );
    finishSpanAndReturn($transaction, $span);

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 100;
    $maxPage = ceil(doubleval($imageCnt) / 100.0);

    $span = createAndStartDbSpan($transaction, 'SELECT
                tA.illustId AS id,
                i.width AS width,
                i.height AS height
            FROM
                tagAssign tA,
                illusts i
            WHERE
                tA.tagId = ? AND
                tA.illustId = i.id
            ORDER BY tA.illustId DESC
            LIMIT 100
            OFFSET ?');
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
    finishSpanAndReturn($transaction, $span);

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
    $transaction->finish();
});

$klein->respond('GET', '/', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /');
    $span = createAndStartDbSpan($transaction, 'SELECT
                i.id,
                i.width,
                i.height
            FROM
                illusts i
            ORDER BY RAND()
            LIMIT 20');
    $images = DB::query(
        'SELECT
            i.id,
            i.width,
            i.height
        FROM
            illusts i
        ORDER BY RAND()
        LIMIT 20'
    );
    finishSpanAndReturn($transaction, $span);
    $service->render(__DIR__ . '/views/index.php', [
        'images' => $images,
    ]);
    $transaction->finish();
});

$klein->respond('POST', '/util/tag/complete', function ($request, $response, $service, $app) {
    if (isset($request->headers()['sentry-trace']) && isset($request->headers()['baggage'])) {
        \Sentry\continueTrace($request->headers()['sentry-trace'], $request->headers()['baggage']);
    }
    $transaction = createAndStartWebTransaction('POST /util/tag/complete');
    $queryObj = json_decode($request->body(), true);
    if (!isset($queryObj['w'])) {
        $response->code(400);
        $transaction->finish();
        return;
    }
    $span = createAndStartDbSpan($transaction, 'SELECT tagName FROM tags WHERE tagName LIKE ?');
    $res = DB::queryFirstColumn("SELECT tagName FROM tags WHERE tagName LIKE %ss", $queryObj['w']);
    finishSpanAndReturn($transaction, $span);
    $response->json(['sw' => $res]);
    $transaction->finish();
});

$klein->respond('POST', '/image/[i:illustId]/tag/[i:tagId]/delete', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('POST /image/[i:illustId]/tag/[i:tagId]/delete');
    $span = createAndStartDbSpan($transaction, 'SELECT * FROM tagNegativeAssign WHERE illustId = ? AND tagId = ?');
    $isNegativeExists = DB::queryFirstRow('SELECT * FROM tagNegativeAssign WHERE illustId = %i AND tagId = %i', $request->illustId, $request->tagId);
    finishSpanAndReturn($transaction, $span);
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
    $transaction->finish();
});

$klein->respond('POST', '/image/[i:illustId]/tag/[i:tagId]/approve', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('POST /image/[i:illustId]/tag/[i:tagId]/approve');
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
    $transaction->finish();
});

$klein->respond('GET', '/image/[i:illustId]/tag/new', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /image/[i:illustId]/tag/new');
    $span = createAndStartDbSpan($transaction, 'SELECT path FROM illusts WHERE id = ?');
    $img = DB::queryFirstRow('SELECT path FROM illusts WHERE id = %i', $request->illustId);
    finishSpanAndReturn($transaction, $span);
    if ($img === null) {
        $response->code(404);
        $transaction->finish();
        return;
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
        $request->illustId,
    );
    finishSpanAndReturn($transaction, $span);

    $service->render(__DIR__ . '/views/newTagAssign.php', [
        'imageId' => $request->illustId,
        'srvPath' => $img['path'],
        'tags' => $tags,
        'pending' => intval($_GET['pending'] ?? '0'),
    ]);
    $transaction->finish();
});

$klein->respond('POST', '/image/[i:illustId]/tag/new', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('POST /image/[i:illustId]/tag/new');
    $newTag = trim($_POST['newTagId'] ?? '', " \n\r\t\v\x00　");
    if ($newTag === '') {
        $response->code(400);
        $transaction->finish();
        return;
    }

    $span = createAndStartDbSpan($transaction, 'SELECT i.id, i.path FROM illusts i WHERE id = ?');
    $img = DB::queryFirstRow('SELECT i.id, i.path FROM illusts i WHERE id = %i', $request->illustId);
    finishSpanAndReturn($transaction, $span);
    if ($img === null) {
        $response->code(404);
        $transaction->finish();
        return;
    }

    $span = createAndStartDbSpan($transaction, 'SELECT t.id FROM tags t WHERE t.tagName = ?');
    $tagData = DB::queryFirstRow('SELECT t.id FROM tags t WHERE t.tagName = %s', $newTag);
    finishSpanAndReturn($transaction, $span);
    if ($tagData === null) {
        $response->code(404);
        $transaction->finish();
        return;
    }

    DB::insert('tagAssign', [
        'illustId' => $img['id'],
        'tagId' => $tagData['id'],
        'autoAssigned' => false,
    ]);

    $span = createAndStartDbSpan($transaction, 'SELECT * FROM tagNegativeAssign WHERE illustId = ? AND tagId = ?');
    $isNegativeExists = DB::queryFirstRow(
        'SELECT * FROM tagNegativeAssign WHERE illustId = %i AND tagId = %i',
        $img['id'],
        $tagData['id']
    );
    finishSpanAndReturn($transaction, $span);
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
    $transaction->finish();
});

$klein->respond('GET', '/tag/new', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /tag/new');
    $service->render(__DIR__ . '/views/newTag.php');
    $transaction->finish();
});

$klein->respond('POST', '/tag/new', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('POST /tag/new');
    $tagName = trim($_POST['tagName'] ?? '');
    if (empty($tagName)) {
        $response->code(400);
        $transaction->finish();
        return;
    }
    if (str_contains($tagName, ' ') || str_contains($tagName, '　')) {
        $response->code(400);
        $transaction->finish();
        return 'Tag Name must not contains white space';
    }

    $span = createAndStartDbSpan($transaction, 'SELECT id FROM tags WHERE tagName = ?');
    $searchTag = DB::queryFirstRow('SELECT id FROM tags WHERE tagName = %s', $tagName);
    finishSpanAndReturn($transaction, $span);
    if ($searchTag !== null) {
        $response->code(400);
        $transaction->finish();
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
    $transaction->finish();
});


$klein->respond('GET', '/tag/[i:tagId]/edit', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /tag/[i:tagId]/edit');
    $span = createAndStartDbSpan($transaction, 'SELECT 
        t.id,
        t.tagName,
        t.tagDanbooru,
        t.tagPixivJpn,
        t.tagPixivEng,
        t.description,
        t.taggingNote,
        t.aliasOf,
        t.tagGroup,
        t.selectiveTagGroup
     FROM tags t WHERE t.id = ?');
    $tagInfo = DB::queryFirstRow('SELECT 
        t.id,
        t.tagName,
        t.tagDanbooru,
        t.tagPixivJpn,
        t.tagPixivEng,
        t.description,
        t.taggingNote,
        t.aliasOf,
        t.tagGroup,
        t.selectiveTagGroup
     FROM tags t WHERE t.id = %i', $request->tagId);
    finishSpanAndReturn($transaction, $span);

    if ($tagInfo === null) {
        $response->code(404);
        $transaction->finish();
        return;
    }

    $service->render(__DIR__ . '/views/newTag.php', [
        'tagId' => $tagInfo['id'],
        'tagName' => $tagInfo['tagName'],
        'tagDanbooru' => $tagInfo['tagDanbooru'],
        'tagPixivJpn' => $tagInfo['tagPixivJpn'],
        'tagPixivEng' => $tagInfo['tagPixivEng'],
        'description' => $tagInfo['description'],
        'taggingNote' => $tagInfo['taggingNote'],
        'aliasOf' => $tagInfo['aliasOf'],
        'tagGroup' => $tagInfo['tagGroup'],
        'selectiveTagGroup' => $tagInfo['selectiveTagGroup'],
    ]);
    $transaction->finish();
});

function empty_to_null(string|null $value): string|null {
    return empty($value) ? null : trim($value);
}

$klein->respond('POST', '/tag/[i:tagId]/edit', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('POST /tag/[i:tagId]/edit');
    $span = createAndStartDbSpan($transaction, 'SELECT t.id FROM tags t WHERE t.id = ?');
    $tagExists = DB::queryFirstField('SELECT t.id FROM tags t WHERE t.id = %i', $request->tagId);
    finishSpanAndReturn($transaction, $span);
    if ($tagExists === null) {
        $response->code(404);
        $transaction->finish();
        return;
    }

    if (empty($_POST['tagName'])) {
        $response->code(400);
        $transaction->finish();
        return;
    }

    $span = createAndStartDbSpan($transaction, 'SELECT t.id FROM tags t WHERE t.id <> ? AND t.tagName = ?');
    $tagNameAlreadyInUse = DB::queryFirstField('SELECT t.id FROM tags t WHERE t.id <> %i AND t.tagName = %s', $request->tagId, $_POST['tagName']);
    finishSpanAndReturn($transaction, $span);
    if ($tagNameAlreadyInUse !== null) {
        $response->code(404);
        $transaction->finish();
        return 'You can not use that tag name.';
    }

    DB::update('tags', [
        'tagName' => $_POST['tagName'],
        'tagDanbooru' => empty_to_null($_POST['tagDanbooru'] ?? null),
        'tagPixivJpn' => empty_to_null($_POST['tagPixivJpn'] ?? null),
        'tagPixivEng' => empty_to_null($_POST['tagPixivEng'] ?? null),
        'tagGroup' => empty_to_null($_POST['tagGroup'] ?? null),
        'selectiveTagGroup' => empty_to_null($_POST['selectiveTagGroup'] ?? null),
        'description' => empty_to_null($_POST['description'] ?? null),
        'taggingNote' => empty_to_null($_POST['taggingNote'] ?? null),
        'aliasOf' => empty_to_null($_POST['aliasOf'] ?? null),
    ], ['id' => $request->tagId]);

    $response->redirect('/tag/' . $request->tagId, 303);
    $transaction->finish();
});


$klein->respond('GET', '/tag/pending', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /tag/pending');
    $span = createAndStartDbSpan($transaction, 'SELECT COUNT(DISTINCT tA.illustId) FROM tagAssign tA WHERE tA.autoAssigned = TRUE');
    $imageCnt = DB::queryFirstField(
        'SELECT COUNT(DISTINCT tA.illustId) FROM tagAssign tA WHERE tA.autoAssigned = TRUE'
    );
    finishSpanAndReturn($transaction, $span);

    $p = $_GET['p'] ?? '1';
    $p = intval($p);
    $sttIdx = ($p - 1) * 30;
    $maxPage = ceil(doubleval($imageCnt) / 30.0);

    $span = createAndStartDbSpan($transaction, 'SELECT
                tA.illustId AS imageId
            FROM
                tagAssign tA
            WHERE
                tA.autoAssigned = TRUE
            GROUP BY
                tA.illustId
            LIMIT 30
            OFFSET ?');
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
    finishSpanAndReturn($transaction, $span);

    $service->render(__DIR__ . '/views/pendingTags.php', [
        'pendingTags' => $pendingTags,
        'paginationTotal' => $maxPage,
        'paginationNow' => $p,
        'paginationItemCount' => $imageCnt,
        'paginationItemStart' => $sttIdx,
        'paginationItemEnd' => $sttIdx + 30,
    ]);
    $transaction->finish();
});

$klein->respond('GET', '/tag/assistant', function ($request, $response, $service, $app) {
    $transaction = createAndStartWebTransaction('GET /tag/assistant');
    $service->render(__DIR__ . '/views/tagAssistant.php', [
    ]);
    $transaction->finish();
});

require __DIR__ . '/routes/search.php';
require __DIR__ . '/routes/metric.php';

header('Document-Policy: js-profiling');
header('Access-Control-Allow-Headers: sentry-trace, baggage');
$klein->dispatch();
