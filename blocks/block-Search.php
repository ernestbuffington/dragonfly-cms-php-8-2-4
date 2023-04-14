<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Search.php,v $
  $Revision: 9.6 $
  $Author: phoenix $
  $Date: 2007/05/31 03:29:51 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_active('Search')) {
	$content = 'ERROR';
	return trigger_error('Search module is inactive', E_USER_WARNING);
}
$content = '
<form action="'.getlink('Search').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
<div style="text-align:center;">
<input type="text" name="search" size="20" maxlength="255" /><br /><br /><input type="submit" value="'._SEARCH.'" />
</div></form>';
