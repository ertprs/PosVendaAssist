<?php
/**
 *
 * atualiza_status_checkpoint_os.php
 *
 * Atualiza o status dos pedidos nas OSs
 *
 * @author  Gaspar Lucas
 * @version 2019.05.02
 *
*/

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	require dirname(__FILE__) . '/../../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['fabrica_nome'] 	= 'Gestao';
    $data['arquivo_log'] 	= 'atualiza-status-checkpoint-os';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $erro 					= false;

    $fabrica_distrib = 63;

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica_distrib, __FILE__);
	$phpCron->inicio();
	
	if ($_serverEnvironment != 'development') {
		$data['dest'] 		= 'helpdesk@telecontrol.com.br';
    } else {
    	$data['dest'] 		= 'kaique.magalhaes@telecontrol.com.br';
    }

	$sql = "select fabrica from tbl_fabrica where parametros_adicionais::jsonb->>'telecontrol_distrib' = 't' and ativo_fabrica";
	$resx = pg_query($con,$sql);
	while($fabricas = pg_fetch_object($resx)) {
		$sql = "SELECT DISTINCT tbl_os.os
			FROM tbl_os
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
			AND tbl_fabrica.parametros_adicionais::jsonb->>'telecontrol_distrib' = 't'
			JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			AND tbl_os_item.fabrica_i = tbl_fabrica.fabrica
			JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
			AND tbl_pedido.fabrica = tbl_fabrica.fabrica
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido
			LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_pedido_item.peca
			AND tbl_posto_estoque.posto = 4311
			WHERE COALESCE(tbl_posto_estoque.qtde, 0) < tbl_pedido_item.qtde
			AND tbl_os.fabrica = {$fabricas->fabrica}
            AND tbl_os.finalizada IS NULL
			AND tbl_pedido.data::date = current_date";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			while ($dados = pg_fetch_object($res)) {

				atualiza_status_checkpoint($dados->os, 'Aguard. Abastecimento Estoque');
			}

		}

		$sql = "SELECT DISTINCT tbl_os.os
			FROM tbl_os
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
			AND tbl_fabrica.parametros_adicionais::jsonb->>'telecontrol_distrib' = 't'
			JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			AND tbl_os_item.fabrica_i = tbl_fabrica.fabrica
			JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
			AND tbl_pedido.fabrica = tbl_fabrica.fabrica
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido
			LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_pedido_item.peca
			AND tbl_posto_estoque.posto = 4311
			WHERE (tbl_pedido_item.qtde_faturada > 0 or tbl_pedido_item.qtde_faturada_distribuidor > 0)
			AND tbl_os.fabrica = ". $fabricas->fabrica . "
            AND finalizada isnull
			and status_checkpoint = 35
			and excluida is not true
            AND tbl_pedido.status_pedido  in (4,5,12,13)		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			while ($dados = pg_fetch_object($res)) {

				$sql2 = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os(os) WHERE os = " .$dados->os;
				$res2 = pg_query($con,$sql2);
			}

		}


	}

    $phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Gestão - Erro na atualização do status checkpoint(atualiza_status_checkpoint_os.php)", $msg);
}?>
