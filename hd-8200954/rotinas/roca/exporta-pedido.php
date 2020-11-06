<?php

$fabrica_nome = "roca";

define('APP', 'Exporta Pedido - '.$fabrica_nome);

function retira_acentos($texto){
    $array1 = array('á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç' , 'Á', 'À', 'Â', 'Ã', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï', 'Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç','º','&','%','$','?','@', '\'');
    $array2 = array('a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c' , 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C','_','_','_','_','_','_' ,'');
    return str_replace( $array1, $array2, $texto );
}

function retira_especiais($texto){
    return str_replace("-", " " ,str_replace(array(".", ","), "", $texto));
}

try {
    include 'connect-ftp.php';
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    function formataValor($valor){
        $novoValor = str_replace(".", "", $valor);
        $novoValor = str_replace(",", ".", $novoValor);
        return $novoValor;
    }

    $login_fabrica = 178;
    $vet['fabrica'] = 'roca';
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('ronald.santos@telecontrol.com.br');
    $vet['log']     = 1;
    
    $log_erro = array();
    $log_dir = '/tmp/' . $fabrica_nome . '/logs';

    $pasta = "/tmp/roca/ftp-pasta-in/saida";
    
    $local_ped_exportado = "$pasta/";
    if (!is_dir($pasta)) {
        if (!mkdir($pasta, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
        }
    }
    
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
        }
    }

    $ped_exportados = '/tmp/roca/telecontrol-ped-exportados-roca';
    if(!is_dir($ped_exportados)){
        if (!mkdir($ped_exportados,0777,true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $ped_exportados");
        }
    }

    $arquivo = "$pasta/pedidos.txt";

    $sql = "SELECT arquivo_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND exportado IS NOT NULL AND arquivo_pedido IS NOT NULL ORDER BY pedido DESC LIMIT 1";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) == 0){
        #$seq_arq = str_pad(1,10,0,STR_PAD_LEFT);
        $seq_arq = str_pad(1,10,0,STR_PAD_LEFT);
    }else{
        $arquivo_nome = pg_fetch_result($res, 0, 'arquivo_pedido');
        $seq_arq = preg_replace('/\D/',"",$arquivo_nome);
        $seq_arq = $seq_arq + 1;
        $seq_arq = str_pad($seq_arq,10,0,STR_PAD_LEFT);
    }

    $arquivo_nome = "PED{$seq_arq}.TXT";

    $sql = "
        SELECT DISTINCT 
                'C'::char(1) AS tipo_registro,
                tbl_pedido.pedido,
                tbl_pedido.posto,
                to_char(tbl_pedido.data,'YYYYMMDD') AS data_pedido,
                trim(tbl_posto_fabrica.centro_custo) AS numero_cliente,
                tbl_tipo_pedido.codigo AS tipo_pedido,
                tbl_linha.codigo_linha,
                tbl_condicao.codigo_condicao AS condicao_pagamento,
                m_os.codigo_marca AS codigo_marca_os,
                m_troca.codigo_marca AS codigo_marca_troca,
                '0001'::char(4) AS empresa,
                tbl_os_campo_extra.os_revenda
        FROM tbl_pedido
        JOIN tbl_tipo_pedido USING(tipo_pedido,fabrica)
        JOIN tbl_linha USING(linha, fabrica)
        JOIN tbl_condicao USING(condicao,fabrica)
        JOIN tbl_posto_fabrica USING(posto,fabrica)
        JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_os_item.fabrica_i = {$login_fabrica}
        JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
        JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os_produto.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}
        JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = {$login_fabrica}
        LEFT JOIN tbl_marca m_os ON m_os.marca = tbl_os_campo_extra.marca AND m_os.fabrica = {$login_fabrica}
        LEFT JOIN tbl_marca m_troca ON m_troca.marca = tbl_os.marca AND m_troca.fabrica = {$login_fabrica}
        WHERE tbl_pedido.fabrica = {$login_fabrica}
        AND tbl_pedido.finalizado IS NOT NULL
AND ((tbl_pedido.exportado IS NULL AND tbl_pedido.status_pedido = 1) 
        OR tbl_pedido.exportado IS NOT NULL AND tbl_pedido.status_pedido = 9)";
    $res = pg_query($con,$sql);

    $numPedidos  = pg_num_rows($res);
    $msg_erro = pg_errormessage($con);

    if (!empty($msg_erro)) {
        $log_erro[] = "ERRO NA CONSULTA DE PEDIDO NO TELECONTROL";
        throw new Exception($msg_erro);
    }

    if ($numPedidos > 0) {

	    $fp = fopen($arquivo,"w");

        for ($i = 0; $i < $numPedidos; $i++) {
            $enviar_para                = "";
	    $consumidor_cpf_cnpj        = "";
	    $dados_consumidor		= "";
            $posto_interno              = false;

            $tipo_registro              = pg_fetch_result($res, $i, "tipo_registro");
            #$pedido                     = str_pad(pg_fetch_result($res, $i,'pedido'),10,0,STR_PAD_LEFT);
            $pedido                     = pg_fetch_result($res, $i,'pedido');
            $data_pedido                = pg_fetch_result($res, $i,'data_pedido');
            #$numero_cliente             = str_pad(pg_fetch_result($res, $i,'numero_cliente'),10,' ');
            $numero_cliente             = pg_fetch_result($res, $i,'numero_cliente');
            #$tipo_pedido                = str_pad(pg_fetch_result($res, $i,'tipo_pedido'),4,' ');
            $tipo_pedido                = pg_fetch_result($res, $i,'tipo_pedido');
            #$condicao_pagamento         = str_pad(pg_fetch_result($res, $i,'condicao_pagamento'),10,' ');
            $condicao_pagamento         = pg_fetch_result($res, $i,'condicao_pagamento');
            $empresa                    = pg_fetch_result($res, $i, "empresa");
            $codigo_linha               = pg_fetch_result($res, $i, "codigo_linha");
            #$codigo_marca               = pg_fetch_result($res, $i, "codigo_marca");
            $os_revenda                 = pg_fetch_result($res, $i, "os_revenda");
            $posto                      = pg_fetch_result($res, $i, "posto");
            
            $codigo_marca_os            = pg_fetch_result($res, $i, "codigo_marca_os");
            $codigo_marca_troca         = pg_fetch_result($res, $i, "codigo_marca_troca");

            if (strlen(trim($codigo_marca_os)) > 0){
                $codigo_marca = $codigo_marca_os;
            }else{
                $codigo_marca = $codigo_marca_troca;
            }
            
            $sql_tipo_posto = "
                SELECT posto 
                FROM tbl_posto_fabrica 
                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                AND tbl_posto_fabrica.posto = $posto
                AND tbl_tipo_posto.posto_interno IS TRUE ";
            $res_tipo_posto = pg_query($con, $sql_tipo_posto);
            
            if (pg_num_rows($res_tipo_posto) > 0){
                $posto_interno = true;
            }

            $sql_os_revenda = "
                SELECT
                    tbl_os_revenda.consumidor_cpf,
        		    tbl_os_revenda.consumidor_nome,
        		    tbl_os_revenda.consumidor_fone       ,
        		    tbl_os_revenda.consumidor_endereco   ,
        		    tbl_os_revenda.consumidor_numero     ,
        		    tbl_os_revenda.consumidor_cep        ,
        		    tbl_os_revenda.consumidor_complemento,
        		    tbl_os_revenda.consumidor_bairro     ,
                    tbl_os_revenda.campos_extra->>'enviar_para' AS enviar_para,
                    tbl_os_revenda.campos_extra->>'os_troca_revenda_cnpj' AS cnpj_revenda,
        		    tbl_os_revenda.campos_extra->>'inscricao_estadual' AS ie,
                    tbl_os_revenda.consumidor_email,
        		    tbl_cidade.cod_ibge,
                    tbl_cidade.nome AS cidade,
                    tbl_cidade.estado
                FROM tbl_os_revenda 
		        LEFT JOIN tbl_cidade ON fn_retira_especiais(UPPER(tbl_os_revenda.consumidor_cidade)) = fn_retira_especiais(UPPER(tbl_cidade.nome)) AND tbl_os_revenda.consumidor_estado = tbl_cidade.estado AND tbl_cidade.pais = 'BR'
                WHERE tbl_os_revenda.fabrica = {$login_fabrica}
                AND tbl_os_revenda.os_revenda = {$os_revenda} ";
            $res_os_revenda = pg_query($con, $sql_os_revenda);
            
            $enviar_para    	    = pg_fetch_result($res_os_revenda, 0, "enviar_para");
            $cnpj_revenda   	    = pg_fetch_result($res_os_revenda, 0, "cnpj_revenda");
            $consumidor_cpf 	    = pg_fetch_result($res_os_revenda, 0, "consumidor_cpf");
    	    
            $consumidor_nome	    = pg_fetch_result($res_os_revenda,0,"consumidor_nome");
            $consumidor_nome        = strtoupper(retira_acentos(retira_especiais($consumidor_nome)));

            $consumidor_fone        = pg_fetch_result($res_os_revenda,0,"consumidor_fone");
    	    
            $consumidor_endereco    = pg_fetch_result($res_os_revenda,0,"consumidor_endereco");
    	    $consumidor_endereco    = strtoupper(retira_acentos(retira_especiais($consumidor_endereco)));
            
            $consumidor_numero      = pg_fetch_result($res_os_revenda,0,"consumidor_numero");
            
            $consumidor_cep         = pg_fetch_result($res_os_revenda,0,"consumidor_cep");
            if (strlen(trim($consumidor_cep)) == 8){
                $consumidor_cep     = preg_replace("/^(\d{5})(\d{3})$/", "\\1-\\2", $consumidor_cep);
            }

            $consumidor_complemento = pg_fetch_result($res_os_revenda,0,"consumidor_complemento");
            $consumidor_complemento = strtoupper(retira_acentos(retira_especiais($consumidor_complemento)));
            
            $consumidor_bairro      = pg_fetch_result($res_os_revenda,0,"consumidor_bairro");
            $consumidor_bairro      = strtoupper(retira_acentos(retira_especiais($consumidor_bairro)));

            $consumidor_email       = pg_fetch_result($res_os_revenda,0,"consumidor_email");

            $consumidor_cidade      = pg_fetch_result($res_os_revenda,0,"cidade");
            $consumidor_cidade      = strtoupper(retira_acentos(retira_especiais($consumidor_cidade)));
            
            $consumidor_estado      = pg_fetch_result($res_os_revenda,0,"estado");
            
    	    $cod_ibge               = pg_fetch_result($res_os_revenda, 0, "cod_ibge");

    	    $ie			            = pg_fetch_result($res_os_revenda,0,"ie");
    	    $ie			            = (strlen(trim($ie)) > 0) ? $ie : "ISENTO";

            if ($posto_interno === true OR $enviar_para == "C"){
                $consumidor_cpf_cnpj = preg_replace('/\D/','', $consumidor_cpf);
		    }else if ($enviar_para == "R" AND strlen($cnpj_revenda) > 0){
                $consumidor_cpf_cnpj = preg_replace('/\D/','', $cnpj_revenda);
            }
	    $fisica_juridica = (strlen($consumidor_cpf_cnpj) == 14) ? "J" : "F";

	    if(in_array($enviar_para,array('C','R')) OR $posto_interno === true){
            	$dados_consumidor = "|{$consumidor_nome}|{$fisica_juridica}|{$ie}|{$consumidor_endereco}|{$consumidor_numero}|{$consumidor_complemento}|{$consumidor_bairro}|{$consumidor_cidade}|{$consumidor_estado}|{$consumidor_cep}|{$cod_ibge}|{$consumidor_fone}|{$consumidor_email}";
	    }
	
            if ($codigo_linha == "04"){
                $motivo_ordem = "Z30";
            }else if ($codigo_linha == "06"){
                $motivo_ordem = "Z35";
            }else{
                if ($codigo_marca == "CE"){
                    $motivo_ordem = "Z01";
                }else if ($codigo_marca == "IN"){
                    $motivo_ordem = "Z02";
                }else if ($codigo_marca == "LO"){
                    $motivo_ordem = "Z03";
                }else if ($codigo_marca == "RO"){
                    $motivo_ordem = "Z20";
                }else if ($codigo_marca == "OU"){
                    $motivo_ordem = "Z02";
                }
            }

            //ALTERADO DE Z31 PARA Z30 CONFORME SOLICITAÇÃO DO MAURO NO DIA 16/05/2019
            //Flávio, 
            //Me desculpe, mas eu cometi um engano. Favor substituir o motivo Z31, na lista abaixo, pelo Z30.
            
            // FAZER CONFORME ESSAS REGRAS
            // Conforme conversamos, segue a regra para a determinação do código do motivo da ordem, para pedidos ZRAT.
            // 606 |     178 | Incepa      |         |                  | t     | f       | IN
            // 607 |     178 | Celite      |         |                  | t     | f       | CE
            // 614 |     178 | Logasa      |         |                  | t     | f       | LO
            // 615 |     178 | Roca        |         |                  | t     | f       | RO
            // 605 |     178 | Outras      |         |                  | t     | f       | OU
            // Se pedido de metais: motivo da ordem = Z31
            // Se pedido de móveis: motivo da ordem = Z35
            // Se pedido de louças ou outros materiais não citados acima
            //         Se logomarca CE: motivo da ordem = Z01
            //         Se logomarca IN: motivo da ordem = Z02
            //         Se logomarca LO: motivo da ordem = Z03
            //         Se logomarca RO: motivo da ordem = Z20
            //quando a logomarca for OU e o produto não for metais e nem móveis. O motivo correto nesse caso é Z02
            //o campo motivo ordem deve ser o ultimo campo do arquivo
            
            $header = "$tipo_registro|$pedido|$data_pedido|$numero_cliente|$tipo_pedido|$condicao_pagamento|$empresa|$motivo_ordem|$os_revenda|$consumidor_cpf_cnpj$dados_consumidor\n";

            fwrite($fp, $header);

            $sql = "SELECT 'I'::char(1) AS tipo_registro,
                            tbl_peca.referencia,
                            (tbl_pedido_item.qtde - (qtde_faturada+ qtde_cancelada)) as qtde,
                            tbl_pedido_item.preco,
                            tbl_pedido_item.pedido_item
                    FROM tbl_pedido_item
                    JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
					WHERE tbl_pedido_item.pedido = {$pedido}
					AND tbl_pedido_item.qtde > (qtde_faturada+qtde_cancelada)";
            $resI = pg_query($con,$sql);

            $rowsItens = pg_num_rows($resI);

            if($rowsItens > 0){

                for($j = 0; $j < $rowsItens; $j++){

                    $tipo_registro              = pg_fetch_result($resI, $j, "tipo_registro");
                    #$numero_item                = str_pad($j+1,3,0,STR_PAD_LEFT);
                    $numero_item                = $j+1;
                    #$referencia                 = str_pad(pg_fetch_result($resI, $j,'referencia'),18,'0',STR_PAD_LEFT);
                    $referencia                 = pg_fetch_result($resI, $j,'referencia');
                    $qtde                       = pg_fetch_result($resI, $j, "qtde");
                    $preco                      = pg_fetch_result($resI, $j, "preco");
                    #$pedido_item                = str_pad(pg_fetch_result($resI, $j,'pedido_item'),10,0,STR_PAD_LEFT);;
                    $pedido_item                = pg_fetch_result($resI, $j,'pedido_item');
                    #$qtde = str_pad(number_format($qtde,2),12,0,STR_PAD_LEFT);
                    $qtde = number_format($qtde,2);
                    #$preco = str_pad($preco,12,0,STR_PAD_LEFT);
                    $preco = $preco;

                    $body = "$tipo_registro|$pedido|$numero_item|$referencia|$qtde|$preco|$pedido_item\n";
                    fwrite($fp, $body);
                }
            }

            $sql = "UPDATE tbl_pedido SET status_pedido = 9, exportado = CURRENT_TIMESTAMP, arquivo_pedido = '{$arquivo_nome}' WHERE pedido = {$pedido}";
            $resU = pg_query($con,$sql);
        }
    }

    $now = date('Ymd_His');
    $err_log = $log_dir . '/exporta-pedido-err-'. $now . '.log';
    if (count($log_erro) > 1){
        $elog = fopen($err_log, "w");
        $dados_log_erro = implode("\n", $log_erro);
        fwrite($elog, $dados_log_erro);
        fclose($elog);
    }

    if (filesize($err_log) > 0) {
        $data_arq_enviar = date('dmy');
        $cmds = "cp $log_dir/exporta-pedido-err-$now.log $log_dir/exporta-pedido-err-$data_arq_enviar.txt";
        system($cmds, $retorno);
        
        if ($retorno == 0){
            $manda_email = true;
            $arquivos_email[] = "$log_dir/exporta-pedido-err-$data_arq_enviar.txt";
        }
    }

	fclose($fp);
	$sql    = "SELECT to_char(current_timestamp, 'YYYY-MM-DD-HH24-MI')";
	$result = pg_query($con,$sql);
	$data= pg_fetch_result($result,0,0);

	if (file_exists($arquivo) and (filesize($arquivo) > 0)) {
		date_default_timezone_set('America/Sao_Paulo');
		$data_arquivo = date('dmyHi');

		copy($arquivo, $pasta.'/' . $arquivo_nome);
        ftp_chmod($conn_id, 0777, "out/");
        ftp_put($conn_id, "out/$arquivo_nome","$pasta/$arquivo_nome", FTP_BINARY);
    }
    ftp_close($conn_id);
    system("mv $local_ped_exportado$arquivo_nome /tmp/$fabrica_nome/telecontrol-ped-exportados-roca/$arquivo_nome-$data_arq_process-ok.txt");
    
    $teste = "pedidos.txt";
    system("mv $local_ped_exportado$teste /tmp/$fabrica_nome/telecontrol-ped-exportados-roca/$teste-$data_arq_process-ok.txt");

    if ($manda_email === true){
        if (count($arquivos_email) > 0){
            $zip = "zip $log_dir/exporta-pedido-err-$data_arq_enviar.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
            system($zip, $retorno);
        }
        
        require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
        $assunto = ucfirst($fabrica_nome) . utf8_decode(': Exportação de pedidos ') . date('d/m/Y');
        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->From = 'helpdesk@telecontrol.com.br';
        $mail->FromName = 'Telecontrol';

        $mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
        $mail->Subject = $assunto;
        $mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
        
        if (count($arquivos_email) > 0){
            $mail->AddAttachment("$log_dir/exporta-pedido-err-$data_arq_enviar.zip", "exporta-pedido-err-$data_arq_enviar.zip");
        }
        
        if (!$mail->Send()) {
            echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
        } else {
            if (count($arquivos_email) > 0){
                unlink("$log_dir/exporta-pedido-err-$data_arq_enviar.zip");
                foreach ($arquivos_email as $key => $value) {
                    unlink($value);
                }
            }
        }
    }

} catch (Exception $e) {
    ftp_close($conn_id);
    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	echo $msg;
    Log::envia_email($vet, APP, $msg);
}

