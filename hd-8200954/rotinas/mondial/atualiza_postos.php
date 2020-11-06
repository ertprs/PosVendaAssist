<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../os_cadastro_unico/fabricas/151/classes/Participante.php';

$login_fabrica = 151;
$participante = new Participante();

$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.nome_fantasia, tbl_posto_fabrica.contato_cep, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_numero, tbl_posto_fabrica.contato_complemento, tbl_posto_fabrica.contato_bairro, tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_nome, tbl_posto_fabrica.contato_email, tbl_posto_fabrica.contato_fone_comercial, tbl_posto.ie, tbl_posto_fabrica.contato_endereco
	FROM tbl_posto_fabrica
	INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
	WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
	AND tbl_posto.posto NOT IN (6359)";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
	while ($posto = pg_fetch_object($res)) {
		unset($fone, $ddd, $telefone);

		$fone = str_replace(array("(", ")", " ", "-", "."), "", $posto->contato_fone_comercial);
                $ddd = substr($fone, 0, 2);
	        $telefone = substr($fone, 2, strlen($fone) - 1);


		$dados_posto["SdEntParticipante"] = array(
			"RelacionamentoCodigo"           => "AssistTecnica",
			"ParticipanteTipoPessoa"         => "J",
			"ParticipanteFilialCPFCNPJ"      => preg_replace("/\W/", "", $posto->cnpj),
			"ParticipanteRazaoSocial"        => utf8_encode($posto->nome),
			"ParticipanteFilialNomeFantasia" => utf8_encode($posto->nome_fantasia),
			"ParticipanteStatus"             => "A",
			"Enderecos"                      => array(
				array(
					"ParticipanteFilialEnderecoSequencia"   => 1,
					"ParticipanteFilialEnderecoTipo"        => "Cobranca",
					"ParticipanteFilialEnderecoCep"         => preg_replace("/\D/", "", $posto->contato_cep),
					"ParticipanteFilialEnderecoLogradouro"  => utf8_encode($posto->contato_endereco),
					"ParticipanteFilialEnderecoNumero"      => $posto->contato_numero,
					"ParticipanteFilialEnderecoComplemento" => utf8_encode($posto->contato_complemento),
					"ParticipanteFilialEnderecoBairro"      => utf8_encode($posto->contato_bairro),
					"PaisCodigo"                            => 1058,
					"PaisNome"                              => "Brasil",
					"UnidadeFederativaCodigo"               => "",
					"UnidadeFederativaNome"                 => utf8_encode($posto->contato_estado),
					"MunicipioNome"                         => utf8_encode($posto->contato_cidade),
					"ParticipanteFilialEnderecoStatus"      => "A",
					"InscricaoEstadual" => $posto->ie
				)
			),
			"Contatos" => array(
				array(
					"ParticipanteFilialEnderecoContatoNome"        => utf8_encode($posto->contato_nome),
					"ParticipanteFilialEnderecoContatoEmail"       => utf8_encode($posto->contato_email),
					"ParticipanteFilialEnderecoContatoTelefoneDDI" => 55,
					"ParticipanteFilialEnderecoContatoTelefoneDDD" => $ddd,
					"ParticipanteFilialEnderecoContatoTelefone"    => $telefone
				)
			)
		);

		$status_posto = $participante->gravaParticipante($dados_posto);

	        if(!is_bool($status_posto) && $$status_posto != true){
        	    echo "posto {$posto->cnpj}: $status_posto\n\n";
	        }
	}
}

