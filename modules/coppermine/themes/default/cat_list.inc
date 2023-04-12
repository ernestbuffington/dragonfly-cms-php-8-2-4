<?php
global $CPG_M_DIR;
$template_cat_list = '
<!-- BEGIN header -->
        <tr>
                <td class="tableh1" width="80%"><b>'.CATEGORY.'</b></td>
                <td class="tableh1" width="10%" align="center"><b>'.ALBUMS.'</b></td>
                <td class="tableh1" width="10%" align="center"><b>'.PICTURES.'</b></td>
        </tr>
<!-- END header -->
<!-- BEGIN catrow_noalb -->
        <tr>
                <td class="tableb"><span class="catlink"><b>{CAT_TITLE}</b></span>{CAT_DESC}</td>
        </tr>
<!-- END catrow_noalb -->
<!-- BEGIN catrow -->
        <tr>
          <td class="tableb" colspan="3">
            <table border="0" width="100%">
              <tr>
                <td><span class="catlink"><b>{CAT_TITLE}</b></span>{CAT_DESC}</td>
                <td class="tableb" align="center" width="10%">{ALB_COUNT}</td>
                <td class="tableb" align="center" width="10%">{PIC_COUNT}</td>
              </tr>
              <tr>
                <td colspan="3">{CAT_ALBUMS}</td>
              </tr>
            </table>
          </td>
        </tr>
<!-- END catrow -->
<!-- BEGIN footer -->
        <tr>
                <td colspan="3" class="tableh1" align="center"><span class="statlink"><b>{STATISTICS}</b></span></td>
        </tr>
<!-- END footer -->
<!-- BEGIN spacer -->
        <img src="images/spacer.gif" alt="" width="1" height="17" /><br />
<!-- END spacer -->';
?>
