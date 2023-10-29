<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Illust Store</title>
</head>

<body class="text-align--center">
    <div>
        <h1>Illust Store</h1>
    </div>
    <div>
        <form action="/search" method="get">
            <div>
                <input type="text" name="q" id="searchQuery">
                <input type="button" value="Search">
            </div>
        </form>
    </div>
    <div>
        <ul class="forever-ul">
            <?php foreach (DB::query('SELECT * FROM tags ORDER BY RAND() LIMIT 7') as $v) : ?>
                <li><a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a></li>
            <?php endforeach; ?>
            <li><a href="/tag/">more...</a></li>
        </ul>
    </div>
</body>

</html>