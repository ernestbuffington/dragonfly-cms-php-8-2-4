<?php
global $CONFIG;
$CPG = \Coppermine::getInstance();
$slideshow = $_GET->uint('slideshow') ?: cpg_error(sprintf(_ERROR_NOT_SET,'slideshow'), 404);
$pid = $_GET->uint('pid') ?: cpg_error(sprintf(_ERROR_NOT_SET,'pid'), 404);
$album = $_GET->uint('album');
$meta = $_GET->text('meta');
$pic_data = get_pic_data($meta, $album, $pic_count, $album_name, 0, 0);
$start_img = '';
$pictures = array();
$j = 0;
if ($pic_data) {
	$intermediate = $CONFIG['make_intermediate'] && !$CONFIG['fullsize_slideshow'];
	foreach ($pic_data as $i => $picture) {
		if ($intermediate && max($picture['pwidth'], $picture['pheight']) > $CONFIG['picture_width']) {
			$picture_url = get_pic_url($picture, 'normal');
		} else {
			$picture_url = get_pic_url($picture, 'fullsize');
		}
		$pictures[] = $picture_url;
		if ($picture['pid'] == $pid) {
			$j = $i;
			$start_img = $picture_url;
		}
	}
}

\Dragonfly\Output\Js::inline('
CoppermineSlideshow.pictures = ' . \Dragonfly::dataToJSON($pictures) . ';
CoppermineSlideshow.pos = ' . $j .';
CoppermineSlideshow.stop_uri = \'' . $CPG->buildUrl('displayimage', array('album' => $album, 'cat' => $_GET->uint('cat'), 'meta' => $meta, 'pos' => '%d')) .'\';
');

pageheader(SLIDESHOW_TITLE);
$OUT = \Dragonfly::getKernel()->OUT;
$OUT->slideshow = array(
	'image' => $start_img,
	'height' => $CONFIG['picture_width'] + 100,
);
echo $OUT->toString('coppermine/slideshow');
pagefooter();
