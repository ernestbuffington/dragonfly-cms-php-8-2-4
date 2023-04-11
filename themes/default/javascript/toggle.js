/**
  Copyright (c) 2011 by DragonflyCMS
  Released under GNU GPL version 2 or any later version
*/

var togglesClassName  = "toggle";
var togglesCookieName = "toggle";
var togglesImgCloseIt = "minus";
var togglesImgOpenIt  = "plus";

var Toggle = function () {
	var init = function() {
		if (!self.test(togglesCookieName) || !self.test(togglesCookieName)
			|| !self.test(togglesImgCloseIt) || !self.test(togglesImgOpenIt)) {
			return;
		}
		var hiddensC, toggles;
		if (hiddensC = new String(unescape(Poodle.getCookie(togglesCookieName)))) {
			if (!self.test(hiddensC, "\:")) { hiddensC = ''; }
			if (hiddensC) { self.hiddens = hiddensC.split(":"); }
		}
		if (toggles = Poodle.$Q('.'+togglesClassName)) {
			for (var i = 0; i < toggles.length; i++) {
				toggles[i].on("click", Toggle.it);
				var o = Poodle.$(toggles[i].id.replace(togglesClassName, ""));
				if (0 <= self.hiddens.indexOf(o.id)) {
					o.hasClass("toggled") ? self.expand(o) : self.collapse(o);
				} else if (o.hasClass("toggled")) {
					self.collapse(o);
				}
				toggles[i] = null;
			}
		}
		hiddensC = '';
	};
	var it = function(e) {
		var o = Poodle.$(e.currentTarget.id.replace(togglesClassName, ""));
		if (!o.style.display) { self.collapse(o); }
		else { self.expand(o); }
		self.setCookie();
	};
	var self = {
		hiddens: new Array(),
		test: function(str, more) {
			var reg = new RegExp("^[a-z][a-z0-9_"+more+"\-]+$", "i");
			return reg.test(str);
		},
		setCookie: function() {
			Poodle.setCookie(togglesCookieName, self.hiddens.join(":"),0);
		},
		collapse: function(o) {
			o.style.display='none';
			self.rep(Poodle.$(togglesClassName+o.id), togglesImgCloseIt, togglesImgOpenIt);
			if (-1 == self.hiddens.indexOf(o.id) && !o.hasClass("toggled")) {
				if ('null' == self.hiddens) self.hiddens = new Array();
				self.hiddens.push(o.id);
			} else if (0 <= self.hiddens.indexOf(o.id) && o.hasClass("toggled")) {
				self.hiddens.splice(self.hiddens.indexOf(o.id), 1);
			}
		},
		expand: function(o) {
			o.style.display='';
			self.rep(Poodle.$(togglesClassName+o.id), togglesImgOpenIt, togglesImgCloseIt);
			if (o.hasClass("toggled")) {
				if (-1 == self.hiddens.indexOf(o.id)) { self.hiddens.push(o.id); }
			} else {
				self.hiddens.splice(self.hiddens.indexOf(o.id), 1);
			}
		},
		rep: function(o, from, to) {
			if ("img" == o.nodeName.toLowerCase()) {
				o.src = o.src.replace(from, to);
			} else if (to == togglesImgOpenIt) {
				o.addClass('expand');
			} else {
				o.removeClass('expand');
			}
		}
	};
	return {
		init: init,
		it: it
	};
}();

Poodle.onDOMReady(Toggle.init);
