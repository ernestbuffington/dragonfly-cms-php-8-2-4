
Poodle.onDOMReady(function(){

	/**
	 * Post/Edit Topic Poll
	 */

	function delOption(e)
	{
		e.stop();
		this.parent().parent().remove();
	}

	var n = Poodle.$Q("button[name=add_poll_option]",1);
	if (n) {
		n.on('click', function(e){
			e.stop();
			var tr = this.getParentByTagName('tr'), v = tr.$Q('input',1);
			Poodle.$C(['tr',{},
				['td', {}, ['b',{},'Poll option']],
				['td', {},
					['input', {type:"text", value:v.value, name:"poll_option_text[]", maxlength:"255", style:{width:v?v.css('width'):'34em'}}],
					' ',
					['button', {type:"button", className:"icon-delete", onclick:delOption}, _("Delete")]
				]
			]).placeBefore(tr);
			v.value = "";
			v.focus();
		}).addClass("icon-add");

		var i = -1, nodes = Poodle.$Q("button[name^=del_poll_option]");
		while (n = nodes[++i]) {
			n.on('click', delOption).addClass("icon-delete");
		}

		i = -1;
		nodes = Poodle.$Q("button[name=edit_poll_option]");
		while (n = nodes[++i]) {
			n.remove();
		}
	}

});
