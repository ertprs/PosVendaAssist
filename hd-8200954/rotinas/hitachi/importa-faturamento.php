<?php
/**
 *
 * importa-faturamento.php
 *
 * Importação de pedidos de pecas
 *
 * @author  Ronald Santos
 * @version 2014.01.17
 *
*/

ini_set("display_errors", 1);
error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 		= 147;
    $data['fabrica'] 			= 'hitachi';
    $data['arquivo_log'] 		= 'importa-faturamento';
	$data['tipo'] 				= 'importa-faturamento';
    $data['log'] 				= 2;
    $data['arquivos'] 			= "/tmp";
    $data['data_sistema'] 		= Date('Y-m-d');
    $logs 						= array();
    $logs_erro					= array();
    $logs_cliente				= array();
    $erro 						= false;

    if (ENV == 'producao' ) {
	    $data['dest'] 			= array("amaral@hitachi-koki.com.br","helpdesk@telecontrol.com.br");
	    $data['origem']			= "/home/hitachi/pos-vendas/hitachi-telecontrol/faturamento/";
    } else {
	    $data['dest'] 			= 'william.lopes@telecontrol.com.br';
	    $data['origem']			= '/home/william/hitachi/entrada/';
    }
    
    $data['file']       = 'faturamento2';
    $data['file2']      = 'faturamento_item2';

    extract($data);
	
	define('APP', 'Importa Faturamento - '.$fabrica);

    $arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 0777 {$arquivos}/{$fabrica}/" ); 

    foreach (glob($origem.$file."*") as $arquivo) {
		    if(file_exists($arquivo)){
			  
			  $sql = "DROP TABLE IF EXISTS hitachi_nf;";
			  $res = pg_query($con,$sql);
			  $msg_erro .= pg_last_error($con);
			

			  $sql = "CREATE TABLE hitachi_nf (
						  txt_cnpj           text,
						  txt_nota_fiscal    text,
						  txt_serie          text,
						  txt_emissao        text,
						  txt_saida        	 text,
						  txt_cfop           text,
						  txt_total          text,
						  txt_ipi            text,
						  txt_icms           text,
						  txt_transp         text
					  )";
			  $res = pg_query($con,$sql);
			  $msg_erro .= pg_last_error($con);
			  
			  $linhas = file_get_contents($arquivo);
			  $linhas = explode("\n",$linhas);
			  system("mv $arquivo /tmp/hitachi/faturamento/faturamento".date('Y-m-d-H-i').".txt");
			  $erro = $msg_erro;
			  
			  foreach($linhas AS $linha){
			  
				$msg_erro = "";

					list(
		                $txt_nota_fiscal, 
		                $txt_serie, 
		                $txt_emissao, 
		                $txt_saida, 
		                $txt_cnpj, 
		                $txt_cfop, 
		                $txt_total
		            ) = explode("\t",$linha);


						if(!strlen($txt_nota_fiscal ))	{ $erro .= "Erro Campo Nulo $txt_nota_fiscal "; continue; }
						if(!strlen($txt_serie ))		{ $erro .= "Erro Campo Nulo $txt_serie "; continue; }
						if(!strlen($txt_emissao ))		{ $erro .= "Erro Campo Nulo $txt_emissao"; continue; }
						if(!strlen($txt_saida ))		{ $erro .= "Erro Campo Nulo $txt_saida "; continue; }
						if(!strlen($txt_cnpj ))			{ $erro .= "Erro Campo Nulo $txt_cnpj "; continue; }
						if(!strlen($txt_cfop ))			{ $erro .= "Erro Campo Nulo $txt_cfop "; continue; }
						if(!strlen($txt_total ))		{ $erro .= "Erro Campo Nulo $txt_total "; continue; }

		            
					if(!empty($txt_cnpj)){
						$txt_cnpj = str_replace('.','',$txt_cnpj);
						$txt_cnpj = str_replace('/','',$txt_cnpj);
						$txt_cnpj = str_replace('-','',$txt_cnpj);

						$res = pg_query($con,"BEGIN");
						$sql = "INSERT INTO hitachi_nf ( 
		                                    txt_cnpj,
										    txt_nota_fiscal,
										    txt_serie,
										    txt_emissao,
										    txt_saida,
										    txt_cfop,
										    txt_total
										  ) VALUES (
		                                    '$txt_cnpj',
		                                    '$txt_nota_fiscal',
		                                    '$txt_serie',
		                                    '$txt_emissao',
		                                    '$txt_saida',
		                                    '$txt_cfop',
		                                    '$txt_total'
										  );";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_last_error($con);

						if(!empty($msg_erro)){
							$res = pg_query($con,"ROLLBACK");
							$erro .= $msg_erro;
						} else {
							$res = pg_query($con,"COMMIT");
						}
					}
			  
			  }
			  $msg_erro = $erro;
			  
			  $sql = "UPDATE hitachi_nf SET
						txt_cnpj        = trim (txt_cnpj),
						txt_nota_fiscal = lpad (TRIM(txt_nota_fiscal) ,9, '0'),
						txt_serie       = trim (txt_serie),
						txt_emissao     = trim (txt_emissao),
						txt_saida       = trim (txt_saida),
						txt_total       = trim (txt_total);";
			$res = pg_query($con,$sql);
			
			$sql = "ALTER TABLE hitachi_nf ADD COLUMN total FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "ALTER TABLE hitachi_nf ADD COLUMN emissao DATE";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "ALTER TABLE hitachi_nf ADD COLUMN saida DATE";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "ALTER TABLE hitachi_nf ADD COLUMN posto INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "ALTER TABLE hitachi_nf ADD COLUMN pedido INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "UPDATE hitachi_nf SET
						emissao     = TO_DATE(txt_emissao,'YYYY-MM-DD'),
						saida     	= TO_DATE(txt_saida,'YYYY-MM-DD'),
						total       = REPLACE(txt_total,',','.')::numeric";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "UPDATE hitachi_nf SET posto = (
						SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
											AND   tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE hitachi_nf.txt_cnpj = tbl_posto.cnpj
					)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			#------------ IDENTIFICAR POSTOS NAO ENCONTRADOS PELO CNPJ --------------#
			$sql = "DROP TABLE IF EXISTS hitachi_nf_sem_posto";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "SELECT * INTO hitachi_nf_sem_posto FROM hitachi_nf WHERE posto IS NULL";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "DELETE FROM hitachi_nf
					WHERE posto IS NULL";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "DROP TABLE IF EXISTS hitachi_nf_item;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "CREATE TABLE hitachi_nf_item (
						txt_nota_fiscal     text,
						txt_serie           text,
						txt_referencia      text,
						txt_pedido          text,
						txt_pedido_item     text,
						txt_qtde            text,
						txt_unitario        text,
						txt_aliq_ipi        text,
		                txt_aliq_icms       text,
						txt_valor_ipi       text,
						txt_valor_icms      text,
		                txt_valor_subs_trib text,
		                txt_base_ipi        text,
						txt_base_icms       text,
		                txt_base_subs_trib  text
					)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$arquivoItem = glob($origem.$file2."*");
			
			$fatItemArquivo = array($arquivoItem[0]);


			foreach ($fatItemArquivo as $arquivo2) {
				$linhas_item = file_get_contents($arquivo2);
				$linhas_item = explode("\n",$linhas_item);
				system("mv $arquivo2 /tmp/hitachi/faturamento/faturamento_item".date('Y-m-d-H-i').".txt");
				$erro = $msg_erro;
				
				foreach($linhas_item AS $linha_item){

			      list(
		              $txt_nota_fiscal, 
		              $txt_serie, 
		              $txt_referencia, 
		              $txt_qtde, 
		              $txt_unitario, 
		              $txt_aliq_icms,
		              $txt_aliq_ipi,
		              $txt_base_ipi,
		              $txt_base_icms,
		              $txt_base_subs_trib,
		              $txt_valor_ipi,
		              $txt_valor_icms,
		              $txt_valor_subs_trib,
		              $txt_pedido_item , 
		            ) = explode ("\t", $linha_item);



			       	if(!strlen($txt_nota_fiscal)) 	{$msg_erro = "Erro campo Nulo nota_fiscal"; continue;}
		            if(!strlen($txt_serie)) 		{$msg_erro = "Erro campo Nulo serie"; continue;}
		            if(!strlen($txt_referencia)) 	{$msg_erro = "Erro campo Nulo peça refenrencia"; continue;}
		            if(!strlen($txt_qtde)) 			{$msg_erro = "Erro campo Nulo qntde"; continue;}
		            if(!strlen($txt_unitario)) 		{$msg_erro = "Erro campo Nulo valor unitario"; continue;}
		            if(!strlen($txt_aliq_icms)) 	{$msg_erro = "Erro campo Nulo icms"; continue;}
		            if(!strlen($txt_pedido_item)) 	{$msg_erro = "Erro campo Nulo pedido item"; continue;} 

			      if(!empty($txt_pedido_item)){
						
					  $txt_base_icms = str_replace("\r","",$txt_base_icms);

				      $res = pg_query($con,"BEGIN");
				      $sql = "INSERT INTO hitachi_nf_item (
		                                txt_nota_fiscal,
		                                txt_serie,
		                                txt_referencia,
		                                txt_pedido_item,
		                                txt_qtde,
		                                txt_unitario,
		                                txt_aliq_ipi,
		                                txt_aliq_icms,
		                                txt_valor_ipi,
		                                txt_valor_icms,
		                                txt_valor_subs_trib,
		                                txt_base_ipi,
		                                txt_base_icms,
		                                txt_base_subs_trib
									) VALUES (
									    '$txt_nota_fiscal',
									    '$txt_serie',
									    '$txt_referencia',
									    '$txt_pedido_item',
									    '$txt_qtde',
									    '$txt_unitario',
									    '$txt_aliq_ipi',
									    '$txt_aliq_icms',
		                                '$txt_valor_ipi',
		                                '$txt_valor_icms',
		                                '$txt_valor_subs_trib',
		                                '$txt_base_ipi',
		                                '$txt_base_icms',
		                                '$txt_base_subs_trib'
									);";
				      $res = pg_query($con,$sql);
				      $msg_erro .= pg_last_error($con);

				      if(!empty($msg_erro)){
					      $res = pg_query($con,"ROLLBACK");
					      $erro .= $msg_erro;
				      } else {
					      $res = pg_query($con,"COMMIT");
				      }
			      }
					
			}
		}
		$msg_erro = $erro;
		
		$sql = "UPDATE hitachi_nf_item SET
					txt_nota_fiscal 	= LPAD(TRIM(txt_nota_fiscal) ,9, '0'),
					txt_serie       	= TRIM(txt_serie)                    ,
					txt_referencia  	= TRIM(txt_referencia)               ,
					txt_pedido      	= TRIM(txt_pedido)                   ,
					txt_pedido_item 	= TRIM(txt_pedido_item)              ,
					txt_qtde        	= TRIM(txt_qtde)                     ,
					txt_unitario    	= TRIM(txt_unitario)                 ,
					txt_aliq_ipi 		= TRIM(txt_aliq_ipi)				 ,
	                txt_aliq_icms 		= TRIM(txt_aliq_icms)				 ,
	                txt_valor_ipi 		= TRIM(txt_valor_ipi)				 ,
	                txt_valor_icms 		= TRIM(txt_valor_icms)				 ,
	                txt_valor_subs_trib = TRIM(txt_valor_subs_trib)			 ,
	                txt_base_icms 		= TRIM(txt_base_icms)				 ,
	                txt_base_ipi 		= TRIM(txt_base_ipi)				 ,
	                txt_base_subs_trib 	= TRIM(txt_base_subs_trib)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN posto INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN peca INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN qtde FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN pedido INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN pedido_item INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN unitario FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN aliq_ipi FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN aliq_icms FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN valor_ipi FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN valor_icms FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN valor_subs_trib FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN base_icms FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN base_ipi FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN base_subs_trib FLOAT";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN os_item INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN devolucao boolean";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = 	"UPDATE hitachi_nf_item set txt_pedido_item = replace(txt_pedido_item,'\r','')";
			$res = pg_query($con,$sql);

			$sql = "DELETE FROM hitachi_nf_item WHERE txt_pedido_item = ''";
			$res = pg_query($con,$sql);

			$sql = "UPDATE hitachi_nf_item SET
						qtde       		= txt_qtde::numeric                        ,
						unitario   		= REPLACE(case when length(txt_unitario)    	 = 0 then '0' else txt_unitario end   ,',','.')::numeric     ,
						aliq_ipi   		= REPLACE(case when length(txt_aliq_ipi )   	 = 0 then '0' else txt_aliq_ipi end   ,',','.')::numeric     ,
						aliq_icms  		= REPLACE(case when length(txt_aliq_icms )  	 = 0 then '0' else txt_aliq_icms end  ,',','.')::numeric     ,
						valor_ipi  		= REPLACE(case when length(txt_valor_ipi )  	 = 0 then '0' else txt_valor_ipi end  ,',','.')::numeric  	 ,
						valor_icms 		= REPLACE(case when length(txt_valor_icms ) 	 = 0 then '0' else txt_valor_icms end ,',','.')::numeric 	 ,
						valor_subs_trib = REPLACE(case when length(txt_valor_subs_trib ) = 0 then '0' else txt_valor_subs_trib end ,',','.')::numeric,
						base_ipi  		= REPLACE(case when length(txt_base_ipi )  		 = 0 then '0' else txt_base_ipi end  ,',','.')::numeric 	 ,
						base_icms  		= REPLACE(case when length(txt_base_icms )  	 = 0 then '0' else txt_base_icms end  ,',','.')::numeric 	 ,  
						base_subs_trib  = REPLACE(case when length(txt_base_subs_trib )  = 0 then '0' else txt_base_subs_trib end  ,',','.')::numeric";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "UPDATE hitachi_nf_item SET posto = (
						SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
											AND   tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN hitachi_nf on hitachi_nf.txt_nota_fiscal = hitachi_nf_item.txt_nota_fiscal
						WHERE hitachi_nf.txt_cnpj = tbl_posto.cnpj
					)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "UPDATE hitachi_nf_item
					SET pedido = tbl_pedido.pedido,
						pedido_item = tbl_pedido_item.pedido_item
					FROM tbl_pedido_item, tbl_pedido
					WHERE hitachi_nf_item.txt_pedido_item::integer = tbl_pedido_item.pedido_item
					AND tbl_pedido_item.pedido = tbl_pedido.pedido
					AND tbl_pedido.fabrica = $login_fabrica
					-- AND (txt_pedido is not null and length (trim (txt_pedido))> 0)
					;";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "UPDATE hitachi_nf_item
		                SET os_item = tbl_os_item.os_item,
		               		devolucao = tbl_os_item.peca_obrigatoria
		                FROM tbl_os_item
		                WHERE hitachi_nf_item.pedido = tbl_os_item.pedido
						AND tbl_os_item.fabrica_i = $login_fabrica
						AND tbl_os_item.pedido_item = hitachi_nf_item.pedido_item
		                ;";
		    $res = pg_query($con,$sql);
		    $msg_erro .= pg_last_error($con);
			
			$sql = "UPDATE hitachi_nf_item
					SET peca = tbl_peca.peca
					FROM  tbl_peca
					WHERE hitachi_nf_item.txt_referencia = tbl_peca.referencia
					AND tbl_peca.fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$sql = "UPDATE hitachi_nf
						SET pedido = hitachi_nf_item.pedido
					FROM hitachi_nf_item
					WHERE trim(hitachi_nf.txt_nota_fiscal) = trim(hitachi_nf_item.txt_nota_fiscal)
					AND  hitachi_nf.posto = hitachi_nf_item.posto
					";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			#------------ Desconsidera Notas ja Importadas ------------------
		    $sql = "DELETE FROM hitachi_nf
					WHERE pedido is null;";
			#$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	

			$sql = "DELETE FROM hitachi_nf_item
					WHERE pedido is null;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	

			$sql = "DELETE FROM hitachi_nf
					USING tbl_faturamento
					WHERE txt_nota_fiscal         = tbl_faturamento.nota_fiscal
					AND   txt_serie               = tbl_faturamento.serie
					AND   tbl_faturamento.fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	
			$sql = "DELETE FROM hitachi_nf_item
					USING tbl_faturamento
					JOIN tbl_faturamento_item USING(faturamento)
					WHERE txt_nota_fiscal         = tbl_faturamento.nota_fiscal
					AND   txt_serie               = tbl_faturamento.serie
					AND   tbl_faturamento_item.peca = hitachi_nf_item.peca
					AND   tbl_faturamento.fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			#------------ NFs sem Itens --------------#
			$sql = "DROP TABLE IF EXISTS hitachi_nf_sem_itens";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	

			$sql = "SELECT hitachi_nf.*
					INTO hitachi_nf_sem_itens
					FROM hitachi_nf
					LEFT JOIN hitachi_nf_item ON hitachi_nf.txt_nota_fiscal = hitachi_nf_item.txt_nota_fiscal
					WHERE hitachi_nf_item.txt_nota_fiscal IS NULL";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	

			$sql = "DELETE FROM hitachi_nf 
					USING hitachi_nf_sem_itens
					WHERE hitachi_nf.txt_nota_fiscal = hitachi_nf_sem_itens.txt_nota_fiscal
					AND   hitachi_nf.txt_serie       = hitachi_nf_sem_itens.txt_serie";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			#----------------- Importa REALMENTE ------------
			$sql = "INSERT INTO tbl_faturamento (
						fabrica     ,
						emissao     ,
		                saida,
						transp      ,
						posto       ,
						total_nota  ,
						cfop        ,
						nota_fiscal ,
						serie       ,
						tipo_pedido
					)
						SELECT  $login_fabrica,
								hitachi_nf.emissao         ,
		                        hitachi_nf.saida,
								substring(hitachi_nf.txt_transp, 1,30),
								hitachi_nf.posto           ,
								hitachi_nf.total           ,
								hitachi_nf.txt_cfop        ,
								hitachi_nf.txt_nota_fiscal ,
								hitachi_nf.txt_serie       ,
								(select tipo_pedido from tbl_pedido where pedido = hitachi_nf.pedido)
						FROM hitachi_nf
						LEFT JOIN tbl_faturamento ON  hitachi_nf.txt_nota_fiscal   = tbl_faturamento.nota_fiscal
												 AND  hitachi_nf.txt_serie         = tbl_faturamento.serie
												 AND  tbl_faturamento.fabrica      = $login_fabrica
						WHERE tbl_faturamento.faturamento IS NULL
					";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN faturamento INT4";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	

			$sql = "ALTER TABLE hitachi_nf_item ADD COLUMN cfop text";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);	

			$sql = "UPDATE hitachi_nf_item
					SET faturamento = tbl_faturamento.faturamento, cfop = tbl_faturamento.cfop
					FROM tbl_faturamento
					WHERE tbl_faturamento.fabrica     = $login_fabrica
					AND   tbl_faturamento.nota_fiscal = hitachi_nf_item.txt_nota_fiscal
					AND   tbl_faturamento.serie       = hitachi_nf_item.txt_serie";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			#------ Tratar itens sem nota ------

			$sql = "DELETE FROM hitachi_nf_item 
					WHERE faturamento IS NULL";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "DROP TABLE IF EXISTS hitachi_nf_item_sem_peca ";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			

			$sql = "SELECT * INTO hitachi_nf_item_sem_peca 
					FROM hitachi_nf_item 
						WHERE peca IS NULL" ;
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "DELETE FROM hitachi_nf_item 
					WHERE peca IS NULL" ;
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$sql = "SELECT  DISTINCT faturamento,
							pedido     ,
							pedido_item,
							peca       ,
							qtde as qtde_fat,
							cfop       ,
							unitario   ,
							aliq_ipi   ,
		                    aliq_icms  ,
							valor_ipi  ,
							valor_icms ,
		                    valor_subs_trib,
							base_icms  ,
		                    base_ipi,
		                    base_subs_trib,
							txt_referencia,
							devolucao,
							os_item
					FROM hitachi_nf_item;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			for($x = 0; $x < pg_numrows($res); $x++){
				$pedido          = pg_fetch_result($res,$x,'pedido');
				$pedido_item     = pg_fetch_result($res,$x,'pedido_item');
				$faturamento     = pg_fetch_result($res,$x,'faturamento');
				$peca            = pg_fetch_result($res,$x,'peca');
				$cfop            = pg_fetch_result($res,$x,'cfop');
				$qtde_fat        = pg_fetch_result($res,$x,'qtde_fat');
				$unitario        = pg_fetch_result($res,$x,'unitario');
				$aliq_ipi        = pg_fetch_result($res,$x,'aliq_ipi');
				$aliq_icms       = pg_fetch_result($res,$x,'aliq_icms');
				$valor_ipi       = pg_fetch_result($res,$x,'valor_ipi');
				$valor_icms      = pg_fetch_result($res,$x,'valor_icms');
				$valor_subs_trib = pg_fetch_result($res,$x,'valor_subs_trib');
				$base_icms       = pg_fetch_result($res,$x,'base_icms');
				$base_ipi        = pg_fetch_result($res,$x,'base_ipi');
				$base_subs_trib  = pg_fetch_result($res,$x,'base_subs_trib');
				$txt_referencia  = pg_fetch_result($res,$x,'txt_referencia');
				$devolucao_obrig = (pg_fetch_result($res,$x,'devolucao')== '') ? 'f' : 't';
				$os_item         = pg_fetch_result($res,$x,'os_item');
				if (empty($os_item)) $os_item = "null";

				 $sql = "INSERT INTO tbl_faturamento_item (
							faturamento,
							pedido     ,
							pedido_item,
							peca       ,
							qtde       ,
							preco      ,
							aliq_ipi   ,
		                    aliq_icms  ,
		                    valor_ipi  ,
		                    valor_icms ,
		                    valor_subs_trib,
		                    base_icms  ,
		                    base_ipi,
		                    base_subs_trib,
							cfop,
							os_item    ,
							devolucao_obrig
						)
						VALUES(
							$faturamento,
							$pedido     ,
							$pedido_item,
							$peca       ,
							$qtde_fat   ,
							$unitario   ,
							$aliq_ipi   ,
		                    $aliq_icms  ,
		                    $valor_ipi  ,
		                    $valor_icms ,
		                    $valor_subs_trib,
		                    $base_icms  ,
		                    $base_ipi,
		                    $base_subs_trib,
		                    '$cfop',
				    		$os_item    ,
				    		'$devolucao_obrig'
						)";
				$res2 = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

				$sql = "SELECT  qtde as qtde_pedido,
								pedido_item,
								tipo_pedido,
								posto
						FROM tbl_pedido
						JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
						WHERE tbl_pedido.pedido = $pedido
							AND tbl_pedido.fabrica = $login_fabrica
							AND peca = $peca
							AND qtde > qtde_faturada
						LIMIT 1;";
				$res2 = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

				if(pg_num_rows($res2) > 0){
					$pedido_item = pg_fetch_result($res2,0,'pedido_item');
					$qtde_pedido = pg_fetch_result($res2,0,'qtde_pedido');
					$tipo_pedido = pg_fetch_result($res2,0,'tipo_pedido');
					$posto_pedido = pg_fetch_result($res2,0,'posto');

					$sql = "UPDATE tbl_pedido_item
							SET qtde_faturada =  (qtde_faturada + $qtde_fat)
							WHERE pedido_item = $pedido_item;";
					$res3 = pg_query($con,$sql);
					$msg_erro .= pg_last_error($con);
				}else{
					$msg_erro .= " Não foi encontrado o item para atualizar: \n";
					$msg_erro .= " Pedido: $pedido - Peça: $txt_referencia - Qtd.Fat: $qtde_fat \n";
					$msg_erro .= "\n\n\n";
				}

				$sql = "UPDATE tbl_faturamento_item
						SET aliq_icms     = round((valor_icms / (preco*qtde))*100)
						WHERE faturamento = $faturamento ";
				$res3 = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);
			}
			
			$sql = "SELECT fn_atualiza_status_pedido($login_fabrica,pedido)
						FROM hitachi_nf_item;";
			$res3 = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);


			}

		if (!empty($msg_erro)) {
			$msg_erro .= "\n\n".$log_erro;
			$fp = fopen("/tmp/hitachi/faturamento.err","w");
			fwrite($fp,$msg_erro);
			fclose($fp);
			$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
			#Log::envia_email($data, APP, $msg);

		} else {
			$fp = fopen("/tmp/hitachi/faturamento.err","w");
			fwrite($fp,$log_erro);
			fclose($fp);

		#	system("cp $origem$file /tmp/hitachi/faturamento".date('Y-m-d-H-i').".txt");
		#	system("cp $origem$file2 /tmp/hitachi/faturamento_item".date('Y-m-d-H-i').".txt");

			Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

		}

		#system("mv $arquivo /tmp/$fabrica_nome/faturamento/telecontrol-importa_faturamento-$data.txt");
	}

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - hitachi - Importa faturamento (importa-faturamento.php)", $msg);
}
