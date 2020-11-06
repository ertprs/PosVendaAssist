<?php

$os    = $_REQUEST["os_id"];

#Arquivo com as regras padrões do sistema
include __DIR__."/regras_os_revisao.php";

#Arquivo com as regras especificas da fábrica
if (file_exists(__DIR__."/{$login_fabrica}/regras_os_revisao.php")) {
	include __DIR__."/{$login_fabrica}/regras_os_revisao.php";
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
				tbl_os.qtde_diaria,
				tbl_os.qtde_km,
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
				tbl_os.consumidor_email,
				tbl_os.posto,
				tbl_posto_fabrica.codigo_posto AS posto_codigo,
				tbl_posto.nome AS posto_nome
			FROM tbl_os 
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
			WHERE tbl_os.fabrica = {$login_fabrica}
			AND tbl_os.os = {$os}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT = array(
			"os" => array(
				"data_abertura"     => pg_fetch_result($res, 0, "data_abertura"),
				"tipo_atendimento"  => pg_fetch_result($res, 0, "tipo_atendimento"),
				"qtde_km"           => pg_fetch_result($res, 0, "qtde_km"),
				"qtde_visitas" 		=> pg_fetch_result($res, 0, "qtde_diaria")
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
				"telefone"    => pg_fetch_result($res, 0, "consumidor_fone"),
				"email"       => pg_fetch_result($res, 0, "consumidor_email")
			),
			"posto" => array(
				"id" 		=> pg_fetch_result($res, 0, "posto"),
				"codigo" 	=> pg_fetch_result($res, 0, "posto_codigo"),
				"nome" 		=> pg_fetch_result($res, 0, "posto_nome")
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
					tbl_os_produto.capacidade 
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
					"qtde"           	 => pg_fetch_result($res, $i, "capacidade")
				);
			}

			$_RESULT["produto"] = $produtos; 

		}

	} else {
		$msg_erro["msg"][] = "Ordem de Serviço não encontrada";
	}
}

if ($_POST["gravar"]) {

	$campos = array(
		"posto"         => $_POST["posto"],
		"os"            => $_POST["os"],
		"consumidor"    => $_POST["consumidor"],
		"produto"       => $_POST["produto"],
	);

	/**
	 * Validação os campos do formulário
	 */
	valida_campos();
	valida_produtos();

	if (!count($msg_erro["msg"])) {
		try {
			pg_query($con, "BEGIN");

			/**
			 * Grava a Ordem de Serviço
			 */
			grava_os();

			pg_query($con, "COMMIT");

			header("Location: os_press_revisao.php?os={$os}");
		} catch(Exception $e) {
			pg_query($con, "ROLLBACK");
			$msg_erro["msg"][] = $e->getMessage();
		}
	}
}

function grava_os() {
	global $con, $login_fabrica, $campos, $os, $fabrica_usa_subproduto, $qtde_pecas;

	/**
	 * Grava tbl_os
	 */
	if (function_exists("grava_os_fabrica")) {
		/**
		 * A função grava_os_fabrica deve ficar dentro do arquivo de regras fábrica
		 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
		 */
		$tbl_os = grava_os_fabrica();

		if (!empty($os)) {
			$tbl_os_update = array();

			foreach ($tbl_os as $key => $value) {
				$tbl_os_update[] = "{$key} = {$value}";
			}
		}
	}

	if (empty($os)) {

		$sql_tipo_os = "SELECT tipo_os FROM tbl_tipo_os WHERE UPPER(fn_retira_especiais(descricao)) = 'REVISAO'";
		$res_tipo_os = pg_query($con, $sql_tipo_os);

		if(pg_num_rows($res_tipo_os) > 0){
			$tipo_os = pg_fetch_result($res_tipo_os, 0, 'tipo_os');
		}else{
			throw new Exception("Tipo de OS Revisão não cadastrado no sistema");
		} 

		$sql = "INSERT INTO tbl_os 
				(
					fabrica,
					posto,
					data_abertura,
					tipo_atendimento,
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
					consumidor_email,
					consumidor_revenda,
					qtde_km,
					tipo_os,
					qtde_diaria
					".((isset($tbl_os)) ? ", ".implode(", ", array_keys($tbl_os)) : "")."
				)
				VALUES
				(
					{$login_fabrica},
					{$campos['posto']['id']},
					'".formata_data($campos['os']['data_abertura'])."',
					{$campos['os']['tipo_atendimento']},
					'{$campos['consumidor']['nome']}',
					'".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
					'".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
					'{$campos['consumidor']['estado']}',
					'{$campos['consumidor']['cidade']}',
					'{$campos['consumidor']['bairro']}',
					'{$campos['consumidor']['endereco']}',
					'{$campos['consumidor']['numero']}',
					'{$campos['consumidor']['complemento']}',
					'{$campos['consumidor']['telefone']}',
					'{$campos['consumidor']['email']}',
					'C',
					'{$campos['os']['qtde_km']}',
					{$tipo_os},
					{$campos['os']['qtde_visitas']}
					".((isset($tbl_os)) ? ", ".implode(", ", $tbl_os) : "")."
				)
				RETURNING os";
	} else {
		$sql = "UPDATE tbl_os SET
					data_abertura = '".formata_data($campos['os']['data_abertura'])."',
					consumidor_nome = '{$campos['consumidor']['nome']}',
					consumidor_cpf = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
					consumidor_cep = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
					consumidor_estado = '{$campos['consumidor']['estado']}',
					consumidor_cidade = '{$campos['consumidor']['cidade']}',
					consumidor_bairro = '{$campos['consumidor']['bairro']}',
					consumidor_endereco = '{$campos['consumidor']['endereco']}',
					consumidor_numero = '{$campos['consumidor']['numero']}',
					consumidor_complemento = '{$campos['consumidor']['complemento']}',
					consumidor_fone = '{$campos['consumidor']['telefone']}',
					consumidor_email = '{$campos['consumidor']['email']}',
					qtde_km = '{$campos['os']['qtde_km']}',
					qtde_diaria = {$campos['os']['qtde_visitas']} 
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

				$sql = "UPDATE tbl_os_produto SET capacidade = {$qtde} FROM tbl_os WHERE os_produto = {$os_produto} AND tbl_os_produto.os = {$os} AND tbl_os.os = tbl_os_produto.os AND tbl_os.finalizada ISNULL and tbl_os_produto.capacidade <> {$qtde}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao atualizar a Ordem de Serviço #2");
				}

			}

		}

	}

}

?>
