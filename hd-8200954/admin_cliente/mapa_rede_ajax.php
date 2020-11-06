<?php

include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';

#error_reporting(E_ALL);
if(isset($_GET['latlon'])){

	$latlonIndex =  $_GET['latlon']; 
	$callcenter = $_GET['callcenter'];

	if (!function_exists('anti_injection')) {
		function anti_injection($string) {
			$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
			return strtr(strip_tags(trim($string)), $a_limpa);
		}
	}

	$cidade     = anti_injection($_GET['cidade']);
	$estado     = anti_injection($_GET['estado']);
	$pais       = anti_injection($_GET['pais']);
	$cep_orig   = anti_injection($_GET['cep']);
	$linha      = anti_injection($_GET['linha']);
	$consumidor = anti_injection($_GET['consumidor']);
	$estado     = ((!$estado or $estado == '00') and $consumidor) ? substr($consumidor, -2) : $estado;
	$nome_cliente = anti_injection($_GET['nome']);

	$sql_columns = array(
		"tbl_posto.posto",
		"tbl_posto_fabrica.codigo_posto",
		"UPPER(TRIM(tbl_posto.nome)) AS nome",
		"tbl_posto_fabrica.nome_fantasia",
		"UPPER(TRIM(tbl_posto_fabrica.contato_endereco)) AS endereco",
		"tbl_posto_fabrica.contato_numero AS numero",
		"UPPER(TRIM(tbl_posto_fabrica.contato_bairro)) AS bairro",
		"UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) AS cidade",
		"tbl_posto_fabrica.contato_estado AS estado",
		"tbl_posto_fabrica.contato_cep AS cep",
		"LOWER(TRIM(tbl_posto_fabrica.contato_email)) AS email",
		"tbl_posto_fabrica.contato_fone_comercial AS telefone",
		"tbl_posto.latitude AS lng",
		"tbl_posto.longitude AS lat",
	);

	$sql_join = array(
		"JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica",
	);

	$sql_where = array(
		"tbl_posto_fabrica.divulgar_consumidor IS TRUE",
		"AND tbl_posto.posto <> 6359",
	);

	$sql_orderby = array(
		"tbl_posto_fabrica.contato_pais", 
		"tbl_posto_fabrica.contato_estado",
		"tbl_posto_fabrica.contato_cidade",
		"tbl_posto_fabrica.contato_cep",
	);

	$sql_limit = null;

	if($login_fabrica == 30){
		array_push($sql_where, "AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'");
	}

	if ($login_fabrica <> 43 && $login_fabrica <> 30) {
		array_push($sql_where, "AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'");
	}

	if (strlen($pais) > 0) {
		array_push($sql_where, "AND tbl_posto_fabrica.contato_pais = '$pais'");
	}

	if (strlen($estado) > 0) {
		if($estado == "00"){
			$estado = "";
		}else if($estado == "BR-CO"){
			$estado = " IN ('GO','MS','MT','DF') ";
		}else if($estado == "BR-NE"){
			$estado = " IN('SE','AL','RN','MA','PE','PB','CE','PI','BA') ";
		}else if($estado == "BR-N"){
			$estado = " IN('TO','PA','AP','RR','AM','AC','RO') ";
		}else if($estado == "BR-SUL"){
			$estado = " IN('PR','SC','RS') ";
		}else if($estado == "SP-CAPITAL"){
			$estado = " IN('SP') ";
		}else if($estado == "SP-INTERIOR"){
			$estado = " IN('SP') ";
		}else {
			$estado = " = '$estado' ";
		}

	}

	if (strlen($cidade) > 0) {
		$cidade = acentos($cidade);
		$info   .= "&nbsp;&nbsp;<b>Cidade:</b> $cidade";
		array_push($sql_where, "AND TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN1') ~* TO_ASCII('^$cidade$', 'LATIN1')");
	}

	if (strlen($linha) > 0) {
		$sql = "SELECT nome
				FROM tbl_linha
				WHERE 
					tbl_linha.fabrica = $login_fabrica
					AND tbl_linha.linha   = $linha
				ORDER BY 
					tbl_linha.nome";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$aux_nome = pg_fetch_result($res, 0, 'nome');
			$info = "<br /><b>Linha: </b>$aux_nome\n";
		}

		array_push($sql_where, "AND tbl_posto.posto IN (SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto) WHERE linha = $linha AND  fabrica = $login_fabrica)");
			
		if ( $login_fabrica == 24 ){
			array_push($sql_columns, "tbl_posto_linha.divulgar_consumidor");
			array_push($sql_join, "JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha = $linha");
			array_push($sql_where, "AND tbl_posto_linha.divulgar_consumidor IS TRUE");
		}
	}

	$sql = "SELECT
				" . implode(", \n", $sql_columns) . "
			FROM tbl_posto
				" . implode(" \n", $sql_join) . "
			WHERE
				" . implode(" \n", $sql_where) . "
			ORDER BY
				" . implode(", \n", $sql_orderby);

}

/*   Fim   */

else if(isset($_GET['pais'])){

	$pais = $_GET['pais'];
	$estado = $_GET['estado'];
	$cidade = $_GET['cidade'];
	$fabrica = $login_fabrica; 

	if($estado == "00"){
		$estado = "";
	}else if($estado == "BR-CO"){
		$estado = " IN ('GO','MS','MT','DF') ";
	}else if($estado == "BR-NE"){
		$estado = " IN('SE','AL','RN','MA','PE','PB','CE','PI','BA') ";
	}else if($estado == "BR-N"){
		$estado = " IN('TO','PA','AP','RR','AM','AC','RO') ";
	}else if($estado == "BR-SUL"){
		$estado = " IN('PR','SC','RS') ";
	}else if($estado == "SP-CAPITAL"){
		$estado = " IN('SP') ";
	}else if($estado == "SP-INTERIOR"){
		$estado = " IN('SP') ";
	}else {
		$estado = " = '$estado' ";
	}

	$sql = "SELECT tbl_posto.posto AS posto,
				   UPPER(TRIM (tbl_posto.nome)) AS nome,
				   UPPER( TRIM (tbl_posto_fabrica.contato_endereco)) AS endereco,
				   tbl_posto_fabrica.contato_numero AS numero,
				   LOWER( TRIM(tbl_posto_fabrica.contato_email)) AS email,
				   tbl_posto_fabrica.contato_fone_comercial AS telefone,
				   UPPER(TRIM(tbl_posto_fabrica.contato_bairro)) AS bairro,
				   UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) AS cidade,
				   tbl_posto_fabrica.contato_cep AS cep,
				   tbl_posto_fabrica.contato_estado AS estado,
				   tbl_posto.latitude AS lng,
				   tbl_posto.longitude AS lat
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica USING (posto)
			WHERE credenciamento = 'CREDENCIADO'
			   AND posto NOT IN(6359,20462)
			   AND tipo_posto <> 163
			   AND divulgar_consumidor IS TRUE
			   AND fabrica = $fabrica
			   AND tbl_posto.pais = '$pais' ";
	$sql .= ($estado == "")	? $estado : " AND tbl_posto_fabrica.contato_estado $estado";
	$sql.= ($cidade == "") ? $cidade : "AND tbl_posto_fabrica.contato_cidade = '$cidade' ";
	$sql.= "ORDER BY tbl_posto_fabrica.contato_pais, tbl_posto_fabrica.contato_estado,tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_cep";

}

function acentos ($string) {
	$array1 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
	$array2 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" );
	$string = str_replace($array1, $array2, $string);


	$array1 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
	$array2 = array("Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" ,"Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
	$string = str_replace($array1, $array2, $string);

	$array1 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
	$string = str_replace($array1, $array2, $string);

	return $string;
}

/* echo "Cidade: ".$cidade."<br />";
echo "Estado: ".$estado."<br />";
echo "Pais: ".$pais."<br />";
echo "CEP: ".$cep."<br />"; */

function formatCEP($cepString){
	$cepString = str_replace("-", "", $cepString);
	$cepString = str_replace(".", "", $cepString);
	$cepString = str_replace(",", "", $cepString);
	$antes = substr($cepString, 0, 5);
	$depois = substr($cepString, 5);
	$cepString = $antes."-".$depois;
	return $cepString;
}

function formatEndereco($end){
	$end = str_replace("R. ", "", $end);
	$end = str_replace(",,", "+", $end);
	$end = str_replace(", ,", "+", $end);
	$end = str_replace(" ", "+", $end);
	return $end;
}

function getLatLonConsumidor($address){

	$geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false');

	$output = json_decode($geocode);

	$lat = $output->results[0]->geometry->location->lat;
	$lon = $output->results[0]->geometry->location->lng;

	$latLon = $lat."@".$lon;

	return $latLon;

}

function getPostoMaisProximo($arr = array()){
	$posto = 100000;
	$mais_proximo = "";
	foreach ($arr as $key => $value) {
		$parte = explode('|', $value);
		if($parte[0] < $posto){
			$posto = $parte[0];
			$mais_proximo = $parte[1];
		}
	}

	return $mais_proximo;
}

if(!$consumidor == ""){
	$local = formatEndereco($consumidor);
}else{
	$local = formatCEP($cep); 
}

 // $latLonConsumidor = getLatLonConsumidor($local);
$latlonIndex = str_replace("(", "", $latlonIndex);
$latlonIndex = str_replace(")", "", $latlonIndex);
$parte = explode(',', $latlonIndex);

$from_lat = $parte[0];
$from_lon = $parte[1]; 

/* Calcula a distancia entre pontos */
function compute_distance($from_lat, $from_lon, $to_lat, $to_lon, $units = 'K'){
    $units = strtoupper(substr(trim($units),0,1));
    // ENSURE THAT ALL ARE FLOATING POINT VALUES
    $from_lat = floatval($from_lat);
    $from_lon = floatval($from_lon);
    $to_lat   = floatval($to_lat);
    $to_lon   = floatval($to_lon);

    // IF THE SAME POINT
    if ( ($from_lat == $to_lat) && ($from_lon == $to_lon) ){
        return 0.0;
    }

    // COMPUTE THE DISTANCE WITH THE HAVERSINE FORMULA
    $distance = acos( sin(deg2rad($from_lat)) * sin(deg2rad($to_lat)) + cos(deg2rad($from_lat)) * cos(deg2rad($to_lat)) * cos(deg2rad($from_lon - $to_lon)));

    $distance = rad2deg($distance);

    // DISTANCE IN MILES AND KM - ADD OTHERS IF NEEDED
    $miles = (float) $distance * 69.0;
    $km    = (float) $miles * 1.61;

    // RETURN MILES
    if ($units == 'M') return round($miles,1);

    // RETURN KILOMETERS = MILES * 1.61
    if ($units == 'K') return round($km,2);
}

$res = pg_query($con, $sql);

$bubble_sort = array();
$bubble_data = array();
$distacia_sort = array();

if($res){
	while ($result = pg_fetch_object($res)) {

		/* Encurta Nome */
		$nomeTitle = $result->nome;
		if(strlen($result->nome) > 27){
			$result->nome = substr($result->nome, 0, 25)."...";
		}
	
		$endereco = "";
		if($result->endereco != ""){ $endereco .= $result->endereco; }
		if($result->numero != ""){ $endereco .= " ".$result->numero; }
		if($result->bairro != ""){ $endereco .= ", ".$result->bairro; }
		if($result->cidade != ""){ $endereco .= ", ".$result->cidade; }
		if($result->estado != ""){ $endereco .= ", ".$result->estado; }
		
		$endereco = str_replace("?", "", $endereco);
		$endereco = str_replace(".", "", $endereco);
		$endereco = str_replace("+", "", $endereco);
		$endereco = str_replace("-", "", $endereco);

		$enderecoTitle = $result->endereco." ".$result->numero;
		if(strlen($result->endereco) > 27){
			$result->endereco = substr($result->endereco, 0, 25)."...";
			$result->endereco = $result->endereco." ".$result->numero;
		}else{
			$result->endereco = $result->endereco." ".$result->numero;
		}

		unset($distacia_cliente);

		if(strlen($result->lat) > 0 and strlen($result->lng) > 0){
			$distacia_cliente = compute_distance($from_lat, $from_lon, $result->lat, $result->lng);
		}

		if(isset($callcenter)){

			if($distacia_cliente != "" and $distacia_cliente < 100){

				$bubble_sort[] = $distacia_cliente;

				$distacia_sort[] = $distacia_cliente."|".$result->lat.",".$result->lng;

				$distancia_total = number_format($distacia_cliente, 3, '.', '');

				$unit = ($distancia_total >= 1) ? "KM" : "Metros";

				$enderecoPosto = "";
				$enderecoPosto = $result->endereco.", ".$result->bairro.", ".$result->cidade.", ".$result->estado;

				$bubble_data[] = "<tr id='$result->posto' class='posto' >
					<td style='text-align: left;' rel='nome_posto'>
						<input type='hidden' name='lat' value='$result->lat' />
						<input type='hidden' name='lng' value='$result->lng' />
						<input type='hidden' name='distacia_cliente' value='$distacia_cliente' />
						<a href='#' onclick='window.opener.informacoesPosto($result->posto, \"$result->cidade\");self.close();' title='".$nomeTitle."'>".$result->nome."</a>
					</td>
					<td style='text-align: left;' rel='endereco'>
						<span title='".$enderecoTitle."'>".$result->endereco."</span>
					</td>
					<td rel='bairro'>
						".$result->bairro."
					</td>
					<td rel='cidade'>
						".$result->cidade."
					</td>
					<td rel='estado'>
						".$result->estado."
					</td>
					<td rel='cep'>
						".$result->cep."
					</td>
					<td rel='email'>
						".$result->email."
					</td>
					<td rel='telefone'>
						".$result->telefone."
					</td>
					<!-- <td>
						".number_format($distacia_cliente, 3, '.', '')." ".$unit."
					</td>  -->
					<td>
						<a href='#' onclick=\"localizar('$result->lat', '$result->lng', '$endereco')\" >Localizar</a>
					</td>
					<td>
						<a href='#' onclick=\"rota('$result->lat , $result->lng', '$endereco', '$enderecoPosto')\" >Rota</a>
					</td>
				</tr>";

			}

		}else{
		
			$id_posto = ($result->lat == "" || $result->lng == "") ? $result->posto : "''";

			$bubble_data[] = "<tr id='$result->posto' class='posto' >
				<td style='text-align: left;' rel='nome_posto' title='".$nomeTitle."'>
					<input type='hidden' name='lat' value='$result->lat' />
					<input type='hidden' name='lng' value='$result->lng' />
					<input type='hidden' name='distacia_cliente' value='$distacia_cliente' title='".$nomeTitle."' />
					".$result->nome."
				</td>
				<td style='text-align: left;' rel='endereco'>
					<span title='".$enderecoTitle."'>".$result->endereco."</span>
				</td>
				<td rel='bairro'>
					".$result->bairro."
				</td>
				<td rel='cidade'>
					".$result->cidade."
				</td>
				<td rel='estado'>
					".$result->estado."
				</td>
				<td rel='cep'>
					".$result->cep."
				</td>
				<td rel='email'>
					".$result->email."
				</td>
				<td rel='telefone'>
					".$result->telefone."
				</td>	
				<td>
					<a href='#' onclick=\"localizar('$result->lat', '$result->lng', '$endereco', $id_posto)\" >Localizar</a>
				</td>
			</tr>";

		}

	}
}else{
	echo "Nenhum posto localizado!";
}

if(isset($callcenter)){

	/* Orderna os valores mantendo a associaзгo entre valores e chaves */
	asort($bubble_sort);

	/* Define a variavel com a latitude e longitude do posto mais proximo */
	$posto_mais_proximo = getPostoMaisProximo($distacia_sort);

	echo $posto_mais_proximo."*";

	// Lista os Postos 

	$cont = 0;

	// Tabela Excel

	$table_excel = "";

	$table_excel .= "
			<table>
				<thead>
					<tr>
						<td colspan='9' align='center'><strong>CIDADES MAIS PRУXIMAS</strong></td>
					</tr>
					<tr>
						<th>
							Nome do Posto
						</th>
						<th>
							Endereзo
						</th>
						<th>
							Bairro
						</th>
						<th>
							Cidade
						</th>
						<th>
							Estado
						</th>
						<th>
							CEP
						</th>
						<th>
							Email
						</th>
						<th>
							Telefone
						</th>
						<th>
							Distвncia
						</th>
					</tr>
				</thead>
				<tbody>";


	foreach ($bubble_sort as $key => $value) {
		if($callcenter == true){
			//Traz os 5 postos mais proximos 
			if($cont < 5){
				echo $bubble_data[$key];
				$table_excel .= $bubble_data[$key];
			}
			$cont++;
		}else{
			echo $bubble_data[$key];
		}
	} 

	$table_excel .= "
				</tbody>
			</table>";

	if($login_fabrica == 52){

		$table_excel = "";

		/* Comeзo */
		$table_excel .= "<table>";

		/* Monta Cabeзalho */
		$table_excel .= "
		<tr>
			<td colspan='10' align='center'><strong>CIDADES MAIS PRУXIMAS</strong></td>
		</tr>
		<tr class='titulo_tabela' style='margin-top:20px;'>
			<td>Nome do Posto</td>
			<td>Nome Fantasia</td>
			<td>Endereзo</td>
			<td>Bairro</td>
			<td>Cidade</td>
			<td>Estado</td>
			<td>CEP</td>
			<td>Email</td>
			<td>Fone</td>
			<td>KM</td>
		</tr>
		";

		$consumidor_estado = $_GET['consumidor_estado'];
		$consumidor_cidade = $_GET['consumidor_cidade'];

		$sql = "SELECT
				tbl_posto_fabrica.codigo_posto,
				UPPER(TRIM(tbl_posto_fabrica.contato_endereco)) AS endereco,
				UPPER(TRIM(tbl_posto_fabrica.contato_bairro)) AS bairro,
				UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) AS cidade,
				tbl_posto_fabrica.contato_estado AS estado,
				tbl_posto_fabrica.contato_cep AS cep,
				LOWER(TRIM(tbl_posto_fabrica.contato_email)) as email,
				tbl_posto_fabrica.nome_fantasia,
			   	tbl_posto_fabrica_ibge.km,
			   	tbl_posto.nome AS nome,
				tbl_posto_fabrica.contato_fone_comercial AS fone
				
				
				FROM
				tbl_posto_fabrica
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica.fabrica=tbl_posto_fabrica_ibge.fabrica 
				
				AND 
				tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
				JOIN tbl_ibge ON tbl_posto_fabrica_ibge.cod_ibge = tbl_ibge.cod_ibge
				
				WHERE
				tbl_posto_fabrica.fabrica={$login_fabrica}
				AND tbl_ibge.estado=UPPER('{$consumidor_estado}')
				AND tbl_ibge.cidade_pesquisa=UPPER('{$consumidor_cidade}')
				
				ORDER BY
				tbl_posto_fabrica_ibge.km";


		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0){

			$i = 0;
			while ($resultado = pg_fetch_array($res)) {
				$bgcolor = $i % 2 == 0 ? "#eeeeff" : "#ffffff" ;
				
				$cep_atendidas = $resultado['cep'];
				$cep_atendidas = preg_replace('/(\d{2})(\d{3})(\d{3})/','$1$2-$3',$cep_atendidas);
				$table_excel .= "
				<tr bgcolor='{$bgcolor}' style='height:22px; font-size: 10px' >
					<td>
						<a class='km_distancia' 
							km='{$resultado['km']}' 
							cod_posto='{$resultado['codigo_posto']}'  
							nome_posto='{$resultado['nome']}'
							email_posto='{$resultado['email']}'
							fone_posto= '{$resultado['fone']}'
							href'#'>{$resultado['nome']}
						</a>
					</td>
					
					<td>{$resultado['nome_fantasia']}</td>
					<td>{$resultado['endereco']}</td>
					<td>{$resultado['bairro']}</td>
					<td>{$resultado['cidade']}</td>
					<td align='center'>{$resultado['estado']}</td>
					<td>{$cep_atendidas}</td>
					<td align='right'>{$resultado['email']}</td>
					<td>{$resultado['fone']}</td>
					<td>
						<a class='km_distancia'
							km='{$resultado['km']}' 
							cod_posto='{$resultado['codigo_posto']}'  
							nome_posto='{$resultado['nome']}'
							email_posto='{$resultado['email']}'
							fone_posto= '{$resultado['fone']}'
							href'#'>{$resultado['km']}
						</a>
					</td>
				</tr>
				";
				$i++;
			}
		}

		/* Fim */
		$table_excel .= "</table>";
	}

	/* Gera o Excel */
	$caminho = "xls/relatorio-mapa-rede-$login_fabrica.xls";
	$fp 	 = fopen ($caminho,"w");

	$fabricas_geram_excel = array(86, 81, 114);
	// Inicializa o arquivo XLS e Grava
	if(in_array($login_fabrica, $fabricas_geram_excel)){
		fwrite($fp, $table_excel);
		fclose($fp);
	}

}else{

	// Tabela Excel

	$table_excel = "";

	$table_excel .= "
			<table>
				<thead>
					<tr>
						<td colspan='9' align='center'><strong>CIDADES MAIS PRУXIMAS</strong></td>
					</tr>
					<tr>
						<th>
							Nome do Posto
						</th>
						<th>
							Endereзo
						</th>
						<th>
							Bairro
						</th>
						<th>
							Cidade
						</th>
						<th>
							Estado
						</th>
						<th>
							CEP
						</th>
						<th>
							Email
						</th>
						<th>
							Telefone
						</th>
					</tr>
				</thead>
				<tbody>";

	foreach ($bubble_data as $key => $value) {
		echo $value;
		$table_excel .= $value;
	} 

	$table_excel .= "</table>";

	/* Gera o Excel */
	$caminho = "xls/relatorio-mapa-rede-$login_fabrica.xls";
	$fp 	 = fopen ($caminho,"w");

	$fabricas_geram_excel = array(86, 81, 114);

	// Inicializa o arquivo XLS e Grava
	if(in_array($login_fabrica, $fabricas_geram_excel)){
		fwrite($fp, $table_excel);
		fclose($fp);
	}

}


?>
