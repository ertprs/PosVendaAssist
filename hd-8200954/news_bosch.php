<?
$mes_inicial = trim(date("Y")."-".date("m")."-01");
$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

$sql = "SELECT tbl_extrato.extrato,
				tbl_extrato.aprovado
		FROM   tbl_extrato
		WHERE  tbl_extrato.posto = $login_posto
		AND    tbl_extrato.fabrica = $login_fabrica
		AND    tbl_extrato.aprovado BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59'";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {?>
	<div class='contentBlockMiddle' style='background-color: #FFCC00; width: 610 px; align: center'>
	<img src='imagens/esclamachion1.gif'><font size='3'>Existem novos extratos aprovados.</font>
	</div>
	<br>
<? } ?>


<?
##### NOVOS COMUNICADOS - INÍCIO #####
$data_inicial = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));
$data_final   = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

$sql = "SELECT tbl_comunicado.comunicado
		FROM   tbl_comunicado
		WHERE  tbl_comunicado.fabrica = $login_fabrica
		AND    ((tbl_comunicado.posto = $login_posto) OR (tbl_comunicado.posto IS NULL))
		AND    tbl_comunicado.data::date BETWEEN '$data_inicial' AND '$data_final'
		AND    tbl_comunicado.ativo IS TRUE 
		LIMIT 1";
$res = pg_exec($con,$sql);
# if ($ip == "201.0.9.216") echo nl2br($sql);
if (pg_numrows($res) > 0) {
	echo "<div class='contentBlockMiddle' style='background-color: #FFCC00; width: 610 px; align: center'>";
	echo "<font size='2'><B>Existe(m) novo(s) comunicado(s) no site.</B></font>";
	echo "</div>";
	echo "<br>";
}
##### NOVOS COMUNICADOS - FIM #####
?>

<div id="mainCol">
	<div id="leftCol">

		<div class="contentBlockLeft">
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
					AND    tbl_comunicado.ativo IS TRUE 
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
		<div class="contentBlockMiddle">
		</div>

		<div class="contentBlockMiddle">
		</div>

<!--	<div class="contentBlockLeft">
			<font color='#FF0000' size='3'>
			<b>PARTICIPE DA PROMOÇÃO</b>
			<br />
			<a href='comunicados/Dynacom_Comunicado_Promocao.doc'>"Acesse, cadastre e ganhe"</font><br>
			Clique aqui e leia o comunicado</a><br><br>
			<a href='comunicados/Dynacom_PROMOCAO_ACESSE.doc'>Clique aqui e leia o regulamento da promoção</a><br><br>
		</div>-->

		<div class="contentBlockLeft">
		</div>
		
		<div class="contentBlockLeft">
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
		</h3><br>
		<h3><a href="#">Clique aqui para saber mais.</a></h3>
	</div>
	</div>
</div>
