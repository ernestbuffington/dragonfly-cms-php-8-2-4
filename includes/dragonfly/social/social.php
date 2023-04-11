<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly;

class Social implements \DFService
{

	# DFService
	//public static function initAs() { return 'Social'; }
	public function priority() { return 80; }
	public function runlevel() { return array(\DF::BOOT_HEADER); }

	public function update()
	{
		if (!defined('ADMIN_PAGES') && 'disabled' !== $this->config->mode && count($this->plugins))
		{

			foreach ($this->adapters as $k => $v) {
				if ($this->adapters->$k->active) $this->adapters->$k->htmlHeadTags();
			}

			switch ($this->config->mode)
			{
				case 'classic':
				case 'grouped':
					\Dragonfly\Output\Css::add('share');
					\Dragonfly\Output\Js::add('includes/javascript/shares.js');
					$ret = array();
					foreach ($this->plugins as $k => $v) {
						$ret[$k] = array_map('utf8_encode', \Dragonfly\Social\Plugins::$data[$k]);
					}
					\Dragonfly\Output\Js::inline('var SHARERS='.str_replace('\\/', '/', json_encode($ret)).';');
					break;

				case 'inline_counter':
				case 'top_counter':
					foreach ($this->adapters as $k => $v) {
						if ($this->adapters->$k->active) $this->adapters->$k->loadApi();
					}
					break;
			}
		}
	}

	# Dragonfly_Social
	private
		$args,
		$config,
		$plugins,
		$adapters,
		$install = array('mode' => 'disabled', 'adapters' => '');

	public function __construct()
	{
		$CFG = \Dragonfly::getKernel()->CFG;
		if (!$CFG->social) {
			foreach ($this->install as $k => $v) {
				$CFG->add('social', $k, $v);
			}
		}
		$this->config = $CFG->social;
		$this->plugins = new \Dragonfly\Social\Extender();
		$this->adapters = new \Dragonfly\Social\Extender();
		$this->config->adapters = $this->config->adapters ? explode(',', $this->config->adapters) : array();

		if (defined('ADMIN_PAGES')) {
			foreach (\Dragonfly\Social\Plugins::$data as $k => $v) {
				$this->plugins->$k = new \Dragonfly\Social\Extender($v);
				$this->plugins->$k->active = !empty($CFG->social_plugin->$k);
			}
		} else {
			# not installed or disabled
			if (!isset($CFG->social_plugin) || 'disabled' === $this->config->mode) return;
			foreach ($CFG->social_plugin as $k => $v) {
				if ($v) $this->plugins->$k = new \Dragonfly\Social\Extender(\Dragonfly\Social\Plugins::$data[$k]);
			}
		}

		foreach ($this->config->adapters as $k => $v) {
			$class = 'Dragonfly\\Social\\Adapter\\'.$v;
			$this->load(new $class, $v);
		}
	}

	function __get($k)
	{
		switch ($k) {
			case 'plugins':
			case 'adapters':
			case 'config':
			return $this->$k;
		}
	}

	public function GET()
	{
		if (!defined('ADMIN_PAGES')) return;

		$CFG = \Dragonfly::getKernel()->CFG;
		$TPL = \Dragonfly::getKernel()->OUT;
		$TPL->social = $this;

		$files = array();
		$search = new \FilesystemIterator(__DIR__ . '/adapter', \FilesystemIterator::SKIP_DOTS);

		foreach ($search as $file) {
			$files[] = ucfirst(substr($file->getFilename(), 0, -4));
		}
		//sort($files);

		# adapter files deleted
		if ($deleted = array_diff($this->config->adapters, $files)) {
			foreach ($deleted as $name) {
				$CFG->delete($name);
				unset($this->adapters->$name);
			}
		}

		# adapter files installed
		if ($installed = array_diff($files, $this->config->adapters)) {
			foreach ($installed as $name) {
				$class = 'Dragonfly\\Social\\Adapter\\'.$name;
				$this->load(new $class, $name);
			}
		}

		if ($deleted || $installed) $CFG->set('social', 'adapters', implode(',', $files));

		\Dragonfly\Output\Js::add('themes/default/javascript/toggle.js');

		$TPL->form = new \Dragonfly\Social\Extender();
		$TPL->form->mode = array(
			'disabled' => 'Disabled',
			'classic' => 'Classic',
			'compact' => 'Compact',
			'grouped' => 'Grouped',
			'inline_counter' => 'Inline Counter',
			'top_counter' => 'Top Counter'
		);

		$TPL->display('dragonfly/social/admin/index');
	}

	public function POST()
	{
		if (!defined('ADMIN_PAGES')) return;
		$Social = \Dragonfly::getKernel()->SOCIAL;
		$CFG = \Dragonfly::getKernel()->CFG;

		if ($_POST->map('plugins')) {
			foreach (\Dragonfly\Social\Plugins::$data as $k => $v) $CFG->set('social_plugin', $k , isset($_POST['plugins'][$k]));
			\URL::redirect(\URL::admin('social#social-plugins'));
		}
		else if ($_POST->map('adapters')) {
			$name = key($_POST->txt('adapters'));
			foreach ($this->adapters->$name->install as $k => $v) {
				$CFG->set($name, $k, $_POST['adapters'][$name][$k]);
			}
			\URL::redirect(\URL::admin('social#social-adapters'));
		}
		else if ($_POST->map('config')) {
			foreach ($this->config as $k => $v) $CFG->set('social', $k , $_POST->txt('config', $k));
		}
		\URL::redirect(\URL::admin('social'));
	}

	private function load(\Dragonfly\Social\Adapter $class, $name)
	{
		$this->adapters->$name = $class;
	}

	public function updateArgs(array $args=array())
	{
		$this->args = array();
		$this->args['u'] = empty($args['url']) ? BASEHREF.\URL::canonical() : $args['url'];
		$this->args['t'] = strip_tags(empty($args['title']) ? \Dragonfly\Page::get('title') : $args['title']);
		if (!empty($args['image'])) $this->args['i'] = $args['image'];
		# nullables
		if (isset($args['desc'])) $this->args['d'] = strip_tags($args['desc']);
		if (isset($args['ref']))  $this->args['r'] = $args['ref'];
	}

	public function htmlHeadTags()
	{
		foreach ($this->adapters as $k => $v) {
			if ($this->adapters->$k->active) $this->adapters->$k->htmlHeadTags();
		}
	}

	public function html5Buttons() {
		$ret = '';
		foreach ($this->adapters as $k => $v) {
			if ($this->adapters->$k->active) $ret .= $this->adapters->$k->html5Button();
		}
		return $ret;
	}

	public function getSocial(array $args=array())
	{
		if (!count($this->plugins)) return;

		if      ('classic' === $this->config->mode) {}
		else if ('compact' === $this->config->mode) {}
		else if ('grouped' === $this->config->mode) {
				$this->updateArgs($args);
				$ret = str_replace('\\/', '/', json_encode($this->args));
				$this->args = array();
				return $ret;
		}
		else if ('inline_counter' === $this->config->mode) {
			$this->htmlHeadTags();
			return $this->html5Buttons();
		}
		else if ('top_counter' === $this->config->mode) {}
	}

	public function addImage($img) {
		if (isset($this->adapters->Opengraph)) $this->adapters->Opengraph->addImage($img);
	}
}

