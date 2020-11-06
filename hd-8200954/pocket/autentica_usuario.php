<?
$cook_posto_fabrica = $HTTP_COOKIE_VARS['cook_posto_fabrica'];

if (strlen ($cook_posto_fabrica) == 0) {
	header ("Location: index.php");
	exit;
}

$sql = "SELECT	tbl_posto.*                         ,
				tbl_fabrica.nome as fabrica_nome    ,
				tbl_posto_fabrica.pedido_em_garantia,
				tbl_fabrica.fabrica
		FROM	tbl_posto
		JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
		JOIN	tbl_fabrica       ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
		WHERE	tbl_posto_fabrica.oid = $cook_posto_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) == 0) {
	header ("Location: index.php");
	exit;
}

$login_posto              = trim (pg_result ($res,0,posto));
$login_nome               = trim (pg_result ($res,0,nome));
$login_cnpj               = trim (pg_result ($res,0,cnpj));
$login_fabrica            = trim (pg_result ($res,0,fabrica));
$login_fabrica_nome       = trim (pg_result ($res,0,fabrica_nome));
$login_pede_peca_garantia = trim (pg_result ($res,0,pedido_em_garantia));

?>
