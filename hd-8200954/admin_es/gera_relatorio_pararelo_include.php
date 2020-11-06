<?
/*
$XXXpagina = explode("/",$PHP_SELF);
if (count($XXXpagina)>0){
	$XXXpagina = $XXXpagina[count($XXXpagina)-1];
}
*/

if (strlen(trim($_GET["verificar_execucao"]))>0)  { $verificar_execucao = trim($_GET["verificar_execucao"]); }
if (strlen(trim($_GET["login_fabrica"]))>0)       { $login_fabrica      = trim($_GET["login_fabrica"]); }
if (strlen(trim($_GET["login_admin"]))>0)         { $login_admin        = trim($_GET["login_admin"]); }
if (strlen(trim($_GET["login_login"]))>0)         { $login_login        = trim($_GET["login_login"]); }
if (strlen(trim($_GET["login_fabrica_logo"]))>0)  { $login_fabrica_logo = trim($_GET["login_fabrica_logo"]); }

$XXXpagina = basename($PHP_SELF);
$data_ultimo_programa = date("Y-m-d");

$sql = "SELECT TO_CHAR(data,'YYYY-MM-DD') AS data, parametros
		FROM tbl_relatorio_agendamento 
		WHERE admin = $login_admin
		AND programa = '$PHP_SELF'
		ORDER BY data DESC 
		LIMIT 1";
$res = @pg_query($con,$sql);
if (is_resource($res)) {
	if (pg_num_rows($res) != 0) {
		list ($data_ultimo_programa, $parametros_relatorio) = pg_fetch_row($res, 0);
// 		echo "Parâmetros da consulta salva: $parametros_relatorio\n<br>";
	}
}

$relatorio_anterior = "/tmp/relatorios/relatorio_automatico_".$XXXpagina."-".$data_ultimo_programa.".".$login_fabrica.".".$login_admin.".htm";
$include      = trim($_GET["include"]);
if ( $include == "1" ){
	if(file_exists($relatorio_anterior) && filesize($relatorio_anterior) > 0){
		//echo $relatorio_anterior;
		include $relatorio_anterior;
		exit;
	}
	else if(file_exists($relatorio_anterior) && filesize($relatorio_anterior) == 0) {
		unlink($relatorio_anterior);
	}
}
if ($verificar_execucao=='verificar'){
	if(file_exists($relatorio_anterior)){
		echo "ok";
	}else{
		echo "nao";
	}
	exit;
}

?>
