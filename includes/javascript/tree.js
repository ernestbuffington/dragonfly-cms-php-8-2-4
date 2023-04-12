var groupstree = new TableTree();
groupstree.min_id = 4;
groupstree.onAdd = function(tr,id) {
	/* hidden cell */
	var td = document.createElement('td');
		var i = document.createElement('input');
			i.name='type['+id+'][]';
			i.type='checkbox';
			i.value=1;
			i.checked='checked';
		td.appendChild(i);
	tr.appendChild(td);
	/* closed cell */
	td = document.createElement('td');
		i = document.createElement('input');
			i.name='type['+id+'][]';
			i.type='checkbox';
			i.value=2;
			i.checked='checked';
		td.appendChild(i);
	tr.appendChild(td);
	this.addDeleteCell(tr);
	/* description cell */
	td = document.createElement('td');
		i = document.createElement('textarea');
			i.name='desc['+id+']';
			i.className='groupsdescription';
		td.appendChild(i);
	tr.appendChild(td);
}
