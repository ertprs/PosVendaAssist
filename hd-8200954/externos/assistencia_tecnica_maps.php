<?php
require_once('../admin/dbconfig.php');
require_once('../admin/includes/dbconnect-inc.php');
require_once('../admin/funcoes.php');
require_once('../fn_traducao.php');

if (isset($_POST['ajax']) && isset($_POST['todospostos'])) {
	$fabrica = $_POST['fabrica'];

	$sql = "SELECT
				tbl_posto_fabrica.nome_fantasia ,
				tbl_posto_fabrica.latitude AS latitude ,
				tbl_posto_fabrica.longitude AS longitude
			FROM tbl_posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
			WHERE  tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND tbl_posto_fabrica.posto <> 6359
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			ORDER BY tbl_posto.cidade;";

	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$data = pg_fetch_all($res);
		exit("*".json_encode($data));
	}
}

$token        = trim($_GET['tk']);
$token_post   = $_POST['token'];
$cod_fabrica  = $_GET['cf'];
$cod_fabrica  = base64_decode(trim($cod_fabrica));

$nome_fabrica = $_GET['nf'];
$nome_fabrica = base64_decode(trim($nome_fabrica));

$getBy        = $_GET['getby'];
$getby        = base64_decode(trim($getby));


if ($_POST['fabrica']) {
	$xfabrica = $_POST['fabrica'];
} else {
	$xfabrica = $cod_fabrica;
}

if ($xfabrica == 180) {
	$xxpais = "AR"; //argentina
	$img_mapa = "mapa_argentina.png";
	$paises = array("AR" => "Argentina", "BO" => "Bolívia", "PY" => "Paraguai", "UY" => "Uruguai");
} elseif ($xfabrica == 181) {
	$xxpais = "CO"; //colombia
	$img_mapa = "mapa_colombia.jpg";
	$paises = array("CO" => "Colombia");
} elseif ($xfabrica == 182) {
	$xxpais = "PE"; //peru
	$img_mapa = "mapa_peru.jpg";
	$paises = array("CL" => "Chile", "EC" => "Equador", "PE" => "Peru");
}

if(in_array($xfabrica,[180,181,182])){
	$moduloTraducao['es'] = true;
	$sistema_lingua = "es";
}

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
if (trim($token) != trim($token_comp)) {
	exit;
}

/* Filtro: por mapa de rede, os ou ambos. */
if (empty($usaTipoPesquisa)) {
	$sql_param = "SELECT JSON_FIELD('usaTipoPesquisa', parametros_adicionais) AS usaTipoPesquisa 
				  FROM tbl_fabrica WHERE fabrica = {$cod_fabrica}";
	$res_param = pg_query($con,$sql_param);

	if (pg_num_rows($res_param) > 0) {
		$usaTipoPesquisa = pg_fetch_result($res_param, 0, 'usaTipoPesquisa');
		$usaTipoPesquisa = ($usaTipoPesquisa == 't' || $usaTipoPesquisa == 'true' || $usaTipoPesquisa === true) ? true : false;
	}
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

if(isset($_POST['buscaProvincia'])){

	$pais = $_POST['pais'];
	$fabrica = $_POST['fabrica'];
	$linha   = $_POST['linha'];

	$option = [];

	$sql = "SELECT DISTINCT tbl_estado_exterior.estado, tbl_estado_exterior.nome 
		FROM tbl_estado_exterior
		INNER JOIN tbl_posto_fabrica ON tbl_estado_exterior.estado = tbl_posto_fabrica.contato_estado 
		AND tbl_posto_fabrica.fabrica = {$fabrica}
		AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		AND tbl_posto.pais = '{$pais}'
		INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
		AND tbl_posto_linha.linha = {$linha}
		WHERE tbl_estado_exterior.pais ='{$pais}' 
		AND tbl_estado_exterior.visivel IS TRUE 
		ORDER BY nome ASC;";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {

    	$option[""] = traduz("Selecione");

        foreach (pg_fetch_all($res) as $key => $row) {
            $option[$row['estado']] = utf8_decode($row['nome']);
        }
    } else {
    	$option[""] = traduz('Nenhum Posto Autorizado localizado para este estado');
    }

    exit(json_encode($option));

}

/* Busca Cidades */
if (isset($_POST['uf'])) {

	$uf      = $_POST['uf'];
	$linha   = $_POST['linha'];
	$fabrica = $_POST['fabrica'];
	$produto = $_POST['produto'];
	$pais    = $_POST['pais'];

	$cond = "";

        if (in_array($fabrica, [180,181,182])) {
		$joinPosto = " JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto";

		if(in_array($fabrica, [180,181,182])){
			$cond = " AND tbl_posto.pais='{$pais}'";
		}else{
			$cond = " AND tbl_posto.pais='{$xxpais}'";
		}
        }


	if ($fabrica == 74){
		if (!empty($linha)){
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
		}
	}else if ($fabrica == 175){
		if (!empty($produto)){
			$sql = "SELECT
					distinct upper(trim(fn_retira_especiais(tbl_posto_fabrica.contato_cidade))) as contato_cidade
				FROM tbl_posto_fabrica
				JOIN tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto
				JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$fabrica}
				JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = {$fabrica}
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				AND tbl_posto_fabrica.contato_estado = '$uf'
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND tbl_posto_fabrica.posto <> 6359
				AND tbl_produto.produto = $produto
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE ORDER BY 1 ASC";
		}
	}else{
		if (in_array($cod_fabrica, [167,203]) AND in_array($linha, array(1162, 1163, 1219))) {

			$joinTipoPosto = " JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.posto_interno IS TRUE";
			$condEstado = "";

		} else {

			$condEstado = "AND tbl_posto_fabrica.contato_estado = '$uf'";
		}
		if (!empty($linha)){
			$sql = "SELECT
					distinct upper(trim(fn_retira_especiais(tbl_posto_fabrica.contato_cidade))) as contato_cidade
				FROM tbl_posto_fabrica
				JOIN tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
				JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$fabrica}
				{$joinTipoPosto}
				{$joinPosto}
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				{$condEstado}
				{$cond}
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND tbl_posto_fabrica.posto <> 6359
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE ORDER BY 1 ASC";
		}


	}
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		echo "<option value=''></option>\n";
		while ($data = pg_fetch_object($res)) {
			echo "<option value='$data->contato_cidade'>".ucwords(strtolower(retira_acentos($data->contato_cidade)))."</option>";
		}
	}else{
		if ($cod_fabrica == 122) {
			echo "<option value='' >".traduz('Não há um posto próximo!!')."</option>";
		}else{
			echo "<option value='' >".traduz('Nenhum Posto Autorizado localizado para este estado')."</option>";
		}
	}

	exit;

}

/* Busca os Postos Autorizados */
if (isset($_POST['estado']) && isset($_POST['cidade'])) {
	$linha   = $_POST['linha'];
	$uf      = $_POST['estado'];
	$cidade  = $_POST['cidade'];
	$fabrica = $_POST['fabrica'];
	$posto   = $_POST['posto'];

        $cond = "";

	if (in_array($fabrica, [180,181,182])) {
	     $pais = (!empty($_POST['pais'])) ? $_POST['pais'] : $xxpais;
             $joinPosto = " JOIN tbl_posto USING(posto)";
             $cond = " AND tbl_posto.pais='{$pais}'";
        }

	if($cidade != "sem cidade"){
		$cond_cidade .= " AND UPPER(to_ascii(fn_retira_especiais(tbl_posto_fabrica.contato_cidade), 'LATIN9')) = UPPER(to_ascii('$cidade', 'LATIN9')) ";
	}
	if ($fabrica == 74){
		if (!empty($linha)){
			$sql_linha = " select codigo_linha from tbl_linha where linha = $linha and fabrica = $fabrica ";
			$res_linha = pg_query($con, $sql_linha);
			if(pg_num_rows($res_linha)> 0){
				$codigo_linha = pg_fetch_result($res_linha, 0, 'codigo_linha');
			}

			if($codigo_linha == "02"){
				$cond_divulga = " AND JSON_FIELD('divulgar_consumidor_mapa_portateis', parametros_adicionais) = 't'";
			}
			if($codigo_linha == "01"){
				$cond_divulga = " AND JSON_FIELD('divulgar_consumidor_mapa_fogo', parametros_adicionais) = 't'";
			}

			unset($cond_cidade);
			$cond_atlas = "	JOIN tbl_posto_fabrica_ibge on tbl_posto.posto =  tbl_posto_fabrica_ibge.posto
							JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
							JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo = tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo AND tbl_posto_fabrica_ibge_tipo.fabrica = {$fabrica}
							";

			if($cidade != "sem cidade"){
				$cond_uf = "and (  UPPER(to_ascii(tbl_cidade.nome, 'LATIN9')) = UPPER(to_ascii('$cidade', 'LATIN9'))
							OR UPPER(to_ascii(tbl_posto_fabrica.contato_cidade, 'LATIN9')) = UPPER(to_ascii('$cidade', 'LATIN9')) ) ";
			}else{
				$cond_uf = " ";
			}
			$cond_cidade = " ";

			$group_by = "GROUP BY  tbl_posto.posto ,
					tbl_posto.nome ,
					tbl_posto_fabrica.nome_fantasia ,
					tbl_posto_fabrica.contato_cep  ,
					tbl_posto_fabrica.latitude  ,
					tbl_posto_fabrica.longitude  ,
					tbl_posto_fabrica.contato_fone_comercial  ,
					tbl_posto_fabrica.contato_email  ,
					tbl_posto_fabrica.contato_endereco  ,
					tbl_posto_fabrica.contato_numero ,
					tbl_posto_fabrica.contato_cidade ,
					tbl_posto_fabrica.contato_bairro ,
					tbl_posto_fabrica.contato_complemento,
					tbl_posto_fabrica.contato_telefones ";
		}
	} else {

		if (!in_array($cod_fabrica, [167,203])) {

			$cond_uf = "AND tbl_posto_fabrica.contato_estado = '$uf' ";

		} else {

			$cond_uf = " ";
			$cond_cidade = " ";
		}
	}

	if (in_array($fabrica, array(74))) {
		$campos_atlas .= ",tbl_posto_fabrica.contato_telefones";
	}

	if ($fabrica == 175){
		if (!empty($produto)){
			$join_produto = " 
				INNER JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto 
				JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $fabrica
				JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = $fabrica AND tbl_produto.produto = $produto
			";
		}
	}else{
		if (!empty($linha)){
			$join_linha = " INNER JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = {$linha} ";
		}
	}

	$sql ="	SELECT DISTINCT
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
				tbl_posto_fabrica.contato_bairro AS bairro,
				tbl_posto_fabrica.contato_complemento AS complemento
				{$campos_atlas}
			FROM tbl_posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
			{$join_linha}
			{$join_produto}
			{$cond_atlas}
			WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			$cond_uf
			$cond_cidade
			$cond_divulga

			$cond
			AND tbl_posto_fabrica.posto <> 6359
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			AND tbl_posto_fabrica.senha <> '*'
			$group_by
			ORDER BY tbl_posto_fabrica.contato_cidade,tbl_posto_fabrica.nome_fantasia";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$cor = "";
		$i = 0;

		while ($data = pg_fetch_object($res)) {
			/* Mascara CEP */
			$cep = maskCep($data->cep);

			/* Mascara Telefone */
			if (in_array($fabrica, array(74))) {
	            $chars_replace = array('{','}','"');
	            $telefone = str_replace($chars_replace, "", trim($data->contato_telefones));
			}else{
				$telefone = maskFone($data->telefone);
			}

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
							$data->endereco, $data->numero  $data->complemento&nbsp; / &nbsp; ".traduz('CEP').": $cep <br />
							".traduz('BAIRRO').": $data->bairro &nbsp; / &nbsp; $data->cidade - $uf <br />
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
							if(!in_array($cod_fabrica,[180,181,182])){
			echo "
							<button type='button' class='btn btn-default' onclick=\"localizarMap('".$data->lat."', '".$data->lng."')\" style='margin-top: 10px;'><i class='glyphicon glyphicon-search'></i> Localizar</button>";
							}
			echo "
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
		echo traduz('Nenhum Posto Autorizado localizado para este estado!');
	}

	exit;
}

// Preparando variáveis para parametrização do HTML/CSS/JS
$titulo_mapa_rede = traduz('Assistência Técnica');

if (!in_array($cod_fabrica, array(122,125, 131, 152, 203))) {
	$titulo_mapa_rede .= ' - ' . $nome_fabrica;
}

switch ($cod_fabrica) {
	case 74:
		$nome_fabrica = 'Atlas Fogões';
		break;

	case 122:
		$nome_fabrica = "";
		$img = '<img src="../logos/wurth_admin1.jpg" alt="http://www.wurth.com.br" style="float:left;max-height:70px;max-width:210px;margin-top:5px;margin-right:95px;" border="0">';
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
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="fancyselect/fancySelect.css" /> -->
		<link type="text/css" rel="stylesheet" media="screen" href="../plugins/select2/select2.css" />
		<!-- <link type="text/css" rel="stylesheet" href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" /> -->

		<!--[if lt IE 10]>
		<link rel="stylesheet" type="text/css" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" />
		<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
		<![endif]-->

		<script type="text/javascript" src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<!-- <script src="https://raw.github.com/jamietre/ImageMapster/e08cd7ec24ffa9e6cbe628a98e8f14cac226a258/dist/jquery.imagemapster.js"></script> -->

		<!-- Google maps -->
		<!-- <script type="text/javascript" src="http://www.google.com/jsapi?fake=.js"></script>
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>
		<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/routeboxer/src/RouteBoxer.js"></script> -->
		<script type="text/javascript" src="cssmap_brazil_v4_4/jquery.cssmap.js"></script>
		<!-- <script type="text/javascript" src="fancyselect/fancySelect.js"></script> -->
		<script src="https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit" async defer></script>
		<script src="institucional/lib/mask/mask.min.js" ></script>
		<!-- MAPBOX -->
		<link href="../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
		<script src="../plugins/leaflet/leaflet.js" ></script>
		<script src="../plugins/leaflet/map.js?v=<?php echo date('YmdHis');?>" ></script>
		<script>
        	var MapaTelecontrol = Map;
        	var arraySimpleMaps = [];
       	</script>
		<script src="../plugins/mapbox/geocoder.js"></script>
		<script src="../plugins/mapbox/polyline.js"></script>

		<script>
			jQuery.expr[':'].icontains = function(a, i, m) {
			  return jQuery(a).text().toUpperCase()
			      .indexOf(m[3].toUpperCase()) >= 0;
			};
		</script>

		<?php
		foreach ($paises as $siglaPais => $nomePais) { ?>

			<script type="text/javascript" src="simpleMap/<?= $siglaPais ?>/mapdata.js?v=<?= date("YmdHis") ?>"></script>
			<script type="text/javascript" src="simpleMap/<?= $siglaPais ?>/countrymap.js?v=<?= date("YmdHis") ?>"></script>

			<script>
				/*
					Como tem um script pra cada pais, foi necessário criar o código abaixo
					pois o próprio plugin estava sobreescrevendo os eventos
				*/

				arraySimpleMaps['<?= $siglaPais ?>'] = simplemaps_countrymap;

				//callback para ação de clique do estado
				arraySimpleMaps['<?= $siglaPais ?>'].hooks.click_state = function(id) {

					var objetoMapa = arraySimpleMaps['<?= $siglaPais ?>'].mapdata;

					if (objetoMapa.state_specific[id].name != undefined) {

						let nomeEstado = removeAcento((objetoMapa.state_specific[id].name).toUpperCase());

						let option = $("#estado").find("option:icontains('"+nomeEstado+"')");

						if (option.length > 0) {
							$(option).prop("selected", true).change();
						} else {
							alert("<?= traduz('Nenhum posto encontrado para o estado informado') ?>");
						}
						
					}

				};

				//click na capital
				arraySimpleMaps['<?= $siglaPais ?>'].hooks.click_location = function(id) {

					var objetoMapaCapital = arraySimpleMaps['<?= $siglaPais ?>'].mapdata;

					if (objetoMapaCapital.locations[0].estado != undefined) {

						let nomeEstado = removeAcento((objetoMapaCapital.locations[0].estado).toUpperCase());

						let option = $("#estado").find("option:icontains('"+nomeEstado+"')");

						if (option.length > 0) {
							$(option).prop("selected", true).change();
						} else {
							alert("<?= traduz('Nenhum posto encontrado para o estado informado') ?>");
						}
						
					}

				};

				//remover mensagem da versão demo do mapa
				$(window).load(function(){
					setTimeout(function(){ 
						$("#<?= $siglaPais ?>_inner", window.document).find("svg").find("tspan").closest("svg").remove();
					}, 1000);
				});

			</script>

		<?php
		} ?>

		<script src="../plugins/select2/select2.js"></script>
		<style type="text/css">
            <?php if ($cod_fabrica == '124'): ?>
            @font-face {
                font-family: "Segoe";
                src: url("institucional/fonts/segoe/segoeuib.ttf");
            }

            body { font-family: "Segoe", serif }
            <?php endif ?>
            #CL {
            	margin-left: -150px !important;
            }

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

			#map-brazil {
				left: -23px;
			}
			.texto_cidade{
				font-size: 12px;
				font-weight: normal;
				color:#ff0000;
			}
			#reCaptcha{
				margin-top: 5px;
				margin-bottom: 5px;
				margin-left: 14px;
			}
			#btn_os{
				margin-left: 15px;
			}
		</style>

		<style type="text/css">
			.select2-container--default .select2-selection--single{
				width: 420px;
				min-width: 200px;
				height: 44px;
				border-radius: 3px;
				position: relative;
			}
			.select2-search--dropdown .select2-search__field {
			    padding: 4px;
			    box-sizing: border-box;
			    width: 400px;
				min-width: 200px;
			}
			.select2-dropdown .select2-dropdown--below{
				width: 405px !important;
			}
			.select2-dropdown {
				width: 405px !important;
			}
		</style>
		
		<script type="text/javascript">

			function removeAcento (text)
			{       
			    text = text.toLowerCase();                                                         
			    text = text.replace(new RegExp('[ÁÀÂÃ]','gi'), 'a');
			    text = text.replace(new RegExp('[ÉÈÊ]','gi'), 'e');
			    text = text.replace(new RegExp('[ÍÌÎ]','gi'), 'i');
			    text = text.replace(new RegExp('[ÓÒÔÕ]','gi'), 'o');
			    text = text.replace(new RegExp('[ÚÙÛ]','gi'), 'u');
			    text = text.replace(new RegExp('[Ç]','gi'), 'c');
			    return text;                 
			}

			<?php 
			
			if (in_array($cod_fabrica, [167,203])) { ?> 

				$(function () {

    				$(document).ready(function() {
    					$("#linha").on("change", function() {
    						
    						var val_linha = $("#linha").val();

    						// inArray retorna o valor do indíce, caso tenha
    						if (jQuery.inArray(parseInt(val_linha), [1163,1162,1219]) >= 0) {
								
    							$("#estado-group").hide();
    							$("#cidade-group").hide();
    						} else {

    							$("#estado-group").show();
    							$("#cidade-group").show();
    						}

    					});
    				});
    			});

			<?php } ?>


			<?php 
			
			if (in_array($cod_fabrica,array(180,181,182))) { ?> 

				$(function () {

					$(document).ready(function() {
					$("#pais").on("change", function() {

						var pais = $("#pais").val();
						var linha = $("#linha").val();

						$(".mapa_america").hide();
						$("#"+pais).show();

						$("#estado").html("<option value='' ></option>").val("").trigger("change");

						$.ajax({
							url: window.location.pathname,
							type:     'POST',
							data:      {
								pais:      pais,
								buscaProvincia:   true,
								token   : '<?=$token?>',
								fabrica : '<?=$cod_fabrica?>',
								linha   : linha
							},
							dataType: 'json',
							success: function(data) {

								$("#estado option").remove();

								$.each(data, function(sigla, nome){
									
									$.each(arraySimpleMaps[pais].mapdata.state_specific, function(idmaps, nomeEstado){

										if (removeAcento(nomeEstado.name).toUpperCase() == removeAcento(nome).toUpperCase()) {
											arraySimpleMaps[pais].mapdata.state_specific[idmaps].color='red';
										} 

									});

									arraySimpleMaps[pais].refresh();

									setTimeout(function(){
										$("#"+pais+"_inner", window.document).find("svg").find("tspan").closest("svg").remove();
									}, 500);

									let option = $("<option>", {
										value: sigla,
										text: nome
									});

									$("#estado").append(option);

								});

								$("#estado").change();

							}
						});

					});

					$("#pais").change();

				});
    			});

			<?php } ?>
			<?php if (in_array($cod_fabrica, array(122,163,167,171,174,175,198,203))){ ?>
					var showRecaptcha= function() {
		                grecaptcha.render('reCaptcha', {
		                  'sitekey' : '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
		                });
		            };
		    <?php } ?>

			/* INICIO - MAPBOX */
			var MapaTele, RouterTele, GeocoderTele, MarkersTele;

			function addMap(data) {
				var locations = $.parseJSON(data);

				if (typeof MapaTele !== 'object') {
					MapaTele     = new MapaTelecontrol("map_canvas");
					RouterTele   = new Router(MapaTele);
					GeocoderTele = new Geocoder();
					MarkersTele  = new Markers(MapaTele);

					MapaTele.load();
				}

				MarkersTele.remove();
				MarkersTele.clear();
				$.each(locations, function(key, value) {
					var lat = value.latitude;
					var lng = value.longitude;

					if (lat == null || lng == null) {
						return true;
					}

                    <?php
                    $marker_color = "red";
                    if ($cod_fabrica == 124) {
                        $marker_color = "deepblue";
                    }
                    ?>

					MarkersTele.add(lat, lng, "<?= $marker_color ?>", value.nome_fantasia);
				});
				MarkersTele.render();
				MarkersTele.focus();
			}

			function localizarMap(lat, lng) {
				MapaTele.setView(lat, lng, 15);
				scrollPostMessage();
			}

			function setZoomAllMarkers(){
				MarkersTele.focus();
			}
			/* FIM - MAPBOX */

			<?php
			if ($cod_fabrica == 131) {
			?>
				var scroll = 550;
				var scroll_xs = 850;
			<?php
			} else {
			?>
				var scroll = 760;
				var scroll_xs = 750;
			<?php
			}
			?>

			function scrollPostMessage(scroll_p) {
				if (scroll_p != 0) {
					scroll_xs = 0;
					scroll    = 0;
				}
				if ($("div.scroll-xs").is(":visible")) {
					$(window).scrollTop(scroll_xs);
				} else {
					$(window).scrollTop(scroll);
				}

				window.parent.postMessage("scroll", "*");
			}

			/* Google Maps */
			function initialize_antigo(markers) {
				var width = parseInt($("#map_canvas").width() / 2);
				var height = parseInt($("#map_canvas").height() / 2);

				var url = "https://maps.googleapis.com/maps/api/staticmap?scale=2&size="+width+"x"+height+"&maptype=roadmap&"+markers.join('&')+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

				$("#map_canvas").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");
			}

			function addMap_antigo(data) {
				var locations = $.parseJSON(data);
				var markers = [];

				$.each(locations, function(key, value) {
					var lat = value.latitude;
					var lng = value.longitude;

					if (lat == null || lng == null) {
						return true;
					}

					markers.push("markers=color:red%7C"+lat+","+lng);
				});

				initialize(markers);
			}

			function localizarMap_antigo(lat, lng) {
				<?php
				if ($cod_fabrica == 122) {
				?>
					scrollPostMessage(80);
				<?php
				} else {
				?>
					scrollPostMessage();
				<?php
				}
				?>

				var width = parseInt($("#map_canvas").width() / 2);
				var height = parseInt($("#map_canvas").height() / 2);

				var url = "https://maps.googleapis.com/maps/api/staticmap?center="+lat+","+lng+"&zoom=15&scale=2&size="+width+"x"+height+"&maptype=roadmap&markers=color:red%7C"+lat+","+lng+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";
				$("#map_canvas").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");

			}

			function setZoomAllMarkers_antigo() {
				scrollPostMessage();

				var markers = [];

				$("div.row-posto").each(function() {
					var lat = $(this).data("lat");
					var lng = $(this).data("lng");

					if (lat == null || lng == null) {
						return true;
					}

					markers.push("markers=color:red%7C"+lat+","+lng);
				});

				initialize(markers);
			}
			/* Fim - Google Maps */

			<?php
			if ($_GET['xcf'] == 'true') {
			?>
				$(window).load(function () {
					less.modifyVars({'@map_340':'transparent url(\'br-340<?='-'.$cf?>.png\') no-repeat -970px 0'});
				});
			<?php
			}
			?>
			function carregaMapa(){
				var fabrica = <?=$cod_fabrica;?>;
				$.ajax({
					url: window.location.pathname,
					data: {ajax: "sim", todospostos: "sim", fabrica: fabrica},
					method: "POST"
				}).done(function(data){
					info = data.split("*");
					var dados = info[1];

					if($.inArray(fabrica,[180,181,182]) == -1 ){
						$('#box_mapa').show();
						addMap(dados);
					}
				});
			}

			$('document').ready(function() {

                $("#cpf_cnpj").focus(function(){

                    $(this).unmask();
                    $(this).mask("99999999999999");
                });
                $("#cpf_cnpj").blur(function(){
                    var el = $(this);
                    el.unmask();
                    if(el.val().length > 11){
                        el.mask("99.999.999/9999-99");
                    }


                    if(el.val().length <= 11){
                        el.mask("999.999.999-99");
                    }
                });
				//carregaMapa();
				$('#box_mapa').hide();
			    //$("select").fancySelect();
			    $("select").select2();
				$('#linha').blur(function() {
					var id = "linha";
					//closeMessageError(id);

					var linha = $("#linha option:selected").text();
					var iframe_linha = $("#iframe_linha").val();

					if(linha != iframe_linha){
						$("div.trigger").removeClass("open");
						$("ul.options").removeClass("open");
						$("#iframe_linha").val(linha);
					}
				});

				$('#produto').blur(function() {
					var id = "produto";
					//closeMessageError(id);

					var produto = $("#produto option:selected").text();
					var iframe_produto = $("#iframe_produto").val();

					if(produto != iframe_produto){
						$("div.trigger").removeClass("open");
						$("ul.options").removeClass("open");
						$("#iframe_produto").val(produto);
					}
				});

				$('#estado').blur(function() {
					var id = "estado";
					//closeMessageError(id);

					var estado = $("#estado option:selected").text();
					var iframe_estado = $("#iframe_estado").val();

					if(estado != iframe_estado){
						$("div.trigger").removeClass("open");
						$("ul.options").removeClass("open");
						$("#iframe_estado").val(estado);
					}
				});

				$('#cidade').blur(function() {
					var fabrica = <?=$cod_fabrica;?>;
					var id = "cidade";

					if (fabrica != 175 && $('#linha').val() == "" && $('#cidade').val() != "") {
						id = "linha";
						//closeMessageError(id);
					}else if (fabrica == 175 && $("#produto").val() == "" && $("#cidade").val() != ""){
						id = "produto";
					} else if ($('#estado').val() == "" && $('#cidade').val() != "") {
						id = "estado";
						//closeMessageError(id);
					} else if ($('#cidade').val() != "") {
						//closeMessageError(id);
					}

					var cidade = $("#cidade option:selected").text();
					var iframe_cidade = $("#iframe_cidade").val();
					var iframe_produto = $("#iframe_produto").val();

					if (fabrica != 175){
						if(cidade != iframe_cidade){
							$("div.trigger").removeClass("open");
							$("ul.options").removeClass("open");
							$("#iframe_cidade").val(cidade);
						}
					}else{
						if(cidade != iframe_produto){
							$("div.trigger").removeClass("open");
							$("ul.options").removeClass("open");
							$("#iframe_cidade").val(cidade);
						}
					}
				});

				/* Busca Postos Autorizados */
				$('#btn_acao').click(function() {

					var interno = false;

					<?php if (in_array($cod_fabrica, [167,203])) { ?>  
						
						if (jQuery.inArray(parseInt($("#linha").val()), [1163,1162,1219]) >= 0) {

							interno = true;
								
						} else {

							interno = false;
						}

					<?php } ?>  

					<?php
					if (!in_array($cod_fabrica, array(122,180,181,182))) {
					?>
						$('#box_mapa').hide();
					<?php }else{ ?>
						$('#map-brazil').hide();
					<?php } ?>
					$('#lista_posto').html("");

					if ($('#linha').val() == "") {
						$('#linha-group').addClass('danger');
						$("#msgErro").text('<?php echo traduz('Selecione uma linha de produto!');?>').show();
						return;
					} else {
						closeMessageError();
					}

					if ($('#produto').val() == "") {
						$('#produto-group').addClass('danger');
						$("#msgErro").text('<?php echo traduz('Selecione um produto!');?>').show();
						return;
					} else {
						closeMessageError();
					}

					if ($('#estado').val() == "") {
	
						if (interno == false) {

							$('#estado-group').addClass('danger');
							$("#msgErro").text('<?php echo traduz('Preencha o estado que deseja realizar a busca!');?>').show();
							return;
						}
					} else {
						closeMessageError();
					}

					

					<?php
					if (!in_array($cod_fabrica, array(74,122,125, 131, 152, 175,180,181,182))) {
					?>
		    			if ($("#cidade").val() == null || $('#cidade').val() == "") {

							if (interno == false) {

			    				$('#cidade-group').addClass('danger');
			    				$("#msgErro").text('<?php echo traduz('Preencha a cidade!');?>').show();
			    				return;
		    				}
		    			} else {
		    				closeMessageError();
		    			}
		    		<?php
		    		}
		    		?>

					var linha   = $('#linha').val();
					var estado  = $('#estado').val();
					var cidade  = $('#cidade').val();
					var produto = $('#produto').val();
					var fabrica = <?=$cod_fabrica;?>;
					var pais    = $("#pais").val();

					if (cidade == "") {
						cidade = "sem cidade";
					}
					
					$.ajax({
						url:  window.location.pathname,
						type: "POST",
						dataType: "JSON",
						async: false,
						data:
						{
							linha 	: linha,
							produto : produto,
							estado  : estado,
							cidade  : cidade,
							fabrica : fabrica,
							pais    : pais,
							token   : '<?=$token?>'
						},
						beforeSend: function() {
							loading("show");
							$("#msgErro").text('').hide();
						},
						complete: function(data) {
							loading("hide");

							data = data.responseText;
							if (data != '<?php echo traduz("Nenhum Posto Autorizado localizado para este estado!");?>') {
								info = data.split("*");
								var dados = info[1];

								if (dados.length > 0) {
									if($.inArray(fabrica,[180,181,182]) == -1){
										$('#box_mapa').show();
										addMap(dados);
									}

									if (JSON.parse(dados).length < 2)
										$("#show_all").hide();
									else
										$("#show_all").show();
								}

								$('#lista_posto').html(info[0]);
							} else if (!$('#map-brazil').is(":visible")) {
								$('#map-brazil').show();
							}else{
								<?php
								if (!in_array($cod_fabrica, array(122,125, 131, 152))) {
								?>
								$("#msgErro").text('<?php echo traduz("Nenhum Posto Autorizado localizado para esta cidade!");?>').show();
								<?php }else{ ?>
								$("#msgErro").text(data).show();
								<?php } ?>
							}
						}
					});

					window.parent.postMessage($(document).height()+100, "*");
					scrollPostMessage();
				});


                function pegaIp(){
                    var ip = '';
                    $.ajax({
                        url : "./institucional/pega_ip.php",
                        async:false,
                        dataType : "json",
                        success : function(data){
                            ip = data.ip;
                       }
                    });
                    return ip;
                }

				var showOs = function(ret){
                    var qtde = ret.length;
                    var msg_situacao = "";

                    $.each(ret,function(key,value){

                        if (typeof value != 'object' || typeof value.entity == 'undefined') {
                            return;
                        }

                        var descricao;
                        var marca = value.entity.marca;
                        var fone = value.entity.contato_fone_comercial;
                        fone = fone.replace(/^([0-9][0-9])-/g, "(\$1) ");

                        <?if ($cod_fabrica == 171) {
                        ?>
                        descricao = 'Status';
                        var checkpoint = value.entity.status_checkpoint;
                        switch(checkpoint) {
                            case "1":
                                msg_situacao = "Em análise";
                                break;
                            case "2":
                                msg_situacao = "Em processo de reparo";
                                break;
                            case "3":
                                msg_situacao = "Em processo de reparo";
                                break;
                            case "4":
                                msg_situacao = "Aguardando retirada";
                                break;
                            case "8":
                                msg_situacao = "Em processo de troca";
                                break;
                            case "9":
                                msg_situacao = "Concluído";
                                break;
                            case "14":
                                msg_situacao = "Concluído";
                                break;
                        }
                        <?
                        }else{
                        ?>
                        descricao = 'Situação';
                        var situacao = value.entity.situacao;
                        var equip_aparelho = "APARELHO";

                        <?php if (in_array($cod_fabrica, [175])) { ?>
                        var equip_aparelho = "EQUIPAMENTO";
                        <?php } ?>

                        switch(situacao) {
                            case "1":
                                <?php if (in_array($cod_fabrica, [175])) { ?>
                                	msg_situacao = "ORDEM DE SERVIÇO FINALIZADA. EQUIPAMENTO ENTREGUE."
                                <?php } else { ?>
                                	msg_situacao = "SEU APARELHO ESTÁ PRONTO PARA RETIRADA.";
                                <?php } ?>
                                break;
                            case "2":
                                msg_situacao = "O REPARO DO SEU " + equip_aparelho + " ESTÁ EM ANDAMENTO.";
                                break;
                            case "3":
                                msg_situacao = "O REPARO DO SEU " + equip_aparelho + " ESTÁ EM ANDAMENTO. ENTRE EM CONTATO COM O POSTO AUTORIZADO PARA SABER A DATA PARA RETIRADA.";
                                break;
                            case "4":
                                msg_situacao = "O REPARO DO SEU " + equip_aparelho + " ESTÁ EM ANDAMENTO. QUALQUER DÚVIDA ENTRE EM CONTATO CONOSCO.";
                                break;
                            case "5":
                                msg_situacao = "POR FAVOR ENTRE EM CONTATO CONOSCO PARA MAIS INFORMAÇÕES SOBRE O REPARO DO SEU " + equip_aparelho + ".";
                                break;
                        }
                        <?
                    	}
                        ?>

                        var resultado = "<br>"+
                        				"<ul class='list-group' style='margin-bottom: 0px;'>"+
                        					"<li class='list-group-item panel-heading' style='background-color: #428bca; border-color: #428bca'>"+
	                        					"<h3 style='margin-top:0;margin-bottom:0;font-size:16px;color:inherit'><b>Ordem de serviço: "+ value.sua_os+ "</b>"+
	                        					"</h3>"+
                        					"</li>"+
                        					"<li class='list-group-item' > "+((value.entity.consumidor_revenda == "R") ? "<b>Revenda</b>" : "<b>Consumidor</b>")+": "+((value.entity.consumidor_revenda == "R") ? value.entity.revenda_nome : value.entity.consumidor_nome) +
                        					"</li>"+
                        					"<li class='list-group-item' ><b>Produto:</b> "+ value.entity.descricao_produto+
                        					"</li>"+
                        					"<li class='list-group-item'><b>"+descricao+":</b> "+msg_situacao+
                        					"</li>"+
                        					"<li class='list-group-item'><b>Informações do Posto</b></li>"+
                    						"<li class='list-group-item'><b>Nome:</b> "+value.entity.posto_autorizado+
                    						"</li>"+
                    						"<li class='list-group-item'><b>Endereço:</b>: "+value.entity.endereco + " " + value.entity.numero + " - " + value.entity.cidade + "</li>"+
                    						"<li class='list-group-item'><b>Telefone:</b> "+fone +
                        					"</li>"+
                        				"</ul>"+
                        				"<br>";

                        $("#lista_posto").html("");
                        $("#lista_posto").html(resultado);
                        $("#lista_posto").show();
                        scrollPostMessage();
                    });
                };

				$('#btn_os').on('click', function(){
					$('#btn_os').text('Aguarde...');
					$('#btn_os').prop('disabled', true);
                    var msgErro = [];
                    var data = {};
                    var inputOS = $('#os');
                    var inputCpfCnpj = $('#cpf_cnpj');
                    var ip = pegaIp();
                    data.userIpAddress = ip;
                    data.os = inputOS.val();
                    data.cpf_cnpj = inputCpfCnpj.val();
                    data.recaptcha_response_field = grecaptcha.getResponse();
                    data.token_fabrica = "2ade4b7e60491f28e76f7f0f6c5aa47a";

                    <?php if ($cod_fabrica == 203) { ?>
                    		 data.token_fabrica = "26641c80df8d51f381bfb74d197210f1";
                    <?php } ?>

                    if (data.os.length == 0) {
                        msgErro.push("Informe o número da ordem de serviço");
                    }

                    if (data.recaptcha_response_field.length == 0){
                    	msgErro.push("Preencha o ReCaptcha");
                	}

                    if (data.cpf_cnpj.length == 0) {
                    	msgErro.push("Informe o número do CPF/CNPJ");
                    }

                    if( data.cpf_cnpj.length > 0 &&
                        !data.cpf_cnpj.match(/^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/) &&
                        !data.cpf_cnpj.match(/^[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2}$/)){
                        msgErro.push('CPF/CNPJ Inválido');
                    }
                    if(msgErro.length > 0){
                        $("#msgErro").html(msgErro.join("<br />")).show();
						$('#btn_os').text('Consultar');
						$('#btn_os').prop('disabled', false);
						scrollPostMessage(1);
                    }else{
	                    data.cpf_cnpj = data.cpf_cnpj.replace(/[./-]+/gi,'');
	                    var urlSuffix = '';
	                    for(var index in data){
	                        var value = data[index];
	                        if(value == undefined || value.length == 0)
	                            continue;
	                        var value = data[index].replace(" ","");
	                        urlSuffix += index +'/'+value+'/';
	                    }


	                    var apiLink = 'https://api2.telecontrol.com.br/institucional/statusos/';
	                    var url = apiLink + urlSuffix;
	                    $("#msgErro").html("").hide();
	                    $("#result").hide();
	                    $.ajax({
	                        url : 'institucional/crossDomainProxy.php',
	                        data : {
	                            'apiLink' : url
	                        },
	                        method : 'POST',
	                        success : function(data){
	                            if(data.exception){
	                                $("#msgErro").text(data.message).show();
	                            }else{
	                            	showOs(data);
	                            }
	                        },
	                        error : function(data){
	                        	data = JSON.parse(data.responseText);
	                        	if (data.message.match("caracteres da imagem")) {
	                        		alert(data.message);
	                        	}else{
	                        		$("#msgErro").text(data.message).show();
	                        	}
	                        },
	                        complete : function(data){
								$('#btn_os').text('Consultar');
								$('#btn_os').prop('disabled', false);
	                            grecaptcha.reset();
	                        }
	                    });
	                }
				});
				
				/* Busca Produtos */
				$('#estado').on('change.fs', function() {
					//$('#cidade').find("option").remove();
					//$("#cidade").val("");
					$("#cidade").html("<option value='' ></option>").val("").trigger("change");
					var uf = $('#estado').val();
					var linha = $('#linha').val();
					var produto = $('#produto').val();
					var fabrica = <?=$cod_fabrica;?>;
					var pais = '';

					if(fabrica == 180 || fabrica == 182 || fabrica == 181){
						pais = $("#pais").val();
					}
						
					$('ul.brazil > li.active-region').removeClass('active-region');

					if (uf) {
						$('ul.brazil li#'+uf).addClass('active-region');
					}

					if (fabrica == 175){
						if (produto == '' || produto == undefined){
							alert("Selecione um produto para realizar a pesquisa");
							return;
						}
					}else{
						if (linha == '' || linha == undefined){
							alert("Selecione uma linha para realizar a pesquisa");
							return;
						}
					}

					if ((linha != "" && linha != undefined) || (produto != "" && produto != undefined)) {
						$.ajax({
							url: window.location.pathname,
							type:     'POST',
							dataType: "JSON",
							data:      {
								uf:      uf,
								linha:   linha,
								produto: produto,
								fabrica: fabrica,
								token:   '<?=$token?>',
								pais:    pais
							},
							complete: function(data) {
								data = data.responseText;
								if (data.match('Não há um posto próximo')) {
									$('#sacWurth').show();
								}else{
									if ($('#sacWurth').length > 0) {
										$('#sacWurth').hide();
									}
								}
								$('#cidade').append(data).trigger('update.fs');
							}
						});
					}
				});

				/* Busca Produtos */
				$("#linha-group").on('change.fs', function() {
					$("#estado").trigger('change.fs');
				});

				$("#produto-group").on('change.fs', function() {
					$("#estado").trigger('change.fs');
				});

				$('#map-brazil').cssMap({
					'size' : 340,
					onClick : function(e) {
						var uf = e[0].id;

						var linha = $('#linha').val();

						if (linha == "") {
							alert('Por favor escolha o Tipo de Produto!');
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

	<?php
	if ($cod_fabrica == 126) {
		$body_style = "style='background-color: transparent !important; color: #fff !important;'";
	} elseif ($cod_fabrica == 124) {
        $body_style = "style='background-color: transparent !important; color: #727376 !important;'";
    }
	?>

	<body <?=$body_style?> >
		<!-- Titulo -->
		<?php
		if ($cod_fabrica == 131) {
			$style_container_titulo = "style='background-color: #FFCC00; border-bottom: 1px solid #000000; color: #000000;'";
		}

		if ($cod_fabrica == 126) {
			$style_container_titulo = "style='background-color: transparent !important; border-bottom: 0px solid #000000; color: #E27812;'";
		} elseif ($cod_fabrica == 124) {
            $style_container_titulo = "style='background-color: transparent !important; border-bottom: 0px solid #000000; color: #727376;'";
        }
		?>
		<?php
		if(!in_array($cod_fabrica, array(11, 152, 175, 203)) || $usaTipoPesquisa && in_array($getby, ['all', 'bymapa'])) {
		?>
		<div class="container-fluid" <?=$style_container_titulo?> >
			<div class='row'>
			<?php
                $tit_class = 'titulo';
                if ($cod_fabrica == 124) {
                    $tit_class = '';
                }

				if (isset($img)) {
					echo "<div class='col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-lg-8 col-lg-offset-2 titulo'>";
					echo $img;
					echo '<h3>';
				}else{
					echo "<div class='col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2 text-center {$tit_class}'>";
					echo '<h3 style="text-align: center;" >';
				}
			?>
			    	<?php
		    		switch ($nome_fabrica) {
		    			case 'Wurth':
		    				$nome_fabrica = "Würth";
		    				break;

		    			case 'Atlas Fogoes':
		    				$nome_fabrica = "Atlas Fogões";
		    				break;

				}
				if ($cod_fabrica == '124') {
                    echo 'Encontre uma assistência autorizada na sua região:';
                } else {
				?>
			    		<?php echo traduz('Assistência Técnica');?> <?=($cod_fabrica != 125 && $cod_fabrica != 131 && !empty($nome_fabrica)) ? " - ".$nome_fabrica : "" ?>
                <?php } ?>
				</h3>

			    </div>
			</div>

		</div>
		<?php } ?>

		<?php
		if ($cod_fabrica == 131) {
		?>
			<br />

			<div class="container-fluid">
				<div class='row'>
					<div class='col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2 text-center'>
						<img src="../logos/logo_pressure.png" style="border: 0px; max-height: 70px; max-width: 270px;" />
					</div>
				</div>
			</div>
		<?php
		}
		?>
		<br />
		<input type="hidden" id="iframe_linha" value="">
		<input type="hidden" id="iframe_produto" value="">
		<input type="hidden" id="iframe_estado" value="">
		<input type="hidden" id="iframe_cidade" value="">

		<div class="container-fluid">
			<div class="alert alert-danger col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id='msgErro' role="alert" style="display: none;" >
				<strong><?php echo traduz('Preencha os campos obrigatórios');?></strong>
		    </div>
		</div>

		<!-- Corpo -->
		<div class="container-fluid">

			<div class='row'>
			 	<?php if ($cod_fabrica != 122) { ?>
                <?php if ($cod_fabrica == '124'): ?>
			    <div class='col-xs-12 col-sm-12 col-md-6 col-lg-6'>
                    <div class="css-map-container m340">
                        <img src="img/mapa_gamma.png" width="340" height="355" />
                    </div>
                </div>
                <?php elseif (in_array($cod_fabrica, [180,181,182])):
		
                		foreach ($paises as $siglaPais => $nomePais) {

                			$displayMap = $xxpais == $siglaPais ? "" : "hidden";

                			?>
                			<div style="z-index: 0 !important;min-width: 400px !important;" class="col-xs-12 col-sm-12 col-md-8 col-lg-8 mapa_america" id="<?= $siglaPais ?>" <?= $displayMap ?>></div>
                			<?php
                		}

			else:
                	if (!$usaTipoPesquisa || $usaTipoPesquisa && in_array($getby, ['all', 'bymapa'])) { ?>
			    <div class='col-xs-12 col-sm-12 col-md-6 col-lg-6'>
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
                <?php }
            		endif ?>
			    <?php }else{ 
			    		if (!$usaTipoPesquisa || $usaTipoPesquisa && in_array($getby, ['all', 'bymapa'])) { ?>
			    <div class='col-xs-12 col-sm-12 col-md-6 col-lg-6'>
					<div id="box_mapa" class="col-xs-12 col-sm-10 col-sm-offset-1  col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" style="display: none; text-align: center;" >
						<div id="map_canvas" style="height: 610px; margin-top: 20px; border: 1px solid #CCCCCC;"></div>
						<div class="text-right">
							<br />
							<button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> <?php echo traduz('Mostrar todos os Postos');?></button>
						</div>
					</div>
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
				<?php } 
					}
				?>
			    <div class='col-xs-12 col-sm-10 col-sm-offset-1 col-md-4 col-lg-4'>
					<br />
					<?php if (!$usaTipoPesquisa || $usaTipoPesquisa && in_array($getby, ['all', 'bymapa'])) { ?>
			    	<span class="obrigatorio">* <?php echo traduz('Campos obrigatórios');?></span>

			    	<br /><br />

					<?php 
					if ($cod_fabrica == 175){ 
						$sql = "SELECT p.produto, p.referencia, p.descricao 
						FROM tbl_produto p
						INNER JOIN tbl_linha l ON l.linha = p.linha AND l.fabrica = {$cod_fabrica}
						WHERE p.fabrica_i = {$cod_fabrica} 
						AND p.ativo IS TRUE ORDER BY p.descricao ASC;";
					?>
			    	<div class="form-group" id="produto-group">
						<div class="controls controls-row">
							<label class="control-label" for="produto">Produto</label>
							<div class="asterisco">*</div>
							<div class='fancy-select'>
							<select name="produto" id="produto" autofocus required>
								<option value=""></option>
								<?php	
								$res = pg_query($con, $sql);
								$rows = pg_num_rows($res);

								for ($i = 0; $i < $rows; $i++) {
									$produto = pg_fetch_result($res, $i, 'produto');
									$referencia = ucwords(strtolower(pg_fetch_result($res, $i, "referencia")));
									$descricao  = ucwords(strtolower(pg_fetch_result($res, $i, "descricao")));
						            echo "<option value='{$produto}'>{$referencia} - {$descricao}</option>";
		                        }
			                    ?>
							</select>
							</div>
						</div>
					</div>
			    	<?php }else{ ?>

			    		<script type="text/javascript">

			    		</script>
					<?php if(in_array($cod_fabrica,[180,181,182])){ ?>
						<div class="form-group" id="linha-group">
						<div class="controls controls-row">
							<label class="control-label" for="linha"><?php echo traduz('País');?></label>
							<br>
							<div class="asterisco">*</div>
							<select name="pais" id="pais" class="teste" autofocus required>
								<option value=""></option>
								<?php

								foreach($paises as $key => $value){

									$selected = ($xxpais == $key) ? "selected" : "";

									echo "<option {$selected} value='{$key}'>".traduz($value)."</option>";
		                        }
			                    ?>
							</select>
						</div>
					</div>
					<?php } ?>
	
			    	<div class="form-group" id="linha-group">
						<div class="controls controls-row">
							<label class="control-label" for="linha"><?php echo traduz('Linha');?></label>
							<br>
							<div class="asterisco">*</div>
							<select name="linha" id="linha" class="teste" autofocus required>
								<? if ($cod_fabrica != 152)  { ?>
									<option value=""></option>
								<?
								}

								if ($cod_fabrica == 152) {
									$order = " order by tbl_linha.linha ";
								}else{
									$order = " order by nome ";
								}

								if(in_array($cod_fabrica, [167,203])){
									$cond = " AND tbl_linha.codigo_linha NOT IN('03','04','05','06') ";
								}

								$sql = "SELECT DISTINCT
									CASE
										WHEN tbl_linha.descricao_site IS NOT NULL THEN
											tbl_linha.descricao_site
										ELSE
		                                    					tbl_linha.nome
									END AS nome,
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

									if ($cod_fabrica == 125) {
										$sqlRef = "SELECT nome_comercial
												   FROM tbl_produto
												   WHERE linha = $linha
												   AND fabrica_i = $cod_fabrica";
										$resRef = pg_query($con, $sqlRef);
										$rowsRef = pg_num_rows($resRef);

										if($rowsRef > 0){
											for ($j = 0; $j < $rowsRef; $j++) {
												if (strlen(pg_fetch_result($resRef, $j, 'nome_comercial')) > 0) {
													$refs[] = pg_fetch_result($resRef, $j, 'nome_comercial');
												}
											}

											$linhas = " (".implode(',',$refs).")";
										}
									}
						if( ($cod_fabrica == 180 and $linha == 1155) OR ($cod_fabrica == 181 and $linha = 1158) OR ($cod_fabrica == 182 and $linha == 1161)){
							$select = " selected ";							
						}
						
			
						
		                            echo "<option value='{$linha}' $select >{$nome} ".$linhas."</option>";
		                        }
			                    ?>
							</select>
						</div>
					</div>
					<?php } ?>

				<div class="form-group" id="estado-group">
						<label class="control-label" for="linha"><?php echo traduz('Estado');?></label>
						<div class="asterisco">*</div>
						<div class="controls controls-row">
							<select name="estado" id="estado" >
								<option value=""></option>
<?php
                                                                        if (in_array($cod_fabrica, [180,181,182])) {
                                                                                $sql = "SELECT estado, upper(nome) as nome FROM tbl_estado_exterior WHERE pais='{$xxpais}' AND visivel IS TRUE ORDER BY nome ASC;";
                                                                                $res = pg_query($con,$sql);
                                                                                if (pg_num_rows($res) > 0 AND in_array($cod_fabrica, [181])) {
                                                                                        foreach (pg_fetch_all($res) as $key => $row) {
                                                                                                echo '<option value="'.$row['estado'].'">'.retira_acentos($row['nome']).'</option>';
                                                                                        }

                                                                                }
                                                                        } else {
                                                                ?>

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
								<?php }?>
							</select>
						</div>
					</div>

					<?php
						$msgCidade = '';
						if (in_array($cod_fabrica, array(122,125, 131, 152))) {
							$msgCidade = '(Se sua cidade não aparecer na lista abaixo deixe o campo em branco e clique em pesquisar)';
						}
					?>

					<div class="form-group" id="cidade-group">
						<label class="control-label" for="linha"><?php echo traduz('Cidade');?> <span class='texto_cidade'><?=$msgCidade ?></span></label>
						<?php
						if (!in_array($cod_fabrica, array(122,125, 131, 152,180,181,182))) {
						?>
							<div class="asterisco">*</div>
						<?php
						}
						?>
						<div class="controls controls-row" style="z-index: 2;">
							<select name="cidade" id="cidade" >
								<option value=""></option>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-3 col-lg-3 col-sm-3">
							<button class="btn btn-default" id="btn_acao" type="button"><?php echo traduz('Pesquisar');?></button> &nbsp; <span id="loading"></span>
						</div>
						<div class="col-xs-6 col-lg-9 col-sm-9">
							<label id= "sacWurth" style="color: red; display: none">Favor ligar para o SAC (11)4613-1900</label>
						</div>
					</div>
					<?php } ?>
					<?php if (in_array($cod_fabrica, array(122,163,167,171,174,203)) || $usaTipoPesquisa && in_array($getby, ['all', 'byos'])) { ?>
					<label class="control-label">
						<?php
							if (in_array($cod_fabrica, [198])) {
								echo '<span style="font-size: 20px;">Status de Serviço</span> <br />';
								echo 'Após o atendimento, o status da sua solicitação pode ser acompanhado aqui.';
							} else {
								echo ($usaTipoPesquisa && in_array($getby, ['byos'])) ? 'Consulte uma Ordem de Serviço' : 'Ou consulte o andamento do serviço';
							}
						?>
					</label>
					<div class="col-*-12">
						<div class="well">
		                    <div class="container ">
		                        <div class="row">
		                        	<div class="visible-lg-block hidden-sm visible-md-block">
			                            <div class="row">
			                            	<div class="col-md-3">
			                                	<label for="os">N. da Ordem de Serviço</label>
			                                </div>
			                                <div class="col-md-4">
			                                	<label for="cpf_cnpj">CPF / CNPJ</label>
			                                </div>
			                            </div>
			                            <div class="row">
			                                <div class="col-md-3">
			                                    <input type="text"  id="os" name="os" class="form-control" />
			                                </div>
			                                <div class="col-md-3">
			                                	<input type="text"  name="cpf_cnpj" id="cpf_cnpj" class="form-control"/>
			                                </div>
			                            </div>
		                            </div>
		                            <div class="visible-xs-* visible-sm-* hidden-lg hidden-md">
		                            	<div class="row">
											<div class="col-xs-4 col-sm-9">
				                              	<label for="os">N. da Ordem de Serviço</label>
				                              	<input type="text"  id="os" name="os" class="form-control" />
											</div>
			                            </div>
			                            <div class="row">
				                            <div class="col-xs-4 col-sm-9">
				                            	<label for="cpf_cnpj">CPF / CNPJ</label>
				                            	<input type="text"  name="cpf_cnpj" id="cpf_cnpj" class="form-control"/>
				                            </div>
			                            </div>
		                            </div>
		                            <div class="row">
		                            	<div id="reCaptcha">Carregando reCaptcha</div>
		                            </div>
		                            <div class="row">
		                            	<button class="btn btn-default" id='btn_os' data-loading-text="Consultando...">Consultar</button>
		                            </div>
		                        </div>
		                    </div>
		                </div>
	                </div>
					<?php } ?>
			    </div>
			</div>
		</div>

		<div style="clear: both;"></div>
		<?php if ($cod_fabrica != 122) { ?>
		<div id="box_mapa" class="col-xs-12 col-sm-10 col-sm-offset-1  col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" style="display: none;text-align: center;z-index: 1;" >
			<div id="map_canvas" style="height: 450px; margin-top: 50px; border: 1px solid #CCCCCC;"></div>
			<div class="text-right">
				<br />
				<button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
			</div>
		</div>
		<?php }?>

		<div style="clear: both;"></div>

		<div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id="lista_posto" style="padding-bottom: 100px;"></div>

		<div class="scroll-xs visible-xs-block" ></div>
	</body>
</html>
