<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

// PROCESSO AUTOCOMPLETE PARA LOCALIZAÇÃO DO BANCO
$busca = $_GET["q"];
	$sql = "SELECT codigo,
				nome, banco
			FROM tbl_banco
			WHERE (codigo ILIKE '%$busca%' OR nome ILIKE '%$busca%')";
	$res = pg_exec($con, $sql);
	$resultado = array();
	for ($i = 0; $i < pg_num_rows($res); $i++) {
		$id_banco     = pg_result($res, $i, banco);
		$codigo_banco = pg_result($res, $i, codigo);
		$nome_banco   = pg_result($res, $i, nome);
		$resultado[]  = "$codigo_banco|$nome_banco|$id_banco";
	}
$resultado = implode("\n", $resultado);
echo $resultado;
die;

?>

