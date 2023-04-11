<?php
if (class_exists('Dragonfly', false)) {
	$uri = 'News&file=archive';
	if (isset($_GET['sa'])) {
		$uri .= '&sa='.$_GET['sa'];
		if (isset($_GET['year'])) {
			$uri .= '&year='.$_GET['year'];
		}
		if (isset($_GET['month'])) {
			$uri .= '&month='.$_GET['month'];
		}
		if (isset($_GET['min'])) {
			$uri .= '&min='.$_GET['min'];
		}
	}
	\Poodle\URI::redirect(URL::index($uri), 301);
}
