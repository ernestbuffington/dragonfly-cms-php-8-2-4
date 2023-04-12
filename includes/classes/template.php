<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/template.php,v $
  $Revision: 9.15 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:42 $
**********************************************/
/*
  Nathan Codding - Original version design and implementation
  Crimsonbane - Initial caching proposal and work
  psoTFX - Completion of file caching, decompilation routines and implementation of
  conditionals/keywords and associated changes

  The interface was inspired by PHPLib templates, and the template file (formats are
  quite similar)

  The keyword/conditional implementation is currently based on sections of code from
  the Smarty templating engine (c) 2001 ispi of Lincoln, Inc. which is released
  (on its own and in whole) under the LGPL. Section 3 of the LGPL states that any code
  derived from an LGPL application may be relicenced under the GPL, this applies
  to this source

  DEFINE directive inspired by a request by Cyberalien
*/

class cpg_template
{

	// variable that holds all the data we'll be substituting into
	// the compiled templates. Takes form:
	// --> $this->_tpldata[block.][iteration#][child.][iteration#][child2.][iteration#][variablename] == value
	// if it's a root-level variable, it'll be like this:
	// --> $this->_tpldata[.][0][varname] == value
	var $_tpldata = array();

	// Root dir and hash of filenames for each template handle.
	var $tpl = '';
	var $root = '';
	var $files = array();

	// this will hash handle names to the compiled/uncompiled code for that handle.
	var $compiled_code = array();

	function __construct()
	{
		global $MAIN_CFG, $CPG_SESS;
		if (!is_dir(BASEDIR.'themes/default/template')) { trigger_error("'default' theme does not exist", E_USER_ERROR); }
		if (is_dir('themes/'.$CPG_SESS['theme'].'/template')) {
			$this->tpl = $CPG_SESS['theme'];
		} else if (is_dir('themes/'.$MAIN_CFG['global']['Default_Theme'].'/template')) {
			$this->tpl = $MAIN_CFG['global']['Default_Theme'];
		} else {
			$this->tpl = 'default';
		}
		$this->_tpldata['.'][0]['REQUEST_URI'] = str_replace('&', '&amp;', substr(get_uri(),strlen($MAIN_CFG['server']['path'])));
		$this->root = 'themes/'.$this->tpl.'/template';
	}

	// Sets the template filenames for handles. $filename_array
	// should be a hash of handle => filename pairs.
	function set_filenames($filename_array)
	{
		if (!is_array($filename_array)) { return false; }
		foreach ($filename_array as $handle => $filename) {
			$this->set_handle($handle, $filename);
		}
		return true;
	}
	function set_handle($handle, $filename)
	{
		if (empty($handle)) { trigger_error('template error - No handlename specified', E_USER_ERROR); }
		if (empty($filename)) { trigger_error("template error - Empty filename specified for $handle", E_USER_ERROR); }
		$this->filename[$handle] = $filename;
		$this->files[$handle] = $this->root.'/'.$filename;
	}

	// Destroy template data set
	function destroy()
	{
		$this->_tpldata = array();
	}

	// Methods for loading and evaluating the templates
	function display($handle, $include_once = true)
	{
		if ($filename = $this->_tpl_load($handle)) {
			($include_once) ? include_once($filename) : include($filename);
		} else {
			eval(' ?>'.$this->compiled_code[$handle].'<?php ');
		}
		return true;
	}

	function assign_var_from_handle($varname, $handle, $include_once = true)
	{
		ob_start();
		$valid = $this->display($handle, $include_once);
		$varval = ob_get_contents();
		ob_end_clean();
		if ($valid) $this->assign_var($varname, $varval);
		return $valid;
	}

	// Load a compiled template if possible, if not, recompile it
	function _tpl_load(&$handle)
	{
		$pos = null;
  // If we don't have a file assigned to this handle, die.
		if (!isset($this->files[$handle])) {
			trigger_error("template->_tpl_load(): No file specified for handle $handle", E_USER_ERROR);
		}

		if (!file_exists(BASEDIR.$this->files[$handle])) {
			trigger_error('template->_tpl_load(): '.($this->files[$handle]).' doesn\'t exist', E_USER_NOTICE);
			$this->files[$handle] = 'themes/default/template/'.$this->filename[$handle];
			$this->_tpldata['.'][0]['THEME_PATH'] = 'themes/default';
			$this->cachepath = 'cache/tpl_default_';
			if (!file_exists(BASEDIR.$this->files[$handle]) && $pos = strpos($this->filename[$handle], '/') && is_dir(BASEDIR.'modules/'.substr($this->filename[$handle],0,$pos).'/template')) {
				$this->files[$handle] = 'modules/'.substr($this->filename[$handle],0,$pos).'/template/'.substr($this->filename[$handle],$pos+1);
				$this->_tpldata['.'][0]['THEME_PATH'] = 'modules/'.substr($this->filename[$handle],0,$pos);
			}
		} else {
			$this->_tpldata['.'][0]['THEME_PATH'] = 'themes/'.$this->tpl;
			$this->cachepath = 'cache/tpl_'.$this->tpl.'_';
		}

		$filename = preg_replace('#\/#m', '#', $this->filename[$handle]);
		$filename = $this->cachepath.$filename.'.inc';

		// Don't recompile page if the original template is older then the compiled cache
		if (file_exists($filename) && filemtime($filename) > filemtime($this->files[$handle])) {
			return $filename;
		}

		$this->_tpl_load_file($handle);
		return false;
	}

	// Load template source from file
	function _tpl_load_file($handle)
	{
		// Try and open template for read
		if (!($fp = fopen(BASEDIR.$this->files[$handle], 'r'))) {
			trigger_error("template->_tpl_load(): File ".$this->files[$handle]." does not exist or is empty", E_USER_ERROR);
		}
		require_once(CORE_PATH.'classes/template_enc.php');
		$this->compiled_code[$handle] = tpl_encode::compile(trim(fread($fp, filesize($this->files[$handle]))));
		fclose($fp);
		// Actually compile the code now.
		tpl_encode::compile_write($handle, $this->compiled_code[$handle]);
	}

	// Assign key variable pairs from an array
	function assign_vars($vararray)
	{
		foreach ($vararray as $key => $val) {
			$this->_tpldata['.'][0][$key] = $val;
		}
		return true;
	}

	// Assign a single variable to a single key
	function assign_var($varname, $varval)
	{
		$this->_tpldata['.'][0][$varname] = $varval;
		return true;
	}

	// Assign key variable pairs from an array to a specified block
	function assign_block_vars($blockname, $vararray)
	{
		if (strstr($blockname, '.')) {
			// Nested block.
			$blocks = explode('.', $blockname);
			$blockcount = count($blocks) - 1;
			$str = &$this->_tpldata;
			for ($i = 0; $i < $blockcount; $i++)  {
				$str = &$str[$blocks[$i]]; 
				$str = &$str[(is_countable($str) ? count($str) : 0) - 1]; 
			} 
			// Now we add the block that we're actually assigning to.
			// We're adding a new iteration to this block with the given
			// variable assignments.
			$str[$blocks[$blockcount]][] = &$vararray;
		} else {
			// Top-level block.
			// Add a new iteration to this block with the variable assignments
			// we were given.
			$this->_tpldata[$blockname][] = &$vararray;
		}
		return true;
	}
	function unset_block($blockname)
	{
		if (strstr($blockname, '.')) { trigger_error('It\'s only allowed to unset toplevel blocks', E_USER_ERROR); }
		if (isset($this->_tpldata[$blockname])) { unset($this->_tpldata[$blockname]); }
		return true;
	}

	// Include a seperate template
	function _tpl_include($filename, $include = true)
	{
		$handle = $filename;
		$this->filename[$handle] = $filename;
		$this->files[$handle] = $this->root . '/' . $filename;
		$filename = $this->_tpl_load($handle);
		if ($include) {
			if ($filename) {
				include_once($filename);
				return;
			}
			eval(' ?>' . $this->compiled_code[$handle] . '<?php ');
		}
	}

}
