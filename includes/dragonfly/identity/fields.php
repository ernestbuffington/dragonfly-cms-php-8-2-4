<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Identity;

abstract class Fields
{

	const
		TYPE_TEXT     = 0,
		TYPE_YES_NO   = 1,
		TYPE_TEXTAREA = 2,
		TYPE_TIMEZONE = 3,
		TYPE_NUMBER   = 4,
		TYPE_GENDER   = 5,
		TYPE_DATE     = 6,
		TYPE_THEME    = 7,
		TYPE_LANGUAGE = 8;

	public static function fetchFromPost()
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		$fields = array();
		$result = $SQL->query("SELECT field, langdef label, visible, type, size FROM {$SQL->TBL->users_fields} WHERE visible > 0");
		while ($row = $result->fetch_assoc()) {
			$var = ('name' === $row['field']) ? 'realname' : $row['field'];
			if ('_' === $row['label'][0] && defined($row['label'])) {
				$row['label'] = constant($row['label']);
			}
			if (2 == $row['visible'] && (!isset($_POST[$var]) || !strlen($_POST[$var]))) {
				throw new \Exception("Required field '{$row['label']}' can't be empty");
			}
			$val = strip_tags($_POST[$var]);
			if ($row['type'] == self::TYPE_YES_NO || $row['type'] == self::TYPE_NUMBER) { $val = (int)$val; }
			else if ($row['type'] != self::TYPE_TIMEZONE) { $val = mb_substr($val,0,$row['size']); }
			$row['value'] = $val;
			$fields[$var] = $row;
		}
		return $fields;
	}

	public static function tpl_field($row, $userinfo=array())
	{
		$CFG = \Dragonfly::getKernel()->CFG;

		$name = $row['field'];
		$field = array(
			'type'  => $row['type'],
			'name'  => ('name' === $name) ? 'realname' : $name,
			'size'  => $row['size'],
			'label' => defined($row['langdef']) ? constant($row['langdef']) : $row['langdef'],
			'note'  => defined($row['langdef']."MSG") ? constant($row['langdef']."MSG") : null,
			'required' => (2 == $row['visible'])
		);

		if (self::TYPE_YES_NO == $field['type']) {
			$field['checked'] = !(isset($userinfo[$name]) ? empty($userinfo[$name]) : empty($field['size']));
		}
		else if (self::TYPE_TIMEZONE == $field['type'])
		{
			$field['type'] = 8;
			$field['options'] = array();
			if (empty($userinfo[$name])) $userinfo[$name] = date_default_timezone_get();
			foreach (\DateTimeZone::listIdentifiers() as $tz) {
				$field['options'][] = array(
					'label'    => $tz,
					'value'    => $tz,
					'selected' => (isset($userinfo[$name]) ? $tz == $userinfo[$name] : false)
				);
			}
		}
		else if (self::TYPE_THEME == $field['type'])
		{
			if (!$CFG->member->allowusertheme) return;
			$field['type'] = 8;
			$field['options'] = array();
			if (empty($userinfo[$name])) { $userinfo[$name] = $CFG->global->Default_Theme; }
			foreach (\Poodle\TPL::getThemes() as $theme) {
				$field['options'][strtolower($theme)] = array(
					'label'    => $theme,
					'value'    => $theme,
					'selected' => (isset($userinfo[$name]) ? $theme == $userinfo[$name] : false)
				);
			}
			ksort($field['options']);
		}
		else if (self::TYPE_LANGUAGE == $field['type'])
		{
			$field['options'] = array();
			if ('user_lang' === $field['name']) {
				if (!$CFG->global->multilingual) return;
				foreach (\Dragonfly::getKernel()->L10N->getActiveList() as $lng) {
					$field['options'][] = array(
						'label'    => $lng['label'],
						'value'    => $lng['value'],
						'selected' => (isset($userinfo['user_lang']) ? $lng['value'] == $userinfo['user_lang'] : false)
					);
				}
			}
		}
		else
		{
			$field['value'] = isset($userinfo[$name]) ? $userinfo[$name] : null;
		}

		return $field;
	}

}
