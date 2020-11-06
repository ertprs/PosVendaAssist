<?php


header("Location:cadastra_usuario.php");
exit;

header('Content-Type: text/html; charset=ISO-8859-1');

$titulo        = "Cadastro de Usuário Aplicativo - Yanmar";
$fabrica = 148;

include "classes/Company.php";
include "classes/User.php";

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
    function anti_injection($string) {
        $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
        return strtr(strip_tags(trim($string)), $a_limpa);
    }
}

	if(isset($_POST['gravar'])){
		$nome_posto 		= $_POST['nome_posto'];
		$documento 			= $_POST['documento'];
		$nome_usuario 		= $_POST['nome_usuario'];
		$sobrenome_usuario 	= $_POST['sobrenome_usuario'];
		$email 				= $_POST['email'];

		$documento = str_replace(array("-", "/", ".", " "), "", $documento);

		if(strlen(trim($nome_posto))==0){
			$msg_erro = "Informe o nome do posto.<br>";
		}

		if(strlen(trim($documento))==0){
			$msg_erro .= "Informe o documento.<br>";
		}

		if(strlen(trim($nome_usuario))==0){
			$msg_erro .= "Informe o nome do usuário.<br>";
		}

		if(strlen(trim($sobrenome_usuario))==0){
			$msg_erro .= "Informe o sobrenome do usuário.<br>";
		}

		if(strlen(trim($email))==0){
			$msg_erro .= "Informe o email do usuário.<br>";
		}

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			$msg_erro .= "E-mail inválido.";
		}

		if(strlen(trim($msg_erro))==0){

			$dados['companhia']['nome'] = utf8_encode($nome_posto);
			$dados['companhia']['documento'] = $documento;
			$dados['usuario']['nome'] = utf8_encode($nome_usuario);
			$dados['usuario']['sobrenome'] = utf8_encode($sobrenome_usuario);
			$dados['usuario']['email'] = $email; 

			$params = json_encode($dados);

			$url = "http://192.168.0.151:8000";
			$header = array( "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
				"access-env: PRODUCTION",
				"cache-control: no-cache",
				"content-type: application/json"			    
			);
			$company = new Company($url, $header);
			$response = $company->criaCompany($params);
			$companyJson = json_decode($response, true);

			if(isset($companyJson['exception']) OR isset($companyJson['erro'])){
				$msg_erro .= $companyJson['exception'] . $companyJson['erro'];
			}else{
				$companyTitle = $companyJson['company']['companyTitle'];
				$companyActive = $companyJson['company']['active'];
				$companyInternalHash = $companyJson['company']['internalHash'];
				$ok = " Posto autorizado cadastrado com sucesso. ";
			}			
		}
	}

?>

<!DOCTYPE html>
<html>
<head>
	<title>Autenticação</title>
	<meta http-equiv="X-UA-Compatible" content="IE=8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style>

		.titulo_tabela{
		    background-color: #596d9b;
		    font: bold 16px "Arial";
		    color: #FFFFFF;
		    text-align: center;
		    padding: 10px 6px;
		}
		.tac{
		    text-align: center;
		}
		.logo{
			text-align: center;
			padding: 20px; 
		}
		.legend{
			font: bold 16px "Arial";
		    color: #596d9b;
		    padding: 30px 6px 10px 6px;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			
				<div id="geral" class="col-sm-12"> 
					<form name="" method="POST">
					<div class='logo'>
						<img src="image/logo_telecontrol.png" width="200">
					</div>
					<div class='titulo_tabela tac'>
						Cadastro de Posto Autorizado
					</div>
					<?php if(strlen(trim($msg_erro))>0){ ?>
						<div class="alert alert-danger"><?=$msg_erro?></div>
					<?php } ?>
					<?php if(strlen(trim($ok))>0){ ?>
						<div class="alert alert-success"><?=$ok?></div>
					<?php } ?>

					<div class=''>
						<form name="frm_company" method="POST">						
						<fieldset class="form-group">
						  <legend class="col-form-label legend col-sm-12">Dados do Posto Autorizado</legend>
						  <div class="form-group">
						    <label for="exampleInputEmail1">Nome Posto</label>
						    <input type="text" class="form-control" id="nome_posto" name="nome_posto" value="<?=$nome_posto?>">
						  </div>
						  <div class="form-group">
						    <label for="exampleInputEmail1">CNPJ</label>
						    <input type="text" class="form-control" id="documento" name="documento" maxlength="20" value="<?=$documento?>">
						  </div>
						</fieldset>

						<fieldset class="form-group">
						  <legend class="col-form-label legend col-sm-12">Dados do Usuário Responsável</legend>
						  <div class="form-group">
						    <label for="exampleInputEmail1">Nome Usuário</label>
						    <input type="text" class="form-control" id="nome_usuario" name="nome_usuario" value="<?=$nome_usuario?>">
						  </div>
						  <div class="form-group">
						    <label for="exampleInputEmail1">Sobrenome Usuário</label>
						    <input type="text" class="form-control" id="sobrenome_usuario" name="sobrenome_usuario" value="<?=$sobrenome_usuario?>">
						  </div>
						  <div class="form-group">
						    <label for="exampleInputEmail1">E-mail Usuário</label>
						    <input type="text" class="form-control"  id="email" name='email' value="<?=$email?>"  aria-describedby="emailHelp">
						  </div>
						</fieldset>
						  <center>
						  	<input type="submit" name="gravar" class="btn btn-primary" value="Gravar">
						  </center>
						</form>
					</div>
					</form>
				</div>
			
		</div>
	</div>

</body>
</html>