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
                            <li>
                                <a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a>
                                <?php if ($v['autoAssigned'] === '1') : ?>
                                    <form action="/image/<?= $this->imageId ?>/tag/<?= $v['id'] ?>/approve" method="post" onsubmit="return confirm('Are you sure to approve tag: <?= str_replace("'", "\'", $v['tagName']) ?>?')">
                                        <input type="submit" value="🤖">
                                    </form>
                                <?php else : ?>
                                    <span>✅</span>
                                <?php endif; ?>
                                <form action="/image/<?= $this->imageId ?>/tag/<?= $v['id'] ?>/delete" method="post" onsubmit="return confirm('Are you sure to delete tag: <?= str_replace("'", "\'", $v['tagName']) ?>?')">
                                    <input type="submit" value="🗑️">
                                </form>
                            </li>
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