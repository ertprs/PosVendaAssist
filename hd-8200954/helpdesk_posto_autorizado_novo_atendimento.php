<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

// define('DEBUG', true);

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/autentica_usuario.php';
}

if ($_serverEnvironment == 'development' and DEBUG===true) {
   ini_set("display_errors", 1);
   error_reporting(E_ERROR | E_WARNING);
}

include __DIR__.'/funcoes.php';

include_once __DIR__."/helpdesk_posto_autorizado/regras.php";

if (file_exists(__DIR__."/helpdesk_posto_autorizado/{$login_fabrica}/regras.php")) {
	include_once __DIR__."/helpdesk_posto_autorizado/{$login_fabrica}/regras.php";
}

// Preparando os anexos: classes, funções, config...
include_once "class/aws/s3_config.php";
include_once S3CLASS;
include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';

$s3    = new AmazonTC("helpdesk_pa", $login_fabrica);
$tDocs = new TDocs($con, $login_fabrica);

if (in_array($login_fabrica, array(30))) {
    $ordem_de_servico_troca = $_GET['ordem_de_servico'];
    $callcenter_de_troca = $_GET['callcenter'];
}

if(in_array($login_fabrica, [35])){
    $pecaHistorico = $_REQUEST['pecas'];
}

if ($fabricaFileUploadOS) {
    if (!empty($hd_chamado_item)) {
        $tempUniqueId = $hd_chamado_item;
        $anexoNoHash = null;
    } else if (strlen(getValue("anexo_chave")) > 0) {
        $tempUniqueId = getValue("anexo_chave");
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

if (isset($_POST['ajax_anexo_upload'])) {
    $posicao = $_POST['anexo_posicao'];
    $arquivo = $_FILES["anexo_upload"];

	// $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
	$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

	if ($ext == 'jpeg') {
		$ext = 'jpg';
	}

	if (strlen($arquivo['tmp_name']) > 0) {
		if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
			$retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpg, bmp, pdf, doc, docx'));
		} else {

			// Se enviou um outro arquivo, este substitui o anterior
			if ($_FILES['anexo_upload']['tmp_name']) {

				$anexoID[$posicao] = $tDocs->sendFile($_FILES['anexo_upload']);
				$arquivo_nome      = json_encode($tDocs->sentData);

				if (!$anexoID[$posicao]) {
					$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
				} else {
					// Se ocorrer algum erro, o anexo está salvo:
					if (isset($idExcluir)) {
						$tDocs->deleteFileById($idExcluir);
					}
				}
			}

			if (empty($anexoID[$posicao])) {
				$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
			}


			if ($ext == 'pdf') {
				$link = 'imagens/pdf_icone.png';
			} else if(in_array($ext, array('doc', 'docx'))) {
				$link = 'imagens/docx_icone.png';
			} else {
				$link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID[$posicao];

			}

			$href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID[$posicao];

			if (!strlen($link)) {
				$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
			} else {
				$retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao');
			}
		}
	} else {
		$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
	}

    $retorno['posicao'] = $posicao;
	exit(json_encode($retorno));
}

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'consulta_posto_sac') {
        if($login_fabrica == 30){
            $codigo_posto = $_POST['codigo'];
        }elseif($login_fabrica == 72){
            $codigo_posto = '1047133';
        }
        $sql = "SELECT tbl_posto_fabrica.posto, tbl_posto.nome FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
        $res = pg_query($con, $sql);
        exit(json_encode(array("ok" => array("codigo_posto" => $codigo_posto, "codigo" => pg_fetch_result($res, 0, 'posto'), "nome" => pg_fetch_result($res, 0, 'nome')))));
    }elseif ($_POST['action'] == 'tipo_solicitacao') {
        $where = " AND ( codigo IS NULL OR (codigo IS NOT NULL AND codigo <> 'I'))";
        
        if (!in_array($login_fabrica, [198])) {            
            if ($_POST['checked'] == "true") {
                $where = "AND codigo = 'I'";
            }
        }

        $sql = "
            SELECT
                tipo_solicitacao,
                descricao,
                informacoes_adicionais,
                campo_obrigatorio
            FROM tbl_tipo_solicitacao
            WHERE fabrica = {$login_fabrica} $where
            AND ativo IS TRUE
        ";
        $res = pg_query($con, $sql);

        $array_tipos_solicitacoes = array();

        while ($tipo_solicitacao = pg_fetch_object($res)) {
            $array_tipos_solicitacoes[$tipo_solicitacao->tipo_solicitacao] = array(
                "label" => utf8_encode($tipo_solicitacao->descricao),
                "informacoes_adicionais" => json_decode($tipo_solicitacao->informacoes_adicionais, true),
                "campos_obrigatorios" => json_decode($tipo_solicitacao->campo_obrigatorio, true)
            );
        }

        exit(json_encode(array("ok" => $array_tipos_solicitacoes)));
    }
}

if (filter_input(INPUT_POST,"ajax_verifica_os_posto",FILTER_VALIDATE_BOOLEAN)) {
	$os    = filter_input(INPUT_POST,"os");
	$posto = filter_input(INPUT_POST,"posto");

	try {
		if (empty($os)) {
			throw new Exception("Ordem de Serviço não informada");
		}

		if (empty($posto)) {
			throw new Exception("Posto Autorizado não informado");
		}

        $sql = "
            SELECT  tbl_os.os               ,
                    CASE WHEN tbl_os.consumidor_nome <> ''
                         THEN tbl_os.consumidor_nome
                         ELSE tbl_revenda.nome
                    END                             AS consumidor_nome,
                    tbl_produto.produto     ,
                    tbl_produto.referencia  ,
                    tbl_produto.descricao
            FROM    tbl_os
            JOIN    tbl_produto     USING(produto)
       LEFT JOIN    tbl_revenda USING(revenda)
            WHERE   fabrica = {$login_fabrica}
            AND     posto   = {$posto}
            AND     sua_os  = '{$os}'";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res) ) {
			throw new Exception("OS não encontrada para o Posto Autorizado");
		}

		$result = pg_fetch_all($res);
		$result[0] = array_map('utf8_encode',$result[0]);
		$retorno = array(
            "sucesso" => true,
            "resultado" => $result
        );
	} catch(Exception $e) {
		$retorno = array("erro" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

if (isset($_GET["ajax_verifica_pedido_posto"])) {
	$pedido = $_GET["pedido"];
	$posto  = $_GET["posto"];

	try {
		if (empty($pedido)) {
			throw new Exception("Pedido não informado");
		}

		if (empty($posto)) {
			throw new Exception("Posto Autorizado não informado");
		}

		$sql = "SELECT pedido
				FROM tbl_pedido
				WHERE fabrica = {$login_fabrica}
				AND posto = {$posto}
				AND pedido = {$pedido}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res) ) {
			throw new Exception("Pedido não encontrado para o Posto Autorizado");
		}

		$retorno = array("sucesso" => true);
	} catch(Exception $e) {
		$retorno = array("erro" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

if($login_fabrica == 35){
    if(isset($_GET['busca_info_tipo_solicitacao'])){
        $tipo_solicitacao = $_GET['tipo_solicitacao'];
        
        $sql_info_documentos = " select observacao from tbl_info_tipo_solicitacao where visivel = true and tipo_solicitacao = $tipo_solicitacao and fabrica = $login_fabrica";
        $res_info_documentos = pg_query($con, $sql_info_documentos);

        for($a=0; $a<pg_num_rows($res_info_documentos); $a++){
            $observacao = pg_fetch_result($res_info_documentos, $a, observacao);
            $texto_obs .= $observacao."\n\n";
        }
        echo $texto_obs;
        exit;
    }
}

if(in_array($login_fabrica, [30,35,72,175,203]) && $areaAdmin !== true && filter_input(INPUT_GET,'os_abertura')){

    $os_abertura = filter_input(INPUT_GET,'os_abertura');

    $sqlProd = "
            SELECT  tbl_os.sua_os AS sua_os_abertura,
            		consumidor_revenda, 
                    tbl_os.consumidor_nome,
                    tbl_os.revenda_nome,
                    tbl_produto.produto     ,
                    tbl_produto.referencia  ,
                    tbl_produto.descricao
            FROM    tbl_os
            JOIN    tbl_produto     USING(produto)
            WHERE   fabrica = $login_fabrica
            AND     os = $os_abertura
    ";
//     exit(nl2br($sqlProd));
    $resProd = pg_query($con,$sqlProd);

    $sua_os_abertura        = pg_fetch_result($resProd,0,sua_os_abertura);
    $produto['id']          = pg_fetch_result($resProd,0,produto);
    $produto['referencia']  = pg_fetch_result($resProd,0,referencia);
    $produto['descricao']   = pg_fetch_result($resProd,0,descricao);
    $consumidor_nome        = pg_fetch_result($resProd,0,consumidor_nome);
    $consumidor_revenda     = pg_fetch_result($resProd,0,consumidor_revenda);
	$revenda_nome           = pg_fetch_result($resProd,0,revenda_nome);

	if($consumidor_revenda == 'R') $cliente = $revenda_nome;
	if($consumidor_revenda == 'C' or empty($cliente)) $cliente = $consumidor_nome;
}



include "helpdesk_posto_autorizado/helpdesk.php";

if ($areaAdmin === true) {
	$layout_menu = "callcenter";
} else {
	$layout_menu = "os";
}

$title = (!in_array($login_fabrica, [169,170])) ? "Helpdesk do Posto Autorizado" : "Helpdesk de Suporte Técnico";

$title = (in_array($login_fabrica, [198])) ? "Help-desk Interno" : $title;

if ($areaAdmin === true) {
	include __DIR__.'/admin/cabecalho_new.php';
} else {
	include __DIR__.'/cabecalho_new.php';
}

if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
}

$plugins = array(
	"shadowbox",
	"ckeditor",
	"select2",
	"ajaxform",
	"alphanumeric"
);

include __DIR__.'/admin/plugin_loader.php';

if ($login_fabrica == 30) {
    $where = " AND (codigo IS NULL OR (codigo IS NOT NULL AND codigo <> 'I'))";
}


$sqlTipoSolicitacao = "
	SELECT
		tipo_solicitacao,
		descricao,
		informacoes_adicionais,
		campo_obrigatorio
	FROM tbl_tipo_solicitacao
	WHERE fabrica = {$login_fabrica} {$where}
	AND ativo IS TRUE
";
$resTipoSolicitacao = pg_query($con, $sqlTipoSolicitacao);
$array_tipos_solicitacoes = array();

while ($tipo_solicitacao = pg_fetch_object($resTipoSolicitacao)) {

    
	$array_tipos_solicitacoes[$tipo_solicitacao->tipo_solicitacao] = array(
		"label" => utf8_encode($tipo_solicitacao->descricao),
		"informacoes_adicionais" => json_decode($tipo_solicitacao->informacoes_adicionais, true),
		"campos_obrigatorios" => json_decode($tipo_solicitacao->campo_obrigatorio, true)
	);
}

?>

<style>

span.select2 {
	width: 100% !important;
}

div.col-informacao-adicional.visible {
	display: inline-block !important;
}

div.col-informacao-adicional.not-visible {
	display: none !important;
}

#cke_descricao_atendimento {
	margin: 0 auto;
}

<?php if ($login_fabrica != 151 && !$fabricaFileUploadOS) { ?>
div[id^=div_anexo] {display:inline-block}
div[id^=div_anexo].oculto {display: none}
<?php } ?>

#div_informacoes_adicionais, #div_trocar_posto, #div_trocar_produto {
	display: none;
}
.select2-results__option {
    padding: 4px;
    font-size: 10px;
    text-align: left;
}

</style>
<script type='text/javascript' src='js/FancyZoom.js'></script>
<script type='text/javascript' src='js/FancyZoomHTML.js'></script>
<script type='text/javascript'>

var F = <?=$login_fabrica?>;
var array_tipos_solicitacoes = JSON.parse('<?=json_encode($array_tipos_solicitacoes)?>');

var array_informacoes_adicionais = [
	"os_posto",
	"produto_os",
	"hd_chamado_sac",
	"nome_cliente",
	"num_pedido",
	"pedido_pend",
	"motivo",
    "ticket_atendimento",
	"cod_localizador",
	"pre_logistica"
];

<?php if (in_array($login_fabrica, array(30,72,198))) { ?>
    function atualiza_tipo_solicitacao(){
        var checked = $('input[name="posto[chamado_interno]"]').is(':checked');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { ajax: 'sim', action: 'tipo_solicitacao', checked: checked },
            timeout: 5000
        }).fail(function(){
            alert('Ocorreu um erro ao tentar carregar o(s) tipo(s) de solicitação');
        }).done(function(data){
            var tipo_solicitacao = '<?=getValue("tipo_solicitacao")?>';

            data = JSON.parse(data);
            if (data.ok !== undefined) {
                array_tipos_solicitacoes = data.ok;

                var tipo_solicitacao_html = '<option value="">Selecione</option>';
                jQuery.each(data.ok, function(index, val){
                    tipo_solicitacao_html += "<option value='"+index+"'>"+val.label+"</option>";
                });
                $('#tipo_solicitacao').html(tipo_solicitacao_html).select2("val", tipo_solicitacao);
            }
        });

        <?php if (!in_array($login_fabrica, [198])) { ?> 
        if (checked) {
            var tipo_solicitacao = $('#tipo_solicitacao').val();

            $('#dias_chamado_interno').show().next().show();
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: { ajax: 'sim', action: 'consulta_posto_sac', codigo: 'CARE1000' },
                timeout: 5000
            }).fail(function(){
                alert('Ocorreu um erro ao tentar carregar o posto autorizado CARE1000');
            }).done(function(data){
                data = JSON.parse(data);
                $("#posto_id").val(data.ok.codigo);
                $("#posto_codigo").val(data.ok.codigo_posto).prop({ readonly: true })
                $("#posto_nome").val(data.ok.nome).prop({ readonly: true });
                $("#posto_codigo, #posto_nome").next("span").hide();
                $("#div_trocar_posto").show();
            });
        }else{
            $('#dias_chamado_interno').hide().next().hide();
            $('#trocar_posto').trigger('click');
        }
        <?php } ?>
    }
<?php } ?>

$(function() {
	Shadowbox.init();

    <?php
    if (in_array($login_fabrica, [30])) { ?>

        $("#tipo_solicitacao").change(function(){

            var tipo_solicitacao_descricao = $(this).find("option:selected").text().toLowerCase();

            if ($('input[name="posto[chamado_interno]"]').is(":checked") && tipo_solicitacao_descricao == "piloto em campo") {

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: { ajax: 'sim', action: 'consulta_posto_sac', codigo: 'CARE3000' },
                    timeout: 5000
                }).fail(function(){
                    alert('Ocorreu um erro ao tentar carregar o posto autorizado CARE3000');
                }).done(function(data){
                    data = JSON.parse(data);
                    $("#posto_id").val(data.ok.codigo);
                    $("#posto_codigo").val(data.ok.codigo_posto).prop({ readonly: true })
                    $("#posto_nome").val(data.ok.nome).prop({ readonly: true });
                    $("#posto_codigo, #posto_nome").next("span").hide();
                    $("#div_trocar_posto").show();
                });

            } else if ($('input[name="posto[chamado_interno]"]').is(":checked")) {

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: { ajax: 'sim', action: 'consulta_posto_sac', codigo: 'CARE1000' },
                    timeout: 5000
                }).fail(function(){
                    alert('Ocorreu um erro ao tentar carregar o posto autorizado CARE1000');
                }).done(function(data){
                    data = JSON.parse(data);
                    $("#posto_id").val(data.ok.codigo);
                    $("#posto_codigo").val(data.ok.codigo_posto).prop({ readonly: true })
                    $("#posto_nome").val(data.ok.nome).prop({ readonly: true });
                    $("#posto_codigo, #posto_nome").next("span").hide();
                    $("#div_trocar_posto").show();
                });

            }

        });

    <?php
    } ?>

    <?php if (in_array($login_fabrica, array(30,72,198))) { ?>
        atualiza_tipo_solicitacao();
        $('input[name="posto[chamado_interno]"]').change(function(){
            atualiza_tipo_solicitacao();
        });
    <?php } ?>

	$("#pedido, #protocolo_atendimento").numeric();
	$("#ordem_de_servico").numeric({ allow: "-"});

	$("span[rel=lupa]").click(function() {
        <?php if ($login_fabrica == 35): ?>
		$.lupa($(this), ['pedido', 'produto']);
        <?php else: ?>
		$.lupa($(this), ['pedido']);
        <?php endif ?>
	});

	$("#tipo_solicitacao, #pecas").select2();

    $("#pecas").on("select2:unselecting", function(e) {
        var id = e.params.args.data.id;
        $(this).find("option[value="+id+"]").remove();
    });

	CKEDITOR.replace("descricao_atendimento", { enterMode: CKEDITOR.ENTER_BR });

	$(document).on('click', 'button.anexar', function() {
		var pos = $(this).val();
		$("form[name=form_anexo_"+pos+"]>input[name=anexo_upload]").click();
	});

	$(document).on('change', 'input[name^=anexo_upload]', function() {
		var btn = '#div_anexo_' + $(this).parent().find('[name=anexo_posicao]').val() + ' button';
		$(btn).button("loading");

		$(this).parent("form").submit();
    });

    $("form[name^=form_anexo]").ajaxForm({
        complete: function(data) {
        	data = JSON.parse(data.responseText);

			if (data.error) {
				alert(data.error);
			} else {
				var divAnexo = $("#div_anexo_" + data.posicao);
				var imagem = $(divAnexo).find("img.anexo_thumb").clone();

				$(imagem).attr({ src: data.link });

				$(divAnexo).find("img.anexo_thumb").remove();

				var linkFoto = $("<a></a>", {
					href: data.href,
					target: "_blank"
				});

				$(linkFoto).html(imagem);

				$(divAnexo).prepend(linkFoto);

				if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
					setupZoom();
				}

		        $(divAnexo).find("input[name^=anexo][rel=anexo]").val(data.arquivo_nome);
			}

			$(divAnexo).find('.anexar').button("reset");
    	}
    });

    $("#trocar_posto").on("click", function() {
    	$("#posto_id").val("");
		$("#posto_codigo").val("").prop({ readonly: false });
		$("#posto_nome").val("").prop({ readonly: false });
		$("#posto_codigo, #posto_nome").next("span").show();
		$("#div_trocar_posto").hide();
        <?php if (in_array($login_fabrica, array(30,72))) { ?>
            $('#dias_chamado_interno').hide().next().hide();
            $('input[name="posto[chamado_interno]"]').prop('checked', false);
        <?php } ?>
    });

    $("#trocar_produto").on("click", function() {
    	$("#produto_id").val("");
		$("#produto_referencia").val("").prop({ readonly: false });
		$("#produto_descricao").val("").prop({ readonly: false });
		$("#produto_referencia, #produto_descricao").next("span").show();
		$("#div_trocar_produto").removeClass("visible").addClass("not-visible");
    });

    <?php
    if ($areaAdmin === true) {
    ?>
    	$("#ordem_de_servico").on("keypress", function() {
    		var posto = $("#posto_id").val();

    		if (posto.length == 0) {
    			alert("Informe o Posto Autorizado para digitar a Ordem de Serviço");
    			$(this).val("");
    			return false;
    		}
    	});

    	$("#pedido").on("keypress", function() {
    		var posto = $("#posto_id").val();

    		if (posto.length == 0) {
    			alert("Informe o Posto Autorizado para digitar o Pedido");
    			$(this).val("");
    			return false;
    		}
    	});
    <?php
    }
    ?>

    $("#ordem_de_servico").on("change", function() {
    	var input = $(this);
    	var posto = $("#posto_id");

    	if (input.val().length > 0) {
    		$.ajax({
    			url: "helpdesk_posto_autorizado_novo_atendimento.php",
    			type: "POST",
    			dataType:"JSON",
    			data: {
    				ajax_verifica_os_posto: true,
    				os: input.val(),
    				posto: posto.val()
    			}
    		}).done(function(data) {
                if (data.erro) {
                    alert(data.erro);
                    input.val("");
                    <?php if ($login_fabrica == 35): ?>
                    $("#produto_referencia").val("");
                    $("#produto_descricao").val("");
                    $("#cliente").val("");
                    $("#tablePecas tbody").html('')
                    <?php endif ?>
                } else {
                    <?php if ($login_fabrica == 35): ?>
                    $("#lupa_peca_config").attr("produto", data.resultado[0].produto);
                    <?php endif ?>
                    $("#produto_id").val(data.resultado[0].produto);
                    $("#produto_referencia").val(data.resultado[0].referencia);
                    $("#produto_descricao").val(data.resultado[0].descricao);
                    $("#cliente").val(data.resultado[0].consumidor_nome);
                }
    		});
    	}
    });

    $("#pedido").on("change", function() {
    	var input = $(this);
    	var posto = $("#posto_id");

    
        $(".peca_pesquisa_lupa").attr("pedido", input.val());       

    	if (input.val() > 0) {
    		$.ajax({
    			url: "helpdesk_posto_autorizado_novo_atendimento.php",
    			type: "get",
    			data: {
    				ajax_verifica_pedido_posto: true,
    				pedido: input.val(),
    				posto: posto.val()
    			}
    		}).done(function(data) {
    			data = JSON.parse(data);

    			if (data.erro) {
    				alert(data.erro);
    				input.val("");
    			}
    		});
    	}
    });

    $("#tipo_solicitacao").on("change", function() {
    	var select = $(this);
    	var div_informacoes_adicionais = $("#div_informacoes_adicionais");
    	var mostrar_campos = [];
    	var campos_obrigatorios = [];
        var tipo_solicitacao = $(this).val();

        if (select.val().length > 0) {
            var informacoes_adicionais = array_tipos_solicitacoes[select.val()].informacoes_adicionais;
            var xcampos_obg = array_tipos_solicitacoes[select.val()].campos_obrigatorios; 
            if (xcampos_obg != 'null' && xcampos_obg != null) {
			     campos_obrigatorios    = $.map(array_tipos_solicitacoes[select.val()].campos_obrigatorios, function(value, key) {
				    return key;
			     });
            }

    		if (informacoes_adicionais != 'null' && informacoes_adicionais != null) {
    			$.each(informacoes_adicionais, function(informacao_adicional, label) {
    				mostrar_campos.push(informacao_adicional);
    			});

    			div_informacoes_adicionais.show();
            } else {
                div_informacoes_adicionais.hide();
    		}
            <?php if($login_fabrica == 35){ ?>
                carrega_informacoes_documentos(select.val());
            <?php } ?>
    	} else if (div_informacoes_adicionais.is(":visible")) {
    		div_informacoes_adicionais.hide();
    	}        

    	toggle_informacoes_adicionais(mostrar_campos, campos_obrigatorios,informacoes_adicionais, array_tipos_solicitacoes,tipo_solicitacao);
    });


	$("#tipo_solicitacao").change();

    <? if (!empty($callcenter_de_troca)) {
        $sql = "SELECT
                    tbl_posto_fabrica.posto,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_hd_chamado_extra.reclamado
                FROM tbl_hd_chamado_extra
                    JOIN tbl_os ON(tbl_os.os = tbl_hd_chamado_extra.os AND tbl_os.fabrica = {$login_fabrica})
                    JOIN tbl_posto_fabrica ON(tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica})
                    JOIN tbl_posto ON(tbl_posto.posto = tbl_posto_fabrica.posto)
                WHERE tbl_hd_chamado_extra.hd_chamado = {$callcenter_de_troca}";
        $res = pg_query($con, $sql);
        $codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');
        $posto = pg_fetch_result($res, 0, 'posto');
        $nome = pg_fetch_result($res, 0, 'nome');
        $reclamado = pg_fetch_result($res, 0, 'reclamado');
    ?>
    CKEDITOR.instances['descricao_atendimento'].setData('Prezado posto favor enviar o comprovante de troca do produto');
    $('#posto_id').val(<?=$posto; ?>);
    $('#posto_codigo').val('<?=$codigo_posto; ?>');
    $('#posto_nome').val('<?=$nome; ?>');
    <? } if (!empty($ordem_de_servico_troca)) {
    ?>
    $("#ordem_de_servico").val(<?=$ordem_de_servico_troca; ?>).trigger('change');
    <?
    }
    ?>

	<?php
	if ($areaAdmin === true && !in_array($login_fabrica, [169,170])) {
	?>
		if ($("#posto_id").val().length > 0) {
			$("#posto_codigo, #posto_nome").prop({ readonly: true }).next("span").hide();
			<?php if ($login_fabrica != 198) { ?>
                $("#div_trocar_posto").show();
            <?php } ?>
		}

		if ($("#produto_id").val().length > 0) {
			$("#produto_referencia, #produto_descricao").prop({ readonly: true }).next("span").hide();
			$("#div_trocar_produto").show();
		}
	<?php
	}

	if(strlen($os_abertura) > 0){
	?>
        $("#ordem_de_servico").attr({
            value:'<?=$sua_os_abertura?>',
            readonly:"readonly"
        });
        $("#produto_id").attr({
            value:'<?=$produto["id"]?>'
        });
        $("#produto_referencia").attr({
            value:"<?=$produto['referencia']?>",
            readonly:"readonly"
        });
        $("#produto_descricao").attr({
            value:"<?=$produto['descricao']?>",
            readonly:"readonly"
        });
        $("#cliente").attr({
            value:"<?=$cliente?>",
            readonly:"readonly"
        });
	<?
	}
	?>

    $("input[name='produto_garantia']").click(function(){

        var garantia = $(this).val();

        if(garantia == "f"){
            $("#ordem_de_servico").prev("h5.asteristico").hide();
            $(".control-group").eq(3).removeClass("error");
        }else{
            $("#ordem_de_servico").prev("h5.asteristico").show();
        }

    });

    <?php

    if(isset($_POST["produto_garantia"])){

        if($_POST["produto_garantia"] == "f"){
            ?>
            $("#ordem_de_servico").prev("h5.asteristico").hide();
            <?php
        }
    }

    ?>

});

function carrega_informacoes_documentos(tipo_solicitacao){

    $.ajax({
        url: "helpdesk_posto_autorizado_novo_atendimento.php",
        type: "get",
        data: {
            busca_info_tipo_solicitacao: true,
            tipo_solicitacao: tipo_solicitacao
        }
    }).done(function(data) {
        if(data.length > 0){
            $("#div_informacoes_documentos").show();
            $("#obs_info_documento").val(data);    
        }else{
            $("#div_informacoes_documentos").hide();
        }
    });
}

function toggle_informacoes_adicionais(mostrar_campos, campos_obrigatorios, informacoes_adicionais,array_tipos_solicitacoes,tipo_solicitacao) {
	$.each(array_informacoes_adicionais, function(key, value) {
		if ($.inArray(value, mostrar_campos) == -1) { 
			$("div."+value).removeClass("visible").addClass("not-visible");
		} else {
			$("div."+value).removeClass("not-visible").addClass("visible");

			var obg = $("div."+value).find("input:not([type=hidden],[type=search]), select").prev("h5.asteristico").length;

			if ($.inArray(value, campos_obrigatorios) != -1) {
				if (obg == 0) {
					$("div."+value).find("input:not([type=hidden],[type=search]), select").before("<h5 class='asteristico' >*</h5>");
				}
			} else {
				if (obg > 0) {
					$("div."+value).find("input:not([type=hidden],[type=search]), select").prev("h5.asteristico").remove();
				}
			}


			/*if (F == 30 && $.inArray('produto_os', mostrar_campos) == -1) {
				$("div[id^=div_anexo]:not(#div_anexo_0)").addClass('oculto');
			} else {
				$("div[id^=div_anexo]:not(#div_anexo_0)").removeClass('oculto');
			}*/
        
            if ( (F == 30 || F == 72 ) && $.inArray('anexos', mostrar_campos) != -1) {
                 var obrigatorios = array_tipos_solicitacoes[tipo_solicitacao].campos_obrigatorios.anexo_obrigatorio;

                 $("div[id^=div_anexo]").addClass('oculto');
                 $('div[id^=div_anexo] button').removeClass('btn-danger').addClass('btn-primary');
                for(var i = 0; i < informacoes_adicionais["anexos"].length; i++){
                    $("#div_anexo_"+i).removeClass('oculto');
                    $("#div_anexo_"+i+" button").text(informacoes_adicionais["anexos"][i]);
                    
                    if(obrigatorios[i] == 1){
                        $('#div_anexo_'+i+' button').removeClass('btn-primary').addClass('btn-danger');
                    }
                }
            }

			if( value == "produto_os") {
				if ($("#produto_id").val().length > 0) {
					$("#div_trocar_produto").removeClass("not-visible").addClass("visible");
				} else {
					$("#div_trocar_produto").removeClass("visible").addClass("not-visible");
				}
			}
		}
	});

	$("div.row-informacao-adicional").each(function() {
		if ($(this).find("div.col-informacao-adicional.visible").length > 0) {
			$(this).css({ display: "table" });
		} else {
			$(this).css({ display: "none" });
		}
	});
}

function retorna_posto(data) {
	$("#posto_id").val(data.posto);
	$("#posto_codigo").val(data.codigo).prop({ readonly: true });
	$("#posto_nome").val(data.nome).prop({ readonly: true });
	$("#posto_codigo, #posto_nome").next("span").hide();
	$("#div_trocar_posto").show();
}

function retorna_produto(data) {
	$("#produto_id").val(data.produto);
	$("#produto_referencia").val(data.referencia).prop({ readonly: true });
	$("#produto_descricao").val(data.descricao).prop({ readonly: true });
	$("#produto_referencia, #produto_descricao").next("span").hide();
	$("#div_trocar_produto").removeClass("not-visible").addClass("visible");
}
<?php if ($login_fabrica == 35) { ?>
function retorna_peca(data) {
    var existe = false;
    $("[data-peca]").each(function(){
        if ($(this).data('peca') == data.peca){
            existe = true;
        }
    });
    if (!existe){
        var option = "<input type='text' id='pecas["+data.peca+"]' name='pecas["+data.peca+"]' data-maximo='"+data.qtde_maximo+"' data-peca='"+data.peca+"' value='1' style='width: 70px;'>";

        var text = "<tr style='background-color:#fff !important;'><td>" + data.referencia + " - " + data.descricao + "</td><td>" + option + "</td></tr>";
    }	

	$("#tablePecas tbody").append(text);
    $("[data-maximo]").on('blur', function() {
        if ($(this).val() > $(this).data('maximo')){
            alert("Quantidade de peça maior que no pedido!");
            $(this).val($(this).data('maximo'));
        } 
    });
    $("#peca_pesquisa").val("");
}
    <?php

    if ($_GET['tipo_solicitacao'] == 'Reclamação Peças' OR $_GET['tipo_solicitacao'] == 'reclamacao_pecas') { 
        $get =  $_GET;
    ?>    
    $(function() {
        var $_GET = <?php echo json_encode(utf8ize($get)); ?> ;
        $('#tipo_solicitacao').attr('readonly', 'true');
        if ($_GET.ordem_de_servico) {
            $('#ordem_de_servico').attr('readonly', 'true');
            if (unescape($_GET.tipo_solicitacao) != 'Reclama??o Pe?as') {
                $('#ordem_de_servico').val($_GET.ordem_de_servico);
            }
        }
        if ($_GET.pedido) {
            $('#pedido').attr('readonly', 'true');
            $('#pedido').val($_GET.pedido);  
        }
        if ($_GET.produto_referencia) {
            $('#produto_referencia').attr('readonly', 'true');
            $('#produto_referencia').val($_GET.produto_referencia); 
        }
        if ($_GET.produto_descricao) {
            $('#produto_descricao').attr('readonly', 'true');
            $('#produto_descricao').val($_GET.produto_descricao); 
        }
        if ($_GET.cliente) {
            $('#cliente').attr('readonly', 'true');   
            $('#cliente').val($_GET.cliente);
        }
    });
    <?php } ?>

<?php }else{ ?>
function retorna_peca(data) {
    var option = $("<option></option>", {
        value: data.peca,
        text: data.referencia + " - " + data.descricao
    });

    $("#pecas").append(option);

    var pecas_selecionadas = $("#pecas").val();

    if (pecas_selecionadas == null) {
        pecas_selecionadas = [];
    }

    pecas_selecionadas.push(data.peca);

    $("#pecas").val(pecas_selecionadas).trigger("change");
}

<?php } ?>
<?php
    if ($login_fabrica == 35) { 
        if (count($_POST['anexo']) > 0) { ?>
            var count_anexo = "<?=count($_POST['anexo'])?>";
<?php 
        } else { ?>
            var count_anexo = 1;
<?php 
        }
    }
?>
function novo_anexo() {
    var conteudo_anexo = '<div id="div_anexo_'+count_anexo+'" class="tac" style="margin: 0px 5px 0px 5px; vertical-align: top;" >'+
                                '<img src="imagens/imagem_upload.png" class="anexo_thumb" style="width: 100px; height: 90px;" />'+
                                '<button type="button" name="btn_anexo_'+count_anexo+'" class="btn btn-mini btn-primary btn-block anexar" data-loading-text="Anexando..." value="'+count_anexo+'" >Anexar</button>'+
                                '<input type="hidden" rel="anexo" name="anexo['+count_anexo+']" value=""/>'+
                            '</div>';
    $('#todos_anexos').append(conteudo_anexo);

    var conteudo_form = '<form name="form_anexo_'+count_anexo+'" method="post" enctype="multipart/form-data" style="display: none;" >'+
                        '<input type="file" name="anexo_upload" value="" />'+
                        '<input type="hidden" name="ajax_anexo_upload" value="t" />'+
                        '<input type="hidden" name="anexo_posicao" value="'+count_anexo+'" />'+
                        '<input type="hidden" name="token_form" class="token_form" value="TOKEN" />'+
                        '</form>';
    $('#frm_todos_anexos').append(conteudo_form);
    count_anexo++;
    $('.btn-novo-anexo').attr('data-total', count_anexo);    

    $(document).on('click', 'button[name=btn_anexo_'+count_anexo+']', function() {
        var pos = $(this).val();
        $("form[name=form_anexo_"+pos+"]>input[name=anexo_upload]").click();
    });

    $(document).on('change', 'input[name^=anexo_upload]', function() {
        var btn = '#div_anexo_' + $(this).parent().find('[name=anexo_posicao]').val() + ' button';
        $(btn).button("loading");

        $(this).parent("form").submit();
    });

    $("form[name^=form_anexo]").ajaxForm({
        complete: function(data) {
            data = JSON.parse(data.responseText);

            if (data.error) {
                alert(data.error);
            } else {
                var divAnexo = $("#div_anexo_" + data.posicao);
                var imagem = $(divAnexo).find("img.anexo_thumb").clone();

                $(imagem).attr({ src: data.link });

                $(divAnexo).find("img.anexo_thumb").remove();

                var linkFoto = $("<a></a>", {
                    href: data.href,
                    target: "_blank"
                });

                $(linkFoto).html(imagem);

                $(divAnexo).prepend(linkFoto);

                if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                    setupZoom();
                }

                $(divAnexo).find("input[name^=anexo][rel=anexo]").val(data.arquivo_nome);
            }

            $(divAnexo).find('button[name=btn_anexo_'+count_anexo+']').button("reset");
        }
    });
}

</script>

</script>
<?php
function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}
if (count($msg_erro["msg"]) > 0) { ?>
    <?php
        $erro = $msg_erro["msg"];
        if (isset($msg_erro["msg"]["campo_obrigatorio"])) {  
            
            $campo_obrigatorio = $msg_erro["msg"]["campo_obrigatorio"];
            unset($msg_erro["msg"]["campo_obrigatorio"]);
            $erro = $msg_erro["msg"];
        }
    ?>
    <br/>
    <?php if (isset($campo_obrigatorio)) { ?>
        <div class="alert alert-error"><h4><?=implode("<br />", $campo_obrigatorio)?></h4></div>
    <?php } ?>
	<div class="alert alert-error"><h4><?=implode("<br />", $erro)?></h4></div>
<?php } ?>
<br />

<form name="frm_novo_atendimento" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
    <?php if (isset($_GET['callcenter']) && in_array($login_fabrica, array(30))) { ?>
        <div class='alert alert-warning'>
            <h4>Por favor cadastrar o Help-Desk para que o posto possa enviar o comprovante de troca</h4>
        </div>
    <? } ?>
	<div class="row" ><b class="obrigatorio pull-right">* Campos obrigatórios</b></div>

	<div class="tc_formulario" >
		<div class="titulo_tabela">Cadastrar Novo Atendimento</div>

		<br />

		<?php
		if ($areaAdmin === true) {
			if (in_array($login_fabrica, [198])) {
                $sql = "SELECT posto, nome, codigo_posto 
                              FROM tbl_posto_fabrica 
                              INNER JOIN tbl_posto USING(posto) 
                              WHERE (codigo_posto = 'FRITESTE' OR posto = 6359) 
                              AND fabrica = {$login_fabrica}";
                $res   = pg_query($con, $sql);
                
                $posto_id     = pg_fetch_result($res, 0, 'posto');
                $posto_nome   = pg_fetch_result($res, 0, 'nome');
                $posto_codigo = pg_fetch_result($res, 0, 'codigo_posto');

                $posto_input_readonly         = "readonly";
                $posto_esconde_lupa           = "style='display: none;'";
                $posto_mostra_div_troca_posto = "style='display: none;'";

            } else if (strlen(getValue("posto[id]")) > 0) {
				$posto_input_readonly         = "readonly";
				$posto_esconde_lupa           = "style='display: none;'";
				$posto_mostra_div_troca_posto = "style='display: inline-block;'";

                if (in_array($login_fabrica, [169,170])) {
                    $hidden_posto = "hidden";
                    $posto_mostra_div_troca_posto = "";
                }

			}
		?>

			<input type="hidden" id="posto_id" name="posto[id]" value="<?= (empty(getValue('posto[id]'))) ? $posto_id : getValue('posto[id]') ?>" />
            
            <?php if (!in_array($login_fabrica, [198])) { ?>
			<div <?= $hidden_posto ?> class="row-fluid" >

				<div class="span1"></div>

				<div class="span4">
					<div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="posto_codigo">Código do Posto Autorizado</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<h5 class="asteristico">*</h5>
                            <?php
                                if (!in_array($login_fabrica, [198])) {
                                
                                $posto_codigo = '';
                                $posto_nome   = '';

                                if (!empty(getValue('posto[codigo]'))) {
                                    $posto_codigo = getValue('posto[codigo]');
                                }

                                if (!empty(getValue('posto[nome]'))) {
                                    $posto_nome = getValue('posto[nome]');
                                }

                                if (!empty($_GET['posto'])) {
                                    $sql = "SELECT nome FROM tbl_posto_fabrica INNER JOIN tbl_posto USING(posto) where codigo_posto = '{$_GET['posto']}'";
                                    $res = pg_query($con, $sql);
                                    $posto_nome = pg_fetch_result($res, 0, 'nome');
                                    $posto_codigo = $_GET['posto'];
                                }

                                if ($login_fabrica == 35 && (isset($_GET['posto_num']) or isset($_GET['posto']))) {
									$posto_codigo = (isset($_GET['posto_num'])) ? $_GET['posto_num']  : $_GET['posto']; 
                                   $sql = "SELECT tbl_posto.posto, nome, codigo_posto
                                           FROM tbl_posto_fabrica
                                           INNER JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto 
										   WHERE tbl_posto_fabrica.codigo_posto = '{$posto_codigo}'
											AND fabrica = $login_fabrica 	";

                                    $res = pg_query($con, $sql);

                                    $posto_nome = pg_fetch_result($res, 0, 'nome');
                                    $posto_id = pg_fetch_result($res, 0, 'posto');
                                    $posto_codigo = pg_fetch_result($res, 0, 'codigo_posto');

                                    echo "<input id='posto_info_id' type='hidden' posto_id = {$posto_id}></input>";


                                    ?><script type="text/javascript">
                                    $(function() {
                                        var posto_id = $("#posto_info_id").attr("posto_id");

                                        $("#posto_id").val(posto_id);
                                        $("#posto_codigo").prop({ readonly: true })
                                        $("#posto_nome").prop({ readonly: true });
                                        $("#posto_codigo, #posto_nome").next("span").hide();
                                        $("#div_trocar_posto").show(); 
                                               
                                    });

                                    </script><?php
                                }

                                }    
                            ?>
								<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=$posto_codigo?>" <?=$posto_input_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
				</div>

				<div class="span4">
					<div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="posto_nome">Nome do Posto Autorizado</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<h5 class="asteristico">*</h5>
								<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=$posto_nome?>" <?=$posto_input_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
				</div>
            <?php if (in_array($login_fabrica, array(30,72))) { ?>
                <div class="span2">
                    <div class='control-group' >
                        <label class="control-label" for="posto_codigo">Chamado Interno</label>
                        <div class="controls controls-row">
                            <input name="posto[chamado_interno]" type="checkbox" value="t" <?=(getValue('posto[chamado_interno]') == 't') ? 'checked' : '' ?> <?= ($login_fabrica == 198) ? 'checked="checked" disabled="disabled"' : '' ?>/>
                            <select class="span5" name="posto[qtde_dias]" id="dias_chamado_interno" style="<?=(getValue('posto[chamado_interno]') == 't') ? '' : 'display: none;' ?>">
                                <?php
                                for ($i = 0; $i < 16; $i++) {
                                    $selected = (getValue('posto[qtde_dias]') == $i) ? 'selected' : '';
                                    echo "<option value='$i' $selected>$i</option>";
                                }
                                ?>
                            </select><label style="margin-left: 4px; <?=(getValue('posto[qtde_dias]') !== '') ? '' : 'display: none;'?>">dia(s)</label>
                        </div>
                    </div>
                </div>
            <?php } ?>
			</div>
            <?php } ?>

			<div id="div_trocar_posto" class="row-fluid" <?=$posto_mostra_div_troca_posto?> >
				<div class="span1"></div>
				<div class="span10">
					<button type="button" id="trocar_posto" class="btn btn-danger" >Alterar Posto Autorizado</button>
				</div>
			</div>
		<?php
		} else {
		?>
			<input type="hidden" id="posto_id" name="posto[id]" value="<?=$login_posto?>" />
		<?php
		}
		?>

		<div class="row-fluid" >

			<div class="span1"></div>

            <?php if (!in_array($login_fabrica, [198])) { ?>
			<div class="span4" >
				<div class="control-group <?=(in_array('responsavel_solicitacao', $msg_erro['campos'])) ? "error" : "" ?>" >
					<label class="control-label" for="responsavel_solicitacao" >Responsável pela Solicitação</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<?php
							if ($regras["responsavel_solicitacao"]["obrigatorio"] == true) {
							?>
								<h5 class="asteristico" >*</h5>
							<?php
							}
							?>
							<input type="text" class="span12" id="responsavel_solicitacao" name="responsavel_solicitacao" value="<?=getValue('responsavel_solicitacao')?>" />
						</div>
					</div>
				</div>
			</div>
            <?php } ?>

			<div class="span4">
				<div class='control-group <?=(in_array('tipo_solicitacao', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="tipo_solicitacao" >Tipo de Solicitação</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<h5 class="asteristico" >*</h5>
							<select class="span12" id="tipo_solicitacao" name="tipo_solicitacao" >
								<option value="">Selecione</option>
								<?php
                                if ($_GET['tipo_solicitacao'] == 'Reclamação Peças' OR $_GET['tipo_solicitacao'] == "reclamacao_pecas") {
                                    asort($array_tipos_solicitacoes);
                                    foreach ($array_tipos_solicitacoes as $tipo_solicitacao_id => $tipo_solicitacao_data) {
                                        $selected = (getValue("tipo_solicitacao") == $tipo_solicitacao_id) ? "selected" : "";
                                        if (utf8_decode($tipo_solicitacao_data['label']) == 'Reclamação Peças') {
                                            $tipo_solicitacao = $tipo_solicitacao_id;
                                            echo "<option value='{$tipo_solicitacao_id}' selected >".utf8_decode($tipo_solicitacao_data['label'])."</option>";
                                        }
                                    }
                                } else{
                                    asort($array_tipos_solicitacoes);

    								foreach ($array_tipos_solicitacoes as $tipo_solicitacao_id => $tipo_solicitacao_data) {
                                        if (!empty($ordem_de_servico_troca) && strrpos(utf8_decode($tipo_solicitacao_data['label']), 'Solicitação') !== 0)
                                        continue;

    									$selected = (getValue("tipo_solicitacao") == $tipo_solicitacao_id) ? "selected" : "";
                                        if ($login_fabrica == 35 && $_GET['solicitacao'] == 'visaoOS' &&  utf8_decode($tipo_solicitacao_data['label']) == 'Visão Geral de OS') {
                                            $selected = (utf8_decode($tipo_solicitacao_data['label']) == 'Visão Geral de OS') ? "selected" : "";
                                        }
    									echo "<option value='{$tipo_solicitacao_id}' {$selected} >".utf8_decode($tipo_solicitacao_data['label'])."</option>";
    								}
								
                                }
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
            <?php
            if (in_array($login_fabrica, [35]) and !empty($pedido)) {
                $sqlTipoPedido = "SELECT descricao FROM tbl_pedido JOIN tbl_tipo_pedido USING (tipo_pedido) WHERE pedido = $pedido ";
                $resTipoPedido = pg_query($con, $sqlTipoPedido);
                $tipoPedido = pg_fetch_result($resTipoPedido, 0, 'descricao');
            }
            if ((in_array($login_fabrica, [35]) && $tipo_pedido != 'FATURADO')) {
            ?>
			<div class="span3" >
				<div class="control-group" >
					<label class="control-label" for="produto_garantia" >Produto em Garantia</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<label class="radio" >
								Sim
								<input type="radio" name="produto_garantia" checked value="t" />
							</label> &nbsp;
							<label class="radio" >
								Não
								<input type="radio" name="produto_garantia" <?=(getValue("produto_garantia") == "f") ? "checked" : ""?> value="f" />
							</label>
						</div>
					</div>
				</div>
			</div>
            <?php 
            } else if ($login_fabrica == 35) {
                echo '<input type="hidden" name="produto_garantia" value="f" />';
            }
            ?>

		</div>
        <?php 
        if (in_array($login_fabrica, [169,170])) { 
            ?>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('providencia', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="providencia" >Providência</label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <h5 class="asteristico" >*</h5>
                                <select class="span12" id="providencia" name="providencia" >
                                    <option value="">Selecione</option>
                                    <?php
                                    foreach ($arrProvidencia as $codigoProvidencia => $descricaoProvidencia) { 

                                        $selected = (getValue("providencia") == $codigoProvidencia) ? "selected" : "";
                                        ?>
                                        <option value="<?= $codigoProvidencia ?>" <?= $selected ?>><?= $descricaoProvidencia ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class='control-group <?=(in_array('sub_item', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="sub_item" >Sub-item</label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <h5 class="asteristico" >*</h5>
                                <select class="span12" id="sub_item" name="sub_item" >
                                    <option value="">Selecione</option>
                                    <?php
                                    foreach ($arrSubItem as $codigoSubItem => $descricaoSubItem) { 

                                        $selected = (getValue("sub_item") == $codigoSubItem) ? "selected" : "";
                                        ?>
                                        <option value="<?= $codigoSubItem ?>" <?= $selected ?>><?= $descricaoSubItem ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('origem', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="origem" >Origem</label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <h5 class="asteristico" >*</h5>
                                <select class="span12" id="origem" name="origem" >
                                    <option value="">Selecione</option>
                                    <?php
                                    foreach ($arrOrigem as $descricaoOrigem) { 

                                        $selected = (getValue("origem") == $descricaoOrigem) ? "selected" : "";
                                        ?>
                                        <option value="<?= $descricaoOrigem ?>" <?= $selected ?>><?= $descricaoOrigem ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        } ?>

		<div id="div_informacoes_adicionais" >
			<div class="titulo_tabela" >Informações Adicionais</div>
			<br />

			<div class="row-fluid row-informacao-adicional" >
				<div class="span1" ></div>
                <?php 
                     if ((in_array($login_fabrica, [35]) && $tipo_pedido != 'FATURADO') or $login_fabrica != 35) {
                ?>
				<div class="span3 os_posto col-informacao-adicional" >
					<div class="control-group <?=(in_array('ordem_de_servico', $msg_erro['campos'])) ? "error" : "" ?>" >
						<label class="control-label" for="ordem_de_servico" >Ordem de Serviço</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<input type="text" class="span12" id="ordem_de_servico" name="ordem_de_servico" value="<?=getValue('ordem_de_servico')?>" />
							</div>
						</div>
					</div>
				</div>
            <?php } 
            if (in_array($login_fabrica, [35]) && $_GET['tipo_solicitacao'] == 'Reclamação Peças') {
                $sqlSuaOs = "SELECT sua_os from tbl_os WHERE os = {$_GET['ordem_de_servico']}";
                $resSuaOs = pg_query($con, $sqlSuaOs);
                $sua_os = pg_fetch_result($resSuaOs, 0, 'sua_os');
                echo "<input type='hidden' class='span12' id='ordem_de_servico' name='ordem_de_servico' value='{$sua_os}' />";
            }
            ?>
				<div class="span3 num_pedido col-informacao-adicional" >
					<div class="control-group <?=(in_array('pedido', $msg_erro['campos'])) ? "error" : "" ?>" >
						<label class="control-label" for="pedido" >Pedido</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<input type="text" class="span12" id="pedido" name="pedido" value="<?=getValue('pedido')?>" />
							</div>
						</div>
					</div>
				</div>

				<div class="span3 hd_chamado_sac col-informacao-adicional" >
					<div class="control-group <?=(in_array('protocolo_atendimento', $msg_erro['campos'])) ? "error" : "" ?>" >
						<label class="control-label" for="protocolo_atendimento" >Protocolo de Atendimento</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<input type="text" class="span12" id="protocolo_atendimento" name="protocolo_atendimento" value="<?=getValue('protocolo_atendimento')?>" />
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="row-fluid row-informacao-adicional" >
				<div name="margin" class="span1" ></div>

				<div class="span4 produto_os col-informacao-adicional" >
					<div class="control-group <?=(in_array('produto[id]', $msg_erro['campos'])) ? "error" : "" ?>" >
						<label class="control-label" for="produto_referencia" >Referência do Produto</label>
						<div class="controls controls-row" >
							<div class="span10 input-append">
								<input type="hidden" id="produto_id" name="produto[id]" value="<?=getValue('produto[id]')?>" />
								<input id="produto_referencia" name="produto[referencia]" class="span12" type="text" value="<?=getValue('produto[referencia]')?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
							</div>
						</div>
					</div>
				</div>

				<div class="span4 produto_os col-informacao-adicional" >
					<div class="control-group <?=(in_array('produto[id]', $msg_erro['campos'])) ? "error" : "" ?>" >
						<label class="control-label" for="produto_descricao" >Descrição do Produto</label>
						<div class="controls controls-row" >
							<div class="span10 input-append">
								<input id="produto_descricao" name="produto[descricao]" class="span12" type="text" value="<?=getValue('produto[descricao]')?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
							</div>
						</div>
					</div>
				</div>

				<div id="div_trocar_produto" class="span2 produto_os col-informacao-adicional" >
					<br />
					<button type="button" id="trocar_produto" class="btn btn-danger" >Alterar Produto</button>
				</div>
			</div>

			<div class="row-fluid row-informacao-adicional" >
				<div name="margin" class="span1" ></div>

				<div class="span4 nome_cliente col-informacao-adicional" >
					<div class="control-group <?=(in_array('cliente', $msg_erro['campos'])) ? "error" : "" ?>" >
						<label class="control-label" for="cliente" >Cliente</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<input type="text" class="span12" id="cliente" name="cliente" value="<?=getValue('cliente')?>" />
							</div>
						</div>
					</div>
				</div>
			</div>
            <?php if ($login_fabrica == 35) {?>
                <div class="row-fluid row-informacao-adicional" >
                    <div name="margin" class="span1" ></div>

                    <div class="span4 pedido_pend col-informacao-adicional" >
                        <div class="control-group" >
                            <label class="control-label" for="peca_pesquisa" >Peça</label>
                            <div class="controls controls-row" >
                                <div class="span10 input-append">
                                    <input id="peca_pesquisa" class="span12" type="text" />
                                    <span class="add-on" rel="lupa" id="lupa_peca">
                                        <i class="icon-search"></i>
                                    </span>
									<input type="hidden" id="lupa_peca_config" name="lupa_config" class='peca_pesquisa_lupa' tipo="peca" parametro="referencia_descricao" pedido="<?=$pedido?>" produto="<?=$produto['id']?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="row-fluid row-informacao-adicional" >
                    <div name="margin" class="span1" ></div>
                    <div class="span10 pedido_pend col-informacao-adicional" >
                        <div class="control-group <?=(in_array('pecas', $msg_erro['campos'])) ? "error" : "" ?>" >
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <table width="100%" border="1" cellspacing="1" cellpadding="3" align="center" class="tabela" id="tablePecas">
                                        <thead>
                                            <th class="titulo_tabela" width="90%">Peça</th>
                                            <th class="titulo_tabela">Qtde</th>
                                        </thead>
                                        <tbody id="tBody">
                                            <?php
                                            foreach ($pecaHistorico as $codPeca => $qtdPeca) {
                                                $sqlPecaHistorio = "SELECT referencia, descricao
                                                            FROM tbl_peca 
                                                            WHERE peca = $codPeca
                                                            and fabrica = $login_fabrica;";
                                                $resPecaHistorio = pg_query($con, $sqlPecaHistorio);
                                                $referenciaPeca = pg_fetch_result($resPecaHistorio, 0, 'referencia');
                                                $descricaoPeca = pg_fetch_result($resPecaHistorio, 0, 'descricao');
                                                $qtdeMaxPeca = pg_fetch_result($resPecaHistorio, 0, 'qtde');
                                                echo "<tr style='background-color:#fff;'>";
                                                echo "<td>";
                                                echo "$referenciaPeca - $descricaoPeca";
                                                echo "</td>";
                                                echo "<td>";
                                                echo "<input type='text' id='pecas[{$codPeca}]' name='pecas[{$codPeca}]' data-maximo='{$qtdeMaxPeca}' data-peca='{$codPeca}' value='{$qtdPeca}' style='width: 70px;'>";
                                                echo "</td>";
                                                echo "</tr>";   
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </br>
                <div class="row-fluid row-informacao-adicional" >
                    <div name="margin" class="span1" ></div>
                    <div class="span10 motivo col-informacao-adicional" >
                        <div class="control-group <?=(in_array('motivo', $msg_erro['campos'])) ? "error" : "" ?>" >
                            <label class="control-label" for="selectMotivo" >Motivo</label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <?php 
                                    $sqlMotivo = "  SELECT hd_situacao, descricao 
                                                    FROM tbl_hd_situacao 
                                                    WHERE fabrica = $login_fabrica 
                                                    AND tipo_registro = 'motivo_hd_chamado' 
                                                    AND ativo IS TRUE";
                                    $resMotivo = pg_query($con, $sqlMotivo);
                                    ?>
                                    <select id="selectMotivo" name="selectMotivo">
                                        <?php 
                                        while ($motivo = pg_fetch_object($resMotivo)) {
                                            if($motivo->hd_situacao ==  getValue('selectMotivo')){
                                                $selected = " selected ";
                                            }else{
                                                $selected = "";
                                            }
                                            echo "<option $selected value='{$motivo->hd_situacao}'>{$motivo->descricao}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="row-fluid row-informacao-adicional" >
                    <div name="margin" class="span1" ></div>

                    <div class="span4 pedido_pend col-informacao-adicional" >
                        <div class="control-group" >
                            <label class="control-label" for="peca_pesquisa" >Peça</label>
                            <div class="controls controls-row" >
                                <div class="span10 input-append">
                                    <input id="peca_pesquisa" class="span12" type="text" />
                                    <span class="add-on" rel="lupa" >
                                        <i class="icon-search"></i>
                                    </span>
                                    <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia_descricao" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span6 pedido_pend col-informacao-adicional" >
                        <div class="control-group <?=(in_array('pecas', $msg_erro['campos'])) ? "error" : "" ?>" >
                            <label class="control-label" >Digite, ao lado a referência ou a descrição da peça</label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <select id="pecas" name="pecas[]" class="span12" multiple="multiple" >
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }?>

            <?php if($login_fabrica == 35) {?>
                <div class="row-fluid row-informacao-adicional" >
				    <div class="span1" ></div>
                    <div class="span3 ticket_atendimento col-informacao-adicional" >
                        <div class="control-group <?=(in_array('ticket_atendimento', $msg_erro['campos'])) ? "error" : "" ?>" >
                            <label class="control-label" for="ticket_atendimento" >Ticket Atendimento</label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <input type="text" class="span12" id="ticket_atendimento" maxlength="8" name="ticket_atendimento" value="<?=getValue('ticket_atendimento')?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row-fluid row-informacao-adicional" >
				    <div class="span1" ></div>
                    <div class="span3 cod_localizador col-informacao-adicional" >
                        <div class="control-group <?=(in_array('cod_localizador', $msg_erro['campos'])) ? "error" : "" ?>" >
                            <label class="control-label" for="cod_localizador" >Código Localizador</label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <input type="text" class="span12" id="cod_localizador" maxlength="13" name="cod_localizador" value="<?=getValue('cod_localizador')?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row-fluid row-informacao-adicional" >
				    <div class="span1" ></div>
                    <div class="span3 pre_logistica col-informacao-adicional" >
                        <div class="control-group <?=(in_array('pre_logistica', $msg_erro['campos'])) ? "error" : "" ?>" >
                            <label class="control-label" for="pre_logistica" >Pre-Logistica</label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <input type="text" class="span12" id="pre_logistica" maxlength="8" name="pre_logistica" value="<?=getValue('pre_logistica')?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
		</div>

        <div id="div_informacoes_documentos" style='display: none' >
            <div class="titulo_tabela" >Informações de Documentos</div>
            <br />

            <div class="row-fluid row-informacoes_documentos" >
                <div class="span1" ></div>
                <div class="span10">Observações:<br>
                    <textarea readonly="true" id="obs_info_documento" style="margin: 0px; width: 692px; height: 101px;" name='observacao'></textarea>
                </div>
                <div class="span1" ></div>
            </div>
        </div>


		<br />

		<div class="titulo_tabela" >Descrição</div>

		<div class="row-fluid" >
			<div class="span12 tac" >
				<textarea class="span10" id="descricao_atendimento" name="descricao_atendimento" ><?=getValue("descricao_atendimento")?></textarea>
			</div>
		</div>

		<br />

        <?php 

        if ($fabricaFileUploadOS) {

            $boxUploader = array(
                "div_id" => "div_anexos",
                "prepend" => $anexo_prepend,
                "context" => "help desk",
                "unique_id" => $tempUniqueId,
                "hash_temp" => $anexoNoHash,
                "reference_id" => $tempUniqueId
            );

            include "box_uploader.php";

        } else { 
                /*if ($login_fabrica == 35) {
                    $titulo_anexo = 'Anexo(s)';
                } else {*/
                    $titulo_anexo = ($fabrica_qtde_anexos==1)?'Anexo':'Anexos';
                /*}*/
            ?>

            <div class="titulo_tabela" ><?=$titulo_anexo?></div>
         
            <br />

            <div id="todos_anexos" style="text-align: center !important;">

    <?php
    		/*if ($login_fabrica == 35 && count($_POST['anexo']) > 0) {
                $fabrica_qtde_anexos = count($_POST['anexo']);
            }*/ 

            for ($i=0; $i < $fabrica_qtde_anexos; $i++):
    			$anexo_imagem = "imagens/imagem_upload.png";
                $btn_anexo_t  = ($login_fabrica == 35) ? 'Anexar' : $attCfg['labels'][$i];
    			$btn_class    = $attCfg['obrigatorio'][$i] ? 'danger' : 'primary';
    			$anexos = json_decode(str_replace('\\', '', $anexo[$i]),true);
    			$anexo_link = null;
    			if($anexos) {
    				$anexo_link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexos['tdocs_id'].'/file/'.$anexos['name'];
    				$anexo_imagem = $anexo_link;
    			}
    			/* $div_visivel = !($i) ? '' : 'oculto'; */
    			?>
    			<div id="div_anexo_<?=$i?>" class="tac <?=$div_visivel?>" style="margin: 0px 5px 0px 5px; vertical-align: top;" >
    			<?php if (isset($anexo_link)) { ?>
    				<a href="<?=$anexo_link?>" target="_blank" >
    					<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
    				</a>
    			<?php } else { ?>
    				<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
    			<?php } ?>

    				<button type="button" class="btn btn-mini btn-<?=$btn_class?> btn-block anexar" data-loading-text="Anexando..." value="<?=$i?>" ><?=$btn_anexo_t?></button>
    				<input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value='<?=addslashes(json_encode($anexos))?>' />
    			</div>

    			<?php endfor; ?>
            </div>
    		<script>setupZoom();</script>

    		<br />
            <br />
           <?php } ?>
    		<p class="tac">
    			<input type="submit" class="btn btn-large" name="gravar" value="Gravar" />
                <input type="hidden" id="anexo_chave_hidden" name="anexo_chave_hidden" value="<?=$tempUniqueId?>" />
                <?php if ($login_fabrica == 35 && 1==2) { ?>
                    <input type="button" class="btn btn-large btn-novo-anexo" name="new_anexo" value="Novo Anexo" data-total="<?=$fabrica_qtde_anexos?>" onclick="novo_anexo();" />
                <?php } ?>
    		</p>

    		<br />
	</div>

</form>
    <div id="frm_todos_anexos">
<?php for ($i = 0; $i < $fabrica_qtde_anexos; $i++): ?>
	<form name="form_anexo_<?=$i?>" method="post" enctype="multipart/form-data" style="display: none;" >
		<input type="file" name="anexo_upload" value="" />
		<input type="hidden" name="ajax_anexo_upload" value="t" />
		<input type="hidden" name="anexo_posicao" value="<?=$i?>" />
	</form>
    </div>
<?php endfor;
include "rodape.php";

