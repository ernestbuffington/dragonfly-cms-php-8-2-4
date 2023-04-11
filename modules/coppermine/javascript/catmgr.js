/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	@import "poodle"
*/
(function(K){

Coppermine_CatMngr =
{
	tree:null,

	init:function()
	{
		this.tree = K.$('coppermine_categories');
		if (this.tree) {
			var i=-1, li, nodes = this.tree.$Q('li');
			while (li = nodes[++i]) {
				this.initLI(li);
			}
		}
	},

	initLI:function(li)
	{
		if (!li._bm_tree && li.attr('data-id')) {
			li._bm_tree = 1;
			li.attr('draggable','true')
				.on('selectstart', function(e){e.stop();}) // IE prevent text selection
				.on('dragstart', function(e) {
					var dt = e.dataTransfer;
					dt.effectAllowed = 'move';
					dt.setData('text', li.attr('data-id'));
					e.stopPropagation();
				})
				.on('dragenter', function(e){e.stop();})
				.on('dragover', function(e){
					var dli = e.getDraggingNode();
					if (dli == li || dli.contains(li)) {
						e.dataTransfer.dropEffect = 'none';
					} else {
						e.dataTransfer.dropEffect = 'move';
						var h = li.clientHeight/4;
						if (h >= li.getMousePos(e).y) {
							dli.placeBefore(li);
						} else
						if (h*3 <= li.getMousePos(e).y) {
							dli.placeAfter(li);
						// User gallery is 1, it can't have sub or be sub
						} else if (!li.$Q('ul li',1) && 1<li.attr('data-id') && 1<dli.attr('data-id')) {
							li.$Q('ul',1).insertBefore(dli, null);
						}
					}
					e.dataTransfer.dropEffect = 'move';
					e.stop();
				})
				.on('drop', function(e){
					var dli = e.getDraggingNode(), a = li.$Q('a',1),
					 parent = dli.getParentByTagName('li'),
					 prevli = dli.prevElement();
					a.xhr = new PoodleXHR;
					a.xhr.oncomplete = function(pxhr){
						a.removeClass('loading');
						var data = pxhr.fromJSON();
						if (!data || !data.moved) {
							// put back?
						}
					};
					a.addClass('loading');
					a.xhr.post(null, {
						move_item: intval(dli.attr('data-id')),
						parent_id: parent?intval(parent.attr('data-id')):0,
						after_id:  prevli?intval(prevli.attr('data-id')):0
					});
					e.stop();
				});
		}
	}

};

K.onDOMReady(function(){
	Coppermine_CatMngr.init();
//	window.on('beforeunload',function(e){e.stop()});
});

})(Poodle);
