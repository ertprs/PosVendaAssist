<?
if (strlen ($LOG_PAGINA) > 0) {
	$sql = "INSERT INTO log_pagina_final (log_pagina) VALUES ($LOG_PAGINA);";
	$res = @pg_exec ($con,$sql);
}
?>