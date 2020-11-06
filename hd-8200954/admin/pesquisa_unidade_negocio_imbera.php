<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include_once 'funcoes.php';
$nome = $_GET['nome'];
$sql = "SELECT parametros_adicionais FROM tbl_cliente_admin WHERE nome = '{$nome}' AND fabrica = " . $login_fabrica;
$res = pg_query($con, $sql);
$unidade = json_decode(pg_fetch_result($res, 0, parametros_adicionais))->unidadeNegocio;
$listagem = '';
if (!is_array($unidade)) {
	$unidade = str_split($unidade, 4);
}
foreach ($unidade as $codigo_unidade) {
	$sqlUnidade = "SELECT nome FROM tbl_unidade_negocio WHERE codigo = '{$codigo_unidade}'";
	$resUnidade = pg_query($con, $sqlUnidade);
	$nome_unidade = pg_fetch_result($resUnidade, 0, nome);
	$listagem[$codigo_unidade] = $nome_unidade;
}
?>
<html>
	<head>
	</head>
	<body style="height: 100%; width: 100%; background: white;">
		<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
		<style type="text/css">
			@import "../plugins/jquery/datepick/telecontrol.datepick.css";
			@import "../css/lupas/lupas.css";
		</style>
		<div class="lp_header">
			<a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<tr>
				<th>
					Unidade de Negócio 
				</th>
			</tr>
			<?php foreach ($listagem as $codigo => $nome) {
				echo "<tr data-unidade='{$codigo} - {$nome}'>";
					echo "<td>";
						echo "{$codigo} - {$nome}";
					echo "</td>";
				echo "</tr>";
			} ?>
		</table>
		<script type="text/javascript">
			$('[data-unidade]').on('click', function() {
				window.parent.retorna_unidade_imbera($(this).data('unidade'));
			});	
		</script>
	</body>
</html>