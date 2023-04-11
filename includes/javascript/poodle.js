/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://yura.thinkweb2.com/named-function-expressions/
	http://peter.michaux.ca/articles/an-important-pair-of-parens
*/

/**
 * PHP Alike
 */

function defined(v)    { var d; return d !== v; }
function is_array(v)   { return v instanceof Array; }
function is_function(v){ return typeof v === 'function' && (!Function.prototype.call || typeof v.call === 'function'); } /** IE seems to think that some functions are objects rather than functions */
function is_number(v)  { return typeof v === 'number' && isFinite(v); }
function is_object(v)  { return typeof v === 'object'; }
function is_string(v)  { return typeof v === 'string'; }
/*
function is_bool(v)    { return typeof v === 'boolean'; }
function is_numeric(v) { return is_number(v) || (is_string(v) && '' !== v.trim() && !isNaN(v)); }
function is_int(v)     { return is_number(v) && !(v % 1); }
function is_float(v)   { return is_number(v) && !!(v % 1); }
function is_null(v)    { return null === v; }
*/
//ENT_HTML_QUOTE_NONE   = 0;
//ENT_HTML_QUOTE_SINGLE = 1;
//ENT_HTML_QUOTE_DOUBLE = 2;
ENT_NOQUOTES = 0;
ENT_COMPAT   = 2;
ENT_QUOTES   = 3;
htmlspecialchars = (function(de,se,gt,lt,sq,dq){
	return function(str, quote_style, double_encode)
	{
		str = (''+str)
			.replace((!defined(double_encode)||double_encode)?de:se,'&amp;')
			.replace(gt,'&lt;')
			.replace(lt,'&gt;');
		if (!is_number(quote_style)) { quote_style = 2; }
		if (quote_style & 1) { str = str.replace(sq,'&#039;'); }
		return (quote_style & 2) ? str.replace(dq,'&quot;') : str;
	};
})(/&/g,/&(?![\w\#]+;)/gi,/</g,/>/g,/'/g,/"/g /*"'*/);

strip_tags = (function(m){
	return function(str) { return str.replace(m, '').trim(); };
})(/<\s*\/?\s*(\w+|!)[^>]*>/gi);

function floatval(v, def)
{
	var i = parseFloat(v);
	return isFinite(i)?i:defined(def)?def:0.0;
}

function intval(v, d) { return Math.round(floatval(v, d)); }

function sprintf(f) {
	if (!is_string(f)) throw "sprintf: The first arguments need to be a valid format string.";
	var r = new RegExp('%%|%(?:(\d+)[\$#])?([+-])?(\'.|0| )?(\d*)(?:\.(\d+))?([bcdefgosuxX])','g'), idx=0, a=arguments, n;
	/**
	 * The callback function arguments:
	 *      m = found substring
	 *      i = index specifier (\d+\$ or \d+#)
	 *      s = alignment specifier ("+" or "-" or empty)
	 *      p = padding specifier (space, 0 or defined as '.)
	 *      w = width specifier (\d*)
	 *      d = floating-point precision specifier (\.\d*)
	 *      t = type specifier ([bcdefgosuxX])
	 */
	return f.replace(r, function (m, i, s, p, w, d, t) {
		if ('%%'==m) return '%';
		i = 0<i ? i : ++idx;
		if (i >= a.length) throw "sprintf: At least one argument was missing.";
		d = intval(d);
		n = Number(a[i]);
		switch (t) {
		case 'b': return n.toString(2);
		case 'c': return String.fromCharCode(n);
		case 'd':
		case 'u': return n.toString(10);
		case 'e': return n.toExponential(d);
		case 'f': return n.toFixed(d);
		case 'g': return n.toPrecision(d);
		case 'o': return n.toString(8);
		case 's': return String(a[i]).pad(intval(w), ((p && p.length) ? p[p.length-1] : ''), '-'==s?1:0);
		case 'x': return n.toString(16).toLowerCase();
		case 'X': return n.toString(16).toUpperCase();
		}
		return '';
	});
}

/**
 * Initialize Poodle DOM
 */

(function(){

function lc(v){return v.toLowerCase();}

var version = 2.0,
doc = document,
max = Math.max,
emptyTags = /^(area|base|br|col|hr|img|input|link|meta|param)$/i,
dateType = /(date(?:time(?:-local)?)?|month|time|week)/,
fbug=['assert','debug','error','info','log','profile','profileEnd','warn', /** firebug/WebKit */
      'count','dir','dirxml','group','groupEnd','time','timeEnd','trace'], /** firebug */
i,
nav = navigator,
ua  = {ie:0/*@cc_on +(document.documentMode||@_jscript_version*10-50)@*/},
win = window,
DCL = 'DOMContentLoaded',
DFCL = 'DOMFrameContentLoaded',
cssPrefix = '',
oproto = Object.prototype,
hasOwnProp = oproto.hasOwnProperty,
strContains_re = [],
dragNode,
tt=[1,1],
dom_events = {
	// Poodle
	afterreset:[],
	/**
	 * http://en.wikipedia.org/wiki/DOM_events
	 * http://www.quirksmode.org/dom/events/
	 * http://www.w3.org/TR/DOM-Level-3-Events/
	 * http://www.w3schools.com/tags/ref_eventattributes.asp
	 * event: [Bubbles, Cancelable]
	 */
	// Mouse
	click:     tt,
	dblclick:  tt,
	mousedown: tt,
	mouseenter:[0,1],
	mouseleave:[0,1],
	mouseup:   tt,
	mouseover: tt,
	mousemove: [1],
	mouseout:  tt,
	mousewheel:tt,
	// Mouse HTML 5
	DOMMouseScroll:[],
	contextmenu:tt,
	drag:       tt,
	dragend:    tt,
	dragenter:  tt,
	dragleave:  tt,
	dragover:   tt,
	dragstart:  tt,
	drop:       tt,
	selectstart:tt, /** IE, css -moz-user-select:none */
	// Keyboard
	keydown:   tt,
	keypress:  tt,
	keyup:     tt,
	// Text Events
	textInput: tt,
	// HTML frame/object
	load:      [],
	unload:    [],
	abort:     [1],
	error:     [1],
	resize:    [1],
	scroll:    [1],
	beforeunload:[1],
	// HTML form
	select:    [1],
	change:    [1],
	submit:    tt,
	reset:     [1],
	formchange:[],
	forminput: [], /** deprecated, use 'input' event */
	input:     [],
	invalid:   [],
	// User interface
	DOMActivate: tt, /** DOMActivate, IE's onactivate & Gecko oncommand are similar */
	focus:     [],
	focusin:   [1],
	focusout:  [1],
	blur:      [],
	// Mutation
	DOMSubtreeModified:          [1],
	DOMNodeInserted:             [1],
	DOMNodeRemoved:              [1],
	DOMNodeRemovedFromDocument:  [],
	DOMNodeInsertedIntoDocument: [],
	DOMAttrModified:             [1], /** IE's onpropertychange is similar */
	DOMCharacterDataModified:    [1],
	// Firefox, Opera 9+ & Safari 3.1+
	DOMContentLoaded:      [],
	DOMFrameContentLoaded: [],
	// Mutation Name
	DOMAttributeNameChanged: [],
	DOMElementNameChanged:   [],
	/** OLD whatwg Web Forms, but very usefull for our custom HTML5Form controls */
	DOMControlValueChanged:  [1], // whatwg.org/specs/web-forms/current-work/#the-domcontrolvaluechanged
	/** HTML 5 */
	hashchange: tt,
	message:    [0,1],
	orientationchange: tt, // screen rotated, screen.orientation
	// Touch events http://backtothecode.blogspot.nl/2009/10/javascript-touch-and-gesture-events.html
	touchstart:  tt, // like mouseDown
	touchmove:   tt, // like mouseMove
	touchend:    tt, // like mouseUp.
	touchcancel: tt,
	touchleave:  tt,
	//touchenter:  tt, ?????
	// Gesture events
	gesturestart:  tt,
	gesturechange: tt,
	gestureend:    tt,
	// media
	canplay:        tt,
	canplaythrough: tt,
	durationchange: tt,
	emptied:        tt,
	ended:          tt,
	error:          tt,
	loadeddata:     tt,
	loadedmetadata: tt,
	loadstart:      tt,
	pause:          tt,
	play:           tt,
	playing:        tt,
	progress:       tt,
	ratechange:     tt,
	readystatechange: tt,
	seeked:       tt,
	seeking:      tt,
	stalled:      tt,
	suspend:      tt,
	timeupdate:   tt,
	volumechange: tt,
	waiting:      tt
};

/**
 * getfirebug.com
 */

if (win.loadFirebugConsole) loadFirebugConsole();
if (!win.console) win.console = {};
function alertError(e){
	var msg = (e.name?e.name+': ':'')+(e.message?e.message:e);
	if (e.fileName||e.sourceURL) { msg += '\n\n@'+(e.fileName||e.sourceURL)+'#'+(e.lineNumber||e.line); } /** Gecko||WebKit */
	alert(msg);
}
for (i=0;i<fbug.length;++i) {
	if (!console[fbug[i]]) try {
		console[fbug[i]] = win.debug ? debug : (2==i ? (win.opera && win.opera.postError ? opera.postError : alertError) : function(){});
	} catch (e) {}
}

/**
 * Browser Detection
 * Reference: howtocreate.co.uk/tutorials/jsexamples/sniffer.html
 */

if (win.opera) { ua.opera=opera.version?floatval(opera.version()):true; cssPrefix = '-o-'; }
else if (ua.ie) { if(10>ua.ie)alert("You must upgrade your old IE"+ua.ie+" browser"); cssPrefix = '-ms-'; }
else {
	var a = nav.userAgent, m = a.match(/(KHTML|WebKit|Gecko)\/([\d\.]+)/i);
	if (m) {
		ua[lc(m[1])] = floatval(m[2]);
		cssPrefix = '-' + (ua.gecko ? 'moz' : lc(m[1])) + '-';
	}
	v = floatval((a.match(/Version\/([\d\.]+).+Safari\//i)||[0,0])[1]);
	if (v) ua.safari = v;
}
ua[lc((nav.platform.match(/Mac|Win|Linux|BSD|SunOS/i) || ['other'])[0])] = true;

/**
 * Event Handling
 */

function startDrag(n)
{
	if (!dragNode && !n.nodeType && n.dataTransfer) {
		// IE < 10
		n.dataTransfer.effectAllowed='all';
		n = document.elementFromPoint(n.clientX,n.clientY);
	}
	if (!dragNode && n && 1 === n.nodeType) {
		dragNode = n;
		setTimeout(function(){n.addClass('dragging');},1);
	}
}
function endDrag()
{
	if (dragNode) {
		dragNode.removeClass('dragging');
		dragNode = null;
	}
}

function FastClick(el, fn) {
	this.element = el;
	this.handler = fn;
	el.on('touchstart', this);
	// touchstart not always triggered on tap,
	// for example on a Vodafone Smart 3 you have to tap 1 to 5 times
	// So instead, we trigger click
	el.addEventListener('click', this, false);
}
FastClick.prototype = {
	handleEvent: function(e) {
		switch (e.type)
		{
		case 'touchstart':
			e.stopPropagation();
			FastClick.touch = true;
			this.element.on('touchmove touchend touchcancel touchleave', this);
			this.startX = e.touches[0].clientX;
			this.startY = e.touches[0].clientY;
			break;

		case 'touchmove':
			if (Math.abs(e.touches[0].clientX - this.startX) > 10 ||
				Math.abs(e.touches[0].clientY - this.startY) > 10)
			{
				this.reset();
			}
			break;

		case 'touchcancel':
		case 'touchleave':
			this.reset();
			break;

		case 'click':
			if (!FastClick.touch) { this.handler.call(this.element, e); }
			FastClick.touch = false;
			break;

		case 'touchend':
			e.stopPropagation();
			this.reset();
			this.handler.call(this.element, e);
			break;
		}
	},

	reset: function() {
		this.element.off('touchmove touchend touchcancel touchleave', this);
		//setTimeout(function(){FastClick.touch = false;}, 500);
	}
};

function bindEvent(type, fn, capture)
{
	if (this.addEventListener) {
		var types = type.split(/\s+/), i=0;
		while (type = types[i++]) {
			var t = lc(type);
			if (lc(DCL) == t) { this.$W().Poodle.onDOMReady(fn); }
			else {
				if ('beforesubmit' == t) { this.addEventBeforeSubmit(fn); return this; }
				if ('submit' == t) { this.addEventBeforeSubmit(); }
				/**
				 * Extend HTML5 DnD with feature to know which Node is being dragged
				 * and IE < 10 to drag any node
				 */
				if ('dragstart' == t) {
					var n=this;
					if (!n._bm_ds && 1 === n.nodeType) {
						n._bm_ds = 1;
						n.on(t,function(){startDrag(n);});
						n.on('dragend',endDrag);
						if (window.Poodle_initDraggable) Poodle_initDraggable(n);
					}
				}
				if (ua.ie && 'dommousescroll' == t) { type='mousewheel'; }
				// 'ontouchstart' in document.documentElement
				if ('click' == t && 'ontouchstart' in this.$D().documentElement) {
					// Prevent 300ms delay on touch devices
					new FastClick(this, fn);
				} else {
					this.addEventListener(type, fn, !!capture);
				}
				if (!dom_events[type]) { console.log("Custom Event type: "+type); }
			}
		}
	}
	return this;
}

function unbindEvent(type, fn, capture)
{
	if (this.removeEventListener) {
		var types = type.split(/\s+/), i=0;
		while (type = types[i++]) {
			this.removeEventListener(type, fn, !!capture);
		}
	}
	return this;
}

function simulateEvent(type, props, detail, bubbles, cancelable)
{
	if (this.dispatchEvent) {
		var evt, doc=this.$D?this.$D():document;
		if (dom_events[type]) {
			bubbles    = dom_events[type][0];
			cancelable = dom_events[type][1];
		} else {
			bubbles = (3>arguments.length||bubbles);
			cancelable = (4>arguments.length||cancelable);
		}
		if (doc.createEvent) {
			if (detail) {
				evt = doc.createEvent('CustomEvent');
				evt.initCustomEvent(type, !!bubbles, !!cancelable, detail);
			} else {
				evt = doc.createEvent('HTMLEvents');
				evt.initEvent(type, !!bubbles, !!cancelable);
			}
			this.dispatchEvent(extendDOM(evt, props));
		} else {
			console.error("Can't simulate "+type+" event");
		}
	}
	return this;
}

/**
 * Helper functions
 */

function extendDOM(obj, elements)
{
	if (obj && is_object(elements)) {
		var k, o, c;
		for (k in elements)
		try {
			if (!(k in obj || (obj.hasOwnProperty && obj.hasOwnProperty(k)))) {
				o = elements[k];
				if (o && is_object(o) && ('value' in o || 'get' in o || 'set' in o)) {
					c = obj.constructor;
					console.info('defineProperty: '+ (c ? c.name + '.' : '') + k);
					Object.defineProperty(obj, k, o);
				} else {
					obj[k] = o;
				}
			}
		} catch (e) { console.error("defineProperty "+o+"."+k+": "+e.message); }
	}
	return obj;
}

/** Object extending. extend() is used by Selection object */
function extendNode(target, obj)
{
	var o=target, k, v;
	if (obj) {
		if (!is_object(obj)) { obj = new obj; }
		// copy properties
		for (k in obj) {
			v=obj[k];
			// Skip prototype additions
			if (v != oproto[k]) {
				try { switch (k) {
					case 'class':
					case 'className':  o.setClass(v); break;
					case 'for':
					case 'htmlFor':    o.attr('for', v); break;
					case 'cssText':    o.attr('style', v); break;
					case 'innerHTML':  o.html(v); break;
					case 'textContent':o.txt(v); break;
					case 'style':      extendObj(o.style, v); break;
					default:
						if (0 === k.indexOf('on') && dom_events[k.substr(2)] && is_function(v) && o.on) {
							o.on(k.substr(2), v);
						} else {
							// (!obj.hasOwnProperty || !obj.hasOwnProperty(k))
							if (is_number(v) || is_string(v)) o.attr(k,v);
							o[k] = v;
						}
				} } catch (e) {} // IE or read-only
			}
		}
	}
	return o;
}

function extendObj(target, obj, ignoreProto)
{
	if (obj) {
		if (!target) target = {};
		if (!is_object(obj)) { obj = new obj; }
		var k, v;
		for (k in obj) {
			v=obj[k];
			// Skip prototype additions?
			if (ignoreProto || v !== oproto[k]) {
				try {
					target[k] = is_object(v) ? extendObj(target[k], v, ignoreProto) : v;
				} catch (e) {} // IE or read-only
			}
		}
	}
	return target;
}

function mergeObjectProperties(from, to)
{
	Object.getOwnPropertyNames(from).forEach(function(p){
		to[p] = from[p];
	});
}

var CSS = [];
function createCSS(rule, media, newStyle)
{
	var m = is_string(media) ? media : 'screen';
	if (newStyle || !CSS[m]) { CSS[m] = doc.$H().$A('style',{type:'text/css',media:m}).sheet; }
	if (CSS[m]) { CSS[m].insertRule(rule, CSS[m].cssRules.length); }
}

function cssrule(r)
{
	return r=='float'?'cssFloat':r.replace(/\-(\w)/g, function(d,m){return m.toUpperCase();});
}

/**
 * ECMAScript 5
 */
if (!Object.defineProperty) {
	// IE8 only accepts DOM objects
	// Safari 4 and Opera 11 don't
	// Safari 5 in some versions defineProperty is not supported on DOM Objects
	// http://kangax.github.com/es5-compat-table/#define-property-webkit-note
	console.warn("Object.defineProperty not defined");
	Object.defineProperty = function(o, p, d) {
		if (hasOwnProp && is_object(d)) {
			if (hasOwnProp.call(d, 'value')) {
				if (!(o.__lookupGetter__ && o.__lookupGetter__(p)) && !(o.__lookupSetter__ && o.__lookupSetter__(p)))
					o[p] = d.value; // data property defined and no pre-existing accessors
				if ((hasOwnProp.call(d, 'get') || hasOwnProp.call(d, 'set'))) // descriptor has a value prop but accessor already exists
					throw new TypeError("Object doesn't support this action"); // exactly what IE throws
			}
	/*
			// can't implement these features, allow true but not false
			if (!(hasOwnProp.call(d, 'writable')? d.writable : 1)
			 || !(hasOwnProp.call(d, 'enumerable')? d.enumerable : 1)
			 || !(hasOwnProp.call(d, 'configurable')? d.configurable : 1)
			) throw new RangeError("This implementation of Object.defineProperty does not support configurable, enumerable, or writable.");
			//Message: 'writable' attribute on the property descriptor cannot be set to 'true' on this object
	*/
			// DOMElement.prototype.__define*__: ff2, safari3, opera 9.5
			if (is_function(d.get) && o.__defineGetter__) o.__defineGetter__(p, d.get);
			if (is_function(d.set) && o.__defineSetter__) o.__defineSetter__(p, d.set);
		}
		return o;
	};
}

function getPropDesc(o, p) {
	if (!hasOwnProp.call(o,p)) return undefined;
	return {
		get:o.__lookupGetter__?o.__lookupGetter__(p):null,
		set:o.__lookupSetter__?o.__lookupSetter__(p):null
	};
}
if (!Object.getOwnPropertyDescriptor) {
	console.warn("Object.getOwnPropertyDescriptor not defined");
	Object.getOwnPropertyDescriptor = getPropDesc;
} else {
	/**
	 * Firefox catch: "Illegal operation on WrappedNative prototype object"
	 * http://www.autobypass.com/index.php?a=aHR0cHM6Ly9idWd6aWxsYS5tb3ppbGxhLm9yZy9zaG93X2J1Zy5jZ2k%2FaWQ9NTYwMDcy
	 */
	var gOPD = Object.getOwnPropertyDescriptor;
	Object.getOwnPropertyDescriptor = function(o, p) {
		try {
			var pd = gOPD.call(Object, o, p);
			if (pd) return pd;
		} catch(e) {
//			console.error(e);
		}
		return getPropDesc(o, p);
	};
}

/**
 * ProgID is one of: DOMDocument, FreeThreadedDOMDocument, MXHTMLWriter,
 * MXNamespaceManager, MXXMLWriter, SAXAttributes, SAXXMLReader,
 * ServerXMLHTTP, XMLHTTP, XMLSchemaCache, XSLTemplate
 *
 * blogs.msdn.com/xmlteam/archive/2006/10/23/using-the-right-version-of-msxml-in-internet-explorer.aspx
 */
function MSXML(ProgID)
{
	try { return new ActiveXObject('Msxml2.'+ProgID+'.6.0'); }catch(e){} // Vista / Windows 7
	try { return new ActiveXObject('Msxml2.'+ProgID); }catch(e){console.error(e);}
	return null;
}

/**
 * W3C DOM
 */
function $B()    { return this.$D().$B(); }
function $T(name){ return this.getElementsByTagName(name); }
/** http://www.w3.org/TR/selectors-api/ */
var re_css_not = /([a-z])(\[[a-z_:][-a-z0-9_:.]+)!(=('[^']*'|"[^"]*"|[^"'\[\]]+)\])/i,
	re_qs_quick = /^(?:(\w+)|\.([\w-]+))$/;
function $Q(expr,one)
{
	var m, qs = 'querySelector'+(one?'':'All');
	if (0 < expr.indexOf('!=')) {
		expr = expr.replace(re_css_not, '$1:not($2$3)');
	} else if (!one && 11 !== this.nodeType && (m = re_qs_quick.exec(expr))) {
		if (m[1]) {
			qs = 'getElementsByTagName';
		} else if (m[2]) {
			qs = 'getElementsByClassName';
			expr = m[2];
		}
	}
	try {
		// Also convert NodeList to Array
		return (one ? this[qs](expr) : Array.prototype.slice.call(this[qs](expr), 0));
	} catch(e) {}
	return new PoodleSelector(expr).exec(this, one);
}
/** https://developer.mozilla.org/en/XPath, could define getElementsByXPath() */
function $X(expression, altfn)
{
	if (!this.$D().evaluate) { return is_function(altfn)?altfn.call(this):false; }
	var n, result = [],
		q = this.$D().evaluate(expression, this, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
	while (n = q.iterateNext()) result.push(n);
	return result;
}
function contentDoc() { try { return this.contentDocument || this.contentWindow.document; }catch(e){} }
function contentWin() { try { return this.contentWindow || DOM.Document.$W.call(this.$Doc()); }catch(e){} }

function getInputMinMax(o, p)
{
	var v = o.attr(p), t = o.attr('type');
	if ('number'===t) { return floatval(v, ('min'==p)?-Infinity:Infinity); }
	if ('range' ===t) { return floatval(v, ('min'==p)?0:100); }
	if (t.match(dateType)) {
		var f = o.getDateFormat();
		return (v && f) ? v.toDate(f).format(f) : '';
	}
	return null;
}

function setInputMinMax(o, p, v)
{
	if ('' !== v) {
		var t = o.attr('type');
		if ('number'===t) { v = floatval(v, ('min'==p)?-Infinity:Infinity); }
		if ('range' ===t) { v = floatval(v, ('min'==p)?0:100); }
		if (t.match(dateType)) {
			var f = o.getDateFormat();
			v = f ? v.toDate(f) : null;
			v = v ? v.format(f) : v;
		}
	}
	o.attr(p, v);
}

function arrCallFunctions(a, e, o)
{
	a.forEach(function(f){
		try {
			if (is_function(f)) { f.call(o, e); }
		} catch (er) {
			console.error(er);
			console.error((e ? '\n'+e.target.nodeName+'.on'+e.type+'=' : " in ")+f);
		}
	});
}

// HTMLDialogElement alternative
function showDialog(n, m)
{
	var d = n.$C('div',{'class':'dialog'+(m?' modal':'')});
	n.replaceWith(d);
	d.appendChild(n);
	n.attr('open','');
	if (m) {
		window.onkeydown = function(e) {
			if (27 === e.keyCode) {
				n.close();
			}
		};
	}
}

STR_PAD_LEFT  = 0;
STR_PAD_RIGHT = 1;
STR_PAD_BOTH  = 2;

DATE_ISO8601 = "Y-m-d\\TH:i:sO";
DATE_RFC822  = "D, d M y H:i:s O";
DATE_RFC850  = "l, d-M-y H:i:s T";
DATE_RFC1036 = DATE_RFC822;
DATE_RFC1123 = "D, d M Y H:i:s O";
DATE_RFC2822 = DATE_RFC1123;
DATE_RFC3339 = "Y-m-d\\TH:i:sP";
DATE_ATOM    = DATE_RFC3339;
//DATE_COOKIE  = DATE_RFC850;
DATE_RSS     = DATE_RFC1123;
DATE_W3C     = DATE_RFC3339;
Date.longDays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
Date.shortDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
Date.longMonths = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
Date.shortMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

var props = {
	'for': 'htmlFor',
	'class': 'className',
	readonly: 'readOnly',
	maxlength: 'maxLength',
	cellspacing: 'cellSpacing',
	rowspan: 'rowSpan',
	colspan: 'colSpan',
	tabindex: 'tabIndex',
	usemap: 'useMap',
	frameborder: 'frameBorder',
	contenteditable: 'contentEditable'
},
DOM = {

	Array: {
		item: function(i) { return this[i] || null; },
		fill: function(v,s,e) {
			s = s || 0;
			e = e || this.length;
			while (s < e) {
				this[s++] = v;
			}
		}
	},

	Boolean: {
		toJSON: function() { return this.valueOf(); }
	},

	Date: {
		/** Poodle */
		getDayOfYear:    function() { return Math.floor((Date.UTC(this.getFullYear(),this.getMonth(),this.getDate()) - Date.UTC(this.getFullYear(),0,1)) / 86400000); }
		,getDaysInMonth: function() { return 32 - new Date(this.getFullYear(), this.getMonth(), 32).getDate(); }
		,getISODay:      function() { var d = this.getDay(); return d>0 ? d : 7; }
		,getISOYear:     function() { return new Date(this.getFullYear(), this.getMonth(), this.getDate() - ((this.getDay() + 6) % 7) + 3).getFullYear(); }
		,getWeek: function() {
			var d = new Date(this.getFullYear(),0,1),
				wd = d.getISODay(),
				w = Math.ceil((this.getDayOfYear()+wd) / 7);
			/* ISO 8601 states that week 1 is the week with january 4th in it */
			if (4 < wd) { --w; }
			return (1 > w ? (new Date(this.getFullYear()-1,11,31)).getWeek() /* previous year, last week */ : (52 < w && 4 > this.getISODay() ? 1 /* next year, first week */ : w) );
		}
		,hasDST: function() {
			var y=this.getFullYear();
			return this.getTimezoneOffset() != max(new Date(y, 0, 1).getTimezoneOffset(), new Date(y, 6, 1).getTimezoneOffset());
		}
		,isLeapYear: function() { var y = this.getFullYear(); return (y%400===0||(y%4===0&&y%100!==0)); }
		,format: function (str, UTC) {
			UTC = UTC||str.match(/\\Z$/);
			var x = this, d = {
				D:(UTC?x.getUTCDay():x.getDay()),
				Y:(UTC?x.getUTCFullYear():x.getFullYear()),
				m:(UTC?x.getUTCMonth():x.getMonth()),
				d:(UTC?x.getUTCDate():x.getDate()),
				H:(UTC?x.getUTCHours():x.getHours()),
				Z:(UTC?0:-x.getTimezoneOffset())
			};
			return !str
			? this.toString()
			: str.replace(/\\?[a-zA-Z]/g,
				function (m) {
					if (m.charAt(0) === '\\') { return m.charAt(1); }
					switch (m) {
					// Day
					case 'd': return d.d.pad(2);
					case 'D': return Date.shortDays[d.D];
					case 'j': return d.d;
					case 'l': return Date.longDays[d.D];
					case 'N': return x.getISODay();
					case 'S': return (d.d % 10 == 1 && d.d != 11 ? "st" : (d.d % 10 == 2 && d.d != 12 ? "nd" : (d.d % 10 == 3 && d.d != 13 ? "rd" : "th")));
					case 'w': return d.D;
					case 'z': return x.getDayOfYear();
					// Week
					case 'W': return x.getWeek().pad(2);
					// Month
					case 'F': return Date.longMonths[d.m];
					case 'm': return (d.m + 1).pad(2);
					case 'M': return Date.shortMonths[d.m];
					case 'n': return d.m + 1;
					case 't': return x.getDaysInMonth();
					// Year
					case 'L': return (((d.Y%4===0)&&(d.Y%100 !== 0)) || (d.Y%400===0)) ? '1' : '0';
					case 'o': return x.getISOYear();
					case 'Y': return d.Y;
					case 'y': return ('' + d.Y).substr(2);
					// Time
					case 'a': return d.H < 12 ? "am" : "pm";
					case 'A': return d.H < 12 ? "AM" : "PM";
	//				case 'B': return ''; // Swatch Internet time, Not Yet Supported
					case 'g': return d.H % 12 || 12;
					case 'G': return d.H;
					case 'h': return (d.H % 12 || 12).pad(2);
					case 'H': return d.H.pad(2);
					case 'i': return (UTC?x.getUTCMinutes():x.getMinutes()).pad(2);
					case 's': return (UTC?x.getUTCSeconds():x.getSeconds()).pad(2);
					case 'u': return (UTC?x.getUTCMilliseconds():x.getMilliseconds()).pad(3);
					// Timezone
	//				case 'e': return ''; // Timezone identifier, Not Yet Supported
					case 'I': return UTC ? 0 : x.hasDST() ? 1 : 0;
					case 'O': return UTC ? 'Z' : (d.Z > 0 ? '+' : '-') + Math.abs(d.Z / 60).pad(2) + '00';
					case 'P': return UTC ? 'Z' : (d.Z > 0 ? '+' : '-') + Math.abs(d.Z / 60).pad(2) + ':' + Math.abs(d.Z % 60).pad(2);
					case 'T': return UTC ? 'UTC' : new Date(d.Y, 0, 1).toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, '$1');
					case 'Z': return d.Z * 60;
					// Full Date/Time
					case 'c': return x.format(DATE_ISO8601);
					case 'r': return x.format(DATE_RFC2822);
					case 'U': return x.getTime() / 1000;
					}
					return m;
				}
			);
		}
	},

	Number: {
		pad: function(l) { return (''+this).pad(l,'0',0); }
		,toJSON: function() { return this.valueOf(); }
		,bytesToSize:  function(precision){return this.bytesToHuman(precision,['B', 'KiB', 'MiB', 'GiB', 'TiB']);}
		,bytesToSpeed: function(precision){return this.bytesToHuman(precision,['Bps', 'KBps', 'MBps', 'GBps', 'TBps']);}
		,bytesToHuman: function(precision, sizes)
		{
			if (!is_number(precision)) { precision = 2; }
			var i = intval(Math.floor(Math.log(this) / Math.log(1024)));
			if (1>i) { precision = 0; }
			return (this / Math.pow(1024, i)).toFixed(precision) + ' ' + sizes[i];
		}
		,secondsToTime: function(){
			var h = Math.floor(this / 3600),
				m = Math.floor(this % 3600 / 60),
				s = Math.floor(this % 3600 % 60);
			return ((h > 0 ? h + ":" : "") + (m > 0 ? (h > 0 && m < 10 ? "0" : "") + m + ":" : "0:") + (s < 10 ? "0" : "") + s);
		}
	},

	String: {
		/** ECMA 6 */
		includes: function(s, p) { return -1 !== this.indexOf(s, p); },
		/** Poodle */
		addQueryParam: function(key, value) { return (this==''?'':this+'&')+key+'='+encodeURIComponent(value); },
		pad: function(l, s, t) {
			function fillString(s, n) {
				var r = '';
				while (n>0) {
					if (n & 1) r += s;
					n >>= 1;
					if (n) s += s;
				}
				return r;
			}
			s = s || ' ';
			return (l -= this.length) < 1 ? this : (s = fillString(s, Math.ceil(l/s.length))).substr(0, t = !t ? l : t == 1 ? 0 : Math.ceil(l / 2)) + this + s.substr(0, l - t);
		},
		toJSON: function() { return this.valueOf(); },
		toDate: function(format)
		{
			var i = -1,
				d = {Y:1970, m:1, d:1, H:0, i:0, s:0, u:0},
				f = format.match(/([YmdHis])/g),
				m,
				s = this;
			if (m = s.match(RegExp('('+Date.longMonths.join('|')+')')))  { d.m = Date.longMonths.indexOf(m[1])+1;  s = s.replace(m[1],''); } else
			if (m = s.match(RegExp('('+Date.shortMonths.join('|')+')'))) { d.m = Date.shortMonths.indexOf(m[1])+1; s = s.replace(m[1],''); }
			if (m = s.match(RegExp('('+Date.longDays.join('|')+')')))    { s = s.replace(m[1],''); } else
			if (m = s.match(RegExp('('+Date.shortDays.join('|')+')')))   { s = s.replace(m[1],''); }
			if (m = s.match(/\d{4}/)) { d.Y = m[0]; }
			if (d.Y && (m = s.match(/\-W(0[1-9]|[1-4][0-9]|5[0-3])/))) {
				var fd=0, w;
				do { w = new Date(d.Y,0,1+(fd++)); } while (w.getWeek() != 1);
				w = new Date(w.getTime()+(1000*60*60*24*7*(m[1]-1)));
				d.m = w.getMonth()+1;
				d.d = w.getDate();
			} else {
				m = s.trim().match(RegExp(format.trim()
					.replace(/[^YmdH]+/g,'.')
					.replace('d','(3[012]|[12][0-9]|0[0-9])?')
					.replace('m','(1[0-3]|0[0-9])?')
					.replace('Y','(\\d{4})?')
					.replace(/H.*$/,'(?:([01][0-9]|2[0-4]|-1):([0-5][0-9]|-1)(?::([0-5][0-9]|60|-1))?)?')
	//				.replace(/[is]/g,'([0-5]?[0-9])?')
					.replace(/\./g,'[^\\d]')));
				if (f && m) {
					while (f[++i]) { if (m[i+1]) d[f[i]] = intval(m[i+1]); }
					if (0>d.s) {--d.i;d.s=59;}
					if (0>d.i) {--d.H;d.i=59;}
					if (0>d.H) {--d.d;d.H=23;}
					if (1>d.m) {--d.Y;d.m=12;}
				}
			}
			d = (f && m) ? new Date(d.Y, max(1,d.m)-1, d.d, d.H, d.i, d.s, d.u) : null;
			return (d && s.match(/Z$/)) ? new Date(d.getTime()-(d.getTimezoneOffset()*60000)) : d;
		},
		ucfirst: function(){
			var s = this, c = s.charAt(0).toUpperCase();
			return c + s.substr(1);
		},
		countSubString: function(s){
			var n = 0, p = this.indexOf(s), l = s.length;
			while (p > -1) {
				++n;
				p = this.indexOf(s, p + l);
			}
			return n;
		}
	},

	CSSStyleSheet: {
		/** Make Firefox compatible to others non-W3C */
		rules:  {get:function(){return this.cssRules;}}
	},

	/** https://www.w3.org/TR/2011/WD-cssom-view-20110804/#the-clientrect-interface */
	/** ClientRect is renamed to DOMRect in 2013*/
	ClientRect: {
		x: { get:function(){return this.left;}, set:function(v){this.left = v;} },
		y: { get:function(){return this.top;},  set:function(v){this.top = v;} },
		contains:  function(p){return this.containsX(p.x) && this.containsY(p.y);},
		containsX: function(x){return x>=this.left && x<this.right;},
		containsY: function(y){return y>=this.top && y<this.bottom;}
	},
	/** https://www.w3.org/TR/geometry-1/#dom-domrect */
	DOMRect: {
		contains:  function(p){return this.containsX(p.x) && this.containsY(p.y);},
		containsX: function(x){return x>=this.x && x<this.right;},
		containsY: function(y){return y>=this.y && y<this.bottom;}
	},

	Node: {
		lowerName: function(){return lc(this.nodeName);},
		parent: function(){return this.parentNode;},
		next:   function(){return this.nextSibling;},
		prev:   function(){return this.previousSibling;},
		first:  function(){return this.firstChild;},
		last:   function(){return this.lastChild;},

		placeAfter:  function(node){return node.parent().insertBefore(this, node.nextSibling);},
		placeBefore: function(node){return node.parent().insertBefore(this, node);},
		/** https://dom.spec.whatwg.org/#parentnode */
		append:  function(node){this.appendChild(node);},
		prepend: function(node){this.insertBefore(node, this.firstChild);},
		/** https://dom.spec.whatwg.org/#childnode */
		after:       function(node){this.parent().insertBefore(node, this.nextSibling);},
		before:      function(node){this.parent().insertBefore(node, this);},
		remove:      function(){this.parent().removeChild(this);},
		replaceWith: function(node){this.parent().replaceChild(node, this);},
		compareDocumentPosition: function(node) {
			return this.contains
			? 0 + (this != node && this.contains(node) && 16)
				+ (this != node && node.contains(this) && 8)
				+ (this.sourceIndex >= 0 && node.sourceIndex >= 0 ? (this.sourceIndex < node.sourceIndex && 4) + (this.sourceIndex > node.sourceIndex && 2) : 1)
			: 0;
		},
		getParentByTagName: function(tag, or_this)
		{
			tag = lc(tag);
			var n = or_this ? this : this.parentNode;
			while (n) {
				if (lc(n.nodeName) == tag) return n;
				n = n.parentNode;
			}
			return null;
		},
		/** DOM Level 4 http://www.w3.org/TR/domcore/#interface-node */
		contains: function(node) {
			while (node) {
				if (this == node) return true;
				node = node.parentNode;
			}
			return false;
		}
	},

	/**
	 * HTML5 abandoned the HTMLDocument Interface.
	 * Instead, it extends the Document interface.
	 */
	Document: {
		/** HTML 5 */
		head:{get:function(){return this.$T('head')[0];}},
		scripts:{get:function(){return this.$T('script');}},
		/** Poodle */
		 $:  function(id){return this.getElementById(id);},
		$B: function() { return this.body||this.documentElement; }, // Quirks||Standard
		$C: function(tagname, a)
		{
			// Build DOM tree from array
			if (is_array(tagname))
			{
				var A=tagname, l=A.length, node=null, r=false, i;
				if (l>0) {
					if (A[1]) {
						r = A[1].ref;
						delete A[1].ref;
					}
					node = this.$C(A[0], A[1]);
					if (a && r) a[r] = node;
					if (!a) a = node;
					for (i=2; i<l; ++i) {
						if (A[i]) {
							if (is_array(A[i][0])) {
								for (var ii=0; ii<A[i].length; ++ii) {
									node.$A(is_string(A[i][ii]) ? this.createTextNode(A[i][ii]) : this.$C(A[i][ii], a));
								}
							} else {
								node.$A(is_string(A[i]) ? this.createTextNode(A[i]) : this.$C(A[i], a));
							}
						}
					}
				}
				return node;
			}
			// Create element
			var ns = this.$B().namespaceURI, n=lc(tagname), o = ns ? this.createElementNS(ns, n) : this.createElement(n);
			if ('iframe' === n || 'object' === n) {
				// Cross browser DOMFrameContentLoaded
				(function(){
					var DOMListeners = [], DOMLoaded, doc=this;
					function DOMReady(e)
					{
						if (!e.target) { e.target=this; }
						if (!DOMLoaded && o===e.target) {
							var fn = arguments.callee;
							DOMLoaded = true;
							doc.off(DFCL, fn);
							if ('object' != n) { e.target.off('load', fn); }
							if (doc.detachEvent) { doc.detachEvent('load', fn); }
							arrCallFunctions(DOMListeners, e, e.target);
						}
					}
					doc.on(DFCL, DOMReady);
					o.onDOMReady = function(fn) { DOMLoaded ? fn() : DOMListeners.push(fn); };
					try { o.on('load', DOMReady); } catch(e){} /** Crash in IE8 on object */
					if (ua.ie && 'object' === n) {
						doc.attachEvent('onload', DOMReady.bind(o));
					}
				})();
				// IE remove border
				if ('iframe' === n) { o.frameBorder = 0; }
			}
			return extendNode(o, a);
		},
		$D: function(){return this;},
		$H: function() { return this.head||this.$T('head')[0]; },
		$Q: $Q,
		$T: $T,
		$W: function(){return this.defaultView||this.parentWindow;}, // parentWindow is IE
		$X: $X,
//		getElementsByName: function(n),
		getScrollPoint: function()
		{
			var w = this.$W(), b = this.isStrict() ? this.documentElement : this.body;
//			if (is_number(w.pageYOffset)) { return new DOMPoint(w.pageXOffset - b.clientLeft, w.pageYOffset - b.clientTop); }
			if (is_number(w.pageYOffset)) { return new DOMPoint(w.pageXOffset, w.pageYOffset); }
			return new DOMPoint(
				b ? max(0, b.scrollLeft) : 0,
				b ? max(b.scrollTop, 0) : 0
			);
		},
		getMaxScrollPoint: function()
		{
			var b = this.isStrict() ? this.documentElement : this.body;
			return new DOMPoint(
				max(0, b.scrollWidth - b.clientWidth),
				max(0, b.scrollHeight - b.clientHeight)
			);
		},
		isStrict:function(){return('BackCompat'!=this.compatMode);},
		/** Event Handling */
		on:     bindEvent,
		bind:   bindEvent,
		off:    unbindEvent,
		unbind: unbindEvent,
		trigger:simulateEvent
	},

	Element: {
		/** Poodle */
		nextElement: function(){return this.nextElementSibling;},
		prevElement: function(){return this.previousElementSibling;},

		$D: function(){return this.ownerDocument;},
		$T: $T,
		$W: function(){return this.$D().$W();},
		$X: $X,
		attr: function(k,v)
		{
			if (!defined(v)) { return this.getAttribute(k); }
			try{ null!==v ? this.setAttribute(k, v) : this.removeAttribute(k); }catch(e){/*no harm, probably k=type|name which fails in IE for "security reasons"*/}
			return this;
		},
		data: function(k,v){return this.attr('data-'+k, v);},
		getElementById: function(id)
		{
			var n = this.ownerDocument.getElementById(id);
			return this.contains(n) ? n : null;
		},
		hasFocus: function(){return this==document.activeElement;},
		toggleAttribute: function(name){this.attr(name, this.hasAttribute(name)?null:'');return this;}
	},

	Event: {
		stop: function(p)
		{
			this.preventDefault();
			if (!p) this.stopPropagation();
		},
		// old http://www.w3.org/TR/2003/WD-DOM-Level-3-Events-20030331/ecma-script-binding.html
		stopImmediatePropagation: function() {
			this.stopPropagation();
			this.immediatePropagationStopped = true;
		},
		isImmediatePropagationStopped: function(){return !!this.immediatePropagationStopped;},
		// custom Poodle methods
		scrollStep: function(){return (this.detail||(this.wheelDelta/-40))/-3;},
		// Extend HTML5 DnD with feature to know which Node is being dragged
		getDraggingNode: function(){return dragNode||(this.dataTransfer?this.dataTransfer.mozSourceNode:null);},
		// IE<10 only supports dataTransfer.getData(Text|URL) or else throws
		// an exception. We prevent this by using this custom function
		getDragData: function(format) {
			var dt = this.dataTransfer;
			if (dt) {
				try {
					// According to the spec for the drag, dragenter, dragleave, dragover
					// and dragend events the drag data store is protected and not accessible.
					// Could check for: dt.types.indexOf('text/plain')
					var v = dt.getData(format);
					if (v) return v;
				} catch(e) {}
				return dt.getData('Text');
			}
		},
		// Chrome setData() failing: http://code.google.com/p/chromium/issues/detail?id=50009
		setDragData: function(format, value) {
			var dt = this.dataTransfer;
			if (dt) {
				if ('Text' != format) dt.setData('Text', format+": "+value);
				try { dt.setData(format, value); } catch(e) {}
			}
			return this;
		},
		setDragEffect: function(v) { this.dataTransfer.effectAllowed = v; return this; }
	},

	HTMLElement: {
		$A: function(t,a)
		{
			if (is_string(t) || is_array(t)) { t = this.$D().$C(t); }
			return this.appendChild(extendNode(t, a));
		},
		$B:$B,
		$C:function(t,a){return this.$D().$C(t,a);},
		$Q:$Q,

		/** HTML 5 */
		click: function(){
			var evt=doc.createEvent('MouseEvents');
			evt.initMouseEvent('click',true,true,this.$W(),1,0,0,0,0,false,false,false,false,0,null);
			this.dispatchEvent(evt);
		},

		show: function(){
			if ('dialog' == lc(this.nodeName)) {
				showDialog(this);
			} else {
				this.hidden = false;
				return this.attr('hidden', null).removeClass('modal');
			}
		},
		showModal: function(){
			if ('dialog' == lc(this.nodeName)) {
				showDialog(this,1);
			} else {
				this.hidden = false;
				this.attr('hidden', null).addClass('modal');
			}
		},
		close: function(v){
			// HTMLDialogElement?
			if ('dialog' == lc(this.nodeName)) {
				this.returnValue = v;
				this.attr('open',null).parent().replaceWith(this);
				window.onkeydown = null;
			} else {
				this.hidden = false;
				return this.attr('hidden', '');
			}
		},
		hide: function(){return this.close();},

		getBoundingPageRect: function()
		{
			var br = this.getBoundingClientRect(), s=this.$D().getScrollPoint();
			return new DOMRect(br.left+s.x, br.top+s.y, br.width, br.height);
		},
		getBoundingPageX: function() { return this.getBoundingPageRect().x; },
		getBoundingPageY: function() { return this.getBoundingPageRect().y; },
		/** http://www.w3.org/TR/cssom-view/#the-getclientrects-and-getboundingclient */
		getBoundingClientRect: function()
		{
			var n=this, x=0, y=0, o=n, f;
			while (o) {
				x += o.offsetLeft;
				y += o.offsetTop;
				f = ('fixed' == o.css('position'));
				if (f) { break; }
				o = o.offsetParent;
				if (o && o.offsetParent)
				{
					x -= o.scrollLeft;
					y -= o.scrollTop;
				}
			}
			if (!f) {
				o = n.$D().getScrollPoint();
				x -= o.x;
				y -= o.y;
			}
			return { top:y, right:x+n.offsetWidth, bottom:y+n.offsetHeight, left:x };
		},

		// http://www.quirksmode.org/dom/w3c_css.html
		css: function(rule, force)
		{
			var n=this, v=null;
			try {
				if (!force) { v = n.getCSSPropValue(rule); }
				if (!v) { v = n.$W().getComputedStyle(n, null).getPropertyValue(rule); }
			} catch (e) {}
			return v;
		},

		getCSSPropValue: function(n)
		{
			try {
				var s=this.style;
				return s.getPropertyValue(n) || s.getPropertyValue(cssPrefix+n);
			} catch (e) {}
		},

		setCSSProperty: function(n,v)
		{
			/* IE9 value must be a string or you get an invalid argument runtime error */
			if (v && v.toString) { v = v.toString(); }
			var s=this.style;
			try{
				if (!is_string(v) || !v.length) {
					s.removeProperty(cssPrefix+n);
					s.removeProperty(n);
				} else {
					s.setProperty(cssPrefix+n, v, null);
					s.setProperty(n, v, null);
				}
			}catch(e){console.error(e);}
			try{
				s[cssrule(cssPrefix+n)] = s[cssrule(n)] = v;
			}catch(e){console.error(e);}
			return this;
		},

		setCSS:function(css){
			for (var p in css) { this.setCSSProperty(p, css[p]); }
			return this;
		},

		addClass: function(name) { this.classList.add(name); return this; },
		getClass: function()  { return this.attr('class') || this.className; },
		setClass: function(v) { return this.attr('class', v ? v.trim() : v); },
		hasClass: function(name) { return this.classList.contains(name); },
		replaceClass: function(from, to) { return this.setClass((this.getClass()||'').replace(from, to)); },
		removeClass: function(name) { this.classList.remove(name); return this; },
		toggleClass: function(name) { this.classList.toggle(name); return this; },

		getNextByNodeName: function(name)
		{
			var n = this;
			name = name||n.nodeName;
			while (n = n.next()) { if (n.nodeName == name) return n; }
		},
		getPrevByNodeName: function(name)
		{
			var n = this;
			name = name||n.nodeName;
			while (n = n.prev()) { if (n.nodeName == name) return n; }
		},

		getHeight: function(def)
		{
			/** .client* is without border, offset* with border */
			return max(this.clientHeight, (def?def:0)) - intval(this.css('padding-top')) - intval(this.css('padding-bottom'));
		},

		getMousePos:function(e)
		{
			var br = this.getBoundingClientRect();
			return new DOMPoint(intval(e.clientX-br.x), intval(e.clientY-br.y));
		},

		getWidth: function(def)
		{
			return max(this.clientWidth, (def?def:0)) - intval(this.css('padding-left')) - intval(this.css('padding-right'));
		},

		hasFixedPosition: function()
		{
			var n = this, f = false;
			while (n && !f) {
				f |= ('fixed' == n.css('position'));
				n = n.offsetParent;
			}
			return f;
		},

		appendHTML: function(txt) { return this.html(this.innerHTML + txt); },

		html: function(v) {
			if (defined(v)) {
				try {
					this.innerHTML = v;
					return this;
				} catch (e) {
					console.error(e);
					try {
						var doc = new DOMParser().parseFromString(v, 'text/xml').documentElement;
						if (lc(doc.nodeName) == 'parsererror') {
							console.debug(doc.firstChild.data);
						} else {
							this.$A(this.$D().importNode(doc, true));
							return this;
						}
					} catch (e) {
						console.error(e);
					}
					return false;
				}
			}
			return this.innerHTML;
		},
		// 'text' property is used by A. Use '<![CDATA['+v+']]>' ??
		txt:  function(v) { return defined(v) ? this.html(htmlspecialchars(v)) : this.textContent; },

		/** Event Handling */
		on:     bindEvent,
		bind:   bindEvent,
		off:    unbindEvent,
		unbind: unbindEvent,
		trigger:simulateEvent
	},

	HTMLFormElement: {
		// Events.addBeforeSubmit
		addEventBeforeSubmit: function(fn)
		{
			var f = this;
			if (!f.beforesubmit) {
				f.beforesubmit = [];
				f.addEventListener('submit', function(e){arrCallFunctions(f.beforesubmit, e, this);}, false);
			}
			if (fn) f.beforesubmit.push(fn);
			return true;
		}
		/** returns all form fields as a query string */
		,asQuery: function(){
			// handle as application/x-www-form-urlencoded
			var n, i=0, v='', y;
			while (n = this.elements[i++])
			{
				if (!n.name) { continue; }
				switch (n.type)
				{
				case 'radio':
				case 'checkbox':
					if (!n.checked) break;
				case 'text':
				case 'select-one':
				case 'hidden':
				case 'password':
				case 'textarea':
					v = v.addQueryParam(n.name, n.value);
					break;
				case 'select-multiple':
					if (n.options.length) {
						for (y=0; y<n.options.length; ++y) {
							if (n.options[y].selected) {
								v = v.addQueryParam(n.name, n.options[y].value);
							}
						}
					}
					break;
				// skip: fieldset, file, submit and reset
				}
			}
			return v;
		}
		/** returns true when one ore more form fields are changed */
		,hasChanges: function(){
			var n, i = 0;
			while (n = this.elements[i++]) {
				switch (lc(n.nodeName))
				{
				case "input":
					if ("submit" == n.type || "reset" == n.type || "button" == n.type) {
						continue;
					}
					if ("checkbox" == n.type || "radio" == n.type) {
						if (n.defaultChecked != n.checked) { return true; }
						break;
					}
				case "textarea":
					if (n.defaultValue != n.value) { return true; }
					break;
				case "select":
					var o = n.options, oi = o.length;
					while (--oi) {
						if (o[oi].defaultSelected != o[oi].selected) {
							return true;
						}
					}
				}
			}
			return false;
		}
		,setChangesAsDefault: function(){
			var n, i = 0;
			while (n = this.elements[i++]) {
				switch (lc(n.nodeName))
				{
				case "input":
					if ("submit" == n.type || "reset" == n.type || "button" == n.type) {
						continue;
					}
					if ("checkbox" == n.type || "radio" == n.type) {
						n.defaultChecked = n.checked;
						break;
					}
				case "textarea":
					n.defaultValue = n.value;
					break;
				case "select":
					var o = n.options, oi = o.length;
					while (--oi) {
						o[oi].defaultSelected = o[oi].selected;
					}
				}
			}
		}
		/** Mark all checkboxes with the given name as checked */
		,checkAll: function(name, uncheck) {
			this.$Q('input[type=checkbox][name*="'+name+'"]').forEach(function(o){o.checked = !uncheck;});
		}
		,uncheckAll: function(name) { this.checkAll(name, 1); }
	},

	HTMLInputElement: {
		labels:{get:function(){return this.id?this.$D().$Q('label[for='+this.id+']'):null;}}
		// http://dev.w3.org/html5/spec/the-input-element.html
		,min:{ get:function(){return this.getMin();}, set:function(v){this.setMin(v);} }
		,max:{ get:function(){return this.getMax();}, set:function(v){this.setMax(v);} }
		,step:{
			get:function(){return this.getStep();},
			set:function(v){if(this.getStep()!=v)this.attr('step',('any'!=v)?max(0,parseFloat(v)):v);}
		}
		// dev.w3.org/html5/spec/common-input-element-attributes.html#dom-input-stepup
		,stepDown: function(n){
			n = intval(Math.abs(n))||1;
			this.value = max(this.min, Math.min(this.max, floatval(this.value) - (0<this.step?this.step*n:n)));
		}
		,stepUp:   function(n){
			n = intval(Math.abs(n))||1;
			this.value = max(this.min, Math.min(this.max, floatval(this.value) + (0<this.step?this.step*n:n)));
		}
		// types = ['text','search','url','tel','email','password'];
		,checkValidity: function(){return true;}
		,valueAsDate:{
			// Throws an InvalidStateError exception if the control isn't date- or time-based.
			get:function(){return this.getValueAsDate();},
			set:function(date){this.setValueAsDate(date);}
		}
		,valueAsNumber:{
			get:function(){return floatval(this.value);},
			set:function(v){this.value=v;}
		}
		// Poodle
		,getDateFormat:function(){
			var t = lc(this.attr("type")+" "+this.getClass()).match(dateType),
			f = {
				date:'Y-m-d',
				datetime:'Y-m-d\\TH:i:s\\Z',
				'datetime-local':'Y-m-d\\TH:i:s', // DATE_RFC3339
				month:'Y-m',
				time:'H:i',
				week:'Y-\\WW'
			};
			if (t && 'time' === t[1]) {
				var s = this.getStep();
				if (s < 60) { f.time += ":s"; }
				if (s <  1) { f.time += ".u"; }
			}
			return t ? f[t[1]] : false;
		}
		,getValueAsDate:function(){
			var f = this.getDateFormat(), v = this.value;
			return (v && f) ? this.value.toDate(f) : null;
		}
		,setValueAsDate:function(d){
			var f = this.getDateFormat();
			if (f && (!d || (d >= this.getMinDate() && d <= this.getMaxDate()))) {
				this.value = d ? d.format(f) : null;
			}
		}
		,getMinDate:function(){
			var f = this.getDateFormat(), d = this.getMin();
			return (d && f) ? d.toDate(f) : new Date(0,0,1,0,0,0,0);
		}
		,getMaxDate:function(){
			var f = this.getDateFormat(), d = this.getMax();
			return (d && f) ? d.toDate(f) : new Date(2100,0,1,0,0,0,0);
		}
		,getMin:function(){return getInputMinMax(this, 'min');}
		,getMax:function(){return getInputMinMax(this, 'max');}
		,setMin:function(v){setInputMinMax(this, 'min', v);}
		,setMax:function(v){setInputMinMax(this, 'max', v);}
		,getStep:function(){
			if (this.hasClass('time') || this.hasClass('datetime') || this.hasClass('datetime-local') || 0<=this.attr('type').indexOf('time')) {
				return this.getStepSeconds();
			}
			var s = this.attr('step'), v = parseFloat(s);
			return (s&&'any'===lc(s)) ? 0 : (isNaN(v) || 0>=v) ? 1 : v;
		}
		,getStepSeconds:function(){
			// Time: default step is 60 seconds.
			var s = this.attr('step'), v = parseFloat(s);
			v = (s && 'any'===lc(s)) ? 0.001 : (isNaN(v) || 0>=v) ? 60 : v;
			if (v < 1) {
				// 0.001,0.002,0.004,0.005,0.008,0.01,0.02,0.025,0.04,0.05,0.1,0.125,0.2,0.25,0.5
				if (!v || 0 !== (1000 % intval(v*1000))) { v = 0.001; }
			} else {
				v = intval(v);
				// 1,2,3,4,5,6,10,12,15,20,30
				if (v > 1 && v < 60 && 0 !== (60 % v)) { v = 1; }
				// 120,180,240,300,360,600,720,900,1200,1800
				if (v > 60 && v < 3600 && 0 !== (3600 % v)) { v = 60; }
				// 7200,10800,14400,21600,28800,43200
				if (v > 3600 && v < 86400 && 0 !== (86400 % v)) { v = 3600; }
			}
			return v;
		}
		,initValue:function(v){
			if ("checkbox"==this.type || "radio"==this.type) {
				this.checked = this.defaultChecked = !!v;
			} else {
				this.value = this.defaultValue = v;
			}
			this.trigger("change");
		}
	},

	HTMLSelectElement: {
		currentOption: function() {
			var i = this.selectedIndex, o = this.options;
			return (0 > i || i > o.length-1) ? new Option("","") : o[i];
		},
		setSelectedByValue: function(v, d) {
			var o = this.options, i=0, r = false;
			for (;i<o.length;++i) {
				if (v == o[i].value) {
					r = true;
					if (d) {
						o[i].defaultSelected = true;
					}
					this.selectedIndex = i;
					this.trigger('change');
					if (!d) {
						break;
					}
				} else if (d) {
					o[i].defaultSelected = false;
				}
			}
			return r;
		}
	},

	HTMLIFrameElement: { $Doc: contentDoc, $Win: contentWin },
	HTMLObjectElement: { $Doc: contentDoc, $Win: contentWin },

	HTMLStyleElement: {},

	Window: {
		$B:function(){console.error('Window.$B obsolete');},
		$C:function(){console.error('Window.$C obsolete');},
		$D:function(){return this.document;},
		$Q:function(){console.error('Window.$Q obsolete');},
		$W:function(){console.error('Window.$W obsolete');},
		/** Event Handling */
		on:     bindEvent,
		bind:   bindEvent,
		off:    unbindEvent,
		unbind: unbindEvent,
		trigger:(ua.khtml||ua.opera)?function(){}:simulateEvent, // Fails on Konqueror and Opera
		getHash:function(){return this.location.href.replace(/^[^#]+#?/, '');},
		setHash:function(id){
			var n=this.$Q('#'+id,1);
			if (n) n.id='';
			this.location.hash='#'+id;
			if (n) n.id=id;
		}
	}
};

// In Chrome Element.prototype.classList is undefined, yet it exists
if (!defined((doc.body||doc.documentElement).classList)) {
	/**
	 * Simulate HTML5 DOMTokenList
	 * Chrome 8, Firefox 3.6, IE 10, Opera 11.5, Safari 5.1
	 */
	DOM.Element.classList = {get:function(){
		var o = this, c = o.className;
		function getIndex(a, token) {
			if ('' === token) { throw 'SYNTAX_ERR'; }
			if (/\s/.test(token)) { throw 'INVALID_CHARACTER_ERR'; }
			return a.indexOf(token);
		}
		function setAttr(a){
	//		o.className = a.join(' ')
			o.attr('class', a.toString());
		}
		c = c.trim ? c.trim() : c.baseVal.trim(); // SVGAnimatedString
		c = c.length ? c.split(/\s+/) : [];
		c.contains = function(token) { return -1 !== getIndex(this, token); };
		c.add = function(token) {
			if (!this.contains(token)) {
				this.push(token);
				setAttr(this);
			}
		};
		c.remove = function(token) {
			var i = getIndex(this, token);
			if (-1 !== i) {
				this.splice(i, 1);
				setAttr(this);
			}
		};
		c.toggle = function(token) {
			var a = !this.contains(token);
			a ? this.add(token) : this.remove(token);
			return a;
		};
		c.toString = function() { return c.join(' ').trim(); };
		return c;
	}};
}


/** ECMAScript 6 Array */
if (!Array.from) {
	Array.from = function(o, cb, c) {
		return Object(o).length
			? (is_function(cb) ? Array.prototype.map.call(o, cb, c) : Array.prototype.slice.call(o, 0))
			: [];
	};
}
if (!Array.filter) {
	Array.filter = function(o,fn){return Array.prototype.filter.call(o,fn);};
}


/**
 * Ajax
 * http://www.w3.org/TR/XMLHttpRequest/
 */
var PoodleXHR_requests = 0;
PoodleXHR = function()
{
	/** Privileged */
	this.async = true;
	this.oncomplete = null;
	this.onprogress = null;
	this.onresponseline = null;
	this.afteropen = null;

	this.get = function(uri, query) {
		if (!uri) uri = doc.location.href;
		if (query) uri += (-1<uri.indexOf('?')?'&':'?') + PoodleXHR.toQueryString(query);
		return request('GET', uri, null);
	};

	this.post = function(uri, data) {
		return request('POST', uri, data);
	};

	this.put = function(uri, data) {
		return request('PUT', uri, data);
	};

	this['delete'] = function(uri, query) {
		if (!uri) uri = doc.location.href;
		if (query) uri += (-1<uri.indexOf('?')?'&':'?') + PoodleXHR.toQueryString(query);
		return request('DELETE', uri, null);
	};

	this.abort = function() {
		var r = this.xhr;
		if (r) {
			if (r.readyState && r.abort) {
				r.onreadystatechange = function(){}; // null makes IE crash
				r.abort();
				decPXHR();
			}
			r.onreadystatechange = onStateChange.bind(this);
		}
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	};

	this.fromJSON = function() {
		return ("json"==this.type ? JSON.parse(this.xhr.responseText) : null);
	};

	var PXHR = this,
		xhr,
		bytesUploaded,
		bytesTotal,
		updateTime,
		elapsedTime,
		startTime,

		pollTimer,
		prevDataLength,
		lastPos;

	/** Public */
	try {
		xhr = new XMLHttpRequest();
	} catch(e) {
		xhr = MSXML('XMLHTTP');
	}
	/**
	 * XHR level 2
	 * http://www.w3.org/TR/XMLHttpRequest2/
	 */
	this.on = function(type,fn){
		if ("progress" === type || "complete" === type) {
			this["on"+type] = fn;
		} else {
			bindEvent.call(xhr, type, fn);
		}
		return this;
	};
	this.off = function(type,fn){unbindEvent.call(xhr, type, fn); return this;};
	this.bind = function(type,fn){return this.on(type,fn);};
	this.unbind = function(type,fn){return this.off(type,fn);};
//	this.on("load",  uploadComplete);
//	this.on("error", uploadFailed);
//	this.on("abort", uploadCanceled);
	this.xhr = xhr;
	this.getTransferStats = function()
	{
		var bps = bytesUploaded * 1000 / elapsedTime,
		bytesRemaining = bytesTotal - bytesUploaded;
		return {
			bps:              bps,
			speed:            bps.bytesToSpeed(0),
			remainingBytes:   bytesRemaining,
			remainingSize:    bytesRemaining.bytesToSize(),
			remainingTime:    max(0,Math.floor(bytesRemaining/bps - (new Date().getTime()-updateTime)/1000)).secondsToTime(),
			transferredBytes: bytesUploaded,
			transferredSize:  bytesUploaded.bytesToSize(0),
			percentComplete:  Math.min(100,bytesUploaded * 100 / bytesTotal)
		};
	};
	if (xhr.upload) {
		xhr.upload.addEventListener("progress", function(e){
			if (e.lengthComputable) {
				bytesUploaded = intval(e.loaded);
				bytesTotal    = intval(e.total);
				updateTime    = new Date().getTime();
				elapsedTime   = updateTime - startTime;
				onProgress(PXHR);
			}
		}, false);
	}

	/** Private */
	function decPXHR() { if (--PoodleXHR_requests<1) document.$B().removeClass('xhr-loading'); }

	function onProgress(PXHR)
	{
		if (is_function(PXHR.onprogress)) try {
			PXHR.onprogress.call(PXHR, PXHR.getTransferStats());
		} catch(e) {}
	}

	function onStateChange()
	{
		if (4 == this.xhr.readyState) {
			if (this.form) { this.form.removeClass('xhr'); }
			this.type = this.xhr.getResponseHeader('content-type').replace(/^.*\/([a-z]+).*$/, '$1');
			bytesUploaded = bytesTotal;
			onProgress(this);
			if (is_function(this.oncomplete)) { try { this.oncomplete(this); }catch(e){} }
			decPXHR();
		}
	}

	function handleStream()
	{
		var xhr = PXHR.xhr;
		if (xhr.readyState != 4 && xhr.readyState != 3) {
			return;
		}
		if (4 == xhr.readyState) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
		if (xhr.status != 200) {
			return;
		}
		// In konqueror xhr.responseText is sometimes null here...
		if (xhr.responseText === null) {
			return;
		}
		while (prevDataLength != xhr.responseText.length) {
			prevDataLength = xhr.responseText.length;
			var response = xhr.responseText.substring(lastPos),
				lines = response.split('\n'),
				i = 0;
			lastPos += response.lastIndexOf('\n') + 1;
			if (xhr.readyState == 3 && response[response.length-1] != '\n') {
				lines.pop();
			}
			for (; i < lines.length; ++i) {
				if (lines[i].length) {
					PXHR.onresponseline.call(PXHR, lines[i]);
				}
			}
		}
		if (4 == xhr.readyState) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	function request(method, uri, data)
	{
		if (!PXHR.xhr) { return false; }
		PXHR.abort();
		if (PXHR.form) { PXHR.form.addClass('xhr'); }
		if (++PoodleXHR_requests<2) { document.$B().addClass('xhr-loading'); }

		bytesUploaded = bytesTotal = elapsedTime = 0;
		startTime = updateTime = new Date().getTime();

		if (data) { data = PoodleXHR.toQueryString(data); }

		var r = PXHR.xhr;
		r.open(method, uri || doc.location.href, !!PXHR.async);
		if (is_function(PXHR.afteropen)) try {
			/**
			 * Old spec says withCredentials can only be set after "open"
			 * Android browser requires this
			r.withCredentials = true;
			 */
			PXHR.afteropen.call(PXHR);
		} catch(e) {}
		r.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		// http://www.iana.org/assignments/media-types/
		r.setRequestHeader('Accept', 'application/xml,text/xml,text/html,application/xhtml+xml,application/javascript,application/json,text/plain,*/*');
		if (data && is_string(data)) {
			r.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		}
		if (is_function(PXHR.onresponseline)) {
			prevDataLength = lastPos = 0;
			pollTimer = setInterval(handleStream, 100);
		}
		r.send(data?data:null);
		if (!PXHR.async) { onStateChange.call(PXHR); }
		return true;
	}
};

// Serialize an object/array into a query string
PoodleXHR.toQueryString = function(o, prefix)
{
	if (is_string(o) || is_function(o.append)) { return o; }
	var str = [];
	function add(v, k) {
		str.push(is_object(v) ?
			PoodleXHR.toQueryString(v, k) :
			encodeURIComponent(k) + "=" + encodeURIComponent(v));
	}
	if (prefix && is_array(o)) {
		o.forEach(function(v,i){add(v, prefix+"["+i+"]");});
	} else if (is_object(o)) {
		for (var p in o) {
			if (o.hasOwnProperty(p)) {
				add(o[p], prefix ? prefix+"["+p+"]" : p);
			}
		}
	}
	return str.join("&");
};

/** Start Selectors API
 *
 * http://www.w3.org/TR/CSS2/selector.html
 * http://www.w3.org/TR/css3-selectors/
 * Custom:
 *      [class!=made_up] is equal to :not([class=made_up])
 *      :contains(Selectors)
 */

(function(){

	var UID=1;
	$uid = function(node){return node.uniqueID||(node.uniqueID=UID++);};

	var Cache = {nth:{}, parsed:{}},

	RegExps = {
		id: (/#([\w-]+)/),
		tag: (/^(\w+|\*)/),
		quick: (/^(\w+|\*)$/),
		classname: (/^\.(\w+)$/),
		splitter: (/\s*([+>~\s])\s*([a-zA-Z#.*:\[])/g),
		combined: (/\.([\w-]+)|\[([\w-]+)(?:([!*^$~|]?=)(["']?)([^\4]*?)\4)?\]|:([\w-]+)(?:\(["']?(.*?)?["']?\)|$)/g),
		nth:/^([+-]?\d*)?([a-z]+)?([+-]?\d*)?$/
	},

	Getters = {
		' ': function(found, node, tag, id, uniques) {
	//		return Array.filter(getByTagAndID(node, tag, id), function(elem){return chk(elem, uniques)});
			var nodes = getByTagAndID(node, tag, id), i = 0, l = nodes.length, n;
			for (;i<l;++i) {
				n = nodes[i];
				if (chk(n, uniques)) found.push(n);
			}
			return found;
		},

		// E > F an F element child of an E element
		'>': function(found, node, tag, id, uniques) {
			var nodes = getByTagAndID(node, tag, id), i = 0, l = nodes.length, n;
			for (;i<l;++i) {
				n = nodes[i];
				if (n.parentNode === node && chk(n, uniques)) found.push(n);
			}
			return found;
		},

		// E + F an F element immediately preceded by an E element
		'+': function(found, node, tag, id, uniques) {
			while (node = node.nextSibling) {
				if (1 === node.nodeType) {
					if (chk(node, uniques) && byTag(node, tag) && byID(node, id)) found.push(node);
					break;
				}
			}
			return found;
		},

		// E ~ F an F element preceded by an E element
		'~': function(found, node, tag, id, uniques) {
			while (node = node.nextSibling) {
				if (1 === node.nodeType) {
					if (!chk(node, uniques)) break;
					if (byTag(node, tag) && byID(node, id)) found.push(node);
				}
			}
			return found;
		}
	},

	/** http://www.w3.org/TR/ElementTraversal/ */
	firstChild = function(elem){return !elem.previousElementSibling;},
	lastChild = function(elem){return !elem.nextElementSibling;},

	Pseudo = {
		/**
		 * W3C Pseudo-classes
		 * http://www.w3.org/TR/CSS2/selector#pseudo-class-selectors
		 * http://www.w3.org/TR/css3-selectors/#pseudo-classes
			:first-of-type       matches the first child element of the specified element type
			:last-of-type        matches the last child element of the specified element type
			:nth-last-child(N)   matches elements on the basis of their positions within a parent element’s list of child elements
			:nth-of-type(N)      matches elements on the basis of their positions within a parent element’s list of child elements of the same type
			:nth-last-of-type(N) matches elements on the basis of their positions within a parent element’s list of child elements of the same type
			:only-of-type        matches an element that’s the only child element of its type
			:root                matches the element that’s the root element of the document
			:target              matches an element that’s the target of a fragment identifier in the document’s URI
		*/

		checked:  function(elem){return !!('checked' in elem ? elem.checked : elem.selected);},
		disabled: function(elem){return elem.disabled;}, // is attribute
		enabled:  function(elem){return !elem.disabled;},

		empty: function(elem){return !(elem.firstElementChild || (elem.textContent || '').length);},

		not: function(elem,selector){return !match(elem, selector);},

		'first-child': firstChild,
		'last-child':  lastChild,
		'only-child':  function(elem){return firstChild(elem) && lastChild(elem);},

		'nth-child': function(elem, argument, local) {
			argument = (argument === undefined) ? 'n' : argument;
			var parsed = parseNthArgument(argument);
			if (parsed.mode !== 'n') return Pseudo[parsed.mode](elem, parsed.a, local);
			var count = 0;
			local.positions = local.positions || {};
			var uid = $uid(elem);
			if (!local.positions[uid]) {
				var self = elem;
				while (self = self.previousSibling) {
					if (1 !== self.nodeType) continue;
					count ++;
					var position = local.positions[$uid(self)];
					if (position !== undefined) {
						count = position + count;
						break;
					}
				}
				local.positions[uid] = count;
			}
			return (local.positions[uid] % parsed.a === parsed.b);
		},

		// custom pseudo selectors

		contains:function(elem, text){return (elem.textContent || '').indexOf(text) > -1;},

		index: function(elem, index) {
			var root = elem.parentNode;
			var i = 0,
				tret = root.children||root.childNodes,
				ei = (elem['_i']||-1),
				rl = (root['_l']||-1);
			if (tret) {
				var l = tret.length, te;
				// we calculate the parent length as a cheap way to invalidate the
				// cache. It's not 100% accurate, but it's much more honest than what
				// other libraries do
				if (rl != l || ei < 0 || rl < 0) {
					root['_l'] = l;
					ei = -1;
					te = root.firstElementChild;
					while (te) { te['_i'] = i++; te = elem.nextElementSibling; }
					ei = elem['_i']||-1;
				}
			}
			return (ei === index);
		}
	},

	Attributes = {
		 '=': function(e, name, v){return v === e.getAttribute(name);},
		'*=': function(e, name, v){return -1 < (e.getAttribute(name)||'').indexOf(v);},
		'^=': function(e, name, v){return v === (e.getAttribute(name)||'').substr(0, v.length);},
		'$=': function(e, name, v){return v === (e.getAttribute(name)||'').slice(-v.length);},
		'~=': function(e, name, v){return (' '+e.getAttribute(name)+' ').includes(' '+v+' ');},
		'|=': function(e, name, v){return 0 === ((e.getAttribute(name)||'')+'-').indexOf(v+'-');},
		// Custom
		'!=': function(e, name, v){return v != e.getAttribute(name);}
	};

	/** Filters */

	function byTag(node,tag){return ('*' === tag || (node.tagName && lc(node.tagName) === tag));}

	function byID(node, id){return (!id || id === node.id);}

	/** Utils */

	function chk(node, uniques)
	{
		if (!uniques) return true;
		var uid = $uid(node);
		return (uniques[uid] ? false : uniques[uid]=true);
	}

	function parseNthArgument(argument)
	{
		if (Cache.nth[argument]) return Cache.nth[argument];
		var parsed = argument.match(RegExps.nth);
		if (!parsed) return false;
		var a = intval(parsed[1],1),
			b = intval(parsed[3]),
		mode = parsed[2];
		if (0 !== a) {
			b--;
			while (b < 1) b += a;
			while (b >= a) b -= a;
		} else {
			a = b;
			mode = 'index';
		}
		switch (mode) {
			case 'n':    parsed = {a: a, b: b, mode: 'n'}; break;
			case 'odd':  parsed = {a: 2, b: 0, mode: 'n'}; break;
			case 'even': parsed = {a: 2, b: 1, mode: 'n'}; break;
			case 'first':parsed = {mode: 'first-child'}; break;
			case 'last': parsed = {mode: 'last-child'}; break;
			case 'only': parsed = {mode: 'only-child'}; break;
			default: parsed = {a: (a - 1), mode: 'index'};
		}

		return Cache.nth[argument] = parsed;
	}

	function parseTagAndID(selector)
	{
		var tag = selector.match(RegExps.tag), id = selector.match(RegExps.id);
		return [tag ? tag[1] : '*', id ? id[1] : false];
	}

	function getByTagAndID(ctx, tag, id)
	{
		if (id) {
			var n = ctx.getElementById(id);
			return (n && byTag(n, tag)) ? [n] : [];
		}
		return ctx.getElementsByTagName(tag);
	}

	function parseSelector(selector)
	{
		selector = selector.replace('nth-child(even)','nth-child(2n)').replace('nth-child(odd)','nth-child(2n+1)');
		if (Cache.parsed[selector]) return Cache.parsed[selector];
		var m, parsed = {classes: [], pseudos: [], attributes: []};
		while (m = RegExps.combined.exec(selector)) {
			if (m[1]) {
				parsed.classes.push(m[1]);
			} else if (m[2]) {
				parsed.attributes.push({name: m[2], operator: m[3], value: m[5]});
			} else if (m[6]) {
				if (Pseudo.hasOwnProperty(m[6])) parsed.pseudos.push({parser: Pseudo[m[6]], argument: m[7]});
				else parsed.attributes.push({name: m[6], operator: '=', value: m[7]});
			}
		}
		if (!parsed.classes.length) delete parsed.classes;
		if (!parsed.attributes.length) delete parsed.attributes;
		if (!parsed.pseudos.length) delete parsed.pseudos;
		return Cache.parsed[selector] = (parsed.classes || parsed.attributes || parsed.pseudos) ? parsed : null;
	}

	function filter(node, parsed, local)
	{
		var i;
		if (parsed.classes) {
			i = parsed.classes.length;
			while (i--) {
				if (!node.hasClass(parsed.classes[i])) return false;
			}
		}
		if (parsed.attributes) {
			i = parsed.attributes.length;
			while (i--) {
				var a = parsed.attributes[i], o=a.operator, n=a.name, v=a.value;
				if (o) return Attributes[o](node, n, v);
				return (v && v.lenght) ? v==node.getAttribute(n) : node.hasAttribute(n);
			}
		}
		if (parsed.pseudos) {
			i = parsed.pseudos.length;
			while (i--) {
				var psd = parsed.pseudos[i];
				if (!psd.parser(node, psd.argument, local)) return false;
			}
		}
		return true;
	}

	function match(node, selector)
	{
		if (!selector || (selector === node)) return true;
		var tagid = parseTagAndID(selector), tag = tagid[0], id = tagid[1];
		if (!byID(node, id) || !byTag(node, tag)) return false;
		var parsed = parseSelector(selector);
		return (parsed) ? filter(node, parsed, {}) : true;
	}

	PoodleSelector = function(selector)
	{
		var expressions = selector.split(',');

		this.search = function(parent, expression, local)
		{
			var nodes, filtered, node, i=0,
			splitters = [],
			selectors = expression.trim().replace(RegExps.splitter, function(m0, m1, m2){
				splitters.push(m1);
				return ':)' + m2;
			}).split(':)'),
			l = selectors.length;
			for (;i<l;++i)
			{
				var selector = selectors[i];
				if (0 === i && RegExps.quick.test(selector)) {
					nodes = parent.getElementsByTagName(selector);
					continue;
				}
				if (0 === i && RegExps.classname.test(selector)) {
					nodes = parent.getElementsByClassName(selector.substr(1));
					continue;
				}
				var tagid = parseTagAndID(selector),
				tag = tagid[0], id = tagid[1];
				if (0 === i) {
					nodes = getByTagAndID(parent, tag, id);
				} else {
					var uniques = {}, found = [], j = 0, k = nodes.length;
					for (;j<k;++j) found = Getters[splitters[i-1]](found, nodes[j], tag, id, uniques);
					nodes = found;
				}
				var parsed = parseSelector(selector);
				if (parsed) { nodes = Array.filter(nodes, function(elem){return filter(elem, parsed, local);}); }
			}
			return Array.from(nodes);
		};

		this.exec = function(node, one)
		{
			var i = 0, nodes=[], l = expressions.length, local={};
			for (; i<l; ++i) {
				nodes = nodes.concat(this.search(node, expressions[i], local));
				if (one && nodes[0]) return nodes[0];
			}
			var uniques = {};
			return one ? null : nodes.filter(function(node){return chk(node, uniques);});
		};
	};

})();

/** End Selectors API */

PoodleDOM = function(w)
{
	this.L10N = new PoodleL10N;

	var k, doc = w.document, P = this, DCVC='DOMControlValueChanged';

	/**
	 * short notations
	 */
	this.$=function(id) {return doc.$(id);};
	P.$B = function()   {return doc.$B();};
	P.$C = function(t,a){return doc.$C(t,a);};
	P.$D = function()   {return doc;};
	P.$H = function()   {return doc.$H();};
	P.$Q = function(s,n){return doc.$Q(s,n);};
	P.$T = function(s)  {return doc.$T(s);};
	P.$W = function()   {return w;};

	/**
	 * DOM Prototype
	 */
	P.DOM = DOM;
	P.extend = extendObj;
	P.extendDOM = extendDOM;
	P.extendNode = extendNode;

	// Safari 4 issue
	if (ua.safari) {
		DOM.HTMLDocument = DOM.Document;
		extendObj(DOM.Element, DOM.Node);
		extendObj(DOM.HTMLElement, DOM.Element);
		for (k in w) {
			if ('HTML'===k.substr(0,4) && 'HTMLDocument'!=k && 'HTMLElement'!=k) {
				if (DOM[k]) extendObj(DOM[k], DOM.HTMLElement);
				else DOM[k] = DOM.HTMLElement;
			}
		}
	}

	// ClientRect is renamed to DOMRect in specs
	extendObj(DOM.ClientRect, DOM.DOMRect);
	/** https://www.w3.org/TR/geometry-1/ */
	if (!w.DOMRect) {
		function DOMRect(x,y,w,h) {
			this.x = x;
			this.y = y;
			this.width = w;
			this.height = h;
		}
		extendObj(DOM.DOMRect, {
			left: { get:function(){return this.x;} },
			top: { get:function(){return this.y;} },
			right: { get:function(){return this.x+this.width;} },
			bottom: { get:function(){return this.y+this.height;} }
		});
		w.DOMRect = DOMRect;
	}
	if (!w.DOMPoint) {
		w.DOMPoint = function(x,y,z,w) {
			this.x = x;
			this.y = y;
			this.z = z;
			this.w = w;
		};
	}

	for (k in DOM) { if (w[k] && w[k].prototype) extendDOM(w[k].prototype, DOM[k]); }

	// Not working in IE<9, Safari<5.1, Opera<11.60 (should, but fails)
	function watchProp(o, name)
	{
		var opd = Object.getOwnPropertyDescriptor(o, name), fn="set"+name.ucfirst();
		if (opd && opd.set) {
			Object.defineProperty(o, name, {set:function(v){
				var ov=this[name];
				opd.set.call(this, v);
				v=this[name];
				if (ov!=v) this.trigger(DCVC,{propertyName:name, newValue:v, prevValue:ov});
			}});
			o[fn] = function(v){this[name] = v;};
			return true;
		}
		console.error("Failed to watch property '"+name+"'");
		o[fn] = function(v){
			var ov=this[name];
			this[name] = v;
			if (ov!=v) { this.trigger(DCVC,{propertyName:name, newValue:v, prevValue:ov}); }
		};
		return false;
	}
	//var el=['HTMLSelectElement','HTMLTextAreaElement','HTMLInputElement'], i=0;
	var o = w.HTMLInputElement.prototype;
	watchProp(o, 'value');
	watchProp(o, 'checked');
	watchProp(w.HTMLTextAreaElement.prototype, 'value');

	/**
	 * IE/WebKit DOM Window/Document workaround
	 */
	if (!w.on) { extendDOM(w, DOM.Window); }
	if (!doc.on) { extendDOM(doc, DOM.Document); }

	// simulate_hashchange does all the work of triggering the window.onhashchange
	// event for browsers that don't natively support it, including creating a
	// polling loop to watch for hash changes to enable back and forward.
	if (!('onhashchange' in win)) {
		// Remember the initial hash so it doesn't get triggered immediately.
		var lh = w.getHash();
		// This polling loop checks every 100 milliseconds to see if
		// location.hash has changed, and triggers the 'hashchange' event on
		// window when necessary.
		setInterval(function(){
			var h = w.getHash();
			if (h !== lh) {
				lh = h;
				w.trigger('hashchange');
			}
		}, 100);
	}

	/**
	 * Poodle
	 */
	P.UA = extendObj({}, ua);
	P.MSXML = MSXML;
	P.createCSS = createCSS;
	P.scrollStep = function(e){return (e.detail||(e.wheelDelta/-40))/-3;};
	P.getCSSMediaType = function()
	{
		var i=1, id = 'PoodleMediaInspector', o = doc.$(id),
		t = ['all','aural','braille','embossed','handheld','print','projection','screen','speech','tty','tv'];
		if (!o) {
			createCSS('#'+id+'{display:none;width:0px}','all');
			for (;i<11;++i) { createCSS('#'+id+'{width:'+i+'px}',t[i]); }
			o = doc.$B().$A(doc.$C('div', {id:id}));
		}
		return t[o?intval(o.css('width',1)):0];
	};

	P.getCookie = function(name)
	{
		var m = document.cookie.match(new RegExp("(?:^|;\\s*)"+name+"=([^;]*)"));
		return m ? unescape(m[1]) : null;
	};
	P.setCookie = function(name, value, expire, path, domain/*, secure, httponly*/)
	{
		var v = name+'='+escape(value)+'; path='+(path||P.ROOT)+'; domain='+(domain||doc.location.host);
		if (expire) {
			if (!expire.getDay) { expire = new Date(new Date() + (expire*1000)); }
			v = v+"; expires=" + expire.toUTCString();
		}
		document.cookie = v;
	};
	P.delCookie = function(name) { document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT'; };

	P.version = function(){return version;};
	P.loadScript = function(file){doc.head.$A('script', {type:'text/javascript',src:file.replace(/&amp;/gi,'&')});};
	P.Debugger = function(){};

	P.strContains = function(src, str)
	{
		if (src) {
			if (str != src && !defined(strContains_re[str])) strContains_re[str] = new RegExp("(^|\\s)"+str+"(\\s|$)");
			return (str == src || src.match(strContains_re[str]));
		}
		return false;
	};

	P.dragStart = startDrag;
	P.dragEnd   = endDrag;

	k = doc.location;
	P.HOST = k.protocol+'//'+k.host;
	P.ROOT = '/';
	P.PATH = '/';
	k = doc.scripts;
	var re = new RegExp('://[^/]+((.*/)(?:\\?js=))[^/]*$');
	for (var m,i=0; i<k.length; ++i) {
		m = re.exec(k[i].src);
		if (m) {
			P.PATH = m[2];
			P.ROOT = m[2].replace(/\/[^\/]+\.php\/$/,'/');
			P.JS_URI = m[1];
			break;
		}
	}
	m = (new RegExp('href="([^\'"]*/(?:[^\'"/]+\.php/)?\\?css=)')).exec(doc.head.innerHTML);
	if (m) {
		P.CSS_PATH = m[1];
//		P.TPL = m[2];
	}

	P.PostMax = 8388608;
	P.PostMaxFiles = 20;
	P.PostMaxFilesize = 2097152;

	P.initHTML = function(node){
		// HTML5 details + summary
		node = node || doc;
		var i=0, nodes = node.$Q('details > summary'), n;
		for (; i < nodes.length; ++i) {
			n = nodes[i].parentNode;
			if (!defined(n.open)) {
				n.open = n.hasAttribute('open');
				nodes[i].on('click',function(){
					this.parentNode.toggleAttribute('open');
				});
			}
		}
	};

	/**
	 * Cross-browser DOMContentLoaded
	 * Inspired by Dean Edwards' blog: http://dean.edwards.name/weblog/2006/06/again/
	 * Will fire an event when the DOM is loaded (supports: Gecko, IE, Opera9+, WebKit)
	 */
	if (window === w) {
		var DOMListeners = [], DOMLoaded, DOMTimer;
		function callFn()
		{
			if (DOMLoaded) { return; }
			DOMLoaded = true;
			if (DOMTimer) {
				clearInterval(DOMTimer);
				DOMTimer = 0;
			}
			console.info("Poodle\t"+version);
//			console.info("CSS \t"+P.getCSSMediaType());
			console.info("XMLNS\t"+(doc.$B().namespaceURI||'none'));

			P.initHTML();

			var i=0, nodes = P.$Q('input.df-challenge'), n, cn, q, v;
			for (; i < nodes.length; ++i) {
				n = nodes[i];
				cn = n.attr('name');
				q = P.getCookie(cn)||'n';
				v = q.substr(2);
				if ('q' === q[0]) { n.attr('title',v).attr('placeholder',v); }
				if ('h' === q[0]) { n.style.display='none';n.value=v; }
				if ('i' === q[0]) { P.$C('img',{src:v}).placeBefore(n); }
				P.delCookie(cn);
			}
			nodes = P.$Q('form[data-df-challenge]');
			for (i=0; i < nodes.length; ++i) {
				n = nodes[i];
				cn = n.data('df-challenge');
				q = P.getCookie(cn)||'n';
				v = q.substr(2);
				if ('h' === q[0]) { n.$A('input',{type:'hidden',name:cn,value:v}); }
				n.data('df-challenge',null);
				P.delCookie(cn);
			}
			arrCallFunctions(DOMListeners, null, doc);
			DOMListeners = [];
			doc.removeEventListener(DCL, callFn, false);
			w.off('load', callFn);
//			if ('Quirks' == P.getCSSMediaType()) { console.error("DOCTYPE declaration is missing"); }

			console.log(DCL);
		}
		doc.addEventListener(DCL, callFn, false);
		if (((ua.webkit && ua.webkit<525) || ua.khtml) && defined(doc.readyState)) {
			DOMTimer = setInterval(function(){if (/loaded|complete/.test(doc.readyState)) { doc.trigger(DCL); }}, 10);
		}
		w.on('load', callFn);
		P.onDOMReady = function(fn) { DOMLoaded ? fn() : DOMListeners.push(fn); };
	} else {
		extendDOM(w, window);
	}
};

PoodleL10N = function()
{
	this.merge = function(a){
		mergeObjectProperties(a, Poodle.L10N);
	};
	this.get = function(txt) {
		if (!txt) return txt;
		if (this[txt]) {
			return this[txt];
		}
		console.warn('Translation missing for: '+txt);
		return txt;
	};
	this.nget = function(txt, n) {
		txt = this.get(txt);
		if (!is_string(txt)) {
			i = txt.plural ? txt.plural(n) : (1 == n ? 0 : 1);
			txt = txt.msgs[i];
		}
		return sprintf(txt, n);
	};
};

/**
 * End Poodle DOM initialization
 */
})();

Poodle = new PoodleDOM(window);

function _(txt) { return Poodle.L10N.get(txt); }

(function(K){

var
	AF='Africa/',
	AS='Asia/',
	AT='Atlantic/',
	AU='Australia/',
	EU='Europe/',
	PA='Pacific/',
	US='America/',
	timezones = {
		'-660,0':[PA+'Niue'],
		'-600,0':[PA+'Honolulu'],
		'-600,N':[US+'Adak'],
		'-540,N':[US+'Anchorage'],
		'-480,0':[US+'Metlakatla'],
		'-480,N':[US+'Los_Angeles'],
		'-420,0':[US+'Phoenix'],
		'-420,N':[US+'Denver'],
		'-360,0':[US+'Regina'],
		'-360,N':[US+'Chicago'],
		'-360,S':[PA+'Easter'],
		'-300,0':[US+'Jamaica'],
		'-300,N':[US+'New_York'],
		'-270,0':[US+'Caracas'],
		'-240,0':[US+'Aruba'],
		'-240,N':[US+'Halifax'],
		'-240,S':[US+'Asuncion'],
		'-210,N':[US+'St_Johns'],
		'-180,0':[US+'Paramaribo'],
		'-180,N':[US+'Godthab'],
		'-180,S':[US+'Sao_Paulo'],
		'-120,0':[US+'Noronha'],
		 '-60,0':[AT+'Cape_Verde'],
		 '-60,N':[AT+'Azores'],
		   '0,0':['UTC'],
		   '0,N':[EU+'London'],
		  '60,0':[AF+'Lagos'],
		  '60,N':[EU+'Brussels'],
		  '60,S':[AF+'Windhoek'],
		 '120,0':[AF+'Tripoli'],
		 '120,N':[EU+'Istanbul'],
		 '180,0':[AF+'Nairobi'],
		 '210,N':[AS+'Tehran'],
		 '240,0':[EU+'Moscow'],
		 '240,N':[AS+'Yerevan'],
		 '270,0':[AS+'Kabul'],
		 '300,0':[AS+'Ashgabat'],
		 '330,0':[AS+'Colombo'],
		 '360,0':[AS+'Bishkek'],
		 '420,0':[AS+'Bangkok'],
		 '480,0':[AS+'Singapore'],
		 '540,0':[AS+'Pyongyang'],
		 '600,0':[AS+'Yakutsk'],
		 '600,S':[AU+'Hobart'],
		 '630,0':[AU+'Lord_Howe'],
		 '630,N':[AU+'Adelaide'],
		 '660,0':[AS+'Vladivostok'],
		 '660,N':[AU+'Sydney'],
		 '720,0':[AS+'Kamchatka'],
		 '720,S':[PA+'Auckland'],
		 '765,S':[PA+'Chatham'],
		 '780,0':[PA+'Enderbury'],
		 '780,S':[PA+'Apia'],
		 '840,0':[PA+'Kiritimati']
	};

if (!K.getCookie('PoodleTimezone')) {
	var jan = intval(-(new Date(2012, 0, 1, 0, 0, 0, 0)).getTimezoneOffset()),
		jul = intval(-(new Date(2012, 6, 1, 0, 0, 0, 0)).getTimezoneOffset()),
		diff = jan - jul,
		id = jan + (diff < 0 ? ",N" : ",0");
	if (diff > 0) { id = jul + ",S"; }
	id = timezones[id];
	if (!id) {
		id = -Math.round(new Date().getTimezoneOffset()/60);
		id = id ? 'Etc/GMT'+(id<0?'':'+')+id : 'UTC';
	}
	K.setCookie('PoodleTimezone', id);
}

})(Poodle);
