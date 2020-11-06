<?

include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
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
			$ilike = "tbl_produto.referencia ILIKE '%{$term}%'";
			break;
		
		case "desc":
			$ilike = "TO_ASCII(tbl_produto.descricao, 'LATIN-9') ILIKE TO_ASCII('%{$term}%', 'LATIN-9')";
			break;
	}

	$sql = "SELECT tbl_produto.produto AS id, tbl_produto.referencia AS cod, tbl_produto.descricao AS desc
			FROM tbl_produto
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE tbl_produto.fabrica_i = {$login_fabrica}
			AND tbl_linha.fabrica = {$login_fabrica}
			AND {$ilike}
			{$limit}";
	
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++ ){
			$resultado[$i]["id"]   = utf8_encode(pg_fetch_result($res, $i, "id"));
			$resultado[$i]["cod"]  = utf8_encode(pg_fetch_result($res, $i, "cod"));
			$resultado[$i]["cod"]  = (in_array($login_fabrica, array(169,170))) ? str_replace('YY', '-', $resultado[$i]["cod"]) : $resultado[$i]["cod"];
			$resultado[$i]["desc"] = trim(utf8_encode(pg_fetch_result($res, $i, "desc")));
		}
	}

	echo json_encode($resultado);
}

exit;

?>
