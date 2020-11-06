<?php 

$sessoes['assinatura'] = true;
$sessoes['data_evento'] = false;

$funcoes_adicionar_campos = array('adicionaAssinatura');

function adicionaAssinatura(){
	$campo_assinatura = [
		"views" => "",
			"type_form" => "assinatura",
			"inputs" => [
							[
								"type" => "assinatura",
								"required" => true,
								"label" => "Assinatura",
								"name" => "assinatura"                
							]								
						]
		];
	$dados['dados'] = $campo_assinatura;
	$dados['sessao'] = 'assinatura';

	return $dados; 
}


$extras["dados_os"] = [
		"fabricante"                   	=> "$nome_fabricante",
		"os"                          	=> $dados_os["os"],
		"hora_inicio" 					=> $dados_os['hora_inicio_trabalho'],
		"hora_terminio" 				=> $dados_os['hora_fim_trabalho'],
		"tipo_atendimento"  			=> $dados_os["tipo_atendimento"],
		"status_os"         			=> $dados_os["status_os"],
		"data_agendamento"              => $dados_os["data_agendamento"],
		"data_compra"                 	=> $dados_os["data_compra"],
		"nota_fiscal"                 	=> $dados_os["nota_fiscal"],

		"latitude"                 		=> $dados_os["latitude"],
		"longitude"                 	=> $dados_os["longitude"],
		"distancia_limite"              => $dados_os["distancia_limite"],

		"defeito_reclamado"           	=> utf8_encode($dados_os["defeito_reclamado"]),
		"endereco" => [
						"cep"        => $dados_os["consumidor_cep"],
						"estado"     => $dados_os["consumidor_estado"],
						"cidade"     => utf8_encode($dados_os["consumidor_cidade"]),
						"bairro"     => $dados_os["consumidor_bairro"],
						"logradouro" => $dados_os["consumidor_endereco"],
						"numero"     => $dados_os["consumidor_numero"],
		]
	];

	$extras["dados_produto"] = [
								"produto"  => $dados_os["produto"] ,
								"produto_descricao"  => $dados_os["produto_descricao"],
								"produto_referencia"  => $dados_os["produto_referencia"],
								"serie"    => $dados_os["serie"],
								"serie_motor"    => $obs_adicionais["serie_motor"],
								"serie_transmissao"    => $obs_adicionais["serie_transmissao"],
	];
	$extras["dados_cliente"] = [
								"nome"        => $dados_os["consumidor_nome"],
								"cpfCnpj"     => $dados_os["consumidor_cpf"],
								"endereco" => [
												"cep"        => $dados_os["consumidor_cep"],
												"estado"     => $dados_os["consumidor_estado"],
												"cidade"     => $dados_os["consumidor_cidade"],
												"bairro"     => $dados_os["consumidor_bairro"],
												"logradouro" => $dados_os["consumidor_endereco"],
												"numero"     => $dados_os["consumidor_numero"],
								],
								"telefone"  => $dados_os["consumidor_fone"],
								"celular"   => $dados_os["consumidor_celular"],
								"email"     => $dados_os["consumidor_email"],
	];
	$extras["dados_revenda"] = [
		"nome"      => $dados_os["revenda_nome"],
		"cnpj"      => $dados_os["revenda_cnpj"],
		"telefone"  => $dados_os["revenda_fone"],
	];


	$campo_os = [
			"views" => "",
				"type_form" => "default",
				"inputs" => [
								[
									"type" => "text",
									"required" => true,
									"label" => "Defeito Reclamado",
									"name" => "defeito_reclamado",
					"readonly" => true,
					"value" => $dados_os["defeito_reclamado_descricao"],
								]
							]
	
			];

		$campo_lista_basica = [
			"views" => "",
						"type_form" => "multi_escolha",
						"inputs" => [
										[
											"type" => "multiselect2",
											"required" => false,
											"label" => utf8_encode("Peças"),
											"name" => "peca_referencia",
											"options" => $array_pecas,
										],
										[
										"type" => "number",
			"required" => true,
			"label" => "Quantidade",
			"name" => "quantidade",
									],
						[
										"type" => "select",
										"required" => true,
										"label" => utf8_encode("Serviço"),
										"name" => "servico_realizado",
										"options" => $array_servico_realizado,
									],
								]
		
		
				];

		$campo_observacao = [
			"views" => "",
					"type_form" => "default",
					"inputs" => [
									[
										"type" => "textarea",
										"required" => false,
										"label" => utf8_encode("Observação"),
										"name" => "observacao",
									],
								]
				];

		$campo_anexos = [
				"views" => "",
					"inputs" => [
									[
										"type" => "file",
										"required" => true,
										"label" => "Anexos",
										"name" => "anexo",
									],
								]
				];

		$campo_produto = [
			"views" => "",
				"type_form" => "multi_escolha",
				"inputs" => [
								[
									"type" => "select",
									"required" => false,
									"label" => "Defeito Constatado",
									"name" => "defeito_constatado",
									"options" => $array_defeito_constatado,
								]
							]
	
		];

		

		$dados["request"]["view"]["sessoes"] 			= $extras;

		if($sessoes['defeitos']){
			$dados["request"]["checkin"]["defeitos"] 		= $campo_os;
		}
		if($sessoes['produto']){
			$dados["request"]["checkin"]["produto"]       	= $campo_produto;
		}
		if($sessoes['lista_basica']){
			$dados["request"]["checkin"]["lista_basica"]  	= $campo_lista_basica;
		}
		if($sessoes['observacao']){
			$dados["request"]["checkin"]["observacao"]    	= $campo_observacao;
		}
		if($sessoes['anexos']){
			$dados["request"]["checkin"]["anexos"]        	= $campo_anexos;
		}



?>