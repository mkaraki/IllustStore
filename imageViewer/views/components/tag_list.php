<?php

function component_tag_list(array $tags, bool $allowEdit, int $imageId = 0, int $pendingPaginationNow = 0): string {
    ob_start(); ?>
    <ul class="forever-ul">
        <?php foreach ($tags as $v) : ?>
            <li>
                <a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a>
                <?php if ($allowEdit) : ?>
                    <?php if ($v['autoAssigned'] === '1') : ?>
                        <form action="/image/<?= $imageId ?>/tag/<?= $v['id'] ?>/approve" method="post" onsubmit="return confirm('Are you sure to approve tag: <?= str_replace("'", "\'", $v['tagName']) ?>?')">
                            <?php if ($pendingPaginationNow != 0) : ?>
                                <input type="hidden" name="pending" value="<?= $pendingPaginationNow ?>">
                            <?php endif; ?>
                            <input type="submit" value="🤖">
                        </form>
                    <?php else : ?>
                        <span>✅</span>
                    <?php endif; ?>
                    <form action="/image/<?= $imageId ?>/tag/<?= $v['id'] ?>/delete" method="post" onsubmit="return confirm('Are you sure to blacklist tag: <?= str_replace("'", "\'", $v['tagName']) ?>?')">
                        <?php if ($pendingPaginationNow != 0) : ?>
                            <input type="hidden" name="pending" value="<?= $pendingPaginationNow ?>">
                        <?php endif; ?>
                        <input type="submit" value="🗑️">
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if ($allowEdit) : ?>
            <li>
                <form action="/image/<?= $imageId ?>/tag/new" method="get">
                    <?php if ($pendingPaginationNow != 0) : ?>
                        <input type="hidden" name="pending" value="<?= $pendingPaginationNow ?>">
                    <?php endif; ?>
                    <input type="submit" value="➕">
                </form>
            </li>
        <?php endif; ?>
    </ul>
    <?php
    return ob_get_clean();
}

function component_negative_tag_list(array $tags, bool $allowEdit, int $imageId = 0, int $pendingPaginationNow = 0): string {
    ob_start(); ?>
    <ul class="forever-ul">
        <?php foreach ($tags as $v) : ?>
            <li>
                <a href="/tag/<?= $v['id'] ?>"><?= htmlentities($v['tagName']) ?></a>
                <?php if ($allowEdit) : ?>
                    <form action="/image/<?= $imageId ?>/tag/new" method="post" onsubmit="return confirm('Are you sure to approve tag: <?= str_replace("'", "\'", $v['tagName']) ?>?')">
                        <input type="hidden" name="newTagId" value="<?= $v['tagName'] ?>">
                        <?php if ($pendingPaginationNow != 0) : ?>
                            <input type="hidden" name="pending" value="<?= $pendingPaginationNow ?>">
                        <?php endif; ?>
                        <input type="submit" value="➕">
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if ($allowEdit) : ?>
            <li class="text-decoration--none">*</li>
        <?php endif; ?>
    </ul>
    <?php
    return ob_get_clean();
}