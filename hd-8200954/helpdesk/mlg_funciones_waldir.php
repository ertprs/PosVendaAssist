<?
/*	PHPDoc:
	@access:	public
	@author:	Manuel L�pez
	@copyright:	� 2008 Manuel L�pez
	@internal:	Variables, arrays y funciones varias de uso frecuente
	@version:	1.10
*/

#	Declara��o de vari�veis usadas normalmente
#	Dias e Meses do ano. Os dias come�am com o '0' em Domingo, para ficar
#   igual o padr�o do pSQL e PHP, fica mais f�cil de mexer
	$Dias['ptBR']	= array(0 => "Domingo",		"Segunda-feira","Ter�a-feira",
								 "Quarta-feira","Quinta-feira",	"Sexta-feira",
								 "S�bado",		"Domingo");

	$Dias['es']		= array(0 => "Domingo",	"Lunes",	"Martes", "Mi�rcoles",
								 "Jueves",	"Viernes",	"S�bado" );

	$Dias['en']		= array(0 => "Sunday",	"Monday", "Tuesday", "Wednesday",
								 "Thursday","Friday", "Saturday");

	$meses['ptBR']	= array(1 => "Janeiro", "Fevereiro","Mar�o",	"Abril",
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

	$estados = array("AC" => "Acre","AL" => "Alagoas",	"AM" => "Amazonas", 	"AP" => "Amap&aacute;",
					 "BA" => "Bahia",			"CE" => "Cear&aacute;",			"DF" => "Distrito Federal",
					 "ES" => "Esp&iacute;rito Santo",	"GO" => "Goi&aacute;s", "MA" => "Maranh&atilde;o",
					 "MG" => "Minas Gerais",	"MS" => "Mato Grosso do Sul",	"MT" => "Mato Grosso",
					 "PA" => "Par&aacute;",		"PB" => "Para&iacute;ba",		"PE" => "Pernambuco",
					 "PI" => "Piau&iacute;",	"PR" => "Paran&aacute;",		"RJ" => "Rio de Janeiro",
					 "RN" => "Rio Grande do Norte","RO" => "Rond&ocirc;nia",	"RR" => "Roraima",
					 "RS" => "Rio Grande do Sul","SC" => "Santa Catarina",		"SE" => "Sergipe",
					 "SP" => "S&atilde;o Paulo", "TO" => "Tocantins");

	$specialchars = "0123456789,/��������������������?�!��\\\"'@#~";

function iif($condition, $val_true, $val_false) {
		return ($condition) ? $val_true : $val_false;
}

function long_date($lang='ptBR', $time = "") {
	global $Dias,$meses;
	if ($lang = "pt-br") $lang = "ptBR";
	if ($time == "") $time = time();
	$diaSem = intval(date("w",$time));
	$mes	= intval(date("m",$time));
	$longdate = $Dias[$lang][$diaSem];
	$longdate.= ", " . date("d",$time);
	$longdate.= " de ";
	$longdate.= $meses[$lang][$mes];
	$longdate.= " de " . date("Y, H:i",$time);
	return $longdate;
}
function is_in($valor, $valores, $tipo="exact", $sep=",")
{	// BEGIN function is_in v2.0 (usa in_array para 'exact')
    // *** Precisa da fun��o iif ***
    // O 2� par�metro pode ser uma lista CSV ou um array
    // O 3� par�metro � opcional, seleciona o tipo de busca: exata, desde o come�o, desde o final, em qualquer parte
    // O 4� par�metro � opcional, trata-se do separador da lista, se quiser usar um outro
	// Devolve 'true' se o $valor � um dos $valores, 'false' se n�o est�, 'null' se uma
	// das duas vari�veis � "" ou n�o � separador em $valores
	if (is_null($valor) or is_null($valores) or ($valor=="") or ($valores=="")
	    or (is_bool(strpos($valores, $sep)) and !is_array($valores))) {
			return null;
	}

	$a_valores = iif((is_array($valores)),$valores,explode($sep,$valores));
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

function is_between($valor,$min,$max)
{   // BEGIN function is_between
    // *** Precisa da fun��o iif ***
    // Devolve 'true' se o valor est� entre ("between") o $min e o $max
	return iif(($valor >= $min AND $valor <= $max),true,false);
}// Fim is_between

function is_even($num) {
//  true se for par, false se for �mpar, null se n�o for um valor v�lido
    if (!is_numeric($num))  return null;
    return iif((is_integer($num/2)),true,false);
}

//  Limpa a string para evitar SQL injection
function anti_injection($string) {
	$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
	return strtr(strip_tags(trim($string)), $a_limpa);
}

//	aqui eu pego todos os form_email vindos do form
//	e tratos todos de uma vez e j� cria as variaveis correspondentes
foreach ($_REQUEST as $campo => $valor) {
	$$campo = anti_injection ($valor);
}
echo $qtde_item;
function is_email($email=""){   // False se n�o bate...
	return (preg_match("/^[a-z]+([\._\-]?[a-z0-9]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email));
}

function tira_acentos ($texto) {
	$acentos      = array("com" => "������������������������������������������",
						  "sem"	=> "aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC");
	return strtr($texto,$acentos['com'], $acentos['sem']);
}

function change_case($texto, $l_u = 'lower') {
	$acentos      = array("lower"	=> "���������������������",
						  "upper"	=> "���������������������");
    if (substr($l_u, 0, 1) == 'l') {
		return strtr(strtolower($texto), $acentos['upper'], $acentos['lower']);
	} else {
		return strtr(strtoupper($texto), $acentos['lower'], $acentos['upper']);
    }
}
/*
<!--[if lt IE 8]>
	<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script>
<![endif]-->
http://ie7-js.googlecode.com/svn/version/ie7.gif
*/
?>