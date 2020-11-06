<?
exit;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_usuario.php';



$sql = "SELECT tbl_posto.nome,tbl_posto_fabrica.codigo_posto,email
	FROM tbl_posto 
	JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
	WHERE email is not null
	AND fabrica=1
	AND credenciamento = 'CREDENCIADO'";


$res = pg_exec ($con,$sql);
echo "<table>";
echo "<tr class='table_line2' style='background-color: $cor;'>\n";
echo "<td align='left'>Código</td>\n";
echo "<td align='right'>Nome do Posto</td>\n";
echo "<td align='right'>Email</td>\n";
echo "</tr>";
for ($y=0; $y < pg_numrows($res); $y++) {
	$nome         = trim(pg_result($res,$y,nome));
	$codigo_posto = trim(pg_result($res,$y,codigo_posto));
	$email        = trim(pg_result($res,$y,email));

	$cor = "#F7F5F0"; 
	if ($y % 2 == 0) $cor = '#F1F4FA';
	
	echo "<tr class='table_line2' style='background-color: $cor;'>\n";
	echo "<td align='left'>$codigo_posto</td>\n";
	echo "<td align='right'>$nome</td>\n";
	echo "<td align='right'>$email</td>\n";
	echo "</tr>";
}
echo "</table>";
?>