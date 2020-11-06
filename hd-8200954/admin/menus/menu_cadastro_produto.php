<?php
include_once 'funcoes.php';

msgBloqueioMenu();

//hd 19043 - Selecionei as fábricas que usam tbl_subproduto e coloquei no array. Ébano
$usam_subproduto         = array(43, 8, 3, 14, 46, 17, 66, 4, 10, 2, 5);
$vet_fabrica_multi_marca = array(3, 10, 30, 52, 104, 105, 125, 141, 144, 146, 169, 170, 176, 178, 194);
if($multimarca == 't')
	array_push($vet_fabrica_multi_marca, $login_fabrica);
if ($usaProdutoGenerico)
	array_push($vet_fabrica_multi_marca, $login_fabrica);
$fabrica_valores_adicionais = ($inf_valores_adicionais) ? array($login_fabrica) : array(0);

// Upload de arquivo para importação de S/N
$fabrica_integra_serie_upload = array(95,108,111,120,201,150,165);
// Máscara de Número de série
$fabrica_usa_mascara_serie    = array(3, 14, 35, 66, 72, 99, 101, 120, 140, 141, 144, 151, 154, 169, 170, 198, 201); // HD 86636 HD 264560

$cad0030_titulo = in_array($login_fabrica, array(117)) ? traduz('Manutenção de Macro-Famílias') : traduz('Linhas de Produtos');
$cad0030_descr = $novaTelaOs
	? traduz('Consulta de Linha de Produtos')
	: (in_array($login_fabrica, array(117))
		? traduz('Consulta - Inclusão - Exclusão de Macro-Família.')
		: traduz('Consulta - Inclusão - Exclusão de Linha de Produtos.')
	);

/** COMEÇA A DEFINIÇÃO DO ARRAY DO MENU **/
// Menu CADASTRO
return array(
	'secaoProdutos' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => in_array($login_fabrica, $fabricas_contrato_lite) ? traduz('CADASTROS DE PRODUTOS') : traduz('CADASTROS REFERENTES A PRODUTOS'),
			'fabrica_no' => array(87) // Deshabilitado para a JACTO
		),
		array(
			'fabrica'    => $vet_fabrica_multi_marca,
			'fabrica_no' => array(171),
			"icone"      => $icone["cadastro"],
			"link"       => 'marca_cadastro.php',
			"titulo"     => traduz('Marca de Produtos'),
			"descr"      => traduz('Consulta - Inclusão - Exclusão de Marcas.'),
			"codigo"     => "CAD-0000"
		),
		array(
			'fabrica'    => array(3,10),
			"icone"      => $icone["cadastro"],
			"link"       => 'produto_fornecedor_cadastro.php',
			"titulo"     => traduz('Fornecedor de Produtos'),
			"descr"      => traduz('Consulta - Inclusão - Exclusão de Fornecedores de Produto.'),
			"codigo"     => "CAD-0010"
		),
		array(
			"icone"      => $icone["cadastro"],
			"link"       => 'tipo_posto_cadastro.php',
			"titulo"     => traduz('Tipo de Postos'),
			"descr"      => $novaTelaOs
				? traduz('Consulta de Tipo de Postos')
				: traduz('Consulta - Inclusão - Exclusão dos Tipos de Postos.'),
			"codigo"     => "CAD-0020"
		),
		array(
			"icone"      => $icone["cadastro"],
			"link"       => 'linha_cadastro.php',
			"titulo"     => $cad0030_titulo,
			"descr"      => $cad0030_descr,
			"codigo"     => "CAD-0030"
		),
		array(
			"icone"      => $icone["cadastro"],
			"link"       => 'familia_cadastro.php',
			"titulo"     => traduz('Família de Produtos'),
			"descr"      => traduz('Consulta - Inclusão - Exclusão de Família de Produtos.'),
			"codigo"     => "CAD-0040"
		),
		array(
			"icone"      => $icone["cadastro"],
			"link"       => 'produto_cadastro.php',
			"titulo"     => traduz('Cadastro de Produtos'),
			"descr"      => traduz('Consulta - Inclusão - Exclusão de Produtos.'),
			"codigo"     => "CAD-0050"
		),
		array(
			'fabrica'    => array(52, 151, 158),
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_numero_serie_bloqueado.php',
			'titulo'     => traduz('Cadastro de Número de Série Bloqueado'),
			'descr'      => traduz('Cadastro de Número de Série Bloqueado.'),
			"codigo"     => "CAD-0060"
		),
		array(
			'fabrica'    => array(50, 24, 120,201),
			'icone'      => $icone["cadastro"],
			'link'       => 'custo_falha_cadastro.php',
			'titulo'     => 'Cadastro de Custo Falha',
			'descr'      => 'Cadastro de Custo Falha.',
			"codigo"     => "CAD-0060"
		),
		array(
			'fabrica'    => array(50),
			'icone'      => $icone["cadastro"],
			'link'       => 'custo_falha_cadastro_v1779248.php',
			'titulo'     => traduz('Cadastro de Custo Falha (Por Região)'),
			'descr'      => traduz('Cadastro de Custo Falha (Por Região).'),
			"codigo"     => "CAD-0070"
		),
		array(
			'fabrica'    => $usam_subproduto,
			"icone"      => $icone["cadastro"],
			"link"       => 'subproduto_cadastro.php',
			"titulo"     => traduz('Cadastro de Sub-Produtos'),
			"descr"      => traduz('Consulta - Inclusão - Exclusão de Sub-Produtos.'),
			"codigo"     => "CAD-0080"
		),
		array(
			'fabrica'    => 42,
			"icone"      => $icone["cadastro"],
			"link"       => 'classe_produto_cadastro.php',
			"titulo"     => traduz('Cadastro de classes de produtos'),
			"descr"      => traduz('Cadastro de classes de produtos, onde poderá ser feito a inserção de novas classes ou alteração das classes já existentes'),
			"codigo"     => "CAD-0090"
		),
		array(
			'fabrica'    => array(7,10,11,40,172),
			'icone'      => $icone["cadastro"],
			'link'       => 'transportadora_cadastro.php',
			'titulo'     => traduz('Cadastro de Transportadora'),
			'descr'      => traduz('Consulta - Inclusão - Exclusão de Transportadoras.'),
			"codigo"     => "CAD-0100"
		),
		array(
			'fabrica'    => array(11,3,157,172,169,170,176,183),
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_produto_nserie.php',
			'titulo'     => traduz('Cadastro de Número de Série Bloqueio'),
			'descr'      => traduz('Consulta - Cadastro - de Número de Série Bloqueada.'),
			"codigo"     => "CAD-0110"
		),
		array(
			'fabrica'    => array(14,66),
			"icone"      => $icone["consulta"],
			"link"       => 'produto_consulta_detalhe.php',
			"titulo"     => traduz('Estrutura do produto'),
			"descr"      => traduz('Consulta dados da estrutura do produto (Produto &gt; Subconjunto &gt; Peças).'),
			"codigo"     => "CAD-0120"
		),
		array(
			'fabrica'    => 5,
			"icone"      => $icone["cadastro"],
			"link"       => 'serie_controle_cadastro.php',
			"titulo"     => 'Cadastro de Números de Série',
			"descr"      => 'Consulta - Inclusão - Exclusão de Número de Série e quantidade produzida por produto.',
			"codigo"     => "CAD-0130"
		),
		array(
			'fabrica'    => 30,
			"icone"      => $icone["cadastro"],
			"link"       => 'metas_cadastro.php',
			"titulo"     => 'Cadastro de Metas',
			"descr"      => 'Cadastro de metas de produtos e famílias',
			"codigo"     => "CAD-0140"
		),
		array(
			'fabrica'    => 30,
			"icone"      => $icone["cadastro"],
			"link"       => 'cadastro_justificativa.php',
			"titulo"     => 'Cadastro de Justificativa',
			"descr"      => 'Cadastro de justificativa para agendamento de visita',
			"codigo"     => "CAD-0230"
		),
		array(
			'fabrica'    => $fabrica_valores_adicionais,
			'fabrica_no' => array(163,173,186,191),
			"icone"      => $icone["cadastro"],
			"link"       => 'cadastro_valores_adicionais_familia_produto.php',
			"titulo"     => traduz('Cadastro de Valores adicionais por família'),
			"descr"      => traduz('Cadastro de valores adicionais por família, onde todos os produtos dessa família irão assumir esse valores'),
			"codigo"     => "CAD-0150"
		),
		array(
			'fabrica'    => $fabrica_valores_adicionais,
			'fabrica_no' => array(138,176,178,191,193,203),
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_valores_adicionais.php',
			'titulo'     => traduz('Cadastro de serviços e valores adicionais para a OS'),
			'descr'      => traduz('Cadastro de serviços e valores adicionais para a OS.'),
			"codigo"     => 'CAD-0160'
		),
		array(
			'fabrica'    => array(138),
			'icone'      => $icone["cadastro"],
			'link'       => 'subproduto.php',
			'titulo'     => traduz('Cadastro de Subprodutos'),
			'descr'      => traduz('Consulta - Inclusão - Exclusão de Subprodutos'),
			"codigo"     => 'CAD-0170'
		),
		array(
			'fabrica'	=> array(10,117),
			'icone'     => $icone['cadastro'],
			'link'		=> 'macro_linha.php',
			'titulo'	=> traduz('Linhas'),
			'descr'		=> traduz('Relacionamento de Linha x Macro-Famílias'),
			'codigo'	=> 'CAD-0180',
		),
		 array(
			'fabrica' => array(117),
			'icone'   => $icone["cadastro"],
			'link'    => 'cadastro_parque_instalado.php',
			'titulo'  => traduz('Cadastro de Parque Instalado'),
			'descr'   => traduz('Quantidade de produtos que foram vendidos por período.'),
			"codigo"  => "CAD-0190"
		),
		array(
			'fabrica' => array(138),
			'icone' => $icone['cadastro'],
			'link' => 'cadastro_familia_produto_valor_adicional.php',
			'titulo' => traduz('Cadastro de Valores Adicionais por família'),
			'descr' => traduz('Cadastra os valores adicionais de recarga de gás por família'),
			'codigo' => 'CAD-0200'
		),
		array(
			'fabrica'    => array(24),
			'icone'      => $icone["cadastro"],
			'link'       => 'numero_serie_cadastro.php',
			'titulo'     => traduz('Cadastro de Nº de Série'),
			'descr'      => traduz('Cadastro e Manutenção de Número de Série'),
			"codigo"     => "CAD-0210"
		),
		array(
			'fabrica' => $fabrica_usa_mascara_serie, // HD 86636 HD 264560
			'icone'   => $icone["cadastro"],
			'link' => ((isset($usa_versao_produto) or isset($novaTelaOs) or $login_fabrica == 72) ? 'serie_mascara_cadastro.php' : 'produto_serie_mascara.php'),
			'titulo'  => traduz('Máscara de Núm. de Série'),
			'descr'   => traduz('Cadastro e Manutenção de Máscara de Número de Série.'),
			"codigo"  => "CAD-0220"
		),
		array(
			'fabrica'    => array(52),
			'icone'      => $icone["cadastro"],
			'link'       => 'importa_campos_callcenter.php',
			'titulo'     => traduz("Importação de Campos %", null, null, [$login_fabrica_nome]),
			'descr'      => traduz("Importação dos campos % utilizados no Call-Center através do nº de série", null, null, [$login_fabrica_nome]),
			"codigo"     => "CAD-0230"
		),
		array(
			'fabrica' => array(151),
			'icone'   => $icone["cadastro"],
			'link' 	  => 'informa_estoque_produto.php',
			'titulo'  => 'Informa Estoque Produto',
			'descr'   => 'Informa Estoque Produto',
			"codigo"  => "CAD-0240"
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["cadastro"],
			'link' 	  => 'cadastro_logomarca.php',
			'titulo'  => 'Cadastro Logomarcas',
			'descr'   => 'Cadastro Logomarcas',
			"codigo"  => "CAD-0250"
		),
		array(
			'fabrica'    => array(143),
			"icone"      => $icone["cadastro"],
			"link"       => 'posto_linha_tabela_de_preco.php',
			"titulo"     => traduz('Posto X Linha X Tabela de Preço'),
			"descr"      => $novaTelaOs
				? traduz('Posto X Linha X Tabela de Preço')
				: traduz('Consulta - Inclusão - Exclusão dos Tipos de Postos.'),
			"codigo"     => "CAD-0250"
		),
		array(
			'fabrica' => array(151),
			'icone'   => $icone["cadastro"],
			'link' 	  => 'peca_defeito_garantia.php',
			'titulo'  => 'Peça X Defeito X Garantia',
			'descr'   => 'Peça X Defeito X Garantia',
			"codigo"  => "CAD-0260"
		),
		'link'       => 'linha_de_separação'
	),
);

