<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$cachebypass=md5(time());

$btn_acao 	  = $_POST['acao'];
$data_inicial = $_POST['data_inicial_01'];
$data_final   = $_POST['data_final_01'];

$msg_erro = array();
$msgErrorPattern01 = traduz("Preencha os campos obrigatórios.");
$msgErrorPattern02 = traduz("A data de consulta deve ser no máximo de 3 meses.");
include_once('plugins/fileuploader/TdocsMirror.php');
$tDocs = new TdocsMirror();

if (isset($_POST['aprova_os'])) {

	$os = $_POST['os'];
	$texto = $_POST['texto'];
	try{
		if(strlen($texto) > 0){
			$campos_adicionais 	= array("justificativa_aprova_sem_anexo" => $texto);			
			$campos_adicionais 	= json_encode($campos_adicionais);
			$setcampos_adicionais = ",campos_adicionais = '{$campos_adicionais}'";
		}
		$query = "UPDATE tbl_auditoria_os 
		SET liberada = CURRENT_TIMESTAMP, 
		admin = $login_admin
		$setcampos_adicionais WHERE os = {$os}
		AND auditoria_status = 3 
		AND observacao = 'PRODUTOS TROCADOS NA OS' 
		AND tbl_auditoria_os.reprovada IS NULL 
		AND tbl_auditoria_os.liberada IS NULL";

		$res = pg_query($con, $query);

		if (strlen(pg_last_error($res)) > 0) {

			exit(json_encode(['msg' => 'error']));
		}

		$data_fechamento = (new DateTime())->format('Y-m-d');
		$data_finalizada = new DateTime();
		
		$sql_fecha_os = "UPDATE tbl_os 
							SET data_fechamento = '$data_fechamento',
							os_fechada = 't',
							finalizada = CURRENT_TIMESTAMP,
							status_checkpoint = 9 
							WHERE tbl_os.os = {$os}";

		$res = pg_query($con, $sql_fecha_os);
		if (strlen(pg_last_error($res)) > 0) {

			exit(json_encode(['msg' => 'error']));
		}

		exit(json_encode(['msg' => 'success']));

	} catch (Exception $e) {
		
		exit(json_encode(['msg' => 'error']));
	}
} 

if (isset($_POST['recusa_os'])) {

	$os = $_POST['os'];
	$posto = $_POST['posto'];

	try { 

		$descricao = "Aviso sobre OS Recusada";
		$mensagem  = "O comprovante foi recusado. Por favor, reenvie o comprovante";
		$tipo      = "OS";
		$pais      = "BR";

		$comunicado = "INSERT INTO tbl_comunicado (pais, mensagem, fabrica, obrigatorio_site, descricao, tipo, posto, ativo)
					   VALUES ('$pais', '$mensagem', $login_fabrica, 't', '$descricao', '$tipo', {$posto}, 't')";

		$res = pg_query($con, $comunicado);

		$query = "UPDATE tbl_auditoria_os 
				  SET reprovada = CURRENT_TIMESTAMP, 
				      admin = {$login_admin} 
				  WHERE os = {$os} 
				  AND auditoria_status = 3 
				  AND observacao = 'PRODUTOS TROCADOS NA OS' 
				  AND tbl_auditoria_os.reprovada IS NULL 
				  AND tbl_auditoria_os.liberada IS NULL";
		
		$res = pg_query($con, $query);

		$update_tdocs = "UPDATE tbl_tdocs 
						SET situacao = 'inativo'
						WHERE fabrica = $login_fabrica 
						AND referencia_id = $os
						AND contexto = 'comprovante_retirada' 
						AND situacao ='ativo'";

		$res = pg_query($con, $update_tdocs);

		if (strlen(pg_last_error() > 0)) {

			exit(json_encode(array('msg' => 'error')));
		}
	
		exit(json_encode(['msg' => 'success']));

	} catch (Exception $e) {
		
		exit(json_encode(['msg' => 'error']));
	}
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 3) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			for ($i = 0; $i < pg_num_rows($res); $i++ ) {
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

$layout_menu = "auditoria";
$title = traduz("RELATÓRIO DE PRODUTOS TROCADOS NA OS");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");

if ((strlen($btn_acao) > 0)) {

	$dataInicio            = $_POST['data_inicial'];
	$dataFinal             = $_POST['data_final'];
	$os                    = $_POST['os'];

	$dataFinalx  = new DateTime(implode('-', array_reverse(explode('/', $dataFinal))));
	$dataIniciox = new DateTime(implode('-', array_reverse(explode('/', $dataInicio))));		

    $dateInterval = $dataIniciox->diff($dataFinalx);

	if ($dateInterval->days > 95) { // ?

		$msg_erro["msg"][]    = $msgErrorPattern02;
		$msg_erro["campos"][] = traduz("data");
	}

	if( (strlen($dataInicio == 0) || strlen($dataFinal == 0)) And empty($os)){
		$msg_erro["msg"][]    = 'Por favor insira Data inicial e final';
		$msg_erro["campos"][] = traduz("data");
	}
}

?>

<style type="text/css">
	.status_checkpoint{width:9px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
	.status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
<script type="text/javascript" charset="utf-8">
	function aprovarOS(os, link) {
		if(link == ''){
			Swal.fire({
			title: 'Justificativa aprovar OS n° '+os+' sem o comprovante de retirada',
			input: 'text',
			inputAttributes: {
				autocapitalize: 'off'
			},
			showCancelButton: true,
			confirmButtonText: 'Salvar',
			showLoaderOnConfirm: true,
			cancelButtonText: 'Cancelar',
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',

			allowOutsideClick: () => !Swal.isLoading()
		}).then((texto) => {
			if (texto.value) {
				Swal.fire({
					title: 'Deseja realmente aprovar a OS nº '+os+'? Após a aprovação não será possível alterar',
					icon: 'warning',
					showCancelButton: true,
					cancelButtonText: 'Cancelar',
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Sim'
				}).then((result) => {
					if (result.value) {
						$.ajax({
							type: 'POST',
							url: "<?=$_SERVER['PHP_SELF']?>",
							data: {
								aprova_os: true,
								os : os,
								texto: texto.value
							}
						}).done(function (response) {

							response = JSON.parse(response);
							
							if (response['msg'] == 'success') {
								
								$('.btn_hide_' + os).hide();

								Swal.fire("OS aprovada com sucesso!", '', "success").then(
									document.getElementById('btn_pesquisa').click()
								)
								
							} else {
								Swal.fire("Error ao aprovar OS!", '', "error")
							}
						});
					}
				});
			}
		});
		}else{
			Swal.fire({
				title: 'Deseja realmente aprovar a OS nº '+os+'? Após a aprovação não será possível alterar',
				icon: 'warning',
				showCancelButton: true,
				cancelButtonText: 'Cancelar',
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Sim'
			}).then((result) => {
				if (result.value) {
					$.ajax({
						type: 'POST',
						url: "<?=$_SERVER['PHP_SELF']?>",
						data: {
							aprova_os: true,
							os : os,
						}
					}).done(function (response) {

						response = JSON.parse(response);
						
						if (response['msg'] == 'success') {
							
							$('.btn_hide_' + os).hide();

							Swal.fire("OS aprovada com sucesso!", '', "success").then(
								document.getElementById('btn_pesquisa').click()
							)
							
						} else {
							Swal.fire("Error ao aprovar OS!", '', "error")
						}
					});
				}
			});
		}
	
		
	}

	function recusarOS(os, posto) {
		Swal.fire({
				title: 'Deseja realmente reprovar a OS nº '+os+'?',
				icon: 'warning',
				showCancelButton: true,
				cancelButtonText: 'Cancelar',
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Sim'
			}).then((result) => {
				if (result.value) {
					$.ajax({
						type: 'POST',
						url: "<?=$_SERVER['PHP_SELF']?>",
						data: {
							recusa_os: true,
							os : os,
							posto: posto,
						}
					}).done(function (response) {

						response = JSON.parse(response);

						if (response['msg'] == 'success') {
							
							$('.btn_hide_' + os).hide();

							Swal.fire("OS Recusada!", '', "success").then(
								document.getElementById('btn_pesquisa').click()
							)
						} else {
							Swal.fire("Falha ao recusar OS!", '', "error")
						}
					});
				}
			});
	}

	function visualizaranexo(anexo,tipo,name,os) {
		Shadowbox.init();
		Shadowbox.open({
	        content: "imagens_comprovante_suggar.php?anexo=" + anexo+"&tipo="+tipo+"&name="+name+"&os="+os,
	        player: "iframe",
	        width: 850,
	        height:400,
	        options: {
	            modal: true,
	            enableKeys: true,
	            displayNav: true
	        }
	    });
	}

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }
	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
		$("#posto").val(retorno.posto);
	}

	function chamaAjax(linha,data_inicial,data_final,posto,produto,cache) {
		if (document.getElementById('div_sinal_' + linha).innerHTML == '+') {
			$.ajax({
				url: "mostra_os_peca_sem_pedido_ajax.php",
				type: "GET",
				data: {linha:linha,data_inicial:data_inicial,data_final:data_final,posto:posto,produto:produto},
				beforeSend: function(){
					$("#div_detalhe_"+linha).html("<img src='a_imagens/ajax-loader.gif'>");
				},
				complete: function(data){
					var dados = data.responseText;
					dados = dados.split("|");
					$("#div_detalhe_"+linha).html(dados[1]);
					$("#div_sinal_"+linha).html("-");
				}
			});
		} else {
			document.getElementById('div_detalhe_' + linha).innerHTML = "";
			document.getElementById('div_sinal_' + linha).innerHTML = '+';
		}
	}

</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right"> * <?=traduz('Campos obrigatórios') ?> </b> </div>
<form class="form-search form-inline tc_formulario" name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">
	<div class="titulo_tabela"> <?php echo traduz("Parâmetros de Pesquisa") ?></div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Ordem de Serviço") ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<input type="text" name="os" id="os" size="12" maxlength="10" class='span12' value= "<?= (isset($_POST['os']) ? $_POST['os'] : '') ?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Estado") ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
						<select name="consumidor_estado" class="frm" id="consumidor_estado">
							<option></option>
							<?php 
							$sql_estado = "SELECT DISTINCT * FROM (SELECT estado, fn_retira_especiais(nome) AS nome 
							FROM tbl_estado WHERE visivel IS TRUE AND tbl_estado.pais = 'BR' 
							UNION 
							SELECT estado, fn_retira_especiais(nome) AS nome FROM tbl_estado_exterior
							WHERE visivel IS TRUE AND pais = 'BR') x ORDER BY estado;";
							$res_estado = pg_query($con, $sql_estado);
							if(pg_num_rows($res_estado) > 0) {
								for($e =0; $e < pg_num_rows($res_estado); $e++){
									$valor_estado = pg_fetch_result($res_estado, $e, 'estado');
									$nome_estado = pg_fetch_result($res_estado, $e, 'nome');
									$selected = ($_POST['consumidor_estado'] == $valor_estado) ? 'selected' : '';
									echo "<option value='$valor_estado' $selected>$nome_estado</option>";
								}
							}
							?>
						</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial") ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?= (isset($_POST['data_inicial']) ? $_POST['data_inicial'] : '') ?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Final")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'><?php echo traduz("*")?></h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= (isset($_POST['data_final']) ? $_POST['data_final'] : '') ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Cod. Posto")?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="codigo_posto" name="codigo_posto" class='span8' maxlength="20" value="<?= (isset($_POST['codigo_posto']) ? $_POST['codigo_posto'] : '') ?>">
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="referencia" />
							<input type="hidden" id="posto" name="posto" value="<?=	(isset($_POST['posto']) ? $_POST['posto'] : '') ?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao_posto'><?php echo traduz("Nome Posto")?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<?= (isset($_POST['posto_nome']) ? $_POST['posto_nome'] : '') ?>">
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'><?php echo "Status OS"?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="radio" id="aguardando_comprovante" name="aguardando_anexado" value="aguardando_comprovante" <?= $_POST['aguardando_anexado'] =='aguardando_comprovante' ? 'checked':'' ?>>
						 	<label for="male"><?= traduz("Aguardando Comprovante")?></label><br>
							<input type="radio" id="comprovante_anexado" name="aguardando_anexado" value="comprovante_anexado" <?= $_POST['aguardando_anexado'] =='comprovante_anexado' ? 'checked':'' ?>>
							<label for="female"><?= traduz("Comprovante Anexado")?></label><br>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	</div>
	<br />
	<center>
		<input type="button" class='btn' id="btn_pesquisa" value="Pesquisar" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="<?php echo traduz("Preencha as opções e clique aqui para pesquisar") ?>">
		<input type="hidden" name="acao">
	</center>
	<br />
</form>

<?php
	if((strlen($btn_acao) > 0) && (count($msg_erro["msg"]) == 0)) {

		$os                    = $_POST['os'];
		$dataInicio            = $_POST['data_inicial'];
		$dataFinal             = $_POST['data_final'];
		$posto                 = $_POST['posto'];
		$aguardandoAnexado     = $_POST['aguardando_anexado'];
		$referenciaPosto       = $_POST['posto_referencia'];
		$descricaoPosto	       = $_POST['posto_nome'];
		$aguardandoComprovante = $_POST['aguardando_comprovante'];
		$comprovanteAnexado    = $_POST['comprovante_anexado'];
		$estado                = $_POST['consumidor_estado'];

		if($aguardandoAnexado == 'aguardando_comprovante'){
			$join_comprovante = " left JOIN tbl_tdocs ON tbl_os.os = tbl_tdocs.referencia_id and tbl_tdocs.fabrica = $login_fabrica and contexto = 'comprovante_retirada'";
			$whereAguardandoAnexado = " AND tbl_tdocs.tdocs isnull";
		}

		if ($aguardandoAnexado == 'comprovante_anexado') {
			$join_comprovante = " JOIN tbl_tdocs ON tbl_os.os = tbl_tdocs.referencia_id and tbl_tdocs.fabrica = $login_fabrica and contexto = 'comprovante_retirada'";

		}

	 	if (strlen($os) > 0) {
			
			$whereOS = " AND tbl_os.sua_os = '{$os}'";
		}

        if (strlen($posto) > 0) {

        	$wherePosto = " AND tbl_os.posto = {$posto}";
		}
		
		if(((strlen($dataInicio) > 0) and (strlen($dataFinal) > 0) ) and empty($os) ){
			$data_inicial = explode('/',$dataInicio);
			$data_final =  explode('/',$dataFinal);
			$data_final[0]= $data_final[0];
			$wheredata .= " AND tbl_os_troca.data BETWEEN '$data_inicial[2]-$data_inicial[1]-$data_inicial[0] 00:00:00' and '$data_final[2]-$data_final[1]-$data_final[0] 23:59:59'";
		}

		if(strlen($estado) > 0){
			$whereEstado = " AND tbl_os.consumidor_estado = '{$estado}'";
		}

		if (count($msg_erro["msg"]) == 0) {

            $queryAnexoRetirado = "SELECT tbl_os.sua_os,
		                                  tbl_os.os,
		                                  tbl_os.posto,
		                                  tbl_posto.nome as nome_posto, 
		                                  tbl_posto_fabrica.codigo_posto,
		                                  tbl_auditoria_os.auditoria_os,
		                                  TO_CHAR(tbl_os_troca.data, 'dd/mm/YYYY') AS troca_data,
		                                  tbl_posto.nome as posto,
                                          (SELECT tdocs_id ||'##' ||obs 
                                           FROM tbl_tdocs
                                           WHERE contexto = 'comprovante_retirada'
                                           AND referencia_id = tbl_os.os
										   AND situacao = 'ativo' and tbl_tdocs.fabrica = $login_fabrica order by tdocs desc limit 1) AS link
			                        FROM tbl_os
			                        JOIN tbl_os_troca USING(os)
			                        JOIN tbl_posto USING(posto)
			                        join tbl_posto_fabrica on tbl_posto_fabrica.posto= tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
			                        JOIN tbl_auditoria_os 
			                            ON (tbl_os.os = tbl_auditoria_os.os 
			                                AND tbl_auditoria_os.auditoria_status = 3 
			                                AND tbl_auditoria_os.liberada IS NULL 
			                                AND tbl_auditoria_os.reprovada IS NULL 
			                                AND tbl_auditoria_os.observacao = 'PRODUTOS TROCADOS NA OS')
									$join_comprovante
			                        WHERE tbl_os.fabrica = {$login_fabrica}
			                        AND tbl_os.finalizada IS NULL
			                        AND tbl_os.excluida IS NOT TRUE
	                                AND tbl_auditoria_os.liberada IS NULL 
	                                AND tbl_auditoria_os.reprovada IS NULL 
			                        AND tbl_os_troca.fabric = {$login_fabrica}
									{$wheredata}
									{$whereEstado}
			                        {$whereOS}
			                        {$wherePosto}
			                        {$whereAguardandoAnexado}";

			$resAnexoRetirado = pg_query($con, $queryAnexoRetirado); 

			if (pg_num_rows($resAnexoRetirado) > 0) { ?>
	
			<div class="container">
				<div class="row">
					<div class="span2"></div>
					<div class="span6">
						<h5> Status OS </h5>
	                    <table border="0" cellspacing="0" cellpadding="0">
	                        <tbody>
		                    	<tr height="18">
		                            <td width="18">
		                                <div class="status_checkpoint" style="background-color:#ffff66">&nbsp;</div>
		                            </td>
		                    		<td align="left">
		                        		<font size="1">
		                                    <b>Aguardando Comprovante </b>
		                                </font>
		                    		</td>
		                        </tr>
		                        <tr height="18">
		                            <td width="18">
		                                <div class="status_checkpoint" style="background-color:#8cff66">&nbsp;</div>
		                            </td>
		                            <td align="left">
		                                <font size="1">
		                                    <b>Comprovante Anexado</b>
		                                </font>
		                            </td>
		                        </tr>
	                    	</tbody>
	                	</table>
	                </div>
                </div>
            </div>
            <br><br>
				<table class='table table-bordered table-fixed'>
					<thead>
						<tr class='titulo_tabela'>
							<td colspan='4'>
								<center>Relatório Troca de Produto</center>
							</td>
						</tr>
						<tr class='titulo_coluna'>
							<th width="10%"><?= traduz("OS")  ?></th>
							<th width="10%"><?= traduz("Data Troca")  ?></th>
							<th width="25%"><?= traduz("Posto")   ?></th>
							<th width="22%"><?= traduz("Ações") ?></th>
						</tr>
					</thead>
					<tbody>

				<?php 

					for ($i = 0; $i < pg_num_rows($resAnexoRetirado); $i++) {

						$ordemServico  = pg_fetch_result($resAnexoRetirado, $i, 'os');				
						$sua_os = pg_fetch_result($resAnexoRetirado, $i, 'sua_os');		
						$dataTrocaItem = pg_fetch_result($resAnexoRetirado, $i, 'troca_data');
						$posto  	   = pg_fetch_result($resAnexoRetirado, $i, 'posto');
						$link		   = pg_fetch_result($resAnexoRetirado, $i, 'link');
						$posto		   = pg_fetch_result($resAnexoRetirado, $i, 'posto');
						$auditoria_os  = pg_fetch_result($resAnexoRetirado, $i, 'auditoria_os');
						$nome_posto = pg_fetch_result($resAnexoRetirado, $i, 'nome_posto');
						$codigo_posto = pg_fetch_result($resAnexoRetirado, $i, 'codigo_posto');
						
						$links = explode('##',$link);
						$bgColor = "#ffff66";
						$name = json_decode($links[1],true);
						$file_name = substr($name[0]['filename'],-3,3);
						if (strlen($links[0]) > 0) {

							$bgColor = "#8cff66";
						}
					?>

						<tr bgcolor="<?=$bgColor?>">
							<td class='tac'><a href="os_press.php?os=<?= $ordemServico ?>" target="_blank"> <?= $sua_os ?>&nbsp;</td>
							<td class='tac'><?= $dataTrocaItem ?>&nbsp;</td>
							<td class='tac'><?= $codigo_posto . ' - '. $nome_posto ?>&nbsp;</td>
							<td>
								<button class="btn btn-success btn_hide_<?=$ordemServico?>" onclick="aprovarOS(<?=$ordemServico?>, '<?=$link[0]?>')">Aprovar</button>
								<button class="btn btn-danger btn_hide_<?=$ordemServico?>" onclick="recusarOS(<?=$ordemServico?>, <?=$posto?>)">Recusar</button>
								<?php if (strlen($links[0]) > 0) { ?>
								<button class="btn btn-primary btn_hide_<?=$ordemServico?>" onclick="visualizaranexo('<?=$links[0]?>','<?=$file_name?>','<?=$name[0]['filename']?>','<?=$os?>')"> Visualizar </button>
								 <?php } ?>
							</td>
						</tr>
						
					<?php } ?>

					</tbody>
					<tfoot>
						<tr class='titulo_coluna'>
							<td colspan='3' style='text-align:right;'>Número de Registros:</td>
							<td><?=pg_num_rows($resAnexoRetirado)?></td>
						</tr>
					</tfoot>
				</table>

			<?php } else { ?>
			
				<div class='alert'><h4><?=traduz("Nenhum resultado encontrado")?></h4></div>
			
			<?php }
		}
	}

	include "rodape.php" ;
