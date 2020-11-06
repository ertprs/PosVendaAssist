<?php $pagetitle = "Login Único" ?>

<?php
	header("Content-Type:text/html; charset=utf-8");
 include('site_estatico/header.php'); ?>
<script>$('body').addClass('pg log-page')</script>

<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Login Único</h2></div>
		<h3>Passo a passo</h3>
	</div>
</section>

<section class="pad-1 login login-passos">
	<div class="main">

		<div class="title">
			<h2>Como fazer o cadastro</h2>
		</div>

		<ul class="ul1">

		<li>
			<p>
				Para acessar ao cadastro do Login Único, clique no link login único logo na primeira linha da página de acesso da Telecontrol.
			</p>
			<div class="img">
				<img src="imagens/lu00.png">
			</div>
		</li>

		<li>
			<h4><i>1</i>Passo</h4>
			<h2>Acessar</h2>
			<p>
			O Cadastro é iniciado com o login e senha de qualquer Fabricante.
			<br>Depois de clicar em Acessar, a próxima tela é a do Cadastro de usuário.
			</p>
			<div class="img">
				<img src="imagens/lu01.png">
			</div>
		</li>

		<li>
			<h4><i>2</i>Passo</h4>
			<h2>Preencher Cadastro</h2>
			<p>
			Para cadastrar o usuário principal, devem-se preencher todos os campos obrigatórios (seu nome, endereço de e-mail válido e a senha de acesso ao Login Único, que deve conter obrigatóriamente letras e números), e ativar a opção de "Usuário Master" logo embaixo.
			</p>
			<div class="desc">
				<h2>Dados cadastrais</h2>
				<ul>
				<li>Nome: O nome do usuário</li>
				<li>E-Mail: Endereço válido de e-mail do usuário*</li>
				<li>Senha: A senha tem que conter no mínimo 2 letras e 2 números, sendo no mínimo de 6 caracteres</li>
				</ul>
				<img src="imagens/lu02a.png">
			</div>
			<div class="desc">
				<h2>Cadastro do Usuário Master</h2>
				<p>
					O Usuário Master é o usuário principal, que tem acesso a todas as informações e ações disponibilizadas pelo fabricante.
					<br>O Usuário Master é quem irá a gerenciar o nível de acesso dos usuários de seu Posto Autorizado.
				</p>
				<img class="mb" src="imagens/lu02c.png">
				<p>Por último, selecione a opção Usuário ativo e clique no botão Gravar para finalizar a primeira parte do processo.</p>
			</div>
			<div class="desc">
				<h2>Delimitando o acesso</h2>
				<p>Cada usuário poderá ter acesso a todas ou a algumas áreas do sistema:</p>
				<img class="mb" src="imagens/lu02b.png">
				<ul>
					<li>Cadastro ou digitação de Ordens de Serviço</li>
					<li>Pedido em garantia de peças (para OS de produto em garantia)</li>
					<li>Pedido faturado de peças (para produtos fora da garantia)</li>
					<li>Fechamento de Ordens de Serviço</li>
					<li>Ver o extrato mensal do Posto</li>
					<li>Definir os técnicos e o usuário master</li>
				</ul>
			</div>
		</li>

		<li>
			<h4><i>3</i>Passo</h4>
			<h2>Finalizando o Cadastro</h2>
			<p>
			Após o envio dos dados para o cadastro do usuário, este irá a receber na sua caixa de entrada do e-mail fornecido uma mensagem de confirmação, que serve para validar ou conferir o e-mail informado.
			</p>
			<div class="img"><img src="imagens/lu_mail.png"></div>
			<p class="mt">
				Ao clicar no link ou colá-lo no navegador, o Sistema valida seu e-mail e informa que está pronto para acessar o Assist usando o seu Login Único.
			</p>
			<div class="img"><img src="imagens/lu_validado.png"></div>
		</li>

		</ul>

		<div class="title mt">
			<h2>Entrando no Assist com o Login Único</h2>
		</div>

		<ul class="ul1">
			<li>
				<p>
					Uma vez confirmado o processo de cadastro, você poderá acessar finalmente o sistema Telecontrol com o e-mail do Login Único como código e como senha, a senha que escolheu. Com isso terá acesso a todos os fabricantes com que você trabalha. Isto quer dizer que não vai precisar mais se lembrar do código e senha de cada um deles.
				</p>
				<div class="img"><img src="imagens/lu_login.png"></div>
				<p class="mt">
					Dentro da página do Login Único vai ter uma coluna com o logotipo das fábricas as quais tem acesso. Ao clicar no logotipo da fábrica vai acessar às mesmas páginas, menus e opções que já tinha.
				</p>
				<div class="img"><img src="imagens/lu_menu.png"></div>
				<p class="mt">
					A diferença principal é que quando quiser trocar de fábrica, não vai precisar mais sair do sistema e entrar com outro código e senha. Simplesmente irá a clicar no link Inicio que está acima à direita, para voltar à página do Login Único, para selecionar uma outra fábrica.
				</p>
				<div class="img"><img src="imagens/lu_voltar.png"></div>
			</li>
		</ul>

	</div>

</section>

<?php include('site_estatico/footer.php') ?>