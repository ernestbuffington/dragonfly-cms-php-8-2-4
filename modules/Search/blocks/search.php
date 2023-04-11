<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

$content = '
<form action="'.URL::index('Search').'" method="post">
<div style="text-align:center;">
<input type="text" name="search" size="20" maxlength="255" /><br /><br /><input type="submit" value="'._SEARCH.'" />
</div></form>';
