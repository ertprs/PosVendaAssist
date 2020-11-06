<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
require( 'class_resize.php' );

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<?

$mail         = trim($_GET['email']);

if(strlen($mail) > 0){
	$sql = "SELECT DISTINCT tbl_posto.posto        ,
					tbl_posto.nome         ,
					tbl_posto.cnpj
				FROM tbl_posto 
			WHERE email = '$mail' order by posto desc limit 1;";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		$posto      = pg_result($res,0,posto);
		$nome_posto  = pg_result($res,0,nome);
		$cnpj        = pg_result($res,0,cnpj);

		$sql = "SELECT tbl_fabrica.nome FROM tbl_fabrica JOIN tbl_posto_fabrica using(fabrica) WHERE posto = '$posto'; ";
		$res = pg_exec($con,$sql);
		for($i = 0; $i < pg_numrows($res); $i++){
			$fabricas .= pg_result($res,$i,nome) . ", ";
		}
		$fabricas = substr($fabricas, 0 , strlen($fabricas)-2);
		$fabricas .= ".";
	}
}

if (getenv("REQUEST_METHOD") == "POST"){

	$fabricantes  = trim($_POST['fabricantes']);
	$descricao    = trim($_POST['descricao']);
	
	set_time_limit(0);

	$nome      = "Telecontrol";
	$email     = "fernando@telecontrol.com.br";
	$mensagem  .= "<b style='font-size: 14px'><u>Auto cadastramento</b></u>\n\n<br><br>";
	$mensagem  .= "<b>Posto:</b> $nome_posto\n<br>";
	$mensagem  .= "<b>CNPJ:</b> $cnpj\n<br>";
	$mensagem  .= "<b>E-mail:</b> $mail\n<br>";
	$mensagem  .= "<b>Fabricas Telecontrol:</b> $fabricas\n<br>";
	$mensagem  .= "<b>Outros fabricantes:</b> $fabricantes\n<br>";
	$mensagem  .= "<b>Descricao:</b> $descricao\n\n<br><br>";
	$mensagem  .= "____________________________________________<br>";
	$mensagem  .= "Telecontrol Networking<br>";
	$mensagem  .= "www.telecontrol.com.br";
	$assunto   = "Auto Cadastramento";
	$anexos    = 0;
	$boundary = "XYZ-" . date("dmYis") . "-ZYX";

	$mens  = "--$boundary\n";    
	$mens .= "Content-Transfer-Encoding: 8bits\n";
	$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
	$mens .= "$mensagem\n";
	$mens .= "--$boundary\n";
	
	if(strlen($posto) > 0){
		$sql3 = "UPDATE tbl_posto_extra set fabricantes = '$fabricantes', descricao = '$descricao' WHERE posto = '$posto'; ";
		$res3 = pg_exec($con,$sql3);
	}

		$config["tamanho"] = 4096000;
		
		for($i = 1; $i < 4; $i++){
			$arquivo                = isset($_FILES["arquivo$i"]) ? $_FILES["arquivo$i"] : FALSE;
			// Formulário postado... executa as ações 
			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

				$xposto = $posto;
	//			echo "arquivo $arquivo";

				// Verifica o MIME-TYPE do arquivo
				if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
					$msg_erro = "Arquivo em formato inválido!";
				} else {
					// Verifica tamanho do arquivo 
					if ($arquivo["size"] > $config["tamanho"]) 
						$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 4MB. Envie outro arquivo.";
				}

				if (strlen($msg_erro) == 0) {
					// Pega extensão do arquivo
					preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
					$aux_extensao = "'".$ext[1]."'";
					$xposto .= "_" .$i;
					// Gera um nome único para a imagem
					$nome_anexo = $xposto;

					// Caminho de onde a imagem ficará + extensao
					$imagem_dir = "credenciamento/fotos/".strtolower($nome_anexo);

					// Exclui anteriores, qquer extensao
					//@unlink($imagem_dir);

					// Faz o upload da imagem
					if (strlen($msg_erro) == 0) {
						//move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
//						if (copy($arquivo["tmp_name"], $imagem_dir)) {


						// resize $_FILES[ 'myUploadedFile' ] widht
						$thumbail = new resize( "arquivo$i", 600, 400 );

						// save the resized image to "./TEMP.EXT"
						$thumbail -> saveTo("$nome_anexo.".$thumbail -> type,"credenciamento/fotos/" ); 
					}
				}
			}
		}

	if(strlen($nome) == 0) $from = $nome;
	else $from = $mail;

	$headers  = "MIME-Version: 1.0\n";
	$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
	$headers .= "From: \"Telecontrol\" <telecontrol@telecontrol.com.br>\r\n";
	$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";

	if(mail($email, $assunto, $mens, $headers)){
		echo "<br><br><br><br>";
		if(strlen($msg_erro) == 0){
			echo "<table width='600' align='center' style='font-family: verdana; font-size: 12px'>
					<tr>
						<td style='font-size: 16' align='center'><b>Telecontrol</b></td>
					</tr>
					<tr>
						<td>
							Em poucos dias você receberá retorno do fabricante e contrato de prestação de serviços.
						<td>
					</tr>
					<tr>
						<td align='center'><a href=\"javascript: this.close();\">Fechar</a></td>
					</tr>
				</table>"; 
		}else{
			echo "<p align='center'>$msg_erro</p>";
		}
		exit;
	} else {
		echo "Nao foi possível enviar o email";
	}
}
?>

<style>

input.botao {
	background:#596D9B;
	color:#FFFFFF;
	border:1px solid #FFFFFF;
	font-weight: bold;
}

.add {
	position:absolute;
	cursor:pointer;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #f5f5f5
}

</style>

<script type="text/javascript">
function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
	}
}
</script>


<title>AUTO CADASTRAMENTO</title>


<form action='<? $PHP_SELF ?>' method='post' enctype='multipart/form-data' >
<br><br>
<TABLE align='center' border='0' width='600' cellpadding='3' cellspacing='2' style='font-family: verdana; font-size: 10px'>
<tr >
	<td class='menu_top' height='30' colspan='2' align='center' style='font-size: 14px; '>REDE DE ASSISTÊNCIA AUTORIZADA</FONT></td>
</tr>
<tr class='table_line'>
	<td align='right'><b>Além destes fabricantes:</b></td>
	<td>
		<? echo $fabricas; ?>
	</td>
</tr>
<tr class='table_line'>
	<td align='right'><b>Quais outros fabricantes sua loja atende:</b></td>
	<td><INPUT TYPE="text" NAME="fabricantes" size='50'></td>
</tr>
<tr class='table_line'>
	<td align='center' height='20' colspan='2' onClick="MostraEsconde('conteudo')" style='cursor:pointer; cursor:hand;' ><b><u>Clique aqui e envie 3 fotos digitais da sua loja (fachada, recepção e laboratório)</u><b></td>
</tr>
<tr class='table_line'>
	<td colspan='2' align='center'>
		<div id='conteudo' style='display: none;' ><br>
			<input type='file' name='arquivo1' size='50' /><br> 
			<input type='file' name='arquivo2' size='50' /><br>
			<input type='file' name='arquivo3' size='50' /><br>
		</div>
	</td>
</tr>
<tr class='table_line'>
	<td align='right'><b>Caso não tenha máquina digital, descreva a sua loja (fachada, recepção e laboratório)</b></td>
	<td ><TEXTAREA NAME="descricao" ROWS="3" COLS="30"></TEXTAREA></td>
</tr>
<tr class='table_line'>
	<td colspan='2' align='center'><input type='submit' name='Submit' value='Cadastrar' class='botao'></td>
</tr>
<tr class='table_line'>
	<td colspan='2' style='color: #FF3333; font-size: 10px;' align='center'><br><b>*ATENÇÃO: A falta de fotos implicará na visita de um inspetor da fábrica!</b></td>
</tr>
</TABLE>
</form>