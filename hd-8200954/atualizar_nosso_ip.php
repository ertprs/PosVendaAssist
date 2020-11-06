<?
$ippp = $_SERVER['REMOTE_ADDR'];
$ippp_manual = trim($_GET['ip']);

if (strlen($ippp_manual)>0){
	$ippp = $ippp_manual;
}

//TENTA ABRIR O ARQUIVO TXT
$abrir = fopen("/www/assist/www/nosso_ip.txt", "w") or die("Não foi possível criar o arquivo de IP");


//ESCREVE NO ARQUIVO TXT
fwrite($abrir, $ippp)  or die("Não foi possível gravar o IP");

echo "IP atualizado: $ippp";

 fclose($abrir); 
?>