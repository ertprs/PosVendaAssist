<?php
/**
 *
 * exclui-os.php
 *
 * Exclusão de OS com auditoria de Troca de Produto ou Processo Judicial que foram reprovadas e não regularizadas num prazo de 30 dias
 *
 * @author  Ronald Santos
 * @version 2012.11.21
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica']  = 30;
    $data['fabrica_nome']   = 'esmaltec';
    $data['arquivo_log']  = 'os-excluida';
    $data['log']      = 2;
    $data['arquivos']     = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs           = array();
    $logs_erro        = array();
    $logs_cliente     = array();
    $erro           = false;

	$fabrica = 30;
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

    if (ENV == 'producao' ) {
        $data['dest']     = 'helpdesk@telecontrol.com.br';
    } else {
        $data['dest']       = 'ronald.santos@telecontrol.com.br';
    }

    extract($data);

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    $sql = "SELECT interv.os
                FROM (
                    SELECT ultima.os,
                        (
                            SELECT status_os
                            FROM tbl_os_status
                            WHERE status_os IN (164,165,166)
                            AND tbl_os_status.os = ultima.os AND tbl_os_status.fabrica_status = $login_fabrica
                            ORDER BY data DESC LIMIT 1
                        ) AS ultimo_status
                    FROM (
                        SELECT DISTINCT tbl_os_status.os
                        FROM tbl_os_status
                        JOIN tbl_os ON tbl_os_status.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
                        WHERE status_os IN (164,165,166)
                        AND tbl_os_status.fabrica_status = $login_fabrica
                        AND tbl_os_status.data  < current_date - INTERVAL '30 DAYS'
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_os.defeito_constatado IN(12485,11792)
                    ) ultima
                ) interv
                WHERE interv.ultimo_status IN (165)";
    $res = pg_query($con,$sql);
    $total = pg_num_rows($res);
    if($total > 0){
        for($i = 0; $i < $total; $i++){
            $os = pg_fetch_result($res, $i, 'os');

            $sql = "INSERT INTO tbl_os_status (
                        os,
                        status_os,
                        data,
                        observacao,
                        automatico
                    ) VALUES (
                        $os,
                        15,
                        current_timestamp,
                        'OS CANCELADA pelo fabricante  OS CANCELADA Automaticamente(sem defeito constatado e solução cadastrado)',
                        true
                    );";
		    $res1 = pg_query($con,$sql);

            $sql = "INSERT INTO tbl_os_interacao
                    (programa,fabrica, os, comentario, interno, exigir_resposta)
                    VALUES
                    ('Rotina exclui_os',$login_fabrica, $os, 'OS CANCELADA pelo fabricante  OS CANCELADA Automaticamente(sem defeito constatado e solução cadastrado)', TRUE, FALSE)";
            $res = pg_query($con,$sql);


	        if(pg_last_error($con)){
                $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'INSERT status 15'";
                $logs_erro[] = $sql;
                $logs[] = pg_last_error($con);
                $erro = "*";
		    }else{

                #Comentada no HD-3046644
    		        // $sql = "INSERT INTO tbl_os_excluida (
    				 //  fabrica           ,
    				 //  os                ,
    				 //  sua_os            ,
    				 //  posto             ,
    				 //  codigo_posto      ,
    				 //  produto           ,
    				 //  referencia_produto,
    				 //  data_digitacao    ,
    				 //  data_abertura     ,
    				 //  data_fechamento   ,
    				 //  serie             ,
    				 //  nota_fiscal       ,
    				 //  data_nf           ,
    				 //  consumidor_nome   ,
    				 //  consumidor_endereco,
    				 //  consumidor_numero,
    				 //  consumidor_bairro,
    				 //  consumidor_cidade,
    				 //  consumidor_estado,
    				 //  consumidor_fone,
    				 //  defeito_reclamado,
    				 //  defeito_reclamado_descricao,
    				 //  defeito_constatado,
    				 //  revenda_cnpj,
    				 //  revenda_nome )
    				 //  SELECT tbl_os.fabrica                 ,
    				 //  tbl_os.os                     ,
    				 //  tbl_os.sua_os                 ,
    				 //  tbl_os.posto                  ,
    				 //  tbl_posto_fabrica.codigo_posto,
    				 //  tbl_os.produto                ,
    				 //  tbl_produto.referencia        ,
    				 //  tbl_os.data_digitacao         ,
    				 //  tbl_os.data_abertura          ,
    				 //  tbl_os.data_fechamento        ,
    				 //  tbl_os.serie                  ,
    				 //  tbl_os.nota_fiscal            ,
    				 //  tbl_os.data_nf                ,
    				 //  consumidor_nome               ,
    				 //  consumidor_endereco           ,
    				 //  consumidor_numero             ,
    				 //  consumidor_bairro             ,
    				 //  consumidor_cidade             ,
    				 //  consumidor_estado             ,
    				 //  consumidor_fone               ,
    				 //  defeito_reclamado             ,
    				 //  defeito_reclamado_descricao   ,
    				 //  defeito_constatado            ,
    				 //  revenda_cnpj                  ,
    				 //  revenda_nome
    				 //  FROM    tbl_os
    				 //  JOIN    tbl_posto_fabrica  ON tbl_posto_fabrica.posto = tbl_os.posto
    				 //  AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
    				 //  JOIN    tbl_produto        ON tbl_produto.produto     = tbl_os.produto
    				 //  WHERE   tbl_os.os          = $os
    				 //  AND     tbl_os.fabrica     = $login_fabrica;";
    			    // $res1 = pg_query($con,$sql);
                #Fim HD-3046644
		        $sql  = "UPDATE tbl_os SET excluida = 't' WHERE os = $os AND fabrica = $login_fabrica;";
                $res1 = pg_query($con,$sql);
                if(pg_last_error($con)){
                    $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'ATUALIZAR dados da OS'";
                    $logs_erro[] = $sql;
                    $logs[] = pg_last_error($con);
                    $erro = "*";
                }

		    }
        }
    }

    #HD-3046644
    $sql = "SELECT DISTINCT posto
            FROM tbl_os
            WHERE fabrica = $login_fabrica
            AND   data_digitacao::date <= current_date - interval '60 days'
            AND   defeito_constatado IS NULL
            AND   solucao_os         IS NULL
            AND   finalizada         IS NULL
            AND   excluida           IS NOT TRUE
            ORDER BY tbl_os.posto ";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'SELECT DISTINCT posto'";
        $logs_erro[] = $sql;
        $logs[] = pg_last_error($con);
        $erro = "*";
    }else{
        if(pg_num_rows($res) > 0){
            $count = pg_num_rows($res);
            for ($x=0; $x < $count; $x++) {
                $posto = pg_fetch_result($res, $x, 'posto');
                $os_comunicado = "";

                $sql0 = "SELECT os,sua_os
                            FROM tbl_os
                            JOIN tbl_produto USING(produto)
                            WHERE fabrica = $login_fabrica
                            AND   data_digitacao::date <= current_date - interval '60 days'
                            AND   defeito_constatado IS NULL
                            AND   solucao_os         IS NULL
                            AND   finalizada         IS NULL
                            AND   excluida           IS NOT TRUE
                            AND   tbl_os.posto = $posto
                            AND   (
                                tbl_produto.marca NOT IN (
                                    SELECT marca
                                    FROM   tbl_marca
                                    WHERE  fabrica = $fabrica
                                    AND    upper(nome) = 'ITATIAIA'
                                ) or
                                tbl_produto.marca IS NULL
                            )
                            ORDER BY tbl_os.posto ; ";
                $res0 = pg_query($con, $sql0);
                if(pg_last_error($con)){
                    $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'sql0 = SELECT os,sua_os'";
                    $logs_erro[] = $sql0;
                    $logs[] = pg_last_error($con);
                    $erro = "*";
                }else{
                    if(pg_num_rows($res0) > 0){
                        $count_res0 = pg_num_rows($res0);

                        for ($y=0; $y < $count_res0; $y++) {
                            $os     = pg_fetch_result($res0, $y, 'os');
                            $sua_os = pg_fetch_result($res0, $y, 'sua_os');

                            $sql1 = "SELECT comunicado,data FROM
                                    tbl_comunicado
                                    WHERE tipo='Exclusão de OS'
                                    AND   data::date <= current_date - interval '2 days'
                                    AND   posto   = $posto
                                    AND   fabrica = $login_fabrica
                                    AND   mensagem like '%$sua_os%'; ";
                            $res1 = pg_query($con, $sql1);
                            if(pg_last_error($con)){
                                $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'SELECT comunicado,data'";
                                $logs_erro[] = $sql1;
                                $logs[] = pg_last_error($con);
                                $erro = "*";
                            }else{
                                if (pg_num_rows($res1) > 0) {
                                    $sql2 = "SELECT DISTINCT tbl_os.os
                                            FROM tbl_os
                                            JOIN tbl_os_extra USING (os)
                                            LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                                            LEFT JOIN tbl_os_item USING(os_produto)
                                            WHERE (tbl_os_item.pedido IS NOT NULL or tbl_os_extra.extrato IS NOT NULL)
                                            AND   tbl_os_extra.os = $os
                                            AND   tbl_os.fabrica = $login_fabrica ";
                                    $res2 = pg_query($con, $sql2);
                                    if(pg_last_error($con)){
                                        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'SELECT DISTINCT tbl_os.os'";
                                        $logs_erro[] = $sql2;
                                        $logs[] = pg_last_error($con);
                                        $erro = "*";
                                    }else{
                                        if (pg_num_rows($res2) == 0) {
                                            $sql3 = "INSERT INTO tbl_os_status (
                                                            os            ,
                                                            status_os     ,
                                                            data          ,
                                                            observacao    ,
                                                            fabrica_status,
                                                            automatico
                                                        ) VALUES (
                                                            $os              ,
                                                            15               ,
                                                            current_timestamp,
                                                            'OS CANCELADA pelo fabricante OS CANCELADA Automaticamente(sem defeito constatado e solução cadastrado)',
                                                            $fabrica,
                                                            true
                                                        );
                                                    ";
                                            $res3 = pg_query($con, $sql3);

                                            $sqli = "INSERT INTO tbl_os_interacao
                                                    (programa,fabrica, os, comentario, interno, exigir_resposta)
                                                    VALUES
                                                    ('Rotina exclui_os',$login_fabrica, $os, 'OS CANCELADA pelo fabricante OS CANCELADA Automaticamente(sem defeito constatado e solução cadastrado)', TRUE, FALSE)";
                                            $resi = pg_query($con,$sqli);


                                            if(pg_last_error($con)){
                                                $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'INSERT INTO tbl_os_status'";
                                                $logs_erro[] = $sql3;
                                                $logs[] = pg_last_error($con);
                                                $erro = "*";
                                            }

                                            $sql_up  = "UPDATE tbl_os SET excluida = 't' WHERE os = $os AND fabrica = $login_fabrica;";
                                            $res_up = pg_query($con,$sql_up);
                                            if(pg_last_error($con)){
                                                $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'ATUALIZAR dados da OS'";
                                                $logs_erro[] = $sql_up;
                                                $logs[] = pg_last_error($con);
                                                $erro = "*";
                                            }
                                        }
                                    }
                                }else{
                                    $sql_1 = "SELECT comunicado,data FROM
                                        tbl_comunicado
                                        WHERE tipo='Exclusão de OS'
                                        AND   posto   = $posto
                                        AND   fabrica = $login_fabrica
                                        AND   mensagem like '%$sua_os%'; ";
                                    $res_1 = pg_query($con, $sql_1);

                                    if(pg_last_error($con)){
                                        $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'SELECT comunicado,data FROM'";
                                        $logs_erro[] = $sql_1;
                                        $logs[] = pg_last_error($con);
                                        $erro = "*";
                                    }else{
                                        if (pg_num_rows($res_1) == 0) {
                                            if(strlen(trim($os_comunicado)) == 0){
                                                $os_comunicado = $sua_os;
                                            }else{
                                                $os_comunicado = $os_comunicado."<br>".$sua_os;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if(strlen(trim($os_comunicado))<> 0){
                            $sqlc = "INSERT INTO tbl_comunicado (
                                        mensagem        ,
                                        data            ,
                                        tipo            ,
                                        fabrica         ,
                                        posto           ,
                                        obrigatorio_site,
                                        ativo
                                    ) values (
                                        'As seguintes OSs serão excluídas daqui 2 dias por passar mais de 60 dias sem defeito constatado e solução cadastrado:<br>
                                        $os_comunicado'  ,
                                        current_timestamp,
                                        'Exclusão de OS' ,
                                        $login_fabrica         ,
                                        $posto           ,
                                        't'              ,
                                        't'
                                    );";
                            $resc = pg_query($con, $sqlc);
                            if(pg_last_error($con)){
                                $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'INSERT INTO tbl_comunicado'";
                                $logs_erro[] = $sqlc;
                                $logs[] = pg_last_error($con);
                                $erro = "*";
                            }
                        }
                    }
                }
            }

        }
    }
    #Fim HD-3046644
    if(count($logs) > 0){
      $file_log = fopen($arquivo_log,"w+");
        fputs($file_log,implode("\r\n", $logs));
        fclose ($file_log);
    }

    //envia email para HelpDESK
    if($erro){
      if(count($logs_erro) > 0){
        $file_log = fopen($arquivo_err,"w+");
            fputs($file_log,implode("\r\n", $logs));
            if(count($logs_erro) > 0){
              fputs($file_log,"\r\n ####################### SQL ####################### \r\n");
              fputs($file_log,implode("\r\n", $logs_erro));
            }
          fclose ($file_log);

          $mail = new PHPMailer();
      $mail->IsSMTP();
      $mail->IsHTML();
      $mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
      $mail->Subject = Date('d/m/Y')." - Erro na exclusão de OS(exclui-os.php)";
        $mail->Body = $mensagem;
        $mail->AddAddress($dest);
        if(file_exists($arquivo_err) AND filesize($arquivo_err) > 0)
          $mail->AddAttachment($arquivo_err);
        $mail->Send();
      }
    }

	$phpCron->termino();

}catch(Exception $e){
  $e->getMessage();
  $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

  Log::envia_email($data,Date('d/m/Y H:i:s')." - ESMALTEC - Erro na exclusão de OS(exclui-os.php)", $msg);
}
