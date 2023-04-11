<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	Examples:

		<form tal:attributes="data-df-challenge php:Dragonfly\Output\Captcha::generateHidden()">

		\Dragonfly::getKernel()->OUT->my_captcha = \Dragonfly\Output\Captcha::generateRandom();
		<input class="df-challenge" type="text" tal:attributes="name my_captcha"/>

		<div>
			<label>Question:</label>
			<input class="df-challenge" type="text" tal:attributes="name php:Dragonfly\Output\Captcha::generateQuestion()"/>
		</div>

		<div>
			<label>Hidden:</label>
			<input class="df-challenge" type="hidden" tal:attributes="name php:Dragonfly\Output\Captcha::generateHidden()"/>
		</div>

		<div>
			<label>Image:</label>
			<input class="df-challenge" type="text" tal:attributes="name php:Dragonfly\Output\Captcha::generateImage()"/>
		</div>

		\Dragonfly\Output\Captcha::validate($_POST)

**********************************************/

namespace Dragonfly\Output;

abstract class Captcha
{

	protected static function set($value)
	{
		$key = md5(random_bytes(32));
		$_SESSION['DF_CAPTCHA'][$key] = array($value, time());
		$_SESSION['DF_CAPTCHA'] = array_slice($_SESSION['DF_CAPTCHA'], -4, 4, true);
/*
		foreach ($_COOKIE as $name => $value) {
			if (!isset($_SESSION['POODLE_CAPTCHA'][$name]) && preg_match('/^[a-z0-9]{32}=[hiq]:/', "{$name}={$value}")) {
				setcookie($name, null, -1);
			}
		}
*/
		return $key;
	}

	final public static function generateRandom()
	{
		switch (mt_rand(0,2))
		{
		case 0: return self::generateHidden();
		case 1: return self::generateImage();
		case 2: return self::generateQuestion();
		}
	}

	final public static function generateHidden()
	{
		$val = \Poodle\UUID::generate();
		$key = self::set($val);
		setrawcookie($key, rawurlencode('h:'.$val));
		return $key;
	}

	final public static function generateImage($chars=6)
	{
		$time = explode(' ', microtime());
		$key = self::set(substr(dechex($time[0]*3581692740), 0, $chars));
		setrawcookie($key, rawurlencode('i:'.\URL::load('captcha&'.$key)));
		return $key;
	}

	final public static function generateQuestion()
	{
		$key = self::set('value');
		setrawcookie($key, rawurlencode('q:What is your username?'));
		return $key;
	}

	final public static function validate($data)
	{
		$result = false;
		if (!empty($_SESSION['DF_CAPTCHA'])) {
			foreach ($_SESSION['DF_CAPTCHA'] as $name => $answer) {
				if (isset($data[$name])) {
					unset($_SESSION['DF_CAPTCHA'][$name]);
					if ($answer[0] == $data[$name]) {
						$result = time() - $answer[1]; // Return seconds from start form filling till the form POST
						break;
					}
				}
			}
		}
		if (!$result) {
			trigger_error("\Dragonfly\Output\Captcha::validate() failed for {$_SERVER['REMOTE_ADDR']} {$_SERVER['HTTP_USER_AGENT']}");
		}
		return $result;
	}

}
