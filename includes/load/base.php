<?php
/**
 * Only loads the base code
 * No sessions, security, userinfo nor output
 */
if (!class_exists('Dragonfly', false)) { exit; }
define('XMLFEED', 1);
require('includes/cmsinit.inc');
