<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

if (array_key_exists('grava_previsao', $_POST) or array_key_exists('grava_previsao_array', $_POST)) {

	$array_extrato = $_POST['grava_previsao'] ?
		array($_POST['extrato']) :
		$_POST['array_extrato'];
	$data_previsao = is_date(getPost('data_previsao'));

	if (!$data_previsao)
		die (json_encode(
			array(
				'retorno' => 'Data de Previsão inválida (' . getPost('data_previsao') . ')'
			)
		)
	);

	$ret = array('error' => 0, 'retorno' => array());
	$erros = 0;

	foreach ($array_extrato as $key => $extrato) {
		$msg = true;


		if ($login_fabrica != 151) {
			$sql = "UPDATE tbl_extrato
				       SET previsao_pagamento = '$data_previsao', nf_recebida = TRUE
				     WHERE extrato = {$extrato}
				       AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				$ret['errorMsg'][] = pg_last_error();
				$error++;
				continue;
			}	
		} else {
			$codigo = "";
			$extratos = [];
			$sql_agrupado = "SELECT codigo FROM tbl_extrato_agrupado WHERE extrato = ($extrato) ";
			$res_agrupado = pg_query($con, $sql_agrupado);
			if (pg_num_rows($res_agrupado) > 0) {
				$codigo = pg_fetch_result($res_agrupado, 0, 'codigo');
				$sql_extrato = "SELECT extrato FROM tbl_extrato_agrupado WHERE codigo = '$codigo'";
				$res_extrato = pg_query($con, $sql_extrato);
				foreach (pg_fetch_all($res_extrato) as $ky => $vl) {
					$sql = "UPDATE tbl_extrato
						       SET previsao_pagamento = '$data_previsao', nf_recebida = TRUE
						     WHERE fabrica = {$login_fabrica}
						     AND extrato = ".$vl["extrato"];						     
					$res = pg_query($con, $sql);
					if(strlen(pg_last_error()) > 0){
						$ret['errorMsg'][] = pg_last_error();
						$error++;
						continue;
					} else {
						$extratos[] = $vl["extrato"];
					}
				}
			} else {
				$sql = "UPDATE tbl_extrato
					       SET previsao_pagamento = '$data_previsao', nf_recebida = TRUE
					     WHERE extrato = {$extrato}
					       AND fabrica = {$login_fabrica}";
				$res = pg_query($con, $sql);

				if(strlen(pg_last_error()) > 0){
					$ret['errorMsg'][] = pg_last_error();
					$error++;
					continue;
				}				
			}

			if (isset($notaFiscalServicoClass) and is_object($notaFiscalServicoClass)) {
				unset($notaFiscalServicoClass);
			}

			$WsErroDesc = null;

			include_once "../os_cadastro_unico/fabricas/{$login_fabrica}/classes/NotaFiscalServico.php";

			$notaFiscalServicoClass = new NotaFiscalServico($login_fabrica);

			// $msg = $notaFiscalServicoClass->run();

			/* Envia os dados para a Mondial */
			if (!empty($codigo)) {
				$retWS = json_decode($notaFiscalServicoClass->gravaDespesaWs($extrato, null, $codigo), true);
			} else {
				$retWS = json_decode($notaFiscalServicoClass->gravaDespesaWs($extrato), true);
			}

			if($retWS["SdRetSPD"]["SdErro"]["ErroCod"] != 0 || isset($retWS["error"])){

				$WsErroDesc = utf8_decode($retWS["SdRetSPD"]["SdErro"]["ErroDesc"]);
				$labelStyle = 'label-info';

				if(preg_match("/Série já encontra-se cadastrado em outro/i", $WsErroDesc) == false){

					if (count($extratos) > 0) {
						$ex = implode(",", $extratos);
					} else {
						$ex = $extrato;
					}

					$sql = "UPDATE tbl_extrato
							   SET previsao_pagamento = NULL, nf_recebida = FALSE
							 WHERE extrato IN  {$ex}";
					$res = pg_query($con, $sql);
					$erros++;
					$labelStyle = 'label-important';
				}
				$ret['errorMsg'][] = utf8_encode("
					<div>
						<span class='label $labelStyle'> Extrato: $ex</span>
						<span>$WsErroDesc</span>
					</div>
					");

				$ret['retorno'][] = $retWS;
				continue;
			}

			/* Mensagem ao Posto - Comunicado e Email */
			/* Parâmetros:
				- Número de Extrato
				- Data de Previsão
				- Envia Comunicado ao Posto
				- Envia Email ao Posto
			*/
			if (!empty($codigo)) {
				$notaFiscalServicoClass->comunicadoPosto($extrato, $data_previsao, true, true, $extratos);
			} else {
				$notaFiscalServicoClass->comunicadoPosto($extrato, $data_previsao, true, true);
			}
		}

	}

	if ($login_fabrica == 151) {
		if (count($ret['errorMsg']) == 0) {
			$ret['retorno'][] = 'success';
		} else {
			$ret['retorno'][] = 'error';
		}
	}

	$ret['error'] = $erros;
	/* var_export($ret); */
	exit (json_encode($ret));
}

$extrato = $_GET["extrato"];
$posto   = $_GET["posto"];

if(strlen($extrato) > 0){

	$sql = "SELECT data_nf FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
	$res = pg_query($con, $sql);

	$data_nf = pg_fetch_result($res, 0, "data_nf");

	if(strlen($data_nf)){
		list($ano, $mes, $dia) = explode("-", $data_nf);
		$data_nf_desc = $dia."/".$mes."/".$ano;
	}else{
		$data_nf_desc = "Não Informado";
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

    	<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" />
    	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>

		<script src='plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
		<script src='plugins/jquery.mask.js'></script>

		<style>
			.ui-state-default{
				padding-top: 3px !important;
				padding-bottom: 3px !important;
			}
		</style>

		<script>
			$(function () {
				$("#data_previsao").mask("99/99/9999").datepicker();

				$(".cadastrar_pagamento").click(function(){

					var fabrica       = "<?=$login_fabrica?>";
					var extrato       = "<?=$extrato?>";
					var data_nf       = "<?=$data_nf?>";
					var data_previsao = $("#data_previsao").val();

					if(typeof extrato != "undefined" && extrato.length == 0){
						alert("Por favor insira o Extrato");
						return;
					}

					if(typeof data_nf != "undefined" && data_nf.length == 0){
						alert("Por favor insira a Data de Emissão da NF");
						return;
					}

					if(typeof data_previsao != "undefined" && data_previsao.length == 0){
						alert("Por favor insira a Data de Previsão");
						$("#data_previsao").focus();
						return;
					}

					$.ajax({
						url : "<?php echo $_SERVER['PHP_SELF'] ?>",
						type : "POST",
						data : {
							grava_previsao: true,
							data_previsao:  data_previsao,
							extrato:        extrato
						},
						beforeSend: function(){
							$('.loading').html("<em>por favor aguarde...</em>");
						}
					}).always(function(retornoAjax){
						$('.response').removeClass('alert alert-danger alert-danger');

						$('.loading').html("");

						retObj = JSON.parse(retornoAjax);

						if (fabrica  == 101) {
							if(typeof retObj.retorno.error != "undefined"){
								$('.response').addClass('alert alert-danger');
								$('.response').html(data.retorno.error.message);
							} else {
								$(window.parent.document).find("#td_extrato_"+extrato).html("<span class='label label-success' >Previsão cadastrada com sucesso</span>");
								window.parent.Shadowbox.close();
							}
						} else {

							data = retObj.retorno[0];

							/* console.log(data); */

							if (data == 'success') {
								alert("Previsão cadastrada com sucesso");
								parent.location.reload();

							} else if (data == 'error') {
								alert("Erro ao cadastradar previsão");
								parent.location.reload();
								
							} else if(typeof data.retorno.SdRetSPD != "undefined"){

								if(data.retorno.SdRetSPD.SdErro.ErroCod == 0){

									$('.response').addClass('alert alert-success');
									$('.response').html("Previsão cadastrada com Sucesso");

								}else{
									$('.response').addClass('alert alert-danger');
									$('.response').html((data.retorno.SdRetSPD.SdErro.ErroDesc == "") ? "Ocorreu um erro, Por favor verifique as informações do Extrato: <br /> Emissão da NF, NF de serviço e Data de Previsão de Pagamento" : data.retorno.SdRetSPD.SdErro.ErroDesc);
								}

							}else if(typeof retObj.retorno.error != "undefined"){

								$('.response').addClass('alert alert-danger');
								$('.response').html(data.retorno.error.message);

							}else{

								$(window.parent.document).find("#td_extrato_"+extrato).html("<span class='label label-success' >Previsão cadastrada com sucesso</span>");
								window.parent.Shadowbox.close();

							}
						}

					});

				});

			});
		</script>
	</head>

	<body>

		<div id="container_lupa" style="overflow-y: auto; padding: 20px;">
			<h4 class="tac">Informe a Previsão de Pagamento</h4>

			<strong>Extrato:</strong> <?=$extrato?> &nbsp; &nbsp; <strong>Emissão da NF:</strong> <?=$data_nf_desc?> <br />
			<strong>Posto:</strong> <?=$posto?> <br />

			<br />

			<div class="row-fluid">
				<div class="span6">
					<label>Data Previsão Pagamento</label>
					<input type="text" name="data_previsao" id="data_previsao" class="span12" />
				</div>
				<div class="span6">
					<label>&nbsp;</label>
					<button type="button" class="btn btn-success span12 cadastrar_pagamento">Cadastrar</button>
				</div>
			</div>

			<div class="loading"></div>

			<br />

			<div class="response"></div>

		</div>

	</body>
</html>
