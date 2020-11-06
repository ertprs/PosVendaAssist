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
		<p>Após o envio das baterias é imprescindível guardar o comprovante emitido pelos Correios para inserir a informação no site Telecontrol nessa mesma tela "Consultar relatório".</p>
		<p> Lembrando que, as baterias deverão ser embaladas em sacos plásticos com os seus respectivos códigos e colocadas dentro de uma caixa juntamente com a Nota fiscal emitida.</p>
	</div>
</center>
<script language="JavaScript">
	window.print();
</script>