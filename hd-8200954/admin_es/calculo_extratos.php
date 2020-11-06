<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';

	$extratos	= trim($_REQUEST["extratos"]);

	$sql = "SELECT 
					SUM(tbl_os.pecas + tbl_os.mao_de_obra) AS valor_total
				 FROM tbl_extrato
				 	JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica
				 	JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
				 WHERE tbl_extrato.extrato IN ($extratos);";

	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$valor_total = pg_result($res,0,valor_total);

		$sqlE = "SELECT tbl_linha.nome,
						tbl_familia.descricao,
						COUNT(tbl_os_extra.os) AS qtde,
						SUM(tbl_os.pecas + tbl_os.mao_de_obra) AS valor
					FROM tbl_extrato
					JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica
					JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha 
					JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica
					WHERE tbl_extrato.extrato IN ($extratos)
					GROUP BY tbl_linha.nome,tbl_familia.descricao";
		$resE = pg_query($con,$sqlE);

	}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<style type="text/css" media="all">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

			table.tabela tr td{
				font-family: verdana;
				font-size: 11px;
				border-collapse: collapse;
				border:1px solid #596d9b;
			}

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
		</style>

	</head>

	<body>
		<br />
		<table align="center" width="700" class="tabela" cellpadding='2' cellspacing='1'>
			<caption class="titulo_coluna">Calcular las OS de los extractos seleccionados</caption>
			<tr class="titulo_coluna">
				<th>Linea</th>
				<th>Familia</th>
				<th>Cant. OS</th>
				<th>Valor</th>
				<th>%</th>
			</tr>
			<?php
			if(pg_numrows($resE) > 0){
				for($i = 0; $i < pg_numrows($resE); $i++){
					$linha   = pg_result($resE,$i,nome);
					$familia = pg_result($resE,$i,descricao);
					$qtde    = pg_result($resE,$i,qtde);
					$valor   = pg_result($resE,$i,valor);
					$procentagem = ($valor * 100) / $valor_total;

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			?>
					<tr bgcolor="<?php echo $cor;?>">
						<td><?php echo $linha; ?></td>
						<td><?php echo $familia; ?></td>
						<td align='center'><?php echo $qtde; ?></td>
						<td align='right'><?php echo number_format($valor,2,',','.'); ?></td>
						<td align='right'><?php echo number_format($procentagem,2,',','.'); ?></td>
					</tr>
			<?php

				}
			}
			?>
			</table>
	</body>
</html>
