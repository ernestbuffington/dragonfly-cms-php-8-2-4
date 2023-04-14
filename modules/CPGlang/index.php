<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright (c) 2004 by Akamu
  http://www.dragonflycms.org/

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/CPGlang/index.php,v $
  $Revision: 9.11 $
  $Author: djmaze $
  $Date: 2007/12/16 22:13:14 $
*************************************************/
if (!defined('CPG_NUKE')) { exit; }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$pagetitle .= $subject = $MAIN_CFG['global']['sitename'] .' '. _FEEDBACKTITLE;
if (!defined('_SUCCESS_MESSAGE_SENT')) get_lang('Contact');

require_once('header.php');
$op = ((isset($_POST['op']) && $_POST['op']!='') ? $_POST['op'] : NULL);
$op = strtolower($op);
$sender_name = $_POST['sender_name'] ?? '';
$sender_email = $_POST['sender_email'] ?? '';
$filename = $_POST['filename'] ?? NULL;
$send = '';
global $userinfo, $MAIN_CFG;
$qs = '?';
foreach($_GET as $var => $value) {
	if ($var != 'newlang') {
		$qs .= $var."=".$value.= "&amp;";
	}
}
// requires php 4.1.0+ otherwise $HTTP_SERVER_VAR['PHP_SELF'] can be used
// both exist in php 4.1.0+ so $_SERVER['PHP_SELF'] is used
$self =	 $_SERVER['PHP_SELF'];
$self = str_replace('/', '', $self);
if ($self = ''){ $self = 'index.php'; }
global $useflags, $currentlang, $mainindex, $adminindex;

$langsel = array(
	'afrikaans' => 'Afrikaans',
	'albanian'	=> 'Shqip',
	'arabic'	=> 'عربي',
	'basque'	=> 'Basque',
	'bosanski'	=> 'Bosanski',
	'brazilian' => 'Brazilian',
	'bulgarian' => 'Български',
	'czech'		=> 'Český',
	'danish'	=> 'Dansk',
	'desi'		=> 'Desi',
	'dutch'		=> 'Nederlands',
	'english'	=> 'English',
	'estonian'	=> 'Eesti',
	'farsi'		=> 'پارسى',
	'finnish'	=> 'Suomi',
	'french'	=> 'Français',
	'galego'	=> 'galego',
	'german'	=> 'German',
	'greek'		=> 'Ελληνικά',
	'hindi'=> 'हिंदी',
	'hungarian'	 => 'Magyarul',
	'icelandic'	 => 'Icelandic',
	'indonesian' => 'Indonesian',
	'italian'	 => 'Italiano',
	'japanese'	 => '日本語',
	'korean'	 => '한국어',
	'kurdish'	 => 'Kurdi',
	'latvian'	 => 'Latvisks',
	'lithuanian' => 'Lietuvių',
	'macedonian' => 'македонски',
	'melayu'	 => 'Melay',
	'norwegian'	 => 'Norsk',
	'polish'	 => 'Polski',
	'portuguese' => 'Português',
	'romanian'	 => 'Româneste',
	'russian'	 => 'РУССКИЙ',
	'serbian'	 => 'Srpski',
	'slovak'	 => 'Slovenský',
	'slovenian'	 => 'Slovenščina',
	'spanish'	 => 'Espanõl',
	'swahili'	 => 'Kiswahili',
	'swedish'	 => 'Svensk',
	'thai'		 => 'ไทย',
	'turkish'	 => 'Türkçe',
	'uighur'	 => 'Uyghurche',
	'ukrainian'	 => 'Українська',
	'vietnamese' => 'Vietnamese',
);

$self = (defined('ADMIN_PAGES')) ? $adminindex : $mainindex;

$qs = '?';
foreach($_GET as $var => $value) {
	if ($var != 'newlang') {
		$qs .= $var.'='.$value.= '&amp;';
	}
}

$langlist = lang_selectbox('', '', false, true);

$menulist = '';
$content = '<fieldset style="background-color:#FFFFFF;border:thin outset; border-color: #999999;-moz-border-radius:10px;">
<legend style="border: thin outset; border-color: #999999;background-color:#FFFFFF;padding:3px;-moz-border-radius:15px;border-radius:15px;">'._SELECTLANGUAGE.'</legend>
<div style="text-align:center;"><br />';
if ($useflags) {
	for ($i = 0; $i < sizeof($langlist); $i++) {
		if ($langlist[$i]!="") {
			$tl = $langlist[$i];
			$altlang = ($langsel[$langlist[$i]] ?? $langlist[$i]);
			$content .= "<a href=\"{$self}{$qs}newlang=$tl\">";
			$imge = "images/language/flag-$tl.png";
			// akamu fix for broken images if lang doesn't have flag
			if (file_exists($imge)){
				$content .= "<img src=\"$imge\" align=\"middle\" alt=\"$altlang\" title=\"$altlang\" style=\"margin:3px 0 0 3px;\" />";
			} else {
				$content .= $altlang;
			}
			$content .= '</a> ';
		}
	}
} else {
	$content .= '<img src="images/cpglang/babelfish.gif" alt="fish icon" style="float:left; filter:progid:DXImageTransform.Microsoft.Alpha(opacity=75); -moz-opacity:75%; -khtml-opacity:.75; opacity:.75;" /><br />
	<form action="" method="get"><div><select name="newlanguage" onchange="top.location.href=this.options[this.selectedIndex].value">';
	for ($i=0; $i < sizeof($langlist); $i++) {
		if($langlist[$i]!='') {
			// akamu fix uses current page for value passed to js onChange
			$content .= "<option value=\"{$self}{$qs}newlang=$langlist[$i]\"";
			if($langlist[$i]==$currentlang) $content .= ' selected="selected"';
			$content .= '>'.($langsel[$langlist[$i]] ?? $langlist[$i])."</option>\n";
		}
	}
	$content .= '</select></div></form>';
}
$content .= "</div></fieldset>\n";


if ($op!='send' && is_user()) {
	$sender_name = ($userinfo['name'] != '') ? $userinfo['name'] : $userinfo['username'];
	$sender_email = $userinfo['user_email'];
}

$thisad = preg_replace('#:#m','',_ADMIN);
OpenTable();
echo '<table width="100%">
<tr style="background-color:#FFFFE6;padding-left: 3px">
<td style="width:50%; padding-left: 3px">'.$content.'</td>
<td style="width:50%; padding-left: 3px"><fieldset style="background-color:#FFFFFF;border:thin outset; border-color: #999999;-moz-border-radius:10px;">
<legend style="border: thin outset; border-color: #999999;background-color:#FFFFFF;padding:3px;-moz-border-radius:15px;border-radius:15px;">'._SELECTFILE.'</legend>
<img src="images/cpglang/folder_man.gif" alt="folder icon" style="float:left;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=55);-moz-opacity: 55%;-khtml-opacity: .55; opacity: .55;" />
<span>The files '._BlogsLANG.', '._ForumsLANG.', '._coppermineLANG.', '._Your_AccountLANG.', '._MENU.', '._CHAT.' need your attention please...</span><br />
<form style="float:right" name="mod" method="post" action="" enctype="multipart/form-data" accept-charset="'._CHARSET.'"><div>
<input type="hidden" name="name" value="'.$module_name.'" />
	<select name="op">
		<option value="main"  selected="selected">'._HOME.'</option>
		<option value="bbcode">BBCode</option>
		<option value="Blogs">'._BlogsLANG.'</option>
		<option value="coppermine">'._coppermineLANG.'</option>
		<option value="Your_Account">'._Your_AccountLANG.'</option>
		<option value="ircchat">'._CHAT.'</option>
		<option value="Contact">'._ContactLANG.'</option>
		<option value="Content">'._ContentLANG.'</option>
		<option value="cpgmm">'._MENU.'</option>
		<option value="CPGlang">'._CPGlangLANG.'</option>
		<option value="Downloads">'._DownloadsLANG.'</option>
		<option value="Encyclopedia">'._EncyclopediaLANG.'</option>
		<option value="Forums">'._ForumsLANG.'</option>
		<option value="FAQ">'._FAQLANG.'</option>
		<option value="our_sponsors">Our Sponsors</option>
		<option value="Members_List">'._Members_ListLANG.'</option>
		<option value="News">'._NewsLANG.'</option>
		<option value="Private_Messages">'._Private_MessagesLANG.'</option>
		<option value="Tell_a_Friend">'._Tell_a_FriendLANG.'</option>
		<option value="Reviews">'._ReviewsLANG.'</option>
		<option value="Search">'._SearchLANG.'</option>
		<option value="Statistics">'._StatisticsLANG.'</option>
		<option value="Stories_Archive">'._Stories_ArchiveLANG.'</option>
		<option value="Submit_News">'._Submit_NewsLANG.'</option>
		<option value="Tell_a_Friend">'._Tell_a_FriendLANG.'</option>
		<option value="Surveys">'._SurveysLANG.'</option>
		<option value="Top">'._TopLANG.'</option>
		<option value="Topics">'._TopicsLANG.'</option>
		<option value="Web_Links">'._Web_LinksLANG.'</option>
	</select>
	<input type="submit" name="Submit" value="Submit" />
</div></form></fieldset></td></tr></table>';
//CloseTable();
//OpenTable();
echo'<table width="100%">
<tr style="background-color:#FFFFFF;">
<td style="padding: 5px">';
if ($op != 'send') {
	//$lang = $_COOKIE['lang'];
	$lng = $currentlang;
	if ($op != 'forums'){
		if ($op != ''){
			if (file_exists("language/$lng/$op.php")){
				$file_location = "language/$lng/$op.php";
			}elseif (file_exists("language/english/$op.php")){
				$file_location = "language/english/$op.php";
			}elseif (file_exists("modules/$op/language/lang-$lng-utf-8.php")){
				$file_location = "modules/$op/language/lang-$lng-utf-8.php";
			} else {
				trigger_error(sprintf(_ERROR_NOT_SET,$op),E_USER_ERROR);
			}
		}else{
			$op='home';
			if (file_exists("language/$lng/main.php")){ //not needed but available for future use
				$file_location = "language/$lng/main.php";
			} elseif (file_exists('language/english/main.php')) {
				$file_location = 'language/english/main.php';
			} else {
				trigger_error(sprintf(_ERROR_NOT_SET,$op),E_USER_ERROR);
			}
		}
		if ($op == 'bbcode') {
			require($file_location);
			foreach($smilies_desc as $key => $value) {
				$const[$key] = addslashes(htmlspecialchars(stripslashes($value),ENT_COMPAT,'UTF-8'));
			}
			foreach($color_desc as $key => $value) {
				$const[$key] = addslashes(htmlspecialchars(stripslashes($value),ENT_COMPAT,'UTF-8'));
			}
			foreach($bbcode_common as $key => $value) {
				if (is_array($value)) {
					foreach ($key as $subkey => $subvalue) {
						$const[$subkey] = addslashes(htmlspecialchars(stripslashes($subvalue),ENT_COMPAT,'UTF-8'));
					}
				}else{
					$const[$key] = addslashes(htmlspecialchars(stripslashes($value),ENT_COMPAT,'UTF-8'));
				}
			}
		}else{
			$thefile = implode('', file($file_location));
			$thefile = explode("\n", $thefile);
			$const = array();
			foreach($thefile as $line){
				$line = trim($line);
				if (str_starts_with($line, 'define')) {
					$line = substr($line, 8, -3);
					$pos1 = strpos($line, ',');
					$def = substr($line, 0, $pos1-1);
					$line = trim(substr($line, $pos1+1));
					$value = substr($line, 1);
					$const[$def] = addslashes(htmlspecialchars(stripslashes($value),ENT_COMPAT,'UTF-8'));
				}
			}
		}
	} else {
		//is forums
		if (file_exists('language/'.$lng.'/forums.php')){ //not needed but available for future use
			$file_location = 'language/'.$lng.'/forums.php';
		} elseif (file_exists('language/english/forums.php')) {
			$file_location = 'language/english/forums.php';
		} else {
			trigger_error(sprintf(_ERROR_NOT_SET,$op),E_USER_ERROR);
		}
		require($file_location);
		foreach($lang as $key => $value) {
			$const[$key] = addslashes(htmlspecialchars(stripslashes($value),ENT_COMPAT,'UTF-8'));
		}
	}
		$thisfile = $op ? $op : 'Home';

?>
<script type="text/javascript">
<!--
function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
	d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_validateForm() { //v4.0
  var i,p,q,nm,test,num,min,max,errors='',args=MM_validateForm.arguments;
  for (i=0; i<(args.length-2); i+=3) { test=args[i+2]; val=MM_findObj(args[i]);
	if (val) { nm=val.name; if ((val=val.value)!="") {
	  if (test.indexOf('isEmail')!=-1) { p=val.indexOf('@');
		if (p<1 || p==(val.length-1)) errors+='- '+nm+' must contain an e-mail address.\n';
	  } else if (test!='R') {
		if (isNaN(val)) errors+='- '+nm+' must contain a number.\n';
		if (test.indexOf('inRange') != -1) { p=test.indexOf(':');
		  min=test.substring(8,p); max=test.substring(p+1);
		  if (val<min || max<val) errors+='- '+nm+' must contain a number between '+min+' and '+max+'.\n';
	} } } else if (test.charAt(0) == 'R') errors += '- '+nm+' is required.\n'; }
  } if (errors) alert('The following error(s) occurred:\n'+errors);
  document.MM_returnValue = (errors == '');
}
//-->
</script><?php
echo'<span>'._PLSENTER.' '.$lng.' '._ORANY.'</span><br />'
.'<form name="lng" method="post" action="" onsubmit="MM_validateForm(\'sender_name\',\'\',\'R\',\'sender_email\',\'\',\'RisEmail\');return document.MM_returnValue" enctype="multipart/form-data" accept-charset="'._CHARSET.'"><div>'
.'<label class="ulog" for="sender_name">'._YOURNAME.':</label><input type="text" name="sender_name" value="'.$sender_name.'" size="30" /><br />'
.'<label class="ulog" for="sender_email">'._YOUREMAIL.':</label><input type="text" name="sender_email" value="'.$sender_email.'" size="30" /><br />'
.'<label class="ulog" for="sfile">File name :</label><input readonly="readonly"	 type="text" name="sfile" value="'.$op.'" size="30" /><br />'
.'<input type="hidden" name="filename" value="'.$thisfile.'" />'
.'<label class="ulog" title="(if your language is not listed above, use the'."\n".' english translation and enter your language name here)" for="lng">Language name:</label><input type="text" name="lng" title="(if your language is not listed above, use the english translation and enter your language name here)" value="'.$lng.'" size="30" /><br /><br />'
.' <a href="http://dragonflycms.org/Groups/g=5.html"> Join the Translators group</a> and PM your qualifications for language CVS access<br /></div>'
.'<table border="1" cellspacing="0" cellpadding="0">';
echo'<tr style="background-color:#FFFFE6"><td style="padding: 3px">'.$lng.'</td><td style="padding: 3px">'._CORCT.'</td></tr>';
	$count = 1;
	foreach($const as $def => $value){
		$even = is_int($count/2) ? 1 : 0;
		if ($value!='') echo'<tr title="'.$def.'" '.($even ? 'style="background-color:#FFFFE6;"' : 'style="background-color:#FFFFFF"').'><td style="padding-left: 3px"><label for="'.$def.'">'.stripslashes($value).'</label></td>
		<td style="padding: 3px">';
		if (strlen($value)>=80){
			echo '<textarea style="padding-left: 3px;border:0px 0px 0px 1px dashed #DBDBDE;'.($even ? 'background-color:#FFFFE6;"' : 'background-color:#FFFFFF"').' name="'.$def.'" cols="67" rows="'.round(strlen($value)/67).'"></textarea>';
		} else {
			echo '<input type="text" style="padding-left: 3px;border:0px 0px 0px 1px dashed #DBDBDE;'.($even ? 'background-color:#FFFFE6;"' : 'background-color:#FFFFFF"').' name="'.$def.'" size="70" />';
		}
		echo "</td></tr>\n";
		$count++;
	}
	echo'</table>
	<label for="notes">'._FEELFREE.'</label><input type="text" size="100" name="notes"	 title="'._NOTE.'" /><br />
	<input type="hidden" name="op" value="send" /><input type="submit" name="Submit" value="Submit" />
	</td></tr></table></form>';
	CloseTable();
} else {
	if ($sender_name == '') {
		$name_err = '<div style="text-align:center;" class="option"><b><i>'._NLIENTERNAME.'</i></b></div><br />';
		$send = 'no';
	}
	if (is_email($sender_email) < 1) {
		$mailer_message = $PHPMAILER_LANG['recipients_failed'].$to;
		$name_err = $PHPMAILER_LANG['recipients_failed'].$_POST['$sender_email'];
		$send = 'no';
	}
	if ($send != 'no') {
		$mailcontent='';
		foreach ($_POST as $key => $val) {
      strip_tags(removecrlf("$key => $val"));
      //if ($val != ''){
      $mailcontent .= "$key = $val \n";
      //}
  }
		if ( ! ( $mail instanceof PHPMailer ) ) { $mail = new PHPMailer(true); }
		$CLASS['mail']->ClearAll();
		$sender_name = removecrlf($sender_name);
		$sender_email = removecrlf($sender_email);
		$msg = $MAIN_CFG['global']['sitename']."\n\n";
		$msg .= _SENDERNAME.': '.$sender_name."\n";
		$msg .= _SENDEREMAIL.': '.$sender_email."\n";
		$msg .= _MESSAGE.": ".stripslashes($mailcontent)."\n\n--\n";
		$msg .= _POSTEDBY." IP: ".decode_ip($userinfo['user_ip'])." \n\n"; // ChinaBrit
		$CLASS['mail']->SetLanguage();
		if($MAIN_CFG['email']['smtp_on']){
			$CLASS['mail']->IsSMTP();	// set mailer to use SMTP
			$CLASS['mail']->Host = $MAIN_CFG['email']['smtphost'];
			if ($MAIN_CFG['email']['smtp_auth']){
				$CLASS['mail']->SMTPAuth = true;	 // turn on SMTP authentication
				$CLASS['mail']->Username = $MAIN_CFG['email']['smtp_uname']; // SMTP username
				$CLASS['mail']->Password = $MAIN_CFG['email']['smtp_pass']; // SMTP password
			}
		}
		$thismail = '';
		if ($MAIN_CFG['global']['adminmail'] != 'webmaster@cpgnuke.com'){
			$CLASS['mail']->AddAddress($MAIN_CFG['global']['adminmail']);
//			  $thismail = $MAIN_CFG['global']['adminmail'];

		}
//		if ($MAIN_CFG['global']['adminmail'] != 'akamu@cpgnuke.com'){
//			$CLASS['mail']->AddAddress('akamu@cpgnuke.com');
//			  $thismail .= ' akamu@cpgnuke.com';
//		}
		$CLASS['mail']->AddAddress('cpglang@gmail.com');
		$CLASS['mail']->From = $sender_email;
		$CLASS['mail']->FromName = $sender_name;
		$CLASS['mail']->Priority = 2;
		$CLASS['mail']->Encoding = '8bit';
		$CLASS['mail']->CharSet = 'utf-8';
		$CLASS['mail']->Subject = $pagetitle;
		$CLASS['mail']->Body	= $msg;

		if (!$CLASS['mail']->Send()){
			echo 'Message could not be sent. <p>';
			echo 'Mailer Error: '.$CLASS['mail']->ErrorInfo;
		} else {
				echo '<p align="center">'._NLIMAILSENT.' '._NLITHANKSFORCONTACT.'<br />
	<fieldset style="height:120px;background-color:#FFFFFF;border:thin outset; border-color: #999999;-moz-border-radius:10px;">
	<legend style="border: thin outset; border-color: #999999;background-color:#FFFFFF;padding:3px;-moz-border-radius:15px;border-radius:15px;"><b>'.$sender_name.' '._PLSCHOOSE.'</b></legend>
	<form style="margin:auto;" name="mod" method="post" action="" enctype="multipart/form-data" accept-charset="utf-8"><div>
	<input type="hidden" name="name" value="'.$module_name.'" />
	<select name="op">
		<option value="main"  selected="selected">'._HOME.'</option>
		<option value="Blogs">'._BlogsLANG.'</option>
		<option value="coppermine">'._coppermineLANG.'</option>
		<option value="Your_Account">'._Your_AccountLANG.'</option>
		<option value="ircchat">'._CHAT.'</option>
		<option value="Contact">'._ContactLANG.'</option>
		<option value="Content">'._ContentLANG.'</option>
		<option value="cpgmm">'._MENU.'</option>
		<option value="CPGlang">'._CPGlangLANG.'</option>
		<option value="Downloads">'._DownloadsLANG.'</option>
		<option value="Encyclopedia">'._EncyclopediaLANG.'</option>
		<option value="FAQ">'._FAQLANG.'</option>
		<option value="our_sponsors">Our Sponsors</option>
		<option value="Members_List">'._Members_ListLANG.'</option>
		<option value="News">'._NewsLANG.'</option>
		<option value="Private_Messages">'._Private_MessagesLANG.'</option>
		<option value="Tell_a_Friend">'._Tell_a_FriendLANG.'</option>
		<option value="Reviews">'._ReviewsLANG.'</option>
		<option value="Search">'._SearchLANG.'</option>
		<option value="Statistics">'._StatisticsLANG.'</option>
		<option value="Stories_Archive">'._Stories_ArchiveLANG.'</option>
		<option value="Submit_News">'._Submit_NewsLANG.'</option>
		<option value="Tell_a_Friend">'._Tell_a_FriendLANG.'</option>
		<option value="Surveys">'._SurveysLANG.'</option>
		<option value="Top">'._TopLANG.'</option>
		<option value="Topics">'._TopicsLANG.'</option>
		<option value="Web_Links">'._Web_LinksLANG.'</option>
	</select>
	<input type="submit" name="Submit" value="Submit" />
</div></form></fieldset>';
			echo '<pre>'.$msg.'</pre><br />';

	CloseTable();
}
	} else {
		OpenTable2();
		echo $name_err.$email_err._GOBACK;
		CloseTable2();
	}
}
echo '<br />';
