<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 24 and 1==2) {
	if (strlen($pedido)>0) {
		header("Location: pedido_cadastro_suggar.php?pedido=$pedido");
	}
	else {
		header("Location: pedido_cadastro_suggar.php");
	}
}

$login_bloqueio_pedido = $_COOKIE['cook_bloqueio_pedido'];

if ($login_fabrica == 1) {
	header("Location: pedido_blackedecker_cadastro.php");
	exit;
}

if ($login_fabrica == 42) {
	header("Location: pedido_makita_cadastro.php");
	exit;
}

if ($login_fabrica == 3 or $login_fabrica == 6 or $login_fabrica == 30) {
	header("Location: pedido_cadastro_normal.php");
	exit;
}

/* HD 102825
if($login_fabrica == 5 and $login_posto<>6359 and $login_posto<>
055) {
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDO TEMPORARIAMENTE SUSPENSO.</H4>";
	include "rodape.php";
	echo "";
	exit;
}*/

if ($login_fabrica == "15" and $login_posto <> 6359) {
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	/*Desativado conforme solicitacao Rodrigo latina hd 5086 takashi 28/09/07*/
	echo "<BR><BR><center>Desativado Temporariamente</center><BR><BR>";
	include "rodape.php";
	exit;
}

if ($login_fabrica == "50" and $login_posto <> 6359 and 1==2) {
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	// HD  36995
	echo "<BR><BR><center><b>Pedidos faturados bloqueado, favor pedir peças para compra , através do e-mail:</b> <u>carina@colormaq.com.br</u></center><BR><BR>";
	include "rodape.php";
	exit;
}

if ($login_fabrica == 14) {
	$layout_menu = 'pedido';
	$title       = "CADASTRO DE PEDIDOS DE PEÇAS";
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDO INDISPONÍVEL.</H4>";
	include "rodape.php";
	exit;
}

$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query ($con,$sql);
if (pg_fetch_result ($res,0,0) == 'f') {

	//hd 17625 - Suggar faz pedido em garantia manual
	if (pg_fetch_result ($res,0,0) == 'f' and $login_fabrica == 24) {
		$sql = "SELECT pedido_em_garantia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
	}

	if (pg_fetch_result ($res,0,0) == 'f') {
		include "cabecalho.php";
		echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
		include "rodape.php";
		exit;
	}

}

// BLOQUEIO DE PEDIDO FATURADO PARA O GM TOSCAN
if ($login_fabrica == 3 and $login_posto == 970) {
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
	include "rodape.php";
	exit;
}

// BLOQUEIO DE PEDIDO FATURADO PARA AA ELETRONICA(PEDIDO DO TULIO)
if ($login_fabrica == 51 and $login_posto == 554) {
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
	include "rodape.php";
	exit;
}

if ($login_fabrica == 3 and $login_bloqueio_pedido == 't') {
	$sql = "SELECT tbl_posto_linha.posto
			FROM   tbl_posto_linha
			WHERE  tbl_posto_linha.posto        = $login_posto
			AND    tbl_posto_linha.linha NOT IN (2,4);";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) == 0) {
		$layout_menu = 'pedido';
		$title       = "Cadastro de Pedidos de Peças";
		include "cabecalho.php";
		include "rodape.php";
		exit;
	}
}

#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
	$sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	$distribuidor_digita = pg_fetch_result ($res,0,0);
	if (strlen ($posto) == 0) $posto = $login_posto;
}

$limit_pedidos = 2;

/* Suggar liberou até 4 pedido em garantia - HD 22397 23862*/ // HD 33373 // HD 60077
$limite_posto = array(720,20235,476);
if($login_fabrica==24 AND in_array($login_posto,$limite_posto)){
	$limit_pedidos = 4;
}

if ($login_posto == 2474) {
	$limit_pedidos = 4;
}

if ($login_posto== 19566) {
	$limit_pedidos = 99;
}

#Redireciona para a Loja Virtual - Desabilitado pois ainda vai utilizar este cadastro
if ($login_fabrica == 3) {
	$sql = "SELECT estado FROM tbl_posto WHERE posto = $login_posto";
	$res = pg_query ($con,$sql);
	$estado = pg_fetch_result ($res,0,0);
	if ($estado == 'SP'){
		//header("Location: loja_completa.php");
		//exit;
	}
}

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro  = "";
$msg_debug = "";
$qtde_item = 40;

if ($login_posto == 2474) {
	$qtde_item = 70;
}

if ($login_fabrica == 11) {
	$qtde_item = 30;
}

/*HD:22543 - IGOR*/
if ($login_fabrica == 50) {
	$qtde_item = 18;
}

/*HD 70768 - Esmaltec 50 ítens  */
if ($login_fabrica == 30) {
	$qtde_item=50;
}

if ($btn_acao == "gravar") {
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido'];
	$pedido_cliente    = $_POST['pedido_cliente'];
	$transportadora    = $_POST['transportadora'];
	$linha             = $_POST['linha'];
	$observacao_pedido = $_POST['observacao_pedido'];
	$parcial           = $_POST['parcial'];

	$aux_condicao          = (strlen($condicao) == 0) ? "null" : $condicao ;
	$aux_pedido_cliente    = (strlen($pedido_cliente) == 0) ? "null" : "'". $pedido_cliente ."'";
	$aux_transportadora    = (strlen($transportadora) == 0) ? "null" : $transportadora ;
	$aux_observacao_pedido = (strlen($observacao_pedido) == 0) ? "null" : "'$observacao_pedido'" ;

	if (strlen($tipo_pedido) <> 0) {
		$aux_tipo_pedido = "'". $tipo_pedido ."'";
	} else {
		$sql = "SELECT	tipo_pedido
				FROM	tbl_tipo_pedido
				WHERE	descricao IN ('Faturado','Venda')
				AND		fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$aux_tipo_pedido = "'". pg_fetch_result($res,0,tipo_pedido) ."'";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
		if($login_fabrica==3){
			$msg_erro="Por favor, informar a linha para este pedido";
		}
	} else {
		$aux_linha = $linha ;
	}

	if ($login_fabrica == 5 AND strlen ($pedido) > 0) {
		$sql = "SELECT exportado
				FROM tbl_pedido
				WHERE pedido = $pedido
				AND fabrica = $login_fabrica;";
		$res = @pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0){
			$exportado = pg_fetch_result($res,0,exportado);
			if (strlen($exportado)>0){
				$msg_erro="Não é possível alterar. Pedido já exportado.";
			}
		}
	}

	#----------- PEDIDO digitado pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";

	if ($distribuidor_digita == 't') {
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			} else {
				$posto = pg_fetch_result ($res,0,0);
				if ($posto <> $login_posto) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					} else {
						$posto = pg_fetch_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}

	$res = pg_query ($con,"BEGIN TRANSACTION");

	if($login_fabrica==24 and $tipo_pedido==104 and $login_posto<>6359){
		$sql = "SELECT 	to_char(current_date,'MM')::INTEGER as mes,
						to_char(current_date,'YYYY') AS ano";
		$res = pg_query($con,$sql);
		$mes = pg_fetch_result($res,0,mes);
		$ano = pg_fetch_result($res,0,ano);

		if (strlen($mes) > 0) {
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			/*HD: 108583 - RETIRADO PEDIDO DO ADMIN E COM STATUS CANCELADO (14)*/
			$sql = "SELECT 	count(pedido) as qtde
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto
					AND admin is NULL
					AND status_pedido <> 14
					AND data BETWEEN '$data_inicial' AND '$data_final'
					AND tipo_pedido = 104";
			$res = pg_query($con,$sql);
			$qtde = pg_fetch_result($res,0,qtde);
			if($qtde >= $limit_pedidos){
				$msg_erro = "Seu PA já fez $limit_pedidos pedidos de garantia este mês, por favor entre em contato com o fabricante";
			}
		}
	}

	//Se quiser validar Quantidade antes de Gravar o pedido
	/*
	if(strlen($msg_erro)==0){
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$peca_referencia = trim($_POST['peca_referencia_' . $i]);
			$qtde            = trim($_POST['qtde_'            . $i]);
			
			if (strlen($qtde) == 0 OR $qtde < 1 )  {
				$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				break;
			}
		}
	}*/

if(strlen($msg_erro)==0){
	if (strlen ($pedido) == 0 ) {
		// HD  80338
		if($login_fabrica == 24 or $login_fabrica == 85) {
			$sql_campo = " ,tipo_frete ";
			if ($login_fabrica == 24) {
				$sql_valor = " ,'CIF' ";
			} else {
				$sql_valor = " ,'FOB' ";
			}
		}
		#-------------- insere pedido ------------
		$sql = "INSERT INTO tbl_pedido (
					posto                          ,
					fabrica                        ,
					condicao                       ,
					pedido_cliente                 ,
					transportadora                 ,
					linha                          ,
					tipo_pedido                    ,
					digitacao_distribuidor         ,
					obs                            ,
					atende_pedido_faturado_parcial 
					$sql_campo
				) VALUES (
					$posto                         ,
					$login_fabrica                 ,
					$aux_condicao                  ,
					$aux_pedido_cliente            ,
					$aux_transportadora            ,
					$aux_linha                     ,
					$aux_tipo_pedido               ,
					$digitacao_distribuidor        ,
					$aux_observacao_pedido         ,
					'$parcial'
					$sql_valor
				)";
//if($ip=='201.76.78.194') echo nl2br($sql);
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro) == 0){
			$res = @pg_query ($con,"SELECT CURRVAL ('seq_pedido')");
			$pedido  = @pg_fetch_result ($res,0,0);
		}
	}else{
		$sql = "UPDATE tbl_pedido SET
					condicao       = $aux_condicao       ,
					pedido_cliente = $aux_pedido_cliente ,
					transportadora = $aux_transportadora ,
					linha          = $aux_linha          ,
					tipo_pedido    = $aux_tipo_pedido
				WHERE pedido  = $pedido
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}
	if (strlen ($msg_erro) == 0) {

		$nacional  = 0;
		$importado = 0;

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$pedido_item     = trim($_POST['pedido_item_'     . $i]);
			$peca_referencia = trim($_POST['peca_referencia_' . $i]);
			$qtde            = trim($_POST['qtde_'            . $i]);
			$preco           = trim($_POST['preco_'           . $i]);

			if (strlen ($peca_referencia) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) ) {
				$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
				break;
			}
			# hd 142245
			if (strlen ($peca_referencia) > 0 AND strlen($preco) == 0 AND ($login_fabrica == 30 OR $login_fabrica == 5 OR $login_fabrica == 40)) {
				$msg_erro = "A peça $peca_referencia está sem preço!";
				$linha_erro = $i;
				break;
			}

			//verifica se a peça tem o valor da peca caso nao tenha exibe a msg
			//só verifica os precos dos campos que tenha a referencia da peça.
			if ($login_fabrica == '15' AND strlen($peca_referencia) > 0 ) {
				if($tipo_pedido <> '90') {
					if(strlen($preco) == 0) {
						$msg_erro = 'Existem peças sem preço.<br>';
						$linha_erro = $i;
						break;
					}
				}
			}
			//Adicionado a Gama Italy HD20369
			if (($login_fabrica==6 OR ($login_fabrica==51 AND $login_posto <> 4311)) and strlen($peca_referencia) > 0 and strlen($preco)==0) {
				$msg_erro = 'Existem peças sem preço.<br>';
				$linha_erro = $i;
				break;
			}

			if ($login_fabrica==45 and strlen($peca_referencia) > 0 and strlen($preco)==0) {
				$msg_erro = 'Existem peças sem preço.<br>';
				$linha_erro = $i;
				break;
			}

			$qtde_anterior = 0;
			$peca_anterior = "";
			if (strlen($pedido_item) > 0 AND $login_fabrica==3){
				$sql = "SELECT peca,qtde
						FROM tbl_pedido_item
						WHERE pedido_item = $pedido_item";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (pg_num_rows ($res) > 0){
					$peca_anterior = pg_fetch_result($res,0,peca);
					$qtde_anterior = pg_fetch_result($res,0,qtde);
				}
			}

			if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0) {
				$sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido_item = $pedido_item
						AND		pedido = $pedido";
				$res = pg_query ($con,$sql);

				/* Tira do estoque disponivel */
				if ($login_fabrica==3){
					$sql = "UPDATE tbl_peca
							SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior
							WHERE peca     = $peca_anterior
							AND   fabrica  = $login_fabrica
							AND   promocao_site IS TRUE
							AND qtde_disponivel_site IS NOT NULL";
					$res = pg_query ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if (strlen ($peca_referencia) > 0) {
				$peca_referencia = trim (strtoupper ($peca_referencia));
				$peca_referencia = str_replace ("-","",$peca_referencia);
				$peca_referencia = str_replace (".","",$peca_referencia);
				$peca_referencia = str_replace ("/","",$peca_referencia);
				$peca_referencia = str_replace (" ","",$peca_referencia);

				$sql = "SELECT  tbl_peca.peca   ,
								tbl_peca.origem ,
								tbl_peca.promocao_site,
								tbl_peca.qtde_disponivel_site ,
								tbl_peca.qtde_max_site,
								tbl_peca.multiplo_site
						FROM    tbl_peca
						WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
						AND     tbl_peca.fabrica             = $login_fabrica";
				$res = pg_query ($con,$sql);
				$peca          = pg_fetch_result ($res,0,peca);
				$promocao_site = pg_fetch_result ($res,0,promocao_site);
				$qtde_disp     = pg_fetch_result ($res,0,qtde_disponivel_site);
				$qtde_max      = pg_fetch_result ($res,0,qtde_max_site);
				$qtde_multi    = pg_fetch_result ($res,0,multiplo_site);

				if (pg_num_rows ($res) == 0) {
					$msg_erro = "Peça $peca_referencia não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca   = pg_fetch_result ($res,0,peca);
					$origem = trim(pg_fetch_result ($res,0,origem));
				}

				if ($origem == "NAC" or $origem == "1") {
					$nacional = $nacional + 1;
				}

				if ($origem == "IMP" or $origem == "2") {
					$importado = $importado + 1;
				}
				#hd 16782
				if ($nacional > 0 and $importado > 0 and $login_fabrica <> 3 and $login_fabrica <> 5 and $login_fabrica <> 8 and $login_fabrica <> 24 and $login_fabrica <> 6 and $login_fabrica <> 40 and $login_fabrica <> 72) {
					$msg_erro = "Não é permitido realizar um pedido com peça Nacional e Importada";
					$linha_erro = $i;
					break;
				}

				/*
				if ($login_fabrica == 3 && strlen($peca_referencia) > 0) {
					$sqlX =	"SELECT referencia
							FROM tbl_peca
							WHERE referencia_pesquisa = UPPER('$peca_referencia')
							AND   fabrica = $login_fabrica
							AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
					$resX = pg_query($con,$sqlX);
					if (pg_num_rows($resX) > 0) {
						$peca_previsao = pg_fetch_result($resX,0,0);
						$msg_erro = "Não há previsão de chegada da Peça $peca_previsao. Favor encaminhar e-mail para <a href='mailto:leila.beatriz@britania.com.br'>leila.beatriz@britania.com.br</a>, informando o número da ordem de serviço. Somente serão aceitas requisições via email! Não utilizar o 0800.";
					}
				}
				*/

				/* HD 27857 - Não permitir duas peças iguais no mesmo pedido */
				if ($login_fabrica == 3 && strlen($peca) > 0 AND strlen($pedido_item)==0) {
					$sqlX =	"SELECT pedido_item
							FROM tbl_pedido_item
							WHERE pedido = $pedido
							AND   peca = $peca";
					$resX = pg_query($con,$sqlX);
					if (pg_num_rows($resX) > 0) {
						$msg_erro = "Peça $peca_referencia  já selecionada. Não é permitido duas peças iguais no mesmo pedido. Altere sua quantidade.";
					}
				}
				
				if (strlen ($preco) == 0) $preco = "null";
				$preco = str_replace (",",".",$preco);

				if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {
					if (strlen($pedido_item) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
									pedido ,
									peca   ,
									qtde   ,
									preco
								) VALUES (
									$pedido ,
									$peca   ,
									$qtde   ,
									$preco
								)";
					}else{
						$sql = "UPDATE tbl_pedido_item SET
									peca = $peca,
									qtde = $qtde
								WHERE pedido_item = $pedido_item";
					}
//if($ip=='201.76.78.194') echo nl2br($sql);
					$res = @pg_query ($con,$sql);
					$msg_erro = pg_errormessage($con);


					#HD 15017
					#HD 16686
					if ($login_fabrica==3 AND $promocao_site=='t'){
						########## Validação de Quantidade #########
						$sql = "SELECT SUM(tbl_pedido_item.qtde) AS qtde
								FROM tbl_pedido
								JOIN tbl_pedido_item USING(pedido)
								WHERE  tbl_pedido.fabrica     = $login_fabrica
								AND    tbl_pedido.posto       = $login_posto
								AND    tbl_pedido.pedido      = $pedido
								AND    tbl_pedido_item.peca   = $peca";
						$res = pg_query ($con,$sql);
						$pedido_item = "";
						if (pg_num_rows ($res) > 0) {
							$qtde_pedido = pg_fetch_result ($res,0,qtde);

							if (strlen($msg_erro)==0 AND strlen($qtde_max)>0 AND $qtde_pedido > $qtde_max){
								$msg_erro = "Quantidade máxima permitida para a peça $peca_referencia é de $qtde_max.";
							}
							if (strlen($msg_erro)==0 AND strlen($qtde_disp)>0 AND $qtde_pedido > $qtde_disp){
								$msg_erro = "A peça $peca_referencia tem $qtde_disp unidades disponíveis.";
							}
							if (strlen($msg_erro)==0 AND strlen($qtde_multi)>0 AND $qtde_pedido % $qtde_multi <> 0){
								$msg_erro = "Para a peça $peca_referencia a quantidade deve ser múltiplo de $qtde_multi.";
							}
						}
					}

					/* Tira do estoque disponivel */
					if ($login_fabrica==3 AND $promocao_site=='t' AND strlen($pedido_item) > 0 AND $peca_anterior <> $peca){
						$sql = "UPDATE tbl_peca
								SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior
								WHERE peca     = $peca_anterior
								AND   fabrica  = $login_fabrica
								AND   promocao_site IS TRUE
								AND qtde_disponivel_site IS NOT NULL";
						$res = pg_query ($con,$sql);
						$msg_erro = pg_errormessage($con);
						$qtde_anterior = 0;
					}

					if ($login_fabrica==3 AND $promocao_site=='t'){
						$sql = "UPDATE tbl_peca
								SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior -$qtde
								WHERE peca     = $peca
								AND   fabrica  = $login_fabrica
								AND   promocao_site IS TRUE
								AND qtde_disponivel_site IS NOT NULL";
						$res = pg_query ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_query ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_fetch_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
						$res = @pg_query ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}//faz a somatoria dos valores das peças para verificar o total das pecas pedidas
				//Apenas para Latina.
				if($login_fabrica == 15) {
					if( strlen($preco) > 0 AND strlen($qtde) > 0){
						$total_valor = (($total_valor) + ( str_replace( "," , "." ,$preco) * $qtde));
					}
				}
			}
		}
		//modificado para a Latina pois o valor nao pode ser menor do que R$80,00 reias.
		if($login_fabrica == 15){
			if($tipo_pedido <> '90')
				{
				if($total_valor < 80){
					$msg_erro = 'O valor mínimo não foi atingido ';
				}else{
					//condicoes de pagamento depedendo do valor não se pode escolher a forma de pagamento
					if($condicao == 75 AND $total_valor < 200){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
					if($condicao == 98 AND $total_valor < 350){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
					if($condicao == 99 AND $total_valor < 600){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) { 
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	//HD 15482 //HD 27679 23/7/2008 GAMA  //HD 34765
	if((/*$login_fabrica==3 or */$login_fabrica==51) and strlen($msg_erro)==0){
		$sql="SELECT total from tbl_pedido where pedido=$pedido";
		$res=@pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$total=pg_fetch_result($res,0,total);
			if($total < 30){
				$msg_erro="O pedido faturado deve ser maior que R$ 30,00";
			}
		}
	}

	if(($login_fabrica==30) and strlen($msg_erro)==0){ // HD 70768
		$sql="SELECT total from tbl_pedido where pedido=$pedido";
		$res=@pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$total=pg_fetch_result($res,0,total);
			if($total < 60){
				$msg_erro="O pedido deve ser maior que R$ 60,00";
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1&msg=Gravado com Sucesso!");
		exit;
	} else {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT	TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
					tbl_pedido.tipo_frete                                             ,
					tbl_pedido.transportadora                                         ,
					tbl_transportadora.cnpj                   AS transportadora_cnpj  ,
					tbl_transportadora.nome                   AS transportadora_nome  ,
					tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
					tbl_pedido.pedido_cliente                                         ,
					tbl_pedido.tipo_pedido                                            ,
					tbl_pedido.produto                                                ,
					tbl_produto.referencia                    AS produto_referencia   ,
					tbl_produto.descricao                     AS produto_descricao    ,
					tbl_pedido.linha                                                  ,
					tbl_pedido.condicao                                               ,
					tbl_pedido.obs                                                    ,
					tbl_pedido.exportado                                              ,
					tbl_pedido.total_original                                         ,
					tbl_pedido.permite_alteracao
			FROM	tbl_pedido
			LEFT JOIN tbl_transportadora USING (transportadora)
			left JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto        USING (produto)
			WHERE	tbl_pedido.pedido   = $pedido
			AND		tbl_pedido.posto    = $login_posto
			AND		tbl_pedido.fabrica  = $login_fabrica ";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$data                  = trim(pg_fetch_result ($res,0,data));
		$transportadora        = trim(pg_fetch_result ($res,0,transportadora));
		$transportadora_cnpj   = trim(pg_fetch_result ($res,0,transportadora_cnpj));
		$transportadora_codigo = trim(pg_fetch_result ($res,0,transportadora_codigo));
		$transportadora_nome   = trim(pg_fetch_result ($res,0,transportadora_nome));
		$pedido_cliente        = trim(pg_fetch_result ($res,0,pedido_cliente));
		$tipo_pedido           = trim(pg_fetch_result ($res,0,tipo_pedido));
		$produto               = trim(pg_fetch_result ($res,0,produto));
		$produto_referencia    = trim(pg_fetch_result ($res,0,produto_referencia));
		$produto_descricao     = trim(pg_fetch_result ($res,0,produto_descricao));
		$linha                 = trim(pg_fetch_result ($res,0,linha));
		$condicao              = trim(pg_fetch_result ($res,0,condicao));
		$exportado             = trim(pg_fetch_result ($res,0,exportado));
		$total_original        = trim(pg_fetch_result ($res,0,total_original));
		$permite_alteracao     = trim(pg_fetch_result ($res,0,permite_alteracao));
		$observacao_pedido     = @pg_fetch_result ($res,0,obs);
	}
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$condicao       = $_POST['condicao'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$transportadora = $_POST['transportadora'];
	$linha          = $_POST['linha'];
	$codigo_posto   = $_POST['codigo_posto'];
}

$msg = $_GET['msg'];
$title       = "Cadastro de Pedidos de Peças";
$layout_menu = 'pedido';

include "cabecalho.php";

if(($login_fabrica == 3 or $login_fabrica == 51) and $login_bloqueio_pedido == 't'){
	echo "<p>";
	echo "<table border=1 align='center'><tr><td align='center'>";
	echo "<font face='verdana' size='2' color='FF0000'><b>Existem títulos pendentes de seu posto autorizado junto ao Distribuidor.
	<br>";
	if($login_fabrica == 3){
		echo "Não será possível efetuar novo pedido faturado das linhas de eletro e branca.
	<br>";
	}else{
		echo "Não será possível efetuar novo pedido faturado !.
	<br>";
	}
	echo "<br>
	Para regularizar a situação solicitamos um contato urgente com a TELECONTROL:
	<br>
	(14) 3413-6588 / distribuidor@telecontrol.com.br
	<br>
	Entrar em contato com o departamento de cobranças ou <br>
	efetue o depósito em conta corrente no <br><BR>
	Banco Bradesco<BR>
	Agência 2155-5<br>
	C/C 17427-0<br><br>
	e encaminhe um fax (14 3413-6588) com o comprovante.</b>
	<br><br>
	<b>Para visualizar os títulos <a href='posicao_financeira_telecontrol.php'>clique aqui</a></b>
	</font>";
	echo "</td></tr></table>";
	echo "<p>";
}

?>

<SCRIPT LANGUAGE="JavaScript">
function exibeTipo(){
	f = document.frm_pedido;
	if(f.linha.value == 3){
		f.tipo_pedido.disabled = false;
	}else{
		f.tipo_pedido.selectedIndex = 0;
		f.tipo_pedido.disabled = true;
	}
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	
	var url = "";
	
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim" ;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim" ;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&faturado=sim" ;
	}
	

	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.posicao		= peca_posicao;
		janela.focus();
	} else {
		if (document.getElementById('controle_blur').value == 0) {
			alert("Digite pelo menos 3 caracteres!");
		}
	}
}

function verificaTab(event) {

	var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

	if (tecla == 9) {
		return true;
	} else {
		return false;
	}

}


</SCRIPT>


<style type="text/css">
body {
	font: 80% Verdana,Arial,sans-serif;
	/* An explicit background color needed for the Safari browser. */
	/* Without this, Safari users will see black in the corners. */
	background: #FFF;
}

/* The styles below are NOT needed to make .corner() work in your page. */
/*

h1 {
	font: bold 150% Verdana,Arial,sans-serif;
	margin: 0 0 0.25em;
	padding: 0;
	color: #009;
}
h2 {
	font: bold 100% Verdana,Arial,sans-serif;
	margin: 0.75em 0 0.25em;
	padding: 0;
	color: #006;
}
ul {
	margin-top: 0.25em;
	padding-top: 0;
}
code {
	font: 90% Courier New,monospace;
	color: #33a;
	font-weight: bold;
}
#demo {

}*/



.titulo {
	background:#7392BF;
	width: 650px;
	text-align: center;
	padding: 1px 1px; /* padding greater than corner height|width */
/*	margin: 1em 0.25em;*/
	font-size:12px;
	color:#FFFFFF;
}
.titulo h1 {
	color:white;
	font-size: 120%;
}

.subtitulo {
	background:#FCF0D8;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 10px auto;
	color:#392804;
}
.subtitulo h1 {
	color:black;
	font-size: 120%;
}

.content {
	background:#CDDBF1;
	width: 600px;
	text-align: center;
	padding: 5px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:black;
}

.content h1 {
	color:black;
	font-size: 120%;
}

.extra {
	background:#BFDCFB;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.extra span {
	color:#FF0D13;
	font-size:14px;
	font-weight:bold;
	padding-left:30px;
}

.error {
	background:#ED1B1B;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#FFFFFF;
	font-size:12px;
}

.sucesso {
	background:#ED1B1B;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#FFFFFF;
	font-size:12px;
}

.error h1 {
	color:#FFFFFF;
	font-size:14px;
	font-size:normal;
	text-transform: capitalize;
}

.inicio {
	background:#8BBEF8;
	width: 600px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.inicio h1 {
	color:white;
	font-size: 105%;
	font-weight:bold;
}

.subinicio {
	background:#E1EEFD;
	width: 550px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.subinicio h1 {
	color:white;
	font-size: 105%;
}

#tabela {
	font-size:12px;
}
#tabela td{
	font-weight:bold;
}

.xTabela{
	font-family: Verdana, Arial, Sans-serif;
	font-size:12px;
	padding:3px;
}

.xTabela td{
	/*border-bottom:2px solid #9E9E9E;*/
}

</style>

<style type="text/css">

#layout{
	width: 780px;
	margin:0 auto;
}

ul#split, ul#split li{
	margin:50px;
	margin:0 auto;
	padding:0;
	width:780px;
	list-style:none
}

ul#split li{
	float:left;
	width:780px;
	margin:0 10px 10px 0
}

ul#split h3{
	font-size:14px;
	margin:0px;
	padding: 5px 0 0;
	text-align:center;
	font-weight:bold;
	color:white;
}
ul#split h4{
	font-size:90%
	margin:0px;
	padding-top: 1px;
	padding-bottom: 1px;
	text-align:center;
	font-weight:bold;
	color:white;
}

ul#split p{
	margin:0;
	padding:5px 8px 2px
}

ul#split div{
	background: #E6EEF7
}

li#one{
	text-align:left;

}

li#one div{
	border:1px solid #596D9B
}
li#one h3{
	background: #7392BF;
}

li#one h4{
	background: #7392BF;
}

.coluna1{
	width:250px;
	font-weight:bold;
	display: inline;
	float:left;
}

</style>

<!-- Bordas Arredondadas para a JQUERY -->
<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$(".titulo").corner("round");
		$(".subtitulo").corner("round");
		$(".content").corner("round 10px");
		$(".error").corner("dog2 10px");
		$(".extra").corner("dog");
		$(".inicio").corner("round");
		$(".subinicio").corner("round");
		$("input[name^=qtde_]").numeric();
});
</script>

<!-- Bordas Arredondadas para a NIFTY -->
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type="text/javascript">
function fnc_makita_preco (linha_form) {

	condicao = window.document.frm_pedido.condicao.value ;
	posto    = <?= $login_posto ?>;
	
	campo_preco = 'preco_' + linha_form;
	document.getElementById(campo_preco).value = "0,00";

	peca_referencia = 'peca_referencia_' + linha_form;
	peca_referencia = document.getElementById(peca_referencia).value;

	url = 'makita_valida_regras.php?linha_form=' + linha_form + '&posto=<?= $login_posto ?>&produto_referencia=' + peca_referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';
	requisicaoHTTP ('GET', url , true , 'fnc_makita_responde_preco');

}

function fnc_makita_responde_preco (campos) {
	campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
	campos = campos.substring (0,campos.indexOf('</preco>'));
	campos_array = campos.split("|");

	preco      = campos_array[0] ;
	linha_form = campos_array[1] ;

	campo_preco = 'preco_' + linha_form;
	document.getElementById(campo_preco).value = preco;
	fnc_calcula_total(linha_form);
}
function fnc_calcula_total (linha_form) {
	var total = 0;
	preco = document.getElementById('preco_'+linha_form).value;
	qtde = document.getElementById('qtde_'+linha_form).value;
	preco = preco.replace(',','.');
	if (qtde && preco){
		total = qtde * preco;
		total = total.toFixed(2); 
	}

	document.getElementById('sub_total_'+linha_form).value = total;

	//Totalizador
	var total_pecas = 0;
	$("input[rel='total_pecas']").each(function(){
		if ($(this).val()){
			tot = $(this).val();
			tot = parseFloat(tot);
			total_pecas += tot;
		}
	});

	<?if (!in_array($login_fabrica,array(24,30))) { ?>
	document.getElementById('total_pecas').value = total_pecas.toFixed(2);
	<?}?>
}

function atualiza_proxima_linha(linha_form){
	var produto_referencia = document.getElementById('produto_referencia_'+linha_form).value;
	var produto_descricao  = document.getElementById('produto_descricao_'+linha_form).value;

	var proxima_linha = linha_form + 1;

	if (document.getElementById('produto_descricao_'+proxima_linha)){
		if (! document.getElementById('produto_descricao_'+proxima_linha).value){
			document.getElementById('produto_referencia_'+proxima_linha).value = produto_referencia;
			document.getElementById('produto_descricao_'+proxima_linha).value = produto_descricao;
		}
	}

}

</script>


<!--  Mensagem de Erro-->
<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
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
	?>
	<div id="layout">
	<div class="sucesso">
	<? echo $erro . $msg_erro; ?>
	</div>
	</div>
<? } ?>

<?
if (strlen ($msg) > 0) {
	
	?>
	<div id="layout">
	<div class="sucesso">
	<? echo $msg; ?>
	</div>
	</div>
<? } ?>
<?


$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
$res = pg_query ($con,$sql);

if (pg_num_rows ($res) > 0) {
	$frase = "PREENCHA SEU PEDIDO DE COMPRA/GARANTIA";
}else{
	$frase = "PREENCHA SEU PEDIDO DE COMPRA";
}
?>

<br>

<div id="layout">
	<div class="titulo"><h1>Cadastro de Pedido</h1></div>
</div>

<div id="layout">
	<div class="subtitulo">
	<?
	if ($login_fabrica == 51) {
		echo "
		<font size='4' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>*** Atenção esta tela é somente para pedidos fora de garantia ***</b></font>
		<br><br>
		<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#990000'><b>*** Pedidos realizados no valor abaixo de R$30,00 não serão faturados ***</b></font>
		<br><br>";
	}

	if($login_fabrica == 40) {
		echo "<p>MÍNIMO PARA FATURAMENTO R$90,00</p>
				<p>À PARTIR DE R$90,00 - 30 DIAS</p>
				<p>À PARTIR DE R$241,00 - 30/60 DIAS</p>
				<p>À PARTIR DE R$450,00 - 30/60/90 DIAS</p>";
	}
	if ($login_fabrica == 3) { ?>
		<!--<b>Atenção Linha Áudio e Vídeo:</b> Pedidos de peças para linha de áudio e vídeo feitos nesta tela devem ser para uso em consertos fora da garantia, e gerarão fatura e duplicata.<br>Pedidos para conserto em garantia serão gerados automaticamente pela Ordem de Serviço.<br>Leia o Manual e a Circular na primeira página.
		<br><br>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='#990000'><b>*** Pedidos realizados no valor abaixo de R$30,00 não serão faturados ***</b></font>
		<br><br>
		-->
		Não há Valor Mínimo de Pedido de Compra de Peças. <br>
		A restrição será no faturamento e envio de peças pelo depósito da Britânia.<br>
		<b>Valor mínimo de faturamento R$ 30,00.</b> <br>
		Quando houver pedido de peças em garantia será utilizado o mesmo frete.<br>
		Pedidos pendentes de compra superiores a 60 dias serão avaliados e poderão ser excluídos.<br>
	</td>
	<? }elseif ($login_fabrica == 15) { ?>
		<b>AVISO</b> <br>Peças <b>plásticas</b> em garantia, somente para produtos com até <b>90 dias</b> da compra.
		<br>
		<br>
		<b>Condições de Pagamento:</b> <br> Até R$ 200,00 30 dias ; Até R$ 350,00 30-45 dias <br> Até R$ 600,00 , 30-60 dias ; Acima de R$ 600,00 , 30-60-90 dias
		<br>
		<br>
		<b>*** Pedidos abaixo de R$80,00 não serão faturados ***</b>
		<br>
		<br>
		<b>Despesas de frete de peças faturadas serão por conta do Posto Autorizado.</b>
		<br>
			Sudeste/Sul: R$ 28,36<br>
			Centroeste: R$ 30,00<br>
			Norte/Nordeste: R$ 33.80<br>
		<br>
		<b>Despesas de frete de peças em garantia serão por conta da LATINATEC.</b>
		<br>
		<br>
	<? }else{ ?>
		<b>Atenção:</b> Pedidos a prazo dependerão de análise do departamento de crédito.
	<? } ?>

<?
/*
if($login_fabrica ==45){
echo "		<br><br><b>Atenção:</b> <font color ='red'><br>Pedido suspenso até terça-feira (10/02/2009).
<br></font>
Ass. NKS.";
exit;
}
*/
?>
	</div>
</div>

<!-- OBSERVAÇÕES -->
<div id="layout">
<? if ($login_fabrica<>30) {   //HD 70768-1 - Retirar mensagem na Esmaltec  ?>

	<div class="content">
	<? if($login_fabrica<>24){ ?>
		Para efetuar um pedido por modelo do produto, informe a referência <br> ou descrição e clique na lupa, ou simplesmente clique na lupa.

	<? }else {

		echo "O fabricante limita em $limit_pedidos pedidos de garantia por mês.<br>";

		$sql = "SELECT 	to_char(current_date,'MM')::INTEGER as mes,
						to_char(current_date,'YYYY') AS ano";
		$res = pg_query($con,$sql);
		$mes = pg_fetch_result($res,0,mes);
		$ano = pg_fetch_result($res,0,ano);

		if(strlen($mes)>0){
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			$sql = "SELECT 	count(pedido) as qtde
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto
					AND data BETWEEN '$data_inicial' AND '$data_final'
					AND tipo_pedido = 104";
			$res = pg_query($con,$sql);
			$qtde = pg_fetch_result($res,0,qtde);
			if($qtde < 2){
				//echo "<br>";
				echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês</b>";
				//echo "<br>";
			}else{
				//echo "<br>";
				echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês, caso necessite de outro pedido em garantia por favor entre em contato com o fabricante.</b>";
				//echo "<br>";
			}
		}
	}
   }    // fim HD 70768-1
	//alterado por Wellington 13-10-2006 chamado 575
	if ($login_fabrica == "11") { ?>
			<span> Somente Pedidos de Venda </span>
			<? echo "Nesta tela devem ser digitados somente pedidos de <B>VENDA</B>. Pedidos de peça na <B>GARANTIA</B> devem ser feitos somente através da abertura da Ordem de Serviço.";
				?>
	<? } ?>

		<!-- PERMITIR ALTERAÇÕES  -->
		<? if($login_fabrica == 7 AND $total_original > 0 AND $permite_alteracao == 't'){ ?>
			<br><br><b>Atenção:</b> o pedido deve ser superior a R$ <?=number_format($total_original,2,",",".")?>
		<?}?>
		<? if($login_fabrica == 30){ //HD 707682-3 - Aviso valor mínimo ?>
		<DIV class='content'>
			<H1 style='font-size: 1em;color:#B00'>
				<b>Atenção:</b> O pedido só será incluído se tiver no mínimo <B>R$ 60,00</B>
			</H1>
		</DIV>
		<?}?>
	</div>
</div><?php

if ($login_fabrica == 51 || $login_fabrica == 81) {// HD 221731
	
	$sql2    = "SELECT tbl_posto_extra.atende_pedido_faturado_parcial
				  FROM tbl_posto_extra
				  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				 WHERE tbl_posto_extra.posto = $login_posto";

	$rs2     = pg_exec($con,$sql2);
	$parcial = trim(pg_result($rs2,0,'atende_pedido_faturado_parcial'));

	if ($parcial == 'f') {//HD 221731?>
		<div id="layout">
			<div class="subtitulo">
				<p>
					<b>Dica:</b> Caso você tenha algumas peças que não podem ser atendida parcialmente,
					<br />
					favor fazer um pedido separado somente com estas peças e selecione a opção <u>atendimento parcial!</u>
				</p>
			</div>
		</div><?php
	}

	if ($parcial == 'f') {//HD 221731?>
		<div id="layout">
			<div class="content">
				<p>
					<b>Nota: </b> Colocando que não pode ser atendido parcial, somente será faturado o pedido
					<br />
					se todas as peças estiverem em nossos estoques, caso contrário,
					<br />
					em 60 dias será cancelado automaticamente este pedido!
				</p>
			</div>
		</div><?php
	}
	
}?>

<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" id="pedido" value="<? echo $pedido; ?>">
<input class="frm" type="hidden" name="voltagem" id="voltagem" value="<? echo $voltagem; ?>">
<?php
/**
 * HD 254266
 * Campo criado para controlar quando é executado a ação da função fnc_pesquisa_peca_lista
 * Se é no onblur ou onclick, não pude alterar muito a função pois existiam outros arquivos usando
 * Senao teria passado como parametro dentro da função.
 */
?>
<input type="hidden" name="controle_blur" id="controle_blur" value="1" />

<center>
<p>
<? if ($distribuidor_digita == 't' AND $ip == '201.0.9.216') { ?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top' style='font-size:12px'>
			<td nowrap align='center'>
			Distribuidor pode digitar pedidos para seus postos.
			<br />
			Digite o código do posto
			<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
			ou deixe em branco para seus próprios pedidos.
			</td>
		</tr>
	</table>
<? } ?>
</center>

<br />

<!-- INICIA DIVISÃO -->
<ul id="split">
<li id="one">
<h3><? echo $frase; ?></h3>
<div>

	<? if ($login_fabrica <> "24" and $login_fabrica <> "30") { //HD 70768-2 Retirar campo 'pedido do cliente' ?>
		<p><span class='coluna1'>Pedido do Cliente</span>
			<input class="frm" type="text" name="pedido_cliente" size="15" maxlength="20" value="<? echo $pedido_cliente ?>">
		</p>
	<?}?>

	<?
	$res = pg_query ($con, "SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");

	#permite_alteracao - HD 47695
	if (pg_fetch_result ($res,0,'pedido_escolhe_condicao') == 'f' OR $permite_alteracao == 't') {
		echo "<input type='hidden' name='condicao' value=''>";
	}else{?>

	<p><span class='coluna1'>Condição Pagamento</span>
		<select size='1' name='condicao' class='frm'><?php
			//hd 17625
			if ($login_fabrica == 24 or $login_fabrica == 81 or $login_fabrica > 86 ) {
				$sql = "SELECT pedido_em_garantia, pedido_faturado
						FROM tbl_posto_fabrica
						WHERE fabrica = $login_fabrica
						AND   posto   = $login_posto;";
				$res = pg_query($con,$sql);

				$pede_em_garantia = pg_fetch_result($res,0,pedido_em_garantia);
				$pede_faturado    = pg_fetch_result($res,0,pedido_faturado);
			}
			if ($login_posto == 4311) {
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						JOIN     tbl_posto_condicao USING (condicao)
						WHERE    tbl_posto_condicao.posto = $login_posto
						AND      tbl_condicao.fabrica     = $login_fabrica ";
			} else {
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						JOIN     tbl_posto_condicao USING (condicao)
						WHERE    tbl_posto_condicao.posto = $login_posto
						AND      tbl_condicao.fabrica     = $login_fabrica
						AND      tbl_condicao.visivel       IS TRUE
						AND      tbl_posto_condicao.visivel IS TRUE ";
			}

			//hd 17625
			if ($login_fabrica == 24 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
				$sql = " AND tbl_condicao.condicao = 928 ";
			}
			if ($login_fabrica == 81 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
				$sql = " AND tbl_condicao.condicao = 1397 ";
			}

			$sql = "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
			$xxx  = $sql;

			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) == 0 or $login_fabrica==2) {
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						WHERE    tbl_condicao.fabrica = $login_fabrica
						AND      tbl_condicao.visivel IS TRUE ";

				//hd 17625
				if ($login_fabrica == 24 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
					$sql = " AND tbl_condicao.condicao = 928 ";
				}

				if ($login_fabrica == 81 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
					$sql = " AND tbl_condicao.condicao = 1397 ";
				}

				$sql = "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
				$res = pg_query ($con,$sql);
			}

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				#HD 107982
				if ($login_fabrica == 24) {
					if (pg_fetch_result($res,$i,condicao) <> 928) {
						echo "<option value='" . pg_fetch_result ($res,$i,condicao) . "'";
						if (pg_fetch_result($res,$i,condicao) == $condicao) echo " selected";
						echo ">" . pg_fetch_result ($res,$i,descricao) . "</option>";
					}
				} else {
					echo "<option value='" . pg_fetch_result ($res,$i,condicao) . "'";
					if (pg_fetch_result ($res,$i,condicao) == $condicao) echo " selected";
					echo ">" . pg_fetch_result ($res,$i,descricao) . "</option>";
				}
			}?>
		</select>
	</p>

<? } ?>

		<?
		//VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
		$sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica
				WHERE fabrica=$login_fabrica AND posto=$login_posto";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$garantia_antecipada = pg_fetch_result($res,0,0);
			if($garantia_antecipada <> "t") {
				$garantia_antecipada ="f";
			}
		}
		?>

		<p><span class='coluna1'>Tipo de Pedido</span>
		<?
		// se posto pode escolher tipo_pedido

		$sql = "SELECT   *
				FROM     tbl_posto_fabrica
				WHERE    tbl_posto_fabrica.posto   = $login_posto
				AND      tbl_posto_fabrica.fabrica = $login_fabrica";
		if($login_fabrica<>24) {
			$sql = " AND      tbl_posto_fabrica.pedido_em_garantia IS TRUE;";
		}
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo "<select size='1' name='tipo_pedido' class='frm'>";
			$sql = "SELECT   *
					FROM     tbl_tipo_pedido
					WHERE    fabrica = $login_fabrica ";
			if($login_fabrica==24) {
				$sql = " AND tipo_pedido not in(107,104)";

				#HD 17625
				if ($pede_faturado == 'f') {
					$sql = " AND tipo_pedido <> 103 ";
				}
			}
			$sql = " ORDER BY tipo_pedido ";
			$res = pg_query ($con,$sql);
			$xxx = $sql;

			# AND      (garantia_antecipada is false or garantia_antecipada is null)
			# takashi -  coloquei -> AND      (garantia_antecipada is false or garantia_antecipada is null)
			# efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
				#HD 107982
				if($login_fabrica==24){
					if( pg_fetch_result ($res,$i,tipo_pedido) <> 104){
						echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
						if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
							echo " selected";
						}
						echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
					}
				}else{
					echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
					if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
						echo " selected";
					}
					echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
				}
			}

			if($garantia_antecipada=="t"){
				//takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica
						AND garantia_antecipada is true ";
				if($login_fabrica==24) {
					$sql = " and tipo_pedido <> 107";
				}
				 $sql = " ORDER BY tipo_pedido ";
				 $xxl =  $sql;
				$res = pg_query ($con,$sql);

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					#HD 107982
					if($login_fabrica==24){
						if(pg_fetch_result ($res,$i,tipo_pedido) <> 104){
							echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
							if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
								echo " selected";
							}
							echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
						}
					}else{
						echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
						if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido) {
							echo " selected";
						}
						echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
					}
				}
			}
			echo "</select>";

		}else{
			echo "<select size='1' name='tipo_pedido' class='frm' ";
			if ($login_fabrica == 3) {
				echo "disabled";
			}
			echo ">";
			$sql = "SELECT   *
					FROM    tbl_tipo_pedido
					WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
				       OR	tbl_tipo_pedido.descricao ILIKE '%Venda%')
					AND     tbl_tipo_pedido.fabrica = $login_fabrica
					AND     (garantia_antecipada is false or garantia_antecipada is null)
					ORDER BY tipo_pedido;";

			#HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't'){
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica ";
				if (strlen($tipo_pedido)>0){
					$sql = " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
				}
				$sql = " ORDER BY tipo_pedido;";
			}

			$res = pg_query ($con,$sql);

			# takashi -  coloquei : AND      (garantia_antecipada is false or garantia_antecipada is null)
			# efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
				echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
				if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido) echo " selected";
				echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
			}
			if($garantia_antecipada=="t"){
				#takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica
						AND garantia_antecipada is true
						ORDER BY tipo_pedido ";
				$res = pg_query ($con,$sql);

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
					if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
						echo " selected";
					}
					echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
				}
			}
			echo "</select>";
		}
		?>
		</p>

		<?#-------------------- Transportadora -------------------

		#HD 47695 - Para pedidos a serem alterados, nao mostrar a transportadora.
		if ($permite_alteracao != 't'){
			$sql = "SELECT	tbl_transportadora.transportadora        ,
							tbl_transportadora.cnpj                  ,
							tbl_transportadora.nome                  ,
							tbl_transportadora_fabrica.codigo_interno
					FROM	tbl_transportadora
					JOIN	tbl_transportadora_fabrica USING(transportadora)
					JOIN	tbl_fabrica USING(fabrica)
					WHERE	tbl_transportadora_fabrica.fabrica        = $login_fabrica
					AND		tbl_transportadora_fabrica.ativo          = 't'
					AND		tbl_fabrica.pedido_escolhe_transportadora = 't'";
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
			?>
				<p><span class='coluna1'>Transportadora</span>
				<?
					if (pg_num_rows ($res) <= 20) {
						echo "<select name='transportadora' class='frm'>";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
							echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
							if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
							echo ">";
							echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
					}else{
						echo "<input type='hidden' name='transportadora' value='' value='$transportadora'>";
						echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";

						#echo "<input type='text' name='transportadora_cnpj' size='20' maxlength='18' value='$transportadora_cnpj' class='textbox' >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_cnpj,'cnpj')\" style='cursor:pointer;'>";

						echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='javascript: lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

						//echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' onblur='javascript: lupa_transportadora_nome.click()'>&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
						echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
					}
					?>
				</p>
		<? }
		}?>

		<?#-------------------- Linha do pedido -------------------

		#HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
		if ($permite_alteracao == 't' and strlen($linha)>0){
			?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
		}else{
			$sql = "SELECT	tbl_linha.linha            ,
							tbl_linha.nome
					FROM	tbl_linha
					JOIN	tbl_fabrica USING(fabrica)
					JOIN	tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
											AND tbl_posto_linha.linha = tbl_linha.linha
					WHERE	tbl_fabrica.linha_pedido is true
					AND     tbl_linha.fabrica = $login_fabrica ";

			// BLOQUEIO DE PEDIDOS PARA A LINHA ELETRO E BRANCA EM
			// CASO DE INADIMPLÊNCIA
			// Não bloqueia pedidos do JANGADA - CARMEM LUCIA
			if ($login_fabrica == 3 and $login_bloqueio_pedido == 't' and $login_posto <> 1053) {
				$sql = "AND tbl_linha.linha NOT IN (2,4)";
			}
			if ($login_fabrica == 51) {
				$sql = " AND tbl_linha.ativo IS TRUE ";
			}
			#permite_alteracao - HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
				$sql = " AND tbl_linha.linha = $linha ";
			}
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {?>
				<p><span class='coluna1'>Linha</span><?php
						echo "<select name='linha' class='frm' ";
						if ($login_fabrica == 3) echo " onChange='exibeTipo()'";
						echo ">";
						echo "<option selected></option>";
						for ($i = 0; $i < pg_num_rows($res); $i++) {
							echo "<option value='".pg_fetch_result($res,$i,'linha')."' ";
							if ($linha == pg_fetch_result($res,$i,'linha') ) echo " selected";
							echo ">";
							echo pg_fetch_result($res,$i,'nome');
							echo "</option>\n";
						}
						echo "</select>";?>
				</p><?php
			}
		}
		
		if ($login_fabrica == 51 || $login_fabrica == 81) {
			
			if ($parcial == 'f') {//HD 221731

				echo "<p><span class='coluna1'>Este pedido pode ser atendido parcial?</span>";
				echo "<select name='parcial' class='frm'>";
					echo "<option value='t'>Sim</option>";
					echo "<option value='f'>Não</option>";
				echo "</select>";

			} else {

				echo "<input type='hidden' name='parcial' id='parcial' value='t' />";

			}

		} else {

			echo "<input type='hidden' name='parcial' id='parcial' value='t' />";

		}

		if (!in_array($login_fabrica,array(24,30,42))) { ?>
		<h4>Peças</h4>
		
		<br />
		
		<font color='red'><center><b>ATENÇÃO:</b> Utilize o produto para facilitar a pesquisa da peça! Ao escolher o produto a pesquisa restringe
		<br />
		a lista básica (vista explodida) de peças do produto escolhido. Pode escolher mais de um produto por pedido!</font>
		<? } ?>

		<br />

		<? if($login_fabrica == 24 or $login_fabrica == 42 or $login_fabrica == 30){ //HD 70768-Retirar estes campos para a Esmaltec ?>
			<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
		<? } ?>

		<!-- Peças -->
		<p>
		<table border="0" cellspacing="0" cellpadding="2" align="center" class='xTabela'>
			<tr height="20" bgcolor="#CDDBF1" nowrap>
				<? 
				//HD 142667
				if($login_fabrica <> 24 and $login_fabrica <> 30 and $login_fabrica <> 42){ ?>
				<td align='left'>Ref. Produto</td>
				<td align='left'>Desc. Produto</td>
				<?}?>
				<td align='left'><?
				if($login_fabrica<>6){?>Ref. Componente<? }else{?> Código Componente<? }?></td>
				<td align='left'>Descrição Componente</font></td>
				<td align='center'>Qtde</td>
				<? if ($login_fabrica != 14 AND $login_fabrica!=24) { ?>
				<td align='center'>Preço Unit.</td>
				<td align='center'>Total</td>
				<? } ?>
			</tr>

			<?
			$total_geral = 0;

			for ($i = 0 ; $i < $qtde_item ; $i++) {

				if (strlen($pedido) > 0){	// AND strlen ($msg_erro) == 0
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_peca.referencia        ,
									tbl_peca.descricao         ,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco
							FROM  tbl_pedido
							JOIN  tbl_pedido_item USING (pedido)
							JOIN  tbl_peca        USING (peca)
							WHERE tbl_pedido_item.pedido = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido_item.pedido_item";

					$res = pg_query ($con,$sql);

					if (pg_num_rows($res) > 0) {
						$pedido_item     = trim(@pg_fetch_result($res,$i,pedido_item));
						$peca_referencia = trim(@pg_fetch_result($res,$i,referencia));
						$peca_descricao  = trim(@pg_fetch_result($res,$i,descricao));
						$qtde            = trim(@pg_fetch_result($res,$i,qtde));
						$preco           = trim(@pg_fetch_result($res,$i,preco));
						if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');

						$produto_referencia = '';
						$produto_descricao  = '';
					}else{
						$produto_referencia= $_POST["produto_referencia_"     . $i];
						$produto_descricao = $_POST["produto_descricao_" . $i];
						$pedido_item     = $_POST["pedido_item_"     . $i];
						$peca_referencia = $_POST["peca_referencia_" . $i];
						$peca_descricao  = $_POST["peca_descricao_"  . $i];
						$qtde            = $_POST["qtde_"            . $i];
						$preco           = $_POST["preco_"           . $i];
					}
				}else{
					$produto_referencia= $_POST["produto_referencia_"     . $i];
					$produto_descricao = $_POST["produto_descricao_" . $i];
					$pedido_item     = $_POST["pedido_item_"     . $i];
					$peca_referencia = $_POST["peca_referencia_" . $i];
					$peca_descricao  = $_POST["peca_descricao_"  . $i];
					$qtde            = $_POST["qtde_"            . $i];
					$preco           = $_POST["preco_"           . $i];
				}

				$peca_referencia = trim ($peca_referencia);

				#--------------- Valida Peças em DE-PARA -----------------#
				$tem_obs = false;
				$linha_obs = "";

				$sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
				$resX = pg_query ($con,$sql);

				if (pg_num_rows ($resX) > 0) {
					$linha_obs = "Peça original " . $peca_referencia . " mudou para o código acima <br>&nbsp;";
					$peca_referencia = pg_fetch_result ($resX,0,0);
					$tem_obs = true;
				}

				#--------------- Valida Peças Fora de Linha -----------------#
				$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";

				$resX = pg_query ($con,$sql);
				if (pg_num_rows ($resX) > 0) {
					$libera_garantia = pg_fetch_result ($resX,0,libera_garantia);
					#17624
					if ($login_fabrica==3 AND $libera_garantia=='t'){
						$linha_obs = "Peça acima está fora de linha. Disponível somente para garantia. Caso necessário, favor contatar a Assistência Técnica Britânia <br>&nbsp;";
					}else{
						$linha_obs = "Peça acima está fora de linha <br>&nbsp;";
					}
					$tem_obs = true;
				}

				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_query ($con,$sql);
					if (pg_num_rows ($resX) > 0) {
						$peca_descricao = pg_fetch_result ($resX,0,0);
					}
				}

				$peca_descricao = trim ($peca_descricao);

				$cor="";
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($tem_obs) $cor='#FFCC33';
			?>
				<tr bgcolor="<? echo $cor ?>" nowrap>
					<? if($login_fabrica <> 24 and $login_fabrica <> 30 and $login_fabrica <> 42){?>
						<td align='left'>
							<input class="frm" type="text" name="produto_referencia_<?=$i?>" onFocus="this.select()" id="produto_referencia_<?=$i?>" size="7" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.produto_descricao_<?=$i?>,'referencia',document.frm_pedido.produto_voltagem_<?=$i?>)">
						</td>
						<td align='left'>
							<input class="frm" type="text" name="produto_descricao_<?=$i?>" onFocus="this.select()" id="produto_descricao_<?=$i?>" size="12" value="<? echo $produto_descricao ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' align='absmiddle' alt="Clique para pesquisar pela descrição do produto" onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.produto_descricao_<?=$i?>,'descricao',document.frm_pedido.produto_voltagem_<?=$i?>)">
							<input type="hidden" name="produto_voltagem_<?=$i?>">
						</td>
					<?}?>

					<td align='left'>
						<input type="hidden" name="pedido_item_<? echo $i ?>" size="15" value="<? echo $pedido_item; ?>">
						<input class="frm" type="text" name="peca_referencia_<?=$i?>" id="peca_referencia_<?=$i?>" size="15" value="<? echo $peca_referencia; ?>" tabindex="<?=($i*4)+1?>"<?php
						//HD 254266
						if ($login_fabrica == 14) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia_<?=$i?>.value , document.frm_pedido.peca_referencia_<?=$i?> , document.frm_pedido.peca_descricao_<?=$i?> , document.frm_pedido.posicao, 'referencia')}" <?
                        } else if (in_array($login_fabrica,array(24,30,42))) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista (document.getElementById('produto_referencia_<?=$i?>').value, document.getElementById('peca_referencia_<?=$i?>'), document.getElementById('peca_descricao_<?=$i?>'), document.getElementById('preco_<?=$i?>'), document.getElementById('voltagem'), 'referencia', document.getElementById('qtde_<?=$i?>').value)}" <?php
                        } else {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista (document.getElementById('produto_referencia_<?=$i?>').value, document.getElementById('peca_referencia_<?=$i?>'), document.getElementById('peca_descricao_<?=$i?>'), document.getElementById('preco_<?=$i?>'), document.getElementById('voltagem'), 'referencia', document.getElementById('qtde_<?=$i?>').value)}" <?php
                        }?>
						/>
						<img src='imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle'
						<? if ($login_fabrica == 14 ) { ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia_<?=$i?>.value , document.frm_pedido.peca_referencia_<?=$i?>, document.frm_pedido.peca_descricao_<?=$i?> , document.frm_pedido.posicao, 'referencia')" <?
						}elseif(in_array($login_fabrica,array(24,30,42))){ ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<?=$i?>, window.document.frm_pedido.peca_descricao_<?=$i?>, window.document.frm_pedido.preco_<?=$i?>, window.document.frm_pedido.voltagem,'referencia', document.getElementById('qtde_<?=$i?>').value)" <? }else{?>
						onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia_<?=$i?>.value, window.document.frm_pedido.peca_referencia_<? echo $i ?>,window.document.frm_pedido.peca_descricao_<?=$i?>,window.document.frm_pedido.preco_<?=$i?>, window.document.frm_pedido.voltagem,'referencia', document.getElementById('qtde_<?=$i?>').value)"
						<?} ?> >
					</td>
					<td align='left'>
						<input type="hidden" name="posicao">
						<input class="frm" type="text" name="peca_descricao_<? echo $i ?>" id="peca_descricao_<? echo $i ?>" size="20" value="<? echo $peca_descricao ?>" tabindex="<?=($i*4)+2?>"<?php
						//HD 254266
						if ($login_fabrica == 14) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista_intel (document.getElementById('produto_referencia_<?=$i?>').value, document.frm_pedido.peca_referencia_<?=$i?>, document.frm_pedido.peca_descricao_<?=$i?>, document.frm_pedido.posicao, 'descricao')}" <?
                        } else if (in_array($login_fabrica,array(24,30,42))) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista (document.getElementById('produto_referencia').value, document.getElementById('peca_referencia_<?=$i?>'), document.getElementById('peca_descricao_<?=$i?>'), document.getElementById('preco_<?=$i?>'), document.getElementById('voltagem'), 'descricao', document.getElementById('qtde_<?=$i?>').value)}" <?php
                        } else {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista (document.getElementById('produto_referencia_<?=$i?>').value, document.getElementById('peca_referencia_<?=$i?>'), document.getElementById('peca_descricao_<?=$i?>'), document.getElementById('preco_<?=$i?>'), document.getElementById('voltagem'),'descricao', document.getElementById('qtde_<?=$i?>').value)}"<?php
                        }?>
						/>
						<img src='imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' <? if ($login_fabrica == 14 ) { ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia_<?=$i?>.value , document.frm_pedido.peca_referencia_<?=$i?> , document.frm_pedido.peca_descricao_<?=$i?> , document.frm_pedido.posicao, 'descricao')" <? }elseif(in_array($login_fabrica,array(24,30,42))){ ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<?=$i?>, window.document.frm_pedido.peca_descricao_<?=$i?>, window.document.frm_pedido.preco_<?=$i?>, window.document.frm_pedido.voltagem,'descricao', document.getElementById('qtde_<?=$i?>').value)" <? }else{?>
						onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia_<?=$i?>.value, window.document.frm_pedido.peca_referencia_<?=$i?>, window.document.frm_pedido.peca_descricao_<?=$i?>, window.document.frm_pedido.preco_<?=$i?>, window.document.frm_pedido.voltagem,'descricao', document.getElementById('qtde_<?=$i?>').value)"
						<?} ?>>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="qtde_<? echo $i ?>" id="qtde_<? echo $i ?>" size="5" maxlength='5' value="<? echo $qtde ?>" tabindex="<?=($i*4)+3?>"<?php
						if ($login_fabrica == 42) {
							echo " onblur='javascript: fnc_makita_preco ($i)' ";
						} else {
							echo " onblur='javascript: fnc_calcula_total($i); atualiza_proxima_linha($i)' ";
						}?>
						/>
					</td>

					<? if ($login_fabrica != 14 AND $login_fabrica!=24) { ?>
					<td align='center'>
						<input class="frm" id="preco_<? echo $i ?>" type="text" name="preco_<? echo $i ?>" size="10"  value="<? echo $preco ?>" readonly style='text-align:right'>
					</td>
					<td align='center'>
						<input class="frm" name="sub_total_<? echo $i ?>" id="sub_total_<? echo $i ?>" type="text" size="10" rel='total_pecas' readonly style='text-align:right' value='<?
								$preco = str_replace(",",".",$preco); 
								if ($qtde &&  $preco) { 
									$total_geral += $preco * $qtde; 
									echo $preco * $qtde;
								}
							?>'>
					</td>
					<? } ?>

					<? if ($login_fabrica==24){ ?>
					<input type="hidden" name="preco_<? echo $i ?>" value="<? echo $preco ?>">
					 <? } ?>
				</tr>

				<?
				if ($tem_obs) {
					echo "<tr bgcolor='#FFCC33' style='font-size:12px'>";
					echo "<td colspan='4'>$linha_obs</td>";
					echo "</tr>";
				}
				?>

			<?
			}
			if($login_fabrica <> 24 and $login_fabrica <> 30 ){
				echo "<tr style='font-size:12px' align='right'>";
				echo "<td colspan='7'><b>Total</b>: <INPUT TYPE='text' size='15' style='text-align:right' id='total_pecas'";
					if(strlen($total_geral) > 0) echo " value='$total_geral'";
				echo "></td>";
				echo "</tr>";
			}
			if($login_fabrica == 15 || $login_fabrica == 85){
				echo "<tr style='font-size:12px' align='center'>";
				echo "<td colspan='7'><b>Observação</b>: <br /><textarea NAME='observacao_pedido' class='frm' cols='30' rows='7'>";
					if(strlen($observacao_pedido) > 0) echo $observacao_pedido; echo "</textarea>";
				echo "</td>";
				echo "</tr>";
			}
			?>
			</table>
		</p>
		<p><center>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
		</center>
		</p>
</div>
</li>
</ul>
<!-- Fecha Divisão-->

</form>
<br clear='both'>
<p>

<? include "rodape.php"; ?>
