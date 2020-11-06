<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	//Inicializa a session
	session_start();

	//Destroy a session
	session_destroy();

?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
		<title>Telecontrol - Gerência de Assistência Técnica</title>
		<link rel="stylesheet"		type="text/css"		href="http://ww2.telecontrol.com.br/css/tc09_layout.css" />
	</head>

	<body>
		<div id="geral" style='margin: 20px auto;'>
			<center>
				<h2>
					<strong>
						<img src='http://posvenda.telecontrol.com.br/img/logo_tc_2009_md.gif'  style='display: block' /><br />
						A sessão remota foi desconectada porque outro usuário se conectou usando o mesmo login. <br /><br />
						
						Atenciosamente<br>Equipe Telecontrol.
					</strong>
				</h2>
				<h5>
				<a href='https://posvenda.telecontrol.com.br/assist/externos/login_posvenda.php' class="btn btn-primary" type="button" >Acessar novamente</a>
				</h5>

			</center>
<!-- 
				<div id="header">
					<a href="../index.php"><h1 id="logo2">Telecontrol</h1></a>
					<div id="LoginTipos">
						<p class="loginAcesso">
							<a class="loginUnico"	  href="http://www.telecontrol.com.br/login_unico.php">Login Único</a>
							<a class="primeiroAcesso" href="http://www.telecontrol.com.br/primeiro_acesso.php">Primeiro Acesso</a>
						</p>
					</div>
				</div>

				<div id='conteiner'>
					<div id='conteudo'>
						<div class="alert" style='margin: 20px;'>
							<strong>Acesso inválido, possiveis motivos:</strong> <br />
							- O tempo limite da conexão remota se esgotou. Tente conectar-se ao computador remoto novamente.<br />
							- A sessão remota foi desconectada porque outro usuário se conectou usando o mesmo login. <br />
							- A sessão remota foi desconectada devido a um logoff no computador remoto. Seu administrador ou outro usuário pode ter encerrado a sessão. <br />

						</div>
						<p style='margin: 20px; text-align: right;'>
							<a href='http://www.telecontrol.com.br' class="btn btn-primary" type="button" style='color: #FFF;'>Acessar novamente</a>
						</p>
					</div>
				</div>
			</div>
 -->

	</body>
</html>
