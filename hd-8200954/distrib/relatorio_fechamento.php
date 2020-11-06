<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$pesquisar = false;
$msg_erro = '';

$inputs = array(
	'data_inicial' => '',
	'data_final' => '',
);

$fabrica = '';
$tipo = '';
$desconto_telecontrol = '';

if (!empty($_POST['btn_acao'])) {
	$data_inicial = $_POST['data_inicial'];
	$data_final = $_POST['data_final'];
	$fabrica = $_POST['fabrica'];
	$tipo = $_POST['tipo'];
	$desconto_telecontrol = (float) $_POST['desconto_telecontrol'];

	if (empty($data_inicial) or empty($data_final)) {
		$msg_erro .= 'Favor selecionar um período de datas.<br>';
	} else {
		$inputs['data_inicial'] = $data_inicial;
		$inputs['data_final'] = $data_final;

		$arr_data_inicial = explode('/', $data_inicial);
		$arr_data_final = explode('/', $data_final);

		$data_inicial = implode('-', array_reverse($arr_data_inicial));
		$data_final = implode('-', array_reverse($arr_data_final));
	}

	if (empty($fabrica)) {
		$msg_erro .= 'Favor selecionar a fábrica.<br>';
	}

	if (empty($tipo)) {
		$msg_erro .= 'Favor selecionar um tipo.<br>';
	}

	if (empty($msg_erro)) {
		$pesquisar = true;
	}
}

$title = 'Relatório de Fechamento';

?>

<html>
<head>
<title><?echo $title;?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<?php
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script>
	$(document).ready(function() {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});

		$('#desconto_telecontrol').numeric({allow: '.'});
	});
</script>
<body>

<?php include 'menu.php' ?>

<center><h1><?= $title ?></h1></center>

<p>
<?php
		if (strlen($msg_erro) > 0) {
			echo "<div style='border: 1px solid #DD0000; background-color: #FFDDDD; color: #DD0000; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>$msg_erro</div><p>";
		}
?>
	<center>
	<form name='frm_pesquisa' action='' method='POST'>
	<table>

		<tr>
			<td align='right'>Data Inicial</td>
			<td><input type='text' size='11' name='data_inicial' id='data_inicial' class="frm" value="<?= $inputs['data_inicial'] ?>"></td>
			<td align='right'>Data Final</td>
			<td><input type='text' size='11' name='data_final'   id='data_final' class="frm"  value="<?= $inputs['data_final'] ?>"></td>
					<td align='right'>Fábrica</td>
			<td align='left'>
			<?php
			echo "<select style='width:120px;' name='fabrica' id='fabrica' class='frm'>";
			echo '<option value="">---<option>';
				$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN ($telecontrol_distrib) ORDER BY nome";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					for($x = 0; $x < pg_num_rows($res);$x++) {
						$aux_fabrica = pg_fetch_result($res,$x,'fabrica');
						$aux_nome    = pg_fetch_result($res,$x,'nome');
						echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			echo "</select>";
			?>
			</td>
			<td align='center' colspan='6'>Desc. Telecontrol
				<input type="text" name="desconto_telecontrol" id="desconto_telecontrol" class="frm" size="4" value="<?= $desconto_telecontrol ?>">
			</td>
		</tr>
		<tr>
			<td align='center' colspan='7'>Tipo 
				<input type='radio' name='tipo' value='garantia' <?php if ($tipo == 'garantia') echo 'checked="checked"' ?>>Garantia
				<input type='radio' name='tipo' value='venda' <?php if ($tipo == 'venda') echo 'checked="checked"' ?>>Venda
			</td>
		</tr>
		<tr>
			<td align='center' colspan='7'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br/>

<?php

if (true === $pesquisar) {

	if (empty($desconto_telecontrol)) {
		$desconto_telecontrol = 0;
	}

	$sql_venda = '';
	$sql_garantia = '';

	if ($tipo == 'venda') {
		$sql_venda = " AND (pedido_faturado or upper(tbl_tipo_pedido.descricao) = 'FATURADO') ";
	}

	if ($tipo == 'garantia') {
		$sql_garantia = " JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item 
			AND tbl_os_item.pedido = tbl_pedido_item.pedido 
			AND tbl_os_item.os_item = tbl_faturamento_item.os_item ";
	}

	$sql = "
		SELECT 
			tbl_faturamento_item.faturamento,
			tbl_faturamento_item.faturamento_item,
			tbl_faturamento_item.peca,
			tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_faturamento_item.qtde,
			tbl_pedido_item.pedido,
			tbl_pedido_item.pedido_item,
			tbl_pedido.desconto,
			TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data,
			tbl_pedido_item.preco,
			tbl_peca.ipi,
			tbl_faturamento.nota_fiscal,
			TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
		FROM tbl_faturamento_item
		JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		JOIN tbl_pedido ON tbl_faturamento_item.pedido = tbl_pedido.pedido
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		  AND tbl_pedido.fabrica = tbl_tipo_pedido.fabrica
		JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido AND tbl_faturamento_item.peca = tbl_pedido_item.peca
		$sql_garantia
		JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
		WHERE tbl_pedido.fabrica = $fabrica
		AND tbl_pedido.distribuidor = 4311
		$sql_venda
		AND tbl_faturamento.empresa = 'ACAC'
		AND tbl_pedido.data::date BETWEEN '{$data_inicial}' AND '{$data_final}'
		ORDER BY tbl_pedido.data, tbl_peca.referencia, tbl_peca.descricao";

	$res = pg_query($con, $sql);
	$rows = pg_num_rows($res);

	if ($rows > 0) {

		echo "<table align='center' border='0' cellspacing='1' cellpadding='1'>";
		echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
		echo "<td>Pedido</td>";
		echo "<td>Data</td>";
		echo "<td>Ref. Peça</td>";
		echo "<td>Desc. Peça</td>";
		echo "<td>Qtde</td>";
		echo "<td>Preço Unit.</td>";
		echo "<td>IPI</td>";
		echo "<td>Desconto</td>";
		echo "<td>Preço Posto</td>";
		echo "<td>Preço Telecontrol</td>";
		echo "<td>NF</td>";
		echo "<td>Data NF</td>";
		echo "</tr>";

		$link_csv = 'xls/relatorio_fechamento-' . substr(sha1(date('Ymd') . $fabrica . $cookie_login), 0, 8) . '.csv';
		$csv = fopen(__DIR__ . '/./' . $link_csv, 'w');

		$header = utf8_encode('Pedido;Data;Ref. Peça;Desc. Peça;Qtde;Preço Unit.;IPI;Desconto;Preço Posto;Preço Telecontrol;NF;Data NF');
		fwrite($csv, $header . "\n");

		$total_posto = 0;
		$total_telecontrol = 0;

		for ($i = 0; $i < $rows; $i++) {
			$pedido = pg_fetch_result($res, $i, 'pedido');
			$data = pg_fetch_result($res, $i, 'data');
			$peca_ref = pg_fetch_result($res, $i, 'referencia');
			$peca_desc = pg_fetch_result($res, $i, 'descricao');
			$qtde = (int) pg_fetch_result($res, $i, 'qtde');
			$preco = (float) pg_fetch_result($res, $i, 'preco');
			$ipi = (float) pg_fetch_result($res, $i, 'ipi');
			$desconto = (float) pg_fetch_result($res, $i, 'desconto');
			$nota_fiscal = pg_fetch_result($res, $i, 'nota_fiscal');
			$emissao = pg_fetch_result($res, $i, 'emissao');

			$preco_ipi = $preco + ($preco * ($ipi / 100));

			$preco_posto = $qtde * $preco_ipi;
			$desconto_posto = $preco_posto * ($desconto / 100);

			$preco_posto -= $desconto_posto;

			$preco_telecontrol = $preco_posto - ($preco_posto * ($desconto_telecontrol / 100));

			$total_posto += $preco_posto;
			$total_telecontrol += $preco_telecontrol;

			$cor = "cccccc";
			if ($i % 2 == 0) $cor = '#eeeeee';

			echo "<tr bgcolor='$cor' style='font-size:11px'>";

			echo "<td>$pedido</td>";
			echo "<td>$data</td>";
			echo "<td>$peca_ref</td>";
			echo "<td>$peca_desc</td>";
			echo "<td align='center'>$qtde</td>";
			echo "<td align='center'>";
			echo number_format($preco, 2, ',', '');
			echo "</td>";
			echo "<td align='center'>";
			echo number_format($ipi, 2, ',', '');
			echo "</td>";
			echo "<td align='center'>$desconto</td>";
			echo "<td align='center'>";
			echo number_format($preco_posto, 2, ',', '');
			echo "</td>";
			echo "<td align='center'>";
			echo number_format($preco_telecontrol, 2, ',', '');
			echo "</td>";
			echo "<td>$nota_fiscal</td>";
			echo "<td>$emissao</td>";

			echo "</tr>";

			$line = $pedido . ';' . $data . ';' . $peca_ref . ';' . $peca_desc . ';' . $qtde . ';' . number_format($preco, 2, ',', '') . ';';
			$line .= number_format($ipi, 2, ',', '') . ';' . $desconto . ';' . number_format($preco_posto, 2, ',', '') . ';';
			$line .= number_format($preco_telecontrol, 2, ',', '') . ';' . $nota_fiscal . ';' . $emissao;
			$line = utf8_encode($line);

			fwrite($csv, $line . "\n");
		}

		echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
					<td colspan='8' align='right'> Totais:</td>
					<td align='center'>".number_format($total_posto, 2, ',', '')."</td>
					<td align='center'>".number_format($total_telecontrol, 2, ',', '')."</td>
					<td colspan='2'></td>
				</tr>";

		$footer = ';;;;;;;;' . number_format($total_posto, 2, ',', '') . ';' . number_format($total_telecontrol, 2, ',', '') . ';;';
		fwrite($csv, $footer . "\n");

		fclose($csv);

		echo "</table>";

        echo "<br>
        	<a href='$link_csv' target='_blank'>       
                <span><img style='width:40px ; height:40px;' src='../imagens/icon_csv.png'></span>
            </a><br>
        	    <span >Download Arquivo CSV</span>
            ";
	}
}

include "rodape.php";

?>

</body>
</html>
