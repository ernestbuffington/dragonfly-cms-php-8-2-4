<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2006 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /cvs/html/install/sql/data/forums.php,v $
	$Revision: 1.4 $
	$Author: nanocaiordo $
	$Date: 2007/07/15 14:46:09 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$records['bbconfig']['compare'] = DF_DATA_EXIST_LEVEL1;
$records['bbconfig']['query'] = 'config_name';
$records['bbconfig']['content'] = array(
	'allow_html' => 0,
	'allow_html_tags' => "'b,i,u,pre'",
	'allow_bbcode' => 1,
	'allow_smilies' => 1,
	'allow_sig' => 1,
	'allow_namechange' => 0,
	'allow_theme_create' => 0,
	'allow_avatar_local' => 1,
	'allow_avatar_remote' => 1,
	'allow_avatar_upload' => 0,
	'override_user_style' => 1,
	'posts_per_page' => 15,
	'topics_per_page' => 50,
	'hot_threshold' => 25,
	'max_poll_options' => 10,
	'max_inbox_privmsgs' => 100,
	'max_sentbox_privmsgs' => 100,
	'max_savebox_privmsgs' => 100,
	'board_email_sig' => "'Thanks, Webmaster@MySite.com'",
	'require_activation' => 0,
	'flood_interval' => 15,
	'board_email_form' => 0,
	'default_style' => 1,
	'default_dateformat' => "'D M d, Y g:i a'",
	'board_timezone' => 0,
	'prune_enable' => 0,
	'coppa_fax' => "''",
	'coppa_mail' => "''",
	'board_startdate' => gmtime(),
	'default_lang' => "'english'",
	'record_online_users' => 2,
	'record_online_date' => 1034668530,
	'version' => "'.0.0'",
	'enable_confirm' => 0,
	'sendmail_fix' => 0,
	'ropm_quick_reply' => 1,
	'ropm_quick_reply_bbc' => 1
);

$records['bbgroups']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbgroups']['content'] = array(
	array(1, 'Anonymous', 'Personal User', 0, 1)
);

$records['bbranks']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbranks']['content'] = array(
	array('Site Admin', -1, -1, 1, 'images/ranks/stars-6.png'),
	array('Newbie', 1, 0, 0, 'images/ranks/stars-1.png')
);

$records['bbsmilies']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbsmilies']['content'] = array(
	array(':D', 'icon_biggrin.gif', 'Very Happy', 1),
	array(':-D', 'icon_biggrin.gif', 'Very Happy', 2),
	array(':grin:', 'icon_biggrin.gif', 'Very Happy', 3),
	array(':)', 'icon_smile.gif', 'Smile', 4),
	array(':-)', 'icon_smile.gif', 'Smile', 5),
	array(':smile:', 'icon_smile.gif', 'Smile', 6),
	array(':(', 'icon_sad.gif', 'Sad', 7),
	array(':-(', 'icon_sad.gif', 'Sad', 8),
	array(':sad:', 'icon_sad.gif', 'Sad', 9),
	array(':o', 'icon_surprised.gif', 'Surprised', 10),
	array(':-o', 'icon_surprised.gif', 'Surprised', 11),
	array(':eek:', 'icon_surprised.gif', 'Surprised', 12),
	array('8O', 'icon_eek.gif', 'Shocked', 13),
	array('8-O', 'icon_eek.gif', 'Shocked', 14),
	array(':shock:', 'icon_eek.gif', 'Shocked', 15),
	array(':?', 'icon_confused.gif', 'Confused', 16),
	array(':-?', 'icon_confused.gif', 'Confused', 17),
	array(':???:', 'icon_confused.gif', 'Confused', 18),
	array('8)', 'icon_cool.gif', 'Cool', 19),
	array('8-)', 'icon_cool.gif', 'Cool', 20),
	array(':cool:', 'icon_cool.gif', 'Cool', 21),
	array(':lol:', 'icon_lol.gif', 'Laughing', 22),
	array(':x', 'icon_mad.gif', 'Mad', 23),
	array(':-x', 'icon_mad.gif', 'Mad', 24),
	array(':mad:', 'icon_mad.gif', 'Mad', 25),
	array(':P', 'icon_razz.gif', 'Razz', 26),
	array(':-P', 'icon_razz.gif', 'Razz', 27),
	array(':razz:', 'icon_razz.gif', 'Razz', 28),
	array(':oops:', 'icon_redface.gif', 'Embarassed', 29),
	array(':cry:', 'icon_cry.gif', 'Crying or Very sad', 30),
	array(':evil:', 'icon_evil.gif', 'Evil or Very Mad', 31),
	array(':twisted:', 'icon_twisted.gif', 'Twisted Evil', 32),
	array(':roll:', 'icon_rolleyes.gif', 'Rolling Eyes', 33),
	array(':wink:', 'icon_wink.gif', 'Wink', 34),
	array(';)', 'icon_wink.gif', 'Wink', 35),
	array(';-)', 'icon_wink.gif', 'Wink', 36),
	array(':!:', 'icon_exclaim.gif', 'Exclamation', 37),
	array(':?:', 'icon_question.gif', 'Question', 38),
	array(':idea:', 'icon_idea.gif', 'Idea', 39),
	array(':arrow:', 'icon_arrow.gif', 'Arrow', 40),
	array(':|', 'icon_neutral.gif', 'Neutral', 41),
	array(':-|', 'icon_neutral.gif', 'Neutral', 42),
	array(':neutral:', 'icon_neutral.gif', 'Neutral', 43),
	array(':mrgreen:', 'icon_mrgreen.gif', 'Mr. Green', 44)
);

$records['bbthemes']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbthemes']['content'] = array(
	array('subSilver', 'subSilver', 'subSilver.css', '', '0E3259', 000000, 006699, '5493B4', '', 'DD6900', 'EFEFEF', 'DEE3E7', 'D1D7DC', '', '', '', '98AAB1', 006699, 'FFFFFF', 'cellpic1.gif', 'cellpic3.gif', 'cellpic2.jpg', 'FAFAFA', 'FFFFFF', '', 'row1', 'row2', '', 'Verdana, Arial, Helvetica, sans-serif', 'Trebuchet MS', 'Courier, \\\'Courier New\\\', sans-serif', 10, 11, 12, 444444, 006600, 'FFA34F', '', '', '', NULL, NULL)
);

$records['bbthemes_name']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbthemes_name']['content'] = array(
	array('The lightest row colour', 'The medium row color', 'The darkest row colour', '', '', '', 'Border round the whole page', 'Outer table border', 'Inner table border', 'Silver gradient picture', 'Blue gradient picture', 'Fade-out gradient on index', 'Background for quote boxes', 'All white areas', '', 'Background for topic posts', '2nd background for topic posts', '', 'Main fonts', 'Additional topic title font', 'Form fonts', 'Smallest font size', 'Medium font size', 'Normal font size (post body etc)', 'Quote & copyright text', 'Code text colour', 'Main table header text colour', '', '', '')
);

$records['bbtopic_icons']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbtopic_icons']['content'] = array(
	array(-1, 'images/icons/misc/asterix.gif', 'asterix'),
	array(-1, 'images/icons/misc/arrow_bold_ltr.gif', 'Arrow ltr'),
	array(-1, 'images/icons/smile/exclaim.gif', 'Exclamation'),
	array(-1, 'images/icons/smile/question.gif', 'Questionmark'),
	array(-1, 'images/icons/smile/idea.gif', 'Idea')
);

$records['bbuser_group']['compare'] = DF_DATA_MUST_BE_SAME;
$records['bbuser_group']['query'] = 'group_id, user_id';
$records['bbuser_group']['content'] = array(
	1 => array(1, 0)
);

$records['bbattachments_config']['compare'] = DF_DATA_EXIST_LEVEL1;
$records['bbattachments_config']['query'] = 'config_name';
$records['bbattachments_config']['content'] = array(
	'upload_dir' => "'uploads/forums'",
	'upload_img' => "'images/icons/icon_disk.gif'",
	'topic_icon' => "'images/icons/icon_clip.gif'",
	'display_order' => 0,
	'max_filesize' => 262144,
	'attachment_quota' => 52428800,
	'max_filesize_pm' => 262144,
	'max_attachments' => 3,
	'max_attachments_pm' => 1,
	'disable_mod' => 0,
	'allow_pm_attach' => 1,
	'attachment_topic_review' => 0,
	'allow_ftp_upload' => 0,
	'show_apcp' => 0,
	'attach_version' => "'2.3.9'",
	'default_upload_quota' => 0,
	'default_pm_quota' => 0,
	'ftp_server' => "''",
	'ftp_path' => "''",
	'download_path' => "''",
	'ftp_user' => "''",
	'ftp_pass' => "''",
	'ftp_pasv_mode' => 1,
	'img_display_inlined' => 1,
	'img_max_width' => 0,
	'img_max_height' => 0,
	'img_link_width' => 0,
	'img_link_height' => 0,
	'img_create_thumbnail' => 0,
	'img_min_thumb_filesize' => 12000,
	'img_imagick' => "''",
	'use_gd2' => 0,
	'wma_autoplay' => 0,
	'flash_autoplay' => 0
);

$records['bbforbidden_extensions']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbforbidden_extensions']['content'] = array(
	array('php'),
	array('php3'),
	array('phtml4'),
	array('pl'),
	array('asp'),
	array('cgi')
);

$records['bbextension_groups']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbextension_groups']['content'] = array(
	array('Images',1,1,1,'',0,''),
	array('Archives',0,1,1,'',0,''),
	array('Plain Text',0,0,1,'',0,''),
	array('Documents',0,0,1,'',0,''),
	array('Real Media',0,0,2,'',0,''),
	array('Streams',2,0,1,'',0,''),
	array('Flash Files',3,0,1,'',0,'')
);

$records['bbextensions']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbextensions']['content'] = array(
	array(1, 'gif', ''),
	array(1, 'png', ''),
	array(1, 'jpeg', ''),
	array(1, 'jpg', ''),
	array(1, 'tif', ''),
	array(1, 'tga', ''),
	array(2, 'ace', ''),
	array(2, 'bz2', ''),
	array(2, 'gtar', ''),
	array(2, 'gz', ''),
	array(2, 'tar', ''),
	array(2, 'tbz', ''),
	array(2, 'tgz', ''),
	array(2, 'rar', ''),
	array(2, 'zip', ''),
	array(3,'txt', ''),
	array(3,'c', ''),
	array(3,'h', ''),
	array(3,'cpp', ''),
	array(3,'hpp', ''),
	array(3,'diz', ''),
	array(4,'xls', ''),
	array(4,'doc', ''),
	array(4,'dot', ''),
	array(4,'pdf', ''),
	array(4,'ai', ''),
	array(4,'ps', ''),
	array(4,'ppt', ''),
	array(5,'rm', ''),
	array(6,'asx', ''),
	array(6,'pls', ''),
	array(6,'wma', ''),
	array(7,'swf', '')
);

$records['bbquota_limits']['compare'] = DF_DATA_CHECK_ONLY;
$records['bbquota_limits']['content'] = array(
	array('Low', 262144),
	array('Medium', 2097152),
	array('High', 5242880)
);
