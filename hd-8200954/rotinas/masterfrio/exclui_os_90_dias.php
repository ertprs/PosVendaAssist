<?php
/**
 *
 *
 * O.S excluída por falta de solicitação de peças a mais de 90 dias
 *
 * @author  Guilherme Monteiro
 * @version 2014.09.04
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim


try {

  include dirname(__FILE__) . '/../../dbconfig.php';
  include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
  require dirname(__FILE__) . '/../funcoes.php';
  include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica']  = 40;
    $data['fabrica_nome']   = 'masterfrio';
    $data['arquivo_log']  = 'os-excluida-90-dias-sem-peca';
    $data['log']      = 2;
    $data['arquivos']     = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs           = array();
    $logs_erro        = array();
    $logs_cliente     = array();
    $erro           = false;

  $fabrica = 40;
  $phpCron = new PHPCron($fabrica, __FILE__);
  $phpCron->inicio();

  if (ENV == 'producao' ) {
    $data['dest']     = 'helpdesk@telecontrol.com.br';
  } else {
      $data['dest']       = 'guilherme.monteiro@telecontrol.com.br';
  }

    extract($data);

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";

    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    $sql_os = "SELECT tbl_os.os, tbl_os.produto
      FROM tbl_os
      LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
      LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto and tbl_os_item.fabrica_i = $login_fabrica
      WHERE tbl_os.fabrica = $login_fabrica
      AND tbl_os.data_fechamento IS NULL
      AND tbl_os.finalizada IS NULL
      AND tbl_os.os_fechada is false
      AND tbl_os.data_digitacao::date < current_date - INTERVAL '90 days'
      /* AND tbl_os.posto = 6359 AND tbl_os.data_digitacao > '2011-01-01' */
      AND tbl_os.excluida is not true
      AND tbl_os_item.os_item IS NULL;
      ";
    $res_os       = pg_query($con, $sql_os);
    $logs[] = pg_last_error($con);

    $total = pg_num_rows($res_os);

    if($total > 0){

      for($i = 0; $i < $total; $i++){

  	    $sql = "BEGIN TRANSACTION";
        $res = pg_query($con, $sql);

        $os = pg_fetch_result($res_os, $i,'os');
        $produto = pg_fetch_result($res_os, $i, 'produto');

        $sql = "INSERT INTO tbl_os_status (
                os,
                status_os,
                data,
                observacao
              ) VALUES (
                $os,
                15,
                current_timestamp,
                'OS excluida automaticamente a pedido do fabricante. Motivo: Falta solicitação de peças a mais de 90 dias.'
              );";
        $res1 = pg_query($con,$sql);


        if(pg_last_error($con)){

          $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'INSERT status 15'";
          $logs_erro[] = $sql;
          $logs[] = pg_last_error($con);
          $erro = "*";

        }else{

          $sql = "INSERT INTO tbl_os_excluida (
                      fabrica           ,
                      os                ,
                      sua_os            ,
                      posto             ,
                      codigo_posto      ,
                      produto           ,
                      referencia_produto,
                      data_digitacao    ,
                      data_abertura     ,
                      data_fechamento   ,
                      serie             ,
                      nota_fiscal       ,
                      data_nf           ,
                      consumidor_nome   ,
                      consumidor_endereco,
                      consumidor_numero,
                      consumidor_bairro,
                      consumidor_cidade,
                      consumidor_estado,
                      consumidor_fone,
                      defeito_reclamado,
                      defeito_reclamado_descricao,
                      defeito_constatado,
                      revenda_cnpj,
                      revenda_nome )
                  SELECT tbl_os.fabrica                 ,
                      tbl_os.os                     ,
                      tbl_os.sua_os                 ,
                      tbl_os.posto                  ,
                      tbl_posto_fabrica.codigo_posto,
                      tbl_os.produto                ,
                      tbl_produto.referencia        ,
                      tbl_os.data_digitacao         ,
                      tbl_os.data_abertura          ,
                      tbl_os.data_fechamento        ,
                      tbl_os.serie                  ,
                      tbl_os.nota_fiscal            ,
                      tbl_os.data_nf                ,
                      consumidor_nome               ,
                      consumidor_endereco           ,
                      consumidor_numero             ,
                      consumidor_bairro             ,
                      consumidor_cidade             ,
                      consumidor_estado             ,
                      consumidor_fone               ,
                      defeito_reclamado             ,
                      defeito_reclamado_descricao   ,
                      defeito_constatado            ,
                      revenda_cnpj                  ,
                      revenda_nome
                  FROM    tbl_os
                  JOIN    tbl_posto_fabrica  ON tbl_posto_fabrica.posto = tbl_os.posto
                  AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
                  JOIN    tbl_produto        ON tbl_produto.produto     = tbl_os.produto
                  WHERE   tbl_os.os          = $os
                  AND     tbl_os.fabrica     = $login_fabrica;";

          $res1 = pg_query($con,$sql);

          if(pg_last_error($con)){

            $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'INSERT tbl_os_excluida'";
            $logs_erro[] = $sql;
            $logs[] = pg_last_error($con);
            $erro = "*";

          }else{

            $sql  = "UPDATE tbl_os SET fabrica = 0, excluida = 't' WHERE os = $os AND fabrica = $login_fabrica;";
            $res1 = pg_query($con,$sql);

            if(pg_last_error($con)){

              $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'ATUALIZAR dados da OS'";
              $logs_erro[] = $sql;
              $logs[] = pg_last_error($con);
              $erro = "*";

            }

          }

          // INSERE COMUNICADO

          $sql_comunicado = "SELECT tbl_posto.posto
            FROM tbl_posto
            JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
            JOIN tbl_os on tbl_os.posto = tbl_posto.posto
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_os.os = $os";

          $res_comunicado       = pg_query($con, $sql_comunicado);
          $msg_erro .= pg_last_error($con);

          if(pg_num_rows($res_comunicado) > 0){
            $posto_comunicado = pg_result($res_comunicado,0,'posto');
          }
          $texto = "O.S: $os excluída por falta de solicitação de peças a mais de 90 dias.";

          $sql = "INSERT into tbl_comunicado(
              descricao  ,
              mensagem   ,
              data       ,
              tipo       ,
              fabrica    ,
              ativo      ,
              pais       ,
              posto      ,
              produto,
              obrigatorio_site
            )values(
              'Comunicado',
              '$texto'        ,
              current_timestamp      ,
              'Comunicado',
              $login_fabrica         ,
              't'                    ,
              'BR'                   ,
              $posto_comunicado           ,
              $produto,
              't'
            )";
          $res = pg_query($con,$sql);

          if (pg_last_error($con)){
            $erro = pg_last_error($con) ;
          }

        }

		if (strlen($erro)>0){
            $res = pg_query($con,'ROLLBACK TRANSACTION');
          }else{
            $res = pg_query($con,'COMMIT TRANSACTION');
          }

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
        $mail->Subject = Date('d/m/Y')." - Erro na exclusão de OS(exclui_os_90_dias.php)";
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

  Log::envia_email($data,Date('d/m/Y H:i:s')." - MASTERFRIO - Erro na exclusão de OS(exclui_os_90_dias.php)", $msg);
}
