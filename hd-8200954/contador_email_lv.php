<?php
# 21/01/2010
# Este script щ para controle dos email disparados para promoчуo do Loja Virtual
# cgi-bin/loja_virtual-email_mark_20.pl

$file = "/var/www/cgi-bin/contador-email-lv.log";

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
exit;

?>