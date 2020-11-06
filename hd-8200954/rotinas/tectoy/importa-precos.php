<?php
/**
 *
 * importa-precos.php
 *
 * @author  Ronald Santos
 * @version 2012-02-24
 *
 */

 try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$msg_erro = array();
	$log = array();
	
	$vet['fabrica'] = 'tectoy';
	$vet['tipo']    = 'preco';
	$vet['dest']    = 'helpdesk@telecontrol.com.br';
	$vet['log']     = 2;
	//psouza@tectoy.com.br; helpdesk@telecontrol.com.br
	$vet2 = $vet;
	$vet2['log'] = 1;
	$fabrica  = "6" ;
	$arquivos = "/tmp/tectoy";

	$sql = "SELECT to_char(current_date, 'MMYYYY');";
	$result = pg_query($con,$sql);
	$mes_ano = pg_result($result,0,0);

	$sql = "SELECT TO_CHAR(current_timestamp, 'MMYYYYHH24MISS');";
	$result = pg_query($con,$sql);
	$data_bkp = pg_result($result,0,0);
	
	# TABELA 07%
	if(file_exists("$arquivos/tab".$mes_ano."07.txt") OR file_exists("$arquivos/TAB".$mes_ano."07.TXT") OR file_exists("$arquivos/tab".$mes_ano."07.TXT") OR  file_exists("$arquivos/TAB".$mes_ano."07.txt")) {

		if( file_exists("$arquivos/tab".$mes_ano."07.txt")){
			$tabela_item  = "tab".$mes_ano."07.txt";
		}

		if(file_exists("$arquivos/TAB".$mes_ano."07.TXT")){
			$tabela_item  = "TAB".$mes_ano."07.TXT";
		}

		if(file_exists("$arquivos/tab".$mes_ano."07.TXT")){
			$tabela_item  = "tab".$mes_ano."07.TXT";
		}

		if(file_exists("$arquivos/TAB".$mes_ano."07.txt")){
			$tabela_item  = "TAB".$mes_ano."07.txt";
		}


		
		$precos = fopen("$arquivos/".$tabela_item,"r");

		$tabela = "163";

		while(!feof($precos)){

			$conteudo = explode("\n",fgets($precos));
			list($sigla_tabela,$referencia_peca,$preco) = explode("\t",$conteudo[0]);
			
			if ($referencia_peca) {
				$referencia_peca = str_replace("-","",$referencia_peca);
				$referencia_peca = str_replace("/","",$referencia_peca);
				$referencia_peca = str_replace(" ","",$referencia_peca);
			}else{
				$referencia_peca = "null";
			}
				
			if ($preco) {
				$preco = str_replace(",",".",$preco);
			}else{
				$preco = "null";
			}
				
			$peca = "";
				
			### VERIFICA EXISTÊNCIA DA PECA
			$sql = "SELECT tbl_peca.peca
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = '$referencia_peca'
					AND    tbl_peca.fabrica    = $fabrica";
			$result = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (!empty($msg_erro)) {
				throw new Exception($msg_erro);
			}

			if (pg_numrows($result) == 0) {
				$erro .= "Peca $referencia_peca nao cadastrada <br>";
				

			}else{
				$peca = pg_result($result,0,'peca');

				$sql = "SELECT tabela_item FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
				$result = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (!empty($msg_erro)) {
					throw new Exception($msg_erro);
				}

				if (pg_numrows($result) == 0) {
					$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ($tabela,$peca,round (($preco)::numeric,2) )";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}

				}else{
					$tabela_item = pg_result($result,0,'tabela_item');
					
					$sql = "UPDATE tbl_tabela_item SET
								preco = round (($preco)::numeric,2)
							WHERE tbl_tabela_item.tabela_item = $tabela_item ";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}else{
						$erro .= "Peca $referencia_peca com preco atualizado <br>";
					}
				}
			}
			$peca = "";
			
		}

		$sql = "UPDATE tbl_tabela SET
					data_inclusao = current_date
				WHERE tbl_tabela.tabela = $tabela
				AND   tbl_tabela.fabrica = $fabrica ";
		$result = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (!empty($msg_erro)) {
			throw new Exception($msg_erro);
		}

		if( file_exists("$arquivos/tab".$mes_ano."07.txt")){
			rename("$arquivos/tab".$mes_ano."07.txt",     "$arquivos/tab".$mes_ano."07_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/TAB".$mes_ano."07.TXT")){
			rename("$arquivos/TAB".$mes_ano."07.TXT",     "$arquivos/tab".$mes_ano."07_".$data_bkp.".old");
		}

		if( file_exists("$arquivos/tab".$mes_ano."07.TXT")){
			rename("$arquivos/tab".$mes_ano."07.TXT",     "$arquivos/tab".$mes_ano."07_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/tab".$mes_ano."07.TXT")){
			rename("$arquivos/tab".$mes_ano."07.TXT",     "$arquivos/tab".$mes_ano."07_".$data_bkp.".old");
		}
	}

	# TABELA 12%
	if(file_exists("$arquivos/tab".$mes_ano."12.txt") OR file_exists("$arquivos/TAB".$mes_ano."12.TXT") OR file_exists("$arquivos/tab".$mes_ano."12.TXT") OR file_exists("$arquivos/TAB".$mes_ano."12.txt")) {
	
		if( file_exists("$arquivos/tab".$mes_ano."12.txt")){
			$tabela_item  = "tab".$mes_ano."12.txt";
		}

		if(file_exists("$arquivos/TAB".$mes_ano."12.TXT")){
			$tabela_item  = "TAB".$mes_ano."12.TXT";
		}

		if( file_exists("$arquivos/tab".$mes_ano."12.TXT")){
			$tabela_item  = "tab".$mes_ano."12.TXT";
		}

		if(file_exists("$arquivos/TAB".$mes_ano."12.txt")){
			$tabela_item  = "TAB".$mes_ano."12.txt";
		}

		$precos = fopen("$arquivos/".$tabela_item,"r");

		$tabela = "168";

		while(!feof($precos)){

			$conteudo = explode("\n",fgets($precos));
			list($sigla_tabela,$referencia_peca,$preco) = explode("\t",$conteudo[0]);
			
			if ($referencia_peca) {
				$referencia_peca = str_replace("-","",$referencia_peca);
				$referencia_peca = str_replace("/","",$referencia_peca);
				$referencia_peca = str_replace(" ","",$referencia_peca);
			}else{
				$referencia_peca = "null";
			}
				
			if ($preco) {
				$preco = str_replace(",",".",$preco);
			}else{
				$preco = "null";
			}
				
			$peca = "";
				
			### VERIFICA EXISTÊNCIA DA PECA
			$sql = "SELECT tbl_peca.peca
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = '$referencia_peca'
					AND    tbl_peca.fabrica    = $fabrica";
			$result = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (!empty($msg_erro)) {
				throw new Exception($msg_erro);
			}

			if (pg_numrows($result) == 0) {
				$erro .= "Peca $referencia_peca nao cadastrada <br>";
			}else{
				$peca = pg_result($result,0,'peca');

				$sql = "SELECT tabela_item FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
				$result = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (!empty($msg_erro)) {
					throw new Exception($msg_erro);
				}

				if (pg_numrows($result) == 0) {
					$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ($tabela,$peca,round (($preco)::numeric,2) )";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}

				}else{
					$tabela_item = pg_result($result,0,'tabela_item');
					
					$sql = "UPDATE tbl_tabela_item SET
								preco = round (($preco)::numeric,2)
							WHERE tbl_tabela_item.tabela_item = $tabela_item ";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}else{
						$erro .= "Peca $referencia_peca com preco atualizado <br>";
					}
				}
			}
			$peca = "";
			
		}

		$sql = "UPDATE tbl_tabela SET
					data_inclusao = current_date
				WHERE tbl_tabela.tabela = $tabela
				AND   tbl_tabela.fabrica = $fabrica ";
		$result = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (!empty($msg_erro)) {
			throw new Exception($msg_erro);
		}

		if( file_exists("$arquivos/tab".$mes_ano."12.txt")){
			rename("$arquivos/tab".$mes_ano."12.txt",     "$arquivos/tab".$mes_ano."12_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/TAB".$mes_ano."12.TXT")){
			rename("$arquivos/TAB".$mes_ano."12.TXT",     "$arquivos/tab".$mes_ano."12_".$data_bkp.".old");
		}

		if( file_exists("$arquivos/tab".$mes_ano."12.TXT")){
			rename("$arquivos/tab".$mes_ano."12.TXT",     "$arquivos/tab".$mes_ano."12_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/tab".$mes_ano."12.TXT")){
			rename("$arquivos/tab".$mes_ano."12.TXT",     "$arquivos/tab".$mes_ano."12_".$data_bkp.".old");
		}
	}

	# TABELA 17%
	if(file_exists("$arquivos/tab".$mes_ano."17.txt") OR file_exists("$arquivos/TAB".$mes_ano."17.TXT") OR file_exists("$arquivos/tab".$mes_ano."17.TXT") OR file_exists("$arquivos/TAB".$mes_ano."17.txt")) {
	
		if( file_exists("$arquivos/tab".$mes_ano."17.txt")){
			$tabela_item  = "tab".$mes_ano."17.txt";
		}

		if(file_exists("$arquivos/TAB".$mes_ano."17.TXT")){
			$tabela_item  = "TAB".$mes_ano."17.TXT";
		}

		if( file_exists("$arquivos/tab".$mes_ano."17.TXT")){
			$tabela_item  = "tab".$mes_ano."17.TXT";
		}

		if(file_exists("$arquivos/TAB".$mes_ano."17.txt")){
			$tabela_item  = "TAB".$mes_ano."17.txt";
		}

		$precos = fopen("$arquivos/".$tabela_item,"r");

		$tabela = "169";

		while(!feof($precos)){

			$conteudo = explode("\n",fgets($precos));
			list($sigla_tabela,$referencia_peca,$preco) = explode("\t",$conteudo[0]);
			
			if ($referencia_peca) {
				$referencia_peca = str_replace("-","",$referencia_peca);
				$referencia_peca = str_replace("/","",$referencia_peca);
				$referencia_peca = str_replace(" ","",$referencia_peca);
			}else{
				$referencia_peca = "null";
			}
				
			if ($preco) {
				$preco = str_replace(",",".",$preco);
			}else{
				$preco = "null";
			}
				
			$peca = "";
				
			### VERIFICA EXISTÊNCIA DA PECA
			$sql = "SELECT tbl_peca.peca
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = '$referencia_peca'
					AND    tbl_peca.fabrica    = $fabrica";
			$result = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (!empty($msg_erro)) {
				throw new Exception($msg_erro);
			}

			if (pg_numrows($result) == 0) {
				$erro .= "Peca $referencia_peca nao cadastrada <br>";
			}else{
				$peca = pg_result($result,0,'peca');

				$sql = "SELECT tabela_item FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
				$result = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (!empty($msg_erro)) {
					throw new Exception($msg_erro);
				}

				if (pg_numrows($result) == 0) {
					$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ($tabela,$peca,round (($preco)::numeric,2) )";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}

				}else{
					$tabela_item = pg_result($result,0,'tabela_item');
					
					$sql = "UPDATE tbl_tabela_item SET
								preco = round (($preco)::numeric,2)
							WHERE tbl_tabela_item.tabela_item = $tabela_item ";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}else{
						$erro .= "Peca $referencia_peca com preco atualizado <br>";
					}
				}
			}
			$peca = "";
			
		}

		$sql = "UPDATE tbl_tabela SET
					data_inclusao = current_date
				WHERE tbl_tabela.tabela = $tabela
				AND   tbl_tabela.fabrica = $fabrica ";
		$result = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (!empty($msg_erro)) {
			throw new Exception($msg_erro);
		}

		if( file_exists("$arquivos/tab".$mes_ano."17.txt")){
			rename("mv $arquivos/tab".$mes_ano."17.txt", "$arquivos/tab".$mes_ano."17_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/TAB".$mes_ano."17.TXT")){
			rename("$arquivos/TAB".$mes_ano."17.TXT", "$arquivos/tab".$mes_ano."17_".$data_bkp.".old");
		}

		if( file_exists("$arquivos/TAB".$mes_ano."17.txt")){
			rename("$arquivos/TAB".$mes_ano."17.txt", "$arquivos/tab".$mes_ano."17_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/TAB".$mes_ano."17.txt")){
			rename("$arquivos/TAB".$mes_ano."17.txt", "$arquivos/tab".$mes_ano."17_".$data_bkp.".old");
		}
	}

	# TABELA 18%
	if(file_exists("$arquivos/tab".$mes_ano."18.txt") OR file_exists("$arquivos/TAB".$mes_ano."18.TXT") OR file_exists("$arquivos/tab".$mes_ano."18.TXT") OR file_exists("$arquivos/TAB".$mes_ano."18.txt")) {
	
		if( file_exists("$arquivos/tab".$mes_ano."18.txt")){
			$tabela_item  = "tab".$mes_ano."18.txt";
		}

		if(file_exists("$arquivos/TAB".$mes_ano."18.TXT")){
			$tabela_item  = "TAB".$mes_ano."18.TXT";
		}

		if( file_exists("$arquivos/TAB".$mes_ano."18.txt")){
			$tabela_item  = "TAB".$mes_ano."18.txt";
		}

		if(file_exists("$arquivos/tab".$mes_ano."18.TXT")){
			$tabela_item  = "tab".$mes_ano."18.TXT";
		}

		$precos = fopen("$arquivos/".$tabela_item,"r");

		$tabela = "170";

		while(!feof($precos)){

			$conteudo = explode("\n",fgets($precos));
			list($sigla_tabela,$referencia_peca,$preco) = explode("\t",$conteudo[0]);
			
			if ($referencia_peca) {
				$referencia_peca = str_replace("-","",$referencia_peca);
				$referencia_peca = str_replace("/","",$referencia_peca);
				$referencia_peca = str_replace(" ","",$referencia_peca);
			}else{
				$referencia_peca = "null";
			}
				
			if ($preco) {
				$preco = str_replace(",",".",$preco);
			}else{
				$preco = "null";
			}
				
			$peca = "";
				
			### VERIFICA EXISTÊNCIA DA PECA
			$sql = "SELECT tbl_peca.peca
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = '$referencia_peca'
					AND    tbl_peca.fabrica    = $fabrica";
			$result = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (!empty($msg_erro)) {
				throw new Exception($msg_erro);
			}

			if (pg_numrows($result) == 0) {
				$erro .= "Peca $referencia_peca nao cadastrada <br>";
			}else{
				$peca = pg_result($result,0,'peca');

				$sql = "SELECT tabela_item FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
				$result = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (!empty($msg_erro)) {
					throw new Exception($msg_erro);
				}

				if (pg_numrows($result) == 0) {
					$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ($tabela,$peca,round (($preco)::numeric,2) )";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}

				}else{
					$tabela_item = pg_result($result,0,'tabela_item');
					
					$sql = "UPDATE tbl_tabela_item SET
								preco = round (($preco)::numeric,2)
							WHERE tbl_tabela_item.tabela_item = $tabela_item ";
					$result = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (!empty($msg_erro)) {
						throw new Exception($msg_erro);
					}else{
						$erro .= "Peca $referencia_peca com preco atualizado <br>";
					}
				}
			}
			$peca = "";
			
		}

		$sql = "UPDATE tbl_tabela SET
					data_inclusao = current_date
				WHERE tbl_tabela.tabela = $tabela
				AND   tbl_tabela.fabrica = $fabrica ";
		$result = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (!empty($msg_erro)) {
			throw new Exception($msg_erro);
		}

		if( file_exists("$arquivos/tab".$mes_ano."18.txt")){
			rename("$arquivos/tab".$mes_ano."18.txt",     "$arquivos/tab".$mes_ano."12_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/TAB".$mes_ano."18.TXT")){
			rename("$arquivos/TAB".$mes_ano."18.TXT",     "$arquivos/tab".$mes_ano."18_".$data_bkp.".old");
		}

		if( file_exists("$arquivos/tab".$mes_ano."18.TXT")){
			rename("$arquivos/tab".$mes_ano."18.TXT",     "$arquivos/tab".$mes_ano."12_".$data_bkp.".old");
		}

		if(file_exists("$arquivos/TAB".$mes_ano."18.txt")){
			rename("$arquivos/TAB".$mes_ano."18.txt",     "$arquivos/tab".$mes_ano."18_".$data_bkp.".old");
		}
	}

	if (!empty($msg_erro)) {
		$msg_erro .= "<br><br>".$erro;
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($vet, APP, $msg);

	} else {

		Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));

	}

 }
 catch (Exception $e) {

	echo $e->getMessage();

 }
?>
