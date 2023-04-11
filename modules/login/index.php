<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules;

class Login
{
	public
		$allowed_methods = array('GET','HEAD','POST');

	private static
		$auth_providers = array();

	public static function getProviders()
	{
		if (!self::$auth_providers) {
			$providers = \Poodle\Auth\Provider::getPublicProviders();
			foreach ($providers as $p) {
				$c = new $p['class']($p);
				$f = $c->getAction();
				$p['fields'] = $f->fields;
				self::$auth_providers[] = $p;
			}
		}
		return self::$auth_providers;
	}

	public function GET()
	{
		$K = \Dragonfly::getKernel();

		if ($K->IDENTITY->isMember()) {
			if (isset($_GET['logout'])) {
				$K->IDENTITY->logout();
				\URL::redirect(preg_replace('#[?&]logout#','',$_SERVER['REQUEST_URI']));
			} else {
				$options = static::getOptions();
				\URL::redirect($options['redirect_uri'] ?: BASEHREF);
			}
		}

		if ($K->CFG->auth->https && empty($_SERVER['HTTPS'])) {
			\URL::redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		}

		if (isset($_GET['forgot'])) {
			if (!empty($_GET['forgot'])) {
				$this->viewNewPasswordForm();
			} else {
				$this->viewForgotForm();
			}
		} else
		if (isset($_GET['auth'])) {
			$this->doLogin($_GET->int('auth'));
		} else {
			$this->viewForm();
		}
	}

	public function POST()
	{
		$K = \Dragonfly::getKernel();
		$K->L10N->load('login');
		if ($K->IDENTITY->isMember()) {
			$options = static::getOptions();
			\URL::redirect($options['redirect_uri'] ?: BASEHREF);
		} else {
			if (isset($_GET['forgot'])) {
				if (isset($_POST['new_password'])) {
					$this->processNewPassword();
				}
				if (isset($_POST['forgot'])) {
					$this->processForgotForm();
				}
			}
			else
			if (isset($_POST['provider'])) {
//				if (!($K->global->sec_code & 2) || \Dragonfly\Output\Captcha::validate($_POST)) {
				$this->doLogin($_POST->int('provider'));
			}
			else
			if (!empty($_GET['auth'])) {
				$this->doLogin($_GET->int('auth'));
			}
			else
			if (isset($_POST['openid_identifier'])) {
				if ($time = self::disallowedAction(1, 'login')) {
					header('Retry-After: '.time() - $time);
					\Poodle\Report::error(429, sprintf($K->L10N->get('Password recovery blocked until %s'), $K->L10N->date('DATE_F', $time)));
				}
//				if (!($K->global->sec_code & 2) || \Dragonfly\Output\Captcha::validate($_POST)) {
				$auth_provider = \Poodle\Auth\Detect::provider($_POST['openid_identifier']);
				if ($auth_provider) {
					$this->processAuthProviderResult($auth_provider->authenticate($_POST), $auth_provider);
				} else {
					$this->viewForm('Provider not detected');
				}
			}
		}
	}

	protected static function disallowedAction($auth_provider_id, $action)
	{
		$K = \Dragonfly::getKernel();
		$SQL = $K->SQL;
		$c = $SQL->uFetchRow("SELECT
			auth_attempt_count,
			auth_attempt_last_time
		FROM {$SQL->TBL->auth_attempts}
		WHERE auth_provider_id=".((int)$auth_provider_id)."
		  AND auth_attempt_ip=".$SQL->quote($_SERVER['REMOTE_ADDR'])."
		  AND auth_attempt_action=".$SQL->quote($action));
		if ($c) {
			$timeout = $K->CFG->auth->attempts_timeout;
			if ($c[0] >= $K->CFG->auth->attempts && $c[1] > (time() - $timeout)) {
				return $c[1] + $timeout;
			}
		}
		return false;
	}

	protected static function incAttempt($auth_provider_id, $action)
	{
		$K = \Dragonfly::getKernel();
		$tbl = $K->SQL->TBL->auth_attempts;
		$tbl->delete('auth_attempt_last_time<'.(time() - ($K->CFG->auth->attempts_timeout)));
		try {
			$tbl->insert(array(
				'auth_provider_id' => $auth_provider_id,
				'auth_attempt_ip' => $_SERVER['REMOTE_ADDR'],
				'auth_attempt_action' => $action,
				'auth_attempt_count' => 1,
				'auth_attempt_last_time' => time()
			));
		} catch (\Exception $e) {
			$tbl->updatePrepared(array(
				'auth_attempt_count' => 'auth_attempt_count+1',
				'auth_attempt_last_time' => time()
				), array(
				'auth_provider_id' => $auth_provider_id,
				'auth_attempt_ip' => $_SERVER['REMOTE_ADDR'],
				'auth_attempt_action' => $action,
			));
		}
	}

	protected function processForgotForm()
	{
		if (!\Dragonfly\Output\Captcha::validate($_POST)) {
			error_log('Login captcha failed for '.$_SERVER['REMOTE_ADDR']);
			return $this->viewForgotForm('Form validation failed');
		}

		$K = \Dragonfly::getKernel();

		if ($time = self::disallowedAction(1, 'forgot')) {
			header('Retry-After: '.time() - $time);
			\Poodle\Report::error(429, sprintf($K->L10N->get('Password recovery blocked until %s'), $K->L10N->date('DATE_F', $time)));
		}

		\Dragonfly\Page::title(_PASSWORDLOST, false);

		$identity = null;
		if ($email = $_POST->text('forgot', 'email')) {
			$identity = \Poodle\Identity\Search::byEmail($email);
		}
		if (!$identity) {
			$claimed_id = $_POST->text('forgot', 'auth_claimed_id');
			if ($claimed_id && $id = \Poodle\Auth\Detect::identityId(1, $claimed_id)) {
				$identity = \Poodle\Identity\Search::byID($id);
			}
		}
		if (!$identity) {
			return $this->viewForgotForm(_SORRYNOUSERINFO);
		}

		if (0 == $identity['user_level']) {
			cpg_error(_ACCSUSPENDED);
		}
		if (0 > $identity['user_level']) {
			cpg_error(_ACCDELETED);
		}

		$hash_key = \Poodle\Identity\Request::newPassword($identity);
		$from = 'noreply@'.str_replace('www.', '', $K->CFG->server->domain);
		$subject = _CODEFOR." {$identity->nickname}";
		$message = _USERACCOUNT." '{$identity->nickname}' "._AT." {$K->CFG->global->sitename} "
			._HASTHISEMAIL." "._AWEBUSERFROM
			." ". $_SERVER['REMOTE_ADDR']
			." ". _CODEREQUESTED ."\n\n"
			._WITHTHISCODE." ".\Poodle\URI::abs(\URL::index('&forgot='.$hash_key, true, true))."\n"
			._IFYOUDIDNOTASK2;
		if (!\Dragonfly\Email::send($mailer_message, $subject, $message, $identity->email, $identity->nickname, $from)) {
			cpg_error($mailer_message);
		}
		cpg_error(_CODEFOR." {$identity->nickname} "._MAILED, _TB_INFO, \URL::index('&forgot'));
		return;
/*
		$mail_resource = \Poodle\Resource::factory(39);
		if (!$mail_resource) {
			$this->viewForgotForm('Failed to send email');
			return;
		}
		// Fill the email template with needed data
		$MAIL = \Poodle\Mail::sender();
		$MAIL->addTo($identity->email, $identity->surname);
		$MAIL->activate_uri = \Poodle\URI::abs(preg_replace('#\\?.*$#D', '', $_SERVER['REQUEST_URI']).'?forgot='.$hash_key);
		$MAIL->identity = $identity;
		$MAIL->subject = str_replace('{sitename}', $K->CFG->site->name, $mail_resource->title);
		$MAIL->body = $mail_resource->toString($MAIL);
/*
		$MAIL = \Poodle\Mail::sender();
		// Fill the email template with needed data
		$MAIL->identity = $identity;
		$MAIL->activate_uri = \Poodle\URI::abs(preg_replace('#\\?.*$#D','', $_SERVER['REQUEST_URI']).'?forgot='.$hash_key);
		$MAIL->addTo($identity->email, $identity->surname);
		$MAIL->subject = $MAIL->L10N['Reset your password'];
		$MAIL->body    = $MAIL->L10N['Follow the following link to reset your password']
			.' '.$MAIL->activate_uri;
		// Wrap body inside layout
		$MAIL->body = $MAIL->toString('layouts/email');
*/
		if ($MAIL->send()) {
			$this->viewForm(false, 'Check your email to reset your password');
		} else {
			$this->viewForgotForm($MAIL->error);
		}
	}

	protected function processNewPassword()
	{
		if (\Dragonfly\Output\Captcha::validate($_POST)) {
			$K = \Dragonfly::getKernel();
			if ($time = self::disallowedAction(1, 'newpassword')) {
				header('Retry-After: '.time() - $time);
				\Poodle\Report::error(429, sprintf($K->L10N->get('Password recovery blocked until %s'), $K->L10N->date('DATE_F', $time)));
			}
			$request = \Poodle\Identity\Request::getPassword($_GET['forgot']);
			if ($request && $request['identity_id']) {
				$identity = \Poodle\Identity\Search::byID($request['identity_id']);
				if ($identity) {
//					if (empty($_POST['new_password']) || !\Poodle\Identity\Validate::password($_POST['new_password'], $identity->nickname, $errors)) {
					if (empty($_POST['new_password'])) {
						return $this->viewNewPasswordForm('The provided password is incomplete or not according to the given guidelines');
					}
					$identity->updateAuth(1, null, $_POST['new_password']);
					\Poodle\Identity\Request::removePassword($_GET['forgot']);
//					$msg = $K->L10N->get('Your password has been reset. Please login with your credentials');
//					\Dragonfly::closeRequest($msg, 303, preg_replace('#login\\?.*#D', '', $_SERVER['REQUEST_URI']), $msg);
					\URL::redirect(\URL::index('login'));
				}
			}
		}
		$this->viewNewPasswordForm('The provided information was incorrect');
	}

	protected function viewNewPasswordForm($error=false)
	{
		$K = \Dragonfly::getKernel();
		if ($error) {
			self::incAttempt(1, 'newpassword');
		} else if ($time = self::disallowedAction(1, 'newpassword')) {
			header('Retry-After: '.time() - $time);
			\Poodle\Report::error(429, sprintf($K->L10N->get('Password recovery blocked until %s'), $K->L10N->date('DATE_F', $time)));
		}

		$OUT = $K->OUT;
		\Dragonfly\Page::title($OUT->L10N['Set new password'], false);
		\Dragonfly\Output\Css::add('login/login');
//		$OUT->tpl_layout  = 'login';
		$OUT->new_password_info = sprintf($OUT->L10N['The password must be at least %d characters'], $K->CFG->member->minpass);
		$OUT->new_password_error = $error ? $OUT->L10N[$error] : false;
		$OUT->display('login/new-password');
	}

	protected function viewForgotForm($error=false)
	{
		$K = \Dragonfly::getKernel();
		if ($error) {
			self::incAttempt(1, 'forgot');
		} else if ($time = self::disallowedAction(1, 'forgot')) {
			header('Retry-After: '.time() - $time);
			\Poodle\Report::error(429, sprintf($K->L10N->get('Password recovery blocked until %s'), $K->L10N->date('DATE_F', $time)));
		}

		$OUT = $K->OUT;
		\Dragonfly\Page::title(_PASSWORDLOST, false);
		\Dragonfly\Output\Css::add('login/login');
//		$OUT->tpl_layout  = 'login';
		$OUT->login_error = $error;
		$OUT->display('login/forgot');
	}

	protected function viewForm($error=null, $found=null)
	{
		$K = \Dragonfly::getKernel();
		if ($error) {
			self::incAttempt(0, 'login');
		} else if ($time = self::disallowedAction(0, 'login')) {
			header('Retry-After: '.time() - $time);
			\Poodle\Report::error(429, sprintf($K->L10N->get('Login blocked until %s'), $K->L10N->date('DATE_F', $time)));
		}

		\Dragonfly\Output\Js::add('modules/login/javascript/login.js');
		\Dragonfly\Output\Css::add('login/login');

		$OUT = $K->OUT;
//		$OUT->tpl_layout     = 'login';
		$OUT->login_error    = $error ? $OUT->L10N[$error] : false;
		$OUT->auth_providers = self::getProviders();
		$options = static::getOptions();
		$OUT->login_redirect_uri = $options['redirect_uri'];
		$OUT->display('login/form');
	}

	protected function doLogin($provider_id)
	{
		if ($provider_id) {
			$provider = \Poodle\Auth\Provider::getById($provider_id);
			if ($provider instanceof \Poodle\Auth\Provider) {
				$credentials = isset($_POST['auth'][$provider_id]) ? $_POST['auth'][$provider_id] : array();
				$credentials['redirect_uri'] = \URL::index("&auth={$provider_id}");
				$result = \Dragonfly::getKernel()->IDENTITY->authenticate($credentials, $provider);
				return $this->processAuthProviderResult($result, $provider);
			}
		}
		$this->viewForm('Unknown login method');
	}

	protected function processAuthProviderResult($result, \Poodle\Auth\Provider $provider)
	{
		$options = static::getOptions();
		$K = \Dragonfly::getKernel();
		if ($result instanceof \Poodle\Auth\Result\Success) {

			// Autocreate identity
			if (!$K->IDENTITY->isMember() && $result->claimed_id) {
				$K->IDENTITY->save();
				$K->IDENTITY->updateAuth($provider->id, $result->claimed_id);
				$K->IDENTITY->addToGroup(1);
			}

			if (!$K->IDENTITY->isMember()) {
				return $this->viewForm('Unknown login');
			}

			$K->IDENTITY->updateLastVisit();
//			\Poodle\L10N::setGlobalLanguage($K->IDENTITY->language);
			\Poodle\LOG::info(\Poodle\LOG::LOGIN, get_class($provider));

			if ($options['cookie']) {
				$class = isset($K->CFG->auth_cookie->class) ? $K->CFG->auth_cookie->class : 'Poodle\\Auth\\Providers\\Cookie';
				$class::set();
			}

			$uri = $options['redirect_uri'];

			if (XMLHTTPRequest) {
				\Poodle\HTTP\Status::set(202);
//				header('Content-Type: application/json');
//				echo json_encode(array('status' => '302','location' => $uri));
			} else {
//				\Poodle\Notify::success($K->L10N['You successfully logged in']);
				\URL::redirect($uri);
			}
		}
		else if ($result instanceof \Poodle\Auth\Result\Redirect) {
			$_SESSION['DRAGONFLY_LOGIN'] = $options;
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
			$_SESSION['DRAGONFLY_LOGIN'] = $options;
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
//				$K->OUT->tpl_layout = 'login';
//				$K->OUT->display(null, $this->result2html($result, $provider));
				echo $this->result2html($result, $provider);
			}
		}
		else if ($result instanceof \Poodle\Auth\Result\Error) {

			\Poodle\LOG::error(\Poodle\LOG::LOGIN, get_class($provider).'#'.$result->getCode().': '.$result->getMessage());

			return $this->viewForm('The provided information is unknown or incorrect. Please try again');
		}
		else {
			print_r($result);
		}
	}

	protected function result2html(\Poodle\Auth\Result\Form $result, $provider)
	{
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->auth_result = $result;
		$OUT->auth_provider = $provider;
		return $OUT->toString('login/redirect-form');
	}

	protected static function getOptions()
	{
		static $options;
		if (!$options) {
			$options = empty($_SESSION['DRAGONFLY_LOGIN']) ? array() : $_SESSION['DRAGONFLY_LOGIN'];
			unset($_SESSION['DRAGONFLY_LOGIN']);
			if (!isset($options['cookie'])) {
				$options['cookie'] = (!empty($_POST['auth_cookie']) || !empty($_GET['auth_cookie']));
			}
			if (!isset($options['redirect_uri'])) {
				$options['redirect_uri'] = static::getRedirectURI();
			}
			if (!$options['redirect_uri'] || '/' !== $options['redirect_uri'][0]) {
				$options['redirect_uri'] = '';
			}
		}
		return $options;
	}

	protected static function getRedirectURI()
	{
		$uri = isset($_POST['redirect_uri']) ? $_POST['redirect_uri'] : $_GET['redirect_uri'];
		$uri = false === strpos($uri,'/') ? \Poodle\Base64::urlDecode($uri, true) : $uri;
		if ($uri && '/' === $uri[0]) {
			return $uri;
		}
	}

}

if (class_exists('Dragonfly', false)) {
	\Dragonfly::getKernel()->L10N->load('Your_Account');
	\Dragonfly\Page::title(_USERLOGIN, false);
	\Dragonfly\Page::metatag('robots', 'noindex, nofollow');
	$class = new Login;
	$class->{$_SERVER['REQUEST_METHOD']}();
}
