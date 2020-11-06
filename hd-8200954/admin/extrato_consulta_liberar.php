<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';

	$extrato	 = trim (strtolower ($_REQUEST['extrato']));
	$autorizacao = trim (strtolower ($_REQUEST['autorizacao']));

	if( !empty($_POST['numero_requisicao']) ){
		if( $numero_requisicao = filter_input(INPUT_POST, 'numero_requisicao', FILTER_SANITIZE_STRING) ){

			//Verifica se ja foi inserido um pagamento com esse extrato
			$stmt = $pdo->prepare("SELECT * FROM tbl_extrato_pagamento WHERE extrato = {$extrato}");

			if( $stmt->execute() AND $stmt->rowCount() >= 1 ){
				$stmt = $pdo->prepare("UPDATE tbl_extrato_pagamento SET autorizacao_pagto = :autorizacao WHERE extrato = :extrato");
			}else{
				$stmt = $pdo->prepare("INSERT INTO tbl_extrato_pagamento (extrato, autorizacao_pagto) VALUES (:extrato, :autorizacao)");	
			}

			$stmt->bindValue(':extrato',     $extrato);
			$stmt->bindValue(':autorizacao', $numero_requisicao);

			// executa e verifica se houve ao menos um resutaldo
			// se a condição for verdadeira, então, fecha o modal e adiciona um botão liberar na coluna liberar na linha do registro em questão
			if( $stmt->execute() && $stmt->rowCount() >= 1 ){
				echo "<script> 
						alert('Número da Requisição gravado com sucesso!');
						
						var parent = window.parent;	

						parent.Shadowbox.close();

						var el = parent.document.querySelector('.linha_{$extrato}').children;
						el[7].innerText = '{$numero_requisicao}';
						el[9].innerHTML = \"<a id='btn_confirmar_liberacao' data-extrato='{$extrato}' style='cursor: pointer; color: #0000FF; font-size: 15px;' onclick='onBeforeLiberacao({$numero_requisicao}, {$extrato});'/>Liberar</a>\";

					</script>";
			}	

		}else{
			echo "<script> alert('Número de requisição inválido') </script>";
		}
	}

	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
      	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
  		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
  		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
  		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

		<meta http-equiv='pragma' content='no-cache'>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

			.field {
				
				padding: 10%;
				text-align: center;
			}

		</style>
		<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<script type='text/javascript'>
			//funÃ§Ã£o para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();
			});

		</script>
	</head>

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>

		<div class='lp_pesquisando_por'>Liberação de Extrato</div>
		
		<form action="" method="POST">
			<div class="field">
				<label>
					<h6>Número da Requisição</h6>
					<input name="numero_requisicao" class="form-control" autofocus required>
				</label><br>
				<button type='submit' class="btn btn-success">Confirmar Requisição</button>
			</div>
		</form>

	</body>
</html>
