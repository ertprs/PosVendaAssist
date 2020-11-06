<?
//programa utilizado para upload de OSs do programa Offline e os_upload

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$enter = chr(13).chr(10);

$cnpj   = trim($_POST['cnpj']);
$versao = trim($_POST['versao']);

//PARA BRITANIA e TELECONTROL sua_os É IGUAL sua_os_offline PARA AS DEMAIS sua_os É GERADO AUTOMATICAMENTE
$fabrica        = trim($_POST['fabrica']);
$sua_os         = trim($_POST['sua_os']);         //(vem do os_upload)

$sua_os_offline = trim($_POST['sua_os_offline']); //(vem do offline)
if (($fabrica == 3) OR ($fabrica == 10) or ($fabrica == 11)) {
	if (strlen($sua_os) == 0) $sua_os = $sua_os_offline;
}else{
	$sua_os = "";
}


echo "\n<!-- INICIO DO PROCESSAMENTO DA OS # $sua_os_offline -->\n";

if (strlen($fabrica) > 0 AND strlen($cnpj) > 0) {
	$sql = "SELECT tbl_posto_fabrica.posto , tbl_posto_fabrica.oid
			FROM   tbl_posto_fabrica
			JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE  tbl_posto.cnpj                    = '$cnpj'
			AND    tbl_posto_fabrica.fabrica         = '$fabrica'
			AND    (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')";
	$res  = @pg_exec ($con,$sql);
	$erro = pg_errormessage($con);

	if (strlen($erro) > 0) {
		$msg_erro  = "Erro econtrado: $erro$enter";
	}

	if (strlen($msg_erro) == 0) {
		$dv = substr ($cnpj,1,1) * substr ($cnpj,6,1);
		if ($dv <> $_POST['dv']) {
			$msg_erro = "Erro encontrado: Dígito verificador do CNPJ não confere. $enter ";
		}
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
					$msg_erro  = "Erro econtrado: Posto $nome_posto não CREDENCIADO para o fabricante $nome_fabrica !!$enter";
				}else{
					$flag = "f";
					$msg_erro  = "Erro econtrado: Posto informado não CREDENCIADO para este fabricante !!$enter";
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
		
		//DADOS DO CONSUMIDOR
		$cpf_cnpj               = trim($_POST['cpf_cnpj']);
		$consumidor_nome        = trim($_POST['consumidor_nome']);
		$consumidor_fone        = trim($_POST['consumidor_fone']);
		#----------- Campos novos versão 3.0 ---------------#
		$consumidor_endereco    = trim($_POST['consumidor_endereco']);
		$consumidor_numero      = trim($_POST['consumidor_numero']);
		$consumidor_bairro      = trim($_POST['consumidor_bairro']);
		$consumidor_cidade      = trim($_POST['consumidor_cidade']);
		$consumidor_estado      = trim($_POST['consumidor_estado']);
		$consumidor_cep         = trim($_POST['consumidor_cep']);
		$consumidor_complemento = trim($_POST['consumidor_complemento']);
		
		//DADOS DA REVENDA
		$revenda_cnpj        = trim($_POST['revenda_cnpj']);
		$revenda_nome        = trim($_POST['revenda_nome']);
		$revenda_fone        = trim($_POST['revenda_fone']);
		#----------- Campos novos versão 3.0 ---------------#
		$revenda_endereco    = trim($_POST['revenda_endereco']);
		$revenda_numero      = trim($_POST['revenda_numero']);
		$revenda_complemento = trim($_POST['revenda_complemento']);
		$revenda_cep         = trim($_POST['revenda_cep']);
		$revenda_bairro      = trim($_POST['revenda_bairro']);
		$revenda_cidade      = trim($_POST['revenda_cidade']);
		$revenda_estado      = trim($_POST['revenda_estado']);

		//DADOS DA OS
		$nota_fiscal			= trim($_POST['nota_fiscal']);
		$data_nf				= trim($_POST['data_nf']);
		$defeito_reclamado		= trim($_POST['defeito_reclamado']);
		$defeito_constatado		= trim($_POST['defeito_constatado']);
		$causa_defeito			= trim($_POST['causa_defeito']);
		$peca_referencia		= trim($_POST['peca_referencia']);
		$qtde					= trim($_POST['qtde']);
		$defeito				= trim($_POST['defeito']);
		$servico_realizado		= trim($_POST['servico_realizado']);
		$voltagem				= trim($_POST['voltagem']);
		$acessorios				= trim($_POST['acessorios']);
		$aparencia_produto		= trim($_POST['aparencia_produto']);
		$codigo_fabricacao		= trim($_POST['codigo_fabricacao']);
		$type					= trim($_POST['type']);
		$satisfacao				= trim($_POST['satisfacao']);
		$laudo					= trim($_POST['laudo']);
		#----------- Campos novos versão 3.0 ---------------#
		$motivo_atraso			= trim($_POST['motivo_atraso']);
		$troca_faturada			= trim($_POST['troca_faturada']);
		$motivo_troca			= trim($_POST['motivo_troca']);
		$data_digitacao			= trim($_POST['data_digitacao']);
		$tipo_atendimento		= trim($_POST['tipo_atendimento']);
		$quantidade_produto		= trim($_POST['quantidade_produto']);
		$nome_tecnico			= trim($_POST['nome_tecnico']);
		$valores_adicionais		= trim($_POST['valores_adicionais']);
		$justificativa_va		= trim($_POST['justificativa_va']);
		$quilometragem			= trim($_POST['quilometragem']);
		$codigo_posto			= trim($_POST['codigo_posto']);
		$nf_saida				= trim($_POST['nf_saida']);
		$data_nf_saida			= trim($_POST['data_nf_saida']);
		$subproduto				= trim($_POST['subproduto']);
		$posicao				= trim($_POST['posicao']);


		//VERIFICA SE OS JA EXISTE POR os_web
		$os_web = trim($_POST['os_web']);
		if ( strlen($os_web)>0 ) {
			$sql = "SELECT os FROM tbl_os WHERE fabrica = $fabrica AND posto = $posto AND os = $os_web ";
			$res = pg_exec ($con,$sql);
		}else{
			$sql = "SELECT os FROM tbl_os WHERE fabrica = $fabrica AND posto = $posto AND os = -1 ";
			$res = pg_exec ($con,$sql);
		}

		//if ( strlen($os_web)==0 and strlen($sua_os_offline)>0 ) {
		if ( pg_numrows($res)==0 and strlen($sua_os_offline)>0 ) {
			$sql = "SELECT os FROM tbl_os WHERE fabrica = $fabrica AND posto = $posto AND sua_os_offline = '$sua_os_offline'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) $os_web = pg_result ($res,0,0);
		}

		if ( strlen($os_web)==0 and strlen($sua_os)>0 ) {
			$sql = "SELECT os FROM tbl_os WHERE fabrica = $fabrica AND posto = $posto AND ltrim(sua_os,'0') = ltrim('$sua_os','0')";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) $os_web = pg_result ($res,0,0);
		}

		//NOVA OS
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
			$data_string .= "obs=$obs&";
			$data_string .= "quem_abriu_chamado=$quem_abriu_chamado&";
			$data_string .= "consumidor_revenda=$consumidor_revenda&";
			$data_string .= "produto_voltagem=$voltagem&";
			$data_string .= "type=$type&";
			$data_string .= "satisfacao=$satisfacao&";
			$data_string .= "laudo_tecnico=$laudo&";
			#----------- Campos novos versão 3.0 ---------------#
			$data_string .= "consumidor_endereco=$consumidor_endereco&";
			$data_string .= "consumidor_numero=$consumidor_numero&";
			$data_string .= "consumidor_bairro=$consumidor_bairro&";
			$data_string .= "consumidor_cidade=$consumidor_cidade&";
			$data_string .= "consumidor_estado=$consumidor_estado&";
			$data_string .= "consumidor_cep=$consumidor_cep&";
			$data_string .= "consumidor_complemento=$consumidor_complemento&";
			$data_string .= "revenda_endereco=$revenda_endereco&";
			$data_string .= "revenda_numero=$revenda_numero&";
			$data_string .= "revenda_complemento=$revenda_complemento&";
			$data_string .= "revenda_cep=$revenda_cep&";
			$data_string .= "revenda_bairro=$revenda_bairro&";
			$data_string .= "revenda_cidade=$revenda_cidade&";
			$data_string .= "revenda_estado=$revenda_estado&";
			$data_string .= "sua_os_offline=$sua_os_offline&";
			$data_string .= "motivo_atraso=$motivo_atraso&";
			$data_string .= "troca_faturada=$troca_faturada&";
			$data_string .= "tipo_atendimento=$tipo_atendimento&";
			$data_string .= "motivo_troca=$motivo_troca&";
			$data_string .= "data_digitacao=$data_digitacao&";
			$data_string .= "quantidade_produto=$quantidade_produto&";
			$data_string .= "nome_tecnico=$nome_tecnico&";
			$data_string .= "valores_adicionais=$valores_adicionais&";
			$data_string .= "justificativa_va=$justificativa_va&";
			$data_string .= "quilometragem=$quilometragem&";
			$data_string .= "codigo_posto=$codigo_posto&";
			$data_string .= "nf_saida=$nf_saida&";
			$data_string .= "data_nf_saida=$data_nf_saida&";
			$referer  = $_SERVER["SCRIPT_URI"];
			$URL_Info = parse_url("http://posvenda.telecontrol.com.br/assist/os_cadastro.php");
	
			//PARA INTELBRAS DA O POSTO NA TELA os_cadastro_intelbras.php
			if ($fabrica == 14) {
				$URL_Info = parse_url("http://posvenda.telecontrol.com.br/assist/os_cadastro_intelbras.php");
			}
	
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
echo "<br>$post<br>";
			while(!feof($post)) {
				$retorno .= fgets($post, 128);
			}
			fclose($post);

echo $retorno."<br>";

			$os_web = substr ($retorno, strpos ($retorno,'Location: os_cadastro_adicional.php?os=')+39,7);

echo "<br>osweb teste->$os_web<br>";

			if (strpos ($retorno,'<!-- ERRO INICIO -->') > 0) {
				$retorno = substr ($retorno,strpos ($retorno,'<!-- ERRO INICIO -->') + 20);
				$retorno = substr ($retorno,0,strpos ($retorno,'<!-- ERRO FINAL -->'));
				$msg_erro = "Erro encontrado: $retorno";
				$os_web= "";
			}
		}else{
			$os_web = pg_result ($res,0,0);
		}
	}
}


if (strlen ($os_web) == 0) {
	$sql = "SELECT os FROM tbl_os WHERE fabrica = $fabrica AND posto = $posto AND sua_os_offline = '$sua_os_offline'";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) $os_web = pg_result ($res,0,0);
}

if (strlen ($os_web) == 0) $os_web = -1;


#----------------- VERIFICA SE OS ESTA FECHADA ANTES DE FAZER AS ALTERAÇÕS -----------------#
$sql = "SELECT os FROM tbl_os WHERE tbl_os.os = $os_web AND tbl_os.posto = $posto AND tbl_os.fabrica = $fabrica AND data_fechamento IS NOT NULL";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) == 0) {
	$os_ja_lancada = false;
	if (strlen ($msg_erro) > 0) {
		#echo "<!--OFFLINE-I-->$msg_erro<!--OFFLINE-F-->";
		echo "<!--OFFLINE-I--><ERRO-I>$msg_erro<ERRO-F><!--OFFLINE-F-->";
	}else{
		if (strlen ($os_web) > 0) {
			#echo "<!--OFFLINE-I-->JA CADASTRADA OS WEB=$os_web<!--OFFLINE-F-->";
			echo "<!--OFFLINE-I--><OK-I>$os_web<OK-F><!--OFFLINE-F-->";
			$os_ja_lancada = true;
		}else{
			echo "<-- $retorno -->";
			$pos = strpos ($retorno,"Location: os_cadastro_adicional.php?os=");
			if ($pos > 0) {
				$retorno = substr ($retorno,$pos+39);
				$retorno = substr ($retorno,0,strpos($retorno,"Connection")-2);
				$os_web  = $retorno;
				#echo "<!--OFFLINE-I-->OS WEB=$os_web<!--OFFLINE-F-->";
				echo "<!--OFFLINE-I--><OK-I>$os_web<OK-F><!--OFFLINE-F-->";
				$os_ja_lancada = true;
			}else{
				#echo "<!--OFFLINE-I-->ERRO NO ENVIO DESTA OS<!--OFFLINE-F-->";
				echo "<!--OFFLINE-I--><ERRO-I>ERRO NO ENVIO DESTA OS<ERRO-F><!--OFFLINE-F-->";
			}
		}
	}

	#------------ Atualiza campos permitidos na OS --------------#
	if ( ($os_ja_lancada) AND (strlen($os_web) > 0) ) {

		$consumidor_revenda = trim($_POST['consumidor_revenda']);

		$cpf_cnpj               = trim($_POST['cpf_cnpj']);
		$consumidor_nome        = trim($_POST['consumidor_nome']);
		$consumidor_fone        = trim($_POST['consumidor_fone']);
		#----------- Campos novos versão 3.0 ---------------#
		$consumidor_endereco    = trim($_POST['consumidor_endereco']);
		$consumidor_numero      = trim($_POST['consumidor_numero']);
		$consumidor_bairro      = trim($_POST['consumidor_bairro']);
		$consumidor_cidade      = trim($_POST['consumidor_cidade']);
		$consumidor_estado      = trim($_POST['consumidor_estado']);
		$consumidor_cep         = trim($_POST['consumidor_cep']);
		$consumidor_complemento = trim($_POST['consumidor_complemento']);
		
		$revenda_cnpj        = trim($_POST['revenda_cnpj']);
		$revenda_nome        = trim($_POST['revenda_nome']);
		$revenda_fone        = trim($_POST['revenda_fone']);
		#----------- Campos novos versão 3.0 ---------------#
		$revenda_endereco    = trim($_POST['revenda_endereco']);
		$revenda_numero      = trim($_POST['revenda_numero']);
		$revenda_complemento = trim($_POST['revenda_complemento']);
		$revenda_cep         = trim($_POST['revenda_cep']);
		$revenda_bairro      = trim($_POST['revenda_bairro']);
		$revenda_cidade      = trim($_POST['revenda_cidade']);
		$revenda_estado      = trim($_POST['revenda_estado']);

		$nota_fiscal         = trim($_POST['nota_fiscal']);

		$nota_fiscal = "000000" . $nota_fiscal ;
		$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);

		#----------- Campos novos versão 3.0 ---------------#
		$nome_tecnico = trim($_POST["nome_tecnico"]);
		if (strlen($nome_tecnico) > 0) $nome_tecnico = "'".$nome_tecnico."'";
		else                   $nome_tecnico = "null";

		$valores_adicionais = trim($_POST["valores_adicionais"]);
		$valores_adicionais = str_replace (",",".",$valores_adicionais);
		if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

		$justificativa_va = trim($_POST["justificativa_va"]);
		if (strlen($justificativa_va) > 0) $justificativa_va = "'".$justificativa_va."'";
		else                   $justificativa_va = "null";

		$quilometragem = trim($_POST["quilometragem"]);
		$quilometragem = str_replace (",",".",$quilometragem);
		if (strlen($quilometragem) == 0) $quilometragem = "0";

		$motivo_atraso       = trim($_POST['motivo_atraso']);
		$nf_saida            = trim($_POST['nf_saida']);

		if (strlen(trim($_POST['data_nf_saida'])) == 0) $data_nf_saida = 'null';
		else         $data_nf_saida = fnc_formata_data_pg (trim($_POST['data_nf_saida']));

		if (strlen(trim($_POST['serie'])) == 0) $serie = 'null';
		else         $serie = "'".strtoupper(trim($_POST['serie']))."'";

		if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $codigo_fabricacao = 'null';
		else             $codigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";
	
		if (strlen(trim($_POST['aparencia_produto'])) == 0) $aparencia_produto = 'null';
		else             $aparencia_produto = "'".trim($_POST['aparencia_produto'])."'";

		if (strlen(trim($_POST['acessorios'])) == 0) $acessorios = 'null';
		else             $acessorios = "'".trim($_POST['acessorios'])."'";

		if (strlen($_POST['satisfacao']) == 0) $satisfacao = "'f'";
		else             $satisfacao = "'".$_POST['satisfacao']."'";

		if (strlen ($_POST['laudo']) == 0) $laudo = 'null';
		else        $laudo = "'".trim($_POST['laudo'])."'";

		if (strlen ($_POST['quantidade_produto']) == 0) $quantidade_produto = "1";
		else        $quantidade_produto = trim($_POST['quantidade_produto']);

		$tipo_atendimento = $_POST['tipo_atendimento'];
		if (strlen (trim ($tipo_atendimento)) == 0) $tipo_atendimento = 'null';
		

		$sql = "UPDATE tbl_os SET
				consumidor_nome          = '$consumidor_nome'       ,
				consumidor_fone          = '$consumidor_fone'       ,
				consumidor_cpf           = '$cpf_cnpj'              ,
				revenda_nome             = '$revenda_nome'          ,
				revenda_fone             = '$revenda_fone'          ,
				revenda_cnpj             = '$revenda_cnpj'          ,
				nota_fiscal              = '$nota_fiscal'           ,
				acessorios               =  $acessorios             ,
				aparencia_produto        =  $aparencia_produto      ,
				consumidor_endereco      = '$consumidor_endereco'   ,
				consumidor_numero        = '$consumidor_numero'     ,
				consumidor_bairro        = '$consumidor_bairro'     ,
				consumidor_cidade        = '$consumidor_cidade'     ,
				consumidor_estado        = '$consumidor_estado'     ,
				consumidor_cep           = '$consumidor_cep'        ,
				consumidor_complemento   = '$consumidor_complemento',
				motivo_atraso            = '$motivo_atraso'         ,
				tipo_atendimento         =  $tipo_atendimento       ,
				qtde_produtos            =  $quantidade_produto     ,
				tecnico_nome             =  $nome_tecnico           ,
				valores_adicionais       =  $valores_adicionais     ,
				justificativa_adicionais =  $justificativa_va       ,
				qtde_km                  =  $quilometragem          ,
				nota_fiscal_saida        = '$nf_saida'              ,
				data_nf_saida            =  $data_nf_saida          ,
				serie                    =  $serie                  ,
				codigo_fabricacao        =  $codigo_fabricacao      ,
				satisfacao               =  $satisfacao             ,
				laudo_tecnico            =  $laudo
				WHERE tbl_os.os = $os_web
				AND   tbl_os.fabrica = $fabrica
				AND   tbl_os.finalizada IS NULL";
		$res = pg_exec ($con,$sql);
	}


	#---------------- Atualiza Tipo Atendimento ----------------
	$tipo_atendimento = trim($_POST['tipo_atendimento']);
	if (strlen ($tipo_atendimento) > 0 AND strlen ($os_web) > 0 ) {
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento AND fabrica = $fabrica ";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Erro na atualizacao do Tipo de Atendimento($tipo_atendimento). Codigo nao existe.<ERRO-F><!--OFFLINE-F-->";
			exit;
		}

		$sql = "UPDATE tbl_os SET tipo_atendimento = $tipo_atendimento WHERE tbl_os.os = $os_web AND tbl_os.fabrica = $fabrica AND tbl_os.posto = $posto AND tbl_os.data_fechamento IS NULL";
		$res = @pg_exec ($con,$sql);
	}


	#---------------- Atualiza Defeito Reclamado ----------------
	$defeito_reclamado = trim ($_POST['defeito_reclamado']);
	if ( (strlen($defeito_reclamado) > 0) AND (strlen($os_web) > 0) ) {
		$sql = "SELECT defeito_reclamado FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado AND tbl_linha.fabrica = $fabrica ";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Erro na atualizacao do Defeito Reclamado($defeito_reclamado). Codigo nao existe.<ERRO-F><!--OFFLINE-F-->";
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
				echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Erro na atualizacao do Defeito Constatado($defeito_constatado'). Codigo nao existe.<ERRO-F><!--OFFLINE-F-->";
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
		if (1==1) {
			//campo causa do defeito não é mais utilizado
		} else {
			$sql = "SELECT causa_defeito FROM tbl_causa_defeito WHERE causa_defeito = $causa_defeito AND fabrica = $fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {
				echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Erro na atualizacao da Causa do Defeito ($causa_defeito). Codigo nao existe.<ERRO-F><!--OFFLINE-F-->";
				exit;
			}

			$sql = "UPDATE tbl_os SET causa_defeito = $causa_defeito WHERE tbl_os.os = $os_web AND tbl_os.fabrica = $fabrica AND tbl_os.posto = $posto AND tbl_os.data_fechamento IS NULL";
			$res = @pg_exec ($con,$sql);
		}
	}

	#---------------- Atualiza Solução ----------------
	$solucao_os = trim ($_POST['solucao_os']);
	if (strlen ($solucao_os) > 0 AND strlen ($os_web) > 0 ) {
		$sql = "SELECT * FROM tbl_servico_realizado WHERE servico_realizado = $solucao_os AND fabrica = $fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Erro na atualizacao da Solução($solucao_os). Codigo nao existe.<ERRO-F><!--OFFLINE-F-->";
			exit;
		}

		$sql = "UPDATE tbl_os SET solucao_os = $solucao_os WHERE tbl_os.os = $os_web AND tbl_os.fabrica = $fabrica AND tbl_os.posto = $posto AND tbl_os.data_fechamento IS NULL";
		$res = pg_exec ($con,$sql);
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

		$posicao           = trim ($_POST['posicao']);
		if (strlen(trim($posicao)) == 0) $posicao = 'null';


		$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $fabrica";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Peça $peca_referencia não cadastrada<ERRO-F><!--OFFLINE-F-->";
			exit;
		}
		$peca = pg_result ($res,0,peca);

		#---------------- PRODUTO --------------------#
		$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_produto.referencia = '$produto_referencia' AND tbl_linha.fabrica = $fabrica";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Produto com a referência $produto_referencia não encontrado<ERRO-F><!--OFFLINE-F-->";
			exit;
		}
		$produto = pg_result ($res,0,produto);

		#---------------- DE-PARA -------------------------#
		$sql = "SELECT peca_para , para FROM tbl_depara WHERE peca_de = $peca";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$para = pg_result ($res,0,para);
			echo "<!--OFFLINE-I--><OK-I><AVISO-I>Peça $peca_referencia trocada para $para<AVISO-F><OK-F><!--OFFLINE-F-->";
			$peca = pg_result ($res,0,peca_para);
		}

		#---------------- DE-PARA (2x) --------------------#
		$sql = "SELECT peca_para , para FROM tbl_depara WHERE peca_de = $peca";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$para = pg_result ($res,0,para);
			echo "<!--OFFLINE-I--><OK-I><AVISO-I>Peça $peca_referencia trocada para $para<AVISO-F><OK-F><!--OFFLINE-F-->";
			$peca = pg_result ($res,0,peca_para);
		}

		#---------------- FORA DE LINHA --------------------#
		$sql = "SELECT * FROM tbl_peca_fora_linha WHERE peca = $peca";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Peça $peca_referencia está fora de linha<ERRO-F><!--OFFLINE-F-->";
			exit;
		}

		#---------------- DEFEITO DA PEÇA --------------------#
		$sql = "SELECT * FROM tbl_defeito WHERE defeito = $defeito AND fabrica = $fabrica";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Defeito ($defeito) da Peça $peca_referencia inválido<ERRO-F><!--OFFLINE-F-->";
			exit;
		}

		#---------------- SERVIÇO REALIZADO --------------------#
		$sql = "SELECT * FROM tbl_servico_realizado WHERE servico_realizado = $servico_realizado AND fabrica = $fabrica";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Serviço Realizado ($servico_realizado) da Peça $peca_referencia inválido<ERRO-F><!--OFFLINE-F-->";
			exit;
		}
		

		#-------------- Insere itens novos na OS -------------#
		$sql = "SELECT os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_item.peca = $peca AND tbl_os_produto.os = $os_web";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			$os_item = pg_result ($res,0,0);
			echo "<!--OFFLINE-I--><OK-I><AVISO-I>Peça já lançada ($os_item)<AVISO-F><OK-F><!--OFFLINE-F-->";
			exit;
		}

		#-------------- Insere itens novos na OS -------------#
		$sql = "BEGIN TRANSACTION";
		$res = @pg_exec ($con,$sql);
		
		
		
		//NOVO
		$sql = "SELECT  tbl_fabrica.os_item_subconjunto
						FROM    tbl_fabrica
				WHERE   tbl_fabrica.fabrica = $fabrica;";
		$resX = pg_exec ($con,$sql);

		if (pg_numrows($resX) > 0) {
			$os_item_subconjunto = pg_result ($resX,0,os_item_subconjunto);
			if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
		}
		if ($os_item_subconjunto == 'f') {
			$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os_web";
			$res = @pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {

				if (strlen($serie) == 0) {
					$serie = 'null';
				}

				$sql = "INSERT INTO tbl_os_produto (os, produto, serie) VALUES ($os_web,$produto,$serie)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (strlen ($msg_erro) > 0) {
					echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: 1-Erro ao inserir o produto na OS ($msg_erro)<ERRO-F><!--OFFLINE-F-->";
					$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
					exit;
				}
				$sql = "SELECT currval ('seq_os_produto')";
				$res = pg_exec ($con,$sql);
				$os_produto = pg_result ($res,0,0);
			}else{
				$os_produto = pg_result ($res,0,0);
			}
		} else {
			$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os_web AND produto = $subproduto";
			$res = @pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {

				if (strlen($serie) == 0) {
					$serie = 'null';
				}

				$sql = "INSERT INTO tbl_os_produto (os, produto, serie) VALUES ($os_web,$subproduto,$serie)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (strlen ($msg_erro) > 0) {
					echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: 2-Erro ao inserir o produto na OS ($msg_erro)<ERRO-F><!--OFFLINE-F-->";
					$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
					exit;
				}
				$sql = "SELECT currval ('seq_os_produto')";
				$res = pg_exec ($con,$sql);
				$os_produto = pg_result ($res,0,0);
			}else{
				$os_produto = pg_result ($res,0,0);
			}
		}
		//





		if (strlen ($qtde) == 0) $qtde = "1";

		$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, defeito, servico_realizado, posicao) VALUES ($os_produto, $peca, $qtde, $defeito, $servico_realizado, '$posicao')";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) > 0) {
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Erro ao incluir a peça $peca_referencia produto:$os_produto ($msg_erro) $sql<ERRO-F><!--OFFLINE-F-->";
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			exit;
		}

		$sql = "SELECT currval ('seq_os_item')";
		$res = @pg_exec ($con,$sql);
		$os_item = pg_result ($res,0,0);

		$sql = "SELECT fn_valida_os_item($os_web, $fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) > 0) {
			#echo "<!-- XX --><!-- Erro ao inserir OS_ITEM ($msg_erro) -->";
			echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Erro ao incluir a peça $peca_referencia ($msg_erro)<ERRO-F><!--OFFLINE-F-->";
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			exit;
		}

		echo "<!-- OK --><!-- OK OS_ITEM=$os_item -->";
		$sql = "COMMIT TRANSACTION";
		$res = @pg_exec ($con,$sql);
		

		echo "<!-- FINAL PECA -->";
	}
}else{
	$sql = "SELECT  os,
					TO_CHAR(data_fechamento, 'DD/MM/YYYY') AS fechamento
			FROM tbl_os 
			WHERE tbl_os.os = $os_web 
			AND tbl_os.posto = $posto 
			AND tbl_os.fabrica = $fabrica 
			AND data_fechamento IS NOT NULL";
	$res = pg_exec ($con,$sql);
	
	$data_fechamento = pg_result($res,0,fechamento);
	echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Esta OS já foi fechada anteriormente em $data_fechamento<ERRO-F><!--OFFLINE-F-->";
}


#---------------- Fechando OS ----------------
$data_fechamento = trim ($_POST['data_fechamento']);
if (strlen ($data_fechamento) > 0 AND strlen ($os_web) > 0 ) {
	$xdata_fechamento = substr ($data_fechamento,6,4) . "-" . substr ($data_fechamento,3,2) . "-" . substr ($data_fechamento,0,2);

	$sql = "SELECT  os,
					TO_CHAR(data_fechamento, 'DD/MM/YYYY') AS fechamento
			FROM tbl_os 
			WHERE tbl_os.os = $os_web 
			AND tbl_os.posto = $posto 
			AND tbl_os.fabrica = $fabrica 
			AND data_fechamento IS NOT NULL";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$data_fechamento = pg_result($res,0,fechamento);
		echo "<!--OFFLINE-I--><ERRO-I>Erro encontrado: Esta OS já foi fechada anteriormente em $data_fechamento.<ERRO-F><!--OFFLINE-F-->";
	}else{
		echo "<!-- INICIO FECHAMENTO OS -->";

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$sql = "UPDATE tbl_os SET data_fechamento = '$xdata_fechamento'::date WHERE tbl_os.os = $os_web AND tbl_os.posto = $posto AND tbl_os.fabrica = $fabrica";
		$res      = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage ($con);

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_os($os_web, $fabrica)";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
						
		if (strlen ($msg_erro) > 0) {
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");

			echo "<!--OFFLINE-I--><ERRO-I>$msg_erro<ERRO-F><!--OFFLINE-F-->";
		}else{
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			echo "<!--OFFLINE-I--><OK-I><FECHADA-I>$os_web<FECHADA-F><OK-F><!--OFFLINE-F-->";
		}
		
		echo "<!-- FIM FECHAMENTO OS -->";
	}
}
if (strlen ($data_fechamento) == 0 AND strlen ($os_web) > 0 ) {
	echo "<!-- OS EM ABERTO -->";
}

echo "\n<!-- FINAL DO PROCESSAMENTO DA OS # $sua_os -->\n\n\n\n";

?>