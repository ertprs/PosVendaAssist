<div id="mainCol">
	<div id="leftCol">

		<div class="contentBlockLeft">
			<IMG SRC="imagens/novidades_180x50.gif" ALT="">
			<!-- Insira aqui o texto de sua escolha -->
			<?

//PEGA O TIPO DO POSTO
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


			/*
			$sql = "SELECT  tbl_comunicado.comunicado                        ,
							to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
							tbl_comunicado.descricao
					FROM    tbl_comunicado
					JOIN    tbl_produto USING (produto)
					JOIN    tbl_linha   USING (linha)
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_comunicado.data DESC
					LIMIT 10";*/
					$sql = "SELECT  tbl_comunicado.comunicado                        ,
							to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
							tbl_comunicado.descricao                         ,
							tbl_produto.descricao as descricao_produto       
					FROM    tbl_comunicado
					LEFT JOIN    tbl_produto USING (produto)
					LEFT JOIN    tbl_linha   on tbl_linha.linha = tbl_produto.linha
					WHERE   tbl_comunicado.fabrica = $login_fabrica
					AND     ((tbl_comunicado.posto = $login_posto) OR (tbl_comunicado.posto IS NULL))
					AND    tbl_comunicado.ativo IS TRUE 
					ORDER BY tbl_comunicado.data DESC
					LIMIT 10";
					
					
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
					$comunicado = trim(pg_result($res,$x,comunicado));
					$data       = trim(pg_result($res,$x,data));
					$descricao  = trim(pg_result($res,$x,descricao));
					
					echo "<h3><a href='comunicado_mostra.php?comunicado=$comunicado'>$data</a><br><a href='comunicado_mostra.php?comunicado=$comunicado'>$descricao</a></h3>";
				}
			}
			?>
		</div>
	</div>
	<div id="middleCol">
	<div class="contentBlockMiddle">
		<!-- Insira aqui o texto de sua escolha -->
		<img src="imagens/handy2.gif">
	</div>
	
	<div class="contentBlockMiddle">
		<!-- Insira aqui o texto de sua escolha -->
	</div>

	</div>
	<div id="rightCol">

	<div class="contentBlockRight">
		<IMG SRC="imagens/informacoes_180x50.gif" ALT="">
		<!-- Insira aqui o texto de sua escolha -->
		<h3>Aqui os Postos Autorizados podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.</h3>

	</div>
	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
		<a href="http://www.telecontrol.com.br"><img src="image/parceiro.jpg" alt=""></a>
		<h3>A Telecontrol desenvolve sistemas totalmente destinados à Internet, com isto você tem acesso às informações de sua empresa de qualquer lugar, podendo tomar decisões gerenciais com total segurança. 
		</h3><br>
		<h3><a href="#">Clique aqui para saber mais.</a></h3>
	</div>
	</div>
</div>
