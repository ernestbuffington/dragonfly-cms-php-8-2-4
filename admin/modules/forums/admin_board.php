<?php
/***************************************************************************
 *					admin_board.php
 *				  -------------------
 *	 begin		  : Thursday, Jul 12, 2001
 *	 copyright	  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 * Last modification made by CPG Dev Team http://cpgnuke.com:
 *	 $Id: admin_board.php,v 9.9 2007/08/28 06:04:05 phoenix Exp $
 *
 *
 ***************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
global $lang;

//
// Pull all config data
//
if (!Cache::array_load('board_config', 'Forums', true)) {
	$result = $db->sql_query("SELECT * FROM " . CONFIG_TABLE);
	while ($row = $db->sql_fetchrow($result)) {
		$board_config[$row['config_name']] =  str_replace("'", "\'",$row['config_value']);
		$new[$config_name] = $_POST[$config_name] ?? $board_config[$config_name];
		if ($config_name == 'smilies_path') {
			$new['smilies_path'] = 'images/smiles';
		}
		if( isset($_POST['submit']) ) {
			$sql = "UPDATE " . CONFIG_TABLE . " SET
				config_value = '" . Fix_Quotes($new[$config_name]) . "'
				WHERE config_name = '$config_name'";
			$db->sql_query($sql);
			Cache::array_delete('board_config', 'Forums');
		}
	}
	Cache::array_save('board_config', 'Forums');
} elseif( isset($_POST['submit']) )  {
	foreach ($board_config as $config_name => $config_value){
		$new[$config_name] = $_POST[$config_name] ?? $board_config[$config_name];
		if ($config_name == 'smilies_path') {
			$new['smilies_path'] = 'images/smiles';
		}		
			$sql = "UPDATE " . CONFIG_TABLE . " SET
			config_value = '" . Fix_Quotes($new[$config_name]) . "'
			WHERE config_name = '$config_name'";
			$db->sql_query($sql);
	}
	Cache::array_delete('board_config', 'Forums');
}
if (isset($_POST['submit']))
{
	$message = $lang['Config_updated'] . "<br /><br />" . sprintf($lang['Click_return_config'], "<a href=\"".adminlink("&amp;do=board")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
	message_die(GENERAL_MESSAGE, $message);
	return;
}

$html_tags = $board_config['allow_html_tags'];

$override_user_style_yes = ( $board_config['override_user_style'] ) ? "checked=\"checked\"" : "";
$override_user_style_no = ( !$board_config['override_user_style'] ) ? "checked=\"checked\"" : "";

$html_yes = ( $board_config['allow_html'] ) ? "checked=\"checked\"" : "";
$html_no = ( !$board_config['allow_html'] ) ? "checked=\"checked\"" : "";

$bbcode_yes = ( $board_config['allow_bbcode'] ) ? "checked=\"checked\"" : "";
$bbcode_no = ( !$board_config['allow_bbcode'] ) ? "checked=\"checked\"" : "";

$board_email_form_yes = ( $board_config['board_email_form'] ) ? "checked=\"checked\"" : "";
$board_email_form_no = ( !$board_config['board_email_form'] ) ? "checked=\"checked\"" : "";

$privmsg_on = ( !is_active("Private_Messages") ) ? "checked=\"checked\"" : "";
$privmsg_off = ( is_active("Private_Messages") ) ? "checked=\"checked\"" : "";

$prune_yes = ( $board_config['prune_enable'] ) ? "checked=\"checked\"" : "";
$prune_no = ( !$board_config['prune_enable'] ) ? "checked=\"checked\"" : "";

// ROPM QUICK REPLY
$ropm_quick_reply_yes = ( $board_config['ropm_quick_reply']=='1' ) ? "checked=\"checked\"" : "";
$ropm_quick_reply_no = ( $board_config['ropm_quick_reply']=='0' ) ? "checked=\"checked\"" : "";
$ropm_quick_reply_bbc_yes = ( $board_config['ropm_quick_reply_bbc']=='1' ) ? "checked=\"checked\"" : "";
$ropm_quick_reply_bbc_no = ( $board_config['ropm_quick_reply_bbc']=='0') ? "checked=\"checked\"" : "";

$smile_yes = ( $board_config['allow_smilies'] ) ? "checked=\"checked\"" : "";
$smile_no = ( !$board_config['allow_smilies'] ) ? "checked=\"checked\"" : "";

$sig_yes = ( $board_config['allow_sig'] ) ? "checked=\"checked\"" : "";
$sig_no = ( !$board_config['allow_sig'] ) ? "checked=\"checked\"" : "";

$template->set_filenames(array('body' => 'forums/admin/board_config_body.html'));

$template->assign_vars(array(
	"S_CONFIG_ACTION" => adminlink("&amp;do=board"),
	"L_YES" => _YES,
	"L_NO" => _NO,
	"L_CONFIGURATION_TITLE" => $lang['General_Config'],
	"L_CONFIGURATION_EXPLAIN" => $lang['Config_explain'],
	"L_GENERAL_SETTINGS" => $lang['General_settings'],
	"L_PRIVATE_MESSAGING" => $lang['Private_Messaging'],
	"L_INBOX_LIMIT" => $lang['Inbox_limits'],
	"L_SENTBOX_LIMIT" => $lang['Sentbox_limits'],
	"L_SAVEBOX_LIMIT" => $lang['Savebox_limits'],
	"L_DISABLE_PRIVATE_MESSAGING" => $lang['Disable_privmsg'],
	"L_ENABLED" => _ACTIVE,
	"L_DISABLED" => _INACTIVE,
	"L_ABILITIES_SETTINGS" => $lang['Abilities_settings'],
	"L_MAX_POLL_OPTIONS" => $lang['Max_poll_options'],
	"L_FLOOD_INTERVAL" => $lang['Flood_Interval'],
	"L_FLOOD_INTERVAL_EXPLAIN" => $lang['Flood_Interval_explain'],
	"L_BOARD_EMAIL_FORM" => $lang['Board_email_form'],
	"L_BOARD_EMAIL_FORM_EXPLAIN" => $lang['Board_email_form_explain'],
	"L_TOPICS_PER_PAGE" => $lang['Topics_per_page'],
	"L_POSTS_PER_PAGE" => $lang['Posts_per_page'],
	"L_HOT_THRESHOLD" => $lang['Hot_threshold'],
	"L_DEFAULT_STYLE" => $lang['Default_style'],
	"L_OVERRIDE_STYLE" => $lang['Override_style'],
	"L_OVERRIDE_STYLE_EXPLAIN" => $lang['Override_style_explain'],
	"L_DATE_FORMAT" => $lang['Date_format'],
	"L_SYSTEM_TIMEZONE" => $lang['System_timezone'],
	"L_ENABLE_PRUNE" => $lang['Enable_prune'],
	// ROPM QUICK REPLY
	"L_ENABLE_ROPM_QUICK_REPLY" => $lang['enable_ropm_quick_reply'],
	"L_ROPM_QUICK_REPLY" => $lang['ropm_quick_reply'],
	"L_ROPM_QUICK_REPLY_BBC" => $lang['ropm_quick_reply_bbc'],
	"L_ALLOW_HTML" => $lang['Allow_HTML'],
	"L_ALLOW_BBCODE" => $lang['Allow_BBCode'],
	"L_ALLOWED_TAGS" => $lang['Allowed_tags'],
	"L_ALLOWED_TAGS_EXPLAIN" => $lang['Allowed_tags_explain'],
	"L_ALLOW_SMILIES" => $lang['Allow_smilies'],
	"L_ALLOW_SIG" => $lang['Allow_sig'],
	"L_MAX_SIG_LENGTH" => $lang['Max_sig_length'],
	"L_MAX_SIG_LENGTH_EXPLAIN" => $lang['Max_sig_length_explain'],
	"L_COPPA_SETTINGS" => $lang['COPPA_settings'],
	"L_COPPA_FAX" => $lang['COPPA_fax'],
	"L_COPPA_MAIL" => $lang['COPPA_mail'],
	"L_COPPA_MAIL_EXPLAIN" => $lang['COPPA_mail_explain'],
	"L_SUBMIT" => _SUBMIT,
	"L_RESET" => $lang['Reset'],

	"BOARD_EMAIL_FORM_ENABLE" => $board_email_form_yes,
	"BOARD_EMAIL_FORM_DISABLE" => $board_email_form_no,
	"MAX_POLL_OPTIONS" => $board_config['max_poll_options'],
	"FLOOD_INTERVAL" => $board_config['flood_interval'],
	"TOPICS_PER_PAGE" => $board_config['topics_per_page'],
	"POSTS_PER_PAGE" => $board_config['posts_per_page'],
	"HOT_TOPIC" => $board_config['hot_threshold'],
	"STYLE_SELECT" => 'deprecated',
	"OVERRIDE_STYLE_YES" => $override_user_style_yes,
	"OVERRIDE_STYLE_NO" => $override_user_style_no,
	"L_DATE_FORMAT_EXPLAIN" => $lang['Date_format_explain'],
	"DEFAULT_DATEFORMAT" => $board_config['default_dateformat'],
	"TIMEZONE_SELECT" => select_box('board_timezone', $board_config['board_timezone'], $l10n_gmt_regions),
	"INBOX_LIMIT" => $board_config['max_inbox_privmsgs'],
	"SENTBOX_LIMIT" => $board_config['max_sentbox_privmsgs'],
	"SAVEBOX_LIMIT" => $board_config['max_savebox_privmsgs'],
	"PRUNE_YES" => $prune_yes,
	"PRUNE_NO" => $prune_no,
// ROPM QUICK REPLY
"ROPM_QUICK_REPLY_YES" => $ropm_quick_reply_yes,
"ROPM_QUICK_REPLY_NO" => $ropm_quick_reply_no,
"ROPM_QUICK_REPLY_BBC_YES" => $ropm_quick_reply_bbc_yes,
"ROPM_QUICK_REPLY_BBC_NO" => $ropm_quick_reply_bbc_no,
	"HTML_TAGS" => $html_tags,
	"HTML_YES" => $html_yes,
	"HTML_NO" => $html_no,
	"BBCODE_YES" => $bbcode_yes,
	"BBCODE_NO" => $bbcode_no,
	"SMILE_YES" => $smile_yes,
	"SMILE_NO" => $smile_no,
	"SIG_YES" => $sig_yes,
	"SIG_NO" => $sig_no,
	"INBOX_PRIVMSGS" => $board_config['max_inbox_privmsgs'],
	"SENTBOX_PRIVMSGS" => $board_config['max_sentbox_privmsgs'],
	"SAVEBOX_PRIVMSGS" => $board_config['max_savebox_privmsgs'],
	"COPPA_MAIL" => $board_config['coppa_mail'],
	"COPPA_FAX" => $board_config['coppa_fax'],
	'S_HIDDEN_FIELDS' => ''
	)
	
);
