<?php

$preos = $_REQUEST["preos"];
$os    = $_REQUEST["os_id"];
$hd_chamado_item = $_REQUEST["hd_chamado_item"];

include_once __DIR__ . '../../../class/AuditorLog.php';

#Arquivo com as regras padrões do sistema
include_once __DIR__."/regras.php";
#Arquivo com as regras especificas da fábrica
include_once __DIR__."/{$login_fabrica}/regras.php";

if($login_fabrica == 160) {
	include_once __DIR__."../../../class/sms/sms.class.php";
	$sms = new SMS();
}

#Array de erros
$msg_erro = array(
	'msg'    => array(),
	'campos' => array()
);

$camposExtra = "";
$condExtra   = "";
$condJOIN    = "";

if (strlen($preos) > 0 && $areaAdmin === false) {

	$cond_left = (in_array($login_fabrica, array(52,151,186,195))) ? "LEFT" : "";

	if (in_array($login_fabrica, array(151,186,195)) AND !empty($hd_chamado_item)) {

		
		if (in_array($login_fabrica, [186])) {
			$campoDef = "tbl_hd_chamado_extra.defeito_reclamado_descricao,tbl_hd_chamado_extra.obs_callcenter,";
		} else {
			$campoDef = "tbl_hd_chamado_item.defeito_reclamado_descricao as defeito_reclamado,";
		}
		if (in_array($login_fabrica, [195])) {
			$campoDef = "tbl_hd_chamado_extra.defeito_reclamado_descricao as defeito_reclamado,";
		} 
		$camposExtra = "
				tbl_hd_chamado_item.defeito_reclamado As defeito_reclamado_id,
				{$campoDef}
				tbl_hd_chamado_item.serie AS produto_serie,
				tbl_hd_chamado_item.nota_fiscal,
				TO_CHAR(tbl_hd_chamado_item.data_nf, 'DD/MM/YYYY') AS data_compra,
		";
		$condExtra = " JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = tbl_hd_chamado_item.produto";
		$condJOIN  = " JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado_item = {$hd_chamado_item} AND tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado";
	} else if ($login_fabrica == 165) {
        $camposExtra = "
            TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_abertura,
            TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS data_compra,
            TO_CHAR(tbl_numero_serie.data_fabricacao, 'DD/MM/YYYY') AS data_fabricacao,
            tbl_hd_chamado_extra.serie AS produto_serie,
            tbl_hd_chamado_extra.nota_fiscal,
            tbl_hd_chamado_extra.defeito_reclamado As defeito_reclamado_id,
        ";

        $condExtra = " JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = tbl_hd_chamado_extra.produto
                       LEFT JOIN tbl_numero_serie ON tbl_numero_serie.serie = tbl_hd_chamado_extra.serie
                                             AND tbl_numero_serie.produto = tbl_hd_chamado_extra.produto
        ";
		$condJOIN = "";
		$extraos = "sim";
	} else if (in_array($login_fabrica, array(169,170))) {
        $camposExtra = "
            TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS data_compra,
            tbl_hd_chamado_extra.serie AS produto_serie,
            tbl_hd_chamado_extra.nota_fiscal,
            tbl_hd_chamado_extra.defeito_reclamado_descricao,
			tbl_hd_chamado_extra.defeito_reclamado,
			tbl_hd_motivo_ligacao.categoria as cortesia,
        ";

        $condExtra = "{$cond_left} JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = tbl_hd_chamado_extra.produto LEFT JOIN tbl_hd_motivo_ligacao on tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao";
		$condJOIN = "";
		$extraos = "sim";

	}else {
		MONDIALEXTRA:
		$camposExtra = "
				tbl_hd_chamado_extra.nota_fiscal,
				TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS data_compra,
				tbl_hd_chamado_extra.defeito_reclamado As defeito_reclamado_id,
				tbl_hd_chamado_extra.defeito_reclamado_descricao AS defeito_reclamado,
				tbl_hd_chamado_extra.serie AS produto_serie,
		";
		$condExtra = "{$cond_left} JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = tbl_hd_chamado_extra.produto";
		$condJOIN = "";
		$extraos = "sim";
	}

	if (in_array($login_fabrica, [193])) {
		$camposExtra .= " tbl_produto.garantia_horas, 
						  tbl_hd_chamado_extra.defeito_reclamado_descricao, ";

	}

	$sql = "
		SELECT
			{$camposExtra}
			tbl_hd_chamado_extra.nome AS consumidor_nome,
			tbl_hd_chamado_extra.cpf  AS consumidor_cpf,
			tbl_hd_chamado_extra.cep  AS consumidor_cep,
			case when cidade_consumidor.estado_exterior notnull then cidade_consumidor.estado_exterior else cidade_consumidor.estado end AS consumidor_estado,
			UPPER(TO_ASCII(cidade_consumidor.nome, 'LATIN9')) AS consumidor_cidade,
			tbl_hd_chamado_extra.bairro      AS consumidor_bairro,
			tbl_hd_chamado_extra.endereco    AS consumidor_endereco,
			tbl_hd_chamado_extra.numero      AS consumidor_numero,
			tbl_hd_chamado_extra.complemento AS consumidor_complemento,
			tbl_hd_chamado_extra.fone AS consumidor_telefone,
			tbl_hd_chamado_extra.email AS consumidor_email,
			tbl_hd_chamado_extra.celular AS consumidor_celular,
			tbl_hd_chamado_extra.array_campos_adicionais,
			tbl_hd_chamado_extra.tipo_atendimento,
			tbl_hd_chamado_postagem.numero_postagem,
			tbl_hd_chamado_extra.consumidor_revenda as consumidor_revenda_callcenter,
			tbl_revenda.nome AS revenda_nome,
			tbl_revenda.cnpj AS revenda_cnpj,
			tbl_revenda.cep AS revenda_cep,
			case when cidade_revenda.estado_exterior notnull then cidade_revenda.estado_exterior else cidade_revenda.estado end AS revenda_estado,
			UPPER(TO_ASCII(cidade_revenda.nome, 'LATIN9')) AS revenda_cidade,
			tbl_revenda.bairro             AS revenda_bairro,
			tbl_revenda.endereco           AS revenda_endereco,
			tbl_revenda.numero             AS revenda_numero,
			tbl_revenda.complemento        AS revenda_complemento,
			tbl_revenda.fone               AS revenda_telefone,
			tbl_revenda.email              AS revenda_email,
			tbl_produto.produto,
			tbl_produto.referencia         AS produto_referencia,
			tbl_produto.capacidade,
			tbl_produto.descricao          AS produto_descricao,
			tbl_produto.parametros_adicionais AS parametros_adicionais_produto,
			tbl_produto.voltagem           AS produto_voltagem,
			tbl_produto.entrega_tecnica    AS produto_entrega_tecnica,
			tbl_produto.origem              AS produto_origem,
			tbl_produto.valores_adicionais AS produto_valores_adicionais,
			tbl_produto.produto_principal,
			tbl_hd_chamado.cliente_admin
	   	FROM tbl_hd_chamado
	   	JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
	   	LEFT JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado_postagem.hd_chamado = tbl_hd_chamado.hd_chamado
	   	{$condJOIN}
	   	LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_hd_chamado_extra.revenda AND tbl_revenda.cnpj = tbl_hd_chamado_extra.revenda_cnpj
	   	LEFT JOIN tbl_cidade AS cidade_consumidor ON cidade_consumidor.cidade = tbl_hd_chamado_extra.cidade
	   	LEFT JOIN tbl_cidade AS cidade_revenda ON cidade_revenda.cidade = tbl_revenda.cidade
		{$condExtra}
	  	WHERE tbl_hd_chamado.fabrica   = {$login_fabrica}
  		AND tbl_hd_chamado_extra.posto = {$login_posto}
		AND tbl_hd_chamado.hd_chamado  = {$preos}
		{$produto_principal};
	";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT = array(
			'os' => array(
				'hd_chamado'        => $preos,
				'nota_fiscal'       => pg_fetch_result($res, 0, 'nota_fiscal'),
				'data_compra'       => pg_fetch_result($res, 0, 'data_compra'),
				'tipo_atendimento'	=> pg_fetch_result($res, 0, 'tipo_atendimento'),
				'defeito_reclamado' => (in_array($login_fabrica, array(161,164,165,167,173,191,193,198,203))) ? pg_fetch_result($res, 0, "defeito_reclamado_id") : pg_fetch_result($res, 0, 'defeito_reclamado'),
				'cliente_admin'		=> pg_fetch_result($res, 0, 'cliente_admin'),
			),
			"consumidor" => array(
				"nome"        => pg_fetch_result($res, 0, "consumidor_nome"),
				"cpf"         => pg_fetch_result($res, 0, "consumidor_cpf"),
				"cep"         => pg_fetch_result($res, 0, "consumidor_cep"),
				"estado"      => pg_fetch_result($res, 0, "consumidor_estado"),
				"cidade"      => pg_fetch_result($res, 0, "consumidor_cidade"),
				"bairro"      => pg_fetch_result($res, 0, "consumidor_bairro"),
				"endereco"    => pg_fetch_result($res, 0, "consumidor_endereco"),
				"numero"      => pg_fetch_result($res, 0, "consumidor_numero"),
				"complemento" => pg_fetch_result($res, 0, "consumidor_complemento"),
				"telefone"    => pg_fetch_result($res, 0, "consumidor_telefone"),
				"email"       => pg_fetch_result($res, 0, "consumidor_email"),
				"celular"     => pg_fetch_result($res, 0, "consumidor_celular")
			),
			'revenda' => array(
				'nome'        => pg_fetch_result($res, 0, 'revenda_nome'),
				'cnpj'        => pg_fetch_result($res, 0, 'revenda_cnpj'),
				'cep'         => pg_fetch_result($res, 0, 'revenda_cep'),
				'estado'      => pg_fetch_result($res, 0, 'revenda_estado'),
				'cidade'      => pg_fetch_result($res, 0, 'revenda_cidade'),
				'bairro'      => pg_fetch_result($res, 0, 'revenda_bairro'),
				'endereco'    => pg_fetch_result($res, 0, 'revenda_endereco'),
				'numero'      => pg_fetch_result($res, 0, 'revenda_numero'),
				'complemento' => pg_fetch_result($res, 0, 'revenda_complemento'),
				'telefone'    => pg_fetch_result($res, 0, 'revenda_telefone')
			),
			'produto' => array(
				'id'         => pg_fetch_result($res, 0, 'produto'),
				'referencia' => pg_fetch_result($res, 0, 'produto_referencia'),
				'descricao'  => pg_fetch_result($res, 0, 'produto_descricao'),
				'voltagem'   => pg_fetch_result($res, 0, 'produto_voltagem'),
				'serie'      => pg_fetch_result($res, 0, 'produto_serie'),
				'capacidade' => pg_fetch_result($res, 0, 'capacidade')
			)
		);

		if ($usaProdutoGenerico) {
			$produto_principal = pg_fetch_result($res, 0, 'produto_principal');
			if ($produto_principal != 't') {
				unset($_RESULT['produto']);
			}
		}

		if (in_array($login_fabrica, [186])) {
			$revenda_email = pg_fetch_result($res, 0, "revenda_email");
			$obs_callcenter = pg_fetch_result($res, 0, "obs_callcenter");
			$_RESULT["os"]["observacoes"] = $obs_callcenter;
			if (!empty($revenda_email)) {
				$_RESULT["revenda"]["email"] = $revenda_email;
			}
		}

		if($login_fabrica == 160 or $replica_einhell){
			$array_campos_adicionais		= pg_fetch_result($res, 0, "array_campos_adicionais");
			$array_campos_adicionais    	= json_decode($array_campos_adicionais, true);
			$_RESULT["produto"]["versao"] 	= $array_campos_adicionais['versao_produto'];
		}

		if(in_array($login_fabrica, [167, 203])){ //HD-3428328

			$parametros_adicionais            = pg_fetch_result($res, 0, 'parametros_adicionais_produto');
			$parametros_adicionais            = json_decode($parametros_adicionais, true);
			$suprimento                       = $parametros_adicionais["suprimento"];
			$_RESULT['produto']['suprimento'] = $suprimento;

			$array_campos_adicionais          = pg_fetch_result($res, 0, 'array_campos_adicionais');
			$array_campos_adicionais          = json_decode($array_campos_adicionais, true);
			$contador                         = $array_campos_adicionais["contador"];
			$_RESULT["produto"]["contador"]   = $contador;

			if ($login_fabrica == 203) {
				$_RESULT["produto"]["recebido_via_correios"] = $array_campos_adicionais["produto_recebido_via_correios"];			
			}
		}

		if (in_array($login_fabrica, [193])) {
			$parametros_adicionais                = pg_fetch_result($res, 0, 'parametros_adicionais_produto');
			$parametros_adicionais                = json_decode($parametros_adicionais, true);
			$_RESULT['produto']['lancamento']     = (!empty($parametros_adicionais["lancamento"])) ? $parametros_adicionais["lancamento"] : "";
			$_RESULT['produto']['garantia_horas'] = (!empty(pg_fetch_result($res, 0, 'garantia_horas'))) ? pg_fetch_result($res, 0, 'garantia_horas') : "";
		}

		if ($login_fabrica == 176)
		{
			$indice                           = pg_fetch_result($res, 0, 'indice');
			$_RESULT["os"]["indice"]          = $indice;
		}

		if (in_array($login_fabrica, [177])) {
			$array_campos_adicionais          = pg_fetch_result($res, 0, 'array_campos_adicionais');
			$array_campos_adicionais          = json_decode($array_campos_adicionais, true);
			$produto_lote                     = $array_campos_adicionais["lote"];
			$_RESULT["produto"]["lote"]       = $produto_lote;
		}

		if (in_array($login_fabrica, [191])) {
			$array_campos_adicionais            = pg_fetch_result($res, 0, 'array_campos_adicionais');
			$array_campos_adicionais            = json_decode($array_campos_adicionais, true);
			$numero_nf_remessa                  = $array_campos_adicionais["numero_nf_remessa"];
			$_RESULT["os"]["numero_nf_remessa"] = $numero_nf_remessa;
		}

		if($login_fabrica == 162){
			$array_campos_adicionais		= pg_fetch_result($res, 0, "array_campos_adicionais");
			$array_campos_adicionais    	= json_decode($array_campos_adicionais, true);
			$_RESULT['produto']['linha_informatica'] = $array_campos_adicionais['linha_informatica'];
			$_RESULT["produto"]["imei"] = $array_campos_adicionais['imei'];
			$_RESULT["produto"]["aparencia"] = $array_campos_adicionais['interno_aparencia'];
			$_RESULT["produto"]["acessorios"] = $array_campos_adicionais['interno_acessorios'];

            $numero_postagem = pg_fetch_result($res,0,numero_postagem);
            if (!empty($numero_postagem)) {
                $sqlDataPost = "
                    SELECT  data
                    FROM    tbl_faturamento_correio
                    WHERE   fabrica = $login_fabrica
                    AND     numero_postagem = '$numero_postagem'
                    AND     (situacao ILIKE '%Postado%' or situacao ~* 'coletado')
                ";
                $resDataPost = pg_query($con,$sqlDataPost);
                $auxData = pg_fetch_result($resDataPost,0,data);
                if (!empty($auxData)) {
                    $_RESULT['os']['data_abertura'] = $auxData;
                }
            }
		}

		if ($login_fabrica == 165) {
            $_RESULT['os']['data_abertura']         = pg_fetch_result($res,0,data_abertura);
            $_RESULT['produto']['data_fabricacao']  = pg_fetch_result($res,0,data_fabricacao);
		}

		if ($login_fabrica == 195) {
            $_RESULT['produto']['data_fabricacao']  = pg_fetch_result($res,0,data_fabricacao);

            $array_campos_adicionais		= pg_fetch_result($res, 0, "campos_adicionais");
			$array_campos_adicionais    	= json_decode($array_campos_adicionais, true);
			$_RESULT['produto']['solicita_troca_antecipada'] = $array_campos_adicionais['solicita_troca_antecipada'];
			$_RESULT['produto']['solicita_troca_antecipada_orcamento'] = $array_campos_adicionais['solicita_troca_antecipada_orcamento'];

		}

        if (in_array($login_fabrica, array(164))) {
            $_RESULT['os']['data_entrada']        = pg_fetch_result($res,0,data_abertura);
            $_RESULT["produto"]["origem_produto"] = pg_fetch_result($res, 0, "produto_origem");
        }

		if (in_array($login_fabrica, array(52,151,186))) {

			$_RESULT["os"]["hd_chamado_item"] = $_REQUEST["hd_chamado_item"];

		}

		if(strlen(pg_fetch_result($res, 0, "consumidor_cpf")) > 0){

			$cpf_cnpj = str_replace(array(".", "-", "/"), "", pg_fetch_result($res, 0, "consumidor_cpf"));

			if(strlen($cpf_cnpj) == 11){
				$_RESULT["consumidor"]["cnpjCpf"] = "cpf";
			}else if(strlen($cpf_cnpj) == 14){
				$_RESULT["consumidor"]["cnpjCpf"] = "cnpj";
			}else{
				$_RESULT["consumidor"]["cnpjCpf"] = $_POST['consumidor']['cnpjCpf'];
			}

		}

		if (in_array($login_fabrica, array(142))) {
			$_RESULT["produto"]["entrega_tecnica"] = pg_fetch_result($res, 0, "produto_entrega_tecnica");

			if ($_RESULT["produto"]["entrega_tecnica"] == "t") {
				$produto_valores_adicionais = json_decode(pg_fetch_result($res, 0, "produto_valores_adicionais"));
				$_RESULT["produto"]["deslocamento_km"] = $produto_valores_adicionais->deslocamento_km;
			}
		}	

		if (in_array($login_fabrica, [198])) {
			$_RESULT["os"]['defeito_reclamado_descricao'] = pg_fetch_result($res, 0, "defeito_reclamado");
			$_RESULT["os"]['defeito_reclamado'] = pg_fetch_result($res, 0, "defeito_reclamado_id");
		}

		if (in_array($login_fabrica, array(169,170))) {
			$_RESULT["os"]['defeito_reclamado_descricao'] = pg_fetch_result($res, 0, "defeito_reclamado_descricao");
			$_RESULT["os"]['cortesia'] = pg_fetch_result($res, 0, "cortesia");
			unset($_RESULT['produto']);
		}

		if (in_array($login_fabrica, array(174,184,186,193,200)))
		{
			$_RESULT["os"]['defeito_reclamado_descricao'] = pg_fetch_result($res, 0, "defeito_reclamado_descricao");
			$_RESULT["os"]['defeito_reclamado'] = pg_fetch_result($res, 0, "defeito_reclamado_id");
		}

		if ($defeitoReclamadoCadastroDefeitoReclamadoCliente){
			$_RESULT["os"]['defeito_reclamado_descricao'] = pg_fetch_result($res, 0, "defeito_reclamado");
			$_RESULT["os"]['defeito_reclamado'] = pg_fetch_result($res, 0, "defeito_reclamado_id");
		}

		if (in_array($login_fabrica, [186])) {
			$_RESULT["os"]["consumidor_revenda"] = (in_array(pg_fetch_result($res, 0, 'consumidor_revenda_callcenter'), ["D","M"])) ? "R" : "C";
		}

		if (in_array($login_fabrica, array(173))) {
			$_RESULT["os"]['defeito_reclamado_descricao'] = pg_fetch_result($res, 0, "defeito_reclamado");
		} 		
	} else {
		if(in_array($login_fabrica, array(151,186)) and empty($extraos))  {
			goto MONDIALEXTRA;
		}
		$msg_erro["msg"][] = "Pré-OS não encontrada";
		$erro_carrega_os = true;
	}
}

if (strlen($preos) > 0 && $areaAdmin === true && in_array($login_fabrica, array(169,170))) {

	$sql = "
		SELECT
			TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS data_compra,
            tbl_hd_chamado_extra.serie AS produto_serie,
            tbl_hd_chamado_extra.nota_fiscal,
            tbl_hd_chamado_extra.defeito_reclamado_descricao,
            tbl_hd_chamado_extra.defeito_reclamado,
			tbl_hd_chamado_extra.nome AS consumidor_nome,
			tbl_hd_chamado_extra.cpf  AS consumidor_cpf,
			tbl_hd_chamado_extra.cep  AS consumidor_cep,
			case when cidade_consumidor.estado_exterior notnull then cidade_consumidor.estado_exterior else cidade_consumidor.estado end AS consumidor_estado,
			UPPER(TO_ASCII(cidade_consumidor.nome, 'LATIN9')) AS consumidor_cidade,
			tbl_hd_chamado_extra.bairro      AS consumidor_bairro,
			tbl_hd_chamado_extra.endereco    AS consumidor_endereco,
			tbl_hd_chamado_extra.numero      AS consumidor_numero,
			tbl_hd_chamado_extra.complemento AS consumidor_complemento,
			tbl_hd_chamado_extra.fone AS consumidor_telefone,
			tbl_hd_chamado_extra.email AS consumidor_email,
			tbl_hd_chamado_extra.celular AS consumidor_celular,
			tbl_hd_chamado_extra.array_campos_adicionais,
			tbl_hd_chamado_extra.tipo_atendimento,
			tbl_hd_chamado_postagem.numero_postagem,
			tbl_revenda.nome AS revenda_nome,
			tbl_revenda.cnpj AS revenda_cnpj,
			tbl_revenda.cep AS revenda_cep,
			case when cidade_revenda.estado_exterior notnull then cidade_revenda.estado_exterior else cidade_revenda.estado end AS revenda_estado,
			UPPER(TO_ASCII(cidade_revenda.nome, 'LATIN9')) AS revenda_cidade,
			tbl_revenda.bairro             AS revenda_bairro,
			tbl_revenda.endereco           AS revenda_endereco,
			tbl_revenda.numero             AS revenda_numero,
			tbl_revenda.complemento        AS revenda_complemento,
			tbl_revenda.fone               AS revenda_telefone,
			tbl_revenda.email              AS revenda_email,
			tbl_produto.produto,
			tbl_produto.referencia         AS produto_referencia,
			tbl_produto.descricao          AS produto_descricao,
			tbl_produto.parametros_adicionais AS parametros_adicionais_produto,
			tbl_produto.voltagem           AS produto_voltagem,
			tbl_produto.entrega_tecnica    AS produto_entrega_tecnica,
			tbl_produto.origem             AS produto_origem,
			tbl_produto.valores_adicionais AS produto_valores_adicionais,
			tbl_produto.produto_principal,
			tbl_posto.posto,
			tbl_posto_fabrica.codigo_posto AS posto_codigo,
			tbl_posto.nome AS posto_nome
	   	FROM tbl_hd_chamado
	   	JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
	   	LEFT JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado_postagem.hd_chamado = tbl_hd_chamado.hd_chamado
	   	LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_hd_chamado_extra.revenda AND tbl_revenda.cnpj = tbl_hd_chamado_extra.revenda_cnpj
	   	LEFT JOIN tbl_cidade AS cidade_consumidor ON cidade_consumidor.cidade = tbl_hd_chamado_extra.cidade
	   	LEFT JOIN tbl_cidade AS cidade_revenda ON cidade_revenda.cidade = tbl_revenda.cidade
		JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = tbl_hd_chamado_extra.produto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_hd_chamado_extra.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
	  	WHERE tbl_hd_chamado.fabrica   = {$login_fabrica}
		AND tbl_hd_chamado.hd_chamado  = {$preos}
		{$produto_principal};
	";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT = array(
			'os' => array(
				'hd_chamado'        => $preos,
				'nota_fiscal'       => pg_fetch_result($res, 0, 'nota_fiscal'),
				'data_compra'       => pg_fetch_result($res, 0, 'data_compra'),
				'tipo_atendimento'	=> pg_fetch_result($res, 0, 'tipo_atendimento'),
				'defeito_reclamado' => pg_fetch_result($res, 0, 'defeito_reclamado_id'),
				'defeito_reclamado_descricao' => pg_fetch_result($res, 0, 'defeito_reclamado_descricao')
			),
			"consumidor" => array(
				"nome"        => pg_fetch_result($res, 0, "consumidor_nome"),
				"cpf"         => pg_fetch_result($res, 0, "consumidor_cpf"),
				"cep"         => pg_fetch_result($res, 0, "consumidor_cep"),
				"estado"      => pg_fetch_result($res, 0, "consumidor_estado"),
				"cidade"      => pg_fetch_result($res, 0, "consumidor_cidade"),
				"bairro"      => pg_fetch_result($res, 0, "consumidor_bairro"),
				"endereco"    => pg_fetch_result($res, 0, "consumidor_endereco"),
				"numero"      => pg_fetch_result($res, 0, "consumidor_numero"),
				"complemento" => pg_fetch_result($res, 0, "consumidor_complemento"),
				"telefone"    => pg_fetch_result($res, 0, "consumidor_telefone"),
				"email"       => pg_fetch_result($res, 0, "consumidor_email"),
				"celular"     => pg_fetch_result($res, 0, "consumidor_celular")
			),
			'revenda' => array(
				'nome'        => pg_fetch_result($res, 0, 'revenda_nome'),
				'cnpj'        => pg_fetch_result($res, 0, 'revenda_cnpj'),
				'cep'         => pg_fetch_result($res, 0, 'revenda_cep'),
				'estado'      => pg_fetch_result($res, 0, 'revenda_estado'),
				'cidade'      => pg_fetch_result($res, 0, 'revenda_cidade'),
				'bairro'      => pg_fetch_result($res, 0, 'revenda_bairro'),
				'endereco'    => pg_fetch_result($res, 0, 'revenda_endereco'),
				'numero'      => pg_fetch_result($res, 0, 'revenda_numero'),
				'complemento' => pg_fetch_result($res, 0, 'revenda_complemento'),
				'telefone'    => pg_fetch_result($res, 0, 'revenda_telefone')
			),
			'produto' => array(
				'id'         => pg_fetch_result($res, 0, 'produto'),
				'referencia' => pg_fetch_result($res, 0, 'produto_referencia'),
				'descricao'  => pg_fetch_result($res, 0, 'produto_descricao'),
				'voltagem'   => pg_fetch_result($res, 0, 'produto_voltagem'),
				'serie'      => pg_fetch_result($res, 0, 'produto_serie')
			),
			'posto' => array(
				'id'     => pg_fetch_result($res, 0, 'posto'),
				'codigo' => pg_fetch_result($res, 0, 'posto_codigo'),
				'nome'   => pg_fetch_result($res, 0, 'posto_nome')
			)
		);

		if ($usaProdutoGenerico) {
			$produto_principal = pg_fetch_result($res, 0, 'produto_principal');
			if (!$produto_principal) {
				unset($_RESULT['produto']);
			}
		}


		if (in_array($login_fabrica, [186])) {
			$revenda_email = pg_fetch_result($res, 0, "revenda_email");
			if (!empty($revenda_email)) {
				$_RESULT["revenda"]["email"] = $revenda_email;
			}
		}

		if(strlen(pg_fetch_result($res, 0, "consumidor_cpf")) > 0){

			$cpf_cnpj = str_replace(array(".", "-", "/"), "", pg_fetch_result($res, 0, "consumidor_cpf"));

			if(strlen($cpf_cnpj) == 11){
				$_RESULT["consumidor"]["cnpjCpf"] = "cpf";
			}else if(strlen($cpf_cnpj) == 14){
				$_RESULT["consumidor"]["cnpjCpf"] = "cnpj";
			}else{
				$_RESULT["consumidor"]["cnpjCpf"] = $_POST['consumidor']['cnpjCpf'];
			}

		}

	} else {
		$msg_erro["msg"][] = "Pré-OS não encontrada";
		$erro_carrega_os = true;
	}
}

if ($login_fabrica == 143 && !isset($_POST['gravar']) && $areaAdmin == false) {

	$sql = "
		SELECT tbl_posto.nome,
			   tbl_posto.cnpj,
			   tbl_posto_fabrica.contato_cep,
			   tbl_posto_fabrica.contato_estado,
			   UPPER(TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN9')) AS contato_cidade,
			   tbl_posto_fabrica.contato_bairro,
			   tbl_posto_fabrica.contato_endereco,
			   tbl_posto_fabrica.contato_numero,
			   tbl_posto_fabrica.contato_complemento,
			   tbl_posto_fabrica.contato_fone_comercial,
			   tbl_posto_fabrica.contato_email
		  FROM tbl_posto
		  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		 WHERE tbl_posto_fabrica.posto = {$login_posto}
	";
	$res = pg_query($con, $sql);

	$_RESULT = array(
		'consumidor' => array(
			'nome'        => pg_fetch_result($res, 0, 'nome'),
			'cpf'         => pg_fetch_result($res, 0, 'cnpj'),
			'cep'         => pg_fetch_result($res, 0, 'contato_cep'),
			'estado'      => pg_fetch_result($res, 0, 'contato_estado'),
			'cidade'      => pg_fetch_result($res, 0, 'contato_cidade'),
			'bairro'      => pg_fetch_result($res, 0, 'contato_bairro'),
			'endereco'    => pg_fetch_result($res, 0, 'contato_endereco'),
			'numero'      => pg_fetch_result($res, 0, 'contato_numero'),
			'complemento' => pg_fetch_result($res, 0, 'contato_complemento'),
			'telefone'    => pg_fetch_result($res, 0, 'contato_fone_comercial'),
			'email'       => pg_fetch_result($res, 0, 'contato_email')
		)
	);

}

if (!empty($_GET["chave_os_conjunto"])) {
	$os = $_GET["os"];
	$chave_os_conjunto = $_GET["chave_os_conjunto"];

	if (empty($os)) {
		$msg_erro["msg"][] = "Ordem de Serviço de origem inválida";
		$erro_carrega_os = true;
		unset($os);
	} else {
		$chave_os_origem = sha1($os.$login_fabrica);

		if ($chave_os_origem != $chave_os_conjunto) {
			$msg_erro["msg"][] = "Ordem de Serviço de origem inválida";
			$erro_carrega_os = true;
			unset($os);
		} else {
			$sql_os_numero = "SELECT os_numero FROM tbl_os WHERE fabrica = {$login_fabrica} AND os_numero = {$os}";
            $res_os_numero = pg_query($con, $sql_os_numero);
			if (pg_num_rows($res_os_numero) > 0) {
				$msg_erro["msg"][] = "Ordem de Serviço de origem já possui uma Ordem de Serviço de conjunto aberta";
				$erro_carrega_os = true;
				unset($os);
			} else {
				if (!$areaAdmin) {
					$wherePosto = "AND tbl_os.posto = {$login_posto}";
				}

				$sql = "
					SELECT tbl_os.hd_chamado, tbl_os.os_numero, tbl_familia.deslocamento
					FROM tbl_os
					INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_os.os = {$os}
					{$wherePosto}
				";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {
					$msg_erro["msg"][] = "Ordem de Serviço de origem inválida";
					$erro_carrega_os = true;
					unset($os);
				} else {
					$hd_chamado          = pg_fetch_result($res, 0, "hd_chamado");
					$os_numero           = pg_fetch_result($res, 0, "os_numero");
					$familia_os_conjunto = pg_fetch_result($res, 0, "deslocamento");

					if (strtolower($familia_os_conjunto) == "t" && !empty($hd_chamado) && empty($os_numero)) {
						$os_conjunto = true;
					} else {
						$msg_erro["msg"][] = "Ordem de Serviço de origem inválida";
						$erro_carrega_os = true;
						unset($os);
					}
				}
			}
		}
	}
}

if (strlen($os) > 0 OR strlen($numOs) > 0 ) {
	//Essa variável APLICATIVO vem da rotina do aplicativo de ticket/Orçapá
	//rotina/telecontrol/retorna_dados_ticket.php
	if(strlen($numOs)>0 and $aplicativo == true){
		$os = $numOs;
	}
	$sql = "SELECT
			TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
			TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS data_digitacao,
			tbl_os.sua_os,
			tbl_os.tipo_atendimento,
			tbl_os.nota_fiscal,
			TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
			tbl_os.defeito_reclamado_descricao,
			tbl_os.defeito_reclamado,
			tbl_os.aparencia_produto,
			tbl_os.acessorios,
			CASE WHEN tbl_os.fabrica = 52 AND tbl_os.hd_chamado IS NOT NULL
				THEN tbl_os.obs || '\nMotivo: ' || tbl_os.observacao
				ELSE tbl_os.obs
			END AS obs,
			tbl_os.qtde_km,
			tbl_os.solucao_os,
			tbl_os.serie as produto_serie,
			tbl_os.consumidor_revenda,
			tbl_os.consumidor_nome,
			tbl_os.consumidor_cpf,
			tbl_os.consumidor_cep,
			tbl_os.consumidor_estado,
			UPPER(fn_retira_especiais(tbl_os.consumidor_cidade)) AS consumidor_cidade,
			UPPER(fn_retira_especiais(tbl_os.consumidor_bairro)) AS consumidor_bairro,
			tbl_os.consumidor_endereco,
			tbl_os.consumidor_numero,
			tbl_os.consumidor_complemento,
			tbl_os.consumidor_fone,
			tbl_os.consumidor_celular,
			tbl_os.consumidor_email,
			tbl_os.posto,
			tbl_os.cortesia,
			tbl_os.contrato,
			tbl_os.condicao,
			tbl_os.prateleira_box,
			tbl_os.justificativa_adicionais,
			tbl_os.qtde_hora,
			tbl_os.hora_tecnica,
			tbl_os.rg_produto,
			tbl_os.key_code,
			tbl_os.qtde_diaria,
			tbl_os.solucao_os,
			tbl_os.embalagem_original,
			tbl_os.codigo_fabricacao,
			tbl_os.cliente_admin,
			tbl_posto_fabrica.codigo_posto AS posto_codigo,
			tbl_posto.nome AS posto_nome,
			tbl_os.revenda,
			tbl_os.capacidade,
			tbl_revenda.nome AS revenda_nome,
			tbl_revenda.cnpj AS revenda_cnpj,
			tbl_revenda.cep AS revenda_cep,
			case when cidade_revenda.estado_exterior notnull then cidade_revenda.estado_exterior else cidade_revenda.estado end AS revenda_estado,
			UPPER(TO_ASCII(cidade_revenda.nome, 'LATIN9')) AS revenda_cidade,
			tbl_revenda.bairro AS revenda_bairro,
			tbl_revenda.endereco AS revenda_endereco,
			tbl_revenda.numero AS revenda_numero,
			tbl_revenda.complemento AS revenda_complemento,
			tbl_revenda.fone AS revenda_telefone,
			tbl_revenda.email AS revenda_email,
			tbl_os_revenda.campos_extra AS revenda_campos_extra,
			tbl_os_campo_extra.venda,
			tbl_os_campo_extra.campos_adicionais,
			tbl_os_campo_extra.valores_adicionais,
			tbl_os_campo_extra.marca AS marca_produto,
			tbl_os.nf_os,
			tbl_os.hd_chamado,
			tbl_os.motivo_atraso,
			tbl_os.pedagio,
			tbl_os.produto AS produto_os,
			tbl_os.defeito_constatado_grupo,
			tbl_os.os_posto,
			tbl_os.serie_reoperado,
			tbl_os_extra.tecnico AS info_tecnico,
			tbl_os_extra.obs_adicionais,
			tbl_os.type,
			tbl_os_extra.garantia,
			tbl_os_extra.serie_justificativa,
			tbl_os_extra.natureza_servico,
			tbl_os.tipo_os,
			tbl_tipo_os.descricao AS tipo_os_descricao,
			tbl_os.tecnico,
			tbl_os_extra.regulagem_peso_padrao,
			tbl_status_os.descricao AS status_os,
			tbl_os_extra.recolhimento,
			tbl_os.causa_defeito,
			tbl_os.consumidor_nome_assinatura AS os_contato,
			tbl_tipo_atendimento.descricao AS tipo_atendimento_descricao,
			tbl_tipo_atendimento.grupo_atendimento,
			tbl_os.os_numero,
			tbl_os.troca_garantia,
			tbl_os_extra.obs AS obs_os_extra,
			tbl_os_extra.faturamento_cliente_revenda,
			tbl_os_extra.qtde_horas,
			to_char(tbl_os_extra.data_fabricacao,'DD/MM/YYYY') as data_fabricacao,

			tbl_os.marca,
			coalesce(tbl_os_extra.mao_de_obra_adicional, 0) as mao_de_obra_adicional,
			coalesce(tbl_os_extra.desconto, 0) as desconto_os,
			tbl_os.status_checkpoint,
			tbl_posto.pais as pais_posto
		FROM tbl_os
		JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
		LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}
		LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
		LEFT JOIN tbl_os_revenda ON tbl_os_revenda.sua_os = SPLIT_PART(tbl_os.sua_os, '-', 1) AND tbl_os_revenda.fabrica = {$login_fabrica}
		LEFT JOIN tbl_cidade AS cidade_revenda ON cidade_revenda.cidade = tbl_revenda.cidade
		LEFT JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os.status_os_ultimo
		LEFT JOIN tbl_tipo_os ON tbl_tipo_os.tipo_os = tbl_os.tipo_os
		LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
		{$join_produto_principal}
		WHERE tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.os = {$os}
		{$where_produto_prinpal}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT = array(
			'os' => array(
				'data_abertura'      => pg_fetch_result($res, 0, 'data_abertura'),
				'sua_os'      		 => pg_fetch_result($res, 0, 'sua_os'),
				'tipo_atendimento'   => pg_fetch_result($res, 0, 'tipo_atendimento'),
				'nota_fiscal'        => pg_fetch_result($res, 0, 'nota_fiscal'),
				'data_compra'        => pg_fetch_result($res, 0, 'data_nf'),
				'defeito_reclamado'  => (in_array($login_fabrica, array(52)) || isset($usaDefeitoReclamadoCadastro)) ? pg_fetch_result($res, 0, 'defeito_reclamado') : pg_fetch_result($res, 0, 'defeito_reclamado_descricao'),
				'aparencia_produto'  => pg_fetch_result($res, 0, 'aparencia_produto'),
				'acessorios'         => pg_fetch_result($res, 0, 'acessorios'),
				'observacoes'        => pg_fetch_result($res, 0, 'obs'),
				'qtde_km'            => pg_fetch_result($res, 0, 'qtde_km'),
				'qtde_km_hidden'     => pg_fetch_result($res, 0, 'qtde_km'),
				'solucao'            => pg_fetch_result($res, 0, 'solucao_os'),
				'cortesia'           => pg_fetch_result($res, 0, 'cortesia'),
				'prateleira_box'     => pg_fetch_result($res, 0, 'prateleira_box'),
				'consumidor_revenda' => pg_fetch_result($res, 0, 'consumidor_revenda'),
				'motivo_atraso'      => pg_fetch_result($res, 0, 'motivo_atraso'),
				'pedagio'            => pg_fetch_result($res, 0, 'pedagio'),
				"id_tecnico"         => pg_fetch_result($res, 0, 'tecnico'),
				"causa_defeito"      => pg_fetch_result($res, 0, 'causa_defeito'),
				"marca"		     	 => pg_fetch_result($res, 0, 'marca'),
				"enviar_para"	     => pg_fetch_result($res, 0, 'faturamento_cliente_revenda'),
				"status_checkpoint"  => pg_fetch_result($res, 0, 'status_checkpoint'),
				"troca_garantia"     => pg_fetch_result($res, 0, 'troca_garantia')
			),
			'consumidor' => array(
				'nome'        	=> pg_fetch_result($res, 0, 'consumidor_nome'),
				'cpf'         	=> pg_fetch_result($res, 0, 'consumidor_cpf'),
				'cep'         	=> pg_fetch_result($res, 0, 'consumidor_cep'),
				'estado'      	=> pg_fetch_result($res, 0, 'consumidor_estado'),
				'cidade'      	=> pg_fetch_result($res, 0, 'consumidor_cidade'),
				'bairro'      	=> pg_fetch_result($res, 0, 'consumidor_bairro'),
				'endereco'    	=> pg_fetch_result($res, 0, 'consumidor_endereco'),
				'numero'      	=> pg_fetch_result($res, 0, 'consumidor_numero'),
				'complemento' 	=> pg_fetch_result($res, 0, 'consumidor_complemento'),
				'telefone'    	=> pg_fetch_result($res, 0, 'consumidor_fone'),
				'celular'     	=> pg_fetch_result($res, 0, 'consumidor_celular'),
				'email'       	=> pg_fetch_result($res, 0, 'consumidor_email')
			),
			'revenda' => array(
				'id'          => pg_fetch_result($res, 0, 'revenda'),
				'nome'        => pg_fetch_result($res, 0, 'revenda_nome'),
				'cnpj'        => pg_fetch_result($res, 0, 'revenda_cnpj'),
				'cep'         => pg_fetch_result($res, 0, 'revenda_cep'),
				'estado'      => pg_fetch_result($res, 0, 'revenda_estado'),
				'cidade'      => pg_fetch_result($res, 0, 'revenda_cidade'),
				'bairro'      => pg_fetch_result($res, 0, 'revenda_bairro'),
				'endereco'    => pg_fetch_result($res, 0, 'revenda_endereco'),
				'numero'      => pg_fetch_result($res, 0, 'revenda_numero'),
				'complemento' => pg_fetch_result($res, 0, 'revenda_complemento'),
				'telefone'    => pg_fetch_result($res, 0, 'revenda_telefone')
			),
			'posto' => array(
				'id'     => pg_fetch_result($res, 0, 'posto'),
				'codigo' => pg_fetch_result($res, 0, 'posto_codigo'),
				'nome'   => pg_fetch_result($res, 0, 'posto_nome'),
				'pais'   => pg_fetch_result($res, 0, 'pais_posto')
			)
		);

		if (!empty(pg_fetch_result($res, 0, 'revenda_cnpj'))) {

			$cpf_cnpj_revenda = str_replace(array(".", "-", "/"), "", pg_fetch_result($res, 0, "revenda_cnpj"));

			if(strlen($cpf_cnpj_revenda) == 11){
				$_RESULT["revenda"]["cnpjCpf"] = "cpf";
			}else if(strlen($cpf_cnpj_revenda) == 14){
				$_RESULT["revenda"]["cnpjCpf"] = "cnpj";
			}else{
				$_RESULT["revenda"]["cnpjCpf"] = $_POST['revenda']['cnpjCpf'];
			}

		}	

		if (in_array($login_fabrica, [186])) {
			$revenda_campos_extra = json_decode(pg_fetch_result($res, 0, "revenda_campos_extra"), true);
			$revenda_email        = $revenda_campos_extra['revenda_email'];
			$revenda_telefone     = $revenda_campos_extra['revenda_telefone'];

			if (!empty($revenda_email)) {
				$_RESULT["revenda"]["email"]    = $revenda_email;
			}

			if (!empty($revenda_telefone)) {
				$_RESULT["revenda"]["telefone"] = $revenda_telefone;	
			}
		}

		if ($usaPostoTecnico){
			$_RESULT["os"]["tecnico"] = pg_fetch_result($res, 0, "tecnico");
		}

		if (in_array($login_fabrica,[184,191,198,200])){
			$_RESULT["os"]["tecnico"] = pg_fetch_result($res, 0, "tecnico");
		}
		
		if ($login_fabrica == 183){
			$_RESULT["consumidor"]["ponto_referencia"] = pg_fetch_result($res, 0, "obs_os_extra");
		}

		if ($os_conjunto) {
			$_RESULT["os"]["data_abertura"] = date("d/m/Y");
		}

		if($login_fabrica == 35){
			$_RESULT['consumidor']['op_email'] = pg_fetch_result($res, 0, "obs_adicionais");
		}

		// Campos adicionais gravado em tbl_os_campo_extra.valores_adicionais
		if (in_array($login_fabrica, [148])):
			$obs_valores_adicionais = pg_fetch_result($res, 0, valores_adicionais);
			$obs_valores_adicionais = json_decode($obs_valores_adicionais, true);

			$_RESULT["os"]["valores_adicionais"] = [
				"observacao"       => utf8_decode($obs_valores_adicionais["observacao"]),
				"nome_contato"     => utf8_decode($obs_valores_adicionais["observacoes_nome_contato"]),
				"telefone_contato" => $obs_valores_adicionais["observacoes_telefone_contato"]
			];
		endif;

		// Campos Adicionais gravados em tbl_os_campo_extra.campos_adicionais
		$campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");
		$campos_adicionais = json_decode($campos_adicionais,true);
		
		if ($login_fabrica == 171) {
			$_RESULT["os"]["pressao_agua"] = pg_fetch_result($res, 0, "regulagem_peso_padrao");
			$_RESULT["os"]["tempo_uso"]    = pg_fetch_result($res, 0, "qtde_horas");
			$_RESULT["os"]["qtde_visita"]  = pg_fetch_result($res, 0, "qtde_diaria");
			$_RESULT["os"]["qtde_km_ida"]  = $campos_adicionais["qtde_km_ida"];
			$_RESULT["os"]["qtde_km_volta"]= $campos_adicionais["qtde_km_volta"];
			$_RESULT["os"]["edificio"]     = $campos_adicionais["edificio"];
			$_RESULT["os"]["edificio_total_andares"] = $campos_adicionais["edificio_total_andares"];
			$_RESULT["os"]["contrato"] 	   = pg_fetch_result($res, 0, "contrato");
		}

		if (in_array($login_fabrica, [35])) {

			$_RESULT["os"]["formulario_reincidencia_justificativa"] = utf8_decode($campos_adicionais["justificativa_reincidencia"]);
			$_RESULT["os"]["formulario_reincidencia_opcao"] 		= utf8_decode($campos_adicionais["resposta_reincidencia"]);

		}

		if (in_array($login_fabrica, [191])) {

			$_RESULT["os"]["numero_nf_remessa"] = utf8_decode($campos_adicionais["numero_nf_remessa"]);

		}

		if (in_array($login_fabrica, [193])) {
			$_RESULT["os"]["fabricante_motor"]  = $campos_adicionais["fabricante_motor"];
			$_RESULT["os"]["tempo_conserto"]    = $campos_adicionais["tempo_conserto"];
		}

		if(strlen(pg_fetch_result($res, 0, "consumidor_cpf")) > 0 && in_array($login_fabrica, [131])) {

			$cpf_cnpj = str_replace(array(".", "-", "/"), "", pg_fetch_result($res, 0, "consumidor_cpf"));

			if(strlen($cpf_cnpj) == 11){
				$_RESULT["consumidor"]["cnpjCpf"] = "cpf";
			}else if(strlen($cpf_cnpj) == 14){
				$_RESULT["consumidor"]["cnpjCpf"] = "cnpj";
			}else{
				$_RESULT["consumidor"]["cnpjCpf"] = $_POST['consumidor']['cnpjCpf'];
			}

		}

		if ($login_fabrica == 176)
		{
			$_RESULT["os"]["indice"] = pg_fetch_result($res, 0, "type");
		}

		if ($login_fabrica == 178){

			$_RESULT["os"]["marca_produto"] = pg_fetch_result($res, 0, "marca_produto");
			$_RESULT["os"]["rastreabilidade"] = $campos_adicionais["rastreabilidade"];
			$_RESULT["os"]["produto_troca"] = $campos_adicionais["produto_troca_posto"];
			$_RESULT["os"]["instalacao_publica"] = $campos_adicionais["instalacao_publica"];
			$_RESULT["consumidor"]["inscricao_estadual"] = $campos_adicionais["inscricao_estadual"];
		}

		if ($login_fabrica == 138) {
			$_RESULT["os"]["garantia"] = pg_fetch_result($res, 0, "garantia");
		}
		if($login_fabrica == 190){
			$produto_horimetro      = pg_fetch_result($res, 0, "qtde_hora");
		}
		if($login_fabrica == 148){
			$_RESULT["os"]["venda"] = pg_fetch_result($res, 0, "venda");
			$produto_horimetro      = pg_fetch_result($res, 0, "qtde_hora");
			$produto_revisao        = pg_fetch_result($res, 0, "hora_tecnica");
			$obs_adicionais         = pg_fetch_result($res, 0, "obs_adicionais");
		}

		if ($login_fabrica == 161) {
			$_RESULT["produto"]["sem_ns"] = $campos_adicionais["sem_ns"];
		}

		if ($login_fabrica == 131) {
			$data_fabricacao = pg_fetch_result($res, 0, "data_fabricacao");
			$causa_defeito   = pg_fetch_result($res, 0, "causa_defeito");
		}
		if ($login_fabrica == 195) {
			$data_fabricacao = pg_fetch_result($res, 0, "data_fabricacao");
			$array_campos_adicionais		= pg_fetch_result($res, 0, "campos_adicionais");
			$array_campos_adicionais    	= json_decode($array_campos_adicionais, true);

			$solicita_troca_antecipada = $array_campos_adicionais['solicita_troca_antecipada'];
			$solicita_troca_antecipada_orcamento = $array_campos_adicionais['solicita_troca_antecipada_orcamento'];

		}

		if ($login_fabrica == 177){
			$produto_lote = pg_fetch_result($res, 0, "codigo_fabricacao");
		}

		if ($login_fabrica == 142) {
			$_RESULT["os"]["qtde_visita"] = pg_fetch_result($res, 0, "qtde_diaria");
		}

		if ($login_fabrica == 145) {
			$_RESULT["os"]["construtora"] = pg_fetch_result($res, 0, "nf_os");
		}

        if(in_array($login_fabrica, array(164))){
            $_RESULT["produto"]["numero_serie_calefator"] = $campos_adicionais["numero_serie_calefator"];
            $_RESULT["produto"]["cor_indicativa_carcaca"] = $campos_adicionais["cor_indicativa_carcaca"];
            $_RESULT["os"]["data_entrada"]                = $campos_adicionais["data_entrada"];

            $sql_adicionais = "SELECT os FROM tbl_os_campo_extra WHERE os={$os} AND fabrica=164 AND campos_adicionais::jsonb->>'troca_produto' = 't'";
			$res_adicionais = pg_query($con,$sql_adicionais);

			if(pg_num_rows($res_adicionais) > 0){
				$_RESULT["produto"]["troca_produto"] = "t";
			}
        }
        

        if(in_array($login_fabrica, array(167,186,190,191,195,203))){

			$_RESULT["os"]["os_contato"] = pg_fetch_result($res, 0, "os_contato");
			$numero_contador = pg_fetch_result($res, 0, "condicao");

			$_RESULT["os"]["status_orcamento"] = pg_fetch_result($res, 0, "status_os");

			if($campos_adicionais["valor_adicional_peca_produto"] > 0){
				$_RESULT["os"]["valor_adicional_peca_produto"] = $campos_adicionais["valor_adicional_peca_produto"];
			}

			if (in_array($login_fabrica, [203])) {
				$_RESULT["produto"]["recebido_via_correios"]  = $campos_adicionais["produto_recebido_via_correios"];
			}
		}

		if (in_array($login_fabrica, [177])) {
			$_RESULT["os"]["orcamento_status"] = pg_fetch_result($res, 0, "status_os");
			$_RESULT['os']["valor_adicional_mo"]  = number_format(pg_fetch_result($res, 0, 'mao_de_obra_adicional'), 2,",", ".");
			$_RESULT['os']["desconto"]         = number_format(pg_fetch_result($res, 0, 'desconto_os'), 2,",",".");
		}


		if (in_array($login_fabrica, [186,190,191,195])) {
			$_RESULT['os']["valor_adicional_mo"]  = number_format(pg_fetch_result($res, 0, 'mao_de_obra_adicional'), 2,",", ".");
		}

		if ($login_fabrica == 163) {
			$valores_adicionais_orcamento = json_decode(pg_fetch_result($res, 0, "valores_adicionais"), true);

			$_RESULT["os"]["valor_adicional_mo"] = number_format($valores_adicionais_orcamento["Valor Adicional"], 2, ",", ".");
			$_RESULT["os"]["desconto"] = number_format($valores_adicionais_orcamento["Desconto"], 2, ",", ".");
		}

		if (in_array($login_fabrica,array(144,151,157,158))) {
			$_RESULT["os"]["os_posto"] = pg_fetch_result($res, 0, "os_posto");
		}
		if (in_array($login_fabrica, array(152,180,181,182))) {
			$_RESULT["os"]["tempo_deslocamento"] = pg_fetch_result($res, 0, "qtde_hora");
		}

		if($login_fabrica == 153){
			$obs_adicionais = pg_fetch_result($res, 0, "obs_adicionais");
		}

		if(in_array($login_fabrica,array(156,173))){
			$obs_adicionais = pg_fetch_result($res, 0, "obs_adicionais");
		}

		if($login_fabrica == 160 or $replica_einhell){
			$type 		=	pg_fetch_result($res, 0, "type");
			$tipo_os 	= 	pg_fetch_result($res, 0, "tipo_os");
		}

		if ($login_fabrica == 156) {
			$_RESULT["os"]["os_elgin_status"]   = pg_fetch_result($res, 0, "status_os");
			$_RESULT["os"]["natureza_operacao"] = pg_fetch_result($res, 0, "natureza_servico");
			$_RESULT["os"]["fora_garantia"]     = pg_fetch_result($res, 0, "tipo_os");
			$_RESULT["os"]["tipo_produto"]      = pg_fetch_result($res, 0, "tipo_os_descricao");

			$_RESULT["os"]["nf_envio"]       = $campos_adicionais["nf_envio"];
			$_RESULT["os"]["data_nf_envio"]  = $campos_adicionais["data_nf_envio"];
			$_RESULT["os"]["valor_nf_envio"] = $campos_adicionais["valor_nf_envio"];

			$_RESULT["os"]["nf_retorno"] = $campos_adicionais["nf_retorno"];
			$_RESULT["os"]["data_nf_retorno"] = $campos_adicionais["data_nf_retorno"];
			$_RESULT["os"]["valor_nf_retorno"] = $campos_adicionais["valor_nf_retorno"];


			$_RESULT["os"]["nota_fiscal_mo"]       = $campos_adicionais["nota_fiscal_mo"];
			$_RESULT["os"]["data_nota_fiscal_mo"]  = $campos_adicionais["data_nota_fiscal_mo"];
			$_RESULT["os"]["valor_nota_fiscal_mo"] = $campos_adicionais["valor_nota_fiscal_mo"];


			$_RESULT["os"]["nota_fiscal_peca"]       = $campos_adicionais["nota_fiscal_peca"];
			$_RESULT["os"]["data_nota_fiscal_peca"]  = $campos_adicionais["data_nota_fiscal_peca"];
			$_RESULT["os"]["valor_nota_fiscal_peca"] = $campos_adicionais["valor_nota_fiscal_peca"];
		}

		if (in_array($login_fabrica, array(143))) {
			$_RESULT["os"]["rg_produto"] = pg_fetch_result($res, 0, "rg_produto");
		}

		if (in_array($login_fabrica, array(52))) {
			$_RESULT["consumidor"]["pais"]                  = $campos_adicionais["pais"];
		}

		if(in_array($login_fabrica, array(162))){
			$partnumber = pg_fetch_result($res, 0, "key_code");
			$rg_produto = pg_fetch_result($res, 0, "rg_produto");

			$_RESULT["os"]["data_saida"]    = $campos_adicionais["data_saida"];
			$_RESULT["os"]["rastreio"]      = $campos_adicionais["rastreio"];
		}

		if (in_array($login_fabrica, [180,181,182])) {
			$_RESULT["consumidor"]["estado"] = $campos_adicionais["estado"];
		}

		$justificativa_adicionais = pg_fetch_result($res, 0, 'justificativa_adicionais');
		$defeito_constatado_grupo = pg_fetch_result($res, 0, 'defeito_constatado_grupo');
		$marca                    = pg_fetch_result($res, 0, 'marca');
		$info_tecnico             = pg_fetch_result($res, 0, 'info_tecnico');
		$hd_chamado               = pg_fetch_result($res, 0, 'hd_chamado');
		$produto_os               = pg_fetch_result($res, 0, 'produto_os');
		$produto_serie            = pg_fetch_result($res, 0, 'produto_serie');
		$solucao_os               = pg_fetch_result($res, 0, 'solucao_os');
		$cliente_admin            = pg_fetch_result($res, 0, 'cliente_admin');
		$serie_reoperado          = pg_fetch_result($res, 0, 'serie_reoperado');
		$embalagem_original       = pg_fetch_result($res, 0, 'embalagem_original');
		$serie_justificativa      = pg_fetch_result($res, 0, "serie_justificativa");
		$produto_reparo_fabrica   = pg_fetch_result($res, 0, "recolhimento");
		$amperagem 				  = pg_fetch_result($res, 0, "regulagem_peso_padrao");

		if (in_array($login_fabrica, [131])) {

			$arr_justificativa_adicionais = json_decode($justificativa_adicionais, true);
			$_RESULT['os']['justificativa_abertura'] = $arr_justificativa_adicionais["justificativa_abertura"];

		}

		if (in_array($login_fabrica, array(158,169,170))) {
			$grupo_atendimento = pg_fetch_result($res, 0, "grupo_atendimento");
			$_RESULT["os"]["os_numero"] = pg_fetch_result($res, 0, "os_numero");
		}

		if ($login_fabrica == 158) {
			$_RESULT['os']['data_digitacao'] = pg_fetch_result($res, 0, 'data_digitacao');
			$_RESULT["os"]["garantia"] = pg_fetch_result($res, 0, "garantia");
		}

		if (in_array($login_fabrica, array(169,170))) {
			$_RESULT["os"]["defeito_reclamado_descricao"] = pg_fetch_result($res, 0, "defeito_reclamado_descricao");
			$_RESULT["produto"]["emprestimo"] = pg_fetch_result($res, 0, "contrato");
			$_RESULT["revenda"]["contato"] = pg_fetch_result($res, 0, "os_contato");
			$tipo_atendimento_descricao = pg_fetch_result($res, 0, "tipo_atendimento_descricao");
		}

		if (in_array($login_fabrica, array(173,174,176,183,184,191,193,200))) {
			$_RESULT["os"]["defeito_reclamado_descricao"] = pg_fetch_result($res, 0, "defeito_reclamado_descricao");
		}

		if ($login_fabrica == 175){
			$_RESULT["os"]["quantidade_disparos"] = pg_fetch_result($res, 0, "capacidade");
		}

		if ($defeitoReclamadoCadastroDefeitoReclamadoCliente || $login_fabrica == 190){
			$_RESULT["os"]["defeito_reclamado_descricao"] = pg_fetch_result($res, 0, "defeito_reclamado_descricao");
			$_RESULT["os"]["defeito_reclamado"] = pg_fetch_result($res, 0, "defeito_reclamado");
		}

		if (!empty($hd_chamado)) {
			$_RESULT["os"]["hd_chamado"] = $hd_chamado;
		}

		/**
		 * Pega informações do produto
		 */
		if($login_fabrica == 35){
			$produto_critico = " tbl_produto.produto_critico,  ";
		}

		if($login_fabrica == 162){
			$join_linha  = " INNER JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha ";
			$campos_linha = ", tbl_linha.informatica";
		}
		if(in_array($login_fabrica, array(35,157))){
			$join_linha  = " INNER JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha ";
			$campos_linha = ", tbl_linha.deslocamento";
		}

		if ($usaProdutoGenerico) {
			$where = "AND produto_principal IS TRUE";
		}

		if ($login_fabrica == 176)
		{
			$join_linha   = " LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca ";
			$campos_linha = ", tbl_marca.visivel AS visivel ";
		}

		if ($login_fabrica == 175){
			$join_linha  = " INNER JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica";
			$campos_linha = ", tbl_linha.linha as produto_linha ";
		}

		$campo_garantia_horas     = '';
		if (in_array($login_fabrica, [193])) {
			$campo_garantia_horas = ", tbl_produto.garantia_horas";
		}

		if (!$os_conjunto) {
			$sql = "
				SELECT
					tbl_os_produto.os_produto,
					tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.voltagem,
					tbl_os_produto.serie,
					tbl_produto.entrega_tecnica,
					tbl_produto.valores_adicionais,
					tbl_produto.familia,
					tbl_produto.linha,
					tbl_produto.parametros_adicionais AS parametros_adicionais_produto,
					tbl_produto.troca_obrigatoria,
					tbl_produto.origem,
					tbl_produto.capacidade,
					tbl_defeito_constatado.defeito_constatado,
					tbl_causa_defeito.causa_defeito,
					tbl_familia.descricao AS familia_descricao,
					$produto_critico
					tbl_familia.deslocamento AS familia_deslocamento
					$campos_linha
					$campo_garantia_horas
				FROM tbl_os
				LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				INNER JOIN tbl_produto ON (tbl_produto.produto = tbl_os_produto.produto or tbl_os.produto = tbl_produto.produto) AND tbl_produto.fabrica_i = {$login_fabrica}
				{$join_linha}
				INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
				LEFT JOIN tbl_causa_defeito ON tbl_causa_defeito.causa_defeito = tbl_os_produto.causa_defeito AND tbl_causa_defeito.fabrica = {$login_fabrica}
				WHERE tbl_os.os = {$os}
				{$where}
				ORDER BY tbl_os_produto.os_produto ASC
				LIMIT 1;
			";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0 && !in_array($login_fabrica, array(169, 170))) {
				$carrega_produto = true;
			} else if (pg_num_rows($res) > 0 && in_array($login_fabrica, array(169, 170))) {
				if (!strlen(pg_fetch_result($res, 0, "serie"))) {
					if (empty($hd_chamado)) {
						$carrega_produto = true;
					} else {
						$carrega_produto = false;
					}
				} else {
					$carrega_produto = true;
				}
			}

			if ($carrega_produto === true) {
				$_RESULT['produto_disparos'] = (pg_fetch_result($res, 0, 'capacidade') > 0) ? 'true' : null;
				
				$_RESULT["produto"] = array(
					"os_produto"           => pg_fetch_result($res, 0, "os_produto"),
					"id"                   => pg_fetch_result($res, 0, "produto"),
					"referencia"           => pg_fetch_result($res, 0, "referencia"),
					"descricao"            => pg_fetch_result($res, 0, "descricao"),
					"voltagem"             => pg_fetch_result($res, 0, "voltagem"),
					"serie"                => pg_fetch_result($res, 0, "serie"),
					"defeito_constatado"   => pg_fetch_result($res, 0, "defeito_constatado"),
					"familia" 			   => pg_fetch_result($res, 0, "familia"),
					"linha"                            => pg_fetch_result($res, 0, "linha"),
					"troca_obrigatoria"	   => pg_fetch_result($res, 0, "troca_obrigatoria"),
					"familia_descricao"    => pg_fetch_result($res, 0, "familia_descricao"),
					"familia_deslocamento" => pg_fetch_result($res, 0, "familia_deslocamento")
				);
				if($login_fabrica == 35){
					$_RESULT["produto"]["produto_critico"] = pg_fetch_result($res, 0, "produto_critico");
				}

				if(empty($_RESULT["produto"]["serie"])) {
					$_RESULT["produto"]["serie"] = $produto_serie;
				}

				if ($login_fabrica == 164) {
					$sql_adicionais = "SELECT os FROM tbl_os_campo_extra WHERE os={$os} AND fabrica=164 AND campos_adicionais::jsonb->>'troca_produto' = 't'";
					$res_adicionais = pg_query($con,$sql_adicionais);

					if(pg_num_rows($res_adicionais) > 0){
						$_RESULT["produto"]["troca_produto"] = "t";
					}
				}

				if ($login_fabrica == 177){
					$_RESULT["produto"]["causa_defeito"] = pg_fetch_result($res, 0, "causa_defeito");
				}

				if (in_array($login_fabrica, array(164))) {
					$_RESULT["produto"]["origem_produto"] = pg_fetch_result($res, 0, "origem");
				}

				if ($login_fabrica == 175){
					$_RESULT["produto"]["produto_linha"] = pg_fetch_result($res, 0, "produto_linha");
				}

				if (in_array($login_fabrica, array(35,157))) {
					$_RESULT["produto"]["deslocamento_km"] = pg_fetch_result($res, 0, "deslocamento");
				}

				if ($login_fabrica == 177){
					$_RESULT["produto"]["lote"] = $produto_lote;
				}

				if ($login_fabrica == 178){
					$_RESULT["produto"]["defeito_constatado_grupo"] = $defeito_constatado_grupo;

					$fora_linha = pg_fetch_result($res, 0, "parametros_adicionais_produto");

					if (!empty($fora_linha)){
						$fora_linha = json_decode($fora_linha, true);
					}
					
					if ($fora_linha["fora_linha"] == true){
						$fora_linha = "true";
						$_RESULT["produto"]["produto_fora_linha"] = $fora_linha;
					}
				}

				if (in_array($login_fabrica, [193])) {
					$parametros_adicionais                = pg_fetch_result($res, 0, 'parametros_adicionais_produto');
					$parametros_adicionais                = json_decode($parametros_adicionais, true);
					$_RESULT['produto']['lancamento']     = (!empty($parametros_adicionais["lancamento"])) ? $parametros_adicionais["lancamento"] : "";
					$_RESULT['produto']['garantia_horas'] = (!empty(pg_fetch_result($res, 0, 'garantia_horas'))) ? pg_fetch_result($res, 0, 'garantia_horas') : "";
					
					$_RESULT['produto']['troca_produto']  = $campos_adicionais['troca_produto'];
				}

				if(in_array($login_fabrica, [167, 203])){ //HD-3428328
					$suprimento = pg_fetch_result($res, 0, 'parametros_adicionais_produto');
					$suprimento = json_decode($suprimento, true);
					$_RESULT['produto']['suprimento'] = $suprimento['suprimento'];
					$_RESULT["produto"]["contador"] = $numero_contador;

					if (in_array($login_fabrica, [203])) {
						$_RESULT["produto"]["recebido_via_correios"]  = $campos_adicionais["produto_recebido_via_correios"];
						$_RESULT["consumidor"]["nascimento"] 		  = $campos_adicionais["consumidor_nascimento"];
					}
				}

				if ($login_fabrica == 176)
				{
					$_RESULT["produto"]["marca_indice"] = pg_fetch_result($res, 0, "visivel");
				}

				if (!empty($justificativa_adicionais)) {
					$justificativa_adicionais = json_decode($justificativa_adicionais, true);

					if (isset($justificativa_adicionais['troca_produto'])) {
						$_RESULT['produto']['troca_produto'] = $justificativa_adicionais['troca_produto'];
					}

					if (isset($justificativa_adicionais['motivo_visita'])) {
						$_RESULT['os']['motivo_visita'] = utf8_decode($justificativa_adicionais['motivo_visita']);
					}
				}

				if ($login_fabrica == 165 && empty($_RESULT["produto"]["serie"])) {
					$_RESULT["produto"]["sem_ns"] = 't';
				} else if ($login_fabrica == 165 && !empty($_RESULT["produto"]["serie"])) {
					$sqlNumSerie = "
						SELECT  TO_CHAR(tbl_numero_serie.data_fabricacao,'DD/MM/YYYY') AS data_fabricacao
						FROM    tbl_numero_serie
						WHERE   fabrica = {$login_fabrica}
						AND     produto = {$_RESULT["produto"]["id"]}
						AND     serie = '{$_RESULT["produto"]["serie"]}';
					";
					$resNumSerie = pg_query($con, $sqlNumSerie);
					$_RESULT["produto"]["data_fabricacao"] = pg_fetch_result($resNumSerie, 0, data_fabricacao);
				}

				if ($login_fabrica == 161 && empty($_RESULT["produto"]["serie"])) {
					$_RESULT["produto"]["sem_ns"] = $campos_adicionais["sem_ns"];
				}

				if ($login_fabrica == 131) {
					$_RESULT["produto"]["data_fabricacao"] = $data_fabricacao;
					$_RESULT["produto"]["causa_defeito"]   = $causa_defeito;
				}

				if ($login_fabrica == 195) {
					$_RESULT["produto"]["data_fabricacao"] = $data_fabricacao;
					$_RESULT["produto"]["solicita_troca_antecipada"] = $solicita_troca_antecipada;
					$_RESULT["produto"]["solicita_troca_antecipada_orcamento"] = $solicita_troca_antecipada_orcamento;
				}

				if ($login_fabrica == 158) {
					$_RESULT["produto"]["patrimonio"] = $serie_justificativa;
					$_RESULT["produto"]["amperagem"] = number_format($amperagem, 2, ".", "");
					$_RESULT["os"]["unidade_negocio"] = $campos_adicionais["unidadeNegocio"];

					$_RESULT["os"]["marca"] = $campos_adicionais["marca"];


					if (!empty($_RESULT["produto"]["serie"]) && !empty($_RESULT["produto"]["id"])) {
						$sqlNumSerie = "SELECT * FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$_RESULT["produto"]["id"]} AND serie = '{$_RESULT["produto"]["serie"]}';";
						$resNumSerie = pg_query($con, $sqlNumSerie);

						if (pg_num_rows($resNumSerie) > 0) {
							$_RESULT["os"]["data_venda"] = pg_fetch_result($resNumSerie, 0, data_venda);
							$_RESULT["os"]["data_venda"] = DateTime::createFromFormat('Y-m-d',$_RESULT["os"]["data_venda"]);
							$_RESULT["os"]["data_venda"] = date_format($_RESULT["os"]["data_venda"], 'd/m/Y');
							$_RESULT["os"]["data_fabricacao"] = pg_fetch_result($resNumSerie, 0, data_fabricacao);
							$_RESULT["os"]["data_fabricacao"] = DateTime::createFromFormat('Y-m-d',$_RESULT["os"]["data_fabricacao"]);
							$_RESULT["os"]["data_fabricacao"] = date_format($_RESULT["os"]["data_fabricacao"], 'd/m/Y');
						}
					}

					$sqlSolucao = "SELECT solucao FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao IS NOT NULL";
					$resSolucao = pg_query($con, $sqlSolucao);

					if (pg_num_rows($resSolucao) > 0) {
						$_RESULT["produto"]["solucao"] = array();

						while ($solucao = pg_fetch_object($resSolucao)) {
							$_RESULT["produto"]["solucao"][] = $solucao->solucao;
						}
					}


					$sqlReclamado = "SELECT defeito_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_reclamado IS NOT NULL";
					$resReclamado = pg_query($con, $sqlReclamado);

					$reclamado = $_RESULT['os']['defeito_reclamado'];

					$_RESULT["os"]["defeito_reclamado"] = array();

					if(strlen(trim($reclamado))>0){
						$_RESULT['os']['defeito_reclamado'][] = $reclamado;
					}

					if (pg_num_rows($resReclamado) > 0) {						

						while ($defeito_reclamado = pg_fetch_object($resReclamado)) {
							$_RESULT["os"]["defeito_reclamado"][] = $defeito_reclamado->defeito_reclamado;
						}
					}

					$_RESULT["produto"]["pdv_chegada"]      = $campos_adicionais["pdv_chegada"];
					$_RESULT["produto"]["pdv_saida"] = $campos_adicionais["pdv_saida"];				
					$_RESULT["produto"]["marca"] = $campos_adicionais["marca"];	

				}

				if($login_fabrica == 148){
					$_RESULT["produto"]["horimetro"] = $produto_horimetro;
					$_RESULT["produto"]["revisao"] = $produto_revisao;

					$obs_adicionais_json = json_decode($obs_adicionais);
					$_RESULT["produto"]["serie_motor"] = $obs_adicionais_json->serie_motor;
					$_RESULT["produto"]["serie_transmissao"] = $obs_adicionais_json->serie_transmissao;

					$_RESULT["os"]["data_falha"] = $obs_adicionais_json->data_falha;

					$_RESULT["produto"]["detalhes_defeito"]   = (mb_detect_encoding($obs_valores_adicionais["descricao_falha"], "UTF-8")) ? utf8_decode($obs_valores_adicionais["descricao_falha"]) : $obs_valores_adicionais["descricao_falha"];
					$_RESULT["produto"]["detalhes_solucao"]   = (mb_detect_encoding($obs_valores_adicionais["detalhe_solucao"], "UTF-8")) ? utf8_decode($obs_valores_adicionais["detalhe_solucao"]) : $obs_valores_adicionais["detalhe_solucao"];
					$_RESULT["produto"]["produto_em_estoque"] = $obs_valores_adicionais["produto_em_estoque"];
				}

				if($login_fabrica == 190){
					$_RESULT["os"]["horimetro"] = $produto_horimetro;
				}

				if($login_fabrica == 153){
					$_RESULT["produto"]["desc_mau_uso"]  = $obs_adicionais;
				}

				if($login_fabrica == 160 or $replica_einhell){
					$_RESULT["produto"]["fora_garantia"] = "$tipo_os";
					$_RESULT["produto"]["versao"] = $type;
				}

				if($login_fabrica == 162){
					$_RESULT['produto']['linha_informatica'] = pg_fetch_result($res, 0, informatica);
					$_RESULT["produto"]["imei"] = $rg_produto;
					$_RESULT["produto"]["partnumber"] = $partnumber;
				}

				if (in_array($login_fabrica, array(142))) {
					$_RESULT["produto"]["entrega_tecnica"] = pg_fetch_result($res, 0, "entrega_tecnica");

					if ($_RESULT["produto"]["entrega_tecnica"] == "t") {
						$produto_valores_adicionais = json_decode(pg_fetch_result($res, 0, "valores_adicionais"));
						$_RESULT["produto"]["deslocamento_km"] = $produto_valores_adicionais->deslocamento_km;
					}
				}

				if (in_array($login_fabrica, array(156))) {
					//$_RESULT["produto"]["void"] = $serie_reoperado;
					$_RESULT["produto"]["sem_ns"] = $embalagem_original;
					$_RESULT["produto"]["produto_reparo_fabrica"] = $produto_reparo_fabrica;
				}

				if (in_array($login_fabrica, array(169,170))) {
					$_RESULT["produto"]["retirado_oficina"] = $produto_reparo_fabrica;
				}

				if(in_array($login_fabrica, array(143,152,180,181,182)) || isset($defeitoConstatadoMultiplo)){
					$_RESULT["produto"]["defeito_constatado"] = "";
					$_RESULT["produto"]["defeito_peca"] = "";

					$sql_defeitos_multiplos = "SELECT defeito_constatado, tempo_reparo, defeito FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_constatado IS NOT NULL";
					$res_defeitos_multiplos = pg_query($con, $sql_defeitos_multiplos);

					if (pg_num_rows($res_defeitos_multiplos) == 0 && $login_fabrica == 158) {
						$sql_defeitos_multiplos = "SELECT defeito_constatado, '' AS tempo_reparo, '' AS defeito FROM tbl_os WHERE os = {$os} AND defeito_constatado IS NOT NULL";
						$res_defeitos_multiplos = pg_query($con, $sql_defeitos_multiplos);
					}

					if(pg_num_rows($res_defeitos_multiplos) > 0){
						$rows = pg_num_rows($res_defeitos_multiplos);

						if (in_array($login_fabrica, array(152,180,181,182))) {
							$tempo_reparo = array();
						}

						for($i = 0; $i < $rows; $i++){
							$arr[] = pg_fetch_result($res_defeitos_multiplos, $i, "defeito_constatado");

							if (in_array($login_fabrica, array(152,180,181,182))) {
								$tempo_reparo[pg_fetch_result($res_defeitos_multiplos, $i, "defeito_constatado")] = pg_fetch_result($res_defeitos_multiplos, $i, "tempo_reparo");
							}

							if (in_array($login_fabrica, array(169,170))) {
								$defeito_peca = pg_fetch_result($res_defeitos_multiplos, $i, "defeito");

								if (!empty($defeito_peca)) {
									$_RESULT["produto"]["defeito_peca"] = $defeito_peca;
								}

							}
						}

						$_RESULT["produto"]["defeitos_constatados_multiplos"] = implode(",", $arr);

						if (in_array($login_fabrica, array(152,180,181,182))) {
							$_RESULT["produto"]["tempo_reparo_defeito"] = $tempo_reparo;
						}
					}else{
						$_RESULT["produto"]["defeitos_constatados_multiplos"] = "";
					}

				}

				if(in_array($login_fabrica, [35,183])){
					$_RESULT["produto"]["solucao"] = $solucao_os;
				}

				if($login_fabrica == 52){
					$_RESULT["produto"]["grupo_defeito_constatado"] = $defeito_constatado_grupo;
					$_RESULT["produto"]["marca"] = $marca;
					$_RESULT["produto"]["solucao"] = $solucao_os;
				}

				if ($login_fabrica == 145) {
					$_RESULT["produto"]["solucao"] = $solucao_os;
				}

				if ($login_fabrica == 138) {

					$_RESULT["os"]["solucao"] = "";

					$sql_solucoes_multiplos = "SELECT solucao FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os}";
					$res_solucoes_multiplos = pg_query($con, $sql_solucoes_multiplos);

					if(pg_num_rows($res_solucoes_multiplos) > 0){
						$rows = pg_num_rows($res_solucoes_multiplos);
						for($i = 0; $i < $rows; $i++){
							$arr[] = pg_fetch_result($res_solucoes_multiplos, $i, "solucao");
						}
						$_RESULT["produto"]["solucoes_multiplos"] = implode(",", $arr);
					}else{
						$_RESULT["produto"]["solucoes_multiplos"] = "";
					}

					$sql_adicionais = "SELECT os  FROM tbl_os_campo_extra WHERE os = {$os} AND valores_adicionais ~* 'Garantia Compressor'";
					$res_adicionais = pg_query($con,$sql_adicionais);

					if(pg_num_rows($res_adicionais) > 0){
						$_RESULT["produto"]["troca_compressor"] = "t";
					}
				}

				if(in_array($login_fabrica, [148,191])){
					$_RESULT["os"]["solucao"] = "";

					$sql_solucoes_multiplos = "SELECT solucao FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao IS NOT NULL";
					$res_solucoes_multiplos = pg_query($con, $sql_solucoes_multiplos);

					if(pg_num_rows($res_solucoes_multiplos) > 0){
						$rows = pg_num_rows($res_solucoes_multiplos);
						for($i = 0; $i < $rows; $i++){
							$arr2[] = pg_fetch_result($res_solucoes_multiplos, $i, "solucao");
						}
						$_RESULT["produto"]["solucoes_multiplos"] = implode(",", $arr2);
					}else{
						$_RESULT["produto"]["solucoes_multiplos"] = "";
					}
				}

				//inserir brother nesse select  removendo os left JOIN tabela e tabela_item (pegando valor da tbl_os_item.preço)
				if (in_array($login_fabrica, array(148,156,167,177,203))) {

					$sql = "SELECT
								tbl_os_item.os_item,
								tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_os_item.qtde,
								tbl_os_item.admin,
								tbl_os_item.pedido,
								tbl_servico_realizado.troca_de_peca AS troca,
								tbl_servico_realizado.servico_realizado,
								tbl_tabela_item.preco,
								tbl_os_item.parametros_adicionais,
								tbl_peca.parametros_adicionais as peca_pa,
								tbl_os_item.custo_peca,
								tbl_os_item.peca_serie,
								tbl_os_item.peca_serie_trocada,
								tbl_pedido_item.qtde_faturada,
								tbl_os_item.preco AS preco_item
							FROM tbl_os
							INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
							INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
							INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
							LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
							LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
							LEFT JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.tabela AND tbl_tabela.fabrica = {$login_fabrica}
							LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela AND tbl_tabela_item.peca = tbl_peca.peca
							LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
							WHERE tbl_os_item.os_produto = ".pg_fetch_result($res, 0, "os_produto");
				} else {
					if ($login_fabrica == 149) {
						$sql_solucao = "SELECT solucao FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os}";
						$res_solucao = pg_query($con, $sql_solucao);

						if (pg_num_rows($res_solucao) > 0) {
							$_RESULT["produto"]["solucao"] = pg_fetch_result($res_solucao, 0, "solucao");
						}
					}

					if($login_fabrica == 35){
						$campos_promocao_site = " tbl_peca.promocao_site, ";

						$campos_cadence = " tbl_servico_realizado.peca_estoque,  ";

					}

					$sql = "SELECT
								tbl_os_item.os_item,
								tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao,
								$campos_promocao_site
								$campos_cadence
								tbl_os_item.qtde,
								tbl_os_item.causa_defeito,
								tbl_os_item.admin,
								tbl_os_item.os_por_defeito,
								tbl_os_item.pedido,
								tbl_os_item.peca_serie,
								tbl_os_item.peca_serie_trocada,
								tbl_os_item.porcentagem_garantia,
								tbl_os_item.defeito,
								tbl_servico_realizado.troca_de_peca AS troca,
								tbl_servico_realizado.servico_realizado,
								tbl_os_item.parametros_adicionais,
								tbl_peca.parametros_adicionais as peca_pa,
								tbl_os_item.preco,
								tbl_os_item.custo_peca,
								tbl_os_item.liberacao_pedido,
								tbl_pedido_item.qtde_faturada
							FROM tbl_os_item
							JOIN tbl_os_produto using(os_produto)
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
							LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
							LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
							WHERE tbl_os_produto.os = $os
							AND tbl_os_produto.produto = ".$_RESULT['produto']['id'];
				}

				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$rows = pg_num_rows($res);

					$_RESULT["produto_pecas"] = array();

					for ($i = 0; $i < $rows; $i++) {
						$_RESULT["produto_pecas"][$i] = array(
							"os_item"           => pg_fetch_result($res, $i, "os_item"),
							"id"                => pg_fetch_result($res, $i, "peca"),
							"referencia"        => pg_fetch_result($res, $i, "referencia"),
							"descricao"         => pg_fetch_result($res, $i, "descricao"),
							"qtde"              => pg_fetch_result($res, $i, "qtde"),
							"qtde_faturada"     => pg_fetch_result($res, $i, "qtde_faturada"),
							"obs" 		    => pg_fetch_result($res, $i, "obs"),
							"valor"             => number_format(pg_fetch_result($res, $i, "preco"), 2, ",", ""),
							"valor_total"       => number_format(pg_fetch_result($res, $i, "custo_peca"), 2, ",", ""),
							"defeito_peca"      => pg_fetch_result($res, $i, "defeito"),		
							"troca"             => pg_fetch_result($res, $i, "troca"),
							"servico_realizado" => pg_fetch_result($res, $i, "servico_realizado"),
							"parametros_adicionais" => pg_fetch_result($res, $i, "parametros_adicionais"),
							"peca_pa          " => pg_fetch_result($res, $i, "peca_pa"),
							"pedido"            => pg_fetch_result($res, $i, "pedido"),
							"admin"             => pg_fetch_result($res, $i, "admin"),
							"liberacao_pedido"  => pg_fetch_result($res, $i, "liberacao_pedido")
						);

						if($login_fabrica == 35){
							$_RESULT["produto_pecas"][$i]['po_pecas_hidden'] =  pg_fetch_result($res, $i, "promocao_site");
							$parametros_adicionais_po = json_decode($_RESULT["produto_pecas"][$i]['parametros_adicionais'], true);

							$_RESULT["produto_pecas"][$i]['po_pecas'] = $parametros_adicionais_po['po_pecas'];

							$_RESULT["produto_pecas"][$i]['peca_estoque'] = pg_fetch_result($res, $i, "peca_estoque");
						}

						if (in_array($login_fabrica, [148])) {

							$json_adicionais_peca = json_decode($_RESULT["produto_pecas"][$i]['parametros_adicionais'], true);

							$_RESULT["produto_pecas"][$i]['nf_estoque_fabrica'] = $json_adicionais_peca["nf_estoque_fabrica"];

						}

						if ($login_fabrica == 183){
							$_RESULT["produto_pecas"][$i]['codigo_utilizacao'] = pg_fetch_result($res, $i, "causa_defeito");
						}

						if ($login_fabrica == 177){
							$_RESULT["produto_pecas"][$i]["lote"] = pg_fetch_result($res, $i, "peca_serie_trocada");
							$peca_pa = json_decode(pg_fetch_result($res,$i,'peca_pa'),true);

							if(empty($_RESULT["produto_pecas"][$i]["lote"]) and !empty($peca_pa['lote'])) {
								$_RESULT["produto_pecas"][$i]["peca_lote"] = $peca_pa['lote'];
							}
						}

						if (in_array($login_fabrica, array(175))){
							$_RESULT["produto_pecas"][$i]["quantidade_disparos"] = pg_fetch_result($res, $i, "porcentagem_garantia");
							$_RESULT["produto_pecas"][$i]["numero_serie"] = pg_fetch_result($res, $i, "peca_serie");
							$_RESULT["produto_pecas"][$i]["componente_raiz"] = pg_fetch_result($res, $i, "os_por_defeito");
						}

						if (in_array($login_fabrica, array(148,151,169,170))) {
							$_RESULT["produto_pecas"][$i]["referencia"] .= " - ".$_RESULT["produto_pecas"][$i]["descricao"];
						}

						if(in_array($login_fabrica, array(167,203))){ //HD-3428328
							$_RESULT["produto_pecas"][$i]["serie_peca"] = pg_fetch_result($res, $i, "peca_serie");
						}

						if (in_array($login_fabrica, array(156))) {
							#$_RESULT["produto_pecas"][$i]["void"] = json_decode(pg_fetch_result($res, $i, "parametros_adicionais")) ;
							$_RESULT["produto_pecas"][$i]["void"] = pg_fetch_result($res, $i, "parametros_adicionais") ;
						}

						if (in_array($login_fabrica,array(148,156,161,177))) {
							$_RESULT["produto_pecas"][$i]["valor_total"] = number_format(pg_fetch_result($res, $i, "custo_peca"), 2, ".", "");
							$_RESULT["produto_pecas"][$i]["valor"] = number_format(pg_fetch_result($res, $i, "preco"), 2, ".", "");
						}

						if (in_array($login_fabrica,array(186,190,191,195))) {
							$_RESULT["produto_pecas"][$i]["valor_total"] = number_format(pg_fetch_result($res, $i, "preco")*pg_fetch_result($res, $i, "qtde"), 2, ".", "");
							$_RESULT["produto_pecas"][$i]["valor"] = number_format(pg_fetch_result($res, $i, "preco"), 2, ".", "");
						}

						if($aplicativo == true){
							unset($_RESULT["produto_pecas"][$i]["defeito_peca"]);
						}

						if(in_array($login_fabrica, [167,177,203])){
							$_RESULT["produto_pecas"][$i]["valor"] = number_format(pg_fetch_result($res, $i, "preco_item"), 2, ".", "");
						}
					}
				}

				if($login_fabrica == 52){

					if(strlen($cliente_admin) > 0){

						$sql_cliente_admin = "SELECT
												nome,
												cnpj,
												cep,
												estado,
												cidade,
												bairro,
												endereco,
												numero,
												complemento,
												fone
											FROM tbl_cliente_admin
											WHERE
												cliente_admin = {$cliente_admin}
												AND fabrica = {$login_fabrica}";
						$res_cliente_admin = pg_query($con, $sql_cliente_admin);

						if(pg_num_rows($res_cliente_admin) > 0){

							$_RESULT["revenda"] = array(
								'nome'        => pg_fetch_result($res_cliente_admin, 0, 'nome'),
								'cnpj'        => pg_fetch_result($res_cliente_admin, 0, 'cnpj'),
								'cep'         => pg_fetch_result($res_cliente_admin, 0, 'cep'),
								'estado'      => pg_fetch_result($res_cliente_admin, 0, 'estado'),
								'cidade'      => pg_fetch_result($res_cliente_admin, 0, 'cidade'),
								'bairro'      => pg_fetch_result($res_cliente_admin, 0, 'bairro'),
								'endereco'    => pg_fetch_result($res_cliente_admin, 0, 'endereco'),
								'numero'      => pg_fetch_result($res_cliente_admin, 0, 'numero'),
								'complemento' => pg_fetch_result($res_cliente_admin, 0, 'complemento'),
								'telefone'    => pg_fetch_result($res_cliente_admin, 0, 'fone')
							);
						}
					}
				}
			}
		} else {
			$_RESULT["os"]["os_numero"]      = $os;
			$_RESULT["os"]["qtde_km"]        = 0;
			$_RESULT["os"]["qtde_km_hidden"] = 0;
			unset($os);
		}

		if($login_fabrica == 52){

			/* Pré-OS Fricon */
			if(strlen($hd_chamado) > 0){

				if(strlen($_RESULT["produto"]["os_produto"]) == 0){

					$sql = "UPDATE tbl_os SET marca = (SELECT marca FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}) WHERE os = {$os}";
					$res = pg_query($con, $sql);

					if(strlen(pg_last_error()) > 0){
						throw new Exception("Erro ao cadastrar a marca da Pré-OS");
					}

					$sql = "INSERT INTO tbl_os_produto
					(
						os,
						produto,
						serie
					)
					SELECT
						os,
						produto,
						serie
					FROM tbl_os
					WHERE os = {$os}";
					$res = pg_query($con, $sql);

					if(strlen(pg_last_error()) > 0){
						throw new Exception("Erro ao cadastrar o produto da Pré-OS");
					}

					$sql = "SELECT
								tbl_os_produto.os_produto,
								tbl_produto.produto,
								tbl_produto.referencia,
								tbl_produto.descricao,
								tbl_produto.voltagem,
								tbl_os_produto.serie,
								tbl_produto.entrega_tecnica,
								tbl_produto.valores_adicionais,
								tbl_produto.familia,
								tbl_os.marca,
								tbl_defeito_constatado.defeito_constatado
							FROM tbl_os_produto
							INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
							INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
							LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
							LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os_produto.os
							WHERE tbl_os_produto.os = {$os}
							ORDER BY tbl_os_produto.os_produto ASC LIMIT 1";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$_RESULT["produto"] = array(
							"os_produto"               => pg_fetch_result($res, 0, "os_produto"),
							"id"                       => pg_fetch_result($res, 0, "produto"),
							"referencia"               => pg_fetch_result($res, 0, "referencia"),
							"descricao"                => pg_fetch_result($res, 0, "descricao"),
							"voltagem"                 => pg_fetch_result($res, 0, "voltagem"),
							"serie"                    => pg_fetch_result($res, 0, "serie"),
							"defeito_constatado"       => pg_fetch_result($res, 0, "defeito_constatado"),
							"familia"                  => pg_fetch_result($res, 0, "familia"),
							"troca_produto"            => $justificativa_adicionais,
							"defeito_constatado_grupo" => $defeito_constatado_grupo,
							"marca"                    => pg_fetch_result($res, 0, "marca")
						);

					}

				}

			}

			if(strlen($info_tecnico) > 0){

				list($nome, $rg) = explode("|", $info_tecnico);

				$_RESULT["os"]["nome_tecnico"] = $nome;
				$_RESULT["os"]["rg_tecnico"]   = $rg;

			}

		}

		/**
		 * Verifica o uso de subproduto
		 */
		if (isset($fabrica_usa_subproduto)) {
			$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = {$os}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 2) {
				$sql = "SELECT
							tbl_os_produto.os_produto,
							tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_produto.voltagem,
							tbl_os_produto.serie,
							tbl_defeito_constatado.defeito_constatado
						FROM tbl_os_produto
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
						LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
						WHERE tbl_os_produto.os = {$os}
						ORDER BY tbl_os_produto.os_produto DESC LIMIT 1";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$_RESULT["subproduto"] = array(
						"os_produto"         => pg_fetch_result($res, 0, "os_produto"),
						"id"                 => pg_fetch_result($res, 0, "produto"),
						"referencia"         => pg_fetch_result($res, 0, "referencia"),
						"descricao"          => pg_fetch_result($res, 0, "descricao"),
						"voltagem"           => pg_fetch_result($res, 0, "voltagem"),
						"serie"              => pg_fetch_result($res, 0, "serie"),
						"defeito_constatado" => pg_fetch_result($res, 0, "defeito_constatado")
					);

					$sql = "SELECT
								tbl_os_item.os_item,
								tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_os_item.qtde,
								tbl_os_item.defeito,
								tbl_servico_realizado.troca_de_peca AS troca,
								tbl_servico_realizado.servico_realizado
							FROM tbl_os_item
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
							INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
							WHERE tbl_os_item.os_produto = ".pg_fetch_result($res, 0, "os_produto");
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$rows = pg_num_rows($res);

						$_RESULT["subproduto_pecas"] = array();

						for ($i = 0; $i < $rows; $i++) {
							$_RESULT["subproduto_pecas"][$i] = array(
								"os_item"           => pg_fetch_result($res, $i, "os_item"),
								"id"                => pg_fetch_result($res, $i, "peca"),
								"referencia"        => pg_fetch_result($res, $i, "referencia"),
								"descricao"         => pg_fetch_result($res, $i, "descricao"),
								"qtde"              => pg_fetch_result($res, $i, "qtde"),
								"defeito_peca"      => pg_fetch_result($res, $i, "defeito"),
								"troca"             => pg_fetch_result($res, $i, "troca"),
								"servico_realizado" => pg_fetch_result($res, $i, "servico_realizado")
							);
						}
					}
				}
			}
		}
	} else {
		$msg_erro["msg"][] = "Ordem de Serviço não encontrada";
		$erro_carrega_os = true;
	}
}




if (getValue("os[consumidor_revenda]") == "R") {
	$os_revenda = true;

	$regras["consumidor|nome"]["obrigatorio"]     = false;
	$regras["consumidor|cpf"]["obrigatorio"]      = false;
	$regras["consumidor|cep"]["obrigatorio"]      = false;
	$regras["consumidor|estado"]["obrigatorio"]   = false;
	$regras["consumidor|cidade"]["obrigatorio"]   = false;
	$regras["consumidor|bairro"]["obrigatorio"]   = false;
	$regras["consumidor|endereco"]["obrigatorio"] = false;
	$regras["consumidor|numero"]["obrigatorio"]   = false;
	$regras["consumidor|telefone"]["obrigatorio"] = false;
	$regras["consumidor|celular"]["obrigatorio"]  = false;
	$regras["consumidor|email"]["obrigatorio"]    = false;
} else {
	$os_revenda = false;
}

/**
	Para popular o $_POST que vem da integrãção do aplicativo; 
*/
if($aplicativo == true){
	$_POST = array_replace_recursive($_RESULT, $camposApp); 
	$cep = substr($_POST['consumidor']['cep'], 0, 5). "-". substr($_POST['consumidor']['cep'], -3);
	$_POST['consumidor']['cep'] = $cep;
}
/**
 * FAVOR NÃO COLOCAR MAIS IF DE $login_fabrica NA OPERAÇÃO DE GRAVAR ORDEM DE SERVIÇO
 * UTILIZAR MÉTODOS EXISTENTES OU CRIAR NOVOS MÉTODOS QUE POSSAM SER ÚTEIS PAR TODAS AS FÁBRICAS
 */

if ($_POST['gravar'] == "Gravar") {

	$campos = array(
		'posto'         => $_POST['posto'],
		'os'            => $_POST['os'],
		'consumidor'    => $_POST['consumidor'],
		'revenda'       => $_POST['revenda'],
		'produto'       => $_POST['produto'],
		'produto_pecas' => $_POST['produto_pecas'],
		'anexo'         => $_POST['anexo'],
		'anexo_s3'      => $_POST['anexo_s3'],
		'anexo_chave'   => $_POST['anexo_chave']
	);

	if ($login_fabrica == 131) {
		$campos['estoque_aguardar'] = $_POST['estoque_aguardar'];
		$campos['previsao_estoque'] = $_POST['previsao_entrega'];
	}

	$pecas_antes = array( //hd_chamado=3059371
		'pecas_antes' => $_POST['pecas_antes'],
	);

	if (isset($anexo_peca_os) && $login_fabrica != 35) {
		$campos['anexo_peca']    = $_POST['anexo_peca'];
		$campos['anexo_peca_s3'] = $_POST['anexo_peca_s3'];
	}
	
	if (isset($_POST['anexo_termo_'])) {
		$campos['anexo_termo_'] = $_POST['anexo_termo_'];
	}

	if ($login_fabrica == 158) {
		$campos['produto']['solucao_lancada'] = $_POST["solucao_lancada"];
		$campos['produto']['pdv_chegada']      = $_POST["pdv_chegada"];
		$campos['produto']['pdv_saida'] = $_POST["pdv_saida"];
	}

	if (in_array($login_fabrica,[35])) {
		foreach ($campos["anexo"] as $key => $value) {
	        if (empty($value) || ($campos["anexo_s3"][$key] == t)) {
	            unset($campos["anexo"][$key]);
	        }
    	}
	}

	if (isset($fabrica_usa_subproduto)) {
		$campos["subproduto"]       = $_POST["subproduto"];
		$campos["subproduto_pecas"] = $_POST["subproduto_pecas"];
	}

	if($areaAdmin === true){
		$cortesia = $campos["os"]["cortesia"];

		if ($cortesia != "t") {
			$campos['os']['cortesia'] = "f";
		}
	}else{
		if (in_array($login_fabrica, array(169,170))){
			$cortesia = $campos["os"]["cortesia"];
			if ($cortesia != "t") {
				$campos['os']['cortesia'] = "f";
			}
		}else if($login_fabrica != 178){
			$campos['os']['cortesia'] = "f";
		}
	}

	$campos = array_map_recursive("trim", $campos);
 

	try {
		if (isset($antes_valida_campos) && function_exists($antes_valida_campos)) {
			call_user_func($antes_valida_campos);
		}

		/**
	 	* Validação os campos do formulário
	 	*/
		valida_campos();
		
		/**
		* Validação Peça Obrigatoria Defeito Constatado
		**/
		if (isset($defeito_constatado_obriga_lancar_peca) AND $defeito_constatado_obriga_lancar_peca == true AND strlen($valida_lancar_peca_obrigatorio) > 0) {
			call_user_func($valida_lancar_peca_obrigatorio);
		}

		/**
	 	* Verifica se tem valor na tabela peca_defeito_garantia para a mondial
		 */

		if($login_fabrica == 151){
			$peca =$campos['produto_pecas'][0]['id'];
			$defeito = $campos['produto_pecas'][0]['defeito_peca'];

			$sql_consulta = "SELECT *
							FROM tbl_peca_defeito_garantia 
							WHERE tbl_peca_defeito_garantia.peca = $peca
							AND tbl_peca_defeito_garantia.defeito = $defeito";

			$res_consulta = pg_query($con, $sql_consulta);
			if(pg_num_rows($res_consulta) > 0){
				$grava_os_item = false;
			}
		}
		
		/**
	 	* Validação de garantia
		 */
		if ($cortesia != "t" && strlen(trim($valida_garantia)) > 0) {
			$valida_garantia();
		}
		
		/**
		* Validação de lista básica
		*/
		if ($grava_os_item == true) {
			$valida_pecas();
			if(($cortesia != 't' or $areaAdmin === false) && !empty($valida_garantia_item)) {
				$valida_garantia_item();
			}
		}

		$posto_interno_nao_valida_anexo = \Posvenda\Regras::get("posto_interno_nao_valida_anexo", "ordem_de_servico", $login_fabrica);

		if($posto_interno_nao_valida_anexo == true){
			$sql = "SELECT tbl_posto_fabrica.tipo_posto from tbl_tipo_posto
				JOIN tbl_posto_fabrica on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
				WHERE   tbl_posto_fabrica.posto = {$campos["posto"]['id']}
				AND 	tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND     tbl_tipo_posto.posto_interno is not true ";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) == 0 ){
				unset($valida_anexo);
			}
		}

		if (isset($verifica_peca_estoque)) {
    			$verifica_peca_estoque();
  		}

		if (!empty($valida_anexo) && function_exists($valida_anexo)) {
			$valida_anexo();
		}
		
		if (!empty($valida_anexo_boxuploader) && function_exists($valida_anexo_boxuploader)) {
			$valida_anexo_boxuploader();
		}

		/**
	 	* Valida se existem peças que precisam de anexo e se foi anexado
	 	*/
		if (isset($anexo_peca_os) && !empty($valida_anexo_peca) && function_exists($valida_anexo_peca) && $login_fabrica != 35 && $login_fabrica != 157) {
			$valida_anexo_peca();
		}

		if (isset($fabrica_usa_subproduto) && strlen($campos["subproduto"]["id"]) > 0) {
			if ($grava_os_item == true) {
				$valida_pecas("subproduto_pecas");
			}
		}
	} catch(Exception $e) {
		$msg_erro["msg"][] = $e->getMessage();
	}


	if (!count($msg_erro["msg"])) {
		$nova_os = true;
		$nova_os_id = null;

		if (!empty($os)) $nova_os = false;

		try {

			/*if (!strlen($os)) {
				$gravando = true;
				$auditorLog = new AuditorLog('insert');
			} else {
				$auditorLog = new AuditorLog();
    			$auditorLog->retornaDadosSelect("select * from tbl_os join tbl_os_produto using(os) join tbl_os_item using(os_produto) where os = {$os}");
			}*/

			pg_query($con, "BEGIN");

			/**
			 * Executa funções especificas de cada fabrica
			 */

			if (isset($pre_funcoes_fabrica) && !empty($pre_funcoes_fabrica) && is_array($pre_funcoes_fabrica)) {
				foreach ($pre_funcoes_fabrica as $funcao) {
					if (function_exists($funcao)) {
						call_user_func($funcao);
					}
				}
			}

			if ($login_fabrica == 186 && strlen($os) > 0) {
				$sqlStatusOsAntes = "SELECT status_checkpoint
							          FROM tbl_os 
								     WHERE tbl_os.os = $os
								       AND tbl_os.fabrica = {$login_fabrica}";
				$resStatusOsAntes = pg_query($con,$sqlStatusOsAntes);

				if (pg_num_rows($resStatusOsAntes) > 0 ) {
					$status_da_os_antes = pg_fetch_result($resStatusOsAntes, 0, 'status_checkpoint');
				} else {
					$status_da_os_antes = null;
				}
			}

			/**
			 * Grava a Ordem de Serviço
			 */
			grava_os();


			$nao_valida_lb = \Posvenda\Regras::get("nao_valida_lb", "ordem_de_servico", $login_fabrica);

			if($nao_valida_lb != true) {
				if (!empty($valida_qtde_lista_basica) && function_exists($valida_qtde_lista_basica)) {
					$valida_qtde_lista_basica();
				}
			}

			if (function_exists('grava_campo_valor_adicional')) {
				grava_campo_valor_adicional();
			}

			$posto_interno_nao_valida = \Posvenda\Regras::get("posto_interno_nao_valida", "ordem_de_servico", $login_fabrica);
			if ($posto_interno_nao_valida == true) {
				if (verifica_tipo_posto("posto_interno", "TRUE") == true) {
					unset($auditorias);
				}
			}

			/**
			 * Só entra em auditoria se tiver peças lançadas na OS
			 */
			$valida_auditoria_apos_lancamento_pecas = \Posvenda\Regras::get("auditoria_apos_lancamento_pecas", "ordem_de_servico", $login_fabrica);

			if ($valida_auditoria_apos_lancamento_pecas && !os_tem_peca()) {

				unset($auditorias);

			}

			/**
			 * Grava multiplos defeitos se a fábrica tem essa opção habilitada
			 */
			if (isset($defeitoConstatadoMultiplo)) {
				if (function_exists($grava_multiplos_defeitos)) {
					call_user_func($grava_multiplos_defeitos);
				}
			}

			/**
			 * Executa funções especificas de cada fabrica
			 */
			if (isset($funcoes_fabrica) && !empty($funcoes_fabrica) && is_array($funcoes_fabrica)) {
				foreach ($funcoes_fabrica as $funcao) {
					if (function_exists($funcao)) {
						call_user_func($funcao);
					}
				}
			}

			/**
			 * Grava campo extra
			 */
			if (isset($grava_os_campo_extra)) {
				if (function_exists($grava_os_campo_extra)) {
					call_user_func($grava_os_campo_extra);
				}
			}

			/**
			 * Auditoria
			 */
			auditoria($auditorias);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao gravar Ordem de Serviço #10");
	    	}

	    	if ($telecontrol_distrib && !in_array($login_fabrica, [147])) {

	    		if (os_em_intervencao($os)) {
	    			$status_checkpoint = 'Em auditoria';
	    		}

	    		if (!os_tem_peca()) {
	    			$status_checkpoint = 'Aguardando Analise';
	    		}

	    		atualiza_status_checkpoint($os, $status_checkpoint);
	    		
	    	} else {
	    		$updStatus = "UPDATE tbl_os SET status_checkpoint = (SELECT fn_os_status_checkpoint_os($os)) WHERE fabrica = {$login_fabrica} AND os = {$os}";
				$qryStatus = pg_query($con, $updStatus);
	    	}

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao atualizar Status #11");
			}

			/**
			 * Move os anexos do bucket temporario para o bucket da Ordem de Serviço
			 */
			if (!empty($grava_anexo) && function_exists($grava_anexo)) {
				$grava_anexo();
			}

			if (isset($anexo_peca_os) && !empty($grava_anexo_peca) && function_exists($grava_anexo_peca)) {
				$grava_anexo_peca();
			}
			
			/**
			 * Funções que finaliza um atendimento, depois que uma(s) Pré-OS(s) viram OS(s)
			 */
			if (isset($funcoes_preos_atendimento) && !empty($funcoes_preos_atendimento) && is_array($funcoes_preos_atendimento)) {
				foreach ($funcoes_preos_atendimento as $funcaoPreOsAtendimento) {
					if (function_exists($funcaoPreOsAtendimento)) {
						call_user_func($funcaoPreOsAtendimento);
					}
				}
			}
			
			/**
			 * Executa funções especificas de cada fabrica apos gravação
			 */
			if (isset($pos_funcoes_fabrica) && !empty($pos_funcoes_fabrica) && is_array($pos_funcoes_fabrica)) {
				foreach ($pos_funcoes_fabrica as $funcao) {
					if (function_exists($funcao)) {
						call_user_func($funcao);
					}
				}
			}

			pg_query($con, "COMMIT");

			/*$auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);*/

			if($nova_os) {
				if (isset($funcoes_comunicado) && !empty($funcoes_comunicado) && is_array($funcoes_comunicado)) {
					foreach ($funcoes_comunicado as $funcaoComunicao) {
						if (function_exists($funcaoComunicao)) {
							call_user_func($funcaoComunicao);
						}
					}
				}
			}
	
			if (isset($funcoes_envia_email) && !empty($funcoes_envia_email) && is_array($funcoes_envia_email)) {
				foreach ($funcoes_envia_email as $funcaoEnviaEmail) {
					if (function_exists($funcaoEnviaEmail)) {
						call_user_func($funcaoEnviaEmail);
					}
				}
			}

	
			if (isset($funcoes_gera_os_orcamento) && !empty($funcoes_gera_os_orcamento) && is_array($funcoes_gera_os_orcamento)) {
				foreach ($funcoes_gera_os_orcamento as $funcaoGeraOsOrcamento) {
					if (function_exists($funcaoGeraOsOrcamento)) {
						call_user_func($funcaoGeraOsOrcamento);
					}
				}
			}

			/**
			 * Caso tenha alguma condição que finalize a Ordem na gravação
			 */
			if (!empty($finaliza_os) && function_exists($finaliza_os)) {
				$finaliza_os();
			}
			
			$redirecionamento_os();

		} catch(Exception $e) {
			pg_query($con, "ROLLBACK");
			$msg_erro["msg"][] = $e->getMessage();

			if ($nova_os == true) {
				unset($os);
			}
		}
	}
}

function grava_os() {
	global $con, $login_fabrica, $login_admin, $campos, $os, $fabrica_usa_subproduto,
		   $qtde_pecas, $fabrica_usa_valor_adicional, $grava_os_item, $grava_os_item_function,
		   $areaAdmin, $defeitoReclamadoCadastroDefeitoReclamadoCliente, $usaPostoTecnico, $nova_os, $array_estados;
	/**
	 * Grava tbl_os
	 */
	if (function_exists("grava_os_fabrica")) {
		/**
		 * A função grava_os_fabrica deve ficar dentro do arquivo de regras fábrica
		 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
		 */
		$tbl_os = grava_os_fabrica();
		  
		if (!empty($os) and is_array($tbl_os)) {
			$tbl_os_update = array();

			foreach ($tbl_os as $key => $value) {
				if ($login_fabrica == 171 && $key == "qtde_diaria" && empty($value)) {
					$tbl_os_update[] = "{$key} = null";
				} else {
					$tbl_os_update[] = "{$key} = {$value}";
				}
			}
		}
	}

    $sqlProduto = "
        SELECT  produto
        FROM    tbl_produto
        WHERE   produto = ".$campos['produto']['id']."
        AND     fabrica_i = $login_fabrica
    ";
    $resProduto = pg_query($con,$sqlProduto);

    if (pg_fetch_result($resProduto,0,produto) == "") {
        throw new Exception("Erro ao gravar Produto. Não pertence a esta fábrica");
    }

	if (!isset($fabrica_usa_subproduto)) {
		$valor_defeito_constatado = (!strlen($campos['produto']['defeito_constatado'])) ? "null" : $campos['produto']['defeito_constatado'];

		if ($fabrica == 161) {
			$campos['produto']['serie'] = strtoupper($campos['produto']['serie']);
		}

		$column_tbl_os = array(
			"produto" => $campos['produto']['id'],
			"defeito_constatado" => $valor_defeito_constatado,
			"serie" => "'{$campos['produto']['serie']}'"
		);

		if(in_array($login_fabrica,array(161,165,173,174,176,186,191,193)) AND !empty($campos["os"]["defeito_reclamado"])) {
			$column_tbl_os["defeito_reclamado"] = $campos["os"]["defeito_reclamado"];
		}
	}

	if (in_array($login_fabrica, array(184,191,198,200))&& verifica_tipo_posto("posto_interno", "TRUE")) {
		$column_tbl_os["tecnico"]    = $campos['os']['tecnico'];
	}
	$login_admin = (empty($login_admin)) ? "null" : $login_admin;

	if (strlen(preg_replace('/\D/', '', $campos['consumidor']['cpf'])) == 14) {
		$consumidor_revenda = 'R';
	} else {
		$consumidor_revenda = 'C';
	}

	if($login_fabrica != 151){
		$campo_branco = "";
	}

	$campos['os']['observacoes'] = str_replace("\'","'", $campos['os']['observacoes']);

	/***Substr para campo ***/
	$campos['consumidor']['complemento'] = substr($campos['consumidor']['complemento'],0,20);

	if ($login_fabrica == 148 && $campos['consumidor']['estado_ex'] == "EX") {

		$campos['consumidor']['estado'] = $campos['consumidor']['estado_ex'];
		$campos['consumidor']['cidade'] = $campos['consumidor']['cidade_ex'];
	}

	if(in_array($login_fabrica, array(152, 180, 181, 182))){
		$posto_id = $campos['posto']['id'];	
					
	    $pgResource = pg_query($con, "SELECT pais FROM tbl_posto WHERE posto = {$posto_id}"); 	    
	    $pais_posto = pg_fetch_assoc($pgResource)['pais'] ?? 'BR';	    	    
	}

//	if(!in_array($pais_posto, array('AR', 'BR', 'CO', 'PE')) AND in_array($login_fabrica, array(152, 180, 181, 182))){		
//		$consumidor_estado = '';		
		//grava_os_campo_extra_fabrica();		
//	} else {		
		$consumidor_estado 	= $campos['consumidor']['estado'];
		$revenda_estado 	= $campos['revenda']['estado'];
		$revenda_cidade 	= $campos['revenda']['cidade'];
//	}
	//$consumidor_estado = $campos['consumidor']['estado'];	
	$estado_brasil = getEstadosNacional();
	if (!empty($consumidor_estado)) {
		if (!array_key_exists($consumidor_estado, $estado_brasil)) {
			if (in_array($login_fabrica, [152,180,181,182])) {
				if (count($array_estados($pais_posto)) == 0) {
					$consumidor_estado = "EX";
				}
			} else {
				$consumidor_estado = "EX";
			}
		}
	}

	if (empty($os)) {
		$objLog = new AuditorLog('insert');

		if ($login_fabrica == 158) {
			$timezone = new DateTimeZone("America/Sao_Paulo");
			$data_hora_abertura    = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'), $timezone);
			$column_tbl_os["data_hora_abertura"]    = "'".$data_hora_abertura->format('Y-m-d H:i:s')."'";
		}
		
		if ($defeitoReclamadoCadastroDefeitoReclamadoCliente AND !empty($campos["os"]["defeito_reclamado"])){
			$campo_defeito = "defeito_reclamado, ";
			$value_defeito = $campos['os']['defeito_reclamado'].",";
		}
		
		if (in_array($login_fabrica, [184,200]) AND !empty($campos["os"]["defeito_reclamado"])){
			$campo_defeito = "defeito_reclamado, ";
			$value_defeito = $campos['os']['defeito_reclamado'].",";
		}
	
		if (in_array($login_fabrica, [190,198])){
			$campo_defeito = "defeito_reclamado, ";
			$value_defeito = $campos['os']['defeito_reclamado'].",";
		}

		if (!empty($campos["os"]["prateleira_box"])) {
			$campo_prateleira = "prateleira_box, ";
			$value_prateleira = "'".$campos["os"]["prateleira_box"]."', ";
		}

		$sql = "INSERT INTO tbl_os
				(
					fabrica,
					validada,
					posto,
					admin,
					data_abertura,
					tipo_atendimento,
					nota_fiscal,
					data_nf,
					defeito_reclamado_descricao,
					{$campo_defeito}
					aparencia_produto,
					acessorios,
					consumidor_revenda,
					consumidor_nome,
					consumidor_cpf,
					consumidor_cep,
					consumidor_estado,
					consumidor_cidade,
					consumidor_bairro,
					consumidor_endereco,
					consumidor_numero,
					consumidor_complemento,
					consumidor_fone,
					consumidor_celular,
					consumidor_email,
					$campo_prateleira
					revenda,
					revenda_nome,
					revenda_cnpj,
					revenda_fone,
					obs,
					cortesia,
					qtde_km,
					os_posto
					".((isset($column_tbl_os)) ? ", ".implode(", ", array_keys($column_tbl_os)) : "")."
					".((isset($tbl_os)) ? ", ".implode(", ", array_keys($tbl_os)) : "")."
				)
				VALUES
				(
					$login_fabrica,
					current_timestamp,
					{$campos['posto']['id']},
					$login_admin,
					'".formata_data($campos['os']['data_abertura'])."',
					".((empty($campos['os']['tipo_atendimento'])) ? "null" : $campos['os']['tipo_atendimento'] ).",
					'{$campos['os']['nota_fiscal']}',
					".((!empty($campos["os"]["data_compra"])) ? "'".formata_data($campos['os']['data_compra'])."'" : "null").",
					".((in_array($login_fabrica, array(169,170,173,174,176,183,184,190,191,193,198,200)) OR $defeitoReclamadoCadastroDefeitoReclamadoCliente) ? "'".$campos['os']['defeito_reclamado_descricao']."'" : "'".$campos['os']['defeito_reclamado']."'").",
					".$value_defeito."
					'{$campos['os']['aparencia_produto']}',
					".pg_escape_literal($con,$campos['os']['acessorios']).",
					'{$campos['os']['consumidor_revenda']}',
					".pg_escape_literal($con,$campos['consumidor']['nome']).",
					'".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
					'".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
					'{$consumidor_estado}',
					".pg_escape_literal($con,$campos['consumidor']['cidade']).",
					".pg_escape_literal($con,$campos['consumidor']['bairro']).",
					".pg_escape_literal($con,$campos['consumidor']['endereco']).",
					'{$campos['consumidor']['numero']}',
					".pg_escape_literal($con,$campos['consumidor']['complemento']).",
					'{$campos['consumidor']['telefone']}',
					'{$campos['consumidor']['celular']}',
					'{$campos['consumidor']['email']}',
					$value_prateleira
					".verifica_revenda().",
					E'".substr($campos['revenda']['nome'], 0, 50)."',
					'".preg_replace("/[\.\-\/]/", "", $campos['revenda']['cnpj'])."',
					'{$campos['revenda']['telefone']}',
					".pg_escape_literal($con,$campos['os']['observacoes']).",
					'{$campos['os']['cortesia']}',
					".((!strlen($campos['os']['qtde_km'])) ? 0 : $campos['os']['qtde_km']).",
					'".((in_array($login_fabrica,array(35,144,151,157,158))) ? $campos['os']['os_posto'] : $campo_branco)."'
					".((isset($column_tbl_os)) ? ", ".implode(", ", $column_tbl_os) : "")."
					".((isset($tbl_os)) ? ", ".implode(", ", $tbl_os) : "")."
				)
				RETURNING os";

		$acao = "insert";//auditorLog
 	} else {
		$nova_os = false;
		
		$objLog = new AuditorLog();
		$objLog->retornaDadosSelect("SELECT data_abertura,tipo_atendimento,nota_fiscal,data_nf,aparencia_produto,acessorios,tecnico,tipo_os,os_reincidente,cliente,consumidor_nome,consumidor_cpf,consumidor_cep,consumidor_estado,consumidor_cidade,consumidor_bairro,consumidor_endereco,consumidor_numero,consumidor_complemento,consumidor_fone,consumidor_celular,consumidor_email,status_checkpoint,serie,produto,revenda,defeito_reclamado,defeito_constatado,justificativa_adicionais,obs FROM tbl_os WHERE os={$os} AND fabrica = {$login_fabrica}");

		if (isset($column_tbl_os)) {
			$column_tbl_os_update = array();

			foreach ($column_tbl_os as $key => $value) {
				$column_tbl_os_update[] = "{$key} = {$value}";
			}
		}
		if (!in_array($login_fabrica, array(169,170))) {
			$campoOsPosto = ", os_posto = '".((in_array($login_fabrica,array(35,144,151,157,158))) ? $campos['os']['os_posto'] : $campo_branco)."'";
		}

		if ($defeitoReclamadoCadastroDefeitoReclamadoCliente AND !empty($campos["os"]["defeito_reclamado"])){
			$update_defeito = "defeito_reclamado = ".$campos['os']['defeito_reclamado'].",";
		}

		if (in_array($login_fabrica, array(183,184,200)) AND !empty($campos["os"]["defeito_reclamado"])){
			$update_defeito = "defeito_reclamado = ".$campos['os']['defeito_reclamado'].",";
		}

		if (!empty($campos["os"]["prateleira_box"])) {
			$update_prateleira = "prateleira_box = "."'".$campos["os"]["prateleira_box"]."',";
		}

		if (in_array($login_fabrica, [190,198])){
			$update_defeito = "defeito_reclamado = ".$campos['os']['defeito_reclamado'].",";
		}

		$campo_cortesia = (!empty($campos["os"]["cortesia"])) ? " cortesia = '".$campos["os"]["cortesia"]."', " : "";

		$sql = "UPDATE tbl_os SET
					data_abertura = '".formata_data($campos['os']['data_abertura'])."',
					tipo_atendimento = ".((empty($campos['os']['tipo_atendimento'])) ? "null" : $campos['os']['tipo_atendimento'] ).",
					nota_fiscal = '{$campos['os']['nota_fiscal']}',
					data_nf = ".((!empty($campos["os"]["data_compra"])) ? "'".formata_data($campos['os']['data_compra'])."'" : "null").",
					defeito_reclamado_descricao = ".((in_array($login_fabrica, array(169,170,173,174,176,183,184,186,190,193,198,200)) OR $defeitoReclamadoCadastroDefeitoReclamadoCliente) ? "E'".$campos['os']['defeito_reclamado_descricao']."'" : "E'".$campos['os']['defeito_reclamado']."'").",
					$update_defeito
					$update_prateleira
					aparencia_produto = '{$campos['os']['aparencia_produto']}',
					acessorios = ".pg_escape_literal($campos['os']['acessorios']).",
					consumidor_nome = ".pg_escape_literal($con,$campos['consumidor']['nome']).",
					consumidor_cpf = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
					consumidor_cep = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
					consumidor_estado = '{$consumidor_estado}',
					consumidor_cidade = ".pg_escape_literal($con,$campos['consumidor']['cidade']).",
					consumidor_bairro = ".pg_escape_literal($con,$campos['consumidor']['bairro']).",
					consumidor_endereco = ".pg_escape_literal($con,$campos['consumidor']['endereco']).",
					consumidor_numero = '{$campos['consumidor']['numero']}',
					consumidor_complemento = ".pg_escape_literal($con,$campos['consumidor']['complemento']).",
					consumidor_fone = '{$campos['consumidor']['telefone']}',
					consumidor_celular = '{$campos['consumidor']['celular']}',
					consumidor_email = '{$campos['consumidor']['email']}',
					consumidor_revenda = '{$campos['os']['consumidor_revenda']}',
					revenda = ".verifica_revenda().",
					revenda_nome = E'".substr($campos['revenda']['nome'], 0, 50)."',
					revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos['revenda']['cnpj'])."',
					revenda_fone = '{$campos['revenda']['telefone']}',
					obs = E'".str_replace("'","\'",$campos['os']['observacoes'])."',
					{$campo_cortesia}
					qtde_km = ".((!strlen($campos['os']['qtde_km'])) ? 0 : $campos['os']['qtde_km'])."
					{$campoOsPosto}
					".((isset($column_tbl_os_update)) ? ", ".implode(", ", $column_tbl_os_update) : "")."
					".((isset($tbl_os_update)) ? ", ".implode(", ", $tbl_os_update) : "")."
				WHERE os = {$os}
				AND fabrica = {$login_fabrica}";
		$acao = "update";//auditorlog
	}
	$res = pg_query($con, $sql);
	
	if ($consumidor_estado == "EX") {
		if ($acao == 'insert') {
			$os = pg_fetch_result($res, 0, "os");
		}

		if (in_array($login_fabrica, [180,181,182])) { 
			$sqlu = "UPDATE tbl_os SET sua_os = $os WHERE os = $os";			
			pg_query($con, $sqlu);
		}

		$get_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os;";		
		$campos_json = pg_query($con, $get_campos_adicionais);

		if (pg_num_rows($campos_json) == 0) {			
			
			$json['estado'] = $campos['consumidor']['estado'];

			$json = json_encode($json);

			$add_json = "INSERT INTO tbl_os_campo_extra 
						  (os, fabrica, campos_adicionais) 
					     VALUES 	
						  ('{$os}', '{$login_fabrica}', '{$json}')";

		} else { 

			$campos_json = pg_fetch_result($re, 0, campos_adicionais);
			
			$campos_json = json_decode($campos_json, true);

			$campos_json['estado'] = $campos['consumidor']['estado'];
			
			$campos_json = json_encode($campos_json);

			$add_json = " UPDATE tbl_os_campo_extra 
						  SET campos_adicionais = '{$campos_json}'
						  WHERE os = $os";
		}		
		$res_update = pg_query($con, $add_json);
	}
	if (in_array($login_fabrica, [35]) && $acao == 'insert') {
		$num_os = pg_fetch_result($res, 0, "os");
		$updateTdocs = "UPDATE tbl_tdocs SET referencia_id = {$num_os} WHERE fabrica = {$login_fabrica} AND hash_temp = '{$campos['anexo_chave']}' ";
		pg_query($con, $updateTdocs);
	}
	if (is_object($objLog)) {
		if ($acao == "insert") {//logos
			$os_log = pg_fetch_result($res, 0, "os");
			$os_log = !empty($os) ? $os : pg_fetch_result($res, 0, "os");
			
			if(!empty($os_log)) {
				$objLog->retornaDadosSelect("SELECT data_abertura,tipo_atendimento,nota_fiscal,data_nf,aparencia_produto,acessorios,tecnico,tipo_os,os_reincidente,cliente,consumidor_nome,consumidor_cpf,consumidor_cep,consumidor_estado,consumidor_cidade,consumidor_bairro,consumidor_endereco,consumidor_numero,consumidor_complemento,consumidor_fone,consumidor_celular,consumidor_email,status_checkpoint,serie,produto,revenda,defeito_reclamado,defeito_constatado,justificativa_adicionais,obs FROM tbl_os WHERE os={$os_log} AND fabrica = {$login_fabrica}")->enviarLog($acao, "tbl_os", $login_fabrica."*".$os_log);
			}
		} else {
			$objLog->retornaDadosSelect()->enviarLog($acao, "tbl_os", $login_fabrica."*".$os);
		}
	}

	unset($objLog);

	if (strlen(pg_last_error()) > 0) {		
		throw new Exception("Erro ao gravar Ordem de Serviço #1 ".pg_last_error());
	} else if (empty($os)) {

		$os = pg_fetch_result($res, 0, "os");

		$sql = "UPDATE tbl_os SET sua_os = '{$os}', validada = CURRENT_TIMESTAMP WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar Ordem de Serviço #2");
		}

		if (strlen($campos["os"]["hd_chamado"]) > 0) {
			$hd_chamado = $campos["os"]["hd_chamado"];
			$hd_chamado_item = $campos["os"]["hd_chamado_item"];

			$sql = "UPDATE tbl_os SET hd_chamado = {$hd_chamado} WHERE fabrica = {$login_fabrica} AND os = {$os}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar Ordem de Serviço #3");
			}

			if (in_array($login_fabrica, [151,186]) and !empty($hd_chamado_item)) {
				$sql = "UPDATE tbl_hd_chamado_item SET os = {$os} WHERE hd_chamado = {$hd_chamado} AND hd_chamado_item = {$hd_chamado_item} and produto notnull ";
				$res = pg_query($con, $sql);
			} else if (in_array($login_fabrica, array(169,170))) {
				if (empty($campos["os"]["os_numero"])) {
					$sql = "UPDATE tbl_hd_chamado_extra SET os = {$os} WHERE hd_chamado = {$hd_chamado}";
					$res = pg_query($con, $sql);
				}
			} else {
				$sql = "UPDATE tbl_hd_chamado_extra SET os = {$os} WHERE hd_chamado = {$hd_chamado}";
				$res = pg_query($con, $sql);
			}

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar Ordem de Serviço #4");
			}

			$sql = "INSERT INTO tbl_hd_chamado_item
					(hd_chamado, interno, comentario)
					VALUES
					({$hd_chamado}, TRUE, 'Foi aberta uma Ordem de Serviço para o Atendimento. OS: {$os}')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar Ordem de Serviço #5");
			}
		}
	}else{
		if(in_array($login_fabrica, array(178)) AND !empty($campos["os"]["cortesia"])){
				
					$sql = "SELECT os_revenda FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
					$res = pg_query($con, $sql);

					$os_revenda = pg_fetch_result($res,0,"os_revenda");

					$sql = "UPDATE tbl_os SET cortesia = '".$campos["os"]["cortesia"]."' FROM tbl_os_campo_extra WHERE tbl_os.os = tbl_os_campo_extra.os AND tbl_os_campo_extra.os_revenda = {$os_revenda}";
					$res = pg_query($con, $sql);

		}
	}
	if (verifica_tipo_posto("posto_interno", "TRUE") && $login_fabrica == 156) {

		$tipo_atendimento = $campos['os']['tipo_atendimento'];
		if ($tipo_atendimento == 247) {
			$tipo_atendimento = 262;
        }
		$sql = "UPDATE tbl_os SET tipo_atendimento={$tipo_atendimento} WHERE fabrica = {$login_fabrica} AND os_numero = {$os}";
		$res = pg_query($con, $sql);

		$xTipoAtendimento = '';
		$sqlTipoAtendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE tbl_tipo_atendimento.fabrica={$login_fabrica} AND tbl_tipo_atendimento.tipo_atendimento={$tipo_atendimento}";
		$resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

		if (pg_num_rows($resTipoAtendimento) > 0) {
			$xTipoAtendimento = pg_fetch_result($resTipoAtendimento, 0, 'descricao');
		}

		$sql_os = "SELECT tbl_os.os, tbl_os.posto
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				JOIN tbl_posto_tipo_posto ON tbl_posto_tipo_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_tipo_posto.fabrica = {$login_fabrica}
				JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
				AND tbl_tipo_posto.posto_interno IS NOT TRUE
				WHERE tbl_os.os_numero = {$os}";
		$res_os = pg_query($con, $sql_os);

		if (pg_num_rows($res_os) > 0) {
			$posto = pg_fetch_result($res_os, 0, 'posto');
			$os_posto = pg_fetch_result($res_os, 0, 'os');
			$msg = "Foi atualizado o Tipo de Atendimento  da Ordem de Serviço {$os_posto}, para {$xTipoAtendimento}";

			$sql = "INSERT INTO tbl_comunicado (mensagem, tipo, fabrica, posto,obrigatorio_site, ativo)
							VALUES ('{$msg}','Comunicado',{$login_fabrica},{$posto},TRUE, TRUE)";
			$res = pg_query($con, $sql);
		}

	}


	/**
	 * Grava tbl_os_extra
	 */
	if (function_exists("grava_os_extra_fabrica")) {
		/**
		 * A função grava_os_extra_fabrica deve ficar dentro do arquivo de regras fábrica
		 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
		 */
		$tbl_os_extra = grava_os_extra_fabrica();

		if (!empty($os) && is_array($tbl_os_extra)) {
			$tbl_os_extra_update = array();

			foreach ($tbl_os_extra as $key => $value) {
				$tbl_os_extra_update[] = "{$key} = {$value}";
			}
		} else {
			unset($tbl_os_extra);
		}
	}

	$sql = "SELECT os FROM tbl_os_extra WHERE os = {$os}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		$sql = "INSERT INTO tbl_os_extra
				(
					os
					".((isset($tbl_os_extra)) ? ", ".implode(", ", array_keys($tbl_os_extra)) : "")."
				)
				VALUES
				(
					{$os}
					".((isset($tbl_os_extra)) ? ", ".implode(", ", $tbl_os_extra) : "")."
				)";
	} else if (isset($tbl_os_extra_update)) {
		$sql = "UPDATE tbl_os_extra SET
					".implode(", ", $tbl_os_extra_update)."
				WHERE os = {$os}";
	}

	$res = pg_query($con, $sql);
	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar Ordem de Serviço #6 ");
	}

	/**
	 * Grava tbl_os_produto
	 */
	if (function_exists("grava_os_produto_fabrica")) {
		/**
		 * A função grava_os_produto_fabrica deve ficar dentro do arquivo de regras fábrica
		 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
		 */
		$tbl_os_produto = grava_os_produto_fabrica();

		if (!empty($os)) {
			$tbl_os_produto_update = array();

			foreach ($tbl_os_produto as $key => $value) {
				if($key !='produto') {
					$tbl_os_produto_update[] = "{$key} = {$value}";
				}
			}
		}
	}

	if (!isset($fabrica_usa_subproduto)) {
		$sqlOsProduto = "
			SELECT os_produto
			FROM tbl_os_produto
			WHERE os = {$os}
		";
		$resOsProduto = pg_query($con, $sqlOsProduto);

		if (pg_num_rows($resOsProduto) > 0) {
			$campos["produto"]["os_produto"] = pg_fetch_result($resOsProduto, 0, "os_produto");
		}
	}

	if ($usaProdutoGenerico) {
		$sql = "SELECT
					os_produto
				FROM tbl_os_produto INNER JOIN tbl_produto USING(produto)
				WHERE fabrica_i = {$login_fabrica} AND produto_principal IS NOT TRUE";
	}

	if ($fabrica == 161) {
		$campos['produto']['serie'] = strtoupper($campos['produto']['serie']);
	}

	if (empty($campos["produto"]["os_produto"])) {
		if (verifica_tipo_posto("posto_interno", "TRUE") && $login_fabrica == 165) {//logosproduto
			$objLog = new AuditorLog('insert');
		}
		$campo_defeito_constatado = (!strlen($campos['produto']['defeito_constatado'])) ? "" : ",defeito_constatado";
		$valor_defeito_constatado = (!strlen($campos['produto']['defeito_constatado'])) ? "" : ",".$campos['produto']['defeito_constatado'];

		unset($tbl_os_produto['produto']);
		$sql = "INSERT INTO tbl_os_produto
				(
					os,
					produto,
					serie
					$campo_defeito_constatado
					".((count($tbl_os_produto) > 0) ? ", ".implode(", ", array_keys($tbl_os_produto)) : "")."
				)
				VALUES
				(
					{$os},
					{$campos['produto']['id']},
					'{$campos['produto']['serie']}'
					$valor_defeito_constatado
					".((count($tbl_os_produto) > 0) ? ", ".implode(", ", $tbl_os_produto) : "")."
				)
				RETURNING os_produto";

		$acao = "insert";
	} else {
		$os_produto = $campos["produto"]["os_produto"];
		if (verifica_tipo_posto("posto_interno", "TRUE") && $login_fabrica == 165) {//logosproduto
			$objLog = new AuditorLog();
			$objLog->retornaDadosSelect("SELECT os,produto,serie,defeito_constatado,servico FROM tbl_os_produto WHERE os={$os} AND os_produto={$os_produto}");
		}

		if( $login_fabrica == 177 AND $areaAdmin == false ){
			if( count($tbl_os_produto_update) == 0 ){
				$tbl_os_produto_update[0] = "causa_defeito = null";
			}
		}

		$sql = "UPDATE tbl_os_produto SET
					produto = {$campos['produto']['id']},
					serie = '{$campos['produto']['serie']}',
					defeito_constatado = ".((!strlen($campos['produto']['defeito_constatado'])) ? "null" : $campos['produto']['defeito_constatado'])."
					".((isset($tbl_os_produto_update) and count($tbl_os_produto_update) > 0) ? ", ".implode(", ", $tbl_os_produto_update) : "")."
				WHERE os = {$os}
				AND os_produto = {$os_produto}";
		$acao = "update";
	}
    if (in_array($login_fabrica,array(156,162))) {
        if ($areaAdmin === true) {
            $sqlf = "SELECT os FROM tbl_os WHERE os = $os AND finalizada IS NOT NULL";
            $qry = pg_query($con, $sqlf);

            if (pg_num_rows($qry) > 0) {
                $grava_os_item = false;
            } else {
                $res = pg_query($con, $sql);
            }
        } else {
            $res = pg_query($con, $sql);
        }
    } else {
        $res = pg_query($con, $sql);
    }

	if (strlen(pg_last_error()) > 0) {
		$msg_erro = pg_last_error();
		if(strpos($msg_erro,"finalizada")) {
			$msg_erro = "Os já finalizada, não pode alterar";
		}else{
			$msg_erro = "Erro ao gravar Ordem de Serviço #7";
		}

		throw new Exception($msg_erro);
	} else if (empty($os_produto)) {
		$os_produto = pg_fetch_result($res, 0, "os_produto");
	}

	if (!empty($objLog)) {
		if ($acao == "insert") {//logosproduto
			$objLog->retornaDadosSelect("SELECT os,produto,serie,defeito_constatado,servico FROM tbl_os_produto WHERE os={$os} AND os_produto={$os_produto}")->enviarLog($acao, "tbl_os_produto", $login_fabrica."*".$os);
		} else {
			$objLog->retornaDadosSelect()->enviarLog($acao, "tbl_os_produto", $login_fabrica."*".$os);
		}
	}
	unset($objLog);

	try {
		/**
		 * Chamada da função que irá gravar os itens do produto
		 */

		if($grava_os_item == true){
			call_user_func(	$grava_os_item_function , $os_produto);
			if(strlen(trim($campos["posto"]["id"]))>0 AND strlen(trim($os_produto))>0){
				grava_gera_pedido_os($campos["posto"]["id"], $os_produto);
			}
		}
	} catch(Exception $e) {
		throw new Exception($e->getMessage());
	}

	if (isset($fabrica_usa_subproduto) && strlen($campos["subproduto"]["id"]) > 0) {
		/**
		 * Grava tbl_os_produto
		 */
		if (function_exists("grava_os_subproduto_fabrica")) {
			/**
			 * A função grava_os_produto_fabrica deve ficar dentro do arquivo de regras fábrica
			 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
			 */
			$tbl_os_subproduto = grava_os_subproduto_fabrica();

			if (!empty($os)) {
				$tbl_os_subproduto_update = array();

				foreach ($tbl_os_subproduto as $key => $value) {
					$tbl_os_subproduto_update[] = "{$key} = {$value}";
				}
			}
		}

		if (empty($campos["subproduto"]["os_produto"])) {
			$sql = "INSERT INTO tbl_os_produto
					(
						os,
						produto,
						serie,
						defeito_constatado
						".((isset($tbl_os_subproduto)) ? ", ".implode(", ", array_keys($tbl_os_subproduto)) : "")."
					)
					VALUES
					(
						{$os},
						{$campos['subproduto']['id']},
						'{$campos['subproduto']['serie']}',
						".((!strlen($campos["subproduto"]["defeito_constatado"])) ? "null" : $campos['subproduto']['defeito_constatado'])."
						".((isset($tbl_os_subproduto)) ? ", ".implode(", ", $tbl_os_subproduto) : "")."
					)
					RETURNING os_produto";
		} else {
			$os_subproduto = $campos["subproduto"]["os_produto"];

			$sql = "UPDATE tbl_os_produto SET
						serie = '{$campos['subproduto']['serie']}',
						defeito_constatado = ".((!strlen($campos["subproduto"]["defeito_constatado"])) ? "null" : $campos['subproduto']['defeito_constatado'])."
						".((isset($tbl_os_subproduto_update)) ? ", ".implode(", ", $tbl_os_subproduto_update) : "")."
					WHERE os = {$os}
					AND os_produto = {$os_subproduto}";
		}
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar Ordem de Serviço #8");
		} else if (empty($os_subproduto)) {
			$os_subproduto = pg_fetch_result($res, 0, "os_produto");
		}

		try {
			/**
			 * Chamada da função que irá gravar os itens do subproduto
			 */
			if ($grava_os_item == true) {
				call_user_func(	$grava_os_item_function , $os_subproduto, "subproduto_pecas");
				// grava_os_item($os_subproduto, "subproduto_pecas");
			}
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	if (isset($fabrica_usa_valor_adicional)) {
		if (count($campos["os"]["valor_adicional"]) > 0) {
			foreach ($campos["os"]["valor_adicional"] as $key => $value) {
				if (strpos($value, '|') !== false) {
					list($chave, $valor) = explode("|", $value);
					$valores[$key] = array(utf8_encode(utf8_decode($chave)) => $valor);
				}else{
					$valores[$key] = utf8_encode($value);
				}
			}
			$valores = json_encode($valores);
			$valores = str_replace("\\", "\\\\", $valores);

			grava_valor_adicional($valores, $os);
		} else {

			grava_valor_adicional(null, $os);
		}
	}

	if($fabrica_usa_log or $login_fabrica == 156){
		grava_log();
	}

	if (in_array($login_fabrica, array(35, 52, 131, 156,158,161,162,164,167,171,174,191,195,203))) {
		grava_campo_adicional($os);
	}

	if(!in_array($pais_posto, array('AR', 'BR', 'CO', 'PE')) AND in_array($login_fabrica, array(152, 180, 181, 182))){		
		grava_campo_adicional($os);
	}

	if (in_array($login_fabrica, array(160)) && $acao == "insert") {
		envia_sms();
	}
}

function grava_log($campos_antigos){
    global $con, $os, $login_fabrica, $login_admin, $login_posto, $campos, $areaAdmin, $_RESULT, $pecas_antes;

    $campos_novos = $campos;
    $campos_novos['os']['data_alteracao'] = date("d/m/Y h:i:s");
    $campos_novos['os']['admin_altera'] = $login_admin;
    unset($campos_novos['anexos'],$campos_novos['anexo_s3']);

    $campos_antigos = $_RESULT;
    unset($campos_novos['anexos'],$campos_novos['anexo_s3']);

    $auditor_ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARTDED_FOR'] != '') {
        $auditor_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $auditor_ip = $_SERVER['REMOTE_ADDR'];
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARTDED_FOR'] != '') {
            $auditor_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $auditor_ip = $_SERVER['REMOTE_ADDR'];
        }

        if (strlen ($auditor_ip) == 0) {
            $auditor_ip = "0.0.0.0";
        }

        #$auditor_url_api = "https://audittable.apiary-mock.com/auditor/audit";
        $auditor_url_api = "https://api2.telecontrol.com.br/auditor/auditor";
        // application => md5('os.php'); ==  'da82d339d0552bcfcf10188a36125270'
        $auditor_array_dados = array (
            "application" => "da82d339d0552bcfcf10188a36125270",
            "table" => "tbl_os",
            "ip_access" => "$auditor_ip",
            "owner" => "$login_fabrica",
            "action" => "update",
            "program_url" => "http://www.telecontrol.com.br/os_cadastro_unico/fabricas/os.php",
            "primary_key" => $login_fabrica . "*" . $os,
            "user" => (($areaAdmin===true) ? "$login_admin" : "$login_posto"),
            "user_level" => (($areaAdmin===true) ? "admin" : "posto"),
            "content" => json_encode (array ("antes" => $campos_antigos , "depois" => $campos_novos,"pecas_antes" => $pecas_antes))
        );
        $auditor_json_dados = json_encode ($auditor_array_dados);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auditor_url_api);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $auditor_json_dados);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        $response = curl_exec($ch);
        // var_dump($response);
        curl_close($ch);
        /* ---- AUDITOR FIM ---- */
     }

}

function grava_os_item($os_produto, $subproduto = "produto_pecas") {
	global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

	if (function_exists("grava_custo_peca") ) {
		/**
		 * A função grava_custo_peca deve ficar dentro do arquivo de regras fábrica
		 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
		 */
		$custo_peca = grava_custo_peca();
		if($custo_peca==false){
			unset($custo_peca);
		}
	}

	if($historico_alteracao === true){
		$historico = array();
	}


	if (verifica_tipo_posto("posto_interno", "TRUE") && $login_fabrica == 165) {//logositem
		$objLog = new AuditorLog();
		$objLog->retornaDadosSelect("SELECT peca,qtde,servico_realizado,peca_obrigatoria FROM tbl_os_item WHERE os_produto={$os_produto}");
	}
	
	foreach ($campos[$subproduto] as $posicao => $campos_peca) {
		if (strlen($campos_peca["id"]) > 0) {

			if($historico_alteracao === true){
				include "$login_fabrica/historico_alteracao.php";
			}

			if (isset($campos_peca['servico_realizado']) && strlen($campos_peca['servico_realizado']) > 0) {
				$sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
				$res = pg_query($con, $sql);

				$troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

				if ($troca_de_peca == "t") {
					$sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$campos_peca['id']}";
					$res = pg_query($con, $sql);

					$devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");

					if ($devolucao_obrigatoria == "t") {
						$devolucao_obrigatoria = "TRUE";
					} else {
						$devolucao_obrigatoria = "FALSE";
					}
				} else {
					$devolucao_obrigatoria = "FALSE";
				}

			} else {
				$devolucao_obrigatoria = "FALSE";
			}

			$login_admin = (empty($login_admin)) ? "null" : $login_admin;

			if(empty($custo_peca[$campos_peca['id']])) $custo_peca[$campos_peca['id']] = 0 ;
			if(empty($campos_peca['valor'])) $campos_peca['valor'] = 0 ;

			$campo_obs = '';
			$valor_obs = '';

			if (isset($campos_peca['obs']) && !empty($campos_peca['obs'])) {
				$campo_obs = 'obs,';
				$valor_obs = "'".$campos_peca['obs']."',";
			} else if (isset($campos_peca['causa_defeito']) && !empty($campos_peca['causa_defeito'])) {
				$campo_obs = 'causa_defeito,';
				$valor_obs = $campos_peca['causa_defeito'].",";
			}
			
			if (empty($campos_peca["os_item"])) {
				$campo_serv = '';
				$valor_serv = '';
				if (isset($campos_peca['servico_realizado']) && !empty($campos_peca['servico_realizado'])) {
					$campo_serv = 'servico_realizado,';
					$valor_serv = $campos_peca['servico_realizado'].",";

					if (in_array($login_fabrica, [169,170])) {
						$objLog = new AuditorLog('insert');
					}

				}

				$parametros_adicionais_peca = [];
				if (in_array($login_fabrica, [148]) && !empty($campos_peca["nf_estoque_fabrica"])) {

					$parametros_adicionais_peca = [
						"nf_estoque_fabrica" => $campos_peca["nf_estoque_fabrica"]
					];

					$totalPeca = (float) str_replace(',','.',str_replace(".","",$campos_peca["valor_total"]));

					if ($totalPeca >= 10000) {
						$campos_peca['valor_total'] = 0;
					}

				}

				$sql = "INSERT INTO tbl_os_item (
							os_produto,
							peca,
							qtde,
							peca_obrigatoria,
							{$campo_serv}							
							{$campo_obs}
							admin
							".(in_array($login_fabrica, array(148)) == false && (!empty($custo_peca)) ? ", custo_peca" : "")."
							".((in_array($login_fabrica, array(161,186,190,191,194)) && isset($campos_peca['valor'])) ? ", preco" : "")."
							".((in_array($login_fabrica, array(148)) && isset($campos_peca['valor_total']) && !empty($campos_peca['valor_total'])) ? ", preco" : "")."
							".((in_array($login_fabrica, array(195)) && isset($campos_peca['valor']) && strlen($campos_peca['valor']) > 0) ? ", preco" : "")."
							".((in_array($login_fabrica, array(148))) ? ", parametros_adicionais" : "")."
							".((in_array($login_fabrica, array(183)) && isset($campos_peca['codigo_utilizacao'])) ? ", causa_defeito" : "")."
							".(($grava_defeito_peca == true) ? ", defeito" : "")."
						) VALUES (
							{$os_produto},
							{$campos_peca['id']},
							{$campos_peca['qtde']},
							{$devolucao_obrigatoria},
							{$valor_serv}
							{$valor_obs}
							{$login_admin}
							".(in_array($login_fabrica, array(148)) == false && (!empty($custo_peca)) ? ", '".str_replace(',','.',str_replace(".","",$custo_peca[$campos_peca['id']]))."'" : "")."
							".((in_array($login_fabrica, array(161,186,191,194)) && isset($campos_peca['valor'])) ? ", '".str_replace(',','.',$campos_peca['valor'])."'" : "")."
							".((in_array($login_fabrica, array(148)) && isset($campos_peca['valor_total']) && !empty($campos_peca['valor_total'])) ? ", '".str_replace(',','.',str_replace(".","",$campos_peca['valor_total']))."'" : "")."
							".((in_array($login_fabrica, array(195)) && isset($campos_peca['valor']) && strlen($campos_peca['valor']) > 0) ? ", '".str_replace(',','.',$campos_peca['valor'])."'" : "")."
							".((in_array($login_fabrica, array(148))) ? ", '".json_encode($parametros_adicionais_peca)."'" : "")."
							".((in_array($login_fabrica, array(190)) && isset($campos_peca['valor'])) ? ", '".str_replace(",",".",str_replace(".","",$campos_peca['valor']))."'" : "")."
							".(($login_fabrica == 183) ? ", ".$campos_peca['codigo_utilizacao'] : "")."
							".(($grava_defeito_peca == true) ? ", ".$campos_peca['defeito_peca'] : "")."
						) RETURNING os_item";
				$acao = "insert";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {

					throw new Exception("Erro ao gravar Ordem de Serviço #9 ");
				}

				$campos[$subproduto][$posicao]["os_item_insert"] = pg_fetch_result($res, 0, "os_item");
			} else {

				$campo_serv = '';
				if (isset($campos_peca['servico_realizado']) && !empty($campos_peca['servico_realizado'])) {
					$campo_serv = ',servico_realizado='.$campos_peca['servico_realizado'];

					if (in_array($login_fabrica, [169,170])) {
						$objLog = new AuditorLog();
						$objLog->retornaDadosSelect("SELECT p.referencia || ' - ' || p.descricao as peca, qtde, s.descricao AS servico_realizado, peca_obrigatoria FROM tbl_os_item JOIN tbl_servico_realizado s using(servico_realizado) JOIN tbl_peca p using(peca) WHERE os_produto ={$os_produto}");
					}
				} 

				$sql = "
					SELECT
						tbl_os_item.os_item,
						tbl_os_item.peca,
						tbl_os_item.qtde,
						tbl_os_item.servico_realizado,
						tbl_os_item.pedido
					FROM tbl_os_item
					JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
					WHERE tbl_os_item.os_produto = {$os_produto}
					AND tbl_os_item.os_item = {$campos_peca['os_item']}
					AND UPPER(tbl_servico_realizado.descricao) NOT IN ('CANCELADO', 'TROCA PRODUTO');
				";

				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$os_item_peca = pg_fetch_result($res, 0, "peca");
					$os_item_qtde = pg_fetch_result($res, 0, "qtde");
					$os_item_servico = pg_fetch_result($res, 0, "servico_realizado");
					$os_item_pedido = pg_fetch_result($res, 0, "pedido");

					if (!empty($os_item_pedido) && ($campos_peca['qtde'] != $os_item_qtde || $campos_peca['servico_realizado'] != $os_item_servico || $campos_peca['id'] != $os_item_peca)) {
                                        	continue;
                                	}

				}

				if (verificaPecaCancelada($campos_peca["os_item"]) === true) {
					continue;
				}

				if (verificaTrocaProduto($campos_peca["os_item"]) === true) {
					continue;
				}

				$sql = "
					UPDATE tbl_os_item SET
						qtde = {$campos_peca['qtde']}
						{$campo_serv}
						".(($grava_defeito_peca == true) ? ", defeito = {$campos_peca['defeito_peca']}" : "")."
						".(($login_fabrica == 183) ? ", causa_defeito = {$campos_peca['codigo_utilizacao']}" : "")."
					WHERE os_produto = {$os_produto}
					AND os_item = {$campos_peca['os_item']};
				";

				$acao = "update";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao gravar Ordem de Serviço #10");
				}
			}
		}
	}

	if (!empty($objLog)) {//logositem
		if (in_array($login_fabrica, [169,170])) {
			if ($acao == "insert") {
				$objLog->retornaDadosSelect("SELECT p.referencia || ' - ' || p.descricao as peca, qtde, s.descricao AS servico_realizado, peca_obrigatoria FROM tbl_os_item JOIN tbl_servico_realizado s using(servico_realizado) JOIN tbl_peca p using(peca) WHERE os_produto ={$os_produto}")->enviarLog($acao, "tbl_os", $login_fabrica."*".$os);
			} else {
				$objLog->retornaDadosSelect()->enviarLog($acao, "tbl_os", $login_fabrica."*".$os);
			}
		} else {
			$objLog->retornaDadosSelect()->enviarLog($acao, "tbl_os_item", $login_fabrica."*".$os);
		}
	}
	unset($objLog);

	if($historico_alteracao === true){

		if(count($historico) > 0){

			grava_historico($historico, $os, $campos["posto"]["id"], $login_fabrica, $login_admin);

		}

	}
}

function auditoria($auditorias) {
	foreach ($auditorias as $auditoria) {
		try {
			call_user_func($auditoria);
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}

function finaliza_os(){

	global $con, $os, $login_fabrica, $campos;

	$sql = "UPDATE tbl_os SET finalizada = CURRENT_TIMESTAMP, data_fechamento = CURRENT_DATE WHERE os = {$os} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(strlen(pg_last_error() > 0)){
		throw new Exception("Erro ao Finalizar a OS {$os}");
	}

	return true;

}

/**
 * Função que verifica se a peça da os está cancelada
 */
function verificaPecaCancelada($os_item) {
	global $con, $login_fabrica;

	if (!empty($os_item)) {
		$sql = "SELECT tbl_os_item.os_item
				FROM tbl_os_item
				INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
				WHERE tbl_os_item.os_item = {$os_item}
				AND UPPER(tbl_servico_realizado.descricao) = 'CANCELADO'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function verificaPecaCanceladaPedido($os_item){
	global $con, $login_fabrica;

	if (!empty($os_item)) {
		$sql = "SELECT tbl_pedido_item.qtde_cancelada
				FROM tbl_os_item
				INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
				WHERE tbl_os_item.os_item = {$os_item}	and tbl_os_item.fabrica_i = $login_fabrica ";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$qtde_cancelada = pg_fetch_result($res, 0, "qtde_cancelada");
			return $qtde_cancelada;
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}

/**
 * Função que verifica se a peça é um produto de troca
 */
function verificaTrocaProduto($os_item) {
	global $con, $login_fabrica;

	if (!empty($os_item)) {
		$sql = "SELECT tbl_os_item.os_item
				FROM tbl_os_item
				INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
				WHERE tbl_os_item.os_item = {$os_item}
				AND tbl_servico_realizado.troca_produto IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Função que verifica se existe troca lançada para os os_produto
 */
function verificaOsProdutoTroca($os_produto) {
	global $con, $login_fabrica;

	if (!empty($os_produto)) {
		$sql = "SELECT tbl_os_produto.os_produto
				FROM tbl_os_produto
				INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os_produto.os AND tbl_os_troca.produto = tbl_os_produto.produto
				WHERE tbl_os_produto.os_produto = {$os_produto}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Grava valores adicionais
*/
function grava_valor_adicional($valores, $os) {
	global $con, $login_fabrica, $campos;
	$sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
	$res = pg_query($con,$sql);

	$campos["auditoria"]["valor_adicional_valor_antes"] = pg_fetch_result($res, 0, "valores_adicionais");
	$valor_adicional_valor = array();
	$valor_adicional_valor = $campos["os"]["valor_adicional_valor"];

	if(!empty($os)){

		if ($valores != null && $valores != "null") {

			$valores = json_decode($valores,true);

			foreach ($valores as $key => $valor) {
				$valor_key = array_keys($valor);
				$valor_key = $valor_key[0];

				if (isset($valor_adicional_valor[$valor_key])) {
					$valores[$key][$valor_key] = $valor_adicional_valor[$valor_key];
				}
			}

			$valores = json_encode($valores);

			if (pg_num_rows($res) > 0) {
				$sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = '{$valores}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
			} else {
				$sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,valores_adicionais) VALUES({$os},{$login_fabrica},'{$valores}')";
			}

			$res = pg_query($con,$sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar Ordem de Serviço #11");
			}
		} else if ($valores == null && pg_num_rows($res) > 0) {
			$sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = null from tbl_auditoria_os WHERE tbl_auditoria_os.os = tbl_os_campo_extra.os and tbl_os_campo_extra.os = $os and liberada isnull and cancelada isnull and reprovada isnull AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			$qVerificaAuditoria = "
				SELECT
					auditoria_os,
					observacao
				FROM tbl_auditoria_os
				WHERE os = {$os}
				AND liberada IS NULL
				AND cancelada IS NULL
				AND reprovada IS NULL
				AND auditoria_status = 6;
			";
			$rVerificaAuditoria = pg_query($con, $qVerificaAuditoria);
			$rVerificaAuditoria = pg_fetch_all($rVerificaAuditoria);

			$auditoriasOss = [];
			foreach ($rVerificaAuditoria as $key => $value) {
				$auditoriaOs = $value['auditoria_os'];
				$observacao = $value['observacao'];

				if (preg_match("/valores adicionais/", strtolower($observacao))) {
					$auditoriasOss[] = $auditoriaOs;
				}
			}

			if (!empty($auditoriasOss)) {
				$auditoriasOss = implode(", ", $auditoriasOss);
				$justificativaCancelada = "Auditoria cancelada automaticamente pelo sistema: Valores adicionais removidos pelo usuário.";

				$qRemoveAuditoria = "
					UPDATE tbl_auditoria_os
					SET cancelada = CURRENT_TIMESTAMP,
						bloqueio_pedido = false,
						justificativa = '{$justificativaCancelada}'
					WHERE auditoria_os IN ($auditoriasOss)
					AND os = {$os}
				";
				$rRemoveAuditoria = pg_query($con, $qRemoveAuditoria);
			}
			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar Ordem de Serviço #12".pg_last_error());
			}
		}
	}
	return;
}

/**
 * Grava campos adicionais
*/
function grava_campo_adicional($os) {

	global $con, $login_fabrica, $campos;

	$sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";

	$res = pg_query($con,$sql);

	$campo_adicional_valor = array();
	$campo_adicional_valor = grava_os_campo_extra_fabrica();
	
	if (!empty($os) AND count($campo_adicional_valor) > 0) {
		if (pg_num_rows($res) > 0) {

			$campo_adicional = pg_fetch_result($res, 0, 'campos_adicionais');
            $campo_adicional = json_decode($campo_adicional,true);

            if (is_array($campo_adicional)) {
				$campo_adicional = array_merge($campo_adicional, $campo_adicional_valor);
				$campo_adicional = json_encode($campo_adicional);
			} else {
                $campo_adicional = json_encode($campo_adicional_valor);
			}

			$sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$campo_adicional}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
		} else {			
			$campo_adicional_valor = json_encode($campo_adicional_valor);
			
			$sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,campos_adicionais) VALUES ({$os}, {$login_fabrica}, '{$campo_adicional_valor}')";
		}		
		$res = pg_query($con,$sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar Ordem de Serviço #13".pg_last_error($con));
		}

	}	
	return;
}

/**
* Verifica se o produto tem a opção de Visita Técnica
*/
function verifica_visita_tecnica_produto($produto) {

	global $con, $login_fabrica;

	$sql = "SELECT entrega_tecnica FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		return false;
	}

	return (pg_fetch_result($res, 0, 'entrega_tecnica') == 't');
}
