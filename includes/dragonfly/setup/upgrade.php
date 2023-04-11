<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Setup;

class Upgrade extends \Dragonfly\ModManager\SetupBase
{
	public function __construct($prev_version = null)
	{
		global $db;
		$K = \Dragonfly::getKernel();
		$this->db = $db ?: $K->SQL;
		$this->prev_version = $prev_version ?: $K->CFG->global->db_version ?: $K->CFG->global->Version_Num;
		if (version_compare($this->prev_version, '9.3.2.2', '<')) {
			exit('Upgrade needs atleast v9.3.2.2');
		}
		parent::__construct();
	}

	public function pre_install(){return false;}
	public function post_install(){return false;}

	public function pre_upgrade($prev_version)
	{
		$db = $this->db;

		if (version_compare($prev_version, '10.0.9.33', '<')) {
			$db->delete('security_agents', 'agent_name LIKE "Google%"');
			$db->delete('security_agents', 'agent_name LIKE "Yahoo%"');
			$db->delete('security_agents', 'agent_name LIKE "Yandex%"');
		}

		if (version_compare($prev_version, '10.0.9.37', '<')) {
			$db->query("ALTER TABLE {$db->TBL->users} ADD user_nickname_lc VARCHAR(128)");
			$db->query("UPDATE {$db->TBL->users} SET user_nickname_lc=LOWER(username)");
			// Fix duplicate names
			$qr = $db->query("SELECT user_nickname_lc FROM {$db->TBL->users} GROUP BY user_nickname_lc HAVING COUNT(*)>1");
			while ($r = $qr->fetch_row()) {
				$qr2 = $db->query("SELECT user_id FROM {$db->TBL->users} WHERE LOWER(username)='{$r[0]}' ORDER BY user_id ASC LIMIT 1000 OFFSET 1");
				$ids = array();
				while ($r = $qr2->fetch_row()) { $ids[] = $r[0]; }
				$db->query("UPDATE {$db->TBL->users} SET user_active=0, username=CONCAT(username,'-',user_id), user_nickname_lc=CONCAT(user_nickname_lc,'-',user_id) WHERE user_id IN (".implode(',',$ids).")");
			}
		}

		if (version_compare($prev_version, '10.0.9.41', '<')) {
			foreach (\Dragonfly\L10N\V9::$browserlang as $new => $old) {
				$db->query("UPDATE {$db->TBL->blocks} SET blanguage='{$new}' WHERE blanguage='{$old}'");
				$db->query("UPDATE {$db->TBL->history} SET language='{$new}' WHERE language='{$old}'");
				$db->query("UPDATE {$db->TBL->message} SET mlanguage='{$new}' WHERE mlanguage='{$old}'");
				$db->query("UPDATE {$db->TBL->users} SET user_lang='{$new}' WHERE user_lang='{$old}'");
				$db->query("UPDATE {$db->TBL->users_temp} SET user_lang='{$new}' WHERE user_lang='{$old}'");
			}

			list($oldlng) = $db->uFetchRow("SELECT cfg_value FROM {$db->TBL->config_custom} WHERE cfg_name='global' AND cfg_field='language'");
			if ($newlng = array_search($oldlng,\Dragonfly\L10N\V9::$browserlang)) {
				$db->query("UPDATE {$db->TBL->config_custom} SET cfg_value='{$newlng}' WHERE cfg_name='global' AND cfg_field='language'");
			}
		}

		if (version_compare($prev_version, '10.0.9.44', '<')) {
			$result = $db->query("SELECT
				group_id,
				user_id,
				MIN(user_pending) user_pending
			FROM {$db->TBL->bbuser_group}
			GROUP BY group_id, user_id
			HAVING COUNT(group_id)>1");
			if ($result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$db->delete('bbuser_group', "group_id={$row['group_id']} AND user_id={$row['user_id']}");
					$db->insert('bbuser_group', $row);
				}
			}
		}

		if (version_compare($prev_version, '10.0.11.6', '<')) {
			$db->query("DELETE FROM {$db->TBL->referer}");
		}

		if (version_compare($prev_version, '10.0.35.9326', '<')) {
			$db->query("UPDATE {$db->TBL->config_custom} SET cfg_name='mail' WHERE cfg_name='email' AND cfg_field='return_path'");
		}
	}

	public function post_upgrade($prev_version)
	{
		$db = $this->db;

		if (version_compare($prev_version, '10.0.9.23', '<')) {
			$data = $db->list_columns($db->TBL->comments);
			if (isset($data['msg_author'])) {
				$this->add_query('DEL', 'comments', 'msg_author');
				$this->add_query('DEL', 'comments', 'msg_body');
				$this->add_query('DEL', 'comments', 'msg_date');
				$this->add_query('DEL', 'comments', 'author_md5_id');
				$this->add_query('DEL', 'comments', 'author_id');
				$this->add_query('DEL', 'comments', 'msg_raw_ip');
				$this->add_query('DEL', 'comments', 'msg_hdr_ip');
				$this->add_query('DROP_INDEX', 'comments', 'com_pic_id');
			}
			$data = $db->list_indexes($db->TBL->config_custom);
			if (isset($data['unique_cfg'])) {
				$this->add_query('DROP_INDEX', 'config_custom', 'unique_cfg');
			}
			$data = null;
		}

		if (version_compare($prev_version, '10.0.9.28', '<')) {
			$this->add_query('UPDATE', 'counter', 'type="bot", var="Other" WHERE type="browser" AND var="Bot"', 'type="browser" var="Bot" WHERE type="bot" AND var="Other"');
		}

		if (version_compare($prev_version, '10.0.9.29', '<')) {
			$ips = $domains = array();
			$result = $db->query('SELECT ban_ipv4_s, ban_ipv4_e, ban_ipn, ban_string, ban_type, ban_details FROM {security} WHERE ban_type IN (0,2,3,8)', \Poodle\SQL::ADD_PREFIX);
			while ($row = $result->fetch_assoc()) {
				switch ($row['ban_type']):
					case 0:
					case 8:
						$ipn_s = $db->escapeBinary($row['ban_ipn'] ?: inet_pton(long2ip($row['ban_ipv4_s'])));
						$ipn_e = $row['ban_ipv4_e'] ? $db->escapeBinary(inet_pton(long2ip($row['ban_ipv4_e']))) : 'DEFAULT';
						$details = $row['ban_details'] ? "'".$db->escape_string($row['ban_details'])."'" : 'DEFAULT';
						$ips[] = "($ipn_s, $ipn_e, {$row['ban_type']}, $details)";
						break;
					case 2:
					case 3:
						$domains[] = "('".$db->escape_string($row['ban_string'])."', {$row['ban_type']})";
						break;
					default:
					break;
				endswitch;
			}
			if (count($insert)) {
				$this->add_query('INSERT_MULTIPLE', 'security_ips', array('ipn_s, ipn_e, type, details', implode(',', $insert))/*, 'type IN (0,8)'*/);
			}
			if (count($domains)) {
				$this->add_query('INSERT_MULTIPLE', 'security_domains', array('ban_string, ban_type', implode(',', $domains))/*, 'type IN (2,3)'*/);
			}
			$db->query("DELETE FROM {$db->TBL->security_agents} WHERE agent_name = 'MSN'");
			//$this->add_query('DROP', 'security');
		}

		if (version_compare($prev_version, '10.0.9.35', '<')) {
			list($hits) = $db->uFetchRow("SELECT SUM(hits) FROM {$db->TBL->stats_hour}");
			list($count) = $db->uFetchRow("SELECT SUM(count) FROM {$db->TBL->counter} WHERE type IN ('browser','bot')");
			if ($hits > $count) $db->query("UPDATE {$db->TBL->counter} SET count=count+{$hits}-{$count} WHERE type='browser' AND var='Other'");
		}

		if (version_compare($prev_version, '10.0.9.36', '<')) {
			$db->query("UPDATE {$db->TBL->users} SET user_timezone='Etc/GMT'||CEIL(user_timezone) WHERE SUBSTRING(user_timezone,1,1)='-'");
			$db->query("UPDATE {$db->TBL->users} SET user_timezone='Etc/GMT+'||FLOOR(user_timezone) WHERE SUBSTRING(user_timezone,1,1) IN ('0','1')");
		}

		$sha2 = false;
		try {
			list($sha2) = $db->uFetchRow("SELECT SHA2('password', '256')");
			$sha2 = ('5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8' === $sha2);
		} catch (\Exception $e){}
		if (version_compare($prev_version, '10.0.9.37', '<')) {
			$db->query("UPDATE {$db->TBL->admins} SET pwd='Dragonfly_Admin_v9pass:'||".($sha2?"SHA2(pwd, '256')":"pwd"));
			$db->query("INSERT INTO {$db->TBL->auth_identities} (identity_id, auth_provider_id, auth_claimed_id, auth_password)
				SELECT user_id, 1, SHA1(LOWER(username)), 'Dragonfly_Identity_v9pass:'||".($sha2?"SHA2(user_password, '256')":"user_password")." FROM {$db->TBL->users} WHERE user_id > 1");
			$db->query("ALTER TABLE {$db->TBL->users} DROP COLUMN user_password, DROP COLUMN femail");
		}

		if (version_compare($prev_version, '10.0.10.1', '<')) {
			// INSERT INTO modules VALUES (login) ???
			list($value) = $db->uFetchRow("SELECT cfg_value FROM {$db->TBL->config_custom} WHERE cfg_name='cookie' AND cfg_field='admin'");
			$db->query("UPDATE {$db->TBL->config_custom} SET cfg_value='{$value}' WHERE cfg_name='admin_cookie' AND cfg_field='name'");
			list($value) = $db->uFetchRow("SELECT cfg_value FROM {$db->TBL->config_custom} WHERE cfg_name='cookie' AND cfg_field='member'");
			$db->query("UPDATE {$db->TBL->config_custom} SET cfg_value='{$value}' WHERE cfg_name='auth_cookie' AND cfg_field='name'");
			$db->query("DELETE FROM {$db->TBL->config_custom} WHERE cfg_name='cookie' AND cfg_field IN ('admin', 'member')");
		}

		if (version_compare($prev_version, '10.0.10.2', '<')) {
			$db->query("UPDATE {$db->TBL->modules_cat} SET link_type='0', link='/' WHERE name='_HOME'");
		}

		if (version_compare($prev_version, '10.0.11.5', '<')) {
			try {
				// Drop if it still exists
				$db->query("ALTER TABLE {$db->TBL->bbposts_text} DROP COLUMN bbcode_uid");
			} catch (\Exception $e){}
		}

		if (version_compare($prev_version, '10.0.11.6', '<')) {
			try {
				$db->query("UPDATE {$db->TBL->users} SET
					user_new_privmsg = (SELECT COUNT(*) FROM {$db->TBL->privmsgs} WHERE privmsgs_type = 1 AND privmsgs_to_userid = user_id),
					user_unread_privmsg = (SELECT COUNT(*) FROM {$db->TBL->privmsgs} WHERE privmsgs_type = 5 AND privmsgs_to_userid = user_id)");
			} catch (\Exception $e){}
		}

		if (version_compare($prev_version, '10.0.11.7', '<')) {
			$fields = array('user_newpasswd','user_dst','user_msnm','user_actkey','user_sig_bbcode_uid','user_emailtime');
			foreach ($fields as $field) {
				try {
					$db->query("ALTER TABLE {$db->TBL->users} DROP COLUMN {$field}");
				} catch (\Exception $e){}
			}
			$fields = array('user_dst','user_msnm','femail');
			foreach ($fields as $field) {
				try {
					$db->query("ALTER TABLE {$db->TBL->users_temp} DROP COLUMN {$field}");
				} catch (\Exception $e){}
			}
			$db->query("DELETE FROM {$db->TBL->users_fields} WHERE field IN ('user_dst','user_msnm','femail')");
		}

		if (version_compare($prev_version, '10.0.12.2', '<')) {
			try {
				$db->query("ALTER TABLE {$db->TBL->sessions} DROP COLUMN sess_time");
			} catch (\Exception $e){}
		}

		if (version_compare($prev_version, '10.0.11.5', '<')) {
			try {
				// Drop if it still exists
				$db->query("ALTER TABLE {$db->TBL->privmsgs_text} DROP COLUMN privmsgs_bbcode_uid");
			} catch (\Exception $e){}
			$db->query("UPDATE {$db->TBL->users} SET user_posts = 0 WHERE user_id = 1");
		}

		if (version_compare($prev_version, '10.0.13.0', '<')) {
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'blocks/' || blockfile WHERE blockfile LIKE 'block-%'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-last_pictures_thumb.php' WHERE blockfile = 'blocks/block-CPG-center-Last_pictures_thumb.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-random_pictures.php' WHERE blockfile = 'blocks/block-CPG-center-Random_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-scroll-last_pictures.php' WHERE blockfile = 'blocks/block-CPG-center-scroll-Last_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-scroll-last_pictures_thumb.php' WHERE blockfile = 'blocks/block-CPG-center-scroll-Last_pictures_thumb.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-scroll-random_pictures.php' WHERE blockfile = 'blocks/block-CPG-center-scroll-Random_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-scroll-top_rate_pictures.php' WHERE blockfile = 'blocks/block-CPG-center-scroll-Top_rate_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-scroll-top_view_pictures.php' WHERE blockfile = 'blocks/block-CPG-center-scroll-Top_view_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/center-top_rate_pictures.php' WHERE blockfile = 'blocks/block-CPG-center-Top_rate_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/last_comments.php' WHERE blockfile = 'blocks/block-CPG-Last_comments.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/last_pictures_thumb.php' WHERE blockfile = 'blocks/block-CPG-Last_pictures_thumb.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/random_pictures.php' WHERE blockfile = 'blocks/block-CPG-Random_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/scroll-last_comments.php' WHERE blockfile = 'blocks/block-CPG-scroll-Last_comments.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/scroll-last_pictures_thumb.php' WHERE blockfile = 'blocks/block-CPG-scroll-Last_pictures_thumb.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/scroll-least_view_pictures.php' WHERE blockfile = 'blocks/block-CPG-scroll-least_view_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/scroll-random_pictures.php' WHERE blockfile = 'blocks/block-CPG-scroll-Random_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/scroll-top_rate_pictures.php' WHERE blockfile = 'blocks/block-CPG-scroll-Top_rate_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/scroll-top_view_pictures.php' WHERE blockfile = 'blocks/block-CPG-scroll-Top_view_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/top_rate_pictures.php' WHERE blockfile = 'blocks/block-CPG-Top_rate_pictures.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/coppermine/blocks/stats.php' WHERE blockfile = 'blocks/block-CPG_Stats.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Forums/blocks/recent_topics.php' WHERE blockfile = 'blocks/block-Forums.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Forums/blocks/scroll_last_posts.php' WHERE blockfile = 'blocks/block-scroll_Last_posts.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Forums/blocks/stats.php' WHERE blockfile = 'blocks/block-Forum_Stats.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Groups/blocks/groups.php' WHERE blockfile = 'blocks/block-Groups.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/News/blocks/big_story_of_today.php' WHERE blockfile = 'blocks/block-Big_Story_of_Today.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/News/blocks/categories.php' WHERE blockfile = 'blocks/block-Categories.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/News/blocks/last_5_articles.php' WHERE blockfile = 'blocks/block-Last_5_Articles.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/News/blocks/old_articles.php' WHERE blockfile = 'blocks/block-Old_Articles.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/News/blocks/random_headlines.php' WHERE blockfile = 'blocks/block-Random_Headlines.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Our_Sponsors/blocks/advertising.php' WHERE blockfile = 'blocks/block-Advertising.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Search/blocks/search.php' WHERE blockfile = 'blocks/block-Search.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Statistics/blocks/total_hits.php' WHERE blockfile = 'blocks/block-Total_Hits.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Surveys/blocks/survey.php' WHERE blockfile = 'blocks/block-Survey.php'");
		}

		if (version_compare($prev_version, '10.0.14.0', '<')) {
			if (isset($db->TBL->cpg_installs)) {
				$l = strlen($db->TBL->prefix)+1;
				$db->query("UPDATE {$db->TBL->cpg_installs} SET prefix = SUBSTRING(prefix, {$l})");
			}
		}

		if (version_compare($prev_version, '10.0.16.0', '<')) {
			$db->query("UPDATE {$db->TBL->blocks} SET bkey = 'file', blockfile = 'modules/Your_Account/blocks/userbox.php' WHERE bkey = 'userbox'");
		}

		if (version_compare($prev_version, '10.0.18.0', '<')) {
			try {
				$db->query("ALTER TABLE {$db->TBL->users} DROP COLUMN user_style");
			} catch (\Exception $e){}
			$db->query("UPDATE {$db->TBL->users} SET user_new_privmsg = 0, user_unread_privmsg = 0 WHERE user_id < 2");
		}

		if (version_compare($prev_version, '10.0.20.0', '<')) {
			try {
				// Drop if it still exists
				$db->query("ALTER TABLE {$db->TBL->sessions} DROP COLUMN sess_reset");
			} catch (\Exception $e){}
		}

		if (version_compare($prev_version, '10.0.21.0', '<')) {
			try {
				$qr = $db->query("SELECT * FROM {$db->TBL->users_temp}");
				while ($row = $qr->fetch_assoc()) {
					$data = $row;
					unset($data['user_id'], $row['username'], $row['user_email'], $row['user_password'], $row['user_regdate'], $row['check_num'], $row['time']);
					try {
						$db->TBL->users_request->insert(array(
							'request_time'  => $row['time'],
							'request_key'   => $row['user_id'] . '-' . $row['check_num'],
							'user_nickname' => $row['username'],
							'user_password' => $row['user_password'],
							'user_email'    => $row['user_email'],
							'user_details'  => \Poodle::dataToJSON($data),
						));
					} catch (\Exception $e){}
				}
			} catch (\Exception $e){}
		}

		if (version_compare($prev_version, '10.0.22.0', '<')) {
			$db->query("UPDATE {$db->TBL->config_custom} SET cfg_value = REPLACE(REPLACE(cfg_value,'phpmailer_',''),'poodle_','') WHERE cfg_name='email' AND cfg_field='backend'");
		}

		if (version_compare($prev_version, '10.0.24.0', '<')) {
			$db->query("UPDATE {$db->TBL->admins} SET pwd = 'Dragonfly_Admin_v9pass:'||".($sha2?"SHA2(SUBSTRING(pwd FROM 5), '256')":"SUBSTRING(pwd FROM 5)")." WHERE pwd LIKE 'md5:%'");
			$db->query("UPDATE {$db->TBL->auth_identities} SET auth_password = 'Dragonfly_Identity_v9pass:'||".($sha2?"SHA2(SUBSTRING(auth_password FROM 5), '256')":"SUBSTRING(auth_password FROM 5)")." WHERE auth_password LIKE 'md5:%'");
		}

		if (version_compare($prev_version, '10.0.30.0', '<')) {
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Your_Account/blocks/User_Info.php' WHERE blockfile = 'blocks/block-User_Info.php'");
			$db->query("UPDATE {$db->TBL->blocks} SET blockfile = 'modules/Your_Account/blocks/User_Info_small.php' WHERE blockfile = 'blocks/block-User_Info_small.php'");
		}

		/*
		UPDATE cms_comments SET comment = REPLACE(comment,'&quot;','"')
		UPDATE cms_comments SET comment = REPLACE(comment,'&#039;',"'")

		UPDATE cms_config_custom SET cfg_value = 'Dragonfly\\Identity\\Cookie' WHERE cfg_name = 'auth_cookie' AND cfg_field = 'class'
		*/
		return true;
	}

	public function pre_uninstall(){return false;}
	public function post_uninstall(){return false;}

	/**
	 * Must be object using the \Poodle\Events trait
	 */
	protected $listener;
	public function setEventsListener($listener)
	{
		if (method_exists($listener, 'dispatchEvent')) {
			$this->listener = $listener;
		}
	}

	public function run($visual = false)
	{
		global $instlang;
		if (!version_compare($this->prev_version, \Dragonfly::DB_VERSION, '<')) {
			return true;
		}

		$CFG = \Dragonfly::getKernel()->CFG;
		$auto = !empty($CFG->global->db_auto_upgrade);
		if ($auto) {
			$CFG->set('global', 'db_auto_upgrade', false);
			$CFG->onDestroy();
		}

		$log = null;
		try {
			$log_file = dirname(DF_LOG_FILE)."/upgrade-{$this->prev_version}-".\Dragonfly::DB_VERSION;
//			ini_set('error_log', "{$log_file}.errors");
			$log = fopen("{$log_file}.log", 'x');
			if (!$log) {
				throw new \Exception("Upgrade already running?\n\t{$log_file}.log");
			}
			$db = $this->db;

			$server = $db->get_details();
			if ('MySQL' == $db->engine && $server['unicode']) {
				$db->setSchemaCharset();
			}

			# build the database
			set_time_limit(0);
			ignore_user_abort();
			\Poodle\PHP\INI::set('memory_limit', -1);

			if ($visual) {
				echo 'Pre-upgrade steps:<br />';
				echo '<h3>'.$instlang['s3_sync_data'].':</h3>';
			}
			if ($this->listener) {
				$this->listener->dispatchEvent(new \Poodle\PackageManager\InstallProgressEvent("dragonflycms-db-pre", "Before Synchronize"));
			}
			$this->pre_upgrade($this->prev_version);

			$XML = $db->XML->getImporter();
			if ($visual) {
				$XML->addEventListener('afterquery', function(){echo ' .';flush();});

				echo '<h3>'.$instlang['s3_sync_schema'].': </h3>';
			}
			$table_files = array('core', 'auth');
			foreach ($table_files as $file) {
				if ($visual) {
					echo $file;
					flush();
				}
				if ($this->listener) {
					$this->listener->dispatchEvent(new \Poodle\PackageManager\InstallProgressEvent("dragonflycms-db-schema-{$file}", "Synchronize Schema {$file}"));
				}
				if (!$XML->syncSchemaFromFile(__DIR__ . "/db/schemas/{$file}.xml")) {
					throw new \Exception("XML errors in schema {$file}: ".print_r($XML->errors,1));
				}
				if ($visual) {
					echo '<br />';
				}
			}

			if ($visual) {
				echo '<h3>'.$instlang['s3_sync_data'].':</h3>';
			}
			$data_files = array('core', 'auth');
			foreach ($data_files as $file) {
				if ($visual) {
					echo $file;
					flush();
				}
				if ($this->listener) {
					$this->listener->dispatchEvent(new \Poodle\PackageManager\InstallProgressEvent("dragonflycms-db-data-{$file}", "Synchronize Data {$file}"));
				}
				if (!$XML->syncSchemaFromFile(__DIR__ . "/db/data/{$file}.xml")) {
					throw new \Exception("XML errors in data {$file}: ".print_r($XML->errors,1));
				}
				if ($visual) {
					echo '<br />';
				}
			}

			if ($visual) {
				echo '<h3>'.$instlang['s3_sync_done'].'</h3>';
			}

			try {
				$db->begin();
				if ($visual) {
					echo '<h3>'.$instlang['s3_exec_queries'].':</h3>';
				}
				if ($this->listener) {
					$this->listener->dispatchEvent(new \Poodle\PackageManager\InstallProgressEvent("dragonflycms-db-post", "After Synchronize"));
				}
				$this->post_upgrade($this->prev_version);
				if (!$this->test) {
					$this->add_query('UPDATE', 'config_custom', "cfg_value='".\Dragonfly::DB_VERSION."' WHERE cfg_name='global' AND cfg_field='db_version'");
				}
				$this->exec();
			} catch (\Exception $e) {
				$db->rollback();
				throw $e;
			}

			if ($visual) {
				if ($modules = array_keys(\Dragonfly\Modules::ls('install/cpg_inst.php', false))) {
					$this->sync_modules($modules);
				}
			}
			\Dragonfly::getKernel()->CACHE->clear();
			if ($auto) {
				$CFG->set('global', 'db_auto_upgrade', true);
			}
		}
		catch (\Throwable $e) {
			error_log($e->getMessage());
			if ($visual) {
				echo nl2br($e->getMessage()).'<br /><br />'.$instlang['s1_fatalerror'];
			}
			if ($this->listener) {
				$this->listener->dispatchEvent(new \Poodle\PackageManager\InstallErrorEvent($e->getMessage()));
			}
			return false;
		}
		catch (\Exception $error) {
			error_log($e->getMessage());
			if ($visual) {
				echo nl2br($e->getMessage()).'<br /><br />'.$instlang['s1_fatalerror'];
			}
			if ($this->listener) {
				$this->listener->dispatchEvent(new \Poodle\PackageManager\InstallErrorEvent($e->getMessage()));
			}
			return false;
		}
		finally {
			if ($log) {
				fclose($log);
			}
		}
		return true;
	}

	public function sync_modules(array $modules)
	{
		global $instlang;
		$db = $this->db;
		define('ADMIN_MOD_INSTALL', 1);
		echo '<h3>'.$instlang['s3_updt_modules'].':</h3>';

		$installed = array();
		$qr = $db->query("SELECT title, version FROM {$db->TBL->modules}");
		while ($r = $qr->fetch_row()) {
			$installed[$r[0]] = $r[1];
		}

		foreach ($modules as $module) {
			echo $module.': ';
			try {
				if (isset($installed[$module]) && $installer = new \Dragonfly\ModManager\Setup($module)) {
					if ($installer->update_module($installed[$module])) {
						echo $instlang['s3_updt_done'].'<br />';
						continue;
					}
					echo $instlang['s3_inst_fail'].': '.$installer->error.'<br />';
				}
				echo $instlang['s3_inst_fail'].': no installer<br />';
			} catch (\Exception $e) {
				echo $instlang['s3_inst_fail'].': '.$e->getMessage().'<br />';
			}
		}
	}

}
