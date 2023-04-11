<?php

function coppermine_get_favorites()
{
	static $favorites;
	if (!is_array($favorites)) {
		global $MAIN_CFG, $CONFIG;
		if (is_user()) {
			$favorites = coppermine_get_user_favorite_pictures(is_user());
		}
		$cookie_name = $CONFIG['cookie_name'] . '_fav';
		if (!$favorites && isset($_COOKIE[$cookie_name])) {
			$favorites = $_COOKIE[$cookie_name];
			if (preg_match('#^[0-9]+(?:,[0-9]+)*$#D', $favorites)) {
				$favorites = array_map('intval', explode(',', $favorites));
			} else {
				$favorites = base64_decode($favorites);
				if (preg_match('#^a:1:{(?:i:[0-9]+;)+}$#D', $favorites)) {
					$favorites = unserialize($favorites);
					if (is_user()) {
						$SQL = \Dragonfly::getKernel()->SQL;
						foreach ($favorites as $pid) {
							$SQL->exec("INSERT INTO {$CONFIG['TABLE_FAVORITES']} (pid, user_id, ctime) VALUES ({$pid}, ".is_user().", ".time().")");
						}
						setcookie($cookie_name, '', 0, $MAIN_CFG->cookie->path, $MAIN_CFG->cookie->domain);
					}
				}
			}
		}
		if (!is_array($favorites)) {
			$favorites = array();
		}
	}
	return $favorites;
}

function coppermine_get_user_favorite_pictures($user_id)
{
	global $CONFIG;
	$SQL = \Dragonfly::getKernel()->SQL;
	$user_id = (int)$user_id;
	$favorites = array();
	$qr = $SQL->query("SELECT pid FROM {$CONFIG['TABLE_FAVORITES']} WHERE user_id = {$user_id} ORDER BY ctime");
	while ($r = $qr->fetch_row()) {
		$favorites[] = $r[0];
	}
	return $favorites;
}

function coppermine_switch_favorite($pid)
{
	global $MAIN_CFG, $CONFIG;
	$add = true;
	if (is_user()) {
		$SQL = \Dragonfly::getKernel()->SQL;
		try {
			$SQL->exec("INSERT INTO {$CONFIG['TABLE_FAVORITES']} (pid, user_id, ctime) VALUES ({$pid}, ".is_user().", ".time().")");
		} catch (\Exception $e) {
			$add = false;
			$SQL->exec("DELETE FROM {$CONFIG['TABLE_FAVORITES']} WHERE pid = {$pid} AND user_id = ".is_user());
		}
	} else {
		// If user does not accept script's cookies, we don't accept the favorites
		if (!isset($_COOKIE[$CONFIG['cookie_name'] . '_data'])) {
			URL::redirect($redirect_url);
		}

		$FAVPICS = coppermine_get_favorites();
		$add = !in_array($pid, $FAVPICS);

		if ($add) {
			$FAVPICS[] = $pid;
		} else {
			$key = array_search($pid, $FAVPICS);
			unset($FAVPICS[$key]);
		}

		$data = implode(',', $FAVPICS);
		setcookie($CONFIG['cookie_name'] . '_fav', $data, time() + 86400 * 30, $MAIN_CFG->cookie->path, $MAIN_CFG->cookie->domain);
	}

	return $add;
}
