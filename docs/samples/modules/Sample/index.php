<?php
/**
* CPG Dragonfly CMS
* Copyright © 2004 - 2006 by CPG-Nuke Dev Team, dragonflycms.org
* Released under the GNU GPL version 2 or any later version
* $Id: index.php,v 1.4 2006/10/07 07:54:11 nanocaiordo Exp $
*/

// protect against direct access
if (!defined('CPG_NUKE')) { exit; }

// initiate the page title
$pagetitle .= 'My title';

// include the header in the page generation
require_once('header.php');

// start a new table in which we will show some text
OpenTable();

// show the text on the page
echo 'The content of my module';

// close the table that we have created
CloseTable();

// there isn't any need to include the footer, as the index page handles this already
// require('footer.php');