<?php
/*********************************************
  Copyright (c) 2011 by Dragonfly CMS
  https://dragonfly.coders.exchange
  Released under GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Admin;

class Cp
{

	protected static $id;
	protected static $title;
	protected static $url;
	protected static $list_num;
	protected static $list_handle;

	public static function sectionTitle($title)
	{
		self::$title = $title;
		\Dragonfly::getKernel()->OUT->assign_vars(array(
			'S_ACP_PAGE' => $title,
			'U_ACP_PAGE' => \URL::admin()
		));
	}

	public static function sectionMenu($handle, array $items)
	{
		$handle = (string) $handle;
		if (empty($items) || empty($handle)) return;

		global $Module;
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->set_handle($handle, "admin/$handle.html");

		foreach ($items as $key => $title) {
			$OUT->assign_block_vars($handle, array(
				'S_NAME' => $title,
				'U_DETAILS' => \URL::admin($Module->op.'&'.$key),
				'B_GET' => isset($_GET[$key]),
			));
		}
	}

	public static function list_header($args)
	{
		$args = func_get_args();
		self::$list_num = count($args);
		if (3 > self::$list_num) return;
		self::$list_handle = array_shift($args);
		--self::$list_num;

		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->set_handle('list', 'admin/list.html');
		foreach ($args as $arg) {
			$OUT->assign_block_vars(self::$list_handle.'_header', array(
				'S_TITLE' => $arg,
			));
		}
	}

	public static function list_value($args, $row_css='')
	{
//		$args = func_get_args();
		if (count($args) !== self::$list_num) {
			trigger_error('List header count does not match value count');
			return;
		}

		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->assign_block_vars('list_values', array(
			'S_CLASS' => $row_css ? ' '.$row_css : ''
		));
		foreach ($args as $key => $arg) {
			$css = '';
			switch (key($arg)) {
				case 'text':
					$data = $arg['text'];
					break;
				case 'url':
					if (empty($arg['url'])) { $data = $arg['text']; break; }
					$data = '<a href="'.$arg['url'].'" title="';
					$data .= isset($arg['title']) ? $arg['title'] : (isset($arg['text']) ? $arg['text'] : '') .'">';
					if (isset($arg['src'])) {
						$data .= '<img src="'.$arg['src'].'"';
						$data .= isset($arg['title']) ? ' title="'.$arg['title'].'"' : '';
						$data .= isset($arg['alt']) ? ' alt="'.$arg['alt'].'" />' : '/>';
					} else {
						$data .= $arg['text'];
					}
					$data .= '</a>';
					break;
				case 'img':
					$data = '<img src="'.$arg['src'].'"';
					$data .= isset($arg['title']) ? ' title="'.$arg['title'].'"' : '';
					$data .= isset($arg['alt']) ? ' alt="'.$arg['alt'].'" />' : '/>';
					break;
				case 'quick':
					$data = '<form method="post" action="'.$arg['action'].'">';
					$data .= '<input type="hidden" name="'.$arg['quick'].'" value="'.$arg['value'].'" />';
					$data .= isset($arg['change_to']) ? '<input type="hidden" name="change_to" value="'.$arg['change_to'].'" />' : '';
					$data .= !empty($arg['return_url']) ? '<input type="hidden" name="return_url" value="'.$arg['return_url'].'" />' : '';
					$data .= '<input type="submit" value="'.$arg['text'].'"';
					$data .= isset($arg['disabled']) && $arg['disabled'] ? ' disabled="disabled"' : '';
					$data .= ' /></form>';
					break;
				case 'css':
					$css = !empty($arg['css']) ? ' '.$css : '';
					break;
				default:
					continue;
			}
			$OUT->assign_block_vars('list_values.item', array(
				'S_DATA' => $data,
				'S_CLASS' => $css
			));
		}

	}

}
