<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $bug         = '';
    $fabrica     = 150;
    $dia_mes     = date('d');
    #$dia_mes     = "27";
    $dia_extrato = date('Y-m-d H:i:s');
    #$dia_extrato = "2014-08-27 23:59:00";

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $vet['fabrica'] = 'inbrasil';
    $vet['tipo']    = 'extrato';

    /* Log */
    $log = new Log2();
    $log->adicionaLog(array("titulo" => "Log erro Geração de Extrato inbrasil")); // Titulo
  
    $log->adicionaEmail("pedro@produtosinbrasil.com.br");
    $log->adicionaEmail("helpdesk@telecontrol.com.br");
     
    $sql9 = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date";
    $res9 = pg_query($con,$sql9);
    $data_15 = pg_fetch_result($res9, 0, 0);

    $sql = "SELECT  tbl_os.posto, COUNT(*) AS qtde, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
            FROM tbl_os
            JOIN tbl_os_extra USING (os)
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto  
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica  
            WHERE tbl_os.fabrica = $fabrica
            AND   tbl_os_extra.extrato IS NULL 
            AND   tbl_os.excluida      IS NOT TRUE
            AND   tbl_os.posto <> 6359
            AND   tbl_os.finalizada    <= '$dia_extrato'
            AND   tbl_os.finalizada::date <= current_date 
            GROUP BY tbl_os.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
            ORDER BY tbl_os.posto ";

    $res      = pg_query($con, $sql);
    $msg_erro = pg_last_error($con);

    if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

        for ($i = 0; $i < pg_num_rows($res); $i++) {

            $msg_erro     = "";
            $posto        = pg_result($res, $i, 'posto');
            $nome         = pg_result($res, $i, 'nome');
            $codigo_posto = pg_result($res, $i, 'codigo_posto');
            $qtde         = pg_result($res, $i, 'qtde');
            
            $resP         = pg_query($con,"BEGIN TRANSACTION");

            $sql2 = "INSERT INTO tbl_extrato (fabrica, posto, data_geracao,mao_de_obra, pecas, total,avulso) VALUES ($fabrica, $posto,'$dia_extrato', 0, 0, 0, 0);";
            $res2 = pg_query($con, $sql2);
            if(strlen(pg_last_error($con) > 0)){
                $log->adicionaLog("Erro ao gravar extrato para o posto : {$codigo_posto} - {$nome}");
                $log->adicionaLog("linha");
                $msg_erro .= pg_last_error($con);
            }else{
                $sql3      = "SELECT CURRVAL ('seq_extrato');";
                $res3      = pg_query($con, $sql3);
                $extrato   = pg_result($res3, 0, 0);
                $msg_erro .= pg_last_error($con);
            }

            $sql4 = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
                WHERE tbl_extrato_lancamento.fabrica = $fabrica
                AND   tbl_extrato_lancamento.extrato IS NULL
                AND   tbl_extrato_lancamento.posto = $posto; ";
            $res4 = pg_query($con, $sql4);
            if(strlen(pg_last_error($con) > 0)){
                $log->adicionaLog("Erro ao gravar os lançamentos avulsos para o posto : {$codigo_posto} - {$nome}");
                $log->adicionaLog("linha");
                $msg_erro .= pg_last_error($con);
            }

            $sql4 = "UPDATE tbl_os_extra SET extrato = $extrato
                        FROM  tbl_os
                        WHERE tbl_os.posto   = $posto
                        AND   tbl_os.fabrica = $fabrica
                        AND   tbl_os.os      = tbl_os_extra.os
                        AND   tbl_os_extra.extrato IS NULL
                        AND   tbl_os.excluida      IS NOT TRUE
                        AND   tbl_os.finalizada    <= '$dia_extrato'";
            $res4      = pg_query($con, $sql4);
            if(strlen(pg_last_error($con) > 0)){
                $log->adicionaLog("Erro ao relacionar as OS com o extrato para o posto : {$codigo_posto} - {$nome}");
                $log->adicionaLog("linha");
                $msg_erro .= pg_last_error($con);
            }

            $sql5 = "UPDATE tbl_extrato
                    SET avulso = (
                        SELECT SUM (valor)
                        FROM tbl_extrato_lancamento
                        WHERE tbl_extrato_lancamento.extrato = tbl_extrato.extrato
                    )
                WHERE tbl_extrato.fabrica = $fabrica
                AND tbl_extrato.data_geracao > CURRENT_DATE";
            $res5      = pg_query($con, $sql5);
            if(strlen(pg_last_error($con) > 0)){
                $log->adicionaLog("Erro ao atualizar os valores dos lançamentos avulsos para o posto : {$codigo_posto} - {$nome}");
                $log->adicionaLog("linha");
                $msg_erro .= pg_last_error($con);
            }

            $sql6 = " SELECT
                            SUM(tbl_os.mao_de_obra) as total_mo,
                            SUM(tbl_os.qtde_km_calculada) as total_km,
                            SUM(tbl_os.pecas) as total_pecas,
                            SUM(tbl_os.valores_adicionais) as total_adicionais,
                    tbl_extrato.avulso
                        FROM tbl_os
                        INNER JOIN tbl_os_extra USING(os)
                    INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                        WHERE tbl_os_extra.extrato = {$extrato}
                    GROUP BY tbl_extrato.avulso
                    ";

            $res6  = pg_query($con, $sql6);

            if(strlen(pg_last_error($con) > 0)) {
                $log->adicionaLog("Erro ao atualizar os valores do extrato para o posto : {$codigo_posto} - {$nome}");
                $log->adicionaLog("linha");
                $msg_erro .= pg_last_error($con);
            }
            $rest    = pg_fetch_all($res6);

            if(count($rest) > 0){
                $rest             = $rest[0];

                $total_mo         = (!empty($rest['total_mo']))         ? $rest['total_mo']         : 0;
                $total_km         = (!empty($rest['total_km']))         ? $rest['total_km']         : 0;
                $total_pecas      = ($rest['total_pecas'] != "0")       ? $rest['total_pecas']      : 0;
                $total_adicionais = (!empty($rest['total_adicionais'])) ? $rest['total_adicionais'] : 0;
                $avulso           = (strlen($rest['avulso']) > 0)       ? $rest['avulso'] : 0;

                $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

                $sql7 = " UPDATE
                        tbl_extrato
                    SET
                        total           = {$total},
                        mao_de_obra     = {$total_mo},
                        pecas           = {$total_pecas},
                        deslocamento    = {$total_km},
                        valor_adicional = {$total_adicionais}
                    WHERE
                        extrato = {$extrato}
                ";
                $res7 = pg_query($con,$sql7);
                if(strlen(pg_last_error($con) > 0)){
                    $log->adicionaLog("Erro ao atualizar os valores do extrato para o posto : {$codigo_posto} - {$nome}");
                    $log->adicionaLog("linha");
                    $msg_erro .= pg_last_error($con);
                }
            }

            if (strlen($msg_erro) > 0) {

                $resP = pg_query('ROLLBACK;');
                $bug .= $msg_erro;

                Log::log2($vet, $msg_erro);

            } else {

                $resP = pg_query('COMMIT;');

            }

        }

    }else{
        if(strlen($msg_erro) > 0){
            $log->adicionaLog("Erro ao selecionar Postos para gerar Extrato");
            $log->adicionaLog("linha");
        }
    }

    if (strlen($bug) > 0) {

         //envia email para HelpDESK
        if(!empty($erro)){
            if($log->enviaEmails() == "200"){
              echo "Log de erro enviado com Sucesso!";
            }else{
              echo $log->enviaEmails();
            }
        }    
    }

    $phpCron->termino();

} catch (Exception $e) {

    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    $log->adicionaLog($msg);

    if($log->enviaEmails() == "200"){
      echo "Log de erro enviado com Sucesso!";
    }else{
      echo $log->enviaEmails();
    }

}

