<?php
# 26/01/2009
# Este script щ para controle dos email disparados para promoчуo do Login кnico

$file = "/var/www/cgi-bin/contador-email-loja.log";

$data = date("d/m/Y H:i:s");

if (strlen($_GET['id']) == 0) {
	exit;
}

$posto = $_GET['id'];
$linha = "$data\t$posto\n";

if (!$log = fopen($file,'a')) {
	echo 'Erro ao tentar abrir o arquivo ' . $file . '.';
	exit;
}

if (fwrite($log, $linha) === FALSE) {
	exit;
}

fclose($log);

header("Content-type: image/png");
$nada = readfile('imagens/promo_lancamento_loja_v3.jpg');

exit;
?>