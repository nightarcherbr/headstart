<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Retorna o compressor de assets
 */
if( !function_exists('_get_minify') ){
	function _get_minify(){
		$CI = &get_instance();
		$CI->load->library('minify');
		return $CI->minify;
	}
}

/**
 * Converte uma lista de atributos em string
 */
if( !function_exists('_parse_asset_html') ){
	function _parse_asset_html($attributes = NULL)
	{
		if(is_array($attributes))
		{
			$attribute_str = '';
			foreach($attributes as $key => $value):
				$attribute_str .= ' '.$key.'="'.$value.'"';
			endforeach;
			return $attribute_str;
		}
		return '';
	}
}

/**
 * Evita inclusões duplicadas
 */
if( !function_exists('_add_params_get') ){
	function _include_once_pack($pack, $path, $include_once = TRUE)
	{
		$CI = &get_instance();
		// Carrega a configuração
		$include = $CI->config->item('include_once');
		if (! is_array($include)) $include = array();

		// Verifica se o arquivo já foi incluido
		if ( isset($include[$path]) ) {
			if (! $include_once) {
				$include[$path]++;
				return $pack;
			}
			return '';
		} else {
			// Marca como incluido e retorna o arquivo
			$include[$path] = 1;
			$CI->config->set_item('include_once', $include);
			return $pack;
		}
	}
}

/**
 * Adiciona parametros GET em um URL
 */
if( !function_exists('_add_params_get') ){
	function _add_params_get( $url, $params = array() )
	{
		if ( empty( $params )) return $url;
		// Formata os parametros
		if ( is_array($params))
		{
			$tmp = array();
			foreach ($params as $key => $p) $tmp[] = $key.'='.$p;
			$params = implode( '&', $tmp );
		}

		// Verifica se já há parâmetros get no URL
		if ( preg_match( '/\?.*[^\/*]/', $url ) ) $params = '&'.$params;
		else $params = '?'.$params;

		return ($url .= $params);
	}
}

/**
 * Requisição externa
 */
if( !function_exists('_is_external_asset') ){
	function _is_external_asset($asset_name){
		return ( strpos($asset_name, 'http://') !== false );
	}
}

/**
 * Localiza um asset
 */
if( !function_exists('_locate_asset') ){
	function _locate_asset($asset_name, $module_name = NULL, $asset_type = NULL, $base_url = false)
	{

		// Adiciona os módulos na busca
		$asset_location = $base_url;
		if(!empty($module_name) )
		{
			$asset_location .= 'modules/'.$module_name.'/';
		}

		return $asset_location . substr( $asset_type.'/'.$asset_name, strpos($asset_type.'/'.$asset_name, '?') );
	}
}

/**
 * Locate cache
 */
if( !function_exists('_locate_cache') ){
	function _locate_cache($asset_name, $module_name = NULL, $asset_type = NULL)
	{
		$asset_location = '';
		if(!empty($module_name) )
		{
			$asset_location .= 'modules/'.$module_name.'/';
		}
		$asset_location .= $asset_type;

		$cache = APPPATH.'assets/cache/' . $asset_location . '/' . $asset_name;
		if( !is_dir($cache) && !file_exists(dirname($cache))) mkdir( dirname($cache), 0777, true);
		return $cache ;
	}
}

/**
 * Cópia o base_url do url_helper
 */
if ( ! function_exists('base_url'))
{
	function base_url($uri = '')
	{
		$CI =& get_instance();
		return $CI->config->base_url($uri);
	}
}

/**
 * Retorna um caminho para um asset qualquer
 */
if( !function_exists('other_asset_url') ){
	function other_asset_url($asset_name, $module_name = NULL, $asset_type = NULL)
	{
		if(_is_external_asset($asset_name)) return $asset_name;

		// Obtem o caminho do asset
		$base_url = base_url();
		$asset_location = _locate_asset($asset_name, $module_name, $asset_type, APPPATH.'assets/');

		// Engana o navegador para ignorar o cache sempre que arquivo for modificado
		$size = is_file($asset_location)?filemtime($asset_location):0;
		return _add_params_get( $base_url . $asset_location, array('cache'=>$size));
	}
}

/**
 * Retorna um arquivo CSS
 */
if( !function_exists('css_url') ){
	function css_url($asset_name, $module_name = NULL)
	{
		if(_is_external_asset($asset_name)) return $asset_name;

		$base_url = base_url();
		if( ENVIRONMENT !== 'development' ){
			// Posiciona o arquivo de CSS minificado
			$CI =& get_instance();
			$minify = &_get_minify();
			$cache = _locate_cache($asset_name, $module_name, 'css');
			$location = _locate_asset($asset_name, $module_name, 'css', APPPATH.'assets/');
			$asset_location = $minify->css($location, $cache);
		}else{
			$asset_location = _locate_asset($asset_name, $module_name, 'css', APPPATH.'assets/');
		}

		// Engana o navegador para ignorar o cache sempre que arquivo for modificado
		$size = is_file($asset_location)?filemtime($asset_location):0;
		return _add_params_get( $base_url . $asset_location, array('cache'=>$size));
	}
}

/**
 * Retorna um arquivo CSS
 */
if( !function_exists('css') ){
	function css($asset_name, $module_name = NULL, $attributes = array())
	{
		// Completa a extensão do arquivo
		if ( ! preg_match('/\.css$/i', $asset_name) ) $asset_name .= '.css';
		
		$attribute_str = _parse_asset_html($attributes);
		
		$url = css_url($asset_name, $module_name);
		
		return _include_once_pack('<link href="' . $url . '" rel="stylesheet" type="text/css"'.$attribute_str.' />', $url);
	}
}


/**
 * Retorna um arquivo LESS compilado
 */
if( !function_exists('less_url') ){
	function less_url($asset_name, $module_name = NULL)
	{
		if(_is_external_asset($asset_name)) return $asset_name;

		$base_url = base_url();

		// Posiciona o arquivo de LESS compilado
		$CI =& get_instance();
		$minify = &_get_minify();

		// Verifica se é possível encontrar a classe de minimização
		if( class_exists('Minify') ) {
			$cache = _locate_cache($asset_name, $module_name, 'css');
			$location = _locate_asset($asset_name, $module_name, 'css', APPPATH.'assets/');
			$asset_location = $minify->less($location, $cache, (ENVIRONMENT !== 'development') );
		}else{
			$asset_location = _locate_asset($asset_name, $module_name, 'css', APPPATH.'assets/');
		}

		// Engana o navegador para ignorar o cache sempre que arquivo for modificado
		$size = is_file($asset_location)?filemtime($asset_location):0;
		return _add_params_get( $base_url . $asset_location, array('cache'=>$size));
	}
}

/**
 * Retorna um arquivo LESS em uma tag CSS
 */
if( !function_exists('less') ){
	function less($asset_name, $module_name = NULL, $attributes = array())
	{
		// Completa a extensão do arquivo
		if ( ! preg_match('/\.less$/i', $asset_name) ) $asset_name .= '.css';

		$attribute_str = _parse_asset_html($attributes);
		$url = less_url($asset_name, $module_name);
		return _include_once_pack('<link href="' . $url . '" rel="stylesheet/less" type="text/css"'.$attribute_str.' />', $url);
	}
}

/**
 * Retorna um arquivo de JS minificado
 */
if( !function_exists('js_url') ){
	function js_url($asset_name, $module_name = NULL)
	{
		if(_is_external_asset($asset_name)) return $asset_name;

		$base_url = base_url();
		if( ENVIRONMENT == 'development' ){
			// Posiciona o arquivo de JS minificado
			$CI =& get_instance();
			$minify = &_get_minify();
			$cache = _locate_cache($asset_name, $module_name, 'js');
			$location = _locate_asset($asset_name, $module_name, 'js', APPPATH.'assets/');
			$asset_location = $minify->js($location, $cache);
		}else{
			$asset_location = _locate_asset($asset_name, $module_name, 'js', APPPATH.'assets/');
		}

		// Engana o navegador para ignorar o cache sempre que arquivo for modificado
		$size = is_file($asset_location)?filemtime($asset_location):0;
		return _add_params_get( $base_url . $asset_location, array('cache'=>$size));
	}
}

/**
 * Retorna um arquivo de JS minificado
 */
if( !function_exists('js') ){
	function js($asset_name, $module_name = NULL)
	{
		// Completa a extensão do arquivo
		if ( ! preg_match('/\.js$/i', $asset_name) ) $asset_name .= '.js';
		
		$url = js_url($asset_name, $module_name);
		
		return _include_once_pack('<script type="text/javascript" src="' . $url . '"></script>', $url);
	}
}

/**
  * Image Asset Helper
  *
  * Helps generate CSS asset locations.
  *
  * @access		public
  * @param		string    the name of the file or asset
  * @param		string    optional, module name
  * @return		string    full url to image asset
  */
if( !function_exists('img_url') ){
	function img_url($asset_name, $module_name = NULL)
	{
		return other_asset_url($asset_name, $module_name, 'images');
	}
}

// ------------------------------------------------------------------------

/**
  * Image Asset HTML Helper
  *
  * Helps generate image HTML.
  *
  * @access		public
  * @param		string    the name of the file or asset
  * @param		string    optional, module name
  * @param		string    optional, extra attributes
  * @return		string    HTML code for image asset
  */
if( !function_exists('img') ) {
	function img($asset_name, $module_name = '', $attributes = array())
	{
		$attribute_str = _parse_asset_html($attributes);

		return '<img src="'.img_url($asset_name, $module_name).'"'.$attribute_str.' />';
	}
}

if( !function_exists('gl_maps') ){

}

if( !function_exists('gl_analytics') ){
	function gl_analytics(){
		$a = <<<"EOF"
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-2299975-1']);
_gaq.push(['_trackPageview']);

(function() {
var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
EOF
;
	}
}
