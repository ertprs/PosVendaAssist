<?php
$micro_time_end = getmicrotime();

?>
<table width='100%' style='position:fixed;bottom:0;'>
<tr id="footer">
<td>
<hr>
<!--[if IE]>
	<style type='text/css'>
	#footer div ul li {
		display:inline;
		font-size:9px;
	}
	#footer div ul li a {
		display:inline-block;
		zoom: 1;
		width:120px;
	}
	#footer div ul li a:hover {
		background-color: #D9E3EF;
		font-size:10px;
	}
	</style>
	<div style='float:left;text-align:left;font-size:9px;clear:none;margin:0;padding:0;color:#666'>
		<b>Telecontrol Networking Ltda</b> recomenda o uso de navegadores atualizados:<br>
		<ul style='list-style:none;list-style-position:inside;vertical-align:baseline;line-height:14px;float:left;margin:0;padding:0;'>
			<li>
				<a href="http://pt-br.www.mozilla.com/pt-BR/firefox/" target='_blank'>&nbsp;
					<img src="/img/browser_icon_ff.png" height='12' alt="FireFox" />
					<b>Mozilla <i>FireFox</i></b><br />
				</a>
			</li>
			<li>
				<a href="http://www.google.com.br/chrome/?hl=pt-BR" target='_blank'>&nbsp;
					<img src="/img/browser_icon_chrome.png" height='12' alt="Chrome" />
					<b>Google <i>Chrome</i></b><br />
				</a>
			</li>
			<li>
				<a href="http://www.apple.com/br/safari/" target='_blank'>&nbsp;
					<img src="/img/browser_icon_safari.png" height='12' alt="Safari" />
					<b>Apple</b> <i>Safari</i><br />
				</a>
			</li>
<![endif]-->
<!--[if lt IE 8]>
		<li style='display:list-item;width:45%'>
			Se quiser continuar a utilizar o <b>MSIE</b>, recomendamos que atualize para o <a href="http://www.microsoft.com/brasil/windows/internet-explorer/default.aspx" style='display:inline;width:auto' target='_blank'><img src="/img/browser_icon_ie.png" height='12' alt="IE8" />
<b>Microsoft <i>Internet Explorer</i> 8</b><br /></a>
			</li>
<![endif]-->
<!--[if IE]>
		</ul>
	</div>
<![endif]-->
	Telecontrol Networking Ltda - <? echo date("Y"); ?><br>
	<a  href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br>

<?
	echo "<font face='arial' size='-2'> CPU : ";
	$time = $micro_time_end - $micro_time_start;
	echo round($time,4) . " segundos ";
	echo "<br>";
	echo "Dados de seu Navegador $HTTP_USER_AGENT";
	echo "</font>";
	
	
	#------ Envia email se o programa demorar para terminar de rodar -------#
	if (round ($time,0) > 60) {
	  $programa_lento = $_SERVER['PHP_SELF'] ;
	  mail ("marisa.silvana@telecontrol.com.br" , "Programa Lento" , $programa_lento . "\n Demorou " . round ($time,0) . " segundos \n" . $_SERVER ['QUERY_STRING'] );
	}
	
?>

	<br><font color='#fefefe'>Deus é o Provedor</font><br>

<? 
/*envia email para o suporte em caso de erro
if (strlen(trim($msg_erro))>0 and strpos(strtoupper($msg_erro), "ERROR") > 0) {
	$sql = "SELECT  tbl_posto_fabrica.codigo_posto, 
					tbl_posto.email 
			FROM tbl_posto_fabrica 
			JOIN tbl_posto using(posto)
			WHERE fabrica = $login_fabrica 
			AND tbl_posto_fabrica.posto = $login_posto";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {
		$codigo_do_posto = pg_result($res,0,codigo_posto);
		$email_do_posto  = pg_result($res,0,email);
	}
	
	$remetente    = "ERRO NO SITE - TELECONTROL <telecontrol@telecontrol.com.br>"; 
	$destinatario = "helpdesk@telecontrol.com.br"; 
	$assunto      = "ERRO NA PÁGINA"; 

	$mensagem     =		"OCORREU O SEGUINTE ERRO: <BR><BR>
						Programa: $PHP_SELF<BR>
						Fabrica: $login_fabrica<BR>
						Posto: $login_posto<BR>
						Login: $codigo_do_posto<BR>
						Email: $email_do_posto<BR>
						ERRO: <BR><font color='#FF0000'>$msg_erro</font>"; 
	$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
	
	//Sono - retirei, não estamos acompanhando estes emails e está enchendo a caixa postal do suporte
	//@mail($destinatario,$assunto,$mensagem,$headers);
} 
 */
//Ebano: encerrando a conexão para que não fique "idle" no sistema, consumindo recursos
@pg_close($con);

?>

<?
include "/var/www/assist/www/log_final.php";
?>


</div>
</td>
</tr>
</table>
</center>
</center>
</body>
</html>
