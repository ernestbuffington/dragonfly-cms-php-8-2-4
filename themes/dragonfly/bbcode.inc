<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/themes/dragonfly/bbcode.inc,v $
  $Revision: 9.4 $
  $Author: nanocaiordo $
  $Date: 2008/01/14 13:12:11 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$bb_codes['quote'] = '<div class="bbquote">
  <div class="bbhead">'.$bbcode_common['quote'].':</div>
  <div class="bbcontent">';
$bb_codes['quote_name'] = '<div class="bbquote">
  <div class="bbhead">\\1 '.$bbcode_common['Wrote'].':</div>
  <div class="bbcontent">';
$bb_codes['quote_close'] = '</div></div>';

$bb_codes['code_start'] = '<div>
  <div class="bbchead">'.$bbcode_common['code'].':</div>
  <div class="bbccontent"><code>';
$bb_codes['code_end'] =  '</code></div></div>';

$bb_codes['php_start'] = '<div>
  <div class="bbphphead">PHP:</div>
  <div class="bbphpcontent">';
$bb_codes['php_end'] = '</div></div>';