<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'cabecalho.php';
include 'autentica_usuario.php';

$cook_posto = $_COOKIE['cook_posto'];
$login_posto = $cook_posto ;

if (empty($login_posto)) {
        include_once '../token_cookie.php';

        $token_cookie = $_COOKIE['sess'];
        $cookie_login = get_cookie_login($token_cookie);

        $login_posto = $cookie_login['cook_posto'];
}

$ip_maquina = include ("../nosso_ip.php");

if (strlen ($login_posto) == 0) {
    header ("Location: http://www.telecontrol.com.br");
    exit;
}


# posto 595 retirado do sistema de distribui��o... pedido pelo Augusto ao Ricardo em 07/04/2006
//if ($login_posto <> "4311") {
if (!in_array($login_posto,array("4311", "595", "20321","376542"))) {
    header ("Location: http://www.telecontrol.com.br");
    exit;
}



?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Menu Inicial para DISTRIBUIDORES </TITLE>
<link type="text/css" rel="stylesheet" href="css/css.css">
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

</HEAD>
<style>
#menu tbody tr:nth-child(even) {
  background-color: #efefef;
}


#menu tbody tr:nth-child(odd) {
  background-color: #dddddd;
}
</style>

<BODY vlink='#0000ff'>

<? include 'menu.php'?>


<?
$sql = "SELECT COUNT(*) FROM tbl_embarque_item JOIN tbl_embarque USING (embarque) WHERE tbl_embarque.distribuidor = $login_posto AND tbl_embarque_item.liberado IS NOT NULL AND tbl_embarque_item.impresso IS NULL";
$res = pg_exec ($con,$sql);
$qtde = pg_result ($res,0,0) ;
if ($qtde > 0) {
	$apagar_etq = $_GET['apagar_etq'];
	if ($apagar_etq == "S") {
		$sql = "SELECT DISTINCT tbl_embarque.embarque FROM tbl_embarque_item JOIN tbl_embarque USING (embarque) WHERE tbl_embarque.distribuidor = $login_posto AND tbl_embarque.faturar IS NULL AND tbl_embarque_item.liberado IS NOT NULL AND tbl_embarque_item.impresso IS NULL";
		$res = pg_exec ($con,$sql);

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$embarque = pg_result ($res,$i,0);
			pg_exec ($con,"SELECT fn_etiqueta_ok ($embarque)");
		}
		header("Location: $PHP_SELF");
		exit;

	}else{
		echo "<center><h2>Existem $qtde Etiquetas Liberadas e n�o impressas";
		echo "<br>Se foram impressas corretamente, <a href='$PHP_SELF?apagar_etq=S'>clique aqui</a> para apag�-las</h2></center>";
	}
}

?>



<center>
<h1> Menu Inicial para Distribuidores </h1>

<table width='500' align='center' border='0' cellspacing='1' cellpaddin='1' id='menu'>
<thead>
<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Rotina Di�ria de Embarque / Emiss�o de Faturamento </td>
</tr>
</thead>
<tbody>
<tr>
	<td > <a href='embarque_geral_conferencia_novo.php'> Confer�ncia geral do Embarque</a> </td>
</tr>
<tr>
	<td > <a href='embarque_desembarque.php'> Desembarcar itens faltantes </a> </td>
</tr>
<tr>
	<td > <a href='nf_cadastro_manual.php'> Cadastro / Consulta de Nota Fiscal Manual</a> </td>
</tr>
<tr>
	<td > <a href='embarque_faturamento.php'> Faturamento geral do Embarque </a> </td>
</tr>
<tr>
	<td > <a href='../nfephp2/gerencia_nfe.php'> Integra��o de Pedidos ERP</a> </td>
</tr>
<tr>
	<td > <a href='../nfephp2/gerencia_nfe_reimpressao.php'> Consulta e Reimpress�o de NFE </a> </td>
</tr>
<tr>
	<td > <a href='gerar_txt_etiqueta.php'>Gerar arquivo txt para Etiqueta </a> </td>
</tr>
<tr>
	<td> <a href='embarque_consulta.php'>Consulta de embarque</a> </td>
</tr>
<tr>
	<td> <a href='nf_atendimento.php'>Gerar embarque OS e Pedido </a> </td>
</tr>
<tr>
	<td> <a href='estorno_embarque.php'>Estornar Embarque </a> </td>
</tr>
<tr>
	<td> <a href='rastreio_embarque_cadastro.php'>Manuten��o C�digo Rastreio Embarque</a> </td>
</tr>
<tr>
	<td> <a href='conferencia_recebimento.php'>Confer�ncia Recebimento</a> </td>
</tr>
<tr> 
	<td> <a href='os_faturada_nf_sem_rasteiro.php'>OS's e Pedidos faturados com Nota Fiscal e SEM rastreio</a> </td>
</tr>





</tbody>
</table>
<br/>



<table width='500' align='center' border='0' cellspacing='1' cellpaddin='1'>
<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Tarefas Operacionais </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='posto_consulta.php'> Consulta Endere�o dos Postos </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='estoque_consulta.php'> Consulta Estoque de Pe�as </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='pendencia_posto.php'> Consulta Pend�ncia dos Postos </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='peca_localizacao.php'> Mudar localiza��o de Pe�a </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='cadastro_info_adicional_peca.php'> Cadastro de Informa��es Adicionais da Pe�a </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='estoque_acerto.php'> Acerto de Estoque </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='extrato_conferencia.php'> Conferir Extrato de Postos </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='extrato_conferencia_lote.php'> Extrato de Postos Por LOTE</a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='duplicata_conferencia.php'> Confer�ncia de Duplicatas </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='estoque_contagem.php'> Contagem do Estoque </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='estoque_previsao.php'> Previs�o de Estoque </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='pesquisa_balanco.php'> Pesquisar Balan�os Realizados </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='de_para.php'> Acertos de DE-PARA </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='pedidos_nao_atendidos.php'> Pedidos N�o Atendidos </a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='movimento_pecas.php'> Movimento de Pe�as </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='consulta_pendencia_postos.php'> Pend�ncia com Postos </a> </td>
</tr>
<tr>
    <td bgcolor='#dddddd'><a href='solicitar_etiqueta.php'> Solicitar Etiqueta</a> </td>
</tr>
<tr>
    <td bgcolor='#dddddd'><a href='gerar_etiqueta.php'> Imprimir Etiqueta</a> </td>
</tr>
<tr>
    <td bgcolor='#dddddd'><a href='gerar_pre_lista_postagem.php'> Gerar e Imprimir Pr�-lista de Postagem</a> </td>
</tr>
<!--
<tr>
	<td bgcolor='#efefef'><a href='importar_rastreios_fabio.php'> Importar Rastreamentos (CORREIOS e BRASPRESS)</a> </td>
</tr>
-->
<? if($login_posto=="4311") { ?>
<tr>
	<td bgcolor='#dddddd'><a href='chamados_distrib.php'> Chamados</a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'><a href='devolucao_pecas_lgr.php'> Consulta Devolu��o de Pe�as</a> </td>
</tr>
<tr>
        <td bgcolor='#dddddd'><a href='peca_etiqueta.php'> Selecionar pe�as para gerar etiquetas</a> </td>
</tr>

<? } ?>

<tr>
    <td bgcolor='#dddddd'><a href='cadastro_posicoes_estoque_disponiveis.php'> Cadastro de posi��es de estoque dispon�veis </a> </td>
</tr>

<tr bgcolor='#ffffff'>
	<td>&nbsp;</td>
</tr>






<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Lotes dos Postos </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='lote.php'> Confer�ncia dos lotes de OS dos Postos </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='lote_conferencia.php'> Relat�rio dos Lotes </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='relatorio_os_lote.php'> Relat�rio OS em Lote </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='lote_etq_deposito.php'> Etiquetas de Dep�sito </a> </td>
</tr>




<tr bgcolor='#ffffff'>
	<td>&nbsp;</td>
</tr>





<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Rotina FISCAL - Recebimento/Devolu��o/Pend�ncia </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='cad_fornecedor.php'> Cadastro de Fornecedores </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='nf_cadastro.php'> Cadastrar NF de entrada de produtos </a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='nf_entrada.php'> Confer�ncia de recebimento de NF </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='nf_saida.php'> NF de Devolu��o para F�brica (SA�DA)</a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='devolucao.php'> NF de Devolu��o dos Postos </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='pedido_cadastro_new.php'> Cadastro de Pedido (Loja Virtual) </a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='pedido_relacao.php'> Consulta de Pedido Pendentes </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='nf_divergente.php'> Consulta de Pend�ncias de �tens Divergentes de NF</a> </td>
</tr>
<tr>
    <td bgcolor='#efefef'> <a href='posto_atraso_pagamento.php'> Consulta de Postos com Pagamento Atrasado</a> </td>
</tr>
<tr>
    <td bgcolor='#efefef'> <a href='conferencia_erp_sistema.php'> Confer�ncia ERP</a> </td>
</tr>

<tr bgcolor='#ffffff'>
	<td>&nbsp;</td>
</tr>
</table>




<table width='500' align='center' border='0' cellspacing='1' cellpaddin='1' id='menu'>
<thead>
<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Tarefas Gerenciais </td>
</tr>
</thead>
<tbody>
<tr>
	<td> <a href='pedido_pendente.php'> Pedidos de Pe�as Pendentes </a> </td>
</tr>
<tr>
	<td> <a href='../externos/loja/admin'> Administra��o Marketplace </a> </td>
</tr>
<tr>
	<td> <a href='pedidos_pecas.php'> Pedidos de Pe�as - pe�as mais pedidas </a> </td>
</tr>
<tr>
	<td> <a href='pedido_posto.php'> Fazer pedido de pe�as para os postos</a> </td>
</tr>

<!--
<tr>
	<td> <a href='demanda_reprimida.php'> Demanda Reprimida </a> </td>
</tr>
<!-- HD 7889 Comentado por Fabio
<tr>
	<td> <a href='embarque.php'> Embarcar Pe�as Dispon�veis para Postos </a> </td>
</tr>
-->
<!-- 
<tr>
	<td> <a href='desembarque_geral.php'> Desembarcar pe�as parciais e trocas de produto</a> </td>
</tr>
-->

<tr>
	<td> <a href='embarque_conferencia.php'> Relat�rio de Confer�ncia dos embarques aprovados </a> </td>
</tr>

<!--
<tr>
	<td> <a href='embarque_geral_conferencia.php'> Confer�ncia geral do Embarque </a> </td>
</tr>
-->
<!--
<tr>
	<td> Etiquetas de Endere�o (use programa em ACCESS) </td>
</tr>
-->
<tr>
	<td> <a href='embarque_antigo.php'> Embarcar Pe�as mais Antigas </a> </td>
</tr>


<tr>
	<td> <a href='acima_giro.php'> Pe�as Acima do Giro </a> </td>
</tr>

<tr>
	<td> <a href='caixa.php'> Caixa - Posi��o Financeira </a> </td>
</tr>

<tr>
	<td> <a href='baixa_retorno.php'> Baixa pelo arquivo de retorno do Banco </a> </td>
</tr>

<tr>
	<td> <a href='caixa_devedores.php'> Rela��o de Devedores </a> </td>
</tr>

<tr>
	<td> <a href='baixa_manual.php'> Baixa Manual </a> </td>
</tr>

<tr>
	<td> <a href='lista_os_nao_atendida.php'> Lista de OS's n�o Atendidas</a> </td>
</tr>

<tr>
	<td> <a href='pendencia_fabrica_distrib.php'> Pend�ncia da F�brica ao Distribuidor</a> </td>
</tr>

<tr>
	<td> <a href='contas_pagar.php'> Contas a Pagar</a> </td>
</tr>

<tr>
	<td> <a href='conta_receber.php'> Contas a Receber</a> </td>
</tr>

<tr>
	<td> <a href='compra_manual.php'> Compra Manual </a> </td>
</tr>
<tr>
	<td> <a href='pedido_consulta.php'> Pedido Loja virtual</a> </td>
</tr>
<tr>
	<td> <a href='relatorio_reembolso_frete.php'>Reembolso Frete</a> </td>
</tr>
<tr>
	<td> <a href='peca_entrada_saida.php'>Pe�as com entrada sem sa�das</a> </td>
</tr>


<tr>
	<td> <a href='relatorio_nf_entrada.php'>Relat�rio de NF de Entrada</a> </td>
</tr>

<tr>
	<td> <a href='relatorio_nf_entrada_saida.php'>Relat�rio de NF de Sa�da</a> </td>
</tr>
<tr>
	<td> <a href='relatorio_fechamento.php'>Relat�rio de Fechamento</a> </td>
</tr>
<tr>
	<td> <a href='controle_inventario.php'>Controle Invent�rio</a> </td>
</tr>

</tbody>

</table>

</BODY>
</HTML>
<?
include'rodape.php';
?>
