<?php $pagetitle = "Loja Virtual" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp lojavirtual')</script>

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
				Um ambiente mais amigável e convidativo para estimular a compra de peças e acessórios pelos postos autorizados.
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
					<li><span>1</span><h3>A fábrica disponibiliza somente aos seus postos autorizados a possibilidade de abastecer seu estoque de peças em condições promocionais.</h3></li>
					<li><span>2</span><h3>Pode-se acrescentar detalhes técnicos da peça (especificações, aplicações, fotos, etc.).</h3></li>
					<li><span>3</span><h3>Pode-se acrescentar vantagens comerciais, como: descontos progressivos e inclusão de frete.</h3></li>
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
					<li><h3>Incentiva o comércio de peças originais.</h3></li>
					<li><h3>Facilita a divulgação de peças e acessórios em promoção.</h3></li>
					<li><h3>Criação de campanhas de vendas para </h3></li>
					<li><h3>os postos autorizados.</h3></li>
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