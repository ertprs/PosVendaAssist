<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$mao_obra_servico_realizado = $_REQUEST['mao_obra_servico_realizado'];

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {

    $estado = strtoupper($_POST["estado"]);
    $pais   = strtoupper($_POST["pais"]);

    $retorno = buscaCidade($estado, $pais);
    exit(json_encode($retorno));
}


if (isset($mao_obra_servico_realizado) && !empty($mao_obra_servico_realizado)) {
	$sql = "SELECT *
				FROM tbl_mao_obra_servico_realizado
				WHERE fabrica = $login_fabrica
				AND mao_obra_servico_realizado = $mao_obra_servico_realizado";
	$res = pg_query($con, $sql);
	$tipo_atendimento_posto = pg_fetch_result($res, 0, 'tipo_atendimento');
	$mao_de_obra_recusada = pg_fetch_result($res, 0, 'mao_de_obra');
	$xxparametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),1);

	if (isset($xxparametros_adicionais["cidade"]) && strlen($xxparametros_adicionais["cidade"]) > 0) {
		$cidade = $xxparametros_adicionais["cidade"];
	}

	if (isset($xxparametros_adicionais["estado"]) && strlen($xxparametros_adicionais["estado"]) > 0) {
		$estado_bd = $xxparametros_adicionais["estado"];
	}
	//echo "<pre>".print_r(pg_fetch_all($res),1)."</pre>";exit;
}

if($_POST['excluir'] == 'true'){
	$id_mao_obra = $_POST['mao_obra_servico_realizado'];

	$sql = "SELECT mao_obra_servico_realizado
				FROM tbl_mao_obra_servico_realizado
				WHERE fabrica = $login_fabrica
				AND mao_obra_servico_realizado = $id_mao_obra";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$delete = "DELETE FROM tbl_mao_obra_servico_realizado
					WHERE fabrica = $login_fabrica AND mao_obra_servico_realizado = $id_mao_obra";
		$res_delete  = pg_query($con, $delete);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("error" => utf8_encode("Erro ao deletar registro."));
		}else{
			$retorno = array("success" => utf8_encode("Registro deletado com sucesso."));
		}
	}
	echo json_encode($retorno);
	exit;
}

if($_POST["btn_acao"] == "submit") {
	$radios       					= $_POST['radios'];
	$referencia_descricao 			= $_POST['referencia_descricao_posto'];
	$familia 						= $_POST['familia_posto'];
	$servico 						= $_POST['servico'];
	$mao_de_obra 					= $_POST['mao_de_obra'];
	$tipo_posto 					= $_POST['tipo_posto'];

	$campos = array();
	$valores = array();


	/* RADIO FAMILIA */
	if($radios == 'radio_familia'){
		$radio_familia_checked = "checked";

		if(strlen(trim($familia)) > 0){
			$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
			$res = pg_query($con ,$sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro["msg"][]    = "Erro ao consultar familia.";
			}else{
				if (!pg_num_rows($res)) {
					$msg_erro["msg"][]    = "Familia não encontrada";
					$msg_erro["campos"][] = "familia_posto";
				}else{
					$campos[] = "familia";
					$valores[] = $familia;

					$cond .= " AND familia = $familia ";
				}
			}
		}else{
			$msg_erro["msg"][]    = "Campo Familia é obrigatorio.";
			$msg_erro["campos"][] = "familia_posto";
		}
	}

	/* RADIO PRODUTO */
	if($radios == 'radio_produto'){
		$radio_produto_checked = "checked";

		if(strlen(trim($referencia_descricao)) > 0){
			$pesquisa_referencia_descricao = explode('-',$referencia_descricao);

			$sql = "SELECT produto
					FROM tbl_produto
					WHERE fabrica_i = {$login_fabrica}
					AND UPPER(referencia) = UPPER('{$pesquisa_referencia_descricao[0]}')";
			$res = pg_query($con ,$sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro["msg"][]    = "Erro ao consultar produto.";
			}else{
				if (!pg_num_rows($res)) {
					$msg_erro["msg"][]    = "Produto não encontrado";
					$msg_erro["campos"][] = "produto_referencia_descricao";
				} else {
					$produto = pg_fetch_result($res, 0, "produto");
					$campos[] = "produto";
					$valores[] = $produto;

					$cond .= " AND produto = $produto ";
				}
			}
		}else{
			$msg_erro["msg"][]    = "Campo Produto é obrigatorio.";
			$msg_erro["campos"][] = "produto_referencia_descricao";
		}
	}

	if(strlen(trim($tipo_posto)) > 0){
		$sql = "SELECT tipo_posto, descricao FROM tbl_tipo_posto WHERE fabrica = $login_fabrica AND tipo_posto = $tipo_posto";
		$res = pg_query($con,$sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro["msg"][]    = "Erro ao pesquisar tipo de posto.";
		}else{
			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Tipo Posto não encontrado.";
				$msg_erro["campos"][] = "tipo_posto";
			}else{
				$campos[] = "tipo_posto";
				$valores[] = $tipo_posto;

				$cond .= " AND tipo_posto = $tipo_posto ";
			}
		}

	}else{
		$msg_erro["msg"][]    = "Selecione o Tipo de Posto.";
		$msg_erro["campos"][] = "tipo_posto";
	}

	if($radios == ""){
		$msg_erro["msg"][] = "Selecione algumas das opção (Produto ou Familia).";
	}

	/* SERVICO */
	if(strlen(trim($servico)) > 0){
		$sql = "SELECT servico_realizado, descricao FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND servico_realizado = $servico";
		$res = pg_query($con,$sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro["msg"][]    = "Erro ao pesquisar serviço realizado.";
		}else{
			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Serviço não encontrada";
				$msg_erro["campos"][] = "servico";
			}else{
				$servico = pg_fetch_result($res, 0, 'servico_realizado');
			}
		}
	}else{
		$msg_erro["msg"][]    = "Campo Serviço é obrigatorio.";
		$msg_erro["campos"][] = "servico";
	}

	/* MAO DE OBRA */
	if($mao_de_obra > 0){
		$mao_de_obra = moneyDB($mao_de_obra);
	}else{
		$msg_erro["msg"][]    = "Informe o valor da mão de obra.";
		$msg_erro["campos"][] = "mao_de_obra";
	}

	$campos[] = "servico_realizado";
	$campos[] = "mao_de_obra";
	$campos[] =	"fabrica";
	$valores[] = $servico;
	$valores[] = $mao_de_obra;
	$valores[] = $login_fabrica;

	// if($radios == 'radio_produto'){
	// 	$cond .= " AND mao_de_obra = $mao_de_obra ";
	// }

	$sql_query = "SELECT mao_obra_servico_realizado
				FROM tbl_mao_obra_servico_realizado
				WHERE fabrica = $login_fabrica
				AND servico_realizado = $servico
				$cond ";
	$res_query = pg_query($con, $sql_query);

	if(pg_num_rows($res_query) > 0){
		$msg_erro["msg"][]    = "Já existe registro com esses dados gravado.";
	}

	if (!count($msg_erro["msg"])) {

		$dados_campos = implode(',',$campos);
		$dados_valores = implode(',',$valores);

		$insert = "INSERT INTO tbl_mao_obra_servico_realizado
						(
							$dados_campos
						)VALUES(
							$dados_valores
						)";
		$res_insert = pg_query($con, $insert);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro["msg"][]    = "Erro ao inserir registro.";
		}else{
			$msg_success = "Dados gravado com sucesso";
		}
	}
}

if(isset($_POST['btn_acao_recusada']) && $_POST["btn_acao_recusada"] == 'Alterar'){

	$tipo_atendimento 		= $_POST['tipo_atendimento_posto'];
	$mao_obra_servico_realizado 		= $_POST['mao_obra_servico_realizado'];
	$mao_de_obra_recusada 	= moneyDB($_POST['mao_de_obra_recusada']);
	$cidade 				= $_POST['cidade'];
	$estado 				= $_POST['estado'];


	if(strlen(trim($tipo_atendimento)) == 0){
		$msg_erro["msg"][]    = "Tipo de atendimento é obrigatório.";
		$msg_erro["campos"][] = "tipo_atendimento_posto";
	}

	if ($login_fabrica == 195 && strlen($estado) > 0 && strlen($cidade) == 0) {
		$msg_erro["msg"][]    = "Informe a Cidade.";
		$msg_erro["campos"][] = "cidade";
	}
	if(count($msg_erro["msg"]) == 0) {

		$campoAdd = "";
		$valorAdd = "";
		if ($login_fabrica == 195 && strlen($estado) > 0 && strlen($cidade) > 0) {
			$xnovoxparametrosAdd["estado"] = $estado;
			$xnovoxparametrosAdd["cidade"] = $cidade;
			$xnovoxparametrosAdd = json_encode($xnovoxparametrosAdd);
			$valorAdd = ",parametros_adicionais='{$xnovoxparametrosAdd}'";
		}
		$sql_update = "UPDATE tbl_mao_obra_servico_realizado SET tipo_atendimento = $tipo_atendimento_posto,mao_de_obra = $mao_de_obra_recusada {$valorAdd}
									WHERE fabrica = $login_fabrica AND mao_obra_servico_realizado = $mao_obra_servico_realizado";
		$res_update = pg_query($con, $sql_update);
		if (pg_last_error()) {
			$msg_erro["msg"][]    = "Erro ao alterar.";
		} else {
			$msg_success = "Alterado com sucesso!";
			echo "<meta http-equiv=refresh content=\"0;URL=cadastro_mao_obra_new.php\">";
		}
	}

}
if(isset($_POST['btn_acao_recusada']) && $_POST["btn_acao_recusada"] == 'Gravar'){

	$tipo_atendimento 		= $_POST['tipo_atendimento_posto'];
	$mao_de_obra_recusada 	= $_POST['mao_de_obra_recusada'];
	$cidade 	= $_POST['cidade'];
	$estado 	= $_POST['estado'];

	if(strlen(trim($tipo_atendimento)) > 0){
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro["msg"][]    = "Erro ao consultar tipo de atendimento.";
		}else{
			if(pg_num_rows($res) > 0){
				$tipo_atendimento = pg_fetch_result($res,0,'tipo_atendimento');
			}else{
				$msg_erro["msg"][]    = "Tipo de atendimento não encontrado.";
				$msg_erro["campos"][] = "tipo_atendimento_posto";
			}
		}
	}else{
		$msg_erro["msg"][]    = "Tipo de atendimento é obrigatório.";
		$msg_erro["campos"][] = "tipo_atendimento_posto";
	}

	if(strlen(trim($mao_de_obra_recusada)) > 0){
		$mao_de_obra_recusada = moneyDB($mao_de_obra_recusada);
	}else{
		$msg_erro["msg"][]    = "Informe o valor da mão de obra.";
		$msg_erro["campos"][] = "mao_de_obra_recusada";
	}

	if ($login_fabrica == 195 && strlen($estado) > 0 && strlen($cidade) == 0) {
		$msg_erro["msg"][]    = "Informe a Cidade.";
		$msg_erro["campos"][] = "cidade";
	}


	$sql = "SELECT mao_obra_servico_realizado,parametros_adicionais
				FROM tbl_mao_obra_servico_realizado
				WHERE fabrica = $login_fabrica
				AND tipo_atendimento = $tipo_atendimento";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		$msg_erro["msg"][]    = "Erro ao gravar dados.";
	}else{
		if(pg_num_rows($res) > 0 && $login_fabrica <> 195){
			if(!count($msg_erro["msg"])) {

				$sql_update = "UPDATE tbl_mao_obra_servico_realizado SET mao_de_obra = $mao_de_obra_recusada 
								WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
				$res_update = pg_query($con, $sql_update);
			}

		}else{
			if(!count($msg_erro["msg"])) {

				$campoAdd = "";
				$valorAdd = "";
				if ($login_fabrica == 195 && strlen($estado) > 0 && strlen($cidade) > 0) {
					$xnovoxparametrosAdd["estado"] = $estado;
					$xnovoxparametrosAdd["cidade"] = $cidade;
					$xnovoxparametrosAdd = json_encode($xnovoxparametrosAdd);
					$campoAdd = ",parametros_adicionais";
					$valorAdd = ",'{$xnovoxparametrosAdd}'";
				}

				$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND ativo IS TRUE";
				$res = pg_query($con, $sql);

				$servico_padrao = pg_fetch_result($res, 0, 0);

				$sql_insert = "INSERT INTO tbl_mao_obra_servico_realizado(servico_realizado,mao_de_obra,tipo_atendimento,fabrica {$campoAdd})
								VALUES($servico_padrao,$mao_de_obra_recusada,$tipo_atendimento,$login_fabrica {$valorAdd})";
				$res_insert = pg_query($con, $sql_insert);
			}
		}
	}
}

$sqlDados = "SELECT tbl_servico_realizado.descricao AS servico_realizado,
				tbl_tipo_posto.descricao AS tipo_posto,
				tbl_mao_obra_servico_realizado.mao_de_obra,
				tbl_mao_obra_servico_realizado.mao_obra_servico_realizado,
				tbl_familia.descricao AS familia,
				tbl_mao_obra_servico_realizado.fabrica,
				tbl_mao_obra_servico_realizado.parametros_adicionais,
				tbl_tipo_atendimento.descricao AS tipo_atendimento,
				tbl_produto.descricao AS produto_descricao,
				tbl_produto.referencia AS produto_referencia
		FROM tbl_mao_obra_servico_realizado
		JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_mao_obra_servico_realizado.servico_realizado
			AND tbl_servico_realizado.fabrica = $login_fabrica
		LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_mao_obra_servico_realizado.tipo_posto
			AND tbl_tipo_posto.fabrica = $login_fabrica
		LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_mao_obra_servico_realizado.familia
			AND tbl_familia.fabrica = $login_fabrica
		LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_mao_obra_servico_realizado.tipo_atendimento
			AND tbl_tipo_atendimento.fabrica = $login_fabrica
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_mao_obra_servico_realizado.produto
			AND tbl_produto.fabrica_i = $login_fabrica
		WHERE tbl_mao_obra_servico_realizado.fabrica = $login_fabrica";
$resDados = pg_query($con, $sqlDados);



function buscaCidade($estado, $pais) {
	global $con, $login_fabrica, $array_estados;
	$retorno = [];

    if (!empty($pais) && $pais != "BR") {
        
        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                FROM tbl_cidade 
                WHERE UPPER(estado_exterior) = UPPER('{$estado}')
                AND UPPER(pais) = UPPER('{$pais}')
                ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }

    } else {

        if (array_key_exists($estado, $array_estados())) {
			$cond_pais = !empty($pais) ? " and pais ='$pais' " : "";
            $sql = "SELECT DISTINCT * FROM (
                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}') $cond_pais
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
                $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
            }
        } else {
            $retorno = array("error" => utf8_encode("Estado não encontrado"));
        }

    }
    return $retorno;
}

$layout_menu = "cadastro";
$title = "CADASTRO MÃO DE OBRA";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"price_format",
	"dataTable"
);

include("plugin_loader.php");
?>
<style type="text/css">
	.ajuste{
		margin-bottom: -20px;
		margin-top: 20px;
	}

</style>
<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#estado").change(function() {
        
            var paisSelecionado = "BR";

            busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "consumidor", undefined, paisSelecionado);

        });

	});

	function busca_cidade(estado, consumidor_revenda, cidade, pais = "BR") {
	    $("#cidade").html('');

	    if (estado.length > 0) {
	        $.ajax({
	            async: false,
	            url: "cadastro_mao_obra_new.php",
	            type: "POST",
	            data: { ajax_busca_cidade: true, estado: estado , pais: pais},
	            complete: function(data) {
	                data = $.parseJSON(data.responseText);

	                if (data.error) {
	                    alert(data.error);
	                } else {
	                    var option = $("<option></option>", { value: "", text: "Selecione..."});
	                    $("#cidade").append(option);
	                    $.each(data.cidades, function(key, value) {
	                        option = $("<option></option>", { value: value, text: value});
	                        $("#cidade").append(option);
	                    });
	                }

	            }
	        });
	    }

	}

	function verifica_campos(dados){
		if(dados == 'radio_produto'){
			$("#referencia_descricao").show();
			$("#familia_posto").hide();

			$("select[name='tipo_posto']").val('');
			$("input[name='referencia_descricao']").val('');
			$("select[name='familia_posto']").val('');
			$("select[name='servico']").val('');
			$("input[name='mao_de_obra']").val('');
		}else if(dados == 'radio_familia'){
			$("#familia_posto").show();
			$("#referencia_descricao").hide();

			$("select[name='tipo_posto']").val('');
			$("input[name='referencia_descricao_posto']").val('');
			$("select[name='familia_posto']").val('');
			$("select[name='servico']").val('');
			$("input[name='mao_de_obra']").val('');
		}
	}

	function retorna_produto (retorno) {
		if($("input[name='radios']:checked").val() == 'radio_produto' ){
			$("input[name='referencia_descricao_posto']").val(retorno.referencia+'-'+retorno.descricao);
		}
	}

	function excluir(dados){
		$.ajax({
			url: '<?= $_SERVER["PHP_SELF"]; ?>',
			type: "POST",
	        dataType:"JSON",
			data: {
				mao_obra_servico_realizado: dados,
				excluir: 'true'
			},
			complete: function(data){
				data = $.parseJSON(data.responseText);
				if (data.erro) {
					alert(data.erro);
				} else {
					alert(data.success);
					$("#"+dados).remove();
				}
			}
	    });
	}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (strlen(trim($msg_success)) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<?php if ($login_fabrica != 195) {?>
<!-- CADASTRO DE MÃO DE OBRA -->
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >

	<div class='titulo_tabela '>Cadastro mão de obra</div>
	<br/>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span4' id='tipo_posto'>
			<div class='control-group <?=(in_array("tipo_posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='tipo_posto'>Tipo Posto</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<select name="tipo_posto">
							<option value=""></option>
							<?php
							$sql = "SELECT tipo_posto, descricao
									FROM tbl_tipo_posto
									WHERE fabrica = $login_fabrica
									AND ativo";
							$res = pg_query($con,$sql);
							foreach (pg_fetch_all($res) as $key) {
								$selected_tipo_posto = ( isset($tipo_posto) and ($tipo_posto == $key['tipo_posto']) ) ? "SELECTED" : '' ;
							?>
								<option value="<?php echo $key['tipo_posto']?>" <?php echo $selected_tipo_posto ?> >

									<?php echo $key['descricao']; ?>

								</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class='span2' style="padding-top: 21px;">
			 <label class="radio">
		        <input type="radio" name="radios" <?=$radio_produto_checked?> onclick="verifica_campos('radio_produto');" value="radio_produto">
		        Produto
		    </label>
		</div>
		<div class='span2' style="padding-top: 21px;">
		    <label class="radio">
		        <input type="radio" name="radios" <?=$radio_familia_checked?> onclick="verifica_campos('radio_familia');" value="radio_familia">
		        Familia
		    </label>
		</div>
		<div class="span2"></div>
	</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span3' <?php if($radios == 'radio_produto') { echo "style=''";}else{ echo "style='display:none;'";} ?> id="referencia_descricao">
			<div class='control-group <?=(in_array("produto_referencia_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='referencia_descricao_posto'>Referencia/Descrição</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<div class='span10 input-append'>
						<input type="text" name="referencia_descricao_posto" class='span12' maxlength="20" value="<? echo $referencia_descricao_posto ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia_descricao" />
					</div>
				</div>
			</div>
		</div>

		<div class='span3' <?php if($radios == 'radio_familia') { echo "style=''";}else{ echo "style='display:none;'";} ?> id="familia_posto" >
			<div class='control-group <?=(in_array("familia_posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='familia_posto'>Familia</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select class='span10' name="familia_posto">
						<option value=""></option>
						<?php
							$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
							$res = pg_query($con,$sql);
							foreach (pg_fetch_all($res) as $key) {
								$selected_familia = ( isset($familia_posto) and ($familia_posto == $key['familia']) ) ? "SELECTED" : '' ;
							?>
								<option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
									<?php echo $key['descricao']?>
								</option>
							<?php
							}
						?>
					</select>
				</div>
			</div>
		</div>

		<div class='span3'>
			<div class='control-group <?=(in_array("servico", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='servico'>Serviço</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select class='span10' name="servico" id="servico">
						<option value=""></option>
						<?php
							$sql = "SELECT servico_realizado, descricao FROM tbl_servico_realizado WHERE fabrica = $login_fabrica order by descricao";
							$res = pg_query($con,$sql);
							foreach (pg_fetch_all($res) as $key) {
								if($key['descricao'] == 'Cancelado'){
									continue;
								}
								if($key['descricao'] == 'Troca de Peça (estoque)'){
									continue;
								}
								$selected_servico_realizado = ( isset($servico) and ($servico == $key['servico_realizado']) ) ? "SELECTED" : '' ;
							?>
								<option value="<?php echo $key['servico_realizado']?>" <?php echo $selected_servico_realizado ?> >
									<?php echo $key['descricao']?>
								</option>
							<?php
							}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group <?=(in_array("mao_de_obra", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='mao_de_obra'>Mão de Obra</label>
				<div class='controls controls-row'>
					<div class='span10'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="mao_de_obra" id="mao_de_obra" price="true" size="12" maxlength="10" class='span12' value="<? echo priceFormat($mao_de_obra);?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span1'></div>
	</div>
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
<!-- FIM CADASTRO DE MÃO DE OBRA -->
<?php }?>

<?php if ($login_fabrica != 203){ ?>
<form name='frm_relatorio2' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '><?php echo ($login_fabrica != 195) ? "Cadastro de valor Garantia Recusada" : "Cadastro mão de obra";?></div>
	<br/>
	<?php if ($login_fabrica == 195 && isset($mao_obra_servico_realizado) && strlen($mao_obra_servico_realizado) > 0) {?>
		<input type="hidden" name="mao_obra_servico_realizado" value="<?php echo $mao_obra_servico_realizado;?>">
	<?php }?>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("tipo_atendimento_posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='tipo_atendimento_posto'>Tipo Atendimento</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select class='span10' name="tipo_atendimento_posto">
						<option value=""></option>
						<?php
						$cond = "";
						if ($login_fabrica != 195) {
							$cond = " AND descricao = 'Garantia Recusada' ";
						} 
						$sql = "SELECT tipo_atendimento, descricao
								FROM tbl_tipo_atendimento
								WHERE fabrica = $login_fabrica
								AND ativo
								{$cond}
								";
						$res = pg_query($con,$sql);
						foreach (pg_fetch_all($res) as $key) {
							$selected_tipo_atendimento = ( isset($tipo_atendimento_posto) and ($tipo_atendimento_posto == $key['tipo_atendimento']) ) ? "SELECTED" : '' ;
						?>
							<option value="<?php echo $key['tipo_atendimento']?>" <?php echo $selected_tipo_atendimento ?> >
								<?php echo $key['descricao']; ?>
							</option>
						<?php
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("mao_de_obra_recusada", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='mao_de_obra_recusada'>Mão de Obra</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="mao_de_obra_recusada" id="mao_de_obra_recusada" price="true" size="12" maxlength="10" class='span12' value="<? echo priceFormat($mao_de_obra_recusada);?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php if ($login_fabrica == 195) {?>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='estado'>Estado</label>
				<div class='controls controls-row'>
					<select name="estado" id="estado" class="form-control">
						<option value="">Selecione um Estado</option>
						<?php
						if (isset($_POST["estado"])) {
							$estado_bd = $_POST["estado"];
						}
						$sqlPesquisa = "SELECT estado,nome 
								FROM tbl_estado
								WHERE pais = '{$login_pais}'
								AND visivel IS TRUE
								ORDER BY nome";
						$resPesquisa = pg_query($con, $sqlPesquisa);

						while ($dados = pg_fetch_object($resPesquisa)) { 

							$xxselected = ($estado_bd == $dados->estado) ? "selected" : "";

							?>
							<option value="<?= $dados->estado ?>" <?= $xxselected ?>><?= $dados->nome ?></option>
						<?php
						} ?>
					</select>
				</div>
			</div>
		</div>
		<div class="span4">
            <div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
                 <label class='control-label' for='cidade'>Cidade</label><br />
                 <div class='controls controls-row input-append'>
                    <select name='cidade' id='cidade' class='frm'>
                    	<option value="">Selecione...</option>
						<?php

						if ((strlen($estado_bd) > 0 && strlen($_POST["cidade"]) == 0) ) {
							$dadosCidades = buscaCidade($estado_bd ,'BR');
							foreach ($dadosCidades["cidades"] as $key => $xcidade) {
								$selected = ($xcidade == $cidade) ? "selected" : "";
							?>
							<option value="<?= $xcidade ?>" <?= $selected ?>><?= $xcidade ?></option>
						<?php
						}} ?>
                    </select>
                </div>
            </div>
        </div>
		<div class='span2'></div>
	</div>
	<?php }?>
	<p><br/>
		<?php if ($login_fabrica == 195 && isset($mao_obra_servico_realizado) && strlen($mao_obra_servico_realizado) > 0) {?>
			<input type='submit' class='btn' id="btn_acao_recusada" name='btn_acao_recusada' value='Alterar' />
		<?php } else {?>
			<input type='submit' class='btn' id="btn_acao_recusada" name='btn_acao_recusada' value='Gravar' />
		<?php }?>
	</p><br/>
</form>

<?php } ?>

<?php if(pg_num_rows($resDados) > 0){ ?>
	<table id="resultado_cadastro_mao_de_obra" class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr class='titulo_coluna' >
                <?php if ($login_fabrica != 195) {?>
				<th>Tipo Posto</th>
				<th>Serviço Realizado</th>
				<th>Produto</th>
				<th>Familia</th>
				<?php }?>

                <th>Tipo Atendimento</th>
                <?php if ($login_fabrica == 195) {?>
				<th>Cidade</th>
				<th>Estado</th>
                <?php }?>
				<th>Mão de Obra</th>
				<th>Ação</th>
			</tr>
		</thead>
		<tbody>
	<?php
		for ($i=0; $i < pg_num_rows($resDados); $i++) {
			$xreferencia_descricao = "";
			$xservico_realizado = pg_fetch_result($resDados, $i, 'servico_realizado');
			$xtipo_posto = pg_fetch_result($resDados, $i, 'tipo_posto');
			$xmao_de_obra = pg_fetch_result($resDados, $i, 'mao_de_obra');
			$xmao_obra_servico_realizado = pg_fetch_result($resDados, $i, 'mao_obra_servico_realizado');
			$xfamilia = pg_fetch_result($resDados, $i, 'familia');
			$xtipo_atendimento = pg_fetch_result($resDados, $i, 'tipo_atendimento');
			$xproduto_descricao = pg_fetch_result($resDados, $i, 'produto_descricao');
			$xproduto_referencia = pg_fetch_result($resDados, $i, 'produto_referencia');

			if ($login_fabrica == 195) {
				$xparametrosAdd = json_decode(pg_fetch_result($resDados, $i, 'parametros_adicionais'),1);
				$xcidade = $xparametrosAdd["cidade"];
				$xestado = $xparametrosAdd["estado"];
			}
			if(strlen(trim($xproduto_referencia)) > 0 AND strlen(trim($xproduto_descricao)) > 0){
				$xreferencia_descricao = $xproduto_referencia.' - '.$xproduto_descricao;
			}

			if(strlen($xtipo_atendimento) > 0){
				unset($xservico_realizado);
			}

	?>
			<tr id='<?=$xmao_obra_servico_realizado?>'>
				<?php if ($login_fabrica != 195) {?>
				<td class='tal'><?=$xtipo_posto?></td>
				<td class='tal'><?=$xservico_realizado?></td>
				<td class='tal'><?=$xreferencia_descricao?></td>
				<td class='tal'><?=$xfamilia?></td>
				<?php }?>
				<td class='tal'><?=$xtipo_atendimento?></td>
				<?php if ($login_fabrica == 195) {?>
				<td class='tal'><?=$xcidade?></td>
				<td class='tal'><?=$xestado?></td>
				<?php }?>
				<td class='tal'>R$ <? echo priceFormat($xmao_de_obra);?></td>
				<td class='tac'>
					<?php if ($login_fabrica == 195) {?>
						<a href="cadastro_mao_obra_new.php?mao_obra_servico_realizado=<?=$xmao_obra_servico_realizado?>" class='btn btn-primary btn-small'>Alterar</a>
					<?php }?>
					<button type="button" onclick="excluir('<?=$xmao_obra_servico_realizado?>')" class='btn btn-danger btn-small'>Excluir</button></td>
			</tr>
	<?php
		}
	?>
		</tbody>
	</table>

	<script>
		$.dataTableLoad({ table: "#resultado_cadastro_mao_de_obra" });
	</script>

</div>

<?php } ?>
<?php include 'rodape.php';?>
