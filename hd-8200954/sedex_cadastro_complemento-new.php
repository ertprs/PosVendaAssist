<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

if (strlen($_GET['os_sedex']) > 0)  $os_sedex = $_GET['os_sedex'];
if (strlen($_POST['os_sedex']) > 0) $os_sedex = $_POST['os_sedex'];

if (strlen($os_sedex) == 0) header("Location: sedex_parametros.php");

$msg_erro = "";

$btn_acao = $_POST['btn_acao'];

##### A Ç Ã O   G R A V A R #####

if ($btn_acao == 'gravar') {
	$despesas      = trim($_POST["despesas"]);
	$controle      = trim($_POST["controle"]);
	
	if (strlen ($despesas) == 0) {
		$msg_erro = "Digite o valor das despesas.";
	}else{
		$xdespesas = trim($despesas);
		$xdespesas = str_replace(",",".",$xdespesas);
	}

	if (strlen ($controle) == 0) $msg_erro = "Digite o número do controle do objeto.";
	else $xcontrole = "'". trim($controle) ."'";

	if (strlen ($sua_os_origem) == 0) $xsua_os_origem = 'null';
	else $xsua_os_origem = "'". trim($sua_os_origem) ."'";

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

if ($gravou == "ok") $msg = "Lançamento de OS de SEDEX efetuado com sucesso !";

if (strlen($os_sedex) > 0) {
	$sql = "SELECT  tbl_os_sedex.posto_origem                       ,
					tbl_os_sedex.posto_destino                      ,
					tbl_admin.login                                 ,
					to_char(tbl_os_sedex.data, 'DD/MM/YYYY') AS data,
					tbl_os_sedex.despesas                           ,
					tbl_os_sedex.controle                           ,
					tbl_os_sedex.sua_os_destino                     ,
					to_char(tbl_os_sedex.finalizada, 'DD/MM/YYYY HH24:MI') AS finalizada
			FROM    tbl_os_sedex
			JOIN    tbl_admin USING (admin)
			WHERE   tbl_os_sedex.os_sedex = $os_sedex";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$posto_origem   = trim (pg_result ($res,0,posto_origem));
		$posto_destino  = trim (pg_result ($res,0,posto_destino));
		$solicitante    = trim (pg_result ($res,0,login));
		$data           = trim (pg_result ($res,0,data));
		$despesas       = trim (pg_result ($res,0,despesas));
		$despesas       = number_format($despesas,2,',','.');
		$controle       = trim (pg_result ($res,0,controle));
		$sua_os_destino = trim (pg_result ($res,0,sua_os_destino));
		$finalizada     = trim (pg_result ($res,0,finalizada));

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
		$res2 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res2) > 0) {
			$codigo_posto_destino = trim(pg_result($res2,0,codigo_posto));
			$nome_posto_destino   = trim(pg_result($res2,0,nome));
		}
	}
}

if(strlen($despesas) == 0) $despesas = trim($_POST["despesas"]);

if(strlen($controle) == 0) $controle = trim($_POST["controle"]);

if (strlen($msg_erro) > 0) {
	$despesas      = trim($_POST["despesas"]);
	$controle      = trim($_POST["controle"]);
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
<br>
<form name="frmdespesa" method="post" action="<?echo $PHP_SELF?>">

<?	if ($posto_origem == $login_posto OR $posto_origem == 6901){ ?>
<input type="hidden" name="os_sedex" value="<? echo $os_sedex ?>">

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr class="menu_top">
	<td>Controle de objeto</td>
	<td>Despesas</td>
	<td>Solicitado por</td>
	<td>Data</td>
<?
	if (strlen($finalizada) > 0) echo "<td>Finalizada em</td>";
?>

</tr>
<tr class="table_line">
	<td align="center">
	<? 
		if (strlen($finalizada) == 0)	echo "<input type='text' name='controle' value='$controle' size=10>\n";
		else							echo "$controle";
	?>
	</td>
	<td align="center"> R$ 
	<? 
		if (strlen($finalizada) == 0)	echo "<input type='text' name='despesas' value='$despesas' size=10>\n";
		else							echo "$despesas";
	?>
	</td>
	<td align="center"><? echo $solicitante ?></td>
	<td align="center"><? echo $data ?></td>
<?
	if (strlen($finalizada) > 0) echo "<td align='center'>$finalizada</td>";
?>
</tr>
</table>
<br>
<? } ?>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr class="menu_top">
	<td colspan="2" width="100%">Posto Origem da Mercadoria</td>
</tr>
<tr class="menu_top">
	<td width="25%">Código</td>
	<td width="75%">Nome</td>
</tr>
<tr class="table_line">
	<td align="center"><? echo $codigo_posto_origem ?></td>
	<td><? echo $nome_posto_origem ?></td>
</tr>
</table>

<br>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr class="menu_top">
	<td colspan="3" width="100%">Posto Destino da Mercadoria</td>
</tr>
<tr class="menu_top">
	<td width="25%">Código</td>
	<td width="60%">Nome</td>
	<td width="15%">OS</td>
</tr>
<tr class="table_line">
	<td align="center"><? echo $codigo_posto_destino ?></td>
	<td><? echo $nome_posto_destino ?></td>
	<td align="center"><? echo $sua_os_destino ?></td>
</tr>
</table>

<br>

<?
if (strlen($os_sedex) > 0 AND strlen($erro) == 0) {

	##### P E Ç A S #####

	$sql =	"SELECT os_sedex_item
			FROM    tbl_os_sedex_item
			WHERE   os_sedex = $os_sedex";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
		echo "	<tr class='menu_top'>\n";
		echo "		<td colspan='5'>Peça(s) selecionada(s)</td>\n";
		echo "	</tr>\n";
		echo "	<tr class='menu_top'>\n";
		echo "		<td>Referência</td>\n";
		echo "		<td>Descrição</td>\n";
		echo "		<td>Qtde</td>\n";
		echo "		<td>Preço</td>\n";
		echo "		<td>Total</td>\n";
		echo "	</tr>\n";

		$sql =	"SELECT tbl_os_sedex_item.qtde  ,
						tbl_os_sedex_item.preco ,
						tbl_peca.referencia     ,
						tbl_peca.descricao      
				FROM    tbl_os_sedex_item
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_os_sedex_item.os_sedex = $os_sedex";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
				$referencia  = pg_result($res,$i,referencia);
				$descricao   = pg_result($res,$i,descricao);
				$qtde        = pg_result($res,$i,qtde);
				$preco       = pg_result($res,$i,preco);
				$total       = $qtde * $preco;
				$total_geral = $total_geral + $total;

				echo "	<tr class='table_line'>\n";
				echo "		<td align='center'>$referencia</td>\n";
				echo "		<td>$descricao</td>\n";
				echo "		<td align='center'>$qtde</td>\n";
				echo "		<td> R$ ".number_format($preco,2,",",".")."</td>\n";
				echo "		<td> R$ ".number_format($total,2,",",".")."</td>\n";
				echo "	</tr>\n";
			}
			echo "	<tr class='table_line'>\n";
			echo "		<td colspan='4' align='right'><b>Total de Peças</b></td>\n";
			echo "		<td><B> R$ ".number_format($total_geral,2,",",".")."</B></td>\n";
			echo "	</tr>\n";
			echo "	<tr class='table_line'>\n";
			echo "		<td colspan='4' align='right'><b>Total de Peças + Despesas</b></td>\n";
			echo "		<td><b> R$ ".number_format($total_geral + $despesas,2,",",".")."</b></td>\n";
			echo "	</tr>\n";
		}
		echo "</table>\n";
	}

	##### P R O D U T O S #####

	$sql =	"SELECT os_sedex_item_produto
			FROM    tbl_os_sedex_item_produto
			WHERE   os_sedex = $os_sedex";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
		echo "	<tr class='menu_top'>\n";
		echo "		<td colspan='5'>Produto(s) selecionado(s)</td>\n";
		echo "	</tr>\n";
		echo "	<tr class='menu_top'>\n";
		echo "		<td>Referência</td>\n";
		echo "		<td>Descrição</td>\n";
		echo "		<td>Qtde</td>\n";
		echo "	</tr>\n";

		$sql =	"SELECT tbl_os_sedex_item_produto.qtde  ,
						tbl_produto.referencia          ,
						tbl_produto.descricao           
				FROM    tbl_os_sedex_item_produto
				JOIN    tbl_produto USING (produto)
				WHERE   tbl_os_sedex_item_produto.os_sedex = $os_sedex";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
				$referencia  = pg_result($res,$i,referencia);
				$descricao   = pg_result($res,$i,descricao);
				$qtde        = pg_result($res,$i,qtde);

				echo "	<tr class='table_line'>\n";
				echo "		<td>$referencia</td>\n";
				echo "		<td>$descricao</td>\n";
				echo "		<td align='center'>$qtde</td>\n";
				echo "	</tr>\n";
			}
		}
		echo "</table>\n";
	}
}
?>

<? if ($posto_origem == $login_posto OR $posto_origem == 6901){ ?>
<input type='hidden' name='btn_acao' value='0'>
<center><img src='imagens/btn_gravar.gif' style='cursor: hand;' onclick="javascript: if ( document.frmdespesa.btn_acao.value == '0' ) { document.frmdespesa.btn_acao.value='gravar'; document.frmdespesa.submit() ; } else { alert ('Aguarde submissão...'); }"></center>
<? } ?>

</form>

<?include "rodape.php";?>