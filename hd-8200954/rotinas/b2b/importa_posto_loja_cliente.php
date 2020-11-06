<?php 
    #include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    $logClass = new Log2();
    $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
    $logClass->adicionaLog(array("titulo" => "Log de Erro - Importação de Postos para Cliente B2B")); 

    $fabrica    = "";
    $msg_erro   = "";
    $loja       = "";
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

    $log_erro   = array();
    $log_insert = array();

    if (empty($msg_erro)) {

        $sqlPosto = "SELECT posto
                       FROM tbl_posto_fabrica 
                      WHERE fabrica= {$fabrica} AND credenciamento='CREDENCIADO'";
        $resPosto  = pg_query($con, $sqlPosto);

        if (pg_num_rows($resPosto) > 0) {

            $dadosPostos = pg_fetch_all($resPosto);

            foreach ($dadosPostos as $key => $rows) {

                $sqlCliente = "SELECT * 
                                 FROM tbl_loja_b2b_cliente 
                                WHERE tbl_loja_b2b_cliente.loja_b2b = {$loja} 
                                  AND tbl_loja_b2b_cliente.posto = ".$rows["posto"];
                $resCliente = pg_query($con, $sqlCliente);

                if (strlen(pg_last_error($con)) > 0) {
                    $log_erro["erro_consulta"][] = "Erro ao buscar cliente <br> <b>SQL: </b> {$sqlCliente} <br> <b>Erro: </b> ".pg_last_error($con);
                }

                if (pg_num_rows($resCliente) == 0) {
                    //INSERT CLIENTE
                    $sqlClienteInsert = "INSERT INTO tbl_loja_b2b_cliente (
                                                                              loja_b2b, 
                                                                              posto,
                                                                              cidade
                                                                          ) VALUES (
                                                                              {$loja},
                                                                              {$rows["posto"]},
                                                                              0
                                                                          )";
                    $resClienteInsert = pg_query($con, $sqlClienteInsert);
                    if (strlen(pg_last_error($con)) > 0) {
                        $log_erro["erro_insert_cliente"][] = "Erro ao inserir cliente na loja <br> <b>SQL: </b> {$sqlClienteInsert} <br> <b>Erro: </b> ".pg_last_error($con);
                    }
                }
            }
        }

    } else {
        exit($msg_erro);
    }

    if (!empty($log_erro)) {

        if (isset($log_erro["erro_consulta"])) {
            foreach ($log_erro["erro_consulta"] as $key => $erro) {
                $conteudo_log_erro .= "<tr><td style='padding:5px;'>".$erro."</td></tr>\n";
            }
        }

        if (isset($log_erro["erro_insert_cliente"])) {
            foreach ($log_erro["erro_insert_cliente"] as $key => $erro) {
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
