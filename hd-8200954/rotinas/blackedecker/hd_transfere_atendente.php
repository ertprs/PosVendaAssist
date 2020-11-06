<?php
/**
 * *
 * * hd_transfere_atendente.php 
 * *
 * * @author  Thiago Tobias
 * * @version 2016.06.30
 * * 
 * *
 * */

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';    
	include dirname(__FILE__) . '/../../helpdesk.inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	global $login_fabrica;
	$login_fabrica = 1;
	$atendente = 112;

	$phpCron = new PHPCron($login_fabrica, __FILE__); 
	$phpCron->inicio();

	$vet['login_fabrica'] = 'blackedecker';
	$vet['dest']    = 'thiago.tobias@telecontrol.com.br';
	$vet['log']     = 2;
	$msg_erro       = "";

	$sql = "SELECT  hd_chamado, 
		tbl_admin.admin,
		tbl_admin.nome_completo,
		posto,
		categoria,
		status
		FROM tbl_hd_chamado  
		JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
		WHERE tbl_hd_chamado.fabrica = $login_fabrica
		AND tbl_hd_chamado.status <> 'Cancelado'
		AND tbl_hd_chamado.status <> 'Resolvido Posto'
		AND tbl_hd_chamado.atendente in ($atendente)
		AND tbl_hd_chamado.data > '2016-04-01'";
	$res       = pg_query($con, $sql);

	if (pg_num_rows($res) > 0 ) {
		pg_query($con,"BEGIN TRANSACTION");
		for ($i=0; $i < pg_num_rows($res); $i++) { 
			$hd_chamado = pg_fetch_result($res, $i, hd_chamado);
			$hd_admin = pg_fetch_result($res, $i, admin);
			$hd_nome_completo = pg_fetch_result($res, $i, nome_completo);
			$hd_posto = pg_fetch_result($res, $i, posto);
			$hd_categoria = pg_fetch_result($res, $i, categoria);
			$hd_status = pg_fetch_result($res, $i, status);

			$atendente_novo = hdBuscarAtendentePorPosto($hd_posto,$hd_categoria);

			if ($atendente != $atendente_novo) {

				$sql_admin_u = "SELECT nome_completo 
					FROM tbl_admin 
					WHERE fabrica = $login_fabrica
					AND admin = $atendente_novo; ";
				$res_admin_u = pg_query($con,$sql_admin_u);
				if ( pg_num_rows($res_admin_u) > 0 ) {
					$atendente_nome_u = pg_fetch_result($res_admin_u, 0, nome_completo);
				}

				$frase_transferencia = "Chamado transferido automáticamente: de ".$hd_nome_completo." para ".$atendente_nome_u." <br>Transferencia via Rotina!";
				$hd_chamado_item_u = hdCadastrarResposta($hd_chamado, $frase_transferencia,true,$hd_status);

				$sql_u = "UPDATE tbl_hd_chamado SET
					atendente = {$atendente_novo}
					WHERE atendente = {$atendente}
					AND fabrica = {$login_fabrica}
					AND hd_chamado = {$hd_chamado};";
				$res_u = pg_query($con,$sql_u);
			}
		}
		if (strlen(pg_last_error()) > 0) {                
			pg_query($con,'ROLLBACK');
			$bug = pg_last_error();
			Log::log2($vet, $msg_erro);
		}else{
			pg_query($con,'COMMIT');
		}  
	}

	if (strlen($bug) > 0) {
		Log::envia_email($vet, 'Log - HD Transferência de Atendimento ', $bug);
	}

	$phpCron->termino();

} catch (Exception $e) {
	// echo $e->getMessage();
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}
?>
