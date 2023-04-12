<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/admin/functions.inc,v $
  $Revision: 9.13 $
  $Author: nanocaiordo $
  $Date: 2006/11/01 22:18:52 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('news')) { die('Access Denied'); }

function show_head($title=false) {
  global $pagetitle;
  $pagetitle .= ' '._BC_DELIM.' '._ARTICLEADMIN;
  if ($title) {
	  $pagetitle .= ' '._BC_DELIM.' '.$title;
  }
  require_once('header.php');
  GraphicAdmin('_AMENU3');
  OpenTable();
  echo '<span class="genmed"><strong>'._NewsLANG.' '._ADMINISTRATION.'</strong></span><br />
  '.((isset($_GET['mode']) && $_GET['mode'] == 'add') ? '<strong><a href="'.adminlink('News&amp;mode=add').'">'._ADDSTORY.'</a></strong>' : '<a href="'.adminlink('News&amp;mode=add').'">'._ADDSTORY.'</a>').' |
  '.((isset($_GET['file']) && $_GET['file'] == 'submissions') ? '<strong><a href="'.adminlink('News&amp;file=submissions').'">'._SUBMISSIONS.'</a></strong>' : '<a href="'.adminlink('News&amp;file=submissions').'">'._SUBMISSIONS.'</a>').' |
  '.((isset($_GET['mode']) && $_GET['mode'] == 'cfg') ? '<strong><a href="'.adminlink('News&amp;mode=cfg').'">'._TB_CONFIG.'</a></strong>' : '<a href="'.adminlink('News&amp;mode=cfg').'">'._TB_CONFIG.'</a>').'<br /><br />';
}

function themepreview($title, $hometext, $bodytext='', $notes='') {

	$hometext = decode_bb_all($hometext, 1, true);
	$bodytext = decode_bb_all($bodytext, 1, true);
	echo "<b>$title</b><br /><br />$hometext<br /><br />$bodytext";
	if ($notes != '') { echo '<b>'._NOTE."</b> <i>$notes</i>"; }
}

function puthome($ihome, $acomm) {
	echo '<br /><b>'._PUBLISHINHOME.'</b>&nbsp;&nbsp;'.yesno_option('ihome', $ihome).'&nbsp;&nbsp;<font class="content"></font><br />'
		.'<br /><b>'._ACTIVATECOMMENTS.'</b>&nbsp;&nbsp;'.yesno_option('acomm', $acomm).'<br /><br />';
}

function newsorder($display_order) {
	return '<br /><b>Display Order '.select_box('display_order', $display_order, array(0=>'Normal', 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>'Top most')).'</b><br /><br />';
}

function shownews_edit($hometext, $bodytext) {
	$story_bbtable = bbcode_table('hometext', 'postnews', 1);
	$storyext_bbtable = bbcode_table('bodytext', 'postnews', 1);
	echo '<br /><br /><b>'._STORYTEXT.'</b><br />'.$story_bbtable.'
	<textarea wrap="virtual" cols="80" rows="15" name="hometext" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.htmlprepare($hometext).'</textarea><br /><br />
	<b>'._EXTENDEDTEXT.'</b><br />'.$storyext_bbtable.'
	<textarea wrap="virtual" cols="80" rows="25" name="bodytext" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.htmlprepare($bodytext).'</textarea><br />';
}

function autonews_edit($xday, $xmonth, $xyear, $xhour, $xmin) {
	$today = getdate(gmtime());
	if ($today['mday'] < 10) { $today['mday'] = "0$today[mday]"; }
	if ($today['hours'] < 10) { $today['hours'] = "0$today[hours]"; }
	if ($today['minutes'] < 10) { $today['minutes'] = "0$today[minutes]"; }
	echo '<br /><br />'._NOWIS.": $today[month] $today[mday], $today[year] @ $today[hours]:$today[minutes]:00 GMT<br /><br />"._DAY.': <select name="day">';
	for($i = 1; $i <= 31; $i++) {
		echo '<option'.(($i==$xday)?' selected="selected"':'').'>'.$i.'</option>';
	}
	echo '</select> '._UMONTH.': <select name="month">';
	for ($i = 1; $i <= 12; $i++) {
		echo '<option'.(($i==$xmonth)?' selected="selected"':'').'>'.$i.'</option>';
	}
	echo '</select> '._YEAR.': <select name="year">';
	for ($i = $today['year']; $i <= $today['year']+3; $i++) {
		echo '<option'.(($i==$xyear)?' selected="selected"':'').'>'.$i.'</option>';
	}
	echo '</select><br />'._HOUR.': <select name="hour">';
	for ($i = 0; $i <= 23; $i++) {
		$dummy = ($i < 10) ? "0$i" : $i;
		echo '<option'.(($i==$xhour)?' selected="selected"':'').'>'.$dummy.'</option>';
	}
	echo '</select> : <select name="min">';
	$i = 0;
	while ($i <= 59) {
		$dummy = ($i < 10) ? "0$i" : $i;
		echo '<option'.(($i==$xmin)?' selected="selected"':'').'>'.$dummy.'</option>';
		$i += 5;
	}
	echo '</select> : 00 GMT<br /><br />';
}

function SelectCategory($cat=0) {
	global $prefix, $db;
    $selcat = $db->sql_query("select catid, title from ".$prefix."_stories_cat order by title");
    echo '<b>'._CATEGORY.'</b> <select name="catid">';
    $sel[$cat] = ' selected="selected"';
    echo "<option value=\"0\" $sel[0]>"._ARTICLES.'</option>';
    while(list($catid, $title) = $db->sql_fetchrow($selcat)) {
        echo "<option value=\"$catid\"".$sel[$catid].">$title</option>";
    }
	echo '</select> [ <a href="'.adminlink('&amp;mode=catadd').'">'._ADD.'</a> | <a href="'.adminlink('&amp;mode=catedit').'">'._EDIT.'</a> | <a href="'.adminlink('&amp;mode=catdel').'">'._DELETE.'</a> ]';
}

function putpoll($poll_title, $option_text) {
	if (!is_active('Surveys')) return;
	OpenTable();
	echo '<center><font class="title"><b>'._ATTACHAPOLL.'</b></font><br />
	<font class="tiny">'._LEAVEBLANKTONOTATTACH.'</font><br />
	<br /><br />'._POLLTITLE.': <input type="text" name="poll_title" size="50" maxlength="100" value="'.$poll_title.'" /><br /><br />
	<font class="content">'._POLLEACHFIELD.'<br />
	<table border="0">';
	for($i = 1; $i <= 12; $i++)	   {
		echo '<tr><td>'._OPTION." $i:</td><td><input type=\"text\" name=\"option_text[$i]\" size=\"50\" maxlength=\"50\" value=\"".(isset($option_text[$i])?$option_text[$i]:'').'" /></td></tr>';
	}
	print '</table>';
	CloseTable();
}
