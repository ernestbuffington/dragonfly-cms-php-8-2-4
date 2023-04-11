var newRange = {

	count : 0,
	enabled : null,
	hook : null,

	enable : function() {
		if (!newRange.enabled) {
			Poodle.$('buttonarea').style.display = 'inline';
			newRange.enabled = true;
		}
	},
	add : function() {
		++newRange.count;
		var startLabel = {attFor:'ban_ipv4_s'+newRange.count, attText:'IPv4 start*'};
		var startInput = {attName:'ban_ipv4_s[]', attId:'ban_ipv4_s'+newRange.count, attSize:18, attMaxLength:18};
		var endLabel = {attFor:'ban_ipv4_e'+newRange.count, attText:'IPv4 end'};
		var endInput = {attName:'ban_ipv4_e[]', attId:'ban_ipv4_e'+newRange.count, attSize:15, attMaxLength:15};
		hook = Poodle.$('ranges');

		newRange.createContainer(startLabel, startInput);
		newRange.createContainer(endLabel, endInput);
	},
	createContainer : function(label, input) {
		var container = Poodle.$C('div', {'class':'admin-section-item'});
		container.appendChild(newRange.createLabel(label));
		var input_container = Poodle.$C('span');
		input_container.appendChild(newRange.createInput(input));
		container.appendChild(input_container);
		container.placeBefore(hook);
		Poodle.$C('br').placeBefore(hook);
	},
	createLabel : function(obj) {
		return Poodle.$C('label', {'for':obj.attFor}).html(obj.attText);
	},
	createSpan : function(obj) {
		return Poodle.$C('span');
	},
	createInput : function(obj) {
		return Poodle.$C('input', {'type':'text', 'id':obj.attId, 'name':obj.attName, 'size':obj.attSize, 'maxlength':obj.attMaxLength});
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
};
