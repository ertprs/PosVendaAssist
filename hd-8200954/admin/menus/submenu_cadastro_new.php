<?
include_once 'funcoes.php';
$vet_fabrica_multi_marca  = array(3, 10, 30, 52, 101,20,11, 104, 105,125, 141,144,146, 172);
if($multimarca == 't') array_push($vet_fabrica_multi_marca, $login_fabrica);

return array(
	array(
		'fabrica'	=> $vet_fabrica_multi_marca,
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
		'attr'    	=> "style='cursor:not-allowed'"
	),
	array(
		'fabrica'   => 87,
		'link'		=> 'lbm_cadastro.php',
		'descr'		=> traduz('Cadastro da lista b�sica (pe�as que comp�e o produto)'),
		'titulo'	=> traduz('Lista B�sica'),
		'attr'    	=> "style='cursor:not-allowed'"
	),
	array(
		'fabrica_no'=> array(87,117),
		'link'		=> 'linha_cadastro.php',
		'descr'		=> traduz('Cadastro de linhas de produtos'),
		'titulo'	=> traduz('Linhas')
	),
    array(
        'fabrica'	=> array(117),
        'link'      => 'macro_linha.php',
        'descr'     => traduz('Cadastro de linhas de produtos'),
        'titulo'    => traduz('Linhas')
    ),
    array(
        'fabrica'	=> array(117),
        'link'      => 'linha_cadastro.php',
        'descr'     => traduz('Cadastro de Macro - Fam�lias de produtos'),
        'titulo'    => traduz('Macro - Fam�lias')
    ),	
	array(
		'fabrica_no'=> 87,
		'link'		=> 'familia_cadastro.php',
		'descr'		=> traduz('Cadastro de fam�lias de produtos'),
		'titulo'	=> traduz('Fam�lias')
	),
	array(
		'fabrica_no'=> 87,
		'link'		=> 'produto_cadastro.php',
		'descr'		=> traduz('Cadastro de produtos acabados'),
		'titulo'	=> traduz('Produtos')
	),
	array(
		'link'		=> 'peca_cadastro.php',
		'descr'		=> traduz('Cadastro de pe�as e componentes'),
		'titulo'	=> traduz('Pe�as')
	),
	array(
		'fabrica_no'=> [87,189],
		'link'		=> 'lbm_cadastro.php',
		'descr'		=> traduz('Cadastro da lista b�sica (pe�as que comp�e o produto)'),
		'titulo'	=> traduz('Lista B�sica')
	),
	array(
		'link'		=> 'preco_cadastro.php',
		'descr'		=> traduz('Cadastro manual dos pre�os das pe�as'),
		'titulo'	=> traduz('Pre�os')
	),
	array(
		'link'		=> 'posto_cadastro.php',
		'descr'		=> ($login_fabrica == 189) ? traduz('Cadastro de Representantes/Revendas') : traduz('Cadastro de postos autorizados'),
		'titulo'	=> ($login_fabrica == 189) ? traduz('Representantes/Revendas') : traduz('Postos')
	),
);
