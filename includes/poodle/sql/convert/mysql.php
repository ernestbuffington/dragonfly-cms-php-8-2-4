<?php
if (empty($this)) { return; }

$this->tbl_pattern = array(
	'#\sBYTEA#si',
	'#\sCHARACTER VARYING#si',
	'#\sNUMERIC#si',
	'#\s(FLOAT4|REAL)#si',
	'#\s(FLOAT8|DOUBLE\s+PRECISION)#si',
	'#\sINT1#si',
	'#\sINT2#si',
	'#\sINT3#si',
	'#\sINT4#si',
	'#\sINT8#si',
	'#\s(SERIAL4|SERIAL[^,]*)#si',
	'#\s(SERIAL8|BIGSERIAL[^,]*)#si',
	'#^COMMENT\s+ON\s+TABLE\s+([a-z0-9_{}]+)\s+IS\s+\'(.*)\'#si',
	'#^COMMENT\s+ON\s+COLUMN.+#si',
	'#^(CREATE\s+TABLE.+\))$#Dsi',
);
$this->tbl_replace = array(
	' LONGBLOB',
	' VARCHAR',
	' DECIMAL',
	' FLOAT',
	' DOUBLE',
	' TINYINT',
	' SMALLINT',
	' MEDIUMINT',
	' INT',
	' BIGINT',
	' INT NOT NULL AUTO_INCREMENT',
	' BIGINT NOT NULL AUTO_INCREMENT',
	'ALTER TABLE $1 COMMENT=\'$2\'',
	'',
	'$1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin',
);
