<?php

/*
* Includes
*/

function formataData($data){

    $y = substr($data,0,4);
    $m = substr($data,4,2);
    $d = substr($data,6,2);

    $novaData = "$y-$m-$d";
    return $novaData;

}

function formataValor($valor){

    $novoValor = str_replace(".", "", $valor);
    $novoValor = str_replace(",", ".", $novoValor);
    return $novoValor;
}

try {
    include 'connect-ftp.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_178/Os.php';

    date_default_timezone_set('America/Sao_Paulo');
    $login_fabrica = 178;
    $fabrica_nome = 'roca';
    $data = date('d-m-Y');

    $log_dir = '/tmp/' . $fabrica_nome . '/logs';
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
        }
    }

    $snf_in = "/tmp/roca/ftp-pasta-in/snf";
    if (!is_dir($snf_in)) {
        if (!mkdir($snf_in, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $snf_in");
        }
    }
    
    $snf_importados = '/tmp/roca/telecontrol-snf-importados';
    if(!is_dir($snf_importados)){
        if (!mkdir($snf_importados,0777,true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $snf_importados");
        }
    }

    $local_snf = "$snf_in/";
    $server_file = "in/";
    $arquivos = ftp_nlist($conn_id,"in");
    $log_erro = array();
    $log_success =  array();

    foreach ($arquivos as $key => $value) { 
        $pos = strpos( $value, "SNF" );
        if ($pos === false) {
            continue;
        } else {
           if (ftp_get($conn_id, $local_snf.$value, $server_file.$value, FTP_BINARY)){
                ftp_delete($conn_id, "$server_file$value");
	       } 
        }
    }

    $diretorio_origem = $local_snf;
    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    $dir = opendir($diretorio_origem);

    if ($dir){
        while(false !== ($arquivo = readdir($dir))) {
            $nome_arquivo  = explode(".", $arquivo);
            $nome_arquivo = $nome_arquivo[0];

	        if(in_array($arquivo,array('.','..'))) continue;

            $arq_log = $log_dir . '/importa-snf-success-' .$nome_arquivo.'-'. $now . '.log';
            $err_log = $log_dir . '/importa-snf-err-' .$nome_arquivo.'-'. $now . '.log';
            $dados_conteudo = array();
            unset($log_erro);
            unset($log_success);

            if (file_exists($diretorio_origem.$arquivo) and (filesize($diretorio_origem.$arquivo) > 0)) {
                $conteudo = file_get_contents($diretorio_origem.$arquivo);
                $conteudo = explode("\n", $conteudo);
                $conteudo = array_filter($conteudo);

                foreach ($conteudo as $key => $value) {
                    $dados_conteudo[] = $value;
                }

                $log_erro[] = " ==== LOG ERRO INÍCIO: ".date("H:i")." ==== ";

                foreach($dados_conteudo AS $key => $value){
                    $itens = explode("|",$value);
                    
                    if($itens[0] == "CB"){
                        $total_nota         = 0;
                        $pedidoTc           = trim($itens[1]);
                        $emissao            = formataData(trim($itens[2]));

                        $sql = "SELECT pedido,posto,status_pedido 
                                FROM tbl_pedido 
                                WHERE fabrica = {$login_fabrica} 
                                AND pedido = {$pedidoTc}";
                        $res = pg_query($con,$sql);

                        if(pg_num_rows($res) > 0){
                            $pedido = pg_fetch_result($res,0,'pedido');
                            $posto  = pg_fetch_result($res,0,'posto');
                            $status_pedido = pg_fetch_result($res, 0, 'status_pedido');
			    
            			    if($status_pedido == 14){
            				    continue;
            			    }
                        }else{
                            $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PEDIDO NÃO ENCONTRADO NO TELECONTROL- PEDIDO. $pedidoTc";
                            continue;   
                        }
                    }else if($itens[0] == "NF" AND !empty($pedido)){
                    
                        $pedidoTCItem       = trim($itens[1]);
                        $nota_fiscal        = trim($itens[2]);
                        $serie_nota         = trim($itens[3]);
                        $item               = trim($itens[4]);
                        $referencia_item    = trim($itens[5]);
                        #$qtde_item          = formataValor(trim($itens[6]));
                        $qtde_item          = trim($itens[6]);
                        $preco_item         = formataValor(trim($itens[7]));
                        $total_item         = formataValor(trim($itens[8]));
                        $cfop               = trim($itens[9]);
                        $pedido_item        = trim($itens[12]);
                        $total_nota += $total_item;

                        if (empty($nota_fiscal)){
                            $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO GRAVAR FATURAMENTO NO TELECONTROL - PEDIDO. $pedido NOTA FISCAL NÃO INFORMADA";
                            continue;
                        }

                        if($pedidoTCItem == $pedidoTc){

                            $sql = "SELECT faturamento 
                                    FROM tbl_faturamento 
                                    WHERE fabrica = {$login_fabrica} 
                                    AND nota_fiscal = '{$nota_fiscal}' 
                                    AND serie = '{$serie_nota}'";
                            $res = pg_query($con,$sql);

                            if(pg_num_rows($res) > 0){
                                $faturamento = pg_fetch_result($res,0,'faturamento');
                            }else{
                                $sql = "INSERT INTO tbl_faturamento(
                                            fabrica,
                                            posto,
                                            emissao,
                                            saida,
                                            nota_fiscal,
                                            serie,
                                            total_nota
                                        ) VALUES(
                                            $login_fabrica,
                                            $posto,
                                            '{$emissao}',
                                            '{$emissao}',
                                            '{$nota_fiscal}',
                                            '{$serie_nota}',
                                            0
                                        ) RETURNING faturamento";
                                $resIns = pg_query($con,$sql);
                                        
                                if(strlen(pg_last_error()) == 0){
                                    $faturamento = pg_fetch_result($resIns,0,'faturamento');
                                }else{
                                    $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO GRAVAR FATURAMENTO NO TELECONTROL  - PEDIDO. $pedido";
                                    continue;
                                }
                            }

                            $sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$referencia_item}'";
                            $res = pg_query($con,$sql);
							$peca_pedida = "";
							$valor_peca_pedida = ""; 
							$atualiza_item = false;

                            if(pg_num_rows($res) == 0){
                                $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PEÇA DO PEDIDO NÃO CADASTRADA NO TELECONTROL- PEÇA REF. $referencia_item PEDIDO. $pedidoTc";
                                continue;
                            }else{
                                $peca = pg_fetch_result($res,0,'peca');

                                $sql = "SELECT pedido_item,(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS qtde_pendente, peca, tbl_peca.referencia
                                        FROM tbl_pedido_item
										JOIN tbl_peca using(peca)
                                        WHERE pedido = {$pedido}
                                        AND pedido_item = {$pedido_item}";
                                $res = pg_query($con,$sql);

                                if(pg_num_rows($res) == 0){
                                    $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ITEM NÃO CONSTA NO PEDIDO - PEÇA REF. $referencia_item PEDIDO. $pedidoTc";
                                    continue;
                                }else{

                                    $qtde_pendente = pg_fetch_result($res,0,'qtde_pendente');
                                    $pedido_item = pg_fetch_result($res,0,'pedido_item');
									$peca_original = pg_fetch_result($res,0,'peca');
									$referencia_pedido = pg_fetch_result($res,0,'referencia');
									if($peca_original <> $peca) {
										$peca_atualiza = $peca_original;
										$atualiza_item = true;
										$peca_pedida = ",peca_pedida ";
										$valor_peca_pedida = " , $peca_original"; 
									}else{
										$peca_atualiza = $peca;
									}
                                    if($qtde_item > $qtde_pendente){
                                        $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - QUANTIDADE ENVIADA DA PEÇA PARA O PEDIDO É MAIOR DO QUE A QUANTIDADE PENDENTE - PEÇA REF. $referencia_item PEDIDO. $pedidoTc";
                                        continue;
                                    }else{
                                            $sql = "INSERT INTO tbl_faturamento_item (
                                                                                    faturamento,
                                                                                    peca,
                                                                                    qtde,
                                                                                    preco,
                                                                                    cfop,
                                                                                    pedido,
																					pedido_item
																					$peca_pedida
                                                                                ) VALUES(
                                                                                    {$faturamento},
                                                                                    {$peca},
                                                                                    {$qtde_item},
                                                                                    {$preco_item},
                                                                                    '{$cfop}',
                                                                                    {$pedido},
																					{$pedido_item}
																					$valor_peca_pedida
                                                                                ) RETURNING faturamento_item";
                                        $resIns = pg_query($con,$sql);

                                        if(strlen(pg_last_error()) > 0){
                                            $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO INSERIR ITEM DO PEDIDO- PEDIDO: $pedidoTc";
                                            continue;
                                        }else{
                                            $faturamento_item = pg_fetch_result($resIns, 0, 'faturamento_item');
                                            $arrayItens[$item] = $faturamento_item;

                                            $sql = "SELECT fn_atualiza_pedido_item({$peca_atualiza}, {$pedido}, {$pedido_item}, {$qtde_item})";
                                            $res = pg_query($con, $sql);

											if($atualiza_item) {	
												$sql = "SELECT fn_atualiza_pedido_item_peca($login_fabrica, $pedido_item, {$peca_atualiza}, 'Item $referencia_pedido atendido por $referencia_item')";
												$res = pg_query($con, $sql);
											}

                                            if (strlen(pg_last_error()) > 0) {
                                                $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR PEÇA DO PEDIDO - PEDIDO: $pedidoTc";
                                                continue;
                                            }else{

                                                $sql = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido})";
                                                $res = pg_query($con, $sql);

                                                if (strlen(pg_last_error()) > 0) {
                                                    $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR STATUS DO PEDIDO - PEDIDO: $pedidoTc";
                                                    continue;
                                                }
                                            }
                                        }
                                    }
                                }
                            }    
                        }
                    }else if($itens[0] == "TX" AND !empty($pedido)){

                        $nota               = trim($itens[1]);
                        $item_imposto       = trim($itens[2]);
                        $tipo_imposto       = trim($itens[3]);
                        $valor_base         = formataValor(trim($itens[4]));
                        $taxa               = formataValor(trim($itens[5]));
                        $total_imposto      = formataValor(trim($itens[6]));

                        if(!empty($arrayItens[$item_imposto])){

                            if($tipo_imposto = "ICMS"){
                                $sql = "UPDATE tbl_faturamento_item SET base_icms = {$valor_base}, valor_icms = {$total_imposto}, aliq_icms = {$taxa} WHERE faturamento_item = {$arrayItens[$item_imposto]}";
                            }else{
                                $sql = "UPDATE tbl_faturamento_item SET base_ipi = {$valor_base}, valor_ipi = {$total_imposto}, aliq_ipi = {$taxa} WHERE faturamento_item = {$arrayItens[$item_imposto]}";
                            }

                            $res = pg_query($con,$sql);

                            if(strlen(pg_last_error()) > 0){
                                $log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR IMPOSTOS - PEDIDO: $pedidoTc";
                                continue;
                            }
                        }
                    }

                    $sql_tipo_posto = "
                        SELECT tbl_posto_fabrica.posto
                        FROM tbl_posto_fabrica
                        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
                        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                        AND tbl_posto_fabrica.posto = {$posto}
                        AND tbl_tipo_posto.posto_interno IS TRUE";
                    $res_tipo_posto = pg_query($con, $sql_tipo_posto);

                    if (pg_num_rows($res_tipo_posto) > 0){
                        $sql_os = "
                            SELECT 
                                tbl_os.os,
                                tbl_os.sua_os
                            FROM tbl_os
                            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                            WHERE tbl_os.fabrica = {$login_fabrica}
                            AND tbl_os_item.pedido_item = $pedido_item";
                        $res_os = pg_query($con, $sql_os);

                        if (pg_num_rows($res_os) > 0){
                            $xos = pg_fetch_result($res_os, 0, "os");
                            if (file_exists("../../classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
                                include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
                                $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
                                $classOs = new $className($login_fabrica, $xos);
                                
                                $classOs->finaliza($con);
                            }
                        }
                    } 
                }

                ftp_chmod($conn_id, 0777, "in/bkp");
                ftp_put($conn_id, "in/bkp/$now-$arquivo","$local_snf/$arquivo", FTP_BINARY);
            
                if (count($log_erro) > 1){
                    $elog = fopen($err_log, "w");
                    $dados_log_erro = implode("\n", $log_erro);
                    fwrite($elog, $dados_log_erro);
                    fclose($elog);
                }

                if (filesize($err_log) > 0) {
                    $data_arq_enviar = date('dmy');
                    $cmds = "cp $log_dir/importa-snf-err-$nome_arquivo-$now.log $log_dir/importa-snf-err-$nome_arquivo-$data_arq_enviar.txt";
                    system($cmds, $retorno);
                    
                    if ($retorno == 0){
                        $manda_email = true;
                        $arquivos_email[] = "$log_dir/importa-snf-err-$nome_arquivo-$data_arq_enviar.txt";
                    }
                }
                system("mv $diretorio_origem$arquivo /tmp/$fabrica_nome/telecontrol-snf-importados/$nome_arquivo-$data_arq_enviar-ok.txt");
			    ftp_delete($conn_id, "$server_file$arquivo");
            }
        }
        
        if ($manda_email === true){
            
            if (count($arquivos_email) > 0){
                $zip = "zip $log_dir/importa-snf-err-$data_arq_enviar.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
                system($zip, $retorno);
            }
            
            require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
            $assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de faturamento ') . date('d/m/Y');
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            $mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
            $mail->Subject = $assunto;
            $mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
            
            if (count($arquivos_email) > 0){
                $mail->AddAttachment("$log_dir/importa-snf-err-$data_arq_enviar.zip", "importa-snf-err-$data_arq_enviar.zip");
            }
            
            if (!$mail->Send()) {
                echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
            } else {
                if (count($arquivos_email) > 0){
                    unlink("$log_dir/importa-snf-err-$data_arq_enviar.zip");
                    foreach ($arquivos_email as $key => $value) {
                        unlink($value);
                    }
                }
            }
        }
    }
    ftp_close($conn_id);
    ///====================///
    // if(file_exists($arquivo)){

    //     if(filesize($arquivo) > 0){

    //         $dados = file_get_contents($arquivo);
    //         $dados = explode("\n",$dados);

    //         foreach($dados AS $key => $value){
                
    //             $itens = explode("|",$value);
                    
    //             if($itens[0] == "CB"){
                    
    //                 $total_nota         = 0;
    //                 $pedidoTc           = trim($itens[1]);
    //                 $emissao            = formataData(trim($itens[2]));

    //                 $sql = "SELECT pedido,posto 
    //                         FROM tbl_pedido 
    //                         WHERE fabrica = {$login_fabrica} 
    //                         AND pedido = {$pedidoTc}";
    //                 $res = pg_query($con,$sql);

    //                 if(pg_num_rows($res) > 0){
    //                     $pedido = pg_fetch_result($res,0,'pedido');
    //                     $posto  = pg_fetch_result($res,0,'posto');
    //                 }else{
    //                     $log_cliente[] = "Pedido {$pedidoTc} não encontado\n\n\n";
    //                 }

    //             }else if($itens[0] == "NF" AND !empty($pedido)){
                    
    //                 $pedidoTCItem       = trim($itens[1]);
    //                 $nota_fiscal        = trim($itens[2]);
    //                 $serie_nota         = trim($itens[3]);
    //                 $item               = trim($itens[4]);
    //                 $referencia_item    = trim($itens[5]);
    //                 $qtde_item          = formataValor(trim($itens[6]));
    //                 $preco_item         = formataValor(trim($itens[7]));
    //                 $total_item         = formataValor(trim($itens[8]));
    //                 $cfop               = trim($itens[9]);
    //                 $pedido_item        = trim($itens[12]);

    //                 $total_nota += $total_item;

    //                 if($pedidoTCItem == $pedidoTc){

    //                     $sql = "SELECT faturamento 
    //                             FROM tbl_faturamento 
    //                             WHERE fabrica = {$login_fabrica} 
    //                             AND nota_fiscal = '{$nota_fiscal}' 
    //                             AND serie = '{$serie_nota}'";
    //                     $res = pg_query($con,$sql);

    //                     if(pg_num_rows($res) > 0){
    //                         $faturamento = pg_fetch_result($res,0,'faturamento');
    //                     }else{

    //                         $sql = "INSERT INTO tbl_faturamento(
    //                                                             fabrica,
    //                                                             posto,
    //                                                             emissao,
    //                                                             saida,
    //                                                             nota_fiscal,
    //                                                             serie,
    //                                                             total_nota
    //                                                             ) VALUES(
    //                                                                 $login_fabrica,
    //                                                                 $posto,
    //                                                                 '{$emissao}',
    //                                                                 '{$emissao}',
    //                                                                 '{$nota_fiscal}',
    //                                                                 '{$serie_nota}',
    //                                                                 0
    //                                                             ) RETURNING faturamento";
                            
    //                         $resIns = pg_query($con,$sql);
                                    
    //                         if(strlen(pg_last_error()) == 0){
    //                             $faturamento = pg_fetch_result($resIns,0,'faturamento');
    //                         }else{
    //                             $log_erro[] = "Erro INSERT tbl_faturamento \n $sql \n".pg_last_error()."\n\n\n";
    //                             $log_cliente[] = "Erro ao gravar faturamento do pedido $pedido";
    //                         }
    //                     }

    //                     $sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$referencia_item}'";
    //                     $res = pg_query($con,$sql);

    //                     if(pg_num_rows($res) == 0){
    //                         $log_cliente[] = "Peça {$referencia_item} do pedido {$pedidoTc} não cadastrada\n\n\n";
    //                     }else{
    //                         $peca = pg_fetch_result($res,0,'peca');

    //                         $sql = "SELECT pedido_item,(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS qtde_pendente
    //                                 FROM tbl_pedido_item
    //                                 WHERE pedido = {$pedido}
    //                                 AND pedido_item = {$pedido_item}
    //                                 AND peca = {$peca}";
    //                         $res = pg_query($con,$sql);

    //                         if(pg_num_rows($res) == 0){
    //                             $log_cliente[] = "Peça {$referencia_item} não consta no pedido {$pedidoTc} \n\n\n";
    //                         }else{

    //                             $qtde_pendente = pg_fetch_result($res,0,'qtde_pendente');
    //                             $pedido_item = pg_fetch_result($res,0,'pedido_item');

    //                             if($qtde_item > $qtde_pendente){
    //                                 $log_cliente[] = "Quantidade enviada da peça {$referencia_item} para o pedido {$pedidoTc} é maior do que a quantidade pendente";
    //                             }else{

    //                                 $sql = "INSERT INTO tbl_faturamento_item (
    //                                                                             faturamento,
    //                                                                             peca,
    //                                                                             qtde,
    //                                                                             preco,
    //                                                                             cfop,
    //                                                                             pedido,
    //                                                                             pedido_item
    //                                                                         ) VALUES(
    //                                                                             {$faturamento},
    //                                                                             {$peca},
    //                                                                             {$qtde_item},
    //                                                                             {$preco_item},
    //                                                                             '{$cfop}',
    //                                                                             {$pedido},
    //                                                                             {$pedido_item}
    //                                                                         ) RETURNING faturamento_item";
    //                                 $resIns = pg_query($con,$sql);

    //                                 if(strlen(pg_last_error()) > 0){
    //                                     $log_erro[] = "Erro ao inserir item \n $sql \n".pg_last_error()."\n\n\n";
    //                                 }else{
    //                                     $faturamento_item = pg_fetch_result($resIns, 0, 'faturamento_item');
    //                                     $arrayItens[$item] = $faturamento_item;

    //                                     $sql = "SELECT fn_atualiza_pedido_item({$peca}, {$pedido}, {$pedido_item}, {$qtde_item})";
    //                                     $res = pg_query($con, $sql);

    //                                     if (strlen(pg_last_error()) > 0) {
    //                                         $log_erro[] = "Erro ao atualizar peça do pedido";
    //                                     }else{

    //                                         $sql = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido})";
    //                                         $res = pg_query($con, $sql);

    //                                         if (strlen(pg_last_error()) > 0) {
    //                                             $log_erro[] = "Erro ao atualizar status do pedido";
    //                                         }
    //                                     }
    //                                 }
    //                             }
                                
    //                         }
    //                     }    
    //                 }
                    
    //             }else if($itens[0] == "TX" AND !empty($pedido)){

    //                 $nota               = trim($itens[1]);
    //                 $item_imposto       = trim($itens[2]);
    //                 $tipo_imposto       = trim($itens[3]);
    //                 $valor_base         = formataValor(trim($itens[4]));
    //                 $taxa               = formataValor(trim($itens[5]));
    //                 $total_imposto      = formataValor(trim($itens[6]));

    //                 if(!empty($arrayItens[$item_imposto])){

    //                     if($tipo_imposto = "ICMS"){
    //                         $sql = "UPDATE tbl_faturamento_item SET base_icms = {$valor_base}, valor_icms = {$total_imposto}, aliq_icms = {$taxa} WHERE faturamento_item = {$arrayItens[$item_imposto]}";
    //                     }else{
    //                         $sql = "UPDATE tbl_faturamento_item SET base_ipi = {$valor_base}, valor_ipi = {$total_imposto}, aliq_ipi = {$taxa} WHERE faturamento_item = {$arrayItens[$item_imposto]}";
    //                     }

    //                     $res = pg_query($con,$sql);

    //                     if(strlen(pg_last_error()) > 0){
    //                         $log_erro[] = "Erro ao atualizar impostos \n $sql \n".pg_last_error()."\n\n\n";
    //                     }
    //                 }
    //             }
    //         }
    //     }else{
    //         throw new Exception("Arquivo sem conteúdo");
    //     }

    // }else{
    //     throw new Exception("Arquivo de Faturamento não encontrado");
    // }
    ///====================///

}catch(Exception $e){
    ftp_close($conn_id);
    echo $e->getMessage();
}
