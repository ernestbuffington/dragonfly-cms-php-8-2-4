<?php
/***************************************************************************
 *							  posting_attachments.php
 *							  -------------------
 *	 begin				  : Monday, Jul 15, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: class.attachments.main.php,v 9.13 2008/01/21 11:48:33 nanocaiordo Exp $
 *
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
if (!defined('IN_PHPBB')) { exit; }

//
// Base Class for Attaching
//
class attach_parent
{

	var $post_attach = FALSE;
	var $attach_filename = '';
	var $filename = '';
	var $type = '';
	var $extension = '';
	var $file_comment = '';
	var $num_attachments = 0; // number of attachments in message
	var $filesize = 0;
	var $filetime = 0;
	var $thumbnail = 0;
	var $page = -1; // On which page we are on ? This should be filled by child classes.

	// Switches
	var $add_attachment_body = 0;
	var $posted_attachments_body = 0;

	//
	// Constructor
	//
	function attach_parent()
	{
		if (!empty($_POST['add_attachment_body'])) {
			$this->add_attachment_body = intval($_POST['add_attachment_body']);
		}
		if (!empty($_POST['posted_attachments_body'])) {
			$this->posted_attachments_body = intval($_POST['posted_attachments_body']);
		}
		$this->file_comment = isset($_POST['filecomment']) ? trim(strip_tags($_POST['filecomment'])) : '';
		if (isset($_FILES['fileupload']['name'])) {
			$this->filename = ($_FILES['fileupload']['name'] != 'none') ? strtolower(trim($_FILES['fileupload']['name'])) : '';
		}
		global $CPG_SESS;
		$this->attachments = isset($CPG_SESS['bb_attachments']) ? $CPG_SESS['bb_attachments'] : array();
	}
	
	//
	// Preview Attachments in Posts or PM's
	//
	function preview_attachments()
	{
		if (!count($this->attachments))  return; 
		global $attach_config, $is_auth;
		if (intval($attach_config['disable_mod']) || !$is_auth['auth_attachments']) { return FALSE; }
		display_attachments_preview($this->attachments);
	}

	//
	// Get Quota Limits
	//
	function get_quota_limits($userdata_quota, $user_id = 0)
	{
		global $attach_config, $db;
		//
		// Define Filesize Limits (Prepare Quota Settings)
		// Priority: User, Group, Management
		//
		// This method is somewhat query intensive, but i think because this one is only executed while attaching a file, 
		// it does not make much sense to come up with an new db-entry.
		// Maybe i will change this in a future version, where you are able to disable the User Quota Feature at all (using
		// Default Limits for all Users/Groups)
		//

		// Change this to 'group;user' if you want to have first priority on group quota settings.
//		  $priority = 'group;user';
		$priority = 'user;group';

		if ($userdata_quota['user_level'] == ADMIN) {
			$attach_config['pm_filesize_limit'] = 0; // Unlimited
			$attach_config['upload_filesize_limit'] = 0; // Unlimited
			return;
		}
		$quota_type = QUOTA_UPLOAD_LIMIT;
		$limit_type = 'upload_filesize_limit';
		$default = 'attachment_quota';

		if ($user_id < 1) {
			$user_id = intval($userdata_quota['user_id']);
		}
		
		$priority = explode(';', $priority);
		$found = FALSE;

		for ($i = 0; $i < count($priority); $i++) {
			if (($priority[$i] == 'group') && (!$found)) {
				//
				// Get Group Quota, if we find one, we have our quota
				//
				$sql = "SELECT u.group_id FROM " . USER_GROUP_TABLE . " u, " . GROUPS_TABLE . " g
				WHERE (g.group_single_user = 0) AND (u.group_id = g.group_id) AND (u.user_id = " . $user_id . ")";
				$result = $db->sql_query($sql);
				if ($db->sql_numrows($result) > 0) {
					$rows = $db->sql_fetchrowset($result);
					$group_id = array();
					for ($j = 0; $j < count($rows); $j++) {
						$group_id[] = $rows[$j]['group_id'];
					}
					$sql = "SELECT l.quota_limit FROM " . QUOTA_TABLE . " q, " . QUOTA_LIMITS_TABLE . " l
					WHERE (q.group_id IN (" . implode(',', $group_id) . ")) AND (q.group_id <> 0) AND (q.quota_type = " . $quota_type . ") 
					AND (q.quota_limit_id = l.quota_limit_id) ORDER BY l.quota_limit DESC LIMIT 1";
					$result = $db->sql_query($sql);
					if ($db->sql_numrows($result) > 0) {
						$row = $db->sql_fetchrow($result);
						$attach_config[$limit_type] = $row['quota_limit'];
						$found = TRUE;
					}
				}
			}
			if (($priority[$i] == 'user') && (!$found)) {
				//
				// Get User Quota, if the user is not in a group or the group has no quotas
				//
				$sql = "SELECT l.quota_limit FROM " . QUOTA_TABLE . " q, " . QUOTA_LIMITS_TABLE . " l
				WHERE (q.user_id = " . $user_id . ") AND (q.user_id <> 0) AND (q.quota_type = " . $quota_type . ") 
				AND (q.quota_limit_id = l.quota_limit_id) LIMIT 1";
				$result = $db->sql_query($sql);
				if ($db->sql_numrows($result) > 0) {
					$row = $db->sql_fetchrow($result);
					$attach_config[$limit_type] = $row['quota_limit'];
					$found = TRUE;
				}
			}
		}

		if (!$found) {
			// Set Default Quota Limit
			$quota_id = ($quota_type == QUOTA_UPLOAD_LIMIT) ? intval($attach_config['default_upload_quota']) : intval($attach_config['default_pm_quota']);
			if ($quota_id == 0) {
				$attach_config[$limit_type] = $attach_config[$default];
			} else {
				$sql = "SELECT quota_limit FROM " . QUOTA_LIMITS_TABLE . "
				WHERE quota_limit_id = " . $quota_id . " LIMIT 1";
				$result = $db->sql_query($sql);
				if ($db->sql_numrows($result) > 0) {
					$row = $db->sql_fetchrow($result);
					$attach_config[$limit_type] = $row['quota_limit'];
				} else {
					$attach_config[$limit_type] = $attach_config[$default];
				}
			}
		}

		// Never exceed the complete Attachment Upload Quota
		if ($quota_type == QUOTA_UPLOAD_LIMIT) {
			if (intval($attach_config[$limit_type]) > intval($attach_config[$default])) {
				$attach_config[$limit_type] = $attach_config[$default];
			}
		}
	}
	
	//
	// Handle all modes... (intern)
	//
	function handle_attachments($mode)
	{
		global $is_auth, $attach_config, $refresh, $post_id, $submit, $preview, $error, $error_msg, $lang, $template, $userdata, $db;
		global $CPG_SESS;
		$max_attachments = ($userdata['user_level'] == ADMIN) ? ADMIN_MAX_ATTACHMENTS : intval($attach_config['max_attachments']);
		//
		// nothing, if the user is not authorized or attachment mod disabled
		//
		if ( intval($attach_config['disable_mod']) || !$is_auth['auth_attachments']) { return FALSE; }
		//
		// Init Vars
		//
		if (!$refresh) {
			$add = ( isset($_POST['add_attachment']) ) ? TRUE : FALSE;
			$delete = ( isset($_POST['del_attachment']) ) ? TRUE : FALSE;
			$edit = ( isset($_POST['edit_comment']) ) ? TRUE : FALSE;
			$update_attachment = ( isset($_POST['update_attachment']) ) ? TRUE : FALSE;
			$del_thumbnail = ( isset($_POST['del_thumbnail']) ) ? TRUE : FALSE;
			$add_attachment_box = ( !empty($_POST['add_attachment_box']) ) ? TRUE : FALSE;
			$posted_attachments_box = ( !empty($_POST['posted_attachments_box']) ) ? TRUE : FALSE;
			$refresh = $add || $delete || $edit || $del_thumbnail || $update_attachment || $add_attachment_box;
		}
		//
		// Get Attachments
		//
		$auth = ( $is_auth['auth_edit'] || $is_auth['auth_mod'] ) ? TRUE : FALSE;
		if (!$submit && $mode == 'editpost' && $auth) {
			if (!$refresh && !$preview && !$error && !isset($_POST['del_poll_option'])) {
				$this->attachments = get_attachments_from_post($post_id);
			}
		}
		$this->num_attachments = count($this->attachments);
		if ($submit && $mode != 'vote') {
			if ($mode == 'newtopic' || $mode == 'reply' || $mode == 'editpost') {
				if ($this->filename != '') {
					if ($this->num_attachments < intval($max_attachments)) {
						$this->upload_attachment();
						if (!$error && $this->post_attach) {
							array_unshift($this->attachments, array(
								'physical_filename' => $this->attach_filename,
								'real_filename' => $this->filename,
								'extension' => $this->extension,
								'mimetype'	=> $this->type,
								'filesize'	=> $this->filesize,
								'filetime'	=> $this->filetime,
								'attach_id' => 0,
								'thumbnail' => $this->thumbnail,
								'comment'	=> $this->file_comment
							));
							$this->file_comment = '';
							// This Variable is set to FALSE here, because the Attachment Mod enter Attachments into the
							// Database in two modes, one if the id_list is -1 and the second one if post_attach is true
							// Since post_attach is automatically switched to true if an Attachment got added to the filesystem,
							// but we are assigning an id of -1 here, we have to reset the post_attach variable to FALSE.
							//
							// This is very relevant, because it could happen that the post got not submitted, but we do not
							// know this circumstance here. We could be at the posting page or we could be redirected to the entered
							// post. :)
							$this->post_attach = FALSE;
						}
					} else {
						$error = TRUE;
						if(!empty($error_msg)) { $error_msg .= '<br />'; }
						$error_msg .= sprintf($lang['Too_many_attachments'], intval($max_attachments));
					}
				}
			}
		}

		if ($preview || $refresh || $error) {
			$delete_attachment = ( isset($_POST['del_attachment']) ) ? TRUE : FALSE;
			$delete_thumbnail = ( isset($_POST['del_thumbnail']) ) ? TRUE : FALSE;
			$add_attachment = ( isset($_POST['add_attachment']) ) ? TRUE : FALSE;
			$edit_comment = ( isset($_POST['edit_comment']) ) ? TRUE : FALSE;
			$update_attachment = ( isset($_POST['update_attachment']) ) ? TRUE : FALSE;
			//
			// Perform actions on temporary attachments
			//
			$actual_list = isset($CPG_SESS['bb_attachments']) ? $CPG_SESS['bb_attachments'] : array();
			if ($delete_attachment || $delete_thumbnail) {
				// clean values
				$this->attachments = array();
				// restore values :)
				if (!empty($actual_list)) {
					for ($i = 0; $i < count($actual_list); $i++) {
						$attachment = $actual_list[$i];
						$restore = FALSE;
						$del_thumb = FALSE;
						if ($delete_thumbnail) {
							if (!isset($_POST['del_thumbnail'][$attachment['physical_filename']])) {
								$restore = TRUE;
							} else {
								$del_thumb = TRUE;
							}
						}
						if ($delete_attachment) {
							if (!isset($_POST['del_attachment'][$attachment['physical_filename']])) {
								$restore = TRUE;
							}
						}
						if ($restore) {
							$this->attachments[] = $attachment;
						} else if (!$del_thumb) {
							// delete selected attachment
							if ($attachment['attach_id'] < 1) {
								unlink_attach($attachment['physical_filename']);
								if ($attachment['thumbnail'] == 1) {
									unlink_attach($attachment['physical_filename'], MODE_THUMBNAIL);
								}
							} else {
								delete_attachment($post_id, $attachment['attach_id'], $this->page);
							}
						} else if ($del_thumb) {
							// delete selected thumbnail
							$attachment['thumbnail'] = 0;
							$this->attachments[] = $attachment;
							if ($attachment['attach_id'] < 1) {
								unlink_attach($attachment['physical_filename'], MODE_THUMBNAIL);
							} else {
								$db->sql_query("UPDATE " . ATTACHMENTS_DESC_TABLE . " SET thumbnail = 0
								WHERE attach_id = " . $attachment['attach_id']);
							}
						}
					}
				}
			} else if ($edit_comment || $update_attachment || $add_attachment || $preview) {
				if ($edit_comment) {
					$actual_comment_list = isset($_POST['comment_list']) ? $_POST['comment_list'] : '';
					for ($i = 0; $i < count($this->attachments); $i++) {
						$this->attachments[$i]['comment'] = $actual_comment_list[$i];
					}
				}
				if ($update_attachment) {
					if ($this->filename == '') {
						$error = TRUE;
						if(!empty($error_msg)) { $error_msg .= '<br />'; }
						$error_msg .= $lang['Error_empty_add_attachbox']; 
					}
					$this->upload_attachment();
					if (!$error) {
						$attachment_id = 0;
						$actual_element = 0;
						for ($i = 0; $i < count($actual_list); $i++) {
							if (isset($_POST['update_attachment'][($actual_list[$i]['attach_id'])])) {
								$attachment_id = intval($actual_list[$i]['attach_id']);
								$actual_element = $i;
								break;
							}
						}
						// Get current informations to delete the Old Attachment
						$sql = "SELECT physical_filename, comment, thumbnail FROM " . ATTACHMENTS_DESC_TABLE . "
						WHERE attach_id = " . $attachment_id;
						$result = $db->sql_query($sql);
						if ($db->sql_numrows($result) != 1) {
							$error = TRUE;
							if(!empty($error_msg)) { $error_msg .= '<br />'; }
							$error_msg .= $lang['Error_missing_old_entry'];
						}
						$row = $db->sql_fetchrow($result);
						$comment = (trim($this->file_comment) == '') ? trim($row['comment']) : trim($this->file_comment);
						// Update Entry
						$sql = "UPDATE " . ATTACHMENTS_DESC_TABLE . " 
						SET physical_filename = '" . $this->attach_filename . "', real_filename = '" . $this->filename . "', comment = '" . Fix_Quotes($comment) . "', extension = '" . $this->extension . "', mimetype = '" . $this->type . "', filesize = " . $this->filesize . ", filetime = " . $this->filetime . ", thumbnail = " . $this->thumbnail . "
						WHERE attach_id = " . $attachment_id;
						$db->sql_query($sql);
						// Delete the Old Attachment
						unlink_attach($row['physical_filename']);
						if (intval($row['thumbnail']) == 1) {
							unlink_attach($row['physical_filename'], MODE_THUMBNAIL);
						}
						//
						// Make sure it is displayed
						//
						$this->attachments[$actual_element] = array(
							'physical_filename' => $this->attach_filename,
							'real_filename' => $this->filename,
							'extension' => $this->extension,
							'mimetype'	=> $this->type,
							'filesize'	=> $this->filesize,
							'filetime'	=> $this->filetime,
							'attach_id' => $actual_list[$actual_element]['attach_id'],
							'thumbnail' => $this->thumbnail,
							'comment'	=> $comment
						);
						$this->file_comment = '';
					}
				}
				if (($add_attachment || $preview) && $this->filename != '') {
					if ($this->num_attachments < intval($max_attachments)) {
						$this->upload_attachment();
						if (!$error) {
							array_unshift($this->attachments, array(
								'physical_filename' => $this->attach_filename,
								'real_filename' => $this->filename,
								'extension' => $this->extension,
								'mimetype'	=> $this->type,
								'filesize'	=> $this->filesize,
								'filetime'	=> $this->filetime,
								'attach_id' => 0,
								'thumbnail' => $this->thumbnail,
								'comment'	=> $this->file_comment
							));
							$this->file_comment = '';
						}
					} else {
						$error = TRUE;
						if(!empty($error_msg)) { $error_msg .= '<br />'; }
						$error_msg .= sprintf($lang['Too_many_attachments'], intval($max_attachments)); 
					}
				}
			}
		}
		$CPG_SESS['bb_attachments'] = $this->attachments;
		return TRUE;
	}

	//
	// Insert an Attachment into a Post (this is the second function called from posting.php)
	//
	function insert_attachment($post_id)
	{
		global $db, $is_auth, $mode, $userdata, $error, $error_msg;
		if (!empty($post_id) && ( $mode == 'newtopic' || $mode == 'reply' || $mode == 'editpost' ) && ($is_auth['auth_attachments'])) {
			$this->do_insert_attachment('attach_list', 'post', $post_id);
			$this->do_insert_attachment('last_attachment', 'post', $post_id);
			if ((count($this->attachments) > 0 || $this->post_attach) && !isset($_POST['update_attachment'])) {
				$db->sql_query("UPDATE " . POSTS_TABLE . " SET post_attachment = 1 WHERE post_id = " . $post_id);
				$result = $db->sql_query("SELECT topic_id FROM " . POSTS_TABLE . " WHERE post_id = " . $post_id);
				$row = $db->sql_fetchrow($result);
				$db->sql_query("UPDATE " . TOPICS_TABLE . " SET topic_attachment = 1 WHERE topic_id = " . $row['topic_id']);
			}
		}
		global $CPG_SESS;
		unset($CPG_SESS['bb_attachments']); // no longer needed
	}

	//
	// Basic Insert Attachment Handling for all Message Types
	//
	function do_insert_attachment($mode, $message_type, $message_id)
	{
		global $db, $upload_dir;
		if (intval($message_id) < 0) { return (FALSE); }
		if ($message_type == 'pm') {
			global $userdata, $to_userdata;
			$post_id = 0;
			$privmsgs_id = $message_id;
			$user_id_1 = $userdata['user_id'];
			$user_id_2 = $to_userdata['user_id'];
		} else if ($message_type = 'post') {
			global $post_info, $userdata;
			$post_id = $message_id;
			$privmsgs_id = 0;
			$user_id_1 = isset($post_info['poster_id']) ? $post_info['poster_id'] : 0;
			$user_id_2 = 0;
			if (!$user_id_1) {
				$user_id_1 = $userdata['user_id'];
			}
		}
		if ($mode == 'attach_list') {
			for ($i = 0; $i < count($this->attachments); $i++) {
				$this->attachments[$i]['comment'] = Fix_Quotes($this->attachments[$i]['comment']);
				$this->attachments[$i]['real_filename'] = Fix_Quotes($this->attachments[$i]['real_filename']);
				if ($this->attachments[$i]['attach_id'] > 0) {
					//
					// update entry in db if attachment already stored in db and filespace
					//
					$sql = "UPDATE " . ATTACHMENTS_DESC_TABLE . " 
					SET comment = '" . trim($this->attachments[$i]['comment']) . "'
					WHERE attach_id = " . $this->attachments[$i]['attach_id'];
					$db->sql_query($sql);
				} else {
					//
					// insert attachment into db 
					//
					$sql = "INSERT INTO " . ATTACHMENTS_DESC_TABLE . " (physical_filename, real_filename, comment, extension, mimetype, filesize, filetime, thumbnail) 
					VALUES ( '"
					. $this->attachments[$i]['physical_filename'] . "', '"
					. $this->attachments[$i]['real_filename'] . "', '"
					. trim($this->attachments[$i]['comment']) . "', '"
					. $this->attachments[$i]['extension'] . "', '"
					. $this->attachments[$i]['mimetype'] . "', "
					. $this->attachments[$i]['filesize'] . ", "
					. $this->attachments[$i]['filetime'] . ", "
					. $this->attachments[$i]['thumbnail'] . ")";
					$db->sql_query($sql);
					$attach_id = $db->sql_nextid('attach_id');
					$sql = 'INSERT INTO ' . ATTACHMENTS_TABLE . ' (attach_id, post_id, privmsgs_id, user_id_1, user_id_2) VALUES (' . $attach_id . ', ' . $post_id . ', ' . $privmsgs_id . ', ' . $user_id_1 . ', ' . $user_id_2 . ')';
					$db->sql_query($sql);
				}
			}
			return TRUE;
		}
		if ($mode == 'last_attachment') {
			if ( ($this->post_attach) && (!isset($_POST['update_attachment'])) ) {
				//
				// insert attachment into db, here the user submited it directly 
				//
				$sql = "INSERT INTO " . ATTACHMENTS_DESC_TABLE . " (physical_filename, real_filename, comment, extension, mimetype, filesize, filetime, thumbnail) 
				VALUES ( '" . $this->attach_filename . "', '" . Fix_Quotes($this->filename) . "', '" . trim(Fix_Quotes($this->file_comment)) . "', '" . $this->extension . "', '" . $this->type . "', " . $this->filesize . ", " . $this->filetime . ", " . $this->thumbnail . ")";
				$db->sql_query($sql);
				$attach_id = $db->sql_nextid('attach_id');
				$sql = 'INSERT INTO ' . ATTACHMENTS_TABLE . ' (attach_id, post_id, privmsgs_id, user_id_1, user_id_2)
				VALUES (' . $attach_id . ', ' . $post_id . ', ' . $privmsgs_id . ', ' . $user_id_1 . ', ' . $user_id_2 . ')';
				$db->sql_query($sql);
			}
		}
	}
	//
	// Attachment Mod entry switch/output (intern)
	//
	function display_attachment_bodies()
	{
		global $attach_config, $db, $is_auth, $lang, $mode, $template, $upload_dir, $userdata, $forum_id;
		global $phpbb_root_path;
		$value_add = $value_posted = '';
		//
		// Choose what to display
		//
		if (intval($attach_config['show_apcp'])) {
			if (!empty($_POST['add_attachment_box'])) {
				$value_add = ($this->add_attachment_body == 0) ? '1' : '0';
				$this->add_attachment_body = intval($value_add);
			} else {
				$value_add = ($this->add_attachment_body == 0) ? '0' : '1';
			}
			if ( !empty($_POST['posted_attachments_box']) ) {
				$value_posted = ( $this->posted_attachments_body == 0 ) ? '1' : '0';
				$this->posted_attachments_body = intval($value_posted);
			} else {
				$value_posted = ( $this->posted_attachments_body == 0 ) ? '0' : '1';
			}
			$template->assign_block_vars('show_apcp', array());
		} else {
			$this->add_attachment_body = 1;
			$this->posted_attachments_body = 1;
		}
		$template->set_filenames(array('attachbody' => 'forums/posting_attach_body.html'));
//display_compile_cache_clear($template->files['attachbody'], 'attachbody');
		$s_hidden = '<input type="hidden" name="add_attachment_body" value="'.$value_add.'" />';
		$s_hidden .= '<input type="hidden" name="posted_attachments_body" value="'.$value_posted.'" />';
		$u_rules_id = $forum_id;
		$template->assign_vars(array(
			'L_ATTACH_POSTING_CP' => $lang['Attach_posting_cp'],
			'L_ATTACH_POSTING_CP_EXPLAIN' => $lang['Attach_posting_cp_explain'],
			'L_OPTIONS' => $lang['Options'],
			'L_ADD_ATTACHMENT_TITLE' => $lang['Add_attachment_title'],
			'L_POSTED_ATTACHMENTS' => $lang['Posted_attachments'],
			'L_FILE_NAME' => $lang['File_name'],
			'L_FILE_COMMENT' => $lang['File_comment'],
			'POSTED_ATTACHMENTS_BODY' => '',
			'RULES' => '<a href="' . getlink("Forums&amp;file=attach_rules&amp;f=$u_rules_id&amp;popup=1") . '" target="_blank">' . $lang['Allowed_extensions_and_sizes'] . '</a>',

			'S_HIDDEN' => $s_hidden)
		);
		$attachments = array();
		if (count($this->attachments) > 0) {
			if (intval($attach_config['show_apcp'])) {
				$template->assign_block_vars('switch_posted_attachments', array());
			}
		}
		if ($this->add_attachment_body) {
			$template->set_filenames(array('addbody' => 'forums/add_attachment_body.html'));
			$form_enctype = 'enctype="multipart/form-data" accept-charset="utf-8"';
			$template->assign_vars(array(
				'L_ADD_ATTACH_TITLE' => $lang['Add_attachment_title'],
				'L_ADD_ATTACH_EXPLAIN' => $lang['Add_attachment_explain'],
				'L_ADD_ATTACHMENT' => $lang['Add_attachment'],

				'FILE_COMMENT' => htmlprepare($this->file_comment),
				'FILESIZE' => intval($attach_config['max_filesize']),
				'FILENAME' => $this->filename,

				'S_FORM_ENCTYPE' => $form_enctype)	  
			);
			$template->assign_var_from_handle('ADD_ATTACHMENT_BODY', 'addbody');
		}
		if ($this->posted_attachments_body && count($this->attachments) > 0) {
			$template->set_filenames(array('postedbody' => 'forums/posted_attachments_body.html'));
			$template->assign_vars(array(
				'L_POSTED_ATTACHMENTS' => $lang['Posted_attachments'],
				'L_UPDATE_COMMENT' => $lang['Update_comment'],
				'L_UPLOAD_NEW_VERSION' => $lang['Upload_new_version'],
				'L_DELETE_ATTACHMENT' => $lang['Delete_attachment'],
				'L_DELETE_THUMBNAIL' => $lang['Delete_thumbnail'],
				'L_OPTIONS' => $lang['Options'])
			);
			for ($i = 0; $i < count($this->attachments); $i++) {
				if ($this->attachments[$i]['attach_id'] < 1) {
					$download_link = $upload_dir . '/' . $this->attachments[$i]['physical_filename'];
				} else {
					global $module_name;
					$module = ($module_name == 'Private_Messages') ? 'Forums' : $module_name;
					$download_link = getlink($module_name.'&amp;file=download&amp;id=' . $this->attachments[$i]['attach_id']);
				}
				$template->assign_block_vars('attach_row', array(
					'FILE_NAME' => $this->attachments[$i]['real_filename'],
					'ATTACH_FILENAME' => $this->attachments[$i]['physical_filename'],
					'FILE_COMMENT' => htmlprepare($this->attachments[$i]['comment']),
					'ATTACH_ID' => $this->attachments[$i]['attach_id'],

					'U_VIEW_ATTACHMENT' => $download_link)
				);
				//
				// Thumbnail there ? And is the User Admin or Mod ? Then present the 'Delete Thumbnail' Button
				//
				if (intval($this->attachments[$i]['thumbnail']) == 1 && ($is_auth['auth_mod'] || $userdata['user_level'] == ADMIN)) {
					$template->assign_block_vars('attach_row.switch_thumbnail', array());
				}
				if ($this->attachments[$i]['attach_id'] > 0) {
					$template->assign_block_vars('attach_row.switch_update_attachment', array());
				}
			}
			$template->assign_var_from_handle('POSTED_ATTACHMENTS_BODY', 'postedbody');
		}
		$template->assign_var_from_handle('ATTACHBOX', 'attachbody');
	}

	//
	// Upload an Attachment to Filespace (intern)
	//
	function upload_attachment()
	{
		global $db, $error, $error_msg, $lang, $attach_config, $userdata, $upload_dir, $forum_id;
		$this->post_attach = false;
        if (!is_uploaded_file($_FILES['fileupload']['tmp_name']) ||
            $_FILES['fileupload']['name'] == 'none' ||
            $_FILES['fileupload']['name'] == '') {
			$error = true;
			if (!empty($error_msg)) { $error_msg .= '<br />'; }
            switch ($_FILES['fileupload']['error']) {
              case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
				if (!empty($error_msg)) { $error_msg .= '<br />'; }
				$max_size = ini_get('upload_max_filesize');
				$error_msg .= (($max_size == '') ? $lang['Attachment_php_size_na'] : sprintf($lang['Attachment_php_size_overrun'], $max_size));
                break;
              case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
				$error_msg .= $lang['Attachment_php_size_na'];
                break;
              case 3: //uploaded file was only partially uploaded
				$error_msg .= 'The file you are trying upload was only partially uploaded.';
                break;
            }
            return false;
        }

		$r_file = trim(basename($this->filename));
		$this->filesize = intval($_FILES['fileupload']['size']);
		$this->type = $_FILES['fileupload']['type'];
		// Opera add the name to the mime type
		$this->type = (strstr($this->type, '; name')) ? str_replace(strstr($this->type, '; name'), '', $this->type) : $this->type;
		$this->extension = get_extension($this->filename);
		$sql = "SELECT g.allow_group, g.max_filesize, g.cat_id, g.forum_permissions
		FROM " . EXTENSION_GROUPS_TABLE . " g, " . EXTENSIONS_TABLE . " e
		WHERE (g.group_id = e.group_id) AND (e.extension = '" . $this->extension . "')
		LIMIT 1";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$allowed_filesize = (intval($row['max_filesize']) != 0) ? intval($row['max_filesize']) : intval($attach_config['max_filesize']);
		$cat_id = intval($row['cat_id']);
		$auth_cache = trim($row['forum_permissions']);
		//
		// check Filename
		//
		if (preg_match("/[\\/:*?\"<>|]/i", $this->filename)) {
			$error = TRUE;
			if (!empty($error_msg)) { $error_msg .= '<br />'; }
			$error_msg .= sprintf($lang['Invalid_filename'], $this->filename);
		}
		//
		// Check Extension
		//
		if (!$error && intval($row['allow_group']) == 0) {
			$error = TRUE;
			if (!empty($error_msg)) { $error_msg .= '<br />'; }
			$error_msg .= sprintf($lang['Disallowed_extension'], $this->extension);
		} 
		//
		// Check Forum Permissions
		//
		if (!$error && $userdata['user_level'] != ADMIN && !is_forum_authed($auth_cache, $forum_id) && (trim($auth_cache) != '')) {
			$error = TRUE;
			if (!empty($error_msg)) { $error_msg .= '<br />'; }
			$error_msg .= sprintf($lang['Disallowed_extension_within_forum'], $this->extension);
		}
		//
		// Check Image Size, if it's an image
		//
		$this->thumbnail = intval($cat_id == IMAGE_CAT && intval($attach_config['img_create_thumbnail']));
		if (!$error && $userdata['user_level'] != ADMIN && $cat_id == IMAGE_CAT) {
			list($width, $height) = image_getdimension($_FILES['fileupload']['tmp_name']);
			$attach_config['img_max_width'] = intval($attach_config['img_max_width']);
			$attach_config['img_max_height'] = intval($attach_config['img_max_height']);
			if ($width > 0 && $height > 0 && $attach_config['img_max_width'] > 0 && $attach_config['img_max_height'] > 0 &&
			   ($width > $attach_config['img_max_width'] || $height > $attach_config['img_max_height'])) {
				$error = TRUE;
				if(!empty($error_msg)) { $error_msg .= '<br />'; }
				$error_msg .= sprintf($lang['Error_imagesize'], $attach_config['img_max_width'], $attach_config['img_max_height']);
			}
		}
		//
		// check Filesize 
		//
		if (!$error && $allowed_filesize != 0 && $this->filesize > $allowed_filesize && $userdata['user_level'] != ADMIN) {
			$error = TRUE;
			if(!empty($error_msg)) { $error_msg .= '<br />'; }
			$error_msg .= sprintf($lang['Attachment_too_big'], filesize_to_human($allowed_filesize), '');
		}
		//
		// Check our complete quota
		//
		if (intval($attach_config['attachment_quota']) != 0) {
			list($total_filesize) = $db->sql_ufetchrow('SELECT sum(filesize) FROM '.ATTACHMENTS_DESC_TABLE, SQL_NUM);
			if ($total_filesize + $this->filesize > intval($attach_config['attachment_quota'])) {
				$error = TRUE;
				if (!empty($error_msg)) { $error_msg .= '<br />'; }
				$error_msg .= $lang['Attach_quota_reached'];
			}
		}
		$this->get_quota_limits($userdata);
		//
		// Check our user quota
		//
		if (intval($attach_config['upload_filesize_limit']) != 0) {
			$sql = "SELECT attach_id FROM " . ATTACHMENTS_TABLE . "
			WHERE (user_id_1 = ".$userdata['user_id'].") AND (privmsgs_id = 0)
			GROUP BY attach_id";
			$result = $db->sql_uquery($sql);
			$attach_id = array();
			while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
				$attach_id[] = intval($row[0]);
			}
			if (count($attach_id) > 0) {
				// Now get the total filesize
				list($total_filesize) = $db->sql_ufetchrow("SELECT sum(filesize) FROM ".ATTACHMENTS_DESC_TABLE." WHERE attach_id IN (".implode(', ', $attach_id).")", SQL_NUM);
			} else {
				$total_filesize = 0;
			}
			if ($total_filesize + $this->filesize > intval($attach_config['upload_filesize_limit'])) {
				$upload_filesize_limit = intval($attach_config['upload_filesize_limit']);
				$error = TRUE;
				if(!empty($error_msg)) { $error_msg .= '<br />'; }
				$error_msg .= sprintf($lang['User_upload_quota_reached'], filesize_to_human($upload_filesize_limit), '');
			}
		}
		//
		// Prepare Values
		//
		if (!$error) {
			$this->filetime = gmtime();
			$this->filename = $r_file;
			$this->attach_filename = $this->filename;
			// To re-add cryptic filenames, change this variable to true
			$cryptic = false;
			if (!$cryptic) {
				$this->attach_filename = str_replace(' ', '_', $this->attach_filename);
				$this->attach_filename = rawurlencode($this->attach_filename);
				$this->attach_filename = preg_replace("/%(\w{2})/", "_", $this->attach_filename);
				if ($this->attach_filename != '' && $db->sql_count(ATTACHMENTS_DESC_TABLE, "physical_filename='".$this->attach_filename."'") > 0) {
					$this->attach_filename = substr($this->attach_filename, 0, strrpos($this->attach_filename, '.'));
					$this->attach_filename = $this->attach_filename.'_'.substr(rand(), 0, 3).'.'.$this->extension;
				}
			} else {
				$u_id = (intval($userdata['user_id']) == ANONYMOUS) ? 0 : intval($userdata['user_id']);
				$this->attach_filename = $u_id . '_' . $this->filetime . '.' . $this->extension;
			}
			$this->filename = str_replace("'", "\'", $this->filename);
		}
		//
		// Upload Attachment
		//
		if (!$error) {
			$this->move_uploaded_attachment($_FILES['fileupload'], $_FILES['fileupload']['tmp_name']);
		}
		$this->post_attach = !$error;
	}
	
	//
	// Copy the temporary attachment to the right location (copy, move_uploaded_file or ftp)
	//
	function move_uploaded_attachment($file, $filename)
	{
		global $error, $error_msg, $lang, $upload_dir, $attach_config;
		if (intval($attach_config['allow_ftp_upload'])) {
			ftp_file($filename, $this->attach_filename, $this->type);
		} else {
			require_once('includes/classes/cpg_file.php');
			if (!CPG_File::move_upload($file, $upload_dir.'/'.$this->attach_filename)) {
				$error = TRUE;
				if (!empty($error_msg)) { $error_msg .= '<br />'; }
				$error_msg .= sprintf($lang['General_upload_error'], './'.$upload_dir.'/'.$this->attach_filename);
				return;
			}
		}
		if (!$error && $this->thumbnail == 1) {
			if (intval($attach_config['allow_ftp_upload'])) {
				$source = $file;
				$dest_file = THUMB_DIR.'/t_'.$this->attach_filename;
			} else {
				$source = $upload_dir.'/'.$this->attach_filename;
				$dest_file = amod_realpath($upload_dir);
				$dest_file .= '/'.THUMB_DIR.'/t_'.$this->attach_filename;
			}
			if (!create_thumbnail($file, $dest_file, $this->type)) {
				if (!create_thumbnail($source, $dest_file, $this->type)) {
					$this->thumbnail = 0;
				}
			}
		}
	}
}


class attach_posting extends attach_parent
{
	function attach_posting()
	{
		$this->attach_parent();
		$this->page = -1;
	}
	//
	// Handle Attachments (Add/Delete/Edit/Show) - This is the first function called from every message handler
	//
	function posting_attachment_mod()
	{
		global $mode, $confirm, $is_auth, $post_id, $delete, $refresh;
		if (!$refresh) {
			$add_attachment_box = ( !empty($_POST['add_attachment_box']) ) ? TRUE : FALSE;
			$posted_attachments_box = ( !empty($_POST['posted_attachments_box']) ) ? TRUE : FALSE;
			$refresh = $add_attachment_box || $posted_attachments_box;
		}
		// Choose what to display
		if (!$this->handle_attachments($mode)) { return; }
		if ($confirm && ($delete || $mode == 'delete' || $mode == 'editpost') && ($is_auth['auth_delete'] || $is_auth['auth_mod'])) {
			if (!empty($post_id)) { delete_attachment($post_id); }
		}
		$this->display_attachment_bodies();
	}
}
