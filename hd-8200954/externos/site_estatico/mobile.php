<?php $webapp = "<link rel='shortcut icon' href='images/webapp.png' />" ?>

<?php include('header.php'); ?>

<script>$('body').addClass('home');</script>

<script type="text/javascript">
  if( !/Android|webOS|iPhone|iPod|Windows Phone|BlackBerry/i.test(navigator.userAgent) ) {
    document.location = "<?php echo $url ?>"; }
</script>

<div class="fullpage home">
	<header class="main m-header">
		<div class="m-logo"><a><img src="<?php echo $url; ?>/images/logo.png"></a></div>
		<div class="desc">Seu Pós-Venda<br>em um único Software</div>
		<div class="conheca"><a href="#conheca">Conheça mais<i class="fa fa-angle-down"></i></a></div>
	</header>
</div>

<section id="conheca" class="quem-somos">
	<div class="main">
		<div class="pad-top title t-border">
			<h2>A Telecontrol</h2>
		</div>
		<div class="desc pad-tb"><p>
		Nos destacamos pelo pioneirismo no desenvolvimento de sistemas para internet e pela vanguarda tecnológica sem abrir mão das técnicas largamente utilizadas e consagradas pelo mercado. 
		<br><br>
		Graças a expressivos resultados obtidos ao longo de 20 anos de existência estendemos nossas atividades para serviços às indústrias e importadores.
		</p></div>
	</div>
</section>

<section class="fx-soft">
		<div class="tbox"><h2 class="no-m">Software Telecontrol</h2></div>
		<ul class="main">
			<li>
			  <div class="cell icon"><i class="fa fa-cloud"></i></div>
			  <div class="cell"><h3>100% online</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-code"></i></div>
			  <div class="cell"><h3>Integração com os principais ERPs do mercado</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-truck"></i></div>
			  <div class="cell"><h3>Distribuição Logística</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-cube"></i></div>
			  <div class="cell"><h3>Gestão completa de Pós-Venda</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-server"></i></div>
			  <div class="cell"><h3>Servidores Amazon</h3></div>
			</li>
		</ul>
		<div class="tbox"><h2>Vantagens Telecontrol</h2></div>
		<ul class="main">
			<li>
			  <div class="cell icon"><i class="fa fa-users"></i></div>
			  <div class="cell"><h3>Sem limites de usuários</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-dollar"></i></div>
			  <div class="cell"><h3>Economia com administração eficaz do envio de peças de reposição</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-thumbs-up"></i></div>
			  <div class="cell"><h3>Totalmente gratuito para a sua Rede Autorizada</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-cogs"></i></div>
			  <div class="cell"><h3>Aumento na venda de peças e acessórios pela Rede Autorizada</h3></div>
			</li>
			<li>
			  <div class="cell icon"><i class="fa fa-random"></i></div>
			  <div class="cell"><h3>Flexibilidade para adaptações do sistema aos processos internos</h3></div>
			</li>
		</ul>
		<div class="btn m-tb"><a href="<?php echo $url; ?>/software.php">Conheça nosso Software</a></div>
</section>

<section class="m-top frase fr2">
	<h3>Seu Pós-Venda em um único Software</h3>
</section>

<section class="fx-mod">
	<div class="main3">
		<div class="title2"><h2>Módulos Telecontrol</h2></div>
		<div class="desc">
			<p class="text-center">Desenvolvemos módulos fazendo com que nosso software complete seu Pós-Venda.</p>
		</div>
		<div class="btn"><a href="<?php echo $url; ?>/modulos.php">Conheça todos os módulos</a></div>
	</div>
</section>

<?php include('footer.php'); ?>