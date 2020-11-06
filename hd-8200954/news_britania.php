<!--
<script language="JavaScript">
var width = 400;
var height = 180;
var left = (screen.width-width)/2;
var top = (screen.height-height)/2;
var url = "http://www.telecontrol.com.br/assist/comunicado_britania_ht5000.php";
window.open(url,'Britania', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=no, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
</script>
-->
<?
$audio = 'f';

$sql = "SELECT *
		FROM   tbl_linha
		JOIN   tbl_posto_linha   using (linha)
		JOIN   tbl_posto_fabrica using (posto)
		WHERE  tbl_posto_fabrica.fabrica = $login_fabrica
		AND    tbl_posto_linha.posto     = $login_posto
		AND    tbl_linha.nome = 'Áudio e Video';";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0){
	$audio = "t";
}
?>

<div id="mainCol">

<?
if ($login_posto == 4311) {
	$sql = 	"SELECT count(*)
		FROM    tbl_pedido
		WHERE   distribuidor = $login_posto
		AND     status_pedido_posto NOT IN (13)";
}else{
	$sql = 	"SELECT count(*)
		FROM    tbl_pedido
		WHERE   distribuidor = $login_posto
		AND     (pedido_atendido_total IS FALSE OR pedido_atendido_total IS NULL )";
}
$res = pg_exec($con,$sql);
if (pg_result($res,0,0) > 0) {
	echo "<div class='contentBlockLeft' style='background-color: #FFCC00;'>";
	echo "<img src='imagens/esclamachion1.gif'><a href='pedido_posto_relacao.php'>Confira os Pedidos para Atendimento via Distribuidor.<br>".pg_result($res,0,0)." pedidos encontrados.</a>";
	echo "</div>";
}

//if ($ip == '201.0.9.216') echo "Distribuidor: [ $login_distribuidor ] - [ $pedido_via_distribuidor ]";

$sql = "SELECT * 
		FROM   tbl_posto_linha 
		WHERE  posto        = $login_posto 
		AND    distribuidor = 829;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
?>
	<div class="contentBlockLeft">
		<table width='600px' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td>
				<font face='arial' size=2 color='#330066'>
				Prezados Postos Autorizados,
				<br><br>
				A partir de 01 de abril de 2006, o atendimento de peças será de responsabilidade da Fábrica.
				<br>
				O atual distribuidor de peças, Eletrojoli, será responsável por todos os processos até a data acima citada.
				<br><br>
				Todas as peças enviadas pelo distribuidor para atendimento de pedidos faturados ou em garantia, devem ser reportados ao distribuidor.
				<br>
				As ordens de serviço onde as peças serão atendidas pelo distribuidor, ao serem finalizadas, deverão necessariamente ser enviadas para o distribuidor Eletrojoli. As ordens de serviços em que a fábrica forneceu as peças devem ser enviadas para a Britania Eletrodomesticos S.A.
				<br><br>
				Qualquer dúvida, favor entrar em contato através do email assistenciatecnica@britania.com.br ou pelo 0800-415300
				<br><br>
				Atenciosamente,
				<br><br>
				Departamento de Assistência Técnica
				<br>
				Britania Eletrodomesticos S.A.
			</font>
			</td>
		</tr>
		</table>
	</div>
<?
}
?>
	<div class="contentBlockLeft">
		<table width='600px' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td>
				<img src='imagens/esclamachion.gif'>
			</td>
			<td>
			<font face='arial' color='#330066'>
				Leia a Circular e o Informativo antes de usar este novo site.
			</font>
			</td>
		</tr>
		</table>
	</div>

	<div class="contentBlockLeft">
		<table width='600px' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td align = center>
				<a href='comunicados/britania_Orient_NovoSistemaColetaPAC.pdf' target='_blank'>
				<font face='arial' color='#330066' size='3'><b>Novo sistema de Coleta PAC.</b></font>
				Clique aqui
				</font>
				</a>
			</td>
		</tr>
		</table>
	</div>

	<div class="contentBlockLeft" style='background-color: #FFCC00;'>
		<table>
			<tr>
				<td><img src='imagens/esclamachion1.gif'></td>
				<td><b>Comunicados</b></td>
			</tr>
		</table>
		<font size="-2"><b>COMUNICADO - 23/09/2005</b></font>
		<br>
		<font size="-2">
		<b>Para toda nota fiscal de peças enviadas em garantia deve haver nota fiscal de devolução de todas as peças nos mesmos valores, quantidades e com os mesmos destaques de impostos obrigatoriamente.</b><br>
		<b>Para visualizar o arquivo, <a href="comunicados/702.pdf" target="_blank">clique aqui</a>.</b><br>
		</font>
<!--	<br>
		<font size="-2"><b>Nova data Inventário 2005/2006 – Assistência Técnica Britânia - 02/01/2006</b></font>
		<br>
		<font size="-2">
		<b>
		Comunicamos a todos que foi prorrogado o período de inventário na Fábrica.<br>
		Fica estabelecido o novo período de 22 de dezembro de 2005 a 05 de janeiro de 2006.<br>
		Neste período não serão enviadas peças ou trocas de produtos à rede autorizada.
		</b>
		<br>
		<b>Para visualizar o arquivo, <a href="http://www.telecontrol.com.br/assist/comunicados/britania_nova_data_inventario_2005.doc" target="_blank">clique aqui</a>.</b><br>
		</font>-->
		<br>
		<font size="-2"><b>COMUNICADO - 02/01/2006</b></font>
		<br>
		<font size="-2">
		<b>
		Foi revisto o valor de venda dos controles remotos.<br>
		A partir do dia 02/01/2006 será obrigatória a venda de todos os controles remotos de aparelhos DVD e Home Theater ao consumidor final pelo preço máximo de R$ 60,00.
		</b>
		<br>
		<b>Para visualizar o arquivo, <a href="comunicados/britania_novo_comunicado_precos_controles_remotos.doc" target="_blank">clique aqui</a>.</b><br>
		</font>
<!--	<br>
		<br>
		<b>Promoção de Vendas de Peças Originais</b><br>
		<b>Para visualizar o arquivo, <a href="http://www.telecontrol.com.br/assist/comunicados/703.doc" target="_blank">clique aqui</a>.</b><br>-->
		<br>
		<font size="1" color="#FF0000">Se você não possui o Acrobat Reader®, <a href="http://www.adobe.com/products/acrobat/readstep2.html" target="_blank">instale agora</a>.</font>
	</div>
	<div id="leftCol" bgcolor='#FFCC66'>
		<div class="contentBlockLeft">
			<img src='imagens/information.gif'>
		</div>

		<div class="contentBlockLeft">
			<a href="comunicados/britania_info_BTBaseFerro.pdf">NOVO<br>Informativo 02/2005</a>
		</div>

		<div class="contentBlockLeft">
			<font color="#FF0000"><b>ATENÇÃO!!!<br>Novos produtos</b></font>
			<br>
			<a href="comunicados/britania_novos_produtos_novembro_2005.doc">- Novembro/2005</a>
			<br>
			<a href="comunicados/britania_comunicado_novos_produtos.doc">- Julho/2005</a>
		</div>

<!-- 
		<div class="contentBlockLeft">
			<a href="comunicados/britania_comunicado_novos_produtos.pdf">ATENÇÃO!!!<br>Novos produtos<BR>Clique aqui</a>
		</div>
 -->
		<div class="contentBlockLeft">
			<a href="comunicados/britania_comunicado_jan2005.pdf">Comunicado 01/2005</a>
		</div>

		<div class="contentBlockLeft">
			<a href="comunicados/britania_info-dvd500_revisado.pdf">Informativo 01/2005</a>
		</div>

		<div class="contentBlockLeft">

			<!-- Insira aqui o texto de sua escolha -->
			<?



			$sql2 = "SELECT tbl_posto_fabrica.codigo_posto        ,
							tbl_posto_fabrica.tipo_posto       
					FROM	tbl_posto
					LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
					AND     tbl_posto.posto   = $login_posto ";
			
			$res2 = pg_exec ($con,$sql2);

			if (pg_numrows ($res2) > 0) {
				$tipo_posto            = trim(pg_result($res2,0,tipo_posto));
			}


//PEGA O TIPO DO POSTO E MOSTRA PARA NAO MOSTRAR COMUNICADOS PARA OUTROS POSTOS

			$sql = "SELECT  tbl_comunicado.comunicado                        ,
							tbl_comunicado.posto                             ,
							to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
							tbl_comunicado.descricao                         ,
							tbl_produto.descricao as descricao_produto       
					FROM    tbl_comunicado
					LEFT JOIN    tbl_produto USING (produto)
					LEFT JOIN    tbl_linha   on tbl_linha.linha = tbl_produto.linha
					WHERE   tbl_comunicado.fabrica = $login_fabrica
					AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
					AND    tbl_comunicado.ativo IS TRUE 
					ORDER BY tbl_comunicado.data DESC
					LIMIT 10";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
					$posto = trim(pg_result($res,$x,posto));
					if(($posto == $login_posto) || ($posto == '')){
						$comunicado = trim(pg_result($res,$x,comunicado));
						$data       = trim(pg_result($res,$x,data));
						$produto	= trim(pg_result($res,$x,descricao_produto));
						$descricao  = trim(pg_result($res,$x,descricao));
						echo "<a href='comunicado_mostra.php?comunicado=$comunicado'>$data</a><br><font size='-2'><b>$produto</b></font><br/><a href='comunicado_mostra.php?comunicado=$comunicado'>$descricao</a><hr />";
					}
				}
			}
			?>
		</div>

	</div>
	<div id="middleCol">
		<div class='contentBlockMiddle'>
			<div class="contentBlockLeft" style='background-color: #FFCC00;'>
				<img src='imagens/esclamachion1.gif'>
				<br>
				<b>Comunicado<br>19/07/2005</b>
				<br>
				<br>
				<b>DVD COMPACT SLIM - Defeitos Fonte de Alimentação</b><br>
				<b>Para visualizar o arquivo, <a href="comunicados/542.pdf" target="_blank">clique aqui</a>.</b><br>
				<br>
				<b>Problemas com operações da Gaveta DVD-500/1000/Matrix-10</b><br>
				<b>Para visualizar o arquivo, <a href="assist/comunicados/533.doc" target="_blank">clique aqui</a>.</b><br>
				<br>
				<font size="1" color="#FF0000">Se você não possui o Acrobat Reader®, <a href="http://www.adobe.com/products/acrobat/readstep2.html" target="_blank">instale agora</a>.</font>
			</div>
			<!--
			<STRONG>Todas as notas fiscais de devolução de peças enviadas em garantia deverão constar o número da nota fiscal de remessa.</STRONG>
			<font color='#dd0000'>Este procedimento visa assegurar o relacionamento entre as transações de peças que após 61 dias (Postos) e 90 dias (Distribuidores) serão faturadas quando não constarem em nossos controles.</font>
			<hr>-->

<!--
			<font color='#990000' size='2'><b>Informações sobre a coleta dos produtos trocados.</b></font><br/>
			<a href='comunicados/britania_Comunicado_Coleta_de_Produtos_Trocados.pdf' target='_blank'>Clique aqui</a><br/><br/>
			<font color='#000000' size='2'><b>Modelo de Autorização de coleta</b></font><br/>
			<a href='comunicados/britania_autorizacao_coleta.doc' target='_blank'>Clique aqui</a><br />
			<hr>
-->

			<img src='imagens/esclamachion1.gif'><br /><font color='#FF0000' size='2'><b>Obtenha mais informações sobre o novo sistema</b></font><br /><a href='pdf/sistema.pdf'>PDF</a><br /><a href='pdf/sistema.doc'>DOC</a><br /><a href='pdf/sistema.htm'>HTML</a>
			
			<? if ($audio == 'f') { ?>
			<hr><img src='imagens/esclamachion1.gif'><br /><font color='#FF0000' size='2'><b>Consulte o manual feito especialmente para você!</b></font><br /><a href='pdf/ajuda.pdf'>PDF</a><br /><a href='pdf/ajuda.doc'>DOC</a><br /><a href='pdf/ajuda.htm'>HTML</a>
			<? }else{ ?>
			<hr><img src='imagens/esclamachion1.gif'><br /><font color='#FF0000' size='2'><b>Consulte o manual feito especialmente para você!</b></font><br /><a href='pdf/ajuda_audio.pdf'>PDF</a><br /><a href='pdf/ajuda_audio.doc'>DOC</a><br /><a href='pdf/ajuda_audio.htm'>HTML</a>
			<? } ?>
			<hr><img src='imagens/esclamachion1.gif'><br /><font color='#FF0000' size='2'><b>Para valorizar ainda mais o seu serviço, estamos aumentando o valor das taxas de mão-de-obra</b></font><br /><a href='#doisreais'>saiba mais</a>
		</div>
	</div>

	<div id="rightCol">
	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
		<h3>Aqui os Postos Autorizados <b><? echo $login_fabrica_nome ?></b> podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.</h3>
	</div>
	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
		<a href="http://www.telecontrol.com.br/n_downloads.php">Downloads</a>
	</div>

	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
		<a href="http://www.telecontrol.com.br"><img src="image/parceiro.jpg" alt=""></a>
		<h3>A Telecontrol desenvolve sistemas totalmente destinados à Internet, com isto você tem acesso às informações de sua empresa de qualquer lugar, podendo tomar decisões gerenciais com total segurança. 
		</h3><br>
		<h3><a href="#">Clique aqui para saber mais.</a></h3>
	</div>
	</div>
		<?
		$sql = "SELECT * FROM tbl_posto JOIN tbl_posto_linha USING (posto) JOIN tbl_linha USING (linha) WHERE tbl_posto.posto = $login_posto AND tbl_linha.linha = 3 ";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {

			echo "<div><img name='novo_sistema' src='imagens/novo_sistema.gif' usemap='#m_novo_sistema' alt=''><hr></div>";

			echo "<div id='doisreais'><img src='imagens/britania_news_doisreais.gif'></div>";

		}
		?>
</div>
<map name='m_novo_sistema'>
<area shape="rect" coords="501,65,577,121" href="pdf/sistema.htm" target="_blank" alt="" >
<area shape="rect" coords="418,65,498,121" href="pdf/sistema.doc" target="_blank" alt="" >
<area shape="rect" coords="326,65,411,121" href="pdf/sistema.pdf" target="_blank" title="Clique para ver em Adobe Acrobat" alt="Clique para ver em Adobe Acrobat" >
<area shape="rect" coords="503,143,579,199" href="pdf/ajuda.htm" target="_blank" alt="" >
<area shape="rect" coords="420,143,500,199" href="pdf/ajuda.doc" target="_blank" alt="" >
<area shape="rect" coords="328,143,413,199" href="pdf/ajuda.pdf" target="_blank" title="Clique para ver em Adobe Acrobat" alt="Clique para ver em Adobe Acrobat" >
</map>
