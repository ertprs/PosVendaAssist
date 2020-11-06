<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';


$qtd_pecas    = 7;
$qtd_produtos = 5;

$msg_erro = "";

if (strlen($_GET['os_sedex']) > 0)  $os_sedex = $_GET['os_sedex'];
if (strlen($_POST['os_sedex']) > 0) $os_sedex = $_POST['os_sedex'];

$btn_acao = $_POST['btn_acao'];

#--------------- Gravar Sedex ----------------------
if ($btn_acao == 'gravar') {

	$erro = "";

	if (strlen($_POST["posto_origem"]) == 0) $erro = "Digite o posto de origem.";
	else $xposto_origem = "'". trim($_POST["posto_origem"]) ."'";

	if (strlen($_POST["posto_destino"]) == 0) $erro = "Digite o posto de destino.";
	else $xposto_destino = "'". trim($_POST["posto_destino"]) ."'";

	if (strlen($_POST["obs"]) == 0) $xobs = 'null';
	else $xobs = "'". trim($_POST["obs"]) ."'";
	
	if (strlen($_POST["data_lancamento"]) == 0) $xdata_lancamento = 'null';
	else $xdata_lancamento = fnc_formata_data_pg(trim($_POST["data_lancamento"]));

	if (strlen($_POST["sua_os_destino"]) == 0) {
		if($login_fabrica==1){$erro = "Digite o número da OS Destino";}else{$xsua_os_destino = "null";}
	}else{ 
		$xsua_os_destino = "'". trim($_POST["sua_os_destino"]) ."'";
	}

	if (strlen($_POST["sua_os_origem"]) == 0) $xsua_os_origem = 'null';
	else $xsua_os_origem = "'". trim($_POST["sua_os_origem"]) ."'";

/*	if (strlen ($_POST["controle"]) > 0) {
		$xcontrole = "'". trim($_POST["controle"]) ."'";
	}elseif (strlen($_POST["controle"]) == 0 AND strlen($os_sedex) > 0){
		$erro = "Digite o número do controle";
	}

	if (strlen ($_POST["despesas"]) > 0) {
		$xdespesas = "'". trim($_POST["despesas"]) ."'";
	}elseif (strlen ($_POST["despesas"]) == 0 AND strlen($os_sedex) > 0){
		$erro = "Digite o valor das despesas";
	}*/

	if (strlen($_POST["controle"]) == 0) $xcontrole = 'null';
	else $xcontrole = "'". trim($_POST["controle"]) ."'";

	if (strlen($_POST["despesas"]) == 0){
		$xdespesas = 'null';
	}else{
		$xdespesas = str_replace(',','.',$_POST["despesas"]);
		$xdespesas = "'". trim($xdespesas) ."'";
	}

	if (strlen($_POST["tipo_os"]) > 0) {
		$tipo_os = $_POST["tipo_os"];
		if ($tipo_os == "produto") {
			for ( $i = 0 ; $i < $qtd_produtos ; $i++ ){
				if (strlen($_POST["produto_referencia_".$i]) > 0)
					$referencia = $_POST["produto_referencia_".$i];
			}
			if (strlen($referencia) == 0) {
				$erro = "Selecione o(s) produto(s).";
			}
		}
		if ($tipo_os == "peca") {
			for ( $i = 0 ; $i < $qtd_pecas ; $i++ ){
				if (strlen($_POST["peca_referencia_".$i]) > 0)
					$referencia = $_POST["peca_referencia_".$i];
			}
			if (strlen($referencia) == 0) {
				$erro = "Selecione a(s) peça(s).";
			}
		}
	}

	$res = pg_exec($con,"BEGIN TRANSACTION");

	if (strlen($erro) == 0) {
		if (strlen($os_sedex) == 0) {
			$sql =	"INSERT INTO tbl_os_sedex (
						fabrica       ,
						posto_origem  ,
						posto_destino ,
						obs           ,
						data          ,
						sua_os_destino,
						admin
					) VALUES (
						$login_fabrica    ,
						(SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = $xposto_origem AND fabrica = $login_fabrica) ,
						(SELECT posto FROM tbl_posto_fabrica WHERE tbl_posto_fabrica.codigo_posto = $xposto_destino AND fabrica = $login_fabrica),
						$xobs             ,
						$xdata_lancamento ,
						$xsua_os_destino,
						$login_admin
					)";
		}else{
			$sql =	"UPDATE tbl_os_sedex SET
							posto_origem     = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = $xposto_origem AND fabrica = $login_fabrica) ,
							posto_destino    = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = $xposto_destino AND fabrica = $login_fabrica),
							obs              = $xobs             ,
							data             = $xdata_lancamento ,
							controle         = $xcontrole        ,
							despesas         = $xdespesas        ,
							sua_os_origem    = $xsua_os_origem   ,
							sua_os_destino   = $xsua_os_destino  ,
							admin            = $login_admin
					WHERE   tbl_os_sedex.os_sedex = $os_sedex;";
		}
		$res = @pg_exec ($con,$sql);

		if (strlen(pg_errormessage($con)) > 0) {
			$erro = pg_errormessage($con);
			$erro = substr($erro,6);
			if (strpos($erro,'tbl_os_sedex_unico')) $erro = "Número da OS já digitado anteriormente.";
		}
	}
	
	if (strlen($erro) == 0 AND strlen($os_sedex) == 0) {
		$res      = @pg_exec ($con,"SELECT currval ('tbl_os_sedex_seq')");
		$os_sedex = @pg_result ($res,0,0);
	}

	if (strlen($erro) == 0) {
		$sql = "SELECT fn_valida_os_sedex($os_sedex,$login_fabrica);";
		$res = @pg_exec($con,$sql);
		
		if (strlen(pg_errormessage($con)) > 0) {
			$erro = pg_errormessage($con) ;
			$erro = substr($erro,6);
		}
	}

	if (strlen($erro) == 0) {
		if ($os_sedex > 0) {
			$sem_item = 0;

			if ($tipo_os == 'peca'){

				##### P E Ç A S #####

				for ( $y = 0 ; $y < $qtd_pecas ; $y++ ){
					$referencia = trim($_POST["peca_referencia_" .$y]);
					if (strlen($referencia) == 0) $sem_item = $sem_item + 1;
				}
				
				for ($y=0; $y<$qtd_pecas; $y++){
					$novo       = trim($_POST["peca_novo_"       .$y]);
					$item       = trim($_POST["os_sedex_item_"   .$y]);
					$referencia = trim($_POST["peca_referencia_" .$y]);
					$qtde       = trim($_POST["peca_qtde_"       .$y]);
					
					$referencia = strtoupper(trim($referencia));
					$referencia = str_replace ("-","",$referencia);
					$referencia = str_replace (" ","",$referencia);
					$referencia = str_replace ("/","",$referencia);
					$referencia = str_replace (".","",$referencia);

					if (strlen($referencia) == 0) {
						$xreferencia = "null";
					}else{
						$sql =	"SELECT peca
								FROM    tbl_peca
								WHERE   UPPER(trim(referencia_pesquisa)) = UPPER(trim('$referencia'))
								AND     fabrica = $login_fabrica";
						$res = @pg_exec ($con,$sql);

						if (strlen(pg_errormessage($con)) > 0) {
							$erro = pg_errormessage($con);
							$erro = substr($erro,6);
						}

						if (pg_numrows($res) > 0) $xpeca = pg_result($res,0,0);
						else $erro = "Peça $referencia não cadastrada.";
					}

					if (strlen($erro) > 0) {
						$matriz = $matriz . ";" . $y . ";";
						break;
					}

					if (strlen($qtde) == 0) {
						$xqtde = "null";
					}else{
						$xqtde = "'". $qtde ."'";
					}

					if(strlen($referencia) == 0) {
						if (strlen($item) > 0 AND $novo == 'f') {
							$sql = "DELETE FROM tbl_os_sedex_item
									WHERE tbl_os_sedex_item.os_sedex_item = $item";
							$res = @pg_exec($con,$sql);
						}
					}else{
						if ($novo == 't' OR strlen($novo) == 0) {
							$sql =	"INSERT INTO tbl_os_sedex_item (
										os_sedex ,
										peca     ,
										qtde     
									) VALUES (
										$os_sedex ,
										$xpeca    ,
										$xqtde    
									);";
						}else{
							$sql =	"UPDATE tbl_os_sedex_item SET
												os_sedex = $os_sedex ,
												peca     = $xpeca    ,
												qtde     = $xqtde    
									WHERE  tbl_os_sedex_item.os_sedex_item = $item;";
						}
						$res = @pg_exec($con,$sql);

						if (strlen(pg_errormessage($con)) > 0) {
							$erro = pg_errormessage ($con) ;
							$erro = substr($erro,6);
						}

						if (strlen($erro) > 0) {
							$matrizP = $matrizP . ";" . $y . ";";
							break;
						}

						if (strlen($erro) == 0 AND strlen($item) == 0) {
							$res           = @pg_exec ($con,"SELECT currval ('tbl_os_sedex_item_seq')");
							$os_sedex_item = @pg_result ($res,0,0);
						}else{
							$os_sedex_item = $item;
						}

						if (strlen($erro) == 0) {
							$sql = "SELECT fn_valida_os_sedex_item($os_sedex_item,$login_fabrica);";
							$res = @pg_exec($con,$sql);

							if (strlen(pg_errormessage($con)) > 0) {
								$erro = pg_errormessage ($con) ;
								$erro = substr($erro,6);
							}

							if (strlen($erro) > 0) {
								$matrizP = $matrizP . ";" . $y . ";";
								break;
							}
						}
					}
				}

				##### F I M   P E Ç A S #####

			}else{

				##### P R O D U T O S #####

				for ( $y = 0 ; $y < $qtd_produtos ; $y++ ) {
					$referencia = trim($_POST["produto_referencia_" .$y]);
					if (strlen($referencia) == 0) $sem_item = $sem_item + 1;
				}

				for ( $y = 0 ; $y < $qtd_produtos ; $y++ ) {
					$novo       = trim($_POST["produto_novo_"          .$y]);
					$item       = trim($_POST["os_sedex_item_produto_" .$y]);
					$referencia = trim($_POST["produto_referencia_"    .$y]);
					$qtde       = trim($_POST["produto_qtde_"          .$y]);

					$referencia = strtoupper(trim($referencia));
					$referencia = str_replace("-","",$referencia);
					$referencia = str_replace(" ","",$referencia);
					$referencia = str_replace("/","",$referencia);
					$referencia = str_replace(".","",$referencia);

					if (strlen($referencia) == 0) {
						$xreferencia = "null";
					}else{
						$sql =	"SELECT tbl_produto.produto
								FROM    tbl_produto
								JOIN    tbl_linha USING(linha)
								WHERE   tbl_produto.referencia_pesquisa = '$referencia'
								AND     tbl_linha.fabrica = $login_fabrica";
						$res = @pg_exec ($con,$sql);

						if (strlen(pg_errormessage($con)) > 0) {
							$erro = pg_errormessage($con) ;
							$erro = substr($erro,6);
						}

						if (pg_numrows($res) > 0) $xpeca = pg_result($res,0,0);
						else $erro = "Peça $referencia não cadastrada.";
					}

					if (strlen($erro) > 0){
						$matriz = $matriz . ";" . $y . ";";
						break;
					}

					if (strlen($qtde) == 0) $xqtde = "null";
					else $xqtde = "'". $qtde ."'";
					
					if(strlen($referencia) == 0) {
						if (strlen($item) > 0 AND $novo == 'f') {
							$sql = "DELETE FROM tbl_os_sedex_item_produto
									WHERE tbl_os_sedex_item_produto.os_sedex_item_produto = $item";
							$res = @pg_exec($con,$sql);
						}
					}else{
						if ($novo == 't') {
							$sql = "INSERT INTO tbl_os_sedex_item_produto (
										os_sedex ,
										produto  ,
										qtde     
									) VALUES (
										$os_sedex ,
										$xpeca    ,
										$xqtde    
									);";
						}else{
							$sql =	"UPDATE tbl_os_sedex_item_produto SET
										os_sedex = $os_sedex ,
										produto  = $xpeca    ,
										qtde     = $xqtde    
									WHERE  tbl_os_sedex_item_produto.os_sedex_item_produto = $item;";
						}

						$res = @pg_exec($con,$sql);

						if (strlen(pg_errormessage($con)) > 0) {
							$erro = pg_errormessage($con);
							$erro = substr($erro,6);
						}

						if (strlen($erro) > 0) {
							$matriz = $matriz . ";" . $y . ";";
							break;
						}

						if (strlen($erro) == 0 AND strlen($item) == 0) {
							$res           = @pg_exec ($con,"SELECT currval ('seq_os_sedex_item_produto')");
							$os_sedex_item = @pg_result ($res,0,0);
						}else{
							$os_sedex_item = $item;
						}
					}
				}

				##### F I M   P R O D U T O S #####

				if (strlen($erro) == 0) {
					$sql = "UPDATE tbl_os_sedex SET produto = 't' WHERE os_sedex = $os_sedex";
					$res = @pg_exec($con,$sql);

					if (strlen(pg_errormessage($con)) > 0) {
						$erro = pg_errormessage ($con) ;
						$erro = substr($erro,6);
					}
				}

			}
		}
	}

	if (strlen($erro) > 0) {
		$res = pg_exec($con,"ROLLBACK TRANSACTION");

		if (strpos ($erro,"ExecAppend: Fail to add null value in not null attribute posto_destino") > 0)
		$erro = "Código do posto destino não é válido.";

		$os_sedex = $_POST["os_sedex"];

		$msg_erro  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg_erro .= $erro;

	}else{
		$res = pg_exec($con,"COMMIT TRANSACTION");

		###########################################################
		#	E N V I O   D E   E M A I L
		###########################################################

		// Envia email para postos (origem e destino) e Black&Decker
/*
		$sql = "SELECT
					(
						SELECT  tbl_posto.email
						FROM    tbl_posto
						JOIN	tbl_posto_fabrica USING(posto)
						WHERE	tbl_posto_fabrica.fabrica = $login_fabrica
						AND		tbl_posto_fabrica.codigo_posto = $xposto_origem
					) AS email_origem,
					(
						SELECT  tbl_posto.email
						FROM    tbl_posto
						JOIN	tbl_posto_fabrica USING(posto)
						WHERE	tbl_posto_fabrica.fabrica = $login_fabrica
						AND		tbl_posto_fabrica.codigo_posto = $xposto_destino
					) AS email_destino
				FROM   tbl_posto";
*/

		// seleciona os dados do posto origem
		// somente email
		$sql = "SELECT  tbl_posto.email
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING(posto)
				WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
				AND     tbl_posto_fabrica.codigo_posto = $xposto_origem";
		$res = pg_exec($con,$sql);
		$erro = pg_errormessage ($con) ;

		if (strlen ( pg_errormessage ($con) ) > 0) $erro = substr($erro,6);

		if(strlen($erro) == 0){
			$email_origem  = pg_result($res,0,email);
			
			if(strlen($email_origem) == 0) $erro_email .= "e-Mail do posto origem é inválido.";
		}
		$sql = "select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
//		echo $sql;
		if(pg_numrows($res)>0){
			$email_do_admin = trim(pg_result($res,0,0));
		}else{
			if($login_fabrica==1){
				$email_do_admin = "fabiola.oliveira@bdk.com";
			}else{
				$email_do_admin = "samuel@telecontrol.com.br";
			}
		}
		if($login_fabrica==25){
			$email_do_admin = "takashi@telecontrol.com.br";
		}
		// seleciona os dados do posto de destino
		// email | endereco | numero | bairro | cidade | estado | cep
		$sql = "SELECT  tbl_posto_fabrica.contato_email    AS email,
				tbl_posto_fabrica.contato_endereco AS endereco,
				tbl_posto_fabrica.contato_numero   AS numero,
				tbl_posto_fabrica.contato_bairro   AS bairro,
				tbl_posto_fabrica.contato_cidade   AS cidade,
				tbl_posto_fabrica.contato_estado   AS estado,
				tbl_posto_fabrica.contato_cep      AS cep
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica USING(posto)
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.codigo_posto = $xposto_destino";
		$res = pg_exec($con,$sql);
		$erro = pg_errormessage ($con) ;

		if (strlen ( pg_errormessage ($con) ) > 0) $erro = substr($erro,6);

		if(strlen($erro) == 0){
			$email_destino = pg_result($res,0,email);
			$endereco      = pg_result($res,0,endereco);
			$numero        = pg_result($res,0,numero);
			$bairro        = pg_result($res,0,bairro);
			$cidade        = pg_result($res,0,cidade);
			$estado        = pg_result($res,0,estado);
			$cep           = pg_result($res,0,cep);
			
			if(strlen($email_destino) == 0)  $erro_email .= "e-Mail do posto destino é inválido.";
		}

		if(strlen($erro) == 0){
			$ssql = "select nome from tbl_fabrica where fabrica = $login_fabrica";
			$rres = @pg_exec($con,$ssql);
			$nome_do_fabricante = @pg_result($rres,0,0);

			# =========================================================

			###########################################################
			# MAIL PARA OS POSTOS ( ORIGEM ) E BLACK&DECKER #
			###########################################################
			
			$from_nome  = $nome_do_fabricante;
			$from_email = $email_do_admin;
			$cc_email   = $email_origem;

			$subject	= "OS - Despesa de Sedex";

			// TOPO

			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "Prezado <b>".$_POST['nome_posto_origem']."</b>,";
			$mensagem	.= "</font>";
			$mensagem	.= "<br><br>\n";

			// PEÇAS e PRODUTOS
			$xos_sedex = "00000".$os_sedex; //3158 takashi
			$xos_sedex = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
			$xos_sedex = $_POST['posto_origem'].$xos_sedex;

			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "A sua ordem de serviço gerada para o envio da(s) mercadoria(s): <b>" . $xos_sedex . "</b>";
			$mensagem	.= "</font>";

			$mensagem	.= "<table width='100%' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
			$mensagem	.= "<tr>\n";
			
			$mensagem	.= "<td colspan='3'>\n";
			$mensagem	.= "&nbsp;";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "</tr>\n";
			$mensagem	.= "<tr>\n";
			
			$mensagem	.= "<td width='20%'align='left'>\n";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "<b>Referência</b>\n";
			$mensagem	.= "</font>\n";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "<td width='60%'align='left'>\n";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "<b>Descrição</b>\n";
			$mensagem	.= "</font>\n";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "<td width='20%'align='left'>\n";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "<b>Quantidade</b>\n";
			$mensagem	.= "</font>\n";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "</tr>\n";
			
			if ($tipo_os == 'peca'){
				for($i=0; $i<$qtd_pecas; $i++){
					$referencia = $_POST['peca_referencia_'.$i];
					$descricao  = $_POST['peca_descricao_' .$i];
					$qtde       = $_POST['peca_qtde_'      .$i];
					
					if (strlen($referencia) > 0){
						$mensagem	.= "<tr>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$referencia\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='60%'align='left' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$descricao\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$qtde\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "</tr>\n";
					}
				}
			}else{
				for($i=0; $i<$qtd_produtos; $i++){
					$referencia = $_POST['produto_referencia_'.$i];
					$descricao  = $_POST['produto_descricao_' .$i];
					$qtde       = $_POST['produto_qtde_'      .$i];
					
					if (strlen($referencia) > 0){
						$mensagem	.= "<tr>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$referencia\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='60%'align='left' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$descricao\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$qtde\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "</tr>\n";
					}
				}
			}
			$mensagem	.= "</table>\n";

			$mensagem	.= "<br>\n";

			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "Para o posto <b>" . $_POST['nome_posto_destino'] . "</b> é <b>" . $_POST['sua_os_destino'] . "</b>.<br>";
			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "Endereço: <b>" . $endereco . ", " . $numero . "</b>.<br>";
			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "CEP: <b>" . $cep . "</b>.<br>";
			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "Bairro: <b>" . $bairro . "</b>.<br>";
			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "Cidade: <b>" . $cidade . " / " . $estado . "</b>.<br>";
			$mensagem	.= "<br>";
			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
//			$mensagem	.= "é <b> OS " . $_POST['nome_posto_origem'] . $os_sedex . "</b>.<br>";
	//		$mensagem	.= "é <b> OS " . $os_sedex . "</b>.<br>";
			$mensagem	.= "é <b> OS " . $xos_sedex . "</b>.<br>";//chamado 3158 takashi
			$mensagem	.= "</font>\n";

			$mensagem	.= "<br><br>\n";

			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "Solicitamos o breve envio da mercadoria.";
			$mensagem	.= "</font>\n";

			$mensagem	.= "<br><br>\n";

			$mensagem	.= "<p style='text-indent: 350'>";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
			$mensagem	.= "Atenciosamente,";
			$mensagem	.= "</font>";
			$mensagem	.= "</p>\n";

			$mensagem	.= "<p style='text-indent: 300'>";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
			$mensagem	.= "Departamento de Assistência Técnica";
			$mensagem	.= "</font>";
			$mensagem	.= "</p>\n";

			$cabecalho	.= "MIME-Version: 1.0\n";
			$cabecalho	.= "Content-type: text/html; charset=iso-8859-1\n";
			$cabecalho	.= "From: $from_nome < $from_email >\n";
			$cabecalho	.= "To: $from_email \n";
			$cabecalho	.= "Bcc: $cc_email; $from_email \n";
			$cabecalho	.= "Return-Path: < $from_email >\n";
			$cabecalho	.= "X-Priority: 1\n";
			$cabecalho	.= "X-MSMail-Priority: High\n";
			$cabecalho	.= "X-Mailer: PHP/" . phpversion();
//echo $mensagem;
			if ( !mail("", utf8_encode($subject), utf8_encode($mensagem), $cabecalho) ) $erro_email = "NÂO enviou o e-mail.";

			$from_nome		= "";
			$from_email		= "";
			$to_nome		= "";
			$to_email		= "";
			$cc_nome		= "";
			$cc_email		= "";
			$subject		= "";
			$mensagem		= "";
			$cabecalho		= "";
			
			###########################################################
			# MAIL PARA OS POSTOS ( DESTINO ) E BLACK&DECKER #
			###########################################################
			
			$from_nome  = $nome_do_fabricante;
			$from_email = $email_do_admin;
			$cc_email   = $email_destino;
			
			$subject	= "OS - Despesa de Sedex";

			// TOPO

			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "Prezado <b>".$_POST['nome_posto_destino']."</b>,";
			$mensagem	.= "</font>";
			$mensagem	.= "<br><br>\n";

			// PEÇAS e PRODUTOS

			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			if ($tipo_os == 'peca') $mensagem .= "Estamos enviando a(s) peça(s):";
			else                    $mensagem .= "Estamos enviando o(s) produto(s):";
			$mensagem	.= "</font>";

			$mensagem	.= "<table width='100%' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
			$mensagem	.= "<tr>\n";
			
			$mensagem	.= "<td colspan='3'>\n";
			$mensagem	.= "&nbsp;";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "</tr>\n";
			$mensagem	.= "<tr>\n";
			
			$mensagem	.= "<td width='20%'align='left'>\n";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "<b>Referência</b>\n";
			$mensagem	.= "</font>\n";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "<td width='60%'align='left'>\n";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "<b>Descrição</b>\n";
			$mensagem	.= "</font>\n";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "<td width='20%'align='left'>\n";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "<b>Quantidade</b>\n";
			$mensagem	.= "</font>\n";
			$mensagem	.= "</td>\n";
			
			$mensagem	.= "</tr>\n";
			
			if ($tipo_os == 'peca'){
				for($i=0; $i<$qtd_pecas; $i++){
					$referencia = $_POST['peca_referencia_'.$i];
					$descricao  = $_POST['peca_descricao_' .$i];
					$qtde       = $_POST['peca_qtde_'      .$i];
					
					if (strlen($referencia) > 0){
						$mensagem	.= "<tr>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$referencia\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='60%'align='left' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$descricao\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$qtde\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "</tr>\n";
					}
				}
			}else{
				for($i=0; $i<$qtd_produtos; $i++){
					$referencia = $_POST['produto_referencia_'.$i];
					$descricao  = $_POST['produto_descricao_' .$i];
					$qtde       = $_POST['produto_qtde_'      .$i];
					
					if (strlen($referencia) > 0){
						$mensagem	.= "<tr>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$referencia\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='60%'align='left' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$descricao\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "<td width='20%'align='center' nowrap>\n";
						$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$mensagem	.= "$qtde\n";
						$mensagem	.= "</font>\n";
						$mensagem	.= "</td>\n";
						
						$mensagem	.= "</tr>\n";
					}
				}
			}
			$mensagem	.= "</table>\n";

			$mensagem	.= "<br>\n";

			$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem	.= "Para atender a sua OS <b>".$_POST['sua_os_destino']."</b>.";
			$mensagem	.= "</font>\n";

			$mensagem	.= "<br><br>\n";

			if ($tipo_os == 'peca'){
				$mensagem	.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$mensagem	.= "Faremos o abatimento no seu extrato de serviços referente ao valor da(s) peça(s) que receber. O abatimento será feito no próximo extrato gerado para o posto. Será descriminado no extrato o número da ordem de serviço, a(s) peça(s) e o valor do abatimento.";
				$mensagem	.= "</font>\n";

				$mensagem	.= "<br><br>\n";
			}

			$mensagem	.= "<p style='text-indent: 350'>";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
			$mensagem	.= "Atenciosamente,";
			$mensagem	.= "</font>";
			$mensagem	.= "</p>\n";

			$mensagem	.= "<p style='text-indent: 300'>";
			$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
			$mensagem	.= "Departamento de Assistência Técnica";
			$mensagem	.= "</font>";
			$mensagem	.= "</p>\n";

			$cabecalho	.= "MIME-Version: 1.0\n";
			$cabecalho	.= "Content-type: text/html; charset=iso-8859-1\n";
			$cabecalho	.= "From: $from_nome < $from_email >\n";
			$cabecalho	.= "To: $from_email \n";
			$cabecalho	.= "Bcc: $cc_email; $from_email \n";
			$cabecalho	.= "Return-Path: < $from_email >\n";
			$cabecalho	.= "X-Priority: 1\n";
			$cabecalho	.= "X-MSMail-Priority: High\n";
			$cabecalho	.= "X-Mailer: PHP/" . phpversion();
//echo $mensagem;			
			if ( !mail("", utf8_encode($subject), utf8_encode($mensagem), $cabecalho) ) $erro_email = "NÂO enviou o e-mail.";
			
			$from_nome		= "";
			$from_email		= "";
			$to_nome		= "";
			$to_email		= "";
			$cc_nome		= "";
			$cc_email		= "";
			$subject		= "";
			$mensagem		= "";
			$cabecalho		= "";
			
			###########################################################
			#	E N V I O   D E   E M A I L
			###########################################################


			header ("Location: sedex_finalizada.php?os_sedex=$os_sedex");
			exit;
		}
	}
}

if ($gravou == "ok") $msg_erro = "Lançamento de OS de SEDEX efetuado com sucesso: No. ".$_POST["posto_origem"]." $os_sedex !";

if (strlen($os_sedex) > 0) {
	$sql = "SELECT  tbl_os_sedex.posto_origem                       ,
					tbl_os_sedex.posto_destino                      ,
					tbl_os_sedex.obs                                ,
					to_char(tbl_os_sedex.data, 'DD/MM/YYYY') AS data,
					tbl_os_sedex.despesas                           ,
					tbl_os_sedex.controle                           ,
					tbl_os_sedex.sua_os_origem                      ,
					tbl_os_sedex.sua_os_destino                     ,
					tbl_os_sedex.finalizada                         ,
					tbl_os_sedex.extrato                            
			FROM    tbl_os_sedex
			WHERE   tbl_os_sedex.os_sedex = $os_sedex";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		$posto_origem    = trim (pg_result ($res,0,posto_origem));
		$posto_destino   = trim (pg_result ($res,0,posto_destino));
		$obs             = trim (pg_result ($res,0,obs));
		$data_lancamento = trim (pg_result ($res,0,data));
		$despesas        = trim (pg_result ($res,0,despesas));
		$controle        = trim (pg_result ($res,0,controle));
		$sua_os_origem   = trim (pg_result ($res,0,sua_os_origem));
		$sua_os_destino  = trim (pg_result ($res,0,sua_os_destino));
		$finalizada      = trim (pg_result ($res,0,finalizada));
		$extrato         = trim (pg_result ($res,0,extrato));

		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING(posto)
				WHERE   tbl_posto_fabrica.posto = $posto_origem
				AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$posto_origem      = trim(pg_result($res1,0,codigo_posto));
			$nome_posto_origem = trim(pg_result($res1,0,nome));
		}
		
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING(posto)
				WHERE   tbl_posto_fabrica.posto = $posto_destino
				AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
		$res2 = @pg_exec ($con,$sql);

		if (@pg_numrows($res2) > 0) {
			$posto_destino      = trim(pg_result($res2,0,codigo_posto));
			$nome_posto_destino = trim(pg_result($res2,0,nome));
		}
	}
}

if ($gravou == "ok") $msg_erro = "Lançamento de OS de SEDEX efetuado com sucesso: No. ".$posto_origem." $os_sedex !";

$title     = "OS DE DESPESAS DE SEDEX";
$cabecalho = "OS de Despesas de Sedex";
$layout_menu = "callcenter";

include "cabecalho.php";

if(strlen($data_lancamento) == 0) $data_lancamento = date("d/m/Y");
?>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script language="JavaScript">

function fnc_pesquisa_posto (campo1, campo2, tipo, posto) {
	var url = "";
	if (tipo == "codigo" ) {
		var xcampo = campo1;
	}
	if (tipo == "nome" ) {
		var xcampo = campo2;
	}
	if (xcampo != "") {
		var url = "";
		url = "pesquisa_posto_sedex.php?campo=" + xcampo + "&tipo=" + tipo + "&posto=" + posto;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
		janela.codigo  = campo1;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}

}

function fnc_pesquisa_peca (campo1, campo2, linha) {
    var url = "";
	if (campo1.length > 0) {
		var xcampo = campo1;
		url = "pesquisa_peca_sedex.php?referencia=";
	}else{
		var xcampo = campo2;
		url = "pesquisa_peca_sedex.php?nome=";
	}
	if (xcampo.length > 0) {
		url += xcampo + "&linha=" + linha;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_produto (campo1, campo2, linha) {
    var url = "";
	if (campo1.length > 0) {
		url = "pesquisa_produto_sedex.php?referencia="+campo1 + "&linha=" + linha;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_codigo_produto (codigo, nome, linha) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_produto_sedex.php?referencia=" + codigo + "&linha=" + linha;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
        janela.focus();
    }

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_nome_produto (codigo, nome, linha) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_produto_sedex.php?nome=" + nome + "&linha=" + linha;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
        janela.focus();
    }

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function mascara_data(data){
    var mydata = '';
        mydata = mydata + data;
        myform = "data_lancamento";

        if (mydata.length == 2){
            mydata = mydata + '/';
            window.document.frmdespesa.elements[myform].value = mydata;
        }
        if (mydata.length == 5){
            mydata = mydata + '/';
            window.document.frmdespesa.elements[myform].value = mydata;
        }
        if (mydata.length == 10){
            verifica_data();
        }
    }

function verifica_data () {
    dia = (window.document.frmdespesa.elements[myform].value.substring(0,2));
    mes = (window.document.frmdespesa.elements[myform].value.substring(3,5));
    ano = (window.document.frmdespesa.elements[myform].value.substring(6,10));

    situacao = "";
   // verifica o dia valido para cada mes
       if ((dia < 01)||(dia < 01 || dia > 30) && (  mes == 04 || mes == 06 || mes == 09 || mes == 11 ) || dia > 31) {
           situacao = "falsa";
       }

    // verifica se o mes e valido
        if (mes < 01 || mes > 12 ) {
            situacao = "falsa";
        }

    // verifica se e ano bissexto
        if (mes == 2 && ( dia < 01 || dia > 29 || ( dia > 28 && (parseInt(ano / 4) != ano / 4)))) {
            situacao = "falsa";
        }

        if (window.document.frmdespesa.elements[myform].value == "") {
            situacao = "falsa";
        }

        if (situacao == "falsa") {
            alert("Data inválida!");
            window.document.frmdespesa.elements[myform].focus();
        }
    }

function mascara_hora(hora, controle){
    var myhora = '';
    myhora = myhora + hora;
    myform = "hora" + controle;

    if (myhora.length == 2){
        myhora = myhora + ':';
        window.document.frmdespesa.elements[myform].value = myhora;
    }
    if (myhora.length == 5){
        verifica_hora();
    }
}

function verifica_hora(){
    hrs = (window.document.frmdespesa.elements[myform].value.substring(0,2));
    min = (window.document.frmdespesa.elements[myform].value.substring(3,5));

    situacao = "";
    // verifica data e hora
    if ((hrs < 00 ) || (hrs > 23) || ( min < 00) ||( min > 59)){
        situacao = "falsa";
    }

    if (window.document.frmdespesa.elements[myform].value == "") {
        situacao = "falsa";
    }

    if (situacao == "falsa") {
        alert("Hora inválida!");
        window.document.frmdespesa.elements[myform].focus();
    }
}

function TipoOs(tipo_os){
	f = document.frmdespesa;
	if (tipo_os == 'produto'){
		for (i=0; i< <? echo $qtd_produtos; ?>; i++) {
			eval('f.produto_referencia_' + i + '.disabled = false');
			eval('f.produto_descricao_' + i + '.disabled = false');
			eval('f.produto_qtde_' + i + '.disabled = false');
		}
		for (i=0; i< <? echo $qtd_pecas; ?>; i++) {
			eval('f.peca_referencia_' + i + '.disabled = true');
			eval('f.peca_referencia_' + i + '.value    = ""');
			eval('f.peca_descricao_' + i + '.disabled = true');
			eval('f.peca_descricao_' + i + '.value    = ""');
			eval('f.peca_qtde_' + i + '.disabled = true');
			eval('f.peca_qtde_' + i + '.value    = ""');
		}
	}else{
		for (i=0; i< <? echo $qtd_produtos; ?>; i++) {
			eval('f.produto_referencia_' + i + '.disabled = true');
			eval('f.produto_referencia_' + i + '.value    = ""');
			eval('f.produto_descricao_' + i + '.disabled = true');
			eval('f.produto_descricao_' + i + '.value    = ""');
			eval('f.produto_qtde_' + i + '.disabled = true');
			eval('f.produto_qtde_' + i + '.value    = ""');
		}
		for (i=0; i< <? echo $qtd_pecas; ?>; i++) {
			eval('f.peca_referencia_' + i + '.disabled = false');
			eval('f.peca_descricao_' + i + '.disabled = false');
			eval('f.peca_qtde_' + i + '.disabled = false');
		}
	}
}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
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
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<form name="frmdespesa" id="frmdespesa" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="os_sedex" value="<? echo $os_sedex ?>">

<? if (strlen ($msg_erro) > 0) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" class="formulario" width = '700'>
<tr>
	<td valign="middle" align="center" class='error'>
	<?
	// retira palavra ERROR:
		if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}
		echo $msg_erro;
		$data_msg = date ('d-m-Y h:i');
		echo `echo '$data_msg ==> $msg_erro' >> /tmp/black-os-solicitacao.err`;
	?>
	</td>
</tr>
</table>

<? } ?>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="formulario">
	<tr class="subtitulo">
		<td width="100%" colspan="4">Posto Origem da Mercadoria</td>
	</tr>
	<tr>
		<td width="2%">&nbsp;</td>
		<td width="25%">Código</td>
		<td width="57%">Nome</td>
		<td width="18%"><? if (strlen($extrato) > 0) echo "OS"; ?></td>
	</tr>
	<tr class="table_line1">
		<td width="2%">&nbsp;</td>
<? if (strlen($extrato) == 0) { ?>
		<td>
			<input type="text" name="posto_origem" size="15" maxlength="" value="<? echo $posto_origem ?>" class="frm">
			<img src="imagens/lupa.png" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem.value, document.frmdespesa.nome_posto_origem.value, 'codigo', 'origem')" style='cursor:pointer;'>
		</td>
		<td>
			<input type="text" name="nome_posto_origem" size="55" maxlength="50" value="<? echo $nome_posto_origem ?>" class="frm">
			<img src="imagens/lupa.png" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem.value, document.frmdespesa.nome_posto_origem.value, 'nome', 'origem')" style='cursor:pointer;'>
		</td>
		<td>&nbsp;</td>
<?
}else{
	echo "<td> $posto_origem </td>\n";
	echo "<td> $nome_posto_origem </td>\n";
	echo "<td> $sua_os_origem </td>\n";
}
?>
	</tr>
</table>


<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="formulario">
	<tr class="subtitulo">
		<td width="100%" colspan="4">Posto Destino da Mercadoria</td>
	</tr>
	<tr>
		<td width="2%">&nbsp;</td>
		<td width="25%">Código</td>
		<td width="57%">Nome</td>
		<td width="18%">OS</td>
	</tr>
	<tr class="table_line1">
	<td width="2%">&nbsp;</td>
<? if (strlen($extrato) == 0) { ?>
		<td>
			<input type="text" name="posto_destino" size="15" maxlength="" value="<? echo $posto_destino ?>" class="frm">
			<img src="imagens/lupa.png" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_destino.value, document.frmdespesa.nome_posto_destino.value, 'codigo', 'destino')" style='cursor:pointer;'>
		</td>
		<td>
			<input type="text" name="nome_posto_destino" size="55" maxlength="50" value="<? echo $nome_posto_destino ?>" class="frm">
			<img src="imagens/lupa.png" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_destino.value, document.frmdespesa.nome_posto_destino.value, 'nome', 'destino')" style='cursor:pointer;'>
		</td>
		<td>
			<input type="text" name="sua_os_destino" size="14" maxlength="20" value="<? echo $sua_os_destino ?>" class="frm">
		</td>
<? }else{
	echo "<td> $posto_destino </td>";
	echo "<td> $nome_posto_destino </td>";
	echo "<td> $sua_os_destino </td>";
} ?>
	</tr>
</table>


<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="formulario">
	<tr class="subtitulo"><td colspan="3">Informações</td></tr>
	<tr style="text-align:left;">
		<td width="2%">&nbsp;</td>
		<td>Observações</td>
		<td>Data</td>
	</tr>
	<tr class="table_line1">
	<td width="2%">&nbsp;</td>
<? if (strlen($extrato) == 0) { ?>
		<td><input type="text" name="obs" maxlength="" value="<? echo $obs ?>" class="frm" style="width:490px"></td>
		<td><input type="text" name="data_lancamento" size="10" maxlength="10" value="<? echo $data_lancamento ?>" OnKeyUp='mascara_data(this.value)' class="frm" style="width:85px"></td>
<? }else{
	echo "<td> $obs </td>";
	echo "<td> $data_lancamento </td>";
} ?>
	</tr>
</table>

<br>

<? if (strlen($finalizada) > 0) { ?>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
	<tr class="subtitulo">
		<td>Controle</td>
		<td>Despesas</td>
	</tr>
	<tr>
	<? if (strlen($extrato) == 0) { ?>
		<td><input type="text" name="controle" size="10" value="<? echo $controle; ?>" class='frm'></td>
		<td> R$ <input type='text' name='despesas' size='10' value='<? echo number_format($despesas,2,',','.'); ?>' class='frm'></td>
	<? }else{ 
		echo "<td> $controle </td>";
		echo "<td> R$ ".number_format($despesas,2,',','.')." </td>";
	} ?>
	</tr>
</table>
<br>
<? } ?>

<?
if (strlen($os_sedex) > 0) {
	$sqlPo =	"SELECT tbl_os_sedex_item_produto.os_sedex_item_produto
				FROM    tbl_os_sedex_item_produto
				JOIN    tbl_os_sedex USING (os_sedex)
				WHERE   tbl_os_sedex.os_sedex = $os_sedex;";
	$resPo = @pg_exec($con,$sqlPo);
	if (@pg_numrows($resPo) > 0) {
		$tipo_produto = @pg_result($resPo,0,0);
	}
}
?>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="tabela">
	<tr class="subtitulo">
		<td width="100%" colspan="3" align="left"><input type="radio" name="tipo_os" value="produto" onclick="javascript:TipoOs('produto');" <? if (strlen($os_sedex) == 0 OR strlen($tipo_produto) > 0) echo 'checked'; ?>><b>Selecione esta opção se a OS de Sedex for de produtos/equipamentos</b></td>
	</tr>
	<tr class="titulo_tabela">
		<td width="100%" colspan="3">Selecione o(s) Produto(s)</td>
	</tr>
	<tr class="titulo_coluna">
		<td width="20%">Referência</td>
		<td width="65%">Descrição</td>
		<td width="15%">Qtde</td>
	</tr>
<?
for ( $y = 0 ; $y < $qtd_produtos ; $y++ ) {
	if (strlen($os_sedex) > 0 AND strlen($erro) == 0) {

		$sql =	"SELECT tbl_os_sedex_item_produto.os_sedex_item_produto ,
						tbl_os_sedex_item_produto.qtde                  ,
						tbl_produto.referencia                          ,
						tbl_produto.descricao                           
				FROM    tbl_os_sedex_item_produto
				JOIN    tbl_produto  USING (produto)
				JOIN    tbl_os_sedex USING (os_sedex)
				WHERE   tbl_os_sedex.os_sedex = $os_sedex;";
		$res = @pg_exec ($con,$sql);

		if (@pg_numrows($res) > 0) {
			$produto_novo          = 'f';
			$qtde                  = trim(@pg_result ($res,$y,qtde));
			$os_sedex_item_produto = trim(@pg_result ($res,$y,os_sedex_item_produto));
			$referencia            = trim(@pg_result ($res,$y,referencia));
			$descricao             = trim(@pg_result ($res,$y,descricao));
		}else{
			$produto_novo          = 't';
			$os_sedex_item_produto = $_POST["os_sedex_item_produto_" .$y];
			$qtde                  = $_POST["produto_qtde_"          .$y];
			$referencia            = $_POST["produto_referencia_"    .$y];
			$descricao             = $_POST["produto_descricao_"     .$y];
		}
	}else{
		$produto_novo          = 't';
		$os_sedex_item_produto = $_POST["os_sedex_item_produto_" .$y];
		$qtde                  = $_POST["produto_qtde_"          .$y];
		$referencia            = $_POST["produto_referencia_"    .$y];
		$descricao             = $_POST["produto_descricao_"     .$y];
	}

	if (strstr($matriz, ";" . $y . ";")) $cor = "#CC3333";
	else $cor = "#D9E2EF";

	if (strlen($extrato) > 0){
		echo "<tr bgcolor='$cor'>\n";
		echo "<td> $referencia </td>\n";
		echo "<td> $descricao </td>\n";
		echo "<td> $qtde </td>\n";
		echo "</tr>\n";
	}else{
		echo "<tr class='table_line1' bgcolor='$cor'>\n";
		echo "<td>\n";
		echo "<input type='hidden' name='os_sedex_item_produto_$y' value='$os_sedex_item_produto'>";
		echo "<input type='hidden' name='produto_novo_$y' value='$produto_novo'>";
		echo "<input type='text' name='produto_referencia_$y' size='10' maxlength='15' value='$referencia' class='frm' style='width:100px'>\n";
		echo "<img src='imagens/lupa.png' onclick='javascript:fnc_pesquisa_produto (document.frmdespesa.produto_referencia_$y.value, document.frmdespesa.produto_descricao_$y.value, $y)' style='cursor:pointer;'>";
		echo "</td>\n";
		echo "<td>\n";
		echo "<input type='text' name='produto_descricao_$y' size='50' maxlength='50' value='$descricao' class='frm' style='width:410px'>\n";
		if($login_fabrica == 25){
			echo "<img src='imagens/lupa.png' onclick='javascript:fnc_pesquisa_nome_produto (document.frmdespesa.produto_referencia_$y.value, document.frmdespesa.produto_descricao_$y.value, $y)' style='cursor:pointer;'>";
		}
		echo "</td>\n";
		echo "<td>\n";
		echo "<input type='text' name='produto_qtde_$y' size='10' maxlength='10' value='$qtde' class='frm' style='width:70px'>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
}
?>
</table>

<br>

<?
if (strlen($os_sedex) > 0) {
	$sqlPe =	"SELECT tbl_os_sedex_item.os_sedex_item
				FROM    tbl_os_sedex_item
				JOIN    tbl_os_sedex USING (os_sedex)
				WHERE   tbl_os_sedex_item.os_sedex = $os_sedex;";
	$resPe = @pg_exec($con,$sqlPe);
	if (pg_numrows($resPe) > 0) {
		$tipo_peca = @pg_result($resPe,0,0);
	}
}
?>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="tabela">
	<tr class="subtitulo">
		<td width="100%" colspan="3" align="left"><input type="radio" name="tipo_os" value="peca" onclick="javascript:TipoOs('peca');" <? if (strlen($tipo_peca) > 0) echo "checked"; ?>><b>Selecione esta opção se a OS de Sedex for de peças</b></td>
	</tr>
	<tr class="titulo_tabela">
		<td width="100%" colspan="3">Selecione a(s) Peça(s)</td>
	</tr>
	<tr class="titulo_coluna">
		<td width="20%">Referência</td>
		<td width="65%">Descrição</td>
		<td width="15%">Qtde</td>
	</tr>
<?
for ( $y = 0 ; $y < $qtd_pecas ; $y++ ) {
	if (strlen($os_sedex) > 0 AND strlen($erro) == 0){

		$sql =	"SELECT tbl_os_sedex_item.os_sedex_item ,
						tbl_os_sedex_item.qtde          ,
						tbl_os_sedex_item.preco         ,
						tbl_peca.referencia             ,
						tbl_peca.descricao              
				FROM    tbl_os_sedex_item
				JOIN    tbl_peca     USING (peca)
				JOIN    tbl_os_sedex USING (os_sedex)
				WHERE   tbl_os_sedex_item.os_sedex = $os_sedex;";
		$res = @pg_exec ($con,$sql);

		if (@pg_numrows($res) > 0) {
			$peca_novo     = 'f';
			$os_sedex_item = trim(@pg_result($res,$y,os_sedex_item));
			$qtde          = trim(@pg_result($res,$y,qtde));
			$preco         = trim(@pg_result($res,$y,preco));
			$referencia    = trim(@pg_result($res,$y,referencia));
			$descricao     = trim(@pg_result($res,$y,descricao));
		}else{
			$peca_novo     = 't';
			$os_sedex_item = $_POST["os_sedex_item_"   .$y];
			$qtde          = $_POST["peca_qtde_"       .$y];
			$preco         = $_POST["peca_preco_"      .$y];
			$referencia    = $_POST["peca_referencia_" .$y];
			$descricao     = $_POST["peca_descricao_"  .$y];
		}
	}else{
		$peca_novo     = 't';
		$os_sedex_item = $_POST["os_sedex_item_"   .$y];
		$qtde          = $_POST["peca_qtde_"       .$y];
		$preco         = $_POST["peca_preco_"      .$y];
		$referencia    = $_POST["peca_referencia_" .$y];
		$descricao     = $_POST["peca_descricao_"  .$y];
	}

	if (strstr($matrizP, ";" . $y . ";")) $cor = "#CC3333";
	else $cor = "#D9E2EF";

	if (strlen($os_sedex_item) == 0 ) $peca_novo = 't';

	if (strlen($extrato) > 0){
		echo "<tr class='table_line1' bgcolor='$cor'>\n";
		echo "<td> $referencia </td>\n";
		echo "<td> $descricao </td>\n";
		echo "<td> $qtde </td>\n";
		echo "</tr>\n";
	}else{
		echo "<tr class='table_line1' bgcolor='$cor'>\n";
		echo "<td>\n";
		echo "<input type='hidden' name='os_sedex_item_$y' value='$os_sedex_item'>\n";
		echo "<input type='hidden' name='peca_novo_$y' value='$peca_novo'>\n";
		echo "<input type='text' name='peca_referencia_$y' size='10' maxlength='15' value='$referencia' class='frm' style='width:100px' disabled>\n";
		echo "<img src=\"imagens/lupa.png\" onclick=\"javascript:fnc_pesquisa_peca (document.frmdespesa.peca_referencia_$y.value, document.frmdespesa.peca_descricao_$y.value, $y)\" style='cursor:pointer;'>";
		echo "</td>\n";
		echo "<td>\n";
		echo "<input type='text' name='peca_descricao_$y' size='50' maxlength='50' value='$descricao' class='frm' style='width:410px' disabled>\n";
		echo "</td>\n";
		echo "<td>\n";
		echo "<input type='text' name='peca_qtde_$y' size='10' maxlength='10' value='$qtde' class='frm' style='width:70px' disabled>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
}
?>
</table>

<br>

<!-- ============================ Botoes de Acao ========================= -->


<? if (strlen($extrato) == 0){ ?>
	<input type='hidden' name='btn_acao' value='0'>
	<center><input type="button" value="Gravar" rel='sem_submit' class='verifica_servidor' onclick="javascript: if ( document.frmdespesa.btn_acao.value == '0' ) { document.frmdespesa.btn_acao.value='gravar'; document.frmdespesa.submit() ; } else { alert ('Aguarde submissão...'); }"></center>
<? } ?>

</form>

<script language="JavaScript">
if (document.frmdespesa.os_sedex.value.length > 0) {
	if (document.frmdespesa.os_sedex_item_produto_0.value.length > 0) {
		TipoOs('produto');
	}
	if (document.frmdespesa.os_sedex_item_0.value.length > 0) {
		TipoOs('peca');
	}
}
</script>

<?include "rodape.php";?>
