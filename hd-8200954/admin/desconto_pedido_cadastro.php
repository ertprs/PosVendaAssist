<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_GET["desconto_pedido"]) > 0)  $desconto_pedido = trim($_GET["desconto_pedido"]);
if (strlen($_POST["desconto_pedido"]) > 0) $desconto_pedido = trim($_POST["desconto_pedido"]);

if (strlen($_GET["cadastrar"]) > 0)  $cadastrar = trim($_GET["cadastrar"]);
if (strlen($_POST["cadastrar"]) > 0) $cadastrar = trim($_POST["cadastrar"]);

if ($btnacao == "gravar") {

	$desconto        = trim($_POST["desconto"]);
	$data_vigencia   = trim($_POST["data_vigencia"]);
	$termino_vigencia= trim($_POST["termino_vigencia"]);

	$desconto = str_replace(",","",$desconto);

	if (strlen($desconto)==0){
		$msg_erro .= "Informe o desconto!";
	}else{
		if ($desconto > 100){
			$msg_erro .= "O desconto não pode ser superior a 100%";
		}
		$xdesconto = $desconto;
	}

	if (strlen($data_vigencia) > 0) {
		$xdata_vigencia = formata_data ($data_vigencia);
		$xdata_vigencia = $xdata_vigencia." 00:00:00";
	}

	if (strlen($termino_vigencia) > 0) {
		$xtermino_vigencia = formata_data ($termino_vigencia);
		$xtermino_vigencia = $xtermino_vigencia." 23:59:59";
	}

	if (strlen($xdata_vigencia)==0){
		$xdata_vigencia = " NULL ";
	}else{
		$xdata_vigencia = "'".$xdata_vigencia."'";
	}
	
	if (strlen($xtermino_vigencia)==0){
		$xtermino_vigencia = " NULL ";
	}else{
		$xtermino_vigencia = "'".$xtermino_vigencia."'";
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if(strlen($msg_erro) == 0){

		if (strlen($desconto_pedido)>0){
			$sql = "UPDATE  tbl_desconto_pedido SET
							desconto         = $xdesconto,
							data_vigencia    = $xdata_vigencia,
							termino_vigencia = $xtermino_vigencia
					WHERE tbl_desconto_pedido.desconto_pedido = $desconto_pedido
					AND   tbl_desconto_pedido.fabrica         = $login_fabrica;";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}else{
			$sql = "INSERT INTO tbl_desconto_pedido (
							fabrica          ,
							desconto         ,
							data_vigencia    ,
							termino_vigencia 
						) VALUES (
							$login_fabrica   ,
							$xdesconto       ,
							$xdata_vigencia  ,
							$xtermino_vigencia
						)";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}
	}

	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($desconto_pedido) > 0) {
	$sql = "SELECT 	tbl_desconto_pedido.desconto_pedido     ,
					TO_CHAR(tbl_desconto_pedido.data,'DD/MM/YYYY') AS data,
					tbl_desconto_pedido.desconto            ,
					TO_CHAR(tbl_desconto_pedido.data_vigencia,'DD/MM/YYYY') AS data_vigencia,
					TO_CHAR(tbl_desconto_pedido.termino_vigencia,'DD/MM/YYYY') AS termino_vigencia
			FROM    tbl_desconto_pedido
			WHERE   tbl_desconto_pedido.desconto_pedido  = $desconto_pedido
			AND     tbl_desconto_pedido.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$desconto_pedido     = trim(pg_result($res,0,desconto_pedido));
		$data                = trim(pg_result($res,0,data));
		$desconto            = trim(pg_result($res,0,desconto));
		$data_vigencia       = trim(pg_result($res,0,data_vigencia));
		$termino_vigencia    = trim(pg_result($res,0,termino_vigencia));
	}
}

$layout_menu = "cadastro";
$title = "Cadastro de Desconto de Pedido";
include 'cabecalho.php';

?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('input[@rel=data]').datePicker({startDate:'01/01/2000'});
		$("input[@rel=data]").maskedinput("99/99/9999");
		$("input[@rel=data2]").maskedinput("99/99/9999");
	});

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;

}

</style>
<? if(strlen($msg_erro) > 0){ ?>
<TABLE  width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<TR align='center'>
	<TD class='error'><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<? } ?>

<? if (strlen($desconto_pedido) > 0 OR $cadastrar == '1') {?>
<br>
<form method='POST' name="frm_cadastro" action="<? echo $PHP_SELF; ?>">
<input type='hidden' name='desconto_pedido' value='<? echo $desconto_pedido; ?>'>
<input type='hidden' name='btnacao' value=''>

<table width="250px" cellpadding="3" align='center' cellspacing="2" border="0">
	<TR class="menu_top">
		<TD colspan='2'>Cadastro de Desconto de Pedido</TD>
	</TR>
	<TR class="menu_top">
		<TD align='center'>Desconto</TD>
	</TR>
	<TR class="table_line">
		<Td align='center'><input size='12' maxlength='10' type='text' name='desconto' id='desconto' value='<?=$desconto?>' class='frm' style='text-align:right'></td>
	</TR>
</table>

<table width="250px" cellpadding="3" align='center' cellspacing="2" border="0">
	<TR class="menu_top">
		<TD>Data Início</TD>
		<TD>Data Término</TD>
	</TR>
	<TR class="table_line">
		<TD align='center'><INPUT size='12' maxlength='10' TYPE='text' NAME='data_vigencia' id='data_vigencia' rel='data' value='<?=$data_vigencia?>' class='frm'></TD>
		<TD align='center'><INPUT size='12' maxlength='10' TYPE='text' NAME='termino_vigencia' id='termino_vigencia' rel='data' value='<?=$termino_vigencia?>' class='frm'></TD>
	</TR>
</table>
<table width="250px" cellpadding="3" align='center' cellspacing="2" border="0">
<TR>

<TD><IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='gravar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor: pointer;"></TD>
<TD><IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor: pointer;"></TD>

</TR>
</table>
</FORM>

<BR>
<? } ?>

<BR>

<table width="400px" cellpadding="2" align='center' cellspacing="2" border="0">
	<caption class="menu_top">Desconto de Pedidos</caption>
	<TR class="menu_top">
		<TD>Data</TD>
		<TD>Desconto</TD>
		<TD>Data Início</TD>
		<TD>Data Término</TD>
		<TD>&nbsp;</TD>
	</TR>
<?
	$sql = "SELECT  TO_CHAR(tbl_desconto_pedido.data,'DD/MM/YYYY') AS data,
					tbl_desconto_pedido.desconto_pedido ,
					tbl_desconto_pedido.desconto        ,
					TO_CHAR(tbl_desconto_pedido.data_vigencia,'DD/MM/YYYY') AS data_vigencia,
					TO_CHAR(tbl_desconto_pedido.termino_vigencia,'DD/MM/YYYY') AS termino_vigencia
			FROM    tbl_desconto_pedido
			WHERE   tbl_desconto_pedido.fabrica = $login_fabrica
			ORDER BY tbl_desconto_pedido.data_vigencia ;";
	$res = @pg_exec ($con,$sql);
	
	for ($i=0; $i < pg_numrows($res); $i++) {
		$data				= trim(pg_result($res,$i,data));
		$desconto_pedido	= trim(pg_result($res,$i,desconto_pedido));
		$desconto			= trim(pg_result($res,$i,desconto));
		$data_vigencia		= trim(pg_result($res,$i,data_vigencia));
		$termino_vigencia	= trim(pg_result($res,$i,termino_vigencia));

		echo "<TR class='table_line'>";
		echo "<TD nowrap>".$data."</TD>";
		echo "<TD nowrap align='right'>".number_format($desconto,2,",","")."</TD>";
		echo "<td nowrap>".$data_vigencia."</td>";
		echo "<td nowrap>".$termino_vigencia."</td>";
		echo "<td nowrap><a href='$PHP_SELF?desconto_pedido=$desconto_pedido'>Alterar</a></td>";
		echo "</TR>";
	}

?>	
</table>

<p>
<a href='<?$PHP_SELF?>?cadastrar=1'>Cadastrar novo desconto</a>
</p>


<?	include "rodape.php"; ?>
