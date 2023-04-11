<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2008 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('cache')) { die('Access Denied'); }
if (!defined('_BROWSE_UP')) { define('_BROWSE_UP', 'One level up'); }
if (!defined('_BROWSE')) { define('_BROWSE', 'Browse'); }

\Dragonfly\Page::title('Caching');

$K = \Dragonfly::getKernel();
$OUT = $K->OUT;

if (isset($_GET['browse'])) {
	\Dragonfly\Output\Css::add('poodle/tree');
	\Dragonfly\Output\Js::add('includes/poodle/javascript/tree.js');
	$OUT->cache_tree = array();
	foreach ($K->CACHE->listAll() as $file) {
		$p = &$OUT->cache_tree;
		$file = explode('/',$file);
		$l = count($file) - 1;
		foreach ($file as $i => $name) {
			if ($i < $l) {
				if (!isset($p[$name])) {
					$p[$name] = array();
				}
				$p = &$p[$name];
			} else {
				$p[] = $name;
			}
		}
	}
	$OUT->display('admin/cache/browse');

} else {
	$current = strtolower(get_class($K->CACHE));
	$OUT->caching = array(
		'installed' => array(
			'name' => $current::INFO_NAME,
			'desc' => $current::INFO_DESC,
			'url'  => $current::INFO_URL,
		),
		'supported' => array(),
	);
	foreach (glob('includes/poodle/cache/adapter/*.php') as $file) {
		$class = strtolower(str_replace('/','\\',substr($file, 9, -4)));
		if ($current !== $class) {
			$OUT->caching['supported'][] = array(
				'name' => $class::INFO_NAME,
				'desc' => $class::INFO_DESC,
				'url'  => $class::INFO_URL,
			);
		}
	}
	$OUT->display('admin/cache/index');
}
