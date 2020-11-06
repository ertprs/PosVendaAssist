<?

include "autentica_validade_senha.php";

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
	<meta name      ="KeyWords"      content="Servicio técnico, Servicios, manutención, Internet, webdesign, orcamiento, comercial, joias, call center">

	<link type="text/css" rel="stylesheet" href="css/css.css">
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

</script>

<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' >
<?


if($login_pais=='AR') $bandeira = 'bandeira-argentina.gif';
if($login_pais=='CO') $bandeira = 'bandeira-colombia.gif' ;
if($login_pais=='UY') $bandeira = 'bandeira-uruguay.gif'  ;
if($login_pais=='MX') $bandeira = 'bandeira-mexico.gif'   ;
if($login_pais=='CL') $bandeira = 'bandeira-chile.gif'    ;
if($login_pais=='VE') $bandeira = 'bandeira-venezuela.gif';

if ($sem_menu == false OR strlen ($sem_menu) == 0 ) {

	$width_fixo = ($login_fabrica == 20) ? "width = '700px'" : "";

	echo "<table $width_fixo border='0' cellpadding='0' cellspacing='0' background='../imagens/fundo-cabecalho.png'  align = 'center'>";
	echo "<tr>";
	echo "<td width='100'><img src='../imagens/pixel.gif' width='30' height='1'></td>";
	echo "<td width='100%' align='center' valign='top'>";


	switch ($layout_menu) {
	case "gerencia":
		echo "<img src='imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
		$cor = "#E6D1DE";
		break;
	case "cadastro":
		echo "<img src='imagens_admin/btn_cadastro.gif' usemap='#menu_map'>";
		$cor = "#FFFDBE";
		break;
	case "tecnica":
		echo "<img src='imagens_admin/btn_tecnica.gif' usemap='#menu_map'>";
		$cor = "#C4E6F8";
		break;
	case "financeiro":
		echo "<img src='imagens_admin/btn_financeiro.gif' usemap='#menu_map'>";
		$cor = "#FEEFB6";
		break;
	case "auditoria":
		echo "<img src='imagens_admin/btn_auditoria.gif' usemap='#menu_map'>";
		$cor = "#C2BCD6";
		break;
	default:
		echo "<img src='imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
		$cor = "#E6D1DE";
		break;
	}
	#echo "<img src='../imagens/$bandeira'>";
	echo "</td>";

	if ($login_fabrica == "10") $prefixo = 'adm_';

/*	$sql  = "SELECT COUNT(*) FROM (
				SELECT tbl_hd_chamado.admin , (SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY hd_chamado_item DESC LIMIT 1) AS admin_item
				FROM tbl_hd_chamado WHERE admin = $login_admin
			) As help WHERE admin <> admin_item ";
*/
	$sql = "SELECT COUNT (*) FROM (
				SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.status ,tbl_hd_chamado.admin ,
					(SELECT tbl_hd_chamado_item.admin
					FROM tbl_hd_chamado_item
					JOIN tbl_hd_chamado using(hd_chamado)
					WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					ORDER BY hd_chamado_item DESC LIMIT 1) AS admin_item
				FROM tbl_hd_chamado
				WHERE admin = $login_admin and upper(status) <> 'RESOLVIDO'
			) As help WHERE admin <> admin_item";
	$sql = "SELECT count(*)
			FROM tbl_hd_chamado
			WHERE admin = $login_admin
			AND (
				(exigir_resposta is TRUE and status<>'Resolvido')
				OR
				(resolvido is null and status='Resolvido')
				)
			AND status<>'Cancelado';";
	$resX = pg_exec ($con,$sql);
	$qtde_help = pg_result ($resX,0,0);
	if ($qtde_help == 0 OR strlen ($qtde_help) == 0) {
		echo "<td width='100' align='center' valign='top'><a href='../helpdesk/".$prefixo."chamado_detalhe.php' target='_blank'><img src='../helpdesk/imagem/help.png' width='30' alt='Sistema de HelpDesk TELECONTROL' border='0'></a></td>";
	}else{
		if ($qtde_help == 1) {
			$msg_help = "Usted tiene $qtde_help llamado pendiente, aguardando su respuesta" ;
		}else{
			$msg_help = "Usted tiene $qtde_help llamados pendientes, aguardando su respuesta" ;
		}
		echo "<td width='100' align='center' valign='top'><a href='../helpdesk/".$prefixo."chamado_lista.php' target='_blank'><img src='../helpdesk/imagem/help-vermelho.gif' width='30' alt='$msg_help' border='0'></a></td>";
	}

	echo "</tr>";
	echo "</table>";

	echo "<table $width_fixo border='0' cellpadding='0' cellspacing='0' background='../imagens/submenu_fundo_cinza.gif'  align = 'center'>";
	echo "<tr>";
	echo "<td width='100'><img src='../imagens/pixel.gif' width='30' height='1'></td>";
	echo "<td width='100%' align='center'>";

	switch ($layout_menu) {
	case "gerencia":
		include 'submenu_gerencia.php';
		break;
	case "cadastro":
		include 'submenu_cadastro.php';
		break;
	case "tecnica":
		include 'submenu_tecnica.php';
		break;
	case "financeiro":
		include 'submenu_financeiro.php';
		break;
	case "auditoria":
		include 'submenu_auditoria.php';
		break;
	default:
		include 'submenu_gerencia.php';
		break;
	}
	echo "</td>";

	echo "<td width='100'><img src='../imagens/pixel.gif' width='30' height='1'></td>";


	echo "</table>";

	echo "
	<map name='menu_map'>
	<area shape='rect' coords='014,0,090,24' href='menu_gerencia.php'>
	<area shape='rect' coords='100,0,176,24' href='menu_cadastro.php'>
	<area shape='rect' coords='190,0,263,24' href='menu_tecnica.php'>
	<area shape='rect' coords='276,0,353,24' href='menu_financeiro.php'>
	<area shape='rect' coords='362,0,439,24' href='menu_auditoria.php'>
	<area shape='rect' coords='450,0,527,24' href='http://www.bosch.com.br../'>
	</map>";

}

?>
<style>
.borda{


background: #ffffff url(imagens/fundo.png) 100% 0 no-repeat;

background-image: -moz-linear-gradient(right, <?php echo $cor; ?> , #ffffff 100%, #ffffff);/*Firefox*/

background-image: -webkit-gradient(linear, 100% 0, 0 0, from(<?php echo $cor; ?>), color-stop(1, white), to(rgba(255, 255, 255, 0))); /*Chrome*/

filter:  progid:DXImageTransform.Microsoft.gradient(GradientType=1,startColorstr='#ffffff', endColorstr='<?php echo $cor; ?>');/*IE 6 e 7*/

-ms-filter: "progid:DXImageTransform.Microsoft.gradient(GradientType=1,startColorstr='#ffffff', endColorstr='<?php echo $cor; ?>)"; /*IE 8*/

padding-right: 10px;
}

.borda2{
	border-top-width:medium;
	border-top-style:solid;
	border-top-color:#DEE3EF;

}

</style>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor="#5A6D9C" align="center">
	<tr height="25">
		<td width='10' style="border-bottom-width:medium; border-bottom-style:solid;
	border-bottom-color:#E4E4E4;"><img src="imagens/canto_esquerdo.jpg" /></td>
		<td style='font-size: 16px; font-weight: bold; font-family: arial;text-align: center; color:#FFFFFF; border-bottom-width:medium; border-bottom-style:solid;
	border-bottom-color:#E4E4E4;'> <? echo "$title" ?> </td>
		<td width='10' style="border-bottom-width:medium; border-bottom-style:solid;
	border-bottom-color:#E4E4E4;"><img src="imagens/canto_direito.jpg" /></td>
	</tr>
</table>

<table width="700px"  border="0" align="center" cellpadding="0" cellspacing="0" bordercolor="#D9E2EF">
<tr height="60">
	<td>
	<div style='float:left;'>
	<?php
		echo "<a href='$login_fabrica_site' target='_new'>";
        $imagensLogo = include('../logos.inc.php');
        $login_fabrica_logo = getFabricaLogo($login_fabrica, $imagensLogo);

		if ($login_login == 'suggar') {
			echo "<img src='../logos/suggar.jpg' alt='$login_fabrica_site' border='0' height='40'>";
		} elseif ($login_login == 'tulio' or ($login_login == 'sergio' and $login_fabrica != 46) or ($login_login == 'samuel' and 1==2)) {
			echo "<img src='../logos/telecontrol.jpg' alt='$login_fabrica_site' border='0' height='40'>";
		} else {
			echo "<img src='../logos/$login_fabrica_logo' alt='$login_fabrica_site' border='0' height='40'>";
		}
		echo "</a>";?>
	</div>
	</td>
<?
function escreveData($data) {
	$vardia = substr($data,8,2);
	$varmes = substr($data,5,2);
	$varano = substr($data,0,4);

	$convertedia = date ("w", mktime (0,0,0,$varmes,$vardia,$varano));

	$diaSemana = array("Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado");

	$mes = array(1=>"enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre");

	if ($varmes < 10) $varmes = substr($varmes,1,1);

	return $diaSemana[$convertedia] . ", " . $vardia  . " de " . $mes[$varmes] . " de " . $varano;
}
// Utilizar da seguinte maneira
//echo escreveData("2005-12-02");
?>
	<td style="font-size: 12px; font-family: arial;" align='right' class='borda'><b>
	<?php
		$data = date("Y-m-d");
		echo escreveData($data);
		//echo date(" - H:i");
		echo " <br /> Usuário: <font color='#FF0000'>".ucfirst($login_login)."</b></font>";
	?>
	</td>
	<td>
	<?
	 //----INICIO HELP----//
	 $local = $PHP_SELF;

		$sql = "SELECT * from tbl_help";
		$res = pg_exec ($con,$sql);

		if (@pg_numrows($res) >= 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$programa       = pg_result($res,$i,arquivo);
				$help           = pg_result($res,$i,help);

				$pos = strpos($local, $programa);
				if ($pos == true) {
//				echo"$programa<BR>";
				echo"
				<SCRIPT LANGUAGE='JavaScript'>
				function ajuda()
				{
				window.open('help.php?programa=$programa', 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');
				}
				//-->
				</SCRIPT>";
				echo"<A HREF='#' ONCLICK='ajuda()'>
				<img src='imagens/help.jpg' alt='Click aquí para obtener ayuda'>";

				echo "</A>";
				}
			}
		}
	//----FIM HELP----//
	?>

	</td>
</tr>
<?
	if(strlen($msg_validade_cadastro)>0){
	echo "<tr>";
	echo "<td align='center' colspan=3>$msg_validade_cadastro</td>";
	echo "</tr>";

} ?>
<tr>
	<td colspan=3><div class="frm-on-os" id="displayArea">&nbsp;</div></td>
</tr>
</TABLE>

<?
#------------- Programa Restrito ------------------#
$sql = "SELECT * FROM tbl_admin WHERE admin = $login_admin AND privilegios NOT ILIKE '%*%' ";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	$sql = "SELECT *
			FROM   tbl_programa_restrito
			WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
			AND    tbl_programa_restrito.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$sql = "SELECT *
				FROM   tbl_programa_restrito
				JOIN   tbl_admin USING (admin)
				WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
				AND    tbl_programa_restrito.admin    = $login_admin
				AND    tbl_programa_restrito.fabrica  = $login_fabrica ";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 0) {
			echo "<p><hr><center><h1>Sin permiso para acceder esse programa.</h1></center><p><hr>";
			exit;
		}
	}
}
//echo "<!-- restricao \n $sql -->";

?>
