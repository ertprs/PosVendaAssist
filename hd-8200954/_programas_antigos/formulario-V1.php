<?
//formatação para o cadastro depois de clicar no botão cadastrar
include "dbconfig.php";
include "includes/dbconnect-inc.php";

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
			WHERE email = '$mail' limit 1;";
	$res = pg_exec($con,$sql);
//echo "<br>$sql";
	if(pg_numrows($res) > 0){
		$posto       = pg_result($res,0,posto);
		$nome_posto  = pg_result($res,0,nome);
		$cnpj        = pg_result($res,0,cnpj);

	//	echo "<br>$posto - $nome - $cnpj";

		$sql = "SELECT tbl_fabrica.nome FROM tbl_fabrica JOIN tbl_posto_fabrica using(fabrica) WHERE posto = '$posto'; ";
		$res = pg_exec($con,$sql);
		for($i = 0; $i < pg_numrows($res); $i++){
			$fabricas .= pg_result($res,$i,nome) . ", ";
		}
		$fabricas = substr($fabricas, 0 , strlen($fabricas)-2);
		$fabricas .= ".";
	}
//	echo "<br>$fabricas";
//	echo "<br>$sql";
}



if (getenv("REQUEST_METHOD") == "POST"){

	$fabricantes  = trim($_POST['fabricantes']);
	$descricao    = trim($_POST['descricao']);
	
	set_time_limit(0);

	$nome      = "Telecontrol";
	$email     = "tecnico@telecontrol.com.br";
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

	for($i = 0; $i < count($_FILES["file"]["name"]); $i++){
		if(is_uploaded_file($_FILES["file"]["tmp_name"][$i])){
			$fp = fopen($_FILES["file"]["tmp_name"][$i], "rb");
			$anexo = chunk_split(base64_encode(fread($fp, $_FILES["file"]["size"][$i])));        
			fclose($fp);
			$mens .= "Content-Type: ".$_FILES["file"]["type"][$i]."\n name=\"".$_FILES["file"]["name"][$i]."\"\n";
			$mens .= "Content-Disposition: attachment; filename=\"".$_FILES["file"]["name"][$i]."\"\n";        
			$mens .= "Content-transfer-encoding:base64\n\n";
			$mens .= $anexo."\n";
			
			if($i + 1 == count($_FILES["file"]["name"]))
				$mens.= "--$boundary--";
			else
				$mens.= "--$boundary\n";
			
			if($_FILES["file"]['error'][$i] == 0) {
				$anexos++;
			}
		}
	}
	
	if(strlen($nome) == 0) $from = $nome;
	else $from = $mail;

	$headers  = "MIME-Version: 1.0\n";
	$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
	$headers .= "From: \"Telecontrol\" <tecnico@telecontrol.com.br>\r\n";
	$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";

/*	if(mail($email, $assunto, $mens, $headers)){
		echo "<br><br><br><br>";
		echo "<table width='600' align='center' style='font-family: verdana; font-size: 12px'>
				<tr>
					<td style='font-size: 16' align='center'><b>HBTech</b></td>
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

		exit;
	} else {
		echo "Nao foi possível enviar o email";
	}
*/
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
	<td class='menu_top' height='30' colspan='2' align='center' style='font-size: 14px; '>REDE DE ASSISTÊNCIA AUTORIZADA <FONT SIZE="3">HBTech</FONT></td>
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
			<input type='file' name='file[]' size='50' /><br> 
			<input type='file' name='file[]' size='50' /><br>
			<input type='file' name='file[]' size='50' /><br>
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