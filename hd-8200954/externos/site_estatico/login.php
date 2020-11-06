<?php $pagetitle = "Login" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Sistema Telecontrol</h2></div>
		<h3>Acesso restrito ao Cliente.</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
	<form action="#">
		<input type="text" placeholder="Login ou Email">
		<input type="password" placeholder="Senha">
		<button type="submit"><i class="fa fa-lock"></i>Acessar</button>
		<ul class="links">
			<li><a href="<?php echo $url ?>/login_unico.php">Login único</a></li>
			<li><a href="<?php echo $url ?>/primeiro_acesso.php">Primeiro acesso</a></li>
			<li><a href="<?php echo $url ?>/esqueci_senha.php">Esqueceu sua senha?</a></li>
			<li class="prob"><a href="https://posvenda.telecontrol.com.br/assist/externos/limpeza_cache.php" target="_blank">Problemas com login?</a></li>
		</ul>
	</form>
	</div>
</section>

<?php include('footer.php') ?>
