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
  $Source: /public_html/modules/coppermine/search.php,v $
  $Revision: 9.0 $
  $Author: djmaze $
  $Date: 2005/01/12 03:32:54 $
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }
require("modules/" . $module_name . "/include/load.inc");
$pagetitle .= ' '._BC_DELIM.' '.S_SEARCH;
pageheader(S_SEARCH);
echo open_form(getlink('&amp;file=thumbnails',0), 'cpgsearch', S_SEARCH);
echo '
    <input type="hidden" name="type" value="full" />
    <input type="hidden" name="meta" value="search" />
    <input type="text" name="search" maxlength="255" size="80" value="" class="textinput" />
    <input type="submit" value="'._SEARCH.'" class="button" />
';
echo close_form();
pagefooter();