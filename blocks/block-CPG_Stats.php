<?php 
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-CPG_Stats.php,v $
  $Revision: 9.10 $
  $Author: phoenix $
  $Date: 2007/05/15 00:04:30 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $cpg_all_installs, $prefix, $db, $CONFIG, $cpg_dir;

$cpg_dir = 'coppermine'; //user defined
$cat = isset($_POST['cat']) && is_numeric($_POST['cat']) ? $_POST['cat'] : (isset($_GET['cat']) && is_numeric($_GET['cat']) ? $_GET['cat'] : 0);

if (!is_active($cpg_dir)) {
	$content = 'ERROR';
	return trigger_error($cpg_dir.' module is inactive', E_USER_WARNING);
}

$cpg_block = true;
require("modules/" . $cpg_dir . "/include/load.inc");
$cpg_block = false;

$content = $cpg_prefix = $cpg_title = $num = '';

foreach($cpg_all_installs AS $cpgdir => $cpgprefix) {
	$cpgprefix = $cpgprefix['prefix'];
	if ($content != '') $content .= '<hr />';
	$cpgtitle = $db->sql_ufetchrow("SELECT custom_title FROM " . $prefix . "_modules WHERE title='$cpgdir'", SQL_NUM);
	if ($cpgtitle[0] == '') $cpgtitle[0] = $cpgdir;
	$content .= '<div style="text-align:center;"><strong>' . $cpgtitle[0] . '</strong></div>
	<b>&#8226;</b>&nbsp;' . ALBUMS . ': ' . cpg_tablecount($cpgprefix . "albums", 'count(*)', __FILE__) . '<br />
	<b>&#8226;</b>&nbsp;' . PICTURES . ': ' . cpg_tablecount($cpgprefix . 'pictures', 'count(*) ', __FILE__) . '<br />';
	$num = cpg_tablecount($cpgprefix .'pictures', 'sum(hits)', __FILE__);
	if (!is_numeric($num)) $num = 0;
	$content .= '<strong><big>&nbsp;&nbsp;&middot;</big></strong>&nbsp;' . PIC_VIEWS . ': ' . $num.'<br />';
	$num = cpg_tablecount($cpgprefix . 'pictures', 'sum(votes)', __FILE__);
	if (!is_numeric($num)) $num = 0;
	$content .= '<strong><big>&nbsp;&nbsp;&middot;</big></strong>&nbsp;' . PIC_VOTES . ': ' . $num.'<br />
	<strong><big>&nbsp;&nbsp;&middot;</big></strong>&nbsp;' . PIC_COMMENTS . ': ' . cpg_tablecount($cpgprefix . 'comments', 'count(*)', __FILE__) . '<br />';
	if (GALLERY_ADMIN_MODE) {
		$num = $db->sql_ufetchrow("SELECT count(*) FROM " . $cpgprefix . "pictures WHERE approved=0", SQL_NUM);
		$categ = ($module_name != $cpgdir) ? '0' : $cat;
		$content .= '<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=editpics&amp;mode=upload_approval").'">' . UPL_APP_LNK . '</a>: ' . $num[0] . '<br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=searchnew").'">' . SEARCHNEW_LNK . '</a><br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=reviewcom").'">' . COMMENTS_LNK . '</a><br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$first_install_M_DIR&amp;file=groupmgr").'">' . GROUPS_LNK . '</a><br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$first_install_M_DIR&amp;file=usermgr").'">' . USERS_LNK . '</a><br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=albmgr&amp;cat=$categ").'">' . ALBUMS_LNK . '</a><br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=catmgr").'">' . CATEGORIES_LNK . '</a><br />';
		if (is_admin()) $content .= '<b>&#8226;</b>&nbsp;<a href="'.adminlink("$cpgdir").'">' . CONFIG_LNK . '</a>';
	} else if (USER_ADMIN_MODE) {
		$content .= '<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=albmgr").'">' . ALBMGR_LNK . '</a><br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=modifyalb").'">' . MODIFYALB_LNK . '</a><br />
<b>&#8226;</b>&nbsp;<a href="'.getlink("$cpgdir&amp;file=profile&amp;op=edit_profile").'">' . MY_PROF_LNK .'</a>';
	} 
} 
