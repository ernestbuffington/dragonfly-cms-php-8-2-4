<?php
/***************************************************************************
 *								  download.php
 *							  -------------------
 *	 begin				  : Monday, Apr 1, 2002
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

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

$attach_id = (int)$_GET->uint('id');
$thumbnail = $_GET->bool('thumb');

if ($attach_id < 1) {
	cpg_error($lang['No_attachment_selected'], 400);
}

if ($attach_config['disable_mod'] && !can_admin($module_name)) {
	cpg_error($lang['Attachment_feature_disabled'], 403);
}

$attachment = $db->uFetchAssoc("SELECT
	COALESCE(pa.forum_id,p.forum_id) as forum_id,
	a.user_id_1,
	d.extension,
	d.mimetype,
	u.upload_file as file,
	u.upload_name as name
FROM ".ATTACHMENTS_TABLE." a
INNER JOIN ".ATTACHMENTS_DESC_TABLE." d USING (attach_id)
INNER JOIN {$db->TBL->users_uploads} u USING (upload_id)
LEFT JOIN ".POSTS_TABLE." p USING (post_id)
LEFT JOIN ".POSTS_ARCHIVE_TABLE." pa USING (post_id)
WHERE a.attach_id = {$attach_id}");
if (empty($attachment)) {
	cpg_error($lang['Error_no_attachment'], 404);
}

//
// get forum_id for attachment authorization or private message authorization
//
/*
if ($attachment['forum_id']) {
	$is_auth = array();
	$is_auth = \Dragonfly\Forums\Auth::all($attachment['forum_id']);
	$authorised = $is_auth['auth_download'];
} else {
	$authorised = (can_admin($module_name) || $userinfo['user_id'] == $attachment['user_id_1']);
}
if (!$authorised) {
	cpg_error($lang['Sorry_auth_view_attach'], 403);
}
*/
//
// Get Information on currently allowed Extensions
//
$download_mode = false;
$result = $db->query("SELECT e.extension, g.download_mode
	FROM {$db->TBL->bbextension_groups} g, {$db->TBL->bbextensions} e
	WHERE (g.allow_group = 1)
	  AND (g.group_id = e.group_id)");
//	  AND LOWER(e.extension)=LOWER({$attachment['extension']})
while ($row = $result->fetch_row()) {
	if (strtolower(trim($row[0])) == $attachment['extension']) {
		$download_mode = (int)$row[1];
		break;
	}
}

//
// disallowed ?
//
if (false === $download_mode && !can_admin($module_name)) {
	cpg_error(sprintf($lang['Extension_disabled_after_posting'], $attachment['extension']), 403);
}

if ($thumbnail) {
	$attachment['file'] = preg_replace('#(^|/)([^/]+)$#D', '$1thumbs/t_$2', $attachment['file']);
} else {
	attachment_inc_download_count($attach_id);
}

//
// Determine the 'presenting'-method
//
if (2 == $download_mode) {
	URL::redirect('/'.$attachment['file']);
} else {
	// Send file to browser
/*
	header('Pragma: public');
	header('Content-Transfer-Encoding: none');
	header("Expires: 0"); // set expiration time
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
*/
	$filename = $attachment['file'];
	if (!is_file(amod_realpath($filename))) {
		cpg_error($template->L10N['Error_no_attachment'], 404);
	}
	if (!($fp = fopen($filename, 'rb'))) {
		cpg_error('Could not open file for sending');
	}
	\Dragonfly::ob_clean();
	// Correct the mime type - we force application/octet-stream for all files, except images
	if (false === stripos($attachment['mimetype'], 'image')) {
		$attachment['mimetype'] = 'application/octet-stream';
	}
	if ($size = filesize($filename)) {
		header("Content-length: {$size}");
	}
	// Send out the Headers
	\Poodle\HTTP\Headers::setContentType($attachment['mimetype'], array('name'=>$attachment['name']));
	\Poodle\HTTP\Headers::setContentDisposition('inline', array('filename'=>$attachment['name']));
	fpassthru($fp);
	fclose($fp);
}
exit;
