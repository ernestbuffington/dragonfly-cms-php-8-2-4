<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Image\Adapter;

if (!class_exists('Imagick',false)) { return; }

class IMagick extends \Imagick
{
	function __construct($file=null)
	{
		parent::__construct($file);
		// Strip meta data
		if ($file) { parent::stripImage(); }
	}

	function __destruct()
	{
		$this->clear();
	}

	public function free()
	{
		$this->clear();
	}

	public function newPixelObject($color = null)
	{
		return new \ImagickPixel($color);
	}

	public function add_text($params)
	{
		$default_params = array(
			'text'  => 'Default text',
			'x'     => 10,
			'y'     => 20,
			'size'  => 12,
			'color' => '#000000',
			'font'  => dirname(__DIR__).'/fonts/default.ttf',
			'angle' => 0,
		);
		$params = array_merge($default_params, $params);
		$params['color']= strtolower($params['color']);
		$draw  = new \ImagickDraw();
		$pixel = new \ImagickPixel($params['color']);
		$draw->setfillcolor($pixel);
		$draw->setfontsize($params['size']);
		$draw->setfont($params['font']);
		return $this->annotateimage($draw, $params['x'], $params['y'], $params['angle'], $params['text']);
	}

	public function readImage($file)
	{
		throw new \BadMethodCallException('readImage() not supported');
	}

	public function rotate($degrees)
	{
		return $this->rotateImage(new \ImagickPixel(), $degrees);
	}
}
