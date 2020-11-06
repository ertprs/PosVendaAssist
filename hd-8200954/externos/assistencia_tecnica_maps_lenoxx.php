<?php
require_once('../admin/dbconfig.php');
require_once('../admin/includes/dbconnect-inc.php');
require_once('../admin/funcoes.php');
$token        = trim($_GET['tk']);
$token_post   = $_POST['token'];
$cod_fabrica  = $_GET['cf'];
$cod_fabrica  = base64_decode(trim($cod_fabrica));

$nome_fabrica = $_GET['nf'];
$nome_fabrica = base64_decode(trim($nome_fabrica));
$brand = $_REQUEST['brand']; 

if (!empty($_POST['fabrica'])) {
	$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = ". $_POST['fabrica'];
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$cod_fabrica = $_POST['fabrica'];
		$nome_fabrica = pg_fetch_result($res,0,0);
	}
}

$token_comp = base64_encode(trim("telecontrolNetworking".$nome_fabrica."assistenciaTecnica".$cod_fabrica));
if (!empty($token_post)) $token = $token_post;

if ($token != $token_comp) {
	exit;
}

function maskCep($cep) {
	$num_cep = preg_replace('/\D/', '', $cep);
	return (strlen($cep == 8)) ? preg_replace('/(\d\d)(\d{3})(\d{3})/', '$1.$2-$3', $num_cep) : $cep;
}

function maskFone($telefone) {
	if (!strstr($telefone, "(")) {
		$telefone = str_replace("-", '', $telefone);
		$inicio   = substr($telefone, 0, 2);
		$meio     = substr($telefone, 2, 4);
		$fim      = substr($telefone, 6, strlen($telefone));
		$telefone = "(".$inicio.") ".$meio."-".$fim;
	}

	return $telefone;
}

function retira_acentos($texto) {
	$array1 = array( 'á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç'
	, 'Á', 'À', 'Â', 'Ã', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï', 'Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç' );
	$array2 = array( 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c'
	, 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C' );
	return str_replace( $array1, $array2, $texto);
}

/* Busca Cidades */
if (isset($_POST['uf']) && isset($_POST['linha'])) {

	$uf      = $_POST['uf'];
	$linha   = $_POST['linha'];
	$fabrica = $_POST['fabrica'];

	if ($fabrica == 74){
		$sql ="	SELECT
					distinct upper(trim(fn_retira_especiais(tbl_ibge.cidade))) as contato_cidade
				FROM tbl_posto_fabrica
				JOIN tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
				JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica_ibge.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $fabrica
				JOIN tbl_ibge on tbl_ibge.cod_ibge = tbl_posto_fabrica_ibge.cod_ibge 
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				AND tbl_ibge.estado = '$uf'
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND tbl_posto_fabrica.posto <> 6359
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE ORDER BY 1 ASC";
	}else{
		$sql = "SELECT
					distinct upper(trim(fn_retira_especiais(tbl_posto_fabrica.contato_cidade))) as contato_cidade
				FROM tbl_posto_fabrica
				JOIN tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				AND tbl_posto_fabrica.contato_estado = '$uf'
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND tbl_posto_fabrica.posto <> 6359
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE ORDER BY 1 ASC";
	}

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		echo "<option value=''></option>\n";
		while ($data = pg_fetch_object($res)) {
			echo "<option value='$data->contato_cidade'>".ucwords(strtolower(retira_acentos($data->contato_cidade)))."</option>";
		}
	}else{
		echo "<option value='' >Nenhum Posto Autorizado localizado para este estado</option>";
	}

	exit;

}

/* Busca os Postos Autorizados */
if (isset($_POST['linha']) && isset($_POST['estado']) && isset($_POST['cidade'])) {

	$linha   = $_POST['linha'];
	$uf      = $_POST['estado'];
	$cidade  = $_POST['cidade'];
	$fabrica = $_POST['fabrica'];

	if($cidade != "sem cidade"){
		$cond_cidade .= " AND UPPER(to_ascii(tbl_posto_fabrica.contato_cidade, 'LATIN9')) = UPPER(to_ascii('$cidade', 'LATIN9')) ";
	}
	if ($fabrica == 74){
		$cond_atlas = "	JOIN tbl_posto_fabrica_ibge on tbl_posto.posto =  tbl_posto_fabrica_ibge.posto
						JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
						JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo = tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo AND tbl_posto_fabrica_ibge_tipo.fabrica = {$fabrica}
						";
		$cond_uf = " UPPER(to_ascii(tbl_cidade.nome, 'LATIN9')) = UPPER(to_ascii('$cidade', 'LATIN9')) ";
	} else {
		$cond_uf = " tbl_posto_fabrica.contato_estado = '$uf' ";
	}

	$sql ="	SELECT
				tbl_posto.posto ,
				tbl_posto.nome ,
				tbl_posto_fabrica.nome_fantasia ,
				tbl_posto_fabrica.contato_cep AS cep ,
				tbl_posto_fabrica.latitude AS lat ,
				tbl_posto_fabrica.longitude AS lng ,
				tbl_posto_fabrica.contato_fone_comercial AS telefone ,
				tbl_posto_fabrica.contato_email AS email ,
				tbl_posto_fabrica.contato_endereco AS endereco ,
				tbl_posto_fabrica.contato_numero AS numero ,
				tbl_posto_fabrica.contato_cidade AS cidade ,
				tbl_posto_fabrica.contato_bairro AS bairro
				{$campos_atlas}
			FROM tbl_posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
			INNER JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = {$linha}
			{$cond_atlas}
			WHERE $cond_uf
			$cond_cidade
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_posto_fabrica.posto <> 6359
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			ORDER BY tbl_posto.cidade
	";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$cor = "";
		$i = 0;

		while ($data = pg_fetch_object($res)) {
			/* Mascara CEP */
			$cep = maskCep($data->cep);

			/* Mascara Telefone */
			$telefone = maskFone($data->telefone);

			if (strlen(trim($data->nome_fantasia)) > 0 && $data->nome_fantasia != "null") {
				$nome_fantasia = strtoupper(retira_acentos($data->nome_fantasia));
				$nome = $data->nome."<br />";
			}else{
				$nome_fantasia = strtoupper(retira_acentos($data->nome));
				$nome = "";
			}

			$nome = preg_replace('/(\d{11})/', '', $nome);

			$cor = ($i%2 == 0) ? "#EEF" : "#FFF";

			echo "
				<div class='row row-posto' data-lat='{$data->lat}' data-lng='{$data->lng}'>
					<div class='col-md-12'>
						<p style='border-bottom: 1px solid #CCCCCC; padding-bottom: 20px;'>
							<br />
							<strong>$nome_fantasia</strong> <br />
							$nome
							$data->endereco, $data->numero  &nbsp; / &nbsp; CEP: $cep <br />
							BAIRRO: $data->bairro &nbsp; / &nbsp; $data->cidade - $uf <br />
							$telefone &nbsp; / &nbsp; ".strtolower($data->email)." <br />";

							if ($fabrica == 74) {
								$sql_cidade_bairro = "SELECT 
														tbl_cidade.nome AS cidade,
														tbl_posto_fabrica_ibge.bairro AS bairro_atende
													  FROM tbl_posto_fabrica_ibge
													  INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
													  WHERE tbl_posto_fabrica_ibge.posto = $data->posto
													  AND tbl_posto_fabrica_ibge.fabrica = $fabrica
													  ORDER BY 
													  	tbl_cidade.nome, 
													  	tbl_posto_fabrica_ibge.bairro";
								$res_cidade_bairro = pg_query($con, $sql_cidade_bairro);

								if (pg_num_rows($res_cidade_bairro) > 0) {
									echo  "<br /> <span style='color: #ff0000;'>CIDADES E BAIRROS QUE O POSTO ATENDE</span>";
									echo  "<table cellpadding='10' style='border: 1px solid #CCCCCC; border-radius: 5px !important; margin-top: 10px !important;'>
															<thead style='background-color: #e9e9e9;'>
																<th style='font-size: 13px; width: 100px;'>CIDADE</th>
																<th style='font-size: 13px;'>BAIRRO(S)</th>
															</thead>
															<tbody>";

									for ($i = 0; $i < pg_num_rows($res_cidade_bairro); $i++) {
										$cidade 		= pg_fetch_result($res_cidade_bairro, $i, 'cidade');
										$bairro_atende 	= json_decode(pg_fetch_result($res_cidade_bairro, $i, 'bairro_atende'),true);

										if ($cidade2 != $cidade) {
											echo  "<tr><td align='center'>".strtoupper(retira_acentos($cidade))."</td><td>".implode(', ',$bairro_atende) . "</td></tr>";
										}

										$cidade2 = $cidade;
									}

									echo  "</tbody>
										</table>";
								}
							}
			echo "
							<button type='button' class='btn btn-default' onclick=\"localizarMap('".$data->lat."', '".$data->lng."')\" style='margin-top: 10px;'><i class='glyphicon glyphicon-search'></i> Localizar</button>
						</p>
					</div>
				</div>";

			if (strlen(trim($data->nome_fantasia)) > 0 && $data->nome_fantasia != "null") {
				$nome_fantasia = strtoupper(retira_acentos($data->nome_fantasia));
			} else {
				$nome_fantasia = strtoupper(retira_acentos($data->nome));
			}

			$lat_lng[] = array(
							"nome_fantasia" => utf8_encode($nome_fantasia),
							"latitude" => $data->lat,
							"longitude" => $data->lng
						);

			$i++;

		}

		$lat_lng = json_encode($lat_lng);

		echo "*".$lat_lng;
	} else {
		echo "<div class='alert alert-danger text-center' role='alert' style='margin-top: 40px;'><strong>Nenhum Posto Autorizado localizado para este estado!</strong></div>*";
	}

	exit;
}

// Preparando variáveis para parametrização do HTML/CSS/JS
$titulo_mapa_rede = 'Assistência Técnica';

if (!in_array($cod_fabrica, array(125, 131, 152)) and empty($brand)) {
	$titulo_mapa_rede .= ' - ' . $nome_fabrica;
}

switch ($cod_fabrica) {
	case 74:
		$nome_fabrica = 'Atlas Fogões';
		break;

	case 122:
		$nome_fabrica = 'Würth';
		break;

	case 126:
		$body_css = 'background-color: transparent !important; color: #fff !important;';
		$style_container_titulo = "style='background-color: transparent !important; border-bottom: 0px solid black; color: #E27812;'";
		break;

	case 131:
		$style_container_titulo = "style='background-color: #FFCC00; border-bottom: 1px solid black; color: black;'";
		break;

	default:
		$style_container_titulo = 'background-color: #f5f5f5; border-bottom: 1px solid #cccccc;';
		break;
}

if ($_GET["xcf"] == 'true')
	$xcf = "-".$_GET['cf'];

$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);
?>
<!DOCTYPE html>
<html lang='en'>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?=$titulo_mapa_rede?></title>
		<link rel="stylesheet/less" type="text/css" media="screen,projection" href="cssmap_brazil_v4_4/cssmap-brazil/cssmap-brasil.less" />
		<script src="cssmap_brazil_v4_4/cssmap-brazil/less-1.3.0.min.js"></script>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap3/css/bootstrap.min.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap3/css/bootstrap-theme.min.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="fancyselect/fancySelect.css" />
		<link type="text/css" rel="stylesheet" href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" />

		<!--[if lt IE 10]>
		<link rel="stylesheet" type="text/css" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" />
		<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
		<![endif]-->

		<script type="text/javascript" src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<!-- <script src="https://raw.github.com/jamietre/ImageMapster/e08cd7ec24ffa9e6cbe628a98e8f14cac226a258/dist/jquery.imagemapster.js"></script> -->

		<!-- Google maps -->
		<!--<script type="text/javascript" src="http://www.google.com/jsapi?fake=.js"></script>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&libraries=weather&amp;language=pt-BR&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>
		<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/routeboxer/src/RouteBoxer.js"></script> -->
		<script type="text/javascript" src="cssmap_brazil_v4_4/jquery.cssmap.js"></script>
		<script type="text/javascript" src="fancyselect/fancySelect.js"></script>


		<!-- plugin para o MapTC -->
		<link href="../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
		<script src="../plugins/leaflet/leaflet.js" ></script>		
		<script src="../plugins/leaflet/map.js" ></script>
		<script src="../plugins/mapbox/geocoder.js"></script>
		<script src="../plugins/mapbox/polyline.js"></script>


		<style type="text/css">
			.titulo{
	    		border-bottom: 1px solid #cccccc;
	    	}

			table {
				margin-top: 40px;
				width: 100%;
			}

			table > thead > tr > td {
				padding: 10px;
				font-size: 12px;
			}

			table > tbody > tr > td {
				padding: 10px;
				border-bottom: 1px solid #CCCCCC;
				font-size: 12px;
			}

			.obrigatorio{
				color: #ff0000;
			}

			.asterisco{
				color: #ff0000;
				position: absolute;
				margin-left: -12px;
				margin-top: 10px;
			}

			.glyphicon{
				top: 2px;
			}

			/* Bootstrap 3 seta o box-sizing para border-box, isso desloca e corta os objetos do mapa */
			.brazil * {box-sizing: content-box!important}

			#map-brazil {
				margin: 0 auto;
			}
			.texto_cidade{
				font-size: 12px;
				font-weight: normal;
				color:#ff0000;
			}
			
			div.fancy-select ul.options {
				z-index: 9999;
			}

		</style>

		<script type="text/javascript">
			var map = null;
			var markers_map = [];

			<?php
			if ($cod_fabrica == 131) {
			?>
				var scroll = 550;
				var scroll_xs = 850;
			<?php
			} else {
			?>
				var scroll = 450;
				var scroll_xs = 750;
			<?php
			}
			?>

			function scrollPostMessage() {
				if ($("div.scroll-xs").is(":visible")) {
					$(window).scrollTop(scroll_xs);
				} else {
					$(window).scrollTop(scroll);
				}

				window.parent.postMessage("scroll", "*");
			}

			var map, markers, router;
			var mapRend = false;
			
			function initialize(markersIni) {
				$("#box_mapa").show();

				if (mapRend == false) {
					map      = new Map("map_canvas");
					map.load();
					markers  = new Markers(map);
					router   = new Router(map);
					mapRend = true;
				}
	
				markers.remove();
				markers.clear();
				
				markersIni.forEach(function(v, k) {
					markers.add(v.latitude,v.longitude,'red');
				});
				
				markers.render();
				markers.focus();
			}

			function addMap(data) {
				var locations = $.parseJSON(data);
				var markers = [];

				$.each(locations, function(key, value) {
					var lat = value.latitude;
					var lng = value.longitude;

					if (lat == null || lng == null) {
						return true;
					}

					markers.push({latitude:lat,longitude:lng});
				});

				initialize(markers);
			}

			function localizarMap(lat, lng) {
					map.setView(lat,lng,15);
					scrollPostMessage();
			}

			function setZoomAllMarkers() {
				scrollPostMessage();
				
				markers.focus();

				/*var markers = [];

				$("div.row-posto").each(function() {
					var lat = $(this).data("lat");
					var lng = $(this).data("lng");

					if (lat == null || lng == null) {
						return true;
					}

					markers.push("markers=color:red%7C"+lat+","+lng);
				});

				initialize(markers);*/
			}
			/* Fim - Google Maps */

			<?php 
			if ($_GET['xcf'] == 'true') {
				if(!empty($brand)) {
					$marca = 'gradiente';
				}else{
					$marca = $cf;
				}
			?>
				$(window).load(function () {
					less.modifyVars({'@map_340':'transparent url(\'br-340<?='-'.$marca?>.png\') no-repeat -970px 0'});
				});
			<?php
			}
			?>

			$('document').ready(function() {
			    $("select").fancySelect();

				$('#linha').blur(function() {
					var id = "linha";
					closeMessageError(id);

					var linha = $("#linha option:selected").text();
					var iframe_linha = $("#iframe_linha").val();

					if(linha != iframe_linha){
						$("div.trigger").removeClass("open");
						$("ul.options").removeClass("open");
						$("#iframe_linha").val(linha);
					}
				});

				$('#linha').focusout(function() {
					$("ul.options").removeClass("open");
				});

				$('#estado').blur(function() {
					var id = "estado";
					closeMessageError(id);

					var estado = $("#estado option:selected").text();
					var iframe_estado = $("#iframe_estado").val();

					if(estado != iframe_estado){
						$("div.trigger").removeClass("open");
						$("ul.options").removeClass("open");
						$("#iframe_estado").val(estado);
					}
				});

				$('#estado').focusout(function() {
					$("ul.options").removeClass("open");
				});

				$('#cidade').blur(function() {
					var id = "cidade";

					if ($('#linha').val() == "" && $('#cidade').val() != "") {
						id = "linha";
						closeMessageError(id);
					} else if ($('#estado').val() == "" && $('#cidade').val() != "") {
						id = "estado";
						closeMessageError(id);
					} else if ($('#cidade').val() != "") {
						closeMessageError(id);
					}

					var cidade = $("#cidade option:selected").text();
					var iframe_cidade = $("#iframe_cidade").val();

					if(cidade != iframe_cidade){
						$("div.trigger").removeClass("open");
						$("ul.options").removeClass("open");
						$("#iframe_cidade").val(cidade);
					}
				});

				$('#cidade').focusout(function() {
					$("ul.options").removeClass("open");
				});

				/* Busca Postos Autorizados */
				$('#btn_acao').click(function() {
                                        $('#box_mapa').hide();
					$('#lista_posto').html("");

					if ($('#linha').val() == "") {
						$('#linha-group').addClass('danger');
						messageError();
						return;
					} else {
						closeMessageError();
					}

					if ($('#estado').val() == "") {
						$('#estado-group').addClass('danger');
						messageError();
						return;
					} else {
						closeMessageError();
					}

					<?php 
					if (!in_array($cod_fabrica, array(11,125, 131, 152))) {
					?>
		    			if ($("#cidade").val() == null || $('#cidade').val() == "") {
		    				$('#cidade-group').addClass('danger');
		    				messageError();
		    				return;
		    			} else {
		    				closeMessageError();
		    			}
		    		<?php 
		    		}
		    		?>

					var linha   = $('#linha').val();
					var estado  = $('#estado').val();
					var cidade  = $('#cidade').val();
					var fabrica = <?=$cod_fabrica;?>;

					if (cidade == "") {
						cidade = "sem cidade";
					}

					$.ajax({
						url: window.location.pathname,
						type: "POST",
						dataType: "JSON",
						async: false,
						data:
						{
							linha 	: linha,
							estado  : estado,
							cidade  : cidade,
							fabrica : fabrica,
							token   : '<?=$token?>'
						},
						beforeSend: function() {
							loading("show");
						},
						complete: function(data) {
							loading("hide");

							data = data.responseText;
							info = data.split("*");
							var dados = info[1];

							if (dados.length > 0) {
								$('#box_mapa').show();
								addMap(dados);
								if (JSON.parse(dados).length < 2)
									$("#show_all").hide();
								else
									$("#show_all").show();
							}

							$('#lista_posto').html(info[0]);
						}
					});
					
					window.parent.postMessage($(document).height()+100, "*");
					scrollPostMessage();
				});

				/* Busca Produtos */
				$('#estado').on('change.fs', function() {
					$('#cidade').find("option").remove();
					$("#cidade").val("");

					var uf 	= $('#estado').val();
					var linha = $('#linha').val();

					$('ul.brazil > li.active-region').removeClass('active-region');

					if (uf) {
						$('ul.brazil li#'+uf).addClass('active-region');
					}

					if (linha != "") {
						var fabrica = <?=$cod_fabrica;?>;

						$.ajax({
							url:      window.location.pathname,
							type:     'POST',
							dataType: "JSON",
							data:      {
								uf:      uf,
								linha:   linha,
								fabrica: fabrica,
								token:   '<?=$token?>'
							},
							complete: function(data) {
								data = data.responseText;
								$('#cidade').append(data).trigger('update.fs');
							}
						});
					}
				});

				/* Busca Produtos */
				$("#linha-group").on('change.fs', function() {
					$("#estado").trigger('change.fs');
				});

				$('#map-brazil').cssMap({
					'size' : 340,
					onClick : function(e) {
						var uf = e[0].id;

						var linha = $('#linha').val();

						if (linha == "") {
							alert('Por favor escolha a Linha de Produto!');
							$('#linha').focus();
						}

						$('#estado').val(uf);
						$('#estado').change();
					},
				});
			});

			/* Loading Imagem */
			function loading(e) {
				if (e == "show") {
					$('#loading').html('<img src="imagens/loading.gif" />');
				}else{
					$('#loading').html('');
				}
			}

			function messageError() {
				$('.alert').show();
			}

			function closeMessageError(e) {
				$('#'+e+'-group').removeClass('danger');
				$('.alert').hide();
			}

			window.onmessage = function(event) {
			    event.source.postMessage($(document).height()+100, event.origin);
			};

			function clearMap() {
			for (i in map._layers) {
				//alert(JSON.stringify(map._layers));
			    if (map._layers[i]._path != undefined) {
				try {
				    map.removeLayer(map._layers[i]);
				}
				catch (e) {
				   alert("problem with " + e + map._layers[i]);
				}
			    }
			}
		    }
		</script>
	</head>

	<body <?=$body_style?> >
		<!-- Titulo -->

		<input type="hidden" id="iframe_linha" value="">
		<input type="hidden" id="iframe_estado" value="">
		<input type="hidden" id="iframe_cidade" value="">

		<div class="container">
			<div class="alert alert-danger col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" role="alert" style="display: none;" >
				<strong>Preencha os campos obrigatórios</strong>
		    </div>
		</div>

		<!-- Corpo -->
		<div class="container">

			<div class='row'>

			    <div class='col-xs-12 col-sm-6 col-md-6 col-lg-6'>
			    	<div id="map-brazil">
						<ul class="brazil">
							<li id="AC" class="br1"><a href="#acre">Acre</a></li>
							<li id="AL" class="br2"><a href="#alagoas">Alagoas</a></li>
							<li id="AP" class="br3"><a href="#amapa">Amapá</a></li>
							<li id="AM" class="br4"><a href="#amazonas">Amazonas</a></li>
							<li id="BA" class="br5"><a href="#bahia">Bahia</a></li>
							<li id="CE" class="br6"><a href="#ceara">Ceará</a></li>
							<li id="DF" class="br7"><a href="#distrito-federal">Distrito Federal</a></li>
							<li id="ES" class="br8"><a href="#espirito-santo">Espírito Santo</a></li>
							<li id="GO" class="br9"><a href="#goias">Goiás</a></li>
							<li id="MA" class="br10"><a href="#maranhao">Maranhão</a></li>
							<li id="MT" class="br11"><a href="#mato-grosso">Mato Grosso</a></li>
							<li id="MS" class="br12"><a href="#mato-grosso-do-sul">Mato Grosso do Sul</a></li>
							<li id="MG" class="br13"><a href="#minas-gerais">Minas Gerais</a></li>
							<li id="PA" class="br14"><a href="#para">Pará</a></li>
							<li id="PB" class="br15"><a href="#paraiba">Paraíba</a></li>
							<li id="PR" class="br16"><a href="#parana">Paraná</a></li>
							<li id="PE" class="br17"><a href="#pernambuco">Pernambuco</a></li>
							<li id="PI" class="br18"><a href="#piaui">Piauí</a></li>
							<li id="RJ" class="br19"><a href="#rio-de-janeiro">Rio de Janeiro</a></li>
							<li id="RN" class="br20"><a href="#rio-grande-do-norte">Rio Grande do Norte</a></li>
							<li id="RS" class="br21"><a href="#rio-grande-do-sul">Rio Grande do Sul</a></li>
							<li id="RO" class="br22"><a href="#rondonia">Rondônia</a></li>
							<li id="RR" class="br23"><a href="#roraima">Roraima</a></li>
							<li id="SC" class="br24"><a href="#santa-catarina">Santa Catarina</a></li>
							<li id="SP" class="br25"><a href="#sao-paulo">São Paulo</a></li>
							<li id="SE" class="br26"><a href="#sergipe">Sergipe</a></li>
							<li id="TO" class="br27"><a href="#tocantins">Tocantins</a></li>
						</ul>
			    	</div>
			    </div>

			    <div class='col-xs-12 col-sm-6 col-md-4 col-lg-4'>
					<br />

			    	<span class="obrigatorio">* Campos obrigatórios</span>

			    	<br /><br />

			    	<div class="form-group" id="linha-group">
						<div class="controls controls-row">
							<label class="control-label" for="linha">Linha</label>
							<div class="asterisco">*</div>	
							<select name="linha" id="linha" autofocus required>
								<? if ($cod_fabrica != 152)  { ?> 
									<option value=""></option>
								<?
								}
								
								if ($cod_fabrica == 152) {
									$order = " order by tbl_linha.linha ";
								}else{
									$order = " order by tbl_linha.nome "; 
								}

								if(!empty($brand)) {
									$cond = " and tbl_linha.linha = 203 ";
								}
					
		                        $sql = "SELECT DISTINCT
		                                    tbl_linha.nome,
		                                    tbl_linha.linha
		                                FROM tbl_linha
										WHERE tbl_linha.fabrica = $cod_fabrica
										AND tbl_linha.ativo IS TRUE
										$cond
		                                $order";
								$res = pg_query($con, $sql);
								$rows = pg_num_rows($res);

								for ($i = 0; $i < $rows; $i++) {
									$linha = pg_fetch_result($res, $i, 'linha');
									$nome  = ucwords(strtolower(pg_fetch_result($res, $i, "nome")));
									$refs  = array();


									if(!empty($brand)) {
										$nome = "Áudio";
									}


		                            echo "<option value='{$linha}'>{$nome} ".$linhas."</option>";
		                        }
			                    ?>
							</select>
						</div>
					</div>

					<div class="form-group" id="estado-group">
						<label class="control-label" for="linha">Estado</label>
						<div class="asterisco">*</div>
						<div class="controls controls-row">
							<select name="estado" id="estado" >
								<option value=""></option>
								<option value='AC'>Acre</option>
								<option value='AL'>Alagoas</option>
								<option value='AM'>Amazonas</option>
								<option value='AP'>Amapá</option>
								<option value='BA'>Bahia</option>
								<option value='CE'>Ceará</option>
								<option value='DF'>Distrito Federal</option>
								<option value='ES'>Espírito Santo</option>
								<option value='GO'>Goiás</option>
								<option value='MA'>Maranhão</option>
								<option value='MG'>Minas Gerais</option>
								<option value='MS'>Mato Grosso do Sul</option>
								<option value='MT'>Mato Grosso</option>
								<option value='PA'>Pará</option>
								<option value='PB'>Paraíba</option>
								<option value='PE'>Pernambuco</option>
								<option value='PI'>Piauí</option>
								<option value='PR'>Paraná</option>
								<option value='RJ'>Rio de Janeiro</option>
								<option value='RN'>Rio Grande do Norte</option>
								<option value='RO'>Rondônia</option>
								<option value='RR'>Roraima</option>
								<option value='RS'>Rio Grande do Sul</option>
								<option value='SC'>Santa Catarina</option>
								<option value='SE'>Sergipe</option>
								<option value='SP'>São Paulo</option>
								<option value='TO'>Tocantins</option>
							</select>
						</div>
					</div>

					<div class="form-group" id="cidade-group">
						<label class="control-label" for="linha">Cidade <span class='texto_cidade'>(Se sua cidade não aparecer na lista abaixo deixe o campo em branco e clique em pesquisar)</span></label>
						<?php
						if (!in_array($cod_fabrica, array(11,125, 131, 152))) {
						?>
							<div class="asterisco">*</div>
						<?php
						}
						?>
						<div class="controls controls-row">
							<select name="cidade" id="cidade" >
								<option value=""></option>
							</select>
						</div>
					</div>

					<button class="btn btn-default" id="btn_acao" type="button">Pesquisar</button> &nbsp; <span id="loading"></span>
			    </div>
			</div>
		</div>

		<div style="clear: both;"></div>

		<div id="box_mapa" class="col-xs-12 col-sm-10 col-sm-offset-1  col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" style="display: none; text-align: center;" >
			<div id="map_canvas" style="height: 450px; margin-top: 50px; border: 1px solid #CCCCCC;"></div>
			<div class="text-right">
				<br />
				<button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
			</div>
		</div>

		<div style="clear: both;"></div>

		<div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id="lista_posto" style="padding-bottom: 100px;"></div>

		<div class="scroll-xs visible-xs-block" ></div>
	</body>
</html>
