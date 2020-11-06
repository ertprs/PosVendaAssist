<?
include_once 'funcoes.php';
return array(
	array(
		'disabled'  => ($multimarca != 't'),
		'fabrica_no'=> 101,
		'link'		=> 'marca_cadastro.php',
		'descr'		=> traduz('Cadastro de Marcas'),
		'titulo'	=> traduz('Marcas'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Cadastro de linhas de produtos'),
		'titulo'	=> traduz('Linhas'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class=submenu_telecontrol")
	),
	array(
		'fabrica'   => 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Cadastro de fam�lias de produtos'),
		'titulo'	=> traduz('Fam�lias'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class=submenu_telecontrol")
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'titulo'	=> traduz('Produtos'),
		'descr'		=> traduz('Cadastro de produtos acabados'),
		'attr'    	=> array("style='cursor:not-allowed'",
						   "class=submenu_telecontrol")
	),
	array(
		'fabrica'   => 87,
		'link'		=> 'lbm_cadastro.php',
		'descr'		=> traduz('Cadastro da lista b�sica (pe�as que comp�e o produto)'),
		'titulo'	=> traduz('Lista B�sica'),
		'attr'    	=> array("style='cursor:not-allowed'",
						   "class=submenu_telecontrol")
	),
	array(
		'fabrica_no'=> array(87,117),
		'link'		=> 'linha_cadastro.php',
		'descr'		=> traduz('Cadastro de linhas de produtos'),
		'titulo'	=> traduz('Linhas'),
		'attr'    => "class=submenu_telecontrol"
	),
    array(
        'fabrica'	=> array(117),
        'link'      => 'macro_linha.php',
        'descr'     => traduz('Cadastro de linhas de produtos'),
        'titulo'    => traduz('Linhas'),
        'attr'    => "class=submenu_telecontrol"
    ),
    array(
        'fabrica'	=> array(117),
        'link'      => 'linha_cadastro.php',
        'descr'     => traduz('Cadastro de Macro - Fam�lias de produtos'),
        'titulo'    => traduz('Macro - Fam�lias'),
        'attr'    => "class=submenu_telecontrol"
    ),	
	array(
		'fabrica_no'=> 87,
		'link'		=> 'familia_cadastro.php',
		'descr'		=> traduz('Cadastro de fam�lias de produtos'),
		'titulo'	=> traduz('Fam�lias'),
		'attr'      => "class=submenu_telecontrol"
	),
	array(
		'fabrica_no'=> 87,
		'link'		=> 'produto_cadastro.php',
		'descr'		=> traduz('Cadastro de produtos acabados'),
		'titulo'	=> traduz('Produtos'),
		'attr'    	=> "class=submenu_telecontrol"
	),
	array(
		'link'		=> 'peca_cadastro.php',
		'descr'		=> traduz('Cadastro de pe�as e componentes'),
		'titulo'	=> traduz('Pe�as'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'fabrica_no'=> [87,189],
		'link'		=> 'lbm_cadastro.php',
		'descr'		=> traduz('Cadastro da lista b�sica (pe�as que comp�e o produto)'),
		'titulo'	=> traduz('Lista B�sica'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'link'		=> 'preco_cadastro.php',
		'descr'		=> traduz('Cadastro manual dos pre�os das pe�as'),
		'titulo'	=> traduz('Pre�os'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'link'		=> 'posto_cadastro.php',
		'descr'		=> ($login_fabrica == 189) ? traduz('Cadastro de  Representantes/Revendas') : traduz('Cadastro de postos autorizados'),
		'titulo'	=> ($login_fabrica == 189) ? traduz('Representantes/Revendas') : traduz('Postos'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
);

