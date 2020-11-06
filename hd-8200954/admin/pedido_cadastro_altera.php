<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

if ($login_fabrica == 1) {
	header ("Location: pedido_cadastro_blackedecker.php");
	exit;
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if (strlen($_GET['pedido']) > 0) {
	$pedido = trim($_GET['pedido']);
}

if (strlen($_POST['pedido']) > 0) {
	$pedido = trim($_POST['pedido']);
}

if ($btn_acao == "apagar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_os_item SET pedido = null
			FROM   tbl_pedido
			WHERE  tbl_os_item.pedido = tbl_pedido.pedido
			AND    tbl_os_item.pedido = $pedido
			AND    tbl_pedido.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$os_exluida = '4836000';

		$sql = "UPDATE tbl_os_produto
				SET os = $os_excluida
				FROM tbl_os_item
				JOIN tbl_pedido USING(pedido)
				WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
				AND tbl_os_item.pedido = $pedido
				AND tbl_pedido.fabrica = $login_fabrica";

		$res = pg_query ($con,$sql);

	}


	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_delete ($pedido, $login_fabrica, $login_admin)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($btn_acao == "gravar") {

	$xtipo_pedido = "'Faturado'";

	if (strlen($_POST['tipo_pedido']) > 0) {
		$xtipo_pedido = "'". $_POST['tipo_pedido'] ."'";
	}else{
		$msg_erro = "Selecione o Tipo de Pedido";
		$xtipo_pedido = "null";
	}

	if (strlen($_POST['condicao']) > 0) {
		$xcondicao = "'". $_POST['condicao'] ."'";
	}else{
		$xcondicao = "null";
	}

	if (strlen($_POST['tipo_frete']) > 0) {
		$xtipo_frete = "'". $_POST['tipo_frete'] ."'";
	}else{
		$xtipo_frete = "null";
	}

	if (strlen($_POST['linha']) > 0) {
		$xlinha = "'". $_POST['linha'] ."'";
	}else{
		$xlinha = "null";
	}

	if (strlen($_POST['pedido_cliente']) > 0) {
		$xpedido_cliente = "'". $_POST['pedido_cliente'] ."'";
	}else{
		$xpedido_cliente = "null";
	}

	if (strlen($_POST['validade']) > 0) {
		$xvalidade = "'". $_POST['validade'] ."'";
	}else{
		$xvalidade = "null";
	}

	if (strlen($_POST['entrega']) > 0) {
		$xentrega = "'". $_POST['entrega'] ."'";
	}else{
		$xentrega = "null";
	}

	if (strlen($_POST['tabela']) > 0) {
		$xtabela = "'". $_POST['tabela'] ."'";
	}else{
		$xtabela = "null";
	}

	if (strlen($_POST['transportadora']) > 0) {
		$xtransportadora = $_POST['transportadora'] ;
	}else{
		$xtransportadora = "null";
	}

	if (strlen($_POST['cnpj']) > 0) {
		$cnpj  = $_POST['cnpj'];
		$cnpj  = str_replace (".","",$cnpj);
		$cnpj  = str_replace ("-","",$cnpj);
		$cnpj  = str_replace ("/","",$cnpj);
		$cnpj  = str_replace (" ","",$cnpj);
		$xcnpj = "'". $cnpj ."'";
	}else{
		$xcnpj = "null";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['referencia']) > 0) {
		$xreferencia = $_POST['referencia'] ;
		$xreferencia  = str_replace (".","",$xreferencia);
		$xreferencia  = str_replace ("-","",$xreferencia);
		$xreferencia  = str_replace ("/","",$xreferencia);
		$xreferencia  = str_replace (" ","",$xreferencia);
		$xreferencia = "'".$xreferencia."'";

		$sql = "SELECT produto
				FROM   tbl_produto
				WHERE  referencia_pesquisa = $xreferencia";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $produto = pg_result($res,0,0);

	}else{
		$xreferencia = "null";
	}

	if ($xcnpj <> "null") {
		$sql = "SELECT tbl_posto.posto
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica USING (posto)
				WHERE  tbl_posto.cnpj            = $xcnpj
				AND    tbl_posto_fabrica.fabrica = $login_fabrica;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			$sql = "SELECT tbl_posto.posto
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica USING (posto)
					WHERE  tbl_posto_fabrica.codigo_posto = $xcnpj
					AND    tbl_posto_fabrica.fabrica      = $login_fabrica;";

			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) $msg_erro = "CNPJ ou Código não cadastrado";
		}
		$posto = @pg_result ($res,0,0);
	}else{
		$msg_erro = "CNPJ ou Código não informados";
	}

	if ($xtipo_pedido <> "null") {
		$sql = "SELECT tipo_pedido, descricao
				FROM   tbl_tipo_pedido
				WHERE  tipo_pedido = $xtipo_pedido
				AND    fabrica     = $login_fabrica";

		$res = pg_exec ($con,$sql);
        if (pg_numrows ($res) == 0) {
            $msg_erro = "Tipo de Pedido não cadastrado";
        } else {
            $tipo_pedido_desc = pg_fetch_result($res, 0, "descricao");

            if ($tipo_pedido_desc == "Insumo" and empty($_REQUEST["insumos"])) {
                $msg_erro = "Favor selecionar uma opção no campo Insumo<br/>";
            }

            if (($login_fabrica == 11 or $login_fabrica == 172) and $tipo_pedido_desc == "Insumo") {
                $qry_insumo = pg_query(
                    $con,
                    "SELECT condicao FROM tbl_condicao
                    WHERE fabrica = $login_fabrica
                    AND descricao = 'Insumo'"
                );

                $xcondicao = pg_fetch_result($qry_insumo, 0, 'condicao');
            }
        }
	}else{
		$msg_erro = "Tipo de Pedido não informado.";
	}

	if ($xtabela <> "null") {
		$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.tabela  = $xtabela
				AND    tbl_tabela.fabrica = $login_fabrica
				AND    tbl_tabela.ativa   IS TRUE ;";

		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Tabela de Preços não cadastrada";
	}else{
		$msg_erro = "Tabela de Preços não informada";
	}

	if ($xcondicao <> "null") {
		$sql = "SELECT tbl_condicao.condicao
				FROM   tbl_condicao
				WHERE  tbl_condicao.condicao = $xcondicao
				AND    tbl_condicao.fabrica  = $login_fabrica";

		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Condição de Pagamento não cadastrada";
	}else{
		$msg_erro = "Condição de Pagamento não informada";
	}

	if($login_fabrica == 11 or $login_fabrica == 172) /*HD-3622818*/
	{
		$sql = "SELECT transportadora FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica AND transportadora NOTNULL";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			$xtransportadora = @pg_result ($res,0,0);
		}else{
			$xtransportadora = "null";
		}
	}

	if (strlen ($msg_erro) == 0) {

		$garantia_antecipada = "f";
		if ($login_fabrica == 3 AND $tipo_pedido == "3") $garantia_antecipada = "t";


		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen ($pedido) == 0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_pedido (
						posto         ,
						fabrica       ,
						condicao      ,
						tabela        ,
						admin         ,
						tipo_pedido   ,
						pedido_cliente,
						validade      ,
						entrega       ,
						obs           ,
						linha         ,
						transportadora,
						tipo_frete    ,
						garantia_antecipada
					) VALUES (
						$posto           ,
						$login_fabrica   ,
						$xcondicao       ,
						$xtabela         ,
						$login_admin     ,
						$tipo_pedido     ,
						$xpedido_cliente ,
						$xvalidade       ,
						$xentrega        ,
						$xobs            ,
						$xlinha          ,
						$xtransportadora ,
						$xtipo_frete     ,
						'$garantia_antecipada'
					)";
		}else{
			$sql = "UPDATE tbl_pedido SET
						posto          = $posto          ,
						condicao       = $xcondicao      ,
						tabela         = $xtabela        ,
						admin_alteracao= $login_admin    ,
						tipo_pedido    = $tipo_pedido    ,
						pedido_cliente = $xpedido_cliente,
						validade       = $xvalidade      ,
						entrega        = $xentrega       ,
						obs            = $xobs           ,
						linha          = $xlinha         ,
						transportadora = $xtransportadora,
						tipo_frete     = $xtipo_frete
					WHERE tbl_pedido.pedido  = $pedido
					AND   tbl_pedido.fabrica = $login_fabrica";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 and strlen($pedido) == 0) {
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");

			$pedido   = pg_result ($res,0,0);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$qtde_item = $_POST['qtde_item'];

			$nacional  = 0;
			$importado = 0;

			for ($i = 0 ; $i < $qtde_item ; $i++) {

				$novo            = $_POST["novo".$i];
				$item            = $_POST["item".$i];
				$excluir_item    = $_POST["excluir_".$i];

				$peca_referencia = $_POST['peca_referencia_' . $i];
				$qtde            = $_POST['qtde_'            . $i];

				if (strlen($peca_referencia) > 0)
					$excluiu_pedido = "f";

				if(strlen($qtde) == 0 OR strlen($peca_referencia) == 0 OR $excluir_item=="sim") {

					if (strlen($item) > 0 AND $novo == 'f') {

						# para excluir, deve zerar a referencia, se nao executa a parte de incluir a peça
						if ( $excluir_item=="sim" and ($login_fabrica <> 11 and $login_fabrica <> 172)){
							$peca_referencia="";
						}

						/*

						//grava informações na tabela tbl_pedido_cancelado
						if (strlen($msg_erro) == 0) {
							$sql = "SELECT  tbl_os.os           ,
											tbl_os.posto        ,
											current_date as data,
											tbl_os_item.peca    ,
											tbl_os_item.qtde
									FROM tbl_os_item
									JOIN tbl_os_produto using(os_produto)
									JOIN tbl_os using(os)
									WHERE tbl_os_item.pedido      = $pedido
									AND   tbl_os_item.pedido_item = $item";
							$res = pg_exec($con,$sql);

							if (pg_numrows($res) > 0) {
								$osc     = pg_result($res,0,os);
								$postoc  = pg_result($res,0,posto);
								$datac   = pg_result($res,0,data);
								$pecac   = pg_result($res,0,peca);
								$qtdec   = pg_result($res,0,qtde);
							} else {
								$sql = "SELECT  tbl_pedido.posto,
												current_date as data,
												tbl_pedido_item.peca,
												tbl_pedido_item.qtde
										FROM tbl_pedido
										JOIN tbl_pedido_item using(pedido)
										WHERE tbl_pedido.pedido           = $pedido
										AND   tbl_pedido_item.pedido_item = $item";
								$res = pg_exec($con,$sql);

								$osc     = 'null';
								$postoc  = pg_result($res,0,posto);
								$datac   = pg_result($res,0,data);
								$pecac   = pg_result($res,0,peca);
								$qtdec   = pg_result($res,0,qtde);
							}
							$sql = "INSERT into tbl_pedido_cancelado
									VALUES($pedido, $postoc, $login_fabrica, $osc, $pecac, $qtdec, 'cancelada pelo fabricante', '$datac')";
							$res = pg_exec($con,$sql);
						}
*/
						// rotina para ativar e desativar a peça qndo ta inativa para poder excluir do pedido
						// By Fabio 26/07/2007 - HD 3239

						if ($login_fabrica == 11 or $login_fabrica == 172) {

							$sql = "SELECT  peca
								FROM tbl_os_item
								WHERE tbl_os_item.pedido_item = $item
								AND   tbl_os_item.pedido      = $pedido";
							$res = pg_exec($con,$sql);

							if (pg_numrows($res) > 0) {
								$peca = pg_result($res,0,peca);
								$sql = "SELECT  peca
									FROM  tbl_peca
									WHERE fabrica = $login_fabrica
									AND   peca    = $peca
									AND   ativo IS NOT TRUE";

								$res = pg_exec($con,$sql);

								if (pg_numrows($res) > 0) {
									$peca_inativa = pg_result($res,0,peca);
									$sql = "UPDATE tbl_peca
										SET ativo   = TRUE
										WHERE peca  = $peca_inativa
										AND fabrica = $login_fabrica";
									$res = pg_exec($con,$sql);
									$msg_erro .= pg_errormessage($con);
								}

							}else{ // HD 669834

								$sql = "SELECT  tbl_peca.peca
										FROM    tbl_peca
										WHERE   tbl_peca.referencia = '$peca_referencia'
										AND     tbl_peca.fabrica    = $login_fabrica ";
								$res = pg_exec ($con,$sql);
								if (pg_numrows ($res) == 0) {
									$msg_erro = "Peça $peca_referencia não cadastrada";
									$linha_erro = $i;
								}else{
									$peca   = pg_result ($res,0,peca);
								}

							}

							// HD 669834 - Inicio

							$motivo_item 	= $_POST['motivo_'.$i]; 		//motivo do cancelamento
							$qtde_cancelar 	= $_POST['qtde_cancelar_'.$i];	 //qtde a cancelar

							//SQL PARA PEGAR A QTDE CANCELADA DO ITEM. PARA PODER VERIFICAR SE A QTDE QUE ESTA SENDO PASSADA PELO POST NÃO É MAIOR DO QUE A PERMITIDA.
							$sqlQtde = "SELECT  tbl_pedido_item.qtde_cancelada,
											tbl_pedido_item.qtde_faturada
									FROM 	tbl_pedido_item
									JOIN 	tbl_pedido USING (pedido)
									WHERE 	tbl_pedido.fabrica = $login_fabrica
									AND 	tbl_pedido_item.peca = $peca
									AND 	tbl_pedido.pedido = $pedido
									AND 	tbl_pedido_item.pedido_item = $item
									";

							$resQtde = pg_query($con,$sqlQtde);

							$qtde_cancelada_item = ( pg_numrows($resQtde)>0 ) ? pg_result($resQtde,0,0) : 0 ;
							$qtde_faturada_item = ( pg_numrows($resQtde)>0 ) ? pg_result($resQtde,0,1) : 0 ;

							if ( strlen($motivo_item) == 0 ){

								$msg_erro = "Favor insira o motivo para o cancelamento da peça: ".$_POST['peca_referencia_' . $i];

							}

							if (empty($msg_erro)){

								if (!empty($qtde_cancelar)){

									if ( ($qtde - $qtde_cancelada_item - $qtde_faturada_item) < $qtde_cancelar) {
										$msg_erro 	= "Quantidade para cancelar da peça $peca_referencia, é maior do que a solicitada no pedido.";
										$linha_erro = $i;
									}

								}else{

									$msg_erro = "Informe a quantidade a ser cancelada para a peça: $peca_referencia";
									$linha_erro = $i;
								}

							}
							// HD 669834 - Fim

						}

						/* Por Raphael pois não é para excluir mais a peça, apenas inativa-la
						$sql = "DELETE FROM tbl_pedido_item
								WHERE  tbl_pedido_item.pedido = $pedido
								AND    tbl_pedido_item.pedido_item = $item;";
						$res = @pg_exec($con,$sql);
						*/

						$sql = "SELECT  tbl_os.os,
								tbl_os.finalizada
							FROM tbl_os_item
							JOIN tbl_os_produto USING (os_produto)
							JOIN tbl_os         USING (os)
							WHERE tbl_os_item.pedido      = $pedido
							AND   tbl_os_item.pedido_item = $item
							";

						$res_finalizada = pg_exec($con,$sql);

						if (pg_numrows($res_finalizada) > 0) $os_apaga = pg_result($res_finalizada,0,os);
						else                                 $os_apaga = 'null';

						if ($login_fabrica <> 11 and $login_fabrica <> 172){

							$sql = "UPDATE tbl_pedido_item
										SET qtde_cancelada = qtde
									WHERE pedido_item = $item
									AND   pedido      = $pedido;";

							$res = pg_exec ($con,$sql);

							$sql = "INSERT INTO tbl_pedido_cancelado (pedido,posto,fabrica,os,peca,qtde,motivo,data
								)VALUES(
									$pedido,
									(SELECT posto FROM tbl_pedido WHERE pedido=$pedido),
									$login_fabrica,
									$os_apaga,
									(SELECT peca FROM tbl_pedido_item WHERE pedido_item = $item),
									$qtde,
									'$motivo_item',
									current_date

								);";

							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);

                        }else if ( ($login_fabrica == 11 or $login_fabrica == 172) and empty($msg_erro) ){

                            $fn_pedido_cancela = 'fn_pedido_cancela_lenoxx';

                            if ($login_fabrica == 172) {
                                $fn_pedido_cancela = 'fn_pedido_cancela_pacific';
                            }

							$sql  = "SELECT {$fn_pedido_cancela}($login_fabrica,$pedido,$peca,$qtde_cancelar,'$motivo_item',$item,$login_admin)";
							$resY = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);

						}

						if (empty($msg_erro) and ($login_fabrica == 11 or $login_fabrica == 172)){

							// Comunicado avisando ao posto sobre a peça que foi cancelada do pedido
							$sqlC = "INSERT INTO tbl_comunicado (
										descricao              ,
										mensagem               ,
										tipo                   ,
										fabrica                ,
										obrigatorio_os_produto ,
										obrigatorio_site       ,
										posto                  ,
										ativo
									) VALUES (
										'Pedido com peça cancelada pelo fabricante',
										'O ítem $peca_referencia referente ao pedido $pedido foi cancelado. <br /> Motivo: $motivo_item <br /> Qtde. Pedida: $qtde <br /> Qtde Cancelada: $qtde_cancelar',
										'Peça Cancelada em Pedido',
										$login_fabrica,
										'f' ,
										't',
										$posto,
										't'
									);";
							$resC = pg_query($con, $sqlC);

						}
						/*
						if ($login_fabrica == 11) {
							//seta finalizada = NULL para não bloquear na trigger em caso de os fechada
							$os_finalizada = "";
							$finalizada     = "";
							$sql = "SELECT  tbl_os.os,
									tbl_os.finalizada
								FROM tbl_os_item
								JOIN tbl_os_produto USING (os_produto)
								JOIN tbl_os         USING (os)
								WHERE tbl_os_item.pedido      = $pedido
								AND   tbl_os_item.pedido_item = $item
								AND   tbl_os.finalizada IS NOT NULL";
							$res_finalizada = pg_exec($con,$sql);

							if (pg_numrows($res_finalizada) > 0) {
								$os_finalizada = pg_result($res_finalizada,0,os);
								$finalizada    = pg_result($res_finalizada,0,finalizada);
								$sql = "UPDATE tbl_os
									SET finalizada = NULL
									WHERE os = $os_finalizada";
								$res = pg_exec($con,$sql);
							}

							$sql = "UPDATE tbl_os_item SET pedido = NULL
								WHERE  tbl_os_item.pedido      = $pedido
								AND    tbl_os_item.pedido_item = $item;";
							$res = @pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);

							$sql = "DELETE FROM tbl_os_item
								WHERE tbl_os_item.pedido_item = $item;";
							$res = @pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);

							if (strlen($os_finalizada) > 0 and strlen($finalizada) > 0) {
								$sql = "UPDATE tbl_os
									SET finalizada = '$finalizada'
									WHERE os       = $os_finalizada";
								$res = pg_exec($con,$sql);
							}

							//verifica se deve excluir o pedido
							if (strlen($msg_erro) == 0) {
								$sql = "SELECT pedido_item
									FROM tbl_pedido_item
									WHERE pedido = $pedido";
								$res = pg_exec($con,$sql);

								if (pg_numrows($res) == 0) {
									$sql = "UPDATE tbl_pedido
										SET exportado = NULL
										WHERE fabrica = $login_fabrica
										AND   pedido  = $pedido";
									$res = @pg_exec ($con,$sql);
									$msg_erro = pg_errormessage($con);

									$sql = "DELETE from tbl_pedido
										WHERE fabrica = $login_fabrica
										AND   pedido  = $pedido";
									$res = @pg_exec ($con,$sql);
									$msg_erro = pg_errormessage($con);

									if (strlen($msg_erro) == 0) $excluiu_pedido = "t";
								}
							}
						}*/

						if (($login_fabrica==11 or $login_fabrica == 172) AND strlen($peca_inativa)>0) {

							$sql = "UPDATE tbl_peca
								SET   ativo = 'f'
								WHERE  peca = $peca_inativa
								AND fabrica = $login_fabrica";

							$res = pg_exec($con,$sql);

							$msg_erro .= pg_errormessage($con);

						}

					}

				}

				if (strlen($msg_erro) == 0) {

					if (strlen ($peca_referencia) > 0) {
						$peca_referencia = strtoupper ($peca_referencia);
						$peca_referencia = str_replace ("-","",$peca_referencia);
						$peca_referencia = str_replace (".","",$peca_referencia);
						$peca_referencia = str_replace ("/","",$peca_referencia);
						$peca_referencia = str_replace (" ","",$peca_referencia);

						$sql = "SELECT  tbl_peca.peca,
										tbl_peca.origem
								FROM    tbl_peca
								WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
								AND     tbl_peca.fabrica    = $login_fabrica ";

						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) == 0) {
							$msg_erro = "Peça $peca_referencia não cadastrada";
							$linha_erro = $i;
						}else{
							$peca   = pg_result ($res,0,peca);
							$origem = trim(pg_result ($res,0,origem));
						}

						if ($origem == "NAC" or $origem == "1") {
							$nacional = $nacional + 1;
						}

						if ($origem == "IMP" or $origem == "2") {
							$importado = $importado + 1;
						}

						if ($nacional > 0 and $importado > 0 AND $login_fabrica <> 3 AND $login_fabrica <> 5 AND $login_fabrica <> 8 AND $login_fabrica <> 11 and $login_fabrica <> 172){
							$msg_erro = "Não é permitido realizar um pedido com peça Nacional e Importada";
							$linha_erro = $i;
							break;
						}

                        if (($login_fabrica == 11 or $login_fabrica == 172) and $tipo_pedido_desc == 'Insumo' and $_REQUEST["insumos"] == "embalagens" and !empty($peca)) {
                            $qry_posto = pg_query(
                                $con,
                                "SELECT atendimento FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica"
                            );

                            if (pg_fetch_result($qry_posto, 0, "atendimento") <> 't') {
                                $linha_erro = $i;
                                $msg_erro = "Posto não permitido para o Insumo selecionado";
                                break;
                            }

                            $qry_pa = pg_query($con, "SELECT parametros_adicionais FROM tbl_peca WHERE peca = $peca");
                            $parametros_adicionais = json_decode(pg_fetch_result($qry_pa, 0, 'parametros_adicionais'), true);

                            $erro_insumos = false;

                            if (empty($parametros_adicionais) or !array_key_exists("embalagens", $parametros_adicionais)) {
                                $erro_insumos = true;
                            } elseif ($parametros_adicionais["embalagens"] <> "t") {
                                $erro_insumos = true;
                            }

                            if (true === $erro_insumos) {
                                $linha_erro = $i;
                                $msg_erro = "Peça não permitida para o Insumo selecionado";
                                break;
                            }
                        }

						if (strlen ($msg_erro) == 0) {
							if (strlen($pedido) == 0 OR $novo == 't') {
								$sql = "INSERT INTO tbl_pedido_item (
											pedido,
											peca  ,
											qtde
										) VALUES (
											$pedido,
											$peca  ,
											$qtde
										)";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen($msg_erro) == 0) {
									$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
									$pedido_item = pg_result ($res,0,0);
									$msg_erro = pg_errormessage($con);
								}

							}else{
								$sql = "UPDATE  tbl_pedido_item SET
												peca = $peca,
												qtde = $qtde
										WHERE  tbl_pedido_item.pedido      = $pedido
										AND    tbl_pedido_item.pedido_item = $item;";
								$res = @pg_exec ($con,$sql);
//echo "<BR><BR>".$sql."<BR>";
								$msg_erro = pg_errormessage($con);


								//wellington 29/03/2007 chamado 1731 (lenoxx altera os_item)
								if ($login_fabrica == 11 or $login_fabrica == 172) {

									//verifica qtde pedido e qtde faturada desta peça neste pedido
									//em caso positivo da baixa em todos os pedido_itens
									$sql = "SELECT (SELECT sum(qtde)
													FROM tbl_pedido_item
													WHERE pedido = $pedido
													AND   peca   = $peca)
											=
												   (SELECT sum(qtde)
													FROM tbl_faturamento_item
													WHERE pedido = $pedido
													AND   peca   = $peca)
											as fat";
									$res_fat = pg_exec($con, $sql);

									//se todas foram faturadas...
									if (pg_result($res_fat,0,fat) == 't') {
										$sql = "UPDATE tbl_pedido_item
												SET qtde_faturada = qtde
												WHERE pedido = $pedido
												AND   peca   = $peca";
										$res_fat = pg_exec($con,$sql);
//echo $sql."<BR>";
									} else {
										//se nao faturou todas
										//faz o faturamento da qtde correspondente

										//pega a qtde faturada q ainda nao foi dado baixa
										$sql = "SELECT (select COALESCE(sum(qtde),0) as sum
														from tbl_faturamento_item
														where pedido = $pedido
														and   peca   = $peca)
														-
														(select sum(qtde_faturada)
														from tbl_pedido_item
														where pedido = $pedido
														and   peca   = $peca)
												as fat;";
										$res_fat = pg_exec($con, $sql);
//echo $sql."<BR>";
										if (trim(pg_result($res_fat, 0, fat)) > 0) {
											$faturar = trim(pg_result($res_fat, 0, fat));
//echo "faturar =".$faturar."<BR>";
											while ($faturar > 0) {
												$sql = "SELECT  pedido_item,
																qtde-qtde_faturada as a_faturar
														FROM tbl_pedido_item
														WHERE pedido = $pedido
														AND   peca   = $peca
														AND   qtde-qtde_faturada > 0";
												$res_fat = pg_exec($con,$sql);

												if (pg_numrows($res_fat) > 0) {
													for ($j=0; $j<pg_numrows($res_fat); $j++) {
														$xpedido_item   = pg_result($res_fat,$j,pedido_item);
														$a_faturar      = pg_result($res_fat,$j,a_faturar);

														if ($faturar >= $a_faturar) {
															$sql = "UPDATE tbl_pedido_item
																	SET qtde_faturada = qtde_faturada + $a_faturar
																	WHERE pedido_item = $xpedido_item";
															$res_up = pg_exec($con,$sql);
//echo $sql."<BR>";
															$faturar = $faturar - $a_faturar;
														} elseif ($faturar < $a_faturar) {
															$sql = "UPDATE tbl_pedido_item
																	SET qtde_faturada = qtde_faturada + $faturar
																	WHERE pedido_item = $xpedido_item";
															$res_up = pg_exec($con,$sql);
//echo $sql."<BR>";
															$faturar = $faturar - $faturar;
														}
													}
												} else {
													$faturar = 0;
												}
											}
										}
									}

									//seta finalizada = NULL para não bloquear na trigger em caso de os fechada
									$os_finalizada = "";
									$finalizada     = "";
									$sql = "SELECT  tbl_os.os,
													tbl_os.finalizada
											FROM tbl_os_item
											JOIN tbl_os_produto using(os_produto)
											JOIN tbl_os using(os)
											WHERE tbl_os_item.pedido      =  $pedido
											AND   tbl_os_item.pedido_item =  $item
											AND   tbl_os_item.peca        <> $peca
											AND   tbl_os.finalizada IS NOT NULL";
									$res_finalizada = pg_exec($con,$sql);
									if (pg_numrows($res_finalizada) > 0) {
										$os_finalizada = pg_result($res_finalizada,0,os);
										$finalizada    = pg_result($res_finalizada,0,finalizada);
										$sql = "UPDATE tbl_os
												SET finalizada = NULL
												WHERE os = $os_finalizada";
										$res = pg_exec($con,$sql);
									}

									$sql = "UPDATE tbl_os_item SET
											peca = $peca,
											qtde = $qtde,
											obs  = 'peça(s) alterada(s) pelo admin',
											admin = $login_admin
											WHERE  tbl_os_item.pedido      =  $pedido
											AND    tbl_os_item.pedido_item =  $item
											AND    tbl_os_item.peca        <> $peca;";
									$res = @pg_exec ($con,$sql);
									$msg_erro = pg_errormessage($con);

									if (strlen($os_finalizada) > 0 and strlen($finalizada) > 0) {
										$sql = "UPDATE tbl_os
												SET finalizada = '$finalizada'
												WHERE os       = $os_finalizada";
										$res = pg_exec($con,$sql);
									}
								}
							}
//$msg_debug .= $sql." - 10<br>";

							if (strlen($msg_erro) == 0) {
								$sql = "SELECT qtde - (qtde_faturada + qtde_cancelada)
										FROM tbl_pedido_item
										WHERE pedido_item = 2219400;";
								$res       = @pg_exec ($con,$sql);
								$xpendente = pg_result($res,0,0);

								if ($xpendente > 0) {
									$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
									$res = @pg_exec ($con,$sql);
									$msg_erro = pg_errormessage($con);
								}
							}

							if (strlen ($msg_erro) > 0) {
								break ;
							}

						}

					}

				}
			}//end for
		}
	}

	if (strlen ($msg_erro) == 0 and $excluiu_pedido <> "t") {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	#---------- Pedido Via DISTRIBUIDOR (forçado) ----------#
	$pedido_via_distribuidor = $_POST['pedido_via_distribuidor'];
	if (strlen ($msg_erro)==0 and $pedido_via_distribuidor == "f"  and $excluiu_pedido <> "t") {
		$sql = "UPDATE tbl_pedido SET pedido_via_distribuidor = 'f' , distribuidor = null WHERE pedido = $pedido";
		$res = pg_exec ($con,$sql);
	}




#	if (strlen($msg_erro) == 0) {
#		$res = pg_exec ($con,"SELECT fn_finaliza_pedido_dynacom($pedido)");
#	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

        if ($login_fabrica == 11 or $login_fabrica == 172) {
            header ("Location: pedido_admin_consulta.php?pedido=$pedido");
            exit;
        }

		header ("Location: pedido_cadastro_altera.php?pedido=$pedido");

		if ($excluiu_pedido <> "t") {
			echo "<script language='javascript'>";
			echo "window.open ('pedido_finalizado.php?pedido=$pedido','pedido', 'toolbar=yes, location=no, status=no, scrollbars=yes, directories=no, width=500, height=400')";
			echo "</script>";
		}

		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


#------------ Le Pedido da Base de dados ------------#
if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_posto.posto          ,
					tbl_posto.cnpj           ,
					tbl_posto.nome           ,
					tbl_pedido.condicao      ,
					tbl_pedido.tabela        ,
					tbl_pedido.obs           ,
					tbl_pedido.tipo_pedido   ,
					tbl_pedido.pedido_via_distribuidor  ,
					tbl_pedido.tipo_frete    ,
					tbl_pedido.pedido_cliente,
					tbl_pedido.validade      ,
					tbl_pedido.entrega       ,
					tbl_pedido.linha         ,
					tbl_pedido.transportadora
			FROM    tbl_pedido
			JOIN    tbl_posto USING (posto)
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$posto          = trim(pg_result ($res,0,posto));
		$condicao       = trim(pg_result ($res,0,condicao));
		$tipo_frete     = trim(pg_result ($res,0,tipo_frete));
		$tipo_pedido    = trim(pg_result ($res,0,tipo_pedido));
		$pedido_cliente = trim(pg_result ($res,0,pedido_cliente));
		$pedido_via_distribuidor = trim (pg_result ($res,0,pedido_via_distribuidor));
		$validade       = trim(pg_result ($res,0,validade));
		$entrega        = trim(pg_result ($res,0,entrega));
		$tabela         = trim(pg_result ($res,0,tabela));
		$nome           = trim(pg_result ($res,0,nome));
		$cnpj           = trim(pg_result ($res,0,cnpj));
if($login_fabrica<>24 and $login_fabrica<>11 and $login_fabrica <> 172){
		$cnpj           = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
}
		$obs            = trim(pg_result ($res,0,obs));
		$linha          = trim(pg_result ($res,0,linha));
#		$referencia     = trim(pg_result ($res,0,referencia));
#		$descricao      = trim(pg_result ($res,0,descricao));
		$transportadora = trim(pg_result ($res,0,transportadora));
	}
}


#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$cnpj           = $_POST['cnpj'];
	$nome           = $_POST['nome'];
	$condicao       = $_POST['condicao'];
	$tipo_frete     = $_POST['tipo_frete'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$validade       = $_POST['validade'];
	$entrega        = $_POST['entrega'];
	$tabela         = $_POST['tabela'];
	$cnpj           = $_POST['cnpj'];
	$obs            = $_POST['obs'];
	$linha          = $_POST['linha'];
	$pedido_via_distribuidor = $_POST['pedido_via_distribuidor'];
}

$layout_menu = "callcenter";
$title       = "Cadastro de Pedidos de Peças";
$body_onload = "javascript: document.frm_pedido.condicao.focus()";

include "cabecalho.php";

?>
<script type="text/javascript" src="js/jquery.js"></script>
<script language="JavaScript">
/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function mostra_motivo(linha){


	if ( $('#excluir_'+linha).attr('checked') ){
		$('#motivo_'+linha).show();
		$('#qtde_cancelar_'+linha).show();
	}else{
		$('#motivo_'+linha).hide();
		$('#qtde_cancelar_'+linha).hide();
	}

}

$(document).ready(function(){
	$(':checkbox').each(
		function() {

			var qtde_itens = $('#qtde_item').val();

			for (i = 0; i < qtde_itens; i++){

				if ( $('#excluir_'+i).attr('checked') ){
					$('#motivo_'+i).show();
					$('#qtde_cancelar_'+i).show();
				}else{
					$('#motivo_'+i).hide();
					$('#qtde_cancelar_'+i).hide();
				}

			}

		}
	);
});

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
    }

    var params = '';

    <?php if ($login_fabrica == 11 or $login_fabrica == 172): ?>
    var tipo_pedido = $('select[name="tipo_pedido"]').val();

    if (!tipo_pedido) {
        alert('Selecione o Tipo do Pedido');
        return;
    }

    var tipo_pedido_text = $('select[name="tipo_pedido"]').find(':selected').text();

    if (tipo_pedido_text == 'Insumo') {
        var insumos = $("#insumos").val();

        if (!insumos) {
            alert('Selecione uma opção de Insumo');
            return;
        }

        var posto = $('input[name="cnpj"]').val();

        if (!posto) {
            alert('Favor informe o Posto');
            return;
        }

        params = '&tipo_pedido=' + tipo_pedido_text + '&insumo=' + insumos + '&posto=' + posto;
    }
    <?php endif ?>

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + params;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		peca_referencia	= campo;
		peca_descricao	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

    <?php
    $transp_param = '';
    if ($login_fabrica == 11 or $login_fabrica == 172) {
        $transp_param = '+ "&transp=f"';
    }
    ?>

	if (xcampo.value != "") {
		var url = "";
        url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo <?php echo $transp_param ?>;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_transportadora (xcampo, tipo)
{
	if (xcampo.value != "") {
		var url = "";
		url = "pesquisa_transportadora.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.transportadora = document.frm_pedido.transportadora;
		janela.nome           = document.frm_pedido.transportadora_nome;
		janela.cnpj           = document.frm_pedido.transportadora_cnpj;
		janela.focus();
	}
}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

?>

<table class="table" align='center' width="730" border="0" cellpadding="0" cellspacing="0" >
<tr>
<!-- class="menu_top" -->
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? }
//echo $msg_debug ;
?>


<!-- ------------- Formulário ----------------- -->
<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido ?>">

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<font face='arial, verdana, times' color='#000000'><b>
		Código ou CNPJ
		</b></font>
	</td>
	<td align='center'>
		<font face='arial, verdana, times' color='#000000'><b>
		Razão Social
		</b></font>
	</td>
</tr>

<tr class="table_line">
	<td align='center'>
		<input type="text" name="cnpj" size="14" maxlength="14" value="<? echo $cnpj ?>" class="textbox" style="width:150px" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'cnpj')" <? } ?>>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'cnpj')" style="cursor:pointer;">
	</td>
	<td align='center'>
		<input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" class="textbox" style="width:300px" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'nome')" <? } ?>>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'nome')" style="cursor:pointer;">
	</td>
</tr>

<tr class="table_line2">
	<td align='center' colspan='2'>
		<font face='arial' color='#333333' size='-1'><b>
		Para efetuar um pedido por modelo do produto, informe a referência <br> ou descrição e clique na lupa, ou simplesmente clique na lupa.
		</b></font>
	</td>
</tr>
</table>
<? if ($login_fabrica == 3) { ?>
<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
	<tr class="table_line" >
		<td align='left' ><input type="radio" name="pedido_via_distribuidor" value='t' <? if ($pedido_via_distribuidor == 't') echo " checked "; ?>> Atendimento Via Distribuidor</td>
		<td align='left' ><input type="radio" name="pedido_via_distribuidor" value='f' <? if ($pedido_via_distribuidor == 'f') echo " checked "; ?>> Atendimento DIRETO (via Fábrica)</td>
	</tr>
</table>
<? } ?>

<? if ($login_fabrica <> 5) { ?>
<table class="table" align='center' width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Linha
		</b>
	</td>
	<td align='center'>
		<b>
		Referência do Produto
		</b>
	</td>
	<td align='center'>
		<b>
		Descrição do Produto
		</b>
	</td>
</tr>

<tr class="table_line">
	<td align='center'>
		<?
		$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='linha' size='1'>";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,linha) . "' ";
				if ($linha == pg_result ($res,$i,linha) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,nome);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</td>
	<td align='center' nowrap>
		<input type="text" name="referencia" size="10" maxlength="20" value="<? echo $referencia ?>" class="textbox" >&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.referencia,document.frm_pedido.descricao,'referencia')"style="cursor:pointer;">
	</td>
	<td align='center' nowrap>
		<input type="text" name="descricao" size="30" maxlength="60" value="<? echo $descricao ?>" class="textbox">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.referencia,document.frm_pedido.descricao,'descricao')" style="cursor:pointer;">
	</td>
</tr>
</table>

<? } ?>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
			Tipo do Pedido
		</b>
	</td>
    <?php if ($login_fabrica == 11 or $login_fabrica == 172): ?>
	<td align='center'>
		<b>Insumo<br><i>(Apenas para Tipo de Pedido Insumo)</i></b>
    </td>
    <?php endif ?>
	<td align='center'>
		<b>
			Tabela de Preços
		</b>
	</td>
	<td align='center'>
		<b>
			Condição de Pagamento
		</b>
	</td>
	<td align='center'>
		<b>
			Tipo de Frete
		</b>
	</td>
</tr>

<tr class="table_line">
	<td align='center'>
		<?
		$sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica";
		if ($login_fabrica == 3) {
			$sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido IN (2,3)";
		}
		if ($login_fabrica == 6) {
			$sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = 4";
		}
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='tipo_pedido' size='1'>";
			echo "<option selected> </option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,tipo_pedido) . "' ";
				if ($tipo_pedido == pg_result ($res,$i,tipo_pedido) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,descricao);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</td>
    <?php if ($login_fabrica == 11 or $login_fabrica == 172): ?>
	<td align='center'>
        <select name="insumos" id="insumos">
            <option value=""></option>
            <?php
            $insumos = $_POST["insumos"];

            foreach (array('pecas' => 'Peças', 'embalagens' => 'Embalagens/Calços') as $k => $v) {
                echo '<option value="' . $k . '"';

                if ($k == $insumos) {
                    echo ' selected="selected"';
                }

                echo '>' . $v . '</option>';
            }
            ?>
        </select>
    </td>
    <?php endif ?>
	<td align='center'>
		<?
		$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='tabela' size='1'>";
			echo "<option selected> </option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,tabela) . "' ";
				if ($tabela == pg_result ($res,$i,tabela) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,sigla_tabela);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</td>
	<td align='center'>
		<?
		$sql = "SELECT * FROM tbl_condicao WHERE fabrica = $login_fabrica ORDER BY lpad(trim(tbl_condicao.codigo_condicao::text),10,'0');";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='condicao' size='1'>";
			echo "<option selected> </option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,condicao) . "' ";
				if ($condicao == pg_result ($res,$i,condicao) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,descricao);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</td>
	<td align='center'>
		<SELECT name="tipo_frete" size="1">
		<option selected> </option>
		<option value="FOB" <? if ($tipo_frete == "FOB") echo " selected " ?> >FOB</option>
		<option value="CIF" <? if ($tipo_frete == "CIF") echo " selected " ?> >CIF</option>
		</SELECT>
	</td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Pedido Cliente
		</b>
	</td>
	<td align='center'>
		<b>
		Validade
		</b>
	</td>
	<td align='center'>
		<b>
		Entrega
		</b>
	</td>
<?

	$sql = "SELECT  tbl_fabrica.pedido_escolhe_transportadora
			FROM    tbl_fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido_escolhe_transportadora = trim(pg_result ($res,0,pedido_escolhe_transportadora));
	}

	if ($pedido_escolhe_transportadora == 't'){
?>
	<td align='center'>
		<b>
		Transportadora
		</b>
	</td>
<?
	}
?>
</tr>

<tr class="table_line">
	<td align='center'>
		<input type="text" name="pedido_cliente" size="10" maxlength="20" value="<? echo $pedido_cliente ?>" class="textbox">
	</td>

	<?
	if (strlen ($validade) == 0) $validade = "10 dias";
	if (strlen ($entrega) == 0)  $entrega  = "15 dias";
	?>
	<td align='center'>
		<input type="text" name="validade" size="10" maxlength="20" value="<? echo $validade ?>" class="textbox">
	</td>
	<td align='center'>
		<input type="text" name="entrega" size="10" maxlength="20" value="<? echo $entrega ?>" class="textbox">
	</td>
<?
	if ($pedido_escolhe_transportadora == 't'){
?>
	<td align='center'>
<?
	$sql = "SELECT	tbl_transportadora.transportadora        ,
					tbl_transportadora.cnpj                  ,
					tbl_transportadora.nome                  ,
					tbl_transportadora_fabrica.codigo_interno
			FROM	tbl_transportadora
			JOIN	tbl_transportadora_fabrica USING(transportadora)
			WHERE	tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND		tbl_transportadora_fabrica.ativo  = 't' ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {

		if (pg_numrows ($res) <= 20) {

			echo "		<select name='transportadora'>";
			echo "			<option selected></option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='".pg_result($res,$i,transportadora)."' ";
				if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
				echo ">";
				echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
				echo "</option>\n";
			}
			echo "		</select>";

		}else{

			echo "		<input type='hidden' name='transportadora' value=''>";
			echo "		<input type='text'   name='transportadora_codigo' size='6' maxlength='10' value='$transportadora_codigo' class='textbox' "; if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\"";
			echo ">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";
			echo "		<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj' class='textbox' >";
			echo "		<input type='text' name='transportadora_nome' size='15' maxlength='50' value='$transportadora_nome' class='textbox' "; if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\"";
			echo ">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";

		}

	}else{

		echo " - - - ";

	}

?>
	</td>
<?
	}
?>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Mensagem
		</b>
	</td>
</tr>
<tr>
	<td align='center'>
		<input type="text" name="obs" size="50" value="<? echo $obs ?>" class="textbox">
	</td>
</tr>
</table>

<p>
		<?if ($login_fabrica==11 or $login_fabrica == 172) echo "<TABLE width='50' align='center' border='0'><TR><TD bgcolor='#E0E0E0' width='90%'>&nbsp;&nbsp;&nbsp;</TD><TD width='90%'>Atendidas</TD></TR></TABLE>"?>
		<table width="550" border="0" cellspacing="3" cellpadding="0" align='center'>
		<tr height="20" class="menu_top">
			<?if ($login_fabrica==11 or $login_fabrica == 172) echo "<TD>OS</TD>";?>
			<td align='center'>Referência Componente</td>
			<td align='center'>Descrição Componente</td>
			<td align='center'>Qtde</td>
			<? if ($login_fabrica==11 or $login_fabrica == 172){?>
				<td align='center'>Cancelar</td>
				<td align='center'>Qtde. Cancelar</td>
				<td align='center'>Motivo</td>
			<?}?>
		</tr>

		<?
		if (strlen($pedido) > 0) {
			$sql = "SELECT      tbl_peca.peca,
							    tbl_pedido_item.pedido_item
					FROM        tbl_pedido_item
					JOIN        tbl_peca   USING (peca)
					JOIN        tbl_pedido USING (pedido)
					WHERE       tbl_pedido_item.pedido = $pedido
					AND         tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada
					ORDER BY    tbl_pedido_item.pedido_item;";
			$ped = pg_exec ($con,$sql);
		}

		$qtde_item = 80;

		echo "<input class='frm' type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>";

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$peca = trim(@pg_result($ped,$i,peca));
			if (@pg_numrows($ped) > 0) {
					if ($login_fabrica == 11 or $login_fabrica == 172 ) $item = trim(@pg_result($ped,$i,pedido_item));
			}
			if (strlen($pedido) > 0 and strlen($peca) > 0 or (($fabrica == 11 or $login_fabrica == 172) and strlen($pedido) > 0 and strlen($peca) > 0 and strlen($item) > 0)) {


				$sql = "SELECT    tbl_pedido_item.pedido_item                                    ,
								  tbl_pedido_item.qtde                                           ,
								  tbl_pedido_item.preco                                          ,
								  (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) as saldo,
								  tbl_peca.referencia                                            ,
								  tbl_peca.origem                                                ,
								  tbl_peca.descricao
						FROM      tbl_pedido_item
						JOIN      tbl_peca USING (peca)
						WHERE     tbl_pedido_item.pedido = $pedido
						AND       tbl_pedido_item.peca   = $peca ";
				if ($login_fabrica == 11 or $login_fabrica == 172)
					$sql .= "AND tbl_pedido_item.pedido_item = $item";

				$aux_ped = @pg_exec ($con,$sql);

				if (@pg_numrows($aux_ped) == 0) {
					$novo            = 't';
					$item            = $HTTP_POST_VARS["item".     $aux];
					$peca_referencia = $HTTP_POST_VARS["peca_referencia_" . $i];
					$peca_descricao  = $HTTP_POST_VARS["peca_descricao_"  . $i];
					$qtde            = $HTTP_POST_VARS["qtde_"            . $i];
					$preco           = $HTTP_POST_VARS["preco_"           . $i];
					$saldo           = 'NULL';
				}else{
					$novo            = 'f';
					$item            = trim(pg_result($aux_ped,0,pedido_item));
					$peca_referencia = trim(pg_result($aux_ped,0,referencia));
					$peca_descricao  = trim(pg_result($aux_ped,0,descricao));
					$qtde            = trim(pg_result($aux_ped,0,qtde));
					$preco           = trim(pg_result($aux_ped,0,preco));
					$origem          = trim(pg_result($aux_ped,0,origem));
					$saldo           = trim(pg_result($aux_ped,0,saldo));

					if (($login_fabrica == 11 or $login_fabrica == 172) and strlen($item) > 0) {
						$sql = "SELECT  tbl_os.os    ,
										tbl_os.sua_os
								FROM tbl_os
								JOIN tbl_os_produto USING(os)
								JOIN tbl_os_item USING(os_produto)
								WHERE tbl_os.fabrica          = $login_fabrica
								AND   tbl_os.posto            = $posto
								AND   tbl_os_item.pedido_item = $item";
						$aux_os = pg_exec ($con,$sql);

						if (pg_numrows($aux_os) > 0) {
							$os_aux = trim(pg_result($aux_os,0,os));
							$sua_os = "<a href='os_press.php?os=$os_aux' target='_blank'>".trim(pg_result($aux_os,0,sua_os))."</a>";
						}
					} else {
						$sua_os = "";
					}
				}
			}else{
				$novo            = 't';
				$item            = $HTTP_POST_VARS["item".     $aux];
				$peca_referencia = $HTTP_POST_VARS["peca_referencia_" . $i];
				$peca_descricao  = $HTTP_POST_VARS["peca_descricao_"  . $i];
				$qtde            = $HTTP_POST_VARS["qtde_"            . $i];
				$preco           = $HTTP_POST_VARS["preco_"           . $i];
			}

			#if (strlen ($msg_erro) > 0) {
			#	$peca_referencia = $HTTP_POST_VARS["peca_referencia_" . $i];
			#	$peca_descricao  = $HTTP_POST_VARS["peca_descricao_"  . $i];
			#	$qtde            = $HTTP_POST_VARS["qtde_"            . $i];
			#	$preco           = $HTTP_POST_VARS["preco_"           . $i];
			#}

			echo "<input type='hidden' name='novo$i' value='$novo'>\n";
			echo "<input type='hidden' name='item$i' value='$item'>\n";
			?>
			<tr <? if ($linha_erro == $i and strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'";
				elseif (($login_fabrica==11 or $login_fabrica == 172) and $saldo=='0') echo "bgcolor='#E0E0E0'"; ?>>
				<?if ($login_fabrica==11 or $login_fabrica == 172) echo "<td><font face='arial' size='1' color='#809DCA'><B>$sua_os</B></font>";$sua_os = ""; echo "</td>";?>
				<td nowrap><input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" value="<? echo $peca_referencia ?>" <? if ($login_fabrica == 5) { echo " onblur='document.frm_pedido.lupa_peca_referencia_$i.click()' " ; } ?> <?if (($login_fabrica==11 or $login_fabrica == 172) and $saldo=='0') echo " readonly "?>><img id='lupa_peca_referencia_<? echo $i ?>' src='../imagens/btn_buscar5.gif' alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca (window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?> , 'referencia')" style="cursor:pointer;">
				</td>
				<td
					align='center' nowrap><input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="30" value="<? echo $peca_descricao ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" <? } ?><?if (($login_fabrica==11 or $login_fabrica == 172) and $saldo=='0') echo " readonly "?>><img id='lupa_peca_descricao_<? echo $i ?>' src='../imagens/btn_buscar5.gif' alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" style="cursor:pointer;">
				</td>
				<td
					align='center' nowrap><input class="frm" type="text" name="qtde_<? echo $i ?>" size="5"  value="<? echo $qtde ?>" <?if (($login_fabrica==11 or $login_fabrica == 172) and $saldo=='0') echo "readonly"?>>
				</td>
				<? if ($login_fabrica==11 or $login_fabrica == 172){?>
				<td align='center' nowrap>
						<?
						if ($msg_erro){
							$excluir_item = $_POST['excluir_'.$i];
							$checked_excluir = ($excluir_item == "sim") ? "CHECKED" : null;
						}
						?>
					<input type="checkbox" <?echo $checked_excluir?> onclick="mostra_motivo(<? echo $i?>)" name="excluir_<? echo $i?>" id="excluir_<? echo $i?>" value="sim" <?if ($saldo=='0') echo "disabled"?> >
				</td>
				<td align="center">
						<?
						if ($msg_erro){
							$qtde_cancelar = $_POST['qtde_cancelar_'.$i];
						}
						?>
					<input type="text" class='frm' name="qtde_cancelar_<?echo $i?>" id="qtde_cancelar_<?echo $i?>" value="<?echo $qtde_cancelar?>" style="display:none;" size="5" />
				</td>
				<td align='center'>
						<?
						if ($msg_erro){
							$motivo_item = $_POST['motivo_'.$i];
						}
						?>
					<input type="text" class='frm' name="motivo_<?echo $i?>" id="motivo_<?echo $i?>" value="<?echo $motivo_item?>" style="display:none" />
				</td>

				<?}?>
			</tr>

			<?$saldo = 'null';?>
		<?
		}
		?>
		</table>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type='hidden' name='btn_acao' value=''>
		<img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
		<img src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='apagar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Pedido" border='0' style="cursor:pointer;">
		<img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php"; ?>
