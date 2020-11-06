<?php
/**
 *
 * gera-pedido.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Guilherme Monteiro
 * @version 2016.8.6
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

  include dirname(__FILE__) . '/../../dbconfig.php';
  include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
  require dirname(__FILE__) . '/../funcoes.php';
  include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica']  = 104;
    $data['fabrica_nome']   = 'vonder';
    $data['arquivo_log']  = 'gera-pedido-os';
    $data['log']      = 2;
    $data['arquivos']     = "/tmp";
   # $data['arquivos']     = "/home/gaspar/public_html/PosVendaAssist/rotinas/vonder/entrada";
    $data['data_sistema']   = Date('Y-m-d');
    $logs           = array();
    $logs_erro        = array();
    $logs_cliente     = array();
    $pedido_pecas     = array();
    $erro           = false;

  $fabrica = 104;
  $phpCron = new PHPCron($fabrica, __FILE__);
  $phpCron->inicio();

  if (ENV == 'producao' ) {
    $data['dest'] = 'helpdesk@telecontrol.com.br';
  } else {
    $data['dest'] = 'guilherme.monteiro@telecontrol.com.br';
  }
    extract($data);

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );




  // ####################################################
  // INTERVENCOES OS Aberta
  // ####################################################


  $inter_produto_auditado = "SELECT tbl_auditoria_os.os
                  INTO TEMP tmp_produto_auditado
                  FROM tbl_auditoria_os
                  JOIN tbl_os USING(os)
                  WHERE tbl_auditoria_os.auditoria_status in (1,3,4)
                  AND tbl_auditoria_os.liberada IS NULL
                  AND tbl_os.fabrica = $fabrica";
  $res_inter_produto_auditado = pg_query($con, $inter_produto_auditado);

  $inter_posto_auditado = "SELECT tbl_auditoria_os.os
                  INTO TEMP tmp_posto_auditado
                  FROM tbl_auditoria_os
                  JOIN tbl_os USING(os)
                  WHERE tbl_auditoria_os.auditoria_status = 6
                  AND tbl_auditoria_os.liberada IS NULL
                  AND tbl_os.fabrica = $fabrica
                  ";
  $res_inter_posto_auditado = pg_query($con, $inter_posto_auditado);

  $inter_intervencao_tecnica = "SELECT intervencao.os
            INTO TEMP tmp_intervencao_tecnica
            FROM (
              SELECT ultimo_status.os, (
                  SELECT status_os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                   WHERE tbl_os_status.os             = ultimo_status.os
                     AND tbl_os_status.fabrica_status = $fabrica
                     AND status_os IN (62,147,64,81,201)
                     AND tbl_os.excluida is not true
               ORDER BY os_status DESC LIMIT 1) AS ultimo_status_os
                FROM (
                  SELECT DISTINCT os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                   WHERE tbl_os_status.fabrica_status = $fabrica
                     AND status_os IN (62,147,64,81,201)
                     AND tbl_os.excluida is not true
                ) ultimo_status) intervencao
           WHERE intervencao.ultimo_status_os IN (62,147)";
  $res_inter_intervencao_tecnica = pg_query($con, $inter_intervencao_tecnica);

  $inter_os_reincidente = "SELECT intervencao.os
            INTO TEMP tmp_os_reincidente
            FROM (
              SELECT ultima.os, (
                  SELECT status_os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                    WHERE status_os IN (19,67,68,70,95,131,134,157)
                    AND tbl_os_status.os = ultima.os
                    AND tbl_os_status.fabrica_status = tbl_os.fabrica
                    AND tbl_os.fabrica = $fabrica
                    AND tbl_os.os_reincidente IS TRUE
                    AND tbl_os.excluida is not true
                    ORDER BY os_status DESC LIMIT 1) AS ultimo_status_os
                FROM (
                  SELECT DISTINCT os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                    WHERE status_os IN (19,67,68,70,95,131,134,157)
                    AND tbl_os_status.fabrica_status = tbl_os.fabrica
                    AND tbl_os.fabrica = $fabrica
                    AND tbl_os.os_reincidente IS TRUE
                    AND tbl_os.excluida is not true
                ) ultima) intervencao
           WHERE intervencao.ultimo_status_os IN (67,68,70,95,134,157)";
  $res_inter_os_reincidente = pg_query($con, $inter_os_reincidente);

  $inter_pecas_excedentes = "SELECT intervencao.os
            INTO TEMP tmp_pecas_excedentes
            FROM (
              SELECT ultimo_status.os, (
                  SELECT status_os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                   WHERE tbl_os_status.os             = ultimo_status.os
                     AND tbl_os_status.fabrica_status = $fabrica
                     AND status_os IN (118,185,187)
                     $cond_data
                     $cond_excluida
                     $cond_garantia
               ORDER BY os_status DESC LIMIT 1) AS ultimo_status_os
                FROM (
                  SELECT DISTINCT os
                    FROM tbl_os_status
                    JOIN tbl_os USING(os)
                  WHERE tbl_os_status.fabrica_status = $fabrica
                    AND status_os IN (118,185,187)
                    AND tbl_os.excluida is not true
                    AND tbl_os.troca_garantia IS NOT TRUE
                ) ultimo_status) intervencao
           WHERE intervencao.ultimo_status_os IN (118)";
  $res_inter_pecas_excedentes = pg_query($con, $inter_pecas_excedentes);

  $sql = "SELECT
          tbl_os.posto        ,
          tbl_produto.linha   ,
          tbl_os_item.peca    ,
          tbl_os_item.os_item ,
          tbl_os_item.qtde    ,
          tbl_os.sua_os,
          tbl_os.os,
          tbl_posto_fabrica.codigo_posto,
          tbl_posto.nome AS posto_nome
          INTO TEMP tmp_pedido_vonder
      FROM    tbl_os_item
      JOIN    tbl_servico_realizado USING (servico_realizado)
      JOIN    tbl_os_produto USING (os_produto)
      JOIN    tbl_os         USING (os)
      JOIN    tbl_posto      USING (posto)
      JOIN    tbl_produto          ON tbl_os.produto          = tbl_produto.produto
      JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
      WHERE   tbl_os_item.pedido IS NULL
      AND     tbl_os.validada    IS NOT NULL
      AND     tbl_os.excluida    IS NOT TRUE
      AND     tbl_os.posto       <> 6359
      AND     tbl_os.fabrica    = $login_fabrica
      AND     tbl_os.troca_garantia       IS NULL
      AND     tbl_os.troca_garantia_admin IS NULL
      AND    (tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
      OR  tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
      AND     tbl_servico_realizado.gera_pedido
      AND     tbl_os.os NOT IN ( SELECT os FROM tmp_produto_auditado)
      AND     tbl_os.os NOT IN ( SELECT os FROM tmp_posto_auditado)
      AND     tbl_os.os NOT IN ( SELECT os FROM tmp_intervencao_tecnica)
      AND     tbl_os.os NOT IN ( SELECT os FROM tmp_os_reincidente)
      AND     tbl_os.os NOT IN ( SELECT os FROM tmp_pecas_excedentes);
      SELECT DISTINCT posto, linha, codigo_posto, posto_nome, os from tmp_pedido_vonder ;";
  $resP = pg_query($con, $sql);

  if(pg_last_error($con)){
    $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Posto'";
    $logs_erro[] = $sql;
    $logs[] = 'Erro ao listar os Postos que irão gerar pedido';
    $erro   = true;
    throw new Exception ($msg_erro);
  }

  /* Estoque */

  for ($i=0; $i < pg_num_rows($resP); $i++) {

    $posto = pg_result($resP,$i,'posto');
    $linha = pg_result($resP,$i,'linha');
    $xos   = pg_result($resP,$i,'os');
    $postoCodigo = pg_result($resP,$i,'codigo_posto');
    $postoNome = pg_result($resP,$i,'posto_nome');
    $erro = " ";
    $res = pg_query($con, "BEGIN TRANSACTION");

    #Garantia
    $sql = "select condicao from tbl_condicao where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";

    $resultG = pg_query($con, $sql);
    if(pg_last_error($con)){
      $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Condição Pagamento'";
      $logs_erro[] = $sql;
      $logs[] = "Erro por falta de condição de pagamento 'GARANTIA' para OS: $sua_os Posto: {$postoCodigo} - {$postoNome} ";
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
      $logs[] = "Erro por falta de tipo de pedido 'GARANTIA' para OS: $sua_os Posto: {$postoCodigo} - {$postoNome}";
      $erro = "*";
    }else{
      $tipo_pedido = pg_result($resultP,0,'tipo_pedido');
    }

    $sql = "INSERT INTO tbl_pedido (
          posto        ,
          fabrica      ,
          condicao     ,
          tipo_pedido  ,
          linha        ,
          status_pedido
        ) VALUES (
          $posto      ,
          $login_fabrica    ,
          $condicao   ,
          $tipo_pedido,
          $linha      ,
          1
        ) RETURNING pedido;";
    $resultP = pg_query($con, $sql);
    if(pg_last_error($con)){
      $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido'";
      $logs_erro[] = $sql;
      $logs[] = "Erro ao gravar pedido para o Posto: {$postoCodigo} - {$postoNome}";
      $erro = "*";
    }else{
      $pedido = pg_result($resultP,0,0);

      $sql_item = "SELECT
            peca    ,
            os_item,
            sum(qtde) as qtde
              from
            tmp_pedido_vonder
            WHERE os = $xos
            group by peca,os_item";
      $result2 = pg_query($con,$sql_item);
      for ($x=0; $x < pg_num_rows($result2); $x++) {
        $peca     = pg_result($result2,$x,'peca');
        $qtde     = pg_result($result2,$x,'qtde');
        $os_item  = pg_result($result2,$x,'os_item');
        $os     = pg_result($result2,$x,'os');
        $sql = "INSERT INTO tbl_pedido_item (
            pedido,
            peca  ,
            qtde  ,
            qtde_faturada,
            qtde_cancelada
          ) VALUES (
            $pedido,
            $peca  ,
            $qtde  ,
            0      ,
            0      ) RETURNING pedido_item";
        $resultX = pg_query($con,$sql);
        if(pg_last_error($con)){
          $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido Item'";
          $logs_erro[] = $sql;
          $logs[] = "Erro ao gravar itens do pedido $pedido para o Posto: $postoCodigo - $postoNome";
          $erro = "*";
        }else{
          $pedido_item = pg_result($resultX,0,0);

          $sql = "SELECT fn_atualiza_os_item_pedido_item($os_item,$pedido,$pedido_item,$login_fabrica)";

          $resultX = pg_query($con,$sql);
          if(pg_last_error($con)){
            $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Atualiza OS Item'";
            $logs_erro[] = $sql;
            $logs[] = "Erro ao atualizar itens do pedido $pedido para o Posto: $postoCodigo - $postoNome";
            $erro = "*";
          }
        }

      }


      $sql = "SELECT count(pedido_item) AS itens FROM tbl_pedido_item WHERE pedido = $pedido";
      $resultP = pg_query($con,$sql);
      $itens = pg_fetch_result($resultP, 0, 'itens');

      if($itens > 0){
        $sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
        $resultX = pg_query($con,$sql);
        if(pg_last_error($con)){
          $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido Item'";
          $logs_erro[] = $sql;
          $logs[] = "Erro ao finalizar o pedido $pedido para o Posto: $postoCodigo - $postoNome " .pg_last_error();
          $erro = "*";
        }
      }else{
        $sql = "DELETE FROM tbl_pedido WHERE pedido = $pedido";
        $resultX = pg_query($con,$sql);
        if(pg_last_error($con)){
          $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Deletar Pedido'";
          $logs_erro[] = $sql;
          $logs[] = "Erro ao deletar $pedido para o Posto: $postoCodigo - $postoNome";
          $erro = "*";
        }
      }

      if ($erro == "*") {
        $resultX = pg_query($con,"ROLLBACK TRANSACTION");
        $sqlY = "SELECT DISTINCT
          referencia,
          qtde,
          tbl_tabela_item.preco
          FROM
          tmp_pedido_vonder
          JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_pedido_vonder.posto and tbl_posto_fabrica.fabrica = $login_fabrica
          JOIN tbl_peca USING(peca)
          JOIN tbl_posto_linha    ON tbl_posto_linha.posto     = tmp_pedido_vonder.posto
          JOIN tbl_tabela_item    ON tbl_tabela_item.peca      = tmp_pedido_vonder.peca and tbl_tabela_item.tabela    = tbl_posto_linha.tabela
		  JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.fabrica = $login_fabrica
		  WHERE tmp_pedido_vonder.posto = $posto ";
        $resY = pg_query($con, $sqlY);

        for ($y=0; $y < pg_num_rows($resY); $y++) {
          $referencia = pg_fetch_result($resY, $y, 'referencia');
          $qtde = pg_fetch_result($resY, $y, 'qtde');
          $preco = pg_fetch_result($resY, $y, 'preco');
          $logs[] = "Posto: '{$postoCodigo}' - OS: {$sua_os} - Peça: {$referencia} - Qtde:{$qtde} - Preço:{$preco}";
        }

      }else{
        $resultX = pg_query($con,"COMMIT TRANSACTION");
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
          /*
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->IsHTML();
            $mail->AddReplyTo("guilherme.monteiro@telecontrol.com.br", "Suporte Telecontrol");
            $mail->Subject = Date('d/m/Y')." - Erro na geração de pedido (gera-pedido-os.php)";
              $mail->Body = $mensagem;
              $mail->AddAddress($dest);
              if(file_exists($arquivo_err) AND filesize($arquivo_err) > 0)
                #$mail->AddAttachment($arquivo_err);
              $mail->Send();
          */
          ##### ENVIA EMAIL #######

          $headers = "MIME-Version: 1.0\n";
          $headers.= "From: helpdesk@telecontrol.com.br\r\n";
          $boundary = "XYZ-" . date("dmYis") . "-ZYX";
          $headers.= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";
          $headers.= "$boundary\n";

          $corpo_mensagem = "<html>
          <head>
             <title>Log Erro Gera Pedido</title>
          </head>
          <body>
          </body>
          </html>";
          // Nesta linha abaixo, abrimos o arquivo enviado.
            $fp = fopen($arquivo_log,"rb");
          // Agora vamos ler o arquivo aberto na linha anterior
            $anexo = fread($fp,filesize($arquivo_log));
          // Codificamos os dados com MIME para o e-mail
            $anexo = base64_encode($anexo);
          // Fechamos o arquivo aberto anteriormente
            fclose($fp);
          // Nesta linha a seguir, vamos dividir a variável do arquivo em pequenos pedaços para podermos enviar

            $anexo = chunk_split($anexo);
            $mensagem = "--$boundary\n";
            $mensagem.= "Content-Transfer-Encoding: 8bits\n";
            $mensagem.= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
            $mensagem.= "$corpo_mensagem\n";
            $mensagem.= "--$boundary\n";
            $mensagem.= "Content-Type: ".$arquivo_log."\n";
            $mensagem.= "Content-Disposition: attachment; filename=\"Arquivo Log Gera Pedido".Date("Y-m-d H:i:s").".log\"\n";
            $mensagem.= "Content-Transfer-Encoding: base64\n\n";
            $mensagem.= "$anexo\n";
            $mensagem.= "--$boundary--\r\n";

            $destinatario = "jeancesar@ovd.com.br, helpdesk@telecontrol.com.br";
            #$destinatario = "guilherme.monteiro@telecontrol.com.br";
            $assunto = "LOG ERRO GERA PEDIDO - VONDER";
            mail($destinatario, $assunto, $mensagem, $headers);
      }
    }

  $phpCron->termino();

} catch (Exception $e) {
  $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    Log::envia_email($data,Date('d/m/Y H:i:s')." - VONDER - Erro na geração de pedido(gera-pedido.php)", $msg);
}?>
