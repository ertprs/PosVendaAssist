<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$estado   = strtoupper($_GET["estado"]);
$cidade   = utf8_decode(trim($_GET["q"]));

if (!empty($estado) && strlen($cidade) >= 3) {
	$sql = "SELECT DISTINCT * FROM (
				SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) ~ UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')
				UNION (
					SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) ~ UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')
				)
			) AS cidade";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$cidade = pg_fetch_result($res, $i, "cidade");

			echo "{$cidade}\n";
		}
	}
}
?>
