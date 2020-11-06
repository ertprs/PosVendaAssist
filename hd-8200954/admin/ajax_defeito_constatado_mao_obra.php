<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';


if (isset($_GET["q"])){
	$q    = strtolower($_GET["q"]);



		$sql = "SELECT DISTINCT tbl_defeito_constatado.defeito_constatado,tbl_defeito_constatado.descricao
					FROM tbl_produto_defeito_constatado 
					JOIN tbl_defeito_constatado USING(defeito_constatado) 
					JOIN tbl_produto USING (produto)
					WHERE fabrica = $login_fabrica
					AND tbl_defeito_constatado.descricao ilike '%$q%'";
		
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
				$nome = trim(pg_result($res,$i,descricao));
				$codigo_posto = trim(pg_result($res,$i,defeito_constatado));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				echo "$codigo_posto|$nome|$nome| $codigo_posto |$codigo_posto-$nome\n";
			}
		}
	}

?>