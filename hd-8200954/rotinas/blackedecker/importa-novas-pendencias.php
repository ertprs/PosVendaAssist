<?php
/**
 *
 * importa-novas-pendencias.php
 *
 * Importação de novas pendêncas Black&Decker
 *
 * @author  Éderson Sandre
 * @version 2011.12.28
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');
#define('ENV','teste');


try {

    $data_log['login_fabrica'] = 3;
    $data_log['log']           = 2;

    date_default_timezone_set('America/Sao_Paulo');

    $log[]           = Date('d/m/Y H:i:s ').$msg = "Inicio do Programa";
    $log_cliente[]   = $msg;
    $fabrica         = 1;
    $perl            = 13;
    $arquivo         = "retsspnf.txt";
    $notificar_falha = 0;

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
    require dirname(__FILE__) . '/../funcoes.php';

    if (ENV == 'teste' ) {
        $origem_cliente = dirname(__FILE__) . "/arquivos/";

        mkdir($origem_cliente, 0777, true);

		$origem = $origem_cliente;

        $data_log['dest'] = 'gaspar.lucas@telecontrol.com.br';

		$destinatarios_clientes = "gaspar.lucas@telecontrol.com.br";

        mkdir($origem_cliente, 0777, true);
    } else {
        $origem                 = "/home/blackedecker/black-telecontrol/";
        $origem_cliente         = "/home/blackedecker/telecontrol-black/";

        $data_log['dest']       = 'helpdesk@telecontrol.com.br';
        #$destinatarios_clientes = "fabiola.Oliveira@bdk.com";
		$destinatarios_clientes = "cadastro@sbdinc.com,fabiola.oliveira@sbdinc.com,projeto@sbdbrasil.com.br,Carlos.Caldas@sbdinc.com";
    }

    if(ENV == "teste"){
        $arquivo_importacao  = $origem.$arquivo;
        //$arquivo_importacao  = $origem.'retsspnf.txt';
        //$arquivo_importacao  = 'tests/retsspnf.txt';
        $file       = file_get_contents($arquivo_importacao);

    }else{

       system("find {$origem_cliente}importa-novas-pendencias-*.log -mtime +30 > arquivos_deletado_hoje.txt 2>/dev/null");
        $arquivos = "/tmp";
        #$arquivos = "home/monteiro/public_html/posvenda/rotinas/blackedecker/arquivo/";
        $data_sistema = Date('Y-m-d');
        mkdir("$arquivos/blackedecker/nao_bkp/log/", 0777, true);

        system("cd $origem && cat retsspnf-[0-9]*.txt > $arquivo 2>/dev/null && rm retsspnf-[0-9]*.txt");

        $arquivo_log         = "$arquivos/blackedecker/nao_bkp/log/importa-novas-pendencias-$data_sistema.log";
        $arquivo_log_cliente = "$arquivos/blackedecker/nao_bkp/log/importa-novas-pendencias-".$data_sistema."_cliente.log";
        $arquivo_importacao  = $origem.$arquivo;

        $fl         = fopen($arquivo_log, "w+");
        $fl_cliente = fopen($arquivo_log_cliente, "w+");
        $file       = fopen($arquivo_importacao, "r");

    }

    if(ENV == "teste"){

        $dados = $file;

    }else{

        //Insere na tabela o inicio do processamente!
        $sql = "INSERT INTO tbl_perl_processado (perl) VALUES ($perl) RETURNING perl_processado;";
        if ($res = pg_query($con, $sql)) {
            $perl_processado = pg_fetch_result($res, 0, 'perl_processado');

        } else {
            $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Erro ao registrar PERL: ".pg_last_error($con)."\r\n";

            fputs($fl, implode("\r\n", $log));
            fclose($fl);

            throw new Exception ($msg_erro);

        }

        //Verifica o arquivo de log, se existe e pode ser lido!
        if (!is_resource($fl)) {

            $log[] = Date('d/m/Y H:i:s ').$msg_erro = "O Arquivo {$arquivo_log} não pode ser lido!";

            $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
            pg_query($con, $sql);

            fputs($fl, implode("\r\n", $log));
            fclose($fl);

            throw new Exception($msg_erro);

        }
        //Verifica se o arquivo existe e pode ser lido!
        if (!is_resource($file)) {
            $log[] = Date('d/m/Y H:i:s ').$msg_erro = "O Arquivo {$arquivo_importacao} não pode ser lido!";
            fputs($fl, implode("\r\n", $log));
            fclose($fl);
            fclose($file);
            $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
            pg_query($con, $sql);
            throw new Exception($msg_erro);
        }

        //Pega o arquivo aberto da importação e joga tudo para $dados
        $dados = fread($file, filesize($arquivo_importacao));
        fclose($file);

    }

    if (!empty($dados)) {
        $linha        = explode("\n", $dados);
    	$total_linha  = intval(count($linha));
    } else {
        $total_linha = 0;
    }

    //$total_linha =1;
    if ($total_linha > 0) {

        $sql = "UPDATE tbl_perl_processado SET qtde_integrar = '{$total_linha}' WHERE perl_processado = {$perl_processado};";
        pg_query($con, $sql);

    } else {

        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Arquivo {$arquivo_importacao} está vazio!";
	    $log_cliente[] = $msg_erro = Date('d/m/Y H:i:s ')."Arquivo {$arquivo_importacao} está vazio!";
        fputs($fl,implode("\r\n", $log));
        fclose($fl);

        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query($con, $sql);
        throw new Exception ($msg_erro);
    }

    $sql  = "DROP TABLE if exists tmp_black_retsspnf;";
    $sql .= "CREATE table tmp_black_retsspnf (
                linha int4,
                conteudo_linha text,
                seu_pedido varchar(20),
                referencia varchar(20),
                qtde_faturada int4,
                data varchar(10),
                nota_fiscal varchar(12),
                sequencial int4,
                transportadora varchar(100),
                conhecimento varchar(20),
                os varchar(20),
                tipo varchar(20),
                referencia_solicitada varchar(20),
                total_peca double precision);";

    if (!pg_query($con,$sql)) {

        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Erro ao criar tmp_black_retsspnf!";
        fputs($fl,implode("\r\n", $log));
        fclose ($fl);

        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        throw new Exception ($msg_erro);
    }

    $log[]         = Date('d/m/Y H:i:s ')."Inserindo informações na temporária";
    $log_cliente[] = "Total de registro enviado: {$total_linha}";

    for ($i = 0; $i < $total_linha; $i++) {//$total_linha

        $dados = explode(";",$linha[$i]);
        
        if (count($dados) == 13) {
            $conteudo_linha         = implode(";",array_filter($dados,'trim'));
            $seu_pedido             = trim($dados[0]);
            $referencia             = trim($dados[1]);
            $qtde_faturada          = trim($dados[2]);
            $data                   = implode("-",array_reverse(explode("/", $dados[3])));
            $nota_fiscal            = trim($dados[4]);
            $sequencial             = trim($dados[5]);
            $transportadora         = trim($dados[6]);
            $conhecimento           = trim($dados[7]);
            $os                     = trim($dados[8]);
            $tipo                   = trim($dados[9]);
            $referencia_solicitada  = trim($dados[10]);
            $total_peca             = trim($dados[11]);

			$total_peca = str_replace(',', '.', $total_peca);
			if(strlen($total_peca) == 0) $total_peca = 0 ;

	        $i_aux = $i+1;
            $sql = "INSERT INTO tmp_black_retsspnf (
                        linha,
                        conteudo_linha,
                        seu_pedido,
                        referencia,
                        qtde_faturada,
                        data,
                        nota_fiscal,
                        sequencial,
                        transportadora,
                        conhecimento,
                        os,
                        tipo,
                        referencia_solicitada,
                        total_peca
                    ) VALUES (
                        '{$i_aux}',
                        '{$conteudo_linha}',
                        '{$seu_pedido}',
                        '{$referencia}',
                        '{$qtde_faturada}',
                        '{$data}',
                        '{$nota_fiscal}',
                        '{$sequencial}',
                        '{$transportadora}',
                        '{$conhecimento}',
                        '{$os}',
                        '{$tipo}',
                        '{$referencia_solicitada}',
                        '{$total_peca}'
                    );";
            if (!pg_query($con, $sql)) {
                $log[] = "Linha {$i}: Erro ao gravar as informações na tabela tmp_black_retsspnf;";
            }

        } else {

            $numeros = count($dados);
            $log[] = $msg_erro = "Linha {$i}: Arquivo com quantidade de campo inválido.";
            $log_cliente_erro[] = $msg_erro;

        }

    }

    $log[] = Date('d/m/Y H:i:s ')."Criando indice";

    $sql  = "ALTER TABLE tmp_black_retsspnf ADD column peca int4;";
    $sql .= "ALTER TABLE tmp_black_retsspnf ADD column peca_solicitada int4;";

    $sql .= "ALTER TABLE tmp_black_retsspnf ADD column pedido int4;";
    $sql .= "ALTER TABLE tmp_black_retsspnf ADD column posto int4;";
    $sql .= "ALTER TABLE tmp_black_retsspnf ADD column os_id int4;";
    $sql .= "ALTER TABLE tmp_black_retsspnf ADD column data_input timestamp;";

    $sql .= "CREATE INDEX tmp_black_retsspnf_linha         ON tmp_black_retsspnf(linha);";
    $sql .= "CREATE INDEX tmp_black_retsspnf_pedido        ON tmp_black_retsspnf(pedido);";
    $sql .= "CREATE INDEX tmp_black_retsspnf_seu_pedido    ON tmp_black_retsspnf(seu_pedido varchar_pattern_ops);";
    $sql .= "CREATE INDEX tmp_black_retsspnf_peca          ON tmp_black_retsspnf(peca);";
    $sql .= "CREATE INDEX tmp_black_retsspnf_referencia    ON tmp_black_retsspnf(referencia);";
    $sql .= "CREATE INDEX tmp_black_retsspnf_qtde_faturada ON tmp_black_retsspnf(qtde_faturada);";

    if (!pg_query ($con,$sql)) {
        $log[] = Date('d/m/Y H:i:s ')."Erro na criação dos indice";
    }

    //Apaga Lixo
    $log[] = Date('d/m/Y H:i:s ')."Excluindo lixos SP2,SP4,SP7,SAE,SAF,SA";
    $sql   = "DELETE FROM tmp_black_retsspnf
               WHERE seu_pedido LIKE 'SP2%' OR
                     seu_pedido LIKE 'SP4%' OR
                     seu_pedido LIKE 'SP7%' OR
                     seu_pedido LIKE 'SAE%' OR
                     seu_pedido LIKE 'SA%' OR
                     seu_pedido LIKE 'SAF%';";

    if (!$res = pg_query($con, $sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao excluir";

    } else {

        $delete = intval(pg_affected_rows($res));
        //$log_cliente[] = "Total de registros 'SP2,SP4,SP7,SAE,SAF,SA': {$delete}";
        $log[] = Date('d/m/Y H:i:s ')."Total de registro afetado: {$delete}";

    }

    //Numero do Pedido
    $log[] = Date('d/m/Y H:i:s ')."Atualizando numero do pedido";
    $sql   = "UPDATE tmp_black_retsspnf SET
                     pedido = tbl_pedido.pedido
                FROM tbl_pedido
               WHERE tbl_pedido.seu_pedido = replace(tmp_black_retsspnf.seu_pedido,'SES','TRI')
			   AND tbl_pedido.status_pedido not in (4,14)
			   AND tbl_pedido.fabrica = {$fabrica};

				UPDATE tmp_black_retsspnf SET
                     posto = tbl_pedido.posto
                FROM tbl_pedido
               WHERE tbl_pedido.pedido = tmp_black_retsspnf.pedido
			   AND tbl_pedido.fabrica = {$fabrica};

    ";

    if (!$res = pg_query ($con,$sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar";

    } else {

        $update = intval(pg_affected_rows($res));
        $log[]  = Date('d/m/Y H:i:s ')."Total de registro afetado: {$update}";
        //$log_cliente[] = Date('d/m/Y H:i:s ')."Total de registros 'SP2,SP4,SP7,SAE,SAF,SA' removidos: {$update}";

    }

    //Consulta pedido invalido
    $sql = "SELECT linha AS linha,pedido, seu_pedido FROM tmp_black_retsspnf WHERE pedido isnull ORDER BY linha ASC;";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        $log_cliente_erro[] = "\r\n################ Valida Pedido ################";
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            extract(pg_fetch_array($res));
            $log_cliente_erro[]   = "Linha: {$linha} - Pedido {$seu_pedido} não encontrado!";
            $notificar_falha = 1;
        }
        $log_cliente_erro[] = "################ Fim ################\r\n";

    }

    $sql = "DELETE FROM tmp_black_retsspnf WHERE pedido isnull;";
    $res = pg_query ($con,$sql);

    //Peca Solicitada
    $log[] = Date('d/m/Y H:i:s ')."Atualizando peça solicitada";
    $sql   = "UPDATE tmp_black_retsspnf
                 SET peca_solicitada = tbl_peca.peca
                FROM tbl_pedido_item
                JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $fabrica
               WHERE UPPER(tbl_peca.referencia) = tmp_black_retsspnf.referencia_solicitada
                 AND tbl_pedido_item.pedido     = tmp_black_retsspnf.pedido;";

    if (!$res = pg_query ($con,$sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar";

    } else {

        $update = intval(pg_affected_rows($res));
        $log[]  = Date('d/m/Y H:i:s ')."Total de registro afetado: {$update}";

    }

	$log[] = Date('d/m/Y H:i:s ')."Atualizando peça solicitada";
    $sql   = "UPDATE tmp_black_retsspnf
                 SET peca = tbl_peca.peca
                FROM tbl_pedido_item
                JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $fabrica
               WHERE UPPER(tbl_peca.referencia) = tmp_black_retsspnf.referencia
			   AND tbl_pedido_item.pedido     = tmp_black_retsspnf.pedido;

    ";

    if (!$res = pg_query ($con,$sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar";

    } else {

        $update = intval(pg_affected_rows($res));
        $log[]  = Date('d/m/Y H:i:s ')."Total de registro afetado: {$update}";

    }

    //Peca
    $log[] = Date('d/m/Y H:i:s ')."Atualizando peça";
    $sql   = "UPDATE tmp_black_retsspnf
                 SET peca = tbl_peca.peca
                FROM tbl_peca
				WHERE UPPER(tbl_peca.referencia) = tmp_black_retsspnf.referencia
				AND  tmp_black_retsspnf.peca_solicitada notnull
                 AND fabrica = $fabrica;";

    if (!$res = pg_query ($con,$sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar";

    } else {

        $update = intval(pg_affected_rows($res));
        $log[]  = Date('d/m/Y H:i:s ')."Total de registro afetado: {$update}";

    }

    //Consulta peça invalida
    $sql = "SELECT linha AS linha, seu_pedido, referencia FROM tmp_black_retsspnf WHERE peca isnull ORDER BY linha ASC;";
    $res = pg_query ($con, $sql);

    if (pg_num_rows($res) > 0) {
        $log_cliente_erro[] = "\r\n################ Peça ################";
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            extract(pg_fetch_array($res));
            $log_cliente_erro[]   = "Linha: {$linha} - Pedido {$seu_pedido} com a peça $referencia não encontrado!";
            $notificar_falha = 1;
        }
        $log_cliente_erro[] = "################ Fim ################\r\n";

    }

    //Atualizando Pedido
    $log[] = Date('d/m/Y H:i:s ')."Atualizando Pedido";
    // Retirado no HD-7031480
    /*$sql   = "SELECT fn_atualiza_pedido_item_peca($fabrica, tbl_pedido_item.pedido_item, tmp_black_retsspnf.peca , (select COALESCE(obs,'') || ' Peça ' || tmp_black_retsspnf.referencia_solicitada || ' alterada para ' || tmp_black_retsspnf.referencia))
                FROM tmp_black_retsspnf
                JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tmp_black_retsspnf.pedido AND tbl_pedido_item.peca = tmp_black_retsspnf.peca_solicitada
               WHERE tmp_black_retsspnf.peca notnull
                 AND tmp_black_retsspnf.peca <> tmp_black_retsspnf.peca_solicitada ;";*/
    $sql = "    SELECT  tbl_pedido_item.pedido_item,
                        tmp_black_retsspnf.peca,
                        tmp_black_retsspnf.referencia_solicitada,
                        tmp_black_retsspnf.referencia
                FROM tmp_black_retsspnf
                JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tmp_black_retsspnf.pedido AND tbl_pedido_item.peca = tmp_black_retsspnf.peca_solicitada
                WHERE tmp_black_retsspnf.peca notnull
                AND tmp_black_retsspnf.peca <> tmp_black_retsspnf.peca_solicitada";
    if (!$res = pg_query ($con, $sql)) {
        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar";
    } else {
        $array_retsspnf = pg_fetch_all($res);
        foreach ($array_retsspnf as $posicao => $valor) {
            $xpeca = $valor['peca'];
            $xpedido_item = $valor['pedido_item'];
            $xobs = "Peça ".$valor['referencia_solicitada']." alterada para ".$valor['referencia'];
            pg_query($con, "BEGIN TRANSACTION;");

            $sqlFn = " SELECT fn_atualiza_pedido_item_peca($fabrica,$xpedido_item,$xpeca,'$xobs')";
            $resFn = pg_query($con, $sqlFn);
            if (pg_last_error() || !$resFn) {
                $log[]           = Date('d/m/Y H:i:s ') . $erro = "Erro fazer insert do novo item fn_atualiza_pedido_item_peca";
                $notificar_falha = 1;
                pg_query($con, "ROLLBACK TRANSACTION;");
            } else {
                pg_query($con, "COMMIT TRANSACTION;");
            }
        }
    }

    //Atualizando OS Item
    /* 
    $log[] = Date('d/m/Y H:i:s ')."Atualizando OS Item";
    $sql   = "SELECT fn_atualiza_os_item_peca($fabrica, tbl_os_item.os_item, tmp_black_retsspnf.peca, (select COALESCE(obs,'') || ' Peça ' || tmp_black_retsspnf.referencia_solicitada || ' alterada para ' || tmp_black_retsspnf.referencia || ' e a data do item foi alterada de ' || to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') || ' para ' || to_char(current_date,'DD/MM/YYYY')))
                FROM tbl_os_item
                JOIN tmp_black_retsspnf ON tmp_black_retsspnf.pedido = tbl_os_item.pedido AND tbl_os_item.peca = tmp_black_retsspnf.peca_solicitada
               WHERE tbl_os_item.fabrica_i=$fabrica AND tmp_black_retsspnf.peca notnull
                 AND tmp_black_retsspnf.peca <> tmp_black_retsspnf.peca_solicitada;";

    if (!$res = pg_query ($con,$sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar";

    }
    */
    //Atualizando Sequencial da Nota
    $log[] = Date('d/m/Y H:i:s ')."Atualizando sequencial da nota";
    $sql   = "UPDATE tmp_black_retsspnf SET
                     data_input = tbl_pendencia_bd_novo_nf.data_input
                FROM tbl_pendencia_bd_novo_nf
               WHERE tmp_black_retsspnf.nota_fiscal = tbl_pendencia_bd_novo_nf.nota_fiscal
                 AND tmp_black_retsspnf.sequencial  = tbl_pendencia_bd_novo_nf.sequencial_nf
                 AND tmp_black_retsspnf.pedido      = tbl_pendencia_bd_novo_nf.pedido_banco
                 AND tmp_black_retsspnf.peca        = tbl_pendencia_bd_novo_nf.peca;";

    if (!$res = pg_query ($con,$sql)) {
        $log[] = Date('d/m/Y H:i:s ')."Erro ao atualizar";
    }

    //Consulta peça invalida
    $sql = "SELECT linha AS linha, seu_pedido, referencia, qtde_faturada FROM tmp_black_retsspnf WHERE qtde_faturada <= 0 ORDER BY linha ASC;";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $log_cliente_erro[] = "\r\n################ Valida Pedido Quantidade ################";
        for ($i = 0; $i < pg_num_rows($res); $i++) {

            extract(pg_fetch_array($res));
            $log_cliente_erro[]   = "Linha: {$linha} - Pedido {$seu_pedido} com a peça $referencia está com a quantidade {$qtde_faturada} inválida!";
            $notificar_falha = 1;
        }
        $log_cliente_erro[] = "################ Fim ################\r\n";

    }

    //Consulta peça invalida
    $sql = "SELECT linha AS linha, seu_pedido, referencia, data_input FROM tmp_black_retsspnf WHERE data_input notnull ORDER BY linha ASC;";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        //$log_cliente[] = "\r\n################ Pedido já exportado ################";
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            extract(pg_fetch_array($res));
          //  $log_cliente[] = "Linha: {$linha} - Pedido {$seu_pedido} com a peça $referencia já foi exportado em {$data_input}";
        }
        //$log_cliente[] = "################ Fim ################\r\n";
    }

    $sql  = "DELETE FROM tmp_black_retsspnf WHERE peca IS NULL;";
    $sql .= "DELETE FROM tmp_black_retsspnf WHERE pedido IS NULL;";
    $sql .= "DELETE FROM tmp_black_retsspnf WHERE qtde_faturada <= 0;";
    $sql .= "DELETE FROM tmp_black_retsspnf WHERE data_input IS NOT NULL;";

    if (!$res = pg_query ($con,$sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao apagar";

    } else {

        $delete = intval(pg_affected_rows($res));
        $log[]  = Date('d/m/Y H:i:s ')."Total de registro inválido e apagado: {$delete}";

    }

    $log[] = Date('d/m/Y H:i:s ')."Validação dos Dados - 02";
    $sql   = "SELECT linha AS linha,
                     conteudo_linha,
                     seu_pedido,
                     referencia,
                     qtde_faturada,
                     data,
                     nota_fiscal,
                     sequencial,
                     transportadora,
                     conhecimento,
                     tipo,
                     os,
                     peca,
					 pedido,
					 total_peca,
                     referencia_solicitada,
                     peca_solicitada
                FROM tmp_black_retsspnf
            ORDER BY linha ASC;";

    if (!$res = pg_query ($con,$sql)) {

        $log[] = Date('d/m/Y H:i:s ')."Erro ao consultar";

    } else {

        $qtde_integrado = null;
        $cont_rows = pg_num_rows($res);

        for ($i = 0; $i < $cont_rows; $i++) {

            extract(pg_fetch_array($res));

            $erro = null;

            $sql_posto_pedido = "SELECT posto FROM tbl_pedido WHERE pedido = {$pedido} AND fabrica = {$fabrica}";
            $res_posto_pedido = pg_query($con, $sql_posto_pedido);

            $posto_id = pg_fetch_result($res_posto_pedido, 0, "posto");

            // HD-6927134
            $sql_usou_estoque = "  SELECT tbl_os_item.os_item
                                   FROM tbl_os_item
                                   JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                   JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
                                   WHERE tbl_os.fabrica = {$fabrica}
                                   AND tbl_os.os = {$os}
                                   AND tbl_os.conferido_saida IS TRUE 
                                   AND tbl_os_item.peca_reposicao_estoque IS TRUE 
                                   AND tbl_os_item.peca = {$peca}";
            $res_usou_estoque = pg_query($con, $sql_usou_estoque);
            if (pg_num_rows($res_usou_estoque) > 0) {
                $sql_3 = "INSERT INTO tbl_estoque_posto_movimento (fabrica, 
                                                                    posto, 
                                                                    os, 
                                                                    peca, 
                                                                    data, 
                                                                    qtde_entrada
                                                                ) VALUES (
                                                                    $fabrica, 
                                                                    $posto_id, 
                                                                    $os, 
                                                                    $peca,
                                                                    now(),
                                                                    $qtde_faturada
                                                                )";
                $res_3 = pg_query($con, $sql_3);
                
                $sql_4 = "UPDATE tbl_estoque_posto SET qtde = qtde + $qtde_faturada WHERE peca = $peca AND posto = $posto_id AND fabrica = $fabrica";
                $res_4 = pg_query($con, $sql_4);
            }

			if($tipo =='GARANTIA') {
				$sua_os = substr($os, 5, strlen($os));

				$sql_os = "SELECT os FROM tbl_os WHERE fabrica = {$fabrica} AND sua_os = '{$sua_os}' and posto = $posto_id";
				$res_os = pg_query($con, $sql_os);
				if(pg_num_rows($res_os) > 0) {
					$os_id = pg_fetch_result($res_os, $z, "os");
					$sql_os = "UPDATE tmp_black_retsspnf set os_id = $os_id where pedido = $pedido and os = '$os' and peca = $peca";
					$res_os = pg_query($con, $sql_os);
				}else{
					$os_id = "0";
				}
			}else{
				$os_id = "0";
			}

                    //Buscando os itens que estão pendentes para pedido e peça (referência)
                    //Tem que ser por referência, busque por EXPLICACAO_REFERENCIA nos comentários do código para detalhes
                    $sql_1 = "SELECT tbl_pedido_item.pedido_item                 AS pedido_item,
                                     COALESCE(tbl_pedido_item.qtde, 0)           AS qtde_item,
                                     COALESCE(tbl_pedido_item.qtde_faturada, 0)  AS qtde_faturada_item,
                                     COALESCE(tbl_pedido_item.qtde_cancelada, 0) AS qtde_cancelada_item
                                FROM tbl_pedido_item 
                                INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.posto = {$posto_id}  
                                INNER JOIN tbl_peca ON tbl_peca.peca=tbl_pedido_item.peca  AND tbl_peca.fabrica = $fabrica 
                                WHERE  
                                    tbl_pedido_item.pedido = $pedido
                                    AND tbl_peca.referencia = '$referencia'
                                    AND tbl_pedido_item.peca_alternativa ISNULL";

                    if (!$res_1 = pg_query ($con,$sql_1)) {

                        $log[] = Date('d/m/Y H:i:s ')."Erro ao consultar";

                    } else {

                        if (pg_num_rows($res_1) > 0 AND empty($erro)) {

                            $qtde_alocada = 0;

                            if ($qtde_alocada < $qtde_faturada) {

                                for ($a = 0; $a < pg_num_rows($res_1); $a++) {

                                    pg_query($con, "BEGIN TRANSACTION;");

                                    extract(pg_fetch_array($res_1));

                                    $pendencia_item = $qtde_item - $qtde_faturada_item - $qtde_cancelada_item;

                                    //As pendências no item são menores que as pendências do que ainda não foram alocadas,
                                    //então preenche toda a pendência do item
                                    if ($pendencia_item <= $qtde_faturada - $qtde_alocada) {

                                        $atualizar = $qtde_faturada_item + $pendencia_item;
                                        $qtde_alocada += $pendencia_item;

                                    } else { //As pendências no item são maiores que as pendências do que ainda não foi alocado, então tudo o que não foi alocado pode ser alocado neste item
                                        $atualizar = $qtde_faturada_item + $qtde_faturada - $qtde_alocada;
                                        $qtde_alocada = $qtde_faturada;
                                    }

                                    $sql = "SELECT fn_atualiza_pedido_item($peca, $pedido, $pedido_item, $atualizar);";

                                    if(pg_query ($con,$sql)){
                                        $log_cliente[] = "Linha: {$linha} - Pedido {$seu_pedido} atualizado com sucesso!";
                                        $qtde_integrado += 1;
                                    }

                                    if ($qtde_alocada != $qtde_faturada) {
                                        $log[] = Date('d/m/Y H:i:s ').$erro = "A quantidade total de itens pendentes no pedido '{$seu_pedido} - {$referencia}' da Telecontrol é menor que a quantidade faturada";
                                    }

                                    $sql = "SELECT fn_atualiza_status_pedido($fabrica, $pedido)";

                                    pg_query ($con,$sql);

                                    $sql = "SELECT
                                                COUNT(*)
                                            FROM
                                                tbl_os_item
                                            WHERE
                                                tbl_os_item.fabrica_i=$fabrica
                                                AND tbl_os_item.pedido=$pedido;";

                                    $res_2 = pg_query ($con,$sql);

									if ($tipo == 'GARANTIA' and pg_num_rows($res_2) > 0) {
										$cond = "";
										if(strlen($os_id) > 3) {
											$cond = " and tbl_os_produto.os = $os_id ";
										}

                                        if (!empty($referencia_solicitada) && ($referencia_solicitada == $referencia || empty($referencia)) && !empty($peca_solicitada)) {
                                            $cond_faturamento_solicitacao = " AND tbl_peca.referencia='$referencia_solicitada' ";
                                        } else {
                                            $cond_faturamento_solicitacao = " AND tbl_peca.referencia='$referencia' ";
                                        }

                                        $sql = "SELECT  tbl_os_item.os_item                         AS os_item,
                                                        tbl_os_item.qtde                            AS qtde_item,
                                                        tbl_os_produto.os                                 ,
                                                       SUM(COALESCE(tbl_os_item_nf.qtde_nf, 0))    AS qtde_nf
                                                        FROM tbl_os_item
                                                        JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                                        JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca AND tbl_peca.fabrica=$fabrica
                                                        LEFT JOIN tbl_os_item_nf ON tbl_os_item_nf.os_item = tbl_os_item.os_item
                                                        WHERE tbl_os_item.pedido=$pedido
														{$cond_faturamento_solicitacao}
														AND tbl_os_item.fabrica_i=$fabrica
														$cond
                                                        GROUP BY tbl_os_item.os_item,
                                                                tbl_os_item.qtde,
                                                                tbl_os_produto.os
                                                        HAVING SUM(COALESCE(tbl_os_item_nf.qtde_nf, 0)) < tbl_os_item.qtde;";

                                        $res_3        = pg_query($con, $sql);
                                        $qtde_alocada = 0;

                                        if ($qtde_alocada < $qtde_faturada) {

                                            for ($b = 0; $b < pg_num_rows($res_3); $b++) {

                                                extract(pg_fetch_array($res_3));

                                                $pendencia_item = $qtde_item - $qtde_nf;
                                                $qtde_alocar    = 0;

                                                //As pendências no item são menores que as pendências do que ainda não foram alocadas, então preenche toda a pendência do item
                                                if ($pendencia_item <= $qtde_faturada - $qtde_alocada) {

                                                    $qtde_alocar = $pendencia_item;

                                                } else { //As pendências no item são maiores que as pendências do que ainda não foi alocado, então tudo o que não foi alocado pode ser alocado neste item

                                                    $qtde_alocar = $qtde_faturada - $qtde_alocada;

                                                }

                                                $sql_nf = "SELECT * FROM tbl_os_item_nf WHERE nota_fiscal = '$nota_fiscal' AND os_item = $os_item;";
                                                $res_nf = pg_query($con, $sql_nf);
                                                $valor_peca = 0 ; 
                                                if(strlen($total_peca) > 0){
                                                    $valor_peca = $total_peca / $qtde_faturada;
                                                    $valor_peca = number_format($valor_peca, 2);
                                                }else{
                                                    $valor_peca = 0;
                                                }

                                                if (pg_num_rows($res_nf)) {

                                                    $sql = "UPDATE tbl_os_item_nf
                                                               SET data_nf = '$data',
                                                               valor_st = $valor_peca
                                                             WHERE nota_fiscal = '$nota_fiscal'
                                                               AND os_item = $os_item;";

                                                } else {

                                                    $sql = "INSERT INTO tbl_os_item_nf(
                                                                os_item,
                                                                qtde_nf,
                                                                nota_fiscal,
                                                                data_nf,
                                                                valor_st
                                                            ) VALUES (
                                                                $os_item,
                                                                $qtde_alocar,
                                                                '$nota_fiscal',
                                                                '$data',
                                                                $valor_peca
                                                            )";

                                                }

                                                if (pg_query($con, $sql)) {
                                                        if (strlen($os_item) > 0) {
                                                                $sql = "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(tbl_os.os) FROM tbl_os_produto JOIN tbl_os_item USING(os_produto) WHERE os_item = $os_item AND tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $fabrica;

                                                                        UPDATE tmp_black_retsspnf SET
                                                                             os_id = $os
                                                                       WHERE tmp_black_retsspnf.linha = $linha ";
                                                            pg_query($con,$sql);
                                                            $log_cliente[] = "Linha: {$linha} - Pedido {$seu_pedido} atualizado!";

                                                                $sql = "SELECT tbl_os.sua_os,tbl_posto_fabrica.codigo_posto,
                                                                    tbl_admin.email
                                                                        FROM tbl_os_troca
                                                                        JOIN tbl_os ON tbl_os.os = tbl_os_troca.os
                                                                        LEFT JOIN tbl_admin ON tbl_os_troca.admin = tbl_admin.admin 
                                                                        OR tbl_os.admin = tbl_admin.admin AND tbl_admin.fabrica = $fabrica
                                                                        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
                                                                        WHERE tbl_os.os = $os
                                                                        AND tbl_admin.fale_conosco IS TRUE
                                                                        AND tbl_os.fabrica = $fabrica";
                                                                $resm = pg_query($con, $sql);

                                                                if (pg_num_rows($resm) > 0) {
                                                                    $sua_os_black = pg_fetch_result($resm, 0, 'sua_os');
                                                                    $codigo_posto = pg_fetch_result($resm, 0, 'codigo_posto');
                                                                    $email_admin = pg_fetch_result($resm, 0, 'email');

                                                                    $envia_msg_email_troca = true;


                                                                    list($yi, $mi, $di) = explode("-", $data);

                                                                    $data_nf = "$di/$mi/$yi";

                                                                    $msg_email_troca_msg = "A informação da NF {$nota_fiscal} data de emissão {$data_nf} foi carregada na ordem de serviço ".$codigo_posto.$sua_os_black.".";

                                                                    $msg_email_troca_subject = "NF da OS ".$codigo_posto.$sua_os_black.".";

                                                                    $mailer_troca = new PHPMailer();
                                                                    //$mailer->IsSMTP();
                                                                    $mailer_troca->IsHTML();
                                                                    $mailer_troca->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");

                                                                    $mailer_troca->AddAddress($email_admin);

                                                                    $mailer_troca->Subject = $msg_email_troca_subject;

                                                                    $mensagem = $msg_email_troca_msg;

                                                                    $mailer_troca->Body = $mensagem;
                                                                    $mailer_troca->Send();
                                                                        

                                                                }        
                                                        }
                                                }

                                                $qtde_alocada += $qtde_alocar;
                                            }

                                            //Tem que conseguir dar insert de toda a quantidade faturada na tbl_os_item_nf,
                                            //senão pode ser falha (pedido ou itens cancelados na Telecontrol,
                                            //pedido ou itens já atendidos totalmente qtde=qtde_faturada, etc)
                                            if ($qtde_faturada != $qtde_alocada) {

                                                $log[] = Date('d/m/Y H:i:s ').$erro = "A quantidade total de itens pendentes no pedido '{$seu_pedido} - {$referencia}' na Telecontrol é menor que a quantidade faturada";
                                                $log_cliente_erro[] = $erro;

                                            }

                                        }

                                    }

                                    $sql = "INSERT INTO tbl_pendencia_bd_novo_nf (
                                                pedido,
                                                referencia_peca,
                                                qtde_embarcada,
                                                data,
                                                nota_fiscal,
                                                sequencial_nf,
                                                transportadora_nome,
                                                conhecimento,
                                                posto,
                                                tipo,
                                                peca,
                                                pedido_banco,
                                                os,
                                                valor_st
                                            ) SELECT DISTINCT tmp_black_retsspnf.pedido,
                                                     tmp_black_retsspnf.referencia,
                                                     tmp_black_retsspnf.qtde_faturada,
                                                     tmp_black_retsspnf.data::date,
                                                     tmp_black_retsspnf.nota_fiscal,
                                                     tmp_black_retsspnf.sequencial,
                                                     tmp_black_retsspnf.transportadora,
                                                     tmp_black_retsspnf.conhecimento,
                                                     (SELECT posto FROM tbl_pedido WHERE pedido = tmp_black_retsspnf.pedido) AS pedido,
                                                     tmp_black_retsspnf.tipo,
                                                     tmp_black_retsspnf.peca,
                                                     tmp_black_retsspnf.pedido,
                                                     tmp_black_retsspnf.os_id,
                                                     tmp_black_retsspnf.total_peca
                                                FROM tmp_black_retsspnf
                                               WHERE tmp_black_retsspnf.linha      = $linha
                                                 AND tmp_black_retsspnf.data_input IS NULL;";

                                    if (!pg_query($con, $sql)) {

                                        $log[]           = Date('d/m/Y H:i:s ') . $erro = "Erro fazer insert na tbl_pendencia_bd_novo_nf";
                                        $notificar_falha = 1;
                                        pg_query($con, "ROLLBACK TRANSACTION;");

                                    } else {
                                        pg_query($con, "COMMIT TRANSACTION;");

                                    }

                                }

                            }

                        } else {

                            $log_cliente[] = "Linha: {$linha} - Pedido {$seu_pedido} e  peça {$referencia} já faturado anteriormente ou a peça não foi encontrada";

                        }

                    }
        }

    }

    if (count($log) > 0 && ENV != "teste") {

        $log[] = Date('d/m/Y H:i:s ')."Fim do Programa";

        fputs($fl,implode("\r\n", $log));
        fclose ($fl);

    }

    /*
    //$log_cliente[] = pg_last_error($con);
    if (count($log_cliente) > 0) {
        $log_cliente[] = "Fim do Programa";
        fputs($fl_cliente, implode("\r\n", $log_cliente));
        fclose($fl_cliente);
    }
    */

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

    //$log_cliente[] = pg_last_error($con);
    if (count($logs) > 0) {
        fputs($fl_cliente, implode("\r\n", $logs));
        fclose($fl_cliente);
    }

    $sql = "UPDATE tbl_perl_processado
               SET qtde_integrado  = {$qtde_integrado},
                   log             = 'Atualizando com sucesso!',
                   fim_processo    = NOW()
             WHERE perl_processado = {$perl_processado};";

    pg_query($con, $sql);

    if (filesize($origem.$arquivo) > 0) {
        //$log[] = "Movendo arquivo para $arquivos/blackedecker/nao_bkp/arquivos/$arquivo-$data_sistema.txt";
        mkdir("{$arquivos}/blackedecker/nao_bkp/arquivos/",0777,true);
        system("mv {$origem}{$arquivo} {$arquivos}/blackedecker/nao_bkp/arquivos/{$arquivo}-{$data_sistema}.txt;");
    }

    $mailer = new PHPMailer();
    //$mailer->IsSMTP();
    $mailer->IsHTML();
    $mailer->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");

    $emails = explode(",", $destinatarios_clientes);

    if (count($emails)) {
        foreach ($emails as $email) {
            $mailer->AddAddress($email);
        }
    } else {
        $mailer->AddAddress($destinatarios_clientes);
    }

    $arquivo_anexo = Date("d-m-Y") . "-LOGS-FATURAMENTO_PEDIDO.TXT";

    $mailer->Subject = "Logs: Faturamento de Pedido";

    $arquivo_completo_anexo = "{$arquivos}/blackedecker/nao_bkp/arquivos/{$arquivo_anexo}";
    $anexo                  = fopen($arquivo_completo_anexo, "w+");

    fputs($anexo, implode("\r\n", $logs));
    fclose($anexo);

    if (file_exists($arquivo_importacao)) {
		system("cp $arquivo_importacao {$arquivos}/blackedecker/nao_bkp/arquivos/;");
    }

    if (file_exists($arquivo_completo_anexo)) {
		system("cd {$arquivos}/blackedecker/nao_bkp/arquivos/; rm log_faturamento_pedido-$data_sistema.zip;  zip log_faturamento_pedido-$data_sistema.zip  $arquivo_anexo {$arquivo}-{$data_sistema}.txt");

    }

    //system("cp $arquivo_completo_anexo /home/ederson/public_html/assist/Logs.txt;");

    $zip = "$arquivos/blackedecker/nao_bkp/arquivos/log_faturamento_pedido-$data_sistema.zip";

    $mensagem = "Logs 'Faturamento de Pedido'<br>";
    if (file_exists($zip)) {
        $mailer->AddAttachment("{$zip}");
        $mensagem .= "Mensagem segue em anexo!<br><br>";
    } else {
        $mensagem .= "Mensagem sem anexo!<br><br>";
    }

    $mensagem .= "<br><br>Att.<br>Telecontrol Networking";

    $mailer->Body = $mensagem;
    if(!$mailer->Send())
        throw new Exception ($mailer->ErrorInfo);

} catch (Exception $e) {
    $msg = "Arquivo: ".__FILE__."<br>Linha: " . $e->getLine() . "<br><br>Descrição: " . $e->getMessage();
    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar importa novas pendencias", $msg);
}
