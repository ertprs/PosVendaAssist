<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
}
header("Content-Type:text/html; charset=iso-8859-1");

$arr_host = explode('.', $_SERVER['HTTP_HOST']);

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../helpdesk/mlg_funciones.php';
if (!function_exists('ttext')) {
	include 'trad_site/fn_ttext.php';
}

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$login       = trim($_POST["login"]);
$senha       = trim($_POST["senha"]);
$acao_unico  = trim($_POST['acao_unico']);
$cook_idioma = (isset($_COOKIE['idioma']))?$_COOKIE['idioma']:"pt-br";

if(strlen($acao_unico)>0){

	if (strlen($msg) == 0) {
		$login = preg_replace('/(\.|\/|-)/', '', strtolower($login));

		#------------- Pesquisa posto pelo Login ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica ,
			tbl_posto_fabrica.posto,
			tbl_posto_fabrica.fabrica,
			tbl_posto_fabrica.credenciamento,
			tbl_posto_fabrica.login_provisorio
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE  lower (tbl_posto_fabrica.codigo_posto) = '$login'
			AND    tbl_posto_fabrica.senha		  = '$senha'
			AND    tbl_posto_fabrica.primeiro_acesso IS NOT NULL";
		// exit(nl2br($sql));
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) == 1) {
			extract(pg_fetch_assoc($res, 0));

			$sql = "SELECT login_unico FROM tbl_login_unico WHERE posto = {$posto} AND master IS TRUE AND ativo IS TRUE";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$msg = "J� existe um usu�rio master cadastrado para o Posto Autorizado, solicite para que seu login seja regularizado.";
			}

			if (strlen($msg) == 0) {
				if($fabrica == 1){
					$arr_status_negativo = array('DESCREDENCIADO', 'Pr&eacute; Cadastro', 'Descred apr', 'Pr&eacute; Cad rpr');
				}else{
					$arr_status_negativo = array('DESCREDENCIADO');
				}

				if (in_array($credenciamento, $arr_status_negativo)) {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				} elseif ($login_provisorio == 't' AND 1==2 ) {
					$msg = '<!--OFFLINE-I-->Para acessar � necess�rio realizar a confirma��o no email.<!--OFFLINE-F-->';
				} else {
					unset($_COOKIE);
									if (empty($token_cookie)) {
						$caminho = dirname(dirname($PHP_SELF));
						$token_cookie = gera_token($fabrica,null, $posto);
						setcookie("sess",$token_cookie,null,$caminho);

						$cookie_login = get_cookie_login($token_cookie);
					}

					add_cookie($cookie_login,'cook_posto_fabrica', $posto_fabrica);
					add_cookie($cookie_login,'cook_posto'        , $posto);
					add_cookie($cookie_login,'cook_fabrica'      , $fabrica);
					add_cookie($cookie_login,'cook_login_unico'  , 'temporario');
					set_cookie_login($token_cookie,$cookie_login);

					$posto = md5($posto);
					$fabrica = md5($fabrica);
					header ("Location: ../login_unico_cadastro.php?lu=temp&fabrica=$fabrica&posto=$posto");
					exit;
				}
			}
		}

			#------------- Pesquisa posto pelo CNPJ ---------------#
			$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
				tbl_posto_fabrica.posto,
				tbl_posto_fabrica.fabrica ,
				tbl_posto_fabrica.credenciamento
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_posto.posto
				WHERE tbl_posto.cnpj                 = '$login'
				AND   tbl_posto_fabrica.senha = '$senha'
				AND    tbl_posto_fabrica.primeiro_acesso IS NOT NULL";
			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) == 1) {
				extract(pg_fetch_assoc($res, 0));
				
				$sql = "SELECT login_unico FROM tbl_login_unico WHERE posto = {$posto} AND master IS TRUE AND ativo IS TRUE";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$msg = "J� existe um usu�rio master cadastrado para o Posto Autorizado, solicite para que seu login seja regularizado.";
				}

				if(strlen($msg) == 0) {
				if ($credenciamento == 'DESCREDENCIADO') {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				}else{
					
					if (empty($token_cookie)) {
						$caminho = dirname(dirname($PHP_SELF));
						$token_cookie = gera_token($fabrica,null, $posto);
						setcookie("sess",$token_cookie,null,$caminho);

						$cookie_login = get_cookie_login($token_cookie);
					}

					add_cookie($cookie_login,'cook_posto_fabrica', $posto_fabrica, null, '/');
					add_cookie($cookie_login,'cook_posto' ,		 $posto,		 null, '/');
					add_cookie($cookie_login,'cook_fabrica' ,		 $fabrica,		 null, '/');
					add_cookie($cookie_login,'cook_login_unico' ,	 'temporario',	 null, '/');


					set_cookie_login($token_cookie,$cookie_login);


					//header ("Location: ../login_unico_cadastro.php");
					header ("Location: ../login_unico_cadastro.php");
					exit;
				}
			}
		}

		if(strlen($msg) > 0){
			$msg= ttext($a_trad_LU,$msg);
		}else{
			$msg= ttext($a_trad_LU,'login_invalido');
		}

		$style = "style='display:block'";
	}
}

if(md5($_GET["id"])==$_GET["key1"]){
	$lu_id = $_GET["id"];
	$sql = "UPDATE tbl_login_unico SET email_autenticado = CURRENT_TIMESTAMP WHERE login_unico = $lu_id";

	$res = pg_query($con, $sql);
	if(pg_affected_rows($res) == 1) {
		$msg      = "<label class='email_sucesso'>".ttext($a_trad_LU, "parabens")."OK</label>\n";
		$validaOK = true;
	} else {
	    echo "<div class='erro' id='mensagem'>".ttext($a_trad_LU, "erro_gravar_auth").
			 "<a href='mailto:suporte@telecontrol.com.br'>".ttext($a_trad_header, "Suporte")."</a>.</div>\n";
   }
}

$html_titulo = ttext($a_trad_LU, 'login_unico');
include('site_estatico/header.php');

?>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Login �nico</h2></div>
		<h3>Voc� tem v�rias vantagens</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
		<?php	if ($validaOK) {	// Mostra apenas a mensagem de valida��o OK
		?>
			<script type="text/javascript">
				$(function(){
					$(".alert.success").show();
				});
			</script>
			<div class="alerts">
				<div class="alert success"><i class="fa fa-check-circle"></i><?php echo "Parab�ns! seu email foi autenticado com sucesso."; ?></div>
			</div>
		<?php exit;  } ?>

		<div class="desc">
			<ul>
				<li>Usando seu pr�prio e-mail e uma �nica senha, ter� acesso a todas as f�bricas em que trabalha;</li>
				<li>Poder� restringir o acesso de seus funcion�rios a �reas espec�ficas do site;</li>
				<li>Poder� consultar o andamento de seus pedidos de pe�as (compra ou garantia), independente do fabricante;</li>
				<li>Poder� consultar suas OS em aberto, filtrando por status ou fabricante.</li>
			</ul>
			<div class="sep"></div>
			<h3>
			Comece agora: Use o usu�rio e senha de algum de seus fabricantes, e crie seu <strong>Login Principal</strong>.
			<br>
			Depois, crie os logins de seus funcion�rios, e determine suas �reas de acesso.
			</h3>
		</div>
		<div class="sep"></div><br/><br/>
		<div class="alerts">
			<div class="alert error" <?=$style?> id="mensagem_envio"><i class="fa fa-exclamation-circle"></i><?php echo $msg;?></div>
		</div>

		<form name="login_unico" id="lu" method="POST" action="login_unico_new.php">
			<h2>Cadastre-se</h2>
			<input type="hidden" name="acao_unico" value="ok">
			<input type="text" name="login" id="campo_login" maxlength="50" value="" placeholder="Login de um dos Fabricantes">
			<input type="password" name="senha" id="campo_senha" placeholder="Senha deste Fabricante">
			<button type="button" name="acao" value="Acessar" onclick="verifica_login_unico('');" ><i class="fa fa-lock"></i>Acessar</button>
			<ul class="links">
				<li><a href="login_unico_envio_email_new.php">N�o recebeu o email de confirma��o?</a></li>
				<li><a href="login_unico_passos.php" target="_blank">Visualizar passo a passo</a></li>
			</ul>
		</form>
	</div>
</section>

<?php include('site_estatico/footer.php') ?>

