<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/nbbcode.php,v $
  $Revision: 9.46 $
  $Author: nanocaiordo $
  $Date: 2008/01/31 08:52:47 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }


global $db, $prefix, $smilies_path, $bbbttns_path, $BASEHREF, $CPG_SESS, $bb_codes, $smilies_close, $bbcode_common;

$smilies_path = file_exists("themes/$CPG_SESS[theme]/images/smiles/icon_smile.gif") ? "themes/$CPG_SESS[theme]/images/smiles/" : 'images/smiles/';
$bbbttns_path = file_exists("themes/$CPG_SESS[theme]/images/bbcode/b.gif") ? "themes/$CPG_SESS[theme]/images/bbcode/" : 'themes/default/images/bbcode/';

get_lang('bbcode');

$bb_codes['quote'] = '<table width="90%" cellspacing="1" cellpadding="3" border="0" align="center"><tr>
	<td><span class="genmed"><b>'.$bbcode_common['quote'][0].':</b></span></td>
</tr><tr>
	<td class="quote">';
$bb_codes['quote_name'] = '<table width="90%" cellspacing="1" cellpadding="3" border="0" align="center"><tr>
	<td><span class="genmed"><b>\\1 '.$bbcode_common['Wrote'].':</b></span></td>
</tr><tr>
	<td class="quote">';
$bb_codes['quote_close'] = '</td></tr></table>';
$bb_codes['code_start'] = '<table width="90%" cellspacing="1" cellpadding="3" border="0" align="center"><tr>
		<td><span class="genmed"><b>'.$bbcode_common['code'][0].':</b></span></td>
</tr><tr>
	<td class="code"><code>';
$bb_codes['code_end'] =  '</code></td></tr></table>';
$bb_codes['php_start'] = '<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1"><tr>
	<td><span class="genmed"><b>PHP:</b></span></td>
</tr><tr>
	<td class="code">';
$bb_codes['php_end'] = '</td></tr></table>';
$bb_codes['win_start'] = '<html>
<head>
  <title>Smiley Selection</title>
  <base href="'.$BASEHREF.'" />
  <link rel="stylesheet" href="themes/'.$CPG_SESS['theme'].'/style/style.css" type="text/css" />
</head>
<body>
<script type="text/javascript">
<!--
function emoticon(form, field, text) {
	text = \' \' + text + \' \';
	if (opener.document.forms[form].elements[field].createTextRange && opener.document.forms[form].elements[field].caretPos) {
		var caretPos = opener.document.forms[form].elements[field].caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == \' \' ? text + \' \' : text;
		opener.document.forms[form].elements[field].focus();
	} else {
		opener.document.forms[form].elements[field].value += text;
		opener.document.forms[form].elements[field].focus();
	}
}
//-->
</script>';
$bb_codes['win_end'] = '<br />
<div align="center"><a href="javascript:window.close();" class="genmed">'.$smilies_close.'</a></div>
</body></html>';
if (file_exists('themes/'.$CPG_SESS['theme'].'/bbcode.inc')) {
	include('themes/'.$CPG_SESS['theme'].'/bbcode.inc');
}

function get_code_lang($var, $array) {
	return (isset($array[$var])) ? $array[$var] : $var;
}

function smilies_table($mode, $field='message', $form='post')
{
	global $bb_codes, $db, $prefix, $smilies_path, $MAIN_CFG, $CPG_SESS;
	global $smilies_more, $smilies_desc;
	$url = $MAIN_CFG['server']['path'].getlink("smilies&amp;field=$field&amp;form=$form");

	$inline_cols = 4;
	$inline_rows = 5;
	$window_cols = 8;

	$content = '';
	if ($mode == 'window') {
		$content = $bb_codes['win_start'];
	} else if (!defined('BBCODE_JS_ACTIVE')) {
		$content .= '<script src="includes/javascript/bbcode.js" type="text/javascript"></script>';
		define('BBCODE_JS_ACTIVE', 1);
	}
	if ($mode == 'onerow') {
		$content .= '
<table width="450" border="0" cellspacing="0" cellpadding="0" class="forumline">';
	} else {
		$content .= '
<table width="100" border="0" cellspacing="0" cellpadding="5" class="forumline">';
	}
	$smilies = get_smilies();
	if (is_array($smilies)) {
		$num_smilies = 0;
		$rowset = array();
		#while ($row = $db->sql_fetchrow($result)) {
		for ($i=0; $i<count($smilies); ++$i) {
			if (empty($rowset[$smilies[$i]['smile_url']])) {
				$rowset[$smilies[$i]['smile_url']]['code'] = str_replace("'", "\\'", str_replace('\\', '\\\\', $smilies[$i]['code']));
# process the smiley description
				$rowset[$smilies[$i]['smile_url']]['emoticon'] = get_code_lang($smilies[$i]['emoticon'],$smilies_desc);
				$num_smilies++;
			}
		}
		if ($num_smilies) {
			$smilies_count = ($mode == 'inline') ? min(19, $num_smilies) : $num_smilies;
			$smilies_split_row = ($mode == 'inline') ? $inline_cols - 1 : $window_cols - 1;

			$s_colspan = $row = $col = 0;

			while (list($smile_url, $data) = each($rowset)) {
				if (!$col) {
					$content .= '<tr align="center" valign="middle">';
				}
				$content .= "<td><a href=\"javascript:emoticon('".$form."', '".$field."', '".$data['code']."')\"><img src=\"" . $smilies_path . $smile_url . "\" style=\"border:0;\" alt=\"".$data['emoticon']."\" title=\"".$data['emoticon']."\" /></a></td>";
				$s_colspan = max($s_colspan, $col + 1);

				if ($mode == 'onerow') {
					if ($col >= 15) {
						if ($num_smilies > 15) {
							$content .= "<td colspan=\"$s_colspan\" class=\"nav\"><a href=\"$url\" onclick=\"window.open('$url', '_smilies', 'height=200,resizable=yes,scrollbars=yes,width=230');return false;\" target=\"_smilies\" class=\"nav\">$smilies_more</a></td>";
						}
						break;
					}
					$col++;
				}
				else if ($col == $smilies_split_row) {
					$content .= '</tr>';
					$col = 0;
					if ($mode == 'inline' && $row == $inline_rows - 1) {
						break;
					}
					$row++;
				}
				else { $col++; }
			}
			if ($col > 0) { $content .= '</tr>'; }

			if ($mode == 'inline' && $num_smilies > $inline_rows * $inline_cols) {
				$content .= "<tr align=\"center\">
					<td colspan=\"$s_colspan\" class=\"nav\"><a href=\"$url\" onclick=\"window.open('$url', '_smilies', 'height=200,resizable=yes,scrollbars=yes,width=230');return false;\" target=\"_smilies\" class=\"nav\">$smilies_more</a></td>
				</tr>";
			}
		}
	}
	$content .= "\n</table>\n";
	if ($mode == 'window') { $content .= $bb_codes['win_end']; }
	return $content;
}

function bbcode_table($field='message', $form='post', $allowed=0)
{
	global $bbbttns_path, $color_desc, $font_desc, $textcolor1, $bbcode_common;
	$content = '';
	if (!defined('BBCODE_JS_ACTIVE')) {
		$content .= '	<script type="text/javascript">
	var b_help = "'. $bbcode_common['bold'][0].' '.$bbcode_common['bold'][1].'";
	var i_help = "'. $bbcode_common['italic'][0].' '.$bbcode_common['italic'][1].'";
	var u_help = "'. $bbcode_common['underline'][0].' '.$bbcode_common['underline'][1].'";
	var quote_help = "'. $bbcode_common['quote'][0].' '.$bbcode_common['quote'][1].'";
	var code_help = "'. $bbcode_common['code'][0].' '.$bbcode_common['code'][1].'";
	var php_help = "'. $bbcode_common['php'][0].' '.$bbcode_common['php'][1].'";
	var img_help = "'. $bbcode_common['img'][0].' '.$bbcode_common['img'][1].'";
	var fc_help = "'. $bbcode_common['fc'][0].' '.$bbcode_common['fc'][1].'";
	var fs_help = "'. $bbcode_common['fs'][0].' '.$bbcode_common['fs'][1].'";
	var ft_help = "'. $bbcode_common['ft'][0].' '.$bbcode_common['ft'][1].'";
	var rtl_help = "'. $bbcode_common['rtl'][0].' '.$bbcode_common['rtl'][1].'";
	var ltr_help = "'. $bbcode_common['ltr'][0].' '.$bbcode_common['ltr'][1].'";
	var mail_help = "'. $bbcode_common['mail'][0].' '.$bbcode_common['mail'][1].'";
	var url_help= "'. $bbcode_common['url'][0].' '.$bbcode_common['url'][1].'";
	var right_help= "'. $bbcode_common['right'][0].' '.$bbcode_common['right'][1].'";
	var left_help= "'. $bbcode_common['left'][0].' '.$bbcode_common['left'][1].'";
	var center_help= "'. $bbcode_common['center'][0].' '.$bbcode_common['center'][1].'";
	var justify_help= "'. $bbcode_common['justify'][0].' '.$bbcode_common['justify'][1].'";
	var marqr_help= "'. $bbcode_common['marqr'][0].' '.$bbcode_common['marqr'][1].'";
	var marql_help= "'. $bbcode_common['marql'][0].' '.$bbcode_common['marql'][1].'";
	var marqu_help= "'. $bbcode_common['marqu'][0].' '.$bbcode_common['marqu'][1].'";
	var marqd_help= "'. $bbcode_common['marqd'][0].' '.$bbcode_common['marqd'][1].'";
	var hr_help= "'. $bbcode_common['hr'][0].' '.$bbcode_common['hr'][1].'";
	var video_help="'. $bbcode_common['video'][0].' '.$bbcode_common['video'][1].'";
	var flash_help="'. $bbcode_common['flash'][0].' '.$bbcode_common['flash'][1].'";</script>
	<script src="includes/javascript/bbcode.js" type="text/javascript"></script>';
		define('BBCODE_JS_ACTIVE', 1);
	}
	$content .= '<table cellpadding="0" cellspacing="0">
<tr>
	<td>
		<img alt="'.$bbcode_common['bold'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'b\')" onclick="BBCcode(\''.$form.'\',\''.$field.'\',this,\'b\')" src="'.$bbbttns_path.'b.gif" />
		<img alt="'.$bbcode_common['italic'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'i\')" onclick="BBCcode(\''.$form.'\',\''.$field.'\',this,\'i\')" src="'.$bbbttns_path.'i.gif" />
		<img alt="'.$bbcode_common['underline'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'u\')" onclick="BBCcode(\''.$form.'\',\''.$field.'\',this,\'u\')" src="'.$bbbttns_path.'u.gif" />
&nbsp;&nbsp;
		<img alt="'.$bbcode_common['ltr'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'ltr\')" onclick="BBCdir(\''.$form.'\',\''.$field.'\',\'ltr\')" src="'.$bbbttns_path.'ltr.gif" />
		<img alt="'.$bbcode_common['rtl'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'rtl\')" onclick="BBCdir(\''.$form.'\',\''.$field.'\',\'rtl\')" src="'.$bbbttns_path.'rtl.gif" />
&nbsp;&nbsp;
		<img alt="'.$bbcode_common['url'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'url\')" onclick="BBCurl(\''.$form.'\',\''.$field.'\')" src="'.$bbbttns_path.'url.gif" />
		<img alt="'.$bbcode_common['mail'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'mail\')" onclick="BBCwmi(\''.$form.'\',\''.$field.'\',\'email\')" src="'.$bbbttns_path.'email.gif" />';
	if ($allowed) {
		$content .= '
&nbsp;&nbsp;
		<img alt="'.$bbcode_common['justify'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'justify\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'align\',this,\'justify\')" src="'.$bbbttns_path.'align_justify.gif" />
		<img alt="'.$bbcode_common['left'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'left\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'align\',this,\'left\')" src="'.$bbbttns_path.'align_left.gif" />
		<img alt="'.$bbcode_common['center'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'center\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'align\',this,\'center\')" src="'.$bbbttns_path.'align_center.gif" />
		<img alt="'.$bbcode_common['right'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'right\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'align\',this,\'right\')" src="'.$bbbttns_path.'align_right.gif" />';
	}
	$content .= '
&nbsp;&nbsp;
		<select onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'fc\')" onchange="BBCfc(\''.$form.'\',\''.$field.'\',this)" title="'.$color_desc['color'].'">
		<option class="genmed" value="'.$textcolor1.'" style="color: black; background-color: rgb(250, 250, 250);">'.$color_desc['Default'].'</option>
		<option class="genmed" value="maroon" style="color: maroon; background-color: rgb(250, 250, 250);">'.$color_desc['Dark Red'].'</option>
		<option class="genmed" value="red" style="color: red; background-color: rgb(250, 250, 250);">'.$color_desc['Red'].'</option>
		<option class="genmed" value="orange" style="color: orange; background-color: rgb(250, 250, 250);">'.$color_desc['Orange'].'</option>
		<option class="genmed" value="brown" style="color: brown; background-color: rgb(250, 250, 250);">'.$color_desc['Brown'].'</option>
		<option class="genmed" value="yellow" style="color: yellow; background-color: rgb(250, 250, 250);">'.$color_desc['Yellow'].'</option>
		<option class="genmed" value="green" style="color: green; background-color: rgb(250, 250, 250);">'.$color_desc['Green'].'</option>
		<option class="genmed" value="olive" style="color: olive; background-color: rgb(250, 250, 250);">'.$color_desc['Olive'].'</option>
		<option class="genmed" value="cyan" style="color: cyan; background-color: rgb(250, 250, 250);">'.$color_desc['Cyan'].'</option>
		<option class="genmed" value="blue" style="color: blue; background-color: rgb(250, 250, 250);">'.$color_desc['Blue'].'</option>
		<option class="genmed" value="darkblue" style="color: darkblue; background-color: rgb(250, 250, 250);">'.$color_desc['Dark Blue'].'</option>
		<option class="genmed" value="indigo" style="color: indigo; background-color: rgb(250, 250, 250);">'.$color_desc['Indigo'].'</option>
		<option class="genmed" value="violet" style="color: violet; background-color: rgb(250, 250, 250);">'.$color_desc['Violet'].'</option>
		<option class="genmed" value="white" style="color: white; background-color: rgb(250, 250, 250);">'.$color_desc['White'].'</option>
		<option class="genmed" value="black" style="color: black; background-color: rgb(250, 250, 250);">'.$color_desc['Black'].'</option>
		</select>';
	if ($allowed) {
		$content .= '
	</td>
</tr><tr>
	<td>
		<img alt="'.$bbcode_common['img'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'img\')" onclick="BBCwmi(\''.$form.'\',\''.$field.'\',\'img\')" src="'.$bbbttns_path.'img.gif" />
		<img alt="'.$bbcode_common['flash'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'flash\')" onclick="BBCmm(\''.$form.'\',\''.$field.'\',\'flash\')" src="'.$bbbttns_path.'flash.gif" />
		<img alt="'.$bbcode_common['video'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'video\')" onclick="BBCmm(\''.$form.'\',\''.$field.'\',\'video\')" src="'.$bbbttns_path.'video.gif" />
&nbsp;&nbsp;
		<img alt="'.$bbcode_common['quote'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'quote\')" onclick="BBCcode(\''.$form.'\',\''.$field.'\',this,\'quote\')" src="'.$bbbttns_path.'quote.gif" />
		<img alt="'.$bbcode_common['code'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'code\')" onclick="BBCcode(\''.$form.'\',\''.$field.'\',this,\'code\')" src="'.$bbbttns_path.'code.gif" />
		<img alt="'.$bbcode_common['php'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'php\')" onclick="BBCcode(\''.$form.'\',\''.$field.'\',this,\'php\')" src="'.$bbbttns_path.'php.gif" />
&nbsp;&nbsp;
		<img alt="'.$bbcode_common['hr'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'hr\')" onclick="BBChr(\''.$form.'\',\''.$field.'\')" src="'.$bbbttns_path.'hr.gif" />
&nbsp;&nbsp;
		<img alt="'.$bbcode_common['marqd'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'marqd\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'marq\',this,\'down\')" src="'.$bbbttns_path.'marq_down.gif" />
		<img alt="'.$bbcode_common['marqu'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'marqu\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'marq\',this,\'up\')" src="'.$bbbttns_path.'marq_up.gif" />
		<img alt="'.$bbcode_common['marql'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'marql\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'marq\',this,\'left\')" src="'.$bbbttns_path.'marq_left.gif" />
		<img alt="'.$bbcode_common['marqr'][0].'" class="bbcbutton" onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'marqr\')" onclick="BBCode(\''.$form.'\',\''.$field.'\',\'marq\',this,\'right\')" src="'.$bbbttns_path.'marq_right.gif" />
&nbsp;&nbsp;
		<select onmouseover="helpline(\''.$form.'\',\''.$field.'\',\'fs\')" onchange="BBCfs(\''.$form.'\',\''.$field.'\',this)" title="'.$font_desc['size'].'">
		<option value="7" class="genmed">'.$font_desc['Tiny'].'</option>
		<option value="9" class="genmed">'.$font_desc['Small'].'</option>
		<option value="12" class="genmed" selected="selected">'.$font_desc['Normal'].'</option>
		<option value="18" class="genmed">'.$font_desc['Large'].'</option>
		<option  value="24" class="genmed">'.$font_desc['Huge'].'</option>
		</select>';
	}
	$content .= '
	</td>
</tr><tr>
	<td>
		<input type="text" name="help'.$field.'" size="66" maxlength="100" value="'.$bbcode_common['Tip'].'" class="helpline" />
	</td>
  </tr>
</table>';

	return $content;
}

function get_smilies() {
	global $db, $prefix;
	$smilies = Cache::array_load('smilies','bb', false);
	if (!$smilies) {
		$smilies = $db->sql_ufetchrowset('SELECT * FROM '.$prefix.'_bbsmilies', SQL_ASSOC);
		if (count($smilies)) {
			usort($smilies, 'sort_smiley');
			Cache::array_save('smilies','bb', $smilies);
		}
	}
	return $smilies;
}
# smilies_pass(
function set_smilies($message, $url='') {
	static $orig, $repl;
	if (!isset($orig)) {
		global $smilies_path, $smilies_desc;
		$orig = $repl = array();
		$smilies = get_smilies();
		if ($url != '' && substr($url, -1) != '/') { $url .= '/'; }
		for ($i = 0; $i < count($smilies); $i++) {
			$smilies[$i]['code'] = str_replace('#', '\#', preg_quote($smilies[$i]['code']));
			$orig[] = "#([\s])".$smilies[$i]['code']."([\s<])#si";
			$repl[] = '\\1<img src="' . $url . $smilies_path . $smilies[$i]['smile_url'] . '" alt="'.get_code_lang($smilies[$i]['emoticon'],$smilies_desc).'" title="'.get_code_lang($smilies[$i]['emoticon'],$smilies_desc).'" />\\2';
		}
	}
	if (count($orig)) {
		$message = preg_replace($orig, $repl, " $message ");
		$message = substr($message, 1, -1);
	}
	return $message;
}

function sort_smiley($a, $b)
{
	if ($a['pos'] == $b['code']) { return 0; }
	return ($a['pos'] < $b['pos']) ? -1 : 1;
//	if (strlen($a['code']) == strlen($b['code'])) { return 0; }
//	return (strlen($a['code']) > strlen($b['code'])) ? -1 : 1;
}

# bbencode_first_pass() prepare bbcode for db insert
function encode_bbcode($text)
{
	return BBCode::encode($text);
}
function decode_bb_all($text, $allowed=0, $allow_html=false, $url='') {
	return set_smilies(decode_bbcode($text, $allowed, $allow_html), $url);
}
function decode_bbcode($text, $allowed=0, $allow_html=false)
{
	return BBCode::decode($text, $allowed, $allow_html);
}

function shrink_url($url) {
	$url = preg_replace("#(^[\w]+?://)#", '', $url);
	return (strlen($url) > 35) ? substr($url,0,22).'...'.substr($url,-10) : $url;
}

function make_clickable($text)
{
	$ret = ' ' . $text;
	$ret = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t<]*)#ise", "'\\1<a href=\"\\2\" rel=\"nofollow\" title=\"\\2\" target=\"_blank\">'.shrink_url('\\2').'</a>'", $ret);
	$ret = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://\\2\" rel=\"nofollow\" target=\"_blank\" title=\"\\2\">'.shrink_url('\\2').'</a>'", $ret);
	$ret = preg_replace("#(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1 \\2 &#64; \\3", $ret);
	$ret = substr($ret, 1);
	return($ret);
}

# prepare_message(
function message_prepare($message, $html_on, $bbcode_on)
{
	global $board_config;
	#
	# Clean up the message
	#
	$message = trim($message);
	if ($html_on) {
		$allowed_html_tags = split(',', $board_config['allow_html_tags']);
		$end_html = 0;
		$start_html = 1;
		$tmp_message = '';
		$message = ' ' . $message . ' ';
		while ($start_html = strpos($message, '<', $start_html)) {
			$tmp_message .= BBCode::encode_html(substr($message, $end_html + 1, ($start_html - $end_html - 1)));
			if ($end_html = strpos($message, '>', $start_html)) {
				$length = $end_html - $start_html + 1;
				$hold_string = substr($message, $start_html, $length);
				if (($unclosed_open = strrpos(' ' . $hold_string, '<')) != 1) {
					$tmp_message .= BBCode::encode_html(substr($hold_string, 0, $unclosed_open - 1));
					$hold_string = substr($hold_string, $unclosed_open - 1);
				}
				$tagallowed = false;
				for ($i = 0; $i < sizeof($allowed_html_tags); $i++) {
					$match_tag = trim($allowed_html_tags[$i]);
					if (preg_match('#^<\/?' . $match_tag . '[> ]#i', $hold_string)) {
						$tagallowed = (preg_match('#^<\/?' . $match_tag . ' .*?(style[ ]*?=|on[\w]+[ ]*?=)#i', $hold_string)) ? false : true;
					}
				}
				$tmp_message .= ($length && !$tagallowed) ? BBCode::encode_html($hold_string) : $hold_string;
				$start_html += $length;
			} else {
				$tmp_message .= BBCode::encode_html(substr($message, $start_html));
				$start_html = strlen($message);
				$end_html = $start_html;
			}
		}
		if ($end_html != strlen($message) && $tmp_message != '') {
			$tmp_message .= BBCode::encode_html(substr($message, $end_html + 1));
		}
		$message = ($tmp_message != '') ? trim($tmp_message) : trim($message);
	} else {
		$message = BBCode::encode_html($message);
	}
	if ($bbcode_on) {
		$message = BBCode::encode($message);
	}
	return $message;
}

class BBCode {

	function encode_html($text) {
		return (ereg('<', $text)) ? htmlprepare($text, false, ENT_NOQUOTES) : $text;
	}

	function encode($text)
	{
		# Split all bbcodes.
		$text_parts = BBCode::split_bbcodes($text);
		# Merge all bbcodes and do special actions depending on the type of code.
		$text = '';
		while ($part = array_shift($text_parts)) {
			if (isset($part['code'])) {
				if ($part['code'] == 'list' && $part['text'][5] == '=' && substr($part['text'], -3) != ':o]') {
					# [list=x] for ordered lists.
					$part['text'] = substr($part['text'], 0, -1).':o]';
				}
				if ($part['code'] != 'code' && $part['code'] != 'php' && $part['subc']) {
					$part['text'] = '['.encode_bbcode(substr($part['text'], 1, -1)).']';
				}
			}
			$text .= $part['text'];
		}
		return trim($text);
	}

	function decode($text, $allowed=0, $allow_html=false)
	{
		global $bb_codes;
		# First: If there isn't a "[" and a "]" in the message, don't bother.
		if (!(strpos($text, '[') !== false && strpos($text, ']'))) {
			return ($allow_html ? (ereg('<', $text) ? $text : nl2br($text)) : nl2br(strip_tags($text)));
		}

		// strip the obsolete bbcode_uid
		$text = preg_replace("/:(([a-z0-9]+:)?)[a-z0-9]{10}(=|\])/si", '\\3', $text);

		# pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
		$text = BBCode::split_on_bbcodes($text, $allowed, $allow_html);

		# Patterns and replacements for URL, email tags etc.
		$patterns = $replacements = array();

		# replace single & to &amp;
		$text = preg_replace('/&(?![a-z]{2,6};|#[0-9]{1,4};)/is', '&amp;', $text);

		# colours
		$patterns[] = '#\[color=(\#[0-9A-F]{6}|[a-z]+)\](.*?)\[/color\]#si';
		$replacements[] = '<span style="color: \\1">\\2</span>';

		# size
		$patterns[] = '#\[size=([1-2]?[0-9])\](.*?)\[/size\]#si';
		$replacements[] = '<span style="font-size: \\1px; line-height: normal">\\2</span>';

		# [b] and [/b] for bolding text.
		$patterns[] = '#\[b\](.*?)\[/b\]#si';
		$replacements[] = '<span style="font-weight: bold">\\1</span>';

		# [u] and [/u] for underlining text.
		$patterns[] = '#\[u\](.*?)\[/u\]#si';
		$replacements[] = '<span style="text-decoration: underline">\\1</span>';

		# [i] and [/i] for italicizing text.
		$patterns[] = '#\[i\](.*?)\[/i\]#si';
		$replacements[] = '<span style="font-style: italic">\\1</span>';

		# align
		$patterns[] = '#\[align=(left|right|center|justify)\](.*?)\[/align\]#si';
		$replacements[] = '<div style="text-align:\\1">\\2</div>';

		# [google]search string[/google]
		$patterns[] = "#\[search=google\](.*?)\[/search\]#ise";
		$replacements[] = "'<form action=\"http://google.com/search\" method=\"get\"><input type=\"text\" name=\"q\" value=\"'.trim('\\1').'\" /><input type=\"submit\" value=\"Search Google\" /></form>'";
		$patterns[] = "#\[search\](.*?)\[/search\]#ise";
		$replacements[] = "'<form action=\"search.html\" method=\"post\"><input type=\"text\" name=\"search\" value=\"'.trim('\\1').'\" /><input type=\"submit\" value=\"Search\" /></form>'";
//		$replacements[] = "'<a href=\"http://google.com/search?q='.urlencode(trim('\\1')).'\" target=\"_blank\" class=\"postlink\" rel=\"nofollow\">\\1</a>'";

		# [url] local
		$patterns[] = "#\[url\]([\w]+(\.html|\.php|/)[^ \[\"\n\r\t<]*?)\[/url\]#ise";
		$replacements[] = "'<a href=\"\\1\" title=\"\\1\" class=\"postlink\">'.shrink_url('\\1').'</a>'";
		$patterns[] = "#\[url=([\w]+(\.html|\.php|/)[^ \[\"\n\r\t<]*?)\](.*?)\[/url\]#is";
		$replacements[] = "<a href=\"\\1\" title=\"\\1\" class=\"postlink\">\\3</a>";

		# [url]xxxx://www.cpgnuke.com[/url]
		$patterns[] = "#\[url\]([\w]+?://[^ \[\"\n\r\t<]*?)\[/url\]#ise";
		$replacements[] = "'<a href=\"\\1\" target=\"_blank\" title=\"\\1\" class=\"postlink\" rel=\"nofollow\">'.shrink_url('\\1').'</a>'";
		# [url]www.cpgnuke.com[/url] (no xxxx:// prefix).
		$patterns[] = "#\[url\]((www|ftp)\.[^ \[\"\n\r\t<]*?)\[/url\]#ise";
		$replacements[] = "'<a href=\"http://\\1\" target=\"_blank\" title=\"\\1\" class=\"postlink\" rel=\"nofollow\">'.shrink_url('\\1').'</a>'";
		# [url=www.cpgnuke.com]cpgnuke[/url] (no xxxx:// prefix).
		$patterns[] = "#\[url=((www|ftp)\.[^ \"\n\r\t<]*?)\](.*?)\[/url\]#is";
		$replacements[] = "<a href=\"http://\\1\" target=\"_blank\" title=\"\\1\" class=\"postlink\" rel=\"nofollow\">\\3</a>";
		# [url=xxxx://www.cpgnuke.com]cpgnuke[/url]
		$patterns[] = "#\[url=([\w]+://[^ (\"\n\r\t<]*?)\](.*?)\[/url\]#is";
		$replacements[] = "<a href=\"\\1\" target=\"_blank\" title=\"\\1\" class=\"postlink\" rel=\"nofollow\">\\2</a>";

		# [email]user@domain.tld[/email] code..
		$patterns[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
		$replacements[] = "<a href=\"mailto:\\1\">\\1</a>";

		if ($allowed) {
			# [hr]
			$patterns[] = "#\[hr\]#si";
			$replacements[] = '<hr />';

			# marquee
			$patterns[] = "#\[marq=(left|right|up|down)\](.*?)\[/marq\]#si";
			$replacements[] = '<marquee direction="\\1" scrolldelay="60" scrollamount="1" onmouseover="this.stop()" onmouseout="this.start()">\\2</marquee>';

			# [img]image_url_here[/img] code..
			$patterns[] = "#\[img\]([\w]+(://|\.|/)[^ \?&=(\"\n\r\t<]*?)\[/img\]#si";
			$replacements[] = "<img src=\"\\1\" style=\"border:0;\" alt=\"\" />";

			# [flash width= height= loop= ] and [/flash] code..
			$patterns[] = "#\[flash width=([0-6]?[0-9]?[0-9]) height=([0-4]?[0-9]?[0-9])\]((ht|f)tp://)([^ \?&=\"\n\r\t<]*?(\.(swf|fla)))\[/flash\]#si";
			$replacements[] = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" width="\\1" height="\\2">
	<param name="movie" value="\\3\\5" />
	<param name="quality" value="high" />
	<param name="scale" value="noborder" />
	<param name="wmode" value="transparent" />
	<param name="bgcolor" value="#000000" />
  <embed src="\\3\\5" quality="high" scale="noborder" wmode="transparent" bgcolor="#000000" width="\\1" height="\\2" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash">
</embed></object>';

			# [video width= height= loop= ] and [/video] code..
			$patterns[] = "#\[video width=([0-6]?[0-9]?[0-9]) height=([0-4]?[0-9]?[0-9])\]([\w]+?://[^ \?&=\"\n\r\t<]*?(\.(avi|mpg|mpeg|wmv)))\[/video\]#si";
			$replacements[] = '<embed src="\\3" width=\\1 height=\\2></embed>';
		}

		$text = preg_replace($patterns, $replacements, $text);

		# Fix linebreaks on important items
		$text = preg_replace("/<br>/si", "<br \/>", $text);
		$text = preg_replace("/<ul><br \/>/si", "<ul>", $text);
		$text = preg_replace("/<\/ul><br \/>/si", "</ul>", $text);
		$text = preg_replace("/<\/ol><br \/>/si", "</ol>", $text);
		$text = preg_replace("/<\/table><br \/>/si", "</table>", $text);
		$text = preg_replace("/<\/div><br \/>/si", "</div>", $text);
		$text = preg_replace("/<br \/><table/si", "<table", $text);

		# Remove our padding from the string..
		return trim($text);
	}

	function split_bbcodes($text)
	{
		$curr_pos = 0;
		$str_len = strlen($text);
		$text_parts = array();
		while ($curr_pos < $str_len) {
			# Find bbcode start tag, if not found end the loop.
			$curr_pos = strpos($text, '[', $curr_pos);
			if ($curr_pos === false) { break; }
			$end = strpos($text, ']', $curr_pos);
			if ($end === false) { break; }

			$code_start = substr($text, $curr_pos, $end-$curr_pos+1);
			$code = strtolower(preg_replace('/\[([a-z]+).*]/i', '\\1', $code_start));
			$code_len = strlen($code);

			$end_pos = empty($code) ? false : $end;
			$depth = 0;
			$sub = false;
			while ($end_pos) {
				# Find bbcode end tag, if not found end the loop.
				$end_pos = strpos($text, '[', $end_pos);
				if ($end_pos === false) { break; }
				$end = strpos($text, ']', $end_pos);
				if ($end === false) { break; }
				$code_end = strtolower(substr($text, $end_pos, $code_len+2));
				if ($code_end == "[/$code") {
					if ($depth > 0) {
						$depth--;
						$end_pos++;
						$sub = true;
					} else {
						if ($curr_pos > 0) {
							$text_parts[] = array('text' => substr($text, 0, $curr_pos), 'code' => false, 'subc' => false);
						}
						$text_parts[] = array(
							'text' => substr($text, $curr_pos, $end-$curr_pos+1),
							'code' => $code,
							'subc' => $sub);
						$text = substr($text, $end+1);
						$str_len = strlen($text);
						$curr_pos = 0;
						break;
					}
				} else {
					if (substr($code_end, 0, -1) == "[$code") { $depth++; }
					$end_pos++;
				}
			}
			$curr_pos++;
		}
		if ($str_len > 0) { $text_parts[] = array('text' => $text, 'code' => false, 'subc' => false); }
		return $text_parts;
	}

	# split the bbcodes and use nl2br on everything except [php]
	function split_on_bbcodes($text, $allowed=0, $allow_html=false)
	{
		global $bb_codes;
		# Split all bbcodes.
		$text_parts = BBCode::split_bbcodes($text);
		# Merge all bbcodes and do special actions depending on the type of code.
		$text = '';
		while ($part = array_shift($text_parts)) {
			if ($part['code'] == 'php') {
				# [PHP]
				$text .= ($allowed) ? BBCode::decode_php($part['text']) : nl2br(htmlspecialchars($part['text']));
			} elseif ($part['code'] == 'code') {
				# [CODE]
				if (!$allowed && ereg('<', $part['text'])) {
					$part['text'] = nl2br(htmlspecialchars($part['text']));
				}
				$text .= $allowed ? BBCode::decode_code($part['text']) : $part['text'];
			} elseif ($part['code'] == 'quote') {
				# [QUOTE] and [QUOTE=""]
				if ($part['text'][6] == ']') {
					$text .= $bb_codes['quote'].BBCode::split_on_bbcodes(substr($part['text'], 7, -8), $allowed, $allow_html).$bb_codes['quote_close'];
				} else {
					$part['text'] = preg_replace('/\[quote=["]*(.*?)["]*\]/si', $bb_codes['quote_name'], BBCode::split_on_bbcodes(substr($part['text'], 0, -8), $allowed, $allow_html), 1);
					$text .= $part['text'].$bb_codes['quote_close'];
				}
			} elseif ($part['subc']) {
				$tmptext = '['.BBCode::split_on_bbcodes(substr($part['text'], 1, -1)).']';
				$text .= ($part['code'] == 'list') ? BBCode::decode_list($tmptext) : $tmptext;
				unset($tmptext);
			} else {
				if ($allow_html) {
					$tmptext = (!ereg('<', $part['text']) ? nl2br($part['text']) : $part['text']);
				} else {
					$tmptext = nl2br(BBCode::encode_html($part['text']));
				}
				$text .= ($part['code'] == 'list') ? BBCode::decode_list($tmptext) : $tmptext;
				unset($tmptext);
			}
		}
		return $text;
	}

	function decode_code($text)
	{
		global $bb_codes;
		$text = substr($text, 6, -7);
		$code_entities_match   = array('#<#',  '#>#',  '#"#',	'#:#',   '#\[#',  '#\]#',  '#\(#',  '#\)#',  '#\{#',   '#\}#');
		$code_entities_replace = array('&lt;', '&gt;', '&quot;', '&#58;', '&#91;', '&#93;', '&#40;', '&#41;', '&#123;', '&#125;');
		$text = preg_replace($code_entities_match, $code_entities_replace, $text);
		return $bb_codes['code_start']."<pre>$text</pre>".$bb_codes['code_end'];
	}

	function decode_php($text)
	{
		global $bb_codes;
		$text = str_replace("\r\n", "\n", substr($text, 5, -6)); # Windows
		$text = str_replace("\r", "\n", $text);   # Mac
		$text = str_replace("\t", '/*t*/', $text); # Temporary tab fix
		$text = htmlunprepare($text, true);
		$added = FALSE;
		if (preg_match('/^<\?.*/', $text) <= 0) {
			$text = "<?php\n$text";
			$added = TRUE;
		}
		if (PHPVERS < 42) {
			ob_start();
			highlight_string($text);
			$text = ob_get_contents();
			ob_end_clean();
		} else {
			$text = highlight_string($text, TRUE);
		}
		if (PHPVERS < 50) {
			$text = preg_replace('/<font color="(.*?)">/si', '<span style="color: \\1;">', $text);
			$text = str_replace('</font>', '</span>', $text);
		}
		if ($added == TRUE) {
			if (PHPVERS < 50) {
				$text = preg_replace('/^(.*)\n.*?<\/span>(.*)php<br \/>/i', "\\1\n\\2?php<br />", $text, 1);
			}
			$text = preg_replace('/^(.*)\n.*php<br \/><\/span>/i', "\\1\n", $text, 1);
			$text = preg_replace('/^(.*)\n(.*)>.*php<br \/>/i', "\\1\n\\2>", $text, 1);
		}
		$text = str_replace('[', '&#91;', $text);
		$text = str_replace("\n", '', $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('/*t*/', "\t", $text);
		$text = preg_replace('#<span style="color: \#[A-F0-9]{6}">([\t]+)</span>#', '\\1', $text);
		return $bb_codes['php_start']."<pre>$text</pre>".$bb_codes['php_end'];
	}

	function decode_list($text)
	{
		// &(?![a-z]{2,6};|#[0-9]{1,4};)
		$items = explode('[*]', $text);
		$text = array_shift($items).'<li>';
		$text .= implode('</li><li>', $items);
		if (count($items) > 1) $text = str_replace('[/list', '</li>[/list', $text);
		$text = preg_replace("#<br />[\r\n]+</li>#", "</li>\n", $text);
		unset($items);
		# [list] and [list=x] for (un)ordered lists.
		# unordered lists
		$text = preg_replace('#\[list\]#i', '<ul>', $text);
		$text = preg_replace('#\[/list:u\]#i', '</ul>', $text);
		$text = preg_replace('#\[/list\]#i', '</ul>', $text);
		# Ordered lists
		$text = preg_replace('#\[list=([ai1])\]#i', '<ol type="\\1">', $text);
		$text = preg_replace('#\[/list:o\]#i', '</ol>', $text);

		$text = preg_replace('#(<[ou]l.*?>)<br />#s', '\\1', $text);
		return $text;
	}

}
