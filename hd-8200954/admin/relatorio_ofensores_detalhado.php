<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$tipo 				= $_GET['tipo'];
$tipo_pesquisa      = $_GET['tipo_pesquisa'];
$mes           		= $_GET['mes'];
$posto              = $_GET['posto'];
$data_inicial  		= $_GET['data_inicial'];
$data_final    		= $_GET['data_final'];
$status  			= $_GET['status'];
$status_pedido      = $_GET['status_pedido'];

switch ($tipo) {
	case 'qtde_finalizadas':
		$condPesquisa = ($tipo_pesquisa == 'ordem_servico') ? "AND tbl_os.finalizada IS NOT NULL" : "AND tbl_status_pedido.descricao = 'Entregue'";
		$titulo = "Ordens de Serviço Finalizadas";
		break;
	case 'status':
		$condPesquisa = ($tipo_pesquisa == 'ordem_servico') ? "AND tbl_os.status_checkpoint = {$status}" : "AND tbl_pedido.status_pedido = {$status}";
		$titulo = "Ordens de Serviço por Status";
		break;
	default:
		$condPesquisa = "";
		$titulo = "Listar Ordens de Serviço";
	break;
}

if ($tipo_pesquisa == "pedido_faturado") {
	$titulo = "Pedidos Faturados por Status";
}

if ($tipo_pesquisa == 'ordem_servico') {
	if (empty($posto)) {
		$cond = "AND to_char(tbl_os.data_abertura, 'mm') = '{$mes}'";
	} else {
		$cond = "AND tbl_os.posto = {$posto}";
	}
} else {
	if (empty($posto)) {
		$cond = "AND to_char(tbl_pedido.data, 'mm') = '{$mes}'";
	} else {
		$cond = "AND tbl_pedido.posto = '{$posto}'";
	}

}

if ($tipo_pesquisa == 'ordem_servico') {
	$sql = "SELECT tbl_os.os as id, 
				 to_char(tbl_os.data_abertura, 'dd/mm/yyyy') as data_abertura, 
				 tbl_posto.nome as nome_posto,
				 to_char(tbl_os.finalizada, 'dd/mm/yyyy') as finalizada,
				 tbl_status_checkpoint.descricao as status
			  FROM tbl_os
			  JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			  JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
			  WHERE tbl_os.fabrica = {$login_fabrica}
			  AND tbl_os.data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}'
			  AND tbl_os.excluida IS NOT TRUE
			  {$cond}
			  {$condPesquisa}
			  ORDER BY tbl_os.data_abertura DESC";

} else {
	$sql = "SELECT DISTINCT ON (tbl_pedido.pedido)
						 tbl_pedido.pedido as id,
						 to_char(tbl_pedido.data, 'dd/mm/yyyy') as data_abertura,
						 tbl_posto.nome as nome_posto,
						 (
						 	SELECT to_char(tbl_pedido_status.data, 'dd/mm/yyyy')
						 	FROM tbl_pedido_status
						 	JOIN tbl_status_pedido ON tbl_pedido_status.status = tbl_status_pedido.status_pedido
						 	WHERE tbl_status_pedido.descricao = 'Entregue'
						 	AND tbl_pedido_status.pedido = tbl_pedido.pedido
						 	ORDER BY tbl_pedido_status.pedido_status DESC
						 	LIMIT 1
						 ) as finalizada,
						 tbl_status_pedido.descricao as status
				  FROM tbl_pedido
				  JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
				  AND tbl_tipo_pedido.pedido_faturado IS TRUE
				  JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
				  JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
				  WHERE tbl_pedido.fabrica = {$login_fabrica}
				  AND tbl_pedido.finalizado IS NOT NULL
				  AND tbl_pedido.data BETWEEN '{$data_inicial}' AND '{$data_final}'
				  {$condPesquisa}
				  {$cond}
				  ";
}

//exit(nl2br($sql));
$res = pg_query($sql);


?>
<html>
	<head>
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<script src="bootstrap/js/bootstrap.js"></script>
		<style>

			body {
				margin: 30px;
			}

		</style>
	</head>
	<body>
		<table class="table table-bordered">
			<thead>
				<tr class="titulo_tabela">
					<th colspan="100%"><?= $titulo ?></th>
				</tr>
				<tr class="titulo_coluna">
					<th>Número</th>
					<th>Data Abertura</th>
					<th>Finalizada</th>
					<th>Nome Posto</th>
					<th>Status OS</th>
				</tr>
			</thead>
			<tbody>
				<?php
					while ($dados = pg_fetch_object($res)) {

						$link = ($tipo_pesquisa == 'ordem_servico') ? "os_press.php?os={$dados->id}" : "pedido_admin_consulta.php?pedido={$dados->id}";

						?>
						<tr>
							<td class="tac">
								<a href="<?= $link ?>" target="_blank"><?= $dados->id ?></a>
							</td>
							<td class="tac"><?= $dados->data_abertura ?></td>
							<td class="tac"><?= $dados->finalizada ?></td>
							<td><?= $dados->nome_posto ?></td>
							<td ><?= $dados->status ?></td>
						</tr>
				<?php
					}
				?>
			</tbody>
		</table>
	</body>
</html>