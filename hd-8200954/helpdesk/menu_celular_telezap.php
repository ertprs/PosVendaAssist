<?php

// ini_set('display_errors', 'on');

require "../classes/autoload.php";

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_admin.php';

use \libphonenumber\PhoneNumberUtil;
use \libphonenumber\PhoneNumberType;


function validarCelular($celular){
	if (strlen(trim($celular)) > 0 && strlen(trim($celular)) == 11) {
		$phoneUtil = PhoneNumberUtil::getInstance();

		$celular          = $phoneUtil->parse("+55".$celular, "BR");
		$isValid          = $phoneUtil->isValidNumber($celular);
		$numberType       = $phoneUtil->getNumberType($celular);
		$mobileNumberType = PhoneNumberType::MOBILE;

		if (!$isValid || $numberType != $mobileNumberType) {
		    return "Número de Whatsapp Inválido ! Válido Somente Números do Brasil. <br />";
		}
    
	} else if (strlen(trim($celular)) > 0 && strlen(trim($celular)) != 11) {
		return "Número de Whatsapp Inválido ! Válido Somente Números do Brasil. <br />";
	}
}

$celular = filter_input(INPUT_POST, 'celular');
if( !empty($celular) ){

	$celular = trim(str_replace(["(",")"," ","-"], "", $celular));

	if (strlen($celular) < 10) {
		$msgError = "Whatsapp informado inválido";
	}

	if (strlen($celular) > 0) {

		$ddd = $celular[0] . $celular[1];

		if (intval($ddd) < 11) {

			$msgError = 'Número do whatsapp com DDD inválido';
		}
	}

	try{
		if( strlen($msgError) > 0 ){
			throw new Exception($msgError);
		}						


		$stmt = $pdo->prepare("UPDATE tbl_admin SET whatsapp = :whatsapp WHERE admin = :admin");
        	$stmt->bindValue(':admin', $login_admin);
		$stmt->bindValue(':whatsapp', $celular);
  
	          if( !$stmt->execute() OR $stmt->rowCount() == 0 ){
        	          throw new Exception('Erro ao buscar informações do usuário');
	          } 



		// Verifica se existe informação já cadastrada no campo parametros_adicionais
/*        $stmt = $pdo->prepare("SELECT parametros_adicionais FROM tbl_admin WHERE admin = :admin");
        $stmt->bindValue(':admin', $login_admin);

        if( !$stmt->execute() OR $stmt->rowCount() == 0 ){
        	throw new Exception('Erro ao buscar informações do usuário');
        }   
		
		// Recupera os parametros adicionais
        $userParametrosAdicionais = $stmt->fetch(PDO::FETCH_ASSOC)['parametros_adicionais'];

		// Verifica se existe alguma informaçao inserida no campo
		// Insere o celular
        if( empty($userParametrosAdicionais) ){
			$parametrosAdicionais = [];
			$parametrosAdicionais['celular'] = $celular;
			$parametrosAdicionais = json_encode($parametrosAdicionais);
		}else{
			$arrayTmp = json_decode($userParametrosAdicionais, true);
			$arrayTmp['celular'] = $celular;
			$parametrosAdicionais = json_encode($arrayTmp);
		}

		$stmt = $pdo->prepare("UPDATE tbl_admin SET parametros_adicionais = :parametrosAdicionais WHERE admin = :admin");	
		$stmt->bindValue(':parametrosAdicionais', $parametrosAdicionais);
		$stmt->bindValue(':admin', $login_admin);

		if( !$stmt->execute() OR $stmt->rowCount() == 0 ){
			throw new Exception('Erro ao cadastrar celular');
		}
*/
		$msgSuccess = true;

	}catch(Exception $e){
		$msgError = $e->getMessage();
	}
}

?>

<!DOCTYPE html>
<html lang="pt_br">
<head>
	<meta charset="UTF-8">
	<title>Cadastro de celular no Telezap</title>
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/bootstrap.css" />
	<script src="../admin/js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="../admin/js/jquery.mask.js"></script>
	<script>
		$(function(){
			var mask_field = $('#celular');

        	mask_field.mask('(00) 000000000');
		});
	</script>
</head>
<body style="background-color: #FFFFFF">
		
			<div style="background-color: #7D8FBB; padding: 5px !important">
				<h4 style="text-align: center; color: white"> Cadastre aqui seu Whatsapp</h4>
			</div>

		<div class="image-container" style="text-align: center; margin: 15px auto !important">
			<img src="imagem/logo.gif">
		</div>
		
		<hr/>

		<p style="text-align: center; margin-top: 15px !important; color: red" >Identificamos que seu whatsapp ainda não está cadastrado no sistema</p>

		<?php if( !empty($msgError) ){ ?>
			<div class="alert alert-error" style="text-align: center !important; margin: 5px auto !important;">
			  <strong>Atenção!</strong> <?= $msgError ?>
			</div>
		<?php } ?>
	
		<form action="" method="POST">
			<div style="text-align: center !important; margin-top: 15px !important">
				<div class="">
					<label for="cadastro-celular-telezap">Cadastre aqui o número do seu whatsapp</label>
					<div class="" style="text-align: center">
						<input type="text" placeholder="DDD + Nº Whatsapp" id="celular" name="celular" id="cadastro-celular-telezap" onkeyup="this.value=this.value.replace(/[^\d]/,'')">
						<br>
					</div>
					
					<div class="form-group-action">
						<button type="submit" class="btn btn-primary" style="padding: 4px 12px !important">Gravar</button>
					</div>
				</div>
			</div>
		</form>
	
	<script>
		<?= $msgSuccess ? 'window.parent.redirecionarUsuario()' : '' ?>	
	</script>
</body>
</html>
