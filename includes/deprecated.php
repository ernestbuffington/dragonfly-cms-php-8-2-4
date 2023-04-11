<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

// as of v10
function GraphicAdmin() {
	trigger_deprecated('No longer needed');
}

function define_nobody($file) {
	trigger_deprecated('Automatically handled using PHP\'s umask()');
}

function filesize_to_human($size, $precision=2) {
	trigger_deprecated('Use Dragonfly::getKernel()->L10N->filesizeToHuman($size, $precision)');
	return Dragonfly::getKernel()->L10N->filesizeToHuman($size, $precision);
}

function get_microtime() {
	trigger_deprecated('Use microtime(true)');
	return microtime(true);
}

function file_write($filename, &$content, $mode='wb') {
	trigger_deprecated('Use \Poodle\File::putContents($filename, $content)');
	return \Poodle\File::putContents($filename, $content);
}

function get_theme() {
	trigger_deprecated();
	return \Dragonfly::getKernel()->OUT->theme;
}

function generate_secimg($chars=6) {
//	trigger_deprecated('Instead of generate_secimg() use the new Dragonfly\Output\Captcha functionality');
	$id = md5(random_bytes(32));
	$time = explode(' ', microtime());
	$value = substr(dechex($time[0]*3581692740), 0, $chars);
	$_SESSION['DF_CAPTCHA'][$id] = array($value, time());
	$_SESSION['DF_CAPTCHA'] = array_slice($_SESSION['DF_CAPTCHA'], -4, 4, true);
	return '<img class="captcha" src="'.htmlspecialchars(URL::load('captcha&'.$id)).'" alt="'._SECURITYCODE.'" title="'._SECURITYCODE.'" />
	<input type="hidden" name="gfxid" value="'.$id.'" />';
}

function validate_secimg($chars=6) {
//	trigger_deprecated('Instead of validate_secimg() use the new Dragonfly\Output\Captcha functionality');
	if (!isset($_POST['gfx_check']) || !isset($_POST['gfxid'])) { return false; }
	return \Dragonfly\Output\Captcha::validate(array($_POST['gfxid'] => $_POST['gfx_check']));
}

function viewbanner() {
	trigger_deprecated('Use \Dragonfly\Modules\Our_Sponsors\Banner::getRandom()');
	return \Dragonfly\Modules\Our_Sponsors\Banner::getRandom();
}

# Time Formatting
function formatDateTime($time, $format) {
	trigger_deprecated("use Dragonfly::getKernel()->L10N->strftime('{$format}', {$time})");
	return Dragonfly::getKernel()->L10N->strftime($format, $time);
}

# IP Handling
function ip2long32($ip) {
	trigger_deprecated('Use \Dragonfly\Net::ip2long($ip)');
	return \Dragonfly\Net::ip2long($ip);
}
function decode_ip($ip) {
	trigger_deprecated();
	return \Dragonfly\Net::decode_ip($ip);
}

# Caching
function cache_save_array($name, $module_name='config', $array=false) {
	trigger_deprecated("Use Dragonfly::getKernel()->CACHE->set('modules/{$module_name}/{$name}', \$array)");
	Dragonfly::getKernel()->CACHE->set("modules/{$module_name}/{$name}", $array);
}
function cache_load_array($name, $module_name='config', $global=true) {
	trigger_deprecated("Use Dragonfly::getKernel()->CACHE->get('modules/{$module_name}/{$name}')");
	$data = Dragonfly::getKernel()->CACHE->get("modules/{$module_name}/{$name}");
	if ($global) { $GLOBALS[$name] = $data; }
	return $data;
}
function cache_delete_array($name, $module_name='config') {
	trigger_deprecated("Use Dragonfly::getKernel()->CACHE->delete('modules/{$module_name}/{$name}')");
	Dragonfly::getKernel()->CACHE->delete("modules/{$module_name}/{$name}");
}

# linking.php
function getlink($url='', $UseLEO=true, $full=false)
{
	trigger_deprecated('Change to URL::index($url, $UseLEO, $full)');
	return URL::index($url, $UseLEO, $full);
}

function adminlink($url='', $full=false)
{
	trigger_deprecated('Change to URL::admin($url, $full)');
	return URL::admin($url, $full);
}

function encode_url($url)
{
	trigger_deprecated('Change to URL::encode($url)');
	return URL::encode($url);
}

function url_refresh($url='', $time=3)
{
	trigger_deprecated('Change to URL::refresh($url, $time)');
	URL::refresh($url, $time);
}

function url_redirect($url='', $redirect=false)
{
	trigger_deprecated('Change to URL::redirect($url, $redirect)');
	URL::redirect($url, $redirect);
}

function get_uri()
{
	trigger_deprecated('Change to $_SERVER[\'REQUEST_URI\']');
	return $_SERVER['REQUEST_URI'];
}

function get_fileinfo($url, $detectAnim=false, $getdata=false, $lastmodified=0)
{
	trigger_deprecated('\Poodle\HTTP\URLInfo::get()');
	return \Poodle\HTTP\URLInfo::get($url, $detectAnim, $getdata, $lastmodified);
}

function get_rss($url)
{
	trigger_deprecated('Change to \Dragonfly\RSS::read()', E_USER_WARNING);
	return \Dragonfly\RSS::read($url);
}

# language.php
function get_langcode($thislang)
{
	trigger_deprecated("use Dragonfly::getKernel()->L10N->lng.");
	return Dragonfly::getKernel()->L10N->lng;
}

function get_lang($module)
{
	trigger_deprecated("use Dragonfly::getKernel()->L10N->load().");
	Dragonfly::getKernel()->L10N->load(strtr($module, array('forums'=>'Forums', 'your_account'=>'Your_Account')));
}

function lang_selectbox($current, $fieldname='alanguage', $all=true, $return_list=false)
{
	$L10N = \Dragonfly::getKernel()->L10N;
	if ($return_list) {
		trigger_deprecated("use Dragonfly::getKernel()->L10N->getActiveList().");
		$languages = array();
		foreach ($L10N->getActiveList() as $lng) {
			$languages[] = $lng['value'];
		}
		return $languages;
	}

	trigger_deprecated("use: tal:repeat=\"lng L10N/getActiveList\" tal:attributes=\"value lng/value; selected php:\${lng/value}==\${CURRENT_VALUE}\" tal:content=\"lng/label\"");
	$content = '<select name="'.$fieldname.'" id="'.$fieldname.'">';
	if ($all) {
		$content .= '<option value=""'.($current ? '' : ' selected="selected"').'>'._ALL."</option>\n";
	}
	foreach ($L10N->getActiveList() as $lng)
	{
		$content .= '<option value="'.$lng['value'].'"'.(($lng['value'] == $current) ? ' selected="selected"' : '').'>'.$lng['label']."</option>\n";
	}
	return $content.'</select>';
}

# display.php
# infobox.js
function show_tooltip($tip)
{
	trigger_deprecated();
	global $MAIN_CFG;
	if ($MAIN_CFG->global->admin_help) {
		\Dragonfly\Output\Js::add('includes/javascript/infobox.js');
	}
	return $MAIN_CFG->global->admin_help ? ' onmouseover="tip(\''.$tip.'\')" onmouseout="untip()"' : '';
}

function show_img_tooltip($tip)
{
	trigger_deprecated();
	global $MAIN_CFG;
	if ($MAIN_CFG->global->admin_help) {
		\Dragonfly\Output\Js::add('includes/javascript/infobox.js');
	}
	return $MAIN_CFG->global->admin_help ? ' <img src="'.DF_STATIC_DOMAIN.'images/icons/16x16/info.png" alt="" onmouseover="tip(\''.$tip.'\')" onmouseout="untip()" style="cursor: help;" />' : '';
}

class Dragonfly_Output_Js extends \Dragonfly\Output\Js {}
class Dragonfly_Output_Css extends \Dragonfly\Output\Css {}
