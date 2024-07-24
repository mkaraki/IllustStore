<?php
$page = intval($_GET['p'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$limit = 500;
$offset = ($page - 1) * $limit;

$res = DB::query(
    'SELECT
        t.id,
        t.tagName,
        (
            SELECT
                COUNT(tA.tagId) AS count
            FROM
                tagAssign tA
            WHERE
                t.id = tA.tagId
            GROUP BY 
                tA.tagId
        ) AS count
    FROM
        tags t
    ORDER BY
        t.tagName ASC
    LIMIT %i OFFSET %i',
    $limit,
    $offset
);

$tags = DB::queryFirstField(
    'SELECT COUNT(id) FROM tags'
);

$maxCount = DB::queryFirstField(
    'SELECT MAX(cntHost.cnt) FROM (SELECT COUNT(tagId) AS cnt FROM tagAssign GROUP BY tagId) cntHost;'
);

$maxCount = doubleval($maxCount);
$calcMaxCount = $maxCount - 1.0;
$usableSize = 20.0;
$initSize = 12.0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Tags</title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">Tags</h1>
        <a href="/tag/new">New tag</a>
    </header>
    <ul class="forever-ul tag-cloud">
        <?php foreach ($res as $v) : ?>
            <li><a href="/tag/<?= $v['id'] ?>" style="font-size: <?= $initSize + ((-pow((doubleval($v['count']) / $maxCount) - 1, 2) + 1) * $usableSize) ?>pt"><?= htmlentities($v['tagName']) ?></a> (<?= $v['count'] ?>)</li>
        <?php endforeach; ?>
    </ul>
    <footer>
        <?php for ($i = 1; $i <= ceil($tags / $limit); $i++) : ?>
            <?php if ($i != $page) : ?>
                <a href="/tag/?p=<?= $i ?>"><?= $i ?></a>
            <?php else : ?>
                <?= $i ?>
            <?php endif; ?>
        <?php endfor; ?>
        <br />
        <?= $tags ?> tags found.<br />
        Displaying <?= $offset + 1 ?> to <?= $offset + $limit ?>.<br />
        MAX <?= $maxCount ?> times used.
    </footer>
</body>

</html>