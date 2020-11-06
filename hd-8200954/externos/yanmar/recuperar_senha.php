<?php

header('Content-Type: text/html; charset=utf-8');
$titulo = "Recuperar Senha - Yanmar";
include "../../classes/Ticket/classes/User.php";

if (!function_exists('anti_injection')) {
    function anti_injection($string) {
        $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
        return strtr(strip_tags(trim($string)), $a_limpa);
    }
}

if(isset($_POST['recuperar_senha'])){

	$email = anti_injection($_POST["email"]);
	$error_msg = "";
	$error = false;
	$success = false;

	if(empty($email)){
		$error_msg = "Por favor informe o e-mail para prosseguir com a recuperação de senha.";
		$error = true;
	}

	if(!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)){
		$error_msg = "E-mail informado não é valido, tente inserir no formato: <a href='#'>exemplo@telecontrol.com.br</a>";
		$error = true;
	}

	if(!$error){

		$data['email'] = utf8_encode($email);
		$data['service'] = "app_checkin";
		$data = json_encode($data);

		$url = "https://api2.telecontrol.com.br";
		$header = array( 
			"access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			"access-env: PRODUCTION",
			"content-type: application/json"			    
		);

		$user = new User($url, $header);
		$response = json_decode($user->recuperaSenhaUsuario($data));

		if(!empty($response->exception)){
			$error_msg = "<strong>Ocorreu um erro ao enviar o email: </strong>" . $response->exception;
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
				<div class="col-md-4"></div>
				 <div class="col-md-4 logo">
					<img src="image/logo_telecontrol.png" class="img-responsive" width="200">
				</div>
				<div class="col-md-4"></div>	
			</div>
			<? if($error) : ?>
			<div class="row">
				<div class="col-md-4"></div>
			 	<div class="col-md-4">
		 			<div class="alert alert-danger" role="alert">
			  			<span><? echo $error_msg ?></span>
					  </div>
				</div>
				<div class="col-md-4"></div>
			</div>
			<? endif; ?>
			<? if($success) : ?>
			<div class="row">
				<div class="col-md-4"></div>
			 	<div class="col-md-4">
		 			<div class="alert alert-success" role="alert">
			  			<span>Verifique o seu email com o link para redefinir sua senha. Se não aparecer em alguns minutos, verifique a sua caixa de spam.</span>
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
                                <h3 class="text-center">Esqueceu a senha?</h3>
                                <p>Informe o e-mail cadastrado para recuperar a sua senha.</p>
                            </div>
			          		<input type="email" name="email" placeholder="Email" required class="form-control"/>
							<button type="submit" name="recuperar_senha" class="btn btn-block">Enviar e-mail de recuperação</button>
	        			</form>
	      			</section>  
	      		</div>
	      		<div class="col-md-4"></div>
  			</div>
		</div>
	</body>
</html>