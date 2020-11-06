<?php 
    #include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    $logClass = new Log2();
    $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
    $logClass->adicionaLog(array("titulo" => "Log de Erro - Importação de Peças para Loja Peça B2B")); 

    $fabrica    = "";
    $msg_erro   = "";
    $loja       = "";
    $log_erro   = array();
    $pathCompleto = "";

    if ($argv[1]) {
        $fabrica = $argv[1];
    }

    if (empty($fabrica)) {
        $msg_erro .= "Fabrica não informada\n";
    }

    $sqlB2B = "SELECT loja_b2b
                 FROM tbl_loja_b2b 
                WHERE fabrica= {$fabrica}";
    $resB2B  = pg_query($con, $sqlB2B);
    if (pg_num_rows($resB2B) == 0) {
        $msg_erro .= "Loja não encontrada\n";
    } else {
        $row = pg_fetch_assoc($resB2B);
        $loja = $row["loja_b2b"];
    }

    if (empty($msg_erro)) {

        $sqlPecas = "SELECT *
                       FROM tbl_peca
                      WHERE fabrica = {$fabrica} 
                        AND ativo IS TRUE";
        $resPecas = pg_query($con, $sqlPecas);

        if (pg_num_rows($resPecas) > 0) {

            $dadosPecas = pg_fetch_all($resPecas);

            foreach ($dadosPecas as $key => $rows) {

                $sqlLojaPeca = "SELECT peca 
                                 FROM tbl_loja_b2b_peca 
                                WHERE tbl_loja_b2b_peca.loja_b2b = {$loja} 
                                  AND tbl_loja_b2b_peca.peca = ".$rows["peca"];
                $resLojaPeca = pg_query($con, $sqlLojaPeca);
                if (strlen(pg_last_error($con)) > 0) {
                    $log_erro["erro_consulta_loja_peca"][] = "Erro ao busca na loja peça <br> <b>SQL: </b> {$sqlLojaPeca} <br> <b>Erro: </b> ".pg_last_error($con);
                }
                if (pg_num_rows($resLojaPeca) > 0) {
                    continue;
                }

                //INSERE PEÇA NA LOJA PEÇA
                $sqlClienteInsert = "INSERT INTO tbl_loja_b2b_peca (
                                                                        loja_b2b, 
                                                                        peca,
									descricao,
									qtde_estoque,
									qtde_max_posto,
									preco_promocional,
									categoria,
									preco
                                                                    ) VALUES (
                                                                        {$loja},
                                                                        {$rows["peca"]},
									' ',
									0,
									0,
									0,
									0,
									0
                                                                      )";
                $resClienteInsert = pg_query($con, $sqlClienteInsert);
                if (strlen(pg_last_error($con)) > 0) {
                    $log_erro["erro_insert_loja_peca"][] = "Erro ao inserir peça  na loja peça <br> <b>SQL: </b> {$sqlClienteInsert} <br> <b>Erro: </b> ".pg_last_error($con);
                }
            }
        }

    } else {
        exit($msg_erro);
    }

    if (!empty($log_erro)) {

        if (isset($log_erro["erro_consulta_loja_peca"])) {
            $conteudo_log_erro .= "<tr><td style='background:#d90000;color:#ffffff;padding:5px;'>Erros de consulta de loja peça</td></tr>\n";
            foreach ($log_erro["erro_consulta_loja_peca"] as $key => $erro) {
                $conteudo_log_erro .= "<tr><td style='padding:5px;'>".$erro."</td></tr>\n";
            }
        }

        if (isset($log_erro["erro_insert_loja_peca"])) {
            $conteudo_log_erro .= "<tr><td style='background:#d90000;color:#ffffff;padding:5px;'>Erros de Insert na vinculação da peça na loja peça</td></tr>\n";
            foreach ($log_erro["erro_insert_loja_peca"] as $key => $erro) {
                $conteudo_log_erro .= "<tr><td style='padding:5px;'>".$erro."</td></tr>\n";
            }
        }

        $corpo = "<table width='100%' border='1' cellpadding='0' cellspacing='0' width='100%' style='border: solid 1px #d90000;'>
                        <tr>
                            <th style='background:#d90000;color:#ffffff;padding:5px;'>Descrição</th>
                        </tr>
                        ".$conteudo_log_erro."
                  </table>
                ";
        $logClass->adicionaLog($corpo);

        if ($logClass->enviaEmails() == "200") {
            echo "Log de Erro enviado com Sucesso!";
        }

    }

