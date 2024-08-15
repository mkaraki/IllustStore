<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Illust Store</title>
</head>

<body class="text-align--center margin-bottom-children-div">
    <div>
        <h1>Illust Store</h1>
    </div>
    <div>
        <form action="/search" method="get">
            <div>
                <input type="text" name="q" id="searchQuery" />
                <input type="submit" value="Search" />
            </div>
            <div id="search-tag-auto-complete"></div>
        </form>
    </div>
    <div>
        <form action="/search/image" enctype="multipart/form-data" method="post" id="im-search-form">
            <div>
                <input type="file" name="img" accept="image/*" id="im-search-file" />
                <input type="submit" value="Image Search" />
            </div>
        </form>
    </div>
    <div>
        Random tags
        <ul class="forever-ul">
            <?php foreach (DB::query('SELECT * FROM tags ORDER BY RAND() LIMIT 7') as $v) : ?>
                <li><a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a></li>
            <?php endforeach; ?>
            <li><a href="/tag/">more...</a></li>
        </ul>
    </div>
    <div>
        <?php
        $nonTaggedImageAndTag = DB::queryFirstRow(
            'SELECT
                res.imageId,
                res.tagId,
                res.tagName
            FROM
                (
                    SELECT
                        tA.illustId AS imageId,
                        tA.tagId AS tagId,
                        t.tagName AS tagName
                    FROM
                        tagAssign tA,
                        tags t
                    WHERE
                        tA.autoAssigned = 1 AND
                        t.id = tA.tagId
                    LIMIT 5000
                ) res
            ORDER BY
                RAND()
            LIMIT 1'
        );
        ?>
        <?php if ($nonTaggedImageAndTag !== null) : ?>
            Tagging
            <div>
                <table class="margin--auto">
                    <tbody>
                        <tr>
                            <td>
                                <a href="/image/<?= $nonTaggedImageAndTag['imageId'] ?>/raw">
                                    <img src="/image/<?= $nonTaggedImageAndTag['imageId'] ?>/thumb" alt="img" loading="lazy" />
                                </a>
                            </td>
                            <td class="padding-left--30px">
                                Is this image contains
                                <code>
                                    <a href="/tag/<?= $nonTaggedImageAndTag['tagId'] ?>">
                                        <?= htmlentities($nonTaggedImageAndTag['tagName']) ?>
                                    </a>
                                </code>?
                                <br /><br />
                                <ul class="forever-ul">
                                    <li>
                                        <form
                                            action="/image/<?= $nonTaggedImageAndTag['imageId'] ?>/tag/<?= $nonTaggedImageAndTag['tagId'] ?>/approve"
                                            method="post"
                                            onsubmit="return confirm('Are you sure to approve tag: <?= str_replace("'", "\'", $nonTaggedImageAndTag['tagName']) ?>?')"
                                            >
                                            <input type="submit" value="Yes" />
                                        </form>
                                        |
                                        <form
                                            action="/image/<?= $nonTaggedImageAndTag['imageId'] ?>/tag/<?= $nonTaggedImageAndTag['tagId'] ?>/delete"
                                            method="post"
                                            onsubmit="return confirm('Are you sure to blacklist tag: <?= str_replace("'", "\'", $nonTaggedImageAndTag['tagName']) ?>?')"
                                            >
                                            <input type="submit" value="No" />
                                        </form>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div>
        Random images
        <div>
            <?php foreach ($this->images as $img) : ?>
                <a href="/image/<?= $img['id'] ?>"><img src="/image/<?= $img['id'] ?>/thumb" alt="img" loading="lazy" /></a>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        const sQ = document.getElementById('searchQuery');
        const searchTagAutoComplete = document.getElementById('search-tag-auto-complete');
        sQ.oninput = () => {
            const sQWords = sQ.value.split(' ');
            const sendWord = sQWords[sQWords.length - 1];
            const sendBody = JSON.stringify({
                'w': sendWord
            });
            if (sendWord == '')
                return;
            fetch('/util/tag/complete', {
                    'method': 'POST',
                    body: sendBody,
                    headers: {
                        "Content-Type": "application/json",
                    },
                })
                .then(d => d.json())
                .then(d => {
                    searchTagAutoComplete.innerHTML = '';
                    d['sw'].forEach(v => {
                        const acObj = document.createElement('a');
                        acObj.href = 'javascript:void(0)';
                        acObj.innerText = v;
                        acObj.onclick = () => {
                            const sQWords = sQ.value.split(' ');
                            sQWords.pop();
                            sQWords.push(v);
                            sQ.value = sQWords.join(' ') + ' ';
                            sQ.focus();
                        }
                        searchTagAutoComplete.appendChild(acObj);
                    })
                })
        }

        document.onpaste = (event) => {
            const clipData = event.clipboardData || window.clipboardData;
            if (clipData.files.length === 0) {
                return;
            }

            if (clipData.files.length > 1) {
                alert('Only one file accepted')
                return;
            }

            document.getElementById('im-search-file').files = clipData.files;
            document.getElementById('im-search-form').submit()
        };
    </script>
</body>

</html>