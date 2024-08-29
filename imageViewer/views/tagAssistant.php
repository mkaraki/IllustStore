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
</head>

<body>
    <?= component_tag_assistant_loader() ?>
</body>
</html>
