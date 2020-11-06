<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'fn_logoResize.php';
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if($login_pais=='AR') $bandeira = 'bandeira-argentina.gif';
if($login_pais=='CO') $bandeira = 'bandeira-colombia.gif' ;
if($login_pais=='UY') $bandeira = 'bandeira-uruguai.gif'  ;
if($login_pais=='MX') $bandeira = 'bandeira-mexico.gif'  ;

$title = 'Menu Inicial';

$cor  = "#485989";
$cor2 = "#9BC4FF";
$corforum = "#880000";

if ($login_fabrica == 24 || $login_fabrica == 15) {

	$data_hora = ($login_fabrica == 15) ? '2010-08-10 09:36:39.548903' : '2010-06-09 09:36:39.548903';

	$sql = "SELECT CASE WHEN atualizacao<='$data_hora' THEN 'sim' ELSE 'NAO' END AS resposta
              FROM tbl_posto_fabrica
             WHERE fabrica = $login_fabrica AND posto = $login_posto";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		if (pg_result($res,0,0) == 'sim') {
			@header('Location:posto_cadastro.php');
		}
	}
}

/*
if($login_fabrica==10 and $ip=="201.13.180.246"){
	$sql_nova = "SELECT
						to_char(data_alteracao, 'DD-MM-YYYY') as data_alteracao
				FROM tbl_posto_fabrica
				WHERE posto=$login_posto
				AND fabrica=$login_fabrica";
	$res_nova = pg_exec($con, $sql_nova);
	$data_alteracao = pg_result($res_nova,0,data_alteracao);
	if($data_alteracao<'17-12-2006'){
		header ("Location: posto_cadastro_atualiza.php");
		exit;
	}
}
*/

/*  Define os links do menu inicial */
//  Ordem de Serviço:
	$link_os				= "menu_os.php";
	$link_os_nome			= mb_strtoupper(traduz("ordem.de.servico",$con,$cook_idioma));

//  Pedido
	$link_pedido			= "pedido_cadastro.php";
	if ($login_fabrica== 1)	$link_pedido = "menu_pedido.php";
	if ($login_fabrica== 3)	$link_pedido = "loja_completa.php"; #	Redireciona para a Loja Virtual
	if ($login_fabrica==14)	$link_pedido = "pedido_relacao.php";
	if ($login_fabrica==15)	$link_pedido = "menu_pedido.php";
	if ($login_fabrica==19)	$link_pedido = "peca_reposicao_arvore.php";
	$link_at_shop = "lv_completa.php?produto_acabado=t";

	$link_pedido_nome		= strtoupper(traduz("pedido",$con,$cook_idioma));
	if ($login_fabrica==19)	$link_pedido_nome = strtoupper(traduz("pecas.de.reposicao",$con,$cook_idioma));
	$link_at_shop_nome = "AT SHOP";

//  Extrato
$link_extrato			= "os_extrato.php"                             ;
$link_extrato_nome		= strtoupper(traduz("extrato",$con,$cook_idioma));

//  Cadastro do Posto
$link_cadastro			= "menu_cadastro.php"                          ;
$link_cadastro_nome		= strtoupper(traduz("cadastro",$con,$cook_idioma));

//  Tabela de preços
$link_preco				= "tabela_precos.php";
if ($login_fabrica==19)	$link_preco = "produtos_arvore.php";

$link_preco_nome		= mb_strtoupper(traduz("tabela.de.preco",$con,$cook_idioma));

//  Informações técnicas / Vista Explodida...
$link_vista				= "info_tecnica_arvore.php";
if ($login_fabrica==11)	$link_vista = "linha_consulta.php";
if ($login_fabrica==14 or
	$login_fabrica== 3) $link_vista = 'comunicado_mostra_pesquisa_agrupado.php?acao=PESQUISAR';

$link_vista_nome		= strtoupper(traduz("vista.explodida",$con,$cook_idioma));
if ($login_fabrica==11)	$link_vista_nome = "TABELA DE M.O. E GARANTIA";
if ($login_fabrica== 3)	$link_vista_nome = strtoupper(traduz("documentacao.tecnica",$con,$cook_idioma));
if ($login_fabrica==14)	$link_vista_nome = strtoupper(traduz("informacoes.tecnicas",$con,$cook_idioma));

//  Informativos (Procedimentos para a Intelbras, HD 149520)
$link_informativo		= "comunicado_mostra.php?tipo=Informativo";
if ($login_fabrica== 3)	$link_informativo = "menu_tecnica.php";
if ($login_fabrica==14)	$link_informativo = "procedimento_mostra.php";


$link_informativo_nome = strtoupper(traduz("informativo.tecnico",$con,$cook_idioma));
if ($login_fabrica==1) $link_informativo_nome = "- ".strtoupper(traduz("informativo.tecnico",$con,$cook_idioma))." <br>- ".strtoupper(traduz("informativo.compressores",$con,$cook_idioma));
if($login_fabrica==14) $link_informativo_nome = strtoupper(traduz("procedimentos",$con,$cook_idioma));

//  Comunicados Administrativos
$link_comunicado		= "comunicado_mostra.php?tipo=Comunicado";
if ($login_fabrica==14)	$link_comunicado  = "comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Comunicado%20administrativo";
$link_comunicado_nome  = strtoupper(traduz("comunicado.administrativo",$con,$cook_idioma));

//  Forum
$link_forum				= "forum.php";
$link_forum_nome		= strtoupper(traduz("forum",$con,$cook_idioma));

//  Pesquisa de opinião / Estrutura do Produto
$link_pesquisa			= "treinamento_agenda.php";
if ($login_fabrica==14)	$link_pesquisa = "produto_consulta_detalhe.php";

$link_pesquisa_nome		= mb_strtoupper(traduz("treinamentos",$con,$cook_idioma));
if ($login_fabrica==14)	$link_pesquisa_nome = strtoupper(traduz("estrutura.do.produto",$con,$cook_idioma));

//  Requisitos do sistema
if($sistema_lingua == "ES") $param = "?sistema_lingua=ES";
$link_requisitos		= "javascript:;' onclick=\"window.open('configuracao.php$param','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')\"";
$link_requisitos_nome	= strtoupper(traduz("requisitos.do.sistema",$con,$cook_idioma));


//  Sair
$link_sair					= "logout_2.php";//"http://www.telecontrol.com.br";
if ($login_fabrica==20)	$link_sair = "http://www.bosch.com.br/assist/";
$link_sair_nome			= strtoupper(traduz("sair",$con,$cook_idioma));

/* HD 147696 - Linha alterada */
//  Manual para a Britânia
if ($login_fabrica== 3) $link_manual = "info_tecnica_arvore_manual.php";
if ($login_fabrica== 3) $link_manual_nome  = mb_strtoupper(traduz("manual.de.servico",$con,$cook_idioma));
$link_manual_nome = str_replace(" (", "<br>(", $link_manual_nome);
//--========================================================================================================--\\

//  MLG 11/02/2010 - A pedido to Túlio, a logo do fabricante deve vir do banco, e não setar "manual"...
$logo = @pg_fetch_result(@pg_query($con, 'SELECT logo FROM tbl_fabrica WHERE fabrica='.$login_fabrica), 0, 'logo');
//  MLG 31/03/2011 - HD 384115 - Atlas pediu uma imagem diferenciada para o Menu Inicial do Posto.
if ($login_fabrica == 74) $logo = 'atlas_saa_anim.gif';
 
$sql = "SELECT * 
		  FROM tbl_comunicado
		 WHERE tipo='Comunicado Inicial'
		   AND fabrica =  $login_fabrica
		   AND ativo   IS TRUE ";
if($login_fabrica==20) $sql .= " AND  pais = '$login_pais' ";

$sql .= "ORDER BY comunicado DESC LIMIT 1";

$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {
			$Xcomunicado           = trim(pg_result ($res,0,comunicado));
			$comunicado_mensagem   = trim(pg_result ($res,0,mensagem));
			$comunicado_titulo     = trim(pg_result ($res,0,descricao));
			$extensao              = trim(pg_result ($res,0,extensao));

			if ($extensao == null) {
				$a_ext = array('gif','jpg','pdf','doc','rtf','xls','ppt','zip');
                foreach ($a_ext as $ext) {
                	if (file_exists($com_file = "comunicados/$Xcomunicado.$ext")) break;
                }
			} else {
				$com_file = "comunicados/$Xcomunicado.$extensao";
			}

			if (file_exists($com_file)) {
			$link = "<a href='$com_file' target='_blank' style='font-weight:bold;color:red'>".traduz("veja.mais",$con,$cook_idioma)."</a>";
			}
}else{
	$comunicado_titulo   = traduz("bem.vindo.ao.assist",$con,$cook_idioma);
	$comunicado_mensagem = traduz("seja.bem.vindo.ao.sistema.assist.telecontrol.ele.foi.reformulado.para.atender.a.sua.necessidade.facilitando.assim.o.seu.trabalho",$con,$cook_idioma);
}
?>

<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
<script type="text/javascript">
	$().ready(function() {
		$('td a.conteudo img').hover(
            function() {    //  onMouseOver/onMouseEnter
				$(this).fadeTo('fast',1);
            },
            function() {    //  onMouseOut/onMouseLeave
				$(this).fadeTo('fast',.6);
            }
		);
	});
</script>

<style type="text/css">
/*  Tabela de resumo de OS para a Precision	*/
table#resumoOS {
	color: #333;
	width: 480px;
	margin:1em 0;
	font: normal normal normal 12px normal Verdana,Arial,Helvetiva,sans-serif;
	border-collapse: separate;
	border-spacing: 3px;
	border: 2px solid #d2e4fc;
	border-radius: 6px;
	-moz-border-radius: 6px;
    box-shadow: 3px 3px 1px #444;
    -o-box-shadow: 3px 3px 1px #444;
    -moz-box-shadow: 3px 3px 1px #444;
    -webkit-box-shadow: 3px 3px 1px #444;
}
table#resumoOS thead {background-color: #485989;color: white}
table#resumoOS tr:nth-child(even) {background-color: #eee}
table#resumoOS tr.bold {
	font-weight:bold;
	cursor: default;
}
table#resumoOS td {
	border: 1px dotted #aaa;
	text-align:center;
	cursor: s-resize;
}
table#resumoOS td a {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: xx-small;
	font-weight: normal;
    text-decoration: none;
	color:#596d9b;
}
table#resumoOS td p {font: normal normal 10px Verdana, Geneva, Arial, Helvetica, sans-serif;}
table#resumoOS td a:hover {color: #405080;text-decoration: underline}
div.oculto {text-align: left;padding: 8px 16px;}

img#gChart {
	border: 2px solid #5989FF;
	margin:1em 0;
	padding: 0.5em 0.7em;
	background-image: -moz-linear-gradient(top, CDDDFF, EDEDFF);
    background-image: -webkit-gradient(linear, 0 0, 0 100%, from(CDDDFF), to(EDEDFF));
             /*
             -moz-linear-gradient([<bg-position> | <angle>,]? <color-stop>, <color-stop>[, <color-stop>]*);
             *color-stop syntax: ' color (name, hex, rbg, rgba...) percentage (0..1 or %)'
             -webkit-gradient(type, start_point, end_point, from(color), to(color)[, color-stop(point, color)...])
             */
    filter: progid:DXImageTransform.Microsoft.Gradient(GradientType=1,StartColorStr='CDDDFF',EndColorStr='EDEDFF');
	border-radius:8px;
	-moz-border-radius:8px;
    box-shadow: 0 0 8px #444;
    -o-box-shadow: 0 0 8px #444;
    -moz-box-shadow:  0 0 8px #444;
    -webkit-box-shadow: 0 0 8px #444;
    *filter:progid:DXImageTransform.Microsoft.DropShadow(color='#444', offX=2, offY=1,enabled=true,positive='false');
}
</style>

<!-- HD 214236 -->
<style>
.tabela_auditoria {
    border:1px solid #d2e4fc;
    background-color:#485989;
}

.inicio_auditoria {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    color: #FFFFFF;
    padding-right: 1ex;
    text-transform: uppercase;
}

.conteudo_auditoria {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #F4F7FB;
}

.titulo2_auditoria {
    font-family: Arial;
    font-size: 7pt;
    text-align: center;
    color: #000000;
    background: #ced7e7;
    text-transform: uppercase;
}

.menu_title {
	text-align: center;
	background-color: <?=$cor?>;
}
.menu_title_forum {
	text-align: center;
	background-color: <?=$corforum?>;
}
.menu_title:hover, .menu_title_forum:hover {
	background-color: <?=$cor2?>;
}

div#menu_posto{
	text-align: center;
}

div#menu_posto ul{
	margin: 0;
	padding: 0;
	text-align: center;
}

div#menu_posto li{
	border: 1px solid <?=$cor?>;;
	margin: 0;
	padding: 0;
	list-style: none;
	position: relative;
	height: 90px;
	width: 179px;
	margin: 3px;
	margin-right: 0;
	margin-bottom: 0;
	float: left;
}

div#menu_posto li span{
	text-align: center !important;


}

div#menu_posto li span img{
	height: 48px;
	margin: 10px auto;
}


div#menu_posto li p{
	position: absolute;
	left: 0;
	bottom: 0;
	text-align: center;
	background-color: <?=$cor?>;
	color: #FFF;
	padding: 2px 0;
	margin: 1px;
	width: 177px;
}
</style>
<?php

if (strlen($cookie_login['cook_login_unico']) > 0) {
	include "cabecalho.php";?>
	<style>
	a.conteudo{
		color: #FFFFFF;
		font-family: Arial;
		FONT-SIZE: 8pt;
		font-weight: bold;
		text-decoration: none;
		text-align: center;
	}
	a.conteudo:visited {
		color: #FFFFFF;
		FONT-SIZE: 8pt;
		font-weight: bold;
		text-decoration: none;
		text-align: center;
	}

	a.conteudo:hover {
		color: #FFFFCC;
		FONT-SIZE: 8pt;
		font-weight: bold;
		text-decoration: none;
		text-align: center;
	}

	a.conteudo:active {
		color: #FFFFFF;
		FONT-SIZE: 8pt;
		font-weight: bold;
		text-decoration: none;
		text-align: center;
	}

	.Tabela{
		border:1px solid #d2e4fc;
	}
	.rodape{
		color: #FFFFFF;
		font-family: Arial, Helvetica, sans-serif;
		font-size: 9px;
		background-color: #FF9900;
		font-weight: bold;
	}
	img{
	border:0px;
	}
	.mensagem {
		color:     #000033;
		font-size: 13px;
	}
	.fundo {
	background-position: bottom left ;
	background-image: url('logos/logo_tc_2009_md.gif') ;
	background-repeat: no-repeat ;
	width: 152px ;
	height: 80px ;
	}

	td a.conteudo img {
		opacity: .6;
	    filter:alpha(opacity=65);
	}
</style>

<table width='100%'  border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td align='center' valign='top'><?php
} else {
    
    $sql = "SELECT digita_os, pedido_faturado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
    $res = @pg_query($con, $sql);
    if(pg_num_rows($res)){
        $digita_os = pg_fetch_result($res, 0, 'digita_os');
        $pedido_faturado = pg_fetch_result($res, 0, 'pedido_faturado');
    }
    ?>
<title><?=$title?></title>
<script language="JavaScript1.2">
<?if($login_fabrica<>20 and $login_fabrica<>3){?>
/*function carga(){
	var carga     = document.getElementById('voip');
	carga.style.visibility = "visible";
	setTimeout('esconde_carregar()',6000);
}
function esconde_carregar() {
	document.getElementById('voip').style.visibility = "hidden";
}
*/
<?}?>
</script>
<style>
body {
	text-align: center;
	font-family:Arial;
	margin: 0px,0px,0px,0px;
	padding:  0px,0px,0px,0px;
}

a.conteudo{
	color: #FFFFFF;
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}
a.conteudo:visited {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:hover {
	color: #FFFFCC;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:active {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

.Tabela{
	border:1px solid #d2e4fc;
}
.rodape{
	color: #FFFFFF;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 9px;
	background-color: #FF9900;
	font-weight: bold;
}
img{
border:0px;
}
.mensagem {
	color: #0099FF;
	font-size: 13px;
}
.fundo {
background-position: bottom left ;
background-image: url('logos/logo_tc_2009_md.gif') ;
background-repeat: no-repeat ;
width: 152px ;
height: 80px ;}

a:hover:visited{
color:#FF0000;
}
#animado {
	background: #FFFFFF;
	position: absolute;
	border: 1px solid #DCDCDC;
	left: 10px;
	top: 10px;
	float: right;
}
#animado h3 {
	font-family: Verdana;
	color: #fff;
}
#voip{
	BORDER-RIGHT: #6699CC 2px solid;
	BORDER-TOP: #6699CC 2px solid;
	BORDER-LEFT: #6699CC 2px solid;
	BORDER-BOTTOM: #6699CC 2px solid;
	FONT: 10pt Arial ;
	COLOR:            #6699CC;
	BACKGROUND-COLOR: #F2F7FF;
	position: absolute; top: 40px; right: 5px;
}

td a.conteudo img {
	opacity: .6;
    filter:alpha(opacity=65);
}
</style>

<? /*COLOCADO APENAS PARA INTELBRAS, CORTESIA POR TINHA MTA PENDENCIA, TAKASHI 19-12-07*/?>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="screen">
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<script type="text/javascript">
	Shadowbox.init();
//Ederson Sandre
	function buscaPecaCatalogoPecas(){
		Shadowbox.open({
			content:	"catalogo_jacto.php?modulo=visualizacao",
			player:	"iframe",
			//modal: true,
			width:	1024,
			title:		"Catálogo de Peças"
		});
	}

	$(function() {
	    $("#animado").animate({left: 450, top: 200}, 5500);

	//  HD 170502
		$('.oculto').hide();
		$('.toggle_data').click(function () {
		    var dias = $(this).attr('dias');
		    var item = "#data_"+dias;
		    if ($(item).html().length > 30) {
				$(item).toggle('normal');
			} else{
				$(item).html('<p>Aguarde...</p>').show('normal');
		// 	alert ("Consultando OS de até "+dias+" dias...");
			$.get('posto_consulta_os_aberto.php',
					{'ajax'	:'consulta',
					 'dias'	: dias},
					function(data) {
				    if (data == 'ko' || data == undefined) {
						    $(item).text('Erro ao consultar as OS. Tente em alguns minutos.');
						} else if (data == 'NO RESULTS' || data.indexOf('<p>') != 0) {
						    $(item).html('').hide('fast');
						} else {
		// 				    alert (data);
							$(item).html(data).show('normal');
						}
			});
			}
		});
		function fechar(){
			$("#animado").toggle();
		}
	});

	function abrejanela() {
		var janela = null
		janela=window.open('imagens/intelbras_qualidade.html','Intelbrás','toolbar=no,location=no,status=yes,menubar=no,scrollbars=yes,resizable=no,width=450,height=500, left=10, top=10');
	}


</script>
</head>
<?  /*COLOCADO APENAS PARA INTELBRAS, CORTESIA POR TINHA MTA PENDENCIA, TAKASHI 19-12-07*/
//<body onload='carga();'> HD 34540 2/9/2008 - Esta mostrando um erro na tela porque a função está comentada?>
<body>
<? /*COLOCADO APENAS PARA INTELBRAS, CORTESIA POR TINHA MTA PENDENCIA, TAKASHI 19-12-07*/

/*
if($login_fabrica==14 ){


<div id="animado">
<a href='javascript:fechar()'><font face='verdana' size='1' color='#000000'>Fechar</font></a><BR>
<a href='javascript:abrejanela()'><IMG SRC="imagens/intelbras_qualidade.jpg" ALT=""></a>
</div>


*/
 /*COLOCADO APENAS PARA INTELBRAS, CORTESIA POR TINHA MTA PENDENCIA, TAKASHI 19-12-07*/
/*
 }else{
	<div id="animado">
	<a href='javascript:fechar()'><font face='verdana' size='1' color='#000000'>Fechar</font></a><BR>
	<a href='#'><IMG SRC="imagens/divulgacao_voip.png" ALT=""></a>
	</div>

}

*/
?>
<?
if ($login_fabrica ==1) {
	// ! HD 121248 - Criar botão de Help-Desk para gerar/listar callcenter. Todos os postos de testes podem ver por padrão (augusto)
	include_once 'helpdesk.inc.php';

	if( hdPermitePostoAbrirChamado() ) {
		$possuiChamadosPendentes 	= (boolean) hdPossuiChamadosPendentes();
		$strHrefHelpDesk			= ( $possuiChamadosPendentes ) ? 'helpdesk_listar.php' : 'helpdesk_cadastrar.php' ;
		$strImgHelpDesk				= ( $possuiChamadosPendentes ) ? 'help-vermelho.gif' : 'help.png';
		$strTitleHelpDesk			= ( $possuiChamadosPendentes ) ? 'Ver chamados pendentes' : 'Cadastrar novo chamado para entrar em contato com a fábrica';
	?>
	<a href="<?php echo $strHrefHelpDesk; ?>" style=" top: 0px; right: 0px; position: absolute;" title="<?php echo $strTitleHelpDesk; ?>">
		<img src="/assist/helpdesk/imagem/<?php echo $strImgHelpDesk; ?>" border="0" style="width: 36px; height: 36px;position:absolute;right:10px;float:right;top:1px;font-size: 0.7em;padding:0;margin:0 " >
	</a>
	<?php
		unset($possuiChamadosPendentes,$strHrefHelpDesk,$strImlHelpDesk,$strTitleHelpDesk);
		unset($aExibirHelpDesk);
	}
}
?>
<center>
<table width='100%' height='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td align='center' valign='top'>


<?
if($login_fabrica == 87) 
	$table_menu = "700px"; 
else 
	$table_menu = "762px";

echo "<table width='$table_menu' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
	if($login_fabrica == 87){
		echo "<td align='right'><a href='$link_sair' title='Sair do Sistema'><img src='imagens/aba/sair.gif' border='0'></a></td>";
	}else{
        if(!in_array($login_fabrica, array(87)) OR (in_array($login_fabrica, array(87)) AND $digita_os == 't')){
            //aba OS
            echo "<td><center><a href='$link_os'><img src='imagens/aba/";
            if($sistema_lingua) echo "es_";
            echo "os";
            if ($layout_menu == "os") echo "_ativo";
            echo ".gif' border='0'></a>";
        }
		//aba INFORMAÇÕES TÉCNICAS
		echo "<a href='$link_vista'>";
		echo"<img src='imagens/aba/";
		if($sistema_lingua) echo "es_";
		echo "info_tecnico";
		if ($layout_menu == "tecnica") echo "_ativo";
		echo ".gif' border='0'></a>";


		if($login_fabrica <>19){
			//aba PEDIDO
			if($login_fabrica <>20){
                if(!in_array($login_fabrica, array(87)) OR (in_array($login_fabrica, array(87)) AND $pedido_faturado == 't')){
                    echo "<a href='menu_pedido.php'><img src='imagens/aba/pedidos";
                    if ($layout_menu == "pedido") echo "_ativo";
                    echo ".gif' border='0'></a>";
                }
			}
			// aba CADASTRO

			echo "<a href='menu_cadastro.php'><img src='imagens/aba/";
			if($sistema_lingua) echo "es_";
			echo "cadastro";
			if ($layout_menu == "cadastro") echo "_ativo";
			echo ".gif' border='0'></a>";

			//aba TABELA DE PREÇO
			if($login_fabrica <>20 and $login_fabrica <> 15){
			echo "<a href='menu_preco.php'><img src='imagens/aba/tabela_preco";
			if ($layout_menu == "preco") echo "_ativo";
			echo ".gif' border='0'></a>";
			}
		}else{
			echo "<a href='peca_reposicao_arvore.php'><img src='imagens/aba/peca_reposicao";
			if ($layout_menu == "reposicao") echo "_ativo";
			echo ".gif' border='0'></a>";

			// aba CADASTRO
			echo "<a href='produtos_arvore.php'><img src='imagens/aba/produtos";
			if ($layout_menu == "produtos") echo "_ativo";
			echo ".gif' border='0'></a>";

			// aba LANÇAMENTOS
			echo "<a href='lancamentos_arvore.php'><img src='imagens/aba/lancamentos";
			if ($layout_menu == "lancamentos") echo "_ativo";
			echo ".gif' border='0'></a>";

			// aba INFORMATIVOS
			echo "<a href='informativos_arvore.php'><img src='imagens/aba/informativos";
			if ($layout_menu == "informativos") echo "_ativo";
			echo ".gif' border='0'></a>";

			// aba PROMOÇÕES
			echo "<a href='promocoes_arvore.php'><img src='imagens/aba/promocoes";
			if ($layout_menu == "promocoes") echo "_ativo";
			echo ".gif' border='0'></a>";
		}

		//aba SAIR
		echo "<a href='$link_sair'>";
		echo "<img src='imagens/aba/";
		if($sistema_lingua) echo "es_";
		echo "sair.gif' border='0'></a></center></td>";

		if($login_fabrica<>20 and $login_fabrica<>3){
			$sql = "SELECT * FROM tbl_posto_fabrica WHERE posto = $login_posto AND credenciamento <> 'DESCREDENCIADO' AND fabrica = 49";
			$resX = pg_exec ($con,$sql);
			if (pg_numrows ($resX) == 1 ) {
		//echo "<div id='voip' class='Chamados2' style='position: absolute;visibility:hidden'><a href='voip_adesao.php' target='_blank'><img src='imagens/divulgacao_voip.png' onmouseout=\"javascript:document.getElementById('voip').style.visibility='hidden'\"></a></div>";
		//onmouseover=\"javascript:document.getElementById('voip').style.visibility='visible'\"
		//	echo "<td><a href='voip_ligacao.php' target='_new'><img src='http://www.telecontrol.com.br/imagens/fone-ico.jpg' height='25' alt='Tele-VoIP' onmouseover=\"javascript:document.getElementById('voip').style.visibility='visible' \"></a></td>";
			}else{
			//echo "<td><img src='http://www.telecontrol.com.br/imagens/fone-ico.jpg' height='25' alt='Tele-VoIP' onmouseover=\"javascript:document.getElementById('voip').style.visibility='visible'\" ><div id='voip' class='Chamados2' style='position: absolute;visibility:hidden'><a href='voip_adesao.php' target='_blank'><img src='imagens/divulgacao_voip.png' onmouseout=\"javascript:document.getElementById('voip').style.visibility='hidden'\"></a></div></td>";
			}
	}
}
echo "</tr>";
echo "</table>";
echo "<TABLE width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td background='/assist/imagens/submenu_fundo_cinza.gif' colspan='8'>&nbsp;</td>";
echo "</tr>";
echo "</table>";

}
if($sistema_lingua) $comunicado_titulo   = "Bienvenido a Assist";

if($login_fabrica == 43){
	include ('posto_medias.php');
}

$logoImg   = "logos/$logo";
$logo_attr = logoSetSize($logoImg);
?>

<table width="700px" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'  style='width: 745px;'>
	<tr>
		<td colspan='4'>
			<table width="740px" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
						<td  class='mensagem' width='180' align='center'>
							<img src="<?=$logoImg;?>" <?=$logo_attr?> alt="Bem-Vindo!!!">
							<?if ($login_fabrica==20 and $bandeira) echo "<img src='imagens/$bandeira'"?>
						</td>
						<td><div class='mensagem'>
							<?
							echo "<b>$comunicado_titulo</b>";
							echo "<br><br>$comunicado_mensagem";
							echo "<br>$link";
							?></div>
						</td>
						<?
							if($login_fabrica == 87){
								//697789 - Padronização do Layout para Jacto
							}else{
								if(strlen($cookie_login['cook_login_unico'])==0){?>
									<td width='200' align='center'><IMG SRC="logos/logo_tc_2009_md.gif" width='190' ALT="Bem-Vindo!!!"></td>
							<?	}
							}?>
				</tr>
				<?
				/* HD 49740 - Desabilitado */
				if (1 == 2 AND (($login_posto == "4311" OR $login_posto == "20321") AND ($login_fabrica == 3 OR $login_fabrica == 11)) ) {
				echo "<tr>\n
					<td  align='right' width='180'><a href='distrib/' class='conteudo'><img src='imagens/botoes/distrib.jpg'></a></td>\n
					<td colspan='2' class='menu_title'><A HREF='distrib/' class='conteudo'> <center>Clique aqui para acessar o Distrib </center></a></td>\n
					</tr>\n" ;
				}

				?>
			</table>
		</td>
	</tr>
	<?
/*
if($condicao_descricao=="30/60/90DD (financeiro de 3%)"){ $condicao ="55"; }
	if($condicao_descricao=="30/60DD (financeiro de 1,5%)"){ $condicao ="53"; }
	if($condicao_descricao=="30DD (sem financeiro)"){ $condicao ="51"; }
	if($condicao_descricao=="45DD (financeiro 1,5%)"){ $condicao ="52"; }
	if($condicao_descricao=="60/90/120DD (financeiro 6,1%)"){ $condicao ="57"; }
	if($condicao_descricao=="60/90DD (financeiro 4,5%)"){ $condicao ="73"; }
	if($condicao_descricao=="60DD (financeiro 3%)"){ $condicao ="54"; }
	if($condicao_descricao=="90DD (financeiro 6,1%)"){ $condicao ="56"; }*/

	if ($login_fabrica == 1) {

		$btn_condicao = $_POST['btn_condicao'];
		if ($btn_condicao == 'Confirmar') {
			$condicao = $_POST['condicao'];
/*takashi -  nao estava gravando id_condicao de pagamento - 11-01-07*/
			if($condicao == "30/60/90DD (financeiro de 3%)"){ $id_condicao = "55"; }
			if($condicao == "30/60DD (financeiro de 1,5%)"){  $id_condicao = "53"; }
			if($condicao == "30DD (sem financeiro)"){         $id_condicao = "51"; }
			if($condicao == "45DD (financeiro 1,5%)"){        $id_condicao = "52"; }
			if($condicao == "60/90/120DD (financeiro 6,1%)"){ $id_condicao = "57"; }
			if($condicao == "60/90DD (financeiro 4,5%)"){     $id_condicao = "73"; }
			if($condicao == "60DD (financeiro 3%)"){          $id_condicao = "54"; }
			if($condicao == "90DD (financeiro 6,1%)"){        $id_condicao = "56"; }
/*takashi -  nao estava gravando id_condicao de pagamento - 11-01-07*/

			$sql = "INSERT INTO tbl_black_posto_condicao (posto, condicao, id_condicao) VALUES ($login_posto, '$condicao', $id_condicao)";
			$resX = pg_exec ($con,$sql);
			echo "<script language='javascript'> location.href=\"$PHP_SELF\" ; </script>";
			exit;
		}

		$sql = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $login_posto AND codigo_posto IN ('10068','10086','10097','10120','10177','10240','10345','10358','10378','10678','10844','11147','11228','11290','12004','12008','12009','12010','12011','12012','12012','12016','12017','12019','12027','12030','12058','12059','12115','12120','12124','12129','12138','13008','13014','13031','13035','13053','13072','13074','13076','13077','13088','13109','13114','13128','13136','13150','13155','13161','13201','13270','13515','13516','13632','13635','13695','13715','13786','13812','14048','14049','14055','14135','14162','14166','14187','14189','14228','14246','14426','14675','14975','15007','15024','15026','15031','15034','15036','15037','15046','15047','15097','15111','15113','15199','16001','20034','20165','20223','20274','20285','20312','20322','20333','20336','20403','20439','20621','20653','20670','20763','20920','20998','21061','21139','21150','21163','21215','21217','21272','21292','21350','21362','21436','21464','21661','21815','21870','21914','21957','22002','22010','22086','22116','22426','22439','22457','22612','22624','22625','22626','22632','22689','22720','22941','23019','23098','23111','23135','23150','23154','23155','23158','23160','23163','23163','23193','23194','23195','23215','23225','23227','23241','23284','23290','23292','23293','23331','23340','23345','23350','23354','23355','23358','23359','23360','23361','23364','23368','23369','23373','23510','23511','23513','23642','23915','23921','23925','24142','24212','24327','24367','24394','24567','26934','26942','26943','26946','26948','26949','26954','26955','26956','26957','26958','29067','29078','29079','30003','30048','30052','31057','31351','32007','32022','32027','32057','32058','32074','32085','31855','33009','33018','33027','33029','33034','34022','34087','35021','35053','35066','35143','36033','36039','36086','36107','36307','36778','36888','37040','37043','38021','38034','38036','38039','38774','39075','39512','39600','39730','39870','39874','40031','40077','40092','40113','40305','40399','40563','40979','41086','41272','41665','41683','42076','42124','42308','42362','42375','42376','42385','42408','43380','43743','45007','45127','45889','48838','50061','50087','50139','51092','51097','51167','51168','51199','51734','51735','51738','51771','51774','51782','51787','52006','52021','52028','52043','52047','52051','52055','52200','52208','53129','53130','53155','53175','53463','54006','54020','54025','54029','54032','54037','54038','54049','54050','54089','55036','55122','55142','55143','55153','55155','55159','55170','55200','55241','55270','56016','56089','56153','56176','56198','56267','56305','56368','56463','56875','56876','56977','57215','57432','57582','57719','57873','57874','57972','58022','58036','58142','58219','58329','58558','58774','10121','10341','10454','10665','58414','10674','10843','11399','12137','43745','43244','10698','11529','12015','12031','58876','42450','12045','12124','12127','12129','14017','14300','14717','14975','15022','15035','15040','15045','15048','15101','20072','20331','20341','20370','20398','20417','20741','21149','21302','21351','21480','21551','21565','21972','22007','22497','22530','22585','22588','22627','22631','22670','22710','22893','23156','23167','23183','23184','58589','23413','23189','23197','23283','23298','23316','23371','23372','23374','23380','23381','23382','23387','23388','23440','23544','23980','24348','26950','26953','26955','26960','26961','26962','26963','27907','29060','29062','29083','30002','31021','31129','31141','31290','31310','31395','32008','32024','32029','34135','36788','37583','38038','39545','39600','39692','39791','39874','40007','40049','40052','40053','40082','40092','40097','40143','40444','40598','40885','40907','40921','41066','41176','41221','41280','41309','41472','41694','41979','42031','42125','42270','42280','42297','42404','42409','43027','43051','43201','43779','43818','44003','45010','45126','50004','51782','52198','53440','53441','54024','54056','55026','55049','55146','55874','56047','56097','56146','56376','56480','57299','57602','58108','58423','58879','08481','99065','53446','54004','11245','29057','22461','57136','20104','57759','29066','13610','13610','57779','21801','20332','20376','20345','22049','29065','20208','58262','13072','20383','58274','99243','58267','13074','20346','57693','58395','58815','57599','58839','58841','22514','42297','20443','14315','52178')";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) > 0) {
			$sql = "SELECT * FROM tbl_black_posto_condicao WHERE posto = $login_posto";
			$resX = pg_exec ($con,$sql);
			if (pg_numrows ($resX) == 0) {
				echo "<tr bgcolor='$cor'>";
				echo "<td colspan='4' align='center'>";
				echo "<font color='#ffffff'><b>".traduz("condicao.de.pagamento.padronizada",$con,$cook_idioma)."</b></font>";
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td colspan='4' align='center'>";
				echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
				echo "Prezado Assistente, <p align='left'>
					Por exigência da Corporação a Black & Decker do Brasil estará mudando seu software operacional no dia 02/01/2007. Essa alteração implicará em nossa sistemática de faturamento. No entanto, a única mudança que irá interferir para o posto de serviço é relativa às condições de pagamento dos pedidos. Explicando melhor, hoje o posto de serviço tem a opção de escolher na hora da colocação de um pedido a condição de pagamento. Com o novo sistema, o posto de serviço ainda poderá determinar sua condição de pagamento, porém, após essa escolha a condição será padronizada e fixa para todos seus pedidos sem opção de alterá-la.
					Através deste comunicado, solicitamos que escolha uma das condições abaixo para efetuarmos as validações a partir de 02/01/2007.
					<p><b>IMPORTANTE</b>: A condição escolhida agora será permanente e única para todos os pedidos colocados a partir de 02/01/2007.";
				echo "<p> Condições a escolher: ";
				echo "<select name='condicao' size='1'>";
				echo "<option value='30DD (sem financeiro)'        >30DD (sem financeiro)        </option>";
				echo "<option value='30/60DD (financeiro de 1,5%)' >30/60DD (financeiro de 1,5%) </option>";
				echo "<option value='30/60/90DD (financeiro de 3%)'>30/60/90DD (financeiro de 3%)</option>";
				echo "<option value='45DD (financeiro 1,5%)'       >45DD (financeiro 1,5%)       </option>";
				echo "<option value='60DD (financeiro 3%)'         >60DD (financeiro 3%)         </option>";
				echo "<option value='60/90DD (financeiro 4,5%)'    >60/90DD (financeiro 4,5%)    </option>";
				echo "<option value='60/90/120DD (financeiro 6,1%)'>60/90/120DD (financeiro 6,1%)</option>";
				echo "<option value='90DD (financeiro 6,1%)'       >90DD (financeiro 6,1%)       </option>";
				echo "</select>";

				echo "<p>Desde já agradecemos a compreensão.";
				echo "<p>Departamento de Assistência Técnica";
				echo "<br>Black & Decker do Brasil";

				echo "<p><input type='submit' name='btn_condicao' value='Confirmar'>";
				echo "</form>";
				echo "</td>";
				echo "</tr>";
			}
		}
	}
	?>
<?if ($login_fabrica == 3){
	$hoje = date("Y-m-d");
	$sql3="select SUM(data_expira_senha-'$hoje') as data from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica;";
	$res3 = pg_exec($con, $sql3);
	if(pg_numrows($res3) > 0){
		$data_expira_senha= pg_result($res3,0,data);
	}
?>
	<tr>
		<td colspan='4' align='center'><a href='alterar_senha.php'><font size='1' face='arial,verdana' style='color:#85858B; text-decoration: none'><?fecho("sua.senha.ira.expirar.em",$con,$cook_idioma)." ";?><? echo " $data_expira_senha "; ?> <? fecho("dias",$con,$cook_idioma);echo ". ";fecho("clique.aqui.para.cadastrar.uma.senha.nova",$con,$cook_idioma);?></font></a></td>
	</tr>
<?}?>


<?
if ($login_fabrica==80 or $login_fabrica==81) {
	$mostra_grafico = false;
	$anterior = 0;
	$os_dias = Array();
    for ($i=5; $i<36; $i+=10) {
		$sql = "SELECT DISTINCT count(posto),os,count(os_produto) AS qtde_itens
					    FROM tbl_os
					    LEFT JOIN tbl_os_produto USING(os)
					  WHERE fabrica	= $login_fabrica
					    AND posto	= $login_posto
					    AND data_fechamento IS NULL
					    AND tbl_os.excluida IS NOT TRUE
					    AND data_abertura::date BETWEEN current_date - INTERVAL '$i days' AND current_date - INTERVAL '$anterior days'
					  GROUP BY posto,os
		";

//  Mais de 30 dias...:
		if ($anterior==26) {
			$sql = "SELECT DISTINCT count(posto),os,count(os_produto) AS qtde_itens
					    FROM tbl_os
					    LEFT JOIN tbl_os_produto USING(os)
					  WHERE fabrica	= $login_fabrica
					    AND posto	= $login_posto
					    AND data_fechamento IS NULL
					    AND tbl_os.excluida IS NOT TRUE
					    AND data_abertura::date < current_date-INTERVAL '25 days'
					  GROUP BY posto,os
		";
		}
		$res = pg_query($con, $sql);
		$os_dias[$i]["total"] = pg_num_rows($res);
		$mostra_grafico = ($mostra_grafico or $os_dias[$i]["total"] != 0);
// 		if ($os_dias[$i]["total"] == 0) continue;
		$num_row = 0;
        while (is_array($row = @pg_fetch_assoc($res, $num_row++))) {
		    $os_dias[$i]["sem_pecas"] += intval(($row['qtde_itens']=="0"));
        }
        if ($os_dias[$i]["total"]==0) $os_dias[$i]["sem_pecas"] = 0;
        $os_dias[$i]["com_pecas"] = $os_dias[$i]["total"] - $os_dias[$i]["sem_pecas"];
		$anterior = $i + 1;
    }
	if ($mostra_grafico) {?>
	<tr>
	<td colspan='4' align='center'>
	<table align='center' id='resumoOS'>
		<thead>
		<tr class='conteudo'>
			<th>Até...</th>
			<th>Sem peças</th>
			<th>Com pedido</th>
			<th>Total</th>
		</tr>
		</thead>
		<tbody>
<?
	$anterior= 0;
	foreach ($os_dias as $dias => $dados) {
		echo "\t<tr title='Clique para visualizar as OS'>\n";
		echo "\t\t<td class='toggle_data' dias='$dias'>";
		echo ($dias==35)?"> 25":"De $anterior até $dias";
		echo " dias</td>\n";
		echo "\t\t<td class='toggle_data' dias='$dias'>{$dados['sem_pecas']}</td>\n";
		echo "\t\t<td class='toggle_data' dias='$dias'>{$dados['com_pecas']}</td>\n";
		echo "\t\t<td class='toggle_data' dias='$dias'>{$dados['total']}</td>\n";
		echo "\t</tr>\n";
		echo "\t<tr id='fila_data_$dias'>\n";
		echo "<td colspan='4'><div class='oculto' id='data_$dias'></div></td></tr>\n";
		$total	+= $dados['total'];
		$sem    += $dados['sem_pecas'];
		$com    += $dados['com_pecas'];
		$anterior = $dias + 1;
	}
?>		<tr class='bold'>
			<td>TOTAIS:</td>
			<td><?=$sem?></td>
			<td><?=$com?></td>
			<td><?=$total?></td>
		</tr>
		</tbody>
	</table>
<?
	$os_dias['t']['total'] = $total;
	$os_dias['t']['sem_pecas'] = $sem;
	$os_dias['t']['com_pecas'] = $com;
//  Agora, muntar a query para o GoogleChart...
	foreach ($os_dias as $dias => $dados) {
	    $max = ($dados['total'] > $max) ? $dados['total'] : $max;
	    $a_data_sem[] = $dados['sem_pecas'];
	    $a_data_com[] = $dados['com_pecas'];
	    $a_totais[]   = $dados['total'];
	}
	$chart_data = implode(",",$a_data_sem);
	$chart_data.= "|".implode(",",$a_data_com);
	$max = max($sem,$com);
	$max = ($max<10) ? 10 : intval(intval(($max)/pow(10,strlen($max)-1))+1)*pow(10,strlen($max)-1);
	$chart_height = 100*strlen($max);

	$chart = "http://chart.apis.google.com/chart?";
	$chart.= "chs=640x".$chart_height."&cht=bvg&chbh=r,0.2,0.8&";		//tipo e tamanho, largura e espaço entre barras
	$chart.= "chco=00D07F|F0F07F|0000FF|FF0000|991111,008040|FF8000|7F00F0|7F3000|2F2F2F&";		//cores das barras
	$chart.= "chf=bg,lg,60,CDDDFF,1,EDEDFF,0&";	//cor de fundo
	$chart.= "chtt=OS+em+aberto&";   //  Título da imagem
	$chart.= "chxt=x,y,r&chxl=0:|At&#233;+5+dias|6-15+dias|16-25+dias|Mais+de+25+dias|Total|&";
	$chart.= "chdl=&lt;5+Dias+Sem+pe&#231;a|6-15+Dias+Sem+pe&#231;a|16-25+Dias+Sem+pe&#231;a|&gt;25+Dias+Sem+pe&#231;a|Total+Sem+pe&#231;as|".
				  "&lt;5+Dias+Com+pe&#231;a|6-15+Dias+Com+pe&#231;a|16-25+Dias+Com+pe&#231;a|&gt;25+Dias+Com+pe&#231;a|Total+Com+pe&#231;as&".
				  "chdlp=t&";	//	Legenda de cores...
	$chart.= "chm=N*f0*+OS,000000,0,-1,12|N*f0*+OS,000099,1,-1,12&"; // Texto em cima das barras
//	Legenda de cores... "chdl=Sem+pedido|Com+pedido&chdlp=b&"
	$chart.= "chd=t:$chart_data&chds=0,$max".
		 "&chxr=1,0,$max|2,0,$max";
?> 	<img id='gChart' src='<?=$chart?>' alt='Resumo de OS em aberto'>
	</td>
</tr>
<?	}
}?>

<? if ($login_fabrica == 40){ ?>
	<tr>
		<td colspan='4' align='center' style='color:#DD0000; font-weight: bold; padding: 10px;'>
		<?
		$sql = "
		SELECT
		os
		
		FROM
		tbl_os
		
		WHERE
		data_abertura < NOW() - INTERVAL '20 day'
		AND data_fechamento IS NULL
		AND posto=$login_posto
		AND fabrica=$login_fabrica
		AND excluida IS NOT TRUE
		
		LIMIT 1";
		@$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			echo "Existem OS em aberto a mais de 20 dias. Verificar com urgência!";
		}
		?>
		
		</td>
	</tr>
<? }

//HD 214236: Colocar um resumo para o posto do que foi auditado e ele ainda não alterou
if ($login_fabrica == 14) { // retirado a fabrica 43 a pedido do Ébano, hd 316707
	$sql = "
	SELECT
	tbl_os.sua_os,
	tbl_os.os,
	tbl_os_auditar.os_auditar,
	CASE
		WHEN tbl_os_auditar.cancelada IS TRUE THEN 'Recusada'
		WHEN tbl_os_auditar.liberado IS TRUE THEN 'Aprovada'
	END AS Status,
	tbl_os_auditar.cancelada,
	tbl_os_auditar.liberado,
	TO_CHAR(tbl_os_auditar.data, 'DD/MM/YYYY HH24:MI') AS data ,
	TO_CHAR(CASE
		WHEN tbl_os_auditar.liberado_data IS NOT NULL THEN tbl_os_auditar.liberado_data
		WHEN tbl_os_auditar.cancelada_data IS NOT NULL THEN tbl_os_auditar.cancelada_data
		ELSE null
	END, 'DD/MM/YYYY HH24:MI') AS data_saida,
	tbl_os_auditar.justificativa

	FROM
	tbl_os
	JOIN tbl_os_auditar ON tbl_os.os=tbl_os_auditar.os
						AND tbl_os_auditar.fabrica=tbl_os.fabrica
						AND tbl_os_auditar.os_auditar=(
							SELECT MAX(os_auditar)
							FROM tbl_os_auditar AS ultima_auditoria
							WHERE ultima_auditoria.os=tbl_os.os
						)
						AND tbl_os_auditar.alterada_data IS NULL
						AND tbl_os_auditar.cancelada
	JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os

	WHERE
	tbl_os.posto=$login_posto
	AND tbl_os_extra.extrato IS NULL
	AND tbl_os.fabrica=$login_fabrica
	";
	@$res = pg_query($con, $sql);
	$n = pg_numrows($res);

	if ($n > 0) {
		echo "
		<TR><TD colspan='4'>
		<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='tabela_auditoria'>
			<TR>
				<TD class='inicio_auditoria' style='text-align:center;' colspan='5' width='700'>
				AUDITORIA PRÉVIA - AS OS ABAIXO FORAM AUDITADAS PELA FÁBRICA, FAVOR DAR CONTINUIDADE
				</TD>
			</TR>
			<TR align='center'>
				<TD class='titulo2_auditoria' align='center' width='70'>OS</TD>
				<TD class='titulo2_auditoria' align='center' width='70'>Status</TD>
				<TD class='titulo2_auditoria' align='center' width='70'>Data Entrada</TD>
				<TD class='titulo2_auditoria' align='center' width='70'>Data Saída</TD>
				<TD class='titulo2_auditoria' align='center' width='490'>Justificativa</TD>
				<TD class='titulo2_auditoria' align='center' width='70'>Ação</TD>
			</TR>";
		
		for ($i = 0; $i < $n; $i++) {
			//Recupera os valores do resultado da consulta
			$valores_linha = pg_fetch_array($res, $i);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha);

			if ($liberado == 'f') {
				if ($cancelada == 'f') {
					$legenda_status = "em análise";
					$cor_status = "#FFFF44";
				}
				elseif ($cancelada == 't') {
					$legenda_status = "reprovada";
					$cor_status = "#FF7744";
				}
				else {
					$legenda_status = "";
					$cor_status = "";
				}
			}
			elseif ($liberado == 't') {
				$legenda_status = "aprovada";
				$cor_status = "#44FF44";
			}
			else {
				$legenda_status = "";
				$cor_status = "";
			}

			echo "
			<TR align='center' style='background-color: $cor_status;'>
				<TD class='conteudo_auditoria' style='background-color: $cor_status; text-align:center;'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>
				<TD class='conteudo_auditoria' style='background-color: $cor_status; text-align:center;'>$legenda_status</TD>
				<TD class='conteudo_auditoria' style='background-color: $cor_status; text-align:center;'>$data</TD>
				<TD class='conteudo_auditoria' style='background-color: $cor_status; text-align:center;'>$data_saida</TD>
				<TD class='conteudo_auditoria' style='background-color: $cor_status; text-align:center;'>$justificativa</TD>
				<TD class='conteudo_auditoria' style='background-color: $cor_status; text-align:center;'><a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar' target='_blank'>Consultar</a></TD>
			</TR>";
		}
		
		echo "
		</TABLE>
		</TD></TR>";
	}
}		

if ($login_posto == 6359){ ?>
	<tr>
		<td colspan='4' align='center'>
			<div style='color:#DA1818; text-decoration: none; font-weight: bold; padding: 20px 10px;'>
				<u><?fecho("atencao",$con,$cook_idioma);?></u>: <?fecho("as.os.e.os.pedidos.do.posto.de.testes.sao.excluidos.diariamente",$con,$cook_idioma);?>
			</div>
		</td>
	</tr>
<? } else {?>
<!--
	<tr>
		<td colspan='4' align='center'>
			<br>
			<p>
			<font size='3' face='arial,verdana' style='color:#DA1818; text-decoration: none'><b><u>MANUTENÇÃO</u>: Estaremos realizando manutenção em nosso Datacenter no dia 24/08/2008 das 4h as 6h sem previsão de indisponibilidade!</b></font>
			</p>
		</td>
	</tr>
	-->
<?}?>
	<tr>
		<td colspan='4'>
			<div id='menu_posto'>
				<ul>
					<?php if ($login_fabrica != 87) { ?>
					<li>
						<span><a href='<?=$link_os;?>' class='conteudo'><img src="imagens/botoes/os.jpg"></a></span>
						<p><a href='<?=$link_os;?>' class='conteudo'><?=$link_os_nome;?></a></p>
					</li>
					<?php } ?>
					<?
					if ($login_fabrica <> 20){
						if($login_fabrica == 87)
							$link_pedido = "menu_pedido.php";?>
                         <?php if(!in_array($login_fabrica, array(87)) OR (in_array($login_fabrica, array(87)) AND $pedido_faturado == 't')){?>
						<li>
							<span><a href='<?=$link_pedido;?>' class='conteudo'><img src="imagens/botoes/<?if($login_fabrica==19){echo "reposicao";} else echo "pedido";?>.jpg"></a></span>
							<p><a href='<?=$link_pedido;?>' class='conteudo'><?=$link_pedido_nome?></a></p>
						</li>
                        <?php }?>
					<?}?>
				 	<?php if ($login_fabrica != 87) { ?>
						<li>
							<span><a href='<?=$link_extrato;?>' class='conteudo'><img src="imagens/botoes/extrato.jpg"></a></span>
							<p><a href='<?=$link_extrato;?>' class='conteudo'><?=$link_extrato_nome;?></a></p>
						</li>
					<?php } ?>
						<li>
							<span><a href='<?=$link_cadastro;?>' class='conteudo'><img src="imagens/botoes/cadastro.jpg"></a></span>
							<p><a href='<?=$link_cadastro;?>' class='conteudo'><?=$link_cadastro_nome;?></a></p>
						</li>
					<?php
						if($login_fabrica <>20){
							if($login_fabrica <> 15){ ?>
								<li>
									<span><a href='<?=$link_preco;?>' class='conteudo'><img src="imagens/botoes/preco.jpg"></a></span>
									<p><a href='<?=$link_preco;?>' class='conteudo'><?=$link_preco_nome;?></a></p>
								</li>
							<?}else{?>
								<li>
									<span><a href='<?=$link_forum;?>' class='conteudo'><img src="imagens/botoes/forum.jpg"></a></span>
									<p><a href='<?=$link_forum;?>' class='conteudo'><?=$link_forum_nome;?></a></p>
								</li>
							<?}?>
							<?php if($login_fabrica  == 87){?>
								<li>
									<span><a href='javascript: void(0);' class='conteudo' onclick='javascript: buscaPecaCatalogoPecas();'><img src="imagens/botoes/vista.jpg"></a></span>
									<p><a href='javascript: void(0);' class='conteudo' onclick='javascript: buscaPecaCatalogoPecas();'><!--<?=$link_vista_nome;?>//-->CATÁLOGO DE PEÇAS</a></p>
								</li>
							<?php }else{?>
								<li>
									<span><a href='<?=$link_vista;?>' class='conteudo'><img src="imagens/botoes/vista.jpg"></a></span>
									<p><a href='<?=$link_vista;?>' class='conteudo'><?=$link_vista_nome;?></a></p>
								</li>
							<?php }?>
							<li>
								<?php if($login_fabrica == 87 ){ $link_informativo = "http://jacto.com.br/default.asp?p=acesso-restrito";?>
									<span><a href='<?=$link_informativo;?>' class='conteudo' target='_blank'><img src="imagens/botoes/informativo.jpg"></a></span>
									<p><a href='<?=$link_informativo;?>' class='conteudo' target='_blank'><?=$link_informativo_nome;?></a></p>
								<?php }else{?>
									<span><a href='<?=$link_informativo;?>' class='conteudo'><img src="imagens/botoes/informativo.jpg"></a></span>
									<p><a href='<?=$link_informativo;?>' class='conteudo'><?=$link_informativo_nome;?></a></p>
								<?php }?>
							</li>
							<li>
								<span><a href='<?=$link_comunicado;?>' class='conteudo'><img src="imagens/botoes/comunicado.jpg"></a></span>
								<p><a href='<?=$link_comunicado;?>' class='conteudo'><?=$link_comunicado_nome;?></a></p>
							</li>
					<?	}
						if($login_fabrica==3){?>
							<li>
								<span><a href='<?=$link_forum;?>' class='conteudo'><img src="imagens/botoes/forum.jpg"></a></span>
								<p <?php if ($login_fabrica == 35) echo " class='menu_title_forum' ";?>><a href='<?=$link_forum;?>' class='conteudo'><?=$link_forum_nome;?></a></p>
							</li>
							<?
							if($login_fabrica <> 20){?>
								<li>
									<span><a href='<?=$link_pesquisa;?>' class='conteudo'><img src='imagens/botoes/<?if ($login_fabrica==14) echo "reposicao.jpg"; elseif ($login_fabrica==42) echo "treinamento.gif"; else echo "pesquisa.gif"?>'></a></span>
									<p><a href='<?=$link_pesquisa;?>' class='conteudo'><?=$link_pesquisa_nome;?></a></p>
								</li>
							<?}?>
							<li>
								<span><a href='<?=$link_requisitos;?>' class='conteudo'><img src="imagens/botoes/requisitos.jpg"></a></span>
								<p><a href='<?=$link_requisitos;?>' class='conteudo'><?=$link_requisitos_nome;?></a></p>
							</li>
							<?php
								if ($login_fabrica == 3){?>
									<li>
										<span><a href='<?=$link_manual;?>' class='conteudo'><img src="imagens/botoes/manual_servico.gif"></a></span>
										<p><a href='<?=$link_manual;?>' class='conteudo'><?=$link_manual_nome;?></a></p>
									</li>
							<?php 
								}else{?>
									<li>
										<span><a href='<?=$link_manual;?>' class='conteudo'><img src="imagens/botoes/manual_servico.gif"></a></span>
										<p><a href='<?=$link_manual;?>' class='conteudo'><?=$link_manual_nome;?></a></p>
									</li>
							<?php
								}?>
							<li>
								<span><a href='<?=$link_at_shop;?>' class='conteudo'><img src="imagens/botoes/at_shop.gif"></a></span>
								<p><a href='<?=$link_at_shop;?>' class='conteudo'><?=$link_at_shop_nome;?></a></p>
							</li>
							<?php
								if ($login_fabrica != 3){?>
									<li>
										<span><a href='<?=$link_sair;?>' class='conteudo'><img src="imagens/botoes/sair.gif"></a></span>
										<p<a href='<?=$link_sair;?>' class='conteudo'><?=$link_sair_nome;?></a></p>
									</li>
							<?php
								}
						}else{ 
							if($login_fabrica <> 15 and $login_fabrica <> 87){?>
								<li>
									<span><a href='<?=$link_forum;?>' class='conteudo'><img src="imagens/botoes/forum.jpg"></a></span>
									<p><a href='<?=$link_forum;?>' class='conteudo'><?=$link_forum_nome;?></a></p>
								</li>
						<?	}
							
							if($login_fabrica <> 20 and $login_fabrica <> 87){?>
								<li>
									<span><a href='<?=$link_pesquisa;?>' class='conteudo'><img src='imagens/botoes/<?if ($login_fabrica==14) echo "reposicao.jpg"; elseif ($login_fabrica==42) echo "treinamento.gif"; else echo "pesquisa.gif"?>'></a></span>
									<p><a href='<?=$link_pesquisa;?>' class='conteudo'><?=$link_pesquisa_nome;?></a></p>
								</li>
						<?
							}?>
						<?php if($login_fabrica <> 87){?>
						<li>
							<span><a href='<?=$link_requisitos;?>' class='conteudo'><img src="imagens/botoes/requisitos.jpg"></a></span>
							<p><a href='<?=$link_requisitos;?>' class='conteudo'><?=$link_requisitos_nome;?></a></p>
						</li>
						<?php }?>
						<li>
								<span><a href='<?=$link_sair;?>' class='conteudo'><img src="imagens/botoes/sair.gif"></a></span>
								<p><a href='<?=$link_sair;?>' class='conteudo'><?=$link_sair_nome;?></a></p>
						</li>
					<? }?>
					</ul>
				<div style='clear: both;'>&nbsp;</div>
			</div>
		</td>
	</tr>
	<tr><td colspan='4'><br>
	<?
	//}//fim else britania

if ($login_fabrica==11){//HD 34540 2/9/2008
	$sql = "SELECT * FROM tbl_admin
			WHERE fabrica = $login_fabrica
			AND tela_inicial_posto is true
			AND ativo is true
			ORDER BY nome_completo";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		echo "<table width='100%' cellpadding='3' cellspacing='0' border='0' >";
		echo "<tr>";
		echo "<td colspan='5' bgcolor='$cor' class='conteudo'>
		<font size='1' face='Arial' color='#ffffff'><b>".strtoupper(traduz("contatos.uteis",$con,$cook_idioma))."</b></font>";
		echo "</td >";
		echo "</tr><br>";
		for($i=0; $i<pg_numrows($res); $i++){
			$admin              = pg_result($res, $i, admin);
			$nome_completo      = pg_result($res, $i, nome_completo);
			$fone               = pg_result($res, $i, fone);
			$email              = pg_result($res, $i, email);
			$responsabilidade   = pg_result($res, $i, responsabilidade);
			$tela_inicial_posto = pg_result($res, $i, tela_inicial_posto);

			if($i==0){
				echo "<TR>";
				$X=0;
			}
				echo "<TD>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
				<A HREF='mailto:$email'>$nome_completo</A><BR>";
				if(strlen($responsabilidade)>0) echo $responsabilidade . "<BR>";
				if(strlen($email)>0)            echo $email . "<BR>";
				if(strlen($fone)>0)             echo $fone;
				echo "</font>
				</TD>";
			if($X==2){
				echo "</TR>";
				$X=0;
			}else{
				$X++;
			}
		}
		echo "</TABLE>";
		}
	}

if ($login_fabrica==11 AND 1==2){//HD 34540 2/9/2008
/*
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' >";
	echo "<tr>";
	echo "<td colspan='3' bgcolor='$cor' class='conteudo'>
	<font size='1' face='Arial' color='#ffffff'><b>CONTATOS ÚTEIS</b></font>";
	echo "</td >";
	echo "</tr><br>";

	echo "<tr>";
		echo "<TD  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:'><b>Sergio / Sup. Técnico</b></a><br>
				Informação técnica, senha para envio de produtos para conserto.<br>
				sergio@lenoxxsound.com.br<BR>
				FONE (11) 3339-9955 ou (11) 0800 770-9044
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:celia@lenoxxsound.com.br'><b>Célia / O.S</b></a></br>
			Pagamento de Ordem de Serviço, Extratos, Números de Série<br>
			celia@lenoxxsound.com.br
			FONE (11) 3217-9957
				</font>
			</TD>";

		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:marcio@lenoxxsound.com.br'><b>Marcio/ Cobrança</b></a></br>

			Duplicatas em atraso/ Cobrança.<br>
			marcio@lenoxxsound.com.br<br>
			FONE (11) 3217-9976
			</font>
			</TD>";
	echo "</tr>";

	echo "<TR>";
		/*echo "<TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:anapaula@lenoxxsound.com.br'><b>Ana Paula/ Adm.</b></a><br>

				Solicitação de coletas Remessa p/ Conserto e devolução de peças<br>
				anapaula@lenoxxsound.com.br<br>
				FONE (11) 3339-9955<br>
			</font>
		</TD>";
		echo "<TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:milena@lenoxxsound.com.br'><b>Cecília / Jurídico</b></a></br>

				Procon e Juizado Especial.<br>
				cecilia@lenoxxsound.com.br<br>
				FONE (11) 3339-9955<br><br>
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:marjorine@lenoxxsound.com.br'><b>Marjorine / DAT</b></a></br>
			Solicitação de coletas Remessa p/ Conserto e devolução de peças<br>
			marjorine@lenoxxsound.com.br<br>
			FONE (11) 3339-9955
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:vanessa@lenoxxsound.com.br'><b>Sônia / Adm.</b></a></br>
			Pedidos e Pendência de Peças.<BR>
			sonia@lenoxxsound.com.br<br>
			FONE (11) 3339-9955
			</font>
			</TD>";
	echo "</TR>";
	echo "<TR>";

		echo "<TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:sac@lenoxxsound.com.br'><b>Luiz Antonio/DAT</b></a></br>
			Credenciamento/Descredenciamento/atualizações de Postos;envio de material tecnico, login e senha Lenoxx Sound.<br>
			luiz@lenoxxsound.com.br<br>
			FONE: (11) 3339-9955
			</font>
			</TD>";
		echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:farias@lenoxxsound.com.br'><b>S.A.C.</b></a><br>
			Serviço de Atendimento a Clientes.<br>
			sac@lenoxxsound.com.br<br>
			FONE 0800 772-9209 ou (11) 3339-9954<br>
			</font>
			</TD>";


		echo "<TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:wagner@lenoxxsound.com.br'><b>Wagner / Inspetor</b></a></br>
			Atendimento a Revendedores/ Lojistas.<BR>
			wagner@lenoxxsound.com.br<br>
			FONE (11) 3339-9955
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:sac@lenoxxsound.com.br'><b>S.A.C.</b></a></br>
				Serviço de Atendimento a Clientes.<br>
				sac@lenoxxsound.com.br<br>
				FONE 0800 772-9209 ou (11) 3217-9953<br><br>
			</font>
			</TD>";
		echo " <TD  valign='top'>
			<br>

			HD 13726 12/2/2008
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:lrodrigues@lenoxxsound.com.br'><b>Luis Antônio Rodrigues – Inspetor Técnico/ADM – Região: MG</b></a><br>
			lrodrigues@lenoxxsound.com.br<br>
				FONE (34) 9171-9805<br>
			</font>
			</TD>";
		echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:marcelor@lenoxxsound.com.br'><b>Marcelo Rocha  - Inspetor Técnico/ADM – Região: RS</b></a><br>
			marcelor@lenoxxsound.com.br<br>
				FONE (51) 3364-3421 ou (51) 9115-3421<br>
			</font>
			</TD>";
		echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:joaopaulo@lenoxxsound.com.br'><b>José Roberto Farias / Inspetor -Região: MT, MS e SP</b></a><br>
			farias@lenoxxsound.com.br<br>
			FONE (11) 3339-9955<br>
			</font>
			</TD>";
	echo "</TR>";
	echo "<TR>";
		echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:demostenes@lenoxxsound.com.br'><b>João Paulo dos Santos Souza. - Inspetor Técnico/ADM – Região: RS, SC e PR</b></a><br>
			joaopaulo@lenoxxsound.com.br<br>
			FONE (51) 3368-6682<br>
			</font>
			</TD>";
			echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:cesar@lenoxxsound.com.br'><b>César Anani – Inspetor Técnico – Região: MG, BA e SE</b></a><br>
			cesar@lenoxxsound.com.br<br>
				FONE (71) 3379-1997<br>
			</font>
			</TD>";

		echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:fassis@lenoxxsound.com.br'><b>Francisco De Assis – Inspetor Técnico – Região: RJ, ES, GO e DF</b></a><br>
			fassis@lenoxxsound.com.br <br>
			FONE (21) 9827-0282<br>
			</font>
			</TD>";
	echo "</TR>";
echo "<TR>";

			echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:marcelo@lenoxxsound.com.br'><b>	Marcelo Soares – Supervisor Regional – Regiões: Norte, Nordeste e Minas Gerais</b></a><br>
			marcelo@lenoxxsound.com.br<br>
			FONE (11) 3339-9955<br>
			</font>
			</TD>";
		echo " <TD  valign='top'>
			<br>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:eder@lenoxxsound.com.br'><b>Nilton Eder Pinheiro – Supervisor Regional – Regiões: Sul, Sudeste, Norte, Nordeste e Minas Gerais</b></a><br>
			eder@lenoxxsound.com.br<br>
			FONE (11) 3339-9955<br>
			</font>
			</TD>";
	echo "</TR>";
echo "</table>";
*/
echo "<table width='100%' cellpadding='3' cellspacing='0' border='0' >";
	echo "<tr>";
	echo "<td colspan='3' bgcolor='$cor' class='conteudo'>
	<font size='1' face='Arial' color='#ffffff'><b>".strtoupper(traduz("contatos.uteis",$con,$cook_idioma))."</b></font>";
	echo "</td >";
	echo "</tr><br>";

echo "<tr>";
		echo "<TD  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:sergio@lenoxxsound.com.br'><b>Sergio / Sup. Técnico</b></a><br>
				Informação técnica, senha para envio de produtos para conserto.<br>
				sergio@lenoxxsound.com.br<BR>
				FONE (11) 3339-9955 ou (11) 0800 770-9044
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:celia@lenoxxsound.com.br'><b>Célia / O.S</b></a></br>
			Pagamento de Ordem de Serviço, Extratos, Números de Série.<br>
			celia@lenoxxsound.com.br
			FONE (11) 3217-9957
				</font>
			</TD>";

		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:marcio@lenoxxsound.com.br'><b>Marcio/ Cobrança</b></a></br>

			Duplicatas em atraso/ Cobrança.<br>
			marcio@lenoxxsound.com.br<br>
			FONE (11) 3217-9976
			</font>
			</TD>";
	echo "</tr>";
echo "<tr>";
		echo "<TD  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:cecilia@lenoxxsound.com.br'><b>Cecília / Jurídico</b></a><br>
				Procon e Juizado Especial.<br>
				cecilia@lenoxxsound.com.br<BR>
				FONE (11) 3339-9955
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:marjorine@lenoxxsound.com.br'><b>Marjorine / DAT</b></a></br>
			Solicitação de coletas Remessa p/ Conserto e devolução de peças.<br>
			marjorine@lenoxxsound.com.br
			FONE (11) 3339-9955
				</font>
			</TD>";

		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:sonia@lenoxxsound.com.br'><b>Sônia / Adm.</b></a></br>

			Pedidos e Pendência de Peças.<br>
			sonia@lenoxxsound.com.br<br>
			FONE (11) 3339-9955
			</font>
			</TD>";
	echo "</tr>";

echo "<tr>";
		echo "<TD  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:luiz@lenoxxsound.com.br'><b>Luiz Antonio/DAT.</b></a><br>
				Credenciamento/Descredenciamento/atualizações de Postos;envio de material tecnico, login e senha Lenoxx Sound.<br>
				luiz@lenoxxsound.com.br<BR>
				FONE: (11) 3339-9955
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:sac@lenoxxsound.com.br'><b>S.A.C.</b></a></br>
			Serviço de Atendimento a Clientes.<br>
			sac@lenoxxsound.com.br<br>
			FONE 0800 772-9209 ou (11) 3339-9954
				</font>
			</TD>";

		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:farias@lenoxxsound.com.br'><b>José Roberto Farias / Inspetor -Região: MT, MS e SP</b></a></br>

			farias@lenoxxsound.com.br<br>
			FONE (11) 3339-9955
			</font>
			</TD>";
	echo "</tr>";

echo "<tr>";
		echo "<TD  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:joaopaulo@lenoxxsound.com.br'><b>João Paulo dos Santos Souza. - Inspetor Técnico/ADM – Região: RS, SC e PR</b></a><br>
				joaopaulo@lenoxxsound.com.br<BR>
				FONE (51) 3368-6682
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:demostenes@lenoxxsound.com.br'><b>Demóstenes Souza – Inspetor Técnico – Região: AL, CE, PA, PE, PB, PI, RN, AC, RO, AM, RR e AP</b></a></br>
			demostenes@lenoxxsound.com.br
				</font>
			</TD>";

		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:cesar@lenoxxsound.com.br'><b>César Anani – Inspetor Técnico – Região: MG, BA e SE</b></a></br>

			cesar@lenoxxsound.com.br<br>
			FONE (71) 3379-1997
			</font>
			</TD>";
	echo "</tr>";


echo "<tr>";
		echo "<TD  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:fassis@lenoxxsound.com.br'><b>Francisco De Assis – Inspetor Técnico – Região: RJ, ES, GO e DF</b></a><br>
				fassis@lenoxxsound.com.br<BR>
				FONE (21) 9827-0282
			</font>
			</TD>";
		echo "<TD  valign='top'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='mailto:eder@lenoxxsound.com.br'><b>Nilton Eder Pinheiro – Supervisor Regional – Regiões: Sul, Sudeste, Norte, Nordeste e Minas Gerais</b></a></br>
			eder@lenoxxsound.com.br<br>
			FONE (11) 3339-9955
				</font>
			</TD>";

		echo "<TD  valign='top'>
			</TD>";
	echo "</tr>";
echo "</table>";
}



if ($login_fabrica==1){
	echo "<table width='740' border='0' cellspacing='2' cellpadding='0' class='tabela' align='center'><tr align='center'><td width='740'>";
//tabela antiga aqui em baixo
	$sql =	"SELECT tbl_posto_fabrica.tipo_posto
			FROM    tbl_posto_fabrica
			WHERE   tbl_posto_fabrica.posto = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$tipo_posto = trim(pg_result($res,0,tipo_posto));

		if ($tipo_posto == "36" or $tipo_posto == 82 or $tipo_posto == 83 or $tipo_posto == 84) {
	?>

		<div id="leftCol" bgcolor='#FFCC66'>
			<div class="contentBlockMiddle" style="width: 610;">
				<a href="comunicados/projeto_locacao.pdf" target="_blank"><font face="Verdana, Tahoma, Arial" size="6"><? fecho("projeto.locacao",$con,$cook_idioma);?></font></a><br>
				<font face="Verdana, Tahoma, Arial" size="3" color="#63798D"><? fecho("informe-se.sobre.o.que.e.o.projeto.locacao",$con,$cook_idioma);?></font><br>
				<!--<br>
				<a href="http://www.blackdecker.com.br/locacao/comparativo-concorrencia.pdf" target="_blank"><? fecho("comparativo.com.a.concorrencia",$con,$cook_idioma);?></a><br>
				<font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><? fecho("veja.um.comparativo.entre.a.concorrencia",$con,$cook_idioma);?></font><br>
				<br>
				<a href="http://www.blackdecker.com.br/locacao/informacao-manutencao.pdf" target="_blank"><? fecho("informacoes.sobre.manutencoes",$con,$cook_idioma);?></a><br>
				<font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><? fecho("informe-se.sobre.as.manutencoes",$con,$cook_idioma);?></font><br>
				<br>
				<a href="http://www.blackdecker.com.br/locacao/precos.pdf" target="_blank"><? fecho("precos.de.maquinas.e.acessorios",$con,$cook_idioma);?></a><br>
				<font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><? fecho("precos.de.maquinas.e.acessorios",$con,$cook_idioma);?></font><br>
				<br>
				<a href="http://www.blackdecker.com.br/locacao/pecas-estoque.pdf" target="_blank"><? fecho("pecas.em.garantia.e.estoque.minimo",$con,$cook_idioma);?></a><br>
				<font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><? fecho("confira.quais.as.pecas.estao.em.garantia.e.a.quantidade.em.estoque.minima",$con,$cook_idioma);?></font><br>
				<br>
				<a href="http://www.blackdecker.com.br/locacao/vista-explodida.pdf" target="_blank"><? fecho("vista.explodida",$con,$cook_idioma);?></a><br>
				<font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><? fecho("arquivo.da.vista.explodida.e.relacao.de.pecas",$con,$cook_idioma);?></font><br>
				<br>
				<a href="http://www.blackdecker.com.br/vistas_dw.php" target="_blank"><? fecho("vista.explodida.da.linha.dewalt",$con,$cook_idioma);?></a><br>
				<font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><? fecho("arquivo.da.vista.explodida.e.relacao.de.pecas.da.linha.dewalt",$con,$cook_idioma);?></font>-->
			</div>
		</div>

	<?
		}
	}

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'class='tabela' >";
	echo "<tr>";
	echo "<td colspan='2' class='conteudo' align='center'>";

	echo "<table width='200'  border='0'cellpadding='0' valign='top'>
	<tr><td class='menu_title'><a href='promocao.php' class='conteudo'>".strtoupper(traduz("promocoes",$con,$cook_idioma))."</a><br><a href='promocao.php' class='conteudo'>".traduz("compre.parafusadeira.para.utilizar.em.sua.oficina",$con,$cook_idioma)."</a>
	</td>
	</tr>
	</table>";
	echo "</td ><td colspan='2' class='conteudo'>";

	echo "<table width='200'  border='0' cellpadding='0' class='contentBlockLeft' valign='top'>
	<tr><td>
	<center><font size='1'><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'><b>".strtoupper(traduz("calendario.fiscal",$con,$cook_idioma))."</b></a></font><br>
	<font size='1' color='#63798D'>".traduz("para.uma.maior.programacao.dos.pedidos.de.pecas.e..acessorios.consulte.o.nosso",$con,$cook_idioma)." <b><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'>".traduz("calendario.fiscal",$con,$cook_idioma)."</a></b>, ".traduz("que.contem.a.data.limite.para.o.envio.de.pedidos.para.a.black.&.decker.na.semana.do.fechamento",$con,$cook_idioma)." <b>".traduz("periodo.do.mes.que.nao.recebemos.pedidos.e.nao.faturamos",$con,$cook_idioma)."</b></font></center></tr></td>
	</table>";

	echo "</td ><td colspan='2' class='conteudo'>";

	echo "<table width='200'  border='0' cellpadding='0' class='contentBlockLeft' valign='top'>
	<tr><td> ";
	#Retirado a pedido da Fabiola HD 241865
	#echo "<a href='peca_faltante.php'><font color='ff0000' size='2'><B>".traduz("informe.a.black.&.decker",$con,$cook_idioma)."</B></font></a></center><br><font size='2' color='#63798D'><center>".traduz("informe.a.black.&.decker.quais.equipamentos.estao.parados.em.sua.oficina.por.falta.de.pecas",$con,$cook_idioma)."</center></font>";
	echo "</tr></td>
	</table>";

	echo "</td ></tr>";
	echo "</table>";

//	'Fale Conosco' da Black&Decker
?>
<table width='100%' cellpadding='5' cellspacing='0' border='0' >
	<caption class='menu_title' style='font-weight:bold;color:white;text-align:left;height:16px;padding:2px 0 0 1ex;font-size:10px;border-radius:4px;-moz-border-radius:4px;'>
		<?=strtoupper(traduz("fale.conosco",$con,$cook_idioma))?>
	</caption>
	<br>
	<tr><?
		$sql = "SELECT * from tbl_fale_conosco ORDER BY ordem";
		$res = pg_query($con,$sql);

		for($i =1;$i<=pg_numrows($res);$i++){
			$j=$i-1;
			$ordem=pg_fetch_result($res,$j,ordem);
			$descricao=pg_fetch_result($res,$j,descricao);
			$descricao = str_replace('Contato via Chamado', '<a href="helpdesk_cadastrar.php">Contato via Chamado</a><br>', $descricao);
			echo "<td  valign='top' id='$ordem'>";
			echo "$descricao</TD>";

			if($i > 0 AND $i%3 == 0) {
				echo "</tr><tr>";
			}
		}
	echo "</tr>";
	echo "</table>";

	echo "</td></tr></table>";
}
?>

	</td></tr>
</table>

<?
if ($login_fabrica <> 20) {
	echo "<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;";
	echo "<div style='float:left'>";
	echo "<script type='text/javascript'><!--
	google_ad_client = 'pub-4175670423523903';
	/* site telecontrol */
	google_ad_slot = '1731533565';
	google_ad_width = 120;
	google_ad_height = 90;
	//-->
	</script>
	<script type='text/javascript'
	src='http://pagead2.googlesyndication.com/pagead/show_ads.js'>
	</script>
	";
	echo "</div>";
}


?>
<style>
	#footer {
		width: 100%;
		border: none;
		padding:10px;
		margin: 0px ;
		background: white;
		color: #E0E0E0;
		text-align: right;
		font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	}
</style>
<?php if($login_fabrica <> 87){?>
	<div id="footer">
		<hr>
			Telecontrol Networking Ltda - <? echo date("Y"); ?><br>
			<a  href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br>
	<?
			echo "<font face='arial' size='-2'> CPU : ";
				$time = $micro_time_end - $micro_time_start;
				echo round($time,4) . " segundos ";
				echo "<br>";
				echo "Dados de seu Navegador $HTTP_USER_AGENT";
			echo "</font>";

			echo "<br><font color='#fefefe'>Deus é o Provedor</font><br>";
			
			@pg_close($con);?>
	</div>
<?php }?>
</body>
</html>
