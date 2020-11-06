<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

# include 'funcoes.php';

$title = "Menu de Ordens de Serviço";
$layout_menu = "os";
include 'cabecalho.php';

?>

<style type="text/css">

body {
	text-align: center;

		}

.cabecalho {

	color: black;
	border-bottom: 2px dotted WHITE;
	font-size: 12px;
	font-weight: bold;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 10px;
	font-weight: normal;
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










<!-- <TABLE width="700px" border="0" align="center">
<TR>
	<TD>
		<?
		echo "<a href='$login_fabrica_site' target='_new'>";
		echo "<IMG SRC='logos/$login_fabrica_logo' ALT='$login_fabrica_site' border='0'>";
		echo "</a>";
		?>
	</TD>
</TR>
</TABLE> -->
<br>
<TABLE class="table" width="700px"  bgcolor="#ffffff" align="center">
<TR>
	<TD colspan="2"><IMG SRC="imagens/cab_menuos.gif" ALT="teste"></TD>
</TR>
<TR>
	<TD class="link" width="250px"><A HREF="os_cadastro.php">ABERTURA DE OS</A></TD>
	<TD class="link" width="450px">Clique aqui para incluir uma nova ordem de serviço</TD>
</TR>
<!-- <TR>
	<TD class="link"><A HREF="os_relacao.php">CONSULTA DE OS</A></TD>
	<TD class="link">Ordens de serviços para consulta, impressão ou lançamento de ítens</TD>
</TR> -->
<TR>
	<TD class="link"><A HREF="os_parametros.php">CONSULTA DE OS</A></TD>
	<TD class="link">Ordens de serviços para consulta, impressão ou lançamento de ítens</TD>
</TR>

<TR>
	<TD class="link"><A HREF="os_fechamento.php">FECHAMENTO DE OS</A></TD>
	<TD class="link">Fechamento das Ordens de Serviços</TD>
</TR>

<TR>
	<TD colspan="2"></TD>
</TR>

<TR>
	<TD class="link"><A HREF="os_revenda.php">ABERTURA DE OS REVENDA</A></TD>
	<TD class="link">Clique aqui para incluir uma nova ordem de serviço de revenda</TD>
</TR>
<TR>
	<TD class="link"><A HREF="os_revenda_parametros.php">CONSULTA DE OS REVENDA</A></TD>
	<TD class="link">Ordens de serviços de revenda para consulta, impressão ou lançamento de ítens</TD>
</TR>
<TR>
	<TD class="link"><A HREF="relatorio_devolucao_obrigatoria.php">DEVOLUÇÃO OBRIGATÓRIA</A></TD>
	<TD class="link">Peças que devem ser devolvidas para a Fabrica constando em Ordens de serviços</TD>
</TR>

<TR>
	<TD colspan="2"></TD>
</TR>
<TR>
	<TD class="link"><A HREF="os_extrato.php">EXTRATO</A></TD>
	<TD class="link">Consulta de Extratos</TD>
</TR>

<?
	if($login_fabrica == 3){
?>

<TR>
	<TD class="link"><A HREF="britania_posicao_extrato.php">EXTRATO (Site antigo)</A></TD>
	<TD class="link">Consulta posição dos Extratos</TD>
</TR>
<?
	}
?>


<?
	if($login_fabrica == 7){
?>
<TR>
	<TD colspan="2"></TD>
</TR>
<TR>
	<TD class="link"><A HREF="os_print_filizola.php?branco=sim" target='_blank'>IMPRIME OS EM BRANCO</A></TD>
	<TD class="link">Impressão de Ordens de serviços para técnicos, em branco</TD>
</TR>

<TR>
	<TD class="link"><A HREF="os_filizola_valores.php">VALORES DA OS</A></TD>
	<TD class="link">Clique aqui para incluir os valores da ordem de serviço</TD>
</TR>
<TR>
	<TD class="link"><A HREF="os_filizola_relatorio.php">FATURAMENTO - VALORES DA OS</A></TD>
	<TD class="link">Consulta as OS com valores</TD>
</TR>
<TR>
	<TD class="link"><A HREF="os_preventiva.php">PREVENTIVA</A></TD>
	<TD class="link">Ordens de serviços de manutenções preventivas</TD>
</TR>
<?
	}
?>
</TABLE>


<? include "rodape.php" ?>

</body>
</html>
