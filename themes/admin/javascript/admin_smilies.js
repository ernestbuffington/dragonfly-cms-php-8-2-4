
Poodle.onDOMReady(function(){

	var i=0, tr, trs = Poodle.$Q('#smilies tr');
	while (tr = trs[i++])
	{
		tr.attr('draggable','true')
//		.on('selectstart', function(e){e.stop()}) // IE prevent text selection
		.on('dragstart', function(e) {
			e.setDragEffect('move').setDragData('text', 'smiley');
		})
		.on('dragenter', function(e){e.stop();})
		.on('dragover', function(e){
			// dt.getData('text') protected so can't use that to check
			var dn = e.getDraggingNode();
			if (dn && dn != this && 'tr' === dn.lowerName()) {
				e.stop(); // allows us to drop
			}
		})
		.on('drop', function(e){
			e.stop();
			var dn = e.getDraggingNode();
			if (dn && dn != this && 'smiley' == e.getDragData('text')) {
				dn.placeBefore(this);
			}
		});
	}


	var n = Poodle.$Q('select[name="smile_url"]',1);
	function sc(){
		n = Poodle.$("smiley_image");
		n.src = n.src.replace(/\/[^\/]+$/, '/'+this.currentOption().value);
	}
	if (n) { n.on('change',sc).on('DOMControlValueChanged',sc); }

});
