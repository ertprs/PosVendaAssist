<?php
//pego os dados enviados pelo formulario
$nome = $_POST["nome"];
$email_from = $_POST["email_from"];
$endereco = $_POST["endereco"];
$numero = $_POST["numero"];
$bairro = $_POST["bairro"];
$complemento = $_POST["complemento"];
$cidade = $_POST["cidade"];
$cargo = $_POST["cargo"];
$area = $_POST["area"];
$salario = $_POST["salario"];
$tel1 = $_POST["tel1"];
$tel2 = $_POST["tel2"];
$email = "comercial@telecontrol.com.br";
$mensagem = $_POST["mensagem"];
$assunto = "Trabalhe Conosco - De: ".$_POST["nome"];
//formato o campo da mensagem
$mensagem = wordwrap( $mensagem, 50, "
", 1);
//valido os emails
$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
if(file_exists($arquivo["tmp_name"]) and !empty($arquivo)){
$fp = fopen($_FILES["arquivo"]["tmp_name"],"rb");
$anexo = fread($fp,filesize($_FILES["arquivo"]["tmp_name"]));
$anexo = base64_encode($anexo);
fclose($fp);
$anexo = chunk_split($anexo);
$boundary = "XYZ-" . date("dmYis") . "-ZYX";
$mens = "--$boundary\n";
$mens .= "Content-Transfer-Encoding: 8bits\n";
$mens .= "Content-Type: text/html; charset=\"UTF-8\"\n\n"; //plain
$mens .= "Nome: $nome"."<br>";
$mens .= "Endereço: $endereco, Num. $numero"."<br>";
$mens .= "Bairro: $bairro, Complemento: $complemento"."<br>";
$mens .= "Cidade: $cidade, Estado: $estado"."<br>";
$mens .= "Telefone 1: $tel1, Telefone 2: $tel2"."<br>";
$mens .= "Email: $email_from"."<br>";
$mens .= "Cargo: $cargo"."<br>";
$mens .= "Área de Atuação: $area"."<br>";
$mens .= "Pretensão Salarial: $salario"."<br><br>";
$mens .= "Objetivos: $mensagem."."<br><br>";
$mens .= "--\n\n";
$mens .= "--$boundary\n";
$mens .= "Content-Type: ".$arquivo["type"]."\n";
$mens .= "Content-Disposition: attachment; filename=\"".$arquivo["name"]."\"\n";
$mens .= "Content-Transfer-Encoding: base64\n\n";
$mens .= "$anexo\n";
$mens .= "--$boundary--\r\n";
$headers = "MIME-Version: 1.0\n";
$headers .= "From: \"$nome\" <$email_from>\r\n";
$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";
$headers .= "$boundary\n";
//envio o email com o anexo
mail($email,$assunto,$mens,$headers);
echo "<script>alert('Obrigado pelo contato. Responderemos em breve.'); location.href='index.php';</script>";
}
?>