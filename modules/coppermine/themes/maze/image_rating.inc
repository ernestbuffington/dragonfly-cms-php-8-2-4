<?php
$template_image_rating = '
        <tr>
                <td colspan="6" class="tableh2_compact"><b>'.RATE_THIS_PIC.'</b> {VOTES}</td>
        </tr>
        <tr>
                <td class="tableb_compact" width="17%" align="center" valign="middle">
                <br />
                <form method="post" action="'.getlink("&amp;file=ratepic").'">
                <input type="hidden" name="pic" value="{PID}" />
                <input type="hidden" name="rate" value="0" />
                <input type="hidden" name="currentpage" value="{CURRENTPAGE}" />
                <input name="submit" type="image" src="'.$THEME_DIR.'/images/rating0.gif" title="'.RUBBISH.'" alt="'.RUBBISH.'" />
                </form>
            </td>
                <td class="tableb_compact" width="17%" align="center" valign="middle">
                <br />
                <form method="post" action="'.getlink("&amp;file=ratepic").'">
                <input type="hidden" name="pic" value="{PID}" />
                <input type="hidden" name="rate" value="1" />
                <input type="hidden" name="currentpage" value="{CURRENTPAGE}" />
                <input name="submit" type="image" src="'.$THEME_DIR.'/images/rating1.gif" title="'.POOR.'" alt="'.POOR.'" />
                </form>
            </td>
                <td class="tableb_compact" width="17%" align="center" valign="middle">
                <br />
                <form method="post" action="'.getlink("&amp;file=ratepic").'">
                <input type="hidden" name="pic" value="{PID}" />
                <input type="hidden" name="rate" value="2" />
                <input type="hidden" name="currentpage" value="{CURRENTPAGE}" />
                <input name="submit" type="image" src="'.$THEME_DIR.'/images/rating2.gif" title="'.FAIR.'" alt="'.FAIR.'" />
                </form>
            </td>
                <td class="tableb_compact" width="17%" align="center" valign="middle">
                <br />
                <form method="post" action="'.getlink("&amp;file=ratepic").'">
                <input type="hidden" name="pic" value="{PID}" />
                <input type="hidden" name="rate" value="3" />
                <input type="hidden" name="currentpage" value="{CURRENTPAGE}" />
                <input name="submit" type="image" src="'.$THEME_DIR.'/images/rating3.gif" title="'.GOOD.'" alt="'.GOOD.'" />
                </form>
                </td>
                <td class="tableb_compact" width="17%" align="center" valign="middle">
                <br />
                <form method="post" action="'.getlink("&amp;file=ratepic").'">
                <input type="hidden" name="pic" value="{PID}" />
                <input type="hidden" name="rate" value="4" />
                <input type="hidden" name="currentpage" value="{CURRENTPAGE}" />
                <input name="submit" type="image" src="'.$THEME_DIR.'/images/rating4.gif" title="'.EXCELLENT.'" alt="'.EXCELLENT.'" />
                </form>
            </td>
                <td class="tableb_compact" width="17%" align="center" valign="middle">
                <br />
                <form method="post" action="'.getlink("&amp;file=ratepic").'">
                <input type="hidden" name="pic" value="{PID}" />
                <input type="hidden" name="rate" value="5" />
                <input type="hidden" name="currentpage" value="{CURRENTPAGE}" />
                <input name="submit" type="image" src="'.$THEME_DIR.'/images/rating5.gif" title="'.GREAT.'" alt="'.GREAT.'" />
                </form>
           </td>
        </tr>';
?>
