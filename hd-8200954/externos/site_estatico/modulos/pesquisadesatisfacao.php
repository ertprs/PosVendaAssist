<?php $pagetitle = "Pesquisa de Satisfação" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp pesquisadesatisfacao')</script>

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
				Pesquisa de Satisfação podem ser direcionadas tanto aos consumidores quanto aos postos autorizados.
				<br>Medem o grau de satisfação em cada fase do processo.
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
					<li><span>1</span><h3>Existem duas pesquisas: para consumidores finais e para postos autorizados.</h3></li>
					<li><span>2</span><h3>Para consumidores o sistema é capaz de gerar uma pendência para que determinado atendente faça uma pesquisa através de uma ligação telefônica após a finalização da OS.</h3></li>
					<li><span>3</span><h3>A pesquisa para o posto autorizado é disparada pelo sistema periodicamente dando a possibilidade de avaliar o suporte da fábrica a sua rede autorizada.</h3></li>
					<li><span>4</span><h3>Ambos os formulários trazem questões predefinidas e são automaticamente tabuladas com percentuais e gráficos.</h3></li>
					<li><span>5</span><h3>Podem ser amostrais ou para todo o universo, espontâneas ou obrigatórias no caso dos postos autorizados.</h3></li>
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
					<li><h3>Melhoria contínua nos processos de atendimento.</h3></li>
					<li><h3>Valorização do posto autorizado.</h3></li>
					<li><h3>Canal de coleta de sugestões de melhoria.</h3></li>
					<li><h3>Melhora no conceito da marca.</h3></li>
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