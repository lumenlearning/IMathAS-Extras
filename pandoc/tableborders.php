#!/usr/bin/env php
<?php
/**
 * Pandoc filter: marks tables that should have cell borders in the Word file.
 *
 * Pandoc's docx writer gives every table the same style and ignores HTML borders.
 * The HTML marks a table that should have borders with the class "table-borders";
 * this filter gives each such table an id starting with TABLE_BORDERS_ID_PREFIX.
 * Pandoc writes a table's id as a bookmark just before the table, and
 * addDocxTableBorders() (docxtableborders.php) then adds the borders to the tables
 * after those bookmarks and removes the bookmarks.
 */
error_reporting(E_ALL & ~E_NOTICE);
require_once __DIR__ . '/./pandocfilters.php';
require_once __DIR__ . '/./docxtableborders.php';

$tableBordersCount = 0;

Pandoc_Filter::toJSONFilter(function ($type, $value, $format, $meta) use (&$tableBordersCount) {

    // A Table's first value is its attributes: [id, classes, key-value pairs].
    if ('Table' == $type && in_array(TABLE_BORDERS_CLASS, $value[0][1], true)) {
        $tableBordersCount++;
        $value[0][0] = TABLE_BORDERS_ID_PREFIX . $tableBordersCount;
        return (object) array('t' => 'Table', 'c' => $value);
    }
});
