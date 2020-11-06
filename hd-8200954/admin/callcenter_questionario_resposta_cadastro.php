<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["questionario_resposta"]) > 0)  $questionario_resposta = trim($_GET["questionario_resposta"]);
if (strlen($_POST["questionario_resposta"]) > 0) $questionario_resposta = trim($_POST["questionario_resposta"]);

if (strlen($_GET["questionario"]) > 0)  $questionario = trim($_GET["questionario"]);
if (strlen($_POST["questionario"]) > 0) $questionario = trim($_POST["questionario"]);

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "deletar" and strlen($questionario_resposta) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_questionario_resposta
			WHERE  questionario          = $questionario
			AND    questionario_resposta = $questionario_resposta";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'questionario_resposta_fk') > 0) $msg_erro = "Esta resposta não pode ser excluída";

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$resposta              = $_POST["resposta"];
		$questionario          = $_POST['questionario'];
		$questionario_resposta = $_POST['questionario_resposta'];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($btnacao == "gravar") {
	$resposta              = $_POST["resposta"];
	$questionario          = $_POST['questionario'];
	$questionario_resposta = $_POST['questionario_resposta'];

	if (strlen($resposta) > 0)
		$aux_resposta = "'". trim($resposta) ."'";
	else
		$msg_erro = "Favor digitar a resposta.";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($questionario_resposta) == 0) {
			$sql = "INSERT INTO tbl_questionario_resposta (
						questionario,
						resposta
					) VALUES (
						$questionario ,
						$aux_resposta
					);";
		}else{
			$sql = "UPDATE  tbl_questionario_resposta SET
						questionario = $questionario,
						resposta     = $aux_resposta
					WHERE questionario_resposta = $questionario_resposta";
		}
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?questionario=$questionario");
		exit;
	}else{
		$resposta              = $_POST["resposta"];
		$questionario          = $_POST['questionario'];
		$questionario_resposta = $_POST['questionario_resposta'];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($questionario_resposta) > 0) {
	$sql = "SELECT  tbl_questionario_resposta.questionario_resposta,
					tbl_questionario_resposta.questionario         ,
					tbl_questionario_resposta.resposta             ,
					tbl_questionario.pergunta
			FROM    tbl_questionario_resposta
			JOIN    tbl_questionario USING(questionario)
			WHERE   tbl_questionario_resposta.questionario_resposta = $questionario_resposta";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$questionario_resposta = trim(pg_result($res,0,questionario_resposta));
		$questionario          = trim(pg_result($res,0,questionario));
		$resposta              = trim(pg_result($res,0,resposta));
	}
}

if (strlen($questionario) > 0) {
	$sql = "SELECT  pergunta
			FROM    tbl_questionario
			WHERE   questionario = $questionario";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$pergunta              = trim(pg_result($res,0,pergunta));
	}
}

$layout_menu = "callcenter";
$title = "Cadastramento de Serviços Realizados";
include 'cabecalho.php';


if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?
}
//echo $msg_debug ;
?>

<form name="frm_questionario_resposta" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="questionario_resposta" value="<? echo $questionario_resposta ?>">
<input type="hidden" name="questionario" value="<? echo $questionario ?>">

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td>Pergunta</td>
	</tr>
	<tr class="table_line">
		<td align=center><? echo $pergunta ?></td>
	</tr>
	<tr class="menu_top">
		<td>Resposta</td>
	</tr>
	<tr class="table_line">
		<td align=center><input type="text" name="resposta" size="75" maxlength="" value="<? echo $resposta ?>"></td>
	</tr>
	<tr>
		<td>
			<input type='hidden' name='btnacao' value=''>
			<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_questionario_resposta.btnacao.value == '' ) { document.frm_questionario_resposta.btnacao.value='gravar' ; document.frm_questionario_resposta.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style='cursor:pointer;'>
			<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_questionario_resposta.btnacao.value == '' ) { document.frm_questionario_resposta.btnacao.value='deletar' ; document.frm_questionario_resposta.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' style='cursor:pointer;'>
			<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_questionario_resposta.btnacao.value == '' ) { document.frm_questionario_resposta.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style='cursor:pointer;'>
			<IMG SRC="imagens_admin/btn_voltar.gif" ONCLICK="javascript: if (document.frm_questionario_resposta.btnacao.value == '' ) { document.frm_questionario_resposta.btnacao.value='voltar' ; window.location='callcenter_questionario_cadastro.php' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style='cursor:pointer;'>
		</td>
	</tr>
</table>

</form>

<div id="subBanner">
	<h1>Relação de respostas da questão</h1>
</div>

<?
$sql = "SELECT  questionario_resposta,
				questionario         ,
				resposta
		FROM    tbl_questionario_resposta
		WHERE   questionario = $questionario";
$res0 = pg_exec ($con,$sql);

echo "<table width=600 border=0>";
echo "<tr class='table_line'>";
echo "<td><b>Para efetuar alterações, clique na descrição da resposta.</b></td>";
echo "</tr>";

for ($y = 0 ; $y < pg_numrows($res0) ; $y++){

	$questionario_resposta = trim(pg_result($res0,$y,questionario_resposta));
	$resposta              = trim(pg_result($res0,$y,resposta));

	echo "<tr align=\"left\" class='table_line'>\n";
	echo "<td><a href='$PHP_SELF?questionario_resposta=$questionario_resposta&questionario=$questionario'>$resposta</a></td>\n";
	echo "</tr>\n";

}
echo "</table>";

include "rodape.php";

?>