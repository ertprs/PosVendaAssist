<?php

/**
 *
 * @author Gabriel Tinetti
 *
**/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

use Posvenda\TcMaps;

function getQuery($codPosto = null, $estado = null, $cidade = null, $latitude = null, $longitude = null, $status = null, $linha = null, $except = null, $limitPostos = null) {
	global $con, $login_fabrica;

	$fields = "";
	$conditions = "";
	$joins = "";
	$orders = "";
	$limit = "";

	if (!empty($codPosto)) {
		$conditions .= " AND tpf.posto = {$codPosto} ";
	} else {
		$conditions .= "
			AND tpf.divulgar_consumidor IS TRUE
			AND tpf.categoria IN ('Autorizada', 'Locadora Autorizada')
		";
		$fields .= ", (111.045 * DEGREES(ACOS(COS(RADIANS({$latitude})) * COS(RADIANS(tpf.latitude)) * COS(RADIANS(tpf.longitude) - RADIANS({$longitude})) + SIN(RADIANS({$latitude})) * SIN(RADIANS(tpf.latitude))))) AS distancia ";
		$orders .= " ORDER BY distancia ASC ";
	}

	if (!empty($status)) {
		$conditions .= " AND tpf.credenciamento IN ({$status}) ";
	}

	if (!empty($linha)) {
		$joins .= " 
			INNER JOIN tbl_linha tl ON tl.linha IN ({$linha}) AND tl.fabrica = {$login_fabrica} 
			INNER JOIN tbl_posto_linha tpl ON tpl.linha = tl.linha AND tpl.posto = tpf.posto  
		";
	}

	if (!empty($except)) {
		$conditions .= " AND tpf.posto NOT IN ({$except}) ";
	}

	if (!empty($estado) AND !empty($cidade)) {
		$conditions .= "
			AND lower(tpf.contato_estado) = '{$estado}'
			AND lower(tpf.contato_cidade) = '{$cidade}'
		";
	}
	if (!empty($limitPostos))
		$limit .= " LIMIT {$limitPostos} ";

	$query = "
		SELECT DISTINCT tpf.posto,
			tpf.codigo_posto,
			tp.nome,
			tpf.nome_fantasia,
			tpf.credenciamento,
			tpf.contato_endereco,
			tpf.contato_numero,
			tpf.contato_complemento,
			tpf.contato_bairro,
			tpf.contato_cidade,
			tpf.contato_estado,
			tpf.contato_cep,
			tpf.contato_email,
			tpf.contato_fone_comercial,
			tpf.latitude,
			tpf.longitude,
			ARRAY_TO_STRING(ARRAY(
				SELECT tl.nome
				FROM tbl_posto_linha tpl
				INNER JOIN tbl_linha tl ON tl.linha = tpl.linha AND tl.fabrica = {$login_fabrica}
				WHERE tpl.posto = tpf.posto
				ORDER BY tl.nome
			), ', ') AS linhas,
			ttp.codigo AS tipo_posto,
			tpf.parametros_adicionais,
			tpf.tipo_atende
			{$fields}
		FROM tbl_posto_fabrica tpf
		INNER JOIN tbl_posto tp ON tp.posto = tpf.posto
		INNER JOIN tbl_tipo_posto ttp ON ttp.tipo_posto = tpf.tipo_posto AND ttp.fabrica = {$login_fabrica}
		{$joins}
		WHERE tpf.fabrica = {$login_fabrica}
		AND tpf.latitude IS NOT NULL
		AND tpf.longitude IS NOT NULL
		{$conditions}
		{$orders}
		{$limit};
	";
	$rPostos = pg_query($con, $query);
	$response = pg_fetch_all($rPostos);

	return $response;
}

if (isset($_POST['ajax'])) {
	$tcMaps = new TcMaps($login_fabrica);
	switch ($_POST['ajax']) {
		case 'loadCities':
			$uf = trim(strtoupper($_POST['uf']));

			$qCidade = "
				SELECT DISTINCT ON (distrito) fn_retira_especiais(distrito) AS distrito,
					id AS cod_cidade,
					latitude,
					longitude
				FROM tbl_ibge_completa
				WHERE uf = '{$uf}'
				AND (tipo = 'URBANO' OR tipo IS NULL) 
				ORDER BY distrito;
			";
			$rCidade = pg_query($con, $qCidade);
			$rCidade = pg_fetch_all($rCidade);

			$response = array_map(function ($r) {
				$r['distrito'] = utf8_encode(ucwords(strtolower($r['distrito'])));
				return $r;
			}, $rCidade);

			echo json_encode($response);
			break;
		case 'searchCEPGeo':
			$cep = trim($_POST['cep']);
			$response = $tcMaps->tcGeocodePostCode($cep);
			
			echo json_encode($response);
			break;
		case 'loadRouteMaps':
			$postoLatLng = $_POST['postoLatLng'];
			$origemLatLng = $_POST['origemLatLng'];

			$response = $tcMaps->route($origemLatLng, $postoLatLng);
			echo json_encode($response);
			break;
		case 'loadPostoLinhas':
			$posto = $_POST['posto'];

			$qLinhas = "
				SELECT tl.linha, 
					tl.nome
				FROM tbl_posto_linha tpl
				JOIN tbl_linha tl ON tl.linha = tpl.linha AND tl.fabrica = {$login_fabrica}
				WHERE tpl.posto = {$posto}
				ORDER BY tl.nome ASC
			";
			$rLinhas = pg_query($con, $qLinhas);
			$rLinhas = pg_fetch_all($rLinhas);

			$response = array_map(function ($r) {
				$r['nome'] = utf8_encode($r['nome']);
				return $r;
			}, $rLinhas);

			echo json_encode($response);
			break;
		case 'loadPostoCred':
			$posto = $_POST['posto'];

			$qCredenciamento = "
				SELECT credenciamento
				FROM tbl_posto_fabrica
				WHERE posto = {$posto}
				AND fabrica = {$login_fabrica}
			";
			$rCredenciamento = pg_query($con, $qCredenciamento);
			$credenciamento = pg_fetch_result($rCredenciamento, 0, 'credenciamento');

			$response = ['credenciamento' => strtoupper($credenciamento)];
			echo json_encode($response);
			break;
	}

	exit;
}

if (isset($_POST['posto_codigo'])) {
	unset($csvError);
	$codigoPosto = trim($_POST['posto_posto']);
	$linhas = implode(", ", $_POST['posto_linha']);
	$status = $_POST['posto_status'];
	$nStatus = array_map(function ($r) {
		return "'" . $r . "'";
	}, $status);
	$nStatus = implode(", ", $nStatus);

	$rPosto = getQuery($codigoPosto);

	$postoLatitude = $rPosto[0]['latitude'];
	$postoLongitude = $rPosto[0]['longitude'];

	$rNextPostos = getQuery(null, null, null, $postoLatitude, $postoLongitude, $nStatus, $linhas, $codigoPosto, 19);
	
	$postoLatLng = $postoLatitude . "," . $postoLongitude;

	$tcMaps = new TcMaps($login_fabrica);
	foreach ($rNextPostos as $key => $posto) {
		$nextLatLng = $posto['latitude'] . "," . $posto['longitude'];
		$distanciaKM = $tcMaps->route($postoLatLng, $nextLatLng);

		$rNextPostos[$key]["distancia"] = $distanciaKM['total_km'];
		$rNextPostos[$key]["rota"] = $distanciaKM;
	}

	$resPostos = array_merge($rPosto, $rNextPostos);

	$responsePostos = [
		'type' => 'posto',
		'postos' => $resPostos
	];
}

if (isset($_POST['ec_estado']) AND isset($_POST['ec_cidade'])) {
	unset($csvError);
	$cidade    = strtolower($_POST['ec_cidade']);
	$latitude  = $_POST['ec_latitude'];
	$longitude = $_POST['ec_longitude'];
	$estado    = strtolower($_POST['ec_estado']);
	$status    = $_POST['ec_status'];
	$linhas    = implode(", ", $_POST['ec_linha']);

	$nStatus = array_map(function ($r) {
		return "'" . $r . "'";
	}, $status);
	$nStatus = implode(", ", $nStatus);

	$rPostos = [];
	$rPostos = getQuery(null, $estado, $cidade, $latitude, $longitude, $nStatus, $linhas, null, null);

	$countPostos = (empty($rPostos)) ? 0 : count($rPostos);
	if ($countPostos < 20) {
		$limitPostos = 20 - $countPostos;

		$exceptPostos = array_map(function ($r) {
			if (!empty($r)) {
				return $r['posto'];
			}
		}, $rPostos);
		$exceptPostos = (!empty($exceptPostos)) ? implode(", ", $exceptPostos) : "";

		$auxPostos = getQuery(null, null, null, $latitude, $longitude, $nStatus, $linhas, $exceptPostos, $limitPostos);
		foreach ($auxPostos as $posto) {
			$rPostos[] = $posto;
		}
	}

	$responsePostos = [
		'type' => 'ec',
		'postos' => $rPostos
	];
}

if (isset($_POST['cep_cep'])) {
	unset($csvError);
	$cep = implode("", explode("-", $_POST['cep_cep']));
	$linhas = implode(", ", $_POST['cep_linha']);
	$status = $_POST['cep_status'];
	$cepLatitude = $_POST['cep_latitude'];
	$cepLatitude = trim($cepLatitude);
	$cepLongitude = $_POST['cep_longitude'];
	$cepLongitude = trim($cepLongitude);

	$nStatus = array_map(function ($r) {
		return "'" . $r . "'";
	}, $status);
	$nStatus = implode(",", $nStatus);

	// $rPostos = getQuery(null, null, null, $cepLatitude, $cepLongitude, $nStatus, $linhas, true);
	$countPostos = (empty($rPostos)) ? 0 : count($rPostos);

	if ($countPostos < 20) {
		$limitPostos = 20 - $countPostos;

		$exceptPostos = array_map(function ($r) {
			if (!empty($r)) {
				return $r['posto'];
			}
		}, $rPostos);
		$exceptPostos = (!empty($exceptPostos)) ? implode(", ", $exceptPostos) : "";

		$auxPostos = getQuery(null, null, null, $cepLatitude, $cepLongitude, $nStatus, $linhas, $exceptPostos, $limitPostos);
		foreach ($auxPostos as $posto) {
			$rPostos[] = $posto;
		}
	}


	$partidaLatLng = $cepLatitude . "," . $cepLongitude;

	$tcMaps = new TcMaps($login_fabrica);
	foreach ($rPostos as $key => $posto) {
		$postoLatLng = $posto['latitude'] . "," . $posto['longitude'];
		$distanciaKM = $tcMaps->route($partidaLatLng, $postoLatLng);

		$rPostos[$key]["distancia"] = $distanciaKM['total_km'];
		$rPostos[$key]["rota"] = $distanciaKM;
	}

	$responsePostos = [
		'type' => 'cep',
		'postos' => $rPostos
	];
}


// gera csv -----
if (!empty($_POST)) {
	$sql = "
		SELECT DISTINCT tpf.posto,
			tpf.codigo_posto,
			tp.nome,
			tpf.nome_fantasia,
			tpf.credenciamento,
			tpf.contato_endereco,
			tpf.contato_numero,
			tpf.contato_complemento,
			tpf.contato_bairro,
			tpf.contato_cidade,
			tpf.contato_estado,
			tpf.contato_cep,
			tpf.contato_email,
			tpf.contato_fone_comercial,
			tpf.latitude,
			tpf.longitude,
			ARRAY_TO_STRING(ARRAY(
				SELECT tl.nome
				FROM tbl_posto_linha tpl
				INNER JOIN tbl_linha tl ON tl.linha = tpl.linha AND tl.fabrica = {$login_fabrica}
				WHERE tpl.posto = tpf.posto
				ORDER BY tl.nome
			), ', ') AS linhas,
			ttp.codigo AS tipo_posto,
			tpf.parametros_adicionais,
			tpf.tipo_atende
			{$fields}
		FROM tbl_posto_fabrica tpf
		INNER JOIN tbl_posto tp ON tp.posto = tpf.posto
		INNER JOIN tbl_tipo_posto ttp ON ttp.tipo_posto = tpf.tipo_posto AND ttp.fabrica = {$login_fabrica}
		{$joins}
		WHERE tpf.fabrica = {$login_fabrica}
		AND tpf.latitude IS NOT NULL
		AND tpf.longitude IS NOT NULL
		{$orders}
		;
	";
	$rPostos = pg_query($con, $sql);
	$response = pg_fetch_all($rPostos);
	if(pg_num_rows($rPostos) > 0) {

	$curDate = new DateTime();
	$csvFileName = "relatorio_posto_cadastrado_" . $curDate->format("mdiu") . ".csv";
	$csvDir = "/tmp/" . $csvFileName;

	$file = fopen("/tmp/" . $csvFileName, "w");

	$rawColumns = array_keys($response[0]);
	
	unset($rawColumns[array_search("posto", $rawColumns)]);
	unset($rawColumns[array_search("latitude", $rawColumns)]);
	unset($rawColumns[array_search("longitude", $rawColumns)]);
	unset($rawColumns[array_search("parametros_adicionais", $rawColumns)]);
	unset($rawColumns[array_search("distancia", $rawColumns)]);
	unset($rawColumns[array_search("tipo_atende", $rawColumns)]);
	unset($rawColumns[array_search("rota", $rawColumns)]);

	$nColumns = array_map(function ($r) {
		return ucwords(implode(" ", explode("_", $r)));
	}, $rawColumns);

	$columns = implode(";", $nColumns);
	$columns .= "\n";

	fwrite($file, $columns);

	$rows = "";
	$specialChars = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML401, "ISO-8859-1"));

	foreach ($response as $postos) {
		foreach ($postos as $key => $value) {
			if (in_array($key, ["posto", "latitude", "longitude", "parametros_adicionais", "distancia", "tipo_atende", "rota"])) {
				continue;
			}

			$rows .= (!empty($value)) ? strtr($value, $specialChars) : " ";
			$rows .= ";";	
		}
		$rows .= "\n";
	}

	fwrite($file, $rows);
	fclose($file);

	$csvError = [];

	try {
		if (file_exists($csvDir)) {
			system("mv $csvDir xls/$csvFileName");
		} else {
			$csvError[] = "file not found";
		}
	} catch (Exception $e) {
		$csvError[] = "error while moving file";		
	}
	}
}

$globalLat = (!empty($cepLatitude)) ? trim($cepLatitude) : trim($postoLatitude);
$globalLon = (!empty($cepLongitude)) ? trim($cepLongitude) : trim($postoLongitude);
$globalType = $responsePostos['type'];

$layout_menu = "callcenter";
$title = "RELATÓRIO DE POSTOS AUTORIZADOS CADASTRADOS";

include "cabecalho_new.php";

$plugins = array(
    "shadowbox",
    "multiselect",
    "tooltip",
    "font_awesome",
    "mask",
    "dataTable"
);

include ("plugin_loader.php");

$qLinhas = "
	SELECT linha,
		nome,
		codigo_linha
	FROM tbl_linha
	WHERE fabrica = {$login_fabrica}
	AND ativo IS TRUE
	ORDER BY nome ASC;
";
$rLinhas = pg_query($con, $qLinhas);
$rLinhas = pg_fetch_all($rLinhas);

?>

<style type="text/css">
	input {width:95%;}
	select {width:100%;}

	.tab-pane {
		padding: 10px 0;
	}

	.tab-pane form {
		padding: 0px;
		margin: 0px;
	}

	.mapa-legenda {
		width:20px;
		height:20px;
		float:left;
		border-radius:2px;
		margin-right:5px;
	}

	#map-render {
		width:100%;
		height:30rem;
		background-color:#F1F1F1;
	}

	th {
		vertical-align: middle;
		text-align: center;
		white-space: nowrap;
	}

	td {
		white-space: nowrap;
	}

	thead, tfoot {background-color:#596D9B;color:#FFF;font-weight:bold;}
</style>

<script type="text/javascript">
	let globalLat = "<?= $globalLat ?>";
	let globalLon = "<?= $globalLon ?>";
	let globalType = "<?= $globalType ?>";

	$(function () {
		$(".multiselect").multiselect({
			selectedText: "# de # opções"
		});
		$("input[name=cep_cep]").mask("99999-999");

		Shadowbox.init();

		$(".btn-lupa").on("click", function () {
			$.lupa($(this));
		});

		$("select[name=ec_estado]").on("change", function (e) {
			let uf = $(this).val();
			$("select[name=ec_cidade]").html("");
			$("#ec").find(".ec-cidade-spin").fadeIn(300);

			let selectOpt = $("<option></option>", {
				text: "Selecione",
				val: ""
			});
			$("select[name=ec_cidade]").prepend(selectOpt);

			$.ajax({
				url: window.location,
				type: 'POST',
				async: true,
				data: {
					ajax: 'loadCities',
					uf: uf
				}
			}).fail(function (response) {
				alert("Falha ao buscar cidades. Tente novamente");
				$("#ec").find(".ec-cidade-spin").fadeOut(300);
			}).done(function (response) {
				response = JSON.parse(response);

				$.each(response, function (index, element) {
					let option = $("<option></option>", {
						text: element.distrito
					});
					$(option).attr("data-latitude", element.latitude.replace(",", "."));
					$(option).attr("data-longitude", element.longitude.replace(",", "."));
					$(option).val(element.distrito);

					$("select[name=ec_cidade]").append(option);
				});
				$("#ec").find(".ec-cidade-spin").fadeOut(300);
			});
		});

		$("button[type=submit]").on("click", function (e) {
			e.preventDefault();

			let form = $(this).parents("form");
			let groups = $(form).find(".control-group");

			let lupas = $(form).find(".btn-lupa");
			$(lupas).on("click", function () {
				$(lupas).removeClass("btn-danger");
			})

			verifyForm(form, function () {
				$(".modal-loading").modal();
				$(form).submit();
			});
		});

		$(".popover-linhas").popover({
			trigger: 'hover',
			placement: 'top',
			animation: 'true',
			delay: '300'
		});

		$("select[name=ec_cidade]").on("change", function () {
			let option = $(this).find("option:selected");

			$(this).parents(".span4").find("input[name=ec_latitude]").val($(option).data("latitude"));
			$(this).parents(".span4").find("input[name=ec_longitude]").val($(option).data("longitude"));
		});

		$(".btn-search-cep").on("click", function () {
			$(".cep-address-row").fadeOut(300);
			$("input[name=cep_latitude]").val("");
			$("input[name=cep_longitude]").val("");

			$(".cep-address-row").find(".alert").removeClass("alert-danger");
			$(".cep-address-row").find(".alert").addClass("alert-info");

			let buttonSearch = $(this);
			$(buttonSearch).removeClass("btn-danger");
			$(buttonSearch).addClass("btn-info");

			$(".cep-cep-spin").fadeIn(300);
			let cep = $(buttonSearch).parents(".input-append").find("input").val();

			if (cep.length == 0) {
				$(buttonSearch).removeClass("btn-info");
				$(buttonSearch).addClass("btn-danger");
				$(buttonSearch).parents(".control-group").addClass("error");
				$(".cep-cep-spin").fadeOut(300);
				return false;
			}
			
			$(".cep-address-correios").html("");
			$(".cep-address-geocoder").html("");

			Promise.all([
				getCorreiosAddress(cep, function (response) {
					if (!response.exception) {
						let corrAddress = formatAddress(response.street, response.neighborhood, response.city, response.state);
						$(".cep-address-correios").text("Correios: " + corrAddress);
					} else {
						$(".cep-address-correios").text(response.exception);
					}
				}),
				getGeocoderAddress(cep, function (response) {
					if (!response.exception) {
						let geoAddress = formatAddress(response.street, response.neighborhood, response.city, response.state);
						$("input[name=cep_latitude]").val(response.lat);
						$("input[name=cep_longitude]").val(response.lon);
						$(".cep-address-geocoder").text("Geolocalização encontrada: " + geoAddress);
					} else {
						$(".cep-address-row").find(".alert").removeClass("alert-info");
						$(".cep-address-row").find(".alert").addClass("alert-danger");
						$(".cep-address-geocoder").text("Geolocalização não encontrada.");
					}
				})
			]).then(function () {
				$(".cep-cep-spin").fadeOut(300);
				$(".cep-address-row").fadeIn(300);	
			});
		});

		Map = new Map("map-render");
		Markers = new Markers(Map);
		Router = new Router(Map);

		Map.load();

		$(".localizar").on("click", function () {
			localizaPosto($(this));
		});

		$(".rota-posto").on("click", function () {
			rotaPosto($(this));
		});
	});

	function formatAddress(street, neighborhood, city, state) {
		let address = "";

		if (typeof street !== 'undefined' && street.length > 0) {
			address += street;
		}

		if (typeof neighborhood !== 'undefined' && neighborhood.length > 0) {
			if (address.length > 0)
				address += ", " + neighborhood;
			else
				address += neighborhood;
		}

		if (typeof city !== 'undefined' && city.length > 0) {
			if (address.length > 0)
				address += " - " + city;
			else
				address += city;
		}

		if (typeof state !== 'undefined' && state.length > 0) {
			if (address.length > 0)
				address += ", " + state;
			else
				address += state;
		}

		return address;
	}

	function getCorreiosAddress(cep, callback) {
		let resCorrJson = {};
		let resCorr = new Promise(function (resolve, reject) {
			$.ajax({
				url: 'ajax_cep.php',
				type: 'GET',
				async: true,
				data: {
					cep: cep.replace("-", "")
				},
				timeout: 10000 // 10 seconds timeout
			}).done(function (response) {
				resolve(response);
			}).fail(function (response) {
				reject(response);
			});
		}).then(function (resolve) {
			resCorr = resolve.split(";");
			if (resCorr.length > 1) {
				resCorrJson = {
					street: resCorr[1],
					neighborhood: resCorr[2],
					city: resCorr[3],
					state: resCorr[4]
				};
			} else {
				resCorrJson = {exception: "CEP não encontrado na base de dados dos Correios"};
			}

			callback(resCorrJson);
		}).catch(function (reject) {
			$.ajax({
				url: 'ajax_cep.php',
				type: 'GET',
				async: true,
				data: {
					cep: cep.replace("-", ""),
					method: 'database'
				}
			}).done(function (response) {
				resCorr = response.split(";");
				if (resCorr.length > 1) {
					resCorrJson = {
						street: resCorr[1],
						neighborhood: resCorr[2],
						city: resCorr[3],
						state: resCorr[4]
					};
				} else {
					resCorrJson = {exception: "CEP não encontrado em nossa base de dados"};
				}

				callback(resCorrJson);
			});
		});
	}

	function getGeocoderAddress(cep, callback) {
		$.ajax({
			url: window.location,
			type: 'POST',
			async: true,
			data: {
				ajax: 'searchCEPGeo',
				cep: cep.replace("-", "")
			}
		}).done(function (response) {
			response = JSON.parse(response);
			callback(response.request_information);
		});
	}

	function rotaPosto(object) {
		let rotaData = $(object).parents("td").find("input[name=rota_posto]").val();
		rotaData = JSON.parse(rotaData);

		Router.remove();
		Router.clear();
		Router.add(Polyline.decode(rotaData.rota.routes[0].geometry));
		Router.render();
		$(window).scrollLeft(0);
		Map.scrollToMap();
	}

	function verifyForm(form, callback) {
		let errors = 0;

		let inputs = $(form).find("input[type=text]");
		let hiddenInputsCep = $(form).find(".cep-latlon")[0];
		let selects = $(form).find("select");

		if ($(hiddenInputsCep).length > 0) {
			if ($(hiddenInputsCep).val().length == 0) {
				$(form).find(".btn-search-cep").removeClass("btn-info");
				$(form).find(".btn-search-cep").addClass("btn-danger");
				$(form).find(".btn-search-cep").parents(".control-group").addClass("error");
				setTimeout(function () {
					$(form).find(".btn-search-cep").parents(".control-group").removeClass("error");
				}, 2000);
				errors++;
			}
		}

		if ($(inputs).length > 0) {
			$.each($(inputs), function (index, element) {
				if (!$(element).val()) {
					$(element).parents(".control-group").addClass("error");
					setTimeout(function () {
						$(element).parents(".control-group").removeClass("error");
					}, 2000);
					errors++;
				}
			});
		}

		if ($(selects).length > 0) {
			$.each($(selects), function (index, element) {
				if (!$(element).val()) {
					$(element).parents(".control-group").addClass("error");
					setTimeout(function () {
						$(element).parents(".control-group").removeClass("error");
					}, 2000);
					errors++;
				}
			});
		}

		if (errors == 0) {
			callback();
		} else {
			let lupas = $(form).find(".btn-lupa");

			if (lupas.length) {
				$.each(lupas, function (index, element) {
					$(element).addClass("btn-danger");
					setTimeout(function () {
						$(element).removeClass("btn-danger");
					}, 2000);
				});
			}
		}
	}

	function retorna_posto(retorno) {
		$("input[name=posto_nome]").val(retorno.nome);
		$("input[name=posto_codigo]").val(retorno.codigo);
		$("input[name=posto_posto]").val(retorno.posto);

		$.ajax({
			url: window.location,
			type: 'POST',
			async: true,
			data: {
				ajax: 'loadPostoCred',
				posto: retorno.posto
			}
		}).done(function (response) {
			response = JSON.parse(response);
			let status = $("#posto-status").find("option");
			let statusMulti = $("input[name=multiselect_posto-status]");

			$.each(status, function (index, element) {
				if ($(element).val() === response.credenciamento) {
					$(element).attr("selected", "true");
				}
			})

			$.each(statusMulti, function (index, element) {
				if ($(element).val() === response.credenciamento) {
					$(element).prop("checked", true);
					$(element).val(response.credenciamento);
				} else {
					$(element).prop("checked", false);
				}
				$("#posto-status").multiselect({
					selectedText: "# de # opções"
				});
			});
		})

		$.ajax({
			url: window.location,
			type: 'POST',
			async: true,
			data: {
				ajax: 'loadPostoLinhas',
				posto: retorno.posto
			}
		}).done(function (response) {
			response = JSON.parse(response);
			let linhas = $("#posto-linha").find("option");
			let linhasMulti = $("input[name=multiselect_posto-linha]");

			$.each(response, function (index, element) {
				$.each(linhas, function (idx, elem) {
					if ($(elem).val() == element.linha) {
						$(elem).attr("selected", "true");
					}
				});
				$.each(linhasMulti, function (idx, elem) {
					if ($(elem).val() == element.linha) {
						$(elem).prop("checked", "true");
						$(elem).val(element.linha);
						$("#posto-linha").multiselect({
							selectedText: "# de # opções"
						})
					}
				})
			});
		});
	}

	function localizaPosto(object) {
		let postoData = $(object).parents("tr");
		let latitude = $(postoData).data("latitude");
		let longitude = $(postoData).data("longitude");

		Map.setView(latitude, longitude, 15);
		$(window).scrollLeft(0);
		Map.scrollToMap();
	}
</script>

<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>
<script src="plugins/leaflet/map.js" ></script>
<script src="plugins/mapbox/geocoder.js"></script>
<script src="plugins/mapbox/polyline.js"></script>

<div class="row-fluid">
	<div class="tabbable">
		<ul class="nav nav-tabs">
			<li class="active posto-tab">
				<a href="#posto" data-toggle="tab">Posto Autorizado</a>
			</li>
			<li class="ec-tab">
				<a href="#ec" data-toggle="tab">Estado/Cidade</a>
			</li>
			<li class="cep-tab">
				<a href="#cep" data-toggle="tab">CEP</a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="posto">
				<form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
					<div class="row-fluid">
						<div class="span2"></div>
						<div class="span3 input-append control-group">
							<label>Código do Posto:</label>
							<input name="posto_posto" type="hidden">
							<input name="posto_codigo" class="lupa form-control" type="text" style="width:75%">
							<button type="button" class="btn btn-lupa" >
								<i class="fas fa-search"></i>
							</button>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo">
						</div>
						<div class="span5 input-append control-group">
							<label for="nome_posto">Nome do Posto:</label>
							<input name="posto_nome" class="lupa" id="nome_posto" type="text">
							<button type="button" class="btn btn-lupa">
								<i class="fas fa-search"></i>
							</button>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome">
						</div>
						<div class="span2"></div>
					</div>
					<div class="row-fluid">
						<div class="span2"></div>
						<div class="span4 control-group">
							<label for="posto_linha">Linhas:</label>
							<select class="multiselect form-control" name="posto_linha[]" id="posto-linha" multiple="multiple">
								<?php foreach ($rLinhas as $linha) { ?>
									<option value="<?= $linha['linha'] ?>"><?= $linha['nome'] ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="span4 control-group" style="margin-left:-35px;">
							<label for="posto-status">Status:</label>
							<select class="multiselect form-control" name="posto_status[]" id="posto-status" multiple="multiple">
								<option value="CREDENCIADO">Credenciado</option>
								<option value="EM CREDENCIAMENTO">Em Credenciamento</option>
								<option value="DESCREDENCIADO">Descredenciado</option>
								<option value="EM DESCREDENCIAMENTO">Em Descredenciamento</option>
							</select>
						</div>
						
					</div>
					<div class="row-fluid" style="margin-top:20px;">
						<div class="span12" style="text-align:center">
							<button class="btn btn-primary" type="submit" name="pesquisa_posto">
								<i class="fas fa-search" style="margin:0 5px"></i>
								Pesquisar
							</button>
						</div>
					</div>
				</form>
			</div>
			<div class="tab-pane" id="ec">
				<form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
					<div class="row-fluid">
						<div class="span2"></div>
						<div class="span4 control-group">
							<label>Linha:</label>
							<select multiple="multiple" name="ec_linha[]" id="ec-linha" class="multiselect form-control">
								<?php foreach ($rLinhas as $linha) { ?>
									<option value="<?= $linha['linha'] ?>"><?= $linha['nome'] ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="span4 control-group">
							<label>Status Posto:</label>
							<select multiple="multiple" name="ec_status[]" class="multiselect form-control">
								<option value="CREDENCIADO" selected>Credenciado</option>
								<option value="EM CREDENCIAMENTO">Em Credenciamento</option>
								<option value="DESCREDENCIADO">Descredenciado</option>
								<option value="EM DESCREDENCIAMENTO">Em Descredenciamento</option>
							</select>
						</div>
						<div class="span2"></div>
					</div>
					<div class="row-fluid">
						<div class="span2"></div>
						<div class="span4 control-group">
							<label>Estado:</label>
							<select name="ec_estado" class="form-control">
								<option value="">Selecione</option>
								<?php foreach ($array_estados() as $key => $value) { ?>
									<option value="<?= $key ?>"><?= $value ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="span4 control-group">
							<label>Cidade:</label>
							<input type="hidden" name="ec_latitude">
							<input type="hidden" name="ec_longitude">
							<select name="ec_cidade" class="form-control">
								<option value="">Selecione</option>
							</select>
						</div>
						<div class="span2">
							<i style="position:absolute;margin-top:30px;margin-left:-10px;display:none;" class="ec-cidade-spin fa-spinner fa fa-spin"></i>
						</div>
					</div>
					<div class="row-fluid" style="margin-top:20px;">
						<div class="span12" style="text-align:center">
							<button type="submit" class="btn btn-primary" name="pesquisa_ec">
								<i class="fas fa-search" style="margin:0 5px"></i>
								Pesquisar
							</button>
						</div>
					</div>
				</form>
			</div>
			<div class="tab-pane" id="cep">
				<form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
					<div class="row-fluid cep-address-row" style="display:none;">
						<div class="span2"></div>
						<div class="span8 alert alert-info" style="padding:0px;">
							<h6 class="cep-address-correios"></h6>
							<h6 class="cep-address-geocoder"></h6>
						</div>
						<div class="span2"></div>
					</div>
					<div class="row-fluid">
						<div class="span3">
							<i style="float:right;margin-top:28px;margin-right:-10px;display:none;" class="cep-cep-spin fa-spinner fa fa-spin"></i>
						</div>
						<div class="span3 input-append control-group">
							<label>CEP:</label>
							<input name="cep_cep" type="text" style="width:75%">
							<button type="button" class="btn btn-info btn-search-cep">
								<i class="fas fa-search"></i>
							</button>
						</div>
						<input type="hidden" class="cep-latlon" name="cep_longitude">
						<input type="hidden" class="cep-latlon" name="cep_latitude">
						<div class="span3 control-group">
							<label>Linha:</label>
							<select multiple="multiple" name="cep_linha[]" id="cep-linha" class="multiselect form-control">
								<?php foreach ($rLinhas as $linha) { ?>
									<option value="<?= $linha['linha'] ?>"><?=$linha['nome']?></option>
								<?php } ?>
							</select>
						</div>
						<div class="span3"></div>
					</div>
					<div class="row-fluid">
						<div class="span3"></div>
						<div class="span4">
							<small class="text-info">Ao digitar o CEP, clique na <b>lupa</b> para pesquisar.</small>
						</div>
						<div class="span5"></div>
					</div>
					<div class="row-fluid">
						<div class="span3"></div>
						<div class="span4 control-group">
							<label>Status Posto:</label>
							<select multiple="multiple" name="cep_status[]" id="cep-status" class="multiselect form-control">
								<option value="CREDENCIADO" selected>Credenciado</option>
								<option value="EM CREDENCIAMENTO">Em Credenciamento</option>
								<option value="DESCREDENCIADO">Descredenciado</option>
								<option value="EM DESCREDENCIAMENTO">Em Descredenciamento</option>
							</select>
						</div>
					</div>
					<div class="row-fluid" style="margin-top:30px;">
						<div class="span12" style="text-align:center">
							<button type="submit" class="btn btn-primary" name="pesquisa_cep">
								<i class="fas fa-search" style="margin:0 5px"></i>
								Pesquisar
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span12 mapa">
		<div class="" id="map-render"></div>
	</div>
	<div class="span12 mapa" style="padding:5px 0">
		<div class="" style="float:right;margin-right:18px;">
			<button type="button" class="btn btn-small btn-success btn-centraliza"><b>Centralizar</b></button>
		</div>
		<div style="padding:2px 0;margin-left:-18px">
			<div class="mapa-legenda" style="background-color:#f45d5d;"></div>
			<small><b>Posto Descredenciado</b></small>
		</div>
		<div style="padding:2px 0;margin-left:-18px">
			<div class="mapa-legenda" style="background-color:#fcf967;"></div>
			<small><b>Posto Atende Somente Revenda</b></small>
		</div>
		<div style="padding:2px 0;margin-left:-18px">
			<div class="mapa-legenda" style="background-color:#6cf776;"></div>
			<small><b>Posto a Mais de 30 km do Consumidor</b></small>
		</div>
		<div style="padding:2px 0;margin-left:-18px">
			<div class="mapa-legenda" style="background-color:#2D2D2D"></div>
			<small><b>* Negrito: Postos que possuem observação em relação às linhas de atendimento</b></small>
		</div>
	</div>
</div>

</div>

<!-- loading -->
<div class="modal fade modal-loading" role="dialog" data-backdrop="static" tabindex="-1" aria-hidden="true" style="width:400px;left:50%;margin-left:-200px;">
	<div class="modal-body" style="text-align:center">
		<h5><i class="fa-spinner fa fa-spin" style="font-size:14px;margin:0 5px;"></i>Carregando...</h5>
	</div>
</div>

<?php
if (!empty($responsePostos)) { 
	$responseType = $responsePostos['type'];
?>

<script type="text/javascript">
	let dataContent;
	$(function () {
		$.dataTableLoad({
		  table: '.table-data',
		  aaSorting: [[13, "asc"]]
		});
	});
</script>
<style type="text/css">
	th, th.sorting {padding:15px;}
</style>
<div style="margin:0 auto;box-sizing:border-box;padding:10px">
	<table class="table table-bordered table-data">
		<thead>
			<tr>
				<th>Tipo</th>
				<th>Código</th>
				<th>Nome</th>
				<th>Nome Fantasia</th>
				<th>Credenciamento</th>
				<th>Logradouro</th>
				<th>Bairro</th>
				<th>Cidade</th>
				<th>Estado</th>
				<th>CEP</th>
				<th>Email</th>
				<th>Telefone</th>
				<th>Linhas</th>
				<?= (!in_array($responseType, ["ec"])) ? "<th>KM</th>" : "" ?>
				<th>Localização</th>
				<?= (!in_array($responseType, ["posto", "ec"])) ? "<th>Rota</th>" : "" ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($responsePostos['postos'] as $posto) { 
				$parametrosAdicionais = json_decode($posto['parametros_adicionais'], true);
			?>
			<tr 
				<?php if ($posto['credenciamento'] == "DESCREDENCIADO") { ?>
					style="background-color:#f45d5d;"
				<?php } elseif ($posto['tipo_atende'] == 't') { ?>
					style="background-color:#F3F781;"
				<?php } elseif (round($posto['distancia'], 1) > 30) { ?>
					style="background-color:#6cf776;"
				<?php } elseif ($posto['latitude'] === $globalLat AND $posto['longitude'] == $globalLon) { ?>
					style="background-color:#E8E8E8;"
				<?php } ?>
				class="posto-data"
				data-latitude="<?= $posto['latitude'] ?>"
				data-longitude="<?= $posto['longitude'] ?>"
				data-observacao="<?= utf8_decode($parametrosAdicionais['obs_posto_cadastrado']) ?>"
			>
				<?php
					$style = "style='text-align:center;vertical-align:middle;";
					if (!empty($parametrosAdicionais['obs_posto_cadastrado'])) $style .= "font-weight:bold;'";
					$style .= "'";
				?>
				<td <?= $style ?>>
				<?php if (in_array($posto['tipo_posto'], ['5SA', '5SB', '5SC'])) { ?>
					<img style="max-width:50px !important;width:50px;margin:0 auto;display:block;" src="imagens/ico_posto5S.png" title="Serviço Autorizado 5S">
				<?php }?>
				</td>
				<td <?= $style ?>><?= $posto['codigo_posto'] ?></td>
				<td class="posto-nome" <?= $style ?>><?= $posto['nome'] ?></td>
				<td class="posto-fantasia" <?= $style ?>><?= $posto['nome_fantasia'] ?></td>
				<td <?= $style ?>><?= $posto['credenciamento'] ?></td>
				<td <?= $style ?>><?= $posto['contato_endereco'] . ", " . $posto['contato_numero'] ?></td>
				<td <?= $style ?>><?= $posto['contato_bairro'] ?></td>
				<td <?= $style ?>><?= $posto['contato_cidade'] ?></td>
				<td <?= $style ?>><?= $posto['contato_estado'] ?></td>
				<td <?= $style ?>><?= substr($posto['contato_cep'], 0, 5) . "-" . substr($posto['contato_cep'], 5) ?></td>
				<td <?= $style ?>><?= $posto['contato_email'] ?></td>
				<td <?= $style ?>><?= $posto['contato_fone_comercial'] ?></td>
				<td <?= $style ?>>
					<span
						class="label label-info popover-linhas"
						data-original-title="Linhas Atendidas"
						data-content="<?= $posto['linhas'] ?>"
					>
						Linhas
					</span>
				</td>
				<?php if (!in_array($responseType, ["ec"])) { ?>
					<td nowrap <?= $style ?>><?= round($posto['distancia'], 1) ?></td>
				<?php } ?>
				<td style="font-size:14px;text-align:center;vertical-align:middle">
					<button class="btn btn-small btn-warning localizar">
						<i class="fas fa-map-marker-alt"></i>
					</button>
				</td>
				<?php if (!in_array($responseType, ["posto", "ec"])) { ?>
				<td style='font-size:14px;text-align:center;vertical-align:middle'>
					<input type="hidden" name="rota_posto" value='<?= htmlspecialchars(json_encode($posto["rota"])) ?>'>
					<button class='btn btn-small btn-primary rota-posto'>
						<i class='fas fa-map'></i>
					</button>
				</td>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<!-- csv -->

<div class="row-fluid">
	<div class="span12" style="text-align:center;">
		<a
			<?= (count($csvError) > 0) ? 'disabled' : '' ?>
			class="btn <?= (count($csvError) == 0) ? 'btn-success' : 'btn-danger' ?>"
			href="<?= (count($csvError) == 0) ? 'xls/' . $csvFileName : "#" ?>"
		>
			<i class="fas <?= (count($csvError) == 0) ? 'fa-file-alt' : 'fa-exclamation-circle' ?>"></i>
			<b>Download CSV</b>
		</a>
	</div>
</div>

<script type="text/javascript">
	$(function () {
		Markers.remove();
		Markers.clear();

		let locations = dataTableGlobal.fnGetNodes();

		if (globalType == "cep")
			Markers.add(globalLat, globalLon, "blue", "Localização Atual");

		$.each(locations, function (index, element) {
			let color = "red";
			
			if ($(element).data("latitude") == globalLat && $(element).data("longitude") == globalLon)
				color = "blue";

			let latitude = $(element).data("latitude");
			let longitude = $(element).data("longitude");
			let postoNome = $(element).find(".posto-nome").text();
			let postoFantasia = $(element).find(".posto-fantasia").text() || "";
			let observacao = $(element).data("observacao");

			Markers.add(latitude, longitude, color, `<b>${postoNome}</b><br />${postoFantasia}`, observacao);
		});

		Markers.render();

		if (globalType == "cep" || globalType == "posto") {
			Map.setView(globalLat, globalLon, 15);
		} else {
			Markers.focus();
		}

		$(".btn-centraliza").on("click", function () {
			Markers.focus();
			Router.remove();
			Router.clear();
			$(window).scrollLeft(0);
			Map.focus();
		});
	});
</script>
<?php } ?>

<?php include('rodape.php') ?>
	
