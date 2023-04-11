<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Social;

abstract class Plugins
{

	static public
		# into database and save 10KiB
		$data = array(
			/*
			'adfty' => 'Adfty', # http://www.adfty.net/submit.php?url=%1s&title=%2s
			'allvoices' => 'Allvoices',
			'amazon_wishlist' => 'Amazon Wishlist',
			'arto' => 'Arto',
			'baidu' => 'Baidu',
			'blinklist' => 'Blinklist',
			'blip' => 'Blip',
			'funp' => array(
				'n' => 'Funp',
				'shareUrl' => 'http://funp.com.tw/push/submit/add.php?title=%2s&url%1s&via= 302-> http://funp.com.tw/push/submit/?title=&url=&via='),
			'mixx' => 'Mixx', http://www.mixx.com/submit?page_url=%1s 301-> http://chime.in/?utm_source=seo&utm_medium=button&utm_campaign=mixx
			'moshare' => 'moShare', # http://www.mogreet.com/moshare/it/?share=%1s&content_url=&media_type=article&title=%2s&src=&thumbnail=%4s&message=&from_name=&description=%3s&cid=&channel=website
			'nujij' => 'NUjij', # http://www.nujij.nl/nieuw-bericht.2051051.lynkx?url=%1s&title=%2s
			'startlap' => array(
				'n' => 'Startlap',
				'shareUrl' => ''),
			'wordpress' => 'WordPress',
			*/
			'bebo' => array(
				'n' => 'Bebo',
				'b' => 'http://www.bebo.com/c/share?',
				'u' => 'Url',
				't' => 'Title'),
			'blogger' => array(
				'n' => 'Blogger',
				'b' => 'http://www.blogger.com/blog_this.pyra?',
				'u' => 'u',
				't' => 'n',
				'd' => 't'),
			'blogmarks' => array(
				'n' => 'Blogmarks',
				'b' => 'http://blogmarks.net/my/new.php?mini=1',
				'u' => 'url',
				't' => 'title'),
			'brainify' => array(
				'n' => 'Brainify',
				'b' => 'http://www.brainify.com/Bookmark.aspx?',
				'u' => 'url',
				't' => 'title'),
			'buddymarks' => array(
				'n' => 'BuddyMarks',
				'b' => 'http://buddymarks.com/add_bookmark.php?',
				'u' => 'bookmark_url',
				't' => 'bookmark_title',
				'd' => 'bookmark_desc'),
			'buffer' => array(
				'n' => 'Buffer',
				'b' => 'http://bufferapp.com/add/?',
				'u' => 'url',
				'd' => 'text'),
			'bus_exchange' => array(
				'n' => 'Bus Exchange',
				'b' => 'http://bx.businessweek.com/api/add-article-to-bx.tn?',
				'u' => 'url'),
			'care2' => array(
				'n' => 'Care2',
				'b' => 'http://www.care2.com/news/compose?',
				'u' => 'share[url]',
				't' => 'share[title]'),
			'chiq' => array(
				'n' => 'Chiq',
				'b' => 'http://www.chiq.com/create/affiliate?',
				'u' => 'url',
				't' => 'title',
				'd' => 'description'),
			'citeulike' => array(
				'n' => 'CiteULike',
				'b' => 'http://www.citeulike.org/posturl?',
				'u' => 'url',
				't' => 'title'),
			'corank' => array(
				'n' => 'coRank',
				'b' => 'http://www.corank.com/submit?',
				'u' => 'url',
				't' => 'title',
				'r' => 'source'),
			'corkboard' => array(
				'n' => 'Corkboard',
				'b' => 'http://www.corkboard.it/posting/create?',
				'u' => 'posting[url]',
				't' => 'posting[title]',
				'd' => 'posting[description]'),
			'current' => array(
				'n' => 'Current TV',
				'b' => 'http://current.com/clipper.htm?',
				'u' => 'url',
				't' => 'title',
				'r' => 'src'),
			'dealsplus' => array(
				'n' => 'Dealspl.us',
				'b' => 'http://dealspl.us/add.php?ibm=1',
				'u' => 'url'),
			'delicious' => array(
				'n' => 'Delicious',
				'b' => 'https://delicious.com/save?',
				'u' => 'url',
				't' => 'title'),
			'digg' => array(
				'n' => 'Digg',
				'b' => 'http://digg.com/submit?phase=2',
				'u' => 'url',
				't' => 'title'),
			'diigo' => array(
				'n' => 'Diigo',
				'b' => 'http://secure.diigo.com/post?',
				'u' => 'url',
				't' => 'title'),
			'dotnetshoutout' => array(
				'n' => '.net Shoutout',
				'b' => 'http://dotnetshoutout.com/Submit?',
				'u' => 'url',
				't' => 'title'),
			'dzone' => array(
				'n' => 'DZone',
				'b' => 'http://www.dzone.com/links/add.html?',
				'u' => 'url',
				't' => 'title'),
			'edmodo' => array(
				'n' => 'Edmodo',
				'b' => 'http://www.edmodo.com/home?share=1',
				'r' => 'source',
				'u' => 'url',
				't' => 'desc'),
			'evernote' => array(
				'n' => 'Evernote',
				'b' => 'http://www.evernote.com/clip.action?',
				'u' => 'url',
				't' => 'title'),
			'facebook' => array(
				'n' => 'Facebook',
				'b' => 'https://www.facebook.com/sharer.php?',
				'u' => 'u'),
			'fark' => array(
				'n' => 'Fark',
				'b' => 'http://www.fark.com/cgi/farkit.pl?',
				'u' => 'u',
				't' => 'h'),
			'fashiolista' => array(
				'n' => 'Fashiolista',
				'b' => 'http://www.fashiolista.com/item_add/?source=Dbookmarklet&version=1.1',
				'u' => 'url'),
			'folkd' => array(
				'n' => 'folkd.com',
				'b' => 'http://www.folkd.com/page/social-bookmarking.html?',
				'u' => 'addurl'),
			'formspring' => array(
				'n' => 'Formspring',
				'b' => 'http://www.formspring.me/share?',
				'u' => 'url',
				't' => 'title'),
			'fresqui' => array(
				'n' => 'Fresqui',
				'b' => 'http://fresqui.com/enviar?',
				'u' => 'url',
				't' => 'title'),
			'friendfeed' => array(
				'n' => 'FriendFeed',
				'b' => 'http://friendfeed.com/share?',
				'u' => 'url',
				't' => 'title'),
			'fwisp' => array(
				'n' => 'fwisp',
				'b' => 'http://fwisp.com/submit?',
				'u' => 'url'),
			'google' => array(
				'n' => 'Google Home/Reader',
				'b' => 'http://www.google.com/ig/add?',
				'u' => 'feedurl',
				't' => 'feedtitle'),
			'google_bmarks' => array(
				'n' => 'Google Bookmarks',
				'b' => 'https://www.google.com/bookmarks/mark?op=edit',
				'u' => 'bkmk',
				't' => 'title'),
			'googleplus' => array(
				'n' => 'Google+',
				'b' => 'https://plus.google.com/share?',
				'u' => 'url'),
			'hatena' => array(
				'n' => 'Hatena',
				'b' => 'http://b.hatena.ne.jp/add?mode=confirm',
				'u' => 'url',
				't' => 'title'),
			'hyves' => array(
				'n' => 'Hyves',
				'b' => 'http://www.hyves.nl/profilemanage/add/tips/?type=12',
				'u' => 'text',
				't' => 'name'),
			'identi' => array(
				'n' => 'identi.ca',
				'b' => 'http://identi.ca/index.php?action=bookmarkpopup',
				'u' => 'url',
				't' => 'title'),
			'instapaper' => array(
				'n' => 'Instapaper',
				'b' => 'http://www.instapaper.com/hello2?',
				'u' => 'url',
				't' => 'title',
				'd' => 'description'),
			'jumptags' => array(
				'n' => 'Jumptags',
				'b' => 'http://www.jumptags.com/add/?',
				'u' => 'url'),
			'kaboodle' => array(
				'n' => 'Kaboodle',
				'b' => 'http://www.kaboodle.com/za/selectpage?p_pop=true&pa=url',
				'u' => 'u'),
			'linkagogo' => array(
				'n' => 'linkaGoGo',
				'b' => 'http://www.linkagogo.com/go/AddMenu?',
				'u' => 'url',
				't' => 'title'),
			'linkedin' => array(
				'n' => 'LinkedIn',
				'b' => 'https://www.linkedin.com/shareArticle?mini=true',
				'u' => 'url'),
			'livejournal' => array(
				'n' => 'LiveJournal',
				'b' => 'http://www.livejournal.com/update.bml?',
				'u' => 'event',
				't' => 'subject'),
			'mail_ru' => array(
				'n' => 'mail.ru',
				'b' => 'http://connect.mail.ru/share?',
				'u' => 'share_url'),
			'meneame' => array(
				'n' => 'Meneame',
				'b' => 'http://www.meneame.net/submit.php?',
				'u' => 'url'),
			'messenger' => array(
				'n' => 'Messenger',
				'b' => 'https://profile.live.com/badge?',
				'u' => 'url'),
			'mister_wong' => array(
				'n' => 'Mr Wong',
				'b' => 'http://www.mister-wong.com/index.php?action=addurl',
				'u' => 'bm_url',
				't' => 'bm_description'),
			'myspace' => array(
				'n' => 'MySpace',
				'b' => 'http://www.myspace.com/Modules/PostTo/Pages/default.aspx?l=3',
				'u' => 'u',
				't' => 't',
				'd' => 'c'),
			'n4g' => array(
				'n' => 'N4G',
				'b' => 'http://n4g.com/tips?',
				'u' => 'url',
				't' => 'title'),
			'netlog' => array(
				'n' => 'Netlog',
				'b' => 'http://en.netlog.com/go/manage/blog/view=add&origin=external',
				'u' => 'message',
				't' => 'title'),
			'netvouz' => array(
				'n' => 'Netvouz',
				'b' => 'http://www.netvouz.com/action/submitBookmark?popup=no',
				'u' => 'url',
				't' => 'title'),
			'newsvine' => array(
				'n' => 'Newsvine',
				'b' => 'http://www.newsvine.com/_tools/seed&save?popoff=0',
				'u' => 'u',
				't' => 'h'),
			'odnoklassniki' => array(
				'n' => 'Odnoklassniki',
				'b' => 'http://www.odnoklassniki.ru/dk?st.cmd=addShare&st.s=1',
				'u' => 'st._surl'),
			'oknotizie' => array(
				'n' => 'Oknotizie',
				'b' => 'http://oknotizie.virgilio.it/post?',
				'u' => 'url',
				't' => 'title'),
			'orkut' => array(
				'n' => 'Orkut',
				'b' => 'http://promote.orkut.com/preview?nt=orkut.com',
				'u' => 'du',
				't' => 'tt'),
			'pinterest' => array(
				'n' => 'Pinterest',
				'b' => 'https://pinterest.com/pin/create/button/?',
				'u' => 'url',
				't' => 'description',
				'd' => 'media'),
			'raise_your_voice' => array(
				'n' => 'Raise Your Voice',
				'b' => 'https://action.raiseyourvoice.us/?',
				'u' => 'url',
				't' => 'title'),
			'reddit' => array(
				'n' => 'Reddit',
				'b' => 'http://reddit.com/submit?',
				'u' => 'url',
				't' => 'title'),
			'segnalo' => array(
				'n' => 'Segnalo',
				'b' => 'http://segnalo.virgilio.it/post.html.php?',
				'u' => 'url',
				't' => 'title',
				'd' => 'descr'),
			'sina' => array(
				'n' => 'Sina',
				'b' => 'http://service.weibo.com/share/share.php?',
				'u' => 'title'),
			'slashdot' => array(
				'n' => 'Slashdot',
				'b' => 'http://slashdot.org/submission?',
				'u' => 'url',
				't' => 'title'),
			'sonico' => array(
				'n' => 'Sonico',
				'b' => 'http://www.sonico.com/share.php?',
				'u' => 'url',
				't' => 'title'),
			'speedtile' => array(
				'n' => 'Speedtile',
				'b' => 'http://www.speedtile.net/api/add/?',
				'u' => 'u',
				't' => 't'),
			'startaid' => array(
				'n' => 'Startaid',
				'b' => 'http://www.startaid.com/index.php?st=AddBrowserLink&type=Detail',
				'u' => 'urlname',
				't' => 'urltitle'),
			'stumbleupon' => array(
				'n' => 'StumbleUpon',
				'b' => 'http://www.stumbleupon.com/submit?',
				'u' => 'url',
				't' => 'title'),
			'stumpedia' => array(
				'n' => 'Stumpedia',
				'b' => 'http://www.stumpedia.com/submit?',
				'u' => 'url'),
			'technorati' => array(
				'n' => 'Technorati',
				'b' => 'http://technorati.com/faves?',
				'u' => 'add'),
			'tumblr' => array(
				'n' => 'Tumblr',
				'b' => 'http://www.tumblr.com/share?v=3',
				'u' => 'u',
				't' => 't',
				'd' => 's'),
			'twitter' => array(
				'n' => 'Twitter',
				'b' => 'https://twitter.com/intent/tweet?',
				'u' => 'url',
				't' => 'text'),
			'typepad' => array(
				'n' => 'TypePad',
				'b' => 'http://www.typepad.com/services/quickpost/post?v=2&qp_show=ac',
				'u' => 'qp_href',
				't' => 'qp_title',
				'd' => 'qp_text'),
			'viadeo' => array(
				'n' => 'Viadeo',
				'b' => 'http://www.viadeo.com/shareit/share/?', # 302-> http://www.viadeo.com/?&url=%1s&title=%2s
				'u' => 'url',
				't' => 'title'),
			'virb' => array(
				'n' => 'Virb',
				'b' => 'http://virb.com/share?',
				'u' => 'url',
				't' => 'title'),
			'vkontakte' => array(
				'n' => 'Vkontakte',
				'b' => 'http://vk.com/share.php?',
				'u' => 'url',
				't' => 'title',
				'd' => 'description',
				'i' => 'image'),
			'voxopolis' => array(
				'n' => 'VOXopolis',
				'b' => 'http://www.voxopolis.com/oexchange/?',
				'u' => 'url'),
			'xanga' => array(
				'n' => 'Xanga',
				'b' => 'http://www.xanga.com/private/editorx.aspx?',
				'u' => 'u',
				't' => 't',
				'd' => 's'),
			'xerpi' => array(
				'n' => 'Xerpi',
				'b' => 'http://www.xerpi.com/favorite/post?',
				'u' => 'url',
				't' => 'title'),
			'xing' => array(
				'n' => 'Xing',
				'b' => 'https://www.xing.com/social_plugins/share/new_vb?h=1',
				'u' => 'url'),
			'yammer' => array(
				'n' => 'Yammer',
				'b' => 'https://www.yammer.com/home/bookmarklet?bookmarklet_pop=1',
				'u' => 'u',
				't' => 't'),
			'yigg' => array(
				'n' => 'Yigg',
				'b' => 'http://yigg.de/neu?',
				'u' => 'exturl',
				't' => 'exttitle')
			);
}
