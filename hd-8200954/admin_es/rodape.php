<?
#------------- Programa Restrito ------------------#
/*echo "<table width='100%' border='0' align='center'>";
echo "<tr>";

echo "<td align='left'>";
$sql = "SELECT * FROM tbl_programa_restrito WHERE programa = '$PHP_SELF' AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	echo "Programa restrito aos inmediato usuarios: ";
	$sql = "SELECT login FROM tbl_programa_restrito JOIN tbl_admin USING (admin) WHERE programa = '$PHP_SELF' AND tbl_programa_restrito.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo pg_result ($res,$i,login);
		echo " ";
	}
}else{
	echo "<font size='-2'>Programa sin Restricción</font>";
}
echo "</td>";

echo "<td align='right'>";
	echo "<a href='programa_restrito.php?programa=$PHP_SELF'><font size='-2'>Restringir Programa </font></a>";

echo "</td>";

echo "</tr>";
echo "</table>";
*/
?>
<? 
$micro_time_end = getmicrotime();
?>
<table width="100%" align='center'>
<div id="footer">
<hr>
	Telecontrol Networking Ltda - <? echo date("Y"); ?><br>
	<a  href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br>

<?
	echo "<font face='arial' size='-2'> CPU : ";

	$time = $micro_time_end - $micro_time_start;

	echo round($time,4) . " segundos ";
	echo "</font>";
?>

	<br><font color='#fefefe'>Dios es el proveedor</font><br>

</div>
</table>
</body>
</html>
