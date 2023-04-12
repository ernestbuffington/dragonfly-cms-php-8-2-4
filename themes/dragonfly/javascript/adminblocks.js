var Tack = {
	ft : null,
	ftPos : null,
	id: null,
	mousePos : null,
	root : null,
	src : null,

	move : function(e) {
		if (Tack.src) { return 0;}
		else { return Tack.start(e,this); }
		return 0;
	},
	hover : function(o){
		if (!Tack.src && o) {
			o.style.cursor = 'move';
			o.style.cursor = 'url(themes/dragonfly/images/cursors/grab.cur), move';
			o.onmousedown = Tack.move;
		}
	},
	start : function(e,o) {
		if (!Tack.src || !Tack.ft) {
			e = getEvent(e);
			Tack.src = o;
			// cannot have an integer as element id
			document.onmousemove = this.drag.bind(this);
			document.onmouseup   = this.end.bind(this);
			Tack.mousePos = mousePos(e);
			Tack.ft = document.createElement('div');
			Tack.ft.style.display = 'block';
			Tack.ft.style.width = 'auto';
			Tack.ft.style.position = 'absolute';
			Tack.ft.style.zIndex = 1000;
			Tack.ftPos = {x:getElementLeft(Tack.src),y:getElementTop(Tack.src)+10}
			Tack.ft.style.top = Tack.ftPos.y+'px';
			Tack.ft.style.left = Tack.ftPos.x+'px';
			o.style.cursor = 'move';
			o.style.cursor = 'url(themes/dragonfly/images/cursors/grabbing.cur), move';
			//o.style.cursor = 'move'; // Opera doesn't support url property for cursor
			//o.style.cursor = 'url(themes/dragonfly/images/cursors/grabbing.cur), move';
			Tack.ft.appendChild(o);
			document.body.appendChild(Tack.ft);
		}
	},
	drag : function(e)
	{
		if (Tack.ft) {
			e = getEvent(e);
			var mPos = mousePos(e);
			Tack.ftPos.x += (mPos.x-Tack.mousePos.x);
			Tack.ftPos.y += (mPos.y-Tack.mousePos.y);
			Tack.ft.style.left = Tack.ftPos.x+'px';
			Tack.ft.style.top = Tack.ftPos.y+'px';
			Tack.mousePos = mPos;
		}
	},
	end : function() {
		if (Tack.src && Tack.ft) {
			document.onmousemove = null;
			document.onmouseup = null;
			var home = $('tack_home') ? $('tack_home') : $('tack_holiday');
			var table = $('blist') ? $('blist') : $('blist_fixed');
			Tack.ft.firstChild.style.cursor = 'move';
			Tack.ft.firstChild.style.cursor = 'url(themes/dragonfly/images/cursors/grab.cur), move';
			if (getElementLeft(table)<=Tack.ftPos.x && getElementTop(table)>=Tack.ftPos.y) {
				home.id = 'tack_holiday';
				table.id = 'blist_fixed';
				home.appendChild(Tack.ft.firstChild);
			} else {
				home.id = 'tack_home';
				table.id = 'blist';
				home.appendChild(Tack.ft.firstChild);
			}
		}
		Tack.src = Tack.ft = null;
	}
};




var Block = {
	accept : null,
	childs: null,
	cleanChilds: new Array(),
	uls: new Array(),
	ft : null,
	ftPos : null,
	granPa : null,
	id: null,
	mousePos : null,
	root : null,
	src : null,

	move : function(e) {
		if (Block.src) { return 0;}
		else { return Block.start(e,this); }
		return 0;
	},
	hover : function(o){
		if (!Block.src && o) {
			if (o.nodeName.toLowerCase() == 'td') {
				if (Block.accept) {
					o.style.cursor = 'move'; // Opera
					o.style.cursor = 'url(themes/dragonfly/images/cursors/copy.cur), move';
					o.onmousedown = Block.move;
				} else {
					o.style.cursor = 'not-allowed';
					o.onmousedown = null;
				}
			}
			else if (o.nodeName.toLowerCase() == 'li') {
				if (Block.accept) {
					o.style.cursor = 'not-allowed';
					o.onmousedowm = null;
				} else {
					o.style.cursor = 'move'; // Opera
					o.style.cursor = 'url(themes/dragonfly/images/cursors/grab.cur), move';
					o.onmousedown = Block.move;
				}
			}
		}
	},
	start : function(e,o) {
		if (!Block.src || !Block.ft) {
			e = getEvent(e);
			Block.src = o;
			// cannot have an integer as element id
			var splitted = Block.src.title.split('.');
			var title = splitted[0];
			Block.id = splitted[1];
			if (Block.accept && Block.root) {
				var l = document.createElement('li');
				l.style.listStyle = 'none';
				var txt = document.createTextNode(title);
				l.appendChild(txt);

				var inp = document.createElement('input');
				inp.type = 'hidden';
				inp.name = 'id[]';
				inp.value = Block.id;
				l.appendChild(inp);

				var inp = document.createElement('input');
				inp.type = 'hidden';
				inp.name = 'mid[]';
				inp.value = Block.granPa.mid;
				l.appendChild(inp);

				var inp = document.createElement('input');
				inp.type = 'hidden';
				inp.name = 'module[]';
				inp.value = Block.granPa.name;
				l.appendChild(inp);

				var inp = document.createElement('input');
				inp.type = 'hidden';
				inp.name = 'side[]';
				inp.value = '';
				l.appendChild(inp);

				var inp = document.createElement('input');
				inp.type = 'hidden';
				inp.name = 'weight[]';
				inp.value = '';
				l.appendChild(inp);

				if (Block.candrop()) {
					Block.root.appendChild(l);
					Block.src = Block.id = null;
				}
			} else if (!Block.accept) {
				document.onmousemove = this.drag.bind(this);
				document.onmouseup   = this.end.bind(this);
				Block.mousePos = mousePos(e);
				Block.ft = document.createElement('div');
				Block.ft.style.display = 'block';
				Block.ft.style.width = 'auto';
				Block.ft.style.position = 'absolute';
				Block.ft.style.zIndex = 1000;
				Block.ftPos = {x:getElementLeft(Block.src),y:getElementTop(Block.src)}
				Block.ft.style.top = Block.ftPos.y+'px';
				Block.ft.style.left = Block.ftPos.x+'px';
				o.style.cursor = 'move'; // Opera doesn't support url property for cursor
				o.style.cursor = 'url(themes/dragonfly/images/cursors/grabbing.cur), move';
				// IE doesn't support className dinamical changes
				// Safari will not -even- pickup the url cursor created with javascript
				// no problems with Netscape and Firefox
				//o.style.cursor = null;
				//Block.ft.setAttribute('class', 'grabbing');
				//Block.ft.className = 'grabbing';
				//Block.ft.style.cursor = 'move'; // Opera
				//Block.ft.style.cursor = 'url(themes/dragonfly/images/cursors/grabbing.cur), move';
				o.style.listStyle = 'none';
				Block.ft.appendChild(o);
				document.body.appendChild(Block.ft);
				Block.getchilds();
				//Block.childs = Block.granPa.getElementsByTagName('li');
			}
		}
	},
	drag : function(e)
	{
		if (Block.ft) {
			e = getEvent(e);
			var mPos = mousePos(e);
			Block.ftPos.x += (mPos.x-Block.mousePos.x);
			Block.ftPos.y += (mPos.y-Block.mousePos.y);
			Block.ft.style.left = Block.ftPos.x+'px';
			Block.ft.style.top = Block.ftPos.y+'px';
			Block.mousePos = mPos;
		}
	},
	end : function() {
		if (Block.src && Block.ft) {
			document.onmousemove = null;
			document.onmouseup = null;
			if (Block.childs && Block.candrop()) {
				// hardcode to remove all onmouseover events each ul
				// however will be rewritten
				if (getElementLeft(Block.uls[0])>=Block.ftPos.x || getElementLeft(Block.uls[0])+50>=Block.ftPos.x) {
					Block.drop(Block.uls[0]);
				} else if (getElementLeft(Block.uls[3])<=Block.ftPos.x || getElementLeft(Block.uls[3])-50<=Block.ftPos.x) {
					var ry = getElementTop($(Block.granPa.id+'_recycle'));
					var rx = getElementLeft($(Block.granPa.id+'_recycle'));
					var my = Block.ftPos.y;
					var mx = Block.ftPos.x;
					if (ry<=my && ry+32>=my && rx-20<=mx && rx+32>=mx) {
						$(Block.granPa.id+'_recycled').appendChild(Block.ft.firstChild);
					} else {
						Block.drop(Block.uls[3]);
					}
				} else if (getElementTop(Block.uls[1])>=Block.ftPos.y || getElementTop(Block.uls[2])>=Block.ftPos.y) {
					Block.drop(Block.uls[1]);
				}	else if (getElementTop(Block.uls[2])<=Block.ftPos.y) {
					Block.drop(Block.uls[2]);
				}
			}
		}
		Block.src = Block.ft = Block.id = null;
		Block.getchilds();
		Block.dispatchHover();
	},
	drop : function(p) {
		var ulChilds = p.getElementsByTagName('li');
		for (var i=0; i<ulChilds.length; ++i) {
			if (getElementTop(ulChilds[i])>=Block.ftPos.y && i>0) {
				p.insertBefore(Block.ft.firstChild,ulChilds[i]);
				break;
			}
		}
		if (i == ulChilds.length) {
			p.appendChild(Block.ft.firstChild);
		}
	},
	candrop : function() {
		if (Block.src) {
			var els = Block.granPa.getElementsByTagName('input');
			for (var i=0; i<els.length; ++i) {
				if (els[i].getAttributeNode('name').nodeValue == 'id[]' && els[i].value == Block.id) {
					Block.id = Block.src = null;
					return 0;
				}
			}
		}
		return 1;
	},
	setparent : function(o) {
		// Block.accept needs to be specified before assigning Block.hover to elements
		if (!Block.root && o) {
			Block.root = o.parentNode;
			Block.accept = true;
			Block.dispatchHover();
			o.className = 'active_parent';
		} else {
			if (Block.root == o.parentNode) {
				Block.root = Block.accept = null;
				Block.dispatchHover();
			} else {
				Block.root = o.parentNode;
				Block.accept = true;
				Block.dispatchHover();
				o.className = 'active_parent';
			}
		}
	},
	dispatchHover : function () {
		for (var i=0; i<Block.childs.length; ++i) {
			if (Block.childs[i].className) {
				Block.childs[i].className = 'parent';
			} else {
				Block.childs[i].onmousedown = null;
				Block.hover(Block.childs[i]);
			}
		}
	},
	getchilds : function () {
		Block.cleanChilds = new Array;
		var all = Block.granPa.getElementsByTagName('li');
		// onmouseover each ul doesn't responde that quick
		// write a better script using OnDrag(), OnMove(), OnDrop()
		// or save more data as possible
		for (var i=0, c=0, u=0; i<all.length; ++i, ++c, ++u) {
			if (all[i].className) {
				Block.uls[u] = all[i].parentNode;
				--c;
			} else {
				Block.cleanChilds[c] = all[i];
				--u;
			}
		}
		return
	},
	activate : function(p, o, tag) {
		if (Block.root) {
			Block.accept = true;
			Block.dispatchHover();
			Block.accept = Block.root = null;
		}
		p = $(p);
		Block.granPa = $(o);
		var splitted = Block.granPa.id.split('.');
		Block.granPa.name = splitted[0];
		Block.granPa.mid = splitted[1];
		Block.childs = Block.granPa.getElementsByTagName('li');
		var tags = p.getElementsByTagName(tag);
		for (var i=0; i<tags.length; ++i) {
			if (tags[i].getAttribute('id')) {
				if (tags[i].id == Block.granPa.id) {
					if (Block.granPa.style.display == 'none') {
						Block.granPa.style.display = '';
					} else {
						Block.granPa.style.display = 'none';
						Block.clean();
						return;
					}
				} else {
					tags[i].style.display = 'none';
		}	}	}
		for (var n=0, j=0; n<Block.childs.length; ++n, ++j) {
			if (Block.childs[n].className) {
				--j;
			} else {
				Block.cleanChilds[j] = Block.childs[n];
				Block.hover(Block.cleanChilds[j]);
			}
		}
	},
	clean: function() {
		document.onmousemove = null;
		document.onmouseup   = null;
		if (Block.ft) { document.body.removeChild(Block.ft); }
		Block.accept = Block.granPa = Block.id = Block.src = Block.ft = null;
		Block.ftPos = Block.root = Block.childs = null;
		Block.cleanChilds = new Array();
	}
};

function new_block(id) {
	var obj = $(id);
	if (obj.style.display=='inline') {
		obj.style.display = 'none';
		$('add_new_block').style.display = 'none';
	} else if($('add_new_block').style.display == 'inline') {
		//getting lazy here
		$('new_rss').style.display = 'none';
		$('new_fromfile').style.display = 'none';
		$('new_custom').style.display = 'none';
		obj.style.display = 'inline';
	} else {
		$('add_new_block').style.display = 'inline';
		obj.style.display = 'inline';
	}
}