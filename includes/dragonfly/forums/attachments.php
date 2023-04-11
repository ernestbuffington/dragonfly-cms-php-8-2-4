<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Forums;

abstract class Attachments
{

	public static function getConfig()
	{
		static $attach_config;
		if (!is_array($attach_config)) {
			$K = \Dragonfly::getKernel();
			$attach_config = $K->CACHE->get('dragonfly/forums/attach_config');
			if (!$attach_config) {
				$attach_config = array();
				$result = $K->SQL->query("SELECT config_name, config_value FROM {$K->SQL->TBL->bbattachments_config}");
				while ($row = $result->fetch_row()) {
					$attach_config[$row[0]] = $row[1];
				}
				$K->CACHE->set('dragonfly/forums/attach_config', $attach_config);
			}
		}
		return $attach_config;
	}

	public static function deleteConfig()
	{
		\Dragonfly::getKernel()->CACHE->delete('dragonfly/forums/attach_config');
	}

	public static function delete($attach_id_array)
	{
		$post_id_array = array();
		$attach_id_array = array_map('intval', is_array($attach_id_array) ? $attach_id_array : array($attach_id_array));

		$result = \Dragonfly::getKernel()->SQL->query("SELECT post_id FROM ".ATTACHMENTS_TABLE." WHERE attach_id IN (".implode(', ', $attach_id_array).") GROUP BY post_id");
		if ($result->num_rows) {
			while ($r = $result->fetch_row()) {
				$post_id_array[] = $r[0];
			}
			static::deleteFromPosts($post_id_array, $attach_id_array);
		}
	}

	//
	// Delete Attachment(s) from post(s) (intern)
	//
	public static function deleteFromPosts($post_id_array, $attach_id_array = 0)
	{
		$db = \Dragonfly::getKernel()->SQL;

		//
		// Generate Array, if it's not an array
		//
		$post_id_array = array_map('intval', is_array($post_id_array) ? $post_id_array : array($post_id_array));
		if (!$post_id_array) {
			return;
		}
		$post_id_array = implode(', ', $post_id_array);

		//
		// First of all, determine the post id and attach_id
		//
		if (!$attach_id_array) {
			// Get the attach_ids to fill the array
			$attach_id_array = array();
			$result = $db->query("SELECT attach_id FROM ".ATTACHMENTS_TABLE." WHERE post_id IN ({$post_id_array}) GROUP BY attach_id");
			if ($result->num_rows) {
				while ($r = $result->fetch_row()) {
					$attach_id_array[] = $r[0];
				}
			}
		}
		$attach_id_array = array_map('intval', is_array($attach_id_array) ? $attach_id_array : array($attach_id_array));
		if (!$attach_id_array) {
			return;
		}
		$attach_id_array = implode(', ', $attach_id_array);

		$db->query("DELETE FROM ".ATTACHMENTS_TABLE."
			WHERE attach_id IN ({$attach_id_array})
			  AND post_id IN ({$post_id_array})");

		$attachments = $db->uFetchAll('SELECT
			a.attach_id as a_attach_id,
			d.attach_id,
			d.upload_id,
			d.thumbnail
		FROM '.ATTACHMENTS_DESC_TABLE.' d
		LEFT JOIN '.ATTACHMENTS_TABLE.' a USING (attach_id)
		WHERE d.attach_id IN ('.$attach_id_array.')');
		foreach ($attachments as $attachment) {
			if (!$attachment['a_attach_id']) {
				$upload = new \Dragonfly\Identity\Upload($attachment['upload_id']);
				if ($attachment['thumbnail']) {
					\Dragonfly\Forums\Attachments::unlinkThumbnail($upload->file);
				}
				$upload->delete();
				$db->query('DELETE FROM '.ATTACHMENTS_DESC_TABLE.' WHERE attach_id = '.$attachment['attach_id']);
			}
		}

		//
		// Now Sync the Topic/PM
		//
		$result = $db->query("SELECT topic_id FROM ".POSTS_TABLE." WHERE post_id IN ({$post_id_array}) GROUP BY topic_id
		UNION SELECT topic_id FROM ".POSTS_ARCHIVE_TABLE." WHERE post_id IN ({$post_id_array}) GROUP BY topic_id");
		while ($row = $result->fetch_row()) {
			\Dragonfly\Forums\Attachments::syncTopic($row[0]);
		}
	}

	public static function getFromPosts($post_id_array)
	{
		global $attach_config;
		$db = \Dragonfly::getKernel()->SQL;

		$post_id_array = array_map('intval', is_array($post_id_array) ? $post_id_array : array($post_id_array));
		if (!$post_id_array) {
			return array();
		}
		$post_id_array = implode(', ', $post_id_array);

		$display_order = $attach_config['display_order'] ? 'ASC' : 'DESC';
		return $db->uFetchAll("SELECT
			a.post_id,
			d.attach_id,
			d.download_count,
			d.comment,
			d.extension,
			d.mimetype,
			d.thumbnail,
			u.upload_file as file,
			u.upload_name as name,
			u.upload_size as filesize,
			u.upload_time as filetime,
			u.upload_id
		FROM ".ATTACHMENTS_TABLE." a
		LEFT JOIN ".ATTACHMENTS_DESC_TABLE." d USING (attach_id)
		LEFT JOIN {$db->TBL->users_uploads} u USING (upload_id)
		WHERE a.post_id IN ({$post_id_array})
		ORDER BY u.upload_time {$display_order}");
	}

	public static function unlink($filename)
	{
		return (!is_file($filename)) || unlink($filename) || (chmod($filename, 0666) && unlink($filename));
	}

	public static function unlinkThumbnail($filename)
	{
		$filename = preg_replace('#(^|/)([^/]+)$#D', '$1'.THUMB_DIR.'/t_$2', $filename);
		return static::unlink($filename);
	}

	public static function getTopicImage($has_attachment, \Dragonfly\Forums\Forum $forum)
	{
		global $attach_config;
		$is_auth = $forum->getUserPermissions();
		if (!$has_attachment || !$is_auth['auth_download'] || !$is_auth['auth_view'] || $attach_config['disable_mod'] || !$attach_config['topic_icon']) {
			return '';
		}
		return DF_STATIC_DOMAIN . $attach_config['topic_icon'];
	}

	public static function syncTopic($topic_id)
	{
		$db = \Dragonfly::getKernel()->SQL;
		list($archived) = $db->uFetchRow("SELECT topic_archive_flag FROM ".TOPICS_TABLE." WHERE topic_id={$topic_id}");
		$posts_table = empty($archived) ? POSTS_TABLE : POSTS_ARCHIVE_TABLE;

		$result = $db->query("SELECT
			p.post_id,
			p.post_attachment,
			COUNT(attach_id)
		FROM {$posts_table} p
		LEFT JOIN ".ATTACHMENTS_TABLE." a USING (post_id)
		WHERE topic_id = {$topic_id}
		GROUP BY 1, 2");

		$topic_attachments = false;
		$has_attachments = array();
		$no_attachments = array();
		while ($row = $result->fetch_row()) {
			$topic_attachments |= $row[2];
			if ($row[1] && !$row[2]) {
				// Post is set to have attachments, but it doesn't
				$no_attachments[] = $row[0];
			} else if ($row[2] && !$row[1]) {
				// Post is set to have no attachments, but it does
				$has_attachments[] = $row[0];
			}
		}
		$result->free();
		$db->query('UPDATE '.TOPICS_TABLE.' SET topic_attachment = '.($topic_attachments ? 1 : 0).' WHERE topic_id = '.$topic_id);
		if ($has_attachments) {
			$db->query("UPDATE {$posts_table} SET post_attachment = 1 WHERE post_id IN (".implode(',',$has_attachments).")");
		}
		if ($no_attachments) {
			$db->query("UPDATE {$posts_table} SET post_attachment = 0 WHERE post_id IN (".implode(',',$no_attachments).")");
		}
	}

}
