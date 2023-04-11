<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

require(__DIR__ . '/include/load.inc');

$pid   = $_GET->uint('pid');
$album = $_GET->uint('album');
$pos   = $_GET->uint('pos');
$meta  = $_GET['meta'];

$row = \Dragonfly::getKernel()->SQL->uFetchAssoc("SELECT
	*
FROM {$CONFIG['TABLE_PICTURES']} AS p
INNER JOIN {$CONFIG['TABLE_ALBUMS']} USING (aid)
WHERE pid = {$pid}");
if (!$row) {
	cpg_error(NON_EXIST_AP, 404);
}

global $CONFIG;

if (isset($_GET['data'])) {
	$data = json_decode(\Poodle\Base64::urlDecode($_GET['data']), true);
	if (!$data || !is_array($data)) {
		cpg_error('ECARD_LINK_CORRUPT');
	}
	if ($CONFIG['make_intermediate'] && max($row['pwidth'], $row['pheight']) > $CONFIG['picture_width']) {
		$n_picname = get_pic_url($row, 'normal');
	} else {
		$n_picname = get_pic_url($row, 'fullsize');
	}
	// Remove HTML tags as we can't trust what we receive
	foreach ($data as $key => $value) {
		$data[$key] = htmlprepare($value);
	}
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->ecard = array(
		'href_ecard' => null,
		'pic_url' => $n_picname,
		'greetings' => $data['g'],
		'message' => nl2br(\Dragonfly\Smilies::parse($data['m'])),
		'sender_email' => $data['se'],
		'sender_name' => $data['sn'],
	);
	$OUT->display('coppermine/ecard');
	return;
}

if (!$USER_DATA['can_send_ecards'] || !in_array($row['visibility'], explode(',', '0,'.$USER_DATA['GROUPS']))) {
	cpg_error(ACCESS_DENIED, 403);
}

$IDENTITY = \Dragonfly::getKernel()->IDENTITY;
if ($IDENTITY->isMember()) {
	$sender_name = $IDENTITY->nickname;
	$sender_email = $IDENTITY->email;
} else {
	$sender_name = (isset($USER['name']) ? $USER['name'] : '');
	$sender_email = (isset($USER['email']) ? $USER['email'] : '');
}

$recipient_name = $recipient_email = $greetings = $message = $sender_email_warning = $recipient_email_warning = '';

if ('POST' === $_SERVER['REQUEST_METHOD']) {

	$sender_name = $_POST['sender_name'];
	$sender_email = $_POST['sender_email'];
	$recipient_name = $_POST['recipient_name'];
	$recipient_email = $_POST['recipient_email'];
	$greetings = $_POST['greetings'];
	$message = $_POST['message'];

	// Check supplied email address
	$valid_sender_email = $valid_recipient_email = false;
	try {
		$valid_sender_email = \Poodle\Input::validateEmail($sender_email);
	} catch (\Exception $e) {}
	try {
		$valid_recipient_email = \Poodle\Input::validateEmail($recipient_email);
	} catch (\Exception $e) {}
	if (!$valid_sender_email) {
		$sender_email_warning = INVALID_EMAIL;
	}
	if (!$valid_recipient_email) {
		$recipient_email_warning = INVALID_EMAIL;
	}

	// Create and send the e-card
	if (\Dragonfly\Output\Captcha::validate($_POST) && $valid_sender_email && $valid_recipient_email) {
		$MAIL = \Dragonfly\Email::getPoodleMailer();
		$MAIL->setFrom($sender_email, $sender_name);
		$MAIL->addTo($recipient_email, $recipient_name);
		$MAIL->subject = sprintf(E_ECARD_TITLE, $sender_name);

		$encoded_data = \Poodle\Base64::urlEncode(\Dragonfly::dataToJSON(array(
			'rn' => $_POST['recipient_name'],
			'sn' => $_POST['sender_name'],
			'se' => $_POST['sender_email'],
			'g' => $greetings,
			'm' => $message,
		)));

		$MAIL->ecard = array(
			'href_ecard' => URL::index("&file=ecard&pid={$pid}&data={$encoded_data}",false,1),
			'pic_url' => 'cid:the-image',
			'greetings' => $greetings,
			'message' => nl2br(\Dragonfly\Smilies::parse($message, \Dragonfly::getKernel()->CFG->global->nukeurl)),
			'sender_email' => $sender_email,
			'sender_name' => $sender_name,
		);
		$MAIL->body = $MAIL->toString('coppermine/ecard');

		if ($CONFIG['make_intermediate'] && max($row['pwidth'], $row['pheight']) > $CONFIG['picture_width']) {
			$image = $row['filepath'].$CONFIG['normal_pfx'].$row['filename'];
		} else {
			$image = $row['filepath'].$row['filename'];
		}
		$ext = strtolower(substr($row['filename'],-3));
		if ($ext == 'gif') {
			$type = 'image/gif';
		} else if ($ext == 'png') {
			$type = 'image/png';
		} else {
			$type = 'image/jpeg';
			$ext = 'jpeg';
		}
		$MAIL->addEmbeddedImage($image, 'the-image', "ecard.{$ext}", 'base64', $type);

		if (!$MAIL->send()) {
			cpg_error($MAIL->error);
		}

		\Poodle\Notify::success(SEND_SUCCESS);
		$CPG = \Coppermine::getInstance();
		\URL::redirect($CPG->buildUrl('displayimage', array('album' => $album, 'cat' => $_GET->uint('cat'), 'meta' => $meta, 'pos' => $pos)));
	}
}

pageheader(E_TITLE);
$OUT = \Dragonfly::getKernel()->OUT;
$OUT->ecard = array(
	'thumb_pic_url' => get_pic_url($row, 'thumb'),
	'sender_name' => $sender_name,
	'sender_email' => $sender_email,
	'sender_email_warning' => $sender_email_warning,
	'recipient_name' => $recipient_name,
	'recipient_email' => $recipient_email,
	'recipient_email_warning' => $recipient_email_warning,
	'greetings' => $greetings,
	'message' => $message,
);
$OUT->display('coppermine/ecard-form');
pagefooter();
