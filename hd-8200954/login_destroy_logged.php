<?php 

	// Variáveis para determinar o servidor de login
	$http_server_name = $_SERVER['SERVER_NAME'];
	$http_referer	  = $_SERVER['HTTP_REFERER'];
	$origem_xhr       = $_SERVER['HTTP_ORIGIN'];

	$allowed_servers  = array(
		'http://www.telecontrol.com.br',
		'http://brasil.telecontrol.com.br',
		'http://urano.telecontrol.com.br',
		'http://telecontrol.no-ip.org',
		'http://telecontrol-urano.no-ip.org',
		'http://192.168.0.199',
		'http://ww2.telecontrol.com.br',
		'https://posvenda.telecontrol.com.br',
		'https://ww2.telecontrol.com.br'
	);

	/*******************************************************************************
	 * Estes headers são para habilitar o Cross Origin Resource Sharing (CORS), ou *
	 * o acesso desde outros domínios via XHR (AJAX)                               *
	 *******************************************************************************/
	if ($_SERVER['REQUEST_METHOD']== 'POST' and $origem_xhr != '' and in_array($origem_xhr, $allowed_servers)) {
		header("Access-Control-Allow-Methods: GET, POST");
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Headers: Content-Type, *");
		header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
	}

	include_once 'dbconfig.php';
	include_once 'includes/dbconnect-inc.php';

		$retorno = "KO";
		if($_REQUEST['mlg'] =='sim') {
			exit("OK");
		}
		if(intval($_POST['admin']) > 0){
			$sql = "DELETE FROM tbl_login_cookie WHERE admin = {$_POST['admin']};";
			pg_query($con, $sql);	

			$retorno = "OK";
		}
		
	exit($retorno);
