<?php

if (isset($_POST["verifica_serie_venda"])) {

	$serie = strtoupper(trim($_POST["serie_venda"]));
	$posto_id = $_POST["posto_id"];

	$sql = "SELECT 
				tbl_revenda.nome AS nome_posto,
				tbl_revenda.cnpj AS cnpj_posto,
				tbl_revenda.endereco AS endereco_posto,
				tbl_revenda.numero AS numero_posto,
				tbl_revenda.complemento AS complemento_posto,
				tbl_revenda.fone AS telefone_posto,
				tbl_revenda.cep AS cep_posto,
				tbl_revenda.bairro AS bairro_posto,
				cr.nome AS cidade_posto,
				COALESCE(cr.estado, cr.estado_exterior) AS estado_posto,
				tbl_produto.produto,
				tbl_produto.referencia 	AS referencia_produto,
				tbl_produto.descricao 	AS descricao_produto,
				tbl_produto.voltagem,
				tbl_produto.fabrica_origem AS tipo_produto,
				tbl_produto.valores_adicionais,
				tbl_familia.descricao as nome_familia,
				tbl_linha.linha,
				tbl_linha.nome as nome_linha,
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
				tbl_cidade.nome 		AS consumidor_cidade,
				COALESCE(tbl_cidade.estado, tbl_cidade.estado_exterior) AS consumidor_estado,
				tbl_cliente.fone 		AS consumidor_fone,
				tbl_cliente.cpf 		AS consumidor_cpf,
				tbl_cliente.email 		AS consumidor_email
			FROM tbl_venda 
			JOIN tbl_produto ON tbl_produto.produto = tbl_venda.produto AND tbl_produto.fabrica_i = {$login_fabrica} 
			JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
			AND tbl_familia.fabrica = {$login_fabrica}
			JOIN tbl_linha   ON tbl_linha.linha = tbl_produto.linha
			AND tbl_linha.fabrica = {$login_fabrica}
			INNER JOIN tbl_cliente ON tbl_cliente.cliente = tbl_venda.cliente
			INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
            		INNER JOIN tbl_numero_serie ON tbl_venda.serie = tbl_numero_serie.serie AND tbl_numero_serie.fabrica = $login_fabrica
            		INNER JOIN tbl_revenda ON tbl_numero_serie.cnpj = tbl_revenda.cnpj
		        INNER JOIN tbl_cidade cr ON tbl_revenda.cidade = cr.cidade
			WHERE tbl_venda.serie = '$serie'
			AND tbl_venda.fabrica = {$login_fabrica}
			ORDER BY tbl_venda.data_nf, tbl_venda.venda DESC LIMIT 1";
	$res = pg_query($con, $sql);

    if (pg_num_rows($res) == 0) {
        $sql = "SELECT
                    '' AS nome_posto,
                    '' AS cnpj_posto,
                    '' AS endereco_posto,
                    '' AS numero_posto,
                    '' AS complemento_posto,
                    '' AS telefone_posto,
                    '' AS cep_posto,
                    '' AS bairro_posto,
                    '' AS cidade_posto,
                    '' AS estado_posto,
                    tbl_produto.produto,
                    tbl_produto.referencia AS referencia_produto,
                    tbl_produto.descricao AS descricao_produto,
                    tbl_produto.voltagem,
                    tbl_produto.fabrica_origem AS tipo_produto,
                    tbl_produto.valores_adicionais,
		    tbl_familia.descricao as nome_familia,
		    tbl_linha.linha,
					tbl_linha.nome as nome_linha,
                    '' AS venda,
                    '' AS nota_fiscal,
                    '' AS serie,
                    '' AS serie_motor,
                    '' AS serie_transmissao,
                    '' AS data_compra,
                    '' AS consumidor_nome,
                    '' AS consumidor_endereco,
                    '' AS consumidor_numero,
                    '' AS consumidor_complemento,
                    '' AS consumidor_bairro,
                    '' AS consumidor_cep,
                    '' AS consumidor_cidade,
                    '' AS consumidor_estado,
                    '' AS consumidor_fone,
                    '' AS consumidor_cpf,
                    '' AS consumidor_email
                FROM tbl_numero_serie
                JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto AND tbl_produto.fabrica_i = $login_fabrica
                JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
				AND tbl_familia.fabrica = {$login_fabrica}
				JOIN tbl_linha   ON tbl_linha.linha = tbl_produto.linha
				AND tbl_linha.fabrica = {$login_fabrica}
                WHERE tbl_numero_serie.serie = '$serie'
                AND tbl_numero_serie.fabrica = $login_fabrica";
        $res = pg_query($con, $sql);
    }

if(pg_num_rows($res) > 0){

	$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto_id AND linha = ". pg_fetch_result($res, 0, "linha");
	$resPl = pg_query($sql);
	if(pg_num_rows($resPl) == 0){
		$data = array("status" => false, "msg" => traduz("Posto não atende a linha do produto selecionado"));
		echo json_encode($data);
		exit;
	}

		list($ano, $mes, $dia) = explode("-", pg_fetch_result($res, 0, "data_compra"));
		$data_compra = $dia."/".$mes."/".$ano;

		$valores_adicionais = json_decode(pg_fetch_result($res, 0, "valores_adicionais"), true);
		$revisao = $valores_adicionais["revisao"];

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
			"bairro_posto"           => utf8_encode(pg_fetch_result($res, 0, "bairro_posto")),
			"cidade_posto"           => utf8_encode(pg_fetch_result($res, 0, "cidade_posto")),
			"estado_posto"           => utf8_encode(pg_fetch_result($res, 0, "estado_posto")),
			"produto"                => pg_fetch_result($res, 0, "produto"),
			"referencia_produto"     => utf8_encode(pg_fetch_result($res, 0, "referencia_produto")),
			"descricao_produto"      => utf8_encode(pg_fetch_result($res, 0, "descricao_produto")),
			"nome_familia"           => utf8_encode(pg_fetch_result($res, 0, "nome_familia")),
			"nome_linha"             => utf8_encode(pg_fetch_result($res, 0, "nome_linha")),
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
			"consumidor_estado"      => utf8_encode(pg_fetch_result($res, 0, "consumidor_estado")),
			"consumidor_fone"        => utf8_encode(pg_fetch_result($res, 0, "consumidor_fone")),
			"consumidor_cpf"         => utf8_encode(pg_fetch_result($res, 0, "consumidor_cpf")),
			"consumidor_email"       => utf8_encode(pg_fetch_result($res, 0, "consumidor_email")),
			"revisao"                => $revisao
		);
	}else{
		$data = array("status" => false, "msg" => traduz('nao.ha.nenhuma.venda.de.produto.com.o.numero.de.serie.informado') );
	}

	echo json_encode($data);
	exit;

}

