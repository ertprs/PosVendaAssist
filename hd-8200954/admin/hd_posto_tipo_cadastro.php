<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
$admin_privilegios = 'gerencia,cadastros';
include_once 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';
include_once '../helpdesk.inc.php';

$layout_menu = 'cadastro';

if ($login_fabrica == 35) {
	$attCfg = array(
		'labels' => array(
			0 => 'Anexar'
		),
		'obrigatorio' => array(0)
	);

} elseif ($login_fabrica == 151) {
		
	$queryAnexos = "SELECT anexo_tipo, nome, codigo
					FROM tbl_anexo_tipo 
					WHERE fabrica = $login_fabrica
					AND ativo = 't' 
					AND anexo_contexto = 7";
		
	$resAnexos = pg_query($con, $queryAnexos);

	$tipo_anexos = pg_fetch_all($resAnexos);

	$attCfg = [
		'labels'      => $tipo_anexos,
		'obrigatorio' => [1,1,1,1,1,1]
	];

} else { 

	$attCfg = array(
		'labels' => array(
			0 => 'Anexar',
			1 => 'Nota Fiscal',
			2 => 'Etiqueta',
			3 => 'Produto (1)',
			4 => 'Produto (2)',
			5 => 'Produto (3)',
		),
		'obrigatorio' => array(0,0,1,1,0,0)
	);

}

/**
 * tbl_tipo_solicitacao
 *         Column         |         Type
 * ------------------------+----------------------
 * tipo_solicitacao       | integer
 * fabrica                | integer
 * descricao              | character varying(60)
 * ativo                  | boolean
 * informacoes_adicionais	 | text (json)
 * campo_obrigatorio      | text (json)
 *
 * ckeck box com coluna com campos obrigatorios e ativo , descricao fixa.
 **/

if(isset($_POST["remove_anexo"])){

	$tipo_solicitacao 	= $_POST['tipo_solicitacao'];
	$posicao 			= $_POST['posicao'];

	$sql = "SELECT
				descricao,
				informacoes_adicionais,
				campo_obrigatorio
			FROM tbl_tipo_solicitacao
			WHERE fabrica = {$login_fabrica}
			AND tipo_solicitacao = {$tipo_solicitacao}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)>0) {

		$inf_adicionais_ativo = json_decode(pg_fetch_result($res, 0, "informacoes_adicionais"), true);
		$inf_adicionais_obrig = json_decode(pg_fetch_result($res, 0, "campo_obrigatorio"), true);

		unset($inf_adicionais_ativo['anexos'][$posicao]);
		$inf_adicionais_ativo = json_encode($inf_adicionais_ativo);

		unset($inf_adicionais_obrig['anexo_obrigatorio'][$posicao]);
		$inf_adicionais_obrig = json_encode($inf_adicionais_obrig);

		$sql_upd = "UPDATE tbl_tipo_solicitacao SET
						informacoes_adicionais = '$inf_adicionais_ativo',
						campo_obrigatorio      = '$inf_adicionais_obrig'
						WHERE tipo_solicitacao = $tipo_solicitacao
						AND fabrica = {$login_fabrica}";
		$res_upd = pg_query($con,$sql_upd);

		if(strlen(trim(pg_last_error($con)))==0){
			echo "sim";
		}else{
			echo "nao";
		}

	}else{
		echo "sim";
	}
	exit;
}

if(strlen($_GET['ativa_tipo_atendimento'])>0){
	$tipo_solicitacao = $_GET["atendimento"];
	if(strlen($tipo_solicitacao)>0){
		$sql = "UPDATE tbl_tipo_solicitacao SET ativo = true WHERE tipo_solicitacao = {$tipo_solicitacao} and fabrica = {$login_fabrica}";
		$res = pg_query($con,$sql);
		if(pg_last_error()){
			$return = array("error" => utf8_encode("Erro ao fazer atualização"));
		}else{
			$return = array("success" => true);
		}
	}else{
		$return = array("error" => utf8_decode("Erro ao ativar solicitação!")) ;
	}

	exit(json_encode($return));

}
if(strlen($_GET['desativa_tipo_atendimento'])>0){
	$tipo_solicitacao = $_GET["atendimento"];
	if(strlen($tipo_solicitacao)>0){
		$sql = "UPDATE tbl_tipo_solicitacao SET ativo = false WHERE tipo_solicitacao = {$tipo_solicitacao} and fabrica = {$login_fabrica}";
		$res = pg_query($con,$sql);
		if(pg_last_error()){
			$return = array("error" => utf8_encode("Erro ao fazer atualização"));
		}else{
			$return = array("success" => true);
		}
	}else{
		$return = array("error" => utf8_decode("Erro ao ativar solicitação!")) ;
	}

	exit(json_encode($return));

}


$campos_obri = array(
	"os_posto"       => "Ordem de Serviço",
	"num_pedido"     => "Pedido",
	"hd_chamado_sac" => "Protocolo de Atendimento",
	"produto_os"     => "Produto",
	"pedido_pend"    => "Peças",
	"nome_cliente"   => "Cliente"
);

if($login_fabrica == 35) {
	$campos_obri['motivo'] = "Motivo";
	$campos_obri['ticket_atendimento'] = "Ticket de Atendimento";
	$campos_obri['cod_localizador'] = "Código Localizador";
	$campos_obri['pre_logistica'] = "Pré-Logística";
}

if(strlen($_POST["gravar"])>0){
	$tipo_solicitacao = $_POST["tipo_solicitacao"];
	$descricao    = trim($_POST['solicitacao']['descricao']);
	$obrigatorios = $_POST['campos']['obrigatorio'];
	$descricao = substr($descricao, 0 , 60);
	$interno = '';
	
	if ($login_fabrica == 151) {
		$anexos_obrigadorios = $_POST["anexos"]["anexos"];
	}

	if (in_array($login_fabrica, array(30,72))) {
		$interno  = trim($_POST['solicitacao']['interno']);
	}

	foreach ($obrigatorios as $id => $campo) {
		$obrigatorios[$id] = utf8_encode($campo);
	}
	if(!empty($_POST['obrigatorio'])){
		$obrigatorios 	= array_merge($obrigatorios, $_POST['obrigatorio']);
	}

	if (empty($descricao)) {
		$msg_erro["msg"][] = "O campo descrição é obrigatório!";
		$msg_erro["campos"][] = "solicitacao[descricao]";
	}

	if (!in_array($login_fabrica, array(153))) {
		$ativo    = $_POST['campos']['ativo'];
		foreach ($obrigatorios as $key => $value) {
			if(!strlen($ativo[$key]) && !in_array($key,array('anexo_obrigatorio','3_anexo')) ){
				$msg_erro["msg"][] = "O campo '$value' também deve ser marcado como ativo";
			}
		}

		foreach ($ativo as $id => $campo) {
			$ativo[$id] = utf8_encode($campo);
		}
		if(!empty($_POST['anexos'])){
			$ativo 	= array_merge($ativo, $_POST['anexos']);
		}

		if(count($obrigatorios) == 0 && !in_array($login_fabrica, array(35,163,175,203))){
	        $msg_erro["msg"][] = "É necessário que tenha, ao menos, 01(um) campo obrigatório";
		}
	}else{
		$ativo = $obrigatorios;
	}
	
	if(!count($msg_erro)>0){
		$obrigatorios = json_encode($obrigatorios);
		$obrigatorios  = str_replace("\\","\\\\",$obrigatorios) ;
		$ativo    = json_encode($ativo);
		$ativo    = str_replace("\\","\\\\",$ativo);

		if (in_array($login_fabrica, array(30,72))) {
			if (!empty($interno)) {
				$update_interno = ",codigo = '$interno'";
			}else{
				$update_interno = ",codigo = null";
			}
		}

		if($login_fabrica == 35){
			$observacao = $_POST['observacao'];
		}

		if(!empty($tipo_solicitacao)){
			$sql = "UPDATE tbl_tipo_solicitacao SET
						descricao          	   = '$descricao' ,
						fabrica                = $login_fabrica ,
						ativo                  = true ,
						informacoes_adicionais = '$ativo',
						campo_obrigatorio      = '$obrigatorios'
						$update_interno
						WHERE tipo_solicitacao = $tipo_solicitacao
						AND fabrica = {$login_fabrica}
			";
			$res = pg_query($con,$sql);

			if(pg_last_error()>0){
				$msg_erro["msg"][] = "Erro ao atualizar tipo de solicitação";
			}else{
				$msg_success = true;

				foreach($observacao as $key => $value){
					$visivel = ($_POST['mostrar_'.$key] == 'sim')? 'true' : 'false';  
					$info_tipo_solicitacao = $_POST['info_tipo_solicitacao'][$key];

					if(empty($info_tipo_solicitacao)){
						$sql_obs = "INSERT INTO tbl_info_tipo_solicitacao (tipo_solicitacao, fabrica, observacao, visivel, admin) values ($tipo_solicitacao, $login_fabrica, '$value', $visivel, $login_admin)";
					}else{
						$sql_obs = "UPDATE tbl_info_tipo_solicitacao SET observacao = '$value', visivel = $visivel, admin = $login_admin WHERE info_tipo_solicitacao = $info_tipo_solicitacao";
					}
					$res_obs = pg_query($con, $sql_obs);
				}

				unset($_POST);
				unset($tipo_solicitacao);
			}
		}else{
			if (in_array($login_fabrica, array(30,72)) && !empty($interno)) {
				$insert_interno = ",codigo";
				$val_interno    = ",'$interno'";
			}

			$sql = "INSERT INTO tbl_tipo_solicitacao(
										descricao,
										fabrica,
										ativo,
										informacoes_adicionais,
										campo_obrigatorio
										$insert_interno
										)
							VALUES (
								'$descricao' ,
								$login_fabrica ,
								true ,
								'$ativo',
								'$obrigatorios'
								$val_interno
								)returning tipo_solicitacao
					";

			$res = pg_query($con,$sql);
			if(pg_last_error()>0){
				$msg_erro["msg"][] = "Erro ao gravar tipo de solicitação";
			}else{
				$msg_success = true;
				$tipo_solicitacao = pg_fetch_result($res, 0, tipo_solicitacao);

				foreach($observacao as $key => $value){
					$visivel = ($_POST['mostrar_'.$key] == 'sim')? 'true' : 'false';  

					$sql_obs = "INSERT INTO tbl_info_tipo_solicitacao (tipo_solicitacao, fabrica, observacao, visivel, admin) values ($tipo_solicitacao, $login_fabrica, '$value', $visivel, $login_admin)";
					$res_obs = pg_query($con, $sql_obs);
				}
				unset($_POST);
			}
		}
	}
}

if ($_GET["tipo_solicitacao"]) {
	$tipo_solicitacao = $_GET["tipo_solicitacao"];

	$sql = "SELECT
				descricao,
				informacoes_adicionais,
				campo_obrigatorio,
				codigo
			FROM tbl_tipo_solicitacao
			WHERE fabrica = {$login_fabrica}
			AND tipo_solicitacao = {$tipo_solicitacao}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		$msg_erro["msg"][] = "Tipo de Solicitação não encontrado";
		unset($tipo_solicitacao);
	} else {
		$inf_adicionais_ativo = json_decode(pg_fetch_result($res, 0, "informacoes_adicionais"), true);
		$inf_adicionais_obrig = json_decode(pg_fetch_result($res, 0, "campo_obrigatorio"), true);

		$qtde_anexos = count($inf_adicionais_ativo['anexos']);

		$_RESULT["solicitacao"] = array(
			"descricao" => pg_fetch_result($res, 0, "descricao")
		);

		if (in_array($login_fabrica, array(30,72))) {
			$_RESULT["solicitacao"]["interno"] = pg_fetch_result($res, 0, "codigo");
		}

		foreach ($inf_adicionais_ativo as $key => $value) {
			$_RESULT["campos"]["ativo"][$key] = true;
		}

		foreach ($inf_adicionais_obrig as $key => $value) {
			$_RESULT["campos"]["obrigatorio"][$key] = true;
		}
	}
}


$title = 'Cadastro Tipos de Solicitação Help-Desk Posto';

include_once 'cabecalho_new.php';

$plugins = array("select2");

include 'plugin_loader.php'; ?>

<script>

$(function() {
	$(".select2").select2();

	$(".plus_info_documento").click(function(){
		var campos_extra = $(".campos_informacao_documento:first").clone();
		var qtde_linha = $('#qtde_linha').val();
		qtde_linha = parseInt(qtde_linha) + 1;

		var campos_extra = '<div class="row-fluid campos_informacao_documento" style="min-height: 30px !important;">'+
				'<div class="span2"></div><div class="span4">'+
							'<div class="control-group">'+
								'<div class="controls controls-row">'+
									'<div class="span12">'+
										'<textarea name="observacao[]"></textarea>'+
									'</div>'+
								'</div>'+
							'</div>'+
						'</div>'+
						'<div class="span2">'+
							'<label>Sim</label>'+
							'<input type="radio" name="mostrar_'+qtde_linha+'" class="mostrar_sim" value="sim" >'+
						'</div>'+
						'<div class="span2">'+
							'<label>Não</label>'+
							'<input type="radio" name="mostrar_'+qtde_linha+'" class="mostrar_nao" value="nao">'+
						'</div>'+
						'<div class="span2">'+
												'</div>'+
					'</div><Br>';

		$("#campos_extras_informacao_documento").append(campos_extra);
		$('#qtde_linha').val(qtde_linha);
	});

	$(document).on("click", ".btn_menos", function(){

		var posicao = $(this).data('pos');
		var tipo_solicitacao = $(this).data('tiposolicitacao');

		$.ajax({
            url: "hd_posto_tipo_cadastro.php",
            type: "POST",
            data: { remove_anexo: true, posicao:posicao, tipo_solicitacao: tipo_solicitacao },
            complete: function(data) {
            	console.log('data '+data.responseText);
            	if(data.responseText == "nao"){
            		alert("Falha ao excluir anexo");
            	}else{
            		$('.tipo_anexo_'+ posicao).remove();
            		$('#hidden_anexo_'+ posicao).remove();
            		$('#hidden_anexo_obrigatorio_'+ posicao).remove();

            		

            	}                
            }
        });

		$("#qtde_anexos").val($("#qtde_anexos").val()-1);
	});

	$(".btn_plus").click(function(){
		$(".linha_anexos").show();
		var campo_btn_plus 	= $(".campo_btn_plus").val();
		var qtde_anexos = parseInt($("#qtde_anexos").val());

		var tipo_solicitacao = <? echo (strlen(trim($tipo_solicitacao))>0)? $tipo_solicitacao: 0 ?>;

		if(qtde_anexos >= 6){
			alert("Limite de Anexo atingido.");
		}else{

			var obrigatorio; 

			if($(".campo_btn_plus_obrigatorio").is(':checked')){
				obrigatorio = "Sim";
				obrig = "1";
			}else{
				obrigatorio = "Não";
				obrig = "0";
			}	

			cont = campo_btn_plus.length;

			for (i = 0; i < cont; i++) {

				var pode = true;

				$(".linha_anexos table tbody").each(function() {
				
					let len_anexos = $(this).find("tr." + campo_btn_plus[i]).length;

					if (len_anexos > 0) {

						pode = false;
					} 

				});

				if (pode == true) {

					qtde_anexos = qtde_anexos + 1;

					 let tr = "<tr class='"+ campo_btn_plus[i] + " tipo_anexo_" + i + "'><td style='text-align:center;nome_anexo'>" + campo_btn_plus[i] + "</td><td  style='text-align:center'>" + obrigatorio + "</td> <td style='text-align:center'><button type='button' class='btn-danger btn_menos' data-pos='" + (qtde_anexos-1) + "' data-tiposolicitacao='" + tipo_solicitacao + "'>Excluir</button></td>";
					
					let inputAnexo = "<input id='hidden_anexo_"+i+"' type='hidden' name='anexos[anexos][]' value='" + campo_btn_plus[i] + "'>";
					
					let hiddenElem = "<input id='hidden_anexo_obrigatorio_"+i+"' type='hidden' name='obrigatorio[anexo_obrigatorio][]' value='" + obrig + "'></tr>";

					$(".lista_anexos").append(tr);

					$(".lista_anexos").append(inputAnexo);

					$(".lista_anexos").append(hiddenElem);
				}
			}

			/*
				$(".lista_anexos").append("<tr><td style='text-align:center; nome_anexo'>"+campo_btn_plus+"</td><td  style='text-align:center'>"+obrigatorio+"</td> <td style='text-align:center'><button type='button' class='btn-danger btn_menos' data-pos='"+(qtde_anexos-1) +"' data-tiposolicitacao='"+tipo_solicitacao+"'>Excluir</button></td>");
			
				$(".lista_anexos").append("<input type='hidden' name='anexos[anexos][]' value='"+campo_btn_plus+"'>");
				$(".lista_anexos").append("<input type='hidden' name='obrigatorio[anexo_obrigatorio][]' value='"+obrig+"'></tr>");
			*/

			$("#qtde_anexos").val(qtde_anexos);
		}
	});

	//$(".btn_menos").click(function(){
	
	$(".botao-desativa").click(function(){
		var botao = $(this);
		var tipo_atendimento = $(this).parent().find("#tipo_solicitacao").val();
		$.ajax({
            url: "hd_posto_tipo_cadastro.php",
            type: "GET",
            data: { desativa_tipo_atendimento: true, atendimento: tipo_atendimento },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    botao.hide();
                   	botao.parent().find(".botao-ativa").first().show();
                }
            }
        });
	});

	$(".botao-ativa").click(function(){
		var botao = $(this);
		var tipo_atendimento = $(this).parent().find("#tipo_solicitacao").val();
		$.ajax({
            url: "hd_posto_tipo_cadastro.php",
            type: "GET",
            data: { ativa_tipo_atendimento: true, atendimento: tipo_atendimento  },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
					botao.hide();
					botao.parent().find(".botao-desativa").first().show();
                }
            }
        });

	});

	$(".listar_todos").click(function(){
		$("#resultados").toggle();
	});

});
</script>

<?
if ($msg_success){ ?>
    <div class="alert alert-success">
		<h4>Tipo de solicitação gravado corretamente.</h4>
    </div>
<?php }

if (count($msg_erro["msg"]) > 0){ ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>
	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>
	<form name="form_solicitacao" action="hd_posto_tipo_cadastro.php" method="POST" class="form-search form-inline" enctype="multipart/form-data" >

		<input type="hidden" name="tipo_solicitacao" value="<?=$tipo_solicitacao?>" />

		<div id="div_informacoes_solicitacao" class="tc_formulario">
			<div class="titulo_tabela" style="text-align: center;" >Cadastro de Solicitações</div>

			<br />

			<div class="row-fluid">

				<div class="span2"></div>

				<div class="span8 tac">
					<div class='control-group <?=(in_array('solicitacao[descricao]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="descricao">Descrição</label>
						<div class="controls controls-row">
							<h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
							<div class="span12">
								<?php
									if ($regras["solicitacao|descricao"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input name="solicitacao[descricao]" class="span12" maxlength='60' type="text" value="<?=getValue('solicitacao[descricao]')?>" <?=$readonly?> />
							</div>
						</div>
					</div>
				</div>
				<?php if (in_array($login_fabrica, array(30,72))) { ?>
				<div class="span2">
					<div class='control-group' >
						<label class="control-label" for="interno">Interno</label>
						<div class="controls controls-row">
							<div class="span12">
								<input name="solicitacao[interno]" type="checkbox" value="I" <?=(getValue('solicitacao[interno]') == 'I') ? 'checked' : '' ?> />
							</div>
						</div>
					</div>
				</div>
				<?php }else{ ?>
					<div class="span2"></div>
				<?php } ?>
			</div>

			<div class="titulo_tabela">Informações Adicionais</div>
			<br />

			<div class="row-fluid" style="min-height: 30px !important;" >
				<div class="span2 "></div>
				<div class="span4 ">Campo</div>
				<?php if (!in_array($login_fabrica, array(153))) { ?>
				<div class="span2 tac ">Ativo</div>
				<?php } ?>
				<div class="span2 tac ">Obrigatório</div>
				<div class="span2 "></div>
			</div>
			<hr></hr>
				<?php
				foreach ($campos_obri as $key => $descricao) {
				?>
					<div class="row-fluid" style="min-height: 30px !important;">
						<div class="span2"></div>

						<div class="span4">
							<div class='control-group' >
								<div class="controls controls-row">
									<div class="span12">
										<input name="campos[descricao][<?=$key?>]" class="span12" type="text" value="<?=$descricao?>" readonly="readonly" />
									</div>
								</div>
							</div>
						</div>
						<?php if (!in_array($login_fabrica, array(153))) { ?>
						<div class="span2">
							<div class='control-group' >
								<div class="controls controls-row">
									<div class="span12 tac">
										<input name="campos[ativo][<?=$key?>]" type="checkbox" value="<?=$descricao?>"
											<?php if(strlen(getValue("campos[ativo][{$key}]")) > 0){ echo "checked=checked"; } ?>
										/>
									</div>
								</div>
							</div>
						</div>
						<?php } ?>
						<div class="span2">
							<div class='control-group' >
								<div class="controls controls-row">
									<div class="span12 tac">
										<input name="campos[obrigatorio][<?=$key?>]" type="checkbox" value="<?=$descricao?>"
											<?php if(strlen(getValue("campos[obrigatorio][{$key}]")) > 0){ echo "checked=checked"; } ?>
										/>
									</div>
								</div>
							</div>
						</div>

						<div class="span2"></div>
					</div>
					<hr />
				<?php
				}
				if (in_array($login_fabrica, [35])) {
				?>
				<div class="row-fluid" style="min-height: 30px !important;">
				
					<div class="span12">
						<table class='table table-bordered table-large'>
							<tr>
								<td colspan="6" class="titulo_tabela"><center>Informações de Documentos</center></td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="row-fluid" style="min-height: 30px !important;">
					<div class="span2 "></div>
					<div class="span4 ">Observação</div>
					<div class="span2 ">Disponível</div>
					<div class="span2 "></div>
					<div class="span2 "></div>
				</div>

				<?php 

				//echo "buscar os tipo info Documentos <br> $tipo_solicitacao";
				$sql_info_tipo_solicitacao = "SELECT * from tbl_info_tipo_solicitacao where tipo_solicitacao = $tipo_solicitacao and fabrica = $login_fabrica";
				$res_info_tipo_solicitacao = pg_query($con, $sql_info_tipo_solicitacao);

				if(!empty($tipo_solicitacao) and pg_num_rows($res_info_tipo_solicitacao) > 0){
					
					for($i=0; $i<pg_num_rows($res_info_tipo_solicitacao); $i++){
						$info_tipo_solicitacao = pg_fetch_result($res_info_tipo_solicitacao, $i, info_tipo_solicitacao);
						$visivel = pg_fetch_result($res_info_tipo_solicitacao, $i, visivel);
						$observacao = pg_fetch_result($res_info_tipo_solicitacao, $i, observacao);

						$qtde_linha = $i;
					?>
					<div class="row-fluid campos_informacao_documento" style="min-height: 30px !important;">
						<div class="span2"></div>

						<div class="span4">
							<div class='control-group' >
								<div class="controls controls-row">
									<div class="span12">
										<textarea name='observacao[]'><?=$observacao?></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="span2">
							<label>Sim</label>
							<input type="radio" name="mostrar_<?=$i?>" class="mostrar_sim" value='sim' <?php if($visivel == 't'){ echo " checked "; } ?>>
						</div>
						<div class="span2">
							<label>Não</label>
							<input type="radio" name="mostrar_<?=$i?>" class='mostrar_nao' value='nao' <?php if($visivel == 'f'){ echo " checked "; } ?> >
						</div>
						<div class="span2">
							<?php if($i==0){
								echo "<button type='button' class='btn btn-success plus_info_documento'>+</button>";
							}
							echo "<input type='hidden' name='info_tipo_solicitacao[]' value='$info_tipo_solicitacao'>";
							?>
						</div>
					</div>
					<br>
					<?php
					}
				}else{

					if(isset($_POST['observacao'])) {

					$qtde_linha = $_POST['qtde_linha'];
					foreach($observacao as $key => $value){
						$visivel = ($_POST['mostrar_'.$key] == 'sim')? 't' : 'f'; 
						$observacao = $value;

					?>

					<div class="row-fluid campos_informacao_documento" style="min-height: 30px !important;">
						<div class="span2"></div>

						<div class="span4">
							<div class='control-group' >
								<div class="controls controls-row">
									<div class="span12">
										<textarea name='observacao[]'><?=$observacao?></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="span2">
							<label>Sim</label>
							<input type="radio" name="mostrar_<?=$key?>" class="mostrar_sim" value='sim' <?php if($visivel == 't'){ echo " checked "; } ?>>
						</div>
						<div class="span2">
							<label>Não</label>
							<input type="radio" name="mostrar_<?=$key?>" class='mostrar_nao' value='nao' <?php if($visivel == 'f'){ echo " checked "; } ?> >
						</div>
						<div class="span2">
							<?php if($key==0){
								echo "<button type='button' class='btn btn-success plus_info_documento'>+</button>";
							}
							echo "<input type='hidden' name='info_tipo_solicitacao[]' value='$info_tipo_solicitacao'>";
							?>
						</div>
					</div>
					<br>


					<?php 
					}
					}else{


					?>

				<div class="row-fluid campos_informacao_documento" style="min-height: 30px !important;">
					<div class="span2"></div>

					<div class="span4">
						<div class='control-group' >
							<div class="controls controls-row">
								<div class="span12">
									<textarea name='observacao[]'></textarea>
								</div>
							</div>
						</div>
					</div> 
					<div class="span2">
						<label>Sim</label>
						<input type="radio" name="mostrar_0" class="mostrar_sim" value='sim'>
					</div>
					<div class="span2">
						<label>Não</label>
						<input type="radio" name="mostrar_0" class='mostrar_nao' value='nao'>
					</div>
					<div class="span2">
						<button type="button" class="btn btn-success plus_info_documento">+</button>
						<input type='hidden' name='info_tipo_solicitacao[]' value='0'>
					</div>
				</div>
				<br>

				<?php } } ?>

				<div id="campos_extras_informacao_documento">
					
				</div>
				<input type="hidden" name="qtde_linha" id='qtde_linha' value="<?=(empty($qtde_linha)) ? 0 : $qtde_linha; ?>">
				<?php } ?>


				<?php if (in_array($login_fabrica, [30,35,72,151])) { ?>
				<div class="row-fluid" style="min-height: 30px !important;">
				
					<div class="span12">
						<table class='table table-bordered table-large'>
							<tr>
								<td colspan="6" class="titulo_tabela"><center>Anexos</center></td>
							</tr>
						</table>
					</div>
				</div>
	
				<div class="row-fluid" style="min-height: 30px !important;">
					<div class="span2 "></div>
					<div class="span4 ">Campo</div>
					<div class="span2 tac ">Obrigatório</div>
					<div class="span2 "></div>
					<div class="span2 "></div>
				</div>
				<div class="row-fluid" style="min-height: 30px !important;">
					<div class="span2"></div>

					<div class="span4">
						<div class='control-group' >
							<div class="controls controls-row">
								<div class="span12">
									<?php if ($login_fabrica == 151) { ?>
										<select multiple name="campos[descricao][3_anexo]" class="span12 select2 campo_btn_plus">
									<?php } else { ?> 
										<select name="campos[descricao][3_anexo]" class="span12 campo_btn_plus">
									<?php } ?>
										<?php 
											if ($login_fabrica == 151) {
												foreach ($attCfg['labels'] as $linha) {
													echo '<option value='. $linha['codigo'] .'>'. $linha['nome'] . '</option>';
												}
											} else { 
												foreach ($attCfg['labels'] as $linha) {
													echo "<option value='$linha'>$linha</option>";
												}
											}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<div class="controls controls-row">
								<div class="span12 tac">
									<?php 

										$check = "";
										$readonlyObrigatorio = "";

										if ($_RESULT["campos"]["obrigatorio"]["3_anexo"] || $login_fabrica == 151) { 
											
											$check = "checked";

											if ($login_fabrica == 151) {

												$readonlyObrigatorio = "readonly";
											}
									 	} 
									?>
									<input name="campos[obrigatorio][3_anexo]" class="campo_btn_plus_obrigatorio" type="checkbox" <?=$check?> <?=$readonlyObrigatorio?> value="3_anexo"/>
								</div>
							</div>
						</div>
					</div>

					<?php if ($login_fabrica != 35) { ?>
					<div class="span2">
						<div class='control-group' >
							<div class="controls controls-row">
								<div class="span12" style="text-align:center">
									<button type="button" class="btn btn-success btn_plus">+</button>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>
					<div class="span2"></div>
				</div>
				<br>
				<?php if($qtde_anexos > 0 ){ $style_lista_anexos = "; display:block; "; }else{$style_lista_anexos = "; display:none; ";} ?>
				<div class="row-fluid linha_anexos" style="min-height: 30px !important <?=$style_lista_anexos ?>;">
				
					<div class="span12">
						<table class='table table-bordered table-large lista_anexos'>
							<tr>
								<td colspan="6" class="titulo_tabela"><center>Anexos Cadastrados</center></td>
							</tr>
							<tr>
								<th>Nome</th>
								<th>Obrigatório</th>
								<th></th>
							</tr>
							<?php $qtd_i = 0;
							   foreach($inf_adicionais_ativo['anexos'] as $linha_anexos){
								echo "<tr class='tipo_anexo_".$qtd_i." '>";
									echo "<td style='text-align:center' class='nome_anexo' >$linha_anexos</td>";
									if($inf_adicionais_obrig['anexo_obrigatorio'][$qtd_i] == 1){
										echo "<td style='text-align:center'>Sim</td>";
										$obrig = 1;
									}else{
										echo "<td style='text-align:center'>Não</td>";
										$obrig = 0;
									}
									echo "<td style='text-align:center'><button type='button' class='btn-danger btn_menos' data-pos='$qtd_i' data-tiposolicitacao='$tipo_solicitacao' >Excluir</button> </td>";

									echo "<input type='hidden' name='obrigatorio[anexo_obrigatorio][]' value='".$obrig."'> <input type='hidden' name='anexos[anexos][]' value='".$linha_anexos."'>";

								echo "</tr>";
								$qtd_i++;
							}
							?>
						</table>
					</div>
					<input type="hidden" name="qtde_anexos" id="qtde_anexos" value="<?php echo (strlen(trim($qtde_anexos))>0)? $qtde_anexos : 0 ?>">
					
				</div>
				<? } ?>
			<br />

			<p class="tac">
				<input type="submit" class="btn " name="gravar" value="Gravar" />
				<?php
				if (!empty($tipo_solicitacao)) {
				?>
					<input type="button" value="Limpar Campos" class="btn btn-warning" onclick="javascript: window.location.href = 'hd_posto_tipo_cadastro.php'">
				<?php
				}
				?>
			</p>
			<br />
			<br />
		</div>
	</form>

	<?php

	$result = pg_fetch_all(pg_query($con, "SELECT * FROM tbl_tipo_solicitacao WHERE fabrica = $login_fabrica"));

	$colsize = 4;
	if ($login_fabrica == 151) {
		$colsize = 5;
	}

	if(!empty($result)) {
		echo "<table id='resultados' class='table table-large' >
			<thead>
				<tr>
					<th colspan='". $colsize ."' class='titulo_coluna' >Tipos de Solicitações cadastrados</th>
				</tr>
				<tr>
					<th class='titulo_coluna' >Descrição</th>
					<th class='titulo_coluna' >Campos Adicionais</th>
					<th class='titulo_coluna' >Campos Obrigatórios</th>";

		if ($login_fabrica == 151) {

			echo "<th class='titulo_coluna' >Anexos Obrigatórios</th>";
		}

		echo "<th class='titulo_coluna' >Ação</th>
				</tr>
			</thead>
			<tbody>
				";
		foreach ($result as $key => $campos) {
 			$inf_valores                = "";
			$campos_obrig           = "";
			$informacoes_adicionais = json_decode(str_replace("\\\\", "\\",  $campos['informacoes_adicionais']),true);
			$campo_obrigatorio      = json_decode(str_replace("\\\\", "\\", $campos['campo_obrigatorio']),true );


			foreach ($informacoes_adicionais as $id => $campo) {
				$informacoes_adicionais[$id] = utf8_decode($campo);
			}
			$inf_valores = implode(', <br />',$informacoes_adicionais);

			foreach ($campo_obrigatorio as $id => $campo) {
				$campo_obrigatorio[$id] = utf8_decode($campo);
			}
			$campos_obrig = implode(', <br />',$campo_obrigatorio);

			if ($login_fabrica == 151) {
				
				$anexo_obrigatorio = json_decode($campos['informacoes_adicionais'],1);
				$anexos_obrigadorios = implode(",",$anexo_obrigatorio['anexos']);

			}

			if($campos['ativo']=="t"){
				echo "<tr>
						<td><a href='hd_posto_tipo_cadastro.php?tipo_solicitacao={$campos['tipo_solicitacao']}' >".$campos['descricao']."</a></td>
						<td>{$inf_valores}</td>
						<td>{$campos_obrig}</td>";
				
				if ($login_fabrica == 151) {
					echo "<td>";
					echo $anexos_obrigadorios;
					echo "</td>";
				}

				echo "<td class='tac' >
							<input type='hidden' id='tipo_solicitacao' value='{$campos['tipo_solicitacao']}'>
							<button type='button' class='btn btn-danger botao-desativa'>Inativar</button>
							<button type='button' style='display:none;' class='btn btn-success botao-ativa'>Ativar</button>
						</td>
					 </tr>";
			}else{
				echo "<tr>
						<td><a href='hd_posto_tipo_cadastro.php?tipo_solicitacao={$campos['tipo_solicitacao']}' >".$campos['descricao']."</a></td>
						<td>{$inf_valores}</td>
						<td>{$campos_obrig}</td>";
				
				if ($login_fabrica == 151) {
					echo "<td>";
					echo $anexos_obrigadorios;
					echo "</td>";
				}	

				echo "<td class='tac' >
							<input type='hidden' id='tipo_solicitacao' value='{$campos['tipo_solicitacao']}'>
							<button type='button' class='btn btn-success botao-ativa'>Ativar</button>
							<button type='button' style='display:none;' class='btn btn-danger botao-desativa'>Inativar</button>
						</td>
					</tr>";
			}
		}
		echo   "
			</tbody>
		</table>";

	}
	?>


<?php
include_once 'rodape.php';

