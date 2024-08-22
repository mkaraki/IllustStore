<?php
require __DIR__ . '/components/tag_list.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Pending Tags</title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">Pending Tags</h1>
    </header>
    <div>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Tags</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->pendingTags as $i) : ?>
                    <?php
                    $tags = DB::query(
                        'SELECT
                            tA.tagId AS id,
                            t.tagName,
                            tA.autoAssigned
                        FROM
                            tagAssign tA,
                            tags t
                        WHERE
                            tA.tagId = t.id AND
                            tA.illustId = %i
                        ORDER BY t.tagName',
                        $i['imageId'],
                    );
                    $negativeTags = DB::query(
                        'SELECT
                            tNA.tagId AS id,
                            t.tagName
                        FROM
                            tagNegativeAssign tNA,
                            tags t
                        WHERE
                            tNA.tagId = t.id AND
                            tNA.illustId = %i',
                        $i['imageId'],
                    )
                    ?>
                    <tr>
                        <td>
                            <?php if (defined("IMG_SERVER_BASE")) : ?>
                                <a href="<?= IMG_SERVER_BASE ?>/image/<?= $i['imageId'] ?>/raw">
                                    <img class="viewer-img" src="<?= IMG_SERVER_BASE ?>/image/<?= $i['imageId'] ?>/thumb" alt="Image" loading="lazy">
                                </a>
                            <?php else : ?>
                                <a href="/image/<?= $i['imageId'] ?>/raw">
                                    <img class="viewer-img" src="/image/<?= $i['imageId'] ?>/thumb" alt="Image" loading="lazy">
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <dl>
                                <dt>Tags</dt>
                                <dd>
                                    <?= component_tag_list($tags, true, $i['imageId'], $this->paginationNow) ?>
                                </dd>
                                <dt>Negative Tags</dt>
                                <dd>
                                    <?= component_negative_tag_list($negativeTags, true, $i['imageId'], $this->paginationNow) ?>
                                </dd>
                            </dl>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer>
        <?php if (isset($this->paginationTotal) && $this->paginationTotal > 1) : ?>
            <?php if ($this->paginationNow > 1) : ?>
                <a href="?p=<?= $this->paginationNow - 1 ?>">Previous</a> |
            <?php endif; ?>
            <?= $this->paginationNow ?>
            <?php if ($this->paginationNow < $this->paginationTotal) : ?>
                | <a href="?p=<?= $this->paginationNow + 1 ?>">Next</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($this->paginationItemCount) && isset($this->paginationTotal)) : ?>
            <div>
                <?= $this->paginationItemCount ?> items.
                <?= $this->paginationTotal ?> pages.
            </div>
            <?php if (isset($this->paginationItemStart) && isset($this->paginationItemEnd)) : ?>
                <div>
                    Showing <?= $this->paginationItemStart ?> - <?= $this->paginationItemEnd ?>. <br />
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </footer>
</body>

</html>