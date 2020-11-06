<div id="footer">
<?php

#------------- Programa Restrito ------------------#
echo "<table width='100%' border='0' align='center'  class='no-print'>";
echo "<tr>";

echo "<td align='left'>";
$sql = "SELECT * FROM tbl_programa_restrito JOIN tbl_login_unico using(login_unico) WHERE programa = '$PHP_SELF' AND posto = $login_unico_posto";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	echo "Programa restrito aos seguintes usuários: ";
	$sql = "SELECT tbl_login_unico.nome FROM tbl_programa_restrito JOIN tbl_login_unico USING (login_unico) WHERE programa = '$PHP_SELF' and ativo";
	
	$res = pg_query ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<b>".pg_result ($res,$i,'nome')."</b>; ";
		echo " ";
	}
}else{
	echo "<font size='-2'>Programa sem restri&ccedil;&atilde;o</font>";
}
echo "</td>";

echo "<td align='right'>";
	echo "<a href='programa_restrito.php?programa=$PHP_SELF&titulo=$title' ><font size='-2'>Restringir Programa </font></a>";

echo "</td>";

echo "</tr>";
echo "</table>";

?>


<h6>
	Telecontrol Networking Ltda - <? echo date("Y"); ?>
<?
/*	echo "<font face='arial' size='-2'> || CPU : ";
	$time = $micro_time_end - $micro_time_start;
	echo round($time,4) . " segundos ";
	echo " || ";
	echo "</font>";
*/
?>


</h6>

</body>
</html>
</div>
