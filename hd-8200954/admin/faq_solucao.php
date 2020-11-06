<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if (strlen($_GET["faq_causa"]) > 0) $faq_causa = trim($_GET["faq_causa"]);

if (strlen($_POST["faq_causa"]) > 0) $faq_causa = trim($_POST["faq_causa"]);

if (strlen($_GET["faq"]) > 0) $faq = trim($_GET["faq"]);

if (strlen($_POST["faq"]) > 0) $faq = trim($_POST["faq"]);

if (strlen($faq_causa) == 0){
	header("Location: faq_situacao.php");
	exit;
}

if (strlen($_POST["btn_acao"]) > 0)  $btn_acao  = trim($_POST["btn_acao"]);

$faq_solucao = $_GET['excluir'];
if (strlen ($faq_solucao) > 0) {
	
	$sql = "DELETE FROM tbl_faq_solucao
			USING   tbl_faq_causa, tbl_faq, tbl_produto
			WHERE   tbl_faq_solucao.faq_causa = tbl_faq_causa.faq_causa
			AND     tbl_faq_causa.faq = tbl_faq.faq
			AND     tbl_faq.produto = tbl_produto.produto
			AND     tbl_produto.fabrica_i = $login_fabrica
			AND     tbl_faq_solucao.faq_solucao = $faq_solucao;";
	$res = @pg_exec ($con,$sql);

	
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);
	header("Location: $PHP_SELF?faq_causa=$faq_causa");
	exit;
}

if (strlen($_GET["faq_solucao"]) > 0) $faq_solucao = trim($_GET["faq_solucao"]);

if (strlen($_POST["faq_solucao"]) > 0) $faq_solucao = trim($_POST["faq_solucao"]);


if ($btn_acao == "gravar") {

	if (strlen($_POST["solucao"]) > 0) {
		$aux_solucao = "'". trim($_POST["solucao"]) ."'";
	}else{
		$msg_erro = "Informe a solução.";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($faq_solucao) == 0) {
			$sql = "INSERT INTO tbl_faq_solucao (
						faq_causa,
						solucao  
					) VALUES (
						$faq_causa  ,
						$aux_solucao
					);";
		}else {
			$sql = "UPDATE tbl_faq_solucao SET 
							solucao = $aux_solucao
					FROM   tbl_faq_causa, tbl_faq, tbl_produto
					WHERE  tbl_faq_solucao.faq_causa = tbl_faq_causa.faq_causa
					AND    tbl_faq_causa.faq = tbl_faq.faq
					AND    tbl_faq.produto = tbl_produto.produto
					AND    tbl_produto.fabrica_i = $login_fabrica
					AND    tbl_faq_solucao.faq_solucao=$faq_solucao;";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro = @pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			if (strlen($faq_solucao) == 0) {
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_faq_solucao')");
				$faq_solucao  = pg_result ($res,0,0);
			}
		}

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?faq_causa=$faq_causa");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			$solucao  = $_POST["solucao"];

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}//fim if msg erro
}

###CARREGA REGISTRO
$faq_solucao = $_GET ['faq_solucao'];

if (strlen($faq_solucao) > 0) {
	$sql = "SELECT  tbl_faq_solucao.solucao   ,
					tbl_faq_solucao.faq_causa ,
					tbl_faq_causa.causa       ,
					tbl_faq_causa.faq         ,
					tbl_faq.situacao          ,
					tbl_produto.referencia    ,
					tbl_produto.descricao     
			FROM    tbl_faq_solucao
			JOIN    tbl_faq_causa USING (faq_causa)
			JOIN    tbl_faq      ON tbl_faq.faq =  tbl_faq_causa.faq
			JOIN    tbl_produto USING (produto)
			WHERE   tbl_faq_causa.faq = tbl_faq.faq
			AND     tbl_faq.produto  = tbl_produto.produto
			AND     tbl_produto.fabrica_i = $login_fabrica
			AND    tbl_faq_solucao.faq_solucao = $faq_solucao;";
	$res = pg_exec($con,$sql);

	if(pg_numrows ($res) > 0){
		$faq_causa      = trim(pg_result($res,0,faq_causa));
		$faq            = trim(pg_result($res,0,faq));
		$solucao        = trim(pg_result($res,0,solucao));
		$causa          = trim(pg_result($res,0,causa));
		$referencia     = trim(pg_result($res,0,referencia));
		$descricao      = trim(pg_result($res,0,descricao));
		$situacao       = trim(pg_result($res,0,situacao));
	}
	
}else if (strlen($faq_causa) > 0) {
	$sql = "SELECT  tbl_faq.situacao      ,
					tbl_faq_causa.causa   ,
					tbl_faq_causa.faq   ,
					tbl_produto.referencia,
					tbl_produto.descricao 
			FROM    tbl_faq_causa
			JOIN    tbl_faq USING (faq)
			JOIN    tbl_produto USING (produto)
			WHERE   tbl_faq.produto = tbl_produto.produto
			AND     tbl_produto.fabrica_i = $login_fabrica
			AND     tbl_faq_causa.faq_causa =$faq_causa;";
	$res = @pg_exec($con,$sql);

	if(pg_numrows ($res) > 0){
		$referencia     = trim(pg_result($res,0,referencia));
		$descricao      = trim(pg_result($res,0,descricao));
		$situacao       = trim(pg_result($res,0,situacao));
		$causa          = trim(pg_result($res,0,causa));
		$faq            = trim(pg_result($res,0,faq));
	}
}

$visual_black = "manutencao-admin";

$title = "Cadastro da Solução";
$cabecalho = "Cadastro da Solução";
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
<?	} 
?> 
<p>

<form name="frm_solucao" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="faq_solucao" value="<? echo $faq_solucao ?>">
<input type="hidden" name="faq" value="<? echo $faq ?>">
<input type="hidden" name="faq_causa" value="<? echo $faq_causa ?>">

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7; font-size:10px;' align='center' width='700' border='0'>
			<tr  bgcolor="#596D9B" >
				<th align='left' colspan='4'><font size='2' color='#ffffff'>Cadastro de FAQ</font></th>
			</tr>
			<tr>
				<th>REFERÊNCIA</th>
				<td><? echo $referencia ?></td>
				<th>DESCRIÇÃO</th>

				<td><? echo $descricao ?></td>
			</tr>
			<tr>
				<th>SITUAÇÃO</th>
				<td colspan='3'><?echo $situacao ?></td>
			</tr>
			<tr>
				<th>CAUSA</th>
				<td colspan='3'><? echo $causa ?></td>
			</tr>

			<tr>
				<th>NOVA SOLUÇÃO</th>
				<td colspan='3'>
				<TEXTAREA rows='2' cols='60' name="solucao" value="" class='frm'><? echo $solucao ?></TEXTAREA>
				</td>
			</tr>
		</table>
		</td>
	</tr>
</table>

<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_solucao.btn_acao.value == '' ) { document.frm_solucao.btn_acao.value='gravar' ; document.frm_solucao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<img src="imagens_admin/btn_limpar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_solucao.btn_acao.value == '' ) { document.frm_solucao.btn_acao.value='reset' ; window.location='<? echo "faq_solucao.php?faq_causa=$faq_causa" ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
<img src="imagens/btn_voltar.gif" style="cursor: pointer;" onclick="javascript: window.location='<? echo "faq_causa.php?faq=$faq" ?>'" ALT="Retornar para Causa" border='0'>

<p>

<?

echo"<TABLE width='700' border='0' cellspacing='1' cellpadding='1' align='center' class='Relatorio'>";
echo "<thead>";
echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
echo "<TR>";
echo "<TD>SOLUCÃO</TD>";
echo "<TD COLSPAN='2' width = '30%'>AÇÕES</TD>";
echo "</TR>";
echo "</thead>";
echo "<tbody>";

$sql = "SELECT  tbl_faq_solucao.faq_causa  ,
				tbl_faq_solucao.faq_solucao,
				tbl_faq_solucao.solucao    
		FROM    tbl_faq_solucao
		JOIN    tbl_faq_causa USING (faq_causa)
		JOIN    tbl_faq USING (faq)
		JOIN    tbl_produto USING (produto)
		JOIN    tbl_linha USING (linha)
		WHERE   tbl_faq_causa.faq = tbl_faq.faq
		AND     tbl_faq.produto = tbl_produto.produto
		AND     tbl_produto.linha = tbl_linha.linha
		AND     tbl_linha.fabrica = $login_fabrica
		AND     tbl_faq_solucao.faq_causa = $faq_causa";


$sql = "SELECT	tbl_faq_solucao.solucao,
				tbl_faq_solucao.faq_solucao,
				tbl_faq_solucao.faq_causa
		from tbl_faq
		JOIN tbl_produto using(produto)
		JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
		join tbl_faq_causa using(faq)
		join tbl_faq_solucao using(faq_causa)
		where tbl_linha.fabrica = $login_fabrica
		and tbl_faq_causa.faq_causa = $faq_causa
		";

$res = @pg_exec ($con,$sql);
//echo $sql;
for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
	
	echo "<tr>";
	
	echo "<td align='left'>";
	echo "<a href='$PHP_SELF?faq_solucao=" . pg_result ($res,$i,faq_solucao) . "'>";
	echo pg_result ($res,$i,solucao);
	echo "</a>";
	echo "</td>";

	echo "<td>";
	echo "<a href='faq_situacao.php'><img src='imagens/btn_lancarnovasituacao.gif'>";
	echo "</a>";
	echo "</td>";

	echo "<td>";
	echo "<A HREF=\"javascript: if (confirm ('Deseja excluir a solução " . pg_result ($res,$i,faq_solucao) . " ?') == true) { window.location='$PHP_SELF?excluir=" . pg_result ($res,$i,faq_solucao) . "&faq_causa=$faq_causa' }\"><img src='imagens/btn_excluir.gif'></A>";
	echo "</td>";

	echo "</tr>";
	echo "</tbody>";

}
echo"</TABLE>";

?>
<? include "rodape.php"; ?>
