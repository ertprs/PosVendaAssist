<div id="mainCol">
	<div id="leftCol" bgcolor='#FFCC66'>

		<div class="contentBlockLeft" align='left'>
			<table>
				<tr>
				<td><img border="0" src="imagens/esclamachion1.gif"></td>
				<td align="center"><a href="http://www.telecontrol.com.br/bd/index2.php" class='menu' target="_blank"><font color="#FF0000">Sistema Antigo</font></a></td>
				</tr>
			</table>
			<br>
			<center><a href="http://www.telecontrol.com.br/bd/index2.php" class='menu' target="_blank"><font color="#FF0000">Para lan�ar suas OSs pendentes e consultas no sistema antigo, clique aqui.</font><br>(Dispon�vel somente no m�s de setembro para finalizar todas as pend�ncias anteriores)</font></a></center>
		</div>

		<div class="contentBlockLeft" align='left'>
			<center><a href="promocao.php" class='menu'><font color="#FF0000">PROMO��ES</font></a></center>
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
			<a href="http://www.blackdecker.com.br/xls/calendario_fechamento.xls" target="_blank"><b>CALEND�RIO FISCAL</b></a>
			<br>
			<h3>Para uma maior programa��o dos pedidos de pe�as e  acess�rios, consulte o nosso <b><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'>Calend�rio Fiscal</a></b>, que cont�m a data limite para o envio de pedidos para a Black & Decker na semana do fechamento, <font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b>per�odo do m�s que n�o recebemos pedidos e n�o faturamos</b>.</h3>
		</div>
	</div>
	
	<div id="rightCol">
		<div class="contentBlockRight">
			<!-- Insira aqui o texto de sua escolha -->
			<a href="http://www.telecontrol.com.br/x_downloads.php" target="_blank">Clique aqui para baixar a vers�o offline do sistema Assist. (Lan�amentos sem necessidade de conex�o permanente � internet)</a>
		</div>
		<div class="contentBlockRight">
			<center><a href='peca_faltante.php' class='menu'>Informe a Black & Decker</a></center><br>
			<h3>Informe a Black & Decker quais equipamentos est�o parados em sua oficina por falta de pe�as.</h3>

<!-- 			<h3>Aqui os Postos Autorizados <b><? echo $login_fabrica_nome ?></b> podem efetuar o lan�amento de Ordens de Servi�o em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa atrav�s do Fale Conosco, ficar a par de lan�amentos de produtos e promo��es entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Servi�o.</h3> -->
		</div>
		</div>

	<div id="leftCol" bgcolor='#FFCC66'>
		<div class="contentBlockRight">
<style>
td {font-size:12px}
</style>
			<TABLE border='0' width="610">
			<TR>
				<TD colspan="3" bgcolor="#eeeeee">
					<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><b> :: FALE CONOSCO</b></font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:mipereira@blackedecker.com.br'><b>MIGUEL PEREIRA</b></a><br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Gerente de Assist�ncia T�cnica.<br>
						MiPereira@blackedecker.com.br<br>
						FONE (34) 3318-3011
					</font>
				</TD>
				<TD>
					<a href='mailto:silvania_silva@blackedecker.com.br'><b>SILVANIA ALVES</b></a><br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Supervisor de Assist�ncia T�cnica.<br>
						silvania_silva@blackedecker.com.br<br>
						FONE (34) 3318-3025
					</font>
				</TD>
				<TD>
					<a href='mailto:rogerio_berto@blackedecker.com.br'><b>ROG�RIO BERTO</b></a><br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Analista T�cnico e Elabora��o <br> de Vistas Explodidas.<br>
						rogerio_berto@blackedecker.com.br<br>
						FONE (34) 3318-3023
					</font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:ueris@blackedecker.com.br'><b>ULISSES REIS</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Orienta��o T�cnica e Especialista <br> em Treinamento T�cnico.<br>
						ureis@blackedecker.com.br<br>
						FONE (34) 3318-3906
					</font>
				</TD>
				<TD>
					<a href='mailto:llaterza@blackedecker.com.br'><b>LILIAN LATERZA</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Analista de Faturamento e Revenda.<br>
						llaterza@blackedecker.com.br<br>
						FONE (34) 3318-3924
					</font>
				</TD>
				<TD>
					<a href='mailto:rfernandes@blackedecker.com.br'><b>R�BIA FERNANDES</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Analista de Faturamento.<br>
						rfernandes@blackedecker.com.br<br>
						FONE (34) 3318-3024
					</font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:faoliveira@blackedecker.com.br'><b>FAB�OLA OLIVEIRA</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Acompanhamento e aprova��o dos extratos para pagamento em garantia.<br>
						faoliveira@blackedecker.com.br<br>
						FONE (34) 3318-3921
					</font>
				</TD>
				<TD>
					<a href='mailto:cschafer@blackedecker.com.br'><b>CHRISTOPHER SCHAFER</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Coordenador de postos autorizados (Nomea��o e Cancelamento de oficinas).<br>
						cschafer@blackedecker.com.br<br>
						FONE (34) 3318-3922
					</font>
				</TD>
				<TD>
					<a href='mailto:mclemente@blackedecker.com.br'><b>MICHEL CLEMENTE</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Atendimento ao Assistente T�cnico.<br>
						mclemente@blackedecker.com.br<br>
						FONE (34) 3318-3920
					</font>
				</TD>
			</TR>
			<TR>
				<TD>
					<a href='mailto:jnardo@blackedecker.com.br'><b>JOHNY NARDO</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Atendimento ao Assistente T�cnico.<br>
						jnardo@blackedecker.com.br<br>
						fone (34) 3318-3037
					</font>
				</TD>
				<TD>
					<a href='mailto:pmachado@blackedecker.com.br'><b>PATR�CIA MACHADO</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Suporte ao SAC - Servi�o de Atendimento ao Consumidor.<br>
						pmachado@blackedecker.com.br<br>
						FONE (34) 3318-3012
					</font>
				</TD>
				<TD>
					<a href='mailto:samaral@blackedecker.com.br'><b>SABRINA AMARAL</b></a></br>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
						Satisfa��o 30 dias DeWalt e <br> Troca de Produtos.<br>
						samaral@blackedecker.com.br<br>
						FONE (34) 3318-3020
					</font>
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