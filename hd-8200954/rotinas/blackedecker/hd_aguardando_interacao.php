<?php
/**
 *
 * hd_aguardando_interacao.php 
 *
 * @author  Thiago Tobias
 * @version 2016.01.04
 * 
 *
 */

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$bug         = '';
	$fabrica     = 1;
	$dia_mes     = date('y-m-d');
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	$vet['fabrica'] = 'blackedecker';
	$vet['tipo']    = 'Ag. Interação';
	//$vet['dest']    = 'helpdesk@telecontrol.com.br';
    $vet['dest']    = 'helpdesk@telecontrol.com.br';
	$vet['log']     = 2;
    $msg_erro       = "";

	$sql = "SELECT i.hd_chamado,i.hd_chamado_item,i.status_item
				FROM tbl_hd_chamado_item AS I
					JOIN tbl_hd_chamado AS HD ON I.hd_chamado = HD.hd_chamado AND hd.status = 'Ag. Posto'
				WHERE fabrica_responsavel = $fabrica
					AND I.status_item       = 'Em Acomp.'
					AND I.data < CURRENT_DATE - INTERVAL '7 days'
					AND GREATEST(I.data) = (SELECT MAX(hdi.data) AS data FROM tbl_hd_chamado_item as hdi WHERE hdi.hd_chamado = i.hd_chamado);";

	$res       = pg_query($con, $sql);
	$msg_erro .= pg_last_error($con);

	if (pg_num_rows($res) > 0 ) {
		pg_query($con,"BEGIN TRANSACTION");
		for ($i=0; $i < pg_num_rows($res); $i++) { 
			$hd_chamado = pg_fetch_result($res, $i, hd_chamado);
			$sql_up = "	UPDATE tbl_hd_chamado
							SET status = 'Ag. Intera'
							WHERE fabrica = {$fabrica}
								AND hd_chamado = {$hd_chamado};";
			$res_up = pg_query($con,$sql_up);			
		}
		if (strlen(pg_last_error($con))) {
			$msg_erro .= pg_last_error($con);
			pg_query($con,"ROLLBACK TRANSACTION");
		}else{
			pg_query($con,"COMMIT TRANSACTION");
		}
	}	

	if (strlen($msg_erro) > 0) {
		$bug .= $msg_erro;
		Log::log2($vet, $msg_erro);
	}
	

	if (strlen($bug) > 0) {

		Log::envia_email($vet, 'Log - HD Chamado Aguardando Interação ', $bug);
	}
	
	$phpCron->termino();

} catch (Exception $e) {
        //echo $e->getMessage();
        $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );
}

//echo $msg_erro;
?>
