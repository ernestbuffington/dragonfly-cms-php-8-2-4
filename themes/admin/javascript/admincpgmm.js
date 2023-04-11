
Poodle.onDOMReady(function()
{
	var i, objs = Poodle.$Q('#groups tbody[draggable]');
	for (i=0; i<objs.length; ++i) {
		objs[i].on('dragstart', function(e){
			e.setDragEffect('move').setDragData('text', 'cpgmm-group').stopPropagation();
		})
		.on('dragenter', function(e){e.stop();})
		.on('dragover', function(e){
			// dt.getData('text') protected so can't use that to check
			var dn = e.getDraggingNode();
			if (dn && ('tbody' === dn.lowerName() || 'tr' === dn.lowerName())) {
				e.stop(); // allows us to drop
			}
		})
		.on('drop', function(e){
			e.stop();
			var dn = e.getDraggingNode(), t=e.getDragData('text');
			if ('cpgmm-group' === t) {
				if (10 > this.getMousePos(e).y) {
					dn.placeBefore(this);
				} else {
					dn.placeAfter(this);
				}
			} else if ('cpgmm-group-item' === t) {
				dn.placeAfter(this.firstElementChild);
			}
		});
	}

	objs = Poodle.$Q('#groups tbody tr[draggable]');
	for (i=0; i<objs.length; ++i) {
		objs[i].on('dragstart', function(e){
			e.setDragEffect('move').setDragData('text', 'cpgmm-group-item').stopPropagation();
		})
		.on('dragenter', function(e){e.stop();})
		.on('dragover', function(e){
			// dt.getData('text') protected so can't use that to check
			var dn = e.getDraggingNode();
			if (dn && 'tr' === dn.lowerName()) {
				e.stop(); // allows us to drop
			}
		})
		.on('drop', function(e){
			if ('cpgmm-group-item' === e.getDragData('text')) {
				e.stop();
				var dn = e.getDraggingNode();
				if (this.offsetHeight/2 > this.getMousePos(e).y) {
					dn.placeBefore(this);
				} else {
					dn.placeAfter(this);
				}
			}
		});
	}
});
