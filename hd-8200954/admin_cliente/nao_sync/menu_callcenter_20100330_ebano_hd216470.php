<?
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
$layout_menu = "callcenter";
$title = "Menu Call-Center";
include '../admin/cabecalho.php';

?>

<style type="text/css">

body {
	text-align: center;
}

.cabecalho {
	color: black;
	border-bottom: 2px dotted WHITE;
	font-size: 12px;
	font-weight: bold;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 12px;
	font-weight: normal;
	text-align: justify;
}

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}

</style>

<br>

	<? if($login_fabrica==6 or $login_fabrica==11 or $login_fabrica==24 or $login_fabrica==15 OR 1==1 AND $login_fabrica<>25){ ?>
	<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
	<tr>
		<td width='10'><img border="0" src="imagens/corner_se_laranja.gif"></td>
		<td class="cabecalho">CALL-CENTER <? if($login_fabrica==6) echo "NOVO"; ?></TD>
		<td width='10'><img border="0" src="imagens/corner_sd_laranja.gif"></td>
	</tr>
	</table>
	<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
	<!-- ================================================================== -->
<!--
	<? if($login_fabrica <> 3){ ?>
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/marca25.gif'></td>
		<td nowrap width='260'><a href='cadastra_callcenter.php' class='menu'>Cadastra Atendimento Call-Center</a></td>
		<td nowrap class='descricao'>Cadastro de atendimento do Call-Center
		<? if($login_fabrica<>  10 AND $login_fabrica <>  7 AND $login_fabrica <> 2 AND $login_fabrica <>  45 AND $login_fabrica <>  5 AND $login_fabrica <>  46 AND $login_fabrica < 50 AND $login_fabrica <>  43 AND $login_fabrica <>  3){ ?>
		<BR><B><font size=1>ESTA TELA SERÁ DESATIVADA EM 05/09/2008<br> E PASSAR A USAR NOVA TELA</font></B>
		<?}?>
		</td>
	</tr>
	<? } ?>
	-->
	<!-- ================================================================== -->
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/marca25.gif'></td>
			<td nowrap width='260'><a href='pre_os_cadastro_sac.php' class='menu'>Cadastra Atendimento Pré-OS
		<? if($login_fabrica == 6) echo "( NOVO )"; ?>
		</a></td>
		<td nowrap class='descricao'>Cadastro de Pré-Os para Postos Autorizados</td>
	</tr>

<?}?>

	<tr bgcolor='#D9E2EF'>
		<td colspan="3"><img src="imagens/spacer.gif" height="3"></td>
	</tr>
	</table>


<br>

<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF'  align = 'center'>
<tr>
	<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">ORDENS DE SERVIÇO</td>
	<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<table border="0" width="700px" border="0" cellpadding="0" cellspacing="0" align = 'center'>
<!-- ================================================================== -->
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='<? if ($login_fabrica == 1) echo "os_consumidor_consulta.php"; else echo "os_consulta_lite.php"; ?>' class='menu'>Consulta Ordens de Serviço</a></td>
	<td nowrap class='descricao'>Consulta OS Lançadas</td>
</tr>

<!-- ================================================================== -->

<!-- ================================================================== -->
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>

<? include "../admin/rodape.php" ?>
