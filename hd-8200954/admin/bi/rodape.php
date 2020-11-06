<?
#------------- Programa Restrito ------------------#
echo "<table width='100%' border='0' align='center'>";
echo "<tr>";

echo "<td align='left'>";
$sql = "SELECT * FROM tbl_programa_restrito WHERE programa = '$PHP_SELF' AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	echo "Programa restrito aos seguintes usuários: ";
	$sql = "SELECT login FROM tbl_programa_restrito JOIN tbl_admin USING (admin) WHERE programa = '$PHP_SELF' AND tbl_programa_restrito.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo pg_result ($res,$i,login);
		echo " ";
	}
}else{
	echo "<font size='-2'>Programa sem restrição</font>";
}
echo "</td>";

echo "<td align='right'>";
	echo "<a href='programa_restrito.php?programa=$PHP_SELF'><font size='-2'>Restringir Programa </font></a>";

echo "</td>";

echo "</tr>";
echo "</table>";

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

	<br><font color='#fafafa'>Deus é o Provedor</font><br>

<? 
//envia email para o suporte em caso de erro
if (strlen(trim($msg_erro))>0 and strpos(strtoupper($msg_erro), "ERROR") > 0) {
	$sql = "SELECT login, email FROM tbl_admin where admin = $login_admin";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res)  == 1) {
		$login_do_admin = pg_result($res,0,login);
		$email_admin    = pg_result($res,0,email);
	}
	
	$remetente    = "ERRO NO SITE - TELECONTROL <telecontrol@telecontrol.com.br>"; 
	$destinatario = "helpdesk@telecontrol.com.br"; 
	$assunto      = "ERRO NA PÁGINA"; 

	$mensagem     =		"OCORREU O SEGUINTE ERRO: <BR><BR>
						Programa: $PHP_SELF<BR>
						Fabrica: $login_fabrica<BR>
						Admin: $login_admin<BR>
						Login: $login_do_admin<BR>
						Email: $email_admin<BR>
						ERRO: <BR><font color='#FF0000'>$msg_erro</font><BR>"; 
	$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
	
	@mail($destinatario,$assunto,$mensagem,$headers);
} 
?>

</div>
</table>
</body>
</html>
<? include "monitora_rodape.php";?>