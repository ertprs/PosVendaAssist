<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_REQUEST["opcao"]) {
	$opcao = $_REQUEST['opcao'];
	$aux_data_inicial = $_REQUEST['dt_ini'];
	$aux_data_final   = $_REQUEST['dt_fin'];

	if ($opcao == 'aberta') {
		$sqlOS = "	SELECT  os_revenda,
							data_abertura,
							data_fechamento,
							codigo_posto || ' - ' || nome AS nome_posto
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND data_fechamento IS NULL 
					AND data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_posto_fabrica.posto <> 6359
					AND tbl_os_revenda.excluida IS NOT TRUE
					ORDER BY nome ASC";
	} else if ($opcao == 'fechada') {
		$sqlOS = "	SELECT  os_revenda,
							data_abertura,
							data_fechamento,
							codigo_posto || ' - ' || nome AS nome_posto
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND data_fechamento NOTNULL 
					AND data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_posto_fabrica.posto <> 6359
					AND tbl_os_revenda.excluida IS NOT TRUE
					ORDER BY nome ASC";		
	} else if ($opcao == 'processada') {
		$sqlOS = "	SELECT  os_revenda,
							data_abertura,
							data_fechamento,
							codigo_posto || ' - ' || nome AS nome_posto
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND data_fechamento IS NULL 
					AND tbl_posto_fabrica.posto <> 6359
					AND tbl_os_revenda.excluida IS NOT TRUE
					ORDER BY nome ASC";
	} else if ($opcao == '10dias') {
		$sqlOS = "	SELECT  os_revenda,
					        data_abertura,
					        data_fechamento,
					        codigo_posto || ' - ' || nome AS nome_posto
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND tbl_os_revenda.data_fechamento NOTNULL
					AND tbl_os_revenda.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_os_revenda.excluida IS NOT TRUE
					AND EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.data_abertura::timestamp)) <= 10
					AND tbl_posto_fabrica.posto <> 6359
					ORDER BY nome ASC";
	} else if ($opcao == '30dias') {
		$sqlOS = "	SELECT  os_revenda,
					        data_abertura,
					        data_fechamento,
					        codigo_posto || ' - ' || nome AS nome_posto
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND tbl_os_revenda.data_fechamento NOTNULL
					AND tbl_os_revenda.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_os_revenda.excluida IS NOT TRUE
					AND EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.data_abertura::timestamp)) > 10
					AND EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.data_abertura::timestamp)) <= 30
					AND tbl_posto_fabrica.posto <> 6359
					ORDER BY nome ASC";
	} else if ($opcao == '31dias') {
		$sqlOS = "	SELECT  os_revenda,
					        data_abertura,
					        data_fechamento,
					        codigo_posto || ' - ' || nome AS nome_posto
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND tbl_os_revenda.data_fechamento NOTNULL
					AND tbl_os_revenda.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_os_revenda.excluida IS NOT TRUE
					AND EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.data_abertura::timestamp)) > 30
					AND tbl_posto_fabrica.posto <> 6359
					ORDER BY nome ASC";
	} else {
		$sqlOS = "	SELECT  os_revenda,
					        data_abertura,
					        data_fechamento,
					        codigo_posto || ' - ' || nome AS nome_posto
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND tbl_os_revenda.data_fechamento NOTNULL
					AND tbl_os_revenda.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_os_revenda.excluida IS NOT TRUE
					AND tbl_posto_fabrica.posto <> 6359
					ORDER BY data_fechamento - data_abertura DESC LIMIT 1";
	}

	$resOS = pg_query($con, $sqlOS);
	if (pg_num_rows($resOS) > 0) {
		$result_os = pg_fetch_all($resOS);  
	}

}
?>

<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>

		<script src="plugins/resize.js"></script>

<script type="text/javascript">
	$(function() {

	});
</script>
	</head>
	<body>
		<div id="" style="overflow-y:auto;z-index:1">
			<?php if(count($result_os) > 0){ ?>
				<table id="detalhes_os" class="display nowrap table table-striped table-bordered table-fixed table-hover" cellspacing="0" width="100%" >
					<thead>
						<tr class='titulo_tabela'>
							<th colspan="10"><?=traduz("Relatorio OSs Detalhado")?></th>
						</tr>
						<tr class='titulo_coluna' >
							<th><?=traduz("OS")?></th>
							<th><?=traduz("Data Abertura")?></th>
							<th><?=traduz("Data Fechamento")?></th>
							<th><?=traduz("Posto")?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($result_os as $key => $value) {
							
							$os 				= $value["os_revenda"];
							$data_abertura		= $value["data_abertura"];
							$data_fechamento    = $value["data_fechamento"];
							$nome_posto			= $value["nome_posto"];
						?>
							<tr>
								<td class="tac"><a href="os_revenda_press.php?os_revenda=<?=$os?>" target="_blank"><?=$os?></a></td>
								<td class="tac"><?=$data_abertura?></td>
								<td class="tac"><?=$data_fechamento?></td>
								<td class="tac"><?=$nome_posto?></td>
							</tr>
						<?php
						}
						?>
					</tbody>
			<?php }else{ ?>
				<div class="alert alert-warning">
					<h4><?=traduz("Nenhum resultado encontrado")?>.</h4>
			    </div>
			<?}?>
		</div>
	</body>
</html>
