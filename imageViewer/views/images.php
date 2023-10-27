<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Images match: <?= $this->escape($this->searchParam) ?></title>
</head>

<body>
    <h1><?= $this->sqlQuery ?></h1>
    <header>
        <h1 class="query-ind query-h1">Query: <?= $this->escape($this->searchParam) ?></h1>
    </header>
    <?php foreach ($this->images as $img) : ?>
        <a href="/image/<?= $img['id'] ?>"><img src="/image/<?= $img['id'] ?>/thumb" alt="img" loading="lazy" /></a>
    <?php endforeach; ?>
</body>

</html>