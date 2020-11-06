<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

$serie = strtoupper(trim($_REQUEST['serie']));
$forma = trim($_REQUEST['forma']);

?>
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">

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

		<script type='text/javascript'>
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			<?php if ($login_fabrica == 3) { ?> 
				$(function() {

					$(".tbl_pesquisa_produto").click(function() {
						
						var referencia = $(this).data('referencia');
						var descricao  = $(this).data('descricao');
						var voltagem   = $(this).data('voltagem');
						var serie      = $(this).data('serie');

						window.parent.retorna_serie(referencia, descricao, voltagem, serie);
						
						$(".tbl_pesquisa_produto").css("background","LightGray");
						$(this).css("background","#00ff00");
						$('#btn_confirmar_produto').text("Confirmar Modelo: " + descricao);
						$('#btn_confirmar_cancelar').show();
					});
				});
			<?php } ?>

			function escolherProduto () {
				
				window.parent.Shadowbox.close();
			}

			function cancelarEscolhaProduto () {
				$(".tbl_pesquisa_produto").css("background","transparent");

				$('#btn_confirmar_cancelar').hide();
			}

		</script>

	</head>
	
	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
			$msg = "<h4 style='color:red;text-align: center;'>FAVOR SELECIONAR O MODELO CORRETO DO PRODUTO</h4>";
			echo "<div class='lp_nova_pesquisa'>";
				echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='forma' value='$forma' />";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Serie</label>
								<input type='text' name='serie' value='$serie' style='width: 270px' maxlength='15' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
				echo "<h2 style='text-align: center;''>" . $msg . "</h2>";
			echo "</div>";

//$n_serie = preg_replace('/(.*)([A-Z][0-9]+[A-Z])$/', '\2', $serie);
$n_serie = preg_replace('/(.*)([A-Z0-9]{5})$/', '\2', $serie);

$serie_pesquisa =  ($serie != $n_serie) ? $n_serie :$serie;

$cond_ativacao = "AND tbl_produto.ativo"; 
if ($login_fabrica == 3) {
	$cond_ativacao = "AND (tbl_produto.ativo IS TRUE OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't'))";
}
$sql = "SELECT *
		  FROM tbl_produto
		 WHERE (radical_serie ilike '%$serie_pesquisa%' OR
		       radical_serie2 ilike '%$serie_pesquisa%' OR
		       radical_serie3 ilike '%$serie_pesquisa%' OR
		       radical_serie4 ilike '%$serie_pesquisa%' OR
		       radical_serie5 ilike '%$serie_pesquisa%' OR
		       radical_serie6 ilike '%$serie_pesquisa%')
		  AND tbl_produto.fabrica_i = $login_fabrica
		  $cond_ativacao";

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
	echo "window.parent.retorna_serie('".pg_fetch_result($res,0,'referencia')."','".pg_fetch_result($res,0,'descricao')."','".pg_fetch_result($res,0,'voltagem')."','$serie'); window.parent.Shadowbox.close();";
	echo "</script>\n";

} else{ 

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
		echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	//echo "<table width='100%' border='0' class='tabela' cellspacing='1'>\n";
	echo"<table width='100%' border='0' class='tabela table table-bordered table-fixed' cellspacing='1'>";
		
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
			$voltagem   = trim(pg_result($res, $i, 'voltagem'));

			if ($login_fabrica != 3) {

				$onclick = "onclick= \"javascript: window.parent.retorna_serie('$referencia','$descricao','$voltagem','$serie');window.parent.Shadowbox.close();\"";
			} else { 

				$onclick = "data-referencia='{$referencia}' data-descricao='{$descricao}' data-voltagem='{$voltagem}' data-serie='{$serie}'";
			}

			$cor = ($i % 2 == 0) ? "#F7F5F0" : $cor = "#F1F4FA";

			if ($login_fabrica != 3) {
				echo "<tr style='background: $cor' $onclick>";
			} else {
				echo "<tr $onclick class='tbl_pesquisa_produto' style='background: $cor'>";
			}
				echo "<td>\n";
				echo "$referencia\n";
				echo "</td>\n";
				echo "<td>\n";
				echo "$descricao\n";
				echo "</td>\n";
			echo "</tr>\n";

		}

	echo "</table>\n";

?>
	<div id="btn_confirmar_cancelar" hidden style="text-align:center; margin-top: 10%">
		<button class="btn btn-success" type="button" id="btn_confirmar_produto" onclick="escolherProduto(this)">Confirmar Modelo</button>
		<button class="btn btn-danger" type="button" id="btn_cancelar_produto" onclick="cancelarEscolhaProduto(this)">Cancelar</button>
	</div>
<?php } ?>

</body>
</html>
