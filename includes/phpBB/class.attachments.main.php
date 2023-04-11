<?php
/***************************************************************************
 *							  class.attachments.main.php
 *							  -------------------
 *	 begin				  : Monday, Jul 15, 2002
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

class attach_posting
{
	const
		ADMIN_MAX_ATTACHMENTS = 50;

	private
		$attachments = array();

	//
	// Handle all modes... (intern)
	//
	public function handle_attachments($mode, \Dragonfly\Forums\Post $post)
	{
		global $attach_config, $refresh, $submit, $preview, $error, $template, $userinfo;
		$db = \Dragonfly::getKernel()->SQL;
		$lang = $template->L10N;
		$max_attachments = (\Dragonfly\Identity::LEVEL_ADMIN == $userinfo->level) ? static::ADMIN_MAX_ATTACHMENTS : (int)$attach_config['max_attachments'];
		//
		// nothing, if the user is not authorized or attachment mod disabled
		//
		if ($attach_config['disable_mod'] || !$post->forum->userAuth('attachments')) {
			return false;
		}
		//
		// Init Vars
		//
		if (!$refresh) {
			$refresh = isset($_POST['add_attachment'])
			 || isset($_POST['del_attachment'])
			 || isset($_POST['edit_comment'])
			 || isset($_POST['del_thumbnail'])
			 || isset($_POST['update_attachment'])
			 || isset($_POST['add_attachment_box']);
		}
		$post_id = (int)$GLOBALS['post_id'];
		//
		// Get Attachments
		//
		if (!$this->attachments) {
			if (isset($_SESSION['bb_attachments'][$post_id])) {
				$this->attachments = $_SESSION['bb_attachments'][$post_id];
			} else
			if (!$submit && 'editpost' == $mode && ($post->forum->userAuth('edit') || $post->forum->userAuth('mod'))) {
				if (!$refresh && !$preview && !$error && !isset($_POST['del_poll_option'])) {
					$this->attachments = \Dragonfly\Forums\Attachments::getFromPosts($post_id);
				}
			}
		}

		// Store post (clicked [Submit] button)
		if ($submit && ('newtopic' == $mode || 'reply' == $mode || 'editpost' == $mode)) {
			$this->add_attachment($post);
		}

		if ($preview || $refresh || $error) {
			//
			// Perform actions on temporary attachments
			//
			if (isset($_POST['edit_comment'])) {
				foreach ($this->attachments as $i => &$attachment) {
					$attachment['comment'] = $_POST['comment_list'][$i];
				}
			} else
			if (isset($_POST['update_attachment'])) {
				// replace upload?
				$attachment = $this->upload_attachment($post);
				if ($attachment) {
					$attachment_id = 0;
					$actual_element = 0;
					foreach ($this->attachments as $i => $old_attachment) {
						if (isset($_POST['update_attachment'][$old_attachment['attach_id']])) {
							$attachment_id = (int)$old_attachment['attach_id'];
							$actual_element = $i;
							break;
						}
					}
					// Get current informations to delete the Old Attachment
					$row = $db->uFetchAssoc("SELECT
						a.comment,
						a.thumbnail,
						u.upload_id
					FROM " . ATTACHMENTS_DESC_TABLE . " a
					LEFT JOIN {$db->TBL->users_uploads} u USING (upload_id)
					WHERE attach_id = {$attachment_id}");
					if ($row) {
						if ($row['upload_id']) {
							$upload = new \Dragonfly\Identity\Upload($row['upload_id']);
//							\Dragonfly\Forums\Attachments::unlink($upload->file);
							if ($row['thumbnail']) {
								\Dragonfly\Forums\Attachments::unlinkThumbnail($upload->file);
							}
							$upload->delete();
						}
						$attachment['attach_id'] = $attachment_id;
						$attachment['comment']   = $attachment['comment'] ?: $row['comment'];
						// Update Entry
						$db->query("UPDATE " . ATTACHMENTS_DESC_TABLE . " SET
							comment = {$db->quote($attachment['comment'])},
							extension = '{$attachment['extension']}',
							mimetype = '{$attachment['mimetype']}',
							thumbnail = {$attachment['thumbnail']},
							upload_id = {$attachment['upload_id']}
						WHERE attach_id = {$attachment_id}");
						$this->attachments[$actual_element] = $attachment;
					} else {
						$error = true;
						\Poodle\Notify::error($lang['Error_missing_old_entry']);
					}
				}
			} else
			if (isset($_POST['del_attachment']) || isset($_POST['del_thumbnail'])) {
				// clean values
				$actual_list = $this->attachments;
				$this->attachments = array();
				// restore values :)
				if ($actual_list) {
					foreach ($actual_list as $i => $attachment) {
						$del_thumb = false;
						if (isset($_POST['del_attachment'][$attachment['file']])) {
							// delete selected attachment
							if ($attachment['attach_id']) {
								\Dragonfly\Forums\Attachments::deleteFromPosts($post_id, $attachment['attach_id']);
							} else {
								if ($attachment['upload_id']) {
									$upload = new \Dragonfly\Identity\Upload($attachment['upload_id']);
									$upload->delete();
								}
								if ($attachment['thumbnail']) {
									\Dragonfly\Forums\Attachments::unlinkThumbnail($attachment['file']);
								}
							}
							continue;
						}
						if (isset($_POST['del_thumbnail'][$attachment['file']])) {
							// delete selected thumbnail
							$attachment['thumbnail'] = 0;
							\Dragonfly\Forums\Attachments::unlinkThumbnail($attachment['file']);
							if ($attachment['attach_id']) {
								$db->query("UPDATE " . ATTACHMENTS_DESC_TABLE . " SET thumbnail = 0
								WHERE attach_id = " . $attachment['attach_id']);
							}
						}
						$this->attachments[] = $attachment;
					}
				}
			} else if (isset($_POST['add_attachment']) || $preview) {
				$this->add_attachment($post);
			}
		}

		$_SESSION['bb_attachments'][$post_id] = $this->attachments;
		return true;
	}

	protected function add_attachment(\Dragonfly\Forums\Post $post)
	{
		global $attach_config, $userinfo;
		$max_attachments = (\Dragonfly\Identity::LEVEL_ADMIN == $userinfo->level) ? static::ADMIN_MAX_ATTACHMENTS : $attach_config['max_attachments'];
		if (count($this->attachments) < $max_attachments) {
			$attachment = $this->upload_attachment($post);
			if ($attachment) {
				array_unshift($this->attachments, $attachment);
				return true;
			}
		} else {
			global $error, $template;
			$error = TRUE;
			\Poodle\Notify::error(sprintf($template->L10N['Too_many_attachments'], (int)$max_attachments));
		}
	}

	//
	// Insert an Attachment into a Post (this is the second function called from posting.php)
	//
	public function save_attachments($mode, \Dragonfly\Forums\Post $post)
	{
		if ($post->id && $post->forum->userAuth('attachments') && ('newtopic' == $mode || 'reply' == $mode || 'editpost' == $mode)) {
			$db = \Dragonfly::getKernel()->SQL;
			foreach ($this->attachments as $attachment) {
				if ($attachment['attach_id']) {
					//
					// update entry in db if attachment already stored in db and filespace
					//
					$db->query("UPDATE " . ATTACHMENTS_DESC_TABLE . "
					SET comment = {$db->quote($attachment['comment'])}
					WHERE attach_id = " . $attachment['attach_id']);
				} else {
					//
					// insert attachment into db
					//
					$db->query("INSERT INTO " . ATTACHMENTS_DESC_TABLE . " (
						comment, extension, mimetype, thumbnail, upload_id
					) VALUES (
						{$db->quote($attachment['comment'])},
						{$db->quote($attachment['extension'])},
						{$db->quote($attachment['mimetype'])},
						{$attachment['thumbnail']},
						{$attachment['upload_id']})");
					$attach_id = $db->insert_id('attach_id');
					$db->query("INSERT INTO " . ATTACHMENTS_TABLE . " (attach_id, post_id) VALUES ({$attach_id}, {$post->id})");
				}
			}

			if (count($this->attachments) && !isset($_POST['update_attachment'])) {
				$db->exec("UPDATE " . POSTS_TABLE . " SET post_attachment = 1 WHERE post_id = {$post->id}");
				$db->exec("UPDATE " . POSTS_ARCHIVE_TABLE . " SET post_attachment = 1 WHERE post_id = {$post->id}");
				$db->exec("UPDATE " . TOPICS_TABLE . " SET topic_attachment = 1 WHERE topic_id = {$post->topic_id}");
			}
		}
		// no longer needed
		unset($_SESSION['bb_attachments']);
	}

	//
	// Attachment Mod entry switch/output (intern)
	//
	public function display_attachment_bodies(\Dragonfly\Forums\Post $post)
	{
		global $attach_config, $template, $userinfo;
		$db = \Dragonfly::getKernel()->SQL;
		$lang = $template->L10N;
		// Choose what to display
		$s_hidden = array();
		$add_attachment_body = true;
		$posted_attachments_body = true;
		if ($attach_config['show_apcp']) {
			$add_attachment_body = !empty($_POST['add_attachment_body']);
			$posted_attachments_body = !empty($_POST['posted_attachments_body']);
			if (isset($_POST['add_attachment_box'])) {
				$add_attachment_body = !$add_attachment_body;
			}
			if (isset($_POST['posted_attachments_box'])) {
				$posted_attachments_body = !$posted_attachments_body;
			}
			$s_hidden[] = array('name'=>'add_attachment_body','value'=>intval($add_attachment_body));
			$s_hidden[] = array('name'=>'posted_attachments_body','value'=>intval($posted_attachments_body));
		}
		$template->assign_vars(array(
			'show_attachments_cp' => $attach_config['show_apcp'],
			'ADD_ATTACHMENT_BODY' => $add_attachment_body,
			'attach_rules_uri' => URL::index("&file=attach_rules&f={$post->forum_id}"),
			'hidden_attachment_fields' => $s_hidden,
			'MAX_FILESIZE' => $template->L10N->filesizeToHuman(\Poodle\Input\FILES::max_filesize()),
		));
		$template->attach_row = array();
		if ($posted_attachments_body && $this->attachments) {
			foreach ($this->attachments as $attachment) {
				$attachment['DEL_THUMBNAIL'] = $attachment['thumbnail'] && ($post->forum->userAuth('mod') || \Dragonfly\Identity::LEVEL_ADMIN == $userinfo->level);
				$attachment['U_VIEW_ATTACHMENT'] = ($attachment['attach_id'] < 1) ? $attachment['file'] : URL::index('&file=download&id=' . $attachment['attach_id']);
				$template->attach_row[] = $attachment;
			}
		}
	}

	//
	// Upload an Attachment to Filespace (intern)
	//
	private function upload_attachment(\Dragonfly\Forums\Post $post)
	{
		global $error, $template, $attach_config, $userinfo;
		if ($error) {
			return;
		}

		$lang = $template->L10N;
		$file = $_FILES ? $_FILES->getAsFileObject('fileupload') : null;
		if (!$file || UPLOAD_ERR_NO_FILE == $file->errno) {
			return;
		}
		if ($file->errno) {
			$error = true;
			\Poodle\Notify::error($file->error);
			return;
		}

		$db = \Dragonfly::getKernel()->SQL;
		$filesize  = $file->size;
		$extension = $file->extension;
		$row = $db->uFetchAssoc("SELECT g.allow_group, g.max_filesize, g.cat_id, g.forum_permissions
		FROM {$db->TBL->bbextension_groups} g, {$db->TBL->bbextensions} e
		WHERE (g.group_id = e.group_id) AND (e.extension = '{$extension}')");

		//
		// Check Extension
		//
		if (!$row || !$row['allow_group']) {
			$error = true;
			\Poodle\Notify::error(sprintf($lang['Disallowed_extension'], $extension));
			return;
		}

		//
		// Check Forum Permissions
		//
		$row['forum_permissions'] = trim($row['forum_permissions']);
		if (\Dragonfly\Identity::LEVEL_ADMIN != $userinfo->level && $row['forum_permissions'] && !is_forum_authed($row['forum_permissions'], $post->forum_id)) {
			$error = true;
			\Poodle\Notify::error(sprintf($lang['Disallowed_extension_within_forum'], $extension));
			return;
		}

		//
		// Check Image Size, if it's an image
		//
		if (\Dragonfly\Identity::LEVEL_ADMIN != $userinfo->level && $row['cat_id'] == IMAGE_CAT) {
			list($width, $height) = getimagesize($file->tmp_name);
			$attach_config['img_max_width'] = (int)$attach_config['img_max_width'];
			$attach_config['img_max_height'] = (int)$attach_config['img_max_height'];
			if (($attach_config['img_max_width'] && $width > $attach_config['img_max_width'])
			 || ($attach_config['img_max_height'] && $height > $attach_config['img_max_height'])) {
				$error = true;
				\Poodle\Notify::error(sprintf($lang['Error_imagesize'], $attach_config['img_max_width'], $attach_config['img_max_height']));
				return;
			}
		}

		//
		// check Filesize
		//
		$allowed_filesize = intval($row['max_filesize'] ?: \Poodle\Input\FILES::max_filesize());
		if ($allowed_filesize && $filesize > $allowed_filesize && \Dragonfly\Identity::LEVEL_ADMIN != $userinfo->level) {
			$error = true;
			\Poodle\Notify::error(sprintf($lang['Attachment_too_big'], $template->L10N->filesizeToHuman($allowed_filesize), ''));
			return;
		}

		//
		// Check our user quota
		//
		$upload_filesize_limit = $userinfo->getUploadQuota();
		if ($userinfo->getUploadUsage() + $filesize > $upload_filesize_limit) {
			$error = true;
			\Poodle\Notify::error(sprintf($lang['User_upload_quota_reached'], $template->L10N->filesizeToHuman($upload_filesize_limit), ''));
			return;
		}

		//
		// Prepare Values
		//
		$filetime = time();
		$attach_filename = $file->filename;
		// To re-add cryptic filenames, change this to false
		if (true) {
			$attach_filename = rawurlencode($attach_filename);
			$attach_filename = preg_replace('/%(\\w{2})/', '_', $attach_filename);
		} else {
			$u_id = ($userinfo->isMember() ? $userinfo->id : 0);
			$attach_filename = $u_id . '_' . $filetime;
		}

		//
		// Move the temporary attachment to the right location
		//
		if ($file->moveTo("{$attach_config['upload_dir']}/{$attach_filename}", $extension)) {
			$attach_filename = "{$attach_config['upload_dir']}/{$file->name}";
		} else {
			$error = true;
			\Poodle\Notify::error(sprintf($lang['General_upload_error'], "./{$attach_config['upload_dir']}/{$file->name}"));
			return;
		}

		$thumbnail = false;
		if (!$error && IMAGE_CAT == $row['cat_id'] && $attach_config['img_create_thumbnail']) {
			$thumbnail = static::create_thumbnail($file->tmp_name, $attach_filename);
		}

		if (!$error) {
			$upload = new \Dragonfly\Identity\Upload();
			$upload->size = $filesize;
			$upload->file = $attach_filename;
			$upload->name = $file->org_name;
			$upload->save();
			return array(
				'file'      => $attach_filename,
				'name'      => $file->org_name,
				'extension' => $extension,
				'mimetype'	=> $file->type,
				'filesize'	=> $filesize,
				'filetime'	=> $filetime,
				'attach_id' => 0,
				'thumbnail' => $thumbnail ? 1 : 0,
				'comment'	=> $_POST->txt('filecomment'),
				'upload_id' => $upload->id
			);
		}
		return false;
	}

	protected static function create_thumbnail($source, $new_file)
	{
		global $attach_config;
		$source = amod_realpath($source);
		$new_file = preg_replace('#(^|/)([^/]+)$#D', '$1'.THUMB_DIR.'/t_$2', $new_file);
		$img_filesize = is_file($source) ? filesize($source) : false;
		if (!$img_filesize || $img_filesize <= $attach_config['img_min_thumb_filesize']) {
			return false;
		}

		try {
			$img = \Poodle\Image::open($source);
			if (!$img || !$img->getImageWidth() || !$img->getImageHeight()) {
				return false;
			}
			$img->thumbnailImage(400, 200, true);
			$img->writeImage($new_file);
			$new_file = $img->getImageFilename();
			if (!is_file($new_file)) { return false; }
			chmod($new_file, 0666);
			return true;
		} catch (\Exception $e) {}

		return false;
	}

}
