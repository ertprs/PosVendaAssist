<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "funcoes.php";
	$admin_privilegios="gerencia";
	include 'autentica_admin.php';
	function reduz_imagem($img, $max_x, $max_y, $nome_foto) {
		list($original_x, $original_y) = getimagesize($img);	//pega o tamanho da imagem

		// se a largura for maior que altura
		if($original_x > $original_y) {
		   $porcentagem = (100 * $max_x) / $original_x;
		}
		else {
		   $porcentagem = (100 * $max_y) / $original_y;
		}

		$tamanho_x	= $original_x * ($porcentagem / 100);
		$tamanho_y	= $original_y * ($porcentagem / 100);
		$image_p	= imagecreatetruecolor($tamanho_x, $tamanho_y);
		$image		= imagecreatefromjpeg($img);

		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
		imagejpeg($image_p, $nome_foto, 65);
	}

	$residuo_solido = $_GET['residuo_solido'];

	if($_POST['btn_acao']){
		$data_de    =  $_POST['data_de'];
		$data_ate   =  $_POST['data_ate'];
		$comprovante_devolucao = $_POST['comprovante_devolucao'];
		$residuo_solido = $_POST['residuo_solido'];

	if(!empty($data_de) AND !empty($data_ate)){

		list($di, $mi, $yi) = explode("/", $data_de);
		if(!checkdate($mi,$di,$yi)){ 
			$msg_erro = "Data Inválida";
		} else {
			$aux_data_de = "$yi-$mi-$di";
		}

		list($df, $mf, $yf) = explode("/", $data_ate);
		if(!checkdate($mf,$df,$yf)){ 
			$msg_erro .= "Data Inválida";
		} else {
			$aux_data_ate = "$yf-$mf-$df";
		}

		if(strlen($msg_erro)==0){
		    if(strtotime($aux_data_ate) < strtotime($aux_data_de)){
		        $msg_erro .= "Data Inválida<br>";
		    }
		}
	} else {
		$msg_erro .= "Data de Devolução é obrigatória<br>";
	} 
	
	if(empty($comprovante_devolucao)){
		$msg_erro .= "Número do comprovante de devolução é obrigatório<br>";
	}

	if(empty($msg_erro)){
			$Fotos = $_FILES['arquivos'];
			$Nome    = $Fotos['name'];
			$Tamanho = $Fotos['size'];
			$Tipo    = $Fotos['type'];
			$Tmpname = $Fotos['tmp_name'];
			if (strlen($Nome) > 0){
				$Destino = "../nf_bateria/correio/";

				if(strlen($Nome)>0){
					if(preg_match('/(jpeg|jpg|pdf)$/i', $Tipo)){
						//echo $Tmpname;
						if(!is_uploaded_file($Tmpname)){
							$msg_erro .= "Não foi possível efetuar o upload.<br>";
							break;
						}

						$tmp = explode(".",$Nome);
						$ext = $tmp[count($tmp)-1];

						if (strlen($Nome)==0){
							$ext = $Nome;
						}

						$ext = strtolower($ext);

						$nome_foto  = "$residuo_solido.$ext";

						$Caminho_foto  = $Destino . $nome_foto;

						if(strtolower($ext)=="pdf"){
							if (file_exists($Caminho_foto)) { //Imagem anterior!
								if (!unlink($Caminho_foto)) {
									$msg_erro .= "Não foi possível excluir o arquivo $Nome".".pdf!<br>\n";
								} else {
									$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_foto);
								}
							} else {
									$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_foto);
								}
							if (file_exists($Caminho_foto)) chmod($Caminho_foto, 0666); //Nova imagem!
								#copy($Tmpname, $Caminho_thumb); Não carregar o Thumb para pdf, senão algum lugar nao funcionar
							}else{
								#Apaga a imagem anterior
								if(file_exists($Caminho_foto)){
									unlink($Caminho_foto);
								}

								// Apaga a imagem anterior, mesmo se a extensão está em maiúsculo
								if(file_exists(strtoupper($Caminho_foto))){
									unlink(strtoupper($Caminho_foto));
								}

								reduz_imagem($Tmpname, 800, 600, $Caminho_foto);
								$copiou_arquivo = file_exists($Caminho_foto); // Nova imagem!
							}

							//if ($testIMG==1) unlink ($Caminho_foto); // Exclui a imagem recém inserida para testes
								if ($copiou_arquivo and !file_exists($Caminho_foto)) { // Confere se REALMENTE existe o arquivo "anexado"
									$copiou_arquivo = false;
								}
								if  (!$copiou_arquivo) $msg_erro.= ($ext=='pdf') ? 'Documento não anexado.':'Imagem não anexada.';
							}else{
								$msg_erro = "O formato do arquivo $Nome não é permitido!<br>São permitidas apenas imagens JPG ou PDF";
							}

					} else{
						$msg_erro .= "Comprovante de devolução dos correio é obrigatório";
					}
			} else {
				$msg_erro .= "Comprovante de devolução dos correio é obrigatório";
			}
		}

		if(empty($msg_erro)){
			$sql = "UPDATE tbl_residuo_solido SET
							numero_devolucao       = $comprovante_devolucao,
							data_devolucao_inicial = '$aux_data_de',
							data_devolucao_final   = '$aux_data_ate',
							admin_aprova           = $login_admin,
							data_aprova            = current_timestamp
						WHERE residuo_solido = $residuo_solido";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(empty($msg_erro)){

			$sql = "SELECT   tbl_posto_fabrica.contato_email AS posto_email, tbl_admin.email AS admin_email, tbl_residuo_solido.protocolo, tbl_residuo_solido.posto
						FROM tbl_residuo_solido 
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_residuo_solido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
						JOIN tbl_admin ON tbl_admin.admin = tbl_residuo_solido.admin_aprova
						WHERE tbl_residuo_solido.residuo_solido = $residuo_solido
						AND tbl_residuo_solido.fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

			if(pg_numrows($res) > 0){

				$email_cadastros	 = pg_fetch_result($res,0,posto_email);
				$admin_email		 = pg_fetch_result($res,0,admin_email);
				$protocolo			 = pg_fetch_result($res,0,protocolo);
				$posto				 = pg_fetch_result($res,0,posto);

				$sql = "INSERT INTO tbl_comunicado(mensagem,tipo,fabrica,posto,ativo,obrigatorio_site) VALUES('Relatório $protocolo aprovado','Comunicado',$login_fabrica,$posto,true,true)";
				$res = pg_query($con,$sql);
				$erro = pg_errormessage($con);
				$remetente    = $admin_email;
				$destinatario = $email_cadastros ;
				$assunto      = "Devolução de Baterias B&D";
				$mensagem     = "Prezado, <br> o relatório $protocolo foi aprovado precisa imprimir o comprovante de devolução.";
				$headers="Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
				mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
			
				echo "<script> 

						function ocultaMsg(){
							parent.document.getElementById('msg_retorno').style.display = 'none';
						}

						var excluir = parent.document.getElementById('excluir_".$residuo_solido."');
						var aprovar = parent.document.getElementById('aprovar_".$residuo_solido."');
						var reprovar = parent.document.getElementById('reprovar_".$residuo_solido."');

						parent.document.getElementById('div_motivo_".$residuo_solido."').style.display = 'none';
						parent.document.getElementById('msg_retorno').style.display = 'block';
						aprovar.parentNode.removeChild(aprovar);
						reprovar.parentNode.removeChild(reprovar);
						excluir.parentNode.removeChild(excluir);
						setTimeout('ocultaMsg()', 3000);
					</script>";
			}

		}

		
	}
?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.frm {
    background-color: #F0F0F0;
    border-color: #888888;
    border-right: 1px solid #888888;
    border-style: solid;
    border-width: 1px;
    font-family: Verdana;
    font-size: 8pt;
    font-weight: bold;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<?php include "javascript_calendario.php"; ?>
<script src="js/jquery.alphanumeric.js" type="text/javascript"></script>
<script type='text/javascript'>
	$().ready(function(){
		$("#data_de").datePicker({startDate : "01/01/2000"});
		$("#data_de").maskedinput("99/99/9999");
		$("#data_ate").datePicker({startDate : "01/01/2000"});
		$("#data_ate").maskedinput("99/99/9999");

		$("#comprovante_devolucao").numeric();
	});
</script>
<?php
	if(!empty($msg_erro)){
?>
		<table width='690' align='center' class='msg_erro'>
			<tr><td><?php echo $msg_erro; ?></td></tr>
		</table>
<?php
	}
?>

<form name='frm_aprova' method='post' enctype='multipart/form-data' class='formulario'>
	<table width='690' align='center'  class='formulario'style='border:0;'>
		<caption class='titulo_tabela'>Aprovar Devolução</caption>
		<tr>
			<td width='70'>&nbsp;</td>
			<td align='left'>
				Comprovante devolução (PAC) <br> <!-- Colocado maxlength de 14, pois nos testes a Fabíola colocou uma sequência de 15 vezes o número 9, dando erroa de RANGE no banco pois o campo é do tipo INTEGER-->
				<input type='text' name='comprovante_devolucao' id='comprovante_devolucao' size='20' maxlength="30" value="<?php echo $comprovante_devolucao; ?>" class='frm'>
			</td>
			<td align='left'>
				De <br><input class='frm' type='text' name='data_de' id='data_de' size='10' value="<?php echo $data_de; ?>" >
			</td>
			<td align='left'>
				Até <br><input class='frm' type='text' name='data_ate' id='data_ate' size='10' value="<?php echo $data_ate; ?>" >
			</td>
		</tr>
		<tr><td colspan='3'>&nbsp;</td></tr>
		<tr>
			<td width='70'>&nbsp;</td>
			<td colspan='3' align='left'>
				Comprovante Devolução Correios <br>
				<input type='file' size='35' value='Procurar imagem' name='arquivos' />
			</td>
		</tr>
		<tr><td colspan='3'>&nbsp;</td></tr>
		<tr>
			<td colspan='4' align='center'>
				<input type='hidden' name='residuo_solido' value="<?php echo $residuo_solido; ?>">
				<input type='submit' name='btn_acao' value='Gravar'>
			</td>
		</tr>
	</table>
</form>
