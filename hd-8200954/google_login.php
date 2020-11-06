<?
# http://www.telecontrol.com.br/assist/google_login.php?user=google&passwd=asd123jo
if ($_GET['user']=='google' AND $_GET['passwd']=='asd123jo') {
	setcookie ("cook_posto_fabrica"  ,'647453876');
	setcookie ("cook_fabrica"        ,'0');
	setcookie ("cook_posto"          ,'0');
	setcookie ('cook_idioma'         ,"pt-br");
	setcookie ('cook_bloqueio_pedido','t');

	header ("Location: menu_inicial.php");
}
?>
