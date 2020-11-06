<?php 
	
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';

	if(isset($_POST['cod'])){

		if($login_fabrica == 30){
			$info_sql = "('CREDENCIADO')";
		}else{
			$info_sql = "('CREDENCIADO', 'EM DESCREDENCIAMENTO')";
		}

		$cod = $_POST['cod'];
		$sql = "SELECT tbl_posto.posto,
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto AS codigo,
					tbl_posto_fabrica.nome_fantasia,
					tbl_posto.fone,
					tbl_posto_fabrica.contato_fone_comercial as contato_fone_comercial,
					tbl_posto_fabrica.contato_email as contato_email,
					tbl_posto_fabrica.contato_email as email
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_fabrica.posto = $cod
					AND tbl_posto_fabrica.credenciamento IN $info_sql";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			while($data = pg_fetch_object($res)){
				echo $data->nome."|";
				echo $data->contato_fone_comercial."|";
				echo $data->contato_email."|";
				echo $data->cnpj."|";
				echo $data->codigo;
			}	
		}
	}

?>