<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Surveys/admin/index.inc,v $
  $Revision: 9.12 $
  $Author: nanocaiordo $
  $Date: 2007/08/27 02:46:11 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('surveys')) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '._POLLSADMIN;
global $language;
$mode = (isset($_GET['mode'])) ? $_GET['mode'] : '';
if ($mode == 'save') {
	if (isset($_POST['id'])) {
		$id = intval($_POST['id']);
		$db->sql_query('UPDATE '.$prefix."_poll_desc SET poll_title='".Fix_Quotes($_POST['title'], true)."' WHERE poll_id=$id");
		for($i = 1; $i <= 12; $i++) {
			$db->sql_query('UPDATE '.$prefix."_poll_data set option_text='".Fix_Quotes($_POST['opt'][$i])."' WHERE vote_id=$i AND poll_id=$id");
		}
	} else if (isset($_POST['opt'])) {
		$db->sql_query("INSERT INTO ".$prefix."_poll_desc (poll_id, poll_title, time_stamp, voters, planguage, artid, comments, anonymous) VALUES (DEFAULT, '".Fix_Quotes($_POST['title'])."', ".gmtime().", 0, '".Fix_Quotes($_POST['language'])."', 0, 1, '".Fix_Quotes($_POST['anonymous'])."')");
		$id = $db->sql_nextid('poll_id');
		for ($i = 1; $i <= sizeof($_POST['opt']); $i++) {
			if (!empty($_POST['opt'][$i])) {
				$db->sql_query("INSERT INTO ".$prefix."_poll_data (poll_id, option_text, option_count, vote_id) VALUES ($id, '".Fix_Quotes($_POST['opt'][$i])."', 0, $i)");
			}
		}
	}
	url_redirect(adminlink('&mode=edit'));
} else if ($_GET['mode'] == 'del' && isset($_POST['id'])) {
	$id = intval($_POST['id']);
	if(isset($_POST['cancel'])) {
		url_redirect(adminlink('&mode=edit'));
	}
	if (!isset($_POST['confirm'])) {
		$msg = sprintf(_ERROR_DELETE_CONF,_POLLS);
		cpg_delete_msg(adminlink("&amp;mode=del"),$msg,'<input type="hidden" name="id" value="'.$id.'" />');				
	} else {
		$db->sql_query("DELETE FROM ".$prefix."_poll_desc WHERE poll_id=$id");
		$db->sql_query("DELETE FROM ".$prefix."_poll_data WHERE poll_id=$id");
		$db->sql_query("DELETE FROM ".$prefix."_pollcomments WHERE poll_id=$id");
		$db->sql_query("DELETE FROM ".$prefix."_poll_check WHERE poll_id=$id");
		url_redirect(adminlink('&mode=edit'));
	}
}

require_once('header.php');
GraphicAdmin('_AMENU3');
OpenTable();
echo '<div align="center" class="title">'._POLLSADMIN.'</div>';
CloseTable();
OpenTable();
echo '<center><font class="option">'
	.(($_GET['mode'] == 'add') ? '<b>'._CREATEPOLL.'</b>' : '<a href="'.adminlink('&amp;mode=add').'">'._CREATEPOLL.'</a>').' | '
	.(($_GET['mode'] == 'del') ? '<b>'._REMOVEEXISTING.'</b>' : '<a href="'.adminlink('&amp;mode=del').'">'._REMOVEEXISTING.'</a>').' | '
	.(($_GET['mode'] == 'edit') ? '<b>'._EDITPOLL.'</b>' : '<a href="'.adminlink('&amp;mode=edit').'">'._EDITPOLL.'</a>')
	.'</font></center><br /><br />';

if ($_GET['mode'] == 'del') {
	//echo _POLLDELWARNING."</center><br /><br />"
	echo open_form(adminlink('&amp;mode=del'), 'deletepoll', _CHOOSEPOLL);
	$result = $db->sql_uquery("SELECT poll_id, poll_title, planguage FROM ".$prefix."_poll_desc ORDER BY time_stamp");
	/* cycle through the descriptions until everyone has been fetched */
	while ($row = $db->sql_fetchrow($result)) {
		echo '<input type="radio" name="id" id="'.$row['poll_id'].'" title="'.$row['poll_title'].'" value="'.$row['poll_id'].'" />
		<label for="'.$row['poll_id'].'">'.$row['poll_title'].' '.((!empty($row['planguage'])&& $multilingual) ?	' ('._LANGUAGE .' - '. $row['planguage'].')' : ' ('._LANGUAGE.' - '. _ALL).')</label><br/>';
	}
	echo '<input type="submit" value="'._DELETE.'" />';
	echo close_form();
}
else if ($_GET['mode'] == 'edit') {
	if (!isset($_POST['id'])) {
		echo open_form(adminlink('&amp;mode=edit'), 'editpoll', _CHOOSEPOLLEDIT);
		$result = $db->sql_query("SELECT poll_id, poll_title, time_stamp, planguage FROM ".$prefix."_poll_desc ORDER BY time_stamp");
		/* cycle through the descriptions until everyone has been fetched */
		while ($row = $db->sql_fetchrow($result)) {
			echo '<input type="radio" name="id" value="'.$row['poll_id'].'" />
			<label for="'.$row['poll_id'].'">'.$row['poll_title'].' '.((!empty($row['planguage'])&& $multilingual) ?	' ('._LANGUAGE .' - '. $row['planguage'].')' : ' ('._LANGUAGE.' - '. _ALL.')').'</label><br/>';
		}
		echo '<input type="submit" value="'._EDIT.'" />';
		echo close_form();
	} else {
		$id = intval($_POST['id']);
		list($poll_title) = $db->sql_ufetchrow("select poll_title from ".$prefix."_poll_desc where poll_id=$id", SQL_NUM);
		echo open_form(adminlink('&amp;mode=save'), 'newpoll', _EDIT.' '._POLLS);
		echo '<label for="title"><b>'._TITLE.':</b></label><input type="text" name="title" value="'.$poll_title.'" size="40" maxlength="100"><br /><br />';
		$result = $db->sql_uquery("select option_text, option_count, vote_id from ".$prefix."_poll_data where poll_id=$id order by vote_id");
		while (list($option_text, $option_count, $vote_id) = $db->sql_fetchrow($result)) {
			echo '<label for="opt['.$vote_id.']"><b>'.$vote_id.'</b> <input type="text" name="opt['.$vote_id.']" value="'.htmlprepare($option_text).'" size="40" maxlength="50" />'.$option_count.' '._VOTES.'<br />';
		 $nextvote = $vote_id+1;
		 }		 
		echo '<input type="hidden" name="id" value="'.$id.'" /><br /><br /><center><input type="submit" value="'._SAVECHANGES.'" /><br /><br />'._GOBACK.'</center>';
		echo close_form();
	}
}
else {
	echo open_form(adminlink('&amp;mode=save'), 'newpoll', _CREATEPOLL);
	echo '<label for="title">'._POLLTITLE.': </label><input type="text" name="title" size="50" maxlength="100" /><br /><br />';
	if ($multilingual) {
		echo '<br /><label for="language">'._LANGUAGE.': </label>'.lang_selectbox($language, 'language').'<br /><br />';
	} else {
		echo '<input type="hidden" name="language" value="'.$language.'" />';
	}
	echo '<font class="content">'._POLLEACHFIELD.'</font><br /><table border="0">';
	for($i = 1; $i <= 12; $i++)	   {
		echo '<tr><td><label for="opt[$i]">'._OPTION." $i:</label></td><td><input type=\"text\" name=\"opt[$i]\" size=\"50\" maxlength=\"50\" /></td></tr>";
	}
	echo '</table><br />'
    .'<label for="anonymous">Allow Anonymous Voting:</label>'
    .'<select name="anonymous">'
    .'<option value="0">No</option>'
    .'<option value="1">Yes</option>'
    .'</select><br />'
	.'<input type="submit" value="'._CREATEPOLLBUT.'" />';
	echo close_form();
}
CloseTable();
