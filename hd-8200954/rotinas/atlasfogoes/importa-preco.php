<?php

#!/usr/bin/perl 
#
# Telecontrol Networking
# www.telecontrol.com.br
# Importacao de Tabela de Precos da ATLAS FOG�ES
#
#
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$fabrica       	= "74" ;
	$login_fabrica 	= $fabrica ;
	$arquivos      	= "/home/atlas/atlas-telecontrol";
	$data 			= date('Y-m-d-h-s');
	
	/* Inicio Processo */ 
	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	function limpa_string($dados){
		$retirar = array("-",".", "/", "*");
		$dados = str_replace($retirar, "", $dados);
		return $dados;
	}

	if(file_exists("$arquivos/tabela_preco_item_venda.txt")){
		#---------------------------- Importa Tabelas de Precos -------------------------------
		$sql = "DROP TABLE atlas_preco";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao apagar tabela pre�o. ";
			$msg_erro_interno .= pg_last_error($con). "\n\n";
		}

		$sql = "CREATE TABLE atlas_preco (sigla_tabela text, referencia text, txt_preco text)";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao criar tabela";
			$msg_erro_interno .= pg_last_error($con)."\n\n";
		}

		$conteudo_arquivo = file("$arquivos/tabela_preco_item_venda.txt");
		$num_linha = 1;
		foreach ($conteudo_arquivo as $linha) {
			$valores = explode("\t",$linha);

			$sigla_tabela	= trim(limpa_string($valores[0]));
			$referencia		= trim(limpa_string($valores[1]));
			$txt_preco		= trim(limpa_string($valores[2]));

			$sql_peca = "select referencia from tbl_peca where fabrica=$login_fabrica and referencia = '$referencia' ";
			$res_peca = pg_query($con, $sql_peca);
			if(pg_num_rows($res_peca)==0){
				$log .= "$linha $num_linha - Refer�ncia $referencia n�o encontrada. \n";
			}

			$sql = "insert into atlas_preco (sigla_tabela, referencia, txt_preco) values ('$sigla_tabela', '$referencia', '$txt_preco')";
			$res = pg_query($con, $sql);
			if (strlen(trim(pg_last_error($con))) > 0) {
				$msg_erro_interno .= "Erro ao copiar dados pre�o venda. \n";				
				$msg_erro_interno .= pg_last_error($con);
			}
			$num_linha ++;
		}

	}else{
		$msg_erro_interno .= "Arquivo tabela_preco_item_venda.txt n�o encontrado. \n\n ";
	}

	if(file_exists("$arquivos/tabela_preco_item_compra.txt")){
		$conteudo_arquivo = file("$arquivos/tabela_preco_item_compra.txt");
		foreach ($conteudo_arquivo as $linha) {
			$valores = explode("\t",$linha);

			$sigla_tabela	= trim(limpa_string($valores[0]));
			$referencia		= trim(limpa_string($valores[1]));
			$txt_preco		= trim(limpa_string($valores[2]));

			$sql = "insert into atlas_preco (sigla_tabela, referencia, txt_preco) values ('$sigla_tabela', '$referencia', '$txt_preco')";
			$res = pg_query($con, $sql);
			if (strlen(trim(pg_last_error($con))) > 0) {
				$msg_erro_interno .= "Erro ao copiar dados pre�o compra. \n";				
				$msg_erro_interno .= pg_last_error($con);
			}
		}
	}else{
		$msg_erro_interno .= "Arquivo tabela_preco_item_compra.txt n�o encontrado. \n\n ";
	}


	if(file_exists("$arquivos/tabela_preco_item_venda.txt") or file_exists("$arquivos/tabela_preco_item_compra.txt")){
		$sql = "UPDATE atlas_preco SET 
			sigla_tabela= upper(replace (trim (sigla_tabela),'/','')) ,
			referencia  = trim (referencia) ,
			txt_preco   = replace (trim (txt_preco),',','.') ";
		$res = pg_query($con, $sql);	
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao atualizar sigla tabela, refer�ncia e pre�o \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "ALTER TABLE atlas_preco ADD COLUMN preco FLOAT";
		$res = pg_query($con, $sql);	
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao alterar coluna pre�o\n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "ALTER TABLE atlas_preco ADD COLUMN tabela INT4";
		$res = pg_query($con, $sql);	
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao alterar coluna tabela. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "ALTER TABLE atlas_preco ADD COLUMN peca INT4";
		$res = pg_query($con, $sql);	
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao alterar coluna pe�a. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "DELETE FROM atlas_preco WHERE length(trim(txt_preco)) = 0 ; 
				UPDATE atlas_preco SET preco = txt_preco::numeric";
		$res = pg_query($con, $sql);	
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao atualizar pre�o. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "UPDATE atlas_preco SET peca = tbl_peca.peca
				FROM   tbl_peca
				WHERE atlas_preco.referencia = tbl_peca.referencia
				AND tbl_peca.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao atualizar pe�a. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "UPDATE atlas_preco SET tabela = tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE atlas_preco.sigla_tabela = tbl_tabela.sigla_tabela
				AND   tbl_tabela.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao atualizar tabela. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		#---------------------- PE�AS NAO CADASTRADAS -------------
		$sql = "DROP TABLE atlas_preco_sem_peca";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao apagar tabela pre�o sem pe�a. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "SELECT * INTO TABLE atlas_preco_sem_peca FROM atlas_preco WHERE peca IS NULL";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao buscar pe�a sem pre�o. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "DELETE from atlas_preco
				WHERE peca is null ";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao apagar pre�o sem pe�a. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		#---------------------- TABELAS NAO CADASTRADAS -------------
		$sql = "DROP TABLE atlas_preco_sem_tabela";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao apagar pre�o sem tabela. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "SELECT * INTO TABLE atlas_preco_sem_tabela FROM atlas_preco WHERE tabela IS NULL";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao buscar pre�o sem tabela. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}


		#-------------------Incluindo tabelas nao cadastradas------------------
		$sql = "INSERT INTO tbl_tabela (sigla_tabela, fabrica, descricao ) select distinct upper(sigla_tabela), $fabrica, 'Tabela ' || upper(sigla_tabela) FROM atlas_preco_sem_tabela";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao inserir nova tabela. \n\n";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco ) 
				select (select tabela from tbl_tabela 
				where sigla_tabela = atlas_preco_sem_tabela.sigla_tabela 
				and fabrica = $fabrica), peca, preco 
				FROM atlas_preco_sem_tabela";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao inserir item na tabela (sigla tabela)";
			$msg_erro_interno .= pg_last_error($con);
		}

		#-------------------Termino da inclus de tabelas n cadastradas ------------------------

		$sql = "DELETE from atlas_preco
				WHERE tabela is null ";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao deletar pre�o sem tabela";
			$msg_erro_interno .= pg_last_error($con);
		}

		#-------------- Deleta Precos Duplicados --------------#
		$sql = "DROP TABLE atlas_preco_duplicado";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao apagar tabela de pre�o duplicado";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "SELECT * INTO TABLE atlas_preco_duplicado 
				FROM (SELECT sigla_tabela, referencia 
				FROM atlas_preco 
				GROUP BY sigla_tabela, referencia 
				HAVING COUNT (*) > 1 ) duplic ";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao buscar pre�o duplicado";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "DELETE FROM atlas_preco 
				USING atlas_preco_duplicado
				WHERE atlas_preco.sigla_tabela = atlas_preco_duplicado.sigla_tabela 
				AND atlas_preco.referencia     = atlas_preco_duplicado.referencia";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao apagar pre�o duplicado";
			$msg_erro_interno .= pg_last_error($con);	
		}

		#---------------------- ATUALIZANDO PRECOS -------------
		$sql = "UPDATE tbl_tabela_item 
					SET preco = atlas_preco.preco
				FROM atlas_preco
				WHERE tbl_tabela_item.tabela = atlas_preco.tabela
				AND   tbl_tabela_item.peca = atlas_preco.peca ";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao atualizar item da tabela";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "DELETE FROM atlas_preco 
				USING tbl_tabela_item
				WHERE tbl_tabela_item.tabela = atlas_preco.tabela 
				AND tbl_tabela_item.peca     = atlas_preco.peca ";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao apagar item da tabela";
			$msg_erro_interno .= pg_last_error($con);
		}

		$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) 
				(SELECT DISTINCT tabela, peca, preco FROM atlas_preco) ";
		$res = pg_query($con, $sql);
		if (strlen(trim(pg_last_error($con))) > 0) {
			$msg_erro_interno .= "Erro ao inserir item na tabela";
			$msg_erro_interno .= pg_last_error($con);
		}
		$data_sistema = date("Y-m-d");
	}
	
	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}
	
	if(!empty($msg_erro_interno)){
		##########################################################
		#               Gerando email de erro                    #
		##########################################################
		//system ("mv $origem/serie.txt $origem/serie_$data_sistema.txt");

		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
		$para_interno = "helpdesk@telecontrol.com.br";
	    
	    $assunto   = "Atlas - Erros na Importa��o de pre�os";
		$mensagem  = "Segue erros da importa��o de pre�os. \n ";
		$mensagem  .= "$msg_erro_interno \n";
		mail($para_interno, $assunto, $mensagem, $headers);	
	}

	if (!empty($log)) {
		##########################################################
		#               Gerando email de logs                    #
		##########################################################
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "jeffersons@atlas.ind.br, evandro.carlos@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "Atlas - Log na Importa��o de pre�os";
		$mensagem  = "Segue Log da importa��o de pre�os. \n ";
		$mensagem  .= "$log";
		mail($para, $assunto, $mensagem, $headers);	
	} 

$phpCron->termino();

if (file_exists("/home/atlas/atlas-telecontrol/tabela_preco_item_venda.txt")) {
	system("mv /home/atlas/atlas-telecontrol/tabela_preco_item_venda.txt  /tmp/atlas/tabela_preco_item_venda_$data.txt");
}

if (file_exists("/home/atlas/atlas-telecontrol/tabela_preco_item_compra.txt")) {
	system("mv /home/atlas/atlas-telecontrol/tabela_preco_item_compra.txt  /tmp/atlas/tabela_preco_item_compra_$data.txt");
}

?>
