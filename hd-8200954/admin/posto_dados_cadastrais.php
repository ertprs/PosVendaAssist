<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$title = "Alteração de Dados Cadastrais do Posto";
$cabecalho = "Alteração de Dados Cadastrais do Posto";
$layout_menu = "cadastro";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_GET["posto"]<>"") {
	$posto = $_GET["posto"];

	$sql_get = "SELECT  tbl_posto_fabrica.posto,
						tbl_posto_fabrica.contato_cep,
						tbl_posto_fabrica.contato_estado,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_bairro,
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero,
						tbl_posto_fabrica.contato_complemento,
						tbl_posto.nome,
						tbl_posto.ie,
						tbl_posto.cnpj
				FROM tbl_posto_fabrica
					JOIN tbl_posto USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND tbl_posto_fabrica.posto = {$posto};";

	$res_get = pg_query($con,$sql_get);

	$_RESULT["posto"]					= pg_fetch_result($res_get,0, 'posto');
	$_RESULT["contato_cep"]				= pg_fetch_result($res_get,0, 'contato_cep');
	$_RESULT["contato_estado"]	        = pg_fetch_result($res_get,0, 'contato_estado');
	$_RESULT["contato_cidade"]			= pg_fetch_result($res_get,0, 'contato_cidade');
	$_RESULT["contato_bairro"]			= pg_fetch_result($res_get,0, 'contato_bairro');
	$_RESULT["contato_endereco"]		= pg_fetch_result($res_get,0, 'contato_endereco');
	$_RESULT["contato_numero"]			= pg_fetch_result($res_get,0, 'contato_numero');
	$_RESULT["contato_complemento"]		= pg_fetch_result($res_get,0, 'contato_complemento');
	$_RESULT["nome"]				    = pg_fetch_result($res_get,0, 'nome');
	$_RESULT["ie"]						= pg_fetch_result($res_get,0, 'ie');
	$_RESULT["cnpj"]					= pg_fetch_result($res_get,0, 'cnpj');

	//Pega o ibge da cidade
	$sql_ibge = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('".getValue("contato_cidade")."')) AND UPPER(estado) = UPPER('".getValue("contato_estado")."')";
	$res_ibge = pg_query($con, $sql_ibge);
	
	if (pg_num_rows($res_ibge) > 0) {
		$_RESULT["addressIbge"] = pg_fetch_result($res_ibge, 0, cod_ibge);
	}	
}

if ($_POST["solicitar"] == "Solicitar") {

	$posto 					= trim($_POST["posto"]);
	$contato_cep 			= preg_replace("/[\.\-\/]/", "",trim($_POST["contato_cep"]));
	$contato_estado 		= trim($_POST["contato_estado"]);
	$contato_cidade 		= trim($_POST["contato_cidade"]);
	// $addressIbge 			= trim($_POST["addressIbge"]);
	// $contato_bairro 		= trim($_POST["contato_bairro"]);
	// $contato_endereco 		= trim($_POST["contato_endereco"]);
	// $contato_numero 		= trim($_POST["contato_numero"]);
	// $contato_complemento 	= trim($_POST["contato_complemento"]);
	$nome 					= trim($_POST["nome"]);
	$ie 					= trim($_POST["ie"]);
	$cnpj 					= preg_replace("/[\.\-\/]/", "",trim($_POST["cnpj"]));
	// $observacao 			= trim($_POST["observacao"]);
	if ($posto == 6359) {
		$msg_erro["msg"][] = "Alteração não autorizada para o posto: ".$nome."!";
	}

	//Validações campos vazios
	if (!strlen($ie)) {
        $msg_erro["campos"][] = "ie";
    }
    if (!strlen($cnpj)) {
        $msg_erro["campos"][] = "cnpj";
    }
	if (!strlen($nome)) {
        $msg_erro["campos"][] = "nome";
    }
	if (!strlen($contato_cep)) {
        $msg_erro["campos"][] = "contato_cep";
    }
	if (!strlen($contato_estado)) {
        $msg_erro["campos"][] = "contato_estado";
    }
	if (!strlen($contato_cidade)) {
        $msg_erro["campos"][] = "contato_cidade";
    }
	// if (!strlen($contato_bairro)) {
    //     $msg_erro["campos"][] = "contato_bairro";
    // }
	// if (!strlen($contato_endereco)) {
    //     $msg_erro["campos"][] = "contato_endereco";
    // }
	// if (!strlen($contato_numero)) {
    //     $msg_erro["campos"][] = "contato_numero";
    // }    
    // Fim validação campos Vazios


    if (count($msg_erro["campos"]) > 0) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
	}

	$sql_verifica_solicitacao = "SELECT conferencia_cadastro FROM tbl_conferencia_cadastro WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND data_alterado ISNULL";
	$res_verifica_solicitacao = pg_query($con, $sql_verifica_solicitacao);

	if(pg_num_rows($res_verifica_solicitacao) > 0){
		$msg_erro["msg"][] = "Já existe uma solicitação de alteração pendente para este posto, favor aguardar!";
	}

	if (count($msg_erro["msg"]) == 0) {

	    $sql_ant = "SELECT 	tbl_posto_fabrica.posto,
							tbl_posto_fabrica.contato_cep,
							tbl_posto_fabrica.contato_estado,
							tbl_posto_fabrica.contato_cidade,
							tbl_posto_fabrica.contato_bairro,
							tbl_posto_fabrica.contato_endereco,
							tbl_posto_fabrica.contato_numero,
							tbl_posto_fabrica.contato_complemento,
							tbl_posto.nome,
							tbl_posto.ie,
							tbl_posto.cnpj
	    				FROM tbl_posto JOIN tbl_posto_fabrica USING(posto)
	    				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
	    				AND tbl_posto_fabrica.posto = $posto; ";
	    $res_ant = pg_query($con,$sql_ant);

	    if (pg_num_rows($res_ant) > 0) {
	    	$posto_ant 		 = trim(pg_fetch_result($res_ant,0, 'posto'));
			$cep_ant 		 = trim(pg_fetch_result($res_ant,0, 'contato_cep'));
			$estado_ant 	 = trim(pg_fetch_result($res_ant,0, 'contato_estado'));
			$cidade_ant 	 = trim(pg_fetch_result($res_ant,0, 'contato_cidade'));
			//$bairro_ant 	 = trim(pg_fetch_result($res_ant,0, 'contato_bairro'));
			//$endereco_ant 	 = trim(pg_fetch_result($res_ant,0, 'contato_endereco'));
			//$numero_ant 	 = trim(pg_fetch_result($res_ant,0, 'contato_numero'));
			//$complemento_ant = trim(pg_fetch_result($res_ant,0, 'contato_complemento'));
			$nome_ant 		 = trim(pg_fetch_result($res_ant,0, 'nome'));
			$ie_ant 		 = trim(pg_fetch_result($res_ant,0, 'ie'));
			$cnpj_ant 		 = trim(pg_fetch_result($res_ant,0, 'cnpj'));

			if ($posto_ant != $posto) {
				$msg_erro["msg"][] = "Código do Posto diferente.";
			}

	    } else {
	    	$msg_erro["msg"][] = "Posto não cadastrado no Telecontrol.";
	    }

		//if(strtoupper($contato_cidade) <> strtoupper($cidade_ant)) {
	    if (pg_query($con,"SELECT CASE WHEN UPPER(fn_retira_especiais('{$contato_cidade}')) != UPPER(fn_retira_especiais('{$cidade_ant}')) THEN true ELSE false END;")) {
	    	
			if (strlen($addressIbge)) {
				$msg_erro["msg"][] = "A cidade possui código de IBGE, favor realizar a alteração na tela: CADASTRO DE POSTOS AUTORIZADOS!";
			} else {
				//Pega o ibge da cidade
				$sql_ibge = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('".getValue("contato_cidade")."')) AND UPPER(estado) = UPPER('".getValue("contato_estado")."')";
				$res_ibge = pg_query($con, $sql_ibge);

				if (pg_num_rows($res_ibge) > 0) {
					$msg_erro["msg"][] = "A cidade possui código de IBGE, favor realizar a alteração na tela: CADASTRO DE POSTOS AUTORIZADOS!";
				}
			}
		}
	    //echo nl2br($sql_ant);exit;
			
		if (!count($msg_erro["msg"]) > 0) {
			$alterar = FALSE;

			if ($cep_ant != $contato_cep) {
				$add_cep = "'{$cep_ant}'";
				$add_cep_new = "'{$contato_cep}'";
				$alterar = TRUE;
			} else {
				$add_cep = 'null';
				$add_cep_new = 'null';
			}

			if ($estado_ant != $contato_estado) {
				$add_estado = "'{$estado_ant}'";
				$add_estado_new = "'{$contato_estado}'";
				$alterar = TRUE;
			} else {
				$add_estado = 'null';
				$add_estado_new = 'null';
			}

			//if(strtoupper($contato_cidade) <> strtoupper($cidade_ant)) {
			if (pg_query($con,"SELECT CASE WHEN UPPER(fn_retira_especiais('{$contato_cidade}')) != UPPER(fn_retira_especiais('{$cidade_ant}')) THEN true ELSE false END;")) {
				$add_cidade = "'{$cidade_ant}'";
				$add_cidade_new = "'{$contato_cidade}'";
				$alterar = TRUE;
			} else {
				$add_cidade = 'null';
				$add_cidade_new = 'null';
			}

			if ($nome_ant != $nome) {
				$add_nome = "'{$nome_ant}'";
				$add_nome_new = "'{$nome}'";
				$alterar = TRUE;
			} else {
				$add_nome = 'null';
				$add_nome_new = 'null';
			}

			if ($ie_ant != $ie) {
				$add_ie = "'{$ie_ant}'";
				$add_ie_new = "'{$ie}'";
				$alterar = TRUE;
			} else {
				$add_ie = 'null';
				$add_ie_new = 'null';
			}

			if ($cnpj_ant != $cnpj) {
				$add_cnpj = "'{$cnpj_ant}'";
				$add_cnpj_new = "'{$cnpj}'";
				$alterar = TRUE;
			} else {
				$add_cnpj = 'null';
				$add_cnpj_new = 'null';
			}

			//if ($alterar == TRUE AND $posto != 6359) {
			if ($alterar == TRUE) {
				pg_query($con,"BEGIN;");

				$sql_ins = "INSERT 	INTO tbl_conferencia_cadastro(
									fabrica,
									posto,
									admin,
									razao_social,
									razao_social_novo,
									cnpj,
									cnpj_novo,
									ie,
									ie_novo,
									cidade,
									cidade_novo,
									cep,
									cep_novo,
									estado,
									estado_novo
								)
				              	VALUES 
				              	(
					              	$login_fabrica,
					              	$posto,
					              	$login_admin,
					              	$add_nome,
					              	$add_nome_new,
					              	$add_cnpj,
					              	$add_cnpj_new,
					              	$add_ie,
					              	$add_ie_new,
					              	$add_cidade,
					              	$add_cidade_new,
					              	$add_cep,
					              	$add_cep_new,
					              	$add_estado,
					              	$add_estado_new
				              	);";
				//echo $sql_ins;exit;
				$res_ins = pg_query($con,$sql_ins);
				if(pg_last_error($con)){
					pg_query($con,"ROLLBACK;");
					$msg_erro["msg"][] = "Erro ao solicitar Alteração de Dados do Posto!";
				}else{
					pg_query($con,"COMMIT;");
					//header("Location: posto_dados_cadastrais.php?num_processo={$num_processo}&msg=oki");
					$msg = "Solicitação de Alteração de Dados do Posto Enviado!";
				}
			}else{
				$msg_erro["msg"][] = "Não foi realizada nenhuma alteração de dados!";
			}
		}
	}
}

include 'cabecalho_new.php';

$plugins = array(
   	"maskedinput",
   	"alphanumeric",
   	"ajaxform"
);

include 'plugin_loader.php';
?>
<script type="text/javascript">

	$(function() {
		
		/**
		 * Mascaras
		 */
		$("#contato_cep").mask("99999-999");
		$("#cnpj").mask("99.999.999/9999-99");

		//campo só aceita numerico
		$("#ie").numeric();
	});
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

if (count($msg) > 0) {
	?>
	<br />
	<div class="alert alert-success">
		<h4><?=$msg?></h4>
	</div>
	<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_pesquisa_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
<input type="hidden" id="posto" name="posto" class="span12"  value="<?=getValue('posto')?>" />

<div id="div_informacoes_atendimento" class="tc_formulario">
	<div class="titulo_tabela">Informações Detalhadas</div>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span3'>
            <div class='control-group <?=(in_array('ie', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class='control-label' for='ie'>I.E.</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                    	<h5 class='asteristico'>*</h5>
                        <input id="ie" name="ie" class="span12" type="text" value="<?=getValue('ie')?>" />
                    </div>
                </div>
            </div>
    	</div>
    	<div class="span2">
    	</div>
    	<div class="span3">
			<div class="control-group <?=(in_array('cnpj', $msg_erro['campos'])) ? "error" : "" ?>">
				<label class='control-label'>CNPJ</label>
				<div class='controls controls-row'>					
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="cnpj" id="cnpj" class='span12' value= "<?=getValue('cnpj')?>">
						<!-- <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="cnpj" /> -->
					</div>
				</div>	
   			</div>
		</div>
		<div class="span2"></div>	
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span10">
			<div class='control-group <?=(in_array('nome', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="nome">Razão Social</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
						<input id="nome" name="nome" class="span12" type="text" value="<?=getValue('nome')?>" maxlength="150" />
					</div>
				</div>
			</div>
		</div>
		<div class="span1"></div>	
	</div>
	<div class="row-fluid">
		<div class="span1"></div>			
		<div class="span3">
			<div class='control-group <?=(in_array('contato_cep', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="contato_cep">CEP</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
						<input id="contato_cep" name="contato_cep" class="span12 addressZip" type="text" value="<?=getValue('contato_cep')?>"/>
					</div>
				</div>
			</div>
		</div>
		<div class="span3">
			<div class="control-group <?=(in_array('contato_estado', $msg_erro['campos'])) ? "error" : "" ?>">
				<label class="control-label" for="contato_estado">Estado</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
							<select id="contato_estado" name="contato_estado" class="span12 addressState">
								<option value="" >Selecione</option>
								<?php
								#O $array_estados() está no arquivo funcoes.php
								foreach ($array_estados() as $sigla => $nome_estado) {
									$selected = ($sigla == getValue('contato_estado')) ? "selected" : "";

									echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
								}
								?>
							</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array('contato_cidade', $msg_erro['campos'])) ? "error" : "" ?>">
				<label class="control-label" for="contato_cidade">Cidade</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
						<input id="contato_cidade" name="contato_cidade" class="span12 addressCity" type="text" value="<?=getValue('contato_cidade')?>"/>
						<!-- <select id="contato_cidade" name="contato_cidade" class="span12 addressCity">
							<option value="" >Selecione</option>
							<?php
							
								// if (strlen(getValue("contato_estado")) > 0) {
								// 	$sql = "SELECT DISTINCT * FROM (
								// 			SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".getValue("contato_estado")."')
								// 				UNION (
								// 					SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".getValue("contato_estado")."')
								// 				)
								// 			) AS cidade
								// 			ORDER BY cidade ASC";
								// 	$res = pg_query($con, $sql);

								// 	if (pg_num_rows($res) > 0) {
								// 		while ($result = pg_fetch_object($res)) {
								// 			$selected  = (trim($result->cidade) == trim(getValue("contato_cidade"))) ? "SELECTED" : "";

								// 			echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
								// 		}
								// 	}
								// }
								
							?>
						</select> -->
						<!-- <input type="hidden" name="addressIbge" class="addressIbge" value="<?=getValue("addressIbge")?>"> -->
					</div>
				</div>
			</div>
		</div>
		<div class="span1"></div>
	</div>
<!-- 	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span3">
			<div class='control-group <?=(in_array('contato_bairro', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="contato_bairro">Bairro</label>
				<div class="controls controls-row">
					<div class="span12">
					<h5 class='asteristico'>*</h5>
						<input id="contato_bairro" name="contato_bairro" class="span12 addressDistrict" type="text" maxlength="80" value="<?=getValue('contato_bairro')?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span3">
			<div class='control-group <?=(in_array('contato_endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="contato_endereco">Endereço</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
						<input id="contato_endereco" name="contato_endereco" class="span12 address" type="text" value="<?=getValue('contato_endereco')?>" maxlength="80" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class='control-group <?=(in_array('contato_numero', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="contato_numero">Número</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
						<input id="contato_numero" name="contato_numero" class="span12" type="text" value="<?=getValue('contato_numero')?>" maxlength="10" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class='control-group' >
				<label class="control-label" for="contato_complemento">Complemento</label>
				<div class="controls controls-row">
					<div class="span12">
						<input id="contato_complemento" name="contato_complemento" class="span12" type="text" value="<?=getValue('contato_complemento')?>" maxlength="40" />
					</div>
				</div>
			</div>
		</div>
		<div class="span1"></div>	
	</div> -->
	<!-- <div class="row-fluid">
		<div class="span1"></div>

		<div class="span10">
			<div class='control-group' >
				<label class="control-label" for="observacao">Observações</label>
				<div class="controls controls-row">
					<div class="span12">
						<textarea id="observacao" name="observacao" class="span12" style="height: 100px;" ><?=getValue("observacao")?></textarea>
					</div>
				</div>
			</div>
		</div>
		<div class="span1"></div>
	</div> -->
	<br />
	<?php
	if (!count($msg) > 0) {
		?>
		<p class="tac">
			<input type="submit" class="btn" name="solicitar" value="Solicitar" />
		</p>
		<?php
	}
	?>
		
	<br />
</div>
</FORM>
<br />
<script language='javascript' src='address_components.js'></script>
<?php

include "rodape.php";

?>
