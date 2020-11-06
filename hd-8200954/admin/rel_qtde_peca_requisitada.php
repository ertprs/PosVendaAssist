<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

include "autentica_admin.php";

$peca_referencia = $_GET['referencia'];
$data = $_GET['data'];
$posto = $_GET['posto'];

if(!empty($posto)){
	$cond = " AND tbl_posto_fabrica.posto = $posto";
}
$sql = "SELECT peca, descricao FROM tbl_peca WHERE fabrica = $login_fabrica and referencia = '$peca_referencia'";
$res = pg_exec($con,$sql);
$peca = pg_result($res,0,peca);
$peca_nome = pg_result($res,0,descricao);

$title="RELATÓRIO QUANTIDADE REQUISIÇÃO PEÇA";
include "cabecalho.php";
	

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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

</style>
<table align='center' width='700' cellspacing='1' class='tabela'>
	<caption class='titulo_tabela'><? echo "$referencia - $peca_nome"; ?></caption>
	<tr class='titulo_coluna'>
		<td>OS</td>
		<td>Cod. Posto</td>
		<td>Posto Nome</td>
		<td>Qtde Peca</td>
	</tr>
	<?
	
	$sql = "SELECT tbl_os.sua_os,
				   tbl_posto_fabrica.codigo_posto,
				   tbl_posto.nome,
				   SUM(tbl_os_item.qtde) as qtde
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
				JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = tbl_peca.fabrica
				JOIN tbl_posto using(posto)
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica $cond
				WHERE
				tbl_peca.fabrica=$login_fabrica
				AND tbl_os_item.peca = $peca
				AND tbl_servico_realizado.troca_de_peca IS TRUE
				AND tbl_servico_realizado.gera_pedido IS FALSE
				AND tbl_os_item.digitacao_item BETWEEN '$data 00:00:00' AND '$data 23:59:59'
				GROUP BY tbl_os.sua_os, tbl_posto.nome, tbl_posto_fabrica.codigo_posto";

	$res = pg_exec($con,$sql);
	$total = pg_numrows($res);
	#echo nl2br($sql);exit;
	for($i = 0; $i < $total; $i++){
		$sua_os       = pg_result($res,$i,sua_os);
		$codigo_posto = pg_result($res,$i,codigo_posto);
		$posto_nome   = pg_result($res,$i,nome);
		$qtde         = pg_result($res,$i,qtde);

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	?>
	<tr bgcolor='<? echo $cor ?>'>
		<td><? echo $sua_os;?></td>

		<td><? echo $codigo_posto;?></td>
		
		<td><? echo $posto_nome;?></td>

		<td><? echo $qtde;?></td>
	</tr>
	<?
	}
	?>
</table>

<?
include "rodape.php" ?>
