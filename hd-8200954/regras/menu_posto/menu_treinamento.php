<?php
$winOpts  = 'toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0';
$winURL   = 'configuracao.php'  . iif(($cook_idioma == 'es'), '?sistema_lingua=ES', '');
$link_lng = ($cook_idioma == 'es') ? 'es-es' : $cook_idioma; // Para o Site do IE, 'es' não serve.

$menu_treinamento = array(
	'title' => traduz("treinamentos",$con),
	array (
		'disabled' => false,
		'fabrica'  => array(42),
		'icone'    => 'marca25.gif',
		'link'     => 'treinamento_agenda.php',
		'titulo'   => traduz('Agendar Treinamentos', $con),
		'descr'    => traduz('Agenda de treinamento para postos autorizados', $con)
	),
	array (
		'disabled' => false,
		'fabrica'  => array(42),
		'icone'    => 'tela25.gif',
		'link'     => 'comunicado_mostra.php?tipo=Treinamento+Telecontrol',
		'titulo'   => traduz('Treinamentos Telecontrol', $con),
		'descr'    => traduz('Treinamentos de como utilizar as principais tarefas Telecontrol', $con)
	),
	'linha_de_separação'
);

return $menu_treinamento;

