<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";

if (!isset($_GET['backup'])) {
	include "autentica_admin.php";
} else {
	$login_fabrica = 3;
}

$campos_adicionais = json_encode(array('aprovacao' => true));

include "funcoes.php";

if (strlen($_POST['extrato']) > 0) $extrato = trim($_POST['extrato']);
else                               $extrato = trim($_GET['extrato']);

if (strlen($_POST['posto']) > 0)   $posto   = trim($_POST['posto']);
else                               $posto   = trim($_GET['posto']);

$gravar_conferencia   = trim($_POST['gravar_conferencia']);
$cancelar_conferencia = trim($_POST['cancelar_conferencia']);
$btn_ok_todas         = trim($_POST['btn_ok_todas']);
$atualiza_sinalizador = $_GET['atualiza_sinalizador'];
$atualiza_os = $_GET['atualiza_os'];

$caixa    = $_GET['caixa'];
$caixa_os = $_GET['caixa_os'];
$print_codigo = $_POST['print_codigo'];

if(isset($_GET['envia_nota'])) {
	$nota_avulsa = $_GET['nota_avulsa'];
	//system("/www/cgi-bin/britania/exporta-extrato-tipo-nota.pl nota_avulsa:$nota_avulsa",$ret);
    system("php exporta_extrato_tipo_nota.php nota_avulsa $nota_avulsa",$ret);

	//echo "Aguarde o envio de arquivo de nota para Britânia.";
	/*
*/

	$arquivo = "xls/integracao_ems.txt";
	if(file_exists($arquivo)){
		
		echo "<div style='margin: 20px; text-align: center;'>
			<a href='exporta_extrato_tipo_nota_download.php'>Download do Arquivo</a>
			</div>";



        echo "<script>";
            echo "window.open('exporta_extrato_tipo_nota_download.php'); ";
        echo "</script>";
		/*
		echo "<script language='javascript'>";
			echo "window.open('$arquivo'); ";
			echo "setTimeout('window.close()',1000); ";
		echo "</script>";
		*/
		exit;
	}else{
		if($ret == 0) {
			echo "<script language='javascript'>";
			echo "window.opener=null; ";
			echo "window.open(\"\",\"_self\"); ";
			echo "setTimeout('window.close()',1000); ";
			echo "</script>";
		}
	}

	exit;
}

if(!empty($print_codigo)) {
	$sql4 = "SELECT DISTINCT tbl_extrato.valor_agrupado ,  tbl_extrato.extrato 
						from tbl_extrato
						join tbl_extrato_agrupado using(extrato)
						where tbl_extrato.fabrica = $login_fabrica AND tbl_extrato_agrupado.codigo = '$print_codigo' ";
	$res4 = pg_query ($con,$sql4);
	for ($x4 = 0 ; $x4 < pg_num_rows($res4) ; $x4++){
		$total = trim(pg_fetch_result($res4,$x4,valor_agrupado));
		$extrato = trim(pg_fetch_result($res4,$x4,extrato));
		$xtotal = $xtotal + $total;

		echo "<script>window.open('extrato_posto_mao_obra_novo_britania_print.php?extrato=$extrato&codigo_agrupado=$codigo_agrupado','','height=600, width=750, top=20, left=20, scrollbars=yes')</script>";
	}
	exit;
}


if (isset($_GET['grava_nota'])) {
	$data_lancamento    = $_GET['data_lancamento'];
	$nota_fiscal        = $_GET['nota_fiscal'];
	$data_emissao       = $_GET['data_emissao'];
	$valor_original     = str_replace(",",".",$_GET['valor_original']);
	$previsao_pagamento = $_GET['previsao_pagamento'];
	$obs_fabricante     = $_GET['obs_fabricante'];
	$grava_nota         = $_GET['grava_nota'];
	$extrato_tipo_nota  = $_GET['extrato_tipo_nota'];
	$serie              = trim($_GET['serie']);
	$estabelecimento    = $_GET['estabelecimento'];

	if(strlen($msg_erro)==0){
        list($ddl, $mdl, $ydl) = explode("/", $data_lancamento);
        if(!checkdate($mdl,$ddl,$ydl)) 
            $msg_erro .= "Data de Lançamento Inválida"."\r\n";

        list($dde, $mde, $yde) = explode("/", $data_emissao);
        if(!checkdate($mde,$dde,$yde)) 
            $msg_erro .= "Data de Emissão Inválida"."\r\n";

        list($dpp, $mpp, $ypp) = explode("/", $previsao_pagamento);
        if(!checkdate($mpp,$dpp,$ypp)) 
            $msg_erro .= "Data de Previsão de Pagamento Inválida"."\r\n";
    }

    if (!$msg_erro){
    	
        $data_lancamento = "$ydl-$mdl-$ddl";
        $data_emissao = "$yde-$mde-$dde";
        $previsao_pagamento = "$ypp-$mpp-$dpp";
    }

	if(!empty($extrato_tipo_nota)) {
			$sql = "SELECT  contato_estado 
					FROM tbl_posto_fabrica
					JOIN tbl_extrato   USING(posto,fabrica)
					WHERE  tbl_extrato.fabrica = $login_fabrica AND tbl_extrato.extrato = $grava_nota";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$contato_estado = pg_fetch_result($res,0,'contato_estado');

				if(strlen(trim($contato_estado)) > 0) {
					$sql = "SELECT cfop,codigo_item
							FROM tbl_extrato_tipo_nota_excecao
							WHERE extrato_tipo_nota = $extrato_tipo_nota
							AND   estado = '$contato_estado'";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$cfop        = pg_fetch_result($res,0,'cfop');
						$codigo_item = pg_fetch_result($res,0,'codigo_item');
					}else{
						$sql = "SELECT cfop,codigo_item
							FROM tbl_extrato_tipo_nota
							WHERE extrato_tipo_nota = $extrato_tipo_nota";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$cfop        = pg_fetch_result($res,0,'cfop');
							$codigo_item = pg_fetch_result($res,0,'codigo_item');
						}
					}
				}else{
					$sql = "SELECT cfop,codigo_item
							FROM tbl_extrato_tipo_nota
							WHERE extrato_tipo_nota = $extrato_tipo_nota";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$cfop        = pg_fetch_result($res,0,'cfop');
						$codigo_item = pg_fetch_result($res,0,'codigo_item');
					}
				}
			}
		}
	
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = " INSERT INTO tbl_extrato_nota_avulsa (
				 fabrica           ,
				 extrato           ,
				 data_lancamento   ,
				 nota_fiscal       ,
				 data_emissao      ,
				 valor_original    ,
				 previsao_pagamento,
				 admin             ,
				 observacao        ,
				 cfop              ,
				 codigo_item       ,
				 serie             ,
				 estabelecimento   
				) values(
				 $login_fabrica       ,
				 $grava_nota          ,
				 '$data_lancamento'   ,
				 '$nota_fiscal'       ,
				 '$data_emissao'      ,
				 '$valor_original'    ,
				 '$previsao_pagamento',
				 $login_admin         ,
				 '$obs_fabricante'    ,
				 '$cfop'              ,
				 '$codigo_item'       ,
				 '$serie'             ,
				 '$estabelecimento'
				) RETURNING extrato_nota_avulsa";
	$res = @pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if (strlen($msg_erro) == 0 ) {
		$extrato_nota_avulsa = pg_fetch_result ($res,0,0);
	}

	if (strlen($msg_erro) == 0 ) {
		$res = @pg_query ($con,"COMMIT TRANSACTION");
		echo "ok|".$extrato_nota_avulsa."|".$grava_nota;
	}else{
		if(strpos($msg_erro, "date/time field value out of range") !== false) {
			$msg_erro = "Data inválida, verifique os dados digitados";
		}
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		echo "erro|$msg_erro| ";
	}
	exit;
}


if(!empty($atualiza_sinalizador) and !empty($atualiza_os)){
	if(strlen($atualiza_sinalizador) > 0 ) {
		$sql2 = "UPDATE tbl_os SET 
				sinalizador = $atualiza_sinalizador
				WHERE os = $atualiza_os
				AND tbl_os.fabrica = $login_fabrica
				AND (sinalizador IS NULL or sinalizador not in (3,9));";
		$res2 = pg_query ($con,$sql2);
		$msg_erro = pg_last_error($con);
	}

	if(strlen($msg_erro) > 0) {
		echo "Erro inesperado, tente novamente";
	}else{
		echo "OK";
	}
	exit;
}


if(!empty($caixa) and !empty($caixa_os)){

	$sql1 = " SELECT os
				FROM tbl_os
				WHERE os = $caixa_os
				AND   tbl_os.fabrica = $login_fabrica
				AND   (sinalizador <> 9 or sinalizador IS NULL)";
	$res = pg_query($con,$sql1);
	
	if(pg_num_rows($res) > 0){
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql2 = "UPDATE tbl_os SET 
						sinalizador = 9
					WHERE os = $caixa_os
					AND   fabrica = $login_fabrica ;";
		$res2 = pg_query ($con,$sql2);
		$msg_erro = pg_last_error($con);

		
		$sql2 = "UPDATE tbl_extrato_conferencia SET 
					data_conferencia= current_timestamp
					FROM tbl_os_extra
					JOIN tbl_os USING(os)
					WHERE tbl_os_extra.extrato = tbl_extrato_conferencia.extrato
					AND   tbl_os.fabrica = tbl_os_extra.i_fabrica
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_os_extra.os = $caixa_os
					AND   cancelada IS NOT TRUE ";
		#$res2 = pg_query ($con,$sql2);
		#$msg_erro = pg_last_error($con);

		$sql = "SELECT  tbl_os.os           ,
					tbl_os.sua_os           ,
					tbl_os.posto            ,
					tbl_os_extra.mao_de_obra,
					tbl_os_extra.extrato    ,
					to_char(data_geracao,'MM/YYYY') as data_geracao
			FROM    tbl_os 
			JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os and tbl_os.fabrica = tbl_os_extra.i_fabrica
			JOIN    tbl_extrato USING(extrato)
			WHERE   tbl_os.fabrica = $login_fabrica 
				AND tbl_os.os=$caixa_os  ";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_last_error($con);

		$os           = pg_fetch_result($res,0,os);
		$sua_os       = pg_fetch_result($res,0,sua_os);
		$posto        = pg_fetch_result($res,0,posto);
		$mao_de_obra  = pg_fetch_result($res,0,mao_de_obra);
		$xextrato      = pg_fetch_result($res,0,extrato);
		$data_geracao = pg_fetch_result($res,0,data_geracao);

		$xhistorico =  "Regularização de OS nº $sua_os, pertinente ao extrato $data_geracao, caixa arquivo $caixa.";

		$sql = "INSERT INTO tbl_extrato_lancamento (
								posto                ,
								fabrica              ,
								lancamento           ,
								historico            ,
								valor                ,
								admin                
								) VALUES (
								$posto               ,
								$login_fabrica       ,
								153                  ,
								'$xhistorico'        ,
								'$mao_de_obra'       ,
								$login_admin         
								);";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_last_error($con);
		
		if(strlen($msg_erro) > 0) {
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			echo $msg_erro;
		}else{
			$res = pg_query ($con,"COMMIT TRANSACTION");
			echo "ok";
		}
	}
	exit;
}

if(!empty($btn_ok_todas)){
	$res = pg_query ($con,"BEGIN TRANSACTION");
	if (strlen ($msg_erro) == 0){
		$sql = " UPDATE tbl_os set sinalizador = 1
				FROM tbl_os_extra
				WHERE tbl_os_extra.os = tbl_os.os
				AND   tbl_os.fabrica = tbl_os_extra.i_fabrica
				AND   tbl_os_extra.extrato = $extrato
				AND   tbl_os.sinalizador IS NULL
				AND   fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$extrato = str_replace("'","", $extrato);
		header("location: $PHP_SELF?extrato=$extrato&posto=$posto&msg=ok");
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if(isset($_POST['btn_atualiza']) and !empty($_POST['btn_atualiza'])){
	$extrato             = trim($_POST['extrato']);
	$extrato_conferencia = trim($_POST['extrato_conferencia']);
	
	if(!empty($extrato_conferencia) AND !empty($extrato)) {
		$res = pg_query ($con,"BEGIN TRANSACTION");
		$sql = " DELETE FROM tbl_extrato_conferencia_item
				WHERE extrato_conferencia = $extrato_conferencia";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		$sql = "SELECT extrato FROM tbl_extrato_agrupado WHERE extrato=$extrato";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) == 0){
			if(empty($msg_erro)) {
				$sqlx = "INSERT INTO tbl_extrato_conferencia_item(
								extrato_conferencia  ,
								linha                ,
								mao_de_obra          ,
								mao_de_obra_unitario ,
								qtde_conferida       
							)
							SELECT
							$extrato_conferencia,
							tbl_linha.linha                      ,
							SUM  (case when os.sinalizador =1 then tbl_os_extra.mao_de_obra else 0 end) AS mao_de_obra_posto          ,
							tbl_os_extra.mao_de_obra AS unitario ,
							COUNT(tbl_os_extra.os) AS qtde       
							FROM
							(SELECT tbl_os_extra.os ,tbl_os.sinalizador
							FROM tbl_os_extra 
							JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
							WHERE tbl_os_extra.extrato = $extrato
							AND   sinalizador in (1,9) 
							AND   tbl_os.fabrica = $login_fabrica
							and not (sinalizador is  null)
							) os 
							JOIN tbl_os_extra ON os.os = tbl_os_extra.os
							JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
							JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
							GROUP BY tbl_linha.linha, tbl_os_extra.mao_de_obra
							ORDER BY tbl_linha.linha;";
				$resd = pg_query($con,$sqlx);
				$msg_erro = pg_last_error($con);

				if(empty($msg_erro)) {
					$res = pg_query ($con,"COMMIT TRANSACTION");
				}else{
					$res = pg_query ($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}

if(isset($_POST['cancelar_conferencia']) and !empty($_POST['cancelar_conferencia'])){
	$extrato                   = trim($_POST['extrato']);
	$extrato_conferencia = trim($_POST['extrato_conferencia']);

	if(strlen($extrato_conferencia) > 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql = " DELETE FROM tbl_extrato_agrupado
				WHERE extrato = $extrato ";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		
		//HD 204146: Fechamento automático de OS: se o sinalizador já estiver como 19 quer dizer que já foi lançado extrato avulso para a OS para debitar peças que foram enviadas em garantia, portanto não deve zerar o sinalizador
		$sql = " UPDATE tbl_os SET sinalizador = null
				FROM tbl_os_extra
				JOIN tbl_extrato_conferencia USING(extrato)
				WHERE tbl_os_extra.os = tbl_os.os and tbl_os.fabrica = tbl_os_extra.i_fabrica
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os_extra.extrato = $extrato
				AND    extrato_conferencia = $extrato_conferencia
				AND tbl_os.sinalizador <> 19";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		$sql = " UPDATE tbl_extrato_conferencia
					SET cancelada = 't',
						admin_cancelou = $login_admin,
						data_cancelada = current_timestamp
				WHERE extrato_conferencia = $extrato_conferencia ";
		$res = @pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);

		$sql = " UPDATE tbl_extrato
					SET valor_agrupado = null
				WHERE extrato = $extrato";
		$res = @pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);

		if(strlen($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			header("location: $PHP_SELF?extrato=$extrato&posto=$posto&msg=ok");
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	
	}
}

if(!empty($gravar_conferencia)){
	
	$extrato                   = trim($_POST['extrato']);
	$caixa_conferencia         = trim($_POST['caixa_conferencia']);
	$obs_fabricante            = trim($_POST['obs_fabricante']);
	$extrato_conferencia       = trim($_POST['extrato_conferencia']);
	$qtde_item_enviada	       = trim($_POST['qtde_linha']);	

	$sql = "
	SELECT
	posto

	FROM
	tbl_extrato

	WHERE
	extrato=$extrato
	";
	$res_dados_extrato = pg_query($con, $sql);
	$posto = pg_fetch_result($res_dados_extrato, 0, posto);

	$res = pg_query ($con,"BEGIN TRANSACTION");

	
		if(strlen($extrato_conferencia)==0){
			if(strlen($caixa_conferencia)>0)         $xcaixa_conferencia = "'".$caixa_conferencia."'";
			else                                     $msg_erro = "Por favor, informe a caixa";

			if(strlen($msg_erro)==0){
				$sql = " UPDATE tbl_extrato_conferencia set cancelada ='t' where extrato = $extrato";
				$res = pg_query($con,$sql);
				
				$sqls = " SELECT extrato 
						FROM tbl_extrato_agrupado
						WHERE extrato = $extrato";
				$ress = pg_query($con,$sqls);
				
				if(pg_num_rows($ress) == 0){
					$sql = "INSERT INTO tbl_extrato_conferencia(
									extrato_conferencia ,
									extrato             ,
									data_conferencia    ,
									caixa               ,
									admin               ,
									obs_fabricante      
									)VALUES(
									DEFAULT                    ,
									$extrato                   ,
									current_timestamp          ,
									$xcaixa_conferencia        ,
									$login_admin               ,
									'$obs_fabricante'
									) RETURNING extrato_conferencia;";
					$res = @pg_query ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen ($msg_erro) == 0 ){
						$extrato_conferencia = trim(pg_fetch_result($res,0,extrato_conferencia));

						$sqld="SELECT	
							tbl_linha.linha                      ,
							tbl_os_extra.mao_de_obra AS unitario ,
							COUNT(tbl_os_extra.os) AS qtde                      ,
							SUM  (case when os.sinalizador =1 then tbl_os_extra.mao_de_obra else 0 end) AS mao_de_obra_posto     
							FROM
							(SELECT tbl_os_extra.os ,tbl_os.sinalizador
							FROM tbl_os_extra 
							JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os and tbl_os.fabrica = tbl_os_extra.i_fabrica
							WHERE tbl_os_extra.extrato = $extrato
							AND   tbl_os.fabrica = $login_fabrica
							AND   sinalizador in (1,9) 
							and not (sinalizador is  null)
							) os 
							JOIN tbl_os_extra ON os.os = tbl_os_extra.os
							JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
							JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
							GROUP BY tbl_linha.linha, tbl_os_extra.mao_de_obra
							ORDER BY tbl_linha.linha;";
						$resd = pg_query($con,$sqld);
						if(pg_num_rows($resd) > 0){
							for($d =0;$d<pg_num_rows($resd);$d++) {
								$xlinha = pg_fetch_result($resd,$d,linha);
								$xmao_de_obra_unitario = pg_fetch_result($resd,$d,unitario);
								$xmao_de_obra_posto = pg_fetch_result($resd,$d,mao_de_obra_posto);
								$qtde_conferir_os = pg_fetch_result($resd,$d,qtde);

								if(strlen($msg_erro)==0 ){
									$sqlx = "INSERT INTO tbl_extrato_conferencia_item(
													extrato_conferencia  ,
													linha                ,
													mao_de_obra          ,
													mao_de_obra_unitario ,
													qtde_conferida       
												)VALUES(
													$extrato_conferencia ,
													$xlinha               ,
													$xmao_de_obra_posto   ,
													$xmao_de_obra_unitario,
													$qtde_conferir_os
												);";
									$resx = @pg_exec ($con,$sqlx);
									$msg_erro = pg_errormessage($con);
								}
							}
						}
				}

				

					//HD 204146: Fechamento automático de OS. Sempre que finalizar o processo de conferência e tiver OS com sinalizador = 18 (FECHAMENTO AUTOMÁTICO) o sistema irá verificar se foram enviadas peças para o posto. Se sim, será gerado um lançamento avulso para debitar as peças do próximo extrato.

					$sql = "
					SELECT
					os,
					sua_os

					FROM
					tbl_os

					WHERE
					tbl_os.fabrica = $login_fabrica
					AND sinalizador=18
					AND os IN (SELECT os FROM tbl_os_extra WHERE extrato=$extrato and tbl_os_extra.i_fabrica = $login_fabrica)
					";
					@$res = pg_query($con, $sql);
					if (pg_errormessage($con)) {
						$msg_erro = "Falha no sistema. Contate o HelpDesk. Mensagem: $sql";
					}
					
					//O UPDATE tem que ficar abaixo da seleção das OS, senão vai debitar do próximo extrato 2 vezes as peças se cancelar e finalizar o processo novamente
					$sql = "
					UPDATE tbl_os
					
					SET
					sinalizador=19

					WHERE
					tbl_os.fabrica = $login_fabrica
					AND sinalizador=18
					AND os IN (SELECT os FROM tbl_os_extra WHERE extrato=$extrato and tbl_os_extra.i_fabrica = $login_fabrica)
					";
					@$res_update = pg_query($con, $sql);
					if (pg_errormessage($con)) {
						$msg_erro = "Falha no sistema. Contate o HelpDesk. Mensagem: $sql";
					}

					for ($i = 0; $i < pg_num_rows($res); $i++) {
						$os_conferir = pg_fetch_result($res, $i, os);
						$sua_os_conferir = pg_fetch_result($res, $i, sua_os);

						$sql = "
						SELECT
						tbl_os_item.os_item,
						tbl_os_item.qtde,
						tbl_os_item.pedido,
						tbl_os_item.pedido_item,
						tbl_pedido_item.qtde_faturada,
						tbl_pedido_item.preco,
						tbl_peca.peca,
						tbl_peca.referencia

						FROM
						tbl_os_produto
						JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
						JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
						LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item

						WHERE
						tbl_os_produto.os=$os_conferir
						AND tbl_os_item.pedido IS NOT NULL
						";
						@$res_itens = pg_query($con, $sql);
						if (pg_errormessage($con)) {
							$msg_erro = "Falha no sistema. Contate o HelpDesk. Mensagem: $sql";
						}

						for ($j = 0; $j < pg_num_rows($res_itens); $j++) {
							$os_item = pg_fetch_result($res_itens, $j, os_item);
							$qtde = pg_fetch_result($res_itens, $j, qtde);
							$pedido = pg_fetch_result($res_itens, $j, pedido);
							$pedido_item = pg_fetch_result($res_itens, $j, pedido_item);
							$qtde_faturada = pg_fetch_result($res_itens, $j, qtde_faturada);
							$preco = pg_fetch_result($res_itens, $j, preco);
							$peca = pg_fetch_result($res_itens, $j, peca);
							$referencia = pg_fetch_result($res_itens, $j, referencia);
							
							//Se o sistema amarrou o os_item com pedido_item: ainda temos uma incerteza, pois um pedido_item pode estar atendendo a várias OS, então se a quantidade que foi faturada foi maior ou igual à quantidade solicitada na OS, provavelmente mandou as peças
							if (strlen($pedido_item) > 0) {
							}
							//Se o sistema não amarrou o pedido item: verificamos se o pedido atendeu uma quantidade de pecas maior que a solicitada dentro daquele pedido, somando todos os pedido_item da pe;ca em questão
							else {
								$sql = "
								SELECT
								SUM(qtde_faturada) AS qtde_faturada,
								AVG(preco) AS preco

								FROM
								tbl_pedido_item

								WHERE
								pedido=$pedido
								AND peca=$peca
								";
								@$res_faturada = pg_query($con, $sql);
								if (pg_errormessage($con)) {
									$msg_erro = "Falha no sistema. Contate o HelpDesk. Mensagem: $sql";
								}

								$qtde_faturada = pg_fetch_result($res_faturada, 0, qtde_faturada);
								$preco = pg_fetch_result($res_faturada, 0, preco);
							}

							if ($qtde_faturada >= $qtde) {
								$historico = "Peça $referencia enviada em garantia pedido $pedido referente à OS $sua_os_conferir, que não foi fechada automaticamente pelo sistema e o posto não enviou nota fiscal";

								$sql = "
								INSERT INTO tbl_extrato_lancamento (
								posto,
								fabrica,
								lancamento,
								historico,
								valor,
								admin,
								campos_adicionais
								)
								
								VALUES (
								$posto,
								$login_fabrica,
								190,
								'$historico',
								$qtde*$preco,
								$login_admin,
								'$campos_adicionais'
								)
								";
								@$res_avulso = pg_query($con, $sql);
								if (pg_errormessage($con)) {
									$msg_erro = "Falha no sistema. Contate o HelpDesk. Mensagem: $sql";
								}

								$sql = "SELECT CURRVAL('seq_extrato_lancamento')";
								@$res_avulso = pg_query($con, $sql);
								$extrato_lancamento = pg_fetch_result($res_avulso, 0, 0);

								$sql = "
								INSERT INTO tbl_extrato_lancamento_os (
								extrato_lancamento,
								os
								)

								VALUES (
								$extrato_lancamento,
								$os_conferir
								)
								";
								@$res_avulso = pg_query($con, $sql);
								if (pg_errormessage($con)) {
									$msg_erro = "Falha no sistema. Contate o HelpDesk. Mensagem: $sql";
								}

							}
						}
					}
				}
			}
		}
	
	if (strlen ($msg_erro) == 0){
		$sql = " UPDATE tbl_os set sinalizador = 7
				FROM tbl_os_extra
				WHERE tbl_os_extra.os = tbl_os.os
				AND   tbl_os_extra.i_fabrica = tbl_os.fabrica
				AND   tbl_os_extra.extrato = $extrato
				AND   tbl_os.sinalizador IS NULL
				AND   fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
	}

	/*if (strlen ($msg_erro) == 0){
		$sql = " SELECT os FROM tbl_os_extra
				WHERE extrato = $extrato";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			for($i =0;$i<pg_num_rows($res);$i++) {
				$os = pg_fetch_result($res,$i,os);
				$sinalizador_os = $_POST['sinalizador_'.$os];
				if($sinalizador_os =='null') {
					$sinalizador_os = 7;
				}

				if(strlen($sinalizador_os) > 0 and $sinalizador_os <>'null') {
					$sqls = " UPDATE tbl_os  set sinalizador = $sinalizador_os
							FROM tbl_os_extra
							WHERE tbl_os_extra.os = tbl_os.os
							AND   tbl_os_extra.extrato = $extrato
							AND   (sinalizador IS NULL or sinalizador not in (3,9))
							AND   tbl_os.os = $os
							AND   fabrica = $login_fabrica";
					$ress = pg_query($con,$sqls);
					$msg_erro .= pg_last_error($con);
				}
			}
		}
	}*/


	if (strlen ($msg_erro) == 0) {
		$sql_email = "SELECT extrato,
							 to_char(data_geracao,'DD/MM/YYYY') AS data_geracao,
							 codigo_posto,
							 nome,
							 caixa
						FROM tbl_extrato
						JOIN tbl_extrato_conferencia USING(extrato)
						JOIN tbl_posto USING(posto)
						JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE extrato = $extrato AND   tbl_extrato.fabrica = $login_fabrica";
		$res_email = pg_query($con,$sql_email);
		if(pg_num_rows($res_email) > 0){
			$extrato     = trim(pg_fetch_result($res_email,0,extrato));
			$data_geracao= trim(pg_fetch_result($res_email,0,data_geracao));
			$codigo_posto= trim(pg_fetch_result($res_email,0,codigo_posto));
			$nome        = trim(pg_fetch_result($res_email,0,nome));
			$caixa       = trim(pg_fetch_result($res_email,0,caixa));

			$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
							tbl_linha.linha                      ,
							tbl_os_extra.mao_de_obra AS unitario ,
							tbl_sinalizador_os.acao              ,
							SUM  ( tbl_os_extra.mao_de_obra ) AS mao_de_obra_posto     ,
							tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
							SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
							SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
							tbl_os.sua_os,
							tbl_os.nota_fiscal
					FROM
						(SELECT tbl_os_extra.os 
						FROM tbl_os_extra 
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os  AND tbl_os_extra.i_fabrica = tbl_os.fabrica
						WHERE tbl_os.fabrica = $login_fabrica AND tbl_os_extra.extrato = $extrato
						) os 
					JOIN tbl_os_extra ON os.os = tbl_os_extra.os
					JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
					JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
					JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
					LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
					WHERE tbl_os.fabrica = $login_fabrica AND tbl_sinalizador_os.debito='S' AND tbl_os.sinalizador <> 3
					GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao,tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao ,tbl_os.sua_os,tbl_os.nota_fiscal
					ORDER BY tbl_linha.nome";
			$res = pg_query ($con,$sql);

			$total_qtde            = 0 ;
			if(pg_num_rows($res) > 0){
				$remetente    = "Telecontrol <helpdesk@telecontrol.com.br>";
				$destinatario = "antonio.azevedo@britania.com.br ";
				$assunto      = "O Resumo de OS irregular do extrato $extrato - $data_geracao do posto $codigo_posto - Caixa: $caixa";
				$mensagem     = "";

				for($i=0; $i<pg_num_rows($res); $i++){
					$mensagem .= "\n\n<br><br>";
					$acao_sinalizador             = pg_fetch_result ($res,$i,acao);
					$linha             = pg_fetch_result ($res,$i,linha);
					$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
					$nota_fiscal        = pg_fetch_result ($res,$i,nota_fiscal);
					$sua_os        = pg_fetch_result ($res,$i,sua_os);
					$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
					$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
					$mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
					$total_os_i += pg_fetch_result ($res,$i,mao_de_obra_posto);

					$cor = "#FFFFFF";
					if ($i % 2 == 0) $cor = "#FEF2C2";

					$mensagem .= "OS: ".$sua_os."\n<br>";
					$mensagem .= "Nota Fiscal: ".$nota_fiscal."\n<br>";
					$mensagem .= "Sinalizador: ".$acao_sinalizador."\n<br>";
					$mensagem .= "Linha: ".$linha_nome."\n<br>";
					$mensagem .= "M.O.: ".$mao_de_obra_posto."\n<br>";

				}
				$mensagem .= "\n<br> Total: ".number_format ($total_os_i,2,",",".")."\n<br>";

				$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
				$headers .= "Bcc: paulo@telecontrol.com.br" . "\r\n";

				#mail($destinatario,$assunto,$mensagem,$headers);
			}else{
				$remetente    = "Telecontrol <helpdesk@telecontrol.com.br>";
				$destinatario = "antonio.azevedo@britania.com.br ";
				$assunto      = "Extrato $extrato - $data_geracao do posto $codigo_posto - Caixa: $caixa";
				$mensagem     = "Extrato 100%";

				$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
				$headers .= "Bcc: paulo@telecontrol.com.br" . "\r\n";

				#mail($destinatario,$assunto,$mensagem,$headers);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$extrato = str_replace("'","", $extrato);
		system("/www/cgi-bin/relatorio-extrato-backup.pl $extrato",$ret);
		header("location: $PHP_SELF?extrato=$extrato&posto=$posto&msg=ok");
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if(isset($_GET['excluir_nota'])) {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = " DELETE FROM tbl_extrato_nota_avulsa
			WHERE extrato = $extrato 
			AND   fabrica = $login_fabrica
			AND   extrato_nota_avulsa=".$_GET['excluir_nota'];
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if(strlen($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		echo "ok";
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
	exit;
}


$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Posto";

if(!isset($_GET['backup'])) {
	include "cabecalho.php";
}
echo "<span id='msg_carregando'>Carregando<br><img src='imagens/ajax-carregando.gif'></span>";
flush();
?>
<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src='js/jquery.quicksearch.js'></script>
<script type='text/javascript' src='js/jquery.tablesorter.js'></script>
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
<script type="text/javascript" src="js/jquery.editable-1.3.3.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">



<style type="text/css">
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_obs2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}


.error{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #FF0000;
}

.sucesso{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #9900FF;
}

.mensagem_os{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: normal;
	background-color: #9900FF;
}



.mostra{
	display:inline;
}

.esconde{
	display:none;
}
#sb-container {
	z-index: 999999 !important;
}
</style>

<script language="JavaScript">

var os_change = "";

$(document).ready(function () {				
	$("table#search tbody tr").hide();
	$('table#search tbody tr th[rel=os]').quicksearch({
		position: 'before',
		attached: 'table#search',
		hideElement: 'parent',
		labelText: 'Consultar OS',
		loaderText: 'Aguarde<img src="imagens/ajax-loader.gif">'
	})

	$('table#search tbody tr th[rel=nota_fiscal]').quicksearch({
		position: 'before',
		attached: 'table#search',
		hideElement: 'parent',
		labelText: 'Consultar NF',
		loaderText: 'Aguarde<img src="imagens/ajax-loader.gif">'
	})
	
	$("table#search").tablesorter(); 

	$('#msg_carregando').hide();

	$('div[rel=sinalizador]').editable({
		type:'select',
		<?php

			$sql_extrato_agrupado = "SELECT agrupado FROM tbl_extrato_agrupado WHERE extrato = {$extrato}";
			$res_extrato_agrupado = pg_query($con, $sql_extrato_agrupado);

			$extrato_agrupado = (pg_num_rows($res_extrato_agrupado) > 0) ? true : false;

			if($extrato_agrupado === true){
				$cond_sinalizador = " AND sinalizador = 9 ";
			}

			$sql = "SELECT sinalizador,acao,debito,solucao,disponivel FROM tbl_sinalizador_os where disponivel='t' {$cond_sinalizador} order by acao";
			$res = pg_query ($con,$sql) ;
			if (@pg_num_rows($res) > 0) {
				echo "options:{";

				for ($ix=0; $ix<pg_num_rows ($res); $ix++ ){
					echo "'".pg_fetch_result($res,$ix,sinalizador)."':'".pg_fetch_result($res,$ix,acao)."'";
					echo ($ix <> pg_num_rows ($res) - 1) ?",":"";
				}

				if($extrato_agrupado == true){
					echo ",'0':'Selecione'";
				}

				echo "},";
			}
		?>
		submitBy: 'blur',
		onEdit: function(valor){

			sinalizador =  valor.current;
			os = this.attr("alt");

			$('select[rel=sinalizadores]').change(function(){

				os_change = os;

				if ($(this).val() == '9') {

					$("#tr_caixa_"+os).removeClass('esconde').show();

					$("#td_caixa_"+os).html("");
					$("#td_caixa_"+os).append("Caixa <input type='text' id='caixa_"+os+"' name='caixa_"+os+"' size='10' maxlength='20' /><input type='button'  value='Gravar' onClick='gravarCaixa("+os+",document.getElementById(\"caixa_"+os+"\").value);'>");
					
					os_change = os;

				}else{

					if(os_change != ""){
						$("#td_caixa_"+os_change).html("");
					}

				}
			});

			if(sinalizador == "0"){
				$("#msg_"+os+" > div").text("Alterar");
			}

			if(sinalizador == "9"){
				$("#msg_"+os+" > div").text("RESSARCIMENTO DE OS");
			}
			
		},
		onSubmit:function(valor){

			sinalizador = valor.current;

			os = this.attr("alt");

			if(sinalizador == "0"){
				if(os_change != ""){
					$("#msg_"+os+" > div").text("Alterar");
				}
				return;
			}

			if(sinalizador != "9"){
				$("#tr_caixa_"+os).addClass('esconde').hide();
			}else{
				if(os_change != ""){
					$("#msg_"+os+" > div").text("RESSARCIMENTO DE OS");
				}
				return;
			}

			if(sinalizador.length > 0 && os.length >0) {
				$.ajax({
					type: "GET",
					url: "<?=$PHP_SELF?>",
					async:false,
					data:"atualiza_sinalizador="+sinalizador+"&atualiza_os="+os,
					complete: function (resultado){
						$('#msg_'+os).html(resultado.responseText);
					}
				})
			}
		}
	});
})


function abrejanela(endereco,nome){
	window.open(endereco, nome, 'height=600, width=750, top=20, left=20, scrollbars=yes');
}

function gravaSinalizador(sinalizador,os){
	if (sinalizador=='9') {
		$("#tr_caixa_"+os).removeClass('esconde').show();
		if ('#caixa_'+os.length > 0){
		}else{
			$("#td_caixa_"+os).append("Caixa<input type='text' id='caixa_"+os+"' name='caixa_"+os+"' size='10' maxlength='20' /><input type='button'  value='Gravar' onClick='gravarCaixa("+os+",document.getElementById(\"caixa_"+os+"\").value);'>");
		}
	}else{
		$("#tr_caixa_"+os).addClass('esconde').hide();
	}

	if(sinalizador.length > 0 && os.length >0) {
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			data:"atualiza_sinalizador="+sinalizador+"&atualiza_os="+os
		})
	}
}

function gravarCaixa(os,caixa) {
	if(caixa.length == 0) {
		alert("Informe o número da caixa");
	}else{
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			data:"caixa="+caixa+"&caixa_os="+os,
			complete: function(resposta){
				if(resposta.responseText=='ok') {
					$("#td_caixa_"+os).html("Lançamento Avulso incluído");
				}else{
					alert(resposta.responseText);
				}
			}
		})
	}
}

function exibe(id) {
	$('tr[rel=detalhar]').toggle();
	$('tr[rel=gravar]').hide();
}



function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='0';
	}
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO
function calcula_total(){
	var x = parseInt(document.getElementById('qtde_linha').value);
	var y = parseInt(document.getElementById('qtde_avulso').value);

	var somav = 0;
	var somat = 0;
	var mao_de_obra  = 0;
	var qtde_conferir_os = 0;
	var valor_avulso = 0;

	for (f=0; f<x;f++){
		mao_de_obra  = document.getElementById('unitario_'+f).value.replace(',','.');
		qtde_conferir_os = document.getElementById('qtde_conferir_os_'+f).value.replace(',','.');
		somav = parseInt(qtde_conferir_os) * parseFloat(mao_de_obra);
		somat = somat + parseFloat(somav); 
	}

	for (a=0; a<y; a++){
		valor_avulso = document.getElementById('valor_avulso_'+a).value;
		somat += parseFloat(valor_avulso);
	}

	document.getElementById('valor_conferencia_a_pagar').value= somat;
}

function excluirNota(nota,extrato,numero) {
	if(confirm("Deseja realmente excluir essa nota avulsa?") == true){
		$.get(
			'<?=$PHP_SELF?>',
			{
				excluir_nota: nota,
				extrato:      extrato
			},
			function(resposta) {
				if(resposta == 'ok') {
					var td = $('#excluir_'+numero).parent().get(0);
					var tr = $(td).parent().get(0);
					$(tr).html('<font size=2 color="red">Excluido com sucesso</font>');
					$(".excluir_obs_"+numero).hide();
				}
			}
		)
	}
}

function gravaNota(numero2){
	var data_lancamento   = $('#data_lancamento_'+numero2).val();
	var nota_fiscal       = $('#nota_fiscal_'+numero2).val();
	var data_emissao      = $('#data_emissao_'+numero2).val();
	var valor_original    = $('#valor_original_'+numero2).val();
	var previsao_pagamento= $('#previsao_pagamento_'+numero2).val();
	var obs_fabricante    = $('#obs_fabricante_'+numero2).val();
	var extrato_tipo_nota = $('#extrato_tipo_nota_'+numero2).val();
	var serie             = $('#serie_'+numero2).val();
	//var estabelecimento   = $('#estabelecimento_'+numero2+':checked').val();
	//Thiago Contardi (Modifiquei só aqui pra não alterar outras partes do programa)
	//Ele percorre os campos com essa classe e pega o valor apenas da selecionada, não dando conflitos de ID
	$('.estabelecimento_'+numero2+'').each(function(){
		if($(this).is(':checked')){
			estabelecimento = $(this).val();
		}
	});
	
	if (previsao_pagamento.length ==0 || valor_original.length ==0 || data_emissao.length ==0 || nota_fiscal.length==0 || data_lancamento.length ==0 || extrato_tipo_nota.length == 0 || serie.length == 0 || estabelecimento.length == 0) {
		alert('Informe todos os campos antes de clicar em gravar');
	}else{
		$.get(
			'<?=$PHP_SELF?>',
			{
				data_lancamento:    data_lancamento,
				nota_fiscal:        nota_fiscal,
				data_emissao:       data_emissao,
				valor_original:     valor_original,
				previsao_pagamento: previsao_pagamento,
				obs_fabricante:     obs_fabricante,
				extrato_tipo_nota:     extrato_tipo_nota,
				serie:              serie,
				estabelecimento:     estabelecimento,
				grava_nota: <?=$extrato?>
			},
			function(respostas) {
				var resposta = respostas.split('|');
				if (resposta[0] == "ok") {
					$('.'+numero2).hide();
					$('.'+numero2).after('<a href="javascript:excluirNota('+resposta[1]+','+resposta[2]+','+numero2+')" id="excluir_'+numero2+'"><img src="imagens/btn_x.gif">Excluir</a>');
					window.open('<?=$PHP_SELF?>?envia_nota=sim&nota_avulsa='+resposta[1]+'','','height=600, width=750, top=20, left=20, scrollbars=yes');
				}else{
					alert(resposta[1]);
				}
			}
		)
	}
}


function addNota(){
	<? $data = date('d/m/Y');
		$sql = "SELECT admin,login FROM tbl_admin WHERE admin = $login_admin";
		$res = pg_query($con,$sql);
		$login = pg_fetch_result($res,0,login) ;

		$sqle = "SELECT extrato_tipo_nota, descricao
				FROM tbl_extrato_tipo_nota
				WHERE fabrica = $login_fabrica ";
		$rese = pg_query($con,$sqle);
		if(pg_num_rows($rese) > 0){
			for($j =0;$j<pg_num_rows($rese);$j++) {
				$option.="<option value=".pg_fetch_result($rese,$j,'extrato_tipo_nota').">".pg_fetch_result($rese,$j,'descricao')."</option>";
			}
		}
	?>
	var numero = ($('#qtde_nota_avulsa').val() == 0) ? 0 : $('#qtde_nota_avulsa').val();
	var numero2 = numero + 1;
	if($("#"+numero2).length == 0) {
		$('#'+numero).after("<tr style='font-size: 10px;text-align:center'  ><td><input type='text' size='12' rel='data' name='data_lancamento_"+numero2+"' readonly='readonly' id='data_lancamento_"+numero2+"' value=<?=$data?>></td><td><?=$login?></td><td><input type='text' size='12' rel='numero' name='nota_fiscal_"+numero2+"' id='nota_fiscal_"+numero2+"'maxlength='20'></td><td><input type='text' name='data_emissao_"+numero2+"' id='data_emissao_"+numero2+"' size='12' rel='data'></td><td><input type='text' name='valor_original_"+numero2+"' id='valor_original_"+numero2+"' rel='numero'></td><td nowrap><input type='text' name='previsao_pagamento_"+numero2+"' id='previsao_pagamento_"+numero2+"' rel='data' size='12'></td><td nowrap><input type='text' name='serie_"+numero2+"' id='serie_"+numero2+"'  size='12' maxlength='10' value='F'></td><td nowrap>1<input type='radio' name='estabelecimento_"+numero2+"' id='estabelecimento_"+numero2+"'  class='estabelecimento_"+numero2+"' value='1' checked> 22<input type='radio' name='estabelecimento_"+numero2+"' id='estabelecimento_"+numero2+"' class='estabelecimento_"+numero2+"' value='22'><input type='button' name='btn_gravar' value='Gravar' onclick='gravaNota("+numero2+")' class='"+numero2+"'></td></tr><tr class='excluir_obs_"+numero2+"' id='"+numero2+"'><td  style='text-align:center; font-size: x-small;font-weight: bold;color:#ffffff; background-color: #596D9B'>Observacao</td><td colspan='3'><input type='text' name='obs_fabricante_"+numero2+"' id='obs_fabricante_"+numero2+"' size='60'</td><td style='text-align:center; font-size: x-small;font-weight: bold;color:#ffffff; background-color: #596D9B'>Tipo Nota</td><td nowrap><select name='extrato_tipo_nota_"+numero2+"' id='extrato_tipo_nota_"+numero2+"'><?=$option?></select></td></tr>");
		$("input[rel='data']").maskedinput('99/99/9999');
		$("input[rel='numero']").keypress(function(e) {   
			var keycode = e.keyCode;
			var c = String.fromCharCode(e.which);   
			var allowed = '1234567890,.';
			if ((keycode !== 8 && keycode !== 9) && allowed.indexOf(c) < 0) return false;
		});
		$("#qtde_nota_avulsa").val(numero2);
	}
}

function outroExtrato(){
	if (confirm("Você deseja sair sem gravar caixa") == true) 
	{
		window.location = "extrato_posto_britania_novo_processo.php";
	}
	
}

function abrePrint(codigo){
	$.post('<? echo $PHP_SELF; ?>',
		{
			print_codigo: codigo
		},
		function(resposta){
			$('#print').html(resposta);
		}
	)
}

</script>
<p>

<div id='print'></div>
<center>
<?
if(strlen($msg_erro)>0){
	echo "<DIV class='error'>".$msg_erro."</DIV>";
}

if(strlen($msg)>0){
	echo "<DIV class='sucesso'>Gravado com sucesso</DIV>";
}

?>
<font size='+1' face='arial'>Data do Extrato</font>
<?


$sqlu = " 
	SELECT a.os
	into TEMP tmp_reinc_90_$login_admin
	from
	(SELECT distinct tbl_os_auditar.os
	FROM tbl_os_auditar
	JOIN tbl_os_extra USING(os)
	WHERE  (tbl_os_auditar.descricao !~* '.*Reincidente.*' AND tbl_os_extra.mao_de_obra_desconto NOTNULL)
	AND tbl_os_extra.extrato = $extrato
	UNION
	select tbl_os_status.os
	FROM tbl_os_status
	JOIN tbl_os_extra USING(os)
	WHERE tbl_os_extra.extrato = $extrato
	AND tbl_os_status.status_os in (67,70) and tbl_os_status.observacao like '% MAIS 90 DIAS)' ) a	;

	UPDATE tbl_os set sinalizador = 3
	FROM tbl_os_extra
	WHERE tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica = tbl_os.fabrica
		AND tbl_os.fabrica = $login_fabrica
		AND tbl_os_extra.extrato = $extrato
		AND (tbl_os.os_reincidente IS TRUE
		AND tbl_os_extra.os_reincidente IS NOT NULL
		AND tbl_os_extra.mao_de_obra_desconto IS NOT NULL)
		AND sinalizador IS NULL
		AND tbl_os.os not in (select os from tmp_reinc_90_$login_admin);
	SELECT os FROM tmp_reinc_90_$login_admin;
";

$resu = pg_query($con,$sqlu);
$reinc_oss = pg_fetch_all($resu);
$os_reinc = array();
foreach($reinc_oss as $reinc_os){
	foreach($reinc_os as $valor){
		$os_reinc[] = $valor;
	}
}
/*
$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_admin where 1=1) )) THEN 1 else null END ) AS qtde                      ,  
				COUNT(CASE WHEN tbl_os_extra.os_reincidente IS NOT NULL AND tbl_os_extra.os NOT IN (select os from tmp_reinc_90_$login_admin where 1=1) THEN 1 ELSE NULL END) AS qtde_recusada             ,
				SUM  (CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null ) THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null) THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null) THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
		FROM
			(SELECT tbl_os_extra.os,tbl_os.sinalizador
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		WHERE 1=1
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra
		ORDER BY tbl_linha.nome";
*/
	$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_admin where 1=1) )) THEN 1 else null END ) AS qtde                      ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os NOT IN (select os from tmp_reinc_90_$login_admin where 1=1) THEN 1 ELSE NULL END) AS qtde_recusada             ,
				SUM  (CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_admin where 1=1) )) THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_admin where 1=1) )) THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_admin where 1=1) )) THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
		FROM
			(SELECT tbl_os_extra.os,tbl_os.sinalizador
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica = tbl_os.fabrica
			WHERE tbl_os.fabrica = $login_fabrica AND tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		WHERE 1=1
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra
		ORDER BY tbl_linha.nome";
	//echo nl2br($sql);
$res = pg_query ($con,$sql);

echo $data_geracao = @pg_fetch_result ($res,0,data_geracao);

$data_geracao_extrato = $data_geracao;
echo "<br>";

$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		FROM tbl_posto_fabrica
		JOIN tbl_posto USING (posto)
		JOIN tbl_extrato ON tbl_extrato.fabrica = tbl_posto_fabrica.fabrica AND tbl_extrato.posto = tbl_posto_fabrica.posto
		WHERE tbl_extrato.fabrica = $login_fabrica AND tbl_extrato.extrato = $extrato";
$resX = @pg_query ($con,$sql);
if(pg_num_rows($resX) > 0) {
	echo @pg_fetch_result ($resX,0,codigo_posto) . " - " . @pg_fetch_result ($resX,0,nome);

	$codigo_posto2 = @pg_fetch_result ($resX,0,codigo_posto);
	$nome_posto2 = @pg_fetch_result ($resX,0,nome);
}

//include('posto_extrato_ano_britania.php');

$xsql = "SELECT extrato
		FROM tbl_extrato_conferencia 
		JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
		WHERE extrato = $extrato
		AND   tbl_extrato_conferencia.cancelada IS NOT TRUE";
$xres = pg_query($con,$xsql);
if(pg_numrows($xres)==0){
	$mostra_conferencia = 1;
}

if(pg_num_rows($res) > 0){
echo "<table width='450'>";
	echo "<tr>";
		echo "<td><BR></td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td bgcolor='#FF0000' width='15'>&nbsp;</td>";
		echo "<td style='font-size: 10px; text-align: left;'>Débito</td>";
		echo "<td bgcolor='#0000FF' width='15'>&nbsp;</td>";
		echo "<td style='font-size: 10px; text-align: left;'>Crédito</td>";
	//hd 22096
		echo "<td bgcolor='#339900' width='15'>&nbsp;</td>";
		echo "<td style='font-size: 10px; text-align: left;'>Valores de ajuste de Extrato</td>";
	echo "</tr>";
echo "</table>";
}
echo "<form name='frm_conferencia' method='post' action='".$PHP_SELF."?extrato=$extrato&posto=$posto'>";
echo "<table width='650' align='center' border='0' cellspacing='4'>";
echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusadas</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "<td align='center' nowrap >Pago via</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_qtde_recusada   = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

echo "<input type='hidden' name='qtde_linha' id='qtde_linha' value='".pg_num_rows($res)."'>";
$qtde_item_enviada = pg_num_rows($res);

for($i=0; $i<pg_num_rows($res); $i++){
	$linha             = pg_fetch_result ($res,$i,linha);
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
	$qtde_recusada     = number_format(pg_fetch_result ($res,$i,qtde_recusada),0,',','.');
	$qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
	$distrib_nome      = pg_fetch_result ($res,$i,distrib_nome) ;
	$distrib_posto     = pg_fetch_result ($res,$i,distrib_posto) ;

	echo "<tr style='font-size: 10px'>";

	echo "<td nowrap >";
	echo $linha_nome;
	echo "<input type='hidden' name='linha_$i' value='$linha'>";
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "<input type='hidden' name='unitario_$i' id='unitario_$i' value='$unitario'>";
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "<input type='hidden' name='qtde_$i' id='qtde_$i' value='$qtde'>";
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde_recusada;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "<input type='hidden' name='mao_de_obra_posto_$i' id='mao_de_obra_posto' value='$mao_de_obra_posto'>";
	echo "</td>";

	echo "<td  nowrap align='center'>";
	if (strlen ($distrib_nome) == 0) $distrib_nome = "<b>FABR.</b>";
	echo $distrib_nome;
	echo "<input type='hidden' name='distrib_posto_$i' id='mao_de_obra_posto' value='$distrib_posto'>";
	echo "</td>";

	$mounit = pg_fetch_result ($res,$i,unitario) ;

	echo "<td align='right' nowrap>";
	echo "<a href='extrato_posto_detalhe.php?extrato=$extrato&posto=$posto&linha=$linha&mounit=$mounit'>ver O.S.</a>";
	echo "</td>";

		$mao_de_obra_posto = fnc_limpa_moeda($mao_de_obra_posto);

		$mao_de_obra_posto_unitaria = fnc_limpa_moeda($mao_de_obra_posto_unitaria);

	echo "</tr>";

	$total_qtde            += pg_fetch_result ($res,$i,qtde) ;
	$total_qtde_recusada   += pg_fetch_result ($res,$i,qtde_recusada) ;
	$total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;
	$total_mo_adicional    += pg_fetch_result ($res,$i,mao_de_obra_adicional) ;
	$total_adicional_pecas += pg_fetch_result ($res,$i,adicional_pecas) ;
}

$sql = " SELECT
		extrato,
		historico,
		valor,
		admin,
		debito_credito,
		lancamento, 
		campos_adicionais
	FROM tbl_extrato_lancamento
	WHERE extrato = $extrato
	AND fabrica = $login_fabrica
	/* hd 22096 */ 
	AND (admin IS NOT NULL OR lancamento in (103,104))";
$res = pg_query ($con,$sql);

$total_avulso = 0;

if(pg_num_rows($res) > 0){
	for($i=0; $i < pg_num_rows($res); $i++){
		$extrato         = trim(pg_fetch_result($res, $i, extrato));
		$historico       = trim(pg_fetch_result($res, $i, historico));
		$valor           = trim(pg_fetch_result($res, $i, valor));
		$debito_credito  = trim(pg_fetch_result($res, $i, debito_credito));
		$lancamento      = trim(pg_fetch_result($res, $i, lancamento));
		$campos_adicionais = pg_fetch_result($res, $i, campos_adicionais);
		$campos_adicionais = json_decode($campos_adicionais, true);

		if($campos_adicionais['aprovacao'] == false){
			$situacao_avulso = "Aprovado";
		}else{
			$situacao_avulso = "Pendente de Aprovação";
		}

		if($debito_credito == 'D'){ 
			$bgcolor= "bgcolor='#FF0000'"; 
			$color = " color: #000000; ";
			if ($valor>0){
				$valor = $valor * -1;
			}
		}else{ 
			$bgcolor= "bgcolor='#0000FF'";
			$color = " color: #FFFFFF; ";
		}

		//hd 22096 - lançamentos e Valores de ajuste de Extrato
		if ($lancamento==103 or $lancamento==104) {
			$bgcolor= "bgcolor='#339900'";
		}

		echo "<tr style='font-size: 10px; $color' $bgcolor>";
		echo "<TD><b>Avulso</b></TD>";
		echo "<TD colspan='3'><b>$historico</b></TD>";
		echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b>
		</TD>";
		echo "<td></td>";
		echo "<td align='center'>$situacao_avulso</td>";
		echo "</tr>";
		$total_avulso = $valor + $total_avulso;
	}
}

$total = $total_mo_posto + $total_avulso;
echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center'>TOTAIS</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_qtde_recusada  ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total                ,2,",",".") . "</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='center'>&nbsp;</td>";
echo "</tr>";
echo "</table>";

$data = date('Y-m-d');
$unix = strtotime($data);
$data2= strtotime("+1 MONTH",$unix);
$competencia_futura= date('m/Y',$data2);

//---------------------------------------------------   sinalizador todas os
if (in_array($login_fabrica, [3])) {
	echo "<br/>";
	echo "<input type='button' id='agendar_pdf' value='Agenda geração PDF' /><br/>";?>
	<script type="text/javascript">
		$('#agendar_pdf').bind('click', function() {
			$.ajax({
					type: "GET",
					url: "extrato_posto_mao_obra_novo_britania_pdf.php",
					async:false,
					data:"agendar=true&extrato=<?php echo $_GET['extrato']?>",
					complete: function (resultado){
						alert('Solicitação feita com sucesso!');
					}
				});
		});
	</script>
	<?php
}
echo "<br/>";
echo "<a href='#detalhar' onclick=\"javascript:exibe('detalhar')\">Detalhar todas as OS</a><br/><center>";

echo "</center><br/><table class='table_line2' border='0' align='center' cellspacing='1' cellpadding='4' id='search'>";


if($login_fabrica==3 ){
	$sql = "SELECT  tbl_os.os              ,
					tbl_os.sua_os          ,
					tbl_os.produto         ,
					tbl_os.consumidor_nome ,
					tbl_os.consumidor_revenda ,
					tbl_os.revenda_nome    ,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.descricao  AS produto_descricao ,
					tbl_os.serie,
					tbl_os.os_reincidente,
					tbl_os.nf_os,
					tbl_os.sinalizador, 
					tbl_os_extra.mao_de_obra_desconto,
					tbl_os_extra.mao_de_obra,
					to_char (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
					to_char (tbl_os.data_nf,'DD/MM/YYYY') AS data_nf ,
					to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
					tbl_os.nota_fiscal,
					tbl_os_auditar.os_auditar
			FROM    tbl_os 
				JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica = tbl_os.fabrica
				JOIN    tbl_produto  ON tbl_os.produto = tbl_produto.produto
				LEFT JOIN tbl_os_auditar ON tbl_os_auditar.os = tbl_os.os
			WHERE   tbl_os.fabrica = $login_fabrica 
				AND tbl_os.posto = $posto
				AND tbl_os_extra.extrato = $extrato
			ORDER BY tbl_os.sinalizador,tbl_os.sua_os ";

	$res = pg_query ($con,$sql);

	echo "<thead>";
	echo "<tr><td align='center' colspan='100%'><div id='mensagem_os'></div></td></tr>";
	echo "<tr>";
	echo "<td align='center' colspan='100%'>";
	if(strlen($extrato_conferencia) == 0) { 
		echo "<input type='submit' name='btn_ok_todas' value='Marcar Todas as OSs para OK' >&nbsp;&nbsp;";
	}

	echo '<button style="cursor: pointer" type="button" id="iniciar_conferencia">
			 Iniciar Conferência
		  </button>';

	echo "</td>";
	echo "</tr>";

	echo "<tr rel='detalhar' style='display:none;'>";
	echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
	echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td>";
	echo "</tr>";

	echo "<tr rel='detalhar' style='display:none;'>";
	echo "<td width='18' height='20' bgcolor='#F3BCBC'>&nbsp;</td>";
	echo "<td align='left' colspan='4'><font size='1'><b>OS aprovada sem Mão-de-obra pela auditoria</b></font></td>";
	echo "</tr>";

	echo "<tr class='menu_top2'>";
	if (in_array($login_fabrica, [3])){
		echo "<th align='center' nowrap >Detalhe</th>";
	}
	echo "<th align='center' nowrap >Sinalizador</th>";
	echo "<th align='center' nowrap >OS</th>";
	echo "<th align='center' nowrap >Série</th>";
	echo "<th align='center' nowrap >NF.Compra</th>";
	echo "<th align='center' nowrap >Digitação</th>";
	echo "<th align='center' nowrap >Abertura</th>";
	echo "<th align='center' nowrap >Fechamento</th>";
	echo "<th align='center' nowrap >C/R</th>";
	echo "<th align='center' nowrap >Consumidor</th>";
	echo "<th align='center' nowrap >Produto</th>";
	echo "<th align='center' nowrap >MO</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

		$chk = $_POST['chk_'.$i];
		$cor = "#FFFFFF";
		if ($i % 2 == 0) $cor = "#FEF2C2";
		$os_reincidente = trim(pg_fetch_result ($res,$i,os_reincidente));
		if($os_reincidente == true){
			$cor = "#D7FFE1";
		}
		
		$mao_de_obra_desconto = trim(pg_fetch_result ($res,$i,mao_de_obra_desconto));
		$mao_de_obra 		  = trim(pg_fetch_result ($res,$i,mao_de_obra));
		$nota_fiscal          = trim(pg_fetch_result ($res,$i,nota_fiscal));
		$os                   = trim(pg_fetch_result ($res,$i,os));
		$consumidor_revenda   = trim(pg_fetch_result($res,$i,consumidor_revenda));
		$nf_os                = trim(pg_fetch_result($res,$i,nf_os));
		$serie                = trim(pg_fetch_result ($res,$i,serie));
		$os_auditar = trim(pg_fetch_result ($res,$i,os_auditar));
		
		if(!empty($os_auditar) && $mao_de_obra_desconto > 0 && $mao_de_obra_desconto >= $mao_de_obra){
			$cor = "#F3BCBC";
		}
		echo "<tr data-os='".$os."' bgcolor='$cor' style='font-size: 10px;' rel='detalhar' id='".pg_fetch_result ($res,$i,sua_os)."' >";

		$sqls = "SELECT sinalizador FROM tbl_os WHERE os = $os";
		$ress = pg_query($con,$sqls);
		$sinalizador_os = trim(pg_fetch_result ($ress,0,sinalizador));

		$disabled = "";

		$sql3 = "SELECT   caixa  
					FROM  tbl_extrato_conferencia
					WHERE extrato = $extrato 
					AND   cancelada IS NOT TRUE ";
		$res3 = pg_query ($con,$sql3) ;
		if (@pg_num_rows($res3) > 0) {
			$caixa = pg_fetch_result($res3,0,caixa);
			$disabled = " disabled ";
		}
		
		$sinalizador_aux = "";

		if($extrato_agrupado === true){

			if(strlen($sinalizador_os) > 0){

				$sql2 = "SELECT sinalizador, acao FROM tbl_sinalizador_os WHERE sinalizador = $sinalizador_os";
				$res2 = pg_query ($con,$sql2);

				if (pg_num_rows($res2) > 0) {

					$acao = trim(pg_fetch_result($res2, 0, "acao"));

					$sinalizador_aux = ($sinalizador_os == 1 || $sinalizador_os == 9) ? "{$acao}" : "<div rel='sinalizador' alt='$os'>{$acao}</div>";

				}

			}else{

				$sinalizador_aux = "<div rel='sinalizador' alt='$os'>Alterar</div>";

			}

		}else if(!empty($sinalizador_os)) {

			$sql2 = "SELECT sinalizador, acao FROM tbl_sinalizador_os where sinalizador = $sinalizador_os ";
			$res2 = pg_query ($con,$sql2);

			if (pg_num_rows($res2) > 0) {

				$acao = trim(pg_fetch_result($res2,0,acao));
				$sinalizador_aux .= ((($sinalizador_os == 1 || $sinalizador_os == 19) and !empty($disabled)) or $sinalizador_os == 3 or $sinalizador_os ==9) ? "$acao" : "<div rel='sinalizador' alt='$os'>$acao</div>";

			}

		}else{

			$sinalizador_aux .= "<div rel='sinalizador' alt='$os'>Alterar</div>";
		
		}
		if (in_array($login_fabrica, [3])){
			echo "<td class='abrir-conferencia' style='cursor: pointer;' data-os='".pg_fetch_result($res,$i,os)."'>Abrir</td>";
		}
		echo "<td id='msg_$os'>$sinalizador_aux</td>";

		echo "<th nowrap rel='os' id='mostra_$os'><div alt='$os' rel='sinalizador_os'><a href='os_press.php?os=".pg_fetch_result($res,$i,os)."' target='_blank'>".pg_fetch_result ($res,$i,sua_os) . "</a></div><div id='ok_$os'></div></th>";
		echo "<td nowrap >" . pg_fetch_result ($res,$i,serie) . "</td>";

		if ($consumidor_revenda == "C" and $sinalizador_os <> 3) {
			$sql3 = "SELECT nota_fiscal, os FROM tbl_os where posto=$posto and nota_fiscal='$nota_fiscal' AND fabrica = $login_fabrica and os < $os and consumidor_revenda = 'C' and (data_abertura + interval '600 days') > current_date";

			$res3 = pg_query ($con,$sql3);
			if (@pg_num_rows($res3) > 0) {
				echo "<th nowrap align='center' bgcolor='#FF8080' rel='nota_fiscal'>";
				for ($i3=0; $i3<pg_num_rows ($res3); $i3++ ){
					$nota_fiscalx = pg_fetch_result ($res3,$i3,nota_fiscal);
					$osx = pg_fetch_result ($res3,$i3,os);
					echo "<a href=\"javascript:abrejanela('os_press.php?os=" . $osx . "', " . $osx . ")\">". pg_fetch_result ($res,$i,nota_fiscal) ."</a>";
				}
				echo "</th>";
			}else{
				echo "<th nowrap align='center' rel='nota_fiscal'>" . pg_fetch_result ($res,$i,nota_fiscal) . "</th>";
			}
		}else{
			echo "<th nowrap align='center' rel='nota_fiscal'>" . $nota_fiscal . "</th>";
		}

		echo "<td nowrap >" . pg_fetch_result ($res,$i,digitacao) . "</td>";
		echo "<td nowrap >" . pg_fetch_result ($res,$i,abertura) . "</td>";
		echo "<td nowrap >" . pg_fetch_result ($res,$i,fechamento) . "</td>";
		echo "<td nowrap >" . $consumidor_revenda . "</td>";
		if (strlen(pg_fetch_result ($res,$i,consumidor_nome)) > 0) {
			echo "<td nowrap >" . pg_fetch_result ($res,$i,consumidor_nome) . "</td>";
		}else{
			echo "<td nowrap >" . pg_fetch_result ($res,$i,revenda_nome) . "</td>";
		}
		echo "<td nowrap >" . pg_fetch_result ($res,$i,produto_referencia) . "-" . pg_fetch_result ($res,$i,produto_descricao) . "</td>";
		if ($mao_de_obra_desconto > 0 and !in_array($os,$os_reinc)) {
			$xmounit = 0;
		}else{
			$xmounit = $mao_de_obra;
		}
		echo "<td nowrap >".number_format($xmounit,2,',','.')."</td>";
		echo "</tr>";
		echo "<tr id='tr_caixa_$os' rel=gravar><td colspan='5' align='left' id='td_caixa_$os' nowrap></td></tr>";
	}
	echo "";
	echo "</tbody>";
}

echo "</table><br><br>";

//---------------------------------------------------   fim sinalizador todas os

//*******************************************  conferencia

echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='4'>";
echo "<input type='hidden' name='extrato' value='$extrato'>";
echo "<input type='hidden' name='posto'   value='$posto'>";
echo "<input type='hidden' name='acao'   value=''>";

$sql = "SELECT  tbl_extrato_conferencia.extrato_conferencia                        AS extrato_conferencia,
					tbl_extrato_conferencia.data_conferencia                           AS data_conferencia,
					to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY')     AS data,
					tbl_extrato_conferencia.nota_fiscal                                AS nota_fiscal,
					to_char(tbl_extrato_conferencia.data_nf,'DD/MM/YYYY')              AS data_nf,
					tbl_extrato_conferencia.valor_nf                                   AS valor_nf,
					tbl_extrato_conferencia.valor_nf_a_pagar                           AS valor_nf_a_pagar,
					tbl_extrato_conferencia.caixa                                      AS caixa,
					to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY')   AS previsao_pagamento,
					tbl_extrato_conferencia.obs_fabricante                             ,
					tbl_admin.login                                                    AS login
			FROM tbl_extrato_conferencia
			JOIN tbl_admin   USING(admin)
			WHERE tbl_extrato_conferencia.extrato = $extrato
			AND   cancelada IS NOT TRUE
			ORDER BY tbl_extrato_conferencia.data_conferencia";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
	for ($i=0; $i<pg_num_rows($res); $i++) {
		$extrato_conferencia= pg_fetch_result($res,$i,extrato_conferencia);
		$data               = pg_fetch_result($res,$i,data);
		$nota_fiscal_posto  = pg_fetch_result($res,$i,nota_fiscal);
		$data_nf            = pg_fetch_result($res,$i,data_nf);
		$valor_nf           = pg_fetch_result($res,$i,valor_nf);
		$valor_nf_a_pagar   = pg_fetch_result($res,$i,valor_nf_a_pagar);
		$caixa              = pg_fetch_result($res,$i,caixa);
		$previsao_pagamento = pg_fetch_result($res,$i,previsao_pagamento);
		$admin              = pg_fetch_result($res,$i,login);
		$obs_fabricante     = pg_fetch_result($res,$i,obs_fabricante);
		$valor_nf           = number_format($valor_nf,2,",",".");
		$valor_nf_a_pagar   = number_format($valor_nf_a_pagar,2,",",".");

		$disable = (strlen($caixa) > 0) ? " disabled " : "";
	}
}else{
	$data =  date('d/m/Y');
}
?>

	<TR class='menu_top2'>
		<TD colspan="6">CONFERÊNCIA<input type='hidden' name='extrato_conferencia' value='<?=$extrato_conferencia?>'></TD>
	</TR>
	<TR class='menu_top2'>
		<TD>Cod. Posto</TD>
		<TD>Posto</TD>
		<TD>Caixa Arq.</TD>
		<TD>Data Conferência</TD>
		<TD>Admin</TD>
	</TR>
	<TR style='font-size: 10px;text-align:center'>
		<TD><? echo $codigo_posto2; ?></TD>
		<TD><? echo $nome_posto2; ?></TD>
		<TD><input type='text' name='caixa_conferencia' size="8" style='text-align:center' value="<? echo $caixa; ?>" <?=$disable?>></TD>
		<TD><? echo $data; ?></TD>
		<TD><? echo $admin; ?></TD>
	</TR>
</TABLE>
<?
	$sqlt = " SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
			FROM tbl_extrato_conferencia_item
			JOIN tbl_extrato_conferencia USING (extrato_conferencia)
			WHERE tbl_extrato_conferencia.extrato = $extrato 
			AND   cancelada IS NOT TRUE";
	$rest = pg_query($con,$sqlt);
	$total = pg_fetch_result($rest,0,total);

	$total_avulso = 0;

	$sql_av = " SELECT
		extrato,
		historico,
		valor,
		admin,
		debito_credito,
		lancamento
	FROM tbl_extrato_lancamento
	WHERE extrato = $extrato
	AND fabrica = $login_fabrica
	AND (admin IS NOT NULL OR lancamento in (103,104))";

	$res_av = pg_query ($con,$sql_av);

	for($i=0; $i < pg_num_rows($res_av); $i++){
		$valor           = trim(pg_fetch_result($res_av, $i, valor));
		$debito_credito  = trim(pg_fetch_result($res_av, $i, 'debito_credito'));
		$avulso_total = $valor + $avulso_total;
		if($debito_credito == 'D'){ 
			if ($valor>0){
				$valor = $valor * -1;
			}
		}
		

	}

	$total_valor =$avulso_total + $total;

?>
<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='4'>
	<TR class='menu_top2'>
		<TD colspan='2'>Nota Fiscal</TD>
		<TD colspan='2'>Data NF</TD>
		<TD>Valor Extrato</TD>
		<TD colspan='3'>Previsão de Pagamento</TD>
	</TR>
	<TR style='font-size: 10px;text-align:center'>
		<TD colspan='2'><input type='text' name='nf_conferencia' size="12" value="<? echo $nota_fiscal_posto; ?>" disabled style='text-align:center'></TD>
		<TD colspan='2'><input type='text' name='data_nf_conferencia' size="12" value="<? echo $data_nf; ?>" disabled style='text-align:center'></TD>
		<TD><input type='text' name='valor_conferencia_a_pagar' size="12" value="<? echo number_format($total_valor,2,",","."); ?>" disabled style='text-align:center'></TD>
		<TD colspan='3'><input type='text' name='previsao_pagamento_conferencia' size="12" value="<? echo $previsao_pagamento; ?>" disabled style='text-align:center'></TD>
	</TR>
<?	
$qtde_nota_avulsa = 0;

$sql = " SELECT codigo,reprovado,login,to_char(aprovado,'DD/MM/YYYY') as aprovado,motivo_reprovacao from tbl_extrato_agrupado LEFT JOIN tbl_admin USING(admin) where extrato = $extrato";
$res = @pg_query($con,$sql);
if(@pg_num_rows($res) > 0){
	$codigo_agrupado   = pg_fetch_result($res,0,'codigo');
	$reprovado         = pg_fetch_result($res,0,'reprovado');
	$aprovado          = pg_fetch_result($res,0,'aprovado');
	$motivo_reprovacao = pg_fetch_result($res,0,'motivo_reprovacao');
	$login             = pg_fetch_result($res,0,'login');
}
 if(strlen($codigo_agrupado)>0){
	$sql4 = "SELECT tbl_extrato.valor_agrupado 
			from tbl_extrato
			join tbl_extrato_agrupado using(extrato)
			where tbl_extrato.extrato = $extrato";
	$res4 = pg_query ($con,$sql4);
	$valor_agrupado = trim(pg_fetch_result($res4,0,valor_agrupado));

	if(!empty($reprovado)) {
		$codigo_agrupado = "PAGAMENTO NÃO AUTORIZADO";
		$valor_agrupado  = "0";
	}else{
		$codigo_agrupado = "<a href='javascript: abrePrint($codigo_agrupado)'>$codigo_agrupado</a>";
	}

 }
	 ?>
	<TR class='menu_top2'>
		<TD colspan='2'>Código Agrupamento</TD>
		<TD colspan='2'>Valor Total Agrupado</TD>
		<TD colspan='4'>Observação</TD>
	</TR>
	<TR style='font-size: 10px;text-align:center'>
		<TD colspan='2'><? echo $codigo_agrupado;
							echo "<br/>";
							echo "Motivo: $motivo_reprovacao";
		?></TD>
		<TD colspan='2'><? echo number_format ($valor_agrupado,2,",","."); ?></TD>
		<TD colspan='4'><input type='text' name='obs_fabricante' value="<?=$obs_fabricante?>" size='60' <?=$disable?>></TD>
	</TR>
	<? if(!empty($aprovado)) { ?>
	<TR class='menu_top2'>
		<TD colspan='2'>Aprovador</TD>
		<TD colspan='2'>Data Aprovação</TD>
	</TR>
	<TR style='font-size: 10px;text-align:center'>
		<TD colspan='2'><? echo $login; ?></TD>
		<TD colspan='2'><? echo $aprovado; ?></TD>
	</TR>
	<? }?>

	<TR id='0' class='menu_top2'>
		<td>Data Lançamento</td>
		<td>Admin</td>
		<td>Nota Fiscal</td>
		<td>Data Emissão</td>
		<td>Valor Original</td>
		<td>Previsão Pagamento</td>
		<td>Série</td>
		<td>Estabelecimento</td>
	</TR>
	<?
		$sql = " SELECT  
					extrato_nota_avulsa,
					nota_fiscal   ,
					valor_original,
					to_char(data_lancamento,'DD/MM/YYYY') as data_lancamento,
					to_char(data_emissao,'DD/MM/YYYY') as data_emissao,
					to_char(previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento,
					login             ,
					observacao        ,
					cfop              ,
					codigo_item       ,
					serie             ,
					estabelecimento   
				FROM tbl_extrato_nota_avulsa
				JOIN tbl_admin USING(admin) 
				WHERE extrato = $extrato
				AND   tbl_extrato_nota_avulsa.fabrica = $login_fabrica 
				ORDER BY extrato_nota_avulsa";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			for($i =0;$i<pg_num_rows($res);$i++) {
				$extrato_nota_avulsa= pg_fetch_result($res,$i,'extrato_nota_avulsa');
				$data_lancamento   = pg_fetch_result($res,$i,'data_lancamento');
				$nota_fiscal       = pg_fetch_result($res,$i,'nota_fiscal');
				$login             = pg_fetch_result($res,$i,'login');
				$data_emissao      = pg_fetch_result($res,$i,'data_emissao');
				$observacao        = pg_fetch_result($res,$i,'observacao');
				$valor_original    = number_format(pg_fetch_result($res,$i,'valor_original'),2,",","."); 
				$previsao_pagamento= pg_fetch_result($res,$i,'previsao_pagamento');
				$cfop              = pg_fetch_result($res,$i,'cfop');
				$codigo_item       = pg_fetch_result($res,$i,'codigo_item');
				$serie             = pg_fetch_result($res,$i,'serie');
				$estabelecimento   = pg_fetch_result($res,$i,'estabelecimento');

				$sqln = " SELECT descricao
						FROM tbl_extrato_tipo_nota
						WHERE cfop = '$cfop'
						AND   codigo_item = '$codigo_item'";
				$resn = pg_query($con,$sqln);
				if(pg_num_rows($resn) > 0){
					$descricao    =pg_fetch_result($resn,0,'descricao');
				}else{
					$sqln = " SELECT descricao
						FROM tbl_extrato_tipo_nota
						JOIN tbl_extrato_tipo_nota_excecao exc USING(extrato_tipo_nota)
						WHERE exc.cfop = '$cfop'
						AND   exc.codigo_item = '$codigo_item'";
					$resn = pg_query($con,$sqln);
					if(pg_num_rows($resn) > 0){
						$descricao    =pg_fetch_result($resn,0,'descricao');
					}
				}
				echo "<tr style='font-size: 10px;text-align:center'>";
				echo "<td>$data_lancamento</td>";
				echo "<td>$login</td>";
				echo "<td>$nota_fiscal</td>";
				echo "<td>$data_emissao</td>";
				echo "<td>$valor_original</td>";
				echo "<td nowrap>$previsao_pagamento </td>";
				echo "<td>$serie</td>";
				echo "<td>$estabelecimento<a href=\"javascript:excluirNota($extrato_nota_avulsa,$extrato,$extrato_nota_avulsa)\" id='excluir_$extrato_nota_avulsa'><img src='imagens/btn_x.gif'>Excluir</a></td>";
				echo "</tr>";
				echo "<tr class='excluir_obs_$extrato_nota_avulsa'><td  style='text-align:center; font-size: x-small;font-weight: bold;color:#ffffff; background-color: #596D9B'>Observação</td><td colspan='3' style='text-align:center; font-size: x-small;' nowrap>$observacao</td>";
				echo "<td  style='text-align:center; font-size: x-small;font-weight: bold;color:#ffffff; background-color: #596D9B'>Tipo Nota</td><td style='text-align:center; font-size: x-small;' nowrap>$descricao  </td></tr>";
			}
			
		}
	?>
	<TR style='font-size: 10px'>
		<TD colspan='5' align='center' nowrap>
		<input type='hidden' name='qtde_nota_avulsa' id='qtde_nota_avulsa' value='<?=$qtde_nota_avulsa?>'>
		<?if(empty($codigo_agrupado)) { ?>
		<input type='button' name='btn_adiciona_nota' value="Adicionar Avulso" onclick='window.location="extrato_avulso_cadastro.php?extrato=<?=$extrato?>&posto=<?=$posto?>"'>
		<?}?>
		<input type='button' name='btn_adiciona_nota' value="Adicionar Nota Avulsa" onclick='addNota()'>
		<input type='hidden' name='gravar_conferencia' value=''>
		<input type='button' name='btn_gravar_conferencia' value="FINALIZAR PROCESSO"  onclick="javascript: if (document.frm_conferencia.gravar_conferencia.value == '' ) { document.frm_conferencia.gravar_conferencia.value='gravar_conferencia' ; document.frm_conferencia.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') } return false;" >
		<? if(strlen($extrato_conferencia) > 0) { ?>
			<input type='hidden'  name='cancelar_conferencia' value="">
			&nbsp;&nbsp;<input type='button' name='btn_cancelar' value="CANCELAR CONFERÊNCIA" onclick="if(confirm('Deseja realmente cancelar essa conferencia?') == true){
			document.frm_conferencia.cancelar_conferencia.value='cancelar';
			document.frm_conferencia.submit();
			}">
			<input type='submit' name='btn_atualiza' value="ATUALIZAR QTDE OS" >
		<? } ?>
		</TD>
	</TR>
</TABLE> 
</form>
<?
//*******************************************  fim conferencia


/****************************resumo****************************/
echo "<br>";
$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_extrato_conferencia_item.mao_de_obra_unitario AS unitario ,
				tbl_extrato_conferencia_item.mao_de_obra AS mao_de_obra_posto ,
				tbl_extrato_conferencia_item.qtde_conferida as qtde
		FROM tbl_extrato_conferencia
		JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
		JOIN tbl_linha    ON tbl_extrato_conferencia_item.linha = tbl_linha.linha
		WHERE  tbl_extrato_conferencia.extrato = $extrato
		AND    tbl_extrato_conferencia.cancelada IS NOT TRUE
		ORDER BY tbl_linha.nome";

$res = pg_query ($con,$sql);

echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='6'>RESUMO DE CONFERÊNCIA PARA PAGAMENTO</TD></TR>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap >Sinalizador</td>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusadas</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_qtde_recusada   = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;


for($i=0; $i<pg_num_rows($res); $i++){
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
	$linha = pg_fetch_result($res,$i,linha);
	$qtde              = pg_fetch_result ($res,$i,qtde);
	
	$mao_de_obra_a_pagar += pg_fetch_result ($res,$i,mao_de_obra_posto);
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');

	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FEF2C2";


	$sqlr = " SELECT count(*) as qtde_recusada
			FROM tbl_os_extra
			JOIN tbl_os USING(os)
			WHERE (tbl_os.sinalizador = 3 AND tbl_os_extra.mao_de_obra_desconto IS NOT NULL)
			AND   tbl_os_extra.i_fabrica = tbl_os.fabrica
			AND   tbl_os.fabrica = $login_fabrica
			AND   tbl_os_extra.linha = $linha
			AND   tbl_os_extra.extrato = $extrato
			AND   tbl_os_extra.mao_de_obra = ".pg_fetch_result($res,$i,unitario);
	$resr= pg_query($con,$sqlr);
	if(pg_num_rows($resr) > 0){
		$qtde_recusada  = number_format(pg_fetch_result ($resr,0,qtde_recusada),0,',','.');
	}

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";

	echo "<td nowrap align='center'>OK</td>";

	echo "<td nowrap align='center'>";
	echo $linha_nome;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde_recusada;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "</td>";

	echo "</tr>";

	$total_qtde            += $qtde;
	$total_qtde_recusada   += $qtde_recusada;
	$total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;

}



if(pg_num_rows($res_av) > 0){
	for($i=0; $i < pg_num_rows($res_av); $i++){
		$extrato         = trim(pg_fetch_result($res_av, $i, extrato));
		$historico       = trim(pg_fetch_result($res_av, $i, historico));
		$valor           = trim(pg_fetch_result($res_av, $i, valor));
		$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
		$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
		
		if($debito_credito == 'D'){ 
			$bgcolor= "bgcolor='#FF0000'"; 
			$color = " color: #000000; ";
			if ( $valor>0){
				$valor = $valor * -1;
			}
		}else{ 
			$bgcolor= "bgcolor='#0000FF'";
			$color = " color: #FFFFFF; ";
		}

		//hd 22096 - lançamentos e Valores de ajuste de Extrato
		if ($lancamento==103 or $lancamento==104) {
			$bgcolor= "bgcolor='#339900'";
		}

		echo "<tr style='font-size: 10px; $color' $bgcolor>";
		echo "<TD><b>Avulso</b></TD>";
		echo "<TD colspan='4'><b>$historico</b></TD>";
		echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b>
		<INPUT TYPE='hidden' NAME='valor_avulso_$i' id='valor_avulso_$i' value='$valor'>
		</TD>";
		echo "</tr>";
		$total_avulso = $valor + $total_avulso;
	}
}

$total_nota	= ($mao_de_obra_a_pagar+$total_avulso);

echo "<TR class='menu_top2'><TD colspan='3'>TOTAL PARA PAGAMENTO</TD><TD align='right'>".$total_qtde."</TD><TD align='right'>".$total_qtde_recusada."</TD><TD align='right'>".number_format ($total_nota,2,",",".")."</TD></TR></table>";


/*$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				tbl_sinalizador_os.acao              ,
				COUNT(tbl_os.os) AS qtde                      ,
				SUM  ( tbl_os_extra.mao_de_obra) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		WHERE tbl_sinalizador_os.debito='S' AND tbl_os.sinalizador = 3
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao 
		ORDER BY tbl_linha.nome";

$res = pg_query ($con,$sql);

echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='5'>RESUMO DE CONFERÊNCIA OS REINCIDENTE</TD></TR>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap >Sinalizador</td>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "</tr>";

$total_qtde            = 0 ;

for($i=0; $i<pg_num_rows($res); $i++){
	$acao_sinalizador             = pg_fetch_result ($res,$i,acao);
	$linha             = pg_fetch_result ($res,$i,linha);
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
	$qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
	$total_os_r += number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');

	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FEF2C2";

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";

	echo "<td nowrap align='center'>";
	echo $acao_sinalizador;
	echo "</td>";

	echo "<td nowrap align='center'>";
	echo $linha_nome;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "</td>";
	echo "</tr>";

	$total_qtde            += pg_fetch_result ($res,$i,qtde) ;
	$total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;


}
echo "<TR class='menu_top2'><TD colspan='3'>TOTAIS</TD><TD align='right'>".$total_qtde."</TD><TD align='right'>".number_format ($total_os_r,2,",",".")."</TD></TR></table>";
echo "</table>";*/
//------------------------------------resumo irregulares
echo "<br>";
$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				tbl_sinalizador_os.acao              ,
				COUNT(tbl_os.os) AS qtde                      ,
				SUM  ( tbl_os_extra.mao_de_obra ) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os and tbl_os_extra.i_fabrica = tbl_os.fabrica
			WHERE tbl_os.fabrica = $login_fabrica and tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		WHERE tbl_os.fabrica = $login_fabrica and tbl_sinalizador_os.debito='S' AND tbl_os.sinalizador <> 3
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao 
		ORDER BY tbl_linha.nome";

$res = pg_query ($con,$sql);

echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='5'>RESUMO DE CONFERÊNCIA COM IRREGULARIDADE</TD></TR>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap >Sinalizador</td>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "</tr>";

$total_qtde            = 0 ;

echo "<input type='hidden' name='qtde_linha' id='qtde_linha' value='".pg_num_rows($res)."'>";
$qtde_item_enviada = pg_num_rows($res);

for($i=0; $i<pg_num_rows($res); $i++){
	$acao_sinalizador             = pg_fetch_result ($res,$i,acao);
	$linha             = pg_fetch_result ($res,$i,linha);
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
	$qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
	$total_os_i += (pg_fetch_result ($res,$i,mao_de_obra_posto));

	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FEF2C2";

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";

	echo "<td nowrap align='center' >";
	echo $acao_sinalizador;
	echo "</td>";

	echo "<td nowrap align='center'>";
	echo $linha_nome;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "</td>";
	echo "</tr>";

	$total_qtde            += pg_fetch_result ($res,$i,qtde) ;

}
echo "<TR class='menu_top2'><TD colspan='3'>TOTAIS</TD><TD align='right'>".$total_qtde."</TD><TD align='right'>".number_format ($total_os_i,2,",",".")."</TD></TR></table>";
echo "</table>";
//------------------------------------------ pendentes de conferencia
echo "<br>";
/*$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE NULL END) AS qtde                      ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato and  tbl_os.sinalizador is null
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra
		ORDER BY tbl_linha.nome";
$res = pg_query ($con,$sql);

$xsql = "SELECT extrato 
		FROM tbl_extrato_conferencia 
		JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
		WHERE extrato = $extrato
		AND   tbl_extrato_conferencia.cancelada IS NOT TRUE";
$xres = pg_query ($con,$xsql);
if(pg_num_rows($xres)==0){
	$mostra_conferencia = 1;
}

echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='5'>RESUMO DE OS PENDENTES DE CONFERÊNCIA</TD></TR>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;


for($i=0; $i<pg_num_rows($res); $i++){
	
	$linha             = pg_fetch_result ($res,$i,linha);
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
	$qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
	$distrib_nome      = pg_fetch_result ($res,$i,distrib_nome) ;
	$distrib_posto     = pg_fetch_result ($res,$i,distrib_posto) ;

	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FEF2C2";

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";

	echo "<td nowrap >";
	echo $linha_nome;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "</td>";

	echo "</tr>";

	$total_qtde            += pg_fetch_result ($res,$i,qtde) ;
	$total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;
	$total_mo_adicional    += pg_fetch_result ($res,$i,mao_de_obra_adicional) ;
	$total_adicional_pecas += pg_fetch_result ($res,$i,adicional_pecas) ;

}
echo "</table><br>";
*/
/****************************    fim resumo    ****************************/



echo "<p><a href='extrato_posto_mao_obra_os_download.php?extrato=$extrato' target='_blank'>Clique aqui para fazer o download das Ordens de Serviços</a></p>";
echo "<p><a href='extrato_posto_mao_obra_novo_britania_print.php?extrato=$extrato' target='_blank'><img src='imagens/btn_imprimir.gif'></a></p>";

echo "<BR><p>";
echo "<a href='";
echo (strlen($caixa) == 0) ? "javascript:outroExtrato();" :"extrato_posto_britania_novo_processo.php";
echo "'>Outro extrato</a>";

?>
<p><p>
<?php 
if (in_array($login_fabrica, [3])) {?>
<script type="text/javascript">
$(function() {
	Shadowbox.init();

	$("#iniciar_conferencia").click(function(){

		if ($("table#search tbody tr:first").is(":not(:visible)")) {
			exibe('detalhar');
		}

		let os = $("table#search tbody tr:first").data("os");

		Shadowbox.open({
            content :   "shadowbox_extrato_posto_mao_obra_novo.php?os=" + os,
            player  :   "iframe",
            title   :   "<?= traduz('historico.de.help.desk') ?>",
            width   :   2000,
            height  :   1000
        });

	});

});
	$(".abrir-conferencia").click(function(){
        Shadowbox.open({
            content :   "shadowbox_extrato_posto_mao_obra_novo.php?os=" + $(this).data('os'),
            player  :   "iframe",
            title   :   "<?= traduz('historico.de.help.desk') ?>",
            width   :   2000,
            height  :   1000
        });
    });
</script>
<?
}
include "rodape.php";

?>
