<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen(trim($_GET["msg"])) > 0) $msg = trim($_GET["msg"]);

// ALTERAÇÃO DE TABELA DE PREÇOS
if (1 == 1) {
	if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("2006-01-27 08:00:00"))) { // DATA DA VOLTA
		if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("2006-01-24 13:30:00"))) { // DATA DO BLOQUEIO
			//OR date("d/m/Y H:m:s") < "02/01/2004 00:00:00") {
			$title     = "Pedido de Peças";
			$cabecalho = "Pedido de Peças";
			$layout_menu = 'pedido';
			
			include "cabecalho.php";
			
			echo "<br><br>\n";
			
			echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
			echo "<tr>\n";
			
			echo "<td width='100%' align='center'>\n";
			echo "<h4><b>";
			echo "Devido ao nosso fechamento mensal, conforme Calendário Fiscal disponível, o site ficará travado para a digitação de pedidos de peças e acessórios, retornando no dia  27/01 às 08 horas.
					<br>
					<br>
					Salientamos que os pedidos finalizados após esta data, serão enviados para a fábrica somente no dia 30/01.
					<br><br>
					Agradecemos a compreensão.
					<br>
					Faturamento de Peças.";
			echo "</b></h3>\n";
			echo "</td>\n";

			echo "</tr>\n";
			echo "</table>\n";

			include "rodape.php";
			exit;
		}
	}
}

$sql = "SELECT  tbl_posto_fabrica.codigo_posto      ,
				tbl_posto_fabrica.tipo_posto        ,
				tbl_posto_fabrica.pedido_faturado   ,
				tbl_posto_fabrica.pedido_em_garantia,
				tbl_posto.cnpj,
				tbl_posto.ie,
				tbl_posto.nome,
				tbl_posto.estado
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto USING(posto)
		WHERE   tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res_posto = @pg_exec ($con,$sql);

if (@pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}

$codigo_posto       = trim(pg_result ($res_posto,0,codigo_posto));
$tipo_posto         = trim(pg_result ($res_posto,0,tipo_posto));
$nome_posto         = trim(pg_result ($res_posto,0,nome));
$cnpj               = trim(pg_result ($res_posto,0,cnpj));
$ie                 = trim(pg_result ($res_posto,0,ie));
$estado             = trim(pg_result ($res_posto,0,estado));
$pedido_faturado    = trim(pg_result ($res_posto,0,pedido_faturado));
$pedido_em_garantia = trim(pg_result ($res_posto,0,pedido_em_garantia));

//if ($tipo_posto <> 31 and $tipo_posto <> 32 and $tipo_posto <> 33 and $tipo_posto <> 34 and $tipo_posto <> 38 and $tipo_posto <> 85 and $tipo_posto <> 86 and $tipo_posto <> 87 and $tipo_posto <> 39 and $tipo_posto <> 79 and $tipo_posto <> 80 and $tipo_posto <> 81) {
if ($pedido_faturado == 'f'){
	header ("Location: menu_pedido.php");
	exit;
}


$cook_pedido_acessorio = "";
setcookie ("cook_pedido_acessorio");

$sql = "SELECT  tbl_pedido.pedido,
				tbl_pedido.condicao
		FROM    tbl_pedido
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_pedido.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_pedido.exportado        isnull
		AND     tbl_pedido.finalizado       isnull
		AND     tbl_pedido.pedido_acessorio is true
		AND     tbl_pedido.posto   = $login_posto
		AND     tbl_pedido.admin   ISNULL
		AND     tbl_pedido.fabrica = $login_fabrica
		ORDER BY tbl_pedido.pedido DESC
		LIMIT 1;";
$res = @pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$cook_pedido_acessorio = trim(pg_result($res,0,pedido));
	setcookie ("cook_pedido_acessorio",$cook_pedido_acessorio,time()+(3600*48));
	$condicao = trim(pg_result($res,0,condicao));
}


#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_exec ($con,$sql);

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}else{
		header ("Location: $PHP_SELF");
		exit;
	}
}


#----------------------- Finalizar Pedido ------------------
if ($finalizar == 1) {
	if (strlen($msg_erro) == 0) {
		if (strlen (trim ($msg_erro)) == 0) {
			$sql = "INSERT INTO tbl_pedido_alteracao (
						pedido
					)VALUES(
						$cook_pedido_acessorio
					)";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($cook_pedido_acessorio,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$msg = $_GET['msg'];
		header ("Location: pedido_finalizado.php?pedido=$cook_pedido_acessorio&msg=".$msg);
		exit;
	}
}

#--------------- Gravar Item ----------------------
if ($btngravar == "Gravar") {
	$condicao = trim($_POST['condicao']);
	$bloco_os = intval(trim($_POST['bloco_os']));

	if (strlen($bloco_os) == 0) {
		$aux_bloco_os = 0;
	}else{
		if (is_int($bloco_os) == false) {
			$aux_bloco_os = 0;
		}else{
			$fnc          = pg_exec($con,"SELECT fnc_so_numeros('$bloco_os')");
			$aux_bloco_os = pg_result ($fnc,0,0);
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen ($condicao) == 0 AND strlen ($cook_pedido_acessorio)== 0) {
			$msg_erro = "Escolha a condição de pagamento";
		}
	}

	$sql = "SELECT tbl_tabela.tabela
			FROM   tbl_tabela
			WHERE  tbl_tabela.sigla_tabela = 'ACESS'";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) $tabela = pg_result ($res,0,0);

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con, "BEGIN TRANSACTION");

		if (strlen ($cook_pedido_acessorio) == 0) {
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						condicao       ,
						tabela         ,
						bloco_os       ,
						fabrica        ,
						tipo_pedido    ,
						pedido_acessorio
					)VALUES(
						$login_posto   ,
						'$condicao'    ,
						'$tabela'      ,
						$aux_bloco_os  ,
						$login_fabrica ,
						(SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND UPPER(trim(descricao)) = 'ACESSORIOS'),
						't'
					)";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage($con);
			}else{
				$res = pg_exec ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido_acessorio = pg_result($res,0,0);

				# cookie expira em 48 horas
				setcookie ("cook_pedido_acessorio",$cook_pedido_acessorio,time()+(3600*48));
			}
		}else{
			$sql = "UPDATE tbl_pedido SET
						tabela     = '$tabela'       ,
						condicao   = '$condicao'     ,
						bloco_os   = '$aux_bloco_os' ,
						finalizado = null
					WHERE tbl_pedido.pedido = $cook_pedido_acessorio;";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}

			if (strlen (trim ($msg_erro)) == 0) {
				$sql = "INSERT INTO tbl_pedido_alteracao (
							pedido
						)VALUES(
							$cook_pedido_acessorio
						)";
				$res = @pg_exec ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro = pg_errormessage ($con) ;
				}
			}
		}

		if (strlen (trim ($msg_erro)) == 0) {
			for ($i = 0 ; $i < 15 ; $i++) {
				$referencia = trim($_POST["referencia" . $i]);
				$qtde       = trim($_POST["qtde"       . $i]);

				$xreferencia = str_replace(" ","",$referencia);
				$xreferencia = str_replace(".","",$xreferencia);
				$xreferencia = str_replace("-","",$xreferencia);
				$xreferencia = str_replace("/","",$xreferencia);

				$sql = "SELECT tbl_peca.peca
						FROM   tbl_peca
						WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'";
				$resX = pg_exec ($con,$sql);

				if (pg_numrows($resX) > 0 AND strlen (trim ($qtde)) > 0) {
					$peca = pg_result($resX,0,0);
					$sql = "INSERT INTO tbl_pedido_item (
							pedido,
							peca  ,
							qtde
						)VALUES(
							$cook_pedido_acessorio,
							$peca       ,
							$qtde
						)";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0) {
						$res         = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = @pg_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($cook_pedido_acessorio,$peca,$login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen ($msg_erro) > 0) {
						$erro_linha  = "erro_linha" . $i;
						$$erro_linha = 1 ;
						break ;
					}
				}
				
				##### VERIFICA A QTDE DA PEÇA NO DO MÊS ANTERIOR #####
/*				if (strlen($msg_erro) == 0 && strlen($peca) > 0 && strlen($xreferencia) > 0) {
					$data_i = date("Y-m-d", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
					$data_f = date("Y-m-t", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
					$sql =	"SELECT SUM(tbl_pedido_item.qtde)
							FROM tbl_pedido_item
							JOIN tbl_pedido      ON tbl_pedido.pedido           = tbl_pedido_item.pedido
							JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
							JOIN tbl_peca        ON tbl_peca.peca               = tbl_pedido_item.peca
							WHERE tbl_pedido_item.peca = $peca
							AND   tbl_pedido.data BETWEEN '$data_i 00:00:00' AND '$data_f 23:59:59'
							AND   tbl_pedido.pedido NOT IN ($cook_pedido_acessorio)
							AND   UPPER(TRIM(tbl_tipo_pedido.descricao)) = 'ACESSORIOS';";
					$res = pg_exec($con,$sql);
					if (pg_numrows($res) > 0) {
						$qtde_total_mes = pg_result($res,0,0);
						if (strlen($qtde_total_mes) > 0 && $qtde > $qtde_total_mes) {
							$msg .= " A quantidade $qtde pedida para a peça $referencia está acima da média de $qtde_total_mes pedida normalmente. Você tem certeza que a quantidade pedida está correta? ";
						}
					}
				}*/
			}
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido_acessorio,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=".$msg);
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title     = "Pedido de Peças";
$cabecalho = "Pedido de Peças";

$layout_menu = 'pedido';
include "cabecalho.php";

?>

<!-- ---------------------- Inicio do HTML -------------------- -->

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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
</style>

<?
if ($alterar == 1) {
?>

<table width="400" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="table_line1">
		<b>Antes de lançar Pedido ou OS´s, por favor, <a href='cad_posto.php'>clique aqui</a> <br>e complete seu CNPJ e Inscrição Estadual</b>
	</td>
</tr>
</table>

<?}else{?>

<script LANGUAGE="JavaScript">
function PesquisaPeca (peca_referencia, peca_descricao, peca_qtde) {
	var url = "";
	if (peca_referencia.value != "") {
		url = "peca_pesquisa_lista_blackedecker.php?peca=" + peca_referencia.value;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=300,top=100,left=100");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.qtde			= peca_qtde;
		janela.focus();
	}
}

function fnc_fora_linha (nome, seq) {
	var url = "";
	if (nome != "") {
		url = "pesquisa_fora_linha.php?nome=" + nome + "&seq=" + seq;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.focus();
	}
}


<!-- Início
nextfield = "condpgto"; // coloque o nome do primeiro campo do form
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frmpedido.' + nextfield + '.focus()');
			return false;
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP);
// Fim -->

</script>

<? include "javascript_pesquisas.php" ?>

<? if ($tipo_posto == 4) { ?>

<!--
<table width="450" border="1" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="#FFCCCC" align='left' nowrap>
		<font face='arial' size='-1' color='#FF0000'><b>PROJETO DE UNIIFICAÇÃO DE PEDIDOS em vigor apartir de 27/09/2004</b>.</font>
	</td>
</tr>
<tr>
	<td bgcolor="#FFFFFF" align='center'>
		<font face='arial' size='-1' color='#000000'>Objetivando melhor atendê-lo, foi desenvolvido o <b>Projeto de Unificação de Pedidos</b>, que possui como principal objetivo eliminar problemas relativos ao controle de pendências, melhor controle no faturamento e embarque de pedidos.&nbsp;&nbsp;&nbsp;<? if ($posto <> "23513" and $posto <> "20741" and $posto <> "21957") { ?><a href='http://cebolinha.telecontrol.com.br/bd/xls/procedimento_distribuidor.doc' target='_blank'>Ler Procedimentos</a>&nbsp;&nbsp;&nbsp;<a href='http://cebolinha.telecontrol.com.br/bd/xls/calendario_2004.xls' target='_blank'>Ver Calendário Fiscal</a><? } ?></font>
	</td>
</tr>
</table>

<p><p>
-->

<? } ?>

<? if ($tipo_posto == 5) { ?>

<!--
<table width="450" border="1" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="#FFCCCC" align='left' nowrap>
		<font face='arial' size='-1' color='#FF0000'><b>PROJETO DE UNIIFICAÇÃO DE PEDIDOS EM VIGOR A PARTIR DE 27/09/2004</b>.</font>
	</td>
</tr>
<tr>
	<td bgcolor="#FFFFFF" align='center'>
		<font face='arial' size='-1' color='#000000'>Objetivando melhor atendê-lo, foi desenvolvido o <b>Projeto de Unificação de Pedidos</b>, que possui como principal objetivo eliminar problemas relativos ao controle de pendências, melhor controle no faturamento e embarque de pedidos.&nbsp;&nbsp;&nbsp;<a href='http://cebolinha.telecontrol.com.br/bd/xls/procedimento_vip.doc' target='_blank'>Ler Procedimentos</a></font>
	</td>
</tr>
</table>

<p><p>
-->

<? } ?>

<p>

<?
if (strlen($msg) > 0) $msg_erro = $msg;

if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Fail to add null value in not null attribute peca") > 0)
		$msg_erro = "Peça não existe";
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<p>
<? } ?>

<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">

<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='menu_top' colspan='2'>INFORMAÇÕES IMPORTANTES</td>
</tr>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** PROCEDIMENTO PARA PEDIDOS DE ACESSÓRIOS</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/comunicado_visualiza.php?comunicado=72' target='_blank'>Clique aqui</a></b></td>
</tr>
</table>

<br>

<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='menu_top' colspan='2'>CATÁLOGO DOS PRODUTOS</td>
</tr>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td align='center'><a href='http://www.dewalt.com.br/products/Aces/listTipoCate.asp' target='_blank'>DEWALT</a></td>
	<td align='center'><a href='http://www.bdhobby.com.br/prod/aces/ListTipoCate.asp' target='_blank'>HOBBY</a></td>
</tr>
</table>

<br>

<table width="500" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='left' class="menu_top">
		<b>Cond. Pgto.</b>
	</td>
</tr>
<tr>
	<td align='center' nowrap class="table_line1">
		<select name="condicao" class="frm" onFocus="nextfield ='bloco_os'">
		<option value=''></option>
<?
			$sql = "SELECT   tbl_condicao.*
					FROM     tbl_condicao
					JOIN     tbl_posto_condicao USING (condicao)
					WHERE    tbl_posto_condicao.posto = $login_posto
					AND      tbl_condicao.fabrica     = $login_fabrica
					AND      tbl_posto_condicao.visivel     IS TRUE
					AND      tbl_condicao.visivel_acessorio IS TRUE
					ORDER BY lpad(trim(tbl_condicao.codigo_condicao), 10, 0) ";
			$res = pg_exec ($con,$sql);

			for ($x=0; $x < pg_numrows($res); $x++) {
				echo "<option "; if ($condicao == pg_result($res,$x,condicao)) echo " SELECTED "; echo " value='" . pg_result($res,$x,condicao) . "'>" . pg_result($res,$x,descricao) . "</option>\n";
			}
?>
		</select>
		<br>
		<font face='arial' size='-2' color='#336633'><b>Favor escolher a condição de pagamento</b></font>
	</td>
</tr>
</table>
<br>

<table width="500" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="35%" align='center' class="menu_top">
		<b>Referência</b>
	</td>
	<td width="50%" align='center' class="menu_top">
		<b>Descrição</b>
	</td>
	<td width="15%" align='center' class="menu_top">
		<b>Quantidade</b>
	</td>
</tr>


<?
for ($i = 0 ; $i < 15 ; $i ++) {
	$referencia = "referencia" . $i;
	$descricao  = "descricao" . $i;
	$qtde       = "qtde" . $i;
	$erro_linha = "erro_linha" . $i;

	$referencia = $$referencia;
	$descricao  = $$descricao;
	$qtde       = $$qtde;
	$erro_linha = $$erro_linha;

	$prox = $i + 1;
	$done = 14;

	$cor_erro = "#ffffff";
	if ($erro_linha == 1) $cor_erro = "#AA6666";

?>

<tr bgcolor="<?echo $cor_erro?>">
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" size="15" maxlength="15" value="<? echo $referencia ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'" onblur="PesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,window.document.frmpedido.qtde<? echo $i ?>);">
	</td>
	<td align='center'>
		<input type="text" name="descricao<? echo $i ?>" size="30" maxlength="30" value="<? echo $descricao ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
	</td>
	<td align='center'>
		<input type="text" name="qtde<? echo $i ?>" size="4" maxlength="4" value="<? echo $qtde ?>" class="textbox" <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>

<? } ?>

<tr>
	<td align='center' colspan='2'>
		<br>
		<input type="hidden" name="btngravar" value="x">
		<a href="javascript: document.frmpedido.btngravar.value='Gravar' ; document.frmpedido.submit() ; "><img src='imagens/btn_gravar.gif' border='0'></a>
	</td>
</tr>
</table>
<br>

<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b>PARA CONTINUAR A DIGITAR ITENS NESTE PEDIDO, BASTA GRAVAR E EM SEGUIDA CONTINUAR DIGITANDO.</b></font></p>
	</td>
</tr>
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
	</td>
</tr>

</form>
</table>


<?
if (strlen ($cook_pedido_acessorio) > 0) {
?>
<br>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td colspan="4" align="center" class='menu_top'>
		<font face="arial" color="#ffffff" size="+2"><b>Resumo do Pedido</b></font>
	</td>
</tr>

<tr>
	<td width="25%" align='center' class="menu_top">
		<b>Referência</b>
	</td>
	<td width="50%" align='center' class="menu_top">
		<b>Descrição</b>
	</td>
	<td width="15%" align='center' class="menu_top">
		<b>Quantidade</b>
	</td>
	<td width="10%" align='center' class="menu_top">
		<b>Preço</b>
	</td>
</tr>

<?
	$sql = "SELECT	a.oid    ,
					a.*      ,
					referencia,
					descricao
			FROM	tbl_peca
			JOIN	(
						SELECT	oid,*
						FROM	tbl_pedido_item
						WHERE	pedido = $cook_pedido_acessorio
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = pg_exec ($con,$sql);
	$total = 0;
	for ($i = 0 ; $i < @pg_numrows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor'>";
		echo "<td width='25%' align='left' class='table_line1' nowrap>";

		echo "<a href='$PHP_SELF?delete=" . pg_result ($res,$i,pedido_item) . "'>";

		echo "<img src='imagens/btn_excluir.gif' align='absmiddle' hspace='5' border='0'>";
		echo "</a>";
		echo pg_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='50%' align='left' class='table_line1'>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' align='center' class='table_line1'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right' class='table_line1'>";
		echo number_format (pg_result ($res,$i,preco),2,",",".");
		echo "</td>";

		echo "</tr>";

		$total = $total + (pg_result ($res,$i,preco) * pg_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="3" class="menu_top">
		<b>T O T A L</b>
	</td>
	<td align='right' class="menu_top" style='text-align:right'>
		<b>
		<? echo number_format ($total,2,",",".") ?>
		</b>
	</td>
</tr>
</table>



<!-- ============================ Botoes de Acao ========================= -->


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center'>
		<br><a href="<?echo $PHP_SELF?>?finalizar=1&linha=<?echo $linha?>&unificar=t"><img src='imagens/btn_finalizar.gif' border='0'></a><br><br>
	</td>
</tr>
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b>CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR ACIMA.</b></font></p>
	</td>
</tr>
</table>

<?
}
?>


<? } # Final do IF do CNPJ e IE ?>

<p>

<?include "rodape.php";?>
