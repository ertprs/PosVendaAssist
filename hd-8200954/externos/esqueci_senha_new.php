<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
} 

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

header("Content-Type: text/html;charset=utf-8");

include_once '../class/communicator.class.php';
include_once '../classes/Posvenda/Seguranca.php';

$mailer = new TcComm("smtp@posvenda");
$objSeguranca = new \Posvenda\Seguranca(null,$con);



/*  26/11/2009  MLG - Convertendo a tradução ao novo padrão, corrigindo e acrescentando o que
					  está faltando.
					  Também passada para função a criação do e-mail.
					  Isto permite fazer igual o e-mail do posto, do admin e o de amostra,
					  sem repetir desnecessáriamente o texto em todos os idiomas
*/

//  Carrega a função de tradução
if (!function_exists('ttext')) {
	include 'trad_site/fn_ttext.php';
}


//  Array com a tradução
$a_rec_senha = array (
	"esqueci_senha" => array (
		"pt-br" => "Esqueci minha Senha",
		"es"    => "Olvidé mi Contraseña",
		"en"    => "Forgot my password",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"enviado_email" => array (
		"pt-br" => "Foi enviado um e-mail para",
		"es"    => "Se ha enviado un mensaje a",
		"en"    => "An e-mail has been sent to",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"nome" => array (
		"pt-br" => "Nome",
		"es"    => "Nombre",
		"en"    => "Name",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"email_incorreto" => array (
		"pt-br" => "O e-mail digitado está incorreto!",
		"es"    => "¡La dirección de correo es incorrecta!",
		"en"    => "The e-mail address is not correct!",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"titulo" => array (
		"pt-br" => "Recuperar senha de acesso",
		"es"    => "Recuperar datos de acceso",
		"en"    => "Retrieve access data",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"email_inexixtente_ou_nao_master" => array(
		"pt-br" => "Cadastro não encontrado ou este e-mail não é do login-unico MASTER, preencha corretamente seu e-mail.",
		"es"	=> "Registro no encontrado, o este correo electrónico no es del Login Único MASTER, escriba la dirección correcta.",
		"en"	=> "Record not found, or this is not the Unique Login MASTER's e-mail, please type in the correct e-mail address."
	),
	"digite_o_email" => array(
		"pt-br" => "Preencha o e-mail",
		"es"    => "Escriba la dirección de correo electrónico",
		"en"    => "Type in the e-mail address",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"selecione_o_fabricante" => array(
		"pt-br" => "Selecione o Fabricante",
		"es"    => "Seleccione el Fabricante",
		"en"    => "Select the Manufacturer",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"selecione_o_cnpj" => array(
		"pt-br" => "Selecione o CNPJ",
		"es"    => "Seleccione el CNPJ",
		"en"    => "Select the CNPJ field",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"digite_o_email" => array(
		"pt-br" => "E-mail",
		"es"    => "Cibercorreo",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"faca_uma_nova" => array (
		"pt-br" => "Faça uma nova agora mesmo",
		"es"    => "Hacer una nueva en este momento",
		"en"    => "Make a new right now",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"esqueceu_senha" => array (
		"pt-br" => "Esqueceu sua senha?",
		"es"    => "¿Ha olvidado su clave?",
		"en"    => "Forgot your password?",
		"de"    => "Kennwort vergessen?",
		"zh-cn" => "忘记密码?",
		"zh-tw" => "忘記密碼?"
	),
	"preencha_email" => array (
		"pt-br" => "Preencha o e-mail que está cadastrado no sistema e selecione a fábrica que você esqueceu login e senha, para que seja enviado para seu e-mail.",
		"es"    => "Escriba su dirección de correo-e que consta en el registro y el fabricante cuyo usuario/clave ha perdido y le enviaremos un mensaje con los datos de acceso.",
		"en"    => "Fill out the e-maill that is registered in the system and select the factory you forgot the login and password and it will be sent to your e-mail.",
		"de"    => "Teilen Sie uns Ihr mail mit mit dem Sie bei uns angemeldet sind und die Firma für die Sie das den Usernamen und das Kennwort vergessen haben. Das Kennwort wird an Ihre Mailanschrift versandt.",
		"zh-cn" => "填写注册的电子信箱后选择所忘记的厂商用户名和密码，使系统可以发送E-MAIL到您的信箱。",
		"zh-tw" => "填寫註冊的電子信箱後選擇所忘記的廠商用戶名和密碼，使系統可以發送E-MAIL到您的信箱。"
	),
	"email" => array (
		"pt-br" => "E-mail",
		"es"    => "Cibercorreo",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"login_unico" => array (
		"pt-br" => "Login Único",
		"es"    => "Login Único",
		"de"    => "General-Login",
		"en"    => "Unique Login",
		"zh-cn" => "單一登录",
		"zh-tw" => "單一登录"
	),
	"fabrica" => array (
		"pt-br" => "Fábrica",
		"es"    => "",
		"en"    => "Brand",
		"de"    => "Firma",
		"zh-cn" => "公司",
		"zh-tw" => "公司"
	),
	"Selecionar" => array (
		"pt-br" => "",
		"es"    => "Seleccionar",
		"en"    => "Select",
		"de"    => "",
		"zh-cn" => "选择",
		"zh-tw" => "選擇"
	),
	"email_cadastro" => array (
		"pt-br" => "Por favor, digitar o email cadastrado",
		"es"    => "Por favor, use la dirección electrónica de su usuario.",
		"en"    => "Please, type in the registered e-mail.",
		"de"    => "",
		"zh-cn" => "请填写注册的电子信箱",
		"zh-tw" => "請填寫註冊的電子信箱"
	),
	"selecionar_fabrica" => array (
		"pt-br" => "Selecionar Fábrica.",
		"es"    => "Seleccione Fábrica",
		"en"    => "Select Factory",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"nome_cadastro" => array (
		"pt-br" => "Por favor, digitar o nome cadastrado",
		"es"    => "Por favor, use el nombre de su usuario.",
		"en"    => "Please, type in the registered name.",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"apos_enviar" => array (
		"pt-br" => "Após clicar <b>em Enviar</b>, você receberá um e-mail como o abaixo:",
		"es"    => "Tras pulsar en 'Enviar', recibirá un e-mail como éste:",
		"en"    => "Next click Send, you will receive an e-mail like the one below:",
		"de"    => "Nach einem Klick auf “Senden” erhalten Sie folgende mail:",
		"zh-cn" => "在按下寄出之后，您会收到内容如下的E-MAIL:",
		"zh-tw" => "在按下寄出之後，您會收到內容如下的E-MAIL:"
	),
	"Usuario" => array (
		"pt-br" => "Usuário",
		"es"    => "Usuario",
		"en"    => "User",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"Nome_Da_Fabrica" => array (
		"pt-br" => "NOME DA FÁBRICA",
		"es"    => "NOMBRE DE LA FÁBRICA",
		"en"    => "so and so Factory",
		"de"    => "Firma Beispiel",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"posto_multiplo" => array (
		"pt-br" => "Existem 2 ou mais postos cadastrados com esse e-mail",
		"es"    => "Hay 2 o más puestos registrados con este correo electrónico",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"escolha_posto" => array (
		"pt-br" => "Selecione um dos postos abaixo",
		"es"    => "Elija uno de los puestos para enviar los datos",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	)		
);

if (isset($_POST["ajax_verifica_email"])) {
	$fabrica = $_POST["fabrica"];
	$email   = $_POST["email"];

	$sql = "SELECT  tbl_posto.nome                  AS posto_nome  ,
					tbl_posto.cnpj 					AS posto_cnpj  ,	 
			        tbl_posto_fabrica.codigo_posto  AS posto_codigo,
			        CASE WHEN contato_email IS NULL OR LENGTH(contato_email) = 0
						 	  AND tbl_posto.email IS NOT NULL
						 THEN tbl_posto.email
						 ELSE contato_email
			        END                             AS posto_email ,
			        tbl_posto_fabrica.senha         AS posto_senha ,
			        tbl_fabrica.nome                AS fabrica_nome
			    FROM  tbl_posto
			    JOIN  tbl_posto_fabrica USING (posto)
			    JOIN  tbl_fabrica       USING (fabrica)
			    WHERE (LOWER(TRIM(contato_email))       = '$email'
			       OR  LOWER(TRIM(tbl_posto.email))     = '$email')
				  AND  tbl_posto_fabrica.credenciamento  IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
			      AND  tbl_posto_fabrica.fabrica = $fabrica
			      ";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 1){ 
		$postos_mesmo_email = pg_fetch_all($res);
		?>
			<input type="hidden" name="posto_multiplo" value="t" />
			<br />
				<?php
				echo "<h3 style='text-align: center;font-size: 17px;font-weight: 500;'>".ttext($a_rec_senha,"escolha_posto")."</h3>";

				$quantidade_postos = 0;

				foreach($postos_mesmo_email as $valor) { 

					$cnpj_posto 		= $valor["posto_cnpj"];
					$nome_posto 		= $valor["posto_nome"];

				?>
				<style>
					.checkbox:hover {
						background-color: #f2f2f2;
					}
				</style>
				<label>
					<div class="checkbox">
							<div style="display: inline-block;width: 10%;text-align: left;">
								<input class="check_cnpj" type="checkbox" name="posto_cnpj_<?= $quantidade_postos ?>" value="<?= $cnpj_posto ?>" />
							</div>
						    <div style="display: inline-block;width: 50%;text-align: left;font-size: 13px;">
						    	<?= substr($nome_posto,0,35) ?>
						    </div>
						    <div style="display: inline-block;width: 10%;text-align: right;">
						    	<input style="border-bottom: none;text-align: right;" class="cnpj" value="<?= $cnpj_posto ?>" disabled="disabled" />
						    </div>
					</div>
				</label>

			<?php
				$quantidade_postos++;
				} ?>
				<input type="hidden" name="qtde_postos" value="<?= $quantidade_postos ?>" />
	<?php
	}
exit;
}

if ($_POST["ajax_carrega_fabrica_email"]) {
	$email = trim($_POST["email"]);

	$result = [];

	$sql = "SELECT DISTINCT UPPER(tbl_fabrica.nome) AS nome,
				   tbl_fabrica.fabrica
		      FROM tbl_fabrica
		      JOIN tbl_posto_fabrica USING(fabrica)
			 WHERE tbl_fabrica.ativo_fabrica IS TRUE
			   AND tbl_fabrica.fabrica NOT IN(10,46,63,92,93,109,130,133)
			   AND tbl_fabrica.nome !~* 'pedidoweb'
			   AND UPPER(tbl_posto_fabrica.credenciamento) = 'CREDENCIADO'
			   AND UPPER(tbl_posto_fabrica.contato_email) = UPPER('$email')
			 ORDER BY nome";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		foreach (pg_fetch_all($res) as $key => $value) {
			
			$nome = (mb_detect_encoding($value["nome"], "UTF-8")) ? mb_convert_case(utf8_encode($value["nome"]), MB_CASE_UPPER, 'UTF-8') : $value["nome"];

			$result[] = [
							"fabrica"=>$value['fabrica'],
							"nome"=>$nome
						];
		}
	} else {
		$sql = "SELECT DISTINCT UPPER(tbl_fabrica.nome) AS nome,
						tbl_fabrica.fabrica
				FROM tbl_fabrica
				JOIN tbl_admin USING(fabrica)
				WHERE tbl_fabrica.ativo_fabrica IS TRUE
					AND tbl_fabrica.fabrica NOT IN(10,46,63,92,93,109,130,133)
					AND tbl_fabrica.nome !~* 'pedidoweb'
					AND UPPER(tbl_admin.email) = UPPER('$email')
				ORDER BY nome";
				
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			foreach (pg_fetch_all($res) as $key => $value) {
				
				$nome = (mb_detect_encoding($value["nome"], "UTF-8")) ? mb_convert_case(utf8_encode($value["nome"]), MB_CASE_UPPER, 'UTF-8') : $value["nome"];
	
				$result[] = [
								"fabrica"=>$value['fabrica'],
								"nome"=>$nome
							];
			}
		}else{
			$result = ['error'=>'Nenhum resultado encontrado'];
		}
	}

	echo json_encode($result);
	exit();
}


$html_titulo = ttext($a_rec_senha, "titulo");
$body_options = "onload='document.frm_es.email.focus() ;' ";
include "site_estatico/header.php";


if( $_POST['btn_acao']=='Enviar') {
	$reCaptcha   = $_POST["g-recaptcha-response"];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api2.telecontrol.com.br/institucional/CaptchaV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            "response"   => $reCaptcha,
            "privateKey" => "6LckVVIUAAAAAJvDmHg7_2zDSOKuD7ZABc7MNL2H",
            "ip"         => $_SERVER['REMOTE_ADDR']
        ]),
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "Content-Type: application/json"
        ),
    ));

    $response  = curl_exec($curl);
    $err       = curl_error($curl);

    $objetoRetorno = json_decode($response,1);

    if (!$objetoRetorno["success"]) {
        $msg_erro = "Favor selecionar o re-Captcha";
    }

	$email       = strtolower(trim($_POST['email']));
	$fabrica     = trim($_POST['fabrica']);
	$login_unico = trim($_POST['login_unico']);
	$cnpj 		 = trim($_POST['cnpj']);

	if(strlen($email)==0)	$msg_erro = ttext($a_rec_senha, "digite_o_email");
	if (empty($login_unico)){
		if(strlen($fabrica)==0)	$msg_erro .= ttext($a_rec_senha, "selecione_o_fabricante");
	}
	if (!empty($login_unico) and $login_unico == 't'){

		if (empty($msg_erro)){

			$sql = "SELECT login_unico,nome,email, parametros_adicionais FROM tbl_login_unico WHERE email = '$email' AND ativo AND master";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0){
				$msg_erro .= ttext($a_rec_senha, "email_inexixtente_ou_nao_master");
			}else{
				$lu       = pg_fetch_result($res, 0, 0);
				$lu_nome  = pg_fetch_result($res, 0, 1);
				$lu_email = pg_fetch_result($res, 0, 2);
				$parametros_adicionais = pg_fetch_result($res, 0, 3);

				$lu_md5 = md5($lu);
			}

		}

	}

	if(strlen($msg_erro) ==0) {

		$tipo_email = ($lu) ? 'login_unico' : 'normal';

		/*  08/10/2009  MLG - Ao fazer a condição com '=' no WHERE, não pegava e-mails com espaços antes ou depois,
		 *              Agora compara com TRIM, para não ter esse problema, e o valor recuperado em 'posto_email'
		 *              também é TRIM(campo).
		*/
		// $body_top = "--Message-Boundary\n";
		// $body_top.= "Content-type: text/html; charset=iso-8859-1\n";
		// $body_top.= "Content-transfer-encoding: 7BIT\n";
		// $body_top.= "Content-description: Mail message body\n\n";

		if ($tipo_email == 'normal'){

			$posto_multiplo = trim($_POST["posto_multiplo"]);

			if ($posto_multiplo == "t") {
				$qtde = $_POST["qtde_postos"];

				for ($x=0;$x<$qtde;$x++) {
					if (isset($_POST["posto_cnpj_".$x])){
						$cnpj_selecionado = pg_escape_literal($_POST["posto_cnpj_".$x]);
					}
				}

				if (!empty($cnpj_selecionado)) {
					$cond_cnpj = " AND tbl_posto.cnpj = $cnpj_selecionado";
				} else {
					$msg_erro    = ttext($a_rec_senha, "escolha_posto");
				}
			}

			if (empty($msg_erro)) {
				$sql = "SELECT  tbl_posto.nome                  AS posto_nome  ,
								tbl_posto.cnpj 					AS posto_cnpj  ,	 
						        tbl_posto_fabrica.codigo_posto  AS posto_codigo,
								 contato_email                      AS posto_email ,
						        tbl_posto_fabrica.senha         AS posto_senha ,
						        tbl_fabrica.nome                AS fabrica_nome,
								tbl_posto_fabrica.posto_fabrica,
								tbl_posto_fabrica.parametros_adicionais
						    FROM  tbl_posto
						    JOIN  tbl_posto_fabrica USING (posto)
						    JOIN  tbl_fabrica       USING (fabrica)
						    WHERE (LOWER(TRIM(contato_email))       = '$email' )
							  AND  tbl_posto_fabrica.credenciamento  IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
						      AND  tbl_posto_fabrica.fabrica = $fabrica
						      $cond_cnpj
						      ";

				$res = pg_query($con, $sql);

				if (!is_resource($res)) $msg_erro = pg_last_error($con);

				//die('Qtde.: "' . pg_last_error($con) .  '" / ' . pg_num_rows($res));

				if(pg_num_rows($res) > 0 and strlen($msg_erro) == 0) {
						extract(pg_fetch_assoc($res, 0));
						$posto_fabrica  = pg_fetch_result($res, 0, 'posto_fabrica');
						$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');

						
						$res = $objSeguranca->envio_email($tipo_email, $posto_fabrica, $posto_nome, $fabrica_nome, $cook_login, $posto_email, ttext($a_rec_senha, "esqueci_senha"), $mailer,$con);
						
						if ($res === true){
							$msg = ttext($a_rec_senha, "enviado_email").": $email";
							$sucesso = "1";
						}else{
							$msg_erro.= ttext($a_rec_senha, "email_incorreto");
						}
				}else {

					$fabrica = trim($_POST["fabrica"]);
					$email   = trim($_POST["email"]);

					$sql = "SELECT  login,
									senha,
									TRIM(email) AS email,
									nome
							FROM tbl_admin
							JOIN tbl_fabrica USING(fabrica)
							WHERE TRIM(email) = '$email'
							AND   ativo
							AND fabrica = $fabrica";

					$res = pg_query($con,$sql);
					$msg_erro.= pg_last_error($con);

					if(pg_num_rows($res) > 0 and strlen($msg_erro) == 0) {

						$email         = pg_fetch_result($res, 0, 'email');
						$fabrica_nome  = pg_fetch_result($res, 0, 'nome');

						$res = $objSeguranca->envio_email($tipo_email, $posto_fabrica, $posto_nome, $fabrica_nome, $cook_login, $email, ttext($a_rec_senha, "esqueci_senha"), $mailer,$con);

						if ($res === true){
							$msg = ttext($a_rec_senha, "enviado_email").": $email";
							$sucesso = "1";
						}else{
							$msg_erro.= ttext($a_rec_senha, "email_incorreto");
						}

					}else{

						$msg_erro.= ttext($a_rec_senha, "email_incorreto");

					}

				}
			}
			
		}elseif($tipo_email == 'login_unico'){ //ENVIO DE EMAIL PARA QUANDO O POSTO DESEJA RECUPERAR O LOGIN UNICO

			$subject = "Telecontrol - ".ttext($a_rec_senha, "esqueci_senha");
			$token         = $objSeguranca->token($email_destino, $fabrica);

			$res = $objSeguranca->envio_email($tipo_email, $lu, $lu_nome, "", $cook_login, $lu_email, ttext($a_rec_senha, "esqueci_senha"), $mailer, $con);

			if ($res === true){
				$msg = ttext($a_rec_senha, "enviado_email").": $lu_email";
				$sucesso = "1";
			}else{
				$msg_erro.= ttext($a_rec_senha, "email_incorreto");
			}
		}

	}

}



?>
<!-- <script src="http://code.jquery.com/jquery-latest.min.js"></script> -->
<script type="text/javascript">

	$(function() {

		if ($("#fabrica").val() != "" && $("#email").val() != "" && $("#sucesso_email").val() != "1") {
			var email   = $("#email").val();
			var fabrica = $("#fabrica").val();
			busca_posto_multiplo(email,fabrica);
		}

		$("#fabrica").change(function(){
			var email   = $("#email").val();
			var fabrica = $(this).val();

			busca_posto_multiplo(email,fabrica);
		});

		$("#email").blur(function(){
			var email   = $(this).val();
			var fabrica = $("#fabrica").val();

			if (email != '' && email != undefined) {
				carrega_fabricas_email(email);
			} else {
				$("#fabrica").html("");
				$("#fabrica").append("<option value=''>Informar o Email para selecionar a fábrica</option>");
			}

			busca_posto_multiplo(email,fabrica);
		});

		if ($("#login_unico").is(":checked")){
			$("#fabrica_p").hide();
			$("#exlu").show();
			$("#ex").hide();
		}else{
			$("#fabrica_p").show();
			$("#ex").show();
			$("#exlu").hide();
		}

		$(document).on("click",".check_cnpj", function(){
			var cnpj = $(this).val();

			$(".check_cnpj").each(function(){
				if ($(this).val() != cnpj) {
					$(this).prop("checked", false);
				}
			});
		});

		$("#login_unico").click(function(){

			if ($(this).is(":checked")){
				$("#fabrica_p").slideUp("fast");
				$("#exlu").show();
				$("#ex").hide();

			}else{

			$("#fabrica_p").slideDown("fast");
			$("#ex").show();
			$("#exlu").hide();
			}

		});


	});

	function busca_posto_multiplo(email,fabrica) {
		if (email != "" && fabrica != "") {
			$.ajax({
		        async: true,
		        type: 'POST',
		        url: 'esqueci_senha_new.php',
		        data: {
		            ajax_verifica_email:true,
		            fabrica:fabrica,
		            email:email
		        },
		    }).done(function(data) {
	    		$("#posto_multiplo_cnpj").html(data);

	    		$(".cnpj").mask("99.999.999/9999-99");

		    });
		}
	}

	function carrega_fabricas_email(email, fabrica = null) {

		$("#fabrica").html("");

		$.ajax({
	        async: true,
	        type: 'POST',
	        url: 'esqueci_senha_new.php',
	        dataType:"json",
	        data: {
	            ajax_carrega_fabrica_email:true,
	            email:email
	        },
	    }).done(function(data) {
	    	
	    	if (data.error) {
	    		alert("Email informado não foi encontrado em nenhum fabricante.");
	    	} else {
	    		$("#fabrica").append("<option value=''>Selecione uma Fábrica</option>");
	    		let selected = '';
		    	for (let i = 0; i < data.length; i++) {
		    		selected = '';
		    		if (fabrica == data[i].fabrica) {
		    			selected = "selected";
		    		} 
		    		$("#fabrica").append("\
                                <option value='"+data[i].fabrica+"' "+selected+">"+data[i].nome+"</option>\
                            ");
		    	}
	    	}
	    });
	}

</script>
<script src='../admin/plugins/jquery.maskedinput_new.js'></script>
<script type="text/javascript" src="../js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="../ajax.js"></script>
<script src='https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit' async defer></script>
<script>$('body').addClass('pg log-page')</script>
<script type="text/javascript">
	var showRecaptcha = function() {
		grecaptcha.render('reCaptcha', {
			'sitekey' : '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
		});
	};

</script>

<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2><?=ttext($a_rec_senha,"esqueceu_senha")?></h2></div>
		<h3><?=ttext($a_rec_senha,"faca_uma_nova")?></h3>
	</div>
</section>
<?php

if ($msg != '') {
	$class_mensagem = "email_sucesso";
	$mensagem = $msg;
	echo "<script>limpa_campo_esqueci_senha('');</script>";
	$display_success = "style='display:block;'";
}

if ($msg_erro != '') {
	$class_mensagem = "erro_campos_obrigatorios";
	$mensagem = $msg_erro;
	$display_error = "style='display:block;'";
}
?>
<section class="pad-1 login">
	<div class="main">
		<div class="alerts">
			<!--  ALGUMAS MSG DE ERRO VEM DO ARQUIVO esqueceu_senha.js -->
			<div class="alert success" <?=$display_success?> id="mensagem_envio_success"><i class="fa fa-check-circle"></i><?php echo $mensagem;?></div>

			<div class="alert error" <?=$display_error?> id="mensagem_envio"><i class="fa fa-exclamation-circle"></i><?php echo $mensagem;?></div>
		</div>

		<div class="desc">
			<h3>
				<?=ttext($a_rec_senha,"preencha_email");?>
			</h3>
		</div>
		<div class="sep"></div>
		<form name='frm_es' id='frm_es' method='post' action='<?=$PHP_SELF?>'>
			<input type='hidden' name='btn_acao' value='Enviar' >

			<input name ="email" id='email' type="text" maxlength='50' value="<?=$email ?>" placeholder="<?=ttext($a_rec_senha,"email")?>">

			<div id='nome' style="display:none">
				<input type="text" name ="nome" id='nome' maxlength='50' value="<?=$nome ?>" placeholder="<?=ttext($a_rec_senha,"nome")?>">
			</div>

			<div id='fabrica_p'>
				<select name="fabrica" id='fabrica'>
					<option value="" selected><?=ttext($a_rec_senha,"selecionar_fabrica");?></option>selecionar_fabrica
						<?
							$sql = "SELECT fabrica, UPPER(nome) AS nome
								      FROM tbl_fabrica
									 WHERE ativo_fabrica IS TRUE
									   AND fabrica NOT IN(10,46,63,92,93,109,130,133)
									   AND nome !~* 'pedidoweb'
									 ORDER BY nome";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res)>0){
								for($x = 0; $x < pg_num_rows($res);$x++) {
									$aux_fabrica = pg_fetch_result($res,$x,fabrica);
									$aux_nome    = (mb_detect_encoding(pg_fetch_result($res,$x,nome), "UTF-8")) ? mb_convert_case(utf8_encode(pg_fetch_result($res,$x,nome)), MB_CASE_UPPER, 'UTF-8') : pg_fetch_result($res,$x,nome);
									echo "<option value='$aux_fabrica' ";
									if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
								}
							}
						?>
				</select>
			</div>
			<div id="reCaptcha" style="margin-top: 15px;">
				Carrengado reCaptcha
			</div>
			<div id="posto_multiplo_cnpj">
			</div>	
			<div class="checkbox">
				<?php
					$checked = ($_POST['login_unico'] and empty($sucesso)) ? "CHECKED" : "" ;
				?>
				<span><?=ttext($a_rec_senha,"login_unico") ?>?</span>
				<input type="checkbox" name="login_unico" id="login_unico" value="t" <?=$checked?>>
			</div>


			<button type="button" name="btn_acao" value="Enviar" class='input_gravar' onclick="verifica_esqueceu_senha('');"><i class="fa fa-lock"></i>Enviar</button>
			<input type="hidden" id="sucesso_email" value="<?= $sucesso ?>" />
			<br><br>
			<h4><?=ttext($a_rec_senha,"apos_enviar")?>:</h4>
			<div class="mail-preview">

				<p>
				<strong>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****</strong>
				<br><br>
				Caro Usuário,
				<br>
				Foi solicitado a recuperação de senha para acessar o sistema na fábrica NOME DA FÁBRICA:
				<br>
				<br>
				Para redefinir a senha, clique no link abaixo:
				<br>
				<a>Clique Aqui</a>
				<br><br>
				Se o link não funcionar, copie e cole o link abaixo no seu navegador:
				<br>
				https://posvenda.telecontrol.com.br/assist/externos/alterar_senha.php?token=9596sadfdas37a52f445fsdafad547db7d7799bb89ef60029a2a06e9c
				<br>
				<br>
				<b>Atenção:</b> Prazo máximo para troca de senha através deste token: 1h.
				<br>
				Após este período, o link ficará inativo por questões de segurança.
				<br>
				Em caso de link expirado, é necessário fazer novamente o processo de recuperação de senha.
				<br>
				<br>
				Se não tiver solicitado a redefinição de senha, desconsidere este e-mail.
				<br>
				<br>
				<br>
				Atenciosamente.,
				<br>
				<br>
				Suporte Telecontrol Networking.
				<br>
				suporte@telecontrol.com.br
				</p>
			</div>
		</form>
	</div>
</section>
<script>
<?php
    if ($msg != '') {
?>
       $(function(){
               setTimeout(function(){
                       $("#mensagem_envio_success").hide();
               }, 5000);
       });

<?php
    }
?>

	$(document).ready(function() {
		let email = $("#email").val();

		if (email != "" && email != undefined) {
			carrega_fabricas_email(email, $("#fabrica option:selected").val());
		} else {
			$("#fabrica").html("");
			$("#fabrica").append("<option value=''>Informar o Email para selecionar a fábrica</option>");
		}
	});

</script>	

<?php include('site_estatico/footer.php') ?>

