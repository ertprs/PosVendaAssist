<?PHP
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$linha = $_GET['lista_os'];
$linhas = explode (',', $linha);
$resultado = count($linhas);
for ($w = 0; $w <= $resultado; $w++) {
	$os = trim($linhas[$w]); 
	if (strlen($os) > 0) {
		include ("os_print_filizola.php");
		if ($w+2 < $resultado){
			echo "<br style='page-break-before:always'>";
		}
	}
}
?>
<script language="JavaScript">
	window.print();
</script>