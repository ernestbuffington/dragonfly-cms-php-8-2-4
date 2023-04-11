Poodle.onDOMReady(function()
{
	var xhr = new PoodleXHR();
	var uname = Poodle.$("register-username");
	var email = Poodle.$("register-email");
	if (uname) {
		uname.img = uname.parent().$A("i");
		uname.img.className = "iconok hidden";
		uname.label = uname.parent().$A("label");
		uname.select();
		uname.on("blur", function(){
			if (uname.value) {
				xhr.oncomplete = onCompleteUname;
				xhr.post(uname.form.action, "validate_username="+encodeURIComponent(uname.value));
			}
		});
	}
	if (email) {
		email.img = email.parent().$A("i");
		email.img.className = "iconerror hidden";
		email.label = email.parent().$A("label");
		email.on("blur", function(){
			if (email.value) {
				xhr.oncomplete = onCompleteEmail;
				xhr.post(email.form.action, "validate_email="+encodeURIComponent(email.value));
			}
		});
	}
	function onCompleteUname(pxhr)
	{
		var ok = 200 == pxhr.xhr.status;
		uname.removeClass(ok ? "error" : "ok");
		uname.addClass(ok ? "ok" : "error");
		uname.img.className = ok ? "iconok" : "icondelete";
		uname.label.className = ok ? "ok" : "error";
		uname.label.html(pxhr.xhr.responseText);
	}
	function onCompleteEmail(pxhr)
	{
		var ok = 200 == pxhr.xhr.status;
		email.removeClass(ok ? "error" : "ok");
		email.addClass(ok ? "ok" : "error");
		email.img.className = ok ? "iconok" : "icondelete";
		email.label.className = ok ? "ok" : "error";
		email.label.html(pxhr.xhr.responseText);
	}
});
