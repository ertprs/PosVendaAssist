<?php

error_reporting(E_ALL ^ E_NOTICE);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $login_fabrica = 52;
    $fabrica_nome  = 'fricon';

    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('helpdesk@telecontrol.com.br');
    $vet['log']     = 1;

    $sql = "SELECT TO_CHAR(tbl_pedido.data,'YYYYMMDD')      AS emissao,
                   LPAD(tbl_posto_fabrica.codigo_posto,14,' ') AS codigo_posto,
                   LPAD(tbl_pedido.pedido::text,9,'0')      AS pedido,
                   UPPER(tbl_tipo_pedido.codigo)            AS tipo_pedido ,
                   LPAD(tbl_tabela.sigla_tabela,3,'0') AS sigla_tabela,
                   LPAD(tbl_condicao.codigo_condicao,34,' ') AS condicao,
                   SUBSTR(tbl_condicao.frete,1,1) AS tipo_frete
              FROM tbl_pedido
              JOIN tbl_posto_fabrica ON tbl_pedido.posto    = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
              JOIN tbl_posto         ON tbl_posto.posto     = tbl_pedido.posto
              JOIN tbl_tipo_pedido   USING (tipo_pedido)
              JOIN tbl_tabela        ON tbl_pedido.tabela   = tbl_tabela.tabela
              JOIN tbl_condicao      ON tbl_pedido.condicao = tbl_condicao.condicao
             WHERE tbl_pedido.fabrica          = $login_fabrica
               AND tbl_pedido.recebido_fabrica IS NULL
               AND tbl_pedido.posto            <> 6359
               AND tbl_pedido.status_pedido    <> 14
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

        $file = $dir.'/pedido.txt';
        $fp   = fopen($file, 'w');

        if (!is_resource($fp)) {
            throw new Exception('Erro ao criar arquivo de exportação.'."\n");
        }

        for ($i = 0; $i < $numrows; $i++) {

            $pedido       = pg_fetch_result($res, $i, 'pedido');
            $emissao      = pg_fetch_result($res, $i, 'emissao');
            $codigo_posto = pg_fetch_result($res, $i, 'codigo_posto');
            $tipo_pedido  = pg_fetch_result($res, $i, 'tipo_pedido');
            $sigla_tabela = pg_fetch_result($res, $i, 'sigla_tabela');
            $condicao     = pg_fetch_result($res, $i, 'condicao');
            $tipo_frete   = pg_fetch_result($res, $i, 'tipo_frete');

            /**
             *
             *  Layout:
             *
             *  Emissao | Codigo Posto | Tipo Pedido | Pedido | Condicao | Tipo Frete
             *
             */

            fputs($fp, '01');           # -  2 posições
            fputs($fp, $emissao);       # -  8 posições
            fputs($fp, $codigo_posto);  # - 14 posições
            fputs($fp, $tipo_pedido);   # -  3 posições
            fputs($fp, $sigla_tabela);  # -  3 posições
            fputs($fp, $pedido);        # -  9 posições
            fputs($fp, $condicao);      # - 34 posições
            fputs($fp, "\r\n");

            $sql_pecas = "SELECT LPAD(tbl_pedido_item.pedido_item::text,9,'0')         AS pedido_item,
                                 LPAD(tbl_pedido.pedido::text,9,'0')                   AS pedidoX,
                                 RPAD(tbl_peca.referencia,15,' ')                      AS peca_referencia,
                                 tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada AS qtde2,
                                 REPLACE(REPLACE(LPAD(TO_CHAR(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada,'99999.99'),10,'0'),'.',','),' ','0') AS qtde ,
                                 REPLACE(LPAD(TRIM(TO_CHAR(tbl_pedido_item.preco,'999999.99')),10,'0'),'.',',') AS preco ,
                                 LPAD(tbl_os_produto.os::text,9,'0')                   AS os,
                        				 LPAD(tbl_os_item.os_item::text,9,'0')                 AS os_item,
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
                $pedido_item     = trim(pg_fetch_result($res_pecas, $x, 'pedido_item'));
                $os_item         = trim(pg_fetch_result($res_pecas, $x, 'os_item'));
                $qtde2           = trim(pg_fetch_result($res_pecas, $x, 'qtde2'));
                $qtde            = trim(pg_fetch_result($res_pecas, $x, 'qtde'));
                $preco           = trim(pg_fetch_result($res_pecas, $x, 'preco'));
                $os              = trim(pg_fetch_result($res_pecas, $x, 'os'));

                if (empty($os_item)) {
                    $os_item = "000000000";
                }

                if (empty($os)) {
                    $os = "000000000";
                }

                fputs($fp, '02');               # -  2 posições
                fputs($fp, $peca_referencia);   # - 15 posições
                fputs($fp, $qtde);              # - 10 posições
                fputs($fp, $pedido);            # -  9 posições
                fputs($fp, $pedido_item);       # -  9 posições
                fputs($fp, $os);                # -  9 posições
                fputs($fp, $os_item);           # -  9 posições
                fputs($fp, $preco);             # - 10 posições
                fputs($fp, "\r\n");

                $sql_up = "UPDATE tbl_pedido
                              SET exportado     = CURRENT_TIMESTAMP,
                                  status_pedido = 2
                            WHERE pedido        = $pedido
                              AND fabrica       = $login_fabrica
                              AND exportado     IS NULL ";

                $res_up   = pg_query($con, $sql_up);
                $msg_erro = pg_errormessage($con);

                if (!empty($msg_erro)) {
                    throw new Exception($msg_erro);
                }

            }

        }

        if (!empty($msg_erro)) {

            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($vet, APP, $msg);

        } else {

            Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));

        }

        fclose($fp);

        if (file_exists($file) and (filesize($file) > 0)) {

			date_default_timezone_set('America/Sao_Paulo');
			$data_arquivo = date('dmY');

			$destino = '/home/fricon/telecontrol-' . $fabrica_nome . '/pedidos-'.$data_arquivo.'.txt';
			copy($file, $dir . '/pedido-' . $data_arquivo . '.txt');
			rename($file, $destino);

        }

    }

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

