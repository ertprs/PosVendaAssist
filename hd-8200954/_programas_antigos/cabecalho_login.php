<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

if (trim ($login_fabrica) == 3 AND $PHP_SELF <> "/assist/perguntas_britania.php" AND $_SERVER['REMOTE_ADDR'] <> '200.198.99.102') {
	$sql = "SELECT tbl_linha.linha
			FROM   tbl_linha
			JOIN   tbl_posto_linha   using (linha)
			JOIN   tbl_posto_fabrica using (posto)
			WHERE  tbl_posto_fabrica.fabrica = $login_fabrica
			AND    tbl_posto_linha.posto     = $login_posto
			AND    tbl_linha.linha           = 3;";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$sql = "SELECT ja_chegaram
				FROM   britania_fama
				WHERE  posto = $login_posto";
		$res = @pg_exec($con,$sql);
		
		if (@pg_numrows($res) == 0) {
			header("Location: perguntas_britania.php");
			exit;
		}
	}
}

function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

$micro_time_start = getmicrotime();
?>

<html>

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css.css">

</head>

<script>
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde 
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		document.getElementById("displayArea").innerHTML = sText;
	}

</script>



<body>

<!--================== MENU DO SISTEMA ASSIST =======================-->
<!-- PARÂMETRO A SER PASSADO $layout_menu  "passa a opção em destaque-->

<div id="menu"> 
	<p>
	<?
	switch ($layout_menu) {

/*--================== $layout_menu = os =======================-*/
	case "os":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_os_bd.gif' usemap='#menu_map'>";
                }else if(($login_fabrica==20)OR ($login_fabrica==19)){
                        echo "<img src='imagens/btn_os-bosch.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_os.gif' usemap='#menu_map'>";
                }
		include 'submenu_os.php';
		break;

/*--================== $layout_menu = preco ====================-*/
	case "preco":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_preco_bd.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_preco.gif' usemap='#menu_map'>";
                }
                include 'submenu_preco.php';
		break;

/*--================== $layout_menu = pedido ===================-*/
	case "pedido":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_pedidos_bd.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_pedidos.gif' usemap='#menu_map'>";
                }
                include 'submenu_pedido.php';
		break;

/*--================== $layout_menu = tecnica ===================-*/
 	case "tecnica":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_tecnica_bd.gif' usemap='#menu_map'>";
                }else if(($login_fabrica==20)OR ($login_fabrica==19)){
                        echo "<img src='imagens/btn_tecnica-bosch.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_tecnica.gif' usemap='#menu_map'>";
                }
                include 'submenu_tecnica.php';
		break;

/*--================== $layout_menu = cadastro =================-*/
	case "cadastro":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_cadastro_bd.gif' usemap='#menu_map'>";
                }else if(($login_fabrica==20)OR ($login_fabrica==19)){
                        echo "<img src='imagens/btn_cadastro-bosch.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_cadastro.gif' usemap='#menu_map'>";
                }
                include 'submenu_cadastro.php';
		break;

/*--================== $layout_menu = procedimento =======================-*/
	case "procedimento":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_procedimento_bd.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens_admin/btn_procedimento_bd.gif' usemap='#menu_map'>";
                }       
                include 'submenu_tecnica.php';
		break;

/*--================== $layout_menu = padrao =======================-*/
	default:
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_os_bd.gif' usemap='#menu_map'>";
                }else if(($login_fabrica==20)OR ($login_fabrica==19)){
                        echo "<img src='imagens/btn_os-bosch.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_os.gif' usemap='#menu_map'>";
                }
                break;
	}
	?>

<!--============== MAPA DE IMAGEM DA BARRA DE MENU ============-->
                <?
                if (($login_fabrica==20)OR ($login_fabrica==19)){ ?>
                <map name="menu_map">
                <area shape="rect" coords="014,0,090,24" href="menu_os.php">
                <area shape="rect" coords="100,0,176,24" href="menu_tecnica.php">
                <area shape="rect" coords="190,0,263,24" href="menu_cadastro.php">
                <area shape="rect" coords="541,0,622,24" href="http://www.telecontrol.com.br/assist">
                </map>
                <?    }else{ ?>
                <map name="menu_map">
                <area shape="rect" coords="014,0,090,24" href="menu_os.php">
                <area shape="rect" coords="100,0,176,24" href="menu_preco.php">
                <area shape="rect" coords="190,0,263,24" href="menu_pedido.php">
                <area shape="rect" coords="276,0,353,24" href="menu_tecnica.php">
                <area shape='rect' coords='362,0,439,24' href='menu_cadastro.php'>
                <? if ($login_fabrica == 1){ ?>
                <area shape="rect" coords="450,0,527,24" href="procedimento_mostra.php"><? } ?>
                <area shape="rect" coords="541,0,622,24" href="http://www.telecontrol.com.br/assist">
                </map>
                <?  } ?>
</div>

<?
if ($login_fabrica == 3 and date("Y-m-d") < '2005-10-01') {
	echo "<tr bgcolor='#BED2D8'><td align='center'><b>Informativo de leitura obrigatória.</b><br><font size='-1'>Novo procedimento para envio de Ordens de Serviço e Nota fiscal de Mão-de-Obra</font><br><a href='pdf/britania_informativo_001.pdf'>Ler Informativo</a></td></tr>";
}

if (1==2) {
#if ($login_fabrica_nome <> "BlackeDecker"){
	$sql = "SELECT COUNT(*) FROM tbl_opiniao_posto WHERE tbl_opiniao_posto.fabrica = $login_fabrica AND tbl_opiniao_posto.ativo IS TRUE ";
	$res = @pg_exec ($con,$sql);
	$tem_pesquisa = @pg_result ($res,0,0) ;

	$sql = "SELECT COUNT(*) FROM tbl_opiniao_posto JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto) JOIN tbl_opiniao_posto_resposta USING (opiniao_posto_pergunta) WHERE tbl_opiniao_posto.fabrica = $login_fabrica AND tbl_opiniao_posto.ativo IS TRUE AND tbl_opiniao_posto_resposta.posto = $login_posto";
	$res = @pg_exec ($con,$sql);

	if (@pg_result ($res,0,0) == 0 and $tem_pesquisa ) {
		echo "<table width='600' align='center' border='0' align='center'>";
		echo "<tr>";
		echo "<td bgcolor='#FF6633' style='padding: 5px; font-size: 12px; font-weight: normal; font-family: arial,verdana; text-align: center;'>";
		echo "<b>Atencão !</b> Você foi convidado a participar de uma pesquisa. <br><a href='opiniao_posto.php'>Clique aqui</a> para preencher o formulário";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
}
?>

