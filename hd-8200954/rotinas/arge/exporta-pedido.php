<?php

error_reporting(E_ALL ^ E_NOTICE);

$fabrica_nome = "arge";

define('APP', 'Exporta Pedido - '.$fabrica_nome);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $login_fabrica = 137;
    $vet['fabrica'] = 'arge';
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('helpdesk@telecontrol.com.br');
    $vet['log']     = 1;

    /* Log */
    $log = new Log2();

    $sql = "SELECT TO_CHAR(tbl_pedido.data,'YYYYMMDD')      AS emissao,
                   tbl_posto.cnpj                           AS cnpj,
                   tbl_pedido.pedido                        AS pedido,
                   UPPER(tbl_tipo_pedido.codigo)            AS tipo_pedido ,
                   tbl_condicao.codigo_condicao             AS condicao
              FROM tbl_pedido
              JOIN tbl_posto_fabrica ON tbl_pedido.posto    = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
              JOIN tbl_posto         ON tbl_posto.posto     = tbl_pedido.posto
              JOIN tbl_tipo_pedido   USING (tipo_pedido)
              JOIN tbl_condicao      ON tbl_pedido.condicao = tbl_condicao.condicao
             WHERE tbl_pedido.fabrica          = $login_fabrica
               AND tbl_pedido.recebido_fabrica IS NULL
               /*AND tbl_pedido.posto            <> 6359*/
               AND tbl_pedido.status_pedido    <> 14
               AND tbl_pedido.finalizado       NOTNULL
               AND (tbl_pedido.exportado IS NULL OR (tbl_pedido.exportado NOTNULL AND tbl_pedido.recebido_fabrica IS NULL))";
    $res      = pg_query($con, $sql);
    $numrows  = pg_num_rows($res);
    $msg_erro = pg_errormessage($con);
    if(strlen($msg_erro) > 0){
      $log->adicionaLog("Erro ao exportar Pedidos");
    }
    $data     = date('Y-m-d');

    if (!empty($msg_erro)) {
        throw new Exception($msg_erro);
    }

    if ($numrows) {

        $dir = "/tmp/$fabrica_nome/pedidos";
        $file_pedido = $dir.'/telecontrol-pedido.txt';
        $file_pedido_item = $dir.'/telecontrol-pedido-item.txt';

        $fi   = fopen($file_pedido_item,'w');
        $fp   = fopen($file_pedido, 'w');
 
		      for ($i = 0; $i < $numrows; $i++) {

            $pedido      = pg_fetch_result($res, $i, 'pedido');
            $emissao     = pg_fetch_result($res, $i, 'emissao');
            $cnpj        = pg_fetch_result($res, $i, 'cnpj');
            $tipo_pedido = pg_fetch_result($res, $i, 'tipo_pedido');
            $condicao    = pg_fetch_result($res, $i, 'condicao');

            /**
             *
             *  Layout:
             *
             *  Emissao | CNPJ | Tipo Pedido | Pedido | Condicao | Tipo Frete
             *
             */

            fwrite($fp, $emissao.";");
            fwrite($fp, $cnpj.";");
            fwrite($fp, $tipo_pedido.";");
            fwrite($fp, $pedido.";");
            fwrite($fp, $condicao.";");
      	    if($tipo_pedido == "G"){
      		    fwrite($fp,"99;");
      		    fwrite($fp,"PEÇAS GARANT");
      	    }else{
      		    fwrite($fp,";PEÇAS ASS");
      	    }
            fwrite($fp, "\r\n");

            $sql_pecas = "SELECT  tbl_pedido_item.pedido_item        AS pedido_item,
                                  tbl_pedido.pedido                  AS pedidoX,
                                  tbl_peca.referencia                      AS peca_referencia,
                                  (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) AS qtde ,
                                  round(tbl_pedido_item.preco::numeric,2) AS preco 
                                  FROM tbl_pedido
                                  JOIN tbl_pedido_item USING(pedido)
                                  JOIN tbl_peca        USING(peca)
                                  WHERE tbl_pedido.pedido = $pedido
                                  AND tbl_pedido.fabrica = $login_fabrica";

            $res_pecas = pg_query($con, $sql_pecas);
            $tot_pecas = pg_num_rows($res_pecas);
            $msg_erro  = pg_errormessage($con);

            if (!empty($msg_erro)) {

                $log->adicionaLog("Erro ao exportar o pedido {$pedido}");

                throw new Exception($msg_erro);
            }

            for ($x = 0; $x < $tot_pecas; $x++) {

                $peca_referencia = trim(pg_fetch_result($res_pecas, $x, 'peca_referencia'));
                $pedido_item     = trim(pg_fetch_result($res_pecas, $x, 'pedido_item'));
                $qtde            = trim(pg_fetch_result($res_pecas, $x, 'qtde'));
                $preco           = trim(pg_fetch_result($res_pecas, $x, 'preco'));

                fwrite($fi, $peca_referencia.";");
                fwrite($fi, $qtde.";");
                fwrite($fi, $pedido.";");
                fwrite($fi, $pedido_item.";");
                fwrite($fi, $preco);
                fwrite($fi, "\r\n");
            }

            $sql_up = "UPDATE tbl_pedido
                          SET exportado     = CURRENT_TIMESTAMP,
                              status_pedido = 9
                        WHERE pedido        = $pedido
                          AND fabrica       = $login_fabrica
                          AND exportado     IS NULL ";

            $res_up   = pg_query($con, $sql_up);
            $msg_erro = pg_errormessage($con);

            if (!empty($msg_erro)) {

                $log->adicionaLog("Erro ao atualizar o status do pedido {$pedido}");

                throw new Exception($msg_erro);
            }

        }

        if (!empty($msg_erro)) {

            /* Log de Erro */
            $log->adicionaTituloEmail("Logs de Erro da Rotina de Exportação de Pedido - Arge");

            /* $log->adicionaEmail("caroline.miler@arge.com.br");
            $log->adicionaEmail("hermes.nebel@arge.com.br");
            $log->adicionaEmail("helpdesk@telecontrol.com.br"); */
            $log->adicionaEmail("guilherme.silva@telecontrol.com.br");

            /* Fim Log de Erro */

            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($vet, APP, $msg);

        } else {

            Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));

        }

        if (file_exists($file_pedido) and (filesize($file_pedido) > 0)) {

              $log->adicionaEmail("caroline.miler@arge.com.br");
              $log->adicionaEmail("hermes.nebel@arge.com.br");

              // $log->adicionaEmail("guilherme.silva@telecontrol.com.br");
              // $log->adicionaEmail("ronald.santos@telecontrol.com.br");
              // $log->adicionaEmail("rodrigo.perina@telecontrol.com.br");

              $pedidosCSV = new PedidoCSV($con);
              $pedidosCSV->setFabrica($login_fabrica);
              $status = $pedidosCSV->getPedidos();

              if($status == "ok"){
                $log->enviaAnexoCSV($login_fabrica);
              }

      				date_default_timezone_set('America/Sao_Paulo');
      				$data_arquivo = date('dmy');

      				$destino = '/home/arge/telecontrol-'  . $fabrica_nome . '/pedido-'.$data_arquivo.'.txt';
      				$destino2 = '/home/arge/telecontrol-' . $fabrica_nome . '/pedido-item-'.$data_arquivo.'.txt';
				$dirbkp   = '/home/arge/telecontrol-' . $fabrica_nome . '/bkp';


      				copy($file_pedido, $dir . '/pedido-' . $data_arquivo . '.txt');
				copy($file_pedido, $dirbkp . '/pedido-' . $data_arquivo . '.txt');
      				system("mv $file_pedido $destino");
      				copy($file_pedido_item, $dir . '/pedido-item-' . $data_arquivo . '.txt');
				copy($file_pedido_item, $dirbkp . '/pedido-item-' . $data_arquivo . '.txt');
      				system("mv $file_pedido_item $destino2"); 
        }

      fclose($fp);
      fclose($fi);

    }


} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

