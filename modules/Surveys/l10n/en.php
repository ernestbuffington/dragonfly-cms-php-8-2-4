<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004-2006 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

Encoding test: n-array summation ∑ latin ae w/ acute ǽ
*******************************************************/
if (!defined('CPG_NUKE')) { exit; }

$LNG = array(
'Publish' => 'Publish',
'_POLLSADMIN' => 'Polls/Surveys',
);

define('_COMMENT','comment');
define('_NOCOMMENTS','No Comments');
define('_SCORE','Score:');
define('_NOANONCOMMENTS','No Comments Allowed for Anonymous, please <a rel="nofollow" href="'.htmlspecialchars(\Dragonfly\Identity::getRegisterURL()).'">register</a>');
define('_UCOMMENT','Comment');
define('_SURVEYCOM','Survey Comment Post');
define('_SURVEYCOMPRE','Survey Comment Post Preview');
define('_VOTING','Voting Booth');
define('_OTHERPOLLS','Other Polls');
define('_ATTACHEDTOARTICLE','- Attached to article:');
define('_SURVEYSATTACHED','Surveys Attached to Articles');
