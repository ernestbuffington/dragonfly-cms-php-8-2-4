<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  NOTES:
  jalali_to_gregorian() and gregorian_to_jalali() are
  Copyright (C) 2000 under GNU GPL by Roozbeh Pournader and Mohammad Toossi
  http://iranphp.net/
  Sallar Kaboli hooks his copyright to it in 2004 which is incorrect
  They are slightly modified for a better ease of use
**********************************************/

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

abstract class L10NTime
{

	# Convert the GMT time to local time
	public static function tolocal($time)
	{
		trigger_deprecated();
		return $time + date('Z',$time);
	}

	# Convert 01-01-2005 12:00:00 GMT to 01-01-2005 12:00:00 Local Time
	# Convert the local time to GMT
	public static function toGMT($time)
	{
		trigger_deprecated();
		return $time - date('Z',$time);
	}

	public static function strftime($format, $time=null)
	{
		trigger_deprecated("use Dragonfly::getKernel()->L10N->strftime('{$format}', {$time})");
		return Dragonfly::getKernel()->L10N->strftime($format, $time);
	}

	public static function date($format, $time=null)
	{
		trigger_deprecated("use Dragonfly::getKernel()->L10N->date('{$format}', {$time})");
		return Dragonfly::getKernel()->L10N->date($format, $time);
	}

	public static function strtotime($time, $now=0)
	{
		trigger_deprecated();
		return strtotime($time, $now<1?time():$now);
	}

	# Check if the local time is inside DST depending on region
	public static function in_dst($localtime)
	{
		trigger_deprecated();
		return 0 < date('I',$localtime);
	}

	# Convert the local time to DST depending on region
	public static function get_dst_time($localtime)
	{
		trigger_deprecated();
		return $localtime;
	}
/*
  Start internal functions mostly not used outside this class
*/
	public static function get_dst_switch($localtime, $data)
	{
		trigger_deprecated();
		return 0;
	}

	public static function is_dst($localtime, $start, $end)
	{
		trigger_deprecated();
		return 0 < date('I',$localtime);
	}

	public static function gregorian_to_jalali($g_y, $g_m, $g_d)
	{
		trigger_deprecated();
		$date = new \Poodle\DateTime("{$g_y}-{$g_m}-{$g_d}");
		return explode('-', $date->formatSolarHijri('Y-m-d'));
	}

	public static function jalali_to_gregorian($j_y, $j_m, $j_d)
	{
		trigger_deprecated();
		$date = new \Poodle\DateTime();
		$date->setSolarHijriDate($j_y, $j_m, $j_d);
		return explode('-', $date->format('Y-m-d'));
	}

}
