<?

$enviar = $_POST['enviar'];
$senha  = $_POST['senha'];

if (strlen ($enviar) > 0 and strtolower ($senha) == 'm12') {

	$numero   = $_POST['numero'];
	$qtde     = $_POST['qtde'];
	$nickname = $_POST['nickname'];
	$msg      = $_POST['msg'];
	$msg2     = urlencode("--> " . $msg);

	$url      = "vsim1.telme.sg";

	$Userid         = "13996";
#	$nickname       = "sevburo";
#	$nickname       = "evyyyybur";

	$ip = gethostbyname($url);
	echo $ip;
	echo "<p>";
	$ip = "ssl://" . $ip;


	for ($x = 0 ; $x < $qtde ; $x++) {
		$fp = fsockopen($ip, 443, $errno, $errstr, 10);
		$celular  = $numero + $x ;
		$tonick   = "005514" . $celular ;

		$saida    = "POST /centrovsim/sendsms.php?Userid=$Userid&nickname=$nickname&tonick=$tonick&text=$msg2 HTTP/1.1\r\n";
		$saida   .= "Host: vsim1.telme.sg\r\n";
		$saida   .= "Connection: Close\r\n\r\n";
		fwrite($fp, $saida);

#		$resposta = "";
#		while (!feof($fp)) {
#			$resposta .= fgets($fp, 128);
#		}
#		echo htmlspecialchars ($resposta);
#		echo "<hr>";

		fclose($fp);
		echo "Enviei para $tonick <br>";
		flush();
	}
}


if (1==2) {
	$url = "myaccount.justvoip.com";

	$ip = gethostbyname($url);
	echo $ip;
	$fp = fsockopen($ip, 80, $errno, $errstr, 10);

	$action         = "send";
	$panel          = "";
	$message        = "Via Script PHP...... Ahhhhhhhh muleke !!!! ";
	$callerid       = "+55 14 8125-3394";
	$bnrphonenumber = "+551481253394";
	$sendscheduled  = "no";


	$saida  = "GET /clx/websms2.php?action=$action&panel=$panel&message=$message&callerid=$callerid&bnrphonenumber=$bnrphonenumber&sendscheduled=$sendscheduled HTTP/1.1\r\n";
	$saida .= "Host: myaccount.justvoip.com \r\n";
	$saida .= "Connection: Close\r\n\r\n";

	fwrite($fp, $saida);

	$resposta = "";
	while (!feof($fp)) {
		$resposta .= fgets($fp, 128);
	}
	fclose($fp);
	echo htmlspecialchars ($resposta);
	exit;

	echo "<hr>";
	$posicao = strpos ($resposta,"Tarifa=");
	$tarifa  = substr ($resposta,$posicao+7);
	$posicao = strpos ($tarifa,"&");
	$tarifa  = substr ($tarifa,0,$posicao);
	echo $tarifa;
}


/*
<form method="post" action="websms2.php">
<input type="hidden" name="action" value="send">
<input type="hidden" name="panel" value="">
Enter your text message here:
<textarea onkeyup="limitText(this);" onkeydown="limitText(this);" maxlength="640" name="message" id="messg" cols="35" rows="4"></textarea>


Caller id:
<select name="callerid">
<option value="+55 14 8125-3394">+55 14 8125-3394</option><option value="+551481253394">+551481253394</option><option value="telecontrol6588">telecontrol6588</option>							
</select>			

Enter the mobile phone number(s) you want to text (comma separated) max. 20 sms at a time
<textarea name="bnrphonenumber"  cols="20" rows="3"></textarea>


<input id="sendscheduled1" type="radio" name="sendscheduled" value="no" checked>


<input type="submit" value="Send">

</form>	
*/
?>

<html>
<head>
<title>Metralhadora SMS</title>
</head>

<body>
<h1>Metralhadora de SMS</h1>

<form method='post' action='<?= $PHP_SELF ?>' name='frm_metralhadora'>
Numero Inicial
<br>
<input type='text' name='numero' value='<?= $numero ?>'>

<p>
Qtde a discar
<br>
<input type='text' name='qtde' size='5' value='<?=$qtde?>'>

<p>
Mensagem
<br>
<input type='text' name='msg' size='100' value='<?=$msg?>'>

<p>
NickName
<br>
<input type='text' name='nickname' size='20' value='<?=$nickname?>'>

<p>
Senha
<br>
<input type='password' name='senha'>
<p>
<input type='submit' name='enviar' value='Enviar...'>
</form>

