<?php $pagetitle = "Chat Online" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp chatonline')</script>

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
					Utilize os benefícios desta ferramenta de comunicação online para interagir com seus consumidores.
					<br>Apenas uma pessoa do SAC atendendo vários consumidores ao mesmo tempo.
					<br>Segue o mesmo workflow para o atendimento do callcenter.
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
					<li><span>1</span><h3>O consumidor envia mensagem.</h3></li>
					<li><span>2</span><h3>O atendente do SAC recebe as mensagens.</h3></li>
					<li><span>3</span><h3>A mensagem é respondida em tempo real.</h3></li>
					<li><span>4</span><h3>O consumidor é direcionado para melhor forma de resolver a situação.</h3></li>
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
					<li><h3>Rápido atendimento</h3></li>
					<li><h3>Produtividade dos atendentes</h3></li>
					<li><h3>Fornece protocolo do atendimento</h3></li>
					<li><h3>Integrado com o sistema de pós-venda, ordem de serviço e troubleshooting</h3></li>
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