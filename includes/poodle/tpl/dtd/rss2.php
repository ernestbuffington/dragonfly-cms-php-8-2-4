<?php

# NOTE: required attributes must come first

$DTD = array(
	'CDATA'      => array('<![CDATA[', ']]>'),
	'DOCTYPE'    => '',
	'EMPTY-TAGS' => array(),
	'XSD'        => 'http://www.thearchitect.co.uk/schemas/rss-2_0.xsd',
	'XML'        => true,

	'STANDARD_ATTRIBUTES' => array(),

	'rss'            => array('version'=>'0.91'),
	'channel'        => array(),
	'title'          => array(),
	'description'    => array(),
	'link'           => array(),
	'image'          => array(),
	'url'            => array(),
	'item'           => array(),
	'textinput'      => array(),
	'name'           => array(),
	'rating'         => array(),
	'language'       => array(),
	'width'          => array(),
	'height'         => array(),
	'copyright'      => array(),
	'pubDate'        => array(),
	'lastBuildDate'  => array(),
	'docs'           => array(),
	'managingEditor' => array(),
	'webMaster'      => array(),
	'skipHours'      => array(),
	'hour'           => array(),
	'skipDays'       => array(),
	'day'            => array(),
/*
<!ELEMENT rss (channel)>
<!ATTLIST rss version CDATA #REQUIRED> <!-- must be "0.91"> -->
<!ELEMENT channel (title | description | link | language | item+ | rating? | image? | textinput? | copyright? | pubDate? | lastBuildDate? | docs? | managingEditor? | webMaster? | skipHours? | skipDays?)*>
<!ELEMENT title (#PCDATA)>
<!ELEMENT description (#PCDATA)>
<!ELEMENT link (#PCDATA)>
<!ELEMENT image (title | url | link | width? | height? | description?)*>
<!ELEMENT url (#PCDATA)>
<!ELEMENT item (title | link | description)*>
<!ELEMENT textinput (title | description | name | link)*>
<!ELEMENT name (#PCDATA)>
<!ELEMENT rating (#PCDATA)>
<!ELEMENT language (#PCDATA)>
<!ELEMENT width (#PCDATA)>
<!ELEMENT height (#PCDATA)>
<!ELEMENT copyright (#PCDATA)>
<!ELEMENT pubDate (#PCDATA)>
<!ELEMENT lastBuildDate (#PCDATA)>
<!ELEMENT docs (#PCDATA)>
<!ELEMENT managingEditor (#PCDATA)>
<!ELEMENT webMaster (#PCDATA)>
<!ELEMENT skipHours (hour+)>
<!ELEMENT hour (#PCDATA)>
<!ELEMENT skipDays (day+)>
<!ELEMENT day (#PCDATA)>
*/
);
