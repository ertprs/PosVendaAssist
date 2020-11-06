	<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'cadastros';

include 'autentica_admin.php';
include 'funcoes.php';


include_once '../class/AuditorLog.php';

$usaAuditorLog = true;

$layout_menu = 'cadastro';
$title       = 'Cadastro de Número de Série Bloqueado';

unset($msg_erro);
$msg_erro = array();
$btn_acao = "";

if(isset($_POST["btn_acao"])){
	$btn_acao = $_POST["btn_acao"];
}

if($btn_acao == "gravar"){
	$numero_serie = trim($_POST["numero_serie"]);
	$observacao   = $_POST["observacao"];

	if(empty($numero_serie)){
		$msg_erro["campos"][] = "numero_serie";
		$msg_erro["msg"]      = "Preencha os campos obrigátorios";
		$msg_erro["class"]    = "alert-error";
	}

	if(empty($observacao)){
		$msg_erro["campos"][] = "observacao";
		$msg_erro["msg"]      = "Preencha os campos obrigátorios";
		$msg_erro["class"]    = "alert-error";
	}

	if(empty($msg_erro["msg"])){
		$retorno           = gravaNumeroSerie($numero_serie,$observacao);
		$msg_erro["msg"]   = $retorno["msg"];
		$msg_erro["class"] = $retorno["class"];
	}

}else if($btn_acao == "desbloquear_serie"){
	$serie_controle = $_POST["serie_controle"];
	$motivo_desbloq = $_POST["motivo"];

	if(empty($serie_controle) || empty($motivo_desbloq)){
		echo json_encode(array(
			"sucesso"  => false,
			"mensagem" => "Erro ao liberar o número de série."
		));
		exit;
	}else{

		pg_query($con,"BEGIN");

		$sql = "UPDATE tbl_numero_serie SET bloqueada_garantia = FALSE 
			WHERE fabrica = ".$login_fabrica." 
				AND serie = (
					SELECT serie FROM tbl_serie_controle 
					WHERE serie_controle = ".$serie_controle." 
					AND fabrica = ".$login_fabrica."
				)";
		pg_query($con,$sql);

		if(strlen(pg_last_error()) > 0){
			pg_query($con,"ROLLBACK");
			echo json_encode(array(
				"sucesso"  => false,
				"mensagem" => "Erro ao cancelar o bloqueio do número de série."
			));
			exit;
		}else{

			if ($usaAuditorLog) {
				$selectAuditor = "SELECT serie FROM tbl_numero_serie WHERE fabrica = ".$login_fabrica." AND serie = (SELECT serie FROM tbl_serie_controle WHERE serie_controle = ".$serie_controle." AND fabrica = ".$login_fabrica." )";
				$resAuditor    = pg_query($con, $selectAuditor);
				$numero_serie  = (pg_num_rows($resAuditor) > 0) ? pg_fetch_result($resAuditor, 0, 'serie') : false;
					
				$auditorLog = new AuditorLog;
				$auditorLog->retornaDadosSelect("SELECT '&nbsp;' || '$numero_serie' as numero_serie, '' as motivo, 'sim' as numero_serie_bloqueado");
			}

			$sql = "DELETE FROM tbl_serie_controle WHERE serie_controle = ".$serie_controle." AND fabrica = ".$login_fabrica;
			pg_query($con,$sql);

			if(strlen(pg_last_error()) > 0){
				pg_query($con,"ROLLBACK");
				echo json_encode(array(
					"sucesso"  => false,
					"mensagem" => "Erro ao excluir o registro de bloqueio do número de série."
				));
				exit;

			}else{
				if ($usaAuditorLog) {
					$auditorLog->retornaDadosSelect("SELECT '$numero_serie' || '&nbsp;' as numero_serie, '$motivo_desbloq' as motivo, 'não' as numero_serie_bloqueado")->enviarLog('update', 'tbl_numero_serie', $login_fabrica);;
				}

				pg_query($con,"COMMIT");
				echo json_encode(array(
					"sucesso" => true
				));
				exit;
			}
		}
	}

} else if ($btn_acao == "upload_arquivo") {
	$file     = $_FILES["arquivo_txt"];
	$exp_file = explode(".", $file["name"]);
	$msg_erro = '';

	if (empty($file["name"])) {
		$msg_erro .= 'Favor, anexe um arquivo.';
	} else if (count($exp_file) > 2 || $exp_file[1] != 'txt') {
		$msg_erro .= 'Favor, apenas arquivos .txt';
	}

	$arquivo = fopen($file['tmp_name'], 'r+');

    if ($arquivo && strlen($msg_erro) == 0) {
        while(!feof($arquivo)){
            $linha = fgets($arquivo,4096);
            
            if (strlen(trim($linha)) > 0) {
                $registro[] = explode(";", $linha);
            }
        }

        fclose($f);
    }

    if (count($registro) > 0 && strlen($msg_erro) == 0) {
    	foreach ($registro as $key => $rows) {
    		$numero_serie      = $rows[0];
    		$observacao        = $rows[1];
    		$retorno           = gravaNumeroSerie($numero_serie,$observacao);
			$msg_erro["msg"]   = $retorno["msg"];
			$msg_erro["class"] = $retorno["class"];
    	}
    }
}


function gravaNumeroSerie($numero_serie, $observacao=null) {
	global $con, $login_fabrica, $usaAuditorLog;

	$sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = $login_fabrica
			AND serie = '$numero_serie' AND bloqueada_garantia IS FALSE";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		pg_query($con,"BEGIN");

		$sql = "UPDATE tbl_numero_serie SET bloqueada_garantia = TRUE 
			WHERE fabrica = $login_fabrica AND serie = '$numero_serie'";
		pg_query($con,$sql);

		if(strlen(pg_last_error()) > 0){
			pg_query($con,"ROLLBACK");
			$msg_erro["msg"]   = "Erro ao bloquear número de serie ".$numero_serie;
			$msg_erro["class"] = "alert-error";
		}else{

			$sql = "SELECT produto FROM tbl_numero_serie 
				WHERE fabrica = $login_fabrica AND serie = '$numero_serie'";
			$res = pg_query($con,$sql);

			if(pg_num_fields($res) > 0){
				$produto = pg_fetch_result($res, 0, "produto");

				if ($usaAuditorLog) {
					$auditorLog = new AuditorLog();
					$auditorLog->retornaDadosSelect("SELECT '&nbsp;' || '$numero_serie' as numero_serie, '' as motivo, 'não' as numero_serie_bloqueado");
				}

				$sql = "INSERT INTO tbl_serie_controle (fabrica, produto, serie, quantidade_produzida, motivo) 
					VALUES ($login_fabrica, $produto, '$numero_serie', 0, '$observacao')";

				pg_query($con,$sql);

				if(strlen(pg_last_error()) > 0){
					pg_query($con,"ROLLBACK");
					$msg_erro["msg"]   = "Erro ao registrar o controle de número de serie ".$numero_serie;
					$msg_erro["class"] = "alert-error";
				}else{
					pg_query($con,"COMMIT");
					$msg_erro["msg"]   = "Gravado com sucesso!";
					$msg_erro["class"] = "alert-success";

					if ($usaAuditorLog) {
						$auditorLog->retornaDadosSelect("SELECT '$numero_serie' || '&nbsp;' as numero_serie, '$observacao' as motivo, 'sim' as numero_serie_bloqueado")->enviarLog('update', 'tbl_numero_serie', $login_fabrica);
					}

					$numero_serie = "";
					$observacao   = "";
				}
			}else{
				pg_query($con,"ROLLBACK");
				$msg_erro["msg"]   = "Erro ao buscar o produto referente a número de serie ".$numero_serie;
				$msg_erro["class"] = "alert-error";
			}

		}
	}else{
		$msg_erro["msg"]   = "Número de serie ".$numero_serie." já está bloqueado ou não consta no sistema. <br />";
		$msg_erro["class"] = "alert-error";
	}

	return $msg_erro;
}

include 'cabecalho_new.php'; 

$plugins = array(
   "dataTable"
);

include "plugin_loader.php";
?>


<style type="text/css">
	.table > tbody > tr > td{
		text-align: center;
	}
</style>
<script type="text/javascript">
	$(function() {
		$("#btn_gravar").on("click",function(){
			$("#btn_acao").val("gravar");
			$("form[name=frm_cadastro_numero_serie_bloqueado]").submit();
		});

		$("#tabela_numero_serie").DataTable();
	});
</script>
<?php
if(!empty($msg_erro["msg"])){
	?>
	<div class="alert <?=$msg_erro['class']?>"><h4><?= $msg_erro["msg"] ?></h4>
	</div>
	<?php
}
?>
<div id="mensagem_erro"></div>
<form name="frm_cadastro_numero_serie_bloqueado" method="POST" class="form-search form-inline tc_formulario" action="<?=$PHP_SELF?>" <?= (in_array($login_fabrica, [158])) ? "style='margin: 0 0 0 0px !important;'" : '' ?>>
    <legend class='titulo_tabela'>Cadastro</legend>
    <div class='row-fluid'>
    	<div class='span2'></div>
            <div class='span3'>
                <div class='control-group <?=(in_array("numero_serie", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='numero_serie'>Número de Série</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="numero_serie" id="numero_serie" size="12" class='span12' maxlength="50" value="<?=$numero_serie?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span7'>
                <div class='control-group <?=(in_array("observacao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='observacao'>Observação</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="observacao" id="observacao" size="12" class='span12' value="<?=$observacao?>" />
                        </div>
                    </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
    <input type='hidden' id="btn_acao" name='btn_acao' value=''/>
    <p>
        <button class='btn' id="btn_gravar" type="button" >Gravar</button>
    </p>
    <br/>
</form>
<?php if ($login_fabrica == 158) {?>
<form name="frm_cadastro_numero_serie_bloqueado_massa" method="POST" class="form-search form-inline tc_formulario" action="<?=$PHP_SELF?>" enctype="multipart/form-data">
    <legend class='titulo_tabela'>Parâmetros para Upload</legend>
    <div class='row-fluid'>
    	<div class='span1'></div>
            <div class='span10'>
                <div class="alert alert-block alert-warning" style="text-align: left !important;">
                	O Arquivo selecionado deve estar no seguinte formato:
                	<ul>
                		<li><b>.txt</b> e sem cabeçalho</li>
                		<li>
                			vir com os campos:
                			<ul>
                				<li>número de série</li>
                				<li>obsevação</li>
                			</ul>
                		</li>
                		<li>os valores devem vir separados por ponto-e-virgula <b>(;)</b>
                	</ul>
                </div>
            </div>
        <div class='span1'></div>
    </div>
    <div class='row-fluid'>
    	<div class='span1'></div>
            <div class='span10'>
                <div class='control-group <?=(in_array("arquivo_txt", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='arquivo_txt'>Arquivo <b>.txt</b></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="file" name="arquivo_txt" id="arquivo_txt" size="12" class='span12' maxlength="50" />
                        </div>
                    </div>
                </div>
            </div>
        <div class='span1'></div>
    </div>
    <input type='hidden' id="btn_acao" name='btn_acao' value='upload_arquivo' />
    <p>
        <button class='btn btn-primary' id="btn_upload" type="submit" >Upload do Arquivo</button>
    </p>
    <br/>
</form>
<?php
}
$sql = "SELECT tbl_serie_controle.serie_controle, 
		tbl_serie_controle.serie, 
		tbl_serie_controle.motivo 
	FROM tbl_serie_controle 
	WHERE fabrica = $login_fabrica;";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){

	?>
	<table id="tabela_numero_serie" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class="titulo_tabela">
				<th colspan="3">Lista de Número de Série Bloqueado</th>
			</tr>
			<tr class='titulo_coluna'>
				<th>Número de Série</th>
				<th>Observação</th>
				<th style="width:20%;">Ação</th>
			</tr>
		</thead>
		<tbody>
	<?php

	while($objeto_serie = pg_fetch_object($res)){
		?>
		<tr id="linha_<?=$objeto_serie->serie_controle?>">
			<td><?=$objeto_serie->serie?></td>
			<td><?=$objeto_serie->motivo?></td>
			<td name="coluna_acao">
				<button name="btn_desbloquear_<?=$objeto_serie->serie_controle?>" class="btn btn-danger">Desbloquear</button>
			</td>
		</tr>
		<?php
	}
	?>
		</tbody>
	</table>


	<div class='tac'>
	    <a rel='shadowbox' target="_blank" href='relatorio_log_alteracao_new.php?parametro=tbl_numero_serie&titulo=CADASTRO DE NÚMERO DE SÉRIE BLOQUEADO'>Visualizar Log Auditor</a>
	</div>

	<script type="text/javascript">
		$(function() {
			$(document).on("click","button[name^=btn_desbloquear_]",function(){
				var motivo         = prompt('Informe o motivo de desbloqueio:');
				var serie_controle = this.name.replace(/\D/g, "");
				$("button[name=btn_desbloquear_"+serie_controle+"]").button("loading");
				$("#mensagem_erro").removeClass('alert alert-error');
                $("#mensagem_erro").html('');

                if (motivo !== null || motivo !== undefined) {
                	$.ajax({
	                    url:"cadastro_numero_serie_bloqueado.php",
	                    type:"POST",
	                    dataType:"json",
	                    data:{
							serie_controle: serie_controle,
	                    	btn_acao: "desbloquear_serie",
	                    	motivo: motivo
	                    }
	                })
	                .done(function(data){
	                    $("button[name=btn_desbloquear_"+serie_controle+"]").button("reset");

	                    if(data.sucesso == true){
	                        $("#tabela_numero_serie > tbody > tr[id=linha_"+serie_controle+"] > td[name=coluna_acao]").html('<span class="label label-success">Desbloqueado</span>');
	                    }else{
	                        $("#mensagem_erro").addClass('alert alert-error');
	                        $("#mensagem_erro").append('<h4>'+data.mensagem+'</h4>');
	                    }
	                });
                }

				
			});
		});
	</script>
	<?php
}
include "rodape.php";
?>
