<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


$codigo_posto   = $HTTP_POST_VARS['codigo_posto'];
$nome_posto     = $HTTP_POST_VARS['nome_posto'];
$data_inicial   = $HTTP_POST_VARS['data_inicial'];
$data_final     = $HTTP_POST_VARS['data_final'];
$tipo_relatorio = $HTTP_POST_VARS['tipo_relatorio'];

$sql = "";
if (strlen ($nome_posto) > 0) {
	$sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.nome ILIKE '%$nome_posto%' ";
}
if (strlen ($codigo_posto) > 0) {
		$sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
}
if (strlen ($sql) > 0) {
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) == 0) {
		$msg_erro = "Posto $posto não cadastrado";
	}
	if (pg_numrows($res) == 1) {
		$relatorio = true;
		$posto        = trim(pg_result($res,0,posto));
		$codigo_posto = trim(pg_result($res,0,codigo_posto));
		$nome_posto   = trim(pg_result($res,0,nome));
	}
	if (pg_numrows($res) > 1) {
		$escolhe_posto = true;
	}
}
?>

<html>
<head>
<title>Telecontrol - Acerto de Contas</title>
</head>

<style type="text/css">
<!--
#cab_credito {
	position: relative;
	width: 645px;
	height: 25px;
	left: 2%;
	vertical-align: middle;
	border-width: thin;
	border-color: #000000
}

#celula_cab_credito {
	position: absolute;
	height: 100%;
	vertical-align: baseline;
	text-align: center;
	background-color: #CCCCCC;
	font:90% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#linha_credito {
	position: relative;
	width: 645px;
	height: 15px;
	text-align: center;
	left: 2%;
	border-width: thin;
	border-color: #000000
}

#celula_credito {
	position: absolute;
	top: 0;
	height: 90%;
	background-color: #EFF2FA;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}


#font_form {
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
	}
-->
</style>

<body bgcolor="#EEEEEE" text="#000000" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" link="#333333">

<hr>

<form name="frm_encontro" method="post" action="<? $PHP_SELF ?>">

<center>
<b><font face="Geneva, Arial, Helvetica, san-serif">:: Encontro de Contas ::</font></b>
</center>

<?
if (strlen ($msg_erro) > 0) {
	echo "<p>";
	echo "<center>";
	echo "<b><font face='arial' size='+1' color='#CC3333'>$msg_erro</font></b>";
	echo "</center>";
}
?>

<p>

<table width="300" border="0" cellspacing="5" cellpadding="2" bgcolor="#FFCCCC" align="center">
<tr>
	<td valign="middle" ><div id="font_form">Código do Posto</div><input type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>"></td>

	<td valign="middle" ><div id="font_form"><b>ou</b> Nome do Posto</div><input type="text" name="nome_posto" size="20" value="<? echo $nome_posto ?>"></td>
</tr>
<?
if ($escolhe_posto) {
	echo "<tr><td colspan='2' nowrap> ";
	echo "<center><b>Selecione um dos postos abaixo</b></center><hr>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<a href='javascript: document.frm_encontro.codigo_posto.value = \"" . pg_result ($res,$i,codigo_posto) . "\" ; document.frm_encontro.nome_posto.value = \"" . pg_result ($res,$i,nome) . "\" ; document.frm_encontro.submit()'>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</a>";
		echo " - ";
		echo pg_result ($res,$i,nome);
		echo "<br>";
	}
	echo "<hr></td></tr>";
}
?>
<tr>
	<td valign="middle"><div id="font_form">Data Inicial</div><input type="text" name="data_inicial" size="12" value="<? echo $data_inicial ?>"></td>
	<td valign="middle"><div id="font_form">Data Final</div><input type="text" name="data_final" size="12" value="<? echo $data_final ?>"></td>
</tr>

<tr>
	<td valign="middle" align="center" colspan="2"><hr><input type="submit" name="btn_acao" value="Pesquisar"><hr></td>
</tr>
</table>

</form>








<!-- ----------------- RELATORIO  COMPLETO --------------------- -->

<?
if ($relatorio == true ) { 
	
	echo "<br>\n";

	if (strlen($HTTP_POST_VARS["data_inicial"]) > 0) {
		$data_inicial = trim($HTTP_POST_VARS["data_inicial"]);
		$data_inicial = str_replace ("-","",$data_inicial);
		$data_inicial = str_replace ("/","",$data_inicial);
		$data_inicial = str_replace (" ","",$data_inicial);
		$data_inicial = str_replace (".","",$data_inicial);
		$xdata_inicial = substr($data_inicial,4,4) ."-". substr($data_inicial,2,2) ."-". substr($data_inicial,0,2);
	}
	
	if (strlen($HTTP_POST_VARS["data_final"]) > 0) {
		$data_final = trim($HTTP_POST_VARS["data_final"]);
		$data_final = str_replace ("-","",$data_final);
		$data_final = str_replace ("/","",$data_final);
		$data_final = str_replace (" ","",$data_final);
		$data_final = str_replace (".","",$data_final);
		$xdata_final = substr($data_final,4,4) ."-". substr($data_final,2,2) ."-". substr($data_final,0,2);
	}
	
?>
<!-- CABECALHO DO RELATÓRIO -->

<table width="90%" border="0" cellspacing="0" cellpadding="0" align="center">
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

<!-- Aqui começa a área de CRÉDITOS -->

<?
$br = true;

$sql = "SELECT  to_char(tbl_conta_corrente.data_vencimento, 'DD/MM/YYYY') AS data_vencimento,
				LPAD (TRIM (tbl_conta_corrente.documento),6,'0')       AS nota_fiscal ,
				tbl_conta_corrente.tipo                                               ,
				tbl_conta_corrente.valor                                              ,
				tbl_conta_corrente.valor_saldo
		FROM    tbl_conta_corrente
		WHERE   tbl_conta_corrente.posto = $posto
		AND     (trim(tbl_conta_corrente.tipo) IN ('AT','AU','AL','MO') )
		AND     (trim(tbl_conta_corrente.representante) = '870' OR tbl_conta_corrente.representante IS NULL)
		AND     tbl_conta_corrente.data_vencimento BETWEEN '$xdata_inicial' AND '$xdata_final'
		ORDER BY LPAD (tbl_conta_corrente.documento,6,'0') ";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$br = false;
	
	echo "<br>";
	
	echo "<center><font face='arial' size='+1'><b>";
	echo "SEUS CRÉDITOS";
	echo "</center></b></font>\n";
	
	
	echo "<center><div id='cab_credito'>\n" ;
	
	echo "<div id='celula_cab_credito' style='left: 5 ; width: 95px ; ' ><b>\n" ;
	echo "Vencimento";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 110 ; width: 100px ' ><b>\n" ;
	echo "Nota";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 220 ; width: 60px ' ><b>\n" ;
	echo "Tipo";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 290 ; width: 80px ' ><b>\n" ;
	echo "Valor";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 380 ; width: 80px ' ><b>\n" ;
	echo "Saldo";
	echo "</b></div>\n";
	
	echo "</div></center>\n";
	
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$data_vencimento = trim(pg_result($res,$i,data_vencimento));
		$nota_fiscal  = trim(pg_result($res,$i,nota_fiscal));
		$tipo         = trim(pg_result($res,$i,tipo));
		$valor        = trim(pg_result($res,$i,valor));
		$valor_saldo  = trim(pg_result($res,$i,valor_saldo));
		$soma_credito = $soma_credito + $valor_saldo;
		
		$cor = "#eeeeff";
		if ($i % 2 == 0 ) $cor = "#eef0ee";

		$cor = "#f0f0f0";

		echo "<center><div id='linha_credito'>" ;
		
		echo "<div id='celula_credito' style='left: 5; width: 95px; background-color: $cor ; '><b>" ;
		echo "$data_vencimento";
		echo "</b></div>";
		
		echo "<div id='celula_credito' style='left: 110; width: 100px; background-color: $cor ; '><b>" ;
		echo "$nota_fiscal";
		echo "</b></div>";
		
		echo "<div id='celula_credito' style='left: 220; width: 60px; background-color: $cor ; '><b>" ;
		echo "$tipo";
		echo "</b></div>\n";
		
		echo "<div id='celula_credito' style='left: 290; width: 80px; background-color: $cor ; text-aling: right ;'><b>" ;
		echo  number_format($valor,2,",",".");
		echo "</b></div>\n";
		
		echo "<div id='celula_credito' style='left: 380; width: 80px; background-color: $cor ; '><b>" ;
		echo  number_format($valor_saldo,2,",",".");
		echo "</b></div>\n";
		
		echo "</div></center>\n";
	}

	#---------- Total dos Creditos ---------

}
?>


<!-- Aqui começa a área de DÉBITOS -->

<p>

<?
$sql = "SELECT  to_char(tbl_conta_corrente.data_vencimento, 'DD/MM/YYYY') AS data_vencimento,
				LPAD (TRIM (tbl_conta_corrente.documento),6,'0')       AS nota_fiscal ,
				tbl_conta_corrente.tipo                                               ,
				tbl_conta_corrente.valor                                              ,
				tbl_conta_corrente.valor_saldo
		FROM    tbl_conta_corrente
		WHERE   tbl_conta_corrente.posto = $posto
		AND     (trim(tbl_conta_corrente.tipo) IN ('DP','IM') )
		AND     trim(tbl_conta_corrente.representante) = '870'
		AND     tbl_conta_corrente.data_vencimento BETWEEN '$xdata_inicial' AND '$xdata_final'
		ORDER BY LPAD (tbl_conta_corrente.documento,6,'0') ";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	if ($br == true) {
		echo "<br>";
	}
	
	
	echo "<center><font face='arial' size='+1'><b>";
	echo "SEUS DÉBITOS";
	echo "</b></font></center>\n";
	
	
	
	echo "<center><div id='cab_credito'>\n" ;
	
	echo "<div id='celula_cab_credito' style='left: 5 ; width: 95px ; ' ><b>\n" ;
	echo "Vencimento";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 110 ; width: 100px ' ><b>\n" ;
	echo "Nota";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 220 ; width: 60px ' ><b>\n" ;
	echo "Tipo";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 290 ; width: 80px ' ><b>\n" ;
	echo "Valor";
	echo "</b></div>\n";
	
	echo "<div id='celula_cab_credito' style='left: 380 ; width: 80px ' ><b>\n" ;
	echo "Saldo";
	echo "</b></div>\n";
	
	echo "</div></center>\n";

	
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$data_vencimento = trim(pg_result($res,$i,data_vencimento));
		$nota_fiscal  = trim(pg_result($res,$i,nota_fiscal));
		$tipo         = trim(pg_result($res,$i,tipo));
		$valor        = trim(pg_result($res,$i,valor));
		$valor_saldo  = trim(pg_result($res,$i,valor_saldo));
		$soma_debito  = $soma_debito + $valor_saldo;
		
		
		$cor = "#eeeeff";
		if ($i % 2 == 0 ) $cor = "#eef0ee";

		$cor = "#f0f0f0";

		echo "<center><div id='linha_credito'>" ;
		
		echo "<div id='celula_credito' style='left: 5; width: 95px; background-color: $cor ; '><b>" ;
		echo "$data_vencimento";
		echo "</b></div>";
		
		echo "<div id='celula_credito' style='left: 110; width: 100px; background-color: $cor ; '><b>" ;
		echo "$nota_fiscal";
		echo "</b></div>";
		
		echo "<div id='celula_credito' style='left: 220; width: 60px; background-color: $cor ; '><b>" ;
		echo "$tipo";
		echo "</b></div>\n";
		
		echo "<div id='celula_credito' style='left: 290; width: 80px; background-color: $cor ; text-aling: right ;'><b>" ;
		echo  number_format($valor,2,",",".");
		echo "</b></div>\n";
		
		echo "<div id='celula_credito' style='left: 380; width: 80px; background-color: $cor ; '><b>" ;
		echo  number_format($valor_saldo,2,",",".");
		echo "</b></div>\n";
		
		echo "</div></center>\n";

	}
}

$soma_total = $soma_credito + ($soma_debito * (-1));
?>

<!--  TOTAIS DAS NOTAS FISCAIS. -->

<div id='titulo_credito_externo'>

<div id='titulo_credito'><b>TOTALIZAÇÃO</b></div>

</div>

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center">
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

<table width="630" border="0" cellspacing="0" cellpadding="0" align="center">
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
	Depto Assist&ecirc;ncia T&eacute;cnica</font></b></p>
	</td>
	</tr>
</table>

<p>
<center>
<h2>Final de Relatório</h2>
</center>


<? } ?>






<p>



<br>

</body>
</html>