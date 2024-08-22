<?php
$selectableTags = [];
if (count($this->tags) > 0) {
    $assignedTags = [];
    foreach ($this->tags as $t)
        $assignedTags[] = $t['id'];
    $selectableTags = DB::query('SELECT id, tagName FROM tags WHERE id NOT IN %li ORDER BY tagName', $assignedTags);
} else {
    $selectableTags = DB::query('SELECT id, tagName FROM tags ORDER BY tagName');
}

require_once __DIR__ . '/components/image_thumbs.php';
require_once __DIR__ . '/components/tag_list.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/global.css">
    <title>Assign new tag to: <?= $this->illustId ?></title>
</head>

<body>
    <header>
        <h1 class="query-ind query-h1">Tag Assign: imageId:<?= $this->imageId ?></h1>
    </header>
    <div>
        <?= component_image_thumb_simple($this -> imageId) ?>
        <div>
            <dl>
                <dt>Current Tags</dt>
                <dd>
                    <?= component_tag_list($this->tags, false) ?>
                </dd>
                <dt>New tag info</dt>
                <dd>
                    <form action="/image/<?= $this->imageId ?>/tag/new" method="post">
                        <div>
                            <label for="newTag">New tag:</label>
                            <input type="text" name="newTagId" id="newTag" required>
                            <input type="hidden" name="pending" value="<?= $this->pending ?>">
                        </div>
                        <div id="tag-auto-complete"></div>
                        <div>
                            <input type="submit" value="Add">
                        </div>
                    </form>
                </dd>
            </dl>
        </div>
    </div>

<script>
    const newTag = document.getElementById('newTag');
    const tagAutoComplete = document.getElementById('tag-auto-complete');
    newTag.oninput = () => {
        if (newTag.value === '') {
            return;
        }
        const sendBody = JSON.stringify({
            'w': newTag.value
        })

        fetch('/util/tag/complete', {
            'method': 'POST',
            body: sendBody,
            headers: {
                "Content-Type": "application/json",
            },
        })
            .then(d => d.json())
            .then(d => {
                tagAutoComplete.innerHTML = '';
                d['sw'].forEach(v => {
                    const acObj = document.createElement('a');
                    acObj.href = 'javascript:void(0)';
                    acObj.innerText = v;
                    acObj.onclick = () => {
                        newTag.value = v;
                        newTag.focus();
                    }
                    tagAutoComplete.appendChild(acObj);
                })
            })
    }
</script>

</body>

</html>