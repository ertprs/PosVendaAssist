<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if ($_GET["term"]) {
	$term   = $_GET["term"];

	if (!strlen($term)) {
		exit;
	}

	if (in_array($login_fabrica, array(11,172))) {
		$login_fabrica_conjunto = "11,172";
		$prod_ativo = " AND tbl_produto.ativo is true ";
	} else {
		$login_fabrica_conjunto = $login_fabrica; 
		$prod_ativo = "";
	}

	$limit = "LIMIT 21";
		
	$ilike = "tbl_produto.referencia ILIKE '%{$term}%' OR TO_ASCII(tbl_produto.descricao, 'LATIN-9') ILIKE TO_ASCII('%{$term}%', 'LATIN-9')";


	$sql = "SELECT tbl_produto.produto AS id, tbl_produto.referencia AS cod, tbl_produto.descricao AS desc
			FROM tbl_produto
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE tbl_produto.fabrica_i in ( $login_fabrica_conjunto )
			AND tbl_linha.fabrica in ( $login_fabrica_conjunto ) 
			$prod_ativo
			AND {$ilike}
			{$limit}";
				
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++ ){
			$resultado[$i]["cod"]  = utf8_encode(pg_fetch_result($res, $i, "cod"));
			$resultado[$i]["desc"] = trim(utf8_encode(pg_fetch_result($res, $i, "desc")));
		}
	}

	echo json_encode($resultado);
}

exit;

?>
