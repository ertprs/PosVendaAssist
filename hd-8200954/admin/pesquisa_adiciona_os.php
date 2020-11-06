<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

?>

<html>
<head>
<title> Pesquisa Ordem de Serviço... </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body onblur="setTimeout('window.close()',6500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<?

$adiciona_sua_os   = trim ($_GET['adiciona_sua_os']);
$posto             = trim ($_GET['posto']);
$extrato           = trim ($_GET['extrato']);



$sql = "SELECT  tbl_os.sua_os                                                        ,
				tbl_os.os                                                            ,
				tbl_posto_fabrica.codigo_posto                                       ,
				to_char (tbl_os.data_abertura   ,'DD/MM/YYYY')  AS data_abertura     ,
				to_char (tbl_os.data_fechamento ,'DD/MM/YYYY')  AS data_fechamento   ,
				tbl_os.consumidor_nome                                               ,
				tbl_os.excluida
			FROM  tbl_os
			JOIN  tbl_posto_fabrica using(posto)
		WHERE     tbl_os.fabrica = $login_fabrica
		AND       tbl_os.sua_os =  '$adiciona_sua_os'
		AND       tbl_posto_fabrica.codigo_posto = '$posto' ";

$res = pg_exec ($con,$sql);

if (@pg_numrows ($res) == 0) {
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	echo "<br><p style='font-size: 20px;' align='center'><B>OS</B> <I>$adiciona_sua_os</I> <B>não encontrada</B></p>";
	echo "<br><br><br><br><br><P style='color:#FFFFFF;' align='right'>www.telecontrol.com.br</P>";
	exit;
}

echo "<br>";
echo "<h4 align='center' style='font-family:verdana; font-size: 14px;'>Informações da OS: <i>$adiciona_sua_os</i></h4>";

for($i = 0; $i < pg_numrows($res); $i++){

	$os               = pg_result($res,0,os);
	$adiciona_sua_os  = pg_result($res,0,sua_os);
	$posto            = pg_result($res,0,codigo_posto);
	$data_abertura    = pg_result($res,0,data_abertura);
	$data_fechamento  = pg_result($res,0,data_fechamento);
	$consumidor_nome  = pg_result($res,0,consumidor_nome);
	$excluida         = pg_result($res,0,excluida);

	$sql = "SELECT extrato FROM tbl_os_extra WHERE os = '$os' ";
	$res = @pg_exec($con,$sql);

	if(@pg_numrows($res) > 0){
		$extrato_atual = pg_result($res,0,0);
		if (strlen($extrato_atual) > 0){
			$sql2 = "SELECT extrato 
				FROM tbl_extrato_pagamento 
			WHERE extrato = '$extrato_atual' ";
			$res2 = @pg_exec($con,$sql2);// Verifica se o extrato ja foi dado baixa
			$adiciona_baixado = @pg_result($res2,0,0);
		}
	}

	echo "<table align='center' width='400' border='0' bgcolor='#F3F3F3' style='font-size: 11px; font-family: verdana;' align='center'>";

	echo "<tr>";
	echo "<td nowrap><B>Posto:</B></td>"; 
	echo "<td><font face='Arial, Verdana, Times, Sans' color='#000000'>$posto</font></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td width='150' nowrap><B>OS:</B></td>";
	echo "<td >";
	echo "<font face='Arial, Verdana, Times, Sans' color='#000000'>$adiciona_sua_os</font>";
	echo "</a>";
	echo "</td>";
	echo "</tr>";

	echo "<tr'>";
	echo "<td width='150' nowrap><B>Data Abertura:</B></td>";
	echo "<td>";
	echo "<font face='Arial, Verdana, Times, Sans' color='000000'>$data_abertura</font>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td width='150' nowrap ><B>Data Fechamento:</B></td>";
	echo "<td>";
	echo "<font face='Arial, Verdana, Times, Sans' color='000000'>$data_fechamento</font>";
	echo "</td>";
	echo "</tr>";


	echo "<tr>";
	echo "<td width='150' nowrap ><B>Consumidor:</B></td>";
	echo "<td>";
	echo "<font face='Arial, Verdana, Times, Sans' color='000000'>$consumidor_nome</font>";
	echo "</td>";
	echo "</tr>";

	if(strlen($extrato_atual) > 0 AND $extrato_atual <> $extrato){
		echo "<tr>";
		echo "<td width='150' nowrap ><B>Extrato atual:</B></td>";
		echo "<td>";
		echo "<font face='Arial, Verdana, Times, Sans' color='#FF0000'>$extrato_atual</font>";
		echo "</td>";
		echo "</tr>";
	}

	if($excluida == "t"){
		echo "<tr colspan='2'>";
		echo "<td style='font-family: verdana; font-size: 9px; color:#FF0000;'>*A OS já foi excluída.</td>";
		echo "</tr>";
	}

	if(strlen($data_fechamento) == 0){
		echo "<tr colspan='2'>";
		echo "<td style='font-family: verdana; font-size: 9px; color:#FF0000;'>*A OS deve estar fechada.</td>";
		echo "</tr>";
	}

	if($extrato_atual == $extrato){
		echo "<tr colspan='2'>";
		echo "<td style='font-family: verdana; font-size: 9px; color:#FF0000;'>*OS já inclusa no extrato atual</td>";	
		echo "</tr>";
	}

	if(strlen($adiciona_baixado) > 0){
		echo "<tr colspan='2'>";
		echo "<td style='font-family: verdana; font-size: 9px; color:#FF0000;'>*Extrato já foi dado baixa</td>";
		echo "</tr>";
	}

	echo "<tr bgcolor='#D6D6D6'>";
	echo "<td align='center' colspan='2'>";

	if(strlen($data_fechamento) > 0 AND $extrato_atual <> $extrato AND strlen($adiciona_baixado) == 0 AND $excluida<> "t"){

		echo "<a href=\"javascript: janela = opener.document.location.href ; posicao = janela.lastIndexOf('.') ; janela = janela.substring(0,posicao+4) ; opener.document.location = janela + '?extrato=$extrato&adiciona_sua_os=$adiciona_sua_os' ; this.close() ;\" > " ;

/*		echo "<a href=\"javascript: adiciona_sua_os.value ='$adiciona_sua_os'; adiciona_data_abertura.value = '$data_abertura'; ";
		if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
		echo "this.close() ; \" >";
*/
		echo "<font face='Arial, Verdana, Times, Sans' >Confirma OS</font>";
		echo "</a>";

	}else{
		echo "Confirma OS";
	}
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
	echo "<a href=\"javascript: adiciona_sua_os.value =''; this.close();\">Não Confirma</a>";
	echo "</td>";
	echo "</tr>";

	echo "</table><br><br>";
}

?>


</body>
</html>
