<?php

error_reporting(E_ALL ^ E_NOTICE);

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
        include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
        require dirname(__FILE__) . '/../funcoes.php';


	$login_fabrica = 88;
	$login_fabrica_nome  = 'orbis';
	
	$phpCron = new PHPCron($login_fabrica, __FILE__); 
	$phpCron->inicio();

    define('APP', 'Importa Faturamento - '.$login_fabrica_nome);

    $vet['fabrica'] = $login_fabrica_nome;
    $vet['tipo']    = 'importa-faturamento';
    $vet['dest']    = array('helpdesk@telecontrol.com.br');
    $vet['log']     = 1;

	$ftp_server = "ftp.telecontrol.com.br";
	$ftp_user_name = "orbis";
	$ftp_user_pass = "orb11is";

	$local_file = dirname(__FILE__) . '/entrada/faturamento.txt';
	$server_file = 'orbis-telecontrol/faturamento.txt';

	$local_file2 = dirname(__FILE__) . '/entrada/faturamento_item.txt';
	$server_file2 = 'orbis-telecontrol/faturamento_item.txt';

	$conn_id = ftp_connect($ftp_server);
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
	ftp_pasv($conn_id, true);

	ftp_get($conn_id, $local_file, $server_file, FTP_BINARY); 
	ftp_get($conn_id, $local_file2, $server_file2, FTP_BINARY); 

	ftp_close($conn_id);

	$origem = dirname(__FILE__) . "/entrada/";
	$file = "faturamento.txt";
	$file2 = "faturamento_item.txt";


	if(file_exists($origem.$file)){
		$sql = "DROP TABLE IF EXISTS orbis_nf;";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		$sql = "CREATE TABLE orbis_nf 
			(
			txt_codigo     text, 
			nota_fiscal    text, 
			serie          text, 
			txt_emissao    text, 
			cfop           text, 
			txt_total      text, 
			txt_ipi        text, 
			txt_icms       text, 
			transp         text, 
			txt_natureza   text, 
			txt_frete      text, 
			txt_tipo_frete text, 
			txt_condicao   text
			);";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		
		$sql = "ALTER TABLE orbis_nf ADD COLUMN total FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "ALTER TABLE orbis_nf ADD COLUMN posto INT4;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$linhas = file_get_contents($origem.$file);
		$linhas = explode("\n",$linhas);
		
		$erro = $msg_erro;

		foreach($linhas AS $linha){

			$msg_erro = "";

			list($txt_codigo, $nota_fiscal, $serie, $txt_emissao, $cfop, $txt_total, $txt_ipi, $txt_icms,$transp , $txt_natureza, $txt_frete, $txt_tipo_frete , $txt_condicao) = explode(";",$linha);
			if(!empty($txt_codigo)){

				$txt_condicao = (empty($txt_condicao)) ? null : $txt_condicao;
				$txt_tipo_frete = ($txt_tipo_frete == "NORMAL") ? "NOR" : "URG";

				$res = pg_query($con,"BEGIN");
				$sql = "INSERT INTO orbis_nf ( txt_codigo     ,
												nota_fiscal    ,
												serie          ,
												txt_emissao    ,
												cfop           ,
												txt_total      ,
												txt_ipi        ,
												txt_icms       ,
												transp         ,
												txt_natureza   ,
												txt_frete      ,
												txt_tipo_frete ,
												txt_condicao   
											  ) VALUES (
												'$txt_codigo'     ,
												'$nota_fiscal'    ,
												'$serie'          ,
												'$txt_emissao'    ,
												'$cfop'           ,
												'$txt_total'      ,
												'$txt_ipi'        ,
												'$txt_icms'       ,
												'$transp'         ,
												'$txt_natureza'   ,
												'$txt_frete'      ,
												'$txt_tipo_frete' ,
												'$txt_condicao'   
											  );";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if(!empty($msg_erro)){
					$res = pg_query($con,"ROLLBACK");
					$erro .= $msg_erro;
				} else {
					$res = pg_query($con,"COMMIT");
				}
			}

		}
		
		$msg_erro = $erro;
		$sql = "UPDATE orbis_nf SET
				txt_codigo     = trim (txt_codigo)                 ,
				nota_fiscal    = lpad (TRIM(nota_fiscal) ,8, '0'),
				serie          = trim (serie)                    ,
				txt_emissao    = trim (txt_emissao)              ,
				cfop           = trim (cfop)                     ,
				txt_total      = trim (txt_total)                ,
				transp         = trim (transp)                   ,
				txt_natureza   = trim (txt_natureza)             ,
				txt_frete      = trim (txt_frete)                ,
				txt_tipo_frete = trim(txt_tipo_frete)          ,
				txt_condicao   = trim(txt_condicao)              ;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		
		$sql = "UPDATE orbis_nf SET total = REPLACE(txt_total,',','.')::numeric";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "UPDATE orbis_nf SET posto =
				(
					SELECT tbl_posto.posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
										AND   tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE orbis_nf.txt_codigo = tbl_posto_fabrica.codigo_posto
				);";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		#------------ IDENTIFICAR POSTOS NAO ENCONTRADOS PELO CÓDIGO --------------#
		$sql = "DROP TABLE IF EXISTS orbis_nf_sem_posto;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "SELECT * INTO orbis_nf_sem_posto
				FROM orbis_nf
				WHERE posto IS NULL
				;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "DELETE FROM orbis_nf
				WHERE posto IS NULL
			;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		
	} else {
		$msg_erro = "Arquivo '".strtoupper($file)."' não encontrado";
	}
	
	if(file_exists($origem.$file2)){
		$sql = "DROP TABLE IF EXISTS orbis_nf_item;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	

		$sql = "CREATE TABLE orbis_nf_item
				(	txt_codigo            text,
					nota_fiscal           text,
					serie                 text,
					referencia_solicitada text,
					txt_pedido            text,
					txt_pedido_item		  text,
					txt_qtde              text,
					txt_unitario          text,
					txt_aliq_ipi          text,
					txt_aliq_icms         text,
					txt_valor_ipi         text,
					txt_valor_icms        text,
					txt_valor_sub_icms    text,
					txt_base_ipi          text,
					txt_base_icms         text,
					txt_base_sub_icms     text
				);";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		
		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN posto INT4;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN peca INT4;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN qtde FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN pedido INT4;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN pedido_item INT4;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN unitario FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN aliq_ipi FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN valor_ipi FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN valor_icms FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN base_icms FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN aliq_icms FLOAT;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "ALTER TABLE orbis_nf_item ADD COLUMN faturamento INT4;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$linhas = file_get_contents($origem.$file2);
		$linhas = explode("\n",$linhas);
		
		$erro = $msg_erro;

		
		foreach($linhas AS $linha){

			$msg_erro = "";

			list($txt_codigo, $nota_fiscal, $serie, $referencia_solicitada, $txt_pedido, $txt_pedido_item, $txt_qtde, $txt_unitario,$txt_aliq_ipi , $txt_aliq_icms, $txt_valor_ipi, $txt_valor_icms , $txt_valor_sub_icms, $txt_base_ipi, $txt_base_icms, $txt_base_sub_icms) = explode(";",$linha);
			
			if(!empty($txt_codigo)){
				$res = pg_query($con,"BEGIN");

				$sql = "INSERT INTO orbis_nf_item(
									txt_codigo            ,
									nota_fiscal           ,
									serie                 ,
									referencia_solicitada ,
									txt_pedido            ,
									txt_pedido_item       ,
									txt_qtde              ,
									txt_unitario          ,
									txt_aliq_ipi          ,
									txt_aliq_icms         ,
									txt_valor_ipi         ,
									txt_valor_icms        ,
									txt_valor_sub_icms    ,
									txt_base_ipi          ,
									txt_base_icms         ,
									txt_base_sub_icms     
									) VALUES(
									'$txt_codigo'            ,
									'$nota_fiscal'           ,
									'$serie'                 ,
									'$referencia_solicitada' ,
									'$txt_pedido'            ,
									'$txt_pedido_item'       ,
									'$txt_qtde'              ,
									'$txt_unitario'          ,
									'$txt_aliq_ipi'          ,
									'$txt_aliq_icms'         ,
									'$txt_valor_ipi'         ,
									'$txt_valor_icms'        ,
									'$txt_valor_sub_icms'    ,
									'$txt_base_ipi'          ,
									'$txt_base_icms'         ,
									'$txt_base_sub_icms');";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if(!empty($msg_erro)){
					$res = pg_query($con,"ROLLBACK");
					$erro .= $msg_erro;
				} else {
					$res = pg_query($con,"COMMIT");
				}
			}
			
		}

		$msg_erro .= $erro;
		
			$sql = "UPDATE orbis_nf_item SET
					txt_codigo      = TRIM(txt_codigo)                  ,
					nota_fiscal     = LPAD(TRIM(nota_fiscal) ,8, '0')   ,
					serie           = TRIM(serie)                       ,
					referencia_solicitada  = TRIM(referencia_solicitada),
					txt_pedido      = TRIM(txt_pedido)                  ,
					txt_pedido_item = TRIM(txt_pedido_item)             ,
					txt_qtde        = TRIM(txt_qtde)                    ,
					txt_unitario    = TRIM(txt_unitario)                ,
					txt_aliq_ipi    = TRIM(txt_aliq_ipi)                ,
					txt_aliq_icms   = TRIM(txt_aliq_icms)               ,
					txt_valor_ipi   = TRIM(txt_valor_ipi)               ,
					txt_valor_icms  = TRIM(txt_valor_icms)              ,
					txt_base_icms   = TRIM(txt_base_icms);";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			
			$sql = "UPDATE orbis_nf_item SET
					qtde       = txt_qtde::numeric                        ,
					unitario   = REPLACE(case when length(txt_unitario)    = 0 then '0' else txt_unitario end   ,',','.')::numeric   ,
					aliq_ipi   = REPLACE(case when length(txt_aliq_ipi )   = 0 then '0' else txt_aliq_ipi end   ,',','.')::numeric   ,
					valor_ipi  = REPLACE(case when length(txt_valor_ipi )  = 0 then '0' else txt_valor_ipi end  ,',','.')::numeric  ,
					valor_icms = REPLACE(case when length(txt_valor_icms ) = 0 then '0' else txt_valor_icms end ,',','.')::numeric ,
					base_icms  = REPLACE(case when length(txt_base_icms )  = 0 then '0' else txt_base_icms end  ,',','.')::numeric ,
					pedido_item = REPLACE(case when length(txt_pedido_item )  = 0 then '0' else txt_pedido_item end  ,',','.')::numeric ,
					aliq_icms  = REPLACE(case when length(txt_aliq_icms )  = 0 then '0' else txt_aliq_icms end  ,',','.')::numeric;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
			

			$sql = "UPDATE orbis_nf_item SET posto = (
						SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
											AND   tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE orbis_nf_item.txt_codigo = tbl_posto_fabrica.codigo_posto);";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "UPDATE orbis_nf_item
					SET pedido = tbl_pedido.pedido
					FROM tbl_pedido
					WHERE (orbis_nf_item.txt_pedido::numeric = tbl_pedido.pedido OR orbis_nf_item.txt_pedido = tbl_pedido.seu_pedido)
					AND tbl_pedido.fabrica = $login_fabrica
					AND (txt_pedido is not null and length(trim (txt_pedido))> 0);
					";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "UPDATE orbis_nf_item
					SET peca = tbl_peca.peca
					FROM  tbl_peca
					WHERE orbis_nf_item.referencia_solicitada = tbl_peca.referencia
					AND tbl_peca.fabrica = $login_fabrica;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			
			$sql = "DROP TABLE IF EXISTS orbis_nf_sem_pedido;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "SELECT orbis_nf_item.*
					INTO orbis_nf_sem_pedido
					FROM orbis_nf_item
					WHERE orbis_nf_item.pedido IS NULL";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "DELETE FROM orbis_nf_item
					WHERE pedido is null;
				";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "DROP TABLE IF EXISTS orbis_nf_sem_itens;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "SELECT orbis_nf_item.*
					INTO orbis_nf_sem_itens
					FROM orbis_nf
					LEFT JOIN orbis_nf_item ON orbis_nf.nota_fiscal = orbis_nf_item.nota_fiscal
					WHERE orbis_nf_item.nota_fiscal IS NULL
					";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "DELETE FROM orbis_nf 
					USING orbis_nf_sem_itens
					WHERE orbis_nf.nota_fiscal = orbis_nf_sem_itens.nota_fiscal
					AND   orbis_nf.serie       = orbis_nf_sem_itens.serie
					";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "INSERT INTO tbl_faturamento
					(
						fabrica     ,
						emissao     ,
						saida       ,
						transp      ,
						posto       ,
						total_nota  ,
						cfop        ,
						nota_fiscal ,
						serie       
					)
						SELECT  $login_fabrica,
								orbis_nf.txt_emissao::date         ,
								orbis_nf.txt_emissao::date         ,
								substring(orbis_nf.transp, 1,30),
								orbis_nf.posto           ,
								orbis_nf.total           ,
								orbis_nf.cfop        ,
								orbis_nf.nota_fiscal ,
								orbis_nf.serie       
						FROM orbis_nf
						LEFT JOIN tbl_faturamento ON  orbis_nf.nota_fiscal   = tbl_faturamento.nota_fiscal
												 AND  orbis_nf.serie         = tbl_faturamento.serie
												 AND  tbl_faturamento.fabrica      = $login_fabrica
												 AND  tbl_faturamento.distribuidor IS NULL
						WHERE tbl_faturamento.faturamento IS NULL
					";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			
			$sql = "UPDATE orbis_nf_item
					SET faturamento = tbl_faturamento.faturamento
					FROM tbl_faturamento
					WHERE tbl_faturamento.fabrica     = $login_fabrica
					AND   tbl_faturamento.nota_fiscal = orbis_nf_item.nota_fiscal
					AND   tbl_faturamento.serie       = orbis_nf_item.serie";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "DELETE FROM orbis_nf_item WHERE faturamento IS NULL";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "DROP TABLE IF EXISTS orbis_nf_item_sem_peca;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "SELECT * INTO orbis_nf_item_sem_peca 
					FROM orbis_nf_item 
					WHERE peca IS NULL" ;
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			$sql = "DELETE FROM orbis_nf_item WHERE peca IS NULL" ;
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT  DISTINCT faturamento,
							pedido     ,
							peca       ,
							qtde as qtde_fat,
							unitario   ,
							aliq_ipi   ,
							aliq_icms  ,
							valor_ipi  ,
							valor_icms ,
							base_icms  ,
							referencia_solicitada
					FROM orbis_nf_item;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			if(pg_num_rows($res) > 0){
				$erro = $msg_erro;

				for($i = 0; $i < pg_num_rows($res); $i++){

					$res1 = pg_query($con,"BEGIN");
					$msg_erro = "";
					$pedido          = pg_result($res,$i,'pedido');
					$faturamento     = pg_result($res,$i,'faturamento');
					$peca            = pg_result($res,$i,'peca');
					$qtde_fat        = pg_result($res,$i,'qtde_fat');
					$unitario        = pg_result($res,$i,'unitario');
					$aliq_ipi        = pg_result($res,$i,'aliq_ipi');
					$valor_ipi       = pg_result($res,$i,'valor_ipi');
					$valor_icms      = pg_result($res,$i,'valor_icms');
					$base_icms       = pg_result($res,$i,'base_icms');
					$aliq_icms       = pg_result($res,$i,'aliq_icms');
					
					$sql = "SELECT pedido FROM tbl_pedido WHERE (seu_pedido = '$pedido' OR tbl_pedido.pedido = $pedido) AND fabrica = $login_fabrica";
					$res3 = pg_query($con,$sql);

					if(pg_num_rows($res3) > 0){

						$pedido = pg_result($res3,0,'pedido');

						$sql = "INSERT INTO tbl_faturamento_item
								(
									faturamento,
									pedido     ,
									peca       ,
									qtde       ,
									preco      ,
									aliq_ipi   ,
									valor_ipi  ,
									valor_icms ,
									aliq_icms  ,
									base_icms
								)
								VALUES(
									$faturamento,
									$pedido     ,
									$peca       ,
									$qtde_fat   ,
									$unitario   ,
									$aliq_ipi   ,
									$valor_ipi  ,
									$valor_icms ,
									$aliq_icms  ,
									$base_icms
								)";
						$res2 = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);


						$sql = "SELECT qtde as qtde_pedido,
										pedido_item
								FROM tbl_pedido_item
								WHERE pedido = $pedido
									AND peca = $peca
									AND qtde > qtde_faturada
								LIMIT 1;";
						$res2 = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if(pg_num_rows($res2) > 0){

							$pedido_item = pg_result($res2,0,'pedido_item');
							$qtde_pedido = pg_result($res2,0,'qtde_pedido');

							$sql = "SELECT fn_atualiza_pedido_item($peca,$pedido,$pedido_item,$qtde_fat)";
							$res3 = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
							

						} else {

							$msg_erro .= " Não foi encontrado o item : $pedido_item referente ao pedido : $pedido para atualizar: \n";

						}
						
						$sql = "SELECT fn_atualiza_status_pedido($login_fabrica,$pedido);";
						$res3 = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
					if(!empty($msg_erro)){
						$res1 = pg_query($con,"ROLLBACK");
						$erro .= $msg_erro;
					} else {
						$res1 = pg_query($con,"COMMIT");
					}

				}

				
			}

			$msg_erro = $erro;
		
	} else {
		$msg_erro = "Arquivo '".strtoupper($file2)."' não encontrado";
	}
	
	$sql = "SELECT nota_fiscal FROM orbis_nf_sem_posto";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$log_erro = "Notas Fiscais sem Posto Autorizado <br>";
		for($i = 0; $i < pg_num_rows($res); $i++){
			$nota = pg_result($res,$i,'txt_nota_fiscal');
			$log_erro .= " $nota -";
		}

		$log_erro .= "<br><br>";
	}

	$sql = "SELECT nota_fiscal FROM orbis_nf_sem_pedido";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$log_erro = "Notas Fiscais sem Pedido <br>";
		for($i = 0; $i < pg_num_rows($res); $i++){
			$nota = pg_result($res,$i,'txt_nota_fiscal');
			$log_erro .= " $nota -";
		}
		$log_erro .= "<br><br>";
	}


	$sql = "SELECT nota_fiscal FROM orbis_nf_sem_itens";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$log_erro = "Notas Fiscais sem Itens <br>";
		for($i = 0; $i < pg_num_rows($res); $i++){
			$nota = pg_result($res,$i,'txt_nota_fiscal');
			$log_erro .= " $nota -";
		}
	}

	if (!empty($msg_erro)) {
		$msg_erro .= "\n\n".$log_erro;
		$fp = fopen("entrada/faturamento.err","w");
		fwrite($fp,$msg_erro);
		fclose($fp);
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($vet, APP, $msg);

	} else {
		$fp = fopen("/tmp/orbis/faturamento.err","w");
		fwrite($fp,$log_erro);
		fclose($fp);

		system("mv $origem$file /tmp/orbis/faturamento".date('Y-m-d').".txt");
		system("mv $origem$file2 /tmp/orbis/faturamento_item".date('Y-m-d').".txt");

		Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('Y-m-d'));

	}
	
	$phpCron->termino();

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}
