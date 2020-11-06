<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "login_unico_autentica_usuario.php";

$aba=3;
include "estoque_cabecalho.php";


$agrupada = "";
for ($i = 0 ; $i < $_POST['qtde_nf'] ; $i++) {
	$nf = trim ($_POST['agrupada_' . $i]);
	if (strlen ($nf) > 0) {
		$agrupada .= $nf . ",";
	}
}
$agrupada = substr ($agrupada,0,strlen ($agrupada)-1);



$btn_acao = trim ($_POST['btn_acao']);
if (strlen ($btn_acao) > 0) {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	#-------------- Confirma conferência atual ----------#
	$qtde_item   = $_POST['qtde_item'];
	$faturamento = $_POST['faturamento'];

	# LOG
	$arquivo  = fopen ("log_nf_entrada.txt", "a+");
	fwrite($arquivo, "\n\n INICIO ---------------\n ".date("d/m/Y H:i:s")."\n\n [ POST ]\n");

	$sql="UPDATE tbl_faturamento_item SET qtde_estoque = 0 , qtde_quebrada = 0 WHERE tbl_faturamento_item.faturamento = $faturamento";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca             = $_POST['peca_' . $i];
		$qtde_estoque     = $_POST['qtde_estoque_'  . $i];
		$qtde_quebrada    = $_POST['qtde_quebrada_' . $i];
		$localizacao      = $_POST['localizacao_'   . $i];

		fwrite($arquivo, "Peça: $peca - Qtde Estoque: $qtde_estoque - Qtde Quebrada: $qtde_quebrada \n");

		$localizacao = strtoupper (trim ($localizacao));

		if (strlen ($qtde_estoque)  == 0) $qtde_estoque  = "0";
		if (strlen ($qtde_quebrada) == 0) $qtde_quebrada = "0";

		$sql = "SELECT MIN (faturamento_item) FROM tbl_faturamento_item WHERE faturamento = $faturamento AND peca = $peca";
		$res = pg_exec ($con,$sql);
		$faturamento_item = pg_result ($res,0,0);

		$sql="UPDATE tbl_faturamento_item SET qtde_estoque = $qtde_estoque , qtde_quebrada = $qtde_quebrada WHERE tbl_faturamento_item.peca = $peca AND tbl_faturamento_item.faturamento = $faturamento AND tbl_faturamento_item.faturamento_item = $faturamento_item";
		$res = pg_exec ($con,$sql);

		fwrite($arquivo, "\n\nSQL : $sql \n\n");

		$sql = "UPDATE tbl_posto_estoque_localizacao SET localizacao = '$localizacao' WHERE posto = $login_posto AND peca = $peca ";
		$res = pg_exec ($con,$sql);

	}
	

	$sql = "UPDATE tbl_faturamento SET conferencia = current_timestamp WHERE faturamento = $faturamento";
	$res = pg_exec ($con,$sql);

	#----------- Cria Embarque Vinculado a NF -------------
	$sql = "SELECT tbl_os_item.os_item , tbl_pedido_item.pedido_item, tbl_os_item.qtde, tbl_pedido.posto, tbl_faturamento_item.peca, tbl_faturamento_item.qtde_estoque
			FROM tbl_faturamento_item
			JOIN tbl_os_produto  ON tbl_faturamento_item.os = tbl_os_produto.os
			JOIN tbl_os_item     ON tbl_faturamento_item.peca = tbl_os_item.peca AND tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
			JOIN tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
			WHERE tbl_faturamento_item.faturamento = $faturamento
			AND   tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada) ";
	$res = pg_exec ($con,$sql);

	fwrite($arquivo, "\n\nSQL : $sql \n\n");

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto       = pg_result ($res,$i,posto);
		$peca        = pg_result ($res,$i,peca);
		$qtde        = pg_result ($res,$i,qtde);
		$pedido_item = pg_result ($res,$i,pedido_item);
		$os_item     = pg_result ($res,$i,os_item);

		if (strlen ($os_item) == 0) $os_item = "null";

		$sql = "SELECT fn_embarca_item ($login_posto, $posto, $peca, $qtde::float, $pedido_item, $os_item)";
		$resX = pg_exec ($con,$sql);
		fwrite($arquivo, "\n\n --> SQL ITEM (Posto $posto | Peça: $peca | Qtde: $qtde | Ped.Item: $pedido_item | OS Item: $os_item ) -> \n\n $sql \n\n");
	}

	fclose ($arquivo);
	#----------- Embarca Troca Produto Acabado -------------
	/*
	$sql = "SELECT tbl_os_item.os_item 
			FROM tbl_os_item 
			JOIN tbl_faturamento_item ON tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_os_item.peca = tbl_faturamento_item.peca
			JOIN tbl_pedido_item      ON tbl_os_item.pedido = tbl_pedido_item.pedido      AND tbl_os_item.peca = tbl_pedido_item.peca
			WHERE tbl_faturamento_item.faturamento = $faturamento
			AND   tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada) ";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo pg_result ($res,$i,os_item);
		echo "<br>";
	}
	*/

#	exit;
	
#	$sql = "delete from tbl_embarque where embarque not in (select embarque from tbl_embarque_item)";
#	$resX = pg_exec ($con,$sql);
	
	$res = pg_exec ($con,"COMMIT TRANSACTION");

	header ("Location: nf_entrada.php");
	exit;
}


?>

<?
$faturamento = $_GET['faturamento'];
if (strlen ($faturamento) > 0) {
	$sql = "SELECT nota_fiscal , TO_CHAR (conferencia,'DD/MM/YYYY') AS conferencia, TO_CHAR (emissao,'DD/MM/YYYY') AS emissao , TO_CHAR (conferencia - emissao , 'DD') AS trafego FROM tbl_faturamento WHERE faturamento = $faturamento AND posto = $login_posto";
	$res = pg_exec ($con,$sql);
	$nota_fiscal = pg_result ($res,0,nota_fiscal);
	$emissao     = pg_result ($res,0,emissao);
	$conferencia = pg_result ($res,0,conferencia);
	$trafego     = pg_result ($res,0,trafego);
}

if (strlen ($agrupada) > 0) {
	$sql = "SELECT nota_fiscal FROM tbl_faturamento WHERE faturamento IN ($agrupada) AND posto = $login_posto";
	$res = pg_exec ($con,$sql);
	$nota_fiscal = "";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$nota_fiscal .= pg_result ($res,$i,nota_fiscal) . " - " ;
	}
	$nota_fiscal = substr ($nota_fiscal,0,strlen ($nota_fiscal)-3);
	$faturamento = $agrupada;
}


?>


<div id='dest'>Itens da NF de Entrada - <? echo $nota_fiscal ?></div>


<p>

<table width='600' align='center'>
<?
if (strlen ($emissao) > 0) { 
echo "<tr bgcolor='#3399FF' style='color:#ffffff ; font-weight:bold'>";
	echo "<td align='center' colspan='7'>";
		echo "NF Emitida em $emissao";
	echo "</td>";
echo "</tr>";
}
if (strlen ($conferencia) > 0) {
echo "<tr bgcolor='#FF0000' style='color:#ffffff ; font-weight:bold'>";
	echo "<td align='center' colspan='7'>";
		echo "Mercadoria recebida em $conferencia ($trafego dias de tráfego).";
	echo "</td>";
echo "</tr>";
}?>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>Peça</td>
	<td align='center'>Descrição</td>
	<td align='center'>Qtde NF</td>
	<td align='center'>Qtde ESTOQUE</td>
	<td align='center'>Localização</td>
	<td align='center'>Qtde Quebrada</td>
	<td align='center'>Preço Unitário</td>
</tr>


<?
/*
$sql = "SELECT	tbl_faturamento_item.faturamento ,
				tbl_faturamento_item.faturamento_item ,
				tbl_peca.referencia ,
				tbl_peca.descricao ,
				tbl_faturamento_item.peca ,
				tbl_faturamento_item.qtde ,
				tbl_faturamento_item.qtde_estoque ,
				to_char (tbl_faturamento_item.preco,'999999.99') as preco ,
				tbl_posto_estoque_localizacao.localizacao
		FROM    tbl_faturamento_item
		JOIN    tbl_faturamento USING (faturamento)
		JOIN    tbl_peca        USING (peca)
		LEFT JOIN tbl_posto_estoque             ON tbl_faturamento.posto = tbl_posto_estoque.posto AND tbl_faturamento_item.peca = tbl_posto_estoque.peca
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_faturamento.posto = tbl_posto_estoque_localizacao.posto AND tbl_faturamento_item.peca = tbl_posto_estoque_localizacao.peca
		WHERE   tbl_faturamento.posto = $login_posto
		AND     tbl_faturamento.fabrica = $login_fabrica
		AND     tbl_faturamento_item.faturamento = $faturamento
		ORDER BY tbl_peca.referencia, tbl_faturamento_item.faturamento_item";
*/


$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, fat.qtde, fat.qtde_estoque, tbl_posto_estoque_localizacao.localizacao, fat.qtde_quebrada, fat.preco
		FROM (SELECT tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde) AS qtde, SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque, SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada, tbl_faturamento_item.preco
				FROM tbl_faturamento_item
				JOIN tbl_faturamento USING (faturamento)
				WHERE tbl_faturamento.faturamento IN ($faturamento)
				AND   tbl_faturamento.posto       = $login_posto
				GROUP BY tbl_faturamento_item.peca, tbl_faturamento_item.preco
				) fat
		JOIN tbl_peca ON fat.peca = tbl_peca.peca
		LEFT JOIN tbl_posto_estoque_localizacao ON fat.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		ORDER BY tbl_peca.referencia";


$res = pg_exec ($con,$sql);

echo "<form method='post' action='$PHP_SELF' name='frm_nf_entrada_item'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
#	$faturamento      = trim(pg_result($res,$i,faturamento)) ;
#	$faturamento_item = trim(pg_result($res,$i,faturamento_item)) ;
	$referencia       = trim(pg_result($res,$i,referencia)) ;
	$descricao        = trim(pg_result($res,$i,descricao));
	$peca             = trim(pg_result($res,$i,peca));
	$qtde             = trim(pg_result($res,$i,qtde));
	$preco            = trim(pg_result($res,$i,preco));
	$qtde_estoque     = trim(pg_result($res,$i,qtde_estoque));
	$qtde_quebrada    = trim(pg_result($res,$i,qtde_quebrada));
#	$preco            = trim(pg_result($res,$i,preco));
	$localizacao      = trim(pg_result($res,$i,localizacao));

	if (strlen ($msg_erro) > 0) $qtde_estoque = $_POST['qtde_estoque_' . $i];
	if (strlen ($msg_erro) > 0) $localizacao  = $_POST['localizacao_' . $i];

#	if (strlen ($qtde_estoque) == 0) $qtde_estoque = $qtde;

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";

#	echo "<input type='hidden' name='faturamento_item_$i' value='$faturamento_item'>";
	echo "<input type='hidden' name='peca_$i' value='$peca'>";
	
	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='left' nowrap><font size='3'><b>$referencia</b></font></td>\n";
	echo "<td align='left' nowrap>$descricao</td>\n";
#	$preco = number_format ($preco,2,',','.');
#	echo "<td align='right' nowrap>$preco</td>\n";
	
	if ($qtde_estoque == 0) $qtde_estoque = "";
	if ($qtde_quebrada == 0) $qtde_quebrada = "";
	
	echo "<td align='right' nowrap><font size='3'><b>$qtde</b></font></td>\n";
	echo "<td align='right' nowrap><input type='text' name='qtde_estoque_$i'  value='$qtde_estoque'  size='5'  maxlength='5' ></td>\n";
	echo "<td align='right' nowrap><input type='text' name='localizacao_$i'   value='$localizacao'   size='10' maxlength='15'></td>\n";
	echo "<td align='right' nowrap><input type='text' name='qtde_quebrada_$i' value='$qtde_quebrada' size='5'  maxlength='5' ></td>\n";
	$preco = number_format ($preco,2,',','.');
	echo "<td align='right' nowrap><font size='3'><b>$preco</b></font></td>\n";
	echo "</tr>\n";
}


echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";

if (strlen ($agrupada) > 0) {
	echo "NOTA AGRUPADA";
}else{
	//echo "<input type='submit' name='btn_acao' value='Conferida !'>";
	# Colocado por Fabio o aguarde submissão, pois estavam clicando duas vezes e sendo processados duas vezes ao mesmo tempo
	echo "<input type='hidden' name='btn_acao'   value=''>";
	echo "<input type='button' name='btn_conferir' value='Conferida !' OnClick=\"
			javascript: 
			if (document.frm_nf_entrada_item.btn_acao.value == ''){
				document.frm_nf_entrada_item.btn_acao.value='Conferida !';
				document.frm_nf_entrada_item.submit();
			}else{
				alert('Aguarde submissão.');
			}
			\">";
}

echo "</form>";
echo "</td>";
echo "</tr>";


echo "</table>\n";

?>

<p>

<? include "login_unico_rodape.php"; ?>


