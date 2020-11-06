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
	
	/* seleciona o n�mero das OS's */
	// correta
	$sql = "SELECT	os
			FROM	tbl_os
			WHERE	fabrica = $login_fabrica
			AND		sua_os  = '$sua_os_correta';";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (@pg_numrows($res) == 0) $msg_erro = "OS '$sua_os_correta' n�o est� cadastrada.";

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
		if (pg_numrows($res) == 0) $msg_erro = "OS '$sua_os_incorreta' n�o est� cadastrada.";
	}

	if (strlen($msg_erro) == 0) {
		// verifica se existe OS produto
		$sql = "SELECT	os
				FROM	tbl_os_produto
				WHERE	os  = '$os_incorreta';";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 AND pg_numrows($res) == 0){
			$msg_erro = "N�o existem itens lan�ados para a OS '$sua_os_incorreta'";
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
		###CONCLUI OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E RECARREGA CAMPOS
		
		$sua_os_correta   = $_POST["sua_os_correta"];
		$sua_os_incorreta = $_POST["sua_os_incorreta"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title       = "Altera��o de OS - Reincid�ncias";
include 'cabecalho.php';

?>

<script>
function ConfirmaReincidencia(correta,incorreta){
	if(confirm('REINCID�NCIAS.\n\nConfirma a altera��o da OS ' + incorreta +' para a OS ' + correta +'?\n\nPara confirmar clique em \"OK\",\ncaso contr�rio, clique em \"Cancelar\".') == true){
		if(confirm('ATEN��O.\n\nEssa op��o ir� excluir a OS ' + incorreta +' definitivamente.\n\nConfirma a exclus�o da OS ' + incorreta +'?\n\nPara confirmar clique em \"OK\",\ncaso contr�rio, clique em \"Cancelar\".') == true){
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
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: ConfirmaReincidencia(document.frm_reincidencia.sua_os_correta.value,document.frm_reincidencia.sua_os_incorreta.value);" ALT="Gravar formul�rio" border='0' style='cursor:pointer;'>
	</td>
</tr>
</table>
</form>

<?
include "rodape.php";
?>