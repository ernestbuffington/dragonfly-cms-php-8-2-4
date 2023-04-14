<?php 
/***************************************************************************  
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify       
   it under the terms of the GNU General Public License as published by       
   the Free Software Foundation; either version 2 of the License, or          
   (at your option) any later version.                                        
  **************************************************************************  
  Last modification notes:
  $Source: /public_html/includes/coppermine/iptc.inc,v $
  $Revision: 9.1 $
  $Author: trevor $
  $Date: 2005/02/18 00:16:16 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
function get_IPTC($filename)
{
    $size = GetImageSize ($filename, &$info);
    if (isset($info["APP13"])) {
        $iptc = iptcparse($info["APP13"]);
        if (is_array($iptc)) {
            $IPTC_data = array("Title" => $iptc["2#005"][0], // Max 65 octets, non-repeatable, alphanumeric
                "Urgency" => $iptc["2#010"][0], // Max 1 octet, non-repeatable, numeric, 1 - High, 8 - Low
                "Category" => $iptc["2#015"][0], // Max 3 octets, non-repeatable, alpha
                "SubCategories" => $iptc["2#020"], // Max 32 octets, repeatable, alphanumeric
                "Keywords" => $iptc["2#025"], // Max 64 octets, repeatable, alphanumeric
                "Instructions" => $iptc["2#040"][0], // Max 256 octets, non-repeatable, alphanumeric
                "CreationDate" => $iptc["2#055"][0], // Max 8 octets, non-repeatable, numeric, YYYYMMDD
                "CreationTime" => $iptc["2#060"][0], // Max 11 octets, non-repeatable, numeric+-, HHMMSS(+|-)HHMM
                "ProgramUsed" => $iptc["2#065"][0], // Max 32 octets, non-repeatable, alphanumeric
                "Author" => $iptc["2#080"][0], // !Max 32 octets, repeatable, alphanumeric
                "Position" => $iptc["2#085"][0], // !Max 32 octets, repeatable, alphanumeric
                "City" => $iptc["2#090"][0], // Max 32 octets, non-repeatable, alphanumeric
                "State" => $iptc["2#095"][0], // Max 32 octets, non-repeatable, alphanumeric
                "Country" => $iptc["2#101"][0], // Max 64 octets, non-repeatable, alphanumeric
                "TransmissionReference" => $iptc["2#103"][0], // Max 32 octets, non-repeatable, alphanumeric
                "Headline" => $iptc["2#105"][0], // Max 256 octets, non-repeatable, alphanumeric
                "Credit" => $iptc["2#110"][0], // Max 32 octets, non-repeatable, alphanumeric
                "Source" => $iptc["2#115"][0], // Max 32 octets, non-repeatable, alphanumeric
                "Copyright" => $iptc["2#116"][0], // Max 128 octets, non-repeatable, alphanumeric
                "Caption" => $iptc["2#120"][0], // Max 2000 octets, non-repeatable, alphanumeric
                "CaptionWriter" => $iptc["2#122"][0] // Max 32 octets, non-repeatable, alphanumeric
                );
        } else $IPTC_data = array();
    } 
    return $IPTC_data;
}