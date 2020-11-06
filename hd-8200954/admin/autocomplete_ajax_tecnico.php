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
			$ilike = "tbl_tecnico.cpf ILIKE '%{$term}%'";
			break;
		
		case "desc":
			$ilike = " tbl_tecnico.nome ILIKE '%{$term}%' ";
			break;
		case "TF":
			$ilike = "( tbl_tecnico.cpf ILIKE '%{$term}%' OR tbl_tecnico.nome ILIKE '%{$term}%' ) AND tbl_tecnico.tipo_tecnico = 'TF' AND tbl_tecnico.ativo = 't' ";
			break;
	}

	$sql = "SELECT cpf, nome
							FROM tbl_tecnico
							WHERE tbl_tecnico.fabrica = {$login_fabrica}
							AND {$ilike}
							{$limit}";
	
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++ ){
			$resultado[$i]["cod"]  = utf8_encode(pg_fetch_result($res, $i, "cpf"));
			$resultado[$i]["desc"] = utf8_encode(pg_fetch_result($res, $i, "nome"));
		}
	}

	echo json_encode($resultado);
}

exit;
?>
