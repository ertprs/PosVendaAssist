<?php
// define('URI', 'http://api.telecontrol.local/');
define('URI', 'http://api.telecontrol.com.br/posvenda/');
//define('URI', 'http://192.168.0.39:8000/');


include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

#---Autenticação
$fabrica = $_COOKIE['cook_fabrica'];
$admin = $_COOKIE['cook_admin'];


if(strlen($fabrica) > 0 and strlen($admin) > 0){
	$sql = " select login, senha from tbl_admin where fabrica = ".$fabrica." and admin = ".$admin." and ativo = true;";

	$ret = pg_exec($con,$sql);
	$count = pg_num_rows($ret);
	if($count <= 0){
		header ("Location: ../index.php");
		exit;
	}	
}else{
	header ("Location: ../index.php");
	exit;
}



#---Autenticação




include __DIR__ . '/apis_config.php';
include __DIR__ . '/curlApi.php';
include __DIR__ . '/validaTabela.php';


// Nº máximo de  registros a serem  processados individualmente.
// Superado o limite, é enviado um único  pacote em formato JSON
// para o servidor processar como um lote.
$api_max_req_session = 5;


function msgError($error = Array()){
	if(count($error) == 0)
		return null; 

	$str = "<div class='alert alert-error'><strong>Error!</strong>".
		 '<button type="button" class="close" data-dismiss="alert">&times;</button>'.
		 "<ul class='nav nav-tabs nav-stacked'>";

	if (is_array($error)) {
		foreach ($error as $key => $erro) {
			$str .= "<li><strong>{$key}</strong><br />\n" . implode("<br />", $erro) . "</li>";
		}
	} else {
		$str .= $error;
	}

	return $str . "</ul></div>";
}
