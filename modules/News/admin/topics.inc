<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/admin/topics.inc,v $
  $Revision: 9.14 $
  $Author: nanocaiordo $
  $Date: 2007/08/27 02:10:48 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('topics')) { die('Access Denied'); }

get_lang('Topics', __FILE__, __LINE__);
$pagetitle .= ' '._BC_DELIM.' '._TOPICSMANAGER;

if (isset($_POST['createTopic'])) {
	$topic_image = Fix_Quotes($_POST['topicimage'], 1);
	$topic_text = Fix_Quotes($_POST['topictext'], 1);
	$db->sql_query("INSERT INTO ".$prefix."_topics (topicid, topicimage, topictext, counter) VALUES (DEFAULT, '$topic_image', '$topic_text', '0')");
	url_redirect(adminlink('&file=topics'));
} elseif (isset($_POST['saveTopic'])) {
	$topic_id = intval($_POST['topic_id']);
	$topic_image = Fix_Quotes($_POST['topicimage'], 1);
	$topic_text = Fix_Quotes($_POST['topictext'], 1);
	$db->sql_query("UPDATE ".$prefix."_topics SET topicimage='$topic_image', topictext='$topic_text' WHERE topicid='$topic_id'");
	url_redirect(adminlink('&file=topics&edit='.$topic_id));
} elseif (isset($_POST['addRelated'])) {
	$topic_id = intval($_POST['topic_id']);
	if (isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['url']) && !empty($_POST['url']) && $_POST['url'] != 'http://') {
		$db->sql_query("INSERT INTO ".$prefix."_related (rid, tid, name, url) VALUES (DEFAULT, '$topic_id', '".Fix_Quotes($_POST['name'], 1)."', '".Fix_Quotes($_POST['url'], 1)."')");
	}
	url_redirect(adminlink('&file=topics&edit='.$topic_id));
} elseif (isset($_POST['saveRelated'])) {
	$topic_id = intval($_POST['topic_id']);
	$db->sql_query("UPDATE ".$prefix."_related SET name='".Fix_Quotes($_POST['name'], 1)."', url='".Fix_Quotes($_POST['url'], 1)."' WHERE rid='".intval($_POST['related_id'])."'");
	url_redirect(adminlink('&file=topics&edit='.$topic_id));
} elseif (isset($_GET['rdelete'])) {
	$related_id = intval($_GET['rdelete']);
	list($topic_id) = $db->sql_ufetchrow("SELECT tid FROM ".$prefix."_related WHERE rid='$related_id'", SQL_NUM);
	if (!isset($topic_id)) { url_redirect(adminlink('&file=topics')); }
	if (isset($_POST['cancel'])) { url_redirect(adminlink('&file=topics&edit='.$topic_id)); }
	if (isset($_POST['confirm'])) {
		$db->sql_query("DELETE FROM ".$prefix."_related WHERE rid='$related_id'");
		url_redirect(adminlink('&file=topics&edit='.$topic_id));
	}
	cpg_delete_msg(adminlink('&amp;file=topics&amp;rdelete='.$related_id), sprintf(_ERROR_DELETE_CONF, 'this related link'));
} elseif (isset($_GET['delete'])) {
	$topic_id = intval($_GET['delete']);
	if (isset($_POST['cancel'])) { url_redirect(adminlink('&file=topics')); }
	if (isset($_POST['confirm'])) {
		list($sid) = $db->sql_ufetchrow("SELECT sid FROM ".$prefix."_stories WHERE topic='$topic_id'", SQL_NUM);
		$db->sql_query("DELETE FROM ".$prefix."_stories WHERE topic='$topic_id'");
		$db->sql_query("DELETE FROM ".$prefix."_topics WHERE topicid='$topic_id'");
		$db->sql_query("DELETE FROM ".$prefix."_related WHERE tid='$topic_id'");
		list($sid) = $db->sql_ufetchrow("SELECT sid FROM ".$prefix."_comments WHERE sid='$sid'", SQL_NUM);
		$db->sql_query("DELETE FROM ".$prefix."_comments WHERE sid='$sid'");
		url_redirect(adminlink('&file=topics'));
	}
	cpg_delete_msg(adminlink('&amp;file=topics&amp;delete='.$topic_id), sprintf(_ERROR_DELETE_CONF, 'this topic'));
} elseif (isset($_GET['edit'])) {
	if (empty($_GET['edit'])) { cpg_error(sprintf(_ERROR_NOT_SET, 'ID'), _SEC_ERROR); }
	$topic_id = intval($_GET['edit']);
	if (isset($_GET['related'])) {
		$related_id = isset($_GET['related']) ? intval($_GET['related']) : '';
		if (empty($_GET['related'])) { cpg_error(sprintf(_ERROR_NOT_SET, 'ID'), _SEC_ERROR); }
		$pagetitle .= ' '._BC_DELIM.' '._EDITRELATED;
		require('header.php');
		GraphicAdmin('_AMENU3');
		OpenTable();
		echo '<span class="genmed"><strong>'._TOPICSMANAGER.' '._BC_DELIM.' <a href="'.adminlink('&amp;file=topics&amp;edit='.$topic_id).'">'._EDITTOPIC.'</a> '._BC_DELIM.' '._EDITRELATED.'</strong></span><br /><br />';
		list($name, $url) = $db->sql_ufetchrow("SELECT name, url FROM ".$prefix."_related WHERE rid='$related_id'", SQL_NUM);
		list($topictext, $topicimage) = $db->sql_ufetchrow("SELECT topictext, topicimage FROM ".$prefix."_topics WHERE topicid='$topic_id'", SQL_NUM);
		echo open_form(adminlink('&amp;file=topics'), false, _EDITRELATED).'
		<img src="images/topics/'.$topicimage.'" border="0" alt="'.$topictext.'" title="'.$topictext.'" align="right" />
		<label class="ulog" for="name">'._SITENAME.'</label>
		 <input type="text" name="name" id="name" size="50" maxlength="30" value="'.htmlprepare($name).'" /><br />
		<label class="ulog" for="url">'._URL.'</label>
		 <input type="text" name="url" id="url" size="50" maxlength="200" value="'.$url.'" /><br /><br />
		<input type="hidden" name="related_id" value="'.$related_id.'" /><input type="hidden" name="topic_id" value="'.$topic_id.'" />
		<input type="submit" name="saveRelated" value="'._SAVECHANGES.'" />'.
		close_form();
	} else {
		$pagetitle .= ' '._BC_DELIM.' '._EDITTOPIC;
		require('header.php');
		GraphicAdmin('_AMENU3');
		OpenTable();
		list($topicimage, $topictext) = $db->sql_ufetchrow("SELECT topicimage, topictext FROM ".$prefix."_topics WHERE topicid='$topic_id'", SQL_NUM);
		$handle = opendir('images/topics');
		$tlist = array();
		while ($file = readdir($handle)) {
			if (ereg('^([a-zA-Z0-9_\-]+)([.]{1})([a-zA-Z0-9_\-]{3})$',$file) && $file != 'AllTopics.gif') {
				$tlist[] = $file;
			}
		}
		closedir($handle);
		sort($tlist);
		echo '<span class="genmed"><strong>'._TOPICSMANAGER.' '._BC_DELIM.' '._EDITTOPIC.'</strong></span><br /><br />'.
		open_form(adminlink('&amp;file=topics'), false, _EDITTOPIC).'
		<img src="images/topics/'.$topicimage.'" border="0" align="right" alt="'.$topictext.'" title="'.$topictext.'" />
		<label class="ulog" for="topictext">'._TOPICNAME.'</label>
		 <input type="text" name="topictext" id="topictext" size="41" maxlength="40" value="'.htmlprepare($topictext).'" /><br />
		<label class="ulog" for="topicimage">'._TOPICIMAGE.'</label>
		 '.select_option('topicimage', $topicimage, $tlist).'<br /><br />
		<input type="hidden" name="topic_id" value="'.$topic_id.'" />
		<input type="submit" name="saveTopic" value="'._SAVECHANGES.'" /> <a href="'.adminlink('&amp;file=topics&amp;delete='.$topic_id).'"><input type="button" value="'._DELETE.'" onclick="parent.location=\''.adminlink('&amp;file=topics&amp;delete='.$topic_id).'\'" /></a>'.
		close_form();
		CloseTable();
		echo '<br />';
		OpenTable();
		$result = $db->sql_query("SELECT rid, name, url FROM ".$prefix."_related WHERE tid='$topic_id'");
		if ($db->sql_numrows($result)) {
			echo '<span class="genmed"><strong>'._ACTIVERELATEDLINKS.'</strong></span><br /><br />
			<table border="0" width="100%">
			 <tr bgcolor="'.$bgcolor2.'">
			   <td><strong>'._SITENAME.'</strong></td>
			   <td><strong>'._URL.'</strong></td>
			   <td><strong>'._FUNCTIONS.'</strong></td>
			 </tr>';
			$bgcolor = $bgcolor3;
			while (list($rid, $name, $url) = $db->sql_fetchrow($result)) {
				$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
				echo '
			 <tr'.$bgcolor.'>
			   <td>'.$name.'</td>
			   <td><a href="'.$url.'">'.$url.'</a></td>
			   <td><a href="'.adminlink('&amp;file=topics&amp;edit='.$topic_id.'&amp;related='.$rid).'">'._EDIT.'</a> / <a href="'.adminlink('&amp;file=topics&amp;rdelete='.$rid).'">'._DELETE.'</a></td>
			 </tr>';
			}
			echo '</table><br /><br />';
		}
		$db->sql_freeresult($result);
		echo open_form(adminlink('&amp;file=topics'), false, _ADDRELATED).'
		<label class="ulog" for="name">'._SITENAME.'</label>
		 <input type="text" name="name" id="name" size="50" maxlength="30" /><br />
		<label class="ulog" for="url">'._URL.'</label>
		 <input type="text" name="url" id="url" size="50" maxlength="200" value="http://" /><br /><br />
		<input type="hidden" name="topic_id" value="'.$topic_id.'" />
		<input type="submit" name="addRelated" value="'._ADD.'" />'.
		close_form();
	}
	CloseTable();
} else {
	require('header.php');
	GraphicAdmin('_AMENU3');
	OpenTable();
	echo '<span class="genmed"><strong>'._TOPICSMANAGER.'</strong></span><br /><br />
	<table border="0" width="100%" align="center" cellpadding="1"><tr>';
	$count = 0;
	$result = $db->sql_query("SELECT topicid, topicimage, topictext FROM ".$prefix."_topics ORDER BY topictext");
	while (list($topicid, $topicimage, $topictext) = $db->sql_fetchrow($result)) {
		echo '<td>
		<a href="'.adminlink("&amp;file=topics&amp;edit=$topicid").'"><img src="images/topics/'.$topicimage.'" border="0" alt="'.$topictext.'" title="'.$topictext.'" /></a><br />
		<strong>'.$topictext.'</strong></td>';
		$count++;
		if ($count == 5) {
			echo '</tr><tr>';
			$count = 0;
		}
	}
	$db->sql_freeresult($result);
	echo '</tr></table><br /><br />';
	$handle = opendir('images/topics');
	$tlist = array();
	while ($file = readdir($handle)) {
		if (preg_match('#^([a-zA-Z0-9_\\\\\-]+)([\.]{1})([a-zA-Z0-9_\\\\\-]{3})$#m',$file) && $file != 'AllTopics.gif') {
			$tlist[] = $file;
		}
	}
	closedir($handle);
	sort($tlist);
	echo open_form(adminlink('&amp;file=topics'), false, _ADDATOPIC).'
	<label class="ulog" for="topictext">'._TOPICNAME.'</label>
	 <input type="text" name="topictext" id="topictext" size="41" maxlength="40" value="'.$topictext.'" /><br />
	<label class="ulog" for="topicimage">'._TOPICIMAGE.'</label>
	 '.select_option('topicimage', $topicimage, $tlist).'<br /><br />
	<input type="submit" name="createTopic" value="'._ADDTOPIC.'" />'.
	close_form();
	CloseTable();
}
