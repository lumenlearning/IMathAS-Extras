<?php
/**
 * Adds cell borders to the tables marked by the tableborders.php pandoc filter.
 */

/** Class the HTML puts on a table that should have cell borders in the Word file. */
const TABLE_BORDERS_CLASS = 'table-borders';

/**
 * Start of the id tableborders.php gives a marked table. It must start with a letter:
 * pandoc replaces other bookmark names (Word's rule) with a hash.
 */
const TABLE_BORDERS_ID_PREFIX = 'tableBorders';

/** Thin single lines around and between all cells (sz is in eighths of a point). */
const TABLE_BORDERS_XML = '<w:tblBorders>'
	. '<w:top w:val="single" w:sz="4" w:space="0" w:color="auto" />'
	. '<w:left w:val="single" w:sz="4" w:space="0" w:color="auto" />'
	. '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto" />'
	. '<w:right w:val="single" w:sz="4" w:space="0" w:color="auto" />'
	. '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto" />'
	. '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto" />'
	. '</w:tblBorders>';

/**
 * Adds cell borders to the marked tables in a .docx file, and removes their bookmarks.
 *
 * Each marked table follows a bookmark whose name starts with TABLE_BORDERS_ID_PREFIX.
 * The borders go into the table's own properties, before tblLook as the Word schema
 * requires. Leaves the file unchanged if it cannot be read (or ZipArchive is missing).
 *
 * @param string $docxFile Path of the .docx file to update in place.
 */
function addDocxTableBorders($docxFile) {
	if (!class_exists('ZipArchive')) {
		return;
	}
	$zip = new ZipArchive();
	if ($zip->open($docxFile) !== true) {
		return;
	}
	$xml = $zip->getFromName('word/document.xml');
	if ($xml !== false && strpos($xml, TABLE_BORDERS_ID_PREFIX) !== false) {
		$bookmarkIds = array();
		$updated = preg_replace_callback(
			'#<w:bookmarkStart\b[^>]*\bw:name="' . TABLE_BORDERS_ID_PREFIX . '\d+"[^>]*/>(\s*<w:tbl>\s*<w:tblPr>)(.*?)(<w:tblLook\b|</w:tblPr>)#s',
			function ($match) use (&$bookmarkIds) {
				if (preg_match('#\bw:id="(\d+)"#', $match[0], $id)) {
					$bookmarkIds[] = $id[1];
				}
				return $match[1] . $match[2] . TABLE_BORDERS_XML . $match[3];
			},
			$xml);
		foreach ($bookmarkIds as $id) {
			if ($updated !== null) {
				$updated = preg_replace('#<w:bookmarkEnd\b[^>]*\bw:id="' . $id . '"[^>]*/>#', '', $updated);
			}
		}
		// On a regex failure (such as a backtrack limit), keep the file as pandoc wrote it.
		if ($updated !== null) {
			$zip->addFromString('word/document.xml', $updated);
		}
	}
	$zip->close();
}
