<?php
/*********************************************
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
*******************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('PIC_VIEWS', 'Views');//new in 1.2.2nuke
define('PIC_VOTES', 'Votes');//new in 1.2.2nuke
define('PIC_COMMENTS', 'Comments');//new in 1.2.2nuke

// lang_translation_info
define('LANG_NAME_ENGLISH', 'English');
define('LANG_NAME_NATIVE', 'English');
define('LANG_COUNTRY_CODE', 'en');
define('TRANS_NAME', 'Akamu Akamai');
define('TRANS_EMAIL', 'akamu@nospam.cpgnuke.com');
define('TRANS_WEBSITE', 'http://dragonflycms.org/');
define('TRANS_DATE', '2003-04-20');
// left is for port compliancy
define('YES', 'Yes');
define('NO', 'No');
// some common strings
define('BACK', 'BACK');
define('CONTINU', 'Finish');
define('ALBUM_DATE_FMT', '%B %d, %Y');
define('LASTCOM_DATE_FMT', '%m/%d/%y @ %H:%M');
define('LASTUP_DATE_FMT', '%B %d, %Y');
define('REGISTER_DATE_FMT', '%B %d, %Y');
define('LASTHIT_DATE_FMT', '%B %d, %Y @ %I:%M %p');
define('COMMENT_DATE_FMT', '%B %d, %Y @ %I:%M %p');

// $LNG['cpg_meta_album_names']
define('RANDOM', 'Random pictures');
define('LASTUP', 'Last additions');
define('LASTUPBY', 'My Last Additions');
define('LASTALB', 'Last updated albums');
define('LASTCOM', 'Last comments');
define('LASTCOMBY', 'My Last comments');
define('TOPN', 'Most viewed');
define('TOPRATED', 'Top rated');
define('LASTHITS', 'Last viewed');
define('SEARCH', 'Search results');
define('FAVPICS', 'Favorite Pictures');

// lang_errors
define('ACCESS_DENIED', 'You don\'t have permission to access this page.');
define('PERM_DENIED', 'You don\'t have permission to perform this operation.');
define('PARAM_MISSING', 'Script called without the required parameter(s).');
define('NON_EXIST_AP', 'The selected album/picture does not exist !');
define('QUOTA_EXCEEDED', 'Disk quota exceeded<br /><br />You have a space quota of [quota]K, your pictures currently use [space]K, adding this picture would make you exceed your quota.');
define('INVALID_IMAGE', 'The image you have uploaded is corrupted or can\'t be handled by the GD library');
define('RESIZE_FAILED', 'Unable to create thumbnail or reduced size image.');
define('NO_IMG_TO_DISPLAY', 'No image to display');
define('NON_EXIST_CAT', 'The selected category does not exist');
define('ORPHAN_CAT', 'A category has a non-existing parent, runs the category manager to correct the problem.');
define('DIRECTORY_RO', 'Directory \'%s\' is not writable, pictures can\'t be deleted');
define('NON_EXIST_COMMENT', 'The selected comment does not exist.');
define('PIC_IN_INVALID_ALBUM', 'Picture is in a non existant album (%s)!?');
define('BANNED', 'You are currently banned from using this site.');
define('NOT_WITH_UDB', 'This function is disabled in Coppermine because it is integrated with forum software. Either what you are trying to do is not supported in this configuration, or the function should be handled by the forum software.');
define('MEMBERS_ONLY', 'This function is for members only, please join.');
define('MUSTBE_GOD', 'This function is only for the site admin. You must be logged in as superadmin, god account to access this function');
define('NO_IMG_TO_APPROVE', 'No images to approve');

// lang_main_menu
define('ALB_LIST_TITLE', 'Go to the album list');
define('ALB_LIST_LNK', 'Album list');
define('MY_GAL_TITLE', 'Go to my personal gallery');
define('MY_GAL_LNK', 'My gallery');
define('MY_PROF_LNK', 'My profile');
define('MY_PROF_TITLE','Check your disk quota and groups');
define('ADM_MODE_TITLE', 'Switch to admin mode');
define('ADM_MODE_LNK', 'Admin mode');
define('USR_MODE_TITLE', 'Switch to user mode');
define('USR_MODE_LNK', 'User mode');
define('UPLOAD_PIC_TITLE', 'Upload a picture into an album');
define('UPLOAD_PIC_LNK', 'Upload picture');
define('REGISTER_TITLE', 'Create an account');
define('LOGIN_LNK', 'Login');
define('LOGOUT_LNK', 'Logout');
define('LASTUP_LNK', 'Last uploads');
define('LASTUP_TITLE', 'Recently uploaded pictures');
define('LASTCOM_TITLE',  'Pictures in order of last commented on');
define('LASTCOM_LNK',  'Last comments');
define('TOPN_TITLE', 'Pictures that have been seen most');
define('TOPN_LNK', 'Most viewed');
define('TOPRATED_TITLE', 'Top rated pictures');
define('TOPRATED_LNK', 'Top rated');
define('SEARCH_TITLE', 'Search Photo Collection');
define('SEARCH_LNK', 'Search');
define('FAV_TITLE', 'My Favorites');
define('FAV_LNK', 'My Favorites');
define('HELP_TITLE', 'HELP');

// lang_gallery_admin_menu
define('UPL_APP_LNK', 'Upload approval');
define('CONFIG_LNK', 'Config');
define('ALBUMS_LNK', 'Albums');
define('CATEGORIES_LNK', 'Categories');
define('USERS_LNK', 'Users');
define('GROUPS_LNK', 'Groups');
define('COMMENTS_LNK', 'Review Comments');
define('SEARCHNEW_LNK', 'Batch add pictures');
define('UTIL_LNK', 'Resize pictures');
define('BAN_LNK', 'Ban Users');

// lang_user_admin_menu
define('ALBMGR_LNK', 'Create / order my albums');
define('MODIFYALB_LNK', 'Modify my albums');
//define('MY_PROF_LNK', 'My profile');

// lang_cat_list
define('CATEGORY', 'Category');
define('ALBUMS', 'Albums');
//define('PICTURES', 'Pictures');

// lang_album_list
define('ALBUM_ON_PAGE', '%d albums on %d page(s)');
// lang_thumb_view
define('DATE', 'DATE');
define('NAME', 'FILE NAME');
define('TITLE', 'TITLE');
define('SORT_DA', 'Sort by date ascending');
define('SORT_DD', 'Sort by date descending');
define('SORT_NA', 'Sort by name ascending');
define('SORT_ND', 'Sort by name descending');
define('SORT_TA', 'Sort by title ascending');
define('SORT_TD', 'Sort by title descending');
define('PIC_ON_PAGE', '%d pictures on %d page(s)');
define('USER_ON_PAGE', '%d users on %d page(s)');
define('SORT_RA', 'Sort by rating ascending');
define('SORT_RD', 'Sort by rating descending');
define('THUMB_RATING', 'RATING');
define('SORT_TITLE', 'Sort pictures by:');

// lang_img_nav_bar
define('THUMB_TITLE', 'Return to the thumbnail page');
define('PIC_INFO_TITLE', 'Display/hide picture information');
define('SLIDESHOW_TITLE', 'Slideshow');
define('SLIDESHOW_DISABLED', 'Slideshow is disabled');
define('SLIDESHOW_DISABLED_MSG', 'This function is for members only, please join.');
define('ECARD_TITLE', 'Send this picture as an e-card');
define('ECARD_DISABLED', 'e-cards are disabled');
define('ECARD_DISABLED_MSG', 'You don\'t have permission to send ecards');
define('PREV_TITLE', 'See previous picture');
define('NEXT_TITLE', 'See next picture');
define('PIC_POS', 'PICTURE %s/%s');
define('NO_MORE_IMAGES', 'There are no more images in this gallery');
define('NO_LESS_IMAGES', 'This is the first image in the gallery');

// lang_rate_pic
define('RATE_THIS_PIC', 'Rate this picture ');
define('NO_VOTES', '(No vote yet)');
define('RATING', '(current rating : %s / 5 with %s votes)');
define('RUBBISH', 'Rubbish');
define('POOR', 'Poor');
define('FAIR', 'Fair');
define('GOOD', 'Good');
define('EXCELLENT', 'Excellent');
define('GREAT', 'Great');

// lang_display_thumbnails
define('FILENAME', 'Filename : ');
define('FILESIZE', 'Filesize : ');
define('DIMENSIONS', 'Dimensions : ');
define('DATE_ADDED', 'Date added : ');

// lang_albmgr_php
define('ALB_NEED_NAME', 'Albums need to have a name !');
define('CONFIRM_MODIFS', 'Are you sure you want to make these modifications ?');
define('NO_CHANGE', 'You did not make any change !');
define('NEW_ALBUM', 'New album');
define('CONFIRM_DELETE1', 'Are you sure you want to delete this album ?');
define('CONFIRM_DELETE2', 'All pictures and comments that it contains will be lost !');
define('SELECT_FIRST', 'Select an album first');
define('ALB_MRG', 'Album Manager');
define('MY_GALLERY', '* My gallery *');
define('NO_CATEGORY', '* No category *');
define('DELETE', 'Delete');
define('NEW', 'New');
define('APPLY_MODIFS', 'Apply modifications');
define('SELECT_CATEGORY', 'Select category');

// lang_catmgr_php
define('MISS_PARAM', 'Parameters required for \'%s\'operation not supplied !');
define('UNKNOWN_CAT', 'Selected category does not exist in database');
define('USERGAL_CAT_RO', 'User galleries category can\'t be deleted !');
define('MANAGE_CAT', 'Manage categories');
define('CONFIRM_DELETE_CAT', 'Are you sure you want to DELETE this category');
//define('CATEGORY', 'Category');
define('OPERATIONS', 'Operations');
define('MOVE_INTO', 'Move into');
define('UPDATE_CREATE', 'Update/Create category');
define('PARENT_CAT', 'Parent category');
define('CAT_TITLE', 'Category title');
define('CAT_DESC', 'Category description');

// $LNG['cpg_config_php']
define('RESTORE_CFG', 'Restore factory defaults');
define('UPD_SUCCESS', 'Coppermine configuration was updated');
define('RESTORE_SUCCESS', 'Coppermine default configuration restored');
define('NAME_A', 'Name ascending');
define('NAME_D', 'Name descending');
define('TITLE_A', 'Title ascending');
define('TITLE_D', 'Title descending');
define('DATE_A', 'Date ascending');
define('DATE_D', 'Date descending');
define('RATING_A', 'Rating ascending');
define('RATING_D', 'Rating descending');

// lang_db_input_php
define('EMPTY_NAME_OR_COM', 'You need to type your name and a comment');
define('COM_ADDED', 'Comment added');
define('ALB_NEED_TITLE', 'You have to provide a title for the album !');
define('NO_UDP_NEEDED', 'No update needed.');
define('ALB_UPDATED', 'Album updated');
define('UNKNOWN_ALBUM', 'Selected album does not exist or you don\'t have permission to upload in this album');
define('NO_PIC_UPLOADED', 'No picture was uploaded !<br /><br />If you have really selected a picture to upload, check that the server allows file uploads...or that the gif is not animated.');
define('ERR_MKDIR', 'Failed to create directory %s !');
define('DEST_DIR_RO', 'Destination directory %s is not writable by the script !');
define('ERR_MOVE', 'Impossible to move %s from %s to %s !');
define('ERR_FSIZE_TOO_LARGE', 'The size of picture you have uploaded is too large (maximum allowed is %s x %s) !');
define('ERR_IMGSIZE_TOO_LARGE', 'The size of the file you have uploaded is too large (maximum allowed is %s KB) !');
define('ERR_INVALID_IMG', 'The file you have uploaded is not a valid image !');
define('ALLOWED_IMG_TYPES', 'You can only upload %s images.');
define('ERR_INSERT_PIC', 'The picture \'%s\' can\'t be inserted in the album ');
define('UPLOAD_SUCCESS', 'Your picture was uploaded successfully.'.(is_admin() ? '' : ' It will be visible after admin approval'));
define('ERR_COMMENT_EMPTY', 'Your comment is empty !');
define('NO_FLOOD', 'Sorry but you are already the author of the last comment posted for this picture<br /><br />Edit the comment you have posted if you want to modify it');
define('REDIRECT_MSG', 'You are being redirected.<br /><br /><br />Click \'CONTINUE\' if the page does not refresh automatically');
define('UPL_SUCCESS', 'Your picture was successfully added');

// lang_delete_php
define('CAPTION', 'Caption');
define('FS_PIC', 'full size image');
define('DEL_SUCCESS', 'successfully deleted');
define('NS_PIC', 'normal size image');
define('ERR_DEL', 'can\'t be deleted');
define('THUMB_PIC', 'thumbnail');
//define('COMMENT', 'comment');
define('IM_IN_ALB', 'image in album');
define('ALB_DEL_SUCCESS', 'Album \'%s\' deleted');
define('ALB_MGR', 'Album Manager');
define('ERR_INVALID_DATA', 'Invalid data received in \'%s\'');
define('CREATE_ALB', 'Creating album \'%s\'');
define('UPDATE_ALB', 'Updating album \'%s\' with title \'%s\' and index \'%s\'');
define('DEL_PIC', 'Delete picture');
define('DEL_ALB', 'Delete album');
define('DEL_USER', 'Delete user');
//define('ERR_UNKNOWN_USER', 'The selected user does not exist !');
define('COMMENT_DELETED', 'Comment was succesfully deleted');

// lang_display_image_php
define('PIC_CONFIRM_DEL', 'Are you sure you want to DELETE this picture ? <br />Comments will also be deleted.');
define('DEL_THIS_PIC', 'DELETE THIS PICTURE');
define('SIZE', '%s x %s pixels');
define('VIEWS', '%s times');
define('SLIDESHOW', 'Slideshow');
define('STOP_SLIDESHOW', 'STOP SLIDESHOW');
define('VIEW_FS', 'Click to view full size image');
define('EDIT_PIC', 'EDIT PICTURE INFO');

// lang_picinfo
define('PIC_INF_TITLE', 'Picture information');
define('PIC_INF_FILENAME', 'Filename');
define('ALBUM_NAME', 'Album name');
define('PIC_INFO_RATING', 'Rating (%s votes)');
define('KEYWORDS', 'Keywords');
define('PIC_INF_FILE_SIZE', 'File Size');
define('PIC_INF_DIMENSIONS', 'Dimensions');
define('DISPLAYED', 'Displayed');
define('CAMERA', 'Camera');
define('DATE_TAKEN', 'Date taken');
define('APERTURE', 'Aperture');
define('EXPOSURE_TIME', 'Exposure time');
define('FOCAL_LENGTH', 'Focal length');
define('COMMENT', 'Comment');
define('ADDFAV', 'Add to Favorites Album');
define('ADDFAVPHRASE', 'Favorites');
define('REMFAV', 'Remove from Favorites Album');
define('IPTCTITLE', 'IPTC Title');
define('IPTCCOPYRIGHT', 'IPTC Copyright');
define('IPTCKEYWORDS', 'IPTC Keywords');
define('IPTCCATEGORY', 'IPTC Category');
define('IPTCSUBCATEGORIES', 'IPTC Sub Categories');
define('BOOKMARK_PAGE', 'Bookmark Image');
define('REMOVEFAV', 'Removed from Favorites Album');
define('ADDEDTOFAV', 'Added to Favorites Album');

// lang_display_comments
define('OK', 'OK');
define('COM_EDIT_TITLE', 'Edit this comment');
define('CONFIRM_DELETE_COM', 'Are you sure you want to delete this comment ?');
define('ADD_YOUR_COMMENT', 'Add your comment');
define('COM_NAME', 'Name');
//define('COMMENT', 'Comment');
define('YOUR_NAME', 'Anon');

// lang_fullsize_popup
define('CLICK_TO_CLOSE', 'Click image to close this window');

// lang_ecard_php
define('E_TITLE', 'Send an e-card');
define('INVALID_EMAIL', '<b>Warning</b> : invalid email address !');
define('E_ECARD_TITLE', 'An e-card from %s for you');
define('VIEW_ECARD', 'If the e-card does not display correctly, click this link');
define('VIEW_MORE_PICS', 'Click this link to view more pictures !');
define('SEND_SUCCESS', 'Your ecard was sent');
define('SEND_FAILED', 'Sorry but the server can\'t send your e-card...');
define('FROM', 'From');
define('_YOUR_NAME', 'Your name');
define('YOUR_EMAIL', 'Your email address');
define('TO', 'To');
define('RCPT_NAME', 'Recipient name');
define('RCPT_EMAIL', 'Recipient email address');
define('GREETINGS', 'Greetings');
define('MESSAGE', 'Message');
define('ECARD_LINK_CORRUPT', 'Sorry but the e-card data has been corrupted by your mail client, please try pasting the link in your browser'); //NEW

// lang_editpics_php
define('PIC_INFO', 'Picture info');
define('ALBUM', 'Album');
define('EDIT_TITLE', 'Title');
define('DESC', 'Description');
//define('KEYWORDS', 'Keywords');
define('PIC_INFO_STR', '%sx%s - %sKB - %s views - %s votes');
define('APPROVE', 'Approve picture');
define('POSTPONE_APP', 'Postpone approval');
//define('DEL_PIC', 'Delete picture');
define('READ_EXIF', 'Read EXIF info again');
define('RESET_VIEW_COUNT', 'Reset view counter');
define('RESET_VOTES', 'Reset votes');
define('DEL_COMM', 'Delete comments');
define('UPL_APPROVAL', 'Upload approval');
define('EDIT_PICS', 'Edit pictures');
define('SEE_NEXT', 'See next pictures');
define('SEE_PREV', 'See previous pictures');
define('N_PIC', '%s pictures');
define('N_OF_PIC_TO_DISP', 'Number of picture to display');
define('APPLY', 'Apply modifications');

// lang_groupmgr_php
define('GROUP_NAME', 'Group name');
define('DISK_QUOTA', 'Disk quota');
define('CAN_RATE', 'Can rate pictures');
define('CAN_SEND_ECARDS', 'Can send ecards');
define('CAN_POST_COM', 'Can post comments');
define('CAN_UPLOAD', 'Can upload pictures');
define('CAN_HAVE_GALLERY', 'Can have a personal gallery');
//define('APPLY', 'Apply modifications');
define('CREATE_NEW_GROUP', 'Create new group');
define('DEL_GROUPS', 'Delete selected group(s)');
define('CONFIRM_DEL', 'Warning, when you delete a group, users that belong to this group will be transfered to the \'Registered\' group !\\n\\nDo you want to proceed ?');
define('GROUP_TITLE', 'Manage user groups');
define('APPROVAL_1', 'Pub. Upl. approval (1)');
define('APPROVAL_2', 'Priv. Upl. approval (2)');
define('NOTE1', 'Uploads in a public album need admin approval');
define('NOTE2', 'Uploads in an album that belong to the user need admin approval');

// lang_index_php
define('WELCOME', 'Welcome !');

// lang_album_admin_menu
define('CONFIRM_DELETE_ALB', 'Are you sure you want to DELETE this album ? <br />All pictures and comments will also be deleted.');
//define('DELETE', 'DELETE');
define('MODIFY', 'PROPERTIES');
//define('EDIT_PICS', 'EDIT PICS');

// lang_list_categories
define('HOME', _HOME);
define('STAT1', '<b>[pictures]</b> pictures in <b>[albums]</b> albums and <b>[cat]</b> categories with <b>[comments]</b> comments viewed <b>[views]</b> times');
define('STAT2', '<b>[pictures]</b> pictures in <b>[albums]</b> albums viewed <b>[views]</b> times');
define('XX_S_GALLERY', '%s\'s Gallery');
define('STAT3', '<b>[pictures]</b> pictures in <b>[albums]</b> albums with <b>[comments]</b> comments viewed <b>[views]</b> times');

// lang_list_users
define('USER_LIST', 'User list');
define('NO_USER_GAL', 'There are no user galleries');
define('N_ALBUMS', '%s album(s)');
define('N_PICS', '%s picture(s)');

// lang_list_albums
define('N_PICTURES', '%s pictures');
define('LAST_ADDED', ', last one added on %s');

// lang_modifyalb_php
define('UPD_ALB_N', 'Update album %s');
define('GENERAL_SETTINGS', 'General settings');
define('ALB_TITLE', 'Album title');
define('ALB_CAT', 'Album category');
define('ALB_DESC', 'Album description');
define('ALB_THUMB', 'Album thumbnail');
define('ALB_PERM', 'Permissions for this album');
define('CAN_VIEW', 'Album can be viewed by');
define('MOD_CAN_UPLOAD', 'Visitors can upload pictures');
define('CAN_POST_COMMENTS', 'Visitors can post comments');
define('MOD_CAN_RATE', 'Visitors can rate pictures');
define('USER_GAL', 'User Gallery');
define('NO_CAT', '* No category *');
define('ALB_EMPTY', 'Album is empty');
define('LAST_UPLOADED', 'Last uploaded');
define('PUBLIC_ALB', 'Everybody (public album)');
define('ME_ONLY', 'Me only');
define('OWNER_ONLY', 'Album owner (%s) only');
define('GROUPP_ONLY', 'Members of the \'%s\' group');
define('ERR_NO_ALB_TO_MODIFY', 'There is no album you may modify. Create an allbum first!');
define('UPDATE', 'Update album');

// lang_rate_pic_php
define('ALREADY_RATED', 'Sorry but you have already rated this picture');
define('RATE_OK', 'Your vote was accepted');

// lang_register_php
define('USERNAME', 'Username');
define('GROUP', 'Group');
define('DISK_USAGE', 'Disk usage');
define('X_S_PROFILE', '%s\'s profile');

// lang_reviewcom_php
define('REVIEW_TITLE', 'Review comments');
define('NO_COMMENT', 'There are no comments to review');
define('N_COMM_DEL', '%s comment(s) deleted');
define('N_COMM_DISP', 'Number of comments to display');
define('R_SEE_PREV', 'See previous');
define('R_SEE_NEXT', 'See next');
define('R_DEL_COMM', 'Delete selected comments');

// lang_search_php
define('S_SEARCH', 'Search the image collection');

// lang_search_new_php
define('PAGE_TITLE', 'Search new pictures');
define('SELECT_DIR_MSG', 'This function allows you to add a batch of pictures that you have uploaded on your server.<br /><br />Select the directory where you have uploaded your pictures');
define('NO_PIC_TO_ADD', 'There is no picture to add');
define('NEED_ONE_ALBUM', 'You need at least one album to use this function');
define('WARNING', 'Warning');
define('CHANGE_PERM', 'the script can\'t write in this directory, you need to change its mode to 755 or 777 before trying to add the pictures !');
define('TARGET_ALBUM', '<b>Put pictures of &quot;</b>%s<b>&quot; into </b>%s');
define('FOLDER', 'Folder');
define('IMAGE', 'Image');
//define('ALBUM', 'Album');
define('RESULT', 'Result');
define('DIR_RO', 'Not writable. ');
define('DIR_CANT_READ', 'Not readable. ');
define('INSERT', 'Adding new pictures to the gallery');
define('LIST_NEW_PIC', 'List of new pictures');
define('INSERT_SELECTED', 'Insert selected pictures');
define('NO_PIC_FOUND', 'No new picture was found');
define('BE_PATIENT', 'Please be patient, the script needs time to add the pictures');
define('SN_NOTES', '<ul><li><b>OK</b> : means that the picture was succesfully added<li><b>DP</b> : means that the picture is a duplicate and is already in the database<li><b>PB</b> : means that the picture could not be added, check your configuration and the permission of directories where the pictures are located<li>If the OK, DP, PB \'signs\' does not appear click on the broken picture to see any error message produced by PHP<li>If your browser timeout, hit the reload button</ul>');
define('SELECT_ALBUM', 'Select album');
define('NO_ALBUM', 'No album name was selected, click back and select an album to put your pictures in');

// lang_upload_php
define('UP_TITLE', 'Upload picture');
define('MAX_FSIZE', 'Maximum allowed file size is %s KB');
//define('ALBUM', 'Album');
define('PICTURE', 'Picture');
define('PIC_TITLE', 'Picture title');
define('DESCRIPTION', 'Picture description');
define('UP_KEYWORDS', 'Keywords (separate with spaces)');
define('ERR_NO_ALB_UPLOADABLES', 'Sorry there is no album where you are allowed to upload pictures. Please create an album first!');

// $LNG['cpg_usermgr_php']
define('U_TITLE', 'Manage users');
//define('NAME_A', 'Name ascending');
//define('NAME_D', 'Name descending');
define('GROUP_A', 'Group ascending');
define('GROUP_D', 'Group descending');
define('REG_A', 'Reg date ascending');
define('REG_D', 'Reg date descending');
define('PIC_A', 'Pic count ascending');
define('PIC_D', 'Pic count descending');
define('DISKU_A', 'Disk usage ascending');
define('DISKU_D', 'Disk usage descending');
define('SORT_BY', 'Sort users by');
define('ERR_NO_USERS', 'User table is empty !');
define('ERR_EDIT_SELF', 'You can\'t edit your own profile, use the \'My profile\' link for that');
define('EDIT', 'EDIT');
//define('DELETE', 'DELETE');
define('U_NAME', 'User name');
//define('GROUP', 'Group');
define('INACTIVE', 'Inactive');
//define('OPERATIONS', 'Operations');
define('PICTURES', 'Pictures');
define('DISK_SPACE', 'Space used / Quota');
define('REGISTERED_ON', 'Registered on');
define('U_USER_ON_P_PAGES', '%d users on %d page(s)');
define('USER_CONFIRM_DEL', 'Are you sure you want to DELETE this user ? <br />All his pictures and albums will also be deleted.');
define('MAIL', 'MAIL');
define('ERR_UNKNOWN_USER', 'Selected user does not exist !');
define('MODIFY_USER', 'Modify user');
define('NOTE_LIST', '<li>If you don\'t want to change the current password, leave the \"password\" field blank');
define('PASSWORD', 'Password');
define('USER_ACTIVE_CP', 'User is active');
define('USER_GROUP_CP', 'User group');
define('USER_EMAIL', 'User email');
define('USER_WEB_SITE', 'User web site');
define('CREATE_NEW_USER', 'Create new user');
define('USER_FROM', 'User location');
define('USER_INTERESTS', 'User interests');
define('USER_OCC', 'User occupation');

// lang_util_php
define('UTIL_TITLE', 'Resize pictures');
define('WHAT_IT_DOES', 'What it does');
define('WHAT_UPDATE_TITLES', 'Updates titles from filename');
define('WHAT_DELETE_TITLE', 'Deletes titles');
define('WHAT_REBUILD', 'Rebuilds thumbnails and resized photos');
define('WHAT_DELETE_ORIGINALS', 'Deletes original sized photos replacing them with the resized version');
define('U_FILE', 'File');
define('TITLE_SET_TO', 'title set to');
define('SUBMIT_FORM', 'submit');
define('UPDATED_SUCCESFULLY', 'updated succesfully');
define('ERROR_CREATE', 'ERROR creating');
define('CONTIN', 'Process more images');
define('MAIN_SUCCESS', 'The file %s was successfully used as main picture');
define('ERROR_RENAME', 'Error renaming %s to %s');
define('ERROR_NOT_FOUND', 'The file %s was not found');
define('U_BACK', 'back to main');
define('THUMBS_WAIT', 'Updating thumbnails and/or resized images, please wait...');
define('THUMBS_CONTINUE_WAIT', 'Continuing to update thumbnails and/or resized images...');
define('TITLES_WAIT', 'Updating titles, please wait...');
define('DELETE_WAIT', 'Deleting titles, please wait...');
define('REPLACE_WAIT', 'Deleting originals and replacing them with resized images, please wait..');
define('INSTRUCTION', 'Quick instructions');
define('INSTRUCTION_ACTION', 'Select action');
define('INSTRUCTION_PARAMETER', 'Set parameters');
define('INSTRUCTION_ALBUM', 'Select album');
define('INSTRUCTION_PRESS', 'Press %s');
define('U_UPDATE', 'Update thumbs and/or resized photos');
define('UPDATE_WHAT', 'What should be updated');
define('UPDATE_THUMB', 'Only thumbnails');
define('UPDATE_PIC', 'Only resized pictures');
define('UPDATE_BOTH', 'Both thumbnails and resized pictures');
define('UPDATE_NUMBER', 'Number of processed images per click');
define('UPDATE_OPTION', '(Try setting this option lower if you experience timeout problems)');
define('FILENAME_TITLE', 'Filename &rArr; Picture title');
define('FILENAME_HOW', 'How should the filename be modified');
define('FILENAME_REMOVE', 'Remove the .jpg ending and replace _ (underscore) with spaces');
define('FILENAME_EURO', 'Change 2003_11_23_13_20_20.jpg to 23/11/2003 13:20');
define('FILENAME_US', 'Change 2003_11_23_13_20_20.jpg to 11/23/2003 13:20');
define('FILENAME_TIME', 'Change 2003_11_23_13_20_20.jpg to 13:20');
define('UT_DELETE', 'Delete picture titles or original size photos');
define('DELETE_TITLE', 'Delete picture titles');
define('DELETE_ORIGINAL', 'Delete original size photos');
define('DELETE_REPLACE', 'Deletes the original images replacing them with the sized versions');
//define('SELECT_ALBUM', 'Select album');

// lang_pagetitle_php
define('VIEWING', 'Viewing Photo');
define('USR', '\'s Photo Gallery');
define('PHOTOGALLERY', 'Photo Gallery');

$LNG = array(
	'Public albums' => 'Public albums',
	'Max Aspect' => 'Max Aspect',
	'Height' => 'Height',
	'Width' => 'Width',

	'cpg_usermgr_php' => array(
		'name_a' => 'Name ascending',
		'name_d' => 'Name descending',
		'group_a' => 'Group ascending',
		'group_d' => 'Group descending',
		'reg_a' => 'Reg date ascending',
		'reg_d' => 'Reg date descending',
		'pic_a' => 'Pic count ascending',
		'pic_d' => 'Pic count descending',
		'disku_a' => 'Disk usage ascending',
		'disku_d' => 'Disk usage descending',
	),

	'cpg_meta_album_names' => array(
		'random' => 'Random pictures',
		'lastup' => 'Last additions',
		'lastupby' => 'My Last additions', // new 1.2.2
		'lastalb' => 'Last updated albums',
		'lastcom' => 'Last comments',
		'lastcomby' => 'My Last comments', // new 1.2.2
		'topn' => 'Most viewed',
		'toprated' => 'Top rated',
		'lasthits' => 'Last viewed',
		'search' => 'Search results',
		'favpics' => 'Favorite Pictures' // changed in cpg1.2.0nuke
	),

	'EXIF' => array(
		'Camera' => CAMERA,
		'DateTaken' => DATE_TAKEN,
		'Aperture' => APERTURE,
		'ExposureTime' => EXPOSURE_TIME,
		'FocalLength' => FOCAL_LENGTH,
		'Comment' => COMMENT
	),

	'IPTC' => array(
		'Title' => IPTCTITLE,
		'Copyright' => IPTCCOPYRIGHT,
		'Keywords' => IPTCKEYWORDS,
		'Category' => IPTCCATEGORY,
		'SubCategories' => IPTCSUBCATEGORIES,
	),

	// General settings
	'General settings' => 'General settings',
	'Gallery name' => 'Gallery name',
	'Gallery description' => 'Gallery description',
	'Page Specific Titles' => 'Page Specific Titles',
	// Album list view
	'Album list view' => 'Album list view',
	'Number of levels of sub-categories to display' => 'Number of levels of sub-categories to display',
	'Number of albums to display' => 'Number of albums to display',
	'Number of columns for the album list' => 'Number of columns for the album list',
	'Size of thumbnails in pixels' => 'Size of thumbnails in pixels',
	'The content of the main page' => 'The content of the main page',
	'Show first level album thumbnails in categories' => 'Show first level album thumbnails in categories',
	// Thumbnail view
	'Thumbnail view' => 'Thumbnail view',
	'Number of columns on thumbnail page' => 'Number of columns on thumbnail page',
	'Number of rows on thumbnail page' => 'Number of rows on thumbnail page',
	'Maximum number of tabs to display' => 'Maximum number of tabs to display',
	'Display picture caption (in addition to title) below the thumbnail' => 'Display picture caption (in addition to title) below the thumbnail',
	'Display number of comments below the thumbnail' => 'Display number of comments below the thumbnail',
	'Default sort order for pictures' => 'Default sort order for pictures',
	'Minimum number of votes for a picture to appear in the \'top-rated\' list' => 'Minimum number of votes for a picture to appear in the \'top-rated\' list',
	'Alts and title tags of thumbnail show title and keyword instead of picinfo' => 'Alts and title tags of thumbnail show title and keyword instead of picinfo',
	// Display Image and Comment settings
	'Display Image and Comment settings' => 'Display Image and Comment settings',
	'Picture information are visible by default' => 'Picture information are visible by default',
	'Filter bad words in comments' => 'Filter bad words in comments',
	'Allow several consecutive comments on one pic from the same user' => 'Allow several consecutive comments on one pic from the same user',
	'Email site admin upon comment submission' => 'Email site admin upon comment submission',
	'Max length for an image description' => 'Max length for an image description',
	'Max number of characters in a word' => 'Max number of characters in a word',
	'Max number of lines in a comment' => 'Max number of lines in a comment',
	'Maximum length of a comment' => 'Maximum length of a comment',
	'Show film strip' => 'Show film strip',
	'Number of items in film strip' => 'Number of items in film strip',
	'Allow viewing of full size pic by anonymous' => 'Allow viewing of full size pic by anonymous',
	'Number of days between being able to vote on the same image' => 'Number of days between being able to vote on the same image',
	'Show fullsize picture in slideshow' => 'Show fullsize picture in slideshow',
	'Show blocks on the right of displayimage if right blocks are on at module level?' => 'Show blocks on the right of displayimage if right blocks are on at module level?',
	// Pictures and thumbnails settings
	'Pictures and thumbnails settings' => 'Pictures and thumbnails settings',
	'Quality for JPEG files' => 'Quality for JPEG files',
	'Place watermark on image' => 'Place watermark on image',
	'Max dimension of a thumbnail' => 'Max dimension of a thumbnail',
	'Use dimension' => 'Use dimension',
	'Create intermediate pictures' => 'Create intermediate pictures',
	'Max width or height of an intermediate picture' => 'Max width or height of an intermediate picture',
	'Max size for uploaded pictures' => 'Max size for uploaded pictures',
	'Max width or height for uploaded pictures (pixels)' => 'Max width or height for uploaded pictures (pixels)',
	// User settings
	'User settings' => 'User settings',
	'Users can can have private albums' => 'Users can can have private albums',
	'Show Users avatar instead of private album picture' => 'Show Users avatar instead of private album picture',
	// Custom fields for image description
	'Custom fields for image description (leave blank if unused)' => 'Custom fields for image description (leave blank if unused)',
	'Field 1 name' => 'Field 1 name',
	'Field 2 name' => 'Field 2 name',
	'Field 3 name' => 'Field 3 name',
	'Field 4 name' => 'Field 4 name',
	// Pictures and thumbnails advanced settings
	'Pictures and thumbnails advanced settings' => 'Pictures and thumbnails advanced settings',
	'Show private album Icon to unlogged user' => 'Show private album Icon to unlogged user',
	'Method for resizing images' => 'Method for resizing images',
	'Allowed image types' => 'Allowed image types',
	'Read EXIF data in JPEG files' => 'Read EXIF data in JPEG files',
	'Read IPTC data in JPEG files' => 'Read IPTC data in JPEG files',
	'The album directory' => 'The album directory',
	'The directory for user pictures' => 'The directory for user pictures',
	'The prefix for intermediate pictures' => 'The prefix for intermediate pictures',
	'The prefix for thumbnails' => 'The prefix for thumbnails',
	'Picinfo display filename' => 'Picinfo display filename',
	'Picinfo display album name' => 'Picinfo display album name',
	'Picinfo display_file_size' => 'Picinfo display_file_size',
	'Picinfo display_dimensions' => 'Picinfo display_dimensions',
	'Picinfo display fav album link' => 'Picinfo display fav album link',
	// Miscellaneous settings
	'Miscellaneous settings' => 'Miscellaneous settings',
	'Enable debug mode' => 'Enable debug mode',
);
