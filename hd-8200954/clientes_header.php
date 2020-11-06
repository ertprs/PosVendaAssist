<?php
header('Content-Type: text/html; charset=utf-8');

/*  Bloqueia o login ao site:   */
/*  Imagem a mostrar. Tem várias já prontas:
		   - login_box.png		-  Só o formulário
		   - login_box_min.png	-  Formulário com a mensagem "Sistema em manutenção. Tente daqui uns minutos"
		   - login_box_horas.png-  Formulário com a mensagem "Sistema em manutenção. Tente daqui umas horas"
*/
//	$disble_login_imagem = "login_box_horas.png";
//  Data e hora de início do bloqueio: (ordem: hora, min, seg, mês, dia, ano)
	if (time() > mktime(18, 00,  0,  3, 20, 2010)) $disable_login = "manutenção";
//  Data e hora de fim do bloqueio, comentar para deixar indefinido, ou incrementar o ano, p.e.
	if (time() > mktime(03, 10,  0,  3, 21, 2010)) $disable_login = "não";

// 	$disable_login = "não";			//  Desbloqueia incondicional. COMENTAR PARA BLOQUEAR CONDICIONAL
// 	$disable_login = "manutenção";	//  Bloqueio incondicional, DESCOMENTAR PARA BLOQUEAR INCONDICIONAL

/*	Seleção de idioma	*/
$cook_idioma = $_COOKIE['cook_idioma'];
$arquivo_url = substr($PHP_SELF, strrpos($PHP_SELF, "/"));

if (($dir_idioma = ($cook_idioma == "pt-br") ? "":$cook_idioma) != "") {	// Se for pt-BR não precisa de diretório...
	if (file_exists($dir_idioma.$arquivo_url)) header("Location: $dir_idioma"."$arquivo_url");
}

/*  Tradução do cabeçalho:    */
include_once "./trad_site/fn_ttext.php";
include "./trad_site/trad_inc_header.php";

if ($html_titulo=="") $html_titulo = "Gest&atilde;o de P&oacute;s-Venda";
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?=ttext($a_trad_header, "titulo") . " - $html_titulo"?></title>
<?	/*	A URL do <base> define a "pasta de trabalho", se tirar o 'new' já vai direcionar tudo para '/'	*/?>
<!--
	<base href="http://www.telecontrol.com.br/" />
	<meta http-equiv="Content-Type"		content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Language"	content="pt-br" />
	<meta name="author"			content="web@w3midia.com.br" />
	<meta name="reply-to"		content="contato@telecontrol.com.br" />
	<meta name="copyright"		content="Telecontrol 2009" />
	<meta name="generator"		content="Notepad++ v5.4.2" />
	<meta name="description"	content="Distribui&ccedil;&atilde;o, armazenagem e gerenciamento do estoque de pe&ccedil;as para Assist&ecirc;ncia T&eacute;cnica." />
	<meta name="keywords"		content="distribui&ccedil;&atilde;o,armazenagem,gerenciamento,Assist&ecirc;ncia,T&eacute;cnica" />
	<meta name="robots"			content="index,follow" />
	<meta name="title"			content="Telecontrol - Gerenciamento de Assistencia Tecnica - Gest&atilde;o de P&oacute;s-Venda" />
	<meta name="resource-type"	content="document" />
	<meta name="classification" content="Internet" />
	<meta name="distribution"	content="Global" />
	<meta name="rating"			content="General" />
	<meta name="doc-class"		content="Completed" />
	<meta name="doc-rights"		content="Public" />
-->

	<link rel="stylesheet"		type="text/css"		href="css/tc09_layout.css"	  charset="utf-8" />
	<link rel="stylesheet"		type="text/css"		href="css/tc09_MenuMatic.css" charset="utf-8" media="screen" />
	<link rel="shortcut icon"	type="image/x-icon" href="img/favicon.ico"		  charset="utf-8" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" href="css/tc09_MenuMatic-ie6.css" type="text/css" media="screen" charset="utf-8" />
	<![endif]-->
	<!--[if IE]>
		<style>
		input[type=button], input[type=submit], input[type=reset], .button, button {
			padding: 2px 5px 5px;
		}
		</style>
	<![endif]-->

	<!--[if lt IE 8]>
	<script type="text/javascript" src="js/IE8.js"></script>
	<![endif]-->
	<script type="text/javascript" src="js/mootools.js"></script>
<!--
	<script type="text/javascript" src="js/MenuMatic_0.68.3.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/AC_RunActiveContent.js"></script>
-->
	<script type="text/javascript" src="js/jquery.min.js"></script>

	<script type="text/javascript">
// 		alert ("<?=$temp_idioma.$arquivo_url?>");
		function setCookie(c_name,value,domain,path,expiredays) {
			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			var expireDate = (expiredays==null) ? "" : ";expires="+exdate.toGMTString();
			var c_path     = (path == null || path == "") ? "" : ";path="+path;
			var c_domain   = (domain == null || domain == "") ? "" : ";domain="+domain;
			document.cookie=c_name+ "=" +escape(value)+c_domain+c_path+expireDate;
		}
	</script>

	<script type="text/javascript">
		jQuery.noConflict();
		/*
		window.addEvent('domready', function() {
			var myMenu = new MenuMatic();
		});*/
	</script>
	<script type="text/javascript">
	jQuery().ready(function ($) {
	    var inputText = '<?=ttext($a_trad_header, "insira_CNPJ", $cook_idioma) ?>';
<?	if ($disable_login != "manutenção") { ?>
<?	if ($arquivo_url == '/index.php') { ?>
		$('.cnpjOFF').focus(function () {
			CNPJ = $(this);
		    if (CNPJ.val() == inputText) CNPJ.val('').removeClass('cnpjOFF');
		}).blur(function () {
			CNPJ = $(this);
			if (CNPJ.val().length == 0) {
				CNPJ.addClass('cnpjOFF')
					.val(inputText);
			}
		});
		$('input[name=btn_acao]').click(function () {
			var CNPJ = $('input[name=cnpj]');
			if (CNPJ.val() == inputText) CNPJ.val('');  // Limpa o valor do INPUT antes de dar o Submit.
//  Este código só serve para o Brasil... Faz uma pré-validação de CNPJ/CPF
// 								    var filtroNums = /\D/g;
// 									var CNPJ = jQuery('input[name=cnpj]');
// 									var cnpj_nums = CNPJ.val().replace(filtroNums,"");
// 									if (cnpj_nums.length == 0 || (cnpj_nums.length != 14 && cnpj_nums != 11)) return false;
// 									CNPJ.val(cnpj_nums);
		});
<?}?>
<?}	// Fim bloqueio login, parte 1...
?>
//  Seta a cookie do idioma segundo a bandeira...
		$('p.flags span,#navfooter3 li').click(function() {
		    idioma = $(this).find('img').attr('alt');
	        if (idioma==undefined) idioma = 'pt-br';
// 			alert(idioma);
			setCookie ("cook_idioma",idioma,"","/");
			 window.location.reload();
		});
		$('p.flags img[alt=<?=$cook_idioma?>]').css('box-shadow','0 0 4px grey');
		$('p.flags img[alt=<?=$cook_idioma?>]').css('-moz-box-shadow','0 0 4px grey');
		$('p.flags img[alt=<?=$cook_idioma?>]').css('-webkit-box-shadow','0 0 4px grey');

<?/*  Mensagem de pré-aviso de parada do sistema, tirar o comentário no SCRIPT, alterar a mensagem se precisar  */	?>

/*		var msg_div = $('#entrando');
		var msg_txt = "<p style='font-weight:bold;font-size:12px;color:white'>";
			msg_txt+= "<img src='img/Exclamation-sm.png' alt='Atenção!' style='float:left;clear:left' />";
			msg_txt+= "ATENÇÃO!! A TELECONTROL não irá alterar seus horários para o horário de verão.<br>Manteremos GMT-3";
//			msg_txt+= "ATENÇÃO!! As linhas VoIP estão com problema intermintente na operadora. O número 0xx14 3413-6588 não está com problema.";
//			msg_txt+= "Hoje estaremos em regime de plantão.</p>";
		$('input[name=login]').focus(function () {
			//msg_div.html(msg_txt).show('fast');
		}).blur(function () {
			$('#entrando').hide('fast').empty();
		});
/**/

<?	if ($arquivo_url == '/artigos.php') {	?>
	    $('ul li.artigo').click(function () {
			url		= 'artigos/'+$(this).attr('alt');
			titulo  = $(this).attr('title');
			$('#artigo').html('Carregando "<strong>'+titulo+'</strong>"...')
						.load(url);
		}).first().click();
<?	}?>
<?	if ($arquivo_url == '/index.php') {	?>
		$('#entrando').load('./artigos.php?load=id', function() {
			$('#entrando ul li').each(function(index) {
				if (index == 10) { // Mudar para '0' para colocar o primeiro arqtigo em destaque no TeleNews
					$(this).prependTo('#telenews ul'); //  O primeiro artigo vai em destaque no TeleNews
				} else {
					$(this).appendTo('#telenews ul'); // O 'resto' vai ao final
				}
				if (index == 1) {   // Por enquanto, só dois artigos
					$('#entrando').empty().hide();
			    	return false;
				}
			});
		}).empty().hide('slow');
	    $('ul li.artigo').live('click', function () {
			url		= '/artigos/'+$(this).attr('alt');
			titulo  = $(this).attr('title');
			$('#idx_cont').html('<div id="documento"><div id="artigo"></div></div>');
			$('embed').first().hide();
			$('#artigo').html('Carregando "<strong><em>'+titulo+'</em></strong>"...')
						.load(url);
		});
<?	}?>
	});
	</script>
    <script src="js/login.js" type="text/javascript" charset='utf-8'></script>
<?
	//    Carrega o GoogleMaps se está na tela do Mapa da Rede Autorizada e algum dado para mostrar
	//  Recupera as variáveis para a pesquisa
	if (strpos($PHP_SELF, "_rede") > 1) {
		$fabrica= $_POST['fabrica'];
		$estado	= $_POST['estado'];
		$cidade	= $_POST['cidade'];
		include_once '../../assist/www/gMapsKeys.inc';

?>
	<script src="http://maps.google.com/maps?file=api&v=2&key=<?=$gAPI_key?>" type="text/javascript"></script>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('select[name=fabrica]').val('<?=$fabrica?>');
		jQuery('select[name=estado]').val('<?=$estado?>');
		jQuery('select[name=cidade]').val('<?=$cidade?>');
		jQuery('select[name=estado]').change(function () {
			if (jQuery('select[name=fabrica]').val()!= "" &&
				jQuery('select[name=estado]').val() != "") {
				jQuery('select[name=cidade]').val("");
			    jQuery('input:submit').click();
			}
			if (jQuery('select[name=estado]').val() == "") {
				jQuery('select[name=cidade]').val("").hide("fast");
				jQuery('form[name=frm_mapa] label[alt=cidade]').hide("fast");
			}
		});
		jQuery('select[name=cidade]').change(function () {jQuery('input:submit').click();});
		jQuery('select[name=fabrica]').change(function () {
			jQuery('select[name=estado]').val('').change();
		});
		jQuery('form[name=frm_mapa] input[name=limpar]').click(function () {
			jQuery('select[name=fabrica]').val("").change();
		});
	});
	</script>
	<?}?>
</head>

<body <?=$body_options?>>
<div id="geral">
<div id="header">
	<a href="../index.php"><h1 id="logo2">Telecontrol</h1></a>
	<div id="LoginTipos">
		<p class="loginAcesso">
			<a class="loginUnico"     target='_parent' href="http://www.telecontrol.com.br/telecontrol/www/login_unico.php">Login Único</a>
			<a class="primeiroAcesso" target='_parent' href="http://www.telecontrol.com.br/telecontrol/www/primeiro_acesso.php">Primeiro Acesso</a>
		</p>
	</div>
<!--nav-->
<!--Mensagens de erro e status-->
