(function(K){

function toggleEdit(e)
{
	e.stop();
	var id = this.data('edit-id');
	K.$('cbody'+id).toggleClass('hidden');
	K.$('cedit'+id).toggleClass('hidden');
}

K.onDOMReady(function(){
	var nodes = K.$Q('#picture-comments a[data-edit-id]');
	nodes.forEach(function(n){
		n.on('click', toggleEdit);
	});
});

})(Poodle);

