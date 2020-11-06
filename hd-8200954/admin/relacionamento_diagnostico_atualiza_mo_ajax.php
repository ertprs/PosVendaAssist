<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';

header('Content-Type: text/html; charset=ISO-8859-1');

if (isset($_GET["diagnostico"])){
	$diagnostico = trim ($_GET["diagnostico"]);
	$mao_de_obra = trim ($_GET["mao_de_obra"]);
	$tipo = trim ($_GET["tipo"]);

	$mao_de_obra = str_replace(',','.', $mao_de_obra);


	$sql = "SELECT  diagnostico
			FROM tbl_diagnostico
			WHERE fabrica = $login_fabrica 
				AND diagnostico = $diagnostico";
	//echo "sql: $sql";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0 and strlen($mao_de_obra)>0){
		$res = pg_exec($con,"BEGIN;");
		
		if ($tipo == 'consumidor') {
		$sql = "UPDATE tbl_diagnostico
				SET		mao_de_obra      = $mao_de_obra,
						admin            =  $login_admin,
						data_atualizacao = current_timestamp
				WHERE fabrica = $login_fabrica 
					AND diagnostico = $diagnostico";
		}
		else {
		$sql = "UPDATE tbl_diagnostico
				SET		mao_de_obra_revenda    = $mao_de_obra,
						admin                  = $login_admin,
						data_atualizacao       = current_timestamp
				WHERE fabrica = $login_fabrica 
					AND diagnostico = $diagnostico";
		}
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro)==0) {
			$res = pg_exec($con,"COMMIT;");
			echo "Mуo de Obra atualizada: $mao_de_obra";
		}else{
			$res = pg_exec($con,"ROLLBACK;");
			echo "Erro na atualizaчуo de Mуo de Obra";
		}
	}else{
		if(strlen($mao_de_obra)==0){
			echo "Nуo atualizou Mуo de Obra.";
		}else{
			echo "Nуo encontrado.";
		}
	}
	exit;
}


?>