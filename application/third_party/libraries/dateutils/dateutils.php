<?
/**
 *	Efetua operações sobre datas
 */
if(!class_exists("DateUtils")){
	define('DATE_SQL', "%Y-%M-%D");
	define('TIME_SQL', "%Y-%M-%D");
	define('DATETIME_SQL', "%Y-%M-%D");

	define('DATE_BR', "%D/%M/%Y");
	define('TIME_BR', "%H:%i:%s");
	define('DATETIME_BR', "%D/%M/%Y %H:%i:%s");

	define('DATE_EN', "%M/%D/%Y");
	define('TIME_EN', "%H:%i:%s");
	define('DATETIME_EN', "%M/%D/%Y %H:%i:%s");

	define('DAY', "%D");
	define('MONTH', "%M");
	define('YEAR', "%Y");
	define('HOUR', "%H");
	define('MINUTE', "%I");
	define('SECOND', "%S");


	/**
	 * Define um validador de datas em diversos formatos 
	 */
	class DateUtils{
		const DATE_SQL		= "%Y-%M-%D";
		const TIME_SQL		= "%H:%i:%s";
		const DATETIME_SQL	= "%Y-%M-%D %H:%i:%s";
	
		const DATE_BR		= "%D/%M/%Y";
		const TIME_BR		= "%H:%i:%s";
		const DATETIME_BR	= "%D/%M/%Y %H:%i:%s";
	
		const DATE_EN		= "%M/%D/%Y";
		const TIME_EN		= "%H:%i:%s";
		const DATETIME_EN	= "%M/%D/%Y %H:%i:%s";
	
		const DAY			= "%D";
		const MONTH			= "%M";
		const YEAR			= "%Y";
		const HOUR			= "%H";
		const MINUTE		= "%I";
		const SECOND		= "%S";
	
		/**
		 * Valida uma data contra um formato específico
		 */
		function validate($date, $formatHint = DateUtils::DATETIME_BR){
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
		
		/**
		 * Cria um timestamp a partir de uma data
		 */
		function createTimestamp($date, $formatHint = DateUtils::DATETIME_BR){
			if(!DateUtils::validate($date, $formatHint)) return false;
			$d = 0; $m = 0; $y = 0; $h = 0; $i = 0; $s = 0;
			
			//Lê cada caractere do formato
			$dt = 0; $tmp = null;
			for($c = 0; $c < strlen($formatHint); $c++){
				if($formatHint[$c] == "%"){
					//Identifica o token
					$keychar = strtolower($formatHint[++$c]);
					$break = isset($formatHint[$c+1])?$formatHint[$c+1]:"";
					
					//Varre o valor encontrado
					$tmp = null;
					while($dt < strlen($date) && ($date[$dt] != $break || strpos("0123456789", $date[$dt]) > 0)){
						$tmp = $tmp . $date[$dt++];
					}
					
					//Carrega o valor encontrado na variavel correta
					$$keychar = $tmp;
				}else $dt++;
			}
			
			if($d == 0) $d = (int)date("d");
			if($y == 0) $y = (int)date("Y");
			date_default_timezone_set('America/Sao_Paulo');
			return mktime($h, $i, $s, $m, $d, $y );
		}
	
		/**
		 * Compara dois timestamps e retorna a diferença em segundos
		 */
		function compare($timestamp1, $timestamp2){
			return abs($timestamp2 - $timestamp1);
		}
		
		/**
		 * Compara dois timestamps e retorna a diferença no formato
		 */
		function compareTimes($timestamp1, $timestamp2){
			$diff = array();
			$secs = DateUtils::compare($timestamp1, $timestamp2);
			$tmp = $secs;

			//Calcula quantos segundos de diferença
			$t = floor($secs / (3600 * 24));
			$tmp = $tmp - $t * (3600 * 24);
			$diff[DateUtils::DAY] = $t;

			$t = floor($tmp / 3600);
			$tmp = $tmp - $t * 3600;
			$diff[DateUtils::HOUR] = $t;
			
			$t = floor($tmp / 60);
			$tmp = $tmp - $t * 60;
			$diff[DateUtils::MINUTE] = $t;
			$diff[DateUtils::SECOND] = $tmp;
			
			return $diff;
		}

		/**
		 * Create timespan
		 */
		function createTimespan($timestamp1, $timestamp2){
			$diff = DateUtils::compareTimes($timestamp1, $timestamp2);
			$span = "";
			if($diff[DateUtils::DAY] > 0) $span .= $diff[DateUtils::DAY]."d ";
			if($diff[DateUtils::DAY] > 0 || $diff[DateUtils::HOUR] > 0) $span .= $diff[DateUtils::HOUR] . "h:";
			$span .= $diff[DateUtils::MINUTE] ."m";
			return $span;
		}
	
		/**
		 * Converte o formato da data em outro
		 */
		function format($date, $targetFormat, $formatHint = DateUtils::DATETIME_BR){
			if(!DateUtils::validate($date, $formatHint)) return false;
			$phpfmt = strtoupper($targetFormat);
			$phpfmt = str_replace(DateUtils::HOUR, "H", $phpfmt);
			$phpfmt = str_replace(DateUtils::MINUTE, "i", $phpfmt);
			$phpfmt = str_replace(DateUtils::SECOND, "s", $phpfmt);
			$phpfmt = str_replace(DateUtils::DAY, "d", $phpfmt);
			$phpfmt = str_replace(DateUtils::MONTH, "m", $phpfmt);
			$phpfmt = str_replace(DateUtils::YEAR, "Y", $phpfmt);
			
			$time = DateUtils::createTimestamp($date, $formatHint);
			return date($phpfmt, $time);
		}

	}
}
/*
		$this->load->library('Datetransform');
		echo "Validacao<br>";
		echo "29/02/2008: " . ($this->datetransform->validate("29/02/2008")?"Valido":"Invalido") . "<br>";;
		echo "29/02/2007: " . ($this->datetransform->validate("29/02/2007")?"Valido":"Invalido"). "<br>";
echo "<hr>";
		echo "Timestamp: <br>";
		echo date("d/m/Y", $this->datetransform->createTimestamp("29/08/2008" )) . "<br>";
		echo date("d/m/Y H:i:s", $this->datetransform->createTimestamp("2008-08-29 00:10:00", DateUtils::DATETIME_SQL )) . "<br>";;
echo "<hr>";
		echo "Comparacao<br>";
		$a = $this->datetransform->compareTimes(time(), strtotime("-1 day"));
		echo "-1 dia: "; var_dump($a);
		echo "<br>";
		$a = $this->datetransform->compareTimes(time(), strtotime("72 hours"));
		echo "3 days: ";var_dump($a);
		echo "<br>";
		echo "<br>";
echo "<hr>";
		echo "Formatacao<br>";
		echo "d18/08/2008* to SQLDATE: ". $this->datetransform->format("d18/08/2008*", DateUtils::DATETIME_SQL, "d".DateUtils::DATE_BR."*") . "<br>";
		echo $this->datetransform->format("d18/08/2008*", DateUtils::DATETIME_SQL, "d".DateUtils::DATE_BR."*") . "<br>";
*/
?>
