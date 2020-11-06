<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["sua_os_correta"]) > 0)  $sua_os_correta  = trim($_POST["sua_os_correta"]);
if (strlen($_POST["sua_os_incorreta"]) > 0) $sua_os_incorreta = trim($_POST["sua_os_incorreta"]);

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "gravar" AND strlen($sua_os_correta) > 0 AND strlen($sua_os_incorreta) > 0) {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	/* seleciona o número das OS's */
	// correta
	$sql = "SELECT	os
			FROM	tbl_os
			WHERE	fabrica = $login_fabrica
			AND		sua_os  = '$sua_os_correta';";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (@pg_numrows($res) == 0) $msg_erro = "OS '$sua_os_correta' não está cadastrada.";

	if (strlen($msg_erro) == 0) {
		$os_correta = @pg_result($res,0,0);
		// incorreta
		$sql = "SELECT	os
				FROM	tbl_os
				WHERE	fabrica = $login_fabrica
				AND		sua_os  = '$sua_os_incorreta';";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro) == 0) $os_incorreta = @pg_result($res,0,0);
		if (pg_numrows($res) == 0) $msg_erro = "OS '$sua_os_incorreta' não está cadastrada.";
	}

	if (strlen($msg_erro) == 0) {
		// verifica se existe OS produto
		$sql = "SELECT	os
				FROM	tbl_os_produto
				WHERE	os  = '$os_incorreta';";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 AND pg_numrows($res) == 0){
			$msg_erro = "Não existem itens lançados para a OS '$sua_os_incorreta'";
		}else{
			// faz update
			$sql = "UPDATE	tbl_os_produto SET
							os = $os_correta
					WHERE	os = $os_incorreta;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
				$sql = "DELETE FROM tbl_os
						WHERE  fabrica = $login_fabrica
						AND    os = $os_incorreta;";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0){
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$sua_os_correta   = $_POST["sua_os_correta"];
		$sua_os_incorreta = $_POST["sua_os_incorreta"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title       = "Alteração de OS - Reincidências";
include 'cabecalho.php';

?>

<script>
function ConfirmaReincidencia(correta,incorreta){
	if(confirm('REINCIDÊNCIAS.\n\nConfirma a alteração da OS ' + incorreta +' para a OS ' + correta +'?\n\nPara confirmar clique em \"OK\",\ncaso contrário, clique em \"Cancelar\".') == true){
		if(confirm('ATENÇÃO.\n\nEssa opção irá excluir a OS ' + incorreta +' definitivamente.\n\nConfirma a exclusão da OS ' + incorreta +'?\n\nPara confirmar clique em \"OK\",\ncaso contrário, clique em \"Cancelar\".') == true){
			if (document.frm_reincidencia.btnacao.value == '' ) { 
				document.frm_reincidencia.btnacao.value='gravar' ; 
				document.frm_reincidencia.submit() 
			}
		}
	}
}
</script>

<p>

<? if (strlen($msg_erro) > 0) { ?>
<table cellpadding='5' cellspacing='5' border='0' width='700' align='center'>
<tr>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<br>
<? } ?>

<table cellpadding='5' cellspacing='5' border='0' align='center'>
<form name="frm_reincidencia" method="post" action="<? echo $PHP_SELF ?>">

<tr>
	<td><b>OS correta</b></td>
	<td><b>OS incorreta (excluir)</b></td>
</tr>

<tr>
	<td><input class='frm' type="text" name="sua_os_correta"  value="<? echo $sua_os_correta ?>"  size="20"></td>
	<td><input class='frm' type="text" name="sua_os_incorreta" value="<? echo $sua_os_incorreta ?>" size="20"></td>
</tr>

</table>

<br>

<table cellpadding='5' cellspacing='5' border='0' align='center'>
<tr>
	<td>
	<input type='hidden' name='btnacao' value=''>
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: ConfirmaReincidencia(document.frm_reincidencia.sua_os_correta.value,document.frm_reincidencia.sua_os_incorreta.value);" ALT="Gravar formulário" border='0' style='cursor:pointer;'>
	</td>
</tr>
</table>
</form>

<?
include "rodape.php";
?>