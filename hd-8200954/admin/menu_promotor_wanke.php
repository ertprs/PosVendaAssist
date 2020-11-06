<?php
if (!function_exists('menu_item')) {
	function menu_item($item, $bgcolor) {
		global $login_fabrica;
		if (!is_array($item)) return false;
		extract($item);
		if (isset($fabrica)) {
			if (!in_array($login_fabrica, $fabrica)) return false;
		}
		if (isset($fabrica_no)) {
			if (in_array($login_fabrica, $fabrica_no)) return false;
		}
	//  Agora sim...
		echo "\t<tr bgcolor='$bgcolor'>\n".
			 "\t\t<td width='25'><img src='imagens/$icone'></td>\n".
			 "\t\t<td nowrap width='250'>";
		if (is_array($titulo) and is_array($link)) {
			$num_titulos = count($titulo);
			for ($t=0; $t < $num_titulos; $t++) {
				echo ($t != 0)?"\n":'';
				echo "<a href='{$link[$t]}' class='menu'>{$titulo[$t]}</a>";
			}
		} else {
			echo "<a href='$link' class='menu'>$titulo</a>";
		}
		echo "</td>\n\t\t<td nowrap class='descricao'>$descr</td>\n\t</tr>\n";
		return true;
	}
}

/* Define os ítens do menu... 
 * HD 684194
 * - Consulta OS:		http://www.telecontrol.com.br/assist/admin/os_consulta_lite.php
 * - Consulta Pedidos:	http://www.telecontrol.com.br/assist/admin/pedido_parametros.php
 * - Abre Chamado:		http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php
 * - Consulta Chamado:	http://www.telecontrol.com.br/assist/admin/callcenter_parametros_interativo.php
 * - Cadastrar pedido:	http://www.telecontrol.com.br/assist/admin/pedido_cadastro.php
 * - Consultar posto:	http://www.telecontrol.com.br/assist/admin/posto_consulta.php
 * - Vista Explodida e Comunicados (apenas visualizar, conforme esta na aba Call Center):
 * 						http://www.telecontrol.com.br/assist/admin/comunicado_produto_consulta.php
 */

$menu_promotor_wanke = array(); //Inicializa o array...
$menu_promotor_wanke[] = array (
	'fabrica'	=> array(91),
	"icone"		=> 'tela25.gif',
	"titulo"	=> 'Consulta de Ordens de Serviço',
	"link"		=> 'os_consulta_lite.php',
	"descr"		=> 'Consulta OS Lançadas'
);
$menu_promotor_wanke[] = array (
	'fabrica'	=> array(91),
	"icone"		=> 'tela25.gif',
	"titulo"	=> 'Consulta Pedidos de Peças',
	"link"		=> 'pedido_parametros.php',
	"descr"		=> 'Consulta pedidos efetuados por postos autorizados.'
);
$menu_promotor_wanke[] = array (
	'fabrica'	=> array(91),
	"icone"		=> 'marca25.gif',
	"titulo"	=> 'Cadastra Atendimento Call-Center',
	"link"		=> 'callcenter_interativo_new.php',
	"descr"		=> 'Cadastro de Atendimento no Call-Center'
);
$menu_promotor_wanke[] = array (
	'fabrica'	=> array(91),
	"icone"		=> 'tela25.gif',
	"titulo"	=> 'Consulta Atendimentos Call-Center',
	"link"		=> 'callcenter_parametros_interativo.php',
	"descr"		=> 'Consulta atendimentos já lançados'
);
$menu_promotor_wanke[] = array (
	'fabrica'	=> array(91),
	"icone"		=> 'marca25.gif',
	"titulo"	=> 'Cadastro de Pedidos',
	"link"		=> 'pedido_cadastro.php',
	"descr"		=> 'Cadastra pedidos de peças'
);
$menu_promotor_wanke[] = array (
	'fabrica'	=> array(91),
	"icone"		=> 'tela25.gif',
	"titulo"	=> 'Consulta Postos',
	"link"		=> 'posto_consulta.php',
	"descr"		=> 'Consulta cadastro de postos autorizados.'
);
$menu_promotor_wanke[] = array (
	'fabrica'	=> array(91),
	"icone"		=> 'tela25.gif',
	"titulo"	=> 'Vista Explodida e Comunicados',
	"link"		=> 'comunicado_produto_consulta.php',
	"descr"		=> 'Consulta vista explodida, diagramas, esquemas e comunicados.'
);
?>
<style type='text/css'>
	body {
		text-align: center;
	}
	.cabecalho {
		color: black;
		border-bottom: 2px dotted WHITE;
		font-size: 12px;
		font-weight: bold;
	}
	.descricao {
		padding: 5px;
		color: black;
		font-size: 12px;
		font-weight: normal;
		text-align: justify;
	}
	a:link.menu {
		padding: 3px;
		display:block;
		font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
		color: navy;
		font-size: 12px;
		font-weight: bold;
		text-align: left;
		text-decoration: none;
	}
	a:visited.menu {
		padding: 3px;
		display:block;
		font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
		color: navy;
		font-size: 12px;
		font-weight: bold;
		text-align: left;
		text-decoration: none;
	}
	a:hover.menu {
		padding: 3px;
		display:block;
		font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
		color: black;
		font-size: 12px;
		font-weight: bold;
		text-align: left;
		text-decoration: none;
		background-color: #ced7e7;
	}
	/*#tbl_menu tr:nth-child(odd) {background-color:#f0f0f0!important}*/
</style>

<table border='0' id='tbl_menu' width='700px' border='0' cellpadding='0' cellspacing='0' align='center'>
<!-- ================================================================== -->
<?
$bgcolor = '#f0f0f0';
foreach ($menu_promotor_wanke as $menu_item) {
	menu_item($menu_item, $bgcolor);
    $bgcolor = ($bgcolor != '#FAFAFA')?'#FAFAFA':'#f0f0f0';
}
?>
<tr>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>

</table>


<? include "rodape.php" ?>
