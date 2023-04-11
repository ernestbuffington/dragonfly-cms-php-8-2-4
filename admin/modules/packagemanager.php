<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	https://dragonfly.coders.exchange

	Released under GNU GPL version 2 or any later version

	A free program released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_PAGES') || !can_admin()) { exit; }
\Dragonfly\Page::title('Package Manager');

use Dragonfly\PackageManager\Install;
use Poodle\PackageManager\Remove;
use Poodle\PackageManager\Repository;

class Dragonfly_Admin_PackageManager
{

	public static function GET()
	{
		$K = \Dragonfly::getKernel();
		$OUT = $K->OUT;
		$SQL = $K->SQL;
		if (isset($_GET['settings'])) {
			$OUT->repositories = $SQL->query("SELECT
				repo_id         id,
				repo_name       name,
				repo_enabled    enabled,
				repo_location   location,
				repo_public_key public_key
			FROM {$SQL->TBL->packagemanager_repos}
			ORDER BY LOWER(repo_name)");
			$OUT->display('dragonfly/packagemanager/settings');
		}

		else if (isset($_GET['installed'])) {
			\Dragonfly\Output\Js::add('includes/poodle/javascript/tablefilter.js');
			\Dragonfly\Output\Js::add('includes/poodle/javascript/tablesort.js');
			\Dragonfly\Output\Js::add('includes/dragonfly/javascript/packagemanager.js');
			$methods = Install::getPossibleWriteMethods();
			$OUT->packages = $SQL->uFetchAll("SELECT
				package_type    type,
				package_name    name,
				package_version version,
				repo_name       repository
			FROM {$SQL->TBL->packagemanager_installed}
			LEFT JOIN {$SQL->TBL->packagemanager_repos} USING (repo_id)
			ORDER BY package_type, LOWER(package_name)");
			$OUT->ftp = !$methods['direct']['umask'];
			$OUT->ftpdir = preg_replace('#^.*?/(htdocs|https?docs|domains|public_html|private_html|www)(/.*)?$#D', '/$1$2', getcwd());
			$OUT->display('dragonfly/packagemanager/installed');
		}

		else if (isset($_GET['list'])) {
			$installed = Install::getInstalledPackages();
			$qr = $SQL->query("SELECT title, version FROM {$SQL->TBL->modules}");
			$modules = array();
			while ($r = $qr->fetch_row()) {
				$modules[$r[0]] = $r[1];
			}
			$qr = $SQL->query("SELECT
				repo_id,
				repo_name,
				repo_location,
				repo_public_key
			FROM {$SQL->TBL->packagemanager_repos}
			WHERE repo_enabled = 1");
			$OUT->repositories = array();
			while ($r = $qr->fetch_row()) {
				$repo = new Repository();
				$repo->id = $r[0];
				$repo->name = $r[1];
				$repo->location = $r[2];
				$repo->public_key = $r[3];
				foreach ($repo->packages as $package) {
					$id = "{$package->type}-{$package->name}";
					$version = isset($installed[$id]) ? $installed[$id] : false;
					$package->version_installed = ('module' === $package->type
						&& isset($modules[$package->name])
						&& version_compare($modules[$package->name], $version, '>')
					) ? $modules[$package->name] : $version;
					$package->install = version_compare($package->version, $package->version_installed, '>');
				}
				$OUT->repositories[] = $repo;
			}
			\Dragonfly\Output\Js::add('includes/poodle/javascript/tablefilter.js');
			\Dragonfly\Output\Js::add('includes/poodle/javascript/tablesort.js');
			\Dragonfly\Output\Js::add('includes/dragonfly/javascript/packagemanager.js');
			$methods = Install::getPossibleWriteMethods();
			$OUT->ftp = !$methods['direct']['umask'];
			$OUT->ftpdir = preg_replace('#^.*?/(htdocs|https?docs|domains|public_html|private_html|www)(/.*)?$#D', '/$1$2', getcwd());
			$OUT->display('dragonfly/packagemanager/list');
		}

		else {
			$OUT->disk_space = array(
				'total' => disk_total_space(BASEDIR),
				'free' => disk_free_space(BASEDIR),
			);
			$OUT->display('dragonfly/packagemanager/index');
		}
	}

	public static function POST()
	{
		if (\Dragonfly::isDemo()) {
			cpg_error('Deactivate demo mode', 403);
		}
		if (XMLHTTPRequest) {
			\Poodle::ob_clean();
			ob_implicit_flush();
			set_time_limit(0);
			if ('install' == $_POST['action']) {
				if (!empty($_POST['ftp']['user']) && isset($_POST['ftp']['pass']) && !empty($_POST['ftp']['path'])) {
					$install = new Install($_POST['ftp']['user'], $_POST['ftp']['pass'], $_POST['ftp']['path']);
				} else {
					$install = new Install();
				}
				static::init($install);
				$repo = new Repository($_POST['repo']);
				if ($install->repositoryPackage($repo, $_POST['name'])) {
					static::pushJSON(array('complete' => true));
				}
			}
			else if ('remove' == $_POST['action']) {
				if (!empty($_POST['ftp']['user']) && isset($_POST['ftp']['pass']) && !empty($_POST['ftp']['path'])) {
					$remove = new Remove($_POST['ftp']['user'], $_POST['ftp']['pass'], $_POST['ftp']['path']);
				} else {
					$remove = new Remove();
				}
				static::init($remove);
				$remove->package($_POST['name']);
				static::pushJSON(array('complete' => true));
			}
			\Dragonfly::getKernel()->CACHE->delete('Dragonfly/update_monitor');
//			set_time_limit(\Poodle\PHP\ini::get('max_execution_time'));
			exit;
		}

		if (isset($_POST['add']) && !empty($_POST['add_repo'])) {
			\Poodle\PackageManager\Repositories::add(
				$_POST['add_repo']['name'],
				$_POST['add_repo']['location'],
				$_POST['add_repo']['public_key'],
				!empty($_POST['add_repo']['enabled'])
			);
			URL::redirect(URL::admin('&settings'));
		}

		if (isset($_POST['install'])) {
			if (!empty($_POST['ftp']['user']) && isset($_POST['ftp']['pass']) && !empty($_POST['ftp']['path'])) {
				$install = new Install($_POST['ftp']['user'], $_POST['ftp']['pass'], $_POST['ftp']['path']);
			} else {
				$install = new Install();
			}
			static::init($install);
			foreach ($_POST['packages'] as $repository_id => $packages) {
				$repo = new Repository($repository_id);
				foreach ($packages as $package_name) {
					$install->repositoryPackage($repo, $package_name);
				}
			}
			exit;
		}

		if (isset($_POST['remove'])) {
			if (!empty($_POST['ftp']['user']) && isset($_POST['ftp']['pass']) && !empty($_POST['ftp']['path'])) {
				$remove = new Remove($_POST['ftp']['user'], $_POST['ftp']['pass'], $_POST['ftp']['path']);
			} else {
				$remove = new Remove();
			}
			static::init($remove);
			foreach ($_POST['packages'] as $package_name) {
				$remove->package($package_name);
			}
			exit;
		}
	}

	protected static function pushJSON(array $data)
	{
		echo \Poodle::dataToJSON($data) . "\n";
	}

	protected static function init($obj)
	{
		$obj->addEventListener('error', 'Dragonfly_Admin_PackageManager::onError');
		$obj->addEventListener('progress', 'Dragonfly_Admin_PackageManager::onProgress');
		if (!XMLHTTPRequest) {
			echo '<script type="text/javascript">
			function pb(n, v, f)
			{
				document.getElementById(\'pb-\' + n).value = v;
				document.getElementById(\'pbmsg-\' + n).textContent = f;
			}
			</script>';
		}
	}

	public static function onError(\Poodle\Events\Event $event)
	{
		if (XMLHTTPRequest) {
			static::pushJSON(array('error' => $event->message));
		} else {
			echo '<div class="error">' . htmlspecialchars($event->message) . '</div>';
		}
	}

	public static function onProgress(\Poodle\Events\Event $event)
	{
		if (XMLHTTPRequest) {
			if (!is_null($event->value)) {
				static::pushJSON(array('progress' => array(
					'max' => max(1, $event->max),
					'value' => $event->value,
					'message' => $event->message
				)));
			}
		} else if (is_null($event->value)) {
			echo '<fieldset style="margin:1em 0"><legend><b>'.$event->message.': </b></legend>'
				. '<progress id="pb-'.$event->task.'" max="'.max(1, $event->max).'" style="display:block;width:100%"></progress>'
				. '<div id="pbmsg-'.$event->task.'"></div>'
				. '</fieldset>';
		} else {
			echo "<script type='text/javascript'>pb('{$event->task}', {$event->value}, '{$event->message}')</script>";
		}
	}

}

switch ($_SERVER['REQUEST_METHOD'])
{
	case 'GET':
		Dragonfly_Admin_PackageManager::GET();
		break;
	case 'POST':
		Dragonfly_Admin_PackageManager::POST();
		break;
	default:
		exit('Invalid request method');
}
