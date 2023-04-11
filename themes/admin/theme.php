<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © since 2004 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

define('THEME_VERSION', '10');

function OpenTable()   { echo '<div class="border">'; }
function CloseTable()  { echo '</div>'; }

function OpenTable2()  { echo '<div class="border">'; }
function CloseTable2() { echo '</div>'; }

function themeheader()
{
	$CFG = \Dragonfly::getKernel()->CFG;
	$OUT = \Dragonfly::getKernel()->OUT;

//	$OUT->tpl_layout = 'default';
	$OUT->tpl_header = 'header';
	$OUT->tpl_footer = 'footer';

	if (//$GLOBALS['DF']::BOOT_HEADER === $GLOBALS['DF']->getState() &&
	    is_admin() && 'logout' != $_GET->raw('op'))
	{
		$OUT->S_MAIN_MENU = \Dragonfly\Page\Menu\Admin::display($CFG->global->admingraphic);
	}

	\Dragonfly\Output\Css::add('poodle/tabs');
	\Dragonfly\Output\Css::add('poodle/forms');
	\Dragonfly\Output\Js::add('includes/poodle/javascript/tabs.js');
	\Dragonfly\Output\Js::add('includes/poodle/javascript/forms.js');
	\Dragonfly\Output\Js::add('themes/default/javascript/toggle.js');

	if (CPG_DEBUG || DF_MODE_DEVELOPER || \Dragonfly::$DEBUG) {
		\Dragonfly\Output\Css::add('poodle/debugger');
		\Dragonfly\Output\Js::add('includes/poodle/javascript/debugger.js');
	}

	$OUT->PUBLIC_HEADER = !defined('ADMIN_PAGES');
}

function theme_open_form($link, $form_name=false, $legend=false, $tborder=false)
{
	$form_name	= $form_name ? ' id="'.$form_name.'"' :'';
	return '<form method="post" action="'.$link.'"'.$form_name.'><fieldset>'
		.($legend ? "<legend>{$legend}</legend>" : '');
}
