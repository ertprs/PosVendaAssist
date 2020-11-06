<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($_GET["os"])>0) $sua_os = $_GET["os"];
else                      $sua_os = $_POST["os"];

//Validando se a OS é do posto logado no sistema
$sql = "SELECT tbl_os.os, tbl_os.sua_os
		FROM tbl_os
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.posto     = $login_posto
		AND (tbl_os.os = $sua_os OR tbl_os.sua_os = '$sua_os')";
$res = pg_query($con, $sql);

if(pg_num_rows($res) == 0) {
	echo "OS não localizada";
	die;
}

$os = pg_fetch_result($res, 0, os);

if ($login_fabrica == 45 and !isset($_FILES['foto'])) {
	$img_nf = "nf_digitalizada/$os.jpg";
	$img_nf2= "nf_digitalizada/$os-2.jpg";
	if (file_exists($img_nf)) {
		$msg_erro = (file_exists($img_nf2)) ? "<b>Esta OS já tem duas imagens anexadas!</b>":"Já existe uma imagem para esta OS. Pode anexar mais uma.";
	} else {
	    if (file_exists($img_nf2)) $msg_erro = "Já existe uma imagem para esta OS. Pode anexar mais uma.";
	}
}

$arquivo = isset($_FILES["foto"]) ? $_FILES["foto"] : false;

if ($arquivo)
{
	# HD 158465
	$max_upload_size = 2097152; // Tamanho máximo do arquivo (em bytes)
	$tamMB = number_format($max_upload_size/1048576, 2, '.', ',');

	# Verifica o mime-type do arquivo
	if(!preg_match("/\/(pjpeg|jpeg|jpg)/", $arquivo["type"])){
		$msg_erro = "<p>O arquivo deve estar no formato JPG.</p>";
	}
	if ($arquivo["size"] > $max_upload_size) {
		$msg_erro.= "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
	}
	if (!function_exists('reduz_imagem')) {
		function reduz_imagem($img, $max_x, $max_y, $nome_foto) {

			list($width, $height) = getimagesize($img);
			$original_x = $width;
			$original_y = $height;

			if($original_x > $original_y) {
			   $porcentagem = (100 * $max_x) / $original_x;
			} else {
			   $porcentagem = (100 * $max_y) / $original_y;
			}

			$tamanho_x = $original_x * ($porcentagem / 100);
			$tamanho_y = $original_y * ($porcentagem / 100);

			$image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
			$image   = @imagecreatefromjpeg($img);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);
			imagejpeg($image_p, $nome_foto, 65);
		return 1;
		}
	}

	if(strlen($msg_erro)==0){
		$img_temp = $arquivo["tmp_name"];
/*
	HD 171045 - Customizar para a NKS: aceitar 2 imagens, a 2ª com sufixo '-2'
				Também tem que criar um "thumbnail" de 150px máx. com o mesmo nome e sufixo _thumb / thumb-2
*/
		$imagem_nota = "nf_digitalizada/$os.jpg";
		$imagem_nota2= "nf_digitalizada/$os-2.jpg"; //  Só NKS
		$arquivo_imagem  = "";
		if ($login_fabrica==45) {
			if (file_exists($imagem_nota)) {
				$msg_erro = (file_exists($imagem_nota2)) ? "Esta OS já tem duas imagens anexadas.":"Já existe uma imagem para esta OS. Pode anexar mais uma.";
			} else {
			    if (file_exists($imagem_nota2)) $msg_erro = "Já existe uma imagem para esta OS. Pode anexar mais uma.";
			}
		} else {
			if (file_exists($imagem_nota)) $msg_erro = "Já existe uma imagem para esta OS.";
		}

/*
		Se tem imagem '1' e ainda não tem imagem 2... "renomeamos" a imagem como "$imagem-2"
		Para usar o mesmo código, simplesmente mudamos o valor da variável da imagem '1'...
		Desta maneira, se o admin excluiu a imagem '1' e o posto grava uma imagem nova, não vai gravar em cima da -2
		e sim vai "reanexar" como a primeira...
*/
		if (file_exists($imagem_nota) and $login_fabrica == 45 and !file_exists($imagem_nota2)) $imagem_nota = $imagem_nota2;

		list($width, $height) = getimagesize($img_temp);
		if (($width * $height) > 4000000) {
		    $msg_erro.= "Tamanho em pixels do arquivo muito grande ($width x $height).<br>Por favor, reduza ou recorte a imagem antes de enviá-la de novo.<br>Obrigado.";
		}

		if(strlen($msg_erro)==0 or ($login_fabrica==45 and $msg_erro == "Já existe uma imagem para esta OS. Pode anexar mais uma.")) {
			$reduziu = reduz_imagem($img_temp, 800, 600, $imagem_nota);
			if ($reduziu) {
			    if ($login_fabrica == 45) {
				//  Redimensiona e copia a imagem, cria a imagem reduzida para pré-visualizar
	                $imagem_thumb = str_replace($os, "$os"."_thumb", $imagem_nota);
					reduz_imagem($img_temp, 150, 135, "$imagem_thumb");
				}
			}
			if (!file_exists($imagem_nota)) {
			    $msg_erro .= " Erro ao anexar o arquivo. Tente novamente, ou contate com <a href='mailto:helpdesk@telecontrol.com.br'>nosso Suporte</a>.";
				$imagem_nota = "";
			} else {
				$msg_erro = "Arquivo enviado com sucesso! ".$msg_erro; // Por causa da 1ª foto da NKS...
				$arquivo_imagem = $imagem_nota;
			}
		}
	}
}

include "cabecalho.php";
?>

<style>
	.titulo {
		font-family: Arial;
		font-size: 9pt;
		text-align: center;
		font-weight: bold;
		color: #FFFFFF;
		background: #408BF2;
	}
	.titulo2 {
		font-family: Arial;
		font-size: 12pt;
		text-align: center;
		font-weight: bold;
		color: #FFFFFF;
		background: #408BF2;
	}

	.conteudo {
		font-family: Arial;
		FONT-SIZE: 8pt;
		text-align: left;
	}

	.mesano {
		font-family: Arial;
		FONT-SIZE: 11pt;
	}

	.Tabela{
		border:		1px solid #485989;
		font-family:Arial;
		font-size:	9pt;
		text-align:	left;
	}
	img{
		border: 0px;
	}
	.caixa{
		border:1px solid #666;
		font-family: courier;
	}

	body {
		margin: 0px;
	}

	.msg {
		color: #f22;
		text-align: center;
        background-color: #fcc;
	}
</style>
<br />
<form name='frm_relatorio' method='post' enctype="multipart/form-data">
	<table width='700' class='Tabela' align = 'center' cellpadding='5' cellspacing='0' border='0' >
		<?if (strlen($msg_erro) > 0) {?>
		<tr>
			<td class="msg">
				<?=$msg_erro?>
			</td>
		</tr>
		<?}?>
		<tr>
			<td align='center'>
				<h1>Selecione uma imagem para a OS <b><?=$sua_os?></b></h1>
				<h3 style='text-align:center'>(formato <b>jpg</b>, tamanho máximo <?php echo $tamMB; ?>Mb ou <span title='3 Megapíxels'>3 Mpx</span>):</h3>
				<input type='file' name='foto'>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<input type='submit' name='btn_acao' value='Enviar Arquivo'>
				<input type='hidden' name='os' value='<?=$os?>'>
			</td>
		</tr>
	</table>
</form>

<div align="center">
<?
if ($arquivo_imagem != "")
{
	echo "<p style='text-align:center'>Imagem anexada:</p>\n<img src='$arquivo_imagem'>\n";
}
?>
</div>