<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if (strlen($_GET["faq"]) > 0)  $faq = trim($_GET["faq"]);

if (strlen($_POST["faq"]) > 0)  $faq = trim($_POST["faq"]);

if (strlen($_POST["btn_acao"]) > 0)  $btn_acao  = trim($_POST["btn_acao"]);

if (strlen($faq) == 0){
	header("Location: faq_situacao.php");
	exit;
}

$faq_causa = $_GET['excluir'];
if (strlen ($faq_causa) > 0) {
	$sql = "DELETE FROM tbl_faq_causa
			WHERE  tbl_faq_causa.faq = tbl_faq.faq
			AND    tbl_produto.produto = tbl_faq.produto
			AND    tbl_linha.linha = tbl_produto.linha
			AND    tbl_linha.fabrica = $login_fabrica
			AND    tbl_faq_causa.faq_causa = $faq_causa;";
	$res = @pg_exec ($con,$sql);

	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);
	header("Location: $PHP_SELF?faq=$faq");
	exit;
}

if (strlen($_GET["faq_causa"]) > 0)  $faq_causa = trim($_GET["faq_causa"]);

if (strlen($_POST["faq_causa"]) > 0) $faq_causa = trim($_POST["faq_causa"]);

if ($btn_acao == "gravar") {

	if (strlen($_POST["causa"]) > 0) {
		$aux_causa = "'". trim($_POST["causa"]) ."'";
	}else{
		$msg_erro = "Informe a causa.";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($faq_causa) == 0) {

			$sql = "INSERT INTO tbl_faq_causa (
						faq       ,
						causa
					) VALUES (
						$faq      ,
						$aux_causa
					);";
		}else {
			$sql = "UPDATE tbl_faq_causa SET
						causa  = $aux_causa
					WHERE tbl_faq_causa.faq = tbl_faq.faq
					AND   tbl_faq.produto = tbl_produto.produto
					AND    tbl_produto.linha = tbl_linha.linha
					AND    tbl_linha.fabrica = $login_fabrica
					AND    tbl_faq_causa.faq_causa = $faq_causa;";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = @pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			if (strlen($faq_causa) == 0) {
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_faq_causa')");
				$faq_causa  = pg_result ($res,0,0);
			}
		}

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?faq=$faq");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			$causa      = $_POST["causa"];
			
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}//fim if msg erro
}

###CARREGA REGISTRO
$faq_causa = $_GET ['faq_causa'];

if (strlen($faq_causa) > 0) {
	$sql = "SELECT  tbl_faq_causa.causa   ,
					tbl_faq_causa.faq   ,
					tbl_faq.situacao      ,
					tbl_produto.referencia,
					tbl_produto.descricao 
			FROM    tbl_faq_causa
			JOIN    tbl_faq USING (faq)
			JOIN    tbl_produto on tbl_faq.produto = tbl_produto.produto
			JOIN    tbl_linha on tbl_produto.linha = tbl_linha.linha
			WHERE   tbl_faq_causa.faq_causa =$faq_causa
			AND     tbl_linha.fabrica       = $login_fabrica;";
	$res = @pg_exec($con,$sql);

	if(pg_numrows ($res) > 0){
		$faq            = trim(pg_result($res,0,faq));
		$causa          = trim(pg_result($res,0,causa));
		$referencia     = trim(pg_result($res,0,referencia));
		$descricao      = trim(pg_result($res,0,descricao));
		$situacao       = trim(pg_result($res,0,situacao));
	}

}else if (strlen($faq) > 0) {//se vier como faq é porque ainda não foi colocada a causa
	$sql = "SELECT  tbl_faq.situacao      ,
					tbl_produto.referencia,
					tbl_produto.descricao 
			FROM    tbl_faq
			JOIN    tbl_produto ON tbl_produto.produto = tbl_faq.produto
			JOIN    tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE   tbl_faq.faq =$faq
			AND     tbl_linha.fabrica = $login_fabrica;";
	$res = @pg_exec($con,$sql);

	if(pg_numrows ($res) > 0){
		$referencia     = trim(pg_result($res,0,referencia));
		$descricao      = trim(pg_result($res,0,descricao));
		$situacao       = trim(pg_result($res,0,situacao));
	}
}


$visual_black = "manutencao-admin";

$title = "Cadastro das Causas ";
$cabecalho = "Cadastro das Causas";
$layout_menu = "cadastro";
include 'cabecalho.php';
?>
<style type="text/css">
.Relatorio{
	font-family: Verdana,sans;
	font-size:10px;
	border: 1px #000099 solid;
}
.Relatorio thead{
	background: #596D9B ;
	color:#FFFFFF;
}
</style>
<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?}?> 
<p>

<form name="frm_situacao" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="faq_causa" value="<? echo $faq_causa ?>">
<input type="hidden" name="faq" value="<? echo $faq ?>">

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7; font-size:10px;' align='center' width='700' border='0'>
			<tr  bgcolor="#596D9B" >
				<th align='left' colspan='4'><font size='2' color='#ffffff'>Cadastro da Causa do FAQ</font></th>
			</tr>
			<tr>
				<th>REFERÊNCIA</th>
				<td width='30%' nowrap><? echo $referencia ?></td>
				<th>DESCRIÇÃO</th>
				<td width='70%' nowrap><? echo $descricao ?></td>
			</tr>
				<th>SITUAÇÃO</th>
				<td colspan='3'><?echo $situacao ?></td>
			</tr>
			<tr>
				<th>NOVA CAUSA</th>
				<td colspan='3'>
				<TEXTAREA rows='2' cols='60' name="causa" value="" class='frm'><? echo $causa ?></TEXTAREA>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_situacao.btn_acao.value == '' ) { document.frm_situacao.btn_acao.value='gravar' ; document.frm_situacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<img src="imagens/btn_voltar.gif" style="cursor: pointer;" onclick="javascript: window.location='<? echo "faq_situacao.php" ?>'" ALT="Retornar para Situação" border='0'>
<!--<img src="imagens/btn_apagar.gif" onclick="javascript: if (document.frm_situacao.btn_acao.value == '' ) { document.frm_situacao.btn_acao.value='deletar' ; document.frm_situacao.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Solução" border='0'>-->

</center>

<p>

<?
echo"<TABLE width='700' border='0' cellspacing='1' cellpadding='1' align='center' class='Relatorio'>";
echo "<thead>";
echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
echo "<TR>";
echo "<TD>CAUSA</TD>";
echo "<TD COLSPAN='2' width = '30%'>AÇÕES</TD>";
echo "</TR>";
echo "</thead>";
echo "<tbody>";

$sql = "SELECT  tbl_faq_causa.faq_causa  ,
				tbl_faq_causa.faq        ,
				tbl_faq_causa.causa
		FROM    tbl_faq_causa 
		JOIN    tbl_faq USING (faq)
		JOIN    tbl_produto USING (produto)
		JOIN    tbl_linha USING (linha)
		WHERE   tbl_faq.produto = tbl_produto.produto
		AND     tbl_produto.linha = tbl_linha.linha
		AND     tbl_linha.fabrica = $login_fabrica
		AND     tbl_faq_causa.faq = $faq;";
$res = @pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
	
	echo "<tr>";
	
	echo "<td align='left'>";
	echo "<a href='$PHP_SELF?faq_causa=" . pg_result ($res,$i,faq_causa) . "'>";
	echo pg_result ($res,$i,causa);
	echo "</a>";
	echo "</td>";

	echo "<td>";
	echo "<a href='faq_solucao.php?faq_causa=" . pg_result ($res,$i,faq_causa) . "'><img src='imagens/btn_lancarnovasolucao.gif'>";
	echo "</a>";
	echo "</td>";
	echo "<td>";
	echo "<A HREF=\"javascript: if (confirm ('Deseja excluir a causa " . pg_result ($res,$i,faq_causa) . " ?') == true) { window.location='$PHP_SELF?excluir=" . pg_result ($res,$i,faq_causa) . "&faq=$faq' }\"><img src='imagens/btn_excluir.gif'>";
	echo "</A>";
	echo "</td>";

	echo "</tr>";
}
echo "</tbody>";
echo"</TABLE>";

?>
<? include "rodape.php"; ?>