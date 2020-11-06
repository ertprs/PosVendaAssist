<?php

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_usuario.php';
include __DIR__.'/funcoes.php';

use Posvenda\Regras;

include_once S3CLASS;

include_once '../class/communicator.class.php';


$nao_fn_pedido_cancela_garantia = array(147, 160);
if($replica_einhell) $nao_fn_pedido_cancela_garantia[] = $login_fabrica;
$auditoria_liberada = true;

if ($_POST["btn_acao"] == "submit") {
	$produtos      = $_POST['os_produto'];
	$hd_chamado    = $_POST['hd_chamado'];
	$troca_produto = $_POST['troca_produto'];

	if(strlen(trim($hd_chamado))==0){
        $hd_chamado = null;
    }

	$produto_form = array();
	$qtde_produto = 0;

	foreach ($produtos as $key => $os_produto) {

		$qtde_produto++;

		$acao = $_POST["acao"][$os_produto];

		$sql = "SELECT (tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto_nome
				FROM tbl_produto
				INNER JOIN tbl_os_produto ON tbl_os_produto.produto = tbl_produto.produto
				WHERE tbl_os_produto.os_produto = {$os_produto}";
		$res = pg_query($con, $sql);

		$produto_nome = pg_fetch_result($res, 0, "produto_nome");

		$produto_form[$os_produto]["familia"]               = $_POST["familia"][$os_produto];
		$produto_form[$os_produto]["produto_troca"]         = $_POST["produto_troca"][$os_produto];
		$produto_form[$os_produto]["numero_registro"]       = $_POST["numero_registro"][$os_produto];
		$produto_form[$os_produto]["causa_troca"]           = $_POST["causa_troca"][$os_produto];
		$produto_form[$os_produto]["pecas"]                 = $_POST["pecas"][$os_produto];
        $produto_form[$os_produto]["produto_troca_qtde"]    = $_POST["produto_troca_qtde"][$os_produto];
		$produto_form[$os_produto]["marca_troca"]    	    = $_POST["marca_troca"][$os_produto];
		$produto_form[$os_produto]["enviar_para"]    	    = $_POST["enviar_para"][$os_produto];
		
		if (empty($produto_form[$os_produto]["produto_troca"])) {
			$msg_erro["msg"]["produto_troca"] = "Preencha os campos obrigatórios";
			$msg_erro["campos"][]             = "{$os_produto}|produto_troca";
		}

		if (empty($produto_form[$os_produto]["causa_troca"])) {
			$msg_erro["msg"]["causa_troca"] = "Preencha os campos obrigatórios";
			$msg_erro["campos"][]           = "{$os_produto}|causa_troca";
		}

		$sqlFabrica = "SELECT fabrica, sua_os FROM tbl_os WHERE os = {$os}";
		$resFabrica = pg_query($con, $sqlFabrica);

		$fabrica = pg_fetch_result($resFabrica, 0, "fabrica");
		$sua_os = pg_fetch_result($resFabrica, 0, "sua_os");

		if ($fabrica != $login_fabrica) {
		    $msg_erro["msg"][] = "Erro";
		}

		/**
		 * Verifica se o produto já está trocado
		 * EXCEÇÃO: PST, será feita uma nova troca
		 */
		$sql = "SELECT tbl_os_troca.os_troca
				  FROM tbl_os_troca
				  JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_troca.os
				   AND tbl_os_troca.produto      = tbl_os_produto.produto
				   AND tbl_os_produto.os_produto = {$os_produto}
				   JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				   JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.troca_produto IS TRUE AND tbl_os_item.pedido IS NOT NULL
				 WHERE tbl_os_troca.fabric = {$login_fabrica}
				   AND tbl_os_troca.os     = {$os}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$msg_erro["msg"][] = "Produto {$produto_nome} já possui troca/ressarcimento lançado";
		}
		

		/**
		 * Verifica se a Ordem de Serviço já está em Extrato
		 */
		$sql = "SELECT tbl_os_extra.extrato
				FROM tbl_os
				INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.os = {$os}
				AND tbl_os_extra.extrato IS NOT NULL";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$msg_erro["msg"]["os_extrato"] = "Não foi possível lançar a troca/ressarcimento, a Ordem de Serviço já está em Extrato";
		}            

		/**
		 * Verifica se o produto a ser trocado está marcado como listra_troca = true
		 */

		foreach($produto_form[$os_produto]['produto_troca'] as $key => $produto) {
			$sql = "SELECT 
						tbl_produto.lista_troca, 
						tbl_produto.parametros_adicionais::jsonb->>'fora_linha' AS fora_linha
					FROM tbl_produto
					WHERE produto = {$produto}
					AND lista_troca IS TRUE";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) == 0) {
				$msg_erro["msg"][] = "Não foi possível trocar o produto {$produto_nome} o produto selecionado para troca não está habilitado para realizar a troca";
			}
		}

		/**
		 * Verifica se o produto está na tbl_peca
		 */
			

            if(!isset($msg_erro)){

                $sql_os_pecas = "SELECT os_item, peca, pedido, pedido_item, qtde, posto_i, os_produto FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
                $res_os_pecas = pg_query($con, $sql_os_pecas);

                if(pg_num_rows($res_os_pecas) > 0){

                    $cont_pecas = pg_num_rows($res_os_pecas);

                    for($p = 0; $p < $cont_pecas; $p++){

                        $os_item_id          = pg_fetch_result($res_os_pecas, $p, "os_item");
                        $os_item_peca        = pg_fetch_result($res_os_pecas, $p, "peca");
                        $os_item_pedido      = pg_fetch_result($res_os_pecas, $p, "pedido");
                        $os_item_pedido_item = pg_fetch_result($res_os_pecas, $p, "pedido_item");
                        $os_item_qtde        = pg_fetch_result($res_os_pecas, $p, "qtde");
                        $os_item_posto       = pg_fetch_result($res_os_pecas, $p, "posto_i");
                        $os_item_os_produto  = pg_fetch_result($res_os_pecas, $p, "os_produto");

                        $motivo_cancelamento_pedido = "Peça cancelada por Troca de Produto na OS";

						if(!empty($os_item_pedido)) {
							$sql_pedido_cancelado = "SELECT
								pedido
								FROM tbl_pedido_cancelado
								WHERE
								pedido = {$os_item_pedido}
								AND posto = {$os_item_posto}
								AND fabrica = {$login_fabrica}
								AND os = {$os}
								AND peca = {$os_item_peca}
								";
							$res_pedido_cancelado = pg_query($con, $sql_pedido_cancelado);

							if(pg_num_rows($res_pedido_cancelado) == 0 and !empty($os_item_pedido)){
							}

							if(strlen(pg_last_error($con)) > 0){

								$msg_erro["msg"][] = "Não foi possível cancelar o pedido das peças para a OS {$os}";

							}else{

								$sql_cancela_item_pedido = "select fn_pedido_item_cancela($os_item_pedido_item, $os_item_qtde); ";
								$res_cancela_item_pedido = pg_query($con, $sql_cancela_item_pedido);

							}
						}
                    }

                }

            } 

			foreach ($produto_form[$os_produto]['produto_troca'] as $key => $produto) {
				
				$sql = "
					SELECT
						tbl_peca.peca
                    FROM tbl_peca
              		INNER JOIN tbl_produto ON  tbl_produto.referencia = tbl_peca.referencia AND tbl_produto.fabrica_i = {$login_fabrica}
					WHERE tbl_peca.fabrica = {$login_fabrica}
					AND tbl_peca.produto_acabado IS TRUE
					AND tbl_produto.produto = {$produto};
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) == 0) {

					$sql = "SELECT referencia, descricao, ipi, origem
							FROM tbl_produto
							WHERE fabrica_i = {$login_fabrica}
							AND produto = {$produto}";
					$res = pg_query($con, $sql);

					$troca_referencia = pg_fetch_result($res, 0, "referencia");
					$troca_descricao  = substr(pg_fetch_result($res, 0, "descricao"),0,50);
					$troca_ipi        = pg_fetch_result($res, 0, "ipi");
					$troca_origem     = pg_fetch_result($res, 0, "origem");

					if (strlen($troca_ipi) == 0) {
						$troca_ipi = 0;
					}

					$sql = "SELECT peca
                            FROM tbl_peca
							WHERE fabrica = $login_fabrica
							$cond_produto_acabado
							and referencia = '$troca_referencia'";
					$res = pg_query($con, $sql);
                    
					if(pg_num_rows($res) == 0) {
                       
						$sql = "
							INSERT INTO tbl_peca
								(fabrica,referencia,descricao,ipi,origem,produto_acabado {$campos})
							VALUES
								({$login_fabrica},'{$troca_referencia}','{$troca_descricao}',{$troca_ipi},'{$troca_origem}', TRUE {$value_campos})
							RETURNING peca;";	
						$res = pg_query($con, $sql);

						if (!pg_last_error($con)) {
                            $produto_form[$os_produto]["peca_produto_troca"][] = pg_fetch_result($res, 0, "peca");
						} else {
                            $msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar troca";
						}

					}else{
						$produto_form[$os_produto]["peca_produto_troca"][] = pg_fetch_result($res, 0, "peca");
					}

				} else {
					$produto_form[$os_produto]["peca_produto_troca"][] = pg_fetch_result($res, 0, "peca");
				}

			}

			/**
			 * Verifica se o produto está na lista básica
			 */
			
			foreach($produto_form[$os_produto]["produto_troca"] as $key => $produto) {
				$peca_produto = $produto_form[$os_produto]["peca_produto_troca"][$key];

				$sql = "SELECT lista_basica
						FROM tbl_lista_basica
						WHERE fabrica = {$login_fabrica}
						AND produto = '{$produto}'
						AND peca = {$peca_produto}";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {
					$sql = "INSERT INTO tbl_lista_basica
						(produto, peca, qtde, fabrica, ativo)
						VALUES
						({$produto}, {$peca_produto}, 1, {$login_fabrica}, TRUE)";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar troca";
					}
				}
			}
			
            /**
            * - Verifica, para PST, se há uma nova troca.
            * Se houver, apaga a anterior
            */
            
            $res = pg_query($con,"BEGIN TRANSACTION");

            $sqlDel = "
                DELETE  FROM tbl_os_troca
                WHERE   os              = $os
                AND     fabric          = $login_fabrica
                AND     ressarcimento   IS NOT TRUE
            ";
            $resDel = pg_query($con,$sqlDel);
            if(!pg_last_error($con)){
                $res = pg_query($con,"COMMIT TRANSACTION");
            }else{
                $res = pg_query($con,"ROLLBACK TRANSACTION");
                $msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar nova troca";
            }
        
		/**
		 * Verifica se o posto da Ordem de Serviço está credenciado
		 */
		$sql = "
			SELECT
				tbl_posto_fabrica.posto
			FROM tbl_posto_fabrica
			JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = {$login_fabrica}
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_os.os = {$os}
			AND tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';
		";

		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$msg_erro["msg"]["posto_descredenciado"] = "Não foi possível lançar a troca/ressarcimento o Posto Autorizado da Ordem de Serviço está DESCREDENCIADO";
		}
		
	}

	$setor_responsavel    = $_POST["setor_responsavel"];
	$situacao_atendimento = $_POST["situacao_atendimento"];
	$gerar_pedido         = $_POST["gerar_pedido"];


	if ($envio_consumidor != "t") {
		$envio_consumidor = "f";
	}

	if ($gerar_pedido != "t" || $acao == "base_troca") {
		$gerar_pedido = "f";
	}

	$observacao = pg_escape_string(trim($_POST["observacao"]));

	if (empty($setor_responsavel)) {
		$msg_erro["msg"]["setor_responsavel"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "setor_responsavel";
	}

	if (!strlen($situacao_atendimento)) {
		$msg_erro["msg"]["situacao_atendimento"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "situacao_atendimento";
	}

	if (!isset($msg_erro)) {
		pg_query($con, "BEGIN");

        $acao_troca_ressarcimento  = false;
        $troca_ressarcimento_total = 0;

		foreach ($produto_form as $os_produto => $campos) {
			$acao = $_POST["acao"][$os_produto];

			$peca          = $campos["peca_produto_troca"];
			$pecas         = $campos["pecas"];
			$ressarcimento = "FALSE";
			$acao_troca_ressarcimento = true;
            $produto_troca_qtde = $campos["produto_troca_qtde"];
			$troca_ressarcimento_total++;

			$sql = "SELECT produto FROM tbl_os_produto WHERE os = {$os} AND os_produto = {$os_produto}";
			$res = pg_query($con, $sql);

			$produto = pg_fetch_result($res, 0, "produto");

            $causa_troca    = $campos["causa_troca"];            
			$ri             = $campos["numero_registro"];
			$originou_troca = $campos["pecas"];


			if (is_array($peca)) {
				
				foreach($peca as $key => $value) {
                    //PST
                    //Verificar se o pedido da O.S não esta faturado, se não estiver, fazer o cancelamento do PEDIDO.
                    $sql_pedido_faturado = "SELECT tbl_os_item.peca, tbl_os_item.qtde,  tbl_pedido_item.qtde_faturada, tbl_pedido.pedido, tbl_os_item.os_item, tbl_os.os, tbl_pedido.status_pedido,tbl_pedido_item.qtde_faturada_distribuidor
                                              FROM tbl_os_item
                                        INNER JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                        INNER JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
                                        INNER JOIN tbl_pedido_item ON tbl_os_item.pedido        = tbl_pedido_item.pedido
                                                                  AND tbl_os_item.pedido_item   = tbl_pedido_item.pedido_item
                                        INNER JOIN tbl_pedido      ON tbl_pedido.pedido         = tbl_pedido_item.pedido
										WHERE tbl_os.os  = $os
										AND tbl_os_item.peca = $value";
                    $res_pedido_faturado = pg_query($con, $sql_pedido_faturado);
                    if(pg_num_rows($res_pedido_faturado) > 0){
                        $qtde_faturada  = pg_fetch_result($res_pedido_faturado, 0, qtde_faturada);
                        $qtde_faturada_distribuidor  = pg_fetch_result($res_pedido_faturado, 0, qtde_faturada_distribuidor);
                        $qtde           = pg_fetch_result($res_pedido_faturado, 0, qtde);
                    }

					if ($qtde_faturada == 0 and $qtde_faturada_distribuidor == 0 ) {
                        $pedido_cancela_garantia = true;

                        if (in_array($login_fabrica, $nao_fn_pedido_cancela_garantia)) {
                            $sql_embarque = "SELECT embarque
                                FROM tbl_os_produto
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_embarque_item USING(os_item)
                                WHERE tbl_os_produto.os = $os
                                AND   tbl_os_item.pedido IS NOT NULL
                                AND tbl_os_item.peca = $value
                                AND (
                                    tbl_embarque_item.liberado IS NOT NULL
                                    OR tbl_embarque_item.impresso IS NOT NULL
                                )";
                            $res_embarque = pg_query($con, $sql_embarque);

                            if (pg_num_rows($res_embarque) > 0) {
                                $pedido_cancela_garantia = false;
                            }
                        }

                        if (false === $pedido_cancela_garantia) {
                            $sql_audit_0 = "SELECT tbl_auditoria_os.auditoria_status
                                FROM tbl_auditoria_os
                                INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                                WHERE os = $os
                                AND tbl_auditoria_os.observacao = 'OS em intervenção da fábrica por Troca de Produto'
                                AND cancelada IS NULL
                                ORDER BY data_input DESC";
                            $res_audit_0 = pg_query($con, $sql_audit_0);

                            if (pg_num_rows($res_audit_0) == 0) {
                                $sql_audit = "INSERT INTO tbl_auditoria_os (
                                        os,
                                        auditoria_status,
                                        observacao
                                    ) VALUES (
                                        $os,
                                        3,
                                        'OS em intervenção da fábrica por Troca de Produto'
                                    )";
                                $res_audit = pg_query($con, $sql_audit);

                                $auditoria_liberada = false;
                            }
                        } else {
                            $sqlVerPedidoFat = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Produto Trocado',null)
                                From tbl_os_produto
                                JOIN tbl_os_item USING(os_produto)
                                WHERE tbl_os_produto.os = $os
                                AND   tbl_os_item.pedido NOTNULL
                                AND tbl_os_item.peca = $value";

                            $resVerPedidoFat = pg_query($con, $sqlVerPedidoFat);
                        }
                    }

					$sql = "INSERT INTO tbl_os_troca
						(fabric, os,  produto, setor, situacao_atendimento, peca, observacao, causa_troca, gerar_pedido, ressarcimento, ri, envio_consumidor $campo_distribuidor $interventor_admin_campo)
						VALUES
						({$login_fabrica}, {$os}, {$produto}, '{$setor_responsavel}', {$situacao_atendimento}, {$value}, '{$observacao}', {$causa_troca}, '{$gerar_pedido}', {$ressarcimento}, '{$ri}', '{$envio_consumidor}' $value_distribuidor $interventor_admin_value)";
                   $res = pg_query($con, $sql);

                }
				
			} else {

                //PST
                //Verificar se o pedido da O.S não esta faturado, se não estiver, fazer o cancelamento do PEDIDO.
				$sql_pedido_faturado = "SELECT
                                            tbl_os_item.peca,
                                            tbl_os_item.qtde,
                                            tbl_pedido_item.qtde_faturada,
                                            tbl_pedido.pedido,
                                            tbl_os_item.os_item,
                                            tbl_os.os,
                                            tbl_pedido.status_pedido,
                                            tbl_pedido_item.qtde_faturada_distribuidor,
                                            tbl_os_item.peca,
                                            tbl_pedido_item.qtde_cancelada
                                        FROM tbl_os_item
                                        INNER JOIN tbl_os_produto on tbl_os_produto.os_produto  = tbl_os_item.os_produto
                                        INNER JOIN tbl_os on tbl_os.os = tbl_os_produto.os
                                        INNER JOIN tbl_pedido_item on tbl_os_item.pedido = tbl_pedido_item.pedido and tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                                        INNER JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
					                    WHERE tbl_os.os  = $os";
                $res_pedido_faturado = pg_query($con, $sql_pedido_faturado);

                if(pg_num_rows($res_pedido_faturado) > 0){

					for($c=0;$c<pg_num_rows($res_pedido_faturado);$c++) {
						$qtde_faturada  = pg_fetch_result($res_pedido_faturado, $c, qtde_faturada);
						$qtde_cancelada = pg_fetch_result($res_pedido_faturado, $c, qtde_cancelada);
						$qtde           = pg_fetch_result($res_pedido_faturado, $c, qtde);
						$peca_cancela   = pg_fetch_result($res_pedido_faturado, $c, peca);
						$qtde_faturada_distribuidor  = pg_fetch_result($res_pedido_faturado, $c, qtde_faturada_distribuidor);

						if($qtde_faturada == 0 and $qtde_faturada_distribuidor == 0 and $qtde_cancelada == 0 ){
                            $pedido_cancela_garantia = true;

                            if (in_array($login_fabrica, $nao_fn_pedido_cancela_garantia)) {
                                $sql_embarque = "SELECT embarque
                                    FROM tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    JOIN tbl_embarque_item USING(os_item)
                                    WHERE tbl_os_produto.os = $os
                                    AND   tbl_os_item.pedido IS NOT NULL
                                    AND tbl_os_item.peca = $peca_cancela
                                    AND (
                                        tbl_embarque_item.liberado IS NOT NULL
                                        OR tbl_embarque_item.impresso IS NOT NULL
                                    )";
                                $res_embarque = pg_query($con, $sql_embarque);

                                if (pg_num_rows($res_embarque) > 0) {
                                    $pedido_cancela_garantia = false;
                                }
                            }

                            if (false === $pedido_cancela_garantia) {
                                $sqlVerPedidoFat = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Produto Trocado',$login_admin)
                                    From tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    WHERE tbl_os_produto.os = $os
                                    AND   tbl_os_item.pedido NOTNULL
                                    AND tbl_os_item.peca = $value";

                                $resVerPedidoFat = pg_query($con, $sqlVerPedidoFat);
                            } else {
                                $sqlVerPedidoFat = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Ressarcimento Financeiro',$login_admin)
                                    From tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    WHERE tbl_os_produto.os = $os
                                    AND		tbl_os_item.peca = $peca_cancela
                                    AND   tbl_os_item.pedido NOTNULL";
                                $resVerPedidoFat = pg_query($con, $sqlVerPedidoFat);
                            }
						}
					}
				}

				$sql = "INSERT INTO tbl_os_troca
						(fabric, os, admin, produto, setor, situacao_atendimento,  observacao, causa_troca,peca, gerar_pedido, ressarcimento, ri, envio_consumidor $campo_distribuidor)
						VALUES
						({$login_fabrica}, {$os}, {$login_admin}, {$produto}, '{$setor_responsavel}', {$situacao_atendimento},  '{$observacao}', {$causa_troca}, {$peca}, '{$gerar_pedido}', {$ressarcimento}, '{$ri}', '{$envio_consumidor}' $value_distribuidor) RETURNING os_troca";
              	$res = pg_query($con, $sql);

			}

            if (pg_last_error($con)) {
                $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
				break;
			} else {
				
				if (count($originou_troca) > 0) {
					foreach ($originou_troca as $os_item) {
						$sql = "UPDATE tbl_os_item SET originou_troca = TRUE WHERE os_produto = {$os_produto} AND os_item = {$os_item}";
						$res = pg_query($con, $sql);

					}
				}
             

				$sql = "SELECT servico_realizado
					FROM tbl_servico_realizado
					WHERE fabrica = {$login_fabrica}
					AND UPPER(descricao) = UPPER('CANCELADO')";
				$res = pg_query($con, $sql);

				$servico_realizado_cancela_peca = pg_fetch_result($res, 0, "servico_realizado");

				if (empty($servico_realizado_cancela_peca)) {
					$msg_erro["msg"]["servico_realizado_cancela_peca"] = "Troca de produto não configurada";
					break;
				}

				$sql = "
					SELECT tbl_os_item.os_item, tbl_os_item.pedido_item, tbl_os_item.qtde, tbl_os_item.pedido, tbl_os.posto, tbl_os.os, tbl_os_item.peca
					FROM tbl_os_item
					INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
					INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
					LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = {$login_fabrica}
					WHERE tbl_os.os = $os
					AND (tbl_os_item.pedido IS NULL OR tbl_pedido.status_pedido IN(1, 2,12,5,14))
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					while ($osItem = pg_fetch_object($res)) {
						$updateOsItem = "UPDATE tbl_os_item SET servico_realizado = {$servico_realizado_cancela_peca} WHERE os_item = {$osItem->os_item} ";
						$resUpdateOsItem = pg_query($con, $updateOsItem);

						if (!empty($osItem->pedido_item)) {
                            $pedido_cancela_garantia = true;

                            if (in_array($login_fabrica, $nao_fn_pedido_cancela_garantia)) {
                                $sql_embarque = "SELECT embarque
                                    FROM tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    JOIN tbl_embarque_item USING(os_item)
                                    WHERE tbl_os_produto.os = $os
                                    AND   tbl_os_item.pedido IS NOT NULL
                                    AND tbl_os_item.os_item = {$osItem->os_item}
                                    AND (
                                        tbl_embarque_item.liberado IS NOT NULL
                                        OR tbl_embarque_item.impresso IS NOT NULL
                                    )";
                                $res_embarque = pg_query($con, $sql_embarque);

                                if (pg_num_rows($res_embarque) > 0) {
                                    $pedido_cancela_garantia = false;
                                }
                            }

                            if (false === $pedido_cancela_garantia) {
                                $sql_audit_0 = "SELECT tbl_auditoria_os.auditoria_status
                                    FROM tbl_auditoria_os
                                    INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                                    WHERE os = $os
                                    AND tbl_auditoria_os.observacao = 'OS em intervenção da fábrica por Troca de Produto'
                                    AND cancelada IS NULL
                                    ORDER BY data_input DESC";
                                $res_audit_0 = pg_query($con, $sql_audit_0);

                                if (pg_num_rows($res_audit_0) == 0) {
                                    $sql_audit = "INSERT INTO tbl_auditoria_os (
                                            os,
                                            auditoria_status,
                                            observacao
                                        ) VALUES (
                                            $os,
                                            3,
                                            'OS em intervenção da fábrica por Troca de Produto'
                                        )";
                                    $res_audit = pg_query($con, $sql_audit);

                                    $auditoria_liberada = false;
                                }
                            } else {
                                $updatePedidoItem = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,{$osItem->pedido},{$osItem->peca},{$osItem->os_item},'Produto Trocado',null) from tbl_pedido_item
                                                    WHERE pedido_item = {$osItem->pedido_item} and qtde -(qtde_faturada+qtde_cancelada) > 0 ";
                                $resUpdatePedidoItem = pg_query($con, $updatePedidoItem);
                            }

							$atualizaStatusPedido = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$osItem->pedido})";
							$resAtualizaStatusPedido = pg_query($con, $atualizaStatusPedido);
						}

						if (pg_last_error($con)) {
							$msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
							break;
						}
					}
				}
                
				
				$sql = "SELECT servico_realizado
					FROM tbl_servico_realizado
					WHERE fabrica = {$login_fabrica}
					AND troca_produto IS TRUE";					

				if($login_fabrica == 178 AND $gerar_pedido != "t"){
					$sql = "SELECT servico_realizado
					FROM tbl_servico_realizado
					WHERE fabrica = {$login_fabrica}
					AND troca_produto IS NOT TRUE
					AND gera_pedido IS NOT TRUE
					AND peca_estoque IS TRUE
					AND troca_de_peca IS TRUE
					AND ativo IS TRUE";
				}

				$res = pg_query($con, $sql);

				$servico_realizado_troca_produto = pg_fetch_result($res, 0, "servico_realizado");

				if (empty($servico_realizado_troca_produto)) {
					$msg_erro["msg"]["servico_realizado_troca_produto"] = "Troca de produto não configurada";
					break;
				}

				if (is_array($peca)) {

					foreach($peca as $key => $value) {

                        $qtde = 1;

                        $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE peca = {$value} AND fabrica = {$login_fabrica}";
                        $res = pg_query($con, $sql);

                        $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");
						$devolucao_obrigatoria = (empty($devolucao_obrigatoria)) ?"f":$devolucao_obrigatoria;

						$sql = "INSERT INTO tbl_os_item
							(os_produto, peca, qtde, servico_realizado, peca_obrigatoria $campo_defeito_peca)
							VALUES
							({$os_produto}, {$value}, $qtde, {$servico_realizado_troca_produto}, '{$devolucao_obrigatoria}' $valor_defeito_peca)";
                        $res = pg_query($con, $sql);

					}
				} else {

                    $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE peca = {$peca} AND fabrica = {$login_fabrica}";
                    $res = pg_query($con, $sql);

                    $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");
					$devolucao_obrigatoria = (empty($devolucao_obrigatoria)) ?"f":$devolucao_obrigatoria;

					$sql = "INSERT INTO tbl_os_item
						(os_produto, peca, qtde, servico_realizado, peca_obrigatoria $campo_defeito_peca)
						VALUES
						({$os_produto}, {$peca}, 1, {$servico_realizado_troca_produto}, '{$devolucao_obrigatoria}' $valor_defeito_peca)";
                    $res = pg_query($con, $sql);
				}

                if (pg_last_error($con)) {
                    $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
					break;
				}
				
			}
		}

		if (count($msg_erro["msg"]) == 0) {

			$mensagem = "";

			foreach ($produto_form as $os_produto => $campos) {
				$acao = $_POST["acao"][$os_produto];
				$sql = "SELECT (tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto FROM tbl_os_produto INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto WHERE tbl_os_produto.os_produto = {$os_produto}";
				$res = pg_query($con, $sql);

				$produto_descricao = pg_fetch_result($res, 0, "produto");

				
				if (is_array($campos["produto_troca"])) {
					$produtos_trocar_descricao = array();

					foreach ($campos["produto_troca"] as $key => $value) {
						$sql = "SELECT (referencia || ' - ' || descricao) AS produto FROM tbl_produto WHERE produto = {$value}";
                        $res = pg_query($con, $sql);

						$produtos_trocar_descricao[] = pg_fetch_result($res, 0, "produto");
					}
				} else {
					$sql = "SELECT (referencia || ' - ' || descricao) AS produto FROM tbl_produto WHERE produto = {$campos['produto_troca']}";
                    $res = pg_query($con, $sql);

                    $produtos_trocar_descricao = pg_fetch_result($res, 0, "produto");
				}

				$mensagem .= "O produto {$produto_descricao} será trocado pelo(s) produto(s) <strong>".((is_array($produtos_trocar_descricao)) ? implode(",", $produtos_trocar_descricao) : $produtos_trocar_descricao)."</strong>, as peças lançadas para este produto foram canceladas. <br />";
			}

			if ($acao_troca_ressarcimento == true) {

				$sql = "SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
				$res = pg_query($con, $sql);

                $sua_os = pg_fetch_result($res, 0, "sua_os");
                $posto  = pg_fetch_result($res, 0, "posto");

				$sql = "INSERT INTO tbl_comunicado (
							fabrica,
							posto,
							obrigatorio_site,
							tipo,
							ativo,
							descricao,
							mensagem
						) VALUES (
							{$login_fabrica},
							{$posto},
							true,
							'Com. Unico Posto',
							true,
							'Troca/Ressarcimento de Produto(s) da OS {$sua_os}',
							'{$mensagem}'
						)";
				$res = pg_query($con, $sql);

			}
			 
			if (count($msg_erro["msg"]) == 0) {
				$sql = "SELECT fn_os_status_checkpoint_os({$os}) AS status";
				$res = pg_query($con, $sql);

				$status = pg_fetch_result($res, 0, "status");

				$sql = "UPDATE tbl_os SEt status_checkpoint = $status WHERE fabrica = {$login_fabrica} AND os = {$os}";
				$res = pg_query($con, $sql);

                if (pg_last_error($con)) {
                    $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
				}
			}

			if(!count($msg_erro["msg"]) && $auditoria_unica == true and $auditoria_liberada == true){
				
				$sqlAuditoria = "
					UPDATE tbl_auditoria_os SET
						liberada = CURRENT_TIMESTAMP
					WHERE os = $os
					AND liberada IS NULL
					AND cancelada IS NULL
					AND reprovada IS NULL
					AND bloqueio_pedido IS TRUE
				";
				pg_query($con,$sqlAuditoria);

				if(strlen(pg_last_error()) > 0){
					$msg_erro["msg"][] = "Erro ao liberar auditoria da OS";
				}
			}
			

			if (!count($msg_erro["msg"])) {
				
				pg_query($con, "COMMIT");
				
				header("Location: os_press.php?os={$os}");				
				exit;

			} else {
				pg_query($con, "ROLLBACK");
			}
		} else {
			pg_query($con, "ROLLBACK");
		}
	}
}

$layout_menu = "callcenter";
$title       = "TROCA DE PRODUTO DA ORDEM DE SERVIÇO";

include __DIR__.'/cabecalho_new.php';

$plugins = array(
   "alphanumeric",
   "autocomplete",
   "jquery_multiselect",
   "shadowbox",
   "ajaxform",
   "mask",
   "datepicker",
   "price_format",
   "select2"
);

include __DIR__."/plugin_loader.php";

?>

<style>

.ms-container {
	width: 92%;
	margin: 0 auto;
}

span.select2-container {
	width: 100% !important;
}

span.select2-dropdown {
	width: 330px !important;
}

li.select2-results__option {
	border-bottom: 1px solid #ddd;
	margin-bottom: 3px;
}

</style>

<script>

function retorna_produto(retorno) {
    var compara = [];

    $("#produto-troca-" + retorno.retornaIndice + " > option").each(function(k,val){
        compara.push(this.value);
    });

    
    if ($.inArray(retorno.produto,compara) == -1) {
        $("div.produto-selecionado-"+retorno.retornaIndice).find("ul").append("\
            <li class='active' style='float: none; margin-top: 15px;' data-produto-id='"+retorno.produto+"'  data-produto-referencia='"+retorno.referencia+"' data-os-produto='"+retorno.retornaIndice+"'  >\
            <a href='#' class='remover-produto' ><button type='button' class='btn btn-danger btn-mini' ><i class='icon-remove icon-white'></i></button> "+retorno.referencia+" - "+retorno.descricao+"</a>\
            </li>\
        ");

        $("#produto-troca-"+retorno.retornaIndice).append("\
            <option value='"+retorno.produto+"' selected >"+retorno.referencia+"</value>\
        ");

    } else {
        alert("Esse produto já está na lista de troca para essa OS");
    }
     
}

$(function() {
    
    $("input.numeric").numeric();

    $(document).on("click", "a.remover-produto", function(e) {
        e.preventDefault();
        var li = $(this).parents("li");
        var produto = $(li).data("produto-id");
        var os_produto = $(li).data("os-produto");
        $("#produto-troca-"+os_produto).find("option[value="+produto+"]").first().remove();
        $(li).remove();
    });

	$("select.pecas").multiSelect();

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this), ["listaTroca", "retornaIndice"]);		
	});

});


function addParametrosLupa(){

	var marca = $("#marca_troca").val();
	var familia = '<?=$familia_produto?>';
	$("input[name^=lupa_config]").attr({"marca":marca,"familia":familia});
}

</script>

<?php
if(count($msg_erro["msg"]) > 0) {
    $msg = array_unique($msg_erro["msg"]);
?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg)?></h4></div>
<?php
}

?>

<div class="row">
	<b class="obrigatorio pull-right"> * Campos obrigatórios </b>
</div>

<form name="frm_os" method="POST" class="form-search form-inline tc_formulario" enctype="multipart/form-data" >
	<div class="titulo_tabela">Trocar Produto em Garantia</div>
	<input type="hidden" name="hd_chamado" value="<?=$hd_chamado?>" />
	<input type="hidden" id="qtde_dias" name="qtde_dias" value="<?=$qtde_dias?>" />
	<input type="hidden" id="autoriza_gravacao" name="autoriza_gravacao" value="" />
	<input type="hidden" name="posto_status" id="posto_status" value="" />

	<?php 

	$sql_produto = "
		SELECT
			tbl_os_produto.os_produto,
			(tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto			
		FROM tbl_os_produto
		INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
		INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
		WHERE tbl_os_produto.os = {$os}
		ORDER BY tbl_os_produto.os_produto, tbl_produto.produto ASC";
		
	$res_produto = pg_query($con, $sql_produto);
	
	if (pg_num_rows($res_produto) > 0) {
		$total_trocados   = 0;
		
        $xproduto = "";//hd_chamado=3032797
        while ($produto = pg_fetch_object($res_produto)) {
            if ($produto->produto <> $xproduto) { //hd_chamado=3032797
                $xproduto = $produto->produto;
            } else {
                continue;
            } ?>
			<div class="informacoes_produto">
				<input type="hidden" name="os_produto[]" value="<?=$produto->os_produto?>" />
				<div class="subtitulo_tabela"><?=$produto->produto?></div>
				<?php
				$sql_troca_efetuada = "
					SELECT
						tbl_os_troca.os_troca,
						tbl_os_troca.ressarcimento
					FROM tbl_os_troca
					JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_troca.os AND tbl_os_troca.produto = tbl_os_produto.produto AND tbl_os_produto.os_produto = {$produto->os_produto}
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.troca_produto IS TRUE AND tbl_os_item.pedido IS NOT NULL
					WHERE tbl_os_troca.fabric = {$login_fabrica}
					AND tbl_os_troca.os = {$os}
					ORDER BY tbl_os_troca.os_troca DESC
					LIMIT 1
				";
				$res_troca_efetuada = pg_query($con, $sql_troca_efetuada);

				$troca_ressarcimento_efetuado = false;

				if (pg_num_rows($res_troca_efetuada) > 0 || (pg_num_rows($res_troca_efetuada) > 0 && pg_fetch_result($res_troca_efetuada, 0, "ressarcimento") != "t")) {
					$troca_ressarcimento_efetuado = true;
				}else{
					$sql_troca_efetuada = "SELECT ressarcimento From tbl_os_troca WHERE os = $os and ressarcimento "; 
					$res_troca_efetuada = pg_query($con, $sql_troca_efetuada); 
					if(pg_num_rows($res_troca_efetuada) > 0) {
						$troca_ressarcimento_efetuado = true;
					}
				}

				if ($troca_ressarcimento_efetuado === false) { ?>
					
					<?php

                    $produto_indice = $produto->os_produto;
                    $tipo_troca     = getValue("acao[{$produto_indice}]");

		   		?>
					<div class="row-fluid troca" >
						<div class="span1"></div>

						<div class='span4'>
							<div class='control-group <?=(in_array("$produto_indice|produto_troca", $msg_erro["campos"])) ? "error" : ""?>'>
								<label class='control-label' for='produto_referencia[<?=$produto_indice?>]'>Trocar pelo produto:</label>
								<div class='controls controls-row'>
									<div class='span10  input-append'>
										<input type='hidden' data-product-index="<?=$produto_indice?>" name='produto_troca[<?=$produto_indice?>]' value='<?$_POST["produto_troca[$produto_indice"]?>' />
										<input type='hidden' data-product-index="<?=$produto_indice?>" name="familia[<?=$produto_indice?>]" value='<?$_POST["familia[$produto_indice"]?>' />
										<input type='text'   data-product-index="<?=$produto_indice?>" name="referencia[<?=$produto_indice?>]" class='span8 ' value=""
											placeholder="Referência" />
										<span class='add-on' rel='lupa'><i class='icon-search'></i></span>
										<input type='hidden' name='lupa_config' tipo='produto' retornaIndice="<?=$produto_indice?>"
										 listaTroca="true" parametro='referencia'  />
									</div>
								</div>
							</div>
						</div>
						<div class='span4'>
							<div class='control-group '>
								<label class='control-label' for='produto_descricao'></label>
								<div class='controls controls-row'>
									<div class='span12 input-append'>
									<input type='text' class='span10' data-product-index="<?=$produto_indice?>"
										   name='produto_descricao[<?=$produto_indice?>]'
									placeholder="Descrição" value="" />
										<span class='add-on' rel='lupa'><i class='icon-search'></i></span>
										<input type='hidden' name='lupa_config' tipo='produto' retornaIndice="<?=$produto_indice?>"
										 listaTroca="true" parametro='descricao' />
									</div>
								</div>
							</div>
						</div>
					</div>
					

					<div class="row-fluid produto-selecionado-<?=$produto_indice?> troca" >
						<div class="span1" ></div>
						<div class="span8" >
							<?php
							$produto_troca = array();

							foreach($_POST["produto_troca"][$produto_indice] as $key => $produto) {
	                        	$sql = "SELECT produto, referencia, descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
								$res = pg_query($con, $sql);

								$produto_troca[] = array(
									"produto" => pg_fetch_result($res, 0, "produto"),
									"referencia" => pg_fetch_result($res, 0, "referencia"),
									"descricao" => pg_fetch_result($res, 0, "descricao")
								);
							}

							if (!count($msg_erro["msg"])) {
								if ($usaProdutoGenerico) {
									$whereProdutoGerencico = "AND tbl_produto.produto_principal IS TRUE";
								}

								
								$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao
										FROM tbl_os
										INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
										INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
										WHERE tbl_os.fabrica = {$login_fabrica}
										AND tbl_os_produto.os_produto = {$produto_indice}
										{$whereProdutoGerencico}";
								$res = pg_query($con, $sql);
								if (pg_num_rows($res) > 0) {
									$produto_troca[] = array(
										"produto" => pg_fetch_result($res, 0, "produto"),
										"referencia" => pg_fetch_result($res, 0, "referencia"),
										"descricao" => pg_fetch_result($res, 0, "descricao")
									);
								}
								
							}

							if(count($produto_troca) == 0) {
							?>
							<span class="label label-important" >É necessário informar pelo menos um produto</span>
							<br />
							<? } ?>
							<select id="produto-troca-<?=$produto_indice?>" name="produto_troca[<?=$produto_indice?>][]" style="display: none;" multiple="multiple" >
								<?php
								foreach($produto_troca as $key => $produto) {
									echo "<option value='{$produto['produto']}' selected >{$produto['referencia']}</option>";
								}
								?>
        	                </select>
                            <label class="control-label" for="produto_troca_qtde">Lista de Produtos</label>
							<ul class="nav-tabs produtos_troca" style="margin-top:10px; margin-left:0px;">
								<?php
								foreach($produto_troca as $key => $produto) {
									echo "
										<li class='active' style='float: none;' data-produto-id='{$produto['produto']}' data-produto-referencia='{$produto['referencia']}' data-os-produto='{$produto_indice}'  >
									        <a href='#' class='remover-produto' ><button type='button' class='btn btn-danger btn-mini' ><i class='icon-remove icon-white'></i></button> {$produto['referencia']} - {$produto['descricao']}</a>
								                </li>

									";
								}
								?>
							</ul>
						</div>						
					</div>

<?php
					$nome_cliente = getValue("nome_cliente[$produto_indice]");
					$cpf_cliente = getValue("cpf_cliente[$produto_indice]");

					if ($login_fabrica == 151 && empty($msg_erro["msg"])) {
						$nome_cliente = $hd_chamado_nome;
						$cpf_cliente  = $hd_chamado_cpf;
					}
					?>

					<div class="row-fluid troca_ressarcimento" >
						<div class="span1"></div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" for="posto_nome">Número de Registro</label>
								<div class="controls controls-row">
									<div class="span12">
										<input type="text" class='span12' name="numero_registro[<?=$produto_indice?>]" value="<?=getValue("numero_registro[$produto_indice]")?>" maxlength="10" />
									</div>
								</div>
							</div>
						</div>

						<div class="span4">
							<div class='control-group <?=(in_array("$produto_indice|causa_troca", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="posto_nome">Causa da Troca/Ressarcimento</label>
								<div class="controls controls-row">
									<div class="span12">
										<h5 class="asteristico">*</h5>
										<select class='span12' name="causa_troca[<?=$produto_indice?>]" >
											<option value="" >Selecione</option>
											<?php

											$sql_causa_troca = "SELECT causa_troca, descricao FROM tbl_causa_troca WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao ASC";
											$res_causa_troca = pg_query($con, $sql_causa_troca);

											if (pg_num_rows($res_causa_troca)) {
												$causa_troca_post = getValue("causa_troca[{$produto_indice}]");

												while ($causa_troca = pg_fetch_object($res_causa_troca)) {
													$selected = ($causa_troca->causa_troca == $causa_troca_post) ? "selected" : "";

													echo "<option value='{$causa_troca->causa_troca}' {$selected} >{$causa_troca->descricao}</option>";
												}
											}

											?>
										</select>
									</div>
								</div>
							</div>
						</div>
						
					</div>

					<div class="row-fluid troca_ressarcimento" >
						<div class="span1"></div>

						<div class="span8">
							<div class='control-group' >
								<label class="control-label" >Se o motivo da troca/ressarcimento for peça selecione as peças</label>
								<div class="controls controls-row">
									<div class="span12">
										<select class="pecas" name="pecas[<?=$produto_indice?>][]" multiple="multiple" >
											<?php

											$sql_pecas = "SELECT
															tbl_os_item.os_item,
															(tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS peca
														  FROM tbl_os
														  INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
														  INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
														  INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
														  WHERE tbl_os.fabrica = {$login_fabrica}
														  AND tbl_os_produto.os_produto = {$produto_indice}
														  ORDER BY tbl_peca.referencia ASC";

											$res_pecas = pg_query($con, $sql_pecas);

											if (pg_num_rows($res_pecas) > 0) {
												$pecas_post = getValue("pecas[{$produto->os_produto}]");

												while ($peca = pg_fetch_object($res_pecas)) {
													$selected = (in_array($peca->os_item, $pecas_post)) ? "selected" : "";

													echo "<option value='{$peca->os_item}' {$selected} >{$peca->peca}</option>";
												}
											}

											?>
										</select>
									</div>
								</div>
							</div>
						</div>


					</div>
				<?php
				} else {
					$total_trocados++;
					?>
					<br />
					<div class="alert alert-block alert-success" style="margin-bottom: 0px; margin: 10px;" >
						<h4>Produto já trocado/ressarcido</h4>
					</div>
<?php                    
				}
?>
			</div>
			<br />
		<?php
		}
	}

	if ($total_trocados != pg_num_rows($res_produto)) {
	?>
		<div class="titulo_tabela">Informações Adicionais</div>

		<br />

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group <?=(in_array("setor_responsavel", $msg_erro["campos"])) ? "error" : ""?>' >
					<div class="controls controls-row">
						<div class="span12 ">
								<h5 class="asteristico">*</h5>
							    <ul class="nav-list">
								    <li class="nav-header">Setor Responsável</li>
								       	<li><label class="radio"><input type="radio" name="setor_responsavel" value="revenda" <? if (getValue("setor_responsavel") == "revenda") echo "checked"; ?> />Revenda</label></li>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="carteira" <? if (getValue("setor_responsavel") == "carteira") echo "checked"; ?> />Carteira</label></li>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="sac" <? if (getValue("setor_responsavel") == "sac") echo "checked"; ?> />SAC</label></li>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="procon" <? if (getValue("setor_responsavel") == "procon") echo "checked"; ?> />Procon</label></li>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="suporte_tecnico" <? if (getValue("setor_responsavel") == "suporte_tecnico") echo "checked"; ?> />Suporte Técnico</label></li>
								    
							    </ul>
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group <?=(in_array("situacao_atendimento", $msg_erro["campos"])) ? "error" : ""?>' >
					<div class="controls controls-row">
						<div class="span12 ">
							<h5 class="asteristico">*</h5>
						    <ul class="nav-list">
							    <li class="nav-header">Situação do Atendimento</li>
							    <li><label class="radio"><input type="radio" name="situacao_atendimento" value="0" <? if (getValue("situacao_atendimento") == "0") echo "checked"; ?> />Produto em Garantia</label></li>
							    <li><label class="radio"><input type="radio" name="situacao_atendimento" value="50" <? if (getValue("situacao_atendimento") == "50") echo "checked"; ?> />Faturado 50%</label></li>
							    <li><label class="radio"><input type="radio" name="situacao_atendimento" value="100" <? if (getValue("situacao_atendimento") == "100") echo "checked"; ?> />Faturado 100%</label></li>
							</ul>
						</div>
					</div>
				</div>			    
			</div>

			<div class="span2"></div>
		</div>
		<br />
		<br />
		<div class="row-fluid gera_pedido" >
			<div class="span2"></div>

			<div class="span8">
				<div class='control-group' >
					<div class="controls controls-row">
						<div class="span12">
							<div class="alert alert-warning">
								<label type="checkbox">
									<h5>
										<input type="checkbox" name="gerar_pedido" id="gerar_pedido" value="t" <? if (getValue("gerar_pedido") == "t" ) echo "checked"; ?> />
										Gerar Pedido ?
									</h5>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>

		<br />		

		<div class="row-fluid" >
			<div class="span2"></div>

			<div class="span8">
				<div class='control-group' >
					<label class="control-label" for="observacao">Observações</label>
					<div class="controls controls-row">
						<div class="span12">
							<textarea name="observacao" class="span12" ><?=getValue("observacao")?></textarea>
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>

		<br />

        <?php if($btn_gravar_hidden != true){ ?>

		<p>
            <br />
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<input type='hidden' id="interventor_admin" name='interventor_admin' value='' />

		</p>

        <br />

	<?php
        }
	}
	?>

</form>

<?php
include "rodape.php";
?>
