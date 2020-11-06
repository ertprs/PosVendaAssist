<?php $pagetitle = "Primeiro Acesso" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Primeiro Acesso</h2></div>
		<h3>Obtenha já seu Login e Senha</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
	<div class="desc">
		<h3>
		Para obter seu login e criar uma senha de acesso, digite seu CNPJ.
		<br>
		Não se esqueça de desabilitar qualquer tipo de ANTI-POP-UP que você tiver.
		</h3>
	</div>
	<div class="sep"></div>
	<form action="#">
		<input type="text" placeholder="CNPJ">
		<button type="submit"><i class="fa fa-lock"></i>Acessar</button>
	</form>
	</div>
</section>

<?php include('footer.php') ?>
