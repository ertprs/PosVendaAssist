<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios="info_tecnica,call_center";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

if (filter_input(INPUT_POST,'ajax') && filter_input(INPUT_POST,'acao') == 'consulta_bairro') {
    $estado = filter_input(INPUT_POST,"estado");
    $cidade = filter_input(INPUT_POST,"cidade");

    $sql = "
        SELECT  DISTINCT
                bairro
        FROM    tbl_cep
        WHERE   cidade = '$cidade'
        AND     estado = '$estado'
    ";
    $res = pg_query($con,$sql);

    while ($result = pg_fetch_object($res)) {
        $bairros[] = array("bairro" => $result->bairro);
    }

    echo json_encode(array("bairros" => $bairros));
}

//--====== AJAX CIDADES ============================================================================-
if (filter_input(INPUT_POST, 'ajax') && filter_input(INPUT_POST, 'acao') == 'consulta_cidades_ibge') {
	$uf = filter_input(INPUT_POST, 'uf');

	$qCidades = "SELECT DISTINCT ON (UPPER(distrito)) fn_retira_especiais(distrito) AS distrito,
					id AS cod_cidade,
					latitude,
					longitude
				FROM tbl_ibge_completa
				WHERE uf = '{$uf}'
				AND (tipo = 'URBANO' OR tipo IS NULL) 
				ORDER BY UPPER(distrito);";
	$rCidades = pg_query($con, $qCidades);
	$cidades = pg_fetch_all($rCidades);

	die(json_encode(['cidades' => $cidades]));
}


if (filter_input(INPUT_POST,'ajax') && filter_input(INPUT_POST,'acao') == 'consulta_cidades') {

	if (filter_input(INPUT_POST,"uf")) {
		$uf = filter_input(INPUT_POST,"uf");
	}
	$sql = "SELECT DISTINCT UPPER(TRIM(nome)) AS cidade, cidade AS cod_cidade
		      FROM tbl_cidade
			 WHERE estado = '$uf'
			   AND (cod_ibge IS NOT NULL OR cep IS NOT NULL)
		  ORDER BY cidade";
	$res = pg_query($con,$sql);

	$arr_cidades = array();

	for ($i = 0; $i < pg_num_rows($res); $i++) {

		$cidade = pg_fetch_result($res, $i, "cidade");
		$cod_cidade = pg_fetch_result($res, $i, "cod_cidade");

		$arr_cidades[] = array("cidade" => utf8_encode($cidade), "cod_cidade" => $cod_cidade);

	}

	exit(json_encode(array("cidades" => $arr_cidades)));

}
//--====== AJAX ESTADOS ============================================================================-
if (filter_input(INPUT_POST,'ajax') && filter_input(INPUT_POST,'acao') == 'consulta_estados') {

	if(array_key_exists('estados', $_GET)){
		$regiao = $_GET['estados'];
	}else{
		echo json_encode(array("messageError" => utf8_encode("Informe uma região")));
	}

	switch ($regiao) {
		case "PR, RS, SC":
			$estado[] = array("cod_estado" => "PR", "estado" => utf8_encode("Paraná"));
			$estado[] = array("cod_estado" => "RS", "estado" => utf8_encode("Rio Grande do Sul"));
			$estado[] = array("cod_estado" => "SC", "estado" => utf8_encode("Santa Catarina'"));
			break;

		case "AL, BA, CE, MA, PB, PE, PI, RN, SE":
			$estado[] = array("cod_estado" => "AL", "estado" => utf8_encode("Alagoas"));
			$estado[] = array("cod_estado" => "BA", "estado" => utf8_encode("Bahia"));
			$estado[] = array("cod_estado" => "CE", "estado" => utf8_encode("Ceará"));
			$estado[] = array("cod_estado" => "MA", "estado" => utf8_encode("Maranhão"));
			$estado[] = array("cod_estado" => "PB", "estado" => utf8_encode("Paraíba"));
			$estado[] = array("cod_estado" => "PE", "estado" => utf8_encode("Pernambuco"));
			$estado[] = array("cod_estado" => "PI", "estado" => utf8_encode("Piaui"));
			$estado[] = array("cod_estado" => "RN", "estado" => utf8_encode("Rio Grande do Norte"));
			$estado[] = array("cod_estado" => "SE", "estado" => utf8_encode("Sergipe"));
			break;

		case "ES, MG, RJ, SP":
			$estado[] = array("cod_estado" => "ES", "estado" => utf8_encode("Espirito Santo"));
			$estado[] = array("cod_estado" => "MG", "estado" => utf8_encode("Minas Gerais"));
			$estado[] = array("cod_estado" => "RJ", "estado" => utf8_encode("Rio de Janeiro"));
			$estado[] = array("cod_estado" => "SP", "estado" => utf8_encode("São Paulo"));
			break;

		case "DF, GO, MT, MS":
			$estado[] = array("cod_estado" => "DF", "estado" => utf8_encode("Distrito Federal"));
			$estado[] = array("cod_estado" => "GO", "estado" => utf8_encode("Goiás"));
			$estado[] = array("cod_estado" => "MT", "estado" => utf8_encode("Mato Grosso"));
			$estado[] = array("cod_estado" => "MS", "estado" => utf8_encode("Mato Grosso do Sul"));
			break;

		case "AC, AP, AM, PA, RO, RR, TO":
			$estado[] = array("cod_estado" => "AC", "estado" => utf8_encode("Acre"));
			$estado[] = array("cod_estado" => "AP", "estado" => utf8_encode("Amapá"));
			$estado[] = array("cod_estado" => "AM", "estado" => utf8_encode("Amazonas"));
			$estado[] = array("cod_estado" => "PA", "estado" => utf8_encode("Pará"));
			$estado[] = array("cod_estado" => "RO", "estado" => utf8_encode("Rondônia"));
			$estado[] = array("cod_estado" => "RR", "estado" => utf8_encode("Roraima"));
			$estado[] = array("cod_estado" => "TO", "estado" => utf8_encode("Tocantins"));
			break;
	}

	echo json_encode($estado);
	exit;
}


if (filter_input(INPUT_POST,'consultaCity') == 'sim') {

	$pais = filter_input(INPUT_POST, 'pais');
	$provincia = filter_input(INPUT_POST, 'provincia');
	if(strlen(trim($provincia)) > 0 ) {
		$cond = " and estado_exterior = '".utf8_decode($provincia)."' ";
	}
	if(!empty($pais)) {
		$sql = "SELECT DISTINCT UPPER(TRIM(nome)) AS cidade, cidade AS cod_cidade
			FROM tbl_cidade
			WHERE pais ='$pais'
			AND length(nome) > 2
			$cond
			ORDER BY 1";
		$res = pg_query($con,$sql);

		$arr_cidades = array();

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$cidade = pg_fetch_result($res, $i, "cidade");
			$cod_cidade = pg_fetch_result($res, $i, "cod_cidade");

			$cidade = mb_detect_encoding($cidade, 'UTF-8', true) ? utf8_decode($cidade) : $cidade;
			$arr_cidades[] = array("cidade" => utf8_encode($cidade), "cod_cidade" => $cod_cidade);

		}

		exit(json_encode($arr_cidades));

	}
}

if (filter_input(INPUT_POST,'consulta_estado_provincia') == 'sim') {

	if (filter_input(INPUT_POST,"pais")) {
		$pais = filter_input(INPUT_POST,"pais");
		
		$sql = "SELECT DISTINCT UPPER(TRIM(nome)) AS estado_nome, estado AS estado
			      FROM tbl_estado_exterior
				 WHERE pais = '$pais'
				   AND visivel
			  ORDER BY estado_nome";
		$res = pg_query($con,$sql);

		$arr_estados = array();

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$estado = pg_fetch_result($res, $i, "estado");
			$estado_nome = pg_fetch_result($res, $i, "estado_nome");

			$arr_estados[] = array("estado" => utf8_encode($estado), "estado_nome" => $estado_nome);

		}
	}

	exit(json_encode($arr_estados));

}



