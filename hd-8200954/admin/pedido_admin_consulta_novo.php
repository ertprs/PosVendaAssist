<?php
	include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_admin.php";
    include "funcoes.php";
	
	
	if($_GET){
		$pedido = $_GET["pedido"];
		$sql = "
		SELECT
		tbl_pedido.pedido

		FROM
		tbl_pedido

		WHERE 
		tbl_pedido.pedido=$pedido
		AND tbl_pedido.fabrica=$login_fabrica
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
		}
		else {
			$msg_erro = "Pedido não encontrado";
		}
	}

	
?>

<?php
	$title = "PEDIDO DE PEÇAS";
	include "cabecalho.php";	
    include "javascript_pesquisas.php";
	include "javascript_calendario.php";
?>

<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script language="JavaScript">

$(document).ready(function(){
});

function mostra_qtde(pedido_item) {
	$("#dados" + pedido_item).css("display", "table-cell");

	url = "pedido_admin_consulta_novo_ajax.php?acao=pesquisaros&pedido_item="+pedido_item;
	requisicaoHTTP("GET", url, true , "mostra_qtde_retorno", pedido_item);
}

function mostra_qtde_retorno(retorno, pedido_item) {
	partes = retorno.split("|")
	$("#dados" + pedido_item).html(partes[2]);
}

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

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco tr td{
	padding-left:50px;
}

</style>
<?php

if ($login_fabrica <> 15) {
	echo "<div class='texto_avulso' style='width:700px;'><b>Atenção:&nbsp;</b>Pedidos a prazo dependerão de análise do departamento de crédito.</div><br />";
}

?>

<?php
	if($_GET and strlen($msg_erro)==0){ 
		
		$sql = "
		SELECT
		tbl_pedido_item.pedido_item,
		tbl_peca.referencia,
		tbl_peca.descricao,
		tbl_pedido_item.qtde,
		tbl_pedido_item.qtde_cancelada,
		tbl_pedido_item.qtde_faturada,
		tbl_pedido_item.qtde_faturada_distribuidor,
		tbl_peca.ipi, /* ISSO AQUI ESTÁ ERRADO, VOU PEGAR DAQUI PORQUE NÃO TEM NOS ITENS DOS PEDIDOS. O IPI DEVERIA FICAR GRAVADO NO PEDIDO, PRINCIPALMENTE SE JÁ FOI FATURADO */
		tbl_pedido_item.preco

		FROM
		tbl_pedido_item
		JOIN tbl_peca ON tbl_pedido_item.peca=tbl_peca.peca

		WHERE
		tbl_pedido_item.pedido=$pedido

		ORDER BY
		tbl_peca.descricao,
		tbl_peca.peca
		";
		$res = pg_query($con, $sql);
?>
	<table width="700" align="center" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
			<td align='left'>Componente</td>
			<td>Qtde</td>
			<td>Canc</td>
			<td>Fat</td>
			<td>Pen</td>
			<td>IPI</td>
			<td>Preço</td>
			<td>Total</td>
		</tr>

		<?
		$num_rows = pg_num_rows($res);

		for($i=0;$i<$num_rows;$i++){
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			extract(pg_fetch_array($res));

			if ($qtde_faturada_distribuidor > 0) $qtde_faturada = $qtde_faturada_distribuidor;
			$qtde_pendente = $qtde - $qtde_cancelada - $qtde_faturada;
			$total = $preco * (1 + $ipi/100);

			echo "
			<tr bgcolor='$cor'>
				<td align='left'>$referencia - $descricao</td>
				<td>$qtde <img src='imagens/mais.bmp' onclick='mostra_qtde($pedido_item);' style='cursor:pointer' id='img$pedido_item'></td>
				<td>$qtde_cancelada <img src='imagens/mais.bmp' onclick='mostra_cancelada($pedido_item);' style='cursor:pointer'></td>
				<td>$qtde_faturada <img src='imagens/mais.bmp' onclick='mostra_faturada($pedido_item);' style='cursor:pointer'></td>
				<td>$qtde_pendente</td>
				<td>$ipi%</td>
				<td>$preco</td>
				<td>$total</td>
			</tr>
			<tr>
				<td colspan='8' style='display:none;' id='dados$pedido_item'>
				teste
				</td>
			</tr>
			";
		}
		?>
	</table>
<?php
	}
?>
<?php
	include "rodape.php";
?>