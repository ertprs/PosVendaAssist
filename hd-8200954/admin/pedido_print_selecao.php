<?PHP
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$linha = $_GET['lista_pedido'];
$linhas = explode (',', $linha);
$resultado = count($linhas);
$imprimir_selecao = 1;
for ($w = 0; $w <= $resultado; $w++) {
	$pedido = trim($linhas[$w]); 
	if (strlen($pedido) > 0) {
		#echo "PEDIDO: ($pedido) <br>";
		include ("pedido_finalizado.php");
		if ($w+2 < $resultado){
			echo "<br style='page-break-before:always'>";
		}
	}
}
?>
<script language="JavaScript">
	window.print();
</script>