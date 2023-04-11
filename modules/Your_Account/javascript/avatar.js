
Poodle.onDOMReady(function()
{
	var n = Poodle.$Q("input[name=avatar]",1), i = Poodle.$("avatar_preview");
	if (n && i) {
		i.style.maxHeight = "150px";
		i.style.maxWidth = "150px";
		i.style.display = "block";
		n.on("change", function(){
			var f = this.files[0];
			if (/image\/(jpeg|gif|png)/.test(f.type)) {
				var r = new FileReader();
				r.onload = function(e) { i.src = e.target.result; };
				r.readAsDataURL(this.files[0]);
			} else {
				i.src = null;
			}
		});
	}
});
