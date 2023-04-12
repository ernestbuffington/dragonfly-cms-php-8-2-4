<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Our_Sponsors/index.php,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/09/06 22:54:11 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= 'Our Sponsors';

function show_table_datahead($caption) {
	global $bgcolor3;
	OpenTable();
	echo '<table border="0" col="6">
	<caption>'.$caption.'</caption><br />
	<tr><td bgcolor="'.$bgcolor3.'" align="center"><strong>'.BANNER_ID.'</strong></td>
	<td bgcolor="'.$bgcolor3.'" align="center"><strong>'._IMAGE.'</strong></td>
	<td bgcolor="'.$bgcolor3.'" align="center"><strong>'._IMPRESSIONS.'</strong></td>
	<td bgcolor="'.$bgcolor3.'" align="center"><strong>'._IMPLEFT.'</strong></td>
	<td bgcolor="'.$bgcolor3.'" align="center"><strong>'._CLICKS.'</strong></td>
	<td bgcolor="'.$bgcolor3.'" align="center"><strong>'._CLICKSPERCENT.'</strong></tr>';
}
function show_table_data($row, $bgcolor) {
	global $bgcolor2;
	foreach($row as $var => $value) {
		$$var = $value;
	}
	echo '</tr><td'.$bgcolor.' align="center">'.$bid.'</td>';
	$this_size = getimagesize($imageurl);
	$this_width = $this_size[0] *.5;
	$this_height = $this_size[1] *.5;
	echo '<td bgcolor="'.($text_bg!=''? "#".$text_bg : $bgcolor2).'" align="center">';
	if ($textban) {
		echo '<a href="'.$clickurl.'">'.$text_title.'</a>';
	} else {
		echo '<a href="'.$clickurl.'"><img src="'.$imageurl.'" width="'.$this_width.'" height="'.$this_height.'" alt="" />';
	}
	echo '</td>
	<td'.$bgcolor.' align="center"><strong>'.$impmade.'</strong></td>';

	if ($imptotal == 0) {
		$left = _UNLIMITED;
	} else {
		$left = $imptotal-$impmade;
	}
	
	echo '<td'.$bgcolor.' align="center"><strong>'.$left.'</strong></td>
	<td'.$bgcolor.' align="center"><strong>'.$clicks.'</strong></td>';
	if ($impmade == 0) {
		$percent = 0;
	} else {
		$percent = substr(100 * $clicks / $impmade, 0, 5);
	}
	echo '<td'.$bgcolor.' align="center"><strong>'.$percent.'</strong></td></tr>';
}
function display_all_banners($row) {
	foreach($row as $var => $value) {
		$$var = $value;
	}
	echo '<table class="head"><tr><td>';
	if ($textban) {
		echo '<table align="center" width="'.$text_width.'" align="center" border="0" height="'.$text_height.'" bgcolor="#'.$text_bg.'"><tr><td align="center"><a href="'.$clickurl.'" style="color:#'.$text_clr.'">'.$text_title.'</a></td></tr></table>';
	} else {
		$this_size = getimagesize($imageurl);
		$this_width = $this_size[0];
		$this_height = $this_size[1];
		echo '<p align="center"><a	href="'.$clickurl.'"><img src="'.$imageurl.'" width="'.$this_width.'" height="'.$this_height.'" border="0" alt="" /></a></p>';
	}
	echo '</td></tr></table><br clear="all" />';
}
function show_table_datafoot() {
	echo '</table>';
	CloseTable();
	echo '<br />';
}
function count_banners($userid, &$count) {
	global $db, $prefix;
	$count = array(0=>0, 1=>0);
//	  $count = array_fill(0, 2, 0); // PHP >= 4.2.0
	$result = $db->sql_query("SELECT active, count(*) FROM ".$prefix."_banner WHERE cid='$userid' GROUP BY active");
	while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
		$count[$row[0]] = $row[1];
	}
	return $count[0]+$count[1];
}
function select_banners($usid, $act=0) {
	global $db, $prefix, $bgcolor3;
	show_table_datahead($act ? _ACTIVEBANNERS2: _INACTIVEBANNERS);
	$bgcolor = $bgcolor3;
	$result = $db->sql_uquery("SELECT * FROM ".$prefix."_banner WHERE cid='$usid' AND active='$act'");
	while ($row = $db->sql_fetchrow($result)) {
		$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
		show_table_data($row, $bgcolor);
	}
	show_table_datafoot();
}
function show_all_banners($act=0) {
	global $db, $prefix;
	$result = $db->sql_query("SELECT * FROM ".$prefix."_banner WHERE  active='$act'");
	if ($db->sql_numrows($result)) {
		echo '<br />';
		OpenTable();
		echo '<p align="center">'._ALL.' '._BANNERS.'</p><p align="center">';
		while ($row = $db->sql_fetchrow($result)) {
			display_all_banners($row);
		}
		echo '</p>';
		CloseTable();
	}
	$db->sql_freeresult($result);
}
function make_banner_form() {
	OpenTable();
	echo '<fieldset><legend><strong>'._ADDNEWBANNER.'</strong></legend><br />
	<form method="post" action="'.getlink('&amp;op=make_new').'" enctype="multipart/form-data" accept-charset="utf-8">
	<label for="type">'._TYPE.'</label>
	<select name="type">
	<option value="0">'._NORMAL.'</option>
	<option value="1">'._BLOCK.'</option></select>&nbsp;
	<select name="textban">
	<option value="0">'._IMAGE_BANNER.'</option>
	<option value="1">'._TEXT_BANNER.'</option>
	</select>
	<br /><br />
	<fieldset><legend><strong>'._IMAGE_BANNER.'</strong></legend>
	<table width="500">
	<tr>
	<td><label for="imageurl">'._IMAGEURL.'</label></td>
	<td><input type="text" name="imageurl" size="50" maxlength="255" /></td>
	</tr><tr>
	<td><label for="alttext">'._ALTERNATETEXT.'</label></td>
	<td><input type="text" name="alttext" size="50" maxlength="255" /></td>
	</tr>
	</table>
	</fieldset>
	<br />
	<fieldset><legend><strong>'._TEXT_BANNER.'</strong></legend>
	<table width="500">
	<tr>
	<td><label for="text_title">'._TEXT_TITLE.'</label></td>
	<td><textarea name="text_title" rows="2" cols="44" maxlength="144"></textarea></td>
	</tr><tr>
	<td><label for="text_width">'._TEXT_WIDTH.'</label></td>
	<td><input type="text" name="text_width" size="7" maxlength="3" /></td>
	</tr><tr>
	<td><label for="text_height">'._TEXT_HGT.'</label></td>
	<td><input type="text" name="text_height" size="7" maxlength="3" /></td>
	</tr><tr>
	<td><label for="alttext">'._TEXT_COLOR.'</label></td>
	<td><input type="text" name="text_clr" size="7" maxlength="6" /></td>
	</tr><tr>
	<td><label for="alttext">'._TEXT_BACKGROUND.'</label></td>
	<td><input type="text" name="text_bg" size="7" maxlength="6" /></td>
	</tr>
	</table>
	</fieldset><br />
	<table width="500">
	<tr>
	<td><label for="clickurl">'._CLICKURL.'</label></td>
	<td><input type="text" name="clickurl" size="50" maxlength="255" /></td>
	</tr><tr>
	<td><label for="imptotal">'._IMPRESSIONS_WANTED.'</label></td>
	<td><input type="text" name="imptotal" size="10" maxlength="12" /></td>
	</tr>
	</table>
	<center><input type="submit" value="'._SUBMIT.'" /></center>
	</form></fieldset>';
	CloseTable();
}

if ($user_id = is_user()) {
	if (isset($_GET['op']) && $_GET['op'] == 'make_new' && isset($_POST['type'])) {
		$type = intval($_POST['type']);
		$textban = intval($_POST['textban']);
		$imptotal = intval($_POST['imptotal']);
		$text_width = intval($_POST['text_width']);
		$text_height = intval($_POST['text_height']);
		$imageurl = Fix_Quotes($_POST['imageurl'], 1);
		$clickurl = Fix_Quotes($_POST['clickurl'], 1);
		$alttext = Fix_Quotes($_POST['alttext'], 1);
		$text_bg = Fix_Quotes($_POST['text_bg'], 1);
		$text_clr = Fix_Quotes($_POST['text_clr'], 1);
		$text_title = Fix_Quotes($_POST['text_title'], 1);
		$db->sql_query('INSERT INTO '.$prefix.'_banner (bid, cid, imptotal, impmade, clicks, imageurl, clickurl, alttext, date, dateend, type, active, textban, text_width, text_height, text_title, text_bg, text_clr) '.
				"VALUES (DEFAULT, '$user_id', '$imptotal', '0', '0', '$imageurl', '$clickurl', '$alttext', ".gmtime().", '0', '$type', '0', '$textban', '$text_width', '$text_height', '$text_title', '$text_bg','$text_clr')");
		url_redirect(getlink());
	}
	require_once('header.php');
	if (count_banners($user_id, $count) > 0) {
		if ($count[1] > 0) {
			select_banners($user_id, 1);
		}
		if ($count[0] > 0) {
			select_banners($user_id);
		}
	} else {
		OpenTable();
		echo _NO_BANNERS.' '.$userinfo['username'];
		CloseTable();
	}
	make_banner_form();
} else {
	require_once('header.php');
	OpenTable();
	echo '<h3 align="center">You must <a href="'.getlink('Your_Account&amp;file=register').'">register</a> before you can create advertisements!</h2>';
	CloseTable();
	show_all_banners(1);
}