<?php

if (empty($_GET['rastreio'])) {
	header("HTTP/1.0 400 Bad Request");
	die("Nenhuma informação de rastreio encontrada");
}

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

$rastreio = $_GET['rastreio'];

$te_ids = [ 174 => [2027, 5682] ];

$te_tracking_url = 'http://tracking.totalexpress.com.br/poupup_track.php?reid=@REID@&pedido=' . $rastreio . '&nfiscal=' . $rastreio;

foreach ($te_ids[$login_fabrica] as $te_id) {
	$url = str_replace('@REID@', $te_id, $te_tracking_url);

	if (trim(file_get_contents($url)) <> 'Dados não encontrados!') {
		header("Location: $url");
		exit;
	}
}

echo 'Nenhuma informação de rastreio encontrada';
