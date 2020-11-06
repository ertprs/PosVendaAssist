<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";
include_once "funcoes.php";

use Posvenda\TcMaps;

if ($_POST["ajax_lat_lon"]) {
	try {
		$logradouro = $_POST["logradouro"];
		$numero     = $_POST["numero"];
		$bairro     = $_POST["bairro"];
		$cidade     = $_POST["cidade"];
		$estado     = $_POST["estado"];
		$pais       = $_POST["pais"];

		$oTcMaps = new TcMaps($login_fabrica, $con);
		$retorno = $oTcMaps->geocode($logradouro, $numero, $bairro, $cidade, $estado, $pais);

		if (empty($retorno)) {
			throw new Exception("Endere�o n�o encontrado");
		}

		exit(json_encode($retorno));
	} catch(Exception $e) {
		exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
	}
}

if(isset($_POST["gravar_posto"])){
	$cep       = $_POST["cep"];
	$estado    = $_POST["estado"];
	$cidade    = $_POST["cidade"];
	$endereco  = $_POST["endereco"];
	$numero    = $_POST["numero"];
	$bairro    = $_POST["bairro"];
	$latitude  = trim($_POST["latitude"]);
	$longitude = trim($_POST["longitude"]);

	$cep = str_replace("-", "", $cep);

	pg_query($con, "BEGIN");

	$sql = "UPDATE tbl_posto_fabrica SET 
				contato_endereco = '$endereco',
				contato_numero   = '$numero',
				contato_bairro   = '$bairro',
				contato_cidade   = '$cidade',
				contato_estado   = '$estado',
				contato_cep      = '$cep',
				latitude         = $latitude,
				longitude        = $longitude 
			WHERE tbl_posto_fabrica.posto = {$posto} AND tbl_posto_fabrica.fabrica = {$login_fabrica}";

	pg_query($con,$sql);

            if (in_array($login_fabrica, array(158))) {
                $sql = "UPDATE tbl_tecnico SET latitude = '{$latitude}', longitude = '{$longitude}' WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
                pg_query($con,$sql);
            }

	if(strlen(pg_last_error()) > 0){
		pg_query($con,"ROLLBACK");
		$resultado = array("success" => false,
			"mensagem" => utf8_encode("ERRO: Não foi possível gravar as informações do posto")
		);
	}else{
		pg_query($con, "COMMIT");
		$resultado = array(
			"success" => true
		);
	}

	echo json_encode($resultado); exit;
}

if (isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])) {
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


$posto     = $_GET['posto'];
$nome      = $_GET['nome'];
$cep       = $_GET['cep'];
$endereco  = $_GET['endereco'];
$numero    = $_GET['numero'];
$bairro    = $_GET['bairro'];
$cidade    = $_GET['cidade'];
$estado    = $_GET['estado'];
$latitude  = $_GET['latitude'];
$longitude = $_GET['longitude'];

?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>

<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>		
<script src="plugins/leaflet/map.js" ></script>


<?php

$plugins = array(
   "maskedinput"
);

include __DIR__."/plugin_loader.php";

?>
<style type="text/css">

#Map {
	width: 100%;
	height: 280px; 
}

</style>
<script type="text/javascript">

var Map, Markers;

$(function(){
	Map = new Map("Map");
	Markers = new Markers(Map);
	Map.load();
	
	$("#cep_shadow").mask("99999-999");
	$("#cep_shadow").blur(function() {
		busca_cep($(this).val(), "consumidor");
	});

	$("#btn_localizar").on("click", function(){
		$.ajax({
			url: window.location,
			type: "POST",
			data: {
				ajax_lat_lon: true,
				logradouro: $("#endereco_shadow").val(),
				numero: $("#numero_shadow").val(),
				bairro: $("#bairro_shadow").val(),
				cidade: $("#cidade_shadow").val(),
				estado: $("#estado_shadow").val(),
				pais: "Brasil"
			}
		}).done(function(data) {
			data = JSON.parse(data);

			if (data.erro) {
				$("#mensagem").addClass("alert alert-error");
				$("#mensagem").html("Localiza��o n�o encontrada, verifica se est� correto as informa��es de endere�o do posto.");
			} else {
				$("#latitude_shadow").val(data.latitude);
				$("#longitude_shadow").val(data.longitude);

				Markers.add(data.latitude, data.longitude);
				Markers.render();
				Markers.focus(false);
			}
		});
	});

	$("#btn_gravar").on("click",function(){
		var posto     = $("#posto_shadow").val();
		var cep       = $("#cep_shadow").val();
		var estado    = $("#estado_shadow").val();
		var cidade    = $("#cidade_shadow").val();
		var endereco  = $("#endereco_shadow").val();
		var numero    = $("#numero_shadow").val();
		var bairro    = $("#bairro_shadow").val();
		var latitude  = $("#latitude_shadow").val();
		var longitude = $("#longitude_shadow").val();

		$.ajax({
			url: "alterar_informacao_posto.php",
			type: "POST",
			data : {
				gravar_posto : "gravar_posto",
				posto : posto,
				cep : cep,
				estado : estado,
				cidade : cidade,
				endereco : endereco,
				numero : numero,
				bairro : bairro,
				latitude : latitude,
				longitude : longitude
			}
		}).done(function(data){
			console.log(data);
			data = JSON.parse(data);

			if(data.success){
				window.parent.posto_atualizado(posto, latitude, longitude);
                window.parent.Shadowbox.close();
			}else{
				$("mensagem_erro").addClass("alert alert-error");
				$("mensagem_erro").html(data.mensagem);
			}
		});
	});
});

/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, consumidor_revenda, cidade) {
	$("#cidade_shadow").find("option").first().nextAll().remove();

	if (estado.length > 0) {
		$.ajax({
			async: false,
			url: "cadastro_os.php",
			type: "POST",
			data: { ajax_busca_cidade: true, estado: estado },
			beforeSend: function() {
				if ($("#cidade_shadow").next("img").length == 0) {
					$("#cidade_shadow").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
				}
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					$.each(data.cidades, function(key, value) {
						var option = $("<option></option>", { value: value, text: value});

						$("#cidade_shadow").append(option);
					});
				}

				$("#cidade_shadow").show().next().remove();
			}
		});
	}

	if(typeof cidade != "undefined" && cidade.length > 0){
		$('#cidade_shadow option[value='+cidade+']').attr('selected','selected');
	}
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
			data: { cep: cep, method: method },
			beforeSend: function() {
                $("#endereco_shadow").next("img").remove();
                $("#bairro_shadow").next("img").remove();
                $("#cidade_shadow").next("img").remove();
				$("#estado_shadow").next("img").remove();

				$("#endereco_shadow").hide().after(img.clone());
				$("#bairro_shadow").hide().after(img.clone());
				$("#cidade_shadow").hide().after(img.clone());
				$("#estado_shadow").hide().after(img.clone());
			},
			error: function(xhr, status, error) {
                busca_cep(cep, consumidor_revenda, "database");
            },
			success: function(data) {
				results = data.split(";");

				if (results[0] != "ok") {
					alert(results[0]);
					$("#cidade_shadow").show().next().remove();
				} else {
					$("#estado_shadow").val(results[4]);

					busca_cidade(results[4], consumidor_revenda);

					$("#cidade_shadow").val(retiraAcentos(results[3]).toUpperCase());

					if (results[2].length > 0) {
						$("#bairro_shadow").val(results[2]);
					}

					if (results[1].length > 0) {
						$("#endereco_shadow").val(results[1]);
					}
				}

				$("#estado_shadow").show().next().remove();
				$("#bairro_shadow").show().next().remove();
				$("#endereco_shadow").show().next().remove();

				if ($("#bairro_shadow").val().length == 0) {
					$("#bairro_shadow").focus();
				} else if ($("#endereco_shadow").val().length == 0) {
					$("#endereco_shadow").focus();
				} else if ($("#numero_shadow").val().length == 0) {
					$("#numero_shadow").focus();
				}
			}
		});
	}
}
</script>

<? if (count($msg_erro['msg']) > 0) { ?>
    <br/>
    <div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
    <br/>
<? } ?>
<form id="fm_alterar_informacao_posto" action="<?=$PHP_SELF?>" method="POST" class="form-search form-inline" >
    <div id="mensagem_erro"></div>
    <div class="div_alteracao" style="margin: 5px; padding-right: 20px;">
        <div id="mensagem_conferencia">
            <div class='container tc_container'>
                <div class="row-fluid">
                    <div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" for="nf_shadow" >Posto</label>

                            <div class="controls controls-row" >
                                <input type="text" id="nome_shadow" readOnly class="span12" value="<?=$nome?>" readOlny/>
                                <input type="hidden" id="posto_shadow" value="<?=$posto?>">
                            </div>
                        </div>
                    </div>
                    <div class="span2" >
                        <div class="control-group" >
                            <label class="control-label" for="cep_shadow" >CEP</label>

                            <div class="controls controls-row" >
                                <input type="text" id="cep_shadow" class="span12" value="<?=$cep?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="span2" >
                        <div class="control-group" >
                            <label class="control-label" for="estado_shadow" >Estado</label>

                            <div class="controls controls-row" >
                                <select id="estado_shadow" name="estado_shadow" class="span12">
									<option value="" >Selecione</option>
									<?php
									foreach ($array_estados() as $sigla => $nome_estado) {
										$selected = ($sigla == $estado) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
									}
									?>
								</select>
                            </div>
                        </div>
                    </div>
                    <div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" for="cidade_shadow" >Cidade</label>

                            <div class="controls controls-row" >
                                <select id="cidade_shadow" name="cidade_shadow" class="span12" >
									<option value="" >Selecione</option>
									<?php

									if (strlen($estado) > 0) {
										$sql = "SELECT DISTINCT * FROM (
													SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$estado."')
													UNION (
														SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$estado."')
													)
												) AS cidade
												ORDER BY cidade ASC";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) > 0) {
											while ($result = pg_fetch_object($res)) {
												$selected  = (trim($result->cidade) == trim($cidade)) ? "SELECTED" : "";

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
                <div class="row-fluid">
                    <div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" for="endereco_shadow" >Endereço</label>

                            <div class="controls controls-row" >
                                <input type="text" id="endereco_shadow" class="span12" value="<?=$endereco?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="span2" >
                        <div class="control-group" >
                            <label class="control-label" for="numero_shadow" >Número</label>

                            <div class="controls controls-row" >
                                <input type="text" id="numero_shadow" class="span6" value="<?=$numero?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" for="bairro_shadow" >Bairro</label>

                            <div class="controls controls-row" >
                                <input type="text" id="bairro_shadow" class="span12" value="<?=$bairro?>"/>                            </div>
                        </div>
                    </div>
                </div>
                <div class="row-fluid">
                    <div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" for="latitude_shadow" >Latitude</label>

                            <div class="controls controls-row" >
                                <input type="text" id="latitude_shadow" class="span6" value="<?=$latitude?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="span2" >
                        <div class="control-group" >
                            <label class="control-label" for="longitude_shadow" >Longitude</label>

                            <div class="controls controls-row" >
                                <input type="text" id="longitude_shadow" class="span12" value="<?=$longitude?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="span6" style="margin-top: 17px;">
                        <div class="control-group" >
                            <div class="controls controls-row" >
                                <input type="button" id="btn_localizar" class="btn btn-default" value="Pesquisar Localização"/>
                                <input type="button" id="btn_gravar" class="btn btn-default" value="Gravar"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div id="Map"></div>
