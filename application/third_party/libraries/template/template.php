<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Template {
	function show($view, $data=array()){
		$CI = &get_instance();

		$CI->load->view('layout/base', array('view' => $view, 'dados' => $data));
	}
}
?>