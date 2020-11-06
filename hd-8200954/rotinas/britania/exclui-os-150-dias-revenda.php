<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$bug         = '';
	$fabrica     = 3;
	$dia_mes     = date('d');
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	$vet['fabrica'] = 'britania';
	$vet['tipo']    = 'excluios';
	//$vet['dest']    = 'helpdesk@telecontrol.com.br';
    $vet['dest']    = 'ederson.sandre@telecontrol.com.br';
	$vet['log']     = 2;
    $msg_erro       = "";

	if ($dia_mes == 10){ //mudar para 10
		$sql = "
            SELECT 
                tbl_os.os
			FROM tbl_os
                LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto and tbl_os_item.fabrica_i = $fabrica
			WHERE tbl_os.fabrica = $fabrica
                AND tbl_os.data_fechamento IS NULL
                AND tbl_os.finalizada IS NULL
                AND tbl_os.os_fechada is false
                AND tbl_os.consumidor_revenda = 'R'
                AND tbl_os.data_digitacao::date < current_date - INTERVAL '150 days'
                AND tbl_os.excluida is not true
                AND tbl_os_item.os_item IS NULL;";
		$res       = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);

		if (@pg_num_rows($res) > 0) {

			for ($i = 0; $i < pg_num_rows($res); $i++) {
			    $os = pg_fetch_result($res,$i,'os');

			    if(strlen($os)>0){
                    $sql = "SELECT fn_os_excluida($os,$fabrica,1020)"; 
                    if(pg_query($con, $sql)){
                        $sql = "SELECT os_status FROM tbl_os_status WHERE os = {$os} AND status_os = 15;";    
                        $res_verifica = pg_query($con, $sql);
                
                         if(pg_num_rows($res_verifica) == 0){
                             $sql = "INSERT INTO tbl_os_status (
                                        os,
                                        status_os ,
                                        observacao,
                                        admin,
                                        automatico
                                    ) VALUES (
                                        $os,
                                        15,
                                        'Os excluida automaticamente por estar 150 dias em aberto sem peça',
                                        1020,
                                        true
                                    );";
                             
                            pg_query($con, $sql);
                         }

                        //$msg_erro .= pg_last_error($con);
                        
                        if(strlen($msg_erro)>0){
                            $msg_erro  = nl2br($msg_erro);
                        }
			        }
                }
			}
		}

		if (strlen($msg_erro) > 0) {
			$bug .= $msg_erro;
			Log::log2($vet, $msg_erro);
		}
	}

	if (strlen($bug) > 0) {

		Log::envia_email($vet, 'Log - Exclui OS aberta a mais de 150 dias', $bug);
	}
	
	$phpCron->termino();

} catch (Exception $e) {
        //echo $e->getMessage();
        $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );
}

//echo $msg_erro;
?>
