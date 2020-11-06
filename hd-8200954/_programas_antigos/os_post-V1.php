<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$enter = chr(13).chr(10);

$fabrica    = trim($_POST['fabrica']);
$cnpj       = trim($_POST['cnpj']);
$sua_os     = trim($_POST['sua_os']);
$sequencial = trim($_POST['sequencial']);
$consumidor_nome = trim($_POST['consumidor_nome']);

/*
if (strlen ($sua_os) < 6) {
	$sua_os = "000000" . trim ($sua_os);
	$sua_os = substr ($sua_os,strlen ($sua_os)-6,6);
}
*/
if (strlen ($sua_os) < 5) {
	$sua_os = "000000" . trim ($sua_os);
	$sua_os = substr ($sua_os,strlen ($sua_os)-5,5);
}
if (strlen (trim ($sequencial)) > 0) $sua_os = $sua_os . "-" . $sequencial ;


echo "\n<!-- INICIO DO PROCESSAMENTO DA OS # $sua_os -->\n";

if (strlen($fabrica) > 0 AND strlen($cnpj) > 0) {
	$sql = "SELECT tbl_posto_fabrica.posto , tbl_posto_fabrica.oid
			FROM   tbl_posto_fabrica
			JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE  tbl_posto.cnpj                   = '$cnpj'
			AND    tbl_posto_fabrica.fabrica        = '$fabrica'
			AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
	$res  = @pg_exec ($con,$sql);
	$erro = pg_errormessage($con);

	if (strlen($erro) > 0) {
		$msg_erro  = "Foi detectado o seguinte erro:$enter";
		$msg_erro .= "$erro$enter";
	}

	$dv = substr ($cnpj,1,1) * substr ($cnpj,6,1);
	if ($dv <> $_POST['dv']) {
		$msg_erro = "ERRO: Dígito verificador não confere. $enter ";
	}

	if (strlen($msg_erro) == 0) {
		if (pg_numrows($res) == 0) {
			$sql = "SELECT * FROM
						(
							SELECT trim(tbl_posto.nome) AS nome_posto
							FROM   tbl_posto
							WHERE  tbl_posto.cnpj ='$cnpj'
						) AS a,
						(
							SELECT upper(trim(tbl_fabrica.nome)) AS nome_fabrica
							FROM   tbl_fabrica
							WHERE  tbl_fabrica.fabrica = '$fabrica'
						) AS b;";
			$res  = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			if (strlen($msg_erro) == 0) {
				if (pg_numrows($res) > 0) {
					$nome_posto   = trim(pg_result($res,0,nome_posto));
					$nome_fabrica = trim(pg_result($res,0,nome_fabrica));
					
					$flag = "t";
					$msg_erro  = "Foi detectado o seguinte erro:$enter";
					$msg_erro .= "Posto $nome_posto não CREDENCIADO para o fabricante $nome_fabrica !!$enter";
				}else{
					$flag = "f";
					$msg_erro  = "Foi detectado o seguinte erro:$enter";
					$msg_erro .= "Posto informado não CREDENCIADO para este fabricante !!$enter";
				}
			}
		}else{
			$posto         = trim(pg_result($res,0,posto));
			$posto_fabrica = trim(pg_result($res,0,oid));
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$os_offline = trim($_POST['os_offline']);
		if (strlen($os_offline) == 0) {
			$aux_os_offline = "null";
		}else{
			$aux_os_offline = "'". $os_offline ."'";
		}

		$consumidor_revenda  = trim($_POST['consumidor_revenda']);
		$data_abertura       = trim($_POST['data_abertura']);
		$data_fechamento     = trim($_POST['data_fechamento']);
		$produto_referencia  = trim($_POST['produto_referencia']);
		$serie               = trim($_POST['serie']);
		
		if ($consumidor_revenda == 'R') {
			$cpf_cnpj            = "";
			$consumidor_nome     = "";
			$consumidor_fone     = "";
		}else{
			$cpf_cnpj            = trim($_POST['cpf_cnpj']);
			$consumidor_nome     = trim($_POST['consumidor_nome']);
			$consumidor_fone     = trim($_POST['consumidor_fone']);
		}
		
		$revenda_cnpj        = trim($_POST['revenda_cnpj']);
		$revenda_nome        = trim($_POST['revenda_nome']);
		$revenda_fone        = trim($_POST['revenda_fone']);
		$nota_fiscal         = trim($_POST['nota_fiscal']);
		$data_nf             = trim($_POST['data_nf']);
		$defeito_reclamado   = trim($_POST['defeito_reclamado']);
		$defeito_constatado  = trim($_POST['defeito_constatado']);
		$causa_defeito       = trim($_POST['causa_defeito']);
		$peca_referencia     = trim($_POST['peca_referencia']);
		$qtde                = trim($_POST['qtde']);
		$defeito             = trim($_POST['defeito']);
		$servico_realizado   = trim($_POST['servico_realizado']);
		$voltagem            = trim($_POST['voltagem']);
		$acessorios          = trim($_POST['acessorios']);
		$aparencia_produto   = trim($_POST['aparencia_produto']);
		$codigo_fabricacao   = trim($_POST['codigo_fabricacao']);
		$type                = trim($_POST['type']);
		$satisfacao          = trim($_POST['satisfacao']);
		$laudo               = trim($_POST['laudo']);

		

		$sql = "SELECT os FROM tbl_os WHERE fabrica = $fabrica AND posto = $posto AND sua_os = '$sua_os'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			#---------------- Postando Dados em OS_CADASTRO.PHP --------------
			$data_string  = "btn_acao=continuar&";
			$data_string .= "os_offline=1&";
			$data_string .= "sua_os=$sua_os&";
			$data_string .= "data_abertura=$data_abertura&";
			$data_string .= "produto_referencia=$produto_referencia&";
			$data_string .= "consumidor_nome=$consumidor_nome&";
			$data_string .= "consumidor_cpf=$cpf_cnpj&";
			$data_string .= "consumidor_fone=$consumidor_fone&";
			$data_string .= "revenda_cnpj=$revenda_cnpj&";
			$data_string .= "revenda_nome=$revenda_nome&";
			$data_string .= "revenda_fone=$revenda_fone&";
			$data_string .= "nota_fiscal=$nota_fiscal&";
			$data_string .= "data_nf=$data_nf&";
			$data_string .= "produto_serie=$serie&";
			$data_string .= "codigo_fabricacao=$codigo_fabricacao&";
			$data_string .= "aparencia_produto=$aparencia_produto&";
			$data_string .= "acessorios=$acessorios&";
			$data_string .= "defeito_reclamado_descricao=$defeito_reclamado_descricao&";
			$data_string .= "obs=$obs&";
			$data_string .= "quem_abriu_chamado=$quem_abriu_chamado&";
			$data_string .= "consumidor_revenda=$consumidor_revenda&";
			$data_string .= "produto_voltagem=$voltagem&";
			$data_string .= "type=$type&";
			$data_string .= "satisfacao=$satisfacao&";
			$data_string .= "laudo_tecnico=$laudo&";
			
			$referer  = $_SERVER["SCRIPT_URI"];
			$URL_Info = parse_url("http://www.telecontrol.com.br/assist/os_cadastro.php");
			$request  = "POST "  . $URL_Info["path"] . " HTTP/1.1\n";
			$request .= "Host: " . $URL_Info["host"] . "\n";
			$request .= "Referer: $referer\n";
			$request .= "Cookie: login_fabrica=$fabrica;login_posto=$posto;cook_posto=$posto;cook_fabrica=$fabrica;cook_posto_fabrica=$posto_fabrica \n";
			$request .= "Content-type: application/x-www-form-urlencoded\n";
			$request .= "Content-length: " . strlen ($data_string) . "\n";
			$request .= "Connection: close\n";
			$request .= "\n";
			$request .= $data_string . "\n";
			
			$post = fsockopen($URL_Info["host"],80);
			$retorno = "";
			fputs($post, $request);
			while(!feof($post)) {
				$retorno .= fgets($post, 128);
			}
			fclose($post);
			
			if (strpos ($retorno,'<!-- ERRO INICIO -->') > 0) {
				$retorno = substr ($retorno,strpos ($retorno,'<!-- ERRO INICIO -->') + 20);
				$retorno = substr ($retorno,0,strpos ($retorno,'<!-- ERRO FINAL -->'));
				$msg_erro = "ERRO ENCONTRADO: $retorno";
			}
			
	#		echo "<h1>Retorno do POST</h1>";
	#		echo $retorno;
	#		echo "<hr>";
		}else{
			$os_web = pg_result ($res,0,0);
		}
	}
}

$os_ja_lancada = false;
if (strlen ($msg_erro) > 0) {
	echo "<!--OFFLINE-I-->$msg_erro<!--OFFLINE-F-->";
}else{
	if (strlen ($os_web) > 0) {
		echo "<!--OFFLINE-I-->JA CADASTRADA OS WEB=$os_web<!--OFFLINE-F-->";
		$os_ja_lancada = true;
	}else{
		echo "<-- $retorno -->";
		$pos = strpos ($retorno,"Location: os_cadastro_adicional.php?os=");
		if ($pos > 0) {
			$retorno = substr ($retorno,$pos+39);
			$retorno = substr ($retorno,0,strpos($retorno,"Connection")-2);
			$os_web  = $retorno;
			echo "<!--OFFLINE-I-->OS WEB=$os_web<!--OFFLINE-F-->";
		}else{
			echo "<!--OFFLINE-I-->ERRO NO ENVIO DESTA OS<!--OFFLINE-F-->";
		}
	}
}


#------------ Atualiza campos permitidos na OS --------------
if ($os_ja_lancada AND strlen ($os_web) > 0) {

	$consumidor_nome    = trim($_POST['consumidor_nome']);
	$consumidor_revenda = trim($_POST['consumidor_revenda']);

	if ($consumidor_revenda == 'R') {
		$cpf_cnpj            = "";
		$consumidor_nome     = "";
		$consumidor_fone     = "";
	}else{
		$cpf_cnpj            = trim($_POST['cpf_cnpj']);
		$consumidor_nome     = trim($_POST['consumidor_nome']);
		$consumidor_fone     = trim($_POST['consumidor_fone']);
	}
	
	$revenda_cnpj        = trim($_POST['revenda_cnpj']);
	$revenda_nome        = trim($_POST['revenda_nome']);
	$revenda_fone        = trim($_POST['revenda_fone']);
	$nota_fiscal         = trim($_POST['nota_fiscal']);
	$acessorios          = trim($_POST['acessorios']);
	$aparencia_produto   = trim($_POST['aparencia_produto']);

	$nota_fiscal = "000000" . $nota_fiscal ;
	$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);

	$sql = "UPDATE tbl_os SET
			consumidor_nome = '$consumidor_nome' ,
			consumidor_fone = '$consumidor_fone' ,
			consumidor_cpf  = '$cpf_cnpj'        ,
			revenda_nome    = '$revenda_nome'    ,
			revenda_fone    = '$revenda_fone'    ,
			revenda_cnpj    = '$revenda_cnpj'    ,
			nota_fiscal     = '$nota_fiscal'     ,
			acessorios      = '$acessorios'      ,
			aparencia_produto = '$aparencia_produto'
			WHERE tbl_os.os = $os_web
			AND   tbl_os.fabrica = $fabrica
			AND   tbl_os.finalizada IS NULL";
	$res = @pg_exec ($con,$sql);
}


#---------------- Atualiza Defeito Reclamado ----------------
$defeito_reclamado = trim ($_POST['defeito_reclamado']);
if (strlen ($defeito_reclamado) > 0 AND strlen ($os_web) > 0 ) {
	$sql = "SELECT linha, familia FROM tbl_defeito_reclamado JOIN tbl_linha USING (linha) WHERE defeito_reclamado = $defeito_reclamado AND tbl_linha.fabrica = $fabrica ";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<!-- XX --><!-- Erro na atualizacao do Defeito Reclamado. Codigo nao existe ($defeito_reclamado) -->";
		exit;
	}


	$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado WHERE tbl_os.os = $os_web AND tbl_os.fabrica = $fabrica AND tbl_os.posto = $posto AND tbl_os.data_fechamento IS NULL";
	$res = @pg_exec ($con,$sql);
}


#---------------- Atualiza Defeito Constatado ----------------
$defeito_constatado = trim ($_POST['defeito_constatado']);
if (strlen ($defeito_constatado) > 0 AND strlen ($os_web) > 0 ) {
	$sql = "SELECT fabrica, linha, familia FROM tbl_defeito_constatado WHERE defeito_constatado = $defeito_constatado AND fabrica = $fabrica ";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		$sql = "SELECT fabrica, linha, familia, defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = $fabrica AND codigo = '$defeito_constatado'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!-- XX --><!-- Erro na atualizacao do Defeito Constatado ($defeito_constatado'). Codigo nao existe -->";
			exit;
		}
		$defeito_constatado = pg_result ($res,0,defeito_constatado);
	}


	$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado WHERE tbl_os.os = $os_web AND tbl_os.fabrica = $fabrica AND tbl_os.posto = $posto AND tbl_os.data_fechamento IS NULL";
	$res = @pg_exec ($con,$sql);
}


#---------------- Atualiza Causa do Defeito ----------------
$causa_defeito = trim ($_POST['causa_defeito']);
if (strlen ($causa_defeito) > 0 AND strlen ($os_web) > 0 ) {
	$sql = "SELECT causa_defeito FROM tbl_causa_defeito WHERE causa_defeito = $causa_defeito AND fabrica = $fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<!-- XX --><!-- Erro na atualizacao da Causa do Defeito ($causa_defeito). Codigo nao existe -->";
		exit;
	}

	$sql = "UPDATE tbl_os SET causa_defeito = $causa_defeito WHERE tbl_os.os = $os_web AND tbl_os.fabrica = $fabrica AND tbl_os.posto = $posto AND tbl_os.data_fechamento IS NULL";
	$res = @pg_exec ($con,$sql);
}

#---------------- Atualiza Solução ----------------
$solucao_os = trim ($_POST['solucao_os']);
if (strlen ($solucao_os) > 0 AND strlen ($os_web) > 0 ) {
	$sql = "SELECT * FROM tbl_servico_realizado WHERE servico_realizado = $solucao_os AND fabrica = $fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<!-- XX --><!-- Erro na atualizacao da Solução ($solucao_os). Codigo nao existe -->";
		exit;
	}

	$sql = "UPDATE tbl_os SET solucao_os = $solucao_os WHERE tbl_os.os = $os_web AND tbl_os.fabrica = $fabrica AND tbl_os.posto = $posto AND tbl_os.data_fechamento IS NULL";
	$res = @pg_exec ($con,$sql);
}

#---------------- Campos para Black&Decker ----------------
$type = trim ($_POST['type']);
if (strlen ($type) > 0 AND strlen ($os_web) > 0 ) {
	$sql = "UPDATE tbl_os SET type = '$type' WHERE os = $os_web AND posto = $posto";
	$res = @pg_exec ($con,$sql);
}


#-------------------------------------------------------------------
#---------------- Lançamento de ITENS deve ser AQUI ----------------
$peca_referencia = trim ($_POST['peca_referencia']);
echo "<!-- INICIO PECA -->";
if (strlen ($peca_referencia) > 0 AND strlen ($os_web) > 0 ) {
	$defeito           = trim ($_POST['defeito']);
	$qtde              = trim ($_POST['qtde']);
	$servico_realizado = trim ($_POST['servico_realizado']);

	$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $fabrica";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<!-- XX --><!-- Peça $peca_referencia não cadastrada -->";
		exit;
	}
	$peca = pg_result ($res,0,peca);

	#---------------- PRODUTO --------------------#
	$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_produto.referencia = '$produto_referencia' AND tbl_linha.fabrica = $fabrica";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<!-- XX --><!-- Produto não encontrado para OS_PRODUTO $produto_referencia -->";
		exit;
	}
	$produto = pg_result ($res,0,produto);

	#---------------- DE-PARA --------------------#
	$sql = "SELECT peca_para , para FROM tbl_depara WHERE peca_de = $peca";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$para = pg_result ($res,0,para);
		echo "<!-- AVISO --><!-- Peça $peca_referencia trocada para $para -->";
		$peca = pg_result ($res,0,peca_para);
	}

	#---------------- DE-PARA (2x) --------------------#
	$sql = "SELECT peca_para , para FROM tbl_depara WHERE peca_de = $peca";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$para = pg_result ($res,0,para);
		echo "<!-- AVISO --><!-- Peça $peca_referencia trocada para $para -->";
		$peca = pg_result ($res,0,peca_para);
	}

	#---------------- FORA DE LINHA --------------------#
	$sql = "SELECT * FROM tbl_peca_fora_linha WHERE peca = $peca";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
#		$para = pg_result ($res,0,para);
		echo "<!-- XX --><!-- Peça fora de linha -->";
		exit;
	}

	#---------------- DEFEITO DA PEÇA --------------------#
	$sql = "SELECT * FROM tbl_defeito WHERE defeito = $defeito AND fabrica = $fabrica";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
#		$para = pg_result ($res,0,para);
		echo "<!-- XX --><!-- Defeito ($defeito) da Peça inválido -->";
		exit;
	}

	#---------------- SERVIÇO REALIZADO --------------------#
	$sql = "SELECT * FROM tbl_servico_realizado WHERE servico_realizado = $servico_realizado AND fabrica = $fabrica";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
#		$para = pg_result ($res,0,para);
		echo "<!-- XX --><!-- Serviço Realizado ($servico_realizado) inválido -->";
		exit;
	}

	#-------------- Insere itens novos na OS -------------#
	$sql = "SELECT os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_item.peca = $peca AND tbl_os_produto.os = $os_web";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$os_item = pg_result ($res,0,0);
		echo "<!-- AVISO --><!-- Peça já lançada ($os_item) -->";
		exit;
	}

	#-------------- Insere itens novos na OS -------------#
	$sql = "BEGIN TRANSACTION";
	$res = @pg_exec ($con,$sql);
	
	$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os_web";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {

		if (strlen($serie) == 0) {
			$serie = 'null';
		}else{
			$serie = "'" . $serie . "'";
		}

		$sql = "INSERT INTO tbl_os_produto (os, produto, serie) VALUES ($os_web,$produto,$serie)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) > 0) {
			echo "<!-- XX --><!-- Erro ao inserir OS_PRODUTO ($msg_erro) -->";
			exit;
		}
		$sql = "SELECT currval ('seq_os_produto')";
		$res = pg_exec ($con,$sql);
		$os_produto = pg_result ($res,0,0);
	}else{
		$os_produto = pg_result ($res,0,0);
	}


	if (strlen ($qtde) == 0) $qtde = "1";

	$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, defeito, servico_realizado) VALUES ($os_produto, $peca, $qtde, $defeito, $servico_realizado)";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen ($msg_erro) > 0) {
		echo "<!-- XX --><!-- Erro ao inserir OS_ITEM ($msg_erro) -->";
		exit;
	}

	$sql = "SELECT currval ('seq_os_item')";
	$res = @pg_exec ($con,$sql);
	$os_item = pg_result ($res,0,0);

	$sql = "SELECT fn_valida_os_item($os_web, $fabrica)";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen ($msg_erro) > 0) {
		echo "<!-- XX --><!-- Erro ao inserir OS_ITEM ($msg_erro) -->";
		exit;
	}


	echo "<!-- OK --><!-- OK OS_ITEM=$os_item -->";
	$sql = "COMMIT TRANSACTION";
	$res = @pg_exec ($con,$sql);
	

	echo "<!-- FINAL PECA -->";
}




#---------------- Fechando OS ----------------
$data_fechamento = trim ($_POST['data_fechamento']);
if (strlen ($data_fechamento) > 0 AND strlen ($os_web) > 0 ) {
	$xdata_fechamento = substr ($data_fechamento,6,4) . "-" . substr ($data_fechamento,3,2) . "-" . substr ($data_fechamento,0,2);

	$sql = "SELECT os FROM tbl_os WHERE tbl_os.os = $os_web AND tbl_os.posto = $posto AND tbl_os.fabrica = $fabrica AND data_fechamento IS NOT NULL";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		echo "<!-- FECHADA ANTERIORMENTE ($os_web) -->";
	}else{
		echo "<!-- INICIO FECHAMENTO OS -->";

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$sql = "UPDATE tbl_os SET data_fechamento = '$xdata_fechamento'::date WHERE tbl_os.os = $os_web AND tbl_os.posto = $posto AND tbl_os.fabrica = $fabrica";
		$res       = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage ($con);

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_os($os_web, $fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
						
		if (strlen ($msg_erro) > 0) {
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "<!-- XX FECHAMENTO --><!-- ERRO NO FECHAMENTO DA OS -->";
			echo "<!-- $msg_erro -->";
		}else{
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			echo "<!-- OS FECHADA COM SUCESSO -->";
		}
		
		echo "<!-- FIM FECHAMENTO OS -->";
	}
}
if (strlen ($data_fechamento) == 0 AND strlen ($os_web) > 0 ) {
	echo "<!-- OS EM ABERTO -->";
}

echo "\n<!-- FINAL DO PROCESSAMENTO DA OS # $sua_os -->\n\n\n\n";

?>
