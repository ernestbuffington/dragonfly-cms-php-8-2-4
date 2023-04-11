<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (class_exists('Dragonfly', false)) {
	showTop();
}

function showTopResult(\Poodle\SQL\Interfaces\Result $result, $url, $label, $plural)
{
	if ($result->num_rows) {
		$K = \Dragonfly::getKernel();
		$K->OUT->top10 = array(
			'result' => $result,
			'url'    => $url,
			'label'  => $K->CFG->global->top.' '.$label,
			'plural' => $plural
		);
		$K->OUT->display('Top/list');
	}
}

function showTop()
{
	\Dragonfly\Page::title(_TopLANG, false);
	require_once('header.php');
	OpenTable();

	$db = \Dragonfly::getKernel()->SQL;
	$L10N = \Dragonfly::getKernel()->L10N;
	$CFG = \Dragonfly::getKernel()->CFG;

	$limit = $CFG->global->top;

	if (\Dragonfly\Modules::isVisible('News')) {
		if ($L10N->multilingual) {
			$querylang = "AND (alanguage='{$L10N->lng}' OR alanguage='')";
		} else {
			$querylang = '';
		}

		/* Top N read stories */
		$result = $db->query("SELECT
			sid id,
			title,
			counter value
		FROM {$db->TBL->stories} WHERE ptime<=".time()." AND counter>0 {$querylang} ORDER BY counter DESC LIMIT {$limit}");
		showTopResult($result, "News&file=article&sid=", _READSTORIES, '%d views');

		/* Top N most voted stories */
		$result = $db->query("SELECT
			sid id,
			title,
			ratings value
		FROM {$db->TBL->stories} WHERE ptime<=".time()." AND ratings>0 {$querylang} ORDER BY ratings DESC LIMIT {$limit}");
		showTopResult($result, "News&file=article&sid=", _MOSTVOTEDSTORIES, '%d votes');

		/* Top N best rated stories */
		$result = $db->query("SELECT
			sid id,
			title,
			(score/ratings) value
		FROM {$db->TBL->stories} WHERE ptime<=".time()." AND score>0 {$querylang} ORDER BY 3 DESC LIMIT {$limit}");
		showTopResult($result, "News&file=article&sid=", _BESTRATEDSTORIES, '%d points');

		/* Top N commented stories */
		if ($CFG->global->articlecomm) {
			$result = $db->query("SELECT
				sid id,
				title,
				comments value
			FROM {$db->TBL->stories} WHERE ptime<=".time()." AND comments>0 {$querylang} ORDER BY comments DESC LIMIT {$limit}");
			showTopResult($result, "News&file=article&sid=", _COMMENTEDSTORIES, '%d comments');
		}

		/* Top N categories */
		$result = $db->query("SELECT
			catid id,
			title,
			counter value
		FROM {$db->TBL->stories_cat} WHERE counter>0 ORDER BY counter DESC LIMIT {$limit}");
		showTopResult($result, "News&catid=", _ACTIVECAT, '%d hits');
	}

	/* Top N articles in special sections */
	if (\Dragonfly\Modules::isVisible('Sections')) {
		if ($L10N->multilingual) {
			$querylang = "WHERE slanguage='{$L10N->lng}' ";
		} else {
			$querylang = '';
		}
		$result = $db->query("SELECT
			artid id,
			title,
			counter value
		FROM {$db->TBL->seccont} {$querylang} ORDER BY counter DESC LIMIT {$limit}");
		showTopResult($result, "Sections&op=viewarticle&artid=", _READSECTION, '%d views');
	}

	if (is_user() || !\Dragonfly::getKernel()->CFG->member->private_profile) {
		/* Top N users submitters */
		$result = $db->query("SELECT
			user_id id,
			username title,
			counter value
		FROM {$db->TBL->users} WHERE counter > 0 ORDER BY counter DESC LIMIT {$limit}");
		showTopResult($result, "Your_Account&profile=", _NEWSSUBMITTERS, '%d items');
	}

	/* Top N Polls */
	if (\Dragonfly\Modules::isVisible('Surveys')) {
		$result = $db->query("SELECT
			a.poll_id id,
			a.poll_title title,
			SUM(b.option_count) value
		FROM {$db->TBL->poll_desc} a
		LEFT JOIN {$db->TBL->poll_data} b on a.poll_id = b.poll_id
		GROUP BY a.poll_id, a.poll_title ORDER BY voters DESC LIMIT {$limit}");
		showTopResult($result, "Surveys&pollid=", _VOTEDPOLLS, '%d votes');
	}

	/* Top N reviews */
	if (\Dragonfly\Modules::isVisible('Reviews')) {
		if ($L10N->multilingual) {
			$querylang = "AND language='{$L10N->lng}' OR language=''";
		} else {
			$querylang = '';
		}
		$result = $db->query("SELECT
			id,
			title,
			hits value
		FROM {$db->TBL->reviews} WHERE hits>0 {$querylang} ORDER BY hits DESC LIMIT {$limit}");
		showTopResult($result, "Reviews&op=showcontent&id=", _READREVIEWS, '%d views');
	}

	/* Top N Pages in Content */
	if (\Dragonfly\Modules::isVisible('Content')) {
		$result = $db->query("SELECT
			pid id,
			title,
			counter value
		FROM {$db->TBL->pages} WHERE active=1 AND counter>0 ORDER BY counter DESC LIMIT {$limit}");
		showTopResult($result, "Content&pa=showpage&pid=", _MOSTREADPAGES, '%d views');
	}

	CloseTable();
}
