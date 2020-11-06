<?php

/*
* Includes
*/

function formataData($data,$hora=null){
    $y = substr($data,0,4);
    $m = substr($data,4,2);
    $d = substr($data,6,2);

    $novaData = "$y-$m-$d";

    if(strlen($hora) > 0){
        $h = substr($hora,0,2);
        $m = substr($hora,2,2);
        $s = substr($hora,4,2);

        $novaData .= " $h:$m:$s";
    }
    return $novaData;
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

    $ped_in = "/tmp/roca/ftp-pasta-in/pedidos-recebidos";
    if (!is_dir($ped_in)) {
        if (!mkdir($ped_in, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $ped_in");
        }
    }

    $ped_confirmados_telecontrol = '/tmp/roca/telecontrol-ped-confirmados';
    if(!is_dir($ped_confirmados_telecontrol)){
        if (!mkdir($ped_confirmados_telecontrol,0777,true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $ped_confirmados_telecontrol");
        }
    }
    
    $local_ped = "$ped_in/";
    $server_file = "in/";
    $arquivos = ftp_nlist($conn_id,"in");
    $log_erro = array();
    $log_success =  array();
    
    foreach ($arquivos as $key => $value) { 
        $pos = strpos( $value, "PED" );
        if ($pos === false) {
            continue;
        } else {
            if (ftp_get($conn_id, $local_ped.$value, $server_file.$value, FTP_BINARY)){
                #ftp_delete($conn_id, "$server_file$value");
            } 
        }
    }
    
    $diretorio_origem = $local_ped;

    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    $dir = opendir($diretorio_origem);
    
    if ($dir){
        while(false !== ($arquivo = readdir($dir))) {
            unset($arq_log);
            unset($err_log);
	    unset($nome_arquivo);

            $nome_arquivo  = explode(".", $arquivo);
    	    $nome_arquivo = $nome_arquivo[0];

            if(in_array($arquivo,array('.','..'))) continue;
            
            $arq_log = $log_dir . '/importa-confirmacao-pedido-success-' .$nome_arquivo.'-'. $now . '.log';
            $err_log = $log_dir . '/importa-confirmacao-pedido-err-' .$nome_arquivo.'-'. $now . '.log';

            unset($log_erro);
            unset($log_success);
            $dados_conteudo = array();
            
            if (file_exists($diretorio_origem.$arquivo) and (filesize($diretorio_origem.$arquivo) > 0)) {
                $conteudo = file_get_contents($diretorio_origem.$arquivo);
                $conteudo = explode("\n", $conteudo);

                $conteudo = array_filter($conteudo);
                
                foreach ($conteudo as $key => $value) {
                    $dados_conteudo[] = $value;
                }
                
                foreach($dados_conteudo AS $key => $value){
                    $itens = explode("|",$value);
                    unset($pedido_sap);
                    
                    $pedidoTc           = trim($itens[1]);
                    $data               = trim($itens[2]);
                    $hora               = trim($itens[3]);
                    $tipo_mensagem      = trim($itens[5]);
                    $grupo_mensagem     = trim($itens[6]);
                    $cod_mensagem       = trim($itens[7]);
                    $mensagem           = trim($itens[8]);
                    $mensagem_v1        = trim($itens[11]);
                    
                    if ($tipo_mensagem == "S") {
                        if ($grupo_mensagem == "V1" AND $cod_mensagem == "311"){
                            $pedido_sap = trim($itens[12]);
                        }else{
                            $pedido_sap = "";
                        }
                    } else if ($grupo_mensagem == "ZPV_TELECONTROL") {
                        
                        $pos = strpos( $mensagem, "já gerou OV" );

                        if ($pos){
                            $pedido_sap = trim($itens[12]);
                        }else{
                            $pedido_sap = "";
                        }
                    }

                    $dadosPedido[$pedidoTc][$key] = array("data" => $data, 
                                                    "hora" => $hora, 
                                                    "tipo_mensagem" => $tipo_mensagem, 
                                                    "grupo_mensagem" => $grupo_mensagem, 
                                                    "cod_mensagem" => $cod_mensagem, 
                                                    "mensagem" => $mensagem,
                                                    "mensagem_v1" => $mensagem_v1,
                                                    "pedido_sap" => $pedido_sap
                                                );
                }
                
                foreach($dadosPedido AS $key => $value){
                    $sql = "SELECT pedido, status_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$key}";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) == 0){
                        $log_erro[] = "ARQUIVO: $nome_arquivo PEDIDO NÃO ENCONTRADO NO TELECONTROL - Pedido {$key} - SQL - {$sql}";
                        continue;
                    }else{
                        $pedido = pg_fetch_result($res, 0, 'pedido');
                        $status_pedido = pg_fetch_result($res, 0, 'status_pedido');

                        #if($status_pedido <> 9) continue;
                        
                        for ($i=0; $i < count($value); $i++) { 
                            if($i == 0 AND $value[$i]["tipo_mensagem"] == "I" AND $value[$i]["grupo_mensagem"] != "ZPV_TELECONTROL"){
                                continue(2);
                            }else if($value[$i]["grupo_mensagem"] == "ZPV_TELECONTROL" AND $value[$i]["tipo_mensagem"] == "I"){
                                continue;
                            }
                            
                            if(strlen($value[$i]["data"]) > 0){
                                $recebido_fabrica = formataData($value[$i]["data"],$value[$i]["hora"]);
                            }

			                $recebido_fabrica = (!empty($recebido_fabrica)) ? $recebido_fabrica : date('Y-d-m'); 
                            if($value[$i]["tipo_mensagem"] == "S" AND !empty($value[$i]['pedido_sap'])){
                                $sql = "UPDATE tbl_pedido SET status_pedido = 2, pedido_cliente = '{$value[$i]['pedido_sap']}', recebido_fabrica = '{$recebido_fabrica}' WHERE pedido = {$pedido}";
                                $res = pg_query($con,$sql);
                            
                                if (pg_last_error()) {
                                    $log_erro[] = "#1ARQUIVO: $nome_arquivo LINHA ERRO AO ATUALIZAR STATUS DO PEDIDO - PEDIDO: $key";
                                    continue;
                                }
                            } else if ($value[$i]["tipo_mensagem"] == "E" AND !empty($value[$i]['pedido_sap'])) {
                                $sql = "UPDATE tbl_pedido SET status_pedido = 2, pedido_cliente = '{$value[$i]['pedido_sap']}', recebido_fabrica = '{$recebido_fabrica}' WHERE pedido = {$pedido}";
                                $res = pg_query($con,$sql);
                            
                                if (pg_last_error()) {
                                    $log_erro[] = "#2ARQUIVO: $nome_arquivo LINHA ERRO AO ATUALIZAR STATUS DO PEDIDO - PEDIDO: $key";
                                    continue;
                                }
                            }else{
                                $sql = "UPDATE tbl_pedido SET obs = obs||'{$value[$i]["mensagem"]}', recebido_fabrica = '{$recebido_fabrica}' WHERE pedido = {$pedido}";
                                $res = pg_query($con,$sql);
                            
                                if (pg_last_error()) {
                                    $log_erro[] = "#3ARQUIVO: $nome_arquivo LINHA ERRO AO ATUALIZAR STATUS DO PEDIDO - PEDIDO: $key".pg_last_error();
                                    continue;
                                }
                            }
                        }
                    }
                }
                
                ftp_chmod($conn_id, 0777, "in/bkp");
                ftp_put($conn_id, "in/bkp/$now-$arquivo","$local_ped/$arquivo", FTP_BINARY);
            
                if (count($log_erro) > 1){
                    $elog = fopen($err_log, "w");
                    $dados_log_erro = implode("\n", $log_erro);
                    fwrite($elog, $dados_log_erro);
                    fclose($elog);
        		}else{
        			ftp_delete($conn_id, "$server_file$arquivo");
        		}

                if (filesize($err_log) > 0) {
			        $data_arq_enviar = date('Ymd_His');
			        $data_arq_process = date('Ymd_His');
                    $cmds = "cp $log_dir/importa-confirmacao-pedido-err-$nome_arquivo-$now.log $log_dir/$data_arq_enviar-importa-confirmacao-pedido-err-$nome_arquivo.txt";
                    system($cmds, $retorno);
                    
                    if ($retorno == 0){
                        $manda_email = true;
                        $arquivos_email[] = "$log_dir/$data_arq_enviar-importa-confirmacao-pedido-err-$nome_arquivo.txt";
                    }
                }
                system("mv $diretorio_origem$arquivo /tmp/$fabrica_nome/telecontrol-ped-confirmados/$nome_arquivo-$data_arq_process-ok.txt");
            }
        }
        
        if ($manda_email === true){
            if (count($arquivos_email) > 0){
                $zip = "zip $log_dir/$data_arq_enviar-importa-confirmacao-pedido-err.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
                system($zip, $retorno);
            }
            
            require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
            $assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de pedido ') . date('d/m/Y');
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            $mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
            $mail->Subject = $assunto;
            $mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
            
            if (count($arquivos_email) > 0){
                $mail->AddAttachment("$log_dir/$data_arq_enviar-importa-confirmacao-pedido-err.zip", "$data_arq_enviar-importa-confirmacao-pedido-err.zip");
            }
          
            if (!$mail->Send()) {
                echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
            } else {
                if (count($arquivos_email) > 0){
                    unlink("$log_dir/$data_arq_enviar-importa-confirmacao-pedido-err.zip");
                    foreach ($arquivos_email as $key => $value) {
                        unlink($value);
                    }
                }
            }
        }
    }
    ftp_close($conn_id);
//     if(file_exists($arquivo)){

//         if(filesize($arquivo) > 0){

//             $dados = file_get_contents($arquivo);
//             $dados = explode("\n",$dados);

//             foreach($dados AS $key => $value){

//                 $itens = explode("|",$value);
//                 $pedidoTc           = trim($itens[1]);
//                 $data               = trim($itens[2]);
//                 $hora               = trim($itens[3]);
//                 $tipo_mensagem      = trim($itens[5]);
//                 $grupo_mensagem     = trim($itens[6]);
//                 $cod_mensagem       = trim($itens[7]);
//                 $mensagem           = trim($itens[8]);
//                 $mensagem_v1        = trim($itens[11]);

//                 $dadosPedido[$pedidoTc][$key] = array("data" => $data, 
//                                                 "hora" => $hora, 
//                                                 "tipo_mensagem" => $tipo_mensagem, 
//                                                 "grupo_mensagem" => $grupo_mensagem, 
//                                                 "cod_mensagem" => $cod_mensagem, 
//                                                 "mensagem" => $mensagem,
//                                                 "mensagem_v1" => $mensagem_v1);
//             }

//             foreach($dadosPedido AS $key => $value){
//                 $sql = "SELECT pedido, status_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$key}";
//                 $res = pg_query($con,$sql);

//                 if(pg_num_rows($res) == 0){
//                     $log_cliente[] = "Pedido {$key} não encontrado";
//                     break;
//                 }else{
//                     $pedido = pg_fetch_result($res, 0, 'pedido');
//                     $status_pedido = pg_fetch_result($res, 0, 'status_pedido');

//                     if($status_pedido <> 9) continue;

//                     for ($i=0; $i < count($value); $i++) { 
                       
//                         if($i == 0 AND $value[$i]["tipo_mensagem"] == "I" AND $value[$i]["grupo_mensagem"] != "ZPV_TELECONTROL"){
//                             continue(2);
//                         }else if($value[$i]["grupo_mensagem"] == "ZPV_TELECONTROL" AND $value[$i]["tipo_mensagem"] == "I"){
//                             continue;
//                         }

//                         if(strlen($value[$i]["data"]) > 0){
//                             $recebido_fabrica = formataData($value[$i]["data"],$value[$i]["hora"]);
//                         }else{
//                             $recebido_fabrica = null;
//                         }

//                         if($value[$i]["tipo_mensagem"] == "S"){

//                             $sql = "UPDATE tbl_pedido SET status_pedido = 2, recebido_fabrica = '{$recebido_fabrica}' WHERE pedido = {$pedido}";
//                             $res = pg_query($con,$sql);

//                         }else{
//                             $sql = "UPDATE tbl_pedido SET obs = '{$value[$i]["mensagem"]}', recebido_fabrica = '{$recebido_fabrica}' WHERE pedido = {$pedido}";
//                             $res = pg_query($con,$sql);
//                         }
//                     }
//                 }
//             }
//         }else{
//             throw new Exception("Arquivo sem conteúdo");
//         }

//     }else{
//         throw new Exception("Arquivo de Confirmação de Pedido não encontrado");
//     }
// print_r($log_erro);
// print_r($log_cliente);
}catch(Exception $e){
    ftp_close($conn_id);
    echo $e->getMessage();
}
