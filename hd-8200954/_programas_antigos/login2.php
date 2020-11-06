<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Telecontrol ASSIST - Gerenciamento de Assistência Técnica";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';


?>

<link type="text/css" rel="stylesheet" href="css/login.css">

<div id="container" >
	<IMG SRC="imagens/bemVindo<? echo $login_fabrica_nome ?>.gif" usemap="#top_map" ALT="Bem Vindo!!!">
	<div id="nomeposto">
		<h1><? echo $login_nome ?></h1>
	</div>
<? include "rodape.php";?>
</div>


<!-- AQUI VAI INSERIDO OS RELATÓRIOS E OS FORMS -->

<?

switch (trim ($login_fabrica_nome)) {
	case "Dynacom":
		include "news_dynacom.php";
	break;
	
	case "Britania":
		include "news_britania.php";
	break;

	case "Meteor":
		include "news_meteor.php";
	break;

	case "Mondial":
		include "news_mondial.php";
	break;

	case "Tectoy":
		include "news_tectoy.php";
	break;
}
?>

<map name="top_map"> 
	<area shape="rect" coords="143,19,237,39" href="os_cadastro.php" alt="Ordem de Servi&ccedil;o" title="Ordem de Servi&ccedil;o">
	<area shape="rect" coords="239,19,330,39" href="tabela_precos.php" alt="Tabela de Pre&ccedil;os" title="Tabela de Pre&ccedil;os">
	<area shape="rect" coords="335,19,384,39" href="pedido_cadastro.php" alt="Pedidos" title="Pedidos">
	<area shape="rect" coords="388,19,454,39" href="#" alt="Informa&ccedil;&otilde;es T&eacute;cnicas" title="Informa&ccedil;&otilde;es T&eacute;cnicas">
	<area shape="rect" coords="459,19,520,39" href="#" alt="Cadastros" title="Cadastros">
	<area shape="rect" coords="527,19,589,39" href="#" alt="Miscel&acirc;nia" title="Miscel&acirc;nia">
	<area shape="rect" coords="591,19,644,39" href="#" alt="Sair" title="Sair">
	<area shape="rect" coords="483,42,647,82" href="http://www.tectoy.com.br" alt="Vai para a Home Page da Tectoy" title="Vai para a Home Page da Tectoy" target="_blank">
</map>
