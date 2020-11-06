<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica != 7 ){
	header("Location: menu_os.php");
	exit;
}

$msg_erro = "";

$title = "Ordem de Serviço - Lote de Agrupamento para Faturamento";
$layout_menu = "callcenter";
include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
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
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

input {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

TEXTAREA {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

</style>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE>
<TR>
	<TD><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?}?>

<form name='frm_os' action='<? echo $PHP_SELF; ?>' method="POST">

<?
if (strlen($lote) > 0){
	$sql  = "SELECT tbl_os_faturamento.os_faturamento,
					tbl_os_faturamento.obs           ,
					tbl_cliente.nome AS cliente_nome ,
					tbl_cliente.cpf  AS cliente_cpf  ,
					tbl_revenda.nome AS revenda_nome ,
					tbl_revenda.cnpj AS revenda_cnpj 
			FROM    tbl_os_faturamento
			LEFT JOIN tbl_cliente USING(cliente)
			LEFT JOIN tbl_revenda USING(revenda)
			WHERE   tbl_os_faturamento.os_faturamento = $lote";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		$os_faturamento = pg_result($res,0,os_faturamento);
		$obs            = pg_result($res,0,obs);
		$cliente_cpf    = pg_result($res,0,cliente_cpf);
		$cliente_nome   = pg_result($res,0,cliente_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
		$revenda_nome   = pg_result($res,0,revenda_nome);
	}
}
?>
<table class="border" width='700' align='center' border='0' cellpadding="3" cellspacing="3">
	<tr>
		<td colspan=3 class="menu_top">LOTE DE FATURAMENTO</td>
	</tr>
	<tr>
		<td class="menu_top">LOTE</td>
		<td class="menu_top">CNPJ/CPF</td>
		<td class="menu_top">NOME</td>
	</tr>
	<tr>
		<TD class="table_line2"><center><? echo $lote; ?></center></TD>
		<TD class="table_line2"><center><? echo $cliente_cpf; echo $revenda_cnpj; ?></center></TD>
		<TD class="table_line2"><center><? echo $cliente_nome; echo $revenda_nome; ?></TD>
	</tr>
	<tr>
		<td colspan=3 class="menu_top">OBSERVAÇÕES</td>
	</tr>
	<tr>
		<TD colspan=3 class="table_line2"><center><textarea name='obs' rows='5' cols=50><? echo $obs ?></textarea></center></TD>
	</tr>
</table>

<BR>
<?

$sql  = "SELECT to_char(tbl_os_faturamento.data_abertura, 'DD/MM/YYYY') AS data_abertura,
				tbl_cliente.nome AS cliente_nome                                       ,
				tbl_cliente.cpf  AS cliente_cpf                                        ,
				tbl_revenda.nome AS revenda_nome                                       ,
				tbl_revenda.cnpj AS revenda_cnpj                                       
		FROM    tbl_os_faturamento
		LEFT JOIN tbl_cliente USING(cliente)
		LEFT JOIN tbl_revenda USING(revenda)
		WHERE   tbl_os_faturamento.data_fechamento IS NULL
		ORDER BY tbl_os.sua_os DESC";

// tbl_os.fabrica = $login_fabrica

$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0){

?>
<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan='3' class="menu_top">&nbsp;</td>
	</tr>
	<tr>
		<td class="menu_top">OS</td>
		<td class="menu_top">DATA ABERTURA</td>
		<td class="menu_top">DATA FECHAMENTO</td>
		<td class="menu_top">PRODUTO</td>
		<td class="menu_top">SÉRIE</td>
		<td class="menu_top">&nbsp;</td>
	</tr>
<?
	for ($i=0; $i<pg_numrows($res); $i++) {
		$os              = trim(pg_result($res,$i,os));
		$sua_os          = trim(pg_result($res,$i,sua_os));
		$data_abertura   = trim(pg_result($res,$i,data_abertura));
		$data_fechamento = trim(pg_result($res,$i,data_fechamento));
		$produto         = trim(pg_result($res,$i,produto));
		$serei           = trim(pg_result($res,$i,serie));

		echo "<tr>\n";
		echo "	<TD class='table_line'>$sua_os</TD>\n";
		echo "	<TD class='table_line'>$data_abertura</TD>\n";
		echo "	<TD class='table_line'>$data_fechamento</TD>\n";
		echo "	<TD class='table_line'>$produto</TD>\n";
		echo "	<TD class='table_line'>$serie</TD>\n";
		echo "	<TD class='table_line'><input type='checkbox' name='os_$i' value='$os'></TD>\n";
		echo "</tr>\n";
	}
}
?>

</table>

</form>

<br>

<?
include 'rodape.php';
?>