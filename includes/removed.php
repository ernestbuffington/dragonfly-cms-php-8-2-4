<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

# Removed functions only available thru CPG_DEBUG mode
function title($text) {
	trigger_deprecated();
}

function themesidebox($title, $content, $bid=0) {
	trigger_deprecated();
	return false;
}

function blocks($side, $count=false) {
	global $Blocks;
	trigger_deprecated();
	if (is_object('Blocks')) {
		return $Blocks->display($side);
	}
	return false;
}

function themecenterbox($title, $content, $side=0) {
	trigger_deprecated();
	return false;
}

function blocks_visible($side, $special=false) {
	global $Blocks;
	trigger_deprecated();
	if (is_object('Blocks')) {
		return $Blocks->$side;
	}
	return false;
}

function hideblock($id) {
	trigger_deprecated();
	return \Dragonfly\Blocks::isHidden($id);
}

function userblock($bid) {
	trigger_deprecated();
	return false;
}

function nuke_error($message, $title='ERROR', $redirect='') {
	trigger_deprecated();
	cpg_error($message, $title, $redirect);
}

function cookiedecode() {
	trigger_deprecated();
	return false;
}

function getusrinfo() {
	trigger_deprecated();
	return Dragonfly::getKernel()->IDENTITY;
}

function FixQuotes($what) {
	trigger_deprecated();
	$what = str_replace("'","''",$what);
	while (false !== strpos($what, "\\\\'")) { $what = str_replace("\\\\'", "'", $what); }
	return $what;
}

function formatTimestamp($time) {
	trigger_deprecated();
	return Dragonfly::getKernel()->L10N->date('DATE_F', $time);
}

function check_html($str, $strip='') {
	trigger_deprecated();
	return Fix_Quotes($str, empty($strip));
}

function filter_text($Message, $strip='') {
	trigger_deprecated();
	return check_words($Message);
}

function delQuotes($string) {
	trigger_deprecated();
	return $string;
}

function is_group() {
	trigger_deprecated();
}

function update_points() {
	trigger_deprecated();
}

function formatAidHeader($aid) {
	trigger_deprecated();
	echo $aid;
}

function get_author($aid) {
	trigger_deprecated();
	return $aid;
}

function gmtime() {
	trigger_deprecated();
	return time();
}

function encode_ip($ip) {
	trigger_deprecated();
	return inet_pton($ip);
}
