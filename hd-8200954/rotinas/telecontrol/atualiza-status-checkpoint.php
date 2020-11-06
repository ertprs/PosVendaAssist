<?php

error_reporting(E_ALL ^ E_NOTICE);

try {
	
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    define('APP', 'Atualiza Status Pedido');
	define('ENV','producao');

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'atualiza-status';
    $vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
    $vet['log']     = 1;

		if(!empty($argv[1])) {
			$fabrica = $argv[1];
			$cond = " AND tbl_os.fabrica = $fabrica ";
		}

    $sql = "SELECT  os, status_checkpoint
	    into temp p
	    from tbl_os
	    where fabrica <> 0 
	    and posto <> 6359
	    and excluida is not true
		and status_checkpoint <> 9
		$cond
		and data_digitacao between current_timestamp - interval '6 months' and current_timestamp - interval '1 day'
		and fabrica not in (select fabrica from tbl_fabrica where parametros_adicionais ~'telecontrol_distrib') ;

	SELECT *, fn_os_status_checkpoint_os(os) into temp pp from p;

	SELECT os from pp WHERE status_checkpoint <> fn_os_status_checkpoint_os ;
    ";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
        for($i = 0; $i < pg_numrows($res); $i++){
			$os			= pg_result($res,$i,'os');
			if(!empty($os)){
				$sql2 = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os(os) WHERE os = $os";
				$res2 = pg_query($con,$sql2);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

	$sql = "drop table p ; SELECT  os, status_checkpoint
	    into temp p
	    from tbl_os
	    where fabrica <> 0 
	    and posto <> 6359
	    and excluida is not true
		and status_checkpoint not in (9,14)
		and finalizada notnull
		$cond
		and data_digitacao between current_timestamp - interval '6 months' and current_timestamp - interval '1 day' ;

	drop table pp ; SELECT *, fn_os_status_checkpoint_os(os) into temp pp from p;

	SELECT os from pp WHERE status_checkpoint <> fn_os_status_checkpoint_os ;
    ";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
        for($i = 0; $i < pg_numrows($res); $i++){
			$os			= pg_result($res,$i,'os');
			if(!empty($os)){
				$sql2 = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os(os) WHERE os = $os";
				$res2 = pg_query($con,$sql2);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}
	if (!empty($msg_erro)) {
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($vet, APP, $msg);
	}

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}
