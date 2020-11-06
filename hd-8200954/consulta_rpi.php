<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

use \Posvenda\Fabricas\_169\Os;

$array_estados = $array_estados();
$array_estados = array_map(function($e) {
    return $e;
}, $array_estados);

if ($_REQUEST['pesquisar'] == "Pesquisar") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_serie		= $_POST['produto_serie'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$cnpj_posto			= $_POST['cnpj_posto'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$consumidor_cpf 	= $_POST['consumidor_cpf'];
	$consumidor_estado	= $_POST['consumidor_estado'];
	$consumidor_cidade	= $_POST['consumidor_cidade'];
	$linha              = $_POST['linha'];
	$familia 			= $_POST['familia'];
	$rpi_exportado 		= $_POST['rpi_exportado'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
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

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}

            $sqlX = "SELECT '$aux_data_inicial'::date + interval '3 months' < '$aux_data_final 23:59:59'";
            $res = pg_query($con, $sqlX);
            $periodo_3meses = pg_fetch_result($resX,0,0);

            if ($periodo_3meses == 't'){
            	$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "As datas devem ser no máximo 3 meses";
            }

		}
	}

	if (strlen(trim($produto_serie)) > 0){
		$sql = "
			SELECT
				serie
			FROM tbl_numero_serie
			WHERE fabrica = {$login_fabrica}
			AND (tbl_numero_serie.serie = UPPER('{$produto_serie}') OR tbl_numero_serie.serie = UPPER('S{$produto_serie}'))
		";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0){
			$serie = pg_fetch_result($res, 0, 'serie');
		}else{
			$msg_erro["msg"][]    = "Número de série inválido";
			$msg_erro["campos"][] = "produto_serie";
		}
	}

	if (strlen(trim($produto_referencia)) > 0){		
		$sql = "
			SELECT produto
			FROM tbl_produto
			WHERE fabrica_i = {$login_fabrica}
			AND referencia = UPPER('{$produto_referencia}')
			";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	if ($areaAdmin === true) {

		if (strlen(trim($cnpj_posto)) > 0 AND (strlen(trim($codigo_posto)) == 0 OR strlen(trim($descricao_posto)) == 0)){
			$msg_erro["msg"][]    	= "Posto não encontrado. Preencha os campos Código Posto/Nome Posto";
			$msg_erro["campos"][] 	= "codigo_posto";
			$$msg_erro["campos"][] 	= "descricao_posto";
		}

		if (strlen(trim($codigo_posto)) > 0 or strlen(trim($descricao_posto)) > 0){
			$sql = "SELECT tbl_posto_fabrica.posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND (
						(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
						OR
						(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
					)";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Posto não encontrado";
				$msg_erro["campos"][] = "posto";
			} else {
				$posto = pg_fetch_result($res, 0, "posto");
			}
		}

 		if (strlen(trim($consumidor_cpf)) > 0){
			$consumidor_cpf = preg_replace("/\D/", "", $consumidor_cpf);
            $sql = "SELECT fn_valida_cnpj_cpf('$consumidor_cpf')";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
            	$msg_erro["msg"][]    = "CPF inválido";
				$msg_erro["campos"][] = "consumidor_cpf";
			}
    	}

		if (strlen(trim($consumidor_estado)) > 0){
			$sql = "SELECT estado FROM tbl_cidade WHERE estado = '{$consumidor_estado}'";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 0){
				$msg_erro["msg"][]    = "Estado não encontrado";
				$msg_erro["campos"][] = "consumidor_estado";
			}
		}

		if (strlen(trim($linha)) > 0) {
			$sql = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND linha = {$linha}";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Linha não encontrada";
				$msg_erro["campos"][] = "linha";
			}
		}

		if (strlen(trim($familia))) {
			$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Familia não encontrada";
				$msg_erro["campos"][] = "familia";
			}
		}
	}

	if ($rpi_exportado == "true") {
		$cond_rpi_exportado = " AND tbl_rpi.exportado IS NOT TRUE ";
	} else if ($rpi_exportado == "false") {
		$cond_rpi_exportado = " AND tbl_rpi.exportado IS NULL ";
	} else {
		$cond_rpi_exportado = "";
	}

	if (!count($msg_erro["msg"])) {
		$cond = "";

		if (!empty($serie)){ $cond .= " AND tbl_rpi_produto.serie = '{$serie}' "; }

		if (!empty($produto)){ $cond .= " AND tbl_rpi_produto.produto = {$produto} "; }

		if (!empty($posto)){ $cond .= " AND tbl_rpi.posto = {$posto} "; }

		if (!empty($consumidor_cpf)){ $cond .= " AND tbl_rpi.consumidor_cpf = '{$consumidor_cpf}' "; }

		if (!empty($consumidor_estado)){ $cond .= " AND tbl_cidade.estado = '{$consumidor_estado}' "; }

		if (!empty($consumidor_cidade)){ $cond .= " AND tbl_rpi.consumidor_cidade = {$consumidor_cidade} "; }

		if (!empty($linha)){ $cond .= " AND tbl_produto.linha = {$linha} "; }

		if (!empty($familia)){ $cond .= " AND tbl_produto.familia = {$familia} "; }

		$sql = "
			SELECT
				tbl_posto_fabrica.codigo_posto 				AS codigo_posto,
				tbl_posto.nome 								AS descricao_posto,
				TO_CHAR(tbl_rpi.data_partida, 'DD/MM/YYYY') AS data_partida,
				TO_CHAR(tbl_rpi.data_input, 'DD/MM/YYYY')   AS data_cadastro,
				tbl_rpi.consumidor_nome 					AS consumidor_nome,
				tbl_cidade.estado 							AS consumidor_estado,
				tbl_cidade.nome 							AS consumidor_cidade,
				tbl_produto.referencia 						AS produto_referencia,
				tbl_produto.descricao 						AS produto_descricao,
				tbl_rpi_produto.serie 						AS produto_serie,
				tbl_rpi.responsavel 						AS responsavel,
				tbl_rpi.rpi 								AS id_rpi,
				tbl_rpi.responsavel_funcao 					AS funcao,
				tbl_rpi.consumidor_cpf 						AS consumidor_cpf,
				tbl_rpi.consumidor_cep 						AS consumidor_cep,
				tbl_rpi.consumidor_bairro                   AS consumidor_bairro,
				tbl_rpi.consumidor_endereco					AS consumidor_endereco,
				tbl_rpi.consumidor_numero					AS consumidor_numero,
				tbl_rpi.consumidor_complemento				AS consumidor_complemento,
				tbl_rpi.consumidor_telefone					AS consumidor_telefone,
				tbl_rpi.consumidor_contato					AS consumidor_contato,
				tbl_rpi.obs 								AS observacao,
				tbl_rpi.exportado 							AS exportado,
				tbl_rpi.exportado_erro 						AS exportado_erro,
				tbl_rpi.cancelado							AS cancelado,
				tbl_linha.nome 								AS linha,
				tbl_familia.descricao						AS familia
			FROM tbl_rpi
			JOIN tbl_rpi_produto ON tbl_rpi_produto.rpi = tbl_rpi.rpi AND tbl_rpi_produto.fabrica = {$login_fabrica}
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_rpi.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			JOIN tbl_posto ON tbl_posto.posto = tbl_rpi.posto
			JOIN tbl_cidade ON tbl_cidade.cidade = tbl_rpi.consumidor_cidade
			JOIN tbl_produto ON tbl_produto.produto = tbl_rpi_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
			JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
			WHERE tbl_rpi.fabrica = {$login_fabrica}
			AND tbl_rpi.data_partida BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
			AND tbl_rpi.cancelado IS NULL
			$cond
			$cond_rpi_exportado
			ORDER BY tbl_rpi.rpi ASC;
		";

		$resSubmit = pg_query($con, $sql);

		if ($_POST["gerar_excel"]) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_consulta_rpi-{$data}.csv";
			$file = fopen("/tmp/{$fileName}", "w");

			$thead = "RPI Número;Código Posto;Nome Posto;Data Cadastro;Data Partida;Responsável;Função;Ref. Produto;Desc. Produto;Série;Linha;Familia;Consumidor Nome;Consumidor CPF;Consumidor Contato;Consumidor Telefone;Consumidor Estado;Consumidor Cidade;Consumidor Cep;Consumidor Endereço;Consumidor Número;Consumidor Bairro;Consumidor Complemento;Observação \n";
			$thead = utf8_encode($thead);
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				unset($codigo_posto);
				unset($descricao_posto);
				unset($data_partida);
				unset($data_cadastro);
				unset($consumidor_nome);
				unset($consumidor_estado);
				unset($consumidor_cidade);
				unset($produto_referencia);
				unset($produto_descricao);
				unset($produto_serie);
				unset($responsavel);
				unset($funcao);
				unset($consumidor_cpf);
				unset($consumidor_cep);
				unset($consumidor_bairro);
				unset($consumidor_endereco);
				unset($consumidor_numero);
				unset($consumidor_complemento);
				unset($consumidor_telefone);
				unset($consumidor_contato);
				unset($observacao);
				unset($linha);
				unset($familia);

				$numero_rpi 				= pg_fetch_result($resSubmit, 0, 'id_rpi');
				$codigo_posto 				= utf8_encode(pg_fetch_result($resSubmit, $i, 'codigo_posto'));
				$descricao_posto 			= utf8_encode(pg_fetch_result($resSubmit, $i, 'descricao_posto'));
				$data_partida 				= pg_fetch_result($resSubmit, $i, 'data_partida');
				$data_cadastro 				= pg_fetch_result($resSubmit, $i, 'data_cadastro');
				$consumidor_nome 			= utf8_encode(pg_fetch_result($resSubmit, $i, 'consumidor_nome'));
				$consumidor_estado 			= pg_fetch_result($resSubmit, $i, 'consumidor_estado');
				$consumidor_cidade 			= utf8_encode(pg_fetch_result($resSubmit, $i, 'consumidor_cidade'));
				$produto_referencia 		= utf8_encode(pg_fetch_result($resSubmit, $i, 'produto_referencia'));
				$produto_descricao 			= utf8_encode(pg_fetch_result($resSubmit, $i, 'produto_descricao'));
				$produto_serie 				= pg_fetch_result($resSubmit, $i, 'produto_serie');
				$responsavel 				= utf8_encode(pg_fetch_result($resSubmit, $i, 'responsavel'));
				$funcao 					= utf8_encode(pg_fetch_result($resSubmit, $i, 'funcao'));
				$consumidor_cpf 			= pg_fetch_result($resSubmit, $i, 'consumidor_cpf');
				$consumidor_cep 			= pg_fetch_result($resSubmit, $i, 'consumidor_cep');
				$consumidor_bairro 			= utf8_encode(pg_fetch_result($resSubmit, $i, 'consumidor_bairro'));
				$consumidor_endereco 		= utf8_encode(pg_fetch_result($resSubmit, $i, 'consumidor_endereco'));
				$consumidor_numero 			= pg_fetch_result($resSubmit, $i, 'consumidor_numero');
				$consumidor_complemento 	= utf8_encode(pg_fetch_result($resSubmit, $i, 'consumidor_complemento'));
				$consumidor_telefone 		= pg_fetch_result($resSubmit, $i, 'consumidor_telefone');
				$consumidor_contato 		= utf8_encode(pg_fetch_result($resSubmit, $i, 'consumidor_contato'));
				$observacao 				= utf8_encode(pg_fetch_result($resSubmit, $i, 'observacao'));
				$linha 						= utf8_encode(pg_fetch_result($resSubmit, $i, 'linha'));
				$familia 					= utf8_encode(pg_fetch_result($resSubmit, $i, 'familia'));

				$result .= $numero_rpi.';'.$codigo_posto.';'.$descricao_posto.';'.$data_cadastro.';'.$data_partida.';'.$responsavel.';'.$funcao.';'.$produto_referencia.';'.$produto_descricao.';'.$serie.';'.$linha.';'.$familia.';'.$consumidor_nome.';'.$consumidor_cpf.';'.$consumidor_contato.';'.$consumidor_telefone.';'.$consumidor_estado.';'.$consumidor_cidade.';'.$consumidor_cep.';'.$consumidor_endereco.';'.$consumidor_numero.';'.$consumidor_bairro.';'.$consumidor_complemento.';'.$observacao."\n";
			}

			fwrite($file, $result);

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}

			exit;
		}
	}
}

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade, cidade AS cidade_id FROM tbl_cidade WHERE estado = UPPER('{$estado}') ORDER BY cidade ASC;";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades 		= array();
            $array_cidades_id 	= array();

            $i = 0;
            while ($result = pg_fetch_object($res)) {
                $array_cidades[$i]['cidade']= $result->cidade;
	    		$array_cidades[$i]['cidade_id']= $result->cidade_id;
                $i++;
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

if (isset($_REQUEST['ajax_exportar_rpi'])) {

	$className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
    $classOs = new $className($login_fabrica, null, $con);

	$rpi = $_REQUEST['rpi'];
	$serie = $_REQUEST['serie'];

	try {
		$dadosRPI = $classOs->getDadosRPIExport($rpi, $serie);
		$exportRPI = $classOs->exportRPI($dadosRPI);

		if ($exportRPI !== true) {
			throw new Exception("O RPI não foi exportado");
		}

		$retorno = array("success" => utf8_encode("RPI exportado com sucesso"));
	} catch(Exception $e) {
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));

}

$title = "CONSULTA DE RPI";
if ($areaAdmin === true) {
	$layout_menu = 'callcenter';
	include __DIR__.'/admin/cabecalho_new.php';
} else {
    $layout_menu = 'os';
    include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "dataTable"
);
include __DIR__.'/admin/plugin_loader.php'; ?>

<script type="text/javascript">
$(function() {

	$("#xgerar_excel").click(function () {
		var json = $.parseJSON($("#jsonPOST").val());
		json["gerar_excel"] = true;

		$.ajax({
			url: "<?=$_SERVER['PHP_SELF']?>",
			type: "POST",
			data: json,
			complete: function (data) {
				window.open(data.responseText, "_blank");
			}
		});
	});

	/**
     * Inicia o shadowbox, obrigatório para a lupa funcionar
     */
    Shadowbox.init();
    $.datepickerLoad(Array("data_final", "data_inicial"));


    $("#consumidor_cpf").mask("999.999.999-99",{placeholder:""});

    $("#form_submit").on("click", function(e) {
        e.preventDefault();

        var submit = $(this).data("submit");
        if (submit.length == 0) {
            $(this).data({ submit: true });
            $("input[name=pesquisar]").val('Pesquisar');
            $(this).parents("form").submit();
        } else {
           alert("Não clique no botão voltar do navegador, utilize somente os botões da tela");
        }
    });

    $("#cnpj_posto").mask("99.999.999/9999-99",{placeholder:""});

    $(document).on("click", "span[rel=lupa_produto]", function() {
        var parametros_lupa_produto = ["posto", "ativo", "posicao", "codigo_validacao_serie"];

        $.lupa($(this), parametros_lupa_produto);
    });

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});
});

function retorna_posto(retorno){
	$("#cnpj_posto").val(retorno.cnpj).mask("99.999.999/9999-99",{placeholder:""});
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function retorna_produto(retorno) {
 	$("input[name='produto_referencia']").val(retorno.referencia);
    $("input[name='produto_descricao']").val(retorno.descricao);
}

/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado) {
    $("#consumidor_cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url: "consulta_rpi.php",
            type: "POST",
            data: { ajax_busca_cidade: true, estado: estado },
            beforeSend: function() {
                if ($("#consumidor_cidade").next("img").length == 0) {
                    $("#consumidor_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $.each(data.cidades, function(key, value) {
                    	var option = $("<option></option>", { value: value.cidade_id, text: value.cidade });
                    	$("#consumidor_cidade").append(option);
                    });
                }
                $("#consumidor_cidade").show().next().remove();
            }
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0){
        $("#consumidor_cidade option[value='"+cidade+"']").attr('selected','selected');
    }
}

$(document).on('click', 'button.exportar_rpi', function() {
	var that = $(this);
    var rpi = $(this).data('rpi_numero');
    var serie = $(this).data('rpi_serie');

    $.ajax({
        url: "<?= $_SERVER["PHP_SELF"]; ?>",
        type: "POST",
        data: {ajax_exportar_rpi: true, rpi: rpi, serie: serie},
        beforeSend: function() {
            if ($(that).next("img").length == 0) {
                $(that).after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
            }
            $(that).prop("disabled", true);
        },
        complete: function(data) {
        	data = $.parseJSON(data.responseText);

            if (data.error) {
                alert(data.error);
                $(that).prop("disabled", false);
            } else {
                alert(data.success);
                $(that).hide();
            }
            $(that).prev().remove();
            $(that).next().remove();
        }
    });
});

var modal_rpi;
var modal_rpi_erro;
var rpi_numero;
$(document).on('click','button.erro_exportacao', function(){
	modal_rpi    = $("#modal-erro-exportacao");
    modal_rpi_erro = $(this).data("exportado_erro");
    rpi_numero = $(this).data("rpi_numero");

    $(modal_rpi).find("div.modal-body > div.alert").remove();
    $(modal_rpi).find("div.modal-header").html("<h4>RPI Número: "+rpi_numero+"</h4>");
    $(modal_rpi).find("#texto_erro_exportado").html("<p>"+modal_rpi_erro+"</p>");
    $(modal_rpi).modal("show");;
});

$(document).on('click','#btn-close-modal-erro-exportacao', function(){
    $(modal_rpi).modal("hide");
});
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if (strlen(trim($msg_success)) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<form name="frm_os" id="frm_os" method="POST" class="form-search form-inline tc_formulario" enctype="multipart/form-data" >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span1'></div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial(Data Partida)</label>
					<div class='controls controls-row'>
						<div class='span6'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span3'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final(Data Partida)</label>
				<div class='controls controls-row'>
					<div class='span6'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span1'></div>
	</div>

	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span3">
            <div class='control-group' <?=(in_array('produto_serie', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="serie">Número de Série</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input name="produto_serie" class="span12 produto_serie" type="text" value="<?=$produto_serie?>" maxlength="30" />

                        <span class="add-on lupa_serie" rel="lupa_produto" style='cursor: pointer;'>
                     		<i class='icon-search'></i>
                        </span>
                        <input
                        	type="hidden"
                        	name="lupa_config"
                        	tipo="produto"
                        	posicao="um_produto"
                        	ativo="t"
                        	parametro="numero_serie"
                        	mascara='true'
                        	codigo_validacao_serie="true"
                        	grupo-atendimento=''
                        	fora-garantia=''
                        	km-google=''
                        	<?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?>
                    	/>
                    </div>
                </div>
            </div>
        </div>


		<div class='span3'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'>Ref. Produto</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
						<span class='add-on' rel="lupa_produto" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" ativo="t" codigo_validacao_serie="true" mascara='true' grupo-atendimento='' fora-garantia='' km-google=''  />
					</div>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_descricao'>Desc. Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
						<span class='add-on' rel="lupa_produto" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" ativo="t" codigo_validacao_serie="true" mascara='true' grupo-atendimento='' fora-garantia='' km-google=''  />
					</div>
				</div>
			</div>
		</div>
		<div class='span1'></div>
	</div>

	<? if ($areaAdmin === true) { ?>
		<div class='row-fluid'>
			<div class='span1'></div>
			<div class="span3">
	            <div class='control-group' <?=(in_array('cnpj_posto', $msg_erro['campos'])) ? "error" : "" ?>' >
	                <label class="control-label" for="cnpj_posto">CNPJ Posto</label>
	                <div class="controls controls-row">
	                    <div class="span10 input-append">
	                        <input name="cnpj_posto" id="cnpj_posto" class="span12" type="text" value="<?=$cnpj_posto?>" />
	                        <span class="add-on" rel="lupa">
	                             <i class='icon-search'></i>
	                        </span>
	                        <input type="hidden" name="lupa_config" tipo="posto" parametro='cnpj' />
	                    </div>
	                </div>
	            </div>
	        </div>
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
			<div class='span3'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span3">
	        	<div class='control-group <?=(in_array('consumidor_cpf', $msg_erro['campos'])) ? "error" : "" ?>' >
	                <label class="control-label" for="consumidor_cpf">CPF</label>
	                <div class="controls controls-row">
	                    <div class="span12 input-append">
	                        <input id="consumidor_cpf" name="consumidor_cpf" class="span11" type="text" value="<?=$consumidor_cpf?>"/>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class="span3">
	            <div class="control-group <?=(in_array('consumidor_estado', $msg_erro['campos'])) ? "error" : "" ?>">
	                <label class="control-label" for="consumidor_estado">Estado</label>
	                <div class="controls controls-row">
	                    <div class="span11">
	                       <select id="consumidor_estado" onchange="busca_cidade(this.value)" name="consumidor_estado" class="span12" >
	                            <option value="" >Selecione</option>
	                            <?php
	                            foreach ($array_estados as $sigla => $nome_estado) {
	                                $selected = ($sigla == $consumidor_estado) ? "selected" : "";
	                                echo "<option value='{$sigla}' {$selected} >" . utf8_decode($nome_estado) . "</option>";
	                            }
	                            ?>
	                        </select>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class="span3">
	            <div class="control-group <?=(in_array('consumidor_cidade', $msg_erro['campos'])) ? "error" : "" ?>">
	                <label class="control-label" for="consumidor_cidade">Cidade</label>
	                <div class="controls controls-row">
	                    <div class="span12">
	                        <select id="consumidor_cidade" name="consumidor_cidade" class="span12" />
	                            <option value="" >Selecione</option>
	                            <?php
	                            if (strlen($consumidor_estado) > 0) {
	                            	$sql = "
	                            		SELECT
	                        				UPPER(fn_retira_especiais(nome)) AS cidade,
	                        				cidade AS cidade_id
	                    				FROM tbl_cidade
	                    				WHERE estado = UPPER('".$consumidor_estado."')
	                                    ORDER BY cidade ASC";
	                                $res = pg_query($con, $sql);
	                                if (pg_num_rows($res) > 0) {

	                                    while ($result = pg_fetch_object($res)) {
	                                        $selected  = (trim($result->cidade_id) == trim($consumidor_cidade)) ? "SELECTED" : "";

	                                        echo "<option value='{$result->cidade_id}' {$selected} >{$result->cidade} </option>";
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
			<div class='span3'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>
						<div class='span11'>
							<select name="linha" id="linha" class="span12">
								<option value=""></option>
								<?php
								$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo;";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ; ?>
									<option value="<?= $key['linha']?>" <?= $selected_linha ?>><?= $key['nome']?></option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'>Familia</label>
					<div class='controls controls-row'>
						<div class='span11'>
							<select name="familia" id="familia" class="span12">
								<option value=""></option>
								<?php
								$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo ORDER BY descricao";
								$res = pg_query($con,$sql);
								foreach (pg_fetch_all($res) as $key) {
									$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ; ?>
									<option value="<?= $key['familia']?>" <?= $selected_familia ?> ><?= $key['descricao']?></option>
								<?php
								}
								?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
	<? } ?>
    <div class='row-fluid'>
    	<div class='tac'>
    		<p><br/>
				<input type='hidden' name="pesquisar" />
    			<input type="button" class="btn" value="Pesquisar" id="form_submit" data-submit="" />
			</p><br/>
    	</div>
    </div>
</form>
</div>

<? if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) { ?>
		<center>
			<table id="resultado_consulta_rpi" align="center" class='table table-bordered' >
				<thead>
					<tr class='titulo_coluna' >
						<th>RPI Número</th>
						<th>Código posto</th>
						<th>Nome posto</th>
						<th>Data partida</th>
	                    <th>Consumidor nome</th>
	                    <th>Consumidor estado</th>
						<th>Consumidor cidade</th>
						<th>Produto/Série</th>
						<?php if ($areaAdmin === true){ ?>
							<th nowrap>Ações</th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php

					while ($result = pg_fetch_object($resSubmit)) {
						$xx = "";
						$codigo_posto           = $result->codigo_posto;
						$descricao_posto        = $result->descricao_posto;
						$data_partida           = $result->data_partida;
						$consumidor_nome        = $result->consumidor_nome;
						$consumidor_estado      = $result->consumidor_estado;
						$consumidor_cidade      = $result->consumidor_cidade;
						$id_rpi                 = $result->id_rpi;
						$exportado              = $result->exportado;
						$exportado_erro         = $result->exportado_erro;
						$produto_referencia     = $result->produto_referencia;
						$produto_descricao      = $result->produto_descricao;
						$produto_serie          = $result->produto_serie; ?>
						<tr id='<?=$id_rpi?>'>
							<td class='tac'><?=$id_rpi?></td>
							<td><?=$codigo_posto?></td>
							<td><?=$descricao_posto?></td>
							<td><?=$data_partida?></td>
							<td><?=$consumidor_nome?></td>
							<td class='tac'><?=$consumidor_estado?></td>
							<td><?=$consumidor_cidade?></td>
							<td><?= '<b>Ref:</b> '.$produto_referencia.' <b>Descr: </b>'.$produto_descricao.' <b>Série: </b>'.$produto_serie.'<br/><br/>'; ?></td>
							<? if ($areaAdmin === true) { ?>
								<td nowrap>
									<? if (empty($exportado) && !empty($exportado_erro)) { ?>
										<button type="button" class="btn btn-small erro_exportacao" data-rpi_numero='<?=$id_rpi?>' data-exportado_erro='<?=$exportado_erro?>'>Ver erro integração</button>
									<? }
									if (empty($exportado)) { ?>
										<button type="button" class="btn btn-primary btn-small exportar_rpi" data-rpi_numero="<?= $id_rpi; ?>" data-rpi_serie="<?= $produto_serie; ?>">Exportar</button>
									
									<?	if (in_array($login_fabrica, [169,170]) && $areaAdmin) { ?>
											<a href="cadastro_rpi.php?rpi=<?=$id_rpi?>" target='_blank'"><button type="button" class="btn btn-primary btn-small">Alterar</button></a>
									<?  } 
									} ?>
								</td>
							<? } ?>
						</tr>
					<? } ?>
				</tbody>
			</table>

			<div id="modal-erro-exportacao" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
		        <div class="modal-header">
		        </div>
		        <div class="modal-body">
		            <div class="row-fluid" >
		                <div class="span12" >
		                    <div class="control-group" >
		                        <div class="controls controls-row">
		                            <div class="span12" id='texto_erro_exportado'>
		                            </div>
		                        </div>
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class="modal-footer">
		            <button type="button" id="btn-close-modal-erro-exportacao" class="btn">Fechar</button>
		        </div>
		    </div>
			<? $jsonPOST = excelPostToJson($_POST); ?>
			<div id='xgerar_excel' class="btn_excel" style="width:200px">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
				<? if ($areaAdmin === true) { ?>
			    	<span><img style="width:40px; height:40px;" src='imagens/icon_csv.png' /></span>
			    <? } else { ?>
			    	<span><img style="width:40px; height:40px;" src='admin/imagens/icon_csv.png' /></span>
			    <? } ?>
			</div>
		</center>
		<script>
			$.dataTableLoad({ table: "#resultado_consulta_rpi" });
		</script>
	<? } else { ?>
		<div class="container">
			<div class="alert">
			    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<? }
}
include 'rodape.php'; ?>