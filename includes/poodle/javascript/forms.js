/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	@import "poodle"

	When client supports the correct HTML5 type, this is NOT used!
	It uses the A element to provide focus on tabbing.

	http://dev.w3.org/html5/spec/the-input-element.html
	http://dev.w3.org/html5/spec/common-input-element-attributes.html

	http://www.w3.org/TR/html-markup/input.color.html
	<input type="color"/> must be #[0-9A-F]{6}

	trigger('input') Occurs when the text content of an element is changed through the user interface.
*/

/** keyCode
 *   8: backspace
 *   9: tab
 *  13: enter
 *  16: shift
 *  17: ctrl
 *  18: alt
 *  19: pause/break
 *  20: caps lock
 *  27: escape
 *  32: space
 *  33: page up
 *  34: page down
 *  35: end
 *  36: home
 *  37: arrow left
 *  38: arrow up
 *  39: arrow right
 *  40: arrow down
 *  45: insert
 *  46: delete
 *  93: context menu
 *  96: Numpad 0
 * 105: Numpad 9
 * 107: Numpad +
 * 109: Numpad -
 * 110: Numpad .
 * 112: F1
 * 123: F12
 * 144: num lock
 * 145: scroll lock
 * 190: dot
 */

(function(){

var K = Poodle,
    ub = "25[0-5]|2[0-4]\\d|[01]?\\d\\d?",  // IPv4 part, unsigned byte (0-255)
    h4 = "[0-9A-Fa-f]{1,4}",                // IPv6 part, hex
    dp = "[a-z0-9](?:[a-z0-9-]*[a-z0-9])?", // domain part
   loc = "[a-z0-9!#$%&'*+/=?^_`{|}~-]+",    // e-mail local-part part
 local = loc+"(?:\\."+loc+")*",             // e-mail local-part
domain = "(?:"+dp+"\\.)+"+dp,
  IPv4 = "(?:(?:"+ub+")\\.){3}"+ub,
  IPv6 = "\\["+[
	"(?:(?:"+h4+":){7}(?:"+h4+"|:))",
	"(?:(?:"+h4+":){6}(?::"+h4+"|"+IPv4+"|:))",
	"(?:(?:"+h4+":){5}(?:(?:(?::"+h4+"){1,2})|:"+IPv4+"|:))",
	"(?:(?:"+h4+":){4}(?:(?:(?::"+h4+"){1,3})|(?:(?::"+h4+")?:"+IPv4+")|:))",
	"(?:(?:"+h4+":){3}(?:(?:(?::"+h4+"){1,4})|(?:(?::"+h4+"){0,2}:"+IPv4+")|:))",
	"(?:(?:"+h4+":){2}(?:(?:(?::"+h4+"){1,5})|(?:(?::"+h4+"){0,3}:"+IPv4+")|:))",
	"(?:(?:"+h4+":){1}(?:(?:(?::"+h4+"){1,6})|(?:(?::"+h4+"){0,4}:"+IPv4+")|:))",
	"(?::(?:(?:(?::"+h4+"){1,7})|(?:(?::"+h4+"){0,5}:"+IPv4+")|:))"
  ].join("|")+"\\]",
  host = "("+domain+"|"+IPv4+"|"+IPv6+")",
re_domain= new RegExp(domain),
re_email = new RegExp('^(("[\\w\\s-]+")('+local+')?|'+local+')@'+host+'$',"i"),
re_ipv4  = new RegExp(IPv4),
re_ipv6  = new RegExp(IPv6),
re_uri   = new RegExp("^([a-z][a-z0-9\\+\\.\\-]+)://"+host+"(:[0-9]+)?(/[^\\x00-\\x1F#?]+)?(\\?[^\\x00-\\x1F#]+)?(#[^\\x00-\\x1F]+)?$"),
DCVC = "DOMControlValueChanged",
ADIF = "data-input-format",
datalist_supported = ('options' in document.createElement('datalist')),
types = ["text","search","url","tel","email","password","datetime","datetime-local","date","month","week","time","number","checkbox","radio","file"];

function F()
{
	var i=0, fs=arguments, f;
	if (!fs.length) fs=K.$T("form");
	while (f = fs[i++]) {
		var c=0, n=f.elements;
		for (;c<n.length;++c) {
			try {
				switch (n[c].lowerName())
				{
				case "button": F.Button(n[c]); break;
				case "input":  HTML5InputElement(n[c]); break;
				case "select": F.Select(n[c]); break;
				case "textarea":
					F.placeholder(n[c]);
					if ((n[c]).hasClass('visualML')) { F.visualMaxLength(n[c]); }
					break;
				}
			} catch (e) { console.error(e); }
		}
		f.on("reset",function(e){setTimeout(function(){e.target.trigger("afterreset");},200);});
	}

	function HTML5InputElement(o)
	{
		if (!o._ph5fi) {
			var type = o.attr("type")||"text", pattern;
			if (type != o.type || (K.UA.safari && ('color'===type || type.match(c_type_re)))) {
				/** Handle type */
				switch (type)
				{
				case "date":
				case "datetime":
				case "datetime-local":
				case "month":
				case "week":
					F.Calendar(o);
					break;
				case "color":
					F.Color(o);
					break;
				case "email":
					break;
				case "number":
					F.Number(o);
					break;
				case "range":
					F.Range(o);
					break;
				case "search":
					break;
				case "tel":
					pattern = "^(+|00)[0-9 \\(\\)]+$";
					break;
				case "time":
					F.Time(o);
					break;
				case "url":
					pattern = "^([a-z]+)://"+host+"(:[0-9]+)?(/[^\\x00-\\x1F#?]+)?(\\?[^\\x00-\\x1F#]+)?(#[^\\x00-\\x1F]+)?$";
					break;
				}
			} else
			if ("text" == o.type) {
				if (o.hasClass("color")) F.Color(o);
				else if (o.hasClass("number")) F.Number(o);
				else if (o.hasClass("range")) F.Range(o);
				else if (o.getClass().match(c_type_re)) F.Calendar(o);
				else {
					if (!datalist_supported) { F.InputDatalist(o); }
					o._ph5fi = o.type;
				}
			} else
			if ("checkbox" == o.type) {
				F.Checkbox(o);
			} else
			if ("radio" == o.type) {
				F.Radio(o);
			} else
			if ("submit" == o.type || "image" == o.type) {
				F.Button(o);
				return;
			} else
			if ("file" == o.type) {
				o.on("change",function(){
					var i=0, total = 0;
					for (; i < this.files.length; ++i) {
						if (this.files[i].size > K.PostMaxFilesize) {
							alert(this.files[i].name + ' is too big!\nMaximum is: '+K.PostMaxFilesize.bytesToSize(1));
							return;
						}
						total += this.files[i].size;
					}
					if (total > K.PostMax || this.files.length > K.PostMaxFiles) {
						alert('To many files!');
					}
				});
			}

			/** Handle attributes */

//			if (o.hasAttribute("multiple") && ("email"==type||"file"==type)) { }

			var i = types.indexOf(type);
			if (0 <= i && 6>i) {
				pattern = o.attr("pattern") || pattern;
				if (!defined(o.pattern) && pattern) {
					o.attr("pattern", o.pattern = pattern);
				}
				F.placeholder(o);
			}

			setOnInvalidate(o);

//			if (0 <= i && o.hasAttribute("required")) { }

			if (!defined(o.autofocus) && o.hasAttribute("autofocus")) { o.focus(); }

			if (!o._ph5fi) o._ph5fi = 1;
		}
	}
}

F.validate = {
	domain: function(v) { return !!v.match(re_domain); },
	email_address: function(v) { return !!v.match(re_email); },
	host: function(v) { return (this.domain(v) || this.IPv4(v) || this.IPv6(v)); },
	IPv4: function(v) { return !!v.match(re_ipv4); },
	IPv6: function(v) { return !!v.match(re_ipv6); }
};

/**
 * Default actions for range and number types
 * Don't use DOMAttrModified since it modifies the attribute (default value)
 * and not the actual value
 */

function get_input(e){return e.target.input||e.target.parentNode.input;}

function noevent(e){e.stop();}

function mousescroll(e)
{
	var o = get_input(e);
	if (o) {
		step_value(o, K.scrollStep(e));
		K.DOM.Event.stop.call(e);
	}
}

function step_value(o, n) { set_value(o, floatval(o.value)+(0<o.getStep()?o.getStep()*n:n)); }

function set_value(o, v) {
	var s = o.getStep();
	v = Math.max(o.getMin(), Math.min(o.getMax(), s?(s*intval(v/s)):v));
	o.setValue(v);
	return v;
}

function isCombiKey(e) { return !!(e.ctrlKey || e.altKey || e.metaKey); }

function setOnInvalidate(o) { o.on("invalid", function(){this.form.addClass('invalid');}); }

/**
 * examples:
 *		<input placeholder="search"/>
 *		<textarea placeholder="message here"></textarea>
 */
F.placeholder = function(o)
{
	if ((!o.hasOwnProperty || o.hasOwnProperty("placeholder")) && o.hasAttribute("placeholder"))
	{
		console.log(o.nodeName+' has no support for HTML5 placeholder');
		o.on("focus", function() { if (o.hasClass("placeholder")) o.removeClass("placeholder").value=""; })
		 .on("blur", function() { if (!o.value) o.addClass("placeholder").value = o.attr("placeholder"); })
		 .trigger("blur")
		 .form.on("beforesubmit",function(){o.trigger("focus");})
		      .on("afterreset",function(){o.trigger("blur");});
	}
};

/**
 * examples:
 *		<input type="number"/>
 *		<input type="text" class="number"/> = force custom slider
 */
var _timer, _timeout;
F.Number = function(input, force)
{
	var n, t, ov;

	function keydown(e)
	{
		var o = e.target, kc = e.keyCode;
		if (!o.readOnly && !o.disabled) {
			// up || down
			if (38 == kc || 40 == kc) {
				step_value(o, 38==kc?1:-1);
				e.stop();
			}
		}
	}

	function keypress(e)
	{
		var o = e.target, cc = e.charCode;
		if (!o.readOnly && !o.disabled) {
			// Skip non-numeric characters
			if (cc && !isCombiKey(e) && (48 > cc || 57 < cc)) {
				e.stop();
			}
		}
	}

	function btnpress(e)
	{
		if (32 == e.charCode && !isCombiKey(e)) {
			e.stop();
			t = get_input(e);
			n = e.target._nstep;
			if (n && !t.readOnly && !t.disabled) {
				step_value(t, n);
			}
		}
	}

	function mousedown(e)
	{
		t = get_input(e);
		n = e.target._nstep;
		ov = t.value;
		if (n && !t.readOnly && !t.disabled) {
			var fn = function(){step_value(t, n);};
			mouseup(e);
			fn();
			document.on("mouseup",mouseup); // stop timer
			_timeout = setTimeout(function(){_timer=setInterval(fn,150);}, 500);
		}
	}
	function mouseup(e)
	{
		if (_timer) clearInterval(_timer);
		if (_timeout) clearTimeout(_timeout);
		document.off("mouseup",mouseup);
//		if (ov!=t.value)t.trigger("change");
	}

	if (!input._ph5fi || force) {
		input._ph5fi = "number";
		var s = K.$C("span",{"class":"input-number",input:input,onDOMMouseScroll:function(e){
				var o = this.input;
				if (o && !o.readOnly && !o.disabled) mousescroll(e);
			}
		});
		input.replaceWith(s);
		s.$A(input);
		input
			.on("keydown",keydown)
			.on("keypress",keypress)
			.on(DCVC,function(e){this.trigger("change");});
		s.$A("a",{
			href:"#",
			innerHTML:"▲",
			onclick:noevent,
			ondragstart:noevent,
			onmousedown:mousedown,
			onkeypress:btnpress
		})._nstep = 1;
		s.$A("a",{
			href:"#",
			innerHTML:"▼",
			onclick:noevent,
			ondragstart:noevent,
			onmousedown:mousedown,
			onkeypress:btnpress
		})._nstep = -1;
	}
};

/**
 * When the control is wider than it is tall (or square), the control is
 * expected to be a horizontal slider, with the lowest value on the left unless
 * the 'direction' property on this element has a computed value of 'rtl'.
 * When the control is taller than it is wide, it is expected to be a vertical
 * slider, with the lowest value on the bottom.
 *
 * examples:
 *		<input type="range"/>
 *		<input type="text" class="range"/> = force custom slider
 */
F.Range = function(input, force)
{
	var target;

	function move(e, a)
	{
		if (a) {
			var v=a.vertical,
			    o=a.input,
			  pos=a.getMousePos(e),
			    p=v?"offsetHeight":"offsetWidth",is=a.slider[p],os=a[p],r=o.getMax()-o.getMin();
			if (!o.readOnly && !o.disabled) {
				set_value(o, o.getMin() + (r / (os-is) * (((v||"rtl"==o.dir) ? (os-(v?pos.y:pos.x)) : pos.x)-is/2)));
			}
		}
	}

	function mousedown(e)
	{
		// W3C: 0=Left, 1=Middle, 2=Right || IE: 1=Left, 2=Right, 4=Middle
		if (!e.button || (!e.which && e.button&1)) {
			var o = e.target, n = o.lowerName();
			if ("i" == n) {
				target = o.parentNode;
				document.on("mousemove",mousemove).on("mouseup",mouseup); // stop
				target.focus();
			} else {
				if ("input" == n) o=o.parentNode;
				move(e, o);
				o.focus();
			}
			e.stop();
		}
	}
	function mousemove(e) { move(e, target); e.stop(); }
	function mouseup(e)   { target = null; document.off("mouseup",mouseup).off("mousemove",mousemove); }

	function keydown(e)
	{
		var kc = e.keyCode,
		     o = get_input(e),
		     a = o.parentNode,
		     h = !a.vertical,
		     s = 1;
		if (!o.readOnly && !o.disabled) {
			if ((37==kc && h) || (40==kc && !h)) { // left || down
				s = -s;
			} else
			if (!(39==kc && h) && !(38==kc && !h)) return; // !right && !up
			step_value(o, (h && "rtl"==o.dir) ? -s : s);
			e.stop();
		}
	}

	function changed(o)
	{
		var a=o.parentNode,
		    s=a.slider,
		    v=set_value(o,o.value),
		    h=!a.vertical,
		    r=o.getMax()-o.getMin(),
		    // Also try to detect width when (parent) element has display:none
		    p=h?"offsetWidth":"offsetHeight",
		    m=intval(a[p] || o.css(h?'width':'height')) - intval(s[p] || s.css(h?'width':'height'));
		a.attr("title", v);
	    p=m/r*(r-o.getMax()+v);
		s.style[h?"left":"bottom"] = ((h && "rtl"==o.dir)?m-p:p)+"px";
		o.trigger("change");
	}

	if (!input._ph5fi || force) {
		input._ph5fi = "range";
		var v = input.offsetWidth<input.offsetHeight,
			a = K.$C("a",{
				"class":"input-range"+(v?"-v":""),
				href:"#",
				input:input,
				vertical:v,
				onclick:function(e){e.stop();},
				onkeydown:keydown,
				onmousedown:mousedown,
				onDOMMouseScroll:mousescroll
			});
		a.slider = a.$A("i",{ondragstart:noevent});
		input.replaceWith(a);
		changed(a.$A(input)
			// createEvent("KeyboardEvent") with DOM_VK_TAB fails, instead just make them last
			.attr("tabindex",100000000)
			.on(DCVC,function(){changed(input);})
		);
		input.form.on("afterreset",function(){changed(input);});
	}
};

/**
 * Helper functions for Calendar and ColorPicker
 */

function reposition(o, ref)
{
	if (o.offsetWidth) { o.defWidth = o.offsetWidth; }
	if (o.offsetHeight) { o.defHeight = o.offsetHeight; }
	var br = ref.getBoundingPageRect(), b = o.$B(), p = ref.hasFixedPosition()?"fixed":"",
		w = o.offsetWidth || o.defWidth || 230,
		h = o.offsetHeight || o.defHeight || 180;
	if (br.x+w > b.offsetWidth) { br.x = (br.right-w); }
	br.y += (br.bottom+h > b.offsetHeight) ? -h : ref.offsetHeight;
	o.setCSS({
		left: intval(br.x) + "px",
		top: intval(br.y) + "px",
		position: p
	});
}

/**
 * examples:
 *		<input type="time"/>
 *		<input type="time" class="time"/>
 *		<input type="text" class="time"/> = force
 */

F.Time = function(o, force)
{

	function setValueAsDate(o, d)
	{
		if (d) {
			var min = o.getMin(), max = o.getMax();
			if (min) { min = min.toDate('H'); }
			if (max) { max = max.toDate('H'); }
			if (min && min > d) { d = min; }
			if (max && max > min && max < d) { d = max; }
		}
		o.setValueAsDate(d);
		o.trigger('input');
	}

	function getDate(o)
	{
		return (o.getValueAsDate()||new Date(1970,1,1,0,0,0,0));
	}

	function keydown(e)
	{
		var kc = e.keyCode, o = e.target;
		if (!o.readOnly && !o.disabled) {
			// backspace | delete
			if (8 == kc || 46 == kc) {
				o.value = "";
			}
			// Arrow up/down
			if (38 == kc || 40 == kc) {
				e.stop();
				var s = o.getStep(),
					d = getDate(o),
					p = Math.max(0, o.selectionStart);
				if (p < 3 && s < 3600) {
					s = 3600;
				} else
				if (p < 6 && s < 60) {
					s = 60;
				} else
				if (p < 9 && s < 1) {
					s = 1;
				}
				d.setTime(d.getTime() + ((38==kc?s:-s) * 1000));
				setValueAsDate(o,d);
				o.setSelectionRange(p,p);
			}
		}
	}

	function keypress(e)
	{
		var cc = e.charCode, o = e.target;
		if (!o.readOnly && !o.disabled && !isCombiKey(e)) {
			if (48 <= cc && 57 >= cc)
			{
				var s = o.getStep(), f = 'H:i';
				if (s < 60) { f += ":s"; }
				if (s <  1) { f += ".u"; }
				var d = getDate(o),
					p = Math.max(0, o.selectionStart),
					v = d.format(f);
				// Move position to first numeric character
				if (2 == p || 5 == p || 8 == p) { ++p; }
				// Replace numeric character
				if (p < v.length) {
					d = (v.substring(0,p)+(cc-48)+v.substring(++p)).toDate(f);
					// Move position to next numeric character
					if (2 == p || 5 == p || 8 == p) { ++p; }
				}
				setValueAsDate(o,d);
				o.setSelectionRange(p,p);
			}
			if (0 < cc) { e.stop(); }
		}
	}

	if (!o._ph5fi || force) {
		o._ph5fi = "time";
		var s = o.getStep(), l = 5, pattern = "^(2[0-3]|[01][0-9]):[0-5][0-9]";
		if (s < 60) {
			l = 8;
			pattern += ":[0-5][0-9]";
		}
		if (s < 1) {
			l = 12;
			pattern += "\\.[0-9]{1,3}";
		}
		o.attr("maxlength", l)
		 .attr("pattern", o.pattern = pattern+"$")
		 .on("keydown", keydown)
		 .on("keypress", keypress)
		 .trigger(DCVC);
	}

	return o;
};

/**
 * examples:
 *		<input type="datetime"/>
 *		<input type="week" class="week"/>
 *		<input type="text" class="week"/> = force calendar
 */

/** Private Static Properties */
var calendar, c_scroll, DD="DropDown", c_type_re = /(date(?:time(?:-local)?)?|month|week)/,
c_format = {
	// input field format in PHP date format
	date:"Y-m-d",
	datetime:"Y-m-d\\TH:i:s\\Z",
	"datetime-local":"Y-m-d\\TH:i:s", // DATE_RFC3339
	month:"Y-m",
	week:"Y-\\WW",
	// today string format in PHP date format
	today:"D. j F Y"
};

function c_placeholder(f)
{
	return f.replace(/\\?[a-zA-Z]/g,
		function (m) {
			if (m.charAt(0) === '\\') { return m.charAt(1); }
			if ('d'==m || 'j'==m) return 'dd';
			if ('W'==m) return 'ww';
			if ('m'==m || 'n'==m || 'i'==m) return 'mm';
			if ('Y'==m) return 'yyyy';
			if ('y'==m) return 'yy';
			if ('g'==m || 'G'==m || 'h'==m || 'H'==m) return 'hh';
			if ('s'==m) return 'ss';
			if ('u'==m) return 'uuu';
			return '';
		}
	);
}

function c_pattern(f)
{
	return f.replace(/\\?[a-zA-Z]/g,
		function (m) {
			if (m.charAt(0) === '\\') { return m.charAt(1); }
			if ('d'==m || 'j'==m) return '(0[1-9]|[12][0-9]|3[01])';
			if ('W'==m) return '(0[1-9]|[1-4][0-9]|5[0-3])';
			if ('m'==m) return '(0[1-9]|1[012])';
			if ('i'==m || 'n'==m || 's'==m) return '[0-5][0-9]';
			if ('Y'==m) return '[0-9]{4}';
			if ('y'==m) return '[0-9]{2}';
			if ('g'==m || 'G'==m || 'h'==m || 'H'==m) return '([01][0-9]|2[0-3])';
//			if ('u'==m) return 'uuu';
			return '';
		}
	);
}

F.Calendar = function(o, force)
{
	function asTime(v)
	{
		var m = (v||'').match(/([0-9]+)(?::([0-9]+))?(?::([0-9]+))?/);
		return m?(m[1]||'00')+':'+(m[2]||'00')+':'+(m[3]||'00'):'00:00:00';
	}

	if (!o._ph5fi || force) {
//		o.type = 'text';
		var c = o.getClass(),
			m = (o.getAttribute('type')+' '+c).match(c_type_re),
		    f = m ? m[1] : 'datetime',
		    s = K.$C('span',{'class':'poodle-calendar-input '+(f.replace('-local','')),input:o});
		o._ph5fi = f;
		o.replaceWith(s);
		o = s.$A(o).addClass('invisible');

		var d = o.getValueAsDate(),
		   df = (o.attr(ADIF) || getL10NFormat(f)).replace(/(\s|\\T).+$/,''),
		   ed = o.editor = s.$A('input',{
					'class':c,
					type:'text',
					placeholder:(o.attr('placeholder') || c_placeholder(df)),
					readOnly:o.disabled||o.readOnly,
					input:o,
					required:o.hasAttribute('required'),
					value:d?d.format(df):'',
					pattern:c_pattern(df)
				}).attr(ADIF,df).attr('style',o.attr('style')).placeAfter(o),
		   fn = function(){
			   o.setValueAsDate(ed.getValueAsDate());
			   o.trigger('input');
			};
		setOnInvalidate(ed);
		ed.getValueAsDate = function(){
			var f = this.attr(ADIF), v = this.value;
			if (v && o.time) {
				v = v + ' ' + asTime(o.time.value);
				f = f + ' H:i:s';
			}
			return f ? v.toDate(f) : null;
		};
		ed.setValueAsDate = function(date){
			var f = this.attr(ADIF);
			if (f) {
				this.value = date ? date.format(f) : '';
				if (o.time) {
					o.time.setValueAsDate(date);
				}
				fn();
			}
		};
		ed.addClass(ed._ph5fi = f)
		  .on('click', click)
		  .on('focus', focus)
		  .on('keydown', keydown)
		  .on('keypress', keypress);
		// Safari: attempting to change access mechanism for an unconfigurable property
		try { Object.defineProperty(ed, 'valueAsDate', K.DOM.HTMLInputElement.valueAsDate); } catch(e){}

		s.$A('a',{
			href:'#',
			onclick:function(e){display(ed);e.stop();},
			onkeydown:function(e){if(32==e.keyCode){display(ed);e.stop();}}
		});

		if ('datetime' === f || 'datetime-local' === f) {
			ed.replaceClass(f,'date');
			o.time = F.Time(s.$A('input',{
				'class': 'time',
				type: 'text',
				step: o.getStepSeconds(),
				readOnly: o.disabled || o.readOnly,
				input: o,
				required: o.hasAttribute('required'),
				value: d ? d.format('H:i:s.u') : ''
			}))
			 .on('input', fn)
			 .on('focus', setInputState);
		}

		o.on(DCVC, function(){ed.setValueAsDate(o.getValueAsDate());}).trigger(DCVC);
		o.form.on('beforesubmit',fn);
	}

	function getDate(o)
	{
		var d = o.getValueAsDate();
		if (!d) {
			d = new Date();
			d.setHours(0);
			d.setMinutes(0);
			d.setSeconds(0);
			d.setMilliseconds(0);
		}
		return d;
	}

	function getL10NFormat(s)
	{
		var f = F.Calendar.l10n_formats;
		return f[s] ? f[s] : c_format[s];
	}

	function hideDropDowns()
	{
		for (var i=0;i<2;++i) calendar.selects[i].hide();
	}

	function hideCal(e, f)
	{
		if (e) e.stop();
		hideDropDowns();
		stopSelectBoxScroll();
		calendar.hide();
		calendar.target.focus();
	}

	function save()
	{
		var c = calendar, o = c.target, i = o.input;
		if (!(o.readOnly = (i.disabled || i.readOnly))) {
			o.setValueAsDate(c.date);
			o.trigger('change');
		}
		hideCal();
	}

	function getNPos(v, p)
	{
		var n = "0123456789", l = v.length;
		p = Math.max(0, p);
		while (p < l && 0 > n.indexOf(v.charAt(p))) { ++p; }
		return p;
	}

	function keypress(e)
	{
		var cc = e.charCode, o = e.target;
		if (cc && !isCombiKey(e) && !(o.readOnly = (o.input.disabled || o.input.readOnly))) {
			if (48 <= cc && 57 >= cc) {
				var f = o.attr(ADIF),
					v = getDate(o).format(f),
					p = getNPos(v, o.selectionStart);
				if (p < v.length) {
					v = v.substring(0, p)+(cc-48)+v.substring(++p);
					if (o.value != v/* && v.toDate(f)*/) {
						o.setValueAsDate(v.toDate(f));
						if (calendar) calendar.update(o,1);
					}
					p = getNPos(v, p);
					o.setSelectionRange(p,p);
				}
			}
			e.stop();
		}
	}

	function keydown(e)
	{
		var kc = e.keyCode, o = e.target;
		o.readOnly = (o.input.disabled || o.input.readOnly);
		// Escape || Space
		if (27==kc || 32==kc) {
			display(e, 27==kc);
			e.stop();
		}
		if (!o.readOnly) {
			// backspace | delete
			if (8 == kc || 46 == kc) { o.value = ""; }
			// Arrow up/down
			if (38 == kc || 40 == kc) {
				var m, ml,
					f = o.attr(ADIF),
					n = "0123456789",
					v = getDate(o).format(f),
					s = o.selectionStart,
					p = (0 > n.indexOf(v.charAt(s))) ? s : getNPos(v, s);
				while (p && 0<=n.indexOf(v.charAt(p-1))) --p;
				if (m = v.substring(p).match(/^[0-9]+/)) {
					ml = m[0].length;
					m = intval(m[0])+(38==kc?1:-1);
					v = v.substring(0, p)+m.pad(ml)+v.substring(p+ml);
					if (!v.toDate(f)) { return e.stop(); }
					v = v.toDate(f).format(f);
				}
				if (o.value != v/* && v.toDate(f)*/) {
					o.setValueAsDate(v.toDate(f));
					if (calendar) calendar.update(o,1);
				}
				o.setSelectionRange(s,s);
				e.stop();
			}
		}
	}

	function setInputState(e)
	{
		var o = e.currentTarget, i = o.input;
		o.readOnly = i.disabled || i.readOnly;
	}

	function focus(e)
	{
		setInputState(e);
		var c = calendar, o = e.currentTarget;
		if (c && o != c.target && !c.hidden) display(e);
	}

	function click(e)
	{
		var x = this.getMousePos(e).x, pl = intval(o.css('padding-left'));
		if ((pl > 10 && x < pl) || x > pl + intval(o.css('width')))
		{
			display(e);
		}
	}

	function display(e, hide)
	{
		var c = calendar || createCalendar(), d, o = e.currentTarget||e;
		if (hide || (o == c.target && !c.hidden)) {
			hideCal();
			return false;
		}
		if (!o.input.disabled) {
			c.className = o._ph5fi;
			c.update(o);
			reposition(c,o);
			c.show();
			reposition(c,o);
			o.focus();
		}
	}

	function switchMonth(e)
	{
		hideDropDowns();
		var d = calendar.date, min = getMinDate(), max = getMaxDate();
		if (this.hasClass("prevMonth")) {
			if (!min || min.format('Ym') < d.format('Ym')) {
				d.setMonth(d.getMonth()-1);
				writeContent();
			}
		} else {
			if (!max || max.format('Ym') > d.format('Ym')) {
				d.setMonth(d.getMonth()+1);
				writeContent();
			}
		}
		e.stop();
	}

	function showDropDown(e)
	{
		var s = this.select;
		if (s.hidden) {
			hideDropDowns();
			this.select.update();
			s.style.left = this.offsetLeft+"px";
			s.show();
		} else s.hide();
		if (e) e.stop();
	}

	function pickTodaysDate(e)
	{
		calendar.date = new Date();
		if (e) e.stop();
		save();
	}

	function ADivTo(o, cn, click)
	{
		return o.$A("div", {"class":cn, onclick:click});
	}

	function ADiv(cn, click)
	{
		var n = ADivTo(this, cn, click);
		n.$ADiv = ADiv;
		n.$AA = function(cn, f, s){return this.$A("a", {"class":cn, href:"#", innerHTML:s, onclick:f});};
		return n;
	}

	function setOption(o, i, no, cn, html)
	{
		cn = cn==no?"current":"";
		n = o.childNodes[i]||ADivTo(o, cn, selectDateItem).on("selectstart",noevent);
		n.value = no;
		return n.setClass(cn).txt(html||no.pad(2));
	}
	function selectDateItem()
	{
		var d = calendar.date, s = this.parentNode;
		switch (s.id.replace(DD,""))
		{
			case "year"  : d.setYear(this.txt());break;
			case "month" : d.setMonth(this.value);break;
		}
		writeContent();
		s.hide();
	}

	function appendScroller(o, move)
	{
		return o.$A("div", {
			innerHTML:"&#160;&#160;"+move
			,onclick:changeSelectBox
			,onmouseover:startSelectBoxScroll
			,onmouseout:stopSelectBoxScroll
			,onselectstart:noevent
		});
	}
	function changeSelectBox(e,obj)
	{
		if (!obj) obj = this;
		var p = obj.parentNode,
		    items = p.childNodes,
			start = items[1].txt()/1,
			inc = obj.txt().indexOf("-")<0,
			d = calendar.date,
			i;
		if (inc) ++start;
		d = d.getFullYear();
		if (!inc && --start<0) start=0;
		for (i=1; i<items.length-1; ++i)
		{
			items[i].setClass(start==d?"current":"");
			items[i].txt((start++).pad(2));
		}
	}
	function stopSelectBoxScroll()
	{
		if (c_scroll) clearInterval(c_scroll);
		c_scroll = null;
	}
	function startSelectBoxScroll()
	{
		var n = this;
		if (n.txt().indexOf("-")>=0 || n.txt().indexOf("+")>=0)
		{
			c_scroll = setInterval(function(){changeSelectBox(false, n);}, 200);
		}
	}

	function pickDate()
	{
		calendar.date.setDate(this.txt());
		save();
	}

	function getMinDate()
	{
		var i = calendar.target.input, f = i.getDateFormat(), v = i.attr('min');
		return (f && v) ? v.toDate(f) : null;
	}

	function getMaxDate()
	{
		var i = calendar.target.input, f = i.getDateFormat(), v = i.attr('max');
		return (f && v) ? v.toDate(f) : null;
	}

	function writeContent()
	{
		var c = calendar, d = c.date, td = new Date(),
		content = c.content,
		year  = d.getFullYear(),
		month = d.getMonth(),
		days  = d.getDaysInMonth(),
		  min = getMinDate(),
		  max = getMaxDate();
		if (min) { min = min.format('Ymd'); }
		if (max) { max = max.format('Ymd'); }

		c.year.txt(year);
		c.month.txt(d.format("F"));
		c.today.title = td.format(getL10NFormat("today"));

		td.setDate(1);
		td.setMonth(month);
		td.setFullYear(year);

		if (content.childNodes[0]) content.removeChild(content.childNodes[0]);

		var i,
		cols  = 0,
		table = content.$A("table"),
		tbody = table.$A("thead"),
		row   = tbody.insertRow(-1),
		cell  = row.insertCell(-1);

		// Fill heading
		row.id = "weekdays";
		cell.txt(_("Week"));
		cell.setClass("weekno");
		for (i=1; i<8; ++i)
		{
			cell = row.insertCell(-1);
			cell.txt(Date.shortDays[7==i?0:i]);
			if (i > 5) cell.setClass("weekend");
		}

		tbody = table.$A("tbody");
		// Fill cells
		for (i=2-td.getISODay(); i<=days+6; ++i)
		{
			if (cols%7==0)
			{
				if (i>days) break;
				row = tbody.insertRow(-1);
				cell = row.insertCell(-1);
				cell.txt(new Date(year, month, Math.max(1,i)).getWeek());
				cell.setClass("weekno");
			}
			cell = row.insertCell(-1);
			cell.html((0<i && i<=days) ? i : "&#160;"/* empty cell */);
			if (cols%7 > 4) cell.setClass("weekend");
			if (0<i && i<=days) {
				d = new Date(year, month, i, 0, 0, 0, 0);
				// valid date
				if (d.format('Ymd') == c.cur_date.format('Ymd')) {
					cell.addClass("activeDay");
				}
				cell.txt(i);
				if ((min && min > d.format('Ymd')) || (max && max < d.format('Ymd'))) {
					cell.addClass("disabled");
				} else {
					cell.onclick = pickDate;
				}
			}
			++cols;
		}
	}

	function createCalendar()
	{
		var n, sb = "cal-select", c = K.$C("div",{
			id: "poodle-calendar",
			hidden: true,
			selects: [],
			$ADiv: ADiv,
			update: function(o, when_visible)
			{
				if (!when_visible || !this.hidden) {
					this.target = o;
					var d = getDate(o);
					this.cur_date = d;
					this.date = new Date(d);
					this.date.setDate(1);
					writeContent();
				}
			}
		});
		with (c)
		{
			n = $ADiv("caption");
			with (n)
			{
				$AA("prevMonth", switchMonth/*,"\xA0"*/);
				c.month = $AA("select month",showDropDown);
				c.year  = $AA("select year", showDropDown);
				$AA("nextMonth", switchMonth);
				$AA("close", hideCal);
			}
			c.content = c.$A("div", {"class":"content"});
			with ($ADiv("todaysDate"))
			{
				c.today = $AA("todaysDateString", pickTodaysDate, _("Today"));
			}

			c.year.select = selects[0] = n = $ADiv(sb).hide();
			n.id = "year"+DD;
			n.update = function()
			{
				var i=1, y=intval(c.date.getFullYear());
				if (!this.childNodes[0]) appendScroller(this, "-");
				for (;i<11;++i) setOption(this, i, y-6+i, y);
				if (!this.childNodes[11]) appendScroller(this, "+");
			};

			c.month.select = selects[1] = n = $ADiv(sb).hide();
			n.id = "month"+DD;
			n.update = function()
			{
				var i=0, m=intval(c.date.getMonth());
				for (;i<12;++i) setOption(this, i, i, m, Date.longMonths[i]);
			};
		}
		calendar = document.$B().$A(c);

		// Close calendar when clicking outside
		document.$B().on('click',function(e){
			if (!c.hidden
			 && !c.contains(e.target)
			 && !c.target.parent().contains(e.target)
			){
				hideCal();
			}
		});

		return calendar;
	}

};

// Format of current day at the bottom of the calendar in PHP date format
F.Calendar.l10n_formats = c_format;
K.Calendar = F.Calendar;



/**
 * examples:
 *		<input type="color"/>
 *		<input type="text" class="color"/> = force color picker
 */

var cp = null,
clrNames = {transparent:"rgba(0,0,0,0)",aliceblue:"F0F8FF",darkslategray:"2F4F4F",lightsalmon:"FFA07A",palevioletred:"DB7093",antiquewhite:"FAEBD7",darkturquoise:"00CED1",lightseagreen:"20B2AA",papayawhip:"FFEFD5",aqua:"00FFFF",darkviolet:"9400D3",lightskyblue:"87CEFA",peachpuff:"FFDAB9",aquamarine:"7FFFD4",deeppink:"FF1493",lightslategray:"778899",peru:"CD853F",azure:"F0FFFF",deepskyblue:"00BFFF",lightsteelblue:"B0C4DE",pink:"FFC0CB",beige:"F5F5DC",dimgray:"696969",lightyellow:"FFFFE0",plum:"DDA0DD",bisque:"FFE4C4",dodgerblue:"1E90FF",lime:"00FF00",powderblue:"B0E0E6",black:"000000",firebrick:"B22222",limegreen:"32CD32",purple:"800080",blanchedalmond:"FFEBCD",floralwhite:"FFFAF0",linen:"FAF0E6",red:"FF0000",blue:"0000FF",forestgreen:"228B22",magenta:"FF00FF",rosybrown:"BC8F8F",blueviolet:"8A2BE2",fuchsia:"FF00FF",maroon:"800000",royalblue:"4169E1",brown:"A52A2A",gainsboro:"DCDCDC",mediumaquamarine:"66CDAA",saddlebrown:"8B4513",burlywood:"DEB887",ghostwhite:"F8F8FF",mediumblue:"0000CD",salmon:"FA8072",cadetblue:"5F9EA0",gold:"FFD700",mediumorchid:"BA55D3",sandybrown:"F4A460",chartreuse:"7FFF00",goldenrod:"DAA520",mediumpurple:"9370DB",seagreen:"2E8B57",chocolate:"D2691E",gray:"808080",mediumseagreen:"3CB371",seashell:"FFF5EE",coral:"FF7F50",green:"008000",mediumslateblue:"7B68EE",sienna:"A0522D",cornflowerblue:"6495ED",greenyellow:"ADFF2F",mediumspringgreen:"00FA9A",silver:"C0C0C0",cornsilk:"FFF8DC",honeydew:"F0FFF0",mediumturquoise:"48D1CC",skyblue:"87CEEB",crimson:"DC143C",hotpink:"FF69B4",mediumvioletred:"C71585",slateblue:"6A5ACD",cyan:"00FFFF",indianred:"CD5C5C",midnightblue:"191970",slategray:"708090",darkblue:"00008B",indigo:"4B0082",mintcream:"F5FFFA",snow:"FFFAFA",darkcyan:"008B8B",ivory:"FFFFF0",mistyrose:"FFE4E1",springgreen:"00FF7F",darkgoldenrod:"B8860B",khaki:"F0E68C",moccasin:"FFE4B5",steelblue:"4682B4",darkgray:"A9A9A9",lavender:"E6E6FA",navajowhite:"FFDEAD",tan:"D2B48C",darkgreen:"006400",lavenderblush:"FFF0F5",navy:"000080",teal:"008080",darkkhaki:"BDB76B",lawngreen:"7CFC00",oldlace:"FDF5E6",thistle:"D8BFD8",darkmagenta:"8B008B",lemonchiffon:"FFFACD",olive:"808000",tomato:"FD6347",darkolivegreen:"556B2F",lightblue:"ADD8E6",olivedrab:"6B8E23",turquoise:"40E0D0",darkorange:"FF8C00",lightcoral:"F08080",orange:"FFA500",violet:"EE82EE",darkorchid:"9932CC",lightcyan:"E0FFFF",orangered:"FF4500",wheat:"F5DEB3",darkred:"8B0000",lightgoldenrodyellow:"FAFAD2",orchid:"DA70D6",white:"FFFFFF",darksalmon:"E9967A",lightgreen:"90EE90",palegoldenrod:"EEE8AA",whitesmoke:"F5F5F5",darkseagreen:"8FBC8F",lightgrey:"D3D3D3",palegreen:"98FB98",yellow:"FFFF00",darkslateblue:"483D8B",lightpink:"FFB6C1",paleturquoise:"AFEEEE",yellowgreen:"9ACD32"},
re_hex3 = /^\#?([0-9A-F])([0-9A-F])([0-9A-F])$/i,
re_hex6 = /\#?([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})?/i,
re_rgba = /rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})(?:\s*,\s*(0(?:\.[0-9]*)?))?\s*\)/,
re_hsla = /hsla?\(\s*([0-2]?[0-9]{1,2}|3[0-5][0-9]|360)\s*,\s*([0-9]{1,2}|100)%\s*,\s*([0-9]{1,2}|100)%(?:\s*,\s*(0(?:\.[0-9]*)?))?\s*\)/;

F.Color = function(input, force)
{
	input.type = "text";
	if (!input._ph5fi || force) {
		input._ph5fi = "color";
		input.on("click keydown", showColorPicker)
			.attr("autocomplete","off")
			.on(DCVC,function(){clrChanged(input);})
			.readOnly = true;
		input.form.on("afterreset",function(){clrChanged(input);});
		clrChanged(input);
	}

	function clrChanged(o)
	{
		if (o.value) {
			o.title = o.value;
			getColor(o.value).set_bg(o);
		} else {
			o.attr("title",null).attr("style",null);
		}
	}

	function mm(v,m){m=m||255;return intval(v<0?0:v>m?m:v);} // intval(Math.max(0,Math.min(m||255,v))) ??
	function RGBColor(r,g,b,a)
	{
		function h(c){c=intval(c).toString(16);return c.length%2?"0"+c:c;}
		function rgb1(c){
			c={r:c.r/255, g:c.g/255, b:c.b/255};
			c.max=Math.max(c.r,c.g,c.b);
			c.min=Math.min(c.r,c.g,c.b);
			c.m=c.max+c.min;
			c.s=c.max-c.min;
			return c;
		}
		if (isNaN(a)) a = (cp.cpColor||cp.baseColor).a;
		this.r = mm(r);
		this.g = mm(g);
		this.b = mm(b);
		this.a = isNaN(a)?100:mm(a,100);
		this.hex = function(){return "#"+h(this.r)+h(this.g)+h(this.b);};
		this.rgb = function(a){
			if (a && 0==this.a) return "transparent";
			a=a&&100>this.a;
			return "rgb"+(a?"a":"")+"("+this.r+","+this.g+","+this.b+(a?","+(this.a/100):"")+")";
		};
		this.hsl = function(a){
			if (a && 0==this.a) return "transparent";
			var c=rgb1(this), d=h=s=c.s, l=c.m/2;
			if (0 < d) {
				s = l > 0.5 ? d/(2-d) : d/c.m;
				h = (c.max==c.r ? ((c.g-c.b)/d+(c.g<c.b?6:0)) : (c.max==c.g ? ((c.b-c.r)/d+2) : ((c.r-c.g)/d+4))) / 6;
			}
			a=a&&100>this.a;
			return "hsl"+(a?"a":"")+"("+intval(h*360)+","+intval(s*100)+"%,"+intval(l*100)+"%"+(a?","+(this.a/100):"")+")";
		};
		this.rgba = function(){return this.rgb(1);};
		this.hsla = function(){return this.hsl(1);};
		this.h = function(){
			var c=rgb1(this), h=c.s;
			return 0<h ? intval((c.max==c.r ? ((c.g-c.b)/h+(c.g<c.b?6:0)) : (c.max==c.g ? ((c.b-c.r)/h+2) : ((c.r-c.g)/h+4))) * 60) : 0;
		};
		this.s = function(){
			var c=rgb1(this), s=c.s;
			return 0<s ? intval(((c.m/2) > 0.5 ? s/(2-s) : s/c.m) * 100) : 0;
		};
		this.l = function(a){return intval(rgb1(this).m/2 * 100);};

		this.set_bg = function(o) {
			o.style.backgroundColor = this.rgba();
		};

		this.asWebSafe = function()
		{
			return new RGBColor(
				Math.round(this.r/51)*51,
				Math.round(this.g/51)*51,
				Math.round(this.b/51)*51
			);
		};
	}

	// http://www.w3.org/TR/css3-color/
	// http://en.wikipedia.org/wiki/HSL_and_HSV
	function hsl2rgb(h,s,l,a)
	{
		var r=l, g=l, b=l, q=l<0.5?l*(s+1):l+s-l*s, p=l*2-q;
		if (0 != s) {
			function hue2rgb(h){
				h += h<0 ? 1 : (h>1 ? -1 : 0);
				return (h*6<1) ? (p+(q-p)*h*6) : ((h*2<1) ? q : ((h*3<2) ? p+(q-p)*(2/3-h)*6 : p));
			}
			r = hue2rgb(h+1/3);
			g = hue2rgb(h);
			b = hue2rgb(h-1/3);
		}
		return new RGBColor(r*255, g*255, b*255, isNaN(a)?100:a*100);
	}

	function getColor(text)
	{
		function i(v){return parseInt(v,16);}
		var m, v = clrNames[text.toLowerCase()] || text;
		if (m = v.match(re_hex6)) return new RGBColor(i(m[1]), i(m[2]), i(m[3]), m[4]?intval(i(m[4])/2.55):100);
		if (m = v.match(re_hex3)) return new RGBColor(i(m[1]+m[1]), i(m[2]+m[2]), i(m[3]+m[3]), 100);
		if (m = v.match(re_rgba)) return new RGBColor(m[1], m[2], m[3], isNaN(m[4])?100:m[4]*100);
		if (m = v.match(re_hsla)) return hsl2rgb(m[1]/360,m[2]/100,m[3]/100,m[4]);
		return new RGBColor(255,0,0);
	}
	function getGradientColor(x, y, Base)
	{
		x = mm(x); y = mm(y);
		return new RGBColor(
			intval((1-(1-(Base.r/255))*(x/255))*(255-y)),
			intval((1-(1-(Base.g/255))*(x/255))*(255-y)),
			intval((1-(1-(Base.b/255))*(x/255))*(255-y))
		);
	}
	function getHorizColor(x, y, width, height)
	{
		var base, i=y*width+x,
		sw=width/7,             // "section" width
		C=i%width,              // column
		R=Math.floor(i/(sw*7)), // row
		c=i%sw,                 // column in current group
		r,g,b,h,
		l=(255/sw)*c;          // color percentage
		if (C>=sw*6) {
			r=g=b=255-l;
		} else {
			h=255-l;
			r=C<sw?255:C<sw*2?h:C<sw*4?0:C<sw*5?l:255;
			g=C<sw?l:C<sw*3?255:C<sw*4?h:0;
			b=C<sw*2?0:C<sw*3?l:C<sw*5?255:h;
			if (R<height/2) {
				base = 255-(255*2/height)*R;
				var m=R*2/height;
				r=r*m+base;
				g=g*m+base;
				b=b*m+base;
			} else if (R>height/2) {
				base = (height-R)/(height/2);
				r=r*base;
				g=g*base;
				b=b*base;
			}
		}
		return new RGBColor(r, g, b);
	}
	function getVertColor(i, s)
	{
		var n=s/6, j=s/n, C=i, c=i%n;
		return new RGBColor(
			C<n?255:C<n*2?255-c*j:C<n*4?0:C<n*5?c*j:255,
			C<n?c*j:C<n*3?255:C<n*4?255-c*j:0,
			C<n*2?0:C<n*3?c*j:C<n*5?255:255-c*j
		);
	}

	function setCPHSLColor() { setCPColor(hsl2rgb(cp.hInput.value/360, cp.sInput.value/100, cp.lInput.value/100, cp.aInput.value/100)); }
	function setCPColor(color)
	{
		if (color.target) color=null;
		if (is_string(color)) color = getColor(color);
		if (!color) color = new RGBColor(cp.rInput.value, cp.gInput.value, cp.bInput.value, cp.aInput.value);

		var wsColor = color.asWebSafe(), d="block";
		if (wsColor.rgb() !== color.rgb()){
			cp.websafeImg.style.backgroundColor = wsColor.hex();
		} else {
			d = "none";
		}
		cp.websafeImg.style.display = d;
		cp.hInput.value = color.h();
		cp.sInput.value = color.s();
		cp.lInput.value = color.l();
		cp.rInput.value = color.r;
		cp.gInput.value = color.g;
		cp.bInput.value = color.b;
		cp.aInput.value = color.a;
		cp.cpColor      = color;
		color.set_bg(cp.curColorDiv);
		cpSliderSetPos(intval(color.h()*255/360));
	}

	function saveColor(e)
	{
		if (defined(cp.cpColor)) {
			var o=cp.cpInput;
			o.setValue(cp.cpColor[100>cp.cpColor.a?"rgba":"hex"]());
			hideColorPicker(e);
			o.trigger("change");
		}
	}

	function cpsMouseMove(e)
	{
		var hCPI = e.target, m = hCPI.getMousePos(e);
		cp.cpColor = getHorizColor(m.x, m.y, intval(hCPI.offsetWidth), intval(hCPI.offsetHeight));
		cp.cpColor.set_bg(cp.sColor);
	}

	function showColorPicker(e)
	{
		var kc = e.keyCode;
		// Enter || Escape || Space || Up || Down
		if (kc && 13!=kc && 27!=kc && 32!=kc && 38!=kc && 40!=kc) return;
		if (!cp) {
			// createColorPicker
			cp = document.body.$A(
			["DIV", {id:"poodle-colorpicker", defWidth:400, defHeight:400},
				["DIV", {"class":"small", ref:"sColorPicker"},
					["I", {"class":"color", ref:"sColor"}],
					["I", {"class":"colors", onclick:saveColor, onmousemove:cpsMouseMove}],
					["I", {"class":"resize", onclick:showColorPicker}]
				],
				["DIV", {"class":"big", ref:"fColorPicker"},
					["I", {"class":"resize", onclick:showColorPicker}],
					["DIV", {"class":"colors", ref:"fColorImg", onmousedown:cpMouseDown, onclick:cpMouseClick}],
					["DIV", {"class":"slider", ref:"Arrows", onmousedown:cpSliderMouseDown, onclick:cpSliderClick}, ["I", {ref:"colorSlider"}]],
					["DIV", {"class":"clrGroup"},
						["DIV", {"class":"curClr", ref:"curColorDiv"}],
						["DIV", {"class":"orgClr", ref:"orgColorDiv", onclick:resetColor}]
					],
					["I", {"class":"ws", title:"Click to Select Web Safe Color", ref:"websafeImg", onclick:setWebSafeColor}],
					["LABEL", {"class":"H",title:"hue"},        "H:", ["INPUT", {type:"number", min:0, max:359, ref:"hInput", onchange:setCPHSLColor}]],
					["LABEL", {"class":"S",title:"saturation"}, "S:", ["INPUT", {type:"number", min:0, max:100, ref:"sInput", onchange:setCPHSLColor}]],
					["LABEL", {"class":"L",title:"luminance"},  "L:", ["INPUT", {type:"number", min:0, max:100, ref:"lInput", onchange:setCPHSLColor}]],
					["LABEL", {"class":"R",title:"red"},        "R:", ["INPUT", {type:"number", min:0, max:255, ref:"rInput", onchange:setCPColor}]],
					["LABEL", {"class":"G",title:"green"},      "G:", ["INPUT", {type:"number", min:0, max:255, ref:"gInput", onchange:setCPColor}]],
					["LABEL", {"class":"B",title:"blue"},       "B:", ["INPUT", {type:"number", min:0, max:255, ref:"bInput", onchange:setCPColor}]],
					["LABEL", {"class":"A",title:"alpha"},      "A:", ["INPUT", {type:"number", min:0, max:100, value:100, ref:"aInput", onchange:setCPColor}]],
					["BUTTON", {type:"button", onclick:saveColor}, "OK"],
					["BUTTON", {type:"button", onclick:hideColorPicker}, "Cancel"]
				]
			]);
//			F.Number(cp.rInput);
			cp.baseColor = new RGBColor(255,0,0,100);
		}
		var o = e.target, w={s:cp.sColorPicker, b:cp.fColorPicker}, n=o.lowerName(),
		size = ("block"==w.b.style.display?"b":"s");
		if ("input"==n) {
			if ("block" == cp.style.display && o == cp.cpInput) { hideColorPicker(e); return; }
			cp.cpInput = o;
		}
		if ("i"==n) size = ("b"==size?"s":"b");
		o = cp.cpInput;
		reposition(cp,o);
		cp.style.display = "block";
		w.s.style.display = w.b.style.display = "none";
		w[size].style.display = "block";
		reposition(cp,o);
		cp.baseColor = cp.origColor = getColor(o.value);
		if ("s"==size) {
			cp.baseColor.set_bg(cp.sColor);
		} else {
			cp.fColorImg.style.backgroundColor = cp.baseColor.hex();
			cp.baseColor.set_bg(cp.orgColorDiv);
			setCPColor(cp.baseColor);
		}
		e.stop();
	}

	function followMouse(e, mousemove)
	{
		var f = function(){document.off("mousemove",mousemove).off("mouseup",arguments.callee);};
		document.on("mousemove",mousemove).on("mouseup",f);
		e.stop();
	}

	function cpMouseDown(e) {followMouse(e,cpMouseClick);}
	function cpMouseClick(e){
		var o=cp.fColorImg, m = o.getMousePos(e);
		setCPColor(getGradientColor(m.x-intval(o.css("border-left-width",1)), m.y-intval(o.css("border-top-width",1)), cp.baseColor));
	}

	function cpSliderMouseDown(e){followMouse(e,cpSliderClick);}
	function cpSliderClick(e){
		var o=cp.colorSlider, m=o.getMousePos(e);
		cp.baseColor = getVertColor(cpSliderSetPos(m.y-o.offsetTop), 256);
		cp.fColorImg.style.backgroundColor = cp.baseColor.hex();
		cp.hInput.value = cp.baseColor.h();
		cp.hInput.trigger("change");
	}
	function cpSliderSetPos(y)
	{
		y = y<0?0:y>255?255:y;
		cp.Arrows.style.backgroundPosition = cp.Arrows.css("background-position").replace(/[^\s]+$/, y+"px");
		return y;
	}

	function setWebSafeColor(){setCPColor(cp.cpColor.asWebSafe());}
	function resetColor(){setCPColor(cp.origColor);}
	function hideColorPicker(e){cp.cpInput.focus();cp.style.display="none";cp.cpColor=null;e.stop();}
};

/**
 * Checkbox & Radio
 */

function CheckboxOrRadio(input, force)
{
	function click(e)
	{
		e.stop();
		var o = e.target.input||e.target, v=o.checked;
		if (!o.disabled && !o.readOnly) {
			if (e.target == e.currentTarget) {
				o.click();
			} else {
				o.a.focus();
				if (v || "radio"!=o.type) {
					o.checked=false;
					setTimeout(function(){o.checked=v;o.trigger("change");},1);
				}
			}
		}
	}

	function attr(e)
	{
		if ("disabled"==e.attrName) {
			e.target.a[e.newValue?"addClass":"removeClass"]("disabled");
		}
	}

	if (!input._ph5fi || force) {
		input._ph5fi = input.type;
		var o=input,
		a = o.a = K.$C("a",{
			"class":"input-"+o.type /*+ (o.disabled?" disabled":"")*/,
			href:"#",
			input:o,
			onclick:click,
			onkeyup:function(e){if(32==e.keyCode)click(e);},
			onkeydown:function(e){if(32==e.keyCode)e.stop();}
		}),
		cf=function(){
			if ("radio"==o.type) {
				var i=0, n, fields = o.form.$Q('input[type="radio"]');
				while (n = fields[i++]) if (n.a && n.name == o.name) n.a[n.checked?"addClass":"removeClass"]("checked");
			} else {
				o.a[o.checked?"addClass":"removeClass"]("checked");
			}
		};
		o.replaceWith(a);
		a.$A(o)
			.on("change "+DCVC,cf)
//			.on("DOMAttrModified",attr) // Disabled form controls in Mozilla call stopPropagation on all events, so the listener in question doesn't fire.
			.form.on("afterreset",cf);
		cf();
	}
}

F.Checkbox = F.Radio = CheckboxOrRadio;

F.Select = function(s)
{
	// Create <span class="selectbox"> around <select> for custom styling
	if (!s._ph5fi && 'select-one' === s.type) {
		s._ph5fi = 1;
		var d = K.$C('span', {'class':'selectbox'}),
			o = s.currentOption(),
		   fn = function(){
				var o = s.currentOption();
				s.textbox.txt(o?o.txt()||'':'');
			};
		s.textbox = d.$A('span',{textContent:o?o.txt()||'':''});
		s.span = d;
		s.on('change keydown keyup',fn)
		.on('focus',function(){this.span.addClass('focus').removeClass('invalid');})
		.on('blur',function(){this.span.removeClass('focus');})
		.replaceWith(d);
		setOnInvalidate(s);
		d.$A(s);
		s.form.on("afterreset",fn);
	}
};

F.InputDatalist = function(o)
{
	if (!o._bmdatalist && o.attr("list")) {
		/* User Interaction according to Firefox (any key != up/down arrow | tab | enter):
			1: input blur: close list
			2: mouse:
				a: click & input no focus: don't open list
				b: click & input has focus: open list
				c: hover on option: set current selected
				d: click on list option: set option value
			3: keyboard:
				a: input has focus:
					a: arrow down & closed: open list
					b: arrow down & open: make first option selected
					c: arrow up & open: make last option selected
					d: any key: close list (when no match | control character)
				b: option selected
					a: arrow up/down item: select prev/next option
					b: arrow down last item: none selected
					c: arrow up first item: none selected
					d: [enter]: set option value
		*/
		o._bmdatalist = K.$(o.attr("list"));
		if (o._bmdatalist) {
			// <input type="text" list="example"><datalist id="example"><option/></datalist>
			// <span class="combobox"><input type="text" list="example"/><span class="datalist"><a></a></span></span>
			o.cb = K.$C("span",{"class":"combobox",input:o});
			o.replaceWith(o.cb);
			o.cb.$A(o);
			o.cb.datalist = o.cb.$A("select",{"class":"datalist", hidden:"", "size":6, cb:o.cb});
			o.cb.datalist._ph5fi = 1;
			o.cb.datalist.selectNext = function(){
				var i = this.selectedIndex+1;
				this.selectedIndex = (i<this.options.length) ? i : -1;
			};
			o.cb.datalist.selectPrev = function(){
				var i = this.selectedIndex-1;
				this.selectedIndex = (i>=-1) ? i : this.options.length-1;
			};
			o.cb.showDatalist = function(){
				this.input.openDatalist = false;
				// $Q("option") fails in IE8, "*" returns HTMLUnknownElement
				// Doesn't work at all in IE9 as <option> inside <datalist> is removed
				// It does work when using <datalist><select><option>
				var dl = this.datalist, odl=this.input._bmdatalist, i=-1, n, options = odl.$Q("option"), v, vi=this.input.value.trim().toLowerCase();
				dl.options.length = 0;
				if (!options.length) { options = odl.$Q("*"); }
				if (options.length) {
					while (n = options[++i]) {
						v = n.attr("value");
						if (''==vi || -1 < v.toLowerCase().indexOf(vi)) {
							dl.$A("option",{value:v, textContent:v});
						}
					}
				}
				if (dl.options.length) {
					dl.size = Math.max(2,Math.min(10,dl.options.length));
					dl.setCSSProperty('width',this.input.clientWidth+'px');
					dl.show();
				} else {
					dl.hide();
				}
			};

			// 1: input blur: close list
			o.on('blur',function(e){
				var o=this;
				setTimeout(function(){
					if (!o.hasFocus() && !o.cb.datalist.hasFocus()) { o.cb.datalist.hide(); }
				},50);
			})
			// 2.a: click & input no focus: don't open list
			// 2.b: click & input has focus: open list
			.on('mousedown',function(){
				this.openDatalist = this.hasFocus();
			}).on('click',function(){
				if (this.openDatalist) { this.cb.showDatalist(); }
			})
			// Keyboard
			.on('keydown',function(e){
				var dl = this.cb.datalist, cc = e.which||e.keyCode, hidden = dl.hasAttribute("hidden");;
				if (40 == cc) {
					if (hidden) {
						// 3.a.a: arrow down & closed: open list
						this.cb.showDatalist();
					} else {
						// 3.a.b: arrow down & open: make first option selected
						// 3.b.a: arrow up/down item: select prev/next option
						// 3.b.c: arrow up first item: none selected
						dl.selectNext();
					}
				} else if (38 == cc) {
					if (!hidden) {
						// 3.a.c: arrow up & open: make last option selected
						// 3.b.a: arrow up/down item: select prev/next option
						// 3.b.b: arrow down last item: none selected
						dl.selectPrev();
					}
				} else {
					if ((13 == cc || 37 == cc || 39 == cc) && !hidden) {
						// 3.b.d: [enter]: set option value
						var i = dl.selectedIndex;
						if (i>=0) { this.value = dl.options[i].value; }
						this.cb.datalist.hide();
					}
				}
			}).on('keypress',function(e){
				var dl = this.cb.datalist, cc = e.which||e.keyCode;
				// 3.a.d: any key: close list (when no match | control character)
				if (e.charCode) {
					var cb=this.cb;
					setTimeout(function(){cb.showDatalist();},50);
				} else {
					this.cb.datalist.hide();
				}
			}).on('keyup',function(e){
				var cc = e.which||e.keyCode;
				if (8 == cc || 46 == cc) {
					var cb=this.cb;
					setTimeout(function(){cb.showDatalist();},50);
				}
			});

			o.cb.datalist.on('focus',function(e){
				this.cb.input.focus();
			}).on('mousemove',function(e){
				// 2.c: hover on option: set current
				if ("option"==e.target.nodeName.toLowerCase()) {
					var o=this.options, l=o.length, i=0;
					for (;i<l;++i) {
						o[i].selected = e.target == o[i];
					}
				}
			}).on('click',function(e){
				//  2.d: click on list option: set option value
				var i = this.cb.input;
				if (e.target.value) { i.value = e.target.value; }
				i.focus();
				this.hide();
			});
		}
	}
};

F.Button = function(o)
{
	if (!o._ph5fi) {
		var t = o.attr("type");
		o._ph5fi = 1;
		// http://xhtml5.nl/naslag/html/attributen/formulieren
		if ("submit"==t || "image"==t) {
			o.on("click", function(){
				var a=["action","enctype","method","target","novalidate"], i=0,
				form = o.attr("form");
				form = is_string(form) ? K.$(form) : o.form;
				for (;i<a.length;++i) {
					if (o.attr("form"+a[i])) { form.attr(a[i], o.attr("form"+a[i])); }
				}
			});
		}
		else if ("button"==t && "get"==o.attr("formmethod") && o.attr("formaction")) {
			o.on("click", function(e){document.location.href = o.attr("formaction");});
		}
	}
};

function checkMaxLength()
{
	var o = this, l=intval(o.attr('maxlength'));
	if (l) {
		if (o.value.length > l) { o.value = o.value.substr(0,l); }
		if (o.mlc) { o.mlc.txt(o.value.length+"/"+l); }
	}
}
F.visualMaxLength = function(o)
{
	var l=intval(o.attr('maxlength'));
	if (l>0) {
		var mlc = K.$C('em',{'class':'maxlength-counter'}).txt(o.value.length+"/"+l);
		mlc.placeAfter(o);
		o.mlc = mlc;
		o.on('keyup keypress change', checkMaxLength);
	}
};

/**
 * Do magic trick when DOMContentLoaded
 */
K.onDOMReady(function(){F();});
K.HTML5Form = F;

})();
