<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Forums/cpg_inst.php,v $
  $Revision: 1.9 $
  $Author: djmaze $
  $Date: 2006/01/08 03:09:50 $
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class Forums {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;
// class constructor
	function Forums() {
		$this->radmin = true;
		$this->version = '1.0.0';
		$this->modname = 'CPG-BB';
		$this->description = 'CPG Bulletin Board by CPG-Nuke Dev Team and based on phpBB 2.0.x which is released under the GNU GPL';
		$this->author = 'CPG-Nuke Dev Team';
		$this->website = 'dragonflycms.org';
		$this->base = basename(dirname(__FILE__));
		$this->prefix = ($this->base == 'Forums') ? 'bb' : strtolower($this->base).'_';
		$this->dbtables = array($this->prefix.'auth_access',
			$this->prefix.'categories', $this->prefix.'forum_prune', $this->prefix.'forums',
			$this->prefix.'posts', $this->prefix.'posts_text',
			$this->prefix.'search_wordlist', $this->prefix.'search_wordmatch',
			$this->prefix.'topic_icons', $this->prefix.'topics', $this->prefix.'topics_watch',
			$this->prefix.'vote_desc', $this->prefix.'vote_results', $this->prefix.'vote_voters',
			$this->prefix.'words', $this->prefix.'attachments', $this->prefix.'attachments_desc');
		if ($this->base == 'Forums') {
			$this->dbtables[] = $this->prefix.'config';
			$this->dbtables[] = $this->prefix.'disallow';
			$this->dbtables[] = $this->prefix.'themes';
			$this->dbtables[] = $this->prefix.'themes_name';
			$this->dbtables[] = $this->prefix.'attachments_config';
			$this->dbtables[] = $this->prefix.'extension_groups';
			$this->dbtables[] = $this->prefix.'extensions';
			$this->dbtables[] = $this->prefix.'forbidden_extensions';
			$this->dbtables[] = $this->prefix.'attach_quota';
			$this->dbtables[] = $this->prefix.'quota_limits';
		}
	}

// module installer
	function install() {
		global $installer;
		$installer->add_query('CREATE', $this->prefix.'auth_access', '
  group_id mediumint(8) NOT NULL default "0",
  forum_id smallint(5) unsigned NOT NULL default "0",
  auth_view tinyint(1) NOT NULL default "0",
  auth_read tinyint(1) NOT NULL default "0",
  auth_post tinyint(1) NOT NULL default "0",
  auth_reply tinyint(1) NOT NULL default "0",
  auth_edit tinyint(1) NOT NULL default "0",
  auth_delete tinyint(1) NOT NULL default "0",
  auth_sticky tinyint(1) NOT NULL default "0",
  auth_announce tinyint(1) NOT NULL default "0",
  auth_vote tinyint(1) NOT NULL default "0",
  auth_pollcreate tinyint(1) NOT NULL default "0",
  auth_attachments tinyint(1) NOT NULL default "0",
  auth_mod tinyint(1) NOT NULL default "0",
  auth_download TINYINT(1) DEFAULT "0" NOT NULL,
  KEY group_id (group_id),
  KEY forum_id (forum_id)', $this->prefix.'auth_access');

		$installer->add_query('CREATE', $this->prefix.'categories', '
  cat_id mediumint(8) unsigned NOT NULL auto_increment,
  cat_title varchar(100) default NULL,
  cat_order mediumint(8) unsigned NOT NULL default "0",
  PRIMARY KEY (cat_id),
  KEY cat_order (cat_order)', $this->prefix.'categories');

		$installer->add_query('CREATE', $this->prefix.'forum_prune', '
  prune_id mediumint(8) unsigned NOT NULL auto_increment,
  forum_id smallint(5) unsigned NOT NULL default "0",
  prune_days tinyint(4) unsigned NOT NULL default "0",
  prune_freq tinyint(4) unsigned NOT NULL default "0",
  PRIMARY KEY (prune_id),
  KEY forum_id (forum_id)', $this->prefix.'forum_prune');
		$installer->add_query('CREATE', $this->prefix.'forums', '
  forum_id smallint(5) unsigned NOT NULL auto_increment,
  cat_id mediumint(8) unsigned NOT NULL default "0",
  parent_id SMALLINT(5) UNSIGNED NOT NULL,
  forum_name varchar(150) default NULL,
  forum_desc text,
  forum_status tinyint(4) NOT NULL default "0",
  forum_order mediumint(8) unsigned NOT NULL default "1",
  forum_posts mediumint(8) unsigned NOT NULL default "0",
  forum_topics mediumint(8) unsigned NOT NULL default "0",
  forum_last_post_id mediumint(8) unsigned NOT NULL default "0",
  forum_type TINYINT(1) UNSIGNED NOT NULL,
  forum_link VARCHAR(255),
  prune_next int(11) default NULL,
  prune_enable tinyint(1) NOT NULL default "1",
  auth_view tinyint(2) NOT NULL default "0",
  auth_read tinyint(2) NOT NULL default "0",
  auth_post tinyint(2) NOT NULL default "0",
  auth_reply tinyint(2) NOT NULL default "0",
  auth_edit tinyint(2) NOT NULL default "0",
  auth_delete tinyint(2) NOT NULL default "0",
  auth_sticky tinyint(2) NOT NULL default "0",
  auth_announce tinyint(2) NOT NULL default "0",
  auth_vote tinyint(2) NOT NULL default "0",
  auth_pollcreate tinyint(2) NOT NULL default "0",
  auth_attachments tinyint(2) NOT NULL default "0",
  auth_download TINYINT(2) DEFAULT "0" NOT NULL,
  PRIMARY KEY (forum_id),
  KEY forums_order (forum_order),
  KEY cat_id (cat_id),
  KEY forum_last_post_id (forum_last_post_id)', $this->prefix.'forums');

		$installer->add_query('CREATE', $this->prefix.'posts', '
  post_id mediumint(8) unsigned NOT NULL auto_increment,
  topic_id mediumint(8) unsigned NOT NULL default "0",
  forum_id smallint(5) unsigned NOT NULL default "0",
  poster_id mediumint(8) NOT NULL default "0",
  post_time int(11) NOT NULL default "0",
  poster_ip varchar(16) binary NOT NULL default "",
  post_username varchar(25) default NULL,
  enable_bbcode tinyint(1) NOT NULL default "1",
  enable_html tinyint(1) NOT NULL default "0",
  enable_smilies tinyint(1) NOT NULL default "1",
  enable_sig tinyint(1) NOT NULL default "1",
  post_edit_time int(11) default NULL,
  post_edit_count smallint(5) unsigned NOT NULL default "0",
  post_attachment TINYINT(1) DEFAULT "0" NOT NULL,
  PRIMARY KEY (post_id),
  KEY forum_id (forum_id),
  KEY topic_id (topic_id),
  KEY poster_id (poster_id),
  KEY post_time (post_time),
  KEY topic_n_id (topic_id,post_id)', $this->prefix.'posts');
		$installer->add_query('CREATE', $this->prefix.'posts_text', '
  post_id mediumint(8) unsigned NOT NULL default "0",
  post_subject varchar(60) default NULL,
  post_text text,
  PRIMARY KEY (post_id)', $this->prefix.'posts_text');

		$installer->add_query('CREATE', $this->prefix.'search_wordlist', '
  word_text varchar(50) binary NOT NULL default "",
  word_id mediumint(8) unsigned NOT NULL auto_increment,
  word_common tinyint(1) unsigned NOT NULL default "0",
  PRIMARY KEY (word_text),
  KEY word_id (word_id)', $this->prefix.'search_wordlist');
		$installer->add_query('CREATE', $this->prefix.'search_wordmatch', '
  post_id mediumint(8) unsigned NOT NULL default "0",
  word_id mediumint(8) unsigned NOT NULL default "0",
  title_match tinyint(1) NOT NULL default "0",
  KEY word_id (word_id)', $this->prefix.'search_wordmatch');

		if ($this->base == 'Forums') {
			$installer->add_query('CREATE', $this->prefix.'config', '
  config_name varchar(255) NOT NULL default "",
  config_value varchar(255) NOT NULL default "",
  PRIMARY KEY  (config_name)', $this->prefix.'config');
			$installer->add_query('CREATE', $this->prefix.'disallow', '
  disallow_id mediumint(8) unsigned NOT NULL auto_increment,
  disallow_username varchar(25) default NULL,
  PRIMARY KEY (disallow_id)', $this->prefix.'disallow');
			$installer->add_query('CREATE', $this->prefix.'themes', '
  themes_id mediumint(8) unsigned NOT NULL auto_increment,
  template_name varchar(30) NOT NULL default "",
  style_name varchar(30) NOT NULL default "",
  head_stylesheet varchar(100) default NULL,
  body_background varchar(100) default NULL,
  body_bgcolor varchar(6) default NULL,
  body_text varchar(6) default NULL,
  body_link varchar(6) default NULL,
  body_vlink varchar(6) default NULL,
  body_alink varchar(6) default NULL,
  body_hlink varchar(6) default NULL,
  tr_color1 varchar(6) default NULL,
  tr_color2 varchar(6) default NULL,
  tr_color3 varchar(6) default NULL,
  tr_class1 varchar(25) default NULL,
  tr_class2 varchar(25) default NULL,
  tr_class3 varchar(25) default NULL,
  th_color1 varchar(6) default NULL,
  th_color2 varchar(6) default NULL,
  th_color3 varchar(6) default NULL,
  th_class1 varchar(25) default NULL,
  th_class2 varchar(25) default NULL,
  th_class3 varchar(25) default NULL,
  td_color1 varchar(6) default NULL,
  td_color2 varchar(6) default NULL,
  td_color3 varchar(6) default NULL,
  td_class1 varchar(25) default NULL,
  td_class2 varchar(25) default NULL,
  td_class3 varchar(25) default NULL,
  fontface1 varchar(50) default NULL,
  fontface2 varchar(50) default NULL,
  fontface3 varchar(50) default NULL,
  fontsize1 tinyint(4) default NULL,
  fontsize2 tinyint(4) default NULL,
  fontsize3 tinyint(4) default NULL,
  fontcolor1 varchar(6) default NULL,
  fontcolor2 varchar(6) default NULL,
  fontcolor3 varchar(6) default NULL,
  span_class1 varchar(25) default NULL,
  span_class2 varchar(25) default NULL,
  span_class3 varchar(25) default NULL,
  img_size_poll smallint(5) unsigned default NULL,
  img_size_privmsg smallint(5) unsigned default NULL,
  PRIMARY KEY (themes_id)', $this->prefix.'themes');
		$installer->add_query('CREATE', $this->prefix.'themes_name', '
  themes_id smallint(5) unsigned NOT NULL default "0",
  tr_color1_name char(50) default NULL,
  tr_color2_name char(50) default NULL,
  tr_color3_name char(50) default NULL,
  tr_class1_name char(50) default NULL,
  tr_class2_name char(50) default NULL,
  tr_class3_name char(50) default NULL,
  th_color1_name char(50) default NULL,
  th_color2_name char(50) default NULL,
  th_color3_name char(50) default NULL,
  th_class1_name char(50) default NULL,
  th_class2_name char(50) default NULL,
  th_class3_name char(50) default NULL,
  td_color1_name char(50) default NULL,
  td_color2_name char(50) default NULL,
  td_color3_name char(50) default NULL,
  td_class1_name char(50) default NULL,
  td_class2_name char(50) default NULL,
  td_class3_name char(50) default NULL,
  fontface1_name char(50) default NULL,
  fontface2_name char(50) default NULL,
  fontface3_name char(50) default NULL,
  fontsize1_name char(50) default NULL,
  fontsize2_name char(50) default NULL,
  fontsize3_name char(50) default NULL,
  fontcolor1_name char(50) default NULL,
  fontcolor2_name char(50) default NULL,
  fontcolor3_name char(50) default NULL,
  span_class1_name char(50) default NULL,
  span_class2_name char(50) default NULL,
  span_class3_name char(50) default NULL,
  PRIMARY KEY (themes_id)', $this->prefix.'themes_name');
		$installer->add_query('CREATE', $this->prefix.'words', '
  word_id mediumint(8) unsigned NOT NULL auto_increment,
  word char(100) NOT NULL default "",
  replacement char(100) NOT NULL default "",
  PRIMARY KEY (word_id)', $this->prefix.'words');

		$installer->add_query('INSERT', $this->prefix.'config', "'allow_html', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_html_tags', 'b,i,u,pre'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_bbcode', '1'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_smilies', '1'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_sig', '1'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_namechange', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_theme_create', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_avatar_local', '1'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_avatar_remote', '1'");
		$installer->add_query('INSERT', $this->prefix.'config', "'allow_avatar_upload', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'override_user_style', '1'");
		$installer->add_query('INSERT', $this->prefix.'config', "'posts_per_page', '15'");
		$installer->add_query('INSERT', $this->prefix.'config', "'topics_per_page', '50'");
		$installer->add_query('INSERT', $this->prefix.'config', "'hot_threshold', '25'");
		$installer->add_query('INSERT', $this->prefix.'config', "'max_poll_options', '10'");
		$installer->add_query('INSERT', $this->prefix.'config', "'max_inbox_privmsgs', '100'");
		$installer->add_query('INSERT', $this->prefix.'config', "'max_sentbox_privmsgs', '100'");
		$installer->add_query('INSERT', $this->prefix.'config', "'max_savebox_privmsgs', '100'");
		$installer->add_query('INSERT', $this->prefix.'config', "'board_email_sig', 'Thanks, Webmaster@MySite.com'");
		$installer->add_query('INSERT', $this->prefix.'config', "'require_activation', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'flood_interval', '15'");
		$installer->add_query('INSERT', $this->prefix.'config', "'board_email_form', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'default_style', '1'");
		$installer->add_query('INSERT', $this->prefix.'config', "'default_dateformat', 'D M d, Y g:i a'");
		$installer->add_query('INSERT', $this->prefix.'config', "'board_timezone', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'prune_enable', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'coppa_fax', ''");
		$installer->add_query('INSERT', $this->prefix.'config', "'coppa_mail', ''");
		$installer->add_query('INSERT', $this->prefix.'config', "'board_startdate', '".gmtime()."'");
		$installer->add_query('INSERT', $this->prefix.'config', "'default_lang', 'english'");
		$installer->add_query('INSERT', $this->prefix.'config', "'record_online_users', '2'");
		$installer->add_query('INSERT', $this->prefix.'config', "'record_online_date', '1034668530'");
		$installer->add_query('INSERT', $this->prefix.'config', "'version', '.0.0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'enable_confirm', '0'");
		$installer->add_query('INSERT', $this->prefix.'config', "'sendmail_fix', '0'");

		$installer->add_query('INSERT', $this->prefix.'themes', '"1", "subSilver", "subSilver", "subSilver.css", "", "0E3259", "000000", "006699", "5493B4", "", "DD6900", "EFEFEF", "DEE3E7", "D1D7DC", "", "", "", "98AAB1", "006699", "FFFFFF", "cellpic1.gif", "cellpic3.gif", "cellpic2.jpg", "FAFAFA", "FFFFFF", "", "row1", "row2", "", "Verdana, Arial, Helvetica, sans-serif", "Trebuchet MS", "Courier, \'Courier New\', sans-serif", "10", "11", "12", "444444", "006600", "FFA34F", "", "", "", NULL, NULL');

		$installer->add_query('INSERT', $this->prefix.'themes_name', '"1", "The lightest row colour", "The medium row color", "The darkest row colour", "", "", "", "Border round the whole page", "Outer table border", "Inner table border", "Silver gradient picture", "Blue gradient picture", "Fade-out gradient on index", "Background for quote boxes", "All white areas", "", "Background for topic posts", "2nd background for topic posts", "", "Main fonts", "Additional topic title font", "Form fonts", "Smallest font size", "Medium font size", "Normal font size (post body etc)", "Quote & copyright text", "Code text colour", "Main table header text colour", "", "", ""');
		} // end ($this->base == 'Forums')

		$installer->add_query('CREATE', $this->prefix.'topic_icons', '
   icon_id smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
   forum_id smallint(5) NOT NULL,
   icon_url varchar(100) NOT NULL,
   icon_name varchar(25) NOT NULL,
   PRIMARY KEY (icon_id),
   KEY (forum_id)', $this->prefix.'topic_icons');
		$installer->add_query('CREATE', $this->prefix.'topics', '
  topic_id mediumint(8) unsigned NOT NULL auto_increment,
  forum_id smallint(8) unsigned NOT NULL default "0",
  topic_title char(60) NOT NULL default "",
  topic_poster mediumint(8) NOT NULL default "0",
  topic_time int(11) NOT NULL default "0",
  topic_views mediumint(8) unsigned NOT NULL default "0",
  topic_replies mediumint(8) unsigned NOT NULL default "0",
  topic_status tinyint(3) NOT NULL default "0",
  topic_vote tinyint(1) NOT NULL default "0",
  topic_type tinyint(3) NOT NULL default "0",
  topic_last_post_id mediumint(8) unsigned NOT NULL default "0",
  topic_first_post_id mediumint(8) unsigned NOT NULL default "0",
  topic_moved_id mediumint(8) unsigned NOT NULL default "0",
  topic_attachment TINYINT(1) DEFAULT "0" NOT NULL,
  icon_id smallint(5),
  PRIMARY KEY (topic_id),
  KEY forum_id (forum_id),
  KEY topic_moved_id (topic_moved_id),
  KEY topic_status (topic_status),
  KEY topic_last_post_id (topic_last_post_id),
  KEY topic_first_post_id (topic_first_post_id),
  KEY topic_type (topic_type)', $this->prefix.'topics');
		$installer->add_query('CREATE', $this->prefix.'topics_watch', '
  topic_id mediumint(8) unsigned NOT NULL default "0",
  user_id mediumint(8) NOT NULL default "0",
  notify_status tinyint(1) NOT NULL default "0",
  KEY topic_id (topic_id),
  KEY user_id (user_id),
  KEY notify_status (notify_status)', $this->prefix.'topics_watch');

		$installer->add_query('CREATE', $this->prefix.'vote_desc', '
  vote_id mediumint(8) unsigned NOT NULL auto_increment,
  topic_id mediumint(8) unsigned NOT NULL default "0",
  vote_text text NOT NULL,
  vote_start int(11) NOT NULL default "0",
  vote_length int(11) NOT NULL default "0",
  PRIMARY KEY (vote_id),
  KEY topic_id (topic_id)', $this->prefix.'vote_desc');
		$installer->add_query('CREATE', $this->prefix.'vote_results', '
  vote_id mediumint(8) unsigned NOT NULL default "0",
  vote_option_id tinyint(4) unsigned NOT NULL default "0",
  vote_option_text varchar(255) NOT NULL default "",
  vote_result int(11) NOT NULL default "0",
  KEY vote_option_id (vote_option_id),
  KEY vote_id (vote_id)', $this->prefix.'vote_results');
		$installer->add_query('CREATE', $this->prefix.'vote_voters', '
  vote_id mediumint(8) unsigned NOT NULL default "0",
  vote_user_id mediumint(8) NOT NULL default "0",
  vote_user_ip varchar(16) binary NOT NULL default "",
  KEY vote_id (vote_id),
  KEY vote_user_id (vote_user_id),
  KEY vote_user_ip (vote_user_ip)', $this->prefix.'vote_voters');

		$installer->add_query('INSERT', $this->prefix.'topic_icons', '"1", "-1", "images/icons/misc/asterix.gif", "asterix"');
		$installer->add_query('INSERT', $this->prefix.'topic_icons', '"2", "-1", "images/icons/misc/arrow_bold_ltr.gif", "Arrow ltr"');
		$installer->add_query('INSERT', $this->prefix.'topic_icons', '"3", "-1", "images/icons/smile/exclaim.gif", "Exclamation"');
		$installer->add_query('INSERT', $this->prefix.'topic_icons', '"4", "-1", "images/icons/smile/question.gif", "Questionmark"');
		$installer->add_query('INSERT', $this->prefix.'topic_icons', '"5", "-1", "images/icons/smile/idea.gif", "Idea"');
		$this->attach_mod();
		return true;
	}

	/*********************************
	  ATTACHYMENTS MOD
	*********************************/
	function attach_mod() {
		global $installer;
		$installer->add_query('CREATE', $this->prefix.'attachments_desc', '
  attach_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  physical_filename VARCHAR(255) NOT NULL,
  real_filename VARCHAR(255) NOT NULL,
  download_count MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  comment VARCHAR(255),
  extension VARCHAR(100),
  mimetype VARCHAR(100),
  filesize INT NOT NULL,
  filetime INT NOT NULL DEFAULT 0,
  thumbnail tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (attach_id),
  KEY filetime (filetime),
  KEY physical_filename (physical_filename(10)),
  KEY filesize (filesize)', $this->prefix.'attachments_desc');
		$installer->add_query('CREATE', $this->prefix.'attachments', '
  attach_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  post_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  privmsgs_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  user_id_1 MEDIUMINT NOT NULL,
  user_id_2 MEDIUMINT NOT NULL,
  KEY attach_id_post_id (attach_id, post_id),
  KEY attach_id_privmsgs_id (attach_id, privmsgs_id)', $this->prefix.'attachments');

		if ($this->base == 'Forums') {
			$installer->add_query('CREATE', $this->prefix.'attachments_config', '
  config_name VARCHAR(255) NOT NULL,
  config_value VARCHAR(255) NOT NULL,
  PRIMARY KEY (config_name)', $this->prefix.'attachments_config');
			$installer->add_query('CREATE', $this->prefix.'extension_groups', '
  group_id MEDIUMINT NOT NULL AUTO_INCREMENT,
  group_name VARCHAR(20) NOT NULL,
  cat_id tinyint(2) NOT NULL DEFAULT 0,
  allow_group tinyint(1) NOT NULL DEFAULT 0,
  download_mode tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  upload_icon VARCHAR(100) DEFAULT \'\',
  max_filesize INT NOT NULL DEFAULT 0,
  forum_permissions VARCHAR(255) DEFAULT \'\' NOT NULL,
  PRIMARY KEY (group_id)', $this->prefix.'extension_groups');
			$installer->add_query('CREATE', $this->prefix.'extensions', '
  ext_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  extension VARCHAR(100) NOT NULL,
  comment VARCHAR(100),
  PRIMARY KEY (ext_id)', $this->prefix.'extensions');
			$installer->add_query('CREATE', $this->prefix.'forbidden_extensions', '
  ext_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  extension VARCHAR(100) NOT NULL,
  PRIMARY KEY (ext_id)', $this->prefix.'forbidden_extensions');
			$installer->add_query('CREATE', $this->prefix.'attach_quota', '
  user_id MEDIUMINT unsigned NOT NULL DEFAULT 0,
  group_id MEDIUMINT unsigned NOT NULL DEFAULT 0,
  quota_type smallint(2) NOT NULL DEFAULT 0,
  quota_limit_id MEDIUMINT unsigned NOT NULL DEFAULT 0,
  KEY quota_type (quota_type)', $this->prefix.'attach_quota');
			$installer->add_query('CREATE', $this->prefix.'quota_limits', '
  quota_limit_id MEDIUMINT unsigned NOT NULL AUTO_INCREMENT,
  quota_desc VARCHAR(20) NOT NULL DEFAULT \'\',
  quota_limit BIGINT unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (quota_limit_id)', $this->prefix.'quota_limits');
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'upload_dir','uploads/forums'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'upload_img','images/icons/icon_disk.gif'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'topic_icon','images/icons/icon_clip.gif'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'display_order','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'max_filesize','262144'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'attachment_quota','52428800'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'max_filesize_pm','262144'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'max_attachments','3'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'max_attachments_pm','1'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'disable_mod','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'allow_pm_attach','1'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'attachment_topic_review','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'allow_ftp_upload','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'show_apcp','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'attach_version','2.3.9'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'default_upload_quota', '0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'default_pm_quota', '0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'ftp_server',''");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'ftp_path',''");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'download_path',''");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'ftp_user',''");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'ftp_pass',''");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'ftp_pasv_mode','1'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_display_inlined','1'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_max_width','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_max_height','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_link_width','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_link_height','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_create_thumbnail','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_min_thumb_filesize','12000'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'img_imagick', ''");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'use_gd2','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'wma_autoplay','0'");
		$installer->add_query('INSERT', $this->prefix.'attachments_config', "'flash_autoplay','0'");
		# -- forbidden_extensions
		$installer->add_query('INSERT', $this->prefix.'forbidden_extensions', "1,'php'");
		$installer->add_query('INSERT', $this->prefix.'forbidden_extensions', "2,'php3'");
		$installer->add_query('INSERT', $this->prefix.'forbidden_extensions', "3,'php4'");
		$installer->add_query('INSERT', $this->prefix.'forbidden_extensions', "4,'phtml'");
		$installer->add_query('INSERT', $this->prefix.'forbidden_extensions', "5,'pl'");
		$installer->add_query('INSERT', $this->prefix.'forbidden_extensions', "6,'asp'");
		$installer->add_query('INSERT', $this->prefix.'forbidden_extensions', "7,'cgi'");
		# -- extension_groups
		$installer->add_query('INSERT', $this->prefix.'extension_groups', "1,'Images',1,1,1,'',0,''");
		$installer->add_query('INSERT', $this->prefix.'extension_groups', "2,'Archives',0,1,1,'',0,''");
		$installer->add_query('INSERT', $this->prefix.'extension_groups', "3,'Plain Text',0,0,1,'',0,''");
		$installer->add_query('INSERT', $this->prefix.'extension_groups', "4,'Documents',0,0,1,'',0,''");
		$installer->add_query('INSERT', $this->prefix.'extension_groups', "5,'Real Media',0,0,2,'',0,''");
		$installer->add_query('INSERT', $this->prefix.'extension_groups', "6,'Streams',2,0,1,'',0,''");
		$installer->add_query('INSERT', $this->prefix.'extension_groups', "7,'Flash Files',3,0,1,'',0,''");
		# -- extensions
		$installer->add_query('INSERT', $this->prefix.'extensions', "1, 1,'gif', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "2, 1,'png', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "3, 1,'jpeg', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "4, 1,'jpg', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "5, 1,'tif', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "6, 1,'tga', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "7, 2,'gtar', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "8, 2,'gz', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "9, 2,'tar', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "10, 2,'zip', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "11, 2,'rar', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "12, 2,'ace', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "13, 3,'txt', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "14, 3,'c', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "15, 3,'h', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "16, 3,'cpp', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "17, 3,'hpp', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "18, 3,'diz', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "19, 4,'xls', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "20, 4,'doc', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "21, 4,'dot', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "22, 4,'pdf', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "23, 4,'ai', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "24, 4,'ps', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "25, 4,'ppt', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "26, 5,'rm', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "27, 6,'wma', ''");
		$installer->add_query('INSERT', $this->prefix.'extensions', "28, 7,'swf', ''");
		# -- default quota limits
		$installer->add_query('INSERT', $this->prefix.'quota_limits', "1, 'Low', 262144");
		$installer->add_query('INSERT', $this->prefix.'quota_limits', "2, 'Medium', 2097152");
		$installer->add_query('INSERT', $this->prefix.'quota_limits', "3, 'High', 5242880");
		} // end ($this->base == 'Forums')
	}

// module uninstaller
	function uninstall() {
		//bbconfig, bbthemes, bbwords used by Privmsgs
		global $installer;
		foreach($this->dbtables as $table) {
			if ($table != $this->prefix.'config' &&
				$table != $this->prefix.'words' &&
				$table != $this->prefix.'themes')
				$installer->add_query('DROP', $table);
		}
		return true;
	}

// module upgrader
	function upgrade($prev_version) {
		global $installer;
//		  $db->sql_query('DELETE FROM '.$prefix.'_credits WHERE modname="Downloads v2"');
		return true;
	}

}
