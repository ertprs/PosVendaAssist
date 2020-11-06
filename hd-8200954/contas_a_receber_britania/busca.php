<? 
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
include 'banco.php';

$acao = $_GET["acao"];

 
$login = $_POST["login"]; 
	
?>

<FORM METHOD=POST ACTION="cobranca.php">
<input type="hidden" name="tipo" value="select">
<SELECT NAME="busca">
	<OPTION VALUE="data_antiga">data de vencimento mais antiga para mais recente</option>
	<OPTION VALUE="data_recente">data de vencimento mais recente para mais antiga</option>
	<OPTION VALUE="menor_valor">menor saldo em aberto para maior</option>
	<OPTION VALUE="maior_valor">maior saldo em aberto para menor</option>
	<OPTION VALUE="nome">ordem alfabetica da razão social</option>
</SELECT>
<INPUT TYPE="submit" value="buscar">
</FORM>

<FORM METHOD=POST ACTION="cobranca.php">
<input type="hidden" name="tipo" value="texto">
<SELECT NAME="busca">
	<OPTION VALUE="cnpj">CNPJ</option>
	<OPTION VALUE="razao_social">razão social</option>
</SELECT>
<input type="text" name="texto">
<INPUT TYPE="submit" value="buscar">
</FORM>
<?
include 'rodape.php';
?>