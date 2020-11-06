<?php

try {
	include dirname(__FILE__)."/../../dbconfig.php";
	include dirname(__FILE__)."/../../includes/dbconnect-inc.php";
	include dirname(__FILE__)."/../funcoes.php";

	$login_fabrica = 147;

	system("mkdir /tmp/hitachi/ 2> /dev/null ; chmod 777 /tmp/hitachi/");
	system("mkdir /tmp/hitachi/pedidos/ 2> /dev/null ; chmod 777 /tmp/hitachi/pedidos/");

	if ($_serverEnvironment == "production") {
		$emails = array(
			"helpdesk@telecontrol.com.br",
			"amaral@hitachi-koki.com.br",
			"guilherme.curcio@telecontrol.com.br"
		);

		$arquivo_dir = "/home/hitachi/pos-vendas/hitachi-telecontrol/cancelamento/";
	} else {
		$emails = array(
			"guilherme.curcio@telecontrol.com.br"
		);

		$arquivo_dir = "/home/hitachi/pos-vendas/hitachi-telecontrol/cancelamento/";
	}

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	foreach (glob("{$arquivo_dir}pedido-cancelado*") as $arquivo) {
		$erros = array();
		$arquivo_backup = "/tmp/hitachi/pedidos/".date("YdmHiS")."-importa-pedido-cancelado.txt";
		$arquivo_erro   = "/tmp/hitachi/pedidos/erro-".date("YdmHiS")."-importa-pedido-cancelado.txt";

		if(filesize($arquivo) == 0){
			unlink($arquivo);
			continue;
		}

		$conteudo = file_get_contents($arquivo);
		$conteudo = explode("\n", $conteudo);

		foreach ($conteudo as $linha_numero => $linha_conteudo) {
			if (empty($linha_conteudo)) {
				continue;
			}

			$linha_erros = array();

			if (!empty($linha_conteudo)) {
				list (
						$pedido,
						$pedido_item,
						$peca_referencia,
						$qtde_cancelada,
						$motivo
					) = explode ("\t",$linha_conteudo);

				$pedido          = trim($pedido);
				$pedido_item     = trim($pedido_item);
				$peca_referencia = trim($peca_referencia);
				$qtde_cancelada  = (integer) str_replace(",", ".", trim($qtde_cancelada));
				$motivo          = trim($motivo);

				if (empty($pedido)) {
					$linha_erros[] = "Pedido não informado";
				}

				if (empty($pedido_item)) {
					$linha_erros[] = "Pedido Item não informado";
				}

				if (empty($peca_referencia)) {
					$linha_erros[] = "Referência da Peça não informada";
				}

				if (empty($qtde_cancelada)) {
					$linha_erros[] = "Quantidade Cancelada não informada";
				}

				if (empty($linha_erros)) {
					$sqlPedido = "SELECT pedido, posto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
					$resPedido = pg_query($con, $sqlPedido);

					if (!pg_num_rows($resPedido)) {
						$linha_erros[] = "Pedido {$pedido} não encontrado";
					} else {
						$sqlCancelado = "SELECT status_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido} AND status_pedido = 14";
						$resCancelado = pg_query($con, $sqlCancelado);

						if (pg_num_rows($resCancelado) > 0) {
							$erros[$linha_numero] = array(
								"Pedido {$pedido} já está cancelado"
							);
							continue;
						}

						$posto = pg_fetch_result($resPedido, 0, "posto");

						$sqlPedidoItem = "SELECT 
											tbl_pedido_item.pedido_item, 
											tbl_pedido_item.peca, 
											tbl_os_item.os_item,
											(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)) AS qtde_pendente
										  FROM tbl_pedido_item 
										  LEFT JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
										  WHERE tbl_pedido_item.pedido_item = {$pedido_item} 
										  AND tbl_pedido_item.pedido = {$pedido}";
						$resPedidoItem = pg_query($con, $sqlPedidoItem);

						if (!pg_num_rows($resPedidoItem)) {
							$linha_erros[] = "Pedido Item {$pedido_item} não encontrado para o Pedido {$pedido}";
						} else {
							unset($os);

							$qtde_pendente = pg_fetch_result($resPedidoItem, 0, "qtde_pendente");
							$peca          = pg_fetch_result($resPedidoItem, 0, "peca");
							$os_item       = pg_fetch_result($resPedidoItem, 0, "os_item");

							if (!empty($os_item)) {
								$sqlOs = "SELECT tbl_os_produto.os 
										  FROM tbl_os_item
										  INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
										  WHERE tbl_os_item.os_item = {$os_item}";
								$resOs = pg_query($con, $sqlOs);

								if (pg_num_rows($resOs) > 0) {
									$os = pg_fetch_result($resOs, 0, "os");
								}
							}

							if ($qtde_cancelada > $qtde_pendente) {
								$linha_erros[] = "Quantidade a ser cancelada ({$qtde_cancelada}) da peça {$peca_referencia} do pedido {$pedido} maior que a quantidade pendente ({$qtde_pendente})";
							}
						}
					}
				}

				if (!empty($linha_erros)) {
					$erros[$linha_numero] = $linha_erros;
					continue;
				} else {
					pg_query($con, "BEGIN");

					$sql = "
						INSERT INTO tbl_pedido_cancelado
						(fabrica, posto, pedido, pedido_item, peca, qtde, motivo, data)
						VALUES
						({$login_fabrica}, {$posto}, {$pedido}, {$pedido_item}, {$peca}, {$qtde_cancelada}, '{$motivo}', current_date)
					";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$erros[$linha_numero] = array(
							"Ocorreu um erro ao cancelar a peça {$peca_referencia} do pedido {$pedido} #1"
						);

						pg_query($con, "ROLLBACK");
						continue;
					} 

					$sql = "SELECT fn_atualiza_pedido_item_cancelado ({$peca}, {$pedido}, {$pedido_item}, {$qtde_cancelada})";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$erros[$linha_numero] = array(
							"Ocorreu um erro ao cancelar a peça {$peca_referencia} do pedido {$pedido} #2"
						);

						pg_query($con, "ROLLBACK");
						continue;
					}

					$sql = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido})";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$erros[$linha_numero] = array(
							"Ocorreu um erro ao cancelar a peça {$peca_referencia} do pedido {$pedido} #3"
						);

						pg_query($con, "ROLLBACK");
						continue;
					}

					pg_query($con, "COMMIT");
				}
			}
		}

		if (count($erros) > 0) {
			$f_erro = fopen($arquivo_erro, "w");

			foreach ($erros as $linha_numero => $linha_erros) {
				fwrite($f_erro, "<b style='color: #FF0000;' >Linha {$linha_numero}</b><br />");

				fwrite($f_erro, "<ul>");

				foreach ($linha_erros as $erro) {
					fwrite($f_erro, "<li>{$erro}</li>");
				}

				fwrite($f_erro, "</ul>");

				fwrite($f_erro, "<br />");
			}

			fclose($f_erro);

			mail(implode(",", $emails), "Telecontrol - Erro na Importação Pedidos Cancelados da Hitachi", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
		}

		system("mv {$arquivo} {$arquivo_backup}");
	}

	$phpCron->termino();
} catch (Exception $e) {
	$f_erro = fopen($arquivo_erro, "w");
	fwrite($f_erro, "Ocorreu um erro ao executar o script de importar pedidos cancelados, entrar em contato com o suporte da Telecontrol");
	fclose($f_erro);

	mail(implode(",", $emails), "Telecontrol - Erro na Importação de Pedidos Cancelados da Hitachi", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");

	$phpCron->termino();
}

