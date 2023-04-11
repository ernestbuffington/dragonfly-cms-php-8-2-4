<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Auth;

class Admin
{
	public
		$title = 'Authentication',
		$allowed_methods = array('GET','HEAD','POST');

	public function GET()
	{
		$K = \Poodle::getKernel();

		$providers = array();
		$auth_providers = $K->SQL->query("SELECT
			auth_provider_id id,
			auth_provider_class class,
			auth_provider_is_2fa is_2fa,
			auth_provider_mode mode,
			auth_provider_name name
		FROM {$K->SQL->TBL->auth_providers}");
		foreach ($auth_providers as $auth) {
			$auth['uid']    = preg_replace('#[^a-z0-9]#','-',mb_strtolower($auth['name']));
			$auth['detect'] = array();
			$detections = $K->SQL->query("SELECT
				auth_detect_name name,
				auth_detect_regex regex,
				auth_detect_discover_uri discover_uri
			FROM {$K->SQL->TBL->auth_providers_detect}
			WHERE auth_provider_id={$auth['id']}");
			foreach ($detections as $detect) {
				$auth['detect'][] = $detect;
			}

			$auth['config'] = array();
			if (!empty($auth['class']) && class_exists($auth['class'])) {
				$auth['config'] = $auth['class']::getConfigOptions();
			}

			$providers[] = $auth;
		}

		if (!isset($K->CFG->auth_cookie->ip_protection)) {
			$K->CFG->set('auth_cookie', 'ip_protection', 1);
		}

		$K->OUT->auth_providers = $providers;
		$K->OUT->display('poodle/auth/admin');
	}

	public function POST()
	{
		$tbl = \Poodle::getKernel()->SQL->TBL->auth_providers;
		foreach ($_POST['auth_provider'] as $id => $data) {
			if (!empty($data['class']) && class_exists($data['class'])) {
				$tbl->update(array(
					'auth_provider_class' => $data['class'],
					'auth_provider_mode'  => empty($data['mode']) ? 0 : array_sum($data['mode']),
					'auth_provider_name'  => $data['name']
				), 'auth_provider_id='.$id);

				// Always set, as unchecked checkboxes are not posted but must be saved
				$data['class']::setConfigOptions(empty($data['config']) ? array() : $data['config']);
			}
		}

		$CFG = \Poodle::getKernel()->CFG;
		$CFG->set('auth', 'attempts',             $_POST->uint('config','auth','attempts'));
		$CFG->set('auth', 'attempts_timeout',     $_POST->uint('config','auth','attempts_timeout'));
		$CFG->set('auth', 'https',                $_POST->bool('config','auth','https'));
		$CFG->set('auth', 'default_pass_hash_algo', $_POST->raw('config', 'auth', 'default_pass_hash_algo'));
		$CFG->set('auth_cookie', 'allow',         $_POST->bool('config','auth_cookie','allow'));
		$CFG->set('auth_cookie', 'ip_protection', $_POST->bool('config','auth_cookie','ip_protection'));
		$CFG->set('auth_cookie', 'name',          $_POST->text('config','auth_cookie','name'));
		$CFG->set('auth_cookie', 'timeout',       $_POST->uint('config','auth_cookie','timeout'));
		$CFG->set('auth_cookie', 'cipher',        $_POST->text('config','auth_cookie','cipher'));
		$CFG->set('auth_cookie', 'cryptkey',      $_POST->text('config','auth_cookie','cryptkey') ?: sha1(mt_rand().microtime()));
		$CFG->set('auth_cookie', 'compression',   $_POST->text('config','auth_cookie','compression'));

		\Poodle\Notify::success('Configuration saved');
		\Poodle\URI::redirect($_SERVER['REQUEST_URI']);
	}

}
