<?php
require_once __DIR__ . '/components/tag_list.php';
require __DIR__ . '/components/image_view.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Image: <?= $this->imageId ?></title>
</head>

<body>
<header>
    <h1 class="query-ind query-h1">View: imageId:<?= $this->imageId ?></h1>
</header>
<div>
    <?php if (defined("IMG_SERVER_BASE")) : ?>
        <a href="<?= IMG_SERVER_BASE ?>/image/<?= $this->imageId ?>/raw">
            <img class="viewer-img" src="<?= IMG_SERVER_BASE ?>/image/<?= $this->imageId ?>/large" alt="Image">
        </a>
    <?php else : ?>
        <a href="/image/<?= $this->imageId ?>/raw">
            <img class="viewer-img" src="/image/<?= $this->imageId ?>/large" alt="Image">
        </a>
    <?php endif; ?>
    <div>
        <dl>
            <dt>Tags</dt>
            <dd>
                <?= component_tag_list($this->tags, true, $this->imageId) ?>
            </dd>
            <dt>Negative Tags</dt>
            <dd>
                <?= component_negative_tag_list($this->negativeTags, true, $this->imageId) ?>
            </dd>
            <?= component_image_hashes(
                $this->aHash,
                $this->pHash,
                $this->dHash,
                $this->colorHash
            ) ?>
            <dt>Metadata Provider</dt>
            <dd>
                <?php if ($this->metadata['metadataProviderName'] !== null) : ?>
                    <?= $this->escape($this->metadata['metadataProviderName']) ?>
                <?php else : ?>
                    <span>No Provider</span>
                <?php endif; ?>
            </dd>
            <?php if (isset($this->metadata['metadataProviderUrl'])) : ?>
                <dt>Provider's content page</dt>
                <dd>
                    <a href="<?= $this->metadata['metadataProviderUrl'] ?>" target="_blank">
                        <?= $this->escape($this->metadata['metadataProviderUrl']) ?>
                    </a>
                </dd>
            <?php endif; ?>
            <?php if (isset($this->metadata['apiMetadata'])) : ?>
                <?php foreach ($this->metadata['apiMetadata'] as $k => $v) : ?>
                    <dt>Metadata API: <?= $this->escape($k) ?></dt>
                    <dd><?= $this->escape($v) ?></dd>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (isset($this->metadata['metadataSourceUrl'])) : ?>
                <dt>Source URL</dt>
                <dd>
                    <a href="<?= $this->metadata['metadataSourceUrl'] ?>" target="_blank" rel="noreferrer">
                        <?= $this->escape($this->metadata['metadataSourceUrl']) ?>
                    </a>
                </dd>
            <?php endif; ?>
            <dt>Server Path</dt>
            <dd><?= $this->escape($this->srvPath) ?></dd>
        </dl>
    </div>
</div>
</body>

</html>