<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">New Tag</h1>
    </header>
    <main>
        <form action="/tag/new" method="post">
            <dl>
                <dt>
                    <label for="tagName">Tag Name (For search. Must be unique. No space)</label>
                </dt>
                <dd>
                    <input type="text" name="tagName" id="tagName">
                </dd>
                <dt>
                    <label for="tagDanbooru">Danbooru Tag Name</label>
                </dt>
                <dd>
                    <input type="text" name="tagDanbooru" id="tagDanbooru">
                </dd>
                <dt>
                    <label for="tagPixivJpn">Pixiv 日本語 Tag</label>
                </dt>
                <dd>
                    <input type="text" name="tagPixivJpn" id="tagPixivJpn">
                </dd>
                <dt>
                    <label for="tagPixivEng">Pixiv English Tag (This must translated tag from Japanese. Not individual)</label>
                </dt>
                <dd>
                    <input type="text" name="tagPixivEng" id="tagPixivEng">
                </dd>
            </dl>
            <div>
                <input type="submit" value="Add">
            </div>
        </form>
    </main>
</body>

</html>