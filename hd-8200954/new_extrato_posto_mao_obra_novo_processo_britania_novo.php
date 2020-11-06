<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_e_distribuidor == 't') {
	if ($login_posto <> 4311 AND $login_posto <>725){
		header ("Location: new_extrato_distribuidor.php");
		exit;
	}
}

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";
?>
<style>
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}

.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FE918D
}
.menu_top4 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #CC3333;
}
#comunicado{
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	color:#000000;
	border: 1px solid;
	width: 690;
}
</style>
<?
echo "<br><br><div id='comunicado'><center><b>ATENÇÃO:</b></center><br>Face à mudança exigida pela Receita Federal, surgiu a necessidade de adaptação da Britania com relação à forma de envio dos comprovantes para o pagamento da mão-de-obra.<br><br>Está sendo disponibilizada a tela primeiramente para que seja enviada a Britania somente os comprovantes “cópia das notas fiscais”, para realização de auditoria e após a liberação para emissão da nota fiscal de mão-de-obra. 
<br><br></div>";


?>
<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?

$sql = "SELECT	tbl_linha.nome AS linha_nome ,
				tbl_linha.linha              ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE NULL END) AS qtde                                            ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NOT NULL THEN 1 ELSE NULL END) AS qtde_recusada                                   ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                        ,
				tbl_extrato.total
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
					AND tbl_os.fabrica = $login_fabrica
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		$join_mao_de_obra
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia,tbl_extrato.total
		ORDER BY distrib_nome, tbl_linha.nome";
$res = pg_exec ($con,$sql);
echo @pg_result ($res,0,data_geracao);

echo "<table width='300' align='center' border='1' cellspacing='2'>";
echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >Distribuidor</td>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

$distribuidor_nome = pg_result ($res,distrib_nome);
if(strlen($distribuidor_nome) == 0){
	$distribuidor_nome = "Britânia";
}

$distribuidor_nome_ant = $distribuidor_nome;
for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$distribuidor_nome = pg_result ($res,$i,distrib_nome);
	if(strlen($distribuidor_nome) == 0){
		$distribuidor_nome = "Britânia";
	}

	if ($distribuidor_nome_ant <> $distribuidor_nome){
		echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center'>TOTAIS</td>";
		echo "<td align='center' nowrap >&nbsp;</td>";
		echo "<td align='center'>&nbsp;</td>";
		echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
		echo "<td align='center'>&nbsp;</td>";
		echo "</tr>";
		echo "<tr style='font-size: 10px; $color' $bgcolor>";

		if (date('Y-m-d') >= '2006-08-01' AND $login_fabrica == 3 AND $extrato > 58445 AND $distribuidor_britania == 1) {
			echo "<table align='center'>";
			echo "<tr>";
			?>
			<br>
			<table align="center" border="2" size="2" bgcolor="#FFCC33">
			<tr>
			<td align="center"><font size='+1' face='arial'>ENVIO DE DOCUMENTOS</td>
			<td align="center"><font size='+1' face='arial'>DESTINO</td>
			<td align="center"><font size='+1' face='arial'>FORMA DE ENVIO</td>
			</font>
			</tr>
			<tr>
			<td><font size='+1' face='arial'>Notas Fiscais de Serviço<br> e Ordens de Serviço</td>
			<td align='center'>
				Britânia Curitiba<br>
				Av. Nossa Senhora da Luz, 1330<br>
				Bairro: Hugo Lange<br>
				Curitiba - PR - CEP 82.520-060<br>
				CNPJ: 76.492.701/0001-57<br>
				I.E.: 10.503.415-65<br>
			</td>
			<td>Encomenda Normal-Correios,  <br>com ressarcimento pela Britânia (*)</td>
			</tr>
			</table>
			<br>
			<?
		}

		echo "<table width='300' align='center' border='1' cellspacing='2'>";
		echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center' nowrap >Distribuidor</td>";
		echo "<td align='center' nowrap >Linha</td>";
		echo "<td align='center' nowrap >M.O.Unit.</td>";
		echo "<td align='center' nowrap >Qtde</td>";
		echo "<td align='center' nowrap >Mão-de-Obra</td>";
		echo "<td align='center' nowrap >&nbsp;</td>";
		echo "</tr>";

		$total_qtde            = 0 ;
		$total_mo_posto        = 0 ;
		$total_mo_adicional    = 0 ;
		$total_adicional_pecas = 0 ;
		$distribuidor_nome_ant = $distribuidor_nome;
	}

	echo "<tr style='font-size: 10px'>";

	echo "<td nowrap >";
	echo $distribuidor_nome;
	echo "</td>";

	echo "<td nowrap >";
	echo pg_result ($res,$i,linha_nome);
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,qtde),0,',','.');
	echo "</td>";
	echo "</td>";
	
	$linha = pg_result ($res,$i,linha) ;

	echo "<td align='right' nowrap>";
	echo "<a href='new_extrato_posto_detalhe.php?extrato=$extrato&linha=$linha&mounit=$mounit'>ver O.S.</a>";
	echo "</td>";
	echo "</tr>";
	
	$total_qtde            += pg_result ($res,$i,qtde) ;
	$total_valor           += pg_result ($res,$i,total) ;

}

$sql = " SELECT
		extrato,
		historico,
		valor,
		admin,
		debito_credito,
		lancamento
	FROM tbl_extrato_lancamento
	WHERE extrato = $extrato
	AND fabrica = $login_fabrica
	/* hd 22096 */  /* HD 45942 */ 
	 AND (admin IS NOT NULL OR lancamento in (104,103)) 
	";

$res = pg_exec ($con,$sql);

if(pg_numrows($res) > 0){
	for($i=0; $i < pg_numrows($res); $i++){
		$extrato         = trim(pg_result($res, $i, extrato));
		$historico       = trim(pg_result($res, $i, historico));
		$valor           = trim(pg_result($res, $i, valor));
		$debito_credito  = trim(pg_result($res, $i, debito_credito));
		$lancamento      = trim(pg_result($res, $i, lancamento));

		if($debito_credito == 'D'){ 
			$bgcolor= "bgcolor='#FF0000'"; 
			$color = " color: #000000; ";
			if ($lancamento == 78 AND $valor>0){
				$valor = $valor * -1;
			}
		}else{ 
			$bgcolor= "bgcolor='#0000FF'";
			$color = " color: #FFFFFF; ";
		}
		
		if ($lancamento==103 or $lancamento==104) {
			$bgcolor= "bgcolor='#339900'";
		}

		echo "<tr style='font-size: 10px; $color' $bgcolor>";
		echo "<TD><b>Avulso</b></TD>";
		echo "<TD colspan='4'><b>$historico</b></TD>";

		echo "</tr>";
	}
}
echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center'>TOTAIS</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='center'>&nbsp;</td>";
echo "</tr>";
echo "</table>";
if(pg_numrows($res) > 0){
echo "<table>";
	echo "<tr>";
		echo "<td><BR></td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td bgcolor='#FF0000' width='15'>&nbsp;</td>";
		echo "<td style='font-size: 10px'>Débito</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td bgcolor='#0000FF' width='15'>&nbsp;</td>";
		echo "<td style='font-size: 10px'>Crédito</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td bgcolor='#339900' width='15'>&nbsp;</td>";
		echo "<td style='font-size: 10px'>Valores de ajuste de Extrato</td>";
	echo "</tr>";
echo "</table>";
}
//-------------------------------------------  mostra resumo apos conferencia
$sql3 = "SELECT  codigo 
		FROM    tbl_extrato_agrupado 
		WHERE   extrato = $extrato";
$res3 = pg_exec ($con,$sql3) ;
if (@pg_numrows($res3) > 0) {
$codigo_agrupado = pg_result($res3,0,codigo);
}

echo $codigo_agrupado ."<----------";

if (strlen($codigo_agrupado)>0){
	echo "<br>";
	echo$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
					tbl_linha.linha                      ,
					tbl_os_extra.mao_de_obra AS unitario ,
					tbl_sinalizador_os.acao              ,
					COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE NULL END) AS qtde                      ,
					SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
					tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
					SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
					SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
					to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
					distrib.nome_fantasia AS distrib_nome                                  ,
					distrib.posto    AS distrib_posto                                 
			FROM
				(SELECT tbl_os_extra.os 
				FROM tbl_os_extra 
				JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
				WHERE tbl_os_extra.extrato = $extrato
				) os 
			JOIN tbl_os_extra ON os.os = tbl_os_extra.os
			JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
			LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
			WHERE tbl_sinalizador_os.debito='N' and tbl_os.sinalizador != 4 
			GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao
			ORDER BY tbl_linha.nome";

	$res = pg_exec ($con,$sql);

	echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='5'>RESUMO DE CONFERÊNCIA PARA PAGAMENTO</TD></TR>";
	echo "<tr class='menu_top2'>";
	echo "<td align='center' nowrap >Sinalizador</td>";
	echo "<td align='center' nowrap >Linha</td>";
	echo "<td align='center' nowrap >M.O.Unit.</td>";
	echo "<td align='center' nowrap >Qtde</td>";
	echo "<td align='center' nowrap >Mão-de-Obra</td>";
	echo "</tr>";

	$total_qtde            = 0 ;
	$total_mo_posto        = 0 ;
	$total_mo_adicional    = 0 ;
	$total_adicional_pecas = 0 ;

	for($i=0; $i<pg_numrows($res); $i++){
		$acao_sinalizador             = pg_result ($res,$i,acao);
		$linha_nome        = pg_result ($res,$i,linha_nome);
		$unitario          = number_format(pg_result ($res,$i,unitario),2,',','.');
		$qtde              = number_format(pg_result ($res,$i,qtde),0,',','.');
		$mao_de_obra_a_pagar += number_format(pg_result ($res,$i,mao_de_obra_posto),2,',','.');
		$mao_de_obra_posto = number_format(pg_result ($res,$i,mao_de_obra_posto),2,',','.');
		$mao_de_obra_posto_unitaria = number_format(pg_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');

		$cor = "#FFFFFF";
		if ($i % 2 == 0) $cor = "#FEF2C2";

		echo "<tr bgcolor='$cor' style='font-size: 10px'>";

		echo "<td nowrap >";
		echo $acao_sinalizador;
		echo "</td>";

		echo "<td nowrap >";
		echo $linha_nome;
		echo "</td>";

		echo "<td  nowrap align='right'>";
		echo $unitario;
		echo "</td>";

		echo "<td  nowrap align='right'>";
		echo $qtde;
		echo "</td>";

		echo "<td  nowrap align='right'>";
		echo $mao_de_obra_posto;
		echo "</td>";

		echo "</tr>";


		$total_qtde            += pg_result ($res,$i,qtde) ;
		$total_mo_posto        += pg_result ($res,$i,mao_de_obra_posto) ;
		$total_mo_adicional    += pg_result ($res,$i,mao_de_obra_adicional) ;
		$total_adicional_pecas += pg_result ($res,$i,adicional_pecas) ;

	}


	$sql = " SELECT
			extrato,
			historico,
			valor,
			admin,
			debito_credito,
			lancamento
		FROM tbl_extrato_lancamento
		WHERE extrato = $extrato
		AND fabrica = $login_fabrica
		/* hd 22096 */ 
		AND (admin IS NOT NULL OR lancamento in (103,104))";

	$res = pg_exec ($con,$sql);

	echo "<INPUT TYPE='hidden' NAME='qtde_avulso' id='qtde_avulso' value=". pg_numrows($res) .">";
	$total_avulso = 0;

	if(pg_numrows($res) > 0){
		for($i=0; $i < pg_numrows($res); $i++){
			$extrato         = trim(pg_result($res, $i, extrato));
			$historico       = trim(pg_result($res, $i, historico));
			$valor           = trim(pg_result($res, $i, valor));
			$debito_credito  = trim(pg_result($res, $i, debito_credito));
			$lancamento      = trim(pg_result($res, $i, lancamento));
			
			if($debito_credito == 'D'){ 
				$bgcolor= "bgcolor='#FF0000'"; 
				$color = " color: #000000; ";
				if ($lancamento == 78 AND $valor>0){
					$valor = $valor * -1;
				}
			}else{ 
				$bgcolor= "bgcolor='#0000FF'";
				$color = " color: #FFFFFF; ";
			}

			//hd 22096 - lançamentos e Valores de ajuste de Extrato
			if ($lancamento==103 or $lancamento==104) {
				$bgcolor= "bgcolor='#339900'";
			}

			echo "<tr style='font-size: 10px; $color' $bgcolor>";
			echo "<TD><b>Avulso</b></TD>";
			echo "<TD colspan='3'><b>$historico</b></TD>";
			echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b>
			<INPUT TYPE='hidden' NAME='valor_avulso_$i' id='valor_avulso_$i' value='$valor'>
			</TD>";
			echo "</tr>";
			$total_avulso = $valor + $total_avulso;
		}
	}

	$total_nota	= ($mao_de_obra_a_pagar+$total_avulso);
	$valor_conferencia = $total_nota;

	echo "<TR class='menu_top2'><TD colspan='4'>TOTAL PARA PAGAMENTO</TD><TD align='right'>".number_format ($total_nota,2,",",".")."</TD></TR></table>";

	//------------------------------------resumo irregulares
	echo "<br>";
	$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
					tbl_linha.linha                      ,
					tbl_os_extra.mao_de_obra AS unitario ,
					tbl_sinalizador_os.acao              ,
					COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE 0 END) AS qtde                      ,
					SUM  ( tbl_os_extra.mao_de_obra          ) AS mao_de_obra_posto     ,
					tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
					SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
					SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
					to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
					distrib.nome_fantasia AS distrib_nome                                  ,
					distrib.posto    AS distrib_posto                                 
			FROM
				(SELECT tbl_os_extra.os 
				FROM tbl_os_extra 
				JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
				WHERE tbl_os_extra.extrato = $extrato
				) os 
			JOIN tbl_os_extra ON os.os = tbl_os_extra.os
			JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
			LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
			WHERE tbl_sinalizador_os.debito='S'
			GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao
			ORDER BY tbl_linha.nome";;

	$res = pg_exec ($con,$sql);

	echo "<form style='MARGIN: 0px; WORD-SPACING: 0px' name='frm_conferencia' method='post' action='$PHP_SELF?posto=$posto'>";
	echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='5'>RESUMO DE CONFERÊNCIA COM IRREGULARIDADE</TD></TR>";
	echo "<tr class='menu_top2'>";
	echo "<td align='center' nowrap >Sinalizador</td>";
	echo "<td align='center' nowrap >Linha</td>";
	echo "<td align='center' nowrap >M.O.Unit.</td>";
	echo "<td align='center' nowrap >Qtde</td>";
	echo "<td align='center' nowrap >Mão-de-Obra</td>";
	echo "</tr>";

	$total_qtde            = 0 ;
	$total_mo_posto        = 0 ;
	$total_mo_adicional    = 0 ;
	$total_adicional_pecas = 0 ;

	for($i=0; $i<pg_numrows($res); $i++){
		$acao_sinalizador             = pg_result ($res,$i,acao);
		$linha             = pg_result ($res,$i,linha);
		$linha_nome        = pg_result ($res,$i,linha_nome);
		$unitario          = number_format(pg_result ($res,$i,unitario),2,',','.');
		$qtde              = pg_result ($res,$i,qtde);
		$mao_de_obra_posto = number_format(pg_result ($res,$i,mao_de_obra_posto),2,',','.');
		$mao_de_obra_posto_unitaria = number_format(pg_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
		


		$cor = "#FFFFFF";
		if ($i % 2 == 0) $cor = "#FEF2C2";

		echo "<tr bgcolor='$cor' style='font-size: 10px'>";

		echo "<td nowrap >";
		echo $acao_sinalizador;
		echo "<input type='hidden' name='linha_$i' value='$linha'>";
		echo "</td>";

		echo "<td nowrap >";
		echo $linha_nome;
		echo "<input type='hidden' name='linha_$i' value='$linha'>";
		echo "</td>";

		echo "<td  nowrap align='right'>";
		echo $unitario;
		echo "<input type='hidden' name='unitario_$i' id='unitario_$i' value='$unitario'>";
		echo "</td>";

		echo "<td  nowrap align='right'>";
		echo $qtde;
		echo "<input type='hidden' name='qtde_$i' id='qtde_$i' value='$qtde'>";
		echo "</td>";

		echo "<td  nowrap align='right'>";
		echo $mao_de_obra_posto;
		echo "<input type='hidden' name='mao_de_obra_posto_$i' id='mao_de_obra_posto' value='$mao_de_obra_posto'>";
		echo "</td>";
		echo "</tr>";


	}
	echo "</table>";
}
//--------------------------------------- fim do resumo

if (date('Y-m-d') >= '2006-08-01' AND $login_fabrica == 3 AND $extrato > 58445 AND $distribuidor_nome_ant == "Britânia") {
	echo "<table align='center'>";
	echo "<tr>";
	?>
	<br>
	<table align="center" border="2" size="2" bgcolor="#FFCC33">
	<tr>
	<td align="center"><font size='+1' face='arial'>ENVIO DE DOCUMENTOS</td>
	<td align="center"><font size='+1' face='arial'>DESTINO</td>
	<td align="center"><font size='+1' face='arial'>FORMA DE ENVIO</td>
	</font>
	</tr>
	<tr>
	<td><font size='+1' face='arial'>Notas Fiscais de Serviço<br> e Ordens de Serviço</td>
	<td align='center'>
		Britânia Curitiba<br>
		Av. Nossa Senhora da Luz, 1330<br>
		Bairro: Hugo Lange<br>
		Curitiba - PR - CEP 82.520-060<br>
		CNPJ: 76.492.701/0001-57<br>
		I.E.: 10.503.415-65<br>
	</td>
	<td>Encomenda Normal-Correios,  <br>com ressarcimento pela Britânia (*)</td>
	</tr>
	</table>
	<br>
	<?
}
echo "<p align='center'>";
echo "<p>";
echo "<a href='os_extrato.php'>Outro extrato</a>";
?>
<p><p>
<? include "rodape.php"; ?>
