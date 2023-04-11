/**
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
 */

(function(K){

K.onDOMReady(function(){
	var btn = K.$Q("#packages button[name=install]",1);
	if (btn) {
		btn.on('click', function(e){
			e.stop();
			var n, i=-1, nodes = K.$Q("input[type=checkbox][name^=packages]"), re = /^packages\[([0-9]+)\].+$/;
			while (n = nodes[++i]) {
				if (n.checked) {
					var p = K.$C('progress', {style:{display:'block',width:'100%'}});
					(n.getParentByTagName('a')||n).replaceWith(p);
					p.xhr = new PoodleXHR();
					p.xhr.p = p;
					p.xhr.onresponseline = function(line){
						json = JSON.parse(line);
						if (json) {
							var p = this.p;
							if (json.progress) {
								p.max = json.progress.max;
								p.value = json.progress.value;
							}
							if (json.complete) {
								var t = K.$C('span', {'class':"success", textContent:'installed'});
								p.replaceWith(t);
							}
							if (json.error) {
								var t = K.$C('span', {'class':"error", textContent:json.error});
								p.replaceWith(t);
							}
						}
					};
					p.xhr.post(this.form.action, {
						action: this.name,
						repo: n.name.replace(re, '$1'),
						name: n.value
					});
				}
			}
		});
	}

	btn = K.$Q("#packages button[name=remove]",1);
	if (btn) {
		btn.on('click', function(e){
			e.stop();
			var n, i=-1, nodes = K.$Q("input[type=checkbox][name^=packages]");
			while (n = nodes[++i]) {
				if (n.checked) {
					var p = K.$C('progress', {style:{display:'block',width:'100%'}});
					(n.getParentByTagName('a')||n).replaceWith(p);
					p.xhr = new PoodleXHR();
					p.xhr.p = p;
					p.xhr.onresponseline = function(line){
						json = JSON.parse(line);
						if (json) {
							var p = this.p;
							if (json.progress) {
								p.max = json.progress.max;
								p.value = json.progress.value;
							}
							if (json.complete) {
								var t = K.$C('span', {'class':"success", textContent:'removed'});
								p.replaceWith(t);
							}
							if (json.error) {
								var t = K.$C('span', {'class':"error", textContent:json.error});
								p.replaceWith(t);
							}
						}
					};
					p.xhr.post(this.form.action, {
						action: this.name,
						name: n.value
					});
				}
			}
		});
	}
});

})(Poodle);
