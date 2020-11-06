<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST["btn_acao"] == "submit"){
	$data_inicial   			= $_POST['data_inicial'];
	$data_final					= $_POST['data_final'];

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

			if (strtotime($aux_data_inicial."+6 months" ) < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = "Intervalo de pesquisa não pode ser no do que seis meses.";
				$msg_erro["campos"][] = "data";
			}
		}
	}
	$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";
	
	if(count($msg_erro['msg']) == 0){



		$sql = "SELECT
					   tbl_os.os,
					   tbl_os.sua_os,
					       to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data_digitacao,
					       to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura,
					       tbl_hd_chamado.hd_chamado,
					       tbl_hd_chamado.status

				  FROM tbl_os
				  JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
				  JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				  JOIN tbl_os_extra ON  tbl_os.os = tbl_os_extra.os
				  WHERE tbl_hd_chamado.status <> 'Resolvido' 
				  AND tbl_os_extra.extrato isnull
				  AND tbl_os.finalizada notnull
				  AND tbl_os.fabrica = $login_fabrica
				  AND tbl_os.finalizada between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
				  $resSubmit = pg_query($con, $sql);
				  $count = pg_num_rows($resSubmit);

		if(isset($_POST["gerar_excel"])){



			if(pg_num_rows($resSubmit) > 0){
				$data = date("d-m-Y-H:i");
				$fileName = "os_finalizadas_sem_extrato-{$data}.xls";
				$file = fopen("/tmp/{$fileName}" , "w");
				$thead = "
				<table border = '1'>
					<thead>
						<tr>
							<th colspan = '11' bgcolor = '#D9E2EF' color='#333333' style='color: #333333 !important;' >
							OS FINALIZADAS SEM EXTRATO
							</th>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Digitação</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendimento</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status do Atendimento</th>
							</tr>
						</tr>
					</thead>";
				
				fwrite($file, $thead);

				for($i = 0; $i < pg_num_rows($resSubmit); $i++){
					$os 							= pg_fetch_result($resSubmit, $i, 'os');
					$sua_os							= pg_fetch_result($resSubmit, $i, 'sua_os');
					$data_digitacao					= pg_fetch_result($resSubmit, $i, 'data_digitacao');
					$data_abertura					= pg_fetch_result($resSubmit, $i, 'data_abertura');
					$atendimento                	= pg_fetch_result($resSubmit, $i, 'hd_chamado');
					$status_do_atendimento			= pg_fetch_result($resSubmit, $i, 'status');
				
				
		
					
				  $body ="
				 			<tr>
								<td class= 'tac'><a href='os_press.php? os={$os}' target='_blank' >{$sua_os}</a></td>
								<td class= 'tac'>{$data_digitacao}</td>
								<td class= 'tac'>{$data_abertura}</td>
								<td class= 'tac'><a href='callcenter_interativo_new.php? callcenter={$atendimento}'>$atendimento</a></td>
								<td class= 'tac'>{$status_do_atendimento}</td>
							</tr>"; 
							fwrite($file, $body);
				}
			
				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}"; exit;
				}
				
			}

		}	
		
		if(isset($resSubmit)){
			if(pg_num_rows($resSubmit) > 0){
				echo"<br />";

				if(pg_num_rows($resSubmit) > 500){
					$count = 500;

				}
			}
		}
	}
}
$title= "RELATÓRIO DE OS(S) FINALIZADAS SEM EXTRATO";
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
	});
</script>

	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>

<!--form-->
	<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'>Parametros de Pesquisa </div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12 text-center' value= "<?=$data_final?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />
	</form>

<?php

		echo "<div id = 'registro_max'>
			<h6> Em tela serão mostrados no maximo 500 registros, para vizualizar todos os registros baixe o arquivo Excel no final da teal.</h6>
			  </div>";
			
		 

		if(strlen ($msg_erro["msg"]) == 0  AND pg_num_rows($resSubmit) > 0){


			


?>
			
			<table id="resultado_os_finalizadas_sem_extrato" class = 'table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class = 'titulo_coluna'>
						<th>OS</th>
						<th>Data Digitação</th>
						<th>Data Abertura</th>
						<th>Atendimento</th>
						<th>Status do Atendimento</th>
					</tr>
				</thead>
				<tbody>
					<?php
						for($i = 0; $i < $count; $i++){
							$os 							= pg_fetch_result($resSubmit, $i, 'os');
							$sua_os							= pg_fetch_result($resSubmit, $i, 'sua_os');
							$data_digitacao					= pg_fetch_result($resSubmit, $i, 'data_digitacao');
							$data_abertura					= pg_fetch_result($resSubmit, $i, 'data_abertura');
							$atendimento   					= pg_fetch_result($resSubmit, $i, 'hd_chamado');
							$status_do_atendimento			= pg_fetch_result($resSubmit, $i, 'status');
						
						$body .= "<tr>
									<td class= 'tac'><a href='os_press.php? os={$os}' target='_blank' >{$sua_os}</a></td>
									<td class= 'tac'>{$data_digitacao}</td>
									<td class= 'tac'>{$data_abertura}</td>
									<td class= 'tac'><a href='callcenter_interativo_new.php? callcenter={$atendimento}'>$atendimento</a></td>
									<td class= 'tac'>{$status_do_atendimento}</td>
								</tr>";
							}
								echo $body;
					?>	
				</tbody>
			</table>
			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os_finalizadas_sem_extrato" });
				</script>
			<?php
			}
								  				
			?>

			<?php $jsonPOST = excelPostToJson($_POST);?>
			<input type= "hidden" id= "jsonPOST" value= '<?=$jsonPOST?>'/>
			<div id = 'gerar_excel' class= "btn_excel">
			<span><img src='imagens/excel.png'/></span>
			<span class = "txt">Gerar Arquivo Excel</span>
			</div>	  

<?php 
	}
?>
<?php
include 'rodape.php';
?>