<?php

# NOTE: required attributes must come first

$DTD = array(
//	'CDATA'      => array('<!--', '//-->'),
	'DOCTYPE'    => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
	'EMPTY-TAGS' => array('area', 'base', 'br', 'col', 'hr', 'img', 'input', 'link', 'meta', 'param'),

	'XML'        => false,
	'CASE-INSENSITIVE' => true,
	'STANDARD_ATTRIBUTES' => array('class'=>0, 'dir'=>0, 'id'=>0, 'lang'=>0, 'style'=>0, 'title'=>0),

	'a'          => array('accesskey'=>0, 'charset'=>0, 'coords'=>0, 'href'=>0, 'hreflang'=>0, 'name'=>0, 'rel'=>0, 'rev'=>0, 'shape'=>0, 'tabindex'=>0, 'target'=>0, 'type'=>0),
	'abbr'       => array(),
	'address'    => array(),
	'area'       => array('alt'=>'', 'accesskey'=>0, 'coords'=>0, 'href'=>0, 'nohref'=>0, 'shape'=>0, 'tabindex'=>0, 'target'=>0),
	'b'          => array(),
	'base'       => array('href'=>0, 'target'=>0),
	'bdo'        => array(),
	'big'        => array(),
	'blink'      => array(),
	'blockquote' => array('cite'=>0),
	'body'       => array(), # 'alink', 'background', 'bgcolor', 'link', 'text', 'vlink'
	'br'         => array('clear'=>0),
	'button'     => array('accesskey'=>0, 'disabled'=>0, 'name'=>0, 'tabindex'=>0, 'type'=>0, 'value'=>0),
	'caption'    => array(), # 'align'
	'cite'       => array(),
	'code'       => array(),
	'col'        => array('char'=>0, 'charoff'=>0, 'span'=>0, 'valign'=>0, 'width'=>0),  # 'align'
	'colgroup'   => array('char'=>0, 'charoff'=>0, 'span'=>0, 'valign'=>0, 'width'=>0),  # 'align'
	'dd'         => array(),
	'del'        => array('cite'=>0, 'datetime'=>0),
	'dfn'        => array(),
	'div'        => array(), # 'align'
	'dl'         => array(), # 'compact'
	'dt'         => array(),
	'em'         => array(),
	'fieldset'   => array(),
	'form'       => array('action'=>'', 'accept'=>0 /*\Poodle::CHARSET*/, 'accept-charset'=>0, 'enctype'=>0, 'method'=>0, 'name'=>0), # 'target'
	'h1'         => array(), # 'align'
	'h2'         => array(), # 'align'
	'h3'         => array(), # 'align'
	'h4'         => array(), # 'align'
	'h5'         => array(), # 'align'
	'h6'         => array(), # 'align'
	'head'       => array('profile'=>0),
	'hr'         => array(), # 'align', 'noshade', 'size', 'width'
	'html'       => array(),
	'i'          => array(),
	'img'        => array('alt'=>'', 'src'=>'', 'height'=>0, 'ismap'=>0, 'longdesc'=>0, 'usemap'=>0, 'width'=>0), # 'align', 'border', 'hspace', 'vspace'
	'input'      => array('accept'=>0, 'accesskey'=>0, 'alt'=>0, 'checked'=>0, 'disabled'=>0, 'maxlength'=>0, 'name'=>0, 'readonly'=>0, 'size'=>0, 'src'=>0, 'tabindex'=>0, 'type'=>0, 'value'=>0), # 'align'
	'ins'        => array('cite'=>0, 'datetime'=>0),
	'kbd'        => array(),
	'label'      => array('accesskey'=>0, 'for'=>0),
	'legend'     => array('accesskey'=>0), # 'align'
	'li'         => array('type'=>0, 'value'=>0),
	'link'       => array('disabled'=>0, 'charset'=>0, 'href'=>0, 'hreflang'=>0, 'media'=>0, 'rel'=>0, 'rev'=>0, 'target'=>0, 'type'=>0),
	'map'        => array('name'=>''),
	'meta'       => array('content'=>'', 'http-equiv'=>0, 'name'=>0, 'scheme'=>0),
	'noscript'   => array(),
	'object'     => array('archive'=>0, 'border'=>0, 'classid'=>0, 'codebase'=>0, 'codetype'=>0, 'data'=>0, 'declare'=>0, 'height'=>0, 'hspace'=>0, 'name'=>0, 'standby'=>0, 'tabindex'=>0, 'type'=>0, 'usemap'=>0, 'vspace'=>0, 'width'=>0), # 'align'
	'ol'         => array(), # 'compact', 'start', 'type'
	'optgroup'   => array('label'=>'', 'disabled'=>0),
	'option'     => array('disabled'=>0, 'label'=>0, 'selected'=>0, 'value'=>0),
	'p'          => array(), # 'align'
	'param'      => array('name'=>'', 'type'=>0, 'value'=>0, 'valuetype'=>0),
	'pre'        => array(), # 'width'
	'q'          => array('cite'=>0),
	'samp'       => array(),
	'script'     => array('type'=>'text/javascript', 'charset'=>0, 'defer'=>0, 'src'=>0),
	'select'     => array('disabled'=>0, 'multiple'=>0, 'name'=>0, 'size'=>0, 'tabindex'=>0),
	'small'      => array(),
	'span'       => array(),
	'strong'     => array(),
	'style'      => array('type'=>'text/css', 'media'=>0),
	'sub'        => array(),
	'sup'        => array(),
	'table'      => array('border'=>0, 'cellpadding'=>0, 'cellspacing'=>0, 'frame'=>0, 'rules'=>0, 'summary'=>0, 'width'=>0), # 'align', 'bgcolor'
	'tbody'      => array('char'=>0, 'charoff'=>0, 'valign'=>0), # 'align'
	'td'         => array('abbr'=>0, 'axis'=>0, 'char'=>0, 'charoff'=>0, 'colspan'=>0, 'headers'=>0, 'rowspan'=>0, 'scope'=>0, 'valign'=>0), # 'align', 'bgcolor', 'height', 'nowrap', 'width'
	'textarea'   => array('cols'=>20, 'rows'=>2, 'accesskey'=>0, 'disabled'=>0, 'name'=>0, 'readonly'=>0, 'tabindex'=>0),
	'tfoot'      => array('char'=>0, 'charoff'=>0, 'valign'=>0), # 'align'
	'th'         => array(),
	'thead'      => array('char'=>0, 'charoff'=>0, 'valign'=>0), # 'align'
	'title'      => array(),
	'tr'         => array('char'=>0, 'charoff'=>0, 'valign'=>0), # 'align', 'bgcolor'
	'tt'         => array(),
	'ul'         => array(), # 'compact', 'type'
	'var'        => array(),
);
