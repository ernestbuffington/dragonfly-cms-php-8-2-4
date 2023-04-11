<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (class_exists('Dragonfly', false)) {
	phpBB_page_header();
}

function phpBB_page_header()
{
	global $board_config;

	$OUT = \Dragonfly::getKernel()->OUT;
	$L10N = $OUT->L10N;
	$userinfo = \Dragonfly::getKernel()->IDENTITY;

	# Obtain number of messages if user is logged in
	$s_last_visit = $l_forum_watch = $l_topic_watch = '';
	if ($userinfo->isMember()) {
		$s_last_visit  = $L10N->date($board_config['default_dateformat'], $userinfo['user_lastvisit']);
		$l_forum_watch = $board_config['allow_forum_watch'] ? $L10N['Forum_watch'] : '';
		$l_topic_watch = $L10N['Topic_watch'];
	}

	require_once 'header.php';

	# The following assigns all _common_ variables that may be used at any point
	# in a template.
	$OUT->assign_vars(array(
		'L_INDEX'             => sprintf($L10N['Forum_Index'], ''),

		'U_SEARCH_UNANSWERED' => URL::index('&file=search&search_id=unanswered'),
		'U_SEARCH_SELF'       => URL::index('&file=search&search_id=egosearch'),
		'U_SEARCH_NEW'        => URL::index('&file=search&search_id=newposts'),
		'U_SEARCH_24'         => URL::index('&file=search&search_id=last'),
		'U_INDEX'             => URL::index(),
		'U_SEARCH'            => URL::index('&file=search'),
		'U_MODCP'             => URL::index('&file=modcp'),
		'U_FAQ'               => URL::index('&file=faq'),
		'U_WATCH_FORUM'       => $l_forum_watch ? URL::index('&file=search&search_id=fwatch') : false,
		'U_WATCH_TOPIC'       => $l_topic_watch ? URL::index('&file=search&search_id=watch') : false,
		'U_MODERATORS'        => URL::index('&file=moderators'),

		'S_TIMEZONE'          => sprintf($L10N['All_times'], date('e')),
	));
	if (version_compare(THEME_VERSION, '10', '<')) {
		$OUT->assign_vars(array(
			'CURRENT_TIME'        => sprintf($L10N['Current_time'], $L10N->date($board_config['default_dateformat'])),
			'LAST_VISIT_DATE'     => sprintf($L10N['You_last_visit'], $s_last_visit),
			'PAGE_TITLE'          => \Dragonfly\Page::get('title'),
			'L_FAQ'               => $L10N['FAQ'],
			'L_MODERATORS'        => $L10N['Moderators'],
			'L_SEARCH'            => $L10N['Search'],
			'L_SEARCH_NEW'        => $L10N['Search_new'],
			'L_SEARCH_24'         => $L10N['Search_last_24'],
			'L_SEARCH_UNANSWERED' => $L10N['Search_unanswered'],
			'L_SEARCH_SELF'       => $L10N['Search_your_posts'],
			'L_USERNAME'          => $L10N['Username'],
			'L_USER_CLOAK'        => false,
			'L_WATCH_FORUM'       => $l_forum_watch,
			'L_WATCH_TOPIC'       => $l_topic_watch,
			'S_CONTENT_DIRECTION' => _TEXT_DIR,
			'S_CONTENT_BASE'	  => $GLOBALS['BASEHREF'],
			'S_CONTENT_ENCODING'  => \Dragonfly::CHARSET,
			'S_LOGGED_IN'         => $userinfo->isMember(),
			'S_LOGIN_ACTION'      => URL::index('&file=login'),
			'S_SIMPLE_HEADER'     => false,
			'S_SIMPLE_FOOTER'     => false,
			'S_PRINT_HEADER'      => false,
			'U_PRIVATEMSGS'       => URL::index('Private_Messages'),
			'U_PROFILE'           => URL::index('Your_Account'),
		));
	}
}
