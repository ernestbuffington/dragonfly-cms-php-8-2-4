<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

	New way:
		\Dragonfly\BBCode::pushHeaders($smilies=false);
		<textarea class="bbcode">

**********************************************/

function smilies_table($mode, $field='message', $form='post')
{
	trigger_deprecated('it is embedded in BBCode');
}

function bbcode_table($field='message', $form='post', $allowed=0)
{
	trigger_deprecated('Use \Dragonfly\BBCode::pushHeaders() and <textarea class="bbcode">');
	\Dragonfly\BBCode::pushHeaders();
	return '<script type="text/javascript">Poodle.onDOMReady(function(){
		var textarea = document.forms[\''.$form.'\'].elements[\''.$field.'\'];
		if (textarea) { Poodle_BBCode(textarea); }
	});</script>';
}

function get_smilies($and_duplicates=false)
{
	trigger_deprecated('Use \Dragonfly\Smilies::get($and_duplicates)');
	return array();
}

# smilies_pass(
function set_smilies($message, $url='')
{
	trigger_deprecated('Use \Dragonfly\Smilies::parse($message, $url)');
	return \Dragonfly\Smilies::parse($message, $url);
}

# bbencode_first_pass() prepare bbcode for db insert
function encode_bbcode($text)
{
	trigger_deprecated('Use \Dragonfly\BBCode::encode($text)');
	return \Dragonfly\BBCode::encode($text);
}

function decode_bb_all($text, $allowed=0, $allow_html=false, $url='')
{
	trigger_deprecated('Use \Dragonfly\BBCode::decodeAll($text, $allowed, $allow_html, $url)');
	return \Dragonfly\BBCode::decodeAll($text, $allowed, $allow_html, $url);
}

function decode_bbcode($text, $allowed=0, $allow_html=false)
{
	trigger_deprecated('Use \Dragonfly\BBCode::decode($text, $allowed, $allow_html)');
	return \Dragonfly\BBCode::decode($text, $allowed, $allow_html);
}

function shrink_url($url)
{
	trigger_deprecated('Use \URL::shrink($url)');
	return \URL::shrink($url);
}

function make_clickable($text)
{
	trigger_deprecated('Use \URL::makeClickable($text)');
	return \URL::makeClickable($text);
}

# prepare_message(
function message_prepare($message, $html_on, $bbcode_on)
{
	trigger_deprecated('Use \Dragonfly\BBCode::encode_html() and \Dragonfly\BBCode::encode()');
	$message = \Dragonfly\BBCode::encode_html(trim($message));
	if ($bbcode_on) {
		$message = \Dragonfly\BBCode::encode($message);
	}
	return $message;
}

abstract class BBCode extends \Dragonfly\BBCode
{
}

if (!defined('ADMIN_PAGES') && isset($Module) && $Module->name == 'smilies') {
	exit;
}
