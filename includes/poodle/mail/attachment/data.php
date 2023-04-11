<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Mail\Attachment;

class Data extends \Poodle\Mail\Attachment
{
	protected
		$data;

	/**
	 * Adds a string or binary attachment (non-filesystem) to the list.
	 * This method can be used to attach ascii or binary data,
	 * such as a BLOB record from a database.
	 * @param string $string    String attachment data.
	 * @param string $name      Name of the attachment.
	 * @param string $mime_type File extension (MIME) type.
	 */
	function __construct($owner, $string, $name, $mime_type=null)
	{
		$this->name      = $name;
		$this->data      = $string;
		$this->mime_type = $mime_type ? $mime_type : 'application/octet-stream';
		parent::__construct($owner);
	}

	protected function getContent()
	{
		return $this->data;
	}

}
