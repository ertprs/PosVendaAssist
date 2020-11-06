<?
// Autentica usuario na tabela de preços (intelbras)

//$cook_acessa_tabela_preco = $_COOKIE['acessa_tabela_preco'];

$sql = "SELECT senha_tabela_preco,tbl_posto.posto
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_posto_fabrica.posto = $login_posto
		AND senha_tabela_preco IS NOT NULL
		AND length(senha_tabela_preco) > 0";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {
	if($acessa_tabela_preco=='SIM'){
		$msg = traduz("area.restrita.para.pessoal.autorizado",$con,$cook_idioma);
	}
	else{
		
		header ("Location: tabela_precos_senha_preco.php");
		exit;
	}
}
?>