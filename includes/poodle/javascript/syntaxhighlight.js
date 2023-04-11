/*
 * Comment
 */


(function(){

	function stringSplit(code)
	{
		code = (' ' + code).split(/([^\w])('(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*")(?=[^\w]|$)/g);
		var i = 0, r = [], l = code.length;
		for (;i<l; i+= 3) {
			if (i+1 < l) {
				r.push(code[i]+code[i+1]);
				r.push(code[i+2]);
			} else {
				r.push(code[i]);
			}
		}
		r[0] = r[0].substr(1);
		return r;
	}

	function parseAttributes(tag)
	{
		return tag.replace(/\s([\w-:]+)(?:=('[^']*'|"[^"]*"))?/g, function(s, k, v){
			return ' <span class="attribute">' + k + '</span>' + (v ? '=<span class="string">'+v+'</span>' : '');
		});
	}

	SyntaxHighlight = {
		languages: {
			css : {
				regex: [
					[/[^{}]+{|}[^{}]+/g, function(s){
						s = s.replace(/(^|\s)(\w+)([:{\[\s,\.])/g, '$1<span class="tag">$2</span>$3');
						return s.replace(/(::(?:after|before|first-letter|first-line|selection)|:(?:active|checked|disabled|empty|enabled|first-child|first-of-type|focus|hover|in-range|invalid|lang(language)|last-child|last-of-type|link|not(selector)|nth-child(n)|nth-last-child(n)|nth-last-of-type(n)|nth-of-type(n)|only-of-type|only-child|optional|out-of-range|read-only|read-write|required|root|target|valid|visited))/g, '<span class="keyword">$1</span>');
					}],
					[/{[^}]+}|{[^{}]+$|[^{}]+}/g, function(s){return s.replace(/([\w-]+)(\s*:)/g, '<span class="attribute">$1</span>$2');}]
				]
			},
			diff : {},
			ini : {
				regex: [
					[/^(\[\w+\])/gm, '<span class="tag">$1</span>'],
					[/^(;.*)/gm, '<span class="comment">$1</span>'],
					[/^([\w\.\-]+)(\s*=\s*)(.+)?/gm, function(c,k,s,v){return '<var>'+k+'</var>'
						+s
						+(v?'<span class="attribute">'+v+'</span>':'');}]
				]
			},
			js : {
				keywords: 'function|var|this|if|else|return|new|instanceof|typeof' +
					'|do|while|for|switch|case|default|break|continue' +
					'|try|catch|throw',
				literal: 'true|false|null|undefined|console',
				regex: [
					// numbers
					[/([^\w])([0-9]+(?:\.[0-9]+)?|0x(?:[0-9A-F][0-9A-F])+)(?=[^\w])/g,'$1<span class="number">$2</span>'],
					// comments
					[/\/\/.*$/gm,   function(s){return '<span class="comment">' + strip_tags(s) + '</span>';}]
				]
			},
			php : {
				keywords:
					'class|interface|trait|function|abstract|public|protected|private|static|final|namespace|extends|implements|use|const' +
					'|clone|new|parent|self|$this|global|return|yield' +
					'|__CLASS__|__FUNCTION__|__METHOD__|__FILE__|__LINE__|__DIR__|__NAMESPACE__' +
					'|include|include_once|require|require_once' +
					'|do|while|endwhile|foreach|endforeach|for|endfor|as|break|continue' +
					'|switch|case|default|endswitch' +
					'|if|and|xor|or|elseif|else|endif' +
					'|array|list|isset|unset' +
					'|echo|print|exit|eval|die|empty' +
					'|declare|enddeclare' +
					'|goto|instanceof|insteadof' +
					'|try|catch|finally|throw|exception',
				literal: 'true|false|null',
				regex: [
					[/(&lt;\?php|&lt;\?=|\?&gt;)/gi,'<span class="tag">$1</span>'], // php tags
					[/(\$[\w_]+)/g,'<var>$1</var>'], // vars
					// numbers
					[/([^\w$])([0-9]+(?:\.[0-9]+)?|0x(?:[0-9A-F][0-9A-F])+)(?=[^\w])/g,'$1<span class="number">$2</span>'],
					// comments
					[/(?:\/\/|#).*$/gm,   function(s){return '<span class="comment">' + strip_tags(s) + '</span>';}]
				]
			},
			sql : {
				keywords:
					'insert into|select|update|delete|create|alter|drop|add|modify|table|view|as|set|from|values|where|and|or|order by|limit|offset|having|left|inner|right|join|using|on|desc|asc|primary|key|not|index|column' +
					'|BIGINT|BIGSERIAL|BINARY|BLOB|CHAR|DATE|DOUBLE|TIME|DATETIME|INT|MEDIUMINT|NUMERIC|SERIAL|SMALLINT|TEXT|TIMESTAMP|TINYINT|VARCHAR|VARBINARY',
				literal: 'true|false|null',
				regex: [
					// numbers
					[/([^\w$])([0-9]+(?:\.[0-9]+)?|0x(?:[0-9A-F][0-9A-F])+)(?=[^\w])/g,'$1<span class="number">$2</span>'],
					// comments
					[/(?:\/\/|#|--).*$/gm,   function(s){return '<span class="comment">' + strip_tags(s) + '</span>';}]
				]
			},
			xml : {}
		},
		highlight: function(node)
		{
			var lang = node.data('type') || node.className;
			if ('html' === lang) { lang = 'xml'; }
			if ('javascript' === lang) { lang = 'js'; }
			if (this.languages[lang]) {
				node.addClass('syntaxhighlight').html(
					this.highlightCode(strip_tags(node.innerHTML.replace(/<br[^>]*>\r?\n?/g,'\n')), lang)
				);
				if (1 < node.innerHTML.split("\n").length) {
					if ('pre' !== node.parent().lowerName()) {
						var pre = node.$C('pre');
						node.replaceWith(pre);
						pre.append(node);
					}
					node.addClass('lines');
				}
			}
		},
		highlightCode: function(code, lang)
		{
			var lng = SyntaxHighlight.languages[lang];
			if ('diff' === lang) {
				code = code.replace(/^(\+.*)/gm, '<ins>$1</ins>').replace(/^(\-.*)/gm, '<del>$1</del>');
			} else if ('xml' === lang) {
				// Parse elements
				code = code.replace(/&lt;((?:\/|\?x|!)?\w[\w-:]*)(?:[^"']*|(?:'[^']*'|"[^"]*"))*?\/?&gt;/g, function(s, t){
					if ('style' === t || '/style' === t || 'script' === t || '/script' === t) {
						return s;
					}
					return '<span class="tag">' + parseAttributes(s) + '</span>';
				});
				// script tag
				code = code.replace(/(&lt;script.*?&gt;)([\s\S]*?)(&lt;\/script.*?&gt;)/gi, function(m, s, c, e){
					return '<span class="tag">' + parseAttributes(s) + '</span>'
						+ SyntaxHighlight.highlightCode(strip_tags(c), 'js')
						+ '<span class="tag">' + e + '</span>';
				});
				// style tag
				code = code.replace(/(&lt;style.*?&gt;)([\s\S]*?)(&lt;\/style.*?&gt;)/gi, function(m, s, c, e){
					return '<span class="tag">' + parseAttributes(s) + '</span>'
						+ SyntaxHighlight.highlightCode(strip_tags(c), 'css')
						+ '<span class="tag">' + e + '</span>';
				});
				// PHP
				code = code.replace(/(&lt;\?(?:php|=))([\s\S]*?)(\?&gt;)/gi, function(s){
					return SyntaxHighlight.highlightCode(strip_tags(s), 'php');
				});
				// comments
				code = code.replace(/&lt;!--.*?--&gt;/g, function(s){return '<span class="comment">' + strip_tags(s) + '</span>';});
			}
			// css, js, php, sql
			else {
				code = stringSplit(code);
				var i = 0, l = code.length,
				 re_k = lng.keywords ? new RegExp('(^|[^\\w;$])('+lng.keywords+')(?=[^\\w])', 'gi') : null,
				 re_l = lng.literal ? new RegExp('(^|[^\\w;$])('+lng.literal+')(?=[^\\w])', 'gi') : null;
				for (;i<l; i+= 2) {
					if (re_k) {
						code[i] = code[i].replace(re_k, '$1<span class="keyword">$2</span>');
					}
					if (re_l) {
						code[i] = code[i].replace(re_l, '$1<span class="literal">$2</span>');
					}
					lng.regex.forEach(function(re){code[i] = code[i].replace(re[0], re[1]);});

					if (i+1 < l) {
						if ('php' === lang && '"' === code[i+1][0]) {
							code[i+1] = code[i+1].replace(/({\$[^}]+})/g,'<var>$1</var>');
						}
						code[i+1] = '<span class="string">' + code[i+1] + '</span>';
					}
				}
				code = code.join('');
				// comments
				code = code.replace(/\/\*[\s\S]*?\*\//g, function(s){return '<span class="comment">' + strip_tags(s) + '</span>';});
			}
			return '<i class="line"></i>'+code.replace(/\n/gm,'\n<i class="line"></i>',code);
		}
	};
})();

Poodle.onDOMReady(function(){
	var i=-1, n, nodes = Poodle.$Q("code");
	while (n = nodes[++i]) {
		SyntaxHighlight.highlight(n);
	}
});
