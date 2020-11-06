<?php

error_reporting(E_ALL ^ E_NOTICE);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$login_fabrica = 88;
	$fabrica_nome  = 'orbis';
	
	$phpCron = new PHPCron($login_fabrica, __FILE__); 
	$phpCron->inicio();

    define('APP', 'Exporta Pedido - '.$fabrica_nome);

    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('helpdesk@telecontrol.com.br');
    $vet['log']     = 1;

    $sql = "SELECT TO_CHAR(tbl_pedido.data,'YYYYMMDD')      AS emissao,
                   tbl_posto_fabrica.codigo_posto           AS codigo_posto,
				   tbl_posto_fabrica.desconto                              ,
				   tbl_pedido.pedido                        AS pedido,
                   tbl_pedido.seu_pedido                    AS seu_pedido,
                   UPPER(tbl_tipo_pedido.codigo)            AS tipo_pedido ,
                   tbl_condicao.codigo_condicao             AS condicao,
                   tbl_pedido.tipo_frete                    AS tipo_frete,
				   tbl_pedido.valor_frete ,
				   tbl_transportadora.nome                  AS transportadora
              FROM tbl_pedido 
              JOIN tbl_posto_fabrica ON tbl_pedido.posto    = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
              JOIN tbl_posto         ON tbl_posto.posto     = tbl_pedido.posto
              JOIN tbl_tipo_pedido   USING (tipo_pedido)
              JOIN tbl_tabela        ON tbl_pedido.tabela   = tbl_tabela.tabela
              JOIN tbl_condicao      ON tbl_pedido.condicao = tbl_condicao.condicao
			  LEFT JOIN tbl_transportadora ON tbl_pedido.transportadora = tbl_transportadora.transportadora
             WHERE tbl_pedido.fabrica          = $login_fabrica 
               AND tbl_pedido.recebido_fabrica IS NULL
               AND tbl_pedido.posto            <> 6359
               AND tbl_pedido.status_pedido    <> 14
               AND tbl_pedido.finalizado       NOTNULL
               AND tbl_pedido.exportado        IS NULL 
			   AND tbl_pedido.exportado_manual IS TRUE";

    $res      = pg_query($con, $sql);
    $numrows  = pg_num_rows($res);
    $msg_erro = pg_errormessage($con);
    $data     = date('Y-m-d-His');

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

            $pedido         = trim(pg_fetch_result($res, $i, 'pedido'));
			$seu_pedido     = trim(pg_fetch_result($res, $i, 'seu_pedido'));
            $emissao        = trim(pg_fetch_result($res, $i, 'emissao'));
            $codigo_posto   = trim(pg_fetch_result($res, $i, 'codigo_posto'));
			$desconto       = trim(pg_fetch_result($res, $i, 'desconto'));
            $tipo_pedido    = trim(pg_fetch_result($res, $i, 'tipo_pedido'));
            $condicao       = trim(pg_fetch_result($res, $i, 'condicao'));
            $tipo_frete     = trim(pg_fetch_result($res, $i, 'tipo_frete'));
			$valor_frete    = trim(pg_fetch_result($res, $i, 'valor_frete'));
			$transportadora = trim(pg_fetch_result($res, $i, 'transportadora'));

			$tipo_frete = ($tipo_frete == "NOR") ? "NORMAL" : "URGENTE";
			
			$pedido_aux = (empty($seu_pedido)) ? $pedido : $seu_pedido;
            /**
             *
             *  Layout:
             *
             *  Emissao | Código Posto | Tipo Pedido | Pedido | Condicao | Tipo Frete | Valor Frete | Transportadora
             *
             */

            fputs($fp, '01;');
            fputs($fp, $emissao.';');
            fputs($fp, $codigo_posto.';');
            fputs($fp, $tipo_pedido.';');
            fputs($fp, $pedido_aux.';');
            fputs($fp, $condicao.';');
            fputs($fp, $tipo_frete.';');
			fputs($fp, $valor_frete.';');
			fputs($fp, $transportadora);
            fputs($fp, "\r\n");

            $sql_pecas = "SELECT tbl_pedido_item.pedido_item         AS pedido_item,
                                 tbl_pedido.pedido                   AS pedidoX,
                                 tbl_peca.referencia                      AS peca_referencia,
                                 tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada AS qtde,
                                 tbl_pedido_item.preco 
                            FROM tbl_pedido
                            JOIN tbl_pedido_item USING(pedido)
                            JOIN tbl_peca        USING(peca)
                            WHERE tbl_pedido.pedido = $pedido
							AND   tbl_pedido.seu_pedido = '$seu_pedido'
							AND   tbl_pedido.fabrica = $login_fabrica";

            $res_pecas = pg_query($con, $sql_pecas);
            $tot_pecas = pg_num_rows($res_pecas);
            $msg_erro  = pg_errormessage($con);

           
            for ($x = 0; $x < $tot_pecas; $x++) {

                $peca_referencia = trim(pg_fetch_result($res_pecas, $x, 'peca_referencia'));
                $pedido_item     = trim(pg_fetch_result($res_pecas, $x, 'pedido_item'));
                $qtde            = trim(pg_fetch_result($res_pecas, $x, 'qtde'));
                $preco           = trim(pg_fetch_result($res_pecas, $x, 'preco'));
		$preco 		 = round($preco,2);

                
                fputs($fp, '02;');
                fputs($fp, $peca_referencia.';');
                fputs($fp, $qtde.';');
                fputs($fp, $pedido_aux.';');
                fputs($fp, $pedido_item.';');
                fputs($fp, $preco);
                fputs($fp, "\r\n");

            }

			$sql_up = "UPDATE tbl_pedido 
                              SET exportado     = CURRENT_TIMESTAMP, 
                                  status_pedido = 2 
                            WHERE tbl_pedido.pedido = $pedido
							AND   tbl_pedido.seu_pedido = '$seu_pedido' 
                            AND fabrica       = $login_fabrica 
                            AND exportado     IS NULL ";

			$res_up   = pg_query($con, $sql_up);
			$msg_erro = pg_errormessage($con);
				
			

        }

        if (!empty($msg_erro)) {

            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($vet, APP, $msg);

        } else {

            Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y-H-m'));

        }

        fclose($fp);

        if (file_exists($file)) {

           # system("cp $file /home/orbis/telecontrol-$fabrica_nome/pedido_$fabrica_nome.txt");
            system("mv $file $dir/pedido_$data.txt");
			
			$ftp_server = "ftp.telecontrol.com.br";
			$ftp_user_name = "orbis";
			$ftp_user_pass = "orb11is";

			$local_file = "$dir/pedido_$data.txt";
			$server_file = "/telecontrol-orbis/pedido_$data.txt";

			$conn_id = ftp_connect($ftp_server);
			$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
			ftp_pasv($conn_id, true);
			ftp_put($conn_id, $server_file, $local_file, FTP_BINARY);  
			ftp_close($conn_id);

        }

    }
	
	$phpCron->termino();

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

