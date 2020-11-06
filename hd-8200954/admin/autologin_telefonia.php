<?php
	
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';	
	//include 'token_cookie.php';
	include 'autentica_admin.php';

	$ajax = isset($_POST['ajax_retorna_sessao']);

	if ($ajax === false) {
		if ($_GET['fila']) {
			$fila = $_GET['fila'];			
			$sql_fila = "SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais ILIKE '%" . $fila . "%'";
		}

		$res_fila = pg_query($con, $sql_fila);
		$rows_fila = pg_fetch_assoc($res_fila);
	} else {
		$rows_fila = array("fabrica" => $_POST['fabrica_atendimento']);
	}	

	$sql_login = "SELECT admin, grupo_admin FROM tbl_admin WHERE fabrica = " . $rows_fila['fabrica'] . " AND login = '" . $login_login . "' AND ativo IS true";

	$res_login = pg_query($con, $sql_login);

	if (pg_num_rows($res_login) == 0){	
		if ($ajax === false) {
			header('Location: https://posvenda.telecontrol.com.br/assist/externos/login_posvenda_new.php' );
		} else {
			exit("Erro ao trocar sessão #1");
		}
	} 

	$rows_login = pg_fetch_assoc($res_login);

	$token_cookie = gera_token($rows_fila['fabrica'], $rows_login['admin'], "");

	if ($ajax === false) {
		$url = "https://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?a={$_GET['telefone']}&ligacao_id={$_GET['ligacaoUniqueId']}";
	} else {
		$url = $_POST['url_retorno'];		
	}

	set_cookie_login($token_cookie,array(
		"cook_admin" => $rows_login['admin'],
		"cook_grupo_admin" => $rows_login['grupo_admin'],
		"cook_fabrica" => $rows_fila['fabrica'],
		"cook_retorno_url" => $url,
		"cook_idioma" => "pt-br",
		"cook_posto_fabrica" => "",
    	"cook_posto" => ""
	));
		
	setcookie("sess",$token_cookie,0,"","",false,true);
	sleep(1);

	if ($ajax === false) {
		if($HTTP_HOST == "novodevel.telecontrol.com.br") {
			header("Location: http://novodevel.telecontrol.com.br/~breno/PosVendaAssist/admin/callcenter_interativo_new.php?a={$_GET['telefone']}&ligacao_id={$_GET['ligacaoUniqueId']}");
		} else {
			header("Location: https://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?a={$_GET['telefone']}&ligacao_id={$_GET['ligacaoUniqueId']}");		
		}
	} else {
		exit("true");
	}
