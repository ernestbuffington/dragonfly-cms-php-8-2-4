<?php
/*	Dragonfly™ CMS, Copyright © since 2010 by CPG-Nuke Dev Team. All rights reserved.
*/

namespace Poodle\SQL\Interfaces;

interface Adapter
{
	function __construct($config);

	/* bool, Start a transaction */
	public function begin();

	/* string, Returns the current character set for the connection. */
	public function character_set_name();

	/* Closes a connection */
	public function close();

	/* bool, Commits the current transaction */
	public function commit();

	/* bool, Opens a connection */
	public function connect();

	/* string, Returns the default database for database queries */
	public function dbname();

	/* string, Escape a string for insertion into a binary field */
	public function escapeBinary($data);

	/* string, Escape a string for insertion into a text field */
	public function escape_string($data);

	/* string, Returns the current character set for the connection. */
	public function get_charset();

	/* mixed, Returns the auto generated id used in the last query */
	public function insert_id($idfield);

	/* mixed, Gets the current running processes */
	public function listProcesses();

	/* bool, Pings a server connection, or tries to reconnect if the connection has gone down */
	public function ping();

	/* Execute a query */
	public function query($query, $unbuffered=0);

	/* string, Escape a string for insertion into a binary field and append surrounding quotes */
	public function quoteBinary($data);

	/* bool, Rolls back current transaction */
	public function rollback();

	/* bool, Sets the default charset */
	public function set_charset();

	/* mixed, Returns SQL result of all tables */
	public function showTables($prefix);

	/* string, Gets the current system status */
	public function stat();

	/* string, Unescape binary field data */
	public function unescapeBinary($data);
/*
	public function search(array $fields, &$text);
*/
	public function createLock($name, $timeout = 0);
	public function releaseLock($name);

}
