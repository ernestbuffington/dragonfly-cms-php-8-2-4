<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/meta.php,v $
  $Revision: 9.8 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:11:46 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$METATAGS['keywords'] = $module_name;
switch($module_name) {
	case 'Content':
		$METATAGS['description'] = _ContentLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'coppermine':
		$METATAGS['description'] = _coppermineLANG." $slogan";
		$METATAGS['keywords'] .= ', photogallery, photo, gallery';
	break;
	case 'Downloads':
		$METATAGS['description'] = _DownloadsLANG." $slogan";
		$METATAGS['keywords'] .= ', free, programs, opensource, fast, secure, security, cms, content, management, system, interactive, programming';
	break;
	case 'Encyclopedia':
		$METATAGS['description'] = _EncyclopediaLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Contact':
		$METATAGS['description'] = _ContactLANG." $slogan";
		$METATAGS['keywords'] .= ', fast,'._ContactLANG.', secure, '._MESSAGE.', cms, content, management, system';
	break;
	case 'FAQ':
		$METATAGS['description'] = _FAQLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Forums':
		$METATAGS['description'] = _BBFORUMS." $slogan";
		$METATAGS['keywords'] .= ', forums, bulletin, board, boards, phpbb, community';
	break;
	case 'Blogs':
		$METATAGS['description'] = _BlogsLANG." $slogan";
		$METATAGS['keywords'] .= ', journal, blog, blogging, blogger, diary';
	break;
	case 'Members_List':
		$METATAGS['description'] = _Members_ListLANG." $slogan";
		$METATAGS['keywords'] .= ', forum, forums, bulletin, board, boards, phpbb, community';
	break;
	case 'News':
		$METATAGS['description'] = _NewsLANG." $slogan";
		$METATAGS['keywords'] .= ', news, new, headlines';
		break;
	case 'Private_Messages':
		$METATAGS['description'] = _Private_MessagesLANG;
		$METATAGS['keywords'] .= ', forum, forums, bulletin, board, boards, phpbb, community';
	break;
	case 'Reviews':
		$METATAGS['description'] = _ReviewsLANG." Product Reviews";
		$METATAGS['keywords'] .= ', Product Reviews';
	break;
	case 'Search':
		$METATAGS['description'] = _SearchLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Sections':
		$METATAGS['description'] = _SectionsLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Statistics':
		$METATAGS['description'] = _StatisticsLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Stories_Archive':
		$METATAGS['description'] = _Stories_ArchiveLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Surveys':
		$METATAGS['description'] = _SurveysLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Tell_a_Friend':
		$METATAGS['description'] = _Tell_a_FriendLANG." $slogan";
		$METATAGS['keywords'] .= ', forum, forums, bulletin, board, boards, phpbb, community';
	break;
	case 'Top':
		$METATAGS['description'] = _TopLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Topics':
		$METATAGS['description'] = _TopicsLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Web_Links':
		$METATAGS['description'] = _Web_LinksLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';

	break;
	case 'Your_Account':
		$METATAGS['description'] = _Your_AccountLANG." $slogan";
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	default:
		$METATAGS['description'] = $slogan;
		$METATAGS['keywords'] .= ', cpg, cpg-nuke, dragonfly, nuke, php-nuke, pc, pcnuke, pc-nuke, software, downloads, community, forums, bulletin, boards';
	break;
}