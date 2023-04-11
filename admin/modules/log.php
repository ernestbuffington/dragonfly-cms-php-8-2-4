<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { cpg_error('Access Denied'); }

class Dragonfly_Admin_Log
{
	protected
		$limit = 40;

	public function GET()
	{
		\Dragonfly\Page::title('Log');

		$K = \Dragonfly::getKernel();
		$OUT = $K->OUT;
		$SQL = $K->SQL;

		if (isset($_GET['level'])) {
			if (ctype_digit($_GET['level'])) {
				if (!empty($_GET['id'])) {
					return $this->displayEntry($_GET['id']);
				}

				$level = (int)$_GET['level'];

				$offset = max(0, $_GET->uint('offset'));
				$result = $SQL->query("SELECT
					log_id,
					log_time,
					log_request_uri,
					log_type
				FROM {$SQL->TBL->log} WHERE log_level={$level}
				ORDER BY log_type DESC, log_time DESC LIMIT {$this->limit} OFFSET {$offset}");
				$items = array();
				while ($row = $result->fetch_row()) {
					$items[] = array(
						'type'        => $K->L10N->get('_log_types', $row[3]),
						'date'        => $K->L10N->date('Y-m-d', $row[1]),
						'request_uri' => $row[2],
						'uri_details' => URL::admin("&level={$level}&id={$row[0]}")
					);
				}
				$OUT->logitems = $items;
				$result->free();

				$OUT->logitems_pagination = new \Poodle\Pagination(
					preg_replace('#&offset=.*$#D','',$_SERVER['REQUEST_URI']).'&offset=${offset}',
					$SQL->count('log', 'log_level='.$SQL->quote($level)), $offset, $this->limit);

				return $OUT->display('poodle/log/level');
			}

			if ('all' == $_GET['level']) {
				if (!empty(\Dragonfly::$PATH[2]) && 'level' == \Dragonfly::$PATH[2]) {
					return $this->displayAll(true);
				}
				return $this->displayAll(false);
			}
		}

		if (isset($_GET['type'])) {
			if (!empty($_GET['id'])) {
				return $this->displayEntry($_GET['id']);
			}
			$type   = \Poodle\Base64::urlDecode($_GET['type']);
			$levels = $K->L10N->get('_log_levels');
			$offset = max(0, $_GET->uint('offset'));
			$result = $SQL->query("SELECT
				log_id,
				log_time,
				log_request_uri,
				log_level
			FROM {$SQL->TBL->log} WHERE log_type=".$SQL->quote($type)."
			ORDER BY log_level, log_time DESC LIMIT {$this->limit} OFFSET {$offset}");
			$items = array();
			while ($row = $result->fetch_row()) {
				$items[] = array(
					'level'       => $levels[$row[3]],
					'date'        => $K->L10N->date('Y-m-d', $row[1]),
					'request_uri' => $row[2],
					'uri_details' => URL::admin("&type={$_GET['type']}&id={$row[0]}")
				);
			}
			$OUT->logitems = $items;
			$result->free();

			$OUT->logitems_pagination = new \Poodle\Pagination(
				preg_replace('#&offset=.*$#D','',$_SERVER['REQUEST_URI']).'&offset=${offset}',
				$SQL->count('log', 'log_type='.$SQL->quote($type)), $offset, $this->limit);

			return $OUT->display('poodle/log/type');
		}

		$OUT->loglevels = array();
		$result = $SQL->query("SELECT log_level as level, COUNT(*) as count FROM {$SQL->TBL->log}
		GROUP BY log_level ORDER BY log_level");
		while ($row = $result->fetch_assoc()) {
			$row['uri']   = URL::admin("&level={$row['level']}");
			$row['level'] = $K->L10N->get('_log_levels', $row['level']);
			$OUT->loglevels[]  = $row;
		}

		$l10n_logtypes = $K->L10N->get('_log_types');
		$OUT->logtypes = array();
		$result = $SQL->query("SELECT log_type as type, COUNT(*) as count FROM {$SQL->TBL->log}
		GROUP BY log_type ORDER BY log_type");
		while ($row = $result->fetch_assoc()) {
			$row['uri']  = URL::admin('log&type='.\Poodle\Base64::urlEncode($row['type']));
			if (isset($l10n_logtypes[$row['type']])) {
				$row['type'] = $l10n_logtypes[$row['type']];
			}
			$OUT->logtypes[]  = $row;
		}

		$result->free();

		return $OUT->display('poodle/log/index');
	}

	public function POST()
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		if (isset($_GET['id'])) {
			$SQL->exec("DELETE FROM {$SQL->TBL->log} WHERE log_id = ".intval($_GET['id']));
		} else
		if (isset($_GET['type'])) {
			$SQL->exec("DELETE FROM {$SQL->TBL->log} WHERE log_type = ".$SQL->quote(\Poodle\Base64::urlDecode($_GET['type'])));
		} else
		if (isset($_GET['level'])) {
			$SQL->exec("DELETE FROM {$SQL->TBL->log} WHERE log_level = ".intval($_GET['level']));
		}
		\URL::redirect(\URL::admin('log'));
	}

	protected function displayEntry($id)
	{
		if (!ctype_digit($id)) {
			\URL::redirect(\URL::admin('log'), 303);
		}
		$id = (int)$id;
		$K = \Dragonfly::getKernel();
		$OUT = $K->OUT;
		$SQL = $K->SQL;
		$result = $SQL->query("SELECT
			log_id id,
			log_time time,
			log_level level,
			log_type type,
			identity_id,
			log_ip ip,
			log_msg msg,
			log_request_uri request_uri,
			log_request_method request_method,
			log_request_headers request_headers
		FROM {$SQL->TBL->log} WHERE log_id={$id}");
		if ($row = $result->fetch_assoc()) {
			if ($row['identity_id'] > 0 && $member = \Poodle\Identity\Search::byID($row['identity_id'])) {
				$row['identity_nick'] = $member->nickname;
			} else {
				$row['identity_nick'] = 'unknown';
			}
			if ($row['request_headers'])
			{
				$row['request_headers'] = str_replace('; ',";\n\t",$row['request_headers']);
			}
			$row['date'] = $K->L10N->date('DATE_F', $row['time']);
			$row['level'] = $K->L10N->get('_log_levels', $row['level']);

			$OUT->logentry = $row;
		}
		return $OUT->display('poodle/log/entry');
	}

	protected function displayAll($by_level)
	{
		$K = \Dragonfly::getKernel();
		$OUT = $K->OUT;
		$SQL = $K->SQL;

		$levels = $K->L10N->get('_log_levels');
		$offset = max(0, $_GET->uint('offset'));
		$result = $SQL->query("SELECT
			log_id,
			log_time,
			log_request_uri,
			log_type,
			log_level
		FROM {$SQL->TBL->log}
		ORDER BY ".($by_level?'log_level ASC,':'')." log_time DESC LIMIT {$this->limit} OFFSET {$offset}");
		$items = array();
		while ($row = $result->fetch_row()) {
			$items[] = array(
				'level'       => $levels[$row[4]],
				'type'        => $K->L10N->get('_log_types', $row[3]),
				'date'        => $K->L10N->date('Y-m-d', $row[1]),
				'request_uri' => $row[2],
				'uri_details' => "/admin/poodle_log/{$row[4]}/{$row[0]}"
			);
		}
		$OUT->logitems = $items;
		$result->free();

		$OUT->logitems_pagination = new \Poodle\Pagination(
			preg_replace('#&offset=.*$#D','',$_SERVER['REQUEST_URI']).'&offset=${offset}',
			$SQL->count('log'), $offset, $this->limit);

		return $OUT->display('poodle/log/list');
	}

}

\Dragonfly::getKernel()->L10N->load('poodle_log');
$class = new Dragonfly_Admin_Log;
$class->{$_SERVER['REQUEST_METHOD']}();
