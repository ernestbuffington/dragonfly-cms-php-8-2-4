<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('settings')) { die('Access Denied'); }

abstract class Dragonfly_Admin_Settings
{
	protected static
		$sections = array(_SYSTEM, _MAINTENANCE, _BROWSER_COOKIES, _FOOTER, _BACKENDCONF, _COMMENTSOPT, _CENSOROPTIONS, _EMAILOPTIONS, _DEBUG, _MISCOPT);

	public static function GET()
	{
		$section = isset($_GET['s']) ? intval($_GET['s']) : 0;
		if (is_writeable(CORE_PATH.'config.php')) { self::$sections[11] = 'config.php'; }

		# check for valid section
		if (!isset(self::$sections[$section])) { URL::redirect(URL::admin()); }

		$OUT = \Dragonfly::getKernel()->OUT;
		$MAIN_CFG = \Dragonfly::getKernel()->CFG;

		$OUT->assign_vars(array(
			'S_SECTION' => $section
		));

		if ($section == 0) {
			$timezones = timezone_identifiers_list();
			array_unshift($timezones, 'UTC');
			$themes = \Poodle\TPL::getThemes();
			//if (strpos($_SERVER['SERVER_SOFTWARE'], 'IIS')) $MAIN_CFG->seo->leo = 0;
			$avail_settings = array(
				array(
					'label' => _SITENAME,
					'name'  => 'global[sitename]',
					'value' => $MAIN_CFG->global->sitename,
					'input' => array(
						'type'  => 'text',
						'maxlength' => 255
					)
				),
				array(
					'label' => _SITE_DOMAIN,
					'name'  => 'server[domain]',
					'value' => $MAIN_CFG->server->domain,
					'input' => array(
						'type'  => 'text',
						'maxlength' => 50
					)
				),
				array(
					'label' => _SITE_PATH,
					'name'  => 'server[path]',
					'value' => $MAIN_CFG->server->path,
					'input' => array(
						'type'  => 'text',
						'maxlength' => 100
					)
				),
				array(
					'label' => _SITE_TIMEZONE,
					'tooltip' => _SITE_TIMEZONE_EXPLAIN,
					'name'  => 'global[timezone]',
					'value' => $MAIN_CFG->global->timezone,
					'select' => array(
						'options' => array_combine($timezones, $timezones)
					)
				),
				array(
					'label' => _SITELOGO,
					'name'  => 'global[site_logo]',
					'value' => $MAIN_CFG->global->site_logo,
					'input' => array(
						'type'  => 'text',
						'maxlength' => 255
					)
				),
				array(
					'label' => _SITESLOGAN,
					'name' => 'global[slogan]',
					'value' => $MAIN_CFG->global->slogan,
					'input' => array(
						'type' => 'text',
						'maxlength' => 255
					)
				),
				array(
					'label' => _STARTDATE,
					'name' => 'global[startdate]',
					'value' => $MAIN_CFG->global->startdate,
					'input' => array(
						'type' => 'text',
						'maxlength' => 50
					)
				),
				array(
					'label' => _ADMINEMAIL,
					'name' => 'global[adminmail]',
					'value' => $MAIN_CFG->global->adminmail,
					'input' => array(
						'type' => 'email',
						'maxlength' => 255
					)
				),
				array(
					'label' => _BREADCRUMB,
					'name' => 'global[crumb]',
					'value' => $MAIN_CFG->global->crumb ?: _BC_DELIM,
					'input' => array(
						'type' => 'text',
						'maxlength' => 8
					)
				),
				array(
					'label' => _TOOLTIPS,
					'name' => 'global[admin_help]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->admin_help
					)
				),
				array(
					'label' => _UM_TOGGLE,
					'tooltip' => _UM_EXPLAIN,
					'name' => 'global[update_monitor]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->update_monitor
					)
				),
				array(
					'label' => 'Update database automaticly',
					'tooltip' => 'Try to update database automatically',
					'name' => 'global[db_auto_upgrade]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->db_auto_upgrade
					)
				),
				array(
					'label' => _BLOCK_FRAMES,
					'name' => 'global[block_frames]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->block_frames
					)
				),
				array(
					'label' => _DEFAULTTHEME,
					'name'  => 'global[Default_Theme]',
					'value' => $MAIN_CFG->global->Default_Theme,
					'select' => array(
						'options' => array_combine($themes, $themes)
					)
				),
				array(
					'label' => _ACTIVATE_LEO,
					'name' => 'seo[leo]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->seo->leo
					)
				),
				array(
					'label' => 'LEO url ending',
					'name'  => 'seo[leoend]',
					'value' => $MAIN_CFG->seo->leoend,
					'select' => array(
						'options' => array('.html'=>'.html', '/'=>'/', ''=>'')
					)
				),
				array(
					'label' => 'PHAR '._MODULES,
					'name' => 'global[phar_modules]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->phar_modules
					)
				),
				//array(
				//	'label' => 'Use canonical tag in header',
				//	'name' => 'seo[canonical]',
				//	'value' => 1,
				//	'checkbox' => array(
				//		'checked' => $MAIN_CFG->seo->canonical
				//	)
				//)
			);
		}

		else if ($section == 1) {
			$avail_settings = array(
				array(
					'label' => _ACTIVE,
					'name' => 'global[maintenance]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->maintenance
					)
				),
				array(
					'label' => _MESSAGE,
					'name' => 'global[maintenance_text]',
					'value' => $MAIN_CFG->global->maintenance_text,
					'textarea' => array('class'=>null)
				)
			);
		}

		else if ($section == 2) {
			$avail_settings = array(
				array(
					'label' => _SNAME_AS_COOKIE,
					'name' => 'cookie[server]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->cookie->server
					)
				),
				array(
					'label' => _COOKIE_DOMAIN,
					'name' => 'cookie[domain]',
					'value' => $MAIN_CFG->cookie->domain,
					'input' => array(
						'type' => 'text',
						'maxlength' => 255
					)
				),
				array(
					'label' => _COOKIE_PATH,
					'name' => 'cookie[path]',
					'value' => $MAIN_CFG->cookie->path,
					'input' => array(
						'type' => 'text',
						'maxlength' => 255
					)
				),
			);
		}

		else if ($section == 3) {
			\Dragonfly\Output\Js::add('includes/poodle/javascript/wysiwyg.js');
			\Dragonfly\Output\Css::add('wysiwyg');
			$avail_settings = array(
				array(
					'label' => _FOOTERMSG,
					'name' => 'global[foot1]',
					'value' => $MAIN_CFG->global->foot1,
					'textarea' => array('class'=>'wysiwyg')
				),
				array(
					'label' => _FOOTERLINE2,
					'name' => 'global[foot2]',
					'value' => $MAIN_CFG->global->foot2,
					'textarea' => array('class'=>'wysiwyg')
				),
				array(
					'label' => _FOOTERLINE3,
					'name' => 'global[foot3]',
					'value' => $MAIN_CFG->global->foot3,
					'textarea' => array('class'=>'wysiwyg')
				)
			);
		}

		else if ($section == 4) {
			$avail_settings = array(
				array(
					'label' => _BACKENDTITLE,
					'name' => 'global[backend_title]',
					'value' => $MAIN_CFG->global->backend_title,
					'input' => array(
						'type' => 'text',
						'maxlength' => 100
					)
				),
				array(
					'label' => _BACKENDLANG,
					'name' => 'global[backend_language]',
					'value' => $MAIN_CFG->global->backend_language,
					'input' => array(
						'type' => 'text',
						'maxlength' => 10
					)
				)
			);
		}

		else if ($section == 5) {
			$avail_settings = array(
				array(
					'label' => _COMMENTSLIMIT,
					'name' => 'global[commentlimit]',
					'value' => $MAIN_CFG->global->commentlimit,
					'input' => array(
						'type' => 'number',
						'maxlength' => 5,
						'min' => 128,
						'max' => PHP_INT_MAX
					)
				),
				array(
					'label' => _COMMENTSPOLLS,
					'name' => 'global[pollcomm]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->pollcomm
					)
				),
				array(
					'label' => _COMMENTSMOD,
					'name'  => 'global[moderate]',
					'value' => $MAIN_CFG->global->moderate,
					'select' => array(
						'options' => array(_NOMOD,_MODADMIN,_MODUSERS)
					)
				)
			);
		}

		else if ($section == 6) {
			$avail_settings = array(
				array(
					'label' => _CENSORMODE,
					'name'  => 'global[CensorMode]',
					'value' => $MAIN_CFG->global->CensorMode,
					'select' => array(
						'options' => array(_NOFILTERING,_EXACTMATCH,_MATCHBEG,_MATCHANY)
					)
				),
				array(
					'label' => _CENSORREPLACE,
					'name' => 'global[CensorReplace]',
					'value' => $MAIN_CFG->global->CensorReplace,
					'input' => array(
						'type' => 'text',
						'maxlength' => 10
					)
				)
			);
			$CensorList = explode('|',$MAIN_CFG->global->CensorList);
			sort($CensorList);
			$OUT->censorlist = $CensorList;
		}

		else if ($section == 7) {
			$OUT->L10N->load('poodle/mail');
			$avail_settings = array(
				array(
					'label' => _ALLOW_HTML_EMAIL,
					'name' => 'email[allow_html_email]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->email->allow_html_email
					)
				),
				array(
					'label' => $OUT->L10N['Default bounce email address'],
					'name'  => 'mail[return_path]',
					'value' => $MAIN_CFG->mail->return_path,
					'input' => array(
						'type' => 'email',
						'maxlength' => 254
					),
					'tooltip' => $OUT->L10N['Email_bounce_info']
				),
				array(
					'label' => $OUT->L10N['Default sender email address'],
					'name'  => 'mail[from]',
					'value' => $MAIN_CFG->mail->from,
					'input' => array(
						'type' => 'email',
						'maxlength' => 254
					),
				),
				array(
					'label' => 'Backend mailer',
					'name'  => 'email[backend]',
					'value' => $MAIN_CFG->email->backend,
					'select' => array(
						'options' => array(
							'php' => 'PHP',
							'smtp' => 'SMTP',
							'sendmail' => 'Sendmail',
							'sendmail_bs' => 'Sendmail Server',
							'qmail' => 'Qmail')
					)
				),
				array(
					'label' => _SMTP_HOST,
					'name' => 'email[smtphost]',
					'value' => $MAIN_CFG->email->smtphost,
					'input' => array(
						'type' => 'text',
						'maxlength' => 100
					)
				),
				array(
					'label' => "SMTP {$OUT->L10N['Port']}",
					'name' => 'email[smtp_port]',
					'value' => $MAIN_CFG->email->smtp_port,
					'input' => array(
						'type' => 'number',
						'maxlength' => 5,
						'min' => 0,
						'max' => 65535
					)
				),
				array(
					'label' => 'SMTP Protocol',
					'name'  => 'email[smtp_protocol]',
					'value' => $MAIN_CFG->email->smtp_protocol,
					'select' => array(
						'options' => array('tls' => 'TLS', 'ssl' => 'SSL', '' => 'Normal')
					)
				),
				array(
					'label' => 'SMTP Login Type',
					'name'  => 'email[smtp_auth]',
					'value' => $MAIN_CFG->email->smtp_auth,
					'select' => array(
						'options' => array('' => 'auto-detect', 'PLAIN'=>'PLAIN', 'LOGIN'=>'LOGIN', 'CRAM-MD5'=>'CRAM-MD5')
					)
				),
				array(
					'label' => "SMTP {$OUT->L10N['Username']}",
					'name' => 'email[smtp_uname]',
					'value' => $MAIN_CFG->email->smtp_uname,
					'input' => array(
						'type' => 'text',
						'maxlength' => 100
					)
				),
				array(
					'label' => "SMTP {$OUT->L10N['Password']}",
					'name' => 'email[smtp_pass]',
					'value' => $MAIN_CFG->email->smtp_pass,
					'input' => array(
						'type' => 'password',
						'maxlength' => 100
					)
				),
			);
		}

		else if ($section == 8) {
			$error_level = $MAIN_CFG->debug->error_level;
			$log_level = $MAIN_CFG->debug->log_level;
			$avail_settings = array(
				array(
					'label' => 'PHP Notices',
					'debug' => static::debugOptions(E_NOTICE)
				),
				array(
					'label' => 'PHP Warnings',
					'debug' => static::debugOptions(E_WARNING)
				),
				array(
					'label' => 'PHP Strict',
					'debug' => static::debugOptions(E_STRICT)
				),
				array(
					'label' => 'PHP Recoverable Error',
					'debug' => static::debugOptions(E_RECOVERABLE_ERROR)
				),
				array(
					'label' => 'PHP Deprecated',
					'debug' => static::debugOptions(E_DEPRECATED)
				),
				array(
					'label' => 'CMS Notices',
					'debug' => static::debugOptions(E_USER_NOTICE)
				),
				array(
					'label' => 'CMS Warnings',
					'debug' => static::debugOptions(E_USER_WARNING)
				),
				array(
					'label' => 'CMS Deprecated',
					'debug' => static::debugOptions(E_USER_DEPRECATED)
				),
				array(
					'label' => 'CMS Errors',
					'debug' => static::debugOptions(E_USER_ERROR)
				),
				array(
					'label' => 'Database Queries Display',
					'name' => 'debug[database]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->debug->database
					)
				),
				array(
					'label' => "Show session data on error pages",
					'name' => 'debug[session]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->debug->session
					)
				)
			);
		}

		else if ($section == 9) {
			\Dragonfly\Output\Js::inline('
			function adminGraphSettings(e) {
				var el, i=-1, elements=Poodle.$("settings").$T("input");
				while (el=elements[++i]) {
					if ("admingraphic[]" == el.name) {
						if (1 == el.value) continue;
						if (!e) {
							el.bind("click", adminGraphSettings);
						} else if ("click" == e.type) {
							el.initValue(e.target.value == el.value);
						}
					}
				}
			}
			Poodle.onDOMReady(function(event){adminGraphSettings(event);});
			');
			$img_options = array(''=>'[Auto select]');
			foreach (\Poodle\Image::getHandlers() as $name => $info) {
				$img_options[$name] = $info['name'];
			}

			$avail_settings = array(
				array(
					'label' => _ACTBANNERS,
					'name' => 'global[banners]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->banners
					)
				),
				array(
					'label' => _ACTIVATEHTTPREF,
					'name' => 'global[httpref]',
					'value' => 1,
					'checkbox' => array(
						'checked' => $MAIN_CFG->global->httpref
					)
				),
				array(
					'label' => _MAXREF,
					'name' => 'global[httprefmax]',
					'value' => $MAIN_CFG->global->httprefmax,
					'input' => array(
						'type' => 'number',
						'maxlength' => 5,
						'min' => 0,
						'max' => PHP_INT_MAX
					)
				),
				array(
					'label' => _ITEMSTOP,
					'name' => 'global[top]',
					'value' => $MAIN_CFG->global->top,
					'input' => array(
						'type' => 'number',
						'maxlength' => 5,
						'min' => 5,
						'max' => 30
					)
				),
				array(
					'label' => _GRAPHICOPT,
					'S_TYPE' => '
						<input type="radio" name="admingraphic[]" value="'.\Dragonfly\Page\Menu\Admin::CSS.'"
							'.(($MAIN_CFG->global->admingraphic & \Dragonfly\Page\Menu\Admin::CSS) ? 'checked="checked"' : '').'/>
							CSS menu<br />
						<input type="radio" name="admingraphic[]" value="'.\Dragonfly\Page\Menu\Admin::TABS.'"
							'.(($MAIN_CFG->global->admingraphic & \Dragonfly\Page\Menu\Admin::TABS) ? 'checked="checked"' : '').'/>
							Tabbed menu<br />
						<input type="checkbox" name="admingraphic[]" value="'.\Dragonfly\Page\Menu\Admin::BLOCK.'"
							'.(($MAIN_CFG->global->admingraphic & \Dragonfly\Page\Menu\Admin::BLOCK) ? 'checked="checked"' : '').'/>
							'._SIDEBLOCK.'<br />
						<input type="checkbox" name="admingraphic[]" value="'.\Dragonfly\Page\Menu\Admin::GRAPH.'"
							'.(($MAIN_CFG->global->admingraphic & \Dragonfly\Page\Menu\Admin::GRAPH) ? 'checked="checked"' : '').'/>
							'._GRAPHICAL.'<br />
							'
				),
				array(
					'label' => 'Image Processing and Generation',
					'name'  => 'image[handler]',
					'value' => $MAIN_CFG->image->handler,
					'select' => array(
						'options' => $img_options
					)
				)
			);
		}

		else if ($section == 11) {
			$avail_settings = array(
				array(
					'label' => 'Debugging mode',
					'name' => 'config_debug',
					'value' => 1,
					'checkbox' => array(
						'checked' => CPG_DEBUG
					)
				),
				array(
					'label' => 'Activate installer mode',
					'name' => 'config_mode_install',
					'value' => 1,
					'checkbox' => array(
						'checked' => DF_MODE_INSTALL
					)
				),
				array(
					'label' => 'Require HTTP SSL',
					'name' => 'config_ssl_required',
					'value' => 1,
					'checkbox' => array(
						'checked' => DF_HTTP_SSL_REQUIRED
					)
				),
				array(
					'label' => 'Static images domain',
					'name' => 'config_static_domain',
					'value' => DF_STATIC_DOMAIN,
					'input' => array(
						'type' => 'text',
						'maxlength' => 255
					)
				)
			);
		}

		$sc = count(self::$sections);
		foreach (self::$sections as $key => $value) {
			$OUT->assign_block_vars('menu', array(
				'uri' => ($section != $key) ? URL::admin('&s='.$key) : null,
				'label' => $value,
			));
			if ($key == $section) {
				foreach ($avail_settings as $settings) {
					$OUT->assign_block_vars('settings', $settings);
				}
			}
		}

		\Dragonfly\Page::title(_SITECONFIG, false);
		\Dragonfly\Page::title(self::$sections[$section]);
		$OUT->display('admin/settings');
	}

	public static function POST()
	{
		$section = isset($_GET['s']) ? intval($_GET['s']) : 0;
		if (extension_loaded('gd')) { self::$sections[] = _SECURITYCODE; }
		if (is_writeable(CORE_PATH.'config.php')) { self::$sections[11] = 'config.php'; }

		# check for valid section
		if (!isset(self::$sections[$section])) { URL::redirect(URL::admin()); }

		if (\Dragonfly\Output\Captcha::validate($_POST)) {
			$MAIN_CFG = \Dragonfly::getKernel()->CFG;

			if (11 == $section) {
				$fp = fopen(CORE_PATH.'config.php', 'rb');
				$config = fread($fp, filesize(CORE_PATH.'config.php'));
				fclose($fp);
				$config = preg_replace('#define\(\'DF_MODE_INSTALL\', [a-z]+\)#s', 'define(\'DF_MODE_INSTALL\', '.($_POST['config_mode_install'] ? 'true' : 'false').')', $config);
				$config = preg_replace('#define\(\'DF_STATIC_DOMAIN\', \'.*?\'\)#s', 'define(\'DF_STATIC_DOMAIN\', \''.Fix_Quotes($_POST['config_static_domain']).'\')', $config);
				$config = preg_replace('#define\(\'DF_HTTP_SSL_REQUIRED\', [a-z]+\)#s', 'define(\'DF_HTTP_SSL_REQUIRED\', '.($_POST['config_ssl_required'] ? 'true' : 'false').')', $config);
				$config = preg_replace('#define\(\'CPG_DEBUG\', [a-z]+\)#s', 'define(\'CPG_DEBUG\', '.($_POST['config_debug'] ? 'true' : 'false').')', $config);
				$config = preg_replace('#\$sitekey = \'.*?\';#s', '', $config);
				$written = false;
				if ($fp = fopen(CORE_PATH.'config.php', 'wb')) {
					$written = fwrite($fp, $config);
					fclose($fp);
				}
				if (!$written) { cpg_error('Failed modifying file.'); }
				URL::redirect(URL::admin('&s='.$section));
			}

			$sections = array(
				array('global' => array('sitename', 'timezone', 'dateformat', 'site_logo', 'slogan', 'startdate', 'adminmail', 'crumb', 'admin_help', 'update_monitor', 'db_auto_upgrade', 'GoogleTap', 'block_frames', 'Default_Theme', 'phar_modules'),
					  'server' => array('timezone', 'domain', 'path'),
						 'seo' => array('leo', 'leoend')),
				array('global' => array('maintenance', 'maintenance_text')),
				array('cookie' => array('server', 'domain', 'path')),
				array('global' => array('foot1', 'foot2', 'foot3')),
				array('global' => array('backend_title', 'backend_language')),
				array('global' => array('commentlimit', 'pollcomm', 'moderate')),
				array('global' => array('CensorMode', 'CensorReplace')),
				array('email'  => array('allow_html_email', 'backend', 'smtphost', 'smtp_auth', 'smtp_uname', 'smtp_pass', 'smtp_port', 'smtp_protocol'),
					   'mail'  => array('return_path', 'from')),
				array('debug'  => array('database', 'session')),
				array('global' => array('banners', 'httpref', 'httprefmax', 'top'),
					  'image'  => array('handler'))
			);

			if (isset($sections[$section])) {
				foreach ($sections[$section] as $area => $keys) {
					foreach ($keys as $key) {
						if (isset($_POST[$area][$key])) {
							$value = trim($_POST[$area][$key]);
							if ($key == 'path') {
								if (substr($value, -1) != '/') $value .= '/';
								if ($value[0] != '/') $value = '/'.$value;
							}
							else if ($key == 'Default_Theme') { $_SESSION['CPG_SESS']['theme'] = $value; }
							$MAIN_CFG->set($area, $key, $value);
						} else {
							// Handle checkbox
							$MAIN_CFG->set($area, $key, $_POST->bool($area, $key));
						}
					}
				}
			}

			if (6 == $section) {
				$list = array();
				foreach ($_POST['global']['CensorList'] as $word) {
					$word = mb_strtolower(trim($word));
					if ($word) { $list[$word] = $word; }
				}
				natcasesort($list);
				$MAIN_CFG->set('global', 'CensorList', implode('|',$list));
			}

			else if (8 == $section) {
				$MAIN_CFG->set('debug', 'error_level', isset($_POST['error_level']) ? array_sum($_POST['error_level']) : 0);
				$MAIN_CFG->set('debug', 'log_level', isset($_POST['log_level']) ? array_sum($_POST['log_level']) : 0);
			}

			else if (9 == $section) {
				$admingraphic = 0;
				if (isset($_POST['admingraphic'])) {
					foreach ($_POST['admingraphic'] as $val) { $admingraphic |= intval($val); }
				}
				if ($admingraphic < 1) { $admingraphic = \Dragonfly\Page\Menu\Admin::GRAPH & \Dragonfly\Page\Menu\Admin::BLOCK; }
				$MAIN_CFG->set('global', 'admingraphic', $admingraphic);
			}
		}
		URL::redirect(URL::admin('&s='.$section));
	}

	private static function debugOptions($value)
	{
		$CFG = \Dragonfly::getKernel()->CFG->debug;
		return array(
			'value' => $value,
			'display' => !!($CFG->error_level & $value),
			'log' => !!($CFG->log_level & $value),
		);
	}

}

Dragonfly_Admin_Settings::{$_SERVER['REQUEST_METHOD']}();
