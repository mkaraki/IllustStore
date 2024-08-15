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
            <?php if ($this->aHash !== null) : ?>
                <dt>Average Hash</dt>
                <dd>
                    <a href="/search/aHash/<?= $this->escape($this->aHash) ?>">
                        <?= $this->escape($this->aHash) ?>
                    </a>
                    (<a href="/search/aHash/<?= $this->escape($this->aHash) ?>?exact=1">exact</a>)
                </dd>
            <?php endif; ?>
            <?php if ($this->pHash !== null) : ?>
                <dt>Perceptual Hash</dt>
                <dd>
                    <a href="/search/pHash/<?= $this->escape($this->pHash) ?>">
                        <?= $this->escape($this->pHash) ?>
                    </a>
                    (<a href="/search/pHash/<?= $this->escape($this->pHash) ?>?exact=1">exact</a>)
                </dd>
            <?php endif; ?>
            <?php if ($this->dHash !== null) : ?>
                <dt>Difference Hash</dt>
                <dd>
                    <a href="/search/dHash/<?= $this->escape($this->dHash) ?>">
                        <?= $this->escape($this->dHash) ?>
                    </a>
                    (<a href="/search/dHash/<?= $this->escape($this->dHash) ?>?exact=1">exact</a>)
                </dd>
            <?php endif; ?>
            <?php if ($this->colorHash !== null) : ?>
                <dt>Color Hash</dt>
                <dd>
                    <a href="/search/colorHash/<?= $this->escape($this->colorHash) ?>">
                        <?= $this->escape($this->colorHash) ?>
                    </a>
                    (<a href="/search/colorHash/<?= $this->escape($this->colorHash) ?>?exact=1">exact</a>)
                </dd>
            <?php endif; ?>
        </dl>
    </div>
</div>
</body>

</html>
