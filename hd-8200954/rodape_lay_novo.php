<? 
$micro_time_end = getmicrotime();
?>
<table width="100%" align='center'> 
<tr>
<td align='right'>
<hr>
<font size='1'>Telecontrol Networking Ltda - <? echo date("Y"); ?><br>
<a  href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br></font>

<?
	echo "<font face='arial' size='-2'> CPU : ";
	$time = $micro_time_end - $micro_time_start;
	echo round($time,4) . " segundos ";
	echo "<br>";
	echo "Dados de seu Navegador $HTTP_USER_AGENT";
	echo "</font>";
?>

	<br><font color='#fefefe'>Deus é o Provedor</font><br>
</td></tr>

</table>
</body>
</html>
