<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
}

session_start();

header("Content-Type: text/html;charset=iso-8859-1");

$html_titulo = 'Primeiro Acesso';

if (empty($_POST)) {
    $token = md5(uniqid(rand(), true));
    $_SESSION['csrfToken'] = $token;
}

if (isset($_POST["valida_cnpj"]) && !empty($_POST["cnpj"])) {
	if ($_SESSION["csrfToken"] == $_POST["csrf_token"]) {
        include '../dbconfig.php';
        include '../includes/dbconnect-inc.php';

        $cnpj = $_POST['cnpj'];

        $cnpj = preg_replace('/[-.,+|\/()*_]|\s/', '', $cnpj); //2011-08-09 Postos de fora do Brasil não conseguem

        $sql  = "SELECT * FROM tbl_posto WHERE cnpj = '$cnpj'";
        $res  = pg_query($con,$sql);

        if(pg_num_rows($res) == ''){
            $msg_erro = 'erro';
        }
        echo $msg_erro;
        exit;
    } else {
        die('errox');
    }
}

include('site_estatico/header.php');

if ($_GET['mensagem'] == 'sucesso') {
	$class_mensagem = 'email_sucesso';

	$msg ="<div class='alerts'>
			<div class='alert success' style='display:block'><i class='fa fa-check-circle'></i>
				Prezado Posto Autorizado, seja bem-vindo!</p>
				<p>Seu acesso foi liberado com sucesso.</p>
				<p><a style='color:#ffffff' href='http://www.telecontrol.com.br/'>Clique aqui para acessar nosso <i>site</i>.</a></p>
			</div>
		</div>";

	// $msg = "<p>Prezado Posto Autorizado, seja bem-vindo!</p>
	// 		<p>Seu acesso foi liberado com sucesso.</p>
	// 		<p><a href='http://www.telecontrol.com.br/'>Clique aqui para acessar nosso <i>site</i>.</a></p>";
}

?>
<script>
	$('body').addClass('pg log-page');

</script>
<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Primeiro Acesso</h2></div>
		<h3>Obtenha já seu Login e Senha</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
		<div class="alerts">
			<div class="alert error" id="mensagem_envio"><i class="fa fa-exclamation-circle"></i></div>
		</div>

		<?php
			echo $msg;
		?>

		<div class="desc">
			<h3>
			Para obter seu login e criar uma senha de acesso, digite seu CNPJ.
			<br>
			Não se esqueça de desabilitar qualquer tipo de ANTI-POP-UP que você tiver.
			</h3>
		</div>
		<div class="sep"></div>
		<form name="frm" id="frm" method="POST" action="primeiro_acesso_valida_new.php">
			<input name="cnpj" id="cnpj" maxlength="19" value="" placeholder="CNPJ" type="text" onkeyup="this.value=this.value.replace(/[^\d]/,'')" />
			<input name="csrf_token" id="csrf_token" type="hidden" value="<?= $token ?>" />
			<button value="Gravar" type="submit" name="btnG" class="input_gravar"><i class="fa fa-lock"></i> Acessar</button>
		</form>
	</div>
</section>

<script type="text/javascript">
	$("#frm").submit(function() {
		if(verifica_primeiro_acesso() == false){
			return false;
		}
	});
</script>
<?php include('site_estatico/footer.php') ?>
