<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/functions/linking.php,v $
  $Revision: 9.26 $
  $Author: nanocaiordo $
  $Date: 2007/11/24 12:23:55 $
**********************************************/

function getlink($url='', $UseLEO=true, $full=false)
{
	global $module_name, $mainindex, $MAIN_CFG, $BASEHREF;
	if (empty($url) || $url[0] == '&') $url = $module_name.$url;
	if ($MAIN_CFG['global']['GoogleTap'] && $UseLEO) {
		$url = ereg_replace('&amp;', '/', $url);
		$url = ereg_replace('&', '/', $url);
		$url = str_replace('?', '/', $url);
		if (ereg('/file=', $url)) {
			$url = ereg_replace('/file=', '/', $url);
		}
		if (ereg('#', $url)) {
			$url = ereg_replace('#', '.html#', $url);
		} else $url .= '.html';
	} else {
		$url = "$mainindex?name=".$url;
	}
	if ($full) $url = $BASEHREF.$url;
	return $url;
}
function adminlink($url='', $full=false)
{
	global $adminindex, $op, $module_name, $MAIN_CFG;
	if (empty($op) && !empty($module_name)) $op = $module_name;
	if (empty($url)) { $url = $op; }
	if ($url[0] == '&') { $url = "$adminindex?op=$op".$url; }
	else { $url = "$adminindex?op=".$url; }
	if ($full) $url = 'http://'.$MAIN_CFG['server']['domain'].$MAIN_CFG['server']['path'].$url;
	return $url;
}

function encode_url($url)
{
	$url = str_replace('&', '%26', $url);
	$url = str_replace('/', '%2F', $url);
	$url = str_replace('.', '%2E', $url);
	return $url;
}

function url_refresh($url='', $time=3)
{
	global $MAIN_CFG;
	$url = ereg_replace('&amp;', '&', $url);
	if (!ereg('://', $url) && $MAIN_CFG['server']['path'] != substr($url, 0, strlen($MAIN_CFG['server']['path']))) {
		$url = $MAIN_CFG['server']['path'].$url;
	}
	header('Refresh: '.intval($time).'; url='.$url);
}

function url_redirect($url='', $redirect=false)
{
	global $mainindex, $SESS, $CPG_SESS, $MAIN_CFG;
	if ($url == '') $url = $mainindex;
	$url = ereg_replace('&amp;', '&', $url);
	$type = preg_match('/IIS|Microsoft|WebSTAR|Xitami/', $_SERVER['SERVER_SOFTWARE']) ? 'Refresh: 0; URL=' : 'Location: ';
	if ($redirect) $CPG_SESS['user']['redirect'] = get_uri();
	if (is_object($SESS)) $SESS->write_close();
	//header("HTTP/1.1 303 REDIRECT");
	if (!ereg('://', $url) && $MAIN_CFG['server']['path'] != substr($url, 0, strlen($MAIN_CFG['server']['path']))) {
		$url = $MAIN_CFG['server']['path'].$url;
	}
	header($type . $url);
	//header("Status: 303");
	//header("Connection: close");
	exit;
}

# Stupid function to create an REQUEST_URI for IIS 5 servers
function get_uri()
{
	if (ereg('IIS', $_SERVER['SERVER_SOFTWARE']) && isset($_SERVER['SCRIPT_NAME'])) {
		$REQUEST_URI = $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['QUERY_STRING'])) {
			$REQUEST_URI .= '?'.$_SERVER['QUERY_STRING'];
		}
	} else {
		$REQUEST_URI = $_SERVER['REQUEST_URI'];
	}
	# firefox encodes url by default but others don't
	$REQUEST_URI = urldecode($REQUEST_URI);
	# encode the url " %22 and <> %3C%3E
	$REQUEST_URI = str_replace('"', '%22', $REQUEST_URI); 
	$REQUEST_URI = preg_replace('#([\x3C\x3E])#e', '"%".bin2hex(\'\\1\')', $REQUEST_URI);
	$REQUEST_URI = substr($REQUEST_URI, 0, strlen($REQUEST_URI)-strlen(stristr($REQUEST_URI, '&CMSSESSID')));
	return $REQUEST_URI;
}

function get_fileinfo($url, $detectAnim=false, $getdata=false, $lastmodified=0)
{
	$rdf = parse_url($url);
	if (!isset($rdf['host'])) return false;
	if (!isset($rdf['path'])) $rdf['path'] = '/';
	if (!isset($rdf['port'])) $rdf['port'] = 80;
	if (!isset($rdf['query'])) $rdf['query'] = '';
	elseif ($rdf['query'] != '') $rdf['query'] = '?'.$rdf['query'];
	$file = array('size'=>0, 'type'=>'', 'date'=>0, 'animation'=>false, 'modified'=>true);
	if ($fp = fsockopen($rdf['host'], $rdf['port'], $errno, $errstr, 4)) {
		fputs($fp, 'GET '.$rdf['path'].$rdf['query']." HTTP/1.0\r\n");
		fputs($fp, 'User-Agent: Dragonfly File Reader ('.getlink('credits', true, true).")\r\n");
		if ($lastmodified > 0) fputs($fp, 'If-Modified-Since: '.date('D, d M Y H:i:s \G\M\T', $lastmodified)."\r\n");
		if (GZIPSUPPORT) fputs($fp, "Accept-Encoding: gzip;q=0.9\r\n");
		fputs($fp, "HOST: $rdf[host]\r\n\r\n");
		$data = rtrim(fgets($fp, 300));
		preg_match('#.* ([0-9]+) (.*)#i', $data, $head);
		// 301 = Moved Permanently, 302 = Found, 307 = Temporary Redirect
		if (($head[1] >= 301 && $head[1] <= 303) || $head[1] == 307) {
			while (!empty($data)) {
				$data = rtrim(fgets($fp, 300)); // read lines
				if (ereg('Location: ', $data)) {
					$new_location = trim(eregi_replace('Location: ', '', $data));
					break;
				}
			}
			$head[2] .= ($head[1]==302) ? ' at' : ' to';
			fputs($fp,"Connection: close\r\n\r\n"); fclose($fp);
			trigger_error("$url $head[2] <b>$new_location</b>", E_USER_WARNING);
			return get_fileinfo($new_location, $detectAnim, $getdata);
		} elseif ($lastmodified > 0 && $head[1] == 304) {
			# file isn't modifed since $lastmodified
			$file['modified'] = false;
			fputs($fp,"Connection: close\r\n\r\n"); fclose($fp);
			return $file;
		} elseif ($head[1] != 200) {
			fputs($fp,"Connection: close\r\n\r\n"); fclose($fp);
			trigger_error($url."<br />$data", E_USER_WARNING);
			return false;
		}
		$file['utf8'] = $GZIP = false;
		// Read all headers
		while (!empty($data)) {
			$data = rtrim(fgets($fp, 300)); // read lines
			if (ereg('Content-Length: ', $data)) {
				$file['size'] = trim(eregi_replace('Content-Length: ', '', $data));
			}
			elseif (ereg('Content-Type: ', $data)) {
				$file['type'] = trim(eregi_replace('Content-Type: ', '', $data));
			}
			elseif (ereg('Last-Modified: ', $data)) {
				$file['date'] = trim(eregi_replace('Last-Modified: ', '', $data));
			}
			if (eregi('Content-Encoding: gzip', $data) || eregi('Content-Encoding: x-gzip', $data)) { $GZIP = true; }
			if (eregi('charset=utf-8', $data)) { $file['utf8'] = true; }
		}

		$data = '';
		if ($getdata || ($detectAnim && ereg('image/', $file['type']))) {
			while(!feof($fp)) {
				$data .= fread($fp, 1024); // read binary
			}
			if ($GZIP) { $data = gzinflate(substr($data,10,-4)); }
			if ($getdata) $file['data'] = $data;
		}
		// Animation detection thanks to PerM
		if ($detectAnim && ereg('image/', $file['type'])) {
//			if (preg_match('/NETSCAPE2.0/', $data))
			$data = preg_split('/\x00[\x00-\xFF]\x00\x2C/', $data); // split GIF frames
			$file['animation'] = (count($data) > 2); // 1 = header, 2 = first/main frame
		}
		fputs($fp,"Connection: close\r\n\r\n");
		fclose($fp);
	} else {
		trigger_error($errstr, E_USER_WARNING);
		return false;
	}
	return $file;
}

function get_rss($url)
{
	trigger_error('The function get_rss() is deprecated in Dragonfly. Please change your call to CPG_RSS::read()', E_USER_NOTICE);
	require_once(CORE_PATH.'classes/rss.php');
	return CPG_RSS::read($url);
}
