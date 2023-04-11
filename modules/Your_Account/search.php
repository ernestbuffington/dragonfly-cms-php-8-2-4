<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

if (!class_exists('Dragonfly', false)) { exit; }

if (!\Dragonfly::getKernel()->IDENTITY->isMember() && !\Dragonfly::getKernel()->IDENTITY->isAdmin()) {
	cpg_error('Forbidden', 403);
}

if (isset($_GET['window'])) {
	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->search_nickname = $_POST->raw('nickname');
	$OUT->found_users = false;
	if ($OUT->search_nickname) {
		$found_users = \Dragonfly\Identity\Search::byName($OUT->search_nickname);
		if ($found_users->num_rows) {
			$OUT->found_users = $found_users;
		}
	}

	echo '<!DOCTYPE html>';
	echo $OUT->toString('Your_Account/search_nickname_window');
}

exit;
