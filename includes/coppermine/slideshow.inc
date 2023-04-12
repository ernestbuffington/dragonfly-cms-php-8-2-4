<?php $slideshow = intval($_GET['slideshow']) ? $_GET['slideshow'] : cpg_die(_ERROR,sprintf(_ERROR_NOT_SET,'slideshow'));?> 
<!-- Coppermine Photo Gallery 1.3.1 for CPG-Nuke-->
<script language="JavaScript" type="text/javascript">
// (C) 2000 www.CodeLifter.com
// http://www.codelifter.com
// Free for all users, but leave in this  header
// NS4-6,IE4-6
// Fade effect only in IE; degrades gracefully
// Set slideShowSpeed (milliseconds) 
var slideShowSpeed = <?php echo $slideshow ?> 
// Duration of crossfade (seconds)
var crossFadeDuration = 3
 
// Specify the image files
var Pic = new Array() // don't touch this
// to add more images, just continue
// the pattern, adding to the array below
<?php
global $CPG_URL;
$i = 0;
$j = 0;
$pid = intval($_GET['pid']) ? $_GET['pid'] : cpg_die(_ERROR,sprintf(_ERROR_NOT_SET,'pid'));
$start_img = '';
$album = (isset($_GET['album'])&& $_GET['album']!='') ? $_GET['album'] : NULL;
$meta = (isset($_GET['meta'])&& $_GET['meta']!='') ? $_GET['meta'] : NULL;
$thisalbumlink = '';
if (isset($album)) {
    $thisalbumlink = "&album=$album";
}else {
    $thisalbumlink = "&meta=$meta";
}
$pic_data = get_pic_data($_GET['meta'], $_GET['album'], $pic_count, $album_name, -1, -1, false);
foreach ($pic_data as $picture) {
    if (($CONFIG['make_intermediate'] && max($picture['pwidth'], $picture['pheight']) > $CONFIG['picture_width'])&&(!$CONFIG['fullsize_slideshow'] )) {
        $picture_url = get_pic_url($picture, 'normal');
    } else {
        $picture_url = get_pic_url($picture, 'fullsize');
    } 

    echo "Pic[$i] = '" . $picture_url . "'\n";
    if ($picture['pid'] == $pid) {
        $j = $i;
        $start_img = $picture_url;
    } 
    $i++;
} 

?> 
var t;
var j = <?php echo "$j;\n" ?>
var p = Pic.length;
var pos = j;
 
var preLoad = new Array()
 
function preLoadPic(index)
{
	if (Pic[index] != ''){
		window.status='Loading : '+Pic[index];
		preLoad[index] = new Image();
		preLoad[index].src = Pic[index];
		Pic[index] = '';
		window.status='';
	}
}

function runSlideShow(){
	if (document.all){
		document.images.SlideShow.style.filter="blendTrans(duration=2)"
		document.images.SlideShow.style.filter= "blendTrans(duration=crossFadeDuration)"
		document.images.SlideShow.filters.blendTrans.Apply()
	}
	document.images.SlideShow.src = preLoad[j].src
	if (document.all){
		document.images.SlideShow.filters.blendTrans.Play()
	}
	pos = j;
	j = j + 1;
	if (j > (p-1)) j=0;
	t = setTimeout('runSlideShow()', slideShowSpeed);
	preLoadPic(j);
}

function endSlideShow(){
	self.document.location = '<?php echo getlink("&file=displayimage$thisalbumlink&cat=".(isset($_GET['cat']) ? $_GET['cat'] : '0').'&pos=\'+pos+\'', true, true)."'"; ?>
}

preLoadPic(j)
</script>
