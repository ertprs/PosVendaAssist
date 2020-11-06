<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
if(!$_GET["q"]){
	include "javascript_pesquisas.php";
	include "javascript_calendario.php";
	$cod_posto = $_GET["cod_posto"];
}else{
	// PROCESSO AUTOCOMPLETE PARA LOCALIZAÇÃO DO POSTO
	$busca = $_GET["q"];
		$sql = "SELECT
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.posto
				FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND ( tbl_posto_fabrica.codigo_posto ILIKE '%$busca%' OR nome ILIKE '%$busca%');";
		$res = pg_query($con,$sql);
		$resultado = array();
		for ($i=0; $i<pg_num_rows ($res); $i++ ){
			$nome			= trim(pg_fetch_result($res,$i,nome));
			$codigo_posto	= trim(pg_fetch_result($res,$i,codigo_posto));
			$posto			= trim(pg_fetch_result($res,$i,posto));
			$resultado[] = "$codigo_posto|$nome|$posto";
		}
	$resultado = implode("\n", $resultado);
	echo $resultado;
	die;
}
?>
