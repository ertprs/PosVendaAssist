<?php
include_once 'funcoes.php';

$fabrica_no = array(138,142,143,145,146);

if($login_fabrica > 146){
	$fabrica_no[] = $login_fabrica;
}

return array(
	array(
		'link'		=> 'comunicado_produto.php',
		'descr'		=> traduz('Cadastro de comunicados, vistas explodidas, manuais, etc.'),
		'titulo'	=> traduz('Comunicados')
	),
	array(
		'fabrica'   => 19,
		'link'		=> 'confirmacao_comunicado_leitura.php',
		'descr'		=> traduz('Acompanhamento de leitura dos comunicados na entrada do site pelos postos.'),
		'titulo'	=> traduz('Postos e comunicados')
	),
	array(
		'fabrica_no'=> array_merge(array(1), $fabrica_no),
		'link'		=> 'forum.php',
		'descr'		=> traduz('Painel de mensagens, perguntas e sugestões dos postos'),
		'titulo'	=> traduz('Forum')
	),
	array(
		'fabrica'   => 1,
		'link'		=> 'helpdesk_listar.php',
		'descr'		=> traduz('Help-Desk Postos Autorizados'),
		'titulo'	=> traduz('HelpDesk Postos')
	),
	array(
		'fabrica_no'	=> $fabrica_no,
		'link'		=> 'forum_moderado.php',
		'descr'		=> traduz('Liberação das mensagens do painel'),
		'titulo'	=> traduz('Forum Moderado')
	),
);

