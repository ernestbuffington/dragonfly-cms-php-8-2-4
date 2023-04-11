<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

function search_pics($search_string, $select_columns, $query_all, $limit, &$pic_count)
{
	global $CONFIG, $db;

	$pic_count = 0;
	if ($query_all) {
		$search_string = str_replace('*', '%', $search_string);
		if ('*' !== $select_columns) { $select_columns .= ', caption'; }
		$result = $db->query("SELECT SQL_CALC_FOUND_ROWS
			{$select_columns}
		FROM {$CONFIG['TABLE_PICTURES']} AS p
		INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
		WHERE MATCH (p.title, p.caption, p.keywords, p.filename, p.user1, p.user2, p.user3, p.user4)
		   AGAINST ('{$search_string}' IN BOOLEAN MODE) AND approved=1
		{$limit}");
		list($pic_count) = $db->uFetchRow("SELECT FOUND_ROWS()");
		if (!$pic_count) {
			$result = $db->query("SELECT SQL_CALC_FOUND_ROWS
				{$select_columns}
			FROM {$CONFIG['TABLE_PICTURES']} AS p
			INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
			WHERE MATCH (p.title, p.caption, p.keywords, p.filename, p.user1, p.user2, p.user3, p.user4)
			   AGAINST ('{$search_string}' IN BOOLEAN MODE) AND approved=1
			{$limit}");
			list($pic_count) = $db->uFetchRow("SELECT FOUND_ROWS()");
		}
		// finally if nothing else works we try the old way
		if (!$pic_count) {
			$search_string = '%' . $search_string . '%';
			$search_string = str_replace('%%', '%', $search_string);
			list($pic_count) = $db->uFetchRow("SELECT
				COUNT(*)
			FROM {$CONFIG['TABLE_PICTURES']} AS p
			INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
			WHERE approved=1 AND (
				  a.title LIKE '{$search_string}'
			   OR p.title LIKE '{$search_string}'
			   OR p.caption LIKE '{$search_string}'
			   OR p.keywords LIKE '{$search_string}'
			   OR p.filename LIKE '{$search_string}'
			   OR p.user1 LIKE '{$search_string}'
			   OR p.user2 LIKE '{$search_string}'
			   OR p.user4 LIKE '{$search_string}')");
			return $db->query("SELECT {$select_columns}
			FROM {$CONFIG['TABLE_PICTURES']} AS p
			INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
			WHERE approved=1 AND (
				  a.title LIKE '{$search_string}'
			   OR p.title LIKE '{$search_string}'
			   OR p.caption LIKE '{$search_string}'
			   OR p.keywords LIKE '{$search_string}'
			   OR p.filename LIKE '{$search_string}'
			   OR p.user1 LIKE '{$search_string}'
			   OR p.user2 LIKE '{$search_string}'
			   OR p.user4 LIKE '{$search_string}')
			{$limit}");
		}
		return $result;
	} else if ('' != $search_string) {
		// Replace line endings by a space
		$split_search = ' ' . preg_replace('/\R+/s', ' ', strtolower($search_string));
		// + and - becomes and & not
		$split_search = str_replace(' +', ' and ', $split_search);
		$split_search = str_replace(' -', ' not ', $split_search);
		// 'words' that consist of <3 or >20 characters are removed.
//		$split_search = preg_replace('/\b([a-z0-9]{1,2}|[a-z0-9]{21,})\b/', ' ', $split_search);
		$split_search = explode(' ', $split_search);

		$current_match_type = 'and';
		$pic_set = '';
		for ($i = 0; $i < count($split_search); $i++) {
			switch ($split_search[$i]) {
				case 'and':
					$current_match_type = 'and';
					break;

				case 'or':
					$current_match_type = 'or';
					break;

				case 'not':
					$current_match_type = 'not';
					break;

				default:
					if (empty($split_search[$i])) break;

					$word = Fix_Quotes($split_search[$i]);
					$keyword = Fix_Quotes($split_search[$i]);
					$match_word = '%' . str_replace('*', '%', $word) . '%';
					$match_keyword = '%' . str_replace('*', '%', $keyword) . '%';

					//$match_word = '%' . str_replace('*', '%', $split_search[$i])) . '%';
					//$match_keyword = '% ' . str_replace('*', '%', Fix_Quotes($split_search[$i])) . ' %';

					$sql = "SELECT pid FROM {$CONFIG['TABLE_PICTURES']} p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE CONCAT('', keywords, '') LIKE '$match_keyword' ";

					if ($query_all) $sql .= "OR filename LIKE '$match_word' " . "OR title LIKE '$match_word' " . "OR caption LIKE '$match_word' " . "OR user1 LIKE '$match_word' " . "OR user2 LIKE '$match_word' " . "OR user3 LIKE '$match_word' " . "OR user4 LIKE '$match_word' ";
					$result = $db->query($sql);
					if ($result->num_rows) {
						$set = '';
						while ($row = $result->fetch_row()) {
							$set .= $row[0] . ',';
						} // while
						$set = substr($set, 0, -1);
						if (empty($pic_set)) {
							if ('not' === $current_match_type) {
								$pic_set .= ' pid not in (' . $set . ') ';
							} else {
								$pic_set .= ' pid in (' . $set . ') ';
							}
						} else {
							if ('not' === $current_match_type) {
								$pic_set .= ' and pid not in (' . $set . ') ';
							} else {
								$pic_set .= ' ' . $current_match_type . ' pid in (' . $set . ') ';
							}
						}
					}

					$result->free();

					$current_match_type = 'and';
			}
		}

		if (!empty($pic_set)) {
			list($pic_count) = $db->uFetchRow("SELECT
				COUNT(*)
			FROM {$CONFIG['TABLE_PICTURES']} AS p
			INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
			WHERE ({$pic_set}) AND approved=1");
			if ('*' !== $select_columns) { $select_columns .= ', p.title, caption'; }
			return $db->query("SELECT
				{$select_columns}
			FROM {$CONFIG['TABLE_PICTURES']} AS p
			INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
			WHERE ({$pic_set}) AND approved=1
			{$limit}", SQL_BOTH);
		}
	}

	return null;
}
