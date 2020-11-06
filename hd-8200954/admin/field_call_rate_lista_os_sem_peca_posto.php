<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$data_inicial 		= $_GET["data_ini"];
$data_final 		= $_GET["data_fim"];
$data_filtro 		= $_GET["data_filtro"];
$linhas				= $_GET["linhas"];
$consumidor_revenda = $_GET['cr'];
$posto 				= $_GET['posto'];

?>

<!DOCTYPE html />
<html>
	<head>
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

		<script>
			$(function () {
				$.dataTableLupa();
			});
		</script>
	</head>

	<body>
	<div id="container_lupa" style="overflow-y:auto;">

<?php

switch($consumidor_revenda) {
		case "C":
			$cond_consumidor_revenda = " AND tbl_os.consumidor_revenda='C' ";
		break;

		case "R":
			$cond_consumidor_revenda = " AND tbl_os.consumidor_revenda='R' ";
		break;

		default:
			$cond_consumidor_revenda = "  ";
	}

	switch($data_filtro) {
		case "finalizadas":
			$cond_x = " AND tbl_os.data_fechamento IS NOT NULL ";
		break;

		case "nao_finalizada":
			$cond_x = " AND tbl_os.data_fechamento IS NULL ";
		break;

		case "analisadas":
			$cond_x = " AND tbl_os.defeito_constatado IS NOT NULL AND tbl_os.solucao_os IS NOT NULL ";
		break;

		case "nao_analisadas":
			$cond_x = " AND tbl_os.defeito_constatado IS NULL AND tbl_os.solucao_os IS NULL ";
		break;
	}

	if(!empty($linhas)){
		$cond_1 = "AND tbl_produto.linha IN ($linhas) ";
	}

	$sql="
		SELECT DISTINCT(tbl_os.os)
		INTO TEMP tmp_fcr_ossempeca_$login_admin
		FROM tbl_os
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		JOIN tbl_os_produto ON tbl_os.os              = tbl_os_produto.os
		JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE tbl_os.fabrica=$login_fabrica
		AND   tbl_os.posto = $posto
		AND (tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final')
		$cond_1 $cond_x;

		SELECT 	tbl_os.os,
			tbl_os.sua_os,
			tbl_posto.nome                                    AS posto_nome,
			tbl_os.defeito_reclamado,
			CASE WHEN tbl_os.fabrica in (15,35,122,81,114,124,123)
				THEN tbl_os.defeito_reclamado_descricao
			ELSE
				tbl_defeito_reclamado.descricao
			END as defeito_reclamado_descricao,
			tbl_os.defeito_constatado,
			tbl_defeito_constatado.descricao as defeito_constatado_descricao,
			tbl_os.solucao_os,
			tbl_defeito_constatado_grupo.descricao as defeito_constatado_grupo,
			tbl_solucao.descricao as solucao,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	,
			tbl_os.fabrica,
			tbl_produto.descricao as produto_descricao,
			tbl_os.consumidor_revenda
		INTO TEMP tmp_fcr_ossempeca2_$login_admin
		FROM tbl_os
		JOIN tbl_produto on tbl_produto.produto=tbl_os.produto
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado=tbl_os.defeito_reclamado
		LEFT JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado=tbl_os.defeito_constatado
		LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os
		LEFT JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_os.defeito_constatado_grupo
		WHERE (tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final')
		AND tbl_os.fabrica = $login_fabrica
		AND tbl_os.posto = $posto
		$cond_1 
		$cond_x
		$cond_consumidor_revenda
		AND tbl_os.os NOT IN( select os from tmp_fcr_ossempeca_$login_admin);

		SELECT * FROM  tmp_fcr_ossempeca2_$login_admin X
		ORDER BY X.defeito_reclamado_descricao, X.defeito_constatado_descricao, X.defeito_constatado_descricao";
#echo nl2br($sql); exit;
	$res = pg_query($con,$sql);
	$rows = pg_num_rows($res);

	$sql = "SELECT nome,codigo_posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE posto = $posto AND fabrica = $login_fabrica";
	$resP = pg_query($con,$sql);
	$cod_posto  = pg_fetch_result($resP, 0, 'codigo_posto');
	$nome_posto = pg_fetch_result($resP, 0, 'nome');
	?>

		<div class='alert alert-success'>
			<h4><?=$cod_posto?> - <?=$nome_posto?></h4>
		</div>

		<div id="border_table">		
			<table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large'>
				<thead>				
					<tr class='titulo_tabela'>
						<td >OS</td>
						<td>C/R</td>
						<td>Produto</td>
						<td>Abertura</td>
						<td>Fechamento</td>
						<td>Defeito Reclamado</td>
						<td>Defeito Constatado</td>
						<td>Solução</td>
					</tr>
				</thead>
				<tbody> 
					<?php
					for ($i=0; $i < $rows; $i++){

							$os								= trim(pg_result($res,$i,os));
							$sua_os							= trim(pg_result($res,$i,sua_os));
							$defeito_reclamado_descricao 	= trim(pg_result($res,$i,defeito_reclamado_descricao));
							$defeito_constatado_descricao 	= trim(pg_result($res,$i,defeito_constatado_descricao));
							$solucao 						= trim(pg_result($res,$i,solucao));
							$abertura 						= trim(pg_result($res,$i,abertura));
							$fechamento 					= trim(pg_result($res,$i,fechamento));
							$posto_nome 					= trim(pg_result($res,$i,posto_nome));
							$produto_descricao				= trim(pg_result($res,$i,produto_descricao));
							$consumidor_revenda_banco		= pg_result($res, $i, consumidor_revenda);
						
						?>			
						<tr bgcolor='<?=$cor?>'>
							<td align='left'><a href='<?="os_press.php?os=$os"?>' target='blank'><font size='1'><?=$sua_os?></font></a></td>
							<td><font size='1'><?=$consumidor_revenda_banco?></font></td>
							<td><?=$produto_descricao?></font></td>
							<td><font size='1'><?=$abertura?></font></td>
							<td><font size='1'><?=$fechamento?></font></td>
							<td align='left' nowrap><font size='1'><?=$defeito_reclamado_descricao?></font></td>			
							<td align='left' nowrap><font size='1'><?=$defeito_constatado_descricao?></font></td>
							<td align='left' nowrap><font size='1'><?=$solucao?></font></td>
						</tr>
				<?  } ?>
					
				</tbody>
			</table>
		</div>
	</div>

	</body>
</html>

