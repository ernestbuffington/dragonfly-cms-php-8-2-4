<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * AddDefaultValueForUndefinedVariableRector (https://github.com/vimeo/psalm/blob/29b70442b11e3e66113935a2ee22e165a70c74a4/docs/fixing_code.md#possiblyundefinedvariable)
 */
 
if (!class_exists('Dragonfly', false)) { exit; }
\Dragonfly\Page::title(_Your_AccountLANG, false);

if (is_user()) { cpg_error(_YOUAREREGISTERED, 409); }

if (!$MAIN_CFG['member']['allowuserreg']) { cpg_error(_ACTDISABLED, 403); }

if ('POST' == $_SERVER['REQUEST_METHOD']) {
	if (XMLHTTPRequest) {
		if (!empty($_POST['validate_username'])) {
			try {
				\Dragonfly\Identity\Validate::nickname($_POST['validate_username']);
			} catch (Exception $e) {
				\Poodle\HTTP\Status::set(409);
				echo $e->getMessage();
			}
		} else if (!empty($_POST['validate_email'])) {
			try {
				\Dragonfly\Identity\Validate::email($_POST['validate_email']);
			} catch (Exception $e) {
				\Poodle\HTTP\Status::set(409);
				echo $e->getMessage();
			}
		} else {
			\Poodle\HTTP\Status::set(412);
			echo 'Field can not be empty.';
		}
		exit;
	}

	if (isset($_POST['op']) && 'finish' == $_POST['op']) {
		// Step 4
		register_finish();
	} else if (isset($_POST['username'])) {
		// Step 3
		register_check();
	} else {
		// Step 2
		register_form();
	}
}

// Step 5
else if (isset($_GET['activate'])) {
	activate($_GET['activate'] . (isset($_GET['check_num']) ? '-'.$_GET['check_num'] : ''));
}

// Step 1
else if (!isset($_POST['terms_coppa']) && !isset($_POST['terms_agreed']) && $MAIN_CFG['member']['show_registermsg']) {
	\Dragonfly\Page::title(_MA_REGISTRATION, false);
	\Dragonfly::getKernel()->OUT->display('Your_Account/register/agree');
}

// Step 2
else {
	register_form();
}

function register_form()
{
	\Dragonfly\Page::title(_REGISTRATIONSUB, false);
	\Dragonfly\Output\Js::add('modules/Your_Account/javascript/register.js');
	\Dragonfly\Output\Js::add('modules/Your_Account/javascript/password.js');
//	\Dragonfly\Output\Css::inline('input.error { border-color: #F00 } label.ok, label.error { margin:0 5px; width:auto }');
	$K = \Dragonfly::getKernel();
	$SQL = $K->SQL;
	$K->OUT->registerinfo = array(
		array(
			'name'   => 'username',
			'label'  => _USERNAME,
			'type'   => 'text',
			'length' => 25,
			'note'   => null,
			'required' => true,
			'pattern' => null,
			'info' => null,
		),
		array(
			'name'   => 'email',
			'label'  => _EMAILADDRESS,
			'type'   => 'email',
			'length' => 255,
			'note'   => null,
			'required' => true,
			'pattern' => null,
			'info' => null,
		),
		array(
			'name'   => 'password',
			'label'  => _PASSWORD,
			'type'   => 'password',
			'length' => 255,
			'note'   => _BLANKFORAUTO,
			'required' => false,
			'pattern' => '.{'.$K->CFG->member->minpass.',}',
			'info' => sprintf($K->OUT->L10N['The password must be at least %d characters'], $K->CFG->member->minpass),
		),
		array(
			'name'   => 'password_confirm',
			'label'  => _CONFIRMPASSWORD,
			'type'   => 'password',
			'length' => 255,
			'note'   => null,
			'required' => false,
			'pattern' => '.{'.$K->CFG->member->minpass.',}',
			'info' => null,
		),
	);

	// Add the additional fields to form if activated
	$additional_fields = array();
	$result = $SQL->query("SELECT * FROM {$SQL->TBL->users_fields} WHERE visible > 0 ORDER BY section");
	if ($result->num_rows) {
		$settings = 0;
		while ($row = $result->fetch_assoc()) {
			$field = \Dragonfly\Identity\Fields::tpl_field($row);
			if ($field) {
				if ($row['section'] == 3 && !$settings) {
					$settings = 3;
					$additional_fields[$settings] = array(
					'label' => _MA_PRIVATE,
					'fields' => array(),
					);
				} else if ($row['section'] == 5 && $settings != 5) {
					$settings = 5;
					$additional_fields[$settings] = array(
					'label' => _MA_PREFERENCES,
					'fields' => array(),
					);
				}
				if (!$settings && !isset($additional_fields[0])) {
					$additional_fields[0] = array(
					'label'  => null,
					'fields' => array(),
					);
				}
				$additional_fields[$settings]['fields'][] = $field;
			}
		}
	}
	$K->OUT->register_sections = $additional_fields;
	$K->OUT->display('Your_Account/register/form');
}

function register_check()
{
	\Dragonfly\Page::title(_USERFINALSTEP, false);

	if (!\Dragonfly\Output\Captcha::validate($_POST)) {
		error_log('Register captcha failed for '.$_SERVER['REMOTE_ADDR']);
		cpg_error('Form post failed', 409);
	}

	$username = $_POST['username'];
	$email = strtolower($_POST['email']);
	$password = $_POST['password'];
	try {
		\Dragonfly\Identity\Validate::password($password, (string)$_POST['password_confirm']);
		\Dragonfly\Identity\Validate::nickname($username);
		\Dragonfly\Identity\Validate::email($email);
		// Check the additional activated fields
		$fields = \Dragonfly\Identity\Fields::fetchFromPost();
	} catch (Exception $e) {
		cpg_error($e->getMessage(), 409);
	}

	$data = array(
		'username'   => $username,
		'user_email' => $email,
		'password'   => $password,
		'coppa'      => !empty($_POST['terms_coppa']),
	);
	foreach ($fields as $k => $v) {
		$data[$k] = $v['value'];
		if (1 == $v['type']) { $fields[$k]['value'] = $v['value'] ? _YES : _NO; }
	}
	$_SESSION['REGISTER'] = $data;

	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->register_data = array_merge(array(
		array('label' => _USERNAME, 'value' => $username),
		array('label' => _EMAILADDRESS, 'value' => $email),
		array('label' => _PASSWORD, 'value' => _MA_HIDDEN),
	), $fields);
	$OUT->display('Your_Account/register/check');
}

function register_finish()
{
	$mailer_message = null;
 $K = \Dragonfly::getKernel();
	$SQL = $K->SQL;
	$CFG = $K->CFG;

	$fields = $_SESSION['REGISTER'];
	if (empty($fields['username'])) {
		cpg_error('session gone...', 409);
	}

	\Dragonfly\Page::title(_ACCOUNTCREATED, false);

	$requireadmin = ($fields['coppa'] || $CFG->member->requireadmin);

	$password = '';
	if (empty($fields['password'])) {
		$fields['password'] = \Poodle\Auth::generatePassword(max(12, $CFG->member->minpass + 2));
		$password = "\n"._PASSWORD.': '.$fields['password'];
	}

	$activation_key = null;
	if ($CFG->member->useactivate || $requireadmin) {
		$data = $fields;
		unset($data['password']);
		unset($data['username']);
		unset($data['user_email']);
		$activation_key = \Poodle\Identity\Request::newAccount(array(
			'nickname'  => $fields['username'],
			'password'  => \Poodle\Auth::hashPassword($fields['password']),
			'email'     => $fields['user_email'],
			'givenname' => '',
			'surname'   => '',
			'details'   => $data,
		));
	} else {
		$identity = $K->IDENTITY;
		$identity->nickname   = $fields['username'];
		$identity->email      = $fields['user_email'];
		$result = $SQL->query("SELECT field FROM {$SQL->TBL->users_fields} WHERE visible > 0");
		while (list($field) = $result->fetch_row()) {
			$identity->$field = $fields[$field];
		}
		$identity->save();
		$identity->updateAuth(1, $fields['username'], $fields['password']);
	}

	$tpl = '';

	// Prepare email
	$subject = $message = '';
	if ($requireadmin) {
//		$message = $lang['COPPA'];
//		$email_template = 'coppa_welcome_inactive';
		$subject = $K->L10N->get('User Account Registration');
		$message = _TOAPPLY." {$CFG->global->sitename}.\n\n"._WAITAPPROVAL."\n\n"._FOLLOWINGMEM."\n"._USERNAME.": {$fields['username']}{$password}";
		$tpl = 'status-pending';
		\Dragonfly\Identity\Create::notifyAdmin($fields['username']);
	} else {
		$message = _TOREGISTER." {$CFG->global->sitename}.\n\n";
		if ($CFG->member->useactivate) {
			$subject = _ACTIVATIONSUB;
			$finishlink = URL::index("&file=register&activate={$activation_key}", true, true);
			$message .= _TOFINISHUSER."\n\n {$finishlink}\n\n"; // Is the activation link in email.
			$tpl = 'status-activate';
		} else {
			$subject = _REGISTRATIONSUB;
			$tpl = 'status-active';
		}
		$message .= _FOLLOWINGMEM."\n"._USERNAME.": {$fields['username']}{$password}";
	}

	// Send email to user
	$from = 'noreply@'.str_replace('www.', '', $CFG->server->domain);
	$message = _WELCOMETO." {$CFG->global->sitename}!\n\n"._YOUUSEDEMAIL.' '.$message;
	if (!\Dragonfly\Email::send($mailer_message, $subject, $message, $fields['user_email'], $fields['username'], $from)) {
		\Poodle\LOG::error('mail',$mailer_message);
	}

	unset($_SESSION['REGISTER']);

	$K->OUT->display('Your_Account/register/'.$tpl);
}

function activate($activation_key)
{
	$tpl = null;
 $K = \Dragonfly::getKernel();
	$CFG = $K->CFG;
	if (!$CFG->member->requireadmin) {
		\Poodle\Identity\Request::cleanup(0);
	}
	$row = \Poodle\Identity\Request::getAccount($activation_key);
	if (!$row) {
		cpg_error(_ACTERROR2, _ACTIVATIONERROR);
	}
	$identity = $K->IDENTITY;
	$identity->nickname = $row['nickname'];
	$identity->email    = $row['email'];
	$result = $K->SQL->query("SELECT field FROM {$K->SQL->TBL->users_fields} WHERE visible > 0");
	while (list($field) = $result->fetch_row()) {
		if (isset($row['details'][$field])) {
			$identity->$field = $row['details'][$field];
		}
	}
	$identity->save();

	$c = new \Poodle\Auth\Credentials($identity, $row['username'], $row['password']);
	$c->hash_password = false;
	\Poodle\Auth::update(1, $c);

	\Poodle\Identity\Request::removeAccount($activation_key);
	\Dragonfly\Page::title(_ACTIVATIONYES, false);
	return $K->OUT->display('Your_Account/register/status-active'.$tpl);
}
