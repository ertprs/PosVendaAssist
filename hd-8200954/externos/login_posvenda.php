<?php

// Não deixa ter acesso direto ao arquivo somente include
//if (preg_match("/login_posvenda.php/", $_SERVER['SCRIPT_NAME'])){
//	$params = "";

	if(!empty($_GET['errLogin']))
		$params = '?errLogin='.$_GET['errLogin'];

//	header("Location: ../index.php{$params}");
//}

// if (!isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_HOST']=='posvenda.telecontrol.com.br') {
// 	header("Location: http://www.telecontrol.com.br/");
// }
header("X-Frame-Options: SAMEORIGIN");
header("Content-Type: text/html;charset=UTF-8");
$msg = null;
if (!empty($_GET['errLogin'])) {
    $msg = $_GET['errLogin'];
}
$html_titulo = 'Acesso';
include('topo_wordpress.php'); ?>
		<div style='width: 300px; margin: 30px auto;'><br />
			<div  id="entrando" align='right' class='msg Carregando'>&nbsp;</div>
	  		<div  id='errologin' class='erro'>&nbsp;</div>
		     <form name='acessar' id='acessar' action="javascript: login();" method="post">
		            <input type="hidden" name="btnAcao" value='enviar' />
		            <div id="loginBox">
	                    <br>
	                    <div class="login_telecontrol">
	                      <label for='login'>Login:</label>
	                      <input type="text" name='login' id='login' value="" tabindex='10'   placeholder='Login ou e-mail' />
	                    </div>
	                    <div class="senha_telecontrol">
	                      <label for='senha' >Senha:</label>
	                      <input type="password" name='senha' id='senha' value="" tabindex='11'   placeholder='Digite sua senha' />
	                    </div>
	                    <div class="btn_logar_telecontrol">
	                            <input type="submit" class="input_gravar" name="acessar"   id="btnAcao" value="Acessar" style="width: 70px;">
	                    </div>
	                    <div class='acessar_telecontrol' id='popover'>
                            <a href="login_unico.php" target="_parent">Login &Uacute;nico</a>&nbsp;|&nbsp;<a href="primeiro_acesso.php" target="_parent">Primeiro Acesso</a>
                            <br><a href="esqueci_senha.php" target="_parent">Esqueceu sua senha?</a>
                            <br><a href="limpeza_cache.php" target="_blank" class="comunicado">Problemas com login?</a>
	                    </div>
	                </div>
		      </form>
		      <div style='clear: both;'>&nbsp;</div>
		 </div><br /><br /><br /><br /><br /><br />

		<?php if ($msg != '') { ?>
			<script type="text/javascript">
				jQuery('#errologin').html("<?php echo $msg?>");
				jQuery('#errologin').fadeIn('fast')
					.delay(5000).fadeOut('fast');
			</script>
		<?php }?>
	<div class="blank_footer">&nbsp;</div>
	<?php //include("rodape_wordpress.php"); ?>
  </body>
</html>
