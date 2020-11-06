<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
include 'autentica_admin.php';

include 'funcoes.php';

$title = "Cadastros do Sistema";
$layout_menu = "cadastro";
include 'cabecalho.php';

echo $login_master;
?>


<body>

<img src='http://chart.apis.google.com/chart?
cht=p3
&amp;chd=t:20,70,10
&amp;chs=250x100
&amp;chl=Hello|Worldadf|zxczxc'
alt='Exemplo de Gráfico'>


<hr>


<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">  
	google.load("language", "1");

	function initialize() {
		var text = document.getElementById("text").innerHTML;      
		google.language.detect(text, function(result) {
			if (!result.error && result.language) {          
				google.language.translate(text, result.language, "pt", function(result) {            
					var translated = document.getElementById("translation");            
					if (result.translation) {              
						translated.innerHTML = result.translation;            
					}          
				});        
			}      
		});    
	}    

	function traduzir(texto_original,idioma_destino) {
		var texto_traduzido ;
		google.language.detect(texto_original, function(result) {
			if (!result.error && result.language) {
				google.language.translate(texto_original, result.language, idioma_destino, function(result) {
					if (result.translation) {
						texto_traduzido = result.translation;
					}else{
						texto_traduzido = texto_original;
					}
				});        
			}
		});
	}    
	
	google.setOnLoadCallback(initialize);


</script>


<? include 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=hello%20world&langpair=en%7Cfr'
?>




<?

echo "<script language='javascript'>document.write (\"teste\") ; document.write (traduzir('testando sistema de tradução','fr'));</script>";

?>

<div id="text">Die Firma Telecontrol übernimmt Vertrieb, Lagerung und Bestandsverwaltung von Ersatzteilen für den Technischen Kundendienst.</div>    
<div id="translation"></div>
	
<hr>


<?
#------------ Validações BRITANIA ------------
if ($login_fabrica == 3 OR $login_fabrica == 6 ) {
	#----------------- Produtos sem Familia ---------------
	$sql = "SELECT produto, referencia, descricao FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.familia IS NULL AND tbl_produto.ativo IS NOT FALSE ";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		echo "<center><font style='12px' color='#882200'>Produtos sem Família</font></center>";
		echo "<table align='center' cellspacing='1'>";
		echo "<tr bgcolor='#882200' style='font-color:#ffffff ; font-weight:bold ; font-size:12px' >";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Descrição</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			echo "<tr style='font-color:#000000 ; font-size:10px'>";

			echo "<td align='left'>";
			echo "<a href='produto_cadastro.php?produto=" . pg_result ($res,$i,produto) . "'>";
			echo pg_result ($res,$i,referencia);
			echo "</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}

	#----------------- Produtos sem Mão-de-Obra ---------------
	$sql = "SELECT produto, referencia, descricao FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND (tbl_produto.mao_de_obra IS NULL OR tbl_produto.mao_de_obra = 0) AND tbl_produto.ativo IS NOT FALSE  ";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		echo "<center><font style='12px' color='#882200'>Produtos sem Mão-de-Obra</font></center>";
		echo "<table align='center' cellspacing='1'>";
		echo "<tr bgcolor='#882200' style='font-color:#ffffff ; font-weight:bold ; font-size:12px' >";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Descrição</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			echo "<tr style='font-color:#000000 ; font-size:10px'>";

			echo "<td align='left'>";
			echo "<a href='produto_cadastro.php?produto=" . pg_result ($res,$i,produto) . "'>";
			echo pg_result ($res,$i,referencia);
			echo "</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
}
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



<script type="text/javascript">
	google.load("visualization", "1", {packages:["gauge"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Label');
		data.addColumn('number', 'Value');
		data.addRows(1);
		data.setValue(0, 0, 'Análise');
		data.setValue(0, 1, 5);
		var chart = new google.visualization.Gauge(document.getElementById('chart_div1'));
		var options = {width: 400, height: 120, greenFrom: 0 , greenTo: 3 , yellowFrom: 3, yellowTo: 6, redFrom: 6, redTo: 10, minorTicks: 1, max: 10};
		chart.draw(data, options);
	}
</script>

<script type="text/javascript">
	google.load("visualization", "1", {packages:["gauge"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Label');
		data.addColumn('number', 'Value');
		data.addRows(1);
		data.setValue(0, 0, 'Peça');
		data.setValue(0, 1, 8);
		var chart = new google.visualization.Gauge(document.getElementById('chart_div2'));
		var options = {width: 400, height: 120, greenFrom: 0 , greenTo: 3 , yellowFrom: 3, yellowTo: 6, redFrom: 6, redTo: 10, minorTicks: 1, max: 10};
		chart.draw(data, options);
	}
</script>

<script type="text/javascript">
	google.load("visualization", "1", {packages:["gauge"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Label');
		data.addColumn('number', 'Value');
		data.addRows(1);
		data.setValue(0, 0, 'Conserto');
		data.setValue(0, 1, 8);
		var chart = new google.visualization.Gauge(document.getElementById('chart_div3'));
		var options = {width: 400, height: 120, greenFrom: 0 , greenTo: 4 , yellowFrom: 4, yellowTo: 8, redFrom: 8, redTo: 15, minorTicks: 1, max: 15};
		chart.draw(data, options);
	}
</script>


<script type="text/javascript">
	google.load("visualization", "1", {packages:["gauge"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Label');
		data.addColumn('number', 'Value');
		data.addRows(1);
		data.setValue(0, 0, 'Total');
		data.setValue(0, 1, 25);
		var chart = new google.visualization.Gauge(document.getElementById('chart_div4'));
		var options = {width: 400, height: 120, greenFrom: 0 , greenTo: 8 , yellowFrom: 8, yellowTo: 20, redFrom: 20, redTo: 30, minorTicks: 1, max: 30};
		chart.draw(data, options);
	}
</script>


<table align='center' border="0">
<tr>
	<td colspan="4" align="center">
		Posição das Ordens de Serviço em aberto
	</td>
</tr>
<tr height='120'>
	<td id='chart_div1' title="OS aguardando análise"></td>
	<td id='chart_div2' title="OS aguardando a fábrica faturar a peça"></td>
	<td id='chart_div3' title="Peça enviada. Aguardando conserto"></td>
	<td id='chart_div4' title="Tempo total da OS em aberto"></td>
</tr>
</table>



<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CADASTROS REFERENTES A PRODUTOS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<? if ($login_fabrica == 3) { ?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='marca_cadastro.php' class='menu'>Marca de Produtos</a></td>
	<td nowrap class='descricao'>Consulta - Inclusão - Exclusão de Marcas.</td>
</tr>
<? } ?>

<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='tipo_posto_cadastro.php' class='menu'>Tipo de Postos</a></td>
	<td nowrap class='descricao'>Consulta - Inclusão - Exclusão dos Tipos de Postos.</td>
</tr>

<!-- ================================================================== -->

<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='linha_cadastro.php' class='menu'>Linhas de Produtos</a></td>
	<td nowrap class='descricao'>Consulta - Inclusão - Exclusão de Linha de Produtos.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='familia_cadastro.php' class='menu'>Família de Produtos</a></td>
	<td class='descricao'>Consulta - Inclusão - Exclusão de Família de Produtos.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='produto_cadastro.php' class='menu'>Cadastro de Produtos</a></td>
	<td class='descricao'>Consulta - Inclusão - Exclusão de Produtos.</td>
</tr>
<!-- ================================================================== -->
<? //hd 19043
	if ($login_fabrica <> 50 ) { ?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='subproduto_cadastro.php' class='menu'>Cadastro de Sub-Produtos</a></td>
			<td class='descricao'>Consulta - Inclusão - Exclusão de Sub-Produtos.</td>
		</tr>
<?	} ?>

<? //hd 20300
	if ($login_fabrica ==11 ) { ?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='transportadora_cadastro.php' class='menu'>Cadastro de Transportadora</a></td>
			<td class='descricao'>Consulta - Inclusão - Exclusão de Transportadoras.</td>
		</tr>
<?	} ?>

<? if ($login_fabrica==14) { ?>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='produto_consulta_detalhe.php' class='menu'>Estrutura do produto</a></td>
		<td class='descricao'>Consulta dados da estrutura do produto (Produto > Subconjunto > Peças).</td>
	</tr>
<? } ?>
<? if ($login_fabrica==5) {?>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='serie_controle_cadastro.php' class='menu'>Cadastro de Números de Série</a></td>
		<td class='descricao'>Consulta - Inclusão - Exclusão de Número de Série e quantidade produzida por produto.</td>
	</tr>
<? } ?>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<?if($login_fabrica == 10) echo "<center><a href='loja_virtual_adm.php'> Administrador da Loja Virtual</a></center>";?>

<!-- ========================================================================= -->
<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CADASTROS REFERENTES A PEDIDOS DE PEÇAS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='peca_cadastro.php' class='menu'>Cadastro de Peças</a></td>
	<td class='descricao'>Consulta - Inclusão - Exclusão de Componentes utilizados pela fábrica.</td>
</tr>
<!-- ================================================================== -->
<?
//PARA BLACK - ADICIONADO DIA 30-03-2007 IGOR - HD:1666
if($login_fabrica == "24"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_amarracao.php' class='menu'>Amarração de Peças</a></td>
	<td class='descricao'>Ferramenta de amarração de peças. Quando lançar uma peça é obrigado a lançar a peça amarrada.</td>
</tr>
<?}
if($login_fabrica == "6"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_amarracao_lista.php' class='menu'>Lista Peça X Peça</a></td>
	<td class='descricao'>Cadastro e exclusão de peça e subpeça da lista básica.</td>
</tr>
<?}?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='lbm_cadastro.php' class='menu'>Lista Básica</a></td>
	<td class='descricao'>Estrutura de peças aplicadas a cada produto</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='condicao_cadastro.php' class='menu'>Condições de Pagamento</a></td>
	<td class='descricao'>Cadastramento de condições de pagamentos para pedidos de peças</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='tipo_posto_condicao_cadastro.php' class='menu'>Condições de Pagamento para Postos</a></td>
	<td class='descricao'>Cadastramento de condições de pagamentos para pedidos de peças específica para postos</td>
</tr>
<!-- ================================================================== -->
<?
//PARA BLACK - ADICIONADO DIA 30-03-2007 IGOR - HD:1666
if($login_fabrica == "1"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='condicao_pagamento_manutencao.php' class='menu'>Alteração de Condições de Pagamento</a></td>
	<td class='descricao'>Alteração  de condições de pagamentos dos postos</td>
</tr>
<?
}	
?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='preco_cadastro.php' class='menu'>Preços de Peças</a></td>
	<td class='descricao'>Cadastramento e alteração em preços de peças.</td>
</tr>
<?
//HD 17541
if($login_fabrica == "1"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='preco_upload.php' class='menu'>Atualização de Preços de Acessórios</a></td>
	<td class='descricao'>Atualiza preço de peça Acessórios para pedido Acessório e Loja Virtual.</td>
</tr>
<!-- ================================================================== -->
<?}if ($login_fabrica == 3) {?>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='fator_multiplicacao.php' class='menu'>Preços Sugeridos</a></td>
		<td class='descricao'>Cadastro de preços sugeridos para que o PA se baseie para vender ao consumidor.</td>
	</tr>
<?}?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='tipo_pedido.php' class='menu'>Tipo do Pedido</a></td>
	<td class='descricao'>Cadastro de Tipo de Pedidos</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='depara_cadastro.php' class='menu'>De -> Para</a></td>
	<td class='descricao'>Cadastro de peças DE-PARA (alteração em códigos de peças).</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_alternativa_cadastro.php' class='menu'>Peças Alternativas</a></td>
	<td class='descricao'>Cadastro de peças ALTERNATIVAS.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_fora_linha_cadastro.php' class='menu'>Peças Fora de Linha</a></td>
	<td class='descricao'>Cadastro de peças FORA DE LINHA</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_analise_cadastro.php' class='menu'>Peças em Análise</a></td>
	<td class='descricao'>Cadastro de peças em ANÁLISE</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_acerto.php' class='menu'>Acerto de Peças</a></td>
	<td class='descricao'>Lista todas as peças e seus dados para acerto comum.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='produto_acerto_linha.php' class='menu'>Acerto de Produtos</a></td>
	<td class='descricao'>Lista todos os produtos e seus dados para acerto comum.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_previsao_entrega.php' class='menu'>Previsão de Entrega de Peças</a></td>
	<td class='descricao'>Cadastra a previsão de entrega de peças com abastecimento crítico. Os postos serão informados da previsão, e pode-se consultar as pendências destas peças para tomada de providências.</td>
</tr>
<!-- ================================================================== -->
<? if ($login_fabrica <> 6 and $login_fabrica <> 24 and $login_fabrica <> 50) { ?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
<!--<td nowrap width='260'><a href='peca_represada_cadastro.php' class='menu'>Peças Represadas</a></td>-->
	<td nowrap width='260'><a href='peca_represada_cadastro.php' class='menu'>Peças Utilizadas do Estoque do Distribuidor</a></td>
	<td class='descricao'>Cadastro de Peças que o distribuidor não vai mais receber automaticamente. As peças irão gerar crédito. A finalidade deste processo é permitir que o distribuidor possa abaixar o estoque de determinadas peças.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 5) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td><a href='producao_cadastro.php' class='menu'>Cadastro de Itens de Produção</a></td>
	<td class='descricao'>Cadastro de itens produzidos.</td>
</tr>
<? } ?>
<!-- ================================================================== -->


<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>


<? if ($login_fabrica == 1) { ?>
<br>
<table width="700" border="0" cellspacing="0" cellpadding="0" bgcolor="#D9E2EF" align = 'center'>
	<tr>
		<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
		<td class="cabecalho">LOCAÇÃO</td>
		<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
	</tr>
</table>
<table width="700" border="0" cellpadding="0" cellspacing="0" align = 'center'>
	<tr bgcolor="#F0F0F0">
		<td width="25"><img border="0" src="imagens/pasta25.gif"></td>
		<td nowrap width="260"><a href="os_cadastro_locacao.php" class="menu">Cadastro de Produtos Locação</a></td>
		<td nowrap class="descricao">Produtos liberados para Locação</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width="25"><img border="0" src="imagens/pasta25.gif"></td>
		<td nowrap width="260"><a href="pedido_consulta_locacao.php" class="menu">Consulta de Produtos Locação</a></td>
		<td nowrap class="descricao">Consulta Produtos liberados para Locação</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="3"><img border="0" src="imagens/spacer.gif" height="3"></td>
	</tr>
</table>
<? } ?>


<!-- ========================================================================= -->
<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CADASTROS DE DEFEITOS - EXCEÇÕES</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<? if($login_fabrica <> 51){?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='defeito_reclamado_cadastro.php' class='menu'>Defeitos Reclamados</a></td>
	<td nowrap class='descricao'>Tipos de defeitos reclamados pelo CLIENTE</td>
</tr>
<?}?>
<?if ($login_fabrica==25){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_reclamado_cadastro_callcenter.php' class='menu'>Defeitos Reclamados Call Center</a></td>
	<td class='descricao'>Cadastro de defeitos reclamados no CallCenter</td>
</tr>
<?}?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_constatado_cadastro.php' class='menu'>Defeitos Constatados</a></td>
	<td class='descricao'>Tipos de defeitos constatados pelo TÉCNICO</td>
</tr>
<!-- ================================================================== -->
<? //chamado 2977
if (1==2 and $login_fabrica<>24){?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='causa_defeito_cadastro.php' class='menu'>Causa de Defeitos</a></td>
	<td class='descricao'>Causas de defeitos constatados pelo TÉCNICO</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='excecao_cadastro.php' class='menu'>Exceção de mão-de-obra</a></td>
	<td class='descricao'>Cadastro das exceções de mão-de-obra</td>
</tr>
<?if($login_fabrica==45){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='extrato_lancamento_mensal.php' class='menu'>Valor fixo mensal para postos</a></td>
	<td class='descricao'>Cadastro de valores que serão incluídos todos os meses ao extrato</td>
</tr>
<?}?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='servico_realizado_cadastro.php' class='menu'><? if ($login_fabrica==20){ echo "Cadastro de Identificação";}else {echo"Serviços Realizados";}?></a></td>
	<td class='descricao'><? if ($login_fabrica==20){ echo "Cadastro de Identificação, terceiro código de falha";}else {echo"Cadastro de serviços realizados";}?></td>
</tr>

<!-- ================================================================== -->
<? //chamado 2977
if ($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==5 OR $login_fabrica==8 OR $login_fabrica==10 OR $login_fabrica==14 OR $login_fabrica==16 OR $login_fabrica==20) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_causa_defeito_cadastro.php' class='menu'>Defeitos x Causa do Defeito</a></td>
	<td class='descricao'>Cadastro da relação entre os defeitos e suas causas possíveis</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? //chamado 2977
if ($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==5 OR $login_fabrica==8 OR $login_fabrica==10 OR $login_fabrica==14 OR $login_fabrica==16 OR $login_fabrica==20) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_reclamado_defeito_constatado.php' class='menu'>Defeito Constatado x Reclamado</a></td>
	<td class='descricao'>Cadastro da relação entre os defeitos reclamados e seus possíveis defeitos constatados</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_cadastro.php' class='menu'>Defeito em Peças</a></td>
	<td class='descricao'>Cadastro de defeitos que podem ocorrer nas peças</td>
</tr>
<!-- ================================================================== -->
<?
/*if (($login_fabrica == 6) or ($login_fabrica == 11) or ($login_fabrica == 15) or ($login_fabrica == 3) or ($login_fabrica == 24) or ($login_fabrica == 3) or ($login_fabrica == 5) or ($login_fabrica == 2) or ($login_fabrica == 29) or ($login_fabrica == 30) or ($login_fabrica == 31 or ($login_fabrica == 32) or ($login_fabrica == 33) or ($login_fabrica == 34) or ($login_fabrica == 35) or ($login_fabrica == 36) or ($login_fabrica == 37) or ($login_fabrica == 8))){*/
if($login_fabrica<>14 and $login_fabrica<>2 and $login_fabrica<>19 and $login_fabrica<>20){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='solucao_cadastro.php' class='menu'>Solução</a></td>
	<td class='descricao'>Cadastro de Solução de um defeito</td>
</tr>
<?}
/*	if (($login_fabrica == 6) or ($login_fabrica == 11) or ($login_fabrica == 15) or ($login_fabrica == 3) or ($login_fabrica == 24) or ($login_fabrica == 1) or ($login_fabrica == 3) or ($login_fabrica == 2) or ($login_fabrica == 5) or ($login_fabrica == 26) or ($login_fabrica == 25) or ($login_fabrica == 29) or ($login_fabrica == 30) or ($login_fabrica == 31) or ($login_fabrica == 32) or ($login_fabrica == 33) or ($login_fabrica == 34) or ($login_fabrica == 35) or ($login_fabrica == 36) or ($login_fabrica == 37) or ($login_fabrica == 8)){*/
if($login_fabrica<>20){
?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><? if($login_fabrica == 2 AND $login_fabrica == 15 AND $login_fabrica == 28 AND $login_fabrica == 35 AND $login_fabrica == 45 AND $login_fabrica == 47 AND $login_fabrica == 30 AND $login_fabrica == 46 OR $login_fabrica > 49) {?>
		<a href='relacionamento_diagnostico_new.php' class='menu'>
	<? }else{ ?> 
		<a href='relacionamento_diagnostico.php' class='menu'>
	<?}?>
		Relacionamento de Integridade</a></td>
	<td class='descricao'>Relacionamento de Linha, Familia, Defeito Reclamado, Defeito Constatado e Solução   para o Diagnóstico</td>
</tr>
<?
}
if($login_fabrica == 15){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='os_acerto_defeito.php' class='menu'>
		Acertos de OS´s cadastradas</a></td>
	<td class='descricao'>Acerto dos cadastro dos defeitos das OS´s.</td>
</tr>
<?}
if($login_fabrica==24){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_integridade.php' class='menu'>Integridade de Peças</a></td>
	<td class='descricao'>Cadastro de integridade de peças</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='acao_corretiva_cadastro.php' class='menu'>Ação Corretiva</a></td>
	<td class='descricao'>Cadastro de correções efetuadas em produtos.</td>
</tr>

<? } ?>
<?if($login_fabrica == 20){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='produto_custo_tempo_cadastro.php' class='menu'>Cadastro de Custo Tempo</a></td>
	<td class='descricao'>Cadastro e atulização de custo tempo por produtos</td>
</tr>
<?}?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='causa_troca_cadastro.php' class='menu'>Cadastro de Causa de Troca</a></td>
	<td class='descricao'>Cadastro das causas da troca do produto</td>
</tr>
<? if($login_fabrica == 56 OR $login_fabrica == 57 OR $login_fabrica == 58){ ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='laudo_tecnico_cadastro.php' class='menu'>Cadastro de Laudo Técnico</a></td>
	<td class='descricao'>Cadastro dos Laudos Ténicos por Produto ou Família</td>
</tr>
<?}?>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<!-- ========================================================================= -->
<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CADASTROS REFERENTES AO EXTRATO</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='lancamentos_avulsos_cadastro.php' class='menu'>Lançamentos Avulsos</a></td>
	<td nowrap class='descricao'>Cadastro dos Lançamentos Avulsos ao Extrato</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<!-- ========================================================================= -->
<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CADASTROS DE TRANSACIONADORES E OUTROS CADASTROS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='posto_cadastro.php' class='menu'>Postos Autorizados</a></td>
	<td nowrap class='descricao'>Cadastramento de postos autorizados</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='credenciamento.php' class='menu'>Credenciamento de Postos</a></td>
	<td nowrap class='descricao'>Credenciamento de postos autorizados</td>
</tr>
<!-- ================================================================== -->

<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='revenda_cadastro.php' class='menu'>Revendas</a></td>
	<td class='descricao'>Cadastro de Revendedores</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='consumidor_cadastro.php' class='menu'>Consumidores</a></td>
	<td class='descricao'>Cadastro de Consumidores</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='fornecedor_cadastro.php' class='menu'>Fornecedores</a></td>
	<td class='descricao'>Cadastro de Fornecedores</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='faq_situacao.php' class='menu'>Perguntas Frequentes</a></td>
	<td class='descricao'>Cadastro de  perguntas e respostas sobre um determinado produto </td>
</tr>
<!-- ================================================================== -->
<? if ($login_fabrica == 1) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='comunicado_blackedecker.php' class='menu'>Comunicados por E-mail</a></td>
	<td class='descricao'>Envie comunicados por e-mail para os postos</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<?
	if ($login_fabrica == 3){
?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='distribuidor_posto_relatorio.php' class='menu'>Distribuidor e seus postos</a></td>
	<td class='descricao'>Relação para conferência da Distribuição</td>
</tr>
<?
	}
?>
<?
	if ($login_fabrica == 3 AND ($login_admin==258 or $login_admin==852)){
?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='cadastro_km.php' class='menu'>Quilometragem</a></td>
	<td class='descricao'>Cadastro do valor pago por Quilometragem para Ordens de Serviços com atendimento em Domicilio.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='aprova_atendimento_domicilio.php' class='menu'>Aprovar OS Domicilio (EM TESTE)</a></td>
	<td class='descricao'>Aprovação de Ordens de Serviços que tenham atendimento em domicilio.</td>
</tr>
<?
	}
?>

<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='feriado_cadastra.php' class='menu'>Cadastro de Feriado</a></td>
	<td class='descricao'>Cadastro de feriados no sistema</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='callcenter_pergunta_cadastro.php' class='menu'>Cadastro de Perguntas do Callcenter</a></td>
	<td class='descricao'>Para que as frases padrões do callcenter sejam alteradas.</td>
</tr>
<?
	if ($login_fabrica == 20){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='upload_importacao.php' class='menu'>Upload de Arquivos</a></td>
	<td class='descricao'>Faz o Upload de peças, preço, produto, lista básica do Brasil e América Latina.</td>
</tr>
<?
	}
	?>



<?
	if ($login_fabrica == 7){
?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='condicao_cadastro.php' class='menu'>Classificação de OS</a></td>
	<td class='descricao'>Cadastro de Clasificação de Ordem de Serviço</td>
</tr>
<?
	}
?>

<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<!-- ========================================================================= -->
<?
//Menu disponivel somente para a Britania, como teste, HD 3780
	if ($login_fabrica == 3 OR $login_fabrica == 10){
?>
<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CONSULTA LOJA VIRTUAL</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='loja_completa.php' class='menu'>Listas de Produtos</a></td>
	<td nowrap class='descricao'>Listas dos Produtos Promoção Loja Virtual</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='manutencao_valormin.php' class='menu'>Manutenção</a></td>
	<td nowrap class='descricao'>Manutenção do Valor Minimo de Compra</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<?
	}
?>
<!-- ========================================================================= -->
<br />
<?if($login_fabrica==20 ){?>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>INFORMAÇÕES CADASTRAIS DA AMÉRICA LATINA</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='peca_informacoes_pais.php' class='menu'>Tabela de Preços América Latina</a></td>
	<td nowrap class='descricao'>Todas tabelas de preço da América Latina</td>
</tr>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='produto_informacoes_pais.php' class='menu'>Produtos por País</a></td>
	<td nowrap class='descricao'>Todas os produtos cadastrados pelos países da América Latina</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td width='260'><a href='informacoes_pais.php' class='menu'>Dados Países da América Latina</a></td>
	<td nowrap class='descricao'>Dados de conversão de moeda e desconto de cada país <br>usado na integração com a Alemanha</td>
</tr>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<?}?>
<? include "rodape.php" ?>

</body>
</html>
