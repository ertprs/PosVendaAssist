<?php 
include "conn.php";
$page = "conosco";
include "inc/head.php"; 
?>
</head>
<style>
#sidebar{float:left; position:relative; top:0; margin:0;}
#nav_menu li .submenu_assintencia { display:block; }
</style>
<body>
	<div id="wrap">
	<?php include "inc/top.php"; ?>
	<div class="container">
		<div class="content">
			<?php include "inc/sidebar.php"; ?>
			<ul id="breadcrumb">
				<li><a href="index.php">Home</a></li>
				<li><a href="central.php">Central De Relacionamento</a></li>
			</ul>
			<h1>Central de Relacionamento.</h1>
			<div class="box_content">
				<p>Se você deseja entrar em contato conosco, preencha o formulário abaixo e aguarde nosso retorno.</p>
				<form id="formCentral" method="post" action="insert-central.php">
					<fieldset>
						<label for="name">Nome:</label>
						<input type="text" class="required" name="name" id="name"></input>
						<label for="end">Endereço:</label>
						<input type="text" class="required" name="end" id="end"></input>
						<label for="comple">Complemento:</label>
						<input type="text" name="comple" id="comple"></input>
						<label for="cep">CEP:</label>
						<input type="text" class="required" name="cep" id="cep"></input>
						<label>Estado:</label>
						<select class="required">
							<option></option>
							<option>Santa Catarina</option>
						</select>
						<label>Assunto:</label>
						<select class="required" name="subject" style="width:710px;">
							<option></option>
							<option>Solicitação de informações</option> 
							<option>Sugestão</option> 
							<option>Reclamação Posto Autorizado</option> 
							<option>Reclamação de Produto/Defeito</option> 
							<option>Reclamação empresa</option>
						</select>
						<label>Família:</label>
						<select class="required">
							<option></option>
							<option>Selecione</option>
						</select>
					</fieldset>
					<fieldset>
						<label for="mail">E-mail:</label>
						<input type="text" class="required" name="mail" id="mail"></input>
						<label for="numero">Número:</label>
						<input type="text" class="required" name="numero" id="numero"></input>
						<label for="bairro">Bairro:</label>
						<input type="text" class="required" name="bairro" id="bairro"></input>
						<label for="phone">Telefone:</label>
						<input type="text" class="required" name="phone" id="phone"></input>
						<label for="city">Cidade:</label>
						<input type="text" class="required" name="city" id="city"></input>
						<label style="margin-top:62px;">Produto:</label>
						<select class="required">
							<option></option>
							<option>Selecione</option>
						</select>
					</fieldset>
					<fieldset id="textarea">
						<label for="msg">Mensagem</label>
						<textarea name="msg" class="required" id="msg"></textarea>
						<input type="submit" value="Enviar"></input>
					</fieldset>
				</form>
				<span class="opcao_central">
					<h2>Central de Relacionamento com o Cliente<em> Horário de Atendimento: das 08h às 18h.</em></h2>
					<img src="img/img_contato.png" width="43" height="42" alt=""/>
					<h3>(54) 3290 2200</h3>
				</span>
			</div>
		</div>
	</div>	
	</div>
	<?php include "inc/footer.php"; ?>
</body>
</html>
