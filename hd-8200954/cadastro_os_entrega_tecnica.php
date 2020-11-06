<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
#ini_set('display_errors', 1);
#error_reporting(E_ALL);

if ($areaAdmin === true) {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_usuario.php';
}

include_once 'funcoes.php';
include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("os", $login_fabrica);

/**
* Ajax que retorna todos os dados do Posto
**/
if(isset($_POST['ajax_posto'])){

	$posto = $_POST['ajax_posto'];

	$sql = "
		SELECT
			tbl_posto.nome,
			tbl_posto.cnpj,
			tbl_posto_fabrica.contato_cep,
			tbl_posto_fabrica.contato_estado,
			UPPER(fn_retira_especiais(tbl_posto_fabrica.contato_cidade)) AS contato_cidade,
			tbl_posto_fabrica.contato_bairro,
			tbl_posto_fabrica.contato_endereco,
			tbl_posto_fabrica.contato_numero,
			tbl_posto_fabrica.contato_complemento,
			tbl_posto_fabrica.contato_fone_comercial,
			tbl_posto_fabrica.contato_email
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		WHERE tbl_posto_fabrica.posto = {$posto}
	";
	$res = pg_query($con, $sql);

	$dados = "";

	$dados .= pg_fetch_result($res, 0, 'nome')."|";
	$dados .= pg_fetch_result($res, 0, 'cnpj')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_cep')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_estado')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_cidade')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_bairro')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_endereco')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_numero')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_complemento')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_fone_comercial')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_email');

	echo $dados;

	exit;

}

/*
*AJax busca valores adicionais do produto
*
*/
if (isset($_POST['ajax_busca_valor_adicional'])) {
	$produto = $_POST['produto'];

	if (isset($fabrica_usa_subproduto)) {
		$subproduto = $_POST["subproduto"];
	}

	if (strlen($produto) > 0) {
		$valores_adicionais_array = array();

		if (strlen($subproduto) > 0) {
			$where = "produto IN ({$produto}, {$subproduto})";
		} else {
			$where = "produto = {$produto}";
		}

		$sql_linha = "SELECT valores_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND {$where}";
		$res_linha = pg_query($con, $sql_linha);

		if(pg_num_rows($res_linha) > 0){
			while ($result = pg_fetch_object($res_linha)) {
				$valores_adicionais = $result->valores_adicionais;
				$valores_adicionais = utf8_encode($valores_adicionais);

				if(strlen($valores_adicionais) > 0){
					$valores_adicionais = json_decode($valores_adicionais,true);

					foreach ($valores_adicionais as $descricao => $valor) {
						$valores_adicionais_array[] = array(
							"descricao" => $descricao,
							"valor" => $valor
						);
					}
				}
			}

			if(in_array($login_fabrica, array(152,180,181,182))) {
				$sql = "SELECT valores_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica} ";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					while ($result = pg_fetch_object($res)) {
						$v_adicional = $result->valores_adicionais;
						$v_adicional = utf8_encode($v_adicional);
						$valores     = json_decode($v_adicional,true);

						foreach ($valores as $key => $value) {
							$preco = $value['valor'];
							$editar = $value['editar'];

							$valores_adicionais_array[] = array(
								"descricao" => $key,
								"valor" => $preco,
								"editar" => $editar
							);
						}
					}
				}
			}

			if (count($valores_adicionais_array) > 0) {
				$retorno = array("ok" => true, "valores_adicionais" => $valores_adicionais_array);
			} else {
				$retorno = array("erro" => utf8_encode("não foi encontrado valores adicionais para o produto"));
			}
		} else {
			$retorno = array("erro" => utf8_encode("produto não encontrado"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("produto não informado"));
	}

	exit(json_encode($retorno));
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


/**
 *	Verifica digita OS
 */
if (strlen($os) == 0  and strlen($_REQUEST['os_id']) == 0 and $areaAdmin === false) {
	
	if (!$login_posto_digita_os) {
		$desabilita_tela = traduz('Sem permissão para cadastrar Ordens de Serviço.');
		include __DIR__.'/cabecalho_new.php';
	}
}

if(isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])){
	require_once __DIR__.'/classes/cep.php';

	$cep = $_POST['cep'];

	try {
		$retorno = CEP::consulta($cep);
		$retorno = array_map('utf8_encode', $retorno);
	} catch(Exception $e) {
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

if (isset($_POST['ajax_anexo_upload'])) {
    $posicao = $_POST['anexo_posicao'];
    $chave   = $_POST['anexo_chave'];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($arquivo['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
            $retorno = array('error' => utf8_encode(traduz('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx')));
        } else {
            $arquivo_nome = "{$chave}_{$posicao}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);

            if($ext == 'pdf'){
            	$link = 'imagens/pdf_icone.png';
            } else if(in_array($ext, array('doc', 'docx'))) {
            	$link = 'imagens/docx_icone.png';
            } else {
	            $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
	        }

	        $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);

            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(traduz('Erro ao anexar arquivo')));
            } else {
                $retorno = array('link' => $link, 'arquivo_nome' => "{$arquivo_nome}.{$ext}", 'href' => $href, 'ext' => $ext);
            }
        }
    } else {
        $retorno = array('error' => utf8_encode(traduz('Erro ao anexar arquivo')));
    }

    $retorno['posicao'] = $posicao;

    exit(json_encode($retorno));
}

// Arquivo com o array para montar o formulário
// include __DIR__."/os_cadastro_unico/fabricas/{$login_fabrica}/form.php";

// Arquivo que processa o post
include "os_cadastro_unico/fabricas/os_entrega_tecnica.php";

if ($areaAdmin === true) {
	$layout_menu = "callcenter";
} else {
	$layout_menu = "os";
}

$title       = traduz("CADASTRO DE ORDEM DE SERVIÇO DE ENTREGA TÉCNICA");

if ($areaAdmin === true) {
	include __DIR__.'/admin/cabecalho_new.php';
} else {
	include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "leaflet",
   "price_format"
);


include __DIR__."/admin/plugin_loader.php";

$entrega_tecnica_hora = false;

if(in_array($login_fabrica, array(152,180,181,182))){
	$entrega_tecnica_hora = false;

	$array_produto = getValue('produto');
	$count_produto = count($array_produto);
	$count_aux = 0;

	for($x=0; $x<$count_produto; $x++){
		if(!empty($array_produto[$x]['entrega_tecnica'])){
			if($array_produto[$x]['entrega_tecnica'] == "hora"){
				$entrega_tecnica_hora = true;
				$count_aux++;
			}
		}
	}
	unset($array_produto);
	$count_produto = $count_aux;
}
?>

<style>

#div_trocar_posto, #div_trocar_produto {
	display: none;
	height: 40px;
}
/*
#div_informacoes_subproduto, #div_informacoes_deslocamento, #modelo_peca, #modelo_subproduto_peca, #div_subproduto_pecas, #div_os_garantia, #div_peca_anexo, #div_peca_anexo_subproduto, #div_visita {
	display: none;
}*/

#div_informacoes_deslocamento > div.tc_formulario {
	height: auto;

}


#google_maps {
	width: 100%;
	float: left;
}

#google_maps_direction {
	width: 39%;
	float: right;
	overflow: auto;
}

#google_maps, #google_maps_direction {
	height: 300px;
	display: inline-block;
}

#google_maps img {
	max-width: none;
}

</style>

<!--
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&async=2&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>
<script src="plugins/markermanager.js" ></script>
-->
<script type="text/javascript">

/**
 * Variaveis do google maps
 */
var map = null;
var lista_enderecos;
var geocoder;
var posto_marker;
var consumidor_marker;
var directionsRenderer;
var directionsService;

/**
 * Funções do google maps
 */
function load_google_maps() {
	var latlon  = new google.maps.LatLng(-15.78014820, -47.92916980);
	var options = { zoom: 2, center: latlon, mapTypeId: google.maps.MapTypeId.HYBRID, zoomControlOptions: { style: google.maps.ZoomControlStyle.SMALL } };
	map         = new google.maps.Map(document.getElementById("google_maps"), options);
}

<?php if (isset($fabrica_usa_valor_adicional)) { ?>
	var valores_adicionais_selecionados = [];

	if ($("input[name='os[valor_adicional][]']:checked").length > 0) {
		$("input[name='os[valor_adicional][]']:checked").each(function() {
			valores_adicionais_selecionados.push($(this).val());
		});
	}
<?php } ?>
$(function() {
	<?php if (isset($fabrica_usa_valor_adicional)) { ?>
		if ($("input[name='os[valor_adicional][]']:checked").length > 0) {
			$("input[name='os[valor_adicional][]']:checked").each(function() {
				valores_adicionais_selecionados.push($(this).val());
			});
		}
	<?php } ?>
	$('#qtde_horas , qtde_deslocamento').numeric();

	//load_google_maps();

	<?php
	if (count($msg_erro["msg"]) > 0) {
	?>
		try {
			calcula_km();
		} catch(e) {
			console.log(e.message);
		}
	<?php
	}
	?>

	$("#google_maps").hide();


	/**
	 * Carrega o datepicker já com as mascara para os campos
	 */
	$("#data_abertura").datepicker({ maxDate: 0, minDate: "-6d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	$("#data_compra").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	/**
	 * Evento de change do campo data abertura para não deixar o campo data compra ser maior
	 */
	$("#data_abertura").change(function() {
		$("#data_compra").datepicker("destroy");
		$("#data_compra").datepicker({ maxDate: $("#data_abertura").datepicker("getDate") });
		$("#data_compra").datepicker("refresh");
	});

	$("#consumidor_cep").mask("99999-999");

	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();

	/**
	 * Configurações do Alphanumeric
	 */
	$(".numeric").numeric();
	$("#consumidor_telefone").numeric({ allow: "()- " });
	$("#qtde_km").priceFormat({
		prefix: '',
        thousandsSeparator: '',
        centsSeparator: '.',
        centsLimit: 2
	});

	$(".valor_adicional_valor").priceFormat({
		prefix: '',
		thousandsSeparator: '',
		centsSeparator: '.',
		centsLimit: 2
	});

	/**
	 * Evento que chama a função de lupa para a lupa clicada
	 */
	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	/**
	 * Evento que chama a lupa do produto
	 */
	$(document).on("click", "span[rel=lupa_produto]", function() {
		var parametros_lupa_produto = ["posto", "posicao", "ativo" ,"entrega"];

		<?php
			if(in_array($login_fabrica, array(152,180,181,182))){
		?>
			$.lupa($(this), parametros_lupa_produto,"", "",0);
		<?php
			}else{
		?>
			$.lupa($(this), parametros_lupa_produto);
		<?php
			}
		?>

	});

	<?php
	if(in_array($login_fabrica, array(152,180,181,182)) && $entrega_tecnica_hora == false){
	?>
		$("#qtde_horas").hide();
		$("#label_qtde_horas").hide();
	<?php
	}
	?>

	/**
	 * Evento de keypress do campo consumidor_cpf
	 * Irá verificar o tamanho do campo, se o tamanho já for 14(CPF) irá alterar a máscara para CNPJ e alterar o Label
	 */
	<?php
	if(strlen(getValue('consumidor[cpf]')) > 0){
		$consCPF = getValue('consumidor[cpf]');
		$consCPF = preg_replace("/\D/", "", $consCPF);
		if(strlen($consCPF) > 11){
		?>
			$("#consumidor_cpf").mask("99.999.999/9999-99");
		<?php
		}else{
		?>
			$("#consumidor_cpf").mask("999.999.999-99");
		<?php
		}
	}
	?>
	$("input[name=cnpjCpf]").change(function(){
		$("#consumidor_cpf").unmask();
		var tipo = $(this).val();
		if(tipo == 'cnpj'){
			$("#consumidor_cpf").mask("99.999.999/9999-99");
		}else{
			$("#consumidor_cpf").mask("999.999.999-99");
		}
	});

	/**
	 * Evento de click do botão trocar_posto
	 * Irá remover o readonly dos campos código e nome e dar um show nas lupas
	 */
	$("#trocar_posto").click(function() {
	 	$("#div_informacoes_posto").find("input").val("");
	 	$("#div_informacoes_posto").find("input[readonly=readonly]").removeAttr("readonly");
	 	$("#div_informacoes_posto").find("span[rel=lupa]").show();
	 	$("#div_trocar_posto").hide();

	 	<?php
		if ($areaAdmin === true) {
		?>
			$("input[name=lupa_config][tipo=produto]").attr({ posto: "" });

			$("#consumidor_nome").val("");
			$("#consumidor_cnpj").val("");
			$("#consumidor_cep").val("");
			$("#consumidor_estado").val("");
			$("#consumidor_cidade").val("");
			$("#consumidor_bairro").val("");
			$("#consumidor_endereco").val("");
			$("#consumidor_numero").val("");
			$("#consumidor_complemento").val("");
			$("#consumidor_telefone").val("");
			$("#consumidor_email").val("");

		<?php
		}
		?>
	});

	/**
	 * Evento para quando alterar o estado carregar as cidades do estado
	 */
	$("select[id$=_estado]").change(function() {
		busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "consumidor");
	});

	/**
	 * Evento para buscar o endereço do cep digitado
	 */
	$("input[id$=_cep]").blur(function() {
		if ($(this).attr("readonly") == undefined) {
			busca_cep($(this).val(), ($(this).attr("id") == "revenda_cep") ? "revenda" : "consumidor");
		}
	});

	/**
	 * Evento para calcular o KM
	 */
	$("#calcular_km").click(function() {
		try {
			calcula_km();
		} catch(e) {
			alert(e.message);
		}
	});

	/**
	 * Evento que adiciona uma nova linha de produto
	 */

	$("button[name=adicionar_produto]").click(function() {
		var posicao = $("div[id^=div_produto_][id!=div_produto___model__]").length;
		var linhaProduto = $("#modelo_produto").clone();

		$("#div_produto").append($(linhaProduto).html().replace(/__model__/g, posicao));

		$(".numeric").numeric();
	});

	$("#consumidor_numero").focusout(function() {
        $("#calcular_km").click();
	});

	/**
	 * Bloqueia campos na area do posto
	 */
	<?php
	if (strlen($os) > 0 && $areaAdmin === false && count($msg_erro["msg"]) == 0) {
	?>
		$("#div_informacoes_os input, #div_informacoes_consumidor input, #div_informacoes_produto input, #div_informacoes_deslocamento input").each(function() {
			if ($(this).prev("h5").length > 0 || $(this).val().length > 0) {
				if ($(this).parents("#div_informacoes_produto").length > 0 && typeof $(this).attr("id") != "undefined" && $(this).attr("id").match(/^qtde_/)) {
					return;
				}
				if ($(this).attr("id") == "qtde_horas" || $(this).attr("id") == "os_tempo_deslocamento") {
                	return;
                }
				$(this)[0].readOnly = true;

				if ($(this).attr("id") == "data_abertura") {
					$(this).datepicker("destroy");
				}

				if ($(this).next("span[rel=lupa]").length > 0) {
					$(this).next("span[rel=lupa]").hide();
				}
			}
		});

		$("#div_informacoes_os select, #div_informacoes_consumidor select, #div_informacoes_produto select").each(function() {
			var option_remove = false;

			if ($(this).prev("h5").length > 0 || $(this).val().length > 0) {
				$(this).selectreadonly(true);
				option_remove = true;
			}

			if (option_remove = true) {
				$(this).find("option").each(function() {
					if (!$(this).is(":selected")) {
						$(this).remove();
					}
				});
			}
		});
	<?php
	}

	if (strlen($os) > 0) {
	?>
		$("#div_trocar_produto").hide();
	<?php
	}
	?>

	/**
    * Eventos para anexar imagem
    */
    $("form[name=form_anexo]").ajaxForm({
        complete: function(data) {
			data = $.parseJSON(data.responseText);

			if (data.error) {
				alert(data.error);
			} else {
				var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
				$(imagem).attr({ src: data.link });

				$("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

				var link = $("<a></a>", {
					href: data.href,
					target: "_blank"
				});

				$(link).html(imagem);

				$("#div_anexo_"+data.posicao).prepend(link);

				if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
					setupZoom();
				}

		        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
			}

			$("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
			$("#div_anexo_"+data.posicao).find("button").show();
			$("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
    	}
    });

	$("button[name=anexar]").click(function() {
		var posicao = $(this).attr("rel");

		$("input[name=anexo_upload_"+posicao+"]").click();
	});

	$("input[name^=anexo_upload_]").change(function() {
		var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

		$("#div_anexo_"+i).find("button").hide();
		$("#div_anexo_"+i).find("img.anexo_thumb").hide();
		$("#div_anexo_"+i).find("img.anexo_loading").show();

		$(this).parent("form").submit();
    });

    $("#os_consumidor_revenda").change(function() {
		if ($(this).val() == "R") {
			$("#div_informacoes_consumidor input, #div_informacoes_consumidor select").each(function() {
				$(this).prevAll("h5").remove();
			});
		} else {
			$("#div_informacoes_consumidor input, #div_informacoes_consumidor select").each(function() {
				if ($(this).data("obrigatorio") == 1) {
					if ($(this).prevAll("h5").length == 0) {
						$(this).before("<h5 class='asteristico'>*</h5>");
					}
				}
			});
		}
	});
});

/**
 * Evento de click do botão trocar_produto
 * Irá remover o readonly dos campos referência e descrição e dar show nas lupas
 */
 function alterarProduto(posicao){
 	<?php
	if(in_array($login_fabrica, array(152,180,181,182))){
	?>
 	var entrega_tecnica = $("input[name='produto["+posicao+"][entrega_tecnica]']").val();
 	var count_produto = parseInt($("input[name='count_entrega_tecnica']").val());
	<?php
	}
	?>

 	$("input[name='produto["+posicao+"][id]']").val("");
	$("input[name='produto["+posicao+"][referencia]']").val("");
	$("input[name='produto["+posicao+"][descricao]']").val("");
	$("input[name='produto["+posicao+"][entrega_tecnica]']").val("");
	$("input[name='produto["+posicao+"][qtde]']").val(0);

	$("input[name='produto["+posicao+"][referencia]']")[0].readOnly = true;
	$("input[name='produto["+posicao+"][descricao]']")[0].readOnly = true;

	$("#div_produto_"+posicao).find("span[rel=lupa_produto]").show();
	$("#div_produto_"+posicao).find("div.troca_produto").hide();

	<?php
 	if (isset($fabrica_usa_valor_adicional)) {
 	?>
 		$("#valores_adicionais").html("");
 		valores_adicionais_array = [];
 	<?php
 	}

	if(in_array($login_fabrica, array(152,180,181,182))){
	?>

		if(entrega_tecnica === "hora" && count_produto > 0){
			count_produto = count_produto - 1;
			$("input[name='count_entrega_tecnica']").val(count_produto);

			if(count_produto == 0){
				$("#qtde_horas").hide();
				$("#label_qtde_horas").hide();
			}
		}

	<?php
	}
	?>

 }

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

/**
* Função de retorna todos os dados do Posto
*/
function busca_dados_posto(posto){

	$.ajax({
		url : "<?php echo $_SERVER['PHP_SELF']; ?>",
		type: "POST",
		data: { ajax_posto : posto },
		complete: function(data){
			var arr_posto = new Array();
			var dados = data.responseText;

			arr_posto = dados.split("|");

			$("#consumidor_nome").val(arr_posto[0]);
			$("#consumidor_cnpj").val(arr_posto[1]);
			$("#consumidor_cep").val(arr_posto[2]);
			$("#consumidor_estado").val(arr_posto[3]);
			$("#consumidor_bairro").val(arr_posto[5]);
			$("#consumidor_endereco").val(arr_posto[6]);
			$("#consumidor_numero").val(arr_posto[7]);
			$("#consumidor_complemento").val(arr_posto[8]);
			$("#consumidor_telefone").val(arr_posto[9]);
			$("#consumidor_email").val(arr_posto[10]);

			busca_cidade(arr_posto[3], "consumidor", arr_posto[4]);

		}
	});

}

/**
 * Função de retorno da lupa do posto
 */
function retorna_posto(retorno) {
	/**
	 * A função define os campos código e nome como readonly e esconde o botão
	 * O posto somente pode ser alterado quando clicar no botão trocar_posto
	 * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
	 */
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
	$("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
	$("#div_trocar_posto").show();
	$("#div_informacoes_posto").find("span[rel=lupa]").hide();

	<?php
	if ($areaAdmin === true) {
	?>
		$("#posto_latitude").val(retorno.latitude);
		$("#posto_longitude").val(retorno.longitude);
		$("input[name=lupa_config][tipo=produto]").attr({ posto: retorno.posto });
	<?php
	}
	?>
}

/**
 * Função de retorno da lupa de produto ela já retorna as inforamções do subproduto caso tenha
 */
function retorna_produto(retorno) {
	var div = $("div[id^=div_produto_"+retorno.posicao+"]");

	if ($("div[id^=div_produto_]").find("input[name$='[id]'][value="+retorno.produto+"]").length > 0) {
		alert("Produto já inserido");
		return false;
	}

	var produto_entrega_tecnica = retorno.entrega_tecnica;
	var lancarProduto           = true;
	var linhaProduto            = $("div.linha_produto");

	$.each(linhaProduto, function() {
		var produto_id = $(this).find("input.produto_id").val();

		if (produto_id.length == 0) {
			return;
		}

		var entrega_tecnica = $(this).find("input.produto_entrega_tecnica").val();

		if (entrega_tecnica != produto_entrega_tecnica) {

			lancarProduto = false;
			return false;
		}


	});
	<?
	if($fabrica_usa_valor_adicional){
	?>
		busca_valor_adicional();
	<?php
	}
	?>

	if (lancarProduto === false) {
		switch(produto_entrega_tecnica){
			case "os":
				var modelos = [
					"Smashweld 187 /260 / 318 / 408*"
				];
				break;

			case "equip":
				var modelos = [
					"OrigoMig 408/558/5004",
					"Warrior 400/500",
					"AristoMig 4004 Pulse /5000i/U5000iw",
					"Linha Caddytig 2200",
					"Origotig 3000i",
					"Heliarc 283",
					"Linha LPH"
				];
				break;

			case "hora":
				var modelos = [
					"Crossbow e Automação"
				];
				break;
		}

		alert("O produto selecionado só pode ser lançado junto com os produtos dos seguintes modelos:\n\n"+modelos.join("\n"));
		return false;
	}

	<?php
	if(in_array($login_fabrica, array(152,180,181,182))){
	?>
		if(produto_entrega_tecnica === "hora"){
			$("#qtde_horas").show();
			$("#label_qtde_horas").show();
			var entrega_tecnica = $("input[name='count_entrega_tecnica']").val();

			if(parseInt(entrega_tecnica) == 0){
				$("#qtde_horas").val("");
			}

			entrega_tecnica = parseInt(entrega_tecnica) + 1;

			$("input[name='count_entrega_tecnica']").val(entrega_tecnica);
		}
	<?php
	}
	?>
	$(div).find("input[name$='[id]']").val(retorno.produto);
	$(div).find("input[name$='[referencia]']").val(retorno.referencia);
	$(div).find("input[name$='[descricao]']").val(retorno.descricao);
	$(div).find("input[name$='[entrega_tecnica]']").val(retorno.entrega_tecnica);
	$(div).find("input[name$='[referencia]']")[0].readOnly = true;
	$(div).find("input[name$='[descricao]']")[0].readOnly = true;
	$(div).find("div.troca_produto").show();
	$(div).find("span[rel=lupa_produto]").hide();

	if ($("div[id^=div_produto_][id!=div_produto___model__]").find("input[name$='[id]'][value='']").length == 0) {
		$("button[name=adicionar_produto]").click();
	}
}

/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, consumidor_revenda, cidade) {
	$("#"+consumidor_revenda+"_cidade").find("option").first().nextAll().remove();

	if (estado.length > 0) {
		$.ajax({
			async: false,
			url: "cadastro_os.php",
			type: "POST",
			data: { ajax_busca_cidade: true, estado: estado },
			beforeSend: function() {
				if ($("#"+consumidor_revenda+"_cidade").next("img").length == 0) {
					$("#"+consumidor_revenda+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
				}
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					$.each(data.cidades, function(key, value) {
						var option = $("<option></option>", { value: value, text: value});

						$("#"+consumidor_revenda+"_cidade").append(option);
					});
				}


				$("#"+consumidor_revenda+"_cidade").show().next().remove();
			}
		});
	}

	if(typeof cidade != "undefined" && cidade.length > 0){

		$('#consumidor_cidade option[value='+cidade+']').attr('selected','selected');

	}

}

/**
 * Função que faz um ajax para buscar o cep nos correios
 */
function busca_cep(cep, consumidor_revenda, method) {
	if (cep.length > 0) {
		var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		if (typeof method == "undefined" || method.length == 0) {
            method = "webservice";

            $.ajaxSetup({
                timeout: 3000
            });
        } else {
            $.ajaxSetup({
                timeout: 5000
            });
        }

		$.ajax({
			async: true,
			url: "ajax_cep.php",
			type: "GET",
			data: { ajax: true, cep: cep, method: method },
            beforeSend: function() {
				$("#"+consumidor_revenda+"_estado").hide().after(img.clone());
				$("#"+consumidor_revenda+"_cidade").hide().after(img.clone());
				$("#"+consumidor_revenda+"_bairro").hide().after(img.clone());
				$("#"+consumidor_revenda+"_endereco").hide().after(img.clone());
			},
			error: function(xhr, status, error) {
                busca_cep(cep, consumidor_revenda, "database");
            },
            success: function(data) {
                results = data.split(";");

                if (results[0] != "ok") {
                    alert(results[0]);
                    $("#"+consumidor_revenda+"_cidade").show().next().remove();
                } else {
                    $("#"+consumidor_revenda+"_estado").val(results[4]);

                    busca_cidade(results[4], consumidor_revenda);
                    results[3] = results[3].replace(/[()]/g, '');

                    $("#"+consumidor_revenda+"_cidade").val(retiraAcentos(results[3]).toUpperCase());

                    if (results[2].length > 0) {
                        $("#"+consumidor_revenda+"_bairro").val(results[2]);
                    }

                    if (results[1].length > 0) {
                        $("#"+consumidor_revenda+"_endereco").val(results[1]);
                    }
                }

                $("#"+consumidor_revenda+"_estado").show().next().remove();
                $("#"+consumidor_revenda+"_bairro").show().next().remove();
                $("#"+consumidor_revenda+"_endereco").show().next().remove();

                if ($("#"+consumidor_revenda+"_bairro").val().length == 0) {
                    $("#"+consumidor_revenda+"_bairro").focus();
                } else if ($("#"+consumidor_revenda+"_endereco").val().length == 0) {
                    $("#"+consumidor_revenda+"_endereco").focus();
                } else if ($("#"+consumidor_revenda+"_numero").val().length == 0) {
                    $("#"+consumidor_revenda+"_numero").focus();
                }

                $.ajaxSetup({
                    timeout: 0
                });
            }
		});
	}
}


/**
* Função de busca a Linha do Produto
**/
function busca_valor_adicional(){
	var data = { ajax_busca_valor_adicional: true };

	var produto = $("#produto_id").val();

	if (typeof produto != "undefined" && produto.length > 0) {
		data.produto = produto;
	}


	if (typeof data.produto != "undefined") {
		$.ajax({
			url: "cadastro_os_entrega_tecnica.php",
			type: "POST",
			data: data,
			beforeSend: function() {
				$("#valores_adicionais").find("div.valor_adicional_produto").remove();
			}
		}).always(function(data) {
			data = $.parseJSON(data);

			if (data.valores_adicionais) {
				$.each(data.valores_adicionais, function(key, valor_adicional) {
					var label = $("<label></label>", { class: "checkbox", text: valor_adicional.descricao });


					var input = $("<input />", {
						type: "checkbox",
						class: "checkbox",
						name: "os[valor_adicional][]",
						value: valor_adicional.descricao+"|"+valor_adicional.valor
					});

					if(valor_adicional.editar == true || valor_adicional.editar == "t" ){
						var input2 = $("<input />" , {
							type: "text",
							class: "numeric valor_adicional_valor",
							name: "os[valor_adicional_valor]["+valor_adicional.descricao+"]",
							value:  valor_adicional.valor,
						});
					}else{
							var input2 = $("<input />" , {
							type: "text",
							class: "numeric valor_adicional_valor",
							name: "os[valor_adicional_valor]["+valor_adicional.descricao+"]",
							value:  valor_adicional.valor,
							readonly : "readonly"
						});
					}

					if (valores_adicionais_selecionados.length > 0 && $.inArray($(input).val(), valores_adicionais_selecionados) != -1) {
						$(input).check();
					}

					$(label).prepend(input);
					$(label).append("<br / >");
					$(label).append(input2);

					var div = $("<div></div>", { class: "breadcrumb span8" });
					$(div).html(label);

					$("#valores_adicionais").append(div);
					$(".valor_adicional_valor").priceFormat({
						prefix: '',
						thousandsSeparator: '',
						centsSeparator: '.',
						centsLimit: 2
					});
				});
			}
			valores_adicionais_selecionados = [];

			if ($("input[name='os[valor_adicional][]']:checked").length > 0) {
				$("input[name='os[valor_adicional][]']:checked").each(function() {
					valores_adicionais_selecionados.push($(this).val());
				});
			}
		});
	}
}

/**
 * Função que faz as tentivas de pesquisas do google maps e realiza a rota
 */
 function myCallback (data, i, consumidorLatLng, km_rota) {
 	if (i > lista_enderecos.length) {
 		$('#google_maps_direction').html("<br /><div class='alert alert-block alert-error' style='width: 80%; margin: 0 auto;'><strong>Não foi possível realizar a rota</strong></div>");
 		$('#qtde_km').val(0);
	} else {
		if (data == true) {
			rota(consumidorLatLng, km_rota);
		} else {
			geocodeLatLon(i, myCallback, km_rota);
		}
	}
}


var Map, Markers, Route, Geocoder, geometry;

function calcula_km (km_rota = true) {

    try {

		if ($("#consumidor_cep").val() == "" && $("#consumidor_endereco").val() == "" && $("#consumidor_cidade").val() == "" && $("#consumidor_estado").val() == "") {
			throw new Error('<?=traduz("Digite as informações do consumidor para calcular o KM")?>');
		}

		if ($("#posto_latitude").val() == "" && $("#posto_longitude").val() == "") {
			throw new Error('<?=traduz("Não foi possível determinar a localização do posto")?>');
		}

		var Pais = "Brasil";

	    if (typeof Map !== "object") {
	        Map      = new Map("google_maps");
	        Markers  = new Markers(Map);
	        Router   = new Router(Map);
	        Geocoder = new Geocoder();
	    }

        Geocoder.setEndereco({
            endereco: $("#consumidor_endereco").val(),
            numero: $("#consumidor_numero").val(),
            bairro: $("#consumidor_bairro").val(),
            cidade: $("#consumidor_cidade > option:selected").text(),
            cep: $("#consumidor_cep").val(),
            estado: $("#consumidor_estado").val(),
            pais: Pais
        });
        request = Geocoder.getLatLon();

        request.then(
            function(resposta) {

	            var pLat    = $("#posto_latitude").val();
	            var pLng    = $("#posto_longitude").val();
	            var pLatLng = pLat+","+pLng;
	            
	            var cLat  = resposta.latitude;
	            var cLng  = resposta.longitude;
	            var cLatLng = cLat+","+cLng;

	            if (km_rota == true) {
	              var faz_rota = 'sim';
	            } else {
	              var faz_rota = 'nao';
	            }

	            $.ajax({
	                url: "controllers/TcMaps.php",
	                type: "POST",
	                data: {ajax: "route", origem: pLatLng, destino: cLatLng, ida_volta: faz_rota},
	                timeout: 60000
	            }).done(function(data){
	                data = JSON.parse(data);

	                geometry = data.rota.routes[0].geometry;
	                var kmtotal = parseFloat(data.total_km).toFixed(2);

					$("#google_maps").show();
	                Map.load();

					/* Marcar pontos no mapa */
					Markers.remove();    
					Markers.clear();    
					Markers.add(cLat, cLng, "blue", "Cliente");
					Markers.add(pLat, pLng, "red", "Posto");
					Markers.render();
					Markers.focus();    

					Router.remove();
					Router.clear();
					Router.add(Polyline.decode(geometry));
					Router.render();

					$('#qtde_km').val(kmtotal);
					$('#qtde_km_hidden').val(kmtotal);
					$('#loading-map').hide();
                }).fail(function(){
                    $('#loading-map').hide();
                    alert('<?=traduz("Erro ao tentar calcular a rota!")?>');
                });
            },
            function(erro) {
                $('#loading-map').hide();
                alert(erro);
            }
        );
    } catch(e) {
        $('#loading-map').hide();
        alert(e.message);
    }
}


/**
 * Função que trata os dados do consumidor para pesquisar a latitude e longitude
 */
function calcula_km_ant (km_rota) {
	if (typeof km_rota == "undefined") {
		var km_rota = false;
	}

	if ($("#posto_id").val().length == 0) {
		throw new Error('<?=traduz("Selecione um Posto Autorizado")?>');
	}

	if ($("#posto_latitude").val().length == 0 && $("#posto_longitude").val().length == 0) {
		throw new Error('<?=traduz("Posto Autorizado sem latitude e longitude")?>');
	}

	if ($("#consumidor_cep").val() == "" && $("#consumidor_endereco").val() == "" && $("#consumidor_cidade").val() == "" && $("#consumidor_estado").val() == "") {
		throw new Error('<?=traduz("Digite as informações do consumidor para calcular o KM")?>');
	}

	var c = [
		$("#consumidor_endereco").val(),
		$("#consumidor_numero").val(),
		$("#consumidor_bairro").val(),
		$("#consumidor_cidade > option:selected").text(),
		$("#consumidor_estado").val()
	];

	var consumidor_endereco = c.join(", ");

	delete(c[2]);
	var consumidor_endereco_sem_bairro = c.join(" ,");

	var consumidor_cep = "cep " + $("#consumidor_cep").val();

	delete c[0];
	delete c[1];
	var consumidor_cidade = c.join(", ");

	lista_enderecos = [consumidor_endereco, consumidor_endereco_sem_bairro, consumidor_cep, consumidor_cidade];

	geocodeLatLon(0, myCallback, km_rota);
}

/**
 * Função que pesquisa a latitude e longitude do consumidor
 */
function geocodeLatLon_ant (i, callback, km_rota) {
	geocoder = new google.maps.Geocoder();

	geocoder.geocode( { 'address': lista_enderecos[i] }, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			/* Endereço retornado pelo Google */
			var destino = results[0].address_components;
			var estadoConsumidor = $("#consumidor_estado").val();
			var cidadeConsumidor = $("#consumidor_cidade > option:selected").text();

			var estadoComp = '';
			var cidadeComp = '';
			var bairro = '';
			var endereco = '';
			var consumidorLatLng = '';

			$.each(destino, function (key, value) {
				if ($.inArray("administrative_area_level_1", value.types) != -1) {
					estadoComp = value.short_name;
				} else if ($.inArray("administrative_area_level_2", value.types) != -1 || $.inArray("locality", value.types) != -1) {
					cidadeComp = value.long_name;
				} else if ($.inArray("neighborhood", value.types) != -1) {
					bairro = value.long_name;
				} else if ($.inArray("route", value.types) != -1) {
					endereco = value.long_name;
				}
			});

			var cidadesIguais = false;
			var estadosIguais = false;

			 // Reescreve a Sigla do estado para o nome completo
			var estadoConsumidor2 = estadoConsumidor;

			var comp1 = [];
			var comp2 = [];

			var seq = 0;

			if (cidadeComp.length > 0) {
				cidadeComp       = retiraAcentos(cidadeComp);
				cidadeConsumidor = retiraAcentos(cidadeConsumidor);

				if (cidadeComp.trim() == cidadeConsumidor.trim()) {
					cidadesIguais = true;
				}
			}

			if (estadoComp.length > 0) {
				estadoComp       = retiraAcentos(estadoComp);
				estadoConsumidor = retiraAcentos(estadoConsumidor);

				if (estadoComp.trim() == estadoConsumidor.trim() || estadoComp.trim() == estadoConsumidor2.trim()) {
					estadosIguais = true;
				}
			}

			if (cidadesIguais == true && estadosIguais == true) {
				consumidorLatLng = results[0].geometry.location;
				consumidorLatLng = consumidorLatLng.toString();

				callback(true, null, consumidorLatLng, km_rota);
			} else {
				callback(false, ++i, null, km_rota);
			}
		} else {
			$('#google_maps_direction').html("<br /><div class='alert alert-block alert-error' style='width: 80%; margin: 0 auto;'><strong>Não foi possível realizar a rota</strong></div>");
			$('#qtde_km').val(0);
		}
	});
}

/**
 * Função que realiza a roda do consumidor ao posto
 */
function rota_ant (consumidorLatLng, km_rota) {
	if (posto_marker != null) {
		posto_marker.setMap(null);
	}

	if (consumidor_marker != null) {
		consumidor_marker.setMap(null);
	}

	if (directionsRenderer != null) {
		directionsRenderer.setMap(null);
	}

	var postoLatLng      = new google.maps.LatLng($("#posto_latitude").val(), $("#posto_longitude").val());
	consumidorLatLng = consumidorLatLng.replace("(", "");
	consumidorLatLng = consumidorLatLng.replace(")", "");

	var parte = consumidorLatLng.split(',');
	var lat = parte[0];
	var lng = parte[1];

	var consumidorLatLng = new google.maps.LatLng(lat, lng);

	consumidor_marker = new google.maps.Marker({
		icon: 'https://mts.googleapis.com/vt/icon/name=icons/spotlight/spotlight-waypoint-b.png&text=B&psize=16&font=fonts/Roboto-Regular.ttf&color=ff333333&ax=44&ay=48&scale=1',
		map: map,
		position: consumidorLatLng
	});

	posto_marker = new google.maps.Marker({
		icon: 'https://mts.googleapis.com/vt/icon/name=icons/spotlight/spotlight-waypoint-a.png&text=A&psize=16&font=fonts/Roboto-Regular.ttf&color=ff333333&ax=44&ay=48&scale=1',
		position: postoLatLng,
		map: map
	});

	directionsService  = new google.maps.DirectionsService();
	directionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: true, zoom: 5 });
	directionsRenderer.setMap(map);
	$("#google_maps_direction").html("");
	directionsRenderer.setPanel(document.getElementById("google_maps_direction"));

	directionsService.route({ origin: postoLatLng, destination: consumidorLatLng, travelMode: google.maps.DirectionsTravelMode.DRIVING }, function(response, status){
		if (status == google.maps.DirectionsStatus.OK) {
			directionsRenderer.setDirections(response);
			var km1 = response.routes[0].legs[0].distance.value;
			var km2 = 0;

			var directionsService  = new google.maps.DirectionsService();
			directionsService.route({ origin: consumidorLatLng, destination: postoLatLng, travelMode: google.maps.DirectionsTravelMode.DRIVING }, function(response, status){
				km2 = response.routes[0].legs[0].distance.value;

				if (km_rota == false) {
					$("#qtde_km").val(((km1 + km2) / 1000).toFixed(2));
					$("#qtde_km_hidden").val(((km1 + km2) / 1000).toFixed(2));
				}

				if(km_rota == true && $('#qtde_km_hidden').val() != "" && $('#qtde_km').val() > 0){
					$('#qtde_km').val($('#qtde_km_hidden').val());
				}

			});
		} else {
			$('#google_maps_direction').html("<br /><div class='alert alert-block alert-error' style='width: 80%; margin: 0 auto;'><strong>Não foi possível realizar a rota</strong></div>");
			$('#qtde_km').val(0);
		}
	});
}

/**
 * Função de retorno da lupa de revenda
 */
function retorna_revenda(retorno) {
	$("#revenda_nome").val(retorno.razao);
	$("#revenda_cnpj").val(retorno.cnpj);

	if (retorno.cep.length > 0) {
		$("#revenda_cep").val(retorno.cep);

		busca_cep(retorno.cep, "revenda");

		if ($("#revenda_bairro").val().length == 0) {
			$("#revenda_bairro").val(retorno.bairro);
		}

		if ($("#revenda_endereco").val().length == 0) {
			$("#revenda_endereco").val(retorno.endereco);
		}
	} else {
		$("#revenda_estado").val(retorno.estado);

		busca_cidade(retorno.estado, "revenda");

		$("#revenda_cidade").val(retiraAcentos(retorno.cidade_nome).toUpperCase());
		$("#revenda_bairro").val(retorno.bairro);
		$("#revenda_endereco").val(retorno.endereco);
	}

	$("#revenda_numero").val(retorno.numero);
	$("#revenda_complemento").val(retorno.complemento);
	$("#revenda_telefone").val(retorno.fone);
}
</script>

<?php if(count($msg_erro["msg"]) > 0) { ?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
<?php } ?>

<form name="frm_os" method="POST" class="form-search form-inline" enctype="multipart/form-data" >

	<?php
	$sqlTipoAtendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = 145 AND grupo_atendimento = 'R'";
	$resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

	$tipo_atendimento = pg_fetch_result($resTipoAtendimento, 0, "tipo_atendimento");
	?>
	<input type="hidden" name="os[tipo_atendimento]" value="<?=$tipo_atendimento?>" />

	<div class="row"> <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b> </div>

	<?php
	if ($areaAdmin === true) {

		if ((count($msg_erro["msg"]) > 0 && strlen(getValue("posto[id]")) > 0) && !strlen($os)) {
			$posto_readonly     = "readonly='readonly'";
			$posto_esconde_lupa = "style='display: none;'";
			$posto_mostra_troca = "style='display: block;'";
		}

		if (strlen($os) > 0 && strlen(getValue("posto[id]")) > 0) {
			$posto_readonly     = "readonly='readonly'";
			$posto_esconde_lupa = "style='display: none;'";
		}
	?>
		<div id="div_informacoes_posto" class="tc_formulario">
			<div class="titulo_tabela"><?=traduz('Informações do Posto Autorizado')?></div>

			<br />

			<input type="hidden" id="posto_id" name="posto[id]" value="<?=getValue('posto[id]')?>" />

			<div class="row-fluid">
				<div class="span2"></div>

				<div class="span4">
					<div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="posto_codigo"><?=traduz('Código')?></label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<h5 class="asteristico">*</h5>
								<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
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
						<label class="control-label" for="posto_nome"><?=traduz('Nome')?></label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<h5 class="asteristico">*</h5>
								<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
				</div>

				<div class="span2"></div>
			</div>

			<div id="div_trocar_posto" class="row-fluid" <?=$posto_mostra_troca?> >
				<div class="span2"></div>
				<div class="span10">
					<button type="button" id="trocar_posto" class="btn btn-danger" ><?=traduz('Alterar Posto Autorizado')?></button>
				</div>
			</div>
		</div>
		<br />
	<?php
	} else {
		echo "<input type='hidden' id='posto_id' name='posto[id]' value='{$login_posto}' />";

		if (strlen(getValue("os[hd_chamado]")) > 0) {
			echo "<input type='hidden' name='os[hd_chamado]' value='".getValue("os[hd_chamado]")."' />";
		}
	}

	if ($areaAdmin === true) {
		if (strlen(getValue("posto[id]")) > 0) {
			$sql = "SELECT latitude, longitude FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = ".getValue("posto[id]");
			$res = pg_query($con, $sql);
		}
	} else {
		$sql = "SELECT latitude, longitude FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
		$res = pg_query($con, $sql);
	}

	if (pg_num_rows($res) > 0) {
		$posto_latitude  = pg_fetch_result($res, 0, "latitude");
		$posto_longitude = pg_fetch_result($res, 0, "longitude");
	}

	echo "<input type='hidden' id='posto_latitude' value='{$posto_latitude}' disabled='disabled' />";
	echo "<input type='hidden' id='posto_longitude' value='{$posto_longitude}' disabled='disabled' />";
	?>

	<div id="div_informacoes_os" class="tc_formulario">
		<div class="titulo_tabela"><?=traduz('Informações da OS')?></div>

		<br />

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group <?=(in_array('os[data_abertura]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_abertura"><?=traduz('Data Abertura')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["os|data_abertura"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input id="data_abertura" name="os[data_abertura]" class="span12" type="text" value="<?=getValue('os[data_abertura]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class='control-group <?=(in_array('os[data_compra]', $msg_erro['campos'])) ? "error" : "" ?>'>
					<label class="control-label" for="data_compra"><?=traduz('Data Compra')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["os|data_compra"]["obrigatorio"] == true || $login_fabrica == 145) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="data_compra" name="os[data_compra]" class="span12" type="text" value="<?=getValue('os[data_compra]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>


		<div class="row-fluid">
			<div class="span2"></div>


			<div class="span4">
				<div class='control-group <?=(in_array('os[nota_fiscal]', $msg_erro['campos'])) ? "error" : "" ?>'>
					<label class="control-label" for="nota_fiscal"><?=traduz('Nota Fiscal')?></label>

					<?php
						if(isset($valida_anexo)){
							?>
							<strong  style="color:#B94A48">(<?=traduz('Anexo Obrigatório')?>)</strong >
							<?php
						}
					?>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["os|nota_fiscal"]["obrigatorio"] == true || $login_fabrica == 145) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="nota_fiscal" name="os[nota_fiscal]" class="span12" type="text" value="<?=getValue('os[nota_fiscal]')?>" maxlength="20" />
						</div>
					</div>
				</div>
			</div>



			<div class="span4">
				<div class='control-group <?=(in_array('os[consumidor_revenda]', $msg_erro['campos'])) ? "error" : "" ?>'>
					<label class="control-label" for="consumidor_revenda"><?=traduz('Tipo de OS')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class="asteristico">*</h5>
							<select id="os_consumidor_revenda" name="os[consumidor_revenda]" >
								<option value="C" ><?=traduz('Consumidor')?></option>
								<option value="R" <?=(getValue("os[consumidor_revenda]") == "R") ? "selected" : ""?> ><?=traduz('Revenda')?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4" >
				<div class='control-group <?=(in_array('os[qtde_horas]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" id="label_qtde_horas" for="qtde_horas"><?=traduz('Hora Técnica')?> <strong style='color: #b94a48' >(<?=traduz('informar em minutos')?>)</strong></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["os|qtde_horas"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input type="text" class="span12" id="qtde_horas" name="os[qtde_horas]"  value="<?=getValue('os[qtde_horas')?>" min="0" />
							<input type="hidden" class="span12" id="count_entrega_tecnica" name="count_entrega_tecnica"  value="<?=$count_produto?>" min="0" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>

		</div>
	</div>
	<br />

	<div id="div_informacoes_consumidor" class="tc_formulario">
		<div class="titulo_tabela"><?=traduz('Informações do Consumidor')?></div>

		<br />

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span3">
				<div class='control-group <?=(in_array('consumidor[nome]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_nome"><?=traduz('Nome')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["consumidor|nome"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input id="consumidor_nome" name="consumidor[nome]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|nome"]["obrigatorio"]?>" value="<?=getValue('consumidor[nome]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group <?=(in_array('consumidor[cpf]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_cpf">
					<?
					if (strlen($os)){
						echo 'CPF/CNPJ';
					}else {
					?>
					<?=traduz('CPF')?> <input type="radio" id="cpf_cnpj" name="cnpjCpf" <? echo (getValue('cnpjCpf')=='cpf') ? 'checked="checked"': ''; ?> value="cpf" >
					/<?=traduz('CNPJ')?> <input type="radio" id="cnpj_cpf" name="cnpjCpf" <? echo (getValue('cnpjCpf')=='cnpj') ? 'checked="checked"': ''; ?> value="cnpj" >
					<?
					}
					?>
					</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["consumidor|cpf"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input id="consumidor_cpf" name="consumidor[cpf]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|cpf"]["obrigatorio"]?>" value="<?=getValue('consumidor[cpf]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array('consumidor[cep]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_cep"><?=traduz('CEP')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["consumidor|cep"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input id="consumidor_cep" name="consumidor[cep]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|cep"]["obrigatorio"]?>" value="<?=getValue('consumidor[cep]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="control-group <?=(in_array('consumidor[estado]', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="consumidor_estado"><?=traduz('Estado')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|estado"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>

							<select id="consumidor_estado" name="consumidor[estado]" data-obrigatorio="<?=$regras["consumidor|estado"]["obrigatorio"]?>" class="span12">
								<option value="" ><?=traduz('Selecione')?></option>
								<?php
								#O $array_estados está no arquivo funcoes.php
								foreach ($array_estados() as $sigla => $nome_estado) {
									$selected = ($sigla == getValue('consumidor[estado]')) ? "selected" : "";

									echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
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
				<div class="control-group <?=(in_array('consumidor[cidade]', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="consumidor_cidade"><?=traduz('Cidade')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["consumidor|cidade"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>

							<select id="consumidor_cidade" name="consumidor[cidade]" data-obrigatorio="<?=$regras["consumidor|cidade"]["obrigatorio"]?>" class="span12" >
								<option value="" ><?=traduz('Selecione')?></option>

								<?php

								if (strlen(getValue("consumidor[estado]")) > 0) {
									$sql = "SELECT DISTINCT * FROM (
												SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".getValue("consumidor[estado]")."')
												UNION (
													SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".getValue("consumidor[estado]")."')
												)
											) AS cidade
											ORDER BY cidade ASC";
									$res = pg_query($con, $sql);

									if (pg_num_rows($res) > 0) {
										while ($result = pg_fetch_object($res)) {
											$selected  = (trim($result->cidade) == trim(getValue("consumidor[cidade]"))) ? "SELECTED" : "";

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

			<div class="span3">
				<div class='control-group <?=(in_array('consumidor[bairro]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_bairro"><?=traduz('Bairro')?></label>
					<div class="controls controls-row">
						<div class="span12">
						<?php
						if ($regras["consumidor|bairro"]["obrigatorio"] == true) {
							echo "<h5 class='asteristico'>*</h5>";
						}
						?>
						<input id="consumidor_bairro" name="consumidor[bairro]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|bairro"]["obrigatorio"]?>" value="<?=str_replace('\\','',getValue('consumidor[bairro]')) ?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group <?=(in_array('consumidor[endereco]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_endereco"><?=traduz('Endereço')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|endereco"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_endereco" name="consumidor[endereco]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|endereco"]["obrigatorio"]?>" value="<?=str_replace('\\','',getValue('consumidor[endereco]')) ?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span1">
				<div class='control-group <?=(in_array('consumidor[numero]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_numero"><?=traduz('Número')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|numero"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_numero" name="consumidor[numero]" class="span12 numeric" type="text" data-obrigatorio="<?=$regras["consumidor|numero"]["obrigatorio"]?>" value="<?=getValue('consumidor[numero]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span1"></div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span2">
				<div class='control-group <?=(in_array('consumidor[complemento]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_complemento"><?=traduz('Complemento')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|complemento"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_complemento" name="consumidor[complemento]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|complemento"]["obrigatorio"]?>" value="<?=str_replace('\\','',getValue('consumidor[complemento]')) ?>" maxlength="20" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class="control-group <?=(in_array('consumidor[telefone]', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="consumidor_telefone"><?=traduz('Telefone')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|telefone"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_telefone" name="consumidor[telefone]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|telefone"]["obrigatorio"]?>" value="<?=getValue('consumidor[telefone]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class="control-group <?=(in_array('consumidor[celular]', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="consumidor_celular"><?=traduz('Celular')?></label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if (($regras["consumidor|celular"]["obrigatorio"] == true && $os_revenda === false) || ($login_fabrica == 151  && $os_revenda === false)) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input id="consumidor_celular" name="consumidor[celular]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|celular"]["obrigatorio"]?>" value="<?=getValue('consumidor[celular]')?>" <?=$readonly?> maxlength="20" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span4">
				<div class='control-group <?=(in_array('consumidor[email]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_email">Email</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|email"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_email" name="consumidor[email]" class="span12" type="text" data-obrigatorio="<?=$regras["consumidor|email"]["obrigatorio"]?>" value="<?=getValue('consumidor[email]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span1"></div>
		</div>

	</div>
	<br />

	<?php
	if (strlen($os) > 0 && $areaAdmin === false) {
	?>
		<style>
			#div_informacoes_deslocamento > div.tc_formulario {
				height: auto;
			}
		</style>
	<?php
	}
	?>
	<div id="div_informacoes_deslocamento" <?=$mostra_mapa?> >
		<div class="tc_formulario" style="padding-bottom: <?=$px?>px;height: block;">
			<div class="titulo_tabela"><?=traduz('Informações do Deslocamento')?></div>

			<?php
			if (!in_array($login_fabrica, array(143))) {
			?>
				<div id="google_maps" <?=$style?> ></div>
				<!-- <div id="google_maps_direction" <?//=//$style?> ></div> -->
			<?php
			}
			if($login_fabrica == 145 && strlen($os) > 0){
				$sql = "SELECT tipo_atendimento FROM tbl_os WHERE os = {$os} and fabrica = {$login_fabrica} ";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res)>0 and strlen($tipo_atendimento=pg_fetch_result($res, 0,0))>0){
					$digita_km = 'readonly="readonly"';
				}
			}

			?>

			<br />

			<div class="row-fluid">
				<div class="span1"></div>

				<div class="span10" style="padding-top: 5px;height: auto;">
					<div class='control-group <?=(in_array('os[qtde_km]', $msg_erro['campos'])) ? "error" : "" ?> tac' >
						<label class="control-label" id="box_desc_distancia" for="box_desc_distancia"><?=traduz('Distância')?> <span style="color: #FF0000;">(<?=traduz('a distância já é calculada a ida e a volta')?>)</span></label>
						<div class="controls controls-row">
							<div class="span12 tac">
								<span id="info_km">
									<h5 class="asteristico" style="float: none; display: inline;">*</h5>
									<input id="qtde_km" name="os[qtde_km]" class="span2" type="text" value="<?=number_format(getValue('os[qtde_km]'), 2, '.', '')?>" <?=$digita_km?>/>
								</span>
								<input id="qtde_km_hidden" name="os[qtde_km_hidden]" type="hidden" value="<?=getValue('os[qtde_km_hidden]')?>" />
								<?php
								if (!in_array($login_fabrica, array(143)) AND empty($digita_km)) {
								?>
									<button type="button" id="calcular_km" class="btn btn-primary btn-small" ><?=traduz('Calcular KM')?></button>
								<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="span1"></div>
			</div>

			<?php
			if (in_array($login_fabrica, array(152,180,181,182))) {
			?>
				<div class="row-fluid">
					<div class="span1"></div>

					<div class="span10" style="padding-top: 5px;height: auto;">
						<div class='control-group tac' >
							<label class="control-label" for="os_tempo_deslocamento"><?=traduz('Tempo de Deslocamento')?> <span style="color: #FF0000;">(informar em horas)</span></label>
							<div class="controls controls-row">
								<div class="span12 tac">
									<input id="os_tempo_deslocamento" name="os[tempo_deslocamento]" class="span1 numeric" type="text" value="<?=getValue('os[tempo_deslocamento]')?>" />
								</div>
							</div>
						</div>
					</div>

					<div class="span1"></div>
				</div>
			<?php
			}
			?>
		</div>
		<br />
	</div>

	<div id="div_informacoes_revenda" class="tc_formulario">
			<div class="titulo_tabela"><?=($login_fabrica == 145) ? traduz("Informações da Revenda/Construtora") : traduz("Informações da Revenda")?></div>

			<br />

			<input type="hidden" name="revenda[id]" value="" />

			<div class="row-fluid">
				<div class="span1"></div>

				<div class="span4">
					<div class='control-group <?=(in_array('revenda[nome]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="revenda_nome"><?=traduz('Nome')?></label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<?php
									if ($regras["revenda|nome"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input id="revenda_nome" name="revenda[nome]" class="span12" type="text" maxlength="50" value="<?=getValue('revenda[nome]')?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
							</div>
						</div>
					</div>
				</div>

				<div class="span3">
					<div class='control-group <?=(in_array('revenda[cnpj]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="revenda_cnpj"><?=traduz('CNPJ')?></label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<?php
									if ($regras["revenda|cnpj"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input id="revenda_cnpj" name="revenda[cnpj]" class="span12" type="text" value="<?=getValue('revenda[cnpj]')?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
							</div>
						</div>
					</div>
				</div>

				<div class="span2">
					<div class='control-group <?=(in_array('revenda[cep]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="revenda_cep"><?=traduz('CEP')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
									if ($regras["revenda|cep"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input id="revenda_cep" name="revenda[cep]" class="span12" type="text" value="<?=getValue('revenda[cep]')?>" />
							</div>
						</div>
					</div>
				</div>

				<div class="span1"></div>
			</div>

			<div class="row-fluid">
				<div class="span1"></div>

				<div class="span2">
					<div class="control-group <?=(in_array('revenda[estado]', $msg_erro['campos'])) ? "error" : "" ?>">
						<label class="control-label" for="revenda_estado"><?=traduz('Estado')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
									if ($regras["revenda|estado"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<select id="revenda_estado" name="revenda[estado]" class="span12" >
									<option value="" ><?=traduz('Selecione')?></option>
									<?php
									#O $array_estados está no arquivo funcoes.php
									$rev_uf = getValue('revenda[estado]');
									foreach ($array_estados() as $sigla => $nome_estado) {
										$selected = ($sigla == $rev_uf) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected}>{$nome_estado}</option>";
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class="span3">
					<div class="control-group <?=(in_array('revenda[cidade]', $msg_erro['campos'])) ? "error" : "" ?>">
						<label class="control-label" for="revenda_cidade"><?=traduz('Cidade')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
									if ($regras["revenda|cidade"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<select id="revenda_cidade" name="revenda[cidade]" class="span12" >
									<option value="" ><?=traduz('Selecione')?></option>

									<?php

									if (strlen($rev_uf = getValue("revenda[estado]")) > 0) {
										$sql = "SELECT * FROM (
													SELECT UPPER(TRIM(fn_retira_especiais(cidade))) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('$rev_uf')
													UNION (
														SELECT UPPER(TRIM(fn_retira_especiais(nome))) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('$rev_uf')
													)
												) AS cidade
												ORDER BY cidade ASC";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) > 0) {
											$rev_cidade = getValue("revenda[cidade]");
											while ($result = pg_fetch_object($res)) {
												$selected = ($result->cidade == $rev_cidade) ? " selected" : "";

												echo "\t<option value='{$result->cidade}'{$selected}>{$result->cidade}</option>\n";
											}
										}
									}

									?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class="span3">
					<div class='control-group <?=(in_array('revenda[bairro]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="revenda_bairro"><?=traduz('Bairro')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
									if ($regras["revenda|bairro"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input id="revenda_bairro" name="revenda[bairro]" class="span12" type="text" value="<?=getValue('revenda[bairro]')?>" />
							</div>
						</div>
					</div>
				</div>

				<div class="span1"></div>
			</div>

			<div class="row-fluid">
				<div class="span1"></div>

				<div class="span3">
					<div class='control-group <?=(in_array('revenda[endereco]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="revenda_endereco"><?=traduz('Endereço')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
									if ($regras["revenda|endereco"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input id="revenda_endereco" name="revenda[endereco]" class="span12" type="text" value="<?=getValue('revenda[endereco]')?>" />
							</div>
						</div>
					</div>
				</div>

				<div class="span1">
					<div class='control-group <?=(in_array('revenda[numero]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="revenda_numero"><?=traduz('Número')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
									if ($regras["revenda|numero"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input id="revenda_numero" name="revenda[numero]" class="span12" type="text" value="<?=getValue('revenda[numero]')?>" />
							</div>
						</div>
					</div>
				</div>

				<div class="span2">
					<div class='control-group <?=(in_array('revenda[complemento]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="revenda_complemento"><?=traduz('Complemento')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
									if ($regras["revenda|complemento"]["obrigatorio"] == true) {
										echo "<h5 class='asteristico'>*</h5>";
									}
								?>
								<input id="revenda_complemento" name="revenda[complemento]" class="span12" type="text" value="<?=getValue('revenda[complemento]')?>" />
							</div>
						</div>
					</div>
				</div>

				<div class="span3">
					<div class="control-group <?=(in_array('revenda[telefone]', $msg_erro['campos'])) ? "error" : "" ?>">
						<label class="control-label" for="revenda_telefone"><?=traduz('Telefone')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<?php
								if ($regras["revenda|telefone"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
								?>
								<input id="revenda_telefone" name="revenda[telefone]" class="span12" type="text" value="<?=getValue('revenda[telefone]')?>" />
							</div>
						</div>
					</div>
				</div>

				<div class="span1"></div>
			</div>
		</div>

		<br />

	<div id="div_informacoes_produto" class="tc_formulario">
		<div class="titulo_tabela"><?=traduz('Informações do Produto')?> <?php echo (in_array($login_fabrica, array(152,180,181,182))) ? traduz("(Favor clicar na lupa para selecionar)") : "" ?> </div>

		<?php
		$produtos = getValue("produto");
		?>

		<div id="modelo_produto" style="display: none;">

			<div class="row-fluid linha_produto" id="div_produto___model__">

				<input type="hidden"  class="produto_id" name="produto[__model__][id]" value=""  />
				<input type="hidden" name="produto[__model__][os_produto]" value="" />
				<input type="hidden"  class="produto_entrega_tecnica" name="produto[__model__][entrega_tecnica]" value="" />

				<div class="span1"></div>

				<div class="span2">
					<div class='control-group' >
						<label class="control-label" ><?=traduz('Referência')?></label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input name="produto[__model__][referencia]" class="span12" type="text" value="" readonly />
								<span class="add-on" rel="lupa_produto" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" entrega="true" posto="<?=$login_posto?>" posicao="__model__" ativo="t" />
							</div>
						</div>
					</div>
				</div>

				<div class="span5">
					<div class='control-group' >
						<label class="control-label" ><?=traduz('Descrição')?></label>
						<div class="controls controls-row">
							<div class="span11 input-append">
								<input name="produto[__model__][descricao]" class="span12" type="text" value="" readonly />
								<span class="add-on" rel="lupa_produto" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao"  entrega="true" posto="<?=$login_posto?>" posicao="__model__" ativo="t" />
							</div>
						</div>
					</div>
				</div>

				<div class="span1">
					<div class='control-group' >
						<label class="control-label" ><?=traduz('Qtde')?></label>
						<div class="controls controls-row">
							<div class="span12">
								<input name="produto[__model__][qtde]" id="qtde___model__" class="span12 numeric" type="text" value="0" />
							</div>
						</div>
					</div>
				</div>

				<div class="span2 troca_produto" style="display: none;" >
					<div style='padding-top: 20px;'>
						<button type="button" class="btn btn-danger" onclick="alterarProduto(__model__)"><?=traduz('Alterar Produto')?></button>
					</div>
				</div>

			</div>

			<div style="clear: both;"></div>

		</div>

		<br />

		<div id="div_produto">
			<?php
			$qtde_produtos_lancado = 0;

			foreach ($produtos as $key => $produto) {
				if (!empty($produto["id"])) {
					$qtde_produtos_lancado++;
				}
			}

			if (empty($qtde_produtos_lancado)) {
				$qtde_produtos = 3;
			} else {
				$qtde_produtos = $qtde_produtos_lancado + 3;
			}

			for ($i = 0; $i < $qtde_produtos; $i ++) {
				if (!empty($produtos[$i]["id"])) {
					$id_produto      = $produtos[$i]["id"];
					$os_produto      = $produtos[$i]["os_produto"];
					$referencia      = $produtos[$i]["referencia"];
					$descricao       = $produtos[$i]["descricao"];
					$qtde            = $produtos[$i]["qtde"];
					$entrega_tecnica = $produtos[$i]["entrega_tecnica"];
				} else {
					unset($id_produto, $os_produto, $referencia, $descricao, $qtde);
				}

				$produto_esconde_troca = "style='display: inline-block;'";
				$produto_esconde_lupa  = "style='display: inline-block;'";

				if (empty($id_produto)) {
					$produto_esconde_troca = "style='display: none;'";
				}

				if (!empty($id_produto)) {
					$produto_esconde_lupa  = "style='display: none;'";
					$produto_readonly = "readonly='readonly'";
				} else {
					unset($produto_readonly);
				}

				if (count($msg_erro["msg"]) > 0 && in_array("produto[$i]", $msg_erro["campos"])) {
					$bgcolor = "style='background-color: #F2DEDE'";
				} else {
					unset($bgcolor);
				}

				?>

				<div class="row-fluid linha_produto" id="div_produto_<?=$i?>" <?=$bgcolor?> >
					<input type="hidden" class="produto_id" name="produto[<?=$i?>][id]" value="<?=$id_produto?>" />
					<input type="hidden" name="produto[<?=$i?>][os_produto]" value="<?=$os_produto?>" />
					<input type="hidden" class="produto_entrega_tecnica" name="produto[<?=$i?>][entrega_tecnica]" value="<?=$entrega_tecnica?>" />

					<div class="span1"></div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><?=traduz('Referência')?></label>
							<div class="controls controls-row">
								<div class="span10 input-append">
									<input name="produto[<?=$i?>][referencia]" class="span12" type="text" readonly value="<?=$referencia?>"  <?=$produto_readonly?> />
									<span class="add-on" rel="lupa_produto" <?=$produto_esconde_lupa?> >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" entrega="true"  posto="<?=$login_posto?>" posicao="<?=$i?>" ativo="t" />
								</div>
							</div>
						</div>
					</div>

					<div class="span5">
						<div class='control-group' >
							<label class="control-label" ><?=traduz('Descrição')?></label>
							<div class="controls controls-row">
								<div class="span11 input-append">
									<input name="produto[<?=$i?>][descricao]" class="span12" type="text" readonly value="<?=$descricao?>" <?=$produto_readonly?> />
									<span class="add-on" rel="lupa_produto" <?=$produto_esconde_lupa?> >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" entrega="true"  posto="<?=$login_posto?>" posicao="<?=$i?>" ativo="t" />
								</div>
							</div>
						</div>
					</div>
					<div class="span1">
						<div class='control-group' >
							<label class="control-label" ><?=traduz('Qtde')?></label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="produto[<?=$i?>][qtde]" id="qtde_<?=$i?>" class="span12 numeric" type="text" value="<?=(empty($qtde)) ? 0 : $qtde?>" />
								</div>
							</div>
						</div>
					</div>

					<div class="span2 troca_produto" <?=$produto_esconde_troca?> >
						<div style='padding-top: 20px;'>
							<button type="button" class="btn btn-danger" onclick="alterarProduto(<?=$i?>)" ><?=traduz('Alterar Produto')?></button>
						</div>
					</div>
				</div>

				<div style="clear: both;"></div>

			<?php
			}
			?>
		</div>

		<br />
		<p class="tac">
			<button type="button" name="adicionar_produto" class="btn btn-primary" ><?=traduz('Adicionar novo produto')?></button>
		</p>
		<br />
	</div>

	<?php
	if($fabrica_usa_valor_adicional) {
		$vi = 0;

		$display_valores_adicionais = "style='display: none;'";

		if(strlen(getValue("produto[id]")) > 0){
			if (isset($os)) {
				$sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
				$resVAOS = pg_query($con, $sql);
			}
		}

		$sql = "SELECT valores_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica} ";
		$resVA = pg_query($con,$sql);

		if (pg_num_rows($resVAOS) > 0 || pg_num_rows($resVA) > 0) {
			$display_valores_adicionais = "style='display: block;'";
		}

		$valor_adicional_selecionado = array();

		if (count(getValue("os[valor_adicional]")) > 0) {
			foreach (getValue("os[valor_adicional]") as $key => $value) {
				list($name, $valor) = explode("|", $value);
				$valor_adicional_selecionado[] = $name;
			}

			if (pg_num_rows($resVAOS) > 0 && !count($msg_erro["msg"])) {
				$valores = json_decode(pg_fetch_result($resVAOS, 0, "valores_adicionais"), true);

				if (is_array($valores)) {
					foreach ($valores as $key => $value) {
						$value_key = array_keys($value);

						$valor_adicional_selecionado[] = $value_key[0];
					}
				}
			}
		}
		?>
		<br />
		<div id="div_informacoes_solucao" class="tc_formulario" <?=$display_valores_adicionais?> >
			<div class="titulo_tabela"><?=traduz('Valores Adicionais')?></div>

			<br />
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<div class='row-fluid'>
						<div id="valores_adicionais">
							<?php
							if (pg_num_rows($resVA) > 0) {
								while ($result = pg_fetch_object($resVA)) {
									$v_adicional = $result->valores_adicionais;
									$v_adicional = utf8_encode($v_adicional);
									$valores     = json_decode($v_adicional,true);

									foreach ($valores as $key => $value) {
										unset($block);

										$preco = $value['valor'];
										$valor = $key."|".$preco;
										$valor = trim($valor);

										$checked = (in_array($key, $valor_adicional_selecionado) || in_array(utf8_decode($valor), $valor_adicional_selecionado)) ? "CHECKED" : "";

										if ($value["editar"] != "t") {
											$block = "readonly=readonly";
										}

										echo "
											<div class='span3 label label-default' style='min-height: 50px; margin-top: 10px; ".(($vi % 4 == 0) ? "margin-left: 0px !important;" : "")."' >
												<input type='checkbox' name='os[valor_adicional][]' value='$key|$preco' $checked> ".utf8_decode($key)."<br />
												<input type='text' class='span12 valor_adicional_valor'  {$block} name='os[valor_adicional_valor][$key]' value=' ".$value['valor']." '>
											</div>
										";

										$vi++;
									}
								}
							}
							?>
						</div>
					</div>

					<br />

				</div>
				<div class="span1"></div>
			</div>

			<div style='clear: both;'></div>

		</div>

		<br />
	<?php
	}
	?>

	<br />

	<div id="div_observacoes" class="tc_formulario">
		<div class="titulo_tabela"><?=traduz('Observações da Ordem de Serviço')?></div>

		<br />

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span10">
				<div class='control-group <?=(in_array('os[observacoes]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<div class="controls controls-row">
						<div class="span12">
							<textarea id="os_observacoes" name="os[observacoes]" class="span12" style="height: 50px;" ><?=str_replace('\\','',getValue('os[observacoes]')) ?></textarea>
						</div>
					</div>
				</div>
			</div>

			<div class="span1"></div>
		</div>
	</div>

	<br />

	<div id="div_anexos" class="tc_formulario">
		<div class="titulo_tabela">
			<?=traduz('Anexo(s)')?>
		</div>
		<br />

		<div class="tac" >
		<?php
		if (isset($valida_anexo)) {
			echo "<label class='label label-important' >".traduz('Anexo(s) obrigatórios')."</label><br />";
		}

		if ($fabrica_qtde_anexos > 0) {
			if (strlen($os) > 0) {
				list($dia,$mes,$ano) = explode("/", getValue("os[data_abertura]"));
			}

			echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

			for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
				unset($anexo_link);

				$anexo_imagem = "imagens/imagem_upload.png";
				$anexo_s3     = false;
				$anexo        = "";

				if (strlen(getValue("anexo[{$i}]")) > 0 && getValue("anexo_s3[{$i}]") != "t") {
					$anexos       = $s3->getObjectList(getValue("anexo[{$i}]"), true);

					$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

					if ($ext == "pdf") {
						$anexo_imagem = "imagens/pdf_icone.png";
					} else if (in_array($ext, array("doc", "docx"))) {
						$anexo_imagem = "imagens/docx_icone.png";
					} else {
						$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
					}

					$anexo_link = $s3->getLink(basename($anexos[0]), true);

					$anexo        = getValue("anexo[$i]");
				} else if(strlen($os) > 0) {
				    $anexos = $s3->getObjectList("{$os}_{$i}.", false, $ano, $mes);

				    if (count($anexos) > 0) {

						$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

						if ($ext == "pdf") {
							$anexo_imagem = "imagens/pdf_icone.png";
						} else if (in_array($ext, array("doc", "docx"))) {
							$anexo_imagem = "imagens/docx_icone.png";
						} else {
							$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
						}

						$anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

						$anexo        = basename($anexos[0]);
						$anexo_s3     = true;
				    }
				}
				?>
				<div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
					<?php if (isset($anexo_link)) { ?>
						<a href="<?=$anexo_link?>" target="_blank" >
					<?php } ?>
							<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
					<?php if (isset($anexo_link)) { ?>
						</a>
						<script>setupZoom();</script>
					<?php } ?>

					<?php
					if ($anexo_s3 === false) {
					?>
					    <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" ><?=traduz('Anexar')?></button>
					<?php
					}
					?>

					<img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

					<input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
					<input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
				</div>
            <?php
			}
        }
		?>
		</div>
		<br />
	</div>

	<br />

	<p class="tac">
		<input type="submit" class="btn btn-large" name="gravar" value="<?=traduz("Gravar")?>" />
	</p>
</form>

<?php
if ($fabrica_qtde_anexos > 0) {
	for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
    ?>
		<form name="form_anexo" method="post" action="cadastro_os.php" enctype="multipart/form-data" style="display: none;" >
			<input type="file" name="anexo_upload_<?=$i?>" value="" />

			<input type="hidden" name="ajax_anexo_upload" value="t" />
			<input type="hidden" name="anexo_posicao" value="<?=$i?>" />
			<input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
		</form>
	<?php
	}
}

include "rodape.php";
?>
