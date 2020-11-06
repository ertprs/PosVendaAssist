<?php
$os    = $_REQUEST["os_id"];

#Arquivo com as regras padrões do sistema
include __DIR__."/regras_os_entrega_tecnica.php";

#Arquivo com as regras especificas da fábrica
if (file_exists(__DIR__."/{$login_fabrica}/regras_os_entrega_tecnica.php")) {
	include __DIR__."/{$login_fabrica}/regras_os_entrega_tecnica.php";
}

#Array de erros
$msg_erro = array(
	"msg"    => array(),
	"campos" => array()
);

if (strlen($os) > 0) {
	$sql = "SELECT
				TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
				tbl_os.tipo_atendimento,
				tbl_os.nota_fiscal,
				TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
				tbl_os.defeito_reclamado_descricao,
				tbl_os.aparencia_produto,
				tbl_os.acessorios,
				tbl_os.obs,
				tbl_os.qtde_km,
				tbl_os.solucao_os,
				tbl_os.consumidor_revenda,
				tbl_os.consumidor_nome,
				tbl_os.consumidor_cpf,
				tbl_os.consumidor_cep,
				tbl_os.consumidor_estado,
				tbl_os.consumidor_cidade,
				tbl_os.consumidor_bairro,
				tbl_os.consumidor_endereco,
				tbl_os.consumidor_numero,
				tbl_os.consumidor_complemento,
				tbl_os.consumidor_fone,
				tbl_os.consumidor_celular,
				tbl_os.consumidor_email,
				tbl_os.posto,
				tbl_os.cortesia,
				tbl_os.justificativa_adicionais,
				tbl_os.qtde_hora,
				tbl_os.hora_tecnica,
				tbl_os.rg_produto,
				tbl_os.qtde_diaria,
				tbl_posto_fabrica.codigo_posto AS posto_codigo,
				tbl_posto.nome AS posto_nome,
				tbl_os.revenda,
				tbl_revenda.nome AS revenda_nome,
				tbl_revenda.cnpj AS revenda_cnpj,
				tbl_revenda.cep AS revenda_cep,
				cidade_revenda.estado AS revenda_estado,
				UPPER(TO_ASCII(cidade_revenda.nome, 'LATIN9')) AS revenda_cidade,
				tbl_revenda.bairro AS revenda_bairro,
				tbl_revenda.endereco AS revenda_endereco,
				tbl_revenda.numero AS revenda_numero,
				tbl_revenda.complemento AS revenda_complemento,
				tbl_revenda.fone AS revenda_telefone,
				tbl_os_campo_extra.venda,
				tbl_os.nf_os,
				tbl_os_extra.obs_adicionais
			FROM tbl_os
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}
			LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
			LEFT JOIN tbl_cidade AS cidade_revenda ON cidade_revenda.cidade = tbl_revenda.cidade
			WHERE tbl_os.fabrica = {$login_fabrica}
			AND tbl_os.os = {$os}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT = array(
			'os' => array(
				'data_abertura'      => pg_fetch_result($res, 0, 'data_abertura'),
				'tipo_atendimento'   => pg_fetch_result($res, 0, 'tipo_atendimento'),
				'nota_fiscal'        => pg_fetch_result($res, 0, 'nota_fiscal'),
				'data_compra'        => pg_fetch_result($res, 0, 'data_nf'),
				'defeito_reclamado'  => pg_fetch_result($res, 0, 'defeito_reclamado_descricao'),
				'aparencia_produto'  => pg_fetch_result($res, 0, 'aparencia_produto'),
				'acessorios'         => pg_fetch_result($res, 0, 'acessorios'),
				'observacoes'        => pg_fetch_result($res, 0, 'obs'),
				'qtde_km'            => pg_fetch_result($res, 0, 'qtde_km'),
				'qtde_horas'         => pg_fetch_result($res, 0, 'hora_tecnica'),
				'tempo_deslocamento' => pg_fetch_result($res, 0, 'qtde_hora'),
				'qtde_km_hidden'     => pg_fetch_result($res, 0, 'qtde_km'),
				'solucao'            => pg_fetch_result($res, 0, 'solucao_os'),
				'consumidor_revenda' => pg_fetch_result($res, 0, 'consumidor_revenda')
			),
			'consumidor' => array(
				'nome'        => pg_fetch_result($res, 0, 'consumidor_nome'),
				'cpf'         => pg_fetch_result($res, 0, 'consumidor_cpf'),
				'cep'         => pg_fetch_result($res, 0, 'consumidor_cep'),
				'estado'      => pg_fetch_result($res, 0, 'consumidor_estado'),
				'cidade'      => pg_fetch_result($res, 0, 'consumidor_cidade'),
				'bairro'      => pg_fetch_result($res, 0, 'consumidor_bairro'),
				'endereco'    => pg_fetch_result($res, 0, 'consumidor_endereco'),
				'numero'      => pg_fetch_result($res, 0, 'consumidor_numero'),
				'complemento' => pg_fetch_result($res, 0, 'consumidor_complemento'),
				'telefone'    => pg_fetch_result($res, 0, 'consumidor_fone'),
				'celular'     => pg_fetch_result($res, 0, 'consumidor_celular'),
				'email'       => pg_fetch_result($res, 0, 'consumidor_email')
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
				'nome'   => pg_fetch_result($res, 0, 'posto_nome')
			)
		);

		/**
		 * Pega informações do produto
		 */
		$sql = "SELECT
					tbl_os_produto.os_produto,
					tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_os_produto.capacidade,
					tbl_produto.code_convention
				FROM tbl_os_produto
				INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				WHERE tbl_os_produto.os = {$os}
				ORDER BY tbl_os_produto.os_produto ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			$rows = pg_num_rows($res);

			$produtos = array();

			for($i = 0; $i < $rows; $i++){
				$produtos[$i] = array(
					"os_produto"         => pg_fetch_result($res, $i, "os_produto"),
					"id"                 => pg_fetch_result($res, $i, "produto"),
					"referencia"         => pg_fetch_result($res, $i, "referencia"),
					"descricao"          => pg_fetch_result($res, $i, "descricao"),
					"qtde"           	 => pg_fetch_result($res, $i, "capacidade"),
					"entrega_tecnica"  	 => pg_fetch_result($res, $i, "code_convention")
				);
			}

			$_RESULT["produto"] = $produtos;

		}
		
	} else {
		$msg_erro["msg"][] = "Ordem de Serviço não encontrada";
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

if ($_POST["gravar"]) {

	$campos = array(
		'posto'         => $_POST['posto'],
		'os'            => $_POST['os'],
		'consumidor'    => $_POST['consumidor'],
		'revenda'       => $_POST['revenda'],
		'produto'       => $_POST['produto'],
		'anexo'         => $_POST['anexo'],
		'anexo_s3'      => $_POST['anexo_s3']
	);

	/*HD-4165550*/
	$campos['os']['observacoes'] = pg_escape_string($campos['os']['observacoes']);

	/**
	 * Validação os campos do formulário
	 */
	valida_campos();
	valida_produtos();

	/**
	 * Validação de anexo
	 */
	if (!empty($valida_anexo) && function_exists($valida_anexo)) {
		$valida_anexo();
	}

	if (!count($msg_erro["msg"])) {
		try {
			pg_query($con, "BEGIN");

			if (!strlen($os)) {
				$gravando = true;
			}

			/**
			 * Grava a Ordem de Serviço
			 */
			grava_os();

			/**
			 * Auditoria
			 */
			auditoria($auditorias);

			if (!empty($grava_anexo) && function_exists($grava_anexo)) {
				$grava_anexo();
			}

			pg_query($con, "COMMIT");

			header("Location: os_press_entrega_tecnica.php?os={$os}");
		} catch(Exception $e) {
			pg_query($con, "ROLLBACK");
			$msg_erro["msg"][] = $e->getMessage();

			if ($gravando === true) {
				unset($os);
			}
		}
	}
}

function grava_os() {
	global $con, $login_fabrica, $campos, $os, $fabrica_usa_valor_adicional, $fabrica_usa_subproduto, $qtde_pecas;

	/**
	 * Grava tbl_os
	 */
	if (function_exists("grava_os_fabrica")) {
		/**
		 * A função grava_os_fabrica deve ficar dentro do arquivo de regras fábrica
		 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
		 */
		// NAO ESTA GRAVANDO HORAS TECNICAS
		$tbl_os = grava_os_fabrica();

		if (!empty($os)) {
			$tbl_os_update = array();

			foreach ($tbl_os as $key => $value) {
				$tbl_os_update[] = "{$key} = {$value}";
			}
		}
	}

	$login_admin = (empty($login_admin)) ? "null" : $login_admin;

	$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE entrega_tecnica = true AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	$campos['os']['tipo_atendimento'] = pg_fetch_result($res,0,"tipo_atendimento");

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar Ordem de Serviço - 3 ");
	}

	$campos['consumidor']['nome'] = substr($campos['consumidor']['nome'],0,50);
	$campos['revenda']['nome'] = substr($campos['revenda']['nome'],0,50);

	if (empty($os)) {
		$sql = "INSERT INTO tbl_os
				(
					fabrica,
					posto,
					admin,
					data_abertura,
					tipo_atendimento,
					nota_fiscal,
					data_nf,
					defeito_reclamado_descricao,
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
					revenda,
					revenda_nome,
					revenda_cnpj,
					revenda_fone,
					obs,
					qtde_km
					".((isset($column_tbl_os)) ? ", ".implode(", ", array_keys($column_tbl_os)) : "")."
					".((isset($tbl_os)) ? ", ".implode(", ", array_keys($tbl_os)) : "")."
				)
				VALUES
				(
					$login_fabrica,
					{$campos['posto']['id']},
					$login_admin,
					'".formata_data($campos['os']['data_abertura'])."',
					".((empty($campos['os']['tipo_atendimento'])) ? "null" : $campos['os']['tipo_atendimento'] ).",
					'{$campos['os']['nota_fiscal']}',
					".((!empty($campos["os"]["data_compra"])) ? "'".formata_data($campos['os']['data_compra'])."'" : "null").",
					'{$campos['os']['defeito_reclamado']}',
					'{$campos['os']['aparencia_produto']}',
					'{$campos['os']['acessorios']}',
					'{$campos['os']['consumidor_revenda']}',
					'{$campos['consumidor']['nome']}',
					'".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
					'".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
					'{$campos['consumidor']['estado']}',
					'".pg_escape_string($con,$campos['consumidor']['cidade'])."',
					'".pg_escape_string($con,$campos['consumidor']['bairro'])."',
					'".pg_escape_string($con,$campos['consumidor']['endereco'])."',
					'{$campos['consumidor']['numero']}',
					'".pg_escape_string($con,$campos['consumidor']['complemento'])."',
					'{$campos['consumidor']['telefone']}',
					'{$campos['consumidor']['celular']}',
					'{$campos['consumidor']['email']}',
					".verifica_revenda().",
					'{$campos['revenda']['nome']}',
					'".preg_replace("/[\.\-\/]/", "", $campos['revenda']['cnpj'])."',
					'{$campos['revenda']['telefone']}',
					'{$campos['os']['observacoes']}',
					".((!strlen($campos['os']['qtde_km'])) ? 0 : $campos['os']['qtde_km'])."
					".((isset($column_tbl_os)) ? ", ".implode(", ", $column_tbl_os) : "")."
					".((isset($tbl_os)) ? ", ".implode(", ", $tbl_os) : "")."
				)
				RETURNING os";

	} else {
		if (isset($column_tbl_os)) {
			$column_tbl_os_update = array();

			foreach ($column_tbl_os as $key => $value) {
				$column_tbl_os_update[] = "{$key} = {$value}";
			}
		}

		$sql = "UPDATE tbl_os SET
					data_abertura = '".formata_data($campos['os']['data_abertura'])."',
					tipo_atendimento = ".((empty($campos['os']['tipo_atendimento'])) ? "null" : $campos['os']['tipo_atendimento'] ).",
					nota_fiscal = '{$campos['os']['nota_fiscal']}',
					data_nf = ".((!empty($campos["os"]["data_compra"])) ? "'".formata_data($campos['os']['data_compra'])."'" : "null").",
					defeito_reclamado_descricao = '{$campos['os']['defeito_reclamado']}',
					aparencia_produto = '{$campos['os']['aparencia_produto']}',
					acessorios = '{$campos['os']['acessorios']}',
					consumidor_nome = '{$campos['consumidor']['nome']}',
					consumidor_cpf = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
					consumidor_cep = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
					consumidor_estado = '{$campos['consumidor']['estado']}',
					consumidor_cidade = '".pg_escape_string($con,$campos['consumidor']['cidade'])."',
					consumidor_bairro = '".pg_escape_string($con,$campos['consumidor']['bairro'])."',
					consumidor_endereco = '".pg_escape_string($con,$campos['consumidor']['endereco'])."',
					consumidor_numero = '{$campos['consumidor']['numero']}',
					consumidor_complemento = '".pg_escape_string($con,$campos['consumidor']['complemento'])."',
					consumidor_fone = '{$campos['consumidor']['telefone']}',
					consumidor_celular = '{$campos['consumidor']['celular']}',
					consumidor_email = '{$campos['consumidor']['email']}',
					revenda = ".verifica_revenda().",
					revenda_nome = '{$campos['revenda']['nome']}',
					revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos['revenda']['cnpj'])."',
					revenda_fone = '{$campos['revenda']['telefone']}',
					obs = '{$campos['os']['observacoes']}',
					qtde_km = '".((!strlen($campos['os']['qtde_km'])) ? 0 : $campos['os']['qtde_km'])."'
					".((isset($column_tbl_os_update)) ? ", ".implode(", ", $column_tbl_os_update) : "")."
					".((isset($tbl_os_update)) ? ", ".implode(", ", $tbl_os_update) : "")."
				WHERE os = {$os}
				AND fabrica = {$login_fabrica}";
	}

	$res = pg_query($con, $sql);
	
	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar Ordem de Serviço - 1 ");
	} else if (empty($os)) {
		$os = pg_fetch_result($res, 0, "os");

		$sql = "UPDATE tbl_os SET sua_os = '{$os}', validada = CURRENT_TIMESTAMP WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar Ordem de Serviço - 2");
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

		if (!empty($os)) {
			$tbl_os_extra_update = array();

			foreach ($tbl_os_extra as $key => $value) {
				$tbl_os_extra_update[] = "{$key} = {$value}";
			}
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
		throw new Exception("Erro ao gravar Ordem de Serviço - 6");
	}

	/**
	 * Grava tbl_os_produto
	 */
	$campos_produtos = count($campos["produto"]);

	if($campos_produtos){

		for($i = 0; $i < $campos_produtos; $i++){

			$os_produto = $campos["produto"][$i]["os_produto"];
			$id_produto = $campos["produto"][$i]["id"];
			$referencia = $campos["produto"][$i]["referencia"];
			$descricao 	= $campos["produto"][$i]["descricao"];
			$qtde 		= $campos["produto"][$i]["qtde"];

			if(!empty($id_produto) && empty($os_produto)){

				$sql = "INSERT INTO tbl_os_produto (os, produto, capacidade) VALUES ({$os}, {$id_produto}, {$qtde}) RETURNING os_produto";

				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao gravar Ordem de Serviço - ".$sql);
				} else if (empty($os_produto)) {
					$os_produto = pg_fetch_result($res, 0, "os_produto");
				}

			}else if(!empty($os_produto)){

				$sql = "UPDATE tbl_os_produto SET capacidade = {$qtde} WHERE os_produto = {$os_produto} AND os = {$os}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao atualizar a Ordem de Serviço - ".pg_last_error());
				}

			}

		}

		if(isset($fabrica_usa_valor_adicional)){
			if(count($campos["os"]["valor_adicional"]) > 0){

				foreach ($campos["os"]["valor_adicional"] as $key => $value) {
					list($chave,$valor) = explode("|", $value);
					$valores[$key] = array(utf8_encode(utf8_decode($chave)) => $valor);
				}

				$valores = json_encode($valores);

				$valores = str_replace("\\", "\\\\", $valores);

				grava_valor_adicional($valores,$os);

			} else {
				grava_valor_adicional(null,$os);
			}
		}

	}

}


/**
 * Grava valores adicionais
*/
function grava_valor_adicional($valores, $os){
	global $con, $login_fabrica,$campos;
	$sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
	$res = pg_query($con,$sql);

	$valor_adicional_valor = array();
	$valor_adicional_valor = $campos["os"]["valor_adicional_valor"];

	if ($valores != null) {
		if (pg_num_rows($res) > 0) {
			$valor_adicional = pg_fetch_result($res, 0, 'valores_adicionais');

			if(strlen($valor_adicional) > 0 && $valor_adicional != "null"){
				$valor_adicional = json_decode($valor_adicional,true);
				$valores = json_decode($valores,true);
				var_dump($valores);
				foreach ($valores as $key => $valor) {
					$valor_key = array_keys($valor);
					$valor_key = $valor_key[0];

					if (isset($valor_adicional_valor[$valor_key])) {
						$valores[$key][$valor_key] = $valor_adicional_valor[$valor_key];
					}
				}

				$valores = json_encode($valores);
			}

			$sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = '{$valores}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
		} else {
			$valores = json_decode($valores,true);

			foreach ($valores as $key => $valor) {
				$valor_key = array_keys($valor);
				$valor_key = $valor_key[0];

				if (isset($valor_adicional_valor[$valor_key])) {
					$valores[$key][$valor_key] = $valor_adicional_valor[$valor_key];
				}
			}

			$valores = json_encode($valores);
			$sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,valores_adicionais) VALUES({$os},{$login_fabrica},'{$valores}')";
		}
		$res = pg_query($con,$sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar Ordem de Serviço #11");
		}
	} else if ($valores == null && pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = null WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar Ordem de Serviço #12");
		}
	}
	return;
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
?>
