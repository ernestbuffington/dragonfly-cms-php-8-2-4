<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('headlines')) { die('Access Denied'); }
\Dragonfly\Page::title(_HeadlinesLANG);

if (isset($_POST['save']))
{
	foreach ($_POST['save'] as $hid => $dummy)
	{
		$hid = intval($hid);
		$db->TBL->headlines->update(array(
			'sitename'     => $_POST['headlines'][$hid]['name'],
			'headlinesurl' => $_POST['headlines'][$hid]['url']
		),"hid={$hid}");
	}
	URL::redirect(URL::admin());
}

else if (isset($_POST['add']))
{
	$db->TBL->headlines->insert(array(
		'sitename'     => $_POST['headlines'][0]['name'],
		'headlinesurl' => $_POST['headlines'][0]['url']
	));
	URL::redirect(URL::admin());
}

else if (isset($_POST['delete']) || isset($_GET['del']))
{
	if (isset($_POST['cancel'])) { URL::redirect(URL::admin()); }
	if (isset($_POST['confirm'])) {
		$db->TBL->headlines->delete("hid=".intval($_GET['del']));
		URL::redirect(URL::admin());
	}
	foreach ($_POST['delete'] as $hid => $dummy)
	{
		\Dragonfly\Page::confirm(URL::admin('&del='.intval($hid)), _SURE2DELHEADLINE);
	}
}

else
{
	$TPL = Dragonfly::getKernel()->OUT;
	$TPL->headlines = $db->query("SELECT hid id, sitename name, headlinesurl url FROM {$db->TBL->headlines} ORDER BY hid");
	$TPL->display('admin/headlines');
}
