<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004-2006 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

Encoding test: n-array summation ∑ latin ae w/ acute ǽ
*******************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $MAIN_CFG;

$LNG = array(
'Token' => 'Token',
'User Account Registration' => 'User Account Registration',
'New User Account Pending!' => 'New User Account Pending!',
'Your registration as a new member is pending' => 'Welcome! Your registration as a new member is pending. The site administrator will contact you when your registration has been processed.',
'The site Administrator will review your registration' => 'The site Administrator will review your registration and send you an email if you are approved.',
'You successfully added/updated a login method' => 'You successfully added/updated a login method',
'Show profiles to registered users only' => 'Show profiles to registered users only',

// Search user window
'Find a nickname' => 'Find a nickname',
'Use * as a wildcard for partial matches' => 'Use * as a wildcard for partial matches',
'No matches found.' => 'No matches found.',
'Select' => 'Select',
'Close Window' => 'Close Window',
);

define('_MA_HIDDEN','Hidden');
define('_MA_VISIBLE','Visible');
define('_MA_REQUIRED','Required');
define('_MA_PROFILE_INFO','Profile Information');
define('_MA_ADDITIONAL','Additional Information');
define('_MA_REALNAME','Real Name');
define('_MA_HOMEPAGE','Home Page');
define('_MA_ICQ','ICQ Number');
define('_MA_AIM','AIM Screen Name');
define('_MA_YIM','Yahoo! Messenger ID');
define('_MA_LOCATION','My Location');
define('_MA_OCCUPATION','My Occupation');
define('_MA_INTERESTS','My Interests');
define('_MA_SIGNATURE','My Signature');
define('_MA_EXTRAINFO','Extra Info');
define('_MA_PREFERENCES','Preferences');
define('_MA_TIMEZONE','Timezone');
define('_MA_DATEFORMAT','Date format');
define('_MA_RECEIVENEWSLETTER','Receive Newsletter');
define('_MA_SHOWEMAIL','Show my Email Address');
define('_MA_SHOWONLINE','Show my online status');
define('_MA_ALLOWBBCODE','Always Allow BBCode');
define('_MA_ALLOWHTML','Always Allow HTML');
define('_MA_ALLOWSMILIES','Always Enable Smilies');
define('_MA_NOTIFYREPLY','Notify me of replies');
define('_MA_NOTIFYPM','Notify on new Private Message by Email');
define('_MA_POPUPPM','Pop up window on new Private Message');
define('_MA_ATTACHSIG','Always attach my signature');
define('_MA_PRIVATE','Private Information');
define('_MA_FIRSTNAME','Firstname');
define('_MA_LASTNAME','Lastname');
define('_MA_GENDER','Gender');
define('_MALE','Male');
define('_FEMALE','Female');
define('_MA_BIRTHDAY','Date of birth');
define('_MA_BIRTHDAYMSG','Fill in as month/day/year');
define('_MA_TELEPHONE','Telphone number');
define('_MA_FAX','Fax number');
define('_OSC_NEWSLETTER','Recieve Shop newsletter');
define('_OSC_NEWSLETTERMSG','This is a separate e-mail about new shop items and discounts');
define('_BOUNDREGISTRATION','By clicking Register below you agree to be bound by these conditions.');
define('_MA_REGISTRATION','Registration Agreement Terms');
define('_MA_AGREE_OVER_13','I Agree to these terms and am <b>over</b> or <b>exactly 13</b> years of age');
define('_MA_AGREE_UNDER_13','I Agree to these terms and am <b>under</b> 13 years of age');
define('_MA_REGISTRATION_INFO','Registration Information');
define('_MA_ITEMS_REQUIRED','Items marked with a * are required unless stated otherwise.');
define('_USERNAME','Username');
define('_EMAILADDRESS','Email address');
define('_BLANKFORAUTO','Leave blank to auto-generate your password');
define('_CONFIRMPASSWORD','Confirm password');
define('_MA_DATEFORMATMSG','The syntax used is identical to the PHP <a href="http://php.net/date">date()</a> function');
define('_MA_SIGNATUREMSG','This is a block of text that can be added to my posts<br />'.sprintf(_M_CHARS, 255));
define('_MA_NOTIFYREPLYMSG','Sends an email when someone replies to a topic you have posted in. This can be changed whenever you post');
define('_MA_POPUPPMMSG','Will open a new popup window to inform you when new private messages arrive');
define('_ACTDISABLED','This function has been <b>DISABLED</b> by the site administrator');
define('_USERFINALSTEP','User Registration: Final Step');
define('_USERCHECKDATA','please check the following information. If it is correct, then you can finalize the registration process by clicking on the "Finish" button, otherwise click "Go Back" and change whatever information is needed.');
define('_YOUWILLRECEIVE','You will receive a confirmation email with an activation link you should visit within the next 24 hours');
define('_YOUWILLRECEIVE2','You will receive an email with your login information.');
define('_WAITAPPROVAL','The site Administrator will review your registration and send you an email if you are approved.');
define('_FINISH','Finish');
define('_YOUUSEDEMAIL','You or someone else has used your email account');
define('_TOREGISTER','to register an account at');
define('_ACCOUNTCREATED','New User Account Created!');
define('_YOUAREREGISTERED','Welcome! You are now registered as a member');
define('_FINISHUSERCONF','Your request for a new account has been processed. You will receive an email shortly with an activation link that should be visited within the next 24 hours to activate your account');
define('_TOFINISHUSER','To finish the registration process you should visit the following link within the next 24 hours to activate your user account, otherwise the information will be purged by our system and you will need to re-apply');
define('_ACTIVATIONSUB','User Account Activation');
define('_REGISTRATIONSUB','User Account Registration');
define('_FOLLOWINGMEM','The following is your member information:');
define('_TOAPPLY','to apply for an account at');
define('_ERRORINVEMAIL','ERROR: Invalid Email');
define('_NICK2SHORT','Username is too short. It must be more than 3 characters');
define('_ERRORINVNICK','ERROR: Invalid Username');
define('_NICK2LONG','Username is too long. It must be less than 26 characters');
define('_NAMEDENIED','ERROR: This part of your chosen user name may not be used :');
define('_NICKTAKEN','ERROR: Username already taken');
define('_EMAILREGISTERED','ERROR: Email address already registered');
define('_PASSDIFFERENT','Both passwords are different. They need to be identical.');
define('_EMAILNOTUSABLE','ERROR: Email address is not usable');
define('_ACTIVATIONYES','User Activation');
define('_ACTIVATIONERROR','User Activation Error');
define('_ACTERROR1','User verification number is invalid.<br /><br />Please be sure to click on the link you received by email or apply for a new account <a href="'.URL::index().'">here</a>.');
define('_ACTERROR2','There is no user in the database with this information.<br /><br />You can register a new user from <a href="'.URL::index().'">here</a>.');
define('_CURRENTPASSWORD','Current password');
define('_CURRENTPASSWORDMSG','You must confirm your current password if you wish to change it or alter your e-mail address');
define('_NEWPASSWORD','New password');
define('_NEWPASSWORDMSG','You only need to supply a password if you want to change it');
define('_CONFIRMPASSWORDMSG','You only need to confirm your password if you changed it above');
define('_CATEGORY_SELECT','Select category');
define('_SELECT_AVATAR','Select avatar');
define('_CANCEL_AVATAR','Cancel avatar');
define('_THISISYOURPAGE','This is your personal page');
define('_PERSONALINFO','Personal Information');
define('_ABOUT_USER','All about ');
define('_CONTACTINFO','Contact Information');
define('_PM','Private Message');
define('_JOINED','Joined');
define('_RANK','Rank');
define('_LAST10BBTOPIC','Last 10 Forum Topics');
define('_LAST10COMMENT','Last 10 Comments');
define('_LAST10SUBMISSION','Last 10 News Submissions');
define('_MEMBERGROUPS','Group Memberships');
define('_AVATAR','Avatar');
define('_WEBSITE','Web Site');
define('_NOTSUBSCRIBED','You are not subscribed to our newsletter');
define('_SUBSCRIBED','You are subscribed to our newsletter');
define('_ACCDELETED','Account has been Deleted');
define('_ACCSUSPENDED','Account has been Suspended');
define('_NOINFOFOR','There is no available info for');
define('_SORRYNOUSERINFO','Sorry, no corresponding user info was found');
define('_LOGININCOR','Login Incorrect! Please Try Again...');
define('_ACCTEXIT','Logout');
define('_DELETEREASON','Reason for Deletion');
define('_SUSPENDREASON','Reason for Suspension');
define('_DENYREASON','Reason for Denial');
define('_ACCTAPPROVE','Account Approved');
define('_ACCTDELETE','Account Deleted');
define('_ACCTDENY','Account Denied');
define('_ACCTRESTORE','Account Restored');
define('_ACCTSUSPEND','Account Suspended');
define('_APPROVE','Approve');
define('_APPROVEUSER','Approve User');
define('_DENY','Deny');
define('_DENYUSER','Deny User');
define('_HASAPPROVE','has been approved.');
define('_HASDENY','has been denied.');
define('_HASRESTORE','has been restored.');
define('_HASSUSPEND','has been suspended.');
define('_REMOVE','Remove');
define('_RESEND','Resend');
define('_RESENDMAIL','Resend Activation Email');
define('_RESTORE','Restore');
define('_RESTOREUSER','Restore User');
define('_SORRYTO','Your account at');
define('_SURE2APPROVE','Are you sure that you want to approve user');
define('_SURE2DENY','Are you sure that you want to deny user');
define('_SURE2RESEND','Are you sure that you want to resend activation email');
define('_SURE2RESTORE','Are you sure that you want to restore user');
define('_SUSPEND','Suspend');
define('_SUSPENDUSER','Suspend User');
define('_MA_USERNOEXIST','User Doesn\'t Exist!');
define('_USERLOGIN','User Login');
define('_PASSWORDLOST','Lost your Password?');
define('_USERACCOUNT','The user account');
define('_HASTHISEMAIL','has this email associated with it.');
define('_AWEBUSERFROM','A Web user from');
define('_IFYOUDIDNOTASK','If you didn\'t ask for this, don\'t worry. You are seeing this message, not \'them\'. If this was an error just login with your new password.');
define('_MAILED','Mailed.');
define('_CODEREQUESTED','has just requested a Confirmation Code to change the password.');
define('_WITHTHISCODE','With this code you can now assign a new password at');
define('_IFYOUDIDNOTASK2','If you didn\'t ask for this, don\'t worry. Just delete this email.');
define('_CODEFOR','Confirmation Code for');
define('_COMMENTSCONFIG','Comments Configuration');
define('_DISPLAYMODE','Display Mode');
define('_NOCOMMENTS','No Comments');
define('_NESTED','Nested');
define('_FLAT','Flat');
define('_THREAD','Thread');
define('_SORTORDER','Sort Order');
define('_OLDEST','Oldest First');
define('_NEWEST','Newest First');
define('_HIGHEST','Highest Scores First');
define('_THRESHOLD','Threshold');
define('_COMMENTSWILLIGNORED','Comments scored less than what you set will be ignored');
define('_UNCUT','Uncut and Raw');
define('_EVERYTHING','Almost Everything');
define('_FILTERMOSTANON','Filter Most Anonymous');
define('_USCORE','Score');
define('_SCORENOTE',"Anonymous posts start at 0, registered posts start at 1.\nModerators can add and subtract points");
define('_NOSCORES','Hide Scores');
define('_MAXCOMMENT','Max Comment Length');
define('_BYTESNOTE','bytes (1024 bytes = 1 kilobyte)');
define('_SEARCHUSERS','Search Users');
define('_THEME','Theme');
define('_MA_HOMECONFIG','Homepage Configuration');
define('_ACTIVATEPERSONAL','Activate Personal Menu');
define('_PERSONALMENUCONTENT','Personal Menu Content');

// avatar
define('_AVATAR_FILESIZE','The avatar image file size must be less than %d KB');
define('_AVATAR_CONTROL','Avatar control panel');
define('_AVATAR_INFO','Displays a small graphic image below your details in posts. Only one image can be displayed at a time, its width can be no greater than '.$MAIN_CFG['avatar']['max_width'].' pixels, the height no greater than '.$MAIN_CFG['avatar']['max_height'].' pixels, and the file size no more than '.intval($MAIN_CFG['avatar']['filesize']/1024).' KB.');
define('_CURRENT_IMAGE','Current Image');
define('_DELETE_IMAGE','Delete Image');
define('_AVATAR_OFFSITE','Link to off-site Avatar');
define('_AVATAR_OFFSITEMSG','Enter the URL of the location containing the Avatar image you wish to link to.');
define('_AVATAR_SELECT','Select Avatar from gallery');
define('_SHOW_GALLERY','Show gallery');
define('_AVATAR_UPLOAD','Upload Avatar from your machine');
define('_AVATAR_UPLOAD_URL','Upload Avatar from a URL');
define('_AVATAR_GALLERY','Avatar gallery');
define('_AVATAR_ERR_IMTYPE','The avatar filetype must be .jpg, .gif or .png currently it is: %s');
define('_AVATAR_ERR_SIZE','Image is too large: %d x %d');
define('_AVATAR_ERR_URL','A connection could not be made to the URL you gave');
define('_AVATAR_ERR_DATA','The file at the URL you gave contains no data');

/**************************
  ADMNINISTRATION SECTION
**************************/
// users_cfg.php
define('_USERSHOMENUM','Let users change News number in Home?');
define('_PASSWDLEN','Minimum users password length');
define('_REQUIREADMIN','Require Admin Approval');
define('_ACTALLOWREG','Allow User Registration');
define('_ACTNOTIFYADD','Notify Admin of User Registration');
define('_ACTALLOWTHEME','Allow User Theme Selection');
define('_ACTALLOWMAIL','Allow User Email Change');
define('_USEACTIVATE','Use email Activation');
define('_USEREGISTERMSG','Use Registration Agreement');
define('_SENDWELCOMEPM','Send welcome PM to new users');
define('_WELCOMEPMBODY','Message Body');
// fields

// avatar
define('_AV_ALLOW_LOCAL','Enable gallery avatars');
define('_AV_ALLOW_REMOTE','Enable remote avatars');
define('_AV_ALLOW_REMOTE_INFO','Avatars linked to from another website');
define('_AV_ALLOW_UPLOAD','Enable avatar uploading');
define('_AV_MAX_FILESIZE','Maximum Avatar File Size');
define('_AV_MAX_FILESIZE_INFO','For uploaded avatar files');
define('_AV_MAX_AVATAR_SIZE','Maximum Avatar Dimensions');
define('_AV_MAX_AVATAR_SIZE_INFO','(Height x Width in pixels)');
define('_AV_AVATAR_STORAGE_PATH','Avatar Upload Path');
define('_AV_AVATAR_STORAGE_PATH_INFO','Path under your CPG-Nuke root dir, e.g. uploads/avatars');
define('_AV_AVATAR_GALLERY_PATH','Avatar Gallery Path');
define('_AV_AVATAR_GALLERY_PATH_INFO','Path under your CPG-Nuke root dir for pre-loaded images, e.g. images/avatars');
define('_AV_DEFAULT','Default avatar image');
define('_AV_DEFAULT_INFO','Relative to your Avatar Gallery Path');
define('_AV_ALLOW_ANIMATED','Allow animated avatars');

// users.php
define('_ADDUSER','Add a New User');
define('_EDITUSER','Edit User');
define('_MA_PRIVILEGES','Privileges');
define('_ICQ','ICQ Number');
define('_AIM','AIM screen name');
define('_YIM','Yahoo! ID');
define('_LOCATION','Location');
define('_OCCUPATION','Occupation');
define('_INTERESTS','Interests');
define('_EXTRAINFO','Extra Info');
