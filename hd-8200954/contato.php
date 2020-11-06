<?php 

	$lista['ATENDIMENTO']   = 'atendimento@telecontrol.com.br';
	$lista['HELPDESK']      = 'helpdesk@telecontrol.com.br';
	$lista['COMERCIAL']     = 'comercial@telecontrol.com.br';
	$lista['DIRETORIA']     = 'diretoria@telecontrol.com.br';
	$lista['DISTRIB']       = 'distribuicao@telecontrol.com.br';

	$msg['ATENDIMENTO'][] 	= 'Sugest&atilde;o';
	$msg['ATENDIMENTO'][] 	= 'Reclama&ccedil;&otilde;es/Cr&iacute;ticas';

	$msg['HELPDESK'][] = 'Dificuldade';
	$msg['HELPDESK'][] = 'Falha no sistema';
	$msg['HELPDESK'][] = 'Perda de senha';
	$msg['HELPDESK'][] = '1&ordm;. acesso';
	$msg['HELPDESK'][] = 'Outros (Essa op&ccedil;&atilde;o n&atilde;o est&aacute; na fila de prioridades)';

	$msg['COMERCIAL'][] = 'Agendar visita';
	$msg['COMERCIAL'][] = 'Interesse no sistema';
	$msg['COMERCIAL'][] = 'Contratar Distribui&ccedil;&atilde;o';
	$msg['COMERCIAL'][] = 'Agendar Treinamento';
	$msg['COMERCIAL'][] = 'Outros (Essa op&ccedil;&atilde;o n&atilde;o est&aacute; na fila de prioridades)';

	$msg['DIRETORIA'][] = 'Sugest&otilde;es';
	$msg['DIRETORIA'][] = 'Reclama&ccedil;&otilde;es/Cr&iacute;ticas';

	$msg['DISTRIB'][] = 'Pedidos';
	$msg['DISTRIB'][] = 'Emerg&ecirc;ncia no envio de pe&ccedil;as';
	$msg['DISTRIB'][] = 'Sugest&otilde;es';
	$msg['DISTRIB'][] = 'Extrato';
	$msg['DISTRIB'][] = 'Reclama&ccedil;&otilde;es/Cr&iacute;ticas';

	$ip = $_SERVER['REMOTE_ADDR'];

	if (strlen ($ip) == 0 OR substr ($ip,0,3) == "10.") {
    	$ipString=@getenv("HTTP_X_FORWARDED_FOR");
    	$addr = explode(",",$ipString);
    	$ip = $addr[sizeof($addr)-1];
	}

	$host = explode ( '.', exec("host $ip") );

	$host = ucfirst($host[6]);

	if ( isset( $_GET['nome'] ) ) {	

		$return = array();

		$dados['E-mail'] 		= trim( $_GET['email'] );
		$dados['confirma_email']= trim( $_GET['confirma_email'] );
		$dados['Nome'] 			= trim( $_GET['nome'] );
		$dados['Mensagem'] 		= htmlentities ( trim( $_GET['mensagem'] ) );

		$dados['Falar Sobre'] 	= trim( $_GET['falar_sobre'] );
		$dados['Assunto'] 		= trim( $_GET['assunto'] );

		foreach($dados as $k => $v) {
			
			if ( empty($v) && $k != 'confirma_email' ) {
				
				$return[] = 'Preencha o campo ' . $k;

			}

			if ( $k == 'E-mail' && !empty($v) ) {
				
				if ( !filter_var($v, FILTER_VALIDATE_EMAIL) ) {
					
					$return[] = 'O campo ' . $k . ' deve conter um e-mail válido';

				}

				else if ( $v != $dados['confirma_email']) {
					
					$return[] = 'O campo Confirme seu e-mail deve ser igual ao campo e-mail';

				}

			}

		}

		if ( empty($return) ) {

			$to = $lista[$_GET['falar_sobre']];

			$mensagem = "
				Nome : {$dados['Nome']} <br />
				E-mail: {$dados['E-mail']} <br />
				IP: {$ip} - Provedor: {$host}<br /><br />
				Mensagem:<br />
				" . html_entity_decode ( $dados['Mensagem'] ) . "<br />
				--<br />
				<div style='font-size:12px;'>
					Suporte Telecontrol<br />
					<strong>Este e-mail foi enviado via formul&aacute;rio de contato da Telecontrol Networking.</strong>
				</div>
			";

			$headers = "From:" . $dados['Nome'] . '<' . $dados['E-mail'].">\nContent-type: text/html\n";

			 if ( !mail( $to, $dados['Falar Sobre'] . ' - ' . $dados['Assunto'], $mensagem, $headers ) ) {

			 	$return[] = 'Falha ao enviar e-mail. Tente novamente mais tarde.';
				
			 } else {

			 	$return['msg'] = 'Mensagem enviada com sucesso.';

			 }

		}

		echo json_encode($return);

		return;

	}

?>

<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>Contato</title>
	<style type="text/css">

		/* resets */
		html, body, div, span, applet, object, iframe,
		h1, h2, h3, h4, h5, h6, p, blockquote, pre,
		a, del, em, font, img, small, strike, strong, sub, b, u, i, center,
		dl, dt, dd, ol, ul, li,
		fieldset, form, label, legend,
		table, caption, tbody, tfoot, thead, tr, th, td {
			margin: 0;
			padding: 0;
			border: 0;
			outline: 0;
			font-size: 100%;
			vertical-align: baseline;
			background: transparent;
		}

		#contatos {
			width:600px;
			margin:5px auto;
			background:#ededed;
			border-radius:8px;
			font-family:Verdana, Geneva, sans-serif;
			font-size:12px;
			padding:5px;
			-moz-box-shadow:    1px 3px 5px 1px #ccc;
  			-webkit-box-shadow: 1px 3px 5px 1px #ccc;
  			box-shadow:         1px 3px 5px 1px #ccc;
		}

		#contatos h3 {
			padding-top:10px;
			text-align:center;
		}

		#contatos h3, #contatos form p label {
			color:#447296;
			font-weight:bold;
		}

		#contatos form{ 

			width:500px;
			margin:20px auto;

		}

		#msg { text-align:center; margin-top:10px; font-weight:bold;}

		.sucesso { color:green; }

		.erro { color:red; }

		#contatos form input, #contatos form select, #contatos form textarea{
			
			padding:5px;

		}

		#contatos form label {
			
			width:180px;
			float:left;
			padding:5px;

		}

		#contatos form label.textarea {
			
			line-height:150px;

		}

		#contatos form p {
			margin-bottom: 10px;
		}

		.text{
			width:255px;
		}

		#contatos form select {
			font-size:12px;
			color: gray;
			width:268px;
		}

	</style>
</head>
<body>
	
	<div id="contatos">
		
		<h3>ENTRE EM CONTATO</h3>

		<p style="text-align:justify;">
				O departamento envolvido far&aacute; contato em breve, atrav&eacute;s do endere&ccedil;o eletr&ocirc;nico informado
				abaixo (certifique-se da digita&ccedil;&atilde;o). O limite de espa&ccedil;o para a mensagem &eacute; de 300 caracteres.<br /><br />

				Caso o seu contato seja para informar dificuldades no acesso do sistema, antes de registrar,
				siga os seguintes passos:<br /><br />

				Limpe os cookies do seu navegador, feche-o e volte a acessar novamente.<br />&bnsp;<br />

				A equipe da Telecontrol agradece o seu contato.
		</p>
	
		<div id="msg"></div>

		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">

			<p>
				<label for="seu_nome">* NOME</label>
				<input type="text" name="nome" id="seu_nome"  class="text" />
			</p>

			<p>
				<label for="email">* E-MAIL</label>
				<input type="text" name="email" id="email" class="text" />
			</p>

			<p>
				<label for="confirma_email">* CONFIRME SEU E-MAIL</label>
				<input type="text" name="confirma_email" id="confirma_email" class="text" onCopy="return false" onDrag="return false" onDrop="return false" onPaste="return false" />
			</p>

			<p>

				<label for="falar_sobre">* &Aacute;REA ENVOLVIDA</label>
				<select name="falar_sobre" id="falar_sobre">
					
					<option value=""></option>
					<option value="ATENDIMENTO">Atendimento</option>
					<option value="COMERCIAL">Comercial</option>
					<option value="DIRETORIA">Diretoria</option>
					<option value="DISTRIB">Distribui&ccedil;&atilde;o</option>
					<option value="HELPDESK">Help-desk</option>

				</select>

			</p>

			<p>

				<label for="assunto">* QUAL O ASSUNTO</label>
				<select name="assunto" id="assunto">
					<option value=""></option>
				</select>

			</p>

			<p>
				<label for="mensagem" class="textarea">* MENSAGEM</label>
				<textarea name="mensagem" id="mensagem" cols="30" rows="10"></textarea>
			</p>

			<p style="text-align:center;">
				<input type="submit" name="enviar" value="Enviar" id="enviar" style="width:auto;" />
				<span style="float:right; font-size:10px; text-align:right;">
					Seu IP: <?=$ip?><br />
					Provedor: <?=$host?>
				</span>
			</p>			

		</form>
			
	</div>

	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>

	<script type="text/javascript">

		$().ready(function(){

			assunto = new Array();

			<?php

				foreach($msg as $k => $v) {

					echo 'assunto["'.$k.'"] = new Array();' . PHP_EOL;

					$i = 0;

					foreach ($v as $item) {

						echo 'assunto["'.$k.'"]['.$i.'] = ' . "'$item';" . PHP_EOL;

						$i++;

					}

				} 

			?>

			function trocaAssunto(obj) {
				
				var value = $(obj).val();

				$('#assunto').children().remove().end().append('<option selected value=""></option>') ;

				if (typeof assunto[value] == 'undefined') {

					return false;

				}
				
				for (var i = 0; i < assunto[value].length; i++ ) {
					
					$("#assunto").append('<option value="'+assunto[value][i]+'">'+assunto[value][i]+'</option>');

				}

			}

			trocaAssunto($("select#falar_sobre"));
			
			$("select#falar_sobre").change(function(e) {
				
				var obj = $(this);

				trocaAssunto(obj);

			});

			sent = false;

			$("#enviar").click(function(e){

				if ( sent === true ) {
					
					alert('Aguarde o envio.');

					return false;

				}
				
				$.getJSON("<?php echo $_SERVER['PHP_SELF']; ?>", $("form").serialize(), function(data){

					$("#msg").html('');

					if ( typeof data.msg != 'undefined' ) {

						$("#msg").removeClass('erro').addClass('sucesso').html(data.msg);

						sent = true;

						return;

					}
					
					$.each(data, function(i, obj){
						
						$("#msg").removeClass('sucesso').addClass('erro').append('<p>' + obj + '</p>');							

					});

				});				

				e.preventDefault();

			});

		});

	</script>
</body>
</html>
