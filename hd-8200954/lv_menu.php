<?
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

function maiusculo($textom){
	$textom = strtoupper($textom);
	$textom = str_replace("á","Á",$textom);
	$textom = str_replace("é","É",$textom);
	$textom = str_replace("í","Í",$textom);
	$textom = str_replace("ó","Ó",$textom);
	$textom = str_replace("ú","Ú",$textom);
	$textom = str_replace("â","Â",$textom);
	$textom = str_replace("ê","Ê",$textom);
	$textom = str_replace("ô","Ô",$textom);
	$textom = str_replace("î","Î",$textom);
	$textom = str_replace("û","Û",$textom);
	$textom = str_replace("ã","Ã",$textom);
	$textom = str_replace("õ","Õ",$textom);
	$textom = str_replace("ü","Ü",$textom);
	$textom = str_replace("ç","Ç",$textom);
	$textom = str_replace("à","À",$textom);
	$textom = str_replace("è","È",$textom);
	return $textom;
}

if(strlen($_POST['produto_acabado'])>0) $produto_acabado = $_POST['produto_acabado'];
else                                    $produto_acabado = $_GET['produto_acabado'];
?>

<link rel="stylesheet" href="js/jquery.tooltip.css" />
<script src="js/jquery.bgiframe.js"          type="text/javascript"></script>
<script src="js/jquery.dimensions.tootip.js" type="text/javascript"></script>
<script src="js/chili-1.7.pack.js"           type="text/javascript"></script>
<script src="js/jquery.tooltip.js"           type="text/javascript"></script>
<script type="text/javascript" src="admin/js/thickbox.js"></script>
<link rel="stylesheet" href="admin/js/thickbox.css" type="text/css" media="screen" />

<script type="text/javascript" src="js/excanvas.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/accordion.js"></script>
<script type="text/javascript">
	<?php if($login_fabrica != 85){ ?>
	$(function() {
		$("a[@rel='regra']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			fixPNG: true,
			showBody: " - ",
			extraClass: "regra"
		});
		$('div.top').corner();
//		$("div[@rel='box_content']").corner("20px tr bl");
	});
	

	jQuery().ready(function(){
		// applying the settings
		jQuery('#menuver').Accordion({
			header: 'h3.head',
			alwaysOpen: false,
			animated: true,
			showSpeed: 400,
			hideSpeed: 800
		});
	});

	<?php } ?>
</script>

<style>
BODY {color: #666;}
.Titutlo2{
	font-family: Verdana,sans;
	font-size: 12px;
	font-weight:bold;
	color: #333;
}
.Titulo{
	font-family: Verdana,sans;
	font-size: 12px;
	font-weight:bold;
	color: #FFF;
}

.MenuText {
	color:#4b5070;
	font-weight:bold;
	font-size: 10px;
	font-family: Verdana, sans-serif;
	text-decoration:none;
}

.MenuText:link {
	color:#4b5070;
}

.MenuText:visited {
	color:#4b5070;
}

A.linhas {
	color:#333;
	text-decoration:none;
	font: 10px Verdana, sans-serif;
    letter-spacing: -0.2ex;
	height:14px;
    font-weight: bold;
}

.ConteudoBusca{
	font-family: Arial;
	font-size: 11px;
	color: #333333;
	background-image: url('imagens/barra_dg_am_tc.png');
	border: 1px solid #242946;
    vertical-align: middle;
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	color: #333333;
}

.Caixa{
	FONT-SIZE:		  8pt;
	FONT-FAMILY:	  Arial;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 14px;
	color:#FFF;
	border:#485989 1px solid;
	background-color: #990000;
}

.contenedorfoto {
	float:left;
	width:110px;
	height:100px;
	margin:3px;
	padding:5px;
	background-color:#f5f7f9;
	border-right: #a5a7aa solid 1px;
	border-bottom: #a5a7aa solid 1px;
	text-align:center;
}

.contenedorfoto a {
	text-decoration: none;
}

.contenedorfoto span {
	color:#515151;
	font-family: Trebuchet MS;
	font-size: 9pt;
}
.content_box {
	float: left;
	width: 155px;
	height: 175px;
	margin: 10px 10px 10px 5px;
	padding: 10px 10px 10px 10px;
	/*border: 2px solid Black;*/
	background: #FFFFFF;
}

.content_box a,.content_box a:visited,.content_box a:hover{
	color:#6B6B6B;
	font: 12px Verdana, sans-serif;
	font-weight:bold;
	text-decoration:none;
}


#menuver {
	width: 180px;
	padding: 0;
	margin: 0;
	font: 10px Verdana, sans-serif;
	background: #f2f2f2;
	/*border-top: 3px solid #B5CDE8; */
	border-bottom: 3px solid #0082d7;
	font-weight: normal;
}
#menuver ul{
margin:0;padding:0;
}
#menuver li {
	list-style: none;
	color: #333333;
	margin: 0.5em 0 0.5em 0.5em;
	font-weight: normal;
}
#menuver li a {
	margin:0;
	padding:0;
	text-decoration:none;
	color: #333333;
	font-weight: normal;
}
#menuver li a:visited {
	margin:0;
	padding:0;
	color: #333333;
	font-weight: normal;
}
#menuver li a:hover {
	margin:0;
	padding:0;
	background: #f2f2f2;
	color: #333333;
	font-weight: normal;
	text-decoration:underline;
}
#menuver li a:active {
	margin:0;
	padding:0;
	background: #D8E4F3;
	color: #333333;
	font-weight: normal;
}
#menuver .head  {
	list-style: none;
	color: #010203;
	margin: 0.5em 0 0.5em 0.5em;
	font-weight: normal;
	font: 10px Verdana, sans-serif;
}

</style>
<?

$sql = "SELECT regra_loja_virtual,descricao_forma_pagamento
		FROM tbl_configuracao
		WHERE fabrica = $login_fabrica";
$res_conf = pg_exec($con,$sql);
$resultado = pg_numrows($res_conf);
if ($resultado>0){
	$regra_loja_virtual        = trim(pg_result($res_conf,0,regra_loja_virtual));
	$descricao_forma_pagamento = trim(pg_result($res_conf,0,descricao_forma_pagamento));
}

if($login_fabrica==10 OR $login_fabrica==1){
/*
	echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' >\n";
	echo "<tr>\n";
	echo "<td valign='top' align='right' bgcolor='#FFFFFF'>\n";
	echo "<a href='#'><img src='imagens/cli_01.gif'></a>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' >\n";
	echo "<tr>\n";
	echo "<td valign='top' align='right' style='width:437;height:58;' width='437' heigth='58' background='imagens/lv_topo1.gif'>&nbsp;</td>";
	echo "<td valign='top' align='right' heigth='58' background='imagens/lv_topo2.gif'>&nbsp;</td>";
	echo "</tr>";
	echo "</table>";
*/

 	echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' >\n";
	echo "<tr valign='middle'>\n";
	echo "<td valign='top' align='left' BACKGROUND='imagens/menu_bg.gif' style='height: 38px; color: #777777;'>&nbsp;&nbsp;\n";
	if (((strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10) OR $login_fabrica==1){
		$sql = "SELECT	DISTINCT tbl_peca.linha_peca AS linha,
			 		 tbl_linha.nome      AS descricao
			FROM tbl_peca
			JOIN tbl_linha ON tbl_linha.linha = tbl_peca.linha_peca
			WHERE tbl_peca.fabrica = $login_fabrica
			AND   tbl_linha.ativo   IS TRUE
			AND   tbl_peca.ativo    IS TRUE
			ORDER BY tbl_linha.nome";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			for($i=0;pg_numrows($res)>$i;$i++){
				$linha           = pg_result($res,$i,linha);
				$linha_descricao = maiusculo(pg_result($res,$i,descricao));
				echo  "<a href='lv_completa.php?categoria=$linha&categoria_tipo=linha'
						 class='linhas'>$linha_descricao</a> | \n";
			}
		}
	}
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}#B5.CDE8
echo "<table width='100%' align='center' cellpadding='0' cellspacing='3' class='ConteudoBusca'>\n";
echo "<tr valign='middle'>\n";
echo "<td align='left'>\n";
	echo "<table cellspacing='4' cellpadding='0' border='0'>\n";

	echo "<tr valign='middle'>\n";
	if(strlen($produto_acabado)==0){
		echo "<td class='Conteudo'>";
		echo " &nbsp;&nbsp;<a href='lv_completa.php' class='MenuText'>LISTA COMPLETA</a> ";
		echo "</td>\n";
		echo "<td>&nbsp; |</td>\n";
	}

	if($produto_acabado=="t" AND $login_fabrica==3){
		$yproduto_acabado = "?produto_acabado=t";
	}

	echo "<td class='Conteudo'>";
	echo "<img src='imagens/carrinho_azul_menu.png' align='absmiddle'>&nbsp;<a href='lv_carrinho.php$yproduto_acabado' class='MenuText'>MEU CARRINHO</a> &nbsp;";
	echo "</td>\n";
	if(strlen($produto_acabado)==0){
		echo "<td> | </td>\n";
	}
	echo "<td class='Conteudo'>";

	if (strlen($regra_loja_virtual)>0){
		/*	Manuel// Anulado código anterior, agora é só um link
		echo "<a href='#' rel='regra' class='MenuText' title=\"<font size='2' color='red'><b>ATENÇÃO</b></font><BR><BR>";
		echo "<font size='1' color='red'>".$regra_loja_virtual."</font>";
		echo "<br>\">&nbsp;&nbsp;<b>FORMAS DE PAGAMENTO</b></a>&nbsp;&nbsp;";	*/
		if($login_fabrica == 10) {
			echo "<A HREF=\"javascript:window.open('./lv_forma_pago.php','Atendimento', 'toolbar=no,location=no,status=no,menubar=no,resizable=no,scrollbars=yes, width=418,height=550');void(0);\" class='MenuText'>FORMAS DE PAGAMENTO</a>";
		}
	}
	if($login_fabrica==3 AND strlen($produto_acabado)==0){
		echo "&nbsp;<a href='loja_liquidacao.php' class='MenuText'>LIQUIDA&Ccedil;&Atilde;O</a>\n";
	}
	if($login_fabrica==10){ #	Conferir depois o link
		echo "&nbsp;<a href='lv_completa.php?promocoes=promocoes' class='MenuText' style='color: red;'><b>PROMO&Ccedil;&Otilde;ES</b></a>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
echo "</td>\n";
echo "<td align='right' style='padding-top: 3px;'>\n";
	echo "<table height='100%' cellspacing='0' cellpadding='3' border='0'>\n";
	echo "<tr valign='middle'>\n<td class='MenuText'>\n";
	echo "<form method='POST' name='Pesquisar' action='lv_completa.php'>\n";
		echo "Busca:\n";
		echo "&nbsp;&nbsp;<input name='busca' size='15' value='$busca' title='Pesquisar' type='text' 	>";
		if($produto_acabado=="t" AND $login_fabrica==3){//HD 98211
			echo "<input type='hidden' name='produto_acabado' value='t'>";
		}
		echo "&nbsp;&nbsp;<input name='btnG' value='' type='hidden'>\n".
			 "<img src='imagens/bt_busca_ok.gif' style='cursor: pointer;' ".
			 	"onclick=\"javascript: if (document.Pesquisar.btnG.value == '' ) { ".
				 		"document.Pesquisar.btnG.value='Comprar'; ".
						"document.Pesquisar.submit() ".
						"} else { ".
						"alert ('Aguarde submissão') }\">";
	echo "</form>\n";
	echo "</td>\n</tr>\n";
	echo "</table>\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
?>
<table width='100%' border='0' cellspacing='0' align='center' cellpadding='2'>
	<tr>
		<td style='background-color:#f5f5f5;text-align:right;font-size:8pt'>
<?
if (strlen($login_posto)>0 AND strlen($cookie_login['cook_pedido_lu']) > 0){

	$sql = "SELECT tbl_pedido.pedido,total
			FROM  tbl_pedido
			WHERE tbl_pedido.pedido_loja_virtual IS TRUE
			AND   tbl_pedido.exportado           IS NULL
			AND   tbl_pedido.finalizado          IS NULL
			AND   tbl_pedido.posto               = $login_posto
			AND   tbl_pedido.fabrica             = $login_fabrica
			ORDER BY pedido DESC
			LIMIT 1";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0 ) {
		$pedido                 = pg_result($res,0,pedido);
		$total_pedido_cabecalho = pg_result($res,0,total);

		$sql2 = "SELECT COUNT(*) AS total_itens FROM tbl_pedido_item WHERE pedido = ".$pedido;
		$res2 = @pg_exec($con,$sql2);
		$pedido_itens = @pg_result($res2,0,0);

		if (pg_numrows($res2) > 0) echo "Pedido com <B>$pedido_itens item(ns)</B>. Valor total R$ <B>".number_format($total_pedido_cabecalho,2,",",".")." </B>";
		else                      echo "<B>Pedido vazio</B>.";
	}

}else{
	if ($login_fabrica == 3){
		echo "<B>Pedido vazio</B>.";
	}
}
if($login_fabrica==1){
	$sqlx ="SELECT   tbl_tipo_posto.acrescimo_tabela_base        ,
					tbl_tipo_posto.acrescimo_tabela_base_venda  ,
					tbl_condicao.acrescimo_financeiro           ,
					((100 - tbl_icms.indice) / 100) AS icms     ,
					tbl_posto_fabrica.pedido_em_garantia
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
										and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
			JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
										and tbl_condicao.condicao     = 50
			JOIN    tbl_icms             on tbl_icms.estado_destino   = tbl_posto.estado
			WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
			AND     tbl_posto_fabrica.posto   = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";

	$resx = @pg_exec($con,$sqlx);

	if (pg_numrows($resx) > 0) {
		$picms                        = pg_result($resx, 0, icms);
		$acrescimo_tabela_base       = pg_result($resx, 0, acrescimo_tabela_base);
		$acrescimo_tabela_base_venda = pg_result($resx, 0, acrescimo_tabela_base_venda);
		$acrescimo_financeiro        = pg_result($resx, 0, acrescimo_financeiro);
		$pedido_em_garantia          = pg_result($resx, 0, pedido_em_garantia);
	}else{
		echo "Problemas com vínculo entre posto e fabricante.";
		exit;
	}

}
?>
</td></tr></table>
<?flush();?>
