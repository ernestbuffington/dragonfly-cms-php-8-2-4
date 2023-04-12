<?php
/*********************************************
	CPG Dragonfly™ CMS
	********************************************
	Copyright © 2004 - 2007 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$Source: /cvs/html/admin/modules/cache/eaccelerator.php,v $
	$Revision: 9.3 $
	$Author: nanocaiordo $
	$Date: 2007/04/23 10:33:58 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }

$web_error = '';

function eaccelerator_error($str) {
	global $web_error;
	$web_error = "ERROR: $str";
}

function eaccelerator_encode_file($src, $out, $f, $c) {
	if (empty($out)) { echo "\n// $src\n"; }
	$prefix = '';
	$cmp = eaccelerator_encode($src, $prefix);
	if (empty($cmp)) {
		eaccelerator_error("Can't compile file \"$src\"");
		if ($f) {
			if ($c && !empty($out)) {
			global $web_error;
				if (!empty($web_error)) {
					echo "<font color=\"#ff0000\">$web_error</font><br />\n"; flush();
					$web_error = '';
				}
				eaccelerator_copy_file($src, $out, $f);
			}
		}
	} else {
		$cmp = $prefix.'<?php if (!is_callable("eaccelerator_load") && !@dl((PHP_OS=="WINNT"||PHP_OS=="WIN32")?"eloader.dll":"eloader.so")) { die("This PHP script has been encoded with eAccelerator, to run it you must install <a href=\"http://eaccelerator.sourceforge.net/\">eAccelerator or eLoader</a>");} return eaccelerator_load(\''.$cmp."');?>\n";
		if (!empty($out)) {
			if (!$f && file_exists($out)) {
				eaccelerator_error("Can't create output file \"$out\" (already exists)");
			} else {
				$file = fopen($out,'wb');
				if (!$file) {
					eaccelerator_error("Can't open output file \"$out\"");
				} else {
					fwrite($file,$cmp);
					unset($cmp);
					fclose($file);
					$stat = stat($src);
					chmod($out, $stat['mode']);
					echo "<font color=\"#00aa00\">Encoding: \"$src\" -> \"$out\"</font><br />\n";
				}
			}
		} else {
			echo '<pre>'.htmlprepare($cmp)."</pre>\n";
			unset($cmp);
		}
	}
}

function eaccelerator_mkdir($dir, $f) {
	if (!empty($dir) && !mkdir($dir, (PHP_AS_NOBODY ? 0777 : 0755)) && !$f) {
		$error = "Can't create destination directory \"$dir\"";
		if (file_exists($dir)) { $error .= ' (already exists)'; }
		eaccelerator_error($error);
		return 0;
	}
	return 1;
}

function eaccelerator_copy_dir($src, $dir, $f) {
	$stat = stat($src);
	$old = umask(0);
	$ret = mmcache_mkdir($dir, $f);
	umask($old);
	return $ret;
}

function eaccelerator_copy_file($src, $out, $f) {
	$i = fopen($src, 'rb');
	if (!$i) {
		return eaccelerator_error("Can't open file \"$src\" for copying");
	}
	if (!$f && file_exists($out)) {
		eaccelerator_error("Can't create output file \"$out\" (already exists)");
	} else {
		$o = fopen($out, 'wb');
		if (!$o) {
			eaccelerator_error("Can't copy file into \"$out\"");
			return;
		}
		while ($tmp = fread($i, 1024*32)) {
			fwrite($o, $tmp);
		}
		fclose($i);
		fclose($o);
		$stat = stat($src);
		chmod($out, $stat['mode']);
		echo "<font color=\"#00aa00\">Copying: \"$src\" -> \"$out\"</font><br />\n";
	}
}

function eaccelerator_copy_link($src, $out, $f) {
	$link = readlink($src);
	if (symlink($link, $out)) { return; }
	if ($f && file_exists($out)) {
		unlink($out);
		eaccelerator_copy_link($src, $out, false);
	} else if ($f && is_array(lstat($out))) {
		unlink($out);
		eaccelerator_copy_link($src, $out, false);
	} else {
		return eaccelerator_copy_link("Can't create symlink \"$out\" -> \"$link\"");
	}
}

function eaccelerator_encode_dir($src, $out, $s, $r, $l, $c, $f) {
	if (!($dir = opendir($src))) {
		return eaccelerator_error("Can't open source directory \"$src\"");
	}
	while (($file = readdir($dir)) !== false) {
		if ($file == '.' || $file == '..') continue;
		$i = "$src/$file";
		$o = empty($out)?$out:"$out/$file";
		if (is_link($i)) {
			if ($c && !empty($o)) {
				eaccelerator_copy_link($i, $o, $f);
				global $web_error;
				if (!empty($web_error)) {
					echo "<font color=\"#ff0000\">$web_error</font><br />\n"; flush();
					$web_error = '';
				}
				continue;
			} else if (!$l) {
				continue;
			}
		}
		if (is_dir($i)) {
			if ($r && eaccelerator_copy_dir($i, $o, $f)) {
				eaccelerator_encode_dir($i, $o, $s, $r, $l, $c, $f);
			}
		} else if (is_file($i)) {
			if (empty($s)) {
				eaccelerator_encode_file($i, $o, $f, $c);
			} else if (is_string($s)) {
				if (preg_match('/'.preg_quote(".$s").'$/i', $file)) {
					eaccelerator_encode_file($i, $o, $f, $c);
				} else if (!empty($o) && $c) {
					eaccelerator_copy_file($i, $o, $f);
				}
			} else if (is_array($s)) {
				$encoded = false;
				foreach($s as $z) {
					if (preg_match('/'.preg_quote(".$z").'$/i', $file)) {
						eaccelerator_encode_file($i, $o, $f, $c);
						$encoded = true;
						break;
					}
				}
				if (!$encoded && !empty($o) && $c) {
					eaccelerator_copy_file($i, $o, $f);
				}
			}
		}
		global $web_error;
		if (!empty($web_error)) {
			echo "<font color=\"#ff0000\">$web_error</font><br />\n"; flush();
			$web_error = '';
		}
	}
	closedir($dir);
}

set_time_limit(0);

$error = $source = $target = '';
$s = $suffixies = 'php';
if (isset($_POST['submit'])) {
	if (isset($_POST['source'])) {
		$source = $_POST['source'];
	}
	if (isset($_POST['target'])) {
		$target = $_POST['target'];
	}
	if (isset($_POST['suffixies'])) {
		$suffixies = $_POST['suffixies'];
		$s = (strpos($suffixies,',') !== false) ? explode(',',$suffixies) : $suffixies;
	}
	$all = $_POST['all'] ?? false;
	$links = $_POST['links'] ?? false;
	$recursive = $_POST['recursive'] ?? false;
	$copy = $_POST['copy'] ?? false;
	$force = $_POST['force'] ?? false;
	if (empty($source)) {
		$error = 'ERROR: Source is not specified!';
	} else if (!file_exists($source)) {
		$error = "ERROR: Source file \"$source\" doesn't exist.\n";
	} else {
		if (is_dir($source)) {
			if (eaccelerator_mkdir($target, $force, 1)) {
				if ($all) { $s = ''; }
				eaccelerator_encode_dir($source, $target, $s, $recursive, $links, $copy, $force, 1);
			}
		} else {
			eaccelerator_encode_file($source, $target, $force, $copy, 1);
		}
		if (empty($web_error)) {
			echo '<br /><b>DONE</b>';
			return;
		} else {
			$error = $web_error;
		}
	}
}
echo '
<h1 align="center">eAccelerator Encoder '.EACCELERATOR_VERSION.'</h1>
<h3 align="center"><font color="#ff0000">'.$error.'</font></h3>
<form method="post" action="'.adminlink().'" enctype="multipart/form-data" accept-charset="utf-8">
<input type="hidden" name="mode" value="Encode" />
<table border="0" cellpadding="3" cellspacing="1" width="600" bgcolor="#000000" align="center">
<tr valign="baseline" bgcolor="#cccccc"><td width="50%" bgcolor="#ccccff"><b>Source file or directory name:</b></td><td width="50%"><input type="text" name="source" size="32" value="'.$source.'" style="width:100%" /></td></tr>
<tr valign="baseline" bgcolor="#cccccc"><td width="50%" bgcolor="#ccccff"><b>Target file or directory name:</b></td><td width="50%"><input type="text" name="target" size="32" value="'.$target.'" style="width:100%" /></td></tr>
<tr valign="baseline" bgcolor="#cccccc"><td width="50%" bgcolor="#ccccff"><b>PHP suffixies <small>(comma separated list)</small>:</b></td><td width="50%"><input type="text" name="suffixies" size="32" value="'.$suffixies.'" style="width:100%" /></td></tr>
<tr valign="baseline" bgcolor="#cccccc"><td width="50%" bgcolor="#ccccff"><b>Options:</b></td><td width="50%">
	<input type="checkbox" id="all" name="all"'.(empty($all)?'':' checked="checked"').' /> - <label for="all">encode all files</label><br />
	<input type="checkbox" id="links" name="links"'.(empty($links)?'':' checked="checked"').' /> - <label for="links">follow symbolic links</label><br />
	<input type="checkbox" id="recursive" name="recursive"'.(empty($recursive)?'':' checked="checked"').' /> - <label for="recursive">encode directories recursively</label><br />
	<input type="checkbox" id="copy" name="copy"'.(empty($copy)?'':' checked="checked"').' /> - <label for="copy">copy files those shouldn\'t be encoded</label><br />
	<input type="checkbox" id="force" name="force"'.(empty($force)?'':' checked="checked"').' /> - <label for="force">overwrite existing files</label><br />
</td></tr>
<tr><td colspan="2" align="center" bgcolor="#cccccc"><input class="button" type="submit" name="submit" value="OK" /></td></tr>
</table>
</form>';
