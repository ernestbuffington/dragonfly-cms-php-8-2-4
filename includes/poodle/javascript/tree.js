/*	Poodle WCMS, Copyright (c) MH X Solutions since 2010. All rights reserved.

	The contents of this file are subject to the terms of the
	Common Development and Distribution License, Version 1.0 only
	(the "License").  You may not use this file except in compliance
	with the License.

	@import "poodle"
*/

function Poodle_Tree()
{
	var n, i=-1, trees = arguments.length ? arguments : Poodle.$Q("ul.tree");
	while (n=trees[++i])
	{
		n.on("click",onclick)
		 .on("keydown",onkey)
		 .toggleAll = function(unfold) {
			var i=-1, n, li = this.$Q('li');
			while (n = li[++i]) toggle(this, n, !!unfold);
		};
		n.expandItem   = function(li){toggle(this,li,1);};
		n.collapseItem = function(li){toggle(this,li,0);};
		n.fullExpand   = function(){this.toggleAll(1);};
		n.fullCollapse = function(){this.toggleAll(0);};
	}

	function getLI(o)
	{
		while (o && "li" != o.lowerName()) { o = o.parent(); }
		return o;
	}

	function onkey(e)
	{
		if ('a' != e.target.lowerName()) return;

		var kc = e.keyCode;
		// (un)fold using left/right arrows
		if (37 == kc) kc = 109; // left
		if (39 == kc) kc = 107; // right
		if (107 == kc || 109 == kc)
		{
			var li = getLI(e.target);
			if (li && (li.hasClass("folds") || li.hasClass("unfolds"))) {
				toggle(this, li, 107 == kc);
			}
		}
		// Browse using up/down arrows
		if (40 == kc || 38 == kc)
		{
			var li = getLI(e.target), nodes = this.$Q('.tree > li, .folds > ul > li'), i=0, n;
			for (;i<nodes.length;++i) {
				if (nodes[i] == li) {
					if (40 == kc && nodes[i+1]) ++i;
					else if (38 == kc && 0<i) --i;
					nodes[i].$Q('a',1).focus();
					return;
				}
			}
		}
	}

	function onclick(e)
	{
		var o = e.target;
		if ("li" == o.lowerName() && intval(o.css('padding-left',1)) >= o.getMousePos(e).x && toggle(this, o)) e.stop();
	}

	function toggle(tree, o, unfold)
	{
		if (!defined(unfold)) { unfold = o.hasClass("unfolds"); }
		if (o.$Q("ul",1)) {
			o.replaceClass(/(un)?folds/, unfold?"folds":"unfolds");
			return true;
		} else if (o.hasClass("loading")) {
			o.removeClass("loading");
			if (o.xhr) o.xhr.abort();
		} else if (unfold) {
			var uri = o.$Q("a",1).attr('href');
			uri += (0>uri.indexOf("?") ? "?" : "&");
			uri += "tree=getChildren&"+o.$D().location.search.substr(1);
			o.addClass("loading");
			o.xhr = new PoodleXHR;
			o.xhr.parent = o;
			o.xhr.oncomplete = function(pxhr){
					try {
						var data=pxhr.fromJSON(), o=pxhr.parent;
						if (data && data.DOM) {
							o.$A(data.DOM);
							o.replaceClass(/(un)?folds/, "folds");
							tree.trigger("PoodleTreeModified",{},o);
						}
					} catch (e) {console.error(e);}
					o.removeClass("loading");
				};
			o.xhr.get(uri);
			return true;
		}
	}
}

Poodle.onDOMReady(function(){Poodle_Tree();});
