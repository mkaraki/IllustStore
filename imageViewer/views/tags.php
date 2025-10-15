<?php
$res = $this->res;
$page = $this->page;
$offset = $this->offset;
$limit = $this->limit;
$tags = $this->tags;
$maxCount = $this->maxCount;

if ($maxCount == 0) { $maxCount = 1; }
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
    <?php require __DIR__ . '/components/heads.php' ?>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">Tags</h1>
        <a href="/tag/new">New tag</a> | <a href="/tag/pending">Tagging queue</a>
    </header>
    <ul class="forever-ul tag-cloud">
        <?php foreach ($res as $v) : 
        $fontSize = $initSize + ((-pow((doubleval($v['count']) / $maxCount) - 1, 2) + 1) * $usableSize);
        ?>
            <li><a href="/tag/<?= $v['id'] ?>" style="font-size: <?= $fontSize ?>pt"><?= htmlentities($v['tagName']) ?></a> (<?= $v['count'] ?? 0 ?>)</li>
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
