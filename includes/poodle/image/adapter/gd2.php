<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Image\Adapter;

class GD2
{
	public const
		COLOR_BLACK   = 11,
		COLOR_BLUE    = 12,
		COLOR_CYAN    = 13,
		COLOR_GREEN   = 14,
		COLOR_RED     = 15,
		COLOR_YELLOW  = 16,
		COLOR_MAGENTA = 17,
		COLOR_OPACITY = 18,
		COLOR_ALPHA   = 19,
		COLOR_FUZZ    = 20,

		FILTER_UNDEFINED = 0,
		FILTER_POINT     = 1,
		FILTER_BOX       = 2,
		FILTER_TRIANGLE  = 3,
		FILTER_HERMITE   = 4,
		FILTER_HANNING   = 5,
		FILTER_HAMMING   = 6,
		FILTER_BLACKMAN  = 7,
		FILTER_GAUSSIAN  = 8,
		FILTER_QUADRATIC = 9,
		FILTER_CUBIC     = 10,
		FILTER_CATROM    = 11,
		FILTER_MITCHELL  = 12,
		FILTER_LANCZOS   = 13,
		FILTER_BESSEL    = 14,
		FILTER_SINC      = 15,

		CHANNEL_UNDEFINED = 0,
		CHANNEL_GRAY    = 1,
		CHANNEL_RED     = 1,
		CHANNEL_GREEN   = 2,
		CHANNEL_BLUE    = 4,
		CHANNEL_CYAN    = 1,
		CHANNEL_MAGENTA = 2,
		CHANNEL_YELLOW  = 4,
		CHANNEL_ALPHA   = 8,
		CHANNEL_OPACITY = 8,
		CHANNEL_MATTE   = 8,
		CHANNEL_BLACK   = 32,
		CHANNEL_INDEX   = 32,
		CHANNEL_ALL     = 255,

		COMPOSITE_DEFAULT     = 40,
		COMPOSITE_UNDEFINED   = 0,
		COMPOSITE_NO          = 1,
		COMPOSITE_ADD         = 2, // Deprecated
		COMPOSITE_ATOP        = 3, // Composites the inside of one layer with the other
		COMPOSITE_BLEND       = 4,
		COMPOSITE_BUMPMAP     = 5, // The same as COMPOSITE_MULTIPLY, except the source is converted to greyscale first.
		COMPOSITE_CLEAR       = 7,
		COMPOSITE_COLORBURN   = 8,
		COMPOSITE_COLORDODGE  = 9,
		COMPOSITE_COLORIZE    = 10,
		COMPOSITE_COPYBLACK   = 11,
		COMPOSITE_COPYBLUE    = 12,
		COMPOSITE_COPY        = 13, // Simply place the source on top of the destination.
		COMPOSITE_COPYCYAN    = 14,
		COMPOSITE_COPYGREEN   = 15,
		COMPOSITE_COPYMAGENTA = 16,
		COMPOSITE_COPYOPACITY = 17,
		COMPOSITE_COPYRED     = 18,
		COMPOSITE_COPYYELLOW  = 19,
		COMPOSITE_DARKEN      = 20,
		COMPOSITE_DSTATOP     = 21,
		COMPOSITE_DST         = 22,
		COMPOSITE_DSTIN       = 23,
		COMPOSITE_DSTOUT      = 24,
		COMPOSITE_DSTOVER     = 25,
		COMPOSITE_DIFFERENCE  = 26, // The difference in color values. Good for comparing images.
		COMPOSITE_DISPLACE    = 27,
		COMPOSITE_DISSOLVE    = 28,
		COMPOSITE_EXCLUSION   = 29,
		COMPOSITE_HARDLIGHT   = 30,
		COMPOSITE_HUE         = 31,
		COMPOSITE_IN          = 32, // Replaces the inside of one layer with another
		COMPOSITE_LIGHTEN     = 33,
		COMPOSITE_LUMINIZE    = 35,
		COMPOSITE_MINUS       = 36, // The source is subtracted to the destination and replaces the destination.
		COMPOSITE_MODULATE    = 37,
		COMPOSITE_MULTIPLY    = 38,
		COMPOSITE_OUT         = 39, // Replaces the outside of one layer with another
		COMPOSITE_OVER        = 40, // Overlay one image over the next
		COMPOSITE_OVERLAY     = 41,
		COMPOSITE_PLUS        = 42, // The source is added to the destination and replaces the destination.
		COMPOSITE_REPLACE     = 43,
		COMPOSITE_SATURATE    = 44,
		COMPOSITE_SCREEN      = 45,
		COMPOSITE_SOFTLIGHT   = 46,
		COMPOSITE_SRCATOP     = 47,
		COMPOSITE_SRC         = 48,
		COMPOSITE_SRCIN       = 49,
		COMPOSITE_SRCOUT      = 50,
		COMPOSITE_SRCOVER     = 51,
		COMPOSITE_SUBTRACT    = 52, // Deprecated
		COMPOSITE_THRESHOLD   = 53,
		COMPOSITE_XOR         = 54; // The part of the source that lies outside of the destination is combined with the part of the destination that lies outside the source.

	protected
		$img = null;

	private
		$error = null,
		$file,
		$format,
		$compression_q = 85,
		$type;

	function __construct($filename=null)
	{
		if (!extension_loaded('gd')) {
			throw new \Exception('GD image library not available');
		}
		\Poodle\PHP\INI::set('memory_limit', '64M');
		if (is_string($filename) && !$this->loadImage($filename)) {
			throw new \Exception($this->error);
		}
	}

	function __destruct()
	{
		$this->free();
	}

	function __toString()
	{
		return $this->getImageBlob();
	}

	public function free()
	{
		if ($this->img) {
			imagedestroy($this->img);
			$this->img = null;
		}
	}

	public function newPixelObject($color = null)
	{
		return new GD2Pixel($color);
	}

	private function setError($msg)
	{
		$this->error = $msg;
		return false;
	}

	private function store_image($filename)
	{
		switch ($this->format)
		{
		case 'png':
		case 'png8':
		case 'png24':
		case 'png32':
			imagesavealpha($this->img, true);
			return imagepng($this->img, $filename, 9);

		case 'jpeg':
			return imagejpeg($this->img, $filename, $this->compression_q);

		case 'gif':
			return imagegif($this->img, $filename);
		}
		return false;
	}

	public function newImage($cols, $rows, $background, $format='')
	{
		$this->img = $this->create_image($cols, $rows, true);
		if ('none' !== $background) {
//			imagefill($this->img, 0, 0, imagecolorallocate($this->img, 255, 0, 0));
		}
		if ($format) {
			$this->setImageFormat($format);
		}
	}

	private function create_image($width = -1, $height = -1, $trueColor = null)
	{
		if (-1 == $width) { $width = imagesx($this->img); }
		if (-1 == $height) { $height = imagesy($this->img); }
		if ($trueColor || ($this->img && imageistruecolor($this->img))) {
			$tmp_img = imagecreatetruecolor($width, $height);
			imagesavealpha($tmp_img, true);
			$trans_colour = imagecolorallocatealpha($tmp_img, 0, 0, 0, 127);
			imagefill($tmp_img, 0, 0, $trans_colour);
		} else {
			$tmp_img = imagecreate($width, $height);
			imagepalettecopy($tmp_img, $this->img);
			$t_clr_i = imagecolortransparent($this->img);
			if (-1 !== $t_clr_i) {
				imagecolortransparent($tmp_img, $t_clr_i);
				imagefill($tmp_img, 0, 0, $t_clr_i);
			}
		}
		return $tmp_img;
	}

	/**
	 * Imagick PECL similar methods
	 */

	public function clear()   { $this->free(); return true; }
	public function destroy() { $this->free(); return true; }

	public function compositeImage(GD2 $composite_object, $composite, $x, $y, $channel=255)
	{
		imagealphablending($this->img, $channel & 8);
		return imagecopy($this->img, $composite_object->img, $x, $y, 0, 0, $composite_object->getImageWidth(), $composite_object->getImageHeight());
	}

	public function cropImage($width, $height, $x, $y)
	{
		$x = min(imagesx($this->img), max(0, $x));
		$y = min(imagesy($this->img), max(0, $y));
		$width   = min($width,  imagesx($this->img) - $x);
		$height  = min($height, imagesy($this->img) - $y);
		$tmp_img = $this->create_image($width, $height);
		if (!imagecopy($tmp_img, $this->img, 0, 0, $x, $y, $width, $height)) {
			imagedestroy($tmp_img);
			throw new \Exception('Failed image transformation: crop()');
		}
		imagedestroy($this->img);
		$this->img = $tmp_img;
		return true;
	}

	public function cropThumbnailImage($width, $height)
	{
		$x = imagesx($this->img);
		$y = imagesy($this->img);
		$tx = $x/$width;
		$ty = $y/$height;
		if ($tx > $ty) {
			$x = round($x/$ty);
			$this->thumbnailImage($x, $height);
			$x = floor(($x-$width)/2);
			$y = 0;
		} else if ($tx < $ty) {
			$y = round($y/$tx);
			$this->thumbnailImage($width, $y);
			$x = 0;
			$y = floor(($y-$height)/2);
		} else {
			return $this->thumbnailImage($width, $height);
		}
		return $this->cropImage($width, $height, $x, $y);
	}

	public function flipImage()
	{
		return imageflip($this->img, IMG_FLIP_VERTICAL);
	}

	public function flopImage()
	{
		return imageflip($this->img, IMG_FLIP_HORIZONTAL);
	}

	public function gammaImage($gamma, $channel=0)
	{
		return ((int)$gamma < 1) ? imagegammacorrect($this->img, 1.0, $gamma) : true;
	}

	public function getImageBlob()
	{
		ob_start();
		if (!$this->store_image(null)) {
			ob_end_clean();
			throw new \Exception('Failed to generate image blob');
		}
		return ob_get_clean();
	}
	public function getImageFilename() { return $this->file; }
	public function getImageFormat()   { return $this->format; }
	public function getImageHeight()   { return imagesy($this->img); }
	public function getImageMimeType()
	{
		switch ($this->format)
		{
		case 'png':
		case 'png8':
		case 'png24':
		case 'png32':
			return 'image/png';
		case 'jpeg':
			return 'image/jpeg';
		case 'gif':
			return 'image/gif';
		}
		return false;
	}
	public function getImageType()  { return $this->type; }
	public function getImageWidth() { return imagesx($this->img); }

	public function magnifyImage() { return $this->thumbnailImage(imagesx($this->img)*2, 0); }
	public function minifyImage()  { return $this->thumbnailImage(round(imagesx($this->img)/2), 0); }

	public function scaleImage($columns, $rows, $fit)
	{
		return $this->thumbnailImage($columns, $rows, $fit);
	}
	public function resizeImage($columns, $rows, $filter, $blur, $fit=false)
	{
		return $this->thumbnailImage($columns, $rows, $fit);
	}

	protected function loadImage($file)
	{
		if (!($imginfo = getimagesize($file))) {
			return $this->setError($file.' is not an image or not accessible');
		}
		switch ($imginfo[2])
		{
		case IMAGETYPE_GIF:
			$this->img = imagecreatefromgif($file);
			$this->format = 'gif';
			break;

		case IMAGETYPE_JPEG:
			$this->img = imagecreatefromjpeg($file);
			$this->format = 'jpeg';
			break;

		case IMAGETYPE_PNG:
			$this->img = imagecreatefrompng($file);
			$this->format = 'png';
			break;

		default:
			return $this->setError('Unsupported fileformat: '.$imginfo['mime']);
		}
		if (!is_resource($this->img)) {
			return $this->setError('Failed to create image resource');
		}
		$this->file = $file;
		$this->type = (int)$imginfo[2];
/*
		imagick::IMGTYPE_UNDEFINED
		imagick::IMGTYPE_BILEVEL
		imagick::IMGTYPE_GRAYSCALE
		imagick::IMGTYPE_GRAYSCALEMATTE
		imagick::IMGTYPE_PALETTE
		imagick::IMGTYPE_PALETTEMATTE
		imagick::IMGTYPE_TRUECOLOR
		imagick::IMGTYPE_TRUECOLORMATTE
		imagick::IMGTYPE_COLORSEPARATION
		imagick::IMGTYPE_COLORSEPARATIONMATTE
		imagick::IMGTYPE_OPTIMIZE
*/
		return true;
	}

	public function rotate($degrees)
	{
		return $this->rotateImage(0, $degrees);
	}

	public function rotateImage($background, $degrees)
	{
		if (0 === ($degrees % 360)) { return true; }
		/** rotate clockwise */
		if (!function_exists('imagerotate')) { require(__DIR__.'/gd2/imagerotate.inc'); }
		$tmp_img = imagerotate($this->img, $degrees * -1, 0);
		if (!is_resource($tmp_img)) { return false; }
		imagedestroy($this->img);
		$this->img = $tmp_img;
		return true;
	}

	public function sampleImage($width, $height)
	{
		if (!$width && !$height) { return false; }
		if (0 > min($width, $height)) { return false; }
		$x = imagesx($this->img);
		$y = imagesy($this->img);
		if ($x != $width || $y != $height) {
			$tmp_img = $this->create_image($width, $height);
			if (!is_resource($tmp_img)) { return false; }
			if (!imagecopyresized($tmp_img, $this->img, 0, 0, 0, 0, $width, $height, $x, $y)) {
				imagedestroy($tmp_img);
				return false;
			}
			imagedestroy($this->img);
			$this->img = $tmp_img;
		}
		return true;
	}

	public function thumbnailImage($width, $height, $fit=false)
	{
		if (!$width && !$height) { return false; }
		if (0 > min($width, $height)) { return false; }
		$x = imagesx($this->img);
		$y = imagesy($this->img);
		$tx = $width  ? $x/$width : 0;
		$ty = $height ? $y/$height : 0;
		if (!$width  || ($fit && $tx < $ty)) { $width  = round($x / $ty); }
		if (!$height || ($fit && $tx > $ty)) { $height = round($y / $tx); }
		$tmp_img = $this->create_image($width, $height);
		if (!is_resource($tmp_img)) { return false; }
		imagealphablending($tmp_img, false);
		if (!imagecopyresampled($tmp_img, $this->img, 0, 0, 0, 0, $width, $height, $x, $y)) {
			if (!imagecopyresized($tmp_img, $this->img, 0, 0, 0, 0, $width, $height, $x, $y)) {
				imagedestroy($tmp_img);
				return false;
			}
		}
		imagedestroy($this->img);
		$this->img = $tmp_img;
		return true;
	}

	public function getImageCompressionQuality() { return $this->compression_q; }
	public function setImageCompressionQuality($q) { $this->compression_q = min(100,(int)$q); }
	public function setImageFilename($filename) { $this->file = $filename; }
	/** http://www.imagemagick.org/script/formats.php */
	public function setImageFormat($format)     { $this->format = strtolower($format); }

	public function valid() { is_resource($this->img); }

	public function writeImage($filename=null)
	{
		if ($filename) { $this->setImageFilename($filename); }
		return $this->store_image($this->getImageFilename());
	}

	public function getCopyright() { return 'GD'; }

	public function getPackageName() { return 'GD library'; }

	public function getReleaseDate() { return null; }

	public function getVersion()
	{
		return array(
			'versionNumber' => (int)GD_VERSION,
			'versionString' => GD_VERSION.(GD_BUNDLED?' (bundled)':'')
		);
	}

	public function queryFormats($pattern="*") { return array('GIF','JPEG','PNG'); }

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
		if (preg_match('@^#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})$@Di', $params['color'], $match)) {
			array_shift($match);
		} else {
			$match = array(0, 0, 0);
		}
		$params['angle'] = 360-$params['angle'];
		$c = imagecolorresolve($this->img, hexdec($match[0]), hexdec($match[1]), hexdec($match[2]));
		if ('.ttf' === substr($params['font'], -4)) {
			// TrueType font
			return imagettftext($this->img, $params['size']*0.8, $params['angle'], $params['x'], $params['y'], $c, $params['font'], $params['text']);
			// FreeType 2
//			return imagefttext($this->img, $params['size'], $params['angle'], $params['x'], $params['y'], $c, $params['font'], $params['text'], $extrainfo);
			// PostScript Type1 font
//			return imagepstext($this->img, $params['size'], $params['angle'], $params['x'], $params['y'], $c, $params['font'], $params['text']);
		}
		return imagestring($this->img, $params['size'], $params['x'], $params['y'], $params['text'], $c);
	}

	public function stripImage() { return $this; }
}

class GD2Pixel
{
	protected $color;

	function __construct($color = '')
	{
		$this->setColor($color);
	}

	public function getColor()
	{
		return $this->color;
	}

	public function setColor($color)
	{
		$this->color = $color;
	}

}
