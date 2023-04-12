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
  $Source: /cvs/html/modules/coppermine/displayimagepopup.php,v $
  $Revision: 9.4 $
  $Author: nanocaiordo $
  $Date: 2007/04/06 10:52:34 $
****************************************************************************/
if (preg_match('#modules\/#mi', $_SERVER['PHP_SELF'])) {
    die ("You can't access this file directly...");
}
define('DISPLAYIMAGE_PHP', true);
define('NO_HEADER', true);
require("modules/" . $module_name . "/include/load.inc");
header('Content-Type: text/html; charset=utf-8');
header('Content-language: ' . LANG_COUNTRY_CODE );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="<?php echo LANG_COUNTRY_CODE;?>">
<head>
    <base href="<?php echo $BASEHREF ?>" />
<title><?php echo CLICK_TO_CLOSE ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo _CHARSET ?>" />
<link rel="stylesheet" href="<?php echo $CPG_M_DIR;?>/themes/default/style.css" />
<meta http-equiv="imagetoolbar" content="no" />
<style type="text/css">
<!--
.imgtbl {  position: absolute; left: 0px; top: 0px; overflow: scroll; }
-->
</style>

</head>
<!-- <body>
<a href="javascript:self.close();" title="<?php echo CLICK_TO_CLOSE;?>"><table width="100%" border="0" cellpadding="0" cellspacing="2" class="imgtbl">
-->
<body>
<table width="100%" border="0" cellpadding="0" cellspacing="2" class="imgtbl" title="<?php echo $lang_fullsize_popup["click_to_close"];?>">
     <td align="center" valign="middle"> 
          <table cellspacing="2" cellpadding="0" style="border: 1px solid #000000; background-color: #FFFFFF;">
               <td> 
<?php 
if (isset($_GET['picfile'])) {
    $picfile = $_GET['picfile'];
    $picname = $CONFIG['fullpath'].$picfile;
    if (preg_match('#\.\.#m', $picfile)) {
        $picfile = 'Error';
        $picname = 'images/error.gif';
    }
    $imagesize = getimagesize($picname);
    echo "<a href=\"javascript:window.close();\"><img src=\"" . path2url($picname) . "\" $imagesize[3] class=\"image\" border=\"0\" alt=\"$picfile\" /></a><br />\n";
} elseif (isset($pid)) {
    //init.inc $pid = $_GET['pid'];
    $result = $db->sql_query("SELECT * from {$CONFIG['TABLE_PICTURES']} where pid='$pid'",false,__FILE__,__LINE__);
    $row = $db->sql_fetchrow($result);
    $pic_url = get_pic_url($row, 'fullsize');
    $geom = 'width="' . $row['pwidth'] . '" height="' . $row['pheight'] . '"'; 
    print '<a href="javascript:window.close();"><img src="' . $pic_url . '" ' . $geom . ' class="image" border="0" alt="' . CLICK_TO_CLOSE . '" title="' . CLICK_TO_CLOSE . '" /></a>';
} 

?>               </td>
          </table>
     </td>
</table><!-- </a> 
<script language="JavaScript" type="text/javascript">
</script>-->
</body>
</html>
