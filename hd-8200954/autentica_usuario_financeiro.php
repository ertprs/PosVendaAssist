<?

$sql = "SELECT posto, senha_financeiro
		FROM tbl_posto_fabrica 
		WHERE	posto   = $login_posto
			AND fabrica = $login_fabrica
			AND senha_financeiro IS NOT NULL
			AND LENGTH(TRIM(senha_financeiro)) > 0";
$res = pg_query($con,$sql);
if (pg_num_rows($res) > 0) {
	if($cookie_login['acessa_extrato']=='SIM') {
		$msg = ($sistema_lingua <> 'ES') ? "Area Restrita Para Pessoal Autorizado." : "Área restringida, sólo personal autorizado.";
	} else {
		header ("Location: os_extrato_senha_financeiro.php");
		exit;
	}
}
?>
