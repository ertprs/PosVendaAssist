<?php $pagetitle = "Ranking" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp ranking')</script>

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
				Exposição da posição ocupada por um determinado posto autorizado na relação de assistências técnicas daquela linha de produtos ou marca.
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
					<li><span>1</span><h3>Utilizando os dados disponíveis no sistema a Telecontrol.</h3></li>
					<li><span>2</span><h3>Qualifica-se cada posto autorizado por pontos obtidos nos seguintes indicadores: reincidências, consumo de peças por ordem de serviço e tempo médio de reparo.</h3></li>
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
					<li><h3>A fábrica pode fazer campanhas para incentivar os melhores colocados</h3></li>
					<li><h3>Utilizar informações para concentrar esforços em auditorias dirigidas ao postos com menor desempenho</h3></li>
					<li><h3>Motivar o assistente técnico a fazer auto-avaliação dos seus processos internos e buscar saber como outros postos fazem</h3></li>
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