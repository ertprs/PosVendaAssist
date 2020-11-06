<?php
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 190;
    $fabrica_nome = "nilfisk";
    $data = date('d-m-Y');

    #$env = "producao";
    $env = "teste";

    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de OS's Preventivas Nilfisk")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
    }

    $log_success = array();
    $msg_erro = array();

    pg_query($con, 'BEGIN');
    $sqlContrato = "SELECT CT.*,
                           CI.horimetro,
                           CI.serie,
                           CI.preventiva,
                           CI.preco,
                           CI.produto,
                           PR.referencia AS produto_referencia,
                           PR.descricao AS produto_descricao,
                           CA.nome AS cliente_nome,
                           CA.cnpj AS cliente_cnpj,
                           CA.endereco AS cliente_endereco,
                           CA.numero AS cliente_numero,
                           CA.complemento AS cliente_complemento,
                           CA.bairro AS cliente_bairro,
                           CA.cep AS cliente_cep,
                           CA.cidade AS cliente_cidade,
                           CA.estado AS cliente_estado,
                           CA.email AS cliente_email,
                           CA.fone AS cliente_fone,
                           CA.celular AS cliente_celular,
                           CA.ie AS cliente_ie
                     FROM tbl_contrato CT
                     JOIN tbl_cliente_admin CA ON CA.cliente_admin = CT.cliente  
                     JOIN tbl_contrato_status_movimento  CSM ON CSM.contrato = CT.contrato 
                     JOIN tbl_contrato_item CI ON CI.contrato = CT.contrato  AND CI.preventiva = 't'
                     JOIN tbl_produto PR ON  PR.produto = CI.produto AND PR.fabrica_i={$fabrica}
                    WHERE CT.fabrica={$fabrica}  
                      AND CT.qtde_preventiva > 0 
                      AND CSM.contrato_status = (
                                                    SELECT tbl_contrato_status.contrato_status
                                                      FROM tbl_contrato_status_movimento 
                                                      JOIN tbl_contrato_status ON  tbl_contrato_status.contrato_status = tbl_contrato_status_movimento.contrato_status
                                                     WHERE tbl_contrato_status_movimento.contrato = CT.contrato 
                                                       AND tbl_contrato_status.descricao = 'Ativo' 
                                                  ORDER BY tbl_contrato_status_movimento.data DESC LIMIT 1
                                                )
                    ";
    $resContrato = pg_query($con, $sqlContrato);

    if (pg_num_rows($resContrato) > 0) {

        $sqlTA = "SELECT tipo_atendimento 
                    FROM tbl_tipo_atendimento 
                   WHERE fabrica = {$fabrica} 
                     AND codigo='150'";
        $resTA = pg_query($con, $sqlTA);
        $tipo_atendimento = pg_fetch_result($resTA, 0, 'tipo_atendimento');

        foreach (pg_fetch_all($resContrato) as $key => $linha) {

            //verificar se ja nao tem a os aberta
            //verificar se o produto no contrato é preventivo (verificar com luizinho)
            //colocar a regra no lancamento de peça de tipo garantia, qdo consumiveis estiver marcado, 
            //nao pode ser lancada
            //echo "<pre>".print_r(pg_fetch_all($resContrato),1)."</pre>";exit; 
            $sqlOS = "SELECT tbl_os.os
                        FROM tbl_os 
                       JOIN tbl_contrato_os ON tbl_contrato_os.os = tbl_os.os 
                       WHERE tbl_os.fabrica = {$fabrica} 
                         AND tbl_os.tipo_atendimento=".$tipo_atendimento." 
                         AND tbl_os.posto=".$linha['posto']." 
                         AND tbl_os.consumidor_cpf='".$linha['cliente_cnpj']."' 
                         AND tbl_os.produto=".$linha['produto']." 
                         AND tbl_os.cliente_admin=".$linha['cliente']." 
                         AND tbl_os.data_digitacao BETWEEN '".date("Y-m-01")."' AND '".date("Y-m-t")."'";
            $resOS = pg_query($con, $sqlOS);

            if (pg_num_rows($resOS) > 0) {
                continue;
            } else {

                $sql = "INSERT INTO tbl_os (
                                                fabrica,
                                                validada,
                                                posto,
                                                data_abertura,
                                                consumidor_nome,
                                                consumidor_cidade,
                                                consumidor_estado,
                                                consumidor_fone,
                                                consumidor_cpf,
                                                consumidor_endereco,
                                                consumidor_numero,
                                                consumidor_cep,
                                                consumidor_complemento,
                                                consumidor_bairro,
                                                consumidor_email,
                                                consumidor_celular,
                                                consumidor_fone_comercial,
                                                consumidor_revenda,
                                                /*revenda,
                                                revenda_cnpj,
                                                revenda_nome,
                                                revenda_fone,*/
                                                produto,
                                                serie,
                                                cliente_admin,
                                                tipo_atendimento
                                            ) VALUES (
                                                ".$fabrica.",
                                                'now()',
                                                ".$linha['posto'].",
                                                '".date('Y-m-d')."',
                                                '".$linha['cliente_nome']."',
                                                '".$linha['cliente_cidade']."',
                                                '".$linha['cliente_estado']."',
                                                '".$linha['cliente_fone']."',
                                                '".$linha['cliente_cnpj']."',
                                                '".$linha['cliente_endereco']."',
                                                '".$linha['cliente_numero']."',
                                                '".$linha['cliente_cep']."',
                                                '".$linha['cliente_complemento']."',
                                                '".$linha['cliente_bairro']."',
                                                '".$linha['cliente_email']."',
                                                '".$linha['cliente_celular']."',
                                                '".$linha['cliente_fone']."',
                                                'C',
                                                '".$linha['produto']."',
                                                '".$linha['serie']."',
                                                '".$linha['cliente']."',
                                                ".$tipo_atendimento."
                                            ) RETURNING os; ";
                $res = pg_query($con, $sql);
                $os_aberta = pg_fetch_result($res, 0, 'os');


                            
                if (pg_last_error()) {
                    $msg_erro[$i]["log_erro"]["msg"][]           = "Erro ao abrir Ordem de Serviço Preventiva";
                    $msg_erro[$i]["log_erro"]["cliente_admin"][] = $linha['cliente'];
                    $msg_erro[$i]["log_erro"]["produto"][]       = $linha['produto'];
                    $msg_erro[$i]["log_erro"]["contrato"][]      = $linha['contrato'];
                    $msg_erro[$i]["log_erro"]["pg_last_error"][] = pg_last_error();
                } else {


                    if (strlen($os_aberta) > 0) {

                        $sqlOsExtra     = "INSERT INTO tbl_os_extra(os,representante) VALUES ($os_aberta,".$linha['representante'].")";
                        $resOsExtra     = pg_query($con, $sqlOsExtra);

                        $sqlOsProduto   = "INSERT INTO tbl_os_produto (os, produto, serie) VALUES (".$os_aberta.", ".$linha['produto'].", '".$linha['serie']."')";
                        $resOsProduto   = pg_query($con, $sqlOsProduto);

                        $sqlOsContrato  = "INSERT INTO tbl_contrato_os (os, contrato) VALUES (".$os_aberta.", ".$linha['contrato'].")";
                        $resOsContrato  = pg_query($con, $sqlOsContrato);

                        $updateStatus   = "UPDATE tbl_os set status_checkpoint = 0 WHERE os = {$os_aberta} AND fabrica = {$fabrica}";
                        $resUpStatus    = pg_query($con, $updateStatus);

                        $sqlSuaOs       = "UPDATE tbl_os SET sua_os = {$os_aberta} WHERE os = {$os_aberta} AND fabrica = {$fabrica}";
                        $resSuaOs       = pg_query($con, $sqlSuaOs);

                        $data_agendamento = date('Y-m-d', strtotime(date("Y-m-d"). ' + 2 days'));;

                        $countAgenda      = "SELECT COUNT(*) FROM tbl_tecnico_agenda WHERE fabrica = {$fabrica} AND os = {$os_aberta};";
                        $resCountAgenda   = pg_query($con,$countAgenda);

                        $ordem = pg_fetch_result($resCountAgenda, 0, 0);
                        $ordem += 1;

                        $sqlAgenda = "INSERT INTO tbl_tecnico_agenda (
                                                            fabrica,
                                                            os,
                                                            data_agendamento,
                                                            ordem,
                                                            periodo
                                                        ) VALUES (
                                                            {$fabrica},
                                                            {$os_aberta},
                                                            '{$data_agendamento}',
                                                            $ordem, 
                                                            'manha'
                                                        );";

                        $resAgenda = pg_query($con,$sqlAgenda);

                        if (pg_last_error()) {
                            $msg_erro[$i]["log_erro"]["msg"][]           = "Erro ao abrir Ordem de Serviço Preventiva";
                            $msg_erro[$i]["log_erro"]["cliente_admin"][] = $linha['cliente'];
                            $msg_erro[$i]["log_erro"]["produto"][]       = $linha['produto'];
                            $msg_erro[$i]["log_erro"]["contrato"][]      = $linha['contrato'];
                            $msg_erro[$i]["log_erro"]["os"][]            = $os_aberta;
                            $msg_erro[$i]["log_erro"]["pg_last_error"][] = pg_last_error();
                        }
                       
                    }
                    if (count($msg_erro) == 0) {
                        $msg_success[$i]["log_success"]["msg"][]        = "O.S. Preventiva aberta com sucesso";
                        $msg_success[$i]["log_success"]["os"][]         = $os_aberta;
                        $msg_success[$i]["log_success"]["contrato"][]   = $linha['contrato'];
                    }
                }

            }
        }

    }
    if(!empty($msg_erro)){
        pg_query($con, 'ROLLBACK');
    } else {
        pg_query($con, 'COMMIT');
    }

    if(!empty($msg_erro)){

        $tabela = "
                    <table>
                        <thead>
                            <tr>
                                <th style='padding:5px;background:#d90000;color:#fff;'>Descrição</th>
                                <th style='padding:5px;background:#d90000;color:#fff;'>Erro Sql</th>
                                <th style='padding:5px;background:#d90000;color:#fff;'>Obs</th>
                            </tr>
                        </thead>
                        <tbody>";

        for ($i=0; $i < count($msg_erro["log_erro"]["msg"]); $i++) { 

            $tabela .= "
                    <tr>
                        <td>".$msg_erro["log_erro"]["msg"][$i]."</td>
                        <td>".$msg_erro["log_erro"]["pg_last_error"][$i]."</td>
                        <td>
                            Contrato: ".$msg_erro["log_erro"]["contrato"][$i]."<br>
                            O.S.: ".$msg_erro["log_erro"]["os"][$i]."<br>
                            Cliente: ".$msg_erro["log_erro"]["cliente_admin"][$i]."<br>
                            Produto: ".$msg_erro["log_erro"]["produto"][$i]."
                        </td>
                    </tr>";
        }

        $tabela .= "</tbody>
                </table>";

        $logClass->adicionaLog($tabela);
        $logClass->enviaEmails();

        $fp = fopen("tmp/{$fabrica_nome}/os_preventiva/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

