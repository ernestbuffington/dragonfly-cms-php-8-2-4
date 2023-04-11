<?php
/*********************************************
  CPG-NUKE: Advanced Content Management System
  ********************************************
  Copyright (c) 2004 by CPG-Nuke Dev Team
  http://www.cpgnuke.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version
***********************************************************************/

function make_jumpbox($action, $match_forum_id = 0)
{
	$lang = \Dragonfly::getKernel()->OUT->L10N;
	$categories = BoardCache::categories();
	$boxstring = '<select name="f" onchange="if(this.options[this.selectedIndex].value != -1){ this.form.submit() }">';
	if ($categories) {
		$boxstring .= '<option value="-1">'.$lang['Select_forum'].'</option>';
		$forum_rows = array_values(BoardCache::forums_rows());
		if ($total_forums = count($forum_rows)) {
			foreach ($categories as $category) {
				$boxstring_forums = '';
				for ($j = 0; $j < $total_forums; $j++) {
					if ($forum_rows[$j]['cat_id'] == $category['id'] && $forum_rows[$j]['auth_view'] <= AUTH_REG) {
						$selected = ($forum_rows[$j]['forum_id'] == $match_forum_id) ? ' selected="selected"' : '';
						$boxstring_forums .=  '<option value="'.$forum_rows[$j]['forum_id'].'"'.$selected.'>'.htmlspecialchars($forum_rows[$j]['forum_name']).'</option>';
					}
				}
				if ($boxstring_forums) {
					$boxstring .= '<optgroup label="'.htmlspecialchars($category['title']).'">' . $boxstring_forums . '</optgroup>';
				}
			}
		}
	}
	$boxstring .= '</select>';
	\Dragonfly::getKernel()->OUT->assign_vars(array(
		'L_JUMP_TO' => $lang['Jump_to'],
		'L_SELECT_FORUM' => $lang['Select_forum'],
		'L_GO' => $lang['Go'],

		'S_JUMPBOX_SELECT' => $boxstring,
		'S_JUMPBOX_ACTION' => URL::index("&file={$action}")
	));
	return;
}

function make_forum_select($box_name, $ignore_forum = false, $select_forum = '')
{
	$lang = \Dragonfly::getKernel()->OUT->L10N;
	$categories = BoardCache::categories();
	$boxstring = '';
	if ($categories) {
		$forum_rows = array_values(BoardCache::forums_rows());
		if ($forum_rows) {
			$is_auth_ary = \Dragonfly\Forums\Auth::read(0);
			$boxstring .= '<option value="">'.$lang['Select_forum'].'</option>';
			foreach ($categories as $category) {
				$boxstring_forums = '';
				foreach ($forum_rows as $forum) {
					$forum_id = $forum['forum_id'];
					if ($forum['cat_id'] == $category['id'] && $is_auth_ary[$forum_id]['auth_read'] && $ignore_forum != $forum_id) {
						$selected = ($forum_id == $select_forum) ? ' selected="selected"' : '';
						$boxstring_forums .=  '<option value="'.$forum_id.'"'.$selected.'>'.htmlspecialchars($forum['forum_name']).'</option>';
					}
				}
				if ($boxstring_forums) {
					$boxstring .= '<optgroup label="'.htmlspecialchars($category['title']).'">' . $boxstring_forums . '</optgroup>';
				}
			}
		}
	}
	return $boxstring ? '<select name="' . $box_name . '">' . $boxstring . '</select>' : '<strong>-- ! No Forums ! --</strong>';
}

function get_forums_images()
{
	static $images;
	if (!$images) {
		$OUT = \Dragonfly::getKernel()->OUT;
		$template_name = $OUT->theme;
		$map_file = "themes/{$template_name}/images/forums/_map.cfg";
		if (!is_file($map_file)) {
			$map_file = "themes/{$template_name}/template/forums/images.cfg";
			if (!is_file($map_file)) {
				$template_name = 'default';
				$map_file = "themes/{$template_name}/images/forums/_map.cfg";
			}
		}
		$current_template_path = "themes/{$template_name}/images/forums";
		include_once $map_file;
		if (!defined('TEMPLATE_CONFIG')) {
			trigger_error("Could not open {$template_name} template config file", E_USER_ERROR);
		}
		$img_lang = is_file(realpath($current_template_path.'/lang_'.$OUT->L10N->lng)) ? $OUT->L10N->lng : 'en';
		foreach ($images as $key => $value) {
			if (!is_array($value)) {
				$images[$key] = str_replace('{LANG}', 'lang_'.$img_lang, $value);
			}
		}
	}
	return $images;
}

# Pagination routine, generates page number sequence
function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = true, $uri_cb = 'index')
{
	if (ceil($num_items/$per_page) == 1) { return ''; }
	return generate_pagination_from_class(
		new \Poodle\Pagination(URL::$uri_cb($base_url.'&start=${offset}'), $num_items, $start_item, $per_page),
		$add_prevnext_text
	);
}

function generate_pagination_from_class(\Poodle\Pagination $pag, $add_prevnext_text = true)
{
	$items = $pag->items();
	if (!$items) { return ''; }
	$page_string = '';
	foreach ($items as $item) {
		if ($item['current']) {
			$page_string .= '<b>'.$item['page'].'</b>, ';
		} else if ($item['uri']) {
			$page_string .= '<a href="'.htmlspecialchars($item['uri']).'">'.$item['page'].'</a>, ';
		} else {
			$page_string = trim($page_string,', ') . ' ' . $item['page'] . ' ';
		}
	}
	$page_string = trim($page_string,', ');

	$lang = \Dragonfly::getKernel()->OUT->L10N;
	if ($add_prevnext_text) {
		if ($url = $pag->prev()) {
			$page_string = '<a href="'.htmlspecialchars($url).'">'.$lang['Previous'].'</a> '.$page_string;
		}
		if ($url = $pag->next()) {
			$page_string .= ' <a href="'.htmlspecialchars($url).'">'.$lang['Next'].'</a>';
		}
	}

	return $lang['Goto_page'].' '.$page_string;
}

# Pagination routine, generates page number sequence
function generate_admin_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = TRUE)
{
	return generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text, 'admin');
}

# Obtain list of naughty words and build preg style replacement arrays for use by the
# calling script, note that the vars are passed as references this just makes it easier
# to return both sets of arrays
function obtain_word_list(&$orig_word, &$replacement_word)
{
	$db = \Dragonfly::getKernel()->SQL;
	$result = $db->query("SELECT word, replacement FROM	{$db->TBL->bbwords}");
	while ($row = $result->fetch_row()) {
		$orig_word[] = '#\b('.str_replace('\*', '\w*?', preg_quote($row[0], '#')).')\b#i';
		$replacement_word[] = $row[1];
	}
	return true;
}

# This is general replacement for die(), allows templated
# output in users (or default) language, etc.

# $msg_code can be one of these constants:

# GENERAL_MESSAGE : Use for any simple text message, eg. results
# of an operation, authorisation failures, etc.

# GENERAL ERROR : Use for any error which occurs _AFTER_ the
# common.php include and session code, ie. most errors in pages/functions

function message_die($msg_code, $msg_text = '', $msg_title = '')
{
	$K = \Dragonfly::getKernel();
	$db = $K->SQL;
	$template = $K->OUT;
	$lang = $template->L10N;

	if (defined('HAS_DIED')) {
		exit("message_die() was called multiple times. This isn't supposed to happen.");
	}

	define('HAS_DIED', 1);

	# If the header hasn't been output then do it
	if (!defined('ADMIN_PAGES')) {
		$temp = (false !== stripos($msg_text, '<br />')) ? explode('<br />', $msg_text) : explode('.', $msg_text);
		\Dragonfly\Page::title($msg_title ? strip_tags($msg_title) : strip_tags($temp[0]));
		require_once 'includes/phpBB/page_header.php';
	}

	$Debugger = \Dragonfly::getKernel()->DEBUGGER;
	switch ($msg_code)
	{
		case GENERAL_MESSAGE:
			if (!$msg_title) {
				$msg_title = (!empty($lang[$msg_text])) ? $lang[$msg_text] : $msg_text;
				$msg_title = ((empty($msg_title)) && (!empty($msg_text))) ? $msg_text : $lang['Information'];
			}
			#$Debugger->error_handler(E_USER_WARNING, $msg_title.'<br />'.$msg_text)
			break;

		case GENERAL_ERROR:
			if (!empty($lang[$msg_text])) {
				$msg_text = $lang[$msg_text];
			}
			if (!$msg_text)  { $msg_text = $lang['An_error_occured']; }
			if (!$msg_title) { $msg_title = $lang['General_Error']; }
			break;
	}

	if (!empty($lang[$msg_text])) {
		$msg_text = $lang[$msg_text];
	}
	if (defined('ADMIN_PAGES')) {
		cpg_error($msg_text, $msg_title);
	} else {
		$template->assign_vars(array(
			'MESSAGE_TITLE' => $msg_title,
			'MESSAGE_TEXT' => $msg_text
		));
		$template->display('forums/message_body');
		require_once('footer.php');
	}
	return false;
}
