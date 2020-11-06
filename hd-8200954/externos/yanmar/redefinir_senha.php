<?php

header('Content-Type: text/html; charset=utf-8');
$titulo = "Redefinir senha - Yanmar";
include "../../classes/Ticket/classes/User.php";

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
    function anti_injection($string) {
        $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
        return strtr(strip_tags(trim($string)), $a_limpa);
    }
}

if(!function_exists('validate_password')) {
	function validate_password($string){
		if(preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $string)){
			return true;
		}else{
			return false;
		}
	}
}

if(isset($_POST['redefinir_senha'])){

	$token = anti_injection($_GET['token']);
	$senha = anti_injection($_POST["senha"]);
	$confirm = anti_injection($_POST["senha_confirm"]);
	$error_msg = "";
	$error = false;
	$success = false;

	if(empty($token)){
		$error_msg = "<strong>Token não informado:</strong> Por favor reenvie o e-mail e tente novamente.";
		$error = true;
	}

	if(empty($senha) && !$error){
		$error_msg = "Por favor informe a senha para prosseguir com a redefinição de senha.";
		$error = true;
	}

	if(empty($confirm) && !$error){
		$error_msg = "Por favor informe a confirmação da senha para prosseguir com a redefinição de senha.";
		$error = true;
	}

	if(!$error && !validate_password($senha)){
		$error_msg = "A nova senha informada é fraca e pode ser vulnerável. Sua senha não foi salva, por favor informe uma nova senha.";
		$error = true;
	}
	
	if(($senha != $confirm) && !$error){
		$error_msg = "A senha de confirmação não é igual a senha informada.";
		$error = true;
	}

	if(!$error){

		$data['senha'] = utf8_encode($senha);
		$data['token'] = $token;
		$data = json_encode($data);

		$url = "https://api2.telecontrol.com.br";
		$header = array( 
			"access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			"access-env: PRODUCTION",
			"content-type: application/json"			    
		);

		$user = new User($url, $header);
		$response = json_decode($user->alteraSenhaUsuario($data));

		if(!empty($response->exception)){
			$error_msg = "<strong>Ocorreu um erro ao redefinir a senha: </strong>" . $response->exception;
			$error = true;
		}else{
			$success = true;
		}
	}
}

?>

<!doctype html>
<html lang="pt-br">
	<head>
    	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<link rel="stylesheet" href="css/auth_usuario.css">
		<link href="../../imagens/tc_2009.ico" rel="shortcut icon">
		<script src="../js/jquery.min.js"></script>
    	<title><? echo $titulo ?></title>
  	</head>
	<body>
		<div class="container">
			<div class="row">
				 <div class="col-md-12 logo">
					<img src="image/logo_telecontrol.png" class="img-responsive" width="200">
				</div>	
			</div>
			<? if($error) : ?>
			<div class="row">
				<div class="col-md-4"></div>
		 	 	<div class="col-md-4 logo">
	 				<div class="alert alert-danger" role="alert">
			  			<span><? echo $error_msg ?></span>
				  	</div>
				</div>
				<div class="col-md-4"></div>
			</div>
			<?php endif; ?>
			<? if($success) : ?>
			<div class="row">
				<div class="col-md-4"></div>
			 	<div class="col-md-4">
		 			<div class="alert alert-success" role="alert">
			  			<span>Senha alterada com sucesso! Clique <a href="#">aqui</a> para fazer login. </span>
					  </div>
				</div>
				<div class="col-md-4"></div>
			</div>
			<? endif; ?>
	  		<div class="row">
	  			<div class="col-md-4"></div>
				<div class="col-md-4">
	      			<section>
	        			<form method="post">
	        				<div class="text-center info">
                                <h3 class="text-center">Alterar senha</h3>
                                <small>Informe a nova senha para a sua conta</small>
                            </div>
	        				<div class="form-group">
							    <label for="senha"><strong>Senha</strong></label>
							    <input type="password" class="form-control" required name="senha">
						  	</div>
						  	<div class="form-group">
							    <label for="senha_confirm"><strong>Confirmar senha</strong></label>
							    <input type="password" class="form-control" required name="senha_confirm">
						  	</div>
						  	<p class="note">A senha deve conter no mínimo 8 caracteres, e no mínimo um número, um caractere especial, uma letra maiúscula e minúscula.</p>
							<button type="submit" name="redefinir_senha" class="btn btn-block">Alterar senha</button>
	        			</form>
	      			</section>
	      		</div>
	      		<div class="col-md-4"></div>
  			</div>
		</div>
	</body>
</html>