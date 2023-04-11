<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('CPG_NUKE')) { exit; }

global $METATAGS;
$METATAGS = array();

return;

$METATAGS['keywords'] = $module_name;
switch($module_name) {
	case 'Content':
		$METATAGS['description'] = _ContentLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'coppermine':
		$METATAGS['description'] = _coppermineLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', photogallery, photo, gallery';
	break;
	case 'Downloads':
		$METATAGS['description'] = _DownloadsLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', free, programs, opensource, fast, secure, security, cms, content, management, system, interactive, programming';
	break;
	case 'Encyclopedia':
		$METATAGS['description'] = _EncyclopediaLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Contact':
		$METATAGS['description'] = _ContactLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast,'._ContactLANG.', secure, '._MESSAGE.', cms, content, management, system';
	break;
	case 'FAQ':
		$METATAGS['description'] = _FAQLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Forums':
		$METATAGS['description'] = _BBFORUMS. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', forums, bulletin, board, boards, phpbb, community';
	break;
	case 'Blogs':
		$METATAGS['description'] = _BlogsLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', journal, blog, blogging, blogger, diary';
	break;
	case 'Members_List':
		$METATAGS['description'] = _Members_ListLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', forum, forums, bulletin, board, boards, phpbb, community';
	break;
	case 'News':
		$METATAGS['description'] = _NewsLANG. ' '. $MAIN_CFG['global']['slogan'];
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
		$METATAGS['description'] = _SearchLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Sections':
		$METATAGS['description'] = _SectionsLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Statistics':
		$METATAGS['description'] = _StatisticsLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Surveys':
		$METATAGS['description'] = _Surveys. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Tell_a_Friend':
		$METATAGS['description'] = _Tell_a_FriendLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', forum, forums, bulletin, board, boards, phpbb, community';
	break;
	case 'Top':
		$METATAGS['description'] = _TopLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Topics':
		$METATAGS['description'] = _TopicsLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	case 'Web_Links':
		$METATAGS['description'] = _Web_LinksLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';

	break;
	case 'Your_Account':
		$METATAGS['description'] = _Your_AccountLANG. ' '. $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', fast, secure, security, cms, content, management, system';
	break;
	default:
		$METATAGS['description'] = $MAIN_CFG['global']['slogan'];
		$METATAGS['keywords'] .= ', cpg, cpg-nuke, dragonfly, nuke, php-nuke, pc, pcnuke, pc-nuke, software, downloads, community, forums, bulletin, boards';
	break;
}
