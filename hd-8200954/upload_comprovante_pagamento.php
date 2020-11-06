<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
	include "funcoes.php";

	$pedido = $_GET['pedido'];

	if(isset($_POST['enviando'])){

		include_once S3CLASS;

		$amazonTC = new AmazonTC("pedido", $login_fabrica);

		$imagem = $_FILES['comprovante'];
		$pedido = $_POST['pedido'];

		$types = array("png", "jpg", "jpeg", "bmp", "pdf", 'doc', 'docx', 'odt');

		if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){

          	$type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));

          	if(!in_array($type, $types)){
            	$pathinfo = pathinfo($imagem["name"]);
            	$type = $pathinfo["extension"];
          	}

          	if (!in_array($type, $types)) {

            	$msg_erro = "Formato inválido, são aceitos os seguintes formatos: png, jpg, jpeg, bmp, doc, odt e pdf";

          	} else {

            	$fileName = "comprovante_pedido_{$login_fabrica}_{$pedido}";                

            	$amazonTC->upload($fileName, $imagem, "", "");

            	$link = $amazonTC->getLink("$fileName.{$type}", false, "", "");

            	$msg_sucesso = "Comprovante de Pagamento Inserido com Sucesso";
          	}

        }

	}

?>

<html>
	<head>
		<title>Upload de Comprovante de Pagamento</title>

		<style>
			body{
				font-family: arial;
				padding: 20px;
				background: #fff;
				width: 480px;
			}
			.erro{
				padding: 10px;
				width: 100%;
				background: #ff0000;
				color: #ffffff;
				margin-bottom: 20px;
			}

			.sucesso{
				padding: 10px;
				width: 100%;
				background: green;
				color: #ffffff;
				margin-bottom: 20px;
			}
		</style>

	</head>
	<body>

		<?php

		if(!empty($msg_erro)){
			echo "<div class='erro'>{$msg_erro}</div>";
		}

		if(!empty($msg_sucesso)){
			echo "<div class='sucesso'>{$msg_sucesso}</div>";
		}

		?>

		<form method="POST" action="<?php echo  $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
			<strong>Insira o Comprovante de Pagamento</strong> <br /> <br />
			<input type="file" name="comprovante" /> <br /> <br />
			<input type="hidden" name="pedido" value="<?php echo $pedido; ?>" />
			<input type="hidden" name="enviando" value="sim" />
			<input type="submit" value="Enviar" />
		</form>
	</body>
</html>