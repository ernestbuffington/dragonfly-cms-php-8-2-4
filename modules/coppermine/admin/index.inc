<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   http://dragonflycms.org/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($op)) { exit; }
require("modules/{$op}/include/load.inc");

if (count($_POST)) {
	$msg = UPD_SUCCESS;
	$redirect = URL::admin();
	if (isset($_POST['update_config'])) {
		$options = array(
			'gallery_name' => '',
			'gallery_description' => '',
			'subcat_level' => 0,
			'albums_per_page' => 1,
			'album_list_cols' => 1,
			'alb_list_thumb_size' => 0,
			'main_page_layout' => '',
			'thumbcols' => 1,
			'thumbrows' => 1,
			'max_tabs' => 1,
			'default_sort_order' => '',
			'min_votes_for_rating' => 0,
			'max_img_desc_length' => 0,
			'max_com_wlength' => 0,
			'max_com_lines' => 0,
			'max_com_size' => 0,
			'max_film_strip_items' => 1,
			'keep_votes_time' => 0,
			'jpeg_qual' => 0,
			'thumb_width' => 1,
			'thumb_use' => '',
			'picture_width' => 1,
			'max_upl_width_height' => 0,
			'user_field1_name' => '',
			'user_field2_name' => '',
			'user_field3_name' => '',
			'user_field4_name' => '',
			'thumb_method' => '',
			'allowed_img_types' => '',
			'fullpath' => '',
			'userpics' => '',
			'normal_pfx' => '',
			'thumb_pfx' => '',
			'nice_titles' => true,
			'first_level' => true,
			'caption_in_thumbview' => true,
			'display_comment_count' => true,
			'seo_alts' => true,
			'display_pic_info' => true,
			'filter_bad_words' => true,
			'disable_flood_protection' => true,
			'comment_email_notification' => true,
			'display_film_strip' => true,
			'allow_anon_fullsize' => true,
			'fullsize_slideshow' => true,
			'right_blocks' => true,
			'watermark' => true,
			'make_intermediate' => true,
			'allow_private_albums' => true,
			'avatar_private_album' => true,
			'show_private' => true,
			'read_exif_data' => true,
			'read_iptc_data' => true,
			'picinfo_display_filename' => true,
			'picinfo_display_album_name' => true,
			'picinfo_display_file_size' => true,
			'picinfo_display_dimensions' => true,
			'picinfo_display_favorites' => true,
			'debug_mode' => true,
		);
		foreach ($options as $name => $type) {
			if (is_int($type)) {
				$value = max($type, $_POST->uint($name));
			} else if (is_bool($type)) {
				$value = $_POST->bool($name);
			} else {
				$value = $_POST[$name];
				$old_value = $CONFIG[$name];
				if ('fullpath' === $name || 'userpics' === $name) {
					$value = trim($value, '/') . '/';
				}
				else if ('normal_pfx' === $name || 'thumb_pfx' === $name) {
					$value = preg_replace('#[^A-Za-z0-9_]+#', '_', trim($value));
					if ($old_value != $value) {
						// rename all intermediate images or thumbnails
						$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($CONFIG['fullpath'], \FilesystemIterator::SKIP_DOTS));
						foreach ($iterator as $path) {
							if (!$path->isDir() && preg_match("#^(.+[\/]){$old_value}([^\/]+)$#", $path, $m)) {
								rename($path, "{$m[1]}{$value}{$m[2]}");
							}
						}
						if (false === strpos($CONFIG['userpics'], $CONFIG['fullpath'])) {
							$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($CONFIG['userpics'], \FilesystemIterator::SKIP_DOTS));
							foreach ($iterator as $path) {
								if (!$path->isDir() && preg_match("#^(.+[\/]){$old_value}([^\/]+)$#", $path, $m)) {
									rename($path, "{$m[1]}{$value}{$m[2]}");
								}
							}
						}
					}
				}
			}
			$CONFIG['TABLE_CONFIG']->update(array('value'=>$value), "name='{$name}'");
		}
	} else if (isset($_POST['restore_config'])) {
		$xml = file_get_contents(BASEDIR.'modules/coppermine/install/data.xml');
		$xml = str_replace('<table name="cpg_','<table name="'.('coppermine'===$op?'cpg':$op).'_',$xml);
		$db->query("DELETE FROM {$CONFIG['TABLE_CONFIG']}");
		if (!$db->XML->syncSchemaFromString($xml)) {
			cpg_error(print_r($db->XML->errors,1));
		}
		$msg = RESTORE_SUCCESS;
	}
	\Dragonfly::closeRequest($msg, 200, $redirect);
}

\Dragonfly\Page::title(\Dragonfly::getKernel()->L10N['Configuration']); // TODO check if needs translation

$OUT = \Dragonfly::getKernel()->OUT;
$OUT->cpg_config = $CONFIG;
$OUT->image_handlers = array(array('value' => '', 'label' => '[SYSTEM DEFAULT]', 'current' => false));
foreach (\Poodle\Image::getHandlers() as $name => $info) {
	$OUT->image_handlers[] = array(
		'value' => $name,
		'label' => $info['name'],
		'current' => $name == $CONFIG['thumb_method'],
	);
}
$OUT->display('coppermine/admin/configuration');
