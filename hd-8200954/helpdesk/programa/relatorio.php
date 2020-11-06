<head><style type="text/css">
.table_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: normal;
	border: 0px solid;

}

</style>
</head>
<body>
<?
echo "<BR><table width = '600' align = 'center' cellpadding='5' cellspacing='0' border='1' >";
echo "<form name='frm_relatorio_adm' action='$PHP_SELF' method='post' >";
echo "<tr>";
echo "<td colspan='4' class='table_top'><center><B>Relatório de conferência das peças devolvidas</B></center></td>";
echo "</tr>";
echo "<tr>";
echo "<td align='right' width='150' class='table_line'> Mês: </td>";
echo "<td width='40' class='table_line' ><select name='mes' size='1'>
		<option value=''></option>
		<option value='01'>Janeiro</option>
		<option value='02'>Fevereiro</option>
		<option value='03'>Março</option>
		<option value='04'>Abril</option>
		<option value='05'>Maio</option>
		<option value='06'>Junho</option>
		<option value='07'>Julho</option>
		<option value='08'>Agosto</option>
		<option value='09'>Setembro</option>
		<option value='10'>Outubro</option>
		<option value='11'>Novembro</option>
		<option value='12'>Dezembro</option>
	</select></td>";

echo "<td width='50' class='table_line'> Ano: </td>";
echo "<td width='200' class='table_line'><select name='ano' size='1'>
		<option value=''></option>
		<option value='2004'>2004</option>
		<option value='2005'>2005</option>
		<option value='2006'>2006</option>
	</select></td>";

echo "</tr>";
echo "<tr>";
echo "<td align='right' width='150' class='table_line'> Código do Posto: </td>";
echo "<td class='table_line' ><input type='text' size='15' name='codigo_posto' ></td>";
echo "<td class='table_line'> &nbsp;</TD>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='4' class='table_line'><center><input type='submit' name='btn_acao' value='Gerar Relatório'></center></td>";
echo "</tr>";
echo "</form>";
echo "</table>";
?>

<?

if($_POST['btn_acao']) $btn_acao = trim ($_POST['btn_acao']);

if (strlen ($btn_acao) > 0) {
	if($_POST['codigo_posto']) $codigo_posto = trim ($_POST['codigo_posto']);
	if($_POST['ano']) $ano = trim ($_POST['ano']);
	if($_POST['mes']) $mes = trim ($_POST['mes']);
	if (strlen ($ano) == 0 && strlen ($mes) == 0){
	echo "<script language='JavaScript'>alert('Ano e Mês em branco!');</script>";
	exit;
	}
	if (strlen ($ano) == 0){
	echo "<script language='JavaScript'>alert('Ano em branco!');</script>";
	exit;
	}
	if (strlen ($mes) == 0){ 
	echo "<script language='JavaScript'>alert('Mês em branco!');</script>";
	exit;
	}

	if ($mes == '01' )$aux_mes="Janeiro";
	if ($mes == '02' )$aux_mes="Fevereiro";
	if ($mes == '03' )$aux_mes="Março";
	if ($mes == '04' )$aux_mes="Abril";
	if ($mes == '05' )$aux_mes="Maio";
	if ($mes == '06' )$aux_mes="Junho";
	if ($mes == '07' )$aux_mes="Julho";
	if ($mes == '08' )$aux_mes="Agosto";
	if ($mes == '09' )$aux_mes="Setembro";
	if ($mes == '10' )$aux_mes="Outubro";
	if ($mes == '11' )$aux_mes="Novembro";
	if ($mes == '12' )$aux_mes="Dezembro";
		
	if (strlen ($ano) > 0 && strlen ($mes) > 0 && strlen($codigo_posto) == 0){
	//fazer consulta em sql
	//SQL - buca somente por mes e por ano
	//gera cabeca do resultado
	echo "<BR><table width = '750' align = 'center' cellpadding='5' cellspacing='1' border='1' >";
	echo "<tr>";
	echo "<td colspan='9' class='table_top'> <center>Relátorio do Mês <B>$aux_mes</B> Ano <b>$ano</b></center>";
	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#D4D4D4'>";
	echo "<td class='table_line'>Código Posto</td>";
	echo "<td class='table_line'>Nome Posto</td>";
	echo "<td class='table_line'>OS</td>";
	echo "<td class='table_line'>Extrato</td>";
	echo "<td class='table_line'>Produto</td>";
	echo "<td class='table_line'>Peça</td>";
	echo "<td class='table_line'>Descrição</td>";
	echo "<td class='table_line'>Defeito</td>";
	echo "<td class='table_line'>Quantidade</td>";
	echo "</tr>";
	
	//colocar o for do SELECT AQUI
	echo "<tr>";
	echo "<td class='table_line'><a href='$PHP_SELF?ano=$ano&mes=$mes&cod_posto=$codigo_posto'>VARIAVEL Código</A></td>";
	echo "<td class='table_line'><a href='$PHP_SELF?ano=$ano&mes=$mes&cod_posto=$codigo_posto'>VARIAVEL Posto</a></td>";
	echo "<td class='table_line'>VARIAVEL OS</td>";
	echo "<td class='table_line'>VARIAVEL Extrato</td>";
	echo "<td class='table_line'>VARIAVEL Produto</td>";
	echo "<td class='table_line'>VARIAVEL Peça</td>";
	echo "<td class='table_line'>VARIAVEL Descrição</td>";
	echo "<td class='table_line'>VARIAVEL Defeito</td>";
	echo "<td class='table_line'>VARIAVEL Quantidade</td>";
	echo "</tr>";
	//fim for
	
	echo "</table>";
	}
	
	if (strlen($ano)>0 && strlen($mes)>0 && strlen($codigo_posto)>0){
	//fazer consulta em sql
	//SQL - buca  por mes, por ano e por posto
	//gera cabeca do resultado
	echo "<BR><table width = '750' align = 'center' cellpadding='5' cellspacing='1' border='1' >";
	echo "<tr>";
	echo "<td colspan='7' class='table_top'> <center>Relátorio do Mês <B>$aux_mes</B> Ano <b>$ano</b> Posto <B>$codigo_posto</b></center>";
	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#D4D4D4'>";
	echo "<td class='table_line'>OS</td>";
	echo "<td class='table_line'>Extrato</td>";
	echo "<td class='table_line'>Produto</td>";
	echo "<td class='table_line'>Peça</td>";
	echo "<td class='table_line'>Descrição</td>";
	echo "<td class='table_line'>Defeito</td>";
	echo "<td class='table_line'>Quantidade</td>";
	echo "</tr>";
	
	
	//colocar o for do SELECT AQUI
	echo "<tr>";
	echo "<td class='table_line'>VARIAVEL OS</td>";
	echo "<td class='table_line'>VARIAVEL Extrato</td>";
	echo "<td class='table_line'>VARIAVEL Produto</td>";
	echo "<td class='table_line'>VARIAVEL Peça</td>";
	echo "<td class='table_line'>VARIAVEL Descrição</td>";
	echo "<td class='table_line'>VARIAVEL Defeito</td>";
	echo "<td class='table_line'>VARIAVEL Quantidade</td>";
	echo "</tr>";
	
	//fim for
	echo "</table>";
	}
	
}
?>

</body>