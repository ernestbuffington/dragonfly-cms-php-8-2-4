<?php

$LNG['Poodle\\Input\\File\\Errors'] = array(
UPLOAD_ERR_INI_SIZE   => 'Filesize exceeds the upload_max_filesize directive in php.ini, which is set to: %s',
UPLOAD_ERR_FORM_SIZE  => 'Filesize exceeds the MAX_FILE_SIZE directive that was specified in the html form',
UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',

\Poodle\Input\File::ERR_NO_UPLOAD_FILE => 'Not an uploaded file',
\Poodle\Input\File::ERR_FILE_EMPTY     => 'File is empty',
\Poodle\Input\File::ERR_TMP_UNREADABLE => 'Temporary file is not readable and failed to move it to tmp',
\Poodle\Input\File::ERR_FILE_INVALID   => 'Failed to match the file type (%s) with any of the accepted file types',
\Poodle\Input\File::ERR_ACCESS_FAILED  => 'Target directory "%s" is missing or not writable',
\Poodle\Input\File::ERR_MOVE_FAILED    => 'Failed to move file',
\Poodle\Input\File::ERR_ALREADY_MOVED  => 'File was already moved',
);
