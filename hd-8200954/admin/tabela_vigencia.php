<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_GET["tabela"]) > 0)  $tabela = trim($_GET["tabela"]);
if (strlen($_POST["tabela"]) > 0) $tabela = trim($_POST["tabela"]);


if ($btnacao == "gravar") {

	$data_vigencia   = trim($_POST["data_vigencia"]);
	$termino_vigencia= trim($_POST["termino_vigencia"]);

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

	if (strlen($tabela)==0){
		$msg_erro .= "Informe a tabela de preço!";
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if(strlen($msg_erro) == 0){
		$sql = "UPDATE  tbl_tabela SET
						data_vigencia    = $xdata_vigencia,
						termino_vigencia = $xtermino_vigencia
				WHERE tbl_tabela.tabela = $tabela
				AND    tbl_tabela.fabrica  = $login_fabrica;";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
	}

	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($tabela) > 0) {
	$sql = "SELECT 	tbl_tabela.tabela              ,
					tbl_tabela.sigla_tabela        ,
					tbl_tabela.descricao           ,
					TO_CHAR(tbl_tabela.data_vigencia,'DD/MM/YYYY') AS data_vigencia,
					TO_CHAR(tbl_tabela.termino_vigencia,'DD/MM/YYYY') AS termino_vigencia
			FROM    tbl_tabela
			WHERE   tbl_tabela.tabela  = $tabela
			AND     tbl_tabela.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$tabela              = trim(pg_result($res,0,tabela));
		$sigla_tabela        = trim(pg_result($res,0,sigla_tabela));
		$descricao           = trim(pg_result($res,0,descricao));
		$data_vigencia       = trim(pg_result($res,0,data_vigencia));
		$termino_vigencia    = trim(pg_result($res,0,termino_vigencia));
	}
}

$layout_menu = "cadastro";
$title = "Vigência da Tabela de Preço";
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

<? if (strlen($tabela) > 0) {?>
<br>
<form method=post name="frm_tabela" action="<? echo $PHP_SELF; ?>">
<input type='hidden' name='tabela' value='<? echo $tabela; ?>'>
<input type='hidden' name='btnacao' value=''>

<table width="250px" cellpadding="3" align='center' cellspacing="2" border="0">
	<TR class="menu_top">
		<TD colspan='2'>Vigência da Tabela Promocional</TD>
	</TR>
	<TR class="menu_top">
		<TD>Código</TD>
		<TD>Descrição</TD>
	</TR>
	<TR class="table_line">
		<TD><? echo $sigla_tabela ?></TD>
		<TD><? echo $descricao?></TD>
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

<TD><IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_tabela.btnacao.value == '' ) { document.frm_tabela.btnacao.value='gravar' ; document.frm_tabela.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor: pointer;"></TD>
<TD><IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_tabela.btnacao.value == '' ) { document.frm_tabela.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor: pointer;"></TD>

</TR>
</table>
</FORM>

<BR>
<? } ?>

<BR>

<table width="400px" cellpadding="2" align='center' cellspacing="2" border="0">
	<caption class="menu_top">Vigência das Tabelas Promocionais</caption>
	<TR class="menu_top">
		<TD>Código</TD>
		<TD>Descrição</TD>
		<TD>Data Início</TD>
		<TD>Data Término</TD>
	</TR>
<?
	$sql = "SELECT  DISTINCT
					tbl_tabela.tabela              ,
					tbl_tabela.sigla_tabela        ,
					tbl_tabela.descricao           ,
					tbl_tabela.descricao           ,
					TO_CHAR(tbl_tabela.data_vigencia,'DD/MM/YYYY') AS data_vigencia,
					TO_CHAR(tbl_tabela.termino_vigencia,'DD/MM/YYYY') AS termino_vigencia
			FROM    tbl_tabela
			JOIN    tbl_condicao ON tbl_condicao.tabela_promocao = tbl_tabela.tabela
			WHERE   tbl_tabela.fabrica = $login_fabrica
			ORDER BY tbl_tabela.sigla_tabela ;";
	$res = @pg_exec ($con,$sql);
	
	for ($i=0; $i < pg_numrows($res); $i++) {
		$tabela				= trim(pg_result($res,$i,tabela));
		$sigla_tabela		= trim(pg_result($res,$i,sigla_tabela));
		$descricao			= trim(pg_result($res,$i,descricao));
		$data_vigencia		= trim(pg_result($res,$i,data_vigencia));
		$termino_vigencia	= trim(pg_result($res,$i,termino_vigencia));

		echo "<TR class='table_line'>";
		echo "<TD nowrap>$sigla_tabela</TD>";
		echo "<TD align=left nowrap><a href='$PHP_SELF?tabela=$tabela'>$descricao</a></TD>";
		echo "<td nowrap>".$data_vigencia."</td>";
		echo "<td nowrap>".$termino_vigencia."</td>";
		echo "</TR>";
	}
?>
</table>



<?	include "rodape.php"; ?>
