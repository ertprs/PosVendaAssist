<?
##### COMUNICADOS NÃO LIDOS - INÍCIO #####
if (getenv("REMOTE_ADDR") == "201.0.9.216") {
	$sql =	"SELECT tbl_posto_fabrica.pedido_em_garantia ,
					tbl_posto_fabrica.pedido_faturado    ,
					tbl_posto.suframa
			FROM tbl_posto
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto.posto = $login_posto;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$pedido_em_garantia = pg_result($res,0,pedido_em_garantia);
		$pedido_faturado    = pg_result($res,0,pedido_faturado);
		$suframa            = pg_result($res,0,suframa);
	}

	$sql = "SELECT  tbl_comunicado.comunicado                          ,
					tbl_comunicado.tipo                                ,
					tbl_comunicado.descricao                           ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data  ,
					tbl_comunicado.data                       AS ordem ,
					tbl_comunicado.pedido_em_garantia                  ,
					tbl_comunicado.pedido_faturado                     ,
					tbl_comunicado.suframa
			FROM tbl_comunicado
			LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
			WHERE tbl_comunicado.fabrica     = $login_fabrica
			AND   (tbl_comunicado.destinatario_especifico ILIKE '%$login_codigo_posto%' OR tbl_comunicado.destinatario = $login_tipo_posto)
			AND   tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL ";
	if ($pedido_em_garantia == "t") $sql .= " AND tbl_comunicado.pedido_em_garantia IS NOT FALSE";
	if ($pedido_faturado == "t")    $sql .= " AND tbl_comunicado.pedido_faturado IS NOT FALSE";
	if ($suframa == "t")            $sql .= " AND tbl_comunicado.suframa IS NOT FALSE";
	$sql .= " LIMIT 1";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<div class='contentBlockLeft' style='background-color: #FFCC00;'>";
		echo "<img src='imagens/esclamachion1.gif'><b>Existem comunicados não lidos, <a href='comunicado_mostra_blackedecker.php'>clique aqui</a></b>.";
		echo "</div>";
		echo "<br>";
	}
}
##### COMUNICADOS NÃO LIDOS - FIM #####

###############################################
# VERIFICA SE TEM OSs RECUSADAS PELO FABRICANTE
###############################################
$sql =	"SELECT tbl_posto_fabrica.codigo_posto                                                  ,
				tbl_os.os                                                       ,
				tbl_os.sua_os                                                   ,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura ,
				tbl_os_status.observacao                       AS observacao    
		FROM tbl_os
		JOIN tbl_os_extra USING (os)
		JOIN tbl_posto    USING (posto)
		JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os 
		WHERE tbl_os.finalizada      ISNULL
		AND tbl_os.data_fechamento ISNULL
		AND tbl_os_extra.extrato   ISNULL
		AND tbl_os.posto   = $login_posto
		AND tbl_os.fabrica = $login_fabrica
		AND tbl_os_status.status_os = 13
		 ;";
$res = pg_exec($con,$sql);
//echo nl2br($sql);
if (pg_numrows($res) > 0) {
	echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo'>";
	echo "<td colspan='4' bgcolor='#FFFFCC' >RELAÇÃO DE OSs RECUSADAS</td>";
	echo "</tr>";
	echo "<tr class='Titulo'  bgcolor='#FFFFCC' >";
	echo "<td>OS</td>";
	echo "<td>ABERTURA</td>";
	echo "<td>STATUS</td>";
	echo "<td>OBSERVAÇÃO</td>";
	echo "</tr>";

	$extrato = '';
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
		$os             = trim(pg_result($res,$i,os));
		$sua_os         = trim(pg_result($res,$i,sua_os));
		$data_digitacao = trim(pg_result($res,$i,data_digitacao));
		$data_abertura  = trim(pg_result($res,$i,data_abertura));
		$observacao      = trim(pg_result($res,$i,observacao));

		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

//		echo "<tr class='Conteudo' bgcolor='$cor' onclick=\"javascript: window.location='os_cadastro.php?os=$os';\" style='cursor: hand;' TITLE='CLIQUE PARA ACESSAR A OS'>";
		echo "<tr class='Conteudo' bgcolor='$cor' >";
		echo "<td class='Conteudo' >$codigo_posto$sua_os</td>";
		echo "<td align='center'>" . $data_abertura . "</td>";
		echo "<td align='center'>Recusada</td>";
		echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}


########################################################
# VERIFICA SE TEM PEDIDO EM ABERTO HA MAIS DE UMA SEMANA
########################################################
$sqlX = "SELECT to_char (current_date - INTERVAL '6 day', 'YYYY-MM-DD')";
$resX = pg_exec ($con,$sqlX);
$dt_inicial = pg_result ($resX,0,0) . " 00:00:00";
$dt_inicial = '2005-12-26 13:40:00';

$sql = "SELECT  lpad(tbl_pedido.pedido_blackedecker,5,0) AS pedido_blackedecker
		FROM    tbl_pedido
		WHERE   tbl_pedido.finalizado          ISNULL
		AND     tbl_pedido.exportado           ISNULL
		AND     tbl_pedido.controle_exportacao ISNULL
		AND     tbl_pedido.admin               ISNULL
		AND     tbl_pedido.data > '$dt_inicial'
		AND     (
			tbl_pedido.natureza_operacao ISNULL        OR
			tbl_pedido.natureza_operacao <> 'SN-GART' AND
			tbl_pedido.natureza_operacao <> 'VN-REV'
			)
		AND     tbl_pedido.condicao          NOT IN (62) "; // condicao 62 é a GARANTIA
/*
if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2006-01-01 00:00:00"))) {
	$sql .= "AND     tbl_pedido.tabela = 31 ";
}else{
	$sql .= "AND     tbl_pedido.tabela = 108 ";
}
*/
$sql .= "AND     tbl_pedido.posto             = $login_posto
		AND     tbl_pedido.fabrica           = $login_fabrica
		ORDER BY tbl_pedido.pedido DESC LIMIT 1;";
$res = pg_exec ($con,$sql);
//echo $sql;
if (pg_numrows($res) > 0) {
	$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
	echo "<table border=0 width='700'>\n";
	echo "<tr>\n";
	echo "<td>";
	echo "<font size='2' color='#ff0000'><B>Existe o pedido de número <font color='#CC3300'>$pedido_blackedecker</font> sem finalização, o qual ainda não foi enviado para a fábrica.<br>Por gentileza, acesse a tela de digitação de pedidos e clique no botão <font color='#CC3300'>FINALIZAR</font>.</B></font>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br><br>\n";
}
########################################################


##### OS SEM LANCAMENTO DE ITENS HÁ MAIS DE 5 DIAS #####
/*
$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem
		FROM      tbl_os
		JOIN      tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
		LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.posto   = $login_posto
		AND   (tbl_os.data_abertura + INTERVAL '5 days') <= current_date
		AND   tbl_os_item.os_item    IS NULL
		AND   tbl_os.data_fechamento IS NULL
		ORDER BY tbl_os.data_abertura, os_ordem";
$res = pg_exec($con,$sql);
//echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
if (pg_numrows($res) > 0) {
	echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo' height='15'>";
	echo "<td colspan='4'>OS SEM LANCAMENTO DE ITENS HÁ MAIS DE 5 DIAS<br>Clique na OS para efetuar o lançamento</td>";
	echo "</tr>";
	echo "<tr class='Titulo' height='15'>";
	echo "<td>OS</td>";
	echo "<td>ABERTURA</td>";
	echo "<td>PRODUTO</td>";
	echo "</tr>";
	for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
		$os               = trim(pg_result($res,$a,os));
		$sua_os           = $login_codigo_posto . trim(pg_result($res,$a,sua_os));
		$abertura         = trim(pg_result($res,$a,abertura));
		$referencia       = trim(pg_result($res,$a,referencia));
		$descricao        = trim(pg_result($res,$a,descricao));
		$voltagem         = trim(pg_result($res,$a,voltagem));
		$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;
		$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
		echo "<td align='center'><a href='os_item.php?os=$os' target='_blank'>" . $sua_os . "</a></td>";
		echo "<td align='center'>" . $abertura . "</td>";
		echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
*/
##### OS SEM LANCAMENTO DE ITENS HÁ MAIS DE 5 DIAS #####

##### OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA #####
$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem
		FROM tbl_os
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.posto   = $login_posto
		AND   (tbl_os.data_abertura + INTERVAL '20 days') <= current_date
		AND   (tbl_os.data_abertura + INTERVAL '30 days') > current_date
		AND   tbl_os.data_fechamento IS NULL
		ORDER BY os_ordem";
$res = pg_exec($con,$sql);
//echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
if (pg_numrows($res) > 0) {
	echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo' height='15' bgcolor='#91C8FF'>";
	echo "<td colspan='3'>OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA</td>";
	echo "</tr>";
	echo "<tr class='Titulo' height='15' bgcolor='#91C8FF'>";
	echo "<td>OS</td>";
	echo "<td>ABERTURA</td>";
	echo "<td>PRODUTO</td>";
	echo "</tr>";
	for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
		$os               = trim(pg_result($res,$a,os));
		$sua_os           = $login_codigo_posto . trim(pg_result($res,$a,sua_os));
		$abertura         = trim(pg_result($res,$a,abertura));
		$referencia       = trim(pg_result($res,$a,referencia));
		$descricao        = trim(pg_result($res,$a,descricao));
		$voltagem         = trim(pg_result($res,$a,voltagem));
		$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;

		$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
		echo "<td align='center'>" . $sua_os . "</td>";
		echo "<td align='center'>" . $abertura . "</td>";
		echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
##### OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA #####

##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####
$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem
		FROM tbl_os
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.posto   = $login_posto
		AND   (tbl_os.data_abertura + INTERVAL '30 days') <= current_date
		AND   tbl_os.data_fechamento IS NULL
		AND  tbl_os.excluida is not true
		ORDER BY os_ordem";
$res = pg_exec($con,$sql);
//echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
if (pg_numrows($res) > 0) {
	echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
	echo "<td colspan='3'>OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO<br><font color='#FFFF00'>Clique na OS para informar o Motivo</font></td>";
	echo "</tr>";
	echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
	echo "<td>OS</td>";
	echo "<td>ABERTURA</td>";
	echo "<td>PRODUTO</td>";
	echo "</tr>";
	for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
		$os               = trim(pg_result($res,$a,os));
		$sua_os           = $login_codigo_posto . trim(pg_result($res,$a,sua_os));
		$abertura         = trim(pg_result($res,$a,abertura));
		$referencia       = trim(pg_result($res,$a,referencia));
		$descricao        = trim(pg_result($res,$a,descricao));
		$voltagem         = trim(pg_result($res,$a,voltagem));
		$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;
		
		$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
		echo "<td align='center'><a href='os_motivo_atraso.php?os=$os' target='_blank'>" . $sua_os . "</a></td>";
		echo "<td align='center'>" . $abertura . "</td>";
		echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####

?>


<style type="text/css">
.vermelho{
	color: #FF0000;
	
	text-align: center;
}

</style>
<div id="mainCol">
	<div id="leftCol" bgcolor='#FFCC66'>

		<div class="contentBlockLeft" align='left'>
			<table border=0 cellpadding=0>
				<tr>
				<td><img border="0" src="http://www.telecontrol.com.br/assist/imagens/esclamachion1.gif"></td>
				<td align="center"><a href="http://www.telecontrol.com.br/bd/index2.php" class='menu' target="_blank"><b>Sistema Antigo</b></a></td>
				</tr>
			</table>
			<br>
			<center><a href="http://www.telecontrol.com.br/bd/index2.php" class='menu' target="_blank">Clique aqui para consultar seus extratos gerados no sistema antigo.<!-- Para lançar suas OSs pendentes e consultas no sistema antigo, clique aqui.</font><br>(Disponível somente no mês de setembro para finalizar todas as pendências anteriores) --></a></center>
		</div>

		<div class="contentBlockLeft" align='left'>
			<center><a href="promocao.php" class='menu'>PROMOÇÕES</a></center>
			<center><a href="promocao.php" class='menu'>Compre parafusadeira, furadeira e moto compressor para utilizar em sua oficina.</a></center>
		</div>

<!-- 		<div class="contentBlockLeft" align='left'> -->
			<?
/*
			$sql = "SELECT  tbl_comunicado.comunicado                        ,
							to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
							tbl_comunicado.descricao                         ,
							tbl_produto.descricao as descricao_produto       
					FROM    tbl_comunicado
					JOIN    tbl_produto USING (produto)
					JOIN    tbl_linha   USING (linha)
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_comunicado.data DESC
					LIMIT 10";
			$res = pg_exec ($con,$sql);
			
			if (@pg_numrows($res) > 0) {
				for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
					$comunicado = trim(pg_result($res,$x,comunicado));
					$data       = trim(pg_result($res,$x,data));
					$produto	= trim(pg_result($res,$x,descricao_produto));
					$descricao  = trim(pg_result($res,$x,descricao));
					
					echo "<a href='comunicado_mostra.php?comunicado=$comunicado'>$data</a><br><font size='-2'><b>$produto</b></font><br/><a href='comunicado_mostra.php?comunicado=$comunicado'>$descricao</a><hr />";
				}
			}
*/
?>
<!-- 		</div> -->

	</div>
	<div id="middleCol">
		<div class='contentBlockMiddle'>
			<a href="http://www.blackdecker.com.br/xls/calendario_fechamento.xls" target="_blank"><b>CALENDÁRIO FISCAL</b></a>
			<br>
			<font color="#63798D">Para uma maior programação dos pedidos de peças e  acessórios, consulte o nosso <b><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'>Calendário Fiscal</a></b>, que contém a data limite para o envio de pedidos para a Black & Decker na semana do fechamento, <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>período do mês que não recebemos pedidos e não faturamos.</b></a>
		</div>
		<div class='contentBlockMiddle'>
			<a href="http://www.telecontrol.com.br/x_downloads.php" target="_blank">Clique aqui</a> <font color="#63798D">para baixar a versão offline do sistema Assist. (Lançamentos sem necessidade de conexão permanente à internet)</a>
		</div>
	</div>
	
	<div id="rightCol">
		<div class="contentBlockRight" bgcolor="#FFCCCC">
			<img src='imagens/esclamachion1.gif'><BR>
			<a href="procedimento_mostra.php"><b>PROCEDIMENTOS, COMPROMISSOS E OBRIGAÇÕES.</b></a>
			<BR><BR>
<?
/*
	##### PROCEDIMENTOS #####
	$sql = "SELECT tbl_comunicado.comunicado                                        ,
					tbl_comunicado.descricao                                         ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM tbl_comunicado
			WHERE tbl_comunicado.fabrica     = $login_fabrica
			AND   UPPER(tbl_comunicado.tipo) = 'PROCEDIMENTO'
			ORDER BY tbl_comunicado.data DESC;";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
			echo "<div>";
			echo "<a href='procedimento_mostra.php?acao=VER&comunicado=" . pg_result($res,$j,comunicado) . "'><FONT SIZE=\"2\">" . pg_result($res,$j,descricao) . "</FONT></a>";
			echo "<br><font size=\"1\">" . pg_result($res,$j,data) . "</font>";
			echo "</div>";
		}
	}
*/
?>
		</div>

		<div class="contentBlockRight">
			<center><a href='peca_faltante.php' class='menu'><span class="vermelho">Informe a Black & Decker<span></a></center><br>
			<h3><span class="vermelho"><center>Informe a Black & Decker quais equipamentos estão parados em sua oficina por falta de peças.</center></h3>

<!-- 			<h3>Aqui os Postos Autorizados <b><? echo $login_fabrica_nome ?></b> podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.</h3> -->
		</div>
		</div>

<?
$sql =	"SELECT tbl_posto_fabrica.tipo_posto
		FROM    tbl_posto_fabrica
		WHERE   tbl_posto_fabrica.posto = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$tipo_posto = trim(pg_result($res,0,tipo_posto));

	if ($tipo_posto == "36" or $tipo_posto == 82 or $tipo_posto == 83 or $tipo_posto == 84) {
?>

	<div id="leftCol" bgcolor='#FFCC66'>
		<div class="contentBlockMiddle" style="width: 610;">
			<a href="http://www.blackdecker.com.br/locacao/projeto-locacao.pdf" target="_blank">Projeto Locação</a><br>
			<font face="Verdana, Tahoma, Arial" size="2" color="#63798D">Informe-se sobre o que é o projeto locação.</font><br>
			<br>
			<a href="http://www.blackdecker.com.br/locacao/comparativo-concorrencia.pdf" target="_blank">Comparativo com a Concorrência</a><br>
			<font face="Verdana, Tahoma, Arial" size="2" color="#63798D">Veja um comparativo entre a concorrência.</font><br>
			<br>
			<a href="http://www.blackdecker.com.br/locacao/informacao-manutencao.pdf" target="_blank">Informações sobre Manutenções</a><br>
			<font face="Verdana, Tahoma, Arial" size="2" color="#63798D">Informe-se sobre as manutenções.</font><br>
			<br>
			<a href="http://www.blackdecker.com.br/locacao/precos.pdf" target="_blank">Preços de Máquinas e Acessórios</a><br>
			<font face="Verdana, Tahoma, Arial" size="2" color="#63798D">Confira os preços de máquinas e acessórios.</font><br>
			<br>
			<a href="http://www.blackdecker.com.br/locacao/pecas-estoque.pdf" target="_blank">Peças em garantia e Estoque mínimo</a><br>
			<font face="Verdana, Tahoma, Arial" size="2" color="#63798D">Confira quais as peças estão em garantia e a quantidade em estoque mínima.</font><br>
			<br>
			<a href="http://www.blackdecker.com.br/locacao/vista-explodida.pdf" target="_blank">Vista Explodida</a><br>
			<font face="Verdana, Tahoma, Arial" size="2" color="#63798D">Arquivo da vista explodida e relação de peças.</font><br>
			<br>
			<a href="http://www.blackdecker.com.br/vistas_dw.php" target="_blank">Vista Explodida da Linha DeWalt</a><br>
			<font face="Verdana, Tahoma, Arial" size="2" color="#63798D">Arquivo da vista explodida e relação de peças da Linha DeWalt.</font>
		</div>
	</div>

<?
	}
}
?>

	<div id="leftCol" bgcolor='#FFCC66'>
		<div class="contentBlockRight">
<style>
table.fale {font-size:12px}
</style>
			<TABLE border='0' width="610" class="fale">
			<TR>
				<TD colspan="3" bgcolor="#eeeeee">
					<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><b> :: FALE CONOSCO</b></font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:mipereira@blackedecker.com.br'><b>MIGUEL PEREIRA</b></a><br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Gerente de Assistência Técnica.<br>
						MiPereira@blackedecker.com.br<br>
						FONE (34) 3318-3011
					</font>
				</TD>
				<TD>
					<a href='mailto:salves@blackedecker.com.br'><b>SILVANIA ALVES</b></a><br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Supervisora de Assistência Técnica.<br>
						 salves@blackedecker.com.br<br>
						FONE (34) 3318-3025
					</font>
				</TD>
				<TD>
					<a href='mailto:rogerio_berto@blackedecker.com.br'><b>ROGÉRIO BERTO</b></a><br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Analista Técnico e Elaboração <br> de Vistas Explodidas.<br>
						rogerio_berto@blackedecker.com.br<br>
						FONE (34) 3318-3023
					</font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:ueris@blackedecker.com.br'><b>ULISSES REIS</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Orientação Técnica e Especialista <br> em Treinamento Técnico.<br>
						ureis@blackedecker.com.br<br>
						FONE (34) 3318-3906
					</font>
				</TD>
				<TD>
					<a href='mailto:llaterza@blackedecker.com.br'><b>LILIAN LATERZA</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Atendimento à Revenda / Embalagem / Pendência de docs. da Garantia.<br>
						llaterza@blackedecker.com.br<br>
						FONE (34) 3318-3924
					</font>
				</TD>
				<TD>
					<a href='mailto:rfernandes@blackedecker.com.br'><b>RÚBIA FERNANDES</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Analista de Faturamento.<br>
						rfernandes@blackedecker.com.br<br>
						FONE (34) 3318-3024
					</font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:faoliveira@blackedecker.com.br'><b>FABÍOLA OLIVEIRA</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Acompanhamento e aprovação dos extratos para pagamento em garantia.<br>
						faoliveira@blackedecker.com.br<br>
						FONE (34) 3318-3921
					</font>
				</TD>
				<TD>
					<a href='mailto:cschafer@blackedecker.com.br'><b>CHRISTOPHER SCHAFER</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Coordenador de postos autorizados (Nomeação e Cancelamento de oficinas).<br>
						cschafer@blackedecker.com.br<br>
						FONE (34) 3318-3922
					</font>
				</TD>
				<TD>
					<a href='mailto:drocha@blackedecker.com.br'><b>DIOGO ROCHA</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Suporte a Rede Autorizada.<br>
						drocha@blackedecker.com.br<br>
						FONE (34) 3318-3920
					</font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:acamilo@blackedecker.com.br'><b>ANDERSON CAMILO</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Atendimento ao Assistente Técnico.<br>
						acamilo@blackedecker.com.br<br>
						fone (34) 3318-3085
					</font>
				</TD>
				<TD>
					<a href='mailto:pmachado@blackedecker.com.br'><b>PATRÍCIA MACHADO</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Suporte ao SAC - Serviço de Atendimento ao Consumidor.<br>
						pmachado@blackedecker.com.br<br>
						FONE (34) 3318-3012
					</font>
				</TD>
				<TD>
					<a href='mailto:samaral@blackedecker.com.br'><b>SABRINA AMARAL</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Satisfação 30 dias DeWalt e <br> Troca de Produtos.<br>
						samaral@blackedecker.com.br<br>
						FONE (34) 3318-3020
					</font>
				</TD>
			</TR>
						<TR>
				<TD>
				<a href='mailto:mclemente@blackedecker.com.br'><b>MICHEL CLEMENTE</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Instrutor.<br>
						mclemente@blackedecker.com.br<br>
						FONE (34) 3318-3906
					</font>
				</TD>
				<TD>
				
				</TD>
				<TD>

				</TD>
			</TR>
			</TABLE>
		</div>
	</div>
</div>

<map name='m_novo_sistema'>
<area shape="rect" coords="501,65,577,121" href="pdf/sistema.htm" target="_blank" alt="" >
<area shape="rect" coords="418,65,498,121" href="pdf/sistema.doc" target="_blank" alt="" >
<area shape="rect" coords="326,65,411,121" href="pdf/sistema.pdf" target="_blank" title="Clique para ver em Adobe Acrobat" alt="Clique para ver em Adobe Acrobat" >
<area shape="rect" coords="503,143,579,199" href="pdf/ajuda.htm" target="_blank" alt="" >
<area shape="rect" coords="420,143,500,199" href="pdf/ajuda.doc" target="_blank" alt="" >
<area shape="rect" coords="328,143,413,199" href="pdf/ajuda.pdf" target="_blank" title="Clique para ver em Adobe Acrobat" alt="Clique para ver em Adobe Acrobat" >
</map>
