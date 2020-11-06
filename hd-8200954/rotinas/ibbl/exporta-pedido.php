<?php
/**
 *
 * rotinas/ibbl/exporta-pedido.php
 *
 * @author  Francisco Ambrozio
 * @version 2012.02
 *
 */

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$msg_erro = '';

	$log = array();

	$vet['fabrica'] = 'ibbl';
	$vet['tipo']    = 'pedido';
	$vet['dest']    = 'helpdesk@telecontrol.com.br';

	$fabrica  = '90' ;
	$arquivos = '/tmp/' . $vet['fabrica'] . '/' . $vet['tipo'];
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	if (!is_dir($arquivos)) {
		mkdir($arquivos, 0777, true);
	}

	$arq_pedido = $arquivos . '/pedido.txt';
	$arq_item = $arquivos . '/pedido-item.txt';

	$sql = "SELECT TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')         AS data_pedido    ,
					tbl_posto.cnpj                                AS cnpj_posto     ,
					CASE WHEN tbl_pedido.tipo_pedido = 179 THEN
						'F'
					ELSE
						'G'
					END                               AS tipo_pedido    ,
					tbl_pedido.pedido                                   ,
					tbl_condicao.codigo_condicao                        ,
					tbl_peca.referencia               AS peca_referencia,
					tbl_pedido_item.qtde              AS peca_quantidade,
					tbl_pedido_item.pedido_item                         ,
					tbl_pedido_item.preco,
					tbl_pedido_item.peca
				INTO TEMP tmp_pedido_ibbl
				FROM tbl_pedido
				JOIN tbl_condicao     ON tbl_pedido.condicao  = tbl_condicao.condicao
				JOIN tbl_pedido_item  ON tbl_pedido.pedido    = tbl_pedido_item.pedido
				JOIN tbl_posto        ON tbl_pedido.posto     = tbl_posto.posto
				JOIN tbl_peca         ON tbl_pedido_item.peca = tbl_peca.peca
				WHERE     tbl_pedido.fabrica                  = $fabrica
				AND       tbl_pedido.posto                    <> 6359
				AND       tbl_pedido.status_pedido            = 1
				AND       tbl_pedido.exportado                IS NULL
				AND       tbl_pedido.finalizado               IS NOT NULL
				AND       tbl_pedido.troca                    IS NOT TRUE
				ORDER BY  tbl_pedido.pedido, tbl_peca.referencia";
	$query = pg_query($con, $sql);
	$msg_erro.= pg_last_error($con);

	if (!empty($msg_erro)) {
		throw new Exception($msg_erro);
	}

	$query = pg_query($con, "SELECT distinct pedido, data_pedido, cnpj_posto, tipo_pedido, codigo_condicao from tmp_pedido_ibbl");

	if (pg_num_rows($query) > 0) {
		$f_ped = fopen($arq_pedido, 'w');
		$f_item = fopen($arq_item, 'w');

		$prepare_itens = pg_prepare($con, "query_itens", "SELECT peca_referencia, peca_quantidade, pedido, preco FROM tmp_pedido_ibbl WHERE pedido = $1");
		$prepare_os = pg_prepare($con, "query_oss", "SELECT array_to_string(array(SELECT tbl_os.sua_os FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) JOIN tbl_os USING(os) WHERE tbl_os_item.pedido = $1 AND tbl_os.fabrica = $2 AND tbl_os.fabrica = tbl_os_item.fabrica_i), ',') AS suas_os");

		if (!is_resource($prepare_itens) or !is_resource($prepare_os)) {
			throw new Exception("Erro ao preparar queries");
		}


		while ($fetch = pg_fetch_array($query)) {
			$data_pedido = $fetch['data_pedido'];
			$cnpj_posto = $fetch['cnpj_posto'];
			$tipo_pedido = $fetch['tipo_pedido'];
			$pedido = $fetch['pedido'];
			$codigo_condicao = $fetch['codigo_condicao'];

			$query_oss = pg_execute($con, "query_oss", array($pedido, $fabrica));

			if (pg_num_rows($query_oss) > 0) {
				$oss = pg_fetch_result($query_oss, 0, 0);
			} else {
				$oss = '';
			}

			$write = "$data_pedido\t$cnpj_posto\t$tipo_pedido\t$pedido\t$codigo_condicao\t$oss\n";
			fwrite($f_ped, $write);

			$query_itens = pg_execute($con, "query_itens", array($pedido));

			while ($fetch_itens = pg_fetch_array($query_itens)) {
				$peca_referencia = $fetch_itens['peca_referencia'];
				$peca_quantidade = $fetch_itens['peca_quantidade'];
				$pedido_pedido_item = $fetch_itens['pedido'];
				$preco = $fetch_itens['preco'];
				
				$write = "$peca_referencia\t$peca_quantidade\t$pedido_pedido_item\t$preco\n";
				fwrite($f_item, $write);
			}

		}

		fclose($f_ped);
		fclose($f_item);

		if (file_exists($arq_pedido) and (filesize($arq_pedido) > 0)) {
			$query_data = pg_query($con, "SELECT to_char(current_timestamp, 'YYYY-MM-DD-HH24-MI')");
			$data = pg_fetch_result($query_data, 0, 0);

			$destino = '/home/' . $vet['fabrica'] . '/telecontrol-' . $vet['fabrica'] . '/pedido-' . $data . '.txt';
			$destino_bkp = '/home/' . $vet['fabrica'] . '/telecontrol-' . $vet['fabrica'] . '/bkp/pedido-' . $data . '.txt';
			copy($arq_pedido, $destino);
			copy($arq_pedido, $destino_bkp);
			system ("/usr/bin/uuencode  $destino pedido-$data.txt | /usr/sbin/mailsubj \"Exportação PEDIDOS Assist/Ibbl $data\" helpdesk@telecontrol.com.br");

			if (file_exists($arq_item) and (filesize($arq_item) > 0)) {
				$destino_item = '/home/' . $vet['fabrica'] . '/telecontrol-' . $vet['fabrica'] . '/pedido-item-' . $data . '.txt';
				$destino_item_bkp = '/home/' . $vet['fabrica'] . '/telecontrol-' . $vet['fabrica'] . '/bkp/pedido-item-' . $data . '.txt';
				copy($arq_item, $destino_item);
				copy($arq_item, $destino_item_bkp);
				system ("/usr/bin/uuencode $destino_item pedido-item-$data.txt | /usr/sbin/mailsubj \"Exportação ITENS DOS PEDIDOS Assist/Ibbl $data\" helpdesk@telecontrol.com.br");
			}

			$begin = pg_query($con, "BEGIN");
			$update = "UPDATE tbl_pedido SET exportado = current_timestamp, status_pedido = 2
						WHERE tbl_pedido.pedido IN (SELECT pedido::numeric FROM tmp_pedido_ibbl)
						AND   tbl_pedido.exportado IS NULL";
			$qryup = pg_query($con, $update);
			$msg_erro.= pg_last_error($con);

			if (!empty($msg_erro)) {
				$rollback = pg_query($con, "ROLLBACK");
				throw new Exception($msg_erro);
			}

			$commit = pg_query($con, "COMMIT");

			$msg_pedido = file_get_contents($arq_pedido);
			$msg_item = file_get_contents($arq_item);

			$msg_pedido = str_replace("\n", "<br>", $msg_pedido);
			$msg_item = str_replace("\n", "<br>", $msg_item);

			Log::envia_email($vet, utf8_decode("IBBL - Geração de Pedidos"), $msg_pedido);
			Log::envia_email($vet, utf8_decode("IBBL - Geração de Item de Pedido"), $msg_item);

		}

	}

	$phpCron->termino();
	
} catch (Exception $e) {
	echo $e->getMessage();
}

