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
                <input type="submit" value="Search">
            </div>
            <div id="search-tag-auto-complete"></div>
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
    </script>
</body>

</html>