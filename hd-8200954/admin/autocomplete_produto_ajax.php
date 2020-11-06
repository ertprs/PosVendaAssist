<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
if(!$_GET["q"]){
	include "javascript_pesquisas.php";
	include "javascript_calendario.php";
	$cod_produto = $_GET["cod_produto"];
}else{
	// PROCESSO AUTOCOMPLETE PARA LOCALIZAÇÃO DO POSTO
	$busca = $_GET["q"];
		$sql = "SELECT
					tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao
				FROM tbl_produto
					JOIN tbl_linha USING(linha)
				WHERE tbl_linha.fabrica = $login_fabrica
					AND ( tbl_produto.referencia ILIKE '%$busca%' OR descricao ILIKE '%$busca%');";
		$res = pg_query($con,$sql);
		$resultado = array();
		for ($i=0; $i<pg_num_rows ($res); $i++ ){
			$produto		= trim(pg_fetch_result($res,$i,produto));
			$referencia		= trim(pg_fetch_result($res,$i,referencia));
			$descricao		= trim(pg_fetch_result($res,$i,descricao));
			$resultado[] = "$produto|$referencia|$descricao";
		}
	$resultado = implode("\n", $resultado);
	echo $resultado;
	die;
}
?>
