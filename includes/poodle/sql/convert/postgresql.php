<?php
if (empty($this)) { return; }

$this->tbl_pattern = array(
	'#\s(UNSIGNED|BINARY|FULLTEXT|COMMENT\s+\'[^\']*\')\s*#si',
	'#\s[A-Z]*BLOB#si',
	'#\s[A-Z]*TEXT#si',
	'#\sCHAR(?!ACTER)#si',
	'#\sDECIMAL#si',
	'#\sFLOAT4?#si',
	'#\s(FLOAT8|DOUBLE(?!\s+PRECISION))#si',
	'#\s(INT[12]|TINYINT)#si',
	'#\s(INT[34]|MEDIUMINT)#si',
	'#\sINT8#si',
	'#\s(SERIAL4|INT[^,]+AUTO_INCREMENT[^,]*)#si',
	'#\s(SERIAL8|BIGINT[^,]+AUTO_INCREMENT[^,]*)#si',
	'#\s(VAR)?BINARY\(\d+\)#si',
	'#^ALTER\s+TABLE\s+([a-z0-9_{}]+)\s+COMMENT=\'([^\']*)\'#si',
	'#([a-z0-9_]+)\s+ENUM\(([^\(\)]+)\)[^,)]+#si',
);
$this->tbl_replace = array(
	' ',
	' BYTEA',
	' TEXT',
	' CHARACTER',
	' NUMERIC',
	' REAL',
	' DOUBLE PRECISION',
	' SMALLINT',
	' INTEGER',
	' BIGINT',
	' SERIAL NOT NULL',
	' BIGSERIAL NOT NULL',
	' BYTEA',
	'COMMENT ON TABLE $1 IS \'$2\'',
	'$1 VARCHAR(20) CHECK ($1 IN($2))',
);
