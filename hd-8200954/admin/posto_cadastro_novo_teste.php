<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "/var/www/telecontrol/www/mapa_rede/mlg_funciones.php";//  Para o mapa do Brasil
include '/var/www/assist/www/helpdesk.inc.php';// Funcoes de HelpDesk

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";
$msg_debug = "";

//exclusão da foto
if (strlen($_GET['posto']) > 0 and strlen($_GET['excluir_foto']) > 0 and strlen($_GET['foto']) > 0) {
	$posto    = trim($_GET['posto']);
	$excluir_foto = trim($_GET['excluir_foto']);
	$foto         = trim($_GET['foto']);
	$sql = "SELECT * FROM tbl_posto_fabrica_foto WHERE posto_fabrica_foto = $excluir_foto";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$aux_fabrica = pg_fetch_result($res, 0, fabrica);

		//valida a fabrica para o caso de ter sido alterado direto na barra de endereços
		if ($aux_fabrica == $login_fabrica) {
			if ($foto == 'foto_posto') {
				$caminho_foto  = pg_fetch_result($res, 0, 'foto_posto');
				$caminho_thumb = pg_fetch_result($res, 0, 'foto_posto_thumb');

				$sql = "UPDATE tbl_posto_fabrica_foto SET
							foto_posto           = NULL,
							foto_posto_thumb     = NULL,
							foto_posto_descricao = NULL
						WHERE posto_fabrica_foto = $excluir_foto";
				$res = pg_query($con, $sql);

				system("rm $caminho_foto");
				system("rm $caminho_thumb");
			}

			if ($foto == 'foto_contato1') {
				$caminho_foto  = pg_fetch_result($res, 0, foto_contato1);
				$caminho_thumb = pg_fetch_result($res, 0, foto_contato1_thumb);

				$sql = "UPDATE tbl_posto_fabrica_foto SET
							foto_contato1           = NULL,
							foto_contato1_thumb     = NULL,
							foto_contato1_descricao = NULL
						WHERE posto_fabrica_foto = $excluir_foto";
				$res = pg_query($con, $sql);

				system("rm $caminho_foto");
				system("rm $caminho_thumb");
			}

			if ($foto == 'foto_contato2') {
				$caminho_foto  = pg_fetch_result($res, 0, foto_contato2);
				$caminho_thumb = pg_fetch_result($res, 0, foto_contato2_thumb);

				$sql = "UPDATE tbl_posto_fabrica_foto SET
							foto_contato2           = NULL,
							foto_contato2_thumb     = NULL,
							foto_contato2_descricao = NULL
						WHERE posto_fabrica_foto = $excluir_foto";
				$res = pg_query($con, $sql);
				$msg_sucesso = 'Gravado com Sucesso!';
				system("rm $caminho_foto");
				system("rm $caminho_thumb");
			}
		}
	}
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

#-------------------- Descredenciar -----------------
if ($btn_acao == "descredenciar" and strlen($posto) > 0 ) {
	$sql = "DELETE FROM tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.posto   = $posto
			AND    tbl_posto_fabrica.fabrica = $login_fabrica;";
	$res = pg_query ($con,$sql);

	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}
}

if ($btn_acao == "gravar") {

	$cnpj  = trim($_POST['cnpj']);
	$xcnpj = str_replace (".","",$cnpj);
	$xcnpj = str_replace ("-","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);

	$nome  = trim($_POST ['nome']);

	$posto = trim($_POST ['posto']);

	if (strlen($posto) > 0){
		$sqlVcnpj = "SELECT cnpj
					FROM tbl_posto
					WHERE posto = $posto";
		$resVcnpj = pg_query ($con,$sqlVcnpj);

		if (pg_num_rows ($resVcnpj) > 0){
			if ($xcnpj <> trim((pg_fetch_result ($resVcnpj,0,0)))){
				if($login_fabrica <> 1) {
					$msg_erro = "A alteração de CNPJ só é possível mediante abertura de
				chamados para a Telecontrol";
				}
			}
		}
	}

	if (strlen($nome) == 0 AND strlen($xcnpj) > 0) {
		// verifica se posto está cadastrado
		$sql = "SELECT posto
				FROM   tbl_posto
				WHERE  cnpj = '$xcnpj'";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) > 0) {
			$posto = pg_fetch_result ($res,0,0);
			header ("Location: $PHP_SELF?posto=$posto");
			exit;
		}else{
			$posto    = '';
			$msg_erro = "Posto não cadastrado, favor completar os dados do cadastro.";
		}
	}

/*  HD 268395
	if(strlen($xcnpj) <> 14 and !in_array($login_fabrica, array(2,5,7,14,30,35,45,49,50,51,86))) {
		$msg_erro = "CNPJ inválido, digitar novamente.";
	}
*/
	if(strlen($xcnpj) <> 14 AND strlen($xcnpj) <> 11 and !in_array($login_fabrica, array(2,5,7,30,35,45,49,50,51,86))){
		//Cadence	07/04/2008 HD 17261	- A Cadence tem postos que são cadastrados pelo CPF
		//Dynacom	06/03/2008 HD 15279	- A Dynacom tem postos que são cadastrados pelo CPF
		//NKS		16/04/2008 HD 17853	- A NKS tem postos que são cadastrados pelo CPF
		//GAMA		23/07/2008 HD 27662	- A GAMA tem postos que são cadastrados pelo CPF
		//MOndial	16/03/2010 HD 208465- A Mondial tem postos que são cadastrados pelo CPF
		//FILIZOLA	11/08/2008 HD 27662	- A FILIZOLA tem postos que são cadastrados pelo CPF
		//ESMALTEC  14/05/2009 HD 106125- A Esmaltec tem postos que são cadastrados pelo CPF
		//FAMASTIL  28/05/2010 Fone		- A Famastil precisou cadastrar 2 postos com CPF (MLG)
		$msg_erro = "CNPJ inválido, digitar novamente.";
	}

	if($login_fabrica==2){//HD 34921 29/8/2008
		$validar = checa_cnpj($xcnpj);
		if ($validar==1){
			$msg_erro = "Por favor digite um CNPJ válido.";
		}
	}

	if(strlen($xcnpj) == 0){
		$msg_erro = "Digite o CNPJ/CPF do Posto.";
	}else{
		$cnpj = $xcnpj;
	}

	if (strlen($msg_erro) == 0){
		if (strlen($posto) == 0 AND strlen($nome) > 0 AND strlen($xcnpj) > 0) {
			// verifica se posto está cadastrado
			$sql = "SELECT posto
					FROM   tbl_posto
					WHERE  cnpj = '$xcnpj'";
			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) > 0){
				$posto = pg_fetch_result ($res,0,0);
				header ("Location: $PHP_SELF?posto=$posto");
				exit;
			}
		}
	}
	$codigo  = trim($_POST ['codigo']);
	if(strlen($codigo)==0){
		$msg_erro = "Digite o código do posto! Ele será utilizado para LOGIN do posto. Se você não tiver um, sugerimos o CNPJ como código.";
	}

	if (strlen($msg_erro) == 0){
		$ie                                      = trim($_POST ['ie']);
		$im                                      = trim($_POST ['im']);
		$endereco                                = trim($_POST ['endereco']);
		$numero                                  = trim($_POST ['numero']);
		$complemento                             = trim($_POST ['complemento']);
		$bairro                                  = trim($_POST ['bairro']);
		$cep                                     = trim($_POST ['cep']);
		$cidade                                  = trim($_POST ['cidade']);
		$estado                                  = trim($_POST ['estado']);
		$email                                   = trim($_POST ['email']);
		$fone                                    = trim($_POST ['fone']);
		$fax                                     = trim($_POST ['fax']);
		$contato                                 = trim($_POST ['contato']);
		$nome_fantasia                           = trim($_POST ['nome_fantasia']);
		$obs                                     = trim($_POST ['obs']);
		$capital_interior                        = trim($_POST ['capital_interior']);
		$posto_empresa                           = trim($_POST ['posto_empresa']);
		$tipo_posto                              = trim($_POST ['tipo_posto']);
		$escritorio_regional                     = trim($_POST ['escritorio_regional']);
		$codigo                                  = trim($_POST ['codigo']);
		$senha                                   = trim($_POST ['senha']);
		$desconto                                = trim($_POST ['desconto']);
		$valor_km                                = trim($_POST ['valor_km']);
		$desconto_acessorio                      = trim($_POST ['desconto_acessorio']);
		$custo_administrativo                    = trim($_POST ['custo_administrativo']);
		$imposto_al                              = trim($_POST ['imposto_al']);
		$suframa                                 = trim($_POST ['suframa']);
		$item_aparencia                          = trim($_POST ['item_aparencia']);
		$pedido_em_garantia_finalidades_diversas = trim($_POST ['pedido_em_garantia_finalidades_diversas']);
		$pais                                    = trim($_POST ['pais']);
		$garantia_antecipada                     = trim($_POST ['garantia_antecipada']);
		$imprime_os                              = trim($_POST ['imprime_os']);
		$qtde_os_item                            = trim($_POST ['qtde_os_item']);
		$escolhe_condicao                        = trim($_POST ['escolhe_condicao']); #HD 23738
		$condicao_liberada                       = trim($_POST ['condicao_liberada']); #HD 23738
		$atende_consumidor						 = trim($_POST ['atende_consumidor']);
// MLG	17/7/2009	HD 126810 -	Adicionado campo 'atende_consumidor'

		if(strlen($pais)==0) $msg_erro = "Selecione o país";

		$xie				= (strlen($ie) > 0)					? "'$ie'"					: 'null';
		$xim				= (strlen($im) > 0)					? "'$im'"					: 'null';
		$xnumero			= (strlen($numero) > 0)				? "'$numero'"				: 'null';
		$xcomplemento		= (strlen($complemento) > 0)		? "'$complemento'"			: 'null';
		$xbairro			= (strlen($bairro) > 0)				? "'$bairro'"				: 'null';
		$xcidade			= (strlen($cidade) > 0)				? "'$cidade'"				: 'null';
		$xestado			= (strlen($estado) > 0)				? "'$estado'"				: 'null';
		$xcontato			= (strlen($contato) > 0)			? "'$contato'"				: 'null';
		$xemail				= (strlen($email) > 0)				? "'$email'"				: 'null';
		$xfone				= (strlen($fone) > 0)				? "'$fone'"					: 'null';
		$xfax				= (strlen($fax) > 0)				? "'$fax'"					: 'null';
		$xcontato			= (strlen($contato) > 0)			? "'$contato'"				: 'null';
		$xnome_fantasia		= (strlen($nome_fantasia) > 0)		? "'$nome_fantasia'"		: 'null';
		$xcapital_interior	= (strlen($capital_interior) > 0)	? "'$capital_interior'"		: 'null';
		$xposto_empresa		= (strlen($posto_empresa) > 0)		? "'$posto_empresa'"		: 'null';
		$xtipo_posto		= (strlen($tipo_posto) > 0)			? "'$tipo_posto'"			: 'null';
		$xescritorio_regional=(strlen($escritorio_regional)> 0)	? "'$escritorio_regional'"	: 'null';
		$xcodigo			= (strlen($codigo) > 0)				? "'$codigo'"				: 'null';
		$xsuframa			= (strlen($suframa) > 0)			? "'f'"						: "'$suframa'";
		$zgarantia_antecipada=(strlen($garantia_antecipada)> 0)	? "'f'"						: "'".$garantia_antecipada."'";
		$xescolhe_condicao	= (strlen($escolhe_condicao) > 0)	? "'t'"						: "'f'";
		$xatende_consumidor	= (strlen($atende_consumidor) > 0)	? "'t'"						: "'f'";
		$xendereco			= (strlen($endereco) > 0)			? "'".$endereco."'"			: 'null';
		$xendereco			= (strlen($endereco) > 0)			? "'".$endereco."'"			: 'null';
		if (strlen($cep) > 0){
			$xcep = str_replace (".","",$cep);
			$xcep = str_replace ("-","",$xcep);
			$xcep = str_replace (" ","",$xcep);
			$xcep = "'".substr($xcep,0,8)."'";
		}else{
			$xcep = 'null';
		}

		if (strlen($pedido_em_garantia_finalidades_diversas) == 0)
			$xpedido_em_garantia_finalidades_diversas = "'f'";
		if($pedido_em_garantia_finalidades_diversas=='t')
			$xpedido_em_garantia_finalidades_diversas = "'$pedido_em_garantia_finalidades_diversas'";

		$sql="SELECT posto FROM tbl_posto where cnpj ='$xcnpj'";
		$res=pg_query($con,$sql);
		$msg_erro.=pg_errormessage($con);
		if(pg_num_rows($res) >0){
			$posto=pg_fetch_result($res,0,posto);
		}
		if (strlen($msg_erro) == 0) {
			$res = pg_query ($con,"BEGIN TRANSACTION");

			#----------------------------- Alteração de Dados ---------------------
			if (strlen ($posto) > 0) {

				# 49038 - Não permitir mais alterar CNPJ
				if($login_fabrica ==1 OR $login_fabrica ==81) { // HD 84547
					$add_posto=", nome			= '$nome'                   ,
								cnpj			= '$xcnpj'                  ,
								ie				= $xie                      ";
				}
				$sql = "UPDATE tbl_posto SET
							nome				= '$nome'                   ,
							/*cnpj				= '$xcnpj'                  ,*/
							ie					= $xie                      ,";
if($login_fabrica == 15){$sql .= "im			= $xim                      ,";}
				$sql  .= "  endereco			= $xendereco				,
							numero				= $xnumero                  ,
							complemento			= $xcomplemento             ,
							bairro				= $xbairro                  ,
							cep					= $xcep                     ,
							cidade				= $xcidade                  ,
							estado				= $xestado                  ,
							contato				= $xcontato                 ,
							email				= $xemail                   ,
							/* HD 52864 19/11/2008
							fone				= $xfone                    ,
							fax					= $xfax                     ,*/
							nome_fantasia		= $xnome_fantasia			,
							capital_interior	= upper($xcapital_interior)	,
							suframa				= $xsuframa                 ,
							pais				= '$pais'
						WHERE tbl_posto.posto			= $posto
						AND   tbl_posto.posto           = tbl_posto_fabrica.posto
						AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
				// HD 6623 Não alterar mais tbl_posto e só utilizar tbl_posto_fabrica
				// HD 12459
				// HD  64768 não deixar alterar nome e IE
				$sql = "UPDATE tbl_posto SET
							/*nome             = '$nome'                   ,
							cnpj             = '$xcnpj'                  ,
							ie               = $xie                      ,*/";
				if($login_fabrica == 15){
					$sql  .= "im             = $xim                      ,";
				}
				$sql  .= "
							/* HD 52864 19/11/2008
							fone             = $xfone                    ,
							fax              = $xfax                     ,*/
							capital_interior = upper($xcapital_interior),
							suframa          = $xsuframa                 ,
							pais             = '$pais'                   ,
							contato          = $xcontato
							$add_posto
						WHERE tbl_posto.posto    = $posto
						AND   tbl_posto.posto           = tbl_posto_fabrica.posto
						AND   tbl_posto_fabrica.fabrica = $login_fabrica ";

				$res = @pg_query($con,$sql);
				if (strlen(pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);

				if($login_fabrica==1){
					$sql = "UPDATE tbl_posto_condicao SET
									visivel = $xpedido_em_garantia_finalidades_diversas
							WHERE posto  = $posto
							AND condicao = 62";
					$res = @pg_query($con,$sql);
				}
			}else{
				#-------------- INSERT ---------------
				$sql = "INSERT INTO tbl_posto (
							nome            ,
							cnpj            ,
							ie              ,";
				if($login_fabrica == 15){
					$sql  .= "im            ,";
				}
				$sql .= "endereco           ,
							numero          ,
							complemento     ,
							bairro          ,
							cep             ,
							cidade          ,
							estado          ,
							contato         ,
							email           ,
							fone            ,
							fax             ,
							nome_fantasia   ,
							capital_interior,
							pais            ,
							suframa
						) VALUES (
							'$nome'                  ,
							'$xcnpj'                 ,
							$xie                     ,";
				if($login_fabrica == 15){
					$sql  .= "$xim            ,";
				}
				$sql  .= "$xendereco                 ,
							$xnumero                 ,
							$xcomplemento            ,
							$xbairro                 ,
							$xcep                    ,
							$xcidade                 ,
							$xestado                 ,
							$xcontato                ,
							$xemail                  ,
							$xfone                   ,
							$xfax                    ,
							$xnome_fantasia          ,
							upper($xcapital_interior),
							'$pais'                  ,
							$xsuframa
						)";
				$res = @pg_query($con,$sql);
				if (!is_resource($res)) {   // Se usar o pg_last_error/pg_errormessage não vai devolver o erro do CNPJ e sim o do 'current transaction aborted'
					$erro_cnpj = explode('.',pg_last_error($con));
					$msg_erro   = preg_replace('/ERROR: /','',$erro_cnpj[0]);
					unset($erro_cnpj);
				}

				if (strlen($msg_erro) == 0){
					$sql = "SELECT CURRVAL ('seq_posto')";
					$res = pg_query ($con,$sql);
					$posto = pg_fetch_result ($res,0,0);
					$msg_erro = pg_errormessage ($con);
				}
			}

			// grava posto_fabrica
			if (strlen($msg_erro) == 0 and strlen($posto) > 0){
				// HD 110541
				if($login_fabrica==11){
					$atendimento_lenoxx  = trim ($_POST['atendimento_lenoxx']);
				}
				$codigo_posto            = trim ($_POST['codigo']);
				$senha                   = trim ($_POST['senha']);
				$posto_empresa           = trim ($_POST['posto_empresa']);
				$tipo_posto              = trim ($_POST['tipo_posto']);
				$escritorio_regional     = trim ($_POST['escritorio_regional']);
				$obs                     = trim ($_POST['obs']);
				$transportadora          = trim ($_POST['transportadora']);
				$cobranca_endereco       = trim ($_POST['cobranca_endereco']);
				$cobranca_numero         = trim ($_POST['cobranca_numero']);
				$cobranca_complemento    = trim ($_POST['cobranca_complemento']);
				$cobranca_bairro         = trim ($_POST['cobranca_bairro']);
				$cobranca_cep            = trim ($_POST['cobranca_cep']);
				$cobranca_cidade         = trim ($_POST['cobranca_cidade']);
				$cobranca_estado         = trim ($_POST['cobranca_estado']);
				$desconto                = trim ($_POST['desconto']);
				$valor_km                = trim ($_POST['valor_km']);
				$desconto_acessorio      = trim ($_POST['desconto_acessorio']);
				$custo_administrativo    = trim ($_POST['custo_administrativo']);
				$imposto_al              = trim ($_POST['imposto_al']);
				$pedido_em_garantia      = trim($_POST ['pedido_em_garantia']);
				$coleta_peca             = trim($_POST ['coleta_peca']);
				$reembolso_peca_estoque  = trim($_POST ['reembolso_peca_estoque']);
				$pedido_faturado         = trim($_POST ['pedido_faturado']);
				$digita_os               = trim($_POST ['digita_os']);
				$prestacao_servico       = trim($_POST ['prestacao_servico']);
				$prestacao_servico_sem_mo= trim($_POST ['prestacao_servico_sem_mo']);
				$banco                   = trim($_POST ['banco']);
				$agencia                 = trim($_POST ['agencia']);
				$conta                   = trim($_POST ['conta']);
				$favorecido_conta        = trim($_POST ['favorecido_conta']);
				$conta_operacao          = trim($_POST ['conta_operacao']);//HD 8190 5/12/2007 Gustavo
				$cpf_conta               = trim($_POST ['cpf_conta']);
				$tipo_conta              = trim($_POST ['tipo_conta']);
				$obs_conta               = trim($_POST ['obs_conta']);
				$pedido_via_distribuidor = trim($_POST ['pedido_via_distribuidor']);
				$pais                    = trim($_POST ['pais']);
				$garantia_antecipada     = trim($_POST ['garantia_antecipada']);
				// HD 12104
				$imprime_os              = trim($_POST ['imprime_os']);
				// HD 17601
				$qtde_os_item            = trim($_POST ['qtde_os_item']);
				$escolhe_condicao        = trim($_POST ['escolhe_condicao']);
				// ! HD 121248 (augusto) - Atendente de callcenter para o posto
				$admin_sap				 = (int) $_POST['admin_sap'];
				$admin_sap               = (empty($admin_sap)) ? 'null' : $admin_sap ;
				// HD 126810
				$atende_consumidor		 = (strlen(trim($_POST ['atende_consumidor']))>0) ? "'t'" : "'f'";
				// hd 21496 - Francisco - campo Data da Nomeação para Dynacom
			if ($login_fabrica==2) { $data_nomeacao           = trim ($_POST['data_nomeacao']);
				$data_nomeacao = (strlen($data_nomeacao) >0) ? "$data_nomeacao" : "0001-01-01";
			}

			if(strlen($pais)==0) $msg_erro = "Selecione o país";

				if($login_fabrica==19){
					$atende_comgas = trim($_POST ['atende_comgas']);
					$atende_comgas = (strlen($atende_comgas)==0) ? "'f'" : "'t'";
				}
				$xcodigo_posto         = (strlen($codigo_posto) > 0) ? "'" . strtoupper ($codigo_posto) . "'" : 'null';
				$xsenha                = (strlen($senha) > 0) ? "'".$senha."'" : "'*'";
				$xdesconto             = (strlen($desconto) > 0) ? "'".$desconto."'" : 'null';
				$valor_km              = str_replace (",",".",$valor_km);
				$xvalor_km             = (strlen($valor_km) > 0) ? $valor_km : '0';
				$xdesconto_acessorio   = (strlen($desconto_acessorio) > 0) ? "'".$desconto_acessorio."'" : 'null';
				$xcusto_administrativo = (strlen($custo_administrativo) > 0) ? "'".$custo_administrativo."'" : 0;
				$ximposto_al           = (strlen($imposto_al) > 0) ? "'".$imposto_al."'" : 'null';
				$xposto_empresa        = (strlen($posto_empresa) > 0) ? "'".$posto_empresa."'" : 'null';
				$xtipo_posto           = (strlen($tipo_posto) > 0) ? "'".$tipo_posto."'" : 'null';
				$xescritorio_regional  = (strlen($escritorio_regional) > 0) ? "'".$escritorio_regional."'" : 'null';
				$xobs                  = (strlen($obs) > 0) ? "'".$obs."'" : 'null';
				$xtransportadora       = (strlen($transportadora) > 0) ? "'".$transportadora."'" : 'null';
				$xcobranca_endereco    = (strlen($cobranca_endereco) > 0) ? "'".$cobranca_endereco."'" : 'null';
				$xcobranca_numero      = (strlen($cobranca_numero) > 0) ? "'".$cobranca_numero."'" : 'null';
				$xcobranca_complemento = (strlen($cobranca_complemento) > 0) ? "'".$cobranca_complemento."'" : 'null';
				$xcobranca_bairro      = (strlen($cobranca_bairro) > 0) ? "'".$cobranca_bairro."'" : 'null';

				if (strlen($cobranca_cep) > 0){
					$xcobranca_cep = str_replace (".","",$cobranca_cep);
					$xcobranca_cep = str_replace ("-","",$xcobranca_cep);
					$xcobranca_cep = str_replace (" ","",$xcobranca_cep);
					$xcobranca_cep = "'".$xcobranca_cep."'";
				}else{
					$xcobranca_cep = 'null';
				}

				$xcobranca_cidade          = (strlen($cobranca_cidade) > 0) ? "'".$cobranca_cidade."'" : 'null';
				$xcobranca_estado          = (strlen($cobranca_estado) > 0) ? "'".$cobranca_estado."'" : 'null';
				$xobs                      = (strlen($obs) > 0) ? "'".$obs."'" : 'null';
				$xpedido_em_garantia       = (strlen($pedido_em_garantia) > 0) ? "'".$pedido_em_garantia."'" : "'f'";
				$xcoleta_peca              = (strlen($coleta_peca) > 0) ? "'".$coleta_peca."'" : "'f'";
				$xreembolso_peca_estoque   = (strlen($reembolso_peca_estoque) > 0) ? "'".$reembolso_peca_estoque."'" : "'f'";
				$xpedido_faturado          = (strlen($pedido_faturado) > 0) ? "'".$pedido_faturado."'" : "'f'";
				$xdigita_os                = (strlen($digita_os) > 0) ? "'".$digita_os."'" : "'f'";
				$xprestacao_servico        = (strlen($prestacao_servico) > 0) ? "'".$prestacao_servico."'" : "'f'";
				$xprestacao_servico_sem_mo = (!empty($prestacao_servico_sem_mo)) ? "'".$prestacao_servico_sem_mo."'": "'f'";
				$xgarantia_antecipada      = (strlen($garantia_antecipada) > 0) ? "'".$garantia_antecipada."'" : "'f'";

				if (strlen($banco) > 0) {
					$xbanco = "'".$banco."'";
					$sqlB = "SELECT nome FROM tbl_banco WHERE codigo = '$banco'";
					$resB = pg_query($con,$sqlB);
					if (pg_num_rows($resB) == 1) {
						$xnomebanco = "'" . trim(pg_fetch_result($resB,0,0)) . "'";
					}else{
						$xnomebanco = "null";
					}
				}else{
					$xbanco     = "null";
					$xnomebanco = "null";
				}

				$xagencia          = (strlen($agencia) > 0) ? "'".$agencia."'" : 'null';
				$xconta            = (strlen($conta) > 0) ? "'".$conta."'" : 'null';
				$xfavorecido_conta = (strlen($favorecido_conta) > 0) ? "'".$favorecido_conta."'" : 'null';
				$xconta_operacao   = (strlen($conta_operacao) > 0) ? "'".$conta_operacao."'" : 'null';
				$xtipo_conta       = (strlen($tipo_conta) > 0) ? "'".$tipo_conta."'" : 'null';

				$cpf_conta = str_replace (".","",$cpf_conta);
				$cpf_conta = str_replace ("-","",$cpf_conta);
				$cpf_conta = str_replace ("/","",$cpf_conta);
				$cpf_conta = str_replace (" ","",$cpf_conta);

				if (strlen($cpf_conta) <> 14 AND $tipo_conta == 'Conta jurídica'){
					$msg_erro = "CNPJ da Conta jurídica inválida";
				}

				$xcpf_conta               = (strlen($cpf_conta) > 0) ? "'".$cpf_conta."'" : 'null';
				$xobs_conta               = (strlen($obs_conta) > 0) ? "'".$obs_conta."'" : 'null';
				$xpedido_via_distribuidor = (strlen($pedido_via_distribuidor) > 0) ? "'".$pedido_via_distribuidor."'" : "'f'";

					// HD 17601
					if(strlen($qtde_os_item)==0){
						if($login_fabrica==45){
							$msg_erro="Por favor, preencher a quantidade de itens na OS que o posto pode lançar";
						}else{
							$qtde_os_item="0";
						}
					}

				if (strlen($msg_erro) == 0 AND strlen($posto) > 0) {
					$sql = "SELECT  tbl_posto_fabrica.*
							FROM    tbl_posto_fabrica
							WHERE   tbl_posto_fabrica.posto   = $posto
							AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
					$res = pg_query($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
				}

				//Lenoxx não pode repetir código do posto, pois nº da OS é gerado pelo codigo
				if ( strlen($msg_erro) == 0 AND strlen($posto) > 0 AND strlen($xcodigo_posto) > 0 AND ( $login_fabrica == 11 OR $login_fabrica == 3 ) ) {
					$sqlx = "SELECT  tbl_posto_fabrica.*
							FROM    tbl_posto_fabrica
							WHERE   tbl_posto_fabrica.posto       <> $posto
							AND     tbl_posto_fabrica.fabrica      = $login_fabrica
							AND     tbl_posto_fabrica.codigo_posto = $xcodigo_posto";
					$resx = pg_query($con,$sqlx);
					if (pg_num_rows($resx) > 0) $msg_erro = "Já existe um posto cadastrado com o código $xcodigo_posto";
				}

				if (strlen($msg_erro) == 0){
					$total_rows = pg_num_rows($res);
					//HD 15225
					if ($login_fabrica == 3) {
					    $xpedido_via_distribuidor = "'t'";
					    $xpedido_em_garantia      = "'f'";
					    $xreembolso_peca_estoque  = "'f'";
					}

					//HD 12104

					if($login_fabrica == 14){
						$imprime_os = (strlen($imprime_os) > 0) ? 't' : 'f';
					} else {
						$imprime_os='f';
					}

					if($login_fabrica==7 AND $xposto_empresa<>'null'){
						$sqlp = "SELECT posto
								FROM tbl_posto_fabrica
								WHERE codigo_posto = $xposto_empresa
								AND   fabrica      = $login_fabrica";
						$resp = @pg_query ($con,$sqlp);
						$msg_erro = pg_errormessage($con);
						if (pg_num_rows ($resp) > 0) {
							$xposto_empresa = pg_fetch_result($resp, 0, posto);
						}else{
							$msg_erro = "Código posto empresa não encontrado. ";
						}
					}

					if ($login_fabrica == 20 and strlen($posto) > 0) {
						// HD 31884
						$sql_t="SELECT  tbl_posto_fabrica.* ,
								nome                ,
								cnpj                ,
								ie                  ,
								capital_interior    ,
								suframa             ,
								pais                ,
								contato
							INTO  TEMP tmp_posto_$login_admin
							FROM  tbl_posto_fabrica
							JOIN  tbl_posto USING (posto)
							WHERE tbl_posto_fabrica.posto  = $posto
							AND   tbl_posto_fabrica.fabrica= $login_fabrica;

						CREATE INDEX tmp_posto_posto_$login_admin ON tmp_posto_$login_admin(posto); ";

						$res_t=pg_query($con,$sql_t);
					}
					if (pg_num_rows ($res) > 0) {
						// ! Atualizar POSTO FABRICA
						$sql = "UPDATE tbl_posto_fabrica SET
									codigo_posto            = $xcodigo_posto           ,
									senha                   = $xsenha                  ,
									posto_empresa           = $xposto_empresa          ,
									tipo_posto              = $xtipo_posto             ,
									obs                     = $xobs                    ,
									contato_endereco        = $xendereco               ,
									contato_numero          = $xnumero                 ,
									contato_complemento     = $xcomplemento            ,
									contato_bairro          = $xbairro                 ,
									contato_cidade          = $xcidade                 ,
									contato_cep             = $xcep                    ,
									contato_estado          = $xestado                 ,
									contato_fone_comercial  = $xfone                   ,
									contato_fax             = $xfax                    ,
									nome_fantasia           = $xnome_fantasia          ,
									contato_email           = $xemail                  ,
									transportadora          = $xtransportadora         ,
									cobranca_endereco       = $xcobranca_endereco      ,
									cobranca_numero         = $xcobranca_numero        ,
									cobranca_complemento    = $xcobranca_complemento   ,
									cobranca_bairro         = $xcobranca_bairro        ,
									cobranca_cep            = $xcobranca_cep           ,
									cobranca_cidade         = $xcobranca_cidade        ,
									cobranca_estado         = $xcobranca_estado        ,
									desconto                = $xdesconto               ,
									valor_km                = $xvalor_km               ,
									desconto_acessorio      = $xdesconto_acessorio     ,
									custo_administrativo    = $xcusto_administrativo   ,
									imposto_al              = $ximposto_al             ,
									pedido_em_garantia      = $xpedido_em_garantia     ,
									coleta_peca             = $xcoleta_peca            ,
									reembolso_peca_estoque  = $xreembolso_peca_estoque ,
									pedido_faturado         = $xpedido_faturado        ,
									digita_os               = $xdigita_os              ,
									prestacao_servico       = $xprestacao_servico      ,
									prestacao_servico_sem_mo=$xprestacao_servico_sem_mo,
									admin_sap				= $admin_sap			   ,
									banco                   = $xbanco                  ,
									agencia                 = $xagencia                ,
									conta                   = $xconta                  ,";
	if($login_fabrica==11){ $sql .= "atendimento            = '$atendimento_lenoxx'    , ";}//HD 110541
							$sql .= "nomebanco               = $xnomebanco              ,
									favorecido_conta        = $xfavorecido_conta       ,
									escritorio_regional     = $xescritorio_regional    ,";
	//HD 8190 5/12/2007 Gustavo
	if($login_fabrica==45){ $sql .= "conta_operacao         = $xconta_operacao         , ";}
							$sql .= "cpf_conta               = $xcpf_conta              ,
									tipo_conta              = $xtipo_conta             ,
									obs_conta               = $xobs_conta              , ";
	if($login_fabrica==19){ $sql .= " atende_comgas         = $atende_comgas           , ";}
							$sql .= " pedido_via_distribuidor = $xpedido_via_distribuidor,
									item_aparencia          = '$item_aparencia'        ,
									data_alteracao          = current_timestamp        ,
									admin                   = $login_admin             ,
									garantia_antecipada     = $xgarantia_antecipada    ,
									atende_consumidor		= $xatende_consumidor	   ,
									imprime_os              = '$imprime_os'            ,
									divulgar_consumidor     = '$divulgar_consumidor'   ,";
	// hd 21496 - Francisco - campo Data da Nomeação para Dynacom
    if($login_fabrica==2){  $sql .= "data_nomeacao			= '$data_nomeacao'		   ,";}
							$sql .= "qtde_os_item           = '$qtde_os_item'
								WHERE tbl_posto_fabrica.posto   = $posto
								AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
					}else{
						// ! Inserir POSTO FABRICA
						$sql = "INSERT INTO tbl_posto_fabrica (
									posto                  ,
									fabrica                ,
									codigo_posto           ,
									senha                  ,
									desconto               ,
									valor_km               ,
									desconto_acessorio     ,
									custo_administrativo   ,
									imposto_al             ,
									posto_empresa          ,
									tipo_posto             ,
									obs                    ,
									contato_endereco       ,
									contato_numero         ,
									contato_complemento    ,
									contato_bairro         ,
									contato_cidade         ,
									contato_cep            ,
									contato_estado         ,
									contato_fone_comercial ,
									contato_fax            ,
									nome_fantasia          ,
									contato_email          ,
									transportadora         ,
									cobranca_endereco      ,
									cobranca_numero        ,
									cobranca_complemento   ,
									cobranca_bairro        ,
									cobranca_cep           ,
									cobranca_cidade        ,
									cobranca_estado        ,
									pedido_em_garantia     ,
									reembolso_peca_estoque ,
									coleta_peca           ,
									pedido_faturado        ,
									digita_os              ,
									prestacao_servico      ,
									prestacao_servico_sem_mo,
									admin_sap				,
									banco                  ,
									agencia                ,
									conta                  ,
									nomebanco              ,
									favorecido_conta       ,
									atende_consumidor      ,";
	if($login_fabrica==45){ $sql .= "conta_operacao        ,";}//HD 8190 5/12/2007 Gustavo
							$sql .= "cpf_conta              ,
									tipo_conta             ,
									obs_conta              ,
									pedido_via_distribuidor,
									item_aparencia         , ";
	if($login_fabrica==19){ $sql .= " atende_comgas        ,  ";}
							$sql .= " data_alteracao       ,
									admin                  ,
									garantia_antecipada    ,
									escritorio_regional    ,";
	if($login_fabrica==11){ $sql .= "atendimento           ,";}// HD 110541
							$sql .= "imprime_os             ,";
	// hd 21496 - Francisco - campo Data da Nomeação para Dynacom
    if($login_fabrica==2) { $sql .= "data_nomeacao			,";}
						    $sql .=	"qtde_os_item
								) VALUES (
									$posto                   ,
									$login_fabrica           ,
									$xcodigo                 ,
									$xsenha                  ,
									$xdesconto               ,
									$xvalor_km               ,
									$xdesconto_acessorio     ,
									$xcusto_administrativo   ,
									$ximposto_al             ,
									$xposto_empresa          ,
									$xtipo_posto             ,
									$xobs                    ,
									$xendereco               ,
									$xnumero                 ,
									$xcomplemento            ,
									$xbairro                 ,
									$xcidade                 ,
									$xcep                    ,
									$xestado                 ,
									$xfone                   ,
									$xfax                    ,
									$xnome_fantasia          ,
									$xemail                  ,
									$xtransportadora         ,
									$xcobranca_endereco      ,
									$xcobranca_numero        ,
									$xcobranca_complemento   ,
									$xcobranca_bairro        ,
									$xcobranca_cep           ,
									$xcobranca_cidade        ,
									$xcobranca_estado        ,
									$xpedido_em_garantia     ,
									$xreembolso_peca_estoque ,
									$xcoleta_peca            ,
									$xpedido_faturado        ,
									$xdigita_os              ,
									$xprestacao_servico      ,
									$xprestacao_servico_sem_mo,
									$admin_sap				 ,
									$xbanco                  ,
									$xagencia                ,
									$xconta                  ,
									$xnomebanco              ,
									$xfavorecido_conta       ,
									$xatende_consumidor		 ,";
	if($login_fabrica==45){ $sql .="$xconta_operacao         ,";}//HD 8190 5/12/2007 Gustavo
							$sql .= "$xcpf_conta              ,
									$xtipo_conta             ,
									$xobs_conta              ,
									$xpedido_via_distribuidor,
									'$item_aparencia'        , ";
	if($login_fabrica==19){ $sql .= " $atende_comgas         , ";}
							$sql .= " current_timestamp      ,
									$login_admin             ,
									$xgarantia_antecipada    ,
									$xescritorio_regional    ,";
	if($login_fabrica==11){ $sql .= "'$atendimento_lenoxx'     ,";}// HD 110541
							$sql .= "'$imprime_os'            , ";
	// hd 21496 - Francisco - campo Data da Nomeação para Dynacom
    if($login_fabrica==2){  $sql .= "'$data_nomeacao'		 ,";}
						    $sql .= "'$qtde_os_item'
								)";
					}
					//echo nl2br($sql);
					$res = pg_query ($con,$sql);
				}
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
			}
			//HD 15526
			if(strlen($msg_erro) ==0 and strlen($posto) > 0) {
				$sql="UPDATE tbl_posto set
						endereco=substr($xendereco,1,50)
						where posto=$posto and endereco is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql="UPDATE tbl_posto set
						numero=substr($xnumero,1,10)
						where posto=$posto and numero is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql="UPDATE tbl_posto set
						complemento=substr($xcomplemento,1,20)
						where posto=$posto and complemento is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql="UPDATE tbl_posto set
						bairro=substr($xbairro,1,40)
						where posto=$posto and bairro is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql="UPDATE tbl_posto set
						cidade=substr($xcidade,1,30)
						where posto=$posto and cidade is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql="UPDATE tbl_posto set
						cep=$xcep
						where posto=$posto and cep is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql="UPDATE tbl_posto set
						estado=$xestado
						where posto=$posto and estado is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql="UPDATE tbl_posto set
						email=substr($xemail,1,50)
						where posto=$posto and email is null";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			// grava posto_linha
			if (strlen($msg_erro) == 0){
				if ($login_fabrica <> 14) {
					$sql = "SELECT * FROM tbl_linha WHERE ativo = 't' and fabrica = $login_fabrica";
					$res = pg_query ($con,$sql);

					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
						$linha = pg_fetch_result ($res,$i,linha);

						$atende       = $_POST ['atende_'       . $linha];
						$tabela       = $_POST ['tabela_'       . $linha];
						$desconto     = $_POST ['desconto_'     . $linha];
						$tabela_posto = $_POST ['tabela_posto_' . $linha];
						$distribuidor = $_POST ['distribuidor_' . $linha];

						/* conforme conversa com Samuel e solicitação de Airton retirar esta validação */
						/*
						if ($login_fabrica == 3 AND strlen ($atende) > 0
							AND strlen ($distribuidor) == 0
							AND ($estado == 'RS' )
							AND $linha <> 335) {
							if ($posto <> 1905) {
								echo "<h1>Posto deve ser atendido por distribuidor</h1>";
								$resX = pg_query ($con,"ROLLBACK TRANSACTION");
							}
						}
						*/

						$sql = "SELECT tbl_tabela.tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
						$resX = pg_query($con,$sql);
						if (pg_num_rows($resX) == 1) {
							$tabela = pg_fetch_result ($resX,0,tabela);
						}

						if (strlen ($atende) == 0) {
							$sql = "DELETE FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
							$resX = pg_query ($con,$sql);
						}else{
							if (strlen ($tabela) == 0) $msg_erro = "Informa a tabela para esta linha";
							if (strlen ($desconto) == 0) $desconto = "0";
							if (strlen ($distribuidor) == 0) $distribuidor = "null";
							if (strlen ($tabela_posto) == 0) $tabela_posto = "null";

							if (strlen ($msg_erro) == 0) {
								$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
								$resX = pg_query ($con,$sql);
								if (pg_num_rows ($resX) > 0) {
									$sql = "UPDATE tbl_posto_linha SET
												tabela       = $tabela  ,
												desconto     = $desconto,
												tabela_posto = $tabela_posto,
												distribuidor = $distribuidor
											WHERE tbl_posto_linha.posto = $posto
											AND   tbl_posto_linha.linha = $linha";
									$resX = pg_query ($con,$sql);
								}else{
									$sql = "INSERT INTO tbl_posto_linha (
												posto   ,
												linha   ,
												tabela  ,
												desconto,
												distribuidor,
												tabela_posto
											) VALUES (
												$posto   ,
												$linha   ,
												$tabela  ,
												$desconto,
												$distribuidor,
												$tabela_posto
											)";
									$resX = pg_query ($con,$sql);
								}
							}
						}
					}
				}else{
					$sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY tbl_familia.descricao;";
					$res = pg_query ($con,$sql);

					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
						$familia = pg_fetch_result ($res,$i,familia);

						$atende       = $_POST ['atende_'       . $familia];
						$tabela       = $_POST ['tabela_'       . $familia];
						$desconto     = $_POST ['desconto_'     . $familia];
						$distribuidor = $_POST ['distribuidor_' . $familia];

						if (strlen ($atende) == 0) {
							$sql = "DELETE FROM tbl_posto_linha
									WHERE  tbl_posto_linha.posto   = $posto
									AND    tbl_posto_linha.familia = $familia";
							$resX = pg_query ($con,$sql);
						}else{
							if (strlen ($tabela) == 0)       $msg_erro = "Informa a tabela para esta familia";
							if (strlen ($desconto) == 0)     $desconto = "0";
							if (strlen ($distribuidor) == 0) $distribuidor = "null";

							if (strlen ($msg_erro) == 0) {
								if($login_fabrica == 20){
									$sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
											WHERE  tbl_tabela.fabrica = $login_fabrica
											AND    tbl_tabela.tabela  = $tabela
											AND    tbl_tabela.ativa IS TRUE";
									$resX = pg_query($con,$sql);

									if (pg_num_rows($resX) == 1) {
										$tabela = pg_fetch_result ($resX,0,tabela);
									}

									$sql = "UPDATE tbl_posto_fabrica SET
												tabela       = $tabela
											WHERE posto   = $posto
											AND   familia = $familia";
									$resX = pg_query ($con,$sql);

								}
								$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
								$resX = pg_query ($con,$sql);

								if (pg_num_rows ($resX) > 0) {
									$sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
											WHERE  tbl_tabela.fabrica = $login_fabrica
											AND    tbl_tabela.tabela  = $tabela
											AND    tbl_tabela.ativa IS TRUE";
									$resX = pg_query($con,$sql);

									if (pg_num_rows($resX) == 1) {
										$tabela = pg_fetch_result ($resX,0,tabela);
									}

									$sql = "UPDATE tbl_posto_linha SET
												tabela       = $tabela  ,
												desconto     = $desconto,
												distribuidor = $distribuidor
											WHERE tbl_posto_linha.posto   = $posto
											AND   tbl_posto_linha.familia = $familia";
									$resX = pg_query ($con,$sql);

								}else{
									$sql = "INSERT INTO tbl_posto_linha (
												posto   ,
												familia ,
												tabela  ,
												desconto,
												distribuidor
											) VALUES (
												$posto   ,
												$familia ,
												$tabela  ,
												$desconto,
												$distribuidor
											)";
									$resX = pg_query ($con,$sql);
								}
							}
						}
					}
				}
			}
		}

		if($login_fabrica == 20 AND strlen($msg_erro)==0){
			$tabela = $_POST["tabela_unica"];
			if(strlen($tabela) > 0){
				$sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
						WHERE  tbl_tabela.fabrica = $login_fabrica
						AND    tbl_tabela.tabela  = $tabela
						AND    tbl_tabela.ativa IS TRUE";
				$resX = pg_query($con,$sql);
				if (pg_num_rows($resX) == 1) $tabela = pg_fetch_result ($resX,0,tabela);

				$sql = "UPDATE tbl_posto_fabrica SET
							tabela     = $tabela
						WHERE posto    = $posto
						AND   fabrica  = $login_fabrica";
				$resX = pg_query ($con,$sql);
			}

			$sql= " SELECT	CASE WHEN tbl_posto_fabrica.codigo_posto             <> tmp_posto_$login_admin.codigo_posto              THEN tbl_posto_fabrica.codigo_posto            ELSE null END AS codigo_posto_alterado               ,
							CASE WHEN tbl_posto_fabrica.credenciamento           <> tmp_posto_$login_admin.credenciamento            THEN tbl_posto_fabrica.credenciamento          ELSE null END AS credenciamento_alterado             ,
							CASE WHEN tbl_posto_fabrica.senha                    <> tmp_posto_$login_admin.senha                     THEN tbl_posto_fabrica.senha                   ELSE null END AS senha_alterado                      ,
							CASE WHEN tbl_posto_fabrica.desconto                 <> tmp_posto_$login_admin.desconto                  THEN tbl_posto_fabrica.desconto                ELSE null END AS desconto_alterado                   ,
							CASE WHEN tbl_posto_fabrica.desconto_acessorio       <> tmp_posto_$login_admin.desconto_acessorio        THEN tbl_posto_fabrica.desconto_acessorio      ELSE null END AS desconto_acessorio_alterado         ,
							CASE WHEN tbl_posto_fabrica.custo_administrativo     <> tmp_posto_$login_admin.custo_administrativo      THEN tbl_posto_fabrica.custo_administrativo    ELSE null END AS custo_administrativo_alterado       ,
							CASE WHEN tbl_posto_fabrica.imposto_al               <> tmp_posto_$login_admin.imposto_al                THEN tbl_posto_fabrica.imposto_al              ELSE null END AS imposto_al_alterado                 ,
							CASE WHEN tbl_posto_fabrica.tipo_posto               <> tmp_posto_$login_admin.tipo_posto                THEN tbl_posto_fabrica.tipo_posto              ELSE null END AS tipo_posto_alterado                 ,
							CASE WHEN tbl_posto_fabrica.obs                      <> tmp_posto_$login_admin.obs                       THEN tbl_posto_fabrica.obs                     ELSE null END AS obs_alterado                        ,
							CASE WHEN tbl_posto_fabrica.contato_endereco         <> tmp_posto_$login_admin.contato_endereco          THEN tbl_posto_fabrica.contato_endereco        ELSE null END AS contato_endereco_alterado           ,
							CASE WHEN tbl_posto_fabrica.contato_numero           <> tmp_posto_$login_admin.contato_numero            THEN tbl_posto_fabrica.contato_numero          ELSE null END AS contato_numero_alterado             ,
							CASE WHEN tbl_posto_fabrica.contato_complemento      <> tmp_posto_$login_admin.contato_complemento       THEN tbl_posto_fabrica.contato_complemento     ELSE null END AS contato_complemento_alterado        ,
							CASE WHEN tbl_posto_fabrica.contato_bairro           <> tmp_posto_$login_admin.contato_bairro            THEN tbl_posto_fabrica.contato_bairro          ELSE null END AS contato_bairro_alterado             ,
							CASE WHEN tbl_posto_fabrica.contato_cidade           <> tmp_posto_$login_admin.contato_cidade            THEN tbl_posto_fabrica.contato_cidade          ELSE null END AS contato_cidade_alterado             ,
							CASE WHEN tbl_posto_fabrica.contato_cep              <> tmp_posto_$login_admin.contato_cep               THEN tbl_posto_fabrica.contato_cep             ELSE null END AS contato_cep_alterado                ,
							CASE WHEN tbl_posto_fabrica.contato_estado           <> tmp_posto_$login_admin.contato_estado            THEN tbl_posto_fabrica.contato_estado          ELSE null END AS contato_estado_alterado             ,
							CASE WHEN tbl_posto_fabrica.contato_fone_comercial   <> tmp_posto_$login_admin.contato_fone_comercial    THEN tbl_posto_fabrica.contato_fone_comercial  ELSE null END AS contato_fone_comercial_alterado     ,
							CASE WHEN tbl_posto_fabrica.contato_fax              <> tmp_posto_$login_admin.contato_fax               THEN tbl_posto_fabrica.contato_fax             ELSE null END AS contato_fax_alterado                ,
							CASE WHEN tbl_posto_fabrica.nome_fantasia            <> tmp_posto_$login_admin.nome_fantasia             THEN tbl_posto_fabrica.nome_fantasia           ELSE null END AS nome_fantasia_alterado              ,
							CASE WHEN tbl_posto_fabrica.contato_email            <> tmp_posto_$login_admin.contato_email             THEN tbl_posto_fabrica.contato_email           ELSE null END AS contato_email_alterado              ,
							CASE WHEN tbl_posto_fabrica.transportadora           <> tmp_posto_$login_admin.transportadora            THEN tbl_posto_fabrica.transportadora          ELSE null END AS transportadora_alterado             ,
							CASE WHEN tbl_posto_fabrica.cobranca_endereco        <> tmp_posto_$login_admin.cobranca_endereco         THEN tbl_posto_fabrica.cobranca_endereco       ELSE null END AS cobranca_endereco_alterado          ,
							CASE WHEN tbl_posto_fabrica.cobranca_numero          <> tmp_posto_$login_admin.cobranca_numero           THEN tbl_posto_fabrica.cobranca_numero         ELSE null END AS cobranca_numero_alterado            ,
							CASE WHEN tbl_posto_fabrica.cobranca_complemento     <> tmp_posto_$login_admin.cobranca_complemento      THEN tbl_posto_fabrica.cobranca_complemento    ELSE null END AS cobranca_complemento_alterado       ,
							CASE WHEN tbl_posto_fabrica.cobranca_bairro          <> tmp_posto_$login_admin.cobranca_bairro           THEN tbl_posto_fabrica.cobranca_bairro         ELSE null END AS cobranca_bairro_alterado            ,
							CASE WHEN tbl_posto_fabrica.cobranca_cep             <> tmp_posto_$login_admin.cobranca_cep              THEN tbl_posto_fabrica.cobranca_cep            ELSE null END AS cobranca_cep_alterado               ,
							CASE WHEN tbl_posto_fabrica.cobranca_cidade          <> tmp_posto_$login_admin.cobranca_cidade           THEN tbl_posto_fabrica.cobranca_cidade         ELSE null END AS cobranca_cidade_alterado            ,
							CASE WHEN tbl_posto_fabrica.cobranca_estado          <> tmp_posto_$login_admin.cobranca_estado           THEN tbl_posto_fabrica.cobranca_estado         ELSE null END AS cobranca_estado_alterado            ,
							CASE WHEN tbl_posto_fabrica.pedido_em_garantia       <> tmp_posto_$login_admin.pedido_em_garantia        THEN tbl_posto_fabrica.pedido_em_garantia      ELSE null END AS pedido_em_garantia_alterado         ,
							CASE WHEN tbl_posto_fabrica.pedido_faturado          <> tmp_posto_$login_admin.pedido_faturado           THEN tbl_posto_fabrica.pedido_faturado         ELSE null END AS pedido_faturado_alterado            ,
							CASE WHEN tbl_posto_fabrica.digita_os                <> tmp_posto_$login_admin.digita_os                 THEN tbl_posto_fabrica.digita_os               ELSE null END AS digita_os_alterado                  ,
							CASE WHEN tbl_posto_fabrica.prestacao_servico        <> tmp_posto_$login_admin.prestacao_servico         THEN tbl_posto_fabrica.prestacao_servico       ELSE null END AS prestacao_servico_alterado          ,
							CASE WHEN tbl_posto_fabrica.banco                    <> tmp_posto_$login_admin.banco                     THEN tbl_posto_fabrica.banco                   ELSE null END AS banco_alterado                      ,
							CASE WHEN tbl_posto_fabrica.agencia                  <> tmp_posto_$login_admin.agencia                   THEN tbl_posto_fabrica.agencia                 ELSE null END AS agencia_alterado                    ,
							CASE WHEN tbl_posto_fabrica.conta                    <> tmp_posto_$login_admin.conta                     THEN tbl_posto_fabrica.conta                   ELSE null END AS conta_alterado                      ,
							CASE WHEN tbl_posto_fabrica.nomebanco                <> tmp_posto_$login_admin.nomebanco                 THEN tbl_posto_fabrica.nomebanco               ELSE null END AS nomebanco_alterado                  ,
							CASE WHEN tbl_posto_fabrica.favorecido_conta         <> tmp_posto_$login_admin.favorecido_conta          THEN tbl_posto_fabrica.favorecido_conta        ELSE null END AS favorecido_conta_alterado           ,
							CASE WHEN tbl_posto_fabrica.cpf_conta                <> tmp_posto_$login_admin.cpf_conta                 THEN tbl_posto_fabrica.cpf_conta               ELSE null END AS cpf_conta_alterado                  ,
							CASE WHEN tbl_posto_fabrica.tipo_conta               <> tmp_posto_$login_admin.tipo_conta                THEN tbl_posto_fabrica.tipo_conta              ELSE null END AS tipo_conta_alterado                 ,
							CASE WHEN tbl_posto_fabrica.obs_conta                <> tmp_posto_$login_admin.obs_conta                 THEN tbl_posto_fabrica.obs_conta               ELSE null END AS obs_conta_alterado                  ,
							CASE WHEN tbl_posto_fabrica.pedido_via_distribuidor  <> tmp_posto_$login_admin.pedido_via_distribuidor   THEN tbl_posto_fabrica.pedido_via_distribuidor ELSE null END AS pedido_via_distribuidor_alterado    ,
							CASE WHEN tbl_posto_fabrica.item_aparencia           <> tmp_posto_$login_admin.item_aparencia            THEN tbl_posto_fabrica.item_aparencia          ELSE null END AS item_aparencia_alterado             ,
							CASE WHEN tbl_posto_fabrica.garantia_antecipada      <> tmp_posto_$login_admin.garantia_antecipada       THEN tbl_posto_fabrica.garantia_antecipada     ELSE null END AS garantia_antecipada_alterado        ,
							CASE WHEN tbl_posto_fabrica.escritorio_regional      <> tmp_posto_$login_admin.escritorio_regional       THEN tbl_posto_fabrica.escritorio_regional     ELSE null END AS escritorio_regional_alterado        ,
							CASE WHEN tbl_posto_fabrica.tabela                   <> tmp_posto_$login_admin.tabela                    THEN tbl_posto_fabrica.tabela                  ELSE null END AS tabela_alterado                     ,
							CASE WHEN tbl_posto.nome                             <> tmp_posto_$login_admin.nome                      THEN tbl_posto.nome                            ELSE null END AS nome_alterado                       ,
							CASE WHEN tbl_posto.cnpj                             <> tmp_posto_$login_admin.cnpj                      THEN tbl_posto.cnpj                            ELSE null END AS cnpj_alterado                       ,
							CASE WHEN tbl_posto.ie                               <> tmp_posto_$login_admin.ie                        THEN tbl_posto.ie                              ELSE null END AS ie_alterado                         ,
							/* HD 52864 20/11/2008
							CASE WHEN tbl_posto.fone                             <> tmp_posto_$login_admin.fone                      THEN tbl_posto.fone                            ELSE null END AS fone_alterado                       ,
							CASE WHEN tbl_posto.fax                              <> tmp_posto_$login_admin.fax                       THEN tbl_posto.fax                             ELSE null END AS fax_alterado                        ,
							*/
							CASE WHEN tbl_posto.capital_interior                 <> tmp_posto_$login_admin.capital_interior          THEN tbl_posto.capital_interior                ELSE null END AS capital_interior_alterado           ,
							CASE WHEN tbl_posto.contato                          <> tmp_posto_$login_admin.contato                   THEN tbl_posto.contato                         ELSE null END AS contato_alterado                    ,
							tmp_posto_$login_admin.*
					FROM   tbl_posto_fabrica
					JOIN   tbl_posto USING (posto)
					JOIN   tmp_posto_$login_admin USING (posto)
					WHERE  tbl_posto_fabrica.fabrica = $login_fabrica
					AND    tbl_posto_fabrica.posto   = $posto";
			$res=pg_query($con,$sql);


			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
			$destinatario = "samel.silva@br.bosch.com";
			$assunto      = "Alteração no posto $xcodigo_posto";

			if(strlen(pg_fetch_result($res,0,codigo_posto_alterado           ))        >0 ) $mensagem ="Foi alterado o código do posto, de -" .pg_fetch_result($res,0,codigo_posto). "para - ".pg_fetch_result($res,0,codigo_posto_alterado) ."<br>";
			if(strlen(pg_fetch_result($res,0,credenciamento_alterado         ))        >0 ) $mensagem ="Foi alterado o credenciamento, de -" .pg_fetch_result($res,0,credenciamento). "para - ".pg_fetch_result($res,0,credenciamento_alterado) ."<br>";
			if(strlen(pg_fetch_result($res,0,senha_alterado                  ))        >0 ) $mensagem.="Foi alterada a Senha, de - " .pg_fetch_result($res,0,senha). " para - " .pg_fetch_result($res,0,senha_alterado) . "<br>";
			if(strlen(pg_fetch_result($res,0,desconto_alterado               ))        >0 ) $mensagem.="Foi alterado o Desconto, de - " .pg_fetch_result($res,0,desconto               ). " para - " .pg_fetch_result($res,0,desconto_alterado               ) . "<br>";
			if(strlen(pg_fetch_result($res,0,desconto_acessorio_alterado     ))        >0 ) $mensagem.="Foi alterada o Desconto Acessório, de - " .pg_fetch_result($res,0,desconto_acessorio     ). " para - " .pg_fetch_result($res,0,desconto_acessorio_alterado     ) . "<br>";
			if(strlen(pg_fetch_result($res,0,custo_administrativo_alterado   ))        >0 ) $mensagem.="Foi alterada o Custo Administrativo, de - " .pg_fetch_result($res,0,custo_administrativo   ). " para - " .pg_fetch_result($res,0,custo_administrativo_alterado   ) . "<br>";
			if(strlen(pg_fetch_result($res,0,imposto_al_alterado             ))        >0 ) $mensagem.="Foi alterada o Imposto IVA, de - " .pg_fetch_result($res,0,imposto_al             ). " para - " .pg_fetch_result($res,0,imposto_al_alterado             ) . "<br>";
			if(strlen(pg_fetch_result($res,0,tipo_posto_alterado             ))        >0 ) $mensagem.="Foi alterada o Tipo do posto, de - " .pg_fetch_result($res,0,tipo_posto             ). " para - " .pg_fetch_result($res,0,tipo_posto_alterado             ) . "<br>";
			if(strlen(pg_fetch_result($res,0,obs_alterado                    ))        >0 ) $mensagem.="Foi alterada a observação, de - " .pg_fetch_result($res,0,obs                    ). " para - " .pg_fetch_result($res,0,obs_alterado                    ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_endereco_alterado       ))        >0 ) $mensagem.="Foi alterada o endereço, de - " .pg_fetch_result($res,0,contato_endereco       ). " para - " .pg_fetch_result($res,0,contato_endereco_alterado       ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_numero_alterado         ))        >0 ) $mensagem.="Foi alterada o número, de - " .pg_fetch_result($res,0,contato_numero         ). " para - " .pg_fetch_result($res,0,contato_numero_alterado         ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_complemento_alterado    ))        >0 ) $mensagem.="Foi alterada o complemento, de - " .pg_fetch_result($res,0,contato_complemento    ). " para - " .pg_fetch_result($res,0,contato_complemento_alterado    ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_bairro_alterado         ))        >0 ) $mensagem.="Foi alterada o bairro, de - " .pg_fetch_result($res,0,contato_bairro         ). " para - " .pg_fetch_result($res,0,contato_bairro_alterado         ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_cidade_alterado         ))        >0 ) $mensagem.="Foi alterada a cidade, de - " .pg_fetch_result($res,0,contato_cidade         ). " para - " .pg_fetch_result($res,0,contato_cidade_alterado         ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_cep_alterado            ))        >0 ) $mensagem.="Foi alterada o cep, de - " .pg_fetch_result($res,0,contato_cep            ). " para - " .pg_fetch_result($res,0,contato_cep_alterado            ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_estado_alterado         ))        >0 ) $mensagem.="Foi alterada o estado, de - " .pg_fetch_result($res,0,contato_estado         ). " para - " .pg_fetch_result($res,0,contato_estado_alterado         ) . "<br>";
			if(strlen(pg_fetch_result($res,0,transportadora_alterado         ))        >0 ) $mensagem.="Foi alterada a transportadora, de - " .pg_fetch_result($res,0,transportadora         ). " para - " .pg_fetch_result($res,0,transportadora_alterado         ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cobranca_endereco_alterado      ))        >0 ) $mensagem.="Foi alterada o endereço da cobrança, de - " .pg_fetch_result($res,0,cobranca_endereco      ). " para - " .pg_fetch_result($res,0,cobranca_endereco_alterado      ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cobranca_numero_alterado        ))        >0 ) $mensagem.="Foi alterada o número da cobrança, de - " .pg_fetch_result($res,0,cobranca_numero        ). " para - " .pg_fetch_result($res,0,cobranca_numero_alterado        ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cobranca_complemento_alterado   ))        >0 ) $mensagem.="Foi alterada o complemento da cobrança, de - " .pg_fetch_result($res,0,cobranca_complemento   ). " para - " .pg_fetch_result($res,0,cobranca_complemento_alterado   ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cobranca_bairro_alterado        ))        >0 ) $mensagem.="Foi alterada o bairro da cobrança, de - " .pg_fetch_result($res,0,cobranca_bairro        ). " para - " .pg_fetch_result($res,0,cobranca_bairro_alterado        ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cobranca_cep_alterado           ))        >0 ) $mensagem.="Foi alterada o cep da cobrança, de - " .pg_fetch_result($res,0,cobranca_cep           ). " para - " .pg_fetch_result($res,0,cobranca_cep_alterado           ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cobranca_cidade_alterado        ))        >0 ) $mensagem.="Foi alterada a cidade da cobrança, de - " .pg_fetch_result($res,0,cobranca_cidade        ). " para - " .pg_fetch_result($res,0,cobranca_cidade_alterado        ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cobranca_estado_alterado        ))        >0 ) $mensagem.="Foi alterada o  da cobrança, de - " .pg_fetch_result($res,0,cobranca_estado        ). " para - " .pg_fetch_result($res,0,cobranca_estado_alterado        ) . "<br>";
			if(strlen(pg_fetch_result($res,0,agencia_alterado                ))        >0 ) $mensagem.="Foi alterada a agencia, de - " .pg_fetch_result($res,0,agencia                ). " para - " .pg_fetch_result($res,0,agencia_alterado                ) . "<br>";
			if(strlen(pg_fetch_result($res,0,conta_alterado                  ))        >0 ) $mensagem.="Foi alterada a conta, de - " .pg_fetch_result($res,0,conta                  ). " para - " .pg_fetch_result($res,0,conta_alterado                  ) . "<br>";
			if(strlen(pg_fetch_result($res,0,nomebanco_alterado              ))        >0 ) $mensagem.="Foi alterada o banco , de - " .pg_fetch_result($res,0,nomebanco              ). " para - " .pg_fetch_result($res,0,nomebanco_alterado              ) . "<br>";
			if(strlen(pg_fetch_result($res,0,favorecido_conta_alterado       ))        >0 ) $mensagem.="Foi alterada o Nome favorecido, de - " .pg_fetch_result($res,0,favorecido_conta       ). " para - " .pg_fetch_result($res,0,favorecido_conta_alterado       ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cpf_conta_alterado              ))        >0 ) $mensagem.="Foi alterada o CPF do favorecido, de - " .pg_fetch_result($res,0,cpf_conta              ). " para - " .pg_fetch_result($res,0,cpf_conta_alterado              ) . "<br>";
			if(strlen(pg_fetch_result($res,0,tipo_conta_alterado             ))        >0 ) $mensagem.="Foi alterada o tipo de conta, de - " .pg_fetch_result($res,0,tipo_conta             ). " para - " .pg_fetch_result($res,0,tipo_conta_alterado             ) . "<br>";
			if(strlen(pg_fetch_result($res,0,obs_conta_alterado              ))        >0 ) $mensagem.="Foi alterada a observação da conta, de - " .pg_fetch_result($res,0,obs_conta              ). " para - " .pg_fetch_result($res,0,obs_conta_alterado              ) . "<br>";
			if(strlen(pg_fetch_result($res,0,escritorio_regional_alterado    ))        >0 ) $mensagem.="Foi alterada O escritório regional, de - " .pg_fetch_result($res,0,escritorio_regional    ). " para - " .pg_fetch_result($res,0,escritorio_regional_alterado    ) . "<br>";
			if(strlen(pg_fetch_result($res,0,tabela_alterado                 ))        >0 ) $mensagem.="Foi alterada a tabela, de - " .pg_fetch_result($res,0,tabela                 ). " para - " .pg_fetch_result($res,0,tabela_alterado                 ) . "<br>";
			if(strlen(pg_fetch_result($res,0,nome_alterado                   ))        >0 ) $mensagem.="Foi alterada o Nome do posto, de - " .pg_fetch_result($res,0,nome                   ). " para - " .pg_fetch_result($res,0,nome_alterado                   ) . "<br>";
			if(strlen(pg_fetch_result($res,0,cnpj_alterado                   ))        >0 ) $mensagem.="Foi alterada o CNPJ do posto, de - " .pg_fetch_result($res,0,cnpj                   ). " para - " .pg_fetch_result($res,0,cnpj_alterado                   ) . "<br>";
			if(strlen(pg_fetch_result($res,0,ie_alterado                     ))        >0 ) $mensagem.="Foi alterada o Inscrição Estadual, de - " .pg_fetch_result($res,0,ie                     ). " para - " .pg_fetch_result($res,0,ie_alterado                     ) . "<br>";
			if(strlen(pg_fetch_result($res,0,capital_interior_alterado       ))        >0 ) $mensagem.="Foi alterada o Capital/Interior, de - " .pg_fetch_result($res,0,capital_interior       ). " para - " .pg_fetch_result($res,0,capital_interior_alterado       ) . "<br>";
			if(strlen(pg_fetch_result($res,0,contato_alterado                ))        >0 ) $mensagem.="Foi alterada o contato, de - " .pg_fetch_result($res,0,contato                ). " para - " .pg_fetch_result($res,0,contato_alterado                ) . "<br>";
			if(pg_fetch_result($res,0,pedido_em_garantia_alterado)          =='t') $mensagem.="Este posto não fazia PEDIDO EM GARANTIA (Manual), foi alerado para poder fazer<br>";
			elseif(pg_fetch_result($res,0,pedido_em_garantia_alterado)      =='f')                                                                       $mensagem.="Este posto fazia PEDIDO EM GARANTIA (Manual), foi alerado para não poder fazer<br>";
			if(pg_fetch_result($res,0,pedido_faturado_alterado)             =='t') $mensagem.="Este posto não fazia PEDIDO FATURADO (Manual), foi alerado para poder fazer<br>";
			elseif(pg_fetch_result($res,0,pedido_faturado_alterado)         =='f')                                                                       $mensagem.="Este posto fazia DIGITA OS, foi alerado para não poder fazer<br>";
			if(pg_fetch_result($res,0,digita_os_alterado)                   =='t') $mensagem.="Este posto não fazia DIGITA OS, foi alerado para poder fazer<br>";
			elseif(pg_fetch_result($res,0,digita_os_alterado)               =='f')                                                                      $mensagem.="Este posto fazia PEDIDO FATURADO (Manual), foi alerado para não poder fazer<br>";
			if(pg_fetch_result($res,0,prestacao_servico_alterado)           =='t') $mensagem.="Este posto não fazia PRESTAÇÃO DE SERVIÇO, foi alerado para poder fazer<br>";
			elseif(pg_fetch_result($res,0,prestacao_servico_alterado)       =='f')                                                                       $mensagem.="Este posto fazia PRESTAÇÃO DE SERVIÇO, foi alerado para não poder fazer<br>";
			if(pg_fetch_result($res,0,pedido_via_distribuidor_alterado)     =='t') $mensagem.="Este posto não fazia PEDIDO VIA DISTRIBUIDOR, foi alerado para poder fazer<br>";
			elseif(pg_fetch_result($res,0,pedido_via_distribuidor_alterado) =='f')                                                                       $mensagem.="Este posto fazia PEDIDO VIA DISTRIBUIDOR, foi alerado para não poder fazer<br>";
			if(pg_fetch_result($res,0,item_aparencia_alterado)              =='t') $mensagem.="Este posto não podia pedir peças com item de aparência, foi alerado para poder fazer<br>";
			elseif(pg_fetch_result($res,0,item_aparencia_alterado)          =='f')                                                                       $mensagem.="Este posto podia pedir peças com item de aparência, foi alerado para não poder fazer<br>";

			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
			if(strlen(trim($mensagem))>0) {
				$conteudo="Foi alterado os dados do posto $xcodigo_posto:<br>".$mensagem;
				if(mail($destinatario,$assunto,$conteudo,$headers)){
					#echo "enviado com sucesso"; exit;
				};
			}
		}

		if (strlen($msg_erro) == 0 && $login_fabrica == 1) {
			$sql =	"SELECT DISTINCT tbl_condicao.condicao , tbl_posto_condicao.visivel
					FROM tbl_condicao
					JOIN tbl_posto_condicao USING (condicao)
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_condicao.fabrica = $login_fabrica
					AND   tbl_condicao.tabela  = 31
					AND   tbl_condicao.visivel IS TRUE
					AND   tbl_posto_fabrica.tipo_posto = $xtipo_posto
					ORDER BY tbl_condicao.condicao ASC";
			$res1 = pg_query($con,$sql);
			for ($i = 0 ; $i < pg_num_rows($res1) ; $i++) {
				$condicao = pg_fetch_result($res1,$i,condicao);
				$visivel  = pg_fetch_result($res1,$i,visivel);

				$tabela = ($condicao == 62) ? 47 : 31;

				$sql =	"SELECT condicao
						FROM tbl_posto_condicao
						WHERE posto  = $posto
						AND condicao = $condicao;";
				$res2 = pg_query($con,$sql);
				if (pg_num_rows($res2) > 0) {
					$sql =	"UPDATE tbl_posto_condicao SET
								tabela  = $tabela,
								visivel = '$visivel'
							WHERE tbl_posto_condicao.condicao = $condicao
							AND   tbl_posto_condicao.posto    = $posto;";
				}else{
					$sql = "INSERT INTO tbl_posto_condicao (
								posto    ,
								condicao ,
								tabela   ,
								visivel
							) VALUES (
								$posto    ,
								$condicao ,
								$tabela   ,
								'$visivel'
							);";
				}
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (strlen($msg_erro) > 0) {
					$msg_erro = " Não foi possível cadastrar a condição de pagamento p/ este posto. ";
					break;
				}
			}

			if($login_fabrica==1){
				$sql = "UPDATE tbl_posto_condicao SET
								visivel = $xpedido_em_garantia_finalidades_diversas
						WHERE posto  = $posto
						AND condicao = 62";
				$res = @pg_query($con,$sql);
			}

			/* HD 23738 */
			if($login_fabrica==1){
				$sql = "SELECT	tbl_posto_fabrica.escolhe_condicao,
								tbl_posto_fabrica.condicao_escolhida,
								tbl_posto.nome,
								tbl_posto_fabrica.contato_email
						FROM tbl_posto_fabrica
						JOIN tbl_posto USING(posto)
						WHERE tbl_posto_fabrica.posto   = $posto
						AND   tbl_posto_fabrica.fabrica = $login_fabrica";
				$res = @pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					$escolhe_condicao_ant = pg_fetch_result($res,0,escolhe_condicao);
					$condicao_escolhida   = pg_fetch_result($res,0,condicao_escolhida);
					$posto_nome           = pg_fetch_result($res,0,nome);
					$posto_email          = pg_fetch_result($res,0,contato_email);

					$sql = "UPDATE tbl_posto_fabrica SET
									escolhe_condicao = $xescolhe_condicao
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res = @pg_query($con,$sql);

					if ($condicao_escolhida == '' AND $escolhe_condicao_ant <> 't'){
						if ($escolhe_condicao == 't'){

							/* Dispara um email para o PA */
							$assunto = "Definir condição de pagamento: Posto $codigo_posto";
							$mensagem  = "<b>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM </b>****.<BR><BR><BR>";
							$mensagem .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
							$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
							$mensagem .= "<b>Prezado Cliente,</b> ";
							$mensagem .= "<br><br>\n";
							$mensagem .= "Informamos que o seu posto foi nomeado para adquirir peças direto com a fábrica.";
							$mensagem .= "<br>\n";
							$mensagem .= "<br>\n";
							$mensagem .= "Acesse a tela de digitação no site através do caminho <b>PEDIDOS/CADASTRO DE PEDIDOS DE PEÇAS</b> para ler o procedimento sobre definição da condição de pagamento.";
							$mensagem .= "<br><br>\n";
							$mensagem .= "Obrigada.";
							$mensagem .= "<br><br>\n";
							$mensagem .= "Black & Decker do Brasil.";
							$mensagem .= "</font>";

							$cabecalho .= "MIME-Version: 1.0\n";
							$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
							$sqlemail = "SELECT email 
									FROM tbl_admin 
									WHERE fabrica = $login_fabrica 
									and tbl_admin.admin = $login_admin";
							$resemail = pg_query($con,$sqlemail);
							$email_admin = pg_fetch_result($resemail,0,email);

							$cabecalho .= "From: Black & Decker <$email_admin>\n";
							$cabecalho .= "To: $posto_nome <$posto_email>, Blackedecker <$email_admin>\n";

							$cabecalho .= "Subject: $assunto\n";
							$cabecalho .= "Return-Path: Suporte <helpdesk@telecontrol.com.br>\n";
							$cabecalho .= "X-Priority: 1\n";
							$cabecalho .= "X-MSMail-Priority: High\n";
							$cabecalho .= "X-Mailer: PHP/" . phpversion();

							if ( !mail("", $assunto, $mensagem, $cabecalho) ) {
								$msg_erro = " Não foi possível enviar o email. Tente novamente. ";
							}
						}
					}

					if ($escolhe_condicao == 't' AND $condicao_escolhida == 'f' AND strlen($condicao_liberada)>0){
						$sql = "UPDATE tbl_posto_fabrica SET
										condicao_escolhida = 't',
										escolhe_condicao   = 'f'
								WHERE posto   = $posto
								AND   fabrica = $login_fabrica";
						$res = @pg_query($con,$sql);

						/* Dispara um email para o PA */
						$assunto = "Definir condição de pagamento: Posto $codigo_posto";
						$mensagem  = "<b>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM </b>****.<BR><BR><BR>";
						$mensagem .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem .= "<b>Prezado Cliente,</b> ";
						$mensagem .= "<br><br><br>\n";
						$mensagem .= "Informamos que tela de digitação de pedido foi liberada com a condição de pagamento que você escolheu.";
						$mensagem .= "<br><br>\n";
						$mensagem .= "Acesse a tela de digitação no site através do caminho <b>PEDIDOS/CADASTRO DE PEDIDOS DE PEÇAS</b>.";
						$mensagem .= "<br><br>\n";
						$mensagem .= "Obrigada.";
						$mensagem .= "<br><br><br>\n";
						$mensagem .= "Black & Decker do Brasil.";
						$mensagem .= "</font>";

						$sqlemail = "SELECT email 
									FROM tbl_admin 
									WHERE fabrica = $login_fabrica 
									and tbl_admin.admin = $login_admin";
						$resemail = pg_query($con,$sqlemail);
						$email_admin = pg_fetch_result($resemail,0,email);
						$cabecalho .= "MIME-Version: 1.0\n";
						$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
						$cabecalho .= "From: Black & Decker <$email_admin>\n";
						$cabecalho .= "To: $posto_nome <$posto_email>, Blackedecker <$email_admin>\n";

						#$cabecalho .= "To: Rúbia Fernandes <rfernandes@blackedecker.com.br>, Fábio Nowaki <fabio@telecontrol.com.br> \n";
						$cabecalho .= "Subject: $assunto\n";
						$cabecalho .= "Return-Path: Suporte <helpdesk@telecontrol.com.br>\n";
						$cabecalho .= "X-Priority: 1\n";
						$cabecalho .= "X-MSMail-Priority: High\n";
						$cabecalho .= "X-Mailer: PHP/" . phpversion();

						if ( !mail("", $assunto, $mensagem, $cabecalho) ) {
							$msg_erro = " Não foi possível enviar o email. Tente novamente. ";
						}
					}
				}
			}
		}

		//hd 49412 - o código abaixo se repete para cada foto, pois o admin pode cadastrar fotos com extensões diferentes.
		if(strlen($msg_erro)==0 and $login_fabrica==50){
			if (isset($_FILES['foto_posto'])) {
				$Destino  = "/www/assist/www/foto_posto/";
				$DestinoT = "/www/assist/www/foto_posto/";

				$Fotos    = $_FILES['foto_posto'];
				$Nome     = $Fotos['name'];
				$Tamanho  = $Fotos['size'];
				$Tipo     = $Fotos['type'];
				$Tmpname  = $Fotos['tmp_name'];
				$Extensao = $Nome;

				if(strlen($Extensao)>0){
					if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
						if(!is_uploaded_file($Tmpname)){
							$msg_erro .= "Não foi possível efetuar o upload.";
						}

						$tmp = explode(".",$Nome);
						$ext = $tmp[count($tmp)-1];

						if (strlen($Extensao)==0){
							$ext = $Extensao;
						}

						$ext = strtolower($ext);

						$sql = "SELECT posto_fabrica_foto
								FROM tbl_posto_fabrica_foto
								WHERE posto = $posto
								AND fabrica = $login_fabrica";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) == 0) {
							#insere um registro
							$sql = "INSERT INTO tbl_posto_fabrica_foto
										(posto, fabrica)
										VALUES ($posto,$login_fabrica)";
							$res = pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
							$res                = pg_query ($con,$sql);
							$posto_fabrica_foto = pg_fetch_result($res,0,0);
						} else {
							$posto_fabrica_foto = pg_fetch_result($res,0,0);
						}

						$nome_foto  = "$posto_fabrica_foto"."_posto.$ext";
						$nome_thumb = "$posto_fabrica_foto"."_posto_thumb.$ext";

						$Caminho_foto  = "../foto_posto/$nome_foto";
						$Caminho_thumb = "../foto_posto/$nome_thumb";

						$descricao_foto_posto = str_replace("\'","",$_POST['descricao_foto_posto']);
						$descricao_foto_posto = str_replace("\"","",$descricao_foto_posto);

						#Atualiza o nome do arquivo na tabela
						if (strlen($posto_fabrica_foto)>0){
							$sql = "UPDATE tbl_posto_fabrica_foto SET
										foto_posto           = '$Caminho_foto',
										foto_posto_thumb     = '$Caminho_thumb',
										foto_posto_Descricao = '$descricao_foto_posto'
									WHERE posto_fabrica_foto = $posto_fabrica_foto";
							$res = pg_query ($con,$sql);
						}

						reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
						reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
					}else{
						$msg_erro .= "O formato da foto $Nome não é permitido!<br>";
					}
				}
			}

			if (isset($_FILES['foto_contato1'])) {
				$Destino  = "/www/assist/www/foto_posto/";
				$DestinoT = "/www/assist/www/foto_posto/";

				$Fotos    = $_FILES['foto_contato1'];
				$Nome     = $Fotos['name'];
				$Tamanho  = $Fotos['size'];
				$Tipo     = $Fotos['type'];
				$Tmpname  = $Fotos['tmp_name'];
				$Extensao = $Nome;

				if(strlen($Extensao)>0){
					if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
						if(!is_uploaded_file($Tmpname)){
							$msg_erro .= "Não foi possível efetuar o upload.";
						}

						$tmp = explode(".",$Nome);
						$ext = $tmp[count($tmp)-1];

						if (strlen($Extensao)==0){
							$ext = $Extensao;
						}

						$ext = strtolower($ext);

						$sql = "SELECT posto_fabrica_foto
								FROM tbl_posto_fabrica_foto
								WHERE posto = $posto
								AND fabrica = $login_fabrica";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) == 0) {
							#insere um registro
							$sql = "INSERT INTO tbl_posto_fabrica_foto
										(posto, fabrica)
										VALUES ($posto,$login_fabrica)";
							$res = pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
							$res                = pg_query ($con,$sql);
							$posto_fabrica_foto = pg_fetch_result($res,0,0);
						} else {
							$posto_fabrica_foto = pg_fetch_result($res,0,0);
						}

						$nome_foto  = "$posto_fabrica_foto"."_contato1.$ext";
						$nome_thumb = "$posto_fabrica_foto"."_contato1_thumb.$ext";

						$Caminho_foto  = "../foto_posto/$nome_foto";
						$Caminho_thumb = "../foto_posto/$nome_thumb";

						$descricao_foto_contato1 = str_replace("\'","",$_POST['descricao_foto_contato1']);
						$descricao_foto_contato1 = str_replace("\"","",$descricao_foto_contato1);


						#Atualiza o nome do arquivo na tabela
						if (strlen($posto_fabrica_foto)>0){
							$sql = "UPDATE tbl_posto_fabrica_foto SET
										foto_contato1           = '$Caminho_foto',
										foto_contato1_thumb     = '$Caminho_thumb',
										foto_contato1_descricao = '$descricao_foto_contato1'
									WHERE posto_fabrica_foto = $posto_fabrica_foto";
							$res = pg_query ($con,$sql);
						}

						reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
						reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
					}else{
						$msg_erro .= "O formato da foto $Nome não é permitido!<br>";
					}
				}
			}

			if (isset($_FILES['foto_contato2'])) {
				$Destino  = "/www/assist/www/foto_posto/";
				$DestinoT = "/www/assist/www/foto_posto/";

				$Fotos    = $_FILES['foto_contato2'];
				$Nome     = $Fotos['name'];
				$Tamanho  = $Fotos['size'];
				$Tipo     = $Fotos['type'];
				$Tmpname  = $Fotos['tmp_name'];
				$Extensao = $Nome;

				if(strlen($Extensao)>0){
					if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
						if(!is_uploaded_file($Tmpname)){
							$msg_erro .= "Não foi possível efetuar o upload.";
						}

						$tmp = explode(".",$Nome);
						$ext = $tmp[count($tmp)-1];

						if (strlen($Extensao)==0){
							$ext = $Extensao;
						}

						$ext = strtolower($ext);

						$sql = "SELECT posto_fabrica_foto
								FROM tbl_posto_fabrica_foto
								WHERE posto = $posto
								AND fabrica = $login_fabrica";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) == 0) {
							#insere um registro
							$sql = "INSERT INTO tbl_posto_fabrica_foto
										(posto, fabrica)
										VALUES ($posto,$login_fabrica)";
							$res = pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
							$res                = pg_query ($con,$sql);
							$posto_fabrica_foto = pg_fetch_result($res,0,0);
						} else {
							$posto_fabrica_foto = pg_fetch_result($res,0,0);
						}

						$nome_foto  = "$posto_fabrica_foto"."_contato2.$ext";
						$nome_thumb = "$posto_fabrica_foto"."_contato2_thumb.$ext";

						$Caminho_foto  = "../foto_posto/$nome_foto";
						$Caminho_thumb = "../foto_posto/$nome_thumb";

						$descricao_foto_contato2 = str_replace("\'","",$_POST['descricao_foto_contato2']);
						$descricao_foto_contato2 = str_replace("\"","",$descricao_foto_contato2);

						#Atualiza o nome do arquivo na tabela
						if (strlen($posto_fabrica_foto)>0){
							$sql = "UPDATE tbl_posto_fabrica_foto SET
										foto_contato2       = '$Caminho_foto',
										foto_contato2_thumb = '$Caminho_thumb',
										foto_contato2_descricao = '$descricao_foto_contato2'
									WHERE posto_fabrica_foto = $posto_fabrica_foto";
							$res = pg_query ($con,$sql);
						}

						reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
						reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
					}else{
						$msg_erro .= "O formato da foto $Nome não é permitido!<br>";
					}
				}
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
			exit;
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}//fim if msg_erro
}

#-------------------- Pesquisa Posto -----------------
if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen($posto) > 0 and strlen ($msg_erro) == 0 ) {
	$sql = "SELECT  tbl_posto_fabrica.posto               ,
			tbl_posto_fabrica.credenciamento      ,
			tbl_posto_fabrica.codigo_posto        ,
			tbl_posto_fabrica.posto_empresa       ,
			tbl_posto_fabrica.tipo_posto          ,
			tbl_posto_fabrica.transportadora_nome ,
			tbl_posto_fabrica.transportadora      ,
			tbl_posto_fabrica.cobranca_endereco   ,
			tbl_posto_fabrica.cobranca_numero     ,
			tbl_posto_fabrica.cobranca_complemento,
			tbl_posto_fabrica.cobranca_bairro     ,
			tbl_posto_fabrica.cobranca_cep        ,
			tbl_posto_fabrica.cobranca_cidade     ,
			tbl_posto_fabrica.cobranca_estado     ,
			tbl_posto_fabrica.obs                 ,
			tbl_posto_fabrica.banco               ,
			tbl_posto_fabrica.agencia             ,
			tbl_posto_fabrica.conta               ,
			tbl_posto_fabrica.nomebanco           ,
			tbl_posto_fabrica.favorecido_conta    ,
			tbl_posto_fabrica.conta_operacao      ,
			tbl_posto_fabrica.cpf_conta           ,
			tbl_posto_fabrica.atendimento         ,
			tbl_posto_fabrica.tipo_conta          ,
			tbl_posto_fabrica.obs_conta           ,
			tbl_posto.nome                        ,
			tbl_posto.cnpj                        ,
			tbl_posto.ie                          ,
			tbl_posto.im                          ,
			tbl_posto_fabrica.contato_endereco       AS endereco,
			tbl_posto_fabrica.contato_numero         AS numero,
			tbl_posto_fabrica.contato_complemento    AS complemento,
			tbl_posto_fabrica.contato_bairro         AS bairro,
			tbl_posto_fabrica.contato_cep            AS cep,
			tbl_posto_fabrica.contato_cidade         AS cidade,
			tbl_posto_fabrica.contato_estado         AS estado,
			tbl_posto_fabrica.contato_email          AS email,
			tbl_posto_fabrica.contato_fone_comercial AS fone,
			tbl_posto_fabrica.contato_fax            AS fax,
			/* HD 52864 19/11/2008
			tbl_posto.fone                        ,
			tbl_posto.fax                         ,*/
			tbl_posto.suframa                     ,
			tbl_posto.contato                     ,
			tbl_posto.capital_interior            ,
			tbl_posto_fabrica.nome_fantasia       ,
			tbl_posto.pais                        ,
			tbl_posto_fabrica.item_aparencia      ,
			tbl_posto_fabrica.senha               ,
			tbl_posto_fabrica.desconto            ,
			tbl_posto_fabrica.valor_km            ,
			tbl_posto_fabrica.desconto_acessorio  ,
			tbl_posto_fabrica.custo_administrativo,
			tbl_posto_fabrica.imposto_al          ,
			tbl_posto_fabrica.pedido_em_garantia  ,
			tbl_posto_fabrica.reembolso_peca_estoque,
			tbl_posto_fabrica.coleta_peca         ,
			tbl_posto_fabrica.pedido_faturado     ,
			tbl_posto_fabrica.digita_os           ,
			tbl_posto_fabrica.prestacao_servico   ,
			tbl_posto_fabrica.prestacao_servico_sem_mo,
			tbl_posto_fabrica.atende_comgas       ,
			tbl_posto_fabrica.senha_financeiro    ,
			tbl_posto.senha_tabela_preco          ,
			tbl_posto_fabrica.admin               ,
			to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
			tbl_posto_fabrica.pedido_via_distribuidor,
			tbl_posto_fabrica.garantia_antecipada,
			tbl_posto_fabrica.escritorio_regional,
			tbl_posto_fabrica.imprime_os         ,
			to_char(tbl_posto_fabrica.data_nomeacao,'DD/MM/YYYY') AS data_nomeacao,
			tbl_posto_fabrica.qtde_os_item,
			tbl_posto_fabrica.escolhe_condicao,
			tbl_posto_fabrica.condicao_escolhida,
			tbl_posto_fabrica.atende_consumidor,
			tbl_posto_fabrica.admin_sap,
			tbl_posto_fabrica.divulgar_consumidor
		FROM      tbl_posto
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		WHERE     tbl_posto_fabrica.fabrica = $login_fabrica
		AND       tbl_posto_fabrica.posto   = $posto ";

	$res = pg_query ($con,$sql);
	if (pg_num_rows ($res) > 0) {
		$posto            = trim(pg_fetch_result($res,0,posto));
		$credenciamento   = trim(pg_fetch_result($res,0,credenciamento));
		$codigo           = trim(pg_fetch_result($res,0,codigo_posto));
		$nome             = trim(pg_fetch_result($res,0,nome));
		$cnpj             = trim(pg_fetch_result($res,0,cnpj));
		$ie               = trim(pg_fetch_result($res,0,ie));
		$im               = trim(pg_fetch_result($res,0,im));
		if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		$endereco         = trim(pg_fetch_result($res,0,endereco));
		$endereco         = str_replace("\"","",$endereco);
		$numero           = trim(pg_fetch_result($res,0,numero));
		$complemento      = trim(pg_fetch_result($res,0,complemento));
		$bairro           = trim(pg_fetch_result($res,0,bairro));
		$cep              = trim(pg_fetch_result($res,0,cep));
		$cidade           = trim(pg_fetch_result($res,0,cidade));
		$estado           = trim(pg_fetch_result($res,0,estado));
		$email            = trim(pg_fetch_result($res,0,email));
		$fone             = trim(pg_fetch_result($res,0,fone));
		$fax              = trim(pg_fetch_result($res,0,fax));
		$contato          = trim(pg_fetch_result($res,0,contato));
		$suframa          = trim(pg_fetch_result($res,0,suframa));
		$item_aparencia   = trim(pg_fetch_result($res,0,item_aparencia));
		$obs              = trim(pg_fetch_result($res,0,obs));
		$capital_interior = trim(pg_fetch_result($res,0,capital_interior));
		$posto_empresa    = trim(pg_fetch_result($res,0,posto_empresa));
		$tipo_posto       = trim(pg_fetch_result($res,0,tipo_posto));
		$senha            = trim(pg_fetch_result($res,0,senha));
		$pais            = trim(pg_fetch_result($res,0,pais));
		$desconto         = trim(pg_fetch_result($res,0,desconto));
		$valor_km         = trim(pg_fetch_result($res,0,valor_km));
		$desconto_acessorio       = trim(pg_fetch_result($res,0,desconto_acessorio));
		$custo_administrativo     = trim(pg_fetch_result($res,0,custo_administrativo));
		$imposto_al               = trim(pg_fetch_result($res,0,imposto_al));
		$nome_fantasia            = trim(pg_fetch_result($res,0,nome_fantasia));
		$transportadora           = trim(pg_fetch_result($res,0,transportadora));
		$escritorio_regional      = trim(pg_fetch_result($res,0,escritorio_regional));

		$cobranca_endereco       = trim(pg_fetch_result($res,0,cobranca_endereco));
		$cobranca_numero         = trim(pg_fetch_result($res,0,cobranca_numero));
		$cobranca_complemento    = trim(pg_fetch_result($res,0,cobranca_complemento));
		$cobranca_bairro         = trim(pg_fetch_result($res,0,cobranca_bairro));
		$cobranca_cep            = trim(pg_fetch_result($res,0,cobranca_cep));
		$cobranca_cidade         = trim(pg_fetch_result($res,0,cobranca_cidade));
		$cobranca_estado         = trim(pg_fetch_result($res,0,cobranca_estado));
		$pedido_em_garantia      = trim(pg_fetch_result($res,0,pedido_em_garantia));
		$reembolso_peca_estoque  = trim(pg_fetch_result($res,0,reembolso_peca_estoque));
		$coleta_peca            = trim(pg_fetch_result($res,0,coleta_peca));
		$pedido_faturado         = trim(pg_fetch_result($res,0,pedido_faturado));
		$digita_os               = trim(pg_fetch_result($res,0,digita_os));
		$prestacao_servico       = trim(pg_fetch_result($res,0,prestacao_servico));
		$prestacao_servico_sem_mo= trim(pg_fetch_result($res,0,prestacao_servico_sem_mo));
		$banco                   = trim(pg_fetch_result($res,0,banco));
		$agencia                 = trim(pg_fetch_result($res,0,agencia));
		$conta                   = trim(pg_fetch_result($res,0,conta));
		$nomebanco               = trim(pg_fetch_result($res,0,nomebanco));
		$favorecido_conta        = trim(pg_fetch_result($res,0,favorecido_conta));
		$conta_operacao          = trim(pg_fetch_result($res,0,conta_operacao));//HD 8190 5/12/2007 Gustavo
		$cpf_conta               = trim(pg_fetch_result($res,0,cpf_conta));
		$tipo_conta              = trim(pg_fetch_result($res,0,tipo_conta));
		$obs_conta               = trim(pg_fetch_result($res,0,obs_conta));
		$senha_financeiro        = trim(pg_fetch_result($res,0,senha_financeiro));
		$senha_tabela_preco      = trim(pg_fetch_result($res,0,senha_tabela_preco));
		$pedido_via_distribuidor = trim(pg_fetch_result($res,0,pedido_via_distribuidor));
		$atende_comgas           = trim(pg_fetch_result($res,0,atende_comgas));
		$atendimento_lenoxx      = trim(pg_fetch_result($res,0,atendimento));//HD 110541
		$divulgar_consumidor	 = pg_fetch_result($res,0,divulgar_consumidor);

		$admin          = trim(pg_fetch_result($res,0,admin));
		$data_alteracao = trim(pg_fetch_result($res,0,data_alteracao));
		$garantia_antecipada= trim(pg_fetch_result($res,0,garantia_antecipada));
		// HD12104
		$imprime_os=pg_fetch_result($res,0,imprime_os);
		// HD 17601
		$qtde_os_item       = pg_fetch_result($res,0,qtde_os_item);
		$escolhe_condicao   = pg_fetch_result($res,0,escolhe_condicao);
		$condicao_escolhida = pg_fetch_result($res,0,condicao_escolhida);
		// HD 126810 -	Adicionado campo 'atende_consumidor'
		$atende_consumidor	= pg_fetch_result($res,0,atende_consumidor);
		// hd 21496 - Francisco - campo Data da Nomeação para Dynacom
		$data_nomeacao		= pg_fetch_result($res,0,data_nomeacao);
		// ! HD 121248 (augusto) - Buscar atendente de posto cadastrado para este posto
		$admin_sap 			= pg_fetch_result($res,0,'admin_sap');
		# HD 110541
		if($login_fabrica==11){
		$sql_X = "select TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS dataa from tbl_credenciamento where fabrica=11 and posto=$posto order by data desc limit 1";
				$res_X = pg_query ($con,$sql_X);
				if (pg_num_rows ($res_X) > 0) {
						$data_credenciamento   = trim(pg_fetch_result($res_X,0,'dataa'));
				}
		}
	}else{
		$sql = "SELECT  tbl_posto_fabrica.posto               ,
				tbl_posto_fabrica.credenciamento      ,
				tbl_posto_fabrica.codigo_posto        ,
				tbl_posto_fabrica.posto_empresa       ,
				tbl_posto_fabrica.tipo_posto          ,
				tbl_posto_fabrica.transportadora_nome ,
				tbl_posto_fabrica.transportadora      ,
				tbl_posto_fabrica.cobranca_endereco   ,
				tbl_posto_fabrica.cobranca_numero     ,
				tbl_posto_fabrica.cobranca_complemento,
				tbl_posto_fabrica.cobranca_bairro     ,
				tbl_posto_fabrica.cobranca_cep        ,
				tbl_posto_fabrica.cobranca_cidade     ,
				tbl_posto_fabrica.cobranca_estado     ,
				tbl_posto_fabrica.obs                 ,
				tbl_posto_fabrica.digita_os           ,
				tbl_posto_fabrica.prestacao_servico   ,
				tbl_posto_fabrica.prestacao_servico_sem_mo,
				tbl_posto_fabrica.banco               ,
				tbl_posto_fabrica.agencia             ,
				tbl_posto_fabrica.conta               ,
				tbl_posto_fabrica.nomebanco           ,
				tbl_posto_fabrica.favorecido_conta    ,
				tbl_posto_fabrica.conta_operacao      ,
				tbl_posto_fabrica.cpf_conta           ,
				tbl_posto_fabrica.tipo_conta          ,
				tbl_posto_fabrica.obs_conta           ,
				tbl_posto_fabrica.atende_comgas       ,
				tbl_posto.nome                        ,
				tbl_posto.cnpj                        ,
				tbl_posto.ie                          ,
				tbl_posto.im                          ,
				tbl_posto_fabrica.contato_endereco    AS endereco,
				tbl_posto_fabrica.contato_numero      AS numero,
				tbl_posto_fabrica.contato_complemento AS complemento,
				tbl_posto_fabrica.contato_bairro      AS bairro,
				tbl_posto_fabrica.contato_cep         AS cep,
				tbl_posto_fabrica.contato_cidade      AS cidade,
				tbl_posto_fabrica.contato_estado      AS estado,
				tbl_posto_fabrica.contato_email       AS email,
				tbl_posto_fabrica.contato_fone_comercial AS fone,
				tbl_posto_fabrica.contato_fax            AS fax,
				/* HD 52864 19/11/2008
				tbl_posto.fone                        ,
				tbl_posto.fax                         ,*/
				tbl_posto.contato                     ,
				tbl_posto.suframa                     ,
				tbl_posto.pais                        ,
				tbl_posto.capital_interior            ,
				tbl_posto.nome_fantasia               ,
				tbl_posto_fabrica.item_aparencia      ,
				tbl_posto_fabrica.senha               ,
				tbl_posto_fabrica.desconto            ,
				tbl_posto_fabrica.valor_km            ,
				tbl_posto_fabrica.desconto_acessorio  ,
				tbl_posto_fabrica.custo_administrativo,
				tbl_posto_fabrica.imposto_al          ,
				tbl_posto_fabrica.pedido_em_garantia  ,
				tbl_posto_fabrica.reembolso_peca_estoque,
				tbl_posto_fabrica.coleta_peca        ,
				tbl_posto_fabrica.pedido_faturado     ,
				tbl_posto_fabrica.digita_os           ,
				tbl_posto_fabrica.prestacao_servico   ,
				tbl_posto_fabrica.prestacao_servico_sem_mo,
				tbl_posto_fabrica.senha_financeiro    ,
				tbl_posto.senha_tabela_preco          ,
				tbl_posto_fabrica.admin               ,
				to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
				tbl_posto_fabrica.pedido_via_distribuidor,
				tbl_posto_fabrica.garantia_antecipada,
				tbl_posto_fabrica.escritorio_regional,
				tbl_posto_fabrica.imprime_os         ,
				to_char(tbl_posto_fabrica.data_nomeacao,'DD/MM/YYYY') AS data_nomeacao,
				tbl_posto_fabrica.qtde_os_item,
				tbl_posto_fabrica.escolhe_condicao,
				tbl_posto_fabrica.atende_consumidor,
				tbl_posto_fabrica.condicao_escolhida,
				tbl_posto_fabrica.divulgar_consumidor
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.posto   = $posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) > 0) {
			$posto            = trim(pg_fetch_result($res,0,posto));
			//$codigo         = trim(pg_fetch_result($res,0,codigo_posto));
			$credenciamento   = trim(pg_fetch_result($res,0,credenciamento));
			$nome             = trim(pg_fetch_result($res,0,nome));
			$cnpj             = trim(pg_fetch_result($res,0,cnpj));
			$ie               = trim(pg_fetch_result($res,0,ie));
			$im               = trim(pg_fetch_result($res,0,im));
			if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
			if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
			$endereco         = trim(pg_fetch_result($res,0,endereco));
			$endereco         = str_replace("\"","",$endereco);
			$numero           = trim(pg_fetch_result($res,0,numero));
			$complemento      = trim(pg_fetch_result($res,0,complemento));
			$bairro           = trim(pg_fetch_result($res,0,bairro));
			$cep              = trim(pg_fetch_result($res,0,cep));
			$cidade           = trim(pg_fetch_result($res,0,cidade));
			$estado           = trim(pg_fetch_result($res,0,estado));
			$email            = trim(pg_fetch_result($res,0,email));
			$fone             = trim(pg_fetch_result($res,0,fone));
			$fax              = trim(pg_fetch_result($res,0,fax));
			$contato          = trim(pg_fetch_result($res,0,contato));
			$suframa          = trim(pg_fetch_result($res,0,suframa));
			$item_aparencia   = trim(pg_fetch_result($res,0,item_aparencia));
			$obs              = trim(pg_fetch_result($res,0,obs));
			$capital_interior = trim(pg_fetch_result($res,0,capital_interior));
			$posto_empresa    = trim(pg_fetch_result($res,0,posto_empresa));
			$tipo_posto       = trim(pg_fetch_result($res,0,tipo_posto));
			//$senha            = trim(pg_fetch_result($res,0,senha));
			$desconto         = trim(pg_fetch_result($res,0,desconto));
			$valor_km         = trim(pg_fetch_result($res,0,valor_km));
			$desconto_acessorio = trim(pg_fetch_result($res,0,desconto_acessorio));
			$custo_administrativo = trim(pg_fetch_result($res,0,custo_administrativo));
			$imposto_al         = trim(pg_fetch_result($res,0,imposto_al));
			$nome_fantasia    = trim(pg_fetch_result($res,0,nome_fantasia));
			$transportadora   = trim(pg_fetch_result($res,0,transportadora));
			$pais             = trim(pg_fetch_result($res,0,pais));

			$cobranca_endereco    = trim(pg_fetch_result($res,0,cobranca_endereco));
			$cobranca_numero      = trim(pg_fetch_result($res,0,cobranca_numero));
			$cobranca_complemento = trim(pg_fetch_result($res,0,cobranca_complemento));
			$cobranca_bairro      = trim(pg_fetch_result($res,0,cobranca_bairro));
			$cobranca_cep         = trim(pg_fetch_result($res,0,cobranca_cep));
			$cobranca_cidade      = trim(pg_fetch_result($res,0,cobranca_cidade));
			$cobranca_estado      = trim(pg_fetch_result($res,0,cobranca_estado));
			$pedido_em_garantia   = trim(pg_fetch_result($res,0,pedido_em_garantia));
			$reembolso_peca_estoque = trim(pg_fetch_result($res,0,reembolso_peca_estoque));
			$coleta_peca         = trim(pg_fetch_result($res,0,coleta_peca));
			$pedido_faturado      = trim(pg_fetch_result($res,0,pedido_faturado));
			$digita_os            = trim(pg_fetch_result($res,0,digita_os));
			$prestacao_servico    = trim(pg_fetch_result($res,0,prestacao_servico));
			$prestacao_servico_sem_mo = trim(pg_fetch_result($res,0,prestacao_servico_sem_mo));
			$banco                = trim(pg_fetch_result($res,0,banco));
			$agencia              = trim(pg_fetch_result($res,0,agencia));
			$conta                = trim(pg_fetch_result($res,0,conta));
			$nomebanco            = trim(pg_fetch_result($res,0,nomebanco));
			$favorecido_conta        = trim(pg_fetch_result($res,0,favorecido_conta));
			$conta_operacao          = trim(pg_fetch_result($res,0,conta_operacao));//HD 8190 5/12/2007 Gustavo
			$cpf_conta               = trim(pg_fetch_result($res,0,cpf_conta));
			$tipo_conta              = trim(pg_fetch_result($res,0,tipo_conta));
			$obs_conta               = trim(pg_fetch_result($res,0,obs_conta));
			$senha_financeiro        = trim(pg_fetch_result($res,0,senha_financeiro));
			$senha_tabela_preco      = trim(pg_fetch_result($res,0,senha_tabela_preco));
			$pedido_via_distribuidor = trim(pg_fetch_result($res,0,pedido_via_distribuidor));
			$atende_comgas           = trim(pg_fetch_result($res,0,atende_comgas));
			$escritorio_regional     = trim(pg_fetch_result($res,0,escritorio_regional));

			$admin          = trim(pg_fetch_result($res,0,admin));
			$data_alteracao = trim(pg_fetch_result($res,0,data_alteracao));
			$garantia_antecipada= trim(pg_fetch_result($res,0,garantia_antecipada));
			$imprime_os=trim(pg_fetch_result($res,0,imprime_os));
			// HD 17601
			$qtde_os_item       = pg_fetch_result($res,0,qtde_os_item);
			$escolhe_condicao   = pg_fetch_result($res,0,escolhe_condicao);
			$condicao_escolhida = pg_fetch_result($res,0,condicao_escolhida);
			// HD 126810 -	Adicionado campo 'atende_consumidor'
			$atende_consumidor	= pg_fetch_result($res,0,atende_consumidor);
			// hd 21496 - Francisco - campo Data da Nomeação para Dynacom
			$data_nomeacao=pg_fetch_result($res,0,data_nomeacao);
			$divulgar_consumidor=pg_fetch_result($res,0,divulgar_consumidor);
		}else{
			$sql = "SELECT  tbl_posto.nome                        ,
							tbl_posto.cnpj                        ,
							tbl_posto.ie                          ,
							tbl_posto.im                          ,
							tbl_posto.endereco                    ,
							tbl_posto.numero                      ,
							tbl_posto.complemento                 ,
							tbl_posto.bairro                      ,
							tbl_posto.cep                         ,
							tbl_posto.cidade                      ,
							tbl_posto.estado                      ,
							tbl_posto.email                       ,
							tbl_posto.fone                        ,
							tbl_posto.fax                         ,
							tbl_posto.contato                     ,
							tbl_posto.suframa                     ,
							tbl_posto.capital_interior            ,
							tbl_posto.senha_financeiro            ,
							tbl_posto.senha_tabela_preco          ,
							tbl_posto.pais                        ,
							tbl_posto.nome_fantasia
					FROM	tbl_posto
					WHERE   tbl_posto.posto   = $posto ";
			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) > 0) {
				$nome             = trim(pg_fetch_result($res,0,nome));
				$cnpj             = trim(pg_fetch_result($res,0,cnpj));
				if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
				if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
				$ie               = trim(pg_fetch_result($res,0,ie));
				$im               = trim(pg_fetch_result($res,0,im));
				$endereco         = trim(pg_fetch_result($res,0,endereco));
				$endereco         = str_replace("\"","",$endereco);
				$numero           = trim(pg_fetch_result($res,0,numero));
				$complemento      = trim(pg_fetch_result($res,0,complemento));
				$bairro           = trim(pg_fetch_result($res,0,bairro));
				$cep              = trim(pg_fetch_result($res,0,cep));
				$cidade           = trim(pg_fetch_result($res,0,cidade));
				$estado           = trim(pg_fetch_result($res,0,estado));
				$email            = trim(pg_fetch_result($res,0,email));
				$fone             = trim(pg_fetch_result($res,0,fone));
				$fax              = trim(pg_fetch_result($res,0,fax));
				$contato          = trim(pg_fetch_result($res,0,contato));
				$suframa          = trim(pg_fetch_result($res,0,suframa));
				$capital_interior = trim(pg_fetch_result($res,0,capital_interior));
				$senha_financeiro = trim(pg_fetch_result($res,0,senha_financeiro));
				$senha_tabela_preco = trim(pg_fetch_result($res,0,senha_tabela_preco));
				$nome_fantasia    = trim(pg_fetch_result($res,0,nome_fantasia));
				$pais             = trim(pg_fetch_result($res,0,pais));
			}
		}
	}
}

$visual_black = "manutencao-admin";

$title       = "CADASTRO  DE POSTOS AUTORIZADOS";
$cabecalho   = "CADASTRO DE POSTOS AUTORIZADOS";
$layout_menu = "cadastro";
include 'cabecalho.php';

?>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript'>
//função p/ digitar só numero
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function fnc_pesquisa_codigo_posto (codigo, nome) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_posto.php?codigo=" + codigo;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_posto (codigo, nome) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_posto.php?nome=" + nome;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (tipo == "codigo" ) {
		var xcampo = campo3;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}

//HD 5595 Listar posto revenda para Tectoy
function posto_revenda(fabrica){
	janela = window.open("posto_revenda.php?fabrica=" + fabrica ,"fabrica",'resizable=1,scrollbars=yes,width=650,height=450,top=0,left=0');
	janela.focus();
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "cnpj";
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 6){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 10){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 15){
		mycnpj = mycnpj + '-';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}



</script>

<!-- JavaScript Mapa da Rede-->
<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.alphanumeric.js" type="text/javascript"></script>
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
<script type="text/javascript">
$(document).ready(function() {
	$("input[@name=fone]").maskedinput("(99) 9999-9999");
	$("input[@name=fax]").maskedinput("(99) 9999-9999");
	$("input[@name=cidade]").alpha({allow:" "});
	$("input[@name=im]").numeric({allow:" "});
	$("input[@name=cnpj]").numeric({allow:" "});
	$( 'input[@name=cep]' ).maskedinput( '99.999-999' );
	$( 'input[@name=cobranca_cep]' ).maskedinput( '99.999-999' );
	$("input[@name=desconto]").numeric();
	$("input[@name=cpf_conta]").numeric();
	$("input[@name=cobranca_cidade]").alpha({allow : ' .,-'});
	
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
	$('#mapabr map area').click(function() {
		$('#mapa_estado').val($(this).attr('name'));
		$('#mapa_estado').change();
	});
	$('#sel_cidade').hide('fast');
	$('#abre_mapa_br').click(function() {
		$("#mapa_pesquisa").slideToggle("slow",function() {
			if ($("#mapa_pesquisa").is(":hidden")) {
				$("#abre_mapa_br").html("<div class='titulo_coluna'>Consulte o Mapa da Rede &darr;</div>");
			} else {
				$("#abre_mapa_br").html("<div class='titulo_coluna'>Esconda o Mapa da Rede &uarr;</div>");
			}
		});
	});

//  Quando muda o valor do select 'estado' requisita as cidades onde tem postos autorizados e os
//  insere no select 'cidades'
	$('#mapa_estado').change(function() {
	    var estado = $('#mapa_estado').val();
	    if (estado == '') {
			$('#sel_cidade').hide(500);
			return false;
		}
		$.get("cidade_mapa_rede.php", {'action': 'cidades','estado': estado,'fabrica':<?=$login_fabrica?>},
		  function(data){
			$('#sel_cidade').show(500);
			if (data.indexOf('Sem resultados') < 0) {
			    $('#mapa_cidades').html(data).val('').removeAttr('disabled');
				if ($('#mapa_cidades option').length == 2) {
	                $('#mapa_cidades option:last').attr('selected','selected');
	                $('#mapa_cidades').change();
				}
			} else {
			    $('#mapa_cidades').html(data).val('Sem resultados').attr('disabled','disabled');
			}
		  });
	});

	$('#mapa_cidades').change(function() {
		$('select[name=cidade]').val($('#mapa_cidades').val());
		$('[name=btn_mapa]').click();
		  });
// 	});
}); // FIM do jQuery
</script>

<style type="text/css">

.text_curto {
	text-align: center;
	font-weight: bold;
	color: #000;
	background-color: #FF6666;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}


.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 14px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<!-- CSS Mapa BR -->
<style type="text/css">
<!--
div#mapa_pesquisa {
	font-family: sans-serif, Verdana, Geneva, Arial, Helvetica;
	font-size: 11px;
	line-height: 1.2em;
	color:#88A;
    background: white;
	top: 0;
	left: 0;
	padding: 30px 10px 15px 10px;
	display: none;
}

#frmdiv {
	margin: 10px;
	text-align: left;
	width: 512px;
}

#mapabr {height:340px;position:relative;float: left}
	#mapabr label, #mapabr select {margin-left: 1em;z-index:10}
	#mapabr span {
		padding: 2px 4px;
		color: white;
		background-color: #A10F15;
	    text-shadow: 0 0 0 transparent;
		font: inherit
	}
	#mapabr h2 {margin-top: 1.5em}
	#mapabr area {cursor: pointer}
	#mapabr fieldset {
		border-radius: 5px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		height: 365px;
		width: 500px;
}

.cinza {#667}
.bold {
	font-weight: bold;
}
//-->
</style>

<?
function reduz_imagem($img, $max_x, $max_y, $nome_foto) {
	//pega o tamanho da imagem ($original_x, $original_y)
	list($width, $height) = getimagesize($img);
	$original_x = $width;
	$original_y = $height;
	// se a largura for maior que altura
	if($original_x > $original_y) {
	   $porcentagem = (100 * $max_x) / $original_x;
	}
	else {
	   $porcentagem = (100 * $max_y) / $original_y;
	}

	$tamanho_x = $original_x * ($porcentagem / 100);
	$tamanho_y = $original_y * ($porcentagem / 100);

	$image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
	$image   = imagecreatefromjpeg($img);
	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);
	imagejpeg($image_p, $nome_foto, 65);
}

if ($login_login == "fabricio" && $login_fabrica == 3) {
	$sql =	"SELECT DISTINCT
					tbl_posto.posto                                               ,
					tbl_posto_fabrica.codigo_posto                AS posto_codigo ,
					tbl_posto.nome                                AS posto_nome   ,
					TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data         ,
					tbl_credenciamento.dias                                       ,
					TO_CHAR((tbl_credenciamento.data::date + tbl_credenciamento.dias),'DD/MM/YYYY') AS data_prevista
			FROM tbl_credenciamento
			JOIN tbl_posto          ON  tbl_posto.posto           = tbl_credenciamento.posto
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_credenciamento.fabrica = $login_fabrica
			AND   UPPER(tbl_credenciamento.status) = 'EM DESCREDENCIAMENTO'
			AND   UPPER(tbl_posto_fabrica.credenciamento) = 'EM DESCREDENCIAMENTO'
			AND   (tbl_credenciamento.data::date + tbl_credenciamento.dias) < current_date
			ORDER BY tbl_posto_fabrica.codigo_posto;";
	$resC = pg_query($con,$sql);
	if (pg_num_rows($resC) > 0) {
		echo "<div id='mainCol'>";
		echo "<div class='contentBlockLeft' style='background-color: #FFCC00; width: 500;'>";
		echo "<br>";
		echo "<b>Postos com Status \"Em Descredenciamento\"</b>";
		echo "<table class='formulario'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td>Posto</td>";
			echo "<td>Data</td>";
			echo "<td>Dias</td>";
			echo "<td>Data Prevista</td>";
			echo "</tr>";
		for ($k = 0 ; $k < pg_num_rows($resC) ; $k++) {
			$cor = ($k % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td align='left'><a href='$PHP_SELF?posto=" . trim(pg_fetch_result($resC,$k,posto)) . "'>" . trim(pg_fetch_result($resC,$k,posto_codigo)) . " - " . trim(pg_fetch_result($resC,$k,posto_nome)) . "</a></td>";
			echo "<td>" . trim(pg_fetch_result($resC,$k,data)) . "</td>";
			echo "<td>" . trim(pg_fetch_result($resC,$k,dias)) . "</td>";
			echo "<td>" . trim(pg_fetch_result($resC,$k,data_prevista)) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
		echo "</div>";
		echo "</div>";
	}
}
?>

<?
	if(strlen($msg_erro) > 0){
		if (strpos($msg_erro, "tbl_posto_cnpj") ) $msg_erro = "CNPJ do posto já cadastrado.";
?>
<table width='700px' align='center' border='0' class='formulario' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<? } ?>
<?php 

$msg_sucesso = $_GET['msg'];
if ( strlen( $msg_sucesso ) > 0 ):

?>
<table class='msg_sucesso' width='700px' align='center'>
	<tr>
		<td> <?php echo $msg_sucesso ?> </td>
	</tr>
</table>

<?php
endif;
?>
<form name='frm_mapa' method='post' action='mapa_rede.php' target='_blank'>
<table width='700px' align='center' border='0' cellspacing='2' class='formulario'>
<tr>
		<td align='center' class='texto_avulso' colspan='5'>
			Para incluir um novo posto, preencha somente seu CNPJ/CPF e clique em gravar.
			<br>
			Faremos uma pesquisa para verificar se o posto já está cadastrado em nosso banco de dados.
		</td>
	</tr>
<tr class='titulo_tabela'>
	<td colspan='5'>Cadastro</td>
</tr>
	<tr>
		<td align='center' colspan='5'>
			<span id='abre_mapa_br'>
				<div class='titulo_coluna'>Consulte o Mapa da Rede &darr;</div>
			</span>
			<br>
		<div id='mapa_pesquisa'>
			<div id='frmdiv'>
				<fieldset for="frm_mapa_rede_gama">
					<legend>Pesquisa de Postos Autorizados</legend>
					<div id='mapabr'>
						<map name="Map2">
							<area shape="poly" name="RS" coords="122,238,142,221,164,232,148,262">
							<area shape="poly" name="SC" coords="143,214,172,215,169,235,143,219">
							<area shape="poly" name="PR" coords="138,202,148,191,166,192,175,207,171,214,139,213">
							<area shape="poly" name="SP" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190">

							<area shape="poly" name="MS" coords="136,195,156,171,138,159,124,159,117,182">
							<area shape="poly" name="MT" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142">
							<area shape="poly" name="RO" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121">
							<area shape="poly" name="AC" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113">
							<area shape="poly" name="AM" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82">
							<area shape="poly" name="RR" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11">
							<area shape="poly" name="PA" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25">
							<area shape="poly" name="AP" coords="145,25,153,23,157,13,164,29,153,41">
							<area shape="poly" name="MA" coords="196,50,185,72,194,90,212,82,215,59">

							<area shape="poly" name="TO" coords="179,83,165,120,189,128,185,101">
							<area shape="poly" name="GO" coords="159,166,148,157,165,131,188,136,170,151">
							<area shape="poly" name="PI" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107">
							<area shape="poly" name="RJ" coords="206,201,202,190,214,189,218,181,226,187">
							<area shape="poly" name="MG" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170">
							<area shape="poly" name="ES" coords="236,167,228,162,221,177,226,183">
							<area shape="poly" name="BA" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115">
							<area shape="poly" name="CE" coords="230,59,235,86,241,86,252,70,239,61">
							<area shape="poly" name="SE" coords="250,108,248,113,251,118,257,113,252,109">

							<area shape="poly" name="AL" coords="266,102,258,104,251,102,260,110,266,104">
							<area shape="poly" name="PE" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96">
							<area shape="poly" name="PB" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89">
							<area shape="poly" name="RN" coords="256,73,249,81,256,80,257,83,270,82,265,76">
							<area shape="poly" name="DF" coords="168,162,171,153,183,149,182,161">
						</map>
						<p style='textalign: right; font-weight: bold;'>Selecione o Estado:</p>
						<img src="/mapa_rede/imagens/mapa_azul.gif" usemap="#Map2" border="0">
					</div>
					<label for='estado'>Selecione o Estado</label><br>
					<select title='Selecione o Estado' name='mapa_estado' id='mapa_estado'>
						<option></option>
	<?				foreach ($estados as $sigla=>$estado_nome) {
						echo "\t\t\t\t<option value='$sigla'>$estado_nome</option>\n";
					}
	?>				</select>
					<div id='sel_cidade'>
			            <label for='mapa_cidades'>Selecione uma cidade</label><br>
						<select title='Selecione uma cidade' name='mapa_cidades' id='mapa_cidades'>
							<option></option>
						</select>
					</div>
				</fieldset>
			</div>
		</div>

		<? if($login_fabrica == 59) { ?>
			País
			<select class='frm' name='pais'>
			<?	$sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_pais) AS contato_pais
				FROM tbl_posto_fabrica
				WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO'AND  */
				tbl_posto_fabrica.fabrica = $login_fabrica
				ORDER BY contato_pais";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res)>0){
					echo "<option value='' selected>Todos</option>";
					for($x=0; $x<pg_num_rows($res); $x++){
						$nome_pais = pg_fetch_result($res, $x, contato_pais);
						echo "<option value='$nome_pais'>";
						echo $nome_pais;
						echo "</option>";
					}
				}
			?>
			</select>
		<? }else{ ?>
			País
			<select class='frm' name='pais'>
				<option value='BR' selected>Brasil</option>
				<option value='PE'         >Peru</option>
			</select>
		<? } ?>

		<? if($login_fabrica == 59) { ?>
			Estado
			<select class='frm' name='estado'>
			<?	$sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_estado) AS contato_estado
				FROM tbl_posto_fabrica
				WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO' AND */
				tbl_posto_fabrica.fabrica = $login_fabrica
				ORDER BY contato_estado";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res)>0){
					echo "<option value='' selected>Todos</option>";
					for($x=0; $x<pg_num_rows($res); $x++){
						$nome_estado = pg_fetch_result($res, $x, contato_estado);
						echo "<option value='$nome_estado'>$nome_estado</option>";
					}
				}
			?>
			</select>
		<? }else{ ?>
				Estado
				<select class='frm' name='estado'>
					<option value='00' selected>Todos</option>
					<option value='SP'         >São Paulo</option>
					<option value='RJ'         >Rio de Janeiro</option>
					<option value='PR'         >Paraná</option>
					<option value='SC'         >Santa Catarina</option>
					<option value='RS'         >Rio Grande do Sul</option>
					<option value='MG'         >Minas Gerais</option>
					<option value='ES'         >Espírito Santo</option>
					<option value='BR-CO'      >Centro-Oeste</option>
					<option value='BR-NE'      >Nordeste</option>
					<option value='BR-N'       >Norte</option>
				</select>
		<? } ?>
				Cidade
				<select class='frm' name='cidade' id='cidade'>
				<?	$sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_cidade) AS contato_cidade
					FROM tbl_posto_fabrica
					WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO' AND */
					tbl_posto_fabrica.fabrica = $login_fabrica
					ORDER BY contato_cidade";
					$res = pg_query($con, $sql);
					if(pg_num_rows($res)>0){
						echo "<option value='' selected>Todos</option>";
						for($x=0; $x<pg_num_rows($res); $x++){
							$nome_cidade = pg_fetch_result($res, $x, contato_cidade);
							echo "<option value='$nome_cidade'>";
							echo $nome_cidade;
							echo "</option>";
						}
					}
				?>
				</select>

				<input class='frm' type='submit' name='btn_mapa' id='btn_mapa' value='mapa'>
				</font>
			</td>
		</tr>
</form>
<br>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>" <?if ($login_fabrica == 50) {?> enctype='multipart/form-data' <?}?>>
<input type="hidden" name="posto" value="<? echo $posto ?>">

<?
	echo "<TR>";
	echo "<TD align='left'><font size='2' face='verdana' ";
	if ($credenciamento == 'CREDENCIADO')
		$colors = "color:green";
	else if ($credenciamento == 'DESCREDENCIADO')
		$colors = "color:#F3274B";
	else if ($credenciamento == 'EM DESCREDENCIAMENTO')
		$colors = "color:red";
	else if ($credenciamento == 'EM CREDENCIAMENTO')
		$colors = "color:#E8C023";
	# HD 110541
	if($login_fabrica==11 AND strlen($data_credenciamento)>0){
		if ($credenciamento == 'CREDENCIADO')
			$show_date_credenciamento = "EM: $data_credenciamento";
		else if ($credenciamento == 'DESCREDENCIADO'){
			$sql_X2 = "select TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data from tbl_credenciamento where fabrica=11 and posto=$posto and status='CREDENCIADO'";
			$res_X2 = pg_query ($con,$sql_X2);
			if (pg_num_rows ($res_X2) > 0) {
					$data_credenciamento_2   = trim(pg_fetch_result($res_X2,0,data));
					$show_date_credenciamento .= "CREDENCIADO EM: $data_credenciamento_2 E DESCREDENCIADO EM $data_credenciamento";
			}else{
				$show_date_credenciamento .= "DESCREDENCIADO EM $data_credenciamento";;
			}
		}
		else if ($credenciamento == 'EM DESCREDENCIAMENTO')
			$show_date_credenciamento = "DESDE: $data_credenciamento";
		else if ($credenciamento == 'EM CREDENCIAMENTO')
			$show_date_credenciamento = "DESDE: $data_credenciamento";
		else if ($credenciamento == 'REPROVADO') {
			$show_date_credenciamento = "EM: $data_credenciamento";
		}
	}
	echo "><B>	";
	echo "<a href='credenciamento.php?codigo=$codigo&posto=$posto&listar=3' style='$colors'>";
	# HD 110541
	if($login_fabrica==11 AND $credenciamento == 'DESCREDENCIADO'){
		echo $show_date_credenciamento;
	}else{
		echo $credenciamento."  ".$show_date_credenciamento;
	}
	echo "</B></font></TD>";

	echo "<td align='right' nowrap>";
//	if (strlen ($posto) > 0 and $login_fabrica <> 3) {
//	HD 148558 pediu para colocar também para Britânia

	if (strlen ($posto) > 0 ){
		$resX = pg_query ("SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto FROM tbl_posto_fabrica JOIN tbl_posto ON tbl_posto_fabrica.distribuidor = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.posto = $posto");

		if (pg_num_rows ($resX) > 0) {
			echo "Distribuidor: " . pg_fetch_result ($resX,0,codigo_posto) . " - " . pg_fetch_result ($resX,0,nome) ;
		}else{
			echo "Atendimento Direto";
		}
	}
	echo "</td>";

	echo "</TR>";
?>
<tr>
	<td width='650px' align='center'>
	

	<tr>
		<td colspan="5" class='subtitulo'>
			Informações Cadastrais
		</td>
	</tr>
	<?
	//HD 11308 11/1/2008
	if($login_fabrica == 15){?>
	<tr align='left'>
	
		<td>CNPJ/CPF</td>
		<td>I.E.</td>
		<td>I.M.</td>
	</tr>
	<tr align='left'>
	
		<td><input class='frm' type="text" name="cnpj" id="cnpj" size="25" maxlength="18" value="<? echo $cnpj ?>" <? if ($login_fabrica==5) {echo "onKeyUp=\"formata_cnpj(this.value, 'frm_posto')\""; } #HD 14232?> >&nbsp;<a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
		<td><input class='frm' type="text" name="ie" size="20" maxlength="20" value="<? echo $ie ?>" ></td>
		<td><input  class='frm' type='text' name='im' size='40' maxlength='40' value="<? echo $im ?>"></td>
	</tr>

	<tr align='left'>
		
		<td>Fone</td>
		<td>Fax</td>
		<td>Contato</td>
	</tr>
	<tr align='left'>
		
		<td><input class='frm' type="text" name="fone" size="18" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input class='frm' type="text" name="fax" size="18" maxlength="20" value="<? echo $fax ?>"></td>
		<td><input class='frm' type="text" name="contato" size="40" maxlength="30" value="<? echo $contato ?>"></td>
	</tr>
	<?}else{?>
	<tr align='left'>
		
		<td>CNPJ/CPF</td>
		<td>Código</td>
		<td>Razão Social</td>
	</tr>
	<tr align='left'>
		
		<td nowrap>
			<input class='frm' type="text" name="cnpj" id="cnpj" size="18" maxlength="18" value="<? echo $cnpj ?>" <? if ($login_fabrica==5) {echo "onKeyUp=\"formata_cnpj(this.value, 'frm_posto')\""; } #HD 14232?> >&nbsp;
				<a href="#">
					<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')">
				</a>
		</td>
		<td nowrap>
			<input class='frm' type="text" name="codigo" size="10" maxlength="14" value="<? echo $codigo ?>" style="width:150px"<?if(strlen($posto) > 0 and $login_fabrica == 45 AND strlen(trim($codigo)) > 0)  echo " readonly='readonly' ";?>>&nbsp;
				<a href="#">
					<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'codigo')">
					</a>
				</td>
		<td colspan="3" align='left'>
			<input class='frm' type="text" name="nome" size="30" maxlength="60" value="<? if ($login_fabrica == 50) { echo strtoupper($nome); } else { echo $nome; } ?>" />
			&nbsp;	<a href="#">
						<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'nome')">
					</a>
		</td>
	</tr>
	<tr align='left'>
	
		<td colspan='2'>Nome Fantasia</td>
		<td>I.E.</td>
		
	</tr>
	<tr align='left'>
		
		<td colspan='2'>
			<input class='frm' type="text" name="nome_fantasia" style='width: 348px' maxlength="30" value="<? echo $nome_fantasia ?>" >
		</td>
			<td><input class='frm' type="text" name="ie" size="18" maxlength="11" value="<? echo $ie ?>" ></td>
			
	</tr>
	<tr align='left'>
		
		<td>CEP</td>
		<td>Endereço</td>
		<td>Número</td>
		<td>Complemento</td>
	
	</tr>
	<tr align='left'>
		
		<td><input class='frm' type="text" name="cep"    size="10" maxlength="8" value="<? echo $cep ?>" onblur=" buscaCEP(this.value, this.form.endereco, this.form.bairro, this.form.cidade, this.form.estado);"></td>
		<td><input class='frm' type="text" name="endereco" size="16" maxlength="50" value="<? echo $endereco ?>"></td>
		<td><input class='frm' type="text" name="numero" size="5" maxlength="10" value="<? echo $numero ?>"></td>
		<td><input class='frm' type="text" name="complemento" size="5" maxlength="20" value="<? echo $complemento ?>"></td>
	
	</tr>
	<tr align='left'>
		
		<td>Bairro</td>
		<td>Cidade</td>
		<td>Estado</td>
	</tr>
	<tr align='left'>
		
		<td><input class='frm' type="text" name="bairro" size="20" maxlength="20" value="<? echo $bairro ?>"></td>
		<td><input  class='frm'type="text" name="cidade" size="16" maxlength="30" value="<? echo $cidade ?>"></td>
				<?php
			$sql = "SELECT * from tbl_estado where pais = 'BR' AND estado <> 'EX' ORDER BY estado";
			$res = pg_query ( $con, $sql );
			
			
		?>
		
		<td>
			<select class='frm' name="estado" id='estado' />
				<option value=""></option>
		<?php
			for( $i ; $i < pg_num_rows( $res ); $i++ ):
			$uf = pg_fetch_result($res,$i,estado);
			$sel= ($uf == $estado) ? ' SELECTED':'';
		?>
				<option value='<?php echo $estado ?>'<?php echo $sel?>><?php echo $estado ?></option>
		
		<?php
			endfor;
		?>
	</td>
	</tr>
	
	<tr align='left'>
		
		<td>Fone</td>
		<td>Fax</td>
		<td>Contato</td>
		<td>Desconto</td>
		
	</tr>
	<tr align='left'>
			
		
		<td><input class='frm' type="text" name="fone" size="15" maxlength="11" value="<? echo $fone ?>"></td>
		<td><input class='frm' type="text" name="fax" size="15" maxlength="11" value="<? echo $fax ?>"></td>
		<td><input  class='frm'type="text" name="contato" size="30" maxlength="15" value="<? echo $contato?>" style="width:100px"></td>
		<td nowrap><input class='frm' type="text" name="desconto" size="5" maxlength="5" value="<? echo $desconto ?>" >%</td>
		
	</tr>
	<?}
	$colspan = ($login_fabrica == 15) ? 1 : 3;

	?>
	
	<tr align='left'>
		
		<td>Tipo do Posto</td>
		
		<?php
			if ( $login_fabrica != 20 ):
		?>
		<td>País</td>
		<?php endif ?>
		<td>E-mail</td>
		<td>Capital/Interior</td>
		<?if($login_fabrica == 7){?><td>Posto Empresa</td><?}?>
		<!-- <td>PEDIDO EM GARANTIA</td> -->
		<?if($login_fabrica == 20){?><td>ER</td><?}?>
		<?// HD 110541
		if($login_fabrica==11){?>
			<td width = '34%'>Atendimento</td>
		<? } ?>
		<?if($login_fabrica == 50 or $login_fabrica == 52 or $login_fabrica == 72 or $login_fabrica == 24){?><td>Valor/km</td><?}?>
		<? // HD 12104
		if($login_fabrica == 14){ ?>
		<td>Liberar 10%</td>
		<? } ?>
		<? // HD 17601
		if($login_fabrica == 45){ ?>
		<td>Qtde Itens</td>
		<? } ?>
	</tr>
	<tr align='left'>
	
		<td>
			<select class='frm' name='tipo_posto' size='1'>
				<?
					$sql = "SELECT *
							FROM   tbl_tipo_posto
							WHERE  tbl_tipo_posto.fabrica = $login_fabrica
							AND tbl_tipo_posto.ativo = 't'
							ORDER BY tbl_tipo_posto.descricao";
					$res = pg_query ($con,$sql);
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
							echo "<option value='" . pg_fetch_result ($res,$i,tipo_posto) . "' ";
								if ($tipo_posto == pg_fetch_result ($res,$i,tipo_posto)) echo " selected ";
							echo ">";
							echo pg_fetch_result ($res,$i,descricao);
					echo "</option>";
					}
				?>
			</select>
		</td>
		<?php
			if( $login_fabrica != 20 ):
		?>
		
		<td>

		<select name='pais' class='frm'>
		<?	$sql = "SELECT pais, nome
					FROM tbl_pais
					ORDER BY nome";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res)>0){
				echo "<option value=''></option>";
				for($x=0; $x<pg_num_rows($res); $x++){
					$aux_pais = pg_fetch_result($res, $x, pais);
					$nome_pais= pg_fetch_result($res, $x, nome);

					$selected_pais = " ";
					if ($pais == $aux_pais) $selected_pais = " selected ";

					echo "<option value='$aux_pais' $selected_pais>";
					echo $nome_pais;
					echo "</option>";
				}
			}
		?>
		</select>
		</td>
		
		<?php endif; ?>
		
		<td>
			<input class='frm' type="text" name="email" size="15" maxlength="50" value="<? echo $email ?>" />
		</td>
		<td>
			<select class='frm' name='capital_interior' size='1'>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> >Capital</option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> >Interior</option>
			</select>
		</td>
		<? if($login_fabrica==7){
			if(strlen($posto_empresa)>0){
			$sqlx = "SELECT codigo_posto
					FROM tbl_posto_fabrica
					WHERE posto   = $posto_empresa
					AND   fabrica = $login_fabrica";
			$resx = pg_query($con, $sqlx);
			if(pg_num_rows($resx)>0){
				$posto_empresa = pg_fetch_result($resx, 0, codigo_posto);
			}
		   } 
			?>
		<?}?>
		
		<?if($login_fabrica == 20){?>
		<td>
			<select class='frm' name='escritorio_regional'>
				<?
					$sql = "SELECT *
							FROM   tbl_escritorio_regional
							WHERE  fabrica = $login_fabrica
							ORDER BY descricao";
					$res = pg_query ($con,$sql);
						echo "<option value=''></option>";
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
							echo "<option value='" . pg_fetch_result ($res,$i,escritorio_regional) . "' ";
								if ($escritorio_regional == pg_fetch_result ($res,$i,escritorio_regional)) echo " selected ";
							echo ">";
							echo pg_fetch_result ($res,$i,descricao);
					echo "</option>";
					}
				?>
			</select>
		</td>
		<?}?>
<!--
		<td>
			<select name='pedido_em_garantia' size='1'>
				<option value=''></option>
				<option value='t' <? if ($pedido_em_garantia == "t") echo " selected "; ?> >Sim</option>
				<option value='f' <? if ($pedido_em_garantia == "f") echo " selected "; ?> >Não</option>
			</select>
		</td>
 -->
		
		<?// HD 110541
		if($login_fabrica==11){?>
		<td width = '33%'>
			<select name='atendimento_lenoxx'
			<?php
				if (isset($readonly) and strlen($atendimento_lenoxx)>0){
					echo " DISABLED";
				} ?>>
				<option selected></option>
				<option value='b'   <? if ($atendimento_lenoxx == 'b')   echo "selected"; ?>>Balcão</option>
				<option value='r'   <? if ($atendimento_lenoxx == 'r')   echo "selected"; ?>>Revenda</option>
				<option value='t'   <? if ($atendimento_lenoxx == 't')   echo "selected"; ?>>Balcão/Revenda</option>
			</select>
		</td>
		<? } ?>
		<?if($login_fabrica == 50 or $login_fabrica == 52 or $login_fabrica == 72 or $login_fabrica == 24){?>
			<td><input class='frm' type="text" name="valor_km" size="5" maxlength="5" value="<? echo $valor_km?>" ></td>
		<?}?>
		<? // HD 12104
		if($login_fabrica == 14){?>
		<td align='center' nowrap>
		<input type='checkbox' class='frm' name='imprime_os' value='imprime_os' <? if($imprime_os == 't') echo " checked "; ?> />
		</td>
		<? } ?>
		<? // HD 17601
		if($login_fabrica == 45){?>
		<td>
		<input type='text' class='frm' name='qtde_os_item' size='2' maxlength='3' value='<? echo "$qtde_os_item";?>'>
		</td>
		<? } ?>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td colspan="5">
			<input type="button" style="background: url(imagens/teste.gif); width: 250px; height:24px;border: 1px #D9E2EF;" onclick="location.href='<? echo $PHP_SELF ?>?listar=todos#postos'">
		</td>
		
	</tr>

<?

//17/7/2009 MLG
	$colspan = 3;   // Calcula o 'colspan' da tD do "país"
	if ($login_fabrica==2) $colspan--;    //  Um a menos, porque tem 'data nomeação'
	if ($login_fabrica==2) $colspan--;    //  Um a menos, porque tem 'atende consumidor'
?>

<?php if ( hdPermitePostoAbrirChamado() or $login_fabrica == 30 ): ?>
	<tr>
		
		<td class='titulo_coluna' colspan='5'> 
			<? echo ($login_fabrica == 1) ? "Atendente de Callcenter Para este Posto" : "Inspetor para esse posto";?>
		</td>
	</tr>
	<tr>
		<td align="center" colspan='5'> 
		<? echo ($login_fabrica == 1) ? "Selecione o atendente para quem serão gerados os chamados abertos por este posto de atendimento" : "Selecione o inspetor para esse posto";?> </td>
	</tr>
	<tr>
		<td colspan='5' align='center'>
			<?php 
				// ! Buscar atendentes  de posto
				// HD 121248 (augusto)
				$aAtendentes = hdBuscarAtendentes();
			?>
			<select class='frm' name="admin_sap" id="admin_sap">
				<option value=""></option>
				<?php foreach($aAtendentes as $aAtendente): ?>
					<option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
<?php endif; if( $login_fabrica ==20 || $login_fabrica == 2 ): ?>
	<tr>
		<td class='titulo_coluna' colspan='5'><?php ($login_fabrica == 2) ? $frase = 'Nomeação' :  $frase = 'Categoria de Desconto';  echo $frase ?></td>
	</tr>
	<?php
	if( $login_fabrica == 20 ):
	?>
	<tr>
		<td colspan="1">Desconto Acessório</td>
		<td colspan="1">Imposto IVA</td>
		<td colspan="1">Custo Administrativo</td>
		<td>País</td>
		<? endif; if ($login_fabrica==2): 
		/*	hd 21496 - Francisco - campo Data da Nomeação para Dynacom
		    HD 167192- MLG - A Dynacom pode fazer com que o posto não apareça na pesquisa de postos,
							 tanto no Call-Center quanto na web (telecontrol / mapa_rede ...)
			Para as fábricas que querem controlar se aparecem os postos na pesquisa 
	   */?>
		<td>Data Nomeação</td>
		<td>Atende Consumidor</td>
		<?php endif ?>
	</tr>
	<?php
	if( $login_fabrica == 20 ):
	?>
	<tr>
		<td><input class='frm' type="text" name="desconto_acessorio" size="5" maxlength="5" value="<? echo $desconto_acessorio ?>" >%</td>
		<td><input class='frm' type="text" name="imposto_al" size="5" maxlength="5" value="<? echo $imposto_al ?>" >%</td>
		<td><input class='frm' type="text" name="custo_administrativo" size="5" maxlength="5" value="<? echo $custo_administrativo ?>" >%</td>
		<td colspan="<?=$colspan?>">

		<select name='pais' class='frm'>
		<?	$sql = "SELECT pais, nome
					FROM tbl_pais
					ORDER BY nome";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res)>0){
				echo "<option value=''></option>";
				for($x=0; $x<pg_num_rows($res); $x++){
					$aux_pais = pg_fetch_result($res, $x, pais);
					$nome_pais= pg_fetch_result($res, $x, nome);

					$selected_pais = " ";
					if ($pais == $aux_pais) $selected_pais = " selected ";

					echo "<option value='$aux_pais' $selected_pais>";
					echo $nome_pais;
					echo "</option>";
				}
			}
		?>
		</select>
		</td>
		<?php endif; if($login_fabrica==2): ?>
		<td>
		<!-- hd 21496 - Francisco - campo Data da Nomeação para Dynacom -->
		<? include "javascript_calendario.php"; ?>
<script type='text/javascript' src='js/jquery.datePicker.js'></script>
		<script type="text/javascript" charset="utf-8">
			$(function()
			{
				$("input[rel='data_mask']").maskedinput("99/99/9999");
				$("input[@name=data_nomeacao]").datePicker({startDate : "01/01/2000"});
	
			});
		</script>
		<? if($data_nomeacao=='01/01/0001' OR $data_nomeacao=='0001-01-01') {
			$data_nomeacao ="";
		} 
		$atende_consumidor_checked = ($atende_consumidor<>'f')? "CHECKED" :"";
		?>
			<input class='frm' type="text" name="data_nomeacao" rel='data_mask' size="12" maxlength="16"
			  value="<? echo $data_nomeacao ?>" ></td>
			<td><input class='frm' type="checkbox" name="atende_consumidor" <?=$atende_consumidor_checked?>
					  title='Se desmarcar, o posto não irá a aparecer na pesquisa da rede de postos autorizados' />
		</td>
		<?php endif; ?>
	</tr>

<tr><td>&nbsp;</td></tr>
<!--
<tr>
<td>
<center>

<input type='hidden' name='btn_acao' value=''>
<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
</center>
</td></tr> -->
<?php endif; ?>

	<tr align='left'>
	
		<td>Senha</td>
		<td colspan='5'>Transportadora</td>
	</tr>
	<tr align='left'>
	
		<td><input class='frm' type="text" name="senha" size="10" maxlength="10" value="<? echo $senha ?>"></td>
		<td colspan='5'>
			<select class='frm' name="transportadora" width='80px'>
				<option selected></option>
<?
	if($login_fabrica == 11){
		$sql = "SELECT	tbl_transportadora.transportadora        ,
						tbl_transportadora.nome                  ,
						tbl_transportadora.cnpj
				FROM	tbl_transportadora
				JOIN	tbl_transportadora_fabrica USING(transportadora)
				WHERE	tbl_transportadora_fabrica.fabrica = $login_fabrica
				AND		tbl_transportadora_fabrica.ativo  = 't' ";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
				if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
				echo ">";
				echo pg_fetch_result($res,$i,cnpj) ." - ".substr (pg_fetch_result($res,$i,nome),0,25);
				echo "</option>\n";
			}
		}
	}else{
		$sql = "SELECT	tbl_transportadora.transportadora        ,
						tbl_transportadora.nome                  ,
						tbl_transportadora_fabrica.codigo_interno
				FROM	tbl_transportadora
				JOIN	tbl_transportadora_padrao USING(transportadora)
				JOIN	tbl_transportadora_fabrica USING(transportadora)
				WHERE	tbl_transportadora_padrao.fabrica = $login_fabrica
				AND		tbl_transportadora_fabrica.ativo  = 't' ";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
				if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
				echo ">";
				echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
				echo "</option>\n";
			}
		}
	}
?>
			</select>
		</td>
		</tr>
		<tr align='left'>
		
		<td>Região Suframa</td>
		<td>Item Aparência</td>
		
		<? if($senha_financeiro != null): ?>
		<td>Senha do financeiro</td>
		<?php endif; ?>
		
		<? if($senha_tabela_preco != null): ?>
		<td>Senha da Tabela de Preço</td>
		<?php endif; ?>
		
		<td colspan='2'>Divulgar posto para o consumidor?</td>
		
		
	</tr>
	<tr align='left'>
	
		<td>
			Sim<INPUT TYPE="radio" NAME="suframa" VALUE = 't' <?if ($suframa == 't') echo "checked";?>>
			Não<INPUT TYPE="radio" NAME="suframa" VALUE = 'f' <?if ($suframa == 'f' or strlen($suframa) == 0) echo "checked";?>>
		</td>
		<td><acronym title='Esta informação trabalha em conjunto com a informação item de aparência no cadastro de peças. Deixando setado como SIM, este posto vai conseguir lançar peças de item de aparência nas Ordens de Serviço de Revenda.'>
			SIM<INPUT TYPE="radio" NAME="item_aparencia" VALUE = 't' <?if ($item_aparencia == 't') echo "checked";?>>
			NÃO<INPUT TYPE="radio" NAME="item_aparencia" VALUE = 'f' <?if ($item_aparencia <> 't') echo "checked";?>>
			</acronym>
	<?	//5595 link para mostrar os postos que atendem revenda para Tectoy
		if($login_fabrica==6) {
			echo "<BR><a href=\"javascript: posto_revenda('$login_fabrica')\" rel='ajuda' title='Clique aqui para ver os postos de revenda'><font size=1>Listar postos</font></a>";
		}
	?>
		</td>
		
		<?php if( $senha_financeiro != null ): ?>
		<td><?php echo "$senha_financeiro"; ?></td>
		<?php endif ; ?>
		
		
		<?php if( $senha_tabela_preco !=  null ): ?>
		<td><? echo "$senha_tabela_preco"; ?></td>
		<?php endif; ?>
		
		<td colspan='2'>
			<? if (($divulgar_consumidor != 't') && ($divulgar_consumidor != 'f')) $divulgar_consumidor = 't'; ?>
			SIM<INPUT TYPE="radio" NAME="divulgar_consumidor" VALUE = 't' <?if ($divulgar_consumidor == 't') echo "CHECKED";?>>
			NÃO<INPUT TYPE="radio" NAME="divulgar_consumidor" VALUE = 'f' <?if ($divulgar_consumidor == 'f') echo "CHECKED";?>>
		</td>
		
	</tr>
	<tr >
		<td colspan='5' align='center'>Observações</td>
	</tr>
	<tr>
		<td colspan='5' align='center'>
			<textarea class='frm' name="obs" cols="75" rows="2"><? echo $obs ?></textarea>
		</td>
	</tr>


<!--   Cobranca  -->
<tr><td colspan=5 class='subtitulo'>Informações para cobrança</td></tr>
	<!-- Sem a linha abaixo, aparece errado no IE.. ??? -->
	<tr  align='left'>
		<td>Cep</td>
		<td colspan=2>Endereço</td>
		<td>Número</td>
		<td>Complemento</td>
	</tr>
	<tr align='left'>
		<td><input class='frm' type="text" name="cobranca_cep" size="10" maxlength="8" value="<? echo $cobranca_cep ?>" onblur=" buscaCEP(this.value, this.form.cobranca_endereco, this.form.cobranca_bairro, this.form.cobranca_cidade, this.form.cobranca_estado);"></td>
		<td colspan=2><input class='frm' type="text" name="cobranca_endereco" size="40" maxlength="50" value="<? echo $cobranca_endereco ?>"></td>
		<td><input class='frm' type="text" name="cobranca_numero" size="10" maxlength="10" value="<? echo $cobranca_numero ?>"></td>
		<td><input class='frm' type="text" name="cobranca_complemento" size="10" maxlength="20" value="<? echo $cobranca_complemento ?>"></td>
	</tr>
	<tr  align='left'>
		<td colspan=2>Bairro</td>
		<td colspan=2>Cidade</td>
		<td>UF</td>
	</tr>
	<tr align='left'>
		<td colspan=2><input class='frm' type="text" name="cobranca_bairro" size="30" maxlength="20" value="<? echo $cobranca_bairro ?>"></td>
		<td colspan=2><input class='frm' type="text" name="cobranca_cidade" size="30" maxlength="30" value="<? echo $cobranca_cidade ?>"></td>
		<td>
		<select class='frm' name="cobranca_estado" value="<? echo $cobranca_estado ?>">
			<option value=""></option>
		<?php
		
			$sql = "SELECT estado from tbl_estado where pais = 'BR' AND estado <> 'EX' ORDER BY estado";
			$res = pg_query( $con, $sql );
			
			for( $i = 0; $i < pg_num_rows ( $res ); $i++ ):
			$estado = pg_fetch_result($res,$i,estado);
		    $sel    = ($estado == $cobranca_estado) ? ' SELECTED' : '';
		?>
		
			<option value=<?php echo "'$estado'$sel>$estado" ?></option>
		<?php
			endfor;
		?>
		</td>
	</tr>

<?
if ($login_fabrica <> 1 OR $login_login == "fabiola" OR $login_login == "silvania"  OR $login_login == "kliferthi" ) {

# HD 55187
if ($login_fabrica == 45){
	$sqlPriv = "SELECT admin FROM tbl_admin WHERE admin = $login_admin
				AND (privilegios like '%financeiro%' or privilegios like '*')";
	$resPriv = pg_query($con,$sqlPriv);
	if (pg_num_rows($resPriv) == 0){
		$readonly = " READONLY";
	}
}
?>

<tr><td colspan='5' class='subtitulo'>Informações Bancárias</td></tr>
	<tr  align='left'>
	
		<td width = '33%'>CPF/CNPJ Favorecido</td>
		<td colspan='3'>Nome Favorecido</td>
	</tr>
	<tr align='left'>
	
		<td width = '33%'>
		<input class='frm' type="text" name="cpf_conta" size="14" maxlength="19" value="<? echo $cpf_conta ?>"
		<?php
		if (strlen($cpf_conta)>0){
			echo $readonly;
		}
		?>></td>
		<td colspan=3>
		<input class='frm' type="text" name="favorecido_conta" size="47" maxlength="50" value="<? echo $favorecido_conta ?>"
		<?php
		if (strlen($favorecido_conta)>0){
			echo $readonly;
		}
		?>></td>
	</tr>
	<tr  align='left'>
	
		<td colspan='5' width = '100%'>Banco</td>
	</tr>
	<tr align='left'>
	
		<td colspan='5'>
			<?
			$sqlB =	"SELECT codigo, nome
					FROM tbl_banco
					ORDER BY codigo";
			$resB = pg_query($con,$sqlB);
			if (pg_num_rows($resB) > 0) {
				echo "<select class='frm' name='banco' size='1'";
				if (isset($readonly) and strlen($banco)>0){ // HD 85519
					echo " onfocus='defaultValue=this.value' onchange='this.value=defaultValue' ";
				}
				echo ">";
				echo "<option value=''></option>";
				for ($x = 0 ; $x < pg_num_rows($resB) ; $x++) {
					$aux_banco     = pg_fetch_result($resB,$x,codigo);
					$aux_banconome = pg_fetch_result($resB,$x,nome);
					echo "<option value='" . $aux_banco . "'";
					if ($banco == $aux_banco) echo " selected";
					echo ">" . $aux_banco . " - " . $aux_banconome . "</option>";
				}
				echo "</select>";
			}
			?>
		</td>
	</tr>
	<tr  align='left'>
	
		<td width = '33%'>Tipo de Conta</td>
		<td width = '33%'>Agência</td>
		<td width = '34%'>Conta</td>
		<? if($login_fabrica == 45 ){?>
		<td width = '34%'>Operação</td>
		<?}?>
	</tr>
	<tr align='left'>
	
		<td width = '33%'>
			<select class='frm' name='tipo_conta'
			<?php
				if (isset($readonly) and strlen($tipo_conta)>0){
					echo " DISABLED";
				} ?>>
				<option selected></option>
				<option value='Conta conjunta'   <? if ($tipo_conta == 'Conta conjunta')   echo "selected"; ?>>Conta conjunta</option>
				<option value='Conta corrente'   <? if ($tipo_conta == 'Conta corrente')   echo "selected"; ?>>Conta corrente</option>
				<option value='Conta individual' <? if ($tipo_conta == 'Conta individual') echo "selected"; ?>>Conta individual</option>
				<option value='Conta jurídica'   <? if ($tipo_conta == 'Conta jurídica')   echo "selected"; ?>>Conta jurídica</option>
				<option value='Conta poupança'   <? if ($tipo_conta == 'Conta poupança')   echo "selected"; ?>>Conta poupança</option>
			</select>
		</td>
		<td width = '33%'>
		<input  class='frm' type="text" name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>"
		<?php
		if (strlen($agencia)>0){
			echo $readonly;
		}
		?>></td>
		<td width = '34%'>
		<input class='frm' type="text" name="conta" size="15" maxlength="15" value="<? echo $conta ?>"
		<?php
		if (strlen($conta)>0){
			echo $readonly;
		}
		?>></td>
		<? if($login_fabrica == 45 ){?>
		<td width = '34%'>
		<input class='frm' type="text" name="conta_operacao" size="5" maxlength="3" value="<? echo $conta_operacao ?>"
		<?php
		if (strlen($conta_operacao)>0){
			echo $readonly;
		}
		?>></td>
		<?}?>
	</tr>
	<tr >
		<td colspan="5">Observações</td>
	</tr>
	<tr>
		<td colspan="5">
			<textarea class='frm' name="obs_conta" cols="75" rows="2"
			<?php
			if (strlen($obs_conta)>0){
				echo $readonly;
			}?>><? echo $obs_conta; ?></textarea>
		</td>
	</tr>
<? }else{ ?>
<input type="hidden" name="cpf_conta" value="<? echo $cpf_conta ?>">
<input type="hidden" name="favorecido_conta" value="<? echo $favorecido_conta ?>">
<input type="hidden" name="banco" value="<? echo $banco ?>">
<input type="hidden" name="nomebanco" value="<? echo $nomebanco ?>">
<input type="hidden" name="tipo_conta" value="<? echo $tipo_conta ?>">
<input type="hidden" name="agencia" value="<? echo $agencia ?>">
<input type="hidden" name="conta" value="<? echo $conta ?>">
<input type="hidden" name="obs_conta" value="<? echo $obs_conta ?>">
<? } ?>

</tr>

<?
if($login_fabrica == 20 AND strlen($posto)>0) {
	$sql = "SELECT tabela FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
	$resX = @pg_query ($con,$sql);
	$tabela =  @pg_fetch_result($resX,0,tabela);

	$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
	$resX = pg_query ($con,$sql);

	echo "<select class='frm' name='tabela_unica'>\n";
	echo "<option selected></option>\n";

	for($x=0; $x < pg_num_rows($resX); $x++){
		$check = "";
		if ($tabela == pg_fetch_result($resX,$x,tabela)) $check = " selected ";
		echo "<option value='".pg_fetch_result($resX,$x,tabela)."' $check>".pg_fetch_result($resX,$x,sigla_tabela)."</option>";
	}

	echo "</select>\n";
}

if (strlen ($posto) > 0 AND $login_fabrica <> 20) {
		echo "<table class='formulario' width='700px' align='center'><tr>
			<td class='subtitulo' colspan='5'>
				<!-- criar imagem com texto referente a linha e tabela -->
				Linhas e Tabelas
			</td>
		</tr>";
?>

	<TR class='titulo_coluna'>
	<? if ($login_fabrica <> 14) { ?>
	<TD>Linha</TD>
	<? } else { ?>
	<TD>Família</TD>
	<? } ?>
	<td width='80px'>Atende</td>
	<? if ($login_fabrica <> 40) { ?>
	<TD>Tabela</TD>
	<? } else { ?>
	<TD>Tabela Garantia</TD>
	<TD>Tabela Faturada</TD>
	<? } ?>
	<TD>Desconto</TD>
	<TD>Distribuidor</TD>
	</tr>
<?
	if ($login_fabrica <> 14) {
		$sql = "SELECT  tbl_linha.linha,
						tbl_linha.nome
				FROM	tbl_linha
				WHERE	ativo = 't' and tbl_linha.fabrica = $login_fabrica ";
		$res = pg_query ($con,$sql);

		for($i=0; $i < pg_num_rows($res); $i++){
			$linha = pg_fetch_result ($res,$i,linha);
			$check = "";
			$tabela = "" ;
			$desconto = "";
			$distribuidor = "";

			$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
			if($login_fabrica == 2) $sql = "SELECT DISTINCT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
			$resX = pg_query ($con,$sql);

			if (pg_num_rows ($resX) == 1) {
				$check        = " CHECKED ";
				$tabela       = pg_fetch_result ($resX,0,tabela);
				$desconto     = pg_fetch_result ($resX,0,desconto);
				$distribuidor = pg_fetch_result ($resX,0,distribuidor);
				$tabela_posto = pg_fetch_result ($resX,0,tabela_posto);
			}

			if (pg_num_rows ($resX) > 1) {
				echo "<h1> ERRO NAS LINHAS, AVISE TELECONTROL </h1>";
				exit;
			}

			if (strlen ($msg_erro) > 0) {
				$atende       = $_POST ['atende_'       . $linha] ;
				$tabela       = $_POST ['tabela_'       . $linha] ;
				$tabela_posto = $_POST ['tabela_posto_' . $linha] ;
				$desconto     = $_POST ['desconto_'     . $linha] ;
				$distribuidor = $_POST ['distribuidor_' . $linha] ;
				if (strlen ($atende) > 0 ) $check = " CHECKED ";
			}
			echo "<tr align='center'>";

			echo "<td align='left'>" . pg_fetch_result ($res,$i,nome) . "</td>";
			echo "<td><input type='checkbox' name='atende_$linha' value='$linha' '$check' /></td>";
			echo "<td>";

			if($login_fabrica == 6){
				$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica and ativa ORDER BY sigla_tabela";
				$resX = pg_query ($con,$sql);

				echo "<select class='frm' style='width: 85px' name='tabela_$linha'>\n";
				echo "<option selected></option>\n";

				for($x=0; $x < pg_num_rows($resX); $x++){
					$check = "";
					if ($tabela == pg_fetch_result($resX,$x,tabela)) $check = " selected ";
					echo "<option value='".pg_fetch_result($resX,$x,tabela)."' $check>".pg_fetch_result($resX,$x,descricao)."</option>";
				}
			}else{

				$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
				$resX = pg_query ($con,$sql);

				echo "<select class='frm' style='width: 85px' name='tabela_$linha'>\n";
				echo "<option selected></option>\n";

				for($x=0; $x < pg_num_rows($resX); $x++){
					$check = "";
					if ($tabela == pg_fetch_result($resX,$x,tabela)) $check = " selected ";
					echo "<option value='".pg_fetch_result($resX,$x,tabela)."' $check>".pg_fetch_result($resX,$x,sigla_tabela)."</option>";
				}
			}
			echo "</select>\n";
			echo "</td>";

			if($login_fabrica == 40) { # HD 212563
				echo "<td align='left'>";
				$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
				$resX = pg_query ($con,$sql);

				echo "<select class='frm' name='tabela_posto_$linha'>\n";
				echo "<option selected></option>\n";

				for($x=0; $x < pg_num_rows($resX); $x++){
					$check = "";
					if ($tabela_posto == pg_fetch_result($resX,$x,tabela)) $check = " selected ";
					echo "<option value='".pg_fetch_result($resX,$x,tabela)."' $check>".pg_fetch_result($resX,$x,sigla_tabela)."</option>";
				}
				echo "</select>\n";
				echo "</td>";
			}

			echo "<script type='text/javascript'>
						$(document).ready( function(){
									$(\"input[@name=desconto_$linha]\").numeric();
							} );
				  </script>";
			echo "<td><input class='frm' type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
			echo "<td align='center'>";

			$sql = "SELECT  tbl_posto.posto   ,
							tbl_posto.nome_fantasia,
							tbl_posto.nome
					FROM    tbl_posto
					JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN    tbl_tipo_posto       ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
					WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
					AND     tbl_tipo_posto.distribuidor is true
					ORDER BY tbl_posto.nome_fantasia";
			$resX = pg_query ($con,$sql);

			echo "<select style='width: 320px' class='frm' name='distribuidor_$linha'>\n";
			echo "<option ></option>\n";

			for($x = 0; $x < pg_num_rows($resX); $x++) {
				$check = "";
				if ($distribuidor == pg_fetch_result($resX,$x,posto)) $check = " selected ";
				$fantasia = pg_fetch_result ($resX,$x,nome_fantasia) ;
				if (strlen (trim ($fantasia)) == 0) $fantasia = pg_fetch_result ($resX,$x,nome) ;
				echo "<option value='".pg_fetch_result($resX,$x,posto)."' $check>$fantasia</option>";
			}

			echo "</select>\n";
			echo "</td>";
			echo "</tr>";
		}
	}else{
		$sql = "SELECT  tbl_familia.familia,
						tbl_familia.descricao
				FROM	tbl_familia
				WHERE	tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$res = pg_query ($con,$sql);

		for($i=0; $i < pg_num_rows($res); $i++){
			$familia       = pg_fetch_result ($res,$i,familia);
			$check         = "";
			$tabela        = "" ;
			$desconto      = "";
			$distribuidor  = "";

			$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
			$resX = pg_query ($con,$sql);

			if (pg_num_rows ($resX) == 1) {
				$check        = " CHECKED ";
				$tabela       = pg_fetch_result ($resX,0,'tabela');
				$desconto     = pg_fetch_result ($resX,0,'desconto');
				$distribuidor = pg_fetch_result ($resX,0,'distribuidor');
			}

			if (pg_num_rows ($resX) > 1) {
				echo "<h1> ERRO NAS FAMÍLIAS, AVISE TELECONTROL </h1>";
				exit;
			}

			if (strlen ($msg_erro) > 0) {
				$atende       = $_POST ['atende_'       . $familia] ;
				$tabela       = $_POST ['tabela_'       . $familia] ;
				$desconto     = $_POST ['desconto_'     . $familia] ;
				$distribuidor = $_POST ['distribuidor_' . $familia] ;
				if (strlen ($atende) > 0 ) $check = " CHECKED ";
			}
			echo "<tr>";

			echo "<td nowrap>" . pg_fetch_result ($res,$i,descricao) . "</td>";
			echo "<td><input type='checkbox' name='atende_$familia' value='$familia' '$check' /></td>";

			echo "<td align='left'>";

			$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
			$resX = pg_query ($con,$sql);

			echo "<select class='frm' style='width: 85px' name='tabela_$familia'>\n";
			echo "<option selected></option>\n";

			for($x=0; $x < pg_num_rows($resX); $x++){
				$check = "";
				if ($tabela == pg_fetch_result($resX,$x,tabela)) $check = " selected ";
				echo "<option value='".pg_fetch_result($resX,$x,tabela)."' $check>".pg_fetch_result($resX,$x,sigla_tabela)."</option>";
			}

			echo "</select>\n";
			echo "</td>";
			echo "<td><input class='frm' type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto' />%</td>";
			echo "<td align='center'>";

			$sql = "SELECT  tbl_posto.posto   ,
							tbl_posto.nome_fantasia,
							tbl_posto.nome
					FROM    tbl_posto
					JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
												AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN    tbl_tipo_posto       ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
					WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
					AND     tbl_posto_fabrica.posto    <> 7214
					AND     tbl_tipo_posto.distribuidor is true
					ORDER BY tbl_posto.nome_fantasia";
			$resX = pg_query ($con,$sql);

			echo "<select class='frm' style='width: 320px' name='distribuidor_$familia' disabled>";
			echo "<option > </option>\n";

			for($x = 0; $x < pg_num_rows($resX); $x++) {
				$check = "";
				if ($distribuidor == pg_fetch_result($resX,$x,posto)) $check = " selected ";
				$fantasia = pg_fetch_result ($resX,$x,nome_fantasia) ;
				if (strlen (trim ($fantasia)) == 0) $fantasia = pg_fetch_result ($resX,$x,nome) ;
				echo "<option value='".pg_fetch_result($resX,$x,posto)."' $check>$fantasia</option>";
			}

			echo "</select>\n";
			echo "</td>";

			echo "</tr></table>";
		}
	}
}


	?>
	</TD>
	</TR>
<tr>
</td>
</tr>
</tr>
<TR align='left'>
	<TD colspan='5' class='subtitulo' >Posto pode Digitar:</TD>
</TR>
<TR>
	<TD><INPUT TYPE="checkbox" NAME="pedido_faturado" VALUE='t' <? if ($pedido_faturado == 't') echo ' checked ' ?> /></TD>
	<TD colspan='4' align='left'>Pedido Faturado (Manual)</TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia" VALUE='t' <? if ($pedido_em_garantia == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> /></TD>
	<TD align='left' colspan='4'>Pedido em Garantia (Manual)</TD>
</TR>

<? if ($login_fabrica == 1) {
	if($posto){
		$sql = "SELECT visivel FROM tbl_posto_condicao WHERE condicao = 62 AND posto = $posto";
//echo $sql;
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0){
			$pedido_em_garantia_finalidades_diversas = pg_fetch_result ($res,0,visivel);
		}
	}
?>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia_finalidades_diversas" VALUE='t' <? if ($pedido_em_garantia_finalidades_diversas == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> /></TD>
	<TD align='left' colspan='4'>Pedido de Garantia ( Finalidades Diversas )</TD>
</TR>

<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="coleta_peca" VALUE='t' <? if ($coleta_peca == 't') echo 'checked' ?> /></TD>
	<TD align='left' colspan='4'>Coleta de Peças</TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="reembolso_peca_estoque" VALUE='t' <? if ($reembolso_peca_estoque == 't') echo 'checked' ?> <? if ($login_fabrica == 3) echo " disabled " ?> /></TD>
	<TD align='left' colspan='4'>Reembolso de Peça do Estoque ( Garantia Automática )</TD>
</TR>
<? } ?>
<? if ($login_fabrica == 6 or $login_fabrica==24 or $login_fabrica==81){ ?>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="garantia_antecipada" VALUE='t' <? if ($garantia_antecipada == 't'){ echo ' checked ';} ?> /></TD>
	<TD align='left' colspan='4'>Pedido em Garantia Antecipada</TD>
</TR>
<? } ?>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="digita_os" VALUE='t' <? if ($digita_os == 't') echo ' checked ' ?> /></TD>
	<TD align='left' colspan='4'>Digita OS
	<?
	if($login_fabrica==11 and strlen($posto)>0){
		if($digita_os<>"t"){
			echo "<font color='red'><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Posto Bloqueado Para digitar OS.</b></font>";
		}
	}
	?>
	</TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico" VALUE='t' <? if ($prestacao_servico == 't') echo ' checked ' ?>  <? if ($login_fabrica == 3) echo " disabled " ?>  /></TD>
	<TD align='left' colspan='4'>Prestação de Serviço<br><font size='-2'>&nbsp;Posto só recebe mão-de-obra. Peças são enviadas sem custo.</font></TD>
</TR>
<TR>
	<TD align='center'>
	<INPUT TYPE="checkbox" disabled NAME="pedido_via_distribuidor" VALUE='t'

	<?
	   if(strlen($posto) >0) {
		if ($pedido_via_distribuidor == 't') echo ' checked '; else echo '';
		$sql = "SELECT		tbl_tipo_posto.distribuidor
				FROM		tbl_tipo_posto
				LEFT JOIN	tbl_posto_fabrica USING (tipo_posto)
				WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
				AND         tbl_posto_fabrica.posto = $posto;";
		$res = pg_query ($con,$sql);

		if (@pg_fetch_result($res,0,0) == 't') echo ''; else echo 'disabled';
	}
	?>
	<? if ($login_fabrica == 3) echo " disabled " ?>
	/>
	</TD>
	<TD align='left' colspan='4'>PEDIDO VIA DISTRIBUIDOR</TD>
	</TR>

<? if ($login_fabrica == 1){ ?>
<TR>
	<TD align='center'>
	<?
	#HD 23738
	if ($condicao_escolhida == 'f'){
		$msg_bloqueio_condicao =" onClick='this.checked = !this.checked; alert(\"Posto já selecionou a condição de pagamento.\")' ";
	}
	if ($condicao_escolhida == 't'){
		$msg_bloqueio_condicao      = " disabled  ";
		$msg_bloqueio_condicao_desc = " <br><font size='-2'>Posto já escolheu a Condição de Pagamento</font>  ";
	}

	?>
	<INPUT TYPE="checkbox" NAME="escolhe_condicao" VALUE='t' <? if ($escolhe_condicao == 't'){ echo ' checked ';} ?> <?=$msg_bloqueio_condicao?> />
	</TD>
	<TD align='left' colspan='4'>ESCOLHE CONDIÇÃO DE PAGAMENTO
		<?
		echo $msg_bloqueio_condicao_desc;

		if ($escolhe_condicao == 't'){
			if ($condicao_escolhida == ''){
				echo "<br><font size='-2'>Posto não escolheu a condição de pagamento</b></font>";
			}else{
				$sql = "SELECT tbl_black_posto_condicao.condicao
						FROM tbl_black_posto_condicao
						JOIN tbl_condicao ON tbl_condicao.condicao  = tbl_black_posto_condicao.id_condicao
						WHERE tbl_black_posto_condicao.posto = $posto
						AND   tbl_condicao.fabrica           = $login_fabrica
						AND   tbl_condicao.promocao          IS NOT TRUE ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$nome_condicao_escolhida = pg_fetch_result($res,0,0);
					if ($condicao_escolhida == 'f'){
						echo "<br><font size='-2'>Condição de Pagamento escolhida: <b>$nome_condicao_escolhida</b></font>";
						echo "&nbsp;&nbsp;&nbsp;Liberar ";
						echo "<INPUT TYPE='checkbox' NAME='condicao_liberada' VALUE='t' />";
					}else{
						echo "<br><font size='-2'>Condição de Pagamento escolhida: <b>$nome_condicao_escolhida</b></font>";
					}
				}
			}
		}
		?>
	</TD>
</TR>
<? } ?>

<? if ($login_fabrica == 19){ ?>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="atende_comgas" VALUE='t' <? if ($atende_comgas == 't'){ echo ' checked ';} ?> /></TD>
	<TD align='left' colspan='4'>Atend.Comgás<br><font size='-2'>&nbsp;Posto pode digitar OS Comgás.</font></TD>
</TR>
<? } ?>
<? if ($login_fabrica == 20){ # HD 85632?>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico_sem_mo" VALUE='t' <? if ($prestacao_servico_sem_mo == 't'){ echo ' checked ';} ?> /></TD>
	<TD align='left' colspan='4'>PRESTAÇÃO DE SERVIÇO ISENTA DE MO<br><font size='-2'>&nbsp;Posto só recebe valor das peças. Mão-de-obra não será cobrada.</font></TD>
</TR>
<? } ?>
<?
if ($login_fabrica == 50 and strlen($posto) > 0) {
	$sql = "SELECT * FROM tbl_posto_fabrica_foto WHERE posto = $posto and fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$posto_fabrica_foto     = pg_fetch_result($res,0,posto_fabrica_foto);

		$caminho_foto_posto     = pg_fetch_result($res,0,foto_posto);
		$caminho_thumb_posto    = pg_fetch_result($res,0,foto_posto_thumb);
		$descricao_foto_posto   = pg_fetch_result($res,0,foto_posto_descricao);

		$caminho_foto_contato1   = pg_fetch_result($res,0,foto_contato1);
		$caminho_thumb_contato1  = pg_fetch_result($res,0,foto_contato1_thumb);
		$descricao_foto_contato1 = pg_fetch_result($res,0,foto_contato1_descricao);

		$caminho_foto_contato2   = pg_fetch_result($res,0,foto_contato2);
		$caminho_thumb_contato2  = pg_fetch_result($res,0,foto_contato2_thumb);
		$descricao_foto_contato2 = pg_fetch_result($res,0,foto_contato2_descricao);
	}

		echo "<tr>";
			echo "<td colspan='5'><img src='imagens/cab_fotosposto.gif'></td>";
		echo "</tr>";

		echo "<tr>";
			echo "<td width='216'>Posto</td>";
			echo "<td width='216'>Contato 1</td>";
			echo "<td width='216'>Contato 2</td>";
		echo "</tr>";

		echo "<tr>";
			echo "<td>";
				if (strlen($caminho_foto_posto) > 0) {
					$image = $caminho_foto_posto;
					$size = getimagesize("$image");
					$height = $size[1];
					$width  = $size[0];
					echo "<IMG SRC='$caminho_thumb_posto' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_posto','Posto','status=no,scrollbars=no,width=$width,height=$height');\">";
					echo "<BR>$descricao_foto_posto";
					echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_posto'\">";
					echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
				} else {
					echo "<B>Selecione a imagem (jpg,gif,png):</B><BR><input class='frm' type='file' value='Procurar foto' name='foto_posto' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
					echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_posto' maxlength='100' name='descricao_foto_posto'>";
				}
			echo "</td>";
			echo "<td>";
				if (strlen($caminho_foto_contato1) > 0) {
					$image = $caminho_foto_contato1;
					$size = getimagesize("$image");
					$height = $size[1];
					$width  = $size[0];
					echo "<IMG SRC='$caminho_thumb_contato1' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_contato1','Contato','status=yes,scrollbars=no,width=$width,height=$height');\">";
					echo "<BR>$descricao_foto_contato1";
					echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_contato1'\">";
					echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
				} else {
					echo "<B>Selecione a imagem (jpg,gif,png):</B><BR><input  class='frm' type='file' value='Procurar foto' name='foto_contato1' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
					echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_contato1' maxlength='100' name='descricao_foto_contato1'>";
				}
			echo "</td>";
			echo "<td>";
				if (strlen($caminho_foto_contato2) > 0) {
					$image = $caminho_foto_contato2;
					$size = getimagesize("$image");
					$height = $size[1];
					$width  = $size[0];
					echo "<IMG SRC='$caminho_thumb_contato2' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_contato2','Contato','status=yes,scrollbars=no,width=$width,height=$height');\">";
					echo "<BR>$descricao_foto_contato2";
					echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_contato2'\">";
					echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
				} else {
					echo "<B>Selecione a imagem (jpg,gif,png):</B><BR><input class='frm' type='file' value='Procurar foto' name='foto_contato2' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
					echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_contato2' maxlength='100' name='descricao_foto_contato2'>";
				}
			echo "</td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td colspan='3'><FONT color='#B1B1B1' size='1'>Clique sobre a imagem para ampliar</font></td>";
		echo "</tr>";
	echo "</table>";
}
?>


<?
if (strlen($data_alteracao) > 0 AND strlen($admin) > 0){
?>
<br>
<tr>
	<td >Última alteração:</td>
	<td>Em: <? echo $data_alteracao; ?></td>
	<td>Usuário:  <?
	$sql = "SELECT login,fabrica FROM tbl_admin WHERE (fabrica = $login_fabrica OR fabrica=10) AND admin = $admin";
	$res = pg_query($con,$sql);

	echo pg_fetch_result($res,0,login);
	if(pg_fetch_result($res,0,fabrica)==10)echo " <font size='1'>(Telecontrol)</font>";
	?></td>
</tr>
<br>
<?
}
?>
<tr><td>&nbsp;</td></tr>
<tr>
	<td colspan='5'>
<a name="postos">
<br>
<center>
<input type='hidden' name='btn_acao' value=''>
<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<!-- img src='imagens_admin/btn_apagar.gif' style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { if(confirm('Deseja realmente DESCREDENCIAR este POSTO?') == true) { document.frm_posto.btn_acao.value='descredenciar'; document.frm_posto.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Ordem de Serviço" border='0' -->
<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
</center>
</a>
<br>
</td></tr>
<!-- ============================ Botoes de Acao ========================= -->

</form>

<tr>
<? if (strlen($posto) > 0) {
	?>
	<tr>
		<td colspan='5'><a href='javascript: alert("Atenção, irá abrir uma nova janela para que se trabalhe como se fosse este posto ! " + document.frm_posto.codigo.value); document.frm_login.login.value = document.frm_posto.codigo.value ; document.frm_login.senha.value = document.frm_posto.senha.value ; document.frm_login.submit() ; document.location = "<? echo $PHP_SELF ?>";'><img src="imagens/btn_comoestepostonovo.gif" alt="Clique Aqui para acessar como se fosse este POSTO"></a></td>
	</tr>
<? } ?>

<? // <form name="frm_login" method="post" target="_blank" action="../index.php"> ?>
<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>
<input type="hidden" name="login">
<input type="hidden" name="senha">
<input type="hidden" name="btnAcao" value="Enviar">
</form>
<?php

if ($_GET ['listar'] == 'todos') {

	// gera nome xls
	if ($login_fabrica == 3) {

		$data = date ("d-m-Y-H-i");

		$arquivo_nome = "relatorio_todos_postos-$data.xls";
		$path         = "/www/assist/www/admin/xls/";
		$path_tmp     = "/tmp/assist/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo_tmp.zip `;
		echo `rm $arquivo_completo.zip `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");
		fputs ($fp, "NOME \t CÓDIGO \t TIPO \t CREDENCIAMENTO \t PEDIDO FATURADO \t PEDIDO EM GARANTIA \t DIGITA OS \t PRESTAÇÃO DE SERVIÇO \t PEDIDO VIA DISTRIBUIDOR \t CNPJ/CPF \t I.E. \t FONE \t FAX \t CONTATO \t ENDEREÇO \t NÚMERO \t COMPLEMENTO \t BAIRRO \t CEP \t CIDADE \t  ESTADO \t E-MAIL \r\n");

	}
	// fim gera nome xls

	$sql = "SELECT	tbl_posto.posto                           ,
					tbl_posto.cnpj   						  ,
					tbl_posto.contato                         ,
					tbl_posto.ie  		                      ,
					tbl_posto_fabrica.contato_cidade  as cidade        ,
					tbl_posto_fabrica.contato_estado  as estado       ,
					tbl_posto_fabrica.contato_endereco       AS endereco,
					tbl_posto_fabrica.contato_numero         AS numero,
					tbl_posto_fabrica.contato_complemento    AS complemento,
					tbl_posto_fabrica.contato_bairro         AS bairro,
					tbl_posto_fabrica.contato_cep            AS cep,
					tbl_posto_fabrica.contato_email          AS email,
					tbl_posto_fabrica.contato_fone_comercial AS fone,
					tbl_posto_fabrica.contato_fax            AS fax,
					tbl_posto.nome                            ,
					tbl_posto.pais                            ,
					tbl_posto_fabrica.codigo_posto            ,
					tbl_tipo_posto.descricao                  ,
					tbl_posto_fabrica.pedido_faturado         ,
					tbl_posto_fabrica.pedido_em_garantia      ,
					tbl_posto_fabrica.coleta_peca             ,
					tbl_posto_fabrica.reembolso_peca_estoque  ,
					tbl_posto_fabrica.digita_os               ,
					tbl_posto_fabrica.prestacao_servico       ,
					tbl_posto_fabrica.prestacao_servico_sem_mo,
					tbl_posto_fabrica.pedido_via_distribuidor ,
					tbl_posto_fabrica.credenciamento          ,
					to_char(tbl_posto_fabrica.contrato,'DD/MM/YYYY HH24:MI')    as contrato,
					to_char(tbl_posto_fabrica.atualizacao,'DD/MM/YYYY HH24:MI') as atualizacao
			FROM	tbl_posto
			JOIN	tbl_posto_fabrica USING (posto)
			LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			LEFT JOIN tbl_empresa_cliente ON tbl_posto.posto = tbl_empresa_cliente.posto AND tbl_empresa_cliente.fabrica = tbl_posto_fabrica.fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica";

	if ($login_fabrica == 20) {
		if ($login_admin == (590) OR $login_admin == (364) OR $login_admin == (588)) $sql .= " AND 1 = 1 ";
		else $sql .= "AND tbl_posto.pais = 'BR'";
		$sql .=" ORDER BY tbl_posto.pais,tbl_posto_fabrica.credenciamento, tbl_posto.nome";
	} else {
		$sql .=" ORDER BY tbl_posto_fabrica.credenciamento, tbl_posto.nome";
	}

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {

		echo "<table border='0' cellpadding='1' cellspacing='0' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				if ($login_fabrica == 20) {
					echo "<td nowrap rowspan='2'>País</td>";
				}
				echo "<td nowrap rowspan='2'>Cidade</td>";
				echo "<td nowrap rowspan='2'>Estado</td>";
				echo "<td nowrap rowspan='2'>Nome</td>";
				echo "<td nowrap rowspan='2'>Código</td>";
				if ($login_fabrica == 15) {
					echo "<td nowrap rowspan='2'>I.E.</td>";
				}
				echo "<td nowrap rowspan='2'>Tipo</td>";
				echo "<td nowrap rowspan='2'>Credenciamento</td>";
				if ($login_fabrica == 15) {
					echo "<td nowrap rowspan='2'>Data Atualização</td>";
				}
				if ($login_fabrica == 25 OR $login_fabrica == 47 OR $login_fabrica == 81) {
					echo "<td nowrap rowspan='2'>Data Contrato</td>";
				}
				echo "<td nowrap colspan='5'>Posto pode Digitar</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td>Pedido Faturado</td>";
				echo "<td>Pedido em Garantia</td>";
				if ($login_fabrica == 1) {
					echo "<td>Coleta de Peças</td>";
					echo "<td>Reembolso de Peça do Estoque</td>";
				}
				echo "<td>Digita OS</td>";
				echo "<td>Prestação de Serviço</td>";
				if($login_fabrica == 20) echo "<td>Prestação de Serviço Isenta de MO</td>";
				echo "<td>Pedido via Distribuidor</td>";
				echo "</tr>";
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

			$posto = pg_fetch_result($res,$i,posto);

			// conteudo excel
			if ($login_fabrica == 3) {

				$pedido_faturado         = (pg_fetch_result($res, $i, 'pedido_faturado') =='t')         ? "Sim" : "Não";
				$pedido_em_garantia      = (pg_fetch_result($res, $i, 'pedido_em_garantia') =='t')      ? "Sim" : "Não";
				$digita_os               = (pg_fetch_result($res, $i, 'digita_os') =='t')               ? "Sim" : "Não";
				$prestacao_servico       = (pg_fetch_result($res, $i, 'prestacao_servico') =='t')       ? "Sim" : "Não";
				$pedido_via_distribuidor = (pg_fetch_result($res, $i, 'pedido_via_distribuidor') =='t') ? "Sim" : "Não";

				fputs($fp,pg_fetch_result($res,$i,'nome')."\t");
				fputs($fp,pg_fetch_result($res,$i,'codigo_posto')."\t");
				fputs($fp,pg_fetch_result($res,$i,'descricao')."\t");
				fputs($fp,pg_fetch_result($res,$i,'credenciamento')."\t");
				fputs($fp,$pedido_faturado."\t");
				fputs($fp,$pedido_em_garantia."\t");
				fputs($fp,$digita_os."\t");
				fputs($fp,$prestacao_servico."\t");
				fputs($fp,$pedido_via_distribuidor."\t");
				fputs($fp,pg_fetch_result($res,$i,'cnpj')."\t");
				fputs($fp,pg_fetch_result($res,$i,'ie')."\t");
				fputs($fp,pg_fetch_result($res,$i,'fone')."\t");
				fputs($fp,pg_fetch_result($res,$i,'fax')."\t");
				fputs($fp,pg_fetch_result($res,$i,'contato')."\t");
				fputs($fp,pg_fetch_result($res,$i,'endereco')."\t");
				fputs($fp,pg_fetch_result($res,$i,'numero')."\t");
				fputs($fp,pg_fetch_result($res,$i,'complemento')."\t");
				fputs($fp,pg_fetch_result($res,$i,'bairro')."\t");
				fputs($fp,pg_fetch_result($res,$i,'cep')."\t");
				fputs($fp,pg_fetch_result($res,$i,'cidade')."\t");
				fputs($fp,pg_fetch_result($res,$i,'estado')."\t");
				fputs($fp,pg_fetch_result($res,$i,'email')."\t");
				fputs($fp,"\r\n");

			}
			// fim  conteudo excel

			/*Retira todos usuários do TIME*/
			$sql = "SELECT *
					FROM  tbl_empresa_cliente
					WHERE posto   = $posto
					AND   fabrica = $login_fabrica";
			$res2 = pg_query ($con,$sql);
			if (pg_num_rows($res2) > 0) continue;
			$sql = "SELECT *
					FROM  tbl_empresa_fornecedor
					WHERE posto   = $posto
					AND   fabrica = $login_fabrica";
			$res2 = pg_query ($con,$sql);
			if (pg_num_rows($res2) > 0) continue;

			$sql = "SELECT *
					FROM  tbl_erp_login
					WHERE posto   = $posto
					AND   fabrica = $login_fabrica";
			$res2 = pg_query ($con,$sql);
			if (pg_num_rows($res2) > 0) continue;

			$x = ($login_fabrica==3) ? $i : $i % 20;

/*
			Estava repetindo os campos aleatoriamente, por isso resolvi tirar de dentro do loop. HD 268395
			if ($x == 0) {
				flush();
				echo "<tr class='titulo_coluna'>";
				if ($login_fabrica == 20) {
					echo "<td nowrap rowspan='2'>País</td>";
				}
				echo "<td nowrap rowspan='2'>Cidade</td>";
				echo "<td nowrap rowspan='2'>Estado</td>";
				echo "<td nowrap rowspan='2'>Nome</td>";
				echo "<td nowrap rowspan='2'>Código</td>";
				if ($login_fabrica == 15) {
					echo "<td nowrap rowspan='2'>I.E.</td>";
				}
				echo "<td nowrap rowspan='2'>Tipo</td>";
				echo "<td nowrap rowspan='2'>Credenciamento</td>";
				if ($login_fabrica == 15) {
					echo "<td nowrap rowspan='2'>Data Atualização</td>";
				}
				if ($login_fabrica == 25 OR $login_fabrica == 47 OR $login_fabrica == 81) {
					echo "<td nowrap rowspan='2'>Data Contrato</td>";
				}
				echo "<td nowrap colspan='7'>Posto pode Digitar</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td>Pedido Faturado</td>";
				echo "<td>Pedido em Garantia</td>";
				if ($login_fabrica == 1) {
					echo "<td>Coleta de Peças</td>";
					echo "<td>Reembolso de Peça do Estoque</td>";
				}
				echo "<td>Digita OS</td>";
				echo "<td>Prestação de Serviço</td>";
				if($login_fabrica == 20) echo "<td>Prestação de Serviço Isenta de MO</td>";
				echo "<td>Pedido via Distribuidor</td>";
				echo "</tr>";
			}
*/

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";

			if ($login_fabrica == 20) {
				echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'pais') . "</td>";
			}
			echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'cidade') . "</td>";
			echo "<td nowrap>" . pg_fetch_result($res,$i,'estado') . "</td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?posto=" . pg_fetch_result($res,$i,'posto') . "'>" . pg_fetch_result($res,$i,'nome') . "</a></td>";
			echo "<td nowrap>" . pg_fetch_result($res,$i,'codigo_posto') . "</td>";
			if ($login_fabrica == 15) {
				echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'ie') . "</td>";
			}
			echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'descricao') . "</td>";
			echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'credenciamento') . "</td>";
			if ($login_fabrica == 15) {
				echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'atualizacao') . "</td>";
			}
			if ($login_fabrica == 25 OR $login_fabrica == 47 OR $login_fabrica == 81) {
				echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'contrato') . "</td>";
			}
			echo "<td>";
			if (pg_fetch_result($res,$i,'pedido_faturado') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			echo "<td>";
			if (pg_fetch_result($res,$i,'pedido_em_garantia') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			if ($login_fabrica == 1) {
				echo "<td>";
				if (pg_fetch_result($res,$i,'coleta_peca') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
				echo "</td>";
				echo "<td>";
				if (pg_fetch_result($res,$i,'reembolso_peca_estoque') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
				echo "</td>";
			}
			echo "<td>";
			if (pg_fetch_result($res,$i,'digita_os') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			echo "<td>";
			if (pg_fetch_result($res,$i,'prestacao_servico') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			if($login_fabrica == 20) { #HD 85632
				echo "<td>";
				if (pg_fetch_result($res,$i,'prestacao_servico_sem_mo') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
				echo "</td>";
			}
			echo "<td>";
			if (pg_fetch_result($res,$i,'pedido_via_distribuidor') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	//final gera relatorio excel
	if ($login_fabrica==3){
		fclose ($fp);
		flush();

		echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;
		echo "<br><p id='id_download2'><a href='xls/$arquivo_nome.zip'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório de todos os postos</font></a></p><br>";
	}
	//fim final gera relatorio excel
}
?>


<? include "rodape.php"; ?>
