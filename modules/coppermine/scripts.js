function imgpop(target,title,options)
{
	var mywin;
	mywin = window.open(target,title,options);
}
function writeCookie(name, data, noDays){
  var cookieStr = name + "="+ data
  if (writeCookie.arguments.length > 2){
    cookieStr += "; expires=" + getCookieExpireDate(noDays)
    }
  document.cookie = cookieStr
}

function blocking(nr, cookie, vis_state)
{
    if (document.layers)
    {
        current = (document.layers[nr].display == 'none') ? vis_state : 'none';
        if (cookie != '')
            writeCookie(nr, current);
        document.layers[nr].display = current;
    }
    else if (document.all)
    {
        current = (document.all[nr].style.display == 'none') ? vis_state : 'none';
        if (cookie != '')
            writeCookie(nr, current);
        document.all[nr].style.display = current;
    }
    else if (document.getElementById)
    {
        display = (document.getElementById(nr).style.display == 'none') ? vis_state : 'none';
        if (cookie != '')
            writeCookie(nr, display);
        document.getElementById(nr).style.display = display;
    }
}
