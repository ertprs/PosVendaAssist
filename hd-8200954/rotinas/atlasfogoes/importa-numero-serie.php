<?php 

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$fabrica  = "74" ;
	$login_fabrica = $fabrica;
	$origem   = "/home/atlas/atlas-telecontrol";
	$tmp = "/tmp/atlas";
	$data = date("d-m-Y");
	$arquivo_log    = 'numero_serie';

	$arquivo_err = "$tmp/{$arquivo_log}-{$data}.err";
    $arquivo_log = "$tmp/{$arquivo_log}-{$data}.log";	

    $file_log = fopen("$arquivo_log", "w");
    $file_err = fopen("$arquivo_err", "w");

	function limpa_string($dados){
		$retirar = array(".", "/", "*");
		$dados = str_replace($retirar, "", $dados);
		return $dados;
	}

	function validaData($data){
		$data = explode('-', $data);

		if(checkdate($data[1], $data[2], $data[0])){
			return true;
		}
		else{
			return false;
		}
	}

	function formata_data_banco($data) {
		$aux_ano  = substr ($data,6,4);
		$aux_mes  = substr ($data,3,2);
		$aux_dia  = substr ($data,0,2);
		return $aux_ano."-".$aux_mes."-".$aux_dia;
	}

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

	if(file_exists("$origem/serie.txt")){

		$sql = "DROP TABLE atlas_numero_serie;";
		$result = pg_query($con, $sql);

		$sql = "CREATE TABLE atlas_numero_serie
				(
					txt_referencia_produto text ,
					txt_serie              text ,
					txt_data_venda         text ,
					txt_data_fabricacao    text ,
					txt_cnpj               text 
				);
				";
		$result = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao criar tabela atlas_numero_serie. ". pg_last_error($con);
		}
		
		$conteudo_serie = file("$origem/serie.txt");

		$num_linha = 1;
		foreach($conteudo_serie as $linha){
			$valores = explode("\t", $linha);
				$grava_tbl_temp = true;
				$txt_referencia_produto 	= trim(limpa_string($valores[0]));
				$txt_serie              	= trim(limpa_string($valores[1]));
				$txt_data_venda         	= trim(limpa_string($valores[2]));
				$txt_data_fabricacao    	= trim(limpa_string($valores[3]));
				$txt_cnpj               	= trim(limpa_string($valores[4]));

				$sql_produto = "SELECT referencia FROM tbl_produto WHERE referencia = '$txt_referencia_produto' and fabrica_i = $fabrica";
				$res_produto = pg_query($con, $sql_produto);
				if(pg_num_rows($res_produto)==0){
					$log .= "Linha $num_linha - Produto de referência $txt_referencia_produto não foi encontrado. $txt_referencia_produto $txt_serie $txt_data_venda $txt_data_fabricacao $txt_cnpj \n \n\n";
					$grava_tbl_temp = false;					
				}

				if(!validaData($txt_data_venda)){
					$log .= "Linha $num_linha - Data de Venda informada no arquivos não é válida - $txt_referencia_produto $txt_serie $txt_data_venda $txt_data_fabricacao $txt_cnpj \n";
					$grava_tbl_temp = false;
				}

				if(!validaData($txt_data_fabricacao)){
					$log .= "Linha $num_linha - Data de Fabricação informada no arquivos não é válida - $txt_referencia_produto $txt_serie $txt_data_venda $txt_data_fabricacao $txt_cnpj \n";
					$grava_tbl_temp = false;
				}
				
			if($grava_tbl_temp){
				$sql = "insert into atlas_numero_serie (
						txt_referencia_produto  ,
						txt_serie               ,
						txt_data_venda          ,
						txt_data_fabricacao     ,
						txt_cnpj                
					) values (
						'$txt_referencia_produto'  ,
						'$txt_serie'               ,
						'$txt_data_venda'          ,
						'$txt_data_fabricacao'     ,
						'$txt_cnpj'                
					)";
				$res = pg_query($con, $sql);
				if(strlen(trim(pg_last_error($con)))>0){
					$msg_erro_interno .= "\n\nErro ao inserir na tabela atlas_numero_serie. ". pg_last_error($con);
				}
			}
			$num_linha ++;			
		}

		$sql = "ALTER TABLE atlas_numero_serie ADD column produto int4";
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao alterar campo produto. ". pg_last_error($con);
		}
		
		$sql = "UPDATE atlas_numero_serie
					SET produto = tbl_produto.produto
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE  upper(trim(atlas_numero_serie.txt_referencia_produto)) = upper(trim(tbl_produto.referencia))
				AND    tbl_linha.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao atualizar produto. ". pg_last_error($con);
		}

 		$sql = "ALTER TABLE atlas_numero_serie ADD COLUMN tem_serie boolean;";
                $res = pg_query($con, $sql);
                if(strlen(trim(pg_last_error($con)))>0){
                        $msg_erro_interno .= "\n\nErro ao alterar coluna tem_serie. ". pg_last_error($con);
                }

                $sql = "UPDATE  atlas_numero_serie
                                        SET tem_serie = 't'
                                FROM tbl_numero_serie
                                WHERE upper(trim(tbl_numero_serie.serie)) = upper(trim(atlas_numero_serie.txt_serie))
                                AND tbl_numero_serie.produto = atlas_numero_serie.produto
                                AND tbl_numero_serie.fabrica = $fabrica";
                $res = pg_query($con, $sql);
                if(strlen(trim(pg_last_error($con)))>0){
                        $msg_erro_interno .= "\n\n Erro ao atualizar campo tem_serie. ". pg_last_error($con);
                }

		$sql = "DROP TABLE atlas_serie_falha;";
		$res = pg_query($con, $sql);

		$sql = "ALTER TABLE atlas_numero_serie ADD COLUMN tem_duplicado boolean;";
                $res = pg_query($con, $sql);
                if(strlen(trim(pg_last_error($con)))>0){
                        $msg_erro_interno .= "\n\nErro ao alterar coluna tem_duplicado. ". pg_last_error($con);
                }

		$sql = "SELECT COUNT(*),produto,txt_serie into temp atlas_serie_duplicado from atlas_numero_serie group by produto,txt_serie having count(*) > 1";
		$res = pg_query($con, $sql);
                if(strlen(trim(pg_last_error($con)))>0){
                        $msg_erro_interno .= "\n\n Erro serie duplicada ". pg_last_error($con);
                }

		$sql = "UPDATE atlas_numero_serie SET tem_duplicado = 't' from  atlas_serie_duplicado WHERE atlas_serie_duplicado.txt_serie = atlas_numero_serie.txt_serie and atlas_serie_duplicado.produto = atlas_numero_serie.produto";
		$res = pg_query($con, $sql);
                if(strlen(trim(pg_last_error($con)))>0){
                        $msg_erro_interno .= "\n\nErro ao alterar coluna tem_duplicado. ". pg_last_error($con);
                }
			
		### GERA ARQUIVO COM ERROS
		$sql = "SELECT    *
				INTO TEMP atlas_serie_falha
				FROM      atlas_numero_serie
				WHERE     (atlas_numero_serie.produto IS NULL OR atlas_numero_serie.tem_serie IS TRUE)";
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao buscar produto sem série. ". pg_last_error($con);
		}
	
		$sql = "UPDATE atlas_numero_serie SET txt_cnpj='' WHERE length(txt_cnpj) > 14";
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao atualizar CNPJ. ". pg_last_error($con);
		}
		
		$sql = "SELECT  txt_serie              ,
						txt_referencia_produto ,
						txt_data_venda         ,
						produto
				FROM atlas_serie_falha
					WHERE tem_serie is not true
				"; 
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao buscar série (falha). ". pg_last_error($con);
		}else{
			$txt_serie 					= pg_fetch_result($res, 0, 'txt_serie');
			$txt_referencia_produto 	= pg_fetch_result($res, 0, 'txt_referencia_produto');
			$txt_data_venda 			= pg_fetch_result($res, 0, 'txt_data_venda');

			$dadosFalha .= " $txt_serie, $txt_referencia_produto, $txt_data_venda \n";
		}
		
		## DELETA NÚMERO DE SÉRIE COM ERROS
		$sql = "DELETE FROM atlas_numero_serie
				WHERE  (atlas_numero_serie.produto IS NULL OR atlas_numero_serie.tem_serie IS TRUE)";
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao apagar número de série. $sql ". pg_last_error($con);
		}

		# INSERE PEÇAS  NOVOS
		$sql = "INSERT INTO tbl_numero_serie
				(
					fabrica           ,
					serie             ,
					cnpj              ,
					referencia_produto,
					data_venda        ,
					data_fabricacao   ,
					produto          
				)
				SELECT  DISTINCT
					$fabrica                 ,
					txt_serie                ,
					txt_cnpj                 ,
					txt_referencia_produto   ,
					txt_data_venda::date     ,
					txt_data_fabricacao::date,
					produto
				FROM    atlas_numero_serie
					WHERE   tem_serie is not true
					AND txt_data_venda <> ''
					AND txt_data_fabricacao <> ''
					AND tem_duplicado is not true;
				";
		$res = pg_query($con, $sql);

		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "\n\n Erro ao inserir número de série. ". pg_last_error($con);
		}
		$data_sistema = date("Y-m-d");
	}

	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}	

	if (!empty($msg_erro_interno)) {
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "ATLAS - Erro na importação do Número de Série";
		$mensagem  = "Segue dados da importação de Número de Série. \n ";
		$mensagem  .= "$msg_erro_interno";
		mail($para, $assunto, $mensagem, $headers);	
	} else {
		//system ("mv $origem/serie.txt $origem/serie_$data_sistema.txt");

		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "ATLAS - Log na importação do Número de Série";
		$mensagem  = "Segue dados da importação de Número de Série. \n ";
		$mensagem  .= "$log \n";
		mail($para, $assunto, $mensagem, $headers);	
	}

	fwrite($file_log, $log);
	fclose($file_log);

	fwrite($file_err, $msg_erro_interno);
	fclose($file_err);

$phpCron->termino();
 	$data = date('Y-m-d-h-s');
	if (file_exists("/home/atlas/atlas-telecontrol/serie.txt")) {
                system("cp /home/atlas/atlas-telecontrol/serie.txt  /tmp/atlas/serie_$data.txt");
		system("mv /home/atlas/atlas-telecontrol/serie.txt  /home/atlas/atlas-telecontrol/bkp/serie_$data.txt");
	}

?>
