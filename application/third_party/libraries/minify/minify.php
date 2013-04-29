<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('lessc.inc.php');
require_once('cssmin.php');
require_once('jsmin.php');

/**
 * Sistema de importação minimização de JS / CSS automáticos
 */
class Minify {
	/**
	 * Converte um arquivo LESS e minimiza o resultado
	 */
	public function less($inputFile, $outputFile, $minify = true){
		try{
			if( !is_file($inputFile) ) return false;
			// load the cache
			$cacheFile = $inputFile.".cache";
			if (file_exists($cacheFile)) {
				$cache = unserialize(file_get_contents($cacheFile));
			} else {
				$cache = $inputFile;
			}
		
			// Compila o css novo;
			$less = new lessc();
			$newCache = $less->cachedCompile($cache);
			if (!is_array($cache) || $newCache["updated"] > $cache["updated"]) {
				file_put_contents($cacheFile, serialize($newCache));
				// file_put_contents($outputFile, $newCache['compiled']);
				if( $minify ) {
					file_put_contents($outputFile, $this->css_minify($newCache['compiled']) );
				}else{
					file_put_contents($outputFile, $newCache['compiled']);
				}
			}

			return $outputFile;
		}catch(Exception $ex){
			return false;
		}
	}

	/**
	 * Minimiza um arquivo de CSS
	 */
	public function css($inputFile, $outputFile, $return = false, $basepath = null){
		// Se existir cache ou estiver desatualizado
		if( !is_file($inputFile) ) return false;
		if( !is_file($outputFile) || filemtime($inputFile) > filemtime($outputFile) ) {
			// Le o arquivo e minimiza ele
			$css = file_get_contents($inputFile);
			$minified = $this->css_minify($css, true, $basepath);
			file_put_contents($outputFile, $minified);

			if( $return ) return $minified;
			else return $outputFile;
		}else{
			if( $return ) return file_get_contents($outputFile);
			else return $outputFile;
		}
	}

	/**
	 * Minimiza um conteudo CSS
	 */
	private function css_minify($content, $preserveComments = true, $prependRelativePath = null){
		// Abre o arquivo de entrada
		$min = new Minify_CSS();
		return $min->minify($content, array(
				'preserveComments'=> $preserveComments, 
				'prependRelativePath' => $prependRelativePath
			)
		);
	}

	/**
	 * Minimiza um arquivo de JS
	 */
	public function js($inputFile, $outputFile, $return = false){
		// Se existir cache ou estiver desatualizado
		if( !is_file($inputFile) ) return false;
		if( !is_file($outputFile) || filemtime($inputFile) > filemtime($outputFile) ) {
			// Le o arquivo e minimiza ele
			$js = file_get_contents($inputFile);
			$minified = $this->js_minify($js);
			file_put_contents($outputFile, $minified);

			if( $return ) return $minified;
			else return $outputFile;
		}else{
			if( $return ) return file_get_contents($outputFile);
			else return $outputFile;
		}
	}

	/**
	 * Minimiza um fonte JS
	 */
	private function js_minify($content){
		$min = JSMin::minify($content);
		return $min;
	}
}
