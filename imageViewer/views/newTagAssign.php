<?php
$selectableTags = [];
if (count($this->tags) > 0) {
    $assignedTags = [];
    foreach ($this->tags as $t)
        $assignedTags[] = $t['id'];
    $selectableTags = DB::query('SELECT id, tagName FROM tags WHERE id NOT IN %li ORDER BY tagName', $assignedTags);
} else {
    $selectableTags = DB::query('SELECT id, tagName FROM tags ORDER BY tagName');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Assign new tag to: <?= $this->illustId ?></title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">Tag Assign: imageId:<?= $this->imageId ?></h1>
    </header>
    <div>
        <img class="viewer-img" src="/image/<?= $this->imageId ?>/thumb" alt="Image">
        <div>
            <dl>
                <dt>Current Tags</dt>
                <dd>
                    <ul class="forever-ul">
                        <?php foreach ($this->tags as $v) : ?>
                            <?php $assignedTags[] = $v['id']; ?>
                            <li>
                                <a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </dd>
                <dt>New tag info</dt>
                <dd>
                    <form action="/image/<?= $this->imageId ?>/tag/new" method="post">
                        <div>
                            <label for="newTag">New tag:</label>
                            <input type="text" name="newTagId" id="newTag" list="newTagList" required>
                            <datalist id="newTagList">
                                <?php foreach ($selectableTags as $t) : ?>
                                    <option value="<?= $t['id'] ?>"><?= $this->escape($t['tagName']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                            <!--
                            <select id="newTag" name="newTagId" required>
                                <?php foreach ($selectableTags as $t) : ?>
                                    <option value="<?= $t['id'] ?>"><?= $this->escape($t['tagName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            -->
                        </div>
                        <div>
                            <input type="submit" value="Add">
                        </div>
                    </form>
                </dd>
            </dl>
        </div>
    </div>

</body>

</html>