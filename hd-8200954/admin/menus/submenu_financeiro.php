<?php
include_once 'funcoes.php';
return array(
	array(
		'fabrica'	=> 3,
		'link'		=> 'devolucao_cadastro.php',
		'descr'		=> traduz('Cadastro de Notas de Devolução para postos que fazem encontro de contas'),
		'titulo'	=> traduz('NFs Devolução'),
		'attr'    => "class='submenu_telecontrol'"
	),
	array(
		'fabrica'	=> 3,
		'link'		=> 'acerto_contas.php',
		'descr'		=> traduz('Encontro de Contas'),
		'titulo'	=> traduz('Encontro de Contas'),
		'attr'    => "class='submenu_telecontrol'"
	),
	array(
		'fabrica'	=> array(11,50),
		'link'		=> 'os_extrato_por_posto.php',
		'descr'		=> traduz('Fechamento dos extratos'),
		'titulo'	=> traduz('Fecha Extrato'),
		'attr'    => "class='submenu_telecontrol'"
	),
	array(
		'fabrica'	=> array(),
		'link'		=> 'extrato_consulta.php',
		'descr'		=> traduz('Liberação e manutenção dos extratos já fechados'),
		'titulo'	=> traduz('Libera Extrato'),
		'attr'    => "class='submenu_telecontrol'"
	),
	array(
		'fabrica_no'=> array(3,20,74),//PARA A BOSCH SÓ LANÇA AVULSO NO EXTRATO
		'link'		=> 'extrato_avulso.php',
		'descr'		=> traduz('Lançamentos avulsos no extrato do posto'),
		'titulo'	=> traduz('Extrato Avulso'),
		'attr'    => "class='submenu_telecontrol'"
	),
    array(
		'fabrica'=> array(3,74),
		'link'		=> 'extrato_avulso_cadastro.php',
		'descr'		=> traduz('Lançamentos avulsos no extrato do posto'),
		'titulo'	=> traduz('Extrato Avulso'),
		'attr'    => "class='submenu_telecontrol'"
	),
);

