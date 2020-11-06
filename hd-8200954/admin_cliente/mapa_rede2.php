<?php
include "dbconfig.php";
include "dbconnect-inc.php";
$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$aStrings   = array();
$aStrings[] = "São Paulo";
$aStrings[] = "São Carlos";
$aStrings[] = "São Caetano do Sul";
$aStrings[] = "Poços de Caldas";
$aStrings[] = "São Sebastião da Grama";
$aStrings[] = "Curitiba";

?>
<table>
<tr>
	<td> Original </td>
	<td> ISO </td>
	<td> UTF-8 </td>
	<td> ISO->ASCII </td>
	<td> ISO->UTF-8->ASCII </td>
</tr>
	<?php foreach ($aStrings as $string): ?>
	<?php 
		$utf8 = iconv('ISO-8859-1','UTF-8',$string);
		$iso  = $string;
	?>
	<tr>
		<td> <?php echo $string; ?> </td>
		<td> <?php echo $iso; ?> </td>
		<td> <?php echo $utf8; ?> </td>
		<td> <?php echo iconv('ISO-8859-1','ASCII//TRANSLIT',$iso); ?> </td>
		<td> <?php echo iconv('UTF-8','ASCII//TRANSLIT',$utf8); ?> </td>
	</tr>
	<?php endforeach; ?>
</table>

