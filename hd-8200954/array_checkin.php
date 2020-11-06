<?php
$json = array("view"=>array(
						"sessoes"=>array(
										"dados_os"=>array(
															"os"=>12345,
															"tipo_atendimento" => "Garantia",
															"data_abertura" => "2018-10-01",
															"data_compra" => "2018-08-01",
															"nota_fiscal" => "8976889",
															"defeito_reclamado" => "Não Liga"
														),
										"dados_produto" => array(
																"produto" => "ABC - Nome do Produto",
																"serie"	=> "32532535353",
															),
										"dados_cliente" => array(
																"nome" => "João",
																"cpfCnpj" => "68686986768",
																"cep" => "99797979",
																"estado" => "SP",
																"cidade" => "Marília",
																"bairro" => "Fragata",
																"endereco" => "Rua A",
																"numero" => "100",
																"telefone" => "14897787677",
																"email" => "teste@teste.com"
															),
										"dados_revenda" => array(
																"nome" => "Yanmar",
																"cnpj" => "9787878709",
																"telefone" => "11997979799"
															)
							)
										
					),
				"checkin" => array(
									"os" => array(	
													array(
														"type" => "text",
 														"required" => true,
 														"label" => "Defeito Reclamado",
 														"name" => "defeito_reclamado"
 													)
												),
									"produto" => array(
														array(
															"type" => "text",
	 														"required" => false,
	 														"label" => "Horímetro",
	 														"name" => "horimetro"
														),
														array(
															"type" => "select",
															"required" => false,
															"label" => "Defeito Constatado",
															"name" => "defeito_constatado",
															"options" => array(
																				"BOMBA INJETORA",
																				"ESTRUTURA DA MÁQUINA",
																				"MOTOR",
																				"ELÉTRICO"
																			)
														),
														array(
															"type" => "select",
															"required" => false,
															"label" => "Solução",
															"name" => "solucao",
															"options" => array(
																				"TROCA DA JUNTA",
																				"TROCA DAS RODAS",
																				"TROCA DAS ESTEIRAS",
																				"TROCA DO CILINDRO DA LANÇA"
																			)
														)
													),
									"lista_basica" => array(
															"pecas" => array(
																			array(
																				"type" => "multiselect2",
						 														"required" => false,
						 														"label" => "Peças",
						 														"name" => "peca_referencia",
						 														"options" => array(
						 																		array(
						 																			"referencia" => "ABC",
						 																			"descricao" => "Peça teste",
						 																			"qtde_maxima" => "1"
						 																		),
						 																		array(
						 																			"referencia" => "ABC",
						 																			"descricao" => "Peça teste",
						 																			"qtde_maxima" => "1"
						 																		),
						 																		array(
						 																			"referencia" => "ABC",
						 																			"descricao" => "Peça teste",
						 																			"qtde_maxima" => "1"
						 																		)
						 																	)
																			),
																			array(
																					"type" => "select",
																					"required" => true,
																					"label" => "Serviço",
																					"name" => "servico_realizado",
																					"options" => array(
																						"AJUSTE",
																						"TROCA DE PEÇA",
																						"TROCA DE PEÇA USANDO ESTOQUE"
																									)
																				)
																		)
														
									),
									"observacao" => array(
															array(
																"type" => "textarea",
		 														"required" => false,
		 														"label" => "Observação",
		 														"name" => "observacao"
															)
														),
									"anexos" => array(
														array(
															"type" => "file",
	 														"required" => true,
	 														"label" => "Anexos",
	 														"name" => "anexo"
														)
													)

								)
			);

echo json_encode($json);
