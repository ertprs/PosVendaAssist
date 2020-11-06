<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Cadastro de Processos";
$layout_menu = "gerencia";
$admin_privilegios="gerencia";
  // echo "<pre>";
  // print_r($_GET);
  // echo "</pre>";

if (!empty($_GET["num_processo"])) {
	
	$num_processo = $_GET['num_processo'];

	$sql_se = "SELECT 	orgao,
						consumidor_nome,
						consumidor_cpf,
						consumidor_fone,
						consumidor_email,
						consumidor_cep,
						consumidor_bairro,
						consumidor_endereco,
						consumidor_numero,
						consumidor_complemento,
						cidade,
						produto,
						motivo_processo,
						data_notificacao,
						data_audiencia1,
						data_audiencia2,
						solucao,
						data_solucao,
						advogado_nome,
						advogado_celular,
						advogado_email,
						valor_cliente,
						custo_advogado,
						historico
				FROM tmp_processo_suggar
				WHERE processo = $num_processo
				AND fabrica = $login_fabrica;";

	$res_se = pg_query($con,$sql_se);
// echo $sql_se;
	if(pg_last_error($con)){
		$msg_erro["msg"][] = pg_last_error($con);
	}elseif (pg_num_rows($res_se) > 0) {
					
			$orgao_processo 		= pg_fetch_result($res_se, 0, 'orgao');
			$cli_nome 				= pg_fetch_result($res_se, 0, 'consumidor_nome');
			$consumidor_cpf			= pg_fetch_result($res_se, 0, 'consumidor_cpf');
			$cli_tel_fix			= pg_fetch_result($res_se, 0, 'consumidor_fone');
			$cli_email				= pg_fetch_result($res_se, 0, 'consumidor_email');
			$cli_cep				= pg_fetch_result($res_se, 0, 'consumidor_cep');
			$cli_bairro				= pg_fetch_result($res_se, 0, 'consumidor_bairro');
			$cli_endereco 			= pg_fetch_result($res_se, 0, 'consumidor_endereco');
			$cli_numero		 		= pg_fetch_result($res_se, 0, 'consumidor_numero');
			$cli_end_complemento 	= pg_fetch_result($res_se, 0, 'consumidor_complemento');
			$cod_ibge 				= pg_fetch_result($res_se, 0, 'cidade');
			$produto 				= pg_fetch_result($res_se, 0, 'produto');
			$motivo_principal 		= pg_fetch_result($res_se, 0, 'motivo_processo');
			$data_notificacao 		= pg_fetch_result($res_se, 0, 'data_notificacao');
			$data_audiencia 		= pg_fetch_result($res_se, 0, 'data_audiencia1');
			$data_audiencia2  		= pg_fetch_result($res_se, 0, 'data_audiencia2');
			$solucao_audiencia 		= pg_fetch_result($res_se, 0, 'solucao');
			$data_solucao 			= pg_fetch_result($res_se, 0, 'data_solucao');
			$nome_adv		= pg_fetch_result($res_se, 0, 'advogado_nome');
			$adv_Tel_cel	= pg_fetch_result($res_se, 0, 'advogado_celular');
			$adv_mail 		= pg_fetch_result($res_se, 0, 'advogado_email');
			$valor_cliente 	= pg_fetch_result($res_se, 0, 'valor_cliente');
			$custo_adv 		= pg_fetch_result($res_se, 0, 'custo_advogado');
			$observacao 	= pg_fetch_result($res_se, 0, 'historico');
	}
		
}
/**
 * Area para colocar os AJAX
 */

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
	$estado = strtoupper($_POST["estado"]);

	if (array_key_exists($estado, $array_estados())) {
		$sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
					UNION (
						SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
					)
				) AS cidade
				ORDER BY cidade ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$array_cidades = array();

			while ($result = pg_fetch_object($res)) {
				$array_cidades[] = $result->cidade;
			}

			$retorno = array("cidades" => $array_cidades);
		} else {
			$retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
		}
	} else {
		$retorno = array("error" => utf8_encode("estado não encontrado"));
	}

	exit(json_encode($retorno));
}

if(isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])){
	require_once __DIR__.'/../classes/cep.php';

	$cep = $_POST['cep'];

	try {
		$retorno = CEP::consulta($cep);
		$retorno = array_map(utf8_encode, $retorno);
	} catch(Exception $e) {
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}



include 'cabecalho_new.php';

$plugins = array(
   	"datepicker",
   	"shadowbox",
   	"maskedinput",
   	"alphanumeric",
   	"ajaxform",   
	"price_format"
);

include 'plugin_loader.php';
?>
<script type="text/javascript">

$(function() {
	/**
	 * Carrega o datepicker já com as mascara para os campos
	 */
	//$("#data_nascimento").datepicker({ maxDate: 0, minDate: "-6d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_audiencia").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_notificacao").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_audiencia2").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_solucao").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");


	

	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();

	/**
	 * Configurações do Alphanumeric
	 */
	$(".numeric").numeric();
	$("#cli_telefone, #cli_celular").numeric({ allow: "()- " });

	/**
	 * Evento que chama a função de lupa para a lupa clicada
	 */
	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	
	/**
	 * Mascaras
	 */
	$("#cli_cep").mask("99999-999");
	$("#cli_cep").mask("99999-999"); 

	

	<?php
		if(strlen(getValue('consumidor_cpf')) > 0){
			if(strlen(getValue('consumidor_cpf')) > 14){
	?>
				$("#consumidor_cpf").mask("99.999.999/9999-99");
				$("label[for=consumidor_cpf]").html("CNPJ");
	<?php
			}else{
	?>
				$("#consumidor_cpf").mask("999.999.999-99");
				$("label[for=consumidor_cpf]").html("CPF");
	<?php
			}
	?>
	<?php
		}
	?>
	/**
	 * Evento de keypress do campo consumidor_cpf
	 * Irá verificar o tamanho do campo, se o tamanho já for 14(CPF) irá alterar a máscara para CNPJ e alterar o Label
	 */
	$("#consumidor_cpf").blur(function(){
		var tamanho = $(this).val().replace(/\D/g, '');

		if(tamanho.length > 11){
			$("#consumidor_cpf").mask("99.999.999/9999-99");
			$("label[for=consumidor_cpf]").html("CNPJ");
		}else{
			$("#consumidor_cpf").mask("999.999.999-99");
			$("label[for=consumidor_cpf]").html("CPF");
		}
	});

	$("#consumidor_cpf").focus(function(){
		$(this).unmask();
	});

	/**
	 * Evento para quando alterar o estado carregar as cidades do estado
	 */
	$("select[id$=_estado]").change(function() {
		busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "cli");
	});

	/**
	 * Evento para buscar o endereço do cep digitado
	 */
	$("input[id$=_cep]").blur(function() {
		if ($(this).attr("readonly") == undefined) {
			busca_cep($(this).val(), ($(this).attr("id") == "revenda_cep") ? "revenda" : "cli");
		}
	});

});

/**
 * Função para retirar a acentuação
 */
function retiraAcentos(palavra){
	var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
	var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
    	if (com_acento.search(palavra.substr(i, 1)) >= 0) {
      		newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
      	} else {
       		newPalavra += palavra.substr(i, 1);
    	}
    }

    return newPalavra.toUpperCase();
}


function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function retorna_produto (retorno) {
	$("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function retorna_processo(retorno) {
	
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
}

/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, cli_revenda, cidade) {
	$("#"+cli_revenda+"_cidade").find("option").first().nextAll().remove();

	if (estado.length > 0) {
		$.ajax({
			async: false,
			//url: "cadastro_processos.php",
			url: "cadastro_processos.php",
			type: "POST",
			data: { ajax_busca_cidade: true, estado: estado },
			beforeSend: function() {
				if ($("#"+cli_revenda+"_cidade").next("img").length == 0) {
					$("#"+cli_revenda+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
				}
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					$.each(data.cidades, function(key, value) {
						var option = $("<option></option>", { value: value, text: value});

						$("#"+cli_revenda+"_cidade").append(option);
					});
				}

				
				$("#"+cli_revenda+"_cidade").show().next().remove();
			}
		});
	}

	if(typeof cidade != "undefined" && cidade.length > 0){
		
		$('#cli_cidade option[value='+cidade+']').attr('selected','selected');

	}

}

/**
 * Função que faz um ajax para buscar o cep nos correios
 */
function busca_cep(cep, cli_revenda) {
	if (cep.length > 0) {
		var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		$.ajax({
			async: false,
			//url: "cadastro_processos.php",
			url: "cadastro_processos.php",
			type: "POST",
			data: { ajax_busca_cep: true, cep: cep },
			beforeSend: function() {
				$("#"+cli_revenda+"_estado").hide().after(img.clone());
				$("#"+cli_revenda+"_cidade").hide().after(img.clone());
				$("#"+cli_revenda+"_bairro").hide().after(img.clone());
				$("#"+cli_revenda+"_endereco").hide().after(img.clone());
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
					$("#"+cli_revenda+"_cidade").show().next().remove();
				} else {
					$("#"+cli_revenda+"_estado").val(data.uf);

					busca_cidade(data.uf, cli_revenda);

					$("#"+cli_revenda+"_cidade").val(retiraAcentos(data.cidade).toUpperCase());

					if (data.bairro.length > 0) {
						$("#"+cli_revenda+"_bairro").val(data.bairro);
					}
					
					if (data.end.length > 0) {
						$("#"+cli_revenda+"_endereco").val(data.end);
					}
				}

				$("#"+cli_revenda+"_estado").show().next().remove();
				$("#"+cli_revenda+"_bairro").show().next().remove();
				$("#"+cli_revenda+"_endereco").show().next().remove();

				if ($("#"+cli_revenda+"_bairro").val().length == 0) {
					$("#"+cli_revenda+"_bairro").focus();
				} else if ($("#"+cli_revenda+"_endereco").val().length == 0) {
					$("#"+cli_revenda+"_endereco").focus();
				} else if ($("#"+cli_revenda+"_numero").val().length == 0) {
					$("#"+cli_revenda+"_numero").focus();
				}
			}
		});
	}
}

</script>



<?php
if (count($msg_erro["msg"]) > 0) {
?>
<br />
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>

<?php
if (strlen($_GET['msg']) > 0) {
	$msg = "Funcionário cadastrado com sucesso";

?>
<br />
    <div class="alert alert-success">
		<h4> <? echo $msg;?></h4>
    </div>
<?php
}

?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_pesquisa_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
<div class="titulo_tabela">Consulta</div>
<br>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span3'>
			<div class='control-group'>
				<label class='control-label'>Nome</label>
				<div class='controls controls-row'>					
					<div class='span10 input-append'>
						<input type="text" name="nome_consulta" id="nome_consulta" size="12" maxlength="10" class='span12' value= "<?=$nome_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class="span3">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>CPF</label>
				<div class='controls controls-row'>					
					<div class='span10 input-append'>
						<input type="text" name="cpf_consulta" id="cpf_consulta" size="12" maxlength="11" class='span12' value= "<?=$cpf_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="cpf" />
					</div>
				</div>	
   			</div>
   		</div>
		<div class="span4">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>CNPJ</label>
				<div class='controls controls-row'>					
					<div class='span10 input-append'>
						<input type="text" name="nome_consulta" id="nome_consulta" size="12" maxlength="14" class='span12' value= "<?=$nome_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="cnpj" />
					</div>
				</div>	
   			</div>
   		</div>
   		<div class="span1"></div>
   	</div> 
   	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>CEP</label>
				<div class='controls controls-row'>					
					<div class='span10 input-append'>
						<input type="text" name="nome_consulta" id="nome_consulta" size="12" maxlength="10" class='span12' value= "<?=$nome_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="cep" />
					</div>
				</div>	
   			</div>
   		</div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Ordem de Serviço</label>
				<div class='controls controls-row'>					
					<div class='span10 input-append'>
						<input type="text" name="nome_consulta" id="nome_consulta" size="12" maxlength="10" class='span12' value= "<?=$nome_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="os" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Telefone</label>
				<div class='controls controls-row'>					
					<div class='span10 input-append'>
						<input type="text" name="nome_consulta" id="nome_consulta" size="12" maxlength="10" class='span12' value= "<?=$nome_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="telefone" />
					</div>
				</div>	
   			</div>
   		</div>
		<div class="span4">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Atendimento</label>
				<div class='controls controls-row'>					
					<div class='span10 input-append'>
						<input type="text" name="nome_consulta" id="nome_consulta" size="12" maxlength="10" class='span12' value= "<?=$nome_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="atendimento" />
					</div>
				</div>	
   			</div>
   		</div>
   		<div class="span1"></div>
   	</div> 
	<br />
	<br />
<div class="titulo_tabela">Cadastro de Processo</div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group <?=(in_array("num_processo", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="num_processo">Numero do Processo</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico'>*</h5>
					<input id="num_processo" name="num_processo" class="span12" type="text" value='<?echo $num_processo?>' maxlength="100" />
				</div>
			</div>
		</div>
	</div>
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group <?=(in_array("orgao_processo", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="orgao_processo">Orgão do Processo</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico'>*</h5>
					<?
					$array_orgao = array(	"1"  => "Juizado",
						"2"  => "Procon");
					?>
					<select id="orgao_processo" name="orgao_processo" class="span12">
						<option value="">Selecione</option>
						<?
						foreach ($array_orgao as $sigla => $nome_orgao) {
							$selected = ($sigla == $orgao_processo) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected} >{$nome_orgao}</option>";
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="span3"></div>
	<div class="span1"></div>
</div>
<br />
<div class="titulo_tabela">Informações do Cliente</div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span4">
		<div class='control-group <?=(in_array("cli_nome", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="cli_nome">Nome</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico'>*</h5>
					<input id="cli_nome" name="cli_nome" class="span12" type="text" value="<?echo $cli_nome?>" maxlength="100" />
				</div>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class='control-group <?=(in_array('consumidor_cpf', $msg_erro['campos'])) ? "error" : "" ?>' >
			<label class="control-label" for="consumidor_cpf">CPF</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico'>*</h5>
					<input id="consumidor_cpf" name="consumidor_cpf" class="span12 numeric" type="text" value="<? echo $consumidor_cpf?>" <?=$readonly?> />
				</div>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class='control-group <?=(in_array('cli_tel_fix', $msg_erro['campos'])) ? "error" : "" ?>'>
			<label class="control-label" for="cli_tel_fix">Telefone Fixo</label>
			<div class="controls controls-row">
				<div class="span12">					
					<h5 class='asteristico'>*</h5>
					<input id="cli_tel_fix" name="cli_tel_fix" class="span12" type="text" value="<? echo $cli_tel_fix?>" />
				</div>
			</div>
		</div>
	</div>

	<div class="span2">
		<div class='control-group <?=(in_array('cli_tel_cel', $msg_erro['campos'])) ? "error" : "" ?>'>
			<label class="control-label" for="cli_tel_cel">Telefone Celular</label>
			<div class="controls controls-row">
				<div class="span12">					
					<h5 class='asteristico'>*</h5>
					<input id="cli_tel_cel" name="cli_tel_cel" class="span12" type="text" value="<? echo $cli_tel_cel?>" />
				</div>
			</div>
		</div>
	</div>			
	<div class="span1"></div>
</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class='control-group <?=(in_array('cli_email', $msg_erro['campos'])) ? "error" : "" ?>'>
					<label class="control-label" for="cli_email">Email</label>
					<div class="controls controls-row">
						<div class="span12">				
							<h5 class='asteristico'>*</h5>
							<input id="cli_email" name="cli_email" class="span12" type="text" value="<? echo $cli_email?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('cep', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_cep">CEP</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="cli_cep" name="cli_cep" class="span12" type="text" value="<? echo $cli_cep?>"/>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="control-group <?=(in_array('estado', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="cli_estado">Estado</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
								<select id="cli_estado" name="cli_estado" class="span12">
									<option value="" >Selecione</option>
									<?php
									#O $array_estados() está no arquivo funcoes.php
									foreach ($array_estados() as $sigla => $nome_estado) {
										$selected = ($sigla == $cli_estado) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
									}
									?>
								</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="control-group <?=(in_array('cidade', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="cli_cidade">Cidade</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<select id="cli_cidade" name="cli_cidade" class="span12">
								<option value="" >Selecione</option>
								<?php
									if (strlen($cli_estado) > 0) {
										$sql = "SELECT DISTINCT * FROM (
												SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('$cli_estado')
													UNION (
														SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('$cli_estado')
													)
												) AS cidade
												ORDER BY cidade ASC";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) > 0) {
											while ($result = pg_fetch_object($res)) {
												$selected  = (trim($result->cidade) == trim($cli_cidade)) ? "SELECTED" : "";

												echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
											}
										}
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span1"></div>

		</div>

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span3">
				<div class='control-group <?=(in_array('bairro', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_bairro">Bairro</label>
					<div class="controls controls-row">
						<div class="span12">
						<h5 class='asteristico'>*</h5>
							<input id="cli_bairro" name="cli_bairro" class="span12" type="text" maxlength="80" value="<? echo $cli_bairro?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group <?=(in_array('endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_endereco">Endereço</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="cli_endereco" name="cli_endereco" class="span12" type="text" value="<? echo $cli_endereco ?>" maxlength="80" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array('cli_numero', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_numero">Número</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="cli_numero" name="cli_numero" class="span12" type="text" value="<? echo $cli_numero?>" maxlength="10" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="cli_end_complemento">Complemento</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="cli_end_complemento" name="cli_end_complemento" class="span12" type="text" value="<? echo $cli_end_complemento?>" maxlength="40" />
						</div>
					</div>
				</div>
			</div>

			<div class="span1"></div>	
		</div>
		<br />
		<div class="titulo_tabela">Informações do Atendimeto</div>
		<br />
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
	            <div class='control-group <?=(in_array("chamado_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='chamado_referencia'>Atendimento</label>
	                <div class='controls controls-row'>
	                    <div class='span10 input-append'>
	                        <input type="text" id="chamado_referencia" name="chamado_referencia" class='span12' maxlength="20" value="<? echo $chamado_referencia ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
	                        <input type="hidden" name="lupa_config" tipo="chamado" parametro="numero" />
	                    </div>
	                </div>
	            </div>
        	</div>
        	<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="status_chamado">Status</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="status_chamado" name="status_chamado" class="span12" type="text" value="<? echo $status_chamado?>" maxlength="40" />
						</div>
					</div>
				</div>
			</div>
			<div class="span5">
				<div class='control-group' >
					<label class="control-label" for="chamado_atendente">Atendente</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="chamado_atendente" name="chamado_atendente" class="span12" type="text" value="<? echo $chamado_atendente?>" maxlength="40" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>	
		</div>

		<br />
		<div class="titulo_tabela">Informações do Produto</div>
		<br />
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span10 input-append'>
	                    	<?
	                    	$sql_prod = "SELECT referencia,descricao FROM tbl_produto WHERE produto = $produto AND fabrica_i = $login_fabrica;";
	                    	$res_prod = pg_query($con,$sql_prod);
	                    	echo $sql_prod;
	                    	$produto_referencia = pg_fetch_result($res_prod, 0, 'referencia');
	                    	?>
	                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
	                    </div>
	                </div>
	            </div>
        	</div>
	        <div class='span4'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span11 input-append'>
	                    	<?$produto_descricao = pg_fetch_result($res_prod, 0, 'descricao');?>
	                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class="span3">
				<div class="control-group">
					<label class="control-label" for="ns_produto">Nº Série</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="ns_produto" name="ns_produto" class="span12" type="text" value="<? echo $ns_produto?>" />
						</div>
					</div>
				</div>
			</div>	    
			<div class="span1"></div>	
		</div>

		<br />
		<div class="titulo_tabela">Informações do Posto</div>
		<br />
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>			
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="os_posto">Ordem de Serviço</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="os_posto" name="os_posto" class="span12" type="text" value="<? echo $os_posto?>" />
						</div>
					</div>
				</div>
			</div>		
			<div class="span1"></div>
		</div>

		<br />
		<div class="titulo_tabela">Informações Gerais</div>
		<br />
	    <div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class='control-group <?=(in_array("motivo_principal", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="motivo_principal">Motivo Principal</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="motivo_principal" name="motivo_principal" class="span12">
								<option value="" >Selecione</option>
								<?php
								$sql_mot = "SELECT motivo_processo,descricao FROM tmp_motivo_processo_suggar WHERE fabrica = $login_fabrica";
								$res_mot = pg_query($con,$sql_mot);
								if(pg_num_rows($res_mot)>0){
									while ($result = pg_fetch_object($res_mot)) {
												$selected  = (trim($result->motivo_processo) == trim($motivo_principal)) ? "SELECTED" : "";

												echo "<option value='{$result->motivo_processo}' {$selected} >{$result->descricao} </option>";
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
					<label class="control-label" for="data_notificacao">Data Notificação</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_notificacao" name="data_notificacao" class="span12" type="text" value="<? echo $data_notificacao?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_audiencia">Data Audiência 1</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_audiencia" name="data_audiencia" class="span12" type="text" value="<? echo $data_audiencia?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_audiencia2">Data Audiência 2</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_audiencia2" name="data_audiencia2" class="span12" type="text" value="<? echo $data_audiencia2?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>	
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span8">
				<div class="control-group">
					<label class="control-label" for="solucao_audiencia">Solução</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="solucao_audiencia" name="solucao_audiencia" class="span12" type="text" value="<? echo $solucao_audiencia?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_solucao">Data Solução</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_solucao" name="data_solucao" class="span12" type="text" value="<? echo $data_solucao?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>	
		</div>
		<div class="row-fluid">
			<div class="span1"></div>	
			<div class="span4">
				<div class='control-group <?=(in_array("nome_adv", $msg_erro["campos"])) ? "error" : ""?>' >
					<label class="control-label" for="nome_adv">Nome Advogado</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="nome_adv" name="nome_adv" class="span12" type="text" value="<? echo $nome_adv?>" maxlength="100" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class="control-group">
					<label class="control-label" for="adv_Tel_cel">Telefone Celular</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="adv_Tel_cel" name="adv_Tel_cel" class="span12" type="text" value="<? echo $adv_Tel_cel?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group  <?=(in_array('adv_mail', $msg_erro['campos'])) ? "error" : "" ?>" >
					<label class="control-label" for="adv_mail">E-mail</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="adv_mail" name="adv_mail" class="span12" type="text" value="<? echo $adv_mail?>" />
						</div>
					</div>
				</div>
			</div>			
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span3">
				<div class='control-group' >
					<label class="control-label" for="valor_cliente">Valor Cliente</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="valor_cliente" name="valor_cliente" price="true" class="span12" type="text" value="<? echo priceFormat( $valor_cliente);?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span3">
				<div class='control-group' >
					<label class="control-label" for="custo_adv">Custo Advogado</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="custo_adv" name="custo_adv" price="true" class="span12" type="text" value="<? echo priceFormat($custo_adv);?>" /> 
						</div>
					</div>
				</div>
			</div>			
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span10">
				<div class='control-group' >
					<label class="control-label" for="observacao">Observações</label>
					<div class="controls controls-row">
						<div class="span12">
							<textarea id="observacao" name="observacao" class="span12" style="height: 50px;" maxlength="100"><? echo $observacao?></textarea>
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>

	<br />
</FORM>
<br /> 

<?php

include "rodape.php";

?>
