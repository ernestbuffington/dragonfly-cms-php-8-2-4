<?php
/*	Dragonfly™ CMS, Copyright © since 2010 by CPG-Nuke Dev Team. All rights reserved.
*/

namespace Poodle\SQL\Interfaces;

interface Manager
{
	function __construct(\Poodle\SQL $SQL);
	public function listDatabases();
	public function listTables($detailed=false);
	public function listColumns($table, $full=true);
	public function listIndices($table);
	public function listForeignKeys($table);
	public function listTriggers($table);
	public function listViews();
	public function listFunctions();
	public function listProcedures();
	public function getView     ($name);
	public function getFunction ($name);
	public function getProcedure($name);
	public function getTableInfo($name);
	public function analyze($table=null);
	public function check($table=null);
	public function optimize($table=null);
	public function repair($table=null);
	public function tablesStatus();
	public function serverStatus();
	public function serverProcesses();
	public function setSchemaCharset();
}
