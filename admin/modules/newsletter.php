<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/admin/modules/newsletter.php,v $
  $Revision: 9.20 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:58 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('newsletter')) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '._NEWSLETTER;

function newsletter_selection($fieldname, $current) {
	static $groups;
	if (!isset($groups)) {
		global $db, $prefix;
		$groups = array(0=>_NL_ALLUSERS, 1=>_SUBSCRIBEDUSERS, 2=>_NL_ADMINS);
		$groupsResult = $db->sql_query("SELECT group_id, group_name FROM ".$prefix."_bbgroups WHERE group_single_user=0");
		while (list($groupID, $groupName) = $db->sql_fetchrow($groupsResult)) {
			$groups[($groupID+2)] = $groupName;
		}
	}
	$tmpgroups = $groups;
	return select_box($fieldname, $current, $tmpgroups);
}

$subject = $_POST['subject'] ?? '';
$content = $_POST['content'] ?? '';
$group = isset($_POST['group']) ? intval($_POST['group']) : 1;

if (isset($_POST['discard'])) {
	url_redirect(adminlink('newsletter'));
} elseif (isset($_POST['send'])) {
	$subject = $_POST['subject'];
	$n_group = intval($_POST['n_group']);

	if (empty($subject)) { cpg_error(sprintf(_ERROR_NOT_SET, _SUBJECT)); }
	if (empty($content)) { cpg_error(sprintf(_ERROR_NOT_SET, _CONTENT)); }
	ignore_user_abort(true);
	if ($n_group == 0) {
		$query = 'SELECT username, user_email FROM '.$user_prefix.'_users WHERE user_level > 0 AND user_id > 1';
		$count = $db->sql_count($user_prefix.'_users WHERE user_level > 0 AND user_id > 1');
	} elseif ($n_group == 2) {
		$query = 'SELECT aid, email FROM '.$prefix.'_admins';
		$count = $db->sql_count($prefix.'_admins');
	} elseif ($n_group > 2) {
		$n_group -= 2;
		$query = 'SELECT u.username, u.user_email FROM '.$user_prefix.'_users u, '.$prefix.'_bbuser_group g WHERE u.user_level>0 AND g.group_id='.$n_group.' AND u.user_id = g.user_id AND user_pending=0';
		$count = $db->sql_count($user_prefix.'_users u, '.$prefix.'_bbuser_group g WHERE u.user_level>0 AND g.group_id='.$n_group.' AND u.user_id = g.user_id AND user_pending=0');
	} else {
		$query = 'SELECT username, user_email FROM '.$user_prefix.'_users WHERE user_level > 0 AND user_id > 1 AND newsletter=1';
		$count = $db->sql_count($user_prefix.'_users WHERE user_level > 0 AND user_id > 1 AND newsletter=1');
	}
	$content = _HELLO.",\n\n$content\n\n\n"._NL_REGARDS.",\n\n$sitename "._STAFF."\n\n\n\n"._NLUNSUBSCRIBE;
	$recipients = array();
	$limit = 1000; //$MAIN_CFG['email']['limit']
	set_time_limit(0);
	for ($offset=0; $offset<$count; $offset+=$limit) {
		$result = $db->sql_query($query." LIMIT $offset, $limit");
		while (list($u_name, $u_email) = $db->sql_fetchrow($result, SQL_NUM)) {
			if (is_email($u_email) > 0) { $recipients[$u_email] = $u_name; }
		}
	}
	if (empty($recipients) || count($recipients) < 1) {
		cpg_error('0 '._NL_RECIPS, _NEWSLETTER);
	}
	if (count($recipients) > 50) {
		while ($part_recips = array_splice($recipients,0,50)) {
			send_mail($mailer_message, $content, 1, $subject, $part_recips, '', $adminmail, $sitename);
		}
	} else {
		send_mail($mailer_message, $content, 1, $subject, $recipients, '', $adminmail, $sitename);
	}
/*
	foreach ($recipients AS $email => $name) {
		send_mail($mailer_message, sprintf($content, $name), 1, $subject, $email, $name, $adminmail, $sitename);
	}
*/
	cpg_error(_NEWSLETTERSENT, _NEWSLETTER, $adminindex);
}

$title = _NEWSLETTER;
$preview = $notes = $submit = '';
if (isset($_POST['preview'])) {
	$pagetitle .= ' '._BC_DELIM.' '._PREVIEW;
	$title .= ' '._PREVIEW;
	if (empty($subject)) { cpg_error(sprintf(_ERROR_NOT_SET, _SUBJECT)); }
	if (empty($content)) { cpg_error(sprintf(_ERROR_NOT_SET, _CONTENT)); }
	if ($group == 0) {
		$num_users = $db->sql_count($user_prefix."_users", 'user_level > 0 AND user_id > 1');
		$group_name = strtolower(_NL_ALLUSERS);
	} elseif ($group == 2) {
		$num_users = $db->sql_count($prefix."_admins");
		$group_name = strtolower(_NL_ADMINS);
	} elseif ($group > 2) {
		$group_id = $group-2;
		$num_users = $db->sql_count($prefix."_bbuser_group", "group_id=$group_id AND user_pending=0");
		list($group_name) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix."_bbgroups WHERE group_id=$group_id", SQL_NUM);
	} else {
		$num_users = $db->sql_count($user_prefix."_users", 'user_level > 0 AND newsletter=1');
		$group_name = strtolower(_SUBSCRIBEDUSERS);
	}
	$status = '';
	if ($num_users < 1) { $status = ' disabled="disabled"'; }
	if ($num_users > 500) {
		$notes = '<tr><td align="center" class="row1" colspan="2">'._MANYUSERSNOTE.'</td></tr>';
	} elseif ($num_users < 1) {
		$notes = '<tr><td align="center" class="row1" colspan="2">'._NL_NOUSERS.'</td></tr>';
	}
	if (!preg_match('#<br#mi',$content)) $content = nl2br($content);
	$preview = '<tr>
	<td class="row1" colspan="2">
	<span style="float: left">This newsletter will be sent to <b>'.$group_name.'</b></span>
	<span style="float: right"><b>'.$num_users.'</b> '._NUSERWILLRECEIVE.'</span><br />
	<hr />
	<span class="gen">'.$content.'</span>
	<hr />
	</td>
</tr>';
	$submit = ' &nbsp;
	<input type="submit" name="send" value="'._SEND.'&nbsp;'._NEWSLETTER.'" class="mainoption"'.$status.' /> &nbsp;
	<input type="submit" name="discard" value="'._DISCARD.'" class="liteoption" />
	<input type="hidden" name="n_group" value="'.$group.'" />';
}

// Load the required wysiwyg class
require(CORE_PATH.'wysiwyg/wysiwyg.inc');
// Create as many wysiwyg instances as you need
$wysiwyg = new Wysiwyg('newsletter', 'content', '90%', '300px', $content);
// Set all the required wysiwyg headers
$wysiwyg->setHeader();

require('header.php');
GraphicAdmin('_AMENU5');
OpenTable();
echo '<form name="newsletter" action="'.adminlink().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
'.$wysiwyg->getSelect().'
<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline" align="center">
<tr>
	<td align="center" class="catleft" colspan="2"><b><span class="gen">'.$title.'</span></b></td>
</tr>'.$preview.'<tr>
	<td class="row1"><span class="gen">'._SUBJECT.'</span></td>
	<td class="row2"><input type="text" name="subject" size="50" maxlength="255" value="'.htmlprepare($subject).'" /></td>
</tr><tr>
	<td class="row1"><span class="gen">'._CONTENT.'</span></td>
	<td class="row2">'.$wysiwyg->getHTML().'</td>
</tr><tr>
	<td class="row1"><span class="gen">'._NL_RECIPS.'</span></td>
	<td class="row2">'.newsletter_selection('group', $group).'</td>
</tr>'.$notes.'<tr>
	<td class="catbottom" colspan="2" align="center" height="28">
	<input type="submit" name="preview" value="'._PREVIEW.'" class="mainoption" />'.$submit.'
	</td>
</tr></table></form>';
CloseTable();
