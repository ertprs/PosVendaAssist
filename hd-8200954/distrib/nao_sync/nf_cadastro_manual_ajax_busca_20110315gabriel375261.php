<?
header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_admin.php';


$q = strtolower($_GET["q"]);
$tipo = $_GET['tipo'];
if (isset($_GET["q"])){
	if($tipo == 'fornecedor') {
		$sql = "SELECT posto,nome 
						FROM tbl_posto
						LEFT JOIN tbl_posto_extra using(posto)
						WHERE tbl_posto_extra.fornecedor_distrib IS TRUE";
		$sql .= " AND (nome ilike '%$q%' or cnpj like '%$q%')";
		$sql .=" ORDER BY nome ";
		$res = pg_query($con,$sql);


		if(pg_num_rows($res)>0) {
			for ( $i = 0 ; $i < @pg_num_rows ($res) ; $i++ ) {
				$posto	= trim(pg_fetch_result($res,$i,posto));	
				$nome	= trim(pg_fetch_result($res,$i,nome));	
				echo "$nome|$posto";
				echo "\n";
			}
		}else{
			echo "<h2>Se você não conseguir encontrar o fonecedor, avise o Ger. Ronaldo para cadastrar na Fábrica Telecontrol o posto para que sirva de Fornecedor (não precisa credenciar como posto, apenas cadastrar).</h2>";
		}
	}
	
	if($tipo =='transportadora') {
		$sql = "SELECT  DISTINCT transp
				FROM     tbl_faturamento ";
		$sql .= " WHERE transp ilike '%$q%' ";
		$sql .=" AND fabrica in (10,51,81) 
					ORDER BY transp ";
		$res = @pg_query ($con,$sql);

		for ($i=0; $i < pg_num_rows($res); $i++) {
			$transp_nome	= strtoupper(trim(pg_fetch_result($res,$i,transp)));
			echo "$transp_nome\n";
		}
	}
	if($tipo =='condicao') {
		$sql = "SELECT DISTINCT  descricao 
			FROM  tbl_condicao 
			WHERE fabrica in (10,51,81) ";
		$sql .= " AND descricao ilike '%$q%' ";
		$res = @pg_query ($con,$sql);
		for ($i=0; $i < pg_num_rows($res); $i++) {
			$cond_des = strtoupper(trim(pg_fetch_result($res,$i,descricao)));
				echo "$cond_des\n";
		}
	}
}