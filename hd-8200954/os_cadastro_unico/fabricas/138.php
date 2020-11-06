<?php

use html\util\SQLSelectOptions;

$array_estados = include "os_cadastro_unico/array_estados.php";
$sqlCallback = new SQLSelectOptions("SELECT tipo_atendimento AS value, descricao AS label, km_google FROM tbl_tipo_atendimento WHERE fabrica = 138 AND ativo IS TRUE");
$sqlServicoCallback = new SQLSelectOptions('SELECT servico_realizado,descricao FROM tbl_servico_realizado WHERE fabrica = 138 AND ativo IS TRUE;');
/*return array(
	"os" => array(
		"data_abertura" => array(
			"id"        => "data_abertura",
			"span"      => 2,
			"label"     => "Data Abertura",
			"type"      => "input/text",
			"width"     => 9,
			"required"  => true
		),
		"tipo_atendimento" => array(
			"id"        => "tipo_atendimento",
			"span"      => 3,
			"label"     => "Tipo de Atendimento",
			"type"      => "select",
			"width"     => 12,
			"maxlength" => 20,
			"required"  => true,
			"options"   => array(
				"sql_query" => $sql_tipo_atendimento,
				"extra"     => "km_google"
			)
		),
		"nota_fiscal" => array(
			"id"        => "nota_fiscal",
			"span"      => 2,
			"label"     => "Nota Fiscal",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 20,
			"required"  => true
		),	
		"data_nf" => array(
			"id"        => "data_nf",
			"span"      => 2,
			"label"     => "Data Compra",
			"type"      => "input/text",
			"width"     => 9,
			"required"  => true
		),
		"defeito_reclamado_descricao" => array(
			"id"        => "",
			"span"      => 3,
			"label"     => "Defeito Reclamado",
			"type"      => "input/text",
			"width"     => 12,
			"required"  => true
		),
		"aparencia_produto" => array(
			"id"        => "aparencia_produto",
			"span"      => 3,
			"label"     => "Aparência do Produto",
			"type"      => "input/text",
			"width"     => 12
		),
		"acessorios" => array(
			"id"        => "acessorios",
			"span"      => 3,
			"label"     => "Acessórios",
			"type"      => "input/text",
			"width"     => 12
		)
	),
	"consumidor" => array(
		"consumidor_nome" => array(
			"id"        => "consumidor_nome",
			"span"      => 4,
			"label"     => "Nome",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 50,
			"required"  => true
		),
		"consumidor_cpf" => array(
			"id"        => "consumidor_cpf",
			"span"      => 4,
			"label"     => "CPF",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 14
		),
		"consumidor_cep" => array(
			"id"        => "consumidor_cep",
			"span"      => 2,
			"label"     => "CEP",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 8
		),
		"consumidor_estado" => array(
			"id"        => "consumidor_estado",
			"span"      => 2,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 12,
			"required"  => true,
			"options"   => $array_estados
		),
		"consumidor_cidade" => array(
			"id"        => "consumidor_cidade",
			"span"      => 2,
			"label"     => "Cidade",
			"type"      => "select",
			"width"     => 12,
			"required"  => true
		),
		"consumidor_bairro" => array(
			"id"        => "consumidor_bairro",
			"span"      => 3,
			"label"     => "Bairro",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 80
		),
		"consumidor_endereco" => array(
			"id"        => "consumidor_endereco",
			"span"      => 3,
			"label"     => "Endereço",
			"type"      => "input/text",
			"width"     => 12
		),
		"consumidor_numero" => array(
			"id"        => "consumidor_numero",
			"span"      => 2,
			"label"     => "Número",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"consumidor_complemento" => array(
			"id"        => "consumidor_complemento",
			"span"      => 2,
			"label"     => "Complemento",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"consumidor_fone" => array(
			"id"        => "consumidor_fone",
			"span"      => 3,
			"label"     => "Telefone",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 20
		),
		"consumidor_email" => array(
			"id"        => "consumidor_email",
			"span"      => 3,
			"label"     => "Email",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 50
		)
	),
	"revenda" => array(
		"revenda_nome" => array(
			"id"        => "revenda_nome",
			"span"      => 4,
			"label"     => "Nome",
			"type"      => "input/text",
			"width"     => 10,
			"maxlength" => 50,
			"required"  => true,
			"lupa" => array(
				"name"      => "lupa",
				"tipo"      => "revenda",
				"parametro" => "razao_social",
				"extra"     => array(
	                "revenda" => "true",
	                'cnpj_not_null' => 'true'
	            )
	        ),
		),	
		"revenda_cnpj" => array(
			"id"        => "revenda_cnpj",
			"span"      => 4,
			"label"     => "CNPJ",
			"type"      => "input/text",
			"width"     => 10,
			"maxlength" => 14,
			"required"  => true,
			"lupa" => array(
	            "name" => "lupa",
	            "tipo" => "revenda",
	            "parametro" => "cnpj",
	            "extra" => array(
	                "revenda" => "true",
	                'cnpj_not_null' => 'true'
	            )
	        ),
		),
		"revenda[cep]" => array(
			"id"        => "revenda_cep",
			"span"      => 2,
			"label"     => "CEP",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 8
		),
		"revenda[estado]" => array(
			"id"        => "revenda_estado",
			"span"      => 2,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 12,
			"options"   => $array_estados,
			"required"  => true
		),
		"revenda[cidade]" => array(
			"id"        => "revenda_cidade",
			"span"      => 2,
			"label"     => "Cidade",
			"type"      => "select",
			"width"     => 12,
			"required"  => true
		),
		"revenda[bairro]" => array(
			"id"        => "revenda_bairro",
			"span"      => 3,
			"label"     => "Bairro",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 80
		),
		"revenda[endereco]" => array(
			"id"        => "revenda_endereco",
			"span"      => 3,
			"label"     => "Endereço",
			"type"      => "input/text",
			"width"     => 12
		),
		"revenda[numero]" => array(
			"id"        => "revenda_numero",
			"span"      => 2,
			"label"     => "Número",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"revenda[complemento]" => array(
			"id"        => "revenda_complemento",
			"span"      => 2,
			"label"     => "Complemento",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"revenda[fone]" => array(
			"id"        => "revenda_fone",
			"span"      => 3,
			"label"     => "Telefone",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 20
		)
	),
	"produto" => array(
		"produto[*][produto]" => array(
			"id"        => "",
			"type"      => "input/hidden",
			"required"  => true,
			"extra"     => array(
				
			)
		),
		"produto[*][referencia]" => array(
			"id"        => "produto[*][referencia]",
			"span"      => 2,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"extra"     => array(
				
			),
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "referencia",
	            "extra" => array(
	                "posicao" => "*",
	                "ativo" => true,
	                "list-replace" => "*"
	            )
	        )
		),
		"produto[*][descricao]" => array(
			"id"        => "produto[*][descricao]",
			"span"      => 4,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"extra"     => array(
				
			),
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "descricao",
	            "extra" => array(
	                "posicao" => "*",
	                "ativo" => true,
	                "list-replace" => "*"
	            )
	        )
		),
		"produto[*][voltagem]" => array(
			"id"        => "produto[*][voltagem]",
			"span"      => 2,
			"label"     => "Voltagem",
			"type"      => "input/text",
			"width"     => 8,
			"readonly"  => true,
			"extra"     => array(
				
			)
		),
		"produto[*][serie]" => array(
			"id"        => "produto[*][serie]",
			"span"      => 2,
			"label"     => "Número de Série",
			"type"      => "input/text",
			"width"     => 12,
			"required"  => true,
			"extra"     => array(
				
			)
		),
		"produto[*][defeito_constatado]" => array(
			"id"        => "produto[*][defeito_constatado]",
			"span"      => 3,
			"label"     => "Defeito Constatado",
			"type"      => "select",
			"width"     => 12,
			"extra"     => array(
				
			)
		),
		"produto[*][servico]" => array(
			"id" => "produto[*][servico]",
			"span" => 3,
			"label" => "Serviço",
			"type" => "select",
			"width" => 12,
			"extra" => array(
				
			)
		)
	),
	"item" => array(
		"produto[*produto*][os_item][*item*][produto]" => array(
			"id"        => "produto[*produto*][os_item][*item*][produto]",
			"type"      => "input/hidden",
			"extra"     => array(
				
			)
		),
		"produto[*produto*][os_item][*item*][peca]" => array(
			"id"        => "produto[*produto*][os_item][*item*][peca]",
			"type"      => "input/hidden",
			"extra"     => array(
				
			)
		),
		"produto[*produto*][os_item][*item*][referencia]" => array(
			"id"        => "produto[*produto*][os_item][*item*][referencia]",
			"span"      => 2,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"extra"     => array(
				
			),
			"lupa" => array(
				"name"      => "lupa_config",
				"tipo"      => "lista_basica",
				"parametro" => "referencia",
				"extra"     => array(
					"posicao"         => "*item*",
					"posicao_produto" => "*produto*",
					"produto"         => "",
					"list-replace" => ""
	            )
	        )
		),
		"produto[*produto*][os_item][*item*][descricao]" => array(
			"id"        => "produto[*produto*][os_item][*item*][descricao]",
			"span"      => 4,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"extra"     => array(
				
			),
			"lupa" => array(
				"name"      => "lupa_config",
				"tipo"      => "lista_basica",
				"parametro" => "descricao",
				"extra"     => array(
					"posicao"         => "*item*",
					"posicao_produto" => "*produto*",
					"produto"         => "",
					"list-replace" => ""
	            )
	        )
		),
		"produto[*produto*][os_item][*item*][qtde]" => array(
			"id"        => "produto[*produto*][os_item][*item*][qtde]",
			"span"      => 1,
			"label"     => "Qtde",
			"type"      => "input/numeric",
			"width"     => 12,
			"maxlength" => 5,
			"extra"     => array(
				
			)
		),
		"produto[*produto*][os_item][*item*][defeito]" => array(
			"id"        => "produto[*produto*][os_item][*item*][defeito]",
			"span"      => 2,
			"label"     => "Defeito",
			"type"      => "select",
			"width"     => 12,
			"extra"     => array(
				
			)
		),
		"produto[*produto*][os_item][*item*][gera_pedido]" => array(
			"id"	=> "produto[*produto*][os_item][*item*][gera_pedido]",
			"span"	=> "1",
			"type"	=> "input/checkbox",
			"label" => "Troca",
			"checks" => array("t"=>""),
			"extra"	=> array(
				
			)
		)

	),
	"obs" => array(
		"obs" => array(
			"id"    => "obs",
			"span"  => 10,
			"type"  => "textarea",
			"width" => 12,
			"extra" => array(
				"style" => "width: 100%;"
			)
		),
	)
);*/


return array(
	"os" => array(
		"os[data_abertura]" => array(
			"id"        => "data_abertura",
			"span"      => 2,
			"label"     => "Data Abertura",
			"type"      => "input/text",
			"width"     => 9,
			"required"  => true
		),
		"os[tipo_atendimento]" => array(
			"id"        => "tipo_atendimento",
			"span"      => 3,
			"label"     => "Tipo de Atendimento",
			"type"      => "select",
			"width"     => 12,
			"maxlength" => 20,
			"required"  => true,
			"content" 	=> $sqlCallback,
		),
		"os[nota_fiscal]" => array(
			"id"        => "nota_fiscal",
			"span"      => 2,
			"label"     => "Nota Fiscal",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 20,
			"required"  => true
		),	
		"os[data_nf]" => array(
			"id"        => "data_nf",
			"span"      => 2,
			"label"     => "Data Compra",
			"type"      => "input/text",
			"width"     => 9,
			"required"  => true
		),
		"os[defeito_reclamado_descricao]" => array(
			"id"        => "",
			"span"      => 3,
			"label"     => "Defeito Reclamado",
			"type"      => "input/text",
			"width"     => 12,
			"required"  => true
		),
		"os[aparencia_produto]" => array(
			"id"        => "aparencia_produto",
			"span"      => 3,
			"label"     => "Aparência do Produto",
			"type"      => "input/text",
			"width"     => 12
		),
		"os[acessorios]" => array(
			"id"        => "acessorios",
			"span"      => 3,
			"label"     => "Acessórios",
			"type"      => "input/text",
			"width"     => 12
		)
	),
	"consumidor" => array(
		"os[consumidor_nome]" => array(
			"id"        => "consumidor_nome",
			"span"      => 4,
			"label"     => "Nome",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 50,
			"required"  => true
		),
		"os[consumidor_cpf]" => array(
			"id"        => "consumidor_cpf",
			"span"      => 4,
			"label"     => "CPF",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 14
		),
		"os[consumidor_cep]" => array(
			"id"        => "consumidor_cep",
			"span"      => 2,
			"label"     => "CEP",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 8
		),
		"os[consumidor_estado]" => array(
			"id"        => "consumidor_estado",
			"span"      => 2,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 12,
			"required"  => true,
			"content"   => $array_estados
		),
		"os[consumidor_cidade]" => array(
			"id"        => "consumidor_cidade",
			"span"      => 2,
			"label"     => "Cidade",
			"type"      => "select",
			"width"     => 12,
			"required"  => true
		),
		"os[consumidor_bairro]" => array(
			"id"        => "consumidor_bairro",
			"span"      => 3,
			"label"     => "Bairro",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 80
		),
		"os[consumidor_endereco]" => array(
			"id"        => "consumidor_endereco",
			"span"      => 3,
			"label"     => "Endereço",
			"type"      => "input/text",
			"width"     => 12
		),
		"os[consumidor_numero]" => array(
			"id"        => "consumidor_numero",
			"span"      => 2,
			"label"     => "Número",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"os[consumidor_complemento]" => array(
			"id"        => "consumidor_complemento",
			"span"      => 2,
			"label"     => "Complemento",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"os[consumidor_fone]" => array(
			"id"        => "consumidor_fone",
			"span"      => 3,
			"label"     => "Telefone",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 20
		),
		"os[consumidor_email]" => array(
			"id"        => "consumidor_email",
			"span"      => 3,
			"label"     => "Email",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 50
		),
	),
	"revenda" => array(
		"os[revenda_nome]" => array(
			"id"        => "revenda_nome",
			"span"      => 4,
			"label"     => "Nome",
			"type"      => "input/text",
			"width"     => 10,
			"maxlength" => 50,
			"required"  => true,
			"lupa" => array(
				"name"      => "lupa_config",
				"tipo"      => "revenda",
				"parametro" => "razao_social",
				"extra"     => array(
	                "revenda" => "true",
	                'cnpj_not_null' => 'true'
	            )
	        ),
		),	
		"os[revenda_cnpj]" => array(
			"id"        => "revenda_cnpj",
			"span"      => 4,
			"label"     => "CNPJ",
			"type"      => "input/text",
			"width"     => 10,
			"maxlength" => 14,
			"required"  => true,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "revenda",
	            "parametro" => "cnpj",
	            "extra" => array(
	                "revenda" => "true",
	                'cnpj_not_null' => 'true'
	            )
	        ),
		),
		"os[revenda][cep]" => array(
			"id"        => "revenda_cep",
			"span"      => 2,
			"label"     => "CEP",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 8
		),
		"os[revenda][estado]" => array(
			"id"        => "revenda_estado",
			"span"      => 2,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 12,
			"content"   => $array_estados,
			"required"  => true
		),
		"os[revenda][cidade]" => array(
			"id"        => "revenda_cidade",
			"span"      => 2,
			"label"     => "Cidade",
			"type"      => "select",
			"width"     => 12,
			"required"  => true
		),
		"os[revenda][bairro]" => array(
			"id"        => "revenda_bairro",
			"span"      => 3,
			"label"     => "Bairro",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 80
		),
		"os[revenda][endereco]" => array(
			"id"        => "revenda_endereco",
			"span"      => 3,
			"label"     => "Endereço",
			"type"      => "input/text",
			"width"     => 12
		),
		"os[revenda][numero]" => array(
			"id"        => "revenda_numero",
			"span"      => 2,
			"label"     => "Número",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"os[revenda][complemento]" => array(
			"id"        => "revenda_complemento",
			"span"      => 2,
			"label"     => "Complemento",
			"type"      => "input/text",
			"width"     => 8,
			"maxlength" => 20
		),
		"os[revenda][fone]" => array(
			"id"        => "revenda_fone",
			"span"      => 3,
			"label"     => "Telefone",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 20
		)
	),
	"solucao" => array(
		"os[solucao_os]" => array(
			"id" => "solucao",
			"span" => 10,
			"type" => "select",
			"width" => 7,
			"center" => true,
			"required" => true,
		)
	),
	"produto" => array(
		"os[os_produto][0][produto]" => array(
			"id"        => "",
			"type"      => "input/hidden",
			"required"  => true,
		),
		"os[os_produto][0][referencia]" => array(
			"id"        => "produto[0][referencia]",
			"span"      => 2,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "referencia",
	            "extra" => array(
	                "posicao" => "0",
	                "ativo" => true,
	                "temSubproduto" => true,
	            )
	        )
		),
		"os[os_produto][0][descricao]" => array(
			"id"        => "produto[0][descricao]",
			"span"      => 4,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "descricao",
	            "extra" => array(
	                "posicao" => "0",
	                "ativo" => true,
	                "temSubproduto" => true,
	            )
	        )
		),
		"os[os_produto][0][voltagem]" => array(
			"id"        => "produto[0][voltagem]",
			"span"      => 2,
			"label"     => "Voltagem",
			"type"      => "input/text",
			"width"     => 8,
			"readonly"  => true,
		),
		"os[os_produto][0][serie]" => array(
			"id"        => "produto[0][serie]",
			"span"      => 2,
			"label"     => "Número de Série",
			"type"      => "input/text",
			"width"     => 12,
			"required"  => true,
		),
		"os[os_produto][0][defeito_constatado]" => array(
			"id"        => "produto[0][defeito_constatado]",
			"span"      => 5,
			"label"     => "Defeito Constatado",
			"type"      => "select",
			"width"     => 12,
		),
	),
	"subproduto" => array(
		"os[os_produto][1][produto]" => array(
			"id"        => "",
			"type"      => "input/hidden",
			"required"  => true,
		),
		"os[os_produto][1][referencia]" => array(
			"id"        => "produto[1][referencia]",
			"span"      => 2,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "referencia",
	            "extra" => array(
	                "posicao" => "1",
	                "ativo" => true,
	                "produtoPai" => 0
	            )
	        )
		),
		"os[os_produto][1][descricao]" => array(
			"id"        => "produto[1][descricao]",
			"span"      => 4,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "descricao",
	            "extra" => array(
	                "posicao" => "1",
	                "ativo" => true,
	                "produtoPai" => 0
	            )
	        )
		),
		"os[os_produto][1][voltagem]" => array(
			"id"        => "produto[0][voltagem]",
			"span"      => 2,
			"label"     => "Voltagem",
			"type"      => "input/text",
			"width"     => 8,
			"readonly"  => true,
		),
		"os[os_produto][1][serie]" => array(
			"id"        => "produto[0][serie]",
			"span"      => 2,
			"label"     => "Número de Série",
			"type"      => "input/text",
			"width"     => 12,
			"required"  => true,
		),
		"os[os_produto][1][defeito_constatado]" => array(
			"id"        => "produto[0][defeito_constatado]",
			"span"      => 5,
			"label"     => "Defeito Constatado",
			"type"      => "select",
			"width"     => 12,
		),
	),
	"produto_item" => array(
		"os[os_produto][0][os_item][*item*][peca]" => array(
			"id"        => "produto[*produto*][os_item][*item*][peca]",
			"type"      => "input/hidden",
			"extra"     => array(
				
			)
		),
		"os_produto[0][os_item][*item*][referencia]" => array(
			"id"        => "produto[0][os_item][*item*][referencia]",
			"span"      => 2,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"extra"     => array(
				
			),
			"lupa" => array(
				"name"      => "lupa_config",
				"tipo"      => "lista_basica",
				"parametro" => "referencia",
				"extra"     => array(
					"posicao"         => "*item*",
					"posicao_produto" => "0",
					"produto"         => "",
					"list-replace" => ""
	            )
	        )
		),
		"os_produto[0][os_item][*item*][descricao]" => array(
			"id"        => "produto[0][os_item][*item*][descricao]",
			"span"      => 4,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"extra"     => array(
				
			),
			"lupa" => array(
				"name"      => "lupa_config",
				"tipo"      => "lista_basica",
				"parametro" => "descricao",
				"extra"     => array(
					"posicao"         => "*item*",
					"posicao_produto" => "0",
					"produto"         => "",
					"list-replace" => ""
	            )
	        )
		),
		"os[os_produto][0][os_item][*item*][qtde]" => array(
			"id"        => "produto[*produto*][os_item][*item*][qtde]",
			"span"      => 1,
			"label"     => "Qtde",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 5,
			"extra"     => array(
				
			)
		),
		"os[os_produto][0][os_item][*item*][defeito]" => array(
			"id"        => "produto[*produto*][os_item][*item*][defeito]",
			"span"      => 2,
			"label"     => "Defeito",
			"type"      => "select",
			"width"     => 12,
			"extra"     => array(
				
			)
		),
		"os[os_produto][0][os_item][*item*][gera_pedido]" => array(
			"id"	=> "produto[*produto*][os_item][*item*][servico_realizado]",
			"span"	=> "1",
			"type"	=> "input/checkbox",
			"label" => "Troca",
			"extra"	=> array(
				
			)
		)
	),
	"subproduto_item"=>array(
		"os[os_produto][1][os_item][*item*][peca]" => array(
			"id"        => "produto[1][os_item][*item*][peca]",
			"type"      => "input/hidden",
			"extra"     => array(
				
			)
		),
		"os_produto[1][os_item][*item*][referencia]" => array(
			"id"        => "produto[1][os_item][*item*][referencia]",
			"span"      => 2,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"extra"     => array(
				
			),
			"lupa" => array(
				"name"      => "lupa_config",
				"tipo"      => "lista_basica",
				"parametro" => "referencia",
				"extra"     => array(
					"posicao"         => "*item*",
					"posicao_produto" => "1",
					"produto"         => "",
					"list-replace" => ""
	            )
	        )
		),
		"os_produto[1][os_item][*item*][descricao]" => array(
			"id"        => "produto[1][os_item][*item*][descricao]",
			"span"      => 4,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"extra"     => array(
				
			),
			"lupa" => array(
				"name"      => "lupa_config",
				"tipo"      => "lista_basica",
				"parametro" => "descricao",
				"extra"     => array(
					"posicao"         => "*item*",
					"posicao_produto" => "1",
					"produto"         => "",
					"list-replace" => ""
	            )
	        )
		),
		"os[os_produto][1][os_item][*item*][qtde]" => array(
			"id"        => "produto[*produto*][os_item][*item*][qtde]",
			"span"      => 1,
			"label"     => "Qtde",
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 5,
			"extra"     => array(
				
			)
		),
		"os[os_produto][1][os_item][*item*][defeito]" => array(
			"id"        => "produto[*produto*][os_item][*item*][defeito]",
			"span"      => 2,
			"label"     => "Defeito",
			"type"      => "select",
			"width"     => 12,
			"extra"     => array(
				
			)
		),
		"os[os_produto][1][os_item][*item*][gera_pedido]" => array(
			"id"	=> "produto[*produto*][os_item][*item*][gera_pedido]",
			"span"	=> "1",
			"type"	=> "input/checkbox",
			"label" => "Troca",
			"checks" => array("t"=>""),
			"extra"	=> array(
				
			)
		)
	),
	"obs" => array(
		"obs" => array(
			"id"    => "obs",
			"span"  => 10,
			"type"  => "textarea",
			"width" => 12,
			"extra" => array(
				"style" => "width: 100%;"
			)
		),		
	),
	"anexo_nf" => array(
		"anexo_nf" 	=> array(
			"id" 	=> "anexo_nf",
			"span" 	=> 10,
			"type" 	=> "input/file",
			"width" => 12,
			"extra" => array(
				"style" => "width: 100%;"
			)
		)
	) 
);

