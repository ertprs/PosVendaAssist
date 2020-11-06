<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';


if (isset($_GET["q"])){
	$q    = strtolower($_GET["q"]);



		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

				$sql .= " AND (tbl_posto.nome ILIKE '%".trim($q)."%'
					  OR  tbl_posto_fabrica.codigo_posto ILIKE '%$q%')";
		
		

		//echo $sql;
		/*$sql = "SELECT tbl_item.descricao,tbl_item.referencia
				FROM tbl_item
				WHERE 1=1 $sql_and
				ORDER BY tbl_item.referencia
				LIMIT 15";
		*/

	//echo nl2br($sql);
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));

				if ($cor <> "#FFFFFF") {
					$cor =  "#FFFFFF";
				}else{
					$cor =  "#EEEEEE";
				}

				echo "$codigo_posto|$codigo_posto - $nome|$nome| $codigo_posto |<span style='font-size:10px;font-family:verdana;'>$codigo_posto-$nome</span>\n";
			}
		}
	}

?>