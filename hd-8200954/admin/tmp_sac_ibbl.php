 <?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include 'autentica_admin.php';
include "cabecalho.php";
?>

<style>

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-transform: capitalize;
}

</style>


<?

if ($_POST['btn_acao']) {

	$hd_chamado_alert = $_POST['hd_chamado_alert'];

	if (strlen($hd_chamado_alert)==0) {
		$msg_erro = 'Chamado Inválido';
	}

	if (strlen($msg_erro)==0) {
		$sql = "UPDATE tbl_hd_chamado_alert set admin = $login_admin where hd_chamado_alert = $hd_chamado_alert";
		$res = pg_exec($con, $sql);
		
		echo "<script language='javascript'>
				window.location = '$PHP_SELF';
			</script>";
	} else {
		echo $msg_erro;
	}
}



if (strlen($_GET['NumeroAtendimento'])>0) {
	$NumeroAtendimento = $_GET['NumeroAtendimento'];
	$sql = "SELECT *from tmp_sac_ibbl where NumeroAtendimento = '$NumeroAtendimento'";
	$res = pg_exec($con,$sql);

	if (pg_num_rows($res)>0) {
		
		$num_field = pg_num_fields($res); 
		
		$colunas = "4";

		if ($num_field>0) {
		echo "<form method='post' name='frm_principal' action=''>";
		echo "<table width='100%' align='center' class='formulario'>";
			for($i=0;$i<pg_num_fields($res);$i++) {
				
				$valor = pg_result($res,0,$i);
				$label = pg_field_name($res,$i);
				$label2 = pg_field_name($res,$i);

				$label = str_replace('tin','',$label);
				$label = str_replace('_',' ',$label);
				$label = str_replace('cli','Cliente ',$label);

				if (($i%$colunas)==0) {
					echo "</tr><tr valign='top'>";
				}

				echo "	<td width='300' align='left'><div class='titulo_tabela' width='100%'>$label</div><br>";
				if (strlen($valor)<=50) {
					echo "<input type='text' name='$label2' value='$valor' size='40' class='frm' readonly='true'>";
					echo "</td>";
				} else {
					echo "<textarea rows='10' cols='35' class='frm' readonly='true'>$valor</textarea>";
				}
			}
		}
		echo "<tr><td colspan='4'></td></tr>";
		echo "</table></form>";
	}
}

$sql = "SELECT *from tmp_sac_ibbl";

$res = pg_exec($con,$sql);

if (pg_num_rows($res)>0){
	echo"<br>";
	echo "<table class='formulario' align='center'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td>NumeroAtendimento</td>";
	echo "<td>Abertura</td>";
	echo "<td>Cliente</td>";
	echo "<td>Cidade</td>";
	echo "<td>Estado</td>";
	echo "</tr>";

	for ($i=0;$i<pg_num_rows($res);$i++) {

		$NumeroAtendimento  = pg_result($res,$i,1);
		$data               = pg_result($res,$i,0);
		$cliente            = pg_result($res,$i,3);
		$cidade            = pg_result($res,10);
		$estado            = pg_result($res,$i,11);

		echo "<tr onclick='javascript:window.location = \"$PHP_SELF?NumeroAtendimento=$NumeroAtendimento\"' style='cursor:pointer'>";
		echo "<td>$NumeroAtendimento</td>";
		echo "<td>$data</td>";
		echo "<td>$data</td>";
		echo "<td>$cliente</td>";
		echo "<td>$estado</td>";
		echo "</tr>";
	}

}

?>

<? include "rodape.php";?>
