<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';

	$residuo_solido = $_GET['residuo_solido'];
	$dir_pac = "nf_bateria/correio/";
	$arquivo_pac = exec("cd $dir_pac; ls $residuo_solido.*");

?>
<center>
	<img src="<?php echo $dir_pac.$arquivo_pac; ?>">
	<br />

	<div style="width:700px; font:bold 11px Arial;text-align:justify;">
		<p>Ap�s o envio das baterias � imprescind�vel guardar o comprovante emitido pelos Correios para inserir a informa��o no site Telecontrol nessa mesma tela "Consultar relat�rio".</p>
		<p> Lembrando que, as baterias dever�o ser embaladas em sacos pl�sticos com os seus respectivos c�digos e colocadas dentro de uma caixa juntamente com a Nota fiscal emitida.</p>
	</div>
</center>
<script language="JavaScript">
	window.print();
</script>