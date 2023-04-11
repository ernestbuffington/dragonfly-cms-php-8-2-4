/*
	@import "poodle"

	Instructions:
		$OUT->head->addScript('poodle_tablesort');
		Add class="sortable" to any table you'd like to make sortable
		Click on the headers to sort

	HTMLTableElement
*/

(function(){

	PoodleTableSort = function(table)
	{
		this.sort = function(column)
		{
			this.thead.cells[column].trigger('click');
		};

		this.click = function(e)
		{
			var tBodies = this.getParentByTagName('table').tBodies, tbody, bi=0, i, ri;

			if (this.hasClass('asc') || this.hasClass('desc')) {
				// if we're already sorted by this column, just
				// reverse the table, which is quicker
				// reverse the rows in a tbody
				while (tbody = tBodies[bi++]) {
					var newrows = [];
					for (ri = 0; ri < tbody.rows.length; ++ri) {
						if (0 < ri || !tbody.rows[ri].$Q('th').length) {
							newrows.push(tbody.rows[ri]);
						}
					}
					for (ri = newrows.length-1; ri >= 0; --ri) {
						 tbody.appendChild(newrows[ri]);
					}
					delete newrows;
				}
				this.toggleClass('asc').toggleClass('desc');
				return;
			}

			// remove sorttable_sorted classes
			var cells = this.parentNode.cells, col=this.cellIndex;
			for (i = 0; i < cells.length; ++i) {
				cells[i].removeClass('asc').removeClass('desc');
			}
			this.addClass('asc');

			// build an array to sort. This is a Schwartzian transform thing,
			// i.e., we "decorate" each row with the actual sort key,
			// sort based on the sort keys, and then put the rows back in order
			// which is a lot faster because you only do getInnerText once per row
			while (tbody = tBodies[bi++]) {
				var rows = tbody.rows, newrows = [];
				for (ri = 0; ri < rows.length; ++ri) {
					if (0 < ri || !tbody.rows[ri].$Q('th').length) {
						newrows.push([getInnerText(rows[ri].cells[col]), rows[ri]]);
					}
				}
				/* If you want a stable sort, uncomment the following line */
				//shaker_sort(newrows, this.sorttable_sortfunction);
				/* and comment out this one */
				newrows.sort(this.sorttable_sortfunction);

				for (ri = 0; ri < newrows.length; ++ri) {
					tbody.appendChild(newrows[ri][1]);
				}
				delete newrows;
			}
		};

		var ths = table.$T('thead'), th, cells, i=0;

		if (0 < ths.length) {
			th = ths[0];
		} else {
			// table doesn't have a tHead. Since it should have, create one and
			// put the first table row in it.
			th = table.$C('thead').before(th, table.firstChild);
			th.$A(table.rows[0]);
		}

		this.thead = th.rows[th.rows.length-1];

		// work through each column of the last thead row and calculate its type
		cells = this.thead.cells;
		for (; i<cells.length; ++i)
		{
			// manually override the type with a sorttable_type attribute
			if (!cells[i].hasClass('nosort')) {
				var mtch = cells[i].className.match(/\bsorttable_([a-z0-9]+)\b/),
				   tbody = table.tBodies[0];
				if (mtch && is_function(PoodleTableSort["sort_"+mtch[1]])) {
					cells[i].sorttable_sortfunction = this["sort_"+mtch[1]];
				} else {
					cells[i].sorttable_sortfunction = guessType(tbody, i);
				}
				// make it clickable to sort
				cells[i].on("click", this.click);
			}
		}
	};

	function getInnerText(node)
	{
		// gets the text we want to use for sorting for a cell.
		// strips leading and trailing whitespace.
		// this is *not* a generic getInnerText function; it's special to sorttable.
		// for example, you can override the cell text with a customkey attribute.
		// it also gets .value for <input> fields.

		if (node.getAttribute("sorttable_customkey") != null) {
			return node.getAttribute("sorttable_customkey");
		}

		var txt = '' + (node.textContent || node.innerText || node.text);
		if (defined(txt) && !node.$T('input').length) {
			return txt.trim();
		}

		switch (node.nodeType)
		{
		case 3:
			var n = node.lowerName();
			if ('input' == n || 'textarea' == n) {
				return node.value.trim();
			}
		case 4:
			return node.nodeValue.trim();

		case 1:
		case 11:
			var innerText = '', i = 0;
			for (; i < node.childNodes.length; ++i) {
				innerText += getInnerText(node.childNodes[i]);
			}
			return innerText.trim();
		}
		return '';
	}

	// guess the type of a column based on its first non-blank row
	function guessType(tbody, column)
	{
		var rows = tbody.rows, i=0, text, PTS = PoodleTableSort,
		   sortfn = PTS.sort_stri, inc = Math.max(1,Math.floor(rows.length/2));
		for (; i<rows.length; i += inc) {
			if (rows[i].cells[column] && 'td' === rows[i].cells[column].lowerName()) {
				if (rows[i].cells[column].data('datetime')) {
					return PTS.sort_date;
				}
				// Strip currency symbols, percentage and space
				text = getInnerText(rows[i].cells[column]).replace(/$ƒ฿៛€₡₢₣₤₥₦₧₨₩₪₫€₭₮₯₰₱₲₳₴₵%\s/,'');
				if (text != '' && text.match(/^-?[\d,.]+$/)) {
					return PTS.sort_numeric;
				}
			}
		}
		return sortfn;
	}

	function shaker_sort(list, fn)
	{
		// A stable sort function to allow multi-level sorting of data
		// see: http://en.wikipedia.org/wiki/Cocktail_sort
		// thanks to Joseph Nahmias
		var b = 0, t = list.length - 1, swap = true, i, q;
		while (swap) {
			swap = false;
			for (i = b; i < t; ++i) {
				if (fn(list[i], list[i+1]) > 0) {
					q = list[i];
					list[i] = list[i+1];
					list[i+1] = q;
					swap = true;
				}
			}
			--t;
			if (!swap) break;
			for (i = t; i > b; --i) {
				if (fn(list[i], list[i-1]) < 0) {
					q = list[i];
					list[i] = list[i-1];
					list[i-1] = q;
					swap = true;
				}
			}
			++b;
		}
	}

	function si(a,b) { return (a==b ? 0 : (a<b ? -1 : 1)); }

	/* sort functions
		 each sort function takes two parameters, a and b
		 you are comparing a[0] and b[0] */
	PoodleTableSort.sort_numeric = function(a,b) {
		return Math.min(1,Math.max(-1, floatval(a[0])-floatval(b[0])));
	};

	PoodleTableSort.sort_str = function(a,b) { return si(a[0], b[0]); };

	PoodleTableSort.sort_stri = function(a,b) { return si(a[0].toLowerCase(), b[0].toLowerCase()); };

	PoodleTableSort.sort_date = function(a,b) { return si(a[1].toDate(DATE_ISO8601), b[1].toDate(DATE_ISO8601)); };

	Poodle.onDOMReady(function(){
		Poodle.$Q('table.sortable').forEach(function(n){new PoodleTableSort(n);});
	});
})();
