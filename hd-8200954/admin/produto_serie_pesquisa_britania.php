
<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'cabecalho_pop_produtos.php';

?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Número de Série Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
	<style type="text/css">

	.table tr {
		cursor: pointer;
	}

	</style>
	<script>
		$(function(){

			<?php
			if ($login_fabrica == 3) { ?>

				$(".linha_produto").click(function(){

					let referencia = $(this).attr("referencia");
					let descricao  = $(this).attr("descricao");

					$("#botoes_confirmar").show();

					$("#btn_confirmar_produto").attr({
						"referencia": referencia,
						"descricao": descricao
					}).text("Confirmar Modelo: " + descricao);

					$(".linha_produto").css("background","white");
					$(this).css("background","#00ff00");

				});

			<?php
			}
			?>
		});

		function cancelarEscolhaProduto () {
			$(".linha_produto").css("background","transparent");
			$("#botoes_confirmar").hide();
		}

	</script>
</head>
<body>
<br /><?php

//$n_serie = preg_replace('/(.*)([A-Z][0-9]+[A-Z])$/', '\2', $serie);
$n_serie = preg_replace('/(.*)([A-Z0-9]{5})$/', '\2', $serie);

if ($serie != $n_serie) {
	$serie = $n_serie;
}

$sql = "SELECT *
		  FROM tbl_produto
		 WHERE (radical_serie ilike '%$serie%' OR
		       radical_serie2 ilike '%$serie%' OR
		       radical_serie3 ilike '%$serie%' OR
		       radical_serie4 ilike '%$serie%' OR
		       radical_serie5 ilike '%$serie%' OR
		       radical_serie6 ilike '%$serie%')";

$res = pg_exec($con,$sql);
$tot = pg_numrows($res);

if ($tot == 0) {

	echo "<h1>Nenhum Produto encontrado!</h1>";
	echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;

} else if ($tot == 1) {

	echo "<script language='JavaScript'>\n";
		echo "referencia.value='".trim(pg_result($res, 0, 'referencia'))."';";
		echo "descricao.value='".trim(pg_result($res, 0, 'descricao'))."';";
		echo "this.close();";
	echo "</script>\n";

} else {

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
		echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	echo "<h4 style='color:red;'>FAVOR SELECIONAR O MODELO CORRETO DO PRODUTO</h4>";
	echo "<table width='100%' border='0' class='tabela table table-bordered table-fixed' cellspacing='1'>\n";
		echo "<tr class='titulo_coluna'>";
			echo "<th colspan='100%'>";
				echo "<font style='font-size:14px;'>Parâmetros de Pesquisa</font>";
			echo "</th>";
		echo "</tr>";
		echo "<tr class='titulo_coluna'>";
			echo "<th>Referência</th>";
			echo "<th>Descrição</th>";
		echo "</tr>";

		for ($i = 0; $i < $tot; $i++) {

			$referencia = trim(pg_result($res, $i, 'referencia'));
			$descricao  = trim(pg_result($res, $i, 'descricao'));

			$cor = ($i % 2 == 0) ? "#F7F5F0" : $cor = "#F1F4FA";

			if ($login_fabrica != 3) {
				$href = "href = \"javascript: referencia.value='{$referencia}'; descricao.value='{$descricao}'; this.close(); \"";
			}

			echo "<tr width='40%' style='background-color: white;' border='1' class='linha_produto' referencia='{$referencia}' descricao='{$descricao}'>\n";
				echo "<td>\n";
					echo "<a {$href}>";
						echo "$referencia\n";
					echo "</a>\n";
				echo "</td>\n";
				echo "<td>\n";
					echo "<a {$href}>";
						echo "$descricao\n";
					echo "</a>\n";
				echo "</td>\n";
			echo "</tr>\n";

		}

	echo "</table>\n";
}?>
<?php if ($login_fabrica == 3) { ?>

	<div id="botoes_confirmar" style="text-align:center; margin-top: 10%" hidden>
		<button class="btn btn-success" id="btn_confirmar_produto" type="button" onclick="referencia.value = this.getAttribute('referencia');descricao.value = this.getAttribute('descricao');window.close();">Confirmar Modelo </button>
			<button type="button" class="btn btn-danger" id="btn_cancelar_produto" onclick="cancelarEscolhaProduto(this)">Cancelar</button>
	</div>

<?php } ?>

</body>
</html>