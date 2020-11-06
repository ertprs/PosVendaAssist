<?php $pagetitle = "Reenviar Email de Validação" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Reenviar E-mail de Validação</h2></div>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
	<div class="desc">
		<h3>
		Caso você não tenha recebido o e-mail de confirmação do login único, digite seu email abaixo e clique em enviar.
		</h3>
	</div>
	<div class="sep"></div>
	<form action="#">
		<input type="text" placeholder="Email">
		<button type="submit"><i class="fa fa-lock"></i>Enviar</button>
		<br><br>
		<h4>Após clicar em enviar, você receberá um email como o abaixo, e para liberar o acesso você deverá clicar no link, ou se tiver problemas copiar (CRTL+C) o endereço e colar (CRTL+V) no seu navegador:</h4>
		<div class="mail-preview">
			<p>
			<strong>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</strong>
			<br><br>
			Parabéns pela sua nova conta de login único no Assist:suporte@telecontrol.com.br, para <strong>validar</strong> seu email, utilize o link abaixo:
			<br>
			<strong>Clique aqui para validar seu email.</strong>
			<br><br>
			Suporte Telecontrol Networking.
			<br>
			suporte@telecontrol.com.br
			</p>
		</div>
	</form>
	</div>
</section>

<?php include('footer.php') ?>
