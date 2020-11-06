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


<?
if ($login_fabrica == 3){
?>

<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>INFORMAÇÕES FINANCEIRAS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='devolucao_cadastro.php' class='menu'>Notas de Devolução</a></td>
	<td nowrap class='descricao'>Consulta as Notas de Devolução</td>
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
  <TD class=cabecalho>MANUTENÇÕES EM EXTRATOS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<?
if($login_fabrica == 8){
?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='os_extrato_pre.php' class='menu'>Pré fechamento de extratos</a></td>
	<td class='descricao'>Pré fechamento de extratos para visualização da quantidade de OS do posto até a data limite e o valor de mão-de-obra.</a>
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
	<td class='descricao'>Fecha o extrato de cada posto, totalizando o que cada um tem a receber de mão-de-obra, suas peças de devolução obrigatória, e demais informações de fechamento.
<? if($login_fabrica == 6){?>
<a href='os_extrato_por_posto.php' class='menu'>Por posto (em teste).</a>
<? } ?>
	</td>
</tr>
<?}?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='extrato_consulta.php' class='menu'>Manutenção de Extratos</a></td>
	<td class='descricao'>Permite retirar ordens de serviços de um extrato, recalcular o extrato, e dar baixa em seu pagamento.</td>
</tr>
<? if($login_fabrica == 20 OR $login_fabrica == 30 ) {?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='extrato_liberado.php' class='menu'>Liberação de Extrato</a></td>
	<td class='descricao'>Libera extratos para aprovação.</td>
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
	<td class='descricao'>Consulta e Manutenção de Extratos Enviados ao Financeiro.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_custo_pecas.php' class='menu'>Custo das Peças</a></td>
	<td class='descricao'>Digitação manual dos custos das peças, quando não for encontrado o último faturamento respectivo.</td>
</tr>
<? } ?>
<? if($login_fabrica==1) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='acumular_extratos.php' class='menu'>Acumular Extratos</a></td>
	<td class='descricao'>Admin informa um valor e sistema acumula os extratos menores que este valor, desde que este extrato não tenha OS fechada a mais de 30 dias</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_pendencia_consulta.php' class='menu'>Pendência Extratos</a></td>
	<td class='descricao'>Consulta e manutenção de pendência de extratos.</td>
</tr>


<? } ?>

<?

if($login_fabrica <> 3){?>
	<tr bgcolor='#fafafa'>
		<td><img src='imagens/marca25.gif'></td>
		<td>
			<a href='extrato_avulso.php' class='menu'>Lançamento avulso / Extratos </a></td>
			<td class='descricao'>Permite gerar um novo lançamento avulso, com isto, um novo extrato também é gerado.</td>
	</tr>
<?}
if($login_fabrica == 3){?>
	<tr bgcolor='#fafafa'>
		<td><img src='imagens/marca25.gif'></td>
		<td>
			<a href='extrato_avulso_britania.php' class='menu'>Lançamento avulso / Extratos </a></td>
			<td class='descricao'>Permite gerar um novo lançamento avulso, com isto, um novo extrato também é gerado.</td>
	</tr>
<?}?>

<?if($login_fabrica == 6 OR $login_fabrica == 59){?>
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='lancamentos_avulsos_cadastro.php' class='menu'>Cadastro Lançamentos Avulsos</a></td>
	<td nowrap class='descricao'>Cadastro dos Lançamentos Avulsos ao Extrato</td>
</tr>
<?}?>
<? if($login_fabrica==11) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='movimentacao_postos_lenoxx.php' class='menu'>Movimentação do Posto Autorizado</a></td>
	<td class='descricao'>Relatório de Movimentação do Posto Autorizado entre períodos.</td>
</tr>
<tr bgcolor='#fAfAfA'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='movimentacao_revenda_lenoxx.php' class='menu'>Movimentação da Revenda</a></td>
	<td class='descricao'>Relatório de Movimentação da Revenda entre períodos.</td>
</tr>
<? } ?>

<? if($login_fabrica==1){ ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td> <a href='aprova_os_troca.php' class='menu'>Aprovação de OS Troca</a></td>
	<td class='descricao'>Manutenção de Ordem de Serviço de Troca.</td>
</tr>
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_excluir.php' class='menu'>Excluir Ordem de Serviço</a></td>
	<td nowrap class='descricao'>Exclua Ordem de Serviços do Posto</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td> <a href='aprova_exclusao.php' class='menu'>Aprovação de OS Excluída</a></td>
	<td class='descricao'>Aprove de Ordem de Serviço Excluída.</td>
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
	<td><a href='extrato_posto_britania.php' class='menu'>Conferência de Extratos de POSTOS</a></td>
	<td class='descricao'>Permite visualizar os extratos dos postos e realizar a conferência das OSs.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_distribuidor.php' class='menu'>Consulta de Extratos de DISTRIBUIDOR</a></td>
	<td class='descricao'>Permite visualizar os extratos dos distribuidores.</td>
</tr>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='manutencao_logistica_reversa.php' class='menu'>Manutenção de Logistica Reversa</a></td>
        <td class='descricao'>Permite apagar e alterar número da nota fiscal de devolução.</td>
</tr>
<? } ?>

<? if ($login_fabrica == 11 OR $login_fabrica == 25 OR $login_fabrica == 43 OR $login_fabrica == 24 OR $login_fabrica == 72 OR $login_fabrica > 80) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'/></td>
	<td><a href='extrato_posto_devolucao_controle.php' class='menu'>Controle de Notas de Devolução</a></td>
	<td class='descricao'>Consulta ou confirme notas fiscais de devolução.</td>
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
	<td class='descricao'>Atualiza o site Telecontrol com a previsão de pagamento de extrato.</td>
</tr>
<? } ?>
<? if (in_array($login_fabrica,array(1,3,7))) { ?>
<tr bgcolor='#fafafa'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='estoque_posto_movimento.php' class='menu'>Movimentação Estoque</a></td>
        <td class='descricao'>Visualização da movimentação do estoque do posto autorizado.</td>
</tr>
<? } ?>

<? if ($login_fabrica == 3) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_extrato_detalhe.php' class='menu'>Relatório Extratos de POSTOS</a></td>
        <td class='descricao'>Relatório para visualizar detalhe dos extratos dos postos.</td>
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
	<td class='descricao'>Permite consultar o número de circular interna em pdf dos extratos liberados.</td>
</tr>
<? } ?>


<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>




<!-- RELATÓRIOS DE EXTRATOS E PAGAMENTOS -->
<BR><TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
  <td width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <td class=cabecalho>RELATÓRIOS</TD>
  <td width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</tr>
</table>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>

<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_ressarcimento.php' class='menu'>Baixar Ressarcimento</a></td>
	<td class='descricao'>Baixar Ressarcimento de Ordem de Serviço</td>
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
	<td class='descricao'>Relatório dos extratos baixados.</td>
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
	<td><a href='posto_dados_pagamento.php' class='menu'>Dados Bancários para Pagamento</a></td>
	<td class='descricao'>Todas as informações bancárias para pagamentos dos postos autorizados</td>
</tr>

<?if($login_fabrica == 3){ // HD 64220 - Etiquetas com o endereço dos postos, Britânia ?>
	<TR bgcolor='#fafafa'>
		<TD><img src='imagens/marca25.gif'/></TD>
		<TD><A href='etiqueta_posto.php' class='menu'>Etiquetas de endereço</A></TD>
		<TD class='descricao'>Imprime etiquetas com o endere&ccedil;o dos postos selecionados.</TD>
	</TR>

<?}?>

<?
if($login_fabrica == 20){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_custo_tempo.php' class='menu'>Custo Tempo de Extratos</a></td>
	<td class='descricao'>Neste relatório contém as OS e seus respectivos Custo Tempo por um determinado período</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_aprovado.php' class='menu'>Tempo de Análise de Extratos</a></td>
	<td class='descricao'>Esse relatório informa a quantidade de tempo para análise do extrato</td>
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
	<td class='descricao'>Relatório de OS's e seus produtos e valor pagos por período.</td>
</tr>
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
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_field_call_rate_familia_custo.php' class='menu'>Field Call Família de Produto X Custo</a></td>
	<td class='descricao'>Relatório de Field Call Rate de Família e valor pagos por período.</td>
</tr>
<? } ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='posto_extrato_ano.php' class='menu'>Comparativo Anual de média de extrato</a></td>
	<td class='descricao'>Valor mensal dos extratos do posto num período de 12 meses.</td>
</tr>
<? if ($login_fabrica == 20) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_semestral_bosch.php' class='menu'>Controle de Garantia Semestral</a></td>
	<td class='descricao'>Relatório semestral com: total de OSs, total de peças, total de mão de obra, total pago e média por os,</td>
</tr>
<? } ?>
<? # HD 24472 - Francisco Ambrozio (4/8/08) - Incluído link Relatório OS
   #   Conferidas por Linha - Britania.
   if ($login_fabrica == 3) { ?>
		<tr bgcolor='#f0f0f0'>
				<td><img src='imagens/marca25.gif'></td>
				<td><a href='relatorio_os_conferida_linha.php' class='menu'>Relatório de OSs Conferidas</a></td>
				<td class='descricao'>Relatório de ordens de serviço conferidas por linha.</td>
		</tr>
<? } ?>
<? if ($login_fabrica==11) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_field_call_rate_gastos_postos.php' class='menu'>Relatório de Mão-de-obra</a></td>
        <td class='descricao'>Relatório de pagamento de mão-de-obra por posto, período e produto.</td>
</tr>
<? } ?>
<?
/*hd: 91609*/
if ($login_fabrica==30) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_detalhado_esmaltec.php' class='menu'>Relatório de Extrato Detalhado</a></td>
	<td class='descricao'>Valor dos extratos com filtro de família e como resultado os detalhes de valor de mão de obra, peças e km.</td>
</tr>
<? }?>

<? if ($login_fabrica==2) { ?>
<!--<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='extrato_posto_devolucao_controle.php' class='menu'>Controle de Notas de Devolução</a></td>
        <td class='descricao'><h1 style='display:inline'>EM TESTE</h1> Consulta ou confirme notas fiscais de devolução.</td>
</tr>
-->
<? } ?>
<? if ($login_fabrica==24) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_pgto_mo.php' class='menu'>Relatório de Mão-de-obra</a></td>
        <td class='descricao'>Relatório de pagamento de mão-de-obra por posto, período e produto.</td>
</tr>
<? } ?>
<? if ($login_fabrica==5) { ?>
<tr bgcolor='#f0f0f0'>
        <td><img src='imagens/marca25.gif'></td>
        <td><a href='relatorio_mobra_relacao.php' class='menu'>Relatório Custo x Posto</a></td>
        <td class='descricao'>Relatório do total de produto e mão-de-obra pagos por posto nas relações ME, MK, ML/MC.</td>
</tr>
<? } ?>

<? if ($login_fabrica == 11 ) { ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_parametros_finalizada.php' class='menu'>Relatório OS Finalizada + Mão-de-Obra</a></td>
	<td class='descricao'>Relatório Ordens de Serviço finalizadas com mão-de-obra e peças aplicadas</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='extrato_peca_retorno_obrigatorio.php' class='menu'>Relatório Devolução Obrigatória</a></td>
	<td class='descricao'>Relatório de Peças de Retorno Obrigatório</td>
</tr>
<? } ?>
<? if ($login_fabrica == 1 ) { ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='extrato_documento_consulta.php' class='menu'>Relatório de Pendência de Documento</a></td>
	<td class='descricao'>Relatório de Todas as Pendências Lançadas nos Extratos.</td>
</tr>
<? } ?>
<?
if($login_fabrica == 14){
?>
	<!--<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='extrato_excluido.php' class='menu'>Relatório dos extratos excluídos</a></td>
		<td class='descricao'>Relatório que mostram os extratos excluídos.</td>
	</tr> -->
	<tr bgcolor='#FAFAFA'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_recusada.php' class='menu'>Relatório das OSs Recusadas</a></td>
		<td class='descricao'>Relatório que mostram a quantidade das OSs recusada do extrato.</td>
	</tr>
	<!--<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/tela25.gif'></td>
		<td nowrap width='260'><a href='relatorio_os_sem_extrato.php' class='menu'>Relatório de OS sem extrato</a></td>
		<td class='descricao'>Relatório de Ordens de serviço que não entraram em nenhum extrato por algum motivo (ex. os pedidos são inferior a R$ 3,00).</td>
	</tr> -->
<?}?>
<?
if($login_fabrica == 45 or $login_fabrica == 5){
?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='relatorio_lancamento_avulso.php' class='menu'>Relatório dos Lançamentos Avulsos</a></td>
	<td class='descricao'>Relatório que mostram os lançamentos avulsos.</td>
</tr>
<?}?>
<?
if($login_fabrica == 1){ // HD 51042
?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='relatorio_defeito_constatado_mo.php' class='menu'>Relatório de Mão-de-Obra DEWALT</a></td>
	<td class='descricao'>Relatório que mostra a mão-de-obra por defeito constatado da linha Dewalt.</td>
</tr>
<?}?>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</TABLE>
<p>
<!--      COBRANÇA      -->

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
	<td><a href='extrato_posto_britania_novo_processo.php' class='menu'>Conferência de Extratos de POSTOS</a></td>
	<td class='descricao'>Permite visualizar os extratos dos postos e realizar a conferência das OSs.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='sinalizador_os.php' class='menu'>Sinalizador</a></td>
	<td class='descricao'>Gerencia o status e opções para sinalizar as OSs.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='agrupa_extrato_posto_geral.php' class='menu'>Agrupar extratos</a></td>
	<td class='descricao'>Agrupa todos os extratos conferidos.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='nota_fiscal_pagamento_britania.php' class='menu'>Lançamento nota fiscal</a></td>
	<td class='descricao'>lança dados da nota fiscal emitida pelo posto e dados de pagamento.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_os_conferida_linha_novo.php' class='menu'>Relatório de OSs Conferidas</a></td>
	<td class='descricao'>Relatório de ordens de serviço conferidas por linha.</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='relatorio_fechamento_automatico.php' class='menu'>Fechamento Automático de OS</a></td>
	<td class='descricao'>Relatório para consulta de OS fechadas automaticamente pelo sistema.</td>
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
  <TD class=cabecalho>COBRANÇA</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='cobranca_busca.php' class='menu'>Cobrança</a></td>
	<td class='descricao'>lista notas para a cobrança.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='cobranca_envia_arquivo.php' class='menu'>Incluir arquivo</a></td>
	<td class='descricao'>incluiu o arquivo txt no banco de dados.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='cobranca_debito.php' class='menu'>Débito detalhado</a></td>
	<td class='descricao'>Gerencia tipos de débito.</td>
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
	<td><a href='acrescimo_mo_prazo_cadastro.php' class='menu'>Cadastro de mão-de-obra diferenciada</a></td>
	<td class='descricao'>Cadastro de mão-de-obra diferenciada por prazo de atendimento.</td>
</tr>
</table>
<? } ?>

<? include "rodape.php" ?>


<!-- ============================================================================================= -->
</body>
</html>
