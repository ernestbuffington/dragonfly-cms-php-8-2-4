<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Our_Sponsors/admin/index.inc,v $
  $Revision: 9.6 $
  $Author: nanocaiordo $
  $Date: 2006/09/23 15:00:21 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '._BANNERSADMIN;

function BannersAdmin() {
	global $prefix, $db, $bgcolor2, $banners, $user_prefix;
	include('header.php');
	GraphicAdmin('_AMENU6');
	OpenTable();
	echo '<div align="center" class="title">'._BANNERSADMIN.'</div>';
	CloseTable();
/* Banners List */
	echo '<br /><a name="top">';
	OpenTable();
	echo "<div align=\"center\" class=\"option\">"._BANNERS."</div><br />"
	."<table width=\"100%\" border=\"0\"><tr>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._ADVERT."/"._CLIENTNAME."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._IMPRESSIONS."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._IMPLEFT."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CLICKS."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CLICKSPERCENT."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._TYPE."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._STATUS."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>Text</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._FUNCTIONS."</b></td><tr>";
	$result = $db->sql_query("SELECT bid, cid, imptotal, impmade, clicks, imageurl, clickurl, date, type, active, textban, text_width, text_height, text_title, text_bg, text_clr from ".$prefix."_banner order by active,cid,bid");
	while (list($bid, $cid, $imptotal, $impmade, $clicks, $imageurl, $clickurl, $date, $type, $active, $textban, $text_width, $text_height, $text_title, $text_bg, $text_clr) = $db->sql_fetchrow($result)) {
		$result2 = $db->sql_query("select username from ".$user_prefix."_users where user_id='$cid'");
		list($user_name) = $db->sql_fetchrow($result2);
		$percent = ($impmade==0) ? 0 : substr(100 * $clicks / $impmade, 0, 5);
		$left = ($imptotal==0) ? _UNLIMITED : ($imptotal-$impmade);
		$type = ($type == 0) ? _NORMAL : _BLOCK;
		if ($active == 1) {
			$t_active = _ACTIVE;
			$c_active = _DEACTIVATE;
		} else {
			$t_active = "<i>"._INACTIVE."</i>";
			$c_active = _ACTIVATE;
		}
		if ($textban == 1) {
			$t_text = 'Text';
			$c_text = 'No_Text';
		} else {
			$t_text = "<i>No_Text</i>";
			$c_text = 'Text';
		}
		echo "<td bgcolor=\"$bgcolor2\" align=\"center\">";
		if ($textban == 1) {
			echo '<table width=\"'.($text_width/2).'" border="0" cellspacing="1" cellpadding="0" bgcolor="#'.$text_bg.'" align="center"><tr>'
				.'<td valign="middle" height="'.($text_height/2).'">'
				."<center><a href=\"banners.php?op=click&amp;bid=$bid\" alt=\"$clickurl\" title=\"$clickurl\" style=\"color:#$text_clr\" onclick=\"window.open('banners.php?op=click&amp;bid=$bid','textad','toolbar=yes,menubar=yes,scrollbars=yes');return false\" target=\"_blank\">$text_title</a></center>\n"
				."</td>\n"
				."</tr></table><br /><br />";
		} else {
			echo "<img src=\"$imageurl\" width=\"50%\" alt=\"\" />";
		}
		echo "<br />$user_name</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$impmade</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$left</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$clicks</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$percent%</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$type</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$t_active</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$t_text</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\"><a href=\"".adminlink("&amp;mode=BannerEdit&amp;bid=$bid").'">'._EDIT.'</a><br /><a href="'.adminlink("&amp;mode=BannerStatus&amp;bid=$bid&amp;status=$active")."\">$c_active</a><br /><a href=\"".adminlink("&amp;mode=BannerDelete&amp;bid=$bid&amp;ok=0").'">'._DELETE.'</a></td><tr>';
	}
	echo '</td></tr></table>';
	CloseTable();
	echo '<br />';
/* Clients List */
	OpenTable();
	echo "<center><font class=\"option\"><b>"._ADVERTISINGCLIENTS."</b></font></center><br />"
	."<table width=\"100%\" border=\"0\"><tr>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CLIENTNAME."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._ACTIVEBANNERS2."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._INACTIVEBANNERS."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CONTACTNAME."</b></td>"
	."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CONTACTEMAIL."</b></td>"
	."</tr>";
	$result = $db->sql_query("SELECT count(b.bid) AS total, sum(b.active) as active, u.name, u.username, u.user_email FROM ".$prefix."_banner b LEFT JOIN ".$user_prefix."_users u ON (u.user_id=b.cid ) GROUP BY u.name, u.username, u.user_email");
	while($row = $db->sql_fetchrow($result)) {
		$clients[$row['username']]['name'] = $row['name'];
		$clients[$row['username']]['user_email'] = $row['user_email'];
		$clients[$row['username']]['banners']['active'] = $row['active'];
		$clients[$row['username']]['banners']['inactive'] = $row['total']-$row['active'];
	}
	if (isset($clients) && count($clients) > 0) foreach($clients AS $user => $client) {
		echo '<tr><td bgcolor="'.$bgcolor2.'" align="center">'.$user.'</td>'
		.'<td bgcolor="'.$bgcolor2.'" align="center">'.$client['banners']['active'].'</td>'
		.'<td bgcolor="'.$bgcolor2.'" align="center">'.$client['banners']['inactive'].'</td>'
		.'<td bgcolor="'.$bgcolor2.'" align="center">'.$client['name'].'</td>'
		.'<td bgcolor="'.$bgcolor2.'" align="center"><a href="mailto:'.$client['user_email'].'">'.$client['user_email'].'</a></td></tr>';
	}
	echo '</table>';
	CloseTable();
}

function BannerStatus() {
	global $prefix, $db;
	$bid = intval($_GET['bid']);
	$status = intval($_GET['status']);
	$active = ($status != 1);
	$result = $db->sql_query("update ".$prefix."_banner set active='$active' WHERE bid='$bid'");
	url_redirect(adminlink('&amp;mode=BannersAdmin'));
}

function BannerDelete() {
	global $prefix, $user_prefix, $db, $bgcolor2;
	$bid = intval($_GET['bid']);
	$ok = intval($_GET['ok']);
	if ($ok) {
		$db->sql_query("delete from ".$prefix."_banner where bid='$bid'");
		url_redirect(adminlink('&amp;mode=BannersAdmin').'#top');
	} else {
		include('header.php');
	GraphicAdmin('_AMENU6');
	OpenTable();
	echo '<div align="center" class="title">'._BANNERSADMIN.'</div>';
	CloseTable();
	echo "<br />";
	$result = $db->sql_query("select cid, imptotal, impmade, clicks, imageurl, clickurl, alttext, textban, text_width, text_height, text_title, text_clr, text_bg from ".$prefix."_banner where bid='$bid'");
	list($cid, $imptotal, $impmade, $clicks, $imageurl, $clickurl, $alttext, $textban, $text_width, $text_height, $text_title, $text_clr, $text_bg) = $db->sql_fetchrow($result);
	OpenTable();
	echo "<center><b>"._DELETEBANNER."</b><br /><br />";
	// If Text Banner
	if ($textban==1) {
	echo "<table width=\"$text_width\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" bgcolor=\"#$text_bg\" align=\"center\"><tr>\n"
		. "<td valign=\"middle\" height=\"$text_height\">\n"
		. "<center><a href=\"banners.php?op=click&amp;bid=$bid\" alt=\"$clickurl\" title=\"$clickurl\" style=\"color:#$text_clr\" onclick=\"window.open('banners.php?op=click&amp;bid=$bid','textad','toolbar=yes,menubar=yes,scrollbars=yes');return false\" target=\"_blank\">$text_title</a></center>\n"
		. "</td>\n"
		. "</tr></table><br /><br />";
	}
	// End Text Banner
	else {
	echo "<a href=\"$clickurl\"><img src=\"$imageurl\" border=\"1\" alt=\"\" /></a><br />"
		."<a href=\"$clickurl\">$clickurl</a><br /><br />";
	}
	echo "<table width=\"100%\" border=\"0\"><tr>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._ID."<b></td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._IMPRESSIONS."<b></td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._IMPLEFT."<b></td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CLICKS."<b></td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CLICKSPERCENT."<b></td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\"><b>"._CLIENTNAME."<b></td><tr>";
	$result2 = $db->sql_query("select user_id, username from ".$user_prefix."_users where user_id='$cid'");
//	  $result2 = $db->sql_query("select cid, name from ".$prefix."_bannerclient where cid='$cid'");
	list($cid, $name) = $db->sql_fetchrow($result2);
	$percent = ($clicks > 0) ? substr(100 * $clicks / $impmade, 0, 5) : $clicks;
	$left = ($imptotal==0) ? _UNLIMITED : $imptotal-$impmade;
	echo "<td bgcolor=\"$bgcolor2\" align=\"center\">$bid</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$impmade</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$left</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$clicks</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$percent%</td>"
		."<td bgcolor=\"$bgcolor2\" align=\"center\">$name</td><tr>";
	}
	echo "</td></tr></table><br />"._SURETODELBANNER."<br /><br />"
	.'[ <a href="'.adminlink('&amp;mode=BannersAdmin').'#top">'._NO.'</a> | <a href="'.adminlink("&amp;mode=BannerDelete&amp;bid=$bid&amp;ok=1").'">'._YES.'</a> ]</center><br /><br />';
	CloseTable();
}

function BannerEdit() {
	global $prefix, $db, $op;
	$bid = intval($_GET['bid']);
	include('header.php');
	GraphicAdmin('_AMENU6');
	OpenTable();
	echo '<div align="center" class="title">'._BANNERSADMIN.'</div>';
	CloseTable();
	echo "<br />";
	$result = $db->sql_query("select * from ".$prefix."_banner where bid='$bid'");
	if ($db->sql_numrows($result) < 1) cpg_error('Banner doesn\'t exist');
	$row = $db->sql_fetchrow($result);
	foreach($row as $var => $value) {
		if (isset($$var)){
			unset($$var);
		}
		$$var = $value;
	}
	if($imptotal==0) {
		$impressions = _UNLIMITED;
	} else {
		$impressions = $imptotal;
	}
	if ($type == 0) {
	$sel1 = 'selected="selected"';
	$sel2 = '';
	} else {
	$sel1 = '';
	$sel2 = 'selected="selected"';
	}
   OpenTable();
	echo
	'<fieldset><legend>'._EDITBANNER.'</legend>'
	.'<form method="post" action="'.adminlink($op).'"  enctype="multipart/form-data" accept-charset="utf-8">'
	.'<input type="hidden" name="mode" value="BannerChange" />'
	.'<input type="hidden" name="bid" value="'.$bid.'" />'
	.'<input type="hidden" name="imptotal" value="'.$imptotal.'" />'
	.'<input type="hidden" name="textban" value="'.$textban.'" />';
   
	
// If Text Banner
if ($textban==1) {
	echo "<table width=\"$text_width\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" bgcolor=\"$text_bg\" align=\"center\"><tr>\n"
		. "<td valign=\"middle\" height=\"$text_height\">\n"
		. "<center><a href=\"banners.php?op=click&amp;bid=$bid\" alt=\"$clickurl\" title=\"$clickurl\" style=\"color:$text_clr\" onclick=\"window.open('banners.php?op=click&amp;bid=$bid','textad','toolbar=yes,menubar=yes,scrollbars=yes');return false\" target=\"_blank\">$text_title</a></center>\n"
		. "</td>\n"
		. "</tr></table><br /><br />";
	
	echo
	'<label for="text_title">'._TEXT_TITLE.'</label><br />'
	.'<textarea name="text_title" rows="2" cols="72" maxlength="144">'.$text_title.'</textarea><br /><br />'
	.'<label for="text_width">'._TEXT_WIDTH.'</label>'
	.'<input type="text" name="text_width" size="5" maxlength="3" value="'.$text_width.'" /><br /><br />'
	.'<label for="text_height">'._TEXT_HGT.'</label>'
	.'<input type="text" name="text_height" size="5" maxlength="3" value="'.$text_height.'" /><br /><br />'
	.'<label for="alttext">'._TEXT_COLOR.'</label>'
	.'<input type="text" name="text_clr" size="50" maxlength="255" value="'.$text_clr.'" /><br /><br />'
	.'<label for="alttext">'._TEXT_BACKGROUND.'</label>'
	.'<input type="text" name="text_bg" size="50" maxlength="255" value="'.$text_bg.'" /><br /><br />';
} else { // End Text Banner
	echo 
	'<img src="'.$imageurl.'" border="1" alt="'.$imageurl.'"></center><br /><br />'
	.'<label for="imageurl">'._IMAGEURL.'</label>'
	.'<input type="text" name="imageurl" size="50" maxlength="255" value="'.$imageurl.'" /><br /><br />'
	.'<label for="alttext">'._ALTERNATETEXT.'</label>'
	.'<input type="text" name="alttext" size="50" maxlength="255" value="'.$alttext.'" /><br /><br />';
}// End Text Banner
	echo
	'<label for="type">'._TYPE.': <select name="type">'
	.'<option value="0" '.$sel1.'>'._NORMAL.'</option>'
	.'<option value="1" '.$sel2.'>'._BLOCK.'</option>'
	.'</select><br />'
	.'<label for="active">'._ACTIVATE.'</label> '.yesno_option('active', $active).'<br /><br />'
	._IMPRESSIONS.'&nbsp;'. $impressions.'<br />'
	.'<label for="imptotal">'._PURCHASEDIMPRESSIONS.'</label>'
	.'<input type="text" name="impadded" size="12" maxlength="12" /><br /><br />'
	.'<label for="clickurl">'._CLICKURL.'</label>'
	.'<input type="text" name="clickurl" size="50" maxlength="255" value="'.$clickurl.'" /><br /><br />'
	.'<input type="submit" value="'._SAVECHANGES.'" />'
	.'</form></fieldset><br />';
	CloseTable();
}

function BannerChange() {
	global $prefix, $db;
	$bid = intval($_POST['bid']);
	$imptotal = intval($_POST['imptotal']);
	$impadded = intval($_POST['impadded']);
	$imageurl = Fix_Quotes($_POST['imageurl']);
	$clickurl = Fix_Quotes($_POST['clickurl']);
	$alttext = Fix_Quotes($_POST['alttext']);
	$type = intval($_POST['type']);
	$active = intval($_POST['active']);
	$textban = intval($_POST['textban']);
	$text_width = intval($_POST['text_width']);
	$text_height = intval($_POST['text_height']);
	$text_title = Fix_Quotes($_POST['text_title']);
	$text_bg = Fix_Quotes($_POST['text_bg']);
	$text_clr = Fix_Quotes($_POST['text_clr']);

	$imp = $imptotal+$impadded;
//	  $alttext = ereg_replace("\"", "", $alttext);
//	  $alttext = ereg_replace("'", "", $alttext);
	$db->sql_query("update ".$prefix."_banner SET imptotal='$imp', imageurl='$imageurl', clickurl='$clickurl', alttext='$alttext', type='$type', active='$active', textban='$textban', text_width='$text_width', text_height='$text_height', text_title='$text_title',	text_bg='$text_bg', text_clr='$text_clr' where bid='$bid'");
	url_redirect(adminlink('&amp;mode=BannersAdmin').'#top');
}

function BannerClientDelete() {
	global $prefix, $db;
	$cid = intval($_GET['cid']);
	$ok = intval($_GET['ok']);
	if ($ok) {
		$db->sql_query("delete from ".$prefix."_banner where cid='$cid'");
		$db->sql_query("delete from ".$prefix."_bannerclient where cid='$cid'");
		url_redirect(adminlink('&amp;mode=BannersAdmin').'#top');
	} else {
	include('header.php');
	GraphicAdmin('_AMENU6');
	OpenTable();
	echo '<div align="center" class="title">'._BANNERSADMIN.'</div>';
	CloseTable();
	echo '<br />';
	$result = $db->sql_query("select cid, name from ".$prefix."_bannerclient where cid='$cid'");
	list($cid, $name) = $db->sql_fetchrow($result);
	OpenTable();
	echo "<center><b>"._DELETECLIENT.": $name</b><br /><br />"._SURETODELCLIENT."<br /><br />";
	$result2 = $db->sql_query("select imageurl, clickurl from ".$prefix."_banner where cid='$cid'");
	$numrows = $db->sql_numrows($result2);
	if($numrows==0) {
		echo _CLIENTWITHOUTBANNERS.'<br /><br />';
	} else {
		echo '<b>'._WARNING.'!!!</b><br />'._DELCLIENTHASBANNERS.':<br /><br />';
	}
	while (list($imageurl, $clickurl) = $db->sql_fetchrow($result2)) {
		echo "<a href=\"$clickurl\"><img src=\"$imageurl\" border=\"1\" alt=\"\" /></a><br />
		<a href=\"$clickurl\">$clickurl</a><br /><br />";
	}
	}
	echo _SURETODELCLIENT.'<br /><br />
	[ <a href="'.adminlink('&amp;mode=BannersAdmin').'#top">'._NO.'</a> | <a href="'.adminlink("&amp;mode=BannerClientDelete&amp;cid=$cid&amp;ok=1").'">'._YES.'</a> ]</center><br /><br />';
	CloseTable();
}

function BannerClientEdit() {
	global $prefix, $db;
	$cid = intval($_GET['cid']);
	include('header.php');
	GraphicAdmin('_AMENU6');
	OpenTable();
	echo '<div align="center" class="title">'._BANNERSADMIN.'</div>';
	CloseTable();
	echo '<br />';
	$result = $db->sql_query("select name, contact, email, login, passwd, extrainfo from ".$prefix."_bannerclient where cid='$cid'");
	list($name, $contact, $email, $login, $passwd, $extrainfo) = $db->sql_fetchrow($result);
	OpenTable();
	echo "<center><font class=\"option\"><b>"._EDITCLIENT."</b></font></center><br /><br />"
	.'<form action="'.adminlink('&amp;mode=BannerClientChange').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">'
	._CLIENTNAME.": <input type=\"text\" name=\"name\" value=\"$name\" size=\"30\" maxlength=\"60\" /><br /><br />"
	._CONTACTNAME.": <input type=\"text\" name=\"contact\" value=\"$contact\" size=\"30\" maxlength=\"60\" /><br /><br />"
	._CONTACTEMAIL.": <input type=\"text\" name=\"email\" size=30 maxlength=\"60\" value=\"$email\" /><br /><br />"
	._CLIENTLOGIN.": <input type=\"text\" name=\"login\" size=12 maxlength=\"10\" value=\"$login\" /><br /><br />"
	._CLIENTPASSWD.": <input type=\"text\" name=\"passwd\" size=12 maxlength=\"10\" value=\"$passwd\" /><br /><br />"
	._EXTRAINFO."<br /><textarea name=\"extrainfo\" cols=\"60\" rows=\"10\">$extrainfo</textarea><br /><br />"
	."<input type=\"hidden\" name=\"cid\" value=\"$cid\" />"
	."<input type=\"hidden\" name=\"op\" value=\"BannerClientChange\" />"
	."<input type=\"submit\" value=\""._SAVECHANGES."\" />"
	."</form>";
	CloseTable();
}

function BannerClientChange() {
	global $prefix, $db;
	$cid = intval($_POST['cid']);
	$name = Fix_Quotes($_POST['name']);
	$contact = Fix_Quotes($_POST['contact']);
	$email = Fix_Quotes($_POST['email']);
	$extrainfo = Fix_Quotes($_POST['extrainfo']);
	$login = Fix_Quotes($_POST['login']);
	$passwd = md5($_POST['passwd']);

	$db->sql_query("update ".$prefix."_bannerclient set name='$name', contact='$contact', email='$email', login='$login', passwd='$passwd' where cid='$cid'");
	url_redirect(adminlink('&amp;mode=BannersAdmin').'#top');
}

$mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : '');
switch ($mode) {

	case "BannersAdmin":	BannersAdmin(); break;
	case "BannerDelete":	BannerDelete(); break;
	case "BannerEdit":	BannerEdit(); break;
	case "BannerChange":	BannerChange(); break;
	case "BannerClientDelete":	BannerClientDelete(); break;
	case "BannerClientEdit":	BannerClientEdit(); break;
	case "BannerClientChange":	BannerClientChange(); break;
	case "BannerStatus":	BannerStatus(); break;
	default:	BannersAdmin(); break;
}