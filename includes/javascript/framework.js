/**
* MOO CMS
* Copyright (c) 2005 by CPG-Nuke Dev Team, moocms.com
* $Id: framework.js,v 1.4 2007/06/10 22:41:14 nanocaiordo Exp $
*/

/* array creator */
var $A = Array.from = function(iterable) {
	if (!iterable) return [];
	if (iterable.toArray) {
		return iterable.toArray();
	} else {
		var results = [];
		for (var i = 0; i < iterable.length; ++i) results.push(iterable[i]);
		return results;
	}
}

/* function delegation */
Function.prototype.bind = function() {
	var __method = this, args = $A(arguments), object = args.shift();
	return function() { return __method.apply(object, args.concat($A(arguments))); }
}

/* Object extending. */
Object.prototype.extend = function(classname) {
//	this.prototype = new oSuper;
//	this.prototype.constructor = this;
	o = new classname;
	// copy properties
	for (sProperty in o) { this[sProperty] = o[sProperty]; }
}

/* xbrowser find event */
function getEvent(e) { return (e ? e : window.event); }
/* xbrowser find event fired element */
function getEventNode(e)
{
	if (!e)  { e = window.event; }
	if (e.nodeType && (e.nodeType == 1 || e.nodeType == 9)) return e; // NODE_ELEMENT or NODE_DOCUMENT
	var o = (e.currentTarget ? e.currentTarget : e.srcElement);
	//if (o.nodeType == 3) { o = (o.parentNode ? o.parentNode : o); } // Safari
	// must be a NODE_ELEMENT or NODE_DOCUMENT
	while (o && !(o.nodeType == 1 || o.nodeType == 9)) { o = o.parentNode; }
	return o;
}
/* xbrowser add event listener */
function addEvent(obj, type, fn) {
	if (obj.addEventListener) {
		obj.addEventListener(type, fn, false);
	} else if (obj.attachEvent) {
		obj.attachEvent('on'+type, fn);
	} else {
//		alert("Could not attach event to element.");
	}
}
function removeEvent(obj, type, fn)
{
	if (obj.removeEventListener) {
		obj.removeEventListener(type, fn, false);
	} else if (obj.detachEvent) {
		obj.detachEvent('on'+type, fn);
	}
}

/* short notation for getElementById */
function $(elementid) { return document.getElementById(elementid);}
/* elements array creator */
function $E()
{
	var elements = new Array();
	for (var i = 0; i < arguments.length; ++i)
	{
		var element = arguments[i];
		if (typeof element == 'string') element = document.getElementById(element);
		if (arguments.length == 1) return element;
		elements.push(element);
	}
	return elements;
}
/* find input element by name */
function getElementByName(o,name,tag)
{
	if (!tag) tag = 'input';
	var e = o.getElementsByTagName(tag);
	for (var i=0;i<e.length;++i) { if (e[i].name==name) return e[i]; }
	return 0;
}

function getElementLeft(o)
{
	var x=0, op=0;
	try { x = o.offsetLeft; } catch (e) {}
	try { op = o.offsetParent; } catch (e) {}
	while (op != null)
	{
		if (op.className == "tabcontent") return x;
		x += op.offsetLeft;
		if (op.scrollLeft>0) { x -= op.scrollLeft; }
  		op = op.offsetParent;
	}
	return x;
}
function getElementTop(o, stopat)
{
	var y=0, op=0;
	try { y = o.offsetTop; } catch (e) {}
	try { op = o.offsetParent; } catch (e) {}
	while (op != null)
	{
		if (stopat && op.className == stopat) return y;
		y += op.offsetTop;
		//if (op.scrollTop>0) { y -= op.scrollTop; }
		op = op.offsetParent;
	}
	return y;
}
function getPrevNode(o, same)
{
	var node = o.previousSibling;
	while (node)
	{
		if (!same || node.nodeName == o.nodeName) return node;
		node = node.previousSibling;
	}
	return 0;
}
function getNextNode(o, same)
{
	var node = o.nextSibling;
	while (node)
	{
		if (!same || node.nodeName == o.nodeName) return node;
		node = node.nextSibling;
	}
	return 0;
}
function getRChildNode(o, type, r, min)
{
	if (!min) min = r+1;
	var n = o.getElementsByTagName(type);
	return (n.length>=min) ? n[n.length-1-r] : null;
}
function insertAfter(parent, node, refNode)
{
	if (refNode.nextSibling)
		parent.insertBefore(node, refNode.nextSibling);
	else
		parent.appendChild(node);
}

function mousePos(e){
	return {
		x:(e.pageX?e.pageX:e.clientX+document.body.scrollLeft-(document.body.clientLeft?document.body.clientLeft:0)),
		y:(e.pageY?e.pageY:e.clientY+document.body.scrollTop -(document.body.clientTop?document.body.clientTop:0))
	};
}

/* CMS stuff */

function show_tab(o)
{
	o = o.parentNode;
	var items = o.parentNode.getElementsByTagName('li');
	for (var i=0; i<items.length; ++i) {
		if (items[i].className.indexOf(' current') > 0) {
			items[i].className=items[i].className.substr(0,items[i].className.indexOf(' current'));
		}
	}
	o.className += ' current';
}

function inputnumeral(e)
{
	e=getEvent(e);
	var kc = e.keyCode;
	getEventNode(e).onkeyup = inputnumeral;
	return (kc == 8 || kc == 46 || kc == 37 || kc == 39 || (!e.shiftKey && ((kc >= 48 && kc <= 57) || (kc >= 96 && kc <= 105))));
}

function switch_title(obj)
{
	obj.title = obj.options[obj.selectedIndex].title;
}

function expand(sender, o)
{
	o = $(o);
	if (!o) return;
	if (o.style.visibility == 'collapse' || o.style.display == 'none') {
		if (o.style.visibility == 'collapse') { o.style.visibility = 'visible'; }
		else { o.style.display = ''; }
		sender.className = sender.className.replace('collapsed', 'expanded');
	} else {
		try { o.style.visibility = 'collapse'; }
		catch (e) { o.style.display = 'none'; }
		sender.className = sender.className.replace('expanded', 'collapsed');
	}
}

var msgBox = {
	box : null,
	loading : function(msg) { return msgBox._new(msg, 'loading'); },
	error : function(msg) { return msgBox._new(msg, 'error'); },
	ok : function(msg) { return msgBox._new(msg, 'ok'); },
	_new : function(msg, type)
	{
		if (!msgBox.box) { msgBox.box = $('messagesbox'); }
		var item = document.createElement('div');
		item.className = type;
		item.innerHTML = msg;
		item.onclick   = msgBox.remove.bind(this);
		msgBox.box.appendChild(item);
		msgBox.box.style.visibility = 'visible';
		return item;
	},
	remove : function(e)
	{
		if (!msgBox.box) { msgBox.box = $('messagesbox'); }
		try {
			msgBox.box.removeChild(getEventNode(e));
		} catch (e) {}
		if (!msgBox.box.hasChildNodes()) { msgBox.box.style.visibility = 'hidden'; }
	}
}

