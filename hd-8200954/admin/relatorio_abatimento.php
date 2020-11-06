<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($HTTP_GET_VARS["posto"]) > 0) {
	$codigo_posto = trim($HTTP_GET_VARS["posto"]);
}

if (strlen($HTTP_GET_VARS["data_inicial"]) > 0) {
	$data_inicial = trim($HTTP_GET_VARS["data_inicial"]);
	$data_inicial = substr($data_inicial,6,4) ."-". substr($data_inicial,3,2) ."-". substr($data_inicial,0,2);
}

if (strlen($HTTP_GET_VARS["data_final"]) > 0) {
	$data_final = trim($HTTP_GET_VARS["data_final"]);
	$data_final = substr($data_final,6,4) ."-". substr($data_final,3,2) ."-". substr($data_final,0,2);
}

$sql = "SELECT  tbl_posto_fabrica.posto       ,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome
		FROM    tbl_posto
		JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		WHERE   tbl_posto_fabrica.fabrica      = $login_fabrica
		AND     tbl_posto_fabrica.codigo_posto = $codigo_posto;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$posto        = trim(pg_result($res,0,posto));
	$codigo_posto = trim(pg_result($res,0,codigo_posto));
	$nome_posto   = trim(pg_result($res,0,nome));
	$relatorio = "ok";
}


$title = "Telecontrol - Relatório de Abatimento";
include "cabecalho.php";
?>

<p>


<? if ($relatorio == "ok") { ?>

<table width="630" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#CCCCCC">
<tr>
	<td height="27" valign="middle" align="center" colspan="3" background="assets/netscapebrowser.gif" bgcolor="#FFFFFF">
		<b><font face="Arial, Helvetica, sans-serif" color="#FFFFCC">
		Relatório de Abatimento
		</font></b>
	</td>
</tr>
</table>

<!-- CABECALHO DO RELATÓRIO -->

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#CCCCCC">
	<td height="1" width="10" src="imagens/spacer.gif"></td>
	<td><font size="2" face="Geneva, Arial, Helvetica, san-serif">Linha:</font></td>
	<td><font size="2" face="Geneva, Arial, Helvetica, san-serif">ÁUDIO</font></td>
	<td>&nbsp;</td>
	<td align="right"><font size="2" face="Geneva, Arial, Helvetica, san-serif">São José dos Pinhais, <? 		echo date ("d") . " de " ;
		switch (date ("m")) {
			case "01": echo "Janeiro";      break;
			case "02": echo "Fevereiro";    break;
			case "03": echo "Março";        break;
			case "04": echo "Abril";        break;
			case "05": echo "Maio";         break;
			case "06": echo "Junho";        break;
			case "07": echo "Julho";        break;
			case "08": echo "Agosto";       break;
			case "09": echo "Setembro";     break;
			case "10": echo "Outubro";      break;
			case "11": echo "Novembro";     break;
			case "12": echo "Dezembro";     break;
		}
		echo " de " . date ("Y") . "." ;
		?> 
		</font></td>
</table>

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#CCCCCC">
	<td height="1" width="270" src="assets/spacer.gif"></td>
	<td><hr></td>
</table>

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#CCCCCC">
	<tr>
		<td height="1" width="10" src="assets/spacer.gif">&nbsp;</td>
		<td align="left"><img src="assets/spacer.gif" height="1" width="260"><br><font size="-2" face="Geneva, Arial, Helvetica, san-serif"><? echo $codigo_posto ."-". $nome_posto ?></font></td>
		<td align="left"><font size="2" face="Geneva, Arial, Helvetica, san-serif">At.:Setor de Contas &agrave; pagar</font>
	</tr>
	<tr>
		<td height="1" width="10" src="assets/spacer.gif">&nbsp;</td>
		<td align="left">&nbsp;</td>
		<td align="left"><font size="2" face="Geneva, Arial, Helvetica, san-serif">Atrav&eacute;s desta comunicamos que as nossas notas fiscais abaixo relacionadas sofreram abatimento igual ao valor de suas notas fiscais</font></td>
	</tr>
</table>

<!-- Aqui começa a área de CRÉDITOS -->

<?
$sql = "SELECT  to_char(tbl_devolucao.data_emissao, 'DD/MM/YYYY') AS data_emissao,
				LPAD (TRIM (tbl_devolucao.nota_fiscal),6,'0') AS nota_fiscal     ,
				tbl_devolucao.valor_total                                        ,
				tbl_devolucao_britania.tipo
		FROM    tbl_devolucao
		JOIN    tbl_devolucao_britania USING (devolucao)
		WHERE   tbl_devolucao.posto = $posto
		AND     (tbl_devolucao_britania.tipo IN ('AT','AU','AL') )
		AND     tbl_devolucao_britania.representante = '870'
		AND     tbl_devolucao.data_emissao BETWEEN '$data_inicial' AND '$data_final'
		UNION
		SELECT  to_char(tbl_devolucao.data_emissao, 'DD/MM/YYYY') AS data_emissao,
				tbl_devolucao.nota_fiscal                                        ,
				tbl_devolucao.valor_total                                        ,
				'Audio' AS tipo
		FROM    tbl_devolucao
		LEFT JOIN tbl_devolucao_britania USING (devolucao)
		WHERE   tbl_devolucao.posto = $posto
		AND     tbl_devolucao_britania.devolucao IS NULL
		AND     tbl_devolucao.data_emissao BETWEEN '$data_inicial' AND '$data_final'
";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='500' border='0' cellspacing='0' cellpadding='0' align='center'>";
	echo "<tr>";
	
	echo "<td background='assets/netscapebrowser.gif' align='center'>";
	echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'><b>SEUS CRÉDITOS</b></font>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "<table width='500' border='0' cellspacing='0' cellpadding='0' align='center' bgcolor='#CCCCCC'>";
	echo "<tr>";
	
	echo "<td><img src='assets/spacer.gif' height='1' width='15'><br>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='80'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>Data do Lançamento</b></font></td>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='120'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>NF Devolução</b></font></td>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='50'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>Tipo NF</b></font></td>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='80'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>Valor</b></font></td>";
	echo "<td><img src='assets/spacer.gif' height='1' width='15'><br>";
	
	echo "</tr>";

	echo "<tr><td></td><td colspan='4'><hr></td><td></td></tr>";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$data_emissao = trim(pg_result($res,$i,data_emissao));
		$nota_fiscal  = trim(pg_result($res,$i,nota_fiscal));
		$tipo         = trim(pg_result($res,$i,tipo));
		$valor_total  = trim(pg_result($res,$i,valor_total));
		$soma_credito = $soma_credito + $valor_total;

		echo "<tr>";

		echo "<td></td>";
		echo "<td align='center'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$data_emissao</font></td>";
		echo "<td align='center'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$nota_fiscal</font></td>";
		echo "<td align='center'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$tipo</font></td>";
		echo "<td align='right'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>". number_format($valor_total,2,",",".") ."</font></td>";
		echo "<td></td>";

		echo "</tr>";
	}
	echo "</table>";
}
?>


<!-- Aqui começa a área de DÉBITOS -->

<?
$sql = "SELECT  to_char(tbl_devolucao.data_emissao, 'DD/MM/YYYY') AS data_emissao,
				LPAD (TRIM (tbl_devolucao.nota_fiscal),6,'0') AS nota_fiscal     ,
				tbl_devolucao_britania.tipo                                      ,
				tbl_devolucao.valor_total
		FROM    tbl_devolucao
		JOIN    tbl_devolucao_britania ON tbl_devolucao_britania.devolucao = tbl_devolucao.devolucao
		WHERE   tbl_devolucao.posto                  = $posto
		AND     (tbl_devolucao_britania.tipo         = 'DP'
		OR      tbl_devolucao_britania.tipo          = 'IM')
		AND     tbl_devolucao_britania.representante = '870'
		AND     tbl_devolucao.data_emissao BETWEEN '$data_inicial' AND '$data_final';";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='500' border='0' cellspacing='0' cellpadding='0' align='center'>";
	echo "<tr>";
	
	echo "<td background='assets/netscapebrowser.gif' align='center'>";
	echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'><b>SEUS DÉBITOS</b></font>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "<table width='500' border='0' cellspacing='2' cellpadding='0' align='center' bgcolor='#CCCCCC'>";
	echo "<tr>";
	
	echo "<td><img src='assets/spacer.gif' height='1' width='15'><br>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='80'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>Data do Lançamento</b></font></td>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='120'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>NF Devolução</b></font></td>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='50'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>Tipo NF</b></font></td>";
	echo "<td align='center'><img src='assets/spacer.gif' height='1' width='80'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'><b>Valor</b></font></td>";
	echo "<td><img src='assets/spacer.gif' height='1' width='15'><br>";
	
	echo "</tr>";
	
	echo "<tr><td></td><td colspan='4'><hr></td><td></td></tr>";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$data_emissao = trim(pg_result($res,$i,data_emissao));
		$nota_fiscal  = trim(pg_result($res,$i,nota_fiscal));
		$tipo         = trim(pg_result($res,$i,tipo));
		$valor_total  = trim(pg_result($res,$i,valor_total));
		$soma_debito  = $soma_debito + $valor_total;
		
		echo "<tr>";
		
		echo "<td></td>";
		echo "<td align='center'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$data_emissao</font></td>";
		echo "<td align='center'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$nota_fiscal</font></td>";
		echo "<td align='center'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$tipo</font></td>";
		echo "<td align='right'><font face='Geneva, Arial, Helvetica, san-serif' size='2'>". number_format($valor_total,2,",",".") ."</font></td>";
		echo "<td></td>";
		
		echo "</tr>";
	}
	echo "</table>";
}

$soma_total = $soma_credito + ($soma_debito * (-1));
?>


<!--  TOTAIS DAS NOTAS FISCAIS. -->

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#CCCCCC">
<tr>
	<td background="../assets/netscapebrowser.gif" align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><font color="#FFFFFF">TOTAIS</font></b></font></td>
</tr>
</table>

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#CCCCCC">
<tr>
	<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif">Total de Débitos</font></td>
	<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif">Total de Créditos</font></td>
	<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif">Saldo Final</font></td>
</tr>
<tr>
	<td align="center"><b><font size="3" face="Geneva, Arial, Helvetica, san-serif"><? echo number_format($soma_debito,2,",",".") ?></font></b></td>
	<td align="center"><b><font size="3" face="Geneva, Arial, Helvetica, san-serif"><? echo number_format($soma_credito,2,",",".") ?></font></b></td>
	<td align="center"><b><font size="3" face="Geneva, Arial, Helvetica, san-serif"><? echo number_format($soma_total,2,",",".") ?></font></b></td>
</tr>
</table>

<!--  RODAPÉ DE INFORMAÇÕES -->

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#CCCCCC">
	<tr>
	<td align="center" size="2"><hr><font size="2"><b><font face="Geneva, Arial, Helvetica, san-serif" size="1">Favor efetuar o pagamento via dep&oacute;sito no BRADESCO Ag. 2762-6 * CC. 210-0 ou <br>
	BANCO DO BRASIL Ag. 3404-5 * CC. 5042-3 em caso de d&eacute;bito.<br>
	Este poder&aacute; ser efetuado na data do vencimento da nota 
	fiscal, em seguida <br>
	nos enviar o comprovante do dep&oacute;sito via fax e identificado <br>
	para que possamos dar baixa de seu d&eacute;bito.<br></font></b></font>
	<p><b><font face="Geneva, Arial, Helvetica, san-serif" size="1">D&uacute;vidas 
	com Sirlei no fone: (41) 382-3211 ramal 272 - Hor&aacute;rio 
	das 08:30h &agrave;s 12:00h.<br>
	<font face="Times New Roman, Times, serif"><i><font size="3">Sirlei</font></i></font><br>
	Depto Assist&ecirc;ncia T&eacute;cnica</font></b></p><hr></td>
	</tr>
</table>

<? } ?>

<p>

<? include "rodape.php" ?>