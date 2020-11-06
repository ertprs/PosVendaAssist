<?php $pagetitle = "Treinamento Posto" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp treinamentoposto')</script>

<div id="fullpage">
<section class="table h-img">
	<?php include('../menu-pgi.php'); ?>
	<div class="cell">
		<div class="marca"></div>
	</div>
	<div class="main gotomods"><a href="<?php echo $url; ?>/modulos.php"><i class="fa fa-angle-left"></i>Módulos</a></div>
</section>

<section class="inst">
	<div class="main2">
		<div class="table">
			<div class="cell">
				<div class="title"><h1>Telecontrol - Software para gestão de Pós-Venda.</h1></div>
				<div class="desc">
				<h2>
				</h2>
				</div>
			</div>
		</div>
	</div>
</section>

<div class="saibamais"><a href="#saibamais">Saiba mais<i class="fa fa-angle-down"></i></a></div>

</div>

<div id="saibamais"></div>
<section class="cf">
	<div class="main2">
		<div class="table">
			<div class="cell">
				<div class="title"><h2>Como funciona</h2></div>
				<ul>
					<li><span>1</span><h3></h3></li>
					<li><span>2</span><h3></h3></li>
					<li><span>3</span><h3></h3></li>
					<li><span>4</span><h3></h3></li>
					<li><span><i class="fa fa-check"></i></span><h3></h3></li>
				</ul>
			</div>
		</div>
	</div>
</section>

<section class="bnf">
	<div class="main2">
		<div class="table">
			<div class="cell">
				<div class="title"><h2>Benefícios</h2></div>
				<ul>
					<li><h3></h3></li>
				</ul>
			</div>
		</div>
	</div>
</section>

<div id="contato" class="h-fix"></div>
<section class="contato pad-1">
	<div class="main">
	<div class="cell">
	<?php include('../contato-simple.php'); ?>
	</div>
	</div>
</section>

<?php include('../footer.php') ?>