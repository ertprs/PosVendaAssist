<?php
/**
 *
 * importa-novas-pendencias.php
 *
 * Importação de novas pendêncas Black&Decker
 *
 * @author  Éderson Sandre
 * @version 2011.12.29
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // production Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
	require dirname(__FILE__) . '/../funcoes.php';

    $data_log['login_fabrica'] = 3;
    $data_log['dest'] = 'helpdesk@telecontrol.com.br';
    $data_log['log'] = 2;

    date_default_timezone_set('America/Sao_Paulo');
    $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	$fabrica = 1;
    $perl = 7;
    $arquivo = "fatpecas.txt";
    $notificar_falha = 0;

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	if (ENV == 'teste' ) {
        $origem_cliente = dirname(__FILE__) . "/arquivos/";
        mkdir($origem_cliente, 0777, true);

		$origem = $origem_cliente;

        $data_log['dest'] = 'francisco@telecontrol.com.br';

		$destinatarios_clientes = "francisco@telecontrol.com.br";

    } else {
        $origem = "/home/blackedecker/black-telecontrol/";
        $origem_cliente = "/home/blackedecker/telecontrol-black/";

        $data_log['dest'] = 'helpdesk@telecontrol.com.br';

        $destinatarios_clientes = "cadastro@sbdinc.com,fabiola.oliveira@sbdinc.com,projeto@sbdbrasil.com.br,Carlos.Caldas@sbdinc.com";
    }

    //apaga todos os arquivos  do FTP com mais de 30 dias
    //system("find {$origem_cliente}importa-estoque-*.log -mtime +30 -delete");
    system("find {$origem_cliente}importa-estoque-*.log -mtime +30 > arquivos_deletado_hoje.txt 2>/dev/null");
   /*
    if(filesize('arquivos_deletado_hoje.txt') > 0){
        system("find {$origem_cliente}importa-estoque-*.log -mtime +30 -delete");
        system("rm arquivos_deletado_hoje.txt");
    }*/
    $arquivos = "/tmp";

    //$data_sistema = Date('Y-m-d-H-i-s');
    $data_sistema = Date('Y-m-d');

	system("cd $origem && cat fatpecas-[0-9]*.txt > $arquivo 2>/dev/null && rm fatpecas-[0-9]*.txt");

    mkdir("$arquivos/blackedecker/nao_bkp/log/", 0777, true);
    $arquivo_log = "$arquivos/blackedecker/nao_bkp/log/importa-estoque-$data_sistema.log";
    $arquivo_log_cliente = "$arquivos/blackedecker/nao_bkp/log/importa-estoque-".$data_sistema."_cliente.log";
    $arquivo_importacao = $origem.$arquivo;

    $fl                 = fopen($arquivo_log,"w+");
    $fl_cliente         = fopen($arquivo_log_cliente,"w+");
    $file               = fopen($arquivo_importacao,"r");

    //Insere na tabela o inicio do processamente!
    $sql = "INSERT INTO tbl_perl_processado (perl) VALUES ($perl) RETURNING perl_processado;";
    if($res = pg_query ($con,$sql)){
		$perl_processado = pg_fetch_result($res,0,'perl_processado');
    }else{
        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Erro ao registrar PERL\r\n";
        fputs($fl,implode("\r\n", $log));
        fclose ($fl);
        throw new Exception ($msg_erro);
    }

    //Verifica o arquivo de log, se existe e pode ser lido!
    if (!is_resource($fl)) {
        $log[] = Date('d/m/Y H:i:s ').$msg_erro = "O Arquivo {$arquivo_log} não pode ser lido!";
        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        fputs($fl,implode("\r\n", $log));
        fclose ($fl);
        throw new Exception ($msg_erro);
    }

    //Verifica se o arquivo existe e pode ser lido!
    if (!is_resource($file)) {
        $log[] = Date('d/m/Y H:i:s ').$msg_erro = "O Arquivo {$arquivo_importacao} não pode ser lido!";
        fputs($fl,implode("\r\n", $log));
        fclose ($fl);
        fclose ($file);
        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        throw new Exception ($msg_erro);
    }

    //Pega o arquivo aberto da importação e joga tudo para $dados
    $dados	=	fread($file, filesize($arquivo_importacao));
    fclose ($file);

    $linha = explode("\n", $dados);
    $total_linha = intval(count($linha));
    $log_cliente[] = "Inicio do Programa";

    if($total_linha > 0){
        $sql = "UPDATE tbl_perl_processado SET qtde_integrar = '{$total_linha}' WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
    }else{
        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Arquivo {$arquivo_importacao} está vazio!";
        fputs($fl,implode("\r\n", $log));
        fclose ($fl);
        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        throw new Exception ($msg_erro);
    }

    $sql  = "DROP TABLE tmp_estoque;";
    $sql .= "CREATE TABLE tmp_estoque (
                linha int4,
                conteudo_linha text,
                pedido varchar(10),
                data_pedido varchar(8),
                data_saida varchar(8),
                peca_referencia text,
                qtde_faturada text,
                nf int4,
                codigo_posto int4);";
    if(!pg_query($con,$sql)){
        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Erro ao criar tmp_estoque!";
        fputs($fl,implode("\r\n", $log));
        fclose ($fl);
        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        throw new Exception ($msg_erro);
    }

    $log[] = Date('d/m/Y H:i:s ')."Inserindo informações na temporária";
    for($i = 0; $i < $total_linha; $i++){ //$total_linha
        $dados = explode(";",$linha[$i]);

        if(count($dados) == 8){
            $conteudo_linha     = implode(";",array_filter($dados,'trim'));
            $pedido             = trim($dados[0]);
            $data_pedido        = trim($dados[1]);
            $data_saida         = trim($dados[2]);
            $peca_referencia    = trim($dados[3]);
            $qtde_faturada      = str_replace(',', '.',trim($dados[4]));
            $nf                 = trim($dados[5]);
            $codigo_posto       = trim($dados[6]);

            $sql = "INSERT INTO tmp_estoque (
                        linha,
                        conteudo_linha,
                        pedido,
                        data_pedido,
                        data_saida,
                        peca_referencia,
                        qtde_faturada,
                        nf,
                        codigo_posto
                   ) VALUES (
                        '{$i}',
                        '{$conteudo_linha}',
                        '{$pedido}',
                        '{$data_pedido}',
                        '{$data_saida}',
                        '{$peca_referencia}',
                        '{$qtde_faturada}',
                        '{$nf}',
                        '{$codigo_posto}'
                   );";
            if(!pg_query ($con,$sql)){
                $log[] = "Linha {$i}: Erro ao gravar as informações na tabela tmp_estoque;";
            }
        }else{
             $log[] = $msg_erro = "Linha {$i}: Arquivo fora do layout";
             $log_cliente_erro[] = $msg_erro;
             $notificar_falha = 1;
        }
    }

    $sql = "SELECT COUNT(*) FROM tmp_estoque ";
    $res = pg_query($con, $sql);
    $log_cliente[] = "Total de registro enviado: ".pg_fetch_result($res,0,0);

    $sql = "CREATE INDEX tmp_estoque_nf_ind ON tmp_estoque(nf);";
    #Acertando quantidade
    $sql .= "UPDATE tmp_estoque SET qtde_faturada = REPLACE(qtde_faturada,'.','');";
    $sql .= "ALTER TABLE tmp_estoque ADD COLUMN qtde_faturada2 INT4;";
    $sql .= "UPDATE tmp_estoque SET qtde_faturada2 = qtde_faturada::integer / 10;";
    $sql .= "UPDATE tmp_estoque SET qtde_faturada = qtde_faturada2;";
    $sql .= "ALTER TABLE tmp_estoque DROP COLUMN qtde_faturada2;";

    //Buscando ID do posto
    $sql .= "ALTER TABLE tmp_estoque ADD COLUMN posto INT4;";
    $sql .= "CREATE INDEX tmp_estoque_posto_ind ON tmp_estoque(posto);";
    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro na criação dos indice";
    }

    $sql = "UPDATE tmp_estoque SET
		        posto = tbl_posto_fabrica.posto
		    FROM
		        tbl_posto_fabrica
		    WHERE
                LPAD(TRIM(tbl_posto_fabrica.codigo_posto)::text,10,'0') = LPAD(TRIM(tmp_estoque.codigo_posto::text),10,'0')
                AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO' AND tbl_posto_fabrica.fabrica = $fabrica;";
    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar posto";
    }

    //Pega todos os posto que não estão com ID
    $sql = "SELECT linha + 1 AS linha, codigo_posto FROM tmp_estoque WHERE posto isnull ORDER BY linha ASC;";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res) > 0){
        $log_cliente_erro[] = "\r\n################ Valida Posto ################";
        for($i = 0; $i < pg_num_rows($res); $i++){
            extract(pg_fetch_array($res));
            $log_cliente_erro[] = "Linha: {$linha} - Posto com o código {$codigo_posto} não encontrado!";
        }
        $notificar_falha = 1;
        $log_cliente_erro[] = "################ Fim Valida Posto ################\r\n";
    }

    //Buscando ID da peça
    $sql  = "ALTER TABLE tmp_estoque ADD COLUMN peca INT4;";
    $sql .= "CREATE INDEX tmp_estoque_peca_ind ON tmp_estoque(peca);";
    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro na criação da coluna peça";
    }
    $sql = "UPDATE tmp_estoque SET
		        peca = tbl_peca.peca
		    FROM
		        tbl_peca
    		WHERE
	    	    UPPER(TRIM(tmp_estoque.peca_referencia)) = UPPER(TRIM(tbl_peca.referencia))
	        	AND tbl_peca.fabrica = $fabrica;";

    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar peças";
    }

    //Pega os registro que não tiveram ID da peça
    $sql = "SELECT linha + 1 AS linha, peca_referencia FROM tmp_estoque WHERE peca isnull ORDER BY linha ASC;";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res) > 0){
        $log_cliente_erro[] = "\r\n################ Valida Peça ################";
        for($i = 0; $i < pg_num_rows($res); $i++){
            extract(pg_fetch_array($res));
            $log_cliente_erro[] = "Linha: {$linha} - Peça {$peca_referencia} não encontrado!";
        }
        $notificar_falha = 1;
        $log_cliente_erro[] = "################ Fim Valida Peça ################\r\n";
    }

    //Buscando ID do pedido
    $sql  = "ALTER TABLE tmp_estoque ADD COLUMN id_pedido int4;";
    $sql .= "CREATE INDEX tmp_estoque_id_pedido_ind ON tmp_estoque(id_pedido);";
    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro na criação da coluna id_pedido";
    }

    $sql = "UPDATE tmp_estoque SET
		        id_pedido = tbl_pedido.pedido
		    FROM
		        tbl_pedido
            WHERE
		        TRIM(UPPER(tmp_estoque.pedido)) = TRIM(UPPER(tbl_pedido.seu_pedido))
		        AND tmp_estoque.posto = tbl_pedido.posto;";
    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar pedido";
    }

    //Pega os registro que não tiveram Pedido Cadastrado
    $sql = "SELECT linha + 1 AS linha,pedido, peca_referencia FROM tmp_estoque WHERE id_pedido isnull ORDER BY linha ASC;";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res) > 0){
        $log_cliente_erro[] = "\r\n################ Valida Pedido ################";
        for($i = 0; $i < pg_num_rows($res); $i++){
            extract(pg_fetch_array($res));
            $log_cliente_erro[] = "Linha: {$linha} - Pedido {$pedido} com a peça {$peca_referencia} não encontrado!";
        }
        $notificar_falha = 1;
        $log_cliente_erro[] = "################ Fim Valida Pedido ################\r\n";
    }

    //Pega os registro que não tem quantidade faturada
    $sql = "SELECT linha + 1, qtde_faturada, peca_referencia FROM tmp_estoque WHERE qtde_faturada::double precision <= 0 ORDER BY linha ASC;";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res) > 0){
        $log_cliente[] = "\r\n################ Valida Quantidade ################";
        for($i = 0; $i < pg_num_rows($res); $i++){
            extract(pg_fetch_array($res));
            $log_cliente[] = "Linha: {$linha} - Quantidade $qtde_faturada da peça {$peca_referencia} inválida!";
        }
        $notificar_falha = 1;
        $log_cliente[] = "################ Fim Valida Quantidade ################\r\n";
    }

    $log[] = Date('d/m/Y H:i:s ')."Apagando registro que tiveram logs de erro";
    $sql = "DELETE FROM tmp_estoque WHERE posto IS NULL OR peca IS NULL OR id_pedido IS NULL OR qtde_faturada::double precision <= 0";
    $res = pg_query($con, $sql);
    $log_cliente[] = "Registros inválido: ".intval(pg_affected_rows($res));


    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro ao deletar registro com logs de erro";
    }else{
        $notificar_falha = 1;
    }

    //Pega os registro de notas já cadastrada
    $sql = "SELECT DISTINCT (linha + 1) AS linha , tmp_estoque.nf, peca_referencia
            FROM tmp_estoque
		        JOIN tbl_estoque_posto_movimento USING(peca)
		    WHERE tbl_estoque_posto_movimento.posto = tmp_estoque.posto
                AND tbl_estoque_posto_movimento.fabrica = $fabrica
                AND tbl_estoque_posto_movimento.peca = tmp_estoque.peca
                AND tbl_estoque_posto_movimento.nf::integer = tmp_estoque.nf
           ORDER BY linha;";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res) > 0){
        //$log_cliente[] = "\r\n################ Registro que já tem notas cadastrada ################";
        for($i = 0; $i < pg_num_rows($res); $i++){
            extract(pg_fetch_array($res));
            //$log_cliente[] = "Linha: {$linha} - Nota $nf com a peça {$peca_referencia} já cadastrada!";
        }
        $notificar_falha = 1;
        //$log_cliente[] = "################ Fim  ################";
        //$log_cliente[] = "Total de registro com nota já cadastrada: ".pg_num_rows($res)."\r\n";
    }

    $log[] = Date('d/m/Y H:i:s ')."Apagando registros de notas já cadastradas:";
    $sql = "DELETE FROM tmp_estoque
            USING
		        tbl_estoque_posto_movimento
		    WHERE tbl_estoque_posto_movimento.posto = tmp_estoque.posto
                AND tbl_estoque_posto_movimento.fabrica = $fabrica
                AND tbl_estoque_posto_movimento.peca = tmp_estoque.peca
                AND tbl_estoque_posto_movimento.nf = tmp_estoque.nf::varchar;";
    if(!pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro ao deletar  de notas já cadastradas";
    }

    $log[] = Date('d/m/Y H:i:s ')."Atualizando o estoque do posto";
    $sql = "SELECT
                distinct 
                pedido,
                qtde_faturada,
                nf,
                posto,
                data_saida,
                peca,
                id_pedido,
                codigo_posto,
                peca_referencia
		    FROM
		        tmp_estoque
            ORDER BY posto;";
    if(!$res = pg_query ($con,$sql)){
        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar estoque do posto";
    }else{
        if(pg_num_rows($res) < 1){
            $log[] = Date('d/m/Y H:i:s ').$msg_erro = "Processo finalizado, não há registros para importar";
            //$log_cliente[] = "Processo finalizado, não há registros para importar";
            fputs($fl,implode("\r\n", $log));
            fclose ($fl);
            //throw new Exception ($msg_erro);
        }else{
            $log_cliente[] = "\r\n################ Atualizando estoque do posto ################";
            $qtde_integrado = pg_num_rows($res);
            for($i = 0; $i < pg_num_rows($res); $i++){
                extract(pg_fetch_array($res));

                $xdata_saida    = '20' . substr($data_saida,6,2) . '-' . substr($data_saida,3,2) . '-' . substr($data_saida,0,2);
                $erro = null;

                if(!pg_query($con, "BEGIN TRANSACTION;")){
                    $log[] = Date('d/m/Y H:i:s ').$erro = "Erro ao iniciar transação";
				}
				$sqlv = "SELECT posto, peca
						FROM tbl_estoque_posto_movimento
						WHERE pedido = $id_pedido
						AND   nf = '$nf'
						AND   peca = $peca
						AND   posto = $posto
						AND   data ='$xdata_saida'
						AND   qtde_entrada = $qtde_faturada;";
				$resv = pg_query($con,$sqlv);
				if(pg_num_rows($resv) > 0 ) {
					continue;
				}

                $sql = "INSERT INTO tbl_estoque_posto_movimento(
                            fabrica,
                            posto,
                            peca,
                            qtde_entrada,
                            data,
                            nf,
                            pedido
                        )VALUES(
                            $fabrica,
                            $posto,
                            $peca,
                            $qtde_faturada,
                            '$xdata_saida',
                            $nf,
                            $id_pedido
                        );";
                if(!pg_query($con, $sql)){
                    $log[] = Date('d/m/Y H:i:s ').$msg = "Erro ao inserir estoque no posto '$codigo_posto'";
                    $log_cliente_erro[] = $msg;
                }
                //A rotina anterior fazia UPDATE na tbl_estoque_posto somando a quantidade e depois
			    //novo UPDATE para totalizar a qtde caso estivesse errada. Melhor atualizar o total
				//de uma vez. Ébano
                $sql = "SELECT
                            SUM(COALESCE(qtde_entrada, 0)) - SUM(COALESCE(qtde_saida, 0)) AS total
                        FROM
                            tbl_estoque_posto_movimento
                        WHERE fabrica = $fabrica
                            AND posto = $posto
                            AND peca = $peca;";
                if(!$res_estoque = pg_query($con, $sql)){
                    $log[] = Date('d/m/Y H:i:s ').$erro = "Erro consulta estoque do posto '$codigo_posto'";
                    $log_cliente_erro[] = $erro;
                }
                $total = pg_fetch_result($res_estoque,0,'total');

                //$log[] = Date('d/m/Y H:i:s ')."Verifica se já tem estoque para posto X peça";
                $sql = "SELECT
				            peca
                        FROM
                            tbl_estoque_posto
                        WHERE fabrica = $fabrica
                            AND posto = $posto
                            AND peca  = $peca;";
                if(!$res_peca = pg_query($con, $sql)){
                    $log[] = Date('d/m/Y H:i:s ').$erro = "Erro consulta peça do posto";
                    $log_cliente_erro[] = $erro;
                }

                //$log[] = Date('d/m/Y H:i:s ')."Registrando o total em estoque";

                if(pg_num_rows($res_peca)){
                    $sql = "UPDATE tbl_estoque_posto SET
                                qtde = $total
                            WHERE
                                fabrica = $fabrica
                                AND posto = $posto
                                AND peca = $peca;";
                    $log[] = Date('d/m/Y H:i:s ').$msg = "Estoque do posto {$codigo_posto} atualizado com  a peça {$peca_referencia} com sucesso!";
                    $log_cliente[] = $msg;
                }else{
                    $sql = "INSERT INTO tbl_estoque_posto(
                                fabrica,
                                posto,
                                peca,
                                qtde
                            )VALUES(
                                $fabrica,
                                $posto,
                                $peca,
                                $qtde_faturada
                            );";
                    $log[] = Date('d/m/Y H:i:s ').$msg = "Estoque do posto {$codigo_posto} inserido com a peça {$peca_referencia} com sucesso!";
                    $log_cliente[] = $msg;
                }

                if(!$res_peca = pg_query($con, $sql)){
                    $log[] = Date('d/m/Y H:i:s ').$erro = "Erro ao atualizar estoque do posto {$codigo_posto}";
                    $log_cliente_erro[] = $erro;
                }

                if(!empty($erro)){
                    $log[] = Date('d/m/Y H:i:s ').$erro = "Erro ao atualizar estoque do posto {$codigo_posto} - {$conteudo_linha}";
                    pg_query($con, "ROLLBACK TRANSACTION;");
                }else{
                    //pg_query($con, "ROLLBACK TRANSACTION;");
                    pg_query($con, "COMMIT TRANSACTION;");
                }

            }

            $log_cliente[] = "################ Fim ################\r\n";
        }
    }

    $sql = "UPDATE tbl_perl_processado SET qtde_integrado = {$qtde_integrado}, log = 'Atualizando com sucesso!', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
    pg_query($con, $sql);

    if(count($log) > 0){
        $log[] = Date('d/m/Y H:i:s ')."Fim do Programa";
        fputs($fl,implode("\r\n", $log));
        fclose ($fl);
    }

    if(count($log_cliente_erro)){
        $logs[] = "################################## ERROS ##################################";
        $logs[] = implode("\r\n", $log_cliente_erro);
        $logs[] = "###########################################################################\r\n###########################################################################\r\n\r\n";
    }

    if(count($log_cliente)){
        $logs[] = "################################## LOGS ##################################";
        $logs[] = implode("\r\n", $log_cliente);
        $logs[] = "##########################################################################\r\n##########################################################################\r\n\r\n";
    }

    if(count($logs) > 0){
        fputs($fl_cliente,implode("\r\n", $logs));
        fclose ($fl_cliente);
    }

    if (filesize($origem.$arquivo) > 0) {
        //$log[] = Date('d/m/Y H:i:s ')."Movendo arquivo para $arquivos/blackedecker/nao_bkp/arquivos/$arquivo-$data_sistema.txt";
        mkdir("{$arquivos}/blackedecker/nao_bkp/arquivos/",0777, true);

        system ("mv {$origem}{$arquivo} {$arquivos}/blackedecker/nao_bkp/arquivos/{$arquivo}-{$data_sistema}.txt;");
        system ("mv {$arquivo_log_cliente} {$origem_cliente}/{$arquivo}-{$data_sistema}.log;");
        //system ("cp $origem.$arquivo /home/ederson/public_html/assist/rotinas/blackedecker/arquivos/;");
    }

    $mailer = new PHPMailer();
    $mailer->IsSMTP();
    $mailer->IsHTML();
    $mailer->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");

    $emails = explode(",", $destinatarios_clientes);
    if(count($emails)){
        foreach ($emails as $email) {
            $mailer->AddAddress($email);
        }
    }else{
        $mailer->AddAddress($destinatarios_clientes);
    }

    $arquivo_anexo = Date("d-m-Y")."-LOG-IMPORTA_ESTOQUE.TXT";

    $mensagem  = "Logs Importa Estoque<br><br>";
    $mensagem  .= "Mensagem segue em anexo!<br><br>";
    $mensagem .= "<br><br>Att.<br>Telecontrol Networking";

    $mailer->Subject = "Logs Importa Estoque";

    $arquivo_completo_anexo = "{$arquivos}/blackedecker/nao_bkp/arquivos/{$arquivo_anexo}";
    $anexo = fopen($arquivo_completo_anexo, "w+");
        fputs($anexo,implode("\r\n", $logs));
    fclose($anexo);

    system("cp $arquivo_importacao {$arquivos}/blackedecker/nao_bkp/arquivos/;");
    system ("cd {$arquivos}/blackedecker/nao_bkp/arquivos/; rm log_pedido_faturado-$data_sistema.zip;  zip log_pedido_faturado-$data_sistema.zip  {$arquivo_anexo} {$arquivo}-{$data_sistema}.txt");

    //system("cp $arquivo_completo_anexo /home/ederson/public_html/assist/Logs.txt;");

    $mailer->Body = $mensagem;
    $mailer->AddAttachment("$arquivos/blackedecker/nao_bkp/arquivos/log_pedido_faturado-$data_sistema.zip");
    #$mailer->Send();
	$boundary = "XYZ-" . date("dmYis") . "-ZYX"; 
	$anexo_nome = "$arquivos/blackedecker/nao_bkp/arquivos/log_pedido_faturado-$data_sistema.zip";
	$anexo = file_get_contents( $anexo_nome );
	$anexo = chunk_split( base64_encode( $anexo ) );

	$mens = "--$boundary\n";
	$mens .= "Content-Transfer-Encoding: 8bits\n";
	$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n"; //plain
	$mens .= "$mensagem\n";
	$mens .= "--$boundary\n";
	$mens .= "Content-Type: zip\n"; 
	$mens .= "Content-Disposition: attachment; filename=\"log-$data_sistema.zip\"\n"; 
	$mens .= "Content-Transfer-Encoding: base64\n\n"; 
	$mens .= "$anexo\n"; 
	$mens .= "--$boundary--\r\n"; 

	$headers  = "MIME-Version: 1.0\n"; 
	$headers .= "From: no_reply@telecontrol.com.br\r\n"; 
	$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n"; 
	$headers .= "$boundary\n";
	mail($destinatarios_clientes, utf8_encode('Logs Importa Estoque'), utf8_encode($mens), $headers);

	$phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    //echo $msg."\r\n";

    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar importa novas pendencias", $msg);
}

