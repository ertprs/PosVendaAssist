<?php
// Não deixa ter acesso direto ao arquivo somente include
//if (preg_match("/login_posvenda.php/", $_SERVER['SCRIPT_NAME'])){
//	$params = "";

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
}

$sess = $_COOKIE['sess'];
if(!empty($sess)) {
	include_once "../dbconfig.php";
	include_once '../includes/dbconnect-inc.php';
	$sql = "SELECT admin,fabrica from tbl_login_cookie where token = '$sess' and admin notnull ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0) {
		$admin   = pg_fetch_result($res,0,'admin');
		$fabrica = pg_fetch_result($res,0,'fabrica');
		if(!empty($admin) and !empty($fabrica)) {
			$sql = "SELECT tbl_admin.admin,
				tbl_admin.fabrica,
				tbl_admin.login,
				tbl_admin.senha,
				tbl_admin.privilegios,
				tbl_admin.cliente_admin,
				tbl_admin.grupo_admin,
				tbl_admin.cliente_admin_master,
				tbl_admin.responsavel_postos,
				tbl_admin.help_desk_supervisor,
				tbl_admin.atendente_callcenter,
				(select tdocs_id from tbl_tdocs where tbl_tdocs.referencia = 'adminfoto' AND tbl_tdocs.referencia_id = tbl_admin.admin and tbl_tdocs.fabrica = tbl_admin.fabrica order by tdocs desc limit 1 ) as avatar,
				tbl_admin.pais
				FROM tbl_admin
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica AND tbl_fabrica.ativo_fabrica IS TRUE
				WHERE tbl_admin.admin = $admin 
				AND tbl_fabrica.fabrica = $fabrica
				AND ativo IS TRUE";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 1) {
				$pais  = pg_fetch_result($res,0,pais) ;
				$admin = pg_fetch_result($res,0,admin);
				$responsavel_postos   = pg_fetch_result($res, 0, 'responsavel_postos'); #HD 233213
				$help_desk_supervisor = pg_fetch_result($res, 0, 'help_desk_supervisor');
				$atendente_callcenter = pg_fetch_result($res, 0, 'atendente_callcenter');
				$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
					$_SERVER['HTTP_X_FORWARDED_FOR'] :
					$_SERVER['REMOTE_ADDR'];

				$sql2 = "UPDATE tbl_admin
					SET ultimo_ip = '$ip' ,
					ultimo_acesso = CURRENT_TIMESTAMP
					WHERE admin = $admin";

				$res2 = pg_query($con,$sql2);

				$login_admin = pg_fetch_result($res, 0, 'admin');
				$privilegios = pg_fetch_result($res,0,privilegios);
				$acesso = explode(",",$privilegios);

				if($pais<>'BR'){
					$pagina = "admin_es/menu_gerencia.php";
				}

				if($fabrica == 86){
					if (in_array($admin,array(6306,6017,2339,5415))){
						$responsabilidade = "alerta_pedido";
					}
				}
				for($i = 0; $i < count($acesso); $i++) {

					if(strlen($acesso[$i]) > 0) {

						if($fabrica == 86 AND $responsabilidade == "alerta_pedido"){
							$pagina="admin/pedidos_abertos.php";
						} else if ($responsavel_postos == 't') {
							$pagina="admin/em_descredenciamento.php";
						} else if ( $help_desk_supervisor=='t'){
							$pagina="admin/hd_aguarda_aprovacao.php";
						} else {
							if ($acesso[$i] == "gerencia") {
								$pagina = "admin/menu_gerencia.php";
							} elseif ($acesso[$i] == "call_center") {
								if($atendente_callcenter == 't' && in_array($fabrica,array(122,125))){
									$pagina = "admin_callcenter/menu_callcenter.php";
								} else {
									$pagina = "admin/menu_callcenter.php";
								}
							} elseif ($acesso[$i] == "cadastros") {
								$pagina = "admin/menu_cadastro.php";
							} elseif ($acesso[$i] == "info_tecnica") {
								$pagina = "admin/menu_tecnica.php";
							} elseif ($acesso[$i] == "financeiro") {
								$pagina = "admin/menu_financeiro.php";
							} elseif ($acesso[$i] == "auditoria") {
								$pagina  = "admin/menu_auditoria.php";
							} elseif ($acesso[$i] == "*") {
								$pagina="admin/menu_cadastro.php";
							}

						}
					}
					$cliente_admin        = pg_fetch_result($res,0,cliente_admin);
					$cliente_admin_master = pg_fetch_result($res,0,cliente_admin_master);

					if (strlen($cliente_admin)>0) {
						$pagina = "admin_cliente/menu_callcenter.php";
					}

					if ($admin == 1152) {
						$pagina = 'admin/relatorio_peca_sem_preco.php?tabela=215';
					}

					if (strlen($pagina) == 0) { /*HD - 4417123*/
						$pagina = "admin/menu_callcenter.php";
					}

					if(!empty($pagina)){
						header ("Location: ../$pagina");
						exit;
					}
				}
			}
		}
	}

}

if (!$iframe) {
	header("X-Frame-Options: SAMEORIGIN");
}

if(!empty($_GET['errLogin']))
$params = '?errLogin='.$_GET['errLogin'];
//	header("Location: ../index.php{$params}");
//}
// if (!isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_HOST']=='posvenda.telecontrol.com.br') {
// 	header("Location: http://www.telecontrol.com.br/");
// }
header("Content-Type: text/html;charset=iso-8859-1");

$msg = null;
if (!empty($_GET['errLogin'])) {
    $msg = $_GET['errLogin'];
}

if($_GET['acaciaeletro']){
	$loginAcacia = 1;
}

if($_GET['loja_black']){
    $loginAcacia = 1;
}

$html_titulo = 'Acesso';
include('site_estatico/header.php');
?>
	<script>
	$('body').addClass('pg log-page');
	$(function() {
		$('#login').focus();
	})
	</script>
	<section class="table h-img">
		<?php include('site_estatico/menu-pgi.php'); ?>
		<div class="cell">
			<div class="title"><h2>Sistema Telecontrol</h2></div>
			<h3>Acesso restrito ao Cliente.</h3>
		</div>
	</section>
	<section class="pad-1 login">
		<div class="main">


		<div class="alerts">
			<div id='errologin' class="alert error"><i class="fa fa-exclamation-circle"></i><span id="msg"></span></div>
			<!--<div id='errologin2' class="alert error"><i class="fa fa-exclamation-circle"></i><span id="msg"></span></div> -->
		</div>

		<form name='acessar' id='acessar' action="javascript: login();" method="post" >
			<input type="hidden" name="cliente_admin" value="" id="cliente_admin" />
			<input type="hidden" name="btnAcao" value='enviar' />
			<input type="hidden" name="loginAcacia" id="loginAcacia" value='<?=$loginAcacia?>' />
			<input type="text" name='login' id='login' value="" placeholder="Login">
			<input type="password" name='senha' id='senha' value="" placeholder="Senha">
			<div id="mult_cliente_admin" hidden>
				
			</div>
			<button type="submit" id="btnAcao" name='acessar' value='Acessar' ><i class="fa fa-lock"></i>Acessar</button>
			<ul class="links">
				<li><a href="./login_unico_new.php">Login único</a></li>
				<li><a href="./primeiro_acesso_new.php">Primeiro acesso</a></li>
				<li><a href="./esqueci_senha_new.php">Esqueceu sua senha?</a></li>
				<li class="prob"><a href="./limpeza_cache_new.php" target="_blank">Problemas com login?</a></li>
			</ul>
		</form>
		</div>
	</section>
		<?php if ($msg != '') { ?>
			<script type="text/javascript">
				jQuery('#msg').html("<?php echo $msg?>");
				jQuery('#errologin').fadeIn('fast')
					.delay(5000).fadeOut('fast');
			</script>
		<?php }?>
		<script>

		</script>
	<?php include("site_estatico/footer.php"); ?>
  </body>
</html>
