<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if ($_GET["term"]) {
	$term = $_GET["term"];
	$tipo = $_GET["tipo"];

	if (!strlen($term) || !strlen($tipo)) {
		exit;
	}

	$limit = "LIMIT 21";

	switch ($tipo) {
		case "produto":
			$sql = "SELECT
						tbl_produto.referencia AS cod,
						tbl_produto.descricao AS desc
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = {$login_fabrica}
					AND (tbl_produto.referencia ILIKE '%{$term}%' OR tbl_produto.descricao ILIKE '%{$term}%')
					{$limit}";
			break;
		
		case "peca":
			# code...
			break;

		case "posto":
			# code...
			break;
	}
	
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
