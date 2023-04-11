<?php
if (!defined('INSTALL')) { exit; }

$content = '<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists(\'Dragonfly\', false)) { exit; }

define(\'DF_MODE_INSTALL\', true);

// The CDN path
define(\'DF_STATIC_DOMAIN\', \Dragonfly::$URI_BASE . \'/\');

define(\'DF_HTTP_SSL_REQUIRED\', false);
define(\'DF_HTTP_CACHE_EXPIRE\', 60*60*24*21); # 3 weeks

define(\'DF_SESSION_FREQ_CLEAR_DB\', 600);   # 10 minutes
define(\'DF_SESSION_FREQ_EXPIRE\', 60*60*3); # 3 hours
define(\'DF_SESSION_FREQ_UPDATE_DB\', 30);   # 30 seconds

define(\'DB_TYPE\', \''.$connect['layer'].'\');
define(\'DB_CHARSET\', '.(isset($connect['charset']) ? "'{$connect['charset']}'" : 'NULL').'); // NULL (is default), latin1, utf8, etc.
$dbhost = \''.$connect['host'].'\';
$dbname = \''.$connect['database'].'\';
$dbuname = \''.$connect['username'].'\';
$dbpass = \''.$connect['password'].'\';
$prefix = \''.$connect['prefix'].'\';
$user_prefix = \''.$connect['user_prefix'].'\';

# -- $adminindex -----------------------------------------
# The filename of the admin index page I\'d like to use for my site
#
# If you change this to something other than it\'s default
# value, you must also rename the file called \'includes/load/admin.php\'
# to the new value you assigned to this variable
#
# default: admin
# --------------------------------------------------------
$adminindex = \'admin\';

# -- admin demo mode -------------------------------------
# Alter the following value to activate the administrative
# system demonstration mode, enabling my users to browse
# my administration menu in a read-only environment
#
# true  = enabled
# false = disabled
#
# default: false
# --------------------------------------------------------
define(\'CPGN_DEMO\', false);

# -- debug mode ------------------------------------------
# Alter the following value to activate debug mode, which
# will show debug messages to all users, instead of
# administrators
#
# Warning: Enabling debug mode is NOT recommended for
#          production websites
#
# true  = enabled
# false = disabled
#
# default: false
# --------------------------------------------------------
define(\'CPG_DEBUG\', false);

# --- WARNING --------------------------
# Do not touch anything below this point
# unless you know what you\'re doing
# --------------------------------------

//session_save_path(\'/home/SOMETHING/tmp\');
';
