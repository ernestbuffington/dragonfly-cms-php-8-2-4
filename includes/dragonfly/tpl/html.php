<?php

namespace Dragonfly\TPL;

class HTML extends \Poodle\TPL
{
	public
		$tpl_header = null,
		$tpl_footer = null,
		$tpl_layout = null,

		$body = '',
		$DTD  = 'html5';

	protected
		$tpl_path = 'themes/default/';

	function __construct()
	{
		parent::__construct();

		if (defined('ADMIN_PAGES')) {
			$theme = 'admin';
		} else {
			$K = \Dragonfly::getKernel();
			$CFG = $K->CFG;
			$theme = $CFG->global->Default_Theme;
			if ($CFG->member->allowusertheme || is_admin()) {
				if (isset($_GET['prevtheme'])) {
					if (!preg_match('#^([a-zA-Z0-9_\-]+)$#', $_GET['prevtheme'], $theme)) {
						cpg_error(sprintf(_ERROR_BAD_CHAR, 'theme'), _SEC_ERROR);
					}
					$theme = $_SESSION['CPG_SESS']['prevtheme'] = $theme[1];
				} else if (!empty($_SESSION['CPG_SESS']['prevtheme'])) {
					$theme = $_SESSION['CPG_SESS']['prevtheme'];
				} else if ($CFG->member->allowusertheme && $K->IDENTITY->isMember() && $K->IDENTITY->theme) {
					$theme = $K->IDENTITY->theme;
				}
			}
			$_SESSION['CPG_SESS']['theme'] = $GLOBALS['ThemeSel'] = $theme = is_file("themes/{$theme}/theme.php") ? $theme : 'default';
		}

		$this->setTPLName($theme);
	}

	function __get($k)
	{
		if ('S_MAIN_MENU' === $k || 'S_ADMIN_GRAPHMENU' === $k) {
			return property_exists($this, $k) ? $this->$k : null;
		}
		return parent::__get($k);
	}

	public function display($filename, $data=null, $mtime=0, $options=0)
	{
		if (!defined('HEADER_OPEN')) { include_once('header.php'); }
		if ('header'===$filename && ($this->tpl_header || $this->tpl_layout)) {
			if ($this->tpl_header) {
				parent::display($this->tpl_header, null, 0, \Poodle\TPL::OPT_PUSH_DOCTYPE);
				$this->tpl_header = null;
			}
		} else if ('footer'===$filename && ($this->tpl_footer || $this->tpl_layout)) {
			if ($this->tpl_footer) {
				parent::display($this->tpl_footer, null, 0, \Poodle\TPL::OPT_END_PARSER);
				$this->tpl_footer = null;
			} else {
				parent::display('layouts/'.$this->tpl_layout, null, 0, \Poodle\TPL::OPT_PUSH_DOCTYPE || \Poodle\TPL::OPT_END_PARSER);
			}
		} else if ($this->tpl_layout) {
			$this->body .= $this->toString($filename, $data, $mtime);
		} else {
			return parent::display($filename, $data, $mtime, $options);
		}
	}

	public static function parseAttributes($name, $attribs)
	{
		if ('time' === $name && !empty($attribs['datetime']) && is_numeric($attribs['datetime'])) {
			$attribs['datetime'] = gmdate('Y-m-d\TH:i:s\Z', $attribs['datetime']);
		} else
		if ('input' === $name || 'option' === $name || 'select' === $name || 'textarea' === $name) {
			if ('input' === $name) {
				if (empty($attribs['type'])) {
					$attribs['type'] = 'text';
				} else {
					$fn = 'gmdate';
					$f = null;
					switch ($attribs['type'])
					{
					case 'date':     $f = 'Y-m-d'; break;
					case 'datetime': $f = 'Y-m-d\TH:i:s\Z'; break;
					case 'datetime-local': $f = 'c'; $fn = 'date'; break;
					case 'month':    $f = 'Y-m'; break;
					case 'time' :    $f = 'H:i:s'; break;
					case 'week':     $f = 'Y-\WW'; break;
					}
					if ($f) {
						if (isset($attribs['value'])) {
							$attribs['value'] = static::getInputDate($attribs['value'], $fn, $f);
						}
						if (isset($attribs['min'])) {
							$attribs['min'] = static::getInputDate($attribs['min'], $fn, $f);
						}
						if (isset($attribs['max'])) {
							$attribs['max'] = static::getInputDate($attribs['max'], $fn, $f);
						}
					}
				}
			}
			if (isset($attribs['autofocus'])) $attribs['autofocus'] = ('' === $attribs['autofocus'] || !empty($attribs['autofocus']));
			if (isset($attribs['checked']))   $attribs['checked']   = ('' === $attribs['checked'] || !empty($attribs['checked']));
			if (isset($attribs['disabled']))  $attribs['disabled']  = ('' === $attribs['disabled'] || !empty($attribs['disabled']));
			if (isset($attribs['multiple']))  $attribs['multiple']  = ('' === $attribs['multiple'] || !empty($attribs['multiple']));
			if (isset($attribs['readonly']))  $attribs['readonly']  = ('' === $attribs['readonly'] || !empty($attribs['readonly']));
			if (isset($attribs['required']))  $attribs['required']  = ('' === $attribs['required'] || !empty($attribs['required']));
			if (isset($attribs['selected']))  $attribs['selected']  = ('' === $attribs['selected'] || !empty($attribs['selected']));
		}
		return parent::parseAttributes($name, $attribs);
	}

	protected static function getInputDate($v, $fn, $f)
	{
		if (is_int($v) || ctype_digit($v)) {
			return 0 == $v ? '' : $fn($f,$v);
		}
		if ($v instanceof \DateTime) {
			return $v->format($f);
		}
		return $v;
	}

}
