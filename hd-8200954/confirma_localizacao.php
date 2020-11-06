<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if ($_POST["busca_cidades"]) {
	$estado = $_POST["estado"];

	if (strlen($estado) == 2) {
		$sql  = "SELECT cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$cidades = array();

			for ($i = 0; $i < $rows; $i++) { 
				$cidades[] = utf8_encode(pg_fetch_result($res, $i, "cidade"));
			}

			$retorno = array("status" => true, "cidades" => $cidades);
		} else {
			$retorno = array("status" => false);
		}
	} else {
		$retorno = array("status" => false);
	}

	exit(json_encode($retorno));
}

if ($_POST["acao"] == "confirmacao_dados") {
	include_once S3CLASS;

	$file = $_FILES["comprovante_endereco"];

	$s3 = new AmazonTC("pa_ce", $login_posto);

	$s3->upload($login_posto, $file);

	$posto_lat_novo      = $_POST["posto_lat_novo"];
	$posto_lng_novo      = $_POST["posto_lng_novo"];
	$posto_latlng_manual = $_POST["posto_latlng_manual"];
	$cep                 = str_replace("-", "", str_replace(".", "", $_POST["cep"]));
	$estado              = $_POST["estado"];
	$cidade              = $_POST["cidade"];
	$bairro              = $_POST["bairro"];
	$endereco            = $_POST["endereco"];
	$numero              = $_POST["numero"];
	$complemento         = $_POST["complemento"];
	$ie                  = $_POST["ie"];
	$email               = $_POST["email"];
	$contato             = $_POST["contato"];
	$telefones_array     = $_POST["telefone"];
	$fax                 = $_POST["fax"];

	foreach ($telefones_array as $telefone) {
		$telefones[] = "\"{$telefone}\"";
	}

	$telefones = implode(", ", $telefones);

	$sql = "INSERT INTO tbl_posto_mapa (
				posto, 
				latitude,
				longitude,
				lat_lon_manual,
				cep,
				estado,
				cidade,
				bairro,
				endereco,
				numero,
				complemento,
				ie, 
				email,
				contato,
				telefones,
				fax
			) VALUES (
				{$login_posto},
				{$posto_lat_novo},
				{$posto_lng_novo},
				{$posto_latlng_manual},
				'{$cep}',
				'{$estado}',
				'{$cidade}',
				'{$bairro}',
				'{$endereco}',
				'{$numero}',
				'{$complemento}',
				'{$ie}',
				'{$email}',
				'{$contato}',
				'{{$telefones}}',
				'{$fax}'
			)";
	$res = pg_query($con, $sql);

	header("Location: login.php");
	exit;
}

$estados = array(
	"AC" => "Acre", 
	"AL" => "Alagoas",	
	"AM" => "Amazonas", 
	"AP" => "Amap·", 
	"BA" => "Bahia", 
	"CE" => "Cear·", 
	"DF" => "Distrito Federal", 
	"ES" => "EspÌrito Santo", 
	"GO" => "Goi·s", 
	"MA" => "Maranh„o", 
	"MG" => "Minas Gerais", 
	"MS" => "Mato Grosso do Sul", 
	"MT" => "Mato Grosso", 
	"PA" => "Par·", 
	"PB" => "ParaÌba", 
	"PE" => "Pernambuco", 
	"PI" => "PiauÌ", 
	"PR" => "Paran·", 
	"RJ" => "Rio de Janeiro", 
	"RN" => "Rio Grande do Norte", 
	"RO" => "RondÙnia", 
	"RR" => "Roraima", 
	"RS" => "Rio Grande do Sul", 
	"SC" => "Santa Catarina", 
	"SE" => "Sergipe", 
	"SP" => "S„o Paulo", 
	"TO" => "Tocantins"
);

$sql = "SELECT latitude AS lng, longitude AS lat FROM tbl_posto WHERE posto = {$login_posto}";
$res = pg_query($con, $sql);

$lat_atual = pg_fetch_result($res, 0, "lat");
$lng_atual = pg_fetch_result($res, 0, "lng");
?>

<link type="text/css" rel="stylesheet" href="admin/css/css.css" />
<link type="text/css" rel="stylesheet" href="plugins/jquery/tablesorter/themes/telecontrol/style.css" />
<link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet">

<style>
html {
	font-size: 12px;
}

#header {
	width: 1024px;
	height: 80px;
	position: relative;
	margin: auto;
	text-align: center;
}

#header #logo {
	display: block;
	float: left;
	margin-top: 5px;
}

#header h1 {
	display: block;
	position: relative;
	width: 300px;
	margin: 0 auto;
	bottom: -30px;
	color: #363A60;
}

#header #mapa {
	width: 90px;
	float: right;
	margin-top: -10px;
}

#body {
	width: 1024px;
	position: relative;
	margin: auto;
	text-align: center
}

#GoogleMaps {
	height: 500px;
	width: 500px;
	border: 1px black solid;
	margin-top: 0px;
	padding: 1px;
	display: inline-block;
	vertical-align: top;
}

.form {
	width: 500px;
	margin-top: 0px;
	padding: 1px;
	display: inline-block;
	text-align: left;
	vertical-align: top;
}

.form_input {
	margin-top: 5px;
	font-weight: bold;
	font-size: 14px;
}

button {
	cursor: pointer;
}
</style>

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/jquery.mask.js"></script>
<script src="plugins/jquery.alphanumeric.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>
<script src="plugins/markermanager.js"></script>

<script>
var directionsDisplay;
var map;
var marks = [];
var marker;
var nova_localizacao = false;

function retiraAcentos (palavra) {
	var com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
	var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
	var newPalavra = "";

	for (i = 0; i < palavra.length; i++) {
		if (com_acento.search(palavra.substr(i, 1)) >= 0) {
			newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
		} else {
			newPalavra += palavra.substr(i, 1);
		}
	}

	return newPalavra.toUpperCase();
}

function loadGoogleMaps () {
	directionsDisplay = new google.maps.DirectionsRenderer();
	var latlng        = new google.maps.LatLng(-15.78014820, -47.92916980);
	var myOptions     = { zoom: 2, center: latlng, mapTypeId: google.maps.MapTypeId.HYBRID };
	map               = new google.maps.Map(document.getElementById("GoogleMaps"), myOptions);
	geocoder          = new google.maps.Geocoder();

	geraMarkers();
}

function geraMarkers () {
	if (nova_localizacao == false) {
		var lat  = $("#posto_lat_atual").val();
		var lng  = $("#posto_lng_atual").val();
	} else {
		var lat  = $("#posto_lat_novo").val();
		var lng  = $("#posto_lng_novo").val();
	}

	if (lat.length == 0 || lng.length == 0) {
		return;
	}

	var local = new google.maps.LatLng(lat, lng);

	marks.push(new google.maps.Marker({
		position: local,
		clickable: true,
		draggable: true,
		flat: false
	}));

    marker = new MarkerManager(map, { trackMarkers: false, maxZoom: 15 });

    google.maps.event.addListener(marks[0], "dragend", function (event) {
		var latlng = new google.maps.LatLng(event.latLng.lat(), event.latLng.lng());

		geocoder.geocode( { "latLng": latlng}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				var result = results[0].address_components;

				var estado;
				var cidade;
				var bairro;
				var endereco;

				$.each(result, function (key, value) {
					if ($.inArray("administrative_area_level_1", value.types) != -1) {
						estado = value.short_name;
					} else if ($.inArray("administrative_area_level_2", value.types) != -1 || $.inArray("locality", value.types) != -1) {
						cidade = value.long_name;
					} else if ($.inArray("neighborhood", value.types) != -1) {
						bairro = value.long_name;
					} else if ($.inArray("route", value.types) != -1) {
						endereco = value.long_name;
					}
				});

				if ($("select[name=estado]").val() == estado) {
					$("select[name=cidade] > options").each(function () {
						if (retiraAcentos($(this).val()).toUpperCase() == retiraAcentos(cidade).toUpperCase()) {
							$("select[name=cidade]").val($(this).val());
							return false;
						}
					});
				} else {
					$("select[name=estado]").val(estado);

					buscaCidades(estado, cidade);	
				}

				$("input[name=bairro]").val(bairro);
				$("input[name=endereco]").val(endereco);

				$("#posto_latlng_manual").val(true);
			}
		});
	});

    google.maps.event.addListener(marker, "loaded", function () {
     	marker.addMarkers(marks, 0);
    	marker.refresh();
	});

	map.setCenter(new google.maps.LatLng(lat, lng));
	map.setZoom(16);
}

google.maps.event.addDomListener(window, "load", loadGoogleMaps);

function buscaCidades (estado, cidadeParam) {
	if (estado.length > 0) {
		$.ajax({
			url: "confirma_localizacao.php",
			type: "POST",
			data: { busca_cidades: true, estado: estado },
			beforeSend: function () {
				$("select[name=cidade]").hide();
				$("#carregando_cidades").show();
				$("select[name=cidade] > option[rel!=padrao]").remove();	
			},
			complete: function (data) {
				data = $.parseJSON(data.responseText);

				if (data.status == true) {
					$.each(data.cidades, function (key, cidade) {
						var cidade_option = $("<option></option>", {
							value: cidade,
							text: cidade
						});

						if (cidadeParam != undefined && retiraAcentos(cidade).toUpperCase() == retiraAcentos(cidadeParam).toUpperCase()) {
							$(cidade_option).attr({ selected: "selected" });
						}

						$("select[name=cidade]").append(cidade_option);
					});
				}

				$("#carregando_cidades").hide();
				$("select[name=cidade]").show();
			}
		});
	} else {
		$("select[name=cidade] > option[rel!=padrao]").remove();
	}
}

function buscaCEP(cep)
{
	$.ajax({
		url: "ajax_cep.php",
		type: "GET",
		data: { cep: cep },
		beforeSend: function () {
			$("#buscar_endereco, input[name=cep]").hide();
			$("#carregando_cep").show();
		},
		complete: function (data) {
			data = data.responseText.split(";");

			if (data[0] != "ok") {
				alert("EndereÁo n„o encontrado");
			} else {
				var estado   = data[4];
				var cidade   = data[3];
				var bairro   = data[2];
				var endereco = data[1];

				if ($("select[name=estado]").val() == estado) {
					$("select[name=cidade] > options").each(function () {
						if (retiraAcentos($(this).val()).toUpperCase() == retiraAcentos(cidade).toUpperCase()) {
							$("select[name=cidade]").val($(this).val());
							return false;
						}
					});
				} else {
					$("select[name=estado]").val(estado);

					buscaCidades(estado, cidade);	
				}

				$("input[name=bairro]").val(bairro);
				$("input[name=endereco]").val(endereco);

				$("#carregando_cep").hide();
				$("#buscar_endereco, input[name=cep]").show();

				alert("Por favor confirme a cidade");
			}
		}
	});
}

$(function () {
	$("input[name=cep]").mask("99.999-999");
	$("input[name=numero]").numeric();

	$("select[name=estado]").change(function () {
		var estado = $(this).val();

		buscaCidades(estado);
	});

	$("#atualizar_localizacao").click(function () {
		var lat = $("#posto_lat_novo").val();
		var lng = $("#posto_lng_novo").val();

		nova_localizacao = true;
		marks = [];

		var endereco = $("input[name=endereco]").val();
		var numero   = $("input[name=numero]").val();
		var bairro   = $("input[name=bairro]").val();
		var cidade   = $("select[name=cidade]").val();
		var estado   = $("select[name=estado]").val();

		geocoder.geocode( { "address": endereco+", "+numero+", "+bairro+", "+cidade+", "+estado }, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				var result = results[0].geometry.location;

				var lat = result.lat();
				var lng = result.lng();

				$("#posto_lat_novo").val(lat);
				$("#posto_lng_novo").val(lng);
				$("#posto_latlng_manual").val(false);
				
			}

			loadGoogleMaps();
		});
	});

	$("#buscar_endereco").click(function () {
		buscaCEP($("input[name=cep]").val());
	});

	$("#adicionar_telefone").click(function () {
		var input_telefone = $("<input />", {
			type: "text",
			name: "telefone[]",
			style: "width: 150px;"
		});

		$("#telefones_adicionais").append(input_telefone);
		$("#telefones_adicionais").append("<br />");
	});

	$("#confirmar_dados").click(function () {
		var msg_erro = [];

		$.each($("input[obrigatorio=true], select[obrigatorio=true]"), function () {
			if (!$(this).attr("multiplos")) {
				if ($.trim($(this).val()).length == 0) {
					msg_erro.push($(this).prevAll("label").text());
				}
			} else {
				var name   = $(this).attr("name");
				var valido = false;

				$.each($("input[name='"+name+"']"), function () {
					if ($.trim($(this).val()).length > 0) {
						valido = true;
						return false;
					}
				});

				if (valido == false) {
					msg_erro.push($(this).prevAll("label").text());
				}
			}
		});

		var tipos_aceitos = [
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document",
			"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
			"application/pdf",
			"image/jpeg",
			"image/png"
		];

		if (document.form_confirmacao_cadastro.comprovante_endereco.files.length == 0 || $.inArray(document.form_confirmacao_cadastro.comprovante_endereco.files[0].type, tipos_aceitos) == -1) {
			msg_erro.push("Comprovante de endereÁo");
		}

		if (msg_erro.length > 0) {
			alert("Os seguintes campos s„o obrigatÛrios: " + msg_erro.join(", "));
		} else {
			if ($("#posto_lat_novo").val().length == 0 || $("#posto_lng_novo").val().length == 0) {
				alert("Por favor atualize sua localizaÁ„o clicando no bot„o Atualizar LocalizaÁ„o");
			} else {
				$("input[name=acao]").val("confirmacao_dados");
				$("form[name=form_confirmacao_cadastro]").submit();
			}
		}
	});

	$("#anexar_comprovante").click(function () {
		$("input[name=comprovante_endereco]").click();
	});

	$("input[name=comprovante_endereco]").change(function () {
		$("#comprovante_anexado").text($(this).val());
	});
});
</script>

<form name="form_confirmacao_cadastro" method="POST" enctype="multipart/form-data" >
	<input type="hidden" name="acao" />

	<input type="hidden" id="posto_lat_atual" name="posto_lat_atual" value="<?=$lat_atual?>" />
	<input type="hidden" id="posto_lng_atual" name="posto_lng_atual" value="<?=$lng_atual?>" />

	<input type="hidden" id="posto_lat_novo" name="posto_lat_novo" />
	<input type="hidden" id="posto_lng_novo" name="posto_lng_novo" />

	<input type="hidden" id="posto_latlng_manual" name="posto_latlng_manual" />

	<div id="header" >
		<img id="logo" src="logos/logo_telecontrol_2013.png" />
		<h1>CONFIRMA«√O DE CADASTRO</h1>
		<img id="mapa" src="externos/mapa_rede/imagens/mapa_azul.gif" />
	</div>
	<div id="body" >
		<br />

		<h1 style="color: #FF0000">POR FAVOR CONFIRME SUA LOCALIZA«√O</h1>

		<br />

		<ul style="text-align: left;">
			<li style="color: #FF0000; font-weight: bold;">
				CASO SUA LOCALIZA«√O ESTEJA ERRADA, DIGITE O ENDERE«O NO FORMUL¡RIO ABAIXO E CLIQUE EM ATUALIZAR				
			</li>
			<li style="color: #FF0000; font-weight: bold;">
				SE PREFERIR DIGITE O CEP E CLIQUE EM BUSCAR ENDERE«O
			</li>
			<li style="color: #FF0000; font-weight: bold;">
				CASO A LOCALIZA«√O INDICADA PELO BAL√O N√O SEJA A CORRETA, FAVOR ARRAST¡-LO PARA O ENDERE«O CORRETO (AS INFORMA«’ES S√O ATUALIZADAS AUTOMATICAMENTE EXCETO O CEP, BAIRRO E N⁄MERO)
			</li>
			<li style="color: #FF0000; font-weight: bold;">
				EVITE ABREVIA«’ES
			</li>
			<li style="color: #FF0000; font-weight: bold;">
				TODOS OS CAMPOS S√O OBRIGAT”RIOS EXCETO COMPLEMENTO
			</li>
		</ul>

		<br />

		<div id="GoogleMaps" ></div>
		<div class="form">
			<div class="form_input" >
				<label>CEP</label><br />
				<input type="text" name="cep" style="width: 85px;" obrigatorio="true" /><button type="button" id="buscar_endereco">Buscar EndereÁo</button>
				<span id="carregando_cep" style="display: none;" >
					Buscando endereÁo do CEP
				</span>
			</div>

			<div class="form_input" >
				<label>Estado</label><br />
				<select name="estado" obrigatorio="true" >
					<option value="" >Selecione um estado</option>
					<?php
					foreach ($estados as $sigla => $nome) {
						echo "<option value='{$sigla}' >{$nome}</option>";
					}
					?>
				</select>
			</div>

			<div class="form_input" >
				<label>Cidade</label><br />
				<select name="cidade" obrigatorio="true" >
					<option value="" rel="padrao" >Selecione um estado para selecionar uma cidade</option>
				</select>
				<span id="carregando_cidades" style="display: none;" >
					Carregando as cidades do estado selecionado
				</span>
			</div>

			<div class="form_input" >
				<label>Bairro</label><br />
				<input type="text" name="bairro" />
			</div>

			<div class="form_input" >
				<label>EndereÁo</label><br />
				<input type="text" name="endereco" style="width: 100%;" obrigatorio="true" />
			</div>

			<div class="form_input" >
				<label>N˙mero</label><br />
				<input type="text" name="numero" style="width: 50px;" obrigatorio="true" />
			</div>

			<div class="form_input" >
				<label>Complemento</label><br />
				<input type="text" name="complemento" />
			</div>

			<div class="form_input" >
				<label style="color: #FF0000">CLIQUE NO BOT√O PARA ATUALIZAR A SUA LOCALIZA«√O</label><br />
				<button type="button" id="atualizar_localizacao" >Atualizar LocalizaÁ„o</button>
			</div>

			<div class="form_input" >
				<label style="color: #FF0000">COMPROVANTE DE ENDERE«O (docx, xlsx, pdf, jpg, png)</label><br />
				<input type="file" name="comprovante_endereco" style="display: none" />
				<button type="button" id="anexar_comprovante" >Anexar um comprovante de endereÁo</button><br />
				<span id="comprovante_anexado" ></span>
			</div>
		</div>

		<br /><br /><br />

		<h1 style="color: #FF0000">POR FAVOR CONFIRME MAIS ALGUNS DADOS</h1>

		<br />

		<ul style="text-align: left;">
			<li style="color: #FF0000; font-weight: bold;">
				TODOS OS CAMPOS S√O OBRIGAT”RIOS EXCETO FAX E I.E.
			</li>
		</ul>

		<div class="form" style="width: 100%;">
			<div class="form_input" style="display: inline-block; margin-right: 10px; vertical-align: top;" >
				<label>I.E.</label><br />
				<input type="text" name="ie" maxlength="17" style="width: 160px; vertical-align: top;" />
			</div>

			<div class="form_input" style="display: inline-block; margin-right: 10px; vertical-align: top;" >
				<label>E-mail</label><br />
				<input type="text" name="email" obrigatorio="true" />
			</div>

			<div class="form_input" style="display: inline-block; margin-right: 10px; vertical-align: top;" >
				<label>Contato</label><br />
				<input type="text" name="contato" obrigatorio="true" />
			</div>

			<div id="telefones_adicionais" class="form_input" style="display: inline-block; margin-right: 10px;" >
				<label>Telefone(s)</label><br />
				<input type="text" name="telefone[]" style="width: 150px;" obrigatorio="true" multiplos="true" /><button type="button" id="adicionar_telefone" style="font-weight: bold;" title="Clique para adicionar mais um n˙mero de telefone" >+</button><br />
			</div>

			<div class="form_input" style="display: inline-block; vertical-align: top;" >
				<label>Fax</label><br />
				<input type="text" name="fax" style="width: 150px;" />
			</div>
		</div>

		<br /><br /><br />

		<button type="button" id="confirmar_dados" >Confirmar os dados e prosseguir com o acesso</button>
	</div>

	<br />
</form>
