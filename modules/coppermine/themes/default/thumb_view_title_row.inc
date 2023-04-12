<?php
global $module_name;
$template_thumb_view_title_row = "
<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
    <tr>
        <form action=\"index.php\" method=\"get\">
        <input type=\"hidden\" name=\"name\" value=\"$module_name\" />
        <input type=\"hidden\" name=\"file\" value=\"thumbnails\" />
        <input type=\"hidden\" name=\"album\" value=\"$_GET[album]\" />
        <input type=\"hidden\" name=\"page\" value=\"".(isset($_GET['page']) ? $_GET['page'] :'1')."\" />
        <td>
            <h2 class=\"statlink\">$album_name</h2>
        </td>
        <td align=\"right\">
             <select name=\"sort\" class=\"sortorder_options\" onchange=\"submit();\">
                 <option value=\"ta\"".((isset($_GET['sort']) && $_GET['sort']=='ta')?' selected="selected">':'>').SORT_TA."</option>
                 <option value=\"td\"".((isset($_GET['sort']) && $_GET['sort']=='td')?' selected="selected">':'>').SORT_TD."</option>
                 <option value=\"na\"".((isset($_GET['sort']) && $_GET['sort']=='na')?' selected="selected">':'>').SORT_NA."</option>
                 <option value=\"nd\"".((isset($_GET['sort']) && $_GET['sort']=='nd')?' selected="selected">':'>').SORT_ND."</option>
                 <option value=\"da\"".((isset($_GET['sort']) && $_GET['sort']=='da')?' selected="selected">':'>').SORT_DA."</option>
                 <option value=\"dd\"".((isset($_GET['sort']) && $_GET['sort']=='dd')?' selected="selected">':'>').SORT_DD."</option>
                 <option value=\"ra\"".((isset($_GET['sort']) && $_GET['sort']=='ra')?' selected="selected">':'>').SORT_RA."</option>
                 <option value=\"rd\"".((isset($_GET['sort']) && $_GET['sort']=='rd')?' selected="selected">':'>').SORT_RD."</option>
             </select><noscript>
             <input type=\"submit\" value=\"Go\" /></noscript>
        </td>
        </form>
 </tr>
</table>";
?>
