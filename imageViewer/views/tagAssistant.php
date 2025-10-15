<?php
require_once __DIR__ . '/components/tagging_assistant.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Images match: <?= $this->escape($this->searchParam) ?></title>
    <?php require __DIR__ . '/components/heads.php' ?>
</head>

<body>
    <?= component_tag_assistant_loader($this->transaction) ?>
</body>
</html>
