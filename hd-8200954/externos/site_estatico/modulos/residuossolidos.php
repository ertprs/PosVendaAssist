<?php $pagetitle = "Resíduos Sólidos" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp residuossolidos')</script>

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
				Foi desenvolvido com o propósito de auxiliar a fábrica a incentivar a coleta de peças e/ou produtos obsoletos que não dever ser simplesmente descartados pelo usuário final ou pelo posto autorizado da marca.
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
					<li><span>1</span><h3>A fábrica cria incentivo através de campanhas visando a entrega nos postos de coletas.</h3></li>
					<li><span>2</span><h3>É registrado no sistema os dados que deram origem a entrega (nome, descrição, composição básica e peso).</h3></li>
					<li><span>3</span><h3>O acúmulo deste material é coletado periodicamente pela fábrica.</h3></li>
					<li><span>4</span><h3>O material é encaminhado a empresas especializadas no descarte apropriado dos componentes.</h3></li>
					<li><span>5</span><h3>Podem ser emitidos certificados pelas empresas que atestam o volume dos resíduos efetivamente. O Ministério Público acrescenta novos itens que devem ser enquadrados neste regime.</h3></li>
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
					<li><h3>Reduz a possibilidade de multas ou incidências de TACs (Termo de Ajuste de Conduta) aplicado por órgãos fiscalizadores</h3></li>
					<li><h3>Contribui para preservação da natureza</h3></li>
					<li><h3>Possibilita a reutilização da matéria (reciclagem)</h3></li>
					<li><h3>Imagem de responsabilidade junto ao meio ambiente</h3></li>
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