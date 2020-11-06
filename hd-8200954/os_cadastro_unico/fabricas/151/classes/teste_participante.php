<?php

	include "Participante.php";

	$Participante = new Participante();

	/*
	Teste de Cadastro de Pessoa Juridica (Posto)
	*/

	$dados_posto = array();

	$dados_posto["SdEntParticipante"] = array(
		"RelacionamentoCodigo" 					=> "AssistTecnica", /* AssistTecnica - Assist�ncia T�cnica | ConsumidorFinal - Consumidor Final */
		"ParticipanteTipoPessoa" 				=> "J", /* F- F�sica | J - Jur�dica | E - Estrangeira */
		"ParticipanteFilialCPFCNPJ" 			=> 56854508000101,
		"ParticipanteRazaoSocial" 				=> utf8_encode("Assist�ncia de Teste - Raz�o Social"),
		"ParticipanteFilialNomeFantasia" 		=> utf8_encode("Assist�ncia TOP - Nome Fantasia"),
		// "ParticipanteFilialRegimeTributario" 	=> "", /* Microempresa | SimplesNacional | LucroPresumido | LucroReal */
		"ParticipanteStatus" 					=> "A", /* A - Ativo | I - Inativo */

		/** Endere�o **/
		"Enderecos"								=> array(
			array(
				"ParticipanteFilialEnderecoSequencia" 	=> 1234, /* Campo n�merico */
				"ParticipanteFilialEnderecoTipo"		=> "Cobranca", /* Cobranca | Entrega */
				"ParticipanteFilialEnderecoCep" 		=> "17521072",
				"ParticipanteFilialEnderecoLogradouro"  => utf8_encode("Rua Steve Ballmer, 1250"),
				"ParticipanteFilialEnderecoNumero" 		=> "514-B",
				"ParticipanteFilialEnderecoComplemento" => utf8_encode("Perto da Rodoviara Joeh Doe"),
				"ParticipanteFilialEnderecoBairro" 		=> utf8_encode("Cascata"),
				"PaisCodigo" 							=> 1058, /* 1058 - Brasil */
				"PaisNome" 								=> "Brasil",
				"UnidadeFederativaCodigo"				=> "SP",
				"UnidadeFederativaNome" 				=> utf8_encode("S�o Paulo"),
				// "MunicipioCodigo" 						=> "",
				"MunicipioNome" 						=> utf8_encode("Mar�lia"),
				// "InscricaoEstadual" 					=> "123456987",
				"ParticipanteFilialEnderecoStatus" 		=> "A", /* A - Ativo | I - Inativo */
			)
		),
		/** Contatos **/
		"Contatos"								=> array(
			array(
				"ParticipanteFilialEnderecoContatoNome" 		=> utf8_encode("Abilio Diniz Ghetso"),
				"ParticipanteFilialEnderecoContatoEmail" 		=> "abilio@assistenciatop.com",
				"ParticipanteFilialEnderecoContatoTelefoneDDI" 	=> 55, /* Default Brasil */
				"ParticipanteFilialEnderecoContatoTelefoneDDD" 	=> 014,
				"ParticipanteFilialEnderecoContatoTelefone" 	=> 996966969
			)
		)

	);

	$status_posto = $Participante->gravaParticipante($dados_posto);

	var_dump($status_posto);

	/* ------------------------------------------------------------------------------------------------------ */

	/*
	Teste de Cadastro de Pessoa F�sica (Consumidor)
	*/

	$dados_cosumidor = array();

	$dados_cosumidor["SdEntParticipante"] = array(
		"RelacionamentoCodigo" 					=> "ConsumidorFinal", /* AssistTecnica - Assist�ncia T�cnica | ConsumidorFinal - Consumidor Final */
		"ParticipanteTipoPessoa" 				=> "F", /* F- F�sica | J - Jur�dica | E - Estrangeira */
		"ParticipanteFilialCPFCNPJ" 			=> 70919203337,
		"ParticipanteRazaoSocial" 				=> utf8_encode("Olavo de Carvalho"),
		"ParticipanteFilialNomeFantasia" 		=> utf8_encode("Olavo de Carvalho"),
		// "ParticipanteFilialRegimeTributario" 	=> "", /* Microempresa | SimplesNacional | LucroPresumido | LucroReal */
		"ParticipanteStatus" 					=> "A", /* A - Ativo | I - Inativo */

		/** Endere�o **/
		"Enderecos"								=> array(
			array(
				"ParticipanteFilialEnderecoSequencia" 	=> 1234, /* Campo n�merico */
				"ParticipanteFilialEnderecoTipo"		=> "Cobranca", /* Cobranca | Entrega */
				"ParticipanteFilialEnderecoCep" 		=> "17521072",
				"ParticipanteFilialEnderecoLogradouro"  => utf8_encode("Rua Steve Ballmer, 1250"),
				"ParticipanteFilialEnderecoNumero" 		=> "514-B",
				"ParticipanteFilialEnderecoComplemento" => utf8_encode("Perto da Rodoviara Joeh Doe"),
				"ParticipanteFilialEnderecoBairro" 		=> utf8_encode("Cascata"),
				"PaisCodigo" 							=> 1058, /* 1058 - Brasil */
				"PaisNome" 								=> "Brasil",
				"UnidadeFederativaCodigo"				=> "SP",
				"UnidadeFederativaNome" 				=> utf8_encode("S�o Paulo"),
				// "MunicipioCodigo" 						=> "",
				"MunicipioNome" 						=> utf8_encode("Mar�lia"),
				"InscricaoEstadual" 					=> "",
				"ParticipanteFilialEnderecoStatus" 		=> "A", /* A - Ativo | I - Inativo */
			),
			array(
				"ParticipanteFilialEnderecoSequencia" 	=> 1234, /* Campo n�merico */
				"ParticipanteFilialEnderecoTipo"		=> "Entrega", /* Cobranca | Entrega */
				"ParticipanteFilialEnderecoCep" 		=> "17521072",
				"ParticipanteFilialEnderecoLogradouro"  => utf8_encode("Rua Steve Ballmer, 1250"),
				"ParticipanteFilialEnderecoNumero" 		=> "514-B",
				"ParticipanteFilialEnderecoComplemento" => utf8_encode("Perto da Rodoviara Joeh Doe"),
				"ParticipanteFilialEnderecoBairro" 		=> utf8_encode("Cascata"),
				"PaisCodigo" 							=> 1058, /* 1058 - Brasil */
				"PaisNome" 								=> "Brasil",
				"UnidadeFederativaCodigo"				=> "SP",
				"UnidadeFederativaNome" 				=> utf8_encode("S�o Paulo"),
				// "MunicipioCodigo" 						=> "",
				"MunicipioNome" 						=> utf8_encode("Mar�lia"),
				"InscricaoEstadual" 					=> "",
				"ParticipanteFilialEnderecoStatus" 		=> "A", /* A - Ativo | I - Inativo */
			)

		),
		/** Contatos **/
		"Contatos"								=> array(
			array(
				"ParticipanteFilialEnderecoContatoNome" 		=> utf8_encode("Olavo de Carvalho"),
				"ParticipanteFilialEnderecoContatoEmail" 		=> "olavo@carvalho.com",
				"ParticipanteFilialEnderecoContatoTelefoneDDI" 	=> 55, /* Default Brasil */
				"ParticipanteFilialEnderecoContatoTelefoneDDD" 	=> 014,
				"ParticipanteFilialEnderecoContatoTelefone" 	=> 996884542
			),
			array(
				"ParticipanteFilialEnderecoContatoNome" 		=> utf8_encode("Karen Bros"),
				"ParticipanteFilialEnderecoContatoEmail" 		=> "karen@bros.com",
				"ParticipanteFilialEnderecoContatoTelefoneDDI" 	=> 55, /* Default Brasil */
				"ParticipanteFilialEnderecoContatoTelefoneDDD" 	=> 015,
				"ParticipanteFilialEnderecoContatoTelefone" 	=> 988445511
			)
		)
	);

	$status_posto = $Participante->gravaParticipante($dados_cosumidor);

	var_dump($status_posto);
	
?>