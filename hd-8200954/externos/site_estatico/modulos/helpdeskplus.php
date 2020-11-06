<?php $pagetitle = "HelpDesk Plus" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp helpdeskplus')</script>

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
				Assim como o fabricante abre chamados para a Telecontrol, os postos autorizados podem utilizar a mesma ferramenta para abrir chamados para o fabricante. A gestão e acompanhamento destes casos segue um padrão rigoroso
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
					<li><span>1</span><h3>O cliente envia sua dúvida.</h3></li>
					<li><span>2</span><h3>É aberto um HelpDesk.</h3></li>
					<li><span>3</span><h3>Será feito o atendimento pelo responsável.</h3></li>
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
					<li><h3>Direcionamento conforme o assunto.</h3></li>
					<li><h3>Acompanhamento dos prazos de resposta.</h3></li>
					<li><h3>Mantenha a qualidade do atendimento.</h3></li>
					<li><h3>Centralização das informações.</h3></li>
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