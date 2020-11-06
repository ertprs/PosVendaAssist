<?php 

	$solicitado = !empty($_COOKIE['promocao_melitta']) ? true : false;
	$enviado = false;

	function validaCNPJ($cnpj) { 
		    if (strlen($cnpj) <> 18) return 0; 
		    $soma1 = ($cnpj[0] * 5) + 

		    ($cnpj[1] * 4) + 
		    ($cnpj[3] * 3) + 
		    ($cnpj[4] * 2) + 
		    ($cnpj[5] * 9) + 
		    ($cnpj[7] * 8) + 
		    ($cnpj[8] * 7) + 
		    ($cnpj[9] * 6) + 
		    ($cnpj[11] * 5) + 
		    ($cnpj[12] * 4) + 
		    ($cnpj[13] * 3) + 
		    ($cnpj[14] * 2); 
		    $resto = $soma1 % 11; 
		    $digito1 = $resto < 2 ? 0 : 11 - $resto; 
		    $soma2 = ($cnpj[0] * 6) + 

		    ($cnpj[1] * 5) + 
		    ($cnpj[3] * 4) + 
		    ($cnpj[4] * 3) + 
		    ($cnpj[5] * 2) + 
		    ($cnpj[7] * 9) + 
		    ($cnpj[8] * 8) + 
		    ($cnpj[9] * 7) + 
		    ($cnpj[11] * 6) + 
		    ($cnpj[12] * 5) + 
		    ($cnpj[13] * 4) + 
		    ($cnpj[14] * 3) + 
		    ($cnpj[16] * 2); 
		    $resto = $soma2 % 11; 
		    $digito2 = $resto < 2 ? 0 : 11 - $resto; 
		    
		    return (($cnpj[16] == $digito1) && ($cnpj[17] == $digito2)); 
		} 

	//Envia Pedido via Form
	if(!empty($_POST['pedido'])){ 
		$nome 			= trim($_POST['nome']);
		$telefoe 		= trim($_POST['telefoe']);
		$email 			= trim($_POST['email']);
		$cnpj 			= trim($_POST['cnpj']);
		$produto_a 		= (int) $_POST['produto_a'];
		$produto_b 		= (int) $_POST['produto_b'];
		$observacao 	= trim($_POST['observacao']);
		$erro 			= Array();

		if(strlen($nome) < 2){
			$erro['nome'] = "Informe um nome válido!";
		}

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			$erro['email'] = "Informe um email válido!";
		}

		if(strlen($telefone) < 8){
			$erro['telefone'] = "Informe um telefone com DDD válido!";
		}

		if(!validaCNPJ($cnpj) AND !empty($cnpj)){
			$erro['cnpj'] = "Informe um CNPJ válido!";
		}

		if($produto_a == 0 AND $produto_b == 0){
			$erro['produto_a'] = "A quantidade minima deve ser 5!";
			$erro['produto_b'] = "A quantidade minima deve ser 5!";
		}

		if($produto_a > 0 AND $produto_a % 5 AND empty($erro['produto_a'])){
			$erro['produto_a'] = "A quantidade solicitada deverá ser múltiplo de 5!";
		}

		if($produto_b > 0 AND $produto_b % 5 AND empty($erro['produto_b'])){
			$erro['produto_b'] = "A quantidade solicitada deverá ser múltiplo de 5!";
		}

		if(($produto_a + $produto_b) % 10 AND empty($erro['produto_b']) AND empty($erro['produto_a'])){
			$erro['produto_b'] = "A somatória dos produtos solicitado deverá ser múltiplo de 10!";
			$erro['produto_a'] = "A somatória dos produtos solicitado deverá ser múltiplo de 10!";
		}

		//Se nÃ£o tiver erro enviar email...
		if(empty($erro)){
			$body = Array();

			$header[] = "<h3>Promoção Melitta</h3>";
			$body[] = "<b>Posto:</b> {$nome}";
			$body[] = "<b>Telefone:</b> {$telefone}";
			$body[] = "<b>Email:</b> {$email}";
			$body[] = "<b>CNPJ:</b> {$cnpj}";
			$body[] = "<b>Observação:</b> $observacao<br />";

			$body[] = "<b>Produtos solicitado:</b>";
			$body[] = "<b>'{$produto_a}'</b> - 10356 - Jarra 10 com tampa ME5CMBZ";
			$body[] = "<b>'{$produto_b}'</b> - 10366 - Jarra 20 com tampa ME10CMBZ";
			$body[] = "<br /><hr />";

			$footer[] = "<b>Telecontrol Networking</b>";
			$footer[] = "Data do envio: ".Date('d/m/Y H:i:s');
			$footer[] = "Programa: {$_SERVER['PHP_SELF']}";

			$para      	= 'jarras.melitta@telecontrol.com.br';
			$assunto 	= "[Pedido Melitta] - {$nome}";

			$mensagem[] = implode("<br />", $header);			
			$mensagem[] = implode("<br />", $body);
			$mensagem[] = implode("<br />", $footer);

			$headers   = array();
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "Content-type: text/html; charset=iso-8859-1";
			$headers[] = "From: Pedido <suporte@telecontrol.com.br>";
			$headers[] = "Reply-To: {$nome} <$email>";
			$headers[] = "Subject: {$assunto}";
			$headers[] = "X-Mailer: PHP/".phpversion();

			if(mail($para, $assunto, implode('',$mensagem), implode("\r\n", $headers))){
				setcookie('promocao_melitta', 'enviado',time()+3600*24*7); // equivalente a 7 dias
				
				$enviado = true;
			}
		}

	}


?>
<!DOCTYPE html>
<html lang="en">
  	<head>
    	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
   	 	<title>Melitta</title>
    	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    	<meta name="description" content="PromoÃ§Ã£o Mellita">
    	<meta name="author" content="Ederson Sandre <ederson.sandre@gmail.com>">
    	<meta name="robots" content="noindex, follow">
    	<meta name="robots" content="index, nofollow">
    	<meta name="robots" content="noindex, nofollow">
    	<link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
    	<style type="text/css">
    		body{
    			background: #FCFCFC;
    		}

    		#box{
    			width: 600px;
    			margin: 20px auto;
    			
    		}
    	</style>
  	</head>

  	<body>

  		<div id='box' class='well' >
  			<?php if($enviado){?>
	  			<span class='row-fluid'>
	  				<span class="span8">
	  					<h2>Pedido solicitado!</h2>
	  				</span>
	  				<span class="span4">
	  					<a href="http://www.telecontrol.com.br" target="_blank">
	  						<img src="http://www.telecontrol.com.br/wp-content/uploads/2012/02/logo_tc_2009_texto.png" alt="Telecontrol">
	  					</a><br /><br />
	  				</span>
		  		</span>
		    	<div class="alert alert-success ">
		    		<h4 style='text-align: center'><?php echo $nome?>, seu pedido foi enviado com sucesso!</h4><br />
		    		<?php echo implode('<br />', $body);?>
		    	</div>
		    	<div class='clearfix'>
		    		<span class='pull-right'>
		    			<a href="javascript: void(0);" onclick='javascript:window.print(); ' class="btn"> Imprimir </a>
		    			<a href="http://www.telecontrol.com.br" class="btn btn-primary"> Sair </a>
		    		</span>
		    	</div>
  			<?php } else { ?>
	  			<?php if($solicitado){ ?>
					<div class="alert alert-block">
			    		<h4>Atenção!</h4>
			    		Já foi enviado pedido desta promoção.<br />
			    		Caso tenha interesse em fazer outro pedido preencha os campos abaixo!
			    	</div>	
	  			<?php }?>
	  			<span class='row-fluid'>
	  				<span class="span8">
	  					<h2>Promoção</h2>
			  			<h4>
			  				Jarra original Melitta a preço de similar!<br />
			  				Vendas apenas por kit de 10 jarras
			  			</h4>
	  				</span>
	  				<span class="span4"><br /> 
	  					<a href="http://www.telecontrol.com.br" target="_blank">
	  						<img src="http://www.telecontrol.com.br/wp-content/uploads/2012/02/logo_tc_2009_texto.png" alt="Telecontrol">
	  					</a>
	  				</span>
		  		</span>

				<form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']; ?>" method='POST' >
					<div class='well'>
						<fieldset>
							<legend>Posto Autorizado</legend>
							<div class="control-group <?php  if(!empty($erro['nome'])) echo " error";  ?>">
								<label class="control-label" for="nome">Nome *</label>
								<div class="controls">
									<input type="text" id="nome" name="nome" value="<?php echo $nome; ?>" class='input-xlarge' placeholder="Nome do posto autorizado">
									<?php  if(!empty($erro['nome'])) echo "<span class='help-block'>{$erro['nome']}</span>";  ?>
								</div>

							</div>

							<div class="control-group <?php  if(!empty($erro['telefone'])) echo " error";  ?>">
								<label class="control-label" for="telefone">Telefone *</label>
								<div class="controls">
									<input type="text" id="telefone" name="telefone" value="<?php echo $telefone; ?>" class='input-xlarge' placeholder="Telefone válido para entrar em contato">
									<?php  if(!empty($erro['telefone'])) echo "<span class='help-block'>{$erro['telefone']}</span>";  ?>
								</div>

							</div>

							<div class="control-group <?php  if(!empty($erro['email'])) echo " error";  ?>">
								<label class="control-label" for="email">Email *</label>
								<div class="controls">
									<input type="text" id="email" name="email" value="<?php echo $email; ?>" class='input-xlarge' placeholder="Email válido para entrar em contato">
									<?php  if(!empty($erro['email'])) echo "<span class='help-block'>{$erro['email']}</span>";  ?>
								</div>
							</div>

							<div class="control-group <?php  if(!empty($erro['cnpj'])) echo " error";  ?>">
								<label class="control-label" for="cnpj">CNPJ</label>
								<div class="controls">
									<input type="text" id="cnpj" name='cnpj' value="<?php echo $cnpj; ?>" class='input-xlarge' />
									<?php  if(!empty($erro['cnpj'])) echo "<span class='help-block'>{$erro['cnpj']}</span>";  ?>
								</div>
							</div>
						</fieldset>
					</div>

					<div class='well'>
						<fieldset>
							<legend>Informe a quantidade desejada</legend>
							<div class="control-group <?php  if(!empty($erro['produto_a'])) echo " error";  ?>">
								<label class="control-label" for="produto_a">Conjunto ME5CMBZ</label>
								<div class="controls">
									<input type="text" id="produto_a" name='produto_a' value="<?php echo $produto_a; ?>" class='input-mini' />								
									<?php  
										if(!empty($erro['produto_a'])) 
											echo "<span class='help-block'>{$erro['produto_a']}</span>"; 
										else 
											echo '<span class="help-inline">Conjunto jarra 10 com tampa ME5CMBZ</span>';
									?>
									
								</div>
							</div>

							<div class="control-group <?php  if(!empty($erro['produto_b'])) echo " error";  ?>">
								<label class="control-label" for="produto_b">Conjunto ME10CMBZ</label>
								<div class="controls">
									<input type="text" id="produto_b" name='produto_b' value="<?php echo $produto_b; ?>" class='input-mini' />
									<?php  
										if(!empty($erro['produto_b'])) 
											echo "<span class='help-block'>{$erro['produto_b']}</span>"; 
										else
											echo '<span class="help-inline">Conjunto jarra 20 com tampa ME10CMBZ</span>';
									?>
									
								</div>
							</div>

							<div class="control-group">
								<label class="control-label" for="observacao">Observação</label>
								<div class="controls">
									<textarea id="observacao" name='observacao' class='input-xlarge'><?php echo $observacao; ?></textarea>
								</div>
							</div>
						</fieldset>
					</div>

					<h5>* Pagamento via boleto (30 e 60 dias)</h5>
					<div class="control-group">
						<div class="controls">
							<input type="submit" class="btn btn-primary" name="pedido" value="Enviar Pedido" />
						</div>
					</div>
				</form>
			<?php }?>
  		</div>
  		<script type="text/javascript" src="http://twitter.github.com/bootstrap/assets/js/bootstrap.min.js"></script>
  		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
  		<script type="text/javascript" src="http://posvenda.telecontrol.com.br/assist/js/jquery.maskedinput-1.3.min.js"></script>
  		<script type="text/javascript">
  		jQuery(function($){
		   	$("#cnpj").mask("99.999.999/9999-99");
		   	$("#telefone").mask("(99) 9999-9999");
		});

  		</script>
	</body>
</html>


<!--  
Nome do Posto
Email do Posto
CNPJ
Telefone

Produtos

ObservaÃ§Ã£o

 -->
