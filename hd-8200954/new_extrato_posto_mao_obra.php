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

$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		distribuidor,
		NULL as produto_acabado,
		NULL as devolucao_obrigatoria
	FROM tbl_faturamento
	WHERE posto IN (13996,4311)
	AND distribuidor=$login_posto
	AND fabrica=$login_fabrica
	AND extrato_devolucao=$extrato
	ORDER BY faturamento ASC";
$res = pg_exec ($con,$sql);
$jah_digitado=pg_numrows ($res);
if ($login_posto <> 4311){
	if ($jah_digitado==0){
		$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NULL";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			header ("Location: new_extrato_posto.php?msg_erro=405");
			exit;
		}
	}
}

$sql = " SELECT extrato
		FROM tbl_extrato 
		WHERE extrato = $extrato ";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
	header ("Location: new_extrato_posto_mao_obra_novo.php?extrato=$extrato");
	exit;
}
$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";
?>
<style>
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
$sql = " SELECT     tbl_posto_linha.linha         ,
					tbl_posto.posto               ,
					tbl_posto_linha.distribuidor
			FROM   tbl_posto
			JOIN   tbl_posto_linha using (posto)
			JOIN   tbl_linha using (linha)
			WHERE  tbl_posto.posto = $login_posto
			AND    tbl_linha.fabrica = $login_fabrica limit 4  ";

$res = pg_exec($con, $sql);

$total = pg_numrows($res);
if($total>1){
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		//echo //pg_result($res,$i,linha)."-".pg_result($res,$i,posto)."-".pg_result($res,$i,distribuidor);
		echo "<br>";
		if (strlen(pg_result ($res,$i,distribuidor))>1) {
			$distribuidor_outro = 1;
		}else{
			$distribuidor_britania = 1;
		}
	}
}ELSE{
	$distribuidor_britania = 1;
}
?>
<? if ($login_fabrica == 3) { // HD 61576
	echo "<div id='comunicado'><center><b>ATENÇÃO:</b></center><br>Devido a exigências das novas regulamentações da Receita Federal, incluindo a implantação do<br>SPED Fiscal, Contábil e Nota Fiscal Eletrônica, não poderão ocorrer pagamentos divergentes dos<br>valores definidos em Nota Fiscal de prestação de serviço.<br>Os postos autorizados que enviarem documentações divergentes e que não comprovem o valor<br>discriminado na Nota Fiscal terão os lotes devolvidos.<br>Portanto, certifiquem-se de que as quantidades de comprovações sejam exatamente as definidas pelo<br>valor de mão-de-obra, caso contrário os pagamentos serão atrasados pelos trâmites de devolução e<br>acerto de Nota Fiscal, além de transtornos ao posto autorizado pelo cancelamento dessas Notas<br>Fiscais incorretas.<br><a href='http://br.com.telecontrol.posvenda-downloads.s3.amazonaws.com/comunicados/003/89973.pdf' target='_blank'><u>PROCESSOS DE CONFERÊNCIA E LIBERAÇÃO DE PAGAMENTOS</u></a><br></div>";
}

?>
<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?
if($login_fabrica==25 OR $login_fabrica==51){//HD 28111 15/8/2008
	$sql_mao_de_obra  = " SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os.mao_de_obra    ELSE 0 END) AS mao_de_obra_posto     , ";
	$join_mao_de_obra = " JOIN tbl_os ON tbl_os.os = tbl_os_extra.os ";

}else{
	$sql_mao_de_obra = " SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra    ELSE 0 END) AS mao_de_obra_posto     , ";
}

$sql = "SELECT	tbl_linha.nome AS linha_nome ,
				tbl_linha.linha              ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE NULL END) AS qtde                                            ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NOT NULL THEN 1 ELSE NULL END) AS qtde_recusada                                   ,
				$sql_mao_de_obra
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
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
#echo nl2br($sql);
echo @pg_result ($res,0,data_geracao);

echo "<table width='300' align='center' border='1' cellspacing='2'>";
echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >Distribuidor</td>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusadas</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
if ($login_fabrica == 51) echo "<td align='center' nowrap >Total NF</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

$distribuidor_nome = pg_result ($res,distrib_nome);
if(strlen($distribuidor_nome) == 0){
	if($login_fabrica==25)     $distribuidor_nome = "HBTECH";
	elseif($login_fabrica==51) $distribuidor_nome = "Gama Italy";
	else                       $distribuidor_nome = "Britânia";
}

$distribuidor_nome_ant = $distribuidor_nome;
for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$distribuidor_nome = pg_result ($res,$i,distrib_nome);
	if(strlen($distribuidor_nome) == 0){
		if($login_fabrica==25)     $distribuidor_nome = "HBTECH";
		elseif($login_fabrica==51) $distribuidor_nome = "Gama Italy";
		else                       $distribuidor_nome = "Britânia";
	}

////
	if ($distribuidor_nome_ant <> $distribuidor_nome){
		echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center'>TOTAIS</td>";
		echo "<td align='center' nowrap >&nbsp;</td>";
		echo "<td align='center'>&nbsp;</td>";
		echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
		echo "<td align='right'>" . number_format ($total_qtde_recusada  ,0,",",".") . "</td>";
		echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";
		echo "<td align='center'>&nbsp;</td>";
		echo "</tr>";
		echo "<tr style='font-size: 10px; $color' $bgcolor>";

		if($login_fabrica==3){
			if($distribuidor_nome_ant <> "Britânia"){
				echo "<br>";
				echo "<table align='center' border='2' size='2' bgcolor='#FFCC33'>";
				echo "<tr><td align='center'>";
				echo "ENVIAR AS ORDENS DE SERVIÇO E A NOTA <br>
				FISCAL PARA O SEU DISTRIBUIDOR ACIMA";
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				echo "<br>";
				echo "<br>";
				echo "<br>";
			}else{
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
			}
		}


		echo "<table width='300' align='center' border='1' cellspacing='2'>";
		echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center' nowrap >Distribuidor</td>";
		echo "<td align='center' nowrap >Linha</td>";
		echo "<td align='center' nowrap >M.O.Unit.</td>";
		echo "<td align='center' nowrap >Qtde</td>";
		echo "<td align='center' nowrap >Recusadas</td>";
		echo "<td align='center' nowrap >Mão-de-Obra</td>";
		#echo "<td align='center' nowrap >Pago via</td>";
		echo "<td align='center' nowrap >&nbsp;</td>";
		echo "</tr>";

		$total_qtde            = 0 ;
		$total_mo_posto        = 0 ;
		$total_mo_adicional    = 0 ;
		$total_adicional_pecas = 0 ;
		$distribuidor_nome_ant = $distribuidor_nome;
	}
////
	echo "<tr style='font-size: 10px'>";

	echo "<td nowrap >";
	echo $distribuidor_nome;
	echo "</td>";

	echo "<td nowrap >";
	echo pg_result ($res,$i,linha_nome);
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,unitario),2,',','.');
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,qtde),0,',','.');
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,qtde_recusada),0,',','.');
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,mao_de_obra_posto),2,',','.');
	echo "</td>";
	
	if ($login_fabrica == 51) { // HD 60841
		echo "<td  nowrap align='right'>";
		echo number_format (pg_result ($res,$i,total),2,',','.');
		echo "</td>";
	}
#	echo "<td  nowrap align='center'>";
#	$distrib_nome = pg_result ($res,$i,distrib_nome) ;
#	if (strlen ($distrib_nome) == 0) $distrib_nome = "<b>FABR.</b>";
#	echo $distrib_nome;
#	echo "</td>";

	$linha = pg_result ($res,$i,linha) ;
	$mounit = pg_result ($res,$i,unitario) ;

	echo "<td align='right' nowrap>";
	echo "<a href='new_extrato_posto_detalhe.php?extrato=$extrato&linha=$linha&mounit=$mounit'>ver O.S.</a>";
	echo "</td>";
	echo "</tr>";
	
	$total_qtde            += pg_result ($res,$i,qtde) ;
	$total_qtde_recusada   += pg_result ($res,$i,qtde_recusada);
	$total_mo_posto        += pg_result ($res,$i,mao_de_obra_posto) ;
	$total_mo_adicional    += pg_result ($res,$i,mao_de_obra_adicional) ;
	$total_adicional_pecas += pg_result ($res,$i,adicional_pecas) ;
	$total_valor           += pg_result ($res,$i,total) ;

}
//alterado HD 7261 7/11/2007 (se alterar aqui tem que mudar em baixo)
if($login_fabrica == 3){
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
#HD 45942
/*Estava sem o 103, acrescentei...*/

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
			
			//hd 22096 - lançamentos e Valores de ajuste de Extrato
			if ($lancamento==103 or $lancamento==104) {
				$bgcolor= "bgcolor='#339900'";
			}

			echo "<tr style='font-size: 10px; $color' $bgcolor>";
			echo "<TD><b>Avulso</b></TD>";
			echo "<TD colspan='4'><b>$historico</b></TD>";
			echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b></TD>";
			echo "<TD>&nbsp;</TD>";
			echo "</tr>";
			$total_mo_posto = $valor + $total_mo_posto;
		}
	}
}
//----------
echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center'>TOTAIS</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_qtde_recusada  ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";

#echo "<td align='center'>&nbsp;</td>";
if ($login_fabrica == 51) { // HD 60841
	echo "<td align='right'>" . number_format ($total_valor       ,2,",",".") . "</td>";
}
echo "<td align='center'>&nbsp;</td>";
echo "</tr>";
echo "</table>";

//alterado HD 7261 7/11/2007 (alterar aqui tambem)
if($login_fabrica == 3){
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

		//hd 22096
		echo "<tr>";
			echo "<td bgcolor='#339900' width='15'>&nbsp;</td>";
			echo "<td style='font-size: 10px'>Valores de ajuste de Extrato</td>";
		echo "</tr>";
	echo "</table>";
	}
}
//-----------

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
}else{
	if ($login_fabrica==25) {//HD 28111 15/8/2008
		echo "<BR>";
		echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
		echo "<tr class='table_line3'>\n";
		echo "<td class='menu_top4'>";
		echo "<div align='center' style='font-size:16px'>ATENÇÃO</div>";
		echo "</td></tr>";
		echo "<tr class='table_line3'>\n";
		echo "<td align=\"center\"><B>EMITIR E ENVIAR NOTA FISCAL DE MÃO DE OBRA JUNTO COM AS OS's PARA:</B><BR>
		HB ASSISTÊNCIA TÉCNICA LTDA.<br>
		Av. Yojiro Takaoka, 4.384 - Conj. 2156 - Loja 17 - Alphaville<br>
		Santana de Parnaíba, SP, CEP 06.541-038<br>
		CNPJ: 08.326.458/0001-47 </td>\n";
		echo "</tr>\n";
		/*echo "<tr class='table_line3'>\n";
		echo "<td align=\"center\"><B>ENVIAR AS OS's JUNTO COM A NF DE MÃO DE OBRA PARA:</b><BR>
		HBFLEX S.A.<br>
		Av. Yojiro Takaoka, 4.384 - Conj. 2156 - Loja 17 - Alphaville<br>
		Santana de Parnaíba, SP, CEP 06.541-038<br>
		CNPJ: 08.326.458/0001-47 </td>\n";*/
		echo "</table>";
	}elseif($login_fabrica==51){
		echo "<BR>";
		echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
		echo "<tr class='table_line3'>\n";
		echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE MÃO DE OBRA PARA:</B><BR>
		BRASVINCI COMÉRCIO DE ACESSÓRIOS E EQUIPAMENTOS DE BELEZA LTDA.<br>
		Rua Bogaert, 152 - Vila Vermelha, SP, CEP 04.298-020<br>
		CNPJ: 07.881.054/0001-52<br>
		IE: 149.256.240-117
		</td>\n";
		echo "</tr>\n";
		echo "<tr class='table_line3'>\n";
		echo "<td align=\"center\"><B>ENVIAR AS OS's JUNTO COM A NF DE MÃO DE OBRA PARA:</b><BR>
		TELECONTROL NETWORKING LTDA.<br>
		AV. Carlos Artêncio, 420 B - Fragata C<br>
		Marília, SP, CEP 17519-255 <br>
		CNPJ: 04.716.427/0001-41 <br></td>\n";
		echo "</tr>\n";
		echo "</table>";
	}else{
		echo "<br>";
		echo "<table align='center' border='2' size='2' bgcolor='#FFCC33'>";
		echo "<tr><td align='center'>";
		echo "ENVIAR AS ORDENS DE SERVIÇO E A NOTA <br>FISCAL PARA O SEU DISTRIBUIDOR ACIMA";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
}

echo "<p align='center'>";
#echo "<font style='font-size:12px'>Enviar Nota Fiscal de Prestação de Serviços para o fabricante<br>no valor de <b>R$ " . trim (number_format ($total_mo_posto + $total_mo_adicional + $total_adicional_pecas,2,",",".")) . "</b> descontados os tributos na forma da Lei.";

echo "<p>";
echo "<a href='os_extrato.php'>Outro extrato</a>";

#echo "<p>";
#echo "<a href='new_extrato_posto_retornaveis.php?extrato=$extrato'>Peças Retornáveis</a>";

?>

<p><p>

<? include "rodape.php"; ?>
