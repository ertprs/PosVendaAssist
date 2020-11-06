<?php 

$sessoes['assinatura'] = true;
$sessoes['data_evento'] = false;

$funcoes_adicionar_campos = array('adicionaAssinatura', 'adicionaAssinaturaTecnico');


$array_solucao = [];
$solucao = $this->getSolucao($dados_os['produto'], true);
if (count($solucao) > 0) {
	foreach ($solucao as $key => $linha) {
		$array_solucao[] = ["key" => $linha["solucao"], "value" => $linha["descricao"]];
	}
}



function adicionaAssinatura(){
	$campo_assinatura = [
		"views" => "",
			"type_form" => "assinatura",
			"inputs" => [
							[
								"type" => "assinatura",
								"required" => true,
								"label" => "Assinatura Consumidor",
								"name" => "assinatura_consumidor"                
							]								
						]
		];
	$dados['dados'] = $campo_assinatura;
	$dados['sessao'] = 'assinatura';

	return $dados; 
}

function adicionaAssinaturaTecnico(){
	$campo_assinatura = [
		"views" => "",
			"type_form" => "assinatura",
			"inputs" => [
							[
								"type" => "assinatura",
								"required" => true,
								"label" => utf8_encode("Assinatura T�cnico"),
								"name" => "assinatura_tecnico"                
							]								
						]
		];
	$dados['dados'] = $campo_assinatura;
	$dados['sessao'] = 'assinatura_tecnico';

	return $dados; 
}


function getRevisao($itens_checklist, $revisao){

    if(strlen(trim($revisao))> 3 ){
        $revisao = substr($revisao, -3);

        if($revisao < 190){
            $revisao = 1000;
        }
    }
    foreach($itens_checklist as $key => $value){      
        $valor = $key - $revisao; 
        if($valor < 0){
            $valor = $valor * -1; 
        }    
        if($key == 50){
            $menor = $valor; 
            $chave = $key;       
        }    
        if($menor > $valor){
            $chave = $key;
            $menor = $valor; 
        }    
        //echo "key = $key -- valor = $valor <br> ";    
    }
    return $chave; 
}



/*
	"endereco" => [
						"cep"        => $dados_os["consumidor_cep"],
						"estado"     => $dados_os["consumidor_estado"],
						"cidade"     => utf8_encode($dados_os["consumidor_cidade"]),
						"bairro"     => $dados_os["consumidor_bairro"],
						"logradouro" => $dados_os["consumidor_endereco"],
						"numero"     => $dados_os["consumidor_numero"],
		]
*/
/*
"hora_inicio" 					=> $dados_os['hora_inicio_trabalho'],
"hora_terminio" 				=> $dados_os['hora_fim_trabalho'],
*/


$extras["dados_os"] = [
		"fabricante"                   	=> "$nome_fabricante",
		"posto" 						=> $dados_os['codigo_posto']. " - ".  $dados_os['nome_posto'],
		"os"                          	=> $dados_os["os"],
		"tipo_atendimento"  			=> $dados_os["tipo_atendimento"],
		"status_os"         			=> $dados_os["status_os"],
		"agendamento_inicio" 					=> $dados_os['hora_inicio_trabalho'],
		"agendamento_termino" 				=> $dados_os['hora_fim_trabalho'],
		"data_compra"                 	=> $dados_os["data_compra"],
		"nota_fiscal"                 	=> $dados_os["nota_fiscal"],
		"latitude"                 		=> $dados_os["latitude"],
		"longitude"                 	=> $dados_os["longitude"],
		"distancia_limite"              => $dados_os["distancia_limite"],
		"KM" 							=> $dados_os['km'],
		"horimetro" 					=> $dados_os["horimetro"],
		"defeito_reclamado"           	=> utf8_encode($dados_os["defeito_reclamado"])		
	];

	if (in_array(utf8_decode($dados_os["tipo_atendimento"]), ["Revis�o"])) {
		$extras["dados_os"]["revisao"] = $dados_os['hora_tecnica'];
	}

	if (in_array(utf8_decode($dados_os["tipo_atendimento"]), ["Entrega T�cnica", "Revis�o"])) {
		$extras["dados_os"]["calcula_km"] = true;
	}

	if (strlen($dados_os["serie"]) > 0 and isset($dados_os["historico_produto"])) {
	 	$extras["dados_os"]["historico_produto"] = $dados_os["historico_produto"];
	}

	$obs_adicionais = json_decode($dados_os["obs_adicionais"],1);
	//"produto"  => $dados_os["produto"] ,
	$extras["dados_produto"] = [								
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


		if (is_array($dados_os['obs_campos_adicionais'])) {
			$extras["informacoes_tecnico"] = [
			"observacao" => $dados_os["obs_campos_adicionais"]["observacao"],
			"nome_contato" => $dados_os["obs_campos_adicionais"]["observacoes_nome_contato"]
			,
			"telefone_contato" => $dados_os["obs_campos_adicionais"]["observacoes_telefone_contato"]
			];
		}

		if(in_array(utf8_decode($dados_os["tipo_atendimento"]), ['Entrega T�cnica', 'Outros', 'Reparo'])){
			$required = false;
			$required_lista_basica = false;
		}else{
			$required = true;
			$required_lista_basica = true;
		}

		$campo_os = [
			"views" => "",
				"type_form" => "default",
				'valores_sistema' => utf8_encode($dados_os["defeito_reclamado_descricao"]),
				"inputs" => [
								[
									"type" => "text",
									"required" => $required,
									"label" => "Defeito Reclamado",
									"name" => "defeito_reclamado",
									"value" => '',
								]
							]
	
			];
		$campo_motor = [
			"views" => "",
				"type_form" => "default",
				"inputs" => [
								[
									"type" => "text",
									"required" => false,
									"label" => "Motor",
									"name" => "Motor",
					"readonly" => true,
					"value" => "",
								],
								[
									"type" => "text",
									"required" => false,
									"label" => "Modelo Motor",
									"name" => "modelo_motor",
					"readonly" => true,
					"value" => "",
								]
							]
	
			];

		$campo_data_evento = [
			"views" => "",
				"type_form" => "default",
				"inputs" => [
								[
									"type" => "datetime",
									"required" => true,
									"label" => "Datetime",
									"name" => "datetime"                
								],
								[
									"type" => "datepicker",
									"required" => true,
									"label" => "Datepicker",
									"name" => "datepicker"                
								]
							]
			];

		/*if(count(array_filter($array_defeito_constatado))==0 OR count(array_filter($array_solucao))==0){
			$sessoes['produto'] = false;
		}*/
		
		$campo_produto = [
			"views" => "",
			"type_form" => "multi_escolha",
			"valores_sistema" => $this->getConstatadoSolucaoOs($dados_os['os']),
			"inputs" => [
							[
								"type" => "select",
								"required" => false,
								"label" => "Defeito Constatado",
								"name" => "defeito_constatado",
								"dependencia" => "solucao",
								"options" => $array_defeito_constatado,
							],
							[
								"type" => "select",
								"required" => false,
								"label" => utf8_encode("Solu��o"),
								"name" => "solucao",
								"options" => $array_solucao,
							],
						]
	
		];
		
		$campo_horimetro = [
			"views" => "",
				"type_form" => "default",
				"inputs" => [
								[
									"type" => "number",
									"required" => $required,
									"label" => "Horimetro",
									"name" => "horimetro",
								]
							]
						];

		$campo_adicionais = [
			"views" => "",
				"type_form" => "default",
				"inputs" => [
								[
									"type" => "number",
									"required" => false,
									"label" => utf8_encode("Ped�gio"),
									"name" => "pedagio",
								],
								[
									"type" => "number",
									"required" => false,
									"label" => utf8_encode("Alimenta��o"),
									"name" => "alimentacao",
								]
							]		
				];


		if(count(array_filter($array_pecas))==0){
			$required_lista_basica = false;		
			/*
			Campo de Pe�a livre digitacao
			*/
			$cmp_peca = [
				"type" => "text",
				"required" => $required_lista_basica,
				"label" => utf8_encode("Pe�as"),
				"name" => "peca_referencia",
				"pipe" => true,
			];			

		}else{
			$cmp_peca = [
				"type" => "multiselect2",
				"required" => $required_lista_basica,
				"label" => utf8_encode("Pe�as"),
				"name" => "peca_referencia",
				"options" => $array_pecas,								
			];
		}

		$campo_lista_basica = [
			"views" => "",
			"type_form" => "multi_escolha",
			"system_values" => $this->buscaPecaLancada($dados_os["os"]),
			"inputs" => [
							$cmp_peca,
							[
								"type" => "number",
								"required" => $required_lista_basica,
								"label" => "Quantidade",
								"name" => "quantidade",
							],
							[
								"type" => "select",
								"required" => $required_lista_basica,
								"label" => utf8_encode("Servi�o"),
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
								"label" => utf8_encode("Observa��o"),
								"name" => "observacao",
							],
						]
				];

		$campo_anexos = [
				"views" => "",
				"type_form" => "anexos",
				"inputs" => [
								[
									"type" => "file",
									"required" => true,
									"label" => "Anexos",
									"name" => "anexo",
									"types" => $array_tipo_anexo
								],
							]
				];

		$itens_checklist[50] = array('Ajustar a tensao da correria do ventilador',
									'Checar n�vel de �leo do sistema hidr�ulico',
									'Drenar decantador do sistema de combustivel',
									'Drenar tranque de combustivel',
									'Examinar a �gua de arrefecimento',
									'Examinar as tubulacoes e conexoes de combustivel',
									'Lavar a colm�ia do radiador',
									'Limpar a superficie da bateria e passar parafina nos polos',
									'Limpar a tela frontal do radiador',
									'Limpar as telas de entrada de ar do radiador',
									'Limpar o filtro de ar',
									'Limpar o filtro de succao do tanque de �leo hidr�ulico',
									'Lubrificar com graxa todos os pinos de engraxe',
									'Lubrificar se os controles do sistema hidr�ulico funcionam corretamente',
									'Trocar filtro de �leo lubrificante do motor',
									'Trocar o filtro de combustivel',
									'Trocar �leo da caixa de redu��o',
									'Trocar �leo lubrificante do motor',
									'Verificar a carga da bateria',
									'Verificar funcionamento da chave de partida',
									'Verificar se h� vazamentos',
									'Verificar sistema de direcao da m�quina',
									'Verificar sistema de escavacao (concha)',
									'Verificar todo sistema el�trico da m�quina'); 

		$itens_checklist[250] = array("Ajustar a tensao da correia do radiador",
									"Checar n�vel de �leo do sistema hidr�ulico",
									"Drenar decantador do sistema de combustivel",
									"Drenar tanque de combustivel",
									"Examinar a �gua de arrefecimento",
									"Examinar as tubulacoes e conexoes de combustivel",
									"Lavar a colm�ia do radiador",
									"Limpar a superficie da bateria e passar parafina nos polos",
									"Limpar a tela frontal do radiador",
									"Limpar as telas de entrada de ar do radiador",
									"Limpar o filtro de ar",
									"Limpar o filtro de suc��o do tanque de �leo hidr�ulico",
									"Lubrificar com graxa todos os pinos de engraxe",
									"Substituir o filtro de retorno do �leo hidr�ulico",
									"Trocar filtro de �leo lubrificante do motor",
									"Trocar o filtro de combustivel",
									"Trocar o �leo da. caixa de reducao",
									"Trocar �leo lubrificante do motor",
									"Verificar a carga da bateria",
									"Verificar funcionamento da chave de partida",
									"Verificar se h� vazamentos",
									"Verificar se os controles do sistema hidr�ulico funcionam corretamente",
									"Verificar sistema de direcao da m�quina",
									"Verificar sistema de escavacao (concha)",
									"Verificar todo sistema el�trico da m�quina");

		$itens_checklist[500] = array("Ajustar a tensao da correia do ventilador",
										"Checar n�vel de �leo do sistema hidr�ulico",
										"Drenar decantador do sistema de combustivel",
										"Examinar a �gua de arrefecimento",
										"Examinar as tubulacoes e conexoes de combustivel",
										"Lavar a colm�ia do radiador",
										"Limpar a superficie da bateria e passar a parafina nos polos",
										"Limpar a tela frontal do radiador",
										"Limpar as telas de entrada de ar radiador",
										"Limpar o filtro de succao do tanque de �leo hidr�ulico",
										"Lubrificar com graxa todos os pinos de engraxe",
										"Trocar filtro de �leo lubrificante do motor",
										"Trocar o elemento do filtro de ar",
										"Trocar o filtro de combustivel",
										"Trocar �leo lubrificante do motor",
										"Verificar a carga da bateria",
										"Verificar funcionamento da chave de partida",
										"Verificar o n�vel do eletr�lito da bateria",
										"Verificar se h� vazamentos",
										"Verificar se os controles do sistema hidr�ulico funcionam corretamente",
										"Verificar sistema de direcao da m�quina",
										"Verificar sistema de escavacao (concha)",
										"Verificar todo sistema el�trico da m�quina");

		$itens_checklist[750] = array("Ajustar a tensao da correia do ventilador",
										"Drenar decantador do sistema de combust�vel",
										"Examinar a �gua de arrefecimento",
										"Examinar as tubulacoes e conexoes de combustivel",
										"Lavar a colm�ia do radiador",
										"Limpar a superficie da bateria e passar parafina nos polos",
										"Limpar a tela frontal do radiador",
										"Limpar as telas de entrada de ar do radiador",
										"Limpar o filtro de succao do tanque de �leo hidr�ulico",
										"Limpar ou trocar o elemento do filtro de ar",
										"Lubrificar com graxa todos os pinos de engraxe",
										"Substituir o filtro de retorno do �leo hidr�ulico",
										"Trocar filtro de �leo lubrificante do motor",
										"Trocar o filtro de combustivel",
										"Trocar o �leo da caixa de reducao",
										"Trocar �leo do sistema hidr�ulico",
										"Trocar �leo lubrificante do motor",
										"Verificar a carga da bateria",
										"Verificar funcionamento da chave de partida",
										"Verificar o n�vel do eletr�lito da bateria",
										"Verificar se h� vazamentos",
										"Verificar se os controles do sistema hidr�ulico funcionam corretamente",
										"Verificar sistema de direcao da m�quina",
										"Verificar sistema de escavacao (concha)",
										"Verificar todo sistema el�trico da m�quina");

		$itens_checklist[1000] = array("Ajustar a tensao da correia do ventilador",
										"Drenar decantador do sistema de combustivel",
										"Drenar e lavar o sistema de arrefecimento do motor e reabestecer ( usar aditivo)",
										"Examinar as tubulacoes e conexoes de combustivel",
										"Lavar a colm�ia do radiador",
										"Limpar a superficie da bateria e passar parafina nos polos",
										"Limpar a tela frontal do radiador",
										"Limpar as telas de entrada de ar do radiador",
										"Limpar o filtro de succao do tanque de �leo hidr�ulico",
										"Lubrificar com graxa todos os pinos de engraxe",
										"Re-aperto do cabecote",
										"Regular folga de v�lvulas de admissao e escape � frio",
										"Trocar filtro de �leo lubrificante do motor",
										"Trocar o elemento do filtro de ar",
										"Trocar o filtro de combustivel",
										"Trocar �leo lubrificante do motor",
										"Verificacao da Turbina",
										"Verificar a carga da bateria",
										"Verificar a condicao da pulverizacao dos bicos injetores",
										"Verificar a pressao dos bicos injetores",
										"Verificar funcionamento da chave de partida",
										"Verificar o n�vel do eletr�lito da bateria",
										"Verificar se h� vazamentos",
										"Verificar se os controles do sistema hidr�ulico funcionam corretamente",
										"Verificar sistema de direcao da m�quina",
										"Verificar sistema de escavacao ( concha )",
										"Verificar todo sistema el�trico da m�quina");

		$dados["request"]["view"]["sessoes"] 				= $extras;

		$revisao_fazer = getRevisao($itens_checklist, $dados_os['hora_tecnica']);

		if(count(array_filter($itens_checklist[$revisao_fazer]))>0 and in_array(utf8_decode($dados_os["tipo_atendimento"]), ['Revis�o']) ){
			foreach($itens_checklist[$revisao_fazer] as $itens){
					$itn[] = [
							'value'=> utf8_encode($itens), 
							'checked' => false
							];					
			}
			
			$campos_checklist = [
				"views" => "",
					"type_form" => "default",
					"inputs" => [
									[ 
										"type" => "checkbox",
										"required" => false,
										"name" => 'checklist_revisao',
										"label" => utf8_encode('CheckList Revis�o '.$revisao_fazer.' Horas'),
										"options" =>$itn,								
									],
									[
										"type" => "textarea",
										"required" => false,
										"label" => utf8_encode("Observa��o"),
										"name" => "observacao",
									]
								]							
				];

			$dados['request']['checkin']['checklist']  = $campos_checklist;
		}
		
		if(in_array(utf8_decode($dados_os["tipo_atendimento"]), ['Entrega T�cnica'])){
			$campos_checklist = [
				"views" => "",
					"type_form" => "default",
					"inputs" => [
									[ 
										"type" => "checkbox",
										"required" => false,
										"name" => 'verificacao_maquina_parada',
										"label" => utf8_encode('Verifica��o com a m�quina parada'),
										"options" => array(
												array('value'=> utf8_encode("Verifica��o de esteira ou da press�o dos pneus"), 'checked' => false),
												array('value'=> utf8_encode("N�vel da �gua de arrefecimento"), 'checked' => false),
												array('value'=> utf8_encode("Tens�o da correia da ventilador"), 'checked' => false),
												array('value'=> utf8_encode("Verificar a carga da bateria"), 'checked' => false),
												array('value'=> utf8_encode("Aperto dos parafusos das conex�es dos terminais da bateria"), 'checked' => false),
												array('value'=> utf8_encode("N�vel de �leo lubrificante do motor"), 'checked' => false),
												array('value'=> utf8_encode("N�vel de �leo da transmiss�o/hidr�ulico"), 'checked' => false),
												array('value'=> utf8_encode("Verifica��o dos pedais de acionamento da m�quina"), 'checked' => false),
												array('value'=> utf8_encode("Aperto dos parafusos e porcas das rodas traseiras."), 'checked' => false),
												array('value'=> utf8_encode("Aperto dos parafusos da rodas dianteiras"), 'checked' => false),
												array('value'=> utf8_encode("N�vel do �leo do tanque de combust�vel"), 'checked' => false),
												array('value'=> utf8_encode("Funcionamento dos far�is dianteiros, farol de trabalho e laternas"), 'checked' => false),
												array('value'=> utf8_encode("N�vel de �leo lubrificante dos eixos"), 'checked' => false),
												array('value'=> utf8_encode("Verifica��o do funcionamento das l�mpadas piloto(todas)"), 'checked' => false),
												array('value'=> utf8_encode("Lataria e pintura em geral"), 'checked' => false),

											),								
									],
									[
										"type" => "checkbox",
										"required" => false,
										"name" => 'verificacao_maquina_funcionando',
										"label" => utf8_encode('Verifica��es ao funcionar a m�quina e durante o funcionamento'),
										"options" => array(
												array('value'=> utf8_encode("Verifica��o do funcionamento de chave de seguran�a"), 'checked' => false),
												array('value'=> utf8_encode("Verifica��o durante a partida do motor"), 'checked' => false),
												array('value'=> utf8_encode("Rota��o m�xima do motor"), 'checked' => false),
												array('value'=> utf8_encode("Funcionamento de sistema hidr�ulico"), 'checked' => false),
											),								
									],
									[
										"type" => "checkbox",
										"required" => false,
										"name" => 'verificacao_maquina_movimento',
										"label" => utf8_encode('Verifica��es durante movimento'),
										"options" => array(
												array('value'=> utf8_encode("Opera�ao com a m�quina verificando os aceleradores"), 'checked' => false),
												array('value'=> utf8_encode("Opera��o da dire��o"), 'checked' => false),
												array('value'=> utf8_encode("Opera��o dos freios"), 'checked' => false),
												array('value'=> utf8_encode("Opera��o do sistema hidr�ulico"), 'checked' => false),
												array('value'=> utf8_encode("Opera��o de vazamentos"), 'checked' => false),
											),								
									],
									[
										"type" => "checkbox",
										"required" => false,
										"name" => 'entrega_tecnica',
										"label" => utf8_encode('ENTREGA T�CNICA'),
										"options" => array(
												array('value'=> utf8_encode("Explicar sobre a garantia da m�quina"), 'checked' => false),
												array('value'=> utf8_encode("Explicar sobre as revis�es"), 'checked' => false),
												array('value'=> utf8_encode("Explicar sobre o funcionamento e as opera��es da maquina"), 'checked' => false),
												array('value'=> utf8_encode("Explicar sobre as manuten��es peri�dicas"), 'checked' => false),
												array('value'=> utf8_encode("Explicar sobre a seguran�a nas opera��es"), 'checked' => false),
											),								
									]
								]							
				];

			$dados['request']['checkin']['checklist']  = $campos_checklist;
		}
		
		if($sessoes['defeitos']){
			$dados["request"]["checkin"]["defeitos"] 		= $campo_os;
		}
		if($sessoes['horimetro']){
			$dados["request"]["checkin"]["horimetro"]  		= $campo_horimetro;
		}
		if($sessoes['produto'] and !in_array(utf8_decode($dados_os["tipo_atendimento"]), ['Entrega T�cnica', 'Outros'])){
			$dados["request"]["checkin"]["produto"]       	= $campo_produto;
		}			
		if($sessoes['lista_basica']){
			$dados["request"]["checkin"]["lista_basica"]  	= $campo_lista_basica;
		}
		if($sessoes['observacao']){
			$dados["request"]["checkin"]["observacao"]    	= $campo_observacao;
		}
		if($sessoes['data_evento']){
			$dados["request"]["checkin"]["data_evento"]    	= $campo_data_evento;				
		}
		$dados["request"]["checkin"]["adicionais"]        	= $campo_adicionais;
		if($sessoes['anexos']){
			$dados["request"]["checkin"]["anexos"]        	= $campo_anexos;
		}
		

?>