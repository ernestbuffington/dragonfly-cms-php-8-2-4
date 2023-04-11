/**
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	@import "poodle"
	@import "poodle_forms"
	@import "poodle_resize"
	@import "poodle_tabs"

	http://www.w3.org/TR/html5/editing.html

	http://msdn.microsoft.com/en-us/library/ms533049%28v=vs.85%29.aspx
	* RemoveFormat

	Opera:
		on double click selection like <s><a></a></s> the setNodePath fails
*/

(function(){

var P = Poodle,
attribs_window = null,
colors_window = null,
body_observer = null,
inserting = false,
self_closing_tags = /<(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)([^>]*)>/g;

// Safari4: n.lowerName is not a function
function lc(v){return v.toLowerCase();}

function getRange(win)
{
	var s = win.getSelection(), r, d = win.document;
	try {
		if (s) { r = s.rangeCount > 0 ? s.getRangeAt(0) : (s.createRange ? s.createRange() : d.createRange()); }
	} catch (e) {}
	if (!r) { r = d.createRange ? d.createRange() : d.body.createTextRange(); }
/*
	if (s) {
		if (s.createRange) {
			r = s.createRange();
		} else if (s.getRangeAt) {
			r = s.getRangeAt(0);
		} else if (s.anchorNode && s.focusNode && doc.createRange) {
			// Older WebKit browsers
			r = doc.createRange();
			r.setStart(s.anchorNode, s.anchorOffset);
			r.setEnd(s.focusNode, s.focusOffset);
			// Handle the case when the selection was selected backwards (from the end to the start in the
			// document)
			if (r.collapsed !== s.isCollapsed) {
				r.setStart(s.focusNode, s.focusOffset);
				r.setEnd(s.anchorNode, s.anchorOffset);
			}
		}
	}
*/
	return r;
}

function getNode(doc, store)
{
	var n, win = doc.$W() || window,
		r = getRange(win),
		s = win.getSelection();
	// Range maybe lost after the editor is made visible again
	if (!r) { return doc.$B(); }
	n = r.focusNode || r.endContainer || r.commonAncestorContainer;
	// Handle selection a image or other control like element such as anchors
	if (!r.collapsed) {
		// If the anchor node is a element instead of a text node then return this element
		if (P.UA.webkit && s.anchorNode && 1 == s.anchorNode.nodeType)
			return s.anchorNode.childNodes[s.anchorOffset];
		if (r.startContainer == r.endContainer
		 && r.startOffset - r.endOffset < 2
		 && r.startContainer.hasChildNodes())
		{
			n = r.startContainer.childNodes[r.startOffset];
		}
	}
	while (n && 1 != n.nodeType) { n = n.parentNode; }
	return n;
}

function initFakeElements(o)
{
	var i=-1, n, els = o.$Q('select');
/*
	// HTMLSelectElement doesn't receive focus and mouse events are ignored
	while (els[++i]) {
		if (!els[i].fakeElement) {
			els[i].fakeElement = n = o.$C('span',{'class':'wysiwyg-fake-element',style:{position:'relative'}});
			els[i].replaceWith(n);
			n.$A(els[i]);
			n.$A('span',{originalElement:els[i],'class':'wysiwyg-fake-element-overlay',style:{position:'absolute',top:0,bottom:0,right:0,left:0}});
		}
	}
*/
	// Init tabs - new tab
	i = -1;
	els = o.$Q("ul.tabs");
	while (els[++i]) {
		//<li class="new"><a class="tab">+</a></li>
		n = P.$C("li",{"class":"new"});
		n.$A("a",{"class":"tab",innerHTML:"+"});
		els[i].insertBefore(n, null);
	}
}

var DOM_DEF_ATTRIBUTES = ['title','class','id'/*,'dir','lang','xml:lang'*/];
var ELEMENT_ATTRIBUTES = {
	a          : ['href','rel','hreflang'/*,'accesskey','charset','coords','name','rev','shape','tabindex','target','type'*/],
	abbr       : [],
	address    : [],
	area       : [/*'alt','accesskey','coords','href','nohref','shape','tabindex','target'*/],
	article    : [], // HTML5
	aside      : [], // HTML5
	audio      : [/*'autoplay','controls','loop','preload','src'*/], // HTML5
	b          : [],
//	base       : [/*'href','target'*/],
	bdo        : [],
	blockquote : [/*'cite'*/],
//	body       : [],
	br         : [/*'clear'*/],
	// formaction, formenctype, formmethod & formnovalidate only when type=submit
	button     : ['disabled','name','type'/*'accesskey','tabindex','value','form','formaction','formenctype','formmethod','formnovalidate'*/],
	canvas     : [], // HTML5
	caption    : [],
	cite       : [],
	code       : [],
	col        : [/*'char','charoff','span','valign','width'*/],
	colgroup   : [/*'span'*/],
	command    : ['disabled'/*,'checked','icon','label','radiogroup','type'*/], // HTML5
	datalist   : [], // HTML5
	dd         : [],
	del        : [/*'cite','datetime'*/],
	details    : ['open'], // HTML5
	dfn        : [],
	div        : [],
	dl         : [],
	dt         : [],
	em         : [],
	embed      : [/*'src','type'*/], // HTML5
	fieldset   : [],
	figcaption : [], // HTML5
	figure     : [], // HTML5
	footer     : [], // HTML5
	form       : ['action','method'/*,'accept-charset','enctype','name','novalidate'*/],
	h1         : [],
	h2         : [],
	h3         : [],
	h4         : [],
	h5         : [],
	h6         : [],
//	head       : [/*'profile'*/],
	header     : [], // HTML5
	hgroup     : [], // HTML5
	hr         : [],
//	html       : [],
	i          : [],
	iframe     : ['allowfullscreen','src'/*'height','sandbox','seamless','srcdox','width'*/],
	img        : ['alt','src'/*,'height','ismap','longdesc','usemap','width'*/],
	// formaction, formenctype, formmethod & formnovalidate only when type=submit|image
	input      : ['disabled','name','placeholder','type','value'/*'accept','accesskey','alt','checked','maxlength','readonly','size','src','tabindex',
		'autocomplete','autofocus','formaction','formenctype','formmethod','formnovalidate','formtarget','list','max','min','multiple','pattern','required','step'*/],
	ins        : [/*'cite','datetime'*/],
	keygen     : ['disabled'/*,'autofocus','challenge','form','keytype','name'*/], // HTML5
	kbd        : [],
	label      : [/*'accesskey','for'*/],
	legend     : [/*'accesskey'*/],
	li         : [/*'type','value'*/],
//	link       : [/*'disabled','charset','href','hreflang','media','rel','rev','target','type'*/],
	main       : [], // HTML5
	map        : [/*'name'*/],
	mark       : [], // HTML5
	menu       : [], // HTML5 redefined
//	meta       : [/*'content','http-equiv','name','scheme'*/],
	meter      : [/*'high','low','max','min','optimum','value'*/], // HTML5
	nav        : [], // HTML5
//	noscript   : [],
	object     : [/*'archive','border','classid','codebase','codetype','data','declare','height','hspace','name','standby','tabindex','type','usemap','vspace','width'*/],
	ol         : [],
	optgroup   : ['disabled','label'],
	option     : ['disabled',/*'label','selected',*/'value'],
	output     : [/*'for','form','name'*/], // HTML5
	p          : [],
	param      : [/*'name','type','value','valuetype'*/],
	pre        : [],
	progress   : [/*'max','value'*/], // HTML5
	q          : [/*'cite'*/],
	rp         : [], // HTML5 Ruby
	rt         : [], // HTML5 Ruby
	ruby       : [], // HTML5 Ruby
	samp       : [],
//	script     : [/*'type'=>'text/javascript','charset','defer','src'*/],
	section    : [/*'cite'*/], // HTML5
	select     : ['disabled','name'/*,'multiple','size','tabindex'*/],
	small      : [],
	source     : [/*'media','src','type'*/], // HTML5
	span       : [],
	strong     : [],
//	style      : [/*'type'=>'text/css','media'*/],
	sub        : [],
	summary    : [], // HTML5
	sup        : [],
	table      : [/*'border'*/],
	tbody      : [],
	td         : [/*'colspan','headers','rowspan'*/],
	textarea   : ['disabled','name','placeholder','maxlength'/*'cols','rows','accesskey','readonly','tabindex','wrap','required'*/],
	tfoot      : [],
	th         : [/*'colspan','headers','rowspan','scope'*/],
	thead      : [],
	time       : [/*'datetime'*/], // HTML5
	title      : [],
	tr         : [/*'char','charoff','valign'*/],
	track      : [/*'kind','src','srclang','label','default'*/], // HTML5
	ul         : [],
	"var"      : [],
	video      : [/*'autoplay','controls','loop','preload','src','poster'*/], // HTML5
	wbr        : [] // HTML5
};

var TAL_ATTRIBUTES = [
	"tal:define",
	"tal:condition",
	"tal:condition-else",
	"tal:repeat",
	"tal:content",
	"tal:replace",
	"tal:attributes",
	"tal:omit-tag",
	"i18n:translate",
	"i18n:attributes"
];

/**
 * Area controller, can be an HTMLIFrameElement, HTMLObjectElement,
 * HTMLTextAreaElement or else a generic HTMLElement
 */
function PW_Area(node)
{
	var n = node,
	is_text = ("textarea" == lc(n.nodeName));

	function body(n) { return (n.$Doc ? n.$Doc().$B() : n); }
	function doc(n)  { return (n.$Doc ? n.$Doc() : n.$D()); }
	function win(n)  { return (n.$Win ? n.$Win() : n.$W()); }

	this.focus = function(){
		try{(n.$Win?n.$Win():n).focus();}catch(e){} // IE8 failure
		return this;
	};

	this.getNode = function(){ return n; };

	this.getSelection = function() { return win(n).getSelection(); };

	this.getRange = function() {
		if (is_text) {
			var r = doc(n).createRange();
			r.setStart(n, 0);
			r.setEnd(n, 0);
			r.selectionStart = n.selectionStart;
			r.selectionEnd = n.selectionEnd;
			return r;
		}
		var s = this.getSelection();
		return s && s.rangeCount ? s.getRangeAt(0) : this.sel_range;
	};

	this.setRange = function(r) {
		if (r) try {
			var s, e, o = r.startContainer, t, i;
			if (is_text) {
				s = r.selectionStart;
				e = r.selectionEnd;
				if (!defined(r.selectionStart)) {
					t = (o?o.innerHTML||o.data:r.text);
					i = n.value.indexOf(">"+t);
					i = (-1<i) ? i+1 : n.value.indexOf(t);
					if (-1<i) {
						s = i+r.startOffset;
						e = i+r.endOffset;
					}
				}
				if (s > 0) {
					n.setSelectionRange(s, e);
				}
			} else if (r.select) {
				r.select();
			} else {
				if (o && 'TEXTAREA' === o.nodeName) {
					r = document.createRange();
					r.setStart(body(n), 0);
					try {
						// Find cursor position in HTML
						t = body(n).html().substr(0, o.selectionStart);
						e = t.split(/(<\/?\w+)/);
						s = [''];
						var depth = 0, skip = 0;
						for (i = 0; i < e.length; ++i) {
							if ('<' === e[i][0]) {
								if ('/' === e[i][1]) {
									s[depth] = '';
									--depth;
									skip = 1;
								} else {
									e[i] = e[i].substr(1);
									if (('<'+e[i]+'>').match(self_closing_tags)) {
										s[depth] += e[i] + ' + ';
										skip = 1;
									} else {
										skip = 0;
										if (depth < s.length) {
											s[depth] += e[i] + ' + ';
										} else {
											s.push(e[i]);
										}
										++depth;
									}
								}
							}
						}
						for (i = 0; i < s.length; ++i) {
							if (s[i].length) {
								s[i] = s[i].substr(0,s[i].length-2) + '>';
							} else if (i) {
								s[i-1] = s[i-1].substr(0,s[i-1].length-2);
							}
						}
						s = s.join(' ').replace(/[\s>]+$/, '');
						i = s ? body(n).$Q(s, 1) : 0;
						if (i)  {
							s = t.substr(t.lastIndexOf('>')).match(/&[^\s&;]*;/g, '$1') || 0;
							if (s) {
								s = s.join('').length - s.length;
							}
							s += t.lastIndexOf('>') + 1;
							i = skip ? i.nextSibling : i.childNodes[0];
							r.setStart(i, Math.min(Math.max(0, o.selectionStart - s), i.nodeValue.length));
						}
					} catch (e) { console.error(e); }
				}
				s = this.getSelection();
				s.removeAllRanges();
				s.addRange(r);
			}
		} catch (e) { console.error(e); }

		return this;
	};
	/*
	selectText = function(node, text)
	{
		if (window.getSelection)
		{
			node.selectionStart = node.value.lastIndexOf(text);
			node.selectionEnd = node.value.lastIndexOf(text) + text.length;
			node.focus();
		}
		else if (document.selection && document.body.createTextRange)
		{
			var range = document.body.createTextRange();
			range.moveToElementText(node);
			range.findText(text);
			range.select();
		}
	}
	*/

	this.getValue = function(){
		// use iframe body
		var o = body(n);
		// ELEMENT_NODE
		if (o && 1 == o.nodeType) {
			return is_text ? o.value : o.html();
		}
	};

	this.setValue = function(html){
		var i=-1;
		while (PW.elementMap[++i]) { html = html.replace(PW.elementMap[i][0], PW.elementMap[i][1]); }
		html = html.trim();

		if (is_text) {
			n.value = html;
		} else {
			body(n).html(html);
		}
	};

	this.getBody     = function(){ return is_text ? null : body(n); };
	this.getDocument = function(){ return is_text ? null : doc(n); };
	this.getWindow   = function(){ return is_text ? null : win(n); };
}

function PW(node)
{
	if (!node || node.wysiwyg) { return; }
	var pw = this,

	buttons = [],

	container = null, // fieldset around wysiwyg

	current = null, // current *area
	htmlarea = null,
	textarea = null,

	statusbar = null,

	node_class = node.className,
	config;

	// Firefox edit mode fails when not turning off and then on again
	function reactivateHTMLArea()
	{
		// Store selection to recreate after enabling
		var a = htmlarea,
		    b = a.getDocument().$B(),
		    r = a.getRange(),s;
		if (!b.contentEditable) {
			b.contentEditable=false;
			b.contentEditable=true;
		}
		// Recreate selection now
		a.setRange(r);
	}

	this.focus = function()
	{
		var n = current.focus().getNode();
		if (n.selection) { n.selection.select(); }
	};

	this.getNode = function() { return getNode(current.getDocument()); };

	// update the wysiwyg to contain the source for the textarea content
	this.updateHTMLArea = function(initial)
	{
		var a = htmlarea;
		if (a) {
			var n = a.getNode();
			if (initial || P.UA.webkit) {
				a.setValue(textarea.getValue());
			} else {
				reactivateHTMLArea();
				pw.execCommand('selectAll'); // WebKit keeps first block element
				pw.execCommand('inserthtml',textarea.getValue());
			}
//			initFakeElements(a.getBody());
			// Due to blur IE loses the selection :(
			if (P.UA.ie) {
				n.onblur = function(){
					var w = a.getWindow();
					a.sel_range = (w.sel_range || getRange(w));
				};
			}
		}
	};

	// update the textarea to contain the source for the wysiwyg content
	this.updateTextArea = function()
	{
		if (current != textarea) {
			textarea.setValue(this.getValue());
		}
	};

	this.getValue = function() { return current.getValue(); };

	this.setValue = function(text)
	{
		textarea.setValue(text);
		this.updateHTMLArea();
	};

	this.editAttributes = edit_attributes;

	this.setConfig = function(cfg)
	{
		config = is_string(cfg) ? JSON.parse(cfg||'{}') : cfg;
		var css = (config ? config.css : 't:admin:style;p:tabs');
		write_iframe(htmlarea, node_class, (css?P.ROOT+'?css='+css:css));
	};

	// execute command
	this.execCommand = function(cmd, value)
	{
		this.focus();
		if (cmd) {
			var a=htmlarea;
//			if ("inserthtml" == cmd) { value = value+"<br/>"; }
			if ((!value || '' == value) && /^(inserthtml|formatblock|forecolor|backcolor|createlink|insertimage)$/.test(cmd)) {
				return;
			}
			if (value) {
				// Moz/Op give bgcolor to block-level element selection is part of,
				// To get the IE/Safari effect we use hilitecolor.
				if ("backcolor" == cmd && (P.UA.opera || P.UA.gecko)) {
					cmd = "hilitecolor";
				}
				if (P.UA.ie) {
					if ("backcolor" == cmd || "forecolor" == cmd) {
						value = value.replace(/^#([0-9A-Z])([0-9A-Z])([0-9A-Z])$/gi, '#$1$1$2$2$3$3');
					}
					// IE doesn't understand inserthtml
					if ("inserthtml" == cmd) {
						var r = a.getRange(), n;
						if (r.pasteHTML) {
							r.pasteHTML(value);
						} else if (r.insertNode) {
							r.deleteContents();
							if (r.createContextualFragment) {
								// Might be in IE10?
								n = r.createContextualFragment(value);
							} else {
								// In IE 9 we need to use innerHTML of a temporary element
								var d = a.getDocument(), div = d.createElement("div"), child;
								div.innerHTML = value;
								n = d.createDocumentFragment();
								while ((child = div.firstChild)) {
									n.appendChild(child);
								}
							}
							r.insertNode(n);
						}
						return;
					}
				}
			}
			try {
				a.getDocument().execCommand(cmd, false, value);
			} catch (e) {
				console.error(e.message+": execCommand("+cmd+", false, "+value+")");
			}
			setNodePath(a.getDocument());
		}
	};

	/* create controls */
	function create(node)
	{
		var f;
		if ("textarea" == lc(node.nodeName)) {
			current = textarea = new PW_Area(node);

			// Temporary hide node to get correct css width, instead of pixels
			node.hide();

			f = P.$C("fieldset",{
				"class":"wysiwyg",
				style:{width:node.css("width") || Math.max(node.offsetWidth, 320)+"px"},
				wysiwyg:node.wysiwyg
			});
			f.btns = f.$A("div",{"class":"toolbars"});

			buttons = [];
			var tb, bar=0, i=0, btn, tbtn, fn, o;
			while (tbtn = PW.toolbarButtons[i++])
			{
				btn = null;
				if (tbtn.length >= 3) {
					fn = tbtn[5];
					if (PW.VALUE_FUNCTION == tbtn[2]
					 && !is_function(fn)
					 && !is_function(pw[fn])
					 && !is_function(window[fn])
					){
						console.warn("Failed to find wysiwyg button function: "+fn);
						continue;
					}

					if ("select" == tbtn[0]) {
						btn = P.$C("select",{
							command:tbtn[0],
							wysiwyg:f.wysiwyg,
							onchange: btnCommand,
							onmouseover: function(){statusbar.html(this.title);},
							onmouseout: function(){setNodePath(htmlarea.getDocument());}
						});
						if (tbtn[2]) btn.commandValue = tbtn[2];
						if (tbtn[3]) btn.title = _(tbtn[3]);
						if (tbtn[4]) btn.className = tbtn[4];
						btn.param1 = tbtn[5]||"";
						for (var si=0;si<tbtn[1].length;++si){
							btn.options[si] = new Option(tbtn[1][si].name,tbtn[1][si].value);
						}
					} else {
						btn = buttons[bar++] = createButton(f, tbtn);
						/* IE bugs */
						/* after getSelection() undo/redo don't work */
						if (P.UA.ie && ("undo" == tbtn[0] || "redo" == tbtn[0])) {
							btn.attr("disabled","");
						}
					}
				} else if ("div" == tbtn[0]) {
					btn = P.$C("div",{"class":"divider"});
				}
				if (!tb || !btn) {
					tb = f.btns.$A("div",{"class":"toolbar"});
				}
				if (btn) tb.$A(btn);
			}

			f.ctrl = f.$A("div",{
				"class":"iframes",
				style:{height:node.css("height") || Math.max(node.offsetHeight, 100)+"px"}
			});

			// Finally unhide the node
			node.show();

			if (defined(window.PoodleResizer)) {
				function wr_mouse(e) {
					if (current.getNode().$Doc) {
						var d = current.getDocument(), fn = ("mousedown" == e.type)?'bind':'unbind';
						d[fn]("mousemove mouseup", function(e){f.ctrl.resizer.mouse(e);});
					}
				}
				function wr_onresize(e, s) {
					if (e.currentTarget == current.getDocument()) { s.x = e.pageX; s.y = e.pageY; }
				}
				f.ctrl.resizer = new PoodleResizer(f, f, f.ctrl);
				with (f.ctrl.resizer) {
					onmousedown = wr_mouse;
					onmouseup = wr_mouse;
					onresize = wr_onresize;
				}
			}

			o = f.ctrl.$A("iframe",{"class":"html"});
			o.onDOMReady(function(){
				var w = this.$Win();
				if (!w.Poodle) w.Poodle = new PoodleDOM(w);
				// Due to Safari we must use timeout
				setTimeout(function(){
					pw.updateHTMLArea(1);
					activateDesignMode(w.$D());
				},1);
			});
			htmlarea = current = new PW_Area(o);

			node.replaceWith(f);
			node = node.placeBefore(o)
//				.on("DOMControlValueChanged",function(e){console.dir(e)})
				.attr("class",null)
				.attr("style",null);

			statusbar = f.$A("div",{"class":"statusbar"});

			node.wysiwyg.setConfig(node.data('wysiwyg'));

			// attach form submit event listener
			o = node.form;
			if (o) o.on("beforesubmit",function(){
				pw.updateTextArea();
				node.defaultValue = node.value;
			});

			window.on('beforeunload',function(e){
				pw.updateTextArea();
//				if (node.value != node.defaultValue) { e.stop(); }
			});
		}
		else
		{
			console.warning("contentEditable not supported yet");
		}
		container = f;
	}

	// create a button for the toolbar
	function createButton(f, tbtn)
	{
		var btn = f.$C("a",{
			command:tbtn[0],
			href:"#",
			textContent:_(tbtn[1]),
			wysiwyg:f.wysiwyg

			,onclick: btnCommand
			,onmouseover: function(){statusbar.html(this.title);}
			,onmouseout: function(){setNodePath(htmlarea.getDocument());}
		});
		if (tbtn[2]) btn.commandValue = tbtn[2];
		if (tbtn[3]) btn.title = _(tbtn[3]);
		if (tbtn[4]) btn.className = tbtn[4];
		btn.param1 = tbtn[5]||"";
		btn.param2 = _(tbtn[6]||"");
		return btn;
	}

	function reactivateDesignMode() { activateDesignMode(this); }
	function activateDesignMode(o)
	{
		var body = o;
		if (9 == o.nodeType) {
			body = o.$B();
		}
		try {
			o.on("mousemove", function(e){
				// Not ideal workaround for checkboxes in Firefox
				if ('checkbox' === o.activeElement.type) {
					setNodePath(o, o.activeElement);
				}
			 })
			 .on("mouseup", onNodePath)
			 .on("keyup"/* keydown*/, function(e){
				if (-1 === ["textarea","input"].indexOf(lc(e.target.nodeName))) {
					onNodePath.call(this,e);
				}
/*				if (37 == e.keyCode || 39 == e.keyCode) {
					// move cursor outside element?
				}*/
			 })
			 .on("dblclick", function(e){
				var n = statusbar.lastChild;
				if (n && n.relNode && 'img' == lc(n.relNode.nodeName)) {
					edit_attributes(statusbar.lastChild);
				}
			 });
//			 .off("mousedown", reactivateDesignMode);
			// support form elements, firefox select|checkbox|radio fail
			// https://bugzilla.mozilla.org/show_bug.cgi?id=416766
			body.on("click", function(e){
				setNodePath(o.$D(),e.target);
				e.stop();
			}).on("paste", function(e){
				// Cleanup pasted html
				var data = e.clipboardData.getData('text/html');
				pw.execCommand('inserthtml', data
					? data.replace(/ (class|style)=("[^"]*"|'[^']*')/gim, '')
					// Safari/Edge not supporting HTML
					: e.clipboardData.getData('Text')
				);
				e.stop();
			 });
			if (!body_observer) {
				body_observer = new MutationObserver(function(mutations) {
					if (!inserting) {
						mutations.forEach(function(mutation) {
							for (var i = 0; i < mutation.addedNodes.length; ++i) {
								var n = mutation.addedNodes[i];
								// Workaround Chrome span bug, this creates a new bug as it removes element/styling
								if (P.UA.webkit && "span" == n.lowerName() && !n.children.length && n.style.lineHeight) {
									n.firstChild.placeBefore(n);
									n.remove();
								}
							}
						});
					}
				});
				body_observer.observe(body, { childList: true });
			}
//			body_observer.disconnect();

			if (!defined(body.contentEditable)) {
				console.log("WYSIWYG designMode");
				body.$D().designMode = "on";
			}
//			execCommand('styleWithCSS', false);
/*			if (P.UA.opera)
			{
				o.on("mouseup keydown keyup", onNodePath);
			}*/
		} catch (e) {
			console.error(e);
//			o.on("mousedown", reactivateDesignMode);
		}
	}

	function write_iframe(area, css_class, css)
	{
		var a = defined(document.all),
		   ss = htmlspecialchars,
		  doc = area.getDocument(),
		    w = area.getWindow(),
		   id = textarea.getNode().id,
		 link = doc.$ ? doc.$("wcss") : null,
		html5 = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width">';
		if (link) {
			link.attr('href', css);
		} else {
			if (a) {
				html5 += '<link id="wcss" type="text/css" href="'+ss(css)+'" rel="stylesheet">';
			}
			html5 += '</head><body id="'+(id?ss(id):'content')+'" class="'+ss(css_class)+'" contenteditable="true"></body></html>';
			doc.open();
			doc.write(html5);
			doc.close();
			w.Poodle = new PoodleDOM(w);
			// Firefox issue
			if (!a) {
				var head = doc.$T("head")[0];
				head.$A("link",{id:"wcss",rel:"stylesheet",type:"text/css",href:css});
			}
		}
	}

	// execute a toolbar button command
	function btnCommand(e)
	{
		var o = this, value = null, cmd = o.command;
		e.stop();
		o.blur();
		if (!container.hasClass("sourcecode") || "toggleView" == o.param1 || "toggleSize" == o.param1) {
			switch (o.commandValue)
			{
			case PW.VALUE_PROMPT:
				value = prompt(_(o.param1), o.param2);
				break;
			case PW.VALUE_FUNCTION:
				try {
					if (is_function(o.param1)) {
						o.param1.call(o,e);
					} else if (is_function(pw[o.param1])) {
						pw[o.param1](e,o);
					} else if (is_function(window[o.param1])) {
						window[o.param1].call(o,e);
					}
				} catch (e) { console.error(e); }
				return;
			default:
				value = o.commandValue;
			}
			pw.execCommand(cmd, value);
		}
	}

	function save_attributes()
	{
		var n, x=0, pop=attribs_window, node=pop.f.relNode, k, v;
		if (pop) {
			while (n = pop.f.elements[x++]) {
				k = n.name;
				if (k && "attribute." == k.substr(0,10)) {
					k = k.substr(10);
					switch (n.type)
					{
					case "checkbox":
						n.checked ? node.setAttribute(k, n.value.trim()) : node.removeAttribute(k);
						break;
					default: // text, color, select-one
						v = n.value.trim();
						v.length ? node.setAttribute(k, v) : node.removeAttribute(k);
						break;
					}
				}
			}
			node.attr("style",pop.raw.value.replace(/\s+/g,' ').trim()||null);
			pop.hide();
		}
	}

	function get_raw_css(o)
	{
		var a = o.style.cssText.match(/([^;]+);\s*/g);
		if (a) a.sort();
		return a ? a.join("").replace(/;\s*/g,";\n") : "";
	}

	var ignore_update_css_example = false;
	function update_css_example()
	{
		if (!ignore_update_css_example) {
			var n, i=0, k, v,
			  pop=attribs_window,
			    t=pop.exampletxt;
			while (n = pop.f.elements[i++]) {
				k = n.name;
				if (k && "attribute."!=k.substr(0,10)) {
					v = n.value.trim();
					if (v.length && n.unit) { v += n.unit.value; }
					t.setCSSProperty(k,v);
				}
			}
			pop.raw.value = get_raw_css(t);
		}
	}

	function update_css_options(initial)
	{
		// P.$Q("input,select") fails in Firefox, missing elements
		ignore_update_css_example = 1;
		var i=0, n, v, s, pop=attribs_window, props=pop.s.$T("input"), t=pop.exampletxt;
		for (; i<props.length; ++i) {
			n = props[i];
			v = t.getCSSPropValue(n.name);
			if ("number" === n.type) {
				n.unit.setSelectedByValue(v ? v.replace(/[0-9]+/,'') : null, initial);
				if (v) {
					v = intval(v);
				}
			}
			if (initial) {
				n.attr("value", v);
				n.defaultValue = v.length ? n.value : v;
			}
			n.on("change blur", function(){update_css_example();})
			 .value = v;
		}
		props=pop.s.$T("select");
		for (i=0; i<props.length; ++i) {
			n = props[i];
			if (n.name) {
				v = t.getCSSPropValue(n.name);
				n.setSelectedByValue(v, initial);
			}
			n.on("change",function(){update_css_example();});
		}
		ignore_update_css_example = 0;
	}

	function edit_attributes(node)
	{
		if (!node || !node.relNode || !defined(node.relNode.nodeName)) { return; }

		var i, t, k, n, o, v, ul, li, w=PW, w_id="wysiwyg_attribs_window", pop = attribs_window, f,
			EA=ELEMENT_ATTRIBUTES;
		if (!pop) {
			pop = attribs_window = document.$B().$A([
			"div",{id:w_id, "class":"windowbg", hidden:""},
				["div",{"class":"window"},
					["div", {"class":"header"},
						["span",{ref:"t"}],
						["a", {"class":"close", innerHTML:"x", title:_("Close"), onclick:function(){
							P.$(w_id).hide();
							pop.f.relNode.$B().focus();
							pop.f.relNode.focus();
						}}]
					],
					["form",{ref:"f"},
						["div", {"class":"body",style:{height:"auto"}},
							["ul", {"class":"tabs", ref:"tabs"},
								["li", {"class":"current"},
									["a", {innerHTML:_("Attributes")}],
									["div", {"class":"attributes", ref:"a"}]
								],
								["li", {},
									["a", {innerHTML:_("Style")}],
									["div", {"class":"style", ref:"s"},
										["div",{id:"element-css-properties"},
											["label",null,
												["span",null,_("display")],
												["select",{name:"display",style:{width:"auto"}},
													["option",{value:""}], // inherit?
													["option",{value:"none"},"none"],
													["option",{value:"box"},"box"],
													["option",{value:"block"},"block"],
													["option",{value:"flex"},"flex"],
													["option",{value:"inline"},"inline"],
													["option",{value:"inline-block"},"inline-block"],
													["option",{value:"inline-flex"},"inline-flex"],
													["option",{value:"inline-table"},"inline-table"],
													["option",{value:"list-item"},"list-item"],
													["option",{value:"table"},"table"],
													["option",{value:"table-caption"},"table-caption"],
													["option",{value:"table-cell"},"table-cell"],
													["option",{value:"table-column"},"table-column"],
													["option",{value:"table-column-group"},"table-column-group"],
													["option",{value:"table-footer-group"},"table-footer-group"],
													["option",{value:"table-header-group"},"table-header-group"],
													["option",{value:"table-row"},"table-row"],
													["option",{value:"table-row-group"},"table-row-group"]
												]
											],
											["br"],
											["label",null,
												["span",null,_("float")],
												["select",{name:"float"},
													["option",{value:""}],
													["option",{value:"none"},_("none")],
													["option",{value:"left"},_("left")],
													["option",{value:"right"},_("right")]
												]
											],
											["br"],
											["label",null,
												["span",null,_("color")],
												["input",{name:"color","class":"color"}]
											],
											["br"],
											["label",null,
												["span",null,_("height")],
												["input",{name:"height",type:"number",min:0}]
											],
											["br"],
											["label",null,
												["span",null,_("width")],
												["input",{name:"width",type:"number",min:0}]
											],
											["br"],
											["fieldset",{"class":"background"},
												["legend",null,_("background")],
												["label",null,
													["span",null,_("color")],
													["input",{name:"background-color","class":"color"}]
												],
												["br"],
												["label",null,
													["span",null,_("image")],
													["input",{name:"background-image",type:"text"}]
												]
										/*
												background-repeat      repeat / repeat-x / repeat-y / no-repeat/ inherit
												background-attachment  scroll / fixed / inherit
												background-position
										*/
											],
											["fieldset",{"class":"border"},
												["legend",null,_("border")],
												["label",null,
													["span",null,_("top")],
													["input",{name:"border-top-width",type:"number",min:0}],
													["select",{name:"border-top-style"},PW.CSS_BORDER_STYLES],
													["input",{name:"border-top-color","class":"color"}]
												],
												["br"],
												["label",null,
													["span",null,_("right")],
													["input",{name:"border-right-width",type:"number",min:0}],
											//		["input",{name:"border-right-style",list:"css-border-styles"}],
													["select",{name:"border-right-style"},PW.CSS_BORDER_STYLES],
													["input",{name:"border-right-color","class":"color"}]
												],
												["br"],
												["label",null,
													["span",null,_("bottom")],
													["input",{name:"border-bottom-width",type:"number",min:0}],
													["select",{name:"border-bottom-style"},PW.CSS_BORDER_STYLES],
													["input",{name:"border-bottom-color","class":"color"}]
												],
												["br"],
												["label",null,
													["span",null,_("left")],
													["input",{name:"border-left-width",type:"number",min:0}],
													["select",{name:"border-left-style"},PW.CSS_BORDER_STYLES],
													["input",{name:"border-left-color","class":"color"}]
												],
												["br"],
												["label",null,
													["span",null,_("outline")],
													["input",{name:"outline-width",type:"number"}],
													["select",{name:"outline-style"},PW.CSS_BORDER_STYLES],
													["input",{name:"outline-color","class":"color"}]
												],
											],
											["fieldset",{"class":"margin"},
												["legend",null,_("margin")],
												["label",null,
													["span",null,_("top")],
													["input",{name:"margin-top",type:"number"}]
												],
												["br"],
												["label",null,
													["span",null,_("right")],
													["input",{name:"margin-right",type:"number"}]
												],
												["br"],
												["label",null,
													["span",null,_("bottom")],
													["input",{name:"margin-bottom",type:"number"}]
												],
												["br"],
												["label",null,
													["span",null,_("left")],
													["input",{name:"margin-left",type:"number"}]
												]
											],
											["fieldset",{"class":"padding"},
												["legend",null,_("padding")],
												["label",null,
													["span",null,_("top")],
													["input",{name:"padding-top",type:"number"}]
												],
												["br"],
												["label",null,
													["span",null,_("right")],
													["input",{name:"padding-right",type:"number"}]
												],
												["br"],
												["label",null,
													["span",null,_("bottom")],
													["input",{name:"padding-bottom",type:"number"}]
												],
												["br"],
												["label",null,
													["span",null,_("left")],
													["input",{name:"padding-left",type:"number"}]
												]
											]
										//	,["datalist",{id:"css-border-styles"},PW.CSS_BORDER_STYLES]
										]
									]
								],
								["li", {},
									["a", {innerHTML:_("Style RAW")}],
									["div", {"class":"raw"},
										["textarea", {ref:"raw", style:{height:"99%",width:"100%"}}]
									]
								],
								["li", {},
									["a", {innerHTML:"TAL"}],
									["div", {"class":"tal", ref:"tal"}]
								]
							],
							["button", {type:"button", onclick:function(){save_attributes();}}, _("Apply")],
							["button", {type:"reset"}, _("Reset")],
							["div", {ref:"example","class":"example"},
								["span", {ref:"exampletxt"}, "Example text"]
							]
				]	]	]
			]);
			n = pop.s.$Q("input[type=number]");
			for (i=0; i<n.length; ++i) {
				o = document.$C(["select",{},PW.CSS_UNITS]);
				n[i].unit = o;
				n[i].after(o);
			}
		}
		Poodle_Tabs(pop.tabs);
		pop.tabs.show_tab(0);

		// Set header titel
		t = "";
		o = node;
		while (o) { t = "/"+o.txt()+t; o = o.prevElement(); }
		pop.t.html(t);

		// Generate html attributes
		n = node.relNode;
		t = lc(n.nodeName);
		pop.a.html("");
		pop.f.relNode = n;
		var l, al = EA[t]?EA[t].length:0;
		if (al) {
			pop.a.$A("h3", {innerHTML:"&lt;"+t.toUpperCase()+"&gt;:"});
			for (i=0; i<al; ++i) {
				k = EA[t][i];
				l = pop.a.$A("label");
				l.$A("span",{innerHTML:k});
				var ii = 0, s, av = n.getAttribute(k)||n[k], opts;
				if ("lang" == k || "hreflang" == k) {
					opts=w.LANG_OPTIONS;
					s = l.$A("select", {name:"attribute."+k});
					s.options[0] = new Option("","");
					for (;ii<opts.length;++ii){
						s.options[1+ii] = new Option(opts[ii][1],v=opts[ii][0], v == av, v == av);
					}
				} else
				if (("input" == t||"button" == t) && "type" == k) {
					s = l.$A("select", {name:"attribute."+k});
					av = av||("button" == t?"submit":"text");
					opts = "button" == t ? ['button','reset','submit'] : ['button','checkbox','color','date','datetime','datetime-local','email','file','image','month','number','password','radio','range','reset','search','submit','tel','text','time','url','week'];
					for (;ii<opts.length;++ii){
						s.options[ii] = new Option(v=opts[ii], v, v == av, v == av);
					}
				} else
				if ("form" == t && ("method" == k||"enctype" == k)) {
					s = l.$A("select", {name:"attribute."+k});
					opts = "method" == k ? ['get','post'] : ["application/x-www-form-urlencoded",'multipart/form-data','text/plain'];
					for (;ii<opts.length;++ii){
						s.options[ii] = new Option(v=opts[ii], v, v == av, v == av);
					}
				} else {
					var ia = {name:"attribute."+k, value:av, type:"text"};
					if ("a" == t && "href" == k) {
						ia.list = "attribute.href.list";
					} else
					if ("maxlength" == k) {
						ia.type = "number";
						ia.min = 0;
					} else
					if ("allowfullscreen" == k||"disabled" == k||"open" == k) {
						ia.type = "checkbox";
						ia.value = "";
						ia.checked = n.hasAttribute(k) ? "" : null;
					}
					l.$A("input", ia);
				}
				if ("rel" == k) {
					// http://www.w3schools.com/TAGS/att_a_rel.asp
					l.$A("em", {"class":"infobox"})
					.$A("span", {innerHTML:"Multiple options allowed (seperated by a space)<dl>"
						+"<dt>alternate</dt><dd>An alternate version of the document (i.e. print page, translated or mirror)</dd>"
						+"<dt>external</dt><dd>Link to an external website to open in a new window</dd>"
						+"<dt>nofollow</dt><dd>Used by Google, to specify that the Google search spider should not follow that link</dd>"
						+"</dl>"});
				}
				pop.a.$A("br");
			}
			pop.a.$A("h3", {"class":"clear",innerHTML:"default:"});
		}
		for (i=0; i<DOM_DEF_ATTRIBUTES.length; ++i) {
			k = DOM_DEF_ATTRIBUTES[i];
			l = pop.a.$A("label");
			l.$A("span",{innerHTML:k});
			l.$A("input", {name:"attribute."+k, value:n.attr(k), type:"text"});
			pop.a.$A("br");
		}
/*
		if ('table' == t) {
			l = pop.a.$A("label");
			l.$A("span",{innerHTML:'Headers'});
			l.$A("input", {name:"table.thead", value:0, type:"number"});
			pop.a.$A("br");
		}
*/

		// Generate TAL attributes
		pop.tal.html("");
		for (i=0; i<TAL_ATTRIBUTES.length; ++i) {
			k = TAL_ATTRIBUTES[i];
			l = pop.tal.$A("label");
			l.$A("span",{innerHTML:k});
			l.$A("input", {name:"attribute."+k, value:n.getAttribute(k), type:"text"});
			pop.tal.$A("br");
		}

		// Convert attributes to CSS
		if (n.attr('width')) {
			n.setCSSProperty('width', n.getWidth()+'px').attr('width', null);
		}
		if (n.attr('height')) {
			n.setCSSProperty('height', n.getHeight()+'px').attr('height', null);
		}

		// Set current CSS style on exampletxt and raw editor
		pop.exampletxt.remove();
		pop.exampletxt = o = pop.example.$A("img" == t ? n.cloneNode() : "span");
		t = n.txt();
		t = get_raw_css(o
			.txt((20>t.length ? t : t.substr(0,20)+"…"))
			.attr("style",n.attr("style")));
		pop.raw.txt(t).value = t;
		// Get and set current surrounding background color of current node
		while (n = n.parent()) {
			if (n.css) {
				t = n.css("background-color",1);
				if ("transparent"!=t && "rgba(0, 0, 0, 0)"!=t) break;
			}
		}
		pop.example.setCSSProperty("background-color",t);

		// Sync the CSS form fields
		update_css_options(1);

		P.HTML5Form(pop.f);
		pop.f.on("afterreset",function(){update_css_example();});
		pop.raw.on("keyup",function(e){
				o.attr("style",pop.raw.value);
			})
			.on("blur",function(){
				update_css_options();
			});

		pop.f.reset();
		pop.show();
	}

	function setNodePath(doc, node, store)
	{
		var n = node||getNode(doc, store), tag, uri,
		    i = 0, btn, btns = buttons, css, cn,
		 sbar = statusbar, scn = sbar.childNodes;
		sbar.html("");
		while (btn = btns[i++]) { btn.removeClass("active"); }
		if (n) {
			if (n.originalElement) {
				n = n.originalElement;
				n.focus();
			}

			tag = lc(n.nodeName);
			while (n && n != doc && 'body' != tag) {
				if (!n.hasClass('wysiwyg-fake-element')) {
					/** dis/enable buttons */
					i=0; cn = tag; css = n.style.cssText;
					if (css) {
						if (css.indexOf('bold')>0) { cn += "|strong"; }
						if (css.indexOf('italic')>0) { cn += "|em"; }
					}
					while (btn = btns[i++]) {
						if (P.strContains(btn.className, cn)) { btn.addClass("active"); }
					}
					if ("a" == tag) {
						if ((uri = n.getAttribute("href")||n.href)) {
							tag += "[@href="+htmlspecialchars(uri)+"]";
						}
					}
					if ("img" == tag) {
						if ((uri = n.getAttribute("src")||n.src)) {
							tag += "[@src="+htmlspecialchars(uri)+"]";
						}
					}
					/** push statusbar path */
					node = P.$C("a",{
						relNode:n,
						innerHTML:tag,
						onclick:function(e){edit_attributes(e.target);}
					});
					sbar.insertBefore(node, scn.length?sbar.childNodes[0]:null);
					sbar.insertBefore(document.createTextNode(' ‣ '), sbar.childNodes[0]);
				}
				/** next */
				n = n.parentNode;
				tag = lc(n.nodeName);
			}
		}
	}

	function onNodePath(e)
	{
		var doc = this.$D(), n;
		if ("mouseup" == e.type && 0 == e.button) {
			n = getNode(doc);
			if (n && n.nodeName && 'a' == lc(n.nodeName) && n.hasClass('tab')) {
				var ul = n.parentNode.parentNode, items = ul.childNodes;
				for (i=0; i<items.length; ++i) { items[i].removeClass("current"); }
				if (n.parentNode.hasClass('new')) {
					var li = P.$C("li",{"class":"current"});
					li.$A("a",{"class":"tab",innerHTML:_("new")});
					li.$A("div",{"class":"tabcontent",innerHTML:_("content")});
					n = ul.insertBefore(li, n.parentNode).firstChild;
					try {
						var win = doc.$W(), s = win.getSelection();
						var range = s.getRangeAt ? s.getRangeAt(0) : /*IE*/ doc.body.createTextRange();
						range.selectNodeContents ? range.selectNodeContents(n) : /*IE*/ range.moveToElementText(n);
					} catch (e) {}
				} else {
					n.parentNode.className += " current";
				}
			}
		}
		setNodePath(doc, n, true);
	}

	this.popupDiv = function(btn, n, cmd)
	{
		var v;
		if (n.btn) { n.btn.removeClass("active"); }
		if (!n.hasAttribute("hidden") && n.command == cmd) { v = "hidden"; }
		n.btn = btn;
		n.command = cmd;
		if ("hidden" == v) {
			n.hide();
			btn.addClass("active");
		} else {
			v = btn.getBoundingPageRect();
			with (n.style) {
				left = v.left+"px";
				top  = v.bottom+"px";
			}
			n.show();
		}
	};

	/**
	 * Button actions
	 */

	this.chooseColor = function(e,btn)
	{
		var o, i, pop = colors_window, clrs=PW.COLORS;
		if (!pop) {
			pop = colors_window = document.$B().$A([
			"div",{id:"wysiwyg-colorpalette",hidden:""
				,onclick:function(e){
					var n=this, b=e.target;
					if (b!=n) {
						n.hide().btn.removeClass("active");
						if (b.value) pw.execCommand(n.command, b.value);
					}
				}
			}]);
			for (i = 0; i < clrs.length; ++i) {
				o = pop.$A("div");
				if ("" == clrs[i]) { o.innerHTML = "X"; }
				else {
					o.style.backgroundColor = o.value = "#"+clrs[i];
//					o.onmouseover = function(){ this.style.borderColor = this.style.backgroundColor; };
//					o.onmouseout  = function(){ this.style.cssText = "background-color:"+this.style.backgroundColor; };
				}
			}
		}
		this.popupDiv(btn, pop, btn.command);
	};

	this.createLink = function(e,btn)
	{
		function getLastA()
		{
			var n = statusbar.lastChild;
			while (n && n.relNode && "a" != lc(n.relNode.nodeName)) { n = n.prevElement(); }
			return n;
		}

		if (btn.hasClass("active")) {
			pw.execCommand("unlink", null);
		} else {
			pw.execCommand("createlink", "/");
			var n = getLastA();
			if (!n) {
				// When there's a <br/> involved, Firefox doesn't
				// have the selection range being <a></a> only
				var r = getRange(current.getWindow());
				n = r.startContainer;
				while (n && "a"!=lc(n.nodeName)) n=n.nextSibling;
				if (n) {
					r.selectNode(n);
					setNodePath(current.getDocument(), n);
					n = getLastA();
				}
			}
			this.editAttributes(n);
		}
	};

	// resize wysiwyg
	this.toggleSize = function(e,btn)
	{
		var w = container,
		    f = w.ctrl,
		    b = w.$B(),
		    r = current.getRange();
		w.toggleClass("maximized");
		if (w.hasClass("maximized")) {
			btn.maximizeTitle = btn.txt();
			btn.txt(btn.param2);
			w.oldCSS = w.style.cssText;
			f.oldCSS = f.style.cssText;
			b.addClass("wysiwyg-maximized");
			f.style.cssText = "";
			w.style.cssText = "";
		} else {
			btn.txt(btn.maximizeTitle);
			f.style.cssText = f.oldCSS;
			w.style.cssText = w.oldCSS;
			b.removeClass("wysiwyg-maximized");
		}
		current.setRange(r);

		reactivateHTMLArea();
		current.focus();
	};

	// switch between text and html area
	this.toggleView = function(e,btn)
	{
		inserting = true;
		var sc="sourcecode", w=container, r=current.getRange();
		w.toggleClass(sc);
		current.focus(); // circumvent firefox issue
		if (w.hasClass(sc)) {
			this.updateTextArea();
			(current = textarea).setRange(r);
		} else {
			this.updateHTMLArea();
			(current = htmlarea).setRange(r);
		}
		current.focus();
		if (body_observer) {
			body_observer.takeRecords();
		}
		inserting = false;
	};

	node.wysiwyg = pw;
	create(node);
}

PW.init = function()
{
	var i=-1, n, nodes = P.$Q(".wysiwyg");
	while (n = nodes[++i]) {
		if (!n.wysiwyg) {
			n.wysiwyg = new PW(n);
		}
	}
};

PW.VALUE_PROMPT = 1;
PW.VALUE_FUNCTION = 2;

// define toolbar buttons
PW.toolbarButtons = new Array(
	// command, display name, value, title/description, class, prompt/function, param2
	[null, "Maximize", PW.VALUE_FUNCTION, "Switch size", "Maximize", "toggleSize", "Normalize"]
	,[null, "Source", PW.VALUE_FUNCTION, "Switch views", "SourceCode", "toggleView", "Compose"]
	,["bold", "Strong", null, "Give text strength", "strong b"] // <strong>
	,["italic", "Emphasis", null, "Give text emphasis", "em i"] // <em>
	,["div"] // place a toolbar divider
	,["JustifyLeft", "Align left", null, "Align left", "JustifyLeft"]
	,["JustifyCenter", "Align center", null, "Align center", "JustifyCenter"]
	,["JustifyRight", "Align right", null, "Align right", "JustifyRight"]
	,["JustifyFull", "Justify", null, "Justify", "JustifyFull"]
	,["div"]
	,["formatblock", "Heading 1", "<H1>", "Make top level heading", "h1"]
	,["formatblock", "Heading 2", "<H2>", "Make 2nd level heading", "h2"]
	,["formatblock", "Heading 3", "<H3>", "Make 3rd level heading", "h3"]
	,["formatblock", "Paragraph", "<P>", "Make a paragraph", "p"]
	,["formatblock", "Monospace", "<PRE>", "Make paragraph monospaced text", "pre"]
	,["div"]
	,["subscript", "Subscript", null, "Set as subscript", "sub"]
	,["superscript", "Superscript", null, "Set as superscript", "sup"]
	,["div"]
	,["undo", "Undo", null, "Undo", "Undo"]
	,["redo", "Redo", null, "Redo", "Redo"]
	,["div"]
//	,[null]
	,["insertunorderedlist", "List", null, "Make an unordered list", "ul"]
	,["insertorderedlist", "Ordered List", null, "Make an ordered list", "ol"]
	,["div"]
	,["createlink", "Link", PW.VALUE_FUNCTION, "Create a hyperlink", "a", "createLink"]
	,["insertimage", "Image", PW.VALUE_PROMPT, "Insert external image", "img", "Enter the URL of the image:", "http://"] // <img>
	,["div"]
	,["forecolor", "Color", PW.VALUE_FUNCTION, "Text color", "Color", "chooseColor"]
	,["backcolor", "Background color", PW.VALUE_FUNCTION, "Background color", "BackColor", "chooseColor"]
);

function setTagCSS(t,v)
{
	return 0<t.indexOf('style=') ? t.replace('style="','style="'+v) : t.replace('>',' style="'+v+'">');
}

// map control elements to desired elements
PW.elementMap = new Array(
	// control regex, desired regex replacement
	[/(<[\/A-Z0-9]+)/gim, function(d,tag){return tag.toLowerCase();}],
	[/<head>.*?<\/head>/gm, ''],
	[/<title>.*?<\/title>/gi, ''],
	[/<\/?(html|head|title|body|link|meta)( [^>]*)?>/gm, ''],
	[/\s+(xmlns="|class="apple|_moz_dirty=")[^"]*"/gi, ''], // WebKit/Gecko
	[/="[^"]*[<>][^"]*"/gim, function(d){return d.replace('<','&lt;').replace('>','&gt;');}],
	// MS Word
	[/<!--\[((?:(?!-->).)*)\]-->/g, ''],
	[/<!--{[^>}]+}>[\s\S]+?<!{[^>}]+}-->/g, ''],
	[/<\/?(xml|[^\s"'>]+:)[^>]*>/g, ''],
	[/mso-[^;]+;/g, ''],
	[/\s+class="Mso[a-z]+"/gi, ''],
	// Obsolete html
	[/(<\/?)font/g, '$1span'], // IE uses this
	// IE
	[/<([^>]+)>/gm, function(d,tag){return '<'+tag.replace(/(\s[a-z-]+)\s*=\s*([^"'\s>]+)/gmi, '$1="$2"')+'>';}], // fix attributes
	[/"[\t ]*([^"=\r\n><]+)[\t ]*"/gm, '"$1"'],
	[/(<[^>]+)\s+(background-color)="(#[A-F0-9]+)"([^>]*>)/gim, function(d,tag,prop,value,tag_e){
		return setTagCSS(tag+tag_e, prop+":"+value+";");
	}],
	// Attributes to css
	[/(<[^>]+)\s+color="(#[A-F0-9]+)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "color:"+value+";");
	}],
	[/(<[^>]+)\s+bgcolor="(#[A-F0-9]+)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "background-color:"+value+";");
	}],
	[/(<[^>]+)\s+valign="(top|middle|bottom|baseline)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "vertical-align:"+value+";");
	}],
	[/(<[^>]+)\s+width="([0-9]+)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "width:"+value+"px;");
	}],
	[/(<[^>]+)\s+height="([0-9]+)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "height:"+value+"px;");
	}],
	[/(<[^>]+)\s+border="([0-9]+)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "border-width:"+value+"px;");
	}],
	[/(<[^>]+)\s+bordercolor="(#[A-F0-9]+)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "border-color:"+value+";");
	}],
	[/(<[^>]+)\s+cellspacing="([0-9]+)"([^>]*>)/gim, function(d,tag,value,tag_e){
		return setTagCSS(tag+tag_e, "border-spacing:"+value+"px;");
	}],
	// HTML 5 compatibility
	[/(<\/?)acronym/gm, '$1abbr'],
	// Gecko/WebKit styleWithCSS=false
	[/style="\s*"/g, ''],
	// self-contained elements to X(HT)ML
	[self_closing_tags, '<$1$2/>'],
	[/[\s+\/]+>/g, '/>'],
	[/\r/g, ''],
	// Init tabs - new tab
	[/<li class="new">.*<\/li>/gi,''],
	// strip FakeElements
	[/<span[^>]+class="wysiwyg-fake-element-overlay"[^>]*>[^<]*<\/span>/g,''],
	[/<span[^>]+class="wysiwyg-fake-element"[^>]*>(.*)?<\/span>/g,'$1'],
	// Google docs
	[/(<[^>]+) id="docs-internal-[^">]+"/g,'$1'],
	[/(<[^>]+) dir="ltr"/g,'$1']
);

PW.LANG_OPTIONS = [["en","English"],["nl","Dutch"]];

PW.DOMDefaultAttributes = [];
PW.DOMElements = {};

P.onDOMReady(function(){PW.init();});
PoodleWYSIWYG = PW;

PW.COLORS = new Array(
	"FCC","FFC","CFC","CFF","CCF","FCF","",
	"F88","FF8","8F8","8FF","88F","F8F","FFF",
	"F44","FF4","4F4","4FF","44F","F4F","CCC",
	"F00","FF0","0F0","0FF","00F","F0F","999",
	"C00","CC0","0C0","0CC","00C","C0C","666",
	"800","880","080","088","008","808","333",
	"400","440","040","044","004","404","000"
);

//	'NO_END_TAG' => array('area','br','col','hr','img','input','param')
// http://www.w3schools.com/css/css_reference_atoz.asp
PW.CSS_BORDER_STYLES = [
	["option"],
	["option",{value:"none"},"none"],
	["option",{value:"hidden"},"hidden"],
	["option",{value:"dotted"},"dotted"],
	["option",{value:"dashed"},"dashed"],
	["option",{value:"solid"},"solid"],
	["option",{value:"double"},"double"],
	["option",{value:"groove"},"groove"],
	["option",{value:"ridge"},"ridge"],
	["option",{value:"inset"},"inset"],
	["option",{value:"outset"},"outset"]
];

PW.CSS_UNITS = [
	["option",{},"px"],
	["option",{},"em"],
	["option",{},"rem"],
	["option",{},"vw"],
	["option",{},"vh"],
	["option",{},"vmin"],
	["option",{},"vmax"],
	["option",{},"ch"],
	["option",{},"cm"],
	["option",{},"ex"],
	["option",{},"in"],
	["option",{},"mm"],
	["option",{},"pc"],
	["option",{},"pt"],
	["option",{},"%"]
];

})();
