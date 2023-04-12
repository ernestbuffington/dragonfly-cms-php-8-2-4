<?php
/***************************************************************************
 *					memberlist.php
 *				  -------------------
 *	 begin		  : Friday, May 11, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
  Last modification notes:
  $Source: /cvs/html/modules/Members_List/index.php,v $
  $Revision: 9.18 $
  $Author: phoenix $
  $Date: 2007/08/27 12:26:11 $
 *
 ***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $pagetitle;
get_lang('Forums');
$mod_name = $module_title;
$members_per_page = 25;

global $MAIN_CFG, $CPG_SESS;

$start = (isset($_GET['start']) && intval($_GET['start'])) ? $_GET['start'] : 0;
$sort_order = isset($_POST['order']) ? $_POST['order'] : (isset($_GET['order']) ? $_GET['order'] : '');
$sort_order = (isset($sort_order) && $sort_order == 'DESC') ? 'DESC' : 'ASC';
$mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : 'default');

// Generate page //
$pagetitle .= _Members_ListLANG;
require_once('header.php');
OpenTable();

$template->assign_vars(array(
	'L_SELECT_SORT_METHOD' => $lang['Select_sort_method'],
	'L_USERNAME' => $lang['Username'],
	'L_EMAIL' => $lang['Ranks'],
	'L_RANK' => $lang['Ranks'],
	'L_WEBSITE' => $lang['Website'],
	'L_FROM' => $lang['Location'],
	'L_ORDER' => $lang['Order'],
	'L_PRIVATE_MESSAGE' => $lang['Private_Message'],
	'L_SORT' => $lang['Sort'],
	'L_SUBMIT' => $lang['Sort'],
	'L_AIM' => $lang['AIM'],
	'L_YIM' => $lang['YIM'],
	'L_MSNM' => $lang['MSNM'],
	'L_ICQ' => $lang['ICQ'],
	'L_JOINED' => $lang['Joined'],
	'L_POSTS' => $lang['Posts'],
	'L_PM' => $lang['Private_Message'],

	'S_MODE_SELECT' => select_box('mode', $mode, array('joindate'=>$lang['Sort_Joined'], 'username'=>$lang['Sort_Username'], 'location'=>$lang['Sort_Location'], 'posts'=>$lang['Sort_Posts'], 'website'=>$lang['Sort_Website'])),
	'S_ORDER_SELECT' => select_box('order', $sort_order, array('ASC'=>$lang['Sort_Ascending'], 'DESC'=>$lang['Sort_Descending'])),
	'S_MODE_ACTION' => $home ? '' : getlink('Members_List')
));

switch ($mode) {
	case 'joindate':
		$order_by = "user_id $sort_order LIMIT $start, ".$members_per_page;
		break;
	case 'username':
		$order_by = "username $sort_order LIMIT $start, ".$members_per_page;
		break;
	case 'location':
		$order_by = "user_from $sort_order LIMIT $start, ".$members_per_page;
		break;
	case 'posts':
		$order_by = "user_posts $sort_order LIMIT $start, ".$members_per_page;
		break;
	case 'website':
		$order_by = "user_website $sort_order LIMIT $start, ".$members_per_page;
		break;
	default:
		$order_by = "user_id $sort_order LIMIT $start, ".$members_per_page;
		break;
}

$template_name = file_exists("themes/$CPG_SESS[theme]/template/forums/images.cfg") ? $CPG_SESS['theme'] : 'default';
$current_template_path = 'themes/'.$template_name.'/images/forums';
include('themes/'.$template_name.'/template/forums/images.cfg');
if (!defined('TEMPLATE_CONFIG')) {
	cpg_error("Could not open $template_name template config file");
}
$img_lang = ( file_exists(realpath($current_template_path.'/lang_'.$MAIN_CFG['global']['language'])) ) ? $MAIN_CFG['global']['language'] : 'english';
while (list($key, $value) = each($images)) {
	if (!is_array($value)) { $images[$key] = str_replace('{LANG}', 'lang_'.$img_lang, $value); }
}

$ranksrow = $db->sql_ufetchrowset("SELECT * FROM ".$prefix."_bbranks 
	ORDER BY rank_special, rank_min",SQL_ASSOC);

$sql = "SELECT username, user_id, user_posts, user_rank, user_regdate, user_from, user_website, user_icq, user_aim, user_yim, user_msnm, user_avatar, user_avatar_type, user_allowavatar
	FROM ".$user_prefix."_users
	WHERE user_id <> (SELECT user_id FROM ".$user_prefix."_users WHERE username='Anonymous') 
	AND user_level > '0'
	ORDER BY $order_by";
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result)) {
	$i = 0;
	do {
		$username = $row['username'];
		$user_id = $row['user_id'];
		if ($row['user_website'] == "http:///" || $row['user_website'] == "http://"){
			$row['user_website'] =	'';
		}
		if (($row['user_website'] != '' ) && (substr($row['user_website'],0, 7) != "http://")) {
			$row['user_website'] = "http://".$row['user_website'];
		}
		$row['user_from'] = str_replace('.gif', '', $row['user_from']);
		$from = (!empty($row['user_from'])) ? $row['user_from'] : '&nbsp;';

		$joined = date('M d, Y' , $row['user_regdate']);
		$posts = ($row['user_posts']) ? $row['user_posts'] : 0;

		$poster_avatar = '';
		if ($row['user_avatar_type'] && $username != 'Anonymous' && $row['user_allowavatar']) {
			switch($row['user_avatar_type']) {
				case '1':
					$poster_avatar = ( $MAIN_CFG['avatar']['allow_upload'] ) ? '<img src="'.$MAIN_CFG['avatar']['path'].'/'.$row['user_avatar'].'" alt="" />' : '';
					break;
				case '2':
					$poster_avatar = ( $MAIN_CFG['avatar']['allow_remote'] ) ? '<img src="'.$row['user_avatar'].'" alt="" />' : '';
					break;
				case '3':
					$poster_avatar = ( $MAIN_CFG['avatar']['allow_local'] ) ? '<img src="'.$MAIN_CFG['avatar']['gallery_path'].'/'.$row['user_avatar'].'" alt="" />' : '';
					break;
			}
		}
		$rank_image = $poster_rank = '';
		for ($j = 0; $j < count($ranksrow); $j++) {
			if (($row['user_rank'] && $row['user_rank'] == $ranksrow[$j]['rank_id'] && $ranksrow[$j]['rank_special']) ||
			    (!$row['user_rank'] && $row['user_posts'] >= $ranksrow[$j]['rank_min'] && !$ranksrow[$j]['rank_special'])) {
				$poster_rank = $ranksrow[$j]['rank_title'];
				$rank_image = ($ranksrow[$j]['rank_image']) ? '<img src="'.$ranksrow[$j]['rank_image'].'" alt="'.$poster_rank.'" title="'.$poster_rank.'" />' : '';
			}
		}

		$temp_url = getlink('Your_Account&amp;profile='.$user_id);
		$profile_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_profile'].'" alt="'.$lang['Read_profile'].'" title="'.$lang['Read_profile'].'" /></a>';
		$profile = '<a href="'.$temp_url.'">'.$lang['Read_profile'].'</a>';

		if (is_user() && is_active('Private_Messages')) {
			$temp_url = getlink('Private_Messages&amp;mode=post&amp;u='.$user_id);
			$pm_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_pm'].'" alt="'.$lang['Send_private_message'].'" title="'.$lang['Send_private_message'].'" /></a>';
			$pm = '<a href="'.$temp_url.'">'.$lang['Send_private_message'].'</a>';
		} else {
			$pm = $pm_img = '';
		}

		$www_img = ( $row['user_website'] ) ? '<a href="'.$row['user_website'].'" target="_blank"><img src="'.$images['icon_www'].'" alt="'.$lang['Visit_website'].'" title="'.$lang['Visit_website'].'" /></a>' : '';
		$www = ( $row['user_website'] ) ? '<a href="'.$row['user_website'].'" target="_blank">'.$lang['Visit_website'].'</a>' : '';

		if (!empty($row['user_icq'])) {
			$icq_status_img = '<a href="http://wwp.icq.com/'.$row['user_icq'].'#pager"><img src="http://web.icq.com/whitepages/online?icq='.$row['user_icq'].'&amp;img=5" style="width:18px; height:18px;" /></a>';
			$icq_img = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$row['user_icq'].'"><img src="'.$images['icon_icq'].'" alt="'.$lang['ICQ'].'" title="'.$lang['ICQ'].'" /></a>';
			$icq =	'<a href="http://wwp.icq.com/scripts/search.dll?to='.$row['user_icq'].'">'.$lang['ICQ'].'</a>';
		} else {
			$icq_status_img = '';
			$icq_img = '';
			$icq = '';
		}

		$aim_img = ( $row['user_aim'] ) ? '<a href="aim:goim?screenname='.$row['user_aim'].'&amp;message=Hello+Are+you+there?"><img src="'.$images['icon_aim'].'" alt="'.$lang['AIM'].'" title="'.$lang['AIM'].'" /></a>' : '';
		$aim = ( $row['user_aim'] ) ? '<a href="aim:goim?screenname='.$row['user_aim'].'&amp;message=Hello+Are+you+there?">'.$lang['AIM'].'</a>' : '';

		$temp_url = getlink('Your_Account&amp;profile='.$user_id);
		$msn_img = ( $row['user_msnm'] ) ? '<a href="'.$temp_url.'"><img src="'.$images['icon_msnm'].'" alt="'.$lang['MSNM'].'" title="'.$lang['MSNM'].'" /></a>' : '';
		$msn = ( $row['user_msnm'] ) ? '<a href="'.$temp_url.'">'.$lang['MSNM'].'</a>' : '';

		$yim_img = ( $row['user_yim'] ) ? '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$row['user_yim'].'&amp;.src=pg"><img src="'.$images['icon_yim'].'" alt="'.$lang['YIM'].'" title="'.$lang['YIM'].'" /></a>' : '';
		$yim = ( $row['user_yim'] ) ? '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$row['user_yim'].'&amp;.src=pg">'.$lang['YIM'].'</a>' : '';

		$temp_url = getlink('Forums&amp;file=search&amp;search_author='.urlencode($username).'&amp;showresults=posts');
		$search_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_search'].'" alt="'.$lang['Search_user_posts'].'" title="'.$lang['Search_user_posts'].'" /></a>';
		$search = '<a href="'.$temp_url.'">'.$lang['Search_user_posts'].'</a>';

		$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

		$template->assign_block_vars('memberrow', array(
			'ROW_NUMBER' => $i + 1 + $start,
			'ROW_COLOR' => $row_color,
			'ROW_CLASS' => $row_class,
			'USERNAME' => $username,
			'FROM' => $from,
			'JOINED' => $joined,
			'POSTS' => $posts,
			'AVATAR_IMG' => $poster_avatar,
			'PROFILE_IMG' => $profile_img,
			'PROFILE' => $profile,
			'SEARCH_IMG' => $search_img,
			'SEARCH' => $search,
			'PM_IMG' => $pm_img,
			'PM' => $pm,
			'EMAIL_IMG' => $rank_image,
			'EMAIL' => $poster_rank,
			'WWW_IMG' => $www_img,
			'WWW' => $www,
			'ICQ_STATUS_IMG' => $icq_status_img,
			'ICQ_IMG' => $icq_img,
			'ICQ' => $icq,
			'AIM_IMG' => $aim_img,
			'AIM' => $aim,
			'MSN_IMG' => $msn_img,
			'MSN' => $msn,
			'YIM_IMG' => $yim_img,
			'YIM' => $yim,
			'U_VIEWPROFILE' => getlink('Your_Account&amp;profile='.$user_id)
		));

		$i++;
	}
	while ( $row = $db->sql_fetchrow($result) );
	$db->sql_freeresult($result);
}

$sql = "SELECT count(*) AS total FROM ".$user_prefix."_users 
WHERE user_id <> (SELECT user_id FROM ".$user_prefix."_users WHERE username='Anonymous') 
AND user_level > '0'";
list($total_members) = $db->sql_ufetchrow($sql,SQL_NUM);

$pagination = members_pagination('Members_List&amp;mode='.$mode.'&amp;order='.$sort_order, $total_members, $members_per_page, $start). '&nbsp;';

$template->assign_vars(array(
	'PAGINATION' => $pagination,
	'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $members_per_page ) + 1 ), ceil( $total_members / $members_per_page )),
	'L_GOTO_PAGE' => $lang['Goto_page']
));

$template->set_filenames(array('body' => 'memberslist.html'));
$template->display('body');

CloseTable();

function members_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = TRUE)
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
			$page_string .= ( $i == $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.getlink($base_url.'&amp;start='.( ( $i - 1 ) * $per_page ) ).'">'.$i.'</a>';
			if ( $i <  $total_pages ) { $page_string .= ', '; }
		}
	}
	if ( $add_prevnext_text ) {
		if ( $on_page > 1 ) {
			$page_string = ' <a href="'.getlink($base_url.'&amp;start='.( ( $on_page - 2 ) * $per_page ) ).'">'.$lang['Previous'].'</a>&nbsp;&nbsp;'.$page_string;
		}
		if ( $on_page < $total_pages ) {
			$page_string .= '&nbsp;&nbsp;<a href="'.getlink($base_url.'&amp;start='.( $on_page * $per_page ) ).'">'.$lang['Next'].'</a>';
		}
	}
	$page_string = $lang['Goto_page'].' '.$page_string;
	return $page_string;
}
