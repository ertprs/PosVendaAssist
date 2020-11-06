<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$fabrica = $_GET["fabrica"]; 

if(strlen($fabrica) > 0) {

	$sql_ap = " SELECT peca,descricao
				 FROM tbl_peca
				 WHERE fabrica = $fabrica
				 AND   peca in ( 866587, 866585, 866584, 866583, 866582, 866581, 866580, 866579, 866578, 866577, 866576, 866575)
				 ORDER BY peca,descricao";
		$res_ap = pg_exec($con,$sql_ap);
		if(pg_numrows($res_ap) > 0) {
			$resposta = "<table  >";
			for($i =0 ;$i< pg_numrows($res_ap);$i++){
				$peca         = pg_result($res_ap,$i,peca);
				$descricao_ap = pg_result($res_ap,$i,descricao);
				$resposta .= "<tr>";
				$resposta .= "<td>$descricao_ap</td>";
				$sqlx= "SELECT produto_aparencia,descricao
						FROM tbl_produto_aparencia
						WHERE fabrica = $fabrica
						ORDER BY produto_aparencia";
				$resx = pg_exec($con,$sqlx);
				$resposta .= "<td nowrap>";
				for($j = 0;$j<pg_numrows($resx);$j++){
					$produto_aparencia = pg_result($resx,$j,produto_aparencia);
					$descricao_aparencia = pg_result($resx,$j,descricao);
					$resposta .= "<input type='checkbox' name='$descricao_aparencia' value='$peca-$produto_aparencia'>$descricao_aparencia&nbsp;";
				}
				$resposta .= "</td>";
				$resposta .= "</tr>";
			}
			$resposta .= "</table>";
			echo $resposta;
		}
		exit;
}
?>
