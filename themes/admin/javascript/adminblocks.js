
(function(P){

Tack = {
	ft : null,
	mPos : null,
	src : null,

	move : function(e) {
		if (Tack.src) { return 0;}
		else { return Tack.start(e,this); }
		return 0;
	},

	setCursor : function(o, v){ o.className=v; },

	start : function(e,o) {
		if (!Tack.src || !Tack.ft) {
			var pos = o.getBoundingPageRect();
			Tack.src = o;
			Tack.mPos = o.getMousePos(e);
			Tack.ft = document.$C('div',{style:{
				width : 'auto',
				position : 'absolute',
				zIndex : 2147483640,
				top : pos.y+'px',
				left : pos.x+'px'
			}});
			Tack.setCursor(o,'grabbing');
			Tack.ft.appendChild(o);
			document.on("mousemove",Tack.drag).on("mouseup",Tack.end).body.appendChild(Tack.ft);
		}
	},

	drag : function(e)
	{
		if (Tack.ft) {
			Tack.ft.style.left = (e.pageX-Tack.mPos.x)+'px';
			Tack.ft.style.top  = (e.pageY-Tack.mPos.y)+'px';
		}
	},

	end : function(e) {
		if (Tack.src && Tack.ft) {
			document.off("mousemove",Tack.drag).off("mouseup",Tack.end);
			var home = P.$('tack_home'), table = P.$('blist');
			Tack.setCursor(Tack.src,'grab');
			if (P.$('blist').getBoundingClientRect().contains({x:e.pageX,y:e.pageY})) {
				table.removeClass('fixed');
				home.removeClass('holiday').appendChild(Tack.ft.firstChild);
			} else {
				table.addClass('fixed');
				home.addClass('holiday').appendChild(Tack.ft.firstChild);
			}
			Tack.ft.remove();
		}
		Tack.src = Tack.ft = null;
	}
};

Block = {
	prev : null,

	activate : function(o)
	{
		var div=this.prev, form=div?div.parent():0;
		if (div && (div.blocks_changed || form.hasChanges())) {
			if (confirm(_('Save changes?'))) {
				var xhr = new PoodleXHR();
				xhr.form = form;
				xhr.oncomplete = function(xhr){
					div.blocks_changed = null;
					form.removeClass('changed');
					form.setChangesAsDefault();
				};
				xhr.post('', form.asQuery());
			}
		}

		if (o.hasClass('hidden')) {
			o.removeClass('hidden');
			if (this.prev) { this.prev.addClass('hidden'); }
			this.prev = o;
		} else {
			o.addClass('hidden');
			this.prev = null;
		}
	},

	init: function(form)
	{
		form.on("click", function(e){
			if (e.target == this || e.target.parent() == this) {
				Block.activate(this.$Q('.blockcontent',1));
			}
		});
		form.on("change", function(e){
			this[(Block.prev.blocks_changed||this.hasChanges())?'addClass':'removeClass']('changed');
		});

		function dragStart(e)
		{
			e.setDragEffect('move').setDragData('text', 'block');
		}

		var x, objs = form.$Q('li[draggable]');
		for (x=0; x<objs.length; ++x) {
			objs[x].on('dragstart', dragStart);
		}

		objs = form.$Q('ul');
		for (x=0; x<objs.length; ++x) {
			objs[x]
			.on('dragenter', function(e){e.stop();})
			.on('dragover', function(e){
				// dt.getData('text') protected so can't use that to check
				var dn = e.getDraggingNode(), dt = e.dataTransfer;
				if (dn && (
					('move' === dt.effectAllowed && 'li' === dn.lowerName()) ||
					('copy' === dt.effectAllowed && 'tr' === dn.lowerName() && !this.hasClass('rec_bin'))))
				{
					e.stop(); // allows us to drop
				}
			})
			.on('drop', function(e){
				e.stop();
				var dn = e.getDraggingNode(), dt = e.dataTransfer;
				if ('block' == e.getDragData('text')) {
					if (this.hasClass('rec_bin'))
					{
						if ('move' == dt.effectAllowed) { dn.remove(); Block.prev.blocks_changed = true; }
					}
					else
					{
						var side = this.attr('data-module-side');
						if ('copy' == dt.effectAllowed) {
							var tr = dn;
							dn = P.$C('li',{textContent:tr.attr('data-block-label'),draggable:'true'});
							dn.on('dragstart', dragStart);
							dn.$A('input',{type:'hidden', value:tr.attr('data-block-id')});
						}
						dn.$Q('input',1).attr('name', (side?'side['+side+'][]':null));
						if (e.target != dn) {
							if ('li' === e.target.lowerName()) {
								if (e.target.hasClass('parent')) {
									dn.placeAfter(e.target);
								} else {
									dn.placeBefore(e.target);
								}
							} else {
								this.appendChild(dn);
							}
							Block.prev.blocks_changed = true;
						}
					}
					form.trigger('change');
				}
			});
		}
	}
};

P.onDOMReady(function()
{
	var i, objs = P.$Q("#mlist form");
	for (i=0; i<objs.length; ++i) { Block.init(objs[i]); }

	objs = P.$Q("#blist tr[draggable]");
	for (i=0; i<objs.length; ++i) {
		objs[i].on('dragstart', function(e) {
			e.setDragEffect('copy').setDragData('text', 'block');
		});
	}
	window.on('beforeunload',function(e){
		var div = Block.prev;
		if (div && (div.blocks_changed || div.parent().hasChanges())) { e.stop(); }
	});

	var o = P.$("tack");
	if (o) {
		Tack.setCursor(o,'grab');
		o.on("mousedown", Tack.move);
	}

	objs = P.$Q('#new_block_types button');
	for (i=0; i<objs.length; ++i) {
		objs[i].on('mouseup', function(){
			var obj = P.$(this.attr('name')), anb=P.$('add_new_block');
			if (!obj.hasClass("hidden")) {
				obj.addClass("hidden");
				anb.addClass("hidden");
			} else if (!anb.hasClass("hidden")) {
				//getting lazy here
				P.$('new_rss').addClass("hidden");
				P.$('new_fromfile').addClass("hidden");
				P.$('new_custom').addClass("hidden");
				obj.removeClass("hidden");
			} else {
				anb.removeClass("hidden");
				obj.removeClass("hidden");
			}
		});
	}

});

})(Poodle);
