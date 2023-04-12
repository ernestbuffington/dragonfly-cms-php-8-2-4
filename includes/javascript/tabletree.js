/**
* MOO CMS
* Copyright (c) 2005 by CPG-Nuke Dev Team, moocms.com
* $Id: tabletree.js,v 1.1 2007/01/14 02:49:53 nanocaiordo Exp $
*/
/* NOTE: also include framework.js and dragndrop.js */

function TableBase()
{
	/* private */
	var inc_id = 0;
	/* public */
	this.move = function(e)
	{
		e = getEvent(e);
		if (Drag.obj || e.button > 1) return;
		var o = getEventNode(e);
		/* fix IE issue with srcElement not being the event handle owner */
		if (o.parentNode.className=='first') { o = o.parentNode; }
		return Drag.start(e, o.parentNode);
	};

	this.hover = function(e, o, c)
	{
		if (Drag.obj) return false;
		if (!o) o = getEventNode(e);
		o.style.cursor = 'move';
		o.onmousedown = (c?c:this).move.bind(this);
		return true;
	};

	this.addDeleteCell = function(tr){
		var td = document.createElement('td');
		td.innerHTML = 'X';
		td.style.cursor = 'pointer';
		td.onmousedown = function(e){getEventNode(e).parentNode.parentNode.removeChild(getEventNode(e).parentNode);};
		tr.appendChild(td);
	};
	this.addNameCell = function(tr,area,id){
		var td = document.createElement('td');
		var i = document.createElement('input');
			i.name='name['+id+']';
			i.type='text';
			i.value=getElementByName(area,'new[name]').value;
		td.appendChild(i);
		tr.appendChild(td);
	};

	this.add = function(tbody){
		tbody = document.getElementById(tbody);
		var id = inc_id++;
		var tr = document.createElement('tr');
		/* row mover cell */
		var td = document.createElement('td');
			td.innerHTML = id;
			td.onmouseover = this.hover.bind(this);
			td.style.cursor = 'move';
			var i = document.createElement('input');
				i.name='id[]';
				i.type='hidden';
				i.value='n'+id;
			td.appendChild(i);
		tr.appendChild(td);
		/* name cell */
		this.addNameCell(tbody,'n'+id);
		// additional fields?
		if (this.onAdd) this.onAdd(tr, id);
		else this.addDeleteCell(tr);
		tbody.appendChild(tr);
		return false;
	};

}

function TableTree()
{
	this.extend(TableBase);

	/* private */
	var tb_hover = this.hover; // move TableBase.hover
	var inc_id = 0;

	function setparent(p, o){
		getElementByName(o,'parent[]').value=getElementByName(p,'id[]').value;
	};

	/* public */
	this.min_id = 1;

	this.hover = function(e)
	{
		var o = getEventNode(e);
		if (o.parentNode.className=='first')
		{
			// dragndrop tbody
			if (tb_hover(e, o.parentNode, this))
				o.parentNode.onmouseout = function(){this.onmousedown = null;this.style.cursor = '';};
			return;
		}
		if (!tb_hover(e, o, this)) return;
		Drag.onStart = function(o){
			if (o.className=='last') {
				// fix last tr classname of previous tbody
				var ptr = getRChildNode(o.parentNode, 'tr', 1, 3);
				if (ptr) ptr.className='last';
			}
			return true;
		}
		Drag.onEnd = function(){
			// check if something odd happend and Drag.onEnd is assigned
			// to a tbody which is the wrong dragging object
			if (Drag.obj.nodeName.toLowerCase() == 'tbody'){ return false; }
			pn = Drag.root.parentNode.getElementsByTagName('tbody');
			for (var p=pn.length-1;p>=0;--p) {
				if (Drag.objPos.y >= getElementTop(pn[p])) {
					tn = pn[p].getElementsByTagName('tr');
					setparent(tn[0],Drag.obj);
					for (var i=1;i<tn.length;++i) {
						if (Drag.objPos.y<=getElementTop(tn[i])) {
							Drag.obj.className='';
							return pn[p].insertBefore(Drag.obj,tn[i]);
						}
					}
					Drag.obj.className='last';
					if (tn.length>1) tn[tn.length-1].className='';
					return pn[p].appendChild(Drag.obj);
				}
			}
			setparent(pn[0].getElementsByTagName('tr')[0],Drag.obj);
			Drag.obj.className='last';
			pn[0].appendChild(Drag.obj);
			return true;
		}
	};

	this.switchlevel = function(e)
	{
		var o = getEventNode(e);
		if (getElementByName(o.parentNode,'id[]').value < this.min_id) return;
		var tr = o.parentNode;
		var tb = tr.parentNode;
		if (tr.className=='first'){
			// make child
			// fail when there are children or no previous tbody sibbling
			var ptb = getPrevNode(tb, 1);
			if (tb.getElementsByTagName('tr').length>1 || !ptb) return;
			// fix last tr classname of previous tbody
			var ptr = getRChildNode(ptb, 'tr', 0, 2);
			if (ptr) ptr.className='';
			// make it the last child
			tr.className='last';
			getElementByName(tr,'parent[]').value=getElementByName(ptb,'id[]').value;
			ptb.appendChild(tr);
			// remove the old surrounding tbody
			tb.parentNode.removeChild(tb);
		}else{
			// make parent
			if (tr.className=='last') {
				// fix last-1 tr classname of previous tbody
				var ptr = getRChildNode(tb, 'tr', 1, 3);
				if (ptr) ptr.className='last';
			}
			tr.className='first';
			getElementByName(tr,'parent[]').value=0;
			// create surrounding tbody
			var ntb = document.createElement('tbody');
			ntb.appendChild(tr);
			insertAfter(tb.parentNode, ntb, tb);
		}
	};

	this.add = function(table){
		table = document.getElementById(table);
		var id = 'n'+(inc_id++);
		var pid = getElementByName(table, 'new[parent_id]', 'select').value;
		var tr = document.createElement('tr');
		/* tree cell */
		var td = document.createElement('td');
			td.onmouseover = this.hover.bind(this);
			td.className = 'tree';
			var i = document.createElement('input');
				i.name='id[]';
				i.type='hidden';
				i.value=id;
			td.appendChild(i);
			var i = document.createElement('input');
				i.name='parent[]';
				i.type='hidden';
				i.value=pid;
			td.appendChild(i);
		tr.appendChild(td);
		/* levelswitch cell */
		td = document.createElement('td');
			td.onclick=this.switchlevel.bind(this);
			td.className='treeswitch';
		tr.appendChild(td);
		/* name cell */
		this.addNameCell(tr,table,id);
		// additional fields?
		if (this.onAdd) this.onAdd(tr, id);
		else this.addDeleteCell(tr);
		if (pid < 1)
		{
			var tb = document.createElement('tbody');
			tr.className='first';
			tb.appendChild(tr);
			var pn = table.getElementsByTagName('tfoot');
			table.insertBefore(tb, pn[0]);
		}
		else
		{
			// child item
			pn = table.getElementsByTagName('input');
			for (var p=pn.length-1;p>=0;--p)
			{
				if (pn[p].name == 'id[]' && pn[p].value == pid)
				{
					// append to tbody group
					tr.className='last';
					pn[p].parentNode.parentNode.parentNode.appendChild(tr);
					if (getPrevNode(tr) != pn[p].parentNode.parentNode) getPrevNode(tr).className='';
				}
			}
		}
		return false;
	};

}
