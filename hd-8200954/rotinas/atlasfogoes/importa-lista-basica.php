<?php
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';


$fabrica  = "74" ;
$origem   = "/home/atlas/atlas-telecontrol";
$erro     = "/tmp/atlas";


/* Inicio Processo */
$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();

$data = date('Y-m-d-h-s');

$okarquivo = "";
if(file_exists("$origem/lista_basica.txt")) {

	$lista_basica = file("$origem/lista_basica.txt");

	$okarquivo = "ok";
	$sql = "DROP TABLE IF EXISTS atlas_lista_basica";
	$result = pg_query($con, $sql);

	$sql = "CREATE TABLE atlas_lista_basica (
				referencia_produto   varchar(20),
				referencia_peca      varchar(20),
				posicao              varchar(50),
				qtde                 text
			)";
	$result = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= "Falha ao criar tabela atlas_lista_basica ".pg_last_error($con). "<Br>";
	}	

	#-------------- Importa Arquivo de Lista Básica -------#
	$num_linha = 1;
	foreach($lista_basica as $linha){
		$valores = explode("\t", $linha);

		$referencia_produto 	= trim($valores[0]);
		$referencia_peca 		= trim($valores[1]);
		$posicao 				= trim($valores[2]);
		$qtde 					= trim($valores[3]);
		
		$sql_produto = "select produto from tbl_produto where referencia = '" .strtoupper($referencia_produto)."'";
		$res_produto = pg_query($con, $sql_produto);
		if(pg_num_rows($res_produto)==0){
			$log .= "Linha $num_linha - O produto de referência - $referencia_produto não foi encontrado. \n\n";
			$log_controle = "ok";
		}
		$sql_peca = "select peca from tbl_peca where referencia = '$referencia_peca'";
		$res_peca = pg_query($con, $sql_peca);
		if(pg_num_rows($res_peca)==0){
			$log .= "Linha $num_linha - A peça de referência - $referencia_peca não foi encontrada. \n\n";
			$log_controle = "ok";
		}		

		if($log_controle == ""){
			$sql = "insert into atlas_lista_basica (
					referencia_produto   ,
					referencia_peca      ,
					posicao              ,
					qtde                 
				) values (
					'$referencia_produto'   ,
					'$referencia_peca'      ,
					'$posicao'              ,
					'$qtde'      
				) ";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Falha ao gravar na tabela atlas_lista_basica ".pg_last_error($con). "<Br>";
			}
		}
		$num_linha ++;
	}
		
	$sql = "UPDATE atlas_lista_basica set qtde = REPLACE(qtde,'.','')";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= "Falha ao atualizar qtde ".pg_last_error($con). "<Br>";
	}
	
	$sql = "UPDATE atlas_lista_basica set qtde = REPLACE(qtde,',','.')";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}
	
	
	$sql = "ALTER TABLE atlas_lista_basica ADD column produto int4";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}

	$sql = "ALTER TABLE atlas_lista_basica ADD column peca int4";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}

	$sql = "UPDATE atlas_lista_basica SET produto = tbl_produto.produto
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  upper(trim(atlas_lista_basica.referencia_produto)) = tbl_produto.referencia
			AND    tbl_linha.linha                                       = tbl_produto.linha
			AND    tbl_linha.fabrica                                     = $fabrica";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}
		
	$sql = "UPDATE atlas_lista_basica SET peca = tbl_peca.peca
			FROM   tbl_peca
			WHERE  upper(trim(atlas_lista_basica.referencia_peca)) = upper(trim(tbl_peca.referencia))
			AND    tbl_peca.fabrica                                    = $fabrica";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}
	
	
	$sql = "DROP TABLE IF EXISTS atlas_lista_basica_falha_produto;";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}
	

	### GERA ARQUIVO COM PRODUTOS NÃO ENCONTRADOS
	$sql = "SELECT referencia_produto
			INTO TEMP atlas_lista_basica_falha_produto
			FROM      atlas_lista_basica
			WHERE     (atlas_lista_basica.produto IS NULL)";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}

	$sql = "select * from atlas_lista_basica_falha_produto";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$dados_produto = "Produtos que não foram encontrados. \n";
	}
	for ($i=0; $i<pg_num_rows($res); $i++){ 
		$referencia_produto = pg_fetch_result($res, $i, 'referencia_produto');
		$dados_produto .= $referencia_produto. "\n";
	}		


	### GERA ARQUIVO COM PEÇAS NÃO ENCONTRADAS
	$sql = "DROP TABLE IF EXISTS atlas_lista_basica_falha_peca;";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}

	$sql = "SELECT referencia_peca
			INTO TEMP atlas_lista_basica_falha_peca
			FROM      atlas_lista_basica
			WHERE     (atlas_lista_basica.peca IS NULL)";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}

	$sql = "select * from atlas_lista_basica_falha_peca";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$dados = "Peças que não foram encontradas  \n";
	}
	for ($i=0; $i<pg_num_rows($res); $i++){ 
		$referencia_peca = pg_fetch_result($res, $i, 'referencia_peca');
		$dados .= $referencia_peca. "\n";
	}

	### DELETA LISTA BÁSICA COM ERROS
	$sql = "DELETE FROM atlas_lista_basica
			WHERE  (atlas_lista_basica.produto IS NULL OR atlas_lista_basica.peca IS NULL)";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}
		
	### ALTERA REGISTROS JÁ CADASTRADOS
	$sql = "UPDATE  tbl_lista_basica SET
					qtde    = atlas_lista_basica.qtde::double precision,
					posicao = atlas_lista_basica.posicao
			FROM    atlas_lista_basica
			WHERE   tbl_lista_basica.produto = atlas_lista_basica.produto
			AND     tbl_lista_basica.peca    = atlas_lista_basica.peca
			AND     tbl_lista_basica.fabrica = $fabrica;";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}	

	### DELETA REGISTROS JÁ EXISTENTES
	$sql = "DELETE FROM atlas_lista_basica
			USING  tbl_lista_basica
			WHERE  tbl_lista_basica.produto = atlas_lista_basica.produto
			AND    tbl_lista_basica.peca    = atlas_lista_basica.peca
			AND    tbl_lista_basica.fabrica = $fabrica";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}

	# INSERE NOVA LISTA BASICA
	$sql = "INSERT INTO tbl_lista_basica (
					fabrica  ,
					produto  ,
					peca     ,
					posicao  ,
					qtde
			)
			SELECT  DISTINCT
					$fabrica                    ,
					atlas_lista_basica.produto  ,
					atlas_lista_basica.peca     ,
					atlas_lista_basica.posicao  ,
					atlas_lista_basica.qtde::double precision
			FROM    atlas_lista_basica
			WHERE 1=1;";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0){
		$msg_erro_interno .= pg_last_error($con). "<Br>";
	}


$phpCron->termino();

}
	
	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}

	
	if(!empty($msg_erro_interno)){
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "jeffersons@atlas.ind.br, evandro.carlos@atlas.ind.br, helpdesk@telecontrol.com.br";	
	    
	    $assunto   = "ATLAS - Integração Lista Básica Atlas - Erro Produtos";
		$mensagem  = "Segue dados da importação de produtos. \n ";
		$mensagem  .= "Produto - $dados_produto \n\n Peças $dados \n\n Erros $msg_erro_interno ";
		mail($para, $assunto, $mensagem, $headers);
	}

	if(!empty($log)){
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "ATLAS - Integração Lista Básica Atlas - Log Lista Básica";
		$mensagem  = "Segue em dados da importação de Lista Básica. \n";
		$mensagem .= "$log";
		mail($para, $assunto, $mensagem, $headers);
	}	

	if (file_exists("/home/atlas/atlas-telecontrol/lista_basica.txt")) {
		system("mv /home/atlas/atlas-telecontrol/lista_basica.txt  /tmp/atlas/lista_basica_$data.txt");
	}