<?php
    //Este programa está configurado somente para Fabrica 03 - Britania

    //var_dump($argv);

    define('ENV','production'); // production Alterar para produção ou algo assim

	try {

        include "dbconfig.php";
        include "includes/dbconnect-inc.php";
		include "../rotinas/funcoes.php";

		$data_atual 		 = date('Ymd_His');
        $data['fabrica']            = 'britania';

		if (ENV == 'teste' ) {
            //$argv[1] = 'agrupado';
            $data['xls']   			= "/home/lucas/public_html/PosVendaAssist/admin/xls/";
		} else {
            mkdir ($data['diretorio'], 0777, true);
            //require "/www/cgi-bin/britania/britania-ftp-php.cfg";
             $data['xls']   		= "/www/assist/www/admin/xls/";
		}

		$data['diretorio'] 			= '/tmp/';//'/tmp/' . $data['fabrica'] . '/';
        $data['login_fabrica']      = 3;
		$data['tipo']               = $argv[1];
        $data['valor']              = $argv[2];
		$data['dest']               = 'ederson.sandre@telecontrol.com.br';
		$data['log']                = 2;
        $data['arquivo_nome']       = "exporta-extrato-tipo-{$data['tipo']}.txt";
        $data['tmp_fabricante']     = "/tmp/{$data['fabrica']}/";
        $data['arquivo_completo']   = $data['tmp_fabricante'].$data['arquivo_nome'];

        @mkdir($data['tmp_fabricante'], 0777, true);

		extract($data);

        if($tipo == 'nota_avulsa'){
	        $sql = "SELECT 	DISTINCT tbl_posto_fabrica.codigo_posto,
				   	tbl_posto.nome,
				  	tbl_extrato_conferencia.caixa,
				   	replace(to_char(tbl_extrato_nota_avulsa.valor_original,'9999999D99'),'.',',') AS valor_total,
				   	tbl_extrato_nota_avulsa.nota_fiscal,
					to_char(tbl_extrato_nota_avulsa.data_emissao,'DD/MM/YYYY') AS data_nf,
				   	to_char(tbl_extrato_nota_avulsa.previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento,
					tbl_extrato_nota_avulsa.cfop,
					tbl_extrato_nota_avulsa.codigo_item,
					tbl_posto_fabrica.codigo_posto AS emitente,
					tbl_extrato_conferencia.serie AS serie,
					tbl_extrato_nota_avulsa.nota_fiscal AS documento,
					tbl_extrato_nota_avulsa.cfop AS natureza_operacao,
					'SERVIÇO' as obs,
					tbl_extrato_nota_avulsa.estabelecimento,
					to_char(tbl_extrato_nota_avulsa.data_emissao,'DD/MM/YYYY') AS emissao,
					to_char(tbl_extrato_nota_avulsa.data_lancamento,'DD/MM/YYYY') AS data_transacao,
					'1' As qtde_emitente,
					replace(to_char(tbl_extrato_nota_avulsa.valor_original,'9999999D99'),'.',',') AS preco_total,
					'MO' as especie,
					tbl_extrato_nota_avulsa.nota_fiscal AS duplicata,
					replace(to_char(tbl_extrato_nota_avulsa.valor_original,'9999999D99'),'.',',') AS valor_duplicata,
					(CASE when
						(
						SELECT tbl_extrato_tipo_nota.descricao
						FROM tbl_extrato_tipo_nota
						WHERE tbl_extrato_tipo_nota.cfop = tbl_extrato_conferencia.cfop
						AND tbl_extrato_tipo_nota.codigo_item = tbl_extrato_conferencia.codigo_item
						)   
					NOTNULL
					THEN 
						(
						SELECT tbl_extrato_tipo_nota.descricao
						FROM tbl_extrato_tipo_nota
						WHERE tbl_extrato_tipo_nota.cfop = tbl_extrato_conferencia.cfop
						AND tbl_extrato_tipo_nota.codigo_item = tbl_extrato_conferencia.codigo_item
						)  
						ELSE
						(
						SELECT tbl_extrato_tipo_nota.descricao || '-' || tbl_extrato_tipo_nota_excecao.estado
						FROM tbl_extrato_tipo_nota
						JOIN tbl_extrato_tipo_nota_excecao USING(extrato_tipo_nota)
						WHERE tbl_extrato_tipo_nota_excecao.cfop = tbl_extrato_conferencia.cfop
						AND tbl_extrato_tipo_nota_excecao.codigo_item = tbl_extrato_conferencia.codigo_item
						AND contato_estado = tbl_extrato_tipo_nota_excecao.estado
						)   
					END
					) AS tipo_nota
			FROM  	tbl_extrato
			JOIN  	tbl_posto_fabrica USING(posto,fabrica)
			JOIN  	tbl_posto ON (tbl_posto_fabrica.posto = tbl_posto.posto)
			JOIN  	tbl_extrato_nota_avulsa USING(extrato)
			LEFT JOIN  tbl_extrato_conferencia USING(extrato)
			WHERE 	tbl_extrato_nota_avulsa.fabrica = $login_fabrica
			AND  	tbl_extrato_nota_avulsa.cfop NOTNULL
			AND   	tbl_extrato_nota_avulsa.extrato_nota_avulsa in ( $valor )
			AND   cancelada IS NOT TRUE;";
        }elseif($tipo == 'agrupado'){
            $sql = "SELECT 	DISTINCT tbl_posto_fabrica.codigo_posto,
				   	tbl_posto.nome,
				  	tbl_extrato_conferencia.caixa,
				   	replace(to_char(CASE WHEN tbl_extrato.valor_agrupado > 0 THEN tbl_extrato.valor_agrupado ELSE tbl_extrato_conferencia.valor_nf END ,'9999999D99'),'.',',') AS valor_total,
				   	tbl_extrato_conferencia.nota_fiscal,
					to_char(tbl_extrato_conferencia.data_nf,'DD/MM/YYYY') AS data_nf,
				   	to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento,
					tbl_extrato_conferencia.cfop,
					tbl_extrato_conferencia.codigo_item,
					tbl_posto_fabrica.codigo_posto AS emitente,
					tbl_extrato_conferencia.serie AS serie,
					tbl_extrato_conferencia.nota_fiscal AS documento,
					tbl_extrato_conferencia.cfop AS natureza_operacao,
					'SERVIÇO' as obs,
					tbl_extrato_conferencia.estabelecimento,
					to_char(tbl_extrato_conferencia.data_nf,'DD/MM/YYYY') AS emissao,
					to_char(tbl_extrato_conferencia.data_lancamento_nota,'DD/MM/YYYY') AS data_transacao,
					'1' As qtde_emitente,
				   	replace(to_char(CASE WHEN tbl_extrato.valor_agrupado > 0 THEN tbl_extrato.valor_agrupado ELSE tbl_extrato_conferencia.valor_nf END ,'9999999D99'),'.',',') AS preco_total,
					'MO' as especie,
					tbl_extrato_conferencia.nota_fiscal AS duplicata,
				   	replace(to_char(CASE WHEN tbl_extrato.valor_agrupado > 0 THEN tbl_extrato.valor_agrupado ELSE tbl_extrato_conferencia.valor_nf END ,'9999999D99'),'.',',') AS valor_duplicata,
					(CASE when
						(
						SELECT tbl_extrato_tipo_nota.descricao
						FROM tbl_extrato_tipo_nota
						WHERE tbl_extrato_tipo_nota.cfop = tbl_extrato_conferencia.cfop
						AND tbl_extrato_tipo_nota.codigo_item = tbl_extrato_conferencia.codigo_item
						)   
					NOTNULL
					THEN 
						(
						SELECT tbl_extrato_tipo_nota.descricao
						FROM tbl_extrato_tipo_nota
						WHERE tbl_extrato_tipo_nota.cfop = tbl_extrato_conferencia.cfop
						AND tbl_extrato_tipo_nota.codigo_item = tbl_extrato_conferencia.codigo_item
						)  
						ELSE
						(
						SELECT tbl_extrato_tipo_nota.descricao || '-' || tbl_extrato_tipo_nota_excecao.estado
						FROM tbl_extrato_tipo_nota
						JOIN tbl_extrato_tipo_nota_excecao USING(extrato_tipo_nota)
						WHERE tbl_extrato_tipo_nota_excecao.cfop = tbl_extrato_conferencia.cfop
						AND tbl_extrato_tipo_nota_excecao.codigo_item = tbl_extrato_conferencia.codigo_item
						AND contato_estado = tbl_extrato_tipo_nota_excecao.estado
						)   
					END
					) AS tipo_nota
				FROM  tbl_extrato
				JOIN  tbl_posto_fabrica       USING(posto,fabrica)
				JOIN  tbl_posto               USING(posto)
				JOIN  tbl_extrato_conferencia USING(extrato)
				JOIN  tbl_extrato_agrupado    USING(extrato)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   data_lancamento_nota::date = CURRENT_DATE
				AND   tbl_extrato_conferencia.cfop NOTNULL
				AND   cancelada IS NOT TRUE 
				ORDER BY tbl_posto_fabrica.codigo_posto;";
        }else{ 
           $msg_erro = "Não foi detectado o tipo de parâmetro para executar as consulta SQL";
           throw new Exception($msg_erro);
        }
        
        //echo die(nl2br($sql));
        $res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
        if(!empty($msg_erro)){
            $msg_erro = pg_errormessage($con);
            throw new Exception($msg_erro);
        }else{ 	
            if(pg_num_rows($res) >  0){
               for($i = 0; pg_num_rows($res) > $i; $i++){
                   $init = $i == 0 ? "" : $i;
                   
                    extract(pg_fetch_array($res));

					$linha  = "A".$init.";";
					$linha .= $codigo_posto.";";
					$linha .= $nome.";";
					$linha .= $caixa.";";
					$linha .= $valor_total.";";
					$linha .= $nota_fiscal.";";
					$linha .= $data_nf.";";
					$linha .= $previsao_pagamento.";";
					$linha .= $tipo_nota.";";
					$linha .= $cfop.";";
					$linha .= $codigo_item.";";
					$linha .= $emitente.";";
					$linha .= $serie.";";
					$linha .= $documento.";";
					$linha .= $natureza_operacao.";";
					$linha .= $obs.";";
					$linha .= $estabelecimento.";";
					$linha .= $emissao.";";
					$linha .= $data_transacao.";";
					$linha .= $qtde_emitente.";";
					$linha .= $preco_total.";";
					$linha .= $especie.";";
					$linha .= $duplicata.";";
					$linha .= $valor_duplicata;
						
					$linhas[] = $linha;

					//echo $linha;
                   //$linha[] = "A{$init};".implode(";", pg_fetch_array($res));
               } 

               //if(count($linha) > 0){
                    $fp = fopen ($arquivo_completo,"w+");
                    fputs($fp,implode("\r\n", $linhas));
                    fclose ($fp);
               //}

                // Envia o arquivo para o FTP do cliente
                /*
                $ftphost = "187.59.7.245";
                $ftpuser = "akacia";
                $ftppass = "britania2009";
                $destino = "Entrada";
                */
                	
                /*
                $ftp = ftp_connect($ftphost);
                if(!$ftp){
                  $msg_erro = "Erro ao conectar no - FTP: {$ftphost} - {$fabrica}!\n";
                  throw new Exception ($msg_erro);      
                }

                $login = ftp_login($ftp, $ftpuser, $ftppass);
                if(!$login){
                  ftp_close($ftp);
                  $msg_erro = "Erro ao efetuar login no - FTP: {$ftphost} - {$fabrica}!\n";
                  throw new Exception ($msg_erro);      
                }

                ftp_pasv($ftp, true);

                if(file_exists($arquivo_completo)){
                    if(!ftp_put($ftp, "Entrada/integracao_ems.txt", $arquivo_completo, FTP_ASCII)){
                        $msg_erro = "Erro: Não foi possivel enviar o arquivo via FTP!\n";
                        throw new Exception ($msg_erro);    
                    }

                    copy($arquivo_completo, $tmp_fabricante.$data_atual."-".$arquivo_nome);

                }else{
                  ftp_close($ftp);
                  $msg_erro = "Erro: Arquivo não encontrado para ser enviar para o FTP: {$ftphost} - {$fabrica}!\n";
                  throw new Exception ($msg_erro);    
                }

                ftp_close($ftp);
                */
            }
        }

        if(file_exists($arquivo_completo)){
        	//copy($arquivo_completo, $tmp_fabricante.$data_atual."-".$arquivo_nome);
        	//copy($arquivo_completo, $xls."integracao_ems.txt");

        	system("cp {$arquivo_completo} {$tmp_fabricante}{$data_atual}-{$arquivo_nome}");
        	system("cp {$arquivo_completo} {$xls}integracao_ems.txt");

        	system("cd {$xls}; rm -rf {$xls}integracao_ems.zip; zip {$xls}integracao_ems.zip integracao_ems.txt  >> /dev/null;");
        }

	}catch (Exception $e) {
		$msg = 'Arquivo: '.__FILE__.'<br />Erro na linha: ' . $e->getLine() . ':<br />Erro descrição: ' . $e->getMessage();
		Log::envia_email($data,"$data_atual - Erro ao exportar arquivo", $msg);
	}
