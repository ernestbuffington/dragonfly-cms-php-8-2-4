<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

class Wysiwyg
{
	public
		$editor,
		$type,
		$form,
		$field,
		$width,
		$height,
		$value;

	function __construct($form, $field, $width='100%', $height='300px', $value='')
	{
		# Check for previously selected wysiwyg and use that else use none
		if (isset($_POST['wysiwyg_editor'])) {
			if (isset($_SESSION['WYSIWYG']) && $_SESSION['WYSIWYG'] == 'bbcode' && $_POST['wysiwyg_editor'] != 'bbcode') {
				$value = \Dragonfly\BBCode::decode(\Dragonfly\BBCode::encode($value), 1, true);
			}
			$_SESSION['WYSIWYG'] = $_POST['wysiwyg_editor'];
		}
		$type = isset($_SESSION['WYSIWYG']) ? $_SESSION['WYSIWYG'] : '';
		if (!empty($type) && $type != 'bbcode' && $type != 'wysiwyg') {
			if (file_exists(BASEDIR."includes/wysiwyg/$type/$type.php")) {
				include_once(BASEDIR."includes/wysiwyg/$type/$type.php");
				$func = $type.'_getInstance';
				if (function_exists($func)) {
					$this->editor = $func($field, $width, $height, $value);
				} else {
					$this->editor = new $type($field, $width, $height, $value);
				}
			} else {
				$type = '';
				trigger_error('The chosen WYSIWYG editor "'.$type.'" is not available.', E_USER_NOTICE);
			}
		}
		$this->type   = $type;
		$this->form   = $form;
		$this->field  = $field;
		$this->width  = $width;
		$this->height = $height;
		$this->value  = $value;

		if ('bbcode' == $type && defined('ADMIN_PAGES')) {
			\Dragonfly\Output\Js::add('includes/poodle/javascript/bbcode.js');
			\Dragonfly\Output\Css::add('bbcode');
		}
		if ('wysiwyg' == $type) {
			\Dragonfly\Output\Js::add('includes/poodle/javascript/wysiwyg.js');
			\Dragonfly\Output\Css::add('wysiwyg');
		}
	}

	function setHeader()
	{
		if (!empty($this->editor) && method_exists($this->editor, 'setHeader')) {
			$this->editor->setHeader();
		}
	}

	function getSelect()
	{
		return \Dragonfly\Output\HTML::select_box('wysiwyg_editor', $this->type, $this->getEditors())
			.' <input type="submit" name="wysiwyg" value="Change" />';
	}

	function getEditors()
	{
		$editors = array('' => 'none');
		$editors['bbcode'] = 'BBCode';
		$editors['wysiwyg'] = 'WYSIWYG';
		$wysiwygs = dir(BASEDIR.'includes/wysiwyg');
		while ($dir = $wysiwygs->read()) {
			if ($dir[0] != '.' && file_exists(BASEDIR."includes/wysiwyg/$dir/$dir.php")) {
				$editors[$dir] = $dir;
			}
		}
		$wysiwygs->close();
		return $editors;
	}

// This non-compliant code for textarea totally sucks - rows/cols must be defined.
	function getHTML()
	{
		if (!empty($this->editor)) {
			return $this->editor->getHtml($this->field);
		} elseif ($this->type == 'bbcode') {
			if (!defined('ADMIN_PAGES')) {
				$html = \Dragonfly\BBCode::getTable($this->field, $this->form, 1);
				$html .= '<textarea id="'.$this->field.'" name="'.$this->field.'" style="width: '.$this->width.'; height: '.$this->height.';" onselect="storeCaret(this);">'.htmlspecialchars($this->value).'</textarea>';
			} else {
				$html = '<textarea class="bbcode" id="'.$this->field.'" name="'.$this->field.'" style="height:'.$this->height.';width:'.$this->width.'">'.htmlspecialchars($this->value).'</textarea>';
			}
			return $html;
		} elseif ($this->type == 'wysiwyg') {
			return '<textarea class="wysiwyg" id="'.$this->field.'" name="'.$this->field.'" style="height:'.$this->height.';width:'.$this->width.'">'.htmlspecialchars($this->value).'</textarea>';
		} else {
			return '<textarea id="'.$this->field.'" name="'.$this->field.'" style="width: '.$this->width.'; height: '.$this->height.';">'.htmlspecialchars($this->value).'</textarea>';
		}
	}

	function Show() { echo $this->getHTML(); }

}
