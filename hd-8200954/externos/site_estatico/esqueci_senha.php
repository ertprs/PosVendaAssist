<?php $pagetitle = "Esqueci a senha" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Esqueceu sua senha?</h2></div>
		<h3>Faça uma nova agora mesmo</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
	<div class="desc">
		<h3>
		Preencha o email que está cadastrado no sistema e selecione a fábrica que você esqueceu login e senha, para que seja enviado para seu email.
		</h3>
	</div>
	<div class="sep"></div>
	<form action="#">
		<input type="text" placeholder="Email cadastrado">
		<input type="password" placeholder="Senha deste fabricante">
		<select name="fabrica">
		<option value="" selected>Selecionar Fábrica</option>
		<option value="">Fábrica 1</option>
		<option value="">Fábrica 2</option>
		<option value="">Fábrica 3</option>
		</select>
		<div class="checkbox">
			<span>Login Unico?</span>
			<input type="checkbox" name="login_unico">
		</div>
		<button type="submit"><i class="fa fa-lock"></i>Enviar</button>
		<br><br>
		<h4>Após clicar em Enviar, você receberá um e-mail como o abaixo:</h4>
		<div class="mail-preview">
			<p>
			<strong>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****</strong>
			<br>
			Caro Usuário,
			<br>
			Foi solicitado o login e a senha para acessar o sistema na fábrica NOME DA FÁBRICA: 
			<br>
			Login: <strong>1234</strong>
			<br>
			Senha: <strong>xx123xx</strong>
			<br>
			<br>
			Para acessar o sistema use o link abaixo:
			<br>
			http://www.telecontrol.com.br 
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

<?php include('footer.php') ?>
