<?php 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';
include_once '../helpdesk/mlg_funciones.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

	$AuditorLog = new AuditorLog;
	$embarque = $_GET['embarque'];
	$fabrica = $_GET['fabrica'];
	$dados_log = $AuditorLog->getLog('tbl_embarque', "$fabrica*$embarque");
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>
	</head>
	<body>
		<div class='container'>
			<?php 
			if(empty($dados_log)){?>
			<div class="alert alert-warning">                
                <h4>Nenhum log encontrado</h4>
            </div>
			<?php }else{
			?>
			<table  id='relatorio_listagem' name='relatorio_listagem' class='table table-striped table-bordered table-hover'>
				<tr class = 'titulo_coluna'>
					<th colspan="4">Log Embarque - <?=$embarque?></th>
				</tr>
				<tr class='titulo_coluna'>
					<th>Embarque</th>
					<th>Data</th>
					<th>Admin</th>
					<th>Motivo</th>
				</tr>
				<?php foreach($dados_log as $chave => $value){
					echo "<tr>";
						echo "<td class='tac'>".$value['content']['dados']['embarque']."</td>";
						echo "<td class='tac'>".$value['content']['dados']['data']."</td>";
						echo "<td class='tac'>".$value['content']['dados']['admin']."</td>";
						echo "<td nowrap>".$value['content']['dados']['motivo']."</td>";
					echo "</tr>";
				}?>
			</table>
			<?php } ?>
		</div>
	</body>
</html>

