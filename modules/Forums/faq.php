<?php
/***************************************************************************
 *									faq.php
 *							  -------------------
 *	 begin				  : Sunday, Jul 8, 2001
 *	 copyright			  : (C) 2001 The phpBB Group
 *	 email				  : support@phpbb.com
 *
 *	 $Id: faq.php,v 9.7 2007/05/15 00:01:59 phoenix Exp $
 *
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
if (!defined('CPG_NUKE')) { exit; }
require_once('modules/'.$module_name.'/nukebb.php');

//
// Start session management
//
$userdata = session_pagestart($user_ip, PAGE_FAQ);
init_userprefs($userdata);
//
// End session management
//
$faq = array();

//
// Load the appropriate faq file
//
if( isset($_GET['mode']) ) {
	switch( $_GET['mode'] )
	{
		case 'bbcode':
			$lang_file = 'lang_bbcode';
			$l_title = $lang['BBCode_guide'];
			break;
		default:
			$lang_file = 'lang_faq';
			$l_title = $lang['FAQ'];
			break;
	}
} else {
	$lang_file = 'lang_faq';
	$l_title = $lang['FAQ'];
}
require_once('language/'.$board_config['default_lang'].'/Forums/'.$lang_file.'.php');
//
// Pull the array data from the lang pack
//
$j = $counter = $counter_2 = 0;
$faq_block = $faq_block_titles = array();

for($i = 0; $i < count($faq); $i++) {
	if( $faq[$i][0] != '--' ) {
		$faq_block[$j][$counter]['id'] = $counter_2;
		$faq_block[$j][$counter]['question'] = $faq[$i][0];
		$faq_block[$j][$counter]['answer'] = $faq[$i][1];
		$counter++;
		$counter_2++;
	} else {
		$j = ( $counter != 0 ) ? $j + 1 : 0;
		$faq_block_titles[$j] = $faq[$i][1];
		$counter = 0;
	}
}

//
// Lets build a page ...
//
$page_title = $l_title;
require_once('includes/phpBB/page_header.php');

make_jumpbox('viewforum');

$template->assign_vars(array(
	'L_FAQ_TITLE' => $l_title,
	'L_BACK_TO_TOP' => $lang['Back_to_top'],
	'L_GO' => $lang['Go'])
);

for($i = 0; $i < count($faq_block); $i++) {
	if( is_countable($faq_block[$i]) ? count($faq_block[$i]) : 0 ) {
		$template->assign_block_vars('faq_block', array(
			'BLOCK_TITLE' => $faq_block_titles[$i])
		);
		$template->assign_block_vars('faq_block_link', array(
			'BLOCK_TITLE' => $faq_block_titles[$i])
		);

		for($j = 0; $j < (is_countable($faq_block[$i]) ? count($faq_block[$i]) : 0); $j++) {
			$row_color = ( !($j % 2) ) ? $bgcolor2 : $bgcolor1;
			$row_class = ( !($j % 2) ) ? 'row1' : 'row2';

			$template->assign_block_vars('faq_block.faq_row', array(
				'ROW_COLOR' => $row_color,
				'ROW_CLASS' => $row_class,
				'FAQ_QUESTION' => $faq_block[$i][$j]['question'],
				'FAQ_ANSWER' => $faq_block[$i][$j]['answer'],
				'U_FAQ_ID' => $faq_block[$i][$j]['id'],
				'REQUEST_URI' => get_uri())
			);

			$template->assign_block_vars('faq_block_link.faq_row_link', array(
				'ROW_COLOR' => $row_color,
				'ROW_CLASS' => $row_class,
				'FAQ_LINK' => $faq_block[$i][$j]['question'],
				'U_FAQ_LINK' => get_uri().'#' . $faq_block[$i][$j]['id'])
			);
		}
	}
}

$template->set_filenames(array('body' => 'forums/faq_body.html'));

require_once('includes/phpBB/page_tail.php');
