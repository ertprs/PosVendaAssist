<?php
/* Questionario - HD 674943 */
$questionario = (int) $_GET['questionario'];
if ( empty ( $questionario ) ) {
	echo 'Erro na passagem de parâmetros';
	exit;
}

$sql = "SELECT questionario FROM tbl_questionario WHERE questionario = $questionario";
$res = pg_query($con,$sql);
if(!pg_num_rows($res)) {

	echo 'Questionário Inválido';
	exit;

}

?>

<table>
	<tr>
		<th>#</th>
		<th></th>
		<th></th>
	</tr>
	<tr>
		<td>1</td>
		<td></td>
		<td></td>
	</tr>
</table>