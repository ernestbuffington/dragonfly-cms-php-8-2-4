<?php
if (!defined('ADMIN_PAGES')) { exit; }

//
// Load default header
//
$no_page_header = TRUE;

//check for confirmed remove
if( isset($_POST['confirm_remove']) ) {
	$icon_id = $_POST['icon_id'];
	if (isset ($icon_id)) {
		//remove this icon from all topics that use it
		$db->sql_query("UPDATE ".TOPICS_TABLE." SET icon_id = NULL WHERE icon_id = $icon_id");
		//remove this icon
		$db->sql_query("DELETE FROM ".TOPIC_ICONS_TABLE." WHERE icon_id = $icon_id");
	}
}

$lang_icons_admin['remove001'] = 'Remove Icon';
$lang_icons_admin['remove002'] = 'Are you sure you want to remove the icon:';
$lang_icons_admin['remove003'] = 'from the forum titled:';
$lang_icons_admin['remove004'] = "from the global icons?";
$lang_icons_admin['remove005'] = 'Yes, remove it';

//check for remove request
if(isset($_GET['remove'])) {
	$icon_id = $_GET['id'];
	if ( !empty($icon_id) ) {
		$template->set_filenames(array('body' => 'forums/admin/topic_icons_remove_body.html'));
		//grab the forum_id and the url of the icon so we can confirm it
		$db->sql_query("SELECT icon_url, forum_id FROM ".TOPIC_ICONS_TABLE." WHERE icon_id = $icon_id");
		$icon_row = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		$forum_id = intval($icon_row[0]['forum_id']);
		$icon_url = $icon_row[0]['icon_url'];
		
		//global
		if ($forum_id == -1) {
			$forum_text = $lang_icons_admin['remove004'];
		} else {
			//forum specific
			//grab the forum name so we can confirm it
			$db->sql_query("SELECT forum_name FROM " . FORUMS_TABLE . " WHERE forum_id = $forum_id");
			$forum_row = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			
			$forum_name = $forum_row[0]['forum_name'];
			$forum_text = $lang_icons_admin['remove003'];
		}
		
		$template->assign_vars(array(
			'L_TITLE' => $lang_icons_admin['remove001'],
			'L_EXPLAIN1' => $lang_icons_admin['remove002'],
			'L_EXPLAIN2' => $forum_text,
			'L_CONFIRM_REMOVE' => $lang_icons_admin['remove005'],
			'ICON_TO_REMOVE_SRC' => $icon_url,
			'FORUM_TO_REMOVE_FROM' => $forum_name,
			'S_ACTION' => adminlink("&amp;do=topic_icons"),
			'S_HIDDEN_FIELDS' => '<input type="hidden" name="icon_id" value="' . $icon_id . '">',
			)
		);

		return;
	}
}

//check for add request
if(isset($_POST['addicon']))
{
	$forum_ids = $_POST['forum_id_list'];
	$icon_name = Fix_Quotes($_POST['icon_name']);
	$icon_path = $_POST['icon_path'];

	$global = $_POST['addglobal'];
	
	//add global
	if ( isset($global) && !empty($icon_name) && !empty($icon_path)) {
		$db->sql_query("INSERT INTO ".TOPIC_ICONS_TABLE." (forum_id, icon_url, icon_name) VALUES(-1, '$icon_path', '$icon_name')");
	} else if ( !empty($forum_ids) && !empty($icon_name) && !empty($icon_path)) {
		//add forum specific
		//create the icon for each forum
		for ($i = 0; $i < count($forum_ids); $i++) {
			$forum_id = intval($forum_ids[$i]);
			$db->sql_query("INSERT INTO ".TOPIC_ICONS_TABLE." (forum_id, icon_url, icon_name) VALUES($forum_id, '$icon_path', '$icon_name')");
		}
	}
}

//pinched from elsewhere. Draws up the forums and categories.
$q_categories = $db->sql_query("SELECT cat_id, cat_title, cat_order FROM " . CATEGORIES_TABLE . " ORDER BY cat_order");
if( $total_categories = $db->sql_numrows($q_categories) ) {
	$category_rows = $db->sql_fetchrowset($q_categories);
	$q_forums = $db->sql_query("SELECT * FROM " . FORUMS_TABLE . " ORDER BY cat_id, forum_order");
	if( $total_forums = $db->sql_numrows($q_forums) ) {
		$forum_rows = $db->sql_fetchrowset($q_forums);
	}

	//
	// Okay, let's build the index
	//
	$gen_cat = array();
	for($i = 0; $i < $total_categories; $i++) {
		$cat_id = $category_rows[$i]['cat_id'];
		$template->assign_block_vars("catrow", array(
			'CAT_ID' => $cat_id,
			'CAT_DESC' => $category_rows[$i]['cat_title'])
		);
		for($j = 0; $j < $total_forums; $j++) {
			$forum_id = $forum_rows[$j]['forum_id'];
			if ($forum_rows[$j]['cat_id'] == $cat_id) {
				$template->assign_block_vars("catrow.forumrow",	   array(
					'FORUM_NAME' => $forum_rows[$j]['forum_name'],
					'FORUM_DESC' => $forum_rows[$j]['forum_desc'],
					'U_VIEWFORUM' => getlink("&amp;file=viewforum&amp;" . POST_FORUM_URL . "=$forum_id"),
					'FORUM_ID' => $forum_id)
				);
				
				//get custom icons for this forum
				$topic_icons = get_topic_icons($forum_id, false);

				//and display them
				while( list($key, $val) = each($topic_icons) ) {
					$template->assign_block_vars('catrow.forumrow.icon', array(
					'ICON_SRC' => $val['icon_url'],
					'U_REMOVE_ICON' => adminlink("&amp;do=topic_icons&amp;remove=true&amp;id=" . $val['icon_id']))
					);
				}

			}// if ... forumid == catid
			
		} // for ... forums

	} // for ... categories

}// if ... total_categories

//get global custom icons
$topic_icons = get_topic_icons(-1, false);

//and display them
while( list($key, $val) = each($topic_icons) )
{
	$template->assign_block_vars('globalicon', array(
		'ICON_SRC' => $val['icon_url'],
		'U_REMOVE_GLOBAL_ICON' => adminlink("&amp;do=topic_icons&amp;remove=true&amp;id=" . $val['icon_id']))
	);
}

$template->set_filenames(array('body' => 'forums/admin/topic_icons_select_body.html'));

$lang_icons_admin['display001'] = 'Topic Icons';
$lang_icons_admin['display002'] = 'This page allows you to add and remove topic icons from your forums. To add an icon, check the box for each forum you wish to add the icon to, and fill in the details at the bottom of this page. To remove an icon, click on it. This will also remove that icon from all topics using that icon, which may be quite a big change so you will need to give confirmation. Removing an icon is not reversible - you may want to take a database backup first.';
$lang_icons_admin['display003'] = 'Forum Icons';
$lang_icons_admin['display004'] = 'Add';
$lang_icons_admin['display005'] = 'Global Icons';

$lang_icons_admin['add001'] = 'Add an icon';
$lang_icons_admin['add002'] = 'Icon path (from root) EG \'images/icons/icon1.gif\'';
$lang_icons_admin['add003'] = 'Icon name';
$lang_icons_admin['add004'] = "Global?";
$lang_icons_admin['add005'] ='Add icon';

$template->assign_vars(array(
	'L_TITLE' => $lang_icons_admin['display001'],
	'L_INSTRUCTIONS' => $lang_icons_admin['display002'],
	'L_FORUM_TITLE' => $lang_icons_admin['display003'],
	'L_FORUM_ADD' => $lang_icons_admin['display004'],
	'L_GLOBAL_ICONS_TEXT' => $lang_icons_admin['display005'],
	
	'L_ADD_TITLE' => $lang_icons_admin['add001'],
	'L_ADD_PATH' => $lang_icons_admin['add002'],
	'L_ADD_NAME' => $lang_icons_admin['add003'],
	'L_ADD_GLOBAL' => $lang_icons_admin['add004'],
	'L_ADD' => $lang_icons_admin['add005'],
	'S_ACTION' => adminlink("&amp;do=topic_icons"),
	)
);
