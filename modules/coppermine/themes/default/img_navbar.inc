<?php
$template_img_navbar = '<tr>
                <td style="text-align:center" valign="middle" class="navmenu" width="48">
                        <a href="{THUMB_TGT}" class="navmenu_pic" title="'.THUMB_TITLE.'"><img src="'.$THEME_DIR.'/images/folder.gif" width="16" height="16" align="absmiddle" border="0" alt="'.THUMB_TITLE.'" /></a>
                </td>
                <td style="text-align:center" valign="middle" class="navmenu" width="48">
                        <a href="javascript:;" onclick="blocking(\'picinfo\',\'yes\', \'block\'); return false;" title="'.PIC_INFO_TITLE.'" alt="'.PIC_INFO_TITLE.'"><img src="'.$THEME_DIR.'/images/info.gif" width="16" height="16" border="0" align="absmiddle" alt="'.PIC_INFO_TITLE.'" /></a>
                </td>
                <td style="text-align:center" valign="middle" class="navmenu" width="48">
                        <a href="{SLIDESHOW_TGT}" title="' .SLIDESHOW_TITLE. '"><img src="'.$THEME_DIR.'/images/slideshow.gif" width="16" height="16" border="0" align="absmiddle" alt="' .SLIDESHOW_TITLE. '" /></a>
                </td>
                <td style="text-align:center" valign="middle" class="navmenu" witdh="100%">
                        {PIC_POS}
                </td>
                <td style="text-align:center" valign="middle" class="navmenu" width="48">
                        <a href="{ECARD_TGT}" title="' .ECARD_TITLE. '"><img src="'.$THEME_DIR.'/images/ecard.gif" width="16" height="16" border="0" align="absmiddle" alt="' .ECARD_TITLE. '" /></a>
                </td>
                <td style="text-align:center" valign="middle" class="navmenu" width="48">
                        <a href="{PREV_TGT}" class="navmenu_pic" title="' .PREV_TITLE. '"><img src="'.$THEME_DIR.'/images/prev.gif" width="16" height="16" border="0" align="absmiddle" alt="' .PREV_TITLE. '" /></a>
                </td>
                <td style="text-align:center" valign="middle" class="navmenu" width="48">
                        <a href="{NEXT_TGT}" class="navmenu_pic" title="' .NEXT_TITLE. '"><img src="'.$THEME_DIR.'/images/next.gif" width="16" height="16" border="0" align="absmiddle" alt="' .NEXT_TITLE. '" /></a>
                </td>
        </tr>';
?>