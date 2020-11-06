<?php $pagetitle = "Status SMS" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp statussms')</script>

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
				<div class="desc"><h2>O status SMS foi desenvolvido para melhorar o contato entre Posto Autorizado e Cliente, fazendo com que ele receba informações do processo completo de reparo.</h2>
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
					<li><span>1</span><h3>O Cliente envia produto para o Posto Autorizado.</h3></li>
					<li><span>2</span><h3>O Posto Autorizado verifica o problema do produto.</h3></li>
					<li><span>3</span><h3>O Cliente é informado através de SMS ou E-Mail do diagnostico e prazo do reparo.</h3></li>
					<li><span>4</span><h3>O Cliente acompanha todo o andamento do reparo até receber o produto.</h3></li>
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
					<li><h3>Formas de contato (SMS, e-Mail).</h3></li>
					<li><h3>Confiança na marca.</h3></li>
					<li><h3>Velocidade de comunicação.</h3></li>
					<li><h3>Acompanhamento do processo completo.</h3></li>
					<li><h3>Satisfação do cliente.</h3></li>
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