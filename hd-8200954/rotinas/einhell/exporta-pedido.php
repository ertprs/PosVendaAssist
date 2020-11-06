<?php 

error_reporting(E_ALL ^ E_NOTICE);

$fabrica_nome = "einhell";

define('APP', 'Exporta Pedido - '.$fabrica_nome);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/conexao_ftp_einhell.php';    

    $login_fabrica = 160;
    $vet['fabrica'] = 'einhell';
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('daniel.pereira@einhell.com', 'luiz.munoz@einhell.com');
    $vet['log']     = 1;

    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    $log_dir = '/tmp/' . $fabrica_nome .'/logs';
    $arq_log = $log_dir . '/exporta-pedido-' . $now . '.log';
    $err_log = $log_dir . '/exporta-pedido-err-' . $now . '.log';

    $nlog = fopen($arq_log, "w");
    $elog = fopen($err_log, "w");

    $sql_pega_postoEinhell = "SELECT posto_fabrica FROM tbl_fabrica WHERE fabrica = $login_fabrica";
    $res_pega_postoEinhell = pg_query($con, $sql_pega_postoEinhell);
    if(pg_num_rows($res_pega_postoEinhell)> 0){
        $posto_einhell = pg_fetch_result($res_pega_postoEinhell, 0, posto_fabrica);
    }else{
      $msg_erro .= "Não foi possivel encontrar o posto Einhell ";
      throw new Exception($msg_erro);    
    }
        
    $sql = "SELECT TO_CHAR(tbl_pedido.data,'DD-MM-YYYY')      AS emissao,
                   tbl_posto.cnpj                           AS cnpj,
                   tbl_pedido.pedido                        AS pedido,
                   tbl_pedido.tipo_frete,
                   tbl_pedido.posto,
                   UPPER(tbl_tipo_pedido.codigo)            AS tipo_pedido ,
                   tbl_condicao.codigo_condicao             AS condicao
              FROM tbl_pedido
              JOIN tbl_posto_fabrica ON tbl_pedido.posto    = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
              JOIN tbl_posto         ON tbl_posto.posto     = tbl_pedido.posto
              JOIN tbl_tipo_pedido   USING (tipo_pedido)
              JOIN tbl_condicao      ON tbl_pedido.condicao = tbl_condicao.condicao
             WHERE tbl_pedido.fabrica          = $login_fabrica
               AND tbl_pedido.recebido_fabrica IS NULL
               AND tbl_pedido.posto            !=  6359
               AND tbl_pedido.status_pedido     in (1, 9)
			   AND tbl_pedido.total > 0 
               AND tbl_pedido.finalizado       NOTNULL
               AND (tbl_pedido.exportado IS NULL OR (tbl_pedido.exportado NOTNULL AND tbl_pedido.recebido_fabrica IS NULL)) ";

    $res      = pg_query($con, $sql);
    $numrows  = pg_num_rows($res);
    $msg_erro .= pg_errormessage($con);

    $data     = date('Y-m-d');

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
            $tipo_frete  = pg_fetch_result($res, $i, 'tipo_frete');
            $condicao    = pg_fetch_result($res, $i, 'condicao');
            $posto_pedido    = pg_fetch_result($res, $i, 'posto');

            /**
             *
             *  Layout:
             *
             *  Emissao | CNPJ | Tipo Pedido | Pedido | Condicao | Tipo Frete
             *
             */

            fwrite($fp, $emissao.';');
            fwrite($fp, $cnpj.';');
            fwrite($fp, $tipo_pedido.';');
            fwrite($fp, $pedido.';');
            fwrite($fp, $tipo_frete.';');
            fwrite($fp, $condicao);
            fwrite($fp, "\r\n");
			
			if($tipo_pedido == 350) {
				$preco = " tbl_pedido_item.preco ";
			}else{
				$preco = "  (tbl_pedido_item.preco *  case when tbl_pedido.desconto isnull or tbl_pedido.desconto=0 then 1 else (100-tbl_pedido.desconto)/100 end ) AS preco ";
			}
            $sql_pecas = "SELECT  tbl_os.os, tbl_pedido_item.pedido_item        AS pedido_item,
                                  tbl_pedido.pedido                  AS pedidoX,
                                  tbl_peca.referencia                      AS peca_referencia,
                                  tbl_peca.peca,
								  (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) AS qtde ,
								  $preco 
                                  FROM tbl_pedido
                                  JOIN tbl_pedido_item USING(pedido)
                                  JOIN tbl_peca        USING(peca)

                                  left join tbl_os_item on tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                                  left join tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                                  left join tbl_os on tbl_os.os = tbl_os_produto.os

                                  WHERE tbl_pedido.pedido = $pedido
                                  AND tbl_pedido.fabrica = $login_fabrica";

            $res_pecas = pg_query($con, $sql_pecas);
            $tot_pecas = pg_num_rows($res_pecas);
            $msg_erro  .= pg_errormessage($con);

            if (!empty($msg_erro)) {
                throw new Exception($msg_erro);
            }

            for ($x = 0; $x < $tot_pecas; $x++) {

                $peca_referencia = trim(pg_fetch_result($res_pecas, $x, 'peca_referencia'));
                $pedido_item     = trim(pg_fetch_result($res_pecas, $x, 'pedido_item'));
                $qtde            = trim(pg_fetch_result($res_pecas, $x, 'qtde'));
                $peca            = trim(pg_fetch_result($res_pecas, $x, 'peca'));
                $preco           = trim(pg_fetch_result($res_pecas, $x, 'preco'));
                $os              = trim(pg_fetch_result($res_pecas, $x, 'os'));

                $peca_referencia_original = $peca_referencia;
                
                $sql_estoque_Einhell = "SELECT qtde from tbl_estoque_posto WHERE posto = $posto_einhell and peca = $peca and fabrica = $login_fabrica ";

                $res_estoque_Einhell = pg_query($con, $sql_estoque_Einhell);
                $msg_erro .= pg_errormessage($con);
                  if(pg_num_rows($res_estoque_Einhell) > 0){
                      $qtde_estoque = (int)pg_fetch_result($res_estoque_Einhell, 0, 'qtde');
                  }

                if( (pg_num_rows($res_estoque_Einhell) <= 0) or $qtde_estoque == 0 ){

                    $sql_alternativa = "SELECT para as referencia_alternativa, peca_para 
                                        FROM tbl_peca_alternativa 
                                        inner join tbl_estoque_posto on tbl_estoque_posto.peca = tbl_peca_alternativa.peca_para
                                        WHERE tbl_peca_alternativa.peca_de = $peca and tbl_peca_alternativa.fabrica = $login_fabrica and tbl_estoque_posto.posto = $posto_einhell and tbl_estoque_posto.qtde > 0 ";
                    $res_alternativa = pg_query($con, $sql_alternativa);
                    $msg_erro .= pg_errormessage($con);

                    if(pg_num_rows($res_alternativa)>0){
                      $peca_referencia  = pg_fetch_result($res_alternativa, 0, 'referencia_alternativa');
                      $peca_para        = pg_fetch_result($res_alternativa, 0, 'peca_para');

                      $upd_peca_alternativa = " UPDATE tbl_pedido_item set peca_alternativa = $peca_para, obs = 'A peça $peca_referencia_original foi alterada para $peca_referencia pelo fabricante.' WHERE pedido_item = $pedido_item ";
                      $res_peca_alternativa = pg_query($con, $upd_peca_alternativa);
                      $msg_erro .= pg_errormessage($con);


                      $sql_comunicado = "INSERT INTO tbl_comunicado (obrigatorio_site, tipo, ativo, mensagem, fabrica, posto) VALUES ('t', 'Comunicado', 't', 'Prezado assistente. <br> Para o pedido <b>$pedido</b>  substituímos a peça $peca_referencia_original pela peça $peca_referencia pois ambas apresentam as mesmas características, qualquer dúvida estaremos à disposição', '$login_fabrica', '$posto_pedido') ";
                      $res_comunicado = pg_query($con, $sql_comunicado);
                      $msg_erro .= pg_errormessage($con);

                    }
                }           
                
                if (empty($os_item)) {
                    $os_item = "000000000";
                }

                if (empty($os)) {
                    $os = "000000000";
                }

                $preco = number_format($preco, 2);

                fwrite($fi, $peca_referencia.';');
                fwrite($fi, $qtde.';');
                fwrite($fi, $pedido.';');
                fwrite($fi, $pedido_item.';');
                fwrite($fi, $preco.';');
                fwrite($fi, $os);
                fwrite($fi, "\r\n");


			}

			$sql_up = "UPDATE tbl_pedido
                              SET exportado     = CURRENT_TIMESTAMP,
							  status_pedido = 9
						WHERE pedido        = $pedido
						  AND fabrica       = $login_fabrica
						  AND exportado     IS NULL ";
			$res_up   = pg_query($con, $sql_up);
			$msg_erro .= pg_errormessage($con);

			if (!empty($msg_erro)) {
				throw new Exception($msg_erro);
			}
        }
         
        fclose($fp);
        fclose($fi);

        $local_file = $file_pedido;
        $server_file = "Telecontrol/Received Order/telecontrol-pedido.txt";

        $local_file2 = $file_pedido_item;
        $server_file2 = "Telecontrol/Received Order Lines/telecontrol-pedido-item.txt";

        $conn_id = ftp_connect($ftp_server);
  
        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        ftp_pasv($conn_id, true);
        
        ftp_put($conn_id, $server_file, $local_file, FTP_BINARY);
        ftp_put($conn_id, $server_file2, $local_file2, FTP_BINARY);

        ftp_close($conn_id);

      }

      if (!empty($msg_erro)) {

          $msg = 'Script: '.__FILE__.'<br />' . $msg_erro; 
          Log::envia_email($vet, APP, $msg);     

          fwrite($elog, 'Erro');                      
          fwrite($elog, $msg);           

      } else {

          Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));
          fwrite($nlog, 'Log'); 
          fwrite($nlog, $msg);
      }

      fclose($nlog);
      fclose($elog);

      if (file_exists($file_pedido) and (filesize($file_pedido) > 0)) {

              date_default_timezone_set('America/Sao_Paulo');
              $data_arquivo = date('dmy-h:i:s');

              copy($file_pedido, $dir . '/pedido-' . $data_arquivo . '.txt');
              copy($file_pedido_item, $dir . '/pedido-item-' . $data_arquivo . '.txt');
      }    

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);
}
