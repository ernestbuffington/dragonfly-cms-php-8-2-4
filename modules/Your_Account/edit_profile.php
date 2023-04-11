<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

function edituser(Dragonfly\Identity $userinfo)
{
	$K = Dragonfly::getKernel();
	$db = $K->SQL;

	$mode = isset($_GET['edit']) ? $_GET['edit'] : 'profile';

	if (isset($_GET['auth']) && $userinfo->id == $K->IDENTITY->id) {
		$provider_id = $_GET->int('auth');
		$provider = \Poodle\Auth\Provider::getById($provider_id);
		if ($provider instanceof \Poodle\Auth\Provider) {
			$credentials = isset($_POST['auth'][$provider_id]) ? $_POST['auth'][$provider_id] : array();
			$credentials['redirect_uri'] = \Poodle\URI::appendArgs($_SERVER['REQUEST_URI'], array('auth' => $provider_id));
			$result = $provider->authenticate($credentials);
			processAuthProviderResult($result, $provider);
		}
		$mode = 'reg_details';
	}

	if ($mode == 'admin' && !defined('ADMIN_PAGES')) $mode = 'profile';
	if ($mode == 'reg_details') {
		\Dragonfly\Page::title(_MA_REGISTRATION_INFO, false);
		\Dragonfly\Output\Js::add('modules/Your_Account/javascript/password.js');
	} else if ($mode == 'profile') {
		$section = 'section=1 OR section=2';
		\Dragonfly\Page::title(_MA_PROFILE_INFO, false);
	} else if ($mode == 'private') {
		$section = 'section=3';
		\Dragonfly\Page::title(_MA_PRIVATE, false);
	} else if ($mode == 'prefs') {
		$section = 'section=5';
		\Dragonfly\Page::title(_MA_PREFERENCES, false);
	} else if ($mode == 'avatar') {
		\Dragonfly\Page::title(_AVATAR_CONTROL, false);
	} else if (!defined('ADMIN_PAGES')) {
		\URL::redirect(\URL::index('Your_Account'));
	}

	if (defined('ADMIN_PAGES')) {
		display_admin_account_top_menu($mode, $userinfo);
		$action = URL::admin("users&id={$userinfo->id}");
	} else {
		display_member_block();
		$action = URL::index();
	}

	if (!empty($userinfo->website) && false === strpos($userinfo->website, '://')) {
		$userinfo->website = "http://{$userinfo->website}";
	}

	$K->CFG->avatar->allow_upload = (ini_get('file_uploads') == '0' || strtolower(ini_get('file_uploads') == 'off')) ? false : $K->CFG->avatar->allow_upload;

	switch ($mode)
	{
	case 'reg_details':
		$TPL = $K->OUT;
		$TPL->userinfo = $userinfo;
		$TPL->user_pass_pattern = '.{'.$K->CFG->member->minpass.',}';
		$TPL->user_pass_info = sprintf($TPL->L10N['The password must be at least %d characters'], $K->CFG->member->minpass);
		$TPL->user_auth_tokens = array();
		$auth_providers = \Poodle\Auth\Provider::getPublicProviders();
		foreach ($auth_providers as $provider) {
			if ($provider['id'] > 1 && $provider['mode'] & 1) {
				$provider = new $provider['class']($provider);
				$TPL->user_auth_tokens[$provider->id] = array(
					'id' => $provider->id,
					'name' => $provider->name,
					'tokens' => array(),
					'form' => ($userinfo->id == $K->IDENTITY->id) ? $provider->getAction() : null,
				);
			}
		}
		$result = $db->query("SELECT
			auth_provider_id,
			auth_claimed_id
		FROM {$db->TBL->auth_identities}
		WHERE identity_id = {$userinfo->id}
		  AND auth_provider_id IN (".implode(',', array_keys($TPL->user_auth_tokens)).")");
		while ($r = $result->fetch_row()) {
			$TPL->user_auth_tokens[$r[0]]['tokens'][] = $r[1];
		}

		$TPL->display('Your_Account/edit/reg_details');
		return;

	case 'avatar':
		if (isset($_POST['submitavatar']) && isset($_POST['avatarselect'])) {
			$user_avatar = $_POST['avatarselect'];
			$user_avatar_type = \Dragonfly\Identity\Avatar::TYPE_GALLERY;
		} else {
			$user_avatar = $userinfo->avatar;
			$user_avatar_type = $userinfo->avatar_type;
		}
		if (\Dragonfly\Identity\Avatar::TYPE_UPLOAD == $user_avatar_type) {
			$avatar = $K->CFG->avatar->path . '/' .$user_avatar;
		} else if (\Dragonfly\Identity\Avatar::TYPE_REMOTE == $user_avatar_type) {
			$avatar = $user_avatar;
		} else if (\Dragonfly\Identity\Avatar::TYPE_GALLERY == $user_avatar_type) {
			$avatar = $K->CFG->avatar->gallery_path . '/' .$user_avatar;
		} else {
			$avatar = $K->CFG->avatar->gallery_path . '/' .$K->CFG->avatar->default;
		}

		\Dragonfly\Output\Js::add('modules/Your_Account/javascript/avatar.js');
		$TPL = $K->OUT;
		$TPL->avatar_img  = $avatar;
		$TPL->avatar_type = $user_avatar_type;
		$TPL->user_avatar = $user_avatar;
		$TPL->display('Your_Account/edit/avatar');
		return;

	case 'admin':
		$result = $db->query("SELECT * FROM {$db->TBL->bbranks} WHERE rank_special = 1 ORDER BY rank_title");
		$rank_select = array(array(
			'value' => 0,
			'title' => 'No special rank assigned',
			'current' => 0 == $userinfo->rank,
		));
		while ($row = $result->fetch_assoc()) {
			$rank_select[] = array(
			'value'   => $row['rank_id'],
			'title'   => $row['rank_title'],
			'current' => $row['rank_id'] == $userinfo->rank,
			);
		}

		$TPL = $K->OUT;
		$TPL->ranks    = $rank_select;
		$TPL->userinfo = $userinfo;
		$TPL->display('Your_Account/edit/admin');
		return;
	}

	$fields = array();
	$result = $db->query("SELECT * FROM {$db->TBL->users_fields} WHERE {$section} ORDER BY section, fid");
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$field = \Dragonfly\Identity\Fields::tpl_field($row, $userinfo);
			if ($field) $fields[] = $field;
		}
	}

	$TPL = $K->OUT;
	$TPL->userinfo = $userinfo;
	$TPL->user_section_fields = $fields;
	$TPL->display('Your_Account/edit/section');
}

function saveuser(Dragonfly\Identity $userinfo)
{
	$K = Dragonfly::getKernel();
	$db = $K->SQL;

	$mode = isset($_GET['edit']) ? $_GET['edit'] : 'profile';
	$mode = isset($_POST['save']) ? $_POST['save'] : $mode;
	if ('admin' === $mode && !defined('ADMIN_PAGES')) { $mode = 'profile'; }
	if ('profile' === $mode) {
		$section = 'section=1 OR section=2';
	} else if ('private' === $mode) {
		$section = 'section=3';
	} else if ('prefs' === $mode) {
		$section = 'section=5';
	}

	if ('reg_details' === $mode) {
		if (isset($_POST['add_auth']) && 1 == count($_POST['add_auth']) && $userinfo->id == $K->IDENTITY->id) {
			$provider_id = array_keys($_POST['add_auth'])[0];
			$provider = \Poodle\Auth\Provider::getById($provider_id);
			if ($provider instanceof \Poodle\Auth\Provider) {
				$credentials = isset($_POST['auth'][$provider_id]) ? $_POST['auth'][$provider_id] : array();
				$credentials['redirect_uri'] = \Poodle\URI::appendArgs($_SERVER['REQUEST_URI'], array('auth' => $provider_id));
				$result = $provider->authenticate($credentials);
				processAuthProviderResult($result, $provider);
				return;
			}
		}

		if (!empty($_POST['revoke'])) {
			foreach ($_POST['revoke'] as $provider_id => $claimed_ids) {
				foreach ($claimed_ids as $claimed_id) {
					$db->TBL->auth_identities->delete(array(
						'identity_id' => $userinfo->id,
						'auth_provider_id' => (int) $provider_id,
						'auth_claimed_id' => $claimed_id,
					));
				}
			}
		}

		if (!empty($_POST['new_password'])) {
			$new_password = $_POST['new_password'];
			$verify_password = isset($_POST['verify_password']) ? $_POST['verify_password'] : '';
			if ($new_password != $verify_password) {
				cpg_error(_PASSDIFFERENT, 'ERROR: Password mismatch');
			}
			if (strlen($new_password) < $K->CFG->member->minpass) {
				cpg_error(sprintf($K->OUT->L10N['The password must be at least %d characters'], $K->CFG->member->minpass), 'ERROR: Password too short');
			}
/*
			if (!defined('ADMIN_PAGES')) {
				$provider = \Poodle\Auth\Provider::getById(1);
				if (!$provider->isValidPassword($userinfo->id, $_POST['current_password'])) {
					cpg_error('Password incorrect');
				}
			}
*/
			$userinfo->updateAuth(1, $userinfo->nickname, $new_password);
		}

		$user_email = isset($_POST['user_email']) ? $_POST['user_email'] : $userinfo->email;
		if (($K->CFG->member->allowmailchange || defined('ADMIN_PAGES')) && $user_email != $userinfo->email) {
/*
			if (!defined('ADMIN_PAGES')) {
				$provider = \Poodle\Auth\Provider::getById(1);
				if (!$provider->isValidPassword($userinfo->id, $_POST['current_password'])) {
					cpg_error('Password incorrect');
				}
			}
*/
			if (is_email($user_email) < 1) {
				cpg_error(_ERRORINVEMAIL);
			}
			$userinfo->email = $user_email;
		}
		if (defined('ADMIN_PAGES') && isset($_POST['username']) && $_POST['username'] != $userinfo['username']) {
			try {
				\Dragonfly\Identity\Validate::nickname($_POST['username']);
			} catch (\Exception $e) {
				cpg_error($e->getMessage());
			}
			$userinfo->nickname = $_POST['username'];
		}
	}

	else if ('avatar' === $mode)
	{
		if (isset($_POST['submitavatar'])) {
			return edituser($userinfo);
		}

		if (isset($_POST['cancelavatar'])) {
			cpg_error(_TASK_CANCELED, _TB_INFO, URL::index('&edit='.$mode));
		}

		$AVATAR_CFG = $K->CFG->avatar;

		require_once('modules/'.basename(__DIR__).'/avatars.php');
		// Local avatar?
		$avatar_local = isset($_POST['user_avatar']) ? $_POST['user_avatar'] : '';
		// Remote avatar?
		$avatar_remoteurl = !empty($_POST['avatarremoteurl']) ? htmlprepare($_POST['avatarremoteurl']) : '';
		// Upload avatar thru remote or upload?
		$file = $_FILES ? $_FILES->getAsFileObject('avatar') : null;

		// None
		if (isset($_POST['avatardel']) || !$avatar_local)
		{
			avatar_delete($userinfo);
		}
		// Upload
		else if ($AVATAR_CFG->allow_upload && (trim($_POST['avatarurl']) || ($file && $file->errno != UPLOAD_ERR_NO_FILE)))
		{
			if ($file && $file->errno != UPLOAD_ERR_NO_FILE) {
				if ($file->errno) {
					cpg_error(sprintf(_AVATAR_FILESIZE, round($AVATAR_CFG->filesize / 1024)), 'ERROR: '.$file->error);
				}
				avatar_upload($userinfo, $file);
			} else if (trim($_POST['avatarurl'])) {
				avatar_upload($userinfo, trim($_POST['avatarurl']));
			}
		}
		// Remote
		else if ($AVATAR_CFG->allow_remote && $avatar_remoteurl)
		{
			if (preg_match('#^https?://.+\\.(gif|jpg|jpeg|png)$#i', $avatar_remoteurl) ) {
				$img_info = getimagesize($avatar_remoteurl);
				if (!$img_info) {
					cpg_error('Image has wrong filetype', _AVATAR_ERR_URL);
				}
				if (!($file_data = \Poodle\HTTP\URLInfo::get($avatar_remoteurl, !$AVATAR_CFG->animated))) {
					cpg_error(_AVATAR_ERR_URL);
				}
				if ($file_data['size'] > $AVATAR_CFG->filesize) {
					cpg_error(sprintf(_AVATAR_FILESIZE, round($AVATAR_CFG->filesize / 1024)));
				}
				if (!$AVATAR_CFG->animated && $file_data['animation']) {
					cpg_error('Animated avatar not allowed');
				}
				list($width, $height) = $img_info;
				if ($height > $AVATAR_CFG->max_height || $width > $AVATAR_CFG->max_width) {
					cpg_error(sprintf(_AVATAR_ERR_SIZE, $width, $height), 'ERROR: Image size');
				}
				avatar_delete($userinfo);
				$userinfo->avatar      = $avatar_remoteurl;
				$userinfo->avatar_type = \Dragonfly\Identity\Avatar::TYPE_REMOTE;
			} else {
				cpg_error('Image has wrong URL', 'ERROR: Image URL');
			}
		}
		// Gallery
		else if ($avatar_local && $avatar_local != $userinfo->avatar && $AVATAR_CFG->allow_local
		 && is_file($AVATAR_CFG->gallery_path.'/'.$avatar_local))
		{
			avatar_delete($userinfo);
			$userinfo->avatar      = $avatar_local;
			$userinfo->avatar_type = \Dragonfly\Identity\Avatar::TYPE_GALLERY;
		}
	}

	else if ('admin' === $mode) {
		$userinfo->allow_pm    = intval($_POST['user_allow_pm']);
		$userinfo->allowavatar = intval($_POST['user_allowavatar']);
		$userinfo->rank        = intval($_POST['user_rank']);
//		$deleteUserData        = isset($_POST['delete_user_data'] && $_POST['delete_user_data']) ? 1 : 0;
		$suspendreason = isset($_POST['suspendreason']) ? $_POST['suspendreason'] : 'no reason';
		if ($_POST['suspendreason'] != $userinfo['susdel_reason']) {
			$userinfo->susdel_reason = intval($suspendreason);
		}
		if (intval($_POST['user_suspend']) == 0 && $userinfo->level == 0) {
			$userinfo->level = 1;
		} else if (intval($_POST['user_suspend']) > 0 && $userinfo->level > 0) {
			$message = _SORRYTO.' '.$K->CFG->global->sitename.' '._HASSUSPEND;
			if ($suspendreason > '') {
				$message .= "\n\n"._SUSPENDREASON."\n$suspendreason";
			}
			$from = 'noreply@'.str_replace('www.', '', $K->CFG->server->domain);
			if (!\Dragonfly\Email::send($mailer_message, _ACCTSUSPEND, $message, $userinfo->email, $userinfo['username'], $from)) {
				trigger_error($mailer_message, E_USER_WARNING);
			}
			$userinfo->level = 0;
			$userinfo->susdel_reason = $suspendreason;
//			if ($deleteUserData) \Poodle\Events trigger_event('deleteUserData', array('nickname' => $userinfo->nickname));
		}
	}

	else {
		$result = $db->query("SELECT field, type FROM {$db->TBL->users_fields} WHERE {$section}");
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$field = ('name' === $row['field'])?'realname':$row['field'];
				$value = strip_tags($_POST[$field]);
				if ('user_lang' === $row['field']  && !$K->CFG->global->multilingual) continue;
				if (1 == $row['type'] || 4 == $row['type']) {
					$value = (int)$value;
				} else {
					if ('user_website' === $field) {
						if (!preg_match('#^http[s]?:\/\/#i', $value)) {
							$value = 'http://' . $value;
						}
						if (!preg_match('#^(http[s]?\:\/\/)?([a-z0-9\-\.]+)?[a-z0-9\-]+\.[a-z]{2,4}$#i', $value)) {
							$value = '';
						}
					}
				}
				if (7 == $row['type']) {
					if (!$K->CFG->member->allowusertheme) {
						$value = $K->CFG->global->Default_Theme;
					} else if (!defined('ADMIN_PAGES')) {
						unset($_SESSION['CPG_SESS']['prevtheme']);
					}
				}
				if (6 == $row['type']) {
					$value = substr($value, 6, 4) . substr($value, 0, 2) . substr($value, 3, 2);
					if (checkdate(substr($value, 4, 2), substr($value, 6, 2), substr($value, 0, 4))) {
						$userinfo[$row['field']] = $value;
					}
				} else {
					$userinfo[$row['field']] = $value;
				}
			}
		}
	}

	$userinfo->save();
	if (defined('ADMIN_PAGES')) {
		\Dragonfly::closeRequest(_TASK_COMPLETED, 200, URL::admin("users&id={$userinfo->id}&edit={$mode}"));
	} else {
		\Dragonfly::closeRequest(_TASK_COMPLETED, 200, URL::index('&edit='.$mode));
	}
}

function processAuthProviderResult($result, \Poodle\Auth\Provider $provider)
{
	$K = \Dragonfly::getKernel();
	if ($result instanceof \Poodle\Auth\Result\Success) {
		if ($result->claimed_id) {
			// check if claimed_id isn't already take by another user
			$id_secure = $K->SQL->quote(\Poodle\Auth::secureClaimedID($result->claimed_id));
			$found_id = $K->SQL->uFetchRow("SELECT
				identity_id
			FROM {$K->SQL->TBL->auth_identities}
			WHERE auth_provider_id = {$provider->id}
			  AND auth_claimed_id = {$id_secure}");
			if ($found_id[0] != $K->IDENTITY->id) {
				$K->L10N->load('login');
				\Poodle\Notify::error($K->L10N['The provided information is already claimed by another account.']);
				\Poodle\LOG::error(\Poodle\LOG::LOGIN, "Can't add {$result->claimed_id} to user {$K->IDENTITY->id}, it is claimed by user {$found_id[0]}");
				\URL::redirect(\URL::index('Your_Account&edit=reg_details'));
			}
			$K->IDENTITY->updateAuth($provider->id, $result->claimed_id);
		}
		if (XMLHTTPRequest) {
			\Poodle\HTTP\Status::set(202);
		} else {
			\Poodle\Notify::success($K->L10N['You successfully added/updated a login method']);
			\URL::redirect(\URL::index('Your_Account&edit=reg_details'));
		}
	}
	else if ($result instanceof \Poodle\Auth\Result\Redirect) {
		if (!XMLHTTPRequest) {
			\URL::redirect($result->uri);
		}
		\Poodle\HTTP\Status::set(202);
		header('Content-Type: application/json');
		echo json_encode(array(
			'status' => '302',
			'location' => $result->uri
		));
	}
	else if ($result instanceof \Poodle\Auth\Result\Form) {
		if (XMLHTTPRequest) {
			\Poodle\HTTP\Status::set(202);
			header('Content-Type: application/json');
			echo json_encode(array(
				'form' => array(
					'action' => $result->action,
					'submit' => $result->submit,
					'fields' => $result->fields
				),
				'provider_id' => $provider->id
			));
		} else {
			$OUT = \Dragonfly::getKernel()->OUT;
			$OUT->auth_result = $result;
			$OUT->auth_provider = $provider;
			$OUT->display('login/redirect-form');
		}
	}
	else if ($result instanceof \Poodle\Auth\Result\Error) {
		$K->L10N->load('login');
		\Poodle\Notify::error($K->L10N['The provided information is unknown or incorrect. Please try again']);
		\Poodle\LOG::error(\Poodle\LOG::LOGIN, get_class($provider).'#'.$result->getCode().': '.$result->getMessage());
		\URL::redirect(\URL::index('Your_Account&edit=reg_details'));
	}
}
