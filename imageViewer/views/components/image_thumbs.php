<?php

function component_image_thumb_simple_go_raw(int $imageId): string {
    ob_start(); ?>
    <a href="/image/<?= $imageId ?>/raw">
        <img src="<?= IMG_SERVER_BASE ?>/image/<?= $imageId ?>/thumb" alt="img" loading="lazy" />
    </a>
    <?php
    return ob_get_clean();
}

function component_image_thumb_simple(int $imageId): string {
    ob_start(); ?>
        <a href="/image/<?= $imageId ?>">
            <img src="<?= IMG_SERVER_BASE ?>/image/<?= $imageId ?>/thumb" alt="img" loading="lazy" />
        </a>
    <?php
    return ob_get_clean();
}

function component_image_thumb(array $img): string {
    ob_start(); ?>
    <a href="/image/<?= $img['id'] ?>">
        <img src="<?= IMG_SERVER_BASE ?>/image/<?= $img['id'] ?>/thumb" alt="img" loading="lazy"
            <?php // Width and height pre-notice.
            if (is_numeric($img['width']) && is_numeric($img['height'])) {
                $targetSize = IMG_SCALE_SIZE;

                if ($img['width'] <= $targetSize && $img['height'] <= $targetSize) {
                    print('width="' . $img['width'] . '" height="' . $img['height'] . '"');
                } else {
                    $widthFloat = floatval($img['width']);
                    $heightFloat = floatval($img['height']);

                    if ($img['width'] > $img['height']) {
                        $scale = $targetSize / $widthFloat;
                    } else {
                        $scale = $targetSize / $heightFloat;
                    }

                    $afterWidth = ceil($widthFloat * $scale);
                    $afterHeight = ceil($heightFloat * $scale);

                    print('width="' . $afterWidth . '" height="' . $afterHeight . '"');
                }
            }
            // end of Width and height pre-notice ?>
        />
    </a>
    <?php
    return ob_get_clean();
}

function component_image_thumbs(array $images):string {
    $ret = '';
    foreach ($images as $image) {
        $ret .= component_image_thumb($image);
    }
    return $ret;
}