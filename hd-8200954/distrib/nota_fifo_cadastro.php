<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<html>
<head>
<title>Nota FIFO</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1] + " - " + row[2];
	}

	function formatResult(row) {
		return row[0];
	}


	$("#descricao").autocomplete("<?echo 'peca_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1]; return row[2];}
	});

	$("#descricao").result(function(event, data, formatted) {
		$("#referencia").val(data[1]) ;
		$("#descricao").val(data[2]) ;
	});

});

</script>

<center><h1>Nota FIFO</h1></center>

<p>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

<table>

		<td>Referência da Peça</td>
		<td><input type='text' size='10' name='referencia' id='referencia' class="frm"></td>
		<td>Descrição da Peça</td>
		<td><input type='text' size='20' name='descricao'   id='descricao' class="frm"></td>
		<td>Qtde devolucao</td>
		<td colspan='3'><input type='text' size='10' name='qtde' class="frm"></td>
	</tr>
	<tr>
		<td align='center' colspan='3'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		<td align='center' colspan='2'><input type='submit' name='btn_nota'  value='Gerar Nota'></td>
	</tr>
</table>
<br>



</form>
</center>


<?

flush();

$referencia   = trim ($_POST['referencia']);
$descricao    = trim ($_POST['descricao']);
$qtde  = trim ($_POST['qtde']);

if (strlen ($referencia) > 2 and isset($_POST['btn_acao'])) {
	$fabricas = array($telecontrol_distrib); // A variável nem estava definida!
	$sqlx = "SELECT peca,fabrica,referencia,descricao
			FROM tbl_peca
			WHERE (referencia ILIKE '%$referencia%' AND fabrica IN (".implode(",", $fabricas)."))
			OR
			(referencia_pesquisa ILIKE '%$referencia%' AND fabrica IN (".implode(",", $fabricas)."))
			ORDER BY fabrica";
	$resx = pg_exec ($con,$sqlx);

	if(pg_numrows($resx)==0) {
		echo "Peça com a referência $referencia não encontrada";
		exit;
	}


	for ($x = 0; $x < pg_numrows($resx); $x++) {

		$peca    = pg_result($resx,$x,peca);
		$referencia    = pg_result($resx,$x,referencia);
		$descricao    = pg_result($resx,$x,descricao);
		$fabrica = pg_result($resx,$x,fabrica);

		$sql = "SELECT  sum(tbl_faturamento_item.qtde)
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
				WHERE  tbl_faturamento.posto in (4311,20682)
				AND (
					tbl_faturamento.distribuidor IN (
						/* Seleciona apenas os distribuidores da condição nova, descartando quando o posto entrava como
						distribuidor (LRG Britania)*/
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto in ( 4311,20682)
						and distribuidor is not null and distribuidor <> 4311)
					OR
					tbl_faturamento.fabrica in (10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.fabrica <> 0
				AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)
				AND tbl_fabrica.fabrica = $fabrica
				AND tbl_faturamento.conferencia notnull
				and tbl_faturamento.emissao > current_date - interval '5 years'
				AND tbl_faturamento_item.peca = $peca";
		$res = pg_exec ($con,$sql);

		$qtde_entrada = pg_fetch_result($res,0,0);

		$sql = "SELECT  sum(tbl_faturamento_item.qtde)
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
				WHERE  tbl_faturamento.distribuidor in (4311,20682)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.status_nfe='100'
				AND tbl_faturamento.fabrica <> 0
				AND tbl_fabrica.fabrica = $fabrica
				AND tbl_faturamento_item.peca = $peca";
		$res = pg_exec ($con,$sql);

		$qtde_saida = pg_fetch_result($res,0,0);

		$sql = "SELECT  qtde
				FROM tbl_posto_estoque
				WHERE peca = $peca";
		$res = pg_exec ($con,$sql);

		$qtde_estoque = pg_fetch_result($res,0,0);

		flush();
		if(pg_numrows ($res)==0){
			echo "<center><b><span class='vermelho'>$referencia </span>- CÓDIGO DE PEÇA NÃO CADASTRADO</center></b><br>";
			exit;
		} else {
				echo "<br><table align='center' border='0' cellspacing='1' cellpadding='5'>";
				echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
				echo "<td>Referência</td>";
				echo "<td>Descrição</td>";
				echo "<td>Qtde Entrada</td>";
				echo "<td>Qtde Saida</td>";
				echo "<td>Total diferença</td>";
				echo "<td>Qtde Estoque</td>";

				echo "</tr>";

				echo "<tr bgcolor='$cor'>";
				echo "<td>";
				echo $referencia;
				echo "</td>";

				echo "<td>";
				echo $descricao;
				echo "</td>";

				echo "<td align='center'>";
				echo $qtde_entrada;
				echo "</td>";

				echo "<td align='center'>";
				echo $qtde_saida;
				echo "</td>";

				echo "<td align='center'>";
				echo ($qtde_entrada - $qtde_saida);
				echo "</td>";

				echo "<td align='center'>";
				echo $qtde_estoque;
				echo "</td>";
		}
	if ($primeiro_item == 't') {
		echo "</table>";

	}
	}
}

flush();
?>


<?


if (isset($_POST['btn_acao']) AND (strlen ($descricao) < 3 AND strlen ($referencia) < 3 AND strlen ($qtde) < 3)) {
	echo "<br><br><center><b class='vermelho'>DIGITE NO MÍNIMO 3 CARACTERES PARA A BUSCA!</center></b>";
}


?>

</body>
</html>
