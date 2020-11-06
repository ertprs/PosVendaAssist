<?php
/**
 *
 * importa-pecas.php
 *
 * Importação peças Black&Decker
 *
 * @author  Ronald Santos
 * @version 2012.01.04
 * @version 2013.02.06 - alteração de alíquota ICMS
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // production Alterar para produção ou algo assim
// define('ENV','teste');  // utilizar em ambiente de teste

try {
    $data_log['login_fabrica']      = 1;
    $data_log['dest']               = 'helpdesk@telecontrol.com.br';
    $data_log['log']                = 2;

    date_default_timezone_set('America/Sao_Paulo');
    $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $fabrica = 1;
    $perl = 7;
    $arquivo = "pecas.txt";

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

    $notificar_falha = 0;
    $destinatarios_tecnicos = "";
    $destinatarios_clientes = "mribeiro\@blackedecker.com.br , rberto\@blackedecker.com.br , rfernandes\@blackedecker.com.br";
//     $destinatarios_clientes = "william.brandino@telecontrol.com.br";

	if (ENV == 'teste' ) {
        $origem_arq     = dirname(__FILE__) . '/tests/';
        $origem_cliente = dirname(__FILE__) . '/tests/';
        mkdir($origem_cliente, 0777, true);
        $arquivos = "./"; //UTILIZAR EM AMBIENTE DE TESTE */
    } else {
        $origem_arq     = "/home/blackedecker/black-telecontrol/";
        $origem_cliente = "/home/blackedecker/telecontrol-black/";
        $arquivos = "/tmp";
    }

    $data_sistema = Date('Y-m-d');
	if(!is_dir("$arquivos/blackedecker/nao_bkp/log/")){
		mkdir("$arquivos/blackedecker/nao_bkp/log/", 0777, true);
	}
    $arquivo_log = "$arquivos/blackedecker/nao_bkp/log/importa-pecas-$data_sistema.log";
    $arquivo_err = "$arquivos/blackedecker/nao_bkp/log/importa-pecas-$data_sistema.err";
    $arquivo_log_cliente = "$arquivos/blackedecker/nao_bkp/log/importa-pecas-".$data_sistema."_cliente.log";
	$arquivo_log_status_removido = "$arquivos/blackedecker/nao_bkp/log/email-log-importacao-peca.txt";
	$arquivo_log_depara = "$arquivos/blackedecker/nao_bkp/log/email-erro-importacao-peca-depara.txt";
	$arquivo_importacao = $origem_arq.$arquivo;

	$fl                 = fopen($arquivo_log,"w+");
	$fl_cliente         = fopen($arquivo_log_cliente,"w+");
	$fl_status_removido = fopen($arquivo_log_status_removido,"w+");
	$fl_depara          = fopen($arquivo_log_depara,"w+");
	$file               = fopen($arquivo_importacao,"r");
	$fl_erro            = fopen($arquivo_err,"w+");
	 //Insere na tabela o inicio do processamente!
    $sql = "INSERT INTO tbl_perl_processado (perl) VALUES ($perl) RETURNING perl_processado;";
    if($res = pg_query ($con,$sql)){
		$perl_processado = pg_fetch_result($res,0,'perl_processado');
    }else{
        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Erro ao registrar PERL\n";
        fputs($fl,implode("\n", $log));
        fclose ($fl);
        throw new Exception ($msg_erro);
    }

    //Verifica o arquivo de log, se existe e pode ser lido!
    if (!is_resource($fl)) {
        $log[] = Date('d/m/Y H:i:s ').$msg_erro = "O Arquivo {$arquivo_log} não pode ser lido!";
        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        fputs($fl,implode("\n", $log));
        fclose ($fl);
        throw new Exception ($msg_erro);
    }

    //Verifica se o arquivo existe e pode ser lido!
    if (!is_resource($file)) {
        $log[] = Date('d/m/Y H:i:s ').$msg_erro = "O Arquivo {$arquivo_importacao} não pode ser lido!";
        fputs($fl,implode("\n", $log));
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
    $log_cliente[] = "Incio do Programa";

    if($total_linha > 0){
        $sql = "UPDATE tbl_perl_processado SET qtde_integrar = '{$total_linha}' WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
    }else{
        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Arquivo {$arquivo_importacao} está vazio!";
        fputs($fl,implode("\n", $log));
        fclose ($fl);
        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        throw new Exception ($msg_erro);
    }

	$sql = "DROP TABLE tmp_black_peca_log;";
	$sql .= "CREATE TABLE tmp_black_peca_log (linha text,conteudo_linha text,referencia text, status text, ncm text, unitario text, coletiva text, estoque text, previsao text, qtde_demanda text);";
	if(!pg_query($con,$sql)){
        $log[] = $msg_erro = Date('d/m/Y H:i:s ')."Erro ao criar tmp_black_peca_log!";
        fputs($fl,implode("\n", $log));
        fclose ($fl);
        $sql = "UPDATE tbl_perl_processado SET log = '{$msg_erro}', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query ($con,$sql);
        throw new Exception ($msg_erro);
    }

	$log[] = Date('d/m/Y H:i:s ')."Inserindo informações na temporária";


    for($i = 0; $i < $total_linha; $i++){ //$total_linha
        $dados = explode(";",$linha[$i]);

		   if(count($dados) == 17){
			   	$conteudo_linha     = implode(";",array_filter($dados,'trim'));

				$referencia         = trim($dados[0]);
				$descricao          = trim($dados[1]);
				$tipo               = trim($dados[2]);
				$grupo              = trim($dados[3]);
				$status             = strtoupper(trim($dados[4]));
				$substituto         = trim($dados[5]);
				$ipi                = trim($dados[6]);
				$preco              = trim($dados[7]);
				$multiplo           = trim($dados[8]);
				$ncm                = trim($dados[9]);
				$classificacao_fiscal = trim($dados[10]);
				$icms_mg            = trim($dados[11]);
				$unitario           = trim($dados[12]);
				$coletiva           = trim($dados[13]);

                $estoque            = trim($dados[14]);
                $previsao           = trim($dados[15]);
                $qtde_demanda       = trim($dados[16]);

                // feito esse tratamento na data no hd-2930301
				// a black não tinha como mudar de dd/mm/aa para aaaa-mm-dd
				if(!empty($previsao)){
					$previsao_temp = DateTime::createFromFormat('d/m/y', $previsao);
					if(!empty($previsao_temp))
						$previsao = $previsao_temp->format('Y-m-d');
				}

				$referencia         = (!empty($referencia)) ? strtoupper($referencia)   : "null";
				$descricao          = (!empty($descricao))  ? $descricao                : "null";
				$descricao          = str_replace("'","",$descricao);
				$tipo               = (!empty($tipo))       ? $tipo                     : "null";
				$grupo              = (!empty($grupo))      ? $grupo                    : "null";
				$substituto         = (!empty($substituto)) ? $substituto               : "null";
				$ipi                = (!empty($ipi))        ? $ipi                      : "null";
				$preco              = (!empty($preco))      ? $preco                    : 0;
				$multiplo           = (!empty($multiplo))   ? $multiplo                 : 1;
				$ncm                = (!empty($ncm))        ? "'$ncm'"                  : "null";
				$unitario           = (!empty($unitario))   ? $unitario             : "null";
				$coletiva           = (!empty($coletiva))   ? $coletiva             : "null";

                $estoque            = (!empty($estoque))    ? $estoque              : "null";
                $previsao           = (!empty($previsao))   ? $previsao             : "";
                $qtde_demanda       = (!empty($qtde_demanda))   ? $qtde_demanda         : "";


				if ($classificacao_fiscal == '0') {
					$classificacao_fiscal = "'$classificacao_fiscal'";
				} else {
					$classificacao_fiscal = (int) $classificacao_fiscal;

					if (in_array($classificacao_fiscal, range(1, 8))) {
						$classificacao_fiscal = "'$classificacao_fiscal'";
					} else {
						$classificacao_fiscal = 'NULL';
					}
				}

                if($status == "OBSOLETO"){
                    $estoque = "Obsoleto";
                }

                if($status == "SUBST"){
                    $estoque = "SUBST";
                }

                if ($status == "IMPINAT"){
                    $estoque = "Indisponivel";
                    if(strlen(trim($previsao))==0){
                        $previsao = " - ";
                    }
                }

				$parametros = array(
                    "caixa_unitario" => $unitario,
                    "caixa_coletiva" => $coletiva,
                    "estoque" => $estoque,
                    "previsao" => $previsao
				);
				$parametros_adicionais = json_encode($parametros);

				// var_dump($classificacao_fiscal); exit;

				$preco_garantia = $preco * 0.75;
				$unidade = "PC";

				$sql = "INSERT INTO tmp_black_peca_log (
							linha,
							conteudo_linha,
							referencia,
							status,
							ncm,
							unitario,
							coletiva,
                            estoque,
                            previsao,
                            qtde_demanda
					   ) VALUES (
							'{$i}',
							'{$conteudo_linha}',
							'{$referencia}',
							'{$status}',
							{$ncm},
							'{$unitario}',
							'{$coletiva}',
                            '$estoque',
                            '$previsao',
                            '$qtde_demanda'
						);";
				if(!pg_query ($con,$sql)){
					$log[] = "Linha {$i}: Erro ao gravar as informações na tabela tmp_black_peca_log;";
					$log_erro[] = "Linha {$i}: ".pg_last_error()."<br>" . $sql;
				}
			}else{
		            $log[] = $msg_erro = "Linha {$i}: Arquivo fora do layout";
			}

			switch($tipo){
				case 'SPBEN': $origem = "TER";
				case 'SPN'  : $origem = "TER";
				case 'SPPCN': $origem = "TER"; break;
				case 'SPCJF': $origem = "NAC";
				case 'SPPCF': $origem = "NAC"; break;
				case 'SPI'  : $origem = "IMP";
				case 'SPPCI': $origem = "IMP"; break;
				case 'SPSF': $origem = "FAB/SUB"; break;
				case 'SPSI': $origem = "IMP/SUB"; break;
				case 'SPSN': $origem = "TER/SUB"; break;
				case 'SPSAN': $origem = "FAB/SA"; break;
				case 'SPSAI': $origem = "IMP/SA"; break;
			}

			switch($grupo){
				case 'SPPPT'  : $xgrupo = "198";
				case 'SPIPG'  : $xgrupo = "198";  break;
				case 'SPHH'   : $xgrupo = "199";  break;
				case 'SPCPT'  : $xgrupo = "200";
				case 'SPCPG'  : $xgrupo = "200";  break;
				case 'SPHHI'  : $xgrupo = "500";  break;
				case 'SPCOMP' : $xgrupo = "null"; break;
			}

			if(!empty($ipi)){
				$ipi_agregado = 1 + ($ipi / 100);
			} else {
				$ipi_agregado = 1;
			}

			$res = pg_query($con,'BEGIN TRANSACTION');

			# PRECO: colocar na tabela de preco
			### VERIFICA NA TABELA EXISTÊNCIA DA PECA
			$sql = "SELECT tbl_peca.peca,tbl_peca.parametros_adicionais
						FROM   tbl_peca
						WHERE  tbl_peca.referencia = '$referencia'
						AND    tbl_peca.fabrica    = $fabrica;
					";
			$res = pg_query($con,$sql);
		    $insert = "";
			if(pg_numrows($res) == 0){
				$sql = "INSERT INTO tbl_peca (
						fabrica                 ,
						referencia              ,
						descricao               ,
						origem                  ,
						unidade                 ,
						ipi                     ,
						ipi_agregado            ,
						multiplo                ,
						linha_peca              ,
						ncm                     ,
						classificacao_fiscal    ,
						parametros_adicionais
					) VALUES (
						$fabrica                ,
						'$referencia'           ,
						'$descricao'            ,
						'$origem'               ,
						'$unidade'              ,
						'$ipi'                  ,
						'$ipi_agregado'         ,
						$multiplo               ,
						$xgrupo                 ,
						$ncm                    ,
						$classificacao_fiscal   ,
						'$parametros_adicionais'
					) RETURNING peca;
					";
					$log_cliente[] = "Peça $referencia - $descricao inserida com sucesso!";
					$insert = true;
			} else {
				$peca = pg_fetch_result($res,0,"peca");
                $parametros_adicionais = pg_fetch_result($res,0,"parametros_adicionais");

                $parametros_adicionais = json_decode($parametros_adicionais,true);

                $parametros_adicionais["estoque"] = $estoque;
                $parametros_adicionais["previsao"] = $previsao;
				$parametros_adicionais["qtde_demanda"] = $parametros_adicionais["qtde_demanda"];


                if ($unitario != 'null') {
                    $parametros_adicionais["caixa_unitario"] = utf8_encode($unitario);
                }else{
                    if (isset($parametros_adicionais["caixa_unitario"])) {
                        unset($parametros_adicionais["caixa_unitario"]);
                    }
                }
                if ($coletiva != 'null') {
                    $parametros_adicionais["caixa_coletiva"] = utf8_encode($coletiva);
                }else{
                    if (isset($parametros_adicionais["caixa_coletiva"])) {
                        unset($parametros_adicionais["caixa_coletiva"]);
                    }
                }
                if (count($parametros_adicionais) == 0) {
                    $parametros_adicionais = null;
                }
                $parametros_adicionais = json_encode($parametros_adicionais);
                if ($parametros_adicionais != 'null') {
                    $parametros_adicionais = "'".$parametros_adicionais."'";
                }

				$sql = "
                        UPDATE tbl_peca
                        SET     descricao               = '$descricao'              ,
                                origem                  = '$origem'                 ,
                                linha_peca              = $xgrupo                   ,
                                ipi                     = '$ipi'                    ,
                                ipi_agregado            = '$ipi_agregado'           ,
                                multiplo                = $multiplo                 ,
                                ncm                     = $ncm                      ,
                                classificacao_fiscal    = $classificacao_fiscal     ,
                                parametros_adicionais   = $parametros_adicionais
                        WHERE   fabrica    = $fabrica
                        AND     peca = $peca;";
					$log_cliente[] = "Peça $referencia - $descricao atualizada com sucesso!";
			}

			$res_peca = pg_query($con,$sql);
			// echo $sql , "\n";

			if(strlen(pg_last_error() > 0)){
				$log[] = "Linha {$i}: Erro ao gravar as informações na tabela tbl_peca;";
				$log_cliente[] = "Erro ao importar a peça $referencia - $descricao";
				$log_erro[] = "Linha {$i}: ".pg_last_error()."<br>" . $sql;

			}else{
				if(empty($peca)){
					$peca = pg_fetch_result($res_peca,0,"peca");
				}

				if(!empty($peca) ) {
					if($insert){
						$cmd_atualiza = "php /www/assist/www/rotinas/blackedecker/atualiza-peca-icms.php $peca";
						$void = exec("$cmd_atualiza");
					}

					if($icms_mg > 0 and !empty($classificacao_fiscal)) {
						$sqlmg = "DELETE from tbl_peca_icms
								WHERE estado_destino='MG'
								AND peca = $peca;

								INSERT INTO tbl_peca_icms(
								    fabrica,
									peca,
									estado_destino,
									codigo,
									indice
							)values(
									$fabrica,
									$peca,
									'MG',
									$classificacao_fiscal,
									'$icms_mg'
								)";
						$resmg = pg_query($con,$sqlmg);
					}
				}
			}
			# PRECO: colocar na tabela de preco
			### VERIFICA NA TABELA EXISTÊNCIA DA PECA
			$sql = "SELECT tbl_tabela_item.tabela_item
					FROM   tbl_tabela_item
					WHERE  tbl_tabela_item.peca =
						(
						SELECT peca
						FROM   tbl_peca
						WHERE  referencia = '$referencia'
						AND    fabrica = $fabrica
						)
					AND    tbl_tabela_item.tabela = 1053;
					";
			$res = pg_query($con,$sql);

			if(pg_numrows($res) == 0){
				$sql = "INSERT INTO tbl_tabela_item (
						tabela,
						peca  ,
						preco
					) VALUES (
						1053  ,
						(SELECT peca FROM tbl_peca WHERE referencia = '$referencia'
							AND fabrica = $fabrica),
						$preco
					);
					";
				$log_cliente[] = "Preço da peça  $referencia - $descricao inserico com sucesso!";

				if(!pg_query ($con,$sql)){
					$log[] = "Linha {$i}: Erro ao gravar as informações na tabela tbl_tabela_item;";
					$log_cliente[] = "Erro ao inserir o preço da peça $referencia - $descricao";
					$log_erro[] = "Linha {$i}: ".pg_last_error()."<br>" . $sql;
				}
			}else{
				$sql = "UPDATE tbl_tabela_item set preco = $preco where peca = $peca and tabela = 1053";
				$res = pg_query($con,$sql);
			}

			$sql = "SELECT tbl_tabela_item.tabela_item
				FROM   tbl_tabela_item
				WHERE  tbl_tabela_item.peca =
					(
					SELECT peca
					FROM   tbl_peca
					WHERE  referencia = '$referencia'
					AND    fabrica = $fabrica
					)
				AND    tbl_tabela_item.tabela = 1054;";
			$res = pg_query($con,$sql);

			if(pg_numrows($res) == 0){
				$sql = "INSERT INTO tbl_tabela_item (
						tabela,
						peca  ,
						preco
					) VALUES (
						1054   ,
						(SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $fabrica),
						$preco_garantia
					);";

				$log_cliente[] = "Preço da peça  $referencia - $descricao inserico com sucesso!";

				if(!pg_query ($con,$sql)){
					$log[] = "Linha {$i}: Erro ao gravar as informações na tabela tbl_tabela_item;";
					$log_cliente[] = "Erro ao inserir o preço da peça $referencia - $descricao";
					$log_erro[] = "Linha {$i}: ".pg_last_error()."<br>" . $sql;
				}
			}else{
				$sql = "UPDATE tbl_tabela_item set preco = $preco_garantia where peca = $peca and tabela = 1054";
				$res = pg_query($con,$sql);
			}

			# Status : se for OBSOLETO, gravar nas pecas fora de linha
			if ($status == "OBSOLETO") {
				### VERIFICA NA TABELA EXISTÊNCIA DA PECA
				$sql = "SELECT tbl_peca_fora_linha.peca_fora_linha
						FROM   tbl_peca_fora_linha
						WHERE  tbl_peca_fora_linha.referencia = '$referencia'
						AND    tbl_peca_fora_linha.fabrica    = $fabrica;
						";
				$res = pg_query($con,$sql);

				### INSERE NA TABELA
				if(pg_numrows($res) == 0){
					$sql = "INSERT INTO tbl_peca_fora_linha (
								fabrica   ,
								referencia,
								peca
							) VALUES (
								$fabrica   ,
								'$referencia',
								(SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $fabrica)
							);
							";
					if(!pg_query ($con,$sql)){
						$log[] = "Linha {$i}: Erro ao gravar as informações na tabela tbl_peca_fora_linha;";
						$log_erro[] = "Linha {$i}: ".pg_last_error()."<br>" . $sql;
					}
				}
			}

			# Status : se for IMPINAT, marcar bloqueada_garantia e bloqueada_venda
			if ($status == "IMPINAT") {

				$sql = "UPDATE  tbl_peca SET
										bloqueada_venda    = 't',
										bloqueada_garantia = 't'
						WHERE  tbl_peca.referencia = '$referencia'
						AND    tbl_peca.fabrica         = $fabrica;
						";

				$res = pg_query($con,$sql);

			}

			# Status : se for SUBST, gravar DE-PARA (pelo $substituto)
			if ($status == "SUBST" and $substituto != "null") {
				### VERIFICA NA TABELA EXISTÊNCIA DA PECA
				$sql = "SELECT tbl_depara.depara
						FROM   tbl_depara
						WHERE  tbl_depara.fabrica = $fabrica
						AND    tbl_depara.de      = '$referencia';
						";
				$res = pg_query($con,$sql);

				### INSERE NA TABELA
				if(pg_numrows($res) == 0){
					$sql = "INSERT INTO tbl_depara (
								fabrica  ,
								de       ,
								para
							) VALUES (
								$fabrica   ,
								'$referencia',
								upper ('$substituto')
							);
							";
					$log_cliente[] = "DE-PARA da peça $referencia para $substituto feito com sucesso";
					$depara_ins = "sim";
					if(!pg_query ($con,$sql)){
						$log[] = "Linha {$i}: Erro ao gravar as informações na tabela tbl_depara;";
						$log_depara[] = "Erro ao fazer DE-PARA da peça $referencia para $substituto";
					}

				}

				$sql = "SELECT tbl_peca.peca
						FROM   tbl_peca
						WHERE  tbl_peca.fabrica    = $fabrica
						AND    tbl_peca.referencia = upper(trim('$substituto'));";
				$res = pg_query($con,$sql);
				if(pg_numrows($res) == 0){
					$log[] = "Linha {$i}: DE-PARA '$substituto' não encontrada no sistema. Por favor, cadastrar-lá";
				} else {
					$peca_para = pg_result($res,0,peca);
				}

				/*Acertando estoque das peças com DE -> PARA*/
				$sql = "SELECT posto, qtde, tbl_estoque_posto.peca
					FROM  tbl_estoque_posto
					JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca AND tbl_peca.fabrica = $fabrica
					WHERE tbl_estoque_posto.fabrica = $fabrica
					AND tbl_peca.referencia = '$referencia'";
				$resx = pg_query($con,$sql);

				if(pg_numrows($resx) > 0 and $depara_ins == 'sim'){
					$erro_depara = "";
					for($x = 0; $x < pg_numrows($resx); $x++){
						$posto_aux = pg_result($resx,$x,posto);
						$qtde_aux  = pg_result($resx,$x,qtde);
						$peca_de  = pg_result($resx,$x,peca);

						if($qtde_aux > 0){
							$sqlS = "SELECT tbl_estoque_posto.peca
								 FROM tbl_estoque_posto
								 JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca AND tbl_peca.fabrica = $fabrica
								 WHERE tbl_estoque_posto.fabrica = $fabrica
								 AND posto = $posto_aux
								 AND tbl_peca.referencia =  upper(trim('$substituto'))";
							$resS = pg_query($con,$sqlS);

							if(pg_numrows($resS) > 0){
								$sqlU = "UPDATE tbl_estoque_posto SET qtde = qtde + $qtde_aux
									 WHERE fabrica = $fabrica
									 AND posto = $posto_aux
									 AND peca = $peca_para";
							} else {
								$sqlU = "INSERT INTO tbl_estoque_posto(
													fabrica,
													posto,
													peca,
													qtde
													) VALUES (
													$fabrica,
													$posto_aux,
													$peca_para,
													$qtde_aux
													)";
							}
							$resU = pg_query($con,$sqlU);
							$erro_depara = pg_errormessage($con);

							$sqlI = "INSERT INTO tbl_estoque_posto_movimento(
													fabrica,
													posto,
													peca,
													data,
													qtde_entrada,
													obs
													) VALUES (
													$fabrica,
													$posto_aux,
													$peca_para,
													current_date,
													$qtde_aux,
													'Saldo transferido do item $referencia - $descricao'
													)";
							$resI = pg_query($con,$sqlI);
							$erro_depara = pg_errormessage($con);

							$sqlP = "UPDATE tbl_estoque_posto SET qtde = 0
									 WHERE fabrica = $fabrica
									 AND posto = $posto_aux
									 AND peca = $peca_de";
							$resP = pg_query($con,$sqlP);
							$erro_depara = pg_errormessage($con);
							$sqlI = "INSERT INTO tbl_estoque_posto_movimento(
								fabrica,
								posto,
								peca,
								data,
								qtde_saida,
								obs
								) VALUES (
								$fabrica,
								$posto_aux,
								$peca_de,
								current_date,
								$qtde_aux,
								'Saldo transferido do item $referencia - $descricao'
								)";
							$resI = pg_query($con,$sqlI);
						}
					}

					if(!empty($erro_depara)){
						$log_depara[] = "Linha {$i}: Erro ao acertar estoque DE -> PARA da peca : $referencia - $descricao\n";
					}
				}
			}

	#Status : se for em branco, verifica se estava obsoleto ou depara e em caso posivito grava no LOG
	if ($status == "" || ($status != "SUBST" && $status != "OBSOLETO"  && $status != "IMPINAT")) {

			### VERIFICA NA TABELA peca_fora_linha EXISTÊNCIA DA PECA
			$sql = "INSERT INTO tmp_black_peca_log (referencia, status)
						SELECT x.referencia, x.status
						FROM (
							SELECT tbl_peca_fora_linha.referencia, 'OBSOLETO' as status
							FROM   tbl_peca_fora_linha
							WHERE  tbl_peca_fora_linha.referencia = '$referencia'
							AND    tbl_peca_fora_linha.fabrica    = $fabrica

							UNION

							SELECT tbl_depara.de as referencia, 'SUBST' as status
							FROM   tbl_depara
							WHERE  tbl_depara.fabrica = $fabrica
							AND    tbl_depara.de      = '$referencia'
						) as x;";
			if(!pg_query ($con,$sql)){
				$log[] = "Linha {$i}: Erro ao gravar as informações na tabela tmp_black_peca_log;";
				$log_erro[] = "Linha {$i}: ".pg_last_error()."<br>" . $sql;
			}

	    ### VERIFICA SE PEÇA ESTÁ EM tbl_peca_fora_linha
			$sqlVerificaPecaForaLinha = "SELECT peca_fora_linha,
							    peca
						  FROM tbl_peca_fora_linha
						  WHERE fabrica = {$fabrica} AND
						  referencia = '{$referencia}' \n";
			$resVerificaPecaForaLinha = pg_query($con, $sqlVerificaPecaForaLinha);
			if(pg_num_rows($resVerificaPecaForaLinha) > 0){

				$peca_fora_linha = pg_fetch_result($resVerificaPecaForaLinha, 0, "peca_fora_linha");
				$pecaAtivar = pg_fetch_result($resVerificaPecaForaLinha, 0, "peca");
				$deletePecaForaLinha = "DELETE FROM tbl_peca_fora_linha
							WHERE peca_fora_linha = $peca_fora_linha";

				if(!pg_query($con, $deletePecaForaLinha)){
				    $log[]  = "Erro ao deletar tbl_peca_fora_linha";
				}

				$ativaPeca =	"UPDATE tbl_peca SET ativo = 't'
						WHERE peca = $pecaAtivar ";
				pg_query($con, $ativaPeca);
			}
	    ### VERIFICA SE BLOQUEADA VENDA E BLOQUEADA FATURADA
			$sqlVerificaBloqueadaVendaEFaturada = "SELECT peca
								FROM tbl_peca
								WHERE fabrica = {$fabrica} AND
									referencia = '{$referencia}' AND
									bloqueada_venda IS TRUE  AND bloqueada_garantia IS TRUE";
			$resVerificaBloqueadaVendaEFaturada = pg_query($con, $sqlVerificaBloqueadaVendaEFaturada);

	                if(pg_num_rows($resVerificaBloqueadaVendaEFaturada) > 0){
				$pecaAtivar = pg_fetch_result($resVerificaBloqueadaVendaEFaturada, 0, "peca");
				$ativaEDesbloqueiaPeca = "UPDATE tbl_peca SET ativo = 't',
										bloqueada_venda = 'f',
										bloqueada_garantia = 'f'
							 WHERE peca = {$pecaAtivar}";
				if(!pg_query($con, $ativaEDesbloqueiaPeca)){
					$log[] = "Erro ao Ativar peça e desbloquear (bloqueada_garantia e bloqueada_venda)";
				}

			}

	    ### VERIFICA DEPARA
			$sqlPeca = "SELECT peca FROM tbl_peca
				    WHERE fabrica = {$fabrica} AND
			            referencia = '{$referencia}'";
			$resPeca = pg_query($con, $sqlPeca);
			if(pg_num_rows($resPeca) > 0){
				$idPeca = pg_fetch_result($resPeca, 0, "peca");

				$sqlVerificaDepara =   "SELECT depara
 						FROM tbl_depara
 						WHERE fabrica = {$fabrica} AND
 						peca_de = {$idPeca}";
	 			$resVerificaDepara = pg_query($con, $sqlVerificaDepara);

				if(pg_num_rows($resVerificaDepara) > 0 ){
					$deparaDeletar = pg_fetch_result($resVerificaDepara, 0, "depara");

					$deleteDepara = "DELETE FROM tbl_depara
 						 WHERE depara = $deparaDeletar";
					if(pg_query($con, $deleteDepara)){

						$ativarPeca = "UPDATE tbl_peca
 							SET ativo = 't'
 							WHERE peca  = {$idPeca} ";

	 					if(!pg_query($con, $ativarPeca)){

							$log[] = "Erro ao Ativar peça";
						}

					}
				}

			}


  	      }

  	      #Status :"INDISPL" deverá INATIVAR a peça
		if ($status == "INDISPL" or $status == 'INDISP') {

			$sql = "UPDATE  tbl_peca SET
									informacoes    = '{$status}',
									ativo = 'f'
					WHERE  tbl_peca.referencia = '$referencia'
					AND    tbl_peca.fabrica         = $fabrica;
					";



		}else{
			if(strlen(trim($status)) == 0) {
				$campo = ",ativo =true";
			}
            $sql = "UPDATE  tbl_peca SET
						informacoes    = '{$status}'
						$campo
                    WHERE  tbl_peca.referencia = '$referencia'
                    AND    tbl_peca.fabrica         = $fabrica;
                    ";

        }
        $res = pg_query($con,$sql);
		$res = pg_query($con,'COMMIT TRANSACTION');
    }

    $sqlf = "
        SELECT  fn_depara_lbm(de,para,$fabrica)
        FROM    tbl_depara d
        JOIN    tbl_lista_basica l  ON  d.peca_de   = l.peca
                                    AND l.fabrica   = $fabrica
   LEFT JOIN    tbl_lista_basica p  ON  d.peca_para = p.peca
                                    AND l.produto   = p.produto
                                    AND l.fabrica   = p.fabrica
        WHERE   p.lista_basica  IS NULL
        AND     d.fabrica       = $fabrica
        AND     peca_de         IS NOT NULL
        AND     peca_para       IS NOT NULL;";
	pg_query ($con,$sqlf);

    /*
     * - Atualiza a garantoa de peça
     * da peca-para, caso
     *
     * # A Peça DE tenha garantia e a PARA não;
     * # A Peça DE tenha garantia DIFERENTE da Peça PARA
     */

    $sqlUpGarantia = "
        UPDATE  tbl_lista_basica
        SET     garantia_peca = novo_valor.garantia_peca
        FROM    (
            SELECT  lista_basica_de.garantia_peca,
                    lista_basica_para.peca
            FROM    tbl_lista_basica lista_basica_de
            JOIN    tbl_depara                          ON  tbl_depara.fabrica  = lista_basica_de.fabrica
                                                        AND tbl_depara.peca_de  = lista_basica_de.peca
            JOIN    tbl_lista_basica lista_basica_para  ON  lista_basica_para.fabrica = tbl_depara.fabrica
                                                        AND lista_basica_para.peca = tbl_depara.peca_para
            WHERE   tbl_depara.fabrica              = $fabrica
            AND     lista_basica_de.produto         = lista_basica_para.produto
            AND     (lista_basica_de.garantia_peca   <> lista_basica_para.garantia_peca or lista_basica_para.garantia_peca isnull)
            AND     lista_basica_de.garantia_peca   IS NOT NULL
        ) novo_valor
        WHERE   tbl_lista_basica.peca = novo_valor.peca
    ";
    // 2018-06-07 - Comentado o UPDATE, a peca_para pode ter um valor de garantia diferente da peca_de
    //$resUpGarantia = pg_query($con,$sqlUpGarantia);

    // item_revenda quando tem o de_para
    $sql_ir = "SELECT lista_basica, tbl_peca.peca, tbl_lista_basica.parametros_adicionais
                FROM tbl_lista_basica
                INNER JOIN tbl_peca ON tbl_lista_basica.peca = tbl_peca.peca
                WHERE tbl_lista_basica.fabrica = $fabrica
                AND tbl_lista_basica.data_input > '$data_sistema'
                AND tbl_peca.parametros_adicionais ilike '%\"item_revenda\":\"t\"%';";
    $res_ir = pg_query($con,$sql_ir);

    if (pg_num_rows($res_ir) > 0) {
        for ($w=0; $w < pg_num_rows($res_ir) ; $w++) {
            $peca_ir = pg_fetch_result($res_ir, $w, peca);
            $lista_basica_ir = pg_fetch_result($res_ir, $w , lista_basica);
            $parametros_adicionais_ir = pg_fetch_result($res_ir, $w , parametros_adicionais);

            $parametros_adicionais_ir = json_decode($parametros_adicionais_ir,true);
            $parametros_adicionais_ir["item_revenda"] = "t";

            $parametros_adicionais_ir = json_encode($parametros_adicionais_ir);

            $sql_pa="UPDATE tbl_lista_basica
                        SET parametros_adicionais = '$parametros_adicionais_ir'
                        WHERE fabrica = $fabrica
                        AND lista_basica = $lista_basica_ir;";
            $res_pa = pg_query($con,$sql_pa);
        }
    }

	$sql = "SELECT referencia, status FROM tmp_black_peca_log";
	$resy = pg_query($con,$sql);
	if (pg_numrows($resy) > 0) {
		for($y = 0; $y < pg_numrows($resy); $y++){
			$referenciax = pg_numrows($resy,$y,referencia);
			$statusx     = pg_numrows($resy,$y,status);
			if(!empty($referenciax)){
				$log_status_removido[] = "O item $referenciax teve o status $statusx removido.<br>";
			}
		}

		if(count($log_status_removido) > 0){
			$log_status_removido[] = Date('d/m/Y H:i:s ')."Fim do Programa";
			fputs($fl_status_removido,implode("\n", $log_status_removido));
			fclose ($fl_status_removido);

			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

			$assunto = utf8_decode('BLACKE&DECKER - Alteração de STATUS na importação de peças \n ') . date('d/m/Y');

			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';

			if (ENV == "teste") {
				$mail->AddAddress('thiago.tobias@telecontrol.com.br');
			} else {
				$mail->AddAddress('mribeiro@blackedecker.com.br');
				$mail->AddAddress('rberto@blackedecker.com.br');
				$mail->AddAddress('rfernandes@blackedecker.com.br');
			}

			$mail->Subject = $assunto;
			$mail->Body = "Segue anexo arquivo de peças com status removidos ou alterados...<br/><br/>";
			$mail->AddAttachment($arquivo_log_status_removido, $arquivo_log_status_removido);

			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				unlink($arquivo_log_status_removido);
			}
		}
	}

	if(count($log_depara) > 0){
		$log_depara[] = Date('d/m/Y H:i:s ')."Fim do Programa";
		fputs($fl_depara,implode("\n", $log_depara));
		fclose ($fl_depara);

		require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

		$assunto = utf8_decode('BLACKE&DECKER -  Erros na importação de peças(DE -> PARA) \n ') . date('d/m/Y');

		$mail = new PHPMailer();
		$mail->IsHTML(true);
		$mail->From = 'helpdesk@telecontrol.com.br';
		$mail->FromName = 'Telecontrol';

		if (ENV == "teste") {
				$mail->AddAddress('thiago.tobias@telecontrol.com.br');
		} else {
			$mail->AddAddress('mribeiro@blackedecker.com.br');
			$mail->AddAddress('rberto@blackedecker.com.br');
			$mail->AddAddress('rfernandes@blackedecker.com.br');
		}

		$mail->Subject = $assunto;
		$mail->Body = "Erros na importação de peças...<br/><br/>";
		$mail->AddAttachment($arquivo_log_depara, $arquivo_log_depara);

		if (!$mail->Send()) {
			echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
		} else {
			unlink($arquivo_log_depara);
		}
	}

	if($notificar_falha == 1){
        Log::envia_email($data_log,Date('d/m/Y H:i:s')."Erro ao executar importa novas peças", implode("<br>", $log_cliente));
    }else{
        $sql = "UPDATE tbl_perl_processado SET qtde_integrado = {$qtde_integrado}, log = 'Atualizando com sucesso!', fim_processo = NOW() WHERE perl_processado = {$perl_processado};";
        pg_query($con, $sql);
    }

	 if(count($log) > 0){
        $log[] = Date('d/m/Y H:i:s ')."Fim do Programa";
        fputs($fl,implode("\n", $log));
        fclose ($fl);
    }

if(count($log_erro) > 0){
        $log_erro[] = Date('d/m/Y H:i:s ')."Fim do Programa";
        fputs($fl_erro,implode("\n", $log_erro));
        fclose ($fl_erro);
    }


    if(count($log_cliente) > 0){
        $log_cliente[] = Date('d/m/Y H:i:s ')."Fim do Programa";
        fputs($fl_cliente,implode("\n", $log_cliente));
        fclose ($fl_cliente);
    }


	if (filesize($origem_arq.$arquivo) > 0) {
        $log[] = Date('d/m/Y H:i:s ')."Movendo arquivo para $arquivos/blackedecker/nao_bkp/arquivos/$arquivo-$data_sistema.txt";
        mkdir("{$arquivos}/blackedecker/nao_bkp/arquivos/",0777, true);

        system ("mv {$origem_arq}{$arquivo} {$arquivos}/blackedecker/nao_bkp/arquivos/{$arquivo}-{$data_sistema}.txt;");
        system ("mv {$arquivo_log_cliente} {$origem_cliente}{$arquivo}-{$data_sistema}.log;");
    }

    $phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\nErro na linha: " . $e->getLine() . "\nErro descrição: " . $e->getMessage();
    echo $msg."\n";

    Log::envia_email($data_log,Date('d/m/Y H:i:s')."Erro ao executar importa peças", $msg);
}

