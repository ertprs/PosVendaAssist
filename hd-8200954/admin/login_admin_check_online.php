<?php
	session_start();

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$timestamp 	= date("Y-m-d H:i:s");
	$url 		= $_POST['url'];
	$retorno 	= '0';

	if(!empty($_SESSION['session_admin'])){
		$admin_session = $_SESSION['session_admin'];

		if(!empty($admin_session['admin_online']) AND !empty($admin_session['fabrica']) AND !empty($admin_session['session_id'])){
			// Verifica se o usuario está usando uma sessão gravada no banco de dados
			// Caso ele não tenha resultado o usuário foi derrubado em outro login
			$sql_admin_online = "
							UPDATE tbl_admin_online SET
								programa = '{$url}',
								data_input = '{$timestamp}'
							WHERE 
								admin_online = {$admin_session['admin_online']}
								AND fabrica = {$admin_session['fabrica']}
								AND sessao = '{$admin_session['session_id']}'
						";
			$res_admin_online = pg_query($con, $sql_admin_online);

			// pg_affected_rows == 0 - A sessão foi destruida!
			if(pg_affected_rows($res_admin_online) == 0){
				$retorno = '0';
			} else {
				$retorno = '1';
			}
		} else
			$retorno = '0';

		if($admin_session['fabrica'] == 10)
			$retorno = '1';
	}


	exit('1');
	exit($retorno);

