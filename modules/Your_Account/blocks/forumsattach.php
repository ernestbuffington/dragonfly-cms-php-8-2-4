<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Your_Account/blocks/forumsattach.php,v $
  $Revision: 9.8 $
  $Author: phoenix $
  $Date: 2007/08/30 12:03:40 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if (is_active('Forums') && $userinfo['user_id'] > 1) {
	if (!defined('IN_PHPBB') && !defined('PHPBB_INSTALLED')) {
		define('IN_PHPBB', true);
		define('PHPBB_INSTALLED', true);
		$phpbb_root_path = "./modules/Forums/";
		require_once($phpbb_root_path.'common.php');
	}
	$userdata = $userinfo;

	$user_id = intval($userinfo['user_id']);
	global $attach_config, $board_config, $phpbb_root_path, $lang, $db, $template, $profiledata, $bgcolor4;
	$board_config['privmsg_graphic_length'] = 175;

	if (!$owninfo && !can_admin('members')) { return; }

	$attachments = new attach_posting();
	$attachments->PAGE = PAGE_INDEX;

	// Get the assigned Quota Limit. For Groups, we are directly getting the value, because this Quota can change from user to user.
	if (is_array($profiledata)) {
		$attachments->get_quota_limits($profiledata, $user_id);
	} else {
		$attachments->get_quota_limits($userdata, $user_id);
	}

	if ( intval($attach_config['upload_filesize_limit']) == 0 ) {
		$upload_filesize_limit = intval($attach_config['attachment_quota']);
	} else {
		$upload_filesize_limit = intval($attach_config['upload_filesize_limit']);
	}
	$user_quota = ($upload_filesize_limit == 0) ? $lang['Unlimited'] : filesize_to_human($upload_filesize_limit);

	//
	// Get all attach_id's the specific user posted, but only uploads to the board and not Private Messages
	//
	$sql = "SELECT attach_id FROM " . ATTACHMENTS_TABLE . "
	WHERE (user_id_1 = " . $user_id . ") AND (privmsgs_id = 0)
	GROUP BY attach_id";

	$result = $db->sql_query($sql);

	$attach_ids = $db->sql_fetchrowset($result);
	$num_attach_ids = $db->sql_numrows($result);
	$attach_id = array();

	for ($j = 0; $j < $num_attach_ids; $j++) {
		$attach_id[] = intval($attach_ids[$j]['attach_id']);
	}

	$upload_filesize = (count($attach_id) > 0) ? get_total_attach_filesize(implode(',', $attach_id)) : 0;
	$user_uploaded = filesize_to_human($upload_filesize);

	$upload_limit_pct = ( $upload_filesize_limit > 0 ) ? round(( $upload_filesize / $upload_filesize_limit ) * 100) : 0;
	$upload_limit_img_length = ( $upload_filesize_limit > 0 ) ? round(( $upload_filesize / $upload_filesize_limit ) * $board_config['privmsg_graphic_length']) : 0;
	if ($upload_limit_pct > 100)
	{
		$upload_limit_img_length = $board_config['privmsg_graphic_length'];
	}
	$upload_limit_remain = ( $upload_filesize_limit > 0 ) ? $upload_filesize_limit - $upload_filesize : 100;

	$l_box_size_status = sprintf($lang['Upload_percent_profile'], $upload_limit_pct);

	echo '<br />';
	OpenTable();
	echo '<div align="left"><table width="100%">
		<tr>
			<td valign="top" align="right" style="white-space:nowrap;"><span class="gen"><strong>'.$lang['Upload_quota'].':</strong></span></td>
			<td>
				<table width="175" cellspacing="1" cellpadding="2" border="0" class="bodyline">
				<tr>
					<td colspan="3" width="100%" class="row2">
						<table cellspacing="0" cellpadding="1" border="0">
						<tr>
							<td bgcolor="'.$bgcolor4.'"><img src="images/spacer.gif" width="'.$upload_limit_img_length.'" height="8" alt="'.$upload_limit_pct.'" /></td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="33%" class="row1"><span class="gensmall">0%</span></td>
					<td width="34%" align="center" class="row1"><span class="gensmall">50%</span></td>
					<td width="33%" align="right" class="row1"><span class="gensmall">100%</span></td>
				</tr>
				</table>
				<b><span class="genmed">['.sprintf($lang['User_uploaded_profile'], $user_uploaded).' / '.sprintf($lang['User_quota_profile'], $user_quota).' / '.$l_box_size_status.']</span> </b><br />
				<span class="genmed"><a href="'.getlink("Forums&amp;file=uacp&amp;u=$user_id").'" class="genmed">'.$lang['UACP'].'</a></span>
			</td>
		</tr>
	</table></div>';
	CloseTable();
}
