<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

if ($_POST['pesquisar']) {
	$data_inicial = implode("-", array_reverse(explode("/", $_POST['data_inicial'])));
	$data_final   = implode("-", array_reverse(explode("/", $_POST['data_final'])));

	if (empty($data_inicial) || empty($data_final)) {
		$msg_erro["msg"][]    = "Data informada inválida";
		$msg_erro["campos"][] = "data";
	}

	if (count($msg_erro) == 0) {
		foreach ($filasTelefonia as $fila) {

			$filaNome = urlencode($fila);
			
			$curl_url  = "https://api2.telecontrol.com.br/telefonia/sla-queue/fila/{$filaNome}/dataInicial/{$data_inicial}/dataFinal/{$data_final}";
			$curlData = curl_init();

			curl_setopt_array($curlData, array(
				CURLOPT_URL => $curl_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 90,
				CURLOPT_HTTPHEADER => array(
					"Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			        "Access-Env: PRODUCTION",
			        "Cache-Control: no-cache",
			        "Content-Type: application/json"
				),
			));

			$responseData = curl_exec($curlData);
			$responseData = json_decode($responseData, true);

			if (!empty(curl_error($curl)) OR $responseData['exception']) {

				$msg_erro["msg"][]    = strlen($responseData['exception']) ? $responseData['exception'] : curl_error($curlData);

			} else {

				$resultadoPesquisa[$fila] = $responseData['total'];

			}	

		}
		
		curl_close($curlData);
	}
}

include '../vendor/autoload.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO TELEFONIA POR ATENDENTE";

include "cabecalho_new.php";
$plugins = array("datepicker", "mask", "font-awesome", "shadowbox");
include ("plugin_loader.php");


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
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data</label>
					<div class='controls controls-row'>						
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_inicial']?>">
					</div>
					
				</div>
			</div>

		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>					
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$_POST['data_final']?>" >
				</div>				
			</div>
		</div>
		<div class='span2'></div> 
	</div>
	<br />
	<div class="row-fluid tac">
		<input type="submit" value="Pesquisar" name="pesquisar" class="btn btn-default" />
	</div>
</form>

</div>

<? 
if (isset($_POST['pesquisar']) && count($msg_erro) == 0) { ?>		
			<table class="table table-bordered table-striped table-responsive">		
				<thead>
					<tr class="titulo_tabela">
						<th colspan="100%">Relatório de atendimentos por fila <?= $_POST['data_inicial'] ?> - <?= $_POST['data_final'] ?></th>
					</tr>
					<tr class="titulo_coluna">
						<th>Fila</th>
						<th>Qtde Total</th>
						<th>Atendidas</th>
						<th>Abandonadas</th>
						<th>Tempo médio de Atendimento</th>
						<th>Tempo médio de Espera</th>
						<th>Aderente ao SLA 30"</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$totalLigacoes    = 0;
					$totalAtendidas   = 0;
					$totalAbandonadas = 0;
					$totalTempoAtivo = 0;
					$totalTempoInativo = 0;
					$totalSla = 0;
					$totalSlaPerc = 0;
					$media = count($filasTelefonia);

					foreach ($resultadoPesquisa as $fila => $last) {

						$qtdeTotal        = $last['qtd_ligacoes'];
						if ($last['qtd_ligacoes'] == 0) {
							$media--;
							$last['qtd_abandonadas'] = 0;
						}

						$qtdeAtendidas    = $last['qtd_ligacoes']; //- $last['qtd_abandonadas'];
						$totalAtendidas  += $qtdeAtendidas;

						$qtdeAbandonadas  = $last['qtd_abandonadas'];
						$totalAbandonadas += $qtdeAbandonadas;

						$qtdeTotal = $qtdeAtendidas +  $qtdeAbandonadas;
						$totalLigacoes 	  += $qtdeTotal;

						$sla              = number_format($last['qtd_ligacoes'] * ($last['sla']/100), 2);
						$totalSla += $sla;
						$totalSlaPerc += $last['sla'];

					?>
						<tr>
							<td><?= $fila ?></td>
							<td class="tac"><?= $qtdeTotal ?></td>
							<td class="tac"><?= $qtdeAtendidas ?></td>
							<td class="tac">
								<?php
								if ($qtdeAbandonadas > 0) { ?>
									<a href="#" class="ligacoes_abandonadas" data-fila-descricao="<?= $fila ?>" data-inicio="<?= $data_inicial ?>" data-fim="<?= $data_final ?>">
										<?= $qtdeAbandonadas ?>
									</a>
								<?php
								} else {?>
									0
								<?php
								}
								?>

							</td>

							<td class="tac">
							   <?php
							   $tempo_ativo = $last['tempo_ativo'] != "" ? $last['tempo_ativo'] : '0';
							   $totalTempoAtivo += $tempo_ativo;

							   echo gmdate("i:s", $tempo_ativo);
							   ?>
                                                                
							</td>

							<td class="tac">
							   <?php
							   $tempo_inativo = $last['tempo_inativo'] != "" ? $last['tempo_inativo'] : '0';
							   $totalTempoInativo += $tempo_inativo;

							   echo gmdate("i:s", $tempo_inativo);
							   ?>
							</td>

							<td class="tac"><?= round($sla) ?> (<?= number_format($last['sla'], 2) ?>%)</td>
						</tr>
					<?php
					}
					?>
					<tr>
						<td>Total</td>
						<td class="tac"><?= $totalLigacoes ?></td>
						<td class="tac"><?= $totalAtendidas ?></td>
						<td class="tac"><?= $totalAbandonadas ?></td>
						<td class="tac"><?= gmdate("i:s", (int) $totalTempoAtivo / $media) ?></td>
						<td class="tac"><?= gmdate("i:s", (int) $totalTempoInativo / $media) ?></td>
						<td class="tac"><?= round($totalSla) . ' (' . number_format(($totalSlaPerc / $media), 2) . '%)' ?></td>
					</tr>
				</tbody>
			</table>

			<script>
				$(function() {
					$.datepickerLoad(Array("data_final", "data_inicial"));
					Shadowbox.init();

					
					$(".ligacoes_abandonadas").click(function(){
						

						let fila 		  = $(this).data('fila-descricao');
						let data_inicial  = $(this).data('inicio');
						let data_final    = $(this).data('fim');



						Shadowbox.open({
			                content: "exibe_ligacoes_abandonadas.php?fila="+fila+"&data_inicial="+data_inicial+"&data_final="+data_final,
			                player: "iframe",
			                title:  "Ligações Abandonadas",
			                width:  1200,
			                height: 800
			            });

					});
				});
			</script>

<?php
} else {
	?>
			<script>
				$(function() {
					$.datepickerLoad(Array("data_final", "data_inicial"));
				});
			</script>

	<?php
}



include "rodape.php"; ?>
