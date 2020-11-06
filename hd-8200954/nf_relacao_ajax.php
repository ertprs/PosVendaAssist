<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
header('Content-Type: text/html; charset=iso-8859-1');

$faturamento = (int) $_GET['faturamento'];
if(empty($faturamento) || $_GET['mostra_itens'] != 's' ) {
	echo '<tr><td colspan="5">Erro na passagem de par&atilde;metros</td></tr>';
	exit;
}
	
$sql = "SELECT 	tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_faturamento_item.qtde,
				tbl_faturamento_item.preco,
				tbl_faturamento_item.pedido,
				tbl_pedido.tipo_pedido,
				tbl_pedido.pedido_cliente,
				tbl_os.sua_os,
				tbl_os_produto.os
		FROM tbl_faturamento_item
		LEFT JOIN tbl_pedido ON (tbl_pedido.pedido = tbl_faturamento_item.pedido AND tbl_pedido.fabrica = $login_fabrica)
		JOIN tbl_peca ON(tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica = $login_fabrica)
		LEFT JOIN tbl_os_item ON tbl_os_item.os_item = tbl_faturamento_item.os_item
		LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		LEFT JOIN tbl_os ON (tbl_os.os = tbl_os_produto.os)
		WHERE faturamento = " . $faturamento;
$res = pg_query($con,$sql);
if(pg_num_rows($res) == 0) {
	echo '<tr class="'.$faturamento.'"><td colspan="5" style="font-size:11px; text-align:center;">Nenhum item encontrado</td></tr>';
	return;
}
if($login_fabrica != 87) $colspan = 2;
echo '<tr bgcolor="#7092BE" style="font-size:11px; font-weight:bold; color:white;" class="'.$faturamento.'">
		<td align="center">Referência</td>
		<td colspan="'.$colspan.'">Descrição Peça</td>
		<td align="right">Qtde</td>
		<td align="right">Preço</td>
		<td align="center">Pedido / OS</td>';
		if($login_fabrica == 87)
			echo "<td align='center'>Ordem de Compra</td>";
	echo '</tr>';

for ( $i = 0 ; $i < pg_num_rows($res); $i++ ) {

	$peca  = pg_result($res,$i,'peca');
	$referencia = pg_result($res,$i,'referencia');
	$descricao 	= pg_result($res,$i,'descricao');
	$qtde		= pg_result($res,$i,'qtde');
	$preco		= pg_result($res,$i,'preco');
	$pedido 	= pg_result($res,$i,'pedido');
	$tipo_pedido= pg_result($res,$i,'tipo_pedido');
	$pedido_cliente= pg_result($res,$i,'pedido_cliente');
	$os		= pg_result($res,$i,'os');
	$sua_os = pg_result($res,$i,'sua_os');

	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	
	echo '<tr class="'.$faturamento.'" style="font-size:11px; background: '.$cor.';">
			<td align="center">'.$referencia.'</td>
			<td colspan="'.$colspan.'">'.$descricao.'</td>
			<td align="right">'.$qtde.'</td>
			<td align="right">'.number_format($preco, 2 , ',', '.').'</td>';
	if($login_fabrica == 80 && $tipo_pedido == 148 ){
		$sql2 = "SELECT tbl_os_produto.os
				FROM tbl_os_item
				JOIN tbl_os_produto USING(os_produto)
				WHERE tbl_os_item.peca = $peca
				AND tbl_os_item.pedido = $pedido";
		$res2 = pg_query($con,$sql2);
		if(pg_num_rows($res2) > 0) {
			$os = pg_result($res2,0,'os');
			echo '<td align="center"><a href="os_press.php?os='.$os.'" target="_blank">'.$os.'</a></td>';
		}else{
			echo '<td align="center"><a href="pedido_finalizado.php?pedido='.$pedido.'" target="_blank">'.$pedido.'</a></td>';
		}
	}else{
		echo '<td align="center" nowrap><a href="pedido_finalizado.php?pedido='.$pedido.'" target="_blank">'.$pedido.'</a>';
		if(!empty($os)){
			if (in_array($login_fabrica, array(151))) {
				echo ' / <a href="os_press.php?os='.$os.'" target="_blank">'.$sua_os.'</a>';
			}else{
				echo ' / <a href="os_press.php?os='.$os.'" target="_blank">'.$os.'</a>';
			}
		}
		echo '</td>';
	}
	if($login_fabrica == 87)
		echo "<td align='center'>$pedido_cliente</a></td>";
	echo '</tr>';

}

echo ' <tr class="'.$faturamento.'"><td>&nbsp;</td></tr>';

?>
