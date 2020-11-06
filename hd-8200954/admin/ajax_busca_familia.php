<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
//header('Content-Type: text/html; charset=ISO-8859-1');
$marca   = $_GET["marca"];
if(strlen($marca)>0){
	echo "<option value=''>Escolha a famíla</option>";
	$sql = "
	SELECT  distinct tbl_familia.familia,
		tbl_familia.descricao
	FROM    tbl_familia
	JOIN tbl_produto using(familia)
	WHERE   tbl_familia.fabrica = $login_fabrica 
	and tbl_produto.marca='$marca'
	ORDER BY tbl_familia.descricao;";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) >0) {
		for($i=0; $i<pg_numrows($res); $i++) {
			$familia =    pg_result($res, $i, 'familia');
			$descricao  = pg_result($res, $i, 'descricao');
			echo "<option value='$familia'>$descricao</option>";
		}
	}
}else{
	echo "<option value=''>Não encontrado.</option>";
}	
?>
