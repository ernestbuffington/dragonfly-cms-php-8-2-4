<?php
if (!isset($CURRENT_PIC['pid']))
$CURRENT_PIC['pid'] = '';

$template_edit_one_pic = '
<!-- BEGIN header -->
<SCRIPT LANGUAGE="JavaScript">
function textCounter(field, maxlimit) {
        if (field.value.length > maxlimit) // if too long...trim it!
        field.value = field.value.substring(0, maxlimit);
}
</script>
        <form method="post">
        <input type="hidden" name="id" value="'.$CURRENT_PIC['pid'].'" />
        <tr>
            <td class="tableh2" colspan="3">
                <b>'.$filename.'</b>
            </td>
        </tr>
        <tr>
            <td class="tableb" style="white-space: nowrap;">
                        '.PIC_INFO.'
            </td>
            <td class="tableb">
                '.$pic_info.'
            </td>
                <td class="tableb" align="center" rowspan="'.$THUMB_ROWSPAN.'">
                <img src="'.$thumb_url.'" class="image" border="0" alt="" /><br />
            </td>
        </tr>
<!-- END header -->
<!-- BEGIN main -->
        <tr>
            <td class="tableb" style="white-space: nowrap;">
                '.TITLE.'
            </td>
            <td width="100%" class="tableb" valign="top">
                <input type="text" style="width: 100%" name="title" maxlength="255" value="'.$CURRENT_PIC['title'].'" class="textinput" />
            </td>
        </tr>

        <tr>
            <td class="tableb" valign="top" style="white-space: nowrap;">
                '.DESCRIPTION.'
            </td>
            <td class="tableb" valign="top">
                <textarea name="caption" rows="5" cols="40" wrap="virtual"  class="textinput" style="width: 100%;" onkeydown="textCounter(this, '.$CONFIG['max_img_desc_length'].');" onkeyup="textCounter(this, '.$CONFIG['max_img_desc_length'].');">'.$CURRENT_PIC['caption'].'</textarea>
            </td>
        </tr>
        <tr>
            <td class="tableb" style="white-space: nowrap;">
                '.KEYWORDS.'
            </td>
            <td class="tableb" valign="top" width="100%">
                <input type="text" style="width: 100%" name="keywords" maxlength="255" value="'.$CURRENT_PIC['keywords'].'" class="textinput" />
            </td>
        </tr>
<!-- END main -->
<!-- BEGIN user_field -->
        <tr>
            <td class="tableb" style="white-space: nowrap;">
                {FIELD_NAME}
            </td>
            <td class="tableb" valign="top" width="100%">
                <input type="text" style="width: 100%" name="{USER_FIELD}" maxlength="255" value="{USER_PIC}" class="textinput" />
            </td>
        </tr>
<!-- END user_field -->
<!-- BEGIN footer -->
        <tr>
                <td class="tableb" colspan="3" align="center">
                        <b><input type="checkbox" name="read_exif" value="1" class="checkbox" />'.READ_EXIF.'</b>&nbsp;
                        <b><input type="checkbox" name="reset_vcount" value="1" class="checkbox" />'.RESET_VIEW_COUNT.'</b>&nbsp;
                        <b><input type="checkbox" name="reset_votes" value="1" class="checkbox" />'.RESET_VOTES.'</b>&nbsp;
                        <b><input type="checkbox" name="del_comments" value="1" class="checkbox" />'.DEL_COMM.'</b>&nbsp;
                </td>
        </tr>
        <tr>
                <td colspan="3" align="center" class="tablef">
                        <input type="submit" value="'.APPLY.'" name="submitDescription" class="button" />
                </td>
        </tr>
        </form>
<!-- END footer -->';
?>