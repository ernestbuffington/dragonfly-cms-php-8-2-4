<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

class DateTime extends \DateTime
{
	const STRING_FORMAT = 'Y-m-d H:i:s',
		// Format types
		GREGORIAN   = 0,
		SOLAR_HIJRI = 1;

	function __construct($utctime = null, $timezone = null)
	{
		if ($utctime instanceof \DateTime) {
			parent::__construct(null, new \DateTimeZone('UTC'));
			$this->setTimestamp($utctime->getTimestamp());
		} else {
			if (is_numeric($utctime) && 2147483647 >= $utctime) {
				$utctime = '@'.(int)$utctime;
			}
			if ('@' === $utctime[0]) {
				parent::__construct($utctime);
			} else {
				parent::__construct($utctime, new \DateTimeZone('UTC'));
			}
		}
		if ($timezone) {
			$this->setTimezone($timezone);
		}
	}

	public function setTimezone($timezone)
	{
		return parent::setTimezone($timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone));
	}

	public function __toString()
	{
		return $this->format(static::STRING_FORMAT);
	}

	public function format($format, $type = 0)
	{
		if (1 == $type) {
			return $this->formatSolarHijri($format);
		}
		return parent::format($format);
	}

	protected static
		// jan, feb, mar, apr, may, jun, jul, aug, sep, okt, nov, dec
		$G_DAYS = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31),
		// far, ord, kho, tir, mor, sha, meh, aba, aza, dey, bah, esf
		$SH_DAYS = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29),
		$SH_MONTHS = array('Farvardin','Ordibehesht','Khordad','Tir','Mordad','Shahrivar','Mehr','Aban','Azar','Dey','Bahman','Esfand');

	public function formatSolarHijri($format)
	{
		$year  = parent::format('Y');
		$month = parent::format('m') - 1;

		$days  = parent::format('d');
		// add leap day?
		if (1 < $month && ((0 === $year % 4 && $year % 100) || 0 === $year % 400)) {
			++$days;
		}
		// add months
		while ($month) {
			$days += static::$G_DAYS[--$month];
		}

		$year -= 622;
		if ($days > 79) {
			$days -= 79;
			$month = 0;
			++$year;
			$day = $days;
		} else {
			$month = 9;
			$days += (3 === $year % 4 ? 11 : 10);
			$day = $days;
			$m = $month;
			while (1) {
				$days += static::$SH_DAYS[--$m];
				if (1 > $m) {
					break;
				}
			}
		}

		while (1) {
			$mdays = static::$SH_DAYS[$month++];
			if ($day > $mdays && 12 > $month) {
				$day -= $mdays;
			} else {
				break;
			}
		}

		return parent::format(preg_replace_callback('/(\\\\*)([djmnFMYyz])/', function($m)use($year,$month,$day,$days){
			if (0 === strlen($m[1]) % 2) {
				switch ($m[2])
				{
				case 'd': return $m[1] . str_pad($day, 2, '0', STR_PAD_LEFT);
				case 'j': return $m[1] . $day;
				case 'm': return $m[1] . str_pad($month, 2, '0', STR_PAD_LEFT);
				case 'n': return $m[1] . $month;
				case 'F': return $m[1] . '\\' . implode('\\',str_split(static::$SH_MONTHS[$month-1]));
				case 'M': return $m[1] . '\\' . implode('\\',str_split(substr(static::$SH_MONTHS[$month-1], 0, 3)));
				case 'Y': return $m[1] . $year;
				case 'y': return $m[1] . substr($year, -2);
				case 'z': return $m[1] . $days;
				}
			}
			return $m[0];
		}, $format));
	}

	public function setSolarHijriDate($year, $month, $day)
	{
		// add gregorian days + previous year leap day?
		$day += (0 === $year % 4) ? 79 : 78;

		// add months
		--$month;
		while ($month) {
			$day += static::$SH_DAYS[--$month];
		}

		$year += 621;
		// next year?
		if ($day > 365) {
			$day %= 365;
			++$year;
		}

		// add leap day?
		if ((0 === $year % 4 && $year % 100) || 0 === $year % 400) {
			++$day;
		}

		$month = 0;
		while (1) {
			$days = static::$G_DAYS[$month++];
			if (2 === $month && ((0 === $year % 4 && $year % 100) || 0 === $year % 400)) {
				++$days;
			}
			if ($day > $days) {
				$day -= $days;
			} else {
				break;
			}
		}

		parent::setDate($year, $month, $day);
	}

}

/**
 * Date or Time has no use for DateTimeZone
 * Therefore we set UTC
 */

class Date extends DateTime
{
	const STRING_FORMAT = 'Y-m-d';

	function __construct($time = null)
	{
		parent::__construct($time, 1);
	}

	public function setTimezone($timezone)
	{
		return parent::setTimezone('UTC');
	}
}

class Time extends DateTime
{
	const STRING_FORMAT = 'H:i:s';

	function __construct($time = null)
	{
		parent::__construct($time, 1);
	}

	public function setTimezone($timezone)
	{
		return parent::setTimezone('UTC');
	}
}

class Timestamp extends DateTime
{
	public function __toString()
	{
		return $this->getTimestamp();
	}
}
