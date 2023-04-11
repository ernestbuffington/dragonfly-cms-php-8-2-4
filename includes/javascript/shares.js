/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	@import "poodle"
*/

(function(){

function SocialShare(){

	if (arguments[0]) {
		initNode(arguments[0]);
	} else {
		init();
	}

	var container;

	function init()
	{
		var nodes = Poodle.$Q('.share'), i = nodes.length;
		while (i--) { initNode(nodes[i]); }
	}

	function initNode(node)
	{
		if (node.firstChild) {
			node.socialShareData = JSON.parse(node.firstChild.nodeValue);
			node.firstChild.nodeValue = '';
			node.removeClass("hidden");
			node.$A(["div", {"class":"shareimage",onclick:function(){openShares(node);}}]);
		}
	}

	function openShares(node)
	{
		var uri, o, sharer, iframedata;
		container = Poodle.$B().$A(
			["div",{"class":"windowbg"},
				["div",{"class":"vertical"},
					["div",{"class":"window"},
						["div",{"class":"inner"},
							["div", {"class":"header",innerHTML:"Be sociable, share it!"},
								["a", {"class":"close",title:"Close",onclick:function(){Poodle.$B().removeChild(container);}}]
							],
							["div", {ref:"body","class":"body"}]
						]
					]
				]
			]
		);
		for (sharer in SHARERS)
		{
			o = SHARERS[sharer];
			uri = o.b.addQueryParam(o.u, node.socialShareData.u);
			if (defined(o.t)) {  uri = uri.addQueryParam(o.t, node.socialShareData.t); }
			if (defined(o.d)) {  uri = uri.addQueryParam(o.d, node.socialShareData.d); }
			if (defined(o.i)) {  uri = uri.addQueryParam(o.i, node.socialShareData.i); }
			if (defined(o.r)) {  uri = uri.addQueryParam(o.r, node.socialShareData.r); }
			container.body.$A(["a", {"class":"a-"+sharer+"",innerHTML:o.n,"uri":uri,onclick:function(){shareit(this.uri);}}]);
		}
	}

	function shareit(uri)
	{
		if (defined(window.sharewindow)) {
			window.sharewindow.src = uri;
		} else {
			window.open(uri, "sharewindow", "width=650,height=500", true);
		}
	}
}
Poodle.onDOMReady(function(){SocialShare();});
})();
