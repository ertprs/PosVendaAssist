<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
if(!$_GET["q"]){
	include "javascript_pesquisas.php";
	include "javascript_calendario.php";
	$cod_peca = $_GET["cod_peca"];
}else{
	// PROCESSO AUTOCOMPLETE PARA LOCALIZAÇÃO DO PEÇA
	$busca = $_GET["q"];
		$sql = "SELECT peca,
					referencia,
					descricao
				FROM tbl_peca
				WHERE fabrica=$login_fabrica
		AND ( descricao ILIKE '%$busca%' OR referencia ILIKE '%$busca%');";
		$res = pg_exec($con, $sql);
		$resultado = array();
		for($i = 0; $i < pg_num_rows($res); $i++){
			$peca = pg_result($res, $i, peca);
			$referencia = pg_result($res, $i, referencia);
			$descricao = pg_result($res, $i, descricao);
			$resultado[] = "$peca|$referencia|$descricao";
		}
	$resultado = implode("\n", $resultado);
	echo $resultado;
	die;
}
?>
