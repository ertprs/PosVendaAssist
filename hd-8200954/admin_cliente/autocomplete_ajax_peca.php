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
			$ilike = "tbl_peca.referencia ILIKE '%{$term}%'";
			break;
		
		case "desc":
			$ilike = "TO_ASCII(tbl_peca.descricao, 'LATIN-9') ILIKE TO_ASCII('%{$term}%', 'LATIN-9')";
			break;
	}

	$sql = "SELECT peca AS id, tbl_peca.referencia AS cod, tbl_peca.descricao AS desc
			FROM tbl_peca
			WHERE tbl_peca.fabrica = {$login_fabrica}
			AND {$ilike}
			{$limit}";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$pecas = pg_fetch_all($res);

		foreach ($pecas as $i => $peca) {
			$pecas[$i]['desc'] = utf8_encode($peca['desc']);
		}
		die(json_encode($pecas));
	}
}

exit;
?>
