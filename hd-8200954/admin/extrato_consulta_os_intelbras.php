<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";




if (strlen($_POST["btn_acao"]) == 0) {
	$data_inicial = $_GET["data_inicial"];
	$data_final = $_GET["data_final"];
	$cnpj = $_GET["cnpj"];
	$razao = $_GET["razao"];
}

if (strlen($_POST["btn_acao"]) == 0 && strlen($_POST["select_acao"]) == 0) {
	setcookie("link", $REQUEST_URI, time()+60*60*24); # Expira em 1 dia
}

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));

if (strlen($_POST["select_acao"]) > 0) $select_acao = strtoupper($_POST["select_acao"]);

if (strlen($_POST["extrato"]) > 0) $extrato = trim($_POST["extrato"]);
if (strlen($_GET["extrato"]) > 0)  $extrato = trim($_GET["extrato"]);

$msg_erro = "";

if ($btn_acao == 'pedido'){
	header ("Location: relatorio_pedido_peca_kit.php?extrato=$extrato");
	exit;
}

//HD 214236: Acrescentada a opção REPROVAR OS
if($login_fabrica == 14){//Recusas de OS´s
	if (strtoupper($select_acao) <> "RECUSAR" AND strtoupper($select_acao) <> "EXCLUIR" AND strtoupper($select_acao) <> "ACUMULAR" && strtoupper($select_acao) <> "REPROVAR" AND strlen($select_acao) > 0) {
		$os     = $_POST["os"];
		$sua_os = $_POST["sua_os"];

		$kk = 0;

		$sql         = "SELECT motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
		$res         = pg_exec($con, $sql);
		$select_acao = pg_result($res,0,motivo);
		$status_os   = pg_result($res,0,status_os);

		if(strlen($status_os) == 0){
			$msg_erro = "Escolha o motivo da Recusa da OS";
		}

		if($status_os == 13 OR $status_os == 14 AND strlen($msg_erro) == 0){

			$res = pg_exec($con,"BEGIN TRANSACTION");

			for ($k = 0 ; $k < $contador ; $k++) {
				if (strlen($msg_erro) > 0) {
					$os[$k]     = $_POST["os_" . $kk];
					$sua_os[$k] = $_POST["sua_os_" . $kk];
				}

				if (strlen($os[$k]) > 0) {
					echo "<input type='hidden' name='os_$kk' value='" . $os[$k] . "'></td>\n";
					$select_acao = RemoveAcentos($select_acao);
					$select_acao = strtoupper($select_acao);
					$kk++;

					if (strlen($msg_erro) == 0) {
						if($status_os == 13){
							$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
						}else{
							$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
						}
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);

						// 20274
						$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
			$res = (strlen($msg_erro) == 0) ? pg_exec($con,"COMMIT TRANSACTION") : pg_exec($con,"ROLLBACK TRANSACTION");
			$select_acao = '';
		}

		$kk = 0;

		if($status_os == 15 AND strlen($msg_erro) == 0){

			$res = pg_exec($con,"BEGIN TRANSACTION");
			
			$sql = "SELECT posto,liberado
					FROM   tbl_extrato
					WHERE  extrato = $extrato
					AND    fabrica = $login_fabrica";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			$posto    = pg_result($res,0,posto);
			$liberado = pg_result($res,0,liberado);

			for ($k = 0 ; $k < $contador ; $k++) {
				if (strlen($msg_erro) > 0) {
					$os[$k]     = $_POST["os_" . $kk];
					$sua_os[$k] = $_POST["sua_os_" . $kk];
					$kk++;
				}
				if (strlen($os[$k]) > 0) {
					$sql = "INSERT INTO tbl_os_status (
									extrato    ,
									os         ,
									observacao ,
									status_os  ,
									admin
								) VALUES (
									$extrato       ,
									$os[$k]        ,
									'$select_acao' ,
									15             ,
									$login_admin
								);";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0) {
							$sql = "UPDATE tbl_os_extra SET extrato = null
									WHERE  tbl_os_extra.os      = $os[$k]
									AND    tbl_os_extra.extrato = $extrato
									AND    tbl_os_extra.os      = tbl_os.os
									AND    tbl_os_extra.extrato = tbl_extrato.extrato
									AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
									AND    tbl_extrato_extra.baixado IS NULL
									AND    tbl_os.fabrica  = $login_fabrica;";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "UPDATE tbl_os SET excluida = true
									WHERE  tbl_os.os           = $os[$k]
									AND    tbl_os.fabrica      = $login_fabrica;";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					#158147 Paulo/Waldir desmarcar se for reincidente
					$sql = "SELECT fn_os_excluida_reincidente($os[$k],$login_fabrica)";
					$res = pg_exec($con, $sql);


						
						// HD 113231
						$sql = "SELECT tbl_os.os
								FROM tbl_os_produto
								JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
								JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca
								WHERE tbl_os_produto.os = $os[$k]
								AND   tbl_pedido.fabrica = $login_fabrica ";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res) > 0){
							if(strlen($liberado) ==0 ) {
								$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
								$res = @pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);
							}else{ 
								$sql = " INSERT INTO tbl_extrato_lancamento (
											posto,
											fabrica,
											lancamento,
											descricao,
											debito_credito,
											historico,
											valor,
											data_lancamento,
											admin
										) SELECT 
											posto,
											fabrica,
											141,
											'Exclusão de OS',
											'D',
											'Débito automático de exclusão de OS do extrato $extrato',
											(SELECT mao_de_obra + pecas FROM tbl_os WHERE os = $os[$k]),
											current_timestamp,
											$login_admin
										FROM tbl_extrato
										WHERE extrato = $extrato
										AND   fabrica = $login_fabrica ";
								$res = pg_exec($con,$sql);
							}
						}
					}
				}
			}
			if (strlen($msg_erro) == 0) {
				if (strlen($posto)> 0){
					$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
			$res = (strlen($msg_erro) == 0) ? pg_exec($con,"COMMIT TRANSACTION") : pg_exec($con,"ROLLBACK TRANSACTION");
			$select_acao = '';
		}
	}
}

if ($btn_acao == 'baixar') {

	if (strlen($_POST["extrato_pagamento"]) > 0) $extrato_pagamento = trim($_POST["extrato_pagamento"]);
	if (strlen($_GET["extrato_pagamento"]) > 0)  $extrato_pagamento = trim($_GET["extrato_pagamento"]);

	$valor_total     = trim($_POST["valor_total"]) ;
	$xvalor_total = (strlen($valor_total) > 0) ? "'".str_replace(",",".",$valor_total)."'" : 'NULL';

	$acrescimo       = trim($_POST["acrescimo"]) ;
	$xacrescimo = (strlen($acrescimo) > 0) ? "'".str_replace(",",".",$acrescimo)."'" : 'NULL';

	$desconto        = trim($_POST["desconto"]) ;
	$xdesconto = (strlen($desconto) > 0) ? "'".str_replace(",",".",$desconto)."'" : 'NULL';

	$valor_liquido   = trim($_POST["valor_liquido"]) ;
	if (strlen($valor_liquido) > 0) {
		$xvalor_liquido = (is_numeric($valor_liquido)) ? $valor_liquido : strtr($valor_liquido, array("."=>"",","=>"."));
	} else {
		$xvalor_liquido = 'NULL';
	}

	if(strlen(trim($_POST["valor_liquido"]))> 0) { // HD 161292
		$sql = " SELECT total from tbl_extrato where extrato = $extrato and fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			if(number_format(pg_fetch_result($res,0,0),2,',','.') <> $valor_liquido) {
				$msg_erro = "O valor liquido deve ser igual ao valor total do extrato";
			}
		}
	}

	$nf_autorizacao = trim($_POST["nf_autorizacao"]) ;
	$xnf_autorizacao = (strlen($nf_autorizacao) > 0) ? "'$nf_autorizacao'" : 'NULL';

	$autorizacao_pagto = trim($_POST["autorizacao_pagto"]) ;
	$xautorizacao_pagto = (strlen($nf_autorizacao) > 0) ? "'$autorizacao_pagto'" : 'NULL';

	$imprime_os= trim($_POST["imprime_os"]) ;

	if (strlen($_POST["data_pagamento"]) > 0) {
		$data_pagamento = trim($_POST["data_pagamento"]) ;
		$xdata_pagamento = str_replace ("/","",$data_pagamento);
		$xdata_pagamento = str_replace ("-","",$xdata_pagamento);
		$xdata_pagamento = str_replace (".","",$xdata_pagamento);
		$xdata_pagamento = str_replace (" ","",$xdata_pagamento);

		$dia = trim (substr ($xdata_pagamento,0,2));
		$mes = trim (substr ($xdata_pagamento,2,2));
		$ano = trim (substr ($xdata_pagamento,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;

		$xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;

		$xdata_pagamento = "'" . $xdata_pagamento . "'";
	}else{
		$xdata_pagamento = "NULL";
	}

	if (strlen($_POST["data_vencimento"]) > 0) {
		$data_vencimento = trim($_POST["data_vencimento"]) ;
		$xdata_vencimento = str_replace ("/","",$data_vencimento);
		$xdata_vencimento = str_replace ("-","",$xdata_vencimento);
		$xdata_vencimento = str_replace (".","",$xdata_vencimento);
		$xdata_vencimento = str_replace (" ","",$xdata_vencimento);

		$dia = trim (substr ($xdata_vencimento,0,2));
		$mes = trim (substr ($xdata_vencimento,2,2));
		$ano = trim (substr ($xdata_vencimento,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;

		$xdata_vencimento = $ano . "-" . $mes . "-" . $dia ;

		$xdata_vencimento = "'" . $xdata_vencimento . "'";
	}else{
		$xdata_vencimento = "NULL";
	}

	if (strlen($_POST["obs"]) > 0) {
		$obs = trim($_POST["obs"]) ;
		$xobs = "'" . $obs . "'";
	}else{
		$xobs = "NULL";
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($extrato_pagamento) > 0) {
			$sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = $login_fabrica AND extrato = $extrato";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) == 0) $msg_erro = "Erro ao cadastrar baixa. Extrato não pertence à esta fábrica.";
		}else{
			$sql = "SELECT extrato_pagamento 
					FROM tbl_extrato_pagamento
					WHERE extrato = $extrato";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(pg_numrows($res) > 0) {
				$extrato_pagamento= pg_result($res,0,extrato_pagamento);
			}
		}

		if (strlen($msg_erro) == 0) {
			if (strlen($extrato_pagamento) > 0) {
				$sql = "UPDATE tbl_extrato_pagamento SET
							extrato           = $extrato           ,
							valor_total       = $xvalor_total       ,
							acrescimo         = $xacrescimo         ,
							desconto          = $xdesconto          ,
							valor_liquido     = $xvalor_liquido     ,
							nf_autorizacao    = $xnf_autorizacao    ,
							data_vencimento   = $xdata_vencimento   ,
							data_pagamento    = $xdata_pagamento    ,
							autorizacao_pagto = $xautorizacao_pagto ,
							obs               = $xobs               ,
							admin             = $login_admin
						WHERE tbl_extrato_pagamento.extrato_pagamento = $extrato_pagamento
						AND   tbl_extrato_pagamento.extrato           = $extrato
						AND   tbl_extrato.fabrica = $login_fabrica";
			}else{
				$sql = "INSERT INTO tbl_extrato_pagamento (
							extrato           ,
							valor_total       ,
							acrescimo         ,
							desconto          ,
							valor_liquido     ,
							nf_autorizacao    ,
							data_vencimento   ,
							data_pagamento    ,
							autorizacao_pagto ,
							obs               ,
							admin
						)VALUES(
							$extrato           ,
							$xvalor_total      ,
							$xacrescimo        ,
							$xdesconto         ,
							$xvalor_liquido    ,
							$xnf_autorizacao   ,
							$xdata_vencimento  ,
							$xdata_pagamento   ,
							$xautorizacao_pagto,
							$xobs              ,
							$login_admin
						)";
			}
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) { // HD 27522
			$sql = "UPDATE tbl_extrato SET
						aprovado = current_timestamp,
						liberado = current_timestamp
					WHERE  tbl_extrato.extrato = $extrato
					AND    tbl_extrato.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(strlen($imprime_os) > 0){  // HD 27522
			$sql =" UPDATE tbl_posto_fabrica SET imprime_os ='t'
					FROM   tbl_extrato
					WHERE  tbl_extrato.posto   = tbl_posto_fabrica.posto
					AND    tbl_extrato.extrato = $extrato
					AND    tbl_posto_fabrica.fabrica=$login_fabrica ";
			$res=pg_exec($con,$sql);
		}
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "excluir") {
	
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");
	
	$sql = "SELECT posto,liberado
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
	
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	$posto    = pg_result($res,0,posto);
	$liberado = pg_result($res,0,liberado);

	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "EXCLUIR";
		}

		$sql =	"INSERT INTO tbl_os_status (
						extrato    ,
						os         ,
						observacao ,
						status_os
					) VALUES (
						$extrato ,
						$x_os    ,
						'$x_obs' ,
						15
					);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os_extra SET extrato = null
						FROM tbl_extrato_extra
						WHERE  tbl_os_extra.os      = $x_os
						AND    tbl_os_extra.extrato = $extrato
						AND    tbl_os_extra.extrato = tbl_extrato_extra.extrato
						AND    tbl_extrato_extra.baixado IS NULL;";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_os SET excluida = true
					WHERE  tbl_os.os           = $x_os
					AND    tbl_os.fabrica      = $login_fabrica;";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($x_os,$login_fabrica);";
			$res = pg_exec($con, $sql);
			$msg_erro = pg_errormessage($con);



			// HD 113231
			$sql = "SELECT tbl_os_produto.os
					FROM tbl_os_produto
					JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
					JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca
					WHERE tbl_os_produto.os = $x_os 
					AND   tbl_pedido.fabrica = $login_fabrica ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0){
				if(strlen($liberado) ==0 ) {
					$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}else{
					$sql = " INSERT INTO tbl_extrato_lancamento (
								posto,
								fabrica,
								lancamento,
								descricao,
								debito_credito,
								historico,
								valor,
								data_lancamento,
								admin
							) SELECT 
								posto,
								fabrica,
								141,
								'Exclusão de OS',
								'D',
								'Débito automático de exclusão de OS do extrato $extrato',
								(SELECT mao_de_obra + pecas FROM tbl_os WHERE os = $x_os),
								current_timestamp,
								$login_admin
							FROM tbl_extrato
							WHERE extrato = $extrato
							AND   fabrica = $login_fabrica ";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			} 
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($posto) > 0){
			// 20274
			$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}


if ($btn_acao == "recusar") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "RECUSAR";
		}
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $x_os, '$x_obs');";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

					// 20274
			$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_numrows($res) > 0) {
			if (@pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
				$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

				// 20274
				$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

//HD 214236: Acrescentando ação REPROVAR OS: zera o valor de mão de obra e faz um lançamento avulso debitando o valor das peças enviadas
if ($btn_acao == "reprovar") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "REPROVAR";
		}

		if (strlen($msg_erro) == 0) {
			$sql = "
			UPDATE
			tbl_os

			SET
			mao_de_obra=0,
			obs = CASE WHEN obs IS NULL THEN '' ELSE obs || '<br>' END || '$x_obs'

			WHERE
			os=$x_os
			";
			$res = @pg_query($con, $sql);
			$msg_erro = pg_errormessage($con);
			
			//Calculando o valor total das peças atendidas. ATENÇÃO: a maioria das fábricas não informa qual pedido_item é específico para atender a cada os_item, como é o caso da Intelbrás, que pediu que esta rotina fosse desenvolvida. Sendo assim, a rotina irá verificar se as peças foram atendidas e fazer um cálculo aproximado. A única situação que se terá certeza que todas as peças daquela OS em específico foram atendidas é quando a quantidade atendida no pedido for superior à solicitada para todas as peças da OS
			$total_aproximado = false;

			$sql = "
			SELECT
			tbl_os_item.os_item,
			tbl_os_item.pedido,
			tbl_os_item.qtde,
			tbl_os_item.peca

			FROM
			tbl_os_produto
			JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_item

			WHERE
			tbl_os_produto.os=10744152
			";
			$res = @pg_query($con, $sql);
			$msg_erro = pg_errormessage($con);

			$total_pecas = 0;

			if (strlen($msg_erro) == 0 && pg_num_rows($res)) {
				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$pedido = pg_result($res, $i, pedido);
					$qtde = pg_result($res, $i, qtde);
					$peca = pg_result($res, $i, peca);
					
					//Status do pedido 14 = Cancelado Total
					$sql = "
					SELECT
					SUM(qtde_faturada) AS qtde_atendida,
					SUM(qtde) AS qtde_solicitada

					FROM
					tbl_pedido_item
					JOIN tbl_pedido ON tbl_pedido_item.pedido=tbl_pedido.pedido

					WHERE
					tbl_pedido_item.peca=$peca
					AND tbl_pedido.pedido=$pedido
					AND tbl_pedido.status_pedido<>14
					";
					$res_atendido = @pg_query($con, $sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0) {
						if (pg_num_rows($res_atendido)) {
							//Quantidade total da peça atendida no pedido
							$qtde_atendida = pg_result($res_atendido, 0, qtde_atendida);
							//Quantidade total da peça solicitada no pedido
							$qtde_solicitada = pg_result($res_atendido, 0, qtde_solicitada);
						}
						else {
							$qtde_atendida = 0;
						}

						if ($qtde_atendida > 0) {
							//Como não dá para determinar qual pedido_item está atendendo o os_item específico, fiz a média dos precos daquela peca no pedido
							$sql = "SELECT AVG(preco) AS preco FROM tbl_pedido_item WHERE pedido=$pedido AND peca=$peca";
							$res_preco = @pg_query($con, $sql);
							$msg_erro = pg_errormessage($con);

							if (strlen($msg_erro) == 0) {
								$preco = pg_result($res_preco, 0, preco);
								
								//Se no pedido foi atendido quantidade maior ou igual à solicitada no pedido, então é sabido que para aquela OS foram atendidas todas as peças, seguramente
								if ($qtde_atendida >= $qtde_solicitada) {
									$total_pecas += $qtde*$preco;
								}
								//Se a quantidade atendida no pedido for menor que a quantidade total solicitada no pedido (else), não tem-se certeza de que foram atendidas todas as peças para aquela OS em específico (não amarra os_item com pedido_item), então faz-se sempre pelo pior caso
								elseif ($qtde_atendida >= $qtde) {
									$total_aproximado = true;
									$total_pecas += $qtde*$preco;
								}
								else {
									$total_aproximado = true;
									$total_pecas += $qtde_atendida*$preco;
								}
							}
						}
					}
				}
			}

			if ($total_pecas > 0) {
				$total_pecas = $total_pecas*(-1);

				if ($total_aproximado) {
					$total_aproximado = " (ATENÇÃO: Total aproximado, pode haver divergências, pois nem todas as peças do pedido desta OS foram atendidas)";
				}
				else {
					$total_aproximado = "";
				}

				$sql = "
				INSERT INTO
				tbl_extrato_lancamento (
				posto,
				fabrica,
				extrato,
				lancamento,
				descricao,
				debito_credito,
				historico,
				valor,
				admin
				)
				
				SELECT
				posto,
				fabrica,
				$extrato,
				142,
				(SELECT descricao FROM tbl_lancamento WHERE lancamento=142),
				'D',
				'Débito do valor das peças da OS $x_os, REPROVADA do extrato $extrato$total_aproximado',
				$total_pecas,
				$login_admin

				FROM
				tbl_os

				WHERE
				os=$x_os
				";
				$res = @pg_query($con, $sql);
				$msg_erro = pg_errormessage($con);
			}

			$sql = "SELECT fn_totaliza_extrato($login_fabrica, $extrato);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}


	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $PHP_SELF;
		header ("location: $link?extrato=$extrato");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "acumular") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "ACUMULAR";
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $x_os, '$x_obs');";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			// 20274
			$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_numrows($res) > 0) {
			if (@pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
				$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

				// 20274
				$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "acumulartudo") {
	if (strlen($extrato) > 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

		$sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		// 20274
		$sql = "UPDATE tbl_extrato_excluido set admin=$login_admin where extrato=$extrato AND data_exclusao IS NOT NULL";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$link = $_COOKIE["link"];
			header ("Location: $link");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}

$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços";
include "cabecalho.php";

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para saber se tem anexo:temNF($os, 'bool');
	Para saber se 2º anexo: temNF($os, 'bool', 2);
	Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
							echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
							echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
include '../anexaNF_inc.php';

?>
<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;

}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<script type='text/javascript' src='js/jquery-1.3.2.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="js/jquery-ui-1.7.2.custom.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.tablesorter20090219waldir.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="js/chili-1.8b.js"></script>
<script type="text/javascript" src="js/docs.js"></script>
<script>
	$(function() {
		$.tablesorter.addWidget({
			id: "repeatHeaders",
			format: function(table) {
				if(!this.headers) {
					var h = this.headers = [];
					$("thead th",table).each(function() {
						h.push(
							"<th>" + $(this).text() + "</th>"
						);
					});
				}
				$("tr.repated-header",table).remove();
				for(var i=0; i < table.tBodies[0].rows.length; i++) {
					// insert a copy of the table head every 10th row
				}
			}
		});
		$("table").tablesorter({
			widgets: ['zebra','repeatHeaders']
		});
	});

</script>


<script language="JavaScript">
var ok = false;
function checkaTodos() {
	f = document.frm_extrato_os;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}

</script>
<?
function RemoveAcentos($Msg){
	$a = array(
		'/[ÂÀÁÄÃ]/'=>'A',
		'/[âãàáä]/'=>'a',
		'/[ÊÈÉË]/'=>'E',
		'/[êèéë]/'=>'e',
		'/[ÎÍÌÏ]/'=>'I',
		'/[îíìï]/'=>'i',
		'/[ÔÕÒÓÖ]/'=>'O',
		'/[ôõòóö]/'=>'o',
		'/[ÛÙÚÜ]/'=>'U',
		'/[ûúùü]/'=>'u',
		'/ç/'=>'c',
		'/Ç/'=>'C');
	// Tira o acento pela chave do array
	return preg_replace(array_keys($a), array_values($a), $Msg);
}
?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } ?>

<?
echo "<FORM METHOD=POST NAME='frm_extrato_os' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='extrato' value='$extrato'>";
echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
echo "<input type='hidden' name='btn_acao' value=''>";
?>

<?
//HD 215808: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
//			 de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
$sql2 = "SELECT  liberado ,aprovado
		FROM tbl_extrato
		WHERE extrato = $extrato
		AND   fabrica = $login_fabrica";
$res2 = pg_query($con,$sql2);
$liberado = pg_fetch_result($res2,0,liberado);
$aprovado = pg_fetch_result($res2,0,aprovado);

/*
Verifica de a ação é "RECUSAR" ou "ACUMULAR"
para somente mostrar a tela para a digitação da observação.
*/
if (strlen($select_acao) == 0) {
	$sql =	"SELECT DISTINCT LPAD(tbl_os.sua_os,10,'0')                                  AS ordem           ,
					tbl_os.os                                                                      ,
					tbl_os.obs as obs_os                                                       ,
					tbl_os.sua_os                                                                   ,
					tbl_os.serie                                                                    ,
					tbl_os.consumidor_nome                                                          ,
					tbl_os.revenda_nome                                                             ,
					tbl_os.pecas                                                AS total_pecas     ,
					tbl_os.consumidor_revenda                                                      ,
					tbl_os.mao_de_obra                                          AS total_mo        ,
					tbl_produto.referencia                                      AS produto_referencia ,
					tbl_produto.descricao                                       AS produto_descricao  ,
					subproduto.referencia                                       AS subproduto_referencia ,
					subproduto.descricao                                        AS subproduto_descricao ,
					tbl_defeito_constatado.descricao                            AS defeito_constatado    ,
					tbl_peca.referencia                                         AS peca_referencia       ,
					tbl_peca.descricao                                          AS peca_descricao        ,
					tbl_os.solucao_os                                           AS solucao_os ,
					tbl_servico_realizado.descricao                             AS servico_realizado  ,
					tbl_os_item.posicao                                                               ,
					tbl_os_extra.extrato                                                            ,
					tbl_os_extra.os_reincidente                                                     ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
					tbl_extrato.total                                           AS total           ,
					tbl_extrato.mao_de_obra                                     AS mao_de_obra     ,
					tbl_extrato.pecas                                           AS pecas           ,
					LPAD(tbl_extrato.protocolo,5,'0')                           AS protocolo       ,
					tbl_posto_fabrica.codigo_posto                              AS codigo_posto    ,
					tbl_posto.nome                                              AS nome_posto      ,
					tbl_extrato_pagamento.valor_total                                               ,
					tbl_extrato_pagamento.acrescimo                                                 ,
					tbl_extrato_pagamento.desconto                                                  ,
					tbl_extrato_pagamento.valor_liquido                                             ,
					tbl_extrato_pagamento.nf_autorizacao                                            ,
					TO_CHAR(tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
					TO_CHAR(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
					tbl_extrato_pagamento.autorizacao_pagto                                         ,
					tbl_extrato_pagamento.obs                                                       ,
					tbl_extrato_pagamento.extrato_pagamento
		FROM      tbl_extrato
		JOIN      tbl_posto                 ON  tbl_posto.posto                           = tbl_extrato.posto
		JOIN      tbl_posto_fabrica         ON  tbl_posto_fabrica.posto                   = tbl_extrato.posto
											AND tbl_posto_fabrica.fabrica                 = tbl_extrato.fabrica
		LEFT JOIN      tbl_os_extra              ON  tbl_os_extra.extrato                      = tbl_extrato.extrato
		LEFT JOIN      tbl_os                    ON  tbl_os.os                                 = tbl_os_extra.os
		LEFT JOIN      tbl_produto               ON  tbl_produto.produto                       = tbl_os.produto
		LEFT JOIN tbl_os_produto            ON  tbl_os_produto.os                         = tbl_os.os
		LEFT JOIN tbl_os_item               ON  tbl_os_produto.os_produto                 = tbl_os_item.os_produto
		LEFT JOIN tbl_produto AS subproduto ON  subproduto.produto                        = tbl_os_produto.produto
		LEFT JOIN tbl_lista_basica          ON  tbl_lista_basica.produto                  = tbl_os_produto.produto
											AND tbl_lista_basica.peca                     = tbl_os_item.peca
											AND TRIM(tbl_lista_basica.posicao)            = TRIM(tbl_os_item.posicao)
		LEFT JOIN tbl_defeito_constatado    ON  tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
		LEFT JOIN tbl_servico_realizado     ON  tbl_servico_realizado.servico_realizado   = tbl_os_item.servico_realizado
		LEFT JOIN tbl_peca                  ON  tbl_peca.peca                             = tbl_os_item.peca
		LEFT JOIN tbl_extrato_pagamento     ON  tbl_extrato_pagamento.extrato             = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_extrato.extrato = $extrato
		ORDER BY tbl_os.serie ASC";
	$res = pg_exec($con,$sql);

$ja_baixado = false ;

if (@pg_numrows($res) == 0) {
	echo "<h1>Nenhum resultado encontrado.</h1>";
}else{
	?>
	<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td bgcolor="#FFCCCC">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>REINCIDÊNCIAS</b></td>
	</tr>
	</table>
	<br>
<?
	if (strlen ($msg_erro) == 0) {
		$extrato_pagamento = pg_result ($res,0,extrato_pagamento) ;
		$valor_total       = pg_result ($res,0,valor_total) ;
		$acrescimo         = pg_result ($res,0,acrescimo) ;
		$desconto          = pg_result ($res,0,desconto) ;
		$valor_liquido     = pg_result ($res,0,valor_liquido) ;
		$nf_autorizacao    = pg_result ($res,0,nf_autorizacao) ;
		$data_vencimento   = pg_result ($res,0,data_vencimento) ;
		$data_pagamento    = pg_result ($res,0,data_pagamento) ;
		$obs               = pg_result ($res,0,obs) ;
		$autorizacao_pagto = pg_result ($res,0,autorizacao_pagto) ;
		$codigo_posto      = pg_result ($res,0,codigo_posto) ;
		$protocolo         = pg_result ($res,0,protocolo) ;
		$obs_os            = pg_result ($res,0,obs_os) ;
	}

	$sql = "SELECT count(*) as qtde
			FROM   tbl_os_extra
			WHERE  tbl_os_extra.extrato = $extrato";
	$resx = pg_exec($con,$sql);

	if (pg_numrows($resx) > 0) $qtde_os = pg_result($resx,0,qtde);

	if (strlen ($extrato_pagamento) > 0) $ja_baixado = true ;

	echo "<TABLE width='700' border='0' cellspacing='1' cellpadding='0' align='center'>";

	echo"<TR class='menu_top'>";
	echo"<TD align='left'> Extrato: ";
	echo ($login_fabrica == 1) ? $protocolo : $extrato;
	echo "</TD>";
	echo "<TD align='left'> Data: " . pg_result ($res,0,data_geracao) . "</TD>";
	echo"<TD align='left'> Qtde de OS: $qtde_os</TD>";
	echo"<TD align='left'> Total: R$ " . number_format(pg_result ($res,0,total),2,",",".") . "</TD>";
	echo"</TR>";

	echo"<TR class='menu_top'>";
	echo"<TD align='left'> Código: " . pg_result ($res,0,codigo_posto) . " </TD>";
	echo"<TD align='left' colspan='3'> Posto: " . pg_result ($res,0,nome_posto) . "  </TD>";
	echo"</TR>";
	echo"</TABLE>";
	echo"<br>";

	//HD 215808: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
	//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
	//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
	if (strlen($aprovado) == 0) {
		$sql = "SELECT pedido FROM tbl_pedido WHERE pedido_kit_extrato = $extrato";
		$resE = pg_exec($con,$sql);
		if (pg_numrows($resE) == 0) echo "<img src='imagens/btn_pedidopecaskit.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedido de Peças do Kit' border='0' style='cursor:pointer;'>";
	}
	
	//Andreus
	$codigo_posto2 = $codigo_posto;
	#include('posto_extrato_ano_padrao.php');
	echo "<br>";
	echo "<br>";

	echo "<TABLE width='700' id='table' align='center' border='0' cellspacing='1' cellpadding='1' align='center'>\n";

	if (strlen($msg) > 0) {
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan=9>$msg</TD>\n";
		echo "</TR>\n";
	}
	echo "<thead>";
	echo "<TR class='menu_top'>\n";
	//HD 215808: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
	//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
	//			 de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
	if (strlen($aprovado) == 0 && $ja_baixado == false) {
		echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></TD>\n";
	}

	echo "<th>OS</TD>\n";
	//HD 214236: Legendas para auditoria de OS da Intelbras. Como vão ser auditadas todas as OS, criei uma coluna nova para que sinalize o status da auditoria
	if ($login_fabrica == 14 || $login_fabrica == 43) {
		echo "<td>AUDITORIA</td>";
	}
	echo "<th>CLIENTE</th>\n";
	echo "<th>PRODUTO</th>\n";
	echo "<th>SUBPRODUTO</th>\n";
	echo "<th>SÉRIE</th>\n";
	echo "<th>DEFEITO</th>\n";
	echo "<th>COMPONENTE</th>\n";
	echo "<th>SERVIÇO</th>\n";
	echo "<th>POSIÇÃO</th>\n";
	echo "<th>MO</th>\n";
	echo "<th>PEÇAS</th>\n";
	echo "<TD>OBS</TD>\n";
	echo "</TR>\n";
	echo "</thead>";
	echo "<tbody>";

	$j = 0;
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$os                    = trim(pg_result($res,$i,os));
		$sua_os                = trim(pg_result($res,$i,sua_os));
		$serie                 = trim(pg_result($res,$i,serie));
		$consumidor_nome       = trim(pg_result($res,$i,consumidor_nome));
		$consumidor_revenda    = trim(pg_result($res,$i,consumidor_revenda));
		$revenda_nome          = trim(pg_result($res,$i,revenda_nome));
		$produto_referencia    = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao     = trim(pg_result($res,$i,produto_descricao));
		$subproduto_referencia = trim(pg_result($res,$i,subproduto_referencia));
		$subproduto_descricao  = trim(pg_result($res,$i,subproduto_descricao));
		$defeito_constatado    = trim(pg_result($res,$i,defeito_constatado));
		$peca_referencia       = trim(pg_result($res,$i,peca_referencia));
		$peca_descricao        = trim(pg_result($res,$i,peca_descricao));
		$solucao_os            = trim(pg_result($res,$i,solucao_os));
		$servico_realizado     = trim(pg_result($res,$i,servico_realizado));
		$posicao               = trim(pg_result($res,$i,posicao));
		$os_reincidente        = trim(pg_result($res,$i,os_reincidente));
		$codigo_posto          = trim(pg_result($res,$i,codigo_posto));
		$total_pecas           = trim(pg_result($res,$i,total_pecas));
		$total_mo              = trim(pg_result($res,$i,total_mo));
		$texto                 = "";
		//HD 214236: Estava recuperando sempre pg_result($res, 0, obs_os), assim vem sempre a obervação da primeira OS. Corrigido
		$obs_os				   = trim(pg_result($res,$i,obs_os));

		$cor = ($j % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		$btn = ($j % 2 == 0) ? "azul" : "amarelo";

		if (strlen($os_reincidente) > 0) {
			$texto = "-R";
			$cor   = "#FFCCCC";
		}

		if (strlen($solucao_os) > 0 && strlen($servico_realizado) == 0) {
			$sqlS =	"SELECT descricao
					FROM   tbl_servico_realizado
					WHERE  servico_realizado = $solucao_os;";
			$resS = pg_exec($con,$sqlS);
			if (pg_numrows($resS) > 0) {
				$servico_realizado = trim(pg_result($resS,0,0));
			}
		}

		echo "<TR class='table_line' style='background-color: $cor;'>\n";

		if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;

		//HD 119408
		if ($consumidor_revenda=='R'){
			if (strlen($revenda_nome) == 0){
				$revenda_nome = $consumidor_nome;
			}
		}

		if ($os != $os_anterior) {
			//HD 215808: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
			//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
			//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
			if (strlen($aprovado) == 0 && $ja_baixado == false ) {
				echo "<TD align='center'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
			}
			echo "<TD nowrap><a href='os_press.php?os=$os' target='_blank'>" . $sua_os . $texto ."</a>";
			$imgURL = current(temNF($os, 'url'));
			if ($imgURL) echo  "<a href='$imgURL' target='_blank'><img src='../helpdesk/imagem/clips.gif' alt='Com Anexo'></a>\n";
			echo "</TD>\n";

			//HD 214236: Legendas para auditoria de OS da Intelbras. Como vão ser auditadas todas as OS, criei uma coluna nova para que sinalize o status da auditoria
			$auditoria_travar_opcoes = false;

			if ($login_fabrica == 14 || $login_fabrica == 43) {
				$sql = "
				SELECT
				liberado,
				cancelada,
				admin

				FROM
				tbl_os_auditar

				WHERE
				os_auditar IN (
					SELECT
					MAX(os_auditar)

					FROM
					tbl_os_auditar

					WHERE
					os=$os
				)
				";
				$res_auditoria = pg_query($con, $sql);

				if (pg_num_rows($res_auditoria)) {
					$liberado = pg_result($res_auditoria, 0, liberado);
					$cancelada = pg_result($res_auditoria, 0, cancelada);
					$admin = pg_result($res_auditoria, 0, admin);

					if ($liberado == 'f') {
						if ($cancelada == 'f') {
							$legenda_status = "em análise";
							$status_comentario = "OS ainda em auditoria prévia. Verificar com o HelpDesk, pois não deveria entrar em extrato e ficar em auditoria";
							$cor_status = "#FFFF44";
							$auditoria_travar_opcoes = true;
						}
						elseif ($cancelada == 't') {
							$legenda_status = "reprovada";
							$status_comentario = "OS reprovada pelo admin na auditoria prévia. Verificar com o HelpDesk, pois não deveria entrar em extrato e ficar em auditoria";
							$cor_status = "#FF7744";
						}
						else {
							//status inválido
							$legenda_status = "";
							$cor_status = "";
						}
					}
					elseif ($liberado == 't') {
						if (strlen($admin)) {
							$legenda_status = "aprovada";
							$status_comentario = "OS aprovada pelo admin na auditoria prévia. Clique no número da OS para detalhes";
							$cor_status = "#44FF44";
						}
						else {
							$legenda_status = "sistema";
							$status_comentario = "OS liberada pelo sistema da auditoria prévia. Efetuar análise da OS. Clique no número da OS para detalhes";
							$cor_status = "#00FFFF";
						}
					}
					else {
						$legenda_status = "";
						$cor_status = "";
					}
				}
				else {
					$legenda_status = "sem auditoria";
					$status_comentario = "OS digitada antes do início do processo de auditoria prévia. Efetuar análise da OS";
					$cor_status = "#FFFFFF";
				}

				echo "<td style='background:$cor_status' nowrap align='center'><acronym style='cursor: help;' title='$status_comentario'>$legenda_status</acronym></td>";
			}
			//HD 214236::: FIM :::

			//HD 7705 Paulo
			if($login_fabrica == 14){
				if($consumidor_revenda=='C'){
					echo "<TD nowrap><acronym title='CLIENTE: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></TD>\n";
				}elseif($consumidor_revenda=='R'){
					echo "<TD nowrap><acronym title='REVENDA: $revenda_nome' style='cursor: help;'>" . substr($revenda_nome,0,20) . "</acronym></TD>\n";
				}

			}else{
				echo "<TD nowrap><acronym title='CLIENTE: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,20) . "</acronym></TD>\n";
			}
			echo "<TD nowrap><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: help;'>" . substr($produto_descricao,0,15) . "</acronym></TD>\n";
			echo "<TD nowrap><acronym title='REFERÊNCIA: $subproduto_referencia\nDESCRIÇÃO: $subproduto_descricao' style='cursor: help;'>" . substr($subproduto_descricao,0,15) . "</acronym></TD>\n";
			echo "<TD nowrap>$serie</TD>\n";
			echo "<TD nowrap><acronym title='DEFEITO: $defeito_constatado' style='cursor: help;'>" . substr($defeito_constatado,0,15) . "</acronym></TD>\n";
			echo "<TD nowrap><acronym title='REFERÊNCIA: $peca_referencia\nDESCRIÇÃO: $peca_descricao' style='cursor: help;'>" . substr($peca_descricao,0,15) . "</acronym></TD>\n";
			echo "<TD nowrap><acronym title='SERVIÇO: $servico_realizado' style='cursor: help;'>" . substr($servico_realizado,0,15) . "</acronym></TD>\n";
			echo "<TD nowrap>$posicao</TD>\n";
			echo "<TD nowrap>" . number_format($total_mo,2,",",".") . "</TD>\n";
			echo "<TD nowrap>" . number_format($total_pecas,2,",",".") . "</TD>\n";
			echo "<TD nowrap>&nbsp;$obs_os</TD>\n";
			$j++;
		}else{
			if ($ja_baixado == false ) echo "<TD>&nbsp</TD>";
			echo "<td><font style='display: none'>$sua_os  $texto</td>";
			echo "<td><font style='display: none'></td>";
			echo "<TD><font style='display: none'>$consumidor_nome</TD>";
			echo "<TD><font style='display: none'>$produto_descricao</TD>";
			echo "<TD nowrap><acronym title='REFERÊNCIA: $subproduto_referencia\nDESCRIÇÃO: $subproduto_descricao' style='cursor: help;'>" . substr($subproduto_descricao,0,15) . "</acronym></TD>\n";
			echo "<TD><font style='display: none'>$serie</TD>";
			echo "<TD><font style='display: none'>$defeito_constatado</TD>";
			echo "<TD nowrap><acronym title='REFERÊNCIA: $peca_referencia\nDESCRIÇÃO: $peca_descricao' style='cursor: help;'>" . substr($peca_descricao,0,15) . "</acronym></TD>\n";
			echo "<TD nowrap><acronym title='SERVIÇO: $servico_realizado' style='cursor: help;'>" . substr($servico_realizado,0,15) . "</acronym></TD>\n";
			echo "<TD nowrap><font style='display: none'>$posicao</TD>\n";
			echo "<TD>". number_format($total_mo,2,",",".") ."</TD>";
			echo "<TD>" . number_format($total_pecas,2,",",".") . "</TD>";
			echo "<TD nowrap>&nbsp;$obs_os</TD>\n";
		}
		echo "</TR>\n";
		$os_anterior = $os;
	}

	//HD 215808: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
	//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
	//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
	if (strlen($extrato_valor) == 0 AND $ja_baixado == false && strlen($aprovado) == 0) {
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan='12' align='left'> &nbsp; &nbsp; &nbsp; <img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; COM MARCADOS: &nbsp; ";
		echo "<select name='select_acao' size='1' class='frm'>";
		echo "<option value=''></option>";
		echo "<option value='RECUSAR'";  if ($_POST["select_acao"] == "RECUSAR")  echo " selected"; echo ">RECUSADO PELO FABRICANTE</option>";
		echo "<option value='REPROVAR'";  if ($_POST["select_acao"] == "REPROVAR")  echo " selected"; echo ">REPROVAR OS</option>";
		echo "<option value='EXCLUIR'";  if ($_POST["select_acao"] == "EXCLUIR")  echo " selected"; echo ">EXCLUÍDA PELO FABRICANTE</option>";
		echo "<option value='ACUMULAR'"; if ($_POST["select_acao"] == "ACUMULAR") echo " selected"; echo ">ACUMULAR PARA PRÓXIMO EXTRATO</option>";

		if($login_fabrica == 14){

			$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 13 AND liberado IS TRUE;";
			$res = pg_exec($con,$sql);

			if(pg_numrows($res) > 0) {
				echo "<option value=''>-->RECUSAR OS</option>";

				for($l=0;$l<pg_numrows($res);$l++){
					$motivo_recusa = pg_result($res,$l,motivo_recusa);
					$motivo        = pg_result($res,$l,motivo);
					$motivo = substr($motivo,0,50);
					echo "<option value='$motivo_recusa'>$motivo</option>";
				}
			}
			$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 14 AND liberado IS TRUE;";
			$res = pg_exec($con,$sql);

			if(pg_numrows($res) > 0) {
				echo "<option value=''>-->ACUMULAR OS</option>";

				for($l=0;$l<pg_numrows($res);$l++){
					$motivo_recusa = pg_result($res,$l,motivo_recusa);
					$motivo        = pg_result($res,$l,motivo);
					$motivo = substr($motivo,0,50);
					echo "<option value='$motivo_recusa'>$motivo</option>";
				}
			}
			$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 15 AND liberado IS TRUE;";
			$res = pg_exec($con,$sql);

			if(pg_numrows($res) > 0) {
				echo "<option value=''>-->EXCLUIR OS</option>";

				for($l=0;$l<pg_numrows($res);$l++){
					$motivo_recusa = pg_result($res,$l,motivo_recusa);
					$motivo        = pg_result($res,$l,motivo);
					$motivo = substr($motivo,0,50);
					echo "<option value='$motivo_recusa'>$motivo</option>";
				}
			}
		}

		echo "</select>";
		echo " &nbsp; <img border='0' src='imagens/btn_continuar.gif' align='absmiddle' onclick='javascript: document.frm_extrato_os.submit()' style='cursor: hand;'>";
		echo "</TD>\n";
		echo "</TR>\n";
	}
	echo "<input type='hidden' name='contador' value='$i'>";
	echo "</TABLE>\n";
}//FIM ELSE

} // Fecha a visualização dos extratos

// ##### EXIBE AS OS QUE SERÃO ACUMULADAS OU RECUSADAS ##### //
//HD 214236: Acrescentada a opção REPROVAR OS
if (strtoupper($select_acao) == "RECUSAR" OR strtoupper($select_acao) == "EXCLUIR" OR strtoupper($select_acao) == "ACUMULAR" || strtoupper($select_acao) == "REPROVAR") {
	$os     = $_POST["os"];
	$sua_os = $_POST["sua_os"];

	echo "<br>\n";
	echo "<HR WIDTH='600' ALIGN='CENTER'>\n";
	echo "<br>\n";

	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' align='center'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='2'>";
	echo "Preencha o campo observação informando o motivo<br>pelo qual será ";
	if (strtoupper($select_acao) == "RECUSAR") echo "RECUSADO PELO FABRICANTE";
	elseif (strtoupper($select_acao) == "EXCLUIR") echo "EXCLUÍDA PELO FABRICANTE";
	elseif (strtoupper($select_acao) == "ACUMULAR") echo "ACUMULAR PARA PRÓXIMO EXTRATO";
	//HD 214236: Acrescentada opção REPROVAR OS
	elseif (strtoupper($select_acao) == "REPROVAR") echo "REPROVAR OS";
	else echo "$select_acao";

	echo "</TD>\n";
	echo "</tr>\n";
	$kk = 0;
	for ($k = 0 ; $k < $contador ; $k++) {
		if ($k == 0) {
			echo "<tr class='menu_top'>\n";
			echo "<td>OS</td>\n";
			echo "<td>OBSERVAÇÃO</td>\n";
			echo "</tr>\n";
		}

		if (strlen($msg_erro) > 0) {
			$os[$k]     = $_POST["os_" . $kk];
			$sua_os[$k] = $_POST["sua_os_" . $kk];
			$obs2        = $_POST["obs_" . $kk];
		}

		if((strtoupper($select_acao) <> "ACUMULAR") AND (strtoupper($select_acao) <> "EXCLUIR") AND (strtoupper($select_acao) <> "RECUSAR") && (strtoupper($select_acao) <> "REPROVAR")){
			$obs2         = "$select_acao";
			$select_acao = "RECUSAR";
		}

		$cor = ($kk % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		if ($linha_erro == $kk && strlen($linha_erro) != 0) $cor = "FF0000";

		if (strlen($os[$k]) > 0) {
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td align='center'>";
			//HD 214236: Acrescentei o link para a OS
			echo "<a href='os_press.php?os=" . $os[$k] . "' target='_blank'>";
			echo $os[$k];
			echo "</a>";
			echo "<input type='hidden' name='os_$kk' value='" . $os[$k] . "'></td>\n";
			$obs2 = RemoveAcentos($obs2);
			$obs2 = strtoupper($obs2);
			echo "<td align='center'><textarea name='obs_$kk' rows='1' cols='100' class='frm'>$obs2</textarea></td>\n";
			echo "</tr>\n";
			$kk++;
		}
	}
	echo "</table>\n";
	echo "<input type='hidden' name='qtde_os' value='$kk'>";
	echo "<br>\n";
	echo "<img border='0' src='imagens/btn_confirmaralteracoes.gif' style='cursor: hand;' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='$select_acao'; document.frm_extrato_os.submit(); }else{ alert('Aguarde submissão'); }\" alt='Confirmar Alterações'>\n";
	echo "<br>\n";
}

echo "<br>";

##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
if ($login_fabrica == 1) {
	$sql = "SELECT 'OS SEDEX' AS descricao ,
					tbl_extrato_lancamento.descricao AS descricao_lancamento ,
					tbl_extrato_lancamento.os_sedex ,
					'' AS historico ,
					tbl_extrato_lancamento.historico AS historico_lancamento,
					tbl_extrato_lancamento.automatico,
					sum(tbl_extrato_lancamento.valor) as valor
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			GROUP BY tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.automatico,
						tbl_extrato_lancamento.descricao,
						tbl_extrato_lancamento.historico;";
}else{
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.os_sedex  ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     ,
					tbl_extrato_lancamento.automatico
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			ORDER BY    tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.descricao";
}
$res_avulso = pg_exec($con,$sql);

if (pg_numrows($res_avulso) > 0) {
	$colspan = ($login_fabrica == 1) ? 5 : 4;
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' align='center'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='$colspan'>LANÇAMENTO DE EXTRATO AVULSO</td>\n";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td>DESCRIÇÃO</td>\n";
	echo "<td>HISTÓRICO</td>\n";
	echo "<td>VALOR</td>\n";
	echo "<td>AUTOMÁTICO</td>\n";
	echo ($login_fabrica == 1) ? "<td>AÇÕES</td>\n" : "";
	echo "</tr>\n";
	for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
		$cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		$descricao            = pg_result($res_avulso, $j, descricao);
		$historico            = pg_result($res_avulso, $j, historico);
		$os_sedex             = pg_result($res_avulso, $j, os_sedex);

		if ($login_fabrica == 1){
			if (strlen($os_sedex) == 0){
				$descricao = @pg_result($res_avulso, $j, descricao_lancamento);
				$historico = @pg_result($res_avulso, $j, historico_lancamento);
			}
		}
		echo "<tr height='18' class='table_line' style='background-color: $cor;'>\n";
		echo "<td width='35%'>" . $descricao . "</td>";
		echo "<td width='35%'>" . $historico . "</td>";
		echo "<td width='10%' align='right' nowrap> R$ " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";
		echo "<td width='10%' align='center' nowrap>" ;
		echo (pg_result($res_avulso, $j, automatico) == 't') ? "S" : "&nbsp;";
		echo "</td>";
		echo "<td width='10%' align='center' nowrap>";
		echo ($login_fabrica == 1 AND strlen($os_sedex) > 0) ? "<a href='sedex_finalizada.php?os_sedex=" . $os_sedex . "' target='_blank'><img border='0' src='imagens/btn_consulta.gif' style='cursor: hand;' alt='Consultar OS Sedex'>" : "";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>\n";
	echo "<br>\n";
}
##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

##### VERIFICA BAIXA MANUAL #####
$sql = "SELECT posicao_pagamento_extrato_automatico FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);
$posicao_pagamento_extrato_automatico = pg_result($res,0,posicao_pagamento_extrato_automatico);

if ($posicao_pagamento_extrato_automatico == 'f' and $login_fabrica <> 1) {
?>

<HR WIDTH='600' ALIGN='CENTER'>

<TABLE width='700' border='0' cellspacing='1' cellpadding='0' align='center'>
<TR>
	<TD height='20' class="menu_top2" colspan='4'>PAGAMENTO</TD>
</TR>
<TR>
	<TD align='left' class="menu_top2"><center>VALOR TOTAL (R$)</center></TD>
	<TD align='left' class="menu_top2"><center>ACRÉSCIMO (R$)</center></TD>
	<TD align='left' class="menu_top2"><center>DESCONTO (R$)</center></TD>
	<TD align='left' class="menu_top2"><center>VALOR LÍQUIDO (R$)</center></TD>
</TR>

<TR>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_total'  size='10' maxlength='10' value='" . $valor_total . "' style='text-align:right' class='frm'>";
	else                      echo number_format($valor_total,2,',','.');
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='acrescimo'  size='10' maxlength='10' value='" . $acrescimo . "' style='text-align:right' class='frm'>";
	else                      echo number_format($acrescimo,2,',','.');
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='desconto'  size='10' maxlength='10' value='" . $desconto . "' style='text-align:right' class='frm'>";
	else                      echo number_format($desconto,2,',','.');
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_liquido'  size='10' maxlength='10' value='" . $valor_liquido . "' style='text-align:right' class='frm'>";
	else                      echo number_format($valor_liquido,2,',','.');
?>
	</TD>
</TR>

<TR>
	<TD align='left' class="menu_top2"><center>DATA DE VENCIMENTO</center></TD>
	<TD align='left' class="menu_top2"><center>Nº NOTA FISCAL</center></TD>
	<TD align='left' class="menu_top2"><center>DATA DE PAGAMENTO</center></TD>
	<TD align='left' class="menu_top2"><center>AUTORIZAÇÃO Nº</center></TD>
</TR>

<TR>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_vencimento'  size='10' maxlength='10' value='" . $data_vencimento . "' class='frm'>";
	else                      echo $data_vencimento;
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='nf_autorizacao'  size='10' maxlength='20' value='" . $nf_autorizacao . "' class='frm'>";
	else                      echo $nf_autorizacao;
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_pagamento' id='data_pagamento' size='10' maxlength='10' value='" . $data_pagamento . "' class='frm'>";
	else                      echo $data_pagamento;
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='autorizacao_pagto' size='10' maxlength='20' value='" . $autorizacao_pagto . "' class='frm'>";
	else                      echo $autorizacao_pagto;
?>
	</TD>
</TR>

<TR>
	<TD align='left' class="menu_top2" colspan='4'><center>OBSERVAÇÃO</center></TD>
</TR>
<TR>
	<TD align='center' colspan='4' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='obs'  size='96' maxlength='255' value='" . $obs . "' class='frm'>";
	else                      echo $obs;
?>
	</TD>
</TR>
</TABLE>

<BR>

<?
if ($ja_baixado == false){
	echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
	echo "<input type='hidden' name='data_final' value='$data_final'>";
	echo "<input type='hidden' name='cnpj' value='$cnpj'>";
	echo "<input type='hidden' name='razao' value='$razao'>";
	echo"<TABLE width='400' align='center' border='0' cellspacing='1' cellpadding='0'>";
	echo"<TR>";
	echo"<TD><img src='imagens/btn_baixar.gif' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='baixar' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Baixar' border='0' style='cursor:pointer;'></TD>";
	echo "<td align='center' nowrap>Liberar 10%";
	echo " <input type='checkbox' class='frm' name='imprime_os' value='t' ";
	if($imprime_os == 't') echo " checked ";
	echo ">";
	echo "</td>\n";
	echo"</TR>";
	echo"</TABLE>";
}

} // fecha verificação se fábrica usa baixa manual

?>
</FORM>
<br>

<center>
<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;'>
<? if ($login_fabrica == 1) { ?>
<img src='imagens/btn_imprimirsimplificado_15.gif' onclick="javascript: window.open('os_extrato_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Simplificado' border='0' style='cursor:pointer;'>
<img src='imagens/btn_imprimirdetalhado_15.gif' onclick="javascript: window.open('os_extrato_detalhe_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Detalhado' border='0' style='cursor:pointer;'>
<? } ?>
<br><br>
<img border='0' src='imagens/btn_voltar.gif' onclick="javascript: history.back(-1);" alt='Voltar' style='cursor: hand;'>
</center>

<? include "rodape.php"; ?>
