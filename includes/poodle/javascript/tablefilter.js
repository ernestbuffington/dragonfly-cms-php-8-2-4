/**
	@import "poodle"

	<form class="blocklabels filter" id="for-werknemers">
		<label>code</label><input name="col_1"/><br/>
		<label>naam</label><input name="col_2"/>
	</form>
*/

(function(K){

	PoodleTableFilter =
	{
		onInput : function()
		{
			var id = this.attr('data-table'), tbody, bi=0, row, ri, i, v=this.value.toLowerCase(), sd, tbody;
			while (tbody = this.table.tBodies[bi++]) {
				ri=0;
				while (row = tbody.rows[ri++]) {
					if (1 < ri || !row.$Q('th').length) {
						sd = v.length?'none':'';
						for (i=0; i<row.cells.length; ++i) {
							if (-1 < row.cells[i].txt().toLowerCase().indexOf(v, 0)) {
								sd = '';
								break;
							}
						}
						row.style.display = sd;
					}
				}
			}
		},

		init : function(n)
		{
			var id = n.attr('data-table'), t;
			if (id) {
				t = K.$Q('table#'+id, 1);
				if (t) {
					n.table = t;
					n.on('input', this.onInput);
				}
			}
		}
	};

	K.onDOMReady(function(){
		Poodle.$Q('input.tablefilter').forEach(function(n){PoodleTableFilter.init(n);});
	});

})(Poodle);
