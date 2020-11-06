<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$email_origem  = "fernando@telecontrol.com.br";
$email_destino = "fernando@telecontrol.com.br";

$corpo.="<br>\n";
$corpo.="<br>_______________________________________________\n";
$corpo.="<br><br>Telecontrol\n";
$corpo.="<br>www.telecontrol.com.br\n";

$body_top = "--Message-Boundary\n";
$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
$body_top .= "Content-transfer-encoding: 7BIT\n";
$body_top .= "Content-description: Mail message body\n\n";
@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ); 


?>