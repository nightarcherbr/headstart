<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Reconfigura os tipos de dados para entrada no mongo_db
 */
if( !function_exists('retype') ){
	function retype($param){
		switch(true){
			case is_numeric($param): return (float)$param;
			case is_object($param): return (array) array_map('retype', (array) $param);
			case is_array($param) : return array_map('retype', $param);
			case is_string($param):
				if( strtotime($param) != false ) return strtotime($param);
				else return $param;
			default: return $param;
		}
	}
}

/**
 * Reindexa um array
 */
if( !function_exists('array_index') )
{
	function array_index($in, $keys)
	{
		$out = array();
		if( !is_array($keys) ) 
			$keys = array($keys);
		$key = array_shift($keys);

		// Percorre o array
		foreach($in as $tmp)
		{
			$row = (array) $tmp;
			if(!isset($row[$key]) ) continue;
			$out[$row[$key]] = $tmp;
		}
		return $out;
	}
}

/**
 * Converte um array em um objeto recursivamente
 */
if( !function_exists('array2object') )
{
	function array2object($array, $recursive = false)
	{
		if (is_array($array))
		{
			$obj = new StdClass();
			foreach ($array as $key => $val)
			{
				if( $recursive == true ) $obj->$key = array2object($val, $recursive);
				else $obj->$key = $val;
			}
		}
		else $obj = $array;
		return $obj;
	}
}

/**
 * Converte um objeto em um array recursivamente
 */
if( !function_exists('object2array') )
{
	function object2array($object, $recursive = false)
	{
		if (is_object($object))
		{
			foreach ($object as $key => $value)
			{
				if( $recursive == true ) $array[$key] = object2array($value, $recursive);
				else $array[$key] = $value;
			}
		}
		else $array = $object;
		return $array;
	}
}

/**
 * Pluck an array of values from an array.
 */
if( !function_exists('array_pluck') )
{
	function array_pluck($array, $key)
	{
		return array_map(function($v) use ($key)
		{
			return is_object($v) ? $v->$key : $v[$key];
		}, $array);
	}
}

/**
 * Returns the values from a single column of the input array, identified by
 * the $columnKey.
 *
 * Optionally, you may provide an $indexKey to index the values in the returned
 * array by the values from the $indexKey column in the input array.
 *
 * @param array $input A multi-dimensional array (record set) from which to pull
 *					 a column of values.
 * @param mixed $columnKey The column of values to return. This value may be the
 *						 integer key of the column you wish to retrieve, or it
 *						 may be the string key name for an associative array.
 * @param mixed $indexKey (Optional.) The column to use as the index/keys for
 *						the returned array. This value may be the integer key
 *						of the column, or it may be the string key name.
 * @return array
 */
if (!function_exists('array_column')) {
	function array_column($input = null, $columnKey = null, $indexKey = null)
	{
		// Using func_get_args() in order to check for proper number of
		// parameters and trigger errors exactly as the built-in array_column()
		// does in PHP 5.5.
		$params = func_get_args();

		if (!isset($params[0])) {
			trigger_error('array_column() expects at least 2 parameters, 0 given', E_USER_WARNING);
			return null;
		} elseif (!isset($params[1])) {
			trigger_error('array_column() expects at least 2 parameters, 1 given', E_USER_WARNING);
			return null;
		}

		if (!is_array($params[0])) {
			trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
			return null;
		}

		if (!is_int($params[1])
			&& !is_string($params[1])
			&& !(is_object($params[1]) && method_exists($params[1], '__toString'))
		) {
			trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
			return false;
		}

		if (isset($params[2])
			&& !is_int($params[2])
			&& !is_string($params[2])
			&& !(is_object($params[2]) && method_exists($params[2], '__toString'))
		) {
			trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
			return false;
		}

		$paramsInput = $params[0];
		$paramsColumnKey = (string) $params[1];
		$paramsIndexKey = (isset($params[2]) ? (string) $params[2] : null);
		$resultArray = array();

		foreach ($paramsInput as $row) {

			$key = $value = null;
			$keySet = $valueSet = false;

			if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
				$keySet = true;
				$key = $row[$paramsIndexKey];
			}

			if (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
				$valueSet = true;
				$value = $row[$paramsColumnKey];
			}

			if ($valueSet) {
				if ($keySet) {
					$resultArray[$key] = $value;
				} else {
					$resultArray[] = $value;
				}
			}

		}

		return $resultArray;
	}
}

