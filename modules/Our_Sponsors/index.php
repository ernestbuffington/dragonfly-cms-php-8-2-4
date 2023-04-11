<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
\Dragonfly\Page::title('Our Sponsors');

$K = \Dragonfly::getKernel();
if ($user_id = is_user()) {
	if (isset($_POST['add_banner']) && isset($_POST['type'])) {
		$K->SQL->TBL->banner->insert(array(
			'cid'         => $user_id,
			'imptotal'    => $_POST->uint('imptotal'),
			'impmade'     => 0,
			'clicks'      => 0,
			'imageurl'    => $_POST->url('imageurl') ?: '',
			'clickurl'    => $_POST->url('clickurl'),
			'alttext'     => $_POST->text('alttext'),
			'date'        => time(),
			'dateend'     => 0,
			'type'        => $_POST->uint('type'),
			'active'      => 0,
			'textban'     => $_POST->bool('textban'),
			'text_width'  => max(16, $_POST->uint('text_width')),
			'text_height' => max(16, $_POST->uint('text_height')),
			'text_title'  => $_POST->text('text_title'),
			'text_bg'     => $_POST->color('text_bg') ?: '',
			'text_clr'    => $_POST->color('text_clr') ?: '',
		));
		URL::redirect(URL::index());
	}

	$K->OUT->active_banners = $K->SQL->query("SELECT * FROM {$K->SQL->TBL->banner} WHERE cid={$user_id} AND active=1");
	$K->OUT->inactive_banners = $K->SQL->query("SELECT * FROM {$K->SQL->TBL->banner} WHERE cid={$user_id} AND active=0");
	$K->OUT->display('Our_Sponsors/my_banners');
}
else
{
	$K->OUT->all_active_banners = $K->SQL->query("SELECT * FROM {$K->SQL->TBL->banner} WHERE active=1");
	$K->OUT->display('Our_Sponsors/activelist');
}
