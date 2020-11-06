<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
//header('Content-Type: text/html; charset=ISO-8859-1');
$dados   = $_GET["dados"];
$tipo_busca = $_GET["tipo_busca"];

if ($tipo_busca == 'estado') {
	$sql = "SELECT DISTINCT TRIM(UPPER(tbl_posto.cidade)) AS cidade
			FROM tbl_posto  WHERE posto in (SELECT posto FROM tbl_posto_fabrica WHERE credenciamento='CREDENCIADO') ";

	if(strlen($dados)>0){
	$sql .= " AND tbl_posto.estado ='$dados' ";
	}
	$sql .= " ORDER BY cidade ";

	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) >0) {
		echo "<option value=''>Selecione</option>";
		for($i=0; $i<pg_numrows($res); $i++) {
			$descricao  = pg_result($res, $i, 'cidade');
			echo "<option value='$descricao'>$descricao</option>";
		}
	}else{
		echo "<option value=''>Não encontrado.</option>";
	}		
}

if ($tipo_busca == 'cidade') {
	$sql = "SELECT DISTINCT TRIM(UPPER(tbl_posto.bairro)) AS bairro
		FROM tbl_posto WHERE posto in (SELECT posto FROM tbl_posto_fabrica WHERE credenciamento='CREDENCIADO')";

	if(strlen($dados)>0){
	$sql .= " AND tbl_posto.cidade ='$dados' ";
	}
	$sql .= " ORDER BY bairro ";

	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) >0) {
		echo "<option value=''>Selecione</option>";
		for($i=0; $i<pg_numrows($res); $i++) {
			$descricao  = pg_result($res, $i, 'bairro');
			echo "<option value='$descricao'>$descricao</option>";
		}
	}else{
		echo "<option value=''>Não encontrado.</option>";
	}		
}
?>
