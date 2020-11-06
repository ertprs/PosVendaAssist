<?

/*$corpo .= "<p>Segue abaixo link para acesso ao chamado:</p><p align=justify><a href='http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter'>http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter</a>";
// HD 112313 (augusto) - Problema no cabeçalho do email, removidas partes com problema;
$body_top  = "Content-type: text/html; charset=iso-8859-1 \n";
$body_top .= "From: {gustavo@telecontrol.com.br} \n";

if ( @mail('gustavo@telecontrol.com.br', stripslashes($assunto), $corpo, $body_top ) ){
	$msg = "<br>Foi enviado um email para: gustavo@telecontrol.com.br <br>";
}else{
	$msg = "Não foi possível enviar o email. ";
}

echo $msg;*/
$sua_os = "0671495";

echo $sua_os . "<br>";

$sua_os         = substr($sua_os, strlen($sua_os)-5, strlen($sua_os));

echo $sua_os . "<br>";

?>