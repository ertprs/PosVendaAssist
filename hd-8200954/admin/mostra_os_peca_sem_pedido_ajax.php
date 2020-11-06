<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$linha=$_GET['linha'];
$produto=$_GET['produto'];
$aux_data_inicial = $_GET['data_inicial'];
$aux_data_final = $_GET['data_final'];

$cond_1 = " 1=1 ";
if(strlen($_GET['posto']) > 0) {
	$codigo_posto = $_GET['posto'];
	$sqlposto     = "select posto
				from tbl_posto_fabrica
				where fabrica = $login_fabrica
				and codigo_posto = '$codigo_posto'";
	$res = pg_query($con, $sqlposto);
	if (pg_num_rows($res)>0) {
		$posto = pg_fetch_result($res, 0, 0);
		$cond_1 = "tbl_os.posto = $posto";
	}
}

$cond_2 = " 1=1 ";
if ($produto > 0) {
	if (in_array($login_fabrica, array(138))) $cond_2 = "tbl_os_produto.produto = $produto ";
	else $cond_2 = "tbl_os.produto = $produto ";
}

if (in_array($login_fabrica, array(138))) {
	$sql = "SELECT
			tbl_os.os AS os,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
			tbl_posto.nome AS nome_posto,
			tbl_produto.descricao AS descricao_produto,
			tbl_peca.descricao AS descricao_peca,
			tbl_os_item.qtde AS qtde_peca,
			to_char(tbl_os_item.digitacao_item, 'DD/MM/YYYY') AS data_digitacao
		FROM tbl_os_produto
			JOIN tbl_os USING (os)
			JOIN tbl_os_item USING (os_produto)
			JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.troca_de_peca = 't' AND tbl_servico_realizado.gera_pedido = 't'
			JOIN tbl_posto USING (posto)
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os_item.pedido IS NULL
			AND tbl_os.fabrica = $login_fabrica
			AND $cond_1
			AND $cond_2
			AND tbl_os.data_abertura > '2009-01-01'
	            	AND tbl_os.data_fechamento IS NULL
			AND tbl_os.data_abertura between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
} else {
	$sql = "SELECT
			tbl_os.os AS os,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
			tbl_posto.nome AS nome_posto,
			tbl_produto.descricao AS descricao_produto,
			tbl_peca.descricao AS descricao_peca,
			tbl_os_item.qtde AS qtde_peca,
			to_char(tbl_os_item.digitacao_item, 'DD/MM/YYYY') AS data_digitacao
		FROM tbl_os
			JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.troca_de_peca = 't' AND tbl_servico_realizado.gera_pedido = 't'
			JOIN tbl_posto USING (posto)
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os_item.pedido IS NULL
			AND tbl_os.fabrica = $login_fabrica
			and tbl_os.excluida is not true
			AND $cond_1
			AND $cond_2
			AND tbl_os.data_abertura > '2009-01-01'
	            	AND tbl_os.data_fechamento IS NULL
			AND tbl_os.data_abertura between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
}
if($login_fabrica == 74){
    $sql .= " AND tbl_os.cancelada IS NOT TRUE 
            AND tbl_os.os NOT IN (
                        SELECT  tbl_os_excluida.os
                        FROM    tbl_os_excluida
                        WHERE   tbl_os_excluida.fabrica = $login_fabrica
                        AND     tbl_os_excluida.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                    )";

}

$sql .= " ORDER BY tbl_os.data_abertura DESC;";

$res = pg_query($con,$sql);
echo "$linha|";

echo "
<table class='table table-striped table-bordered table-hover table-fixed'>
<thead>
	<tr class = 'titulo_coluna'>";

	if ($login_fabrica == 81) {
		echo "
		<th>" . traduz('OS') . "</th>
		<th>" . traduz('Data Abertura') . "</th>";
	} else {
		echo "
		<th>" . traduz('Os') . "</th>
		<th>" . traduz('Data Abertura') . "</th>
		<th>" . traduz('Data de Digita&ccedil;&atilde;o') . "</th>
		<th>" . traduz('Descri&ccedil;&atilde;o do Produto') . "</th>
		<th>" . traduz('Descri&ccedil;&atilde;o da Pe&ccedil;a') . "</th>
		<th>" . traduz('Qtde de Pe&ccedil;a') . "</th>";
	}
echo "
	</tr>
</thead>
<tbody>";

	$total = pg_num_rows($res);
	$total_pecas = 0;
	$os_anterior = 0;

	for ($i = 0; $i < pg_num_rows($res); $i++){

		$os                   = trim(pg_result($res,$i,os));
		$data_abertura        = trim(pg_result($res,$i,data_abertura));
		$data_digitacao       = trim(pg_result($res,$i,data_digitacao));
		$descricao_produto    = trim(pg_result($res,$i,descricao_produto));
		$descricao_peca       = trim(htmlentities(pg_result($res,$i,descricao_peca),ENT_QUOTES,'ISO8859-1'));
		$qtde_peca            = trim(pg_result($res,$i,qtde_peca));

		$total_pecas += $qtde_peca;

		echo "<tr>";
			if($login_fabrica == 81) {
				echo "
				<td><a href=os_press.php?os=$os target=_blank>$os</a></td>
				<td>$data_abertura&nbsp;</td>";
			} else {
				if ($os_anterior == $os) {
					echo "
					<td colspan='2' style='text-align:center;'><b>* Peça OS Acima</b></td>
					<td>$data_digitacao&nbsp;</td>
					<td>$descricao_produto&nbsp;</td>
					<td>$descricao_peca&nbsp;</td>
					<td>$qtde_peca&nbsp;</td>";
				} else {
					echo "
					<td><a href=os_press.php?os=$os target=_blank>$os</a></td>
					<td>$data_abertura&nbsp;</td>
					<td>$data_digitacao&nbsp;</td>
					<td>$descricao_produto&nbsp;</td>
					<td>$descricao_peca&nbsp;</td>
					<td>$qtde_peca&nbsp;</td>";
				}
			}
		echo "</tr>";

		$os_anterior = $os;

	$os_verifica = trim(pg_fetch_result($res, $i, os));
	}
	echo "<tr>";
	echo "<td colspan='5' style='text-align:right;'><b>". traduz('Total de Peças:') ."</b></td>";
	echo "<td>$total_pecas&nbsp;</td>";
	echo "</tr>";

echo "
</tbody></table>";

?>
