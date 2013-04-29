<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Retorna o caminho do SPLIT para armazenamento da foto
 */ 
if ( ! function_exists('image_split'))
{
	function image_split($id){
		$path = substr(sprintf('%05d', $id), 0, 5);
		return implode('/', preg_split('//', $path, -1, PREG_SPLIT_NO_EMPTY) ).'/'.$id .'/';
	}
}


/**
 * Gera o caminho de thumbnail para uma imagem
 */
if ( ! function_exists('image_thumbnail_path')){
	function image_thumbnail_path($original, $suffix = '_thumb'){
		return preg_replace('/(\.[a-zA-Z]*)$/', "$suffix$1", $original);
	}
}

/**
 * Efetua o download de uma imagem para um arquivo temporario;
 */
if( !function_exists('image_download') ){
	function image_download($source, $target){
		$extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
		if( empty($extension) ) $extension = 'jpg';

		// Copia o arquivo para um diretório temporario
		$temp = tempnam($target, 'download') .'.'. $extension;
		@copy($source, $temp);

		return $temp;
	}
}


/**
 * Retorna um objeto imagick a partir de uma string
 */
if( !function_exists('image_open') )
{
	function image_open($original, $ping = false){
		if( empty($original) ) return null;
		if( is_string($original) && file_exists($original) ) {
			if( $ping ) {
				$image = new Imagick();
				$image->pingImage($original);
				$image->setImageFilename($original);
			}else {
				$image = new Imagick($original);
				$image->setImageFilename($original);
			}
		}else {
			if( $original instanceof Imagick ) { $image = $original; }
			else return null;
		}
		return $image;
	}
}


/**
 * Calcula o tamanho da imagem
 */
if( !function_exists('image_get_size') )
{
	function image_get_size($original){
		$image = image_open($original, true);
		if( $image == null ) return null;
		else return (object)$image->getImageGeometry();
	}
}


/**
 * Extrai os metadados da imagem
 */
if ( ! function_exists('image_get_metadata'))
{
	function image_get_metadata($original){
		$image = image_open($original, true);

		// Retorna os metadados
		$tmp = $image->getImageProperties('exif:*', true);
		$meta = array();
		foreach($tmp as $key => $value){
			$meta[ substr($key, 5) ] = $value;
		}
		return $meta;
	}
}


/**
 * Converta uma imagem de um formato qualquer para um formato indicado
 */
if ( ! function_exists('image_convert_jpeg'))
{
	function image_convert_jpeg($original, $quality = 100, $compression = Imagick::COMPRESSION_LOSSLESSJPEG){
		$target = 'JPEG';
		$image = image_open($original);
		if( $image == null ) return false;
		if( strtoupper($image->getImageFormat()) === strtoupper($target) ) return $image;

		// Converte o formato para o alvo
		$image->setImageFormat($target);
		$image->setCompression($compression);
		$image->setImageCompressionQuality($quality);
		
		return $image;
	}
}


/**
 * Efetua a impressão de uma imagem, imprimindo os headers necessários
 */
if ( ! function_exists('image_print') ){
	function image_print($image){
		$CI = &get_instance();
		$CI->load->helper('file');

		switch(true){
			case (is_string($image) && file_exists($image) ): 
				header('Content-type: ' . get_mime_by_extension($image) );
				echo file_get_contents($image);
				break;

			case ($image instanceof Imagick):
				header('Content-type: ' . get_mime_by_extension($image->getImageFormat()) );
				echo $image->getImageBlob();
				break;
			default:
				echo "IMAGEM INVALIDA";
			;
		}
	}
}

/**
 * Reduz o tamanho de uma imagem para um tamanho mínimo, tentando minimizar a perda de qualidade.
 */
if ( ! function_exists('image_shrink'))
{
	function image_shrink($original, $width, $height){
		$image = image_open($original);
		if( $image == null ) return false;

		$size = (object)$image->getImageGeometry();
		if( $size->width <= $width || $size->height <= $height ) return $image;
		$image->resizeImage($width, $height, IMAGICK::FILTER_LANCZOS, 1, true);
		return $image;
	}
}

/**
 * Gera uma imagem de baixa qualidade para thumbnail, cortando na proporção ou não
 */
if ( ! function_exists('image_thumbnail'))
{
	function image_thumbnail($original, $width, $height, $crop = false, $suffix = '_thumb'){
		$image = image_open($original);
		if( $image == null ) return false;
		$image = image_convert_jpeg($image);

		if( $crop ) $image->cropThumbnailImage($width, $height);
		else $image->thumbnailImage($width, $height, true);
		$image->stripImage();

		if( !empty($suffix) ){
			$thumbFile = image_thumbnail_path( $image->getImageFilename(), $suffix );
			$image->setImageFilename($thumbFile);
		}
		return $image;
	}
}


/**
 * Gera uma nova imagem com recorte em FIT
 * Ajusta a imagem para caber na proporção, permitindo bordas
 */
if ( ! function_exists('image_fit') ) 
{
	function image_fit($original, $width, $height, $preview = true, $border=0, $borderColor='#FFFFFF'){
		$image = image_open($original);
		if( $image == null ) return false;

		// Calcula a proporção da imagem final
		$size = (object)$image->getImageGeometry();
		$ratio = ($width > $height)?$width/$height:$height/$width;
		$img_ratio = ($size->width > $size->height)?$size->width/$size->height:$size->height/$size->width;

		// Protege o sistema contra imagens gigantes
		if( $ratio > 5 ) die("Proporção inválida");
		if( ($size->width*$size->height) > 4096000 ){
			$image = image_shrink($image, 1600, 1600);
			$size = (object)$image->getImageGeometry();
			$img_ratio = ($size->width > $size->height)?$size->width/$size->height:$size->height/$size->width;
		}

		// Identifica a relação de proporcionalidade
		if($ratio == $img_ratio) return $image;
		if( $ratio < $img_ratio ) {
			if( $size->width >= $size->height ) $photo = (object)array('width'=> ($size->width), 'height'=>($size->width/$ratio) );
			else $photo = (object)array('width'=> ($size->height/$ratio), 'height'=>($size->height) );
		}else{
			if( $size->width >= $size->height ) $photo = (object)array('width'=> ($size->height*$ratio), 'height'=>($size->height) );
			else $photo = (object)array('width'=> ($size->width), 'height'=>($size->width*$ratio) );
		}

		// Calcula o meio da imagem
		$x = ($photo->width - $size->width) / 2;
		$y = ($photo->height - $size->height) /2;

		// Cria uma nova imagem com o tamanho da atual
		if( $preview ) {
			$new = new Imagick();
			$new->newImage($photo->width, $photo->height, '#FF0000', $image->getImageFormat());
			$new->compositeImage($image, IMAGICK::COMPOSITE_COPY, $x, $y);
			return $new;
		}else{
			return $image;
		}
	}
}

/**
 * Gera uma nova imagem com recorte em FILL. 
 * Corta a imagem proporcionamente ao tamanho informado
 */
if ( ! function_exists('image_fill') ) 
{
	function image_fill($original, $width, $height, $preview = true, $border=0, $borderColor='#FFFFFF'){
		$image = image_open($original);
		if( $image == null ) return false;
		if( empty($width) ) return false;
		if( empty($height) ) return false;

		// Calcula a proporção da imagem final
		$size = (object)$image->getImageGeometry();
		$ratio = ($width > $height)?$width/$height:$height/$width;
		$img_ratio = ($size->width > $size->height)?$size->width/$size->height:$size->height/$size->width;
		
		// Protege o sistema contra imagens gigantes
		if( $ratio > 5 ) die("Proporção inválida");
		if( ($size->width*$size->height) > 4096000 ){
			$image = image_shrink($image, 1600, 1600);
			$size = (object)$image->getImageGeometry();
			$img_ratio = ($size->width > $size->height)?$size->width/$size->height:$size->height/$size->width;
		}

		if( $ratio == $img_ratio ) return $image;

		// Identifica a relação de proporcionalidade
		if( $ratio < $img_ratio ) {
			if( $size->width >= $size->height ) $photo = (object)array('width'=> ($size->height*$ratio), 'height'=>($size->height) );
			else $photo = (object)array('width'=> ($size->width), 'height'=>($size->width*$ratio) );
		}else{
			if( $size->width >= $size->height ) $photo = (object)array('width'=> ($size->width), 'height'=>($size->width/$ratio) );
			else $photo = (object)array('width'=> ($size->height/$ratio), 'height'=>($size->height) );
		}

		// Clona a imagem atual para servir de fundo
		if( $preview ) $clone = $image->clone();

		// Corta a imagem final
		$x = ($size->width - $photo->width) / 2;
		$y = ($size->height - $photo->height) /2;
		$image->cropImage(($photo->width-$border*2), ($photo->height-$border*2), ($x), ($y) );
		if( $border > 0 ) $image->borderImage($borderColor, $border, $border);

		if( $preview ){
			// Mescla a imagem de preview com a final
//			$clone->modulateImage(90, 0, 100);
			$clone->colorizeImage('#ff0000', 1);
			$clone->compositeImage($image, IMAGICK::COMPOSITE_COPY, ($x), ($y));
			return $clone;
		}else{
			return $image;
		}

	}
}


/* Verifica se o arquivo existe local ou remotamente */
if( !function_exists('image_exists') ){
	function image_exists($filename){
		$scheme = parse_url($filename, PHP_URL_SCHEME);
		if( $scheme == 'http' || $scheme == 'https') { 
			// Testa se o CURL está ativo
			if( !function_exists( 'curl_init' ) ) return false;

			// Efetua uma requisição de testes no Servidor
			$curl = curl_init($filename);
			curl_setopt($curl, CURLOPT_NOBODY, true);
			$result = curl_exec($curl);

			// Confere o resultado do CURL
			$ret = false;
			if ($result !== false) {
				$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
				if ($statusCode == 200)$ret = true;   
			}
			curl_close($curl);

			return $ret;
		} else return file_exists($filename);
	}
}
