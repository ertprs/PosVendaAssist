<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_REQUEST["hd_status"]) > 0)  $hd_status = trim($_REQUEST["hd_status"]);

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($hd_status) > 0) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_hd_status
			WHERE  fabrica = $login_fabrica
			AND    hd_status = $hd_status";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg_success = 'Excluído com sucesso!';
	} else {
		$hd_status   = $_POST["hd_status"];
		$status   = $_POST["status"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["status"]) > 0) {
			$res = pg_query($con,"select fn_retira_especiais('".$_POST['status']."')");
			$aux_status = pg_fetch_result($res, 0, 0);
			$aux_status = str_replace('"', "'", $aux_status);
		} else {
			$msg_erro = "Favor informar a descrição do Status";
		}
	}
	

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($tipo_posto) == 0){
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_hd_status (
												fabrica ,
												status
											  ) VALUES (
												$login_fabrica,
												'$aux_status'
											  )";
			
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_success = 'Gravado com sucesso!';
		} else {
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_hd_status SET
							status  =  $aux_status
						WHERE  fabrica   = $login_fabrica
						AND    hd_status = $hd_status;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_success = 'Atualizado com sucesso!';
		}

	}
	#echo nl2br($sql); exit;
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	} else {
		$hd_status    = $_POST["hd_status"];
		$status    = $_POST["status"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}
###CARREGA REGISTRO
if (strlen($hd_status) > 0) {

	$sql = "SELECT  hd_status                                        ,
					status
			FROM    tbl_hd_status
			WHERE   fabrica    = $login_fabrica
			AND     hd_status = $hd_status;";

	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0 ) {
		$hd_status  = trim(pg_result($res, 0, 'hd_status'));
		$status 	= trim(pg_result($res, 0, 'status'));	
	}
	

}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE STATUS DE CHAMADOS";
include 'cabecalho.php';?>
<script src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript">
$( document ).ready( function(){
	$("input[name=status]").blur(function(){
	    var valor = $(this).val();
	    valor = valor.replace(/\'/g, "");
	    valor = valor.replace(/\"/g,"");
	    valor = valor.replace(/\*/g,"");
	    valor = valor.replace(/\\/g,"");
	    $(this).val(valor);
	});
});

</script>
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

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.sucesso {
  color: white;
  text-align: center;
  font: bold 16px Verdana, Arial, Helvetica, sans-serif;
  background-color: green;
}

.subtitulo{

	background-color:#7092BE;
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
</style>
<br /><?php

if (strlen($msg_erro) > 0) {?>
	<TABLE  width='700px' align='center' border='0' cellspacing="1" cellpadding="0" class='Titulo'>
		<TR align='center'>
			<TD class='error'><? echo $msg_erro; ?></TD>
		</TR>
	</TABLE><?php
} else if (strlen($msg_success) > 0) {?>
	<TABLE  width='700px' align='center' border='0' cellspacing="1" cellpadding="0" class='Titulo'>
		<TR align='center'>
			<TD class='sucesso'><?=$msg_success?></TD>
		</TR>
	</TABLE><?php
}?>
<form name="frm_cadastro" method="post" action="<?=$PHP_SELF?>">

<table width='700px' align='center' cellspacing="0" cellpadding="3" class="formulario">
	<tr class="titulo_tabela">		
		<td colspan='3'>
			Cadastro
		</td>
	</tr>
	<tr>
		<td width='120'>&nbsp;</td>
		<td align='right'>
			Descrição
		</td>
		<td align='left'>
			<input class='frm' type="text" name="status" value="<?=$status?>" size="30" maxlength="30">
		</td>
	</tr>
	<tr align='center' >
		<td colspan='3'>
			<br />
			<input type='hidden' name='btnacao' value=''>
			<input type='hidden' name='hd_status' value='<?=$hd_status?>'>
			<input type="button" value="GRAVAR" ONCLICK="if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='gravar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style='cursor:pointer'> &nbsp;
			<? if($hd_status){ ?>
				<input type="button" value="DELETEAR" ONCLICK="if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='deletar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" ALT="Deletar registro" border='0' style='cursor:pointer'> &nbsp;
			<? } ?>
			<input type="button" value="LIMPAR" ONCLICK="window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0' style='cursor:pointer'>
		</td>
	</tr>
</table>
<br />
<table  width='700px' align='center' border='0' cellspacing="1" cellpadding="3" class="tabela">
	<tr align='center' class="titulo_tabela">
		<td colspan='9'>
			Relação dos HD Staus
		</td>
	</tr>
	<tr class="titulo_coluna" valign="top">
		<td>Status</td>
	</tr>
	<?php
	$sql = "SELECT  hd_status    ,
					status   
			FROM    tbl_hd_status
			WHERE   fabrica = $login_fabrica
			ORDER BY status";

	$res0 = pg_exec ($con,$sql);
	$tot0 = pg_numrows($res0);

	for ($y = 0 ; $y < $tot0; $y++) {

		$hd_status      = trim(pg_result($res0, $y, 'hd_status'));
		$status         = trim(pg_result($res0, $y, 'status'));

		$cor = ($y % 2 == 0) ? '#F1F4FA' : "#F7F5F0";
	?>
		<tr align='center' style='background-color: <?=$cor?>;'>
			<td align='left'>
				<a href='<? echo "$PHP_SELF?hd_status=$hd_status"; ?>'><?=$status?></a>
			</td>
		</tr>
	<?
	}
	?>
</table>
</form>

<? include "rodape.php"; ?>
