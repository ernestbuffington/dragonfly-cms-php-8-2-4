<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */
 
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('smilies')) { die('Access Denied'); }
\Dragonfly\Page::title('Smiles Utility');

$smilies_path = 'images/smiles/';
$delimeter = '=+:';

function smile_edit($smile_data)
{
	\Dragonfly\Output\Js::add('themes/admin/javascript/admin_smilies.js');
	global $smilies_path;

	$smiley_images = array();
	// Read a listing of uploaded smilies for use in the add or edit smiley code...
	$dir = opendir($smilies_path);
	while ($file = readdir($dir)) {
		if (!is_dir($smilies_path . $file)) {
			$img_size = getimagesize($smilies_path . $file);
			if ($img_size[0] && $img_size[1]) {
				$smiley_images[] = $file;
			}
		}
	}
	closedir($dir);
	if (!$smile_data['smile_url']) { $smile_data['smile_url'] = $smiley_images[0]; }

	$TPL = Dragonfly::getKernel()->OUT;
	$TPL->smiley = $smile_data;
	$TPL->smilies_path = $smilies_path;
	$TPL->smiley_images = $smiley_images;
	$TPL->display('admin/smilies/edit');
}

// Select main mode
if (isset($_GET['add']))
{
	if ('POST' === $_SERVER['REQUEST_METHOD'])
	{
		// Admin has submitted changes while adding a new smiley.

		// Get the submitted data, being careful to ensure that we only
		// accept the data we are looking for.
		$data = array(
			// Convert < and > to proper htmlentities for parsing.
			'code'      => str_replace('>', '&gt;', str_replace('<', '&lt;', trim($_POST['smile_code']))),
			'smile_url' => trim($_POST['smile_url']),
			'emoticon'  => trim($_POST['smile_emotion'])
		);

		// If no code was entered complain ...
		if (!$data['code'])      { cpg_error(sprintf(_ERROR_NOT_SET, 'Smiley code')); }
		if (!$data['smile_url']) { cpg_error(sprintf(_ERROR_NOT_SET, 'Smiley url')); }

		// Save the data to the smiley table.
		$db->TBL->bbsmilies->insert($data);
		Dragonfly::getKernel()->CACHE->delete('bbsmilies');
		cpg_error('The new smiley was successfully added', 'Smiles Utility', URL::admin('smilies'));
	}

	smile_edit(array(
		'code' => '',
		'emoticon' => '',
		'smile_url' => ''
	));
}

else if (isset($_POST['updatesmiles']))
{
	if (is_array($_POST['smilies']) && \Dragonfly\Output\Captcha::validate($_POST)) {
		foreach ($_POST['smilies'] as $i => $id) {
			$db->TBL->bbsmilies->update(array('pos'=>$i), 'smilies_id='.$id);
		}
		Dragonfly::getKernel()->CACHE->delete('bbsmilies');
	}
	cpg_error('Smilies order was successfully updated', 'Smilies Position Update', URL::admin('smilies'));
}

// Admin has selected to edit a smiley.
else if (isset($_GET['edit']))
{
	$smiley_id = intval($_GET['edit']);
	if ('POST' === $_SERVER['REQUEST_METHOD'])
	{
		// Admin has submitted changes while editing a smiley.

		// Get the submitted data, being careful to ensure that we only
		// accept the data we are looking for.
		$data = array(
			// Convert < and > to proper htmlentities for parsing.
			'code'      => str_replace('>', '&gt;', str_replace('<', '&lt;', trim($_POST['smile_code']))),
			'smile_url' => trim($_POST['smile_url']),
			'emoticon'  => trim($_POST['smile_emotion'])
		);

		// If no code was entered complain ...
		if (!$data['code'])      { cpg_error(sprintf(_ERROR_NOT_SET, 'Smiley code')); }
		if (!$data['smile_url']) { cpg_error(sprintf(_ERROR_NOT_SET, 'Smiley url')); }

		// Proceed with updating the smiley table.
		$db->TBL->bbsmilies->update($data, "smilies_id={$smiley_id}");
		Dragonfly::getKernel()->CACHE->delete('bbsmilies');
		cpg_error('The smiley information was successfully updated', 'Smiles Utility', URL::admin('smilies'));
	}
	$smile_data = $db->uFetchAssoc("SELECT * FROM {$db->TBL->bbsmilies} WHERE smilies_id={$smiley_id}");
	if (empty($smile_data)) {
		cpg_error('The information for the requested smiley could not be obtained');
	}
	smile_edit($smile_data);
}

else if (isset($_GET['delete']))
{
	// Admin has selected to delete a smiley.
	$result = $db->query("DELETE FROM {$db->TBL->bbsmilies} WHERE smilies_id = ".intval($_GET['delete']));
	if (!$result) {
		cpg_error('The smiley could not be deleted');
	} else {
		Dragonfly::getKernel()->CACHE->delete('bbsmilies');
		cpg_error('The smiley was successfully deleted', 'Smiles Utility', URL::admin('smilies'));
	}
}

else {
	// This is the main display of the page before the admin has selected any options.
	\Dragonfly\Output\Js::add('themes/admin/javascript/admin_smilies.js');
	$smilies = $db->uFetchAll("SELECT smilies_id, code, smile_url, emoticon, pos FROM {$db->TBL->bbsmilies} ORDER BY pos");
	// Replace htmlentites for < and > with actual character.
	for ($i = 0; $i < (is_countable($smilies) ? count($smilies) : 0); $i++) { $smilies[$i]['code'] = htmlspecialchars_decode($smilies[$i]['code']); }
	$TPL = Dragonfly::getKernel()->OUT;
	$TPL->smilies = $smilies;
	$TPL->smilies_path = $smilies_path;
	$TPL->display('admin/smilies/index');
}
