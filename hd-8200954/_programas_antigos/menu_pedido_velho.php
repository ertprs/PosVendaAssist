<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

# include 'funcoes.php';

$title = "Menu de Pedido de Peças";
$layout_menu = "pedido";
include 'cabecalho.php';

?>

<style type="text/css">

body {
	text-align:center;

		}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

hr {
	margin: 0px auto;
	color: #ced7e7;
	height: 2px;

}
.link {
	margin: 0px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: left;
	border-bottom: thin dotted #ADD8E6;
		}

</style>
<!-- 
<TABLE width="700px" border="0" align="center">
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
	<TD colspan="3"><IMG SRC="imagens/cab_menupedido.gif" ALT="teste"></TD>
</TR>
<TR>
<!-- 	<TD rowspan="8"  width="200px"><IMG SRC="imagens/pecas.gif" ALT=""></TD> -->
	<TD class="link" width="200px"><A HREF="pedido_cadastro.php">CADASTRO DE PEDIDOS</A></TD>
	<TD class="link" width="300px">Insira seu pedido de peças aqui</TD>
</TR>
<TR>
	<TD class="link" width="200px"><A HREF="pedido_relacao.php">CONSULTA DE PEDIDOS</A></TD>
	<TD class="link" width="300px">Consulta seus pedidos de peças à fábrica</TD>
</TR>
</TABLE>


<? include "rodape.php" ?>

</body>
</html>