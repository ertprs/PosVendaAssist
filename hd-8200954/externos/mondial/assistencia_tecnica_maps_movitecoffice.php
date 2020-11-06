<?php
require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
require_once('../../admin/funcoes.php');

use Posvenda\TcMaps;

function formatCEP($cepString){
    $cepString = str_replace("-", "", $cepString);
    $cepString = str_replace(".", "", $cepString);
    $cepString = str_replace(",", "", $cepString);
    $antes = substr($cepString, 0, 5);
    $depois = substr($cepString, 5);
    $cepString = $antes."-".$depois;
    return $cepString;
}

function getLatLonConsumidor($logradouro = null, $bairro = null, $cidade, $estado, $pais = "BR" , $cep = null){
	global $con, $fabrica;
	$oTcMaps = new TcMaps($fabrica, $con);

	try{
		$retorno = $oTcMaps->geocode($logradouro, null, $bairro, $cidade, $estado, $pais, $cep);
		return $retorno['latitude']."@".$retorno['longitude'];
	}catch(Exception $e){
		return false;
	}
}

        $token        = trim($_GET['tk']);
	$token_post   = $_POST['token'];
	$cod_fabrica  = $_GET['cf'];
	$cod_fabrica  = base64_decode(trim($cod_fabrica));

	$nome_fabrica = $_GET['nf'];
	$nome_fabrica = base64_decode(trim($nome_fabrica));

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

		$inicio = substr($cep, 0, 2);
		$meio   = substr($cep, 2, 3);
		$fim    = substr($cep, 5, strlen($cep));
		$cep    = $inicio.".".$meio."-".$fim;
		return $cep;
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

		$sqlLinha = "SELECT linha from tbl_linha where descricao_site = '$linha' and fabrica = $fabrica";
		$resLinha = pg_query($con, $sqlLinha);
		$linhas = pg_fetch_all($resLinha); 

		foreach($linhas as $lin){
			$idLinha[] = $lin['linha'];
		}

		$sql = "SELECT DISTINCT UPPER(TRIM(fn_retira_especiais(PF.contato_cidade))) AS contato_cidade
				  FROM tbl_posto_fabrica AS PF
				  JOIN tbl_posto_linha   AS PL ON PL.posto = PF.posto AND PL.linha in(".implode(",", $idLinha).")				 WHERE PF.fabrica        = $fabrica
				   AND PF.contato_estado = '$uf'
				   AND PF.credenciamento = 'CREDENCIADO'
				   AND PF.posto <> 6359
				   AND PF.divulgar_consumidor IS TRUE ORDER BY 1 ASC";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			echo "<option value=''>Todas</option>\n";
			while ($data = pg_fetch_object($res)) {
				echo "\t<option value='$data->contato_cidade'>".ucwords(mb_strtolower(retira_acentos($data->contato_cidade)))."</option>\n";
			}
		}else{
			echo json_encode(array('retorno'=>false,'mensagem'=>'Nenhum Posto Autorizado localizado para este estado'));
			//echo "<option value='' >Nenhum Posto Autorizado localizado para este estado</option>";
		}

		exit;

	}

	/* Busca os Postos Autorizados */
	if (isset($_POST['linha']) && (isset($_POST['estado']) && isset($_POST['cidade']) || isset($_POST['cep_cliente']))) {

        $linha      = $_POST['linha'];
        $cepCliente = $_POST['cep_cliente'];
        $endCliente = $_POST['end_cliente'];
        $uf         = $_POST['estado'];
        $cidade     = $_POST['cidade'];
        $fabrica    = $_POST['fabrica'];

	if (!empty($cepCliente)) {
        	$local = (strlen($cepCliente) > 0) ? formatCEP($cepCliente) : "";

		list($endereco, $bairro, $cidade, $estado) = explode(":", $endCliente);
        	$latLonConsumidor = getLatLonConsumidor($endereco, $bairro, $cidade, $estado,$pais, $cepCliente);
        	$parte = explode('@', $latLonConsumidor);

        	$from_lat = $parte[0];
        	$from_lon = $parte[1];

		$coluna_distancia = ", (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distancia";
		$cond_distancia = "AND (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) < 100";
		$order_by = "distancia ASC";
	} else {
		if ($cidade != "sem cidade") {
			$cond_cidade = "AND UPPER(TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN9')) = UPPER(TO_ASCII('$cidade', 'LATIN9'))";
		}
		
		$cond_estado = "AND tbl_posto_fabrica.contato_estado = '$uf'";
		$order_by = "tbl_posto_fabrica.contato_cidade ASC ";
	}


	$sqlLinha = "SELECT linha from tbl_linha where descricao_site = '$linha' and fabrica = $fabrica";
	$resLinha = pg_query($con, $sqlLinha);
	$linhas = pg_fetch_all($resLinha); 
	foreach($linhas as $lin){
		$idLinha[] = $lin['linha'];
	}

		$sql ="
            SELECT  DISTINCT 
            		tbl_posto.posto ,
                    tbl_posto.nome ,
                    tbl_posto_fabrica.nome_fantasia ,
                    tbl_posto_fabrica.contato_cep AS cep ,
                    tbl_posto_fabrica.latitude AS lat ,
                    tbl_posto_fabrica.longitude AS lng ,
                    tbl_posto_fabrica.contato_fone_comercial AS telefone ,
		            tbl_posto_fabrica.contato_fone_residencial AS fone2,
		            tbl_posto_fabrica.contato_telefones AS fones,
                    tbl_posto_fabrica.contato_email AS email ,
                    tbl_posto_fabrica.contato_endereco AS endereco ,
                    tbl_posto_fabrica.contato_numero AS numero ,
                    tbl_posto_fabrica.contato_cidade AS cidade ,
                    tbl_posto_fabrica.contato_estado AS uf ,
                    tbl_posto_fabrica.contato_complemento AS complemento ,
		    tbl_posto_fabrica.contato_bairro AS bairro,
		    tbl_posto_fabrica.contato_telefones[1] AS telefones
			$coluna_distancia
            FROM    tbl_posto
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica   = {$fabrica}
            JOIN    tbl_posto_linha     ON  tbl_posto.posto             = tbl_posto_linha.posto
                                        AND tbl_posto_linha.linha       in (".implode(",", $idLinha).")
            WHERE   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
	    $cond_estado
            $cond_cidade
	    $cond_distancia
            AND     tbl_posto_fabrica.posto <> 6359
            AND     tbl_posto_fabrica.divulgar_consumidor IS TRUE
      		ORDER BY      $order_by
        ";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {

			$cor = "";
			$i = 0;

			while ($data = pg_fetch_object($res)) {

				/* Mascara CEP */
				$cep = maskCep($data->cep);
				$telefone = null;
	            $chars_replace = array('{','}','"');
	            $contato_telefones = str_replace($chars_replace, "", $data->fones);

	            $fones = array();
	            $fones = explode(',', $contato_telefones);

	            if(strlen($telefone)==0 and strlen($fones[0])>0 ){
	                $telefone  = $fones[0];
	            }
	            $fone2 = $fones[1];
	            $fone3 = $fones[2];

		        if($telefone == null){
		            $telefone = $data->telefone;
		        }

				/* Mascara Telefone */
				//$telefone = (empty($telefone)) ? $data->telefones : $telefone;
				$nome_fantasia = mb_strtoupper(retira_acentos($data->nome_fantasia));

				$cor = ($i%2 == 0) ? "#EEF" : "#FFF";
								
				if ($telefone) {
					$telefone = maskFone($telefone);
					$stringTelefone = "$telefone &nbsp; ";
				} 
				if ($fone2) {
					$fone2 = maskFone($fone2);
					$stringTelefone .= "/ $fone2 &nbsp;";
				} 
				if ($fone3) {
					$fone3 = maskFone($fone3);
					$stringTelefone .= "/ $fone3 &nbsp; ";
				} 


                echo "
                    <div class='row'>
                        <div class='col-md-12'>
                            <p style='border-bottom: 1px solid #CCCCCC; padding-bottom: 20px;'>
                                <br />
                                <strong>$nome_fantasia</strong> <br />
                                $data->endereco, $data->numero $data->complemento &nbsp; / &nbsp; CEP: $cep <br />
                                BAIRRO: $data->bairro &nbsp; / &nbsp; $data->cidade - $data->uf <br />
                                $stringTelefone / &nbsp; ".mb_strtolower($data->email)." <br />
                                <button type='button' class='btn btn-default' onclick=\"localizarMap('".$data->lat."', '".$data->lng."')\" style='margin-top: 10px;'><i class='glyphicon glyphicon-search'></i> Localizar</button>
                            </p>
                        </div>
                    </div>
                ";
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
		$titulo_mapa_rede .= ' - ' . ' Movitec ';

		$style_container_titulo = 'background-color: #f5f5f5; border-bottom: 1px solid #cccccc;';

	if ($_GET["xcf"] == 'true')
		$xcf = "-".$_GET['cf'];
?>
<!DOCTYPE html>
<html lang='en'>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<base href='../' /> <? // seta o 'diretório raíz' para todos os links HTML ?>
		<title><?=$titulo_mapa_rede?></title>
		<link rel="stylesheet/less" type="text/css" media="screen,projection" href="cssmap_brazil_v4_4/cssmap-brazil/cssmap-brasil.less" />
		<script src="cssmap_brazil_v4_4/cssmap-brazil/less-1.3.0.min.js"></script>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap3/css/bootstrap.min.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap3/css/bootstrap-theme.min.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="mondial/select2_mondial.css" />
		<!-- <link type="text/css" rel="stylesheet" href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" /> -->

		<!--[if lt IE 10]>
		<link rel="stylesheet" type="text/css" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" />
		<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
		<![endif]-->

		<script type="text/javascript" src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="https://raw.github.com/jamietre/ImageMapster/e08cd7ec24ffa9e6cbe628a98e8f14cac226a258/dist/jquery.imagemapster.js"></script>

		<!-- Google maps -->
		<!-- <script type="text/javascript" src="http://www.google.com/jsapi?fake=.js"></script>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&libraries=weather&amp;language=pt-BR"></script>
		<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/routeboxer/src/RouteBoxer.js"></script> -->
		<script type="text/javascript" src="cssmap_brazil_v4_4/jquery.cssmap.js"></script>
		<script type="text/javascript" src="../plugins/select2/select2.js"></script>
		<script type="text/javascript" src="../plugins/jquery.maskedinput_new.js"></script>
        <!-- MAPBOX -->
		<link href="../plugins/leaflet/leaflet.css?<?=date('s');?>" rel="stylesheet" type="text/css" />
		<script src="../plugins/leaflet/leaflet.js?<?=date('s');?>" ></script>    
		<script src="../plugins/leaflet/map.js?<?=date('s');?>" ></script>
        <script src="../plugins/mapbox/geocoder.js"></script>
        <script src="../plugins/mapbox/polyline.js"></script>

		<style type="text/css">
			html, body {
				color: #888;
			}

			.titulo {
				padding: 10px;
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

			.botao{
				background-color: #15a0a9;
				border: 1px solid #15a0a9;
				padding: 17px 25px;
				text-align: center;
				background-image: none;
				font-family: verdana;
				color: #ffffff;
				letter-spacing: 1px;
				font-weight: bold; 				
			}

			.botao:hover{
				background-color: #15a0a9;
				letter-spacing: 1px;
				color: #000;
				border: 1px solid #15a0a9;
			}

			.control-label{
				color: #888;
				font-weight: normal;
			}

			.obrigatorio{
				color: #15a0a9;
			}

			.asterisco{
				color: #ff0000;
				position: absolute;
				z-index: 1000;
				margin-left: -12px;
				margin-top: 10px;
			}

			.container {
				width: 750px;
			}

			.glyphicon{
				top: 2px;
			}

			/* Bootstrap 3 seta o box-sizing para border-box, isso desloca e corta os objetos do mapa */
			.brazil * {box-sizing: content-box!important}

			input[type=text] {
				background-color: #fff;
				border: 1px solid #15a0a9;
				border-radius: 3px;
				height: 44px;
				padding-left: 15px;
			}
			select, .select2-hidden-accessible, .select2-selection--single {
				background-color: #fff !important;
				border: 1px solid #15a0a9 !important;
				border-radius: 3px !important;
				height: 44px !important;
				padding-left: 15px !important;	
			}

			.titulo_geral{
				font-weight: bold;
				color: #15a0a9;
				font-size: 14px;
			}
			.cor{
				color: #888;
			}
		</style>

<script type="text/javascript">
            /* INICIO - MAPBOX */
            var Map, Router, Geocoder, Markers;

            function addMap(data) {
                var locations = $.parseJSON(data);

                if (typeof Map !== 'object') {
                    Map      = new Map("map_canvas");
                    Router   = new Router(Map);
                    Geocoder = new Geocoder();
                    Markers = new Markers(Map);

                    Map.load();
                }

                Markers.remove();
                Markers.clear();
                $.each(locations, function(key, value) {
                    var lat = value.latitude;
                    var lng = value.longitude;

                    if (lat == null || lng == null) {
                        return true;
                    }

                    Markers.add(lat, lng, "red", value.nome_fantasia);
                });
                Markers.render();
                Markers.focus();
            }

            function localizarMap(lat, lng) {
                Map.setView(lat, lng, 15);
            }

            function setZoomAllMarkers(){
                Markers.focus();
            }
            /* FIM - MAPBOX */

function busca_cep(cep,method){
    var img = $("<img />", { src: "../imagens/loading_img.gif", css: { width: "30px", height: "30px" } });
    if (typeof method == "undefined" || method.length == 0) {
        method = "webservice";
        $.ajaxSetup({
            timeout: 10000
        });
    } else {
        $.ajaxSetup({
            timeout: 10000
        });
    }

    $.ajax({
        async: true,
        url: "ajax_cep.php",
        type: "GET",
        data: {
            cep: cep,
            method: method
        },
        beforeSend: function() {
            $("#estado").prop("disabled","disabled");
            $("#cidade").prop("disabled","disabled");
            $("#btn_acao").prop("disabled","disabled");
        },
        success: function(data) {
            results = data.split(";");

            if (results[0] != "ok") {
                alert(results[0]);
            } else {
		$("#estado").data("callback", "selectCidade").data("callback-param", results[3]);
		$("#estado").select2("val", results[4]);
                $("#end_cliente").val(results[1]+":"+results[2]+":"+results[3]+":"+results[4]);
            }

            $.ajaxSetup({
                timeout: 0
            });
            $("#btn_acao").prop("disabled","");
        },
        error: function(xhr, status, error) {
            busca_cep(cep, "database");
        }
    });
}

window.selectCidade = function(cidade) {
	$("#cidade").select2("val", cidade);
	$("#estado").data("callback", null).data("callback-param", null);
}


		<?php if ($_GET['xcf'] == 'true'): ?>
			$(window).load(function () {
				less.modifyVars({'@map_340':'transparent url(\'br-340-bebelie.png\') no-repeat -970px 0'});
			});
		<?php endif;?>

			$('document').ready(function() {
                $("#cep_cliente").mask("99999-999");
				$("select").select2();

                $("#cep_cliente").blur(function() {
                    if ($(this).attr("readonly") == undefined && $(this).val() != "") {
                        busca_cep($(this).val(),"");
                    }else{
                        $("#estado").prop("disabled","");
                        $("#cidade").prop("disabled","");
                        $("#end_cliente").val("");
                    }
                });

				$('#linha').blur(function() {
					var id = "linha";
					closeMessageError(id);
				});

				$('#estado').blur(function() {
					var id = "estado";
					closeMessageError(id);
				});

				$('#cidade').blur(function() {

					var id = "cidade";

					if ($('#linha').val() == "" && $('#cidade').val() != "") {
						id = "linha";
						closeMessageError(id);
					}

					else if ($('#estado').val() == "" && $('#cidade').val() != "") {
						id = "estado";
						closeMessageError(id);
					}

					else if ($('#cidade').val() != "") {
						closeMessageError(id);
					}

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

					if ($('#estado').val() == "" && $("cep_cliente") == "") {
						$('#estado-group').addClass('danger');
						messageError();
						return;
					} else {
						closeMessageError();
					}

// 					if (($("#cidade").val() == null || $('#cidade').val() == "")) {
// 						$('#cidade-group').addClass('danger');
// 						messageError();
// 						return;
// 					}

                    var linha       = $('#linha').val();
                    var cep_cliente = $('#cep_cliente').val();
			if (cep_cliente.length > 0) {
                    		var end_cliente = $('#end_cliente').val();
			} else {
				var end_cliente = "";
			}
                    var estado      = $('#estado').val();
                    var cidade      = $('#cidade').val();
                    var fabrica     = <?=$cod_fabrica;?>;

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
							linha        : linha,
							estado       : estado,
							cep_cliente  : cep_cliente,
							end_cliente  : end_cliente,
							cidade       : cidade,
							fabrica      : fabrica,
							token        : '<?=$token?>'
						},
						beforeSend: function() {
							loading("show");
						},
						complete: function(data) {
							loading("hide");

							/* $('#linha').val('');
							$('#estado').val('');
							$('#cidade').val(''); */

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
					window.parent.postMessage("scroll", "*");

				});

				/* Busca Produtos */
				$('#estado').on('change', function() {
					var callback = $(this).data("callback");
					var callbackParam = $(this).data("callback-param");

					$('#cidade').find("option").remove();
					$("#cidade").val("");
					$("#div_erro").hide();
					$("#div_erro").text('');

					var uf 	= $('#estado').val();
					var linha = $('#linha').val();

					if(uf != ""){
						$('#cidade').removeAttr('disabled');
					}

					$('ul.brazil > li').removeClass('active-region');
					$('ul.brazil li[id="'+uf+'"]').addClass('active-region');

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
								$('#cidade').append(data);

								if (callback && typeof window[callback] == "function") {
									window[callback](callbackParam);
                                       				} else {
									var dados = $.parseJSON(data); 
                                    if(dados.retorno == false){
                                		$("#div_erro").show(); 
                                		$("#div_erro").text(dados.mensagem);
                                	}

									$("#cidade").select2("val", "");
								}
							}
						});
					}
				});

				/* Busca Produtos */
				$("#linha").on('change', function() {
					//$("#estado").trigger('change');
					$('#cep_cliente').removeAttr('disabled');
					$('#estado').removeAttr('disabled');
					$("#estado").select2("val", "");
					$("#cidade").select2("val", "");
					$("#cidade").attr("disabled", true);
				});

				$('#map-brazil').cssMap({
					'size' : 340,
					onClick : function(e) {

						var uf = e[0].id;

						var linha = $('#linha').val();

						if (linha == "") {
							alert('Por favor escolha a Linha!');
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
		</script>
	</head>

	<body>
		<!-- Titulo -->
		<div class="container-fluid">

			<div class='row'>
			    <div class='col-md-2'></div>
			    <div class='col-md-8 col-sm-12 col-xs-12 text-center titulo titulo_geral'>
			    	Selecione as opções abaixo para encontrar a autorizada Movitec mais perto de você.
			    </div>
			    <div class='col-md-2'></div>
			</div>

		</div>


		<?php if ($cod_fabrica == 131) { ?>
		<br />

		<div class="container-fluid">
			<div class='row'>
				<div class='col-md-2'></div>
				<div class='col-md-8 tac'>
					<img src="../logos/logo_pressure.png" style="border: 0px; max-height: 70px; max-width: 270px;" />
				</div>
				<div class='col-md-2'></div>
			</div>
		</div>

		<br />
		<?php } ?>

		<div class="container-fluid">
			<div class="alert alert-danger col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" role="alert" style="display: none;" >
				<strong>Preencha os campos obrigatórios</strong>
		    </div>
		</div>

		<!-- Corpo -->
		<div class="container-fluid">

			<div class='row'>

			    <div class='col-xs-12 col-sm-6 col-md-6 col-lg-6'>
			    	<div id="map-brazil">
						<ul class="brazil cor">
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

			    <div class='col-xs-12 col-sm-5 col-md-4 col-lg-4'>
					<br />

			    	<span class="obrigatorio">* Campos obrigatórios</span>

			    	<br /><br />

			    	<div class="form-group" id="linha-group">
						<label class="control-label cor" for="linha">Linha</label>
						<div class="asterisco">*</div>	
						<div class="controls controls-row">
							<select name="linha" id="linha" autofocus class="col-md-11" required>
								<option value=""></option>
								<?
			                        $sql = "SELECT DISTINCT
												   tbl_linha.descricao_site as nome
											  FROM tbl_linha
											 WHERE tbl_linha.fabrica = $cod_fabrica
											   AND tbl_linha.ativo IS TRUE
												AND tbl_linha.linha in (1447)
											 ORDER BY tbl_linha.descricao_site";
									$res = pg_query($con,$sql);
									//if (pg_num_rows($res) > 1) { echo "<option></option>" ;}
									for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
										$linha = pg_fetch_result($res,$i,'linha');
										$refs = array();
										echo "<option value='".pg_result ($res,$i,nome)."'>".mb_strtoupper((pg_result ($res,$i,nome))).$linhas."</option>";
			                        }
			                    ?>
							</select>
						</div>
					</div>
                    <div class='form-group' id="cep_cliente-group">
						<label class="control-label cor" for="cep_cliente">CEP</label>
						<div class="controls controls-row">
							<div class="span12">
								<input id="cep_cliente" name="cep_cliente" class="span12" type="text"  value="" disabled  />
                                <input id="end_cliente" name="end_cliente" type="hidden" value="" />
							</div>
						</div>
					</div>
					<div class="form-group" id="estado-group">
						<label class="control-label cor" for="linha">Estado</label>
						<div class="asterisco">*</div>
						<div class="controls controls-row">
							<select name="estado" id="estado" disabled class="col-md-11">
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
						<label class="control-label cor" for="linha">Cidade</label>
						<div class="asterisco">*</div>
						<div class="controls controls-row">
							<?=$asteristico?>
							<select name="cidade" id="cidade" class="col-md-11" disabled style="width:200px;">
								<option value=""></option>
							</select>
						</div>
					</div>
					<div id="div_erro"></div>
					<br>	
					<button class="btn botao" id="btn_acao" type="button">Pesquisar</button> &nbsp; <span id="loading"></span>

			    </div>

			</div>

		</div>

		<div style="clear: both;"></div>

		<div class="col-xs-12 col-sm-10 col-sm-offset-1  col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id="box_mapa" style="display: none;text-align:center">
			<div id="map_canvas" style="height: 450px; margin-top: 50px; border: 1px solid #CCCCCC;"></div>
			<div class="text-right">
				<br />
				<button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os pontos</button>
			</div> 
		</div>
 
		<div style="clear: both;"></div>

		<div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id="lista_posto" style="padding-bottom: 100px;"></div>

		<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-48712605-1', 'auto');
		ga('send', 'pageview');

		</script>

	</body>

</html>

