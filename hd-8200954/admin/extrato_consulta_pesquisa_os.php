<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';

$msg_aviso = $_GET['msg_aviso'];
$ajax_debito = $_GET['ajax_debito'];
if(strlen($ajax_debito)==0){$ajax_debito = $_POST['ajax_debito'];}

if(strlen($ajax_debito)>0){
	$btn_acao = $_POST['btn_acao'];

	if($ajax_debito=="true") {
		$os  = $_GET['os'];
		$sql = "SELECT  tbl_os_extra.extrato, 
						tbl_os.os ,
						tbl_os.mao_de_obra,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_produto.referencia,
						tbl_produto.descricao,
						(select	tbl_os_status.observacao from tbl_os_status where tbl_os_status.os = tbl_os.os order by os_status desc limit 1) as observacao
				FROM tbl_os_extra
				JOIN tbl_os on tbl_os.os = tbl_os_extra.os
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
				LEFT JOIN tbl_os_status on tbl_os_status.os = tbl_os.os and tbl_os_status.extrato = tbl_os_extra.extrato
				where tbl_os.fabrica = $login_fabrica 
				and tbl_os_extra.os = $os";
		//echo $sql;
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$extrato = pg_result($res,0,extrato);
			$os      = pg_result($res,0,os);
			$mao_de_obra  = pg_result($res,0,mao_de_obra);
			$codigo_posto = pg_result($res,0,codigo_posto);
			$referencia   = pg_result($res,0,referencia);
			$descricao    = pg_result($res,0,descricao);
			$nome_posto   = pg_result($res,0,nome);
			$observacao   = pg_result($res,0,observacao);

		}
		echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' align='center' style='font-family: verdana; font-size: 10px'>";
			echo "<tr>";
			echo "<td width='50'>Extrato:</td><td colspan='3'> <B>$extrato</B></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Posto: </td><td colspan='3'><B>$codigo_posto - $nome_posto</B> </td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>OS: </td><td><B>$os</b></td>";
			echo "<td >Mão-de-obra: </td><td width='250'><B> R$ " . number_format($mao_de_obra,2,",",".") . "</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Produto: </td><td colspan='3'><B>$referencia - $descricao</B> </td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Observação: </td><td colspan='3'><B><font color='#AF1807'>$observacao</font></B> </td>";
			echo "</tr>";
		echo "</table>";
		echo "<form name='frm_acerto' method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='ajax_debito' value='cadastro'>";
		echo "<table border='1' cellpadding='4' cellspacing='1' width='90%' align='center' style='font-family: verdana; font-size: 10px'>";
			echo "<tr>";
			echo "<td colspan='2'>Para alterar o valor da mão-de-obra da OS $os por favor insira os dados abaixo:</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td><B>Valor Mão-de-obra: R$ </B> <input type='text' name='mao_de_obra' size='5' maxlength='5' value='" . number_format($mao_de_obra,2,",",".") . "' class='frm'></td>";
			echo "</tr>";
			
			echo "<tr>";
			echo "<td colspan='2' align='center'><B>Observação: </B><BR><TEXTAREA NAME='obs_acerto' ROWS='5' COLS='50'  class='frm'></TEXTAREA>";
			echo "<input type='hidden' name='extrato' value='$extrato'>";
			echo "<input type='hidden' name='os' value='$os'>";
			echo "<input type='hidden' name='btn_acao' value=''>";
			echo "<BR><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_acerto.btn_acao.value == '' ) { document.frm_acerto.btn_acao.value='gravar' ; document.frm_acerto.submit() } else { alert ('Aguarde ') }\" ALT=\"Gravar itens da Ordem de Serviço\" border='0' style=\"cursor:pointer;\">";
			echo "</td>";
			echo "</tr>";

		echo "</table>";

		echo "</form>";
	}
	if($btn_acao =="gravar"){
		$os          = $_POST['os'];
		$extrato     = $_POST['extrato'];
		$obs_acerto  = $_POST['obs_acerto'];
		if(strlen($obs_acerto)==0){$msg_erro = "Insira o comentário";}
		$mao_de_obra = trim($_POST['mao_de_obra']);
		if(strlen($mao_de_obra)==0){$msg_erro = "Insira o valor da mão-de-obra";}
		$mao_de_obra = "'".$mao_de_obra."'";
		$mao_de_obra = fnc_limpa_moeda($mao_de_obra);
		
		if(strlen($msg_erro)==0){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			$sql = "INSERT into tbl_os_status(
							os         ,
							status_os  ,
							data       ,
							observacao ,
							extrato    ,
							admin
						)values(
							$os,
								90,
								current_timestamp,
								'$obs_acerto',
								$extrato,
								$login_admin
							);";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		//	echo $sql."<BR>";
		}
		if(strlen($msg_erro)==0){
			$sql = "UPDATE tbl_os set mao_de_obra = $mao_de_obra
					WHERE  os = $os and fabrica = $login_fabrica;";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			//echo $sql."<BR>";
		}
		if(strlen($msg_erro)==0){
			#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
			#$res = pg_exec($con,$sql);
			#$total_os_extrato = pg_result($res,0,0);
			#HD15716
			if($login_fabrica == 11){
				$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
			}else{
				$sql = "select fn_calcula_extrato($login_fabrica,$extrato)";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				//echo $sql."<BR>";
			}
		}
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
				echo "<center>Alteração efetuada com sucesso!!</center>";	
		}else{
		
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "<center>Ocorreu o seguinte erro: $msg_erro</center>";
		}
	/*

INSERT into tbl_os_status( os , status_os , data , observacao , extrato , admin )values( 3773040, 90, current_timestamp, 'mao de obra estará zero pq eu quero', 195118, 158 );
UPDATE tbl_os set mao_de_obra = 0.00 where os = 3773040 and fabrica = 6;
select fn_calcula_extrato(195118,6);
Alteração efetuada com sucesso!

telecontrol=> \d tbl_os_status;
                                        Table "public.tbl_os_status"
   Column   |            Type             |                            Modifiers
------------+-----------------------------+-----------------------------------------------------------------
 os_status  | integer                     | not null default nextval(('tbl_os_status_seq'::text)::regclass)
 os         | integer                     |
 status_os  | integer                     | not null
 data       | timestamp without time zone | default ('now'::text)::timestamp(6) with time zone
 observacao | text                        | not null
 extrato    | integer                     |
 os_sedex   | integer                     |
 admin      | integer                     |


90
	*/


	}
exit;
}

if (strlen($_POST["btn_acao"]) == 0) {
	$data_inicial = $_GET["data_inicial"];
	$data_final = $_GET["data_final"];
	$cnpj = $_GET["cnpj"];
	$razao = $_GET["razao"];
}

if (strlen($_POST["btn_acao"]) == 0 && strlen($_POST["select_acao"]) == 0) {
	setcookie("link", $REQUEST_URI, time()+60*60*24); # Expira em 1 dia
}

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$extrato = $_GET['extrato'];
	$observacao = $_GET['observacao'];

//	echo $extrato;
//	echo "<BR>";
//	echo $observacao;
	$sql = "BEGIN TRANSACTION";
	$res = pg_exec($con,$sql);

	$sql = "SELECT os 
			FROM tbl_os_extra
			WHERE tbl_os_extra.extrato = $extrato";
	$res = pg_exec($con,$sql);
//	echo "$sql<BR>";
	if(pg_numrows($res)>0){
		for($i=0;pg_numrows($res)>$i;$i++){
			$os = pg_result($res,$i,os);
			$xsql = "select tbl_os.posto       ,
							tbl_os_item.qtde   ,
							tbl_os.os          ,
							tbl_os_item.os_item,
							tbl_os_item.peca
					from tbl_os
					JOIN tbl_os_produto using(os)
					JOIN tbl_os_item using(os_produto)
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.os      = $os
					AND   tbl_os_item.peca_sem_estoque is true";
			$xres = pg_exec($con,$xsql);
				//echo "$xsql<BR>";
			$msg_erro .= pg_errormessage($con);
			if(pg_numrows($res)>0){
					for($x=0;pg_numrows($xres)>$x;$x++){
						$posto = pg_result($xres,$x,posto);
						$qtde  = pg_result($xres,$x,qtde);
						$os    = pg_result($xres,$x,os);
						$os_item= pg_result($xres,$x,os_item);
						$peca   = pg_result($xres,$x,peca);

						$ysql = "INSERT INTO tbl_estoque_posto_movimento(
											fabrica      , 
											posto        , 
											os           ,
											peca         , 
											qtde_entrada   ,
											data, 
											os_item, 
											obs,
											admin
										)VALUES(
											$login_fabrica,
											$posto        ,
											$os           , 
											$peca         ,
											$qtde         ,
											current_date  ,
											$os_item       ,
											'Automático: $observacao',
											$login_admin
									)";
										//	echo $ysql;
										//		echo "<BR>";
								$yres = pg_exec($con,$ysql);
								$msg_erro .= pg_errormessage($con);
								
								if(strlen($msg_erro)==0){
									$ysql = "SELECT peca 
											FROM tbl_estoque_posto 
											WHERE peca = $peca 
											AND posto = $posto 
											AND fabrica = $login_fabrica;";
									$yres = pg_exec($con,$ysql);
									//echo "$ysql<BR>";
									if(pg_numrows($res)>0){
										$ysql = "UPDATE tbl_estoque_posto set 
												qtde = qtde + $qtde
												WHERE peca  = $peca
												AND posto   = $posto
												AND fabrica = $login_fabrica;";
										$yres = pg_exec($con,$ysql);
									//	echo $ysql;
									//	echo "<BR>";
										$msg_erro .= pg_errormessage($con);
									}else{
										$ysql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)
												values($login_fabrica,$posto,$peca,$qtde)";
										$yres = pg_exec($con,$ysql);
									//	echo $ysql;
									//	echo "<BR>";
										$msg_erro .= pg_errormessage($con);
									}
								}
					}
			}
		}
	
	}
	if (strlen($msg_erro) == 0) {
	//	$res = pg_exec($con,"ROLLBACK TRANSACTION");
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "Peça(s) aceita(s) com sucesso!";
	//	exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "Erro no processo: $msg_erro";
	}
	exit;
}

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));

if (strlen($_POST["adiciona_sua_os"]) > 0) $adiciona_sua_os = trim(strtolower($_POST["adiciona_sua_os"]));
if (strlen($_GET["adiciona_sua_os"]) > 0) $adiciona_sua_os = trim(strtolower($_GET["adiciona_sua_os"]));


if (strlen($_POST["select_acao"]) > 0) $select_acao = strtoupper($_POST["select_acao"]);


$xlancamento = $_GET['xlancamento'];
$acao = $_GET['acao'];
$extrato = $_GET['extrato'];
if(strlen($xlancamento)>0 and strlen($acao)>0 and strlen($extrato)>0 and ($login_fabrica==6 or $login_fabrica==45)){//hd 9482
	$sql = "SELECT extrato_lancamento
			from tbl_extrato_lancamento
			where extrato = $extrato
			and extrato_lancamento = $xlancamento";
	$res = pg_exec($con,$sql);
//	echo "$sql<BR>";
	if(pg_numrows($res)>0){
		$sql = "DELETE from tbl_extrato_lancamento 
				where fabrica=$login_fabrica
				and extrato = $extrato
				and extrato_lancamento = $xlancamento";
//		echo $sql;
		$res = pg_exec($con,$sql);
	
		//HD 6887
		if($login_fabrica==6){
			$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
			$res = pg_exec($con,$sql);
		}else{ //hd 9482
			#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
			#$res = pg_exec($con,$sql);
			#$total_os_extrato = pg_result($res,0,0);
			#HD15716
			if($login_fabrica == 11){
				$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
			}else{
				$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
				$res = pg_exec($con,$sql);
			}
		}
	}
}
$lancamento = $_GET['lancamento'];
if(strlen($lancamento) > 0){
	$select_acao = 'RECUSAR';
}

if (strlen($_POST["extrato"]) > 0) $extrato = trim($_POST["extrato"]);
if (strlen($_GET["extrato"]) > 0)  $extrato = trim($_GET["extrato"]);

$msg_erro = "";

if ($btn_acao == 'pedido'){
	header ("Location: relatorio_pedido_peca_kit.php?extrato=$extrato");
	exit;
}

if ($btn_acao == 'baixar') {

	if (strlen($_POST["extrato_pagamento"]) > 0) $extrato_pagamento = trim($_POST["extrato_pagamento"]);
	if (strlen($_GET["extrato_pagamento"]) > 0)  $extrato_pagamento = trim($_GET["extrato_pagamento"]);

	$valor_total     = trim($_POST["valor_total"]) ;
	if(strlen($valor_total) > 0)   $xvalor_total = "'".str_replace(",",".",$valor_total)."'";
	else                           $xvalor_total = 'NULL';

	$acrescimo       = trim($_POST["acrescimo"]) ;
	if(strlen($acrescimo) > 0)     $xacrescimo = "'".str_replace(",",".",$acrescimo)."'";
	else                           $xacrescimo = 'NULL';

	$desconto        = trim($_POST["desconto"]) ;
	if(strlen($desconto) > 0)      $xdesconto = "'".str_replace(",",".",$desconto)."'";
	else                           $xdesconto = 'NULL';

	$valor_liquido   = trim($_POST["valor_liquido"]) ;
	if(strlen($valor_liquido) > 0) $xvalor_liquido = "'".str_replace(",",".",$valor_liquido)."'";
	else                           $xvalor_liquido = 'NULL';

	$nf_autorizacao = trim($_POST["nf_autorizacao"]) ;
	if(strlen($nf_autorizacao) > 0) $xnf_autorizacao = "'$nf_autorizacao'";
	else                            $xnf_autorizacao = 'NULL';

	$autorizacao_pagto = trim($_POST["autorizacao_pagto"]) ;
	if(strlen($nf_autorizacao) > 0) $xautorizacao_pagto = "'$autorizacao_pagto'";
	else                            $xautorizacao_pagto = 'NULL';

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

//-=============Verifica data=================-//
		
		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_pagamento = "'" . $xdata_pagamento . "'";
		}else{
			$msg_erro="A Data de Pagamento não está em um formato válido";
		}
	}else{
		$xdata_pagamento = "NULL";
		//HD 9387 Paulo 10/12/2007
		$msg_erro.="Por favor, digitar a Data de Pagamento!!!";
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
		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_vencimento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_vencimento = "'" . $xdata_vencimento . "'";
		}else{
			$msg_erro .="<br>A Data de Vencimento não está em um formato válido<br>";
		}
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
						AND   tbl_extrato.fabrica                     = $login_fabrica";
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
//	if ($ip=='201.43.248.103') echo $sql;
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			if($login_fabrica == 24){// ficava listando sem tem necessidade, takashi tirou conforme conversa com aline 11/12/07
				echo "<script>";
				echo "window.close();";
				echo "</script>";
			}else{
				header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
				exit;
			}
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "excluir") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");
//$msg_erro .= "$sql <br>$total<br>";
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
						status_os  ,
						admin
					) VALUES (
						$extrato ,
						$x_os    ,
						'$x_obs' ,
						15       ,
						$login_admin
					);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
$msg_er .= "$sql <br>$total<br>";
		if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os_extra SET extrato = null
						WHERE  tbl_os_extra.os      = $x_os
						AND    tbl_os_extra.extrato = $extrato
						AND    tbl_os_extra.os      = tbl_os.os
						AND    tbl_os_extra.extrato = tbl_extrato.extrato
						AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
						AND    tbl_extrato_extra.baixado IS NULL
						AND    tbl_os.fabrica  = $login_fabrica;";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
$msg_er .= "$sql <br>$total<br>";
		}
/*
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
*/
		#OS_TROCA - Excluida/Acumulada/Recusada o débito HD14648
		if(strlen($msg_erro) == 0 AND $login_fabrica == 1){
			$sql = "SELECT tbl_os_troca.os_troca    ,
							tbl_os_troca.total_troca,
							tbl_os.os               ,
							tbl_os.sua_os
						FROM tbl_os 
						JOIN tbl_os_troca USING(os) 
						WHERE os = $x_os";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0){
				$sua_os_troca   = pg_result($res,0,sua_os);
				$os_sedex_troca = '';
				#troca
				$sql = "SELECT os_sedex 
							FROM tbl_os_sedex 
							WHERE extrato_destino = $extrato 
							AND sua_os_destino = '$sua_os_troca'; ";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res) > 0){
					$os_sedex_troca = pg_result($res, 0,os_sedex);
					#Sedex
					$sql = "DELETE FROM tbl_os_sedex WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "SELECT extrato_lancamento 
								FROM tbl_extrato_lancamento
								WHERE extrato = $extrato
								AND   os_sedex = $os_sedex_troca;";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res) > 0 AND strlen($msg_erro) == 0){
						$extrato_lancamento_troca = pg_result($res,0,extrato_lancamento);
						#extrato lançamento
						$sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}

		if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET excluida = true
						WHERE  tbl_os.os           = $x_os
						AND    tbl_os.fabrica      = $login_fabrica;";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
			$res = pg_exec($con, $sql);


$msg_er .= "$sql <br>$total<br>";
		}

/**-Exclusão de os, reembolsando a Black(descontar no extrato) da peça enviada na OS EXCLUIDA.
  * Pedido em garantia = servico_realizado 62.
  * Na OS buscar por peças que estão com o servico realizado : troca de peça gerando pedido 
  * Criar uma OS SEDEX como Débito para o posto no mesmo extrato
  * Criar um registro no tbl_extrato_lancamento com valor negativo
  * 
  * Posto origem : PA
  * Posto destino: Black
  *
  * Apenas para a Black&Decker: verificar o lancamento(41 soh black) na tbl_extrato_lancamento.
**/
		if(strlen($msg_erro) == 0 AND ($login_fabrica == 1 OR $login_fabrica == 10)){
			$sql = "SELECT  SUM(tbl_os_item.custo_peca * tbl_os_item.qtde) AS total
					FROM tbl_os_produto 
					JOIN tbl_os_item           USING(os_produto)
					JOIN tbl_servico_realizado USING(servico_realizado)
					WHERE tbl_os_produto.os             = $x_os 
					AND   tbl_servico_realizado.fabrica = $login_fabrica
					AND   tbl_servico_realizado.troca_de_peca IS TRUE
					AND   tbl_servico_realizado.gera_pedido   IS TRUE;";
	$msg_er .= "$sql <br>$total<br>";
			$res = pg_exec($con,$sql);//somatoria de todas as peças que há troca de peça gerando pedido.
			$total = pg_result($res,0,total);
			if(strlen($msg_erro) == 0 AND $total > 0){

				$sql = "SELECT sua_os FROM tbl_os WHERE os = $x_os;";
				$res = pg_exec($con,$sql);
				$sedex_sua_os     = trim(pg_result($res,0,sua_os));

				$sql = "SELECT posto, protocolo 
						FROM tbl_extrato 
						WHERE extrato = $extrato
						AND   fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);	//busca o posto para ser inserido no posto origem
											//busca o protocolo para enviar e-mail.
				$posto_destino    = pg_result($res,0,posto);
				$sedex_protocolo = pg_result($res,0,protocolo);
	$msg_er .= "$sql <br>total: $total<br>";
				$sql = "INSERT INTO tbl_os_sedex(
										fabrica          ,
										posto_destino    ,
										posto_origem     ,
										sua_os_destino   ,
										extrato          ,
										total_pecas      ,
										total            ,
										finalizada       ,
										obs              ,
										admin            ,
										data             ,
										extrato_destino
								) VALUES (
										$login_fabrica   ,
										$posto_destino   ,
										'6900'           ,
										'$sedex_sua_os'  ,
										$extrato         ,
										$total           ,
										$total           ,
										current_timestamp,
										'$x_obs'         ,
										'$login_admin'   ,
										current_date     ,
										$extrato
						);";
				$res = pg_exec($con,$sql);//insere uma OS SEDEX no extrato atual.
				$msg_erro = pg_errormessage($con);
	$msg_er .= "$sql <br>total: $total<br>";

				$sql = "SELECT os_sedex FROM tbl_os_sedex 
						WHERE extrato       = $extrato
						AND   fabrica       = $login_fabrica
						AND   posto_destino = $posto_destino;";
				$res = pg_exec($con,$sql);//busca a os_sedex que foi cadastrada.
				$os_sedex = pg_result($res,0,os_sedex);
	$msg_er .= "$sql <br>$total<br>";
				
				$total_neg = $total * (-1);
				$sql = "INSERT INTO tbl_extrato_lancamento (
											fabrica   ,
											posto     ,
											extrato   ,
											automatico,
											lancamento,
											valor     ,
											os_sedex
									) VALUES (
											$login_fabrica   ,
											$posto_destino   ,
											$extrato         ,
											't'              ,
											'41'             ,
											$total_neg       ,
											$os_sedex
									);";
				$res = pg_exec($con,$sql);	//insere um lancamento com valor NEGATIVO. Valor das pecas multiplicaod por -1.
											//insere para a black o status 41.
	$msg_er .= "$sql <br>$total<br>";


				$sql = "UPDATE tbl_os_status SET os_sedex = '$os_sedex'
						WHERE extrato = $extrato 
						AND   os = $x_os 
						AND   status_os = 15 ;";

				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato 
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica ;";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
$msg_er .= "$sql <br>$total<br>";
		if (pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){

			#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
			#$res = pg_exec($con,$sql);
			#$total_os_extrato = pg_result($res,0,0);
			#HD15716
			if($login_fabrica == 11){
				$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
			}else{
				$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link&msg_aviso=$msg_aviso");
		exit;
	}else{
	$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
//$msg_erro = $msg_er;
}


if ($btn_acao == "recusar" OR $btn_acao == 'recusar_documento') {
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

		#OS_TROCA - Excluida/Acumulada/Recusada o débito HD14648
		if(strlen($msg_erro) == 0 AND $login_fabrica == 1){
			$sql = "SELECT tbl_os_troca.os_troca    ,
							tbl_os_troca.total_troca,
							tbl_os.os               ,
							tbl_os.sua_os
						FROM tbl_os 
						JOIN tbl_os_troca USING(os) 
						WHERE os = $x_os";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0){
				$sua_os_troca   = pg_result($res,0,sua_os);
				$os_sedex_troca = '';
				#troca
				$sql = "SELECT os_sedex 
							FROM tbl_os_sedex 
							WHERE extrato_destino = $extrato 
							AND sua_os_destino = '$sua_os_troca'; ";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res) > 0){
					$os_sedex_troca = pg_result($res, 0,os_sedex);
					#Sedex
					$sql = "DELETE FROM tbl_os_sedex WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "SELECT extrato_lancamento 
								FROM tbl_extrato_lancamento
								WHERE extrato = $extrato
								AND   os_sedex = $os_sedex_troca;";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res) > 0 AND strlen($msg_erro) == 0){
						$extrato_lancamento_troca = pg_result($res,0,extrato_lancamento);
						#extrato lançamento
						$sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}


		if (strlen($msg_erro) == 0) {

			if($btn_acao == 'recusar_documento'){
				$sql_doc = "SELECT finalizada, data_fechamento FROM tbl_os WHERE os = $x_os LIMIT 1;";
				$res_doc = pg_exec($con,$sql_doc);
				$msg_erro = pg_errormessage($con);

				$doc_finalizada      = pg_result($res_doc,0,finalizada);
				$doc_data_fechamento = pg_result($res_doc,0,data_fechamento);
			}

			$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $x_os, '$x_obs');";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			$sql = "SELECT fn_estoque_recusa_os($x_os,$login_fabrica,$login_admin);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			if($btn_acao == 'recusar_documento' AND $login_fabrica == 1){
				$sql_doc2 = "SELECT os_status FROM tbl_os_status WHERE os = $x_os ORDER BY os_status DESC LIMIT 1;";
				$res_doc2 = pg_exec($con,$sql_doc2);
				$msg_erro = pg_errormessage($con);

				$doc_os_status    = pg_result($res_doc2,0,os_status);

				$sql_doc = "UPDATE tbl_os_status SET status_os = 91 WHERE os = $x_os AND os_status = $doc_os_status;";
				$res_doc = pg_exec($con,$sql_doc);
				$msg_erro = pg_errormessage($con);
				
/*				$sql_doc = "UPDATE tbl_os SET finalizada = '$doc_finalizada', data_fechamento = '$doc_data_fechamento' WHERE os = $x_os;";
				$res_doc = pg_exec($con,$sql_doc);
				$msg_erro = pg_errormessage($con);
*/			}
		}
	}
/*
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato 
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_numrows($res) > 0) {
			if (@pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
				$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}
*/
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link&msg_aviso=$msg_aviso");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}


if($login_fabrica == 11){//Recusas de OS´s
	if (strtoupper($select_acao) <> "RECUSAR" AND strtoupper($select_acao) <> "EXCLUIR" AND strtoupper($select_acao) <> "ACUMULAR" AND strlen($select_acao) > 0) {
		$os     = $_POST["os"];
		$sua_os = $_POST["sua_os"];

		$kk = 0;

		$res = pg_exec($con,"BEGIN TRANSACTION");

		$sql         = "SELECT motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
		$res         = pg_exec($con, $sql);
		$select_acao = pg_result($res,0,motivo);
		$status_os   = pg_result($res,0,status_os);

		if(strlen($status_os) == 0){
			$msg_erro = "Escolha o motivo da Recusa da OS";
		}


		if($status_os == 13 OR $status_os == 14 AND strlen($msg_erro) == 0){

			for ($k = 0 ; $k < $contador ; $k++) {
				if (strlen($msg_erro) > 0) {
					$os[$k]     = $_POST["os_" . $kk];
					$sua_os[$k] = $_POST["sua_os_" . $kk];
				}

				if (strlen($os[$k]) > 0) {
//					echo "<input type='hidden' name='os_$kk' value='" . $os[$k] . "'></td>\n";
					$select_acao = RemoveAcentos($select_acao);
					$select_acao = strtoupper($select_acao);
					$kk++;
				
					if (strlen($msg_erro) == 0) {
						if($status_os == 13){
							$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
						}else{
							$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
						}
		//		echo "<br> $sql<br>";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
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
						//hd 10185 - trocado calcula por totaliza
						//$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
						
						#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
						#$res = pg_exec($con,$sql);
						#$total_os_extrato = pg_result($res,0,0);
						#HD15716
						if($login_fabrica == 11){
							$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
							$res = pg_exec($con,$sql);
							$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
						}else{
							$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
							$res = @pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
						}
					}
				}
			}
			
			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
				$link = $_COOKIE["link"];
				header ("Location: $link&msg_aviso=$msg_aviso");
				exit;

			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
			$select_acao = '';
		}

		$kk = 0;

		if($status_os == 15 AND strlen($msg_erro) == 0){

			$res = pg_exec($con,"BEGIN TRANSACTION");

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
						$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
						$res = pg_exec($con, $sql);


					}
				}
			}
			if (strlen($msg_erro) == 0) {
				$sql = "SELECT posto
						FROM   tbl_extrato 
						WHERE  extrato = $extrato
						AND    fabrica = $login_fabrica";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){

					//hd 10185 - trocado calcula por totaliza
					//$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
					
					#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
					#$res = pg_exec($con,$sql);
					#$total_os_extrato = pg_result($res,0,0);
					#HD15716
					if($login_fabrica == 11){
						$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
						$res = pg_exec($con,$sql);
						$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
					}else{
						$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
				$link = $_COOKIE["link"];
				header ("Location: $link&msg_aviso=$msg_aviso");
				exit;
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
			$select_acao = '';
		}
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

		#OS_TROCA - Excluida/Acumulada/Recusada o débito HD14648
		if(strlen($msg_erro) == 0 AND $login_fabrica == 1){
			$sql = "SELECT tbl_os_troca.os_troca    ,
							tbl_os_troca.total_troca,
							tbl_os.os               ,
							tbl_os.sua_os
						FROM tbl_os
						JOIN tbl_os_troca USING(os) 
						WHERE os = $x_os";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0){
				$sua_os_troca   = pg_result($res,0,sua_os);
				$os_sedex_troca = '';
				#troca
				$sql = "SELECT os_sedex 
							FROM tbl_os_sedex 
							WHERE extrato_destino = $extrato 
							AND sua_os_destino = '$sua_os_troca'; ";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res) > 0){
					$os_sedex_troca = pg_result($res, 0,os_sedex);
					#Sedex
					$sql = "UPDATE tbl_os_sedex SET extrato_destino = NULL WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "SELECT extrato_lancamento 
								FROM tbl_extrato_lancamento
								WHERE extrato = $extrato
								AND   os_sedex = $os_sedex_troca;";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res) > 0 AND strlen($msg_erro) == 0){
						$extrato_lancamento_troca = pg_result($res,0,extrato_lancamento);
						#Extrato lançamento
						$sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $x_os, '$x_obs');";
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
				
				#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
				#$res = pg_exec($con,$sql);
				#$total_os_extrato = pg_result($res,0,0);
				#HD15716
				if($login_fabrica == 11){
					$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
					$res = pg_exec($con,$sql);
					$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
				}else{
					$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
					//retirado por Sono e Samuel pois não há necessidade de atribuir os valores novamente as OSs
					//$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link&msg_aviso=$msg_aviso");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "acumulartudo") {
	if (strlen($extrato) > 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

//if($ip == '201.0.9.216') echo $extrato."<br>";
		$sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$link = $_COOKIE["link"];
			header ("Location: $link&msg_aviso=$msg_aviso");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}


/**--------ADICIONA OS NO EXTRATO------------
  *  Só aparece para a fabrica quando o extrato nao foi dado baixa.
  *  Verifica em tbl_os_status e limpar qualquer registro encontrado diferente de 58 = historico da OS
  *  verificar na tbl_os_extra se a os está em algum extrato, se estiver:
  *		copia o numero do extrato atual
  *  Atualiza o historico de movimentacao da OS entre extrato, caso seja a primeira vez
  * é criado um registro na tabela tbl_os_status com o status_os = 58
  *  Busca por registro na tbl_os_status diferente de 58 e apaga.
  *  Atualiza os os extratos:
  *		recalcula o extrato anterior
  *		recalcula o extrato atual
***/

if(strlen($adiciona_sua_os) > 0 AND ($login_fabrica == 6 OR $login_fabrica == 10 OR $login_fabrica == 11)) {

	$adiciona_sua_os        = trim($adiciona_sua_os);//pega a sua_os digitada pelo admin

	$sql = "SELECT posto 
				FROM tbl_extrato 
			WHERE extrato = '$extrato'
			AND fabrica = '$login_fabrica' ";

	$res = pg_exec($con,$sql);// Atraves do extrato, busca-se o posto
	$adiciona_posto = pg_result($res,0,0);//joga o posto na variavel

	if(strlen($adiciona_posto) > 0 AND strlen($msg_erro) == 0){
		$sql = "SELECT DISTINCT os
				FROM tbl_os 
				WHERE UPPER(sua_os) = UPPER('$adiciona_sua_os') 
				AND fabrica  = '$login_fabrica' 
				AND posto    = '$adiciona_posto' ";

		$res             = pg_exec($con,$sql);// Se encontrou posto procura pela OS do posto atraves da SUA_OS.
		$adiciona_os     = @pg_result($res,0,0);

		if(strlen($adiciona_os) > 0 ){

			$sql2 = "SELECT data_fechamento,extrato FROM tbl_os JOIN tbl_os_extra using(os) WHERE os = '$adiciona_os' ";
			$res2 = @pg_exec($con,$sql2);
			$adiciona_fechamento  = @pg_result($res2,0,data_fechamento);//busca a data de fechamento
			$adiciona_extrato_ant = @pg_result($res2,0,extrato);//busca o extrato caso ja esteja em um extrato

			$sql2 = "SELECT extrato 
					FROM tbl_extrato_pagamento 
					WHERE extrato = '$adiciona_extrato_ant' ";
			$res2 = @pg_exec($con,$sql2);// Verifica se o extrato ja foi dado baixa
			$adiciona_baixado = @pg_result($res2,0,0);//adiciona o extrato anterior

			if(strlen($adiciona_baixado) > 0 and ($login_fabrica == 6 or $login_fabrica == 10)) {//Verifica se o extrato ja foi dado baixa
				$msg_erro = 'O extrato desta OS já foi dado baixa';
			}

			$sql3 = "SELECT extrato 
					FROM tbl_extrato
					WHERE extrato = '$adiciona_extrato_ant' 
					AND liberado IS NOT NULL";
			$res3 = @pg_exec($con,$sql3);// Verifica se o extrato ja foi liberado
			$adiciona_liberado = @pg_result($res3,0,0);//adiciona o extrato anterior

			if(strlen($adiciona_liberado) > 0 and $login_fabrica == 11) {//Verifica se o extrato ja foi liberado
				$msg_erro = 'O extrato desta OS já foi liberado';
			}

			if(strlen($adiciona_fechamento) == 0){//verifica se a OS esta fechada
				$msg_erro = 'A OS está aberta. Deve-se estar fechada para entrar no extrato.<br> ';
			}

			if($adiciona_extrato_ant == $extrato){//se estava no extrato anterior
				$msg_erro = " A OS já faz está neste extrato ";
			}
			
			if( pg_numrows($res) == 1 AND strlen($msg_erro) == 0 ){

				$res = pg_exec ($con,"BEGIN TRANSACTION");

				$sql = "SELECT os_status
							FROM tbl_os_status 
						WHERE os    = '$adiciona_os' 
						AND extrato = '$extrato' ";
				
				$res = @pg_exec($con,$sql);//Se encontrou a OS verifica se a OS ja foi recusada/excludia/acumulada de algum extrato

				$adiciona_status = @pg_numrows($res);

				if($adiciona_status > 0){
					$sql = "DELETE FROM tbl_os_status 
							WHERE os    = '$adiciona_os' 
							AND extrato = '$extrato' 
							AND status_os <> '58' ";
					$res = pg_exec($con,$sql);//Caso encontre algum registro ele deleta o registro
				}//58 é os_status historio da OS nas movimentacoes entre extratos

				

				$sql = "SELECT extrato FROM tbl_os_extra WHERE os = '$adiciona_os' ";
				$res = @pg_exec($con,$sql);//Busca se a OS participa de algum extrato

				if(@pg_numrows($res) > 0){
					$extrato_anterior = pg_result($res,0,0);//caso ja faça parte de um extrato
				}

				$sql = "SELECT os_status, observacao FROM tbl_os_status 
						WHERE os = $adiciona_os 
						AND   status_os = 58 ";
				$res = pg_exec($con,$sql);	//caso haja historico da movimentacao entre extratos,
											//copia para concatenar com a nova movimentacao

				if(pg_numrows($res) > 0 ){//caso haja da um update na tabela os_status
					$adiciona_observacao = pg_result($res,0,observacao);
					$adiciona_os_status  = pg_result($res,0,os_status);

					$adiciona_observacao .= " Saiu do extrato [ $extrato_anterior ] entrou no extrato [ $extrato ]. ";
					
					$sql2 = "UPDATE tbl_os_status SET observacao = '$adiciona_observacao'
							 WHERE os_status = '$adiciona_os_status'
							 AND   status_os = '58' ";
					$res2 = pg_exec($con,$sql2);

				}else{//caso nao encontre adiciona o registro na tabela
					$observacao = "Saiu do extrato [ ".$extrato_anterior." ], entrou no extrato [ ".$extrato." ].";
					
					$sql = "INSERT INTO tbl_os_status ( os               ,
														status_os        ,
														data             ,
														observacao       ,
														extrato          ,
														admin
													) VALUES (
														$adiciona_os     ,
														'58'             ,
														current_timestamp,
														'$observacao'     ,
														$extrato         ,
														$login_admin
													);";
					$res = pg_exec($con,$sql);
				}

				$sql = " UPDATE tbl_os_extra set extrato = $extrato WHERE os = $adiciona_os ";

				$res = pg_exec($con,$sql);  //Coloca o novo extrato na OS_EXTRA

				if(strlen($extrato) > 0 AND strlen($msg_erro) == 0 ){
					if(strlen($extrato_anterior) > 0){
						## AGENDAMENTO DE RECALCULO DE EXTRATO ##
						
						#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato_anterior AND fabrica = $login_fabrica;";
						#$res = pg_exec($con,$sql);
						#$total_os_extrato = pg_result($res,0,0);
						#HD15716
						if($login_fabrica == 11){
							$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato_anterior AND fabrica = $login_fabrica;";
							$res = pg_exec($con,$sql);
							$msg_aviso = "Foi agendado o recalculo do extrato $extrato_anterior para esta noite!<br>";
						}else{
							$sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato_anterior');";
							$res = @pg_exec ($con,$sql);//Recalcula o extrato anterior, caso exista
							$msg_erro = pg_errormessage($con);
						}
					}

					#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
					#$res = pg_exec($con,$sql);
					#$total_os_extrato = pg_result($res,0,0);
					#HD15716
					if($login_fabrica == 11){
						$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
						$res = pg_exec($con,$sql);
						$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
					}else{
						$sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato');";
						$res = @pg_exec ($con,$sql);//Recalcula o extrato atual
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$res = pg_exec($con,"COMMIT TRANSACTION");
						$link = $_COOKIE["link"];
						header ("Location: $link&msg_aviso=$msg_aviso");
//						header ("Location: extrato_consulta_os.php?extrato=$extrato");
						exit;//Recarrega a página
					}else{
						$res = pg_exec($con,"ROLLBACK TRANSACTION");
					}	//caso ocorra erro rollback;
				}else{
						$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			}else{
				if(strlen($msg_erro) == 0){
					$msg_erro = " Não foi possivel encontrar a OS";
				}
			}
		}else{
			$msg_erro = " OS não encontrada. ";
		}
	}else{
		$msg_erro = " Não foi possível localizar o posto";
	}
}


/* para recusar uma os sedex deve-se:
	tirar o finalizada da os_sedex, setar null;
	Criar uma os_status(13) para informar o motivo da recusa;
	Deletar o extrato_lancamento;
*/
$recusa_sedex = $_POST['recusa_sedex'];
if(($login_fabrica == 10 OR $login_fabrica == 1) AND strlen($recusa_sedex) > 0){
	
	$obs                = $_POST['descricao'];
	$os_sedex           = $_POST['os_sedex'];
	$extrato_lancamento = $_POST['extrato_lancamento'];

	if(strlen($obs) > 0 AND strlen($os_sedex) > 0 AND strlen($extrato_lancamento) > 0){
		
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_os_sedex set finalizada = null 
					WHERE os_sedex = $os_sedex 
					AND fabrica = $login_fabrica; ";
		$res = pg_exec($con,$sql);

		$msg_erro = pg_errormessage($con);

		$sql = "INSERT INTO tbl_os_status (
				os_sedex, status_os, observacao, extrato, admin
			) VALUES (
				$os_sedex, '13', '$obs', $extrato, $login_admin
			);";
		$res = pg_exec($con,$sql);
	
		$msg_erro = pg_errormessage($con);

		$sql = "DELETE FROM tbl_extrato_lancamento 
				WHERE os_sedex = $os_sedex 
				AND   extrato_lancamento = $extrato_lancamento; ";
		$res = pg_exec($con,$sql);

		$msg_erro = pg_errormessage($con);

		$sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato');";
		$res = @pg_exec ($con,$sql);//Recalcula o extrato anterior, caso exista
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro) == 0){
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$corpo.="<br>Status: Correto";//e-mail para fernando
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
			$corpo.="<br>Status: Verificar";//e-mail para fernando
		}
	}else{
		$msg_erro = "Não é possível realizar a recusa da OS SEDEX";
	}
}





$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços";
include "cabecalho.php";


//hd 15622
//if ($login_fabrica==11) {
//	echo "<BR><BR><BR><BR><BR><CENTER>Programa em manutenção, aguarde alguns instantes.</CENTER>";
//	exit;
//}

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
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
</style>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#data_pagamento").maskedinput("99/99/9999");
	});
</script>


<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

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

/*substituido.
function fnc_pesquisa_os (sua_os, adiciona_posto, adiciona_data_abertura, adiciona_extrato) {
	url = "pesquisa_os_fer.php?sua_os=" + sua_os.value + "&posto=" + adiciona_posto.value + "&extrato=" + adiciona_extrato.value ;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=200,top=18,left=0");
	janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
	janela.sua_os                   = sua_os;
	janela.adiciona_data_abertura   = adiciona_data_abertura;
	janela.adiciona_extrato         = adiciona_extrato;
	janela.focus();
}
*/

function fnc_pesquisa_os (adiciona_sua_os, adiciona_posto, adiciona_data_abertura, adiciona_extrato) {
	var url = "";
	url = "pesquisa_adiciona_os.php?forma=reload&adiciona_sua_os=" + adiciona_sua_os.value + "&posto=" + adiciona_posto.value + "&extrato=" + adiciona_extrato.value ;
	janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=450, height=250, top=0, left=0");
	janela.adiciona_sua_os  = adiciona_sua_os;
	janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
	janela.focus();
}
</script>

<SCRIPT LANGUAGE="JavaScript">
<!--
function Recusa(os_sedex_extrato,os_sedex_sedex){
	if (confirm('Deseja realmente recusar essa OS SEDEX?') == true){
		window.location = "<? echo $PHP_SELF; ?>?extrato=" + os_sedex_extrato + "&os_sedex_sedex=" + os_sedex_sedex;
	}
}
//-->
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
var http3 = new Array();
function gravaAutorizao(){
	var extrato_estoque = document.getElementById('extrato_estoque');
	var autorizacao_texto = document.getElementById('autorizacao_texto');
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "<? echo $PHP_SELF;?>?ajax=gravar&extrato="+extrato_estoque.value+"&observacao="+autorizacao_texto.value;
	http3[curDateTime].open('get',url);

	var campo = document.getElementById('div_estoque');

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){

				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);


}


</SCRIPT>

<?
function RemoveAcentos($Msg) 
{
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

<!--aqui-->
<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ;
		$msg_erro = '';
		?>
		
	</td>
</tr>
</table>
<?
}
if (strlen ($msg_aviso) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_aviso ;
		$msg_aviso = '';
		?>
		
	</td>
</tr>
</table>
<?
}
?>
<!--aqui-->

<?

echo "<FORM METHOD=POST NAME='frm_extrato_os' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='extrato' value='$extrato'>";
echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
echo "<input type='hidden' name='btn_acao' value=''>";
?>

<?


$join_log="";
$case_log=" ";
/*PARA CONSULTAR O LOG DAS OSs FORA DE GARANTIA*/
if($login_fabrica == 11){
	$case_log=" case when tbl_os_log.os_atual is not null
					then 1
					else 0
				end as log, 
				os_atual as os_log,";
	$join_log=" LEFT JOIN tbl_os_log on tbl_os.os = tbl_os_log.os_atual ";
	$group_log= " os_atual,";
}


/*
Verifica se a ação é "RECUSAR" ou "ACUMULAR"
para somente mostrar a tela para a digitação da observação.
*/
if (strlen($select_acao) == 0) {

$sql = "SELECT      lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
					tbl_os.os                                                                       ,
					tbl_os.sua_os                                                                   ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data_digitacao  ,
					to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
					to_char (tbl_os.data_fechamento ,'DD/MM/YYYY')               AS data_fechamento ,
					to_char (tbl_os.data_nf ,'DD/MM/YYYY')                       AS data_nf ,
					tbl_os.consumidor_revenda                                                       ,
					tbl_os.serie                                                                    ,
					tbl_os.codigo_fabricacao                                                        ,
					tbl_os.consumidor_nome                                                          ,
					tbl_os.consumidor_fone                                                          ,
					tbl_os.revenda_nome                                                             ,
					(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS total_pecas  ,
					tbl_os.mao_de_obra                                           AS total_mo        ,
					tbl_os.cortesia                                                                 ,
					tbl_os.nota_fiscal                                                              ,
					tbl_os.posto                                                                    ,
					tbl_produto.referencia                                                          ,
					tbl_produto.descricao                                                           ,
					tbl_produto.mao_de_obra                                  AS  mao_de_obra_produto,
					tbl_os_extra.extrato                                                            ,
					tbl_os_extra.os_reincidente                                                     ,
					tbl_os.observacao                                                               ,
					tbl_os.motivo_atraso                                                            ,
					tbl_os_extra.motivo_atraso2                                                     ,
					tbl_os.obs_reincidencia                                                         ,
					to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
					tbl_extrato.total                                            AS total           ,
					tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
					tbl_extrato.pecas                                            AS pecas           ,
					lpad (tbl_extrato.protocolo,5,'0')                           AS protocolo       ,
					tbl_posto.nome                                               AS nome_posto      ,
					tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
					tbl_extrato_pagamento.valor_total                                               ,
					tbl_extrato_pagamento.acrescimo                                                 ,
					tbl_extrato_pagamento.desconto                                                  ,
					tbl_extrato_pagamento.valor_liquido                                             ,
					tbl_extrato_pagamento.nf_autorizacao                                            ,
					to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
					to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
					tbl_extrato_pagamento.autorizacao_pagto                                         ,
					tbl_extrato_pagamento.obs                                                       ,
					tbl_extrato_pagamento.extrato_pagamento                                         ,
					(SELECT COUNT(*) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.custo_peca = 0 AND tbl_servico_realizado.troca_de_peca IS TRUE) AS peca_sem_preco,
					(SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os and peca_sem_estoque is true limit 1) AS peca_sem_estoque ,
					$case_log
					tbl_os.data_fechamento - tbl_os.data_abertura  as intervalo
		FROM        tbl_extrato
		LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
		LEFT JOIN tbl_os_extra          ON  tbl_os_extra.extrato           = tbl_extrato.extrato
		LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
		$join_log
		JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
		JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_os.posto
		JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica      = $login_fabrica
		WHERE		tbl_extrato.fabrica = $login_fabrica
		AND         tbl_extrato.extrato = $extrato ";

if($login_fabrica <> 2){
	$sql .= "ORDER BY    tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0)               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,0),'-','') ASC";
}

//echo $sql; exit;
if ($login_fabrica == 1){
	// sem paginacao
	//if ($ip == '200.228.76.93') echo "<br>$sql<br>";
	$res = pg_exec($con,$sql);
}else{
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //
}

$ja_baixado = false ;
$rr = 0;
$reincidencias_os = array();

if (@pg_numrows($res) == 0) {
	echo "<h1>Nenhum resultado encontrado.</h1>";
}else{
	?>
	<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td bgcolor="#FFCCCC">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>REINCIDÊNCIAS</b></td>
	</tr>
	<? if ($login_fabrica == 1) { ?>
	<tr><td height="3"></td></tr>
	<tr>
		<td bgcolor="#D7FFE1">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>OS CORTESIA</b></td>
	</tr>
	<tr><td height="3"></td></tr>
	<tr>
		<td bgcolor="#d9ce94">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>Troca de peça sem estoque</b></td>
	</tr>
	<tr><td height="3"></td></tr>
	<tr>
		<td bgcolor="#FFCC00">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>Pendência de Documentação</b></td>
	</tr>
	<? } ?>
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
		$posto             = pg_result ($res,0,posto) ;
		$protocolo         = pg_result ($res,0,protocolo) ;
		$peca_sem_preco    = pg_result ($res,0,peca_sem_preco) ;
	}
    //echo $extrato_pagamento; echo strlen ($extrato_pagamento);
	//$ja_baixado = true;
	if (strlen ($extrato_pagamento) > 0) $ja_baixado = true ;

	#Esta tela é para consulta, ou seja, nao deixar alterar
	$ja_baixado = true;
	
	$sql = "SELECT count(*) as qtde
			FROM   tbl_os_extra
			WHERE  tbl_os_extra.extrato = $extrato";
	$resx = pg_exec($con,$sql);
	
	if (pg_numrows($resx) > 0) $qtde_os = pg_result($resx,0,qtde);
	
	echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0'>";
	
	echo"<TR class='menu_top'>";
	echo"<TD align='left'> Extrato: ";
	if ($login_fabrica == 1) echo $protocolo;
	else                     echo $extrato;
	echo "</TD>";
	echo "<TD align='left'> Data: " . pg_result ($res,0,data_geracao) . "</TD>";
	echo"<TD align='left'> Qtde de OS: ". $qtde_os ."</TD>";
	echo"<TD align='left'> Total: R$ " . number_format(pg_result ($res,0,total),2,",",".") . "</TD>";
	echo"</TR>";

	echo"<TR class='menu_top'>";
	echo"<TD align='left'> Código: " . pg_result ($res,0,codigo_posto) . " </TD>";
	echo"<TD align='left' colspan='3'> Posto: " . pg_result ($res,0,nome_posto) . "  </TD>";
	echo"</TR>";
	echo"</TABLE>";
	echo"<br>";

	if ($login_fabrica <> 6) {
		$sql = "SELECT  count(*) as qtde,
						tbl_linha.nome
				FROM   tbl_os
				JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
				JOIN   tbl_produto   ON tbl_produto.produto = tbl_os.produto
				JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
									AND tbl_linha.fabrica   = $login_fabrica
				WHERE  tbl_os_extra.extrato = $extrato
				GROUP BY tbl_linha.nome
				ORDER BY count(*)";
		$resx = pg_exec($con,$sql);
		
		if (pg_numrows($resx) > 0) {
			echo "<TABLE width='50%' border='0' align='center' cellspacing='1' cellpadding='0'>";
			echo "<TR class='menu_top'>";
			
			echo "<TD align='left'>LINHA</TD>";
			echo "<TD align='center'>QTDE OS</TD>";
			
			echo "</TR>";
			
			for ($i = 0 ; $i < pg_numrows($resx) ; $i++) {
				$linha = trim(pg_result($resx,$i,nome));
				$qtde  = trim(pg_result($resx,$i,qtde));
				
				echo "<TR class='menu_top'>";
				
				echo "<TD align='left'>$linha</TD>";
				echo "<TD align='center'>$qtde</TD>";
				
				echo "</TR>";
			}
			
			echo "</TABLE>";
			echo"<br>";
		}
	}
	
/**
 * Inclusão de OS em um extrato
 * 
 * Caso o extrato ja tenha sido liberado, não pode mais dar manutenção no extrato.
 * 
**/

$sql2 = "SELECT  liberado 
		FROM tbl_extrato 
		WHERE extrato = $extrato 
		AND   fabrica = $login_fabrica";
$res2 = pg_exec($con,$sql2);
$liberado = pg_result($res2,0,0);

//strlen($liberado) == 0 AND (

if($ja_baixado == false AND ($login_fabrica == 10 OR ($login_fabrica == 6 AND strlen($liberado)==0) OR ($login_fabrica==11 AND strlen($liberado)==0)) ) {
	echo "<table border='0' align='center' width='300' cellspancing='0' cellpadding='0'>";

	echo "<tr bgcolor='#D9E9FD'>";
		echo "<td colspan='2' bgcolor='#D9E9FD' style='font-family: verdana; font-size: 10px;'><br><B>OS para ser adicionada neste extrato</B><br>&nbsp;</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td><INPUT TYPE='text' NAME='adiciona_sua_os' size='10' value='$_adiciona_sua_os'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_os (document.frm_extrato_os.adiciona_sua_os, document.frm_extrato_os.adiciona_posto, document.frm_extrato_os.adiciona_data_abertura, document.frm_extrato_os.adiciona_extrato)' style='cursor: pointer'></td>";
		echo "<INPUT TYPE='hidden' NAME='adiciona_posto' size='10' value='$codigo_posto'>";
		echo "<INPUT TYPE='hidden' NAME='adiciona_extrato' size='10' value='$extrato'>";
		echo "<td><INPUT TYPE='hidden' NAME='adiciona_data_abertura' size='10' value='$adiciona_data_abertura'></td>";
	echo "</tr>";

	echo "</table>";

	echo "<br><br>";
}


	if ($login_fabrica <> 1 and $login_fabrica <> 3){
		$sql = "SELECT pedido
				FROM tbl_pedido
				WHERE pedido_kit_extrato = $extrato
				AND   fabrica            = $login_fabrica";
		$resE = pg_exec($con,$sql);
		if (pg_numrows($resE) == 0)
			echo "<img src='imagens/btn_pedidopecaskit.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedido de Peças do Kit' border='0' style='cursor:pointer;'>";
		echo "<br>";
		echo "<br>";
	}
	$wwsql = " SELECT pedido_faturado
				FROM tbl_posto_fabrica
				JOIN tbl_extrato on tbl_posto_fabrica.posto = tbl_extrato.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_extrato.extrato       = $extrato";
	$wwres = pg_exec($con,$wwsql);
	$pedido_faturado = pg_result($wwres,0,0);

	if ($login_fabrica == 1) {
		echo "<img border='0' src='imagens/btn_acumulartodoextrato.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='acumulartudo'; document.frm_extrato_os.submit();\" alt='Clique aqui p/ acumular todas OSs deste Extrato' style='cursor: hand;'><br><br>";
		
if($pedido_faturado=="t"){
		echo "<div id='div_estoque' style='display:block; Position:relative;width:450px;'>";
		
		echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px' width='350'>";
		echo "<tr>";
		echo "<td align='center'><b><font color='#FFFFFF'>Acerto de peças do estoque</FONT></b></td>";
		echo "</tr>";	
		echo "<tr>";
		echo "<td align='center' bgcolor='#efeeea'><b>Atenção</b><BR>";
		echo "Para ACEITAR todas as peças que o posto utilizou do <BR>estoque informe o motivo e clique em continuar.<BR>";
		echo "<TEXTAREA NAME='autorizacao_texto' ID='autorizacao_texto' ROWS='5' COLS='40' class='textarea'></TEXTAREA>";
		echo "<input type='hidden' name='extrato_estoque' id='extrato_estoque' value='$extrato'>";
		echo "<BR><BR><img src='imagens_admin/btn_confirmar.gif' border='0' style='cursor:pointer;' onClick='gravaAutorizao();'></td>";
		echo "</tr>";	
		echo "</table><BR>";
		
		echo "</div>";
}
	}

	echo "<TABLE width='750' border='0' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	if (strlen($msg) > 0) {
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan=10>$msg</TD>\n";
		echo "</TR>\n";
	}

	echo "<TR class='menu_top'>\n";
	if (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)) echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></TD>\n";
	echo "<TD width='075' >OS</TD>\n";
	if ($login_fabrica == 1) echo "<TD width='075'>CÓD. FABR.</TD>\n";
	echo "<TD width='075'>SÉRIE</TD>\n";
	if ($login_fabrica == 3) echo "<TD width='075'>NF. COMPRA</TD>\n";
	if ($login_fabrica == 3) echo "<TD width='075'>DIGITAÇÃO</TD>\n";
	if ($login_fabrica <> 1) echo "<TD width='075'>ABERTURA</TD>\n";
	if ($login_fabrica == 3) echo "<TD width='075'>FECHAMENTO</TD>\n";
	if ($login_fabrica == 6) echo "<TD width='50'><ACRONYM TITLE=\"Qtde de dias que a OS ficou aberta\">DIAS</ACRONYM></TD>\n";
	echo "<TD width='130'>CONSUMIDOR</TD>\n";
	echo "<TD width='130'>PRODUTO</TD>\n";
	if($login_fabrica == 11){
		//echo "<TD width='130'>LOG-GARANTIA</TD>\n";
	}
	if ($login_fabrica == 3) echo "<TD width='075'>M.O. PRODUTO</TD>\n";
	if ($login_fabrica == 1) {
		echo "<TD width='100'>REVENDA</TD>\n";
		echo "<TD width='130'>TOTAL PEÇA</TD>\n";
		echo "<TD width='130'>TOTAL MO</TD>\n";
		echo "<TD width='130'>PEÇA + MO</TD>\n";
	}
	if ($login_fabrica ==6 or $login_fabrica==24){
		echo "<TD width='130'>TOTAL MO</TD>\n";
	}
	if ($login_fabrica ==6 AND strlen($liberado)==0){
		echo "<TD width='130'>Ação</TD>\n";
	}
	echo "</TR>\n";

	if($login_fabrica == 1 ){
		// monta array para ver duplicidade
		$busca_array     = array();
		$localizou_array = array();
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$nota_fiscal   = trim(pg_result($res,$x,nota_fiscal));
			if (in_array($nota_fiscal, $busca_array)) {
				$localizou_array[] = $nota_fiscal;
			}
			$busca_array[] = $nota_fiscal;
		}
	}


	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os                 = trim(pg_result ($res,$i,os));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$data_digitacao     = trim(pg_result ($res,$i,data_digitacao));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$data_nf            = trim(pg_result ($res,$i,data_nf));
		$serie              = trim(pg_result ($res,$i,serie));
		$codigo_fabricacao  = trim(pg_result ($res,$i,codigo_fabricacao));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$consumidor_fone    = trim(pg_result ($res,$i,consumidor_fone));
		$revenda_nome       = trim(pg_result ($res,$i,revenda_nome));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$mao_de_obra_produto= trim(pg_result ($res,$i,mao_de_obra_produto));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$os_reincidente     = trim(pg_result ($res,$i,os_reincidente));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$total_pecas        = trim(pg_result ($res,$i,total_pecas));
		$total_mo           = trim(pg_result ($res,$i,total_mo));
		$cortesia           = trim(pg_result ($res,$i,cortesia));
		$peca_sem_preco     = pg_result ($res,$i,peca_sem_preco) ;
		$motivo_atraso      = pg_result ($res,$i,motivo_atraso) ;
		$motivo_atraso2      = pg_result ($res,$i,motivo_atraso2) ;
		$obs_reincidencia   = pg_result ($res,$i,obs_reincidencia) ;
		$nota_fiscal        = pg_result ($res,$i,nota_fiscal) ;
		$observacao         = pg_result ($res,$i,observacao) ;
		$consumidor_revenda = pg_result ($res,$i,consumidor_revenda);
		$peca_sem_estoque   = pg_result ($res,$i,peca_sem_estoque);
		$intervalo          = pg_result ($res,$i,intervalo);
		$texto              = "";
		
		if ($peca_sem_estoque =="t"){
			$coloca_botao = "sim";
		}
		if($login_fabrica == 11){
			$os_log= trim(pg_result($res,$i,os_log));
		}

		if($consumidor_revenda=="R" AND ($login_fabrica==6 or $login_fabrica==24)) {
			$consumidor_nome = $revenda_nome;
		}

		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}else{
			$cor = "#F7F5F0";
			$btn = "amarelo";
		}
	//takashi 1583	if(substr($motivo_atraso,0,34) == 'Esta OS é reincidente pois o posto')$observacao = "Justificativa do Sistema: ".$motivo_atraso;
//		echo "<div id='justificativa_$i' style='visibility : hidden; position:absolute; width:500px;left: 200px;opacity:.75;' class='Erro'  >$observacao</div>";
		if (strlen($os_reincidente) > 0) {
			//HD 15683 
			$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
			$res1 = pg_exec ($con,$sql);

			$sqlr="SELECT tbl_os_extra.os_reincidente from tbl_os_extra where os=$os";
			$resr=pg_exec($con,$sqlr);

			if(pg_numrows($resr)>0) $os_reinc=pg_result($resr,0,os_reincidente);
			if(strlen($os_reinc)>0){
				if($login_fabrica == 1){

					$sqlR = "SELECT tbl_os.sua_os, extrato FROM tbl_os_extra JOIN tbl_os USING(os) WHERE os=$os_reinc";
					$resR = pg_exec($con,$sqlR);
					if (pg_numrows($resR) > 0){
						$sos          = pg_result ($resR,0,sua_os) ;
						$sextrato     = pg_result ($resR,0,extrato) ;
						if($sextrato<>$extrato){
							$texto = "-R";
							$cor   = "#FFCCCC";
							if(strlen(trim($obs_reincidencia))==0){$obs_reincidencia = $motivo_atraso;}
	//						if(strlen(trim($obs_reincidencia))==0){$obs_reincidencia = $observacao;}
							$msg_reincidencia = "<td colspan='8' height='60' style='background-color: $cor; color: #AC1313;' align='left'>$obs_reincidencia</td>"; $msg_2 = "OS anterior:<br><a href='os_press.php?os=$os_reinc' target = '_blank'>$codigo_posto$sos</a><br><span style='font-size:8px'>EM OUTRO EXTRATO</span>";

						}
						else{
							$reincidencias_os[$rr]=$os_reinc;
							if(strlen(trim($obs_reincidencia))==0){$obs_reincidencia = $motivo_atraso;}
	//						if(strlen($observacao)==0){$observacao  = $obs_reincidencia;}
							$msg_reincidencia = "<td colspan='8' height='50' style='background-color: $cor; color: #AC1313;' align='left'>$obs_reincidencia</td>";
							$negrito ="<b>";
							//echo $reincidencias_os[$rr];
							$rr++;
						}
					}
				}
				if ($login_fabrica == 11 or $login_fabrica==24 or $login_fabrica==6 or $login_fabrica==15) $cor = '#FFCCCC';
			}
		}

		for($r=0; $r<$rr ; $r++){
			if($reincidencias_os[$r]==$os) {
				$negrito ="<b>";
			}
		}
		if($login_fabrica == 1){
			if (in_array($nota_fiscal, $localizou_array)) {
				$negrito ="<b>";
			}
		}
		
		if ($login_fabrica == 1 && $cortesia == "t") {
			$cor = "#D7FFE1";
		}
		if ($login_fabrica == 6 and $intervalo>30) {
			$intervalo = "<B><font color='#FF3300'>$intervalo</font></b>";
		}
	
		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		if ($ja_baixado == false AND $login_fabrica <> 1){ 
			if (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)) echo "<TD align='center'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
		}elseif($ja_baixado == false){ // HD 2225 takashi colocou esse if($ja_baixado == false) pois se nao fosse fabrica 1 colocava os checks... se estiver com problema tire
			echo "<TD align='center' rowspan='2'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
		}
		if ($login_fabrica <> 1){ echo "<TD nowrap><a href='os_press.php?os=$os' target='_blank'>";
		}else{
			echo "<TD nowrap rowspan='2' ";
			if ($peca_sem_estoque =="t" and $pedido_faturado=='t') echo "bgcolor='#d9ce94'";
			echo "><a href=\"javascript:void(0);\" onclick=\"javascript:window.open('detalhe_ordem_servico.php?os=$os','','width=750,height=500,scrollbars=yes');\">";
		}
		if ($login_fabrica == 1) echo $codigo_posto;
		echo $sua_os . $texto . "</a></TD>\n";
		if ($login_fabrica == 1) echo "<TD nowrap>$negrito$codigo_fabricacao</TD>\n";
		echo "<TD nowrap>$serie</TD>\n";
		if ($login_fabrica==3) {
			echo "<TD align='center'>$data_nf</TD>\n";;
			echo "<TD align='center'>$data_digitacao</TD>\n";;
		}
		if ($login_fabrica <> 1) echo "<TD align='center'>$abertura</TD>\n";
		if ($login_fabrica == 6) echo "<TD align='center'>$intervalo</TD>\n";
		if ($login_fabrica==3) {
			echo "<TD align='center'>$data_fechamento</TD>\n";;
		}
		echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17);
		if ($login_fabrica == 1) echo " - ".$consumidor_fone;
		echo "</ACRONYM></TD>\n";
		echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$produto_referencia - $produto_nome\">";
		if ($login_fabrica == 1) echo $produto_referencia; else echo substr($produto_nome,0,35);
		echo "</ACRONYM></TD>\n";

		if ($login_fabrica==3) {
			echo "<TD nowrap align='right'>".number_format($mao_de_obra_produto,2,","," ")."</TD>\n";
		}

		if($login_fabrica == 11){
			if($os_log == $os){
				echo "<td nowrap align='center'> <span class='text_curto'> <a href='os_log.php?os=$os_log' rel='ajuda1' target='blank' title='OS que Posto tentou cadastrar Fora de Garantia<br> Antes confirmar a nota fiscal e Número Série' class='ajuda'>?</a></span></TD>\n";
			}
		}


		if ($login_fabrica == 1) {
			$total_os = $total_pecas + $total_mo;
			echo "<TD align='left' nowrap>$negrito<ACRONYM TITLE=\"$revenda_nome\">". substr($revenda_nome,0,17) . "</ACRONYM></TD>\n";
			echo "<TD align='right' nowrap>$negrito " ;
			if ($peca_sem_preco == 0) {
				echo number_format($total_pecas,2,",",".") ;
			}else{
				echo "<font color='#ff0000'><b>SEM PREÇO</b></font>";
			}
			echo "</TD>\n";
			echo "<TD align='right' nowrap>$negrito " . number_format($total_mo,2,",",".") . "</TD>\n";
			echo "<TD align='right' nowrap>$negrito " . number_format($total_os,2,",",".") . "</TD>\n";
			echo "<TD align='right' rowspan='2'>$msg_2</TD>\n"; $msg_2 = '';
		}
		if ($login_fabrica ==6 or $login_fabrica==24){
			echo "<TD align='right' nowrap>$negrito " . number_format($total_mo,2,",",".") . "</TD>\n";
		}
		if ($login_fabrica ==6 AND strlen($liberado)==0){
			echo "<TD align='center' nowrap>";
			echo "<a href=\"$PHP_SELF?ajax_debito=true&os=$os&keepThis=trueTB_iframe=true&height=400&width=500\" title=\"Manutenção de valores da OS\" class=\"thickbox\">Alterar MO</a>";
			echo "</TD>\n";
		}

		$sqlD = "SELECT status_os FROM tbl_os_status WHERE os = $os and status_os = 91 order by os_status desc limit 1;";
		$resD = pg_exec($con,$sqlD);
		if(pg_numrows($resD) > 0){
			echo "<td bgcolor='#FFCC00'>Pendência Doc.</td>";
		}

		echo "</TR>\n";

		if ($login_fabrica == 1){
			echo "<tr >";
			echo $msg_reincidencia;
			$msg_reincidencia='';
			echo "</TR>\n";
		}

		if ($login_fabrica==6 and strlen($os_reincidente) > 0){
			echo "<tr  style='background-color: $cor;'>";
			echo "<td ></td>";
			echo "<td colspan='7' align='left'><B>Motivo Reincidência:</B> ";
			echo pg_result ($res,$i,obs_reincidencia) ;
			echo "</td>";
			echo "</TR>\n";
		}

		if ($login_fabrica==6 and strlen($motivo_atraso) > 0){
			echo "<tr  style='background-color: $cor;'>";
			echo "<td ></td>";
			echo "<td colspan='7' align='left'><B>Motivo Atraso:</B> ";
			echo pg_result ($res,$i,motivo_atraso) ;
			echo "</td>";
			echo "</TR>\n";
		}
		if ($login_fabrica==6 and strlen($motivo_atraso2) > 0){
			echo "<tr  style='background-color: $cor;'>";
			echo "<td ></td>";
			echo "<td colspan='7' align='left'><B>Motivo Atraso 60 dias:</B> ";
			echo pg_result ($res,$i,motivo_atraso2) ;
			echo "</td>";
			echo "</TR>\n";
		}
		$negrito ="";
	}//FIM FOR


	if ( (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica <> 6) OR (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica == 6) AND strlen($liberado)==0 ) {
		if ($login_fabrica == 1) $colspan = 10; else $colspan = 7;
		if ($login_fabrica == 6)$colspan = 9;
		if ($login_fabrica == 45) { $colspan = 6; }
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan='$colspan' align='left'> &nbsp; &nbsp; &nbsp; <img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; COM MARCADOS: &nbsp; ";
		echo "<select name='select_acao' size='1' class='frm'>";
		echo "<option value=''></option>";
		echo "<option value='RECUSAR'";  if ($_POST["select_acao"] == "RECUSAR")  echo " selected"; echo ">RECUSADO PELO FABRICANTE</option>";
		echo "<option value='EXCLUIR'";  if ($_POST["select_acao"] == "EXCLUIR")  echo " selected"; echo ">EXCLUÍDA PELO FABRICANTE</option>";
		echo "<option value='ACUMULAR'"; if ($_POST["select_acao"] == "ACUMULAR") echo " selected"; echo ">ACUMULAR PARA PRÓXIMO EXTRATO</option>";
		if($login_fabrica == 1 AND $posto == '6359'){
			echo "<option value='RECUSAR_DOCUMENTO'"; if ($_POST["select_acao"] == "RECUSAR_DOCUMENTO") echo " selected"; echo ">PENDÊNCIA DE DOCUMENTO</option>";
		}
		if($login_fabrica == 11){

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

if ($login_fabrica == 1){
	// sem paginacao
}else{
	// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	echo "<div>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div>";
		echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
}

} // Fecha a visualização dos extratos

// ##### EXIBE AS OS QUE SERÃO ACUMULADAS OU RECUSADAS ##### //
if (strlen($select_acao) > 0 AND strlen($lancamento) == 0) {
	$os     = $_POST["os"];
	$sua_os = $_POST["sua_os"];

	echo "<br>\n";
	echo "<HR WIDTH='600' ALIGN='CENTER'>\n";
	echo "<br>\n";
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='2'>";
	echo "Preencha o campo observação informando o motivo<br>pelo qual será ";
	if (strtoupper($select_acao) == "RECUSAR") echo "RECUSADO PELO FABRICANTE";
	elseif (strtoupper($select_acao) == "EXCLUIR") echo "EXCLUÍDA PELO FABRICANTE";
	elseif (strtoupper($select_acao) == "ACUMULAR") echo "ACUMULAR PARA PRÓXIMO EXTRATO";
	elseif (strtoupper($select_acao) == "RECUSAR_DOCUMENTO") echo "PENDÊNCIA DE DOCUMENTO";
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
			$obs        = $_POST["obs_" . $kk];
		}

		$cor = ($kk % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		if ($linha_erro == $kk && strlen($linha_erro) != 0) $cor = "FF0000";

		if (strlen($os[$k]) > 0) {
			if(strtoupper($select_acao) == "RECUSAR_DOCUMENTO"){
				$sql_doc = "SELECT tbl_extrato.protocolo          ,
									to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as data_geracao      ,
									tbl_posto_fabrica.codigo_posto,
									tbl_extrato.extrato
								FROM tbl_os
								JOIN tbl_os_extra using(os)
								JOIN tbl_extrato using(extrato)
								JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
								WHERE os = $os[$k];";
				$res_doc = pg_exec($con,$sql_doc);
				$codigo_posto = pg_result($res_doc,0,codigo_posto);
				$data_geracao = pg_result($res_doc,0,data_geracao);
				$protocolo    = pg_result($res_doc,0,protocolo);
				$obs = "Constatamos na conferência da documentação do extrato $protocolo do dia $data_geracao, a falta da cópia da nota fiscal da O.S $codigo_posto$sua_os[$k] 
Portanto, essa O.S será aprovada novamente no extrato da próxima semana e caso não possua a documentação gentileza nos comunicar para que possamos excluí-la. 
Sabrina Amaral.";
			}
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td align='center'>";
			if ($login_fabrica == 1) echo $codigo_posto.$sua_os[$k];
			else                     echo $sua_os[$k];
			echo "<input type='hidden' name='os_$kk' value='" . $os[$k] . "'></td>\n";
			echo "<td align='center'><textarea name='obs_$kk' rows='1' cols='100' class='frm'>$obs</textarea></td>\n";
			echo "</tr>\n";
			$kk++;
			$protocolo = '';
			$data_geracao = '';
		}
	}
	echo "</table>\n";
	echo "<input type='hidden' name='qtde_os' value='$kk'>";
	echo "<br>\n";
	echo "<img border='0' src='imagens/btn_confirmaralteracoes.gif' style='cursor: hand;' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='$select_acao'; document.frm_extrato_os.submit(); }else{ alert('Aguarde submissão'); }\" alt='Confirmar Alterações'>\n";
	echo "<br>\n";
}

$lancamento = $_GET['lancamento'];
if(strlen($lancamento) > 0 AND ($login_fabrica == 1 OR $login_fabrica == 10)){
	$sql = "SELECT valor, descricao, os_sedex FROM tbl_extrato_lancamento WHERE extrato_lancamento = $lancamento";

	$res = pg_exec($con,$sql);

	$descricao = pg_result($res,0,descricao);
	$valor     = pg_result($res,0,valor);
	$os_sedex  = pg_result($res,0,os_sedex);

	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<INPUT TYPE='hidden' NAME='os_sedex' value='$os_sedex'>";
	echo "<INPUT TYPE='hidden' NAME='extrato_lancamento' value='$lancamento'>";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='2' align='left' style='color: #FFCC00'>RECUSA DE OS SEDEX</td>";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
		echo "<td colspan='2' style='font-size: 10px'>Descrição: $descricao - Valor: R$ $valor</td>\n";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
		echo "<td>OS SEDEX</td>\n";
		echo "<td>OBSERVAÇÃO</td>\n";
	echo "</tr>\n";
	echo "<tr class='table_line' style='background-color: #F1F4FA;'>\n";
		echo "<td align='center'>$os_sedex</td>";
		echo "<td align='center'><INPUT TYPE=\"text\" size='100' NAME='descricao' class='frm'></td>";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
		echo "<td colspan='2'><INPUT TYPE=\"submit\" name='recusa_sedex' value='Recusar' class='frm'></td>\n";
	echo "</tr>\n";
	echo "</table>\n";

}

echo "<br>";

##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
if ($login_fabrica == 1 OR $login_fabrica == 10) {
	$sql = "SELECT  'OS SEDEX' AS descricao                        ,
					tbl_extrato_lancamento.os_sedex                ,
					''      AS historico                           ,
					tbl_extrato_lancamento.automatico              ,
					sum(tbl_extrato_lancamento.valor) as valor
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			GROUP BY tbl_extrato_lancamento.os_sedex               ,
					tbl_extrato_lancamento.automatico              ,
					tbl_extrato_lancamento.extrato_lancamento";

	$sql = "SELECT 'OS SEDEX' AS descricao                                     ,
					tbl_extrato_lancamento.extrato_lancamento                  ,
					tbl_extrato_lancamento.descricao AS descricao_lancamento   ,
					tbl_extrato_lancamento.os_sedex                            ,
					'' AS historico                                            ,
					tbl_extrato_lancamento.historico AS historico_lancamento   ,
					tbl_extrato_lancamento.automatico                          ,
					sum(tbl_extrato_lancamento.valor) as valor                 ,
					tbl_os_sedex.obs                                           ,
					tbl_os_sedex.sua_os_destino                                ,
					tbl_os_sedex.sua_os_origem                                 ,
					tbl_admin.login
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento   USING (lancamento)
			LEFT JOIN tbl_os_sedex   ON tbl_os_sedex.os_sedex = tbl_extrato_lancamento.os_sedex
			LEFT JOIN tbl_admin      ON tbl_admin.admin       = tbl_os_sedex.admin
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			GROUP BY    tbl_extrato_lancamento.os_sedex                ,
						tbl_extrato_lancamento.automatico              ,
						tbl_extrato_lancamento.descricao               ,
						tbl_extrato_lancamento.extrato_lancamento      ,
						tbl_extrato_lancamento.historico               ,
						tbl_admin.login                                ,
						tbl_os_sedex.obs                               ,
						tbl_os_sedex.sua_os_destino                    ,
						tbl_os_sedex.sua_os_origem;";
//echo $sql;
}else{
	$sql =	"SELECT tbl_extrato_lancamento.extrato_lancamento,
					tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.os_sedex  ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     ,
					tbl_extrato_lancamento.automatico
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			ORDER BY    tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.descricao,
						tbl_extrato_lancamento.extrato_lancamento";

if ($login_fabrica == 6 or $login_fabrica == 45) {//hd 9482
	$sql =	"SELECT tbl_extrato_lancamento.extrato_lancamento,
					tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.os_sedex  ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     ,
					tbl_extrato_lancamento.automatico,
					tbl_extrato_lancamento.descricao AS descricao_lancamento   ,
					tbl_extrato_lancamento.os_sedex                            ,
					tbl_extrato_lancamento.historico AS historico_lancamento   ,
					tbl_admin.login
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			LEFT JOIN tbl_admin      ON tbl_admin.admin       = tbl_extrato_lancamento.admin
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			ORDER BY    tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.descricao,
						tbl_extrato_lancamento.extrato_lancamento";
//echo $sql;

}

}
//if($ip == '189.47.21.40') echo $sql;

$res_avulso = pg_exec($con,$sql);

if (pg_numrows($res_avulso) > 0) {//hd 9482
	if ($login_fabrica == 1 OR $login_fabrica == 10 OR $login_fabrica == 6 or $login_fabrica == 45) $colspan = 6;
	else                     $colspan = 4;
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='$colspan'>LANÇAMENTO DE EXTRATO AVULSO</td>\n";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td>DESCRIÇÃO</td>\n";
	echo "<td>HISTÓRICO</td>\n";
	echo "<td>VALOR</td>\n";
	echo "<td>AUTOMÁTICO</td>\n";//hd 9482
	if ($login_fabrica == 1 OR $login_fabrica == 10 OR $login_fabrica == 6 or $login_fabrica == 45){
		echo "<td>ADMIN</td>\n";
		if ( ($login_fabrica==6 and strlen($liberado)==0) OR $login_fabrica<>6 )
		echo "<td>AÇÔES</td>\n";
	}
	echo "</tr>\n";
	for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
		$cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		$descricao               = pg_result($res_avulso, $j, descricao);
		$historico               = pg_result($res_avulso, $j, historico);
		$os_sedex                = pg_result($res_avulso, $j, os_sedex);
		$extrato_lancamento      = pg_result($res_avulso, $j, extrato_lancamento);
		$obs_sedex               = @pg_result($res_avulso, $j, obs);

		$sedex_faturada = stristr($obs_sedex, 'faturada');
		if(strlen($sedex_faturada) > 0){
			$descricao = "TROCA FATURADA";
		}
		//hd 9482
		if($login_fabrica == 6 or $login_fabrica == 45){
				$descricao          = @pg_result($res_avulso, $j, descricao_lancamento);
				$historico          = @pg_result($res_avulso, $j, historico_lancamento);
				$admin              = pg_result($res_avulso, $j, login);
		}
		if($login_fabrica == 1){
			$sua_os_destino     = @pg_result($res_avulso, $j, sua_os_destino);
			$sua_os_origem      = @pg_result($res_avulso, $j, sua_os_origem);

			if($sua_os_destino == "CR"){
				$sql = "SELECT tbl_os.sua_os FROM tbl_os WHERE os = '$sua_os_origem' AND fabrica = $login_fabrica ;";
				$res = pg_exec($con,$sql);
				$descricao = "CR " . $codigo_posto;
				$descricao .= pg_result($res,0,sua_os);
			}
		}

		if ($login_fabrica == 1 OR $login_fabrica == 10){
			if (strlen($os_sedex) == 0){
				$descricao          = @pg_result($res_avulso, $j, descricao_lancamento);
				$historico          = @pg_result($res_avulso, $j, historico_lancamento);
			}
			$admin              = pg_result($res_avulso, $j, login);
		}
		echo "<tr height='18' class='table_line' style='background-color: $cor;'>\n";
		echo "<td width='35%'>" . $descricao . "</td>";
		echo "<td width='35%' nowrap>" . $historico . "</td>";
		echo "<td width='10%' align='right' nowrap>  " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";

		echo "<td width='10%' align='center' nowrap>" ;
		if (pg_result($res_avulso, $j, automatico) == 't') {
			echo "S";
		}else{
			echo "&nbsp;";
		}
		echo "</td>";
		//hd 9482
		if($login_fabrica == 1 OR $login_fabrica == 10 or $login_fabrica==6 or $login_fabrica == 45){
			if(strlen($admin) > 0 ){
				echo "<td>". $admin ."</td>";
			}else{
				echo "<td>&nbsp;</td>";
			}
		}
		if (($login_fabrica == 1 OR $login_fabrica == 10) AND strlen($os_sedex) > 0){
			echo "<td width='10%' align='center' nowrap>";
			echo "<a href='sedex_finalizada.php?os_sedex=" . $os_sedex . "' target='_blank'><img border='0' src='imagens/btn_consulta.gif' style='cursor: hand;' alt='Consultar OS Sedex'></a>";
			echo "&nbsp;&nbsp;";
			echo "<INPUT TYPE=\"hidden\" NAME=\"lancamento\" value='$extrato_lancamento'>";
			echo "<a href='extrato_consulta_os.php?extrato=$extrato&lancamento=" . $extrato_lancamento . "' ><img border='0' src='imagens/btn_recusar.gif' style='cursor: hand;' alt='Recusa OS SEDEX'></a>";
			//echo "<a href='javascript:Excluir($extrato,$os_sedex)'><img src='imagens/btn_excluir.gif'></a>";
			echo "</td>";
		}//hd 9482
		if (($login_fabrica == 6  OR $login_fabrica == 45)  AND strlen($liberado)==0){
			echo "<td width='10%' align='center' nowrap>";
			echo "<INPUT TYPE=\"hidden\" NAME=\"lancamento\" value='$extrato_lancamento'>";
			echo "<a href='$PHP_SELF?acao=apagar&extrato=$extrato&xlancamento=" . $extrato_lancamento . "' ><img border='0' src='imagens/btn_recusar.gif' style='cursor: hand;' alt='Excluir Avulso'></a>";
			//echo "<a href='javascript:Excluir($extrato,$os_sedex)'><img src='imagens/btn_excluir.gif'></a>";
			echo "</td>";
		}
		echo "</tr>";
	}
	echo "</table>\n";
	echo "<br>\n";
}
##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

if($login_fabrica==6){
	$wsql = "SELECT tbl_os_status.os_status,
					tbl_os_status.os       ,  
					tbl_os.sua_os,
					tbl_os_status.status_os  ,
					tbl_os_status.data as data_order,
					to_char(tbl_os_status.data,'DD/MM/YYYY') as data      ,
					tbl_os_status.observacao ,
					tbl_os_status.extrato    ,
					tbl_os_status.os_sedex   ,
					tbl_admin.login
			from tbl_os_status 
			JOIN tbl_os on tbl_os.os = tbl_os_status.os
			join tbl_extrato on tbl_os_status.extrato =tbl_extrato.extrato
			JOIN tbl_admin on tbl_admin.admin = tbl_os_status.admin
			where tbl_extrato.extrato=$extrato 
			and status_os=90
			order by sua_os, data_order;";
	$wres = pg_exec($con,$wsql);
	if(pg_numrows($wres)>0){
		echo "<BR><BR><table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td colspan='4'>";
		echo "OBSERVAÇÕES FEITAS PELO FABRICANTE";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top' style='background-color: $cor;'>\n";
		echo "<td class='menu_top'>OS</td>";
		echo "<td class='menu_top'>DATA</td>";
		echo "<td class='menu_top'>OBSERVAÇÃO</td>";
		echo "<td class='menu_top'>ADMIN</td>";
		echo "</tr>";
		for($i=0;pg_numrows($wres)>$i;$i++){
			$sua_os     = pg_result($wres,$i,sua_os);
			$data       = pg_result($wres,$i,data);
			$observacao = pg_result($wres,$i,observacao);
			$login = pg_result($wres,$i,login);
			//$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td align='center'>$sua_os</td>";
			echo "<td align='center'>$data</td>";
			echo "<td align='left'>$observacao</td>";
			echo "<td align='center'>$login</td>";
			echo "</tr>";
		}
		echo "</table>";
	
	
	}
}




##### VERIFICA BAIXA MANUAL #####
$sql = "SELECT posicao_pagamento_extrato_automatico
		FROM tbl_fabrica 
		WHERE fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);
$posicao_pagamento_extrato_automatico = pg_result($res,0,posicao_pagamento_extrato_automatico);

if ($posicao_pagamento_extrato_automatico == 'f' and $login_fabrica <> 1) {
?>

<HR WIDTH='600' ALIGN='CENTER'>

<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0'>
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
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_pagamento'  size='10' maxlength='10' id='data_pagamento' value='" . $data_pagamento . "' class='frm'>";
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
	echo"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
	echo"<TR>";
	echo"	<TD ALIGN='center'><img src='imagens/btn_baixar.gif' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='baixar' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Baixar' border='0' style='cursor:pointer;'></TD>";
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

<? if($coloca_botao == "sim")	{ ?>
<img src='imagens/btn_pecas_negativas.gif' onclick="javascript: window.open('os_extrato_detalhe_pecas_negativas.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Detalhado' border='0' style='cursor:pointer;'>
<? } ?>
<? } ?>
<br><br>
<img border='0' src='imagens/btn_voltar.gif' onclick="javascript: history.back(-1);" alt='Voltar' style='cursor: hand;'>
</center>

<? include "rodape.php"; ?>
