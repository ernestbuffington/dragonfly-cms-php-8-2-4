<?php
$template_album_admin_menu = '<table border="0" cellpadding="0" cellspacing="1">
                <tr>    <td align="center" valign="middle">
                                <form method="post" action="'.getlink("&amp;file=delete").'">
                                <input type="hidden" name="what" value="album" />
                                <input type="hidden" name="id" value="{ALBUM_ID}" />
                        <input type="submit" name="submit" class="admin_menu"  value="'.DELETE.'" /></form>
                
                        </td><td align="center" valign="middle">
                                <form method="post" action="'.getlink("&amp;file=modifyalb").'">
                                <input type="hidden" name="album" value="{ALBUM_ID}" />
                        <input type="submit" name="submit" class="admin_menu"  value="'.MODIFY.'" /></form>
                
                        </td>
                        <td align="center" valign="middle">
                                <form method="post" action="'.getlink("&amp;file=editpics").'">
                                <input type="hidden" name="album" value="{ALBUM_ID}" />
                        <input type="submit" name="submit" class="admin_menu"  value="'.EDIT_PICS.'" /></form>
                        </td>
                </tr>
        </table>';
?>
