<?
# HD - 36258
# Foi criado esta tela para imprimir OSs do extrato

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';

$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços - Imprimir";
include "cabecalho.php"; 

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

</style>

<?

$osseleciona = $_POST["osdapag"];

echo "<br/><table border='0' align='center' width='300' cellspancing='0' cellpadding='0'>";
echo "<tr class='menu_top'>";
echo "<td>Selecione as OSs que deseja imprimir:</td></tr></table>";
echo "<form action='os_print_multi.php' method='POST'>";
echo "<center><input type='checkbox' name='osimprime[]' value='$osseleciona[0]'>$osseleciona[0]<br/>";
if (isset ($osseleciona[1])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[1]'>$osseleciona[1]<br/>";
if (isset ($osseleciona[2])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[2]'>$osseleciona[2]<br/>";
if (isset ($osseleciona[3])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[3]'>$osseleciona[3]<br/>";
if (isset ($osseleciona[4])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[4]'>$osseleciona[4]<br/>";
if (isset ($osseleciona[5])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[5]'>$osseleciona[5]<br/>";
if (isset ($osseleciona[6])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[6]'>$osseleciona[6]<br/>";
if (isset ($osseleciona[7])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[7]'>$osseleciona[7]<br/>";
if (isset ($osseleciona[8])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[8]'>$osseleciona[8]<br/>";
if (isset ($osseleciona[9])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[9]'>$osseleciona[9]<br/>";
if (isset ($osseleciona[10])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[10]'>$osseleciona[10]<br/>";
if (isset ($osseleciona[11])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[11]'>$osseleciona[11]<br/>";
if (isset ($osseleciona[12])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[12]'>$osseleciona[12]<br/>";
if (isset ($osseleciona[13])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[13]'>$osseleciona[13]<br/>";
if (isset ($osseleciona[14])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[14]'>$osseleciona[14]<br/>";
if (isset ($osseleciona[15])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[15]'>$osseleciona[15]<br/>";
if (isset ($osseleciona[16])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[16]'>$osseleciona[16]<br/>";
if (isset ($osseleciona[17])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[17]'>$osseleciona[17]<br/>";
if (isset ($osseleciona[18])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[18]'>$osseleciona[18]<br/>";
if (isset ($osseleciona[19])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[19]'>$osseleciona[19]<br/>";
if (isset ($osseleciona[20])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[20]'>$osseleciona[20]<br/>";
if (isset ($osseleciona[21])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[21]'>$osseleciona[21]<br/>";
if (isset ($osseleciona[22])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[22]'>$osseleciona[22]<br/>";
if (isset ($osseleciona[23])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[23]'>$osseleciona[23]<br/>";
if (isset ($osseleciona[24])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[24]'>$osseleciona[24]<br/>";
if (isset ($osseleciona[25])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[25]'>$osseleciona[25]<br/>";
if (isset ($osseleciona[26])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[26]'>$osseleciona[26]<br/>";
if (isset ($osseleciona[27])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[27]'>$osseleciona[27]<br/>";
if (isset ($osseleciona[28])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[28]'>$osseleciona[28]<br/>";
if (isset ($osseleciona[29])) echo "<input type='checkbox' name='osimprime[]' value='$osseleciona[29]'>$osseleciona[29]<br/>";
echo "<br/><input type=submit value=Imprimir></center></form>";

include "rodape.php";
?>