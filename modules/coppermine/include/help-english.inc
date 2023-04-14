<?php 
/***************************************************************************  
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
  **************************************************************************
  Last modification notes:
  $Source: /public_html/modules/coppermine/include/help-english.inc,v $
  $Revision: 9.3 $
  $Author: trevor $
  $Date: 2005/05/10 20:27:33 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
global $userinfo;
define("GTDOC_ADMIN_SUMMARY", '<blockquote>This documentation is intended to help you use this photo script, <i>Coppermine</i>. Depending on whether you are logged in as admin or member or anonymous you will see something different on this page. If you have a question that is not answered here please check the forum at <a class="doc_desc" href="http://dragonflycms.org/Forums/viewforum/f=30.html">dragonflycms.org</a><br />To control the look of this document you may edit the css file located the modules/coppermine/docs/styles.css directory. <br /><a href="'.get_uri().'#menu">Skip to menu info</a><br /><a href="'.get_uri().'#admenu">Skip to admin menu info</a><br /><a href="'.get_uri().'#config">Skip to config info</a></blockquote>');
define("GTDOC_USER_SUMMARY", '<blockquote>The program that displays the photos on this site is called <i>Coppermine</i>. This documentation is intended to help you use this photo script to interact with this site.<br /><a href="'.get_uri().'#menu">Skip to menu info</a><br /><a href="'.get_uri().'#admenu">Skip to admin menu info</a><br /></blockquote>');
define("GTDOC_ANON_SUMMARY", '<blockquote>The program that displays the photos on this site is called <i>Coppermine</i>. This documentation is intended to help you use this photo script to interact with this site. Please <a href="'.getlink('Your_Account').'">login</a> to your account or <a href="'.getlink('Your_Account&amp;file=register').'">join</a> to see more information</blockquote>');

define("GTDOC_OVERVIEW_TITLE", 'Categories, albums and pictures');

define("GTDOC_SITEADM_OVERVIEW_DESC", 'The script works in the following way :<br /><ul><li>Pictures are stored in albums</li><li>Albums may be organised in categories</li><li>Categories can be nested (subcategories) </li></ul><p>If you don\'t have many albums, you don\'t need to use categories. In that case you don\'t create any category and all your albums will appear on the main page of the script.<br />There is a special category named "<b>User galleries</b>". This category can\'t be deleted. If a user belongs to a group where "can have a personal  gallery" is set to YES, he will have the right to create his own albums and his gallery will be a sub-category of "User galleries".<br />The administrator can create albums in any category, you must be logged in as a user to post to your user gallery. Regular users can only create albums in "User galleries/Their_username".<br />');

define("GTDOC_ADM_OVERVIEW_DESC", '<p>The script works in the following way :<br /><ul><li>Pictures are stored in albums</li><li>Albums may be organised in categories</li><li>Categories can be nested (subcategories) </li></ul><p>If you don\'t have many albums, you don\'t need to use categories. In that case you don\'t create any category and all your albums will appear on the main page of the script.<br />There is a special category named "<b>User galleries</b>". This category can\'t be deleted. If a user belongs to a group where "can have a personal  gallery" is set to YES, he will have the right to create his own albums  and his gallery will be a sub-category of "User galleries".<br />The administrator can create albums in any category. Regular users can only create albums in "User galleries/Their_username".<br />');

define("GTDOC_CANUP_USER_OVERVIEW_DESC", 'The photo part of the site is arranged in the following way :<br /><ul><li>Pictures are stored in albums</li><li>Albums may be organized in categories</li><li>Categories can be nested (subcategories) </li></ul>There is a special category named "<b>User galleries</b>". If you belong to a group where "can have a personal  gallery" is set to YES, you will have the right to create your own albums and your gallery will be a sub-category of "User galleries".<br />You may only create albums in "User galleries/' . $userinfo['username'] . '"<br /> You may upload to any gallery enabled by the administrator');

define("GTDOC_USER_OVERVIEW_DESC", '<p>The photo part of the site is arranged in the following way :<br /><ul><li>Pictures are stored in albums</li><li>Albums may be organized in categories</li><li>Categories can be nested (subcategories) </li></ul>As a member you may upload to any gallery enabled by the administrator');



define("GTDOC_ALBLIST_TITLE", 'Album List'); //$lang_main_menu['alb_list_title']
define("GTDOC_ALBLIST_DESC", 'This goes to the page that lists all the categories, the gallery home page<a id="menu" name="menu"></a>');

define("GTDOC_MY_GAL_LNK_TITLE", 'My gallery');
define("GTDOC_MY_GAL_LNK_DESC", 'View your user gallery');

/*
define("GTDOC_ADMINMODE_TITLE", 'Admin mode &amp; User mode');
define("GTDOC_SITEADM_ADMINMODE_DESC", '<a id="menu" name="admenu"></a>When you are logged in as an admin, the script has two modes of operation: Admin mode &amp; User mode. You switch between Admin &amp; User mode by clicking on the corresponding link in the menu bar at the top of the screen.<br />When you are in admin mode, you can administer the gallery and the following menu bar appears :<br />' . theme_admin_mode_menu() . '<br />When you are in user mode you are just a regular user and can\'t access the admin pages unless you are the site admin.<br />');

define("GTDOC_ADM_ADMINMODE_DESC", '<p>When you are logged in, the script has two modes of operation: Admin mode &amp; User mode. You switch between Admin &amp; User mode by clicking on the corresponding link in the menu bar at the top of the screen.<br />When you are in admin mode, you can administer the gallery and the following menu bar appears :<br />' . theme_admin_mode_menu() . '<br />When you are in user mode you are just a regular user and can\'t access the admin pages.<br /> You can\'t access the config link as only the site admin can do this.');

define("GTDOC_USER_ADMINMODE_DESC", '<p>When you are logged in, the script has two modes of operation: Admin mode &amp; User mode. You switch between Admin &amp; User mode by clicking on the corresponding link in the menu bar at the top of the screen.<br />When you are in admin mode, you can administer your personal gallery<br />When you are in user mode you are just a regular user and can\'t access the personal gallery admin pages.<br />');
*/
define("GTDOC_USERUPLOAD_TITLE", 'Upload your Photo'); // file upload.php

define("GTDOC_SITEADM_USERUPLOAD_DESC", 'Your Users/Members may upload photos to groups where they are a member or their user gallery. To allow uploads you must allow them in both the <a href="'.getlink("&amp;file=groupmgr").'">user group permissions</a> and the <a href="'.getlink("&amp;file=modifyalb").'">albums permissions.</a><br /><br /> Using the batchupload feature is faster to upload multiple pics, and you may the edit the picinfo of each picture in an album using the Edit Pics link next to the album thumb in album list view or the link under the bigger photo in displayimage.');

define("GTDOC_ADM_USERUPLOAD_DESC", 'Your Users/Members may upload photos to groups where they are a member or their user gallery. As an coppermine admin you may upload to any folder After clicking the upload link choose a category from the list box, click on the Browse button and find the image you want to upload from your computer.');

define("GTDOC_CANUG_USER_USERUPLOAD_DESC", 'As a Register User/Member you may upload photos to albums allowed by the administrator, and  your user gallery. To upload just click the upload link when logged in. After clicking the upload link choose a category from the list box, click on the Browse button next to the "Picture" field and find the image you want to upload from your computer. Fill out the rest of the fields in the form and click "upload Picture" ');

define("GTDOC_USER_USERUPLOAD_DESC", 'As a Register User/Member you may upload photos to albums allowed by the administrator. To upload just click the upload link when logged in. After clicking the upload link choose a category from the list box, click on the Browse button next to the "Picture" field and find the image you want to upload from your computer. Fill out the rest of the fields in the form and click "upload Picture"');

define("GTDOC_LOGIN_LNK_TITLE", 'Login'); // // module Your_Account
define("GTDOC_LOGIN_LNK_DESC", 'Login to use registered member functions such as uploading photos and sending ecards');

define("GTDOC_LOGOUT_LNK_TITLE", 'Log out'); // module Your_Account
define("GTDOC_LOGOUT_LNK_DESC", 'Logout to finish your session');

define("GTDOC_META_LNK_TITLE", 'Last uploads :: Last comments :: Most viewed :: Top rated'); // album=*metaname*
define("GTDOC_META_LNK_DESC", 'These links go to the "meta albums" Last uploads - Last comments - Most viewed -  Top rated. These albums are made of pictures from the category/album that you are in at the time that you click them, and if not in a category/album the pictures shown with be from all the categories/albums');

define("GTDOC_SEARCH_LNK_TITLE", 'Searching the gallery'); //file search.php
define("GTDOC_SEARCH_LNK_DESC", 'When you click the search link you can search for photos by keyword.');

define("GTDOC_FAV_LNK_TITLE", 'My Favorites'); //album favpics
define("GTDOC_FAV_LNK_DESC", 'This is a temporary album that lasts as long as your cookie from us. Use the link below the pictures to add picture to this album.');
define("GTDOC_ADMIN_FUNC_TITLE", 'ADMIN MENU');
define("GTDOC_ADMIN_FUNC_DESC", 'The following are functions you are able to do while in admin mode, using the admin menu');
define("GTDOC_UPLOAD_APP_TITLE", 'Upload approval'); //album favpics
define("GTDOC_UPLOAD_APP_DESC", 'This is where you can approve photos for posting, as well as edit their descriptions. ');

define("GTDOC_BATCH_AD_TITLE", 'Batch add pictures'); // file searchnew.php
define("GTDOC_BATCH_AD_DESC", 'Click this link to add pictures you have uploaded to the albums directory via ftp. You must have already created the categories and albums that you want to put them in. If you create folders via ftp you will need to change the permissions on them to 777 before using this link. You may the edit the picinfo of each picture in an album using the Edit Pics link next to the album thumb in album list view or the link under the bigger photo in displayimage. You may want to set the albums properties visibility to admin only while you batch add and add descriptions');

define("GTDOC_REVEIW_COM_TITLE", 'Review Posted Comments'); // file reviewcom.php
define("GTDOC_REVEIW_COM_DESC", 'Read, modify and delete comments posted regarding pictures');

define("GTDOC_BANUSERS_TITLE", 'Ban Users');
define("GTDOC_BANUSERS_DESC", 'Here you can control the banning of users, this link uses the built in function of phpBB for nuke, full instructions are there.');
define("GTDOC_GROUPCP_TITLE", 'The group control panel');
define("GTDOC_GROUPCP_DESC", 'This is where you define what members of a group can and can\'t do.<br />The disk quota applies only for groups where "Can have a personal" gallery has been set to "YES". Only pictures uploaded by a user in his personal gallery are included in the quota.<br />Use the <b>anonymous</b> group to define what non-registered users can and can\'t do. Quota and "Can have a personal gallery" are meaningless for anonymous users.<br />Bear in mind that if a user is a member of a group where "can rate pictures", "can post comments" or "can upload pictures" is set YES, he will have the right to perform these operations only in albums where they are allowed, ie. uploading pictures will only be possible in albums where "Visitors can upload pictures" has been set to YES.<br />If "can have a personal gallery" is set to YES, the members of the group  will have their own gallery in the "User galleries" category where they will be able to create their own albums. If you allow member to upload photos but not have user galleries the user album folders(albums/userpics/10001-?) will still be used to store their uploaded photos.<br />If "priv. upl. approval" is set to NO, pictures uploaded by members of the group in albums created in their own gallery won\'t need to be approved by the admin. You may add addition groups but you will need to add users to this group manually by going to the user manager.<br />');

define("GTDOC_USER_MGR_TITLE", 'Users');
define("GTDOC_USER_MGR_DESC", 'Here you change a specific user\'s group, view their the amount of their quota that is being used as well as make change to the pictures they are able to see though groups checkboxes. For more information see the Permissions part of the document.');

define("GTDOC_CREATE_ORDER_ALBUM_TITLE", 'Album Management'); // admin link file albmgr.php
define("GTDOC_CREATE_ORDER_ALBUM_DESC", 'This is where you can create, delete or change the order of albums. If you want a personal album this is the first stop otherwise create the category that you want your new album in if any. At this time this is a javascript feature so make sure it is on before accessing this page. Click the new button first. Then type the name in the input area below the order album section, and save you modification. This is also where you can delete albums click on the album name and click delete. To change the order of your albums click on the album name you want to move and click on the up or down button. To change the name of an album click on the name and edit the text below,and click Apply modifications. ');

define("GTDOC_USER_CREATE_ORDER_ALBUM_TITLE", 'Create / order my albums'); // user file albmgr.php
define("GTDOC_USER_CREATE_ORDER_ALBUM_DESC", 'If you want a personal album this is the first stop. Click the new button. Then type your album name of choice, and click Apply modifications. You can have as many personal album as you want but are limited by the disk space alotted to your group by the administor. At this time this is a javascript feature so make sure it is on before accessing this page. Click the new button first. Then type the name in the input area below the order album section,To change the order of your albums click on the album name you want to move and click on the up or down button. To change the name of an album click on the name and edit the text below,and click Apply modifications. This is also where you can delete albums click on the album name and click delete.');

define("GTDOC_MY_PROFILE_TITLE", 'My Profile'); // admin link file albmgr.php
define("GTDOC_MY_PROFILE_DESC", 'This is where you can view your group membership(s) and disk usage/quota'); // user file name=coppermine&file=profile&op=edit_profile

define("GTDOC_MODALBUM_TITLE", 'Modifying albums/picture'); //file modifyalb.php
define("GTDOC_MODALBUM_DESC", 'This menu is adjacent to each albums description in the category or album list view.<br /><b>Delete</b> allows you to delete the album and all pictures it contains.<br /><b>Properties</b> allows you to modify the name, description and permissions of the album, see more about this feature in the Album properties section below<br /><b>Edit pics</b> allows you to modify the title/caption/keywords etc... of the pictures in the album');

define("GTDOC_ALBUMPROPS_TITLE", 'Album properties');
define("GTDOC_USER_ALBUMPROPS_TITLE", 'Modify my albums');
define("GTDOC_ALBUMPROPS_DESC", '<br />The "Album" drop down list at the top right allows you move between albums to set preferences. The "Album category" drop down list allows you to move an album between  categories. If you set this to "* No category *" then the album will  be displayed on your main page.<br />The "Album description" field is shown next the albums thumbnail in the album list. Coppermine understands the following bbCodes (the same bbCodes that are used by phpBB) in image and album description<br /><ul><li>[b]<b>bold</b>[/b]</li><li>[i]<i>italic</i>[/i]</li><li>[url=http://url]URL text</a>[/url]</li><li>[email]<a href="javascript:;">user@domain.tld</a>[/email] </li></ul>The thumbnail is the picture that will represent the album in the album list.<br /><b>Who can see this?</b>You can define who can view the pictures of this album. <br />When "visitors can upload picture" is set to YES, it is possible to upload pictures in this album. <b>Note that a visitor will have the right to upload pictures into an album where this option is set to YES only if he is a member of a group for which "Can upload pictures" is set to YES. </b>Non registered users are members of the "Anonymous" group.<br />The same rules as above apply for "Visitors can post comments" and "Visitors can rate pictures".');
define("GTDOC_USER_ALBUMPROPS_DESC", '<br />The "Album category" drop down list allows you move between your user albums to set preferences. The "Album description" field is shown next the albums thumbnail in the album list.  Coppermine understands the following bbCodes (the same bbCodes that are used by phpBB) in image and album descriptions<br /><ul><li>[b]<b>bold</b>[/b]</li><li>[i]<i>italic</i>[/i]</li><li>[url=http://url]URL text</a>[/url]</li><li>[email]<a href="javascript:;">user@domain.tld</a>[/email] </li></ul>The thumbnail is the picture that will represent the album in the album list.<br /> <b>Who can see this? </b> If your administrator has set "Users can can have private albums" to YES in the configuration page, you can define who can view the pictures of this album your choices are everybody,me only, and members of the registered group logged in (members only).<br /> Non registered users are members of the "Anonymous" group.<br />The same rules as above apply for "Visitors can post comments" and "Visitors can rate pictures".');

define("GTDOC_PERMISSIONS_TITLE", 'Permissions');
define("GTDOC_SITEADM_PERMISSIONS_DESC", 'This gallery comes with a complex set of permissions. Users can be part of Anonymous, Members or any other group you create in the user group control panel. Registered users are by default part of the registered users group. You can add a group to an users groups click on the Users link in the menu and find the user in the list or enter their name in the edit user box the select the main group they are part of, then using the checkboxes add other group you want them to be part of ( check boxes only give permission to see pictures of that group). <br />Using the album properties link next to each album. You can set which group can see a particular album. You can then set in your config page whether anonymous can see the icons for the private albums, if this is set to off and a category only has albums that a user can\'t see the category won\'t be shown either. Test using separate browser with different logins(member types) that you have set these permissions right. To summarize you must set: user groups,user\'s group,album properties,anon can see private in config to have it all work.');
define("GTDOC_ADM_PERMISSIONS_DESC", 'This gallery comes with a complex set of permissions. Users can be part of Anonymous, Members or any other group you create in the user group control panel. Registered users are by default part of the registered users group. You can add a group to an users groups click on the Users link in the menu and find the user in the list or enter their name in the edit user box the select the main group they are part of, then check the other group you want them to be part of. Then using the album properties link next to each album. You can set which group can see a particular album.');


define("GTDOC_CONFIG_TITLE", 'The configuration page<a id="config" name="config"></a>');
define("GTDOC_CONFIG_DESC", 'The following settings are where you can &quot;customize&quot; coppermine for your needs, this is also where &quot;new&quot; features are enabled. For those seeking to hack or customize coppermine or are accessing these settings though phpmyadmin or other browser based mysql administration the </span>&nbsp;&nbsp;<span class="sqlname"><b>config name</b></span> is next to each title');
define("GTDOC_USER_CONFIG_TITLE", 'The configuration variables are reserved for the site administrator please contact him/her if you need/want these changed');

define("GTDOC_GENSET_TITLE", 'General settings');
define("GTDOC_GAL_NAME_TITLE", 'Gallery name<br /><span class="cur_name"> Current  Value: ' . $CONFIG["gallery_name"] . '</span>&nbsp;&nbsp;<span class="sqlname">gallery_name</span>');
define("GTDOC_GAL_NAME_DESC", 'This is the name of your gallery. It will appear at the top of your home page(edit it\'s display in template.html), and in the nice titles. Do not use html tags in this field');

define("GTDOC_GAL_DESC_TITLE", 'Gallery description<br /><span class="cur_name"> Current  Value: ' . $CONFIG["gallery_description"] . '</span>&nbsp;&nbsp;<span class="sqlname">gallery_description</span>'); // $CONFIG["gallery_description']
define("GTDOC_GAL_DESC_DESC", '<p>This is a short description of your gallery. It will appear at the top of your homepage below the name of your gallery.(edit it\'s display in template.html) You can use the &lt;br&gt; char to manage the display of longer descriptions, edit other display in your theme css');

define("GTDOC_GAL_EMAIL_TITLE", 'Gallery administrator email<br /><span class="cur_name"> Current  Value: ' . $CONFIG["gallery_admin_email"] . '</span>&nbsp;&nbsp;<span class="sqlname">gallery_admin_email</span>'); // $CONFIG["gallery_admin_email']
define("GTDOC_GAL_EMAIL_DESC", 'Your e-mail address. All emails sent by the gallery are sent with this email address.');

define("GTDOC_HOME_LINK_TITLE", ' Address to nuke folder<br /><span class="cur_name"> Current  Value: ' . $CONFIG["ecards_more_pic_target"] . '</span>&nbsp;&nbsp;<span class="sqlname">ecards_more_pic_target</span>');
define("GTDOC_HOME_LINK_DESC", 'This is the URL where nuke is installed. This is used for links for the e-card &quot;see more pictures link&quot; and the bookmark link. ie http://www.mysite.tld/html/ Don\'t forget the trailing slash or your ecards links won\'t work');

define("GTDOC_LANG_TITLE", 'Language<br /><span class="cur_name"> Current  Value: ' . $CONFIG["lang"] . '</span>&nbsp;&nbsp;<span class="sqlname">lang</span>');
define("GTDOC_LANG_DESC", 'This is the default language for your gallery. All language files are stored in the <b>lang</b> directory on your server.<br />The language files with an "<b>-utf8</b>" suffix are unicode encoded files. If you select an -utf8 file as the default language and you set "Character encoding" to "Unicode (utf-8)" then the script will auto detect the preferred language of the visitor based on what is configured in his browser. If the corresponding language is available it will be used else the default language file will be used.<br />When the script auto detect the preferred language, it stores the result in a cookie on the visitor\'s computer. To reset this cookie (and so force the script to do another auto detection) call it with something like: <a href="javascript:%20;">/index.php?name=coppermine&newlang=xxx</a><br />Once you have added some comments or pictures to your gallery, you should not change the character set of your gallery. If you do so, non-ASCII character may not be shown correctly. You must have the language available in you nuke language files to enable it\'s use in your gallery');

define("GTDOC_THEME_TITLE", 'Theme<br /><span class="cur_name"> Current  Value: ' . $CONFIG["album_list_cols"] . '</span>&nbsp;&nbsp;<span class="sqlname">theme</span>'); //$CONFIG["theme']
define("GTDOC_THEME_DESC", 'Use this line to select the theme of your gallery. Themes are stored in sub-directories of the <b>themes</b> directory. Only themes designed for this version will work. The best way to edit themes is to make a copy of the default theme folder, name as you like (inside the theme folder), edit the css and template files as necessary. Once you have uploaded your files your theme name will be displayed here');

define("GTDOC_NICE_TITLES_TITLE", 'Page Specific Titles instead of &gt;Coppermine<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["nice_titles"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">nice_titles</span>'); // $CONFIG["nice_titles']
define("GTDOC_NICE_TITLES_DESC", 'Use this line for page specific titles in the title bar of the browser. These are the same as the breadcrumbs that appear on the page.');

define("GTDOC_RIGHTBLOCKS_TITLE", 'Show Right Blocks<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["right_blocks"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">right_blocks</span>');
define("GTDOC_RIGHTBLOCKS_DESC", 'Use this to show the right blocks while in coppermine, you may want to change the value of  Number of columns for the album list to 1 to make it all fit.');

define("GTDOC_ALBUM_LIST_TITLE", 'Album list view');

define("GTDOC_MAIN_TITLE_WIDTH_TITLE", 'Width of the main table (pixels or %)<br /><span class="cur_name"> Current  Value: ' . $CONFIG["main_table_width"] . '</span>&nbsp;&nbsp;<span class="sqlname">main_table_width</span>');
define("GTDOC_MAIN_TITLE_WIDTH_DESC", 'This is the width of tables used on your main page or when you are viewing thumbnails of an album. You can enter a width in pixels or specify it in percents. The default value is 100%. This is called in the cpg function starttable(); if a percent is not specified (important for theme designers)');

define("GTDOC_SUBCAT_LEVEL_TITLE", 'Number of levels of categories to display<br /><span class="cur_name"> Current  Value: ' . $CONFIG["subcat_level"] . '</span>&nbsp;&nbsp;<span class="sqlname">subcat_level</span>');
define("GTDOC_SUBCAT_LEVEL_DESC", 'The default value is 2. With this value the script will display the current categories plus one level of sub-categories.');

define("GTDOC_ALBUMS_PER_PAGE_TITLE", 'Number of albums to display<br /><span class="cur_name"> Current  Value: ' . $CONFIG["albums_per_page"] . '</span>&nbsp;&nbsp;<span class="sqlname">albums_per_page</span>');
define("GTDOC_ALBUMS_PER_PAGE_DESC", 'This is the number of albums to display on a page. If the current category contains more albums, the album list will spread over multiple pages.');

define("GTDOC_ALB_LIST_COLS_TITLE", 'Number of columns for the album list<br /><span class="cur_name"> Current  Value: ' . $CONFIG["album_list_cols"] . '</span>&nbsp;&nbsp;<span class="sqlname">album_list_cols</span>');
define("GTDOC_ALB_LIST_COLS_DESC", 'Number of columns for the album list. The default value is 2. If your nuke them is not very wide or your\'re using coppermine as your home page, you might change this value to 1');

define("GTDOC_ALBLIST_THUMB_SIZE_TITLE", 'Size of thumbnails in pixels<br /><span class="cur_name"> Current  Value: ' . $CONFIG["alb_list_thumb_size"] . '</span>&nbsp;&nbsp;<span class="sqlname">alb_list_thumb_size</span>');
define("GTDOC_ALBLIST_THUMB_SIZE_DESC", 'This is the size of the thumbnails that are displayed for each album. 50 means that the thumbnail will fit inside a square of 50x50 pixels.
<br />If the size you specify there is larger than "Pictures and thumbnails settings/Max width or height of a thumbnail", the thumbnail will be stretched.');
//<br /><span class="cur_name"><font size="-1"> Current  Value: ' . $CONFIG["main_page_layout"] . '</font></span><br /><span class="sqlname">main_page_layout</span>')
define("GTDOC_MAIN_CONT_TITLE", 'The content of the main page');
define("GTDOC_MAIN_CONT_DESC", 'This option allows you to change the content of the main page displayed by the script.<br />The default value is "breadcrumb/catlist/alblist/lastup,1/lastcom,1/topn,1/toprated,1/random,1/anycontent"<br />You can use the following "codes"<ul><li>"<b>breadcrumb</b>": Home Category Name Album Name (the navigation links)</li><li>"<b>catlist</b>": category list</li><li>"<b>alblist</b>": album list</li><li>"<b>lastup</b>": last uploads</li><li>"<b>lastcom</b>": last comments </li><li>"<b>lastupby</b>": last uploads by current user</li><li>"<b>lastcomby</b>": last comments by current user</li><li>"<b>topn</b>": most viewed</li><li>"<b>toprated</b>": top rated</li><li>"<b>random</b>": random pictures</li><li>"<b>anycontent</b>": The anycontent block ( a new block just edit      any content to put whatever you want here)</li></ul><br />These can be placed in any order and are optional (except catlist and alblist they are needed for navigation)<br />The <b>,2</b> means 2 rows of thumbnails. breadcrumb/catlist/alblist/lastup,2/lastcom,2/topn,2/toprated,2/random,2/anycontent');

define("GTDOC_FIRST_LEVEL_TITLE", ' Show first level album thumbnails in categories<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["first_level"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">first_level</span>');
define("GTDOC_FIRST_LEVEL_DESC", 'This will show all of your albums under there categories in the home page of coppermine Thumbnail view');

define("GTDOC_THUMBVIEW_TITLE", 'Thumbnail view');

define("GTDOC_THUMBCOLS_TITLE", 'Number of columns on thumbnail page<br /><span class="cur_name"> Current  Value: ' . $CONFIG["thumbcols"] . '</span>&nbsp;&nbsp;<span class="sqlname">thumbcols</span>');
define("GTDOC_THUMBCOLS_DESC", 'Default value is 4 this means that each row will show 4 thumbnails.');

define("GTDOC_THUMBROWS_TITLE", 'Number of rows on thumbnail page<br /><span class="cur_name"> Current  Value: ' . $CONFIG["thumbrows"] . '</span>&nbsp;&nbsp;<span class="sqlname">thumbrows</span>');
define("GTDOC_THUMBROWS_DESC", 'Default value is 3 this means that each column will show 3 thumbnails.');

define("GTDOC_MAX_TABS_TITLE", ' Maximum number of tabs to display<br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_tabs"] . '</span>&nbsp;&nbsp;<span class="sqlname">max_tabs</span>');
define("GTDOC_MAX_TABS_DESC", 'When the thumbnails spread over multiple pages, tabs are displayed at the bottom of the page. This value define how many tabs will be displayed.');

define("GTDOC_CAPTION_THUMBVIEW_TITLE", 'Display picture caption (in addition to title) below the thumbnail<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["caption_in_thumbview"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">caption_in_thumbview</span>');
define("GTDOC_CAPTION_THUMBVIEW_DESC", 'This will show a photos caption below the thumb if there is one.');

define("GTDOC_NUM_COMMENT SHOW_TITLE", 'Display number of comments below the thumbnail-- <span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["display_comment_count"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">display_comment_count</span>');
define("GTDOC_NUM_COMMENT SHOW_DESC", 'This will show a count of comment below the thumb if any.');

define("GTDOC_SORT_ORDER_TITLE", 'Default sort order for pictures<br /><span class="cur_name"> Current  Value: ' . $CONFIG["default_sort_order"] . '</span>&nbsp;&nbsp;<span class="sqlname">default_sort_order</span>');
define("GTDOC_SORT_ORDER_DESC", 'This affects the order in which thumbnails are displayed to the user. when a user (even you) click one of the plus or minus signs this will change this default setting until cookies are cleared.');

define("GTDOC_MIN_VOTES_TITLE", 'Minimum number of votes for a picture to appear in the \'top-rated\' list<br /><span class="cur_name"> Current  Value: ' . $CONFIG["min_votes_for_rating"] . '</span>&nbsp;&nbsp;<span class="sqlname">min_votes_for_rating</span>');
define("GTDOC_MIN_VOTES_DESC", 'If a picture has received less than "this value" votes, it will not be displayed on the "top-rated" page.');

define("GTDOC_SEO_ALTS_TITLE", 'Alts and title tags of thumbnail show title and keyword instead of picinfo<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["seo_alts"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">seo_alts</span>');
define("GTDOC_SEO_ALTS_DESC", 'This changes the content of alt tags to picture title and keywords if any else, it shows just file name. These are shown to users mousing over the thumbnails and while downloading (slower connections...)');
define("GTDOC_IMAGE_COMMENT_TITLE",'Image view & Comment settings');

define("GTDOC_PIC_TABLE_WIDTH_TITLE", 'Width of the table for picture display (pixels or %)<br /><span class="cur_name"> Current  Value: ' . $CONFIG["picture_table_width"] . '</span>&nbsp;&nbsp;<span class="sqlname">picture_table_width</span>');
define("GTDOC_PIC_TABLE_WIDTH_DESC", 'The width of the table used to display the intermediate picture.');

define("GTDOC_DISP_PICINFO_TITLE", 'Picture information are visible by default<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["display_pic_info"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">display_pic_info</span>');
define("GTDOC_DISP_PICINFO_DESC", 'Define whether or not picture information (those that appear when you click on the (i) button on the fullsize page) should be visible by default. If this info is important for your gallery you may want to enable this as non-javascript enable browser and Navigator4 will not be able to see this info if not.');

define("GTDOC_BADWORDS_TITLE", 'Filter bad words in comments<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["filter_bad_words"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">filter_bad_words</span>');
define("GTDOC_BADWORDS_DESC", 'Remove "bad words" from comments. The "bad words" list is in the language file.');

define("GTDOC_SMILIES_TITLE", 'Allow smilies in comments <br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["enable_smilies"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">enable_smilies</span>');
define("GTDOC_SMILIES_DESC", 'Allows the use of smilies in comments. These will decoded on displayimage but will appear as chars ( :LOL: ) in thumbnail view and blocks');

define("GTDOC_NO_FLOOD_COM_TITLE", 'Disable Flood Protection  <br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["disable_flood_protection"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">user disable_flood_protection</span>');
define("GTDOC_NO_FLOOD_COM_DESC", 'Disable consecutive comments on one pic from the same user');

define("GTDOC_COM_EMAIL_TITLE", 'Email site admin upon comment submission <br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["comment_email_notification"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">comment_email_notification </span>');
define("GTDOC_COM_EMAIL_DESC", 'Changing this to yes will send email on each comment submission to the site administrator address "gallery_admin_email" ');

define("GTDOC_MAX_DESC_TITLE", 'Max length for an image description<br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_img_desc_length"] . '</span>&nbsp;&nbsp;<span class="sqlname">&nbsp;max_img_desc_length</span>');
define("GTDOC_MAX_DESC_DESC", 'Maximum number of characters that an image description may contain.');

define("GTDOC_MAX_COM_WLENGTH_TITLE", 'Max number of characters in a word<br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_com_wlength"] . '</span>&nbsp;&nbsp;<span class="sqlname">max_com_wlength</span>');
define("GTDOC_MAX_COM_WLENGTH_DESC", 'This is intended to prevent that someone break the layout of the gallery by posting a long comment without space. With the default value, words with more than 38 characters are censored.');

define("GTDOC_MAX_COM_LINES_TITLE", 'Max number of lines in a comment<br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_com_lines"] . '</span>&nbsp;&nbsp;<span class="sqlname">max_com_lines</span>');
define("GTDOC_MAX_COM_LINES_DESC", 'Prevent a comment for containing too many new line char.');

define("GTDOC_MAX_COM_CHARS_TITLE", 'Maximum length of a comment<br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_com_size"] . '</span>&nbsp;&nbsp;<span class="sqlname">max_com_size</span>');
define("GTDOC_MAX_COM_CHARS_DESC", 'Maximum number of characters that a comment may contain.');

define("GTDOC_FILM_STRIP_TITLE", 'Show film strip <span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["display_film_strip"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">display_film_strip</span>');
define("GTDOC_FILM_STRIP_DESC", 'Show film strip below the image (an additional nav tool)');

define("GTDOC_FILM_FRAMES_TITLE", 'Number of items in film strip <br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_film_strip_items"] . '</span>&nbsp;&nbsp;<span class="sqlname">max_film_strip_items</span>');
define("GTDOC_FILM_FRAMES_DESC", 'Number of frames in film strip. Change this to lees if you find the displayimage page too wide for your layout.');

define("GTDOC_ANON_FULL_TITLE", 'Allow viewing of full size pic by anonymous <br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["allow_anon_fullsize"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">allow_anon_fullsize</span>');
define("GTDOC_ANON_FULL_DESC", 'The default value');


define("GTDOC_PIC_THUMB_TITLE",'Pictures and thumbnails settings');    
define("GTDOC_JPEG_TITLE", 'Quality for JPEG files<br /><span class="cur_name"> Current  Value: ' . $CONFIG["jpeg_qual"] . '</span>&nbsp;&nbsp;<span class="sqlname">jpeg_qual</span>');
define("GTDOC_JPEG_DESC", 'The quality used for JPEG compression when the script resizes an image. Value can range from 0 (worst) to 100 (best). This value can be set to 75 when using ImageMagick.');

define("GTDOC_THUMBW_TITLE", 'Max dimension of a thumbnail <br /><span class="cur_name"> Current  Value: ' . $CONFIG["thumb_width"] . '</span>&nbsp;&nbsp;<span class="sqlname">thumb_width</span>');
define("GTDOC_THUMBW_DESC", 'Max dimension of a thumbnail (do not change this after you have pictures}');

define("GTDOC_THUMBU_TITLE", 'Max width or height of a thumbnail<br /><span class="cur_name"> Current  Value: ' . $CONFIG["thumb_use"] . '</span>&nbsp;&nbsp;<span class="sqlname">thumb_use</span>');
define("GTDOC_THUMBU_DESC", 'With the default value thumbnails will fit inside a box of 100x100 pixels.(do not change this after you have pictures)');

define("GTDOC_INTERM_TITLE", 'Create intermediate pictures<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["make_intermediate"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">make_intermediate</span>');
define("GTDOC_INTERM_DESC", 'By default, whenever you upload a picture, the script creates a thumbnail of the picture (picture name with a thumb_ prefix) plus an intermediate version (picture name with a normal_ prefix). If you set this option to NO, the intermediate picture is not created.');

define("GTDOC_PWIDTH_TITLE", 'Max width or height of an intermediate picture<br /><span class="cur_name"> Current  Value: ' . $CONFIG["picture_width"] . '</span>&nbsp;&nbsp;<span class="sqlname">picture_width</span>');
define("GTDOC_PWIDTH_DESC", 'The intermediate pictures are those that appears when you click on a thumbnail. Do not change this after you have pictures. The default value is 400, it means that the intermediate picture will fit inside a square of 400x400 pixels, this works well with a 15 inch monitor and 1024 x 768 screen size with most layouts.');

define("GTDOC_MAXUPSIZE_TITLE", 'Max size for uploaded pictures (KB)<br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_upl_size"] . '</span>&nbsp;&nbsp;<span class="sqlname">max_upl_size</span>');
define("GTDOC_MAXUPSIZE_DESC", 'Any picture with a file size larger than this value will be rejected by the script. You should set this to a value less than you server\'s php setting for this');

define("GTDOC_MAXUPW_TITLE", 'Max width or height for uploaded pictures (pixels)<br /><span class="cur_name"> Current  Value: ' . $CONFIG["max_upl_width_height"] . '</span>&nbsp;&nbsp;<span class="sqlname">max_upl_width_height</span>');
define("GTDOC_MAXUPW_DESC", 'Limit the dimensions of the pictures that are uploaded. Resizing large pictures requires a lot of memory and consumes CPU.');
define("GTDOC_USER_SET_TITLE", 'User settings');
define("GTDOC_USEREG_TITLE", 'Allow new user registrations<span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["allow_user_registration"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">allow_user_registration</span>');
define("GTDOC_USEREG_DESC", 'NOT USED IN THIS VERSION');

define("GTDOC_USEREGM_TITLE", 'User registration requires email verification');
define("GTDOC_USEREGM_DESC", '<b>NOT USED IN THIS VERSION</b>');

define("GTDOC_USEML_TITLE", 'Allow two users to have the same email address');
define("GTDOC_USEML_DESC", '<b>NOT USED IN THIS VERSION</b>');

define("GTDOC_PRVALB_TITLE", 'Users can can have private albums<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["allow_private_albums"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">allow_private_albums</span>');
define("GTDOC_PRVALB_DESC", 'If set to YES then your gallery can contain albums that can be visible only by users that belong to a certain group.<br />If a user is a member of a group that can have its own gallery and this option is turned on then this user will have the permission to hide some of this albums to other users.');

define("GTDOC_USRFLDS_TITLE", 'Custom fields for image description (leave blank if unused)');
define("GTDOC_USRFLDS_DESC", 'These fields are displayed within the "picture information" area. They will appear only if you give them a name. You use one to four of these.');

define("GTDOC_PIC_THUMB_ADV_TITLE", 'Pictures and thumbnails advanced settings');

define("GTDOC_PRIVICON_TITLE", 'Show private album icon to unlogged user<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["show_private"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">show_private</span>');
define("GTDOC_PRIVICON_DESC", 'Default behavior of coppermine is to show a private album icon and info for private albums by setting this to yes will not show the picture or info');

define("GTDOC_FCHAR_TITLE", 'Characters forbidden in filenames<br /><span class="cur_name"> Current  Value: ' . $CONFIG["forbiden_fname_char"] . '</span>&nbsp;&nbsp;<span class="sqlname">forbiden_fname_char</span>');
define("GTDOC_FCHAR_DESC", 'When the filename of a picture that is uploaded contains one of these characters, it will be replaced with an underscore.<br />Don\'t change this unless you know exactly what you are doing.');

define("GTDOC_FEX_TITLE", 'Accepted file extensions for uploaded pictures<br /><span class="cur_name"> Current  Value: ' . $CONFIG["allowed_file_extensions"] . '</span>&nbsp;&nbsp;<span class="sqlname">allowed_file_extensions</span>');
define("GTDOC_FEX_DESC", 'Any file uploaded whose extension is not in this list will be rejected by the script. This is intended to prevent a user from uploading non-image files.<br />The script can only handle image files, so you won\'t add support for instance for video files by adding AVI to the list.<br />The GD library only supports JPEG and PNG images so other types of files will be rejected even if their extension is valid.');

define("GTDOC_TMBM_TITLE", 'Method for resizing images<br /><span class="cur_name"> Current  Value: ' . $CONFIG["thumb_method"] . '</span>&nbsp;&nbsp;<span class="sqlname">thumb_method</span>');
define("GTDOC_TMBM_DESC", 'If you are using GD 1.x and the colors of your thumbnails or intermediate image are wrong then switch to GD 2.x.');

define("GTDOC_IPATH_TITLE", 'Path to ImageMagick \'convert\' utility (example /usr/bin/X11/)<br /><span class="cur_name"> Current  Value: ' . $CONFIG["impath"] . '</span>&nbsp;&nbsp;<span class="sqlname">impath</span>');
define("GTDOC_IPATH_DESC", 'If you are using ImageMagick convert utility to resize you picture, you must enter the name of the directory where the convert program is located there. Don\'t forget the trailing "/".<br />If your server is running under Windows, use / and not \ to separate components of the path (eg. use C:/ImageMagick/ and not C:\ImageMagick\). This path must not contain any space so under Windows don\'t put ImageMagick in the "Program files" directory.<br /><b>ImageMagick will hardly work if PHP on your server is running in SAFE mode and it is a real challenge to get it running under Windows. Consider using GD in these cases and don\'t waste your time asking for support in the forum. There are too many things that can prevent ImageMagick to work correctly and without a physical access to your server it is hard to guess what is wrong.</b>');

define("GTDOC_FEX_TITLE2", 'Allowed image types (only valid for ImageMagick)<br /><span class="cur_name"> Current  Value: ' . $CONFIG["allowed_img_types"] . '</span>&nbsp;&nbsp;<span class="sqlname">allowed_img_types</span>');
define("GTDOC_FEX_DESC2", 'This is the list of image types that the script will accept when using ImageMagick. Image type detection is performed by reading the header of the file and not by looking at its file extension.');

define("GTDOC_IMOPT_TITLE", 'Command line options for ImageMagick<br /><span class="cur_name"> Current  Value: ' . $CONFIG["im_options"] . '</span>&nbsp;&nbsp;<span class="sqlname">im_options</span>');
define("GTDOC_IMOPT_DESC", 'Here you can add options that will be appended to the command line when executing ImageMagick. Read the ImageMagick Convert manual to see what is available.');

define("GTDOC_EXIF_TITLE", 'Read EXIF data in JPEG files<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["read_exif_data"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">read_exif_data</span>');
define("GTDOC_EXIF_DESC", 'With this option turned on, the script will read the EXIF data stored by digicams in JPEG files. This new feature hasn\'t been fully tested.');

define("GTDOC_IPTC_TITLE", 'Read IPTC data in JPEG files<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["read_iptc_data"] ? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">read_iptc_data</span>');
define("GTDOC_IPTC_DESC", 'Read IPTC data in JPEG files. This new feature hasn\'t been fully tested.'); //;($CONFIG["read_iptc_data"]

define("GTDOC_FPATH_TITLE", 'The album directory<br /><span class="cur_name"> Current  Value: ' . $CONFIG["fullpath"] . '</span>&nbsp;&nbsp;<span class="sqlname">fullpath</span>');
define("GTDOC_FPATH_DESC", 'This is the base directory for your "Image Store". The path is relative to the main directory of the script. <br />You can use ../ in the path to move-up one level in the directory tree.<br />You can not use an absolute path there ("/var/my_images/" will not work) and the album directory must be visible by your web server, you can\'t use this to serve you pictures from another server.');

define("GTDOC_UPICS_TITLE", 'The directory for user pictures<br /><span class="cur_name"> Current  Value: ' . $CONFIG["userpics"] . '</span>&nbsp;&nbsp;<span class="sqlname">userpics</span>');
define("GTDOC_UPICS_DESC", 'This is the directory where pictures uploaded with the web interface are stored. This directory is a subdirectory of the album directory.<br />The same remarks as above apply.<br />When you upload pictures by FTP, store them in a subdirectory of the "album directory" and not inside the "directory for user pictures".');

define("GTDOC_PFNAME_TITLE", 'Picinfo display filename<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_filename"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_filename</span>');
define("GTDOC_PFNAME_DESC", 'Display the filename in the picinfo section?');

define("GTDOC_PANAME_TITLE", 'Picinfo display album name<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_album_name"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_album_name</span>');
define("GTDOC_PANAME_DESC", 'Display the album name the image is from in the picinfo section?');

define("GTDOC_PFSIZE_TITLE", 'Picinfo display_file_size<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_file_size"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_file_size</span>');
define("GTDOC_PFSIZE_DESC", 'Display the fieldsize (50KB) in the picinfo section?');

define("GTDOC_PDIM_TITLE", 'Picinfo display_dimensions<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_dimensions"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_dimensions</span>');
define("GTDOC_PDIM_DESC", 'Display the dimensions i.e. (640x480) in the picinfo section?');

define("GTDOC_PCNT_TITLE", 'Picinfo display_count_displayed<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_count_displayed"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_count_displayed</span>');
define("GTDOC_PCNT_DESC", 'Display the amount of times the picture has been displayed in the picinfo section?');

define("GTDOC_PURL_TITLE", 'Picinfo display_URL<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_URL"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_URL</span>');
define("GTDOC_PURL_DESC", 'Display the short URL to the picture in the picinfo section? if you chose to set "Display the short URL to the picture as a bookmark link" to yes that\'s how this link will be displayed, you do not have to set this value to yes to have the bookmark link to work');

define("GTDOC_PBKMK_TITLE", 'Picinfo display URL as bookmark link<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_URL_bookmark"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_URL_bookmark</span>');
define("GTDOC_PBKMK_DESC", 'Display the short URL to the picture as a bookmark link in the picinfo section?');

define("GTDOC_PFAV_TITLE", 'Picinfo display fav album link<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["picinfo_display_favorites"]? 'Enabled':'Not Enabled') . ' </span>&nbsp;&nbsp;<span class="sqlname">picinfo_display_favorites</span>');
define("GTDOC_PFAV_DESC", 'Display the add to favorites album in the picinfo section? This new feature allows user to have a personal favorites album that lasts as long as they have the cookie');

define("GTDOC_COOKIE_TITLE", 'Cookies &amp; Charset settings');

define("GTDOC_CNAME_TITLE", 'Name of the cookie used by the script<br /><span class="cur_name"> Current  Value: ' . $CONFIG["cookie_name"] . '</span>&nbsp;&nbsp;<span class="sqlname">cookie_name</span>');
define("GTDOC_CNAME_DESC", 'Default value is "cpgnuke". Use a different cookie name for each install if you have more than one coppermine install!');

define("GTDOC_CPATH_TITLE", 'Path of the cookie used by the script<br /><span class="cur_name"> Current  Value: ' . $CONFIG["cookie_path"] . '</span>&nbsp;&nbsp;<span class="sqlname">cookie_path</span>');
define("GTDOC_CPATH_DESC", 'Default value is "/". Change this value to the path of your nuke install /html/.');

define("GTDOC_CHARSET_TITLE", 'Character encoding<br /><span class="cur_name"> Current  Value: ' . $CONFIG["charset"] . '</span>&nbsp;&nbsp;<span class="sqlname">charset</span>');
define("GTDOC_CHARSET_DESC", 'This should normally be set to "Default (language file)" or "Unicode (utf-8)". See the discussion in the "<b>language</b>" section of this page.');

define("GTDOC_DEBUG_TITLE", 'Enable debug mode<br /><span class="cur_name"> Current  Value: ' . sprintf('%s', $CONFIG["debug_mode"]? 'Enabled':'Not Enabled') . '</span>&nbsp;&nbsp;<span class="sqlname">debug_mode </span>');
define("GTDOC_DEBUG_DESC", 'If you have problems turn this on and paste the content in your post to the forum (don\'t worry in our version this info only shows to admin not the general public)');

define("GTDOC_ADEBUG_DESC", 'If you have problems turn this and debug mode for additional info (don\'t worry in our version this info only shows to admin not the general public).');
define("GTDOC_MENU_UPDATE_ALERT_TITLE", 'If enabled a light will appear below the admin menu (admin only)');
define("GTDOC_UPDATE_ALERT_DESC", 'If enabled a light will appear below the admin menu (admin only). This shows a red yellow or green light. Green Light: your version is the current version, you\'re all good. Yellow Light: Upgrade Available. Red Light: means security alert, we hope to never have to use this, but if you see it please visit our site for more information');
