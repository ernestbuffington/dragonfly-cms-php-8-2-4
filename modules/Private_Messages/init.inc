<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }

if (!is_user()) {
	\URL::redirect(\Dragonfly\Identity::loginURL());
}

$folders = array('inbox', 'sentbox', 'outbox', 'savebox');

$folder = $_POST->txt('folder') ?: $_GET->txt('folder');
$folder = in_array($folder, $folders) ? $folder : 'inbox';
\Dragonfly\Page::title(ucfirst($folder));

# Received a cancel request
if (isset($_POST['cancel'])) {
	URL::redirect(URL::index('&folder='.$folder));
}

$PMCFG = \Dragonfly::getKernel()->CFG->{$Module->name};

$mode = $_POST->txt('mode') ?: $_GET->txt('mode');

\Dragonfly\Output\Css::inline('
@media screen and (max-width:480px) { #LeftSide, #RightSide { display:none } }
.pm_meter, .pm_boxes { width:100%; display:inline-block; text-align:center }
.pm_boxes > a, nav.pm * > * > * > * > a  { display:inline-block; margin:3px 5px }
.pm_boxes > a { width:70px }');
