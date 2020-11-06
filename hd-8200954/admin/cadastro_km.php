<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {

if (strlen($_POST['valor']) > 0) $valor = $_POST['valor'];

	$sql = "UPDATE tbl_kilometragem SET
				valor = '$valor'
			WHERE fabrica = $login_fabrica";
	$res = pg_exec($con, $sql);
}

$title       = "Cadastro de Quilometragem";
$cabecalho   = "Cadastro de Quilometragem";
$layout_menu = "cadastro";
include 'cabecalho.php';


//recarrega dados
$sql = "SELECT valor
		FROM   tbl_kilometragem
		WHERE  fabrica = $login_fabrica";
$res = pg_exec($con, $sql);
if(pg_numrows($res)>0) $valor = pg_result($res,0,0);
?>
<script language="JavaScript">
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
</script>
<style>
.Conteudo{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	color:#fff;
	font-weight: bold;
}
</style>

<BR>
<FORM METHOD="POST"  NAME="frm_km" ACTION="<? echo $PHP_SELF; ?>">
		<table width='300' align='center' border='0' cellspacing='0' bgcolor='#ffffff'>
		<TR>
			<td align='center'  bgcolor="#330099" class="Conteudo">Valor Pago P/ Quilometagem</td>
		</TR>
		<TR>
			<TD bgcolor='#d9e2ef'><INPUT TYPE="text" NAME="valor" onBlur="checarNumero(this)" value="<? echo $valor; ?>" size="5" maxlength="5"></TD>
		</TR>
		<TR>
			<TD bgcolor='#d9e2ef'><input type='hidden' name='btn_acao' value=''>
			<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_km.btn_acao.value == '' ) { document.frm_km.btn_acao.value='gravar' ; document.frm_km.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
			</TD>
		</TR>
	</TABLE>
</FORM>
<BR>
<? include "rodape.php"; ?>

