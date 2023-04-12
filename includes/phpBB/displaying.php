<?php
/***************************************************************************
 *								  displaying.php
 *							  -------------------
 *	 begin				  : Monday, Jul 15, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: displaying.php,v 9.10 2007/09/13 06:21:38 nanocaiordo Exp $
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

if (!defined('IN_PHPBB')) {
	die('Hacking attempt');
}

$allowed_extensions = $display_categories = $download_modes = $upload_icons = $attachments = array();

function display_compile_cache_clear($filename, $template_var)
{
	global $template;
	if (isset($template->cachedir)) {
		$filename = str_replace($template->root, '', $filename);
		if (str_starts_with($filename, '/')) {
			$filename = substr($filename, 1, strlen($filename));
		}
		if (file_exists(amod_realpath($template->cachedir . $filename . '.php'))) {
			unlink($template->cachedir . $filename . '.php');
		}
	}
	return;
}

// 
// Create needed arrays for Extension Assignments
//
function init_complete_extensions_data()
{
	global $db, $allowed_extensions, $display_categories, $download_modes, $upload_icons;

	$extension_informations = get_extension_informations();
	$allowed_extensions = array();

	for ($i = 0; $i < (is_countable($extension_informations) ? count($extension_informations) : 0); $i++)
	{
		$extension = strtolower(trim($extension_informations[$i]['extension']));
		$allowed_extensions[] = $extension;
		$display_categories[$extension] = intval($extension_informations[$i]['cat_id']);
		$download_modes[$extension] = intval($extension_informations[$i]['download_mode']);
		$upload_icons[$extension] = trim($extension_informations[$i]['upload_icon']);
	}
}

//
// Writing Data into plain Template Vars
//
function init_display_template($template_var, $replacement, $filename = 'forums/viewtopic_attach_body.html')
{
	global $template;
	//
	// Handle Attachment Informations
	//
	if (!isset($template->uncompiled_code[$template_var])) {
		// If we don't have a file assigned to this handle, die.
		if (!isset($template->files[$template_var])) {
			die("Template->loadfile(): No file specified for attachment handle $template_var");
		}
		$filename_2 = $template->files[$template_var];
//		  die("Filename: $filename_2");
		$str = implode("", file($filename_2));
		if (empty($str)) {
			die("Template->loadfile(): File $filename_2 for attachment handle $template_var is empty");
		}
		$template->uncompiled_code[$template_var] = $str;
	}

	$complete_filename = $filename;
	if (!str_starts_with($complete_filename, '/')) {
		$complete_filename = $template->root . '/' . $complete_filename;
	}

	if (!file_exists(amod_realpath($complete_filename))) {
		die("Template->make_filename(): Error - file $complete_filename does not exist for displaying");
	}

	$content = implode('', file($complete_filename));
	if (empty($content)) {
		die('Template->loadfile(): File ' . $complete_filename . ' is empty');
	}

	// replace $replacement with uncompiled code in $filename
	$template->uncompiled_code[$template_var] = str_replace($replacement, $content, $template->uncompiled_code[$template_var]);

	//
	// Force Reload on cached version
	//
	display_compile_cache_clear($template->files[$template_var], $template_var);
}

//
// BEGIN ATTACHMENT DISPLAY IN POSTS
//

//
// Returns the image-tag for the topic image icon
//
function topic_attachment_image($switch_attachment)
{
	global $attach_config, $is_auth;
	if ( (intval($switch_attachment) == 0) || (!( ($is_auth['auth_download']) && ($is_auth['auth_view']))) || (intval($attach_config['disable_mod'])) || ($attach_config['topic_icon'] == '') ) {
		return ('');
	}
	$image = '<img src="' . $attach_config['topic_icon'] . '" alt="" /> ';
	return ($image);
}

//
// END ATTACHMENT DISPLAY IN POSTS
//

//
// BEGIN ATTACHMENT DISPLAY IN TOPIC REVIEW WINDOW
//

//
// Display Attachments in Review Window
//
function display_review_attachments($post_id, $switch_attachment, $is_auth)
{
	//just return
	return;
	//if (empty($is_auth)) return;
	global $attach_config, $attachments;
	if (intval($switch_attachment) == 0 || intval($attach_config['disable_mod']) || !($is_auth['auth_download'] && $is_auth['auth_view']) || intval($attach_config['attachment_topic_review']) == 0) {
		return;
	}
	reset($attachments);
	$attachments['_' . $post_id] = get_attachments_from_post($post_id);
	if ((is_countable($attachments['_' . $post_id]) ? count($attachments['_' . $post_id]) : 0) == 0) { return; }
	display_attachments($post_id);
}

//
// Initializes some templating variables for displaying Attachments in Review Topic Window
//
function init_display_review_attachments($is_auth)
{
	// just return
	return;
	//if (empty($is_auth)) return;
	global $attach_config;
	if ( (intval($attach_config['disable_mod'])) || (!( ($is_auth['auth_download']) && ($is_auth['auth_view']))) || (intval($attach_config['attachment_topic_review']) == 0)) {
		return;
	}
	init_display_template('body', '{postrow.ATTACHMENTS}');
	init_complete_extensions_data();
}

//
// END ATTACHMENT DISPLAY IN TOPIC REVIEW WINDOW
//

//
// BEGIN DISPLAY ATTACHMENTS -> PREVIEW
//
//function display_attachments_preview($attachment_list, $attachment_filesize_list, $attachment_filename_list, $attachment_comment_list, $attachment_extension_list, $attachment_thumbnail_list)
function display_attachments_preview($attachment_list)
{
	//atm just return
	return;
	
	global $attach_config, $is_auth, $allowed_extensions, $lang, $userdata, $display_categories, $upload_dir, $upload_icons, $template, $db, $theme, $bgcolor2, $textcolor2;
	if ((is_countable($attachment_list) ? count($attachment_list) : 0) < 1) { return; }

	init_display_template('preview', '{ATTACHMENTS}');
	init_complete_extensions_data();
	$template->assign_block_vars('postrow', array());

	// Another 'i have to fix minor phpBB2 Bugs...' patch
	$template->assign_vars(array(
		'T_BODY_TEXT' => $textcolor2,
		'T_TR_COLOR3' => $bgcolor2)
	);
	$blockvar = 'postrow.attachment';
	//
	// Some basic Template Vars
	//
	$template->assign_vars(array(
		'L_DESCRIPTION' => $lang['Description'],
		'L_DOWNLOAD' => $lang['Download'],
		'L_FILENAME' => $lang['File_name'],
		'L_FILESIZE' => $lang['Filesize'])
	);

	for ($i=0, $attach_count = is_countable($attachment_list) ? count($attachment_list) : 0 ; $i < $attach_count; ++$i)
	{
		$extension = strtolower(trim($attachment_list[$i]['extension']));
		//
		// Admin is allowed to view forbidden Attachments, but the error-message is displayed too to inform the Admin
		//
		if (!in_array($extension, $allowed_extensions)) {
			$template->assign_block_vars($blockvar.'.denyrow', array(
				'L_DENIED' => sprintf($lang['Extension_disabled_after_posting'], $extension))
			);
		} else {
			$filename = $upload_dir.'/'.$attachment_list[$i]['physical_filename'];
			//
			// define category
			//
			$display = 'DEF_CAT';
			if (intval($display_categories[$extension]) == STREAM_CAT) {
				$display = 'STREAM_CAT';
			} else if (intval($display_categories[$extension]) == SWF_CAT) {
				$display = 'SWF_CAT';
			} else if ( (intval($display_categories[$extension]) == IMAGE_CAT) && (intval($attachment_list[$i]['thumbnail']) == 1) ) {
				$display = 'THUMB_CAT';
			} else if ( (intval($display_categories[$extension]) == IMAGE_CAT) && (intval($attach_config['img_display_inlined'])) ) {
				if ( (intval($attach_config['img_link_width']) != 0) || (intval($attach_config['img_link_height']) != 0) ) {
					list($width, $height) = image_getdimension($filename);
					if ( ($width == 0) && ($height == 0) ) {
						$display = 'IMAGE_CAT';
					} else {
						if ( ($width <= intval($attach_config['img_link_width'])) && ($height <= intval($attach_config['img_link_height'])) ) {
							$display = 'IMAGE_CAT';
						}
					}
				} else {
					$display = 'IMAGE_CAT';
				}
			}

			$blockname = $blockvar;
			switch ($display)
			{
				// Macromedia Flash Files
				case 'SWF_CAT':
					list($width, $height) = swf_getdimension($filename);
					break;

				// display attachment
				default:
					$upload_image = '';
					if ( ($attach_config['upload_img'] != '') && ($upload_icons[$extension] == '') ) {
						$upload_image = '<img src="' . $attach_config['upload_img'] . '" alt="" />';
					} else if (trim($upload_icons[$extension]) != '') {
						$upload_image = '<img src="' . $upload_icons[$extension] . '" alt="" />';
					}
					break;
			}
			$template->assign_block_vars($blockname, array(
				'S_DEF_CAT'	   => false,
				'S_IMAGE_CAT'  => false,
				'S_THUMB_CAT'  => false,
				'S_STREAM_CAT' => false,
				'S_SWF_CAT'	   => false,
				('S_'.$display) => true,
				'DOWNLOAD_NAME' => $attachment_list[$i]['real_filename'],
				'S_UPLOAD_IMAGE' => $upload_image,

				'FILESIZE' => filesize_to_human($attachment_list[$i]['filesize']),
				'COMMENT' => htmlprepare($attachment_list[$i]['comment'], true),

				'L_DOWNLOADED_VIEWED' => ($display == 'DEF_CAT') ? $lang['Downloaded'] : $lang['Viewed'],
				'L_DOWNLOAD_COUNT' => sprintf($lang['Download_number'], $attachment_list[$i]['download_count']),

				//images
				'IMG_SRC' => $filename,
				'IMG_THUMB_SRC' => '',

				//-images
				'U_DOWNLOAD_LINK' => $filename,

				//flash
				'WIDTH' => $width ?? '',
				'HEIGHT' => $height ?? '',

				//default
				'TARGET_BLANK' => (intval($display_categories[$attachment_list[$i]['extension']]) == IMAGE_CAT || $display == 'DEF_CAT') ? 'target="_blank"' : '',
			));
		}
	}
}

//
// END DISPLAY ATTACHMENTS -> PREVIEW
//

//
// Assign Variables and Definitions based on the fetched Attachments - internal
// used by all displaying functions, the Data was collected before, it's only dependend on the template used. :)
// before this function is usable, init_display_attachments have to be called for specific pages (pm, posting, review etc...)
//
function display_attachments($post_id)
{
	global $template, $upload_dir, $userdata, $allowed_extensions, $display_categories, $download_modes,
		   $db, $lang, $attachments, $upload_icons, $attach_config, $module_name;

	if (empty($attachments) || !isset($attachments['_' . $post_id])) {
//		  trigger_error('There are no attachments for '.$post_id, E_USER_NOTICE);
		return;
	}

	$num_attachments = is_countable($attachments['_' . $post_id]) ? count($attachments['_' . $post_id]) : 0;
	$blockvar = 'postrow.attachment';
	//
	// Some basic Template Vars
	//
	$template->assign_vars(array(
		'L_DESCRIPTION' => $lang['Description'],
		'L_DOWNLOAD' => $lang['Download'],
		'L_FILENAME' => $lang['File_name'],
		'L_FILESIZE' => $lang['Filesize'])
	);

	for ($i = 0; $i < $num_attachments; $i++) {
		//
		// Some basic things...
		//
		$attachments['_' . $post_id][$i]['extension'] = strtolower(trim($attachments['_' . $post_id][$i]['extension']));

		//
		// Admin is allowed to view forbidden Attachments, but the error-message is displayed too to inform the Admin
		//
		$denied = !in_array($attachments['_' . $post_id][$i]['extension'], $allowed_extensions);
		if (!$denied || is_admin()) {
			$filename = $upload_dir . '/' . $attachments['_' . $post_id][$i]['physical_filename'];

			$upload_image = '';
			if ($attach_config['upload_img'] != '' && trim($upload_icons[$attachments['_' . $post_id][$i]['extension']]) == '') {
				$upload_image = '<img src="' . $attach_config['upload_img'] . '" alt="" />';
			} else if (trim($upload_icons[$attachments['_' . $post_id][$i]['extension']]) != '') {
				$upload_image = '<img src="' . $upload_icons[$attachments['_' . $post_id][$i]['extension']] . '" alt="" />';
			}

			//
			// define category
			//
			$display = 'DEF_CAT';
			if (intval($display_categories[$attachments['_' . $post_id][$i]['extension']]) == STREAM_CAT) {
				$display = 'STREAM_CAT';
			} else if (intval($display_categories[$attachments['_' . $post_id][$i]['extension']]) == SWF_CAT) {
				$display = 'SWF_CAT';
			} else if ( (intval($display_categories[$attachments['_' . $post_id][$i]['extension']]) == IMAGE_CAT) && ($attachments['_' . $post_id][$i]['thumbnail'] == 1) ) {
				$display = 'THUMB_CAT';
			} else if ( (intval($display_categories[$attachments['_' . $post_id][$i]['extension']]) == IMAGE_CAT) && (intval($attach_config['img_display_inlined'])) ) {
				if ( (intval($attach_config['img_link_width']) != 0) || (intval($attach_config['img_link_height']) != 0) ) {
					list($width, $height) = image_getdimension($filename);
					if ( ($width == 0) && ($height == 0) ) {
						$display = 'IMAGE_CAT';
					} else {
						if ( ($width <= intval($attach_config['img_link_width'])) && ($height <= intval($attach_config['img_link_height'])) ) {
							$display = 'IMAGE_CAT';
						}
					}
				} else {
					$display = 'IMAGE_CAT';
				}
			}
			$thumb_source = '';
			$width = $height = 0;
			$blockname = $blockvar;
			$module = ($module_name == 'Private_Messages') ? 'Forums' : $module_name;
			switch ($display)
			{
				// Images
				case 'IMAGE_CAT':
					// NOTE: If you want to use the download.php everytime an image is displayed inlined, replace the
					// Section between BEGIN and END with (Without the // of course):
					//	  $img_source = getlink($module.'&amp;file=download&amp;id=' . $attachments['_' . $post_id][$i]['attach_id']);
					//	  $download_link = TRUE;
					//
					if ((intval($attach_config['allow_ftp_upload'])) && (trim($attach_config['download_path']) == '')) {
						$filename = getlink($module.'&amp;file=download&amp;id=' . $attachments['_' . $post_id][$i]['attach_id']);
						$download_link = TRUE;
					} else {
						$download_link = FALSE;
					}
					//
					// Directly Viewed Image ... update the download count
					//
					if (!$download_link) {
						$db->sql_query('UPDATE ' . ATTACHMENTS_DESC_TABLE . '
						SET download_count = download_count + 1
						WHERE attach_id = ' . $attachments['_' . $post_id][$i]['attach_id']);
					}
					break;

				// Images, but display Thumbnail
				case 'THUMB_CAT':
					// NOTE: If you want to use the download.php everytime an thumnmail is displayed inlined, replace the
					// Section between BEGIN and END with (Without the // of course):
					//	  $thumb_source = getlink($module.'&amp;file=download&amp;id=' . $attachments['_' . $post_id][$i]['attach_id'] . '&thumb=1');
					//
					if ( (intval($attach_config['allow_ftp_upload'])) && (trim($attach_config['download_path']) == '') ) {
						$thumb_source = getlink($module.'&amp;file=download&amp;id=' . $attachments['_' . $post_id][$i]['attach_id'] . '&thumb=1');
					} else {
						$thumb_source = $upload_dir . '/' . THUMB_DIR . '/t_' . $attachments['_' . $post_id][$i]['physical_filename'];
					}
					$filename = getlink($module.'&amp;file=download&amp;id=' . $attachments['_' . $post_id][$i]['attach_id']);
					break;

				// Streams
				case 'STREAM_CAT':
					//
					// Viewed/Heared File ... update the download count (download.php is not called here)
					//
					$db->sql_query('UPDATE ' . ATTACHMENTS_DESC_TABLE . '
					SET download_count = download_count + 1
					WHERE attach_id = ' . $attachments['_' . $post_id][$i]['attach_id']);
					break;

				// Macromedia Flash Files
				case 'SWF_CAT':
					list($width, $height) = swf_getdimension($filename);
					//
					// Viewed/Heared File ... update the download count (download.php is not called here)
					//
					$db->sql_query('UPDATE ' . ATTACHMENTS_DESC_TABLE . '
					SET download_count = download_count + 1
					WHERE attach_id = ' . $attachments['_' . $post_id][$i]['attach_id']);
					break;

				// display attachment
				default:
					$filename = getlink($module.'&amp;file=download&amp;id=' . $attachments['_' . $post_id][$i]['attach_id']);
					break;
			}
			$template->assign_block_vars($blockname, array(
				'L_ALLOWED' => !$denied || is_admin(),
				'L_DENIED' => $denied ? sprintf($lang['Extension_disabled_after_posting'], $attachments['_' . $post_id][$i]['extension']) : false,
				'S_DEF_CAT'	   => false,
				'S_IMAGE_CAT'  => false,
				'S_THUMB_CAT'  => false,
				'S_STREAM_CAT' => false,
				'S_SWF_CAT'	   => false,
				('S_'.$display) => true,
				'DOWNLOAD_NAME' => $attachments['_' . $post_id][$i]['real_filename'],
				'S_UPLOAD_IMAGE' => $upload_image,

				'FILESIZE' => filesize_to_human($attachments['_' . $post_id][$i]['filesize']),
				'COMMENT' => htmlprepare($attachments['_' . $post_id][$i]['comment'], true),

				'L_DOWNLOADED_VIEWED' => ($display == 'DEF_CAT') ? $lang['Downloaded'] : $lang['Viewed'],
				'L_DOWNLOAD_COUNT' => sprintf($lang['Download_number'], $attachments['_' . $post_id][$i]['download_count']),

				//images
				'IMG_SRC' => $filename,
				'IMG_THUMB_SRC' => $thumb_source,

				//-images
				'U_DOWNLOAD_LINK' => $filename,

				//flash
				'WIDTH' => $width,
				'HEIGHT' => $height,

				//default
				'TARGET_BLANK' => (intval($display_categories[$attachments['_' . $post_id][$i]['extension']]) == IMAGE_CAT || $display == 'DEF_CAT') ? 'target="_blank"' : '',
			));
		}
	}
}