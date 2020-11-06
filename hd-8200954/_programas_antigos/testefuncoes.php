<?

	echo fnc_limpa_moeda('1.000,89')."<br><br>";
	echo fnc_limpa_moeda('1000.00')."<br>";

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
echo "Text: $text <br>";

	if (strlen($text) == 0){
		return false;
	}else{
		$m_pos = -1;
		while ($m_pos < strlen($text)){
			$m_pos ++;
			$m_letra = substr($text,$m_pos,1);
echo "letra XXX: $m_letra <br>";
			if (strpos("\,\.", $m_letra) > 0){
				$m_letra = '*';
				$m_aux   = $m_pos;
echo "letra: $m_letra <br>";
			}
			if ($m_letra <> '*') $m_limpar = $m_limpar . $m_letra;
echo "limpar: $m_limpar <br>";
		}
echo "$m_pos <br>";
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

?>