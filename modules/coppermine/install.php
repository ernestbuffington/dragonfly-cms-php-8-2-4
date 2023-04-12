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
if (!isset($name)) {
    $dirname = basename(dirname(__FILE__));
    $name = $dirname;
    chdir("../../");
    $dirlogo = "images";
}
else {
    $dirname = $name;
    $dirlogo = "modules/$name/images";
}

define('INSTALL_PHP', true);
define('NO_HEADER', true);
require("modules/" . $name . "/include/load.inc");
$CPG_VERSION = '1.3.0b';

$login_url = LOGIN_URL;
if ($dirlogo == "images") {
    $INST_URL = "install.php";
    $CPG_URL = "../../$CPG_URL";
    $login_url = "../../$login_url";
}
else $INST_URL = "$CPG_URL&file=install";

echo '<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Coppermine - Installation</title><link type="text/css" rel="stylesheet" href="installer.css">
</head>
<body>
 <div align="center" style="width:600px;">
      <table width="100%" border="0" cellpadding="0" cellspacing="1" class="maintable">
       <tr>
        <td valign="top" bgcolor="#EFEFEF"><img src="'.$dirlogo.'/logo.gif"><br />
        </td>
       </tr>
      </table><br />';

$phpver = phpversion();
$phpver = "$phpver[0]$phpver[2]";
if ($phpver < 41) {
    echo "You need atl east PHP version 4.1 to use Coppermine 4 CMS</a>";
    cpgfooter();
    die();
}

// check if user has access
if (!can_admin()) {
    echo "You don't have permission to access this file !<p><a href=\"$login_url\">Login as Admin</a>";
    cpgfooter();
    die();
} 

$installtype = 0; // 0 = new, 1 = 1.1D, 2 = 1.2, 3 = 1.2 RC, 4 = 1.2 RC5, 5 = 1.2.2 / 1.2.2a

// check if this is an upgrade
if (file_exists($CPG_M_DIR."/include/config.inc.php")) {
    include($CPG_M_DIR."/include/config.inc.php");
    if ($CONFIG['TABLE_PREFIX']) {
        // CPG 1.1D
        $cpg_prefix = $CONFIG['TABLE_PREFIX'];
        $installtype = 1;
    }
    else $installtype = 2; // CPG 1.2 RC1/2
}
// check if CPG 1.3 or higher is already installed
$result = mysql_query("SELECT * FROM cpg_installs WHERE dirname='$dirname'");
if (!is_null($result[1])) {
   mysql_query("RENAME TABLE cpg_installs TO ".$prefix."_cpg_installs");
   $result = mysql_query("SELECT * FROM ".$prefix."_cpg_installs WHERE dirname='$dirname'");   
}else{
     $result = mysql_query("SELECT * FROM ".$prefix."_cpg_installs WHERE dirname='$dirname'");
}     
if (!is_null($result[1])) {
    $row = $db->sql_fetchrow($result);
    if (isset($row[1]) && isset($row[3])) {
        if ($row[1] == $dirname && $row[3] == $CPG_VERSION) {
            echo '
      <table width="100%" border="0" cellpadding="0" cellspacing="1" class="maintable">
       <tr>
        <td class="tableh1" colspan="2"><h2>Coppermine is already installed</h2>
        </td>
       </tr>
       <tr>
        <td class="tableh2" colspan="2" align="center"><span class="error">&#149;&nbsp;&#149;&nbsp;&#149;&nbsp;ERROR&nbsp;&#149;&nbsp;&#149;&nbsp;&#149;</span>
        </td>
       </tr>
       <tr>
        <td class="tableb" colspan="2">The install/upgrade has already been run successfuly for "' . $dirname . '" and is now locked.
        </td>
       </tr>
      </table>';
            cpgfooter();
            die();
        }
    }
    if ($row[1] == $dirname) {
        $cpg_prefix = $row[2];
        if ($row[3] == "1.2.2b") { $installtype = 6; }
        elseif ($row[3] == "1.2.2" || $row[3] == "1.2.2a") { $installtype = 5; }
        elseif ($row[3] == "1.2 RC5") { $installtype = 4; }
        else                          { $installtype = 3; }
    }
}
clearstatcache(); // frees filexists caching

// Show upgrade page ?
if (isset($cpg_prefix) && !isset($_POST['update'])) {
    $versions=array('new', '1.1D', '1.2', '1.2 RC', '1.2 RC5', '1.2.2 / 1.2.2a');
    echo '
      <table width="100%" border="0" cellpadding="0" cellspacing="1" class="maintable">
       <tr>
        <td class="tableh1"><h2>Coppermine update</h2>
        </td>
       </tr>
       <tr>
        <td class="tableb" align="center">The installer noticed you are already running Coppermine <b>'.$versions[$installtype].'</b> but it needs to be updated.<br />
          Click below to begin update.
          <form action="'.$INST_URL.'" method="post">
          <input type="hidden" name="table_prefix" value="' . $cpg_prefix . '">
          <input type="hidden" name="update" value="1">
          <input type="submit" value="Update now!">
          </center></form>
        </td>
       </tr>
      </table>';
    cpgfooter();
    die();
} 
// else show install page
else if (!isset($_POST['table_prefix'])) {
    echo '
  <form action="'.$INST_URL.'" method="post"></center>
  Welcome and thanks for your interest in the cool Coppermine Photo Gallery<br />
  Before you can use coppermine setup below settings.
  <table>
    <tr>
      <td>Coppermine tables prefix:</td>
      <td><input type="text" name="table_prefix" maxlength="20" value="nuke_cpg_"></td>
    </tr>
  </table>
  <input type="submit" value="install">
  </center></form>';
    cpgfooter();
    die();
} 
// run installation/upgrade
else {
    $result = $db->sql_query("SELECT * FROM `nuke_cpg_installs` WHERE prefix='".$_POST['table_prefix']."'");
    if ($result && $installtype < 3) {
        $row = $db->sql_fetchrow($result);
        if (isset($row['prefix'])) {
            if ($row['prefix'] == $_POST['table_prefix']) {
                echo '
  <form action="'.$INST_URL.'" method="post"></center>
  The installer noticed there\'s already a install with the prefix "'.$_POST['table_prefix'].'"<br />
  Please create a other prefix.
  <table>
    <tr>
      <td>Coppermine tables prefix:</td>
      <td><input type="text" name="table_prefix"></td>
    </tr>
  </table>
  <input type="submit" value="install">
  </center></form>';
                cpgfooter();
                die();
        }   }
    }
    global $sql;
    $sql = array();
    $table_prefix = $_POST['table_prefix'];

    if ($installtype == 0) {
        // New install
        require('modules/'.$dirname.'/install/new.php');
    }
    else if ($installtype == 1) {
        // 1.1D
        require('modules/'.$dirname.'/install/cpg11d.php');
    }
    else if ($installtype == 2) {
        // 1.2
        require('modules/'.$dirname.'/install/cpg12.php');
    }
    else if ($installtype == 3) {
        // 1.2 rc
        require('modules/'.$dirname.'/install/cpg12rc.php');
    }
    if ($installtype < 5) {
        // 1.2 rc 5
        require('modules/'.$dirname.'/install/cpg12rc5.php');
    }

    if (!mysql_query("SELECT * FROM `".$prefix."_cpg_installs` LIMIT 0")) {
        $sql[] = "CREATE TABLE `".$prefix."_cpg_installs` (`cpg_id` TINYINT (3) NOT NULL AUTO_INCREMENT, `dirname` VARCHAR (20) NOT NULL, `prefix` VARCHAR (20) NOT NULL, `version` VARCHAR(10), PRIMARY KEY(`cpg_id`))";
    }
    else {
        $sql[] = "ALTER TABLE `".$prefix."_cpg_installs` CHANGE `prefix` `prefix` VARCHAR(20) NOT NULL";
    }
    if ($installtype < 6) {
        // 1.2.2b
        if ($installtype < 3)
            $sql[] = "INSERT INTO `".$prefix."_cpg_installs` VALUES(DEFAULT, '" . $dirname . "', '" . $_POST['table_prefix'] . "', '$CPG_VERSION');";
        else if ($installtype == 3)
            $sql[] = "ALTER TABLE ".$prefix."_cpg_installs ADD version VARCHAR(10);";

        $result = mysql_query("SELECT user_group_cp FROM `".$user_prefix."_users` LIMIT 0");
        if (!$result) {
            $sql[] = "ALTER TABLE `".$user_prefix."_users` ADD `user_group_cp` INT DEFAULT 2 NOT NULL;";
            $sql[] = "ALTER TABLE `".$user_prefix."_users` ADD `user_active_cp` TINYINT DEFAULT 1 NOT NULL;";
            $sql[] = "UPDATE ".$user_prefix."_users SET user_group_cp = '1' WHERE user_id = '2';";
        }
        $result = mysql_query("SELECT user_group_list_cp FROM `".$user_prefix."_users` LIMIT 0");
        if (!$result)
            $sql[] = "ALTER TABLE `".$user_prefix."_users` ADD `user_group_list_cp` VARCHAR(100) DEFAULT '2' NOT NULL AFTER `user_group_cp`;";
        if ($installtype < 5) {
            $sql[] = "UPDATE ".$user_prefix."_users SET user_group_cp = '3', user_group_list_cp = '3' WHERE user_id = '-1';";
            $sql[] = "UPDATE ".$user_prefix."_users SET user_group_cp = '3', user_group_list_cp = '3' WHERE user_id = '1';";
        }
    }
    if (!mysql_query("SELECT * FROM `".$prefix."_bbsmilies` LIMIT 0")) {
        require('modules/'.$dirname.'/install/smilies.php');
    }
    $sql[] = "INSERT INTO ".$table_prefix."config VALUES ('avatar_private_album', '0')";
    $sql[] = "ALTER TABLE ".$prefix."_cpg_pictures DROP INDEX search, ADD FULLTEXT search (title,caption,keywords,filename,user1,user2,user3,user4)";
    $sql[] = "UPDATE ".$prefix."_cpg_installs SET version = '$CPG_VERSION' WHERE dirname = '$dirname';";

    echo "Check if all queries succeeded <img src=\"$dirlogo/green.gif\" alt=\"Succeed\" title=\"Succeed\"><br />";
    echo "<table border=1>";

    foreach($sql as $query) {
      if ($query != "") {
        if (!mysql_query($query)) {
            echo "<tr><td><font size=1>$query<p>mySQL Error: " . mysql_error() . "</font></td><td><img src=\"$dirlogo/red.gif\" alt=\"Failed\" title=\"Failed\"></td></tr>";
        } else echo "<tr><td><font size=1>$query</font></td><td><img src=\"$dirlogo/green.gif\" alt=\"Succeed\" title=\"Succeed\"></tr></tr>";
      }
    } 
    echo "</table>";
    echo "When everything is correct please go to your <a href=\"$CPG_URL&file=config\">Coppermine config screen</a>";

    cpgfooter();
} 

function cpgfooter()
{
    echo '
 </div>
</body>
</html>';
} 
?>
