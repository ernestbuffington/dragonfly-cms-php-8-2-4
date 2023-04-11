<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$bb_codes['quote'] = '<details class="bbquote" open=""><blockquote>';
$bb_codes['quote_name'] = '<details class="bbquote"><summary>\\1 '.$bbcode_common['Wrote'].'</summary><blockquote>';
$bb_codes['quote_close'] = '</blockquote></details>';

$bb_codes['code_start'] = '<code class="bbcode_code">';
$bb_codes['code_end']   = '</code>';
