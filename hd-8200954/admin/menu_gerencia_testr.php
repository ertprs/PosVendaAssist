<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$title = "MENU GER�NCIA";
$layout_menu = "gerencia";

include 'cabecalho.php';

?>

<!--
<br>
<center>
<img src='../imagens/embratel_logo.gif' valign='absmiddle'>
<br>
<font color='#330066'><b>Conclu�da migra��o para EMBRATEL</b>.</font>
<br>
<font size='-1'>
A <b>Telecontrol</b> agradece sua compreens�o.
<br>Agora com a migra��o para o iDC EMBRATEL teremos
<br>um site mais veloz, robusto e confi�vel.
</font>
<p>
</center>
-->

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
	font-size: 10px;
	font-weight: normal;
	text-align: justify;
}

/*========================== MENU ===================================*/

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
<!--
<TABLE width="700px" border="0" align="center">
<TR>
	<TD>
		<?
		echo "<a href='$login_fabrica_site' target='_new'>";
		echo "<IMG SRC='/assist/logos/$login_fabrica_logo' ALT='$login_fabrica_site' border='0'>";
		echo "</a>";
		?>
	</TD>
</TR>
</TABLE>
 -->

<br />

<? if($login_fabrica == 24){ ?>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class=cabecalho>CREDENCIAMENTO DE ASSIST�NCIAS T�CNICAS</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='../credenciamento/suggar/index.php' target='blank_' class='menu'>Credenciamento de Assist�ncias T�cnicas</a></td>
	<td nowrap class='descricao'>Credenciamento e Descredenciamento de Assist�ncias T�cnicas.</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>

<? if($login_fabrica == 25){ ?>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class=cabecalho>CREDENCIAMENTO DE ASSIST�NCIAS T�CNICAS</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='../credenciamento/hbtech/index_.php' target='blank_' class='menu'>Credenciamento de Assist�ncias T�cnicas</a></td>
	<td nowrap class='descricao'>Credenciamento e Descredenciamento de Assist�ncias T�cnicas.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='credenciamento_lista.php' class='menu'>Acompanhamento do recadastramento</a></td>
	<td nowrap class='descricao'>Listagem dos postos que receberam o e-mail de recadastramento.</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>


<? if($login_fabrica == 10){ ?>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class='cabecalho'>CADASTRO DE FABRICANTES</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<?if($login_admin == 398 OR $login_admin == 435 OR $login_admin == 432){?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='fabricante_cadastro.php' target='blank_' class='menu'>Cadastro de f�bricas</a></td>
	<td nowrap class='descricao'>Cadatramento de fabricantes pela p�gina.</td>
</tr>
<?}?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='posto_credenciamento.php' class='menu'>Credenciar Autorizada</a></td>
	<td nowrap class='descricao'>Cadastramento da rede autorizada para este fabricante.</td>
</tr>
</table>
<br>
<?
}

//CROWN TELA GERA CONTRATO
if($login_fabrica == 47){ ?>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class=cabecalho>CREDENCIAMENTO DE ASSIST�NCIAS T�CNICAS</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='../credenciamento/gera_contrato_crown.php' target='blank_' class='menu'>Contrato Assist�ncias T�cnicas</a></td>
	<td nowrap class='descricao'>Contrato para Assist�ncias T�cnicas.</td>
</tr>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CONSULTAS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_parametros.php' class='menu'>Consulta Ordens de Servi�o</a></td>
	<td nowrap class='descricao'>Consulta OS Lan�adas</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='pedido_parametros.php' class='menu'>Consulta Pedidos de Pe�as</a></td>
	<td class='descricao'>Consulta pedidos efetuados por postos autorizados.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='acompanhamento_os_revenda_parametros.php' class='menu'>Acompanhamento de OS Revenda</a></td>
	<td nowrap class='descricao'>Consulta OS de Revenda Lan�adas e Finalizadas</td>
</tr>
<? if ($login_fabrica == 43) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='status_os_posto.php' class='menu'>Acompanhamento de OS em aberto</a></td>
	<td nowrap class='descricao'>Consulta status das Ordens de Servi�o em aberto</td>
</tr>
<?}?>

<? if ($login_fabrica == 6) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_enviadas_tectoy.php' class='menu'>OS com pe�as enviadas a f�brica</a></td>
	<td class='descricao'>Consulta OSs que o posto enviou pe�as para a f�brica. Autoriza ou n�o o pagamento de metade da m�o-de-obra.</td>
</tr>
<? } ?>
<? if ($login_fabrica == 3) { // HD 55242?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_consulta_agrupada.php' class='menu'>Consulta Ordem de Servi�o Agrupada</a></td>
	<td class='descricao'>Consulta OS agrupada.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 1 and $login_admin == 236) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_consulta_lite_etiqueta.php' class='menu'>Consulta OSs e gera etiquetas</a></td>
	<td nowrap class='descricao'>Transfer�ncia de OS entre postos</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 14) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_transferencia.php' class='menu'>Transfer�ncia de OS</a></td>
	<td nowrap class='descricao'>Transfer�ncia de OS entre postos</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 7) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_transferencia_filizola.php' class='menu'>Transfer�ncia de OS</a></td>
	<td nowrap class='descricao'>Transfer�ncia de OS entre postos</td>
</tr>
<? } ?>
<? if ($login_fabrica == 51) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='estoque_consulta.php' class='menu'>Consulta de estoque</a></td>
	<td class='descricao'>Consulta de estoque da Telecontrol.</td>
</tr>
<? } ?>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<br>
<?
if($login_admin==396){
	echo "<table width='700'>";
	echo "<tr bgcolor='#FFFFFF'>";
	echo "<td align='center'><img src='imagens/icone_r1.png' width='50'></td>";
	echo "<td align='left' width='300'><a href='bi/fcr_os.php' class='menu'>BI - Field Call Rate - Produto</a><font size='-2'>Percentual de quebra de produtos.<br><i>Considera apenas ordem de servi�o fechada, apresentando custos</i><br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "<td align='center'><img src='imagens/icone_r5.png' width='50'></td>";
	echo "<td align='left' width='300'><a href='bi/fcr_pecas.php' class='menu'>BI - Field Call Rate - Pe�as</a><font size='-2'>Percentual de quebra de pe�as.<br><i>Considerando apenas ordem de servi�o fechada</i><br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF'>";
	echo "<td align='center'><img src='imagens/icone_r6.png' width='50'></td>";
	echo "<td align='left' ><a href='bi/fcr_posto.php' class='menu'>BI - Servi�o Autorizado</a><font size='-2'>Estat�stica de performance de consertos.<br><i>Considerando apenas ordem de servi�o fechada, tempo de conserto, os sem pe�a</i><br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "<td align='center'><img src='imagens/icone_r2.png' width='50'></td>";
	echo "<td align='left' ><a href='bi/postos_usando' class='menu'>BI - Postos Usando</a><font size='-2'>Relat�rio de Postos por Linha apresentado OS e pe�as.<br><i>Considerando apenas ordem de servi�o fechada.</i><br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E2EF'>
	<td colspan='4'><img src='imagens/spacer.gif' height='3'></td>
</tr>";
	echo"</table><br>";
}
echo "<table border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr height='18'>";
 echo "<td width='18' bgcolor='#AAAAAA'>&nbsp;</td>";
echo "<td align='left'><font size='1'><b>&nbsp; Relat�rio Desativado, caso necessite das informa��es favor entrar em contato com o suporte</b></font></td>";
echo "</tr>";
echo "<tr height='3'><td colspan='2'></td></tr>";
echo "</table>";
?>


<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>RELAT�RIOS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ==================================================================

<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='bi/fcr_mes.php' class='menu'>Field Call-Rate Optimizado</a></td>
	<td nowrap class='descricao'>Este relat�rio considera o m�s inteiro de OS pela data da digita��o. (Em testes)</td>
</tr>
-->
<? if ($login_fabrica == 3) { ?>
<tr bgcolor='#AAAAAA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='#relatorio_lancamentos.php' class='menu'>Lan�amentos</a></td>
	<td nowrap class='descricao'>Postos que est�o lan�ando ordens de servi�o no site.</td>
</tr>
<? } ?>
<? if (in_array($login_fabrica,array(66,14,15,43))){//HD 44656 ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto.php' class='menu'>Field Call-Rate - Produtos </a></td>
	<td class='descricao'>
	Percentual de quebra de produtos.<br><i>Considera apenas ordem de servi�o fechada, apresentando custos</i></td>
</tr>
<? } ?>
<!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='bi/fcr_os.php' class='menu'>BI -Field Call-Rate - Produtos </a></td>
	<td class='descricao'>
<!--<? if ($login_fabrica==24) { ?>
Relat�rio de defeitos por linha.
<? } ?>
Este relat�rio considera a data de gera��o do extrato aprovado.-->Percentual de quebra de produtos.<br><i>Considera apenas ordem de servi�o fechada, apresentando custos</i><br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>
</tr>
<!-- ================================================================== -->
<? #HD 118202 
if($login_fabrica==5){
?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='bi/fcr_os_detalhado.php' class='menu'>BI -Field Call-Rate - Detalhado </a></td>
	<td class='descricao'>
<!--<? if ($login_fabrica==24) { ?>
Relat�rio de defeitos por linha.
<? } ?>
Este relat�rio considera a data de gera��o do extrato aprovado.-->Detalhamento do Field Call Rate Produtos para Auditoria.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>
</tr>
<? #HD 179811?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='bi/fcr_os_detalhado_peca.php' class='menu'>BI -Field Call-Rate - Defeitos </a></td>
	<td class='descricao'>Detalhamento do Field Call Rate Produtos e pe�as com defeito, para Auditoria.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>
</tr>
<!-- ================================================================== -->
<? }?>
<? if ($login_fabrica == 3 OR $login_fabrica==24) { ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto2.php' class='menu'>Field Call-Rate - Produtos 2</a></td>
	<td class='descricao'>
<!--<? if ($login_fabrica==24) { ?>
Relat�rio de defeitos por produtos.
<? } ?>-->
Relat�rio do percentual de defeitos das pe�as por produto.</td>
</tr>
<!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<?if($login_fabrica == 3){?>
		<td><a href='relatorio_field_call_rate_produto3_britania.php' class='menu'>Field Call-Rate - Produtos 3</a></td>
	<?}else{?>
		<td><a href='relatorio_field_call_rate_produto3.php' class='menu'>Field Call-Rate - Produtos 3</a></td>
	<?}?>
	<td class='descricao'>Considera OS lan�adas no sistema filtrando pela data da digita��o ou finaliza��o. </td>
</tr>
<? } ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_lista_basica.php' class='menu'>Field Call-Rate - Produtos Lista B�sica</a></td>
	<td class='descricao'>Relat�rio de quebras de pe�as da lista b�sica do produto</td>
</tr>
<? if (in_array($login_fabrica,array(66,14))) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_posto.php' class='menu'>Field Call-Rate - Postos</a></td>
	<td class='descricao'>Relat�rio de ocorr�ncia de OS por familia por postos.</td>
</tr>
<?} ?>
<!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='bi/fcr_pecas.php' class='menu'>BI Field Call-Rate - Pe�as</a></td>
	<td class='descricao'>Percentual de quebra de pe�as.<br><i>Considera apenas ordem de servi�o fechada, apresentando custos</i><br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i></td>
</tr>
<!-- ================================================================== -->
<? if (in_array($login_fabrica,array(66,14))){ ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_defeito_constatado.php' class='menu'>Field Call-Rate - Defeitos Constatados</a></td>
	<td class='descricao'>Relat�rio de ocorr�ncia de OS por defeitos constatados.</td>
</tr>
<?} ?>
<!-- ================================================================== -->
<? if($login_fabrica==3){ ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_defeitos.php' class='menu'>Relat�rio de defeitos</a></td>
	<td class='descricao'>Relat�rio de defeitos de produtos e solu��es a partir da data de digita��o</td>
</tr><!-- ================================================================== -->
<? } ?>
<? if($login_fabrica==15){ ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_engenharia_serie.php' class='menu'>Relat�rio de defeitos por N� s�rie</a></td>
	<td class='descricao'>Relat�rio de defeitos de produtos e solu��es a partir do n�mero de s�rie</td>
</tr><!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_serie_reoperado.php' class='menu'>Relat�rio N� s�rie Reoperado</a></td>
	<td class='descricao'>Relat�rio de n�mero de s�ries reoperados.</td>
</tr><!-- ================================================================== -->
<? } ?>
<? if($login_fabrica==24){ ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_defeito_serie_fabricacao.php' class='menu'>Field Call-Rate - Produtos N�mero de S�rie</a></td>
	<td class='descricao'>Relat�rio de quebra dos produtos pela data de fabrica��o.</td>
</tr><!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_grupo.php' class='menu'>Field Call-Rate - Produtos N�mero de S�rie 2</a></td>
	<td class='descricao'>Relat�rio de quebra dos produtos X n�mero de s�rie.</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_pecas.php
' class='menu'>Field Call-Rate - M�o-de-obra Produtos X Pe�as</a></td>
	<td class='descricao'>Relat�rio m�o-de-obra por produto e troca de pe�a espec�ficos.</td>
</tr><!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_troca_pecas.php' class='menu'>Relat�rio Troca de Pe�a</a></td>
	<td class='descricao'>Relat�rio de pe�as trocadas pelo posto autorizado, pe�as trocadas em garantia ou paga pelos clientes</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_os_sem_troca_peca.php' class='menu'>Relat�rio de OS sem troca de Pe�a</a></td>
	<td class='descricao'>Relat�rio em ordem de posto autorizado com maior �ndice de Ordens de Servi�os sem troca de pe�a.</td>
</tr>
<? } ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<?if ($login_fabrica <> 81) {?> 
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_os_peca_sem_pedido.php' class='menu'>Relat�rio de OS de Pe�a sem Pedido</a></td>
	<td class='descricao'>Relat�rioi em ordem de posto autorizado com maior �ndice de Ordens de Servi�os de pe�a sem pedido.</td>
	<?}?>
</tr>
	<? if ($login_fabrica <> 14) {?>
		<tr bgcolor='#FAFAFA'>
		<td><img src='imagens/rel25.gif'></td>
		<td><a href='relatorio_quantidade_os.php' class='menu'>Relat�rio de Quantidade de OS's Aprovadas por LINHA</a></td>
		<td class='descricao'>Relat�rio que mostra a quantidade de OS aprovadas por postos em determinadas linhas nos �ltimos 3 meses.</td>
	<?}?>
</tr>
<!-- ================================================================== -->
<? if($login_fabrica<>14){ ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_devolucao_obrigatoria.php' class='menu'>Devolu��o Obrigat�ria</a></td>
	<td class='descricao'>Pe�as que devem ser devolvidas para a F�brica constando em Ordens de Servi�os.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? //liberado para Tectoy HD 311406
	if ($login_fabrica==6) {?>
	<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_devolucao_obrigatoria_tectoy.php' class='menu'>Total de Pe�as Devolu��o Obrigat�ria</a></td>
		<td class='descricao'>Total de pe�as que devem ser devolvidas para a F�brica.</td>
	</tr>
<? } ?>

<!-- ================================================================== -->
<? //liberado para Lenoxx hd 8231
	if ($login_fabrica==11) {?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_percentual_defeitos.php' class='menu'>Percentual de Defeitos</a></td>
		<td class='descricao'>Relat�rio por per�odo de percentual dos defeitos de produtos.</td>
	</tr>
<? } else { ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#F0F0F0'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_percentual_defeitos.php' class='menu'>Percentual de Defeitos</a></td>
		<td class='descricao'>Relat�rio por per�odo de percentual dos defeitos de produtos.</td>
	</tr>
<? } ?>
<?	if ($login_fabrica==52) {?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_defeito_constatado_os_anual.php' class='menu'>Relat�rio Anual de OS por Defeitos Constatados</a></td>
		<td class='descricao'>Relat�rio anual detalhando por fam�lia, grupo de defeito e defeito X mensal e total anual a quantidade de OS, bem como valores das mesmas</td>
	</tr>
<? } ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_tempo_conserto_mes.php' class='menu'>Perman�ncia em conserto no m�s</a></td>
		<td class='descricao'>Relat�rio que mostra o tempo (dias) de perman�ncia do produto na assist�ncia t�cnica no m�s.</td>
	</tr>
<!-- ================================================================== -->
<? //liberado para Lenoxx hd 8231
   //liberado para Bosch hd 13373
   //liberado para Ibratele hd 138104
   //liberado para Esmaltec hd 149835
   //liberado para Nova Computadores hd 203875
	if ($login_fabrica==11 or $login_fabrica==20 or $login_fabrica==8 or $login_fabrica==30 or $login_fabrica==43) {?>
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_tempo_conserto.php' class='menu'>Perman�ncia em conserto</a></td>
		<td class='descricao'>Relat�rio que mostra tempo m�dio (dias) de perman�ncia do produto na assist�ncia t�cnica.</td>
	</tr>
<? } else { ?>
		<? if ($login_fabrica <> 14) {?>
			<tr bgcolor='#AAAAAA'>
			<td width='25'><img src='imagens/rel25.gif'></td>
			<td nowrap width='260'><a href='#relatorio_tempo_conserto.php' class='menu'>Perman�ncia em conserto</a></td>
			<td class='descricao'>Relat�rio que mostra tempo m�dio (dias) de perman�ncia do produto na assist�ncia t�cnica.</td>
			</tr>
		<?}?>
<? } ?>

<!-- ================================================================== -->

<?	if ($login_fabrica==30) {?>
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_defeitos_esmaltec.php' class='menu'>Relat�rio Defeitos OS por Atendimento</a></td>
		<td class='descricao'>Relat�rio de Defeitos OS x Tipo de Atendimento.</td>
	</tr>
	<?}?>

<!-- ================================================================== -->

<? if (in_array($login_fabrica,array(66,1,2,3,7))){ ?>
<tr bgcolor='#AAAAAA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='#relatorio_prazo_atendimento_periodo.php' class='menu'>Per�odo de atendimento da OS</a></td>
	<td class='descricao'>Relat�rio de acompanhamento do atendimento por per�odo de OS.</td>
</tr>
<? } ?>
<? //liberado para Ibratele hd 138104
if($login_fabrica==8){ ?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_prazo_atendimento_periodo.php' class='menu'>Per�odo de atendimento da OS</a></td>
	<td class='descricao'>Relat�rio de acompanhamento do atendimento por per�odo de OS.</td>
</tr>
<? } ?>
<? if ($login_fabrica==6){?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_qualidade.php' class='menu'>Per�odo de atendimento da OS</a></td>
	<td class='descricao'>Relat�rio de acompanhamento do atendimento por per�odo de OS.</td>
</tr>
<?}?>
<!-- ================================================================== -->
<? if($login_fabrica == 3) {?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_perguntas_britania.php' class='menu'>Relat�rio DVD Fama e Game</a></td>
	<td class='descricao'>Relat�rio que mostra a quantidade de P. A. participaram do question�rio.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica<>24){ ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='produtos_mais_demandados.php' class='menu'>Produtos mais demandados</a></td>
	<td class='descricao'>Relat�rio dos produtos mais demandados em Ordens de Servi�os nos �ltimos meses.</td>
</tr>
<? } ?>
<!-- ================================================================== -->

<? if (in_array($login_fabrica,array(66,14,5,19,43))) {?>
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='defeito_os_parametros.php' class='menu'>Relat�rio de Ordens de Servi�o</a></td>
	<td class='descricao'>Relat�rio de Ordens de Servi�o lan�adas no sistema.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica == 1) {?>
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='auditoria_os_fechamento_blackedecker.php' class='menu'>Auditoria de pe�as trocadas em garantia</a></td>
	<td class='descricao'>Faz pesquisas nas Ordens de Servi�os previamente aprovadas em extrato.</td>
</tr>
<? } if($login_fabrica==20){?>
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_troca_os.php' class='menu'>Relat�rio de Troca de OS</a></td>
	<td class='descricao'>Verifica as OS de troca do PA.</td>
</tr>
<!-- ================================================================== -->
<?} if($login_fabrica ==2 OR $login_fabrica ==3 OR $login_fabrica ==11 OR $login_fabrica ==24) {?>
<? //liberado para Lenoxx hd 8231
	if ($login_fabrica==11) {?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='pendencia_posto.php' class='menu'>Pend�ncias do posto</a></td>
		<td class='descricao'>Relat�rio de pe�as pendentes dos postos.</td>
	</tr>
	<?} else {?>
		<? if ($login_fabrica == 14) {?>
			<tr bgcolor='#FAFAFA'>
		<?}else{?>
			<tr bgcolor='#FOFOFO'>
		<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='pendencia_posto.php' class='menu'>Pend�ncias do posto</a></td>
		<td class='descricao'>Relat�rio de pe�as pendentes dos postos.</td>
	</tr>
	<? } ?>
<? } ?>

<?if ($login_fabrica == 50) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_defeito_troca.php' class='menu'>Relat�rio de Troca de Pe�as</a></td>
		<td class='descricao'>Relat�rio de pe�as trocas os defeitos apresentados, listado por produtos.</td>
	</tr>
<?}?>

<?if ($login_fabrica == 2 AND $login_admin==989898) {?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='extrato_posto_devolucao_controle.php' class='menu'>Pend�ncias do posto - NF</a></td>
	<td class='descricao'>Controle de Notas Fiscais de Devolu��o e Pe�as</td>
</tr>
<? } ?>
<?if (in_array($login_fabrica,array(66,14,24,2,11))) {?>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='os_relatorio.php' class='menu'>Status da Ordem de Servi�o</a></td>
	<td class='descricao'>Status das ordens de servi�os</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 5) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_serie.php' class='menu'>Relat�rio de N� de S�rie</a></td>
		<td class='descricao'>Relat�rio de ocorr�ncia de produtos por n� de s�rie.</td>
	</tr>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_serie_ano.php' class='menu'>Relat�rio de N� de S�rie Anual</a></td>
		<td class='descricao'>Relat�rio de ocorr�ncia de produtos por n� de s�rie no per�odo de 12 meses.</td>
	</tr>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_producao_serie.php' class='menu'>Relat�rio de Produ��o</a></td>
		<td class='descricao'>Relat�rio de produ��o.</td>
	</tr>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_producao_nova_serie.php' class='menu'>Relat�rio de Produ��o S�rie Nova</a></td>
		<td class='descricao'>Relat�rio de produ��o.</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 24) { ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto.php' class='menu'>Relat�rio Troca de Produto</a></td>
		<td class='descricao'>Relat�rio de produto trocado na OS.</td>
	</tr>
	<? if ($login_fabrica == 14 || $login_fabrica == 72) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relat�rio Troca de Produto Total</a></td>
		<td class='descricao'>Relat�rio de produto trocado e arquivo .xls</td>
	</tr>
<? } ?>
<? if ($login_fabrica == 81 || $login_fabrica == 66 || $login_fabrica == 72) { ?>
	<tr>
                <td width='25'><img src='imagens/rel25.gif'></td>
                <td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relat�rio Troca de Produto Total</a></td>
                <td class='descricao'>Relat�rio de produto trocado e arquivo .xls</td>
        </tr>
<? } ?>
<? if ($login_fabrica == 3) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pecas_faturadas.php' class='menu'>Relat�rio de Pe�as Faturadas</a></td>
		<td class='descricao'>Relat�rio de pe�as faturadas.</td>
	</tr>
	<!-- ================================================================== -->
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_field_call_rate_produto_serie.php' class='menu'>Relat�rio OS com N� de S�rie e Posto</a></td>
		<td class='descricao'>Relat�rio Ordens de Servi�os lan�adas no sistema por produto e por posto, e com n�mero de s�rie.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto.php' class='menu'>Relat�rio Troca de Produto</a></td>
		<td class='descricao'>Relat�rio de produto trocado na OS.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relat�rio Troca de Produto Total</a></td>
		<td class='descricao'>Relat�rio de produto trocado e arquivo .xls</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_linha.php' class='menu'>Relat�rio de OS digitadas por linha</a></td>
		<td class='descricao'>Relat�rio de OS digitadas por linha.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pecas_mes.php' class='menu'>Relat�rio de OS e Pecas digitadas</a></td>
		<td class='descricao'>Relat�rio contendo a qtde de OS e Pe�as digitadas.</td>
	</tr>
	<tr bgcolor="#AAAAAA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='#relatorio_garantia_faturado.php' class='menu'>Pe�as faturadas e garantia dos �ltimos quatro meses</a></td>
		<td class='descricao'>Quantidade de pe�as enviadas em garantia, comparadas com as pe�as faturadas, totalizados por linha.</td>
	</tr>
	<tr bgcolor="#AAAAAA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='#relatorio_diario.php' class='menu'>Relat�rio Di�rio</a></td>
		<td class='descricao'>Resumo mensal do Relat�rio Di�rio enviado por email.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_qtde_os.php' class='menu'>Relat�rio Qtde OS e Pe�as Anual</a></td>
		<td class='descricao'>Relat�rio Anual de quantidade de OS's e Pe�as por Data Digita��o e Finaliza��o.</td>
	</tr> 
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_qtde_os_fabrica.php' class='menu'>Relat�rio de OS COM PE�AS e SEM PE�AS Anual</a></td>
		<td class='descricao'>Relat�rio Anual de quantidade de OS's com pe�as e sem pe�as por Data Digita��o e Finaliza��o.</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica == 8) { ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_por_posto.php' class='menu'>Produtos por posto</a></td>
		<td class='descricao'>Relat�rio de produtos lan�ados em OS por posto em determinado per�odo.</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica == 1) { ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='rel_visao_mix_total.php' class='menu'> Vis�o geral por produto </a></td>
		<td class='descricao'>Relat�rio geral por produto.</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='custo_por_os.php' class='menu'>Custo por OS</a></td>
	<td class='descricao'>Calcula o custo m�dio de cada posto para realizar os consertos em garantia.</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_quebra_familia.php' class='menu'>Relat�rio de Quebra por Fam�lia</a></td>
	<td class='descricao'>Este relat�rio cont�m a quantidade de quebra durante os ultimos 12 meses levando em conta o fechamento do extrato de cada m�s.</td>
</tr>
<? if($login_fabrica==15){ ?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_quebra_linha.php' class='menu'>Relat�rio de Quebra por Linha</a></td>
		<td class='descricao'>Este relat�rio cont�m a quantidade de quebra durante os ultimos 12 meses levando em conta o fechamento do extrato de cada m�s.</td>
	</tr>
<? } ?>

<!-- ================================================================== -->
<? if (in_array($login_fabrica,array(66,14))) {?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_defeito_constatado_os.php' class='menu'>Relat�rio de Defeitos Constatados por OS</a></td>
		<td class='descricao'>Relat�rio de Defeitos Constatados por Ordem de Servi�o.</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
<?if (in_array($login_fabrica,array(66,14))) {?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_field_call_rate_os_sem_peca_intelbras.php' class='menu'>Relat�rio de OS sem pe�a</a></td>
	<td class='descricao'>Relat�rio de Ordem de Servi�o sem pe�as e seus respectivos defeitos reclamados, defeitos constatados e solu��o.</td>
</tr>
<tr bgcolor='#efefef'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_reincidencia.php' class='menu'>Relat�rio de OS Reincidente</a></td>
	<td class='descricao'>Relat�rio de Ordem de Servi�o reincidentes X posto autorizado.</td>
</tr>



<!-- ================================================================== -->


<?
}
?>

<? if ($login_fabrica == 50) {?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_auditoria_os.php' class='menu'>Relat�rio de OS Auditadas</a></td>
	<td class='descricao'>Relat�rio de Ordens de Servi�o auditadas por: N�mero de s�rie; Com mais de 3 pe�as; Reincid�ncias; E Ordens de Servi�os que n�o passaram por nenhuma auditoria.
	</td>
</tr>
<?
}
?>


<?
if ($login_fabrica <> 14) {?>
<tr bgcolor='#efefef'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_field_call_rate_os_sem_peca.php' class='menu'>Relat�rio de OS sem pe�a</a></td>
	<td class='descricao'>Relat�rio de Ordem de Servi�o sem pe�as e seus respectivos defeitos reclamados, defeitos constatados e solu��o.</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='custo_os_nac_imp.php' class='menu'>Custo Nacionais "x" Importados</a></td>
	<td class='descricao'>An�lise dos custos das Ordens de Servi�os de produtos nacionais <i>versus</i> produtos importados.</td>
</tr>
<?}?>
<!-- ================================================================== -->

	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='auditoria_os_sem_peca.php' class='menu'>OS's abertas e sem Lan�amento de Pe�as</a></td>
	<td class='descricao'>Relat�rio que consta os postos e a quantidade de OS's que est�o abertas e sem lan�amento de pe�as</td>
</tr>
<?if($login_fabrica == 19){?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_aberta_sac.php' class='menu'>Relat�rio de OS aberta pelo Sac</a></td>
	<td class='descricao'>Relat�rio de OS's abertas pelo Sac.</td>
</tr>
<? } ?>
<?if($login_fabrica == 11){?>
	<tr bgcolor='#FaFaFa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_credenciamento.php' class='menu'>Credenciamento de Postos por M�s</a></td>
		<td class='descricao'>Mostra os postos credenciados por m�s.</td>
	</tr>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_atendida_os_aberta.php' class='menu'>OSs em aberto a mais de 15 dias que j� foram atendidas</a></td>
		<td class='descricao'>Mostra OSs que tiveram suas pe�as faturadas pelo fabricante a mais de 15 dias e ainda n�o foram finalizadas pelo posto autorizado.</td>
	</tr>
	<tr bgcolor='#FaFaFa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_posto_produto_atendido.php' class='menu'>Produtos consertados pelo posto</a></td>
		<td class='descricao'>Relat�rio de produtos consertados por m�s pelo posto autorizado.</td>
	</tr>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_aberta_fechada.php' class='menu'>Relat�rio de OS's digitadas</a></td>
		<td class='descricao'>Relat�rio das OS's digitadas por per�odo</td>
	</tr>

	<?	//hd 16584
		if ($login_fabrica == 11) {?>
		<tr bgcolor='#F0F0F0'>
			<td width='25'><img src='imagens/rel25.gif'></td>
			<td nowrap width='260'><a href='relatorio_produto_os_finalizada.php' class='menu'>Relat�rio OSs finalizadas por produto</a></td>
			<td class='descricao'>Mostra a quantidade de OSs finalizadas por modelo de produto.</td>
		</tr>
	<?}?>
<?}?>
<?if($login_fabrica == 3){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_auditoria_previa.php' class='menu'>Relat�rio de OSs auditadas</a></td>
	<td class='descricao'>Relat�rio de OSs que sofreram auditoria pr�via.</td>
</tr>
<?}?>
<?if($login_fabrica == 20){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='produto_custo_tempo.php' class='menu'>Relat�rio de Custo Tempo Cadastrado</a></td>
	<td class='descricao'>Relat�rio que consta o custo tempo cadastrado separados por fam�lia.</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='peca_informacoes_pais.php' class='menu'>Relat�rio de pe�a e pre�o por pa�s</a></td>
	<td class='descricao'>Relat�rio que consta as pe�as cadastradas por pa�s.</td>
</tr>
<tr bgcolor='#FFFFFF'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_cfa.php' class='menu'>Relat�rio de Garantia dividido por CFA's</a></td>
	<td class='descricao'>Relat�rio de gastos por Fam�lia e Origem de fabrica��o.</td>
</tr>
<?}?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_posto_peca.php' class='menu'>Relat�rio de Pe�as Por Posto</a></td>
	<td class='descricao'>Relat�rio de acordo com a data em que a OS foi finalizada.</td>
</tr>
<?if($login_fabrica == 3){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_preco_produto_acabado.php' class='menu'>Relat�rio de Pre�os de Aparelhos</a></td>
	<td class='descricao'>Relat�rio de pre�os de produto acabado.</td>
</tr>
<?}?>

<? if($login_fabrica==7){?>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_peca_garantia.php' class='menu'>Relat�rio de Pe�as em Garantia</a></td>
	<td class='descricao'>Relat�rio de pe�as com a classifica��o de OS garantia.</td>
</tr>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_sla.php' class='menu'>Relat�rio SLA</a></td>

	<td class='descricao'>Relat�rio SLA</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_back_log.php' class='menu'>Relat�rio Back-Log</a></td>
	<td class='descricao'>Relat�rio Back-Log</td>
</tr>


<? } ?>

<?if($login_fabrica == 2 or $login_fabrica ==15){ // HD 38831 58539?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_comunicado.php' class='menu'>Relat�rio de comunicado lido</a></td>
		<td class='descricao'>Relat�rio dos postos que confirmaram a leitura de comunicado.</td>
	</tr>
<? } ?>
<?if($login_fabrica == 2 ){ // HD 133069?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pendencia_codigo_componente.php' class='menu'>Relat�rio de pend�ncias por C�digo</a></td>
		<td class='descricao'>Relat�rio de pend�ncias por c�digo de componente com filtro de posto opcional.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 51){?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_pendente_gama.php' class='menu'>Relat�rio de Pe�as Pendentes</a></td>
		<td class='descricao'>Relat�rio de pe�as pendentes nas ordens de servi�os.</td>
	</tr>
<? } else { ?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_pendente.php' class='menu'>Relat�rio de Pe�as Pendentes</a></td>
		<td class='descricao'>Relat�rio de pe�as pendentes nas ordens de servi�os.</td>
	</tr>
<? } ?>

<?

if ($login_fabrica == 40) {?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_revenda_produto.php' class='menu'>Relat�rio de Revenda por Produto</a></td>
		<td class='descricao'>Relat�rio de Revenda por Produto</td>
	</tr>
	
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_numero_serie.php' class='menu'>Relat�rio de Retorno por N�mero de S�rie</a></td>
		<td class='descricao'>Relat�rio de retornos por n�mero de s�rie, informando total por defeito, estado e por posto</td>
	</tr>
<?php } ?>


<?if ($login_fabrica == 85) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_gelopar_posto_interno.php' class='menu'>Relat�rio de MO(Posto Gelopar)</a></td>
		<td class='descricao'>Relat�rio que mostra o valor de OS do posto 10641- Gelopar</td>
	</tr>
<?}?>


<?if($login_fabrica == 81){?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_scrap.php' class='menu'>Relat�rio de OS Scrap</a></td>
		<td class='descricao'>Relat�rio de ordens de servi�os scrapeadas.</td>
	</tr>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='extrato_os_scrap.php?posto_telecontrol=sim' class='menu'>Cadastro OS Scrap</a></td>
		<td class='descricao'>Permite cadastrar Scrap da OS Telecontrol.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 51 or $login_fabrica == 81){?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_gerencial_diario.php' class='menu'>Relat�rio Gerencial</a></td>
		<td class='descricao'>Relat�rio Gerencial.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 52){?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_pecas_os.php' class='menu'>Relat�rio Pe�as trocadas por Postos</a></td>
		<td class='descricao'>Relat�rio de pe�as trocadas por posto autorizado, linha e fam�lia</td>
	</tr>
<? } ?>


<?if($login_fabrica == 51){?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_pendente_gama_troca.php' class='menu'>Pe�as Pendentes Cr�ticas</a></td>
		<td class='descricao'>Relat�rio de pe�as pendentes Cr�ticas para troca.</td>
	</tr>
<? } ?>

<?if ($login_fabrica == 80) { #HD 260902 ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relat�rio de Troca</a></td>
		<td class='descricao'>Relat�rio de trocas de produtos.</td>
	</tr>
<?}?>

<? if ($login_fabrica == 43) {?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'>
			<img src='imagens/rel25.gif'>
		</td>
		<td nowrap width='260'>
			<a href='relatorio_status_os.php' class='menu'>
				Relat�rio de O.S. por status
			</a>
		</td>
		<td class='descricao'>
			Relat�rio de O.S. listadas de acordo com a sele��o dos status
		</td>
	</tr>
<?}?>

<?if($login_fabrica == 10){?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pa_todos.php' class='menu'>Relat�rio de Assist�ncias T�cnicas</a></td>
		<td class='descricao'>Relat�rio de Assist�ncias T�cnicas no Brasil.</td>
	</tr>
<? } ?>


<?if($login_fabrica == 30){?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_perfil_cliente.php' class='menu'>Relat�rio Perfil do Cliente</a></td>
		<td class='descricao'>Relat�rio de Perfil do Cliente, mostrando dados do OS, produto, e perfil do cliente.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 35){?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_cadence.php' class='menu'>Relat�rio de Ordem de Servi�o</a></td>
		<td class='descricao'>Relat�rio de ordem de servi�o, mostrando dados do consumidor, revenda, produto, e pe�as.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_fechamento_os_posto.php' class='menu'>Relat�rio de controle de fechamento O.S</a></td>
		<td class='descricao'>Consta o tempo m�dio que o posto levou para finalizar uma ordem de servi�o.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 45){ # HD34411 ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto.php' class='menu'>Relat�rio Troca de Produto</a></td>
		<td class='descricao'>Relat�rio de produto trocado na OS.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_movimentacao_produto.php' class='menu'>Relat�rio Movimenta��o de Produto</a></td>
		<td class='descricao'>Relat�rio de todas as movimenta��es do produto em um determinado per�odo.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_qtde.php' class='menu'>Relat�rio de Ger�ncia</a></td>
		<td class='descricao'>Relat�rio que mostra total do produto(trocado, utilizaram pe�as) do m�s.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_causa.php' class='menu'>Relat�rio Troca Produto Causa</a></td>
		<td class='descricao'>Relat�rio de produto trocado na OS(filtrando por causa).</td>
	</tr>
<? } ?>
<?if($login_fabrica == 20){?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_peca_sem_preco_al.php' class='menu'>Relat�rio de Pe�as sem Pre�o dos Paises da AL</a></td>
	<td class='descricao'>Relat�rio de Pe�as dos paises da Am�rica Latina que est�o sem pre�o cadastrado.</td>
</tr>
<tr bgcolor='#FFFFFF'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_qtde_valor.php' class='menu'>Relat�rio de quantidade / valor  de OSs</a></td>
	<td class='descricao'>Relat�rio de quantidade e valor de OSs por tipo de atendimento.</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_cortesia_comercial.php' class='menu'>Relat�rio de OS Cortesia Comercial</a></td>
	<td class='descricao'>Relat�rio de OS de Cortesia Comercial.</td>
</tr>
<?}?>

<?if($login_fabrica == 24){?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_pecas.php' class='menu'>Relat�rio de Pedidos de Pe�as</a></td>
	<td class='descricao'>Relat�rio de pe�as pedidas pelo posto autorizado em garantia ou compra.</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_revenda_os.php' class='menu'>Consulta Revenda x Produto</a></td>
	<td class='descricao'>Relat�rio de OS por revenda e quantidade em um per�odo</td>
</tr>
<? # HD 24493 - Inclu�do para a Suggar Relat�rio de pe�as exportadas ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_peca_exportada.php' class='menu'>Relat�rio de Pe�as Exportadas</a></td>
	<td class='descricao'>Relat�rio de pe�as exportadas pelo posto em um per�odo</td>
</tr>

<?}?>
<?if($login_fabrica ==11){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_faturamento_pecas.php' class='menu'>Relat�rio de Pe�as Faturadas</a></td>
	<td class='descricao'>Relat�rio de pe�as faturadas para os postos autorizados pela data de emiss�o da nota fiscal.</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_faturamento_garantia_pecas.php' class='menu'>Relat�rio de Pe�as Atendidas em Garantia</a></td>
	<td class='descricao'>Relat�rio de pe�as atendidas em garantia para os postos autorizados pela data de emiss�o da nota fiscal.</td>
</tr>
<?}?>

<?if($login_fabrica ==11){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_devolucao_pecas_pendentes.php' class='menu'>Relat�rio de Devolu��o de Pe�as Pendentes</a></td>
	<td class='descricao'>Relat�rio de pe�as atendidas em garantia para os postos autorizados com devolu��o pendente</td>
</tr>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_pecas_terceiros.php' class='menu'>Relat�rio de Pe�as em Poder de Terceiros</a></td>
	<td class='descricao'>Relat�rio de pe�as em poder de terceiros com base no LGR.</td>
</tr>

<?}?>
<?if($login_fabrica == 15){ // HD 55355 ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_nt_serie.php' class='menu'>Relat�rio de S�rie da Familia NT</a></td>
		<td class='descricao'>Relat�rio que mostra o n�mero de s�rie das OSs com produto da familia Lavadora NT e as OSs sem lan�amento de pe�a.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_defeito_constatado_peca.php' class='menu'>Relat�rio de Defeito Constatado Pe�a</a></td>
		<td class='descricao'>Relat�rio que consulta OS,Defeito Constatado e Pe�a.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_nt_serie_abertura.php' class='menu'>Relat�rio de S�rie da Familia NT Abertura</a></td>
		<td class='descricao'>Relat�rio que mostra o n�mero de s�rie das OSs com produto da familia Lavadora NT e as OSs sem lan�amento de pe�a pela data de abertura.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_mensal.php' class='menu'>Relat�rio de Ordem de Servi�o</a></td>
		<td class='descricao'>Relat�rio que mostra os dados das ordens de servi�os com base na na gera��o do extrato.</td>
	</tr>
<? } ?>
<?if($login_fabrica == 1){ // HD 87689 ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_locacao.php' class='menu'>Relat�rio de Produtos de Loca��o</a></td>
		<td class='descricao'>Relat�rio que mostra os produtos de loca��o.</td>
	</tr>
<? } ?>
<?if($login_fabrica == 15){ // HD 87689 ?>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_reincidencia_latinatec.php' class='menu'>Relat�rio de OS reincid�ntes</a></td>
		<td class='descricao'>Relat�rio que mostra as reincid�ncias de Ordens de Servi�o</td>
	</tr>
<? } ?>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>



<br>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class=cabecalho>RELAT�RIOS CALL-CENTER</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_callcenter_reclamacao_por_estado.php' class='menu'>Reclama��es por estado</a></td>
	<td nowrap class='descricao'>Hist�rico de atendimentos por estado.</td>
</tr>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<br>
<? if($login_fabrica==20){ ?>
<br>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class=cabecalho>RELAT�RIOS - QUALIDADE</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<!--<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pagamento_produto.php' class='menu'>Produto X Custo</a></td>
	<td class='descricao'>Relat�rio de OS's e seus produtos e valor pagos por per�odo.</td>
</tr>-->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pagamento_peca.php' class='menu'>Pe�a X Custo</a></td>
	<td class='descricao'>Relat�rio de OS's e seus produtos e valor pagos por pe�a.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_custo.php' class='menu'>Field Call Rate de Produto X Custo</a></td>
	<td class='descricao'>Relat�rio de Field Call Rate de Produtos e valor pagos por per�odo.</td>
</tr>
</table>

<br>

<? } ?>


<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>TAREFAS ADMINISTRATIVAS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<? if ($login_fabrica == 2){ ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='posto_login.php' class='menu'>Logar como Posto</a></td>
		<td nowrap class='descricao'>Acesse o sistema como se fosse o posto autorizado</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 11){ ?>
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='log_erro_integracao.php' class='menu'>Log de erros de integra��o</a></td>
		<td class='descricao'>Verificar erros na integra��o entre Logix e Telecontrol</td>
	</tr>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='manutencao_contato.php' class='menu'>Manuten��o de contatos �teis</a></td>
		<td class='descricao'>Manuten��o de contatos �teis da �rea do posto.</td>
	</tr>

<? } ?>

<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td NOWRAP><a href='admin_senha.php' class='menu'>Usu�rios ADMIN</a></td>
	<td class='descricao'>Cadastro de usu�rios administradores do sistema, com op��o para troca de senha e atribui��o de privil�gios de acesso aos programas.</td>
</tr>
<!-- ================================================================== -->
<? if (in_array($login_fabrica,array(10,86))) { //Famastil, por enquanto ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td NOWRAP><a href='consulta_auto_credenciamento.php' class='menu'>Auto-Credenciamento de Postos</a></td>
	<td class='descricao'>Lista postos que gostariam de ser credenciados da <?=$login_fabrica_nome?>,<br>
	mostra informa��es do posto, localiza no GoogleMaps<br>e permite credenciar postos.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td NOWRAP><a href='relatorio_usuario_admin.php' class='menu'>Relat�rio de Acesso</a></td>
	<td class='descricao'>Relat�rio de Controle de Acessos.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#AAAAAA'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='#mensalidade_telecontrol.php' class='menu'>Mensalidades Telecontrol</a></td>
	<td class='descricao'>Rela��o das mensalidades Telecontrol</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<? if($login_fabrica == 25 OR $login_fabrica == 10 OR $login_fabrica == 51) {?>
		<td nowrap width='260'><a href='envio_email_new.php' class='menu'>Envio de e-mail</a></td>
	<?}else{?>
		<td nowrap width='260'><a href='<? if($login_fabrica==14){echo "comunicado_intelbras.php";}else{ ?>envio_email.php<? } ?>' class='menu'>Envio de e-mail</a></td>
	<?}?>
	<td class='descricao'>Envia mensagens via e-mail para os Postos</td>
</tr>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='dados_teste.php' class='menu'>Limpar dados de Teste</a></td>
	<td class='descricao'>Apaga todas as informa��es do posto de teste, como OS, pedido e extrato</td>
</tr>
<? if($login_fabrica == 7) {?>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='libera_os_item_pedido_teste.php' class='menu'>Liberar Item em Garantia</a></td>
	<td nowrap class='descricao'>Libera os itens das OSs para gerarem pedidos.</td>
</tr>
<? } ?>
<? if($login_fabrica == 6) {?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='reincidencia_os_cadastro.php' class='menu'>Remanejamento de reincid�ncias</a></td>
	<td class='descricao'>Efetua a substitui��o da OS reincidida para a OS principal</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='libera_os_item_pedido.php' class='menu'>Liberar Item em Garantia</a></td>
	<td nowrap class='descricao'>Libera os itens das OSs para gerarem pedidos.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='libera_os_item_faturado.php' class='menu'>Liberar Item de Vendas</a></td>
	<td class='descricao'>Libera os itens do Pedido Faturado</td>
</tr>
<? } ?>
<?if($login_fabrica==20){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='upload_importa.php' class='menu'>Upload para Carga de Dados</a></td>
	<td class='descricao'>Efetua a carga de dados para atualiza��o do sistema.</td>
</tr>
<?}?>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<!--=====================================================================================================-->
<?if ($login_fabrica == 3 or $login_fabrica == 10 or $login_fabrica > 87){?>
<br/>

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>PESQUISA DE OPINI�O</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='opiniao_posto.php' class='menu'>Cadastro do Question�rio</a></td>
	<td nowrap class='descricao'>Cadastro do Question�rio de Opini�o do Posto</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='opiniao_posto_relatorio.php' class='menu'>Relat�rio de Opini�o dos Postos</a></td>
	<td nowrap class='descricao'>Relat�rio dos question�rios enviados aos Postos</td>
</tr>

<!-- ================================================================== -->

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<?}?>





<?
#==================== Menu para a GAMA acompanhar o DISTRIB ==================
if ($login_fabrica == 51 || $login_fabrica == 81) {
?>
<br>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>DISTRIBUI��O TELECONTROL</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='distrib_pendencia.php' class='menu'>Pend�ncia de Pe�as</a></td>
		<td nowrap class='descricao'>Pend�ncia de Pe�as dos Postos junto ao Distribuidor</td>
	</tr>
</table>
<?
}
?>

<br>
&nbsp;
<br>
&nbsp;

<? include "rodape.php" ?>

</body>
</html>
