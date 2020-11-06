<?php
/*  Tradução do cabeçalho:    */
include_once "./trad_site/fn_ttext.php";
include "./trad_site/trad_inc_header.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="pt-br">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="generator" content="PSPad editor, www.pspad.com">
    <title>Login Telecontrol</title>
	<link rel='stylesheet' type='text/css' href="http://www.telecontrol.com.br/login/posvenda.css" />
 	<link rel="stylesheet"		type="text/css"		href="http://ww2.telecontrol.com.br/css/tc09_layout.css"	charset="utf-8" />
	

  <link rel="stylesheet" href="/css/tc09_estilos.css" type="text/css">
    
	<style type="text/css">
	/*
		Fonte:Arial
		Tamanho: 12
		Cor: #153B63
		Trocar a Fonte do "Esqueceu sua senha?"
		Dar 02 espaços depois do texto
		Alinhar "Esqueceu sua senha?" no final do Box da senha
	*/

		body, html{
			background: #FFF;
			text-align: left;
			margin: 0 !important;
			padding: 0 !important;
		}

		fieldset{
			border: none;
			margin: 0 !important;
			padding: 5px !important;
		}

		fieldset label {
			text-align: left;
		}

		#conteiner{
			margin: 0 !important;
			padding: 0 !important;
			width: 100% !important;
		}

		#entrando, #errologin {
			left: none !important;
			position: relative  !important;
			padding: 3px;
			margin: 5px !important;
			width: 80% !important;
			left: 0;
			top: 0;
			color: #000;
		}

		.erro, .msg{
			border-radius: 4px 4px 4px 4px;
			margin: 0 0 0 auto;
			color: #000 !important;
			left: 0;
			padding: 2;
		}
	</style>
    
  </head>
	<body>

		<div id='conteiner'>
			<div id='conteudo'>
				<div id='box_login'>
								
					<form name='acessar' id='acessar' action="javascript:login();" method='post'>
						<fieldset style='width:260px; padding: 20px;'>
							<div id="entrando"  class='msg Carregando'>&nbsp;</div>
							<div id='errologin' class='erro'>&nbsp;</div>

							<p>
								<label for='login'><?=ttext($a_trad_header, "Login") ?>:</label>
								<input type="text" name='login' id='login' autofocus value="" tabindex='10' />
							</p>
							<p>
								<label for='senha'><?=ttext($a_trad_header, "Senha") ?>:</label>
								<input type="password" name='senha' id='senha' value="" tabindex='11' />
							</p>
							<p style='text-align:left; margin: 10px 23px;' id='popover'>
								<a href="esqueci_senha.php" class="esqueceuSenha" tabindex='15'>
									<?=ttext($a_trad_header, "esqueceu_sua_senha") ?>
								</a>
								<button type="submit" name='btnAcao' value='Enviar' tabindex='12'>
									<?=ttext($a_trad_header, "Entrar")?>
								</button>
							</p>
						</fieldset>
					</form>
				</div>
			</div>
		</div>

	    <script type='text/javascript' src='http://code.jquery.com/jquery-latest.min.js'></script>
	    <script type='text/javascript' src='http://www.telecontrol.com.br/login/bootstrap.js'></script>
	    <script type='text/javascript' src='http://www.telecontrol.com.br/login/login.js'></script>
	</body>
</html>
