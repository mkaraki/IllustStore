<?php
require __DIR__ . '/components/image_view.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Image Hash Search</title>
</head>

<body>
<header>
    <h1 class="query-ind query-h1">View: client-image</h1>
</header>
<div>
    <?php if (isset($this->image)) : ?>
        <img src="<?= $this->escape($this->image) ?>" alt="client-image" />
    <?php endif; ?>
    <div>
        <dl>
            <?= component_image_hashes(
                    $this->aHash,
                    $this->pHash,
                    $this->dHash,
                    $this->colorHash
            ) ?>
        </dl>
    </div>
</div>
</body>

</html>
