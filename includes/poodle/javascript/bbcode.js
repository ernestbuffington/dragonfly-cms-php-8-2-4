/**
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	@import "poodle"
*/

Poodle_BBCode = function()
{
	if (arguments[0]) {
		initControl(arguments[0]);
	} else {
		init();
	}

	// make tab content divs resizable
	function init()
	{
		var n, i=-1, nodes = document.$Q('textarea.bbcode');
		while (n=nodes[++i]) { initControl(n); }
	}

	function initControl(node)
	{
		if (!defined(node.bbcode)) {
			var b = node.bbcode = Poodle.$C('div',{'class':'bbcode',textarea:node}), div, i, j, t=Poodle_BBCode.toolbars, URL, fn, c, a;

			b.help = function(btn) {
				var code = btn.className.replace(/^.*bbcode-([^\s]+).*$/,'$1');
				this.helpDiv.txt(Poodle_BBCode.help[code]);
			};

			b.code = function(btn)
			{
				var code = btn.className.replace(/^.*bbcode-([^\s]+).*$/,'$1'),
				   ToAdd = "["+code+"]", force=false;
				switch (code)
				{
				case 'ltr':
				case 'rtl':
					this.textarea.dir = code;
					return;

				case 'hr':
					this.write('', "[hr]", true);
					return;

				case 'url':
					URL = prompt("Enter the URL", "http://");
					if (URL !== null) {
						if (!URL) {
							alert("Error: You didn't write the URL");
							return;
						}
						if (!this.write("[url="+URL+"]", "[/url]")) {
							var TITLE = prompt("Enter the page name", "Web Page Name");
							this.write('', "[url"+(TITLE ? "="+URL+"]"+TITLE : "]"+URL)+"[/url]", true);
						}
					}
					return;

				case 'email':
				case 'img':
				case 'video':
					if (code == 'email') { URL = prompt("Enter the Email Address",""); }
					else { URL = prompt("Please enter "+code+" URL","http://"); }
					if (URL !== null) {
						if (!URL) {
							alert("Error : You didn't write the address");
							return;
						}
						this.write('', "["+code+"]"+URL+"[/"+code+"]", true);
					}
					return;

				case 'emoticon':
					if (!btn.icons) {
						btn.icons = btn.parent().$A('div',{'class':'bbcode-emoticons hidden',onclick:function(e){
							if ('i' === e.target.lowerName()) {
								b.write('', e.target.code, true);
								btn.icons.toggleClass('hidden');
							} else if ('span' === e.target.lowerName()) {
								b.write('', ' :'+e.target.code+': ', true);
								btn.icons.toggleClass('hidden');
							} else if ('img' === e.target.lowerName()) {
								b.write('', ' '+e.target.code+' ', true);
								btn.icons.toggleClass('hidden');
							}
						}});
						var i = 0, icons = Poodle_BBCode.emoji, l=icons.length;
						if (l) {
							for (; i<l; ++i) {
								btn.icons.$A('span',{
									title:':'+icons[i][1]+':',
									'class':'emoji emoji-'+icons[i][0],
									code:icons[i][1]
								});
							}
						} else if (Poodle.Emoji) {
							icons = Poodle.Emoji.emojis;
							for (i in icons) {
								btn.icons.$A('i',{
									title:icons[i],
									'class':'emoji emoji-' + (((i.charCodeAt(0) & 0x3FF) << 10) + (i.charCodeAt(1) & 0x3FF) + 0x10000).toString(16).toUpperCase(),
									code:i
								});
							}
						}
						for (i=0,icons=Poodle_BBCode.emoticons; i<icons.length; ++i) {
							btn.icons.$A('img',{
								src:Poodle.ROOT + 'images/smiles/'+icons[i].file,
								title:icons[i].title,
								code:icons[i].code
							});
						}
					}
					btn.icons.style.left = btn.offsetLeft+"px";
					btn.icons.toggleClass('hidden');
					return;

				case 'code':
					if (!btn.hasClass('active')) {
						if (!btn.codes) {
							btn.codes = btn.parent().$A('div',{'class':'bbcode-codes hidden',onclick:function(e){
								if ('i' === e.target.lowerName()) {
									ToAdd = '[code='+e.target.code+']';
									if (!b.write(ToAdd, '[/code]', false)) {
										btn.addClass('active');
										b.write('', ToAdd, true);
									}
//									b.write('[code='+e.target.code+']', '[/code]', false);
									btn.codes.toggleClass('hidden');
								}
							}});
							btn.codes.$A('i',{title:'css','class':'bbcode-css',code:'css'});
							btn.codes.$A('i',{title:'html','class':'bbcode-html',code:'html'});
							btn.codes.$A('i',{title:'javascript','class':'bbcode-js',code:'js'});
							btn.codes.$A('i',{title:'php','class':'bbcode-php',code:'php'});
							btn.codes.$A('i',{title:'sql','class':'bbcode-sql',code:'sql'});
							btn.codes.$A('i',{title:'xml','class':'bbcode-xml',code:'xml'});
						}
						btn.codes.style.left = btn.offsetLeft+"px";
						btn.codes.toggleClass('hidden');
						return;
					}
					break;

				case 'color':
				case 'size':
					if (!btn.value) { this.textarea.focus(); return; }
					force = true;
					ToAdd = "["+code+"="+btn.value+"]";
					break;

				case 'justify':
				case 'left':
				case 'center':
				case 'right':
					ToAdd = "[align="+code+"]";
					code  = 'align';
					break;

				default:
					break;
				}
				if (!this.write(ToAdd, "[/"+code+"]", force)) {
					if (!btn.hasClass('active')) {
						btn.addClass('active');
					} else {
						ToAdd = "[/"+code+"]";
						btn.removeClass('active');
					}
					this.write('', ToAdd, true);
				}
			};

			b.write = function(start, end, force)
			{
				var n = this.textarea;
				if (n.caretPos) {
					n.focus();
					// Attempt to create a text range (IE).
					var theSelection = document.selection.createRange().text;
					if (force || theSelection) {
						document.selection.createRange().text = start + theSelection + end;
						n.focus();
						return true;
					}
				} else if (typeof(n.selectionStart) != "undefined") {
					// Mozilla text range replace.
					var text = new Array();
					text[0] = n.value.substr(0, n.selectionStart);
					text[1] = n.value.substr(n.selectionStart, n.selectionEnd-n.selectionStart);
					text[2] = n.value.substr(n.selectionEnd);
					var caretPos = n.selectionEnd+start.length+end.length;
					if (force || text[1]) {
						n.value = text[0]+start+text[1]+end+text[2];
						if (n.setSelectionRange) {
							n.focus();
							n.setSelectionRange(caretPos, caretPos);
						}
						return true;
					}
				} else if (force) {
					// Just put it on the end.
					n.value += start+end;
					n.focus(textarea.value.length-1);
					return true;
				}
				return false;
			};

			for (i=0; i<t.length; ++i)
			{
				div = b.$A('div');
				for (j=0; j<t[i].length; ++j)
				{
					if (t[i][j]) {
						c = t[i][j][0];
						a = t[i][j][1];
						fn = is_function(t[i][j][2]) ? t[i][j][2] : function(){b.code(this);};
						if ('emoticon' === c && !(Poodle.Emoji || Poodle_BBCode.emoticons.length || Poodle_BBCode.emoji.length)) {
							continue;
						}
						if (is_array(a)) {
							var x=0, s=div.$A('select',{
								'class':'bbcode-'+c,
								onchange:fn,
								onmouseover:function(){b.help(this);}
							});
							for (;x<a.length;++x) {
								s.$A("option",{textContent:a[x]});
							}
						} else {
							div.$A('button',{
								'class':'bbcode-'+c,
								type:'button',
								textarea:node,
								onclick:fn,
								onmouseover:function(){b.help(this);}
							});
						}
					}
				}
			}
			b.helpDiv = b.$A('div',{textContent:'Tip: Styles can be applied quickly to selected text'});
			b.placeBefore(node);
			b.$A(node);

			node.on('change keyup click select', function() {
				if (this.createTextRange) this.caretPos = document.selection.createRange().duplicate();
			});
		}
	}
};

Poodle_BBCode.help = {
	b: "Bold: [b]bold[/b]",
	i: "Italic: [i]italic[/i]",
	quote: "Quote: [quote]Quoted Text[/quote]",
	code: "Code: [code(=css|html|js|php|sql|xml|ini|diff)]Code[/code]",
	img: "Image: [img]http://image path[/img]",
	color: "Font Color: [color=red]text[/color] You can use HTML color=#FF0000",
	size: "Font Size: [size=9]Very Small[/size]",
	rtl: "Right to Left: Make message box align from Right to Left",
	ltr: "Left to Right: Make message box align from Left to Right",
	email: "Email: [email]Email Here[/email]",
	url: "URL: [url=Page URL]Page name[/url]",
	right: "Right: [align=right]Right Aligned Text[/align]",
	left: "Left: [align=left]Left Aligned Text[/align]",
	center: "Center: [align=center]Center Aligned Text[/align]",
	justify: "Justify: [align=justify]Justified Text[/align]",
	hr: "Horizontal Rule: Horizontal Rule [hr]",
	video:"Insert video file: [video width=# height=#]file URL[/video]",
	emoticon:"Insert emoticon"
};

Poodle_BBCode.toolbars = [
	[
		['b'],
		['i'],
		null,
		['justify'],
		['left'],
		['center'],
		['right'],
		null,
		 // Font color
		['color',['','maroon','red','orange','brown','yellow','green','olive','cyan','blue','darkblue','indigo','violet','white','black']],
		// Font size
		['size',['','xx-small','x-small','small','medium','large','x-large','xx-large','smaller','larger']]
	],[
		['url'],
		['email'],
		['img'],
		['video'],
		['emoticon'],
		null,
		['quote'],
		['code'],
		null,
		['hr'],
		null,
		['ltr'],
		['rtl']
	]
];

Poodle_BBCode.emoticons = [
//{code:":D",  file:"icon_biggrin.gif", title:"Very Happy"},
];

Poodle_BBCode.emoji = [
//	["1F600","grinning"],
];

// attach to window onload event
Poodle.onDOMReady(function(){Poodle_BBCode();});

// Old v9 code
function storeCaret(text) {}
function bbstyle(bbnumber) {}
