<?php

header("Location:cadastra_usuario.php");
exit;


header('Content-Type: text/html; charset=ISO-8859-1');
/*
include "classes/Company.php";
include "classes/User.php";*/


$titulo  = "Cadastro de Usuário Aplicativo - Yanmar";
$fabrica = 148;
$busca = true;
$busca_usuario = false;

$url = "http://192.168.0.151:8000";
if($_POST['associar_usuario'] == true){
	$company 	= $_POST["company"];
	$user 		= $_POST["user"];

	$params = array('userExternalHash' => $user, "internalHash" => $company);
	$header = array( "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			"access-env: PRODUCTION",
			"cache-control: no-cache",
			"content-type: application/json"			    
	);

	$company = new Company($url, $header);
	$retorno = $company->enviaConvite($params);
	echo $retorno;

	exit;
}


//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
    function anti_injection($string) {
        $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
        return strtr(strip_tags(trim($string)), $a_limpa);
    }
}

if(isset($_POST['buscar'])){
	$busca  = false;
	$email 	= anti_injection($_POST["email"]);
	$cnpj 	= anti_injection($_POST["cnpj"]);

	if(strlen(trim($email))==0){
		$msg_erro .= "Informe o e-mail <br>";
	}

	if(strlen(trim($cnpj))==0){
		$msg_erro .= "Informe o CNPJ <br>";
	}

	if(strlen(trim($msg_erro))==0){
	//busca dados company
		$header = array( "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			"access-env: PRODUCTION",
			"cache-control: no-cache",
			"content-type: application/json"			    
		);
		$company = new Company($url, $header);
		$response = $company->buscaDadosCompany($cnpj);

		$companyJson = json_decode($response, true);
	  	if(isset($companyJson['exception'])){
			if(utf8_decode($companyJson['exception']) == "Companhia não encontrada"){
	  			$msg_erro .= "Posto autorizado não cadastrado. <br>
								Clique <a href='cadastrar_company.php'>aqui</a> para cadastrar.";
			}
	  	}else{
			$companyTitle = $companyJson[0]['document']['company']['companyTitle'];
			$companyActive = $companyJson[0]['document']['company']['active'];
			$companyInternalHash = $companyJson[0]['document']['company']['internalHash'];
			$companyDocument = $companyJson[0]['document']['documentNumber'];			
		}
		if(!empty($companyInternalHash)){
			//Dados usuário
			$busca_usuario = true;
	$urlUser = "https://api2.telecontrol.com.br";
			$header = array( "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
				"access-env: PRODUCTION",
				"cache-control: no-cache",
				"content-type: application/json"			    
			);
			$user = new User($urlUser, $header);
			$response = $user->buscaUsuario($email);
					
		  	$userJson = json_decode($response, true);
		  	if(isset($userJson['exception'])){
		  		//$msg_erro .= utf8_decode($userJson['exception']);
		  	}else{
		  		$userInternalHash 	= $userJson['user']['internal_hash'];
		  		$userEmail 			= $userJson['user']['email'];
		  	}	
		}else{
			$busca  = true;
		}
	}
}


?>

<!DOCTYPE html>
<html>
<head>
	<title>Cadastro de Usuário - App Checkin</title>
	<meta http-equiv="X-UA-Compatible" content="IE=8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="text/javascript" src="jquery.js"></script>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">


	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/js/tempusdominus-bootstrap-4.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css" />



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

	<script type="text/javascript">

		$(function(){
			$("#associar").click(function(){
				var company = $("#associar").data('company');
				var user = $("#associar").data('usuario');

				alert("company"+company+"uauario"+user);

				$.ajax({
		            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
		            data:{"associar_usuario": true, user:user, company:company},
		            type: 'POST',
		            beforeSend: function () {
		                $("#loading").show();
		                $("#associar").hide();
		            },
		            complete: function(data) {
		            data = $.parseJSON(data.responseText);
		            	if(data.exception){
		            		$(".convidar").text(data.exception);
		            	}else{
		            		$(".convidar").text("Convite enviado por e-mail.");
		            	}		                
		                $("#loading").hide();
		            }
		        });
			});
		});		

	</script>
</head>
<body>
	<div class="container">
		<div class="row">			
				<div id="geral" class="col-sm-12"> 
					<form name="" method="POST">

					<div class='logo'>
						<img src="image/logo_telecontrol.png" width="200">
					</div>
					<?php if(strlen(trim($msg_erro))>0){ ?>
						<div class="alert alert-danger"><?=$msg_erro?></div>
					<?php } ?>
					<?php if(strlen(trim($ok))>0){ ?>
						<div class="alert alert-success"><?=$ok?></div>
					<?php } ?>
					<?php if($busca){ ?>
					<div class=''>
						<form method="POST" >
						  <div class="form-group">
						    <label for="exampleInputEmail1">E-mail</label>
						    <input type="text" class="form-control"  id="email" name='email' value="<?=$email?>"  aria-describedby="emailHelp">
						  </div>
						  <div class="form-group">
						    <label >CNPJ</label>
						    <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?=$cnpj?>">
						  </div>
						  <center>
						  	<input type="submit" name="buscar" class="btn btn-primary" value="Buscar">
						  </center>
						</form>
					</div>
					</form>
					<?php } ?>
				</div>
			
		</div>
	<?php if(isset($companyInternalHash) AND isset($userInternalHash)){ ?>
		<div class="row">
			<div class="col-sm-12">
				<table class='table table-striped table-bordered table-hover table-fixed' >
				    <thead>
				        <TR class='titulo_tabela'>
							<th>Posto Autorizado</TD>
							<th>E-mail Técnico</th>
							<th>Ações</th>
				        </TR >
				    </thead>
				    <tbody>
				    	<tr>
				    		<td><?=$companyDocument ." - ".utf8_decode($companyTitle)?></td>
				    		<td><?=$userEmail?></td>
				    		<td class="tac convidar">
				    			<button type='button' id="associar" class="btn btn-primary" data-company="<?=$companyInternalHash?>" data-usuario="<?=$userInternalHash?>" >Convidar</button>
				    			 <img src="image/ajax-loader.gif" style="display: none; height: 20px; width: 20px;" id="loading" />
				    		</td>
				    	</tr>				    	
				    </tbody>
				</table>

			</div>
		</div>
	<?php }elseif($busca_usuario and  !isset($userInternalHash)){ ?>
			<Br>
			<div class="alert alert-warning" style="text-align:center">
				Não foi encontrado usuário para o e-mail <b> <?=$email?> </b> informado. <br> 
				Clique <a href="cadastra_usuario.php">aqui</a> para cadastrar um usuário
			</div>
	<?php }?>	
	</div>


	<div class="container">
	    <div class="row">
	        <div class="col-sm-6">
	            <input type="text" class="form-control datetimepicker-input" id="datetimepicker5" data-toggle="datetimepicker" data-target="#datetimepicker5"/>
	        </div>
	        <script type="text/javascript">
	            $(function () {
	            	var date = new Date();
        			date.setDate(date.getDate());

	                $('#datetimepicker5').datetimepicker(
	                	{ format: "DD/MM/YYYY HH:mm",
	                		defaultDate: new Date(2013, 11 - 1, 21)}
	                );
	            });
	        </script>
	    </div>
	</div>
</div>

</body>
</html>