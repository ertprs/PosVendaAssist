<?php
/**
 *
 * gera-pedido.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  William Ap. Brandino
 * @version 2014.04.29
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
    include dirname(__FILE__) . '/../../class/communicator.class.php';

    $data['login_fabrica']  = 42;
    $data['fabrica_nome']   = 'makita';
    $data['arquivo_log']    = 'gera-pedido-os';
    $data['log']            = 2;
    $data['arquivos']       = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs                   = array();
    $logs_erro              = array();
    $logs_cliente           = array();
    $erro                   = false;

    $login_fabrica = 42;
    $phpCron = new PHPCron($login_fabrica, __FILE__);
    $phpCron->inicio();

    if (ENV == 'producao' ) {
        $data['dest']       = 'helpdesk@telecontrol.com.br';
    } else {
        $data['dest']           = 'ronald.santos@telecontrol.com.br';
    }

    extract($data);

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.txt";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    $sql = "SET DateStyle TO 'SQL,EUROPEAN';";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
        $logs_erro[] = $sql;
        $logs[] = pg_last_error($con);
        $erro   = true;
    }

    $sql = "SELECT to_char(current_date, 'd')::integer;";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'dia da semana'";
        $logs_erro[] = $sql;
        $logs[] = pg_last_error($con);
        $erro   = true;
        throw new Exception ($msg_erro);
    }else{
        $dia_semana = pg_fetch_result($res, 0);
    }

    $sql = "SELECT  tbl_os.posto        ,
                    tbl_os.filial       ,
                    tbl_produto.linha   ,
                    tbl_os_item.peca    ,
                    tbl_os_item.os_item ,
                    tbl_os_item.qtde    ,
                    tbl_os_item.preco   ,
                    tbl_os.sua_os       ,
                    tbl_auditoria_os.auditoria_os,
                    tbl_auditoria_os.liberada
       INTO TEMP    tmp_pedido_makita
            FROM    tbl_os_item
            JOIN    tbl_servico_realizado   USING (servico_realizado)
            JOIN    tbl_os_produto          USING (os_produto)
            JOIN    tbl_os                  USING (os)
            JOIN    tbl_posto               USING (posto)
            JOIN    tbl_produto             ON  tbl_os.produto                      = tbl_produto.produto
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto             = tbl_os.posto
                                            AND tbl_posto_fabrica.fabrica           = tbl_os.fabrica
       LEFT JOIN    tbl_auditoria_os        ON  tbl_auditoria_os.os                 = tbl_os.os
       LEFT JOIN    tbl_os_status           ON  tbl_os.os                           = tbl_os_status.os
                                            AND tbl_os_status.os_status             = (
                                                SELECT  MAX(os_status)
                                                FROM    tbl_os_status
                                                WHERE   tbl_os_status.os        = tbl_os.os
                                                AND     tbl_os_status.status_os IN (19,62,64,67,102,103,118,148,149,150,151,163,161,162,164,165,166,167)
                                            )
            WHERE   tbl_os_item.pedido      IS NULL
            AND     tbl_os.validada         IS NOT NULL
            AND     tbl_os.filial           IS NOT NULL
            AND     tbl_os.excluida         IS NOT TRUE
			and		tbl_os.cancelada is not true
            AND     tbl_os.fabrica          = $login_fabrica
            AND     (
                        tbl_auditoria_os.auditoria_os IS NULL OR
                        (
                            tbl_auditoria_os.auditoria_os IS NOT NULL AND tbl_auditoria_os.liberada IS NOT NULL
                        )
                    )
            AND     (
                        tbl_posto_fabrica.credenciamento    = 'CREDENCIADO'
                    OR  tbl_posto_fabrica.credenciamento    = 'EM DESCREDENCIAMENTO'
                    )
            AND     tbl_servico_realizado.gera_pedido       IS TRUE
            AND     tbl_servico_realizado.troca_de_peca     IS TRUE
            AND     (
                        tbl_os_status.status_os IN(19,64,103,149,151,166,118)
                    OR  tbl_os_status.status_os IS NULL
                    )
            AND     (
                        (
                            tbl_auditoria_os.bloqueio_pedido    IS NOT TRUE
                        OR  tbl_auditoria_os.reprovada          IS NULL
                        OR  tbl_auditoria_os.liberada           IS NOT NULL
                        )
                    OR  tbl_auditoria_os.os IS NULL
                    );

                SELECT DISTINCT posto,linha,filial from tmp_pedido_makita ;

                ";
    $resP = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Posto'";
        $logs_erro[] = $sql;
        $logs[] = pg_last_error($con);
        $erro   = true;
        throw new Exception ($msg_erro);
    }

    #Garantia
    $sql = "select condicao from tbl_condicao where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
    $resultG = pg_query($con, $sql);
    if(pg_last_error($con)){
        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Condição Pagamento'";
        $logs_erro[] = $sql;
        $logs[] = pg_last_error($con);
        $erro = "*";
    }else{
        $condicao = pg_result($resultG,0,'condicao');
    }

    #Tipo_pedido
    $sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
    $resultP = pg_query($con, $sql);
    if(pg_last_error($con)){
        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Tipo Pedido'";
        $logs_erro[] = $sql;
        $logs[] = pg_last_error($con);
        $erro = "*";
    }else{
        $tipo_pedido = pg_result($resultP,0,'tipo_pedido');
    }

    $array_pecas_monitoradas = [];

    for ($i=0; $i < pg_num_rows($resP); $i++) {

        $posto  = pg_result($resP,$i,'posto');
        $linha  = pg_result($resP,$i,'linha');
        $filial = pg_result($resP,$i,'filial');

        $erro = " ";
        $res = pg_query($con, "BEGIN TRANSACTION");

        #Tabela
        $sql = "select tabela from tbl_tabela where fabrica = $login_fabrica;";

        $resultP = pg_query($con, $sql);
        if(pg_num_rows($resultP) > 0) {
            if(pg_last_error($con)){
                $logs_erro[] = $sql."<br>".pg_last_error($con);
                $erro = '*';
            }else{
                $tabela = pg_result($resultP,0,'tabela');
            }
        }else{
            $logs_erro[] = 'Posto sem tabela cadastrada';
            $erro = '*';
        }


        $sql = "INSERT INTO tbl_pedido (
                    posto        ,
                    filial_posto ,
                    fabrica      ,
                    condicao     ,
                    tipo_pedido  ,
                    status_pedido,
                    tabela
                ) VALUES (
                    $posto      ,
                    $filial     ,
                    $login_fabrica    ,
                    $condicao   ,
                    $tipo_pedido,
                    1           ,
                    $tabela
                ) RETURNING pedido;";
        $resultP = pg_query($con, $sql);
        if(pg_last_error($con)){
            $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido'";
            $logs_erro[] = $sql;
            $logs[] = pg_last_error($con);
            $erro = "*";
        }else{

            $pedido = pg_result($resultP,0,0);

            $sql_item = "SELECT distinct
                        peca    ,
                        qtde    ,
                        preco   ,
                        os_item
                            from
                        tmp_pedido_makita
                        WHERE posto = $posto
						AND linha   = $linha
						and filial = $filial";

            $result2 = pg_query($con,$sql_item);

            for ($x=0; $x < pg_num_rows($result2); $x++) {
                $peca = pg_result($result2,$x,'peca');
                $qtde = pg_result($result2,$x,'qtde');
                $preco = pg_result($result2,$x,'preco');
				$preco = empty($preco) ? 0 : $preco;
                $os_item = pg_result($result2,$x,'os_item');

                $sql = "INSERT INTO tbl_pedido_item (
                        pedido,
                        peca  ,
                        qtde  ,
                        preco ,
                        qtde_faturada,
                        qtde_cancelada
                    ) VALUES (
                        $pedido,
                        $peca  ,
                        $qtde  ,
                        $preco ,
                        0      ,
                        0      ) RETURNING pedido_item";
                $resultX = pg_query($con,$sql);
                if(pg_last_error($con)){
                    $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido Item'";
                    $logs_erro[] = $sql;
                    $logs[] = pg_last_error($con);
                    $erro = "*";
                }else{
                    $pedido_item = pg_result($resultX,0,0);

                    $sql_peca_monitorada = "SELECT JSON_FIELD('peca_monitorada', parametros_adicionais) AS peca_monitorada, 
                                                   JSON_FIELD('email_peca_monitorada', parametros_adicionais) AS email_peca_monitorada,
                                                   referencia,
                                                   descricao
                                            FROM tbl_peca
                                            WHERE peca = $peca
                                            AND fabrica = $login_fabrica";
                    $res_peca_monitorada = pg_query($con, $sql_peca_monitorada);
                    if (pg_fetch_result($res_peca_monitorada, 0, 'peca_monitorada') == "t") {
                        $email_peca_monitorada = trim(pg_fetch_result($res_peca_monitorada, 0, 'email_peca_monitorada'));
                        $referencia_monitorada = pg_fetch_result($res_peca_monitorada, 0, 'referencia');
                        $descricao_monitorada  = pg_fetch_result($res_peca_monitorada, 0, 'descricao');

                        $sql_nome_posto = " SELECT tbl_posto.nome, 
                                                   tbl_posto.cnpj 
                                            FROM tbl_posto 
                                            JOIN tbl_posto_fabrica USING(posto) 
                                            WHERE tbl_posto.posto = $posto 
                                            AND tbl_posto_fabrica.fabrica = $login_fabrica";
                        $res_nome_posto = pg_query($con, $sql_nome_posto);
                        $nome_posto = pg_fetch_result($res_nome_posto, 0, 'nome');
                        $cnpj_posto = pg_fetch_result($res_nome_posto, 0, 'cnpj');

                        $array_pecas_monitoradas["email"][$email_peca_monitorada][] = array("pedido" => $pedido, "posto" => $nome_posto, "cnpj" => $cnpj_posto, "referencia" => $referencia_monitorada, "descricao" => $descricao_monitorada, "qtde" => $qtde);
                    }

                    $sql = "SELECT fn_atualiza_os_item_pedido_item($os_item,$pedido,$pedido_item,$login_fabrica)";
                    $resultX = pg_query($con,$sql);
                    if(pg_last_error($con)){
                        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Atualiza OS Item'";
                        $logs_erro[] = $sql;
                        $logs[] = pg_last_error($con);
                        $erro = "*";
                    }
                }
            }

            $sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
            $resultX = pg_query($con,$sql);
            if(pg_last_error($con)){
                $erro_cliente = pg_last_error($con);
                $erro_cliente = preg_replace('/ERROR: /','',$erro_cliente);
                $erro_cliente = preg_replace('/CONTEXT:  .+\nPL.+/','',$erro_cliente);

                $logs[] = $erro_cliente;
                $logs_erro[] = $erro_cliente;
                $logs[] = pg_last_error($con);
                $erro = "*";
            }

            if ($erro == "*") {
                $resultX = pg_query($con,"ROLLBACK TRANSACTION");
                print_r($logs);
            }else{
                $sql_posto = "
                  SELECT
                      tbl_posto.nome,
                      tbl_posto_fabrica.codigo_posto
                  FROM tbl_posto
                      JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                  WHERE
                      tbl_posto.posto = {$posto}
                  LIMIT 1";
                $res_posto = pg_query($con, $sql_posto);
                $codigo_posto = pg_fetch_result($res_posto, 0, 'codigo_posto');
                $nome_posto   = pg_fetch_result($res_posto, 0, 'nome');

                $logs[] = "SUCESSO => Posto: '{$codigo_posto} - {$nome_posto}' - Pedido {$pedido} gerado com sucesso!";

                $resultX = pg_query($con,"COMMIT TRANSACTION");
            }
        }
    }

    if (count($array_pecas_monitoradas) > 0) {

        $msg = []; 
        $msg_pronta = [];

        foreach ($array_pecas_monitoradas as $chave => $value_chave) {
            if ($chave == 'email') {
                foreach ($value_chave as $nome_campo => $value_campo) {
                    foreach ($value_campo as $nomes => $values) {                            
                        $msg[$nome_campo][] = "Pedido: ".$values['pedido'].",<br><br>Posto: ".$values['posto'].", - CNPJ: ".$values['cnpj'].",<br><br>Peça: ".$values['referencia'].", Descrição: ".$values['descricao']." e Quantidade: ".$values['qtde']."<br><br>";
                    }
                }
            }
        }

        foreach ($msg as $email => $vl) {
            $ms = $vl;
            $msg_pronta[$email] = $ms;
        }

        foreach ($msg_pronta as $key => $value) {
            $email = $key;

            $ms = implode('<br>', $value); 
            
            $mailTc = new TcComm("smtp@posvenda");
            $res = $mailTc->sendMail(
                $email,
                utf8_encode('Telecontrol - Peças Monitoradas'),
                utf8_encode($ms),
                'noreply@telecontrol.com.br'
            );
        }
    }

    if(count($logs) > 0){
        $file_log = fopen($arquivo_log,"w+");
            fputs($file_log,implode("\r\n", $logs));
        fclose ($file_log);
    }

    //envia email para HelpDESK
    if($erro){
        if(count($logs_erro) > 0){
            $file_log = fopen($arquivo_err,"w+");
                if(count($logs_erro) > 0){
                    fputs($file_log,implode("\r\n", $logs_erro));
                }
            fclose ($file_log);

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->IsHTML();
        #$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
        $mail->Subject = Date('d/m/Y')." - Erro ao gerar pedido ";
        $mail->Body = "Erro ao gerar pedido";
        $mail->AddAddress("helpdesk@telecontrol.com.br");
        if(file_exists($arquivo_err) AND filesize($arquivo_err) > 0)
        $mail->AddAttachment($arquivo_err);
        $mail->Send();
        }
    }

    $phpCron->termino();

} catch (Exception $e) {
    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Makita - Erro na geração de pedido(gera-pedido.php)", $msg);
}?>
