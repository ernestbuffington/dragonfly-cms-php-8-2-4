<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/

/* Applied rules:
 * ParenthesizeNestedTernaryRector (https://www.php.net/manual/en/migration74.deprecated.php)
 */
 
if (!defined('CPG_NUKE')) { exit; }
global $module_name, $db;

$cat = ($_POST->uint('cat') ?: $_GET->uint('cat')) ?: 0;

$content = '';

foreach (\Coppermine::getInstances() as $cpgdir => $data) {
	if ($content) $content .= '<hr />';
	$cpgtitle = $db->uFetchRow("SELECT custom_title FROM {$db->TBL->modules} WHERE title='{$cpgdir}'");
	if (!$cpgtitle[0]) $cpgtitle[0] = $cpgdir;
	$content .= '<div style="text-align:center;"><strong>' . $cpgtitle[0] . '</strong></div>
	' . ALBUMS . ': ' . $data->config['TABLE_ALBUMS']->count() . '<br />
	' . PICTURES . ': ' . $data->config['TABLE_PICTURES']->count() . '<br />';
	$num = cpg_tablecount($data->config['TABLE_PICTURES'], 'sum(hits)');
	if (!is_numeric($num)) $num = 0;
	$content .= '<strong>&nbsp;&nbsp;·</strong>&nbsp;' . PIC_VIEWS . ': ' . $num.'<br />';
	$num = cpg_tablecount($data->config['TABLE_PICTURES'], 'sum(votes)');
	if (!is_numeric($num)) $num = 0;
	$content .= '<strong>&nbsp;&nbsp;·</strong>&nbsp;' . PIC_VOTES . ': ' . $num.'<br />
	<strong>&nbsp;&nbsp;·</strong>&nbsp;' . PIC_COMMENTS . ': ' . $data->config['TABLE_COMMENTS']->count() . '<br />';
	if (can_admin($cpgdir)) {
		$num = $db->uFetchRow("SELECT count(*) FROM {$data->config['TABLE_PICTURES']} WHERE approved=0");
		$categ = ($module_name != $cpgdir) ? '0' : $cat;
		$content .= '<a href="'.htmlspecialchars(URL::index("{$cpgdir}&file=editpics&mode=upload_approval")).'">' . UPL_APP_LNK . '</a>: ' . $num[0] . '<br />
<a href="'.htmlspecialchars(URL::admin("{$cpgdir}&file=searchnew")).'">' . SEARCHNEW_LNK . '</a><br />
<a href="'.htmlspecialchars(URL::admin("{$cpgdir}&file=reviewcom")).'">' . COMMENTS_LNK . '</a><br />
<a href="'.htmlspecialchars(URL::admin("{$cpgdir}&file=groups")).'">' . GROUPS_LNK . '</a><br />
<a href="'.htmlspecialchars(URL::admin("{$cpgdir}&file=users")).'">' . USERS_LNK . '</a><br />
<a href="'.htmlspecialchars(URL::index("{$cpgdir}&file=albmgr&cat=$categ")).'">' . ALBUMS_LNK . '</a><br />
<a href="'.htmlspecialchars(URL::admin("{$cpgdir}&file=categories")).'">' . CATEGORIES_LNK . '</a><br />';
		if (is_admin()) {
			$content .= '<a href="'.URL::admin($cpgdir).'">' . CONFIG_LNK . '</a>';
		}
	} else if (USER_ADMIN_MODE) {
		$content .= '<b><a href="'.htmlspecialchars(URL::index("{$cpgdir}&file=albmgr")).'">' . ALBMGR_LNK . '</a><br />
<a href="'.htmlspecialchars(URL::index("{$cpgdir}&file=profile&op=edit_profile")).'">' . MY_PROF_LNK .'</a>';
	}
}
