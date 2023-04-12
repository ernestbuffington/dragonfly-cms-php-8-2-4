<?php
$html = '
<table cellpadding="0" cellspacing="1" width="100%">
     <tr valign="middle" align="center">
          <td class="admin_menu" width="15%"><a href="'.getlink("&amp;file=editpics&amp;mode=upload_approval").'>'.UPL_APP_LNK.'</a>
          </td>
          <td class="admin_menu" width="15%"><a href="'.$CPG_URL.'&file=searchnew">'.SEARCHNEW_LNK.'</a>
          </td>
          <!--<td class="admin_menu">
                               <a href="'.$CPG_URL.'&file=util">'.UTIL_LNK.'</a>
                                </td> -->
          <td class="admin_menu"width="15%"><a href="'.$CPG_URL.'&file=reviewcom">'.COMMENTS_LNK.'</a>
          </td>
     <td class="admin_menu" width="15%"><a href="'.$CPG_URL.'&file=groupmgr">'.GROUPS_LNK.'</a></td>
          <td class="admin_menu" width="15%"><a href="'.$CPG_URL.'&file=usermgr">'.USERS_LNK.'</a></td>
     
          <td class="admin_menu" width="15%"><a href="'.$CPG_URL.'&file=albmgr'.$cat_l.'">'.ALBUMS_LNK.'</a>
          </td>
          <td class="admin_menu" width="15%"><a href="'.$CPG_URL.'&file=catmgr">'.CATEGORIES_LNK.'</a>
          </td>';
          if(is_admin() && CPG_NUKE){
                $html .= '<td class="admin_menu" width="15%"><a href="'.$CPG_URL.'&file=config">'.CONFIG_LNK.'</a>
                </td>';
           }
$html .= '</tr>
</table>';
?>
