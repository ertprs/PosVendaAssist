<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "COBRANÇA";
	include 'cabecalho.php';

$acao = $_GET["acao"];

 
$login = $_POST["login"]; 
	
?>

<style type='text/css'>
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_tabela{
	background-color:#596d9b;
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

.espaco{ padding:10px 0 10px 200px;}
</style>
<center>
<div class='formulario' style='width:700px; align:center'>
<center>
<FORM METHOD=POST ACTION="cobranca.php" >
<div class='titulo_tabela' style='width:100%; height:25px;'>Parâmetros de Pesquisa</div>
<br />
<table width='100%'  border='0'>
	<tr align='left'>
		<td class='espaco'>
			<SELECT NAME="qtd_resultados" class='frm'>
				<OPTION VALUE="20">Resultados por Página</option>
				<OPTION VALUE="20">20 postos por página</option>
				<OPTION VALUE="40">40 postos por página</option>
				<OPTION VALUE="60">60 postos por página</option>
				<OPTION VALUE="100">100 postos por página</option>
			</SELECT>
		</td>
	</tr>

	<tr align='left'>
		<td class='espaco'>
			<input type="hidden" name="tipo" value="select" class='frm'>
			<SELECT NAME="busca" class='frm'>
				<OPTION VALUE="maior_valor">Maior saldo em aberto para menor</option>
				<OPTION VALUE="nome">Ordem alfabetica da razão social</option>
			</SELECT>
		</td>
	</tr>

	<tr align='left'>
		<td class='espaco'>
			<input type="hidden" name="tipo" value="texto">
			<SELECT NAME="busca" class='frm'>
				<OPTION VALUE="codigo_posto">Código_posto</option>
				<OPTION VALUE="cnpj">CNPJ</option>
				<OPTION VALUE="nome">Razão social</option>
			</SELECT>
			&nbsp;&nbsp;&nbsp;
			<input type="text" name="texto_busca" size="28" class='frm'>
		</td>
	</tr>

	<tr>
		<td align='center'>
			<INPUT TYPE="submit" value="buscar">
		</td>
	</tr>
</table>

</FORM>
</center>
<BR><BR>
</div>
<?
include 'rodape.php';
?>