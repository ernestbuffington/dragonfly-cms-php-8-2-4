<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

function yesno_option($name, $value=0)
{
	trigger_deprecated('Use \\Dragonfly\\Output\HTML::' . __FUNCTION__ . '()');
	return \Dragonfly\Output\HTML::{__FUNCTION__}($name, $value);
}

function select_option($name, $default, array $options)
{
	trigger_deprecated('Use \\Dragonfly\\Output\HTML::' . __FUNCTION__ . '()');
	return \Dragonfly\Output\HTML::{__FUNCTION__}($name, $default, $options);
}

function select_box($name, $default, array $options)
{
	trigger_deprecated('Use \\Dragonfly\\Output\HTML::' . __FUNCTION__ . '()');
	return \Dragonfly\Output\HTML::{__FUNCTION__}($name, $default, $options);
}

function select_box_group($name, $default, array $groups)
{
	trigger_deprecated('Use \\Dragonfly\\Output\HTML::' . __FUNCTION__ . '()');
	return \Dragonfly\Output\HTML::{__FUNCTION__}($name, $default, $groups);
}

function open_form($link='', $form_name=false, $legend=false, $tborder=false)
{
	trigger_deprecated('Use \\Dragonfly\\Output\HTML::' . __FUNCTION__ . '()');
	return \Dragonfly\Output\HTML::{__FUNCTION__}($link, $form_name, $legend, $tborder);
}

function close_form()
{
	trigger_deprecated('Use \\Dragonfly\\Output\HTML::' . __FUNCTION__ . '()');
	return \Dragonfly\Output\HTML::{__FUNCTION__}();
}

function group_selectbox($fieldname, $current=0, $mvanon=false, $all=true)
{
	trigger_deprecated('Use \\Dragonfly\\Output\HTML::' . __FUNCTION__ . '()');
	return \Dragonfly\Output\HTML::{__FUNCTION__}($fieldname, $current, $mvanon, $all);
}

function cpg_delete_msg($link, $msg, $hidden='')
{
	trigger_deprecated('Use \Dragonfly\Page::confirm()');
	\Dragonfly\Page::confirm($link, $msg, $hidden);
}

function pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext=TRUE, $hide_on_empty=TRUE)
{
	function pagination_page($page, $url = false, $current = false) {
		\Dragonfly::getKernel()->OUT->assign_block_vars('pagination', array('PAGE' => $page, 'URL' => $current ? false : $url, 'current' => $current));
	}
	function pagination_link($url) {
		if (defined('ADMIN_PAGES')) { return URL::admin($url); }
		return URL::index($url);
	}
	$total_pages = ceil($num_items/$per_page);
	$on_page = floor($start_item / $per_page);
	if ($total_pages < 2 && $hide_on_empty) { return \Dragonfly::getKernel()->OUT->B_PAGINATION = false; }
	\Dragonfly::getKernel()->OUT->assign_vars(array(
		'B_PAGINATION' => true,
		'PAGINATION_PREV' => ($add_prevnext && $on_page > 1) ? pagination_link($base_url.(($on_page-1)*$per_page)) : false,
		'PAGINATION_NEXT' => ($add_prevnext && $on_page < $total_pages) ? pagination_link($base_url.($on_page+$per_page)) : false,
		'L_PREVIOUS' => _PREVIOUSPAGE,
		'L_NEXT' => _NEXTPAGE,
	));
	if ($total_pages > 10) {
		$init_page_max = ($total_pages > 3) ? 3 : $total_pages;
		for ($i = 1; $i <= $init_page_max; ++$i) {
			pagination_page($i, pagination_link($base_url.($i*$per_page)), $i == $on_page);
		}
		if ($total_pages > 3) {
			if ($on_page > 1 && $on_page < $total_pages) {
				if ($on_page > 5) { pagination_page(' ... '); }
				$init_page_min = ($on_page > 4) ? $on_page : 5;
				$init_page_max = ($on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;
				for ($i = $init_page_min - 1; $i < $init_page_max + 2; ++$i) {
					pagination_page($i, pagination_link($base_url.($i*$per_page)), $i == $on_page);
				}
				if ($on_page < $total_pages-4) { pagination_page(' ... '); }
			} else {
				pagination_page(' ... ');
			}
			for ($i = $total_pages - 2; $i <= $total_pages; ++$i) {
				pagination_page($i, pagination_link($base_url.($i*$per_page)), $i == $on_page);
			}
		}
	} else {
		for ($i = 1; $i <= $total_pages; ++$i) {
			pagination_page($i, pagination_link($base_url.($i*$per_page)), $i == $on_page);
		}
	}
}
