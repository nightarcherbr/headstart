<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Required
 */
if( !function_exists('required') )
{
	function required($str)
	{
		if ( ! is_array($str))
		{
			return (trim($str) == '') ? FALSE : TRUE;
		}
		else
		{
			return ( ! empty($str));
		}
	}
}


/**
 * Performs a Regular Expression match test.
 */
if( !function_exists('regex_match') )
{
	function regex_match($str, $regex)
	{
		if ( ! preg_match($regex, $str))
		{
			return FALSE;
		}

		return  TRUE;
	}
}



/**
 * Minimum Length
 */
if( !function_exists('min_length') )
{
	function min_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($str) < $val) ? FALSE : TRUE;
		}

		return (strlen($str) < $val) ? FALSE : TRUE;
	}
}


/**
 * Max Length
 */
if( !function_exists('max_length') )
{
	function max_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($str) > $val) ? FALSE : TRUE;
		}

		return (strlen($str) > $val) ? FALSE : TRUE;
	}
}


/**
 * Exact Length
 */
if( !function_exists('exact_length') )
{
	function exact_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($str) != $val) ? FALSE : TRUE;
		}

		return (strlen($str) != $val) ? FALSE : TRUE;
	}
}


/**
 * Valid Email
 */
if( !function_exists('valid_email') )
{
	function valid_email($str)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
	}
}


/**
 * Valid Emails
 */
if( !function_exists('valid_emails') )
{
	function valid_emails($str)
	{
		if (strpos($str, ',') === FALSE)
		{
			return $this->valid_email(trim($str));
		}

		foreach (explode(',', $str) as $email)
		{
			if (trim($email) != '' && $this->valid_email(trim($email)) === FALSE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}


/**
 * Validate IP Address
 */
if( !function_exists('valid_ip') )
{
	function valid_ip($ip, $which = '')
	{
		$CI = &get_instance();
		return $CI->input->valid_ip($ip, $which);
	}
}


/**
 * Alpha
 */
if( !function_exists('alpha') )
{
	function alpha($str)
	{
		return ( ! preg_match("/^([a-z])+$/i", $str)) ? FALSE : TRUE;
	}
}


/**
 * Alpha-numeric
 */
if( !function_exists('alpha_numeric') )
{
	function alpha_numeric($str)
	{
		return ( ! preg_match("/^([a-z0-9])+$/i", $str)) ? FALSE : TRUE;
	}
}


/**
 * Alpha-numeric with underscores and dashes
 */
if( !function_exists('alpha_dash') )
{
	function alpha_dash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}
}


/**
 * Numeric
 */
if( !function_exists('numeric') )
{
	function numeric($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

	}
}


/**
 * Integer
 */
if( !function_exists('integer') )
{
	function integer($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
	}
}


/**
 * Decimal number
 */
if( !function_exists('decimal') )
{
	function decimal($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
	}
}


/**
 * Greather than
 */
if( !function_exists('greater_than') )
{
	function greater_than($str, $min)
	{
		if ( ! is_numeric($str))
		{
			return FALSE;
		}
		return $str > $min;
	}
}


/**
 * Less than
 */
if( !function_exists('less_than') )
{
	function less_than($str, $max)
	{
		if ( ! is_numeric($str))
		{
			return FALSE;
		}
		return $str < $max;
	}
}


/**
 * Is a Natural number  (0,1,2,3, etc.)
 */
if( !function_exists('is_natural') )
{
	function is_natural($str)
	{
		return (bool) preg_match( '/^[0-9]+$/', $str);
	}
}


/**
 * Is a Natural number, but not a zero  (1,2,3, etc.)
 */
if( !function_exists('is_natural_no_zero') )
{
	function is_natural_no_zero($str)
	{
		if ( ! preg_match( '/^[0-9]+$/', $str))
		{
			return FALSE;
		}

		if ($str == 0)
		{
			return FALSE;
		}

		return TRUE;
	}
}


/**
 * Valid Base64
 *
 * Tests a string for characters outside of the Base64 alphabet
 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
 */
if( !function_exists('valid_base64') )
{
	function valid_base64($str)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
	}
}

/**
 * Verifica se o CPF valido
 */
if( !function_exists('valid_cpf') )
{
	function valid_cpf($cpf)
	{
		// Verificação básica
		$pattern = "/[0-9]{3}\.?[0-9]{3}\.?[0-9]{3}-?[0-9]{2}/";
		if( ! preg_match($pattern, $cpf) ) return false;
		else $cpf = preg_replace('/[^0-9]*/', '', $cpf);

		// Verificação de sequencia
		$equal = true;
		for($i = 0; $i < strlen($cpf); $i++ ){
			if($cpf{$i} !== $cpf{$i+1} ) {
				$equal = false;
				break;
			}
		}

		// Verifica os dois digitos
		for ($t = 9; $t < 11; $t++) {
			for ($d = 0, $c = 0; $c < $t; $c++) {
				$d += $cpf{$c} * (($t + 1) - $c);
			}

			$d = ((10 * $d) % 11) % 10;
			if ($cpf{$c} != $d) {
				return false;
			}
		}

		return true;
	}
}

/**
 * Verifica se o CNPJ valido
 */
if( !function_exists('valid_cnpj') )
{
	function valid_cnpj($cnpj)
	{
		// Verificação básica
		$pattern = "/[0-9]{2}\.?[0-9]{3}\.?[0-9]{3}\/[0-9]{4}-?[0-9]{2}/";
		if( ! preg_match($pattern, $cnpj) ) return false;
		else $cnpj = preg_replace('/[^0-9]*/', '', $cnpj);

		// Calcula o Digito 1
		$soma = 0;
		for( $m = 2, $c = 11; $c >= 0; $c--, $m++){
			$soma += $cnpj[$c] * $m;
			if( $m >= 9 ) $m = 1;
		}
		$d1 = $soma % 11;
		$d1 = $d1 < 2 ? 0 : 11 - $d1;

		// Calcula o digito 2
		$soma = 0;
		for( $m = 2, $c = 12; $c >= 0; $c--, $m++){
			$soma += $cnpj[$c] * $m;
			if( $m >= 9 ) $m = 1;
		}
		$d2 = $soma % 11;
		$d2 = $d2 < 2 ? 0 : 11 - $d2;

		return ($cnpj[12] == $d1 && $cnpj[13] == $d2);
	}
}

/**
 * Verifica se é um CNPJ ou CPF valido
 */
if( !function_exists('valid_document') )
{
	function valid_document($documento)
	{
		return valid_cnpj($documento) || valid_cnpj($documento);
	}
}


/* ----------------------------------------------------------------------------
-- Funções de processamento de strings
---------------------------------------------------------------------------- */

/**
 * Prep data for form
 *
 * This function allows HTML to be safely shown in a form.
 * Special characters are converted.
 */
if( !function_exists('prep_for_form') )
{
	function prep_for_form($data = '')
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = prep_for_form($val);
			}

			return $data;
		}

		if ($data === '')
		{
			return $data;
		}

		return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($data));
	}
}


/**
 * Prep URL
 */
if( !function_exists('prep_url') )
{
	function prep_url($str = '')
	{
		if ($str == 'http://' OR $str == '')
		{
			return '';
		}

		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://')
		{
			$str = 'http://'.$str;
		}

		return $str;
	}
}


/**
 * Strip Image Tags
 */
if( !function_exists('strip_image_tags') )
{
	function strip_image_tags($str)
	{
		$CI = &get_instance();
		return $CI->input->strip_image_tags($str);
	}
}


/**
 * XSS Clean
 */
if( !function_exists('xss_clean') )
{
	function xss_clean($str)
	{
		$CI = &get_instance();
		return $CI->security->xss_clean($str);
	}
}

/**
 * Convert PHP tags to entities
 */
if( !function_exists('encode_php_tags') )
{
	function encode_php_tags($str)
	{
		return str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
	}
}


/**
 * Remove qualquer tipo de pontuação
 */
if( !function_exists('clean') ){
	function clean($document)
	{
		return preg_replace('/[^a-zA-Z0-9]*/', '', $document);
	}
}

/**
 * Formata um documento com a pontuação correta
 */
if( !function_exists('format_document') ){
	function format_document($document)
	{
		$document = clean($document);
		if( strlen($document) == 11 )  
			return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '${1}.${2}.${3}-${4}', $document);
		if( strlen($document) == 14 ) 
			return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $document);
		return $document;
	}
}

/**
 * Formata um numero para separação em decimal 
 */
if( !function_exists('format_currency') ){
	function format_currency($valor){
		$valor = str_replace(".", '***', $valor);
		$valor = str_replace(",", '.', $valor);
		$valor = str_replace("***", ',', $valor);
		return $valor;
	}
}