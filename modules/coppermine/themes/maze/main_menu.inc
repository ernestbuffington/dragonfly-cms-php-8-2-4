<?php
/***************************************************************************
   Coppermine Photo Gallery 1.3.1 for CPG-Nuke
  **************************************************************************
   Port Copyright (C) 2004-2005 Coppermine/CPG-Nuke Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 originally written by Grégory Demar
   Copyright (C) 2002,2003 http://coppermine.sf.net/team/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
  **************************************************************************
  Last modification notes:
  $Source: /public_html/modules/coppermine/themes/maze/main_menu.inc,v $
  $Revision: 9.0 $
  $Author: djmaze $
  $Date: 2005/01/12 03:32:59 $
****************************************************************************/
$template_main_menu = '
                <span class="topmenu">
<!-- BEGIN album_list -->
<!--                   <a href="'.getlink("").'" title="'.ALB_LIST_TITLE.'">'.ALB_LIST_LNK.'</a> ::-->
<!-- END album_list -->
<!-- BEGIN upload_pic -->
                        <a href="'.getlink("&amp;file=upload").'" title="'.UPLOAD_PIC_TITLE.'">'.UPLOAD_PIC_LNK.'</a> ::
<!-- END upload_pic -->
<!-- BEGIN register -->
                        <a href="'.NEWUSER_URL.'" title="'.REGISTER_TITLE.'">'.REGISTER_LNK.'</a> ::
<!-- END register -->
<!-- BEGIN meta_links -->
                        <a href="'.getlink("&amp;file=thumbnails&amp;meta=lastup".META_LNK).'">'.LASTUP_LNK.'</a> ::
                        <a href="'.getlink("&amp;file=thumbnails&amp;meta=lastcom".META_LNK).'">'.LASTCOM_LNK.'</a> ::
                        <a href="'.getlink("&amp;file=thumbnails&amp;meta=topn".META_LNK).'">'.TOPN_LNK.'</a> :: 
                        <a href="'.getlink("&amp;file=thumbnails&amp;meta=toprated".META_LNK).'">'.TOPRATED_LNK.'</a> ::
                        <a href="'.getlink("&amp;file=thumbnails&amp;meta=favpics").'">'.FAV_LNK.'</a> <br /><br />    
                        <a href="'.getlink("&amp;file=search").'">'.SEARCH_LNK.'</a> ::
                        <a href="'.getlink("&amp;file=help").'" title="'.HELP_TITLE.'" class="helplink">'.HELP_LNK.'</a>
<!-- END meta_links -->
                </span>';