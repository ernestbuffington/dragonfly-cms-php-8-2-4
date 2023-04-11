<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth\Result;

class Form
{
	protected
		$fields = array(),
		$action = null,
		$submit = false,
		$css_class = null;

	public function __construct($fields, $action = null, $css_class = null, $submit=false)
	{
		if ('?' === $action[0]) {
			$action = $_SERVER['REQUEST_PATH'].$action;
		}
		foreach ($fields as &$field) {
			if (!isset($field['value'])) {
				$field['value'] = null;
			}
		}
		$this->fields = $fields;
		$this->action = $action;
		$this->submit = $submit;
		$this->css_class = $css_class;
	}

	public function __get($key)
	{
		return (property_exists($this, $key) ? $this->$key : null);
	}
}
