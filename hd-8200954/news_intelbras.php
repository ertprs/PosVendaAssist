<div id="mainCol">
	<div class="contentBlockLeft" style='background-color: #FFCC00;'>
			<?
				if ($login_fabrica == 3){
					$sql = "SELECT	count(*)
							FROM	tbl_pedido
							WHERE	distribuidor = $login_posto
							AND		(pedido_atendido_total is false OR pedido_atendido_total is null)";
					$res = pg_exec($con,$sql);

					if (pg_result($res,0,0) > 0){
						echo "<img src='imagens/esclamachion1.gif'><a href='pedido_posto_relacao.php'>Confira os Pedidos para Atendimento via Distribuidor.<br>".pg_result($res,0,0)." pedidos encontrados.</a>";
					}
				}
			?>			

	</div>

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


	<div id="leftCol" bgcolor='#FFCC66'>
		<div class="contentBlockLeft">
			<img src='imagens/information.gif'>

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


			$sql = "SELECT  tbl_comunicado.comunicado                        ,
							to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
							tbl_comunicado.descricao                         ,
							tbl_produto.descricao as descricao_produto       
					FROM    tbl_comunicado
					LEFT JOIN    tbl_produto USING (produto)
					LEFT JOIN    tbl_linha   on tbl_linha.linha = tbl_produto.linha
					WHERE   tbl_comunicado.fabrica = $login_fabrica
					AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
					AND    ((tbl_comunicado.posto = $login_posto) OR (tbl_comunicado.posto IS NULL))
					ORDER BY tbl_comunicado.data DESC
					LIMIT 10";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
					$comunicado = trim(pg_result($res,$x,comunicado));
					$data       = trim(pg_result($res,$x,data));
					$produto	= trim(pg_result($res,$x,descricao_produto));
					$descricao  = trim(pg_result($res,$x,descricao));
					
					echo "<a href='comunicado_mostra.php?comunicado=$comunicado'>$data</a><br><font size='-2'><b>$produto</b></font><br/><a href='comunicado_mostra.php?comunicado=$comunicado'>$descricao</a><hr />";
				}
			}
			?>
		</div>

	</div>
	<div id="middleCol">
		<div class='contentBlockMiddle'>
			<img src='imagens/esclamachion1.gif'><br /><font color='#FF0000' size='2'><b>Obtenha mais informações sobre o novo sistema</b></font>
			<hr><img src='imagens/esclamachion1.gif'><br /><font color='#FF0000' size='2'><b>Consulte o manual feito especialmente para você!</b></font><br /><a href='pdf/Intelbras_Manual_Assist.pdf'>PDF</a><br />
		</div>
	</div>

	<div id="rightCol">
	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
		<h3>Aqui os Postos Autorizados <b><? echo $login_fabrica_nome ?></b> podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.</h3>

	</div>

	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
		<a href="http://www.telecontrol.com.br"><img src="image/parceiro.jpg" alt=""></a>
		<h3>A Telecontrol desenvolve sistemas totalmente destinados à Internet, com isto você tem acesso às informações de sua empresa de qualquer lugar, podendo tomar decisões gerenciais com total segurança. 
		</h3>
<!-- 		<br><h3><a href="#">Clique aqui para saber mais.</a></h3> -->
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