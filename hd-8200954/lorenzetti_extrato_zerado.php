<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$sql = "SELECT extrato
	FROM tbl_extrato
	WHERE total = 0 
	AND fabrica =19;";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i=0; $i<pg_numrows($res); $i++){
		if(strlen(pg_result ($res,$i,extrato))){
			$res1 = @pg_exec($con,"BEGIN TRANSACTION");
			$extrato = pg_result ($res,$i,extrato);
			$sql2 = "SELECT fn_calcula_extrato(19,$extrato)";
echo $sql2;
			$res2 = pg_exec ($con,$sql2);
			if (strlen ($msg_erro) == 0) {
				$res3 = @pg_exec ($con,"COMMIT TRANSACTION");
				echo " - Atualizado<br><br>";
			}else{
				$res3 = @pg_exec ($con,"ROLLBACK TRANSACTION");
				echo " - Não Atualizado<br><br>";
			}

		}
	}
}
?>