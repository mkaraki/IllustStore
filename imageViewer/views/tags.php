<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Tags</title>
</head>

<body>
    <ul class="forever-ul">
        <?php foreach (DB::query('SELECT id, tagName FROM tags ORDER BY tagName') as $v) : ?>
            <li><a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</body>

</html>