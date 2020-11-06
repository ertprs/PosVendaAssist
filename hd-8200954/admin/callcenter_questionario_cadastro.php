<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["questionario"]) > 0)  $questionario = trim($_GET["questionario"]);
if (strlen($_POST["questionario"]) > 0) $questionario = trim($_POST["questionario"]);

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "deletar" and strlen($questionario) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_questionario
			WHERE  fabrica = $login_fabrica
			AND    questionario = $questionario;";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'questionario_fk') > 0) $msg_erro = "Esta questão não pode ser excluída";

	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$pergunta      = $_POST["pergunta"];
		$tipo_resposta = $_POST['tipo_resposta'];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($btnacao == "gravar") {
	$pergunta      = trim($_POST['pergunta']);
	$tipo_resposta = trim($_POST['tipo_resposta']);

	if (strlen($pergunta) > 0)
		$aux_pergunta = "'". trim($pergunta) ."'";
	else
		$msg_erro = "Favor digitar a pergunta.";

	if (strlen($tipo_resposta) > 0)
		$aux_tipo_resposta = "'". trim($tipo_resposta) ."'";
	else
		$msg_erro = "Favor selecionar o tipo de respota.";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($questionario) == 0) {
			$sql = "INSERT INTO tbl_questionario (
						pergunta     ,
						tipo_resposta,
						fabrica
					) VALUES (
						$aux_pergunta     ,
						$aux_tipo_resposta,
						$login_fabrica
					);";
		}else{
			$sql = "UPDATE  tbl_questionario SET
						pergunta      = $aux_pergunta     ,
						tipo_resposta = $aux_tipo_resposta
					WHERE  fabrica      = $login_fabrica
					AND    questionario = $questionario";
		}
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$pergunta      = $_POST["pergunta"];
		$tipo_resposta = $_POST['tipo_resposta'];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($questionario) > 0) {
	$sql = "SELECT  pergunta     ,
					tipo_resposta
			FROM    tbl_questionario
			WHERE   fabrica = $login_fabrica
			AND     questionario = $questionario";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$pergunta      = trim(pg_result($res,0,pergunta));
		$tipo_resposta = trim(pg_result($res,0,tipo_resposta));
	}
}

$layout_menu = "callcenter";
$title = "Cadastramento de Serviços Realizados";
include 'cabecalho.php';

?>

<form name="frm_questionario" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="questionario" value="<? echo $questionario ?>">

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td>Pergunta</td>
		<td>Tipo resposta</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="pergunta" size="75" maxlength="" value="<? echo $pergunta ?>"></td>
		<td>
			<input type="radio" name="tipo_resposta" value="t" <? if ($tipo_resposta == 't') echo "checked" ?>>Sim/Não
			<input type="radio" name="tipo_resposta" value="f" <? if ($tipo_resposta <> 't') echo "checked" ?>>Seleciona
		</td>
	</tr>
	<tr>
		<td colspan=2>
			<input type='hidden' name='btnacao' value=''>
			<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_questionario.btnacao.value == '' ) { document.frm_questionario.btnacao.value='gravar' ; document.frm_questionario.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style='cursor:pointer;'>
			<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_questionario.btnacao.value == '' ) { document.frm_questionario.btnacao.value='deletar' ; document.frm_questionario.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' style='cursor:pointer;'>
			<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_questionario.btnacao.value == '' ) { document.frm_questionario.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style='cursor:pointer;'>
		</td>
	</tr>
</table>

</form>

<div id="subBanner">
	<h1>Relação de Questões</h1>
</div>

<?
$sql = "SELECT  questionario,
				pergunta,
				tipo_resposta
		FROM    tbl_questionario
		WHERE   fabrica = $login_fabrica
		ORDER BY questionario";
$res0 = pg_exec ($con,$sql);

echo "<table width=600 border=0>";
echo "<tr class='table_line'>";
echo "<td><b>Para efetuar alterações, clique na descrição da questão.</b></td>";
echo "<td><b>Alterar/inserir respostas.</b></td>";
echo "</tr>";

for ($y = 0 ; $y < pg_numrows($res0) ; $y++){

	$questionario  = trim(pg_result($res0,$y,questionario));
	$pergunta      = trim(pg_result($res0,$y,pergunta));
	$tipo_resposta = trim(pg_result($res0,$y,tipo_resposta));

	echo "<tr align=\"left\" class='table_line'>\n";
	echo "<td><a href='$PHP_SELF?questionario=$questionario'>$pergunta</a></td>\n";
	echo "<td>\n";
	if ($tipo_resposta <> 't') echo "<a href='callcenter_questionario_resposta_cadastro.php?questionario=$questionario'><img src='imagens/btn_resposta.gif' border=0></a>\n";
	echo "</td>\n";
	echo "</tr>\n";

}
echo "</table>";

include "rodape.php";

?>