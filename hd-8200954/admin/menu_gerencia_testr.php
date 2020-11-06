<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$title = "MENU GERÊNCIA";
$layout_menu = "gerencia";

include 'cabecalho.php';

?>

<!--
<br>
<center>
<img src='../imagens/embratel_logo.gif' valign='absmiddle'>
<br>
<font color='#330066'><b>Concluída migração para EMBRATEL</b>.</font>
<br>
<font size='-1'>
A <b>Telecontrol</b> agradece sua compreensão.
<br>Agora com a migração para o iDC EMBRATEL teremos
<br>um site mais veloz, robusto e confiável.
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
	<TD class=cabecalho>CREDENCIAMENTO DE ASSISTÊNCIAS TÉCNICAS</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='../credenciamento/suggar/index.php' target='blank_' class='menu'>Credenciamento de Assistências Técnicas</a></td>
	<td nowrap class='descricao'>Credenciamento e Descredenciamento de Assistências Técnicas.</td>
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
	<TD class=cabecalho>CREDENCIAMENTO DE ASSISTÊNCIAS TÉCNICAS</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='../credenciamento/hbtech/index_.php' target='blank_' class='menu'>Credenciamento de Assistências Técnicas</a></td>
	<td nowrap class='descricao'>Credenciamento e Descredenciamento de Assistências Técnicas.</td>
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
	<td nowrap width='260'><a href='fabricante_cadastro.php' target='blank_' class='menu'>Cadastro de fábricas</a></td>
	<td nowrap class='descricao'>Cadatramento de fabricantes pela página.</td>
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
	<TD class=cabecalho>CREDENCIAMENTO DE ASSISTÊNCIAS TÉCNICAS</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='../credenciamento/gera_contrato_crown.php' target='blank_' class='menu'>Contrato Assistências Técnicas</a></td>
	<td nowrap class='descricao'>Contrato para Assistências Técnicas.</td>
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
	<td nowrap width='260'><a href='os_parametros.php' class='menu'>Consulta Ordens de Serviço</a></td>
	<td nowrap class='descricao'>Consulta OS Lançadas</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='pedido_parametros.php' class='menu'>Consulta Pedidos de Peças</a></td>
	<td class='descricao'>Consulta pedidos efetuados por postos autorizados.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='acompanhamento_os_revenda_parametros.php' class='menu'>Acompanhamento de OS Revenda</a></td>
	<td nowrap class='descricao'>Consulta OS de Revenda Lançadas e Finalizadas</td>
</tr>
<? if ($login_fabrica == 43) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='status_os_posto.php' class='menu'>Acompanhamento de OS em aberto</a></td>
	<td nowrap class='descricao'>Consulta status das Ordens de Serviço em aberto</td>
</tr>
<?}?>

<? if ($login_fabrica == 6) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_enviadas_tectoy.php' class='menu'>OS com peças enviadas a fábrica</a></td>
	<td class='descricao'>Consulta OSs que o posto enviou peças para a fábrica. Autoriza ou não o pagamento de metade da mão-de-obra.</td>
</tr>
<? } ?>
<? if ($login_fabrica == 3) { // HD 55242?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_consulta_agrupada.php' class='menu'>Consulta Ordem de Serviço Agrupada</a></td>
	<td class='descricao'>Consulta OS agrupada.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 1 and $login_admin == 236) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_consulta_lite_etiqueta.php' class='menu'>Consulta OSs e gera etiquetas</a></td>
	<td nowrap class='descricao'>Transferência de OS entre postos</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 14) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_transferencia.php' class='menu'>Transferência de OS</a></td>
	<td nowrap class='descricao'>Transferência de OS entre postos</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 7) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_transferencia_filizola.php' class='menu'>Transferência de OS</a></td>
	<td nowrap class='descricao'>Transferência de OS entre postos</td>
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
	echo "<td align='left' width='300'><a href='bi/fcr_os.php' class='menu'>BI - Field Call Rate - Produto</a><font size='-2'>Percentual de quebra de produtos.<br><i>Considera apenas ordem de serviço fechada, apresentando custos</i><br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "<td align='center'><img src='imagens/icone_r5.png' width='50'></td>";
	echo "<td align='left' width='300'><a href='bi/fcr_pecas.php' class='menu'>BI - Field Call Rate - Peças</a><font size='-2'>Percentual de quebra de peças.<br><i>Considerando apenas ordem de serviço fechada</i><br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF'>";
	echo "<td align='center'><img src='imagens/icone_r6.png' width='50'></td>";
	echo "<td align='left' ><a href='bi/fcr_posto.php' class='menu'>BI - Serviço Autorizado</a><font size='-2'>Estatística de performance de consertos.<br><i>Considerando apenas ordem de serviço fechada, tempo de conserto, os sem peça</i><br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "<td align='center'><img src='imagens/icone_r2.png' width='50'></td>";
	echo "<td align='left' ><a href='bi/postos_usando' class='menu'>BI - Postos Usando</a><font size='-2'>Relatório de Postos por Linha apresentado OS e peças.<br><i>Considerando apenas ordem de serviço fechada.</i><br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E2EF'>
	<td colspan='4'><img src='imagens/spacer.gif' height='3'></td>
</tr>";
	echo"</table><br>";
}
echo "<table border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr height='18'>";
 echo "<td width='18' bgcolor='#AAAAAA'>&nbsp;</td>";
echo "<td align='left'><font size='1'><b>&nbsp; Relatório Desativado, caso necessite das informações favor entrar em contato com o suporte</b></font></td>";
echo "</tr>";
echo "<tr height='3'><td colspan='2'></td></tr>";
echo "</table>";
?>


<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>RELATÓRIOS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ==================================================================

<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='bi/fcr_mes.php' class='menu'>Field Call-Rate Optimizado</a></td>
	<td nowrap class='descricao'>Este relatório considera o mês inteiro de OS pela data da digitação. (Em testes)</td>
</tr>
-->
<? if ($login_fabrica == 3) { ?>
<tr bgcolor='#AAAAAA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='#relatorio_lancamentos.php' class='menu'>Lançamentos</a></td>
	<td nowrap class='descricao'>Postos que estão lançando ordens de serviço no site.</td>
</tr>
<? } ?>
<? if (in_array($login_fabrica,array(66,14,15,43))){//HD 44656 ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto.php' class='menu'>Field Call-Rate - Produtos </a></td>
	<td class='descricao'>
	Percentual de quebra de produtos.<br><i>Considera apenas ordem de serviço fechada, apresentando custos</i></td>
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
Relatório de defeitos por linha.
<? } ?>
Este relatório considera a data de geração do extrato aprovado.-->Percentual de quebra de produtos.<br><i>Considera apenas ordem de serviço fechada, apresentando custos</i><br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>
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
Relatório de defeitos por linha.
<? } ?>
Este relatório considera a data de geração do extrato aprovado.-->Detalhamento do Field Call Rate Produtos para Auditoria.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>
</tr>
<? #HD 179811?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='bi/fcr_os_detalhado_peca.php' class='menu'>BI -Field Call-Rate - Defeitos </a></td>
	<td class='descricao'>Detalhamento do Field Call Rate Produtos e peças com defeito, para Auditoria.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>
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
Relatório de defeitos por produtos.
<? } ?>-->
Relatório do percentual de defeitos das peças por produto.</td>
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
	<td class='descricao'>Considera OS lançadas no sistema filtrando pela data da digitação ou finalização. </td>
</tr>
<? } ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_lista_basica.php' class='menu'>Field Call-Rate - Produtos Lista Básica</a></td>
	<td class='descricao'>Relatório de quebras de peças da lista básica do produto</td>
</tr>
<? if (in_array($login_fabrica,array(66,14))) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_posto.php' class='menu'>Field Call-Rate - Postos</a></td>
	<td class='descricao'>Relatório de ocorrência de OS por familia por postos.</td>
</tr>
<?} ?>
<!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='bi/fcr_pecas.php' class='menu'>BI Field Call-Rate - Peças</a></td>
	<td class='descricao'>Percentual de quebra de peças.<br><i>Considera apenas ordem de serviço fechada, apresentando custos</i><br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i></td>
</tr>
<!-- ================================================================== -->
<? if (in_array($login_fabrica,array(66,14))){ ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_defeito_constatado.php' class='menu'>Field Call-Rate - Defeitos Constatados</a></td>
	<td class='descricao'>Relatório de ocorrência de OS por defeitos constatados.</td>
</tr>
<?} ?>
<!-- ================================================================== -->
<? if($login_fabrica==3){ ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_defeitos.php' class='menu'>Relatório de defeitos</a></td>
	<td class='descricao'>Relatório de defeitos de produtos e soluções a partir da data de digitação</td>
</tr><!-- ================================================================== -->
<? } ?>
<? if($login_fabrica==15){ ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_engenharia_serie.php' class='menu'>Relatório de defeitos por Nº série</a></td>
	<td class='descricao'>Relatório de defeitos de produtos e soluções a partir do número de série</td>
</tr><!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_serie_reoperado.php' class='menu'>Relatório Nº série Reoperado</a></td>
	<td class='descricao'>Relatório de número de séries reoperados.</td>
</tr><!-- ================================================================== -->
<? } ?>
<? if($login_fabrica==24){ ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_defeito_serie_fabricacao.php' class='menu'>Field Call-Rate - Produtos Número de Série</a></td>
	<td class='descricao'>Relatório de quebra dos produtos pela data de fabricação.</td>
</tr><!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_grupo.php' class='menu'>Field Call-Rate - Produtos Número de Série 2</a></td>
	<td class='descricao'>Relatório de quebra dos produtos X número de série.</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_pecas.php
' class='menu'>Field Call-Rate - Mão-de-obra Produtos X Peças</a></td>
	<td class='descricao'>Relatório mão-de-obra por produto e troca de peça específicos.</td>
</tr><!-- ================================================================== -->
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_troca_pecas.php' class='menu'>Relatório Troca de Peça</a></td>
	<td class='descricao'>Relatório de peças trocadas pelo posto autorizado, peças trocadas em garantia ou paga pelos clientes</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_os_sem_troca_peca.php' class='menu'>Relatório de OS sem troca de Peça</a></td>
	<td class='descricao'>Relatório em ordem de posto autorizado com maior índice de Ordens de Serviços sem troca de peça.</td>
</tr>
<? } ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<?if ($login_fabrica <> 81) {?> 
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_os_peca_sem_pedido.php' class='menu'>Relatório de OS de Peça sem Pedido</a></td>
	<td class='descricao'>Relatórioi em ordem de posto autorizado com maior índice de Ordens de Serviços de peça sem pedido.</td>
	<?}?>
</tr>
	<? if ($login_fabrica <> 14) {?>
		<tr bgcolor='#FAFAFA'>
		<td><img src='imagens/rel25.gif'></td>
		<td><a href='relatorio_quantidade_os.php' class='menu'>Relatório de Quantidade de OS's Aprovadas por LINHA</a></td>
		<td class='descricao'>Relatório que mostra a quantidade de OS aprovadas por postos em determinadas linhas nos últimos 3 meses.</td>
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
	<td nowrap width='260'><a href='relatorio_devolucao_obrigatoria.php' class='menu'>Devolução Obrigatória</a></td>
	<td class='descricao'>Peças que devem ser devolvidas para a Fábrica constando em Ordens de Serviços.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? //liberado para Tectoy HD 311406
	if ($login_fabrica==6) {?>
	<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_devolucao_obrigatoria_tectoy.php' class='menu'>Total de Peças Devolução Obrigatória</a></td>
		<td class='descricao'>Total de peças que devem ser devolvidas para a Fábrica.</td>
	</tr>
<? } ?>

<!-- ================================================================== -->
<? //liberado para Lenoxx hd 8231
	if ($login_fabrica==11) {?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_percentual_defeitos.php' class='menu'>Percentual de Defeitos</a></td>
		<td class='descricao'>Relatório por período de percentual dos defeitos de produtos.</td>
	</tr>
<? } else { ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#F0F0F0'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_percentual_defeitos.php' class='menu'>Percentual de Defeitos</a></td>
		<td class='descricao'>Relatório por período de percentual dos defeitos de produtos.</td>
	</tr>
<? } ?>
<?	if ($login_fabrica==52) {?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_defeito_constatado_os_anual.php' class='menu'>Relatório Anual de OS por Defeitos Constatados</a></td>
		<td class='descricao'>Relatório anual detalhando por família, grupo de defeito e defeito X mensal e total anual a quantidade de OS, bem como valores das mesmas</td>
	</tr>
<? } ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_tempo_conserto_mes.php' class='menu'>Permanência em conserto no mês</a></td>
		<td class='descricao'>Relatório que mostra o tempo (dias) de permanência do produto na assistência técnica no mês.</td>
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
		<td nowrap width='260'><a href='relatorio_tempo_conserto.php' class='menu'>Permanência em conserto</a></td>
		<td class='descricao'>Relatório que mostra tempo médio (dias) de permanência do produto na assistência técnica.</td>
	</tr>
<? } else { ?>
		<? if ($login_fabrica <> 14) {?>
			<tr bgcolor='#AAAAAA'>
			<td width='25'><img src='imagens/rel25.gif'></td>
			<td nowrap width='260'><a href='#relatorio_tempo_conserto.php' class='menu'>Permanência em conserto</a></td>
			<td class='descricao'>Relatório que mostra tempo médio (dias) de permanência do produto na assistência técnica.</td>
			</tr>
		<?}?>
<? } ?>

<!-- ================================================================== -->

<?	if ($login_fabrica==30) {?>
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_defeitos_esmaltec.php' class='menu'>Relatório Defeitos OS por Atendimento</a></td>
		<td class='descricao'>Relatório de Defeitos OS x Tipo de Atendimento.</td>
	</tr>
	<?}?>

<!-- ================================================================== -->

<? if (in_array($login_fabrica,array(66,1,2,3,7))){ ?>
<tr bgcolor='#AAAAAA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='#relatorio_prazo_atendimento_periodo.php' class='menu'>Período de atendimento da OS</a></td>
	<td class='descricao'>Relatório de acompanhamento do atendimento por período de OS.</td>
</tr>
<? } ?>
<? //liberado para Ibratele hd 138104
if($login_fabrica==8){ ?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_prazo_atendimento_periodo.php' class='menu'>Período de atendimento da OS</a></td>
	<td class='descricao'>Relatório de acompanhamento do atendimento por período de OS.</td>
</tr>
<? } ?>
<? if ($login_fabrica==6){?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_qualidade.php' class='menu'>Período de atendimento da OS</a></td>
	<td class='descricao'>Relatório de acompanhamento do atendimento por período de OS.</td>
</tr>
<?}?>
<!-- ================================================================== -->
<? if($login_fabrica == 3) {?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_perguntas_britania.php' class='menu'>Relatório DVD Fama e Game</a></td>
	<td class='descricao'>Relatório que mostra a quantidade de P. A. participaram do questionário.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica<>24){ ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='produtos_mais_demandados.php' class='menu'>Produtos mais demandados</a></td>
	<td class='descricao'>Relatório dos produtos mais demandados em Ordens de Serviços nos últimos meses.</td>
</tr>
<? } ?>
<!-- ================================================================== -->

<? if (in_array($login_fabrica,array(66,14,5,19,43))) {?>
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='defeito_os_parametros.php' class='menu'>Relatório de Ordens de Serviço</a></td>
	<td class='descricao'>Relatório de Ordens de Serviço lançadas no sistema.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica == 1) {?>
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='auditoria_os_fechamento_blackedecker.php' class='menu'>Auditoria de peças trocadas em garantia</a></td>
	<td class='descricao'>Faz pesquisas nas Ordens de Serviços previamente aprovadas em extrato.</td>
</tr>
<? } if($login_fabrica==20){?>
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_troca_os.php' class='menu'>Relatório de Troca de OS</a></td>
	<td class='descricao'>Verifica as OS de troca do PA.</td>
</tr>
<!-- ================================================================== -->
<?} if($login_fabrica ==2 OR $login_fabrica ==3 OR $login_fabrica ==11 OR $login_fabrica ==24) {?>
<? //liberado para Lenoxx hd 8231
	if ($login_fabrica==11) {?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='pendencia_posto.php' class='menu'>Pendências do posto</a></td>
		<td class='descricao'>Relatório de peças pendentes dos postos.</td>
	</tr>
	<?} else {?>
		<? if ($login_fabrica == 14) {?>
			<tr bgcolor='#FAFAFA'>
		<?}else{?>
			<tr bgcolor='#FOFOFO'>
		<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='pendencia_posto.php' class='menu'>Pendências do posto</a></td>
		<td class='descricao'>Relatório de peças pendentes dos postos.</td>
	</tr>
	<? } ?>
<? } ?>

<?if ($login_fabrica == 50) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_defeito_troca.php' class='menu'>Relatório de Troca de Peças</a></td>
		<td class='descricao'>Relatório de peças trocas os defeitos apresentados, listado por produtos.</td>
	</tr>
<?}?>

<?if ($login_fabrica == 2 AND $login_admin==989898) {?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='extrato_posto_devolucao_controle.php' class='menu'>Pendências do posto - NF</a></td>
	<td class='descricao'>Controle de Notas Fiscais de Devolução e Peças</td>
</tr>
<? } ?>
<?if (in_array($login_fabrica,array(66,14,24,2,11))) {?>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='os_relatorio.php' class='menu'>Status da Ordem de Serviço</a></td>
	<td class='descricao'>Status das ordens de serviços</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 5) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_serie.php' class='menu'>Relatório de Nº de Série</a></td>
		<td class='descricao'>Relatório de ocorrência de produtos por nº de série.</td>
	</tr>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_serie_ano.php' class='menu'>Relatório de Nº de Série Anual</a></td>
		<td class='descricao'>Relatório de ocorrência de produtos por nº de série no período de 12 meses.</td>
	</tr>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_producao_serie.php' class='menu'>Relatório de Produção</a></td>
		<td class='descricao'>Relatório de produção.</td>
	</tr>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_producao_nova_serie.php' class='menu'>Relatório de Produção Série Nova</a></td>
		<td class='descricao'>Relatório de produção.</td>
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
		<td nowrap width='260'><a href='relatorio_troca_produto.php' class='menu'>Relatório Troca de Produto</a></td>
		<td class='descricao'>Relatório de produto trocado na OS.</td>
	</tr>
	<? if ($login_fabrica == 14 || $login_fabrica == 72) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relatório Troca de Produto Total</a></td>
		<td class='descricao'>Relatório de produto trocado e arquivo .xls</td>
	</tr>
<? } ?>
<? if ($login_fabrica == 81 || $login_fabrica == 66 || $login_fabrica == 72) { ?>
	<tr>
                <td width='25'><img src='imagens/rel25.gif'></td>
                <td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relatório Troca de Produto Total</a></td>
                <td class='descricao'>Relatório de produto trocado e arquivo .xls</td>
        </tr>
<? } ?>
<? if ($login_fabrica == 3) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pecas_faturadas.php' class='menu'>Relatório de Peças Faturadas</a></td>
		<td class='descricao'>Relatório de peças faturadas.</td>
	</tr>
	<!-- ================================================================== -->
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_field_call_rate_produto_serie.php' class='menu'>Relatório OS com Nº de Série e Posto</a></td>
		<td class='descricao'>Relatório Ordens de Serviços lançadas no sistema por produto e por posto, e com número de série.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto.php' class='menu'>Relatório Troca de Produto</a></td>
		<td class='descricao'>Relatório de produto trocado na OS.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relatório Troca de Produto Total</a></td>
		<td class='descricao'>Relatório de produto trocado e arquivo .xls</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_linha.php' class='menu'>Relatório de OS digitadas por linha</a></td>
		<td class='descricao'>Relatório de OS digitadas por linha.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pecas_mes.php' class='menu'>Relatório de OS e Pecas digitadas</a></td>
		<td class='descricao'>Relatório contendo a qtde de OS e Peças digitadas.</td>
	</tr>
	<tr bgcolor="#AAAAAA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='#relatorio_garantia_faturado.php' class='menu'>Peças faturadas e garantia dos últimos quatro meses</a></td>
		<td class='descricao'>Quantidade de peças enviadas em garantia, comparadas com as peças faturadas, totalizados por linha.</td>
	</tr>
	<tr bgcolor="#AAAAAA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='#relatorio_diario.php' class='menu'>Relatório Diário</a></td>
		<td class='descricao'>Resumo mensal do Relatório Diário enviado por email.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_qtde_os.php' class='menu'>Relatório Qtde OS e Peças Anual</a></td>
		<td class='descricao'>Relatório Anual de quantidade de OS's e Peças por Data Digitação e Finalização.</td>
	</tr> 
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_qtde_os_fabrica.php' class='menu'>Relatório de OS COM PEÇAS e SEM PEÇAS Anual</a></td>
		<td class='descricao'>Relatório Anual de quantidade de OS's com peças e sem peças por Data Digitação e Finalização.</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica == 8) { ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_por_posto.php' class='menu'>Produtos por posto</a></td>
		<td class='descricao'>Relatório de produtos lançados em OS por posto em determinado período.</td>
	</tr>
<? } ?>
<!-- ================================================================== -->
<? if($login_fabrica == 1) { ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='rel_visao_mix_total.php' class='menu'> Visão geral por produto </a></td>
		<td class='descricao'>Relatório geral por produto.</td>
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
	<td class='descricao'>Calcula o custo médio de cada posto para realizar os consertos em garantia.</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_quebra_familia.php' class='menu'>Relatório de Quebra por Família</a></td>
	<td class='descricao'>Este relatório contém a quantidade de quebra durante os ultimos 12 meses levando em conta o fechamento do extrato de cada mês.</td>
</tr>
<? if($login_fabrica==15){ ?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_quebra_linha.php' class='menu'>Relatório de Quebra por Linha</a></td>
		<td class='descricao'>Este relatório contém a quantidade de quebra durante os ultimos 12 meses levando em conta o fechamento do extrato de cada mês.</td>
	</tr>
<? } ?>

<!-- ================================================================== -->
<? if (in_array($login_fabrica,array(66,14))) {?>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_defeito_constatado_os.php' class='menu'>Relatório de Defeitos Constatados por OS</a></td>
		<td class='descricao'>Relatório de Defeitos Constatados por Ordem de Serviço.</td>
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
	<td nowrap width='260'><a href='relatorio_field_call_rate_os_sem_peca_intelbras.php' class='menu'>Relatório de OS sem peça</a></td>
	<td class='descricao'>Relatório de Ordem de Serviço sem peças e seus respectivos defeitos reclamados, defeitos constatados e solução.</td>
</tr>
<tr bgcolor='#efefef'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_reincidencia.php' class='menu'>Relatório de OS Reincidente</a></td>
	<td class='descricao'>Relatório de Ordem de Serviço reincidentes X posto autorizado.</td>
</tr>



<!-- ================================================================== -->


<?
}
?>

<? if ($login_fabrica == 50) {?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_auditoria_os.php' class='menu'>Relatório de OS Auditadas</a></td>
	<td class='descricao'>Relatório de Ordens de Serviço auditadas por: Número de série; Com mais de 3 peças; Reincidências; E Ordens de Serviços que não passaram por nenhuma auditoria.
	</td>
</tr>
<?
}
?>


<?
if ($login_fabrica <> 14) {?>
<tr bgcolor='#efefef'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_field_call_rate_os_sem_peca.php' class='menu'>Relatório de OS sem peça</a></td>
	<td class='descricao'>Relatório de Ordem de Serviço sem peças e seus respectivos defeitos reclamados, defeitos constatados e solução.</td>
</tr>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='custo_os_nac_imp.php' class='menu'>Custo Nacionais "x" Importados</a></td>
	<td class='descricao'>Análise dos custos das Ordens de Serviços de produtos nacionais <i>versus</i> produtos importados.</td>
</tr>
<?}?>
<!-- ================================================================== -->

	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='auditoria_os_sem_peca.php' class='menu'>OS's abertas e sem Lançamento de Peças</a></td>
	<td class='descricao'>Relatório que consta os postos e a quantidade de OS's que estão abertas e sem lançamento de peças</td>
</tr>
<?if($login_fabrica == 19){?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_aberta_sac.php' class='menu'>Relatório de OS aberta pelo Sac</a></td>
	<td class='descricao'>Relatório de OS's abertas pelo Sac.</td>
</tr>
<? } ?>
<?if($login_fabrica == 11){?>
	<tr bgcolor='#FaFaFa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_credenciamento.php' class='menu'>Credenciamento de Postos por Mês</a></td>
		<td class='descricao'>Mostra os postos credenciados por mês.</td>
	</tr>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_atendida_os_aberta.php' class='menu'>OSs em aberto a mais de 15 dias que já foram atendidas</a></td>
		<td class='descricao'>Mostra OSs que tiveram suas peças faturadas pelo fabricante a mais de 15 dias e ainda não foram finalizadas pelo posto autorizado.</td>
	</tr>
	<tr bgcolor='#FaFaFa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_posto_produto_atendido.php' class='menu'>Produtos consertados pelo posto</a></td>
		<td class='descricao'>Relatório de produtos consertados por mês pelo posto autorizado.</td>
	</tr>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_aberta_fechada.php' class='menu'>Relatório de OS's digitadas</a></td>
		<td class='descricao'>Relatório das OS's digitadas por período</td>
	</tr>

	<?	//hd 16584
		if ($login_fabrica == 11) {?>
		<tr bgcolor='#F0F0F0'>
			<td width='25'><img src='imagens/rel25.gif'></td>
			<td nowrap width='260'><a href='relatorio_produto_os_finalizada.php' class='menu'>Relatório OSs finalizadas por produto</a></td>
			<td class='descricao'>Mostra a quantidade de OSs finalizadas por modelo de produto.</td>
		</tr>
	<?}?>
<?}?>
<?if($login_fabrica == 3){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_auditoria_previa.php' class='menu'>Relatório de OSs auditadas</a></td>
	<td class='descricao'>Relatório de OSs que sofreram auditoria prévia.</td>
</tr>
<?}?>
<?if($login_fabrica == 20){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='produto_custo_tempo.php' class='menu'>Relatório de Custo Tempo Cadastrado</a></td>
	<td class='descricao'>Relatório que consta o custo tempo cadastrado separados por família.</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='peca_informacoes_pais.php' class='menu'>Relatório de peça e preço por país</a></td>
	<td class='descricao'>Relatório que consta as peças cadastradas por país.</td>
</tr>
<tr bgcolor='#FFFFFF'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_cfa.php' class='menu'>Relatório de Garantia dividido por CFA's</a></td>
	<td class='descricao'>Relatório de gastos por Família e Origem de fabricação.</td>
</tr>
<?}?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_posto_peca.php' class='menu'>Relatório de Peças Por Posto</a></td>
	<td class='descricao'>Relatório de acordo com a data em que a OS foi finalizada.</td>
</tr>
<?if($login_fabrica == 3){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_preco_produto_acabado.php' class='menu'>Relatório de Preços de Aparelhos</a></td>
	<td class='descricao'>Relatório de preços de produto acabado.</td>
</tr>
<?}?>

<? if($login_fabrica==7){?>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_peca_garantia.php' class='menu'>Relatório de Peças em Garantia</a></td>
	<td class='descricao'>Relatório de peças com a classificação de OS garantia.</td>
</tr>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_sla.php' class='menu'>Relatório SLA</a></td>

	<td class='descricao'>Relatório SLA</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_back_log.php' class='menu'>Relatório Back-Log</a></td>
	<td class='descricao'>Relatório Back-Log</td>
</tr>


<? } ?>

<?if($login_fabrica == 2 or $login_fabrica ==15){ // HD 38831 58539?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_comunicado.php' class='menu'>Relatório de comunicado lido</a></td>
		<td class='descricao'>Relatório dos postos que confirmaram a leitura de comunicado.</td>
	</tr>
<? } ?>
<?if($login_fabrica == 2 ){ // HD 133069?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pendencia_codigo_componente.php' class='menu'>Relatório de pendências por Código</a></td>
		<td class='descricao'>Relatório de pendências por código de componente com filtro de posto opcional.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 51){?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FOFOFO'>
	<?}else{?>
		<tr bgcolor='#FAFAFA'>
	<?}?>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_pendente_gama.php' class='menu'>Relatório de Peças Pendentes</a></td>
		<td class='descricao'>Relatório de peças pendentes nas ordens de serviços.</td>
	</tr>
<? } else { ?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_pendente.php' class='menu'>Relatório de Peças Pendentes</a></td>
		<td class='descricao'>Relatório de peças pendentes nas ordens de serviços.</td>
	</tr>
<? } ?>

<?

if ($login_fabrica == 40) {?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_revenda_produto.php' class='menu'>Relatório de Revenda por Produto</a></td>
		<td class='descricao'>Relatório de Revenda por Produto</td>
	</tr>
	
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_numero_serie.php' class='menu'>Relatório de Retorno por Número de Série</a></td>
		<td class='descricao'>Relatório de retornos por número de série, informando total por defeito, estado e por posto</td>
	</tr>
<?php } ?>


<?if ($login_fabrica == 85) { ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_gelopar_posto_interno.php' class='menu'>Relatório de MO(Posto Gelopar)</a></td>
		<td class='descricao'>Relatório que mostra o valor de OS do posto 10641- Gelopar</td>
	</tr>
<?}?>


<?if($login_fabrica == 81){?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_scrap.php' class='menu'>Relatório de OS Scrap</a></td>
		<td class='descricao'>Relatório de ordens de serviços scrapeadas.</td>
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
		<td nowrap width='260'><a href='relatorio_gerencial_diario.php' class='menu'>Relatório Gerencial</a></td>
		<td class='descricao'>Relatório Gerencial.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 52){?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_pecas_os.php' class='menu'>Relatório Peças trocadas por Postos</a></td>
		<td class='descricao'>Relatório de peças trocadas por posto autorizado, linha e família</td>
	</tr>
<? } ?>


<?if($login_fabrica == 51){?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_peca_pendente_gama_troca.php' class='menu'>Peças Pendentes Críticas</a></td>
		<td class='descricao'>Relatório de peças pendentes Críticas para troca.</td>
	</tr>
<? } ?>

<?if ($login_fabrica == 80) { #HD 260902 ?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_total.php' class='menu'>Relatório de Troca</a></td>
		<td class='descricao'>Relatório de trocas de produtos.</td>
	</tr>
<?}?>

<? if ($login_fabrica == 43) {?>
	<tr bgcolor='#FAFAFA'>
		<td width='25'>
			<img src='imagens/rel25.gif'>
		</td>
		<td nowrap width='260'>
			<a href='relatorio_status_os.php' class='menu'>
				Relatório de O.S. por status
			</a>
		</td>
		<td class='descricao'>
			Relatório de O.S. listadas de acordo com a seleção dos status
		</td>
	</tr>
<?}?>

<?if($login_fabrica == 10){?>
	<tr bgcolor='#F0F0F0'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_pa_todos.php' class='menu'>Relatório de Assistências Técnicas</a></td>
		<td class='descricao'>Relatório de Assistências Técnicas no Brasil.</td>
	</tr>
<? } ?>


<?if($login_fabrica == 30){?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_perfil_cliente.php' class='menu'>Relatório Perfil do Cliente</a></td>
		<td class='descricao'>Relatório de Perfil do Cliente, mostrando dados do OS, produto, e perfil do cliente.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 35){?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_cadence.php' class='menu'>Relatório de Ordem de Serviço</a></td>
		<td class='descricao'>Relatório de ordem de serviço, mostrando dados do consumidor, revenda, produto, e peças.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_fechamento_os_posto.php' class='menu'>Relatório de controle de fechamento O.S</a></td>
		<td class='descricao'>Consta o tempo médio que o posto levou para finalizar uma ordem de serviço.</td>
	</tr>
<? } ?>

<?if($login_fabrica == 45){ # HD34411 ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto.php' class='menu'>Relatório Troca de Produto</a></td>
		<td class='descricao'>Relatório de produto trocado na OS.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_movimentacao_produto.php' class='menu'>Relatório Movimentação de Produto</a></td>
		<td class='descricao'>Relatório de todas as movimentações do produto em um determinado período.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_qtde.php' class='menu'>Relatório de Gerência</a></td>
		<td class='descricao'>Relatório que mostra total do produto(trocado, utilizaram peças) do mês.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_troca_produto_causa.php' class='menu'>Relatório Troca Produto Causa</a></td>
		<td class='descricao'>Relatório de produto trocado na OS(filtrando por causa).</td>
	</tr>
<? } ?>
<?if($login_fabrica == 20){?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_peca_sem_preco_al.php' class='menu'>Relatório de Peças sem Preço dos Paises da AL</a></td>
	<td class='descricao'>Relatório de Peças dos paises da América Latina que estão sem preço cadastrado.</td>
</tr>
<tr bgcolor='#FFFFFF'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_qtde_valor.php' class='menu'>Relatório de quantidade / valor  de OSs</a></td>
	<td class='descricao'>Relatório de quantidade e valor de OSs por tipo de atendimento.</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_cortesia_comercial.php' class='menu'>Relatório de OS Cortesia Comercial</a></td>
	<td class='descricao'>Relatório de OS de Cortesia Comercial.</td>
</tr>
<?}?>

<?if($login_fabrica == 24){?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_pecas.php' class='menu'>Relatório de Pedidos de Peças</a></td>
	<td class='descricao'>Relatório de peças pedidas pelo posto autorizado em garantia ou compra.</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_revenda_os.php' class='menu'>Consulta Revenda x Produto</a></td>
	<td class='descricao'>Relatório de OS por revenda e quantidade em um período</td>
</tr>
<? # HD 24493 - Incluído para a Suggar Relatório de peças exportadas ?>
	<? if ($login_fabrica == 14) {?>
		<tr bgcolor='#FAFAFA'>
	<?}else{?>
		<tr bgcolor='#FOFOFO'>
	<?}?>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_peca_exportada.php' class='menu'>Relatório de Peças Exportadas</a></td>
	<td class='descricao'>Relatório de peças exportadas pelo posto em um período</td>
</tr>

<?}?>
<?if($login_fabrica ==11){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_faturamento_pecas.php' class='menu'>Relatório de Peças Faturadas</a></td>
	<td class='descricao'>Relatório de peças faturadas para os postos autorizados pela data de emissão da nota fiscal.</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_faturamento_garantia_pecas.php' class='menu'>Relatório de Peças Atendidas em Garantia</a></td>
	<td class='descricao'>Relatório de peças atendidas em garantia para os postos autorizados pela data de emissão da nota fiscal.</td>
</tr>
<?}?>

<?if($login_fabrica ==11){?>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_devolucao_pecas_pendentes.php' class='menu'>Relatório de Devolução de Peças Pendentes</a></td>
	<td class='descricao'>Relatório de peças atendidas em garantia para os postos autorizados com devolução pendente</td>
</tr>
<tr bgcolor='#FaFaFa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_pecas_terceiros.php' class='menu'>Relatório de Peças em Poder de Terceiros</a></td>
	<td class='descricao'>Relatório de peças em poder de terceiros com base no LGR.</td>
</tr>

<?}?>
<?if($login_fabrica == 15){ // HD 55355 ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_nt_serie.php' class='menu'>Relatório de Série da Familia NT</a></td>
		<td class='descricao'>Relatório que mostra o número de série das OSs com produto da familia Lavadora NT e as OSs sem lançamento de peça.</td>
	</tr>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_defeito_constatado_peca.php' class='menu'>Relatório de Defeito Constatado Peça</a></td>
		<td class='descricao'>Relatório que consulta OS,Defeito Constatado e Peça.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_nt_serie_abertura.php' class='menu'>Relatório de Série da Familia NT Abertura</a></td>
		<td class='descricao'>Relatório que mostra o número de série das OSs com produto da familia Lavadora NT e as OSs sem lançamento de peça pela data de abertura.</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_mensal.php' class='menu'>Relatório de Ordem de Serviço</a></td>
		<td class='descricao'>Relatório que mostra os dados das ordens de serviços com base na na geração do extrato.</td>
	</tr>
<? } ?>
<?if($login_fabrica == 1){ // HD 87689 ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_produto_locacao.php' class='menu'>Relatório de Produtos de Locação</a></td>
		<td class='descricao'>Relatório que mostra os produtos de locação.</td>
	</tr>
<? } ?>
<?if($login_fabrica == 15){ // HD 87689 ?>
	<tr bgcolor="#F0F0F0">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_reincidencia_latinatec.php' class='menu'>Relatório de OS reincidêntes</a></td>
		<td class='descricao'>Relatório que mostra as reincidências de Ordens de Serviço</td>
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
	<TD class=cabecalho>RELATÓRIOS CALL-CENTER</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_callcenter_reclamacao_por_estado.php' class='menu'>Reclamações por estado</a></td>
	<td nowrap class='descricao'>Histórico de atendimentos por estado.</td>
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
	<TD class=cabecalho>RELATÓRIOS - QUALIDADE</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<!--<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pagamento_produto.php' class='menu'>Produto X Custo</a></td>
	<td class='descricao'>Relatório de OS's e seus produtos e valor pagos por período.</td>
</tr>-->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pagamento_peca.php' class='menu'>Peça X Custo</a></td>
	<td class='descricao'>Relatório de OS's e seus produtos e valor pagos por peça.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto_custo.php' class='menu'>Field Call Rate de Produto X Custo</a></td>
	<td class='descricao'>Relatório de Field Call Rate de Produtos e valor pagos por período.</td>
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
		<td nowrap width='260'><a href='log_erro_integracao.php' class='menu'>Log de erros de integração</a></td>
		<td class='descricao'>Verificar erros na integração entre Logix e Telecontrol</td>
	</tr>
	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='manutencao_contato.php' class='menu'>Manutenção de contatos úteis</a></td>
		<td class='descricao'>Manutenção de contatos úteis da área do posto.</td>
	</tr>

<? } ?>

<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td NOWRAP><a href='admin_senha.php' class='menu'>Usuários ADMIN</a></td>
	<td class='descricao'>Cadastro de usuários administradores do sistema, com opção para troca de senha e atribuição de privilégios de acesso aos programas.</td>
</tr>
<!-- ================================================================== -->
<? if (in_array($login_fabrica,array(10,86))) { //Famastil, por enquanto ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td NOWRAP><a href='consulta_auto_credenciamento.php' class='menu'>Auto-Credenciamento de Postos</a></td>
	<td class='descricao'>Lista postos que gostariam de ser credenciados da <?=$login_fabrica_nome?>,<br>
	mostra informações do posto, localiza no GoogleMaps<br>e permite credenciar postos.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td NOWRAP><a href='relatorio_usuario_admin.php' class='menu'>Relatório de Acesso</a></td>
	<td class='descricao'>Relatório de Controle de Acessos.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#AAAAAA'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='#mensalidade_telecontrol.php' class='menu'>Mensalidades Telecontrol</a></td>
	<td class='descricao'>Relação das mensalidades Telecontrol</td>
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
	<td class='descricao'>Apaga todas as informações do posto de teste, como OS, pedido e extrato</td>
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
	<td><a href='reincidencia_os_cadastro.php' class='menu'>Remanejamento de reincidências</a></td>
	<td class='descricao'>Efetua a substituição da OS reincidida para a OS principal</td>
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
	<td class='descricao'>Efetua a carga de dados para atualização do sistema.</td>
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
  <TD class=cabecalho>PESQUISA DE OPINIÃO</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='opiniao_posto.php' class='menu'>Cadastro do Questionário</a></td>
	<td nowrap class='descricao'>Cadastro do Questionário de Opinião do Posto</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='opiniao_posto_relatorio.php' class='menu'>Relatório de Opinião dos Postos</a></td>
	<td nowrap class='descricao'>Relatório dos questionários enviados aos Postos</td>
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
  <TD class=cabecalho>DISTRIBUIÇÃO TELECONTROL</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='distrib_pendencia.php' class='menu'>Pendência de Peças</a></td>
		<td nowrap class='descricao'>Pendência de Peças dos Postos junto ao Distribuidor</td>
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
