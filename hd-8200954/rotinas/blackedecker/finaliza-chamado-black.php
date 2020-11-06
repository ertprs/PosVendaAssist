<?php
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
	require dirname(__FILE__) . '/../funcoes.php';


    date_default_timezone_set('America/Sao_Paulo');
    $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	$fabrica = 1;
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

    $sql = "SELECT hd_chamado
		  FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra using(hd_chamado) 
		 WHERE fabrica = $fabrica
		   AND fabrica_responsavel = $fabrica
		   AND status IN ('Ag. Posto') 
			AND leitura_pendente is not true
		   ";

	$result = pg_query($con, $sql);

	if (strlen(trim(pg_last_error($con))) > 0) {
		$erro .= "Falha ao executar query $sql ".pg_last_error($con);
	}
	if(pg_num_rows($result)> 0){
		for($i= 0; $i<pg_num_rows($result); $i++){
			$hd_chamado = pg_fetch_result($result, $i, 'hd_chamado');

			$sqls = "SELECT 
						tbl_hd_chamado_item.hd_chamado_item, 
						tbl_hd_chamado_item.admin, 
						tbl_hd_chamado_item.data, 
						tbl_hd_chamado_item.status_item, 
						tbl_hd_chamado_item.interno 
					FROM tbl_hd_chamado_item 
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado_item.hd_chamado 
					WHERE tbl_hd_chamado_item.hd_chamado = {$hd_chamado} 
					AND tbl_hd_chamado_item.interno IS NOT TRUE 
					AND tbl_hd_chamado_extra.leitura_pendente IS FALSE 
					ORDER BY tbl_hd_chamado_item.data DESC LIMIT 1";
			$res2 = pg_query($con, $sqls);

			if (strlen(trim(pg_last_error($con))) > 0) {
				$erro .= "Falha ao executar query $sql ".pg_last_error($con);
			}

			if(pg_num_rows($res2) > 0){
				$hd_chamado_item 	 = pg_fetch_result($res2, 0, 'hd_chamado_item');
				$admin				 = pg_fetch_result($res2, 0, 'admin');
				$interno			 = pg_fetch_result($res2, 0, 'interno');
				$data 				 = pg_fetch_result($res2, 0, 'data');
				$status_item 		 = pg_fetch_result($res2, 0, 'status_item');

                if (!in_array(trim($status_item), ['Em Acomp.', 'Em Acomp. Pendente', 'Resp.Conclusiva'])) {
                    continue;
                }

				$data_insert = date('Y-m-d H:i:s');
				if (strlen(trim($admin)) > 0) {

					$sqld = "SELECT CURRENT_DATE - '$data'::date >= 7";
					$resd = pg_query($con, $sqld);

					$maior = pg_fetch_array($resd, 0, PGSQL_NUM);

					if ($maior[0] == 't') {
                        $erro2 = "";

                        echo "Finalizando $hd_chamado - $hd_chamado_item\n";
						
						$sql = "BEGIN TRANSACTION";
						$resu = pg_query($con, $sql);

						$sql = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							data,
							comentario,
							status_item
						) VALUES (
							$hd_chamado,
							'$data_insert',
							'Esse atendimento foi finalizado automaticamente pois não houve interação nos últimos 7 dias. Se necessário, realize a abertura de um novo chamado.',
							'Resolvido'
						)";

						$resu = pg_query($con, $sql);

						if (strlen(trim(pg_last_error($con)))>0) {
							$erro2 .= pg_last_error($con);
						}

						$sqlu = "UPDATE tbl_hd_chamado SET
									status = 'Resolvido',
									data_resolvido = '$data'
								WHERE fabrica = $fabrica
								AND   fabrica_responsavel=$fabrica
								AND   status ='Ag. Posto'
								AND   hd_chamado = $hd_chamado";
						$resu = pg_query($con, $sqlu);

						if (strlen(trim(pg_last_error($con)))>0) {
							$erro2 .= pg_last_error($con);
						}							

						if (strlen(trim($erro2))>0) {
							$sql = "ROLLBACK TRANSACTION";
							$resu = pg_query($con, $sql);
							if (strlen(trim(pg_last_error($con)))>0) {
								$erro2 = true;
							}
						} else {
							$sql = "COMMIT TRANSACTION";
							$resu = pg_query($con, $sql);
							if (strlen(trim(pg_last_error($con)))>0) {
								$erro2 = true;
							}
						}
					}
				}
			}
		}		
	}

	$data = date("d-m-Y");
	if(strlen(trim($erro))>0 or strlen(trim($erro2))>0){

		$mensagem .= $erro;
		$mensagem .= $erro2;
		$assunto = "Erro rotina de finaliza chamado black {$data}";
		
		$headers = 'From: helpdesk@telecontrol.com.br' . "\r\n" .
		    'Reply-To: helpdesk@telecontrol.com.br' . "\r\n" .
		    'X-Mailer: PHP/' . phpversion();

		mail("helpdesk@telecontrol.com.br", $assunto, $mensagem, $headers);

	}
	$phpCron->termino();


?>
