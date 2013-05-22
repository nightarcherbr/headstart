<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Define uma classe de validação de formulário personalizada
 */
class Validation {
	protected $CI		= null;
	protected $_valid	= true;
	protected $_errors	= false;
	protected $_throw	= true;
	protected $_rules	= array();
	protected $_messages= array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->helper('validation');
		$this->initialize();

		// Set the character encoding in MB.
		if (function_exists('mb_internal_encoding'))
		{
			mb_internal_encoding($this->CI->config->item('charset'));
		}

		log_message('debug', "Data Validation Class Initialized");
	}

	/**
	 * Localiza e executa o teste
	 */
	protected function _call($value, $rule)
	{
		$ruleName	= $rule['rule'];
		$param		= $rule['parameter'];
		$builtin	= $rule['builtin'];

		// Verifica se o método existe aqui ou no chamador
		if( method_exists($this, $ruleName) ) 
		{
			return $this->$ruleName($value, $param);
		}

		// Verifica se é um metodo do CI / Controller chamador
		if( method_exists($this->CI, $ruleName) )
		{
			return $this->CI->$ruleName($value, $param);
		}

		// Verifica se é uma função do PHP nativa
		if( function_exists($ruleName) )
		{
			// Se for uma função builtin evita passar outros parametros
			if( $builtin ) return $ruleName($value);
			else return $ruleName($value, $param);
		};

		$this->_trigger($ruleName, $ruleName, 'invalid_rule', true);
		return true;
	}

	/**
	 * Converte uma string em um array de regras
	 */
	protected function _decode_rule($label, $rules){
		if(empty($rules)) 
			return array();

		// Explode todas as regras em strings depois em regra/parametros
		$re = '/([\w!]+)(\[([\w]*|"[^"\\\]*(?:\\\.[^"\\\]*)*")\])?/';
		$match = preg_match_all($re, $rules, $matches, PREG_PATTERN_ORDER);

		if( $match ){
			$_rules = array();

			// Lista funções internas e externas
			$list = get_defined_functions();

			// Percorre cada correspondencia
			foreach($matches[0] as $r => $text){
				if( empty($matches[1][$r]) || ! preg_match('/^[!]?[a-zA-Z_]+[\w]*$/', $matches[1][$r], $ru) ) continue;

				// Define as regras válidas
				$expected = ( $matches[1][$r]{0} === "!" );
				$rule = trim( str_replace('!', '', $matches[1][$r]) );
				$builtin = ( in_array($rule, $list['internal']) );

				// Configura os parametros
				$param = $matches[2][$r];
				$param = preg_replace('/^\[(\s*")?/', '', $param);
				$param = preg_replace('/("\s*)?]$/', '', $param);
				$param = str_replace('\\"', '"', $param);

				// Configura a regra no array
				$_rules[] = array('rule'=>$rule, 'label'=> $label, 'parameter'=> $param, 'builtin'=>$builtin, 'expected'=>$expected);
			}
		}

		return $_rules;
	}

	/**
	 * Configura uma mensagem de erro
	 */
	protected function _trigger($field, $label, $ruleName, $param, $group = "")
	{
		// Biblioteca de tradução
		$language = config_item('language');
		$this->CI->load->language('validation', $language);

		// Busca a mensagem padrão
		$message = $this->CI->lang->line($ruleName);

		// Encontra a mensagem personalizada
		$m = $this->_messages;
		if( isset($m[$group][$field]) )
		{
			if( is_array($m[$group][$field]) )
			{
				if( isset($m[$group][$field][$ruleName]) )
				{
					$message = $m[$group][$field][$ruleName];
				}
			}
			else
			{
				$message = $m[$group][$field];
			}
		}

		// Verifica a presença de placeholders e substitui por variaveis
		$message = str_replace('{0}', $label, $message);
		$message = str_replace('{1}', $param, $message);

		// Dispara exceções ou retorna um array de erros
		if($this->_throw){
			throw new Exception($message);
		}else{
			if( empty($this->_errors) ) $this->_errors = array();
			$this->_errors[] = $message;
		}
		return false;
	}

	/**
	 * Reseta todas as configurações do sistema
	 */
	public function initialize()
	{
		$this->_valid = true;
		$this->_throw = true;
		$this->_rules = array();
	}

	/**
	 * Executa a validação de variáveis e arrays
	 */
	public function run(&$data, $group = '')
	{
		if( empty($this->_rules) ) return false;
		$this->_errors = false;

		// Evita grupos inválidos
		if( !$group == "" && empty($group) ) $group = "";

		// Percorre as regras
		foreach( $this->_rules[$group] as $field => $rules )
		{
			// Esta regra é requerida
			$required = false;
			foreach($rules as $r )
			{
				if( $r['rule'] == "required" ) {
					$required = true;
					continue;
				}
			}

			// Coleta o valor
			if( is_array($data) ) $value = isset($data[$field])?$data[$field]:false;
			if( is_object($data) ) $value = isset($data->$field)?$data->$field:false;

			// Verifica cada regra uma a uma
			foreach($rules as $rule)
			{
				$expected	= $rule['expected'];
				$ruleName	= $rule['rule'];
				$label		= $rule['label'];
				$param		= $rule['parameter'];

				// Executa a verificação ou o prep
				$result = $this->_call( $value, $rule);

				// Verifica o resultado
				if( !is_bool($result) )
				{
					$value = $result;
				}
				else
				{
					if($result === $expected )
					{
						// Verifica se é requerido
						if( $required == true ) {
							$this->_valid = false;
							$this->_trigger($field, $label, $ruleName, $param);
						}else{
							$value = false;
							break;
						}
					}
				}
			}

			// Devolve o valor depois de todos os processamentos
			if( is_array($data) ) $data[$field] = $value;
			if( is_object($data) ) $data->$field = $value;
		}

		// Reinicializa e retorna o resultado
		$valid = ($this->_valid);
		$this->initialize();
		return $valid;
	}

	/**
	 * Retorna a lista de erros encontrados
	 */
	public function get_errors(){
		return $this->_errors;
	}

	/**
	 * Adiciona regras de validação
	 */
	public function set_rule($field, $label, $rules, $group='')
	{
		// Evita grupos inválidos
		if( !$group == "" && empty($group) ) $group = "";

		// Carrega a regra na listagem
		if( !isset($this->_rules[$group][$field]) )
		{
			$this->_rules[$group][$field] = array();
		}
		$this->_rules[$group][$field] = array_merge( $this->_rules[$group][$field], $this->_decode_rule($label, $rules) );

		return $this;
	}

	/**
	 * Remove regras de validação
	 */
	public function remove_rule($field, $group='')
	{
		// Evita grupos inválidos
		if( !$group == "" && empty($group) ) $group = "";

		// Remove 
		if( isset($this->_rules[$group][$field]) ){
			unset( $this->_rules[$group][$field] );
		}
		return $this;
	}

	/**
	 * Disparar exceções quando encontrar erros
	 */
	public function set_throw_exception($value){
		$this->_throw = !empty($value);
	}

	/**
	 * Configura mensagens personalizadas
	 */
	public function set_message($field, $message, $group='')
	{
		// Bail out
		if( empty($message) ) return $this;

		// Evita grupos inválidos
		if( !$group == "" && empty($group) ) $group = "";

		$this->_messages[$group][$field] = $message;
		return $this;
	}
}
