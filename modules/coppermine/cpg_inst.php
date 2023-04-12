<?php
/*********************************************
  CPG-NUKE: Project Management System
  ********************************************
  Copyright (c) 2004 by CPG-Nuke Dev Team
  http://www.cpgnuke.com

  $Source: /cvs/html/modules/coppermine/cpg_inst.php,v $
  $Revision: 9.10 $
  $Author: nanocaiordo $
  $Date: 2006/10/04 15:03:29 $

***********************************************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class coppermine {
	var $radmin;
	var $version;
	var $modname;
	var $description;
	var $author;
	var $website;
	var $dbtables;

	var $prefix;
	var $base;
	function coppermine() {
		$this->radmin = true;
		$this->version = '1.3.1';
		$this->modname = 'Coppermine';
		$this->description = 'Coppermine Photo Gallery ported for Dragonfly™ by the CPG-Nuke Dev Team';
		$this->author = 'Grégory Demar';
		$this->website = 'coppermine.sourceforge.net';
		$this->base = basename(dirname(__FILE__));
		$this->prefix = ($this->base == 'coppermine') ? 'cpg' : strtolower($this->base);
		$this->dbtables = array($this->prefix.'_albums', $this->prefix.'_categories',
			$this->prefix.'_comments', $this->prefix.'_config', $this->prefix.'_exif',
			$this->prefix.'_pictures', $this->prefix.'_votes');
		if ($this->base == 'coppermine') {
			$this->dbtables[] = $this->prefix.'_usergroups';
			$this->dbtables[] = 'cpg_installs';
		}
	}

	function install() {
		global $installer, $BASEHREF, $MAIN_CFG;
		$installer->add_query('CREATE', $this->prefix.'_albums', "
  aid int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  description text NOT NULL,
  visibility int(11) NOT NULL default '0',
  uploads enum('YES','NO') NOT NULL default 'NO',
  comments enum('YES','NO') NOT NULL default 'YES',
  votes enum('YES','NO') NOT NULL default 'YES',
  pos int(11) NOT NULL default '0',
  category int(11) NOT NULL default '0',
  pic_count int(11) NOT NULL default '0',
  thumb int(11) NOT NULL default '0',
  last_addition INT NOT NULL default '0',
  stat_uptodate enum('YES','NO') NOT NULL default 'NO',
  PRIMARY KEY (aid),
  KEY alb_category (category)", $this->prefix.'_albums');
		$installer->add_query('CREATE', $this->prefix.'_categories', "
  cid int(11) NOT NULL auto_increment,
  owner_id int(11) NOT NULL default '0',
  catname varchar(255) NOT NULL default '',
  description text NOT NULL,
  pos int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0',
  subcat_count int(11) NOT NULL default '0',
  alb_count int(11) NOT NULL default '0',
  pic_count int(11) NOT NULL default '0',
  stat_uptodate enum('YES','NO') NOT NULL default 'NO',
  PRIMARY KEY (cid),
  KEY cat_parent (parent),
  KEY cat_pos (pos),
  KEY cat_owner_id (owner_id)", $this->prefix.'_categories');
		$installer->add_query('CREATE', $this->prefix.'_comments', "
  pid mediumint(9) NOT NULL default '0',
  msg_id mediumint(9) NOT NULL auto_increment,
  msg_author varchar(25) NOT NULL default '',
  msg_body text NOT NULL,
  msg_date datetime NOT NULL default '0000-00-00 00:00:00',
  author_md5_id varchar(32) NOT NULL default '',
  author_id int(11) NOT NULL default '0',
  msg_raw_ip tinytext,
  msg_hdr_ip tinytext,
  PRIMARY KEY (msg_id),
  KEY com_pic_id (pid)", $this->prefix.'_comments');
		$installer->add_query('CREATE', $this->prefix.'_config', "
	name varchar(40) NOT NULL default '',
  value varchar(255) NOT NULL default '',
  PRIMARY KEY (name)", $this->prefix.'_config');
		$installer->add_query('CREATE', $this->prefix.'_exif', "
  filename varchar(255) NOT NULL default '',
  exif_data text NOT NULL,
  UNIQUE KEY filename (filename)", $this->prefix.'_exif');
		$installer->add_query('CREATE', $this->prefix.'_pictures', "
  pid int(11) NOT NULL auto_increment,
  aid int(11) NOT NULL default '0',
  filepath varchar(255) NOT NULL default '',
  filename varchar(255) NOT NULL default '',
  filesize int(11) NOT NULL default '0',
  total_filesize int(11) NOT NULL default '0',
  pwidth smallint(6) NOT NULL default '0',
  pheight smallint(6) NOT NULL default '0',
  hits int(11) NOT NULL default '0',
  mtime timestamp(14) NOT NULL,
  ctime int(11) NOT NULL default '0',
  owner_id int(11) NOT NULL default '0',
  owner_name varchar(40) NOT NULL default '',
  pic_rating int(11) NOT NULL default '0',
  votes int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  caption text NOT NULL,
  keywords varchar(255) NOT NULL default '',
  approved tinyint(1) NOT NULL default '0',
  user1 varchar(255) NOT NULL default '',
  user2 varchar(255) NOT NULL default '',
  user3 varchar(255) NOT NULL default '',
  user4 varchar(255) NOT NULL default '',
  url_prefix tinyint(4) NOT NULL default '0',
  randpos int(11) NOT NULL default '0',
  pic_raw_ip tinytext,
  pic_hdr_ip tinytext,
  PRIMARY KEY (pid),
  KEY pic_hits (hits),
  KEY pic_rate (pic_rating),
  KEY aid_approved (aid,approved),
  KEY randpos (randpos),
  KEY pic_aid (aid),
  FULLTEXT KEY search (title,caption,keywords,filename,user1,user2,user3,user4)", $this->prefix.'_pictures');
		$installer->add_query('CREATE', $this->prefix.'_votes', "
  pic_id mediumint(9) NOT NULL default '0',
  user_md5_id varchar(32) NOT NULL default '',
  vote_time int(11) NOT NULL default '0',
  PRIMARY KEY (pic_id,user_md5_id)", $this->prefix.'_votes');

		$installer->add_query('INSERT', $this->prefix.'_categories', "1, 0, 'User galleries', 'This category contains albums that belong to Coppermine users.', 0, 0, 0, 0, 0, 'NO'");

		$installer->add_query('INSERT', $this->prefix.'_config', "'albums_per_page', '12'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'album_list_cols', '2'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'display_pic_info', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'alb_list_thumb_size', '50'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'allowed_file_extensions', 'GIF/PNG/JPG/JPEG/TIF/TIFF'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'allowed_img_types', 'JPG/GIF/PNG/TIFF'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'allow_private_albums', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'allow_user_registration', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'allow_duplicate_emails_addr', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'caption_in_thumbview', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'charset', 'language file'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'cookie_name', '".$this->base."'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'cookie_path', '".$MAIN_CFG['cookie']['path']."'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'debug_mode', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'default_sort_order', 'na'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'ecards_more_pic_target', '$BASEHREF'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'enable_smilies', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'filter_bad_words', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'forbiden_fname_char', '$/\\:*?\"\'<>|`'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'fullpath', 'modules/coppermine/albums/'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'gallery_admin_email', '".$MAIN_CFG['globals']['adminmail']."'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'gallery_description', 'Your online photo album'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'gallery_name', 'Coppermine Photo Gallery'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'im_options', '-antialias'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'impath', ''");
		$installer->add_query('INSERT', $this->prefix.'_config', "'jpeg_qual', '80'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'keep_votes_time', '30'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'lang', 'english'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'main_page_layout', 'breadcrumb/catlist/alblist/lastalb,1/lastup,1/lastcom,1/topn,1/toprated,1/random,1/anycontent/favpics,3'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'main_table_width', '100%'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'make_intermediate', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_com_lines', '10'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_com_size', '512'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_com_wlength', '38'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_img_desc_length', '512'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_tabs', '12'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_upl_size', '1024'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_upl_width_height', '2048'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'min_votes_for_rating', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'normal_pfx', 'normal_'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picture_table_width', '600'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picture_width', '400'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'randpos_interval', '5'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'read_exif_data', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'reg_requires_valid_email', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'subcat_level', '2'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'theme', 'default'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'thumbcols', '4'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'thumbrows', '3'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'thumb_method', 'gd2'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'thumb_pfx', 'thumb_'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'thumb_width', '100'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'userpics', 'modules/coppermine/albums/userpics/'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'user_field1_name', ''");
		$installer->add_query('INSERT', $this->prefix.'_config', "'user_field2_name', ''");
		$installer->add_query('INSERT', $this->prefix.'_config', "'user_field3_name', ''");
		$installer->add_query('INSERT', $this->prefix.'_config', "'user_field4_name', ''");
		$installer->add_query('INSERT', $this->prefix.'_config', "'display_comment_count', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'display_film_strip', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'max_film_strip_items', '5'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'samename', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'first_level', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'show_private', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'thumb_use', 'ht'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'comment_email_notification', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'disable_flood_protection', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'nice_titles', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'seo_alts', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'read_iptc_data', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_favorites', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_filename', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_album_name', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_file_size', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_dimensions', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_count_displayed', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_URL', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'picinfo_display_URL_bookmark', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'allow_anon_fullsize', '1'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'right_blocks', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'avatar_private_album', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'avatar_private_album', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'watermark', '0'");
		$installer->add_query('INSERT', $this->prefix.'_config', "'fullsize_slideshow', '0'");

		if ($this->base == 'coppermine') {
		$installer->add_query('CREATE', $this->prefix.'_usergroups', "
  group_id int(11) NOT NULL auto_increment,
  group_name varchar(255) NOT NULL default '',
  group_quota int(11) NOT NULL default '0',
  has_admin_access tinyint(4) NOT NULL default '0',
  can_rate_pictures tinyint(4) NOT NULL default '0',
  can_send_ecards tinyint(4) NOT NULL default '0',
  can_post_comments tinyint(4) NOT NULL default '0',
  can_upload_pictures tinyint(4) NOT NULL default '0',
  can_create_albums tinyint(4) NOT NULL default '0',
  pub_upl_need_approval tinyint(4) NOT NULL default '1',
  priv_upl_need_approval tinyint(4) NOT NULL default '1',
  PRIMARY KEY (group_id)", $this->prefix.'_usergroups');
		$installer->add_query('CREATE', 'cpg_installs', "
  cpg_id tinyint(4) NOT NULL auto_increment,
  dirname varchar(20) NOT NULL default '',
  prefix varchar(20) NOT NULL default '',
  version varchar(10) default NULL,
  PRIMARY KEY (cpg_id)", 'cpg_installs');

		$installer->add_query('INSERT', $this->prefix.'_usergroups', "1, 'Administrators', 0, 1, 1, 1, 1, 1, 1, 0, 0");
		$installer->add_query('INSERT', $this->prefix.'_usergroups', "2, 'Registered', '1024', 0, 1, 1, 1, 1, 1, 1, 0");
		$installer->add_query('INSERT', $this->prefix.'_usergroups', "3, 'Anonymous', 0, 0, 1, 0, 0, 0, 0, 1, 1");
		$installer->add_query('INSERT', $this->prefix.'_usergroups', "4, 'Banned', 0, 0, 0, 0, 0, 0, 0, 1, 1");
		}
		global $prefix;
		$installer->add_query('INSERT', 'cpg_installs', "DEFAULT, '".$this->base."', '".$prefix.'_'.$this->prefix."_', '".$this->version."'");

		return true;
	}

	function uninstall() {
		global $installer;
		foreach($this->dbtables as $table) {
			$installer->add_query('DROP', $table);
		}
		return true;
	}

	function upgrade($prev_version) {
//		$db->sql_query('DELETE FROM '.$this->prefix.'_credits WHERE modname="Downloads v2"');
// exifData -> exif_data
		return true;
	}
}
