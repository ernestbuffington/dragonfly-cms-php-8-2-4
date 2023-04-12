<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Private_Messages/init.inc,v $
  $Revision: 9.5 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 01:52:39 $
**********************************************/
if (!is_user()) {
	url_redirect(getlink('Your_Account'), true);
}

// Cancel
if (isset($_POST['cancel'])) {
	if (isset($_POST['folder']) || isset($_GET['folder'])) {
		$folder = isset($_POST['folder']) ? $_POST['folder'] : $_GET['folder'];
	} else {
		$folder = 'inbox';
	}
	url_redirect(getlink('&amp;folder='.$folder));
}
global $MAIN_CFG, $CPG_SESS;

define('IN_PHPBB', true);
$phpbb_root_path = 'modules/Forums/';
include($phpbb_root_path.'common.php');
if ($module_title == '') {
	$mod_name = ereg_replace('_', ' ', $name);
} else {
	$mod_name = $module_title;
}
require_once('includes/nbbcode.php');
require_once('includes/phpBB/functions_post.php');
require_once('modules/Your_Account/functions.php');

//define('MEMBER_BLOCK', true);
global $Blocks;
$block = array(
	'bid' => 10000,
	'view' => 1,
	'side' => 'l',
	'title' => _TB_BLOCK,
	'content' => member_block()
);
$Blocks->custom($block);
$block = NULL;
if (!defined('PRIVMSGS_READ_MAIL')) define('PRIVMSGS_READ_MAIL', 0);
if (!defined('PRIVMSGS_NEW_MAIL')) define('PRIVMSGS_NEW_MAIL', 1);
if (!defined('PRIVMSGS_SENT_MAIL')) define('PRIVMSGS_SENT_MAIL', 2);
if (!defined('PRIVMSGS_SAVED_IN_MAIL')) define('PRIVMSGS_SAVED_IN_MAIL', 3);
if (!defined('PRIVMSGS_SAVED_OUT_MAIL')) define('PRIVMSGS_SAVED_OUT_MAIL', 4);
if (!defined('PRIVMSGS_UNREAD_MAIL')) define('PRIVMSGS_UNREAD_MAIL', 5);

$html_entities_match = array('#&(?!(\#[0-9]+;))#', '#<#', '#>#');
$html_entities_replace = array('&amp;', '&lt;', '&gt;');

//
// Parameters
//
$submit = isset($_POST['post']);
$submit_search = isset($_POST['usersubmit']);
$submit_msgdays = isset($_POST['submit_msgdays']);
$preview = isset($_POST['preview']);
$confirm = isset($_POST['confirm']);
$delete	 = isset($_POST['delete']);
$delete_all = isset($_POST['deleteall']);
$save = isset($_POST['save']);

$refresh = $preview || $submit_search;

$mark_list = ( !empty($_POST['mark']) ) ? $_POST['mark'] : 0;

if ( isset($_POST['folder']) || isset($_GET['folder']) ) {
	$folder = ( isset($_POST['folder']) ) ? $_POST['folder'] : $_GET['folder'];
	if ( $folder != 'inbox' && $folder != 'outbox' && $folder != 'sentbox' && $folder != 'savebox' ) {
		$folder = 'inbox';
	}
} else {
	$folder = 'inbox';
}

//
// Start session management
//
$userdata = session_pagestart($user_ip, '-10');
init_userprefs($userdata);

//
// Var definitions
//
if ( !empty($_POST['mode']) || !empty($_GET['mode']) ) {
	$mode = ( !empty($_POST['mode']) ) ? $_POST['mode'] : $_GET['mode'];
	$mode = htmlprepare($mode);
} else {
	$mode = '';
}

$start = ( !empty($_GET['start']) ) ? intval($_GET['start']) : 0;

if ( isset($_POST['p']) || isset($_GET['p']) ) {
	$privmsg_id = ( isset($_POST['p']) ) ? intval($_POST['p']) : intval($_GET['p']);
} else {
	$privmsg_id = '';
}

$error = FALSE;
$l_box_name = $lang[ucfirst($folder)];

//
// Define the box image links
//
$inbox_img = ( $folder != 'inbox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=inbox').'"><img src="'.$images['pm_inbox'].'" alt="'.$lang['Inbox'].'" /></a>' : '<img src="'.$images['pm_inbox'].'" alt="'.$lang['Inbox'].'" />';
$inbox_url = ( $folder != 'inbox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=inbox').'">'.$lang['Inbox'].'</a>' : $lang['Inbox'];

$outbox_img = ( $folder != 'outbox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=outbox').'"><img src="'.$images['pm_outbox'].'" alt="'.$lang['Outbox'].'" /></a>' : '<img src="'.$images['pm_outbox'].'" alt="'.$lang['Outbox'].'" />';
$outbox_url = ( $folder != 'outbox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=outbox').'">'.$lang['Outbox'].'</a>' : $lang['Outbox'];

$sentbox_img = ( $folder != 'sentbox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=sentbox').'"><img src="'.$images['pm_sentbox'].'" alt="'.$lang['Sentbox'].'" /></a>' : '<img src="'.$images['pm_sentbox'].'" alt="'.$lang['Sentbox'].'" />';
$sentbox_url = ( $folder != 'sentbox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=sentbox').'">'.$lang['Sentbox'].'</a>' : $lang['Sentbox'];

$savebox_img = ( $folder != 'savebox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=savebox').'"><img src="'.$images['pm_savebox'].'" alt="'.$lang['Savebox'].'" /></a>' : '<img src="'.$images['pm_savebox'].'" alt="'.$lang['Savebox'].'" />';
$savebox_url = ( $folder != 'savebox' || $mode != '' ) ? '<a href="'.getlink('&amp;folder=savebox').'">'.$lang['Savebox'].'</a>' : $lang['Savebox'];

function pm_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = TRUE)
{
	global $lang;
	$total_pages = ceil($num_items/$per_page);
	if ($total_pages == 1) { return ''; }
	$on_page = floor($start_item / $per_page) + 1;
	$page_string = '';
	if ( $total_pages > 10 ) {
		$init_page_max = ( $total_pages > 3 ) ? 3 : $total_pages;
		for($i = 1; $i < $init_page_max + 1; $i++) {
			$page_string .= ( $i == $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.getlink($base_url.'&amp;start='.( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
			if ( $i <  $init_page_max ) { $page_string .= ', '; }
		}
		if ( $total_pages > 3 ) {
			if ( $on_page > 1  && $on_page < $total_pages ) {
				$page_string .= ( $on_page > 5 ) ? ' ... ' : ', ';
				$init_page_min = ( $on_page > 4 ) ? $on_page : 5;
				$init_page_max = ( $on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;
				for($i = $init_page_min - 1; $i < $init_page_max + 2; $i++) {
					$page_string .= ($i == $on_page) ? '<b>'.$i.'</b>' : '<a href="'.getlink($base_url.'&amp;start='.( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
					if ( $i <  $init_page_max + 1 ) { $page_string .= ', '; }
				}
				$page_string .= ( $on_page < $total_pages - 4 ) ? ' ... ' : ', ';
			} else {
				$page_string .= ' ... ';
			}
			for($i = $total_pages - 2; $i < $total_pages + 1; $i++) {
				$page_string .= ( $i == $on_page ) ? '<b>'.$i.'</b>'  : '<a href="'.getlink($base_url.'&amp;start='.( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
				if( $i <  $total_pages ) { $page_string .= ", "; }
			}
		}
	} else {
		for($i = 1; $i < $total_pages + 1; $i++) {
			$page_string .= ( $i == $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.getlink($base_url."&amp;start=".( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
			if ( $i <  $total_pages ) { $page_string .= ', '; }
		}
	}
	if ( $add_prevnext_text ) {
		if ( $on_page > 1 ) {
			$page_string = ' <a href="'.getlink($base_url."&amp;start=".( ( $on_page - 2 ) * $per_page ) ).'">'.$lang['Previous'].'</a>&nbsp;&nbsp;'.$page_string;
		}
		if ( $on_page < $total_pages ) {
			$page_string .= '&nbsp;&nbsp;<a href="'.getlink($base_url."&amp;start=".( $on_page * $per_page ) ).'">'.$lang['Next'].'</a>';
		}
	}
	$page_string = $lang['Goto_page'].' '.$page_string;
	return $page_string;
}
