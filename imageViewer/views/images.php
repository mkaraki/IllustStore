<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Images match: <?= $this->escape($this->searchParam) ?></title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">Query: <?= $this->escape($this->searchParam) ?></h1>
    </header>
    <?php if (isset($this->pageType)) : ?>
        <div class="query-info">
            <?php if ($this->pageType === 'tag') : ?>
                <span class="query-info-item">TagId: <?= $this->tagId ?></span>
                <?php if ($this->tagDanbooru !== null) : ?>
                    <span class="query-info-item">
                        Danbooru: <a href="https://danbooru.donmai.us/wiki_pages/<?= urlencode($this->tagDanbooru) ?>"><?= $this->escape($this->tagDanbooru) ?></a>
                    </span>
                <?php endif; ?>
                <?php if ($this->tagPixivJpn !== null) : ?>
                    <span class="query-info-item">
                        Pixiv: <a href="https://www.pixiv.net/tags/<?= urlencode($this->tagPixivJpn) ?>"><?= $this->escape($this->tagPixivJpn) ?></a>
                        <?php if ($this->tagPixivEng !== null) : ?>
                            [<?= $this->escape($this->tagPixivEng) ?>]
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (defined("IMG_SERVER_BASE")) : ?>
        <?php foreach ($this->images as $img) : ?>
            <a href="/image/<?= $img['id'] ?>"><img src="<?= IMG_SERVER_BASE ?>/image/<?= $img['id'] ?>/thumb" alt="img" loading="lazy" /></a>
        <?php endforeach; ?>
    <?php else : ?>
        <?php foreach ($this->images as $img) : ?>
            <a href="/image/<?= $img['id'] ?>"><img src="/image/<?= $img['id'] ?>/thumb" alt="img" loading="lazy" /></a>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($this->paginationTotal) && $this->paginationTotal > 1) : ?>
        <div>
            <?php for ($i = 1; $i <= $this->paginationTotal; $i++) : ?>
                <?php if ($this->paginationNow === $i) : ?>
                    <?= $i ?>
                <?php else : ?>
                    <?php if (isset($this->pageType) && $this->pageType === 'search') : ?>
                        <a href="?q=<?= urlencode($this->searchQuery) ?>&p=<?= $i ?>"><?= $i ?></a>
                    <?php else : ?>
                        <a href="?p=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($this->paginationItemCount)) : ?>
        <div>
            <?= $this->paginationItemCount ?> Items
        </div>
        <?php if (isset($this->paginationItemStart) && isset($this->paginationItemEnd)) : ?>
            <div>
                Showing <?= $this->paginationItemStart ?> - <?= $this->paginationItemEnd ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>