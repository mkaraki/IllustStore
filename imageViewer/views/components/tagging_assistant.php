<?php

function component_tag_assistant_checkbox($tag):string {
    $rand = random_int(PHP_INT_MIN, PHP_INT_MAX);
    $ret = '<input type="checkbox"
                   id="chk-' . $rand . '-' . $tag['id'] . '"
                   name="tag-' . $tag['id'] . '-positive"
                   data-tag-id="' . $tag['id'] . '"
                   class="tag-check-box"
                   />' .
        '<label for="chk-' . $rand . '-' . $tag['id'] . '">' .
        htmlentities($tag['tagName']) . ' - ' .
        htmlentities($tag['description'] ?? 'No description') .
        '</label>' .
        ' (<a href="/tag/' . $tag['id'] . '" target="_blank">detail</a>)';

    if (isset($tag['taggingNote'])) {
        $ret .= '<br /><ul><li>' . htmlentities($tag['taggingNote']) . '</li></ul>';
    }

    return $ret;
}

function component_tag_assistant_non_grouped_tags_group($transaction):string {
    $ret = '<li>Non grouped tags'
    . '<ul>';

    $span = createAndStartDbSpan($transaction, 'SELECT
        t.id, 
        t.tagName,
        t.description,
        t.taggingNote
    FROM
        tags t
    WHERE
        t.aliasOf IS NULL AND
        t.tagGroup IS NULL AND
        t.selectiveTagGroup IS NULL');
    $nonGroupedTags = DB::query('SELECT
        t.id, 
        t.tagName,
        t.description,
        t.taggingNote
    FROM
        tags t
    WHERE
        t.aliasOf IS NULL AND
        t.tagGroup IS NULL AND
        t.selectiveTagGroup IS NULL');
    finishSpanAndReturn($transaction, $span);

    foreach ($nonGroupedTags as $tag) {
        $ret .= '<li>' . component_tag_assistant_checkbox($tag) . '</li>';
    }

    $ret .= '</ul></li>';
    return $ret;
}

function component_tag_assistant_tag_group($transaction, array $tagGroup):string {
    $span = createAndStartDbSpan($transaction, "SELECT tG.name FROM tagGroups tG WHERE tG.id = ?");
    $groupData = DB::queryFirstRow("SELECT tG.name FROM tagGroups tG WHERE tG.id = %i", $tagGroup['id']);
    finishSpanAndReturn($transaction, $span);
    $ret = '<li>Group:' . htmlentities($groupData['name'])
    . '<ul>';

    $span = createAndStartDbSpan($transaction, "SELECT tG.id FROM tagGroups tG WHERE tG.parentGroup = ?");
    $childTags = DB::query("SELECT tG.id FROM tagGroups tG WHERE tG.parentGroup = %i", $tagGroup['id']);
    finishSpanAndReturn($transaction, $span);

    foreach ($childTags as $childTag) {
        $ret .= component_tag_assistant_tag_group($transaction, $childTag['id']);
    }

    $ret .= '</ul></li>';
    return $ret;
}

function component_tag_assistant_all_tags($transaction): string{
    $ret = '<ul>';
    $ret .= component_tag_assistant_non_grouped_tags_group($transaction);

    $span = createAndStartDbSpan($transaction, 'SELECT
        tG.id,
        tG.name,
        tG.description
    FROM
        tagGroups tG
    WHERE
        tG.parentId IS NULL');
    $availableTagGroups = DB::query('SELECT
        tG.id,
        tG.name,
        tG.description
    FROM
        tagGroups tG
    WHERE
        tG.parentId IS NULL');
    finishSpanAndReturn($transaction, $span);

    foreach ($availableTagGroups as $tagGroup) {
        $ret .= component_tag_assistant_tag_group($transaction, $tagGroup);
    }

    $ret .= '</ul>';
    return $ret;
}

function component_tag_assistant_loader($transaction): string {
    // ToDo: add cache for list.
    return component_tag_assistant_all_tags($transaction);
}
