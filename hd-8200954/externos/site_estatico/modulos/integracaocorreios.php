<?php $pagetitle = "Integração Correios" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp integracaocorreios')</script>

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
					Integração com o sistema SIGEP-WEB dos Correios.
					<br>Utilizado para controlar o envio de produtos do consumidor para um posto autorizado, ou para o fabricante (RMA).
					<br>Mantemos atualizados os postos autorizados quanto ao rastreio dos objetos enviados pelo fabricante.
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
					<li><span>1</span><h3>O atendente recebe a ligação do cliente e identifica que o produto precisa ser enviado.</h3></li>
					<li><span>2</span><h3>O módulo de Call Center terá as seguintes opções:<br>a. Autorização de postagem - o cliente tem um número e pode postar o produto sem custo nenhum
					<br>b. Solicitação de coleta - o correio vai até a casa do cliente e coleta o prooduto.</h3></li>
					<li><span><i class="fa fa-check"></i></span><h3>É selecionado a opção correta e depois gerado imediatamente o número da autorização ou solicitação.</h3></li>
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
					<li><h3>Mais velocidade no atendimento ao consumidor.</h3></li>
					<li><h3>Rastreabilidade automatizada.</h3></li>
					<li><h3>Informações do rastreio nas telas do sistema.</h3></li>
					<li><h3>Acompanhamento de prazos.</h3></li>
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