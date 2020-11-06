<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_GET["escritorio_regional"]) > 0)  $escritorio_regional = trim($_GET["escritorio_regional"]);
if (strlen($_POST["escritorio_regional"]) > 0) $escritorio_regional = trim($_POST["escritorio_regional"]);

if ($btnacao == "gravar") {
	$descricao = trim($_POST["descricao"]);
	$ativo = trim($_POST["ativo"]);

	if (strlen($descricao) == 0) {
		$msg_erro = "Digite uma Descrição";
	}
	elseif (strlen($escritorio_regional)) {
		$sql = "
		UPDATE tbl_escritorio_regional
		SET
			descricao = '$descricao',
			ativo = '$ativo'
		WHERE
			escritorio_regional=$escritorio_regional
			AND fabrica=$login_fabrica
		";
		$res = pg_query($con, $sql);
		$msg_erro = pg_errormessage($con);
		
	}
	else {
		$sql = "
		INSERT INTO tbl_escritorio_regional(descricao, fabrica, ativo)
		VALUES('$descricao', $login_fabrica, '$ativo')
		";
		$res = pg_query($con, $sql);
		$msg_erro = pg_errormessage($con);
	}

	
	if(strlen($msg_erro)==0){
		header("location:" . $PHP_SELF . '?msg=Gravado com Sucesso!');
		die;
	}
}

if (strlen($escritorio_regional) > 0) {
	$sql = "
	SELECT
	escritorio_regional,
	descricao,
	ativo

	FROM
	tbl_escritorio_regional

	WHERE
	fabrica=$login_fabrica
	AND escritorio_regional=$escritorio_regional

	ORDER BY
	ativo,
	descricao
	";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$escritorio_regional = pg_result($res, 0, escritorio_regional);
		$descricao = pg_result($res, 0, descricao);
		$ativo = pg_result($res, 0, ativo);
	}
}

$layout_menu = "cadastro";
$title = strtoupper("Cadastramento de EscritÓrios Regionais");
include 'cabecalho.php';

?>

<style type="text/css">

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.espaco td{
	padding:10px 0 10px;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}


</style>
<? if(strlen($msg_erro) > 0){ ?>
<TABLE  width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<TR align='center'>
	<TD class='msg_erro'><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<? } ?>
<? if(isset($_GET['msg']) ) { ?>
<TABLE  width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<TR align='center'>
	<TD class='sucesso'><? echo $_GET['msg']; ?></TD>
</TR>
</TABLE>
<? } ?>

<FORM METHOD=POST NAME="frm_escritorio_regional" ACTION="<? echo $PHP_SELF; ?>">
<input type='hidden' name='escritorio_regional' value='<? echo $escritorio_regional; ?>'>
<input type='hidden' name='btnacao' value=''>

<?php

if ($ativo == 't') {
	$selected_ativo_sim = "SELECTED";
}
elseif ($ativo == 'f') {
	$selected_ativo_nao = "SELECTED";
}

?>

<table width="700px" cellpadding="0" align='center' cellspacing="1" class="formulario">
	<TR class="titulo_tabela">
		<TD colspan="3">Cadastro</TD>
	</TR>
	<TR class="espaco">
		<TD style="width:250px;padding-left:70px;">Descrição &nbsp;<input type="text" name="descricao" value="<? echo $descricao?>" size="20" maxlength="20"></TD>
		<TD align="left">Ativo &nbsp;<select name="ativo" id="ativo"><option value="t" <? echo $selected_ativo_sim; ?>>sim</option><option value="f" <? echo $selected_ativo_nao; ?>>não</option></TD>
		<TD>
			<input type="button" ONCLICK="javascript: if (document.frm_escritorio_regional.btnacao.value == '' ) { document.frm_escritorio_regional.btnacao.value='gravar' ; document.frm_escritorio_regional.submit() } else { alert ('Aguarde submissão') }" style="cursor: pointer;" value="Gravar" />&nbsp;&nbsp;&nbsp;
			<input type="button" ONCLICK="javascript: if (document.frm_escritorio_regional.btnacao.value == '' ) { document.frm_escritorio_regional.btnacao.value='limpar' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" style="cursor: pointer;" value="Limpar" />
		</TD>
	</TR>
	<TR class="espaco">

		

	</TR>
</table>

<BR>

<table width="700px" cellpadding="0" align='center' cellspacing="1" class="tabela" >
	<TR class="titulo_coluna">
		<TD align="left">Descrição</TD>
		<td>Ativo</TD>
	</TR>
<?
	$sql = "
	SELECT
	escritorio_regional,
	descricao,
	CASE
		WHEN ativo THEN 'sim'
		ELSE 'não'
	END AS ativo

	FROM
	tbl_escritorio_regional

	WHERE
	fabrica=$login_fabrica
	";

	$res = @pg_exec ($con,$sql);
	
	for ($i=0; $i < pg_numrows($res); $i++) {
		$escritorio_regional = pg_result($res, $i, escritorio_regional);
		$descricao = pg_result($res, $i, descricao);
		$ativo = pg_result($res, $i, ativo);
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		echo "<TR bgcolor='$cor'>
		<TD align=left nowrap><a href='$PHP_SELF?escritorio_regional=$escritorio_regional'>$descricao</a></TD>
		<td>$ativo</td>";
		echo "</TR>";

	}
?>
</table>

</FORM>

<?	include "rodape.php"; ?>