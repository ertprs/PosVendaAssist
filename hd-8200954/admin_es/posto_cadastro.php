<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";
$msg_debug = "";
//  Para testes da tela de pesquisa
if (preg_match('/posto_cadastro(.*).php/', $PHP_SELF, $a_suffix)) {
	$suffix = $a_suffix[1];
	if (!file_exists("posto_pesquisa$suffix.php")) unset($suffix);
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

#-------------------- Descredenciar -----------------
if ($btn_acao == "descredenciar" and strlen($posto) > 0 ) {
	$sql = "DELETE FROM tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.posto   = $posto
			AND    tbl_posto_fabrica.fabrica = $login_fabrica;";
	$res = pg_query($con,$sql);

	if (strlen(pg_last_error($con)) > 0) $msg_erro = pg_last_error($con);

	if (strlen($msg_erro) == 0) {
		header ("Location: $PHP_SELF");
		exit;
	}
}

if ($btn_acao == "gravar") {
	$cnpj	= trim($_POST['cnpj']);
	$xcnpj	= $cnpj;
	$nome	= trim($_POST['nome']);
	$posto	= trim($_POST['posto']);

	if (strlen($nome) == 0 AND strlen($xcnpj) > 0) {
		// verifica se posto está cadastrado
		$sql = "SELECT posto
				FROM   tbl_posto
				WHERE  cnpj = '$xcnpj'";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$posto = pg_result ($res,0,0);
			header ("Location: $PHP_SELF?posto=$posto");
			exit;
		}else{
			$msg_erro = "El Servicio no está dado de alta. Por favor, complete los datos del registro.";
		}
	}

	if(strlen($xcnpj) == 0)
		$msg_erro = "Informe la Identificación del Servicio.";
	else
		$cnpj = $xcnpj;

	if (strlen($msg_erro) == 0){
		if (strlen($posto) == 0 AND strlen($nome) > 0 AND strlen($xcnpj) > 0) {
			// verifica se posto está cadastrado
			$sql = "SELECT posto
					FROM   tbl_posto
					WHERE  cnpj = '$xcnpj'";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0){
				$posto = pg_result ($res,0,0);
				header ("Location: $PHP_SELF?posto=$posto");
				exit;
			}
		}
	}

	if (strlen($msg_erro) == 0){
		$ie                                      = trim($_POST ['ie']);
		$endereco                                = trim($_POST ['endereco']);
		$numero                                  = trim($_POST ['numero']);
		$complemento                             = trim($_POST ['complemento']);
		$bairro                                  = trim($_POST ['bairro']);
		$cep                                     = trim($_POST ['cep']);
		$cidade                                  = trim($_POST ['cidade']);
		$estado                                  = trim($_POST ['estado']);
		$contato                                 = trim($_POST ['contato']);
		$email                                   = trim($_POST ['email']);
		$fone                                    = trim($_POST ['fone']);
		$fax                                     = trim($_POST ['fax']);
		$contato                                 = trim($_POST ['contato']);
		$nome_fantasia                           = trim($_POST ['nome_fantasia']);
		$obs                                     = trim($_POST ['obs']);
		$capital_interior                        = trim($_POST ['capital_interior']);
		$tipo_posto                              = trim($_POST ['tipo_posto']);
		$codigo                                  = trim($_POST ['codigo']);
		$senha                                   = trim($_POST ['senha']);
		$tabela_unica                            = trim($_POST ['tabela_unica']);
		$desconto                                = trim($_POST ['desconto']);
		$desconto_acessorio                      = trim($_POST ['desconto_acessorio']);
		$custo_administrativo                    = trim($_POST ['custo_administrativo']);
		$imposto_al                              = trim($_POST ['imposto_al']);
		$suframa                                 = trim($_POST ['suframa']);
		$item_aparencia                          = trim($_POST ['item_aparencia']);
		$pedido_em_garantia_finalidades_diversas = trim($_POST ['pedido_em_garantia_finalidades_diversas']);

		$xie          = (strlen($ie) > 0)			? "'$ie'"			: 'null';
		$xendereco    = (strlen($endereco) > 0)		? "'$endereco'"		: 'null';
		$xnumero      = (strlen($numero) > 0)		? "'$numero'"		: 'null';
		$xcomplemento = (strlen($complemento) > 0)	? "'$complemento'"	: 'null';
		$xbairro      = (strlen($bairro) > 0)		? "'$bairro'"		: 'null';

		if (strlen($cep) > 0){
			$xcep = preg_replace ('/\D/', '', $cep);
			$xcep = "'".substr($xcep,0,8)."'";
		}else{
			$xcep = 'null';
		}

		$xcidade           = (strlen($cidade) > 0)			? "'$cidade'"			: 'null';
		$xestado           = (strlen($estado) > 0)			? "'$estado'"			: 'null';
		$xcontato          = (strlen($contato) > 0)			? "'$contato'"			: 'null';
		$xemail            = (strlen($email) > 0)			? "'$email'"			: 'null';
		$xfone             = (strlen($fone) > 0)			? "'$fone'"				: 'null';
		$xfax              = (strlen($fax) > 0)				? "'$fax'"				: 'null';
		$xcontato          = (strlen($contato) > 0)			? "'$contato'"			: 'null';
		$xnome_fantasia    = (strlen($nome_fantasia) > 0)	? "'$nome_fantasia'"	: 'null';
		$xcapital_interior = (strlen($capital_interior) > 0)? "'$capital_interior'" : 'null';
		$xtipo_posto       = (strlen($tipo_posto) > 0)		? "'$tipo_posto'"		: 'null';
		$xcodigo           = (strlen($codigo) > 0)			? "'$codigo'"			: 'null';
		$xsuframa          = (strlen($suframa) == 0)		? 'false'				: "'$suframa'";

		if (strlen($pedido_em_garantia_finalidades_diversas) == 0)
			$xpedido_em_garantia_finalidades_diversas = "'f'";
		if($pedido_em_garantia_finalidades_diversas=='t')
			$xpedido_em_garantia_finalidades_diversas = "'$pedido_em_garantia_finalidades_diversas'";


		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"BEGIN TRANSACTION");

			#----------------------------- Alteração de Dados ---------------------
			if (strlen ($posto) > 0) {
				$sql = "UPDATE tbl_posto SET
							nome			= '$nome'                   ,
							cnpj			= '$xcnpj'                  ,
							ie				= $xie                      ,
							endereco		= $xendereco                ,
							numero			= $xnumero                  ,
							complemento		= $xcomplemento             ,
							bairro			= $xbairro                  ,
							cep				= $xcep                     ,
							cidade			= $xcidade                  ,
							estado			= $xestado                  ,
							contato			= $xcontato                 ,
							email			= $xemail                   ,
							fone			= $xfone                    ,
							fax				= $xfax                     ,
							nome_fantasia	= $xnome_fantasia           ,
							capital_interior= upper($xcapital_interior),
							suframa			= $xsuframa
						FROM    tbl_posto_fabrica
						WHERE tbl_posto.posto           = $posto
						AND	  tbl_posto.posto           = tbl_posto_fabrica.posto
						AND	  tbl_posto_fabrica.fabrica = $login_fabrica ";
				$res = @pg_query($con,$sql);
				if (strlen(pg_last_error($con)) > 0) $msg_erro = pg_last_error($con);
				
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
							ie              ,
							endereco        ,
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
							$xie                     ,
							$xendereco               ,
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
				$res = pg_query($con,$sql);
				if (pg_last_error($con) > 0) echo $msg_erro = pg_last_error($con);

				if (strlen($msg_erro) == 0){
					$sql = "SELECT CURRVAL ('seq_posto')";
					$res = pg_query($con,$sql);
					$posto = pg_result ($res,0,0);
					$msg_erro = pg_last_error($con);
					$novo_posto = $posto;
				}
			}

			// grava posto_fabrica
			if (strlen($msg_erro) == 0){
				$codigo_posto            = trim ($_POST['codigo']);
				$senha                   = trim ($_POST['senha']);
				$tipo_posto              = trim ($_POST['tipo_posto']);
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
				$cpf_conta               = trim($_POST ['cpf_conta']);
				$tipo_conta              = trim($_POST ['tipo_conta']);
				$obs_conta               = trim($_POST ['obs_conta']);
				$pedido_via_distribuidor = trim($_POST ['pedido_via_distribuidor']);
				$pais                    = trim($_POST ['pais']);

				$xcodigo_posto             = (strlen($codigo_posto) > 0) ? "'" . strtoupper ($codigo_posto) . "'" : 'null';
				$xsenha                    = (strlen($senha) > 0) ? "'".$senha."'" : "'*'";
				$xdesconto                 = (strlen($desconto) > 0) ? "'".$desconto."'" : $xdesconto = 'null';
				$xdesconto_acessorio       = (strlen($desconto_acessorio) > 0) ? "'".$desconto_acessorio."'" : 'null';
				$xcusto_administrativo     = (strlen($custo_administrativo) > 0) ? "'".$custo_administrativo."'" : 0;
				$ximposto_al               = (strlen($imposto_al) > 0) ? "'".$imposto_al."'" : 'null';
				$xtipo_posto               = (strlen($tipo_posto) > 0) ? "'".$tipo_posto."'" : 'null';
				$xobs                      = (strlen($obs) > 0) ? "'".$obs."'" : 'null';
				$xtransportadora           = (strlen($transportadora) > 0) ? "'".$transportadora."'" : 'null';
				$xcobranca_endereco        = (strlen($cobranca_endereco) > 0) ? "'".$cobranca_endereco."'" : 'null';
				$xcobranca_numero          = (strlen($cobranca_numero) > 0) ? "'".$cobranca_numero."'" : 'null';
				$xcobranca_complemento     = (strlen($cobranca_complemento) > 0) ? "'".$cobranca_complemento."'" : 'null';
				$xcobranca_bairro          = (strlen($cobranca_bairro) > 0) ? "'".$cobranca_bairro."'" : 'null';
				$xcobranca_cidade          = (strlen($cobranca_cidade) > 0) ? "'".$cobranca_cidade."'" : 'null';
				$xcobranca_estado          = (strlen($cobranca_estado) > 0) ? "'".$cobranca_estado."'" : 'null';
				$xpedido_em_garantia       = (strlen($pedido_em_garantia) > 0) ? "'".$pedido_em_garantia."'" : "'f'";
				$xcoleta_peca              = (strlen($coleta_peca) > 0) ? "'".$coleta_peca."'" : "'f'";
				$xreembolso_peca_estoque   = (strlen($reembolso_peca_estoque) > 0) ? "'".$reembolso_peca_estoque."'" : "'f'";
				$xpedido_faturado          = (strlen($pedido_faturado) > 0) ? "'".$pedido_faturado."'" : "'f'";
				$xdigita_os                = (strlen($digita_os) > 0) ? "'".$digita_os."'" : "'f'";
				$xprestacao_servico        = (strlen($prestacao_servico) > 0) ? "'".$prestacao_servico."'" : "'f'";
				$xprestacao_servico_sem_mo = (strlen($prestacao_servico_sem_mo) > 0) ? "'".$prestacao_servico_sem_mo."'" : "'f'";
				$xagencia                  = (strlen($agencia) > 0) ? "'".$agencia."'" : 'null';
				$xconta                    = (strlen($conta) > 0) ? "'".$conta."'" : 'null';
				$xfavorecido_conta         = (strlen($favorecido_conta) > 0) ? "'".$favorecido_conta."'" : 'null';
				$xtipo_conta               = (strlen($tipo_conta) > 0) ? "'".$tipo_conta."'" : 'null';
				
				if (strlen($cobranca_cep) > 0){
					$xcobranca_cep = str_replace (".","",$cobranca_cep);
					$xcobranca_cep = str_replace ("-","",$xcobranca_cep);
					$xcobranca_cep = str_replace (" ","",$xcobranca_cep);
					$xcobranca_cep = "'".$xcobranca_cep."'";
				}else{
					$xcobranca_cep = 'null';
				}

				if (strlen($banco) > 0) {
					$xbanco = "'".$banco."'";
					$sqlB = "SELECT nome FROM tbl_banco WHERE codigo = $banco";
					$resB = pg_query($con,$sqlB);
					if (pg_num_rows($resB) == 1) {
						$xnomebanco = "'" . trim(pg_result($resB,0,0)) . "'";
					}else{
						$xnomebanco = "null";
					}
				}else{
					$xbanco     = "null";
					$xnomebanco = "null";
				}

				$cpf_conta = str_replace (".","",$cpf_conta);
				$cpf_conta = str_replace ("-","",$cpf_conta);
				$cpf_conta = str_replace ("/","",$cpf_conta);
				$cpf_conta = str_replace (" ","",$cpf_conta);

				if (strlen($cpf_conta) <> 14 AND $tipo_conta == 'Conta jurídica'){
					$msg_erro = "Indentificación de la Cuenta Jurídica inválida";
				}

				$xcpf_conta               = (strlen($cpf_conta) > 0) ? "'".$cpf_conta."'" : 'null';
				$xobs_conta               = (strlen($obs_conta) > 0) ? "'".$obs_conta."'" : 'null';
				$xpedido_via_distribuidor = (strlen($pedido_via_distribuidor) > 0) ? "'".$pedido_via_distribuidor."'" : "'f'";

				if (strlen($msg_erro) == 0 AND strlen($posto) > 0) {
					$sql = "SELECT  tbl_posto_fabrica.*
							FROM    tbl_posto_fabrica
							WHERE   tbl_posto_fabrica.posto   = $posto
							AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
					$res = pg_query($con,$sql);
					if (strlen (pg_last_error($con)) > 0) $msg_erro = pg_last_error($con);
				}

				if (strlen($msg_erro) == 0 AND strlen($posto) > 0 AND strlen($xcodigo_posto) > 0 AND $login_fabrica == 11) {
					$sqlx = "SELECT  tbl_posto_fabrica.*
							FROM    tbl_posto_fabrica
							WHERE   tbl_posto_fabrica.posto       <> $posto
							AND     tbl_posto_fabrica.fabrica      = $login_fabrica 
							AND     tbl_posto_fabrica.codigo_posto = $xcodigo_posto";
					$resx = pg_query($con,$sqlx);
					if (pg_num_rows($resx) > 0) $msg_erro = "Ya existe un servício catastrado con el código $xcodigo_posto";
				}

				if (strlen($msg_erro) == 0){
					$total_rows = pg_num_rows($res);

					if ($login_fabrica == 3) {
					    $xpedido_via_distribuidor = "'t'";
					    $xpedido_faturado         = "'t'";
					    $xpedido_em_garantia      = "'f'";
					    $xreembolso_peca_estoque  = "'f'";
					    $xdigita_os               = "'t'";
					}

					if (pg_num_rows($res) > 0) {
						$sql = "UPDATE tbl_posto_fabrica SET
									codigo_posto            = $xcodigo_posto           ,
									senha                   = $xsenha                  ,
									tipo_posto              = $xtipo_posto             ,
									obs                     = $xobs                    ,
									transportadora          = $xtransportadora         ,
									cobranca_endereco       = $xcobranca_endereco      ,
									cobranca_numero         = $xcobranca_numero        ,
									cobranca_complemento    = $xcobranca_complemento   ,
									cobranca_bairro         = $xcobranca_bairro        ,
									cobranca_cep            = $xcobranca_cep           ,
									cobranca_cidade         = $xcobranca_cidade        ,
									cobranca_estado         = $xcobranca_estado        ,
									desconto                = $xdesconto               ,
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
									banco                   = $xbanco                  ,
									agencia                 = $xagencia                ,
									conta                   = $xconta                  ,
									nomebanco               = $xnomebanco              ,
									favorecido_conta        = $xfavorecido_conta       ,
									cpf_conta               = $xcpf_conta              ,
									tipo_conta              = $xtipo_conta             ,
									obs_conta               = $xobs_conta              ,
									pedido_via_distribuidor = $xpedido_via_distribuidor,
									item_aparencia          = '$item_aparencia'        ,
									data_alteracao          = current_timestamp        ,
									admin                   = $login_admin             
								WHERE tbl_posto_fabrica.posto   = $posto
								AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
					}else{
						$novo_posto = $posto;
						$sql = "INSERT INTO tbl_posto_fabrica (
									posto                  ,
									fabrica                ,
									codigo_posto           ,
									senha                  ,
									desconto               ,
									desconto_acessorio     ,
									custo_administrativo   ,
									imposto_al             ,
									tipo_posto             ,
									obs                    ,
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
									banco                  ,
									agencia                ,
									conta                  ,
									nomebanco              ,
									favorecido_conta       ,
									cpf_conta              ,
									tipo_conta             ,
									obs_conta              ,
									pedido_via_distribuidor,
									item_aparencia         ,
									data_alteracao         ,
									admin                  
								) VALUES (
									$posto                   ,
									$login_fabrica           ,
									$xcodigo                 ,
									$xsenha                  ,
									$xdesconto               ,
									$xdesconto_acessorio     ,
									$xcusto_administrativo   ,
									$ximposto_al             ,
									$xtipo_posto             ,
									$xobs                    ,
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
									$xbanco                  ,
									$xagencia                ,
									$xconta                  ,
									$xnomebanco              ,
									$xfavorecido_conta       ,
									$xcpf_conta              ,
									$xtipo_conta             ,
									$xobs_conta              ,
									$xpedido_via_distribuidor,
									'$item_aparencia'        ,
									current_timestamp        ,
									$login_admin             
								)";
					}
					$res = pg_query($con,$sql);
				}
				if (strlen (pg_last_error($con)) > 0) echo $msg_erro = pg_last_error($con);
			}
			// grava posto_linha
			if (strlen($msg_erro) == 0){
				if ($login_fabrica <> 14) {
					$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica";
					$res = pg_query($con,$sql);

					for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$linha = pg_fetch_result($res, $i, 'linha');

						$atende       = $_POST ['atende_'       . $linha];
						$tabela       = $_POST ['tabela_'       . $linha];
						$desconto     = $_POST ['desconto_'     . $linha];
						$distribuidor = $_POST ['distribuidor_' . $linha];

						if ($login_fabrica == 3 AND strlen ($atende) > 0 
							AND strlen ($distribuidor) == 0 
							AND ($estado == 'RS' ) 
							AND $linha <> 335) {
							if ($posto <> 1905) {
								echo "<h1>Servicio debe ser atendido por distribuidor</h1>";
								$resX = pg_query($con,"ROLLBACK TRANSACTION");
							}
						}

						$sql = "SELECT tbl_tabela.tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
						$resX = pg_query($con,$sql);
						if (pg_num_rows($resX) == 1) {
							$tabela = pg_fetch_result($resX, 0, 'tabela');
						}

						if (strlen ($atende) == 0) {
							$sql = "DELETE FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
							$resX = pg_query($con,$sql);
						}else{
							if (strlen ($tabela) == 0) $msg_erro = "Informe la tabla para esta línea";
							if (strlen ($desconto) == 0) $desconto = "0";
							if (strlen ($distribuidor) == 0) $distribuidor = "null";

							if (strlen ($msg_erro) == 0) {
								$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
								$resX = pg_query($con,$sql);
								if (pg_num_rows($resX) > 0) {
									$sql = "UPDATE tbl_posto_linha SET
												tabela       = $tabela  ,
												desconto     = $desconto,
												distribuidor = $distribuidor
											WHERE tbl_posto_linha.posto = $posto
											AND   tbl_posto_linha.linha = $linha";
									$resX = pg_query($con,$sql);
								}else{
									$sql = "INSERT INTO tbl_posto_linha (
												posto   ,
												linha   ,
												tabela  ,
												desconto,
												distribuidor
											) VALUES (
												$posto   ,
												$linha   ,
												$tabela  ,
												$desconto,
												$distribuidor
											)";
									$resX = pg_query($con,$sql);
								}
							}
						}
					}
				}else{
					$sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY tbl_familia.descricao;";
					$res = pg_query($con,$sql);

					for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$familia = pg_fetch_result($res, $i, 'familia');

						$atende       = $_POST ['atende_'       . $familia];
						$tabela       = $_POST ['tabela_'       . $familia];
						$desconto     = $_POST ['desconto_'     . $familia];
						$distribuidor = $_POST ['distribuidor_' . $familia];

						if (strlen ($atende) == 0) {
							$sql = "DELETE FROM tbl_posto_linha
									WHERE  tbl_posto_linha.posto   = $posto
									AND    tbl_posto_linha.familia = $familia";
							$resX = pg_query($con,$sql);
						}else{
							if (strlen ($tabela) == 0)       $msg_erro = "Informe la tabla para essa familia";
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
										$tabela = pg_fetch_result($resX, 0, 'tabela');
									}
									
									$sql = "UPDATE tbl_posto_fabrica SET
												tabela       = $tabela
											WHERE posto   = $posto
											AND   familia = $familia";
									$resX = pg_query($con,$sql);
					
								}
								$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
								$resX = pg_query($con,$sql);

								if (pg_num_rows($resX) > 0) {
									$sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
											WHERE  tbl_tabela.fabrica = $login_fabrica
											AND    tbl_tabela.tabela  = $tabela
											AND    tbl_tabela.ativa IS TRUE";
									$resX = pg_query($con,$sql);

									if (pg_num_rows($resX) == 1) {
										$tabela = pg_fetch_result($resX, 0, 'tabela');
									}
									
									$sql = "UPDATE tbl_posto_linha SET
												tabela       = $tabela  ,
												desconto     = $desconto,
												distribuidor = $distribuidor
											WHERE tbl_posto_linha.posto   = $posto
											AND   tbl_posto_linha.familia = $familia";
									$resX = pg_query($con,$sql);
					
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
									$resX = pg_query($con,$sql);
								}
							}
						}
					}
				}
			}
		}

		if($login_fabrica == 20){
			$sql = "SELECT * 
					FROM tbl_tabela 
					WHERE fabrica = $login_fabrica 
						AND sigla_tabela = '$login_pais'
						AND ativa 
					ORDER BY sigla_tabela";

			$resX   =  pg_query($con,$sql);
			$tabela =  @pg_fetch_result($resX, 0, 'tabela');

			$sql = "SELECT tbl_tabela.tabela 
					FROM tbl_tabela
					WHERE  tbl_tabela.fabrica = $login_fabrica
					AND    tbl_tabela.tabela  = $tabela
					AND    tbl_tabela.ativa IS TRUE";
			$resX = pg_query($con,$sql);
			
			if (pg_num_rows($resX) == 1) $tabela = @pg_fetch_result($resX, 0, 'tabela');

			$sql = "UPDATE tbl_posto_fabrica SET
						tabela     = $tabela
					WHERE posto    = $posto
					AND   fabrica  = $login_fabrica";
			$resX = pg_query($con,$sql);
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
				$condicao = pg_fetch_result($res1, $i, 'condicao');
				$visivel  = pg_fetch_result($res1, $i, 'visivel');
				
				$tabela =  ($condicao == 62) ? 47 : 31;

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
				$msg_erro = pg_last_error($con);
				if (strlen($msg_erro) > 0) {
					$msg_erro = " No fue posible grabar la forma de pago para este servicio. ";
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
		}

	
             if($error_code === 0) {
                 $link_arquivo = $resposta[0];

                 if ($link_arquivo == 'Sem resultados') {
                     $msg = $link_arquivo;
                     unset($link_arquivo);
                 }

             }else{
                 #$msg_erro = 'Erro ao processar o arquivo de atualização de posto. Tente novamente.';
             }
        }


		if (strlen ($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
				if ($login_fabrica == 20 and $novo_posto) {

	            include_once '../class/email/mailer/class.phpmailer.php';
	            /**
	             * instancia a classe PHPMailer no objeto $mailer
	             */
	            $mailer = new PHPMailer();
	            $cadastro = "novo";
	            $status = "C";
	            

	            $comando = "php /www/assist/www/rotinas/bosch/atualizacao-posto.php $novo_posto $status $cadastro";

	            #$comando = "php /home/monteiro/public_html/posvenda/rotinas/bosch/atualizacao-posto.php $novo_posto $status $cadastro";

	            $link_arquivo = passthru($comando);

			header ("Location: $PHP_SELF");
			exit;
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}
}

#-------------------- Pesquisa Posto -----------------
if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen($posto) > 0 and strlen ($msg_erro) == 0 ) {
	$sql = "SELECT  tbl_posto_fabrica.posto               ,
					tbl_posto_fabrica.credenciamento      ,
					tbl_posto_fabrica.codigo_posto        ,
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
					tbl_posto_fabrica.cpf_conta           ,
					tbl_posto_fabrica.tipo_conta          ,
					tbl_posto_fabrica.obs_conta           ,
					tbl_posto.nome                        ,
					tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
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
					tbl_posto.suframa                     ,
					tbl_posto.contato                     ,
					tbl_posto.capital_interior            ,
					tbl_posto.nome_fantasia               ,
					tbl_posto.pais                        ,
					tbl_posto_fabrica.item_aparencia      ,
					tbl_posto_fabrica.senha               ,
					tbl_posto_fabrica.desconto            ,
					tbl_posto_fabrica.desconto_acessorio  ,
					tbl_posto_fabrica.custo_administrativo,
					tbl_posto_fabrica.imposto_al          ,
					tbl_posto_fabrica.pedido_em_garantia  ,
					tbl_posto_fabrica.reembolso_peca_estoque,
					tbl_posto_fabrica.coleta_peca         ,
					tbl_posto_fabrica.pedido_faturado     ,
					tbl_posto_fabrica.digita_os           ,
					tbl_posto_fabrica.prestacao_servico   ,
					tbl_posto_fabrica.prestacao_servico_sem_mo ,
					tbl_posto.senha_financeiro            ,
					tbl_posto_fabrica.admin               ,
					to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
					tbl_posto_fabrica.pedido_via_distribuidor
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.posto   = $posto ";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$posto            = trim(pg_fetch_result($res, 0, 'posto'));
		$credenciamento   = trim(pg_fetch_result($res, 0, 'credenciamento'));
		$codigo           = trim(pg_fetch_result($res, 0, 'codigo_posto'));
		$nome             = trim(pg_fetch_result($res, 0, 'nome'));
		$cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
		if (strlen($cnpj) == 14) $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
		if (strlen($cnpj) == 11) $cnpj = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
		$ie               = trim(pg_fetch_result($res, 0, 'ie'));
		$endereco         = trim(pg_fetch_result($res, 0, 'endereco'));
		$endereco         = str_replace("\"","",$endereco);
		$numero           = trim(pg_fetch_result($res, 0, 'numero'));
		$complemento      = trim(pg_fetch_result($res, 0, 'complemento'));
		$bairro           = trim(pg_fetch_result($res, 0, 'bairro'));
		$cep              = trim(pg_fetch_result($res, 0, 'cep'));
		$cidade           = trim(pg_fetch_result($res, 0, 'cidade'));
		$estado           = trim(pg_fetch_result($res, 0, 'estado'));
		$email            = trim(pg_fetch_result($res, 0, 'email'));
		$fone             = trim(pg_fetch_result($res, 0, 'fone'));
		$fax              = trim(pg_fetch_result($res, 0, 'fax'));
		$contato          = trim(pg_fetch_result($res, 0, 'contato'));
		$suframa          = trim(pg_fetch_result($res, 0, 'suframa'));
		$item_aparencia   = trim(pg_fetch_result($res, 0, 'item_aparencia'));
		$obs              = trim(pg_fetch_result($res, 0, 'obs'));
		$capital_interior = trim(pg_fetch_result($res, 0, 'capital_interior'));
		$tipo_posto       = trim(pg_fetch_result($res, 0, 'tipo_posto'));
		$senha            = trim(pg_fetch_result($res, 0, 'senha'));
		$pais            = trim(pg_fetch_result($res, 0, 'pais'));
		$desconto         = trim(pg_fetch_result($res, 0, 'desconto'));
		$desconto_acessorio       = trim(pg_fetch_result($res, 0, 'desconto_acessorio'));
		$custo_administrativo     = trim(pg_fetch_result($res, 0, 'custo_administrativo'));
		$imposto_al               = trim(pg_fetch_result($res, 0, 'imposto_al'));
		$nome_fantasia            = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
		$transportadora           = trim(pg_fetch_result($res, 0, 'transportadora'));

		$cobranca_endereco       = trim(pg_fetch_result($res, 0, 'cobranca_endereco'));
		$cobranca_numero         = trim(pg_fetch_result($res, 0, 'cobranca_numero'));
		$cobranca_complemento    = trim(pg_fetch_result($res, 0, 'cobranca_complemento'));
		$cobranca_bairro         = trim(pg_fetch_result($res, 0, 'cobranca_bairro'));
		$cobranca_cep            = trim(pg_fetch_result($res, 0, 'cobranca_cep'));
		$cobranca_cidade         = trim(pg_fetch_result($res, 0, 'cobranca_cidade'));
		$cobranca_estado         = trim(pg_fetch_result($res, 0, 'cobranca_estado'));
		$pedido_em_garantia      = trim(pg_fetch_result($res, 0, 'pedido_em_garantia'));
		$reembolso_peca_estoque  = trim(pg_fetch_result($res, 0, 'reembolso_peca_estoque'));
		$coleta_peca            = trim(pg_fetch_result($res, 0, 'coleta_peca'));
		$pedido_faturado         = trim(pg_fetch_result($res, 0, 'pedido_faturado'));
		$digita_os               = trim(pg_fetch_result($res, 0, 'digita_os'));
		$prestacao_servico       = trim(pg_fetch_result($res, 0, 'prestacao_servico'));
		$prestacao_servico_sem_mo= trim(pg_fetch_result($res, 0, 'prestacao_servico_sem_mo'));
		$banco                   = trim(pg_fetch_result($res, 0, 'banco'));
		$agencia                 = trim(pg_fetch_result($res, 0, 'agencia'));
		$conta                   = trim(pg_fetch_result($res, 0, 'conta'));
		$nomebanco               = trim(pg_fetch_result($res, 0, 'nomebanco'));
		$favorecido_conta        = trim(pg_fetch_result($res, 0, 'favorecido_conta'));
		$cpf_conta               = trim(pg_fetch_result($res, 0, 'cpf_conta'));
		$tipo_conta              = trim(pg_fetch_result($res, 0, 'tipo_conta'));
		$obs_conta               = trim(pg_fetch_result($res, 0, 'obs_conta'));
		$senha_financeiro        = trim(pg_fetch_result($res, 0, 'senha_financeiro'));
		$pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));

		$admin          = trim(pg_fetch_result($res, 0, 'admin'));
		$data_alteracao = trim(pg_fetch_result($res, 0, 'data_alteracao'));

	}else{
		$sql = "SELECT  tbl_posto_fabrica.posto               ,
						tbl_posto_fabrica.credenciamento      ,
						tbl_posto_fabrica.codigo_posto        ,
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
						tbl_posto_fabrica.prestacao_servico_sem_mo ,
						tbl_posto_fabrica.banco               ,
						tbl_posto_fabrica.agencia             ,
						tbl_posto_fabrica.conta               ,
						tbl_posto_fabrica.nomebanco           ,
						tbl_posto_fabrica.favorecido_conta    ,
						tbl_posto_fabrica.cpf_conta           ,
						tbl_posto_fabrica.tipo_conta          ,
						tbl_posto_fabrica.obs_conta           ,
						tbl_posto.nome                        ,
						tbl_posto.cnpj                        ,
						tbl_posto.ie                          ,
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
						tbl_posto.pais                        ,
						tbl_posto.capital_interior            ,
						tbl_posto.nome_fantasia               ,
						tbl_posto_fabrica.item_aparencia      ,
						tbl_posto_fabrica.senha               ,
						tbl_posto_fabrica.desconto            ,
						tbl_posto_fabrica.desconto_acessorio  ,
						tbl_posto_fabrica.custo_administrativo,
						tbl_posto_fabrica.imposto_al          ,
						tbl_posto_fabrica.pedido_em_garantia  ,
						tbl_posto_fabrica.reembolso_peca_estoque,
						tbl_posto_fabrica.coleta_peca        ,
						tbl_posto_fabrica.pedido_faturado     ,
						tbl_posto_fabrica.digita_os           ,
						tbl_posto_fabrica.prestacao_servico   ,
						tbl_posto_fabrica.prestacao_servico_sem_mo   ,
						tbl_posto.senha_financeiro            ,
						tbl_posto_fabrica.admin               ,
						to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
						tbl_posto_fabrica.pedido_via_distribuidor
				FROM	tbl_posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE   tbl_posto_fabrica.posto   = $posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$posto            = trim(pg_fetch_result($res, 0, 'posto'));
			//$codigo         = trim(pg_fetch_result($res, 0, 'codigo_posto'));
			$credenciamento   = trim(pg_fetch_result($res, 0, 'credenciamento'));
			$nome             = trim(pg_fetch_result($res, 0, 'nome'));
			$cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
			$ie               = trim(pg_fetch_result($res, 0, 'ie'));
			if (strlen($cnpj) == 14) $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
			if (strlen($cnpj) == 11) $cnpj = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
			$endereco         = trim(pg_fetch_result($res, 0, 'endereco'));
			$endereco         = str_replace("\"","",$endereco);
			$numero           = trim(pg_fetch_result($res, 0, 'numero'));
			$complemento      = trim(pg_fetch_result($res, 0, 'complemento'));
			$bairro           = trim(pg_fetch_result($res, 0, 'bairro'));
			$cep              = trim(pg_fetch_result($res, 0, 'cep'));
			$cidade           = trim(pg_fetch_result($res, 0, 'cidade'));
			$estado           = trim(pg_fetch_result($res, 0, 'estado'));
			$email            = trim(pg_fetch_result($res, 0, 'email'));
			$fone             = trim(pg_fetch_result($res, 0, 'fone'));
			$fax              = trim(pg_fetch_result($res, 0, 'fax'));
			$contato          = trim(pg_fetch_result($res, 0, 'contato'));
			$suframa          = trim(pg_fetch_result($res, 0, 'suframa'));
			$item_aparencia   = trim(pg_fetch_result($res, 0, 'item_aparencia'));
			$obs              = trim(pg_fetch_result($res, 0, 'obs'));
			$capital_interior = trim(pg_fetch_result($res, 0, 'capital_interior'));
			$tipo_posto       = trim(pg_fetch_result($res, 0, 'tipo_posto'));
			//$senha            = trim(pg_fetch_result($res, 0, 'senha'));
			$desconto         = trim(pg_fetch_result($res, 0, 'desconto'));
			$desconto_acessorio = trim(pg_fetch_result($res, 0, 'desconto_acessorio'));
			$custo_administrativo = trim(pg_fetch_result($res, 0, 'custo_administrativo'));
			$imposto_al         = trim(pg_fetch_result($res, 0, 'imposto_al'));
			$nome_fantasia    = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
			$transportadora   = trim(pg_fetch_result($res, 0, 'transportadora'));
			$pais             = trim(pg_fetch_result($res, 0, 'pais'));

			$cobranca_endereco    = trim(pg_fetch_result($res, 0, 'cobranca_endereco'));
			$cobranca_numero      = trim(pg_fetch_result($res, 0, 'cobranca_numero'));
			$cobranca_complemento = trim(pg_fetch_result($res, 0, 'cobranca_complemento'));
			$cobranca_bairro      = trim(pg_fetch_result($res, 0, 'cobranca_bairro'));
			$cobranca_cep         = trim(pg_fetch_result($res, 0, 'cobranca_cep'));
			$cobranca_cidade      = trim(pg_fetch_result($res, 0, 'cobranca_cidade'));
			$cobranca_estado      = trim(pg_fetch_result($res, 0, 'cobranca_estado'));
			$pedido_em_garantia   = trim(pg_fetch_result($res, 0, 'pedido_em_garantia'));
			$reembolso_peca_estoque = trim(pg_fetch_result($res, 0, 'reembolso_peca_estoque'));
			$coleta_peca         = trim(pg_fetch_result($res, 0, 'coleta_peca'));
			$pedido_faturado      = trim(pg_fetch_result($res, 0, 'pedido_faturado'));
			$digita_os            = trim(pg_fetch_result($res, 0, 'digita_os'));
			$prestacao_servico    = trim(pg_fetch_result($res, 0, 'prestacao_servico'));
			$prestacao_servico_sem_mo    = trim(pg_fetch_result($res, 0, 'prestacao_servico_sem_mo'));
			$banco                = trim(pg_fetch_result($res, 0, 'banco'));
			$agencia              = trim(pg_fetch_result($res, 0, 'agencia'));
			$conta                = trim(pg_fetch_result($res, 0, 'conta'));
			$nomebanco            = trim(pg_fetch_result($res, 0, 'nomebanco'));
			$favorecido_conta        = trim(pg_fetch_result($res, 0, 'favorecido_conta'));
			$cpf_conta               = trim(pg_fetch_result($res, 0, 'cpf_conta'));
			$tipo_conta              = trim(pg_fetch_result($res, 0, 'tipo_conta'));
			$obs_conta               = trim(pg_fetch_result($res, 0, 'obs_conta'));
			$senha_financeiro        = trim(pg_fetch_result($res, 0, 'senha_financeiro'));
			$pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));

			$admin          = trim(pg_fetch_result($res, 0, 'admin'));
			$data_alteracao = trim(pg_fetch_result($res, 0, 'data_alteracao'));

		}else{
			$sql = "SELECT  tbl_posto.nome                        ,
							tbl_posto.cnpj                        ,
							tbl_posto.ie                          ,
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
							tbl_posto.pais                        ,
							tbl_posto.nome_fantasia
					FROM	tbl_posto
					WHERE   tbl_posto.posto   = $posto ";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$nome             = trim(pg_fetch_result($res, 0, 'nome'));
				$cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
				if (strlen($cnpj) == 14) $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
				if (strlen($cnpj) == 11) $cnpj = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
				$ie               = trim(pg_fetch_result($res, 0, 'ie'));
				$endereco         = trim(pg_fetch_result($res, 0, 'endereco'));
				$endereco         = str_replace("\"","",$endereco);
				$numero           = trim(pg_fetch_result($res, 0, 'numero'));
				$complemento      = trim(pg_fetch_result($res, 0, 'complemento'));
				$bairro           = trim(pg_fetch_result($res, 0, 'bairro'));
				$cep              = trim(pg_fetch_result($res, 0, 'cep'));
				$cidade           = trim(pg_fetch_result($res, 0, 'cidade'));
				$estado           = trim(pg_fetch_result($res, 0, 'estado'));
				$email            = trim(pg_fetch_result($res, 0, 'email'));
				$fone             = trim(pg_fetch_result($res, 0, 'fone'));
				$fax              = trim(pg_fetch_result($res, 0, 'fax'));
				$contato          = trim(pg_fetch_result($res, 0, 'contato'));
				$suframa          = trim(pg_fetch_result($res, 0, 'suframa'));
				$capital_interior = trim(pg_fetch_result($res, 0, 'capital_interior'));
				$senha_financeiro = trim(pg_fetch_result($res, 0, 'senha_financeiro'));
				$nome_fantasia    = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
				$pais             = trim(pg_fetch_result($res, 0, 'pais'));
			}
		}
	}
}

$title       = "Catastro de Servicios Autorizado";
$layout_menu = "cadastro";
include 'cabecalho.php';

?>

<script language="JavaScript">
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
		url = "posto_pesquisa<?=$suffix?>.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
		janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}

</script>

<style type="text/css">

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
</style>

<?
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
		echo "<br>";
		echo "<div id='mainCol'>";
		echo "<div class='contentBlockLeft' style='background-color: #FFCC00; width: 500;'>";
		echo "<br>";
		echo "<b>POSTOS COM STATUS \"EM DESCREDENCIAMENTO\"</b>";
		echo "<br><br>";
		echo "<table border='1' cellspadding='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr class='Titulo'>";
			echo "<td>POSTO</td>";
			echo "<td>DATA</td>";
			echo "<td>DIAS</td>";
			echo "<td>FECHA PREVISTA</td>";
			echo "</tr>";
		for ($k = 0 ; $k < pg_num_rows($resC) ; $k++) {
			$cor = ($k % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td align='left'><a href='$PHP_SELF?posto=" . trim(pg_result($resC,$k,posto)) . "'>" . trim(pg_result($resC,$k,posto_codigo)) . " - " . trim(pg_result($resC,$k,posto_nome)) . "</a></td>";
			echo "<td>" . trim(pg_fetch_result($resC, $k, 'data')) . "</td>";
			echo "<td>" . trim(pg_fetch_result($resC, $k, 'dias')) . "</td>";
			echo "<td>" . trim(pg_fetch_result($resC, $k, 'data_prevista')) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
		echo "</div>";
		echo "</div>";
		echo "<br>";
	}
}
?>

<? if(strlen($msg_erro) > 0){ ?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<? } ?>
<p>

<table width='600' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center' style='color:#596d9b; font: 12px arial,verdana ;'>
		Para catastrar un nuevo servicio, complete solo su identificación oficial e click guardar.
		<br>
		La eventual ya existencia del servicio será verificada.
	</td>
</tr>
</table>
<br>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="posto" value="<? echo $posto ?>">

<?
	echo "<table width='650' align='center' border='0'>";
	echo "<tr>";
	echo "<td align='left'><font size='2' face='verdana'>";
	if ($credenciamento == 'CREDENCIADO')
		$colors = "color:#3300CC";
	else if ($credenciamento == 'DESCREDENCIADO')
		$colors = "color:#F3274B";
	else if ($credenciamento == 'EM DESCREDENCIAMENTO')
		$colors = "color:#FF9900";
	else if ($credenciamento == 'EM CREDENCIAMENTO')
		$colors = "color:#006633";
	echo "<B>";
	echo "<a href='credenciamento.php?codigo=$codigo&posto=$posto&listar=3' style='$colors'>";
	echo $credenciamento;
	echo "</B></font></TD>";

	echo "<td align='right' nowrap>";
	if (strlen ($posto) > 0 and $login_fabrica <> 3) {
		$resX = pg_query("SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto FROM tbl_posto_fabrica JOIN tbl_posto ON tbl_posto_fabrica.distribuidor = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.posto = $posto");
		if (pg_num_rows($resX) > 0) {
			echo "Distribuidor: " . pg_result ($resX,0,codigo_posto) . " - " . pg_result ($resX,0,nome) ;
		}else{
			echo "Atendimiento directo";
		}
	}
	echo "</td>";
	echo "</tr>";
	echo "</table>";
?>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5"class="menu_top">
			<font color='#36425C'>INFORMACIONES CATASTRALES
		</td>
	</tr>
	<tr class="menu_top">
		<td>ID SERVICIO 01</td>
		<td>ID SERVICIO 02</td>
		<td>TELÉFONO</td>
		<td>FAX</td>
		<td>CONTACTO</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="cnpj" size="20" maxlength="18" value="<? echo $cnpj ?>" >&nbsp;<a href="#"><img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
		<td><input type="text" name="ie" size="20" maxlength="20" value="<? echo $ie ?>" ></td>
		<td><input type="text" name="fone" size="15" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" size="15" maxlength="20" value="<? echo $fax ?>"></td>
		<td><input type="text" name="contato" size="30" maxlength="30" value="<? echo $contato ?>" style="width:100px"></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">CÓDIGO</td>
		<td colspan="5">RAZÓN SOCIAL</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type="text" name="codigo" size="14" maxlength="14" value="<? echo $codigo ?>" style="width:150px">&nbsp;<a href="#"><img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'codigo')"></a></td>
		<td colspan="3"><input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" style="width:300px" >&nbsp;<a href="#"><img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'nome')"></a></td>
	</tr>
	<tr>
		<td colspan="5">
			<a href='<? echo $PHP_SELF ?>?listar=todos#postos'><img src="imagens/btn_listartodosospostos.gif"></a>
		</td>
	</tr>
</table>
<? if($login_fabrica==20){  ?>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="1">Descuento Accesorio</td>
		<td colspan="1">Impuesto IVA</td>

		<td colspan="1">Coste Administrativo</td>

		<td colspan="1">País</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="desconto_acessorio" size="5" maxlength="5" value="<? echo $desconto_acessorio ?>" >%</td>
		<td><input type="text" name="imposto_al" size="5" maxlength="5" value="<? echo $imposto_al ?>" >%</td>
     
		<td><input type="text" name="custo_administrativo" size="5" maxlength="5" value="<? echo $custo_administrativo ?>" >%</td>

		<td colspan="1">
			<input type='hidden' name='pais' value='<?=$login_pais?>'><?=$login_pais?>
		</td>
</table>
<?}?>
<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Guardar formulário" border='0'>
<img src="imagens_admin/btn_limpar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Borrar campos" border='0'>
</center>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="2">DIRECCIÓN</td>
		<td>NÚMERO</td>
		<td colspan="2">COMPLEMENTO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type="text" name="endereco" size="42" maxlength="30" value="<? echo $endereco ?>"></td>
		<td><input type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
		<td colspan="2"><input type="text" name="complemento" size="35" maxlength="20" value="<? echo $complemento ?>"></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">BARRIO</td>
		<td>APARTADO POSTAL</td>
		<td>CIUDAD</td>
		<td>PROV./ESTADO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type="text" name="bairro" size="40" maxlength="20" value="<? echo $bairro ?>"></td>
		<td><input type="text" name="cep"    size="10" maxlength="10" value="<? echo $cep ?>"></td>
		<td><input type="text" name="cidade" size="30" maxlength="30" value="<? echo $cidade ?>"></td>
		<td><input type="text" name="estado" size="2"  maxlength="2"  value="<? echo $estado ?>"></td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td>E-MAIL</td>
		<td>CAPITAL/PROVINCIA</td>
		<td>TIPO DE SERVICIO</td>
		<td>DESCUENTO</td>
	</tr>
	<tr class="table_line">
		<td>
			<input type="text" bgcolor='#FFFFFF' name="email" size="40" maxlength="50" value="<? echo $email ?>">
		</td>
		<td>
			<select name='capital_interior' size='1'>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> >Capital</option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> >Provincia</option>
			</select>
		</td>
		<td>
			<select name='tipo_posto' size='1'>
				<?
					$sql = "SELECT *
							FROM   tbl_tipo_posto
							WHERE  tbl_tipo_posto.fabrica = $login_fabrica
							and tipo_posto = 92
							ORDER BY tbl_tipo_posto.descricao";
					$res = pg_query($con,$sql);
						for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
							echo "<option value='" . pg_fetch_result($res, $i, 'tipo_posto') . "' ";
								if ($tipo_posto == pg_fetch_result($res, $i, 'tipo_posto')) echo " selected ";
							echo ">";
							echo pg_fetch_result($res, $i, 'descricao');
					echo "</option>";
					}
				?>
			</select>
		</td>
		<td><input type="text" name="desconto" size="5" maxlength="5" value="<? echo $desconto ?>" >%</td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td>NOMBRE COMERCIAL</td>
		<td>CONTRASEÑA</td>
		<td>ÍTEM APARIENCIA</td>
	</tr>
	<tr class="table_line">
		<td>
			<input type="text" name="nome_fantasia" size="20" maxlength="30" value="<? echo $nome_fantasia ?>" >
		</td>
		<td><input type="text" name="senha" size="10" maxlength="10" value="<? echo $senha ?>"></td>
    <INPUT TYPE="hidden" NAME="suframa" VALUE = 'f'>
		</td>
		<td>
			SÍ<INPUT TYPE="radio" NAME="item_aparencia" VALUE = 't' <?if ($item_aparencia == 't') echo "checked";?>>
			NO<INPUT TYPE="radio" NAME="item_aparencia" VALUE = 'f' <?if ($item_aparencia <> 't') echo "checked";?>>
		</td>
	</tr>
	<? if($senha_financeiro <> null){ ?>
	<tr class='menu_top'>
		<td colspan='5'>Contraseña área administrativa</td>
	</tr>
	<tr class='table_line'>
		<td colspan='5'><? echo "$senha_financeiro"; ?></td>
	</tr>
	<? } ?>
	<tr class="menu_top">
		<td colspan="5">Observaciones</td>
	</tr>
	<tr class="table_line">
		<td colspan="5">
			<textarea name="obs" cols="75" rows="2"><? echo $obs ?></textarea>
		</td>
	</tr>
</table>

<p>

<!--   Cobranca  -->
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan='4'class="menu_top">
			<font color='#36425C'>INFORMACIONES PARA COBRO</td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">DIRECCIÓN</td>
		<td>NÚMERO</td>
		<td>COMPLEMENTO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type="text" name="cobranca_endereco" size="50" maxlength="50" value="<? echo $cobranca_endereco ?>"></td>
		<td><input type="text" name="cobranca_numero" size="10" maxlength="10" value="<? echo $cobranca_numero ?>"></td>
		<td><input type="text" name="cobranca_complemento" size="30" maxlength="20" value="<? echo $cobranca_complemento ?>"></td>
	</tr>
	<tr class="menu_top">
		<td>BARRIO</td>
		<td>APARTADO POSTAL</td>
		<td>CIUDAD</td>
		<td>PROV./ESTADO</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="cobranca_bairro" size="30" maxlength="20" value="<? echo $cobranca_bairro ?>"></td>
		<td><input type="text" name="cobranca_cep" size="15" maxlength="8" value="<? echo $cobranca_cep ?>"></td>
		<td><input type="text" name="cobranca_cidade" size="30" maxlength="30" value="<? echo $cobranca_cidade ?>"></td>
		<td><input type="text" name="cobranca_estado" size="5" maxlength="2" value="<? echo $cobranca_estado ?>"></td>
	</tr>
</table>

<? if ($login_fabrica <> 1 OR $login_login == "fabiola" OR $login_login == "silvania" ) { ?>

<p>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top"><td colspan='3' class="menu_top">
			<font color='#36425C'>INFORMACIONES BANCARIAS</td></tr>
	<tr class="menu_top">
		<td width = '33%'>IDENTIFICACIÓN 01 - BENEFICIARIO</td>
		<td colspan=2>NOMBRE - BENEFICIARIO</td>
	</tr>
	<tr class="table_line">
		<td width = '33%'><input type="text" name="cpf_conta" size="14" maxlength="19" value="<? echo $cpf_conta ?>"></td>
		<td colspan=2><input type="text" name="favorecido_conta" size="60" maxlength="50" value="<? echo $favorecido_conta ?>"></td>
	</tr>
	<tr class="menu_top">
		<td colspan='3' width = '100%'>BANCO</td>
	</tr>
	<tr class="table_line">
		<td colspan='3'>
		</td>
	</tr>
	<tr class="menu_top">
		<td width = '33%'>TIPO DE CUENTA</td>
		<td width = '33%'>AGENCIA</td>
		<td width = '34%'>CUENTA</td>
	</tr>
	<tr class="table_line">
		<td width = '33%'>
			<select name='tipo_conta'>
				<option selected></option>
				<option value='Conta conjunta'   <? if ($tipo_conta == 'Conta conjunta')   echo "selected"; ?>>Cuenta conjunta</option>
				<option value='Conta corrente'   <? if ($tipo_conta == 'Conta corrente')   echo "selected"; ?>>Cuenta corriente</option>
				<option value='Conta individual' <? if ($tipo_conta == 'Conta individual') echo "selected"; ?>>Cuenta individual</option>
				<option value='Conta jurídica'   <? if ($tipo_conta == 'Conta jurídica')   echo "selected"; ?>>Cuenta jurídica</option>
				<option value='Conta poupança'   <? if ($tipo_conta == 'Conta poupança')   echo "selected"; ?>>Cuenta ahorro</option>
			</select>
		</td>
		<td width = '33%'><input type="text" name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>"></td>
		<td width = '34%'><input type="text" name="conta" size="15" maxlength="15" value="<? echo $conta ?>"></td>
	</tr>
	<tr class="menu_top">
		<td colspan="3">Observaciones</td>
	</tr>
	<tr class="table_line">
		<td colspan="3">
			<textarea name="obs_conta" cols="75" rows="2"><? echo $obs_conta; ?></textarea>
		</td>
	</tr>
</table>
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

<p>
<!--   linhas, tabelas Distribuidores  -->
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
<tr>
	<td class="menu_top">
		<font color='#36425C'>TABLAS PRECIO</font>
	</td>
</tr>
<tr>
	<td>

<?
if($login_fabrica == 20 AND strlen($posto)>0) {
	$sql = "SELECT tabela FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
	$resX = @pg_query($con,$sql);
	$tabela =  @pg_fetch_result($resX, 0, 'tabela');

	$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND sigla_tabela = '$login_pais'AND ativa ORDER BY sigla_tabela";
	$resX = pg_query($con,$sql);

	echo "<select name='tabela_unica'>\n";
	echo "<option selected></option>\n";

	for($x=0; $x < pg_num_rows($resX); $x++){
		$check = "";
		if ($tabela == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
		echo "<option value='".pg_result($resX,$x,tabela)."' $check>".pg_result($resX,$x,sigla_tabela)."</option>";
	}

	echo "</select>\n";
}

if (strlen ($posto) > 0 AND $login_fabrica <> 20) {
?>
	<TABLE  width='100%' align='center' border='1' cellpadding='1' cellspacing='3'>
	<TR class='menu_top'>
	<? if ($login_fabrica <> 14) { ?>
	<TD>Línea</TD>
	<? } else { ?>
	<TD>Familia</TD>
	<? } ?>
	<TD>Atiende</TD>
	<TD>Tabla</TD>
	<TD>Descuento</TD>
	<TD>Distribuidor</TD>
	</tr>
<?
	if ($login_fabrica <> 14) {
		$sql = "SELECT  tbl_linha.linha,
						tbl_linha.nome
				FROM	tbl_linha
				WHERE	tbl_linha.fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);

		for($i=0; $i < pg_num_rows($res); $i++){
			$linha = pg_fetch_result($res, $i, 'linha');
			$check = "";
			$tabela = "" ;
			$desconto = "";
			$distribuidor = "";
			
			$distinct = ($login_fabrica == 2) ? " DISTINCT " : "";
			$sql = "SELECT $distinct * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
			
			$resX = pg_query($con,$sql);

			if (pg_num_rows($resX) == 1) {
				$check        = " CHECKED ";
				$tabela       = pg_fetch_result($resX, 0, 'tabela');
				$desconto     = pg_fetch_result($resX, 0, 'desconto');
				$distribuidor = pg_fetch_result($resX, 0, 'distribuidor');
			}

			if (pg_num_rows($resX) > 1) {
				echo "<h1> ERRO NAS LINHAS, AVISE TELECONTROL </h1>";
				exit;
			}

			if (strlen ($msg_erro) > 0) {
				$atende       = $_POST ['atende_'       . $linha] ;
				$tabela       = $_POST ['tabela_'       . $linha] ;
				$desconto     = $_POST ['desconto_'     . $linha] ;
				$distribuidor = $_POST ['distribuidor_' . $linha] ;
				if (strlen ($atende) > 0 ) $check = " CHECKED ";
			}
			echo "<tr>";

			echo "<td nowrap>" . pg_fetch_result($res, $i, 'nome') . "</td>";
			echo "<td align='center'><input type='checkbox' name='atende_$linha' value='$linha' $check></td>";
			echo "<td align='left'>";

			$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
			$resX = pg_query($con,$sql);

			echo "<select name='tabela_$linha'>\n";
			echo "<option selected></option>\n";

			for($x=0; $x < pg_num_rows($resX); $x++){
				$check = "";
				if ($tabela == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
				echo "<option value='".pg_result($resX,$x,tabela)."' $check>".pg_result($resX,$x,sigla_tabela)."</option>";
			}

			echo "</select>\n";
			echo "</td>";
			echo "<td align='center'><input type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
			echo "<td align='left'>";

			$sql = "SELECT  tbl_posto.posto, tbl_posto.nome_fantasia, tbl_posto.nome
					FROM    tbl_posto
					JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN    tbl_tipo_posto       ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
					WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
					
					AND     tbl_tipo_posto.distribuidor is true
					ORDER BY tbl_posto.nome_fantasia";
			$resX = pg_query($con,$sql);

			echo "<select name='distribuidor_$linha'>\n";
			echo "<option ></option>\n";

			for($x = 0; $x < pg_num_rows($resX); $x++) {
				$check = "";
				if ($distribuidor == pg_fetch_result($resX, $x, 'posto')) $check = " selected ";
				$nome_fantasia = pg_result($resX,$x,'nome_fantasia');
				if (strlen(trim($nome_fantasia)) == 0) $nome_fantasia = pg_result($resX,$x,'nome');
				echo "<option value='".pg_result($resX,$x,'posto')."'$check>$nome_fantasia</option>";
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
		$res = pg_query($con,$sql);

		for($i=0; $i < pg_num_rows($res); $i++){
			$familia       = pg_fetch_result($res, $i, 'familia');
			$check         = "";
			$tabela        = "";
			$desconto      = "";
			$distribuidor  = "";

			$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
			$resX = pg_query($con,$sql);

			if (pg_num_rows($resX) == 1) {
				$check        = " CHECKED ";
				$tabela       = pg_fetch_result($resX, 0, 'tabela');
				$desconto     = pg_fetch_result($resX, 0, 'desconto');
				$distribuidor = pg_fetch_result($resX, 0, 'distribuidor');
			}

			if (pg_num_rows($resX) > 1) {
				echo "<h1>ERROR EN LAS FAMILIAS, AVISE A <a href='mailto:suporte@telecontrol.com.br'>TELECONTROL</a> </h1>";
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

			echo "<td nowrap>" . pg_fetch_result($res, $i, 'descricao') . "</td>";
			echo "<td align='center'><input type='checkbox' name='atende_$familia' value='$familia' $check></td>";

			echo "<td align='left'>";

			$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
			$resX = pg_query($con,$sql);

			echo "<select name='tabela_$familia'>\n";
			echo "<option selected></option>\n";

			for($x=0; $x < pg_num_rows($resX); $x++){
				$check = "";
				if ($tabela == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
				echo "<option value='".pg_result($resX,$x,tabela)."' $check>".pg_result($resX,$x,sigla_tabela)."</option>";
			}

			echo "</select>\n";
			echo "</td>";
			echo "<td align='center'><input type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
			echo "<td align='left'>";

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
			$resX = pg_query($con,$sql);

			echo "<select name='distribuidor_$familia' disabled>";
			echo "<option > </option>\n";

			for($x = 0; $x < pg_num_rows($resX); $x++) {
				$check = "";
				if ($distribuidor == pg_result($resX,$x,'posto')) $check = " selected ";
				$nome_fantasia = pg_result ($resX,$x,'nome_fantasia') ;
				if (strlen (trim ($nome_fantasia)) == 0) $nome_fantasia = pg_result ($resX,$x,'nome') ;
				echo "<option value='".pg_fetch_result($resX, $x, 'posto')."' $check>$nome_fantasia</option>";
			}

			echo "</select>\n";
			echo "</td>";
			echo "</tr>";
		}
	}
}
	?>
	</TD>
	</TR>
	</TABLE>
</td>
</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='1' cellpadding="3" cellspacing="2">
<TR>
	<TD colspan='2' class="menu_top">SERVICIO PUEDE DIGITAR:</TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_faturado" VALUE='t' <? if ($pedido_faturado == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
	<TD align='left'>PEDIDO FACTURADO (MANUAL)</TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia" VALUE='t' <? if ($pedido_em_garantia == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
	<TD align='left'>PEDIDO EN GARANTÍA</TD>
</TR>

<? if ($login_fabrica == 1) { 
	if($posto){
		$sql = "SELECT visivel FROM tbl_posto_condicao WHERE condicao = 62 AND posto = $posto";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0){
			$pedido_em_garantia_finalidades_diversas = pg_fetch_result($res, 0, 'visivel');
		}
	}
?>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia_finalidades_diversas" VALUE='t' <? if ($pedido_em_garantia_finalidades_diversas == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
	<TD align='left'>PEDIDO DE GARANTÍA ( FINALIDADES DIVERSAS)</TD>
</TR>

<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="coleta_peca" VALUE='t' <? if ($coleta_peca == 't') echo 'checked' ?>></TD>
	<TD align='left'>RECOGIDA DE REPUESTOS</TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="reembolso_peca_estoque" VALUE='t' <? if ($reembolso_peca_estoque == 't') echo 'checked' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
	<TD align='left'>REEMBOLSO DE REPUESTOS EN STOCK (GARANTÍA AUTOMÁTICA)</TD>
</TR>
<? } ?>

<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="digita_os" VALUE='t' <? if ($digita_os == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
	<TD align='left'>ABRE OS</TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico" VALUE='t' <? if ($prestacao_servico == 't') echo ' checked ' ?>  <? if ($login_fabrica == 3) echo " disabled " ?>  ></TD>
	<TD align='left'>PRESTACIÓN DE SERVICIO<br><font size='-2'>&nbsp;Servicio sólo cobra la mano de obra.</font></TD>
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
		$res = pg_query($con,$sql);

		if (@pg_result($res,0,0) == 't') echo ''; else echo 'disabled';
	}
	?>
	<? if ($login_fabrica == 3) echo " disabled " ?>
	>
	</TD>
	<TD align='left'>Pedido vía distribuidor</TD>
</TR>
<? if($login_fabrica == 20) { ?>
<TR>
	<TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico_sem_mo" VALUE='t' <? if ($prestacao_servico_sem_mo == 't') echo ' checked ' ?>></TD>
	<TD align='left'>PRESTACIÓN DE SERVICIO SIN MANO DE OBRA<br><font size='-2'>&nbsp;Servicio solamente recibe el valor de los recambios. No cobra mano de obra.</font></TD>
</TR>
<? } ?>

</TABLE>

<?
if (strlen($data_alteracao) > 0 AND strlen($admin) > 0){
?>
<br>
<table class="border" width='650' align='center' border='1' cellpadding="3" cellspacing="2">
<tr>
	<td class="menu_top">Última alteración:</td>
	<td>El: <? echo $data_alteracao; ?></td>
	<td>Usuario:  <? 
	$sql = "SELECT login,fabrica FROM tbl_admin WHERE (fabrica = $login_fabrica OR fabrica=10) AND admin = $admin";
	$res = pg_query($con,$sql);
	
	echo pg_fetch_result($res, 0, 'login');
	if(pg_fetch_result($res, 0, 'fabrica')==10)echo " <font size='1'>(Telecontrol)</font>";
	?></td>
</tr>
</table>
<br>
<?
}
?>

<a name="postos">
<br>
<center>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde envío') }" ALT="Guardar formulario" border='0'>
<img src="imagens_admin/btn_limpar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde envío') }" ALT="Limpiar formulario" border='0'>
</center>
<br>
<!-- ============================ Botoes de Acao ========================= -->

</form>

<table width='650' align='center'>
<? if (strlen($posto) > 0) {
	?>
	<tr>
		<td><a href='javascript: alert("¡Atención, se abrirá una nueva ventana para trabajar como si fuera este Servicio Autorizado! " + document.frm_posto.codigo.value); document.frm_login.login.value = document.frm_posto.codigo.value ; document.frm_login.senha.value = document.frm_posto.senha.value ; document.frm_login.submit() ; document.location = "<? echo $PHP_SELF ?>";'><img src="imagens/btn_comoesteposto_es.gif" alt="Haga un click aqui para acceder como si fuera este SERVICIO"></a></td>
	</tr>
	<tr>
		<td>
			<a href='<? echo $PHP_SELF ?>?listar=todos#postos'><img src="imagens/btn_listartodosospostos.gif"></a>
		</td>
	</tr>
</div>
<? } ?>
</table>
<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>
<input type="hidden" name="login">
<input type="hidden" name="senha">
<input type="hidden" name="btnAcao" value="Enviar">
</form>
<p>

<?
if ($_GET ['listar'] == 'todos') {
	$sql = "SELECT	tbl_posto.posto                           ,
					tbl_posto.cnpj                            ,
					tbl_posto.cidade                          ,
					tbl_posto.estado                          ,
					tbl_posto.nome                            ,
					tbl_posto.pais                            ,
					tbl_posto_fabrica.codigo_posto            ,
					tbl_tipo_posto.descricao                  ,
					tbl_posto_fabrica.pedido_faturado         ,
					tbl_posto_fabrica.pedido_em_garantia      ,
					tbl_posto_fabrica.coleta_peca			  ,
					tbl_posto_fabrica.reembolso_peca_estoque  ,
					tbl_posto_fabrica.digita_os               ,
					tbl_posto_fabrica.prestacao_servico       ,
					tbl_posto_fabrica.prestacao_servico_sem_mo,
					tbl_posto_fabrica.pedido_via_distribuidor ,
					tbl_posto_fabrica.credenciamento
			FROM	tbl_posto
			JOIN	tbl_posto_fabrica USING (posto)
			LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			WHERE	tbl_posto_fabrica.fabrica = $login_fabrica 
			AND     tbl_posto.pais            = '$login_pais'
			ORDER BY tbl_posto.pais,tbl_posto_fabrica.credenciamento, tbl_posto.nome";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		echo "<table border='1' cellpadding='3' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
			if ($i % 20 == 0) {
				flush();
				echo "<tr class='Titulo'>";
				if($login_fabrica==20){ echo "<td nowrap rowspan='2'>PAIS</td>";}
				echo "<td nowrap rowspan='2'>CIUDAD</td>";
				echo "<td nowrap rowspan='2'>PROV./ESTADO</td>";
				echo "<td nowrap rowspan='2'>NOMBRE</td>";
				echo "<td nowrap rowspan='2'>ID</td>";
				echo "<td nowrap rowspan='2'>TIPO</td>";
				echo "<td nowrap rowspan='2'>CREDENCIAMIENTO</td>";
				echo "<td nowrap colspan='7'>SERVICIO PODE DIGITAR</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td>PEDIDO FACTURADO</td>";
				echo "<td>PEDIDO EN GARANTÍA</td>";
				echo "<td>ABRE OS</td>";
				echo "<td>PRESTACIÓN DE SERVICIO</td>";
				echo "<td>PEDIDO VÍA DISTRIBUIDOR</td>";
				if($login_fabrica==20){ echo "<td>PRESTACIÓN DE SERVICIO SIN MANO DE OBRA</td>";}
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			if($login_fabrica==20){echo "<td nowrap align='left'>" . pg_fetch_result($res, $i, 'pais') . "</td>";}
			echo "<td nowrap align='left'>" . pg_fetch_result($res, $i, 'cidade') . "</td>";
			echo "<td nowrap>" . pg_fetch_result($res, $i, 'estado') . "</td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?posto=" . pg_result($res,$i,posto) . "'>" . pg_result($res,$i,nome) . "</a></td>";
			echo "<td nowrap>" . pg_fetch_result($res, $i, 'codigo_posto') . "</td>";
			echo "<td nowrap align='left'>" . pg_fetch_result($res, $i, 'descricao') . "</td>";
			echo "<td nowrap align='left'>" . pg_fetch_result($res, $i, 'credenciamento') . "</td>";
			echo "<td>";
			if (pg_fetch_result($res, $i, 'pedido_faturado') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			echo "<td>";
			if (pg_fetch_result($res, $i, 'pedido_em_garantia') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			if ($login_fabrica == 1) {
				echo "<td>";
				if (pg_fetch_result($res, $i, 'coleta_peca') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
				echo "</td>";
				echo "<td>";
				if (pg_fetch_result($res, $i, 'reembolso_peca_estoque') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
				echo "</td>";
			}
			echo "<td>";
			if (pg_fetch_result($res, $i, 'digita_os') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			echo "<td>";
			if (pg_fetch_result($res, $i, 'prestacao_servico') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			echo "<td>";
			if (pg_fetch_result($res, $i, 'pedido_via_distribuidor') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
			echo "</td>";
			if($login_fabrica == 20) {
				echo "<td>";
				if (pg_fetch_result($res, $i, 'prestacao_servico_sem_mo') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>
<p>
<? include "rodape.php"; ?>
