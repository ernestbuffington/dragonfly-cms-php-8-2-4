<?php
/*********************************************
  Copyright (c) 2011 by DragonflyCMS
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Modules;

class Module
{

	public $sides, $title;

	public static $custom = array();
	protected static $allow_access = array();

	/* read only */
	protected $active, $cat_id, $chroot, $file, $in_home, $https = true, $inmenu;
	protected $mid, $name, $name_lc, $pos, $uninstall, $version, $view;

	private $data = array(), $list = array();

	public function __get($p) {
		switch ($p) {
			case 'id':
				return $this->mid;
			case 'active':
			case 'cat_id':
			case 'chroot':
			case 'file':
			case 'in_home':
			case 'inmenu':
			case 'mid':
			case 'name':
			case 'name_lc':
			case 'pos':
			case 'sides':
			case 'uninstall':
			case 'version':
			case 'view':
				return $this->$p;
			default: return;
		}
	}

	# $name: the module name, case insensitive
	# $file: file to request inside the module, case insensitive
	# $http: true to look for http requests, false to create a virtual module and access all its informations
	public function __construct($name, $file, $http=true)
	{
		$default = ('op' === $name && defined('ADMIN_PAGES')) ? 'index' : \Dragonfly::getKernel()->CFG->global->main_module;
		if ($http) {
			$this->name = $_GET->text($name) ?: $_POST->text($name) ?: $default;
			$this->file = $_GET->text($file) ?: $_POST->text($file);
		} else {
			$this->name = $name;
			$this->file = $file;
		}
		if (!preg_match('#^[a-z][a-z0-9_\-]*$#i', $this->name)) {
			trigger_error(sprintf(_ERROR_BAD_CHAR, strtolower(_MODULES)));
			cpg_error('', 404);
		}
		if ($this->file && !preg_match('#^[a-z][a-z0-9_\-]*$#i', $this->file)) {
			trigger_error(sprintf(_ERROR_BAD_CHAR, \Dragonfly::getKernel()->L10N->get('file')));
			cpg_error('', 404);
		}
		if ('op' === $name && defined('ADMIN_PAGES')) {
			$this->loadAdmin();
		} else {
			$this->loadModule();
		}
	}

	# Create a new object as, $Example = new Module('example'), tip use module name for new objects
	# Fatal error check: $Example->allow()
	# Skip fatal error with $Example->allow(true)
	public function allow($check=false)
	{
		if (can_admin() || $this->in_home || 'Your_Account' === $this->name || in_array($this->name, self::$allow_access)) { return true; }
		if (!$this->active && !can_admin($this->name_lc)) {
			$error = sprintf(_MODULENOEXIST, '');
			if ($check) { return $error; }
			cpg_error($error, 404);
		}
		elseif (1 === $this->view && !is_user()) {
			$error = _MODULEUSERS.(\Dragonfly::getKernel()->CFG->member->allowuserreg ? _MODULEUSERS2 : '' );
		}
		elseif (2 === $this->view && !can_admin($this->name)) {
			$error = _MODULESADMINS;
		}
		elseif (3 < $this->view && !in_group($this->view-3)) {
			$db = \Dragonfly::getKernel()->SQL;
			list($groupName) = $db->uFetchRow("SELECT group_name FROM {$db->TBL->bbgroups} WHERE group_id=".($this->view-3));
			$error = "'{$groupName}' "._MODULESGROUPS;
		}
		if (isset($error)) {
			if ($check) { return $error; }
			cpg_error($error, 403);
		}
		return true;
	}

	# $title: string to search by name
	# return bool
	public function is_active($name)
	{
		$name = strtolower($name);
		return isset($this->list[$name]) && $this->list[$name]['active'] ;
		//return is_array(page_data($name));
	}

	# $id: integer to search by id or string to search by name
	# return bool
	public function exist($title)
	{
		return isset($this->list[$title]);
	}

	public static function get_title($title)
	{
		if (defined('_'. $title)) {
			return constant('_'. $title);
		}
		if (defined('_'. $title. 'LANG')) {
			return constant('_'. $title. 'LANG');
		}
		if (defined('_'. strtoupper($title))) {
			return constant('_'. strtoupper($title));
		}
		return ucwords(str_replace('_', ' ', $title));
	}

	private function loadAdmin()
	{
		$this->mid = -1;
		$this->pos = -1;
		$this->chroot = ADMIN_PATH.'modules/';
		$this->sides = is_admin() ? \Dragonfly\Blocks::ALL : \Dragonfly\Blocks::NONE; # prevents some blocks to display at login
		if (is_file(ADMIN_PATH.'modules/'.$this->name.'.php')) {
			$this->file = $this->name.'.php';
		} else {
			$chroot = \Dragonfly::getModulePath($this->name).'admin/';
			if ($this->file && is_file($chroot.$this->file.'.php')) {
				$this->chroot = $chroot;
				$this->file .= '.php';
			}
			else if ($this->file && is_file($chroot.$this->file.'.inc')) {
				$this->chroot = $chroot;
				$this->file .= '.inc';
			}
			else if (!$this->file && is_file($chroot.'index.inc')) {
				$this->chroot = $chroot;
				$this->file = 'index.inc';
			}
			else {
				$this->name = 'admin';
				$this->file = 'index.php';
			}
		}
		\Dragonfly::getKernel()->L10N->load($this->name,1);
		$this->title = self::get_title(_ADMINISTRATION);
	}

	private function loadModule()
	{
		$K = \Dragonfly::getKernel();
		# temporary path for custom.inc, location and/or name
		# may change in future, not to be shipped with df
		//if (is_file(MODULE_PATH.'custom.inc')) {
			//require_once(MODULE_PATH.'custom.inc');
			foreach (self::$custom as $i => $this->data){
				if ($this->name == self::$custom[$i]['get']) { break; }
				$this->data = array();
			}
			self::$custom = array();
		//}
		if ($this->data) {
			$this->chroot = BASEDIR;
			$this->file   = $this->data['file'];
			$this->name   = $this->data['title'];
			$this->active = true;
		}
		else {
			$this->data = $K->SQL->uFetchAssoc("SELECT * FROM {$K->SQL->TBL->modules} WHERE LOWER(title)=LOWER('{$this->name}')");
			if ($this->data) {
				$this->name   = $this->data['title'];
				$this->path   = \Dragonfly::getModulePath($this->name);
				$this->chroot = $this->path;
				$this->active = (bool)$this->data['active'];
				# module&var could not be used because interpreted as name=module&file=var
				# so make sure file exists, otherwise allow module&var to be used
				if (!$this->file) { $this->file = 'index'; }
				if (!is_file($this->path.$this->file.'.php')) {
					cpg_error('Not found', 404);
				}
				$this->file .= '.php';
			}
		}
		if (!$this->data) { return; }
		\Dragonfly::getKernel()->L10N->load($this->name,1);
		$this->mid       = (int)$this->data['mid'];  # required
		$this->sides     = (int)$this->data['blocks']; # required
		$this->view      = (int)$this->data['view']; # required
		$this->cat_id    = isset($this->data['cat_id'])  ?   (int)$this->data['cat_id']   : null;
		$this->inmenu    = isset($this->data['inmenu'])  ?  (bool)$this->data['inmenu']   : null;
		$this->pos       = isset($this->data['pos'])     ?   (int)$this->data['pos']      : null;
		$this->uninstall = isset($this->data['unistall'])?  (bool)$this->data['uninstall']: null;
		$this->version   = isset($this->data['version']) ? (float)$this->data['version']  : null;
		$this->name_lc   = strtolower($this->data['title']);
		$this->in_home   = $this->name_lc == strtolower($K->CFG->global->main_module);
		$this->title     = self::get_title($this->data['title']);
	}

	private function push($data)
	{
		if (!preg_match('#^([a-zA-Z0-9_\-]+)$#', $data['name'])) {
			cpg_error(sprintf(_ERROR_BAD_CHAR, strtolower(_MODULES)), E_USER_ERROR);
		}
		if (isset($this->list[$data['name']])) {
			trigger_error($data['name'].': Module name already taken.', E_USER_WARNING);
			return;
		}
		$data['title'] = self::get_title($data['name']);
		$this->list[$data['name']] = $data;
		return true;
	}

}
