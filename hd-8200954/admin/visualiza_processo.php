<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Visualiza de Processos";
$layout_menu = "gerencia";
$admin_privilegios="gerencia";

include 'cabecalho_new.php';

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform"
);

include 'plugin_loader.php';
?>

<script type="text/javascript">

$(function() {
	/**
	 * Carrega o datepicker já com as mascara para os campos
	 */
	//$("#data_nascimento").datepicker({ maxDate: 0, minDate: "-6d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_nascimento").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();

	/**
	 * Configurações do Alphanumeric
	 */
	$(".numeric").numeric();
	$("#funcionario_telefone, #funcionario_celular").numeric({ allow: "()- " });

	/**
	 * Evento que chama a função de lupa para a lupa clicada
	 */
	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	
	/**
	 * Mascaras
	 */
	$("#funcionario_cep").mask("99999-999"); 

	<?php
		if(strlen(getValue('funcionario_cpf')) > 0){
			if(strlen(getValue('funcionario_cpf')) > 14){
	?>
				$("#funcionario_cpf").mask("99.999.999/9999-99");
				$("label[for=funcionario_cpf]").html("CNPJ");
	<?php
			}else{
	?>
				$("#funcionario_cpf").mask("999.999.999-99");
				$("label[for=funcionario_cpf]").html("CPF");
	<?php
			}
	?>
	<?php
		}
	?>

	/**
	 * Evento de keypress do campo funcionario_cpf
	 * Irá verificar o tamanho do campo, se o tamanho já for 14(CPF) irá alterar a máscara para CNPJ e alterar o Label
	 */
	$("#cnpj_cpf").blur(function(){
		var tamanho = $(this).val().replace(/\D/g, '');

			$("#funcionario_cpf").mask("999.999.999-99");
			$("label[for=funcionario_cpf]").html("CPF");
	});

	$("#funcionario_cpf").focus(function(){
		$(this).unmask();
	});

	
	/**
	 * Evento para quando alterar o estado carregar as cidades do estado
	 */
	$("select[id$=_estado]").change(function() {
		busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "funcionario");
	});

	/**
	 * Evento para buscar o endereço do cep digitado
	 */
	$("input[id$=_cep]").blur(function() {
		if ($(this).attr("readonly") == undefined) {
			busca_cep($(this).val(), ($(this).attr("id") == "revenda_cep") ? "revenda" : "funcionario");
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
function busca_cidade(estado, funcionario_revenda, cidade) {
	$("#"+funcionario_revenda+"_cidade").find("option").first().nextAll().remove();

	if (estado.length > 0) {
		$.ajax({
			async: false,
			url: "cadastro_os.php",
			type: "POST",
			data: { ajax_busca_cidade: true, estado: estado },
			beforeSend: function() {
				if ($("#"+funcionario_revenda+"_cidade").next("img").length == 0) {
					$("#"+funcionario_revenda+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
				}
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					$.each(data.cidades, function(key, value) {
						var option = $("<option></option>", { value: value, text: value});

						$("#"+funcionario_revenda+"_cidade").append(option);
					});
				}

				
				$("#"+funcionario_revenda+"_cidade").show().next().remove();
			}
		});
	}

	if(typeof cidade != "undefined" && cidade.length > 0){
		
		$('#funcionario_cidade option[value='+cidade+']').attr('selected','selected');

	}

}

/**
 * Função que faz um ajax para buscar o cep nos correios
 */
function busca_cep(cep, funcionario_revenda) {
	if (cep.length > 0) {
		var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		$.ajax({
			async: false,
			url: "cadastro_os.php",
			type: "POST",
			data: { ajax_busca_cep: true, cep: cep },
			beforeSend: function() {
				$("#"+funcionario_revenda+"_estado").hide().after(img.clone());
				$("#"+funcionario_revenda+"_cidade").hide().after(img.clone());
				$("#"+funcionario_revenda+"_bairro").hide().after(img.clone());
				$("#"+funcionario_revenda+"_endereco").hide().after(img.clone());
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
					$("#"+funcionario_revenda+"_cidade").show().next().remove();
				} else {
					$("#"+funcionario_revenda+"_estado").val(data.uf);

					busca_cidade(data.uf, funcionario_revenda);

					$("#"+funcionario_revenda+"_cidade").val(retiraAcentos(data.cidade).toUpperCase());

					if (data.bairro.length > 0) {
						$("#"+funcionario_revenda+"_bairro").val(data.bairro);
					}
					
					if (data.end.length > 0) {
						$("#"+funcionario_revenda+"_endereco").val(data.end);
					}
				}

				$("#"+funcionario_revenda+"_estado").show().next().remove();
				$("#"+funcionario_revenda+"_bairro").show().next().remove();
				$("#"+funcionario_revenda+"_endereco").show().next().remove();

				if ($("#"+funcionario_revenda+"_bairro").val().length == 0) {
					$("#"+funcionario_revenda+"_bairro").focus();
				} else if ($("#"+funcionario_revenda+"_endereco").val().length == 0) {
					$("#"+funcionario_revenda+"_endereco").focus();
				} else if ($("#"+funcionario_revenda+"_numero").val().length == 0) {
					$("#"+funcionario_revenda+"_numero").focus();
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
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>

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
<br />
<FORM name='frm_pesquisa_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
<div class="titulo_tabela">Consulta</div>
<br>
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="funcionario_nome">Numero do Processo</label>
			<div class="controls controls-row">
				<div class="span12">
					<input id="funcionario_nome" name="funcionario_nome" class="span12" type="text" value="<?=getValue('funcionario_nome')?>" maxlength="100" DISABLED/>
				</div>
			</div>
		</div>
	</div>
	<div class="span7"></div>
	<div class="span1"></div>
</div>
		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span4">
				<div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>' >
					<label class="control-label" for="funcionario_nome">Nome</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_nome" name="funcionario_nome" class="span12" type="text" value="<?=getValue('funcionario_nome')?>" maxlength="100" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('cpf', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_cpf">CPF</label>
					<div class="controls controls-row">
						<div class="span12">
							
							<input id="funcionario_cpf" name="funcionario_cpf" class="span12 numeric" type="text" value="<?=getValue('funcionario_cpf')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class="control-group">
					<label class="control-label" for="funcionario_telefone">Telefone Fixo</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_telefone" class="span12" type="text" value="<?=getValue('funcionario_telefone')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="control-group">
					<label class="control-label" for="funcionario_celular">Telefone Celular</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_celular" class="span12" type="text" value="<?=getValue('funcionario_celular')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>			
			<div class="span1"></div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="funcionario_email">Email</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_email" name="funcionario_email" class="span12" type="text" value="<?=getValue('funcionario_email')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('cep', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_cep">CEP</label>
					<div class="controls controls-row">
						<div class="span12">
							
							<input id="funcionario_cep" name="funcionario_cep" class="span12" type="text" value="<?=getValue('funcionario_cep')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="control-group <?=(in_array('estado', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="funcionario_estado">Estado</label>
					<div class="controls controls-row">
						<div class="span12">
							
								<select id="funcionario_estado" name="funcionario_estado" class="span12" DISABLED>
									<option value="" >Selecione</option>
									<?php
									#O $array_estados() está no arquivo funcoes.php
									foreach ($array_estados() as $sigla => $nome_estado) {
										$selected = ($sigla == getValue('funcionario_estado')) ? "selected" : "";

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
					<label class="control-label" for="funcionario_cidade">Cidade</label>
					<div class="controls controls-row">
						<div class="span12">
							
							<select id="funcionario_cidade" name="funcionario_cidade" class="span12" DISABLED>
								<option value="" >Selecione</option>
								<?php
									if (strlen(getValue("funcionario_estado")) > 0) {
										$sql = "SELECT DISTINCT * FROM (
												SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".getValue("funcionario_estado")."')
													UNION (
														SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".getValue("funcionario_estado")."')
													)
												) AS cidade
												ORDER BY cidade ASC";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) > 0) {
											while ($result = pg_fetch_object($res)) {
												$selected  = (trim($result->cidade) == trim(getValue("funcionario_cidade"))) ? "SELECTED" : "";

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
					<label class="control-label" for="funcionario_bairro">Bairro</label>
					<div class="controls controls-row">
						<div class="span12">
						
							<input id="funcionario_bairro" name="funcionario_bairro" class="span12" type="text" maxlength="80" value="<?=getValue('funcionario_bairro')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group <?=(in_array('endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_endereco">Endereço</label>
					<div class="controls controls-row">
						<div class="span12">
							
							<input id="funcionario_endereco" name="funcionario_endereco" class="span12" type="text" value="<?=getValue('funcionario_endereco')?>" maxlength="80" DISABLED/>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array('numero', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_numero">Número</label>
					<div class="controls controls-row">
						<div class="span12">
							
							<input id="funcionario_numero" name="funcionario_numero" class="span12" type="text" value="<?=getValue('funcionario_numero')?>" maxlength="10" DISABLED/>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="funcionario_complemento">Complemento</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_complemento" name="funcionario_complemento" class="span12" type="text" value="<?=getValue('funcionario_complemento')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>

			<div class="span1"></div>	
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_referencia'>Atendimento</label>
	                <div class='controls controls-row'>
	                    <div class='span12 input-append'>
	                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" DISABLED/>
	                    </div>
	                </div>
	            </div>
        	</div>
        	<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="funcionario_complemento">Status</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_complemento" name="funcionario_complemento" class="span12" type="text" value="<?=getValue('funcionario_complemento')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span5">
				<div class='control-group' >
					<label class="control-label" for="funcionario_complemento">Atendente</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_complemento" name="funcionario_complemento" class="span12" type="text" value="<?=getValue('funcionario_complemento')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>	
		</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span12 input-append'>
	                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' value="<? echo $produto_referencia ?>" DISABLED/>
	                    </div>
	                </div>
	            </div>
        	</div>
	        <div class='span4'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span12 input-append'>
	                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" DISABLED/>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class="span3">
				<div class="control-group">
					<label class="control-label" for="funcionario_celular">Nº Série</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_celular" class="span12" type="text" value="<?=getValue('funcionario_celular')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>	    
			<div class="span1"></div>	
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>			
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="funcionario_celular">Ordem de Serviço</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_celular" class="span12" type="text" value="<?=getValue('funcionario_celular')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>		
			<div class="span1"></div>
		</div>
	    <div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="funcionario_celular">Motivo Principal</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="funcionario_estado" name="funcionario_estado" class="span12" DISABLED>
								<option value="" >Selecione</option>
								<?php
								#O $array_estados() está no arquivo funcoes.php
								foreach ($array_estados() as $sigla => $nome_estado) {
									$selected = ($sigla == getValue('funcionario_estado')) ? "selected" : "";
									echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>	
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_nascimento">Data Notificação</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_nascimento" name="data_nascimento" class="span12" type="text" value="<?=getValue('data_nascimento')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_nascimento">Data Audiência 1</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_nascimento" name="data_nascimento" class="span12" type="text" value="<?=getValue('data_nascimento')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_nascimento">Data Audiência 2</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_nascimento" name="data_nascimento" class="span12" type="text" value="<?=getValue('data_nascimento')?>" DISABLED/>
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
					<label class="control-label" for="funcionario_celular">Solução</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_celular" class="span12" type="text" value="<?=getValue('funcionario_celular')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_nascimento">Data Solução</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_nascimento" name="data_nascimento" class="span12" type="text" value="<?=getValue('data_nascimento')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>	
		</div>
		<div class="row-fluid">
			<div class="span1"></div>	
			<div class="span4">
				<div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>' >
					<label class="control-label" for="funcionario_nome">Nome Advogado</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_nome" name="funcionario_nome" class="span12" type="text" value="<?=getValue('funcionario_nome')?>" maxlength="100" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class="control-group">
					<label class="control-label" for="funcionario_celular">Telefone Celular</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_celular" class="span12" type="text" value="<?=getValue('funcionario_celular')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="funcionario_email">E-mail</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_email" name="funcionario_email" class="span12" type="text" value="<?=getValue('funcionario_email')?>" DISABLED/>
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
					<label class="control-label" for="funcionario_email">Valor Cliente</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_email" name="funcionario_email" class="span12" type="text" value="<?=getValue('funcionario_email')?>" DISABLED/>
						</div>
					</div>
				</div>
			</div>
			<div class="span3">
				<div class='control-group' >
					<label class="control-label" for="funcionario_email">Custo Advogado</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_email" name="funcionario_email" class="span12" type="text" value="<?=getValue('funcionario_email')?>" DISABLED/>
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
					<label class="control-label" for="funcionario_email">Observações</label>
					<div class="controls controls-row">
						<div class="span12">
							<textarea id="observacao" name="observacao" class="span12" style="height: 50px;" maxlength="100" DISABLED><?=getValue("observacao")?></textarea>
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
