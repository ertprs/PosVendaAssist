<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';

if ($_POST['ajax'] && $_POST['classificacao']) {

    if(in_array($login_fabrica, array(169,170))){
        $result = array();
        $xclassificacao = $_POST["classificacao"];

        $sql = "SELECT  tbl_hd_motivo_ligacao.natureza,
                        tbl_hd_motivo_ligacao.hd_motivo_ligacao,
                        tbl_hd_motivo_ligacao.descricao,
                        tbl_hd_motivo_ligacao.categoria,
                        tbl_hd_motivo_ligacao.prazo_dias,
                        tbl_hd_motivo_ligacao.abre_os
                FROM tbl_hd_motivo_ligacao
                WHERE tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
                AND tbl_hd_motivo_ligacao.hd_classificacao = {$xclassificacao}
                AND tbl_hd_motivo_ligacao.ativo IS TRUE
                ORDER BY tbl_hd_motivo_ligacao.descricao ASC";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            for ($i=0; $i < pg_num_rows($res); $i++) {
                $result[] = array("os_cortesia" => utf8_encode(pg_fetch_result($res, $i, categoria)),"abre_os" => utf8_encode(pg_fetch_result($res, $i, abre_os)), "prazo_dias" => utf8_encode(pg_fetch_result($res, $i, prazo_dias)), "motivo_ligacao" => utf8_encode(pg_result($res,$i,hd_motivo_ligacao)), "descricao" => utf8_encode(pg_result($res,$i,descricao)));
            }
            exit(json_encode(array("ok" => $result)));
        }else{
            exit(json_encode(array("no" => 'false')));
        }
    }
}

if($_POST["btn_acao"] == "submit"){

	$data_inicial  = $_POST['data_inicial'];
	$data_final    = $_POST['data_final'];
	$admin         = $_POST['admin'];
	if (($interno_telecontrol OR $login_fabrica == 183) && count($_POST['admin_mult']) > 0) {
		$admin_mult  = implode(",", $_POST['admin_mult']);
		$admin_array = $_POST['admin_mult'];
	}
	$classificacao = $_POST["classificacao"];
	$xjornada 	   = $_POST['jornada'];

	$tipo_atendimento = $_POST['tipo_atendimento'];
	$providencia      = $_POST['providencia'];
	$status 		  = $_POST['status'];

	$providencia3 	= $_POST['providencia_nivel_3'];
	$motivo_contato = $_POST['motivo_contato'];

	if (in_array($login_fabrica, [169, 170])) {
    	if (!empty($providencia3)) {
    		$condProv3 = "AND tbl_hd_chamado_extra.hd_providencia = {$providencia3}";
    	}

    	if (!empty($motivo_contato)) {
    		$condMotivoContato = "AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato}";
    	}
    }

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
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
				$msg_erro["msg"][]    = traduz("Intervalo de pesquisa não pode ser maior do que seis meses.");
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if(strlen($admin) > 0){
		$cond = " AND tbl_hd_chamado.atendente = $admin ";
	}

	$origem_campo = "";
	if (!empty($admin_mult)) {
		$cond = " AND tbl_hd_chamado.atendente IN ($admin_mult) ";
		$origem_campo = ", tbl_hd_chamado_extra.origem";
	}

	if (in_array($login_fabrica, array(169,170))) {
		$cond .= " AND tbl_admin.ativo is true AND (tbl_admin.atendente_callcenter is true OR tbl_admin.callcenter_supervisor is true)";

		if ($xjornada == "true"){
			$cond_jornada = "
				AND (
					(tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.estado IS NULL AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto IS NULL)
					OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto IS NULL)
				)
			";
		}
		$join_jornada = "
			LEFT JOIN tbl_hd_jornada ON tbl_hd_jornada.fabrica = {$login_fabrica}
			LEFT JOIN tbl_os ON (tbl_os.os = tbl_hd_chamado_extra.os OR tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado) AND tbl_os.fabrica = {$login_fabrica}
			LEFT JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
		";

		$campo_jornada = "
			, CASE WHEN tbl_os.os IS NOT NULL AND (
				(tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.estado IS NULL AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto IS NULL)
				OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto IS NULL)
			) THEN
				TRUE
			ELSE
				FALSE
			END AS jornada,
			tbl_status_checkpoint.descricao AS status_os,
			extract(day from current_timestamp - tbl_os.data_digitacao) AS status_jornada
		";

		$distinct_on = "DISTINCT ON (tbl_hd_chamado.hd_chamado)";
		$order_by = "ORDER BY tbl_hd_chamado.hd_chamado DESC, jornada DESC";

		if (strlen(trim($providencia)) > 0){
			$cond .= " AND tbl_hd_chamado_extra.hd_motivo_ligacao = {$providencia} ";
		}
		if (strlen(trim($tipo_atendimento)) > 0){
			$cond .= " AND tbl_os.tipo_atendimento = {$tipo_atendimento} ";
		}

		if (strlen(trim($status)) > 0){
			$cond .= " AND tbl_os.status_checkpoint = {$status} ";
		}
	}

	if (!empty($classificacao)) {
		$cond .= " AND tbl_hd_chamado.hd_classificacao = $classificacao";
	}

	if(count($msg_erro['msg']) == 0){

		if($login_fabrica == 30){
			$cond .= " AND tbl_hd_chamado.status NOT IN('Cancelado','Resolvido') ";
		}

		$sql = "SELECT $distinct_on tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_admin.login AS atendente,
						tbl_admin.nome_completo AS atendente_nome,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
						tbl_hd_chamado_extra.os,
						tbl_hd_chamado_extra.nome,
						tbl_cidade.nome AS cidade,
						tbl_hd_providencia.descricao AS descricao_providencia,
						tbl_motivo_contato.descricao AS descricao_motivo_contato,
						tbl_cidade.estado
						{$origem_campo}
						{$campo_jornada}
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
					JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = $login_fabrica
					{$join_jornada}
					LEFT JOIN tbl_hd_providencia ON tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia
					AND tbl_hd_providencia.fabrica = {$login_fabrica}
					LEFT JOIN tbl_motivo_contato ON tbl_hd_chamado_extra.motivo_contato = tbl_motivo_contato.motivo_contato
					AND tbl_motivo_contato.fabrica = {$login_fabrica}
					WHERE tbl_hd_chamado.fabrica = $login_fabrica
					AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
					$cond
					$cond_jornada
					$condMotivoContato
					$condProv3
					$order_by
					";
			
		$resSubmit = pg_query($con, $sql);
		$count = pg_num_rows($resSubmit);
	}
}
$layout_menu = "callcenter";
$title= traduz("MANUTENÇÃO ATENDIMENTOS EM LOTE");
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable",
	"ajaxform",
	"multiselect",
	"select2"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		<?php if (in_array($login_fabrica, array(169,170))) { ?>
		$("#admin, #atendente").select2();
		<?php } ?>

		$("#admin_mult").multiselect();

		$("#todos").click(function(){

			$(".check").each(function(){

				if($(this).is(':checked')){
					$(this).prop({"checked":false});
				}else{
					$(this).prop({"checked":true});
				}

			});
		});

		
		$("#gravar").click(function(){
			var hd_chamado = "";
			var array_chamado = new Array();
			var json = {};
			var transferir  = $("#atendente").val();
			var providencia = $("#providencia").val();
			var status 		= $("#status").val();

			/*HD - 6196102*/
			var total_chamados        = $(dataTableGlobal.fnGetNodes()).length;
			var verificar_selecionado = 0;
			var aux_chamado           = "";

			for (var wx = 0; wx < total_chamados; wx++) {
				verificar_selecionado = $(dataTableGlobal.fnGetNodes()[wx]).find('td').first().find('input:checked').length;
				
				if (verificar_selecionado == 1) {
					aux_chamado = $(dataTableGlobal.fnGetNodes()[wx]).find('td').first().find('input:checked').val();
					array_chamado.push(aux_chamado);
				}
			}

			json = array_chamado;
	  		json = JSON.stringify(json);

	  		$.ajax({
			  	url: "ajax_hd_chamado_lote.php",
			  	type: "POST",
			  	data: {
			  			acao		:"lote",
			  			hd_chamado  :json,
			  			transferir  :transferir,
			  			providencia :providencia,
			  			status      :status
			  		},
			  	beforeSend: function(){
			  		$("#gravar").text("...processando...");
                    $("#gravar").attr("disabled",true);
			  		
			  		//$("#processando_gravar").append('<br/> <em>Processando...</em>');
			  	},
			  	complete: function(retorno){
		  			var resposta = JSON.parse(retorno.responseText);
		  			var statuss = resposta.statuss;
		  			var msg = resposta.mensagem;

                    $("#gravar").attr("disabled",false);
		  			$("#gravar").text('Gravar');

		  			if(statuss == 'error'){
		  				$('#mensagem').html("<div class='alert alert-error'><h4>"+msg+"</h4></div>");
		  			}

		  			if(statuss == 'ok'){
		  				$("input[class='check']:checked").each(function(){

					    	$(this).parents("tr").remove();

						});

						$('#mensagem').html("<div class='alert alert-success'><h4>"+msg+"</h4></div>");
		  			}
			  	}
		  	});
		});

		$("form[name=form_anexo]").ajaxForm({
            beforeSend: function(){
                $("#upload").prop("disabled",true);
                $("#processando").html('<em>Processando...</em>');
            },
	        complete: function(retorno) {
				var resposta = JSON.parse(retorno.responseText);
	  			var statuss = resposta.statuss;
	  			var msg = resposta.mensagem;

	  			if(statuss == 'error'){
	  				$('#msg_upload').html("<div class='alert alert-error'><h4>"+msg+"</h4></div>");
	  			}

	  			if(statuss == 'ok'){

					$('#msg_upload').html("<div class='alert alert-success'><h4>"+msg+"</h4></div>");
                    $("#upload").prop("disabled",false);
                    $("#processando em").detach();
                    $("#downloadResp a").attr("href",resposta.caminho).show();
	  			}
	    	}
	    });

		$("#classificacao").change(function(){
            $.ajax({
                url: window.location,
                type: "POST",
                data: {ajax: 'sim', classificacao: $("#classificacao").val()},
                timeout: 7000
            }).fail(function(){
                alert('fail');
            }).done(function(data){
                data = JSON.parse(data);
                var option = "<option value=''>Escolha a Providência</option>";
                if (data.ok !== undefined) {
                	$.each(data.ok, function (key, value) {
                        option += "<option data-os_cortesia='"+value.os_cortesia+"' data-abreos_preos='"+value.abre_os+"' data-prazodias='"+value.prazo_dias+"' value='"+value.motivo_ligacao+"'>"+value.descricao+" - "+value.prazo_dias+" D</option>";
                    });
                	$('#providencia').html(option);
                }else{
                	$('#providencia').html(option);
                }
            });
        });

	});
</script>

<style type="text/css">
    #downloadResp {
        text-align:center;
    }

    #downloadResp a{
        font-weight:bold;
        display:none;
    }
</style>

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
		<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
	</div>

<!--form-->
	<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'><?=traduz('Parametros de Pesquisa')?> </div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
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
					<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
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

		<?php
			if(in_array($login_fabrica, array(30,160,169,170,183)) || $interno_telecontrol || $replica_einhell){
		?>
				<div class='row-fluid'>
					<div class='span2'></div>
		<?php
					if ($interno_telecontrol OR $login_fabrica == 183) {
		?>
						<div class="span4">
                            <div class="control-group" id="admin_mult">
                                <label class='control-label'><?=traduz('Atendente')?></label>
                                    <select name='admin_mult[]' id='admin_mult' class='span12' multiple="multiple">
                                    <?php
                                        $ativo = ($login_fabrica == 160 or $replica_einhell) ? "" : " AND ativo";
                                        $sql = "SELECT admin, login, nome_completo FROM tbl_admin WHERE fabrica =     $login_fabrica $ativo  order by login";
                                        $res = pg_query($con,$sql);
                                        if (pg_numrows($res) > 0) {
                                            while($admin_result = pg_fetch_array($res)){
                                    ?>
             	                               <option <?= in_array($admin_result['admin'], $admin_array)?"selected": ""?> value="<?=$admin_result['admin']?>"><?=$admin_result['nome_completo']?></option>
                                    <?php
                                            }
                                        }
                                    ?>
                                   </select>
                            </div>
                        </div>
		<?php 			
				} else {
		?>
					<div class='span4'>
						<div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='admin'><?=traduz('Atendente')?></label>
							<div class='controls controls-row'>
								<div class='span4'>
					
									<select name="admin" id="admin">
										<option value=""></option>
										<?php
										$ativo = ($login_fabrica == 160 or $replica_einhell) ? "" : " AND ativo";
										$sql = "SELECT admin, login, nome_completo FROM tbl_admin WHERE fabrica =     $login_fabrica $ativo  order by login";
										$res = pg_query($con,$sql);
										foreach (pg_fetch_all($res) as $key) {
					
											$selected_admin = ( isset($admin) and ($admin == $key['admin']) ) ? "SELECTED" : '' ;
					
										?>
											<option value="<?php echo $key['admin']?>" <?php echo $selected_admin ?> >
										<?php echo (empty($key['nome_completo'])) ? $key['login'] : $key['nome_completo']?>
											</option>
					
					
										<?php
										}
					
										?>
									</select>
								</div>
							</div>
						</div>
					</div>
		<?php 		
				}
					if (in_array($login_fabrica, array(169,170))) {
					?>
						<div class='span4'>
							<div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
								<label class='control-label' for='classificacao'><?=traduz('Classificação')?></label>
								<div class='controls controls-row'>
									<div class='span4'>
										<select name="classificacao" id="classificacao">
											<option value=""></option>
											<?php
											$sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
											$res = pg_query($con,$sql);
											foreach (pg_fetch_all($res) as $key) {
												$selected = ( isset($classificacao) and ($classificacao == $key['hd_classificacao']) ) ? "SELECTED" : '' ;
												?>
												<option value="<?php echo $key['hd_classificacao']?>" <?php echo $selected ?> >
													<?php echo $key['descricao'] ?>
												</option>
											<?php
											}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>
					<?php
					}
					?>
					<div class='span2'></div>
				</div>

				<?php
					if (in_array($login_fabrica, array(169,170))){
				?>
					<div class="row-fluid">
						<div class="span2"></div>
						<div class='span4'>
							<div class='control-group <?=(in_array("providencia", $msg_erro["campos"])) ? "error" : ""?>'>
								<label class='control-label' for='providencia'>Providência</label>
								<div class='controls controls-row'>
									<div class='span4'>
										<select name="providencia" id="providencia">
											<option value=''>Escolha a Providência</option>
											<?php
											if (strlen(trim($classificacao)) > 0){
												$sql = "SELECT  tbl_hd_motivo_ligacao.hd_motivo_ligacao,
										                        tbl_hd_motivo_ligacao.descricao,
										                        tbl_hd_motivo_ligacao.categoria,
										                        tbl_hd_motivo_ligacao.prazo_dias,
										                        tbl_hd_motivo_ligacao.abre_os
										                FROM tbl_hd_motivo_ligacao
										                WHERE tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
										                AND tbl_hd_motivo_ligacao.hd_classificacao = {$classificacao}
										                AND tbl_hd_motivo_ligacao.ativo IS TRUE
										                ORDER BY tbl_hd_motivo_ligacao.descricao ASC";
										        $res = pg_query($con, $sql);

										        foreach (pg_fetch_all($res) as $key) {
													$selected = ( isset($providencia) and ($providencia == $key['hd_motivo_ligacao']) ) ? "SELECTED" : '' ;
													?>
													<option value="<?php echo $key['hd_motivo_ligacao']?>" <?php echo $selected ?> >
														<?php echo $key['descricao'].' - '.$key['prazo_dias'].' D' ?>
													</option>
												<?php
												}
									        }
									        ?>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class='span4'>
							<div class='control-group <?=(in_array("tipo_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
								<label class='control-label' for='tipo_atendimento'>Tipo Atendimento</label>
								<div class='controls controls-row'>
									<div class='span4'>
										<select name="tipo_atendimento" id="tipo_atendimento">
											<option value=''>Escolha a Atendimento</option>
											<?php
												$sql = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY descricao ASC";
												$res = pg_query($con, $sql);
												if (pg_num_rows($res) > 0){
													foreach (pg_fetch_all($res) as $key) {
						                                $selected_tipo_atendimento = ( isset($tipo_atendimento) and ($tipo_atendimento == $key['tipo_atendimento']) ) ? "SELECTED" : '' ;
						                            ?>
						                                <option value="<?php echo $key['tipo_atendimento']?>" <?php echo $selected_tipo_atendimento ?> >
						                                    <?php echo $key['descricao']?>
						                                </option>
						                            <?php
						                            }
						                        }
											?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="span2"></div>
					</div>

					<?php if(in_array($login_fabrica, array(169,170))){ ?>
					<div class="row-fluid">
						<span class="span2"></span>
						<div class='span4'>
							<div class='control-group'>
								<label class='control-label'>Providência Nível 3</label>
								<div class='controls controls-row'>
									<select name="providencia_nivel_3" id='providencia_nivel_3' class='frm'>
										<option value=""></option>
										<?php
											$sqlProvidencia3 = "SELECT hd_providencia, descricao
																FROM tbl_hd_providencia WHERE fabrica = {$login_fabrica}
																AND ativo IS TRUE
																ORDER BY descricao DESC";
											$resProvidencia3 = pg_query($con,$sqlProvidencia3);

											if(pg_num_rows($resProvidencia3) > 0){
												while($dadosProv = pg_fetch_object($resProvidencia3)){
													
													$selected = ($dadosProv->hd_providencia == $_POST['providencia_nivel_3']) ? "selected" : "";

													?>
													<option value="<?=$dadosProv->hd_providencia?>" <?=$selected?>>
														<?= $dadosProv->descricao ?>
													</option>
													<?php
												}
											}
										?>
									</select>
								</div>
							</div>
						</div>
						<div class='span4'>
							<div class='control-group'>
								<label class='control-label'>Motivo Contato</label>
								<div class='controls controls-row'>
									<select name="motivo_contato" id='motivo_contato' class='frm'>
										<option value=""></option>
										<?php
											$sqlMotivoContato = "SELECT motivo_contato, descricao
																FROM tbl_motivo_contato WHERE fabrica = {$login_fabrica}
																AND ativo IS TRUE
																ORDER BY descricao DESC";
											$resMotivoContato = pg_query($con,$sqlMotivoContato);

											if(pg_num_rows($resMotivoContato) > 0){
												while($dadosContato = pg_fetch_object($resMotivoContato)){
													
													$selected = ($dadosContato->motivo_contato == $_POST['motivo_contato']) ? "selected" : "";

													?>
													<option value="<?=$dadosContato->motivo_contato?>" <?=$selected?>>
														<?= $dadosContato->descricao ?>
													</option>
													<?php
												}
											}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>

					<div class="row-fluid">
						<div class="span2"></div>
						<div class="span4">
							<label class="control-label">Status</label>
							<div class='controls controls-row'>
								<select name="status" class="frm" >
									<option value=""></option>
									<?php
										$array_status = array(
											'0' => 'Aberta Call-Center',
											'14' => 'Aguardando Auditoria',
											'1' => 'Aguardando Analise',
											'3' => 'Aguardando Conserto',
											'30' => 'Aguardando Fechamento',
											'2' => 'Aguardando Peças',
											'8' => 'Aguardando Produto',
											'4' => 'Aguardando Retirada',
											'9' => 'Finalizada',
											'28' => 'OS Cancelada'
										);

										foreach ($array_status as $key => $value) {
			                                $selected_status = ( isset($status) and ($status == $key) ) ? "SELECTED" : '' ;

									?>
											<option value="<?=$key?>" <?=$selected_status?> > <?echo $value?> </option>
									<?
										}
									?>
								</select>
							</div>
						</div>
					</div>

					<div class="row-fluid">
						<div class="span2"></div>
						<div class="span3">
							<div class="control-group">
								<label class="control-label" for="">&nbsp;</label>
								<div class="controls controls-row">
									<label class="checkbox" >
										<input type='checkbox' name='jornada' id='jornada' value='true' <?if($xjornada == 'true') echo "CHECKED";?> /> Jornada
									</label>
								</div>
							</div>
						</div>
					</div>
				<?php
					}
				?>

		<?php
			}
		?>
		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />
	</form>

    <?php if (!in_array($login_fabrica, array(30,169,170))): ?>
	<br />
	<div class="alert">
		<p class="tac" style="font-size:18px; font-weight:bold;"><?=traduz('Layout arquivo')?></p>
		<p><?=traduz('O arquivo deverá ser TXT ou CSV')?></p>
		<?php if ($login_fabrica == 151) { ?>
			<p><?=traduz('O arquivo deverá conter as seguintes colunas separadas por <b>PONTO E VÍRGULA (;)</b> : <br /> nº do atendimento;texto da interação;situação (aberto, cancelado ou resolvido);providência')?></p>
		<?php } else { ?>
			<p><?=traduz('O arquivo deverá conter as seguintes colunas separadas por <b>PONTO E VÍRGULA (;)</b> : <br /> nº do atendimento;data da interacao (dd/mm/yyyy);texto da interação;situação (aberto, cancelado ou resolvido);providência')?></p>
		<?php } ?>
		
		<p><b>OBS:</b> <?=traduz('No corpo do texto da interação não deverá conter <b>PONTO E VÍRGULA (;)')?></b></p>
		<p><b>OBS 2:</b> <?=traduz('Será, ao fim do processo, retornado uma planilha com o resultado das linhas<br />que foram processadas com sucesso ou acusaram erro.')?></p>
	</div>

	<div id="msg_upload"></div>

	<form name="form_anexo" method="post" action="ajax_hd_chamado_lote.php" enctype="multipart/form-data" class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'><?=traduz('Upload de interações')?></div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='controls controls-row'>
					<div class='span12'>
						<input type="file" name="anexo_upload" value="" />
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<p><br />
			<button class='btn' id="upload" type="submit"><?=traduz('Upload')?></button>
			<input type="hidden" name="acao" value="upload">
			<span id="processando"></span>
		</p><br />
	</form>
    <p id="downloadResp">
        <a class="btn btn-success" role="button" href="" target='_blank'>
            <img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'> <?=traduz('Resultado das alterações')?>
        </a>
    </p>
	<br />
    <?php endif ?>
</div>
<?php
	if(strlen ($msg_erro["msg"]) == 0  AND pg_num_rows($resSubmit) > 0){
		if (in_array($login_fabrica, array(169,170))){
		?>
		<table style="margin-bottom: 15px; margin-top: 10px;" align="center">
			<tr>
		    	<TD width='10'><span style='background:#33cc33;width:15px;height:15px;display:block;'></span></TD >
    			<TD align='left'>Status Jornada de 0 - 15 Dias.</TD >

				<TD width='10'><span style='background:#ffff00;width:15px;height:15px;display:block;'></span></TD >
    			<TD align='left'>Status Jornada de 16 - 25 Dias.</TD >

   				<TD width='10'><span style='background:#ff0000;width:15px;height:15px;display:block;'></span></TD >
    			<TD align='left'>Status Jornada Acima de 25 Dias.</TD >
			</tr>
		</table>
		<?php
		}
?>
			<table id="resultado_atendimentos" class = 'table table-bordered table-hover table-large'>
				<thead>
					<tr class = 'titulo_coluna'>
						<td><input type="checkbox" name="todos" id="todos">
						<th>Atendente</th>
						<th>Data</th>
						<th>Atendimento</th>
						<?php if ($interno_telecontrol){ ?>
						<th>Origem</th>
						<?php } ?>
						<th>Cliente</th>
						<th>Cidade</th>
						<th>Estado</th>
						<?php if (!in_array($login_fabrica, array(169,170))){ ?>
						<th>OS</th>
						<?php } ?>
						<th>Status do Atendimento</th>
						<?php if (in_array($login_fabrica, array(169,170))){ ?>
						<th>Providência nv 3.</th>
						<th>Motivo Contato</th>
						<th>OS</th>
						<th> Status OS </th>
						<th> Status Jornada </th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php
						for($i = 0; $i < $count; $i++){
							$os 			= pg_fetch_result($resSubmit, $i, 'os');
							$status			= pg_fetch_result($resSubmit, $i, 'status');
							$atendente		= pg_fetch_result($resSubmit, $i, 'atendente_nome');
							if (empty($atendente)) {
								$atendente = pg_fetch_result($resSubmit, $i, 'atendente');
							}
							$data			= pg_fetch_result($resSubmit, $i, 'data');
							$atendimento 	= pg_fetch_result($resSubmit, $i, 'hd_chamado');
							$status			= pg_fetch_result($resSubmit, $i, 'status');
							$cidade			= pg_fetch_result($resSubmit, $i, 'cidade');
							$estado			= pg_fetch_result($resSubmit, $i, 'estado');
							$cliente		= pg_fetch_result($resSubmit, $i, 'nome');
							$descricao_providencia3     = pg_fetch_result($resSubmit, $i, 'descricao_providencia');
                			$motivo_contato_callcenter  = pg_fetch_result($resSubmit, $i, 'descricao_motivo_contato');

							if ($interno_telecontrol) {
								$origem     = pg_fetch_result($resSubmit, $i, 'origem');
							}

							if (in_array($login_fabrica, array(169,170))){
								$jornada 			= pg_fetch_result($resSubmit, $i, 'jornada');
								$status_os 			= pg_fetch_result($resSubmit, $i, 'status_os');
								$status_jornada		= pg_fetch_result($resSubmit, $i, 'status_jornada');

								if (strlen(trim($status_jornada)) > 0){
									if ($status_jornada <= '15'){
										$texto_jornada = "0 - 15 Dias";
										$cor = "bgcolor='#33cc33'";
									}else if ($status_jornada > '15' AND $status_jornada <= '25'){
										$texto_jornada = "16 - 25 Dias";
										$cor = "bgcolor='#ffff00'";
									}else if ($status_jornada  > '25'){
										$texto_jornada = "Acima de 26 Dias";
										$cor = "bgcolor='#ff0000'";
									}
								}else{
									$texto_jornada = "";
									$cor = "";
								}
							}else{
								$cor = "";
							}

							$body .= "<tr ".$cor.">
									<td><input type='checkbox' value='{$atendimento}' class='check'>
									<td class= 'tac'>{$atendente}</td>
									<td class= 'tac'>{$data}</td>
									<td class= 'tac'><a href='callcenter_interativo_new.php? callcenter={$atendimento}' target='_blank'>$atendimento</a></td>";
							if ($interno_telecontrol) {
								$body .= "<td class= 'tac'>{$origem}</td>";	
							}
							$body .= "<td class= 'tac'>{$cliente}</td>
									<td class= 'tac'>{$cidade}</td>
									<td class= 'tac'>{$estado}</td>";
							if (!in_array($login_fabrica, array(169,170))){
								$body .="<td class= 'tac'><a href='os_press.php? os={$os}' target='_blank' >{$os}</a></td>";
							}
							$body .= "<td class= 'tac'>{$status}</td>";

							if (in_array($login_fabrica, array(169,170))){
								$body .= "
									<td class= 'tac'>{$descricao_providencia3}</td>
									<td class= 'tac'>{$motivo_contato_callcenter}</td>
									<td class= 'tac'><a href='os_press.php? os={$os}' target='_blank' >{$os}</a></td>
									<td class= 'tac'>{$status_os}</td>
									<td class= 'tac'>{$texto_jornada}</td>
								";
							}

							$boby .="</tr>";
							}
							echo $body;
					?>
				</tbody>
			</table>
				<script>
					$.dataTableLoad({ table: "#resultado_atendimentos" });
				</script>
			<br />

			<div class='container'>
			<div class="container" id="mensagem"></div>

			<form name='frm_relatorio' METHOD='POST' align='center' class='form-search form-inline tc_formulario' >
				<div class ='titulo_tabela'>Parametros de alteração</div>
				<br/>

				<div class='row-fluid'>
					<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='data_inicial'>Transferir p/</label>
							<div class='controls controls-row'>
								<div class='span4'>

									<select name="atendente" id="atendente">
										<option value=""></option>
										<?php

											$sql = "SELECT admin, login, nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica and ativo order by login";
											$res = pg_query($con,$sql);
											foreach (pg_fetch_all($res) as $key) {

												$selected_atendente = ( isset($atendente) and ($atendente == $key['admin']) ) ? "SELECTED" : '' ;

											?>
												<option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >
													<?php echo $key['nome_completo']?>
												</option>


											<?php
											}

										?>
									</select>
								</div>
							</div>
						</div>
					</div>
                    <?php if (!in_array($login_fabrica, array(30,169,170))): ?>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='data_inicial'>Providência</label>
							<div class='controls controls-row'>
								<div class='span4'>

									<select name="providencia" id="providencia">
										<option value=""></option>
										<?php

											$sql = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica and ativo order by descricao";
											$res = pg_query($con,$sql);
											foreach (pg_fetch_all($res) as $key) {

												$selected_providencia = ( isset($providencia) and ($providencia == $key['hd_motivo_ligacao']) ) ? "SELECTED" : '' ;

											?>
												<option value="<?php echo $key['hd_motivo_ligacao']?>" <?php echo $selected_providencia ?> >
													<?php echo $key['descricao']?>
												</option>
											<?php
											}

										?>
									</select>
								</div>
							</div>
						</div>
					</div>
                    <?php endif ?>
                <?php if (!in_array($login_fabrica, array(169,170))) { ?>
					<div class='span2'></div>
				</div>
				<?php } ?>

                <?php if ($login_fabrica <> 30): ?>
                <?php if (!in_array($login_fabrica, array(169,170))) { ?>
				<div class='row-fluid'>
					<div class='span2'></div>
				<?php } ?>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='data_final'>Situação</label>
							<div class='controls controls-row'>
								<div class='span4'>
									<select name="status" id="status">
										<option value=""></option>
										<?php

											$sql = "SELECT hd_status,status FROM tbl_hd_status WHERE fabrica = {$login_fabrica} ORDER BY status";
											$res = pg_query($con,$sql);
											foreach (pg_fetch_all($res) as $key) {

												$selected_status = ( isset($status) and ($status == $key['hd_status']) ) ? "SELECTED" : '' ;

											?>
												<option value="<?php echo $key['hd_status']?>" <?php echo $selected_status ?> >
													<?php echo $key['status']?>
												</option>
											<?php
											}

										?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class='span2'></div>
				</div>
                <?php endif ?>
				<p><br />
					<button class='btn' id="gravar" type="button">Gravar</button>
					<span id="processando_gravar"></span>
				</p><br />

<?php
	}
?>
<?php
include 'rodape.php';
?>
