<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if ($login_fabrica <> 10) {
	exit('Acesso negado...');
}

$msg_erro = null;

$posto = intval($_GET['posto']);

if(empty($posto)) {
	die('Par‚metro POST ausente.');
}
else {
	/**
	 Todas as OSs de revenda com problema
	 */

	$res = pg_query($con, "BEGIN"); 

	/* seleciona OSs */
	$sql = "SELECT
				DISTINCT tbl_os_revenda_item.os_revenda
			FROM
				tbl_os_revenda_item
			JOIN
				tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
			JOIN
				tbl_os ON tbl_os.os = tbl_os_revenda_item.os_lote
			JOIN
				tbl_os_troca ON tbl_os_troca.os = tbl_os.os
			LEFT JOIN
				tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			WHERE
				tbl_os_revenda.fabrica = 1
			AND
				tbl_os_revenda.posto = {$posto}
			AND
				tbl_os_revenda.digitacao BETWEEN '2012-07-31 00:00:00' AND '2012-08-07 23:59:59'
			AND
				tbl_os_produto.os_produto IS NULL
			AND
				tbl_os_revenda.tipo_atendimento IS NULL
			AND
				tbl_os_revenda_item.produto_troca IS NULL
			AND
				tbl_os.consumidor_revenda = 'R'
			ORDER BY
				tbl_os_revenda_item.os_revenda;";
////////////////
echo $sql."<hr>";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_last_error($con);

$total_os = pg_num_rows($res);
////////////////
echo "Total de OSs Rev: ".$total_os."<hr>";

	for($i=0; $i<$total_os; $i++) {

		$os_revenda = pg_fetch_result($res,$i,0);
////////////////
echo "OS Revenda: ".$os_revenda."<hr>";

		/* seleciona OS da Rev Item relacionadas */	
		$sql = "SELECT
					os_lote AS os
				FROM
					tbl_os_revenda_item
				WHERE
					os_revenda = {$os_revenda}
				ORDER BY os_lote;";
////////////////
echo $sql."<hr>";

		$resOsLote = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);
		
		$total_os_lote = pg_num_rows($resOsLote);
////////////////
echo "Total de Os Rev Item: ".$total_os_lote."<hr>";

		if($total_os_lote > 0){
			for($z=0; $z<$total_os_lote; $z++) {
				$os = pg_fetch_result($resOsLote,$z,0);
////////////////
echo "OS: ".$os."<br>";
				/* exclui os de troca */
				$sql = "DELETE FROM tbl_os_troca
						WHERE os = {$os};";
////////////////
echo $sql."<br>";
				$resDelOsTroca = pg_query($con, $sql);
				$msg_erro .= pg_last_error($con);
				
				/* marca OS como exclu√≠da */
				$sql = "UPDATE
							tbl_os
						SET
							excluida = true,
							sua_os = null,
							os_sequencia = null
						WHERE
							os = {$os};";
////////////////
echo $sql."<hr>";
				$resUpOs = pg_query($con, $sql);
				$msg_erro .= pg_last_error($con);
			}
		}
		
		/* retira do os revenda item o relacionamento com os */
		$sql = "UPDATE
					tbl_os_revenda_item
				SET
					os_lote = null
				WHERE
					os_revenda = {$os_revenda}";
////////////////
echo $sql."<hr>";
		$resUpRevItem = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);
		
		/* retira o status de explodida */
		$sql = "UPDATE
					tbl_os_revenda
				SET
					explodida = null
				WHERE
					os_revenda = {$os_revenda}";
////////////////
echo $sql."<hr>";
		$resUpRev = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);
	}

	if(strlen($msg_erro) > 0) {
		$res = pg_query($con, "ROLLBACK");
		echo "Erros: <br />"; 
		echo $msg_erro;
	}
	else {
		$res = pg_query($con, "COMMIT");
		echo "Finalizado com sucesso... <br />"; 
	}
}
?>