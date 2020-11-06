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

function mostra_data_hora ($data) {

	if (strlen ($data) == 0) return null;

	$month   = substr($data,5,2);
	$date    = substr($data,8,2);
	$year    = substr($data,0,4);
	$hour    = substr($data,11,2);
	$minutes = substr($data,14,2);
	$seconds = substr($data,17,4);

	return $date."/".$month."/".$year." ".$hour.":".$minutes;
}

function fnc_formata_data_hora_pg ($data) {

	if (strlen ($data) == 0) return null;

	$xdata = $data.":00 ";
	$aux_ano  = substr ($xdata,6,4);
	$aux_mes  = substr ($xdata,3,2);
	$aux_dia  = substr ($xdata,0,2);
	$aux_hora = substr ($xdata,11,5).":00";

	return "'" . $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora . "'";
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
		$text = 'NULL';
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


// ###############################################################
// Funcao para calcular diferenca entre duas horas
// ###############################################################
function calcula_hora($hora_inicio, $hora_fim){
	// Explode
	$ehora_inicio = explode(":",$hora_inicio);
	$ehora_fim    = explode(":",$hora_fim);

	// Tranforma horas em minutos
	$mhora_inicio = ($ehora_inicio[0] * 60) + $ehora_inicio[1];
	$mhora_fim    = ($ehora_fim[0] * 60) + $ehora_fim[1];

	// Subtrai as horas
	$total_horas = ( $mhora_fim - $mhora_inicio );

	// Tranforma em horas
	$total_horas_div = $total_horas / 60;

	// Valor de horas inteiro
	$total_horas_int = intval($total_horas_div);

	// Resto da subtracao = pega minutos
	$total_horas_sub = $total_horas - ($total_horas_int * 60);
/*
	if($total_horas_sub<15){
		$total_horas_sub = 0;
	}elseif ($total_horas_sub>14 AND $total_horas_sub < 45){
		$total_horas_sub = 30;
	}else{
		$total_horas_sub = 0;
		$total_horas_int++;
	}
*/
	// Horas trabalhadas
	if ($total_horas_sub < 10) {
		$total_horas_sub = "0".$total_horas_sub;
	}
	$horas_trabalhadas = $total_horas_int.":".$total_horas_sub;

	// Retorna valor
	return $horas_trabalhadas;
}

function calcula_hora_simples($hora){
	// Explode
	$ehora = explode(":",$hora);

	$total_horas   = $ehora[0] * 60; 	// Tranforma em minutos
	$total_minutos = $ehora[1];		 	// atribui minutos

	$total_horas_minutos = $total_horas + $total_minutos; // soma horas tranformadas em minutos e minutos

	$horas_trabalhadas = ( intval($total_horas_minutos) / 60); // transforma em decimais

	// Retorna valor
	return $horas_trabalhadas;
}

//-----------------------------------------------------
//Funcao: validaCNPJ($cnpj) HD 34921
//Sinopse: Verifica se o valor passado é um CNPJ válido
// Retorno: Booleano
//-----------------------------------------------------
	function checa_cnpj($cnpj)
	{
		if ((!is_numeric($cnpj)) or (strlen($cnpj) <> 14))
		{
			return 2;
		}
		else
		{
			$i = 0;
			while ($i < 14)
			{
			$cnpj_d[$i] = substr($cnpj,$i,1);
			$i++;
			}
			$dv_ori = $cnpj[12] . $cnpj[13];
			$soma1 = 0;
			$soma1 = $soma1 + ($cnpj[0] * 5);
			$soma1 = $soma1 + ($cnpj[1] * 4);
			$soma1 = $soma1 + ($cnpj[2] * 3);
			$soma1 = $soma1 + ($cnpj[3] * 2);
			$soma1 = $soma1 + ($cnpj[4] * 9);
			$soma1 = $soma1 + ($cnpj[5] * 8);
			$soma1 = $soma1 + ($cnpj[6] * 7);
			$soma1 = $soma1 + ($cnpj[7] * 6);
			$soma1 = $soma1 + ($cnpj[8] * 5);
			$soma1 = $soma1 + ($cnpj[9] * 4);
			$soma1 = $soma1 + ($cnpj[10] * 3);
			$soma1 = $soma1 + ($cnpj[11] * 2);
			$rest1 = $soma1 % 11;
			if ($rest1 < 2)
			{
				$dv1 = 0;
			}
			else
			{
				$dv1 = 11 - $rest1;
			}
			$soma2 = $soma2 + ($cnpj[0] * 6);
			$soma2 = $soma2 + ($cnpj[1] * 5);
			$soma2 = $soma2 + ($cnpj[2] * 4);
			$soma2 = $soma2 + ($cnpj[3] * 3);
			$soma2 = $soma2 + ($cnpj[4] * 2);
			$soma2 = $soma2 + ($cnpj[5] * 9);
			$soma2 = $soma2 + ($cnpj[6] * 8);
			$soma2 = $soma2 + ($cnpj[7] * 7);
			$soma2 = $soma2 + ($cnpj[8] * 6);
			$soma2 = $soma2 + ($cnpj[9] * 5);
			$soma2 = $soma2 + ($cnpj[10] * 4);
			$soma2 = $soma2 + ($cnpj[11] * 3);
			$soma2 = $soma2 + ($dv1 * 2);
			$rest2 = $soma2 % 11;
			if ($rest2 < 2)
			{
				$dv2 = 0;
			}
			else
			{
				$dv2 = 11 - $rest2;
			}
			$dv_calc = $dv1 . $dv2;
			if ($dv_ori == $dv_calc)
			{
				return 0;
			}
			else
			{
				return 1;
			}
		}
	}


/*
	FUNÇÃO calcula_frete()
	@Parametros: $cep_origem, $cep_destino. $peso, $codigo_servico
	@Retorno   : float;
	#HD 40324
*/
function calcula_frete($cep_origem,$cep_destino,$peso,$codigo_servico = null ){

	$url = "www.correios.com.br";
	$ip = gethostbyname($url);
	$fp = fsockopen($ip, 80, $errno, $errstr, 10);

	if ($codigo_servico == null){
		$codigo_servico     = "40010"; #Código SEDEX
	}

	if (strlen($cep_origem)>0 AND strlen($cep_destino)>0 AND strlen($peso)>0){
		$saida  = "GET /encomendas/precos/calculo.cfm?servico=$codigo_servico&CepOrigem=$cep_origem&CepDestino=$cep_destino&Peso=$peso HTTP/1.1\r\n";
		$saida .= "Host: www.correios.com.br\r\n";
		$saida .= "Connection: Close\r\n\r\n";
		fwrite($fp, $saida);

		$resposta = "";
		while (!feof($fp)) {
			$resposta .= fgets($fp, 128);
		}
		fclose($fp);
		#echo htmlspecialchars ($resposta);

		$posicao = strpos ($resposta,"Tarifa=");
		$tarifa  = substr ($resposta,$posicao+7);
		$posicao = strpos ($tarifa,"&");
		$tarifa  = substr ($tarifa,0,$posicao);
		return $tarifa;
	}else{
		return null;
	}
}

?>
