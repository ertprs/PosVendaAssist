<?
/*	PHPDoc:
	@access:	public
	@author:	Manuel LÛpez
	@copyright:	© 2008 Manuel LÛpez
	@internal:	Variables, arrays y funciones varias de uso frecuente
	@version:	1.10
*/

#	DeclaraÁ„o de vari·veis usadas normalmente
#	Dias e Meses do ano. Os dias comeÁam com o '0' em Domingo, para ficar
#   igual o padr„o do pSQL e PHP, fica mais f·cil de mexer
	$Dias['pt-br']	= array(0 => "Domingo",		"Segunda-feira","TerÁa-feira",
								 "Quarta-feira","Quinta-feira",	"Sexta-feira",
								 "S·bado",		"Domingo");

	$Dias['es']		= array(0 => "Domingo",	"Lunes",	"Martes", "MiÈrcoles",
								 "Jueves",	"Viernes",	"S·bado" );

	$Dias['en']		= array(0 => "Sunday",	"Monday", "Tuesday", "Wednesday",
								 "Thursday","Friday", "Saturday");

	$meses['pt-br']	= array(1 => "Janeiro", "Fevereiro","MarÁo",	"Abril",
								 "Maio",	"Junho",	"Julho",	"Agosto",
								 "Setembro","Outubro",	"Novembro",	"Dezembro");

	$meses['es']	= array(1 => "Enero",	  "Febrero","Marzo",	"Abril",
								 "Mayo",	  "Junio",	"Julio",	"Agosto",
								 "Septiembre","Octubre","Noviembre","Diciembre");

	$meses['en']	= array(1 => "January",	 "February","March",	"April",
								 "May",		 "June",	"July",		"August",
								 "September","October", "November",	"December");

	$estados_BR	= array("AC", "AL", "AM", "AP", "BA", "CE", "DF", "ES", "GO",
						"MA", "MG", "MS", "MT", "PA", "PB", "PE", "PI", "PR",
						"RJ", "RN", "RO", "RR", "RS", "SC", "SE", "SP", "TO");

	$estados = array("AC" => "Acre",			"AL" => "Alagoas",			"AM" => "Amazonas",
					 "AP" => "Amap·",			"BA" => "Bahia",			"CE" => "Cear·",
					 "DF" => "Distrito Federal","ES" => "EspÌrito Santo",	"GO" => "Goi·s",
					 "MA" => "Maranh„o",		"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
					 "MT" => "Mato Grosso",		"PA" => "Par·",				"PB" => "ParaÌba",
					 "PE" => "Pernambuco",		"PI" => "PiauÌ",			"PR" => "Paran·",
					 "RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte","RO"=>"RondÙnia",
					 "RR" => "Roraima",			"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
					 "SE" => "Sergipe",			"SP" => "S„o Paulo",		"TO" => "Tocantins");

	$specialchars = ".,/∞™·‚‡„È‚ËÌÓÏÛÙÚı˙˘¸Òø?°!∑$%&()^*[]{}®Á«`¥|©Æ\\\"'@#~";

if (!function_exists('iif')) {
	function iif($condition, $val_true, $val_false = "") {
		if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
		if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
		return ($condition) ? $val_true : $val_false;
	}
}

if (!function_exists('long_date')) {
	function long_date($lang='pt-br', $time = "") {
		global $Dias,$meses;
		if ($lang == "") $lang = "pt-br";
		if ($time == "") $time = time();
		$diaSem = intval(date("w",$time));
		$mes	= intval(date("m",$time));
		$longdate = $Dias[$lang][$diaSem].", " .
					date("d",$time)." de ".
					$meses[$lang][$mes]." de " .
					date("Y, H:i",$time);
		return $longdate;
	}
}

if (!function_exists('date_to_timestamp')) {
	function date_to_timestamp($fecha='agora') { // $fecha formato DD/MM/YY [HH:MM:[:SS]] YYYY-MM-DD [H24:MI[:SS]] ou DD-MM-YYYY [H24:MI[:SS]]
	    if ($fecha=="hoje")  $fecha	= date('Y-m-d');
	    if ($fecha=="agora") $fecha	= date('Y-m-d H:i:s');
		list($date, $time)			= explode(' ', $fecha);
		if (strlen($date)==8) {
			list($day, $month, $year)	= preg_split('/[\/|\.|-]/', $date);
		} else {
			list($year, $month, $day)	= preg_split('/[\/|\.|-]/', $date);
		}
		if (strlen($year)==2 and strlen($day)==4) list($day,$year) = array($year,$day); // Troca a ordem de dia e ano, se precisar
		if ($time=="") $time = "00:00:00";
		list($hour, $minute, $second) = explode(':', $time);
		return @mktime($hour, $minute, $second, $month, $day, $year);
	}
}

if (!function_exists('is_in')) {
	function is_in($valor, $valores, $tipo="exact", $sep=",")
	{	// BEGIN function is_in v2.0 (usa in_array para 'exact')
	    // *** Precisa da funÁ„o iif ***
	    // O 2∫ par‚metro pode ser uma lista CSV ou um array
	    // O 3∫ par‚metro È opcional, seleciona o tipo de busca: exata, desde o comeÁo, desde o final, em qualquer parte
	    // O 4∫ par‚metro È opcional, trata-se do separador da lista, se quiser usar um outro
		// Devolve 'true' se o $valor È um dos $valores, 'false' se n„o est·, 'null' se uma
		// das duas vari·veis È "" ou n„o · separador em $valores
		if (is_null($valor) or is_null($valores) or ($valor=="") or ($valores=="")
		    or (is_bool(strpos($valores, $sep)) and !is_array($valores))) {
				return null;
		}

		$a_valores = iif((is_array($valores)),$valores,explode($sep,$valores));

	//  Compara datas
	//  Requires: date_to_timestamp()
	    if ($tipo== 'date') {
	        $datatest = date_to_timestamp($valor);
			$data_ini = date_to_timestamp($a_valores[0]);
			$data_fim = date_to_timestamp($a_valores[1]);
			return (($data_ini >= $datatest) and ($datatest <= $data_fim));
	    }

		if ($tipo = "exact"):
			$is_in = in_array($valor, $a_valores);
			return $is_in;
		endif;

	    foreach ($a_valores as $valor_i) {
			if ($tipo = "icase") $is_in = (strtolower($valor)==strtolower($valor_i));
			if ($tipo = "any")	 $is_in = (strpos($valor_i, $valor) > 0);
			if ($tipo = "start") $is_in = (substr($valor, 0, strlen($valor_i))==$valor_i);
			if ($tipo = "end")	 $is_in = (substr($valor, 0 - strlen($valor_i))==$valor_i);
			if ($is_in) break;
		}
		return $is_in;
	} // END function is_in
}

if (!function_exists('is_between')) {
	function is_between($valor,$min,$max)
	{   // BEGIN function is_between
	    // *** Precisa da funÁ„o iif ***
	    // Devolve 'true' se o valor est· entre ("between") o $min e o $max
		return iif(($valor >= $min AND $valor <= $max),true,false);
	}// Fim is_between
}

if (!function_exists('is_even')) {
	function is_even($num) {
	//  true se for par, false se for Ìmpar, null se n„o for um valor v·lido
	    if (!is_numeric($num))  return null;
	    return iif((is_integer($num/2)),true,false);
	}
}

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

if (!function_exists('is_email')) {
	function is_email($email=""){   // False se n„o bate...
		return (preg_match("/^[A-Za-z0-9._%-]+@([A-Za-z0-9.-]+){1,2}([.][A-Za-z]{2,4}){1,2}$/", $email));
	}
}

if (!function_exists('is_url')) {
	function is_url($url=""){   // False se n„o bate...
		return (preg_match("/^(https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*$/", $url));
	}
}

if (!function_exists('tira_acentos')) {
	function tira_acentos ($texto) {
		$acentos      = array("com" => "·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«",
							  "sem"	=> "aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC");
		return strtr($texto,$acentos['com'], $acentos['sem']);
	}
}

if (!function_exists('change_case')) {
	function change_case($texto, $l_u = 'lower') {
		$acentos      = array("lower"	=> "·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á",
							  "upper"	=> "¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«");
	    if (substr($l_u, 0, 1) == 'l') {
			return strtr(strtolower($texto), $acentos['upper'], $acentos['lower']);
		} else {
			return strtr(strtoupper($texto), $acentos['lower'], $acentos['upper']);
	    }
	}
}

if (!function_exists('p_echo')) {
	function p_echo ($str, $style = "") {echo "<p $style>".$str."</p>\n";}
}

if (!function_exists('pre_echo')) {
	function pre_echo ($str,$header="") {
		if ($header != "") p_echo ($header, " style='font-weight:bold'");
		echo "<pre>\n";
		print_r($str);
		echo "\n</pre>\n";
	}
}

if (!function_exists('ValidateBRTaxID')) {
	function ValidateBRTaxID ($TaxID,$return_str = true) {
		global $con;    // Para conectar com o banco...
	// 	echo "Validando $TaxID...<br>";
		$cpf = preg_replace("/\D/","",$TaxID);   // Limpa o CPF / CNPJ
	// 	echo "Validando $cpf...<br>";
		if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

		$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
		if ($res_cpf === false) {
			return ($return_str) ? pg_last_error($res_cpf) : false;
		}
		return ($return_str) ? $cpf : true;
	}
}
/*
if (!function_exists('check_post_field')) {
	function check_post_field($fieldname, $returns = false) {
		if (!isset($_POST[$fieldname])) return $returns;
		$data = anti_injection($_POST[$fieldname]);
	// 	echo "<p><b>$fieldname</b>: $data</p>\n";
		return (strlen($data)==0) ? $returns : $data;
	}
}
*/
if (!function_exists("file_put_contents")) {
	function file_put_contents($filename,$data,$append=false) {
	    $mode = ($append)?"ab":"wb";
// 	    if (!is_writable($filename)) return false;
		$file_resource = fopen($filename,$mode);
		if (!$file_resource===false):
		    system ("chmod 664 $filename");
			$bytes = fwrite($file_resource, $data);
		else:
		    return false;
		endif;
		fclose($file_resource);
		return $bytes;
	}
}

if (!function_exists('pg_quote')) {
	function pg_quote($str, $type_numeric = false) {
	    if (is_bool($str))	return ($str===true) ? 'TRUE':'FALSE';
		if (is_null($str))	return 'NULL';
		if (is_numeric($str) and $type_numeric) return $str;
		if (in_array($str,array('null','true','false'))) return strtoupper($str);
		return "'".pg_escape_string($str)."'";
	}
}

if (!function_exists('pg_where')) {
	function pg_where($campo,$valores,$numeric = false) {
	//  Confere valores especiais
		if (is_null($valores))	return "$campo IS ".iif((!is_bool($numeric)),$numeric).'NULL';
		if ($valores===true)	return "$campo IS ".iif((!is_bool($numeric)),$numeric).'TRUE';
		if ($valores===false)	return "$campo IS ".iif((!is_bool($numeric)),$numeric).'FALSE';
		if ($valores=='')       return $valores;
	//  Converte valores CSV para array
		if (!is_array($valores) && strpos($valores, ',')!==false) {
			$a_valores = array_map(trim, explode(',',$valores));
		} else {
		    $a_valores = iif((is_array($valores)),$valores,array($valores));
			$a_valores = array_filter($a_valores);
			if (!count($a_valores)) return false;
		}
		$sep = ($numeric) ? ',' : "','";    // separa com vÌrgulas se for numÈrico, coloca entre aspas se n„o for
	    $tmp_ret = implode($sep, array_filter($a_valores));
		if (!$numeric) $tmp_ret = "'$tmp_ret'";
		if ($campo == '') return $tmp_ret;  // para devolver sÛ os valores separados por vÌrgula, setar '$campo' como ''
		return (count($a_valores)>1) ? $tmp_ret = "$campo IN ($tmp_ret)" : "$campo = $tmp_ret";
	}
}

if (!function_exists('getPost')) {
	function getPost($param,$get_first = false) {
		if ($get_first) {
			if (isset($_GET[$param]))  return anti_injection($_GET[$param]);
			if (isset($_POST[$param])) return anti_injection($_POST[$param]);
		} else {
			if (isset($_POST[$param])) return anti_injection($_POST[$param]);
			if (isset($_GET[$param]))  return anti_injection($_GET[$param]);
		}
		return null;
	}
}

/*
<!--[if lt IE 8]>
	<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script>
<![endif]-->
http://ie7-js.googlecode.com/svn/version/ie7.gif
*/
?>
