<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';


if (isset($_GET["q"])){
	$q    = strtolower($_GET["q"]);

		$sql = "SELECT tbl_produto.produto,tbl_produto.descricao,tbl_produto.referencia 
						FROM tbl_produto
						JOIN tbl_linha USING(linha)
						JOIN tbl_fabrica USING(fabrica)
						WHERE fabrica = $login_fabrica
						AND tbl_produto.descricao ilike '%$q%' or tbl_produto.referencia ilike '%$q%' and tbl_produto.ativo is true";
		
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
				$produto = trim(pg_result($res,$i,produto));
				$referencia = trim(pg_result($res,$i,referencia));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				echo "$referencia|$nome| $produto |$referencia-$nome\n";
			}
		}
	}

?>