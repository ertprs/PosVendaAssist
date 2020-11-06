<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$extrato = $_GET['extrato'];

if ($login_fabrica <> 2 OR strlen($extrato) == 0){
	header("Location: os_extrato.php");
	exit;
}

$title = "DADOS PARA EMISSÃO DE NOTA DE PRESTAÇÃO DE SERVIÇO";

$layout_menu = "os";
include "cabecalho.php";

?>

<style type='text/css'>
body {
	text-align: center;
}

.cabecalho {
	background-color: #D9E2EF;
	color: black;
	border: 2px SOLID WHITE;
	font-weight: normal;
	font-size: 10px;
	text-align: left;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 11px;
	font-weight: bold;
	text-align: justify;
}


/*========================== MENU ===================================*/

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}

</style>

<SCRIPT>
	displayText("<center><br><font color='#ff0000'>ENVIAR PARA A DYNACOM - SP AS ORDENS DE SERVIÇO CONSTANTE NESTE EXTRATO, APÓS A VERIFICAÇÃO DAS MESMAS, SERÁ ENCAMINHADO UM E-MAIL COM O VALOR E DADOS PARA PREENCHIMENTO E ENVIO DA NOTA FISCAL DE PRESTAÇÃO DE SERVIÇO</font><br><br></center>");
</SCRIPT>
<br>

<!-- //##################################### -->

<TABLE width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='3'><B>DADOS DA EMPRESA DESTINATÁRIA</B></TD>
</TR>
<TR class='cabecalho'>
	<TD>RAZÃO SOCIAL</TD>
	<TD>CNPJ</TD>
	<TD>IE</TD>
</TR>
<TR class='descricao'>
	<TD>CEDER ELETRONICA DA AMAZONIA LTDA</TD>
	<TD>84.664.713/0001-40</TD>
	<TD>06.200.282-1</TD>
</TR>
<TR class='cabecalho'>
	<TD>ENDEREÇO</TD>
	<TD>CEP</TD>
	<TD>BAIRRO</TD>
</TR>
<TR class='descricao'>
	<TD>AV. ABIURANA Nº 244  </TD>
	<TD>69075-010</TD>
	<TD>DISTRITO INDUSTRIAL</TD>
</TR>
<TR class='cabecalho'>
	<TD>MUNICIPIO</TD>
	<TD>ESTADO</TD>
	<TD>TELEFONE</TD>
</TR>
<TR class='descricao'>
	<TD>MANAUS</TD>
	<TD>AM</TD>
	<TD>&nbsp;</TD>
</TR>
</TABLE>

<BR>

<TABLE width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='2'><B>DADOS IMPORTANTES PARA A NOTA</B></TD>
</TR>
<TR class='cabecalho'>
	<TD>NATUREZA DA OPERAÇÃO</TD>
	<TD>PRESTAÇÃO DE SERVIÇOS DE</TD>
</TR>
<TR class='descricao'>
	<TD>Prestação de Serviço</TD>
	<TD>Mão-de-obra</TD>
</TR>
</TABLE>
<BR>

<TABLE width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD><B>DESCRIÇÃO DO SERVIÇO</B></TD>
</TR>
<TR class='descricao'>
	<TD>
<?
if (strlen ($extrato) > 0) {
	$sql = "SELECT	to_char(tbl_extrato.data_geracao, 'MM')   AS mes,
					to_char(tbl_extrato.data_geracao, 'YYYY') AS ano,
					tbl_extrato.extrato
			FROM	tbl_extrato
			JOIN	tbl_os_extra USING(extrato)
			JOIN	tbl_os       USING(os)
			WHERE	tbl_extrato.extrato = $extrato
			AND		tbl_os.fabrica      = $login_fabrica
			AND		tbl_os.posto        = $login_posto";
	$res = @pg_exec ($con,$sql);
	$mes     = @pg_result ($res,0,0);
	$ano     = @pg_result ($res,0,1);
	$extrato = @pg_result ($res,0,2);

	/* Meses Por Extenso Portugues */
	$Extenso = array(1=>"Janeiro","Fevereiro","Mar&ccedil;o","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");
	if ($mes < 10) $mes = substr($mes,1,1);

	echo "Pagamento de mão-de-obra ref. à  ".$Extenso[$mes]." de $ano conforme extrato nº $extrato.<br><br>";

/*
	echo "Pagamento de mão-de-obra em garantia ref. ao mês $data da(s) Ordem(ns) de Serviço:<br><br>";

	$sql = "SELECT	tbl_os.sua_os
			FROM	tbl_os
			JOIN	tbl_os_extra USING(os)
			JOIN	tbl_extrato  USING(extrato)
			WHERE	tbl_extrato.extrato = $extrato
			AND		tbl_os.fabrica      = $login_fabrica
			AND		tbl_os.posto        = $login_posto
			ORDER BY lpad (tbl_os.sua_os,20,'0') ASC";

	$res = @pg_exec ($con,$sql);
	$sua_os = '';
	for ($i=0; $i< @pg_numrows ($res); $i++) {
		$sua_os .= @pg_result ($res,$i,sua_os);
		if ($i < @pg_numrows ($res)-1 ) $sua_os .= ", ";
	}
	echo $sua_os; 
*/
}

?>
	</TD>
</TR>
</TABLE>

<br>

<? include "rodape.php";?>