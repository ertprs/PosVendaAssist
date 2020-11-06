<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_REQUEST["opcao"]) {
	
	$aux_data_inicial = $_REQUEST['dt_ini'];
	$aux_data_final   = $_REQUEST['dt_fin'];

	if (trim($_REQUEST["origem"]) == "Total") {
		$origem = "";
	} else {
		$sql_origem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica AND descricao = '".trim($_REQUEST["origem"])."' LIMIT 1";
		$res_origem = pg_query($con, $sql_origem);
		if (pg_num_rows($res_origem) > 0) {
			$origem = " AND tbl_hd_chamado_extra.hd_chamado_origem = ".pg_fetch_result($res_origem, 0, 'hd_chamado_origem');
		}
	}

	if (trim($_REQUEST["tipo"]) == "Total Protocolos") {
		$tipo = "";
	} else if (trim($_REQUEST["tipo"]) == "Revenda") {
		$tipo = " AND tbl_hd_chamado_extra.consumidor_revenda = 'R'";
	} else if (trim($_REQUEST["tipo"]) == "Consumidor1") {
		$tipo = " AND tbl_hd_chamado_extra.consumidor_revenda = 'C'";
	} else if (trim($_REQUEST["tipo"]) == "Construtora") {
		$tipo = " AND tbl_hd_chamado_extra.consumidor_revenda = 'S'";
	} else {
		$tipo = " AND tbl_hd_chamado_extra.consumidor_revenda = '".trim($_REQUEST["tipo"])."'";
	}

	$sqlHD = "	SELECT TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data,
					   tbl_hd_chamado.hd_chamado,
					   tbl_hd_chamado.status,
					   tbl_hd_classificacao.descricao AS classificacao,
					   tbl_hd_chamado_extra.nome,
					   tbl_cidade.nome AS cidade,
					   tbl_cidade.estado AS estado,
					   tbl_hd_chamado_origem.descricao AS origem
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING(hd_chamado)
				JOIN tbl_hd_chamado_origem USING(hd_chamado_origem)
				JOIN tbl_hd_classificacao USING(hd_classificacao)
				JOIN tbl_cidade USING(cidade)
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				$tipo
				$origem";
	$resHD = pg_query($con, $sqlHD);
	if (pg_num_rows($resHD) > 0) {
		$result_hd = pg_fetch_all($resHD);  
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
	</head>
	<body>
		<div id="" style="overflow-y:auto;z-index:1">
			<?php if(count($result_hd) > 0){ ?>
				<table id="detalhes_os" class="display nowrap table table-striped table-bordered table-fixed table-hover" cellspacing="0" width="100%" >
					<thead>
						<tr class='titulo_tabela'>
							<th colspan="10"><?=traduz("Relatorio Contato Atendimento Callcenter")?></th>
						</tr>
						<tr class='titulo_coluna' >
							<th><?=traduz("Data")?></th>
							<th><?=traduz("Nº Chamado")?></th>
							<th><?=traduz("Cliente")?></th>
							<th><?=traduz("Cidade")?></th>
							<th><?=traduz("Estado")?></th>
							<th><?=traduz("Classificação")?></th>
							<th><?=traduz("Origem")?></th>
							<th><?=traduz("Status")?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($result_hd as $key => $value) {
							$data 			= $value["data"];
							$hd_chamado		= $value["hd_chamado"];
							$cliente        = traduz($value["nome"]);
							$cidade			= traduz($value["cidade"]);
							$estado			= $value["estado"];
							$classificacao	= traduz($value["classificacao"]);
							$origem			= traduz($value["origem"]);
							$status			= traduz($value["status"]);
						?>
							<tr>
								<td class="tac"><?=$data?></td>
								<td class="tac"><a href="callcenter_interativo_new.php?callcenter=<?=$hd_chamado?>" target="_blank"><?=$hd_chamado?></a></td>
								<td class="tac"><?=$cliente?></td>
								<td class="tac"><?=$cidade?></td>
								<td class="tac"><?=$estado?></td>
								<td class="tac"><?=$classificacao?></td>
								<td class="tac"><?=$origem?></td>
								<td class="tac"><?=$status?></td>
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
