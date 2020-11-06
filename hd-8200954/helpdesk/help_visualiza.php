<?php

//Desenvolvedor Inicial: Ébano Lopes
//HD 205958
//Este arquivo mostra um help devidamente formatado

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$partes = explode("/", $_SERVER["SCRIPT_FILENAME"]);
if ($partes[5] == "admin") {
	include 'autentica_admin.php';
}
elseif ($partes[5] == "helpdesk") {
	include 'autentica_admin.php';
}
elseif ($partes[3] == "assist" && $partes[4] == "www") {
	include 'autentica_usuario.php';
}

$help = $_GET["help"];

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet"		type="text/css"		href="css/tc09_MenuMatic.css" charset="utf-8" media="screen" />
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/thickbox.js"></script>
<script language=javascript>
function fechar_tb() {
	try {
		self.parent.parent.document.getElementById('iframe_tbl_help').style.display='none';;
	}
	catch(err) {
	}

	self.parent.tb_remove();
}
</script>

<?

if ($_GET["leitura"]) {
	$sql = "INSERT INTO tbl_help_admin(help, admin, data_leitura) VALUES($help, $login_admin, NOW())";
	$res = pg_query($con, $sql);

	echo "
	<script>
		fechar_tb();
	</script>";
	die;
}

?>

<style>
html
{
	scrollbar-arrow-color:#696A90;
	scrollbar-3dlight-color:#696A90;
	scrollbar-highlight-color:#696A90;
	scrollbar-face-color:#FFFFFF;
	scrollbar-shadow-color:#696A90;
	scrollbar-darkshadow-color:#696A90;
	scrollbar-track-color:#696A90;
}

body {
	margin: 0px;
	font-family: Arial;
	font-size: 9pt;
	color: #333333;
	text-align: justify;
}

.cabecalho {
	background: url(imagens/TopoBg.gif);
	background-repeat: repeat-x;
	width: 100%;
	height: 100px;
}

.logotelecontrol {
	position: absolute;
	top: 10px;
	left: 10px;
	width: 350px;
	height: 100px;
	background: url(imagens/logo15.png);
	background-repeat: no-repeat;
	background-position: top left;
}

.corpo {
	position: relative;
	margin-left: auto;
	margin-right: auto;
	top: 10px;
	width: 708px;
	height:300px;
	border: 1px solid #E6EEF7;
	background-color: #F1F4FA;
	overflow: auto;
	padding: 10px;
}

.leiturapendente {
	width: 100% - 10px;
	background-color: #FFDDCC;
	border: 1px solid #CC9988;
	padding: 5px;
	font-size: 9pt;
	color: #440000;
}


div#navBar {
		position: absolute;
		top: 53px;
		left: 464px;
		z-index: 30;
        background: url(imagens//navM.gif) repeat-x;
        line-height: 29px;
        margin-bottom: 10px
}

div#navBarL {
        background: url(imagens//navL.gif) no-repeat;
}

div#navBarR {
        background: url(imagens//navR.gif) 100% 0% no-repeat
}

div#navBar, div#navBarL, div#navBarR {
        height: 29px
}

div#header, div#menu, div#container, div#conteiner, div#navBar, div#navBarL, div#navBarR {
        margin-left: auto;
        width: 294px;
        text-align: left;
        display: block;
        clear: both
}

div#navbar a {
       display: block;
       float: left;
       width: 130px;
       margin-left: 7px;
       text-align: center;
       background: url(imagens//navS.gif) 100% 50% no-repeat 
}
</style>

</head>

<body>

  <div id="navBar">
    <div id="navBarR">
      <div id="navBarL">
        <ul id="nav">
          <li> <img src="imagens/navS.gif" /></li>
          <li><a href="<? echo $PHP_SELF . "?help=$help&leitura=sim"; ?>">Já li e Confirmo</a></li>
          <li><img src="imagens/navS.gif" /></li>
          <li><a href="javascript:fechar_tb()">Leio Depois</a></li>
          <li><img src="imagens/navS.gif" /></li>
        </ul>
      </div>
    </div>
  </div>

<div class="cabecalho">
	<div class="logotelecontrol"></div>
</div>
<div class="corpo">
<?php

$sql = "
SELECT
help

FROM
tbl_help_admin

WHERE
help=$help
AND admin=$login_admin
";
$res = pg_query($con, $sql);
if (pg_num_rows($res)) {
}
else {
	echo "
	<div class='leiturapendente'><b>Atenção:</b> a leitura do texto abaixo é obrigatória, pois é essencial para o entendimento do funcionamento do sistema e/ou melhorias e continuará aparecento até que leia e clique em <u>Já li e Confirmo</u></div>
	";
}


$sql = "
SELECT
descricao

FROM
tbl_help

WHERE
help=$help
";
$res = pg_query($con, $sql);
echo pg_result($res, 0, 0);
?>
</div>

</body>
</html>