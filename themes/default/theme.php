<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * AddDefaultValueForUndefinedVariableRector (https://github.com/vimeo/psalm/blob/29b70442b11e3e66113935a2ee22e165a70c74a4/docs/fixing_code.md#possiblyundefinedvariable)
 * TernaryToNullCoalescingRector
 */
 
if (!defined('CPG_NUKE')) { exit; }
define('THEME_VERSION', '10');

$gfxcolor = '#C0C000';

function OpenTable()   { echo '<div class="table1">'; }
function CloseTable()  { echo '</div>'; }

function OpenTable2()  { echo '<div class="table2">'; }
function CloseTable2() { echo '</div>'; }

function themeheader()
{
	global $Module;
	$CFG = \Dragonfly::getKernel()->CFG;
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->tpl_header = 'header';
	$OUT->tpl_footer = 'footer';

	\Dragonfly\Output\Css::add('poodle/tabs');
	\Dragonfly\Output\Css::add('poodle/forms');
//	\Dragonfly\Output\Js::add('includes/poodle/javascript/tabs.js');
	\Dragonfly\Output\Js::add('includes/poodle/javascript/forms.js');
	\Dragonfly\Output\Js::add('themes/default/javascript/toggle.js');

	\Dragonfly\Output\Css::add('poodle/emoji');
	\Dragonfly\Output\Js::add('includes/poodle/javascript/emoji.js');

	if (CPG_DEBUG || DF_MODE_DEVELOPER || \Dragonfly::$DEBUG) {
		\Dragonfly\Output\Css::add('poodle/debugger');
		\Dragonfly\Output\Js::add('includes/poodle/javascript/debugger.js');
	}

	$OUT->RSS_DATA    = $Module && $Module->active && is_file($Module->path .'feed_rss.inc') ? DOMAIN_PATH.'?feed='. $Module->name : false;
	$OUT->S_BANNER    = $CFG->global->banners ? \Dragonfly\Modules\Our_Sponsors\Banner::getRandom() : '';
	$OUT->S_MAIN_MENU = \Dragonfly\Page\Menu\User::display();
}

function themefooter()
{
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->S_FOOTER = footmsg();
	$OUT->display('footer');
}

/**
 * string theme_open_form
 *
 * Creates start tag for form
 * 	$link : link for action default blank
 * 	$form_name : useful for styling
 * 	$legend: optional string value is used in form lagend tag
 * 	$border: optional use 1 to not show border on fieldset from stylesheet
 */
function theme_open_form($link, $form_name=false, $legend=false, $tborder=false)
{
	$form_name = $form_name ? ' id="'.$form_name.'"' :'';
	return '<form method="post" action="'.$link.'"'.$form_name.'><fieldset>'
		.($legend ? "<legend>{$legend}</legend>" : '');
}
function theme_close_form()
{
	return '</fieldset></form>';
}

/**
 * string theme_yesno_option
 * Creates 2 radio buttons with a Yes and No option
 * 	$name : name for the <input>
 * 	$value: current value, 1 = yes, 0 = no
 */
function theme_yesno_option($name, $value=0)
{
	$sel = array('','');
	$sel[$value] = ' checked="checked"';
	return "<select name=\"{$name}\" id=\"{$name}\">\n"
		. '<option value="1"'.$sel[1].">"._YES."</option>\n"
		. '<option value="0"'.$sel[0].">"._NO."</option>\n"
		. '</select>';
}

/**
 * string theme_select_option
 *
 * Creates a selection dropdown box of all given variables in the array
 * 	$name : name for the <select>
 * 	$value: current/default value
 * 	$array: array like array("value1","value2")
 */
function theme_select_option($name, $value, array $array)
{
	$sel = [];
 $sel[$value] = ' selected="selected"';
	$select = "<select name=\"{$name}\" id=\"{$name}\">\n";
	foreach ($array as $var) {
		$select .= '<option'.($sel[$var] ?? '').">{$var}</option>\n";
	}
	return $select.'</select>';
}

/**
 * string theme_select_box
 *
 * Creates a selection dropdown box of all given variables in the multi array
 * 	$name : name for the <select>
 * 	$value: current/default value
 * 	$array: array like array("value1 => title1","value2 => title2")
 */
function theme_select_box($name, $value, array $array)
{
	$select = "<select name=\"{$name}\" id=\"{$name}\">\n";
	foreach ($array as $val => $title) {
		$select .= "<option value=\"{$val}\"".(($val==$value)?' selected="selected"':'').">{$title}</option>\n";
	}
	return $select.'</select>';
}
