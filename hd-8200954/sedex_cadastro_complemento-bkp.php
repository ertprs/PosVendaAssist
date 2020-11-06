<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

if (strlen($_GET['os_sedex']) > 0)  $os_sedex = $_GET['os_sedex'];
if (strlen($_POST['os_sedex']) > 0) $os_sedex = $_POST['os_sedex'];

if (strlen($os_sedex) == 0 AND $ip <> '201.0.9.216') {
	header("Location: sedex_parametros.php");
	exit;
}

$msg_erro = "";

$btn_acao = $_POST['btn_acao'];

#--------------- Gravar Sedex ----------------------
if ($btn_acao == 'gravar') {
	$despesas      = trim($_POST["despesas"]);
	$controle      = trim($_POST["controle"]);
	$sua_os_origem = trim($_POST["sua_os_origem"]);
	
	if (strlen ($despesas) == 0) {
		$msg_erro = "Digite o valor das despesas.";
	}else{
		$xdespesas = trim($despesas);
		$xdespesas = str_replace(",",".",$xdespesas);
	}
	
	if (strlen ($controle) == 0) {
		$msg_erro = "Digite o número do controle do objeto.";
	}else{
		$xcontrole = "'". trim($controle) ."'";
	}

	if (strlen ($sua_os_origem) == 0) {
		$msg_erro = "Digite o número da OS.";
	}else{
		$xsua_os_origem = "'". trim($sua_os_origem) ."'";
	}
	
	if (strlen ($os_sedex) > 0 AND strlen($msg_erro) == 0) {
		$sql = "UPDATE	tbl_os_sedex SET
						controle      = $xcontrole,
						sua_os_origem = $xsua_os_origem,
						despesas      = to_char($xdespesas, 999999990.99)::float,
						finalizada    = current_timestamp,
						total         = to_char((total_pecas + $xdespesas), 999999990.99)::float
				WHERE	tbl_os_sedex.os_sedex = $os_sedex";
		$res = @pg_exec ($con,$sql);
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro = pg_errormessage ($con) ;
			$msg_erro = substr($msg_erro,6);
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_valida_os_sedex($os_sedex,$login_fabrica);";
			$res = @pg_exec($con,$sql);
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
				$msg_erro = substr($msg_erro,6);
			}
		}

	}
	
	if (strpos ($msg_erro,"tbl_os_sedex_sua_os_origem") > 0)
		$msg_erro = "Número da OS já cadastrada.";

	if (strlen($msg_erro) == 0) {
		header ("Location: sedex_parametros.php");
		exit;
	}
}


$title     = "OS de Despesas de Sedex";
$cabecalho = "OS de Despesas de Sedex";

$layout_menu = 'os';

include "cabecalho.php";

if ($gravou == "ok") {
	$msg = "Lançamento de OS de SEDEX efetuado com sucesso !";
}

if (strlen ($os_sedex) > 0) {
	$sql = "SELECT  tbl_os_sedex.posto_origem                       ,
					tbl_os_sedex.posto_destino                      ,
					tbl_os_sedex.solicitante                        ,
					to_char(tbl_os_sedex.data, 'DD/MM/YYYY') AS data,
					tbl_os_sedex.despesas                           ,
					tbl_os_sedex.controle                           ,
					tbl_os_sedex.sua_os_origem                      ,
					tbl_os_sedex.finalizada
			FROM    tbl_os_sedex
			WHERE   tbl_os_sedex.os_sedex = $os_sedex";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		$posto_origem  = trim (pg_result ($res,0,posto_origem));
		$posto_destino = trim (pg_result ($res,0,posto_destino));
		$solicitante   = trim (pg_result ($res,0,solicitante));
		$data          = trim (pg_result ($res,0,data));
		$despesas      = trim (pg_result ($res,0,despesas));
		$controle      = trim (pg_result ($res,0,controle));
		$sua_os_origem = trim (pg_result ($res,0,sua_os_origem));
		$finalizada    = trim (pg_result ($res,0,finalizada));
		
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto_fabrica
				JOIN    tbl_posto USING (posto)
				WHERE   tbl_posto_fabrica.posto   = $posto_origem
				AND     tbl_posto_fabrica.fabrica = $login_fabrica";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$codigo_posto_origem = trim(pg_result($res1,0,codigo_posto));
			$nome_posto_origem   = trim(pg_result($res1,0,nome));
		}
		
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto_fabrica
				JOIN    tbl_posto USING (posto)
				WHERE   tbl_posto_fabrica.posto   = $posto_destino
				AND     tbl_posto_fabrica.fabrica = $login_fabrica";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$codigo_posto_destino = trim(pg_result($res1,0,codigo_posto));
			$nome_posto_destino   = trim(pg_result($res1,0,nome));
		}
	}
}

if(strlen($despesas) == 0)      $despesas      = trim($_POST["despesas"]);
if(strlen($controle) == 0)      $controle      = trim($_POST["controle"]);
if(strlen($sua_os_origem) == 0) $sua_os_origem = trim($_POST["sua_os_origem"]);

if (strlen($msg_erro) > 0) {
	$despesas      = trim($_POST["despesas"]);
	$controle      = trim($_POST["controle"]);
	$sua_os_origem = trim($_POST["sua_os_origem"]);
}
?>

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

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<? 
		echo $msg_erro;
		$data_msg = date ('d-m-Y h:i');
		echo `echo '$data_msg ==> $msg_erro' >> /tmp/black-os-solicitacao.err`;
?>
	</td>
</tr>
</table>
<?
}
?>

<?
if ($posto_origem == $login_posto){
?>
<form name="frmdespesa" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="os_sedex" value="<? echo $os_sedex ?>">

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td class="menu_top">Controle de objeto</td>
	<td class="menu_top">Despesas</td>
	<td class="menu_top">OS</td>
</tr>
<tr>
	<td class="table_line"><input type='text' name='controle' value='<? echo $controle ?>' size=10></td>
	<td class="table_line"><input type='text' name='despesas' value='<? echo $despesas ?>' size=10></td>
	<td class="table_line"><input type='text' name='sua_os_origem' value='<? echo $sua_os_origem ?>' size=10></td>
</tr>
</table>
<?
}
?>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td colspan='2' width="100%" align='left' class="menu_top">Posto Origem da Mercadoria</td>
</tr>
<tr>
	<td width="25%" align='left' class="menu_top">Código</td>
	<td width="75%" align='left' class="menu_top">Nome</td>
</tr>
<tr>
	<td class="table_line"><? echo $codigo_posto_origem ?></td>
	<td class="table_line"><? echo $nome_posto_origem ?></td>
</tr>
<tr>
	<td width="700" class="menu_top" colspan="2">Posto Destino da Mercadoria</td>
</tr>
<tr>
	<td class="menu_top">Código</td>
	<td class="menu_top">Nome</td>
</tr>
<tr>
	<td class="table_line"><? echo $codigo_posto_destino ?></td>
	<td class="table_line"><? echo $nome_posto_destino ?></td>
</tr>
</table>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td class="menu_top">Solicitado por</td>
	<td class="menu_top">Data</td>
</tr>
<tr>
	<td class="table_line"><? echo $solicitante ?></td>
	<td class="table_line"><? echo $data ?></td>
</tr>
</table>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td class="menu_top" colspan="5">Selecione a(s) peça(s)</td>
</tr>
<tr>
	<td class="menu_top">Referência</td>
	<td class="menu_top">Descrição</td>
	<td class="menu_top">Qtde</td>
	<td class="menu_top">Preço</td>
	<td class="menu_top">Total</td>
</tr>
<?
if (strlen($os_sedex) > 0 AND strlen($erro) == 0){
	$sql = "SELECT  tbl_os_sedex_item.peca ,
					tbl_peca.descricao      ,
					tbl_os_sedex_item.preco
			FROM    tbl_os_sedex_item
			JOIN    tbl_peca ON tbl_peca.peca = tbl_os_sedex_item.peca
			WHERE   tbl_os_sedex_item.os_sedex = $os_sedex";
	$res = pg_exec ($con,$sql);

	for ($y=0; $y<pg_numrows($res); $y++) {
		$xpeca = trim(pg_result($res,$y,peca));
		$sql = "SELECT  tbl_os_sedex_item.os_sedex_item,
						tbl_peca.referencia            ,
						tbl_peca.descricao             ,
						tbl_os_sedex_item.qtde         ,
						tbl_os_sedex_item.preco
				FROM    tbl_os_sedex_item
				JOIN    tbl_peca ON tbl_peca.peca  = tbl_os_sedex_item.peca
				WHERE   tbl_os_sedex_item.peca     = $xpeca
				AND     tbl_os_sedex_item.os_sedex = $os_sedex";
		$res1 = pg_exec ($con,$sql);
		
		if (pg_numrows($res1) > 0) {
			$referencia  = trim(pg_result($res1,0,referencia));
			$descricao   = trim(pg_result($res1,0,descricao));
			$qtde        = trim(pg_result($res1,0,qtde));
			$preco       = trim(pg_result($res1,0,preco));
			$total       = $qtde * $preco;
			$total_geral = $total_geral + $total;
			
			echo "<tr>\n";
			echo "<td class='table_line'>$referencia</td>\n";
			echo "<td class='table_line'>$descricao</td>\n";
			echo "<td class='table_line' align='center'>$qtde</td>\n";
			echo "<td class='table_line' align='right'>".number_format($preco,2,",",".")."</td>\n";
			echo "<td class='table_line' align='right'>".number_format($total,2,",",".")."</td>\n";
			echo "</tr>\n";
		}
	}
	
	echo "<tr>\n";
	echo "<td colspan=4 class='table_line' align='right'><b>Total de Peças</b></td>\n";
	echo "<td class='table_line' align='right'><B>".number_format($total_geral,2,",",".")."</B></td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td colspan=4 class='table_line' align='right'><b>Total de Peças + Despesas</b></td>\n";
	echo "<td class='table_line' align='right'><b>".number_format($total_geral + $despesas,2,",",".")."</b></td>\n";
	echo "</tr>\n";
}


?>
</table>

<!-- ============================ Botoes de Acao ========================= -->
<?
if ($posto_origem == $login_posto){
?>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td align='center' width="100%">
		<!--<input type="image" src="imagens/gravar.gif" name="btngravar">-->
		<input type='hidden' name='btn_acao' value='0'>
		<img src='imagens/btn_gravar.gif' style='cursor: hand;' onclick="javascript: if ( document.frmdespesa.btn_acao.value == '0' ) { document.frmdespesa.btn_acao.value='gravar'; document.frmdespesa.submit() ; } else { alert ('Aguarde submissão...'); }">
	</td>
</tr>
</table>

</form>
<?
}
?>
<?include "rodape.php";?>