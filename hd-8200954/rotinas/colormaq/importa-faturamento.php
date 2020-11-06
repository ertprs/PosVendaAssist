<?php
/**
 *
 * importa-faturamento.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Ronald Santos
 * @version 2012.07.31
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 		= 50;
    $data['fabrica'] 	= 'colormaq';
    $data['arquivo_log'] 	= 'importa-faturamento';
	$data['tipo'] 	= 'importa-faturamento';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

    if (ENV == 'producao' ) {
	    $data['dest'] 		= 'helpdesk@telecontrol.com.br';
	    $data['dest_cliente']  	= 'posvendafaturamento@colormaq.com.br,antoniocarlos@colormaq.com.br';
	    $data['origem']		= "/home/colormaq/colormaq-telecontrol/";
	    $data['file']		= 'Faturamento.txt';
	    $data['file2']		= 'Faturamento_Item.txt';
    } else {
	    $data['dest'] 		= 'guilherme.silva@telecontrol.com.br';
	    $data['dest_cliente'] 	= 'guilherme.silva@telecontrol.com.br,ederson.sandre@telecontrol.com.br';
	    $data['origem']		= dirname(__FILE__) . "/entrada/";
	    $data['file']		= 'Faturamento.txt';
	    $data['file2']		= 'Faturamento_Item.txt';
    }

    extract($data);
	
	define('APP', 'Importa Faturamento - '.$login_fabrica_nome);

    $arquivo_err = "{$arquivos}/{$login_fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$login_fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    
    system ("mkdir {$arquivos}/{$login_fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$login_fabrica_nome}/" );
    
    
    if(file_exists($origem.$file)){
	  
	  $sql = "DROP TABLE IF EXISTS colormaq_nf;";
	  $res = pg_query($con,$sql);
	  $msg_erro .= pg_errormessage($con);
	

	  $sql = "CREATE TABLE colormaq_nf (
				  txt_cnpj           text,
				  txt_nota_fiscal    text,
				  txt_serie          text,
				  txt_emissao        text,
				  txt_cfop           text,
				  txt_total          text,
				  txt_ipi            text,
				  txt_icms           text,
				  txt_transp         text,
				  txt_pedido_blackedecker text,
				  txt_saida          text,
				  txt_transp2        text,
				  txt_frete          text
			  )";
	  $res = pg_query($con,$sql);
	  $msg_erro .= pg_errormessage($con);
	  
	  $linhas = file_get_contents($origem.$file);
	  $linhas = explode("\n",$linhas);
	  
	  $erro = $msg_erro;
	  
	  foreach($linhas AS $linha){
	  
		$msg_erro = "";

			list($txt_cnpj, $txt_nota_fiscal, $txt_serie, $txt_emissao, $txt_cfop, $txt_total, $txt_ipi, $txt_icms, $txt_transp, $txt_pedido_blackedecker, $txt_saida, $txt_transp2, $txt_frete) = explode("|",$linha);

			if(!empty($txt_cnpj)){
				
				$txt_cnpj = str_replace('.','',$txt_cnpj);
				$txt_cnpj = str_replace('/','',$txt_cnpj);
				$txt_cnpj = str_replace('-','',$txt_cnpj);
				$txt_frete = str_replace("\r","",$txt_frete);

				$res = pg_query($con,"BEGIN");
				$sql = "INSERT INTO colormaq_nf ( txt_cnpj                 ,
								    txt_nota_fiscal         ,
								    txt_serie               ,
								    txt_emissao             ,
								    txt_cfop                ,
								    txt_total               ,
								    txt_ipi                 ,
								    txt_icms                ,
								    txt_transp              ,
								    txt_pedido_blackedecker ,
								    txt_saida               ,
								    txt_transp2             ,
								    txt_frete
								  ) VALUES (
								      '$txt_cnpj'                ,
								      '$txt_nota_fiscal'         ,
								      '$txt_serie'               ,
								      '$txt_emissao'             ,
								      '$txt_cfop'                ,
								      '$txt_total'               ,
								      '$txt_ipi'                 ,
								      '$txt_icms'                ,
								      '$txt_transp'              ,
								      '$txt_pedido_blackedecker' ,
								      '$txt_saida'               ,
								      '$txt_transp2'             ,
								      '$txt_frete'   
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
	  
	  $sql = "UPDATE colormaq_nf SET
				txt_cnpj        = trim (txt_cnpj)                      ,
				txt_nota_fiscal = lpad (TRIM(txt_nota_fiscal) ,9, '0') ,
				txt_serie       = trim (txt_serie)                     ,
				txt_emissao     = trim (txt_emissao)                   ,
				txt_transp      = trim (txt_transp)                    ,
				txt_cfop        = trim (txt_cfop)                      ,
				txt_total       = trim (txt_total)                     ,
				txt_pedido_blackedecker = trim(txt_pedido_blackedecker),
				txt_saida       = trim (txt_saida)                     ,
				txt_transp2     = trim (txt_transp2)                   ,
				txt_frete       = trim (txt_frete)                    ;";
	$res = pg_query($con,$sql);
	
	$sql = "ALTER TABLE colormaq_nf ADD COLUMN total FLOAT";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf ADD COLUMN emissao DATE";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf ADD COLUMN saida DATE";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf ADD COLUMN posto INT4";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf ADD COLUMN pedido INT4";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf ADD COLUMN valor_frete FLOAT";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "UPDATE colormaq_nf SET
				emissao     = TO_DATE(txt_emissao,'YYYY-MM-DD'),
				saida       = CASE WHEN length(txt_saida) = 0 then TO_DATE(txt_emissao,'YYYY-MM-DD') else TO_DATE(txt_saida,'YYYY-MM-DD') end,
				total       = REPLACE(txt_total,',','.')::numeric,
				valor_frete = REPLACE(txt_frete,',','.')::numeric";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "UPDATE colormaq_nf SET posto = (
				SELECT tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND   tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE colormaq_nf.txt_cnpj = tbl_posto.cnpj
			)";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	#------------ IDENTIFICAR POSTOS NAO ENCONTRADOS PELO CNPJ --------------#
	$sql = "DROP TABLE IF EXISTS colormaq_nf_sem_posto";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "SELECT * INTO colormaq_nf_sem_posto FROM colormaq_nf WHERE posto IS NULL";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "DELETE FROM colormaq_nf
			WHERE posto IS NULL";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "DROP TABLE colormaq_nf_item ";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "CREATE TABLE colormaq_nf_item (
				txt_cnpj        text,
				txt_nota_fiscal text,
				txt_serie       text,
				txt_referencia  text,
				txt_pedido      text,
				txt_pedido_item text,
				txt_qtde        text,
				txt_unitario    text,
				txt_aliq_ipi    text,
				txt_valor_ipi   text,
				txt_valor_icms  text,
				txt_base_icms   text
			)";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$linhas_item = file_get_contents($origem.$file2);
	$linhas_item = explode("\n",$linhas_item);
	
	$erro = $msg_erro;
	
	foreach($linhas_item AS $linha_item){
	      
	      list($txt_cnpj, $txt_nota_fiscal , $txt_serie , $txt_referencia  , $txt_pedido, $txt_pedido_item , $txt_qtde, $txt_unitario, $txt_aliq_ipi, $txt_valor_ipi, $txt_valor_icms, $txt_base_icms) = explode ('|',$linha_item);

	      if(!empty($txt_cnpj)){
				
			  $txt_cnpj = str_replace('.','',$txt_cnpj);
			  $txt_cnpj = str_replace('/','',$txt_cnpj);
			  $txt_cnpj = str_replace('-','',$txt_cnpj);

			  $txt_base_icms = str_replace("\r","",$txt_base_icms);

		      $sql = "INSERT INTO colormaq_nf_item ( txt_cnpj         ,
							  txt_nota_fiscal ,
							  txt_serie       ,
							  txt_referencia  ,
							  txt_pedido      ,
							  txt_pedido_item ,
							  txt_qtde        ,
							  txt_unitario    ,
							  txt_aliq_ipi    ,
							  txt_valor_ipi   ,
							  txt_valor_icms  ,
							  txt_base_icms	
							) VALUES (
							    '$txt_cnpj'        ,
							    '$txt_nota_fiscal' ,
							    '$txt_serie'       ,
							    '$txt_referencia'  ,
							    '$txt_pedido'      ,
							    '$txt_pedido_item' ,
							    '$txt_qtde'        ,
							    '$txt_unitario'    ,
							    '$txt_aliq_ipi'    ,
							    '$txt_valor_ipi'   ,
							    '$txt_valor_icms'  ,
							    '$txt_base_icms'      
							);";
		      $res = pg_query($con,$sql);
		      $msg_erro .= pg_errormessage($con);

	      }
			
	}
	
	$msg_erro = $erro;
	
	$sql = "UPDATE colormaq_nf_item SET
				txt_cnpj        = TRIM(txt_cnpj)                     ,
				txt_nota_fiscal = LPAD(TRIM(txt_nota_fiscal) ,9, '0'),
				txt_serie       = TRIM(txt_serie)                    ,
				txt_referencia  = TRIM(txt_referencia)               ,
				txt_pedido      = TRIM(txt_pedido)                   ,
				txt_qtde        = TRIM(txt_qtde)                     ,
				txt_unitario    = TRIM(txt_unitario)                 ,
				txt_aliq_ipi    = TRIM(txt_aliq_ipi)                 ,
				txt_valor_ipi   = TRIM(txt_valor_ipi)                ,
				txt_valor_icms  = TRIM(txt_valor_icms)               ,
				txt_base_icms   = TRIM(txt_base_icms)               ";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN posto INT4;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN peca INT4;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN qtde FLOAT;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN pedido INT4;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN tipo_pedido INT4;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN unitario FLOAT;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN aliq_ipi FLOAT;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN valor_ipi FLOAT;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN valor_icms FLOAT;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN base_icms FLOAT;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "UPDATE colormaq_nf_item SET
				qtde       = txt_qtde::numeric                        ,
				unitario   = REPLACE(case when length(txt_unitario)    = 0 then '0' else txt_unitario end   ,',','.')::numeric   ,
				aliq_ipi   = REPLACE(case when length(txt_aliq_ipi )   = 0 then '0' else txt_aliq_ipi end   ,',','.')::numeric   ,
				valor_ipi  = REPLACE(case when length(txt_valor_ipi )  = 0 then '0' else txt_valor_ipi end  ,',','.')::numeric  ,
				txt_pedido =  regexp_replace(txt_pedido,'\D','','g') , 
				valor_icms = REPLACE(case when length(txt_valor_icms ) = 0 then '0' else txt_valor_icms end ,',','.')::numeric ,
				base_icms  = REPLACE(case when length(txt_base_icms )  = 0 then '0' else txt_base_icms end  ,',','.')::numeric  ";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "UPDATE colormaq_nf_item SET posto = (
				SELECT tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND   tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE colormaq_nf_item.txt_cnpj = tbl_posto.cnpj
			)";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "DELETE FROM colormaq_nf_item  WHERE length(txt_pedido) < 3;

			UPDATE colormaq_nf_item
			SET pedido = tbl_pedido.pedido
			FROM tbl_pedido
			WHERE colormaq_nf_item.txt_pedido::numeric = tbl_pedido.pedido
			AND tbl_pedido.fabrica = $login_fabrica
			AND (txt_pedido is not null and length (trim (txt_pedido))> 0);";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "UPDATE colormaq_nf_item
			SET peca = tbl_peca.peca
			FROM  tbl_peca
			WHERE colormaq_nf_item.txt_referencia = tbl_peca.referencia
			AND tbl_peca.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$sql = "UPDATE colormaq_nf
				SET pedido = colormaq_nf_item.pedido
			FROM colormaq_nf_item
			WHERE trim(colormaq_nf.txt_nota_fiscal) = trim(colormaq_nf_item.txt_nota_fiscal)
			AND  colormaq_nf.posto = colormaq_nf_item.posto";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "UPDATE colormaq_nf_item SET tipo_pedido = (
				SELECT tbl_pedido.tipo_pedido
				FROM tbl_pedido
				WHERE colormaq_nf_item.pedido = tbl_pedido.pedido
			)";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	#------------ Desconsidera Notas ja Importadas ------------------
	
	$sql = "DELETE FROM colormaq_nf
			WHERE pedido is null;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);	

	$sql = "DELETE FROM colormaq_nf_item
			WHERE pedido is null;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);	

	$sql = "DELETE FROM colormaq_nf
			USING tbl_faturamento
			WHERE txt_nota_fiscal         = tbl_faturamento.nota_fiscal
			AND   txt_serie               = tbl_faturamento.serie
			AND   tbl_faturamento.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);	

	$sql = "DELETE FROM colormaq_nf_item
			USING tbl_faturamento
			JOIN	tbl_faturamento_item USING(faturamento)
			WHERE txt_nota_fiscal         = tbl_faturamento.nota_fiscal
			AND   txt_serie               = tbl_faturamento.serie
			AND   colormaq_nf_item.peca = tbl_faturamento_item.peca
			AND   tbl_faturamento.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	#------------ NFs sem Itens --------------#
	$sql = "DROP TABLE IF EXISTS colormaq_nf_sem_itens";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);	

	$sql = "SELECT colormaq_nf.*
			INTO colormaq_nf_sem_itens
			FROM colormaq_nf
			LEFT JOIN colormaq_nf_item ON colormaq_nf.txt_nota_fiscal = colormaq_nf_item.txt_nota_fiscal
			WHERE colormaq_nf_item.txt_nota_fiscal IS NULL";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);	

	$sql = "DELETE FROM colormaq_nf 
			USING colormaq_nf_sem_itens
			WHERE colormaq_nf.txt_nota_fiscal = colormaq_nf_sem_itens.txt_nota_fiscal
			AND   colormaq_nf.txt_serie       = colormaq_nf_sem_itens.txt_serie";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	#----------------- Importa REALMENTE ------------
	$sql = "INSERT INTO tbl_faturamento (
				fabrica     ,
				emissao     ,
				saida       ,
				transp      ,
				posto       ,
				total_nota  ,
				cfop        ,
				nota_fiscal ,
				serie       ,
				tipo_pedido,
				valor_frete
			)
				SELECT  $login_fabrica,
						colormaq_nf.emissao         ,
						colormaq_nf.saida         ,
						substring(colormaq_nf.txt_transp2, 1,30),
						colormaq_nf.posto           ,
						colormaq_nf.total           ,
						colormaq_nf.txt_cfop        ,
						colormaq_nf.txt_nota_fiscal ,
						colormaq_nf.txt_serie       ,
						(select tipo_pedido from tbl_pedido where pedido = colormaq_nf.pedido),
						case when colormaq_nf.valor_frete is null then 0 else colormaq_nf.valor_frete end as valor_frete
				FROM colormaq_nf
				LEFT JOIN tbl_faturamento ON  colormaq_nf.txt_nota_fiscal   = tbl_faturamento.nota_fiscal
										 AND  colormaq_nf.txt_serie         = tbl_faturamento.serie
										 AND  tbl_faturamento.fabrica      = $login_fabrica
										 AND  tbl_faturamento.distribuidor IS NULL
				WHERE tbl_faturamento.faturamento IS NULL
			";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);	

	$sql = "UPDATE tbl_pedido
			set pedido_blackedecker = txt_pedido_blackedecker::integer
		FROM colormaq_nf
		WHERE tbl_pedido.fabrica = $login_fabrica
			and tbl_pedido.pedido = colormaq_nf.pedido
			and txt_pedido_blackedecker is not null ;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "ALTER TABLE colormaq_nf_item ADD COLUMN faturamento INT4";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);	

	$sql = "UPDATE colormaq_nf_item
			SET faturamento = tbl_faturamento.faturamento
			FROM tbl_faturamento
			WHERE tbl_faturamento.fabrica     = $login_fabrica
			AND   tbl_faturamento.nota_fiscal = colormaq_nf_item.txt_nota_fiscal
			AND   tbl_faturamento.serie       = colormaq_nf_item.txt_serie";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	#------ Tratar itens sem nota ------

	$sql = "DELETE FROM colormaq_nf_item 
			WHERE faturamento IS NULL";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	

	$sql = "DROP TABLE colormaq_nf_item_sem_peca ";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	

	$sql = "SELECT * INTO colormaq_nf_item_sem_peca 
			FROM colormaq_nf_item 
				WHERE peca IS NULL" ;
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	

	$sql = "DELETE FROM colormaq_nf_item 
			WHERE peca IS NULL" ;
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	

	$sql = "SELECT  DISTINCT faturamento,
					pedido     ,
					peca       ,
					qtde as qtde_fat,
					unitario   ,
					aliq_ipi   ,
					valor_ipi  ,
					valor_icms ,
					base_icms  ,
					txt_referencia,
					txt_nota_fiscal
			FROM colormaq_nf_item;";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	for($x = 0; $x < pg_numrows($res); $x++){
		$pedido          = pg_result($res,$x,'pedido');
		$faturamento     = pg_result($res,$x,'faturamento');
		$peca            = pg_result($res,$x,'peca');
		$qtde_fat        = pg_result($res,$x,'qtde_fat');
		$unitario        = pg_result($res,$x,'unitario');
		$txt_nota_fiscal = pg_result($res,$x,'txt_nota_fiscal');
		$aliq_ipi        = pg_result($res,$x,'aliq_ipi');
		$valor_ipi       = pg_result($res,$x,'valor_ipi');
		$valor_icms      = pg_result($res,$x,'valor_icms');
		$base_icms       = pg_result($res,$x,'base_icms');
		$txt_referencia  = pg_result($res,$x,'txt_referencia');

		$sql = "INSERT INTO tbl_faturamento_item (
					faturamento,
					pedido     ,
					peca       ,
					qtde       ,
					preco      ,
					aliq_ipi   ,
					valor_ipi  ,
					valor_icms ,
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
					$base_icms
				)";
		$res2 = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

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
		$msg_erro .= pg_errormessage($con);

		if(pg_numrows($res2) > 0){

			$pedido_item = pg_result($res2,0,'pedido_item');
			$qtde_pedido = pg_result($res2,0,'qtde_pedido');
			$tipo_pedido = pg_result($res2,0,'tipo_pedido');
			$posto_pedido = pg_result($res2,0,'posto');

			$sql = "select fn_atualiza_pedido_item($peca, $pedido, $pedido_item, $qtde_fat)";
			$res3 = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			/* Tipo pedido Garantia Antecipada */
			$sql_tipo_pedido = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = {$login_fabrica} AND garantia_antecipada IS TRUE";
			$res_tipo_pedido = pg_query($con, $sql_tipo_pedido);
			if(pg_num_rows($res_tipo_pedido) > 0){
				$tipo_pedido_garantia_antecipada = pg_fetch_result($res_tipo_pedido, 0, "tipo_pedido");
			}

			if($tipo_pedido == $tipo_pedido_garantia_antecipada){

				if($tipo_pedido == $tipo_pedido_garantia_antecipada){
					$tipo_estoque = " AND tipo = 'pulmao' ";
					$campo_tipo_estoque = " ,tipo ";
					$valor_tipo_estoque = " ,'pulmao' ";
				}
				
				$sql = "SELECT posto,peca FROM tbl_estoque_posto WHERE fabrica = $login_fabrica AND posto = $posto_pedido AND peca = $peca $tipo_estoque";
				$res3 = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if(pg_numrows($res3) > 0){
					
					$sql = "UPDATE tbl_estoque_posto SET
							qtde = qtde + $qtde_fat
							WHERE fabrica = $login_fabrica
							AND posto = $posto_pedido
							AND peca = $peca 
							$tipo_estoque";
					$res4 = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					$sql = "INSERT INTO tbl_estoque_posto(
															fabrica,
															posto,
															peca,
															qtde 
															$campo_tipo_estoque
														) VALUES(
															$login_fabrica,
															$posto_pedido,
															$peca,
															$qtde_fat 
															$valor_tipo_estoque
														)";
					$res4 = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				$sql = "SELECT os FROM tbl_os_produto JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto WHERE tbl_os_item.pedido = $pedido AND tbl_os_item.pedido_item = $pedido_item";
				$resOS = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$os = (pg_numrows($resOS) > 0) ? pg_result($resOS,0,'os') : "null";
				$tipo = ($tipo_pedido == 173) ? "Doação" : "Garantia";
				$tipo = ($tipo_pedido == $tipo_pedido_garantia_antecipada) ? "pulmao" : $tipo;

				$sql = "INSERT INTO tbl_estoque_posto_movimento(
																fabrica,
																posto,
																os,
																peca,
																data,
																qtde_entrada,
																faturamento,
																pedido,
																obs,
																nf,
																tipo) VALUES(
																$login_fabrica,
																$posto_pedido,
																$os,
																$peca,
																CURRENT_DATE,
																$qtde_fat,
																$faturamento,
																$pedido,
																'Reposição de estoque',
																'$txt_nota_fiscal',
																'$tipo'
																)
																";
				$res4 = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
			}

		}else{
			$msg_erro .= " Não foi encontrado o item para atualizar: \n";
			$msg_erro .= " Pedido: $pedido - Peça: $txt_referencia - Qtd.Fat: $qtde_fat \n";
			$msg_erro .= "\n\n\n";
		}

		$sql = "UPDATE tbl_faturamento_item
				SET aliq_icms     = round((valor_icms / (preco*qtde))*100)
				WHERE faturamento = $faturamento ";
		$res3 = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	
		$sql = "SELECT fn_atualiza_pedido_recebido_fabrica(pedido,$login_fabrica, current_date)
			from colormaq_nf_item";
		$res3 = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "SELECT fn_atualiza_status_pedido($login_fabrica,pedido)
				FROM colormaq_nf_item;";
		$res3 = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	
	if (!empty($msg_erro)) {
		$msg_erro .= "\n\n".$log_erro;
		$fp = fopen("/tmp/colormaq/faturamento.err","w");
		fwrite($fp,$msg_erro);
		fclose($fp);
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($data, APP, $msg);

	} else {
		$fp = fopen("/tmp/colormaq/faturamento.err","w");
		fwrite($fp,$log_erro);
		fclose($fp);

		Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

	}

	system("mv $origem$file /tmp/colormaq/Faturamento".date('Y-m-d-H-i').".txt");
	system("mv $origem$file2 /tmp/colormaq/Faturamento_item".date('Y-m-d-H-i').".txt");


} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - COLORMAQ - Importa faturamento (importa-faturamento.php)", $msg);
}?>
