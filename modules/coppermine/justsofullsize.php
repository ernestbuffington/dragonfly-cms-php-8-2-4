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
  $Source: /cvs/html/modules/coppermine/justsofullsize.php,v $
  $Revision: 9.3 $
  $Author: nanocaiordo $
  $Date: 2006/12/21 16:57:57 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('DISPLAYIMAGE_PHP', true);
define('NO_HEADER', true);
require("modules/" . $module_name . "/include/load.inc");

if (is_numeric($pid)) {
    $result = $db->sql_query("SELECT filepath,filename,pwidth,pheight,p.title FROM {$CONFIG['TABLE_PICTURES']} as p INNER JOIN {$CONFIG['TABLE_ALBUMS']} ON (".VIS_GROUPS.") WHERE approved = '1' AND p.pid = $pid GROUP BY p.pid");
    $row = $db->sql_fetchrow($result);
    if ($row[0]=''){
        cpg_die(_ERROR, MEMBERS_ONLY);
    }
    $pic_url = get_pic_url($row, 'fullsize');
    $geom = 'width="' . $row['pwidth'] . '" height="' . $row['pheight'] . '"'; 
} else {
    cpg_die(3, PARAM_MISSING);
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title> <?php echo $row["title"];?> ( <?php echo $lang_fullsize_popup["click_to_close"] ?>)</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="imagetoolbar" content="no">
<link rel="stylesheet" href="<?php echo $CPG_M_DIR;?>/themes/default/style.css" />
<meta http-equiv="imagetoolbar" content="no">
<style type="text/css">
<!--
.imgtblCover { overflow: visible; position: absolute; visibility: visible; left: 0px; top: 0px; ; z-index: 2;  background-attachment: fixed; background-image: url(/<?php echo $CPG_M_DIR;?>/images/spacer.gif); background-repeat: repeat; height: 95%; width: 100%}
-->
</style>
<link rel="stylesheet" href="/modules/coppermine/themes/default/style.css" type="text/css" />
</head>
<body onclick="self.close()" onblur="self.close()">
<table width="100%" border="0" cellpadding="0" cellspacing="2" class="imgtbl">
     <tr><td align="center" valign="middle"> 
    <?php 
    print '<img src="' . $pic_url . '" ' . $geom . ' class="image" border="0" alt="' . $alt . '" />';
 
?></td></tr>
</table>
<table border="0" cellpadding="0" class="imgtblCover">
     <tr onclick="self.close()"><td align="center" valign="bottom"><p class="piccopy">&copy <?php echo $sitename ?></p>
</td></tr>
</table>
</body>
</html>
