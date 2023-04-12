/**
* MOO CMS
* Copyright (c) 2005 by CPG-Nuke Dev Team, moocms.com
* $Id: dragndrop.js,v 1.1 2007/01/14 02:49:53 nanocaiordo Exp $
*/

var Drag = {
	obj : null,
	root : null,
	ft : null,
	objPos : null,
	mousePos : null,
	onStart : null,
	onDrag : null,
	onEnd : null,

	start : function(e,o)
	{
		if (Drag.obj || (Drag.onStart && !Drag.onStart(o))) return false;
		e = getEvent(e);
		Drag.obj = o;
		Drag.root = o.parentNode;
		Drag.mousePos = mousePos(e);
		document.onmousemove = this.drag.bind(this);
		document.onmouseup   = this.end.bind(this);
		var co = o.parentNode;
		if (co.nodeName.toLowerCase() == 'tbody'){
			co = co.parentNode;
		}
		Drag.ft = document.createElement(co.nodeName);
		Drag.ft.className = co.className;
		Drag.ft.style.border = co.style.border;
		Drag.ft.style.display = 'block';
		Drag.ft.style.width = co.offsetWidth+20+'px';
		Drag.ft.style.position = 'absolute';
		Drag.ft.style.zIndex = 1000;
		Drag.objPos = {x:getElementLeft(o),y:getElementTop(o)}
		Drag.ft.style.top = Drag.objPos.y+'px';
		Drag.ft.style.left = Drag.objPos.x+'px';
		if (o.parentNode.nodeName.toLowerCase() == 'tbody'){
			co = co.parentNode;
			var tb = document.createElement('tbody');
			tb.appendChild(o);
			Drag.ft.appendChild(tb);
		}else{
			Drag.ft.appendChild(o);
		}
		document.body.appendChild(Drag.ft);
		return false;
	},

	drag : function(e)
	{
		if (!Drag.obj) return;
		e = getEvent(e);
		var mPos = mousePos(e);
		Drag.objPos.x += (mPos.x-Drag.mousePos.x);
		Drag.objPos.y += (mPos.y-Drag.mousePos.y);
		var firstE = Drag.root.getElementsByTagName(Drag.obj.nodeName);
		firstE = (firstE[0])?firstE[0]:Drag.root;
		firstE.x = getElementLeft(firstE); firstE.y = getElementTop(firstE);
		if (Drag.onDrag){
			Drag.onDrag(firstE);
		}else{
			if (Drag.objPos.x > 0){ Drag.ft.style.left = Drag.objPos.x+'px'; }
			else { Drag.ft.style.left = 0; }
			if (Drag.objPos.y+5 > 0) { Drag.ft.style.top = Drag.objPos.y+'px'; }
			else { Drag.ft.style.top = 0; }
		}
		Drag.mousePos = mPos;
		return false;
	},

	end : function()
	{
		if (!Drag.obj) return;
		document.onmousemove = null;
		document.onmouseup   = null;
		if (!Drag.onEnd || !Drag.onEnd())
		{
			for (var i=0;i<Drag.root.childNodes.length;++i) {
				if (Drag.root.childNodes[i].nodeName == Drag.obj.nodeName &&
				    getElementTop(Drag.root.childNodes[i])>=Drag.objPos.y)
				{
					Drag.root.insertBefore(Drag.obj,Drag.root.childNodes[i]);
					break;
				}
			}
			if (i == Drag.root.childNodes.length){ Drag.root.appendChild(Drag.obj); }
		}
		document.body.removeChild(Drag.ft);
		Drag.obj = Drag.root = Drag.ft = Drag.onStart = Drag.onDrag = Drag.onEnd = null;
	}

}
