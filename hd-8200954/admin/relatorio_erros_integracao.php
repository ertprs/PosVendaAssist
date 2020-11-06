<?php
/**
 * 
 * @author Gabriel Tinetti
 *
*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';


if (array_key_exists('btn_search', $_POST)) {
	$inicialDate = $_POST['inicial_date'];
	$finalDate = $_POST['final_date'];
	
	$queryDate = "SELECT '{$finalDate}'::date - '{$inicialDate}'::date AS dias";
	$resultDate = pg_query($con, $queryDate);
	$resultDate = pg_fetch_result($resultDate, 0, "dias");
	
	if ($resultDate > 7) {
		$msg_erro .= "O intervalo entre as datas não deve ser superior a 7 dias. <br />";
	}

	if (empty($msg_erro)) {
		$queryRoutine = "SELECT 
							trsle.error_message,
							trsle.create_at
						FROM tbl_routine tr
						JOIN tbl_routine_schedule trs ON trs.routine = tr.routine
						JOIN tbl_routine_schedule_log trsl ON trsl.routine_schedule = trs.routine_schedule
						JOIN tbl_routine_schedule_log_error trsle ON trsle.routine_schedule_log = trsl.routine_schedule_log
						WHERE tr.factory = {$login_fabrica}
						AND lower(tr.context) = 'atendimentos mercado livre'
						AND trsle.create_at BETWEEN '{$inicialDate} 00:00:00'::timestamp AND '{$finalDate} 23:59:00'::timestamp
						ORDER BY trsle.create_at DESC";
		$resultRoutine = pg_query($con, $queryRoutine);
		$resultRoutine = pg_fetch_all($resultRoutine);
	}
} elseif (empty($_POST)) {
	$queryRoutine = "SELECT 
						trsle.error_message,
						trsle.create_at
					FROM tbl_routine tr
					JOIN tbl_routine_schedule trs ON trs.routine = tr.routine
					JOIN tbl_routine_schedule_log trsl ON trsl.routine_schedule = trs.routine_schedule
					JOIN tbl_routine_schedule_log_error trsle ON trsle.routine_schedule_log = trsl.routine_schedule_log
					WHERE tr.factory = {$login_fabrica}
					AND lower(tr.context) = 'atendimentos mercado livre'
					AND trsle.create_at + INTERVAL '7 day' > CURRENT_TIMESTAMP
					ORDER BY trsle.create_at DESC";
	$resultRoutine = pg_query($con, $queryRoutine);
	$resultRoutine = pg_fetch_all($resultRoutine);
}

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "RELATÓRIO DE ERROS MERCADO LIVRE";
include "cabecalho_new.php";

$plugins = array("datepicker", "mask");
include "plugin_loader.php";

?>

<style type="text/css">
	.pages-list {
		width:100%;
	}

	#pages-wrapper tr td {
		text-align:center;
		font-size:12px;
		padding:5px;
	}

	.form_busca {
		padding:20px 0px;
		background-color:#D9E2EF;
	}

	.form_title {
		background-color:#596D9B;
		font-family:'Arial';
		font-size:16px;
		color:#FFF;
		text-align:center;
		font-weight:bold;
		padding:5px;
	}

	.form-group {
		padding: 10px 0;
	}

	.form-group input {
		width:100%;
	}

	.sender-container {
		text-align:center;
	}

	.form-warning {
		background-color:#EF151C;
		font-size:14px;
		font-family:'Arial';
		font-weight:bold;
		text-align:center;
		color:#FFF;
		padding:5px 0px;
	}

	p {
		text-align:center;
		font-weight:bold;
		padding:5px;
	}

	thead {
		font-size:14px;
		background-color:#596D9B;
		color:#FFF;
	}

</style>

<div class="row-fluid">
	<?php if (strlen($msg_erro) > 0) { ?>
		<div class="form-warning"><?= $msg_erro ?></div>
	<?php } ?>
	<div class="form_title">Parâmetros de Pesquisa</div>
	<form class="form_busca" name="frm_relatorio" method="POST" action="relatorio_erros_integracao.php">
		<div class="row-fluid form-group">
			<div class="span4"></div>
			<div class="span2">
				<label>Data Inicial:</label>
				<input type="text" class="form-control" name="inicial_date" id="inicial_date">
			</div>
			<div class="span2">
				<label>Data Final:</label>
				<input type="text" class="form-control" name="final_date" id="final_date">
			</div>
			<div class="span4"></div>
		</div>
		<div class="row-fluid form-group">
			<div class="span12 sender-container">
				<button type="submit" class="btn btn-info btn-search" name="btn_search">Pesquisar</button>
			</div>
		</div>
		<p class="alert-info" style="width:70%;display:block;margin:0 auto">O intervalo entre as datas não deve ser superior a 7 dias</p>
	</form>
</div>
<div class="row-fluid">
	<table class="pages-list table table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th>Data de Criação</th>
				<th>Mensagem de Erro</th>
			</tr>
		</thead>
		<tbody id="pages-wrapper">
			<?php foreach ($resultRoutine as $log) {
				if (utf8_decode($log['error_message']) == 'Interação já cadastrada') {
					continue;
				}

				$created = date('d/m/Y H:i:s', strtotime($log['create_at']));
			?>
				<tr>
					<td><?= $created ?></td>
					<td><?= utf8_decode($log['error_message']) ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<script>
	$(function () {
		$("#inicial_date").datepicker({maxDate:0, dateFormat:"dd/mm/yy"}).mask("99/99/9999");
		$("#final_date").datepicker({maxDate:0, dateFormat:"dd/mm/yy"}).mask("99/99/9999");
	});
</script>

<? include "rodape.php"; ?>
