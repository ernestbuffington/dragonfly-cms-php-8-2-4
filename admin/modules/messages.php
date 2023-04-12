<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/messages.php,v $
  $Revision: 9.19 $
  $Author: nanocaiordo $
  $Date: 2007/08/27 02:58:24 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '._MESSAGESADMIN;

require_once(CORE_PATH.'nbbcode.php');

if (isset($_GET['status'])) {
	$statusMsg = intval($_GET['status']);
	$result = $db->sql_query("SELECT active FROM ".$prefix."_message WHERE mid='$statusMsg'");
	if ($db->sql_numrows($result) > 0) {
		list($status) = $db->sql_fetchrow($result);
		if (is_numeric($status)) {
			$status = intval(!$status);
			$db->sql_query("UPDATE ".$prefix."_message SET active='$status' WHERE mid='$statusMsg'");
		}
	}
	url_redirect(adminlink('messages'));
}
elseif (isset($_GET['save']) && isset($_POST['content'])) {
	$id = intval($_GET['save']);
	$title = Fix_Quotes($_POST['title']);
	$content = Fix_Quotes(encode_bbcode($_POST['content']));
	$language = Fix_Quotes($_POST['language']);
	$expire = intval($_POST['expire']);
	$active = intval($_POST['active']);
	$view = intval($_POST['view']);
	if ($id > 0) {
		$newdate = ($_POST['chng_date']) ? ', date='.gmtime() : '';
		$result = $db->sql_query("UPDATE ".$prefix."_message SET title='$title', content='$content' $newdate, expire=$expire, active=$active, view=$view, mlanguage='$language' WHERE mid='$id'");
	} else {
		$db->sql_query("INSERT INTO ".$prefix."_message (mid, title, content, date, expire, active, view, mlanguage) VALUES (DEFAULT, '$title', '$content', ".gmtime().", $expire, $active, $view, '$language')");
	}
	url_redirect(adminlink('messages'));
}
else if (isset($_GET['del']) && isset($_POST['confirm'])) {
	$db->sql_query('DELETE FROM '.$prefix.'_message WHERE mid='.intval($_GET['del']));
	$db->optimize_table($prefix.'_message');
	url_redirect(adminlink('messages'));
}

require_once('header.php');
GraphicAdmin('_AMENU3');
if (isset($_GET['del'])) {
	if (isset($_POST['cancel'])) { url_redirect(adminlink('messages')); }
	cpg_delete_msg(adminlink('&amp;del='.intval($_GET['del'])), _REMOVEMSG);
}
else if (isset($_GET['edit'])) {
	OpenTable();
	$id = intval($_GET['edit']);
	$result = $db->sql_query('SELECT title, content, date, expire, active, view, mlanguage FROM '.$prefix.'_message WHERE mid='.$id);
	$row = $db->sql_fetchrow($result);
	echo '<div style="text-align:center;" class="option">'._EDITMSG.'</div>'
	.'<form name="edit_message" action="'.adminlink('messages&amp;save='.$id).'" method="post" enctype="multipart/form-data" accept-charset="utf-8">'
	.'<br /><strong>'._MESSAGETITLE.'</strong><br />'
	.'<input type="text" name="title" value="'.htmlprepare($row['title']).'" size="50" maxlength="100" /><br /><br />'
	.'<strong>'._MESSAGECONTENT.'</strong><br />'
	.bbcode_table('content', 'edit_message', 1)
	.'<div style="float:left;"><textarea name="content" rows="15" wrap="virtual" cols="63" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.htmlprepare($row['content']).'</textarea></div>
	<div style="float:left; margin-left:5px;">'.smilies_table('inline', 'content', 'edit_message').'</div><br /><br />';
	if ($multilingual) {
		echo '<strong>'._LANGUAGE.'</strong> '.lang_selectbox($row['mlanguage'], 'language').'<br /><br />';
	} else {
		echo '<input type="hidden" name="language" value="" />';
	}
	echo "<strong>"._EXPIRATION.'</strong> '
	.select_box('expire', $row['expire'], array(86400 => '1 '._DAY, 172800 => '2 '._DAYS, 432000 => '5 '._DAYS, 1296000 => '15 '._DAYS, 2592000 => '30 '._DAYS, 0 => _UNLIMITED))
	.'<br /><br />'
	.'<strong>'._ACTIVATE2.'</strong> '.yesno_option('active', $row['active']);
	if ($row['active']) {
		echo '<br /><br /><strong>'._CHANGEDATE.'</strong> '.yesno_option('chng_date', 0).'<br /><br />';
	} else {
		echo '<br /><div class="tiny">'._IFYOUACTIVE.'</div><input type="hidden" name="chng_date" value="1" /><br />';
	}
	echo '<strong>'._VIEWPRIV.'</strong> '.group_selectbox('view', $row['view'], true)
	.'<br /><br /><input type="submit" value="'._SAVECHANGES.'" /></form>';
}
else {
	OpenTable();
	echo '<table border="0" cellspacing="0" width="100%" bgcolor="'.$bgcolor1.'">
	<tr bgcolor="'.$bgcolor2.'">
	<td align="center"><strong>'._ID.'</strong></td>
	<td align="center"><strong>'._TITLE.'</strong></td>
	<td align="center"><strong>'._LANGUAGE.'</strong></td>
	<td align="center" nowrap="nowrap"><strong>'._VIEW.'</strong></td>
	<td align="center"><strong>'._ACTIVE.'</strong></td>
	<td align="center"><strong>'._FUNCTIONS.'</strong></td>
	</tr>';
	$result = $db->sql_query('SELECT mid, title, content, date, expire, active, view, mlanguage FROM '.$prefix.'_message');
	$bgcolor = $bgcolor3;
	while (list($mid, $title, $content, $mdate, $expire, $active, $view, $mlanguage) = $db->sql_fetchrow($result)) {
		$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
		if ($view == 0) {
			$mview = _MVALL;
		} elseif ($view == 1) {
			$mview = _MVUSERS;
		} elseif ($view == 2) {
			$mview = _MVADMIN;
		} elseif ($view == 3) {
			$mview = _MVANON;
		} elseif ($view > 3) {	// <= phpBB User Groups Integration
			$newView = $view - 3;
			list($groupName) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix."_bbgroups WHERE group_id='$newView'", SQL_NUM);
			$mview = $groupName;
		}
		if ($mlanguage == '') {
			$mlanguage = _ALL;
		}
		if ($active) {
			$act_img = 'checked.gif';
			$act_alt = _ACTIVE;
		} else {
			$act_img = 'unchecked.gif';
			$act_alt = _INACTIVE;
		}
		echo '<tr'.$bgcolor.'>
		<td align="center"><strong>'.$mid.'</strong></td>
		<td align="left" width="100%">'.$title.'</td>
		<td align="center">'.$mlanguage.'</td>
		<td align="center" nowrap="nowrap">'.$mview.'</td>
		<td align="center"><a href="'.adminlink('&amp;status='.$mid).'"><img src="images/'.$act_img.'" border="0" alt="'.$act_alt.'" title="'.$act_alt.'" /></a></td>
		<td align="right" nowrap="nowrap"><a href="'.adminlink('&amp;edit='.$mid).'">'._EDIT.'</a> / <a href="'.adminlink('&amp;del='.$mid).'">'._DELETE.'</a>
		</td></tr>';
	}
	echo '</table><br />';
	CloseTable();
	echo '<br />';
	OpenTable();
	echo '<div style="text-align:center;" class="option">'._ADDMSG.'</div><br />
	<form name="message" action="'.adminlink('messages&amp;save=0').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">'
	.'<strong>'._MESSAGETITLE.'</strong><br />'
	.'<input type="text" name="title" value="" size="50" maxlength="100" /><br /><br />'
	.'<strong>'._MESSAGECONTENT.'</strong><br />'
	.bbcode_table('content', 'message', 1)
	.'<div style="float:left;">
	<textarea name="content" rows="15" wrap="virtual" cols="63" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);"></textarea></div>
	<div style="float: left; margin-left: 5px">'.smilies_table('inline', 'content', 'message').'</div><br /><br />';
	if ($multilingual) {
		echo '<strong>'._LANGUAGE.'</strong> '.lang_selectbox($language, 'language').'<br /><br />';
	} else {
		echo '<input type="hidden" name="language" value="" />';
	}
	echo '<strong>'._EXPIRATION.'</strong> '
	.select_box('expire', 0, array(86400 => '1 '._DAY, 172800 => '2 '._DAYS, 432000 => '5 '._DAYS, 1296000 => '15 '._DAYS, 2592000 => '30 '._DAYS, 0 => _UNLIMITED))
	.'<br /><br />'
	.'<strong>'._ACTIVATE2.'</strong> '.yesno_option('active', 1)
	.'<br /><br /><strong>'._VIEWPRIV.'</strong> '.group_selectbox('view', 0, true)
	.'<br /><br /><input type="submit" value="'._ADDMSG.'" /></form>';
}
CloseTable();
