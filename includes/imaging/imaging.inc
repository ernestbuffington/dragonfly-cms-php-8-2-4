<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/imaging/imaging.inc,v $
  $Revision: 9.8 $
  $Author: nanocaiordo $
  $Date: 2007/08/21 21:18:54 $
**********************************************/

global $MAIN_CFG;
if (!isset($MAIN_CFG['imaging']['type'])) {
    global $CONFIG;
    $MAIN_CFG['imaging']['type'] = $CONFIG['thumb_method'];
    $MAIN_CFG['imaging']['impath'] = $CONFIG['impath'];
    $MAIN_CFG['imaging']['pbmpath'] = $CONFIG['impath'];
}
if (!defined('CAN_MOD_INI')) define('CAN_MOD_INI', !ereg('ini_set', ini_get('disable_functions')));
switch ($MAIN_CFG['imaging']['type']) {
    case 'im'     : require(CORE_PATH.'imaging/im.inc'); break;
    case 'netpbm' : require(CORE_PATH.'imaging/netpbm.inc'); break;
    case 'gd1' :
    case 'gd2' :
        if (extension_loaded('gd')) {
            require(CORE_PATH.'imaging/gd2.inc');
        } else {
            trigger_error('GD image library not available', E_USER_ERROR);
        }
        break;
    default: cpg_error('No correct image handler was specified');
}

    
// http://php.net/imagetypes
// http://php.net/exif-imagetype
if (!defined('IMAGETYPE_GIF')) {
    define('IMAGETYPE_GIF', 1);
    define('IMAGETYPE_JPEG', 2);
    define('IMAGETYPE_PNG', 3);
}

function image_file_to_extension($file, $imagetype) {
    $file = explode('.', $file);
    array_pop($file);
    return preg_replace('/\.jpeg$/', '.jpg', implode('.', $file).image_type_to_extension($imagetype));
}

// http://php.net/image-type-to-mime-type
if (!function_exists('image_type_to_mime_type')) {
function image_type_to_mime_type($imagetype) {
    switch($imagetype) {
        case IMAGETYPE_GIF  : return 'image/gif';
        case IMAGETYPE_JPEG : return 'image/jpeg';
        case IMAGETYPE_PNG  : return 'image/png';
        default             : return false;
    }
}
}

// http://php.net/image_type_to_extension
if(!function_exists('image_type_to_extension')) {
function image_type_to_extension($imagetype, $include_dot=true) {
    $dot = $include_dot ? '.' : '';
    switch($imagetype) {
        case IMAGETYPE_GIF  : return $dot.'gif';
        case IMAGETYPE_JPEG : return $dot.'jpeg';
        case IMAGETYPE_PNG  : return $dot.'png';
        default             : return false;
    }
}
}
