<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

##### VERIFICAÇÃO SE O POSTO JÁ LEU O COMUNICADO - INÍCIO #####
/*
$sql =	"SELECT tbl_comunicado.comunicado                                       ,
				tbl_comunicado.descricao                                        ,
				tbl_comunicado.mensagem                                         ,
				tbl_comunicado.extensao                                         ,
				TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data               ,
				tbl_comunicado.produto                                          ,
				tbl_produto.referencia                    AS produto_referencia ,
				tbl_produto.descricao                     AS produto_descricao  
		FROM tbl_comunicado
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		LEFT JOIN tbl_comunicado_posto_blackedecker ON  tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
													AND tbl_comunicado_posto_blackedecker.fabrica    = $login_fabrica
													AND tbl_comunicado_posto_blackedecker.posto      = $login_posto
		WHERE tbl_comunicado.fabrica = $login_fabrica
		AND   tbl_comunicado.obrigatorio_site IS TRUE
		AND   tbl_comunicado_posto_blackedecker.posto IS NULL
		ORDER BY tbl_comunicado.data DESC;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	header("Location: comunicado_site.php");
	exit;
}
*/
##### VERIFICAÇÃO SE O POSTO JÁ LEU O COMUNICADO - FIM #####

##### Comunicados Mondial #####
if (strlen($_COOKIE["ComunicadoMondial20050929"]) == 0 AND $login_fabrica == 5) {
	header("Location: comunicado_mondial_20050929.php");
	exit;
}

##### Comunicados Britânia #####
if (strlen($_COOKIE["ComunicadoBritania"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20050719.php");
	exit;
}

if (strlen($_COOKIE["ComunicadoBritania20050923"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20050923.php");
	exit;
}

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Telecontrol ASSIST - Gerenciamento de Assistência Técnica";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include 'cabecalho_login.php';
?>

<hr>
<h1><? echo $login_nome ?></h1>

<?
	if (trim($login_credenciamento) == "EM DESCREDENCIAMENTO") echo "<div class='error'>$login_credenciamento</div>";
?>

<!-- AQUI VAI INSERIDO OS RELATÓRIOS E OS FORMS -->



<!--
<br>
<center>
<img src='imagens/embratel_logo.gif' valign='absmiddle'>
<br>
<font color='#330066'><b>Concluída migração para EMBRATEL</b>.</font>
<br>
<font size='-1'>
A <b>Telecontrol</b> agradece sua compreensão. 
<br>Agora com a migração para o iDC EMBRATEL teremos 
<br>um site mais veloz, robusto e confiável.
</font>
<p>
</center>
-->


<div id="container"><h2><IMG SRC="imagens/bemVindo<? echo $login_fabrica_nome ?>.gif" ALT="Bem Vindo!!!"></h2></div>
<? 


switch (trim ($login_fabrica_nome)) {
	
	

	case "Dynacom":
		include "news_dynacom.php";
	break;
	
	case "Britania":
		if (getenv("REMOTE_ADDR") == "201.0.9.216") include "news_britania_new.php";
		$sql = "SELECT COUNT(*) FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha IN (2,4) WHERE tbl_posto.estado = 'SP' AND tbl_posto.posto = $login_posto";
		$res = pg_exec ($con,$sql);
		$qtde = pg_result ($res,0,0);
		if ($qtde > 0) {
			echo "<font face='arial' size='+1'>A <b>TELECONTROL</b> é seu novo Distribuidor de Peças BRITÂNIA <br>para as linhas de Eletro Portáteis e Linha Branca</font>";
			echo "<p>";
			echo "<font face='arial' size='-1'>Entre em contato conosco pelo email <a href='mailto:distribuidor@telecontrol.com.br'>distribuidor@telecontrol.com.br</a> <br>ou pelo MSN, usando este mesmo endereço de email. <br> Telefone (14) 3433-9009 </font>";

			echo "<p>";
		}
		include "news_britania.php";
#		echo "<script language='javascript'>window.open ('britania_informativo_2.html','popup2','toolbar=no, location=no, status=nos, scrollbars=no, directories=no, width=300, height=300, top=50, left=100') ; </script>";
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

	case "Ibratele":
		include "news_ibratele.php";
	break;

	case "Filizola":
		include "news_filizola.php";
	break;

	case "Telecontrol":
		include "news_telecontrol.php";
	break;

	case "Lenoxx":
		include "news_lenoxx.php";
	break;

	case "Intelbras":
		include "news_intelbras.php";
	break;
	
	case "BlackeDecker":
		include "news_blackdecker.php";
	break;

	case "Latina":
		include "news_latina.php";
	break;
}

include "rodape.php";
?>
