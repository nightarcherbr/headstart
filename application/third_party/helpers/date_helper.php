<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Used when no configuration is set on codeigniter
if( !defined('DATE_DEFAULT') ) define('DATE_DEFAULT', "DATE_DEFAULT");
if( !defined('TIME_DEFAULT') ) define('TIME_DEFAULT', "TIME_DEFAULT");
if( !defined('DATETIME_DEFAULT') ) define('DATETIME_DEFAULT', "DATETIME_DEFAULT");

if( !defined('DATE_MYSQL') ) define('DATE_MYSQL', "%Y-%M-%D");
if( !defined('TIME_MYSQL') ) define('TIME_MYSQL', "%H:%i:%s");
if( !defined('DATETIME_MYSQL') ) define('DATETIME_MYSQL', "%Y-%M-%D %H:%i:%s");

if( !defined('DATE_ATOM') ) define('DATE_ATOM', '%Y-%m-%dT%H:%i:%s%Q');
if( !defined('DATE_COOKIE') ) define('DATE_COOKIE', '%l, %d-%M-%y %H:%i:%s UTC');
if( !defined('DATE_ISO8601') ) define('DATE_ISO8601', '%Y-%m-%dT%H:%i:%s%Q');
if( !defined('DATE_RFC822') ) define('DATE_RFC822', '%D, %d %M %y %H:%i:%s %O');
if( !defined('DATE_RFC850') ) define('DATE_RFC850', '%l, %d-%M-%y %H:%i:%s UTC');
if( !defined('DATE_RFC1036') ) define('DATE_RFC1036', '%D, %d %M %y %H:%i:%s %O');
if( !defined('DATE_RFC1123') ) define('DATE_RFC1123', '%D, %d %M %Y %H:%i:%s %O');
if( !defined('DATE_RSS') ) define('DATE_RSS', '%D, %d %M %Y %H:%i:%s %O');
if( !defined('DATE_W3C') ) define('DATE_W3C', '%Y-%m-%dT%H:%i:%s%Q');

/**
 * Carrega o formato padrão para o local informado
 */
if( !function_exists('date_locale_format') )
{
	function date_default_format($format)
	{
		if( empty($format) ) $format = DATETIME_DEFAULT;

		$CI = &get_instance();
		$CI->load->config('date');
		if( $format == DATETIME_DEFAULT )
			return $CI->config->item('datetime_format');

		if( $format == TIME_DEFAULT )
			return $CI->config->item('time_format');

		if( $format == DATE_DEFAULT )
			return $CI->config->item('date_format');
		return $format;
	}
}


/**
 * Valida uma data
 */
if( !function_exists('valid_date') )
{
	function valid_date($date, $formatHint = false)
	{
		$formatHint = date_default_format($formatHint);
		$d = 0; $m = 0; $y = 0; $h = 0; $i = 0; $s = 0;

		//Lê cada caractere do formato
		$dt = 0; $tmp = null;
		for($c = 0; $c < strlen($formatHint); $c++){
			if($formatHint[$c] == "%"){
				//Identifica o token
				$keychar = strtolower($formatHint[++$c]);
				
				//Carrega o valor na posição
				$tmp = null;
				while( $dt < strlen($date) && is_numeric($date[$dt])){
					$tmp = $tmp . $date[$dt++];
				}

				//Carrega o valor encontrado na variavel correta
				$$keychar = $tmp;
			}else{
				if(isset($date[$dt]) && $formatHint[$c] != $date[$dt]) return false;
				$dt++;
			}
		}

		//Completa formatos parciais
		if($d == 0) $d = (int)date("d");
		if($y == 0) $y = (int)date("Y");

		//Valida os valores descritos na data
		$bi6o = ($y % 4 == 0)?1:0;
		if($y < 1900) return false;
		if($m > 12 || $m < 1) return false;
		if($d < 1) return false;
		if(($h > 23 || $h < 0) || ($i > 59 || $i < 0) || ($s > 59 || $s < 0))return false;
		if(($d > (28+$bi6o) && $m == 2) ) return false;
		if( ( $d > 30 && ($m % 2 == 0) && $m < 7 ) || ( $d > 30 && $m % 2 != 0 && $m > 7 ) ) return false;
		if($d > 31) return false;
		return true;
	}
}


/**
 * Alias para a função valid_date
 */
if( !function_exists('date_validate') )
{
	function date_validate($date, $formatHint = false)
	{
		return valid_date($date, $formatHint);
	}
}

/**
 * Gera um UNIX timestamp
 */
if( !function_exists('date_timestamp') )
{
	function date_timestamp($date, $formatHint = false)
	{
		$formatHint = date_default_format($formatHint);
		if(!valid_date($date, $formatHint)) return false;
		$d = 0; $m = 0; $y = 0; $h = 0; $i = 0; $s = 0;

		//Lê cada caractere do formato
		$dt = 0; $tmp = null;
		for($c = 0; $c < strlen($formatHint); $c++)
		{
			if($formatHint[$c] == "%")
			{
				//Identifica o token
				$keychar = strtolower($formatHint[++$c]);
				$break = isset($formatHint[$c+1])?$formatHint[$c+1]:"";

				//Varre o valor encontrado
				$tmp = null;
				while( $dt < strlen($date) && ( is_numeric($date[$dt]) ) )
				{
					$tmp = $tmp . $date[$dt++];
				}

				//Carrega o valor encontrado na variavel correta
				$$keychar = $tmp;
			}else $dt++;
		}

		// Efetua a saida da data
		if($d == 0) $d = (int)date("d");
		if($y == 0) $y = (int)date("Y");
		date_default_timezone_set('America/Sao_Paulo');
		return mktime($h, $i, $s, $m, $d, $y );

	}
}

/**
 * Compara duas datas e retorna a diferença em segundos
 */
if( !function_exists('date_compare') )
{
	function date_compare($timestamp1, $timestamp2)
	{
		return abs($timestamp2 - $timestamp1);
	}
}

/**
 * Compara duas datas e retorna a diferença em horas, minutos, etc.
 */
if( !function_exists('fuzzy_date') )
{
	function fuzzy_date($timestamp)
	{
		// Carrega a internacionalização
		$CI = &get_instance();
		$language = config_item('language');
		$CI->lang->load('date');

		$phrase = array('date_second','date_minute','date_hour','date_day','date_week','date_month','date_year');
		$length = array(1, 60, 3600, 86400, 604800, 2630880, 31570560);

		// Compara as datas
		$delta = date_compare($timestamp, time());
		for($i = sizeof($length) -1; ($i >= 0) && (($no =  $delta/$length[$i]) <= 1); $i--);

		if($i < 0) $i=0;
		$no = floor($no);

		// Busca a tradução
		if($no > 1) $phrase[$i] .='s'; 
		$value=sprintf("%d %s ",$no, $CI->lang->line($phrase[$i]));

		return $value.' ago';
	}
}

/**
 * Compara duas datas e retorna um timespan
 */
if( !function_exists('date_timespan') )
{
	function date_timespan($timestamp1, $timestamp2)
	{
		$key = array('s', 'm', 'h', 'd', 'm', 'y');
		$length = array(1, 60, 3600, 86400, 604800, 2630880);

		// Compara as duas timestamps
		$out = array();
		$secs = date_compare($timestamp1, $timestamp2);
		for( $i = sizeof($length)-1; $i >= 0; $i--)
		{
			if( $secs >= $length[$i] )
			{
				// Contabiliza os componentes
				$k = $key[$i];
				$out[] = floor( $secs / $length[$i] ) . $k;
				// Subtrai o componente do total
				$secs = $secs % $length[$i];
			}
		}
		return implode(' ', $out);
	}
}

/**
 * Formata uma data em um formato arbitrário
 */
if( !function_exists('format_date'))
{
	function format_date($date, $targetFormat, $formatHint = false)
	{
		$formatHint = date_default_format($formatHint);
		$targetFormat = date_default_format($targetFormat);
		if(valid_date($date, $targetFormat)) return $date;
		if(!valid_date($date, $formatHint)) return null;

		$phpfmt = strtoupper($targetFormat);
		$phpfmt = str_replace('%H', 'H', $phpfmt);
		$phpfmt = str_replace('%I', "i", $phpfmt);
		$phpfmt = str_replace('%S', "s", $phpfmt);
		$phpfmt = str_replace('%D', "d", $phpfmt);
		$phpfmt = str_replace('%M', "m", $phpfmt);
		$phpfmt = str_replace('%Y', "Y", $phpfmt);
		
		$time = date_timestamp($date, $formatHint);
		return date($phpfmt, $time);
	}
}


/**
 * Formata uma data em um formato arbitrário
 */
if( !function_exists('to_date'))
{
	function to_date($date, $formatHint = false)
	{
		$formatHint = date_default_format($formatHint);
		return format_date($date, $formatHint, DATETIME_MYSQL);
	}
}


/**
 * Formata uma data em um formato arbitrário
 */
if( !function_exists('to_mysql'))
{
	function to_mysql($date, $formatHint = false)
	{
		$formatHint = date_default_format($formatHint);
		return format_date($date, DATETIME_MYSQL, $formatHint);
	}
}
