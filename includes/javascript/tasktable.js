var tasktable = {
	move : function(e){
		if (Drag.obj) return;
		return Drag.start(e,this.parentNode);
	},
	hover : function(o){
		if (Drag.obj || !o) return false;
		o.style.cursor = 'move';
		o.onmousedown = tasktable.move;
		return true;
	},
	setparent : function(p,o){
		getElementByName(o,'parent[]').value=getElementByName(p,'id[]').value;
	},
	add : function(area){
		var tt = document.getElementById(area+'s');
		var id = tt.getElementsByTagName('tr').length+1;
		var nt = document.getElementById('new'+area);
		var tr = document.createElement('tr');
		var td = document.createElement('td');
			td.innerHTML = id;
			td.onmousedown = tasktable.move;
			td.style.cursor = 'move';
		tr.appendChild(td);
		td = document.createElement('td');
			var i = document.createElement('input');
				i.name='name[]';
				i.type='text';
				i.maxlength=40;
				i.size=15;
				i.value=nt.value;
			td.appendChild(i);
			i = document.createElement('input');
				i.name='id[]';
				i.type='hidden';
				i.value='n'+id;
			td.appendChild(i);
		tr.appendChild(td);
		td = document.createElement('td');
			i = document.createElement('input');
				i.name='show[n'+id+']';
				i.type='checkbox';
				i.value=1;
			td.appendChild(i);
		tr.appendChild(td);
		td = document.createElement('td');
			td.innerHTML = 'X';
			td.style.cursor = 'pointer';
			td.onmousedown = function(){this.parentNode.parentNode.removeChild(this.parentNode);};
		tr.appendChild(td);
		tt.appendChild(tr);
		return false;
	}
};
