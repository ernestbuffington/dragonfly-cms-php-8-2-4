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

\Dragonfly\Page::title(_AUTHORSADMIN);

// Add new administrator
if (isset($_POST['add_admin'])) {
	if (!can_admin() || !\Dragonfly\Output\Captcha::validate($_POST)) {
		cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
	}
	if (strlen($_POST['aid']) < 3 || strlen($_POST['pwd']) < 3 || is_email($_POST['email']) < 1) {
		cpg_error(_COMPLETEFIELDS, _CREATIONERROR);
	}
	if (!\Dragonfly\Admin\Login::isValidPassword($_POST['pwd'])) {
		cpg_error(_PASSWORD_MALFORMED, _CREATIONERROR);
	}

	$admin = new \Dragonfly\Admin\Identity();
	$admin->name     = $_POST['aid'];
	$admin->email    = $_POST['email'];
	$admin->password = $_POST['pwd'];
	if (!empty($_POST['radminsuper'])) {
		$admin->radminsuper = true;
	} else {
		foreach ($admin->radmin as $aop => $v) {
			$ra = 'radmin'.$aop;
			$admin->$ra = in_array($aop, $_POST['radmin']);
		}
	}
	$admin->save();

	URL::redirect(URL::admin('admins'));
}

// Delete an administrator
else if (isset($_GET['delete'])) {
	if (!can_admin() || $_SESSION['CPG_SESS']['admin']['page'] != 'admins') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	if (!is_numeric($_GET['delete'])) { cpg_error(sprintf(_ERROR_NOT_SET, _ADMINID), _SEC_ERROR); }
	$del_aid = intval($_GET['delete']);
	if ($del_aid == 1) { cpg_error(_GODNOTDEL); }
	$admin = new \Dragonfly\Admin\Identity($del_aid);
	if (isset($_POST['confirm'])) {
		$admin->delete();
	} else if (!isset($_POST['cancel'])) {
		\Dragonfly\Page::title(_AUTHORDEL);
		\Dragonfly\Page::confirm(URL::admin('admins&delete='.$del_aid), sprintf(_ERROR_DELETE_CONF, $admin->name));
	}
	URL::redirect(URL::admin('admins'));
}

else if (isset($_GET['edit']))
{
	$aid = intval($_GET['edit']);
	if ($aid < 1) { cpg_error(sprintf(_ERROR_NOT_SET, _ADMINID), _SEC_ERROR); }
	if (!can_admin() && $_SESSION['DF_VISITOR']->admin->id != $aid) { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }

	$admin = new \Dragonfly\Admin\Identity($aid);

	// Update administrator settings?
	if ('POST' === $_SERVER['REQUEST_METHOD']) {
		if (!\Dragonfly\Output\Captcha::validate($_POST)) {
			cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
		}
		$admin->email = $_POST['email'];
		if (!empty($_POST['pwd']) && !empty($_POST['pwd2'])) {
			if (!\Dragonfly\Admin\Login::isValidPassword($_POST['pwd'])) { cpg_error(_PASSWORD_MALFORMED); }
			if ($_POST['pwd'] != $_POST['pwd2']) { cpg_error(_PASSWDNOMATCH); }
			$admin->password = $_POST['pwd'];
		}
		if (can_admin()) {
			$admin->radminsuper = !empty($_POST['radminsuper']);
			foreach ($admin->radmin as $aop => $v) {
				$ra = 'radmin'.$aop;
				$admin->$ra = !$admin->radminsuper && in_array($aop, $_POST['radmin']);
			}
		}
		if (isset($_POST['deactivate_totp'])) {
			$admin->totp_2fa = '';
		} else if (isset($_POST['activate_totp']) && !\Dragonfly::isDemo()) {
			$admin->totp_2fa = \Poodle\TOTP::createSecret();
		}
		if (!\Dragonfly::isDemo()) {
			$admin->save();
		}
		URL::redirect(URL::admin("admins&edit={$admin->id}"));
	}

	\Dragonfly\Page::title(_MODIFYINFO);

	$TPL = Dragonfly::getKernel()->OUT;
	$TPL->admin = $admin;
	$TPL->admin_ops = $admin->radmin;

	$pl = \Dragonfly\Admin\Login::getMinPassLength();
	$TPL->admin_pass_pattern = '.{'.$pl.',}';
	$TPL->admin_pass_info = sprintf($TPL->L10N['The password must be at least %d characters'], $pl);
	$TPL->display('admin/admins/edit');
}

else {
	$admin = new \Dragonfly\Admin\Identity();
	$adminops = array_keys($admin->radmin);

	$TPL = Dragonfly::getKernel()->OUT;
	$TPL->admin_ops = $adminops;

	$result = $db->query("SELECT * FROM {$db->TBL->admins}");
	$admins = array();
	while ($row = $result->fetch_assoc()) {
		$radmin = array();
		if ($row['radminsuper']) {
			$radmin[] = _SUPERUSER;
		} else {
			foreach ($adminops as $aop) {
				if ($row[('radmin'.$aop)]) $radmin[] = $aop;
			}
		}
		$admins[] = array(
			'aid' => $row['aid'],
			'permissions' => implode(', ', $radmin),
			'mod_uri' => (can_admin() || $row['aid'] == is_admin()) ? URL::admin('&edit='.$row['admin_id']) : false,
			'del_uri' => (can_admin() && $row['aid'] != is_admin()) ? URL::admin('&delete='.$row['admin_id']) : false,
		);
	}
	$TPL->admins = $admins;
	$pl = \Dragonfly\Admin\Login::getMinPassLength();
	$TPL->admin_pass_pattern = '.{'.$pl.',}';
	$TPL->admin_pass_info = sprintf($TPL->L10N['The password must be at least %d characters'], $pl);
	$TPL->display('admin/admins/index');
}
