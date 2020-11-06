<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os']) > 0)   $os = $_GET['os'];
if (strlen($_POST['os']) > 0)  $os = $_POST['os'];

$sql = "SELECT  tbl_os.sua_os,
				tbl_os.fabrica,
				tbl_produto.familia
		FROM    tbl_os
		JOIN    tbl_produto USING (produto)
		WHERE   tbl_os.os = $os";
$res = @pg_exec ($con,$sql) ;

if (@pg_result ($res,0,fabrica) <> $login_fabrica ) {
	header ("Location: os_cadastro.php");
	exit;
}

$sua_os = trim(pg_result($res,0,sua_os));
$familia = trim(pg_result($res,0,familia));

$btn_acao = strtolower ($_POST['btn_acao']);

//$msg_erro = "";

if ($btn_acao == "gravar") {

	$qtde = $_POST['qtde'];
	$os   = $_POST['os'];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT produto, serie, familia FROM tbl_os JOIN tbl_produto USING (produto) WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql) ;

	$produto = pg_result ($res,0,produto);
	$serie   = pg_result ($res,0,serie);
	$familia = pg_result ($res,0,familia);

	$sql = "DELETE FROM tbl_os_produto WHERE os = $os";
	$res = pg_exec ($con,$sql) ;

	$sql = "INSERT INTO tbl_os_produto (os, produto, serie) VALUES ($os, $produto, '$serie')";
	$res = pg_exec ($con,$sql) ;

	$sql = "SELECT currval ('seq_os_produto')";
	$res = pg_exec ($con,$sql) ;
	$os_produto = pg_result ($res,0,0);

	for ($i = 0 ; $i < $qtde ; $i++) {
		$defeito_constatado = $_POST['defeito_constatado_' . $i];

		if (strlen ($defeito_constatado) > 0) {
			$sql = "SELECT mao_de_obra FROM tbl_familia_defeito_constatado WHERE familia = $familia AND defeito_constatado = $defeito_constatado";
			$res = pg_exec ($con,$sql) ;
			$mao_de_obra = "'".pg_result ($res,0,mao_de_obra)."'";
			$sql = "INSERT INTO tbl_os_defeito (os_produto, defeito_constatado, mao_de_obra) VALUES ($os_produto, $defeito_constatado, $mao_de_obra)";
			$res = @pg_exec ($con,$sql) ;
			$msg_erro = pg_errormessage ($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_os_defeito($os, $login_fabrica)";
		$res      = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
			$res      = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
#		header ("Location: os_finalizada.php?os=$os");
		header ("Location: os_item.php?os=$os");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$title = "Assistência Técnica - Ordem de Serviço por Defeito";
#$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";

$layout_menu = 'os';
include "cabecalho.php";

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*,
					tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_produto.linha
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			WHERE   tbl_os.os = $os";
	$res = pg_exec ($con,$sql) ;
	
	$defeito_constatado             = pg_result ($res,0,defeito_constatado);
	$causa_defeito                  = pg_result ($res,0,causa_defeito);
	$linha                          = pg_result ($res,0,linha);
	$consumidor_nome                = pg_result ($res,0,consumidor_nome);
	$sua_os                         = pg_result ($res,0,sua_os);
	$produto_os                     = pg_result ($res,0,produto);
	$produto_referencia             = pg_result ($res,0,referencia);
	$produto_descricao              = pg_result ($res,0,descricao);
	$produto_serie                  = pg_result ($res,0,serie);
	$produto_codigo_fabricacao      = pg_result ($res,0,codigo_fabricacao);
}

?>

<p>

<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<? echo $msg_erro ?>
		</font></b>
	</td>
</tr>
</table>

<? } ?>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="os" value="<?echo $os?>">
<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
<tr>
	<td>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $sua_os ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_referencia . " - " . $produto_descricao?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
			<? if ($login_fabrica == 1) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. Fabricação</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_codigo_fabricacao ?></b>
				</font>
			</td>
			<? } ?>
		</tr>
		</table>
		<hr>
	</td>
</tr>
<tr>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Defeito Constatado</b><br><br></font>
			</td>
		</tr>
<?

if (strlen($os) > 0) {
	$sql = "SELECT  tbl_defeito_constatado.descricao, tbl_defeito_constatado.defeito_constatado
			FROM    tbl_familia_defeito_constatado
			JOIN    tbl_familia USING (familia)
			JOIN	tbl_defeito_constatado USING(defeito_constatado)
			WHERE   tbl_familia.fabrica = $login_fabrica
			AND		tbl_familia_defeito_constatado.familia = $familia";
	$res = pg_exec ($con,$sql);
}

for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	echo "<tr>";
	echo "<td nowrap>";
	$checado = '';

	if (strlen ($msg_erro) > 0) {
		$defeito_constatado = $_POST['defeito_constatado_' . $i];
		if (strlen ($defeito_constatado) > 0) $checado = ' checked ';
	}

	$sql="SELECT tbl_os.os FROM tbl_os_defeito JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = $os AND tbl_os_defeito.defeito_constatado = " . pg_result ($res,$i,defeito_constatado) ;
	$resX = pg_exec ($con,$sql);
	if (pg_numrows ($resX) > 0) {
		$checado = ' checked ';
	}


	echo "<INPUT TYPE='checkbox' NAME='defeito_constatado_$i' $checado value='" . pg_result ($res,$i,defeito_constatado) . "'>&nbsp;";
	echo "<font size=2 face='Geneva, Arial, Helvetica, san-serif'>";
	echo pg_result ($res,$i,descricao);
	ECHO "</font>";
	echo "</td>";
	echo "</tr>";
}

echo "<input type='hidden' name='qtde' value='$i'>";

?>
		</table>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">

	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php";?>