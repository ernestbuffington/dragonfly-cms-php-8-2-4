<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  https://dragonfly.coders.exchange
  Released under GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
if ( false === \Dragonfly\Output\Js::request() ) {
	\Dragonfly\Net\Http::headersFlush(404, 'File not found');
}
\Dragonfly\Output\Js::flushToClient();
