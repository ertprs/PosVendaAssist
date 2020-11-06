<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	//FABRICA:104 - VONDER//
	$login_fabrica=104;

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	$sql = "SELECT 
				de AS peca_de,
				para AS peca_para,
				campos_extra::jsonb->>'prioridade' AS prioridade,
				status
			FROM tbl_peca_alternativa
			WHERE fabrica = {$login_fabrica}";

	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){

		$arquivo = "ovd-ret-pecas-alternativas.ret";
		$fp = fopen ("$arquivo","w");

		for($i = 0; $i < pg_numrows($res); $i++){
			$peca_de    = trim(pg_fetch_result($res,$i,'peca_de'));
			$peca_para  = trim(pg_fetch_result($res,$i,'peca_para'));
			$prioridade = trim(pg_fetch_result($res,$i,'prioridade'));
			$status     = trim(pg_fetch_result($res,$i,'status'));

			if($status == true){
				$status_x = "S";
			} else {
				$status_x = "N";
			}

			fputs($fp,"$peca_de;$peca_para;$prioridade;$status_x\n");
		}

		fclose ($fp);

		if(file_exists($arquivo)) {
			system("mv $arquivo /home/vonder/telecontrol-vonder/");
		}

	}

	$phpCron->termino();

} catch (Exception $e) {

	echo $e->getMessage();

}?>
