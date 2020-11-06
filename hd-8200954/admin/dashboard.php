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
alt='Exemplo de Gr�fico'>


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

echo "<script language='javascript'>document.write (\"teste\") ; document.write (traduzir('testando sistema de tradu��o','fr'));</script>";

?>

<div id="text">Die Firma Telecontrol �bernimmt Vertrieb, Lagerung und Bestandsverwaltung von Ersatzteilen f�r den Technischen Kundendienst.</div>    
<div id="translation"></div>
	
<hr>


<?
#------------ Valida��es BRITANIA ------------
if ($login_fabrica == 3 OR $login_fabrica == 6 ) {
	#----------------- Produtos sem Familia ---------------
	$sql = "SELECT produto, referencia, descricao FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.familia IS NULL AND tbl_produto.ativo IS NOT FALSE ";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		echo "<center><font style='12px' color='#882200'>Produtos sem Fam�lia</font></center>";
		echo "<table align='center' cellspacing='1'>";
		echo "<tr bgcolor='#882200' style='font-color:#ffffff ; font-weight:bold ; font-size:12px' >";
		echo "<td align='center'>Refer�ncia</td>";
		echo "<td align='center'>Descri��o</td>";
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

	#----------------- Produtos sem M�o-de-Obra ---------------
	$sql = "SELECT produto, referencia, descricao FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND (tbl_produto.mao_de_obra IS NULL OR tbl_produto.mao_de_obra = 0) AND tbl_produto.ativo IS NOT FALSE  ";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		echo "<center><font style='12px' color='#882200'>Produtos sem M�o-de-Obra</font></center>";
		echo "<table align='center' cellspacing='1'>";
		echo "<tr bgcolor='#882200' style='font-color:#ffffff ; font-weight:bold ; font-size:12px' >";
		echo "<td align='center'>Refer�ncia</td>";
		echo "<td align='center'>Descri��o</td>";
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



<script type="text/javascript">
	google.load("visualization", "1", {packages:["gauge"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Label');
		data.addColumn('number', 'Value');
		data.addRows(1);
		data.setValue(0, 0, 'An�lise');
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
		data.setValue(0, 0, 'Pe�a');
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
		Posi��o das Ordens de Servi�o em aberto
	</td>
</tr>
<tr height='120'>
	<td id='chart_div1' title="OS aguardando an�lise"></td>
	<td id='chart_div2' title="OS aguardando a f�brica faturar a pe�a"></td>
	<td id='chart_div3' title="Pe�a enviada. Aguardando conserto"></td>
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
	<td nowrap class='descricao'>Consulta - Inclus�o - Exclus�o de Marcas.</td>
</tr>
<? } ?>

<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='tipo_posto_cadastro.php' class='menu'>Tipo de Postos</a></td>
	<td nowrap class='descricao'>Consulta - Inclus�o - Exclus�o dos Tipos de Postos.</td>
</tr>

<!-- ================================================================== -->

<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='linha_cadastro.php' class='menu'>Linhas de Produtos</a></td>
	<td nowrap class='descricao'>Consulta - Inclus�o - Exclus�o de Linha de Produtos.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='familia_cadastro.php' class='menu'>Fam�lia de Produtos</a></td>
	<td class='descricao'>Consulta - Inclus�o - Exclus�o de Fam�lia de Produtos.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='produto_cadastro.php' class='menu'>Cadastro de Produtos</a></td>
	<td class='descricao'>Consulta - Inclus�o - Exclus�o de Produtos.</td>
</tr>
<!-- ================================================================== -->
<? //hd 19043
	if ($login_fabrica <> 50 ) { ?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='subproduto_cadastro.php' class='menu'>Cadastro de Sub-Produtos</a></td>
			<td class='descricao'>Consulta - Inclus�o - Exclus�o de Sub-Produtos.</td>
		</tr>
<?	} ?>

<? //hd 20300
	if ($login_fabrica ==11 ) { ?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='transportadora_cadastro.php' class='menu'>Cadastro de Transportadora</a></td>
			<td class='descricao'>Consulta - Inclus�o - Exclus�o de Transportadoras.</td>
		</tr>
<?	} ?>

<? if ($login_fabrica==14) { ?>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='produto_consulta_detalhe.php' class='menu'>Estrutura do produto</a></td>
		<td class='descricao'>Consulta dados da estrutura do produto (Produto > Subconjunto > Pe�as).</td>
	</tr>
<? } ?>
<? if ($login_fabrica==5) {?>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='serie_controle_cadastro.php' class='menu'>Cadastro de N�meros de S�rie</a></td>
		<td class='descricao'>Consulta - Inclus�o - Exclus�o de N�mero de S�rie e quantidade produzida por produto.</td>
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
  <TD class=cabecalho>CADASTROS REFERENTES A PEDIDOS DE PE�AS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='peca_cadastro.php' class='menu'>Cadastro de Pe�as</a></td>
	<td class='descricao'>Consulta - Inclus�o - Exclus�o de Componentes utilizados pela f�brica.</td>
</tr>
<!-- ================================================================== -->
<?
//PARA BLACK - ADICIONADO DIA 30-03-2007 IGOR - HD:1666
if($login_fabrica == "24"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_amarracao.php' class='menu'>Amarra��o de Pe�as</a></td>
	<td class='descricao'>Ferramenta de amarra��o de pe�as. Quando lan�ar uma pe�a � obrigado a lan�ar a pe�a amarrada.</td>
</tr>
<?}
if($login_fabrica == "6"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_amarracao_lista.php' class='menu'>Lista Pe�a X Pe�a</a></td>
	<td class='descricao'>Cadastro e exclus�o de pe�a e subpe�a da lista b�sica.</td>
</tr>
<?}?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='lbm_cadastro.php' class='menu'>Lista B�sica</a></td>
	<td class='descricao'>Estrutura de pe�as aplicadas a cada produto</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='condicao_cadastro.php' class='menu'>Condi��es de Pagamento</a></td>
	<td class='descricao'>Cadastramento de condi��es de pagamentos para pedidos de pe�as</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='tipo_posto_condicao_cadastro.php' class='menu'>Condi��es de Pagamento para Postos</a></td>
	<td class='descricao'>Cadastramento de condi��es de pagamentos para pedidos de pe�as espec�fica para postos</td>
</tr>
<!-- ================================================================== -->
<?
//PARA BLACK - ADICIONADO DIA 30-03-2007 IGOR - HD:1666
if($login_fabrica == "1"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='condicao_pagamento_manutencao.php' class='menu'>Altera��o de Condi��es de Pagamento</a></td>
	<td class='descricao'>Altera��o  de condi��es de pagamentos dos postos</td>
</tr>
<?
}	
?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='preco_cadastro.php' class='menu'>Pre�os de Pe�as</a></td>
	<td class='descricao'>Cadastramento e altera��o em pre�os de pe�as.</td>
</tr>
<?
//HD 17541
if($login_fabrica == "1"){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='preco_upload.php' class='menu'>Atualiza��o de Pre�os de Acess�rios</a></td>
	<td class='descricao'>Atualiza pre�o de pe�a Acess�rios para pedido Acess�rio e Loja Virtual.</td>
</tr>
<!-- ================================================================== -->
<?}if ($login_fabrica == 3) {?>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='fator_multiplicacao.php' class='menu'>Pre�os Sugeridos</a></td>
		<td class='descricao'>Cadastro de pre�os sugeridos para que o PA se baseie para vender ao consumidor.</td>
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
	<td class='descricao'>Cadastro de pe�as DE-PARA (altera��o em c�digos de pe�as).</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_alternativa_cadastro.php' class='menu'>Pe�as Alternativas</a></td>
	<td class='descricao'>Cadastro de pe�as ALTERNATIVAS.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_fora_linha_cadastro.php' class='menu'>Pe�as Fora de Linha</a></td>
	<td class='descricao'>Cadastro de pe�as FORA DE LINHA</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_analise_cadastro.php' class='menu'>Pe�as em An�lise</a></td>
	<td class='descricao'>Cadastro de pe�as em AN�LISE</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_acerto.php' class='menu'>Acerto de Pe�as</a></td>
	<td class='descricao'>Lista todas as pe�as e seus dados para acerto comum.</td>
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
	<td><a href='peca_previsao_entrega.php' class='menu'>Previs�o de Entrega de Pe�as</a></td>
	<td class='descricao'>Cadastra a previs�o de entrega de pe�as com abastecimento cr�tico. Os postos ser�o informados da previs�o, e pode-se consultar as pend�ncias destas pe�as para tomada de provid�ncias.</td>
</tr>
<!-- ================================================================== -->
<? if ($login_fabrica <> 6 and $login_fabrica <> 24 and $login_fabrica <> 50) { ?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
<!--<td nowrap width='260'><a href='peca_represada_cadastro.php' class='menu'>Pe�as Represadas</a></td>-->
	<td nowrap width='260'><a href='peca_represada_cadastro.php' class='menu'>Pe�as Utilizadas do Estoque do Distribuidor</a></td>
	<td class='descricao'>Cadastro de Pe�as que o distribuidor n�o vai mais receber automaticamente. As pe�as ir�o gerar cr�dito. A finalidade deste processo � permitir que o distribuidor possa abaixar o estoque de determinadas pe�as.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 5) { ?>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td><a href='producao_cadastro.php' class='menu'>Cadastro de Itens de Produ��o</a></td>
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
		<td class="cabecalho">LOCA��O</td>
		<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
	</tr>
</table>
<table width="700" border="0" cellpadding="0" cellspacing="0" align = 'center'>
	<tr bgcolor="#F0F0F0">
		<td width="25"><img border="0" src="imagens/pasta25.gif"></td>
		<td nowrap width="260"><a href="os_cadastro_locacao.php" class="menu">Cadastro de Produtos Loca��o</a></td>
		<td nowrap class="descricao">Produtos liberados para Loca��o</td>
	</tr>
	<tr bgcolor="#FAFAFA">
		<td width="25"><img border="0" src="imagens/pasta25.gif"></td>
		<td nowrap width="260"><a href="pedido_consulta_locacao.php" class="menu">Consulta de Produtos Loca��o</a></td>
		<td nowrap class="descricao">Consulta Produtos liberados para Loca��o</td>
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
  <TD class=cabecalho>CADASTROS DE DEFEITOS - EXCE��ES</TD>
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
	<td class='descricao'>Tipos de defeitos constatados pelo T�CNICO</td>
</tr>
<!-- ================================================================== -->
<? //chamado 2977
if (1==2 and $login_fabrica<>24){?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='causa_defeito_cadastro.php' class='menu'>Causa de Defeitos</a></td>
	<td class='descricao'>Causas de defeitos constatados pelo T�CNICO</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='excecao_cadastro.php' class='menu'>Exce��o de m�o-de-obra</a></td>
	<td class='descricao'>Cadastro das exce��es de m�o-de-obra</td>
</tr>
<?if($login_fabrica==45){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='extrato_lancamento_mensal.php' class='menu'>Valor fixo mensal para postos</a></td>
	<td class='descricao'>Cadastro de valores que ser�o inclu�dos todos os meses ao extrato</td>
</tr>
<?}?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='servico_realizado_cadastro.php' class='menu'><? if ($login_fabrica==20){ echo "Cadastro de Identifica��o";}else {echo"Servi�os Realizados";}?></a></td>
	<td class='descricao'><? if ($login_fabrica==20){ echo "Cadastro de Identifica��o, terceiro c�digo de falha";}else {echo"Cadastro de servi�os realizados";}?></td>
</tr>

<!-- ================================================================== -->
<? //chamado 2977
if ($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==5 OR $login_fabrica==8 OR $login_fabrica==10 OR $login_fabrica==14 OR $login_fabrica==16 OR $login_fabrica==20) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_causa_defeito_cadastro.php' class='menu'>Defeitos x Causa do Defeito</a></td>
	<td class='descricao'>Cadastro da rela��o entre os defeitos e suas causas poss�veis</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? //chamado 2977
if ($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==5 OR $login_fabrica==8 OR $login_fabrica==10 OR $login_fabrica==14 OR $login_fabrica==16 OR $login_fabrica==20) { ?>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_reclamado_defeito_constatado.php' class='menu'>Defeito Constatado x Reclamado</a></td>
	<td class='descricao'>Cadastro da rela��o entre os defeitos reclamados e seus poss�veis defeitos constatados</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='defeito_cadastro.php' class='menu'>Defeito em Pe�as</a></td>
	<td class='descricao'>Cadastro de defeitos que podem ocorrer nas pe�as</td>
</tr>
<!-- ================================================================== -->
<?
/*if (($login_fabrica == 6) or ($login_fabrica == 11) or ($login_fabrica == 15) or ($login_fabrica == 3) or ($login_fabrica == 24) or ($login_fabrica == 3) or ($login_fabrica == 5) or ($login_fabrica == 2) or ($login_fabrica == 29) or ($login_fabrica == 30) or ($login_fabrica == 31 or ($login_fabrica == 32) or ($login_fabrica == 33) or ($login_fabrica == 34) or ($login_fabrica == 35) or ($login_fabrica == 36) or ($login_fabrica == 37) or ($login_fabrica == 8))){*/
if($login_fabrica<>14 and $login_fabrica<>2 and $login_fabrica<>19 and $login_fabrica<>20){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='solucao_cadastro.php' class='menu'>Solu��o</a></td>
	<td class='descricao'>Cadastro de Solu��o de um defeito</td>
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
	<td class='descricao'>Relacionamento de Linha, Familia, Defeito Reclamado, Defeito Constatado e Solu��o   para o Diagn�stico</td>
</tr>
<?
}
if($login_fabrica == 15){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='os_acerto_defeito.php' class='menu'>
		Acertos de OS�s cadastradas</a></td>
	<td class='descricao'>Acerto dos cadastro dos defeitos das OS�s.</td>
</tr>
<?}
if($login_fabrica==24){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='peca_integridade.php' class='menu'>Integridade de Pe�as</a></td>
	<td class='descricao'>Cadastro de integridade de pe�as</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='acao_corretiva_cadastro.php' class='menu'>A��o Corretiva</a></td>
	<td class='descricao'>Cadastro de corre��es efetuadas em produtos.</td>
</tr>

<? } ?>
<?if($login_fabrica == 20){?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='produto_custo_tempo_cadastro.php' class='menu'>Cadastro de Custo Tempo</a></td>
	<td class='descricao'>Cadastro e atuliza��o de custo tempo por produtos</td>
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
	<td><a href='laudo_tecnico_cadastro.php' class='menu'>Cadastro de Laudo T�cnico</a></td>
	<td class='descricao'>Cadastro dos Laudos T�nicos por Produto ou Fam�lia</td>
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
	<td nowrap width='260'><a href='lancamentos_avulsos_cadastro.php' class='menu'>Lan�amentos Avulsos</a></td>
	<td nowrap class='descricao'>Cadastro dos Lan�amentos Avulsos ao Extrato</td>
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
	<td class='descricao'>Rela��o para confer�ncia da Distribui��o</td>
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
	<td class='descricao'>Cadastro do valor pago por Quilometragem para Ordens de Servi�os com atendimento em Domicilio.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='aprova_atendimento_domicilio.php' class='menu'>Aprovar OS Domicilio (EM TESTE)</a></td>
	<td class='descricao'>Aprova��o de Ordens de Servi�os que tenham atendimento em domicilio.</td>
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
	<td class='descricao'>Para que as frases padr�es do callcenter sejam alteradas.</td>
</tr>
<?
	if ($login_fabrica == 20){
?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='upload_importacao.php' class='menu'>Upload de Arquivos</a></td>
	<td class='descricao'>Faz o Upload de pe�as, pre�o, produto, lista b�sica do Brasil e Am�rica Latina.</td>
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
	<td><a href='condicao_cadastro.php' class='menu'>Classifica��o de OS</a></td>
	<td class='descricao'>Cadastro de Clasifica��o de Ordem de Servi�o</td>
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
	<td nowrap class='descricao'>Listas dos Produtos Promo��o Loja Virtual</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fAfAfA'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='manutencao_valormin.php' class='menu'>Manuten��o</a></td>
	<td nowrap class='descricao'>Manuten��o do Valor Minimo de Compra</td>
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
  <TD class=cabecalho>INFORMA��ES CADASTRAIS DA AM�RICA LATINA</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='peca_informacoes_pais.php' class='menu'>Tabela de Pre�os Am�rica Latina</a></td>
	<td nowrap class='descricao'>Todas tabelas de pre�o da Am�rica Latina</td>
</tr>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='produto_informacoes_pais.php' class='menu'>Produtos por Pa�s</a></td>
	<td nowrap class='descricao'>Todas os produtos cadastrados pelos pa�ses da Am�rica Latina</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td width='260'><a href='informacoes_pais.php' class='menu'>Dados Pa�ses da Am�rica Latina</a></td>
	<td nowrap class='descricao'>Dados de convers�o de moeda e desconto de cada pa�s <br>usado na integra��o com a Alemanha</td>
</tr>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<?}?>
<? include "rodape.php" ?>

</body>
</html>
