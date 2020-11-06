<?php
	/* 
     * @description  HD 417698 - Novo processo de geração de extrato
     * @author Brayan L. Rastelli
     * @version 1.0
	 */
	include_once "dbconfig.php";
	include_once "includes/dbconnect-inc.php";

	include_once "autentica_usuario.php";

	include_once('anexaNF_inc.php');

	$temNF = temNF($os, 'bool');

	if ( isset($_POST['salvar']) ) {

		$os = $_POST['os']; 

		$anexou = anexaNF( $os, $_FILES['foto_nf']);
		if ($anexou !== 0) 
			$msg_erro = (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;
	
		if (empty($msg_erro))
			$msg = 'Arquivo enviado com sucesso';

	}

	if (isset($_GET['os'])) {
		$os = $_GET['os'];
	}

	if ($temNF && empty($msg) && empty($msg_erro)) {
		$msg = 'Nota fiscal já anexada para essa OS';
	}

	$sql = "UPDATE tbl_os_troca SET status_os = NULL 
			WHERE status_os = 13 AND os = $os";
			
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

?>

<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title>Geração de extrato</title>
	<style type="text/css">

	body{
		background: #eee;
	}

	h1 {
		font-size:18px;
		color:gray;
	}

	form {
		margin:50px auto;
		width:150px;
	}

	input#file{
		margin-bottom:20px;
	}

	.msg_erro{
        background-color:#FF0000;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
	}

	.sucesso{
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	#salvar {
		border:1px solid white;
		border-radius: 7px;
		background: green;
		color: white;
		width: 100px;
		padding: 5px;
		font-weight: bold;
		cursor:pointer;
		-webkit-box-shadow: 1px 1px 4px rgba(0,0,0,0.4) inset;
		-moz-box-shadow: 1px 1px 4px rgba(0,0,0,0.4) inset;
		box-shadow: 1px 1px 4px rgba(0,0,0,0.4) inset;
	}

	#salvar:active{
		padding: 6px 13px 6px 11px;
	}

</style>
</head>
<body>

	<?php if (!empty($msg_erro)) : ?>

		<div class="msg_erro"><?=$msg_erro?></div>

	<?php endif; ?>

	<?php if (!empty($msg)) : ?>

		<div class="sucesso"><?=$msg?></div>

	<?php endif; ?>

	<?php if (!$temNF) : ?>

		<form action="<?=$PHP_SELF?>" method="POST" enctype="multipart/form-data">

			<input type="file" name="foto_nf" id="file" />
			<input type="hidden" name="os" value="<?=$os?>">

			<input type="submit" id="salvar" name="salvar" />

		</form>

		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js"></script>
		<script type="text/javascript">

			$(function() {

				click = false
				
				$("#salvar").click(function(e) {

					if (click === true) {

						alert('Aguarde submissão do arquivo');
						e.preventDefault();
						return false;

					}

					click = true;

				});

			});

		</script>

	<? endif; ?>

</body>
</html>