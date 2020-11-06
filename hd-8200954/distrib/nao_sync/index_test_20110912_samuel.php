<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'cabecalho.php';
include 'autentica_usuario.php';

$cook_posto = $_COOKIE['cook_posto'];
$login_posto = $cook_posto ;

$ip_maquina = include_once("../nosso_ip_test.php");

//Tulio pediu para tirar 18/12/2008 - Valeria utiliza fora da empresa.
//if ($ip!=trim($ip_maquina)  ){
//	header("Location: http://www.telecontrol.com.br");
//}


if (strlen ($login_posto) == 0) {
    header ("Location: http://www.telecontrol.com.br");
    exit;
}


# posto 595 retirado do sistema de distribuição... pedido pelo Augusto ao Ricardo em 07/04/2006
//if ($login_posto <> "4311") {
if ($login_posto <> "4311" and $login_posto <> "595" and $login_posto <> "20321") {
    header ("Location: http://www.telecontrol.com.br");
    exit;
}

#echo "posto - $cook_posto <p>";
#print_r($_COOKIE);
#echo "<p>";


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
		echo "<center><h2>Existem $qtde Etiquetas Liberadas e não impressas";
		echo "<br>Se foram impressas corretamente, <a href='$PHP_SELF?apagar_etq=S'>clique aqui</a> para apagá-las</h2></center>";
	}
}

?>



<center>
<h1> Menu Inicial para Distribuidores </h1>

<table width='500' align='center' border='0' cellspacing='1' cellpaddin='1'>
<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Tarefas Operacionais </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='posto_consulta.php'> Consulta Endereço dos Postos </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='estoque_consulta.php'> Consulta Estoque de Peças </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='pendencia_posto.php'> Consulta Pendência dos Postos </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='peca_localizacao.php'> Mudar localização de Peça </a> </td>
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
	<td bgcolor='#dddddd'> <a href='duplicata_conferencia.php'> Conferência de Duplicatas </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='estoque_contagem.php'> Contagem do Estoque </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='estoque_previsao.php'> Previsão de Estoque </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='de_para.php'> Acertos de DE-PARA </a> </td>
</tr>


<tr>
	<td bgcolor='#dddddd'> <a href='pedidos_nao_atendidos.php'> Pedidos Não Atendidos </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='movimento_pecas.php'> Movimento de Peças </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='consulta_pendencia_postos.php'> Pendência com Postos </a> </td>
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
	<td bgcolor='#efefef'><a href='devolucao_pecas_lgr.php'> Consulta Devolução de Peças</a> </td>
</tr>
<? } ?>

<tr bgcolor='#ffffff'>
	<td>&nbsp;</td>
</tr>






<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Lotes dos Postos </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='lote.php'> Conferência dos lotes de OS dos Postos </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='lote_conferencia.php'> Relatório dos Lotes </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='lote_etq_deposito.php'> Etiquetas de Depósito </a> </td>
</tr>




<tr bgcolor='#ffffff'>
	<td>&nbsp;</td>
</tr>





<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Rotinas FISCAL - Recebimento/Devolução </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='cad_fornecedor.php'> Cadastro de Fornecedores </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='nf_cadastro_manual.php'> Cadastro Manual de Notas Fiscais </a> </td>
</tr>


<tr>
	<td bgcolor='#efefef'> <a href='nf_cadastro.php'> Cadastrar NF de entrada de produtos </a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='nf_entrada.php'> Conferência de recebimento de NF </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='nf_saida.php'> NF de Devolução para Fábrica (SAÍDA)</a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='devolucao.php'> NF de Devolução dos Postos </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='pedido_cadastro_new.php'> Cadastro de Pedido (Loja Virtual) </a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='pedido_relacao.php'> Consulta de Pedido Pendentes </a> </td>
</tr>

<tr bgcolor='#ffffff'>
	<td>&nbsp;</td>
</tr>





<tr bgcolor='#0099CC' align='center' style='color:#ffffff ; font-weight:bold ; font-size:16px'>
	<td> Tarefas Gerenciais </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='pedido_pendente.php'> Pedidos de Peças Pendentes </a> </td>
</tr>
<tr>
	<td bgcolor='#dddddd'> <a href='pedido_posto.php'> Fazer pedido de peças para os postos</a> </td>
</tr>

<!--
<tr>
	<td bgcolor='#efefef'> <a href='demanda_reprimida.php'> Demanda Reprimida </a> </td>
</tr>
<!-- HD 7889 Comentado por Fabio
<tr>
	<td bgcolor='#efefef'> <a href='embarque.php'> Embarcar Peças Disponíveis para Postos </a> </td>
</tr>
-->
<!-- 
<tr>
	<td bgcolor='#FFFFCC'> <a href='desembarque_geral.php'> Desembarcar peças parciais e trocas de produto</a> </td>
</tr>
-->

<tr>
	<td bgcolor='#efefef'> <a href='embarque_conferencia.php'> Relatório de Conferência dos embarques aprovados </a> </td>
</tr>

<!--
<tr>
	<td bgcolor='#efefef'> <a href='embarque_geral_conferencia.php'> Conferência geral do Embarque </a> </td>
</tr>
-->
<tr>
	<td bgcolor='#efefef'> <a href='embarque_geral_conferencia_novo.php'> Conferência geral do Embarque</a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='embarque_desembarque.php'> Desembarcar itens faltantes </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='embarque_faturamento.php'> Faturamento geral do Embarque </a> </td>
</tr>

<!--
<tr>
	<td bgcolor='#dddddd'> Etiquetas de Endereço (use programa em ACCESS) </td>
</tr>
-->
<tr>
	<td bgcolor='#efefef'> <a href='embarque_antigo.php'> Embarcar Peças mais Antigas </a> </td>
</tr>


<tr>
	<td bgcolor='#efefef'> <a href='acima_giro.php'> Peças Acima do Giro </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='caixa.php'> Caixa - Posição Financeira </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='baixa_retorno.php'> Baixa pelo arquivo de retorno do Banco </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='caixa_devedores.php'> Relação de Devedores </a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='baixa_manual.php'> Baixa Manual </a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='lista_os_nao_atendida.php'> Lista de OS's não Atendidas</a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='pendencia_fabrica_distrib.php'> Pendência da Fábrica ao Distribuidor</a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='contas_pagar.php'> Contas a Pagar</a> </td>
</tr>

<tr>
	<td bgcolor='#dddddd'> <a href='conta_receber.php'> Contas a Receber</a> </td>
</tr>

<tr>
	<td bgcolor='#efefef'> <a href='compra_manual.php'> Compra Manual </a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='pedido_consulta.php'> Pedido Loja virtual</a> </td>
</tr>
<tr>
	<td bgcolor='#efefef'> <a href='relatorio_reembolso_frete.php'>Reembolso Frete</a> </td>
</tr>

</table>

</BODY>
</HTML>
<?
include'rodape.php';
?>
