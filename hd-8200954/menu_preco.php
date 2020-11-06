<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once 'helpdesk/mlg_funciones.php';

$menu_preco[] = array(
	'icone'     => 'marca25.gif',
	'link'      =>iif(($login_fabrica!=1), 
						'tabela_precos.php',
						'tabela_precos_blackedecker_consulta.php'),
	'titulo'    => traduz('consulta.tabela.de.precos', $con),
	'descr'     => traduz('clique.aqui.para.consultar.a.tabela.de.precos', $con)
);

$menu_preco[] = array(
	'disabled'  => true,
	'fabrica'   => array(1),
	'icone'     => 'tela25.gif',
	'link'      => 'tabela_precos_blackedecker.php',
	'titulo'    => traduz('consulta.de.variacao.na.nova.tabela', $con),
	'descr'     => traduz('nova.tabela.de.precos', $con)
);
$menu_preco[]['link'] = 'linha_de_separação';

$title = traduz("menu.de.tabela.de.precos",$con);
$layout_menu = "preco";
include 'cabecalho.php';

if ($login_unico)
	menu_item(array(
				'link'	   => '#',
				'titulo'   => mb_strtoupper($title),
				'noexpand' => true
			  ), null,
			  'secao_admin');
menuTC($menu_preco, null, '#F8FBF6', '#FCFDFB');
/*
<hr>
<center>
Aprenda como usar o sistema de tabela de preços da Telecontrol
<br>
<iframe title="YouTube video player" width="320" height="195" src="http://www.youtube.com/embed/RE4TqzzZmAg" frameborder="0" allowfullscreen></iframe>
</center>

</hr>
*/
include "rodape.php";

