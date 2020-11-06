<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "LOG DE ERRO DE INTEGRAÇÃO";


if (isset($_POST['confirmar_log'])) {
	$logs = $_POST['logs'];

	$erro = false;

	pg_query($con, "BEGIN");

	foreach ($logs as $key => $log) {
		if (!empty($log)) {
			$sql = "UPDATE tbl_log_integracao 
					SET 
						confirmar_leitura = TRUE, 
						admin = {$login_admin}
					WHERE log_integracao = {$log}
					AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$erro = true;
				break;
			}
		}
	}

	if ($erro == false) {
		echo "success";
		pg_query($con, "COMMIT");
	} else {
		echo "erro";
		pg_query($con, "ROLLBACK");
	}

	exit;
}

if ($_POST["btn_acao"] == "submit") {

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$opcao        = $_POST['opcao'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}

			if (strtotime($aux_data_inicial.'+30 days') < strtotime($aux_data_final) ) {
				$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 30 dias";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	switch ($opcao) {
		case '2':
			$sql_where_opcao = "  AND tbl_log_integracao.confirmar_leitura IS TRUE ";
			break;

		case '3':
			$sql_where_opcao = "  AND tbl_log_integracao.confirmar_leitura IS FALSE ";
			break;
	}
}

include "cabecalho_new.php";

$plugins = array(
	"datepicker",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>

	<script language="javascript">
		$(function() {
			$.datepickerLoad(["data_inicial", "data_final"]);

			$("#confirmar_leitura_todos").click(function () {
				if (confirm("Deseja realmente confirmar a leitura de todos os logs ?")) {
					var logs = [];

					$("td[name=status_confirmado]").each(function () {
						var status = $(this).find("input[name=status]").val();
						var log    = $(this).find("input[name=log_id]").val();

						if (status == "f") {
							logs.push(log);
						}
					});

					if (logs.length > 0) {
						$.ajax({
							url: "<?=$_SERVER['PHP_SELF']?>",
							type: "POST",
							data: { confirmar_log: true, logs: logs },
							complete: function (data) {
								data = data.responseText;

								if (data == "success") {
									$("td[name=status_confirmado]").each(function () {
										$(this).find("span[name=status_text]").text("Leitura confirmada");
									});
								} else {
									alert("Erro ao confirmar leitura dos logs");
								}
							}
						});
					}
				}
			});

			$(document).on("click", "button[name=link_confirmar_leitura]", function () {
				var logs = [];

				var td     = $(this).parents("td");
				var status = $(td).find("input[name=status]").val();
				var log    = $(td).find("input[name=log_id]").val();

				if (status == "f") {
					logs.push(log);
				}

				if (logs.length > 0) {
					$.ajax({
						url: "<?=$_SERVER['PHP_SELF']?>",
						type: "POST",
						data: { confirmar_log: true, logs: logs },
						complete: function (data) {
							data = data.responseText;

							if (data == "success") {
								$(td).find("span[name=status_text]").text("Leitura confirmada");
							} else {
								alert("Erro ao confirmar leitura do log");
							}
						}
					});
				}
			});
		});
	</script>

	<?php
	if (count($msg_erro["msg"]) > 0) {
	?>
	    <div class="alert alert-error">
			<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	    </div>
	<?php
	}
	?>

	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>

	<form name='frm-relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

		<div class='titulo_tabela '>Filtro de Interação</div> 

		<br />

		<div class='row-fluid'>

			<div class='span2'></div>

			<div class='span2'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_inicial']?>">
						</div>
					</div>
				</div>
			</div>

			<div class='span2'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_final']?>">
						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group'>
				<label class='control-label'>&nbsp;</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<label class="radio">
								<input type="radio" name="opcao" value="1" <?=($_POST["opcao"] == "1" || !strlen($_POST["opacao"])) ? "CHECKED" : ""?> />
					        	Selecionar Todas	
							</label>
							<label class="radio">
								<input type="radio" name="opcao" value="2" <?=($_POST["opcao"] == "2") ? "CHECKED" : ""?> />
					        	Já lidas	
							</label>
							<label class="radio">
								<input type="radio" name="opcao" value="3" <?=($_POST["opcao"] == "3") ? "CHECKED" : ""?> />
					       		Falta ler
							</label>
				        </div>
			        </div>
		        </div>
			</div>

			<div class='span2'></div> 

		</div>

		<br />

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
			
	</form>

<?php

if (isset($_POST['btn_acao']) && count($msg_erro["msg"]) == 0) {
	$sql = "SELECT 
				tbl_log_integracao.log_integracao ,
				TO_CHAR(tbl_log_integracao.data,'DD/MM/YYYY HH:MM:SS') AS data,
				tbl_log_integracao.tipo,
				tbl_log_integracao.confirmar_leitura,
				tbl_log_integracao.descricao,
				tbl_admin.login
			FROM tbl_log_integracao
			LEFT JOIN tbl_admin ON tbl_log_integracao.admin = tbl_admin.admin 
			AND tbl_log_integracao.fabrica = tbl_admin.fabrica
			WHERE tbl_log_integracao.fabrica = {$login_fabrica} 
			AND tbl_log_integracao.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
			{$sql_where_opcao}
			ORDER BY tbl_log_integracao.data DESC";
	$res = pg_query($con, $sql);

	if (pg_numrows($res) > 0){
		$count = pg_num_rows($res);
		?>
		<div class="row tac">
			<button type="button" class="btn" id="confirmar_leitura_todos">Confirmar leitura de todos os logs da página</button>
		</div>

		<br />

		<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>Data</th>
					<th>Tipo</th>
					<th>Descrição</th>
					<th>Status</th>
					<th>Admin</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $count; $i++){

					$log_integracao         = trim(pg_result($res,$i, 'log_integracao'));
					$data                   = trim(pg_result($res,$i, 'data'));
					$tipo                   = trim(pg_result($res,$i, 'tipo'));
					$descricao              = trim(pg_result($res,$i, 'descricao'));
					$confirmar_leitura      = trim(pg_result($res,$i, 'confirmar_leitura'));
					$login                  = trim(pg_result($res,$i, 'login'));

					$status = ($confirmar_leitura == "f") ? "<button type='button' class='btn btn-link btn-small' name='link_confirmar_leitura' >Já li, e Confirmo</button>" : "Leitura confirmada";

					echo "<tr>
						<td class='tac'>
							{$data}
						</td>
						<td class='tac'>
							{$tipo}
						</td>
						<td class='tac' style='text-align: justify;'>
							{$descricao}
						</td>
						<td class='tac' name='status_confirmado'>
							<input type='hidden' name='log_id' value='{$log_integracao}' />
							<input type='hidden' name='status' value='{$confirmar_leitura}' />
							<span name='status_text'>{$status}</span>
						</td>
						<td class='tac'>
							{$login}
						</td>
					</tr>";
				}
				?>
			</tbody>
		</table>

		<?php

		if ($count > 50) {
			?>
				<script type="text/javascript">
					$.dataTableLoad({ table: "#resultado_os_atendimento" });
				</script>
			<?php
		}

	} else {
		echo '<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>';
	}
}

echo "<br />";

include 'rodape.php';

?>