//
// Bubblehelp infoboxes, (C) 2002 Klaus Knopper <infobox@knopper.net>
// You can copy/modify and distribute this code under the conditions
// of the GNU GENERAL PUBLIC LICENSE Version 2.
//
var IWIDTH=250;  /** Tip box width */
var ie5;         /** Are we using Internet Explorer Version 5 and up? */
var kon;         /** Are we using KDE Konqueror? */
var x,y,winW,winH;  /** Current help position and main window size */
var idiv=null;   /** Pointer to infodiv container */

function rebrowse(){window.location.reload();}

function infoinit(){
	ie5=(document.all&&(navigator.userAgent.indexOf('MSIE')>0)&&!(navigator.userAgent.indexOf('Opera')>0))?true:false;
	kon=(navigator.userAgent.indexOf('konqueror')>0)?true:false;
	x=0;y=0;winW=800;winH=600;
	idiv=null;
	document.onmousemove = mousemove;
	// Workaround for just another netscape bug: Fix browser confusion on resize
	// obviously konqueror has a similar problem :-(
	if(kon){ setTimeout("window.onresize = rebrowse", 2000); }
}

function untip(){
	if(idiv) { idiv.visibility='hidden'; }
	idiv=null;
	return false;
}

// Prepare tip boxes, but don't show them yet
function maketip(name,title,text){
	document.write('<div class="tooltip-box" id="'+name+'" name="'+name+'" style="position:absolute; visibility:hidden; z-index:20; top:0px; left:0px; width:'+IWIDTH+'px"><div class="helphead">'+title+'</div><div class="helpcontent">'+text+'</div></div>'+"\n");
}

function tip(name){
	if(idiv) { untip(); }
	idiv=(document.all&&document.all[name]&&document.all[name].style)?document.all[name].style:document[name]?document[name]:(document.getElementById(name)?document.getElementById(name).style:0);
	if(idiv){
		winW=(window.innerWidth)? window.innerWidth+window.pageXOffset-20:document.body.offsetWidth-24;
		winH=(window.innerHeight)?window.innerHeight+window.pageYOffset  :document.body.offsetHeight;
		if(x<=0||y<=0){ /** konqueror can't get mouse position */
			x=(winW-IWIDTH)/2+(window.pageXOffset?window.pageXOffset:0);
			y=(winH-50)/2+(window.pageYOffset?window.pageYOffset:0); /** middle of window */
		}
		showtip();
	}
	return false;
}

function showtip(){
	idiv.left=(((x+260)<winW)?x:x-255)+"px";
	idiv.top=(((y+90)<winH)?y+12:y-90)+"px";
	idiv.visibility='visible';
}

function mousemove(e){
	e=e||event;
	if (e) {
		x=e.pageX||e.clientX||0;
		y=e.pageY||e.clientY||0;
	}
	if (ie5 && document.documentElement) {
		x+=document.documentElement.scrollLeft;
		y+=document.documentElement.scrollTop;
	}
	if (idiv) { showtip(); }
}

// Initialize after loading the page
window.onload=infoinit;
