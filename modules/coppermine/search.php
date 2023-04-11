<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   http://dragonflycms.org/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

require(__DIR__ . '/include/load.inc');
\Dragonfly\Page::title(S_SEARCH, false);
pageheader(S_SEARCH);
$OUT = \Dragonfly::getKernel()->OUT;
$OUT->gallery_search_action = URL::index('&file=thumbnails');
$OUT->display('coppermine/search');
pagefooter();
