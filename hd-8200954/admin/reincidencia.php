<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$msg_erro = "";
$msg_debug = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];


if ($btn_acao == 'pesquisar'){
	if (strlen($serie_cnpj) == 0)
		$xserie_cnpj = "'f'";
	else
		$xserie_cnpj = "'$serie_cnpj'";
}


$visual_black = "manutencao-admin";

$title       = "Reincidências";
$cabecalho   = "Reincidências";
$layout_menu = "auditoria";
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

.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
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
	if($msg_erro){
?>
<table width='300px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} ?> 
<p>

<form name="frm_reincidencia" method="post" action="<? echo $PHP_SELF ?>">

<table class="border" width='400' align='center' border='0' cellpadding="1" cellspacing="0">
	<TR>
		<TD colspan="2" class="pesquisa"><div align="center"><b>Pesquisa</b></div></TD>
	</TR>

	<tr class="menu_top">
		<td align='left'>PELO Nº DE SÉRIE &nbsp; <INPUT TYPE="radio" NAME="serie_cnpj" VALUE = 'serie' <?if ($serie_cnpj == 'serie' or strlen($serie_cnpj) == 0) echo "checked";?>></td>
		<td align='left'>CNPJ + NF &nbsp; <INPUT TYPE="radio" NAME="serie_cnpj" VALUE = 'cnpj' <?if ($serie_cnpj == 'cnpj') echo "checked";?>></td>
	</tr>
	<tr class="menu_top">
		<td align='left'>PERÍODO DE REINCIDÊNCIA</td>
		<td align='left'>
			<select name='data' style='width:120px'>
			<option value=30 selected>30 dias</option>
			<option value=60>60 dias </option>;
			<option value=90>90 dias </option>;
			</select>
		</td>

	</TR>
</TABLE>

<center>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_pesquisar_400.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_reincidencia.btn_acao.value == '' ) { document.frm_reincidencia.btn_acao.value='pesquisar' ; document.frm_reincidencia.submit() } else { alert ('Aguarde submissão') }" ALT="Pesquisar" border='0'>
</center>
<br>
<?if ($serie_cnpj == 'serie'){?>
<table width='400'>
	<tr>
		<td colspan='2' CLASS='menu_top'>RELATÓRIO DE REINCIDÊNCIAS POR SÉRIE</td>
	</tr>
	<tr class="menu_top">
		<td>SÉRIE</td>
		<td>OS</td>
	</tr>
	<tr>
		<td class="table_line"><?echo $serie;?></td>
		<td class="table_line"><?echo $os;?></td>
	</tr>

</table>
<?} else if ($serie_cnpj == 'cnpj'){?>

<table width='400'>
	<tr CLASS='menu_top'>
		<td COLSPAN='3'>RELATÓRIO DE REINCIDÊNCIAS POR CNPJ + NF</td>
	</tr>
	<tr class="menu_top">
		<td>CNPJ</td>
		<td>NF</td>
		<td>OS</td>
	</tr>
	<tr>
		<td class="table_line"><?echo $cnpj;?></td>
		<td class="table_line"><?echo $nf;?></td>
		<td class="table_line"><?echo $os;?></td>
	</tr>

</table>

<?}?>
<? include "rodape.php"; ?>