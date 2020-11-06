<?php
header('Content-Type: text/html; charset=ISO-8859-1');

require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
require_once('../../admin/funcoes.php');

use Posvenda\TcMaps;

$titulo        = "Cuisinart - Fale conosco";
$fabrica       = 187;
$login_fabrica = 187;

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


if(isset($_POST["search_model"])){
	$linha = $_POST["linha"];

	if($linha == "eletroportateis"){
		$return["linha"][] = ["linha" => "batedeiras", "descricao" => utf8_encode("Batedeiras")];
		$return["linha"][] = ["linha" => "cafeteiras", "descricao" => utf8_encode("Cafeteiras")];
		$return["linha"][] = ["linha" => "chaleiras_eletricas", "descricao" => utf8_encode("Chaleiras Elétricas")];
		$return["linha"][] = ["linha" => "climtizadores_de_vinho", "descricao" => utf8_encode("Climtizadores de Vinho")];
		$return["linha"][] = ["linha" => "espremedores_de_citricos", "descricao" => utf8_encode("Espremedores de Cítricos")];
		$return["linha"][] = ["linha" => "facas_eletricas", "descricao" => utf8_encode("Facas Elétricas")];
		$return["linha"][] = ["linha" => "fornos_eletricos_compactos", "descricao" => utf8_encode("Fornos Elétricos Compactos")];
		$return["linha"][] = ["linha" => "fundue", "descricao" => utf8_encode("Fundue")];
		$return["linha"][] = ["linha" => "grill", "descricao" => utf8_encode("Grill")];
		$return["linha"][] = ["linha" => "hand_mixer", "descricao" => utf8_encode("Hand Mixer")];
		$return["linha"][] = ["linha" => "jarras_eletricas", "descricao" => utf8_encode("Jarras Elétricas")];
		$return["linha"][] = ["linha" => "liquidificadores", "descricao" => utf8_encode("Liquidificadores")];
		$return["linha"][] = ["linha" => "maquina_de_sorvete", "descricao" => utf8_encode("Máquina de Sorvete")];
		$return["linha"][] = ["linha" => "maquina_de_waffle", "descricao" => utf8_encode("Máquina de Waffle")];
		$return["linha"][] = ["linha" => "mixer_eletrico", "descricao" => utf8_encode("Mixer elétrico")];
		$return["linha"][] = ["linha" => "moedores_de_cafe", "descricao" => utf8_encode("Moedores de Café")];
		$return["linha"][] = ["linha" => "moedores_eletricos_de_pimentas_e_speciarias", "descricao" => utf8_encode("Moedores Elétricos de Pimentas e Especiarias")];
		$return["linha"][] = ["linha" => "outros", "descricao" => utf8_encode("OUTROS")];
		$return["linha"][] = ["linha" => "panelas_eletricas", "descricao" => utf8_encode("Panelas Elétricas")];
		$return["linha"][] = ["linha" => "panelas_para_fundue", "descricao" => utf8_encode("Panelas para Fundue")];
		$return["linha"][] = ["linha" => "pipoqueiras_eletricas", "descricao" => utf8_encode("Pipoqueiras Elétricas")]; 
		$return["linha"][] = ["linha" => "processodores_de_alimento", "descricao" => utf8_encode("Processodores de Alimento")];
		$return["linha"][] = ["linha" => "ralador_eletrico", "descricao" => utf8_encode("Ralador Elétrico")];
		$return["linha"][] = ["linha" => "saca_rolhas", "descricao" => utf8_encode("Saca-Rolhas")];
		$return["linha"][] = ["linha" => "sanduicheira", "descricao" => utf8_encode("Sanduicheira")];
		$return["linha"][] = ["linha" => "sorveteira", "descricao" => utf8_encode("Sorveteira")];
		$return["linha"][] = ["linha" => "torradeiras", "descricao" => utf8_encode("Torradeiras")];
		$return["linha"][] = ["linha" => "waffl", "descricao" => utf8_encode("Waffl")];

	} elseif($linha == 'utensilios_nao_eletricos'){
		$return["linha"][] = ["linha" => "amoladores", "descricao" => utf8_encode("Amoladores")]; 
		$return["linha"][] = ["linha" => "balanca_culinaria_digital", "descricao" => utf8_encode("Balança Culinária Digital")];
		$return["linha"][] = ["linha" => "caldeirao_com_cesto_para_massa", "descricao" => utf8_encode("Caldeirão com cesto para Massa")];
		$return["linha"][] = ["linha" => "colher_para_pasta", "descricao" => utf8_encode("Colher para Pasta")];
		$return["linha"][] = ["linha" => "concha", "descricao" => utf8_encode("Concha")];
		$return["linha"][] = ["linha" => "conjunto_de_panelas", "descricao" => utf8_encode("Conjunto de Panelas")];
		$return["linha"][] = ["linha" => "coqueteleira", "descricao" => utf8_encode("Coqueteleira")];
		$return["linha"][] = ["linha" => "escumadeira", "descricao" => utf8_encode("Escumadeira")];
		$return["linha"][] = ["linha" => "espatula", "descricao" => utf8_encode("Espátula")];
		$return["linha"][] = ["linha" => "espatula_com_pinca", "descricao" => utf8_encode("Espátula com Pinça")];
		$return["linha"][] = ["linha" => "facas_cutelaria", "descricao" => utf8_encode("Facas - Cutelaria")];
		$return["linha"][] = ["linha" => "outros", "descricao" => utf8_encode("OUTROS")];
		$return["linha"][] = ["linha" => "panelas", "descricao" => utf8_encode("Panelas")];
		$return["linha"][] = ["linha" => "pegador_de_espaguete", "descricao" => utf8_encode("Pegador de Espaguete")];
		$return["linha"][] = ["linha" => "pegador_de_pasta", "descricao" => utf8_encode("Pegador de Pasta")];
		$return["linha"][] = ["linha" => "peneiras", "descricao" => utf8_encode("Peneiras")];
		$return["linha"][] = ["linha" => "processador_de_alimentos_manual", "descricao" => utf8_encode("Processador de Alimentos Manual")];
		$return["linha"][] = ["linha" => "ralador_de_queijo", "descricao" => utf8_encode("Ralador de Queijo")];
		$return["linha"][] = ["linha" => "tesoura_magnetica", "descricao" => utf8_encode("Tesoura Magnética")];
	}
	$return["success"] = true;
	echo json_encode($return);
	exit;
}

if (isset($_POST['search_city'])) {
	$state   = $_POST['state'];
	$familia = $_POST['product'];
	$linha   = $_POST['linha'];

	/*$sql = "SELECT tbl_produto.linha FROM tbl_produto
			JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
		WHERE tbl_produto.fabrica_i = {$login_fabrica} and tbl_produto.linha = {$linha}
			AND tbl_produto.familia = {$familia}
		GROUP BY tbl_produto.linha";
	$res    = pg_query($con, $sql);
	$linhas = pg_fetch_all($res); 

	foreach($linhas as $lin){
		$idLinha[] = $lin['linha'];
	}

	$idLinha = implode(",", $idLinha);*/

	$idLinha = $linha;

	$sql = "SELECT DISTINCT UPPER(TRIM(fn_retira_especiais(tbl_posto_fabrica.contato_cidade))) AS contato_cidade
		FROM tbl_posto_fabrica
		JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha in({$idLinha}) 
		WHERE tbl_posto_fabrica.fabrica = $login_fabrica
		   AND tbl_posto_fabrica.contato_estado = '$state'
		   AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
		   AND tbl_posto_fabrica.posto <> 6359
		   AND tbl_posto_fabrica.divulgar_consumidor IS TRUE 
	   ORDER BY 1 ASC";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		for ($i=0; $i < pg_num_rows($res); $i++) { 
			$return["produto"][] = array(
				"cidade"   => pg_fetch_result($res, $i, "contato_cidade"),
				"contato_cidade" => ucwords(mb_strtolower(retira_acentos(pg_fetch_result($res, $i, "contato_cidade"))))
			);
		}

		$return["success"] = true;
	} else {
		$return["success"] = false;
	}

	echo json_encode($return);
	exit;
}

if(isset($_POST["send_place"])){
	$familia    = $_POST['familia'];
	$cepCliente = $_POST['cepCliente'];
	$endCliente = $_POST['end_cliente'];
	$uf         = $_POST['estado'];
	$cidade     = $_POST['cidade'];
	$fabrica    = $_POST['fabrica'];
	$linha      = $_POST['linha'];

	$coluna_distancia = '';
	$cond_distancia = '';
	$order_by = '';
	$cond_cidade = '';
	$cond_estado = '';

	if (!empty($cepCliente) && $cidade == 'sem cidade') {
    	$local = (strlen($cepCliente) > 0) ? formatCEP($cepCliente) : "";

		list($endereco, $bairro, $cidade, $estado) = explode(":", $endCliente);
		$latLonConsumidor                          = getLatLonConsumidor($endereco, $bairro, $cidade, $estado,$pais, $cepCliente);
		$parte                                     = explode('@', $latLonConsumidor);

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
		$order_by    = "tbl_posto_fabrica.contato_cidade ASC ";
	}

	/*$sql = "SELECT tbl_produto.linha FROM tbl_produto
			JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
		WHERE tbl_produto.fabrica_i = {$login_fabrica}
			AND tbl_produto.familia = {$familia}
		GROUP BY tbl_produto.linha";
	$res    = pg_query($con, $sql);
	$linhas = pg_fetch_all($res); 

	foreach($linhas as $lin){
		$idLinha[] = $lin['linha'];
	}

	$idLinha = implode(",", $idLinha);*/

	$idLinha = $linha;

	$sql = "SELECT  DISTINCT 
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
		    tbl_posto_fabrica.contato_telefones[1] AS telefones $coluna_distancia
        FROM tbl_posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto
                AND tbl_posto_linha.linha in ({$idLinha})
        WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
	    	$cond_estado
            $cond_cidade
	    	$cond_distancia
            AND tbl_posto_fabrica.posto <> 6359
            AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
  		ORDER BY $order_by";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$cor = "";
		$i   = 0;

		while ($data = pg_fetch_object($res)) {
			$cep               = maskCep($data->cep);
			$telefone          = null;
			$chars_replace     = array('{','}','"');
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
				"latitude"      => $data->lat,
				"longitude"     => $data->lng
            );

			$i++;
		}

		$lat_lng = json_encode($lat_lng);

		echo "*".$lat_lng;

	} else {
		echo "<div class='alert alert-danger text-center' role='alert' style='margin-top: 40px;'><strong>Nenhum Posto Autorizado localizado na sua região!</strong></div><br/ ><br/ >
			<p style='text-align: center; margin: 1%;''>
				<input type='button' id='open_callcenter' value='Abrir Atendimento' class='theme-button input-button' style='width: 34%;'>
			</p>
		";
	}
	exit;
}

?>

<link rel="stylesheet" id="wpex-style-css" href="https://cuisinartbrasil.com.br/wp-content/themes/Total/style.css?ver=4.7" type="text/css" media="all">
<link rel="stylesheet" id="wpex-google-font-open-sans-css" href="//fonts.googleapis.com/css?family=Open+Sans:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&amp;subset=latin" type="text/css" media="all">
<link rel="stylesheet" id="wpex-visual-composer-css" href="https://cuisinartbrasil.com.br/wp-content/themes/Total/assets/css/wpex-visual-composer.css?ver=4.7" type="text/css" media="all">
<link rel="stylesheet" id="wpex-visual-composer-extend-css" href="https://cuisinartbrasil.com.br/wp-content/themes/Total/assets/css/wpex-visual-composer-extend.css?ver=4.7" type="text/css" media="all">
<link rel="stylesheet" id="js_composer_front-css" href="https://cuisinartbrasil.com.br/wp-content/plugins/js_composer/assets/css/js_composer.min.css?ver=5.5.1" type="text/css" media="all">
<link rel="stylesheet" id="bsf-Defaults-css" href="https://cuisinartbrasil.com.br/wp-content/uploads/smile_fonts/Defaults/Defaults.css?ver=4.8.3" type="text/css" media="all">
<link rel="stylesheet" id="wpex-responsive-css" href="https://cuisinartbrasil.com.br/wp-content/themes/Total/assets/css/wpex-responsive.css?ver=4.7" type="text/css" media="all">
<link rel="stylesheet" type="text/css" href="../../plugins/shadowbox_lupa/shadowbox.css" media="all"> 

<script type="text/javascript" src="../../admin/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="../../js/jquery.form.js"></script>

<script language="JavaScript" src="../../admin/js/jquery.mask.js"></script>
<script type="text/javascript" src="../../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<script type='text/javascript' src='../../js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='../css/jquery.autocomplete.min.js'></script>

<script type="text/javascript" src="../../plugins/shadowbox_lupa/shadowbox.js"></script>

<!-- MAPBOX -->
<link href="../plugins/leaflet/leaflet.css?<?=date('s');?>" rel="stylesheet" type="text/css" />
<script src="../plugins/leaflet/leaflet.js?<?=date('s');?>" ></script>    
<script src="../plugins/leaflet/map.js?<?=date('s');?>" ></script>
<script src="../plugins/mapbox/geocoder.js"></script>
<script src="../plugins/mapbox/polyline.js"></script>

<style type="text/css">
	.titulo_form {
		font-family: Lato;
		font-size: 26px;
		text-transform: uppercase;
	}

	input[type="submit"]:hover,
	input[type="button"]:hover {
	    background-color: #000 !important;
	}

	input[type="submit"],
	input[type="button"] {
	    background-color: #111 !important;
	    font-size: 16px;
	    font-weight: 700;
	}

	.theme-button:hover,
	input[type="submit"]:hover,
	input[type="button"]:hover,
	button:hover,
	#site-navigation .menu-button > a:hover > span.link-inner {
	    background: #5e8b24;
	}

	.post-edit a:hover,
	.theme-button:hover,
	input[type="submit"]:hover,
	input[type="button"]:hover,
	button:hover,
	.wpex-carousel .owl-prev:hover,
	.wpex-carousel .owl-next:hover, 
	#site-navigation .menu-button > a > span.link-inner:hover {
	    background-color: #83c132;
	}

	.vc_custom_1537146638752 {
	    margin-bottom: 10px !important;
	}

	select {
		display: inline-block;
	    color: #777;
	    padding: 6px 12px;
	    font-family: inherit;
	    font-weight: inherit;
	    font-size: 1em;
	    line-height: 1.65;
	    max-width: 100%;
	    border: 1px solid #eee;
	    background: #f7f7f7;
	    border-radius: 0;
	    -webkit-appearance: none;
	    -moz-appearance: none;
	    appearance: none;
	}

	.input-button {
		margin: 1%;
	}

	.fm_fale_conosco {
		margin-left: 2%;
		margin-right: 2%;
	}

	.msg_city {
		background: #dcd9d9;
		font-style: italic;
		position: absolute;
		z-index: 9;
		margin-top: -3%;
		margin-left: 2%;
		-moz-border-radius: 7px;
		-webkit-border-radius: 7px;
		border-radius: 7px;
		padding-top: 1%;
		padding-left: 1%;
		padding-right: 1%;
		color: black;
	}

	.icon-msg-city {
		position: absolute;
		margin-left: 25%;
		margin-top: -2%;
	}

</style>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<title><?=$titulo?></title>
		</head>
		<body>
			<form name="fm_fale_conosco" class="fm_fale_conosco">
				<div class="vc_row wpb_row vc_row-fluid">
					<div class="wpb_column vc_column_container vc_col-sm-12" style="text-align: center; margin: 1%; padding-left: 25%; padding-right: 25%;">
						<div class="vc_column-inner">
							<div class="wpb_wrapper">
								<h2 class="vcex-module vcex-heading vcex-heading-bottom-border-w-color vc_custom_1537146638752" style="font-family:Lato;font-size:26px;text-transform:uppercase;">
									<span class="vcex-heading-inner clr" style="border-color:#4c4c4c;">Selecione  o seu produto</span>
								</h2>
								<div role="form" class="wpcf7" id="wpcf7-f86-p8-o1" lang="en-US" dir="ltr">
									<div class="screen-reader-response"></div>
									<form action="/contato/#wpcf7-f86-p8-o1" method="post" class="wpcf7-form" novalidate="novalidate">
										<div style="display: none;">
											<input type="hidden" name="_wpcf7" value="86">
											<input type="hidden" name="_wpcf7_version" value="5.0.4">
											<input type="hidden" name="_wpcf7_locale" value="en_US">
											<input type="hidden" name="_wpcf7_unit_tag" value="wpcf7-f86-p8-o1">
											<input type="hidden" name="_wpcf7_container_post" value="8">
										</div>
										<div class="full-width-input">
											<!-- <p>
												<span class="wpcf7-form-control-wrap your-name">
													<input type="text" name="your-name" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Seu Nome">
												</span>
											</p>
											<p>
												<span class="wpcf7-form-control-wrap your-email">
													<input type="email" name="your-email" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Seu E-mail">
												</span>
											</p> -->
											
											<!-- <p>
												<span class="wpcf7-form-control-wrap your-message">
													<textarea name="your-message" cols="40" rows="10" class="wpcf7-form-control wpcf7-textarea" aria-invalid="false" placeholder="Sua dúvida"></textarea>
												</span>
											</p>
											<p>
												<input type="submit" value="Enviar" class="wpcf7-form-control wpcf7-submit">
												<span class="fa fa-refresh fa-spin wpex-wpcf7-loader"></span>
											</p> -->
											<p>
												<span class="wpcf7-form-control-wrap your-product">
													<select id="your-product" name="your-product" class="wpcf7-form-control wpex-select-wrap wpcf7-validates-as-required wpcf7-validates-as-select"  aria-required="true" aria-invalid="false">
														<option value="">Selecione a Linha de Produtos</option>
														<option value="eletrodomesticos">ELETRODOMÉSTICOS</option>
														<option value="eletroportateis">Eletroportáteis</option>
														<option value="utensilios_nao_eletricos">Utensílios não elétricos</option>
													</select>
												</span>
											</p>
											<p>
												<span class="wpcf7-form-control-wrap your-model" >
													<select id="your-model" name="your-model" class="wpcf7-form-control wpex-select-wrap wpcf7-validates-as-required wpcf7-validates-as-select"  aria-required="true" aria-invalid="false" style="display: none">
														<option value="">Selecione seu modelo</option>
													</select>
												</span>
											</p>
											<div id="eletrodomestico" style="display: none">
												<center><span style="font-weight: bold; font-size: 24px; text-decoration-line: underline;">SAC - MCASSAB</span></center>
												<center><span style="font-size: 18px;">Para todo o Brasil</span></center>
												<center><span style="color: green; font-size: 16px;">(11) 3003 - 9030</span></center>
												<center><span style="font-size: 18px;">E-mail</span></center>
												<center><span style="color: green; font-size: 16px;">sac.solucao@mcassab.com.br</span></center>
												<center><span style="font-size: 18px;">Horário de Atentimento</span></center>
												<center><span style="color: green; font-size: 16px;">Segunda à Sexta</span></center>
												<center><span style="color: green; font-size: 16px;">das 9:00 às 17:00 horas</span></center>
												<center><span style="color: red; font-size: 10px;">EXCETO FERIADOS</span></center>
											</div>
										</div>
										<div class="wpcf7-response-output wpcf7-display-none"></div>
									</form>
								</div>
								<div class="vcex-spacing" style="height:20px"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="vc_row wpb_row vc_row-fluid fm_fale_conosco nao_e_mc" id="product-cuisinart" style="display: none;">
					<div class="wpb_column vc_column_container vc_col-sm-12">
						<div class="vc_column-inner">
							<div class="wpb_wrapper">
								<div role="form" class="wpcf7" id="wpcf7-f86-p8-o1" lang="en-US" dir="ltr">
									<div class="screen-reader-response"></div>
									<form action="/contato/#wpcf7-f86-p8-o1" method="post" class="wpcf7-form" novalidate="novalidate">
										<div style="display: none;">
											<input type="hidden" name="_wpcf7" value="86">
											<input type="hidden" name="_wpcf7_version" value="5.0.4">
											<input type="hidden" name="_wpcf7_locale" value="en_US">
											<input type="hidden" name="_wpcf7_unit_tag" value="wpcf7-f86-p8-o1">
											<input type="hidden" name="_wpcf7_container_post" value="8">
										</div>
										<div class="full-width-input" style="margin-top: 3%;">
											<p style="text-align: center; margin: 1%;">
												<input type="button" id="search_place" value="Buscar Posto Autorizado" class="theme-button input-button">
											</p>
											<!-- <p style="text-align: center; margin: 1%;">
												<input type="button" id="open_callcenter" value="Abrir Atendimento" class="theme-button input-button" style="width: 34%;">
											</p> -->
										</div>
										<div class="wpcf7-response-output wpcf7-display-none"></div>
									</form>
								</div>
								<div class="vcex-spacing" style="height:20px"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="vc_row wpb_row vc_row-fluid fm_fale_conosco nao_e_mc" id="search-place" style="display: none; margin-top: 1%;">
					<div class="container-fluid" style="text-align: center;">
						<div id="msg_ob" class="alert alert-danger col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" role="alert" style="display: none; width: 50%; background: #ff0000; text-align: center; margin: 0 auto;" >
							<strong style="color: #ffffff;">Preencha os campos obrigatórios</strong>
					    </div>
					    <div id="menssage" class="alert alert-danger" style="display: none; text-align: center;"></div>
					</div>

					<div class="wpb_column vc_column_container vc_col-sm-12">
						<div class="vc_column-inner" style="margin-top: 3%; margin: 0 auto; text-align: center; width: 50%;">
							<div class="wpb_wrapper">
								<h2 class="vcex-module vcex-heading vcex-heading-bottom-border-w-color vc_custom_1537146920735" style="font-family:Lato;font-size:26px;text-transform:uppercase;">
									<span class="vcex-heading-inner clr" style="border-color:#4c4c4c;">Mapa de Posto Autorizado</span>
								</h2>
								<div role="form" class="wpcf7" id="wpcf7-f86-p8-o1" lang="en-US" dir="ltr">
									<div class="screen-reader-response"></div>
									<form action="/contato/#wpcf7-f86-p8-o1" method="post" class="wpcf7-form" novalidate="novalidate">
										<div style="display: none;">
											<input type="hidden" name="_wpcf7" value="86">
											<input type="hidden" name="_wpcf7_version" value="5.0.4">
											<input type="hidden" name="_wpcf7_locale" value="en_US">
											<input type="hidden" name="_wpcf7_unit_tag" value="wpcf7-f86-p8-o1">
											<input type="hidden" name="_wpcf7_container_post" value="8">
										</div>
										<div class="full-width-input">
											<p>
												<span class="wpcf7-form-control-wrap your-cep">
													<input type="text" name="your-cep" id="your-cep" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Seu CEP">
			                    					<input type="hidden" id="end_cliente" name="end_cliente" value="" />
												</span>
											</p>
											<p>
												<span class="wpcf7-form-control-wrap your-linha">
													<select id="your-linha" name="your-linha" class="wpcf7-form-control wpex-select-wrap wpcf7-validates-as-required wpcf7-validates-as-select"  aria-required="true" aria-invalid="false">
														<option value="">Selecione a linha</option>
														<?php 
														$sql = "SELECT linha,nome FROM tbl_linha WHERE fabrica = $fabrica";
														$res = pg_query($con, $sql);
														if(pg_num_rows($res) > 0){
															for($i =0; $i < pg_num_rows($res); $i++){
																echo '<option value='.pg_fetch_result($res, $i, linha).'>'.pg_fetch_result($res, $i, nome).'</option>';
															}
														}
														?>
													</select>
												</span>
											</p>
											<p>
												<select id="your-state" name="your-state" class="wpcf7-form-control wpex-select-wrap wpcf7-validates-as-required wpcf7-validates-as-select"  aria-required="true" aria-invalid="false">
													<option value="">Selecione seu Estado</option>
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
											</p>
											<p>
												<div class="msg_city" style="display: none;">
													<p>Somente serão exibidas cidades que possuem Postos Autorizados que atendam a linha do produto informado.</p>
												</div>
												<select id="your-city" name="your-city" class="wpcf7-form-control wpex-select-wrap wpcf7-validates-as-required wpcf7-validates-as-select"  aria-required="true" aria-invalid="false">
													<option value="">Selecione um Estado antes de selecionar a Cidade</option>
												</select>
												<img class="icon-msg-city" src="../../imagens/alert.png" />
											</p>
											<p style="text-align: center;">
												<input type="button" id="send_information" value="Consultar" class="theme-button input-button">
											</p>
											<p>
												<div style="clear: both;"></div>
												<div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id="lista_posto" style="padding-bottom: 100px;"></div>
												<div class="wpb_column vc_column_container vc_col-sm-12 contatar-campos" style="text-align: right; display: none;">
													<div vc_col-sm-4>
														<p style="color: #ff0000; font-weight: 600; text-decoration: underline #ff0000;" >Não encontrou um posto autorizado?</p>
														<button type="button" class="btn btn-default" id="contatar-fab">Contatar o Fabricante</button>
													</div>
												</div>
											</p>
										</div>
										<div class="wpcf7-response-output wpcf7-display-none"></div>
									</form>
								</div>
								<div class="vcex-spacing" style="height:20px"></div>
							</div>
						</div>
					</div>
					<div class="wpb_column vc_column_container vc_col-sm-12">
						<div class="vc_column-inner">
							<div class="wpb_wrapper">
								<div role="form" class="wpcf7" id="wpcf7-f86-p8-o1" lang="en-US" dir="ltr" style="margin: 0 auto; text-align: center;">
									<div class="screen-reader-response"></div>
									<form action="/contato/#wpcf7-f86-p8-o1" method="post" class="wpcf7-form" novalidate="novalidate">
										<div style="display: none;">
											<input type="hidden" name="_wpcf7" value="86">
											<input type="hidden" name="_wpcf7_version" value="5.0.4">
											<input type="hidden" name="_wpcf7_locale" value="en_US">
											<input type="hidden" name="_wpcf7_unit_tag" value="wpcf7-f86-p8-o1">
											<input type="hidden" name="_wpcf7_container_post" value="8">
										</div>
										<div class="full-width-input">
											<p>
												<div class="col-xs-12 col-sm-10 col-sm-offset-1  col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id="box_mapa" style="display: none; text-align:center">
													<div id="map_canvas" style="height: 450px; margin-top: 50px; border: 1px solid #CCCCCC;"></div>
													<div class="text-right">
														<br />
														<button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os pontos</button>
													</div>
												</div>
											</p>
										</div>
										<div class="wpcf7-response-output wpcf7-display-none"></div>
									</form>
								</div>
								<div class="vcex-spacing" style="height:20px"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="vc_row wpb_row vc_row-fluid fm_fale_conosco" id="product-mcsaab" style="display: none; text-align: center;">
					<div class="wpb_column vc_column_container vc_col-sm-12">
						<div class="vc_column-inner ">
							<div class="wpb_wrapper">
								<div class="full-width-input">
									<h2 class="vcex-module vcex-heading vcex-heading-bottom-border-w-color vc_custom_1537146920735" style="font-family:Lato;font-size:26px;text-transform:uppercase;">
										<span class="vcex-heading-inner clr" style="border-color:#4c4c4c;">SAC - MCASSAB</span>
									</h2>
									<div class="vcex-spacing" style="height:20px"></div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="font-family:Open Sans;font-size:24px;font-weight:300;">
										<span class="vcex-heading-inner clr">Para todo o Brasil</span>
									</div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:30px;font-weight:300;">
										<span class="vcex-heading-inner clr">(11) 3003 - 9030</span>
									</div>
									<div class="vcex-spacing" style="height:20px"></div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="font-family:Open Sans;font-size:24px;font-weight:300;">
										<span class="vcex-heading-inner clr">E-mail</span>
									</div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:30px;font-weight:300;">
										<span class="vcex-heading-inner clr">sac.solucao@mcassab.com.br</span>
									</div>
									<div class="vcex-spacing" style="height:20px"></div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="font-family:Open Sans;font-size:24px;font-weight:300;">
										<span class="vcex-heading-inner clr">Horário de Atendimento</span>
									</div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:22px;font-weight:500;">
										<span class="vcex-heading-inner clr">Segunda à Sexta:</span>
									</div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:30px;font-weight:300;">
										<span class="vcex-heading-inner clr">das 9:00 às 17:00 horas</span>
									</div>
									<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#ff0000;font-family:Open Sans;font-size:14px;font-weight:600;text-transform:uppercase;">
										<span class="vcex-heading-inner clr">exceto feriados</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</body>

        <script type="text/javascript">
        	$(document).ready(function() {

        		Shadowbox.init();

        		$("#contatar-fab").click(function() {

        			Shadowbox.open({
			            content:'<div class="wpb_column vc_column_container vc_col-sm-12">\
									<div class="vc_column-inner" style="width: 600px; margin: 0 auto; text-align: center;">\
										<div class="wpb_wrapper">\
											<h2 class="vcex-module vcex-heading vcex-heading-bottom-border-w-color vc_custom_1537146920735" style="font-family:Lato;font-size:26px;text-transform:uppercase;">\
												<span class="vcex-heading-inner clr" style="border-color:#4c4c4c;">SAC - Cuisinart</span>\
											</h2>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="font-family:Open Sans;font-size:22px;font-weight:300;">\
												<span class="vcex-heading-inner clr">Grande São Paulo</span>\
											</div>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:20px;font-weight:300;">\
												<span class="vcex-heading-inner clr">(11) 4118 - 2961</span>\
											</div>\
											<div class="vcex-spacing" style="height:22px"></div>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="font-family:Open Sans;font-size:22px;font-weight:300;">\
												<span class="vcex-heading-inner clr">Demais Cidades do Brasil</span>\
											</div>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:20px;font-weight:300;">\
												<span class="vcex-heading-inner clr">0800 580 0563</span>\
											</div>\
											<div class="vcex-spacing" style="height:22px"></div>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="font-family:Open Sans;font-size:22px;font-weight:300;">\
												<span class="vcex-heading-inner clr">Horário de Atendimento</span>\
											</div>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:21px;font-weight:500;">\
												<span class="vcex-heading-inner clr">Segunda à Sexta:</span>\
											</div>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#79c11b;font-family:Open Sans;font-size:20px;font-weight:300;">\
												<span class="vcex-heading-inner clr">das 9:00 às 18:00 horas</span>\
											</div>\
											<div class="vcex-module vcex-heading vcex-heading-plain" style="color:#ff0000;font-family:Open Sans;font-size:15px;font-weight:600;text-transform:uppercase;">\
												<span class="vcex-heading-inner clr">exceto feriados</span>\
											</div>\
										</div>\
									</div>\
								</div> ',
			            player:     "html",
			            title:      "Contatos - SAC",
			            width:      600,
			            height:     400,
			            options: {
			                modal: true,
			                enableKeys: false,
			                overlayColor:'#828181'
			            }
			        });
        		});

        		$("#your-cep").mask("99999-999");

                $("#your-cep").blur(function() {
                    if ($(this).attr("readonly") == undefined && $(this).val() != "") {
                        busca_cep($(this).val(),"");
                    }else{
                        $("#end_cliente").val("");
                    }
                });

                $(".icon-msg-city").hover(
                	function() {
                		$(".msg_city").fadeIn("slow");
                	},
                	function() {
                		$(".msg_city").fadeOut("slow");
                	}
                )

                $(".your-product").on("change", function(){
                	var linha = $("#your-product option:selected").val();
					if(linha != 'eletrodomesticos'){

						$('#your-model').css('display', 'block')
						$('#eletrodomestico').css('display', 'none');
						$.ajax({
							type: "POST",
							url: "fale_conosco.php",
							data: {
								"search_model" : true,
								"linha" : linha
							}
						}).done(function(data) {
							data = JSON.parse(data);
							if(data.success){
								$("#your-model").html("<option value=''>Selecione seu modelo</option>");

								$.each(data.linha, function(key, value){
									$("#your-model").append("<option value='" + value.linha + "'>" + value.descricao + "</option>");
								});

							}
						});
					}else{
						$('#your-model').css('display', 'none');
						$(".nao_e_mc").css('display', 'none');
						$('#eletrodomestico').css('display', 'block');
					}
                	
                });

                $("#your-model").on("change", function(){
                	var model = $("#your-model option:selected").val();
            		clearField();

                	if(model == "others"){
                		$("#product-mcsaab").show();
                	} else {
                		$("#product-cuisinart").show();
                	}
                });
            });

            /*$("#open_callcenter").on("click", function(){
            	window.open('../callcenter/callcenter_cadastra_cuisinart.php', '_blank');
            });*/

            $("#search_place").on("click", function(){
            	$("#search-place").show();
            	$("#send_information").focus();
            });

            $("#your-linha").on('change', function() {
            	if ($("#your-linha option:selected").val() == '' || $("#your-linha option:selected").val() == undefined) {
            		alert("Favor informar a linha")
            		$("#send_information").focus();
            	} else {
            		loadCity();
            	}
            });

            $('#your-state').on('change', function() {
            	if ($("#your-cep").val() == '' || $("#your-cep").val() == undefined) {
            		alert("Favor informar o Cep para carregar as cidades")
            		$("#send_information").focus();
            	} else {
            		loadCity();
            	}
            });

            $("#send_information").on("click", function(){
            	loadMap();
            });

            function clearField(){
            	$("#product-mcsaab").hide();
        		$("#product-cuisinart").hide();
        		$("#search-place").hide();
        		$("#your-cep").val("");
        		$("#your-state").val("");
        		$("#your-linha").val("");
        		$("#your-state option").attr('selected',false);
        		$('#your-city').find("option").remove();
        		$("#your-city").html("<option value=''>Selecione um Estado antes de selecionar a Cidade</option>");
        		$('#box_mapa').hide();
				$('#lista_posto').html("");
				$(".contatar-campos").hide();
				$("#msg_ob").hide();
				$("#menssage").hide()
            }

            function loadCity(){
            	$('#your-city').find("option").remove();
				$("#your-city").val("");
				$("#menssage").hide().html("");

				var state   = $('#your-state option:selected').val();
				var product = $("#your-product option:selected").val();
				var linha   = $("#your-linha option:selected").val();

				if(state != ""){
					$('#your-city').removeAttr('disabled');

					$.ajax({
                		type: "POST",
						url: "fale_conosco.php",
						data: {
							"search_city" : true,
							"state": state,
							"product": product,
							"linha":linha
						}
					}).done(function(data) {
						data = JSON.parse(data);

						if(data.success){
							$("#your-city").append("<option value=''>Selecione uma Cidade</option>");

							$.each(data.produto, function(key, value){
								$("#your-city").append("<option value='" + value.cidade + "'>" + value.contato_cidade + "</option>");
							});
							$("#menssage").html("<div style='width: 50%; text-align: center; margin: 0 auto;'><h4 class='alert alert-danger'><b>Nenhum posto autorizado encontrado na sua região</b></h4></div>").show();
						} else {
							$("#contatar-fab").click()
						}
					});
				}
            }
			
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

			function busca_cep(cep,method){
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
			        url: "../ajax_cep.php",
			        type: "GET",
			        data: {
			            cep: cep,
			            method: method
			        },
			        success: function(data) {
			            results = data.split(";");
			            if (results[0] != "ok") {
			            	$("#send_information").focus();
			                alert("CEP Inválido");
			            } else {
							$("#your-state").data("callback", "selectCidade").data("callback-param", results[3]);
							$("#your-state option").attr('selected',false);
							$("#your-state option[value='" + results[4] + "']").attr('selected','selected');
			                $("#end_cliente").val(results[1]+":"+results[2]+":"+results[3]+":"+results[4]);
			            }

			            $.ajaxSetup({
			                timeout: 0
			            });
			        },
			        error: function(xhr, status, error) {
			            busca_cep(cep, "database");
			        }
			    });
			}

			function loadMap(){
				$('#box_mapa').hide();
				$('#lista_posto').html("");

				if (($('#your-state').val() == "" || $('#your-state').val() == undefined) || ($("your-cep") == "" || $("your-cep") == undefined)) {
					$('#your-state').addClass('danger');
					messageError();
					$("#menssage").hide();
					return;
				} else if ($('#your-linha').val() == "" || $('#your-linha').val() == undefined) {
					$('#your-linha').addClass('danger');
					messageError();
					$("#menssage").hide();
					return;
				} else {
					closeMessageError();
				}

                var familia    = $('#your-product option:selected').val();
                var cepCliente = $('#your-cep').val();

				if (cepCliente.length > 0) {
            		var end_cliente = $('#end_cliente').val();
				} else {
					var end_cliente = "";
				}

				var estado  = $('#your-state option:selected').val();
				var cidade  = $('#your-city option:selected').val();
				let linha   = $("#your-linha option:selected").val();

				if (cidade == "") {
					cidade = "sem cidade";
				}

				$.ajax({
            		type: "POST",
					url: "fale_conosco.php",
					data: {
						send_place  : true,
						familia     : familia,
						estado      : estado,
						cepCliente  : cepCliente,
						end_cliente : end_cliente,
						cidade      : cidade,
						linha       : linha
					}
				}).done(function(data) {
					var info  = data.split("*");
					var dados = info[1];

					if (dados != undefined) {
						if (dados.length > 0) {
							$('#box_mapa').show();
							addMap(dados);

							if (JSON.parse(dados).length < 2){
								$("#show_all").hide();
							} else {
								$("#show_all").show();
							}
						}
					}
					$('#lista_posto').html(info[0]);

					$("#open_callcenter").on("click", function(){
		            	window.open('../callcenter/callcenter_cadastra_cuisinart.php', '_blank');
		            });
				});

				window.parent.postMessage($(document).height()+100, "*");
				window.parent.postMessage("scroll", "*");

				setTimeout(function(){ $(".contatar-campos").fadeIn("slow"); }, 2000);
				
			}

			function messageError() {
				$('.alert').show();
			}

			function closeMessageError(e) {
				$('#your-state').removeClass('danger');
				$('.alert').hide();
			}
        </script>
	</html>
