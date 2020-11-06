<?

include 'funcoes.php';

if (isset($formatar)) {
	$data_formatada = fnc_formata_data_pg($_POST['data']);
	echo "<font size='4'>".$data_formatada."</font><br>";
	echo "<a href='$PHP_SELF'>Outro teste</a>";
	exit;
}

?>
<br>
<br>
<br>
<form name='form' method='post' action='<? $PHP_SELF ?>'>
	<center>
		<input type='text' name='data'>
		<br>
		<br>
		<input type='submit' name='formatar' value='Formatar Data'>
	</center>
</form>