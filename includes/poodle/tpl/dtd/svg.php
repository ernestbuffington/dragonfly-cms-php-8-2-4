<?php

// NOTE: required attributes must come first
// http://www.w3.org/TR/html-markup/global-attributes.html
// http://www.w3.org/TR/html-markup/elements.html

$DTD = array_merge($DTD, array(

	'DOCTYPE'    => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">',
	'EMPTY-TAGS' => array(),

	'XML'        => true,

	'STANDARD_ATTRIBUTES' => array('class'=>0),

	'circle' => array('cx'=>0, 'cy'=>0, 'r'=>0),
	'path' => array('d'=>0),
	'svg' => array('xmlns'=>'http://www.w3.org/2000/svg', 'version'=>'1.1', 'viewBox'=>0),
));
