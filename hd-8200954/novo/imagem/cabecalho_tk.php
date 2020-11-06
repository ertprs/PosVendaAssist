<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
// Data no passado
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
// Sempre modificado
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// HTTP/1.0
header("Pragma: no-cache");


#echo "<h1>O site sairá do ar em 5 minutos para manutenção</h1>";
#echo "<h1>e retornará dentro de meia hora</h1>";
#echo "<h1>Por favor, finalize seu trabalho.</h1>";
#echo "<p><font size='+3' color='#ff0000'>Site Fora do ar em 1 minuto</font>";
 
if (trim ($login_fabrica) == 3 AND $PHP_SELF <> "/assist/perguntas_britania.php" AND $_SERVER['REMOTE_ADDR'] <> '200.198.99.102' AND $_SERVER['REMOTE_ADDR'] <> '201.0.9.216') {

	$sqlX = "SELECT tbl_linha.linha
			FROM   tbl_linha
			JOIN   tbl_posto_linha   using (linha)
			JOIN   tbl_posto_fabrica using (posto)
			WHERE  tbl_posto_fabrica.fabrica = $login_fabrica
			AND    tbl_posto_linha.posto     = $login_posto
			AND    tbl_linha.linha = 3;";
	$res = @pg_exec($con,$sqlX);

	if (@pg_numrows($res) > 0) {
		$sqlX = "SELECT ja_chegaram
				FROM   britania_fama
				WHERE  posto     = $login_posto";
		$res = @pg_exec($con,$sqlX);
		if (strlen(@pg_result($res,0,ja_chegaram)) == 0) {
			header("Location: perguntas_britania.php");
			exit;
		}
	}

}

//////////////////////////////////////////////////////////
function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

function TempoExec($pagina, $sql, $time_start, $time_end){
	$time = $time_end - $time_start;
	$time = str_replace ('.',',',$time);
	$sql  = str_replace ('\t',' ',$sql);
	$fp = fopen ("/home/telecontrol/tmp/postgres.log","a");
	fputs ($fp,$pagina);
	fputs ($fp,"#");
	fputs ($fp,$sql);
	fputs ($fp,"#");
	fputs ($fp,$time);
	fputs ($fp,"\n");
	fclose ($fp);
}
//////////////////////////////////////////////////////////

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
	<meta name      ="Generator"     content="na mão...">
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
		/*
	switch ($layout_menu) {

/*--================== $layout_menu = os =======================-*/
	/*case "os":
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
	/*case "preco":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_preco_bd.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_preco.gif' usemap='#menu_map'>";
                }
		include 'submenu_preco.php';
		break;

/*--================== $layout_menu = pedido ===================-*/
/*	case "pedido":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_pedidos_bd.gif' usemap='#menu_map'>";
                }else{
        		echo "<img src='imagens/btn_pedidos.gif' usemap='#menu_map'>";
                }
                include 'submenu_pedido.php';
		break;

/*--================== $layout_menu = tecnica ===================-*/
 /*	case "tecnica":
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
	/*case "cadastro":
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
/*	case "procedimento":
                if ($login_fabrica == 1){
			echo "<img src='imagens/btn_procedimento_bd.gif' usemap='#menu_map'>";
                }else{
			echo "<img src='imagens/btn_tecnica.gif' usemap='#menu_map'>";
                }
                include 'submenu_tecnica.php';
		break;

/*--================== $layout_menu = padrao =======================-*/
/*	default:
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
		<?  } */?>
                
                
                
</div>
<TABLE width="750px" border="1" cellspacing="0" cellpadding="0" align="center">
<tr>
<td>a</td>
<td>b</td>
<td>c</td>
<td>d</td>
<td>e</td>
<td>f</td>
<td>g</td>
<td>h</td>
</tr>
</table>
		
		
		

<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align="center">
<TR> 
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD style='font-size: 14px; font-weight: bold; font-family: arial;'> <? echo "$title" ?> </TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<TABLE width="700px" border="2"  cellpadding='0' cellspacing='0' bordercolor='#d9e2ef' align="center">
<tr>
<?
function escreveData($data) { 
	$vardia = substr($data,8,2);
	$varmes = substr($data,5,2);
	$varano = substr($data,0,4);

	$convertedia = date ("w", mktime (0,0,0,$varmes,$vardia,$varano)); 

	$diaSemana = array("Domingo", "Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado"); 

	$mes = array(1=>"janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro", "dezembro"); 

	if ($varmes < 10) $varmes = substr($varmes,1,1);

	return $diaSemana[$convertedia] . ", " . $vardia  . " de " . $mes[$varmes] . " de " . $varano; 
} 
// Utilizar da seguinte maneira 
//echo escreveData("2005-12-02"); 
?> 
	<td style='padding: 5px; font-size: 12px; font-weight: normal; font-family: arial; text-align: center;'>
	<? 
		$data = date("Y-m-d");
		echo escreveData($data);
		echo date(" - H:i");
		echo " / Posto: " . $login_codigo_posto . "-" . ucfirst($login_nome);
		
		if($login_fabrica == 3 and $login_bloqueio_pedido == 't'){
			echo "<p>";
			
			echo "<font face='verdana' size='2' color='FF0000'><b>Existem títulos pendentes de seu posto autorizado junto ao Distribuidor.
			<br>
			Não será possível efetuar novo pedido faturado das linhas de eletro e branca.
			<br><br>
			Para regularizar a situação solicitamos um contato urgente com a TELECONTROL:
			<br>
			(14) 3413-6588 / (14) 3413-6589 / distribuidor@telecontrol.com.br
			<br>
			Entrar em contato com o departamento de cobranças ou <br>
			efetue o depósito em conta corrente no <br><BR>
			Banco Bradesco<BR>
			Agência 2155-5<br>
			C/C 17427-0<br><br>
			e encaminhe um fax (14 3413-6588) com o comprovante.</b>
			<br><br>
			<b>Para visualizar os títulos <a href='posicao_financeira_telecontrol.php'>clique aqui</a></b>
			</font>";
			
			echo "<p>";
		}
		
	?>
	</td>
</tr>

<?
if ($login_fabrica == 3 and date("Y-m-d") < '2005-10-01') {
	echo "<tr bgcolor='#BED2D8'><td align='center'><b>Informativo de leitura obrigatória.</b><br><font size='-1'>Novo procedimento para envio de Ordens de Serviço e Nota fiscal de Mão-de-Obra</font><br><a href='pdf/britania_informativo_001.pdf'>Ler Informativo</a></td></tr>";
}

if (1==2) {
	$sqlX = "SELECT COUNT(*) FROM tbl_opiniao_posto WHERE tbl_opiniao_posto.fabrica = $login_fabrica AND tbl_opiniao_posto.ativo IS TRUE ";
	$res = @pg_exec ($con,$sqlX);
	$tem_pesquisa = @pg_result ($res,0,0) ;

	$sqlX = "SELECT COUNT(*) FROM tbl_opiniao_posto JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto) JOIN tbl_opiniao_posto_resposta USING (opiniao_posto_pergunta) WHERE tbl_opiniao_posto.fabrica = $login_fabrica AND tbl_opiniao_posto.ativo IS TRUE AND tbl_opiniao_posto_resposta.posto = $login_posto";
	$res = @pg_exec ($con,$sqlX);

	if (@pg_result ($res,0,0) == 0 AND $tem_pesquisa) {
		echo "<tr>";
		echo "<td bgcolor='#FF6633' style='padding: 5px; font-size: 12px; font-weight: normal; font-family: arial,verdana; text-align: center;'>";
		echo "<b>Atencão !</b> Você foi convidado a participar de uma pesquisa. <br>Antes de prosseguir utilizando o site, você deve completar a pesquisa. <br> <a href='opiniao_posto.php'>Clique aqui</a> para preencher o formulário";
		echo "</td>";
		echo "</tr>";

		if (strpos ($PHP_SELF,'opiniao_posto.php') === false) exit;
	}
}

?>

<tr>
	<td><div class="frm-on-os" id="displayArea">&nbsp;</div></td>
</tr>
</TABLE>
