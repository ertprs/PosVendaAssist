<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($HTTP_POST_VARS["btnacao"]) > 0) {
	$btnacao = trim($HTTP_POST_VARS["btnacao"]);
}

if ($btnacao == "gravar") {
	$sql = "INSERT INTO tbl_pedido_status 
				(
				pedido,
				data  ,
				status
				)
			VALUES
				(
				$pedido          ,
				current_timestamp,
				$status          
				)";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
}

$layout_menu = "callcenter";
$title = "Relação de Status do Pedido de Peças";

include "cabecalho.php";


	if(strlen($msg_erro) > 0){
?>
<TABLE width="500" align="center">
<TR>
	<TD bgcolor="#FFE2C6"><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?
	}

$sql = "SELECT * FROM vw_pedido_status WHERE pedido = $pedido";
$res = pg_exec($con, $sql);

if(pg_numrows($res) > 0){
	// valores do campos
	$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
	$cnpj			= trim(pg_result($res,$i,cnpj));
	$nome			= trim(pg_result($res,$i,nome));
}

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}
</style>

<p>

<TABLE width=500 cellpadding="2" cellspacing="1">
<TR class='menu_top'>
	<TD align="center" width=60>Pedido</TD>
	<TD align="center" width=105>Código do Posto</TD>
	<TD align="center">CNPJ do Posto</TD>
	<TD align="center">Nome do Posto</TD>
</TR>
<TR>
	<TD align="center" bgcolor="#fdfdfd"><? echo $pedido; ?></TD>
	<TD align="center"><? echo $codigo_posto; ?></TD>
	<TD align="center"><? echo $cnpj; ?></TD>
	<TD align="left"><? echo $nome; ?></TD>
</TR>
</TABLE>

<p>

<TABLE width="500" align="center" cellpadding="2" cellspacing="1">
<TR class='menu_top'>
	<TD width="40%">Data</TD>
	<TD>Status</TD>
</TR>

<?

$sql = "SELECT * FROM vw_pedido_status WHERE pedido = $pedido ORDER BY data_status DESC";
$res = pg_exec($con, $sql);

for ($i=0; $i < pg_numrows($res); $i++){

	// valores do campos
	$data_status	= trim(pg_result($res,$i,data_status));
	$descricao		= trim(pg_result($res,$i,descricao));

	$cor = "#fdfdfd";
	if ($i % 2 == 0) $cor = '#F1F4FA';

	// monta tabela
	echo "<tr>\n";
	echo "	<td bgcolor=$cor>$data_status</td>\n";
	echo "	<td bgcolor=$cor>$descricao</td>\n";
	echo "</tr>\n";
}
?>
</TABLE>
<FORM NAME="frm_status" METHOD=POST ACTION="<? echo $PHP_SELF; ?>">
<input type='hidden' name='pedido' value='<? echo $pedido; ?>'>

	<TABLE>
	<TR>
		<TD>Selecione um novo status para o pedido:</TD>
	</TR>
	<TR>
		<TD>
			<SELECT NAME="status">
<?
$sql = "SELECT * 
		FROM tbl_status 
		WHERE fabrica = $login_fabrica";
$res = pg_exec($con, $sql);

for ($i=0; $i < pg_numrows($res); $i++){

	// valores do campos
	$status		= trim(pg_result($res,$i,status));
	$descricao	= trim(pg_result($res,$i,descricao));

	// monta tabela
	echo "				<OPTION VALUE='$status'>$descricao</OPTION>\n";
}

?>
			</SELECT>
		</TD>
	</TR>
	<TR>
		<TD>
			<input type='hidden' name='btnacao' value=''>
			<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_status.btnacao.value == '' ) { document.frm_status.btnacao.value='gravar' ; document.frm_status.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar novo status para pedido selecionado" border='0' style="cursor:pointer;">
		</TD>
	</TR>
	</TABLE>
</FORM>

<p>

<?
include "rodape.php"; 
?>