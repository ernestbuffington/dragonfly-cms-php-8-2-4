<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
namespace Dragonfly\Social\Adapter;

class Opengraph extends \Dragonfly\Social\Adapter
{

	protected
		$images = array(),
		$install = array(
			'active' => 0,
			'type' => 'website'
		);

	function __construct()
	{
		parent::init('Opengraph');

		$this->addImage(BASEHREF.'images/'.parent::$MAIN_CFG->global->site_logo);

		//Poodle.onDOMReady( function() {
			// Poodle.$Q("html",1).attr("prefix","http://ogp.me/ns#");
		//});
	}

	public function htmlHeadTags()
	{
		# required
		\Dragonfly\Page::metatag('og:url', BASEHREF.\URL::canonical(), 'property');
		\Dragonfly\Page::metatag('og:title', \Dragonfly\Page::get('title'), 'property');
		\Dragonfly\Page::metatag('og:type', $this->type, 'property');
		\Dragonfly\Page::metatag('og:description', \Dragonfly\Page::get('description'), 'property');

		foreach ($this->images as $v) {
			\Dragonfly\Page::metatag('og:image', $v, 'property');
		}
		# optionals
		\Dragonfly\Page::metatag('og:site_name',  parent::$MAIN_CFG->global->sitename, 'property');
	}

	public function addImage($img)
	{
		array_unshift($this->images, $img);
	}

	public function loadApi() {}
	public function html5Button(){}
}
