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
include_once '../classes/Posvenda/Seguranca.php';

$objSeguranca = new \Posvenda\Seguranca(null,$con);

header("Content-Type: text/html;charset=utf-8");

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

include_once '../class/communicator.class.php';
$mailer = new TcComm("noreply@tc");

$html_titulo = ttext($a_rec_senha, "titulo");
$body_options = "onload='document.frm_es.token.focus() ;' ";
include "site_estatico/header.php";

function countDigits( $str )
{
    return preg_match_all( "/[0-9]/", $str );
}

function countLetters( $str )
{
    return preg_match_all( "/[a-zA-ZÀ-ú]/", $str );
}

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
	"titulo" => array (
		"pt-br" => "Recuperar senha de acesso",
		"es"    => "Recuperar datos de acceso",
		"en"    => "Retrieve access data",
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
	"faca_uma_nova" => array (
		"pt-br" => "Faça uma nova agora mesmo",
		"es"    => "Hacer una nueva en este momento",
		"en"    => "Make a new right now",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"preencha_token" => array (
		"pt-br" => "Preencha o token e senha enviando no email para realizar a recuperação de senha .",
		"es"    => "Escriba su dirección de correo-e que consta en el registro y el fabricante cuyo usuario/clave ha perdido y le enviaremos un mensaje con los datos de acceso.",
		"en"    => "Fill out the e-maill that is registered in the system and select the factory you forgot the login and password and it will be sent to your e-mail.",
		"de"    => "Teilen Sie uns Ihr mail mit mit dem Sie bei uns angemeldet sind und die Firma für die Sie das den Usernamen und das Kennwort vergessen haben. Das Kennwort wird an Ihre Mailanschrift versandt.",
		"zh-cn" => "填写注册的电子信箱后选择所忘记的厂商用户名和密码，使系统可以发送E-MAIL到您的信箱。",
		"zh-tw" => "填寫註冊的電子信箱後選擇所忘記的廠商用戶名和密碼，使系統可以發送E-MAIL到您的信箱。"
	),
	"token" => array (
		"pt-br" => "Token",
		"es"    => "Token",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"senha" => array (
		"pt-br" => "Senha",
		"es"    => "Contraseña",
		"en"    => "Password",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
    ),
    "digite_o_token" => array(
		"pt-br" => "Digite o token enviado no email<br>",
		"es"    => "Cibercorreo",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
    ),
    "digite_o_senha" => array(
		"pt-br" => "Digite a senha<br>",
		"es"    => "Cibercorreo",
		"en"    => "",
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
    "primeiro_acesso" => array(
        "pt-br" => "Primeiro acesso",
		"es"    => "Primer acceso",
		"en"    => "First Access",
    ),
    "alterar_senha" => array(
        "pt-br" => "Preencha a senha para acesso ao sistema!",
    ),
    "senhas" => array(
        "pt-br" => "Senhas não Coincidem",
		"es"    => "Las contraseñas no coinciden",
		"en"    => "Passwords do not match",
    ),
    "token_invalido" => array(
        "pt-br" => "<a style='color: #fff;' href='esqueci_senha_new.php'>Token Expirado! Clique aqui para fazer o envio de um novo token. </a>",
		"es"    => "<a style='color: #fff;' href='esqueci_senha_new.php'>Token caducado! Haga clic aquí para enviar un nuevo token. </a>",
		"en"    => "<a style='color: #fff;' href='esqueci_senha_new.php'>Token Expired! Click here to submit a new token. </a>",
    ),
    "senha_invalida" => [
    	"pt-br" => "A senha deve conter no mínimo seis caracteres e no máximo dez, sendo no mínimo 2 letras (de A a Z) e 2 números (de 0 a 9).",
		"es"    => "La contraseña debe contener un mínimo de seis caracteres y un máximo de diez, con un mínimo de 2 letras (de la A a la Z) y 2 números (del 0 al 9)",
		"en"    => "The password must contain a minimum of six characters and a maximum of ten, with a minimum of 2 letters (from A to Z) and 2 numbers (from 0 to 9)"
    ]
);
?>

<?php 

    if(isset($_REQUEST['token'])) {

        $token       = trim($_REQUEST['token']);

        if(strlen($token)==0)	$msg_erro = ttext($a_rec_senha, "digite_o_token");

        if(strlen($msg_erro) ==0) {
            $sql = "SELECT alteracao_posto_senha, data_solicitacao, posto_fabrica, login_unico, data_alteracao, tipo_alteracao FROM tbl_alteracao_posto_senha WHERE token = '$token'";
            $res = pg_query($con, $sql);

            if (!is_resource($res)) $msg_erro = pg_last_error($con);

            if(pg_num_rows($res) > 0 and strlen($msg_erro) == 0) {
                $posto_fabrica = pg_fetch_result($res, 0, 'posto_fabrica');
                $login_unico = pg_fetch_result($res, 0, 'login_unico');
                $data_solicitacao  = pg_fetch_result($res, 0, 'data_solicitacao');
				$alteracao_posto_senha   = pg_fetch_result($res, 0, 'alteracao_posto_senha');
				$data_alteracao   = pg_fetch_result($res, 0, 'data_alteracao');
				$tipo_alteracao   = pg_fetch_result($res, 0, 'tipo_alteracao');

				$data = new DateTime();
                $data_atual = $data->format('Y-m-d H:i:s.u');

                if(date('Y-m-d H:i:s.u', strtotime('+1 hours', strtotime($data_solicitacao))) >= $data_atual and $data_alteracao == null){
                    $alterar_senha = true;
                }else{
                    $msg_erro.= ttext($a_rec_senha, "token_invalido");
                    $alterar_senha = false;
                }

            }else{
                $msg_erro.= ttext($a_rec_senha, "token_invalido");

            }
		}
		
		
	}
	if( $_POST['btn_acao']=='Enviar') {

		$token = pg_escape_string($_REQUEST['token']);

        $senha 			= pg_escape_string($_POST['senha_nova']);
        $senha_confirma = pg_escape_string($_POST['senha_nova_confirma']);

        $sql = "SELECT alteracao_posto_senha, data_solicitacao, posto_fabrica, login_unico, data_alteracao, tipo_alteracao FROM tbl_alteracao_posto_senha WHERE token = '$token'";
        $res = pg_query($con, $sql);

        $posto_fabrica  		= pg_fetch_result($res, 0, "posto_fabrica");
        $login_unico    		= pg_fetch_result($res, 0, "login_unico");
        $data_alteracao 		= pg_fetch_result($res, 0, "data_alteracao");
        $alteracao_posto_senha  = pg_fetch_result($res, 0, "alteracao_posto_senha");
        $tipo_alteracao         = pg_fetch_result($res, 0, "tipo_alteracao");

        if(strlen($senha) > 0){

        	if (strlen($senha) > 10 || strlen($senha) < 6 || countDigits($senha) < 2 || countLetters($senha) < 2) {
        		$msg_erro .= ttext($a_rec_senha, "senha_invalida");
        	} else {

	            if(strlen($posto_fabrica) > 0){
					$campo = "posto_fabrica";
            		$valor = "$posto_fabrica";

	                if($senha == $senha_confirma){

	                	if ($tipo_alteracao == "primeiro_acesso") {
	                		$campoPrimeiroAcesso = ", primeiro_acesso = current_timestamp, login_provisorio = false";
	                	}

	                    $sql = "UPDATE tbl_posto_fabrica SET senha = '{$senha}' {$campoPrimeiroAcesso} where posto_fabrica = {$posto_fabrica}";
	                    $res = pg_query($con, $sql);

						if (!is_resource($res)) {
							$msg_erro = pg_last_error($con);
						}
						else{
							$data = new DateTime();
							$data_alteracao = $data->format('Y-m-d H:i:s.u');
								
							$sql_alteracao_senha = "UPDATE tbl_alteracao_posto_senha SET data_alteracao = '$data_alteracao' where alteracao_posto_senha = $alteracao_posto_senha";
							$res_alteracao = pg_query($con, $sql_alteracao_senha);

							$txt_aprovada = ($tipo_alteracao == "primeiro_acesso") ? "cadastrada" : "alterada";
						}

						 
	                }else{
	                    $msg_erro.= ttext($a_rec_senha, "senhas");
	                }
	            }elseif(strlen($login_unico)){
					$campo = "login_unico";
					$valor = "$login_unico";
					
	                if($senha == $senha_confirma){
	                    $sql = "UPDATE tbl_login_unico SET senha = '$senha' where login_unico = $login_unico";
	                    $res = pg_query($con, $sql);
						if (!is_resource($res)) {
							$msg_erro = pg_last_error($con);
						}
						else{
							$data = new DateTime();
							$data_alteracao = $data->format('Y-m-d H:i:s.u');

							$sql_alteracao_senha = "UPDATE tbl_alteracao_posto_senha SET data_alteracao = '$data_alteracao' where alteracao_posto_senha = $alteracao_posto_senha";
							$res_alteracao = pg_query($con, $sql_alteracao_senha);
						}
	                }else{
	                    $msg_erro.= ttext($a_rec_senha, "senhas");
	                }
				}

				$dados_posto = $objSeguranca->getPostoFabrica($login_posto,$login_fabrica);
				$senha_old = trim($dados_posto["senha"]);
				$objSeguranca->gravaLogAlteracaoSenha($campo,$valor,$senha_old,$senha,$ip);

				echo "<script>alert('Senha {$txt_aprovada} com Sucesso!');</script>";
				echo "<script>"; 
					echo "window.location.href = 'login_posvenda_new.php'";
				echo "</script>";
				exit;
        	}
        }else{
            $msg_erro.= ttext($a_rec_senha, "digite_o_senha");
        }
    }
?>

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
<script src='../admin/plugins/jquery.maskedinput_new.js'></script>
<script type="text/javascript" src="../js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="../ajax.js"></script>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<?php

		$programa = ($tipo_alteracao == "primeiro_acesso") ? "primeiro_acesso" : "esqueceu_senha";

		?>
		<div class="title"><h2><?=ttext($a_rec_senha,$programa)?></h2></div>
		<h3><?=ttext($a_rec_senha,"faca_uma_nova")?></h3>
	</div>
</section>

    <section class="pad-1 login">
        <div class="main">
            <div class="alerts">
                <!--  ALGUMAS MSG DE ERRO VEM DO ARQUIVO esqueceu_senha.js -->
                <div class="alert success" <?=$display_success?> id="mensagem_envio_success"><i class="fa fa-check-circle"></i><?php echo $mensagem;?></div>
                
                <div class="alert error" <?=$display_error?> id="mensagem_envio"><i class="fa fa-exclamation-circle"></i><?php echo $mensagem;?></div>
            </div>

            <div class="desc">
                <h3>
                    <?=ttext($a_rec_senha,"alterar_senha");?>
                </h3>
            </div>
            <br>
            <div>
            	<span>
            		<p style="font-weight: 500;">
		            	Preencha a nova senha para acesso ao sistema atendendo os requisitos mínimos de segurança abaixo:
		            	<br />
						- Mínimo de 6 caracteres e máximo 10;
						<br />
						- Mínimo 3 letras (de A a Z) e uma maiúscula;
						<br />
						- Mínimo 3 números (de 0 a 9);
						<br />
						- Um caractere especial (#,@,$,&)
						<br /> <br />
						- Exemplos: Assist5089, Acesso@201, Tele&2020.
					</p>
				</span>
            </div>
            <div class="sep"></div>
            <form name='frm_es' id='frm_es' method='post' action='<?=$PHP_SELF?>'>
                <input type='hidden' name='btn_acao' value='Enviar' >
                <input type="hidden" name="token" value='<?= $token ?>' />

                <input name ="senha_nova" id='senha_nova' type="password" value="<?=$senha_nova ?>" placeholder="<?=ttext($a_rec_senha,"Senha")?>" <?php echo ($alterar_senha == false) ? 'disabled=true': '' ?> maxlength="10">

                <input name ="senha_nova_confirma" id='senha_nova_confirma' type="password" value="<?=$senha_nova_confirma ?>" placeholder="<?=ttext($a_rec_senha,"Confirma Senha")?>" <?php echo ($alterar_senha == false) ? 'disabled=true': '' ?> maxlength="10">
                
                <br>
                <button type="button" name="btn_acao" value="Enviar" class='input_gravar' onclick="verifica_nova_senha('');" <?php echo ($alterar_senha == false) ? 'disabled=true': '' ?>><i class="fa fa-lock"></i>Salvar</button>
                <input type="hidden" id="sucesso_token" value="<?= $sucesso ?>" />
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

</script>	



<?php include('site_estatico/footer.php') ?>