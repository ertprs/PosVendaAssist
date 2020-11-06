<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';


# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){




	$extrato    = $_GET["extrato"];
	$posto    = $_GET["posto"];


$sql = "SELECT  tbl_os.os              ,
				tbl_os.sua_os          
		FROM    tbl_os 
		JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
		WHERE   tbl_os.posto = $posto
		AND     tbl_os_extra.extrato = $extrato";

$sql .= " AND tbl_os.sua_os ILIKE '%$q%'";

$sql .= "ORDER BY tbl_os.sua_os ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$os = trim(pg_result($res,$i,os));
				$sua_os = trim(pg_result($res,$i,sua_os));

				
				echo "$sua_os|$os";
					echo "\n";

			}
		}


/*

		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$produto    = trim(pg_fetch_result($res,$i,produto));
					$referencia = trim(pg_fetch_result($res,$i,referencia));
					$descricao  = trim(pg_fetch_result($res,$i,descricao));

				}
			}
		}*/
	}
	exit;
}


/*


if (isset($_GET["q"])){
	$q    = strtolower($_GET["q"]);
	$extrato    = $_GET["extrato"];
	$posto    = $_GET["posto"];


$sql = "SELECT  tbl_os.os              ,
				tbl_os.sua_os          
		FROM    tbl_os 
		JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
		WHERE   tbl_os.posto = $posto
		AND     tbl_os_extra.extrato = $extrato";

$sql .= " AND tbl_os.sua_os ILIKE '%$q%'";

echo $sql .= "ORDER BY tbl_os.sua_os ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$os = trim(pg_result($res,$i,os));
				$sua_os = trim(pg_result($res,$i,sua_os));

				if ($cor <> "#FFFFFF") {
					$cor =  "#FFFFFF";
				}else{
					$cor =  "#EEEEEE";
				}

				echo "$os|$os - $sua_os|$sua_os| $os |<span style='font-size:10px;font-family:verdana;'>$sua_os</span>\n";
			}
		}
	}*/

?>