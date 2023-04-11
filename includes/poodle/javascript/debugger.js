/*
	@import "poodle"
*/

Poodle.Debugger = function(log)
{
	if (!log) { return; }
	var dbgc, i, div, v;

	function phpc(){
		if (!dbgc) {
			dbgc = (Poodle.$("debug-console") || document.body.$A("ul",{id:"debug-console"}))
				.on("click",function(e){if(e.target==dbgc)dbgc.toggleClass("show")});
		}
		return dbgc;
	}

	function filesize_to_human(size, precision)
	{
		if (is_number(size)) {
			if (!is_number(precision)) precision = 2;
			size = Math.max(size, 0);
			var i = 0;
			precision = Math.pow(10, Math.max(0, precision));
			while (size >= 1024) { size /= 1024; ++i; }
			size = Poodle.L10N["_FILESIZE"][i].replace("%s", Math.round(size*precision)/precision);
		}
		return size;
	}
	function time_to_human(v)
	{
		return v ? (v>1 ? (Math.round(v*100)/100)+" s" : (Math.round(v*10000)/10)+" ms") : 0;
	}

	function log_item(c, t, v)
	{
		if (v) return phpc().$A("li",{
			"class":c,
			innerHTML:v,
			title:t
		});
	}

	function log_list(c, t, v)
	{
		var i=0, l=v?v.length:0, div;
		if (v && l) {
			div = log_item(c, t, l).$A("div");
			for (;i<l;++i) div.$A("div",{innerHTML:v[i]});
		}
	}

	if (v = log.memory) { log_item("memory", "Memory", filesize_to_human(v)); }

	if (v = log.parse_time) { log_item("parse_time", "Parse time", time_to_human(v)); }

	if (v = log.tpl_files) {
		div = log_item("tpl_time", "TPL files", v.length+" in "+time_to_human(log.tpl_time)).$A("div");
		for (i=0;i<v.length;++i) {
			div.$A("div",{innerHTML:v[i]});
		}
	} else if (v = log.tpl_time) {
		log_item("tpl_time", "TPL time", time_to_human(v));
	}

	if (v = log.sql) {
		div = log_item("sql", "Database queries", v.count+" in "+time_to_human(v.time));
		if (v.queries) {
			div = div.$A("div");
			for (v in log.sql.queries) {
				div.$A("strong",{innerHTML:v});
				v=log.sql.queries[v];
				for (i=0;i<v.length;++i) {
					div.$A("div",{innerHTML:"#"+v[i].line+" ("+time_to_human(v[i].time)+"): "+v[i].query});
				}
			}
		}
	}

	if (v = log.php) {
		var c=0, l, p, ii, w={w:0,n:0,d:0}, re=/(w|n|d)(arning|otice|eprecated)/i, m;
		for (i=0;i<v.length;++i) {
			l=v[i].log;
			for (p in l) {
				if (l[p] && l[p].length) {
					if (m = p.toLowerCase().match(re)) w[m[1]] += l[p].length;
					c += l[p].length;
				}
			}
		}
		if (c) {
			div = log_item("warnings", "Warnings/Notices/Deprecated", w.w+"/"+w.n+"/"+w.d).$A("div");
			for (i=0;i<v.length;++i) {
				div.$A("strong",{innerHTML:v[i].file});
				l=v[i].log;
				for (p in l) {
					if (l[p] && l[p].length) {
						for (ii=0;ii<l[p].length;++ii) div.$A("div",{innerHTML:p+": "+l[p][ii]});
					}
				}
			}
			phpc().addClass("show");
		}
	}

	if (v = log.included_files) { log_list("files", "Included files", v); }
	if (v = log.declared_classes) { log_list("classes", "Declared classes", v); }
	if (v = log.declared_interfaces) { log_list("interfaces", "Declared interfaces", v); }
};

Poodle.L10N["_FILESIZE"] = ["%s   B", "%s KiB", "%s MiB", "%s GiB", "%s TiB"];
