<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';

$login_fabrica = 183;

if (!function_exists('valida_consumidor_cpf')) {
    function valida_consumidor_cpf($cpf_cnpj) {
        global $con;

        $cpf_cnpj = preg_replace("/\D/", "", $cpf_cnpj);

        if (strlen($cpf_cnpj) > 0) {
            $sql = "SELECT fn_valida_cnpj_cpf('{$cpf_cnpj}')";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                return false;
            }else{
                return true;
            }
        }
    }
}

if (isset($_POST['btn_pesquisa'])){
        
    if (!filter_input(INPUT_POST,"processo")) {
        $msg_erro["msg"][] = "Preencha o campo Número do Processo";
        $msg_erro['campos'][] = "processo";
    }

    if (filter_input(INPUT_POST,"cpf_cnpj")) {
        $valida_cpf_cnpj = valida_consumidor_cpf(filter_input(INPUT_POST,"cpf_cnpj"));

        if ($valida_cpf_cnpj === false){
            $msg_erro["msg"][] = "CPF informado inválido";
            $msg_erro['campos'][] = "cpf_cnpj";
        }
    }else{
    	$msg_erro["msg"][] = "Preencha o campo CPF/CNPJ";
        $msg_erro['campos'][] = "cpf_cnpj";
    }
 
    if (count($msg_erro["msg"]) == 0) {
 		
 		$cpf_cnpj  = trim(filter_input(INPUT_POST, "cpf_cnpj", FILTER_SANITIZE_SPECIAL_CHARS));
        $cpf_cnpj  = preg_replace("/\D/", "", $cpf_cnpj);
        $processo  = trim(filter_input(INPUT_POST,"processo",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));

        $sql = "
			SELECT DISTINCT
				tbl_processo.numero_processo as processo,
				tbl_processo.processo as processo_id,
				tbl_processo.fabrica as fabrica,
				tbl_os.os as os,
				tbl_os.serie as serie,
				tbl_hd_chamado.hd_chamado as hd_chamado,
				tbl_admin.nome_completo as atendente,
				tbl_hd_chamado.status as status,
				tbl_posto.nome_fantasia as posto,
				tbl_posto_fabrica.codigo_posto as codigo_posto,
				tbl_processo.orgao,
				tbl_processo.comarca,
				tbl_processo.consumidor_nome as consumidor_nome,
				tbl_processo.consumidor_cpf_cnpj as consumidor_cpf,
				tbl_processo.consumidor_fone1 as consumidor_fone1,
				tbl_processo.consumidor_fone2 as consumidor_fone2,
				tbl_processo.consumidor_email as consumidor_email,
				tbl_processo.consumidor_endereco as consumidor_endereco,
				tbl_processo.consumidor_bairro as consumidor_bairro,
				tbl_processo.consumidor_numero as consumidor_numero,
				tbl_processo.consumidor_complemento as consumidor_complemento,
				tbl_status_processo.descricao as status_processo,
				tbl_processo.data_solucao,
				tbl_fase_processual.descricao as fase_processual,
				tbl_processo.houve_acordo ,
				tbl_processo.data_transito_julgado, 
				tbl_processo.data_sentenca, 
				tbl_processo.data_execucao, 
				tbl_cidade.nome as cidade,
				tbl_cidade.estado as estado,
				tbl_processo.consumidor_cep as consumidor_cep,
				TO_CHAR(tbl_processo.data_solucao, 'DD/MM/YYYY') AS data_solucao,
				tbl_processo.advogado_nome,
				tbl_processo.advogado_celular,
				tbl_processo.advogado_email,
				tbl_processo.solucao,
				tbl_processo.valor_cliente,
				tbl_processo.valor_causa,
				tbl_processo.partes_adversas,
				tbl_processo.custo_advogado,
				tbl_processo.historico,
				tbl_motivo_processo.descricao as motivo_processo,
				tbl_produto.referencia as produto,
				tbl_produto.descricao as descricao,
				tbl_processo.data_input,
				tbl_processo.observacao AS observacao_audiencia
			FROM tbl_processo
			LEFT JOIN tbl_produto ON tbl_processo.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			LEFT JOIN tbl_hd_chamado ON tbl_processo.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
			LEFT JOIN tbl_os ON tbl_processo.os = tbl_os.os AND tbl_os.fabrica = {$login_fabrica}
			LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			LEFT JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin AND tbl_admin.fabrica = {$login_fabrica} 
			LEFT JOIN tbl_cidade ON tbl_processo.cidade = tbl_cidade.cidade
			LEFT JOIN tbl_motivo_processo ON tbl_processo.motivo_processo = tbl_motivo_processo.motivo_processo
			LEFT JOIN tbl_status_processo ON tbl_status_processo.status_processo = tbl_processo.status_processo AND tbl_status_processo.fabrica = {$login_fabrica}
			LEFT JOIN tbl_fase_processual ON tbl_fase_processual.fase_processual = tbl_processo.fase_processual AND tbl_fase_processual.fabrica = {$login_fabrica}
			WHERE tbl_processo.fabrica = {$login_fabrica}
			AND (tbl_processo.numero_processo = '$processo' OR tbl_processo.processo = $processo)
			AND tbl_processo.consumidor_cpf_cnpj = '$cpf_cnpj' ";
		$res = pg_query($con, $sql);
		
		if (pg_num_rows($res) > 0){
			$result = pg_fetch_all($res);
			
			$id_processo = pg_fetch_result($res, 0, 'processo_id');

			$sql_itens = "
				SELECT 
					tbl_processo_item.data_notificacao,         
					tbl_processo_item.data_audiencia1,          
					tbl_processo_item.data_audiencia2,          
					tbl_processo_item.data_acordo,              
					tbl_processo_item.data_cumprimento_acordo,  
					tbl_processo_item.custo_etapa,              
					tbl_processo_item.valor_acordo,             
					tbl_processo_item.obs_acordo,               
					tbl_tipo_documento.descricao AS tipo_documento,
					tbl_processo_pedido_cliente.descricao AS processo_pedido_cliente,
					tbl_proposta_acordo.descricao AS proposta_acordo
				FROM tbl_processo_item
				LEFT JOIN tbl_tipo_documento ON tbl_tipo_documento.tipo_documento = tbl_processo_item.tipo_documento AND tbl_tipo_documento.fabrica = {$login_fabrica}
				LEFT JOIN tbl_processo_pedido_cliente ON tbl_processo_pedido_cliente.processo_pedido_cliente = tbl_processo_item.processo_pedido_cliente AND tbl_processo_pedido_cliente.fabrica = {$login_fabrica}
				LEFT JOIN tbl_proposta_acordo ON tbl_proposta_acordo.proposta_acordo = tbl_processo_item.proposta_acordo AND tbl_proposta_acordo.fabrica = {$login_fabrica}
				WHERE processo = {$id_processo}";
			$res_itens = pg_query($con, $sql_itens);

			$result_itens = pg_fetch_all($res_itens);
		}
    }
}
?>

<!DOCTYPE html />
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="language" content="pt-br" />

	<!-- jQuery -->
	<script type="text/javascript" src="../callcenter/plugins/jquery-1.11.3.min.js" ></script>

	<!-- Bootstrap -->
	<script type="text/javascript" src="../plugins/bootstrap/js/bootstrap.min.js" ></script>
	<link rel="stylesheet" type="text/css" href="../plugins/bootstrap/css/bootstrap.min.css" />

	<!-- Plugins Adicionais -->
	<script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
	<script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
	<script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
	<link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

	<style>
		input, select, textarea {
			border-radius: 3px;
			font-size: 12px;
			color: #3E3E3D !important;
			height: 35px !important;
			padding: 10px 15px;
			border-color: #E2E0DF !important;
			background: white !important;
			box-shadow: 0px 0px 0px transparent;
		}

		button.btn{
			margin-bottom: 9px;
		}
		.img_tc {
			width: 16%;
			padding-top: 1%;
			padding-bottom: 1%;
			float: left;
		}
		.logo {
			margin-right: 10px;
		}
		#main-menu{
			padding-top: 1%
		}
		.navbar-inner{
			background: #292a4c;
		}
		.navbar .nav>li>a{
			color: #ffffff;
		}
		.escuro{
			background-color: #eeeeee !important;
		}
		.rodape{
			text-align: center;
		    padding-top: 5px;
		    background: #2a2a4c;
		    padding-bottom: 5px;
		    color: white;
		}
		.conteudo {
			padding-top: 100px;
			padding-bottom: 120px;
		}
		.navbar .nav>li>a {
		    float: none;
		    padding: 10px 15px 10px;
		    color: #ffffff !important;
		    text-decoration: none;
		    text-shadow: 0 1px 0 #ffffff;
		}
	</style>

	<script>
		$(function() {
			$("input, textarea").prop("readonly", true);

			$("input, textarea").blur(function() {
	        	var valor = $.trim($(this).val());

	        	if (valor.length > 0) {
	        		if ($(this).parents("div.form-group").hasClass("has-error")) {
	        			$(this).parents("div.form-group").removeClass("has-error");
	        		}
	        	}
	        });

	        $("#processo, #cpf_cnpj").prop("readonly", false);

	        var options = {
		        onKeyPress : function(cpfcnpj, e, field, options) {
		            var masks = ['000.000.000-000', '00.000.000/0000-00'];
		            var mask = (cpfcnpj.length > 14) ? masks[1] : masks[0];
		            $('#cpf_cnpj').mask(mask, options);
		        }
		    };
		    $('#cpf_cnpj').mask('000.000.000-000', options);

		    $("#limpar").click(function(){
		    	$("input, textarea").val("");
		    });

		});
	</script>
</head>
<body>

<header>
	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
		     	<div class="logo">
					<a href="https://www.telecontrol.com.br/" rel="home">
						<img class="img_tc" title="Telecontrol" alt="Telecontrol" src="https://www.telecontrol.com.br/images/logo.png">
					</a>
				</div>
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
				<div class="nav-collapse collapse" id="main-menu">
					<ul class="nav pull-right" id="main-menu-right">
						<li><a rel="tooltip" href="https://www.telecontrol.com.br/" title="" onclick="_gaq.push(['_trackEvent', 'click', 'outbound', 'builtwithbootstrap']);" data-original-title="Telecontrol">Home <i class="icon-white icon-share-alt"></i></a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</header>
<section class='conteudo'>
	<?php
	if (count($msg_erro["msg"]) > 0) {
	?>
	<div class="row-fluid">
		<div class="span10 offset1">
		    <div style="margin-top: 80px;" class="alert alert-error">
		    	<button type="button" class="close" data-dismiss="alert">&times;</button>
				<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
		    </div>
		</div>
	</div>
	<?php
	}
	?>
	<div class="row-fluid">
		<div class="span10 offset1">
			<br/><br/>
			
	        <form name='frm_processo' method='POST' action='<?=$PHP_SELF?>' class="well escuro">
	        	<fieldset>
		        	<legend class="control-label" for="input01">Pesquisar Processo</legend>
		        	<div class="inputs">
			        	<input type="text" id="processo" name='processo' value='' class="input-medium" placeholder="N° Processo">
			        	<input type="text" id="cpf_cnpj" name="cpf_cnpj" value='' class="input-large" placeholder="CPF/CNPJ">
			        	<button type="submit" name='btn_pesquisa' class="btn btn-primary">Pesquisar</button>
			        	<button type="button" class="btn btn-info" id="limpar" style="float: right;">Limpar</button>
		        	</div>
	        	</fieldset>
	        </form>

	        <?php foreach ($result as $key => $value) { ?>
	        	<form class="form well">
		        	<fieldset>
						<legend>Informações do Processo</legend>
						<div class="row-fluid">
							<div class="span2 offset1">
								<div class='control-group'>
									<label class="control-label" for="num_processo">N° do Processo</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['processo']?>" />
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label" for="orgao_processo">Orgão do Processo</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['orgao']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label" for="comarca">Comarca</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['comarca']?>">
										</div>
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
								<label class="control-label" for="comarca">Partes Adversas</label>
								<div class="controls controls-row">
									<div class="span12">
										<input type="text" class='span12' price="true" value="<?=$value['partes_adversas']?>">
									</div>
								</div>
							</div>
						</div>
					</fieldset>
		      	</form>

	        	<form class="form well">
		        	<fieldset>
						<legend>Informações do Advogado</legend>
						<div class="row-fluid">
							<div class="span4 offset1">
								<div class='control-group'>
									<label class="control-label">Advogado</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['advogado_nome']?>" />
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Telefone</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['advogado_celular']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span4">
								<div class='control-group' >
									<label class="control-label">Email</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['advogado_email']?>">
										</div>
									</div>
								</div>
							</div>
						</div>
					</fieldset>
		      	</form>

		        <form class="form well">
		        	<fieldset>
						<legend>Informações do Cliente</legend>
						<div class="row-fluid">
							<div class="span4 offset1">
								<div class='control-group'>
									<label class="control-label">Nome</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['consumidor_nome']?>" />
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">CPF</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['consumidor_cpf']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Telefone 1</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['consumidor_fone1']?>">
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Telefone 2</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['consumidor_fone2']?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row-fluid">
							<div class="span4 offset1">
								<div class='control-group'>
									<label class="control-label">E-mail</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['consumidor_email']?>" />
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">CEP</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['consumidor_cep']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Estado</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['estado']?>">
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Cidade</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['cidade']?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row-fluid">
							<div class="span3 offset1">
								<div class='control-group'>
									<label class="control-label">Bairro</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['consumidor_bairro']?>" />
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label">Endereço</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['consumidor_endereco']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Número</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['consumidor_numero']?>">
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Complemento</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['consumidor_complemento']?>">
										</div>
									</div>
								</div>
							</div>
						</div>
					</fieldset>
		      	</form>

		      	<form class="form well">
		        	<fieldset>
						<legend>Informações Detalhadas</legend>
						<div class="row-fluid">
							<div class="span2 offset1">
								<div class='control-group'>
									<label class="control-label">Atendimento</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['hd_chamado']?>" />
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label">Status</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['status']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span5">
								<div class='control-group' >
									<label class="control-label">Atendente</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['atendente']?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row-fluid">
							<div class="span2 offset1">
								<div class='control-group'>
									<label class="control-label">Código Posto</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['codigo_posto']?>" />
									</div>
								</div>
							</div>
							<div class="span5">
								<div class='control-group' >
									<label class="control-label">Nome Posto</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['posto']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label">Ordem Serviço</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['os']?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row-fluid">
							<div class="span3 offset1">
								<div class='control-group'>
									<label class="control-label">Ref. Produto</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['produto']?>" />
									</div>
								</div>
							</div>
							<div class="span7">
								<div class='control-group' >
									<label class="control-label">Descrição</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['descricao']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
						</div>
					</fieldset>
		      	</form>

		      	<form class="form well">
		        	<fieldset>
						<legend>Informações Gerais</legend>
						
						<div class="row-fluid">
							<div class="span10 offset1">
								<div class='control-group'>
									<label class="control-label">Motivo Principal</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['motivo_processo']?>" />
									</div>
								</div>
							</div>
						</div>

						<?php foreach ($result_itens as $key_itens => $value_itens) { ?>
							<div class='row-fluid'><div class='span10 offset1'><br><b><span>Etapa <?=($key_itens+1)?></span></b><hr class='hr' style='line-height:5px'></div></div>
							
							<div class="row-fluid">
								<div class="span4 offset1">
									<div class='control-group' >
										<label class="control-label">Tipo Documento</label>
										<div class="controls controls-row">
											<div class="span12">
												<input class="span12" type="text" value="<?=$value['status']?>" maxlength="100" />
											</div>
										</div>
									</div>
								</div>
								<div class="span2">
									<div class='control-group' >
										<label class="control-label">Data Notificação <i class='icon-calendar'></i></label> 
										<div class="controls controls-row">
											<div class="span12">
												<input type="text" class='span12' value="<?=mostra_data($value_itens['data_notificacao'])?>">
											</div>
										</div>
									</div>
								</div>
								<div class="span2">
									<div class='control-group' >
										<label class="control-label">Data Audiência 1 <i class='icon-calendar'></i></label>
										<div class="controls controls-row">
											<div class="span12">
												<input type="text" class='span12' value="<?=mostra_data_hora($value_itens['data_audiencia1'])?>">
											</div>
										</div>
									</div>
								</div>
								<div class="span2">
									<div class='control-group' >
										<label class="control-label">Data Audiência 2 <i class='icon-calendar'></i></label>
										<div class="controls controls-row">
											<div class="span12">
												<input type="text" class='span12' value="<?=mostra_data_hora($value_itens['data_audiencia2'])?>">
											</div>
										</div>
									</div>
								</div>
							</div>
						
							<div class="row-fluid">
								<div class="span5 offset1">
									<div class='control-group'>
										<label class="control-label">Pedido Cliente</label>
										<div class="controls controls-row">
											<input class="span12" type="text" value="<?=$value_itens['processo_pedido_cliente']?>" />
										</div>
									</div>
								</div>
								<div class="span5">
									<div class='control-group' >
										<label class="control-label">Proposta Acordo</label>
										<div class="controls controls-row">
											<div class="span12">
												<input class="span12" type="text" value="<?=$value_itens['proposta_acordo']?>" maxlength="100" />
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="row-fluid">
								<div class="span3 offset1">
									<div class='control-group'>
										<label class="control-label">Custo Etapa</label>
										<div class="controls controls-row">
											<input class="span12" type="text" value="R$ <?=number_format($value_itens['custo_etapa'], 2, ',', '.');?>" />
										</div>
									</div>
								</div>
								<div class="span2">
									<div class='control-group' >
										<label class="control-label">Data Acordo <i class='icon-calendar'></i></label>
										<div class="controls controls-row">
											<div class="span12">
												<input class="span12" type="text" value="<?=mostra_data($value_itens['descricao'])?>" maxlength="100" />
											</div>
										</div>
									</div>
								</div>
								<div class="span3">
									<div class='control-group' >
										<label class="control-label">Cumprimento Acordo</label>
										<div class="controls controls-row">
											<div class="span12">
												<input class="span12" type="text" value="<?=mostra_data($value_itens['descricao'])?>" maxlength="100" />
											</div>
										</div>
									</div>
								</div>
								<div class="span2">
									<div class='control-group' >
										<label class="control-label">Valor do Acordo</label>
										<div class="controls controls-row">
											<div class="span12">
												<input class="span12" type="text" value="R$ <?=number_format($value_itens['valor_acordo'], 2, ',', '.');?>" maxlength="100" />
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="row-fluid">
								<div class="span10 offset1">
									<div class='control-group'>
										<label class="control-label">Acordo</label>
										<div class="controls controls-row">
											<textarea class="span12" style="height: 92px !important;"><?$value_itens["obs_acordo"]?></textarea>
										</div>
									</div>
								</div>
							</div>					
						<?php } ?>
					</fieldset>
		      	</form>

		      	<form class="form well">
		        	<fieldset>
						<legend>Informações Andamento do Processo</legend>
						<div class="row-fluid">
							<div class="span3 offset1">
								<div class='control-group'>
									<label class="control-label">Status do Processo</label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=$value['status_processo']?>" />
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label">Fase do Processo</label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=$value['fase_processual']?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span4">
								<div class='control-group' >
									<label class="control-label">Houve cumprimento do acordo</label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=$value['houve_acordo']?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row-fluid">
							<div class="span3 offset1">
								<div class='control-group'>
									<label class="control-label">Data do Transito Julgado <i class='icon-calendar'></i></label>
									<div class="controls controls-row">
										<input class="span12" type="text" value="<?=mostra_data_hora($value['data_transito_julgado'])?>" />
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label">Data da Sentença <i class='icon-calendar'></i></label>
									<div class="controls controls-row">
										<div class="span12">
											<input class="span12" type="text" value="<?=mostra_data_hora($value['data_sentenca'])?>" maxlength="100" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Data da Execução <i class='icon-calendar'></i></label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=mostra_data_hora($value['data_execucao'])?>">
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label">Data da Solução <i class='icon-calendar'></i></label>
									<div class="controls controls-row">
										<div class="span12">
											<input type="text" class='span12' value="<?=mostra_data($value['data_solucao'])?>">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row-fluid">
							<div class="span10 offset1">
								<div class='control-group'>
									<label class="control-label">Observação</label>
									<div class="controls controls-row">
										<textarea class="span12" style="height: 92px !important;"><?=$value_itens['observacao']?></textarea>
									</div>
								</div>
							</div>
						</div>
					</fieldset>
		      	</form>
	        <?php } ?>
		</div>
	</div>
</section>
<footer>
	<div class="row-fluid">
		<div class="span12">
			<p class='rodape'>© 2019 Telecontrol. Todos os Direitos Reservados.</p>
		</div>
	</div>
</footer>
</body>
</html>

