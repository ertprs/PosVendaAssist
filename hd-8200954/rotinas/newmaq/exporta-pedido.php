<?php

error_reporting(E_ALL ^ E_NOTICE);

$fabrica_nome = "newmaq";

define('APP', 'Exporta Pedido - '.$fabrica_nome);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $login_fabrica = 120;
    $vet['fabrica'] = 'newmaq';
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('ronald.santos@telecontrol.com.br');
    $vet['log']     = 1;

    $getDate = getDate();
    $dia_posto = "AND tbl_pedido.posto NOT IN (519914,6359)";
    if ($getDate["wday"] == 4) {
      $dia_posto = "AND tbl_pedido.posto <> 6359"; 
    } 

    $sql = "SELECT TO_CHAR(tbl_pedido.data,'YYYYMMDD')      AS emissao,
                   tbl_posto.cnpj                           AS cnpj,
                   tbl_pedido.pedido                        AS pedido,
                   UPPER(tbl_tipo_pedido.codigo)            AS tipo_pedido ,
                   tbl_condicao.codigo_condicao             AS condicao,
                   tbl_pedido.valor_frete,
                   tbl_transportadora_fabrica.codigo_interno AS cod_transp
              FROM tbl_pedido
              JOIN tbl_posto_fabrica ON tbl_pedido.posto    = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
              JOIN tbl_posto         ON tbl_posto.posto     = tbl_pedido.posto
              JOIN tbl_tipo_pedido   USING (tipo_pedido)
              JOIN tbl_condicao      ON tbl_pedido.condicao = tbl_condicao.condicao
              LEFT JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_pedido.transportadora
             WHERE tbl_pedido.fabrica          = $login_fabrica
               AND tbl_pedido.recebido_fabrica IS NULL
               $dia_posto
	       AND tbl_pedido.status_pedido    NOT IN(14,18)
	       AND tbl_pedido.linha            = 706
               AND tbl_pedido.finalizado       NOTNULL
               AND tbl_pedido.exportado IS NULL";

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

        $file_pedido = $dir.'/pedido.txt';
	      $file_pedido_item = $dir.'/pedido-item.txt';
        $fp   = fopen($file_pedido, 'w');
	      $fi   = fopen($file_pedido_item,'w');

        if (!is_resource($fp)) {
            throw new Exception('Erro ao criar arquivo de exportação.'."\n");
        }

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

            fwrite($fp, $emissao."\t");
            fwrite($fp, $cnpj."\t");
            fwrite($fp, $tipo_pedido."\t");
            fwrite($fp, $pedido."\t");
            fwrite($fp, $condicao . "\t");
            fwrite($fp, pg_fetch_result($res, $i, "valor_frete") . "\t");
            fwrite($fp, pg_fetch_result($res, $i, "cod_transp"));
            fwrite($fp, "\r\n");

            $sql_pecas = "SELECT  tbl_pedido_item.pedido_item        AS pedido_item,
                                  tbl_pedido.pedido                  AS pedidoX,
                                  tbl_peca.referencia                      AS peca_referencia,
                                  (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) AS qtde ,
                                  (tbl_pedido_item.preco) AS preco 
                                  FROM tbl_pedido
                                  JOIN tbl_pedido_item USING(pedido)
                                  JOIN tbl_peca        USING(peca)
                                  WHERE tbl_pedido.pedido = $pedido
                                  AND tbl_pedido.fabrica = $login_fabrica";

            $res_pecas = pg_query($con, $sql_pecas);
            $tot_pecas = pg_num_rows($res_pecas);
            $msg_erro  = pg_errormessage($con);

            if (!empty($msg_erro)) {
                throw new Exception($msg_erro);
            }

            for ($x = 0; $x < $tot_pecas; $x++) {

                $peca_referencia = trim(pg_fetch_result($res_pecas, $x, 'peca_referencia'));
                $pedido_item     = trim(pg_fetch_result($res_pecas, $x, 'pedido_item'));
                $qtde            = trim(pg_fetch_result($res_pecas, $x, 'qtde'));
                $preco           = trim(pg_fetch_result($res_pecas, $x, 'preco'));

                fwrite($fi, $peca_referencia."\t");
                fwrite($fi, $qtde."\t");
                fwrite($fi, $pedido."\t");
                fwrite($fi, $pedido_item."\t");
                fwrite($fi, $preco);
                fwrite($fi, "\r\n");


                if (!empty($msg_erro)) {
                    throw new Exception($msg_erro);
                }

            }
                $sql_up = "UPDATE tbl_pedido
                              SET exportado     = CURRENT_TIMESTAMP,
                                  status_pedido = 2
                            WHERE pedido        = $pedido
                              AND fabrica       = $login_fabrica
                              AND exportado     IS NULL ";

                $res_up   = pg_query($con, $sql_up);
                $msg_erro = pg_errormessage($con);
        }

        if (!empty($msg_erro)) {

            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($vet, APP, $msg);

        } else {

            Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));

        }

        fclose($fp);
	fclose($fi);

        if (file_exists($file_pedido) and (filesize($file_pedido) > 0)) {

		date_default_timezone_set('America/Sao_Paulo');
		$data_arquivo = date('dmy');

		$destino = "/home/$fabrica_nome/telecontrol-$fabrica_nome/pedido.txt";
		$destino2 = "/home/$fabrica_nome/telecontrol-$fabrica_nome/pedido-item.txt";

		copy($file_pedido, $dir . '/pedido-' . $data_arquivo . '.txt');
		system("mv $file_pedido $destino");
		copy($file_pedido_item, $dir . '/pedido-item-' . $data_arquivo . '.txt');
		system("mv $file_pedido_item $destino2");
        }

    }

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

