<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";

	$comunicado	= trim($_REQUEST["comunicado"]);
	if(!$comunicado) die();

	$sql = " SELECT mensagem, extensao, descricao, tipo FROM tbl_comunicado WHERE comunicado = $comunicado;";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)){
		$mensagem 	= pg_fetch_result($res, 0, 'mensagem');
		$extensao 	= pg_fetch_result($res, 0, 'extensao');
		$descricao	= pg_fetch_result($res, 0, 'descricao');
		$tipo       = pg_fetch_result($res, 0, 'tipo');
	}

	if ($S3_sdk_OK) {
		include_once S3CLASS;
		$tipo_s3 = in_array($comunicado_tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';
		$s3 = new anexaS3($tipo_s3, (int) $login_fabrica, $comunicado);
	}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			
		</script>
	</head>

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>

		<div>
			<div class='lp_pesquisando_por'><strong>Comunicado: <?php $descricao = str_replace("DEWALT", "D<span style='font-size: 14px'>E</span>WALT", $descricao); echo $descricao; ?></strong></div>

			<div class='lp_pesquisando_por' style='text-align: left;'>
				<?php 
					$mensagem = str_replace("Clique aqui", $link, $mensagem);
					$mensagem = str_replace("clique aqui", $link, $mensagem);

					$mensagem = str_replace("DEWALT", "D<span style='font-size: 14px'>E</span>WALT", $mensagem);

					echo nl2br($mensagem); 

					if ($extensao) {
						if ($S3_online and $s3->temAnexos($comunicado))	{
							$linkFile = $s3->url;
						} else {
							$arquivo = "comunicados/{$comunicado}.{$extensao}";
							if (file_exists($arquivo))
								$linkFile = $arquivo;
						}
					}
					if(isset($linkFile)){
						echo "<br /><br /><div class='lp_msg_erro'><a href='$linkFile' target='_blank' style='text-decoration: none; color: #000'>Arquivo em ANEXO</a></div>";
					}
				?>
			</div>
			
		</div>
	</body>
</html>
