<?

function formata_data ($data) {

	$data     = str_replace ("-","",$data);
	$data     = str_replace ("/","",$data);
	$data     = str_replace (".","",$data);

	$aux_ano  = substr ($data,4,4);
	$aux_mes  = substr ($data,2,2);
	$aux_dia  = substr ($data,0,2);

	if (strlen (trim ($aux_ano)) == 2) {
		if ($aux_ano > 50) {
			$aux_ano = "19" . $aux_ano;
		}else{
			$aux_ano = "20" . $aux_ano;
		}
	}
	return $aux_ano . "-" . $aux_mes . "-" . $aux_dia;
}


function mostra_data ($data) {

	if (strlen ($data) == 0) return null;

	$data     = str_replace ("-","",$data);
	$data     = str_replace ("/","",$data);
	$data     = str_replace (".","",$data);

	$aux_ano  = substr ($data,0,4);
	$aux_mes  = substr ($data,4,2);
	$aux_dia  = substr ($data,6,2);

	return $aux_dia . "/" . $aux_mes . "/" . $aux_ano;
}

/*
======================================================================================================
Formata uma string para um valor aceito como moeda (REAIS)
- text                : string informada
Retornos:
- float8  : numero convertido
uso:
	echo fnc_limpa_moeda('01.234.567,89')."<br>";
	echo fnc_limpa_moeda('01234567,89')."<br>";
	echo fnc_limpa_moeda('01.234.567,00')."<br>";
	echo fnc_limpa_moeda('01234567.00')."<br>";
	echo fnc_limpa_moeda('01,234,567.89')."<br>";
	echo fnc_limpa_moeda('0123456789')."<br>";

======================================================================================================
*/

function fnc_limpa_moeda($text) {

	$text = trim($text) ;

	if (substr($text,1,1) == ',' OR substr($text,1,1) == '.')
		$text = '0'.$text;

	if (strlen($text) == 0){
		return false;
	}else{
		$m_pos = -1;
		while ($m_pos < strlen($text)){
			$m_pos ++;
			$m_letra = substr($text,$m_pos,1);
			if (strpos("\,\.", $m_letra) > 0){
				$m_letra = '*';
				$m_aux   = $m_pos;
			}
			if ($m_letra <> '*') $m_limpar = $m_limpar . $m_letra;
		}

		if ($m_aux > 0){
			$m_aux = strlen($text) - $m_aux;

			$m_limpar = fnc_so_numeros(substr($m_limpar,0,strlen($m_limpar)-$m_aux+1)) .".". fnc_so_numeros (substr($m_limpar,strlen($m_limpar)-$m_aux+1,$m_aux));
			$m_retorno = $m_limpar;
		}else{
			$m_limpar   = fnc_so_numeros($m_limpar) .'.00';
			$m_retorno  = $m_limpar;
		}
	}
	return $m_retorno;
}


/*-----------------------------------------------------------------------------
SoNumeros($string)
$string = para ser retirado somente os números
Pega uma string e retorna somente os numeros da mesma
-----------------------------------------------------------------------------*/
function fnc_so_numeros($string){
	$numeros = preg_replace("/[^0-9]/", "", $string);
	return trim($numeros);
}


function fnc_formata_data_pg ($string) {

	$xdata = trim ($string);
	$xdata = str_replace ('/','',$xdata);
	$xdata = str_replace ('-','',$xdata);
	$xdata = str_replace ('.','',$xdata);
	
	if (strlen ($xdata) > 0) {

		if (strlen ($xdata) >= 6) {
			$dia = substr ($xdata,0,2);
			$mes = substr ($xdata,2,2);
			$ano = substr ($xdata,4,4);

			if (strpos ($xdata,"/") > 0) {
				list ($dia,$mes,$ano) = explode ("/",$xdata);
			}
			if (strpos ($xdata,"-") > 0) {
				list ($dia,$mes,$ano) = explode ("-",$xdata);
			}
			if (strpos ($xdata,".") > 0) {
				list ($dia,$mes,$ano) = explode (".",$xdata);
			}
		}else{
			$dia = substr ($xdata,0,2);
			$mes = substr ($xdata,2,2);
			$ano = substr ($xdata,4,4);
		}

		if (strlen($ano) == 2) {
			if ($ano > 50) {
				$ano = "19" . $ano;
			}else{
				$ano = "20" . $ano;
			}
		}
		if (strlen($ano) == 1) {
			$ano = $ano + 2000;
		}

		$mes = "00" . trim ($mes);
		$mes = substr ($mes, strlen ($mes)-2, strlen ($mes));

		$dia = "00" . trim ($dia);
		$dia = substr ($dia, strlen ($dia)-2, strlen ($dia));

		$xdata = "'". $ano . "-" . $mes . "-" . $dia ."'";

	}else{
		$xdata = "null";
	}

	return $xdata;

}

?>
