<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Image: <?= $this->imageId ?></title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">View: imageId:<?= $this->imageId ?></h1>
    </header>
    <div>
        <img class="viewer-img" src="/image/<?= $this->imageId ?>/raw" alt="Image">
        <div>
            <dl>
                <dt>Tags</dt>
                <dd>
                    <ul class="forever-ul">
                        <?php foreach ($this->tags as $v) : ?>
                            <li><a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </dd>
                <dt>Server Path</dt>
                <dd><?= $this->escape($this->srvPath) ?></dd>
            </dl>
        </div>
    </div>
</body>

</html>