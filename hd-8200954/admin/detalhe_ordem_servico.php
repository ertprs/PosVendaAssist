<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';


if (strlen($_POST["os"]) > 0) $os = trim($_POST["os"]);
if (strlen($_GET["os"]) > 0)  $os = trim($_GET["os"]);

$sqly = "SELECT to_char(data_envio,'DD/MM/YYYY') as data_envio
			FROM tbl_extrato_financeiro
			WHERE extrato = (SELECT extrato FROM tbl_os_extra where os = $os LIMIT 1)
			LIMIT 1;";
$resy = @pg_exec($con,$sqly);
if(@pg_numrows($resy) > 0){
	$data_envio_financeiro = @pg_result($resy, 0, data_envio);
}

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	if($ajax=="estoque"){
		$peca         = $_GET['peca'];
		$posto        = $_GET['posto'];
		$data_inicial = date("Y-m-d", mktime(0, 0, 0, date("n"), 1,  date("Y")));
		$data_final   = date("Y-m-t", mktime(0, 0, 0, date("n"), 1,  date("Y")));
		$sql = "SELECT 	tbl_estoque_posto_movimento.peca                              ,
				tbl_peca.referencia                                           ,
				tbl_peca.descricao as peca_descricao                          ,
				tbl_os.sua_os                                                 ,
				tbl_estoque_posto_movimento.os                                ,
				to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY') as data,
				tbl_estoque_posto_movimento.qtde_entrada                      ,
				tbl_estoque_posto_movimento.qtde_saida                        ,
				tbl_estoque_posto_movimento.admin                             ,
				tbl_estoque_posto_movimento.pedido                            ,
				tbl_estoque_posto_movimento.obs
		FROM  tbl_estoque_posto_movimento
		JOIN  tbl_peca on tbl_peca.peca =  tbl_estoque_posto_movimento.peca
		AND   tbl_peca.fabrica = $login_fabrica
		LEFT  JOIN tbl_os on tbl_estoque_posto_movimento.os = tbl_os.os
		AND   tbl_os.fabrica = $login_fabrica
		WHERE tbl_estoque_posto_movimento.posto   = $posto
		AND   tbl_estoque_posto_movimento.peca    = $peca
		AND   tbl_estoque_posto_movimento.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao,
		tbl_estoque_posto_movimento.data,
		tbl_estoque_posto_movimento.qtde_saida,
		tbl_estoque_posto_movimento.os";

		//	AND   tbl_estoque_posto_movimento.data between '$data_inicial' and '$data_final'
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth' style='font-family: verdana; font-size: 9px'><tr><td width='95%'>&nbsp;</td><td align='right' bgcolor='#FFFFFF'> <a href='javascript:escondeEstoque();'> <B>Fechar</b></a></td></tr></table>";
			echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px'>";

			echo "<thead>";
			echo "<tr>";
			echo "<th><font color='#FFFFFF'><B>Movimen.</B></FONT></th>";
			echo "<th><font color='#FFFFFF'><B>Data</B></FONT></th>";
			echo "<th><font color='#FFFFFF'><B>Peça</B></FONT></th>";
			echo "<th><font color='#FFFFFF'><B>Entrada</B></FONT></th>";
			echo "<th><font color='#FFFFFF'><B>Saida</B></FONT></th>";
			echo "<th><font color='#FFFFFF'><B>Pedido</B></FONT></th>";
			echo "<th><font color='#FFFFFF'><B>OS</B></FONT></th>";
			echo "<th><font color='#FFFFFF'><B>Observação</B></FONT></th>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			for($i=0; pg_numrows($res)>$i;$i++){

				$os             = pg_result ($res,$i,os);
				$sua_os         = pg_result ($res,$i,sua_os);
				$referencia     = pg_result ($res,$i,referencia);
				$peca_descricao = pg_result ($res,$i,peca_descricao);
				$data           = pg_result ($res,$i,data);
				$qtde_entrada   = pg_result ($res,$i,qtde_entrada);
				$qtde_saida     = pg_result ($res,$i,qtde_saida);
				$admin          = pg_result ($res,$i,admin);
				$obs            = pg_result ($res,$i,obs);
				$pedido         = pg_result ($res,$i,pedido);
				//	$obs            = pg_result ($res,$i,obs);

				$saida_total  = $saida_total + $qtde_saida;
				$entrada_total = $entrada_total + $qtde_entrada;

				/*if(strlen($obs) > 0 and strlen($qtde_saida) == 0){
				$obs = "OS recusada, peça volta para estoque";
				}else{
				$obs = "";
				}*/

				if($qtde_entrada>0){
					$movimentacao = "<font color='#35532f'>Entrada</font>";
				}else{
					$movimentacao = "<font color='#f31f1f'>Saida</font>";
				}

				$cor = "#efeeea";
				if ($i % 2 == 0) $cor = '#d2d7e1';

				echo "<tr bgcolor='$cor'>";
				echo "<td align='center'>$movimentacao</td>";
				echo "<td align='center'>$data</td>";
				echo "<td align='left'>$referencia</td>";
				echo "<td align='center'>$qtde_entrada</td>";
				echo "<td align='center'>$qtde_saida</td>";
				echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
				echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
				echo "<td align='left'>$obs</td>";
				echo "</td>";
				echo "</tr>";
			}
			$total = $entrada_total - $saida_total;
			echo "</tbody>";
			echo "<tfoot>";
			echo "<tr bgcolor='#FFFFFF'>";
			echo "<td colspan='3' align='center'><font color='#2f67cd'><B>SALDO</B></FONT></td>";
			echo "<td colspan='2' align='center'><font color='#2f67cd'><B>"; echo $total; echo "</B></FONT></td>";
			echo "<td>&nbsp;</td>";
			echo "<td>&nbsp;</td>";
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			echo "</tfoot>";
			echo "</table><BR>";

		}else{
			echo "<BR><center><font color='#FFFFFF'>Nenhum resultado encontrado</font></center><BR>";
		}
	}

	if($ajax=="autoriza"){
		$peca         = $_GET['peca'];
		$os_item      = $_GET['os_item'];
		$sql = "SELECT 	tbl_os_produto.os,
						tbl_os_item.peca_sem_estoque,
						tbl_peca.referencia,
						tbl_peca.descricao
				FROM tbl_os_item
				JOIN tbl_os_produto using(os_produto)
				JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
				WHERE tbl_os_item.os_item = $os_item
				and tbl_os_item.peca = $peca
				and peca_sem_estoque is true";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca_referencia = pg_result($res,0,referencia);
			$peca_descricao  = pg_result($res,0,descricao);
			echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth' style='font-family: verdana; font-size: 9px'><tr><td width='95%'>&nbsp;</td><td align='right' bgcolor='#FFFFFF'> <a href='javascript:escondeEstoque();'> <B>Fechar</b></a></td></tr></table>";
			echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px' width='350'>";
			echo "<tr>";
			echo "<td align='center'><b><font color='#FFFFFF'>Aceitar peca: $peca_referencia</FONT></b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' bgcolor='#efeeea'><b>Atenção</b><BR>";
			echo "O estoque do posto para a <BR>peça $peca_descricao esta negativa.<BR> Para autorizar a utilização da peça informe o motivo<BR>";
			echo "<TEXTAREA NAME='autorizacao_texto' ID='autorizacao_texto' ROWS='5' COLS='30' class='textarea'></TEXTAREA>";
			echo "<input type='hidden' name='peca' id='peca' value='$peca'>";
			echo "<input type='hidden' name='os_item' id='os_item' value='$os_item'>";
			echo "<BR><BR><img src='imagens_admin/btn_confirmar.gif' border='0' style='cursor:pointer;' onClick='gravaAutorizao();'></td>";
			echo "</tr>";
			echo "</table><BR>";
		}
	}
	if($ajax=="gravar"){
		$peca         = $_GET['peca'];
		$os_item      = $_GET['os_item'];
		$autorizacao_texto     = $_GET['autorizacao_texto'];
		/*echo "peca $peca<BR>";
		echo "os_item $os_item<BR>";
		echo "autorizacao_texto $autorizacao_texto";*/
		$sql = "select 	tbl_os.posto      ,
						tbl_os_item.qtde  ,
						tbl_os.os
				from tbl_os
				JOIN tbl_os_produto using(os)
				join tbl_os_item using(os_produto)
				where tbl_os.fabrica = $login_fabrica
				and  tbl_os_item.os_item = $os_item
				and  tbl_os_item.peca = $peca
				and   tbl_os_item.peca_sem_estoque is true";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		//echo $sql."<BR>";
		if(pg_numrows($res)>0 and strlen($msg_erro)==0){
			$posto = pg_result($res,0,posto);
			$qtde = pg_result($res,0,qtde);
			$os = pg_result($res,0,os);
			$sql = "INSERT INTO tbl_estoque_posto_movimento(
						fabrica      ,
						posto        ,
						os           ,
						peca         ,
						qtde_entrada   ,
						data,
						os_item,
						obs,
						admin
						)values(
						$login_fabrica,
						$posto        ,
						$os           ,
						$peca         ,
						$qtde         ,
						current_date  ,
						$os_item       ,
						'$autorizacao_texto',
						$login_admin
				)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($msg_erro)==0){
				$sql = "SELECT peca
						FROM tbl_estoque_posto
						WHERE peca = $peca
						AND posto = $posto
						AND fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$sql = "UPDATE tbl_estoque_posto set
							qtde = qtde + $qtde
							WHERE peca  = $peca
							AND posto   = $posto
							AND fabrica = $login_fabrica;";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					$sql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)
							values($login_fabrica,$posto,$peca,$qtde)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
		if(strlen($msg_erro)>0){
			echo "Ocorreu um erro";
		}else{
			echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#FFFFFF' align='center' style='font-family: verdana; font-size: 9px' width='100%'>";
			echo "<tr>";
			echo "<td align='center'><b><font color='#000000'>Atualizado com sucesso!!</FONT></b><br><a href='javascript:escondeEstoque();'> <B>Clique aqui para Fechar</b></a></td>";
			echo "</tr>";
			echo "</table>";
		echo "<META HTTP-EQUIV='Refresh' CONTENT='';URL=$PHP_SELF'>";
		}
	}
exit;
}

$btn_acao = $_POST['btn_acao'];
if (substr (strtoupper ($btn_acao),0,10) == "RECALCULAR") {
	if($login_fabrica ==1){
		$sql = "SELECT fn_calcula_os_item_black ($os,$login_fabrica)";
		$res = pg_exec ($con,$sql);
	}

	$sql = "SELECT extrato FROM tbl_os_extra WHERE os = $os";
	$res = pg_exec ($con,$sql);
	$extrato = pg_result ($res,0,0);
	if (strlen ($extrato) > 0) {
		$sql = "SELECT fn_calcula_extrato ($login_fabrica,$extrato)";
		$res = pg_exec ($con,$sql);

		$sql = "SELECT fn_totaliza_extrato ($login_fabrica,$extrato)";
		$res = pg_exec ($con,$sql);
	}
}

/* #####################################################################################
	EXCLUSÃO DE PEÇA DO EXTRATO GERANDO UMA OS_SEDEX DE DÉBITO PARA O PA.
	EXCLUINDO A PEÇA É GERADO UMA OS_SEDEX DE DÉBITO PARA O POSTO COM O VALOR DA PEÇA
	NO MESMO EXTRATO QUE ESTAVA.
 ####################################################################################### */

$btn_excluir = $_POST['btn_os'];
if($btn_excluir == 'Excluir'){

	$sql = "SELECT os_item
				FROM tbl_os_item
				JOIN tbl_os_produto using(os_produto)
			WHERE os = $os ";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		for($i=0; $i < pg_numrows($res); $i++){
			if(strlen($_POST['motivo'.$i]) > 0) {
				$motivo   = $_POST['motivo'.$i];
				$os_item  = $_POST['os_item'.$i];
				break;
			}
		}
	}

	$sql = "SELECT	tbl_os.os                   ,
					tbl_os.finalizada           ,
					tbl_os_item.custo_peca      ,
					tbl_os_item.qtde            ,
					tbl_os_item.peca            ,
					tbl_peca.referencia         ,
					tbl_os.sua_os               ,
					tbl_os_extra.extrato        ,
					tbl_extrato.protocolo       ,
					tbl_os.posto                ,
					tbl_extrato_extra.baixado
				FROM tbl_os
				JOIN tbl_os_produto    ON tbl_os.os                 = tbl_os_produto.os
				JOIN tbl_os_item       ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_os_extra      ON tbl_os_produto.os         = tbl_os_extra.os
				JOIN tbl_extrato       ON tbl_os_extra.extrato      = tbl_extrato.extrato
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_peca          ON tbl_peca.peca             = tbl_os_item.peca
			WHERE os_item = $os_item
			AND   tbl_os.fabrica = $login_fabrica;";

	$res = pg_exec($con,$sql);//busca as informações da os_item

	if(pg_numrows($res) > 0){
		$custo_peca      = pg_result($res,0,custo_peca);
		$qtde            = pg_result($res,0,qtde);
		$peca            = trim(pg_result($res,0,peca));
		$peca_referencia = trim(pg_result($res,0,referencia));
		$sua_os          = pg_result($res,0,sua_os);
		$posto           = pg_result($res,0,posto);
		$extrato         = pg_result($res,0,extrato);
		$protocolo       = pg_result($res,0,protocolo);
		$os_item_os      = pg_result($res,0,os);
		$os_finalizada   = pg_result($res,0,finalizada);
		$baixado         = pg_result($res,0,'baixado');

		$custo_peca = $custo_peca * $qtde;

		$sql = "SELECT os_sedex
					FROM tbl_os_sedex
					JOIN tbl_os_extra     ON tbl_os_extra.extrato = tbl_os_sedex.extrato
					JOIN tbl_os           ON tbl_os_extra.os      = tbl_os.os
				WHERE tbl_os.os = $os
				AND   tbl_os_sedex.fabrica = $login_fabrica
				AND   tbl_os_sedex.extrato = $extrato
				AND   tbl_os.sua_os = tbl_os_sedex.sua_os_destino
				AND   tbl_os_sedex.obs ilike '%$peca_referencia%'";
		$res = pg_exec($con,$sql);// verifica se a peca ja foi excluida.

		if(pg_numrows($res) == 0){
			$sql6 = "SELECT extrato_lancamento
						FROM tbl_extrato_lancamento
						WHERE extrato = $extrato
						AND lancamento = 47; ";
			$res6 = pg_exec($con,$sql6);
			if(pg_numrows($res6) > 0){
				$taxa_peca = $qtde * ($custo_peca * 0.1) ;
				$sql_tx = "SELECT valor, extrato_lancamento FROM tbl_extrato_lancamento WHERE extrato = $extrato AND lancamento = 47 limit 1; ";
				$res_tx = pg_exec($con,$sql_tx);
				if(pg_numrows($res_tx) > 0){
					$soma_tx_adm   = pg_result($res_tx,0,valor);
					$extrato_lanca = pg_result($res_tx,0,extrato_lancamento);
					$soma_tx_adm   = $soma_tx_adm - $taxa_peca;
//					$sqltx2 = "UPDATE tbl_extrato_lancamento SET valor = '$soma_tx_adm' WHERE extrato_lancamento = $extrato_lanca; ";
//					$restx2 = pg_exec($con,$sqltx2);
				}
				$total_pagar = $custo_peca + $taxa_peca;
			}else{
				$total_pagar = $custo_peca;
			}
			if(strlen($_POST['motivo'.$os_item]) > 0) $motivo = $_POST['motivo'.$os_item]; ;

			$peca_troca_faturada = 'f';

			$obs = "Peça $peca_referencia excluída do extrato $protocolo com o valor de R$ $custo_peca. Motivo: $motivo";

			$sql = "SELECT  tbl_os_item.servico_realizado
						FROM tbl_os_item
						JOIN tbl_servico_realizado using(servico_realizado)
					WHERE tbl_os_item.os_item = $os_item
					AND   tbl_servico_realizado.fabrica = $login_fabrica
					AND   tbl_servico_realizado.troca_de_peca is TRUE
					AND   tbl_servico_realizado.gera_pedido is FALSE
					AND   tbl_servico_realizado.ativo is TRUE ;";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0) $peca_troca_faturada = 't';
			//todas as peças devem ser descontadas, ou seja, a OS informa o pagamento, e se
			//for indevido, desconto logo abaixo.
			$total_pagar = $custo_peca * (-1);

			if($peca_troca_faturada == 't') $total_pagar = '0';

			$res = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "UPDATE tbl_extrato_extra set baixado = null where extrato = $extrato ;
					UPDATE tbl_os set finalizada = null where os = $os and fabrica = 1; ";
			$res = pg_exec($con,$sql);

			$sql = "UPDATE tbl_os_item set custo_peca = 0.00 where os_item = $os_item; ";
			$res = pg_exec($con,$sql);

			$sql = "UPDATE tbl_os set finalizada = '$os_finalizada' where os = $os and fabrica = 1; ";
			$res = pg_exec($con,$sql);

			if(!empty($baixado)) {
				$sql = "UPDATE tbl_extrato_extra set baixado = '$baixado' where extrato = $extrato ; ";
				$res = pg_exec($con,$sql);
			}

//			if($ip=="201.26.21.165"){
				$sql = "SELECT peca from tbl_os_item where os_item = $os_item";
				$res = pg_exec($con,$sql);
				$xxpeca = pg_result($res,0,0);
				$sql = "select fn_estoque_recusa_peca($os,$xxpeca,$login_fabrica,$login_admin)";
				$res = pg_exec($con,$sql);
//			}

			$sql = "INSERT INTO tbl_os_sedex (
										fabrica         ,
										posto_origem    ,
										posto_destino   ,
										extrato         ,
										total_pecas     ,
										total           ,
										extrato_destino ,
										sua_os_destino  ,
										obs             ,
										data            ,
										admin
								) VALUES (
										$login_fabrica  ,
										'$posto'        ,
										'6900'          ,
										$extrato        ,
										'$total_pagar'  ,
										'$total_pagar'  ,
										$extrato        ,
										'$sua_os'       ,
										'$obs'          ,
										current_date    ,
										$login_admin
								);";
			$res = @pg_exec($con,$sql);//insere na os sedex para a blackedecker
			$msg_erro = pg_errormessage($con);

			$sql = "SELECT os_sedex FROM tbl_os_sedex
						WHERE extrato       = $extrato
						AND   fabrica       = $login_fabrica
						AND   posto_origem  = $posto
						ORDER BY data_digitacao DESC limit 1 ;";
			$res = pg_exec($con,$sql);//busca a os_sedex que foi cadastrada.
			$os_sedex = pg_result($res,0,os_sedex);//busca o numero da os_sedex que foi cadastrada

			$historico = "Exclusão Peça: $peça - Extrato: $protocolo - OS: $sua_os.";

			//HD 100622
			$sql = "INSERT INTO tbl_extrato_lancamento (
										posto        ,
										fabrica      ,
										automatico   ,
										extrato      ,
										lancamento   ,
										historico    ,
										valor        ,
										os_sedex
								) VALUES (
										$posto         ,
										$login_fabrica ,
										't'            ,
										$extrato       ,
										'42'           ,
										'$historico'   ,
										$total_pagar   ,
										$os_sedex
								);";
			$res = @pg_exec($con,$sql);//insere na extrato_lancamento com a os_sedex buscada no select.
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
					#$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
					# Troquei para fn_totaliza_extrato pois nao é necessario calcular novamente
					# HD 12822
					$sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					$res = pg_exec($con,"COMMIT TRANSACTION");
			}

			// adicionado por Fernando - Verificar a nova opção de Recusa de OS com reembolso das pecas ja enviadas.
			//email-->
			$email_origem  = "gustavo@telecontrol.com.br"; //HD 79538
			$email_destino = "gustavo@telecontrol.com.br";
			$assunto       = "EXCLUSÃO PEÇA OS NO EXTRATO";
			$corpo .= "<br>OS: $os (Número de controle da Telecontrol)";
			$corpo .= "<br>Posto origem: $posto_origem (Controle da Telecontrol) ";
			$corpo .= "<br>Extrato Telecotnrol: $extrato (Controle da Telecontrol) ";
			$corpo .= "<br>OS SEDEX: $os_sedex ";
			$corpo .= "<br>Total da OS SEDEX: $total_neg";
			$corpo .= "<br>Observação : $obs";
			$corpo .= "<br>_______________________________________________\n";
			$corpo .= "<br><br>Telecontrol\n";
			$corpo .= "<br>www.telecontrol.com.br\n";
			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
			@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " );
			// fim

//			header ("Location: $PHP_SELF?os=$os");
//			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
			$msg_erro = "<br>Não foi possível excluir a peca $peca_referencia.";
		}
	}else{
		$msg_erro = "Peca já foi excluida.";
	}
}


if($login_fabrica ==1){
	$titulo = "Black & Decker - Ordem de Serviço";
}else{
	$titulo = "Filizola - Ordem de Serviço";
}

?>

<html>
<head>
<title><?echo $titulo?></title>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
	background-color: #D9E2EF;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.textarea{
	border-width: 1px;
	border-style: solid;
	border-color: #8c8a79;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
}
a:link {
color:#535353;
text-decoration:none;
}
a:visited {
color:#535353;
text-decoration:none;
}
a:hover {
color:#FF3333;
text-decoration:underline;
}
a:active {
color:#535353;
text-decoration:underline;
background-color:#000000;
}
</style>

<script type="text/javascript">
function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
	}
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
function escondeEstoque(){
	if (document.getElementById('div_estoque')){
		var style2 = document.getElementById('div_estoque');
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
var http3 = new Array();
function gravaAutorizao(){
	var os_item           = document.getElementById('os_item');
	var peca              = document.getElementById('peca');
	var autorizacao_texto = document.getElementById('autorizacao_texto');

		var curDateTime = new Date();
		http3[curDateTime] = createRequestObject();

		url = "detalhe_ordem_servico.php?ajax=gravar&peca="+peca.value+"&os_item="+os_item.value+"&autorizacao_texto="+autorizacao_texto.value;
		http3[curDateTime].open('get',url);
		var campo = document.getElementById('div_estoque');
		Page.getPageCenterX();
		campo.style.top = (Page.top + Page.height/2)-160;
		campo.style.left = Page.width/2-220;

		http3[curDateTime].onreadystatechange = function(){
			if(http3[curDateTime].readyState == 1) {
				campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
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


function aceitarPeca(os_item,peca){
	var div = document.getElementById('div_estoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	autorizarPeca(os_item,peca);

}


function autorizarPeca(os_item,peca){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "detalhe_ordem_servico.php?ajax=autoriza&peca="+peca+"&os_item="+os_item;
	http3[curDateTime].open('get',url);
	var campo = document.getElementById('div_estoque');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
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
function verificarEstoque(posto,peca){
	var div = document.getElementById('div_estoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	mostraEstoque(posto,peca);
}
function mostraEstoque(posto,peca){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "detalhe_ordem_servico.php?ajax=estoque&peca="+peca+"&posto="+posto;
	http3[curDateTime].open('get',url);
	var campo = document.getElementById('div_estoque');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
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
var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('div_estoque').innerHTML ='';
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;
	//For old IE browsers
	if(document.all) {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if(document.getElementById &&!document.all){
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if(document.getElementById) {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}
</script>

</head>

<body>
<?
if(strlen($msg_erro) > 0){
?>
	<TABLE border='0' width='500' align='center'>
	<TR style='font-family: verdana; font-weight: bold; font-size: 14px; color:#FFFFFF' bgcolor='#FF3300'>
		<TD align='center'><? echo $msg_erro; ?></TD>
	</TR>
	</TABLE>
<?}?>

<?
if (strlen($os) > 0) {
//	echo "<form name='frmos' method='post' action='$PHP_SELF'>";

	$sql = "SELECT  tbl_os.os                                                        ,
					tbl_os.posto                                                     ,
					tbl_os.sua_os                                                    ,
					tbl_posto.nome                                                   ,
					tbl_posto_fabrica.codigo_posto                                   ,
					tbl_posto_fabrica.tipo_posto                                     ,
					tbl_produto.referencia                        AS referencia      ,
					tbl_produto.descricao                         AS nome_equipamento,
					tbl_os.type                                   AS tipo            ,
					to_char(tbl_os.data_abertura, 'DD/MM/YYYY')   AS abertura        ,
					to_char(tbl_os.data_fechamento, 'DD/MM/YYYY') AS fechamento      ,
					to_char(tbl_os.finalizada, 'DD/MM/YYYY')      AS finalizada      ,
					tbl_os.serie                                  AS serie           ,
					tbl_os.codigo_fabricacao                      AS codigo_fabricacao,
					tbl_os.consumidor_nome                        AS nome_cli        ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_endereco                    AS cliente_endereco,
					tbl_os.consumidor_cep                         AS cliente_cep     ,
					tbl_os.consumidor_numero                      AS cliente_numero  ,
					tbl_os.consumidor_complemento                 AS cliente_complemento          ,
					tbl_os.consumidor_bairro                      AS cliente_bairro               ,
					tbl_os.consumidor_estado                                                      ,
					tbl_os.consumidor_fone                        AS fone                         ,
					tbl_cliente.endereco                                                          ,
					tbl_cliente.numero                                                            ,
					tbl_cliente.complemento                                                       ,
					tbl_cliente.cep                                                               ,
					tbl_os.nota_fiscal                                                            ,
					to_char(tbl_os.data_nf, 'DD/MM/YYYY')         AS data_nf                      ,
					tbl_os.revenda_nome                           AS loja                         ,
					tbl_os.revenda_fone                           AS loja_fone                    ,
					tbl_os.revenda_cnpj                           AS cnpj                         ,
					tbl_os.troca_faturada                                                         ,
					tbl_os.obs                                                                    ,
					tbl_os.os_numero                                                              ,
					tbl_os.tipo_os                                                                ,
					tbl_os.quem_abriu_chamado                                                     ,
					tbl_tipo_atendimento.descricao                 AS nome_atendimento            ,
					tbl_defeito_reclamado.descricao               AS defeito_reclamado            ,
					tbl_defeito_constatado.codigo                 AS defeito_constatado_codigo    ,
					tbl_defeito_constatado.descricao              AS defeito_constatado_descricao ,
					tbl_solucao.descricao                         AS solucao_os                   ,
					tbl_posto_fabrica.reembolso_peca_estoque
			FROM	tbl_os
			JOIN	tbl_posto                ON tbl_os.posto                              = tbl_posto.posto
			JOIN	tbl_posto_fabrica        ON tbl_posto_fabrica.posto                   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica                 = $login_fabrica
			JOIN	tbl_produto              ON tbl_os.produto                            = tbl_produto.produto
			left JOIN tbl_cliente            ON tbl_cliente.cliente                       = tbl_os.cliente
			LEFT JOIN tbl_defeito_reclamado  ON tbl_defeito_reclamado.defeito_reclamado   = tbl_os.defeito_reclamado
			LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			LEFT JOIN tbl_solucao            ON tbl_solucao.solucao                       = tbl_os.solucao_os
			LEFT JOIN tbl_tipo_atendimento   ON tbl_tipo_atendimento.tipo_atendimento     = tbl_os.tipo_atendimento
			WHERE	tbl_os.os = $os
			AND		tbl_os.fabrica = $login_fabrica ";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$tipo_posto = pg_result ($res,0,tipo_posto);
		$reembolso  = pg_result ($res,0,reembolso_peca_estoque);
		$posto      = pg_result ($res,0,posto);
		$tipo_os    = pg_result ($res,0,tipo_os);
		$os_numero  = pg_result ($res,0,os_numero);
		$quem_abriu_chamado= trim(pg_result($res,0,quem_abriu_chamado));
		$nome_atendimento= trim(pg_result($res,0,nome_atendimento));


		/*hd3636 somente postos que pedem faturado*/
		$wwsql = "SELECT pedido_faturado from tbl_posto_fabrica where posto=$posto and fabrica=$login_fabrica";
		$wwres = @pg_exec($con,$wwsql);
		$pedido_faturado = pg_result($wwres,0,0);
		/*hd3636 somente postos que pedem faturado*/

		echo "<div id='div_estoque' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:450px;'></div>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		if($tipo_os==13){
			echo "<tr class='Titulo'>";
			echo "<td colspan='3'>&nbsp;&nbsp;Tipo Atendimento</TD> ";
			echo "<td colspan='2'>&nbsp;&nbsp;Solicitante</TD> ";
			echo "</tr>";
			echo "<tr class='Conteudo'>";
			echo "<td colspan='3'>&nbsp;&nbsp;$nome_atendimento</TD>";
			echo "<td colspan='2'>&nbsp;&nbsp;$quem_abriu_chamado</TD>";
			echo "</tr>";
		}

		echo "<tr class='Titulo'>";


		echo "<td width='15%'>OS</td>";
		echo "<td width='55%'>Posto</td>";
		echo "<td width='10%'>Abertura</td>";
		echo "<td width='10%'>Fechamento</td>";
		echo "<td width='10%'>Finalizada</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		if($login_fabrica ==1){
			echo "<td width='15%'>" . pg_result($res,0,codigo_posto) . pg_result($res,0,sua_os) . "</td>";
		}else{
			echo "<td width='15%'>" . pg_result($res,0,sua_os) . "</td>";
		}
		echo "<td width='55%'>" . pg_result($res,0,codigo_posto) ." - ". pg_result($res,0,nome) . "</td>";
		echo "<td width='10%'>" . pg_result($res,0,abertura) . "</td>";
		echo "<td width='10%'>" . pg_result($res,0,fechamento) . "</td>";
		echo "<td width='10%'>" . pg_result($res,0,finalizada) . "</td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='70%'>Cliente</td>";
		echo "<td width='30%'>Telefone</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='70%'>" . pg_result($res,0,nome_cli) . "</td>";
		echo "<td width='30%'>" . pg_result($res,0,fone) . "</td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='45%'>Endereço</td>";
		echo "<td width='45%'>Cidade</td>";
		echo "<td width='10%'>CEP</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		if( strlen(pg_result($res,0,cliente_endereco)) > 0 ){
			$cliente_endereco = @pg_result($res,0,cliente_endereco);
		}else{
			$cliente_endereco = @pg_result($res,0,endereco);
		}

		if( strlen(pg_result($res,0,cliente_cep)) > 0 ){
			$cliente_cep = @pg_result($res,0,cliente_cep);
		}else{
			$cliente_cep = @pg_result($res,0,cep);
		}

		if( strlen(pg_result($res,0,cliente_numero)) > 0 ){
			$cliente_numero = @pg_result($res,0,cliente_numero);
		}else{
			$cliente_numero = @pg_result($res,0,cep);
		}

		if( strlen(pg_result($res,0,cliente_complemento)) > 0 ){
			$cliente_complemento = @pg_result($res,0,cliente_complemento);
		}else{
			$cliente_complemento = @pg_result($res,0,complemento);
		}

		echo "<td width='45%'>$cliente_endereco , $cliente_numero $cliente_complemento</td>";
		echo "<td width='45%'>" . pg_result($res,0,consumidor_cidade) . " - " . pg_result($res,0,consumidor_estado) . "</td>";
		echo "<td width='10%'>$cliente_cep</td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='34%'>CNPJ</td>";
		echo "<td width='33%'>Nota Fiscal</td>";
		echo "<td width='33%'>Data NF</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='34%'>" . pg_result($res,0,cnpj) . "</td>";
		echo "<td width='33%'>" . pg_result($res,0,nota_fiscal) . "</td>";
		echo "<td width='33%'>" . pg_result($res,0,data_nf) . "</td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		# HD 23094 - Francisco Ambrozio - incluir campo telefone revenda para a BlackeDecker
		if ($login_fabrica == 1){
			echo "<tr class='Titulo'>";
			echo "<td width='70%'>Loja</td>";
			echo "<td width='30%'>Telefone</td>";
			echo "</tr>";
			echo "<tr class='Conteudo'>";
			echo "<td width='70%'>" . pg_result($res,0,loja) . "</td>";
			echo "<td width='30%'>" . pg_result($res,0,loja_fone) . "</td>";
			echo "</tr>";
		}else{
			echo "<td width='100%'>Loja</td>";
			echo "</tr>";
			echo "<tr class='Conteudo'>";
			echo "<td width='100%'>" . pg_result($res,0,loja) . "</td>";
			echo "</tr>";
		}
		echo "</table>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='100%'>Observações</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='100%' >" . pg_result($res,0,obs) . "<br></td>";

		if (pg_result($res,0,troca_faturada) == 't') {
			echo "<tr class='Conteudo'>";
			echo "<td width='100%'>&nbsp;<B>Troca faturada.</B></td>";
			echo "</tr>";
		}

		echo "</table>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='40%'>Produto</td>";
		echo "<td width='20%'>Cód. Fabr.</td>";
		echo "<td width='20%'>Série</td>";
		echo "<td width='20%'>Tipo</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td>" . pg_result($res,0,referencia) ." - ". pg_result($res,0,nome_equipamento) . "</td>";
		echo "<td>" . pg_result($res,0,codigo_fabricacao) . "</td>";
		echo "<td>" . pg_result($res,0,serie) . "</td>";
		echo "<td>" . pg_result($res,0,tipo) . "</td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='35%'>Defeito Reclamado</td>";
		echo "<td width='35%'>Defeito Constatado</td>";
		echo "<td width='30%'>Solução</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='35%'>" . pg_result($res,0,defeito_reclamado) . "</td>";
		echo "<td width='35%'>" . pg_result($res,0,defeito_constatado_codigo) . " - " . pg_result($res,0,defeito_constatado_descricao) . "</td>";
		echo "<td width='30%'>" . pg_result($res,0,solucao_os) . "</td>";
		echo "</tr>";
		echo "</table>";
	}
	/*takashi compressores*/
	/*HD: 83010 - OS GEO - IGOR*/

	if($tipo_os == 13){
		$sql = "SELECT tecnico
				FROM tbl_os_extra
				WHERE os= $os";
		$res = pg_exec($con,$sql);
		$relatorio_tecnico             = pg_result($res,0,tecnico);

		$sql = "SELECT 	os                                  ,
						to_char(data, 'DD/MM/YYYY') as  data,
						to_char(hora_chegada_cliente, 'HH24:MI') as inicio      ,
						to_char(hora_saida_cliente, 'HH24:MI')   as fim         ,
						km_chegada_cliente   as km          ,
						valor_adicional                     ,
						justificativa_valor_adicional       ,
						qtde_produto_atendido
				FROM tbl_os_visita
				WHERE tbl_os_visita.os_revenda = $os_numero";
		$res = pg_exec($con,$sql);


		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='100%' colspan='7'>&nbsp;DESPESAS DA OS GEO METAL: $os_numero</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td nowrap class='Titulo' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";
		echo "<td nowrap class='Titulo' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>";
		echo "<td nowrap class='Titulo' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>";
		echo "<td nowrap class='Titulo' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
		echo "<td nowrap class='Titulo' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Qtde Prod. Atendido</font></td>";
		echo "<td nowrap class='Titulo' colspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td nowrap class='Titulo'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
		echo "<td nowrap class='Titulo'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
		echo "</tr>";
		if(pg_numrows($res)>0){

			for($i=0;$i<pg_numrows($res);$i++){

				$data                          = pg_result($res,$i,data);
				$inicio                        = pg_result($res,$i,inicio);
				$fim                           = pg_result($res,$i,fim);
				$km                            = pg_result($res,$i,km);
				$qtde_produto_atendido         = pg_result($res,$i,qtde_produto_atendido);
				//$relatorio_tecnico             = pg_result($res,$i,tecnico);
				$valor_adicional               = pg_result($res,$i,valor_adicional);
				$justificativa_valor_adicional = pg_result($res,$i,justificativa_valor_adicional);

				echo "<tr class='Conteudo'>";
				echo "<td align='center'>&nbsp;$data                         </td>";
				echo "<td align='center'>&nbsp;$inicio                       </td>";
				echo "<td align='center'>&nbsp;$fim                          </td>";
				echo "<td align='center'>&nbsp;$km                           </td>";
				echo "<td align='center'>&nbsp;$qtde_produto_atendido        </td>";
				echo "<td align='center'>&nbsp;".number_format($valor_adicional,2,",",".")."</td>";
				echo "<td align='center'>&nbsp;$justificativa_valor_adicional</td>";
				echo "</tr>";
			}
			echo "<tr class='Titulo'>";
			echo "<td align='center' colspan='7'>Relatório Técnico</td>";
			echo "</tr>";
			echo "<tr class='Conteudo'>";
			echo "<td align='left' colspan='7'>$relatorio_tecnico</td>";
			echo "</tr>";
			echo "</table>";

		}


	}else{
		$sql = "SELECT 	os                                  ,
						tbl_os_extra.tecnico                 ,
						to_char(data, 'DD/MM/YYYY') as  data,
						to_char(hora_chegada_cliente, 'HH24:MI') as inicio      ,
						to_char(hora_saida_cliente, 'HH24:MI')   as fim         ,
						km_chegada_cliente   as km          ,
						valor_adicional                     ,
						justificativa_valor_adicional       ,
						qtde_produto_atendido               ,
						(
							extract ( 'hour' from tbl_os_visita.hora_saida_cliente::timestamp -
									tbl_os_visita.hora_chegada_cliente::timestamp)  * 60
							) + (
								extract ( 'minute' from tbl_os_visita.hora_saida_cliente::timestamp -
										tbl_os_visita.hora_chegada_cliente::timestamp)
								)
							 as total_minutos
				FROM tbl_os_visita
				JOIN tbl_os_extra using(os)
				WHERE tbl_os_visita.os = $os";
		$res = pg_exec($con,$sql);


			echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
			echo "<tr class='Titulo'>";
			if($login_fabrica == 7){
				echo "<td width='100%' colspan='7'>Despesas</td>";
			}else{
				echo "<td width='100%' colspan='7'>Despesas de Compressores</td>";
			}
			echo "</tr>";

			echo "<tr>";
			echo "<td nowrap class='Titulo' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";
			echo "<td nowrap class='Titulo' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>";
			echo "<td nowrap class='Titulo' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>";
			echo "<td nowrap class='Titulo' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Minutos trab.</font></td>";
			echo "<td nowrap class='Titulo' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
			echo "<td nowrap class='Titulo' colspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td nowrap class='Titulo'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
			echo "<td nowrap class='Titulo'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
			echo "</tr>";
		if(pg_numrows($res)>0){
			$relatorio_tecnico             = pg_result($res,0,tecnico);
			for($i=0;$i<pg_numrows($res);$i++){

				$data                           = pg_result($res,$i,data);
				$inicio                        = pg_result($res,$i,inicio);
				$fim                           = pg_result($res,$i,fim);
				$km                            = pg_result($res,$i,km);
				$relatorio_tecnico             = pg_result($res,$i,tecnico);
				$valor_adicional               = pg_result($res,$i,valor_adicional);
				$justificativa_valor_adicional = pg_result($res,$i,justificativa_valor_adicional);
				$total_minutos = pg_result($res,$i,total_minutos);

				echo "<tr class='Conteudo'>";
				echo "<td align='center'>&nbsp;$data                         </td>";
				echo "<td align='center'>&nbsp;$inicio                       </td>";
				echo "<td align='center'>&nbsp;$fim                          </td>";
				echo "<td align='center'>&nbsp;$total_minutos                </td>";
				echo "<td align='center'>&nbsp;$km                           </td>";
				echo "<td align='center'>&nbsp;".number_format($valor_adicional,2,",",".")."         </td>";
				echo "<td align='center'>&nbsp;$justificativa_valor_adicional</td>";
				echo "</tr>";
			}
				echo "<tr class='Titulo'>";
				echo "<td align='center' colspan='7'>Relatório Técnico</td>";
				echo "</tr>";
				echo "<tr class='Conteudo'>";
				echo "<td align='left' colspan='7'>$relatorio_tecnico</td>";
				echo "</tr>";
			echo "</table>";

		}

	}


	$sql = "SELECT 	tbl_peca.peca                                                          ,
					tbl_peca.referencia                                                    ,
					tbl_peca.descricao                                                     ,
					tbl_os.posto                                                           ,
					tbl_os_item.os_item                                                    ,
					tbl_os_item.peca_sem_estoque                                     ,
					tbl_os_item.qtde                                                       ,
					tbl_os_extra.extrato                                                   ,
					tbl_pedido.pedido_blackedecker                                         ,
					substr(tbl_pedido.seu_pedido,4,5) as seu_pedido                        ,
					tbl_pedido.pedido                                                      ,
					tbl_os_item.custo_peca                  AS preco                       ,
					tbl_defeito.descricao                   AS defeito                     ,
					tbl_servico_realizado.servico_realizado AS servico_realizado           ,
					tbl_servico_realizado.descricao         AS servico_realizado_descricao
			FROM	tbl_os_item
			JOIN	tbl_os_produto         ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
			JOIN	tbl_os                 ON tbl_os.os                               = tbl_os_produto.os
			JOIN	tbl_peca               ON tbl_os_item.peca                        = tbl_peca.peca
			LEFT JOIN tbl_os_extra         ON tbl_os_extra.os                         = tbl_os.os
			LEFT JOIN tbl_pedido           ON tbl_os_item.pedido                      = tbl_pedido.pedido
			JOIN	tbl_defeito            ON tbl_defeito.defeito                     = tbl_os_item.defeito
			JOIN	tbl_servico_realizado  ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica
			ORDER BY tbl_peca.referencia;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<FORM METHOD=POST ACTION='$PHP_SELF' name='frmos'>";
		echo "<input type='hidden' name='os' value='$os'>";
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='40%'>Peça</td>";
		echo "<td>Defeito</td>";
		echo "<td>Serviço</td>";
		echo "<td width='10%'>Qtde</td>";
		echo "<td width='10%'>Valor</td>";
		echo "<td width='10%'>Total</td>";
		echo "</tr>";

		$aux = 0;
		echo "<input type='hidden' name='qtde' value='$aux'>";
		echo "<input type='hidden' name='btn_os' value=''>";
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$aux++;
			$peca                        = trim(pg_result($res,$x,peca));
			$referencia                  = trim(pg_result($res,$x,referencia));
			$nome                        = trim(pg_result($res,$x,descricao));
			$posto                       = trim(pg_result($res,$x,posto));
			$qtde                        = trim(pg_result($res,$x,qtde));
			$pedido_black                = trim(pg_result($res,$x,pedido_blackedecker));
			$pedido                      = trim(pg_result($res,$x,pedido));
			$seu_pedido                  = trim(pg_result($res,$x,seu_pedido));
			$preco                       = trim(pg_result($res,$x,preco));
			$defeito                     = trim(pg_result($res,$x,defeito));
			$servico_realizado           = trim(pg_result($res,$x,servico_realizado));
			$servico_realizado_descricao = trim(pg_result($res,$x,servico_realizado_descricao));
			$os_item                     = trim(pg_result($res,$x,os_item));
			$extrato                     = trim(pg_result($res,$x,extrato));
			$peca_sem_estoque            = pg_result($res,$x,peca_sem_estoque);


			if ($servico_realizado == 63 OR $servico_realizado ==  64 OR $servico_realizado ==  73) $ocultar_valor = 1;
			else $ocultar_valor = 0;

			if(strlen($pedido_black) == 0) $pedido_black = 0;
			if(strlen($nota_fiscal) == 0) $nota_fiscal = 0;

			if(strlen($pedido) > 0){
				$sqlX  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
								TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS data_nf
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento_item.pedido = $pedido
						AND     tbl_faturamento_item.peca = $peca
						AND     tbl_faturamento.posto = $posto ";
				$resX = pg_exec ($con,$sqlX);
//				echo "$sqlX";
				if (pg_numrows ($resX) > 0) {
					$nota_fiscal = trim(pg_result($resX,0,nota_fiscal));
					$data_nf = trim(pg_result($resX,0,data_nf));
					$link = 1;
				}
			}else{
				$nota_fiscal = "Pendente";
				$link = 1;
			}
			$valor         = $preco;
			$taxa          = 0;
			$ipi           = 0;
			$total         = $qtde * $valor;

			if(($x % 2) == 0) $bg = '#E6EFFF';
			else $bg = '#F9FBFF';
			if ($login_fabrica == 1 and $peca_sem_estoque =="t" and $pedido_faturado=='t') $bg = "#e0d7a7";
			/*hd3636 somente postos que pedem faturado*/
			echo "<tr class='Conteudo' bgcolor='$bg'>";

			echo "<td  rowspan='3'>" . $referencia . " - " . $nome . "</td>";
			echo "<td  rowspan ='3' align='center'>" . $defeito . "</td>";
			echo "<td align='center'>" . $servico_realizado_descricao . "</td>";
			echo "<td align='center'>" . $qtde . "</td>";
			if ($ocultar_valor == 0) {
				echo "<td align='right' nowrap>";
				if ($valor > 0 OR $reembolso == 't') { # mudei aqui :::::: Ricardo
					echo "R$ " . number_format($valor,2,",",".");
				}else{
					echo "<input type='hidden' name='peca$aux' value='$peca'>";
					echo "R$ <input type='text' name='valor_peca$aux' size='6' maxlength='6' value='". number_format($valor,2,",",".") ."' class='frm' style='width:70px'>";
				}
				echo "</td>";
				echo "<td align='right' nowrap>R$ " . number_format($total,2,",",".") . "</td>";
				$soma_geral  = $soma_geral + $total;
			}else{
				echo "<td align='center'>-</td>";
				echo "<td align='center'>-</td>";
			}
			echo "</tr>";

			$sqlK = "SELECT 	tbl_os_item.servico_realizado,
								tbl_servico_realizado.gera_pedido
					FROM tbl_os_produto
					JOIN tbl_os_item using(os_produto)
					JOIN tbl_servico_realizado using(servico_realizado)
					WHERE tbl_os_produto.os = $os
						AND   tbl_servico_realizado.fabrica = $login_fabrica
						AND   tbl_servico_realizado.troca_de_peca is TRUE
						AND   (tbl_servico_realizado.gera_pedido is TRUE OR tbl_servico_realizado.gera_pedido is FALSE)
						AND   tbl_servico_realizado.ativo is TRUE;";

			$resK = pg_exec($con,$sqlK);//somatoria de todas as peças que há troca de peça gerando pedido.

			if(pg_numrows($resK) > 0) {
				$n_servico_realizado = pg_result($resK,0,servico_realizado);
				$n_gera_pedido       = pg_result($resK,0,gera_pedido);
			}else{
				$n_servico_realizado = 0;
			}

			if($n_servico_realizado > 0){
				echo "<tr class='Titulo'>";
				if($n_gera_pedido == 't'){
					echo "<td align='left' colspan='3' bgcolor='$bg'  style='font-weight: normal'><B>Pedido:</B> ";
					echo (!empty($seu_pedido)) ? $seu_pedido : ((!empty($pedido_black)) ? $pedido_black : '-');
					echo "&nbsp;<B>N. Fiscal:</B> ";
					if($nota_fiscal > 0) echo " $nota_fiscal";
					else echo "$nota_fiscal";
					echo "</td>";
				}else{
					echo "<td align='left' colspan='3' bgcolor='$bg' style='font-weight: normal'></td>";
				}

				$sqlQ = "SELECT os_sedex
							FROM tbl_os_sedex
							JOIN tbl_os_extra     ON tbl_os_extra.extrato = tbl_os_sedex.extrato
							JOIN tbl_os           ON tbl_os_extra.os      = tbl_os.os
						WHERE tbl_os.os = $os
						AND   tbl_os_sedex.fabrica = $login_fabrica
						AND   tbl_os_sedex.extrato = $extrato
						AND   tbl_os_sedex.obs ilike '%$referencia%'";
				$resQ = pg_exec($con,$sqlQ);

				if(pg_numrows($resQ) == 0 OR $preco > 0){ 
					echo "<INPUT TYPE='hidden' NAME='os_item$x' value='$os_item'>";
					echo "<input type='hidden' name='btn' value=''>";
					echo "<td rowspan ='2'  bgcolor='$bg' >";
					if(strlen($data_envio_financeiro)>0 and $login_fabrica == 1){
						echo "<span TITLE='Não pode excluir porque já foi enviado para o financeiro no dia $data_envio_financeiro!'><b>Excluir</b></span>";
					}else{
						echo "<b onClick=\"MostraEsconde('conteudo$os_item')\" style='cursor:pointer; cursor:hand;'>Excluir</b> ";
					}
					echo "</td>";
				}else{
					echo "<td style='color:#FF0000' bgcolor='$bg'>Excluída</td>";
				}
				echo "</tr>";
				echo "<tr bgcolor='$bg'>";
					echo "<td colspan='3' align='right' style='font-size: 12px'> ";
						echo "<div id='conteudo$os_item' style='display: none;'>";
						echo "Motivo da exclusão: <INPUT TYPE=\"text\" NAME='motivo".$x."'>&nbsp;&nbsp;<img src='imagens/btn_excluir.gif' style='cursor:pointer' onclick=\"javascript: if (document.frmos.btn_os.value == '' ) { document.frmos.btn_os.value='Excluir' ;  document.frmos.submit() } else { alert ('Aguarde submissão') }\" ALT='Excluir a peça' border='0'>"; //<INPUT TYPE='submit' name='excluir' value='Excluir'>";
						echo "</div>";
					echo "</td>";
				echo "</tr>";
			}else{
				echo "<tr class='Titulo'>";
					echo "<td colspan='4'>&nbsp;</td>";
				echo "</tr>";
			}
			//if ($login_fabrica == 1 and $peca_sem_estoque =="t"){
			/*hd3636 somente postos que pedem faturado*/
			if (($login_fabrica == 1 OR $login_fabrica == 7 )and $pedido_faturado=='t'){
				$zsql = "SELECT obs                               ,
								TO_CHAR(data,'DD/MM/YYYY') AS data
							FROM tbl_estoque_posto_movimento
							WHERE fabrica = $login_fabrica
							AND posto     = $posto
							AND peca      = $peca
							AND os_item   = $os_item
							AND obs notnull order by data desc limit 1; ";
				$zres = pg_exec($con,$zsql);

				echo "<tr class='Conteudo' bgcolor='$bg'>";
				echo "<td style='font-size: 10px' colspan='2'>";
				if ($peca_sem_estoque =="t"){
					echo "<font color='#e30e0e'>*Sem peça no estoque!</font>";
					echo "&nbsp;&nbsp; |&nbsp;&nbsp; ";
				}
				echo "<a href=\"javascript:verificarEstoque($posto,$peca);\"><B><font color='#5c340e'>Verificar estoque</font></b></a>";
				if(pg_numrows($zres)==0){
					if ($peca_sem_estoque =="t"){
						echo "&nbsp;&nbsp; |&nbsp;&nbsp; ";
						echo "<a href=\"javascript:aceitarPeca($os_item,$peca);\"><b><font color='#2d6b0b'>Aceitar peça</font></b></a> ";
						echo "&nbsp;&nbsp; |&nbsp;";
					}
				}
				echo "</td>";
				echo "<td colspan='4'>";
				if(pg_numrows($zres)>0){
					$zobs = pg_result($zres,0,obs);
					$zdata = pg_result($zres,0,data);
					echo "<B>$zdata - $zobs</b>";
				}
				echo "</td>";
				echo "</tr>";
			}
		}
		echo "<input type='hidden' name='qtde_peca' value='$aux'>";

		echo "<tr>";
		echo "<td width='90%' class='Titulo' align='center' colspan='5'>TOTAL GERAL</td>";
		echo "<td width='10%' class='Conteudo' align='right' nowrap>R$ " . number_format($soma_geral,2,",",".") . "</td>";
		echo "</tr>";

		echo "</table>";
		echo "</FORM>";
	}

	$sql2 = "SELECT observacao FROM tbl_os_status WHERE os = $os AND status_os = '71' ;";
	$res2 = pg_exec($con,$sql2);
	if(pg_numrows($res2) > 0){

		echo "<table border='0' cellpadding='2' cellspacing='2' width='50%' align='left'>";
		echo "<tr class='Titulo'>";
		echo "<td width='100%' align='left' style='font-size: 12px'>Peças excluidas</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='100%' style='font-size: 9px;' >";

		for($z = 0; $z < pg_numrows($res2); $z++){

			$os_peca = trim(pg_result($res2,$z,observacao));

			$sqlZ = "SELECT tbl_peca.referencia    ,
					tbl_peca.descricao
				FROM tbl_peca
				WHERE tbl_peca.peca = '$os_peca'
				AND fabrica = $login_fabrica ";
			$resZ = pg_exec($con,$sqlZ);

			$peca_referencia = pg_result($resZ,0,referencia);
			$peca_descricao  = pg_result($resZ,0,descricao);
			echo "$peca_referencia - $peca_descricao<br>";
		}
	}
	echo "</td>";
	echo "</tr>";
	echo "</table><br><br><br><br>";

		$sql = "SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM tbl_os
				join tbl_posto on tbl_os.posto = tbl_posto.posto
				join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				where tbl_os.os = $os
				and tbl_os.fabrica = $login_fabrica
				AND tbl_posto_fabrica.pedido_faturado is true";
		$res = pg_exec($con,$sql);
		if(pg_num_rows($res)>0){
			$codigo_posto = pg_result($res,0,codigo_posto);
			$posto_nome = pg_result($res,0,nome);
			if(strlen($data_envio_financeiro)>0 and $login_fabrica == 1){
				echo "Extrato já enviado para financeiro em $data_envio_financeiro!";
			}else{
				echo "<B><center><a href='estoque_posto_movimento.php?codigo_posto=$codigo_posto&posto_nome=$posto_nome' target='blank'>Clique aqui para fazer o acerto de peças deste posto</a></center></b>";
			}
		}

	echo "<p align='center'>";

	echo "<table border='0' cellpadding='2' cellspacing='2' width='95%' align='center'>";
	echo "<tr>";

	echo "<td width='100%' bgcolor='#FFFFFF' align='center'>";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#0000FF' size='2'>";
	echo "<a href='javascript:window.close()'><img border='0' src='imagens/btn_fechar_azul.gif' alt='Fechar'></a>";
	echo "</font>";
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<p>";
	echo "<form method='post' action='$PHP_SELF' name='frm_recalculo'>";
	echo "<input type='hidden' name='os' value='$os'>";
	if( strlen($data_envio_financeiro)>0 and $login_fabrica == 1){
		echo "Não pode recalcular esta OS - Já enviado para financeiro em $data_envio_financeiro!";
	}else{
		echo "<input type='submit' name='btn_acao' value='Recalcular OS'><font color='red'><b>ATENÇÃO:Se tiver Peça excluída, com valor ZERO não pode recalcular a OS!!!!</b></font>";
	}
	echo "</form>";
}
?>
</body>
</html>
