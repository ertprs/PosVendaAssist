<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if ($_GET["term"]) {
	$term   = $_GET["term"];
	$search = $_GET["search"];

	if (!strlen($term) || !strlen($search)) {
		exit;
	}

	$limit = "LIMIT 21";

	switch ($search) {
		case "cod":
			$ilike = "tbl_posto_fabrica.codigo_posto ILIKE '%{$term}%'";
			break;
		
		case "desc":
			$ilike = "TO_ASCII(tbl_posto.nome, 'LATIN-9') ILIKE TO_ASCII('%{$term}%', 'LATIN-9')";
			break;
	}

	$sql = "SELECT
				tbl_posto_fabrica.codigo_posto AS cod,
				tbl_posto.nome AS desc
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND {$ilike}
			{$limit}";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++ ){
			$resultado[$i]["cod"]  = utf8_encode(pg_fetch_result($res, $i, "cod"));
			$resultado[$i]["desc"] = utf8_encode(pg_fetch_result($res, $i, "desc"));
		}
	}

	echo json_encode($resultado);
}

exit;
?>
