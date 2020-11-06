<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$extrato = $_GET['extrato'];

if ($login_fabrica <> 2 OR strlen($extrato) == 0){
	header("Location: os_extrato.php");
	exit;
}

$title = "DADOS PARA EMISSÃO DE NOTA FISCAL";

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
	displayText("<center><br><font color='#ff0000'>EMITIR NOTA FISCAL CONFORME MODELO ABAIXO E ENVIAR JUNTAMENTE COM AS PEÇAS.</font><br><br></center>");
</SCRIPT>
<br />

<!-- //##################################### -->

<table width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='3'><B>DADOS DA EMPRESA DESTINATÁRIA</B></TD>
</TR>
<TR class='cabecalho'>
	<TD>RAZÃO SOCIAL</TD>
	<TD>CNPJ</TD>
	<TD>IE</TD>
</TR>
<TR class='descricao'>
	<TD>Prodtel Comércio Ltda</TD>
	<TD>04.789.310/0001-98</TD>
	<TD>116.594.848.117</TD>
</TR>
<TR class='cabecalho'>
	<TD>ENDEREÇO</TD>
	<TD>CEP</TD>
	<TD>BAIRRO</TD>
</TR>
<TR class='descricao'>
	<TD>Rua Forte do Rio Branco,762 </TD>
	<TD>08340-140</TD>
	<TD>Pq. Industrial São Lourenço</TD>
</TR>
<TR class='cabecalho'>
	<TD>MUNICIPIO</TD>
	<TD>ESTADO</TD>
	<TD>TELEFONE</TD>
</TR>
<TR class='descricao'>
	<TD>São Paulo</TD>
	<TD>SP</TD>
	<TD>(11) 6117-2336</TD>
</TR>
</TABLE>
<BR>
<table width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='2'><B>DADOS IMPORTANTES PARA A NOTA</B></TD>
</TR>
<TR class='cabecalho'>
	<TD>NATUREZA DA OPERAÇÃO</TD>
	<TD>CFOP</TD>
</TR>
<TR class='descricao'>
	<TD>DEVOLUÇÃO DE REPOSIÇÃO</TD>
	<TD>5949 ( dentro de São Paulo ) 6949 ( fora de São Paulo )</TD>
</TR>
<TR class='cabecalho'>
	<TD colspan=2>ICMS</TD>
</TR>
<TR class='descricao'>
	<TD colspan=2>Se não for isento, preencher conforme aliquota interestadual.</TD>
</TR>
</TABLE>
<BR>

<table width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='5'><B>DADOS DOS ITENS DA NOTA FISCAL</B></TD>
</TR>
<?
// ITENS
if (strlen ($extrato) > 0) {
	$sql = "SELECT  tbl_peca.referencia       ,
					tbl_peca.descricao        ,
					tbl_os_item.qtde          ,
					tbl_os_item.preco         
			FROM	tbl_os_extra
			JOIN	tbl_os_produto USING(os)
			JOIN	tbl_os_item    USING(os_produto)
			JOIN	tbl_peca       USING(peca)
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_peca.devolucao_obrigatoria      IS TRUE";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {

		echo "<TR class='cabecalho'>";
		echo "<TD>DESCRIÇÃO</TD>";
		echo "<TD>QTDE</TD>";
		echo "<TD>UNITÁRIO</TD>";
		echo "<TD>ICMS</TD>";
		echo "<TD>TOTAL</TD>";
		echo "</TR>";

		$total_nota = 0;

		for ($i=0; $i< pg_numrows ($res); $i++){
			$referencia = pg_result ($res,$i,referencia);
			$descricao  = pg_result ($res,$i,descricao);
			$qtde       = pg_result ($res,$i,qtde);
			$preco      = pg_result ($res,$i,preco);
			$icms       = 0;
			$total_peca = $qtde * $preco;

			echo "<TR class='descricao'>";
			echo "<TD>$descricao&nbsp;</TD>";
			echo "<TD>$qtde&nbsp;</TD>";
			echo "<TD>$preco&nbsp;</TD>";
			echo "<TD>$icms&nbsp;</TD>";
			echo "<TD>$total_peca&nbsp;</TD>";
			echo "</TR>";

			$total_nota = $total_nota + $total_peca;

		}

		echo "<TR class='descricao'>";
		echo "<TD colspan=4>Total da Nota Fiscal</TD>";
		echo "<TD>$total_nota &nbsp;</TD>";
		echo "</TR>";

	}else{
		echo "<TR class='descricao'>";
		echo "<TD colspan=5><center>Extrato sem itens para emissão de Nota Fiscal</center></TD>";
		echo "</TR>";
	}
}
?>
</TABLE>

<br>

<? include "rodape.php";?>