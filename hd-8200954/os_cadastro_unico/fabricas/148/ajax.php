<?php

if(isset($_POST["busca_preco_peca"])){

	$peca 	 = $_POST["peca"];

	$sql = "SELECT tbl_tabela_item.preco
			FROM tbl_tabela_item
			JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
			WHERE
				tbl_tabela_item.peca = {$peca}
				AND tbl_tabela.tabela_garantia IS TRUE
				AND tbl_tabela.fabrica = {$login_fabrica}
				AND tbl_tabela.ativa IS TRUE";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$preco = pg_fetch_result($res, 0, "preco");
		$preco = number_format($preco, 2);
	}else{
		$preco = "0.00";
	}

	exit(json_encode(array("preco" => $preco)));

}

if(isset($_POST["pecasreposicao"])){
	$produto = $_POST["produto_pecareposicao"];

	$sql = "SELECT
               tbl_produto.produto,
               tbl_produto.referencia  AS referencia_produto,
               tbl_produto.descricao   AS descricao_produto,
               tbl_produto.voltagem,
               tbl_produto.fabrica_origem AS tipo_produto,
               tbl_produto.valores_adicionais
			FROM tbl_produto WHERE produto = $produto";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) == 0)
		exit(json_encode(array("status" => false)));

	$data = array(
           "status"             => true,
           "produto"            => pg_fetch_result($res, 0, produto),
           "referencia_produto" => pg_fetch_result($res, 0, referencia_produto),
           "descricao_produto"  => utf8_encode(pg_fetch_result($res, 0, descricao_produto)),
           "voltagem"           => utf8_encode(pg_fetch_result($res, 0, voltagem)),
           "tipo_produto"       => pg_fetch_result($res, 0, tipo_produto),
           "valores_adicionais" => pg_fetch_result($res, 0, valores_adicionais),
	);

   exit(json_encode($data));
}

if(isset($_POST["verifica_serie_venda"])){

	$serie = trim($_POST["serie_venda"]);
	$posto_id = $_POST["posto_id"];

	$sql = "SELECT
				tbl_posto.posto,
				substr(tbl_posto.nome,1,50)	AS nome_posto,
				tbl_posto.cnpj 			AS cnpj_posto,
				tbl_posto_fabrica.contato_endereco 		AS endereco_posto,
				tbl_posto_fabrica.contato_numero 		AS numero_posto,
				tbl_posto_fabrica.contato_complemento 	AS complemento_posto,
				tbl_posto.fone 							AS telefone_posto,
				tbl_posto_fabrica.contato_cep 			AS cep_posto,
				tbl_posto_fabrica.contato_bairro 		AS bairro,
				tbl_posto_fabrica.contato_cidade 		AS cidade_posto,
				tbl_posto_fabrica.contato_estado 		AS estado_posto,
				tbl_produto.produto,
				tbl_produto.referencia 	AS referencia_produto,
				tbl_produto.descricao 	AS descricao_produto,
				tbl_produto.voltagem,
				tbl_produto.fabrica_origem AS tipo_produto,
				tbl_produto.valores_adicionais,
				tbl_venda.venda,
				tbl_venda.nota_fiscal,
				tbl_venda.serie,
				tbl_venda.serie_motor,
				tbl_venda.serie_transmissao,
				tbl_venda.data_nf 		AS data_compra,
				tbl_cliente.nome 		AS consumidor_nome,
				tbl_cliente.endereco 	AS consumidor_endereco,
				tbl_cliente.numero 		AS consumidor_numero,
				tbl_cliente.complemento AS consumidor_complemento,
				tbl_cliente.bairro 		AS consumidor_bairro,
				tbl_cliente.cep 		AS consumidor_cep,
				tbl_cliente.cidade 		AS consumidor_cidade,
				tbl_cidade.nome 		AS consumidor_cidade_nome,
				tbl_cidade.estado 		AS consumidor_estado,
				tbl_cliente.fone 		AS consumidor_fone,
				tbl_cliente.cpf 		AS consumidor_cpf,
				tbl_cliente.email 		AS consumidor_email
			FROM tbl_venda
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_venda.posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			INNER JOIN tbl_produto ON tbl_produto.produto = tbl_venda.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			INNER JOIN tbl_cliente ON tbl_cliente.cliente = tbl_venda.cliente
			INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
			INNER JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = tbl_produto.linha
			WHERE LOWER(tbl_venda.serie) = LOWER('$serie')
			AND tbl_venda.fabrica = {$login_fabrica}
			--AND tbl_venda.posto = $posto_id
			ORDER BY tbl_venda.data_nf desc, tbl_venda.data_input DESC";

	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		list($ano, $mes, $dia) = explode("-", pg_fetch_result($res, 0, "data_compra"));
		$data_compra = $dia."/".$mes."/".$ano;

		$valores_adicionais = json_decode(pg_fetch_result($res, 0, "valores_adicionais"), true);
		$revisao = $valores_adicionais["revisao"];

		$consumidor_fone = pg_fetch_result($res, 0, "consumidor_fone");

		$consumidor_fone = preg_replace("/[^0-9]/", "", $consumidor_fone);

		// Possibilidade de 1 digito a mais no celular
		if(strlen(trim($consumidor_fone)) >= 12){
			$consumidor_fone = substr($consumidor_fone,1,12);
		}else{
			$consumidor_fone = $consumidor_fone;
		}

		$data = array(
			"status"                 => true,
			"venda"                  => pg_fetch_result($res, 0, "venda"),
			"posto"                  => pg_fetch_result($res, 0, "posto"),
			"nome_posto"             => utf8_encode(pg_fetch_result($res, 0, "nome_posto")),
			"cnpj_posto"             => utf8_encode(pg_fetch_result($res, 0, "cnpj_posto")),
			"endereco_posto"         => utf8_encode(pg_fetch_result($res, 0, "endereco_posto")),
			"numero_posto"           => utf8_encode(pg_fetch_result($res, 0, "numero_posto")),
			"complemento_posto"      => utf8_encode(pg_fetch_result($res, 0, "complemento_posto")),
			"telefone_posto"         => utf8_encode(pg_fetch_result($res, 0, "telefone_posto")),
			"cep_posto"              => utf8_encode(pg_fetch_result($res, 0, "cep_posto")),
			"bairro"                 => utf8_encode(pg_fetch_result($res, 0, "bairro")),
			"cidade_posto"           => utf8_encode(pg_fetch_result($res, 0, "cidade_posto")),
			"estado_posto"           => utf8_encode(pg_fetch_result($res, 0, "estado_posto")),
			"produto"                => pg_fetch_result($res, 0, "produto"),
			"referencia_produto"     => utf8_encode(pg_fetch_result($res, 0, "referencia_produto")),
			"descricao_produto"      => utf8_encode(pg_fetch_result($res, 0, "descricao_produto")),
			"voltagem"               => utf8_encode(pg_fetch_result($res, 0, "voltagem")),
			"serie"                  => utf8_encode(pg_fetch_result($res, 0, "serie")),
			"serie_motor"            => utf8_encode(pg_fetch_result($res, 0, "serie_motor")),
			"serie_transmissao"      => utf8_encode(pg_fetch_result($res, 0, "serie_transmissao")),
			"tipo_produto"           => utf8_encode(pg_fetch_result($res, 0, "tipo_produto")),
			"data_compra"            => utf8_encode($data_compra),
			"nota_fiscal"            => utf8_encode(pg_fetch_result($res, 0, "nota_fiscal")),
			"consumidor_nome"        => utf8_encode(pg_fetch_result($res, 0, "consumidor_nome")),
			"consumidor_endereco"    => utf8_encode(pg_fetch_result($res, 0, "consumidor_endereco")),
			"consumidor_numero"      => utf8_encode(pg_fetch_result($res, 0, "consumidor_numero")),
			"consumidor_complemento" => utf8_encode(pg_fetch_result($res, 0, "consumidor_complemento")),
			"consumidor_bairro"      => utf8_encode(pg_fetch_result($res, 0, "consumidor_bairro")),
			"consumidor_cep"         => utf8_encode(pg_fetch_result($res, 0, "consumidor_cep")),
			"consumidor_cidade"      => utf8_encode(pg_fetch_result($res, 0, "consumidor_cidade")),
			"consumidor_cidade_nome" => utf8_encode(pg_fetch_result($res, 0, "consumidor_cidade_nome")),
			"consumidor_estado"      => utf8_encode(pg_fetch_result($res, 0, "consumidor_estado")),
			"consumidor_fone"        => utf8_encode($consumidor_fone),
			"consumidor_cpf"         => utf8_encode(pg_fetch_result($res, 0, "consumidor_cpf")),
			"consumidor_email"       => utf8_encode(pg_fetch_result($res, 0, "consumidor_email")),
			"revisao"                => $revisao
		);
	}else{
		$data = array("status" => false);
	}

	echo json_encode($data);
	exit;

}

/* Verifica Tipo Atendimento Revisão */
if(isset($_POST["revisao"])){

	$venda 				= $_POST["venda"];
	$produto 			= $_POST["produto"];
	$horimetro 			= $_POST["horimetro"];
	$intervalo_revisao 	= $_POST["intervalo_revisao"];

	// Verifica se é primeira revisão
	if(verifica_primeira_revisao($venda, $produto) == true){

		if(verifica_tolerancia($horimetro, $intervalo_revisao, $produto) == true){

			$pecas_revisao = pecas_revisao($intervalo_revisao, $produto, "primeira");

			$result = array("sucesso" => true, "pecas" => $pecas_revisao, "status" => "primeira_revisao");

		}else{
			$result = array("erro" => true, "mensagem" => str_replace("\\", "\\\\", utf8_encode("Horimetro fora do limite de tolerância")), "campo" => "produto_horimetro");
		}

	}else{

		if($intervalo_revisao == 50){
			$result = array("erro" => true, "mensagem" => str_replace("\\", "\\\\", utf8_encode("O produto já realizou a revisão de 50 horas")), "campo" => "produto_horimetro");
		}else{

			if(verifica_tolerancia($horimetro, $intervalo_revisao, $produto) == true){

				$revisao_atual = verifica_revisao_atual($venda, $produto);

				if($revisao_atual == $intervalo_revisao){
					$result = array("erro" => true, "mensagem" => str_replace("\\", "\\\\", utf8_encode("A revisão de $intervalo_revisao horas já foi realizada para este produto")), "campo" => "produto_horimetro");
				}else if(verifica_revisoes($venda, $produto, $intervalo_revisao) == false){
					$result = array("erro" => true, "mensagem" => str_replace("\\", "\\\\", utf8_encode("O produto está fora de garantia")), "campo" => "produto_horimetro");
				}else{

					$pecas_revisao = pecas_revisao($intervalo_revisao, $produto, "intervalo");

					$revisoes_produto = verifica_revisoes($venda, $produto, $intervalo_revisao);

					$result = array("sucesso" => true, "pecas" => $pecas_revisao, "status" => "revisao_$intervalo_revisao", "revisoes_produto" => $revisoes_produto);

				}

			}else{
				$result = array("erro" => true, "mensagem" => str_replace("\\", "\\\\", utf8_encode("Horimetro fora do limite de tolerância")), "campo" => "produto_horimetro");
			}

		}

	}

	/* $result = array(
		"venda" 			=> $venda,
		"produto" 			=> $produto,
		"horimetro"			=> $horimetro,
		"intervalo_revisao" => $intervalo_revisao,
	); */

	echo json_encode($result);
	exit;

}

function pecas_revisao($intervalo_revisao, $produto, $status){

	global $login_fabrica, $con;

	$sql = "SELECT valores_adicionais FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$valores_adicionais = pg_fetch_result($res, 0, "valores_adicionais");

		if(empty($valores_adicionais)){

			return false;

		}else{

			$valores_adicionais = json_decode($valores_adicionais, true);

			$primeira_revisao = $valores_adicionais[0]["revisoes"][$status];

			if(count($primeira_revisao) > 0){

				$pecas_revisao = array();

		    	foreach ($primeira_revisao as $categoria) {

					foreach ($categoria as $pecas) {

						for($i = 0; $i < count($pecas["id"]); $i++){

							$peca_id 				= $pecas["id"][$i];
							$servico_realizado_id 	= $pecas["servico_realizado"][$i];
							$peca_referencia 		= $pecas["peca_referencia"][$i];
							$peca_descricao 		= $pecas["peca_descricao"][$i];
							$servico_realizado 		= $pecas["servico_descricao"][$i];

							$pecas_revisao[] = array(
								"peca" 				=> $peca_id,
								"servico_realizado" => $servico_realizado_id,
								"peca_referencia" 	=> $peca_referencia,
								"peca_descricao" 	=> $peca_descricao
							);

						}
					}
				}

				return $pecas_revisao;

		    }else{
		    	return false;
		    }

		}

	}else{

		return false;

	}

}

function verifica_revisao_atual($venda, $produto){

	global $login_fabrica, $con;

	$sql = "SELECT
				tbl_os.hora_tecnica
			FROM tbl_os
			JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
			WHERE
				tbl_os.fabrica = {$login_fabrica}
				AND tbl_os_campo_extra.venda = {$venda}
				AND tbl_os.produto = {$produto}
				AND tbl_os.excluida IS NOT TRUE
			ORDER BY tbl_os.os DESC
			LIMIT 1";
	$res = pg_query($con, $sql);

	return pg_fetch_result($res, 0, "hora_tecnica");

}

?>
