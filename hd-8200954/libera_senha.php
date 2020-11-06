<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

// recebe
$id                = trim($_GET['id']);
$codigo            = trim($_GET['id2']);				//(codigo do posto em tbl_posto_fabrica)
$fabrica           = trim($_GET['id3']);
$oid_posto_fabrica = trim($_GET['id4']);	// da tabela tbl_posto_fabrica


if (strlen($codigo) == 0 AND strlen($fabrica) == 0 AND strlen($oid_posto_fabrica) == 0){
	header("Location: index.php");
	exit;
}
if($id == md5($codigo)){
	$sql = "UPDATE	tbl_posto_fabrica SET
					login_provisorio = 'f',
					primeiro_acesso  = current_timestamp
			WHERE	oid          = $oid_posto_fabrica
			AND		fabrica      = $fabrica
			AND		codigo_posto = '$codigo'";
	$res = pg_exec ($con,$sql);

	if (strlen(pg_errormessage ($con)) > 0){
		$msg_erro = pg_errormessage($con);
		$mensagem = "Erro ao liberar os dados.<br>Clique novamente no link do seu e-Mail <br>ou <a href='index.php'>clique aqui</a> e faça a liberação diretamente no sistema.";
	}else{
		/*$mensagem = "<p>Prezado Posto Autorizado,<br><br>Seja bem-vindo!</p>".
					"<p>Seu acesso foi liberado com sucesso.</p>".
					"<p><a href='http://www.telecontrol.com.br/'>Clique aqui para acessar nosso <i>site</i>.</a></p>";*/
		header('Location: externos/primeiro_acesso_new.php?mensagem=sucesso');
		exit;
	}
}else{
	$msg_erro = "Erro ao liberar os dados.<br>Clique novamente no link do seu e-Mail <br>ou <a href='index.php'>clique aqui</a> e faça a liberação diretamente no sistema.";
}
$title = "Liberação da senha de acesso";

?>

<html>
<title><? echo $title; ?></title>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color:#596d9b;
	background-color: #ffffff
}

h2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	color:#000000;
	background-color: #ffffff
}

</style>

<body>

<br>

<h2><? echo $title; ?></h2>

<br>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "	<font face='arial, verdana' color='#330000' size='-1'>";
	echo $msg_erro;
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<br>

<TABLE align='center'>
<TR>
	<TD class='line_list'><? echo $mensagem; ?></TD>
</TR>
</TABLE>

</body>
</html>

<?
/*
Tulio diz:
na area de postos... libera_senha.php
este programa recebe 3 parametros...
codigo (codigo do posto em tbl_posto_fabrica) ;
fabrica ;
e OID da tabela tbl_posto_fabrica
vc deve pegar estes 3 dados e dar um
UPDATE tbl_posto_fabrica SET login_provisorio = 'f' where oid = $oid_posto_fabrica and fabrica = $fabrica and codigo_posto = $codigo
pegar estas variaveis via GET

Ricardo diz:
qdo será dada a opção ao Posto de clicar no link <liberar senha>?

Tulio diz:
no email que eu mando pra ele a partir da tela cadastra_senha.php

Ricardo diz:
entendi... então o posto recebe um email com um link passando esses parametros...

Tulio diz:
SIM, e enquanto ele nao clicar no link, nao entra no site...
Para garantirmos que o email esta correto... manja ???

Ricardo diz:
certo

Tulio diz:
veja que este programa de autenticacao nao pode tentar fazer a checagem de login e senha, ok ???

Ricardo diz:
sim, depois da confirmacao dos dados, ele será direcionado para a tela de login?
o OID vem como "OID" mesmo ou como "oid_posto_fabrica" ?

Tulio diz:
no get vem como oid_posto_fabrica e vc testa assim: where tbl_posto_fabrica.oid = $oid_posto_fabrica
sim, depois que ele se autenticar, mostre uma tela dizendo que o email foi autenticado,
que e ele pode fazer o login. crie um link para a tela index.php

Ricardo diz:
sim , no "libera_senha.php" estou recebendo as variaveis via get, faço o UPDATE e se estiver tudo OK mostro a mensagem de OK com link para o Login
caso dê erro, exibo mensagem de erro e peço para clicar no link do email novamente

Tulio diz:
se der erro, mande clicar no link do email novamente, ou voltar na tela index e ir no PRIMEIRO LOGIN e preencher corretamente os dados.
*/
?>
