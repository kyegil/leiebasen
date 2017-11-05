<?php
/**********************************************
MysqliConnect
by Kay-Egil Hauan
This version 2017-01-17
**********************************************/

class MysqliConnection extends mysqli {

public $table_prefix = ''; //	End with underscore



public function __construct() {
	parent::__construct(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
	$this->query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
	$this->query("SET TIME_ZONE = '-0:00'"); // All time is saved as GMT and converted later
}



/*	Array Fields
Turn array of field names into MySQL string
******************************************
$array (array) array of field names
------------------------------------------
return: (string) sql-syntax
*/
public function arrayFields($array = '*') {
	if( is_string($array) ) {
		return $this->escape($array);
	}
	settype($array, 'array');
	$set = array();
	foreach( $array as $alias => $field ) {
		$field = explode('.', $field);
		$field = implode('.', $field);

		$set[] = $field . ( !is_numeric( $alias )  ? (' AS ' . $alias) : '');
	}
	
	return implode(', ', $set);
}



/*	where
Turns an array into an SQL WHERE or HAVING clause
******************************************
$array (string/array/object):	array of field names
$operator (string):	The logical operator
------------------------------------------
return: (string) sql-syntax
*/
public function where( $array, $operator = 'AND' ) {
	if( is_string($array) ) {
		return $array;
	}
	settype($array, 'array');
	
	switch( strtolower(trim($operator)) ) {
		case 'or': {
			$operator = 'or';
			break;
		}
		case 'xor': {
			$operator = 'xor';
			break;
		}
		default: {
			$operator = 'and';
			break;
		}
	}
	
	$set = array();

	foreach( $array as $condition => $value ) {
		$condition = trim( $condition );
		if(is_numeric( $condition )) {
			$condition = 'and';
		}
		
		if(
			strtolower($condition) === 'or'
			or strtolower($condition) === 'xor'
			or strtolower($condition) === 'and'
		) {
			$set[] = $this->where( $value, $condition );
		}
		
		else {
			if(
					mb_substr($condition, -1)	!=	'='
				and mb_substr($condition, -1)	!=	'<'
				and mb_substr($condition, -1)	!=	'>'
				and strtolower( mb_substr($condition, -3) )	!=	' is'
				and strtolower( mb_substr($condition, -4) )	!=	' not'
				and strtolower( mb_substr($condition, -5) )	!=	' like'
			) {
				$condition .= ' =';
			}
			if( is_array( $value ) or is_object( $value )) {
				array_walk($value, array($this, 'escape'));
				$set[]
					= "({$condition} '" . implode("' or {$condition} '", $value) . "')";
			}
			else {
				if( is_null( $value ) ) {
					$set[] = "{$condition} NULL";
				}
				else if( is_bool( $value ) ) {
					$set[] = "{$condition} " . ($value ? 'TRUE' : 'FALSE');
				}
				else if( is_int( $value ) or is_float( $value ) ) {
					$set[] = "{$condition} {$this->escape($value)}";
				}
				else {
					$set[] = "{$condition} '{$this->escape($value)}'";
				}
			}
		}
	}
	return " (" . implode(" {$operator} ", $set) . ") ";
}




public function arrayData($config) {
	settype($config, 'object');
	
	settype($config->distinct,		'boolean');
	settype($config->flat,			'boolean');
	settype($config->returnQuery,	'boolean');

	settype($config->fields,		'array');
	settype($config->groupfields,	'string');
	settype($config->limit, 		'string');
	settype($config->orderfields,	'string');
	settype($config->source,		'string');
	settype($config->sql,			'string');
	
	if(!isset($config->where)) {
		$config->where = array();
	}
	if(!isset($config->having)) {
		$config->having = array();
	}

	$result = (object)array(
		'success' => true,
		'msg'=> "",
		'data' => null
	);

	if(!isset( $config->source ) && !isset( $config->sql ) ) {
		$result->success = false;
		$result->msg = "Inadequate input: No source parameter given in MysqliConnection::arrayData()";
		return $result;
	}

	if(!isset($config->class) or !class_exists($config->class)) {
		$config->class = 'stdClass';
	}
	
	$sql =	"SELECT "
		.	($config->distinct	? "DISTINCT " : "")
		.	($config->fields	? "{$this->arrayFields($config->fields)}\n" : "*\n")
		.	"FROM {$config->source}\n"
		.	($config->where		? "WHERE {$this->where($config->where)}\n" : "")
		.	($config->groupfields	? "GROUP BY {$config->groupfields}\n" : "")
		.	($config->having	? "HAVING {$this->where($config->having)}\n" : "")
		.	($config->orderfields	? "ORDER BY {$config->orderfields}\n" : "");
	if( $config->sql ) {
		$sql = $config->sql;
	}
	
	$result->data = array();
	$result->success = true;
	if($sett = $this->query($sql)) {
		$result->totalRows = $sett->num_rows;
	}

	if( $config->limit ) {
		$sql .= "LIMIT {$config->limit}";
		$sett = $this->query( $sql );
	}
	if($config->returnQuery) $result->sql = $sql;
	
	if(!$sett) {
		$result->success = false;
		throw new Exception("mysqli error: {$this->error}\nsql:\n{$sql}\n");
		$result->msg = $this->error;
	}
	else {
		if($config->flat) {
			while($arr = $sett->fetch_row()) {
				$result->data[] = $arr[0];
			}
		}
		else {
			while($arr = $sett->fetch_object($config->class)) {
				$result->data[] = ($arr);
			}
		}
		$sett->free();
	}

	return $result;
}



/*
Escape string
*****************************************/
//	$string (string) string to be escaped
//	--------------------------------------
//	return: (string) escaped string

public function escape($string) {
	if( is_array( $string ) ) {
		throw new Exception( print_r($string, true) );
	}
	settype( $string, 'string' );
	return $this->real_escape_string(get_magic_quotes_gpc() ? stripslashes($string) : $string);
}



public function saveToDb($config) {
	settype($config, 'object');
	
	settype($config->insert,				'boolean');
	settype($config->update,				'boolean');
	settype($config->updateOnDuplicateKey,	'boolean');
	settype($config->returnQuery,			'boolean');

	settype($config->test,					'string');
	settype($config->id,					'string');
	settype($config->set,					'string');
	settype($config->table,					'string');
	settype($config->groupfields,			'string');

	settype($config->fields,				'array');

	if(!isset($config->where)) {
		$config->where = array();
	}

	$result = (object)array(
		'success' => true,
		'msg'=> ""
	);
	$a = array();

	if (!($config->table)) {
		throw new Exception("No target table given");
	}
	if (!is_array($config->fields) and !is_object($config->fields)) {
		$result->success = false;
		$result->msg = 'Inadequate input: No fields or data to save';
		return $result;
	}
	if (!isset($config->update) and !isset($config->insert)) {
		throw new Exception("Asked neither to insert nor update");
	}
	if ($config->update and !$config->where) {
		throw new Exception("'WHERE' limitations required when updating table");
	}
	foreach ($config->fields as $field => $value) {
		if( $value instanceof DateTime ) {
			throw new Exception("Value can not be DateTime object: " . var_export($value, true));
		}
		if(gettype($value) == 'array') {
			throw new Exception("Value can not be array: " . var_export($value, true));
		}
		if($value === true or $value === "true" or $value === "TRUE") {
			$a[] =	"\t$field = true";
		}
		else if($value === false or $value === "false" or $value === "FALSE") {
			$a[] =	"\t$field = false";
		}
		else if($value === null) {
			$a[] =	"\t$field = null";
		}
		else {
			$a[] =	"\t$field = '" . $this->escape($value) . "'";
		}
	}

	if ($config->insert) {
		$sql = "INSERT INTO ";
	}
	else if ($config->update) {
		$sql = "UPDATE ";
	}
	$sql .=	$config->table . "\n"
		.	(count($a) ? "SET {$config->set}\n" : " () VALUES ()\n")
		.	implode(",\n", $a);
		
	if( $config->insert and $config->updateOnDuplicateKey ) {
		$sql .= "\nON DUPLICATE KEY UPDATE\n"
		.	(count($a) ? "{$config->set}\n" : " () VALUES ()\n")
		.	implode(",\n", $a);
	}
		

	if( $config->update ) {
		$sql .= "\nWHERE {$this->where($config->where)}\n";
	}

	if($config->returnQuery) $result->sql = $sql;
	
	if(@$config->test) {
		echo "\n{$config->test}:\n{$sql}\n";
	}
	
	if ($result->success = $this->query($sql)) {
		if ($config->update) {
			$result->id = $config->id;
		}
		else {
			$result->id = $this->insert_id;
		}
	}
	else {
		throw new Exception("mysqli error: {$this->error}\nsql:\n{$sql}\n");
		$result->msg = $this->error;
	}

	return $result;
}


}