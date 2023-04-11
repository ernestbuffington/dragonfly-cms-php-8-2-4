<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\L10N;

class Admin
{
	# http://tools.ietf.org/html/rfc4918#section-9
	public
		$allowed_methods = array('GET','POST');

	public function GET()
	{
		\Dragonfly::getKernel()->L10N->load('dragonfly_l10n');
		if (isset($_GET['translate'])) {
			$this->viewTranslator();
		} else {
			$this->viewList();
		}
	}

	public function POST()
	{
		$K = \Dragonfly::getKernel();
		$SQL = $K->SQL;

		if (isset($_GET['translate'])) {
			$lng = $_GET->txt('translate');
			if (!preg_match(\Dragonfly\L10N::REGEX,$lng)) {
				throw new \Exception('Invalid language');
			}
			$col = 'v_'.str_replace('-','_',$lng);
			$tbl = $SQL->TBL->l10n_translate;
			if (isset($_POST['add'])) {
				if (!empty($_POST['translate_add']['msg_id'])) {
					$tbl->insert(array(
						'msg_id' => $_POST['translate_add']['msg_id'],
						$col => $_POST['translate_add']['value'],
					));
				}
			} else if (isset($_POST['delete'])) {
				if (!empty($_POST['del']) && is_array($_POST['del'])) {
					$keys = array();
					foreach ($_POST['del'] as $v) { $keys[] = $SQL->quote($v); }
					$tbl->delete('msg_id IN ('.implode(',',$keys).')');
				}
			} else {
				foreach ($_POST['translate'] as $msg_id => $value) {
					if ($msg_id) {
						$tbl->update(array($col => $value), array('msg_id' => $msg_id));
					}
				}
			}
		}
		else
		{
			if (isset($_POST['l10n']['active'])) {
				$ids = implode(',',$_POST['l10n']['active']);
				if (preg_match('/^[0-9,]+$/D',$ids)) {
					$SQL->exec("UPDATE {$SQL->TBL->l10n} SET l10n_active=0 WHERE l10n_id NOT IN ({$ids})");
					$SQL->exec("UPDATE {$SQL->TBL->l10n} SET l10n_active=1 WHERE l10n_id IN ({$ids})");
				}
			}
			if (isset($_POST['l10n']['default'])) {
				$K->CFG->set('global', 'language', $_POST['l10n']['default']);
				$K->CFG->set('global', 'multilingual', count($_POST['l10n']['active']));
				$SQL->exec("UPDATE {$SQL->TBL->l10n} SET l10n_active=1 WHERE l10n_rfc1766=".$SQL->quote($K->CFG->global->language));
			}
			$K->CACHE->delete('Dragonfly/L10N/active');
		}
		\URL::redirect($_SERVER['REQUEST_URI']);
	}

	protected function viewList()
	{
		$K = \Dragonfly::getKernel();
		$OUT = $K->OUT;
		$OUT->languages = self::getAvailableLanguages();
//		$OUT->head->addCSS('dragonfly_l10n_admin');
		$OUT->display('dragonfly/l10n/admin/overview');
	}

	protected function viewTranslator()
	{
		$K = \Dragonfly::getKernel();
		$OUT = $K->OUT;
		$SQL = $K->SQL;

		$lng = $_GET->txt('translate') ?: $OUT->L10N->lng;
		if (!preg_match(\Dragonfly\L10N::REGEX,$lng)) {
			throw new \Exception('Invalid language');
		}
		$col = 'v_'.str_replace('-','_',$lng);

		$OUT->trans_lng = $lng;
		$OUT->translations = $SQL->query("SELECT
			msg_id,
			v_en   en,
			{$col} value
		FROM {$SQL->TBL->l10n_translate}
		ORDER BY 1");

		if ('en' === $lng) {
			$OUT->display('dragonfly/l10n/admin/manage-ids');
		} else {
			$OUT->display('dragonfly/l10n/admin/translate');
		}
	}

	protected static function getAvailableLanguages()
	{
		$K = \Dragonfly::getKernel();
		$SQL = $K->SQL;

		$cols = array();
		$languages = array();

		$col = 'v_'.str_replace('-','_',$K->L10N->lng);
		$qr = $SQL->query("SELECT
			l10n_id       id,
			l10n_rfc1766  rfc1766,
			l10n_active   active,
			l10n_iso639_1 iso639_1,
			l10n_iso639_2 iso639_2,
			lt.*
		FROM {$SQL->TBL->l10n}
		LEFT JOIN {$SQL->TBL->l10n_translate} lt ON (msg_id = l10n_rfc1766)");
		while ($r = $qr->fetch_assoc()) {
			if (\Poodle\L10N::getIniFile($r['rfc1766'])) {
				$rfc1766 = $r['rfc1766'];
				$cols[] = str_replace('-','_',$rfc1766);
				$languages[$rfc1766] = array(
					'id'      => $r['id'],
					'rfc1766' => $rfc1766,
					'active'  => $r['active'],
					'label'   => empty($r[$col]) ? ($r['v_en']?:$rfc1766) : $r[$col],
					'default' => ($rfc1766 === $K->CFG->global->language),
				);
			}
		}

		foreach (glob('includes/l10n/[a-z][a-z]*', GLOB_ONLYDIR) as $rfc1766) {
			$rfc1766 = basename($rfc1766);
			if (!isset($languages[$rfc1766]) && $r = self::getIniFileData($rfc1766)) {
				$cols[] = str_replace('-','_',$rfc1766);
				$l10n_id = $K->SQL->TBL->l10n->insert(array(
					'l10n_rfc1766'  => $r['rfc1766'],
					'l10n_active'   => 0,
					'l10n_iso639_1' => $r['iso639-1'],
					'l10n_iso639_2' => $r['iso639-2'],
				),'l10n_id');
				$languages[$rfc1766] = array(
					'id'      => $l10n_id,
					'rfc1766' => $rfc1766,
					'active'  => 0,
					'label'   => empty($r[$K->L10N->lng]) ? ($r['en']?:$rfc1766) : $r[$K->L10N->lng],
					'default' => 0,
				);
			}
		}

		// Synchronize DB table l10n
		if ($cols) {
			$q = $K->SQL->XML->getDocHead();
			$q .= '<table name="l10n_translate"><col name="v_';
			$q .= implode('" type="TEXT" nullable="true"/><col name="v_',$cols);
			$q .= '" type="TEXT" nullable="true"/></table>';
			$q .= $K->SQL->XML->getDocFoot();
			$K->SQL->XML->syncSchemaFromString($q);
		}

		// Update DB table l10n_translate with data from ini files
		foreach ($languages as $k => $v) {
			$rfc1766 = $v['rfc1766'];
			if ($r = self::getIniFileData($rfc1766)) {
				$data = array('msg_id' => $rfc1766);
				foreach ($r as $k => $v) {
					if (in_array($k, $cols)) {
						$data['v_'.$k] = $v;
					}
				}
				try {
					$K->SQL->TBL->l10n_translate->insert($data);
				} catch (\Exception $e) {
					unset($data['msg_id']);
					foreach ($data as $k => $v) {
						$data[$k] = "COALESCE({$k}, {$K->SQL->quote($v)})";
					}
					$K->SQL->TBL->l10n_translate->updatePrepared($data, "msg_id={$K->SQL->quote($rfc1766)}");
				}
			}
		}

		usort($languages, function($a, $b){
			return strnatcasecmp($a['label'], $b['label']);
		});
		return $languages;
	}

	protected static function getIniFileData($rfc1766)
	{
		if ($ini = \Dragonfly\L10N::getIniFile($rfc1766)) {
			if ($r = parse_ini_file($ini, false, INI_SCANNER_RAW)) {
				if ($r['rfc1766'] == $rfc1766) {
					// There are reserved words which must not be used as keys for ini files.
					// These include: null, yes, no, true, false, on, off, none
					if (in_array($rfc1766, array('null', 'yes', 'no', 'true', 'false', 'on', 'off', 'none'))) {
						$r[$rfc1766] = $r["'{$rfc1766}'"];
						unset($r["'{$rfc1766}'"]);
					}
					return $r;
				}
			}
		}
		return false;
	}

}
