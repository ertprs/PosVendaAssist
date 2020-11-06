<?php
/*  Funções */
include_once('mlg_funciones.php');

$included = (basename($PHP_SELF) != basename(__FILE__));

if ($_GET['no_header'] == 'true') $no_header = true;

if (!$included) {
	include_once '../dbconfig.php';
	include_once '../includes/dbconnect-inc.php';

	if (strpos($PHP_SELF, 'assist/')) {
		$assist = true;
		if (strpos($PHP_SELF, '/admin')) {
			include_once '../admin/autentica_admin.php';
		} else {
			//include_once '/var/www/assist/www/autentica_usuario.php';
			if (isset($login_unico)) $e_posto = true;
		}
	}
}

if (strpos($PHP_SELF, 'manuel/')) {
	$assist = true;
	$e_admin = isset($login_admin);
	$e_posto = isset($login_unico);
	if (strpos($PHP_SELF, '/admin'))
		$e_admin = true;
}
//
// MLG 18-04-201 - HD 413668 - BOSCH Sec. não quer popup de aniversários
$fabrica_nao_quer_anivs = array(96);

if (strpos($PHP_SELF, 'mlg/')) {
	$mlg_dir       = true;
	$login_fabrica = 10;   $e_admin = true;
	$login_posto   = 4311; $e_posto = true;
	// Recupera o admin ID
	$sql = "SELECT admin, nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND ativo IS TRUE AND login = '$usr_login'";

	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 1) {
		$login_admin = pg_fetch_result($res, 0, 'admin');
		$admin_nome  = pg_fetch_result($res, 0, 'nome_completo');
	}
	// Procura um login unico com o mesmo nome do Admin
	$sql = "SELECT login_unico, nome FROM tbl_login_unico WHERE nome ~* '$usr_login' AND ativo IS TRUE AND posto = $login_posto";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 1) {
		$login_unico = pg_fetch_result($res, 0, 'login_unico');
		$lu_nome     = pg_fetch_result($res, 0, 'nome');
	}
}

if ($_COOKIE['no_anivs'] == 'no_anivs') unset($e_admin, $e_posto);
$debug = ($_REQUEST['debug'][0] == 't');    // Modo 'debug' se tiver um _GET, _POST ou _COOKIE com valor 't...'

//  MLG 18-04-201 - HD 413668 - BOSCH Sec. não quer popup de aniversários
if (($e_posto or $e_admin or !$included) and !in_array($login_fabrica, $fabrica_nao_quer_anivs)) {

	//  Altera a query dependendo do fabricante logado
	unset ($filtro_fabrica, $join_fabrica, $campo_fabrica);
	if ($e_admin) {
		if ($login_fabrica != 10) {
			$filtro_fabrica = "AND tbl_admin.fabrica IN ($login_fabrica, 10)";
			$campo_fabrica  = ' tbl_fabrica.nome AS fabricante,';
		}
		if ($e_admin && in_array($login_fabrica, array(14,43,66))) { // Para o Grupo Intelbras... especial como sempre!
			$filtro_fabrica = "AND tbl_admin.fabrica IN(14,43,66)";
			$campo_fabrica = ' tbl_fabrica.nome AS fabricante, ';
		}

		$sql_admin_anivs = "SELECT
		  TO_DATE( dia_nascimento::TEXT || '-' ||
			mes_nascimento::TEXT || '-' ||
			COALESCE( ano_nascimento, EXTRACT( YEAR FROM current_date ) )::TEXT,
			'DD/MM/YYYY' ) as data_aniv,
			LPAD( dia_nascimento::TEXT, 2, '0' ) || '-' ||
			LPAD( mes_nascimento::TEXT, 2, '0' ) || '-' ||
			COALESCE( ano_nascimento, EXTRACT( YEAR FROM current_date ) )::TEXT AS aniv,
			admin, nome_completo, tbl_admin.fabrica, tbl_fabrica.nome AS fabricante, email
		  FROM tbl_admin
		  JOIN tbl_fabrica USING( fabrica )
		  WHERE tbl_admin.ativo IS TRUE
		  $filtro_fabrica
		  AND dia_nascimento IS NOT NULL
		  AND TO_DATE( dia_nascimento || '-' || mes_nascimento || '-' || EXTRACT( YEAR FROM current_date ), 'DD/MM/YYYY' )
			BETWEEN CURRENT_DATE AND CURRENT_DATE + 7
		  ORDER BY data_aniv, fabrica";

		$res_admin      = pg_query($con, $sql_admin_anivs);
		$tot_admin      = pg_num_rows($res_admin);
		$tem_aniv_admin = ($tot_admin > 0);
	}
	if ($login_fabrica != 10 and isset($login_fabrica)) {
		$filtro_fabrica = "AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$join_fabrica   = "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_login_unico.posto
												 AND tbl_posto_fabrica.fabrica = $login_fabrica
						   JOIN tbl_fabrica USING(fabrica)";
	}
	$sql_lu_anivs= "SELECT
	  TO_DATE( dia_nascimento::TEXT ||
		mes_nascimento::TEXT ||
		COALESCE( ano_nascimento, EXTRACT( YEAR FROM current_date ) )::TEXT, 'DDMMYYYY' ) AS data_aniv,
		LPAD( dia_nascimento::TEXT, 2, '0' ) || '-' ||
		LPAD( mes_nascimento::TEXT, 2, '0' ) || '-' ||
		COALESCE( ano_nascimento, EXTRACT( YEAR FROM current_date ) )::TEXT AS aniv,
		login_unico, tbl_login_unico.nome,tbl_posto.nome AS razao_social, tbl_login_unico.email
		FROM tbl_login_unico
		$join_fabrica
		JOIN tbl_posto ON tbl_posto.posto = tbl_login_unico.posto
		WHERE tbl_login_unico.ativo IS TRUE
		$filtro_fabrica
		AND dia_nascimento IS NOT NULL
		AND TO_DATE(
		  dia_nascimento::TEXT || '-' ||
		  mes_nascimento::TEXT || '-' ||
		  EXTRACT( YEAR FROM current_date )::TEXT, 'DD/MM/YYYY' )
		  BETWEEN CURRENT_DATE AND CURRENT_DATE + 7
		ORDER BY data_aniv";

	$res_lu     = pg_query($con, $sql_lu_anivs);

	// Mostra a query só se o programa não é parte de alguma tela
	if ($pg_err = pg_last_error($con) and !$included) pre_echo($sql_lu_anivs, $pg_err);
	$tot_lu      = pg_num_rows($res_lu);
	$tem_aniv_lu = ($tot_lu > 0);

	$aniv_titulo_popup = 'Tem anivers&aacute;rio';
	$aniv_titulo_popup.= ($tot_admin + $tot_lu == 1) ? '':'s';

	$ad = 0; $lu = 0;
	if ($tem_aniv_admin) {
		while ($ad < $tot_admin) {
			if ($debug) p_echo("i: $ad tot: $tot_admin");
			$a_nivers[] = pg_fetch_assoc($res_admin, $ad++);
		}
	}

	if ($tem_aniv_lu) {
		while ($lu < $tot_lu) {
			if ($debug) p_echo("i: $lu tot: $tot_lu");
			$a_nivers[] = pg_fetch_assoc($res_lu, $lu++);
		}
	}

	if ($tem_aniv_admin or $tem_aniv_lu) {
		if ($aniv_cp_fmt == 'utf8') array_map(utf8_encode, $a_nivers);
		foreach ($a_nivers as $aniv_item) {
			extract($aniv_item, EXTR_PREFIX_ALL, 'aniv');
			$tipo    = (isset($aniv_nome)) ? 'lu' : 'admin';
			$usuario = ($tipo == 'lu') ? $aniv_nome         : $aniv_nome_completo;
			$razao   = ($tipo == 'lu') ? $aniv_razao_social : $aniv_fabricante;
			$usuario = htmlentities($usuario);
			$razao   = htmlentities($razao);

			$hoje = ($aniv_aniv == date('d-m-Y')) ? ' hoje' : '';

			$dia = ($aniv_aniv == is_date('hoje', '', 'd-m-Y')) ?
				'Hoje é ' :
				(($aniv_aniv == is_date('amanha', '', 'd-m-Y')) ?
				'Amanhã será ' : "Dia $aniv_aniv será ");
			$li_item = /* "<li class='aniv$hoje'>$avatar". */
						"<p>$dia o anivers&aacute;rio de <b>$usuario</b>".
						iif((in_array($login_fabrica, array(10,14,43,66)) or $aniv_fabrica == 10 or $tipo=='lu'), " da <i>$razao</i>", '').
						"!</p><hr width='90%'>"./*
						"Clique <span class='email' alt='$email'>aqui</span> para dar os parab&eacute;ns.".*/
						"</li>\n";
			if ($tipo == 'admin') {
				if ($aniv_admin == $login_admin and $hoje != '') {
					$li_item = "<li class='meu aniv' style='font-size: 1.3em'>".
						"<img style='float:right;margin-right:1em' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAAB90RVh0U29mdHdhcmUATWFjcm9tZWRpYSBGaXJld29ya3MgOLVo0ngAAAfoSURBVFiF1Zd3bN3VFcc/9/7G2/az/bxXbMdx7CQlcWKHEShihAIJKxWUGUYKBUKFxF5FICpEaVVGKUOAgBZFpU0UUKEihYJQgECAoDjTTh1iO/H2e/Z7v7d+4/afkgJlOFWqque/79W953x07tG59wilFP9Lk4fVm5CH7G/6B26UZ7Nahg7qm+U8bpInHtR3SsnNrPrvAcxUd9Ouar+gT2CWOv+gblVhmtVqXhe+QwHQp7vRqZvxlq5/lvpc74t17ggYnlb2T9302k35v5zb9ebbn1UZV0LusAJ03rNzBYH3lg8MxmMHlnLxKXdtj6QKXnpkNEl0/OWPN44/vfDD6rmr7j9vR+jyoQNjgSvhJ4cVoMB1SyJqqkkaTgrgo664cUyHbCjUlU83iQDIZC7WVOgVBIOiZLrBYZo1sGvCy4+lHdqaCiyAiXOXTPVM2PlwRKfhmIUTAIM58iNZj9amiHXYAU5eWMyU0OjqG/GxSpw88kHNsqYq3dg0rLzTNl+5gFNFy5HNhpbwBK/vU5OHAvDNV/CbtgL27DxWWZx+asVly/uaH2Nu4u32jG/+hnh0PkG/zpyiIq/TCjxjVyxWdZlPsoPNZ7Ni6MVTuKLpCSQfYvIOj6k9hwbw0NyZamD7rZYInZWvWFzib1rCpFhKT3+cxbOPELnODbhZk+51E0RlRrqn3Y2KeSL5t1RgW+84y9o6WqzZ97bk+rZc5Rv62ApdL94kbN7NfblPvxvgyeaqfLzv/fici2LqyNWoslaEoVHWnyM0ZrE/rVA5G5GxmF3lZ/u4g5WMY5mKupIAhaMwZpSQW3gdU50a0hoJZTY/ckZ066NnyFvEmfIB9cpXAb5cA+N7rk81nBATF/wOVTobN5vEzE8xamVxgn7KogaObWPZLntzUFLsJ2BqSAG9lkfWZ1IUBHtqDGdqmDw6iePu48DydThlsUdHbwkVfmsGPIkKRA3iVhaFgaabJDJpWsJwaoXHomKFKw2KA3BJg0PScYgFBGllsKJeUeLP0l7k4UgDw6eDHgIhCYUFnplTieFMvvQrAOKLr+HOrZ+srNn54+eCIsHkwqeYMufgaSFMTRCRWXJOnrSjEAIiukICU47EVQK/BpFAkJwI4EgD3UtiprsJd/+CfPc6+nINbzZf13PSt2ZARGepoTmPE9n3MMUbTyJc0oIbnk267ByyvhnYgSakEQEUSSEBDaGlMVUWwxCQ3koosxeZ7sXofwF7Yi/7sjPYE7qVeOXSXPN3FWF9oN/qt2eQnPMEyclVhEZewJzcTuHwSjAEmAV4wVkIPNCDOEYFZmY3MjeIUDaulcB2IClLiQdOYjB6I+PMxPbCmN6E+Jr4/wIQ81+tevC2+csunPsM6akuVOXDWIW/xBKCxORHSHsEmenFcPaDk8S0enF0B+FEcALH4Lk57NhiPLMONzgLyw6SH92HzxqgXNtAwp71tT8fHWDeNe/9oK69bE1vwo2axfNJqRI0lQXlIN0kqnAeSuh40kdW+MHLknFzIHVAIqQJXgYhQFMOerqHfM4G5UPXUpSG/8oH3TOPXXnJtj8ERPy2fc8f2/s5gAQwJ7eOtbcVRY/yfwqZRvTYxQjNRFc9eAOn42V3IYVA2gMYifVIZxhd5tCxMOxe9PRmNGUhPQuJi5a8E9Jr0TQfaaeCbVMPk1WzIrrPPlcGg12Nq7fcUHv56xqA7Djzao48fklbeXXMa9z7AiWvXoqeH0EFClG+VtSMNWiBRoSQaOoz8hO3o9wxhDQRWgEys5b88O0gTYSUCOHhlD0FJddi6xpC02gZXs+Ff7+a2kofZlA3k4M71GTGAECLFJXK+s7lj+dct/ZleTqzh5+lbeDXyHAJdqgR6W/EM6MgHNCrkUWXohnFCFwENiqwEFG0AqkFUHoQ9ADoRZAaIjryFh17f05p/595I3ohyfqTiRXqsr6+NFMettZd9P0GV9/1/mveoqufudepPfrVibxPXBV+idX6Gn648VpqYhGINmGVLycXmUsu3IprlAIuSpgoaaI5k5iOhfDG8CW7CKS70VO96H3rSSVsXk6fwIbYg6QqT6RcixMwhTM1lhway2Xtg40otvqVxaGq1k1SQE1RkOaKAvzOBHWjz1Kc286PImsx/Rp+Q0G4HqQNmg/MakjtgmwcXBuVg+GsRr9qoytwAVv0U3g3UUs87VBqpIn61OTUO0+dsenFB975Uicsv+LpIhEqvi7aOO9nR9REtOqIzrClePeAQSLrsiAyQEdpihj7UdYexqYmaTB6GfOq+V50lDFfB/Fkgg3xDobcCoIFNTSWFxMzLDK2Tfdojv1JFzs1sbnvjgWdX2p+X2zFjZc9sHTOEYsWVFZXnTngFhy1O+HheBDy+VhQ5qcw6GPXhEfXqEPe8QgagvYKP3UFksGUy7bhJBnbwae51EQkLcU6enZy93jGY/eBeM+O9b+9xt74fP83AhxcFCJad/4dC+z6zuMIFgZjpWXL25sqWwsN2DmYiHcPJ3uUP9KhmT5RX6DTViQRQrAj7tE7nIg72fSnpq7V1ZaEK4efu2Hx7jfW9gKOUir/b7GmM5pVn/PT+YvOuuIewxrduumPT/5+/1t/2lN83l1LfLOPXlYYDEQqM30flERC4URZ20U7t3z40MCvLlkjhIiZx6+szL/9fNe3xZgWwHRNCOFTSk17JjjsAP+JHd7h9P8R4B8lOGfzyYHujgAAAABJRU5ErkJggg=='>".
						"<b>HOJE</b> &eacute; seu ANIVERS&Aacute;RIO!!<br>PARAB&Eacute;NS!!!!</li>\n";
				}
				$admin_aniv_item.= $li_item;
			} else {
				$lu_aniv_item.= $li_item;
			}
		}
	}
	if (($tot_admin + $tot_lu) > 0) {
		if (!$included and  !$no_header) {
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Aniversários</title>
<?}?>
		<style type="text/css">
		div#anivs {
			position: fixed;
			bottom: 0;
			right: 1em;
			max-height: 2.5em;
			max-width: 250px;
			background-color: #D9E2EF;
			-moz-border-radius: 5px 5px 0 0;
			border-top-left-radius: 5px;
			border-top-right-radius: 5px;
			border: 1px solid #ccb;
			margin: 0;
			padding: 0;
			font-family: Arial, Helvetica, sans-serif;
			font-size: 12px;
			color: #000;
			cursor: default;
			padding: 0;
			z-index: 10;
			transition: max-height 0.4s;
			-o-transition: max-height 0.4s;
			-moz-transition: max-height 0.4s;
			-webkit-transition: max-height 0.5s linear;
			z-index: 501;
		}
		div#anivs:hover {
			max-height: 22em;
			z-index: 501;
		}
		div#lista {
			 max-height: 13.4em;
			 overflow-y: auto;
			 margin: 2px 0 2px 4px;
		}
		div#anivs > h2 {
			background-color: #596d9b;
			color: #fff;
			-moz-border-radius: 5px 5px 0 0;
			border-top-left-radius: 5px;
			border-top-right-radius: 5px;
			width: 100%;
			height: 2em;
			line-height: 2em;
			padding: 2px 0;
			margin: 0;
			font-size: 13px!important;
			text-align: center;
			font-weight: bold;
			font-stretch: 120%;
		}
		div#lista > h4 {
			text-align: left;
			font-size: 10px;
			line-height: 1.5em!important;
			color: #63798D;
		}

		ul#lu, ul#admin {
			list-style: none;
			margin: 0;
			padding: 0;
			font-size: 1em
		}
		div#lista > ul > li {text-align: left}
		ul#admin li.hoje, ul#lu li.hoje {
			color: darkRed; /*#2C2A4F;*/
		}
		ul#admin li span, ul#lu li span {
			color: navy;
			cursor: pointer;
		}
		.aniv img.no-shadow {
			float: right;
			margin: 5px;
			outline: white 2px;
			max-width: 24px;
			max-height: 32px;
			position: relative;
			right: 5px;
			border-radius: 3px;
		}
		ul li p:first-line {font-weight: bold}
		ul li p {
			border: 1px solid #bbb;
			border-radius: 3px;
			-moz-border-radius: 3px;
			padding: 2px 4px;
			margin-right: 5px;
			background-color: #BBCCDD;
			background-image: -moz-linear-gradient(top, #E2EBFA, #BBCCDD); /* FF3.6 */
			background-image: -webkit-gradient(linear,left top,left bottom,from(#E2EBFA),to(#BBCCDD)); /* Saf4+, Chrome */
		}
		ul li p:hover {
		  background-color: #E2EBFA;
		  background-image: -moz-linear-gradient(top, #BBCCDD, #E2EBFA); /* FF3.6 */
		  background-image: -webkit-gradient(linear,left top,left bottom,from(#BBCCDD),to(#E2EBFA)); /* Saf4+, Chrome */
		}
		ul#admin li span:hover, ul#lu li span:hover {
			color: navy;
			text-decoration: underline;
		}
		#aniv_fecha_popup {
			float:right;
			margin-right: 3px;
			padding-top: 2px;
			width: 1.2em;
			cursor: pointer;
		}

	<?  if ($assist != true) { ?>
		 div#anivs {
			box-shadow: -1px -1px 3px #333;
			border: 1px solid #ccb;
			background-color: #FFF;
			font: normal normal 11px/12px Segoe UI, Verdana, Arial, Helvetica, sans-serif;
		}
		div#anivs > h2 {
			background-color: orange;
			color: #900;
			font-size: 13px!important;
			text-shadow: 1px 1px 2px white;
		}
		div#lista > h4 {
			text-align: left;
			font-size: 10px;
			line-height: 1.5em!important;
			color: #63798D;
		}
		ul li p {
			background-color: white;
			background-image: -moz-linear-gradient(top, white, #ddd); /* FF3.6 */
			background-image: -webkit-gradient(linear,left top,left bottom,from(white),to(#ddd)); /* Saf4+, Chrome */
		}
		ul li p:hover {
			background-color: #ddd;
			background-image: -moz-linear-gradient(top, #ddd, white); /* FF3.6 */
			background-image: -webkit-gradient(linear,left top,left bottom,from(#ddd),to(white)); /* Saf4+, Chrome */
		}

	<?}?>
		</style>
		<script src="http://ww2.telecontrol.com.br/mlg/js/cookies.js" type="text/javascript" language="JavaScript"></script>
		<script type="text/javascript">
		$().ready(function() {
			$('#aniv_fecha_popup').click(function() {
				$('div#anivs').hide('slow').remove();
				setCookie('no_anivs','no_anivs','','',1);
			});
		});
		</script>
<?  if (!$included and $no_header) {   ?>
	</head>
	<body>
<?}?>
		<div id='anivs'>
			<h2><?=$aniv_titulo_popup?>!
				<img id='aniv_fecha_popup' title='Deshabilitar os avisos o resto do dia...'
					src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAAsBJREFUOE+dlbtLW3EUx2/t4iAiTg46RFCKoIOCgg46KOrgaxCfOOggoqCgoIMSo6ioqPgAwcmhHVppR/sHFNrF1vqKaWIe5mljW7LlAeXb3zk3v3tvJOngD7IlH76Pc05eRCIR/I3FFPfhoWKzWJTnvFdms2IaG1NeZmcryu+HB7wvKsKHwkJcjo4itL+P4PY2Ahsb8K+uwmexwLuwgPu5OXimp+GemoJrfBxO8V3nyAg+NzTgXV4eM4ilnJnNDPNvbf0fNjPDMPfEBMPuhodxNzQER18fbjo78TY3F8RSXiuKqmxvT1W2vg7/ygp8i4u6siRMKjPC7N3d+NHVhU9VVSAWA1NsCtif01P4lpdxPzur2ZTKyKZLCAgfH0PCbG1tuG1t1YHBnR01MwH7dXICevFQCF5hwT05qdkkmFvkF/N6+TuBzU1ImLWpSQdKGBXgX1tjGL3E4yM8QqUsgCwnRPD0Yh4PHIODuG1pgbWxETeiHM0yZ0Ztzs9zm6QsHgyqSv1+VaVBWTrYdV2dDtRghsxIWSIcVqGBAINlFI6BgRRlBLuqqdGBrMwAk22SMoLJR9mxTVEA26yvB8Oqq3FZWakD0w0tjQbZlMo4NwG09/SACngKu6io0IG8AYY2CWYsIO7zISY+0r6toyNF2UV5Ob6XlelAghmHNl0B9t5eRF0uhkadTlibm9mmhJ2XlurATDAaHy4gmZmtvV2LIOpw4FqMCik7LynBt+JiHWjczdDubsY5owJIGcHo+ZaWVJjJhK/iOGhzSBvAi97fz+v08+iIw9eGVrYpRoNsXtXWInRwALIpYWcFBTqQThBdDd5NcTm0dUpugJwzzky0yTafwD7m5KhAOjl0z+gE0dV4DuxLfj7eZGWp50seWLpndIIy2UxRJgqgzMgmKSOYdmDpL4CgRCfJz/nQb4nh9QbwD9Dd8KftOW7yAAAAAElFTkSuQmCC' />
			</h2>
			<div id='lista'>
	<?  if ($tem_aniv_admin && $e_admin) {  ?>
				<h4>Anivers&aacute;rios da <?=($login_fabrica_nome)?$login_fabrica_nome:'Telecontrol'?></h4>
				<ul id='admin'>
				<?=$admin_aniv_item?>
				</ul>
	<?  }?>
	<?  if ($tem_aniv_lu) { ?>
					<h4>Anivers&aacute;rios de funcion&aacute;rios dos postos</h4>
				<ul id='lu'>
				<?=$lu_aniv_item?>
				</ul>
				<br>
	<?  }?>
			</div>
		</div>
<?  if ($mlg_dir) {
		echo "<!-- $login_admin, $admin_nome, $login_unico - $lu_nome -->";
	}
	if (!$included) {   ?>
	</body>
</html>
	<?}?>
<?}
// if ($e_admin) p_echo ('É login admin'.$tot_admin);
// if ($included) echo 'Incluso!'; else echo 'Stand_alone!';
}
if ($debug and in_array($login_fabrica, $fabrica_nao_quer_anivs)) p_echo("Fábrica não quer que mostre aniversários: $login_fabrica");

