<?php

error_reporting(E_ALL);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $login_fabrica = 147;
    $fabrica_nome  = 'hitachi';

    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('william.lopes@hotmail.com');
    // $vet['dest']    = array('helpdesk@telecontrol.com.br','amaral@hitachi-koki.com.br');
    $vet['log']     = 1;

    $sql = "SELECT TO_CHAR(tbl_pedido.data,'YYYYMMDD')      AS data_abertura,
                          tbl_posto_fabrica.codigo_posto                     AS posto_codigo,
                          tbl_posto.nome                                          AS posto_nome,
                           UPPER(tbl_tipo_pedido.codigo)              AS tipo_pedido,
                           tbl_pedido.pedido                                    AS pedido,
                           tbl_pedido.tipo_frete                                    AS tipo_frete,
                           tbl_pedido.obs                                         AS obs,
                           tbl_tabela.sigla_tabela                             AS sigla_tabela,
                           tbl_condicao.codigo_condicao AS condicao
              FROM tbl_pedido
              JOIN tbl_posto_fabrica ON tbl_pedido.posto    = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
              JOIN tbl_posto         ON tbl_posto.posto     = tbl_pedido.posto
              JOIN tbl_tabela         ON tbl_tabela.tabela     = tbl_pedido.tabela  AND  tbl_tabela.fabrica = {$login_fabrica}
              JOIN tbl_tipo_pedido   USING (tipo_pedido)
              JOIN tbl_condicao      ON tbl_pedido.condicao = tbl_condicao.condicao
             WHERE tbl_pedido.fabrica          = {$login_fabrica}
               AND tbl_pedido.recebido_fabrica IS NULL
               AND tbl_pedido.posto            <> 6359
               AND tbl_pedido.status_pedido    not in (14,18)
               AND tbl_pedido.finalizado       NOTNULL
               AND tbl_pedido.exportado        IS NULL ";
    $res      = pg_query($con, $sql);
    $numrows  = pg_num_rows($res);
    $msg_erro = pg_errormessage($con);
    $data     = date('Y-m-d');

    if (!empty($msg_erro)) {
        throw new Exception($msg_erro);
    }

    if ($numrows) {

        $dir = "/tmp/$fabrica_nome/pedidos";

        if (!is_dir($dir)) {
          
            if (!mkdir($dir)) {
                throw new Exception('Erro ao criar diretório do fabricante.'."\n");
            }

            if (!chmod($dir, 0777)) {
                throw new Exception('Erro ao dar permissão ao diretório.'."\n");
            }

        }

        
        for ($i = 0; $i < $numrows; $i++) {



            $pedido        = pg_fetch_result($res, $i, "pedido") ;
            $tipo_pedido   = pg_fetch_result($res, $i, "tipo_pedido") ;
            $data_abertura = pg_fetch_result($res, $i, "data_abertura") ;
            $posto_codigo  = pg_fetch_result($res, $i, "posto_codigo") ;
            $posto_nome    = pg_fetch_result($res, $i, "posto_nome") ;
            $sigla_tabela  = pg_fetch_result($res, $i, "sigla_tabela") ;
            $condicao      = pg_fetch_result($res, $i, "condicao") ;
            $tipo_frete    = pg_fetch_result($res, $i, "tipo_frete") ;
            $obs           = pg_fetch_result($res, $i, "obs") ;


            $file = $dir."/pedido-$pedido.txt";
            $fp   = fopen($file, 'w');


            if (!is_resource($fp)) {
                throw new Exception('Erro ao criar arquivo de exportação.'."\n");
            }


           $sql_pecas = "SELECT tbl_pedido_item.pedido_item,
                                 tbl_pedido.pedido AS pedidoX,
                                 tbl_peca.referencia AS peca_referencia,
                                 tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada AS qtde ,
                                 tbl_pedido_item.preco,
                                 tbl_os_produto.produto, 
                                 tbl_peca.peca
                            FROM tbl_pedido
                            JOIN tbl_pedido_item USING(pedido)
                            JOIN tbl_peca        USING(peca)
                       LEFT JOIN tbl_os_item     ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                       LEFT JOIN tbl_os_produto  USING(os_produto)
                           WHERE tbl_pedido.pedido = $pedido";

            $res_pecas = pg_query($con, $sql_pecas);
            $tot_pecas = pg_num_rows($res_pecas);
            $msg_erro  = pg_errormessage($con);

            if (!empty($msg_erro)) {
                throw new Exception($msg_erro);
            }

            for ($x = 0; $x < $tot_pecas; $x++) {

                $peca_referencia = pg_fetch_result($res_pecas, $x, 'peca_referencia');
                $qtde            = trim(pg_fetch_result($res_pecas, $x, 'qtde'));
                $pedido_item     = trim(pg_fetch_result($res_pecas, $x, 'pedido_item'));
                $preco           = trim(pg_fetch_result($res_pecas, $x, 'preco'));


                fputs($fp, "$pedido");
                fputs($fp, ";$tipo_pedido");
                fputs($fp, ";$data_abertura");
                fputs($fp, ";$posto_codigo");
                fputs($fp, ";$posto_nome");
                fputs($fp, ";$sigla_tabela");
                fputs($fp, ";$condicao");
                fputs($fp, ";$tipo_frete");
                fputs($fp, ";$obs");
                fputs($fp, ";$pedido_item");      
                fputs($fp, ";$peca_referencia");  
                fputs($fp, ";$qtde");             
                fputs($fp, ";$preco");            
                fputs($fp, "\n");

             
            }

          fclose($fp);

        }

        if (!empty($msg_erro)) {

            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($vet, APP, $msg);

        } else {

            Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));

        }

        fclose($fp);

      if (file_exists($file) and (filesize($file) > 0)) {

        $destino = '/home/hitachi/pos-vendas/telecontrol-' . $fabrica_nome . '/pedidos/telecontrol-pedido-'.$pedido.'.txt';
        copy($file, $destino);

        if(file_exists($destino)){
              $sql_up = "UPDATE tbl_pedido
                                SET exportado     = CURRENT_TIMESTAMP,
                                status_pedido = 2
                          WHERE pedido        = {$pedido}
                            AND fabrica       = {$login_fabrica}
                            AND exportado     IS NULL ";

              $res_up   = pg_query($con, $sql_up);
              $msg_erro = pg_errormessage($con);

              if (!empty($msg_erro)) {
                  throw new Exception($msg_erro);
              }

          }
      }

    }

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

