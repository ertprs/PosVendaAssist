<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

/*
echo "posto_fabrica = " . $_COOKIE['cook_posto_fabrica'];
echo "<br>";
echo "fabrica = " . $_COOKIE['cook_fabrica'];
echo "<br>";
echo "posto = " . $_COOKIE['cook_posto'];
echo "<br>";
echo "login_unico = " . $_COOKIE['cook_login_unico'];
echo "<br>";
echo "Ta aqui 3";
#exit;
*/

include 'autentica_usuario.php';


$liberacao = $_COOKIE ['liberacao'];

if (strlen ($liberacao) == 0) {
	echo "Entrada de Senha a ser definida";
}


$btn_acao = $_POST ['btn_acao'];

if (strlen ($btn_acao) > 0) {
	$retorno = $_POST ['retorno'];

	$arquivo = date ('Ymd-H-i-s');
	$fp = fopen ("/tmp/telecontrol/CB_$arquivo.RET","w");
	fwrite ($fp,"$retorno");
	fclose($fp);

	system ("/www/cgi-bin/telecontrol/retorno_bradesco.pl CB_$arquivo.RET", $ret_system);
	print_r($ret_system);

}


?>

<html>
<head>
<title>Baixa Automática do Banco</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Baixa Automática do Banco</h1></center>

<p>

<center>
<form method='post' action='<? echo $PHP_SELF ?>' name='frm_baixa'>
Copie e cole aqui o arquivo de retorno do banco.
<br>
<textarea name='retorno' rows='15' cols='100'></textarea>
<p>
<input type='submit' name='btn_acao' value='Processar Retorno'>

</form>
</center>


<? #include "rodape.php"; ?>

</body>
</html>
