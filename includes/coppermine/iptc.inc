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

function get_IPTC($filename)
{
	$IPTC_data = array();
	$size = GetImageSize ($filename, $info);
	if (isset($info['APP13'])) {
		$iptc = iptcparse($info['APP13']);
		if (is_array($iptc)) {
			$IPTC_data = array('Title' => $iptc['2#005'][0], // Max 65 octets, non-repeatable, alphanumeric
				'Urgency' => $iptc['2#010'][0], // Max 1 octet, non-repeatable, numeric, 1 - High, 8 - Low
				'Category' => $iptc['2#015'][0], // Max 3 octets, non-repeatable, alpha
				'SubCategories' => $iptc['2#020'], // Max 32 octets, repeatable, alphanumeric
				'Keywords' => $iptc['2#025'], // Max 64 octets, repeatable, alphanumeric
				'Instructions' => $iptc['2#040'][0], // Max 256 octets, non-repeatable, alphanumeric
				'CreationDate' => $iptc['2#055'][0], // Max 8 octets, non-repeatable, numeric, YYYYMMDD
				'CreationTime' => $iptc['2#060'][0], // Max 11 octets, non-repeatable, numeric+-, HHMMSS(+|-)HHMM
				'ProgramUsed' => $iptc['2#065'][0], // Max 32 octets, non-repeatable, alphanumeric
				'Author' => $iptc['2#080'][0], // !Max 32 octets, repeatable, alphanumeric
				'Position' => $iptc['2#085'][0], // !Max 32 octets, repeatable, alphanumeric
				'City' => $iptc['2#090'][0], // Max 32 octets, non-repeatable, alphanumeric
				'State' => $iptc['2#095'][0], // Max 32 octets, non-repeatable, alphanumeric
				'Country' => $iptc['2#101'][0], // Max 64 octets, non-repeatable, alphanumeric
				'TransmissionReference' => $iptc['2#103'][0], // Max 32 octets, non-repeatable, alphanumeric
				'Headline' => $iptc['2#105'][0], // Max 256 octets, non-repeatable, alphanumeric
				'Credit' => $iptc['2#110'][0], // Max 32 octets, non-repeatable, alphanumeric
				'Source' => $iptc['2#115'][0], // Max 32 octets, non-repeatable, alphanumeric
				'Copyright' => $iptc['2#116'][0], // Max 128 octets, non-repeatable, alphanumeric
				'Caption' => $iptc['2#120'][0], // Max 2000 octets, non-repeatable, alphanumeric
				'CaptionWriter' => $iptc['2#122'][0] // Max 32 octets, non-repeatable, alphanumeric
			);
		};
	}
	return $IPTC_data;
}
