<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New/Edit Tag</title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">New/Edit Tag</h1>
    </header>
    <main>
        <form action="<?= isset($this->tagId) ? ('/tag/' . $this->tagId . '/edit') : '/tag/new'?>" method="post">
            <dl>
                <dt>
                    <label for="tagName">Tag Name (For search. Must be unique. No space)</label>
                </dt>
                <dd>
                    <input type="text" name="tagName" id="tagName" value="<?= htmlentities($this->tagName ?? '') ?>">
                </dd>
                <dt>
                    <label for="tagDanbooru">Danbooru Tag Name</label>
                </dt>
                <dd>
                    <input type="text" name="tagDanbooru" id="tagDanbooru" value="<?= htmlentities($this->tagDanbooru ?? '') ?>">
                </dd>
                <dt>
                    <label for="tagPixivJpn">Pixiv 日本語 Tag</label>
                </dt>
                <dd>
                    <input type="text" name="tagPixivJpn" id="tagPixivJpn" value="<?= htmlentities($this->tagPixivJpn ?? '') ?>">
                </dd>
                <dt>
                    <label for="tagPixivEng">Pixiv English Tag (This must translated tag from Japanese. Not individual)</label>
                </dt>
                <dd>
                    <input type="text" name="tagPixivEng" id="tagPixivEng" value="<?= htmlentities($this->tagPixivEng ?? '') ?>">
                </dd>
                <dt>
                    <label for="description">Tag description</label>
                </dt>
                <dd>
                    <input type="text" name="description" id="description" value="<?= htmlentities($this->description ?? '') ?>">
                </dd>
                <dt>
                    <label for="taggingNote">Tagging note</label>
                </dt>
                <dd>
                    <textarea id="taggingNote" name="taggingNote"><?= htmlentities($this->taggingNote ?? '') ?></textarea>
                </dd>
            </dl>
            <div>
                <input type="submit" value="Add or Edit">
            </div>
        </form>
    </main>
</body>

</html>