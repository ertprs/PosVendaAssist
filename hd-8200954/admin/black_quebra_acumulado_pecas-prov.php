<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center,auditoria";
include "autentica_admin.php";

include "funcoes.php";

$layout_menu = "auditoria";

$title = "Visão geral por produto";
#include 'cabecalho.php';

?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script LANGUAGE="JavaScript">
	function Redirect(produto, data_i, data_f, mobra) {
		window.open('rel_new_visao_geral_peca.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&mobra=' + mobra,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<script LANGUAGE="JavaScript">
	function Redirect1(produto, data_i, data_f) {
		window.open('rel_new_visao_os.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&estado=<? echo $estado; ?>','1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<p>
<? if (strlen($erro) > 0) { ?>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<br>

<?
$x_data_inicial = $_GET['data_inicial'];
$x_data_final   = $_GET['data_final'];
$produto        = $_GET['produto'];
$referencia     = $_GET['referencia'];
$nome           = $_GET['nome'];
$voltagem       = $_GET['voltagem'];
$linha          = $_GET['linha'];
$estado         = $_GET['estado'];
$opcao          = $_GET['opcao'];

$cond_linha     = "1=1";
if (strlen ($linha) > 0) $cond_linha = " tbl_produto.linha = $linha ";

$cond_estado    = "1=1";
$cond_estado2   = "1=1";
if (strlen ($estado) > 0) {
	$cond_estado  = " tbl_posto.estado = '$estado' ";
	$cond_estado2 = " black_antigo_item.estado = '$estado' ";
}

/*takashi 0206*/
$temp_table = "tmp_black_unica";
$cond_linha     = "1=1";
if (strlen ($linha) > 0) $cond_linha = " $temp_table.linha = $linha ";

$cond_estado    = "1=1";
$cond_estado2   = "1=1";
if (strlen ($estado) > 0) {
	$cond_estado  = " $temp_table.estado = '$estado' ";
}
/*takashi 0206*/

$cont_ocorr_os =0;
$nome= trim(preg_replace('#([^a-z0-9/]+)#i',' ',$nome));
$sql = "SELECT descricao, produto
		FROM tbl_produto
		WHERE tbl_produto.produto = $produto ";
$sql = "SELECT referencia, voltagem, descricao, produto
		FROM tbl_produto
		WHERE tbl_produto.referencia_fabrica = TRIM('$referencia')
		AND   (tbl_produto.descricao like '$nome%' or voltagem ='$voltagem')
		AND   tbl_produto.fabrica_i = $login_fabrica";
$res2 = pg_exec ($con,$sql);
for ($y = 0; $y < pg_numrows($res2); $y++) {
	$produto    = pg_result($res2,$y,produto);
	$referencia = pg_result($res2,$y,referencia);
	$voltagem   = pg_result($res2,$y,voltagem);

	if ($relatorio == "gerar" OR 1==1 ) {

		flush();

	if($opcao <> 7){

		$sql = "SELECT	tbl_peca.referencia as referencia   ,
						tbl_peca.descricao as descricao     ,
						CASE WHEN ((tbl_os_item.servico_realizado = 62))
							THEN ((tbl_os_item.custo_peca*0.1) * tbl_os_item.qtde)
							WHEN ((tbl_os_item.servico_realizado = 90))
							THEN ((tbl_os_item.custo_peca*tx_administrativa) * tbl_os_item.qtde)
							ELSE (tbl_os_item.custo_peca * tbl_os_item.qtde)
						END as preco,
						tbl_os_item.qtde,
						$temp_table.pecas as preco2			,
						$temp_table.os						,
						tbl_os.sua_os                       ,
						tbl_posto_fabrica.codigo_posto
				FROM $temp_table
				JOIN tbl_produto            on tbl_produto.produto       = $temp_table.produto
				JOIN tbl_os_produto         on tbl_os_produto.os         = $temp_table.os
				JOIN tbl_os_item            on tbl_os_item.os_produto    = tbl_os_produto.os_produto
				JOIN tbl_os					on tbl_os.os				 = $temp_table.os
				JOIN tbl_peca               on tbl_peca.peca             = tbl_os_item.peca
				JOIN tbl_posto_fabrica      on tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_tipo_posto         on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
				WHERE $temp_table.produto = $produto
					AND   tbl_produto.voltagem = '$voltagem'
					AND $temp_table.admin = $login_admin";
	} else {
		$sql = "SELECT tbl_peca.descricao,
						tbl_peca.referencia,
						tbl_pedido.pedido AS os,
						tbl_pedido.seu_pedido AS sua_os,
						SUM(tbl_pedido_item.qtde) AS qtde,
						tbl_pedido_item.serie_locador,
						tbl_pedido_item.preco,
						tbl_peca.ipi
					FROM tmp_black_unico_pedidos
					JOIN tbl_pedido ON tmp_black_unico_pedidos.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica
					JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido and tbl_pedido_item.produto_locador = tmp_black_unico_pedidos.produto_locador
					JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
					WHERE tbl_pedido_item.produto_locador = $produto
					GROUP BY tbl_peca.descricao,
						tbl_peca.referencia,
						tbl_pedido.pedido,
						tbl_pedido.seu_pedido,
						tbl_pedido_item.serie_locador,
						tbl_pedido_item.preco,
						tbl_peca.ipi
					ORDER BY tbl_peca.descricao; ";
	}
		//echo nl2br($sql);
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			?>

			<center>
			<font face='arial' color='<? echo $cor_forte ?>'><b><?= "$referencia - $voltagem - " . pg_result ($res2,$y,descricao) ?></b></font>
			<center>

			<?

			echo '<table id="relatorio" class="table table-striped table-bordered table-hover table-large">';
			echo "<thead>";
			echo "<tr class='titulo_coluna'>";

			echo "<td class='tac'>";
			echo "Peça";
			echo "</td>";

			echo "<td class='tac'>";
			echo "Referência";
			echo "</td>";

			$pedido_os = ($opcao == 7) ? "Pedido" : "OS";
			echo "<td class='tac'>";
			echo "Ocorrência/$pedido_os";
			echo "</td>";

			if($opcao == 7){
				echo "<td class='tac'>";
				echo "Série";
				echo "</td>";

			}

			echo "<td class='tac'>";
			echo "Qtde";
			echo "</td>";

			echo "<td class='tac'>";
			echo "Custo";
			echo "</td>";

			echo "<td class='tac'>";
			echo "%";
			echo "</td>";

			echo "</tr>";
			echo "</thead>";
			$total_qtde_produto = 0 ;
			echo "<tbody>";
			for ($x = 0; $x < pg_numrows($res); $x++) {
				$preco        = pg_result($res,$x,preco);
				$ipi       = pg_result($res,$x,ipi);
				$qtde         = pg_result($res,$x,qtde);
				if($opcao == 7){
					$preco = $preco + (($preco*$ipi)/100 );
					$preco = $preco * $qtde;
				}
				$total_ocorrencia = $total_ocorrencia + 1;//pg_result($res,$x,qtde);
				$total_peca       = $total_peca + $preco;
				$total_qtde_produto += $qtde;
			}

			for ($x = 0; $x < pg_numrows($res); $x++) {
				$referencia   = pg_result($res,$x,referencia);
				$descricao    = pg_result($res,$x,descricao);
				$qtde         = pg_result($res,$x,qtde);
				$preco        = pg_result($res,$x,preco);
				$sua_os       = pg_result($res,$x,sua_os);
				$codigo_posto = pg_result($res,$x,codigo_posto);
				$os       = pg_result($res,$x,os);

				$total_qtde += $qtde;
				if($opcao == 7){
					$ipi       = pg_result($res,$x,ipi);
					$serie    = pg_result($res,$x,serie_locador);
					$preco = $preco + (($preco*$ipi)/100 );
					$preco = $preco * $qtde;
				}

				if ($preco > 0 AND $total_peca > 0) {
					$porcentagem = ($preco / $total_peca * 100);
				}


				echo "<tr>";

				echo "<td  class='tac' nowrap>";
				echo substr(pg_result($res,$x,descricao),0,45);
				echo "</td>";

				echo "<td  class='tac' nowrap>";
				echo $referencia ;
				echo "</td>";

				if($opcao == 7){
					echo "<td class='tal'>";
					echo "<a href='pedido_admin_consulta.php?pedido=$os' target='_blank'>";
					echo fnc_so_numeros($sua_os);
					echo "</a>";
					echo "</td>";

				} else {
					echo "<td class='tac'>";
					echo "<a href='os_press.php?os=$os' target='_blank'>";
					echo $codigo_posto."".$sua_os;
					echo "</a>";
					echo "</td>";
				}

				if($opcao == 7){
					echo "<td  class='tar'>";
					echo $serie;
					echo "</td>";

				}

				echo "<td  class='tar'>";
				echo $qtde;
				echo "</td>";

				echo "<td  class='tar'>";
				echo number_format($preco,2,",",".");
				echo "</td>";

				echo "<td  class='tar'>";
				echo number_format($porcentagem,2,",",".");
				echo "</td>";

				echo "</tr>";

			}
			echo "</tbody>";
			echo "<tfoot>";
			echo "<tr class='titulo_coluna tac'>";

if($opcao == 7){
	$sql = "SELECT count(1),tbl_pedido_item.pedido, serie_locador
					FROM tmp_black_unico_pedidos
					JOIN tbl_pedido_item on tmp_black_unico_pedidos.pedido = tbl_pedido_item.pedido
					WHERE tbl_pedido_item.produto_locador=$produto
				       	GROUP by tbl_pedido_item.pedido, serie_locador";
			$res = pg_exec ($con,$sql);
			$ocorrencia_os = pg_num_rows($res) ;
			$cont_ocorr_os = ($cont_ocorr_os +$ocorrencia_os );
} else {
	$sql = "SELECT	count(os) as ocorrencia_os
		FROM $temp_table
		WHERE $temp_table.produto = $produto AND $temp_table.admin = $login_admin";
		$res = pg_exec ($con,$sql);
		$ocorrencia_os = pg_result ($res, 0, ocorrencia_os) ;
		$cont_ocorr_os = ($cont_ocorr_os +$ocorrencia_os );
}
$pedido_os = ($opcao == 7) ? "" : "OS";
$x = $total_qtde;
echo "<td colspan='2' class='tac' >";
echo "TOTAL";
echo "</td>";

echo "<td class='tac'>";
echo "$ocorrencia_os";
echo "</td>";

if($opcao == 7){
	echo "<td class='tar'>";
	echo "&nbsp;";
	echo "</td>";
}

echo "<td class='tar'>";
echo "$total_qtde_produto";
echo "</td>";

echo "<td class='tar'>";
echo "". number_format($total_peca,2,",",".") ."";
echo "</td>";

echo "<td class='tar'>";
echo "100%";
echo "</td>";

echo "</tr>";
echo "</tfoot>";
echo "</table>";
echo "<br /> <br />";

$total_ocorrencia = $total_qtde_produto;
$total_g_ocorrencia = $total_g_ocorrencia + $total_ocorrencia;
$total_g_peca       = $total_g_peca + $total_peca;
$total_peca = 0;
$total_ocorrencia = 0;

echo "<p>";
		}
	}
}
echo "<p>";

if (strlen($meu_grafico) > 0) {
	echo $meu_grafico;
}

?>

<div class='tac'>
<h3>Total Geral</h3>
</div>

<?

echo '<table id="relatorio" width="300" class="table table-bordered table-hover table-normal">';
echo "<thead>";
echo "<tr class='titulo_coluna tac' style='text-align:center'>";

echo "<td class='tac'>";
echo "Ocorrência";
echo "</td>";

echo "<td class='tac'>";
echo "Qtde Peça";
echo "</td>";

echo "<td class='tac'>";
echo "Custo";
echo "</td>";

echo "</tr>";

echo "</thead>";
echo "<tr>";
echo "<td class='tac'>";
echo "". number_format($cont_ocorr_os,0,",",".") ."";
echo "</td>";

echo "<td class='tac'>";
echo "". number_format($total_g_ocorrencia,0,",",".") ."";
echo "</td>";

echo "<td class='tar'>";
echo "". number_format($total_g_peca,2,",",".") ."";
echo "</td>";

echo "</tr>";
echo "</table>";

#include 'rodape.php';
?>
