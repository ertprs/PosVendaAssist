<?php

include_once 'funcoes.php';

return array(
	array(
		'link'		=> 'posto_login.php',
		'descr'		=> traduz('Logar como posto autorizado'),
		'titulo'	=> traduz('Logar'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'link'		=> 'bi/postos_usando.php',
		'descr'		=> traduz('Como os postos estão utilizando o site'),
		'titulo'	=> traduz('Postos Usando'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'link'		=> 'gasto_por_posto.php',
		'descr'		=> traduz('Gastos que os postos geram por ordem de serviço'),
		'titulo'	=> traduz('Gastos'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
);

