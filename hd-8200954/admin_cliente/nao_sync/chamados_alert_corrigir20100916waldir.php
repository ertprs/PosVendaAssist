 <?
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
$layout_menu = "callcenter";
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
		$res = pg_exec($sql);
		
		echo "<script language='javascript'>
				window.location = '$PHP_SELF';
			</script>";
	} else {
		echo $msg_erro;
	}
}



if (strlen($_GET['hd_chamado_alert'])>0) {
	$hd_chamado_alert = $_GET['hd_chamado_alert'];
	$sql = "SELECT *from tbl_hd_chamado_alert where hd_chamado_alert = $hd_chamado_alert";
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
		echo "<tr><td colspan='4'><input type='submit' value='Confirmar' name='btn_acao'></td></tr>";
		echo "</table></form>";
	}
}

$sql = "SELECT *,to_char(data_leitura,'dd/mm/yyyy') as data_leitura2 from tbl_hd_chamado_alert where admin is null";

$res = pg_exec($con,$sql);

if (pg_num_rows($res)>0){
	echo"<br>";
	echo "<table class='formulario' align='center'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td>Data/Hora</td>";
	echo "<td>Cliente Nome</td>";
	echo "<td>Cidade</td>";
	echo "<td>UF</td>";
	echo "</tr>";

	for ($i=0;$i<pg_num_rows($res);$i++) {

		$hd_chamado_alert  = pg_result($res,$i,'hd_chamado_alert');
		$data_hora         = pg_result($res,$i,'data_leitura2');
		$cliente_nome      = pg_result($res,$i,'tinclinome');
		$cidade            = pg_result($res,$i,'tinclicidade');
		$estado            = pg_result($res,$i,'tinestado');

		echo "<tr onclick='javascript:window.location = \"$PHP_SELF?hd_chamado_alert=$hd_chamado_alert\"' style='cursor:hand'>";
		echo "<td>$data_hora</td>";
		echo "<td>$cliente_nome</td>";
		echo "<td>$cidade</td>";
		echo "<td>$estado</td>";
		echo "</tr>";
	}

}

?>

<? include "rodape.php";?>
