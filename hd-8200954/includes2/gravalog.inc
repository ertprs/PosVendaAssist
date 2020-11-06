<?php


if (strlen ($cook_visitante) > 0) {
	$visitante = $cook_visitante;
}else{
	$sql = "INSERT INTO visitante (nome) VALUES ('')";
	$res = pgexec ($con , $sql);
	$res = pgexec ($con, "SELECT MAX (codigo) FROM visitante");
	$visitante = pg_result ($res,0,0);
	setcookie ("cook_visitante",$visitante);
}



$sql = "INSERT INTO log (
	visitante,
	data,
	ip,
	browser,
	origem,
	porta,
	pagina,
	caminho,
	pedido,
	dominio,
	proxy)
	VALUES (
	$visitante,
	Now(),
	'" . getenv ("REMOTE_ADDR") . "' ,
	'" . getenv ("HTTP_USER_AGENT") . "' ,
	'" . getenv ("HTTP_REFERER") . "' ,
	'" . getenv ("REMOTE_PORT") . "' ,
	'" . getenv ("SCRIPT_NAME") . "' ,
	'" . getenv ("SCRIPT_FILENAME") . "' ,
	'" . getenv ("REQUEST_URI") . "' ,
	'" . getenv ("SERVER_NAME") . "' ,
	'" . getenv ("HTTP_VIA") . "')" ;

$res = pgexec ($con , $sql);
?>
