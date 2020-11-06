<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
//header('Content-Type: text/html; charset=ISO-8859-1');
$campo   = $_GET["campo"];
$valor   = strtoupper($_GET["valor"]);
$limit   = $_GET["limit"];

	$sqlT = "SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica LIMIT 1";
	$resT = pg_query($con, $sqlT);
if(pg_num_rows($resT) <> 1){
	if ($campo == 'referencia') {
		$sqlT = "SELECT tabela FROM tbl_tabela_item JOIN tbl_peca USING(peca) JOIN tbl_tabela USING (tabela)  WHERE tbl_peca.fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE AND referencia like '%{$valor}%' LIMIT 1";
	} else {
		$sqlT = "SELECT tabela FROM tbl_tabela_item JOIN tbl_peca USING(peca) JOIN tbl_tabela USING (tabela) WHERE tbl_peca.fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE AND descricao = '%{$valor}%' LIMIT 1";
	}
    
	$resT = pg_query($con, $sqlT);
}
$tabela = pg_fetch_result($resT, 0, tabela);
if ($campo == 'referencia') {
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, preco
			FROM tbl_peca 
				JOIN tbl_tabela_item USING (peca) 
				LEFT JOIN tbl_peca_fora_linha USING (peca)
			WHERE tbl_peca.fabrica = {$login_fabrica}
				AND tabela = {$tabela}
				AND tbl_peca.referencia like '%{$valor}%'
				AND peca_fora_linha is null
			LIMIT {$limit}; ";
} else if ($campo == 'descricao') {
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, preco 
			FROM tbl_peca 
				JOIN tbl_tabela_item USING (peca) 
				LEFT JOIN tbl_peca_fora_linha USING (peca)
			WHERE tbl_peca.fabrica = {$login_fabrica}
				AND tabela = {$tabela}
				AND upper(tbl_peca.descricao) like '%{$valor}%'
				AND peca_fora_linha is null
			LIMIT {$limit}; ";
}
$res = pg_query($con, $sql);
die(json_encode(pg_fetch_all($res)));
?>
