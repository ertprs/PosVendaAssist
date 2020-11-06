<?php $pagetitle = "Login Único" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Login Único</h2></div>
		<h3>Você tem várias vantagens</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
	<div class="desc">
		<ul>
			<li>Usando seu próprio e-mail e uma única senha, terá acesso a todas as fábricas em que trabalha;</li>
			<li>Poderá restringir o acesso de seus funcionários a áreas específicas do site;</li>
			<li>Poderá consultar o andamento de seus pedidos de peças (compra ou garantia), independente do fabricante;</li>
			<li>Poderá consultar suas OS em aberto, filtrando por status ou fabricante.</li>
		</ul>
		<div class="sep"></div>
		<h3>
		Comece agora: Use o usuário e senha de algum de seus fabricantes, e crie seu <strong>Login Principal</strong>.
		<br>
		Depois, crie os logins de seus funcionários, e determine suas áreas de acesso.
		</h3>
	</div>
	<div class="sep"></div>
	<form action="#">
		<h2>Cadastre-se</h2>
		<input type="text" placeholder="Login de um dos Fabricantes">
		<input type="password" placeholder="Senha deste Fabricante">
		<button type="submit"><i class="fa fa-lock"></i>Acessar</button>
		<ul class="links">
			<li><a href="<?php echo $url ?>/login_unico_envio_email.php">Não recebeu o email de confirmação?</a></li>
			<li><a href="http://ww2.telecontrol.com.br/assist/externos/lu_man.php" target="_blank">Visualizar passo a passo</a></li>
		</ul>
	</form>
	</div>
</section>

<?php include('footer.php') ?>
