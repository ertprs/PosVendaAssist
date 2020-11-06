<?PHP

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="call_center";
include "autentica_admin.php";

# Fábricas que tem permissão para esta tela
if(!in_array($login_fabrica, array(1))) {
	header("Location: menu_callcenter.php");
	exit;
}

if($login_fabrica == 1 && isset($_GET["hd_chamado"])){
	$hd_chamado = $_GET["hd_chamado"];

	$sql_p = "SELECT protocolo_cliente FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
	$res_p = pg_query($con, $sql_p);

	if(pg_num_rows($res_p) > 0){
		$hd_chamado = pg_fetch_result($res_p, 0, "protocolo_cliente");
	}

}

$sql = "SELECT admin_sap FROM tbl_admin WHERE admin = $login_admin AND fabrica =$login_fabrica";
$res = pg_query($con,$sql);
$admin_sap = pg_fetch_result($res, 0, 'admin_sap');

$dadosAd = "";
$bo = false;
if ($_GET['acao'] == 'alterar' && isset($_GET['advertencia'])) {
	$advertencia = $_GET['advertencia'];
	$acao = $_GET['acao'];

	$sql = "SELECT tbl_advertencia.*,
				   tbl_os.sua_os,
				   tbl_os.consumidor_nome,
				   tbl_posto_fabrica.codigo_posto,
				   tbl_posto.nome,
				   tbl_produto.produto,
				   tbl_produto.descricao,
				   tbl_produto.referencia,
				   tbl_defeito_constatado.descricao AS def_desc,
				   tbl_advertencia.parametros_adicionais->>'acao_bo' AS acao_bo,
				   tbl_advertencia.parametros_adicionais->>'tipo_falha' AS tipo_falha,
				   tbl_advertencia.parametros_adicionais->>'nivel_falha' AS nivel_falha,
				   tbl_advertencia.parametros_adicionais->>'tratativa_atendimento' AS tratativa_atendimento 
			FROM tbl_advertencia 
			LEFT JOIN tbl_os ON tbl_advertencia.os = tbl_os.os
			LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			WHERE advertencia = $advertencia AND tbl_advertencia.fabrica = $login_fabrica limit 1";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$dadosAd = pg_fetch_all($res);
	}

	if (!empty($dadosAd[0]['tipo_ocorrencia'])) {
		$bo = true;
	}
}

$layout_menu = "callcenter";
$title = "CADASTRO DE ADVERTÊNCIA / BOLETIM DE OCORRÊNCIA";

include "cabecalho_new.php";
//include "../js/js_css.php";

$plugins = array(
	"shadowbox",
	"mask",
	);

include("plugin_loader.php");

?>

<style type="text/css">
	/*.sucesso{
		background-color:green;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}*/
</style>

<!-- <link type="text/css" href="css/cadastro_advertencia_bo.css" rel="stylesheet"></link> -->
<script type="text/javascript" src="js/cadastro_advertencia_bo.js"></script>

<?php
if(($login_fabrica == 1 && strlen($hd_chamado) > 0) || ($bo && $_GET['acao'] == 'alterar')){
	?>
	<script>
		$(function(){
			$("#tipo_cadastro").change();
		})
	</script>
	<?php
}
?>

<?
	// Busca e armazena os tipos de ocorrência
	$sql = "SELECT tipo_ocorrencia,
				   descricao
			FROM tbl_tipo_ocorrencia
			WHERE fabrica = $login_fabrica
			ORDER BY tipo_ocorrencia";

	$res = pg_query($con, $sql);

	while($ocorrencia = pg_fetch_object($res)) {
		$tipos_ocorrencia[] = $ocorrencia;
	}
?>

<?
		$msg_erro = $_GET["msg_erro"];

		if(trim($msg_erro) != "") {

			$msg_erro = ($msg_erro == "erro_posto") ? "Posto não encontrado" : $msg_erro;
			$msg_erro = ($msg_erro == "erro_advertencia") ? "Não foi possível inserir a advertência. Favor entrar em contato com a Telecontrol." : $msg_erro;
			$msg_erro = ($msg_erro == "erro_email_posto") ? "Não foi possível inserir a advertência. Favor entrar em contato com a Telecontrol." : $msg_erro;
			$msg_erro = ($msg_erro == "erro_os") ? "Ordem de Serviço não Encontrada" : $msg_erro;

	?>
		<div class="container">
			<div class="alert alert-error">
				<h4>Erro: <?=$msg_erro?></h4>
			</div>
		</div>
	<? } ?>
	<?
		$sucesso = $_GET["sucesso"];

		if(trim($sucesso) != "") {
			$tipo = $_GET["type"];
			$id   = $_GET["id"];
	?>
			<div class="container">
				<div class="alert alert-success">
					<h4><?=($tipo == "advertencia" ? "Advertência" : "Boletim de ocorrência") . " $id cadastrado com sucesso"?></h4>
				</div>
			</div>
	<? } ?>

<div id="ajax-message" class="container">
</div>
<div class="row">
	<b class="pull-right">O cadastro de advertência poderá ser feito apenas por um usuário <font color='#FF0000'>SAP</font></b>
</div>
<form method="post" name='cadastro_advertencia_bo' action="cadastro_advertencia_bo_ajax.php" align='center' class='form-search form-inline tc_formulario'>

	<div class='titulo_tabela '>Cadastro de advertência / boletim de ocorrência</div></br />

	<div class="row-fluid">
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='tipo_cadastro'>Tipo de cadastro</label>
				<div class='controls'>
					<div class='span10'>
						<select name="tipo_cadastro" id="tipo_cadastro">
							<option>- Selecione -</option>
							<?php
								$selected = "";
								if($admin_sap == "t"){
									if (!$bo && $_GET['acao'] == 'alterar') {
										$selected = "selected";
									}
							?>
									<option value="advertencia" <?=$selected?>>Advertência</option>
							<?php
								}
							?>
							<?php	
							$selected = "";						
							if(($login_fabrica == 1 && strlen($hd_chamado) > 0) || ($bo && $_GET['acao'] == 'alterar')){
								$selected = "selected";
							}
							?>
							<option value="boletim_ocorrencia" <?=$selected?>>Boletim de ocorrência</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span4"></div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid cadastroForm">
		<div class="span2"></div>
		<div class='span2'>
			<div class='control-group'>
				<label class='control-label' for='nivel_falha'>Nível de Falha</label>
				<div class='controls'>
					<div class='span12'>
						<select name="nivel_falha" id="nivel_falha" class="span12" required>
							<option></option>
							<?php 
								$nFalha = ["leve"=>"Leve", "medio"=>"Médio", "alto"=>"Alto"];

								foreach ($nFalha as $key => $value) {
									$xselected = "";
									if ($dadosAd[0]['nivel_falha'] == $key) {
										$xselected = "SELECTED";
									}
									echo "<option $xselected value='$key'>" . $value . "</option>";
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group'>
				<label class='control-label' for='tratativa_atendimento'>Tratativa do Atendimento</label>
				<div class='controls'>
					<div class='span12'>
						<select name="tratativa_atendimento" id="tratativa_atendimento" class="span12" required>
							<option></option>
							<?php
								$nTrata = ["devolucao"=>"Devolução de Valor", "reparo"=>"Reparo", "troca"=>"Troca do Produto"];

								foreach ($nTrata as $key => $value) {
									$xselected = "";
									if ($dadosAd[0]['tratativa_atendimento'] == $key) {
										$xselected = "SELECTED";
									}
									echo "<option $xselected value='$key'>" . $value . "</option>";
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group'>
				<label class='control-label' for='tipo_falha'>Tipo de Falha</label>
				<div class='controls'>
					<div class='span12'>
						<select name="tipo_falha" id="tipo_falha" class="span12" required>
							<option></option>
							<?php
								$nTipo = [	"duvida_tecnica"          =>"Falta de Comunicação C/ o Suporte Ref. à Dúvidas Técnicas",
											"pendencia_peca"          =>"Falta de Comunicação C/ o Suporte Ref. à Pendência de Peça", 
											"telecontrol"             =>"Falta de Comunicação C/ o Suporte Ref. à Dúvida na Utilização do Sistema Telecontrol", 
											"demora_analise"          =>"Demora na Análise do Produto (Sem Pedido de Peças)", 
											"demora_realizar"         =>"Demora em Realizar Pedido de Peças", 
											"procedimentos_incorretos"=>"Realização de Procedimentos Incorretos"];

								foreach ($nTipo as $key => $value) {
									$xselected = "";
									if ($dadosAd[0]['tipo_falha'] == $key) {
										$xselected = "SELECTED";
									}
									echo "<option $xselected value='$key'>" . $value . "</option>";
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row-fluid boletim_ocorrencia">
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='tipo_ocorrencia'>Tipo de Ocorrência</label>
				<div class='controls'>
					<div class='span4'>
						<select name="tipo_ocorrencia" id="tipo_ocorrencia">
							<option></option>
							<?
								foreach ($tipos_ocorrencia as $ocorrencia) {
									$xselected = "";
									if ($ocorrencia->tipo_ocorrencia == $dadosAd[0]['tipo_ocorrencia']) {
										$xselected = "SELECTED";
									}
									echo "<option $xselected value='$ocorrencia->tipo_ocorrencia'>" . $ocorrencia->descricao . "</option>";
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class='span4 boletim_ocorrencia'>
			<div class='control-group'>
				<label class='control-label' for='numero_sac'>Número do chamado SAC/Suporte</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<?php
							$hd_chamado = (!empty($dadosAd[0]['numero_sac'])) ? $dadosAd[0]['numero_sac'] : $hd_chamado;
						?>
						<input type="text" name="numero_sac" id="numero_sac" class='span12' maxlength="10" value="<?=$hd_chamado?>">
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
    <div class="row-fluid cadastroForm">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" from="orderm_servico">Número da Ordem de Serviço</label>
                <div class="controls control-row">
                    <div class="span9 input-append">
                        <input type="hidden" name="os" />
                        <?php
                        	$xos = (!empty($dadosAd[0]['os'])) ? $dadosAd[0]['codigo_posto'].$dadosAd[0]['sua_os'] : $os;
                        ?>
                        <input type="text" name="ordem_servico" id="ordem_servico" class='span11' value="<?=$xos?>" />
                        <span class='add-on'><i class='icon-search' ></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="contato_posto">Contato do Posto</label>
                <div class="controls control-row">
                    <div class="span12 input-append">
                    	<?php
                    		$contato_posto = (!empty($dadosAd[0]['contato_posto'])) ? (mb_check_encoding($dadosAd[0]['contato_posto'], "UTF-8")) ? utf8_decode($dadosAd[0]['contato_posto']) : $dadosAd[0]['contato_posto'] : $contato_posto;
                    	?>
                        <input id="contato_posto" type="text" name="contato_posto" class="span8" value="<?=$contato_posto?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>

	<div class='row-fluid cadastroForm'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='produto_referencia'>Ref. Produto</label>
				<div class='controls controls-row'>
					<div class='span9 input-append'>
						<?php
							$produto_produto = (!empty($dadosAd[0]['produto'])) ? $dadosAd[0]['produto'] : $produto_produto;
						?>
						<input type="hidden" name="produto_produto" value="<?=$produto_produto?>" />
						<?php
							$produto_referencia = (!empty($dadosAd[0]['referencia'])) ? $dadosAd[0]['referencia'] : $produto_referencia;
						?>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span11' maxlength="20" value="<? echo $produto_referencia ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<?php
							$produto_descricao = (!empty($dadosAd[0]['descricao'])) ? $dadosAd[0]['descricao'] : $produto_descricao;
						?>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span11' value="<? echo $produto_descricao ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<div class="row-fluid  cadastroForm">
		<div class='span2'></div>
		<div class='span4 cadastroForm'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Código do posto</label>
				<div class='controls controls-row'>
					<div class='span9 input-append'>
						<?php
							$codigo_posto = (!empty($dadosAd[0]['codigo_posto'])) ? $dadosAd[0]['codigo_posto'] : $codigo_posto;
						?>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span11' required value="<?=$codigo_posto?>" />
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>

		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='descricao_posto'>Descrição do posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<?php
							$descricao_posto = (!empty($dadosAd[0]['nome'])) ? $dadosAd[0]['nome'] : $descricao_posto;
						?>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span11' value="<?=$descricao_posto?>">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>
	</div>

	<div class="row-fluid cadastroForm">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" from="consumidor">Nome do Consumidor</label>
				<div class="controls control-row">
					<div class="span7 input-append">
						<?php
							$nome_consumidor = (!empty($dadosAd[0]['consumidor_nome'])) ? $dadosAd[0]['consumidor_nome'] : $nome_consumidor;
						?>
						<input type="text" id="consumidor" name="nome_consumidor" readonly value="<?=$nome_consumidor?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" from="defeito">Defeito Constatado</label>
				<div class="controls control-row">
					<div class="span7 input-append">
						<?php
							$defeito_constatado = (!empty($dadosAd[0]['def_desc'])) ? $dadosAd[0]['def_desc'] : $produto_referencia;
						?>
						<input type="text" id="defeito" name="defeito_constatado" readonly value="<?=$defeito_constatado?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid  cadastroForm">

		<div class="span2"></div>

		<div class='span8'>
			<div class='control-group'>
				<label class='control-label'>Texto</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<?php
							$textarea = (!empty($dadosAd[0]['mensagem'])) ? (mb_check_encoding($dadosAd[0]['mensagem'], "UTF-8")) ? utf8_decode($dadosAd[0]['mensagem']) : $dadosAd[0]['mensagem'] : $textarea;
						?>
						<textarea style="max-heigth:300px; min-heigth:300px; resize: none;"class="span12" rows="2" name="textarea" required ><?=$textarea?></textarea>
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>
	</div>
	<br />
	<p class="cadastroForm"><br/>
		<?php if ($_GET['acao'] == 'alterar') {	?>
				<input type="hidden" name="action" value="alterar" />
				<input type="submit" name="btn-submit" class="btn" value="Alterar"/>
				<input type="hidden" id="adv" name="adv" value="<?=$_GET['advertencia']?>" />
		<?php } else { ?>
				<input type="hidden" name="action" value="gravar" />
				<input type="submit" name="btn-submit" class="btn" value="Gravar"/>
		<?php }	?>

	</p><br/>

</form>

<script type="text/javascript">
	$(document).ready(function(){
		$("#tipo_cadastro").change()
	})
</script>

<?php include "rodape.php"; ?>
