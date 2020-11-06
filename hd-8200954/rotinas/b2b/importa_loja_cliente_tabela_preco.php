<?php 
    #include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    $logClass = new Log2();
    $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
    $logClass->adicionaLog(array("titulo" => "Log de Erro - Importação de Cliente B2B na Tabela de Preço B2B")); 

    $registro     = array();
    $fabrica      = "";
    $tabelaPreco  = "";
    $pathCompleto = "";
    $msg_erro     = "";
    $loja         = "";
    $log_erro     = array();
    $log_insert   = array();

    if ($argv[1]) {
        $fabrica = $argv[1];
    }

    if ($argv[2) {
        $pathCompleto = $argv[2];
    }    

    if ($argv[3) {
        $tabelaPreco = $argv[3];
    }

    if (empty($pathCompleto) || !file_exists($pathCompleto)) {
        $msg_erro .= "Caminho do arquivo completo  não informado ou arquivo não existe\n";
    }

    if (empty($fabrica)) {
        $msg_erro .= "Fabrica não informada\n";
    }

    if (empty($tabelaPreco)) {
        $msg_erro .= "Tabela de Preço B2B não informada\n";
    }

    //VERIFICA SE EXISTE A LOJA PARA FABRICA INFORMADA
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

    //VERIFICA SE EXISTE TABELA DE PREÇO INFORMADA
    $sqlB2Btabela  = "SELECT *
                        FROM tbl_loja_b2b_tabela 
                       WHERE loja_b2b_tabela = {$tabelaPreco}";
    $resB2Btabela  = pg_query($con, $sqlB2Btabela);

    if (pg_num_rows($resB2Btabela) == 0) {
        $msg_erro .= "Tabela de Preço B2B não cadastrada\n";
    }

    $extensao   = strtolower(preg_replace("/.+\./", "", $pathCompleto));

    if (!in_array(strtolower($extensao), array("csv", "txt"))) {
        $msg_erro .= "Formato de arquivo inválido\n";
    }
    pg_prepare($con, "posto_fabrica", "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $fabrica AND codigo_posto = $1");

    if (strlen($msg_erro) == 0) {
        //LE OS ARQUIVO DE POSTO ENVIADO
        $xarquivo = fopen($pathCompleto, 'r+');
        if ($xarquivo ) {
            $i = 0;

            while(!feof($xarquivo)) {
                $linha = fgets($xarquivo,4096);
                if ($i != 0) {
                    if (strlen(trim($linha)) > 0) {
                        //loja/tabela
                        list($codigo_posto) = explode(";", $linha);
                        $res = pg_execute($con, "posto_fabrica",array($codigo_posto));
                        $registro[] = pg_fetch_result($res, 0, 'posto');
                    }
                }
                $i++;
            }

            fclose($xarquivo);
        }

    } else {
        exit($msg_erro);
    }

    foreach ($registro as $k => $posto) {
        //BUSCA CLIENTE DA LOJA PELO POSTO
        $sqlCliente = "SELECT * 
                         FROM tbl_loja_b2b_cliente
                        WHERE tbl_loja_b2b_cliente.loja_b2b = {$loja} 
                          AND tbl_loja_b2b_cliente.posto = {$posto}";
        $resCliente = pg_query($con, $sqlCliente);
        if (strlen(pg_last_error($con)) > 0) {
            $log_erro["erro_consulta"][] = "Erro ao buscar cliente na loja cliente: <br><b>SQL: </b> ".$sqlCliente." <br><b>SQL: </b> ".pg_last_error($con);
        }

        if (pg_num_rows($resCliente) > 0) {
            //PEGA CLIENTE SE JA CADASTRADO
            $dados = pg_fetch_assoc($resCliente);
            $lojaCliente = $dados["loja_b2b_cliente"];
        } else {
            //INSERE POSTO COMO CLIENTE
            $sqlClienteInsert = "INSERT INTO tbl_loja_b2b_cliente (loja_b2b, posto, cidade) VALUES ({$loja}, {$posto}, 0) RETURNING loja_b2b_cliente";
            $resClienteInsert = pg_query($con, $sqlClienteInsert);
            if (strlen(pg_last_error($con)) > 0) {
                $log_erro["erro_insert_cliente"][] = "Erro ao inserir cliente na loja: <br><b>SQL: </b> ".$sqlClienteInsert."  <br><b>Erro: </b> ".pg_last_error($con);
            } else {
                $lojaCliente = pg_fetch_result($resClienteInsert, 0, 'loja_b2b_cliente');
            }
        }

        //VALIDA SE EXISTE CLIENTE NA TABELA
        $sqlTabelaCliente = "SELECT * 
                               FROM tbl_loja_b2b_tabela_cliente 
                              WHERE loja_b2b_tabela  = {$tabelaPreco} 
                                AND loja_b2b_cliente = {$lojaCliente}";
        $resTabelaCliente = pg_query($con, $sqlTabelaCliente);
        if (strlen(pg_last_error($con)) > 0) {
            $log_erro["erro_consulta_tabela_cliente"][] = "Erro ao busca tabela de cliente na loja:  <br><b>SQL: </b> ".$sqlTabelaCliente."  <br><b>Erro: </b> ".pg_last_error($con);
        }

        if (pg_num_rows($resTabelaCliente) > 0) {
            continue;
        }

        //INSERE CLIENTE NA TABELA PRECO
        $sqlTabelaClienteInsert = "INSERT INTO tbl_loja_b2b_tabela_cliente (loja_b2b_tabela, loja_b2b_cliente) VALUES ({$tabelaPreco},{$lojaCliente})";
        $resTabelaClienteInsert = pg_query($con, $sqlTabelaClienteInsert);
        if (strlen(pg_last_error($con)) > 0) {
            $log_erro["erro_insert_tabela_cliente"][] = "Erro ao inserir cliente na tabela de preço loja:  <br><b>SQL: </b>  ".$sqlTabelaClienteInsert." <br><b>Erro: </b> ".pg_last_error($con);
        }

    }

    if (!empty($log_erro)) {

        if (isset($log_erro["erro_consulta"])) {
            $conteudo_log_erro .= "<tr><td style='background:#d90000;color:#ffffff;padding:5px;'>Erros de consulta</td></tr>\n";
            foreach ($log_erro["erro_consulta"] as $key => $erro) {
                $conteudo_log_erro .= "<tr><td style='padding:5px;'>".$erro."</td></tr>\n";
            }
        }

        if (isset($log_erro["erro_insert_cliente"])) {
            $conteudo_log_erro .= "<tr><td style='background:#d90000;color:#ffffff;padding:5px;'>Erros de Insert na vinculação do posto com cliente</td></tr>\n";
            foreach ($log_erro["erro_insert_cliente"] as $key => $erro) {
                $conteudo_log_erro .= "<tr><td style='padding:5px;'>".$erro."</td></tr>\n";
            }
        }

        if (isset($log_erro["erro_consulta_tabela_cliente"])) {
            $conteudo_log_erro .= "<tr><td style='background:#d90000;color:#ffffff;padding:5px;'>Erros de consulta de cliente</td></tr>\n";
            foreach ($log_erro["erro_consulta_tabela_cliente"] as $key => $erro) {
                $conteudo_log_erro .= "<tr><td style='padding:5px;'>".$erro."</td></tr>\n";
            }
        }

        if (isset($log_erro["erro_insert_tabela_cliente"])) {
            $conteudo_log_erro .= "<tr><td style='background:#d90000;color:#ffffff;padding:5px;'>Erros de Insert na vinculação do cliente na tabela</td></tr>\n";
            foreach ($log_erro["erro_insert_tabela_cliente"] as $key => $erro) {
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