<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Output;

abstract class HTML
{

	final public static function minify($str)
	{
		if (DF_MODE_DEVELOPER) return $str;
		$str = preg_replace('#\R+#',  "\n", $str);
		$str = preg_replace('#\n\s+#',"\n", $str);
		$str = preg_replace('#\s+\n#',"\n", $str);
		return preg_replace('#\s+(/?>)#','$1', $str);
	}

	public static function yesno_option($name, $value=0)
	{
		$value = $value ? 1 : 0;
		if (function_exists('theme_yesno_option')) {
			return theme_yesno_option($name, $value);
		}
		$sel = array('','');
		$sel[$value] = ' checked="checked"';
		return '<select name="'.$name.'" id="'.$name.'">'
			. '<option value="1"'.$sel[1].">"._YES.'</option>'
			. '<option value="0"'.$sel[0].">"._NO.'</option>'
			. '</select>';
	}

	public static function select_option($name, $default, array $options)
	{
		if (function_exists('theme_select_option')) {
			return theme_select_option($name, $default, $options);
		}
		$select = '<select name="'.$name.'" id="'.$name."\">\n";
		foreach ($options as $var) {
			$select .= '<option'.(($var == $default)?' selected="selected"':'').">{$var}</option>\n";
		}
		return $select.'</select>';
	}

	public static function select_box($name, $default, array $options)
	{
		if (function_exists('theme_select_box')) {
			return theme_select_box($name, $default, $options);
		}
		$select = '<select name="'.$name.'" id="'.$name."\">\n";
		foreach ($options as $value => $title) {
			$select .= "<option value=\"{$value}\"".(($value == $default)?' selected="selected"':'').">{$title}</option>\n";
		}
		return $select.'</select>';
	}

	public static function select_box_group($name, $default, array $groups)
	{
		$select = '<select name="'.$name.'" id="'.$name."\">\n";
		foreach ($groups as $group => $option) {
			$select .= "<optgroup label=\"{$group}\">\n";
			foreach ($option as $value => $title) {
				$select .= "<option value=\"{$value}\"".(($value == $default)?' selected="selected"':'').">{$title}</option>\n";
			}
			$select .= "</optgroup>\n";
		}
		return $select.'</select>';
	}

	public static function open_form($link='', $form_name=false, $legend=false, $tborder=false)
	{
		if (function_exists('theme_open_form')) {
			return theme_open_form($link, $form_name, $legend, $tborder);
		}
		$bord = ($tborder ? $tborder : '');
		$form_name = ($form_name ? ' id="'.$form_name.'"' : '');
		return '<form method="post" action="'.$link.'"'.$form_name.' enctype="multipart/form-data"><fieldset '.$bord.'>'
			. ($legend ? "<legend>{$legend}</legend>" : '');
	}

	public static function close_form()
	{
		return function_exists('theme_close_form') ? theme_close_form() : '</fieldset></form>';
	}

	public static function group_selectbox($fieldname, $current=0, $mvanon=false, $all=true)
	{
		static $groups;
		if (!isset($groups)) {
			global $db;
			$groups = array(0=>_MVALL, 1=>_MVUSERS, 2=>_MVADMIN, 3=>_MVANON);
			$qr = $db->query("SELECT group_id, group_name FROM {$db->TBL->bbgroups} WHERE group_single_user=0 ORDER BY group_name");
			while ($r = $qr->fetch_row()) {
				$groups[($r[0]+3)] = $r[1];
			}
		}
		$tmpgroups = $groups;
		if (!$all) { unset($tmpgroups[0]); }
		if (!$mvanon) { unset($tmpgroups[3]); }
		return static::select_box($fieldname, $current, $tmpgroups);
	}

}
