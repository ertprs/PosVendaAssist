<center>
<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

$admin_privilegios = "financeiro";

include "autentica_admin.php";
include 'funcoes.php';

//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
//			 de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
//			 O array abaixo define quais fábricas estão enquadradas no processo novo
$fabricas_acerto_extrato = array(43, 45);

$posto = $_GET['posto'];
if (strlen($posto) == 0) $posto = $_POST['posto'];// HD 19580

$os      = $_GET['os'];
$op      = $_GET['op'];
$extrato = $_REQUEST['extrato'];

if (strlen ($os) > 0 AND $op =='zerar' and strlen($extrato) > 0) {

	$res = pg_query ($con,"BEGIN TRANSACTION");
	$sql = " UPDATE tbl_os set mao_de_obra =0 where os=$os;
			 SELECT fn_totaliza_extrato($login_fabrica,$extrato); ";
	$res = @pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	$res = (strlen($msg_erro) == 0) ? pg_query($con,"COMMIT TRANSACTION") : pg_query($con,"ROLLBACK TRANSACTION");

	$resposta = (strlen($msg_erro)>0) ? $msg_erro : "OS $os com mão-de-obra zerada!";
	echo "ok|$resposta";exit;
}

if (trim($_POST['btn_obs']) == 'Enviar OBS' && strlen($_POST['obs_extrato']) > 0) {//HD 226679
	
	$res = pg_query($con,"BEGIN TRANSACTION");
	$sql = "UPDATE tbl_extrato_extra set obs = '".trim($_POST['obs_extrato'])."' where extrato = $extrato;";
	
	$res      = @pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	$res = (strlen($msg_erro) == 0) ? pg_query($con,"COMMIT TRANSACTION") : pg_query($con,"ROLLBACK TRANSACTION");

}

#HD 165932
if(isset($_POST['grava_extrato'])) {
	if(isset($_POST['nota_fiscal_mao_de_obra'])) {
		$sql = " UPDATE tbl_extrato_extra set 
			nota_fiscal_mao_de_obra = '".$_POST['nota_fiscal_mao_de_obra']."'
			WHERE extrato = ".$_POST['grava_extrato'];
		$res = pg_query($con,$sql);
		exit;
	}
	if(isset($_POST['emissao_mao_de_obra'])) {
		$sql = " UPDATE tbl_extrato_extra set 
			emissao_mao_de_obra = '".$_POST['emissao_mao_de_obra']."'
			WHERE extrato = ".$_POST['grava_extrato'];
		$res = pg_query($con,$sql);
		exit;
	}

	if(isset($_POST['valor_total_extrato'])) {
		$sql = " UPDATE tbl_extrato_extra set 
			valor_total_extrato = '".str_replace(",",".",$_POST['valor_total_extrato'])."'
			WHERE extrato = ".$_POST['grava_extrato'];
		$res = pg_query($con,$sql);
		exit;
	}
}

if (strlen ($os) > 0 AND $op =='mo2' and strlen($extrato) >0) {

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_os set
			mao_de_obra = 2,
			admin = $login_admin,
			obs='Valor da M.O. foi alterado por ' || tbl_admin.nome_completo || ' para R$ 2,00'
			from tbl_admin
			where tbl_admin.admin = $login_admin
			and tbl_os.os=$os;

			SELECT fn_totaliza_extrato($login_fabrica,$extrato); ";
	$res = @pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	$res = (strlen($msg_erro) == 0) ? pg_query($con,"COMMIT TRANSACTION") : pg_query($con,"ROLLBACK TRANSACTION");

	$resposta = (strlen($msg_erro)>0) ? $msg_erro : "OS $os com mão-de-obra R$ 2,00 (Troca de Produto)!";
	echo "ok|$resposta";exit;
}

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
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$extrato = pg_fetch_result($res,0,extrato);
			$os      = pg_fetch_result($res,0,os);
			$mao_de_obra  = pg_fetch_result($res,0,mao_de_obra);
			$codigo_posto = pg_fetch_result($res,0,codigo_posto);
			$referencia   = pg_fetch_result($res,0,referencia);
			$descricao    = pg_fetch_result($res,0,descricao);
			$nome_posto   = pg_fetch_result($res,0,nome);
			$observacao   = pg_fetch_result($res,0,observacao);
		}
		echo "<table border='0' cellpadding='4' cellspacing='1' width='700' align='center' style='font-family: verdana; font-size: 10px'>";
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
			$res = pg_query ($con,"BEGIN TRANSACTION");
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
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
		if(strlen($msg_erro)==0){
			$sql = "UPDATE tbl_os set mao_de_obra = $mao_de_obra
					WHERE  os = $os and fabrica = $login_fabrica;";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
		if(strlen($msg_erro)==0){
			#HD15716
			if($login_fabrica == 11){
				$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
				$res = pg_query($con,$sql);
				$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
			}else{
				$sql = "select fn_calcula_extrato($login_fabrica,$extrato)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
				echo "<center>Alteração efetuada com sucesso!!</center>";
		}else{

			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			echo "<center>Ocorreu o seguinte erro: $msg_erro</center>";
		}
/* INSERT into tbl_os_status( os , status_os , data , observacao , extrato , admin )values( 3773040, 90, current_timestamp, 'mao de obra estará zero pq eu quero', 195118, 158 );
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
90 */
	}
	exit;
}

if($login_fabrica==11){
	$extrato = $_GET['extrato'];
	$os      = $_GET['os'];
	$zerarmo = $_GET['zerarmo'];

	if(strlen($os) > 0 AND $zerarmo=='t'){
		$res = pg_query ($con,"BEGIN TRANSACTION");
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
							'Mão de Obra zerada pelo admin na aprovação do extrato.',
							$extrato,
							$login_admin
						);";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		$sqlZ = "UPDATE tbl_os_extra SET
				admin_paga_mao_de_obra = 'f'
				WHERE os      = $os
				AND   extrato = $extrato";
		$resZ = pg_query($con,$sqlZ);
		$msg_erro = pg_errormessage($con);

		$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
		$res = pg_query($con,$sql);
		$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($_GET["acao"] == apagar && $_GET["xlancamento"]) {
	//HD 238905: Ao excluir um extrato, mandar recalcular
	$msg_erro = array();

	$sql = "BEGIN TRANSACTION";
	$res = pg_query($con, $sql);
	$msg_erro[] = pg_errormessage($con);

	$sql = "
	DELETE FROM
	tbl_extrato_lancamento

	WHERE
	extrato_lancamento=" . $_GET["xlancamento"] . "
	AND fabrica=$login_fabrica
	AND extrato=" . $_GET["extrato"] . "
	";
	$res = pg_query($con, $sql);
	$msg_erro[] = pg_errormessage($con);

	$sql = "SELECT fn_calcula_extrato($login_fabrica, " . $_GET["extrato"] . ");";
	$res = pg_query($con, $sql);
	$msg_erro[] = pg_errormessage($con);

	$msg_erro = implode("", $msg_erro);

	if ($msg_erro) {
		$sql = "ROLLBACK TRANSACTION";
		$res = pg_query($con, $sql);
	}
	else {
		$sql = "COMMIT TRANSACTION";
		$res = pg_query($con, $sql);
	}

	header("location:$PHP_SELF?extrato=" . $_GET["extrato"]);
	die;
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

	$sql = "BEGIN TRANSACTION";
	$res = pg_query($con,$sql);

	$sql = "SELECT os
			FROM tbl_os_extra
			WHERE tbl_os_extra.extrato = $extrato";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		for($i=0;pg_num_rows($res)>$i;$i++){
			$os = pg_fetch_result($res,$i,os);
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
			$xres = pg_query($con,$xsql);
			$msg_erro .= pg_errormessage($con);
			if(pg_num_rows($res)>0){
				for($x=0;pg_num_rows($xres)>$x;$x++){
					$posto = pg_fetch_result($xres,$x,posto);
					$qtde  = pg_fetch_result($xres,$x,qtde);
					$os    = pg_fetch_result($xres,$x,os);
					$os_item= pg_fetch_result($xres,$x,os_item);
					$peca   = pg_fetch_result($xres,$x,peca);

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
					$yres = pg_query($con,$ysql);
					$msg_erro .= pg_errormessage($con);

					if(strlen($msg_erro)==0){
						$ysql = "SELECT peca
								FROM tbl_estoque_posto
								WHERE peca = $peca
								AND posto = $posto
								AND fabrica = $login_fabrica;";
						$yres = pg_query($con,$ysql);
						if(pg_num_rows($res)>0){
							$ysql = "UPDATE tbl_estoque_posto set
									qtde = qtde + $qtde
									WHERE peca  = $peca
									AND posto   = $posto
									AND fabrica = $login_fabrica;";
							$yres = pg_query($con,$ysql);
							$msg_erro .= pg_errormessage($con);
						}else{
							$ysql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)values($login_fabrica,$posto,$peca,$qtde)";
							$yres = pg_query($con,$ysql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}
		}
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		echo "Peça(s) aceita(s) com sucesso!";
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
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
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){

		$sql = "DELETE from tbl_extrato_extra_item
				where extrato = $extrato
				and extrato_lancamento = $xlancamento";
		$res = pg_query($con,$sql);

		$sql = "DELETE from tbl_extrato_lancamento
				where fabrica=$login_fabrica
				and extrato = $extrato
				and extrato_lancamento = $xlancamento";
		$res = pg_query($con,$sql);

		//HD 6887
		if($login_fabrica==6){
			$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
			$res = pg_query($con,$sql);
		}else{ //hd 9482
			#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
			#$res = pg_query($con,$sql);
			#$total_os_extrato = pg_fetch_result($res,0,0);
			#HD15716
			if($login_fabrica == 11){
				$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
				$res = pg_query($con,$sql);
				$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
			}else{
				$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
				$res = pg_query($con,$sql);
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
	$xacrescimo = (strlen($acrescimo) > 0) ? "'".str_replace(",",".",$acrescimo)."'" : 'NULL';

	$desconto        = trim($_POST["desconto"]) ;
	$xdesconto = (strlen($desconto) > 0) ? "'".str_replace(",",".",$desconto)."'" : 'NULL';

	$valor_liquido   = trim($_POST["valor_liquido"]) ;
	$xvalor_liquido = (strlen($valor_liquido) > 0) ? "'".str_replace(",",".",$valor_liquido)."'" : 'NULL';

	$nf_autorizacao = trim($_POST["nf_autorizacao"]) ;
	$xnf_autorizacao = (strlen($nf_autorizacao) > 0) ? "'$nf_autorizacao'" : 'NULL';

	if($login_fabrica==45 AND strlen($nf_autorizacao)==0){
		$msg_erro = "Digite a nota fiscal do pagamento";
	}

	$autorizacao_pagto = trim($_POST["autorizacao_pagto"]) ;
	if(strlen($nf_autorizacao) > 0) $xautorizacao_pagto = "'$autorizacao_pagto'";
	else                            $xautorizacao_pagto = 'NULL';

	$data_recebimento_nf = trim($_POST["data_recebimento_nf"]) ;
	$xdata_recebimento_nf = (strlen($data_recebimento_nf) > 0) ? "'$data_recebimento_nf'" : 'NULL';

	if($login_fabrica==30){
		if (strlen($_POST["data_recebimento_nf"]) > 0) {
			$data_recebimento_nf = trim($_POST["data_recebimento_nf"]) ;
			$xdata_recebimento_nf = str_replace ("/","",$data_recebimento_nf);
			$xdata_recebimento_nf = str_replace ("-","",$xdata_recebimento_nf);
			$xdata_recebimento_nf = str_replace (".","",$xdata_recebimento_nf);
			$xdata_recebimento_nf = str_replace (" ","",$xdata_recebimento_nf);

			$dia = trim (substr ($xdata_recebimento_nf,0,2));
			$mes = trim (substr ($xdata_recebimento_nf,2,2));
			$ano = trim (substr ($xdata_recebimento_nf,4,4));
			if (strlen ($ano) == 2) $ano = "20" . $ano;

	//-=============Verifica data=================-//
			$verifica = checkdate($mes,$dia,$ano);
			if ( $verifica ==1){
				$xdata_recebimento_nf = $ano . "-" . $mes . "-" . $dia ;
				$xdata_recebimento_nf = "'" . $xdata_recebimento_nf . "'";
			}else{
				$msg_erro="A Data de Pagamento não está em um formato válido";
			}
		}else{
			$xdata_recebimento_nf = "NULL";
			//HD 9387 Paulo 10/12/2007
			$msg_erro.="Por favor, digitar a Data de Recebimento da Nota Fiscal!!!";
		}
	}

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

		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_pagamento = "'" . $xdata_pagamento . "'";
		}else{
			$msg_erro="A Data de Pagamento não está em um formato válido";
		}
	}else if($login_fabrica==45){
		$xdata_pagamento = "NULL";
	}else{//hd 26972
		$xdata_pagamento = "NULL";
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
		$res = pg_query ($con,"BEGIN TRANSACTION");

		if (strlen($extrato_pagamento) > 0) {
			$sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = $login_fabrica AND extrato = $extrato";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0) $msg_erro = "Erro ao cadastrar baixa. Extrato não pertence à esta fábrica.";
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
							admin             = $login_admin        ,
							data_recebimento_nf = $xdata_recebimento_nf
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
							admin             ,
							data_recebimento_nf
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
							$login_admin       ,
							$xdata_recebimento_nf
						)";
			}
			$res = pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			if($login_fabrica == 24){// ficava listando sem tem necessidade, takashi tirou conforme conversa com aline 11/12/07
				echo "<script language='JavaScript'>";
				echo "if (window.opener){window.opener.refreshTela(50);} "; #HD 22752
				echo "window.close();";
				echo "</script>";
			}else{
				header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
				exit;
			}
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

// HD 18066
if ($btn_acao == "excluir_baixa") {
	$extrato=$_POST['extrato'];
	if (strlen($extrato) > 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");
		$sql="DELETE FROM tbl_extrato_pagamento where extrato=$extrato";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			if ($login_fabrica == 24){
				header ("Location: $PHP_SELF?extrato=$extrato");
			}else{
				header ("Location: extrato_consulta.php?extrato=$extrato");
			}
			exit;
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}
if ($btn_acao == "excluir") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_query($con,"BEGIN TRANSACTION");
	$array_os_geo= array();
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
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os_extra SET extrato = null
						WHERE  tbl_os_extra.os      = $x_os
						AND    tbl_os_extra.extrato = $extrato
						AND    tbl_os_extra.os      = tbl_os.os
						AND    tbl_os_extra.extrato = tbl_extrato.extrato
						AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
						AND    tbl_extrato_extra.baixado IS NULL
						AND    tbl_os.fabrica  = $login_fabrica;";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		#OS_TROCA - Excluida/Acumulada/Recusada o débito HD14648
		if(strlen($msg_erro) == 0 AND ($login_fabrica == 1 || $login_fabrica == 30)){
			$sql = "SELECT tbl_os_troca.os_troca    ,
							tbl_os_troca.total_troca,
							tbl_os.os               ,
							tbl_os.sua_os
						FROM tbl_os
						JOIN tbl_os_troca USING(os)
						WHERE os = $x_os";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$sua_os_troca   = pg_fetch_result($res,0,sua_os);
				$os_sedex_troca = '';
				#troca
				$sql = "SELECT os_sedex
							FROM tbl_os_sedex
							WHERE extrato_destino = $extrato
							AND sua_os_destino = '$sua_os_troca'; ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$os_sedex_troca = pg_fetch_result($res, 0,os_sedex);
					#Sedex

					$sql = "DELETE FROM tbl_extrato_extra_item WHERE os_sedex = $os_sedex_troca ";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "DELETE FROM tbl_os_sedex WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "SELECT extrato_lancamento
								FROM tbl_extrato_lancamento
								WHERE extrato = $extrato
								AND   os_sedex = $os_sedex_troca;";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0 AND strlen($msg_erro) == 0){
						$extrato_lancamento_troca = pg_fetch_result($res,0,extrato_lancamento);
						#extrato lançamento
						$sql = "DELETE FROM tbl_extrato_extra_item WHERE extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

						$sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
			/*OS GEO METAL - 83010*/
			$sql = "SELECT os_numero
					FROM tbl_os
					WHERE os = $x_os
						and tipo_os= 13;";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$os_numero= pg_fetch_result($res,0,os_numero);
				$array_os_geo[$os_numero]= $os_numero;
			}
			# HD 148341 - A movimentação de estoque deve ser retirada quando uma OS é excluida
			$sql = "SELECT fn_estoque_recusa_os($x_os,$login_fabrica,$login_admin);";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_os SET excluida = true
						WHERE  tbl_os.os           = $x_os
						AND    tbl_os.fabrica      = $login_fabrica;";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($x_os,$login_fabrica)";
			$res = pg_query($con, $sql);


			if($login_fabrica ==1){ // HD 28837
				$sql = "insert into tbl_os_excluida (
						fabrica           ,
						admin             ,
						os                ,
						sua_os            ,
						posto             ,
						codigo_posto      ,
						produto           ,
						referencia_produto,
						data_digitacao    ,
						data_abertura     ,
						data_fechamento   ,
						serie             ,
						nota_fiscal       ,
						data_nf           ,
						consumidor_nome
					)
					SELECT  tbl_os.fabrica            ,
						$login_admin                  ,
						tbl_os.os                     ,
						tbl_os.sua_os                 ,
						tbl_os.posto                  ,
						tbl_posto_fabrica.codigo_posto,
						tbl_os.produto                ,
						tbl_produto.referencia        ,
						tbl_os.data_digitacao         ,
						tbl_os.data_abertura          ,
						tbl_os.data_fechamento        ,
						tbl_os.serie                  ,
						tbl_os.nota_fiscal            ,
						tbl_os.data_nf                ,
						tbl_os.consumidor_nome
					FROM    tbl_os
					JOIN    tbl_posto_fabrica        on tbl_posto_fabrica.posto = tbl_os.posto and tbl_os.fabrica          = tbl_posto_fabrica.fabrica
					JOIN    tbl_produto              on tbl_produto.produto     = tbl_os.produto
					WHERE   tbl_os.os      = $x_os
					AND     tbl_os.fabrica = $login_fabrica ";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
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

			$res = pg_query($con,$sql);//somatoria de todas as peças que há troca de peça gerando pedido.
			$total = pg_fetch_result($res,0,total);
			if(strlen($msg_erro) == 0 AND $total > 0){

				$sql = "SELECT sua_os FROM tbl_os WHERE os = $x_os;";
				$res = pg_query($con,$sql);
				$sedex_sua_os     = trim(pg_fetch_result($res,0,sua_os));

				$sql = "SELECT posto, protocolo
						FROM tbl_extrato
						WHERE extrato = $extrato
						AND   fabrica = $login_fabrica;";

				$res = pg_query($con,$sql);	//busca o posto para ser inserido no posto origem
											//busca o protocolo para enviar e-mail.
				$posto_destino    = pg_fetch_result($res,0,posto);
				$sedex_protocolo = pg_fetch_result($res,0,protocolo);

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
				$res = pg_query($con,$sql);//insere uma OS SEDEX no extrato atual.
				$msg_erro = pg_errormessage($con);

				$sql = "SELECT CURRVAL ('tbl_os_sedex_seq')";
				$res = pg_query($con,$sql);//busca a os_sedex que foi cadastrada.
				$os_sedex = pg_fetch_result($res,0,0);

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
											'42'             ,
											$total_neg       ,
											$os_sedex
									);";
				$res = pg_query($con,$sql);	//insere um lancamento com valor NEGATIVO. Valor das pecas multiplicaod por -1.
											//insere para a black o status 41.
				//HD 16545 estava inserindo o lançamento 41 para débito, mas 42 que é debito

				$sql = "UPDATE tbl_os_status SET os_sedex = '$os_sedex'
						WHERE extrato = $extrato
						AND   os = $x_os
						AND   status_os = 15 ;";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	
	/*OS GEO - 83010*/
	if (strlen($msg_erro) == 0 and $login_fabrica ==1 and count($array_os_geo)>0) {
		foreach ($array_os_geo as $i => $os_revenda) {
			$sql = "SELECT tbl_os_revenda_item.os_lote as os, 
						tbl_os_extra.extrato
					FROM tbl_os_revenda
					JOIN tbl_os_revenda_item using(os_revenda)
					JOIN tbl_os_extra on tbl_os_extra.os = tbl_os_revenda_item.os_lote
					WHERE tbl_os_revenda.fabrica =$login_fabrica
						AND tbl_os_revenda.os_revenda = $os_revenda
						AND tbl_os_revenda.extrato_revenda =$extrato
						AND tbl_os_extra.extrato = $extrato";
			//echo "sql: $sql";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				//Quando existir OS (tbl_os) da OS GEO (tbl_os_revenda) não faz nada
			}else{

				//Se não existir tem que atualizar a OS Revenda para Fábrica zero
				$sql = "UPDATE tbl_os_revenda
							SET extrato_revenda = null,
								fabrica = 0
						WHERE os_revenda = $os_revenda
							AND extrato_revenda =$extrato;";
				//echo "sql: $sql";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		$sql = "SELECT posto
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$posto = pg_fetch_result($res,0,posto);
		/*Necessário recalcular o Extrato, pois existem regras de avulso baseada na OS Geo*/
		if (pg_num_rows($res) > 0 AND strlen($msg_erro) == 0) {
			$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica ;";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){

			#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
			#$res = pg_query($con,$sql);
			#$total_os_extrato = pg_fetch_result($res,0,0);
			#HD15716
			if($login_fabrica == 11){
				$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
				$res = pg_query($con,$sql);
				$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
			}else{
				$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link&msg_aviso=$msg_aviso");
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}


if (strtolower($btn_acao) == "recusar" OR strtolower($btn_acao) == 'recusar_documento') {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_query($con,"BEGIN TRANSACTION");

	$array_os_geo= array();
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
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$sua_os_troca   = pg_fetch_result($res,0,sua_os);
				$os_sedex_troca = '';
				#troca
				$sql = "SELECT os_sedex
							FROM tbl_os_sedex
							WHERE extrato_destino = $extrato
							AND sua_os_destino = '$sua_os_troca'; ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$os_sedex_troca = pg_fetch_result($res, 0,os_sedex);
					#Sedex
					$sql = "DELETE FROM tbl_extrato_extra_item WHERE os_sedex = $os_sedex_troca ";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "DELETE FROM tbl_os_sedex WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "SELECT extrato_lancamento
								FROM tbl_extrato_lancamento
								WHERE extrato = $extrato
								AND   os_sedex = $os_sedex_troca;";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0 AND strlen($msg_erro) == 0){
						$extrato_lancamento_troca = pg_fetch_result($res,0,extrato_lancamento);
						#extrato lançamento
						$sql = "DELETE FROM tbl_extrato_extra_item WHERE extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

						$sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}

			/*OS GEO METAL - 83010*/
			$sql = "SELECT os_numero
					FROM tbl_os
					WHERE os = $x_os
						and tipo_os= 13;";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$os_numero= pg_fetch_result($res,0,os_numero);
				$array_os_geo[$os_numero]= $os_numero;
			}
		}


		if (strlen($msg_erro) == 0) {
			if($btn_acao == 'recusar_documento'){
				$sql_doc = "SELECT finalizada, data_fechamento FROM tbl_os WHERE os = $x_os LIMIT 1;";
				$res_doc = pg_query($con,$sql_doc);
				$msg_erro = pg_errormessage($con);

				$doc_finalizada      = pg_fetch_result($res_doc,0,finalizada);
				$doc_data_fechamento = pg_fetch_result($res_doc,0,data_fechamento);
			}

			$sql = "SELECT fn_recusa_os(fabrica, extrato, os, '$x_obs')
					FROM tbl_os
					JOIN tbl_os_extra USING(os)
					WHERE tbl_os.os = $x_os
					AND   extrato   = $extrato
					AND   fabrica   = $login_fabrica ;";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if($login_fabrica != 1 && $login_fabrica != 30){
				$sql = "SELECT fn_estoque_recusa_os($x_os,$login_fabrica,$login_admin);";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if($btn_acao == 'recusar_documento' AND $login_fabrica == 1){
				$sql_doc2 = "SELECT os_status FROM tbl_os_status WHERE os = $x_os ORDER BY os_status DESC LIMIT 1;";
				$res_doc2 = pg_query($con,$sql_doc2);
				$msg_erro = pg_errormessage($con);

				$doc_os_status    = pg_fetch_result($res_doc2,0,os_status);

				$sql_doc = "UPDATE tbl_os_status SET status_os = 91 WHERE os = $x_os AND os_status = $doc_os_status;";
				$res_doc = pg_query($con,$sql_doc);
				$msg_erro = pg_errormessage($con);

/*				$sql_doc = "UPDATE tbl_os SET finalizada = '$doc_finalizada', data_fechamento = '$doc_data_fechamento' WHERE os = $x_os;";
				$res_doc = pg_query($con,$sql_doc);
				$msg_erro = pg_errormessage($con);
*/			}
		}

		if(strlen($msg_erro)==0) { // HD 52911
			if($login_fabrica == 45) {
				# HD 53003
				$sqlGrAdmin = "UPDATE tbl_os_status set admin = $login_admin
									WHERE extrato = $extrato
									AND   os      = $x_os
									AND   os_status in (
									SELECT os_status
									FROM tbl_os_status
									WHERE extrato = $extrato
									AND os = $x_os
									ORDER BY os_status DESC LIMIT 1); ";
				$resGrAdmin = pg_query($con,$sqlGrAdmin);
				$msg_erro = pg_errormessage($con);

				$sql = "SELECT tbl_os.sua_os,contato_email
						FROM tbl_os
						JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE os=$x_os";
				$res = @pg_query($con,$sql);
				$pr_sua_os        = @pg_fetch_result($res,0,sua_os);
				$pr_contato_email = @pg_fetch_result($res,0,contato_email);

				$sqlx= "SELECT email From tbl_admin WHERE admin = $login_admin";
				$resx = @pg_query($con,$sqlx);
				$admin_email = @pg_fetch_result($resx,0,email);

				if($btn_acao =='excluir') $conteudo_acao ='excluída';
				if($btn_acao =='acumular') $conteudo_acao ='acumulada';
				if($btn_acao =='recusar') $conteudo_acao ='recusada';

				$destinatario = $pr_contato_email;
				$assunto      = " OS $pr_sua_os $conteudo_acao";
				$mensagem = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
				$mensagem .=  "At. Responsável,<br><br>A OS $pr_sua_os foi $conteudo_acao pelo seguinte motivo: <br> $x_obs. <br>";
				$mensagem .="Qualquer duvida contatar a sua atendente regional.<br>";
				$mensagem .="<b><font color='red'>NKS</font></b>";
				$body_top .= "Content-type: text/html;";
				if(strlen($mensagem)>0) mail($destinatario,$assunto,$mensagem,$body_top);
			}
		}
	}

	/*OS GEO - 83010*/
	//print_r($array_os_geo);
	if (strlen($msg_erro) == 0 and $login_fabrica ==1 and count($array_os_geo)>0) {

		foreach ($array_os_geo as $i => $os_revenda) {
			$sql = "SELECT tbl_os_revenda_item.os_lote as os, 
						tbl_os_extra.extrato
					FROM tbl_os_revenda
					JOIN tbl_os_revenda_item using(os_revenda)
					JOIN tbl_os_extra on tbl_os_extra.os = tbl_os_revenda_item.os_lote
					WHERE tbl_os_revenda.fabrica =$login_fabrica
						AND tbl_os_revenda.os_revenda = $os_revenda
						AND tbl_os_revenda.extrato_revenda =$extrato
						AND tbl_os_extra.extrato = $extrato";
			//echo "sql: $sql";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				$msg_erro.="Para a OS GEO: $os_revenda deve ser recusado todos os produtos juntos.<br>";
			}else{
				$sql = "UPDATE tbl_os_revenda
							SET extrato_revenda = null
						WHERE os_revenda = $os_revenda
							AND extrato_revenda =$extrato;";
				//echo "sql: $sql";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}


		$sql = "SELECT posto
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$posto = pg_fetch_result($res,0,posto);
/*CONFIRMAR COM FABIOLA SE RECALCULA AQUI???*/
		if (pg_num_rows($res) > 0 AND strlen($msg_erro) == 0) {
			$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
			$res = @pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

	}



/*
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_num_rows($res) > 0) {
			if (@pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
				$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}
*/
	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link&msg_aviso=$msg_aviso");
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}


if($login_fabrica == 11 OR $login_fabrica == 66){//Recusas de OS´s
	if (strtoupper($select_acao) <> "RECUSAR" AND strtoupper($select_acao) <> "EXCLUIR" AND strtoupper($select_acao) <> "ACUMULAR" AND strlen($select_acao) > 0) {
		$os     = $_POST["os"];
		$sua_os = $_POST["sua_os"];

		$kk = 0;

		$res = pg_query($con,"BEGIN TRANSACTION");

		$sql         = "SELECT motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
		$res         = pg_query($con, $sql);
		$select_acao = pg_fetch_result($res,0,motivo);
		$status_os   = pg_fetch_result($res,0,status_os);

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
					$select_acao = RemoveAcentos($select_acao);
					$select_acao = strtoupper($select_acao);
					$kk++;

					if (strlen($msg_erro) == 0) {
						if($status_os == 13){
							$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
						}else{
							$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
						}
						$res = @pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

						$sql2 = "UPDATE tbl_os_status set admin = $login_admin
									WHERE extrato = $extrato
									AND   os      = $os[$k]
									AND   os_status in (SELECT os_status FROM tbl_os_status WHERE extrato = $extrato AND os = $os[$k] ORDER BY os_status DESC LIMIT 1); ";
						$res2 = pg_query($con,$sql2);
						$msg_erro = pg_errormessage($con);
					}
				}
			}

			if (strlen($msg_erro) == 0) {
				$sql = "SELECT posto
						FROM   tbl_extrato
						WHERE  extrato = $extrato
						AND    fabrica = $login_fabrica";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (pg_num_rows($res) > 0) {
					if (@pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
						//hd 10185 - trocado calcula por totaliza
						//$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
						#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
						#$res = pg_query($con,$sql);
						#$total_os_extrato = pg_fetch_result($res,0,0);
						#HD15716
						if($login_fabrica == 11){
							$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
							$res = pg_query($con,$sql);
							$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
						}else{
							$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
							$res = @pg_query($con,$sql);
							$msg_erro = pg_errormessage($con);
						}
					}
				}
			}

			if (strlen($msg_erro) == 0) {
				$res = pg_query($con,"COMMIT TRANSACTION");
				$link = $_COOKIE["link"];
				header ("Location: $link&msg_aviso=$msg_aviso");
				exit;
			}else{
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
			$select_acao = '';
		}

		$kk = 0;

		if($status_os == 15 AND strlen($msg_erro) == 0){

			$res = pg_query($con,"BEGIN TRANSACTION");

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
					$res = @pg_query($con,$sql);
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
						$res = @pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
							$sql = "UPDATE tbl_os SET excluida = true
									WHERE  tbl_os.os           = $os[$k]
									AND    tbl_os.fabrica      = $login_fabrica;";
						$res = @pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

						#158147 Paulo/Waldir desmarcar se for reincidente
						$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
						$res = pg_query($con, $sql);


					}
					if ((strlen($msg_erro) == 0) and $fabrica == 51 ) {
						//samuel colocou 12-12-2008 pq Ronaldo perguntou pq a OS excluída gerou embarque. EX os OS 6197121 - Excluida na consulta distrib (embarque 39363).
						$distribuidor      = 4311; //Distribuidor Telecontrol
						// ATENCAO - A rotina abaixo pede como parametro o distribuidor, mas não utiliza, mesmo assim estamos enviando 4311 porque e o distribuidor Telecontrol
						$fabrica           = 51; //Gama Italy
						$motivo            = "OS excluída pelo fabricante no extrato";

						$sql_os = "SELECT DISTINCT tbl_os.os ,
								tbl_os.posto,
								tbl_os_item.os_item,
								tbl_os_item.peca,
								tbl_os_item.pedido
								FROM tbl_os
								JOIN tbl_posto_fabrica         ON tbl_posto_fabrica.fabrica = tbl_os.fabrica and tbl_posto_fabrica.posto = tbl_os.posto
								JOIN tbl_os_produto USING (os)
								JOIN tbl_os_item    USING (os_produto)
								JOIN tbl_peca       USING (peca)
								LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido
											AND tbl_faturamento_item.peca = tbl_os_item.peca
								LEFT JOIN tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_os_item.pedido
															  AND tbl_pedido_item.peca   = tbl_os_item.peca
															  AND tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
								LEFT JOIN tbl_pedido           ON tbl_pedido.pedido = tbl_pedido_item.pedido
								LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
								JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
								WHERE tbl_os.fabrica = 51
								AND tbl_faturamento.nota_fiscal IS NULL
								AND tbl_os_item.pedido IS NOT NULL
								AND tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
								AND tbl_os.troca_garantia is not true
								AND tbl_os.os = $os[$k]";
						$res_os = pg_query($con,$sql_os);
						if(pg_num_rows($res_os)>0){
							for($j=0; $j<pg_num_rows($res_os); $j++){
								$os        = trim(pg_fetch_result($res_os,$j,os));
								$os_item   = trim(pg_fetch_result($res_os,$j,os_item));
								$posto     = trim(pg_fetch_result($res_os,$j,posto));
								$peca      = trim(pg_fetch_result($res_os,$j,peca));
								$pedido    = trim(pg_fetch_result($res_os,$j,pedido));

								$sql_ja = "SELECT count(*) as ja
									FROM tbl_pedido_cancelado
									WHERE pedido = $pedido
									AND posto = $posto
									AND fabrica = 51
									AND os = $os
									AND peca = $peca";
								$res_ja = pg_query($con, $sql_ja);
								$ja = 0;
								if(pg_num_rows($res_ja)>0){
									$ja = pg_fetch_result($res_ja,0,ja);
								}
								if($ja==0){
									$sql ="SELECT fn_pedido_cancela_garantia($distribuidor,$fabrica,$pedido,$peca,$os_item, '$motivo')";
									$res = pg_query ($con,$sql);
								}
							}
						}
					}
				}
			}
			if (strlen($msg_erro) == 0) {
				$sql = "SELECT posto
						FROM   tbl_extrato
						WHERE  extrato = $extrato
						AND    fabrica = $login_fabrica";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){

					//hd 10185 - trocado calcula por totaliza
					//$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";

					#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
					#$res = pg_query($con,$sql);
					#$total_os_extrato = pg_fetch_result($res,0,0);
					#HD15716
					if($login_fabrica == 11){
						$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
						$res = pg_query($con,$sql);
						$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
					}else{
						$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
						$res = @pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
			if (strlen($msg_erro) == 0) {
				$res = pg_query($con,"COMMIT TRANSACTION");
				$link = $_COOKIE["link"];
				header ("Location: $link&msg_aviso=$msg_aviso");
				exit;
			}else{
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
			$select_acao = '';
		}
	}
}


if ($btn_acao == "acumular") {

	$qtde_os = $_POST["qtde_os"];
	$res = pg_query($con,"BEGIN TRANSACTION");

	$array_os_geo= array();
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
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$sua_os_troca   = pg_fetch_result($res,0,sua_os);
				$os_sedex_troca = '';
				#troca
				$sql = "SELECT os_sedex
							FROM tbl_os_sedex
							WHERE extrato_destino = $extrato
							AND sua_os_destino = '$sua_os_troca'; ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$os_sedex_troca = pg_fetch_result($res, 0,os_sedex);
					#Sedex
					$sql = "UPDATE tbl_os_sedex SET extrato_destino = NULL WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					$sql = "SELECT extrato_lancamento
								FROM tbl_extrato_lancamento
								WHERE extrato = $extrato
								AND   os_sedex = $os_sedex_troca;";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0 AND strlen($msg_erro) == 0){
						$extrato_lancamento_troca = pg_fetch_result($res,0,extrato_lancamento);
						#Extrato lançamento
						$sql = "DELETE FROM tbl_extrato_extra_item WHERE extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

						$sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
			/*OS GEO METAL - 83010*/
			$sql = "SELECT os_numero
					FROM tbl_os
					WHERE os = $x_os
						and tipo_os= 13;";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$os_numero= pg_fetch_result($res,0,os_numero);
				$array_os_geo[$os_numero]= $os_numero;
			}
		}
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $x_os, '$x_obs');";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(strlen($msg_erro)==0) { // HD 52911
			if($login_fabrica == 45) {
				# HD 53003
				$sqlGrAdmin = "UPDATE tbl_os_status set admin = $login_admin
									WHERE extrato = $extrato
									AND   os      = $x_os
									AND   os_status in (
									SELECT os_status
									FROM tbl_os_status
									WHERE extrato = $extrato
									AND os = $x_os
									ORDER BY os_status DESC LIMIT 1); ";
				$resGrAdmin = pg_query($con,$sqlGrAdmin);
				$msg_erro = pg_errormessage($con);

				$sql = "SELECT tbl_os.sua_os,contato_email
						FROM tbl_os
						JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE os=$x_os";
				$res = @pg_query($con,$sql);
				$pr_sua_os        = @pg_fetch_result($res,0,sua_os);
				$pr_contato_email = @pg_fetch_result($res,0,contato_email);

				$sqlx= "SELECT email From tbl_admin WHERE admin = $login_admin";
				$resx = @pg_query($con,$sqlx);
				$admin_email = @pg_fetch_result($resx,0,email);

				if($btn_acao =='excluir') $conteudo_acao ='excluída';
				if($btn_acao =='acumular') $conteudo_acao ='acumulada';
				if($btn_acao =='recusar') $conteudo_acao ='recusada';

				$destinatario = $pr_contato_email;
				$assunto      = " OS $pr_sua_os $conteudo_acao";
				$mensagem = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
				$mensagem .=  "At. Responsável,<br><br>A OS $pr_sua_os foi $conteudo_acao pelo seguinte motivo: <br> $x_obs. <br>";
				$mensagem .="Qualquer duvida contatar a sua atendente regional.<br>";
				$mensagem .="<b><font color='red'>NKS</font></b>";
				$body_top .= "Content-type: text/html;";
				if(strlen($mensagem)>0) mail($destinatario,$assunto,$mensagem,$body_top);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($msg_erro) == 0 and $login_fabrica ==1 and count($array_os_geo)>0) {
			foreach ($array_os_geo as $i => $os_revenda) {
				$sql = "SELECT tbl_os_revenda_item.os_lote as os, 
							tbl_os_extra.extrato
						FROM tbl_os_revenda
						JOIN tbl_os_revenda_item using(os_revenda)
						JOIN tbl_os_extra on tbl_os_extra.os = tbl_os_revenda_item.os_lote
						WHERE tbl_os_revenda.fabrica =$login_fabrica
							AND tbl_os_revenda.os_revenda = $os_revenda
							AND tbl_os_revenda.extrato_revenda =$extrato
							AND tbl_os_extra.extrato = $extrato";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					$msg_erro.="Para a OS GEO: $os_revenda deve ser ACUMULADO todos os produtos juntos.<br>";
				}else{
					$sql = "UPDATE tbl_os_revenda
								SET extrato_revenda = null
							WHERE os_revenda = $os_revenda
								AND extrato_revenda =$extrato;";
					echo "sql: $sql";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}

		$sql = "SELECT posto
				FROM   tbl_extrato
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		if (pg_num_rows($res) > 0) {
			if (@pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){

				#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
				#$res = pg_query($con,$sql);
				#$total_os_extrato = pg_fetch_result($res,0,0);
				#HD15716
				if($login_fabrica == 11){
					$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
					$res = pg_query($con,$sql);
					$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
				}else{
					$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
					//retirado por Sono e Samuel pois não há necessidade de atribuir os valores novamente as OSs
					//$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
					$res = @pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link&msg_aviso=$msg_aviso");
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}


if ($btn_acao == "acumulartudo") {
	if (strlen($extrato) > 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");

		$sql = "SELECT  os     ,
						extrato
				INTO TEMP TABLE tmp_acumula_extrato_$login_fabrica
				FROM tbl_os_extra
				JOIN tbl_extrato USING(extrato)
				WHERE extrato = $extrato
				AND fabrica   = $login_fabrica;";
		$res = pg_query($con,$sql);

		$sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		$sql = "UPDATE tbl_os_status SET admin = $login_admin
				WHERE  tbl_os_status.os        = tmp_acumula_extrato_$login_fabrica.os
				AND    tbl_os_status.extrato   = tmp_acumula_extrato_$login_fabrica.extrato;";
		$res = pg_query($con,$sql);

		if ($login_fabrica==45) {
			if (strlen($msg_erro) == 0) {
				$destinatario = $pr_contato_email;
				$assunto      = " Extrato $extrato";
				$mensagem = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
				$mensagem .=  "At. Responsável,<br><br>A OSs do extrato $extrato foram acumuladas para o próximo mês. <br>";
				$mensagem .="<b><font color='red'>NKS</font></b>";
				$body_top .= "Content-type: text/html;";
				if(strlen($mensagem)>0) mail($destinatario,$assunto,$mensagem,$body_top);
			}
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
			$link = $_COOKIE["link"];
			header ("Location: $link&msg_aviso=$msg_aviso");
			exit;
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "cancelar_extrato") {//24982 - 11/7/2008 - 46967 17/10/2008
	if (strlen($extrato) > 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");

		//EXCLUI A BAIXA NO EXTRATO
		$sql = "SELECT extrato_pagamento
				FROM tbl_extrato_pagamento
				JOIN tbl_extrato USING(extrato)
				WHERE tbl_extrato_pagamento.extrato = $extrato;";
		#echo $sql."<BR>";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			$extrato_pagamento = pg_fetch_result($res, 0, extrato_pagamento);

			$sql="DELETE FROM tbl_extrato_pagamento WHERE extrato_pagamento = $extrato_pagamento AND extrato=$extrato;";
			#echo $sql."<BR>";
			$res = pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		//TIRA O AVULSO DO EXTRATO
		if (strlen($msg_erro) == 0) {
			$sqlA = "SELECT  tbl_extrato_lancamento.extrato_lancamento,
							tbl_extrato_lancamento.extrato
					FROM    tbl_extrato_lancamento
					JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_extrato_lancamento.extrato
					AND tbl_extrato.fabrica = $login_fabrica
					WHERE   tbl_extrato_lancamento.extrato = $extrato
					AND     tbl_extrato_lancamento.fabrica = $login_fabrica;";
			$resA = pg_query($con,$sqlA);

			if(pg_num_rows($resA)>0){
				for($z=0; $z<pg_num_rows($resA); $z++){
					$extrato_lancamento = pg_fetch_result($resA, $z, extrato_lancamento);
					$extrato            = pg_fetch_result($resA, $z, extrato);

					$sqlAv = "UPDATE tbl_extrato_lancamento SET extrato = NULL WHERE extrato_lancamento = $extrato_lancamento AND extrato = $extrato;";
					$resAv = @pg_query($con,$sqlAv);
					$msg_erro = pg_errormessage($con);
				}
			}
		}

		//TIRA AS OSs DO EXTRATO
		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_os_extra SET extrato = NULL
					WHERE  tbl_os_extra.extrato IN(
													SELECT tbl_os_extra.extrato
													FROM tbl_os_extra
													JOIN tbl_os USING(os)
													JOIN tbl_extrato USING(extrato)
													JOIN tbl_extrato_extra USING(extrato)
													WHERE tbl_extrato_extra.baixado IS NULL
													AND   tbl_os.fabrica       = $login_fabrica
													AND   tbl_os_extra.extrato = $extrato
												);";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		//EXCLUI O EXTRATO
		if (strlen($msg_erro) == 0) {
			$sql = "DELETE FROM tbl_extrato WHERE extrato = $extrato AND fabrica = $login_fabrica;";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$res  = pg_query($con,"COMMIT TRANSACTION");
			$link = $_COOKIE["link"];
			header ("Location: extrato_consulta.php");
			exit;
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
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

if(strlen($adiciona_sua_os) > 0 AND ($login_fabrica == 6 OR $login_fabrica == 10 OR $login_fabrica == 11 OR $login_fabrica == 51)) {
	$adiciona_sua_os        = trim($adiciona_sua_os);//pega a sua_os digitada pelo admin

	$sql = "SELECT posto
				FROM tbl_extrato
			WHERE extrato = '$extrato'
			AND fabrica = '$login_fabrica' ";

	$res = pg_query($con,$sql);// Atraves do extrato, busca-se o posto
	$adiciona_posto = pg_fetch_result($res,0,0);//joga o posto na variavel

	if(strlen($adiciona_posto) > 0 AND strlen($msg_erro) == 0){
		$sql = "SELECT DISTINCT os
				FROM tbl_os
				WHERE UPPER(sua_os) = UPPER('$adiciona_sua_os')
				AND fabrica  = '$login_fabrica'
				AND posto    = '$adiciona_posto' ";

		$res             = pg_query($con,$sql);// Se encontrou posto procura pela OS do posto atraves da SUA_OS.
		$adiciona_os     = @pg_fetch_result($res,0,0);

		if(strlen($adiciona_os) > 0 ){

			$sql2 = "SELECT data_fechamento,extrato FROM tbl_os JOIN tbl_os_extra using(os) WHERE os = '$adiciona_os' ";
			$res2 = @pg_query($con,$sql2);
			$adiciona_fechamento  = @pg_fetch_result($res2,0,data_fechamento);//busca a data de fechamento
			$adiciona_extrato_ant = @pg_fetch_result($res2,0,extrato);//busca o extrato caso ja esteja em um extrato

			$sql2 = "SELECT extrato
					FROM tbl_extrato_pagamento
					WHERE extrato = '$adiciona_extrato_ant' ";
			$res2 = @pg_query($con,$sql2);// Verifica se o extrato ja foi dado baixa
			$adiciona_baixado = @pg_fetch_result($res2,0,0);//adiciona o extrato anterior

			if(strlen($adiciona_baixado) > 0 and ($login_fabrica == 6 or $login_fabrica == 10)) {//Verifica se o extrato ja foi dado baixa
				$msg_erro = 'O extrato desta OS já foi dado baixa';
			}

			$sql3 = "SELECT extrato
					FROM tbl_extrato
					WHERE extrato = '$adiciona_extrato_ant'
					AND liberado IS NOT NULL";
			$res3 = @pg_query($con,$sql3);// Verifica se o extrato ja foi liberado
			$adiciona_liberado = @pg_fetch_result($res3,0,0);//adiciona o extrato anterior

			if(strlen($adiciona_liberado) > 0 and $login_fabrica == 11) {//Verifica se o extrato ja foi liberado
				$msg_erro = 'O extrato desta OS já foi liberado';
			}

			if(strlen($adiciona_fechamento) == 0){//verifica se a OS esta fechada
				$msg_erro = 'A OS está aberta. Deve-se estar fechada para entrar no extrato.<br> ';
			}

			if($adiciona_extrato_ant == $extrato){//se estava no extrato anterior
				$msg_erro = " A OS já faz está neste extrato ";
			}

			if( pg_num_rows($res) == 1 AND strlen($msg_erro) == 0 ){

				$res = pg_query ($con,"BEGIN TRANSACTION");

				$sql = "SELECT os_status
							FROM tbl_os_status
						WHERE os    = '$adiciona_os'
						AND extrato = '$extrato' ";

				$res = @pg_query($con,$sql);//Se encontrou a OS verifica se a OS ja foi recusada/excludia/acumulada de algum extrato

				$adiciona_status = @pg_num_rows($res);

				if($adiciona_status > 0){
					$sql = "DELETE FROM tbl_os_status
							WHERE os    = '$adiciona_os'
							AND extrato = '$extrato'
							AND status_os <> '58' ";
					$res = pg_query($con,$sql);//Caso encontre algum registro ele deleta o registro
				}//58 é os_status historio da OS nas movimentacoes entre extratos

				$sql = "SELECT extrato FROM tbl_os_extra WHERE os = '$adiciona_os' ";
				$res = @pg_query($con,$sql);//Busca se a OS participa de algum extrato

				if(@pg_num_rows($res) > 0){
					$extrato_anterior = pg_fetch_result($res,0,0);//caso ja faça parte de um extrato
				}

				$sql = "SELECT os_status, observacao FROM tbl_os_status
						WHERE os = $adiciona_os
						AND   status_os = 58 ";
				$res = pg_query($con,$sql);	//caso haja historico da movimentacao entre extratos,
											//copia para concatenar com a nova movimentacao

				if(pg_num_rows($res) > 0 ){//caso haja da um update na tabela os_status
					$adiciona_observacao = pg_fetch_result($res,0,observacao);
					$adiciona_os_status  = pg_fetch_result($res,0,os_status);

					$adiciona_observacao .= " Saiu do extrato [ $extrato_anterior ] entrou no extrato [ $extrato ]. ";

					$sql2 = "UPDATE tbl_os_status SET observacao = '$adiciona_observacao'
							 WHERE os_status = '$adiciona_os_status'
							 AND   status_os = '58' ";
					$res2 = pg_query($con,$sql2);

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
					$res = pg_query($con,$sql);
				}

				$sql = " UPDATE tbl_os_extra set extrato = $extrato WHERE os = $adiciona_os ";

				$res = pg_query($con,$sql);  //Coloca o novo extrato na OS_EXTRA

				if(strlen($extrato) > 0 AND strlen($msg_erro) == 0 ){
					if(strlen($extrato_anterior) > 0){
						## AGENDAMENTO DE RECALCULO DE EXTRATO ##

						#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato_anterior AND fabrica = $login_fabrica;";
						#$res = pg_query($con,$sql);
						#$total_os_extrato = pg_fetch_result($res,0,0);
						#HD15716
						if($login_fabrica == 11){
							$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato_anterior AND fabrica = $login_fabrica;";
							$res = pg_query($con,$sql);
							$msg_aviso = "Foi agendado o recalculo do extrato $extrato_anterior para esta noite!<br>";
						}else{
							$sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato_anterior');";
							$res = @pg_query ($con,$sql);//Recalcula o extrato anterior, caso exista
							$msg_erro = pg_errormessage($con);
						}
					}

					#$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
					#$res = pg_query($con,$sql);
					#$total_os_extrato = pg_fetch_result($res,0,0);
					#HD15716
					if($login_fabrica == 11){
						$sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
						$res = pg_query($con,$sql);
						$msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
					}else{
						$sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato');";
						$res = @pg_query ($con,$sql);//Recalcula o extrato atual
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$res = pg_query($con,"COMMIT TRANSACTION");
						$link = $_COOKIE["link"];
						header ("Location: $link&msg_aviso=$msg_aviso");
//						header ("Location: extrato_consulta_os.php?extrato=$extrato");
						exit;//Recarrega a página
					}else{
						$res = pg_query($con,"ROLLBACK TRANSACTION");
					}	//caso ocorra erro rollback;
				}else{
						$res = pg_query($con,"ROLLBACK TRANSACTION");
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

		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_os_sedex set finalizada = null
					WHERE os_sedex = $os_sedex
					AND fabrica = $login_fabrica; ";
		$res = pg_query($con,$sql);

		$msg_erro = pg_errormessage($con);

		$sql = "INSERT INTO tbl_os_status (
				os_sedex, status_os, observacao, extrato, admin
			) VALUES (
				$os_sedex, '13', '$obs', $extrato, $login_admin
			);";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		$sql = "DELETE FROM tbl_extrato_extra_item
				WHERE extrato_lancamento = $extrato_lancamento; ";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		$sql = "DELETE FROM tbl_extrato_lancamento
				WHERE os_sedex = $os_sedex
				AND   extrato_lancamento = $extrato_lancamento; ";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		$sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato');";
		$res = @pg_query ($con,$sql);//Recalcula o extrato anterior, caso exista
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro) == 0){
			$res = pg_query($con,"COMMIT TRANSACTION");
			$corpo.="<br>Status: Correto";//e-mail para fernando
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
			$corpo.="<br>Status: Verificar";//e-mail para fernando
		}
	}else{
		$msg_erro = "Não é possível realizar a recusa da OS SEDEX";
	}
}

if ($login_fabrica == 50 or $login_fabrica == 45 or $login_fabrica == 15) {
	if ($_POST["gravar_previsao"] == "Gravar") {
		$data_recebimento_nf = trim($_POST["data_recebimento_nf"]) ;
		$xdata_recebimento_nf = (strlen($data_recebimento_nf) > 0) ? "'$data_recebimento_nf'" : 'NULL';

		if (strlen($_POST["data_recebimento_nf"]) > 0) {
			$data_recebimento_nf = trim($_POST["data_recebimento_nf"]) ;
			$xdata_recebimento_nf = str_replace ("/","",$data_recebimento_nf);
			$xdata_recebimento_nf = str_replace ("-","",$xdata_recebimento_nf);
			$xdata_recebimento_nf = str_replace (".","",$xdata_recebimento_nf);
			$xdata_recebimento_nf = str_replace (" ","",$xdata_recebimento_nf);

			$dia = trim (substr ($xdata_recebimento_nf,0,2));
			$mes = trim (substr ($xdata_recebimento_nf,2,2));
			$ano = trim (substr ($xdata_recebimento_nf,4,4));
			if (strlen ($ano) == 2) $ano = "20" . $ano;

				//-=============Verifica data=================-//

				$verifica = checkdate($mes,$dia,$ano);
				if ( $verifica ==1){
					$xdata_recebimento_nf = $ano . "-" . $mes . "-" . $dia ;
					$xdata_recebimento_nf = "'" . $xdata_recebimento_nf . "'";
				}else{
					$msg_erro="A Data de Pagamento não está em um formato válido";
				}
		}else{
			$xdata_recebimento_nf = "NULL";
			//HD 9387 Paulo 10/12/2007
			$msg_erro.="Por favor, digitar a Data de Recebimento da Nota Fiscal!!!";
		}

		$previsao_pagamento = trim($_POST["previsao_pagamento"]) ;
		if(strlen($previsao_pagamento) > 0){
			$xprevisao_pagamento = "'$previsao_pagamento'";
		}else{
			$xprevisao_pagamento = 'NULL';
		}

		if (strlen($_POST["previsao_pagamento"]) > 0) {
			$previsao_pagamento = trim($_POST["previsao_pagamento"]) ;
			$xprevisao_pagamento = str_replace ("/","",$previsao_pagamento);
			$xprevisao_pagamento = str_replace ("-","",$xprevisao_pagamento);
			$xprevisao_pagamento = str_replace (".","",$xprevisao_pagamento);
			$xprevisao_pagamento = str_replace (" ","",$xprevisao_pagamento);

			$dia = trim (substr ($xprevisao_pagamento,0,2));
			$mes = trim (substr ($xprevisao_pagamento,2,2));
			$ano = trim (substr ($xprevisao_pagamento,4,4));
			if (strlen ($ano) == 2) $ano = "20" . $ano;

			//-=============Verifica data=================-//

			$verifica = checkdate($mes,$dia,$ano);
			if ( $verifica ==1){
				$xprevisao_pagamento = $ano . "-" . $mes . "-" . $dia ;
				$xprevisao_pagamento = "'" . $xprevisao_pagamento . "'";
			}else{
				$msg_erro="A Data de Pagamento não está em um formato válido";
			}
		}else{
			$xprevisao_pagamento = "NULL";
			//HD 9387 Paulo 10/12/2007
			$msg_erro.="Por favor, digitar a Data de Recebimento da Nota Fiscal!!!";
		}

		if (strlen($extrato) > 0) {
			$sql = "UPDATE tbl_extrato SET
						previsao_pagamento  = $xprevisao_pagamento        ,
						data_recebimento_nf = $xdata_recebimento_nf       ,
						admin               = $login_admin
					WHERE extrato       = $extrato
					AND   fabrica       = $login_fabrica";
		}
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}


$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços";
include "cabecalho.php";

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
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
	padding: 0 0 0 50px;
}
</style>
<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script language="JavaScript">

$(function()
{
	$("#data_pagamento").maskedinput("99/99/9999");
	$("#data_recebimento_nf").maskedinput("99/99/9999");
	$("#previsao_pagamento").maskedinput("99/99/9999");
	$("input[name=data_vencimento]").maskedinput("99/99/9999");
	
	$("input[name=valor_total]").numeric({allow:".,"});
	$("input[name=acrescimo]").numeric({allow:".,"});
	$("input[name=desconto]").numeric({allow:".,"});
	$("input[name=valor_liquido]").numeric({allow:".,"});
});
</script>
<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/jquery.editable-1.3.3.js"></script>
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

function selecionarTudo(){
	$('input[@rel=osimprime]').each( function (){
		this.checked = !this.checked;
	});
}

function imprimirSelecionados(){
	var qtde_selecionados = 0;
	var linhas_seleciondas = "";
	$('input[@rel=osimprime]:checked').each( function (){
		if (this.checked){
			linhas_seleciondas = this.value+", "+linhas_seleciondas;
			qtde_selecionados++;
		}
	});

	if (qtde_selecionados>0){
		janela = window.open('os_print_multi.php?osimprime='+linhas_seleciondas,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=850,height=600,top=18,left=0");
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

$(document).ready(function() {
	$('#nota_fiscal_mao_de_obra').editable({
		submit:'Baixar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			$.post(
				'<?=$PHP_SELF?>',
				{
					nota_fiscal_mao_de_obra: valor.current,
					grava_extrato: '<?=$extrato?>'
				}
			)
		}
	});
	$('#emissao_mao_de_obra').editable({
		type:'date',
		submit:'Baixar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			$.post(
				'<?=$PHP_SELF?>',
				{
					emissao_mao_de_obra: valor.current,
					grava_extrato: '<?=$extrato?>'
				}
			)
		}
	});
	$('#valor_total_extrato').editable({
		submit:'Baixar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			$.post(
				'<?=$PHP_SELF?>',
				{
					valor_total_extrato: valor.current,
					grava_extrato: '<?=$extrato?>'
				}
			)
		}
	});
}); 

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

var http_forn = new Array();

function zerar_mo(os,extrato,btn) {

	var botao = document.getElementById(btn);
	var acao  = 'zerar';

	url = "<?=$PHP_SELF?>?ajax=sim&op="+acao+"&os="+escape(os)+"&extrato="+escape(extrato);

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){

		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					alert(response[1]);
					botao.value='MO ZERADA';
					botao.disabled='true';
				}
				if (response[0]=="0"){
					alert(response[1]);
				}

			}
		}
	}
	http_forn[curDateTime].send(null);
}


function mo2(os,extrato,btn,mo) {

	var botao = document.getElementById(btn);
	var mobra = document.getElementById(mo);
	var acao  = 'mo2';

	url = "<?=$PHP_SELF?>?ajax=sim&op="+acao+"&os="+escape(os)+"&extrato="+escape(extrato);

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){

		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					alert(response[1]);
					botao.value='M.O. R$ 2,00';
					botao.disabled='true';
					mobra.value = '2,00';
				}
				if (response[0]=="0"){
					alert(response[1]);
				}

			}
		}
	}
	http_forn[curDateTime].send(null);
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
<? if (strlen ($msg_erro) > 0) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ; $msg_erro = ''; ?>
	</td>
</tr>
</table>
<? }
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
<? } 
echo "<center>";
echo "<FORM METHOD=POST NAME='frm_extrato_os' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='extrato' value='$extrato'>";
echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
echo "<input type='hidden' name='btn_acao' value=''>";

$join_log="";
$case_log=" ";
/*PARA CONSULTAR O LOG DAS OSs FORA DE GARANTIA*/
if($login_fabrica == 11 ){
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
if (strlen($select_acao) == 0 && strlen($extrato)>0) {
	//HD 205958: Este SQL estava sendo executado dentro do laço que verifica as OSs do extrato
	//			 Coloquei fora, pois estava errado
	$sql2 = "SELECT  liberado ,aprovado
			FROM tbl_extrato
			WHERE extrato = $extrato
			AND   fabrica = $login_fabrica";
	$res2 = pg_query($con,$sql2);
	$liberado = pg_fetch_result($res2,0,liberado);
	$aprovado = pg_fetch_result($res2,0,aprovado);

	$sql = "/* Programa: $PHP_SELF ### Fabrica: $login_fabrica ### Admin: $login_admin */
			SELECT      lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
						tbl_os.os                                                                       ,
						tbl_os.sua_os                                                                   ,
						to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data            ,
						to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
						to_char (tbl_os.data_fechamento,'DD/MM/YYYY')                AS fechamento       ,
						to_char (tbl_os.finalizada    ,'DD/MM/YYYY')                 AS finalizada      ,
						tbl_os.consumidor_revenda                                                       ,
						tbl_os.serie                                                                    ,
						tbl_os.codigo_fabricacao                                                        ,
						tbl_os.consumidor_nome                                                          ,
						tbl_os.consumidor_fone                                                          ,
						tbl_os.revenda_nome                                                             ,
						tbl_os.troca_garantia                                                           ,
						tbl_os.data_fechamento                                                          ,
						(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS total_pecas  ,
						tbl_os.mao_de_obra                                           AS total_mo        ,
						tbl_os.qtde_km                                               AS qtde_km         ,
						tbl_os.qtde_km_calculada                                     AS qtde_km_calculada,
						tbl_os.cortesia                                                                 ,
						tbl_os.nota_fiscal                                                              ,
						to_char(tbl_os.data_nf, 'DD/MM/YYYY')                        AS data_nf         ,
						tbl_os.nota_fiscal_saida                                                        ,
						tbl_os.posto                                                                    ,
						tbl_produto.referencia                                                          ,
						tbl_produto.descricao                                                           ,
						tbl_os_extra.extrato                                                            ,
						tbl_os_extra.os_reincidente                                                     ,
						tbl_os.observacao                                                               ,
						tbl_os.motivo_atraso                                                            ,
						tbl_os_extra.motivo_atraso2                                                     ,
						tbl_os_extra.taxa_visita                                                        ,
						tbl_os.obs_reincidencia                                                         ,
						to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
						tbl_extrato.total                                            AS total           ,
						tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
						tbl_extrato.pecas                                            AS pecas           ,
						tbl_extrato.deslocamento                                     AS total_km        ,
						tbl_extrato.admin                                            AS admin_aprovou   ,
						lpad (tbl_extrato.protocolo::text,6,'0')                     AS protocolo       ,
						tbl_posto.nome                                               AS nome_posto      ,
						tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
						tbl_extrato_pagamento.valor_total                                               ,
						tbl_extrato_pagamento.acrescimo                                                 ,
						tbl_extrato_pagamento.desconto                                                  ,
						tbl_extrato_pagamento.valor_liquido                                             ,
						tbl_extrato_pagamento.nf_autorizacao                                            ,
						to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento ,
						to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf ,
						to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
						to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
						tbl_extrato_pagamento.autorizacao_pagto                                         ,
						tbl_posto_fabrica.valor_km                                                      ,
						tbl_extrato_pagamento.obs                                                       ,
						tbl_extrato_pagamento.extrato_pagamento                                         ,
						(SELECT COUNT(*) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.custo_peca = 0 AND tbl_servico_realizado.troca_de_peca IS TRUE) AS peca_sem_preco,
						(SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os and peca_sem_estoque is true limit 1) AS peca_sem_estoque ,
						$case_log
						tbl_os.data_fechamento - tbl_os.data_abertura  as intervalo                     ,
						(SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = $login_fabrica) AS admin,
						tbl_familia.descricao 		as familia_descr,
						tbl_familia.familia	  		as familia_id,
						tbl_familia.codigo_familia 	as familia_cod
			FROM        tbl_extrato
			LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
			LEFT JOIN tbl_os_extra          ON  tbl_os_extra.extrato           = tbl_extrato.extrato
			LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
			$join_log
			LEFT JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
			JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_extrato.posto
			JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica      = $login_fabrica
			LEFT JOIN tbl_familia			ON  tbl_produto.familia			   = tbl_familia.familia
											AND tbl_familia.fabrica			   = $login_fabrica
			WHERE		tbl_extrato.fabrica = $login_fabrica
			AND         tbl_extrato.extrato = $extrato ";
			if($login_fabrica==45){ //HD 39933
			$sql .= "
				AND    tbl_os.mao_de_obra notnull
				AND    tbl_os.pecas       notnull
				AND    ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)) ";
			}

	if($login_fabrica <> 2 && $login_fabrica != 50 ){
		$sql .= "ORDER BY    tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
						replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
	} else if ( $login_fabrica == 50 ) { // HD 107642 (augusto)
		$sql .= "ORDER BY   tbl_familia.descricao ASC,
							tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
							replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
	} else {
		$sql .= " ORDER BY replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC ";
	}
	//echo nl2br($sql);
	if ($login_fabrica == 1 or $login_fabrica == 51){
		$res = pg_query($con,$sql);
		$registros = pg_num_rows($res);
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

		if($login_fabrica == 45) {
			$resxls=pg_query($con,$sql);
		}
	}

	$ja_baixado = false ;
	$rr = 0;
	$reincidencias_os = array();

	if (@pg_num_rows($res) == 0) {
		echo "<h1>Nenhum resultado encontrado.</h1>";
	}else{
		?>
		<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" style="font-size:11px;">
		<tr>
			<td bgcolor="#FFCCCC">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>REINCIDÊNCIAS</b></td>
		</tr>
			<? if($login_fabrica == 51){ ?>
				<tr>
					<td bgcolor="#CCFF99">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>PRODUTO TROCADO</b></td>
				</tr>
			<? } ?>
			<? if($login_fabrica == 30){ ?>
				<tr>
					<td bgcolor="#CCFF99">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>REINCIDÊNCIAS MAIS 90 DIAS</b></td>
				</tr>
			<? } ?>
			<? if (in_array($login_fabrica,array(30,50,85,90,91))) { ?>
				<tr>
					<td bgcolor="#FFCC99">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>INTERVENÇÃO DE KM EM ABERTO</b></td>
				</tr>
			<? } ?>
		<?php
			# HD 62078
			if ($login_fabrica == 45){
				echo "<tr>";
				echo "<td bgcolor='#CCCCFF'>&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td width='100%' valign='middle' align='left'>
					&nbsp;<b>OS COM RESSARCIMENTO FINANCEIRO</b></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td bgcolor='#FFCC66'>&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td width='100%' valign='middle' align='left'>
					&nbsp;<b>OS COM TROCA DE PRODUTO</b></td>";
				echo "</tr>";
			}
		?>

	<? if ($login_fabrica == 2) { // HD 19580 ?>
		<tr><td height="3"></td></tr>
		<tr>
			<td bgcolor="#FFCC00">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>OS FECHADA ATÉ FINAL DE 2007</b></td>
		</tr>
	<? } ?>
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
		<tr><td height="3"></td></tr>
		<tr>
			<td bgcolor="#FFCCFF">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>Reincidências com mesmo produto e nota</b></td>
		</tr>
		<? } ?>
		</table>
		<br>
	<?
		if (strlen ($msg_erro) == 0) {
			$extrato_pagamento         = pg_fetch_result ($res,0,extrato_pagamento) ;
			$valor_total               = pg_fetch_result ($res,0,valor_total) ;
			$acrescimo                 = pg_fetch_result ($res,0,acrescimo) ;
			$desconto                  = pg_fetch_result ($res,0,desconto) ;
			$valor_liquido             = pg_fetch_result ($res,0,valor_liquido) ;
			$nf_autorizacao            = pg_fetch_result ($res,0,nf_autorizacao) ;
			$previsao_pagamento        = pg_fetch_result ($res,0,previsao_pagamento) ;
			$data_vencimento           = pg_fetch_result ($res,0,data_vencimento) ;
			$data_pagamento            = pg_fetch_result ($res,0,data_pagamento) ;
			$obs                       = pg_fetch_result ($res,0,obs) ;
			$autorizacao_pagto         = pg_fetch_result ($res,0,autorizacao_pagto) ;
			$data_recebimento_nf       = pg_fetch_result ($res,0,data_recebimento_nf) ;
			$codigo_posto              = pg_fetch_result ($res,0,codigo_posto) ;
			$posto                     = pg_fetch_result ($res,0,posto) ;
			$protocolo                 = pg_fetch_result ($res,0,protocolo) ;
			$peca_sem_preco            = pg_fetch_result ($res,0,peca_sem_preco) ;
			$admin_aprovou             = pg_fetch_result ($res,0,admin_aprovou) ;
		}

		if($login_fabrica==45){ //HD 26972 - 8/8/2008
			if (strlen($extrato_pagamento) > 0 AND strlen($valor_liquido) > 0 AND strlen($valor_total) > 0 AND strlen($data_vencimento) > 20/2/20090 AND strlen($data_pagamento) > 0){
				$ja_baixado = true;
			}else{
				$ja_baixado = false;
			}
		}else if (strlen($extrato_pagamento) > 0 AND $login_fabrica<>45) 
			$ja_baixado = true;

		if($login_fabrica==45){//HD 39377 12/9/2008
		$sql = "SELECT count(*) as qtde
				FROM tbl_os
				JOIN tbl_os_extra USING(os)
				WHERE tbl_os.mao_de_obra notnull
				and tbl_os.pecas       notnull
				and ((
						SELECT tbl_os_status.status_os
						FROM tbl_os_status
						WHERE tbl_os_status.os = tbl_os.os
						ORDER BY tbl_os_status.data DESC LIMIT 1
						) IS NULL
					OR (SELECT tbl_os_status.status_os
						FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os
						ORDER BY tbl_os_status.data DESC LIMIT 1
						) NOT IN (15)
					)
				and tbl_os_extra.extrato = $extrato";
		}else{
			$sql = "SELECT count(*) as qtde
					FROM   tbl_os_extra
					WHERE  tbl_os_extra.extrato = $extrato";
		}
		$resx = pg_query($con,$sql);

		if (pg_num_rows($resx) > 0) $qtde_os = pg_fetch_result($resx,0,qtde);
		if($login_fabrica == 30) {
		$cols = "colspan='2'";
		}
		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0'>";

		echo"<TR class='menu_top'>";
		echo"<TD align='left'> Extrato: ";
		echo ($login_fabrica == 1) ? $protocolo : $extrato;
		echo "</TD>";
		echo "<TD align='left'> Data: " . pg_fetch_result ($res,0,data_geracao) . "</TD>";
		echo"<TD align='left' $cols> Qtde de OS: ". $qtde_os ."</TD>";
		//HD 31799 esmaltec
		if($login_fabrica == 30 or $login_fabrica == 91) {
		echo"</TR>";
		echo"<TR class='menu_top'>";
		echo"<TD align='left'> Total de Peças: R$ " . number_format(pg_fetch_result ($res,0,pecas),2,",",".") . "</TD>";
		echo"<TD align='left'> Total de MO: R$ " . number_format(pg_fetch_result ($res,0,mao_de_obra),2,",",".") . "</TD>";		
		echo"<TD align='left'> Total de KM: R$ ". number_format(pg_fetch_result ($res,0,total_km),2,",",".") ."</TD>";
		}
		echo"<TD align='left'> Total: R$ " . number_format(pg_fetch_result ($res,0,total),2,",",".") . "</TD>";
		echo"</TR>";

		echo"<TR class='menu_top'>";
		echo"<TD align='left'> Código: " . pg_fetch_result ($res,0,codigo_posto) . " </TD>";
		$cols = ($login_fabrica == 30) ? 6 : 3 ;
		echo"<TD align='left' colspan='$cols'> Posto: " . pg_fetch_result ($res,0,nome_posto) . "  </TD>";
		echo"</TR>";

		if($login_fabrica == 43 and strlen($admin_aprovou)>0 ) {
			$sql = "SELECT nome_completo
					FROM   tbl_admin
					WHERE  admin = $admin_aprovou";
			$res_adm = pg_query($con,$sql);

			if (pg_num_rows($res_adm) > 0){
				$nome_completo = pg_fetch_result($res_adm,0,nome_completo);
				echo"<TR class='menu_top'>";
				echo"<TD align='left' colspan='1'> Admin que aprovou:</TD>";
				echo"<TD align='left' colspan='3'> $nome_completo</TD>";
				echo"</TR>";
			}
		}
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
					WHERE  tbl_os_extra.extrato = $extrato ";
					if($login_fabrica==45){
						$sql .= "
						and    tbl_os.mao_de_obra notnull
						and    tbl_os.pecas       notnull
						and    ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)) ";
					}
					$sql .= " GROUP BY tbl_linha.nome
					ORDER BY count(*)";
			$resx = pg_query($con,$sql);

			if (pg_num_rows($resx) > 0) {
				echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0'>";
				echo "<TR class='menu_top'>";
				echo "<TD align='left'>LINHA</TD>";
				echo "<TD align='center'>QTDE OS</TD>";
				echo "</TR>";

				for ($i = 0 ; $i < pg_num_rows($resx) ; $i++) {
					$linha = trim(pg_fetch_result($resx,$i,nome));
					$qtde  = trim(pg_fetch_result($resx,$i,qtde));

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

	if($ja_baixado == false AND ($login_fabrica == 10 OR ($login_fabrica == 6 AND strlen($liberado)==0) OR ($login_fabrica==11 AND strlen($liberado)==0) OR ($login_fabrica==51 AND strlen($liberado)==0)) ) {
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

	//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
	//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
	//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
	//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
	$libera_acesso_acoes = false;
	if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
		if (strlen($aprovado) == 0) {
			$libera_acesso_acoes = true;
		}
	}
	//HD 205958: Condicional antigo
	elseif ($login_fabrica <> 1){
		$libera_acesso_acoes = true;
	}

	if ($libera_acesso_acoes) {
		$sql = "SELECT pedido
				FROM tbl_pedido
				WHERE pedido_kit_extrato = $extrato
				AND   fabrica            = $login_fabrica";
		$resE = pg_query($con,$sql);
		if (pg_num_rows($resE) == 0)
			echo "<img src='imagens/btn_pedidopecaskit.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedido de Peças do Kit' border='0' style='cursor:pointer;'>";
		echo "<br>";
		echo "<br>";
	}

	//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
	//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
	//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
	//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
	$libera_acesso_acoes = false;
	if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
		if (strlen($aprovado) == 0) {
			$libera_acesso_acoes = true;
		}
	}
	//HD 205958: Condicional antigo
	else {
		$libera_acesso_acoes = true;
	}

	if ($libera_acesso_acoes) {
		$wwsql = " SELECT pedido_faturado
					FROM tbl_posto_fabrica
					JOIN tbl_extrato on tbl_posto_fabrica.posto = tbl_extrato.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_extrato.extrato       = $extrato";
		$wwres = pg_query($con,$wwsql);
		$pedido_faturado = pg_fetch_result($wwres,0,0);

		if ($login_fabrica == 1 or $login_fabrica == 45) { //HD 66773
			echo "<input type='button' value='Acumular todo o extrato' border='0'  onclick=\"javascript: document.frm_extrato_os.btn_acao.value='acumulartudo'; document.frm_extrato_os.submit();\" alt='Clique aqui p/ acumular todas OSs deste Extrato' style='cursor: pointer;'><br><br>";
		}
	}

		if ($login_fabrica == 1) {
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

		if($login_fabrica == 15) { # HD 165932
			$sqlnf = " SELECT nota_fiscal_mao_de_obra,
							to_char(emissao_mao_de_obra,'DD/MM/YYYY') as emissao_mao_de_obra,
							valor_total_extrato
					 FROM tbl_extrato_extra
					 WHERE extrato = $extrato ";
			$resnf = pg_query($con,$sqlnf);
			if(pg_num_rows($res) > 0){
				$nota_fiscal_mao_de_obra= trim(pg_result($resnf,0,nota_fiscal_mao_de_obra)) ;
				$emissao_mao_de_obra    = trim(pg_result($resnf,0,emissao_mao_de_obra)) ;
				$valor_total_extrato    = trim(pg_result($resnf,0,valor_total_extrato)) ;

				if(!empty($nota_fiscal_mao_de_obra)) {
					echo "<br>";
					echo "<table width='750' border='0' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
					echo "<caption class='menu_top'>Clique para alterar</caption>";
					echo "<tr class='menu_top2'>";
					echo "<td nowrap>";
					echo "Nota Fiscal: <div id='nota_fiscal_mao_de_obra'>$nota_fiscal_mao_de_obra</div>";
					echo "</td>";
					echo "<td nowrap>";
					echo "Data Emissão: <div id='emissao_mao_de_obra'>$emissao_mao_de_obra</div>";
					echo "</td>";
					echo "<td nowrap>";
					echo "Valor NF: <div id='valor_total_extrato'>".number_format($valor_total_extrato,2,",",".")."</div>";
					echo "</td>";
					echo "</tr>";
					echo "</table>";
					echo "<br>";
				}
			}
			
		}
		echo "<TABLE width='750' border='0' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";

		if (strlen($msg) > 0) {
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan=10>$msg</TD>\n";
			echo "</TR>\n";
		}

		echo "<TR class='titulo_coluna'>\n";
		//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
		//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
		//			 de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
		//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
		if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
			if (strlen($aprovado) == 0) {
				echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></TD>\n";
			}
		}
		//HD 205958: Rotina antiga
		elseif (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)) echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></TD>\n";

		echo "<TD width='075' >OS</TD>\n";
		echo ($login_fabrica == 1) ? "<TD width='075'>Cód. Fabr.</TD>\n" : "";
		echo "<TD width='075'>Série</TD>\n";
		echo ($login_fabrica <> 1) ? "<TD width='075'>Abertura</TD>\n" : "";
		echo ($login_fabrica == 11 or $login_fabrica ==45 or $login_fabrica == 51) ? "<TD width='075'>Fechamento</TD>\n" : "";
		echo ($login_fabrica == 2) ? "<TD width='075'>Finalizada</TD>\n" : "";
		echo ($login_fabrica == 6) ? "<TD width='50'><ACRONYM TITLE=\"Qtde de dias que a OS ficou aberta\">Dias</ACRONYM></TD>\n" : "";
		echo "<TD width='130'>Consumidor</TD>\n";
		if ($login_fabrica == 11){
			echo "<TD>REVENDA</TD>\n";
			echo "<TD><ACRONYM TITLE=\"Nota Fiscal de Entrada\">NF Entrada</ACRONYM></TD>\n";
			echo "<TD><ACRONYM TITLE=\"Nota Fiscal de Saída\">NF Saída</ACRONYM></TD>\n";
			echo "<TD><ACRONYM TITLE=\"Referência do Produto\">Ref. Prod.</ACRONYM></TD>\n";
			echo "<TD><ACRONYM TITLE=\"Mão de Obra\">M.O.</ACRONYM></TD>\n";
			echo "<TD>ADMIN</TD>\n";
# HD 196633 pediu para tirar a coluna <NÃO PAGA M.O.>
#			echo "<TD><ACRONYM TITLE='Clique no link para não pagar mão de obra do admin na Ordem de serviço'>NÃO PAGA M.O.</ACRONYM></TD>\n";
		} else {
			echo "<TD width='130'>Produto</TD>\n";
			if($login_fabrica == 95){
				echo "<TD width='80'>M.O.</TD>\n";
			}
		}
		if ($login_fabrica == 52) {
			echo "<TD nowrap>Nota Fiscal</TD>\n";
			echo "<TD nowrap>Data NF</TD>\n";
		}
		# HD 45710 17/10/2008 - Permitir selecionar quais OSs imprimir
		echo ($login_fabrica == 15 ) ? "<TD width='30'><a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'> </a></TD>\n" : "";
		echo ($login_fabrica == 51 or $login_fabrica == 74) ? "<TD width='130' nowrap>Mão-de-Obra</TD>\n" : "";
		echo ($login_fabrica ==2 or $login_fabrica == 51) ? "<TD width='130'>AÇÃO</TD>\n" : "";

		if ($login_fabrica == 1 ) {
			echo "<TD width='100'>Revenda</TD>\n";
			echo "<TD width='130'>Total Peça</TD>\n";
			echo "<TD width='130'>Total MO</TD>\n";
			echo "<TD width='130'>Peça + MO</TD>\n";
		}
		if (in_array($login_fabrica,array(30,85,90,91))) {
			echo "<TD width='100'>Revenda</TD>\n";
			echo "<TD width='130'>Total KM</TD>\n";
			echo "<TD width='130'>Total Peça</TD>\n";
			echo "<TD width='130'>Total MO</TD>\n";
			if($login_fabrica == 90) { # HD 310739
				echo "<TD width='130'>Taxa Visita</TD>\n";
			}
			echo "<TD width='130'>KM + Peça + MO</TD>\n";
		}

		if($login_fabrica == 42 || $login_fabrica == 74){
			echo "<TD width='130'>Total Peça</TD>\n";
			echo "<TD width='130'>Total MO</TD>\n";
			echo "<TD width='130'>Peça + MO</TD>\n";
		}

		if ($login_fabrica==50) {
			echo "<TD width='100'>Revenda</TD>\n";
			echo "<TD width='130'>Total KM</TD>\n";
			echo "<TD width='130'>Total MO</TD>\n";
			echo "<TD width='130'>Total KM + MO</TD>\n";
			# HD 36258 - Permitir selecionar quais OSs imprimir
			echo "<TD width='130'><a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'> </a></TD>\n";
		}

		if ($login_fabrica == 30 || $login_fabrica == 50) {
			echo "<TD width='130'>Intervenção KM</TD>\n";
		}


		if ($login_fabrica==52 or $login_fabrica == 24) {
			echo "<TD width='130'>Qtde KM</TD>\n";
			echo "<TD width='130'>Valor por KM</TD>\n";
			echo "<TD width='130'>Total KM</TD>\n";
			echo "<TD width='130'>Total MO</TD>\n";
			
			echo "<TD width='130'>";
				if ($login_fabrica == 52){
					echo "Total KM + MO + PEÇAS";
				} else {
					echo "Total KM + MO";
				}
			echo "</TD>\n";
		}

		echo ($login_fabrica ==6 or $login_fabrica==43) ? "<TD width='130'>Total MO</TD>\n" : "";
		echo ($login_fabrica ==6 AND strlen($liberado)==0) ? "<TD width='130'>Ação</TD>\n" : "";
		echo "</TR>\n";

		if($login_fabrica == 1 ){
			// monta array para ver duplicidade
			$busca_array     = array();
			$localizou_array = array();
			for ($x = 0; $x < pg_num_rows($res); $x++) {
				$nota_fiscal   = trim(pg_fetch_result($res,$x,nota_fiscal));
				if (in_array($nota_fiscal, $busca_array)) {
					$localizou_array[] = $nota_fiscal;
				}
				$busca_array[] = $nota_fiscal;
			}
		}

		$totalizador    = array();
		$ultima_familia = $ultima_familia_exibida = null;

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$os                 = trim(pg_fetch_result ($res,$i,'os'));
			$sua_os             = trim(pg_fetch_result ($res,$i,'sua_os'));
			$data               = trim(pg_fetch_result ($res,$i,'data'));
			$abertura           = trim(pg_fetch_result ($res,$i,'abertura'));
			$fechamento         = trim(pg_fetch_result ($res,$i,'fechamento'));
			$finalizada         = trim(pg_fetch_result ($res,$i,'finalizada'));
			$sua_os             = trim(pg_fetch_result ($res,$i,'sua_os'));
			$serie              = trim(pg_fetch_result ($res,$i,'serie'));
			$codigo_fabricacao  = trim(pg_fetch_result ($res,$i,'codigo_fabricacao'));
			$consumidor_nome    = trim(pg_fetch_result ($res,$i,'consumidor_nome'));
			$consumidor_fone    = trim(pg_fetch_result ($res,$i,'consumidor_fone'));
			$revenda_nome       = trim(pg_fetch_result ($res,$i,'revenda_nome'));
			$produto_nome       = trim(pg_fetch_result ($res,$i,'descricao'));
			$produto_referencia = trim(pg_fetch_result ($res,$i,'referencia'));
			$data_fechamento    = trim(pg_fetch_result ($res,$i,'data_fechamento'));
			$os_reincidente     = trim(pg_fetch_result ($res,$i,'os_reincidente'));
			$codigo_posto       = trim(pg_fetch_result ($res,$i,'codigo_posto'));
			$total_pecas        = trim(pg_fetch_result ($res,$i,'total_pecas'));
			$total_mo           = trim(pg_fetch_result ($res,$i,'total_mo'));
			$qtde_km            = trim(pg_fetch_result ($res,$i,'qtde_km'));
			$valor_km           = trim(pg_fetch_result ($res,$i,'valor_km'));
			$total_km           = trim(pg_fetch_result ($res,$i,'qtde_km_calculada'));
			$taxa_visita        = trim(pg_fetch_result ($res,$i,'taxa_visita'));
			$cortesia           = trim(pg_fetch_result ($res,$i,'cortesia'));
			$peca_sem_preco     = pg_fetch_result ($res,$i,'peca_sem_preco') ;
			$motivo_atraso      = pg_fetch_result ($res,$i,'motivo_atraso') ;
			$motivo_atraso2     = pg_fetch_result ($res,$i,'motivo_atraso2') ;
			$obs_reincidencia   = pg_fetch_result ($res,$i,'obs_reincidencia') ;
			$nota_fiscal        = pg_fetch_result ($res,$i,'nota_fiscal') ;
			$data_nf            = pg_fetch_result ($res,$i,'data_nf') ;
			$nota_fiscal_saida  = pg_fetch_result ($res,$i,'nota_fiscal_saida') ;
			$observacao         = pg_fetch_result ($res,$i,'observacao') ;
			$consumidor_revenda = pg_fetch_result ($res,$i,'consumidor_revenda');
			$peca_sem_estoque   = pg_fetch_result ($res,$i,'peca_sem_estoque');
			$intervalo          = pg_fetch_result ($res,$i,'intervalo');
			$troca_garantia     = pg_fetch_result ($res,$i,'troca_garantia');
			$texto              = "";
			$admin              = pg_fetch_result ($res,$i,'admin');
			// HD 107642 (augusto)
			$familia_descr		= pg_fetch_result($res,$i,'familia_descr');
			$familia_cod		= pg_fetch_result($res,$i,'familia_cod');
			$familia_id			= pg_fetch_result($res,$i,'familia_id');
			
			# HD 340281
			$total_pecas = ($login_fabrica == 90) ? 0 : $total_pecas;

			if ( isset($totalizador[$familia_id]) ) {
				$totalizador[$familia_id]['total_km'] 	+= (float) $total_km;
				$totalizador[$familia_id]['total_mo'] 	+= (float) $total_mo;
				$totalizador[$familia_id]['total'] 		+= (float) $total_km + $total_mo + $total_pecas ;
			} else {
				$totalizador[$familia_id]['descr'] 		 = $familia_descr;
				$totalizador[$familia_id]['total_km'] 	= (float) $total_km;
				$totalizador[$familia_id]['total_mo'] 	= (float) $total_mo;
				$totalizador[$familia_id]['total'] 		= (float) $total_km + $total_mo + $total_pecas ;
			}
			//echo $familia_id,'-',$familia_descr,'_';
			$totalizador['geral']['total_km'] 	+= (float) $total_km;
			$totalizador['geral']['total_mo'] 	+= (float) $total_mo;
			$totalizador['geral']['total'] 		+= (float) $total_km + $total_mo + $total_pecas ;
			$ultima_familia						 = ( is_null($ultima_familia) ) ? $familia_id : $ultima_familia;
			$exibir_total_familia                = (boolean) ( ! is_null($ultima_familia) && $ultima_familia != $familia_id );
			// fim HD 107642
			
			//HD 237498: Aproveitei para fazer esta correção. A sql que busca as OS é a mesma que busca algumas informações do extrato, então sempre traz pelo menos 1 resultado, mas ne sempre tem OS na linha. O correto mesmo seria corrigir a sql principal, mas isso tem que ser feito com MUUUIIITA calma, então está ai o "quebra-galho"
			if (strlen($os) > 0) {
				
				// HD 107642 (augusto)
				if ( $login_fabrica == 50 && $exibir_total_familia ) {
					$ultima_familia_exibida = $ultima_familia;
					?>
					<tr class="menu_top">
						<td colspan="7" align="right"> Total da família (</em><?php echo $totalizador[$ultima_familia]['descr']; ?></em>):  </td>
						<td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_km'],2,',','.'); ?></td>
						<td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_mo'],2,',','.'); ?></td>
						<td align="right"><?php echo number_format($totalizador[$ultima_familia]['total'],2,',','.'); ?></td>
						<td>&nbsp;</td>
					</tr>
					<?php 
				}
				$ultima_familia = (int) $familia_id;
				// fim HD 107642

				if ($peca_sem_estoque =="t"){
					$coloca_botao = "sim";
				}
				if($login_fabrica == 11 ){
					$os_log= trim(pg_fetch_result($res,$i,os_log));
				}

				if($consumidor_revenda=="R" AND ($login_fabrica==6 or $login_fabrica==24 or $login_fabrica==51)) {
					$consumidor_nome = $revenda_nome;
				}

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
				$btn = ($i % 2 == 0) ? "azul" : "amarelo";

				# HD 62078 - OSs com troca mostrar outra cor
				if ($login_fabrica == 45){
					$sqlTroca = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
					$resTroca = pg_query($con,$sqlTroca);
					if(pg_num_rows($resTroca)==1){
						$cor = (pg_fetch_result($resTroca,0,ressarcimento)=='t') ? "#CCCCFF" : "#FFCC66";
					}
				}

			//takashi 1583	if(substr($motivo_atraso,0,34) == 'Esta OS é reincidente pois o posto')$observacao = "Justificativa do Sistema: ".$motivo_atraso;
		//		echo "<div id='justificativa_$i' style='visibility : hidden; position:absolute; width:500px;left: 200px;opacity:.75;' class='Erro'  >$observacao</div>";
				if (strlen($os_reincidente) > 0) {
					//HD 15683
					$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
					$res1 = pg_query ($con,$sql);

					$sqlr="SELECT tbl_os_extra.os_reincidente from tbl_os_extra where os=$os";
					$resr=pg_query($con,$sqlr);

					if(pg_num_rows($resr)>0) $os_reinc=pg_fetch_result($resr,0,os_reincidente);
					if(strlen($os_reinc)>0){
						if($login_fabrica == 1){

							$sqlR = "SELECT tbl_os.sua_os, extrato FROM tbl_os_extra JOIN tbl_os USING(os) WHERE os=$os_reinc";
							$resR = pg_query($con,$sqlR);
							if (pg_num_rows($resR) > 0){
								$sos          = pg_fetch_result ($resR,0,sua_os) ;
								$sextrato     = pg_fetch_result ($resR,0,extrato) ;
								if($sextrato<>$extrato){
									$texto = "-R";
									$cor   = "#FFCCCC";
									if(strlen(trim($obs_reincidencia))==0){$obs_reincidencia = $motivo_atraso;}
									$msg_reincidencia = "<td colspan='8' height='60' style='background-color: $cor; color: #AC1313;' align='left'>$obs_reincidencia</td>"; $msg_2 = "OS anterior:<br><a href='os_press.php?os=$os_reinc' target = '_blank'>$codigo_posto$sos</a><br><span style='font-size:8px'>EM OUTRO EXTRATO</span>";
								}
								else{
									$reincidencias_os[$rr]=$os_reinc;
									if(strlen(trim($obs_reincidencia))==0){$obs_reincidencia = $motivo_atraso;}
									$msg_reincidencia = "<td colspan='8' height='50' style='background-color: $cor; color: #AC1313;' align='left'>$obs_reincidencia</td>";
									$negrito ="<b>";
									$rr++;
								}
							}
						}else{
							$cor = '#FFCCCC';
							if($login_fabrica == 30) { # HD 163277
								$sqls = " SELECT os FROM tbl_os_status where os = $os and status_os=138 ";
								$ress = pg_query($con,$sqls);
								if(pg_num_rows($ress) > 0){
									$cor = "#CCFF99";
								}
							}
						}
					}
				}

				//HD 237498: Legendar as OS que ainda estejam em intervenção de KM
				if (strlen($os) && (in_array($login_fabrica,array(30,50,85,90,91)))) {
					//Verifica se a OS em algum momento entrou em intervenção de KM, status 98 | Aguardando aprovação da KM
					$sql = "
					SELECT
					tbl_os_extra.os

					FROM
					tbl_os_extra
					JOIN tbl_os_status ON tbl_os_extra.os=tbl_os_status.os

					WHERE
					tbl_os_extra.os=$os
					AND tbl_os_status.status_os=98
					";
					$res_km = pg_query($con, $sql);

					if (pg_num_rows($res_km)) {
						//Caso a OS algum dia tenha entrado em intervenção de KM, precisa ser verificado se saiu todas as vezes
						//A OS pode sair da intervenção de KM por um dos status abaixo:
						// 99 | KM Aprovada              
						//100 | KM Aprovada com alteração
						//101 | km Recusada              
						$n_intervencao_km = pg_num_rows($res_km);

						$sql = "
						SELECT
						tbl_os_extra.os

						FROM
						tbl_os_extra
						JOIN tbl_os_status ON tbl_os_extra.os=tbl_os_status.os

						WHERE
						tbl_os_extra.os=$os
						AND tbl_os_status.status_os IN (99, 100, 101)
						";
						$res_km = pg_query($con, $sql);

						$n_saida_intervencao_km = pg_num_rows($res_km);
						
						//Verifica se o número de vezes que saiu da intervenção é menor do que o número de
						//vezes que entrou, ou seja, se falta atender alguma intervenção para as OS deste extrato
						if ($n_saida_intervencao_km < $n_intervencao_km) {
							$cor = "#FFCC99";
							$intervencao_km_os = true;
						}
						else {
							$intervencao_km_os = false;
						}
					}
					else {
						$intervencao_km_os = false;
					}
				}

				for($r=0; $r<$rr ; $r++){
					if($reincidencias_os[$r]==$os) $negrito ="<b>";
				}
				if($login_fabrica == 1){
					if (in_array($nota_fiscal, $localizou_array)) {
						$negrito ="<b>";
					}
				}
				// HD 18816
				if($login_fabrica==1){
					$sqlD = "SELECT os_reincidente, status_os FROM tbl_os JOIN tbl_os_status USING(os) WHERE tbl_os.os = $os AND status_os = 95 AND os_reincidente IS TRUE;";
					$resD = @pg_query($con,$sqlD);
					if(@pg_num_rows($resD) > 0){//HD 47150
						$status_os = pg_fetch_result($resD,0,status_os);
						if($status_os==95) $cor='#FFCCFF';
					}
				}
				if ($login_fabrica == 1 && $cortesia == "t") $cor = "#D7FFE1";
				if ($login_fabrica == 6 and $intervalo>30) $intervalo = "<B><font color='#FF3300'>$intervalo</font></b>";

				if($login_fabrica==2 && strlen($os)){
					$btn_zera="f";
					$sqlc="SELECT os from tbl_os where os=$os and data_fechamento < '2008-01-01 00:00:00'";
					$resc=pg_query($con,$sqlc);
					if(pg_num_rows($resc) > 0){
						$cor='#FFCC00';
						$btn_zera="t";
					}
				}
				if($login_fabrica==51 and $troca_garantia == 't' ){
						$cor='#CCFF99';
				}
				echo "<TR class='table_line' style='background-color: $cor;'>\n";
				//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
				//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
				//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
				//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
				if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
					if (strlen($aprovado) == 0) {
						if ($login_fabrica <> 1){
							$rowspan = "";
						}
						else {
							$rowspan = "rowspan='2'";
						}
						echo "<TD align='center' $rowspan><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
					}
				}
				//HD 205958: Rotina antiga
				elseif ($ja_baixado == false AND $login_fabrica <> 1){
					if (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)) echo "<TD align='center'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
				}elseif($ja_baixado == false){ // HD 2225 takashi colocou esse if($ja_baixado == false) pois se nao fosse fabrica 1 colocava os checks... se estiver com problema tire
					echo "<TD align='center' rowspan='2'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
				}

				if ($login_fabrica == 1 OR $login_fabrica == 7){
					echo ($login_fabrica ==1 ) ? "<TD nowrap rowspan='2' " : "<TD nowrap ";

					if ($peca_sem_estoque =="t" and $pedido_faturado=='t') echo "bgcolor='#d9ce94'";
					echo "><a href=\"javascript:void(0);\" onclick=\"javascript:window.open('detalhe_ordem_servico.php?os=$os','','width=750,height=500,scrollbars=yes');\">";
				}elseif ($login_fabrica==30){
					echo "<TD nowrap><a href=\"javascript:void(0);\" onclick=\"javascript:window.open('detalhe_os_esmaltec.php?os=$os','','width=750,height=500,scrollbars=yes');\">";
					//echo "<TD nowrap><a href='detalhe_os_esmaltec.php?os=$os' target='_blank'>";
				}else{
					echo "<TD nowrap><a href='os_press.php?os=$os' target='_blank'>";
				}
				if ($login_fabrica == 1 and strlen($sua_os)>0) echo $codigo_posto;
				echo $sua_os . $texto . "</a></TD>\n";
				if ($login_fabrica == 1) echo "<TD nowrap>$negrito$codigo_fabricacao</TD>\n";
				echo "<TD nowrap>$serie</TD>\n";
				if ($login_fabrica <> 1) echo "<TD align='center'>$abertura</TD>\n";
				if ($login_fabrica == 11 or $login_fabrica ==45 or $login_fabrica == 51) echo "<TD align='center'>$fechamento</TD>\n";
				if ($login_fabrica == 2) echo "<TD align='center'>$finalizada</TD>\n";
				if ($login_fabrica == 6) echo "<TD align='center'>$intervalo</TD>\n";
				echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17);
				if ($login_fabrica == 1) echo " - ".$consumidor_fone;
				echo "</ACRONYM></TD>\n";

				if($login_fabrica==11 ){
					echo "<TD nowrap>$revenda_nome</TD>";
					echo "<TD nowrap>$nota_fiscal</TD>";
					echo "<TD nowrap>$nota_fiscal_saida</TD>";
					echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$produto_referencia - $produto_nome\">$produto_referencia</ACRONYM></TD>\n";
					echo "<TD align='right' nowrap>$negrito " . number_format($total_mo,2,",",".") . "</TD>\n";
					echo "<TD nowrap>$admin</TD>";

					if(strlen($admin)>0){
						$sqlMO = "SELECT admin_paga_mao_de_obra FROM tbl_os_extra WHERE os = $os";
						$resMO = pg_query($con, $sqlMO);

						$paga_mao_de_obra_admin = pg_fetch_result($resMO,0,admin_paga_mao_de_obra);

						if($paga_mao_de_obra_admin=='t'){
							if(strlen($aprovado)==0){
								echo "<TD>&nbsp;</TD>";
							}else{
	# HD 196633
	#							echo "<TD ALIGN='center' nowrap>
	#								<a #href='$PHP_SELF?extrato=$extrato&os=$os&zerarmo=t&pagina=$pagina'>Não Pagar M.O</a>
	#							</TD>";
							}
						}else{
	# HD 196633
	#						echo "<TD ALIGN='center' nowrap>
	#							M.O Zerada pelo ADMIN
	#						</TD>";
						}
					}else{
						echo "<TD>&nbsp;</TD>";
					}
					if($os_log == $os){
						echo "<td nowrap align='center'> <span class='text_curto'> <a href='os_log.php?os=$os_log' rel='ajuda1' target='blank' title='OS que Posto tentou cadastrar Fora de Garantia<br> Antes confirmar a nota fiscal e Número Série' class='ajuda'>?</a></span></TD>\n";
					}
				}else{
					echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$produto_referencia - $produto_nome\">";
					if ($login_fabrica == 1 OR $login_fabrica == 2) {
						echo $produto_referencia;
					} elseif($login_fabrica == 45){
						echo $produto_nome;
					}else{
						 echo substr($produto_nome,0,17);
					}
					echo "</ACRONYM></TD>\n";
					if($login_fabrica == 95){
						echo "<td align='right'>R$ " . number_format($total_mo,2,",",".") . "</td>";
					}
				}

				if($login_fabrica == 52){
					echo "<TD>$nota_fiscal</TD>";
					echo "<TD>$data_nf</TD>";
				}

				if (in_array($login_fabrica,array(30,50,85,90,1,15,52,24,42,91,74))) {
					$total_os = $total_pecas + $total_mo + $total_km;
					if($login_fabrica == 90) {
						$total_os += $taxa_visita;
					}
					if ($login_fabrica<>15) { 
						if ($login_fabrica <> 52 and $login_fabrica <> 24 and $login_fabrica <> 42 and $login_fabrica <> 74) {
							echo "<TD align='left' nowrap>$negrito<ACRONYM TITLE=\"$revenda_nome\">". substr($revenda_nome,0,17) . "</ACRONYM></TD>\n";
						}

						if ($login_fabrica == 52 or $login_fabrica == 24) {
						echo "<td align='right'>$qtde_km</td>";
						echo "<td align='right'>$valor_km</td>";
						}
						if (in_array($login_fabrica,array(30,50,85,90,52,24,91))) {
							echo "<TD align='right' nowrap>$negrito " ;
							$qtde_km = ($qtde_km>0) ? "Kilometragem: $qtde_km Km" : "&nbsp;";
							echo "<ACRONYM TITLE=\"$qtde_km\">".number_format($total_km,2,",",".")."</ACRONYM>\n";
							echo "</TD>\n";
						}

						if ($login_fabrica==1 OR $login_fabrica==30 OR $login_fabrica==52 or $login_fabrica == 90 or $login_fabrica==85 or $login_fabrica == 42 or $login_fabrica == 91) {
							echo "<TD align='right' nowrap>$negrito " ;
							if ($peca_sem_preco == 0) {
								echo number_format($total_pecas,2,",",".") ;
							}else{
								echo "<font color='#ff0000'><b>SEM PREÇO</b></font>";
							}
							echo "</TD>\n";
						}
						echo "<TD align='right' nowrap>$negrito" . number_format($total_mo,2,",",".") . "</TD>\n";
						if($login_fabrica == 90) {
							echo "<td align='right'>".number_format($taxa_visita,2,",",".")."</td>";
						}
						echo "<TD align='right' nowrap>$negrito" . number_format($total_os,2,",",".") . "</TD>\n";

						//ATENÇÃO: A rotina abaixo redireciona para a tela de auditoria de KM, para que seja auditada a OS com a rotina já existente, CUIDADO AO MODIFICAR
						if (in_array($login_fabrica,array(30,50,85,90,91))) {
							if ($intervencao_km_os) {
								echo "<td align='center' nowrap><a href='aprova_km.php?os=$os&btn_acao=Pesquisar' target='_blank'>VER INTERV KM</a></td>";
							}else{
								echo "<td align='center' nowrap></td>";
							}
						}


					}
					# HD 36258 / HD 45710 17/10/2008
					if ($login_fabrica == 50 OR $login_fabrica == 15){
						$arrayos[] = $os;
						echo "<TD align='center' nowrap><input type='checkbox' name='osimprime[]' id='osimprime' rel='osimprime' value='$arrayos[$i]'></TD>\n";
					}
					if ($login_fabrica<>15) echo "<TD align='right' rowspan='2'>$msg_2</TD>\n"; $msg_2 = '';
				}

				if($login_fabrica==2){
					// HD 19580
					echo "<TD align='center' valign='top' nowrap>";
					if($btn_zera=='t' and $total_mo > 0 and $aprovado ==0){
						echo "<input type='button' name='zerar_$i' id='zerar_$i' value='RECUSAR MO' onClick=\"if (this.value=='Processando...'){ alert('Aguarde');}else {this.value='Processando...'; zerar_mo('$os','$extrato','zerar_$i');}\" >";
					}
					echo "</TD>\n";
				}
				if($login_fabrica==51 or $login_fabrica == 74 ){
					echo "<TD align='right' nowrap>";
						echo "<input type='text' name='mo_$i' id='mo_$i' value='" . number_format($total_mo,2,",",".") . "' readonly>";
					echo "</TD>\n";
				}
				if($login_fabrica==51 and $troca_garantia == 't'){
					// HD 59408
					echo "<TD align='center' valign='top' nowrap>";
						//echo $total_mo;
						if($total_mo<>2){
							echo "<input type='button' name='mo2_$i' id='mo2_$i' value='Pagar M.O. R$ 2,00 para troca' onClick=\"if (this.value=='Processando...'){ alert('Aguarde');}else {this.value='Processando...'; mo2('$os','$extrato','mo2_$i','mo_$i');}\" >";
						}
					echo "</TD>\n";
				}
				if ($login_fabrica ==6 or $login_fabrica==43 ){
					echo "<TD align='right' nowrap>$negrito " . number_format($total_mo,2,",",".") . "</TD>\n";
				}
				if ($login_fabrica ==6 AND strlen($liberado)==0){
					echo "<TD align='center' nowrap>";
					echo "<a href=\"$PHP_SELF?ajax_debito=true&os=$os&keepThis=trueTB_iframe=true&height=400&width=500\" title=\"Manutenção de valores da OS\" class=\"thickbox\">Alterar MO</a>";
					echo "</TD>\n";
				}

				$sqlD = "SELECT status_os FROM tbl_os_status WHERE os = $os and status_os = 91 order by os_status desc limit 1;";
				$resD = @pg_query($con,$sqlD);
				if(@pg_num_rows($resD) > 0){
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
					echo pg_fetch_result ($res,$i,obs_reincidencia) ;
					echo "</td>";
					echo "</TR>\n";
				}

				if ($login_fabrica==6 and strlen($motivo_atraso) > 0){
					echo "<tr  style='background-color: $cor;'>";
					echo "<td ></td>";
					echo "<td colspan='7' align='left'><B>Motivo Atraso:</B> ";
					echo pg_fetch_result ($res,$i,motivo_atraso) ;
					echo "</td>";
					echo "</TR>\n";
				}
				if ($login_fabrica==6 and strlen($motivo_atraso2) > 0){
					echo "<tr  style='background-color: $cor;'>";
					echo "<td ></td>";
					echo "<td colspan='7' align='left'><B>Motivo Atraso 60 dias:</B> ";
					echo pg_fetch_result ($res,$i,motivo_atraso2) ;
					echo "</td>";
					echo "</TR>\n";
				}
				$negrito ="";

				if($login_fabrica == 52 AND strlen($os)>0){
					$sql_peca = "SELECT tbl_peca.descricao              AS peca_descricao   ,
										tbl_peca.referencia             AS peca_referencia  ,
										tbl_servico_realizado.descricao AS servico_descricao
								FROM tbl_os
								JOIN tbl_os_produto USING(os)
								JOIN tbl_os_item    USING(os_produto)
								JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
								JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
								WHERE tbl_os.fabrica = $login_fabrica
								AND   tbl_os.os      = $os";
					$res_peca = pg_query($con,$sql_peca);
					if(pg_numrows($res_peca)>0){
						for($z=0; $z<pg_numrows($res_peca); $z++){
							$peca_descricao    = pg_result($res_peca,$z,peca_descricao);
							$peca_referencia   = pg_result($res_peca,$z,peca_referencia);
							$servico_descricao = pg_result($res_peca,$z,servico_descricao);

							echo "<TR style='background-color: $cor;'>";
								echo "<TD colspan='5'>&nbsp;</TD>";
								echo "<TD colspan='5' style='font-size:10px; text-align:left;'>$peca_referencia - $peca_descricao</TD>";
								echo "<TD colspan='4' style='font-size:10px; text-align:left;'>$servico_descricao</TD>";
							echo "</TR>";
						}
					}
				}
			}	//FIM IF strlen($os) > 0
		}//FIM FOR
		
		// HD 107642 (augusto)
		if ( $login_fabrica == 50 && $familia_id != $ultima_familia_exibida ) {
			?>
			<tr class="menu_top">
				<td colspan="7" align="right"> Total da família (</em><?php echo $totalizador[$ultima_familia]['descr']; ?></em>):  </td>
				<td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_km'],2,',','.'); ?></td>
				<td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_mo'],2,',','.'); ?></td>
				<td align="right"><?php echo number_format($totalizador[$ultima_familia]['total'],2,',','.'); ?></td>
				<td>&nbsp;</td>
			</tr>
			<?php 
		}
		if($login_fabrica == 50) {
		?>
			<tr class="menu_top">
				<td colspan="7" align="right"> Total de todas as famílias:  </td>
				<td align="right"><?php echo number_format($totalizador['geral']['total_km'],2,',','.'); ?></td>
				<td align="right"><?php echo number_format($totalizador['geral']['total_mo'],2,',','.'); ?></td>
				<td align="right"><?php echo number_format($totalizador['geral']['total'],2,',','.'); ?></td>
				<td>&nbsp;</td>
			</tr>
		<?php
		}
		// fim HD 107642

		//HD: 121163 - VAI BLOQUEAR PARA OUTRAS FÁBRICAS TAMBÉM, ESTAVA ERRADA A CONDIÇÃO ABAIXO.
		//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
		//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
		//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
		//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
		$libera_acesso_acoes = false;
		if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
			if (strlen($aprovado) == 0) {
				$libera_acesso_acoes = true;
			}
		}
		//HD 205958: Condicional antigo
		elseif ( (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica <> 6) OR (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica == 6) AND strlen($liberado)==0 ) {
			$libera_acesso_acoes = true;
		}

		if ($libera_acesso_acoes) {
			if ($login_fabrica == 1 or $login_fabrica==30 or $login_fabrica==50) $colspan = 10; else $colspan = 7;
			if ($login_fabrica == 6||$login_fabrica == 42 || $login_fabrica == 74)  $colspan = 9;
			if ($login_fabrica == 2)  $colspan = 8;
			if ($login_fabrica == 24)  $colspan = 11;

			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='$colspan' align='left'> &nbsp; &nbsp; &nbsp; <img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; COM MARCADOS: &nbsp; ";
			echo "<input type='hidden' name='posto' value='$posto'>";
			echo "<select name='select_acao' size='1' class='frm'>";
			echo "<option value=''></option>";
			echo "<option value='RECUSAR'";  if ($_POST["select_acao"] == "RECUSAR")  echo " selected"; echo ">RECUSADO PELO FABRICANTE</option>";
			echo "<option value='EXCLUIR'";  if ($_POST["select_acao"] == "EXCLUIR")  echo " selected"; echo ">EXCLUÍDA PELO FABRICANTE</option>";
			if($login_fabrica <>91 ) { # HD 303959
				echo "<option value='ACUMULAR'"; if ($_POST["select_acao"] == "ACUMULAR") echo " selected"; echo ">ACUMULAR PARA PRÓXIMO EXTRATO</option>";

			}
			if($login_fabrica == 1){
				echo "<option value='RECUSAR_DOCUMENTO'"; if ($_POST["select_acao"] == "RECUSAR_DOCUMENTO") echo " selected"; echo ">PENDÊNCIA DE DOCUMENTO</option>";
			}
			if($login_fabrica == 11 OR $login_fabrica == 66){
				$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 13 AND liberado IS TRUE;";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0) {
					echo "<option value=''>-->RECUSAR OS</option>";

					for($l=0;$l<pg_num_rows($res);$l++){
						$motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
						$motivo        = pg_fetch_result($res,$l,motivo);
						$motivo = substr($motivo,0,50);
						echo "<option value='$motivo_recusa'>$motivo</option>";
					}
				}
				$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 14 AND liberado IS TRUE;";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0) {
					echo "<option value=''>-->ACUMULAR OS</option>";

					for($l=0;$l<pg_num_rows($res);$l++){
						$motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
						$motivo        = pg_fetch_result($res,$l,motivo);
						$motivo = substr($motivo,0,50);
						echo "<option value='$motivo_recusa'>$motivo</option>";
					}
				}
				$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 15 AND liberado IS TRUE;";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0) {
					echo "<option value=''>-->EXCLUIR OS</option>";

					for($l=0;$l<pg_num_rows($res);$l++){
						$motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
						$motivo        = pg_fetch_result($res,$l,motivo);
						$motivo = substr($motivo,0,50);
						echo "<option value='$motivo_recusa'>$motivo</option>";
					}
				}

			}
			echo "</select>";
			echo " &nbsp; <input type='button' value='Continuar' border='0' align='absmiddle' onclick='javascript: document.frm_extrato_os.submit()' style='cursor: pointer;'>";
			echo "</TD>\n";
			echo "</TR>\n";
		}
		echo "<input type='hidden' name='contador' value='$i'>";
		echo "</TABLE>\n";
	}//FIM ELSE

	if ($login_fabrica == 1 or $login_fabrica == 51){
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

if($login_fabrica == 45) { // HD 46595
	if(@pg_num_rows($resxls) >0) {
		flush();
		$data = date ("d/m/Y H:i:s");

		$arquivo_nome     = "extrato-consulta-os-$login_fabrica.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Extrato Consulta - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<TABLE width='750' border='0' align='center' cellspacing='1' cellpadding='0'>");
		fputs ($fp,"<TR class='menu_top'>");
		fputs ($fp,"<TD align='left'> Extrato: ");
		fputs ($fp,pg_fetch_result($resxls,0,extrato));
		fputs ($fp,"</TD>");
		fputs ($fp,"<TD align='left'> Data: " . pg_fetch_result ($resxls,0,data_geracao) . "</TD>");
		fputs ($fp,"<TD align='left'> Qtde de OS: ". $qtde_os ."</TD>");
		fputs ($fp,"<TD align='left'> Total: R$ " . number_format(pg_fetch_result ($resxls,0,total),2,",",".") . "</TD>");
		fputs ($fp,"</TR>");
		fputs ($fp,"<TR class='menu_top'>");
		fputs ($fp,"<TD align='left'> Código: " . pg_fetch_result ($resxls,0,codigo_posto) . " </TD>");
		fputs ($fp,"<TD align='left' colspan='3'> Posto: " . pg_fetch_result ($resxls,0,nome_posto) . "  </TD>");
		fputs ($fp,"</TR>");
		fputs ($fp,"</TABLE>");
		fputs ($fp,"<br>");

		fputs ($fp,"<TABLE width='750' align='center' border='1' cellspacing='1' cellpadding='1'>");
		fputs ($fp,"<caption>Relação de Ordens de Serviços</caption>");
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>OS</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>SÉRIE</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>ABERTURA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>FECHAMENTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>CONSUMIDOR</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>PRODUTO</b></td>");
		fputs ($fp,"</tr>");

		for($i=0;$i<pg_num_rows($resxls);$i++){
			$os                 = trim(pg_fetch_result ($resxls,$i,os));
			$sua_os             = trim(pg_fetch_result ($resxls,$i,sua_os));
			$data               = trim(pg_fetch_result ($resxls,$i,data));
			$abertura           = trim(pg_fetch_result ($resxls,$i,abertura));
			$fechamento         = trim(pg_fetch_result ($resxls,$i,fechamento));
			$serie              = trim(pg_fetch_result ($resxls,$i,serie));
			$consumidor_nome    = trim(pg_fetch_result ($resxls,$i,consumidor_nome));
			$produto_nome       = trim(pg_fetch_result ($resxls,$i,descricao));
			$produto_referencia = trim(pg_fetch_result ($resxls,$i,referencia));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			$sqlr="SELECT tbl_os_extra.os_reincidente from tbl_os_extra where os=$os";
			$resr=pg_query($con,$sqlr);
			if(pg_num_rows($resr)>0)  $os_reinc=pg_fetch_result($resr,0,os_reincidente);
			if(strlen($os_reinc) > 0) {
				$cor="#FFCCCC";
			}

			fputs ($fp,"<TR bgcolor='$cor'>\n");
			fputs ($fp,"<TD>".$sua_os."</TD>\n");
			fputs ($fp,"<TD nowrap align='center'>$serie</TD>\n");
			fputs ($fp,"<TD align='center'>$fechamento</TD>\n");
			fputs ($fp,"<TD align='center'>$abertura</TD>\n");
			fputs ($fp,"<TD nowrap>$consumidor_nome</TD>\n");
			fputs ($fp,"<TD nowrap>");
			fputs ($fp,$produto_referencia . "  -  " . $produto_nome);
			fputs ($fp,"</TD>\n");
			fputs ($fp,"</tr>");
		}

		fputs ($fp,"</table>");

		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo ` cp $arquivo_completo_tmp $path `;
		$data = date("Y-m-d").".".date("H-i-s");

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
		echo "<br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/extrato-consulta-os-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}




if($login_fabrica == 51 or $login_fabrica==81){
	echo "<br><a href='lote_capa_conferencia_gama.php?extrato=$extrato&linhas=$registros' target='_BLANK'>CONFERIR LOTE</a><br>";
}

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
				$res_doc = pg_query($con,$sql_doc);
				$codigo_posto = pg_fetch_result($res_doc,0,codigo_posto);
				$data_geracao = pg_fetch_result($res_doc,0,data_geracao);
				$protocolo    = pg_fetch_result($res_doc,0,protocolo);
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

	$res = pg_query($con,$sql);

	$descricao = pg_fetch_result($res,0,descricao);
	$valor     = pg_fetch_result($res,0,valor);
	$os_sedex  = pg_fetch_result($res,0,os_sedex);

	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>\n";
	echo "<INPUT TYPE='hidden' NAME='os_sedex' value='$os_sedex'>";
	echo "<INPUT TYPE='hidden' NAME='extrato_lancamento' value='$lancamento'>";
	echo "<tr class='titulo_tabela'>\n";
	echo "<td colspan='2' align='left' style='color: #FFCC00'>RECUSA DE OS SEDEX</td>";
	echo "</tr>\n";
	echo "<tr class='subtitulo'>\n";
		echo "<td colspan='2' style='font-size: 10px'>Descrição: $descricao - Valor: R$ $valor</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
		echo "<td>OS SEDEX</td>\n";
		echo "<td>OBSERVAÇÃO</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
		echo "<td align='center'>$os_sedex</td>";
		echo "<td align='center'><INPUT TYPE=\"text\" size='100' NAME='descricao' class='frm'></td>";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
		echo "<td colspan='2'><INPUT TYPE=\"submit\" name='recusa_sedex' value='Recusar' class='frm'></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

echo "<br />";

if ($login_fabrica == 11) {//HD 226679
	
	$sql_obs = "SELECT obs FROM tbl_extrato_extra WHERE extrato = " . abs($extrato);
	$res_obs = pg_query($con, $sql_obs);

	if (pg_num_rows($res_obs)) {
		$obs_extrato = pg_fetch_result($res_obs, 0, obs);
	} else {
		$obs_extrato = '';
	}
	
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>\n";
		echo "<tr class='titulo_tabela'>\n";
			echo "<td height='20px'><label for='obs_extrato' title='Neste campo você poderá gravar contatos feitos com o PA (Posto de Atendimento) ou alguma observação importante do extrato.'>Observação</label></td>\n";
		echo "</tr>\n";
		echo "<tr >\n";
			echo "<td align='center' style='padding:10px 0 10px 0 ;'><textarea name='obs_extrato' id='obs_extrato' cols='100' rows='5' class='frm'>$obs_extrato</textarea></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
			echo "<td align='center'><input type='submit' name='btn_obs' value='Enviar OBS' /></td>\n";
		echo "</tr>\n";
	echo "</table>\n";
	echo "<br />";

}

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
	}
}
$res_avulso = pg_query($con,$sql);

if (pg_num_rows($res_avulso) > 0) {//hd 9482
	if ($login_fabrica == 1 OR $login_fabrica == 10 OR $login_fabrica == 6 or $login_fabrica == 45) $colspan = 6;
	else                     $colspan = 4;
	//HD 227632: Habilitar excluir para a Lenoxx
	if ($login_fabrica == 11) {
		$colspan = 5;
	}
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";
	echo "<tr class='titulo_tabela'>\n";
	echo "<td colspan='$colspan'>LANÇAMENTO DE EXTRATO AVULSO</td>\n";
	echo "</tr>\n";
	echo "<tr class='titulo_coluna'>\n";
	echo "<td>Descrição</td>\n";
	echo "<td>Histórico</td>\n";
	echo "<td>Valor</td>\n";
	echo "<td>Automético</td>\n";//hd 9482
	if ($login_fabrica == 1 OR $login_fabrica == 10 OR $login_fabrica == 6 or $login_fabrica == 45){
		echo "<td>Admin</td>\n";
		if ( ($login_fabrica==6 and strlen($liberado)==0) OR $login_fabrica<>6 )
		echo "<td>Ações</td>\n";
	}
	//HD 227632: Habilitar excluir para a Lenoxx
	if ($login_fabrica == 11) {
		echo "<td>Ações</td>\n";
	}
	echo "</tr>\n";
	$sqly = "SELECT to_char(data_envio,'DD/MM/YYYY') as data_envio
				FROM tbl_extrato_financeiro
				WHERE extrato = $extrato
				LIMIT 1;";
	$resy = @pg_query($con,$sqly);
	if(@pg_num_rows($resy) > 0){
		$data_envio_financeiro = @pg_fetch_result($resy, 0, data_envio);
	}

	for ($j = 0 ; $j < pg_num_rows($res_avulso) ; $j++) {
		$cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		$descricao               = pg_fetch_result($res_avulso, $j, descricao);
		$historico               = pg_fetch_result($res_avulso, $j, historico);
		$os_sedex                = pg_fetch_result($res_avulso, $j, os_sedex);
		$extrato_lancamento      = pg_fetch_result($res_avulso, $j, extrato_lancamento);
		$obs_sedex               = @pg_fetch_result($res_avulso, $j, obs);

		$sedex_faturada = stristr($obs_sedex, 'faturada');
		if(strlen($sedex_faturada) > 0){
			$descricao = "TROCA FATURADA";
		}

		$sedex_faturada = stristr($obs_sedex, 'Débito');
		if(strlen($sedex_faturada) > 0 AND $login_fabrica==1){ //HD 57068
			$descricao = $obs_sedex;
		}

		//hd 9482
		if($login_fabrica == 6 or $login_fabrica == 45){
				$descricao          = @pg_fetch_result($res_avulso, $j, descricao_lancamento);
				$historico          = @pg_fetch_result($res_avulso, $j, historico_lancamento);
				$admin              = pg_fetch_result($res_avulso, $j, login);
		}
		if($login_fabrica == 1){
			$sua_os_destino     = @pg_fetch_result($res_avulso, $j, sua_os_destino);
			$sua_os_origem      = @pg_fetch_result($res_avulso, $j, sua_os_origem);

			if($sua_os_destino == "CR"){
				$sql = "SELECT tbl_os.sua_os FROM tbl_os WHERE os = '$sua_os_origem' AND fabrica = $login_fabrica ;";
				$res = pg_query($con,$sql);
				$descricao = "CR " . $codigo_posto;
				$descricao .= pg_fetch_result($res,0,sua_os);
			}
		}

		if ($login_fabrica == 1 OR $login_fabrica == 10){
			if (strlen($os_sedex) == 0){
				$descricao          = @pg_fetch_result($res_avulso, $j, descricao_lancamento);
				$historico          = @pg_fetch_result($res_avulso, $j, historico_lancamento);
			}
			$admin              = pg_fetch_result($res_avulso, $j, login);
		}

		$historico = str_replace("\n", "<br>", $historico);

		echo "<tr height='18' class='table_line' style='background-color: $cor;'>\n";
		echo "<td width='35%'>" . $descricao . "</td>";
		echo "<td width='35%' nowrap>" . $historico . "</td>";
		echo "<td width='10%' align='right' nowrap>  " . number_format( pg_fetch_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";

		echo "<td width='10%' align='center' nowrap>" ;
		echo (pg_fetch_result($res_avulso, $j, automatico) == 't') ? "S" : "&nbsp;";
		echo "</td>";
		//hd 9482
		if($login_fabrica == 1 OR $login_fabrica == 10 or $login_fabrica==6 or $login_fabrica == 45){
			echo (strlen($admin) > 0 ) ? "<td>". $admin ."</td>" : "<td>&nbsp;</td>";
		}
		if (($login_fabrica == 1 OR $login_fabrica == 10) AND strlen($os_sedex) > 0){
			echo "<td width='10%' align='center' nowrap >";
			echo "<a href='sedex_finalizada.php?os_sedex=" . $os_sedex . "' target='_blank'><img border='0' src='imagens/btn_consulta.gif' style='cursor: pointer;' alt='Consultar OS Sedex'></a>";
			echo "&nbsp;&nbsp;";
			echo "<INPUT TYPE=\"hidden\" NAME=\"lancamento\" value='$extrato_lancamento' >";
			if(strlen($data_envio_financeiro)>0 and $login_fabrica == 1){
				echo "<span TITLE='Extrato não pode recusar porque já foi enviado para o financeiro no dia $data_envio_financeiro!'>Financeiro</span>";
			}else{
				echo "<a href='extrato_consulta_os.php?extrato=$extrato&lancamento=" . $extrato_lancamento . "' ><img border='0' src='imagens/btn_recusar.gif' style='cursor: hand;' alt='Recusa OS SEDEX'></a>";
			}
			//echo "<a href='javascript:Excluir($extrato,$os_sedex)'><img src='imagens/btn_excluir.gif'></a>";
			echo "</td>";
		}//hd 9482


		//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
		//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
		//			 de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
		//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
		$libera_acesso_acoes = false;
		if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
			if (strlen($aprovado) == 0) {
				$libera_acesso_acoes = true;
			}
		}
		//HD 205958: Condicional antigo
		//HD 227632: Habilitar excluir para a Lenoxx
		elseif (($login_fabrica == 6  OR $login_fabrica == 45 || $login_fabrica == 11)  AND strlen($liberado)==0){
			$libera_acesso_acoes = true;
		}

		if ($libera_acesso_acoes) {
			echo "<td width='10%' align='center' nowrap>";
			echo "<INPUT TYPE=\"hidden\" NAME=\"lancamento\" value='$extrato_lancamento'>";
			echo "<a href='$PHP_SELF?acao=apagar&extrato=$extrato&xlancamento=" . $extrato_lancamento . "' ><img border='0' src='imagens/btn_recusar.gif' style='cursor: hand;' alt='Excluir Avulso' onclick='return(confirm(\"Excluir o lançamento selecionado?\"))'></a>";
			//echo "<a href='javascript:Excluir($extrato,$os_sedex)'><img src='imagens/btn_excluir.gif'></a>";
			echo "</td>";
		}
		else {
			echo "<td width='10%' align='center' nowrap>";
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
	$wres = pg_query($con,$wsql);
	if(pg_num_rows($wres)>0){
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
		for($i=0;pg_num_rows($wres)>$i;$i++){
			$sua_os     = pg_fetch_result($wres,$i,sua_os);
			$data       = pg_fetch_result($wres,$i,data);
			$observacao = pg_fetch_result($wres,$i,observacao);
			$login = pg_fetch_result($wres,$i,login);

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

if($login_fabrica==45){
	if (strlen($posto) >0){
		$sql = "SELECT  tbl_excecao_mobra.excecao_mobra ,
					tbl_posto_fabrica.codigo_posto          ,
					tbl_posto.cnpj                          ,
					tbl_posto.nome                          ,
					tbl_produto.produto                     ,
					tbl_produto.referencia                  ,
					tbl_produto.descricao                   ,
					tbl_linha.nome              AS linha    ,
					tbl_excecao_mobra.familia                ,
					tbl_familia.descricao AS familia_descricao,
					tbl_excecao_mobra.mao_de_obra           ,
					tbl_excecao_mobra.adicional_mao_de_obra ,
					tbl_excecao_mobra.percentual_mao_de_obra
				FROM    tbl_excecao_mobra
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_excecao_mobra.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN    tbl_posto            ON tbl_posto.posto           = tbl_posto_fabrica.posto
				LEFT JOIN tbl_produto        ON tbl_produto.produto       = tbl_excecao_mobra.produto
				LEFT JOIN tbl_linha AS l1    ON l1.linha                  = tbl_produto.linha
				AND l1.fabrica                = $login_fabrica
				LEFT JOIN tbl_familia AS ff    ON ff.familia               = tbl_produto.familia
				AND l1.fabrica                = $login_fabrica
				LEFT JOIN tbl_linha          ON tbl_linha.linha           = tbl_excecao_mobra.linha
				AND tbl_linha.fabrica         = $login_fabrica
				LEFT JOIN tbl_familia          ON tbl_familia.familia           = tbl_excecao_mobra.familia
				AND tbl_familia.fabrica         = $login_fabrica
				WHERE   tbl_excecao_mobra.fabrica = $login_fabrica
				AND     tbl_excecao_mobra.posto   = $posto
				ORDER BY tbl_posto.nome;";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>";
			echo "<tr>";
			echo "<td  class='titulo_tabela' align='center' colspan='6'>Exceção de Mão de Obra</td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna'>";
			echo "<td align='center'>Linha</td>";
			echo "<td align='center'>Família</td>";
			echo "<td align='center'>Produto</td>";
			echo "<td align='center'>Mão-de-Obra</td>";
			echo "<td align='center'>Adicional</td>";
			echo "<td align='center'>Percentual</td>";
			echo "</tr>";

			for ($z = 0 ; $z < pg_num_rows($res) ; $z++){
				$cor = ($z % 2 == 0) ? '#F1F4FA' : '#E2E9F5';

				$excecao_mobra    = trim(pg_fetch_result($res,$z,excecao_mobra));
				$cnpj             = trim(pg_fetch_result($res,$z,cnpj));
				$cnpj             = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
				$codigo_posto     = trim(pg_fetch_result($res,$z,codigo_posto));
				$posto            = trim(pg_fetch_result($res,$z,nome));
				$produto          = trim(pg_fetch_result($res,$z,produto));
				$produto_descricao= trim(pg_fetch_result($res,$z,referencia)) ."-". trim(pg_fetch_result($res,$z,descricao));
				$linha            = trim(pg_fetch_result($res,$z,linha));
				$familia           = trim(pg_fetch_result($res,$z,familia));
				$familia_descricao = trim(pg_fetch_result($res,$z,familia_descricao));
				if (strlen($familia_descricao) == 0) $familia_descricao = "<i style='color: #959595'>TODAS</i>";
				$mobra            = trim(pg_fetch_result($res,$z,mao_de_obra));
				$adicional_mobra  = trim(pg_fetch_result($res,$z,adicional_mao_de_obra));
				$percentual_mobra = trim(pg_fetch_result($res,$z,percentual_mao_de_obra));

				if(strlen($linha) > 0){
					$familia_descricao = "<i style='color: #959595;'>TODAS DA LINHA ESCOLHIDA</i>";
					$produto_descricao = "<i style='color: #959595'>TODOS DA FAMILIA ESCOLHIDA</i>";
				}

				if(strlen($familia) > 0){
					$linha             = "&nbsp;";
					$produto_descricao = "<i style='color: #959595'>TODOS DA FAMILIA ESCOLHIDA</i>";
				}

				if(strlen($produto) > 0){
					$linha             = "&nbsp;";
					$familia           = "&nbsp;";
				}

				if(strlen($linha) == 0 AND strlen($familia) == 0 AND strlen($produto) == 0){
					$linha             = "<i style='color: #959595;'>TODAS</i>";
					$familia_descricao = "<i style='color: #959595;'>TODAS DA LINHA ESCOLHIDA</i>";
					$produto_descricao = "<i style='color: #959595;'>TODOS DA FAMILIA ESCOLHIDA</i>";
				}

				echo "<tr>";

				echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$linha</font></td>";
				echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$familia_descricao</font></td>";
				echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$produto_descricao</font></td>";
				echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($mobra,2,",",".") ."</font></td>";
				echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($adicional_mobra,2,",",".") ."</font></td>";
				echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($percentual_mobra,2,",",".") ."</font></td>";

				echo "</tr>";
			}
			echo "</table>";
		}
	}
}

##### VERIFICA BAIXA MANUAL #####
$sql = "SELECT posicao_pagamento_extrato_automatico
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica;";
$res = pg_query($con,$sql);
$posicao_pagamento_extrato_automatico = pg_fetch_result($res,0,posicao_pagamento_extrato_automatico);

if ($posicao_pagamento_extrato_automatico == 'f' and $login_fabrica <> 1) {
?>

<HR WIDTH='600' ALIGN='CENTER'>
<? if ($login_fabrica == 50 or $login_fabrica == 45 or $login_fabrica == 15) {?>
	<br>
	<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario'>
		<TR class='titulo_tabela'>
			<TD height='20' colspan='3'>Previsão de Pagamento</TD>
		</TR>
		<TR>
			<TD align='left' class='espaco'>Data de Chegada</TD>
			<TD align='left'>Data Prevista de Pagamento</TD>
			<TD align='left' >Ações</TD>
		</TR>
		<TR>
			<TD class='espaco'>
				<?
				echo "<INPUT TYPE='text' NAME='data_recebimento_nf'  size='12' maxlength='10' value='" . $data_recebimento_nf . "' class='frm' id='data_recebimento_nf'>";
				?>
			</TD>
			<TD>
				<?
				echo "<INPUT TYPE='text' NAME='previsao_pagamento'  size='12' maxlength='10' value='" . $previsao_pagamento . "' class='frm' id='previsao_pagamento'>";
				?>
			</TD>
			<TD>
				<?
				echo "<INPUT TYPE='submit' NAME='gravar_previsao' size='10' maxlength='20' value='Gravar' >";
				?>
			</TD>
		</TR>
	</TABLE>
<?}
if($login_fabrica == 30){?>
	<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario'>
		<TR>
			<TD align='left' class='espaco'>Data Recebimento NF</TD>
		</TR>
		<TR>
			<TD class='espaco'>
				<?
					if ($ja_baixado == false){
						echo "<INPUT TYPE='text' NAME='data_recebimento_nf'  size='10' maxlength='11' value='" . $data_recebimento_nf . "' class='frm'>";
					}else{
						echo $data_recebimento_nf;
					}
				?>
			</TD>
		</TR>
	</TABLE>
<? } ?>
<BR>

<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario'>
<TR class='titulo_tabela'>
	<TD height='20' colspan='4'>Pagamento</TD>
</TR>
<tr><td colspan='4'>&nbsp;</td></tr>
<TR>
	<TD align='left' class='espaco'>Valor Total (R$)</TD>
	<TD align='left'>Acréscimo (R$)</TD>
	<TD align='left'>Desconto (R$)</TD>
	<TD align='left'>Valor Líquido (R$)</TD>
</TR>

<TR>
	<TD class='espaco'>
<?
	if($login_fabrica==45 or $login_fabrica==50) echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_total'  id=''valor_total' size='12' maxlength='10' value='" . $valor_total . "' class='frm'>";
	else                      echo number_format($valor_total,2,',','.');
?>
	</TD>
	<TD>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='acrescimo'  size='12' maxlength='10' value='" . $acrescimo . "' class='frm'>";
	else                      echo number_format($acrescimo,2,',','.');
?>
	</TD>
	<TD>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='desconto'  size='12' maxlength='10' value='" . $desconto . "' class='frm'>";
	else                      echo number_format($desconto,2,',','.');
?>
	</TD>
	<TD>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_liquido'  size='10' maxlength='10' value='" . $valor_liquido . "' class='frm'>";
	else                      echo number_format($valor_liquido,2,',','.');
?>
	</TD>
</TR>

<TR>
	<TD align='left' class='espaco'>Data de Vencimento</TD>
	<TD align='left'>Nº Nota Fiscal</TD>
	<TD align='left'>
	<?
		if($login_fabrica==43){//HD 84828
			echo "Data Prevista de Pagamento";
		}else{
			echo "Data de Pagamento";
		}
	?>
	</TD>
	<TD align='left'>Autorização Nº</TD>
</TR>

<TR>
	<TD class='espaco'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_vencimento'  size='12' maxlength='10' value='" . $data_vencimento . "' class='frm'>";
	else                      echo $data_vencimento;
?>
	</TD>
	<TD >
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='nf_autorizacao'  size='10' maxlength='20' value='" . $nf_autorizacao . "' class='frm'>";
	else                      echo $nf_autorizacao;
?>
	</TD>
	<TD >
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_pagamento'  size='12' maxlength='10' id='data_pagamento' value='" . $data_pagamento . "' class='frm'>";
	else                      echo $data_pagamento;
?>
	</TD>
	<TD >
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='autorizacao_pagto' size='10' maxlength='20' value='" . $autorizacao_pagto . "' class='frm'>";
	else                      echo $autorizacao_pagto;
?>
	</TD>
</TR>
<? if($login_fabrica == 30){?>
<TR>
	<TD class='espaco'>Data Recebimento NF</TD>
</TR>
<TR>
	<TD class='espaco'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_recebimento_nf'  size='10' maxlength='11' value='" . $data_recebimento_nf . "' class='frm'>";
	else                      echo $data_recebimento_nf;
?>
	</TD>
</TR>
<? } ?>
<TR>
	<TD  colspan='4' class='espaco'>Observação</TD>
</TR>
<TR>
	<TD colspan='4' class='espaco'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='obs'  size='96' maxlength='255' value='" . $obs . "' class='frm'>";
	else                      echo $obs;
?>
	</TD>
</TR>
<tr><td colspan='4'>&nbsp;</td></tr>
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
	echo"	<TD ALIGN='center'><input type='button' value='Baixar' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { if (window.opener){window.opener.refreshTela(5000);} document.frm_extrato_os.btn_acao.value='baixar' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Baixar' border='0' style='cursor:pointer;'></TD>";
	echo"</TR>";
	echo"</TABLE>";
}else{
	//HD 18066
	//Fabrica 24 HD 22758
	if($login_fabrica == 24 OR $login_admin==903){
		echo "<input type='hidden' name='extrato' value='$extrato'>";
		echo"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
		echo"<TR>";
		echo "<td align='center'><input type='button' name='excluir_baixa' value='Excluir Baixa' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) {
			if (window.opener){window.opener.refreshTela(5000);} document.frm_extrato_os.btn_acao.value='excluir_baixa' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\"></td>";
		echo"</TR>";
		echo"</TABLE>";
	}
}

} // fecha verificação se fábrica usa baixa manual
	
if ($login_fabrica == 11) { //24982 10/7/2008
		$sql = "SELECT  aprovado,
						liberado
					FROM tbl_extrato
					WHERE extrato = $extrato
					AND   fabrica = $login_fabrica
					AND   aprovado IS NULL
					AND   liberado IS NULL";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)==1){
	?>
		<BR>
		<input type='button' value='Cancela' style="cursor:pointer" onclick="
			javascript:
			if (document.frm_extrato_os.btn_acao.value == '' ) {
				if(confirm('Deseja realmente cancelar o Extrato?') == true) {
					document.frm_extrato_os.btn_acao.value='cancelar_extrato'; document.frm_extrato_os.submit();
				}else{
					return;
				};
			}" ALT="Cancelar Extrato" border='0'>
<?	}
}?>

</FORM>
</center>
<br>

<center>
<input type='button' value='Imprimir' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;'>

<? # HD 36258
	if ($login_fabrica == 50 OR $login_fabrica == 15) {
		?>
		<input type='button' value='Imprimir' onclick="javascript: imprimirSelecionados()" ALT='Imprimir' border='0' style='cursor:pointer;'>
<?	}

if ($login_fabrica == 1) { ?>

<input type='button' value='Imprimir Simplificado' onclick="javascript: window.open('os_extrato_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Simplificado' border='0' style='cursor:pointer;'>

<input type='button' value='Imprimir Detalhado' onclick="javascript: window.open('os_extrato_detalhe_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Detalhado' border='0' style='cursor:pointer;'>

<? if($coloca_botao == "sim"){ ?>
<img src='imagens/btn_pecas_negativas.gif' onclick="javascript: window.open('os_extrato_detalhe_pecas_negativas.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Detalhado' border='0' style='cursor:pointer;'>
<? } ?>
<? } ?>
<br><br>
<input type='button' value='Voltar' border='0' onclick="javascript: history.back(-1);" alt='Voltar' style='cursor: pointer;'>
</center>

<? include "rodape.php"; ?>
