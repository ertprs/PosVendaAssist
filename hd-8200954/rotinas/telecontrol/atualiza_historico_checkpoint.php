<?php

try {

        include dirname(__FILE__) . '/../../dbconfig.php';
        include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
        require dirname(__FILE__) . '/../funcoes.php';
	
	#$login_fabrica = $argv[1];
	#$extrato = $argv[2];

        $sql = "SELECT TO_CHAR(CURRENT_DATE-1,'DD/MM/YYYY')";
        $res = pg_query($con,$sql);
        $data_dia = pg_fetch_result($res,0,0); 
	
        $data_corte='2017-01-01 00:00:00';
        #$sql = "SELECT fabrica,os,status_checkpoint into tmp_marisa_checkpoint from tbl_os where fabrica <> 0 and excluida is NOT TRUE and finalizada IS NULL and data_digitacao >= '$data_corte';";
        #$res = pg_query($con,$sql);

        $sql = "SELECT fabrica,os,status_checkpoint from tbl_os where fabrica <> 0 and excluida is NOT TRUE and finalizada IS NULL and data_digitacao >= '$data_corte';";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
                    $rows = pg_num_rows($res);
		for($i = 0; $i < $rows; $i++){
			$fabrica = pg_fetch_result($res,$i,'fabrica');
			$os = pg_fetch_result($res,$i,'os');
			$status_checkpoint = pg_fetch_result($res,$i,'status_checkpoint');

			$sqlhisto = "SELECT os_historico_checkpoint,os,status_checkpoint,TO_CHAR(data_input,'DD/MM/YYYY') AS data_input FROM tbl_os_historico_checkpoint WHERE fabrica = $fabrica and os = $os and data_input <= '$data_dia 00:00:00' order by os_historico_checkpoint DESC LIMIT 1";
			$reshisto = pg_query($con,$sqlhisto);

			$rowshisto = pg_num_rows($reshisto);

			for($y = 0; $y < $rowshisto; $y++){
				$status_check_histo = pg_fetch_result($reshisto,$y,'status_checkpoint');
				$data_input         = pg_fetch_result($reshisto,$y,'data_input');

				if ($data_dia <> $data_input and !empty($status_checkpoint)){
					$sql_insert="INSERT INTO tbl_os_historico_checkpoint(fabrica,os,status_checkpoint,tg_grava) values ($fabrica, $os, $status_checkpoint, 'rotina-BI');";
					$res_insert = pg_query($con,$sql_insert);

					if (strlen(pg_last_error($con)) > 0) {
						$errors[$row["os"]] = pg_last_error($con);
						$sqlinsert;
						pg_last_error();
						die;
					}
				}
			}
		}      
	}
}catch(Excepiton $e){
	echo "Erro ao gravar historico checkpoint-> ".$e;
}
