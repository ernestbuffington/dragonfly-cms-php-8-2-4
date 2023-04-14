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
  $Source: /public_html/includes/coppermine/crop.inc,v $
  $Revision: 9.1 $
  $Author: trevor $
  $Date: 2005/02/18 00:16:16 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

// ////////////////// Variables //////////////////////////////
// used texts
$txt['bigger'] = ">";
$txt['smaller'] = "<";
$txt['up'] = "^";
$txt['down'] = "v";
$txt['closewindow'] = "Close window";

$result = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_PICTURES']} WHERE pid = '$pid'");
$CURRENT_PIC = $db->sql_fetchrow($result);
$db->sql_freeresult($result);
$pic_url = get_pic_url($CURRENT_PIC, 'fullsize');

global $CPG_M_DIR;

echo <<<cropUIjs
<script language="JavaScript" src="$CPG_M_DIR/dhtmlLib.js"></script>
<script language="JavaScript">
<!--
function libinit(){
        obj=new lib_obj('cropDiv')
        obj.dragdrop()
        objImg =new lib_obj('imgDiv')
        //alert (objImg.x + "-" + objImg.y);        
        obj.moveIt(objImg.x,objImg.y)

        
}

function cropCheck(crA){
  if (((obj.x + obj.cr) <= ({$CURRENT_PIC['pwidth']}+objImg.x))&&((obj.y + obj.cb) <= ({$CURRENT_PIC['pheight']}+objImg.y))&&(obj.x > objImg.x)&&(obj.y > objImg.y)){
    //alert (obj.x + "-" + obj.y);
    cropX = obj.x - objImg.x;
    cropY = obj.y - objImg.y;
    var url = 'cropAction.php?pop=1&x='+cropX+'&y='+cropY+'&h='+obj.cb+'&w='+obj.cr+'&id={$pid}' ;
    if (crA == 'pre'){
      window.open(url,'prevWin','width='+obj.cr+',height='+obj.cb);
    }else{
      location.href=url;
    }
  } else {
    alert('{$lang_editpics_php['sel_on_img']}');
  }
}

function stopZoom() {
  loop = false;
  clearTimeout(zoomtimer);
}

function cropZoom(dir) {
  loop = true;
  zoomtimer = null;
  direction = dir;
  if (loop == true) {
    if (direction == "in") {
      if ((obj.cr > 60 )){
        cW = obj.cr - 1;
        cH = obj.cb ;        
        obj.clipTo(0,cW,cH,0,1);
      }
    } else if (direction == "out") {
      if ((obj.cr < ({$CURRENT_PIC['pwidth']}-5))){
        cW = obj.cr + 1;
        cH = obj.cb ;
        obj.clipTo(0,cW,cH,0,1);
      }
    } else if (direction == "down") {    
      if ((obj.cb < ({$CURRENT_PIC['pheight']}-5) )){
        cW = obj.cr ;
        cH = obj.cb + 1;
        obj.clipTo(0,cW,cH,0,1);
      }
    } else if (direction == "up") {    
      if ((obj.cb > 60 )){
        cW = obj.cr ;
        cH = obj.cb - 1;
        obj.clipTo(0,cW,cH,0,1);
      }
    }
    zoomtimer = setTimeout("cropZoom(direction)", 10);
  }
}

onload=libinit;
// -->
</script>
<style>

#cropDiv{
        position:absolute;
        left:10px;
        top:10px;
        width:60px;
        height:60px;
        z-index:2;
        background-image: url($CPG_M_DIR/images/spacer.gif);
}

#imgDiv{
        position:relative;
}
</style>
cropUIjs;

starttable("100%", $lang_editpics_php['crop_title'], 3);
echo <<<EOT
<tr><td>
<table cellspacing="0" cellpadding="5" border="0" align="center">
<tr><td>
<table cellspacing="0" cellpadding="5" border="0" align="center">
<tr><td align="center" valign="top"><DIV ID="imgDiv"><IMG SRC="$pic_url"></DIV></td></tr>
<tr>
<td align="right">
    <input type="button" name="Submit2" value="{$lang_editpics_php['save']}" onclick="cropCheck('pre');" class="button" >
    <input type="button" name="Submit2" value="{$lang_editpics_php['preview']}" onclick="cropCheck('pre');" class="button" >
    <input type="button" name="Submit3" value="{$txt['smaller']}" onMouseDown="cropZoom('in');" onMouseUp="stopZoom();" class="button" >
    <input type="button" name="Submit3" value="{$txt['up']}" onMouseDown="cropZoom('up');" onMouseUp="stopZoom();" class="button" >
    <input type="button" name="Submit3" value="{$txt['down']}" onMouseDown="cropZoom('down');" onMouseUp="stopZoom();" class="button" >
    <input type="button" name="Submit4" value="{$txt['bigger']}" onMouseDown="cropZoom('out');" onMouseUp="stopZoom();" class="button" >
</td>
</tr>
  </FORM>
</table>
</td></tr>
</table>
</td></tr>
</table>

<DIV ID="cropDiv">
  <table width="100%" border="1" cellpadding="0" cellspacing="0" bordercolor="#000000">
<tr>
      <td><img src="$CPG_M_DIR/images/spacer.gif"></td>
    </tr>
  </table>
</DIV>
EOT;