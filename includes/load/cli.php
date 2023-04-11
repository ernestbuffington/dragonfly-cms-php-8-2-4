<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }

$_get = array();

$argv = array(
	0 => 'index.php',
	1 => 'file',
	2 => 'param',
	2 => 'key',
	3 => 'val'
);

class Dragonfly_Cli
{
	static public function readline($str)
	{
		if (PHP_OS == 'WINNT') {
			echo $str;
			return stream_get_line(STDIN, 1024, PHP_EOL);
		} else {
			return readline($str);
		}
	}

}

echo "Are you sure you want to do this?  Type 'yes' to continue: ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim($line) != 'yes'){
	echo "ABORTING!\n";
	exit;
}
echo "\n";
echo "Thank you, continuing...\n";

#$username = Dragonfly_Cli::readline('Username: ');
#echo $username;

array_shift($array);

if (is_dir(CORE_PATH. 'load/cli')) {
	$dir = new DirectoryIterator(CORE_PATH. 'load/cli');
	while ( $dir->valid() ) {
		if ( !$dir->isDot() && $file = $dir->current() && $array[1] === $file->getFilename() ) {
			$file = CORE_PATH. 'load/cli/'. $array[1];
			exec(`php -a `);
			$array = array_chunk($array, 2);
		}
		$dir->next();
	}
}

exit(0);
