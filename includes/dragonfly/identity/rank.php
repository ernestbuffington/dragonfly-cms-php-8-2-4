<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Identity;

class Rank
{

	public static function get($rank_id, $posts)
	{
		static $ranks;
		if (!$ranks) {
			$ranks = array(0=>array(),1=>array());
			$SQL = \Dragonfly::getKernel()->SQL;
			$result = $SQL->query("SELECT * FROM {$SQL->TBL->bbranks} ORDER BY rank_min DESC");
			while ($rank = $result->fetch_assoc()) {
				$ranks[$rank['rank_special']?1:0][$rank['rank_id']] = $rank;
			}
		}

		if ($rank_id && isset($ranks[1][$rank_id])) {
			return array(
				'title' => $ranks[1][$rank_id]['rank_title'],
				'image' => $ranks[1][$rank_id]['rank_image']
			);
		}

		foreach ($ranks[0] as $rank) {
			if ($posts >= $rank['rank_min']) {
				return array(
					'title' => $rank['rank_title'],
					'image' => $rank['rank_image']
				);
			}
		}
	}

}
