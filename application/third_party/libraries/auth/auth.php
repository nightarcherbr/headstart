<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
define('AUTH_NAMESPACE', 'admin.sold.com.br');

class Auth {
	private $CI = null;
	private $auth = array();

	/**
	 * Constroi a classe
	 */
	public function __construct(){
		$this->CI = &get_instance();
		$this->CI->load->config('auth');

		$session_config = array('sess_namespace' => AUTH_NAMESPACE);
		$this->CI->load->library('session', $session_config);
		$this->auth = $this->CI->session->userdata('auth', AUTH_NAMESPACE);
	}

	/**
	 * Armazena os dados do login
	 */
	private function store(array $data){
		$this->CI->session->set_userdata('auth', $data);
		$this->auth = $data;
	}

	/**
	 * Efetua a consulta do usuario / senha no banco
	 */
	public function login($login, $senha){
		$this->CI->load->database();

		// Verifica o usuario e a senha
		$login = $this->CI->db
					->select('op.idOperador, op.nome, op.email, op.login, op.ativo, op.senha ')
					->from('sold.operador op')
					->where('op.Login', $login)
					->where('op.Senha', md5($senha) )
					->where('op.Ativo', 1)
					->limit(1)
					->get()
					->row();
		if( empty($login) ) return false;
		else{
			$operador = $login->idOperador;

			// Se o usuario e a senha confere, busca as permissões do usuario
			$perms = $this->CI->db
							->select("GROUP_CONCAT(DISTINCT OG.idGrupo) 'grupo'")
							->select("GROUP_CONCAT(DISTINCT GP.idPermissao) 'permissao'")
							->from('sold.operador O')
							->join('sold.operador_grupo OG', 'O.idOperador = OG.idOperador', 'left')
							->join('sold.grupo_permissao GP', 'OG.idGrupo = GP.idGrupo', 'left')
							->where('O.idOperador', $operador)
							->get()
							->row();

			$grupos = explode(',', $perms->grupo);
			$permissoes = explode(',', $perms->permissao);

			// Armazenando os dados de permissões
			$data = array(
				'idOperador' => $operador,
				'nome' => $login->nome,
				'email' => $login->email,
				'login' => $login->login,
				'ativo' => $login->ativo,
				'senha' => $login->senha,
				'time' => time(), 
				'grupos'=>$grupos, 
				'permissoes'=>$permissoes
			);
			$this->store( $data );
			return $data;
		}
	}

	/**
	 * Apaga as informações de autenticação do usuario
	 */
	public function logout(){
		if(empty($this->auth)) return false;
		$this->CI->session->destroy();
		$this->auth = array();
	}

	/**
	 * Verifica o login e redireciona o usuário para a pagina correta
	 */
	public function redirect(){
		// Se o usuario não estiver logado
		if( ! $this->check_login() ){

			// Armazena os dados do login para recuperação posterior
			$data = array();
			$data['url'] = current_url() . ( ! empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '' );
			$data['time'] = time();
			$data['post'] = $_POST;
			$data['get'] = $_GET;
			$data['cookie'] = $_COOKIE;
			$data['files'] = $_FILES;


			// Grava o storage de sessão
			$this->CI->session->set_flashdata('redirect', $data);
			$login_url = $this->CI->config->item('login_method');

			if ( $this->CI->input->is_ajax_request() ) show_error('Não autenticado', 401);
			else {
				$login_url = site_url($login_url);
				redirect($login_url);
			}
			die();
		}else{
			// Recupera as informações da sessão da requisição anterior
			$data = $this->CI->session->flashdata('redirect');
			if( !empty($data) ){
				$_POST = array_merge($_POST, $data['post']);
				$_GET = array_merge($_GET, $data['get']);
				$_REQUEST = array_merge($_REQUEST, $data['get'], $data['post'] );
				$_COOKIE = array_merge($_COOKIE, $data['cookie']);
				$_FILES = array_merge($_FILES, $data['files']);
			}
			return $data;
		}
	}

	/**
	 * Mantem os dados da redirect por mais uma página
	 */
	public function keep_redirect(){
		$this->CI->session->keep_flashdata('redirect');
	}

	/**
	 * Mantem os dados da redirect por mais uma página
	 */
	public function get_redirect(){
		return $this->CI->session->flashdata('redirect');
	}

	/**
	 * Verifica o login
	 */
	public function check_login(){
		return !( empty($this->auth) || empty($this->auth['idOperador']) ) ;
	}

	/**
	 * Verifica um ou mais perfis específicos
	 */
	public function check_profile($required = array()){
		if( ! $this->check_login() ) return false;

		if( !is_array( $required ) ) $required = array($required);
		
		// Libera acesso para os admins
		$profiles = $this->auth['grupos'];
		if( in_array( GRUPO_ADMIN, $this->auth['grupos'] ) ) return true;

		// Pesquisa se o usuario tem algum perfil que confere com o necessário
		foreach($profiles as $p) {
			if( in_array($p, $required) ) return true;
		}
		return false;
	}

	/**
	 * Verifica uma ou mais permissoes especificas
	 */
	public function check_permission( array $required ){
		if( ! $this->check_login() ) return false;

		// Libera acesso para os admins
		if( in_array( GRUPO_ADMIN, $this->auth['grupos'] ) ) return true;

		// Pesquisa as permissões de acesso
		$perms = $this->auth['permissoes'];
		foreach($perms as $p) {
			if( in_array($p, $required ) ) return true;
		}
		return false;
	}

	/**
	 * Verifica um hash de autenticação
	 */
	public function check_token($id, $token){
		if( ! $this->check_login() ) return false;
		$this->CI->load->library('encrypt');
		$msg = $this->CI->encrypt->decode($token);

		// Descomprime os dados
		$data = explode("#", $msg);
		$idOperador = $data[0];
		$senha = $data[1];
		$time = $data[2];

		// Verifica o operador e a data de expiração do token
		if( (int)$idOperador != (int)$id ) return false;
		if( ($time +86400*7) <= time() ) { return false; } // 86400*7 = 7 Dias

		// Busca a senha na base de dados
		$operador = $this->CI->db->get_where('operador o', array('o.IDOperador'=>$data[0], 'o.Senha'=>$data[1]) )->row();
		if( empty($operador) ) return false;
		return true;
	}

	/**
	 * Recupera os dados de autenticação
	 */
	public function get(){
		if( ! $this->check_login() ) return false;
		return (object)$this->auth;
	}
	
	/**
	 * Recupera o id do usuario
	 */
	public function get_user_id(){
		if( ! $this->check_login() ) return false;
		return $this->auth['idOperador'];
	}

	/**
	 * Recupera o nome do usuario
	 */
	public function get_user_name(){
		if( ! $this->check_login() ) return false;
		return $this->auth['nome'];
	}

	/**
	 * Recupera o login do usuario
	 */
	public function get_user_login(){
		if( ! $this->check_login() ) return false;
		return $this->auth['login'];
	}

	/**
	 * Recupera o login do usuario
	 */
	public function get_user_email(){
		if( ! $this->check_login() ) return false;
		return $this->auth['email'];
	}

	/**
	 * Recupera o Token de usuario
	 */
	public function get_user_token(){
		if( ! $this->check_login() ) return false;
		$this->CI->load->library('encrypt');

		$msg = $this->auth['idOperador'] . "#" . $this->auth['senha'] . "#" . time();
		return $this->CI->encrypt->encode($msg);
	}
}