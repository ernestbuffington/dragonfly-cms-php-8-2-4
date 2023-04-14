<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Survey.php,v $
  $Revision: 9.14 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:16 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }
if (!is_active('Surveys')) {
	$content = 'ERROR';
	return trigger_error('Surveys module is inactive', E_USER_WARNING);
}

global $prefix, $multilingual, $currentlang, $db, $content, $pollcomm, $userinfo, $ThemeSel;

$querylang = ($multilingual) ? "AND planguage='$currentlang' OR planguage=''" : '';

$result = $db->sql_query("SELECT poll_id, poll_title, voters, anonymous FROM ".$prefix."_poll_desc WHERE artid=0 $querylang ORDER BY poll_id DESC LIMIT 0,1");
if ($db->sql_numrows($result) < 1) {
	$content = sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_SurveysLANG));
} else {
	list($poll_id, $poll_title, $voters, $anonymous) = $db->sql_fetchrow($result);
	$content = "<b>$poll_title</b><br /><br />\n";
	$content .= '<form action="'.getlink("Surveys").'" method="post" enctype="multipart/form-data" accept-charset="utf-8">';
	$content .= '<table border="0" cellpadding="0" cellspacing="0" width="100%">';
	$sum = 0;
	$button = '';
	$past = gmtime()-86400*30; // 86400 is one day
    # if you are anonymous and no anonymous votes allowed, you can't vote
    if (!$anonymous && !is_user()) {
        $voted = "1";
    }
    # if limited by username, but anonymous is allowed, don't disallow multiple anonymous entries
    elseif ($anonymous && !is_user()) {
        $voted = $db->sql_count($prefix.'_poll_check', "user_id='".$userinfo['user_id']."' AND ip=".$userinfo['user_ip']." AND poll_id='$poll_id'");//only one anonymous per ip
    }
    else {
        $voted = $db->sql_count($prefix.'_poll_check', "user_id='".$userinfo['user_id']."' AND poll_id='$poll_id'");
    }
	$result2 = $db->sql_query("SELECT option_text, vote_id, option_count FROM ".$prefix."_poll_data WHERE poll_id='$poll_id' AND option_text!='' ORDER BY vote_id");
	if ($voted) {
		while ($row = $db->sql_fetchrow($result2, SQL_ASSOC)) {
			$options[] = $row;
			$sum += intval($row['option_count']);
		}
		$leftbar = file_exists("themes/$ThemeSel/images/survey_leftbar.gif") ? 'survey_leftbar.gif' : 'leftbar.gif';
		$mainbar = file_exists("themes/$ThemeSel/images/survey_mainbar.gif") ? 'survey_mainbar.gif' : 'mainbar.gif';
		$rightbar = file_exists("themes/$ThemeSel/images/survey_rightbar.gif") ? 'survey_rightbar.gif' : 'rightbar.gif';
		$l_size = getimagesize("themes/$ThemeSel/images/$leftbar");
		$m_size = getimagesize("themes/$ThemeSel/images/$mainbar");
		$r_size = getimagesize("themes/$ThemeSel/images/$rightbar");
		if (file_exists("themes/$ThemeSel/images/survey_mainbar_d.gif")) $mainbar_d = 'survey_mainbar_d.gif';
		if (isset($mainbar_d)) $m1_size = getimagesize("themes/$ThemeSel/images/$mainbar_d");

		foreach ($options as $option) {
			$percent = ($sum) ? 100 / $sum * $option['option_count'] : 0;
			$percentInt = (int)$percent * .85;
			$percent2 = (int)$percent;
/*			  if (isset($mainbar_d)) {
				if ($percent2 > 0 AND $percent2 <= 23) {
					$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$percentInt\">";
				} elseif ($percent2 > 24 AND $percent2 < 50) {
					$a = $percentInt - 100;
					$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$a\">";
				} elseif ($percent2 > 49 AND $percent2 < 75) {
					$a = $percentInt - 200;
					$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$a\">";
				} elseif ($percent2 > 74 AND $percent2 <= 100) {
					$a = $percentInt - 300;
					$salto = "<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"70\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar_d\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m1_size[1]\" width=\"30\">"
							."<img src=\"themes/$ThemeSel/images/$mainbar\" alt=\"$percent2 %\" title=\"$percent2 %\" height=\"$m_size[1]\" width=\"$a\">";
				}
			}*/
			$content .= "<tr><td>$option[option_text]<br />";
			$content .= "<img src=\"themes/$ThemeSel/images/$leftbar\" height=\"$l_size[1]\" width=\"$l_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
			if ($percent > 0) {
//				  if (isset($mainbar_d)) {
//					  $content .= $salto;
//				  } else {
					$content .= "<img src=\"themes/$ThemeSel/images/$mainbar\" height=\"$m_size[1]\" width=\"$percentInt%\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
//				  }
			} else {
				if (!isset($mainbar_d)) {
					$content .= "<img src=\"themes/$ThemeSel/images/$mainbar\" height=\"$m_size[1]\" width=\"$m_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" />";
				}
			}
			$content .= "<img src=\"themes/$ThemeSel/images/$rightbar\" height=\"$r_size[1]\" width=\"$r_size[0]\" alt=\"$percent2 %\" title=\"$percent2 %\" /><br />";
			$content .= "</td></tr>\n";
		}
	}
	else {
		while ($row = $db->sql_fetchrow($result2, SQL_ASSOC)) {
			$content .= '<tr>
		<td valign="top"><input title="'.$row['option_text'].'" type="radio" name="vote_id" id="vote_id'.$row['vote_id'].'" value="'.$row['vote_id'].'" /></td>
		<td width="100%"><label for="vote_id'.$row['vote_id'].'">'.$row['option_text']."</label></td>
	</tr>\n";
			$sum += intval($row['option_count']);
		}
		$button .= '<br /><input type="hidden" name="pollid" value="'.$poll_id.'" />';
		$button .= '<input type="submit" value="'._VOTE.'" class="button" /><br /><br />';
	}
	$db->sql_freeresult($result2);

	$content .= '</table><div style="text-align:center;">'.$button.'
	<a href="'.getlink("Surveys&amp;op=results&amp;pollid=$poll_id").'"><b>'._RESULTS.'</b></a> <b>::</b> <a href="'.getlink('Surveys').'"><b>'._POLLS.'</b></a><br />
	<br />'._VOTES.": <b>$sum</b>\n";
	if ($pollcomm) {
		list($numcom) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_pollcomments WHERE poll_id='$poll_id'", SQL_NUM);
		$content .= "<br /> "._PCOMMENTS." <b>$numcom</b>\n";
	}
	$content .= "</div></form>\n\n";
}
$db->sql_freeresult($result);
