
(function(P){
	blocking = function(nr, cookie, vis_state)
	{
		display = (P.$(nr).style.display == 'none') ? vis_state : 'none';
		if (cookie) {
			P.setCookie(nr, display);
		}
		P.$(nr).style.display = display;
	};

	var cs = {
		pictures: [],
		pos: 0,
		delay: 5000,
		stop_uri: '',
		stop: function() {
			document.location = this.stop_uri.replace('%d', this.pos);
		}
	}, next;
	CoppermineSlideshow = cs;
	function preLoadImage(i) {
		if (!is_object(cs.pictures[i])) {
			var n = new Image();
			n.src = cs.pictures[i];
			cs.pictures[i] = n;
		}
	}
	function showImage() {
		var img = P.$('SlideShow');
		if (img) {
			if (!defined(next)) {
				next = cs.pos;
			} else {
				cs.pos = next;
				img.src = cs.pictures[next].src;
			}
			setTimeout(showImage, cs.delay);
			if (++next >= cs.pictures.length) { next = 0; }
			preLoadImage(next);
		}
	}
	P.onDOMReady(showImage);

	P.onDOMReady(function()
	{
		var n = P.$Q("input[name=userpicture]",1), i = P.$("userpicture_preview");
		if (n && i) {
			i.style.maxWidth = "100%";
			i.style.display = "block";
			n.on("change", function(){
				var f = this.files[0];
				if (/image\/(jpeg|gif|png)/.test(f.type)) {
					var r = new FileReader();
					r.onload = function(e) { i.src = e.target.result; };
					r.readAsDataURL(f);
				} else {
					i.src = null;
				}
			});
		}
	});
})(Poodle);
