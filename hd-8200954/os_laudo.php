<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';

	if (strlen($_REQUEST['os']) == 0) 
		header("Location: os_consulta_lite.php");
	else
		$os = $_REQUEST['os'];

	$acao = trim(@$_REQUEST['acao']);
	$btn_acao = trim(@$_REQUEST['btn_acao']);
	$laudo = $_REQUEST['laudo'];

	if(strlen($btn_acao) > 0){
		function reduz_imagem($img, $max_x, $max_y, $dir) {
			$img = $dir.$img;
			if (!file_exists($img))
				exit('Imagem Inválida!');

			list($original_x, $original_y) = getimagesize($img);

			if($original_x > $original_y) {
			   $porcentagem = (100 * $max_x) / $original_x;
			}else {
			   $porcentagem = (100 * $max_y) / $original_y;
			}

			$tamanho_x = $original_x * ($porcentagem / 100);
			$tamanho_y = $original_y * ($porcentagem / 100);

			if ($original_x < $max_x and $original_y < $max_y) {
				copy($img, $img);
			} else {
				$image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
				$image   = imagecreatefromjpeg($img);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
				imagejpeg($image_p, $img, 90);
			}
		}
		
		$cancelado_justificativa = $_POST['cancelado_justificativa'];
		$texto = $_POST['texto'];
		$tipo_laudo = $_POST['tipo_laudo'];
		$imagem =$_FILES['imagem']['name'];

		if($btn_acao == "Cancelar"){
		
			if(strlen($cancelado_justificativa) == 0){
				$msg_erro = "Já existe laudo emitido para esta OS!";
			}
			
			if (strlen($msg_erro) == 0){
				$res = pg_exec ($con,"BEGIN TRANSACTION");
				$sql = "UPDATE tbl_laudo SET cancelado_justificativa = '$cancelado_justificativa', data_cancelado = NOW() WHERE laudo = $laudo;";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if (strlen($msg_erro) == 0){
					$res = pg_exec($con,"COMMIT TRANSACTION");
					header("Location: $PHP_SELF?os=$os");
				}else{
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			}			
		}
		
		if($btn_acao == "Gravar" or $btn_acao == "Alterar"){
			//valida dados
			if($btn_acao == "Gravar"){
				//Verifica se tem Laudo antes de Gravar
			 	$sql = "SELECT laudo FROM tbl_laudo WHERE tbl_laudo.os=$os AND data_cancelado IS NULL;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) != 0)
					$msg_erro = "Levantamento técnico inválido!";
			}
			
			
			if(strlen($texto) == 0){
				$msg_erro = "Levantamento técnico inválido!";
			}
	
			if(strlen($imagem) == 0 AND strlen($msg_erro) == 0 AND $btn_acao == "Gravar"){
				$msg_erro = "Imagem Inválida!";
			}elseif($btn_acao == "Gravar"){		
				$extensao = strtolower(pathinfo($imagem,PATHINFO_EXTENSION));
				if($extensao != 'jpg' AND $extensao != 'jpeg' AND strlen($msg_erro) == 0){
					$msg_erro = "Tipo de arquivo inválido, somente extensões 'JPG ou JPEG'";
				}
			}
			
	
			if (strlen($msg_erro) == 0){
				$res = pg_exec ($con,"BEGIN TRANSACTION");
				
				if($btn_acao == "Gravar"){
					$sql = "INSERT INTO tbl_laudo (os,texto,tipo_laudo,imagem) VALUES ($os,'$texto',$tipo_laudo,'t') RETURNING laudo;";
					$res = pg_exec($con,$sql);
					$laudo = pg_result($res,0,laudo);
					$msg_erro = pg_errormessage($con);
				}else{
					$sql = "UPDATE tbl_laudo SET texto = '$texto', tipo_laudo = $tipo_laudo WHERE laudo = $laudo;";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
				
				if(strlen($msg_erro) == 0 and $btn_acao == "Alterar" and strlen($imagem) != 0){
					$extensao = strtolower(pathinfo($imagem,PATHINFO_EXTENSION));
					if($extensao != 'jpg' AND $extensao != 'jpeg' AND strlen($msg_erro) == 0){
						$msg_erro = "Tipo de arquivo inválido, somente extensões 'JPG ou JPEG'";
					}
				}
				
				//UPLOAD
				if(strlen($msg_erro) == 0){
					$sql = "SELECT sua_os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica;";
					$res = pg_exec($con,$sql);
					$sua_os = pg_result($res,0,sua_os);
					
					$dir = "laudo/$login_fabrica/imagens/";
					$file = $laudo.".".strtolower(pathinfo($imagem,PATHINFO_EXTENSION));
					
					if(!file_exists($dir))
						mkdir("$dir", 777);
	
					if(move_uploaded_file($_FILES['imagem']['tmp_name'], $dir.$file)){
						reduz_imagem("$file",480,480,"laudo/$login_fabrica/imagens/");
						$res = pg_exec($con,"COMMIT TRANSACTION");
						header("Location: $PHP_SELF?os=$os");
					}else{
						if($btn_acao == "Alterar" and strlen($imagem) == 0){
							$res = pg_exec($con,"COMMIT TRANSACTION");
							header("Location: $PHP_SELF?os=$os");
						}else
							$res = pg_exec($con,"ROLLBACK TRANSACTION");
					}
				}else{
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}

	$layout_menu = 'os';
	include "cabecalho.php";
	?>
	<script type='text/javascript'>
	
	function imprmiLaudo(laudo){
		//alert(os);
		window.open('os_laudo_impressao.php?laudo='+laudo,'laudo_impressao');
		window.location.reload();
	}
	
	function cancelaLaudo(os, laudo){
		 window.location.href = '<?php echo $PHP_SELF;?>?os='+os+'&laudo='+laudo+'&acao=Cancelar';
	}
	
	function alteraLaudo(os, laudo){
		 window.location.href = '<?php echo $PHP_SELF;?>?os='+os+'&laudo='+laudo;
	}
	</script>
	<style type="text/css">
		.Titulo {
			text-align: center;
			font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-weight: bold;
			color: #FFFFFF;
			background-color: #596D9B;
		}
		.Conteudo {
			font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-weight: normal;
		}
		.titulo_tabela{
			background-color:#596d9b;
			font: bold 14px "Arial";
			padding: 10px auto;
			color:#FFFFFF;
			text-align:center;
		}


		.titulo_coluna{
			background-color:#596d9b;
			font: bold 11px "Arial";
			color:#FFFFFF;
			text-align:center;
		}


		.msg_erro{
			background-color:#FF0000;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
			padding: 5px; 
			width: 690px;
			margin: 0 auto;
		}

		.sucesso{
			background-color:#008000;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}

		.formulario{
			background-color:#D9E2EF;
			font:11px Arial;
			text-align:left;
		}

		.formulario td{
			padding: 0 100px;
		}

		.subtitulo{

			background-color: #7092BE;
			font:bold 14px Arial;
			color: #FFFFFF;
			text-align:center;
		}

		table.tabela tr td{
			font-family: verdana;
			font-size: 11px;
			border-collapse: collapse;
			border:1px solid #596d9b;

		}

		.texto_avulso{
			font: 14px Arial; color: rgb(89, 109, 155);
			background-color: #d9e2ef;
			text-align: center;
			border-collapse: collapse;
			border:1px solid #596d9b;
			padding: 10px 0; 
			width: 700px;
			margin: 0 auto;
		}

		.informacao{
			font: 14px Arial; color:rgb(89, 109, 155);
			background-color: #C7FBB5;
			text-align: center;
			width:700px;
			margin: 0 auto;
			border-collapse: collapse;
			border:1px solid #596d9b;
		}

		.espaco{
			padding-left:80px; 
			width: 220px;
		}
	</style>
	<br /><br />
	<?php

	if(strlen($msg_erro) != 0)
		echo "<div class='msg_erro'>$msg_erro</div>";

	//consulta laudo para esta OS
	$sql = "SELECT laudo FROM tbl_laudo WHERE os = $os AND data_cancelado IS NULL;";
	$res = pg_exec ($con,$sql);
	if ((pg_numrows ($res) == 0 or strlen($laudo) != 0) and $acao != "Cancelar") {
		if(strlen($laudo) != 0 and strlen($msg_erro) == 0){
				$sql = "SELECT texto, tipo_laudo FROM tbl_laudo WHERE laudo = $laudo AND (data_impressao IS NULL or data_cancelado IS NULL);";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) != 0){
					$texto = pg_result($res,0,texto);
					$tipo_laudo = pg_result($res,0,tipo_laudo);
				}else{
					header("Location: $PHP_SELF?os=$os");
					exit;
				}
		}
		echo "<form name='frm_laudo' method='post' action='$PHP_SELF' enctype='multipart/form-data' >";
			echo "<table cellspacing='2' cellpadding='3' border='0' align='center' width='500px' class='formulario'>";
				echo "<tr>";
					echo "<td class='titulo_tabela'>Laudo Técnico</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td><br />";
						echo "Tipo de Laudo<br>";
						echo "<select name='tipo_laudo' style='width: 500px'>";
							function verifica_option($v1, $v2){
								if($v1 == $v2)
									return " selected style='color: #F00'";
							}
							$sql = "SELECT tipo_laudo, descricao FROM tbl_tipo_laudo WHERE fabrica = $login_fabrica AND ativo IS TRUE;";
							$res = pg_exec ($con,$sql);
							if (pg_numrows($res) > 0){
								while($dados = pg_fetch_array($res))
									echo "<option value='$dados[0]' ".verifica_option($tipo_laudo,$dados[0]).">$dados[1]</option>";
							}else
								echo "<option value='0' >Nenhum tipo de laudo encontrado!</option>";
						echo "</selected>";
					echo "</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td>";
						echo "Levantamento Técnico<br>";
						echo "<textarea name='texto' style='width: 500px; height: 100px'>$texto</textarea>";
					echo "</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td>";
						if(strlen($laudo) == 0)
							echo "Imagem <i>(somente JPG ou JPEG)</i><br>";
						else
							echo "Imagem <i>(somente JPG ou JPEG) (Preencher somente se for alterar a imagem atual)</i><br>";
						echo "<input type='file' name='imagem' style='width: 500px' />";
					echo "</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='center'>";
						echo "<input type='hidden' name='os' value='$os' />";
						if(strlen($laudo) == 0){
							echo "<br /><input type='submit' name='btn_acao' value=' Gravar ' /><br /><br />";
						}else{
							echo "<input type='hidden' name='laudo' value='$laudo' />";
							echo "<br /><input type='submit' name='btn_acao' value=' Alterar ' /><br /><br />";
						}
					echo "</td>";
				echo "</tr>";

			echo "</table>";
		echo "</form>";
		
	}elseif($acao == "Cancelar"){
		$sql = "SELECT texto FROM tbl_laudo WHERE laudo = $laudo AND data_cancelado IS NULL;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) == 0){
			header("Location: $PHP_SELF?os=$os");
			exit;
		}
		echo "<form name='frm_laudo' method='post' action='$PHP_SELF'>";
			echo "<table cellspacing='1' cellpadding='3' border='0' align='center' width='500px' class='formulario'>";
				echo "<tr>";
					echo "<td class='titulo_tabela'>Laudo Técnico</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td><br />";
						echo "Motivo Cancelamento<br>";
						echo "<textarea name='cancelado_justificativa' style='width: 500px'>$cancelado_justificativa</textarea>";
					echo "</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='center'>";
						echo "<input type='hidden' name='os' value='$os' />";
							echo "<input type='hidden' name='laudo' value='$laudo' />";
							echo "<input type='hidden' name='acao' value='Cancelar' />";
							echo "<input type='hidden' name='btn_acao' value='Cancelar' />";
							echo "<br /><input type='submit' value=' Cancelar Laudo ' /><br /><br />";
					echo "</td>";
				echo "</tr>";
			echo "</table>";
		echo "</form>";
	}else{
		echo "<div class='texto_avulso'>Já existe laudo emitido para esta OS, caso precise emitir um novo laudo, cancele o anterior</div>";
	}

	$sql = "SELECT 
			laudo												,
			DATE(data_digitacao) AS digitacao		,
			DATE(data_impressao) AS imprensao		, 
			DATE(data_cancelado) AS cancelamento
		FROM 
			tbl_laudo
		WHERE 
			os = $os 
		ORDER BY 
			data_digitacao DESC ;";
	$res = pg_exec ($con,$sql);
	$total = pg_numrows ($res);
	
	if ($total > 0) {
		function DataBR($data){
			$data = explode('-',$data);
			return $data[2].'/'.$data[1].'/'.$data[0];
		}
		
		echo "<table cellspacing='1' cellpadding='3' border='0' align='center' class='tabela' style='width: 700px; margin: 20px auto;'>";
			echo "<tr>";
				echo "<td class='titulo_tabela' colspan='6'>Relatório de Laudos</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td class='titulo_coluna'>Data Digitação</td>";
				echo "<td class='titulo_coluna'>Data Impressão</td>";
				echo "<td class='titulo_coluna'>Data Cancelamento</td>";
				echo "<td class='titulo_coluna' colspan='3'>Ações</td>";
			echo "</tr>";

			for($i = 0;$i < $total; $i++){
				$digitacao = pg_result($res,$i,digitacao);
				$imprensao = pg_result($res,$i,imprensao);
				$cancelamento = pg_result($res,$i,cancelamento);
				$laudo = pg_result($res,$i,laudo);
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr bgcolor='$cor'>";
					echo "<td align='center'>"; 
						if(strlen($digitacao) > 6) echo DataBR($digitacao); else echo "&nbsp;";
					echo "</td>"; 
					echo "<td align='center'>"; 
						if(strlen($imprensao) > 6) echo DataBR($imprensao); else echo "&nbsp;";
					echo "</td>";
					echo "<td align='center'>"; 
						if(strlen($cancelamento) > 6) echo DataBR($cancelamento); else echo "&nbsp;";
					echo "</td>";
					echo "<td align='center'>";
							echo "<a href='javascript:void(0);' onclick='javascript: imprmiLaudo($laudo);' title='Imprmir' target='_blank'><input type='button' value='Imprimir'></a>";
					echo "</td>";
					echo "<td align='center'>";
						if(strlen($imprensao) == 0 and strlen($cancelamento) == 0)
							echo "<a href='#' onclick='javascript: alteraLaudo($os,$laudo);' title='Alterar'><input type='button' value='Alterar'></a>";
						else   
							echo "&nbsp;";
					echo "</td>";
					echo "<td align='center'>";
						if(strlen($cancelamento) == 0)
							echo "<a href='javascript:void(0);' onclick='javascript: cancelaLaudo($os,$laudo);' title='Cancelar'><input type='button' value='Cancelar'></a>";
						else
							echo "&nbsp;";
					echo "</td>";
				echo "</tr>";
			}
		echo "</table>";
	}
	include "rodape.php";
?>
