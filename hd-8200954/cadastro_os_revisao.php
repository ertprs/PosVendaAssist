<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/funcoes.php';

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
	require_once __DIR__.'/classes/cep.php';

	$cep = $_POST['cep'];

	try {
		$retorno = CEP::consulta($cep);
		$retorno = array_map(utf8_encode, $retorno);
	} catch(Exception $e) {
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

#Arquivo com o array para montar o formulário
#include __DIR__."/os_cadastro_unico/fabricas/{$login_fabrica}/form.php";

#Arquivo que processa o post
include "os_cadastro_unico/fabricas/os_revisao.php";

if ($areaAdmin === true) {
	$layout_menu = "callcenter";
} else {
	$layout_menu = "os";	
}

$title       = "CADASTRO DE ORDEM DE SERVIÇO DE REVISÃO	";

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
   "price_format"
);

include __DIR__."/admin/plugin_loader.php";

?>

<style>

#div_trocar_posto, #div_trocar_produto {
	display: none;
	height: 40px;
}

#div_informacoes_deslocamento > div.tc_formulario {
	min-height: 380px;
}

#google_maps {
	width: 59%;
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

#mapbox{
	width: 100%;
	min-height: 357px;
	float: left;
	z-index: 0;
}

</style>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&async=2&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>
<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>		
<script src="plugins/leaflet/map.js" ></script>
<script src="plugins/mapbox/geocoder.js"></script>
<script src="plugins/mapbox/polyline.js"></script>
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
function load_mapbox(){
	//mapbox
	Map      = new Map("mapbox");
	Router   = new Router(Map);
	Geocoder = new Geocoder();
	Markers = new Markers(Map);
	
	// Polyline = new Polyline();

	//Map.load();
}

function load_google_maps() {
	var latlon  = new google.maps.LatLng(-15.78014820, -47.92916980);
	var options = { zoom: 2, center: latlon, mapTypeId: google.maps.MapTypeId.HYBRID, zoomControlOptions: { style: google.maps.ZoomControlStyle.SMALL } };
	map         = new google.maps.Map(document.getElementById("google_maps"), options);
}

$(function() {
	<?php
	if (!(strlen($os) > 0 && $areaAdmin === false)) {
	?>
		//load_google_maps();
		load_mapbox();
		$("#div_informacoes_deslocamento").hide();		
	<?php
	}
	?>

	try {		
		calcula_km();
	} catch(e) {
		console.log(e.message);
	}

	/**
	 * Carrega o datepicker já com as mascara para os campos
	 */
	$("#data_abertura").datepicker({ maxDate: 0, minDate: "-6d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");

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
		var parametros_lupa_produto = ["posto", "posicao", "ativo"];

		$.lupa($(this), parametros_lupa_produto);
	});

	/**
	 * Evento de keypress do campo consumidor_cpf
	 * Irá verificar o tamanho do campo, se o tamanho já for 14(CPF) irá alterar a máscara para CNPJ e alterar o Label
	 */
	<?php
		if(strlen(getValue('consumidor[cpf]')) > 0){
			if(strlen(getValue('consumidor[cpf]')) > 11){
	?>
				$("#consumidor_cpf").mask("99.999.999/9999-99");
	<?php
			}else{
	?>
				$("#consumidor_cpf").mask("999.999.999-99");
	<?php
			}
	?>
	<?php
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
				if ($(this).parents("#div_informacoes_produto").length > 0 && $(this).attr("id").match(/^qtde_/)) {
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

		$("#qtde_visitas").removeAttr("readonly");
	<?php
	}

	if (strlen($os) > 0) {
	?>
		$("#div_trocar_produto").hide();
	<?php
	}
	?>
});

/**
 * Evento de click do botão trocar_produto
 * Irá remover o readonly dos campos referência e descrição e dar show nas lupas
 */
 function alterarProduto(posicao){

 	$("input[name='produto["+posicao+"][id]']").val("");
	$("input[name='produto["+posicao+"][referencia]']").val("");
	$("input[name='produto["+posicao+"][descricao]']").val("");
	$("input[name='produto["+posicao+"][qtde]']").val(0);

	$("input[name='produto["+posicao+"][referencia]']")[0].readOnly = false;
	$("input[name='produto["+posicao+"][descricao]']")[0].readOnly = false;

	$("#div_produto_"+posicao).find("span[rel=lupa_produto]").show();
	$("#div_produto_"+posicao).find("div.troca_produto").hide();
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

	$(div).find("input[name$='[id]']").val(retorno.produto);
	$(div).find("input[name$='[referencia]']").val(retorno.referencia);
	$(div).find("input[name$='[descricao]']").val(retorno.descricao);
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
function busca_cep(cep, consumidor_revenda) {
	if (cep.length > 0) {
		var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		$.ajax({
			async: false,
			url: "cadastro_os.php",
			type: "POST",
			data: { ajax_busca_cep: true, cep: cep },
			beforeSend: function() {
				$("#"+consumidor_revenda+"_estado").hide().after(img.clone());
				$("#"+consumidor_revenda+"_cidade").hide().after(img.clone());
				$("#"+consumidor_revenda+"_bairro").hide().after(img.clone());
				$("#"+consumidor_revenda+"_endereco").hide().after(img.clone());
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
					$("#"+consumidor_revenda+"_cidade").show().next().remove();
				} else {
					$("#"+consumidor_revenda+"_estado").val(data.uf);

					busca_cidade(data.uf, consumidor_revenda);

					$("#"+consumidor_revenda+"_cidade").val(retiraAcentos(data.cidade).toUpperCase());

					if (data.bairro.length > 0) {
						$("#"+consumidor_revenda+"_bairro").val(data.bairro);
					}
					
					if (data.end.length > 0) {
						$("#"+consumidor_revenda+"_endereco").val(data.end);
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

/**
 * Função que trata os dados do consumidor para pesquisar a latitude e longitude
 */
// function calcula_km (km_rota) {
// 	if (typeof km_rota == "undefined") {
// 		var km_rota = false;
// 	}

// 	if ($("#posto_id").val().length == 0) {
// 		throw new Error("Selecione um Posto Autorizado");
// 	}

// 	if ($("#posto_latitude").val().length == 0 && $("#posto_longitude").val().length == 0) {
// 		throw new Error("Posto Autorizado sem latitude e longitude");
// 	}

// 	if ($("#consumidor_cep").val() == "" && $("#consumidor_endereco").val() == "" && $("#consumidor_cidade").val() == "" && $("#consumidor_estado").val() == "") {
// 		throw new Error("Digite as informações do consumidor para calcular o KM");
// 	}

// 	var c = [
// 		$("#consumidor_endereco").val(),
// 		$("#consumidor_numero").val(),
// 		$("#consumidor_bairro").val(),
// 		$("#consumidor_cidade > option:selected").text(),
// 		$("#consumidor_estado").val()
// 	];

// 	var consumidor_endereco = c.join(", ");

// 	delete(c[2]);
// 	var consumidor_endereco_sem_bairro = c.join(" ,");

// 	var consumidor_cep = "cep " + $("#consumidor_cep").val();

// 	delete c[0];
// 	delete c[1];
// 	var consumidor_cidade = c.join(", ");

// 	lista_enderecos = [consumidor_endereco, consumidor_endereco_sem_bairro, consumidor_cep, consumidor_cidade];



// 	geocodeLatLon(0, myCallback, km_rota);
// }

function calcula_km (km_rota) {    

    if ($("#consumidor_cep").val() == "" && $("#consumidor_endereco").val() == "" && $("#consumidor_cidade").val() == "" && $("#consumidor_estado").val() == "") {
      throw new Error("Digite as informações do consumidor para calcular o KM");
    }

    if ($("#posto_latitude").val() == "" && $("#posto_longitude").val() == "") {
      throw new Error("Não foi possível determinar a localização do posto");
    }

      var Pais = "Brasil";

        Geocoder.setEndereco({
            endereco: $("#consumidor_endereco").val(),
            numero: $("#consumidor_numero").val(),
            bairro: $("#consumidor_bairro").val(),
            cidade: $("#consumidor_cidade > option:selected").text(),
            estado: $("#consumidor_estado").val(),
            pais: Pais
        });

        request = Geocoder.getLatLon();

        request.then(
            function(resposta) {
              $("#div_informacoes_deslocamento").show();
              var pLat = $("#posto_latitude").val();
              var pLng = $("#posto_longitude").val();
                var pLatLng = $("#posto_latitude").val()+","+$("#posto_longitude").val();
                
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

                    Map.load();

              /* Marcar pontos no mapa */
              Markers.clear();    
              Markers.remove();    
              Markers.add(cLat, cLng, "blue", "Cliente");
              Markers.add(pLat, pLng, "red", "Posto");
              Markers.render();
              Markers.focus();    

              Router.remove();
              Router.clear();
              Router.add(Polyline.decode(geometry));
              Router.render();

                    $('#qtde_km').val(kmtotal);
                    $('#loading-map').hide();
                }).fail(function(){
                    $('#loading-map').hide();
                    alert('Erro ao tentar calcular a rota!');
                });
            },
            function(erro) {
                $('#loading-map').hide();
                alert(erro);
            }
        );    
}

/**
 * Função que pesquisa a latitude e longitude do consumidor
 */
function geocodeLatLon (i, callback, km_rota) {
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

			/* Reescreve a Sigla do estado para o nome completo */
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
function rota (consumidorLatLng, km_rota) {
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

	<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>

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
			<div class="titulo_tabela">Informações do Posto Autorizado</div>

			<br />

			<input type="hidden" id="posto_id" name="posto[id]" value="<?=getValue('posto[id]')?>" />

			<div class="row-fluid">
				<div class="span2"></div>

				<div class="span4">
					<div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="posto_codigo">Código</label>
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
						<label class="control-label" for="posto_nome">Nome</label>
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
					<button type="button" id="trocar_posto" class="btn btn-danger" >Alterar Posto Autorizado</button>
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
		<div class="titulo_tabela">Informações da OS</div>

		<br />

		<div class="row-fluid">
			<div class="span4"></div>

			<div class="span2">
				<div class='control-group <?=(in_array('os[data_abertura]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_abertura">Data Abertura</label>
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

			<div class="span2">
				<div class='control-group <?=(in_array('os[qtde_visitas]', $msg_erro['campos'])) ? "error" : "" ?>'>
					<label class="control-label" for="qtde_visitas">Qtde Visitas</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["os|qtde_visitas"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
								$qtde_visitas = getValue('os[qtde_visitas]');
							?>
							<input id="qtde_visitas" name="os[qtde_visitas]" class="span3 numeric" type="text" value="<?php echo (empty($qtde_visitas)) ? 1 : $qtde_visitas; ?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span4"></div>
		</div>

	</div>
	<br />

	<div id="div_informacoes_consumidor" class="tc_formulario">
		<div class="titulo_tabela">Informações do Consumidor</div>

		<br />

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span3">
				<div class='control-group <?=(in_array('consumidor[nome]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_nome">Nome</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["consumidor|nome"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input id="consumidor_nome" name="consumidor[nome]" class="span12" type="text" value="<?=getValue('consumidor[nome]')?>" />
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
					CPF <input type="radio" id="cpf_cnpj" name="cnpjCpf" <? echo (getValue('cnpjCpf')=='cpf') ? 'checked="checked"': ''; ?> value="cpf" > 
					/CNPJ <input type="radio" id="cnpj_cpf" name="cnpjCpf" <? echo (getValue('cnpjCpf')=='cnpj') ? 'checked="checked"': ''; ?> value="cnpj" >
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
							<input id="consumidor_cpf" name="consumidor[cpf]" class="span12" type="text" value="<?=getValue('consumidor[cpf]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array('consumidor[cep]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_cep">CEP</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["consumidor|cep"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>
							<input id="consumidor_cep" name="consumidor[cep]" class="span12" type="text" value="<?=getValue('consumidor[cep]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="control-group <?=(in_array('consumidor[estado]', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="consumidor_estado">Estado</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|estado"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>

							<select id="consumidor_estado" name="consumidor[estado]" class="span12">
								<option value="" >Selecione</option>
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
					<label class="control-label" for="consumidor_cidade">Cidade</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
								if ($regras["consumidor|cidade"]["obrigatorio"] == true) {
									echo "<h5 class='asteristico'>*</h5>";
								}
							?>

							<select id="consumidor_cidade" name="consumidor[cidade]" class="span12" >
								<option value="" >Selecione</option>

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
					<label class="control-label" for="consumidor_bairro">Bairro</label>
					<div class="controls controls-row">
						<div class="span12">
						<?php
						if ($regras["consumidor|bairro"]["obrigatorio"] == true) {
							echo "<h5 class='asteristico'>*</h5>";
						}
						?>
						<input id="consumidor_bairro" name="consumidor[bairro]" class="span12" type="text" value="<?=getValue('consumidor[bairro]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group <?=(in_array('consumidor[endereco]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_endereco">Endereço</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|endereco"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_endereco" name="consumidor[endereco]" class="span12" type="text" value="<?=getValue('consumidor[endereco]')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span1">
				<div class='control-group <?=(in_array('consumidor[numero]', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="consumidor_numero">Número</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|numero"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_numero" name="consumidor[numero]" class="span12 numeric" type="text" value="<?=getValue('consumidor[numero]')?>" />
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
					<label class="control-label" for="consumidor_complemento">Complemento</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|complemento"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_complemento" name="consumidor[complemento]" class="span12" type="text" value="<?=getValue('consumidor[complemento]')?>" maxlength="20" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class="control-group <?=(in_array('consumidor[telefone]', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="consumidor_telefone">Telefone</label>
					<div class="controls controls-row">
						<div class="span12">
							<?php
							if ($regras["consumidor|telefone"]["obrigatorio"] == true) {
								echo "<h5 class='asteristico'>*</h5>";
							}
							?>
							<input id="consumidor_telefone" name="consumidor[telefone]" class="span12" type="text" value="<?=getValue('consumidor[telefone]')?>" />
						</div>
					</div>
				</div>
			</div>

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
							<input id="consumidor_email" name="consumidor[email]" class="span12" type="text" value="<?=getValue('consumidor[email]')?>" />
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

	<div id="div_informacoes_deslocamento" >
		<div class="tc_formulario">
			<div class="titulo_tabela">Informações do Deslocamento</div>

			<?php
			if (!(strlen($os) > 0 && $areaAdmin === false)) {
			?>
				<div id="mapbox"></div>
				<!-- <div id="google_maps" ></div> -->
				<!-- <div id="google_maps_direction" ></div> -->
			<?php
			}
			?>

			<br />

			<div class="row-fluid">
				<div class="span1"></div>

				<div class="span10">
					<div class='control-group <?=(in_array('os[qtde_km]', $msg_erro['campos'])) ? "error" : "" ?> tac' >
						<label class="control-label" for="qtde_km">Distância <span style="color: #FF0000;">(a distância já é calculada a ida e a volta)</span></label>
						<div class="controls controls-row">
							<div class="span12 tac">
								<h5 class="asteristico" style="float: none; display: inline;">*</h5>
								<input id="qtde_km" name="os[qtde_km]" class="span2" type="text" value="<?=getValue('os[qtde_km]')?>" />
								<input id="qtde_km_hidden" name="os[qtde_km_hidden]" type="hidden" value="<?=getValue('os[qtde_km_hidden]')?>" />
								<?php
								if (!(strlen($os) > 0 && $areaAdmin === false)) {
								?>
									<button type="button" id="calcular_km" class="btn btn-primary btn-small" >Calcular KM</button>
								<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="span1"></div>
			</div>
		</div>
		<br />
	</div>

	<div id="div_informacoes_produto" class="tc_formulario">
		<div class="titulo_tabela">Informações do Produto</div>

		<?php
		$produtos = getValue("produto");
		?>

		<div id="modelo_produto" style="display: none;">

			<div class="row-fluid" id="div_produto___model__">

				<input type="hidden" name="produto[__model__][id]" value="" />
				<input type="hidden" name="produto[__model__][os_produto]" value="" />

				<div class="span1"></div>

				<div class="span2">
					<div class='control-group' >
						<label class="control-label" >Referência</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input name="produto[__model__][referencia]" class="span12" type="text" value="" />
								<span class="add-on" rel="lupa_produto" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posto="<?=$login_posto?>" posicao="__model__" ativo="t" />
							</div>
						</div>
					</div>
				</div>

				<div class="span5">
					<div class='control-group' >
						<label class="control-label" >Descrição</label>
						<div class="controls controls-row">
							<div class="span11 input-append">
								<input name="produto[__model__][descricao]" class="span12" type="text" value="" />
								<span class="add-on" rel="lupa_produto" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posto="<?=$login_posto?>" posicao="__model__" ativo="t" />
							</div>
						</div>
					</div>
				</div>

				<div class="span1">
					<div class='control-group' >
						<label class="control-label" >Qtde</label>
						<div class="controls controls-row">
							<div class="span12">
								<input name="produto[__model__][qtde]" id="qtde___model__" class="span12 numeric" type="text" value="0" />
							</div>
						</div>
					</div>
				</div>

				<div class="span2 troca_produto" style="display: none;" >
					<div style='padding-top: 20px;'>
						<button type="button" class="btn btn-danger" onclick="alterarProduto(__model__)">Alterar Produto</button>
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
					$id_produto = $produtos[$i]["id"];
					$os_produto = $produtos[$i]["os_produto"];
					$referencia = $produtos[$i]["referencia"];
					$descricao  = $produtos[$i]["descricao"];
					$qtde       = $produtos[$i]["qtde"];
				} else {
					unset($id_produto, $os_produto, $referencia, $descricao, $qtde);
				}

				$produto_esconde_troca = "style='display: inline-block;'";
				$produto_esconde_lupa  = "style='display: inline-block;'";

				if (!empty($os_produto) || empty($id_produto)) {
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

				<div class="row-fluid" id="div_produto_<?=$i?>" <?=$bgcolor?> >
					<input type="hidden" name="produto[<?=$i?>][id]" value="<?=$id_produto?>" />
					<input type="hidden" name="produto[<?=$i?>][os_produto]" value="<?=$os_produto?>" />

					<div class="span1"></div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" >Referência</label>
							<div class="controls controls-row">
								<div class="span10 input-append">
									<input name="produto[<?=$i?>][referencia]" class="span12" type="text" value="<?=$referencia?>" <?=$produto_readonly?> />
									<span class="add-on" rel="lupa_produto" <?=$produto_esconde_lupa?> >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posto="<?=$login_posto?>" posicao="<?=$i?>" ativo="t" />
								</div>
							</div>
						</div>
					</div>

					<div class="span5">
						<div class='control-group' >
							<label class="control-label" >Descrição</label>
							<div class="controls controls-row">
								<div class="span11 input-append">
									<input name="produto[<?=$i?>][descricao]" class="span12" type="text" value="<?=$descricao?>" <?=$produto_readonly?> />
									<span class="add-on" rel="lupa_produto" <?=$produto_esconde_lupa?> >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posto="<?=$login_posto?>" posicao="<?=$i?>" ativo="t" />
								</div>
							</div>
						</div>
					</div>

					<div class="span1">
						<div class='control-group' >
							<label class="control-label" >Qtde</label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="produto[<?=$i?>][qtde]" id="qtde_<?=$i?>" class="span12 numeric" type="text" value="<?=(empty($qtde)) ? 0 : $qtde?>" />
								</div>
							</div>
						</div>
					</div>

					<div class="span2 troca_produto" <?=$produto_esconde_troca?> >
						<div style='padding-top: 20px;'>
							<button type="button" class="btn btn-danger" onclick="alterarProduto(<?=$i?>)" >Alterar Produto</button>
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
			<button type="button" name="adicionar_produto" class="btn btn-primary" >Adicionar novo produto</button>
		</p>
		<br />
	</div>

	<br />

	<p class="tac">
		<input type="submit" class="btn btn-large" name="gravar" value="Gravar" />
	</p>
</form>

<?php
include "rodape.php";
?>
