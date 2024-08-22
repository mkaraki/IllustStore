<?php

function component_image_hashes(
    string|null $aHash,
    string|null $pHash,
    string|null $dHash,
    string|null $colorHash,
): string {
    ob_start(); ?>
    <?php if ($aHash !== null) : ?>
        <dt>Average Hash</dt>
        <dd>
            <a href="/search/aHash/<?= htmlspecialchars($aHash) ?>"
               class="monospace"
                ><?= htmlspecialchars(strtolower($aHash)) ?></a>
            &thinsp;
            (<a href="/search/aHash/<?= htmlspecialchars($aHash) ?>?exact=1">exact</a>)
        </dd>
    <?php endif; ?>
    <?php if ($pHash !== null) : ?>
        <dt>Perceptual Hash</dt>
        <dd>
            <a href="/search/pHash/<?= htmlspecialchars($pHash) ?>"
               class="monospace"
                ><?= htmlspecialchars(strtolower($pHash)) ?></a>
            &thinsp;
            (<a href="/search/pHash/<?= htmlspecialchars($pHash) ?>?exact=1">exact</a>)
        </dd>
    <?php endif; ?>
    <?php if ($dHash !== null) : ?>
        <dt>Difference Hash</dt>
        <dd>
            <a href="/search/dHash/<?= htmlspecialchars($dHash) ?>"
               class="monospace"
                ><?= htmlspecialchars(strtolower($dHash)) ?></a>
            &thinsp;
            (<a href="/search/dHash/<?= htmlspecialchars($dHash) ?>?exact=1">exact</a>)
        </dd>
    <?php endif; ?>
    <?php if ($colorHash !== null) : ?>
        <dt>Color Hash</dt>
        <dd>
            <a href="/search/colorHash/<?= htmlspecialchars($colorHash) ?>"
               class="monospace"
                ><?= htmlspecialchars(strtolower($colorHash)) ?></a>
            &thinsp;
            (<a href="/search/colorHash/<?= htmlspecialchars($colorHash) ?>?exact=1">exact</a>)
        </dd>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
