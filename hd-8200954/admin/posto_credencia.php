<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

# ---------- se botao btnacao foi setado
$btn_acao = $_POST ['btn_acao'];


# ---------- credenciar posto
if ($btn_acao == "credenciar") {

	$cnpj = $_POST['cnpj'];
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("-","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace (" ","",$cnpj);

	// verifica se posto está cadastrado
	$sql = "SELECT posto FROM tbl_posto WHERE cnpj = '$cnpj'";
	$res = pg_exec ($con,$sql);
	$numRows	= pg_numrows($res);

	if($numRows > 0){
		// se está cadastrado, mostra tela com dados do posto
		$posto = pg_result($res,0,0);
		header("Location: posto_cadastro2.php?codposto=$posto&credencia=yes");
		exit;
	}else{
		// se não está cadastrado, direciona para formulario de cadastro
		header("Location: posto_cadastro.php");
		exit;
	}

}

if (strlen($btn_acao) == 0){
	// tela de pesquisa posto para credenciar por CNPJ

$visual_black	= "manutencao-admin";

$title			= "Credenciamente de Postos Autorizados";
$cabecalho		= "Credenciamente de Postos Autorizados";

$layout_menu	= "cadastro";

include 'cabecalho.php';

?>
<style type="text/css">

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

</style>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "	<font face='arial, verdana' color='#330000' size='-1'>";
	echo $msg_erro;
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<table width='600' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#596d9b' size='-1'>
		Para credenciar um novo posto, preencha somente seu CNPJ e clique em "Credenciar".
		<br>
		Faremos uma pesquisa para verificar se o posto já está cadastrado em nosso banco de dados.
		</font>
	</td>
</tr>
</table>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="posto" value="<? echo $posto ?>">
<input type='hidden' name='btn_acao' value=''>
<table width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td><b><?echo $erro;?></b></td>
	</tr>
</table>

<table class="border" width='350' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5">
			<img src="imagens/cab_informacoescadastrais.gif">
		</td>
	</tr>
	<tr class="menu_top">
		<td>CNPJ</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="cnpj" size="18" maxlength="14" value="<? echo $cnpj ?>"></td>
	</tr>
	<tr class="table_line">
		<td><img src="imagens/btn_credenciar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='credenciar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Credenciar posto" border='0'></td>
	</tr>
</TABLE>

</form>

<p>

<? include "rodape.php";?>

</div>

</div>

<?
}
?>