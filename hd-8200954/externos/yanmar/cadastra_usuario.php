<?php

header('Content-Type: text/html; charset=ISO-8859-1');

$titulo = "Cadastro de Usuário Aplicativo - Yanmar";

include "../../classes/Ticket/classes/Company.php";
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

	if(isset($_POST['gravar'])){
		$busca = false;
		$nome 			= anti_injection($_POST["nome"]);
		$sobrenome 		= anti_injection($_POST["sobrenome"]);
		$email 			= anti_injection($_POST["email"]);
		//$senha 			= anti_injection($_POST["senha"]);
		$company 		= anti_injection($_POST["company"]);

		if(strlen(trim($nome))==0){
			$msg_erro = "Informe o nome.<br>";
		}
		if(strlen(trim($sobrenome))==0){
			$msg_erro .= "Informe o sobrenome.<bR>";
		}
		if(strlen(trim($email))==0){
			$msg_erro .= "Informe o e-mail.<br>";
		}
		/*if(strlen(trim($senha))==0){
			$msg_erro .= "Informe a senha.<br>";
		}*/

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			$msg_erro .= "E-mail inválido.";
		}

		if(strlen(trim($msg_erro))==0){
			$dados['nome'] = utf8_encode($nome);
			$dados['sobrenome'] = utf8_encode($sobrenome);
			$dados['email'] = utf8_encode($email);
			//$dados['senha'] = utf8_encode($senha);

			$dados = json_encode($dados);

			$urlUser = "https://api2.telecontrol.com.br";
			$header = array( "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
				"access-env: PRODUCTION",
				"cache-control: no-cache",
				"content-type: application/json"			    
			);
			$user = new User($urlUser, $header);
			$response = $user->criaUsuario($dados);

			$retorno = json_decode($response, true);
			if( strlen(trim($retorno['exception']))>0){
				$msg_erro .= "Falha ao cadastrar usuário. <br> ".utf8_decode($retorno['exception']);
			}

			if(isset($retorno['user']['id'])){
				if(strlen(pg_last_error($con))==0){
					$ok .= "Cadastro realizado com sucesso";
				}
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
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			
				<div id="geral" class="col-sm-12"> 
					<form name="" method="POST">
					<!-- <div class='titulo_tabela tac'>
						Cadastro
					</div> -->
					<div class='logo'>
						<img src="image/logo_telecontrol.png" width="200">
					</div>
					<?php// if(strlen(trim($msg_erro))>0){ ?>
						<div class="alert alert-danger tac">Solicitar acesso via posto autorizado </div>
					<?php// } ?>
					<?php if(strlen(trim($ok))>0){ ?>
						<div class="alert alert-success"><?=$ok?></div>
					<?php } ?>

					<!--<div class=''>
						<form>
							<div class="form-group">
						    <label for="exampleInputEmail1">Nome</label>
						    <input type="text" class="form-control" id="nome" name="nome" value="<?=$nome?>">
						  </div>
						  <div class="form-group">
						    <label for="exampleInputEmail1">Sobrenome</label>
						    <input type="text" class="form-control" id="sobrenome" name="sobrenome" value="<?=$sobrenome?>">
						  </div>
						  <div class="form-group">
						    <label for="exampleInputEmail1">E-mail</label>
						    <input type="text" class="form-control"  id="email" name='email' value="<?=$email?>"  aria-describedby="emailHelp">
						  </div>
						  <!-- <div class="form-group">
						    <label for="exampleInputPassword1">Senha</label>
						    <input type="password" class="form-control" id="senha" name="senha" value="<?=$senha?>" >
						  </div> -->
						  <!--<center>
						  	<input type="submit" name="gravar" class="btn btn-primary" value="Gravar">
						  </center>
						</form>
					</div>
					</form>-->
				</div>
			
		</div>
	</div>

</body>
</html>