<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include 'autentica_admin.php';

$title = "MENU FINANCEIRO";
$layout_menu = "financeiro";
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


<?
if ($login_fabrica == 3){
?>

<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>INFORMA��ES FINANCEIRAS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='devolucao_cadastro.php' class='menu'>Notas de Devolu��o</a></td>
	<td nowrap class='descricao'>Consulta as Notas de Devolu��o</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='acerto_contas.php' class='menu'>Encontro de Contas</a></td>
	<td class='descricao'>Realiza o encontro de contas</td>
</tr>
<?
}
?>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<br/>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>MANUTEN��ES EM EXTRATOS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<?
if($login_fabrica == 8){
?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='os_extrato_pre.php' class='menu'>Pr� fechamento de extratos</a></td>
	<td class='descricao'>Pr� fechamento de extratos para visualiza��o da quantidade de OS do posto at� a data limite e o valor de m�o-de-obra.</a>
<? } ?>
<?
if($login_fabrica <> 20){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><? if($login_fabrica == 11 OR $login_fabrica == 25 OR $login_fabrica == 50){?>
		<a href='os_extrato_por_posto.php' class='menu'>Fechamento de extratos</a>
	<?}else{?>
	<a href='os_extrato<? if($login_fabrica == 6 or $login_fabrica == 2){echo "_new";}?>.php' class='menu'>Fechamento de extratos</a>
	<? } ?>
	</td>
	<td class='descricao'>Fecha o extrato de cada posto, totalizando o que cada um tem a receber de m�o-de-obra, suas pe�as de devolu��o obrigat�ria, e demais informa��es de fechamento.
<? if($login_fabrica == 6){?>
<a href='os_extrato_por_posto.php' class='menu'>Por posto (em teste).</a>
<? } ?>
	</td>
</tr>
<?}?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='extrato_consulta.php' class='menu'>Manuten��o de Extratos</a></td>
	<td class='descricao'>Permite retirar ordens de servi�os de um extrato, recalcular o extrato, e dar baixa em seu pagamento.</td>
</tr>
<? if($login_fabrica == 20 OR $login_fabrica == 30 ) {?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='extrato_liberado.php' class='menu'>Libera��o de Extrato</a></td>
	<td class='descricao'>Libera extratos para aprova��o.</td>
</tr>
<?}?>

<? if ($login_fabrica == 1 ) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_aprovado_consulta.php' class='menu'>Extratos Aprovados</a></td>
	<td class='descricao'>Permite enviar um extrato para o financeiro.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_financeiro_consulta.php' class='menu'>Extratos Enviados ao Financeiro</a></td>
	<td class='descricao'>Consulta e Manuten��o de Extratos Enviados ao Financeiro.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_custo_pecas.php' class='menu'>Custo das Pe�as</a></td>
	<td class='descricao'>Digita��o manual dos custos das pe�as, quando n�o for encontrado o �ltimo faturamento respectivo.</td>
</tr>
<? } ?>
<? if($login_fabrica==1) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='acumular_extratos.php' class='menu'>Acumular Extratos</a></td>
	<td class='descricao'>Admin informa um valor e sistema acumula os extratos menores que este valor, desde que este extrato n�o tenha OS fechada a mais de 30 dias</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pendencia_consulta.php' class='menu'>Pend�ncia Extratos</a></td>
	<td class='descricao'>Consulta e manuten��o de pend�ncia de extratos.</td>
</tr>


<? } ?>

<?

if($login_fabrica <> 3){?>
	<tr bgcolor='#fafafa'>
		<td><img src='imagens/marca25.gif'></td>
		<td>
			<a href='extrato_avulso.php' class='menu'>Lan�amento avulso / Extratos </a></td>
			<td class='descricao'>Permite gerar um novo lan�amento avulso, com isto, um novo extrato tamb�m � gerado.</td>
	</tr>
<?}
if($login_fabrica == 3){?>
	<tr bgcolor='#fafafa'>
		<td><img src='imagens/marca25.gif'></td>
		<td>
			<a href='extrato_avulso_britania.php' class='menu'>Lan�amento avulso / Extratos </a></td>
			<td class='descricao'>Permite gerar um novo lan�amento avulso, com isto, um novo extrato tamb�m � gerado.</td>
	</tr>
<?}?>

<?if($login_fabrica == 6 OR $login_fabrica == 59){?>
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='lancamentos_avulsos_cadastro.php' class='menu'>Cadastro Lan�amentos Avulsos</a></td>
	<td nowrap class='descricao'>Cadastro dos Lan�amentos Avulsos ao Extrato</td>
</tr>
<?}?>
<? if($login_fabrica==11) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='movimentacao_postos_lenoxx.php' class='menu'>Movimenta��o do Posto Autorizado</a></td>
	<td class='descricao'>Relat�rio de Movimenta��o do Posto Autorizado entre per�odos.</td>
</tr>
<tr bgcolor='#fAfAfA'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='movimentacao_revenda_lenoxx.php' class='menu'>Movimenta��o da Revenda</a></td>
	<td class='descricao'>Relat�rio de Movimenta��o da Revenda entre per�odos.</td>
</tr>
<? } ?>

<? if($login_fabrica==1){ ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td> <a href='aprova_os_troca.php' class='menu'>Aprova��o de OS Troca</a></td>
	<td class='descricao'>Manuten��o de Ordem de Servi�o de Troca.</td>
</tr>
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_excluir.php' class='menu'>Excluir Ordem de Servi�o</a></td>
	<td nowrap class='descricao'>Exclua Ordem de Servi�os do Posto</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td> <a href='aprova_exclusao.php' class='menu'>Aprova��o de OS Exclu�da</a></td>
	<td class='descricao'>Aprove de Ordem de Servi�o Exclu�da.</td>
</tr>
<? } ?>
<? if ($login_fabrica == 3 ) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_posto_britania.php?somente_consulta=sim' class='menu'>Consulta de Extratos de POSTOS</a></td>
	<td class='descricao'>Permite visualizar os extratos dos postos.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_posto_britania.php' class='menu'>Confer�ncia de Extratos de POSTOS</a></td>
	<td class='descricao'>Permite visualizar os extratos dos postos e realizar a confer�ncia das OSs.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_distribuidor.php' class='menu'>Consulta de Extratos de DISTRIBUIDOR</a></td>
	<td class='descricao'>Permite visualizar os extratos dos distribuidores.</td>
</tr>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='manutencao_logistica_reversa.php' class='menu'>Manuten��o de Logistica Reversa</a></td>
        <td class='descricao'>Permite apagar e alterar n�mero da nota fiscal de devolu��o.</td>
</tr>
<? } ?>

<? if ($login_fabrica == 11 OR $login_fabrica == 25 OR $login_fabrica == 43 OR $login_fabrica == 24 OR $login_fabrica == 72 OR $login_fabrica > 80) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'/></td>
	<td><a href='extrato_posto_devolucao_controle.php' class='menu'>Controle de Notas de Devolu��o</a></td>
	<td class='descricao'>Consulta ou confirme notas fiscais de devolu��o.</td>
</tr>
<? } ?>

<? if ($login_fabrica <> 6) { //takashi 18/08/2007  eu nao sabia para que servia, entao tirei?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='motivo_recusa_cadastro.php' class='menu'>Motivo de Recusa</a></td>
	<td class='descricao'>Cadastro de Motivo de Recusa de OS dos Extratos.</td>
</tr>
<? } ?>
<? if ($login_fabrica == 24 ) { ?>

<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'/></td>
	<td><a href='extrato_baixa.php' class='menu'>Pagamento de Extratos</a></td>
	<td class='descricao'>Permite efetuar o pagamento de extratos gerados.</td>
</tr>
<? } ?>
<? if ($login_fabrica == 1 ) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='upload_importa_black.php' class='menu'>UPLOAD arquivo pagamento</a></td>
	<td class='descricao'>Atualiza o site Telecontrol com a previs�o de pagamento de extrato.</td>
</tr>
<? } ?>
<? if (in_array($login_fabrica,array(1,3,7))) { ?>
<tr bgcolor='#fafafa'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='estoque_posto_movimento.php' class='menu'>Movimenta��o Estoque</a></td>
        <td class='descricao'>Visualiza��o da movimenta��o do estoque do posto autorizado.</td>
</tr>
<? } ?>

<? if ($login_fabrica == 3) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_extrato_detalhe.php' class='menu'>Relat�rio Extratos de POSTOS</a></td>
        <td class='descricao'>Relat�rio para visualizar detalhe dos extratos dos postos.</td>
</tr>
<? } ?>

<? if ($login_fabrica == 30 ) { ?>

<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='gera_circular.php' class='menu'>Cadastro Circular Interna</a></td>
	<td class='descricao'>Permite gerar uma circular interna em pdf dos extratos liberados.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='consulta_circular.php' class='menu'>Consulta Circular Interna</a></td>
	<td class='descricao'>Permite consultar o n�mero de circular interna em pdf dos extratos liberados.</td>
</tr>
<? } ?>


<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>




<!-- RELAT�RIOS DE EXTRATOS E PAGAMENTOS -->
<BR><TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
  <td width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <td class=cabecalho>RELAT�RIOS</TD>
  <td width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</tr>
</table>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>

<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_ressarcimento.php' class='menu'>Baixar Ressarcimento</a></td>
	<td class='descricao'>Baixar Ressarcimento de Ordem de Servi�o</td>
</tr>


<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_avulso.php' class='menu'>Avulsos Pagos em Extrato</a></td>
	<td class='descricao'>Todos os pagamentos avulsos pagos em extrato.</td>
</tr>
<?
if($login_fabrica==24){
?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_pago.php' class='menu'>Extrato Baixados</a></td>
	<td class='descricao'>Relat�rio dos extratos baixados.</td>
</tr>
<? }?>
<?if($login_fabrica == 30 or $login_fabrica == 50){?>
	<tr bgcolor='#fafafa'>
		<td><img src='imagens/marca25.gif'></td>
		<td><a href='relatorio_gasto_km.php' class='menu'>Gasto com km Pagos em Extrato</a></td>
		<td class='descricao'>Valores pagos em extrato pelo deslocamento no atendimento do posto autorizado.</td>
	</tr>

<?}?>

<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='posto_dados_pagamento.php' class='menu'>Dados Banc�rios para Pagamento</a></td>
	<td class='descricao'>Todas as informa��es banc�rias para pagamentos dos postos autorizados</td>
</tr>

<?if($login_fabrica == 3){ // HD 64220 - Etiquetas com o endere�o dos postos, Brit�nia ?>
	<TR bgcolor='#fafafa'>
		<TD><img src='imagens/marca25.gif'/></TD>
		<TD><A href='etiqueta_posto.php' class='menu'>Etiquetas de endere�o</A></TD>
		<TD class='descricao'>Imprime etiquetas com o endere&ccedil;o dos postos selecionados.</TD>
	</TR>

<?}?>

<?
if($login_fabrica == 20){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_custo_tempo.php' class='menu'>Custo Tempo de Extratos</a></td>
	<td class='descricao'>Neste relat�rio cont�m as OS e seus respectivos Custo Tempo por um determinado per�odo</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_aprovado.php' class='menu'>Tempo de An�lise de Extratos</a></td>
	<td class='descricao'>Esse relat�rio informa a quantidade de tempo para an�lise do extrato</td>
</tr>

<?
}
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pagamento.php' class='menu'>Valores de Extratos</a></td>
	<td class='descricao'>Informa todos os extratos e valores a serem pagos para os postos.</td>
</tr>
<?	//retirado a pedido de Andre chamado 2254
	if ($login_fabrica <> 20) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pagamento_produto.php' class='menu'>Produto X Custo</a></td>
	<td class='descricao'>Relat�rio de OS's e seus produtos e valor pagos por per�odo.</td>
</tr>
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
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_field_call_rate_familia_custo.php' class='menu'>Field Call Fam�lia de Produto X Custo</a></td>
	<td class='descricao'>Relat�rio de Field Call Rate de Fam�lia e valor pagos por per�odo.</td>
</tr>
<? } ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='posto_extrato_ano.php' class='menu'>Comparativo Anual de m�dia de extrato</a></td>
	<td class='descricao'>Valor mensal dos extratos do posto num per�odo de 12 meses.</td>
</tr>
<? if ($login_fabrica == 20) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_semestral_bosch.php' class='menu'>Controle de Garantia Semestral</a></td>
	<td class='descricao'>Relat�rio semestral com: total de OSs, total de pe�as, total de m�o de obra, total pago e m�dia por os,</td>
</tr>
<? } ?>
<? # HD 24472 - Francisco Ambrozio (4/8/08) - Inclu�do link Relat�rio OS
   #   Conferidas por Linha - Britania.
   if ($login_fabrica == 3) { ?>
		<tr bgcolor='#f0f0f0'>
				<td><img src='imagens/marca25.gif'></td>
				<td><a href='relatorio_os_conferida_linha.php' class='menu'>Relat�rio de OSs Conferidas</a></td>
				<td class='descricao'>Relat�rio de ordens de servi�o conferidas por linha.</td>
		</tr>
<? } ?>
<? if ($login_fabrica==11) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_field_call_rate_gastos_postos.php' class='menu'>Relat�rio de M�o-de-obra</a></td>
        <td class='descricao'>Relat�rio de pagamento de m�o-de-obra por posto, per�odo e produto.</td>
</tr>
<? } ?>
<?
/*hd: 91609*/
if ($login_fabrica==30) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_detalhado_esmaltec.php' class='menu'>Relat�rio de Extrato Detalhado</a></td>
	<td class='descricao'>Valor dos extratos com filtro de fam�lia e como resultado os detalhes de valor de m�o de obra, pe�as e km.</td>
</tr>
<? }?>

<? if ($login_fabrica==2) { ?>
<!--<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='extrato_posto_devolucao_controle.php' class='menu'>Controle de Notas de Devolu��o</a></td>
        <td class='descricao'><h1 style='display:inline'>EM TESTE</h1> Consulta ou confirme notas fiscais de devolu��o.</td>
</tr>
-->
<? } ?>
<? if ($login_fabrica==24) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_pgto_mo.php' class='menu'>Relat�rio de M�o-de-obra</a></td>
        <td class='descricao'>Relat�rio de pagamento de m�o-de-obra por posto, per�odo e produto.</td>
</tr>
<? } ?>
<? if ($login_fabrica==5) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_mobra_relacao.php' class='menu'>Relat�rio Custo x Posto</a></td>
        <td class='descricao'>Relat�rio do total de produto e m�o-de-obra pagos por posto nas rela��es ME, MK, ML/MC.</td>
</tr>
<? } ?>

<? if ($login_fabrica == 11 ) { ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_parametros_finalizada.php' class='menu'>Relat�rio OS Finalizada + M�o-de-Obra</a></td>
	<td class='descricao'>Relat�rio Ordens de Servi�o finalizadas com m�o-de-obra e pe�as aplicadas</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='extrato_peca_retorno_obrigatorio.php' class='menu'>Relat�rio Devolu��o Obrigat�ria</a></td>
	<td class='descricao'>Relat�rio de Pe�as de Retorno Obrigat�rio</td>
</tr>
<? } ?>
<? if ($login_fabrica == 1 ) { ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='extrato_documento_consulta.php' class='menu'>Relat�rio de Pend�ncia de Documento</a></td>
	<td class='descricao'>Relat�rio de Todas as Pend�ncias Lan�adas nos Extratos.</td>
</tr>
<? } ?>
<?
if($login_fabrica == 14){
?>
	<!--<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='extrato_excluido.php' class='menu'>Relat�rio dos extratos exclu�dos</a></td>
		<td class='descricao'>Relat�rio que mostram os extratos exclu�dos.</td>
	</tr> -->
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_recusada.php' class='menu'>Relat�rio das OSs Recusadas</a></td>
		<td class='descricao'>Relat�rio que mostram a quantidade das OSs recusada do extrato.</td>
	</tr>
	<!--<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_sem_extrato.php' class='menu'>Relat�rio de OS sem extrato</a></td>
		<td class='descricao'>Relat�rio de Ordens de servi�o que n�o entraram em nenhum extrato por algum motivo (ex. os pedidos s�o inferior a R$ 3,00).</td>
	</tr> -->
<?}?>
<?
if($login_fabrica == 45 or $login_fabrica == 5){
?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='relatorio_lancamento_avulso.php' class='menu'>Relat�rio dos Lan�amentos Avulsos</a></td>
	<td class='descricao'>Relat�rio que mostram os lan�amentos avulsos.</td>
</tr>
<?}?>
<?
if($login_fabrica == 1){ // HD 51042
?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='relatorio_defeito_constatado_mo.php' class='menu'>Relat�rio de M�o-de-Obra DEWALT</a></td>
	<td class='descricao'>Relat�rio que mostra a m�o-de-obra por defeito constatado da linha Dewalt.</td>
</tr>
<?}?>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</TABLE>
<p>
<!--      COBRAN�A      -->

<?if($login_fabrica == 3){?>
<BR><TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>NOVO SISTEMA DE EXTRATO</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_posto_britania_novo_processo.php' class='menu'>Confer�ncia de Extratos de POSTOS</a></td>
	<td class='descricao'>Permite visualizar os extratos dos postos e realizar a confer�ncia das OSs.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='sinalizador_os.php' class='menu'>Sinalizador</a></td>
	<td class='descricao'>Gerencia o status e op��es para sinalizar as OSs.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='agrupa_extrato_posto_geral.php' class='menu'>Agrupar extratos</a></td>
	<td class='descricao'>Agrupa todos os extratos conferidos.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='nota_fiscal_pagamento_britania.php' class='menu'>Lan�amento nota fiscal</a></td>
	<td class='descricao'>lan�a dados da nota fiscal emitida pelo posto e dados de pagamento.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_os_conferida_linha_novo.php' class='menu'>Relat�rio de OSs Conferidas</a></td>
	<td class='descricao'>Relat�rio de ordens de servi�o conferidas por linha.</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='relatorio_fechamento_automatico.php' class='menu'>Fechamento Autom�tico de OS</a></td>
	<td class='descricao'>Relat�rio para consulta de OS fechadas automaticamente pelo sistema.</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'/></td>
</tr>
</TABLE>

<? } ?>
<?if($login_fabrica == 3){?>


<BR><TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>COBRAN�A</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='cobranca_busca.php' class='menu'>Cobran�a</a></td>
	<td class='descricao'>lista notas para a cobran�a.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='cobranca_envia_arquivo.php' class='menu'>Incluir arquivo</a></td>
	<td class='descricao'>incluiu o arquivo txt no banco de dados.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='cobranca_debito.php' class='menu'>D�bito detalhado</a></td>
	<td class='descricao'>Gerencia tipos de d�bito.</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'/></td>
</tr>
</TABLE>
<br/><table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
  <td width='10'><img src="imagens/corner_se_laranja.gif"/></TD>
  <TD class=cabecalho>CADASTRO</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='acrescimo_mo_prazo_cadastro.php' class='menu'>Cadastro de m�o-de-obra diferenciada</a></td>
	<td class='descricao'>Cadastro de m�o-de-obra diferenciada por prazo de atendimento.</td>
</tr>
</table>
<? } ?>

<? include "rodape.php" ?>


<!-- ============================================================================================= -->
</body>
</html>
