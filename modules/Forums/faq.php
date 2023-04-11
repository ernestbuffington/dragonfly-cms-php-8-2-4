<?php
/***************************************************************************
 *									faq.php
 *							  -------------------
 *	 begin				  : Sunday, Jul 8, 2001
 *	 copyright			  : (C) 2001 The phpBB Group
 *	 email				  : support@phpbb.com
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

$faq = array();

//
// Load the appropriate faq file
//
if ('bbcode' == $_GET['mode']) {
	$lang_file = 'lang_bbcode';
	$l_title = $lang['BBCode_guide'];
} else {
	$lang_file = 'lang_faq';
	$l_title = $lang['FAQ'];
}
if (is_file("includes/l10n/{$template->L10N->lng}/Forums/{$lang_file}.php")) {
	require_once "includes/l10n/{$template->L10N->lng}/Forums/{$lang_file}.php";
} else {
	require_once "includes/l10n/en/Forums/{$lang_file}.php";
}

//
// Pull the array data from the lang pack
//
$j = $counter = $counter_2 = 0;
$faq_blocks = $faq_blocks_titles = array();

foreach ($faq as $faq_item) {
	if ($faq_item[0] != '--') {
		$faq_blocks[$j][$counter]['id'] = $counter_2;
		$faq_blocks[$j][$counter]['question'] = $faq_item[0];
		$faq_blocks[$j][$counter]['answer'] = $faq_item[1];
		++$counter;
		++$counter_2;
	} else {
		$j = ( $counter != 0 ) ? $j + 1 : 0;
		$faq_blocks_titles[$j] = $faq_item[1];
		$counter = 0;
	}
}
unset($faq);

//
// Lets build a page ...
//

\Dragonfly\Page::title($l_title);

$template->assign_vars(array(
	'L_FAQ_TITLE' => $l_title,
	'L_BACK_TO_TOP' => $lang['Back_to_top'],
));

foreach ($faq_blocks as $i => $faq_block) {
	if (count($faq_block)) {
		$template->assign_block_vars('faq_block', array(
			'BLOCK_TITLE' => $faq_blocks_titles[$i]
		));
		$template->assign_block_vars('faq_block_link', array(
			'BLOCK_TITLE' => $faq_blocks_titles[$i]
		));
		foreach ($faq_block as $faq_item) {
			$template->assign_block_vars('faq_block.faq_row', array(
				'FAQ_QUESTION' => $faq_item['question'],
				'FAQ_ANSWER' => $faq_item['answer'],
				'U_FAQ_ID' => $faq_item['id'],
				'REQUEST_URI' => $_SERVER['REQUEST_URI']
			));

			$template->assign_block_vars('faq_block_link.faq_row_link', array(
				'FAQ_LINK' => $faq_item['question'],
				'U_FAQ_LINK' => $_SERVER['REQUEST_URI'].'#' . $faq_item['id']
			));
		}
	}
}
unset($faq_blocks, $faq_blocks_titles);

require_once('includes/phpBB/page_header.php');
make_jumpbox('viewforum');
$template->display('forums/faq_body');
