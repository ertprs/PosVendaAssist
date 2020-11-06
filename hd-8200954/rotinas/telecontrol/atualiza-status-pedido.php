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
	
	$sql = "SELECT DISTINCT tbl_pedido.pedido, tbl_pedido.fabrica
			FROM tbl_pedido
			JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
			WHERE tbl_pedido.data > (CURRENT_TIMESTAMP - INTERVAL '50 days')
			AND tbl_pedido.status_pedido NOT IN(4,13,14)
			AND (tbl_pedido_item.qtde_faturada > 0 OR tbl_pedido_item.qtde_cancelada > 0 OR tbl_pedido_item.qtde_faturada_distribuidor > 0)
			";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){

        for($i = 0; $i < pg_numrows($res); $i++){
			$pedido			= pg_result($res,$i,'pedido');
			$login_fabrica	= pg_result($res,$i,'fabrica');

			$sql2 = "SELECT fn_atualiza_status_pedido($login_fabrica,$pedido) ; UPDATE tbl_pedido_item set qtde_faturada = 0 where pedido  = $pedido and qtde_faturada < 0 ";
			$res2 = pg_query($con,$sql2);
			$msg_erro .= pg_errormessage($con);
		}
	}

	$sql = "SELECT DISTINCT tbl_pedido.pedido, tbl_pedido.fabrica
			FROM tbl_pedido
			JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
			WHERE tbl_pedido.data > (CURRENT_TIMESTAMP - INTERVAL '50 days')
			AND tbl_pedido.status_pedido  IN(4)
			AND (tbl_pedido_item.qtde_faturada = 0 and tbl_pedido_item.qtde_cancelada = 0 and tbl_pedido_item.qtde_faturada_distribuidor = 0)
			";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){

        for($i = 0; $i < pg_numrows($res); $i++){
			$pedido			= pg_result($res,$i,'pedido');
			$login_fabrica	= pg_result($res,$i,'fabrica');

			$sql2 = "SELECT fn_atualiza_status_pedido($login_fabrica,$pedido) ; UPDATE tbl_pedido_item set qtde_faturada = 0 where pedido  = $pedido and qtde_faturada < 0 ";
			$res2 = pg_query($con,$sql2);
			$msg_erro .= pg_errormessage($con);
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
