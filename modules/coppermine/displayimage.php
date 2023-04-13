<?php
/***************************************************************************  
   Coppermine Photo Gallery 1.3.1 for CPG-Nuke								
  **************************************************************************  
   Port Copyright (C) 2004 Coppermine/CPG-Nuke Dev Team						
   http://cpgnuke.com/											   
  **************************************************************************  

   http://coppermine.sf.net/team/										
   This program is free software; you can redistribute it and/or modify	   
   it under the terms of the GNU General Public License as published by	   
   the Free Software Foundation; either version 2 of the License, or		  
   (at your option) any later version.										   
  **************************************************************************  
  $Header: /cvs/html/modules/coppermine/displayimage.php,v 9.19 2007/08/04 13:14:37 nanocaiordo Exp $			
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('DISPLAYIMAGE_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
require_once('includes/nbbcode.php');
$breadcrumb_text = '';
$cat_data = array();
if ($CONFIG['read_exif_data'] && function_exists('exif_read_data')) {
	require_once("includes/coppermine/exif_php.inc");
} elseif ($CONFIG['read_exif_data']) {
	cpg_die(_CRITICAL_ERROR, 'PHP running on your server does not support reading EXIF data in JPEG files, please turn this off on the config page', __FILE__, __LINE__);
}
/* doesn't work on resized pics
if ($CONFIG['read_iptc_data']) {
	require_once(includes/coppermine/iptc.inc");
}*/
/**
 * Local functions definition
 */
function html_picture_menu($id)
{
	global $THEME_DIR;
	return '<span style="float:right;">
	<form method="post" action="'.getlink("&amp;file=editOnePic",1,1).'">
		<input type="hidden" name="id" value="'.$id.'" />
		<input name="submit" title="'.EDIT_PIC.'" type="image" src="' . $THEME_DIR . '/images/edit.gif" /></form></span>' . "\n".'
		<span style="float:right;"><form method="post" action="'.getlink("&amp;file=delete",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
		<input type="hidden" name="what" value="picture" />
		<input type="hidden" name="id" value="'.$id.'" />
	   <input name="submit" title="'.DEL_PIC.'" type="image" src="' . $THEME_DIR . '/images/delete.gif" /></form></span>' . "\n";
	}
	/*<br /><div align="center" class="admin_menu">
	<a href="'.getlink("&amp;file=editOnePic&amp;id=$id&amp;what=picture").'" class="admin_menu">'.EDIT_PIC.'</a>
	<a href="'.getlink("&amp;file=delete&amp;id=$id&amp;what=picture").'" class="adm_menu" onclick="return confirm(\''.PIC_CONFIRM_DEL.'\');">'.DEL_PIC.'</a></div>';*/

// Prints the image-navigation menu
function html_img_nav_menu()
{
	global $CONFIG, $CURRENT_PIC_DATA;
	global $meta, $album, $cat, $pos, $pic_count, $template_img_navbar;
	$cat_link = is_numeric($album) ? '&amp;album='.$album : '&amp;cat=' . $cat;
	$meta_link = ($meta == '') ? '' : '&amp;meta=' . $meta;
	$human_pos = $pos + 1;
	$page = ceil(($pos + 1) / ($CONFIG['thumbrows'] * $CONFIG['thumbcols']));
	$pid = $CURRENT_PIC_DATA['pid'];
	if ($pos > 0) {
		$prev = $pos - 1;
		$prev_tgt = getlink("&amp;file=displayimage$meta_link$cat_link&amp;pos=$prev");
		$prev_title = PREV_TITLE;
	} else {
		$prev_tgt = "javascript:alert('" . addcslashes(NO_LESS_IMAGES, "'") . "');";
		$prev_title = NO_LESS_IMAGES;
	}
	if ($pos < ($pic_count -1)) {
		$next = $pos + 1;
		$next_tgt = getlink("&amp;file=displayimage$meta_link$cat_link&amp;pos=$next");
		$next_title = NEXT_TITLE;
	} else {
		$next_tgt = "javascript:alert('" . addcslashes(NO_MORE_IMAGES, "'") . "');";
		$next_title = NO_MORE_IMAGES;
	}
	if ((USER_CAN_SEND_ECARDS) && (USER_ID or $CONFIG['allow_anon_fullsize'] or USER_IS_ADMIN)) {
		$ecard_tgt = getlink("&amp;file=ecard$meta_link$cat_link&amp;pid=$pid&amp;pos=$pos");
		$ecard_title = ECARD_TITLE;
	} else {
		$ecard_tgt = "javascript:alert('" . addcslashes(ECARD_DISABLED, "'") . "');";
		$ecard_title = ECARD_DISABLED;
	}
	$thumb_tgt = getlink("&amp;file=thumbnails$meta_link$cat_link&amp;page=$page"); //$cat_link&page=$page
	// Only show the slideshow to registered user, admin, or if admin allows anon access to full size images
	if (USER_ID or $CONFIG['allow_anon_fullsize'] or USER_IS_ADMIN ) {
		$slideshow_tgt = getlink("&amp;file=displayimage$meta_link$cat_link&amp;pid=$pid&amp;slideshow=5000");
		$slideshow_title = SLIDESHOW_TITLE;
	} else {
		$slideshow_tgt = "javascript:alert('" . addcslashes(SLIDESHOW_DISABLED, "'") . "');";
		$slideshow_title = MEMBERS_ONLY;
	}

	$pic_pos = sprintf(PIC_POS, $human_pos, $pic_count);
	$params = array('{THUMB_TGT}' => $thumb_tgt,
//				'{THUMB_TITLE}' => THUMB_TITLE,
//				'{PIC_INFO_TITLE}' => PIC_INFO_TITLE,
				'{SLIDESHOW_TGT}' => $slideshow_tgt,
				'{SLIDESHOW_TITLE}' => $slideshow_title,
				'{PIC_POS}' => $pic_pos,
				'{ECARD_TGT}' => $ecard_tgt,
				'{ECARD_TITLE}' => $ecard_title,
				'{PREV_TGT}' => $prev_tgt,
				'{PREV_TITLE}' => $prev_title,
				'{NEXT_TGT}' => $next_tgt,
				'{NEXT_TITLE}' => $next_title,
	);
	return template_eval($template_img_navbar, $params);
}

// Displays a picture
function html_picture()
{
	global $CONFIG, $CURRENT_PIC_DATA, $CURRENT_ALBUM_DATA, $USER, $CPG_M_DIR;
	global $album, $template_display_picture;
	$pid = $CURRENT_PIC_DATA['pid'];
	// $ina is where the Registered Only picture is
	$ina = "$CPG_M_DIR/images/ina.jpg";
	// Check for anon picture viewing - only for registered user, admin, or if admin allows anon access to full size images
	if (USER_ID > 1 or $CONFIG['allow_anon_fullsize'] or USER_IS_ADMIN) {
		// Add 1 to hit counter unless the user reloaded the page
		if (!isset($USER['liv']) || !is_array($USER['liv'])) {
			$USER['liv'] = array();
		}
		// Add 1 to hit counter
		if ($album != "lasthits" && !in_array($pid, $USER['liv']) && isset($_COOKIE[$CONFIG['cookie_name'] . '_data'])) {
			add_hit($pid);
			if (count($USER['liv']) > 4) array_shift($USER['liv']);
			//pass by ref depreciated in 4.3.9 array_push($USER['liv'], $pid);
			$USER['liv'][] = $pid;
		}
		if ($CONFIG['make_intermediate'] && max($CURRENT_PIC_DATA['pwidth'], $CURRENT_PIC_DATA['pheight']) > $CONFIG['picture_width']) {
			$picture_url = get_pic_url($CURRENT_PIC_DATA, 'normal');
		} else {
			$picture_url = get_pic_url($CURRENT_PIC_DATA, 'fullsize');
		}
		$picture_menu = ((USER_ADMIN_MODE && $CURRENT_ALBUM_DATA['category'] == FIRST_USER_CAT + USER_ID) || GALLERY_ADMIN_MODE || $CURRENT_PIC_DATA['owner_id']== USER_ID) ? html_picture_menu($pid) : '';
		$image_size = compute_img_size($CURRENT_PIC_DATA['pwidth'], $CURRENT_PIC_DATA['pheight'], $CONFIG['picture_width']);
		$pic_title = '';
		if ($CURRENT_PIC_DATA['title'] != '') {
			$pic_title .= $CURRENT_PIC_DATA['title'] . "\n";
		}
		if ($CURRENT_PIC_DATA['caption'] != '') {
			$pic_title .= $CURRENT_PIC_DATA['caption'] . "\n";
		}
		if ($CURRENT_PIC_DATA['keywords'] != '') {
			$pic_title .= KEYWORDS . ": " . $CURRENT_PIC_DATA['keywords'];
		}
		if (isset($image_size['reduced'])) {
			$CONFIG['justso']=0;
			if ($CONFIG['justso']) {
				//require_once('jspw.js');
				$winsizeX = $CURRENT_PIC_DATA['pwidth']+ 16;
				$winsizeY = $CURRENT_PIC_DATA['pheight']+ 16;
				$hug = 'hug image';
				$hugwidth = '4';
				$bgclr = '#000000';
				$alt = CLICK_TO_CLOSE; // $lang_fullsize_popup[1];
				$pic_html = '<a href="'.getlink("&amp;file=justsofullsize&amp;pid=$pid",false,true).'" target="' . uniqid(random_int(0, mt_getrandmax())) . "\" onclick=\"JustSoPicWindow('".getlink("&amp;file=justsofullsize&amp;pid=$pid",false,true)."','$winsizeX','$winsizeY','$alt','$bgclr','$hug','$hugwidth');return false\">";
			} else {
				$winsizeX = $CURRENT_PIC_DATA['pwidth'] + 16;
				$winsizeY = $CURRENT_PIC_DATA['pheight'] + 16;
				$pic_html = '<a href="'.getlink("&amp;file=displayimagepopup&amp;pid=$pid&amp;fullsize=1",true,true).'" target="' . uniqid(random_int(0, mt_getrandmax())) . "\" onclick=\"imgpop('".getlink("&amp;file=displayimagepopup&amp;pid=$pid&amp;fullsize=1",true,true)."','" . uniqid(random_int(0, mt_getrandmax())) . "','resizable=yes,scrollbars=yes,width=$winsizeX,height=$winsizeY,left=0,top=0');return false\">"; //toolbar=yes,status=yes,
				$pic_title = VIEW_FS . "\n ============== \n" . $pic_title; //added by gaugau
			}
			$pic_html .= "<img src=\"" . $picture_url . "\" {$image_size['geom']} class=\"image\" border=\"0\" alt=\"{$pic_title}\" title=\"{$pic_title}\" /><br />";
			$pic_html .= "</a>\n";
		} else {
			$pic_html = "<img src=\"" . $picture_url . "\" {$image_size['geom']} alt=\"{$pic_title}\" title=\"{$pic_title}\" class=\"image\" border=\"0\" /><br />\n";
		}
			if (!$CURRENT_PIC_DATA['title'] && !$CURRENT_PIC_DATA['caption']) {
				template_extract_block($template_display_picture, 'img_desc');
			} else {
				if (!$CURRENT_PIC_DATA['title']) {
					template_extract_block($template_display_picture, 'title');
				}
				if (!$CURRENT_PIC_DATA['caption']) {
					template_extract_block($template_display_picture, 'caption');
				}
			}
	} else {
		$imagesize = getimagesize($ina);
		$image_size = compute_img_size($imagesize[0], $imagesize[1], $CONFIG['picture_width']);
		$pic_html = '<a href="' .NEWUSER_URL. '">';
		$pic_html .= "<img src=\"" . $ina . "\" {$image_size['geom']} alt=\"Click to register\" title=\"Click to register\" class=\"image\" border=\"0\" /></a><br />";
		$picture_menu = "";
		$CURRENT_PIC_DATA['title'] = MEMBERS_ONLY;
		$CURRENT_PIC_DATA['caption'] = '';
	}
	$params = array('{CELL_HEIGHT}' => '100',
		'{IMAGE}' => $pic_html,
		'{ADMIN_MENU}' => $picture_menu,
		'{TITLE}' => $CURRENT_PIC_DATA['title'],
		'{CAPTION}' => decode_bbcode($CURRENT_PIC_DATA['caption']),
	);
	return template_eval($template_display_picture, $params);
}

function html_rating_box()
{
	global $CONFIG, $CURRENT_PIC_DATA, $CURRENT_ALBUM_DATA;
	global $template_image_rating;
	if (!(USER_CAN_RATE_PICTURES && $CURRENT_ALBUM_DATA['votes'])) return '';
	$votes = (!empty($CURRENT_PIC_DATA['pic_rating'])) ? sprintf(RATING, round($CURRENT_PIC_DATA['pic_rating'] / 2000, 1), $CURRENT_PIC_DATA['votes']) : NO_VOTES;
	//$pid = $CURRENT_PIC_DATA['pid'];
	$qs = '';
	foreach($_GET as $var => $value) {
	if ($var != 'name') {
		$qs .= $var.'='.$value.= '&amp;';
	}
	}
	$qs = substr($qs, 0, -5);
	$params = array(
//		'{TITLE}' => $lang_rate_pic['rate_this_pic'],
		'{PID}'   => $CURRENT_PIC_DATA['pid'],
		'{VOTES}' => $votes,
		'{CURRENTPAGE}' => "&amp;$qs",
	);
/*		'{RATE0}' => getlink("&amp;file=ratepic&amp;pic=$pid&amp;rate=0"),
		'{RATE1}' => getlink("&amp;file=ratepic&amp;pic=$pid&amp;rate=1"),
		'{RATE2}' => getlink("&amp;file=ratepic&amp;pic=$pid&amp;rate=2"),
		'{RATE3}' => getlink("&amp;file=ratepic&amp;pic=$pid&amp;rate=3"),
		'{RATE4}' => getlink("&amp;file=ratepic&amp;pic=$pid&amp;rate=4"),
		'{RATE5}' => getlink("&amp;file=ratepic&amp;pic=$pid&amp;rate=5"),
	);*/
	if (USER_ID or $CONFIG['allow_anon_fullsize'] or USER_IS_ADMIN) {
		return template_eval($template_image_rating, $params);
	}
}
// Display picture information
function html_picinfo()
{
	$info = [];
 global $CONFIG, $CURRENT_PIC_DATA, $CURRENT_ALBUM_DATA, $THEME_DIR, $FAVPICS, $CPG_M_DIR;
	global $album,$lang_byte_units, $db;
	if ($CURRENT_PIC_DATA['owner_id'] && $CURRENT_PIC_DATA['owner_name']) {
		$owner_link = '<a href ="'.getlink('Your_Account&amp;profile=' . $CURRENT_PIC_DATA['owner_id']) . '">' . $CURRENT_PIC_DATA['owner_name'] . '</a> ';
	} else {
		$owner_link = '';
	}
	if (GALLERY_ADMIN_MODE && $CURRENT_PIC_DATA['pic_raw_ip']) {
		if ($CURRENT_PIC_DATA['pic_hdr_ip']) {
			$ipinfo = ' (' . $CURRENT_PIC_DATA['pic_hdr_ip'] . '[' . $CURRENT_PIC_DATA['pic_raw_ip'] . ']) / ';
		} else {
			$ipinfo = ' (' . $CURRENT_PIC_DATA['pic_raw_ip'] . ') / ';
		}
	} else {
		if ($owner_link) {
			$ipinfo = '/ ';
		} else {
			$ipinfo = '';
		}
	}
	if ($CONFIG['picinfo_display_filename']) {
		$info[PIC_INF_FILENAME] = htmlprepare($CURRENT_PIC_DATA['filename']);
	}
	// -----------------------------------------------------------------
	// Added by Vitor Freitas on 2003-09-01.
	// Hack version: 1.1
	// Display the name of the user that upload the image whit the image information.
	// Modified by DJ Maze for CPG 1.2 RC4
	global $db;

	$vf_sql = "SELECT username FROM " . $CONFIG['TABLE_USERS'] . " WHERE user_id='" . $CURRENT_PIC_DATA['owner_id'] . "'";
	$vf_result = $db->sql_query($vf_sql);
	$vf_row = $db->sql_fetchrow($vf_result);
	// if statement added by gtroll
	// only display if there is a value
	if ($vf_row != '') {
		$info['Upload by'] = '<a href="'.getlink('Your_Account&amp;profile=' . $CURRENT_PIC_DATA['owner_id']) . '" target="_blank">' . $vf_row['username'] . '</a>';
	}
	// End -- Vitor Freitas on 2003-08-29.
	// -----------------------------------------------------------------
	if ($CONFIG['picinfo_display_album_name']) {
		$info[ALBUM_NAME] = '<span class="alblink"><a href="' . getlink('&amp;file=thumbnails&amp;album=' . $CURRENT_PIC_DATA['aid']) . '">' . $CURRENT_ALBUM_DATA['title'] . '</a></span>';
	}
	if ($CURRENT_PIC_DATA['votes'] > 0) {
		$info[sprintf(PIC_INFO_RATING, $CURRENT_PIC_DATA['votes'])] = '<img src="' . $CPG_M_DIR . '/images/rating' . round($CURRENT_PIC_DATA['pic_rating'] / 2000) . '.gif" alt="'.sprintf(RATING, round($CURRENT_PIC_DATA['pic_rating'] / 2000),$CURRENT_PIC_DATA['votes']).'" align="absmiddle"/>';
	}
	if ($CURRENT_PIC_DATA['keywords'] != "") {
		$info[KEYWORDS] = '<span class="alblink">' . preg_replace("/(\S+)/", '<a href="'.getlink('&amp;file=thumbnails&amp;meta=search&amp;search=\\1').'">\\1</a>' , $CURRENT_PIC_DATA['keywords']) . '</span>';
	}
	//$info[test] = "SELECT pid FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} ON visibility IN (".USER_IN_GROUPS.") WHERE p.pid='".$CURRENT_PIC_DATA['pid']."' GROUP BY pid LIMIT 1";
	for ($i = 1; $i <= 4; $i++) {
		if ($CONFIG['user_field' . $i . '_name']) {
			if ($CURRENT_PIC_DATA['user' . $i] != "") {
				$info[$CONFIG['user_field' . $i . '_name']] = make_clickable($CURRENT_PIC_DATA['user' . $i]);
			}
		}
	}
	$filesizeinfo = ($CURRENT_PIC_DATA['filesize'] > 10240 ? ($CURRENT_PIC_DATA['filesize'] >> 10) . ' ' . $lang_byte_units[1] : $CURRENT_PIC_DATA['filesize'] . ' ' . $lang_byte_units[0]);
	if ($CONFIG['picinfo_display_file_size']) {
		$info[PIC_INF_FILE_SIZE] = '<span dir="LTR">' . $filesizeinfo . '</span>';
	}
	if ($CONFIG['picinfo_display_dimensions']) {
		$info[PIC_INF_DIMENSIONS] = sprintf(SIZE, $CURRENT_PIC_DATA['pwidth'], $CURRENT_PIC_DATA['pheight']);
	}
	if ($CONFIG['picinfo_display_dimensions']) {
		$info[DISPLAYED] = sprintf(VIEWS, $CURRENT_PIC_DATA['hits']);
	}
	$path_to_pic = $CURRENT_PIC_DATA['filepath'] . $CURRENT_PIC_DATA['filename'];
	if ($CONFIG['read_exif_data']) $exif = exif_parse_file($path_to_pic);
	if (isset($exif) && is_array($exif)) {
		if (isset($exif['Camera'])) $info[CAMERA] = strip_tags(trim($exif['Camera'],"\x0..\x1f"));
		if (isset($exif['DateTaken'])) $info[DATE_TAKEN] = strip_tags(trim($exif['DateTaken'],"\x0..\x1f"));
		if (isset($exif['Aperture'])) $info[APERTURE] = strip_tags(trim($exif['Aperture'],"\x0..\x1f"));
		if (isset($exif['ExposureTime'])) $info[EXPOSURE_TIME] = strip_tags(trim($exif['ExposureTime'],"\x0..\x1f"));
		if (isset($exif['FocalLength'])) $info[FOCAL_LENGTH] = strip_tags(trim($exif['FocalLength'],"\x0..\x1f"));
		if (isset($exif['Comment'])) $info[COMMENT] = strip_tags(trim($exif['Comment'],"\x0..\x1f"));
	}
	// Create the absolute URL for display in info
	if (($CONFIG['picinfo_display_URL']) || ($CONFIG['picinfo_display_URL_bookmark'])) {
		if ($CONFIG['picinfo_display_URL_bookmark']) {
			$info["URL"] = '<a href="'.getlink("&amp;file=displayimage&amp;album=$CURRENT_PIC_DATA[aid]&amp;pid=$CURRENT_PIC_DATA[pid]").'" onclick="addBookmark(\''.$CURRENT_PIC_DATA["filename"].'\',\''.getlink("&amp;file=displayimage&amp;pid=$CURRENT_PIC_DATA[pid]")."');return false\">".BOOKMARK_PAGE.'</a>';
		} else {
			$info['URL'] = '<a href="'.getlink("&amp;file=displayimage&amp;album=$CURRENT_PIC_DATA[aid]&amp;pid=$CURRENT_PIC_DATA[pid]").'">'.$CONFIG["ecards_more_pic_target"].getlink("&amp;file=displayimage&amp;pid=$CURRENT_PIC_DATA[pid]").'</a>';
		}
	}
/* doesn't work on resized pics
	if ($CONFIG['read_iptc_data']) $iptc = get_IPTC($path_to_pic);
	if (isset($iptc) && is_array($iptc)) {
		if (isset($iptc['Title'])) $info[IPTCTITLE] = strip_tags(trim($iptc['Title'],"\x0..\x1f"));
		if (isset($iptc['Copyright'])) $info[IPTCCOPYRIGHT] = strip_tags(trim($iptc['Copyright'],"\x0..\x1f"));
		if (!empty($iptc['Keywords'])) $info[IPTCKEYWORDS] = strip_tags(trim(implode(' ',$iptc['Keywords']),"\x0..\x1f"));
		if (isset($iptc['Category'])) $info[IPTCCATEGORY] = strip_tags(trim($iptc['Category'],"\x0..\x1f"));
		if (!empty($iptc['SubCategories'])) $info[IPTCSUBCATEGORIES] = strip_tags(trim(implode(' ',$iptc['SubCategories']),"\x0..\x1f"));
	}
*/
	// with subdomains the variable is $_SERVER["SERVER_NAME"] does not return the right value instead of using a new config variable I reused $CONFIG["ecards_more_pic_target"] with trailing slash in the configure
	// Create the add to fav link
	if ($CONFIG['picinfo_display_favorites']) {
		if (!in_array($CURRENT_PIC_DATA['pid'], $FAVPICS)) {
			$info[ADDFAVPHRASE] = '<a href="' . getlink('&amp;file=addfav&amp;pid=' . $CURRENT_PIC_DATA['pid']) . '" >' . ADDFAV . '</a>';
		} else {
			$info[ADDFAVPHRASE] = '<a href="' . getlink('&amp;file=addfav&amp;pid=' . $CURRENT_PIC_DATA['pid']) . '" >' . REMFAV . '</a>';
		}
	}
	if (USER_ID or $CONFIG['allow_anon_fullsize'] or USER_IS_ADMIN) {
		return theme_html_picinfo($info);
	}
}
// Displays comments for a specific picture
function html_comments($pid)
{
	global $CONFIG, $USER, $CURRENT_ALBUM_DATA, $username,$FAVPICS,$CURRENT_PIC_DATA,$THEME_DIR;
	global $template_image_comments, $template_add_your_comment, $db;
	$html = '';
	if (!$CONFIG['enable_smilies']) {
		$tmpl_comment_edit_box = template_extract_block($template_image_comments, 'edit_box_no_smilies', '{EDIT}');
		template_extract_block($template_image_comments, 'edit_box_smilies');
		template_extract_block($template_add_your_comment, 'input_box_smilies');
	} else {
		$tmpl_comment_edit_box = template_extract_block($template_image_comments, 'edit_box_smilies', '{EDIT}');
		template_extract_block($template_image_comments, 'edit_box_no_smilies');
		template_extract_block($template_add_your_comment, 'input_box_no_smilies');
	}
	$tmpl_comments_buttons = template_extract_block($template_image_comments, 'buttons', '{BUTTONS}');
	$tmpl_comments_ipinfo = template_extract_block($template_image_comments, 'ipinfo', '{IPINFO}');
	$result = $db->sql_query("SELECT msg_id, msg_author, msg_body, msg_date, author_id, author_md5_id, msg_raw_ip, msg_hdr_ip FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid='$pid' ORDER BY msg_id ASC",false, __FILE__,__LINE__);
	while ($row = $db->sql_fetchrow($result)) {
		$user_can_edit = (GALLERY_ADMIN_MODE || (USER_ID > 1 && USER_ID == $row['author_id'] && USER_CAN_POST_COMMENTS) || (USER_ID < 2 && USER_CAN_POST_COMMENTS && $USER['ID'] == $row['author_md5_id']));
		$comment_buttons = $user_can_edit ? $tmpl_comments_buttons : '';
		$comment_edit_box = $user_can_edit ? $tmpl_comment_edit_box : '';
		$comment_ipinfo = ($row['msg_raw_ip'] && GALLERY_ADMIN_MODE)?$tmpl_comments_ipinfo : '';
		if ($CONFIG['enable_smilies']) {
			$comment_body = set_smilies(make_clickable($row['msg_body']));
			$smilies = smilies_table('onerow', 'msg_body', "f{$row['msg_id']}");
		} else {
			$comment_body = make_clickable($row['msg_body']);
			$smilies = '';
		}
		$params = array('{EDIT}' => &$comment_edit_box,
			'{BUTTONS}' => &$comment_buttons,
			'{IPINFO}' => &$comment_ipinfo
		);
		$template = template_eval($template_image_comments, $params);
		$info = '';
		if (!in_array($pid, $FAVPICS)) {
			$info = '<a href="' . getlink('&amp;file=addfav&amp;pid=' . $CURRENT_PIC_DATA['pid']) . '" >' . ADDFAV . '</a>';
		} else {
			$info = '<a href="' . getlink('&amp;file=addfav&amp;pid=' . $CURRENT_PIC_DATA['pid']) . '" >' . REMFAV . '</a>';
		}
		$params = array('{MSG_AUTHOR}' => $row['msg_author'],
			'{MSG_ID}' => $row['msg_id'],
			'{MSG_TYPE}' => GALLERY_ADMIN_MODE ? 'text' : 'hidden',
			'{EDIT_TITLE}' => COM_EDIT_TITLE,
			'{CONFIRM_DELETE}' => CONFIRM_DELETE_COM,
			'{DELETE_LINK}' => getlink("&amp;file=delete"),
			'{DELETE_TEXT}' => DELETE.' '.COMMENT,
			'{MSG_DATE}' => localised_date($row['msg_date'], COMMENT_DATE_FMT),
			'{MSG_BODY}' => &$comment_body,
			'{MSG_BODY_RAW}' => $row['msg_body'],
			'{OK}' => OK,
			'{SMILIES}' => $smilies,
			'{HDR_IP}' => decode_ip($row['msg_hdr_ip']),
			'{RAW_IP}' => decode_ip($row['msg_raw_ip']),
			'{ACTION}' => 'action="'.getlink('&amp;file=db_input').'" enctype="multipart/form-data" accept-charset="utf-8"',
			'{ADDFAVLINK}' => getlink("&amp;file=addfav&amp;pid=$pid"), 
			'{ADDFAVTEXT}' => $info,
			'{THEMEDIR}' => $THEME_DIR,

		);
		$html .= template_eval($template, $params);
	}
	if (USER_CAN_POST_COMMENTS && $CURRENT_ALBUM_DATA['comments']) {
		if (USER_ID > 1) {
			$username_input = '<input type="hidden" name="msg_author" value="' . CPG_USERNAME . '" />';
			template_extract_block($template_add_your_comment, 'username_input', $username_input);
			// $username = '';
		} else {
			$username = isset($USER['name']) ? '"' . htmlprepare($USER['name']) . '"' : '"' . YOUR_NAME . '" onclick="javascript:this.value=\'\';"';
		}
		if (!in_array($pid, $FAVPICS)) {
			$info = '<a href="' . getlink('&amp;file=addfav&amp;pid=' . $CURRENT_PIC_DATA['pid']) . '" >' . ADDFAV . '</a>';
		} else {
			$info = '<a href="' . getlink('&amp;file=addfav&amp;pid=' . $CURRENT_PIC_DATA['pid']) . '" >' . REMFAV . '</a>';
		}
		$params = array('{ADD_YOUR_COMMENT}' => ADD_YOUR_COMMENT,
			// Modified Name and comment field
			'{NAME}' => COM_NAME,
			'{COMMENT}' => COMMENT,
			'{PIC_ID}' => $pid,
			'{username}' => $username,
			'{MAX_COM_LENGTH}' => $CONFIG['max_com_size'],
			'{OK}' => OK,
			'{SMILIES}' => '',
			'{ACTION}' => 'action="'.getlink("&amp;file=db_input").'" enctype="multipart/form-data" accept-charset="utf-8"',
			'{ADDFAVLINK}' => getlink("&amp;file=addfav&amp;pid=$pid"),
			'{ADDFAVTEXT}' => $info

		);
		if ($CONFIG['enable_smilies']) $params['{SMILIES}'] = smilies_table('onerow', 'message', 'post');
//		if ($CONFIG['enable_smilies']) $params['{SMILIES}'] = generate_smilies();
		$html .= template_eval($template_add_your_comment, $params);
	}
	if (USER_ID > 1 or $CONFIG['allow_anon_fullsize'] or USER_IS_ADMIN) {
		return $html;
	}
}

function slideshow()
{
	$start_img = null;
 global $CONFIG, $template_display_picture, $CPG_M_DIR;
	if (function_exists('theme_slideshow')) {
		theme_slideshow();
		return;
	}
	pageheader(SLIDESHOW_TITLE);
	require_once("includes/coppermine/slideshow.inc");
	$start_slideshow = '<script language="JavaScript" type="text/JavaScript">runSlideShow()</script>';
	template_extract_block($template_display_picture, 'img_desc', $start_slideshow);
	$params = array('{CELL_HEIGHT}' => $CONFIG['picture_width'] + 100,
		'{IMAGE}' => '<img src="' . $start_img . '" name="SlideShow" class="image" alt="" /><br />',
		'{ADMIN_MENU}' => '',
	);
	starttable();
	echo template_eval($template_display_picture, $params);
	endtable();
	starttable();
	echo '
		<tr>
		<td style="text-align:center"  class="navmenu" style="white-space: nowrap;">
		<a href="javascript:endSlideShow()" class="navmenu">'.STOP_SLIDESHOW.'</a>
		</td>
		</tr>
';
	endtable();
	pagefooter();
}

/**
 * Main code
 */
//global $lang_list_categories;
$pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$album = isset($_GET['album']) ? intval($_GET['album']) : '';
$meta = $_GET['meta'] ?? '';
$cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;

// $thisalbum is passed to get_pic_data as a varible used in queries 
// to limit meta queries to the current album or category
$thisalbum = "category >= '0'";//just something that is true
if ($meta != '') {
	if ($album != '') {
		$cat = -$album;
	}
	$album = $meta;
}
if ($cat<0) { //  && $cat<0 Meta albums, we need to restrict the albums to the current category
	$actual_album = -$cat;
	$thisalbum = 'a.aid = '.$actual_album;
}
else if ($cat){
	if ($cat == USER_GAL_CAT) {
		$thisalbum = 'category > ' . FIRST_USER_CAT;
	} elseif ($meta != '' && is_numeric($cat)) {
		if ($cat > 0) $thisalbum = "category = '$cat'";
	} else if (is_numeric($album)) {
		$thisalbum= "a.aid = $album";
	}
} else if (is_numeric($album)) {
	$thisalbum= "a.aid = $album";
}
// END NEW

// Retrieve data for the current picture
if ($meta == 'random' || ($meta == '' && !is_numeric($album)) || $pid > 0 || $pos < 0) {
	if ($pid < 1) $pid = $pos;
	if ($pos < 0) $pid = -$pos;
	$result = $db->sql_query("SELECT p.aid, a.visibility FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid && ".VIS_GROUPS.") WHERE approved = '1' AND p.pid=".$pid." LIMIT 1");	
	if ($db->sql_numrows($result) == 0) {
		list($visibility) = $db->sql_ufetchrow("SELECT a.visibility FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid) AND p.pid=".$pid." LIMIT 1");
		if ($visibility ==2){
			cpg_die(INFO, MEMBERS_ONLY, __FILE__, __LINE__);
		// works needs translation
		//} elseif ($visibility >= FIRST_USER_CAT){
		//	cpg_die(INFO, 'Users Private Gallery', __FILE__, __LINE__);
		} else{
			cpg_die(INFO, $row._MODULESADMINS, __FILE__, __LINE__);
		}	
	}
	$row = $db->sql_fetchrow($result);
	$album = $row['aid'];
	$pic_data = get_pic_data('', $album, $pic_count, $album_name, -1, 1, false);
	for($pos = 0; $pic_data[$pos]['pid'] != $pid && $pos < $pic_count; $pos++);
	$pic_data = get_pic_data('', $album, $pic_count, $album_name, $pos, 1, false);
	$CURRENT_PIC_DATA = $pic_data[0];
} else if (isset($_GET['pos'])){
	$pic_data = get_pic_data($meta, $album, $pic_count, $album_name, $pos, 1, false);
	if ((is_countable($pic_data) ? count($pic_data) : 0) == 0 && $pos >= $pic_count) {
		$pos = $pic_count - 1;
		$human_pos = $pos + 1;
		$pic_data = get_pic_data($meta, $album, $pic_count, $album_name, $pos, 1, false);
	}
	# last comment removed from an album and search engine cached the url
	if (empty($pic_data)) {
		cpg_die(INFO,sprintf(_ERROR_NONE_TO_DISPLAY, _COMMENTS), __FILE__, __LINE__);
	}
	if ($pic_count == 0) {
		list($visibility) = $db->sql_ufetchrow("SELECT visibility FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid=".$album." LIMIT 1");
		if ($visibility ==2){
			cpg_die(INFO, MEMBERS_ONLY, __FILE__, __LINE__);
		//works
		//} elseif ($visibility >= FIRST_USER_CAT){
		//	cpg_die(INFO, 'Users Private Gallery', __FILE__, __LINE__);
		} else{
			cpg_die(INFO, _MODULESADMINS, __FILE__, __LINE__);
		}
	}
	$CURRENT_PIC_DATA = $pic_data[0];	
}
// Retrieve data for the current album
	
if (isset($CURRENT_PIC_DATA)) {
	$result = $db->sql_query("SELECT title, comments, votes, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid='{$CURRENT_PIC_DATA['aid']}' LIMIT 1", false,__FILE__,__LINE__);
	if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, sprintf(PIC_IN_INVALID_ALBUM, $CURRENT_PIC_DATA['aid']), __FILE__, __LINE__);
	$CURRENT_ALBUM_DATA = $db->sql_fetchrow($result);
	if (!empty($CURRENT_PIC_DATA['keywords'])) { 
		$METATAGS['keywords'] = htmlprepare($CURRENT_PIC_DATA['keywords'], false, NULL, 1); 
	}
}
// slideshow control
if (isset($_GET['slideshow'])){
	slideshow();	
} else {
//	if (!isset($_GET['pos'])) cpg_die(_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
	$picture_title = $CURRENT_PIC_DATA['title'] ? $CURRENT_PIC_DATA['title'] : strtr(preg_replace("/(.+)\..*?\Z/", "\\1", htmlprepare($CURRENT_PIC_DATA['filename'])), "_", " ");
	$nav_menu =  html_img_nav_menu();
	$picture = html_picture();
	$votes = html_rating_box();
	$pic_info = html_picinfo();
	$comments = html_comments($CURRENT_PIC_DATA['pid']);
	pageheader($album_name . '/' . $picture_title, '', false);
	// Display Breadcrumbs
	set_breadcrumb(0);
	// Display Filmstrip if the album is not search
	if ($album != 'search') {
		$film_strip = display_film_strip($meta, $album, ($cat ?? 0), $pos, true);
	}
	
	theme_display_image($nav_menu, $picture, $votes, $pic_info, $comments, $film_strip); //,
	// strpos ( string haystack, string needle [, int offset])
	$mpl=$CONFIG['main_page_layout'];
	if (strpos("$mpl","anycontent")=== true) {
		require_once("$CPG_M_DIR/anycontent.php");
	}
	pagefooter();
}

?>
