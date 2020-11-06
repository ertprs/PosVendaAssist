<?
/*
$XXXpagina = explode("/",$PHP_SELF);
if (count($XXXpagina)>0){
	$XXXpagina = $XXXpagina[count($XXXpagina)-1];
}
*/

if (strlen(trim($_GET["login_fabrica"]))>0)       { $login_fabrica      = trim($_GET["login_fabrica"]); }
if (strlen(trim($_GET["login_admin"]))>0)         { $login_admin        = trim($_GET["login_admin"]); }
if (strlen(trim($_GET["login_login"]))>0)         { $login_login        = trim($_GET["login_login"]); }
if (strlen(trim($_GET["login_posto"]))>0)         { $login_posto        = trim($_GET["login_posto"]); }
if (strlen(trim($_GET["login_fabrica_logo"]))>0)  { $login_fabrica_logo = trim($_GET["login_fabrica_logo"]); }

$login_admin = 567;
$login_posto = 4311;

$XXXpagina = basename($PHP_SELF);
$data_ultimo_programa = date("Y-m-d");

$sql = "SELECT TO_CHAR(data,'YYYY-MM-DD') AS data
		FROM tbl_relatorio_agendamento 
		WHERE admin = 567 
		AND programa = '$PHP_SELF'
		ORDER BY data DESC 
		LIMIT 1";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	$data_ultimo_programa = pg_result($res,0,data);
}

$relatorio_anterior = "/tmp/relatorios/relatorio_automatico_".$XXXpagina."-".$data_ultimo_programa.".".$login_fabrica.".".$login_admin.".htm";
$include      = trim($_GET["include"]);
if ( $include == "1" ){
	if(file_exists($relatorio_anterior)){
		include $relatorio_anterior;
		exit;
	}
}

?>