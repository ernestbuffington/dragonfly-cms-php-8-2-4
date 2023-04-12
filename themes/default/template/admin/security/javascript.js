var newRange = {

	count : 0,
	enabled : null,

	enable : function() { // show the button since javascript is enabled otherwise we dont need it
		if (!newRange.enabled) {
			document.getElementById('buttonarea').style.display = 'inline';
			newRange.enabled = true;
		}
	},
	add : function() {
		++newRange.count;
		var startLabel = {attClass:'set', attFor:'ban_ipv4_s_'+newRange.count, attText:'IPv4 start*'};
		var startInput = {attName:'ban_ipv4_s[]', attId:'ban_ipv4_s_'+newRange.count, attType:'text'};
		var endLabel = {attClass:'set', attFor:'ban_ipv4_e_'+newRange.count, attText:'IPv4 end'};
		var endInput = {attName:'ban_ipv4_e[]', attId:'ban_ipv4_e_'+newRange.count, attType:'text'};
		var readNote = '(read note)';
		var range = document.getElementById('ranges');
		var p = document.createElement('p');

		p.appendChild(newRange.createLabel(startLabel));
		newRange.createLineBreak(p);
		p.appendChild(newRange.createInput(startInput));
		newRange.createLineBreak(p);
		newRange.addText(p, "\n");

		p.appendChild(newRange.createLabel(endLabel));
		newRange.createLineBreak(p);
		p.appendChild(newRange.createInput(endInput));

		var ita = newRange.createTextItalic();
		newRange.addText(ita, readNote);
		p.appendChild(ita);

		range.appendChild(p);
		newRange.addText(range, "\n");
	},
	createLabel : function(obj) {
		var lab = document.createElement('label');
		lab.setAttribute('for', obj.attFor);
		lab.setAttribute('class', obj.attClass);
		newRange.addText(lab, obj.attText);
		return lab;
	},
	createInput : function(obj) {
		var inp = document.createElement('Input');
		inp.setAttribute('type', obj.attType);
		inp.setAttribute('id', obj.attId);
		inp.setAttribute('name', obj.attName);
		return inp;
	},
	createLineBreak : function(o) {
		return o.appendChild(document.createElement('br'));
	},
	createTextItalic : function() {
		return document.createElement('i');
	},
	addText : function(o, text) {
		return o.appendChild(document.createTextNode(text));
	}
}
