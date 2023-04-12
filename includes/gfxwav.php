<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.com
  
  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/gfxwav.php,v $
  $Revision: 1.6 $
  $Author: nanocaiordo $
  $Date: 2007/12/30 07:38:53 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$wav = '';
if (isset($_GET['play']) && isset($CPG_SESS['gfx'][$_GET['play']])) {
	$wav = strtolower($CPG_SESS['gfx'][$_GET['play']]);
}
if (!strlen($wav)) { exit; }
if (is_dir(BASEDIR.'language/'.$currentlang.'/captcha')) {
	$dir = BASEDIR.'language/'.$currentlang.'/captcha/';
} else {
	$dir = BASEDIR.'language/english/captcha/';
}

$gaps = array(
	8=>chr(0x80),
	16=>chr(0x00).chr(0x80),
	24=>chr(0x00).chr(0x00).chr(0x80),
	32=>chr(0x00).chr(0x00).chr(0x00).chr(0x80)
);
$fields = implode('/',array('H8ChunkID', 'VChunkSize', 'H8Format',
                          'H8Subchunk1ID', 'VSubchunk1Size',
                          'vAudioFormat', 'vNumChannels', 'VSampleRate',
                          'VByteRate', 'vBlockAlign', 'vBitsPerSample'));
$riff = array();
//wave header                         /* +---------------------+ */
$riff['ChunkID'] = '';                /* | ChunkId             | */
$riff['ChunkSize'] = $bytes = 36;     /* | ChunkSize           | */
//wave -> type                        /* +---------------------+ */
$riff['Format'] = '';                 /* | Format              | */
// wave -> type -> header             /* | +-----------------+ | */
$riff['Subchunk1ID'] = '';            /* | | Subchunk1ID     | | */
$riff['Subchunk1Size'] = 0;           /* | | Subchunk1Size   | | */
//wave -> type -> info                /* | | +-------------+ | | */
$riff['AudioFormat'] = 0;             /* | | |AudioFormat  | | | */
$riff['NumChannels'] = 0;             /* | | |NumChannels  | | | */
$riff['SampleRate'] = 0;              /* | | |SampleRate   | | | */
$riff['ByteRate'] = 0;                /* | | |ByteRate     | | | */
$riff['BlockAlign'] = 0;              /* | | |BlockAlign   | | | */
$riff['BitsPerSample'] = 0;           /* | | |BitsPerSample| | | */
$riff['Extra'] = '';                  /* | | |Optional     | | | */
//wave -> data -> header              /* | | +-------------+ | | */
$riff['Subchunk2ID'] = '';            /* | | Subchunk2ID     | | */
$riff['Subchunk2Size'] = 0;           /* | | Subchunk2Size   | | */
//wave -> data -> samples             /* | | +-------------+ | | */
$riff['Data'] = '';                   /* | | |Data Samples | | | */
                                      /* | | +-------------+ | | */
                                      /* | +-----------------+ | */
                                      /* +---------------------+ */
$data = !$once = true;

for ($i=0;$i<strlen($wav);$i++) {
	$file = $dir.$wav[$i].'.wav';
	if (!$fp = fopen($file,'rb')) {
		$data = false;
		break;
	}
	$info = unpack($fields,fread($fp,36));
	if ($once) {
		$blocks = $info['BlockAlign']; //BitsPerSample/8 * NumChannels
		$bps = $info['BitsPerSample'];
		$riff['ChunkID'] = pack('H8',$info['ChunkID']);
		$riff['Format'] = pack('H8',$info['Format']);
		$riff['Subchunk1ID'] = pack('H8',$info['Subchunk1ID']);
		$riff['AudioFormat'] = pack('v',$info['AudioFormat']);
		$riff['NumChannels'] = pack('v',$info['NumChannels']);
		$riff['SampleRate'] = pack('V',$info['SampleRate']);
		$riff['ByteRate'] = pack('V',$info['ByteRate']);
		$riff['BlockAlign'] = pack('v',$info['BlockAlign']);
		$riff['BitsPerSample'] = pack('v',$info['BitsPerSample']);
	}
	if ($info['Subchunk1Size'] > 16) {
		$riff['Extra'] = fread($fp,($info['Subchunk1Size']-16));
		$bytes = ($bytes < 38) ? $bytes+($info['Subchunk1Size']-16) : $bytes;
	}
	if ($once) {
		$riff['Subchunk1Size'] = pack('V',$info['Subchunk1Size']);
		$once = !$data = true;
	}
	$riff['Subchunk2ID'] = fread($fp,4);
	$size  = unpack('vSize',fread($fp, 4));
	$riff['Data'][$i] = fread($fp,$size['Size']);
}

if ($data) {
	fclose($fp);
	$gap = str_repeat($gaps[$bps],2000*$blocks);
	$riff['Data'] = implode($gap,$riff['Data']);
	$datasize = strlen($riff['Data']);

	$riff['ChunkSize'] = pack('V',$datasize+$bytes);
	$riff['Subchunk2Size'] = pack('V',$datasize);
	$riff = implode('',$riff);
} else {
	$riff = 'RIFF'.pack('V',38).'WAVEfmt '.pack('l',281479271677970).pack('l',34359738376000).pack('l',524289).'data'; 
}
$datasize = strlen($riff);

header('Date: '.date('D, d M Y H:i:s', gmtime()).' GMT');
header('Last-Modified: '.date('D, d M Y H:i:s', gmtime()).' GMT');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Content-Length: '.$datasize);
header('Content-Type: audio/x-wav');
header('Connection: Close');
echo $riff;
exit;