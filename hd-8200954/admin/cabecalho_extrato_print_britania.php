<?
if($login_fabrica == 3 and $login_login <> 'samuel'){
	include "autentica_validade_senha.php";
//	echo "1";
}
include "monitora_cabecalho.php";
#header("Expires: 0");
#header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
#header("Pragma: no-cache, public");

/*$sql = "SELECT tbl_fabrica.multimarca,
				tbl_fabrica.acrescimo_tabela_base
		FROM   tbl_fabrica
		WHERE  tbl_fabrica.fabrica = $login_fabrica
		AND    tbl_fabrica.multimarca is true
		AND    tbl_fabrica.acrescimo_tabela_base is true;";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0){
	$multimarca            = trim(pg_result($res,0,multimarca));
	$acrescimo_tabela_base = trim(pg_result($res,0,acrescimo_tabela_base));
}*/

function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

function TempoExec($pagina, $sql, $time_start, $time_end){
	if (1 == 1){
		$time = $time_end - $time_start;
		$time = str_replace ('.',',',$time);
		$sql  = str_replace ('\t',' ',$sql);
#		$fp = fopen ("/home/telecontrol/tmp/postgres.log","a");
#		fputs ($fp,$pagina);
#		fputs ($fp,"#");
#		fputs ($fp,$sql);
#		fputs ($fp,"#");
#		fputs ($fp,$time);
#		fputs ($fp,"\n");
#		fclose ($fp);
	}
}

$micro_time_start = getmicrotime();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css.css">
	<link type="text/css" rel="stylesheet" href="css/tooltips.css">
</head>

<script>
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		document.getElementById("displayArea").innerHTML = sText;
	}

	function atualiza_dado(admin){
		window.open('atualiza_dado.php?admin='+admin, 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');
	}
</script>

<script language="javascript" src="js/assist.js"></script>

<? if (in_array($login_fabrica,array(14,43,66))) { ?>
<script type="text/javascript">
function setCookie(c_name,value,path,expiredays)
{
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	var expireDate = (expiredays==null) ? "" : ";expires="+exdate.toGMTString();
	var c_path     = (path == null) ? "" : ";path="+path;
	document.cookie=c_name+ "=" +escape(value)+c_path;
	window.location.reload();
}

function trocaFabrica(novoLogin) {
	login = novoLogin.split("|");
	setCookie("cook_admin",login[0],"/assist/");
	setCookie("cook_fabrica",login[1],"/assist/");
}

</script>
<?}?>
<!--
<body onLoad="fnc_preload();">
-->

<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' <?=$body_onload;?> >


<?
include ("email_admin_include.php");

?>
