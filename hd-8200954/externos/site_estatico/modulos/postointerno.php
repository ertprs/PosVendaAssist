<?php $pagetitle = "Posto Interno" ?>
<?php include('../header.php') ?>
<script>$('body').addClass('pg mod-sp postointerno')</script>

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
				Módulo para controlar a logística e procedimentos em casos de produtos que retornam para conserto no laboratório interno do fabricante.
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
					<li><span>1</span><h3>O cliente possui um canal de atendimento (0800, e-mail ou chat) onde deve buscar solução do problema.</h3></li>
					<li><span>2</span><h3>O atendente certifica-se que o cliente seguiu todas as orientações conforma manual do usuário.</h3></li>
					<li><span>3</span><h3>Quando constatado defeito no produto e não há ponto de assistência técnica autorizada o atendente procede a coleta do produto para um centro de reparo único.</h3></li>
					<li><span>4</span><h3>Existem funções e relatórios específicos para esta modalidade de atendimento, tais como; integração com os correios (sigep web, geração de pré-OS, consulta situação do reparo, relatórios, geração de etiquetas, etc).</h3></li>
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
					<li><h3>Emissão automática da Autorização de Postagem ou Ordem de Coleta junto aos Correios.</h3></li>
					<li><h3>Acompanhamento dos prazos de envio.</h3></li>
					<li><h3>Abertura da Ordem de Serviço já com o resumo do caso.</h3></li>
					<li><h3>Retorno do produto ao consumidor.</h3></li>
					<li><h3>Integração com ERP para emissão de documentos fiscais.</h3></li>
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