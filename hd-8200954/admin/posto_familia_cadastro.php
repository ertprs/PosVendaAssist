<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

// HD54668 somente para Colormaq

$msg_erro = "";
$msg_debug = "";


if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {
	$qtde_posto   = $_POST['qtde_posto'];
	$qtde_familia = $_POST['qtde_familia'];
	for ($i = 0 ; $i < $qtde_posto ; $i++) {
		$posto = $_POST ['posto'.$i];
		for ($x = 0 ; $x < $qtde_familia ; $x++){
			$familia = $_POST['familia'.$x];
			$paga_deslocamento = $_POST['paga_deslocamento'.$i.$x];
			if ($paga_deslocamento != 't') {
				$paga_deslocamento = 'f';
			}
			$inserir = $_POST['inserir'.$i.$x];

			if ($inserir == 's'){
				if($paga_deslocamento == 't' ){
					$sql = "INSERT INTO tbl_posto_familia (
								posto            ,
								familia          ,
								paga_deslocamento
							) VALUES (
								$posto            ,
								$familia          ,
								'$paga_deslocamento'
							)";
					//echo $sql; exit;
					$res = pg_exec ($con,$sql);
					if (pg_errormessage ($con) > 0) $msg_erro .= pg_errormessage ($con);
				}
			}
			if ($inserir == 'n'){
				$sql = "UPDATE tbl_posto_familia SET
							paga_deslocamento = '$paga_deslocamento'
						WHERE posto = $posto
						AND familia = $familia;";
				$res = pg_exec ($con,$sql);
				if (pg_errormessage ($con) > 0) $msg_erro .= pg_errormessage ($con);
			}
		}
	}
	if (strlen ($msg_erro) == 0) {
		echo "<script language='JavaScript'>
				alert('Gravado com sucesso!');
			</script>";
	}
}
?>

<style type="text/css">

.text_curto {
	text-align: center;
	font-weight: bold;
	color: #000;
	background-color: #FF6666;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>
<?
$visual_black = "manutencao-admin";

$title       = "Postos Autorizados X Deslocamentos";
$cabecalho   = "Postos Autorizados X Deslocamentos";
$layout_menu = "cadastro";
include 'cabecalho.php';
?>
<table width='600' align='center' border='0' cellspacing='2' bgcolor='#ffffff'>
<tr>
	<td align='center' bgcolor='#d9e2ef'>
		<font face='arial, verdana' color='#596d9b'>
		Para autorizar Pagto de Deslocamento, selecione a "Família de Produto" de cada POSTO depois clique em Gravar.
		</font>
	</td>
</tr>
<tr>

<?php
$params = '';
if (!empty($_SERVER['QUERY_STRING'])) {
	$params = '?' . $_SERVER['QUERY_STRING'];
}
$action = $_SERVER['PHP_SELF'] . $params; ?>
<form name="frm_posto" method="post" action="<? echo $action ?>">

<?php

if (empty($_GET['i'])) {
	$inicial = 'A';
} else {
	$inicial = $_GET['i'];
}

$arrABC = range('A', 'Z');
$arrLetras = array();

$prepare = pg_prepare($con, "query_qtde", "SELECT count(tbl_posto.posto) FROM tbl_posto JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto WHERE tbl_posto_fabrica.fabrica = 50 and tbl_posto_fabrica.credenciamento = 'CREDENCIADO' and  valor_km > 0 AND UPPER(tbl_posto.nome) like $1");

foreach ($arrABC as $letra) {
	$result = pg_execute($con, "query_qtde", array("$letra%"));
	$qtde = pg_fetch_result($result, 0, 0);

	if ($qtde > 0) {
		$arrLetras[] = $letra;

	}
}

unset($arrABC);

echo '<div style="text-align: center; margin-bottom: 20px;">';
foreach ($arrLetras as $l) {
	echo '<span style="padding-left: 10px;">';
		echo '<a href="?i=' , $l , '"';
		if ($inicial == $l) {
			echo ' style="font-size: 14px;"';
		}
		echo '>' , $l , '</a>';
	echo '</span>';
}

echo '</div>';

unset($arrLetras);

$sql = "SELECT  tbl_posto.posto , tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM	 tbl_posto
				JOIN	 tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE    tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.credenciamento = 'CREDENCIADO' and  valor_km > 0
				AND tbl_posto.nome ILIKE '$inicial%'
				ORDER BY tbl_posto.nome;";
$res = pg_exec ($con,$sql);
$qtde_posto = pg_numrows($res);

$sql = "SELECT   tbl_familia.familia          ,
				 tbl_familia.descricao        ,
				 tbl_familia.codigo_familia
				FROM     tbl_familia
				WHERE    tbl_familia.fabrica = $login_fabrica and tbl_familia.ativo = 't'
				ORDER BY tbl_familia.codigo_familia;";
$res1 = pg_exec ($con,$sql);
$qtde_familia = pg_numrows($res1);

if ($qtde_posto > 0 AND $qtde_familia > 0) {
	echo "<table align='center' border='1' cellpadding='3' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<input type='hidden' name='qtde_posto' value=$qtde_posto>";
	echo "<input type='hidden' name='qtde_familia' value=$qtde_familia>";

	for ($i = 0 ; $i < $qtde_posto ; $i++) {
		$posto = pg_result($res,$i,posto);
		if ($i % 20 == 0) {
			flush();
			echo "<tr class='Titulo'>";
			echo "<td>NOME</td>";

			for ($x = 0 ; $x < $qtde_familia ; $x++){
				$familia        = trim(pg_result($res1,$x,familia));
				$descricao      = trim(pg_result($res1,$x,descricao));
				$codigo_familia = trim(pg_result($res1,$x,codigo_familia));
				echo "<td align='center'>$codigo_familia<br>$descricao";
				echo "<input type='hidden' name='familia$x' value=$familia></td>";
			}
			echo "</tr>\n";
		}
		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

		echo "<tr class='Conteudo' bgcolor='$cor'>";
		echo "<td nowrap align='left'>
			<input type='hidden' name='posto$i' value=$posto>".pg_result($res,$i,nome)."</td>";

		for ($x = 0 ; $x < $qtde_familia ; $x++){
			$familia = trim(pg_result($res1,$x,familia));
			$sql = "SELECT tbl_posto_familia.paga_deslocamento
						FROM  tbl_posto_familia
						WHERE tbl_posto_familia.posto = $posto
						AND   tbl_posto_familia.familia = $familia";
			$res2 = pg_exec ($con,$sql);
			echo "<td align='center'>";
			echo "<input type='checkbox' name='paga_deslocamento$i$x' value='t'";
			if (pg_numrows($res2) > 0) {
				$paga_deslocamento = pg_result($res2,0,paga_deslocamento);
				if ($paga_deslocamento == 't'){
					echo "checked";
				}
				echo ">";
				echo "<input type='hidden' name='inserir$i$x' value='n'>";
			}else{
				echo ">";
				echo "<input type='hidden' name='inserir$i$x' value='s'>";
			}
			echo "</td>";
		}
		echo "</tr>\n";
	}
	echo "</table>";
}

?>

<p>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<p>

</form>
</tr>
</table>
<? include "rodape.php"; ?>
