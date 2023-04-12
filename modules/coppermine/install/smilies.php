<?php 
// ------------------------------------------------------------------------- //
// Coppermine Photo Gallery for CMS                                          //
// ------------------------------------------------------------------------- //
// Copyright (C) 2002,2003 Gregory DEMAR <gdemar@wanadoo.fr>                 //
// http://www.chezgreg.net/coppermine/                                       //
// ------------------------------------------------------------------------- //
// Updated by the Coppermine Dev Team                                        //
// (http://coppermine.sf.net/team/)                                          //
// see /docs/credits.html for details                                        //
// ------------------------------------------------------------------------- //
// This program is free software; you can redistribute it and/or modify      //
// it under the terms of the GNU General Public License as published by      //
// the Free Software Foundation; either version 2 of the License, or         //
// (at your option) any later version.                                       //
// ------------------------------------------------------------------------- //
if (!defined('INSTALL_PHP')) {
  die('Your are not allowed to access this page');
}
global $sql, $prefix;

$sql[] = "DROP TABLE IF EXISTS ".$prefix."_bbsmilies";
$sql[] = "CREATE TABLE ".$prefix."_bbsmilies (
  smilies_id smallint(5) unsigned NOT NULL auto_increment,
  code varchar(50) default NULL,
  smile_url varchar(100) default NULL,
  emoticon varchar(75) default NULL,
  PRIMARY KEY  (smilies_id)
) TYPE=MyISAM";

$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("1", ":D", "icon_biggrin.gif", "Very Happy")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("2", ":-D", "icon_biggrin.gif", "Very Happy")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("3", ":grin:", "icon_biggrin.gif", "Very Happy")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("4", ":)", "icon_smile.gif", "Smile")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("5", ":-)", "icon_smile.gif", "Smile")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("6", ":smile:", "icon_smile.gif", "Smile")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("7", ":(", "icon_sad.gif", "Sad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("8", ":-(", "icon_sad.gif", "Sad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("9", ":sad:", "icon_sad.gif", "Sad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("10", ":o", "icon_surprised.gif", "Surprised")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("11", ":-o", "icon_surprised.gif", "Surprised")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("12", ":eek:", "icon_surprised.gif", "Surprised")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("13", "8O", "icon_eek.gif", "Shocked")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("14", "8-O", "icon_eek.gif", "Shocked")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("15", ":shock:", "icon_eek.gif", "Shocked")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("16", ":?", "icon_confused.gif", "Confused")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("17", ":-?", "icon_confused.gif", "Confused")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("18", ":???:", "icon_confused.gif", "Confused")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("19", "8)", "icon_cool.gif", "Cool")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("20", "8-)", "icon_cool.gif", "Cool")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("21", ":cool:", "icon_cool.gif", "Cool")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("22", ":lol:", "icon_lol.gif", "Laughing")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("23", ":x", "icon_mad.gif", "Mad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("24", ":-x", "icon_mad.gif", "Mad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("25", ":mad:", "icon_mad.gif", "Mad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("26", ":P", "icon_razz.gif", "Razz")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("27", ":-P", "icon_razz.gif", "Razz")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("28", ":razz:", "icon_razz.gif", "Razz")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("29", ":oops:", "icon_redface.gif", "Embarassed")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("30", ":cry:", "icon_cry.gif", "Crying or Very sad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("31", ":evil:", "icon_evil.gif", "Evil or Very Mad")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("32", ":twisted:", "icon_twisted.gif", "Twisted Evil")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("33", ":roll:", "icon_rolleyes.gif", "Rolling Eyes")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("34", ":wink:", "icon_wink.gif", "Wink")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("35", ";)", "icon_wink.gif", "Wink")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("36", ";-)", "icon_wink.gif", "Wink")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("37", ":!:", "icon_exclaim.gif", "Exclamation")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("38", ":?:", "icon_question.gif", "Question")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("39", ":idea:", "icon_idea.gif", "Idea")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("40", ":arrow:", "icon_arrow.gif", "Arrow")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("41", ":|", "icon_neutral.gif", "Neutral")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("42", ":-|", "icon_neutral.gif", "Neutral")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("43", ":neutral:", "icon_neutral.gif", "Neutral")';
$sql[] = 'INSERT INTO '.$prefix.'_bbsmilies VALUES("44", ":mrgreen:", "icon_mrgreen.gif", "Mr. Green")';

?>
