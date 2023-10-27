<?php
$res = DB::query(
    'SELECT
        tA.tagId AS id,
        t.tagName,
        COUNT(tA.tagId) AS count
    FROM
        tags t,
        tagAssign tA
    WHERE
        tA.tagId = t.id
    GROUP BY tA.tagId
    ORDER BY tagName'
);

$tags = DB::queryFirstField(
    'SELECT COUNT(id) FROM tags'
);

$maxCount = DB::queryFirstField(
    'SELECT COUNT(tagId) FROM tagAssign GROUP BY tagId'
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
    <ul class="forever-ul tag-cloud">
        <?php foreach ($res as $v) : ?>
            <li><a href="/tag/<?= $v['id'] ?>" style="font-size: <?= $initSize + ((-pow((doubleval($v['count']) / $maxCount) - 1, 2) + 1) * $usableSize) ?>pt"><?= htmlentities($v['tagName']) ?></a> (<?= $v['count'] ?>)</li>
        <?php endforeach; ?>
    </ul>
    <footer>
        <?= $tags ?> tags found.<br />
        MAX <?= $maxCount ?> times used.
    </footer>
</body>

</html>