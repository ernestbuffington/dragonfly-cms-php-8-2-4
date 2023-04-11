
Poodle.onDOMReady(function(){
	var n = Poodle.$('DeniedUserNames');
	n.on('click',function(e){
		var n = e.target, p = n.parent();
		if (n.hasClass('icon-add')) {
			n = p.cloneNode(true).placeBefore(p);
			n.$Q('button',1).replaceClass('icon-add','icon-delete');
			n.$Q('input',1).value='';
		} else if (n.hasClass('icon-delete')) {
			p.remove();
			e.stop();
		}
	});
});
