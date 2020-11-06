<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "COBRANÇA";
	include 'cabecalho.php';
?>
<style type='text/css'>
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>
<center>
<div style='width:700px;'>
	<form method="post" action="cobranca_upload.php" enctype="multipart/form-data" class='formulario'>
		<div style='width:100%' class='titulo_tabela'>Envio de Arquivo TXT</div>
		<p style='padding:20px 0 20px 150px;'>
			Arquivo
			<input type="file" name="arquivo" />
			<input type="submit" value="Enviar" />
		</p>
	</form>
</div><BR><BR>
<?
include 'rodape.php';
?>