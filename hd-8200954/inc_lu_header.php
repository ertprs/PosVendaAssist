<?php
$login_posto = $cook_posto;

$tela_lu = (strpos($PHP_SELF,"login_unico.") === false);

include_once "helpdesk/mlg_funciones.php";
if (!function_exists('codigo_visitar_loja')) {
    function codigo_visitar_loja($login, $is_lu=true, $fabrica='') { // BEGIN function codigo_visitar_loja
  		$lu = ($is_lu) ? "1" : "0";
		$cp_len		= dechex(strlen($login));   // Comprimento do código_posto / login_unico, em hexa (até 15 chars)
		$ctrl_pos	= str_pad(4 + $cp_len,2, "0",STR_PAD_LEFT); // Posição do código de controle, 2 dígitos (até 255 chars... suficiente)
		$fabrica	= str_pad($fabrica,   2, "0",STR_PAD_LEFT);// Código da fábrica. '00' se é login_unico
		$controle	= ((date('d')*24) + date('h')) * 3600;    // Pega apenas dia do mês e hora, para
															// minimizar divergências se passarem vários minutos desde
															// que carregou a página até que clica em visitar loja...
		return $lu . $cp_len . $ctrl_pos . $fabrica . $login . $controle;
    } // END function codigo_visitar_loja
}

$infoPosto  = "<label for='$login_nome' to='$nome_posto'> $login_codigo_posto ";
$infoPosto .= (!$login_fabrica) ? $nome_posto : $login_nome;
$infoPosto .= "</label>";

if($login_fabrica==20) {

	if($login_pais=='AR') $bandeira = 'bandeira-argentina.gif';
	if($login_pais=='CO') $bandeira = 'bandeira-colombia.gif';
	if($login_pais=='UY') $bandeira = 'bandeira-uruguai.gif';

}

if ($login_fabrica) {
	$logo_fabrica = @pg_fetch_result(@pg_query($con, "SELECT logo FROM tbl_fabrica WHERE fabrica = $login_fabrica"), 0, 'logo');

	if ($logo_fabrica) {
		include 'fn_logoResize.php';

		if ($login_fabrica == 46 and $AWS_sdk_OK) { // Para a Telecontrol Net, usar logotipos desde o S3
			include_once AWS_SDK;
			$s3logo   = new AmazonS3();
			if (is_object($s3logo)) {
				$logoS3      = 'logos/' . basename($logo_fabrica);
				$bucket      = 'br.com.telecontrol.posvenda-downloads';
				$logoImg     = ($usaLogoS3 = $s3logo->if_object_exists($bucket, $logoS3)) ? $s3logo->get_object_url($bucket, $logoS3) : $logo_fabrica;
			}
		}

		if (!$logoImg) {
				if (is_readable('logos/' . basename($logo_fabrica)))
					$logoImg = 'logos/' . basename($logo_fabrica);
		}

		$attrLogo    = "style='" . logoSetSize($logoImg, 120, 48, 'css') . "position:absolute;margin-top:4.3em;float:right'";
		if (strpos($attrLogo, 'width')>5) {
			$infoFabrica = "<img src='$logoImg' $attrLogo />";
		}
	}
	if (!$logoImg or !$infoFabrica)
		$infoFabrica = "<span style='float:right;margin-top:6em'><label>Fabricante:</label>$login_fabrica_nome</span>";
}

$IEheader = ($login_fabrica) ? '<meta http-equiv="X-UA-Compatible" content="IE=9; IE=8">'
							 : '<meta http-equiv="X-UA-Compatible" content="IE=9; IE=7">';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
<?=$IEheader?>
<title><?=$title;?></title>
<link type="text/css" rel="stylesheet" href="css/tc_lu.css">
<link type="text/css" rel="stylesheet" href="css/css.css">
<link type="text/css" rel="stylesheet" href="css/estilo.css">
<link href="/assist/imagens/tc_2009.ico" rel="shortcut icon">
<script type="text/javascript" language="JavaScript">
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		return false;   <? // Essa função não tem onde colocar o texto, e como está em toda parte, estou anulando ela com este 'return false' ?>
	}

	function changeIframeHeight(id, height) {
	    $("#"+id).css({ height: height+"px" });
	}
</script>

</head>
<body>
<div id='div_carregando'>Carregando...</div>
<div id='header'>
<?
	//Ronaldo disse que pode mostrar em todas...
	//if (strpos($PHP_SELF, 'menu_') or strpos($PHP_SELF, 'login_unico'))
		include 'inc_browsers.php';
?>
	<style type="text/css">
	#browsers {
		top: -12px;
		z-index: 1000;
	}
	</style>
	<a href="http://www.telecontrol.com.br/" target="_blank">
		<img id='logo' src='logos/telecontrol_2011_texto.png'>
	</a>
	<!--<SPAN class='div_lv'>
	    <A href='http://www.telecontrol.com.br/loja/index.php<?= "?visitar_loja=" . codigo_visitar_loja ($login_unico);?>'
		  title='Acesse a Loja Virtual Telecontrol'
		 target='_blank'>
         <IMG src='./imagens/ico_tc_shop.gif' style='position: relative; top: -16px'>
	 	</A>
	 </SPAN>-->
	<div id='infouser'>
		<span class='logo'>
            <?=$infoFabrica;?>
			<span style='height:100px;zoom:1;display:inline-block;'>
				<?=$infoPosto?><br>
				<span><?=long_date(strtolower($cook_idioma))?></span>
				<br>
				<label>Usuário:</label><span><?=($login_fabrica)?$login_unico_nome:$login_nome?></span>
				<?  if($bandeira) echo "&nbsp; <img src='imagens/$bandeira' />"; ?>
				<br />
				<br />
				<span style="float:right">
				<a class='botao' href='login_unico_logout.php' title='Sair do Sistema'><? fecho('sair', $con, $cook_idioma);?></a>
				<?if ($tela_lu) { ?>
				<a class='botao' href='login_unico.php' title='Voltar ao menu inicial do Login Único'><?=traduz('inicio', $con, $cook_idioma)?></a>
				<?}?>
				</span>
			</span>
		</span>
	</div>
</div>
<?
include './comunicados_telecontrol.inc.php';

	if($login_fabrica == 3 and $login_bloqueio_pedido == 't'){
		echo "<div class='erro'>Existe pendência no Distribuidor TELECONTROL, regularize a sua situação.".
			 " <a href='posicao_financeira_telecontrol.php'>Clique aqui</a>".
			 "</div>";
	}
?>
