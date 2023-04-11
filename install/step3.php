<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }

class Installer extends \Dragonfly\ModManager\SetupBase
{
	public function pre_install(){return false;}
	public function post_install(){return false;}
	public function pre_upgrade($prev_version){return false;}
	public function post_upgrade($prev_version){return false;}
	public function pre_uninstall(){return false;}
	public function post_uninstall(){return false;}
}

inst_header();
$server = $db->get_details();
if ((!isset($current_version) || !$current_version) && !isset($_POST['version'])) {
	if (isset($db->TBL->users)) {
		echo $instlang['s1_unknown'];
		return;
	} else {
		echo $instlang['s1_new'];
		$current_version = 0;
	}
	echo '<table>
	<tr>
	  <th colspan="2" nowrap="nowrap">'.$instlang['s1_dbconfig'].'</th>
	  <td><i class="infobox"><span>'.$instlang['s1_database'].'</span></i></td>
	</tr><tr>
	  <td colspan="3"><hr noshade="noshade" size="1" /></td>
	</tr><tr>
	  <td>'.$server['engine'].'</td><td>'.$server['version'].'</td>
	  <td><i class="infobox"><span>'.sprintf($instlang['s1_server2'], $server['engine']).'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_host'].'</td><td>'.$dbms['master']['host'].'</td>
	  <td><i class="infobox"><span>'.$instlang['s1_host2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_dbname'].'</td><td>'.$dbms['master']['database'].'</td>
	  <td><i class="infobox"><span>'.$instlang['s1_dbname2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s1_prefix'].'</td><td>'.$prefix.'</td>
	  <td><i class="infobox"><span>'.$instlang['s1_prefix2'].'</span></i></td>
	</tr><tr>
	  <td colspan="3"><hr noshade="noshade" size="1" /></td>
	</tr>
</table>'
	.$instlang['s1_correct'].'<p align="center"><input type="hidden" name="step" value="3" />
	<input type="hidden" name="version" value="'.$current_version.'" />
	<input type="submit" value="'.$instlang['s1_build_db'].'" class="formfield" /></p>';
}
else {
	$version = isset($_POST['version']) ? $_POST['version'] : $current_version;
	if ($version) {
		$upgrade = new \Dragonfly\Setup\Upgrade($version);
		echo '<div style="text-align:left">';
		if ($upgrade->run(true)) {
			echo $instlang['s3_finnish'];
		}
		echo '</div>';
	} else {
		if ('MySQL' == $db->engine && $server['unicode']) {
			$db->setSchemaCharset();
		}

		# build the database
		set_time_limit(0);
		ignore_user_abort();
		\Poodle\PHP\INI::set('memory_limit', -1);

		echo '<div style="text-align:left">';

		$XML = $db->XML->getImporter();
		$XML->addEventListener('afterquery', function(){echo ' .';flush();});

		echo '<h3>'.$instlang['s3_sync_schema'].': </h3>';
		$table_files = array('core', 'auth');
		foreach ($table_files as $file) {
			echo $file;
			flush();
			if (!$XML->syncSchemaFromFile(BASEDIR."includes/dragonfly/setup/db/schemas/{$file}.xml")) {
				print_r($XML->errors);
				exit;
			}
			echo '<br />';
		}

		echo '<h3>'.$instlang['s3_sync_data'].':</h3>';
		$data_files = array('core', 'auth');
		foreach ($data_files as $file) {
			echo $file;
			flush();
			if (!$XML->syncSchemaFromFile(BASEDIR."includes/dragonfly/setup/db/data/{$file}.xml")) {
				print_r($XML->errors);
				exit;
			}
			echo '<br />';
		}

		echo '<h3>'.$instlang['s3_sync_done'].'</h3>';

		try {
			$db->begin();
			echo '<h3>'.$instlang['s3_exec_queries'].':</h3>';
			$installer = new Installer();
			$installer->progress = ' .';
//			$installer->test = DF_MODE_DEVELOPER;
			$installer->add_query('INSERT', 'message', "1, 'Welcome to Dragonfly!', '[align=center]Thanks for downloading Dragonfly ".Dragonfly::VERSION.". We hope you enjoy your new website!\r\n\r\nThis message can be removed easily through the [url={$adminindex}]administration menu[/url].[/align]', ".time().", 0, 1, 0, ''");
			$installer->add_query('UPDATE', 'config_custom', "cfg_value='".date('F j, Y')."' WHERE cfg_name='global' AND cfg_field='startdate'");
			$installer->add_query('UPDATE', 'modules_cat', "link='".\Dragonfly::$URI_INDEX."' WHERE name='_HOME'");
			if (!$installer->test) {
				$installer->add_query('UPDATE', 'config_custom', "cfg_value='".\Dragonfly::DB_VERSION."' WHERE cfg_name='global' AND cfg_field='db_version'");
			}
			$installer->exec();
		} catch (Exception $e) {
			$db->rollback();
			echo $e.'<br /><br />'.$instlang['s1_fatalerror'];
			return;
		}

		$modules = array_keys(Dragonfly\Modules::ls('install/cpg_inst.php', false));
		if ($modules) {
			define('ADMIN_MOD_INSTALL', 1);
			echo '<h3>'.$instlang['s3_inst_modules'].':</h3>';
			foreach ($modules as $module) {
				try {
					if ($installer = new \Dragonfly\ModManager\Setup($module, true)) {
						if ($installer->add_module()) {
							echo $module.' -> '.$instlang['s3_inst_done'].'<br />';
							continue;
						}
					}
					echo $module.' -> '.$instlang['s3_inst_fail'].': '.$installer->error.'<br />';
				} catch (\Exception $e) {
					echo $module.' -> '.$instlang['s3_inst_fail'].': '.$e->getMessage().'<br />';
				}
			}
		}

		echo '</div>';

		echo $instlang['s1_donenew'].'<p align="center"><input type="hidden" name="step" value="4" /><input type="submit" value="'.$instlang['s1_necessary_info'].'" class="formfield" /></p>';
		flush();
	}
}
