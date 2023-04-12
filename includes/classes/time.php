<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/time.php,v $
  $Revision: 1.22 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:42 $
  
  NOTES:
  jalali_to_gregorian() and gregorian_to_jalali() are
  Copyright (C) 2000 under GNU GPL by Roozbeh Pournader and Mohammad Toossi
  http://iranphp.net/
  Sallar Kaboli hooks his copyright to it in 2004 which is incorrect
  They are slightly modified for a better ease of use

  Most countries that observe daylight saving time are listed below.
  They all save one hour in the summer and change their clocks some time
  between midnight and 3 am.
  Data taken from http://webexhibits.org/daylightsaving/g.html

  the integer containing the country data is the country int. dialcode since
  ISO 3166 isn't detailed enough unless you have the money for ISO 3166-3.
  http://kropla.com/dialcode.htm
**********************************************/

global $l10n_dst_regions, $l10n_gmt_regions;

$l10n_gmt_regions = array(
	-12    => '(GMT -12:00) Eniwetok, Kwajalein',
	-11    => '(GMT -11:00) Midway Island, Samoa',
	-10    => '(GMT -10:00) Hawaii',
	-9     => '(GMT -9:00) Alaska',
	-8     => '(GMT -8:00) Pacific Time (US &amp; Canada), Tijuana',
	-7     => '(GMT -7:00) Mountain Time (US &amp; Canada), Arizona',
	-6     => '(GMT -6:00) Central Time (US &amp; Canada), Mexico City',
	-5     => '(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima, Quito',
	-4     => '(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz',
	'-3.5' => '(GMT -3:30) Newfoundland',
	-3     => '(GMT -3:00) Brassila, Buenos Aires, Georgetown, Falkland Is',
	-2     => '(GMT -2:00) Mid-Atlantic, Ascension Is., St. Helena',
	-1     => '(GMT -1:00) Azores, Cape Verde Islands',
	0      => '(GMT) Casablanca, Dublin, Edinburgh, London, Lisbon, Monrovia',
	1      => '(GMT +1:00) Amsterdam, Berlin, Brussels, Madrid, Paris, Rome',
	2      => '(GMT +2:00) Cairo, Helsinki, Kaliningrad, South Africa, Tallinn, Warsaw',
	3      => '(GMT +3:00) Baghdad, Riyadh, Moscow, Nairobi',
	'3.5'  => '(GMT +3:30) Tehran',
	4      => '(GMT +4:00) Abu Dhabi, Baku, Muscat, Tbilisi',
	'4.5'  => '(GMT +4:30) Kabul',
	5      => '(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent',
	'5.5'  => '(GMT +5:30) Bombay, Calcutta, Madras, New Delhi',
    '5.75' => '(GMT +5:45) Nepal',
	6      => '(GMT +6:00) Almaty, Colombo, Dhaka, Novosibirsk',
	'6.5'  => '(GMT +6:30) Rangoon',
	7      => '(GMT +7:00) Bangkok, Hanoi, Jakarta',
	8      => '(GMT +8:00) Beijing, Hong Kong, Perth, Singapore, Taipei',
	9      => '(GMT +9:00) Osaka, Sapporo, Seoul, Tokyo, Yakutsk',
	'9.5'  => '(GMT +9:30) Adelaide, Darwin',
	10     => '(GMT +10:00) Canberra, Guam, Melbourne, Sydney, Vladivostok',
    '10.5' => '(GMT +10:30) South Australia Summer', 
	11     => '(GMT +11:00) Magadan, New Caledonia, Solomon Islands',
    '11.5' => '(GMT +11:30) Norfolk Isl. Australia', 
	12     => '(GMT +12:00) Auckland, Wellington, Fiji, Marshall Island',
    '12.75' => '(GMT +12:45) Chatham Island',
    13     => '(GMT +13:00) Phoenix Islands',
    14     => '(GMT +14:00) Christmas Islands'
);

$l10n_dst_regions = array(
	0   => array('No DST', 0, 0),
	61  => array('Australia (Capital Ter., South), Victoria, New South Wales, Lord Howe Isl.',
		array(2,10,31,0), # Last Sunday in October at ?am
		array(2,3,31,0)   # Last Sunday in March at ?am
	),
	613 => array('Australia - Tasmania',
		array(2,10,7,0), # First Sunday in October at ?am
		array(2,3,31,0)  # Last Sunday in March at ?am
	),
	55  => array('Brazil',
		array(2,11,7,0), # First Sunday in November at ?am
		array(2,2,21,0)  # Third Sunday in February at ?am
	),
	# http://webexhibits.org/daylightsaving/chile.html
	56  => array('Chile',
		array(0,10,14,6), # Second Saturday of October - at midnight
		array(0,3,14,6)  # Second Saturday of March - at midnight
	),
	53  => array('Cuba',
		array(2,4,1),    # April 1 at ?am
		array(2,10,31,0) # Last Sunday in October at ?am
	),
	20  => array('Egypt',
		# time, month, day, weekday[0-6]
		array(2,4,30,5), # Last friday in April at ?am
		array(2,9,30,4)  # Last thursday in September at ?am
	),
    # http://webexhibits.org/daylightsaving/uk.html
	44  => array('European Union - Western (GMT)',
		array(1,3,31,0), # Last Sunday in March at 1am
		array(1,10,31,0) # Last Sunday in October at 1am
	),
	# http://webexhibits.org/daylightsaving/eu.html
	3   => array('European Union - Central (GMT +1)',
		array(2,3,31,0), # Last Sunday in March at 2am
		array(2,10,31,0) # Last Sunday in October at 2am
	),
	# Finland, Estonia, Romania, Bulgaria, Greece, Cyprus
	35  => array('European Union - Eastern (GMT +2)',
		array(3,3,31,0), # Last Sunday in March at 3am
		array(3,10,31,0) # Last Sunday in October at 3am
	),
	500 => array('Falklands',
		array(1,9,14,0), # First Sunday on or after 8 September
		array(1,4,12,0)  # First Sunday on or after 6 April
	),
	299 => array('Greenland',
		array(1,3,31,0), # Last Sunday in March at 1am
		array(1,10,31,0) # Last Sunday in October at 1am
	),
	# http://ortelius.de/kalender/pers_en.php
	98  => array('Iran',
		array(2,1,1,-1), # the first day of Farvardin at ?am
		array(2,7,1,-1)  # the first day of Mehr at ?am
	),
	964 => array('Iraq',
		array(2,4,1), # April 1 at ?am
		array(2,10,1) # October 1 at ?am
	),
	# Estimate, Israel decides the dates every year
	# http://webexhibits.org/daylightsaving/g.html#i
	972 => array('Israel',
		array(2,4,7,5), # First Friday in April at ?am
		array(2,9,7,5)  # First Friday in September at ?am
	),
	996 => array('Kirgizstan',
		array(2,3,31,0), # Last Sunday in March at ?am
		array(2,10,31,0) # Last Sunday in October at ?am
	),
	961 => array('Lebanon',
		array(2,3,31,0), # Last Sunday in March at ?am
		array(2,10,31,0) # Last Sunday in October at ?am
	),
	264 => array('Namibia',
		array(2,9,7,0), # First Sunday in September at ?am
		array(2,4,7,0)  # First Sunday in April at ?am
	),
	# http://webexhibits.org/daylightsaving/newZealand.html
	64  => array('New Zealand, Chatham',
		array(2,10,7,0), # First Sunday in October at ?am
		array(2,3,31,0)  # Last Sunday in March at ?am
	),
	# http://webexhibits.org/daylightsaving/g.html#p
	970 => array('Palestine',
		array(2,3,21,5), # First Friday on or after 15 April at ?am
		array(2,10,21,5) # First Friday on or after 15 October at ?am
	),
	595 => array('Paraguay',
		array(2,3,7,0), # First Sunday in September at ?am
		array(2,10,7,0) # First Sunday in April at ?am
	),
	7   => array('Russia',
		array(2,3,31,0), # Last Sunday in March at 2am
		array(2,10,31,0) # Last Sunday in October at 2am
	),
	963 => array('Syria',
		array(2,4,1), # April 1 at ?am
		array(2,10,1) # October 1 at ?am
	),
	676 => array('Tonga',
		array(2,11,7,0), # First Sunday in November at ?am
		array(2,1,31,0)  # Last Sunday in January at ?am
	),
	1   => array('U.S., Canada',
		array(2,3,14,0),  # Second Sunday in March at 2am
		array(2,11,7,0) # First Sunday in November at 2am
	),
	37  => array('former state of USSR',
		array(2,3,31,0), # Last Sunday in March at ?am
		array(2,10,31,0) # Last Sunday in October at ?am
	),
	52   => array('Mexico, St. Johns, Bahamas, Turks, Caicos',
		array(2,4,7,0),  # First Sunday in april at 2am
		array(2,10,31,0) # Last Sunday in October at 2am
	)

);

/*
  Notes:
  PHP date, strftime, gmdate, gmstrftime and gmmktime functions are
  DST sensitive
  Without a given time:
    - date() uses the current server time.
      This is DST affected if the server uses DST
    - gmdate() uses the current GMT time, it extracts server GMT & DST.
  With a given time
    * if server time is DST but the given time isn't then DST is extracted
    * if server time isn't DST but the given time is then DST is added
    - date() add/extracts DST
    - gmdate() extracts server GMT & DST
*/

class L10NTime {

	# Convert the GMT time to local time
	public static function tolocal($time, $region, $gmt) {
		if ($gmt != 0) { $time += (3600*$gmt); }
		return (L10NTime::in_dst($time, $region)) ? $time+3600 : $time;
	}

	# Convert 01-01-2005 12:00:00 GMT to 01-01-2005 12:00:00 Local Time
	# Convert the local time to GMT
	public static function toGMT($time, $region, $gmt) {
		$time -= ($gmt*3600);
		return (L10NTime::in_dst($time, $region)) ? $time-3600 : $time;
	}

	public static function strftime($format, $time, $region=0, $gmt=0) {
		$datetime = [];
        global $LNG;
		# check if we already have a unix timestamp else convert 
		if (!is_numeric($time)) {
			# 'YEAR-MONTH-DAY HOUR:MIN:SEC' aka MySQL DATETIME
			if (preg_match('#([0-9]{4})\-([0-9]{1,2})\-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#m', $time, $datetime)) {
				# gmmktime() adds server GMT & DST
				$time = mktime($datetime[4],$datetime[5],$datetime[6],$datetime[2],$datetime[3],$datetime[1]);
			}
		}
		# Convert the GMT time to local time
		$time = L10NTime::tolocal($time, $region, $gmt);
		# strftime() is affected by DST so we avoid that
		# If server time is DST but the given time isn't then DST is extracted
		# Fixed for PHP 5.1? http://bugs.php.net/bug.php?id=32588
		if (date('I') == 1 && date('I', $time) == 0) $time += 3600;
//		if (date('I') == 0 && date('I', $time) == 1) $time -= 3600; # thisone is unshure yet
		# return the formatted string
		$format = str_replace(
			array('%a', '%A', '%b', '%B'),
			array('_D%w', '_l%w', '_M%m', '_F%m'),
			$format);
		$time = strftime($format, $time);
		return preg_replace_callback('#_([DlFM])([0-9]{1,2})#', fn($matches) => $LNG['_time'][$matches[1]][intval($matches[2])], $time);
	}

	public static function date($format, $time, $region=0, $gmt=0) {
		$datetime = [];
        global $LNG;
		# check if we already have a unix timestamp else convert 
		if (!is_numeric($time)) {
			# 'YEAR-MONTH-DAY HOUR:MIN:SEC' aka MySQL DATETIME
			if (preg_match('#([0-9]{4})\-([0-9]{1,2})\-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#m', $time, $datetime)) {
				$time = mktime($datetime[4],$datetime[5],$datetime[6],$datetime[2],$datetime[3],$datetime[1]);
			}
		}
		# Convert the GMT time to local time
		$time = L10NTime::tolocal($time, $region, $gmt);
		# date() is affected by DST so we avoid that
		if (date('I') == 1 && date('I', $time) == 0) $time += 3600;
		# return correct formatted time
		$format = preg_replace('#([Dl])#', '_\\\\\\1w', $format);
		$format = preg_replace('#([FM])#', '_\\\\\\1n', $format);
		$time = date($format, $time);
		return preg_replace_callback('#_([DlFM])([0-9]{1,2})#', fn($matches) => $LNG['_time'][$matches[1]][intval($matches[2])], $time);
	}

	public static function strtotime($time, $now=0) {
		static $php5;
		if ($now < 1) $now = gmtime();
		$time = strtotime($time, $now);
		if ($php5 ||
		   ($time >= 0 && PHPVERS == 50 && version_compare(phpversion(), '5.0.3', '<=')))
		{
			$time += (L10NTime::date('H', $now)*3600);
			$time += (date('i', $now)*60);
			$time += date('s', $now);
			$php5 = true;
		}
		return $time;
	}

	# Check if the local time is inside DST depending on region
	public static function in_dst($localtime, $region)
	{
		global $l10n_dst_regions;
		if (!isset($l10n_dst_regions[$region]) || $region <= 0) return false;
		$start = $l10n_dst_regions[$region][1];
		$end   = $l10n_dst_regions[$region][2];
		return L10NTime::is_dst($localtime, $start, $end);
	}

	# Convert the local time to DST depending on region
	public static function get_dst_time($localtime, $region)
	{
		return (L10NTime::in_dst($localtime, $region)) ? $localtime+3600 : $localtime;
	}
/*
  Start internal functions mostly not used outside this class
*/
	public static function get_dst_switch($localtime, $data)
	{
		$gdata = [];
        $this_year = L10NTime::date('Y', $localtime);
		if (isset($data[3]) && $data[3] == -1) {
			# DST switch is on a jalali calendar day so convert
			$jdate = L10NTime::gregorian_to_jalali($this_year, L10NTime::date('n', $localtime), L10NTime::date('j', $localtime));
			$gdate = L10NTime::jalali_to_gregorian($jdate[0], $data[1], $data[2]);
			$data[1] = $gdata[1]; # Gregorian month
			$data[2] = $gdata[2]; # Gregorian day
			unset($data[3]);
		}
		$switchtime = mktime($data[0],0,0,$data[1],$data[2],$this_year);
		$time = 0;
		if (isset($data[3])) {
			# DST switch is on a set weekday so change the time accordingly
			$the_weekday = L10NTime::date('w', $switchtime);
			if ($the_weekday > $data[3]) {
				$time = ($the_weekday-$data[3])*86400;
			} elseif ($the_weekday < $data[3]) {
				$time = (7+$the_weekday-$data[3])*86400;
			}
		}
		return $switchtime-$time;
	}

	public static function is_dst($localtime, $start, $end)
	{
		$dst_start = L10NTime::get_dst_switch($localtime, $start);
		$dst_end = L10NTime::get_dst_switch($localtime, $end);
		if ($dst_start < $dst_end) {
			# Northern Hemisphere
			return ($localtime > $dst_start && $localtime < $dst_end);
		} else {
			# Southern Hemisphere
			return ($localtime > $dst_start || $localtime < $dst_end);
		}
	}

	public static function gregorian_to_jalali($g_y, $g_m, $g_d)
	{
		$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
		$gy = $g_y-1600;
		$gm = $g_m-1;
		$gd = $g_d-1;
		$g_day_no = 365*$gy + intval(($gy+3)/4) - intval(($gy+99)/100) + intval(($gy+399)/400);
		for ($i=0; $i < $gm; ++$i)
			$g_day_no += $g_days_in_month[$i];
		/* leap and after Feb */
		if ($gm>1 && (($gy%4==0 && $gy%100!=0) || ($gy%400==0)))
			++$g_day_no;
		$g_day_no += $gd;
		$j_day_no = $g_day_no-79;
		$j_np = intval($j_day_no/12053);
		$j_day_no %= 12053;
		$jy = 979+33*$j_np+4*intval($j_day_no/1461);
		$j_day_no %= 1461;
		if ($j_day_no >= 366) {
			$jy += intval(($j_day_no-1)/365);
			$j_day_no = ($j_day_no-1)%365;
		}
		for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
			$j_day_no -= $j_days_in_month[$i];
		}
		$jm = $i+1;
		$jd = $j_day_no+1;
		return array($jy, $jm, $jd);
	}

	public static function jalali_to_gregorian($j_y, $j_m, $j_d)
	{
		$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
		$jy = $j_y-979;
		$jm = $j_m-1;
		$jd = $j_d-1;
		$j_day_no = 365*$jy + intval($jy/33)*8 + intval(($jy%33+3)/4);
		for ($i=0; $i < $jm; ++$i)
			$j_day_no += $j_days_in_month[$i];
		$j_day_no += $jd;
		$g_day_no = $j_day_no+79;
		$gy = 1600 + 400*intval($g_day_no/146097);
		$g_day_no = $g_day_no % 146097;
		$leap = true;
		if ($g_day_no >= 36525) {
			$g_day_no--;
			$gy += 100*intval($g_day_no/36524);
			$g_day_no = $g_day_no % 36524;
			if ($g_day_no >= 365)
				$g_day_no++;
			else
				$leap = false;
		}
		$gy += 4*intval($g_day_no/1461);
		$g_day_no %= 1461;
		if ($g_day_no >= 366) {
			$leap = false;
			$g_day_no--;
			$gy += intval($g_day_no/365);
			$g_day_no = $g_day_no % 365;
		}
		for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++) {
			$g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
		}
		$gm = $i+1;
		$gd = $g_day_no+1;
		return array($gy, $gm, $gd);
	}

}
