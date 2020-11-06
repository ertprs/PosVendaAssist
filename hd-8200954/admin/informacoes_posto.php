<?php 
	
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';

	if(isset($_POST['cod'])){

		if($login_fabrica == 30){
			$info_sql = "('CREDENCIADO')";
		}else{
			$info_sql = "('CREDENCIADO', 'EM DESCREDENCIAMENTO')";
		}

		$campo_adicional = '';
		$sql_fone_adicional = "";
		if($login_fabrica == 30){
			$sql_fone_adicional = ',tbl_posto_fabrica.contato_fone_residencial AS telefone2,
				   			   	   tbl_posto_fabrica.contato_cel AS telefone3';
			$campo_adicional = ',(SELECT tbl_tipo_posto.descricao FROM tbl_tipo_posto WHERE tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto) AS tipo_posto';
		}

		if (in_array($login_fabrica, array(169,170))){
			$campo_os_dealer = ", JSON_FIELD('abre_os_dealer',tbl_posto_fabrica.parametros_adicionais) AS abre_os_dealer ";
		}

		$cod = $_POST['cod'];
		$sql = "SELECT tbl_posto.posto,
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto AS codigo,
					tbl_posto_fabrica.nome_fantasia,
					tbl_posto.fone,
					tbl_posto_fabrica.obs,
					tbl_posto_fabrica.contato_fone_comercial as contato_fone_comercial,
					tbl_posto_fabrica.contato_email as contato_email,
					tbl_posto_fabrica.contato_email as email,
					tbl_posto_fabrica.contato_estado as contato_estado,
					tbl_posto_fabrica.contato_cidade as contato_cidade
					$sql_fone_adicional
					{$campo_adicional}
					{$campo_os_dealer}
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_fabrica.posto = $cod
					AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			while($data = pg_fetch_object($res)){
				echo $data->nome."|";
				echo $data->contato_fone_comercial."|";
				echo $data->contato_email."|";
				echo $data->cnpj."|";
				echo $data->codigo."|";
				echo $data->contato_estado."|";
				echo $data->contato_cidade;
				if (in_array($login_fabrica, array(169,170))){
					echo "|";
					echo $data->abre_os_dealer;
				}
				if($login_fabrica == 30){
					echo "|";
					echo $data->telefone2."|";
					echo $data->telefone3."|";
					echo $data->tipo_posto."|";
					echo $data->obs;
				}
			}	
		}
	}

?>
