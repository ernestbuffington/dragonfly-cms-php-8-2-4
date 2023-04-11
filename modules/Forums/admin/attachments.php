<?php
/***************************************************************************
 *								  admin_attachments.php
 *								  -------------------
 *	 begin				  : Wednesday, Jan 09, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/

if (!defined('ADMIN_PAGES')) { exit; }

require_once CORE_PATH.'phpBB/functions_attach.php';

# Init Vars
$mode = $_POST['mode'] ?: $_GET['mode'];
$submit = ('POST' === $_SERVER['REQUEST_METHOD']);
$error_messages = array();

# Management
if ('manage' == $mode) {

	# Re-evaluate the Attachment Configuration
	if ($submit) {
/*
		if ($_POST['upload_dir'] != $attach_config['upload_dir']) {
			$l = strlen($attach_config['upload_dir']);
			$db->query("UPDATE {$db->TBL->users_uploads}
			SET upload_file = CONCAT('{$_POST['upload_dir']}', SUBSTRING(upload_file, $l))
			WHERE upload_file LIKE '{$attach_config['upload_dir']}/%'");
		}
*/
		$result = $db->query('SELECT config_name FROM ' . $db->TBL->bbattachments_config);
		while ($row = $result->fetch_row()) {
			$config_name = $row[0];
			if (ctype_digit($config_name)) {
				$db->exec("DELETE FROM {$db->TBL->bbattachments_config} WHERE config_name = '{$config_name}'");
			} else if (isset($_POST[$config_name])) {
				$attach_config[$config_name] = $_POST[$config_name];
				$db->query("UPDATE {$db->TBL->bbattachments_config}
				SET config_value = {$db->quote($attach_config[$config_name])}
				WHERE config_name = {$db->quote($config_name)}");
			}
		}
		Dragonfly\Forums\Attachments::deleteConfig();
	}

	# Check Settings
	if (isset($_POST['test_settings'])) {
		# Does the target directory exist, is it a directory and writeable. (only test if ftp upload is disabled)
		$upload_dir = $attach_config['upload_dir'];
		$test_file = '0_000000.000';
		if (!file_exists(amod_realpath($upload_dir))) {
			$error_messages[] = sprintf($lang['Directory_does_not_exist'], $upload_dir);
		} else if (!is_dir($upload_dir)) {
			$error_messages[] = sprintf($lang['Directory_is_not_a_dir'], $upload_dir);
		} else if (!is_writable($upload_dir) || !touch("{$upload_dir}/{$test_file}")) {
			$error_messages[] = sprintf($lang['Directory_not_writeable'], $upload_dir);
		} else {
			unlink("{$upload_dir}/{$test_file}");
		}
		if ($attach_config['img_create_thumbnail']) {
			$upload_dir .= '/' . THUMB_DIR;
			if (!file_exists(amod_realpath($upload_dir)) && !mkdir($upload_dir, 0777)) {
				$error_messages[] = sprintf($lang['Directory_does_not_exist'], $upload_dir);
			} else if (!is_dir($upload_dir)) {
				$error_messages[] = sprintf($lang['Directory_is_not_a_dir'], $upload_dir);
			} else if (!is_writable($upload_dir) || !touch("{$upload_dir}/{$test_file}")) {
				$error_messages[] = sprintf($lang['Directory_not_writeable'], $upload_dir);
			} else {
				unlink("{$upload_dir}/{$test_file}");
			}
		}
		if (!$error_messages) {
			\Dragonfly::closeRequest($lang['Test_settings_successful'], 200, $_SERVER['REQUEST_URI']);
		}
	}

	if ($submit && !$error_messages) {
		\Dragonfly::closeRequest($lang['Attach_config_updated'], 200, $_SERVER['REQUEST_URI']);
	}

	$template->set_handle('body', 'Forums/admin/attach_manage');

	$assigned_group_images = array();
	$qr = $db->query("SELECT group_name FROM {$db->TBL->bbextension_groups} WHERE cat_id = ".IMAGE_CAT." ORDER BY group_name");
	while ($row = $qr->fetch_row()) {
		$assigned_group_images[] = $row[0];
	}
	$template->assigned_group_images = implode(', ', $assigned_group_images);

	$template->attach_cfg = $attach_config;
}

else if ('shadow' == $mode) {
	if ($submit) {
		# Delete Attachments from file system...
		if (!empty($_POST['attach_file_list'])) {
			foreach ($_POST['attach_file_list'] as $file) {
				if (false === strpos($file,'/') && false === strpos($file,'\\')) {
					\Dragonfly\Forums\Attachments::unlink($file);
				}
			}
		}

		# Delete Attachments from table...
		if (!empty($_POST['attach_id_list'])) {
			$attach_id_list = array_map('intval', $_POST['attach_id_list']);
			if ($attach_id_list) {
				$attach_id_list = implode(',', $attach_id_list);
				// DELETE WHERE IN SELECT Query slow?
				$db->query("DELETE FROM {$db->TBL->users_uploads} WHERE upload_id IN (SELECT upload_id FROM ".ATTACHMENTS_DESC_TABLE." WHERE attach_id IN ({$attach_id_list}))");
				$db->query("DELETE FROM " . ATTACHMENTS_DESC_TABLE . " WHERE attach_id IN ({$attach_id_list})");
				$db->query("DELETE FROM " . ATTACHMENTS_TABLE . " WHERE attach_id IN ({$attach_id_list})");
			}
		}

		\Dragonfly::closeRequest($lang['Attach_config_updated'], 200, $_SERVER['REQUEST_URI']);
	}

	# Shadow Attachments
	$template->set_handle('body', 'Forums/admin/attach_shadow');

	$table_filenames = array();

	$template->file_shadow_row = array();
	$template->table_shadow_row = array();

	# Collect all attachments

	$result = $db->query("SELECT
		d.attach_id,
		u.upload_file
	FROM " . ATTACHMENTS_DESC_TABLE . " d
	LEFT JOIN {$db->TBL->users_uploads} u USING (upload_id)
	ORDER BY 2");
	while ($row = $result->fetch_row()) {
		$table_filenames[$row[0]] = $row[1];
	}

	set_time_limit(120);
	$result = $db->query("SELECT
		a.attach_id,
		COUNT(p.post_id),
		COUNT(pa.post_id)
	FROM " . ATTACHMENTS_TABLE . " a
	LEFT JOIN ".POSTS_TABLE." p ON (p.post_id = a.post_id)
	LEFT JOIN ".POSTS_ARCHIVE_TABLE." pa ON (pa.post_id = a.post_id)
	GROUP BY attach_id");
	while ($row = $result->fetch_row()) {
		if (!$row[1] && !$row[2]) {
			# Post doesn't exist
			$template->table_shadow_row[] = array(
				'ATTACH_ID' => $row[0],
				'ATTACH_FILENAME' => $table_filenames[$row[0]]
			);
		} else if (!isset($table_filenames[$row[0]])) {
			# Attachment is removed from ATTACHMENTS_DESC_TABLE
			$template->table_shadow_row[] = array(
				'ATTACH_ID' => $row[0],
				'ATTACH_FILENAME' => $lang['Empty_file_entry']
			);
		}
	}

	# collect all attachments on file-system
	if ($dir = opendir($attach_config['upload_dir'])) {
		set_time_limit(120);
		while ($file = readdir($dir)) {
			$filename = $attach_config['upload_dir'] . '/' . $file;
			if ($file != 'index.php' && $file[0] != '.' && is_file($filename)) {
				$i = array_search($filename, $table_filenames);
				if (false === $i) {
					$template->file_shadow_row[] = array(
						'ATTACH_ID'       => $filename,
						'ATTACH_FILENAME' => $file,
						'U_ATTACHMENT'    => $filename
					);
				} else {
					unset($table_filenames[$i]);
				}
			}
		}
		closedir($dir);
	} else {
		message_die(GENERAL_ERROR, 'Is Safe Mode Restriction in effect on '.$attach_config['upload_dir'].'? The Attachment Mod seems to be unable to collect the Attachments within the upload Directory. Try to use FTP Upload to circumvent this error.');
	}

	# Go through the Database and get those Files not stored at the Filespace
	foreach ($table_filenames as $id => $name) {
		$template->table_shadow_row[] = array(
			'ATTACH_ID' => $id,
			'ATTACH_FILENAME' => $name
		);
	}

	unset($table_filenames);
}

else if ('sync' == $mode) {
	\Dragonfly::ob_clean();
	header('Content-Type: text/plain');
	$info = '';

	echo "Sync Topics\n";
	set_time_limit(120);
	$result = $db->query("SELECT topic_id FROM " . TOPICS_TABLE);
	$i = 0;
	while ($row = $result->fetch_row()) {
		echo (++$i % 50 == 0) ? ".\n" : '. ';
		flush();
		\Dragonfly\Forums\Attachments::syncTopic($row[0]);
	}
	$result->free();

	# Reassign Attachments to the Poster ID
	echo "\n\nSync Posts\n";
	set_time_limit(120);
	$result = $db->query("SELECT
		a.attach_id,
		a.post_id,
		COALESCE(pa.poster_id, p.poster_id)
	FROM " . ATTACHMENTS_TABLE . " a
	LEFT JOIN ".POSTS_TABLE." p ON (p.post_id = a.post_id)
	LEFT JOIN ".POSTS_ARCHIVE_TABLE." pa ON (pa.post_id = a.post_id)
	WHERE a.user_id_1 <> COALESCE(pa.poster_id, p.poster_id)");
	$i = 0;
	while ($row = $result->fetch_row()) {
		echo (++$i % 50 == 0) ? ".\n" : '. ';
		flush();
		$db->query("UPDATE " . ATTACHMENTS_TABLE . " SET user_id_1 = {$row[2]}
			WHERE attach_id = {$row[0]} AND post_id = {$row[1]}");
	}

	# Sync Thumbnails
	echo "\n\nSync Thumbnails\n";
	set_time_limit(120);
	$result = $db->query("SELECT
		d.attach_id,
		u.upload_file,
		d.thumbnail
	FROM " . ATTACHMENTS_DESC_TABLE . " d
	LEFT JOIN {$db->TBL->users_uploads} u USING (upload_id)");
	$i = 0;
	while ($row = $result->fetch_row()) {
		echo (++$i % 50 == 0) ?  ".\n" : '. ';
		flush();
		$exists = is_file(amod_realpath(preg_replace('#(^|/)([^/]+)$#D', '$1'.THUMB_DIR.'/t_$2', $row[1])));
		if ($row[2]) {
			# If a thumbnail is no longer there, delete it
			# Get all Posts/PM's with the Thumbnail Flag set
			# Go through all of them and make sure the Thumbnail exist. If it does not exist, unset the Thumbnail Flag
			if (!$exists) {
				$info .= sprintf($lang['Sync_thumbnail_resetted'], $row[1]) . "\n";
				$db->query("UPDATE " . ATTACHMENTS_DESC_TABLE . " SET thumbnail = 0 WHERE attach_id = " . $row[0]);
			}
		} else {
			# Make sure all non-existent thumbnails are deleted
			# Get all Posts/PM's with the Thumbnail Flag NOT set
			# Go through all of them and make sure the Thumbnail does NOT exist. If it does exist, delete it
			if ($exists) {
				$info .= sprintf($lang['Sync_thumbnail_resetted'], $row[1]) . "\n";
				\Dragonfly\Forums\Attachments::unlinkThumbnail($row[1]);
			}
		}
	}
	$result->free();
	flush();
	exit("\n\n".$lang['Attach_sync_finished']."\n\n".$info);
}

$template->ERROR_MESSAGE = $error_messages ? implode('<br/>',$error_messages) : '';
