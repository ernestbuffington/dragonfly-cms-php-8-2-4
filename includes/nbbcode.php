<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/nbbcode.php,v $
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
	return $array[$var] ?? $var;
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

			foreach ($rowset as $smile_url => $data) {
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
		if (is_countable($smilies) ? count($smilies) : 0) {
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
		for ($i = 0; $i < (is_countable($smilies) ? count($smilies) : 0); $i++) {
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
	$ret = preg_replace_callback('#(^|[
 ])([\w]+?://[\w]+[^ "

	<]*)#is', fn($matches) => $matches[1] . shrink_url($matches[2]) . '</a>', $ret);
	$ret = preg_replace_callback('#(^|[
 ])((www|ftp)\.[^ "	

<]*)#is', fn($matches) => $matches[1] . shrink_url($matches[2]) . '</a>', $ret);
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
		$allowed_html_tags = preg_split('#,#m', $board_config['allow_html_tags']);
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

class BBCode 
{
	public static function encode_html($text) 
	{
		return (preg_match('/</', $text)) ? stripslashes(check_html($text, '')) : $text;
	}

	public static function encode($text)
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

	public static function decode($text, $allowed=0, $allow_html=false)
	{
		global $bb_codes;
		# First: If there isn't a "[" and a "]" in the message, don't bother.
		if (!(strpos($text, '[') !== false && strpos($text, ']'))) {
			return ($allow_html ? (preg_match('/</', $text) ? $text : nl2br($text)) : nl2br(strip_tags($text)));
		}

		//$bb_codes['code_start'] 	= '<div class="codebox"><p>Code: [ <a href="#" class="code_selection">Select all</a> ]</p><pre style="white-space: pre-wrap"><code>';
		//$bb_codes['code_end']   	= '</code></pre></div>';

		//$bb_codes['php_start'] 		= '<div class="codebox phpcodebox"><p>PHP:&nbsp;&nbsp;[ <a href="#" class="code_selection code_select">Select all</a> ]</p><pre style="white-space: normal">';
		//$bb_codes['php_end']   		= '</pre></div>';

		//$bb_codes['quote'] 			= '<div class="notepaper"><figure class="quote"><blockquote class="curly-quotes"><figcaption class="quote-by">&mdash; Quote</figcaption>';
		//$bb_codes['quote_close']	= '</blockquote></figure></div>';

		# pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
		$text = BBCode::split_on_bbcodes($text, $allowed, $allow_html);

		# Patterns and replacements for URL, email tags etc.
		$patterns = $replacements = array();

		# [b] and [/b] for bolding text.
		$text = preg_replace_callback("(\[b\](.*?)\[/b\])is", function($m) { return '<span style="font-weight: bold">'.$m[1].'</span>'; }, $text);

		# [i] and [/i] for italicizing text.
		$text = preg_replace_callback("(\[i\](.*?)\[/i\])is", function($m) { return '<span style="font-style: italic;">'.$m[1].'</span>'; }, $text);

		# [u] and [/u] for underlining text.
		$text = preg_replace_callback("(\[u\](.*?)\[/u\])is", function($m) { return '<span style="text-decoration: underline">'.$m[1].'</span>'; }, $text);

		# [s] and [/s] for striking through text.
		$text = preg_replace_callback("(\[s\](.*?)\[/s\])is", function($m) { return '<span style="text-decoration: line-through;">'.$m[1].'</span>'; }, $text);

		# colors
		$text = preg_replace_callback("(\[color=(\#[0-9A-F]{6}|[a-z\-]+)\](.*?)\[/color\])is", function($m) { return '<span style="color:'.$m[1].'">'.$m[2].'</span>'; }, $text); 

		# align
		$text = preg_replace_callback("(\[align=(left|right|center|justify)\](.*?)\[/align\])is", function($m) { return '<div style="text-align:'.$m[1].';">'.$m[2].'</div>'; }, $text);

		# fonts
		$text = preg_replace_callback("(\[font=(.*?)\](.*?)\[/font\])is", function($m) { return '<span style="font-family: '.$m[1].'">'.$m[2].'</span>'; }, $text);

		# highlight
		$text = preg_replace_callback("(\[highlight=(\#[0-9A-F]{6}|[a-z\-]+)\](.*?)\[/highlight\])is", function($m) { return '<span style="background-color: '.$m[1].'">'.$m[2].'</span>'; }, $text);

		# font size
		$text = preg_replace_callback("(\[size=([1-2]?[0-9])\](.*?)\[/size\])is", function($m) { return '<span style="font-size: '.$m[1].'px; line-height: normal;">'.$m[2].'</span>'; }, $text);

		# marguee
		$text = preg_replace_callback("(\[marq=(left|right|up|down)\](.*?)\[/marq\])is", function($m) { return '<marquee direction="'.$m[1].'" scrolldelay="60" 
		scrollamount="1" onmouseover="this.stop()" onmouseout="this.start()">'.$m[2].'</marquee>'; }, $text);

		# flash


		# Horizontal Rule
		$text = preg_replace_callback("(\[hr\])is", function($m) { return '<hr />'; }, $text);

		# [url] local
		$text = preg_replace_callback("(\[url\]([\w]+(\.html|\.php|/)[^ \[\"\n\r\t<]*?)\[/url\])is", function($m) { return '<a href="'.$m[1].'" title="'.$m[1].'">'.shrink_url($m[1]).'</a>'; }, $text);
		$text = preg_replace_callback("(\[url=([\w]+(\.html|\.php|/)[^ \[\"\n\r\t<]*?)\](.*?)\[/url\])is", function($m) { return '<a href="'.$m[1].'" title="'.$m[1].'">'.$m[3].'</a>'; }, $text);

        # [url]xxxx://www.cpgnuke.com[/url]
		// $text = preg_replace_callback("(\[url\]([\w]+?://[^ \[\"\n\r\t<]*?)\[/url\])is", function($m) { return '<a href="'.$m[1].'" target="_blank" title="'.$m[1].'">'.shrink_url($m[1]).'</a>'; }, $text);
		$text = preg_replace_callback("#\[url\]((?!javascript)[a-z]+?://)([^\r\n\"<]+?)\[/url\]#si", function($m) { return '<a href="'. $m[1] . $m[2] .'" target="_blank" title="'.$m[1] . $m[2].'">'.$m[1] . $m[2].'</a>'; }, $text);
		
        # [url]www.cpgnuke.com[/url] (no xxxx:// prefix).
		$text = preg_replace_callback("(\[url\]((www|ftp)\.[^ \[\"\n\r\t<]*?)\[/url\])is", function($m) { return '<a href="http://'.$m[1].'" target="_blank" title="'.$m[1].'">'.$m[1].'</a>'; }, $text);
		
        # [url=www.cpgnuke.com]cpgnuke[/url] (no xxxx:// prefix).
		$text = preg_replace_callback("(\[url=((www|ftp)\.[^ \"\n\r\t<]*?)\](.*?)\[/url\])is", function($m) { return '<a href="http://'.$m[1].'" target="_blank" title="'.$m[1].'">'.$m[3].'</a>'; }, $text);
	
        # [url=xxxx://www.cpgnuke.com]cpgnuke[/url]	
		$text = preg_replace_callback("(\[url=(.*?)\](.*?)\[/url\])is", function($m) { return '<a href="'.$m[1].'" target="_blank" title="'.$m[1].'">'.$m[2].'</a>'; }, $text);

		# [spoil]Spoiler[/spoil] code..
    	// $text = preg_replace_callback("(\[spoil\](.*?)\[/spoil\])is", function($m) { return '[spoil:'._BBCODE_UNIQUE_ID.']'.$m[1].'[/spoil:'._BBCODE_UNIQUE_ID.']'; }, $text);

		# Images
		$text = preg_replace_callback("(\[img\](.*?)\[/img\])is", function($m) { return '<img class="reimg" onload="reimg(this);" onerror="reimg(this);" src="'.$m[1].'" border="0" alt="" />'; }, $text);

		# Parse YouTube videos
		$text = preg_replace_callback("#\[video=(.*?)\](.*?)\[/video\]#is", 'BBCode::evo_parse_video_callback', $text);

		# tag/mention a member
    	$text = preg_replace_callback("(\[tag\](.*?)\[/tag\])is", 'BBCode::evo_mention_callback', $text);

    	# Spoiler tag
    	$text = preg_replace_callback("(\[spoil\](.*?)\[/spoil\])is", 'BBCode::evo_spoil_callback', $text);

		/**
		 *  The BBCODES below are for SCEditor support
		 */
		# SCeditor Center Alignment
		$text = preg_replace_callback("(\[center\](.*?)\[/center\])is", function($m) { return '<div style="text-align:center;">'.$m[1].'</div>'; }, $text);
		
		# SCeditor Left Alignment
		$text = preg_replace_callback("(\[left\](.*?)\[/left\])is", function($m) { return '<div style="text-align:left;">'.$m[1].'</div>'; }, $text);
		
		# SCeditor Right Alignment
		$text = preg_replace_callback("(\[right\](.*?)\[/right\])is", function($m) { return '<div style="text-align:right;">'.$m[1].'</div>'; }, $text);
		
		# SCeditor Justify Alignment
		$text = preg_replace_callback("(\[justify\](.*?)\[/justify\])is", function($m) { return '<div style="text-align:justify;">'.$m[1].'</div>'; }, $text);

        # [flash width= height= loop= ] and [/flash] code..
        $patterns[] = "#\[flash width=([0-6]?[0-9]?[0-9]) height=([0-4]?[0-9]?[0-9])\]((ht|f)tp://)([^ \?&=\"\n\r\t<]*?(\.(swf|fla)))\[/flash\]#si";
        $replacements[] = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" width="\\1" height="\\2">
    <param name="movie" value="\\3\\5">
    <param name="quality" value="high">
    <param name="scale" value="noborder">
    <param name="wmode" value="transparent">
    <param name="bgcolor" value="#000000">
  <embed src="\\3\\5" quality="high" scale="noborder" wmode="transparent" bgcolor="#000000" width="\\1" height="\\2" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash">
</embed></object>';

        # [video width= height= loop= ] and [/video] code..
        $patterns[] = "#\[video width=([0-6]?[0-9]?[0-9]) height=([0-4]?[0-9]?[0-9])\]([\w]+?://[^ \?&=\"\n\r\t<]*?(\.(avi|mpg|mpeg|wmv)))\[/video\]#si";
        $replacements[] = '<embed src="\\3" width=\\1 height=\\2></embed>';
        
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

	public static function evo_spoil_callback( $matches )
	{
		return BBCode::evo_spoil( $matches[1] );
	}

	public static function evo_spoil( $hidden_content )
	{
		$template  = '
		<style>
		.spoiler-container {
			display: block;
		}

		.btn {
		    display: inline-block;
		    font-weight: 400;
		    color: #212529;
		    text-align: center;
		    vertical-align: middle;
		    user-select: none;
		    background-color: transparent;
		    border: 1px solid transparent;
		    padding: .375rem .75rem;
		    font-size: 1rem;
		    line-height: 1.5;
		    border-radius: .25rem;
		    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
		    cursor: pointer;
		    outline: none;
		    margin-bottom: 10px;
		}

		.btn:focus {
			outline: none !important;
		}

		.btn-mod.btn-border {
		    color: #ccc;
		    border: 1px solid #414141;
		    background: #181818;
		}

		.btn-mod.btn-border:hover, .btn-mod.btn-border:focus {
		    color: #fff;
		    border-color: #ccc;
		    background: #313131;
		}

		#spoiler-contents {
			display: none;
			margin: 0 20px 10px 20px;
		}
		</style>
		';

		// $template  = addCSStoHead( 'includes/css/bbcode.css' );
		$template .= '<div class="spoiler-container">';
		$template .= '	Spoiler: <button class="btn btn-mod btn-border" type="button" id="reveal-spoiler" name="spoiler">Show</button>';
		$template .= '	<div id="spoiler-contents">Phones Broken</div>';
		$template .= '</div>';
		$template .= '<script>
			var hidden_content = document.getElementById("spoiler-contents");
			document.getElementById("reveal-spoiler").addEventListener("click", function (event) 
			{
				if (window.getComputedStyle(hidden_content).display === "none") 
				{
					hidden_content.style.display = "block";
				}
				else
				{
					hidden_content.style.display = "none";
				}
			});
		</script>';
		return $template;
	}


	
	# Parse the YouTube video
	public static function evo_parse_video_callback( $matches ) 
	{
		return BBCode::evo_parse_video( $matches[1], $matches[2] );
	}

	public static function evo_parse_video( $video, $url )
	{
		global $bbcode_tpl, $board_config, $nukeurl;

		$stripped_url = preg_replace("(^https?://)", "", $nukeurl );

		if(empty($video) || empty($url))
		{
			 return "[video={$video}]{$url}[/video]";
		}

		$parsed_url = parse_url(urldecode($url));

		$winchester = '';
		if($parsed_url == false)
		{
			return "[video={$video}]{$url}[/video]";
		}

		$fragments = array();
		if($parsed_url['fragment'])
		{
			$fragments = explode("&", $parsed_url['fragment']);
		}

		$queries = explode("&", $parsed_url['query']);

		$input = array();
		foreach($queries as $query)
		{
			list($key, $value) = explode("=", $query);
			$key = str_replace("amp;", "", $key);
			$input[$key] = $value;
		}

		$path = explode('/', $parsed_url['path']);
		switch($video):

			/* ----- youtube video embed ----- */
			case "youtube":
				if($fragments[0])
					# http://www.youtube.com/watch#!v=fds123
					$id = str_replace('!v=', '', $fragments[0]); 
				elseif($input['v'])
					# http://www.youtube.com/watch?v=fds123
					$id = $input['v']; 
				else
					# http://www.youtu.be/fds123
					$id = $path[1];

				$video_replace = '<iframe style="max-width: 100%" id="ytplayer-'.$id.'" width="'.$board_config['youtube_width'].'" height="'.$board_config['youtube_height'].'" src="//www.youtube.com/embed/'.$id.'?rel=0&amp;vq=hd1080" frameborder="0" allowfullscreen=""></iframe><br />[<a href="https://www.youtube.com/watch?v='.$id.'" target="_blank">'._WATCH_YOUTUBE.'</a>]';
				break;

			/* ----- twitch video embed ----- */
			case "twitch":

				// if(preg_match("/clip/", $url, $matches)):
				if ( preg_match('/(clips?|clip)/', $url, $matches) ):

					$clips = explode('/', $url);
					// $id = 'embed?clip='.$clips[5].'';
					if ( $matches[1] == 'clip' ):
						$id = 'embed?clip='.$clips[5].'&parent='.$stripped_url.'&autoplay=false&tt_medium=clips_embed';
					else:
						$id = 'embed?clip='.$clips[3].'&parent='.$stripped_url.'&autoplay=false&tt_medium=clips_embed';
					endif;
					$player = 'clips';
				
				else:
				
					if(count($path) >= 3 && $path[1] == 'videos')
					{
						// Direct video embed with URL like: https://www.twitch.tv/videos/179723472
						$id = '?video=v'.$path[2].'&parent='.$stripped_url;
					}
					elseif(count($path) >= 4 && $path[2] == 'v')
					{
						// Direct video embed with URL like: https://www.twitch.tv/waypoint/v/179723472
						$id = '?video=v'.$path[3].'&parent='.$stripped_url;
					}
					elseif(count($path) >= 2)
					{
						// Channel (livestream) embed with URL like: https://twitch.tv/waypoint
						$id = '?channel='.$path[1].'&parent='.$stripped_url;
					}

					$time = explode("?", $url);

					$player = 'player';
				
				endif;
				$video_replace = '<iframe style="max-width: 100%" src="https://'.$player.'.twitch.tv/'.$id.'&amp;autoplay=false" frameborder="0" scrolling="no" height="'.$board_config['twitch_height'].'" width="'.$board_config['twitch_width'].'" allowfullscreen=""></iframe>';                
				// $video_replace = '<pre>'.var_export( $clips, true ).'</pre>'; 
				break;

			default:
				return "[video={$video}]{$url}[/video]";
		
		endswitch;

		if(empty($id))
		{
			return "[video={$video}]{$url}[/video]1";
		}
		return $video_replace;
	}

	public static function evo_mention_callback( $matches )
	{
		return BBCode::evo_mention( $matches[1] );
	}

	public static function evo_mention( $user )
	{
		global $db, $customlang;
		
		// modules.php?name=Private_Messages&mode=post&pm_uname=Lonestar
		// $row = $db->sql_ufetchrow("SELECT `user_id`, `username` FROM `".USERS_TABLE."` WHERE `username` = '".$user."'");
		return '<a href="modules.php?name=Private_Messages&mode=post&pm_uname='.$user.'" target="_blank" alt="'.$customlang['global']['send_pm'].'" title="'.$customlang['global']['send_pm'].'">'.$user.'</a>';
	}

	public static function split_bbcodes($text)
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
	public static function split_on_bbcodes($text, $allowed=0, $allow_html=false)
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
				if (!$allowed && preg_match('/</', $part['text'])) {
					$part['text'] = nl2br(htmlspecialchars($part['text']));
				}
				$text .= $allowed ? BBCode::decode_code($part['text']) : $part['text'];
			} elseif ($part['code'] == 'quote') {
				# [QUOTE] and [QUOTE=""]
				if ($part['text'][6] == ']') {
					$text .= $bb_codes['quote'].BBCode::split_on_bbcodes(substr($part['text'], 7, -8), $allowed, $allow_html).$bb_codes['quote_close'];
				} else {
					$part['text'] = preg_replace('/\[quote="(.*?)"\]/si', $bb_codes['quote_name'], BBCode::split_on_bbcodes(substr($part['text'], 0, -8), $allowed, $allow_html), 1);
					$text .= $part['text'].$bb_codes['quote_close'];
				}
			} elseif ($part['subc']) {
				$tmptext = '['.BBCode::split_on_bbcodes(substr($part['text'], 1, -1)).']';
				$text .= ($part['code'] == 'list') ? BBCode::decode_list($tmptext) : $tmptext;
				unset($tmptext);
			} else {
				if ($allow_html) {
					$tmptext = (!preg_match('/</', $part['text']) ? nl2br($part['text']) : $part['text']);
				} else {
					$tmptext = nl2br(BBCode::encode_html($part['text']));
				}
				$text .= ($part['code'] == 'list') ? BBCode::decode_list($tmptext) : $tmptext;
				unset($tmptext);
			}
		}
		return $text;
	}

	public static function decode_code($text)
	{
		global $bb_codes;
		$text = substr($text, 6, -7);
		$code_entities_match   = array('#<#',  '#>#',  '#"#',    '#:#',   '#\[#',  '#\]#',  '#\(#',  '#\)#',  '#\{#',   '#\}#');
		$code_entities_replace = array('&lt;', '&gt;', '&quot;', '&#58;', '&#91;', '&#93;', '&#40;', '&#41;', '&#123;', '&#125;');
		$text = preg_replace($code_entities_match, $code_entities_replace, $text);
		# Replace 2 spaces with "&nbsp; " so non-tabbed code indents without making huge long lines.
		$text = str_replace('  ', '&nbsp; ', $text);
		# now Replace 2 spaces with ' &nbsp;' to catch odd #s of spaces.
		$text = str_replace('  ', ' &nbsp;', $text);
		# Replace tabs with "&nbsp; &nbsp;" so tabbed code indents sorta right without making huge long lines.
		$text = str_replace("\t", '&nbsp; &nbsp;', $text);
		# now Replace space occurring at the beginning of a line
		$text = preg_replace('/^ {1}/m', '&nbsp;', $text);
		// return $bb_codes['code_start'].nl2br($text).$bb_codes['code_end'];
		return $bb_codes['code_start'].$text.$bb_codes['code_end'];
	}

	public static function decode_php($text)
	{
		global $bb_codes;
		$text = substr($text, 5, -6);
		$text = str_replace("\r\n", "\n", $text);
		$text = htmlunprepare($text, true);
		$added = FALSE;
		if (preg_match('/^<\?.*/', $text) <= 0) {
			$text = "<?php\n$text\n";
			$added = TRUE;
		}

		// if (PHPVERS < '4.2') {
		// 	ob_start();
		// 	highlight_string($text);
		// 	$text = ob_get_contents();
		// 	ob_end_clean();
		// } else {
		// 	$text = highlight_string($text, TRUE);
		// }

		$text = highlight_string($text, TRUE);

		if (PHPVERS < '5.0') {
			$text = preg_replace('/<font color="(.*?)">/si', '<span style="color: \\1;">', $text);
			$text = str_replace('</font>', '</span>', $text);
		}
		if ($added == TRUE) {
			if (PHPVERS < '5.0') {
				$text = preg_replace('/^(.*)\n.*?<\/span>(.*)php<br \/>/i', "\\1\n\\2?php<br />", $text, 1);
			}
			$text = preg_replace('/^(.*)\n.*php<br \/><\/span>/i', "\\1\n", $text, 1);
			$text = preg_replace('/^(.*)\n(.*)>.*php<br \/>/i', "\\1\n\\2>", $text, 1);
		}
		$text = str_replace('[', '&#91;', $text);
		return $bb_codes['php_start'].trim($text).$bb_codes['php_end'];
	}

	public static function decode_list($text)
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
		$text = preg_replace('#\[list\]#i', '<ul class="mycode_list">', $text);
		$text = preg_replace('#\[/list:u\]#i', '</ul>', $text);
		$text = preg_replace('#\[/list\]#i', '</ul>', $text);
		# Ordered lists
		$text = preg_replace('#\[list=([ai1])\]#i', '<ol class="mycode_list" type="\\1">', $text);
		$text = preg_replace('#\[/list:o\]#i', '</ol>', $text);

		$text = preg_replace('#(<[ou]l.*?>)<br />#s', '\\1', $text);
		return $text;
	}
}
