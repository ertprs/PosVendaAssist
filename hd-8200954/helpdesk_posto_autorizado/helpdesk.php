<?php
#Arquivo com as regras padrões do sistema
include_once __DIR__."/regras.php";

if (file_exists(__DIR__."/{$login_fabrica}/regras.php")) {
	include_once __DIR__."/{$login_fabrica}/regras.php";
}
require_once __DIR__."/../class/email/PHPMailer/PHPMailerAutoload.php";

$msg_erro = array(
	'msg'    => array(),
	'campos' => array(),
	'inf'	 => array()
);

if (in_array($login_fabrica, [169,170])) {

	$sqlBuscaPostoFabrica = "SELECT tbl_posto_fabrica.posto,
									tbl_posto.nome,
									tbl_posto_fabrica.codigo_posto
							 FROM tbl_fabrica
							 JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_fabrica.posto_fabrica
							 AND tbl_posto_fabrica.fabrica = {$login_fabrica}
							 JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
							 WHERE tbl_fabrica.fabrica = {$login_fabrica}
							";
	$resBuscaPostoFabrica = pg_query($con, $sqlBuscaPostoFabrica);

	if(pg_num_rows($resBuscaPostoFabrica)>0){
		$posto_id 			= pg_fetch_result($resBuscaPostoFabrica, 0, posto);
		$descricao_posto 	= pg_fetch_result($resBuscaPostoFabrica, 0, nome);
		$codigo_posto 		= pg_fetch_result($resBuscaPostoFabrica, 0, codigo_posto);
	
		$_RESULT['posto']['id'] 		= $posto_id;
		$_RESULT['posto']['codigo'] 	= $codigo_posto;
		$_RESULT['posto']['nome'] 		= $descricao_posto;
	}


}

if(in_array($login_fabrica, [35]) && $areaAdmin == true && filter_input(INPUT_GET,'os_abertura')){

	$os_abertura = filter_input(INPUT_GET,'os_abertura');

	$sqlBuscaPosto = " SELECT tbl_os.sua_os AS sua_os_abertura, 
								tbl_os.os, 
								tbl_posto_fabrica.posto, 
								tbl_posto.nome, 
								tbl_posto_fabrica.codigo_posto
						FROM tbl_os 
						inner join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						inner join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
						where tbl_os.os = $os_abertura ";
	$resBuscaPosto = pg_query($con, $sqlBuscaPosto);
	if(pg_num_rows($resBuscaPosto)>0){
		$sua_os_abertura 	= pg_fetch_result($resBuscaPosto, 0, sua_os_abertura);
		$posto_id 			= pg_fetch_result($resBuscaPosto, 0, posto);
		$descricao_posto 	= pg_fetch_result($resBuscaPosto, 0, nome);
		$codigo_posto 		= pg_fetch_result($resBuscaPosto, 0, codigo_posto);
	
		$_RESULT['posto']['id'] 		= $posto_id;
		$_RESULT['posto']['codigo'] 	= $codigo_posto;
		$_RESULT['posto']['nome'] 		= $descricao_posto;
		$_RESULT['ordem_de_servico'] 	= $os_abertura;
	}

}


if ($_POST["gravar_interacao"]) {
	global $areaAdmin, $login_fabrica, $login_fabrica_nome;

	$campos = array(
		"tipo_interacao" => $_POST["tipo_interacao"],
		"nova_interacao" => trim($_POST["nova_interacao"]),
		"anexo"          => $_POST["anexo_i"]
	);

	if ($campos["tipo_interacao"] == "Transferir") {
		$campos = array_merge($campos, array("novo_atendente" => $_POST['admin_disp']));
		if (strlen($campos["novo_atendente"]) == 0) {
			$msg_erro['msg'][] = 'Opção de Transferir marcada, necessário selecionar um Admin!';
		}
	}

    $data_providencia = '';

    if (!empty($_POST["data_providencia"])) {
        $dp = DateTime::createFromFormat('d/m/Y', $_POST["data_providencia"]);
        $m = (int) $dp->format('m');
        $d = (int) $dp->format('d');
        $y = (int) $dp->format('Y');

        if (checkdate($m, $d, $y)) {
            $data_providencia = $dp->format('Y-m-d');
        } else {
            $msg_erro['msg'][] = 'Data de Retorno inválida';
        }
    }

	if (count($msg_erro['msg']) == 0) {
		try {
			pg_query($con, "BEGIN");

			if ($campos["tipo_interacao"] == "Transferir") {

				$sqlNomeAdmin = "SELECT nome_completo,email FROM tbl_admin WHERE admin = {$campos['novo_atendente']};";
				$resNomeAdmin = pg_query($con, $sqlNomeAdmin);
				$nome_completo = pg_fetch_result($resNomeAdmin, 0, nome_completo);
				$email_admin = pg_fetch_result($resNomeAdmin, 0, email);

				$hd_chamado_item = gravar_interacao($hd_chamado, "Atendimento transferido para: {$nome_completo}.<br />".$campos["nova_interacao"], "Interação Interna");
				updateAtendente($hd_chamado, $campos['novo_atendente']);
				
				if (in_array($login_fabrica, [35,151]) && $fabricaFileUploadOS) {
					gravarAnexosBoxUploader();
				} elseif ($fabricaFileUploadOS) {
					gravarAnexosBoxUploaderGeral();
				} else {
					grava_anexo_tdocs_interacao();
				}

				Envia_Email($email_admin, $hd_chamado, $campos["nova_interacao"], 1);

			} else {
				$hd_chamado_item = gravar_interacao($hd_chamado, $campos["nova_interacao"], $campos["tipo_interacao"]);

				if (in_array($login_fabrica, [35,151]) && $fabricaFileUploadOS) {
					gravarAnexosBoxUploader();
				} elseif ($fabricaFileUploadOS) {
					gravarAnexosBoxUploaderGeral();
				} else {
					grava_anexo_tdocs_interacao();
				}

				if ($_POST["tipo_interacao"] !== "Exigir Comprovante" and $_POST["tipo_interacao"] !== "Interação Interna") {
					if ($areaAdmin == true) {
						$sql = "SELECT tbl_posto_fabrica.contato_email as email FROM tbl_posto_fabrica JOIN tbl_posto ON(tbl_posto_fabrica.posto = tbl_posto.posto) JOIN tbl_hd_chamado ON(tbl_posto.posto = tbl_hd_chamado.posto) WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado} AND tbl_posto_fabrica.fabrica = {$login_fabrica};";
					}else{
						$sql = "SELECT tbl_admin.email as email FROM tbl_hd_chamado JOIN tbl_admin ON(tbl_admin.admin = tbl_hd_chamado.admin) WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado}";
					}
					$res = pg_query($con, $sql);
					$email = pg_fetch_result($res, 0, email);
					Envia_Email($email, $hd_chamado, $campos["nova_interacao"]);
				}
			}

			unset($campos, $_POST);

			pg_query($con, "COMMIT");

            if (!empty($data_providencia)) {
                gravaDataProvidencia($hd_chamado, $data_providencia);
            }
		} catch(Exception $e) {
			pg_query($con, "ROLLBACK");
			$msg_erro["msg"][] = $e->getMessage();
		}
	}
}

if ($_POST['gravar']) {

	$campos = array(
		"posto"                   => $_POST["posto"],
		"responsavel_solicitacao" => trim($_POST["responsavel_solicitacao"]),
		"tipo_solicitacao"        => $_POST["tipo_solicitacao"],
		"produto_garantia"        => $_POST["produto_garantia"],
		"tipo_interacao"          => $_POST["tipo_interacao"],
		"descricao_atendimento"   => trim($_POST["descricao_atendimento"]),
		"anexo"                   => $_POST["anexo"]
	);

	if (in_array($login_fabrica, [30,35])) {
		$campos['ordem_de_servico'] = $_POST["ordem_de_servico"];
	}	

	if ($areaAdmin === true && $login_fabrica == 30) {
		$campos['chamado_interno'] = $_POST["posto"]['chamado_interno'];
		$campos['qtde_dias'] 	   = $_POST["posto"]['qtde_dias'];
	}

	if ($areaAdmin === false) {
		$campos["tipo_interacao"] = "Ag. Fábrica";
	}

	if (in_array($login_fabrica, [198])) {
		$campos['chamado_interno'] = "t";
		$campos['tipo_interacao']  = "Interação Interna";
	}

	$colunas_adicionais = array(
		"tbl_hd_chamado_extra" => array(),
		"tbl_hd_chamado_posto" => array()
	);

	if($login_fabrica == 35 and $areaAdmin === true and !empty($campos['ordem_de_servico'])){
		$colunas_adicionais['tbl_hd_chamado_extra']['os'] = $campos['ordem_de_servico'];
	}

	if (in_array($login_fabrica, [35]) and !empty($_POST['selectMotivo'])) {
		$campos['selectMotivo'] = $_POST["selectMotivo"];
		$colunas_adicionais['tbl_hd_chamado_extra']['hd_situacao'] = $_POST["selectMotivo"];
	}

	if (in_array($login_fabrica, [169,170])) {

		$campos["origem"]      = $_POST["origem"];
		$campos["sub_item"]    = $_POST["sub_item"];
		$campos["providencia"] = $_POST['providencia'];

		$jsonCamposAdicionais = json_encode([
			"providencia" => $_POST['providencia'],
			"sub_item"    => $_POST['sub_item']
		]);

		$colunas_adicionais['tbl_hd_chamado_extra']['origem'] = "'{$_POST['origem']}'";
		$colunas_adicionais['tbl_hd_chamado_extra']['array_campos_adicionais'] = "'{$jsonCamposAdicionais}'";
	}

	setaCamposAdicionais($campos["tipo_solicitacao"]);

	valida_campos();
	
	if ($fabricaFileUploadOS && $login_fabrica == 151) {

		$queryAnexObrig = "SELECT informacoes_adicionais 
				  FROM tbl_tipo_solicitacao 
				  WHERE fabrica = $login_fabrica
				  AND ativo is TRUE
				  AND tipo_solicitacao = $tipo_solicitacao";

		$resAnexObrig = pg_query($con, $queryAnexObrig);

		$obrigatorios = json_decode(pg_fetch_result($resAnexObrig, 0, "informacoes_adicionais"), 1);

		$anexosInseridos = retorna_anexos_inseridos();
		
		foreach ($obrigatorios["anexos"] as $key => $obrigatorio) {
	
			if (!in_array($obrigatorio, $anexosInseridos)) {

				$msg_erro["msg"][] = "Anexo de " . $obrigatorio . " é obrigatório";
			}
		}
			
	}

	if (!count($msg_erro["msg"])) {
		try {
			if ($campos["tipo_solicitacao"] == "73" && in_array($login_fabrica, [35]) && $_REQUEST['pecas'] == NULL) {
				throw new Exception("Informe pelo menos 1 peça!");
			}
			pg_query($con, "BEGIN");
			
			$hd_chamado = null;

			gravar_atendimento();

			$hd_chamado_item = gravar_interacao($hd_chamado, $campos["descricao_atendimento"], $campos["tipo_interacao"], true);

			if (in_array($login_fabrica, [35,151,169,170]) && $fabricaFileUploadOS) {	
				gravarAnexosBoxUploader();
			} elseif ($fabricaFileUploadOS) {
				gravarAnexosBoxUploaderGeral();
			} else {
				grava_anexo_tdocs();
			}

			pg_query($con, "COMMIT");

			Notifica_Admin($_POST["tipo_solicitacao"], $campos, $hd_chamado);

			header("Location: helpdesk_posto_autorizado_atendimento.php?hd_chamado={$hd_chamado}");
			exit;
		} catch(Exception $e) {
			pg_query($con, "ROLLBACK");
			$msg_erro["msg"][] = $e->getMessage();
		}
	}
}

if ($_POST['indisponibilizar']) {
	$atendente = $_POST['admin'];
	$motivo = $_POST['motivo'];
	$disponibilidade = "indisponivel";
	if (strlen($atendente) > 0 && strlen($disponibilidade) > 0 && strlen($motivo) > 0) {
		try {
			pg_query($con, "BEGIN");
			gravaAdminDisponibilidade($atendente, $disponibilidade, $motivo);
			$recAdminInds = selectAdminsIndisponiveis($atendente);
			pg_query($con, "COMMIT");
			$return = array("success" => utf8_encode("Atendente adicionado a lista de Indisponíveis com Sucesso!"),
					"admin"         => $recAdminInds[0]['admin'],
					"motivo"        => utf8_encode($recAdminInds[0]['nao_disponivel']),
					"nome_completo" => utf8_encode($recAdminInds[0]['nome_completo'])
					);
		} catch (Exception $e) {
			pg_query($con, "ROLLBACK");
			$return = array("error" => utf8_encode($e->getMessage()));
		}
	} else {
		$return = array("error" => utf8_encode("Ocorreu um erro na seleção do Admin, entre em contato com a Telecontrol!"));
	}
	echo json_encode($return);
	exit(0);

}

if ($_POST['disponibilizar']) {
	$atendente = $_POST['admin'];
	if (strlen($atendente) > 0) {
		try {
			pg_query($con, "BEGIN");
			gravaAdminDisponibilidade($atendente);
			pg_query($con, "COMMIT");
			$return = array("success" => utf8_encode("Atendente removido da lista de Indisponíveis com Sucesso!"));
		} catch (Exception $e) {
			pg_query($con, "ROLLBACK");
			$return = array("error" => utf8_encode($e->getMessage()));
		}
	} else {
		$return = array("error" => utf8_encode("Ocorreu um erro na seleção do Admin, entre em contato com a Telecontrol!"));
	}
	echo json_encode($return);
	exit(0);

}

if ($_POST["admin_refresh"]) {
	$admins = array();
	$recAdminDisp = selectAdminsDisponiveis();
	foreach ($recAdminDisp as $admin) {
		$admins[$admin["admin"]] = utf8_encode($admin["nome_completo"]);
	}
	echo json_encode($admins);
	exit(0);
}

if(isset($_POST["pesquisar_helpdesk"])){
	$protocolo          = $_POST["protocolo"];
	$data_inicial       = $_POST["data_inicial"];
	$data_final         = $_POST["data_final"];
	$posto_codigo       = $_POST["posto"]["codigo"];
	$posto_nome         = $_POST["posto"]["nome"];
	$tipo_solicitacao   = $_POST["tipo_solicitacao"];
	$atendente          = $_POST["atendente"];
	$status             = $_POST["status"];

	$providencia        = $_POST["providencia"];
	$origem             = $_POST["origem"];
	$sub_item           = $_POST["sub_item"];

	$estado = $_POST["estado"];

	if ($_POST["csv"]) {
		$status             = utf8_decode($_POST["status"]);
	}

	if(empty($protocolo)){
		try {

			if ($login_fabrica == 30) {
				validaData($data_inicial, $data_final, 6);
			}else if ($login_fabrica == 203){
				validaData($data_inicial, $data_final, 12);
			} else {
				validaData($data_inicial, $data_final, 3);
			}

			list($dia, $mes, $ano) = explode("/", $data_inicial);
            $aux_data_inicial      = $ano."-".$mes."-".$dia;

            list($dia, $mes, $ano) = explode("/", $data_final);
            $aux_data_final        = $ano."-".$mes."-".$dia;

            $condicao = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		} catch (Exception $e) {
			$msg_erro["msg"][] = $e->getMessage();
			return false;
		}

	}else{
		$condicao = " AND tbl_hd_chamado.hd_chamado = {$protocolo} ";
	}

	if(!empty($status)){
		$condicao .= " AND TO_ASCII(tbl_hd_chamado.status, 'LATIN-9') = TO_ASCII('".retira_acentos($status)."', 'LATIN-9') ";
	}

	if(!empty($tipo_solicitacao)){
		$condicao .= " AND tbl_tipo_solicitacao.tipo_solicitacao = {$tipo_solicitacao} ";
	}

	if(!empty($atendente)){
		$condicao .= " AND tbl_hd_chamado.atendente = {$atendente} ";
	}

	if (!empty($estado)) {
		switch ($estado) {
			case 'centro-oeste':
				$xestado = "'GO','MT','MS','DF'";
				break;
			case 'nordeste':
				$xestado = "'MA','PI','CE','RN','PB','PE','AL','SE','BA'";
				break;
			case 'norte':
				$xestado = "'AC','AM','RR','RO','PA','AP','TO'";
				break;
			case 'sudeste':
				$xestado = "'MG','ES','RJ','SP'";
				break;
			case 'sul':
				$xestado = "'PR','SC','RS'";
				break;
			
			default:
				$xestado = "'{$estado}'";
				break;
		}
		
		$condicao .= " AND tbl_posto.estado in ({$xestado}) ";
	}

	if ($areaAdmin === true) {
		if(strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
			$sql = "SELECT tbl_posto_fabrica.posto FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}')))
				";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][] = "Posto não encontrado";
			} else {
				$posto = pg_fetch_result($res, 0, "posto");
				$condicao .= " AND tbl_posto.posto = {$posto} ";
			}
		}
	}else{
		$condicao .= " AND tbl_posto.posto = {$login_posto} ";
		if($login_fabrica == 30) {
			$condicao .= " AND (tbl_tipo_solicitacao.codigo <> 'I' OR tbl_tipo_solicitacao.codigo IS NULL) ";
		}
	}

	if (in_array($login_fabrica, [169,170])) {

		if (!empty($providencia)) {

			$condicao .= "AND JSON_FIELD('providencia', array_campos_adicionais) = '{$providencia}'";

		}

		if (!empty($sub_item)) {

			$condicao .= "AND JSON_FIELD('sub_item', array_campos_adicionais) = '{$sub_item}'";

		}

		if (!empty($origem)) {

			$condicao .= "AND tbl_hd_chamado_extra.origem = '{$origem}'";

		}

	}

	if ($login_fabrica == 35) {
		$cond_data      = " TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') ";
		$cond_data_item = " TO_CHAR(MAX(tbl_hd_chamado_item.data), 'DD/MM/YYYY') ";
		//$cond_data_item = " TO_CHAR(MAX(tbl_hd_chamado_item.data), 'YYYY/MM/DD HH:MI:SS') ";
		$left_hd_situacao = " LEFT JOIN tbl_hd_situacao on tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao  LEFT JOIN tbl_os ON tbl_hd_chamado_extra.os = tbl_os.os"; 
		$campos_hd_chamado_extra = " tbl_os.sua_os as os,  tbl_hd_chamado_extra.pedido, tbl_hd_chamado_extra.hd_situacao,  tbl_hd_situacao.descricao as descricao_motivo,  ";

		$group_by_hd_chamado_extra = " tbl_os.sua_os, tbl_hd_chamado_extra.pedido, tbl_hd_chamado_extra.hd_situacao,  tbl_hd_situacao.descricao ,  ";
		
	} else {
		$cond_data      = " TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY HH24:MI') ";
		$cond_data_item = " TO_CHAR(MAX(tbl_hd_chamado_item.data), 'DD/MM/YYYY HH24:MI') ";
	}

	$sql = "SELECT
			tbl_hd_chamado.hd_chamado,
			tbl_hd_chamado.status,
			{$cond_data} AS data,
			tbl_hd_chamado.data AS data2,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome AS posto,
			UPPER(tbl_posto_fabrica.contato_estado) AS estado,
			$campos_hd_chamado_extra 
			tbl_tipo_solicitacao.descricao AS tipo_solicitacao,
			tbl_admin.nome_completo AS atendente,
			TO_CHAR(tbl_hd_chamado.data_providencia, 'DD/MM/YYYY') AS data_providencia,
			MAX(tbl_hd_chamado_item.data) AS ultima_interacao2,
			tbl_hd_chamado_posto.usuario_sac AS responsavel_solicitacao,
            tbl_hd_chamado_posto.nome_cliente AS nome_cliente,
			tbl_hd_chamado_extra.origem,
			tbl_hd_chamado_extra.array_campos_adicionais,
			{$cond_data_item} AS ultima_interacao
		FROM tbl_hd_chamado
			INNER JOIN tbl_hd_chamado_posto ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_posto.hd_chamado
			INNER JOIN tbl_tipo_solicitacao ON tbl_tipo_solicitacao.tipo_solicitacao = tbl_hd_chamado.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica}
			INNER JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = {$login_fabrica}
			left JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
			LEFT JOIN tbl_hd_chamado_extra on tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_hd_chamado.fabrica = $login_fabrica
			$left_hd_situacao
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_hd_chamado.posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		{$condicao}
		GROUP BY
			tbl_hd_chamado.hd_chamado,
			tbl_hd_chamado.status,
			tbl_hd_chamado.data,
			tbl_hd_chamado.data_providencia,
            tbl_hd_chamado_posto.nome_cliente,
			responsavel_solicitacao,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_posto_fabrica.contato_estado,
			$group_by_hd_chamado_extra
			tbl_tipo_solicitacao.descricao,
			tbl_admin.nome_completo,
			tbl_hd_chamado_extra.origem,
			tbl_hd_chamado_extra.array_campos_adicionais
		ORDER BY tbl_hd_chamado.data DESC";
		


	$resPesquisa = pg_query($con, $sql);
	if(pg_num_rows($resPesquisa) == 0){
		$msg_erro["msg"][] = "Não foi encontrado nenhum atendimento";
	}
}

function setaCamposAdicionais($tipo_solicitacao) {
	global $con, $campos, $regras, $colunas_adicionais, $login_fabrica;

	if (!empty($tipo_solicitacao)) {
		$sqlInformacoesAdicionais = "
			SELECT informacoes_adicionais, campo_obrigatorio
			FROM tbl_tipo_solicitacao
			WHERE fabrica = {$login_fabrica}
			AND tipo_solicitacao = {$tipo_solicitacao}
		";
		$resInformacoesAdicionais = pg_query($con, $sqlInformacoesAdicionais);

		if (pg_num_rows($resInformacoesAdicionais) > 0) {
			$informacoes_adicionais = json_decode(pg_fetch_result($resInformacoesAdicionais, 0, "informacoes_adicionais"), true);
			$campos_obrigatorios    = array_keys(json_decode(pg_fetch_result($resInformacoesAdicionais, 0, "campo_obrigatorio"), true));

			foreach ($informacoes_adicionais as $informacao_adicional => $label) {
				$campo_obrigatorio = (in_array($informacao_adicional, $campos_obrigatorios)) ? true : false;

				switch ($informacao_adicional) {
					case 'os_posto':
						 $campos["ordem_de_servico"] = $_POST["ordem_de_servico"];

						 $colunas_adicionais["tbl_hd_chamado_extra"]["os"] = ($campos["ordem_de_servico"]) ? : "null";

						if ($campo_obrigatorio === true && $campos["produto_garantia"] != "f") {
							$regras["ordem_de_servico"]["obrigatorio"] = true;
						}
						if (!empty($campos["ordem_de_servico"])) {
							$regras["ordem_de_servico"]["function"] = array("valida_os_posto");
						}
						break;
					case 'num_pedido':
						$campos["pedido"] = $_POST["pedido"];

						$colunas_adicionais["tbl_hd_chamado_extra"]["pedido"] = ($campos["pedido"]) ? : "null";

						if ($campo_obrigatorio === true) {
							$regras["pedido"]["obrigatorio"] = true;
						}
						break;
					case 'hd_chamado_sac':
						$campos["protocolo_atendimento"] = $_POST["protocolo_atendimento"];

						$colunas_adicionais["tbl_hd_chamado_posto"]["hd_chamado_sac"] = ($campos["protocolo_atendimento"]) ? : "null";

						if ($campo_obrigatorio === true) {
							$regras["protocolo_atendimento"]["obrigatorio"] = true;
						}
						break;
					case 'produto_os':
						$campos["produto"]["id"]         = $_POST["produto"]["id"];
						$campos["produto"]["referencia"] = $_POST["produto"]["referencia"];
						$campos["produto"]["descricao"]  = $_POST["produto"]["descricao"];

						$colunas_adicionais["tbl_hd_chamado_extra"]["produto"] = ($campos["produto"]["id"]) ? : "null";

						if ($campo_obrigatorio === true) {
							$regras["produto|id"]["obrigatorio"] = true;
						}
						break;
					case 'pedido_pend':
						$campos["pecas"] = $_POST["pecas"];
						if ($login_fabrica == 35) {
							$pecas = "";
							foreach ($campos["pecas"] as $key => $value) {
								$pecas .= $key . "=". $value . ",";
							}
							$pecas = substr($pecas,0, strlen($pecas)-1);
							$colunas_adicionais["tbl_hd_chamado_posto"]["peca_faltante"] = "'" . $pecas . "'";
						}else{
							$colunas_adicionais["tbl_hd_chamado_posto"]["peca_faltante"] = (!empty($campos["pecas"])) ? "'".implode(",", $campos["pecas"])."'" : "''";
						}

						if ($campo_obrigatorio === true) {
							$regras["pecas"]["obrigatorio"] = true;
						}
						break;
					case 'nome_cliente':
						$campos["cliente"] = trim($_POST["cliente"]);

						$colunas_adicionais["tbl_hd_chamado_posto"]["nome_cliente"] = ($campos["cliente"]) ? "'{$campos["cliente"]}'" : "''";

						if ($campo_obrigatorio === true) {
							$regras["cliente"]["obrigatorio"] = true;
						}
						break;
					case 'ticket_atendimento':
						$campos["ticket_atendimento"] = trim($_POST["ticket_atendimento"]);

						$colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["ticket_atendimento"] .= ($campos["ticket_atendimento"]) ? "{$campos["ticket_atendimento"]}" : "''";

						if ($campo_obrigatorio === true) {
							$regras["ticket_atendimento"]["obrigatorio"] = true;
						}
						break;
					case 'cod_localizador':
						$campos["cod_localizador"] = trim($_POST["cod_localizador"]);

						$colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["cod_localizador"] .= ($campos["cod_localizador"]) ? "{$campos["cod_localizador"]}" : "''";

						if ($campo_obrigatorio === true) {
							$regras["cod_localizador"]["obrigatorio"] = true;
						}
						break;
					case 'pre_logistica':
						$campos["pre_logistica"] = trim($_POST["pre_logistica"]);

						$colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["pre_logistica"] .= ($campos["pre_logistica"]) ? "{$campos["pre_logistica"]}" : "''";

						if ($campo_obrigatorio === true) {
							$regras["pre_logistica"]["obrigatorio"] = true;
						}
						break;
				}
			}
			if(isset($colunas_adicionais["tbl_hd_chamado_extra"])){
				if(isset($colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["ticket_atendimento"])){
					$campos_adicionais["ticket_atendimento"] = $colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["ticket_atendimento"];
				}
				if(isset($colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["cod_localizador"])){
					$campos_adicionais["cod_localizador"] = $colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["cod_localizador"];
				}
				if(isset($colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["pre_logistica"])){
					$campos_adicionais["pre_logistica"] = $colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"]["pre_logistica"];
				}
			}

			if(count($campos_adicionais) > 0){
				$campos_adicionais = json_encode($campos_adicionais);
				$colunas_adicionais["tbl_hd_chamado_extra"]["array_campos_adicionais"] = "'".$campos_adicionais."'";
			}
		}
	}
}

function buscarAtendente($posto, $tipo_solicitacao, $posto_autorizado_dados) {
	global $con, $login_fabrica;

	if (empty($posto)) {
		throw new Exception("Erro ao buscar atendente, posto autorizado não informado");
	}

	$cod_ibge_pa = $posto_autorizado_dados["cod_ibge"];
	$estado_pa   = strtoupper($posto_autorizado_dados["contato_estado"]);

	if(in_array($login_fabrica,[35,72])){
		$sql_sap = "SELECT tbl_posto_fabrica.admin_sap FROM tbl_posto_fabrica JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} AND posto = {$posto} and tbl_admin.ativo";
		$res = pg_query($con, $sql_sap);
		if (pg_num_rows($res) > 0) {
			$ad_sap = pg_fetch_result($res, 0, "admin_sap");
		}
	}
	if(!empty($ad_sap)){
		return $ad_sap;
	}else{
		if ($cod_ibge_pa) {
			$sql = "SELECT tbl_admin_atendente_estado.admin
					FROM tbl_admin_atendente_estado
						INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
					WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
					AND tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao}
					AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge_pa}
					AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
					AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
					AND tbl_admin.ativo IS TRUE 
					{$whereClassificacao}
					order by random()";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				return pg_fetch_result($res, 0, "admin");
			}
		}
		if ($estado_pa) {
			$sql = "SELECT tbl_admin_atendente_estado.admin
					FROM tbl_admin_atendente_estado
						INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
					WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
					AND tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao}
					AND tbl_admin_atendente_estado.cod_ibge IS NULL
					AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
					AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
					AND tbl_admin.ativo IS TRUE
					{$whereClassificacao}
					order by random()";
			$res = pg_query($con, $sql);
		// exit(nl2br($sql));
			if (pg_num_rows($res) > 0) {
				return pg_fetch_result($res, 0, "admin");
			}
		}

		$sql = "SELECT tbl_admin_atendente_estado.admin
				FROM tbl_admin_atendente_estado
					INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
				WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
				AND tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao}
				AND tbl_admin_atendente_estado.cod_ibge IS NULL
				AND (tbl_admin_atendente_estado.estado IS NULL OR LENGTH(tbl_admin_atendente_estado.estado) = 0)
				AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
				AND tbl_admin.ativo IS TRUE
				{$whereClassificacao}
				order by random()";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return pg_fetch_result($res, 0, "admin");
		}

	}

	throw new Exception("Nenhum atendente encontrado para o tipo de solicitacação");
}
function buscarInformacoesPosto($posto) {
	global $con, $login_fabrica;

	if (empty($posto)) {
		throw new Exception("Erro ao buscar informações do posto autorizado");
	}

	$sql = "SELECT
				tbl_posto.nome,
				tbl_posto_fabrica.contato_cep,
				tbl_posto_fabrica.contato_estado,
				COALESCE(replace(tbl_ibge.cidade,'''',''), 'null') AS cidade,
				tbl_posto_fabrica.contato_endereco,
				tbl_posto_fabrica.contato_numero,
				tbl_posto_fabrica.contato_complemento,
				tbl_posto_fabrica.contato_fone_comercial,
				tbl_posto_fabrica.contato_email,
				tbl_posto.cnpj,
				tbl_posto_fabrica.cod_ibge
			FROM tbl_posto_fabrica
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_ibge ON tbl_ibge.cod_ibge = tbl_posto_fabrica.cod_ibge
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_posto_fabrica.posto = {$posto}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		throw new Exception("Posto Autorizado não encontrado");
	}

	return array(
		"nome"                   => pg_fetch_result($res, 0, "nome"),
		"contato_cep"            => pg_fetch_result($res, 0, "contato_cep"),
		"contato_estado"         => pg_fetch_result($res, 0, "contato_estado"),
		"cidade"                 => pg_fetch_result($res, 0, "cidade"),
		"contato_endereco"       => pg_fetch_result($res, 0, "contato_endereco"),
		"contato_numero"         => pg_fetch_result($res, 0, "contato_numero"),
		"contato_complemento"    => pg_fetch_result($res, 0, "contato_complemento"),
		"contato_fone_comercial" => pg_fetch_result($res, 0, "contato_fone_comercial"),
		"contato_email"          => pg_fetch_result($res, 0, "contato_email"),
		"cnpj"                   => pg_fetch_result($res, 0, "cnpj"),
		"cod_ibge"               => pg_fetch_result($res, 0, "cod_ibge")
	);
}

function tipoSolicitacaoCamposAdicionais($tipo_solicitacao) {
	if (empty($tipo_solicitacao)) {
		throw new Exception("Ocorreu um erro ao gravar as informações adicionais, tipo de solicitação não informado");
	}
}

function gravar_atendimento() {
	global $con, $campos, $login_fabrica, $login_admin, $login_posto, $colunas_adicionais, $areaAdmin, $hd_chamado, $buscar_atendente, $verifica_atendimento_aberto, $enviar_email_helpdesk;

	$posto_autorizado_dados = buscarInformacoesPosto($campos["posto"]["id"]);

	if(!in_array($login_fabrica,[169,170])){
		if($areaAdmin == true){
			if($login_fabrica == 35){
				$tipo_solicitacao = $campos['tipo_solicitacao'];
				$estado = $posto_autorizado_dados['contato_estado'];
				$sql_atendente = "SELECT
							tbl_admin_atendente_estado.admin
							FROM tbl_admin_atendente_estado
							WHERE tbl_admin_atendente_estado.fabrica = $login_fabrica AND
							  tbl_admin_atendente_estado.admin = $login_admin AND
							  tbl_admin_atendente_estado.tipo_solicitacao = '$tipo_solicitacao'
							AND tbl_admin_atendente_estado.estado = '{$estado}'";
						$res_atendente = pg_query($con, $sql_atendente);
				if(pg_num_rows($res_atendente) > 0){
					$atendente = pg_fetch_result($res_atendente, 'admin');
				}else{
					$atendente = call_user_func($buscar_atendente, $campos["posto"]["id"], $campos["tipo_solicitacao"], $posto_autorizado_dados);
				}
			}else{
				$atendente = call_user_func($buscar_atendente, $campos["posto"]["id"], $campos["tipo_solicitacao"], $posto_autorizado_dados);
			}
		}else{
			$atendente = call_user_func($buscar_atendente, $campos["posto"]["id"], $campos["tipo_solicitacao"], $posto_autorizado_dados);
		}

	} else {
		$atendente = $login_admin;
		$campos['posto']['chamado_interno'] = "t";
	}

	if(strlen($verifica_atendimento_aberto) > 0 and !empty($campos['ordem_de_servico'])){
		call_user_func($verifica_atendimento_aberto,$campos['ordem_de_servico']);
	}
	
	if(!empty($colunas_adicionais["tbl_hd_chamado_extra"]["os"]) and $colunas_adicionais["tbl_hd_chamado_extra"]["os"] != 'null'){
		$colunas_adicionais["tbl_hd_chamado_extra"]["os"] = verificaOsRevenda($colunas_adicionais["tbl_hd_chamado_extra"]["os"]);
	}

	if(strlen($colunas_adicionais['tbl_hd_chamado_posto']['hd_chamado_sac']) > 0 AND $colunas_adicionais['tbl_hd_chamado_posto']['hd_chamado_sac'] != "null"){

		$sql = "SELECT hd_chamado
			FROM tbl_hd_chamado
			WHERE fabrica_responsavel = {$login_fabrica}
			AND hd_chamado = {$colunas_adicionais['tbl_hd_chamado_posto']['hd_chamado_sac']}";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) == 0){
			throw new Exception("Protocolo de Atendimento ".$colunas_adicionais['tbl_hd_chamado_posto']['hd_chamado_sac']." não encontrado");
		}
	}

	$titulo = 'Help-Desk Posto';
	$campo_data_providencia = $valor_data_providencia = '';
	if ($areaAdmin === true) {
		$atendente_responsavel = $login_admin;
		$status = "Ag. Posto";
		if ($campos['posto']['chamado_interno'] == 't') {
			$titulo = 'Help-Desk Admin';

			if (!in_array($login_fabrica, [169,170])) {
				$campo_data_providencia = ',data_providencia';
				$valor_data_providencia = ",current_date + ".$campos['posto']['qtde_dias'];
			} else {
				$status = "Call Center";
			}

		}
	} else {
		$atendente_responsavel = $atendente;
		$status = "Ag. Fábrica";
	}

	$sql = "INSERT INTO tbl_hd_chamado
				(
					fabrica,
					fabrica_responsavel,
					atendente,
					admin,
					posto,
					tipo_solicitacao,
					status,
					titulo
					$campo_data_providencia
				)
			VALUES
				(
					{$login_fabrica},
					{$login_fabrica},
					{$atendente},
					{$atendente_responsavel},
					{$campos['posto']['id']},
					{$campos['tipo_solicitacao']},
					'{$status}',
					'$titulo'
					$valor_data_providencia
				)
			RETURNING hd_chamado";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar atendimento #1");
	}

	$hd_chamado = pg_fetch_result($res, 0, "hd_chamado");
	$nome_posto_inserte = substr($posto_autorizado_dados['nome'], 0,50);
	$fone = substr($posto_autorizado_dados['contato_fone_comercial'],0,20);
 	
 	if (!isset($campos['produto_garantia'])) {
       
       $campos['produto_garantia'] = 'f';
   	}

	$sql = "INSERT INTO tbl_hd_chamado_extra
				(
					hd_chamado,
					nome,
					cep,
					cidade,
					endereco,
					numero,
					complemento,
					fone,
					email,
					cpf,
					garantia
					".((count($colunas_adicionais["tbl_hd_chamado_extra"]) > 0) ? ",".implode(",", array_keys($colunas_adicionais["tbl_hd_chamado_extra"])) : "")."
				)
			VALUES
				(
					{$hd_chamado},
					'{$nome_posto_inserte}',
					'{$posto_autorizado_dados['contato_cep']}',
					COALESCE((SELECT cidade FROM tbl_cidade WHERE LOWER(fn_retira_especiais(nome)) = LOWER(fn_retira_especiais('{$posto_autorizado_dados['cidade']}')) LIMIT 1), NULL),
					'{$posto_autorizado_dados['contato_endereco']}',
					'{$posto_autorizado_dados['contato_numero']}',
					'{$posto_autorizado_dados['contato_complemento']}',
					'$fone',
					'{$posto_autorizado_dados['contato_email']}',
					'{$posto_autorizado_dados['cnpj']}',
					'{$campos['produto_garantia']}'
					".((count($colunas_adicionais["tbl_hd_chamado_extra"]) > 0) ? ",".implode(",", $colunas_adicionais["tbl_hd_chamado_extra"]) : "")."
				)";

	$res = pg_query($con, $sql);
	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar atendimento #2");
	}

	$sql = "INSERT INTO tbl_hd_chamado_posto
				(
					hd_chamado,
					usuario_sac
					".((count($colunas_adicionais["tbl_hd_chamado_posto"]) > 0) ? ",".implode(",", array_keys($colunas_adicionais["tbl_hd_chamado_posto"])) : "")."
				)
			VALUES
				(
					{$hd_chamado},
					substr('{$campos['responsavel_solicitacao']}',0,30)
					".((count($colunas_adicionais["tbl_hd_chamado_posto"]) > 0) ? ",".implode(",", $colunas_adicionais["tbl_hd_chamado_posto"]) : "")."
				)";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		//throw new Exception("Erro ao gravar atendimento #3");
		throw new Exception("Erro ao gravar atendimento, protocolo de atendimento inválido".pg_last_error());
	}else{
		if ($login_fabrica == 30) {
			$status = "false";
			if (in_array($campos["tipo_solicitacao"], array(14, 15, 16, 17, 18))) //Solicitação de troca
				$status = "true";

			$sql = "SELECT os FROM tbl_os WHERE sua_os = '".$campos['ordem_de_servico']."' AND fabrica = {$login_fabrica};";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) > 0) {
				$os = pg_fetch_result($res, 0, "os");

				$sql = "UPDATE tbl_os SET status_checkpoint = 33 WHERE os = {$os} and finalizada isnull";
				pg_query($con, $sql);
				if (strlen(pg_last_error()) > 0)
					throw new Exception("Erro ao gravar atendimento, não foi possível atualizar a OS.");

				$sql = "SELECT os FROM tbl_os_campo_extra WHERE os = {$os}";
				$res = pg_query($con, $sql);
				if (pg_num_rows($res) == 0) {
					$sql = "INSERT INTO tbl_os_campo_extra(os, fabrica, os_bloqueada) VALUES({$os}, {$login_fabrica}, {$status})";
				}else{
					$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = {$status} WHERE os = {$os} AND fabrica = {$login_fabrica}";
				}
				pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao gravar atendimento, não foi possível bloquear a OS. ".pg_last_error());
				}			
			}
		}

		if($areaAdmin !== true || in_array($login_fabrica, [169,170])){
		    if(strlen($enviar_email_helpdesk) > 0){
				call_user_func_array($enviar_email_helpdesk,array($campos['tipo_solicitacao'],$campos['ordem_de_servico'],$hd_chamado));
		    }

		    #Notifica_Admin($_POST["tipo_solicitacao"], $campos, $hd_chamado);
		}
	}
}

function Envia_Email($email, $hd_chamado, $descricao, $transferido = 0){
	global $login_fabrica_nome, $tipo_interacao;

	$email_origem  = "noreply@telecontrol.com.br";
	$email_destino = $email;
	if ($transferido == 1) {
		$assunto   = "Atendimento Help-Desk {$hd_chamado} transferido";
		$corpo	   = "<b>Mensagem transferida</b><br />{$descricao}";
	}else{
		$assunto   = "Nova interação no atendimento Help-Desk {$hd_chamado} - $login_fabrica_nome ";
		$corpo	   = "<b>Mensagem</b><br />{$descricao}";
	}

	if($tipo_interacao == 'Finalizado' AND $login_fabrica_nome == 'Mallory'){
		$assunto   = "Finalizado Help-Desk {$hd_chamado} - $login_fabrica_nome ";
		$corpo	   = "<b>Mensagem</b><br />{$descricao}";
	}

	$corpo = "<b> Nota: Este e-mail é gerado automaticamente. <br> POR FAVOR NÃO RESPONDER ESTA MENSAGEM </b> <br><br> ".$corpo;

	$body_top 	   = "--Message-Boundary\n";
	$body_top 	  .= "Content-type: text/html \n";
	//charset=iso-8859-1
	$body_top 	  .= "Content-transfer-encoding: 7BIT\n";
	$body_top 	  .= "Content-description: Mail message body\n\n";

	@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top ");
}

function Notifica_Admin($tipo_solicitacao, $campos, $atendimento){
	global $con, $login_fabrica;

	if ($login_fabrica == 30 && $campos['chamado_interno'] == 't') {
		return;
	}

	$sql          = "SELECT nome,estado FROM tbl_posto WHERE posto = ".$campos["posto"]["id"];
	$res          = pg_query($con,$sql);
	$posto 	      = pg_fetch_result($res, 0, "nome");
	$estado_posto = pg_fetch_result($res, 0, 'estado'); 

	$sql = "SELECT
				tbl_admin.email as email
			FROM tbl_admin_atendente_estado
				JOIN tbl_admin ON (tbl_admin_atendente_estado.admin = tbl_admin.admin)
			WHERE
				tbl_admin_atendente_estado.fabrica = {$login_fabrica} AND
				tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao} AND
				(tbl_admin_atendente_estado.estado = '$estado_posto' OR tbl_admin_atendente_estado.estado IS NULL)";

	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$error = '';
		for ($i=0; $i < pg_num_rows($res); $i++) {
			$email_origem  = "noreply@telecontrol.com.br";
			$email_destino = pg_fetch_result($res, $i, "email");
			$assunto       = "Novo atendimento cadastrado pelo posto {$posto}";
			$corpo 		   = "O responsável <b>{$campos['responsavel_solicitacao']}</b> cadastrou um novo atendimento (<b>{$atendimento}</b>).<br /><br /><b>Descrição</b><br />{$campos['descricao_atendimento']}\n";

			$body_top 	   = "--Message-Boundary\n";
			$body_top 	  .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top 	  .= "Content-transfer-encoding: 7BIT\n";
			$body_top 	  .= "Content-description: Mail message body\n\n";

			if (!@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
				break;
			}
		}
	}
}

function gravar_interacao($hd_chamado, $descricao, $tipo_interacao, $primeira_interacao = false) {
	global $con, $login_posto, $login_admin, $areaAdmin, $login_fabrica;

	if ($login_fabrica == 30 && isset($_GET['callcenter']))
		$tipo_interacao = "Exigir Comprovante";

	if (in_array($login_fabrica, [198])) {
		$tipo_interacao = "Interação Interna";		
	}

	$descricao = trim($descricao);

	if (empty($hd_chamado)) {
		throw new Exception("Erro ao interagir no atendimento, número do atendimento não informado");
	}

	if (empty($descricao)) {
		throw new Exception("Informe o texto da interação");
	}

	if ($areaAdmin === true) {
		$admin = $login_admin;
		$posto = "null";
		$tipo_interacao = (strlen(trim($tipo_interacao)) == 0) ? "Ag. Posto" : $tipo_interacao; 
	} else {
		$admin = "null";
		$posto = $login_posto;
		$tipo_interacao = (strlen(trim($tipo_interacao)) == 0) ? "Ag. Fábrica" : $tipo_interacao; 
	}

	$interacao_interna = ($tipo_interacao == "Interação Interna") ? "true" : "false";

	if (in_array($login_fabrica, [151]) && $tipo_interacao == "Cancelado") {

		$sqlCancelaOsForaGarantia = "
				  UPDATE tbl_os SET excluida = true
				  WHERE os = (
				  		SELECT tbl_hd_chamado_extra.os
				  		FROM tbl_hd_chamado_extra
				  		JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				  		JOIN tbl_os ON tbl_hd_chamado_extra.os = tbl_os.os
				  		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				  		WHERE tbl_hd_chamado_extra.hd_chamado = {$hd_chamado}
				  		AND (extract(year from age(tbl_os.data_abertura, tbl_os.data_nf)) * 12 +
				  		extract(month from age(tbl_os.data_abertura, tbl_os.data_nf))) >= tbl_produto.garantia
				  		AND tbl_hd_chamado.titulo = 'Help-Desk Posto'
				  )
				  ";
		$resCancelaOsForaGarantia = pg_query($con, $sqlCancelaOsForaGarantia);

	}

	if (in_array($login_fabrica, [169,170]) && $primeira_interacao == true) {
		$tipo_interacao = "Call Center";
	}

	$sql = "INSERT INTO tbl_hd_chamado_item
				(
					hd_chamado,
					admin,
					posto,
					comentario,
					interno,
					status_item
				)
			VALUES
				(
					{$hd_chamado},
					{$admin},
					{$posto},
					'{$descricao}',
					{$interacao_interna},
					'{$tipo_interacao}'
				) RETURNING hd_chamado_item";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao interagir no atendimento #4");
	}

	if ($tipo_interacao == "Resposta Conclusiva") {
		if ($login_fabrica == 30) {
			$sql = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = false WHERE os = ".pg_fetch_result($res, 0, "os");
				pg_query($con, $sql);
				$sql = "UPDATE tbl_os SET status_checkpoint = 4 WHERE os = ".pg_fetch_result($res, 0, "os")." AND fabrica = {$login_fabrica} and finalizada isnull";
				pg_query($con, $sql);
			}
		}
		#gravaDuracaoAtendimento($hd_chamado);
		updateStatus($hd_chamado, 'Ag. Conclusão');
	}

	if ($tipo_interacao == "Ag. Finalização") {
		#gravaDuracaoAtendimento($hd_chamado);
		updateStatus($hd_chamado, 'Ag. Finalização');
	}

	if (trim($tipo_interacao) == "Exigir Comprovante") {
		updateStatus($hd_chamado, "Exigir Comprovante");
		$sql = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
		$res = pg_query($con, $sql);
		$os  = pg_fetch_result($res, 0, "os");

		$sql = "UPDATE tbl_os SET status_checkpoint = 32 WHERE os = {$os} and finalizada isnull";
		pg_query($con, $sql);

		$sql = "SELECT
					contato_email
				FROM tbl_hd_chamado
					JOIN tbl_posto_fabrica ON(tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}) WHERE tbl_hd_chamado.fabrica = {$login_fabrica} AND tbl_hd_chamado.hd_chamado = {$hd_chamado};";

		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			$contato_email = pg_fetch_result($res, 0, "contato_email");
			Envia_Email($contato_email, $hd_chamado, "O chamado {$hd_chamado} esta aguardando o envio de comprovante solicitado pelo admin. Favor verificar este chamado!");
		}
	}

	if (in_array($tipo_interacao, array("Eng. Servicos", "Call Center", "Ag. Posto", "Ag. Fábrica", "Cancelado", "Finalizado"))) {
		
		if ($login_fabrica == 30 and $tipo_interacao == "Finalizado") {
			$sql = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				$os = pg_fetch_result($res, 0, "os");
				if(!empty($os)) {
					$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = false WHERE os = $os";				
					$res = pg_query($con, $sql);
					$sql = "UPDATE tbl_os SET status_checkpoint = 4 WHERE os = $os AND fabrica = {$login_fabrica} and finalizada isnull";
					$res = pg_query($con, $sql);
				}
			}
		}
		updateStatus($hd_chamado, $tipo_interacao);
	}
	return pg_fetch_result($res, 0, 0);
}

function gravaDataProvidencia($hd_chamado, $data_providencia)
{
    global $con;

    $t = pg_query($con, "BEGIN");

    $up = pg_query(
        $con,
        "UPDATE tbl_hd_chamado SET data_providencia = '$data_providencia' WHERE hd_chamado = $hd_chamado"
    );

    if (pg_affected_rows($up) > 1) {
        $t = pg_query($con, "ROLLBACK");
        return false;
    }

    $t = pg_query($con, "COMMIT");

    return true;
}

function updateStatus($hd_chamado, $status) {
	global $con, $login_fabrica;

	if (empty($hd_chamado)) {
		throw new Exception("Erro ao atualizar status, atendimento não informado");
	}

	if (empty($status)) {
		throw new Exception("Erro ao atualizar status, status não informado");
	}

	$sql = "UPDATE tbl_hd_chamado SET status = '{$status}' WHERE fabrica = {$login_fabrica} AND hd_chamado = {$hd_chamado}";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Ocorreu um erro ao atualizar o status do atendimento");
	}
}

function updateAtendente($hd_chamado = null, $atendente = null) {
	global $con, $login_fabrica;

	if ($hd_chamado == null) {
		throw new Exception("Erro ao atualizar status, atendimento não informado");
	}

	if ($atendente == null) {
		throw new Exception("Erro ao atualizar status, status não informado");
	}

	$sql = "UPDATE tbl_hd_chamado SET atendente = {$atendente} WHERE fabrica = {$login_fabrica} AND hd_chamado = {$hd_chamado};";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Ocorreu um erro ao atualizar o status do atendimento");
	}
}

/*
 *
 * Função para alterar disponibilidade do admin para atendimento de chamado
 * Data: 17/11/2015
 *
 */

function gravaAdminDisponibilidade($admin = null, $disponibilidade = null, $motivo = null) {

	global $con, $login_fabrica;

	if ($admin == null) {
		throw new Exception("É necessário adicionar um Atendente!");
	}

	if ($disponibilidade == 'indisponivel') {
		if ($motivo == null) {
			throw new Exception("É necessário adicionar um motivo!");
		} else {
			$sql = "UPDATE tbl_admin
				SET nao_disponivel = '{$motivo}'
				WHERE fabrica = {$login_fabrica}
				AND admin = {$admin}
				AND ativo IS TRUE
				AND callcenter_supervisor IS TRUE";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Ocorreu um erro na gravação de indisponibilidade do Atendente!");
			}
		}
	} else {
		$sql = "UPDATE tbl_admin
			SET nao_disponivel = NULL
			WHERE fabrica = {$login_fabrica}
			AND admin = {$admin}
			AND ativo IS TRUE
			AND callcenter_supervisor IS TRUE";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Ocorreu um erro na gravação de disponibilidade do Atendente!");
		}
	}

}

/*
 *
 * Função para buscar admins disponíveis para atendimento de chamado
 * Data: 17/11/2015
 *
 */

function selectAdminsDisponiveis(){

	global $con, $login_fabrica;

	$cond = ($login_fabrica == 35) ? "  AND (admin_sap IS TRUE OR callcenter_supervisor IS TRUE) " :
			(in_array($login_fabrica,[11,160,172])) ? " AND admin_sap IS TRUE " : " AND callcenter_supervisor IS TRUE ";

	$cond = (in_array($login_fabrica, [198])) ? ''  : $cond;

	$sql = "SELECT admin, nome_completo
		FROM tbl_admin
		WHERE fabrica = {$login_fabrica}
		AND ativo IS TRUE
		{$cond}
		AND (nao_disponivel IS NULL OR LENGTH(nao_disponivel) = 0)
		ORDER BY nome_completo ASC";

	if (in_array($login_fabrica, [169,170])) {

		$sql = "SELECT admin, nome_completo
			  FROM tbl_admin
			 WHERE tbl_admin.fabrica = {$login_fabrica}
			 AND JSON_FIELD('suporte_tecnico', parametros_adicionais) = 't'
			 ORDER BY nome_completo ASC";

	}

	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Ocorreu um erro na busca dos Atendentes!");
	} else {
		return pg_fetch_all($res);
	}
}

/*
 *
 * Função para buscar admins indisponíveis para atendimento de chamado
 * Data: 17/11/2015
 *
 */

function selectAdminsIndisponiveis($admin = null){

	global $con, $login_fabrica;

	if ($admin == null) {
		$sql = "SELECT admin, nome_completo, nao_disponivel
			FROM tbl_admin
			WHERE fabrica = {$login_fabrica}
			AND ativo IS TRUE
			AND callcenter_supervisor IS TRUE
			AND (nao_disponivel IS NOT NULL OR LENGTH(nao_disponivel) > 0)
			ORDER BY nome_completo ASC;";
	} else {
		$sql = "SELECT admin, nome_completo, nao_disponivel
			FROM tbl_admin
			WHERE fabrica = {$login_fabrica}
			AND ativo IS TRUE
			AND callcenter_supervisor IS TRUE
			AND (nao_disponivel IS NOT NULL OR LENGTH(nao_disponivel) > 0)
			AND admin = {$admin};";
	}

	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Ocorreu um erro na busca dos Atendentes!");
	} else {
		return pg_fetch_all($res);
	}
}

/*function gravaDuracaoAtendimento($hd_chamado) {
	global $con, $login_fabrica;

	if (empty($hd_chamado)) {
		throw new Exception("Erro ao gravar duração do atendimento, número do atendimento não informado");
	}

	$sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
	$res = pg_query($con, $sql);

	$array_campos_adicionais = pg_fetch_result($res, 0, "array_campos_adicionais");

	if (empty($array_campos_adicionais)) {
		$array_campos_adicionais = array();
	} else {
		$array_campos_adicionais = json_decode($array_campos_adicionais, true);
	}

	$sql = "UPDATE tbl_hd_chamado SET
				duracao = (current_timestamp - data)
			WHERE fabrica = {$login_fabrica}
			AND hd_chamado = {$hd_chamado}";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar duração do atendimento");
	}
}*/

if (isset($_GET["hd_chamado"])) {
	$hd_chamado = $_GET["hd_chamado"];

	if ($areaAdmin == false) {
		$whereLoginPosto = "AND tbl_hd_chamado.posto = {$login_posto}";
	}

	$where = "AND tbl_hd_chamado.titulo = 'Help-Desk Posto'";
	if ($login_fabrica == 30) {
		$where = "AND (tbl_hd_chamado.titulo = 'Help-Desk Posto' OR tbl_hd_chamado.titulo = 'Help-Desk Admin')";
	}

	$sqlAtendimento = "
		SELECT
			tbl_hd_chamado.hd_chamado,
			tbl_hd_chamado.status,
			tbl_posto_fabrica.posto AS posto_id,
			tbl_posto.nome AS posto_nome,
			tbl_posto_fabrica.codigo_posto AS posto_codigo,
			tbl_hd_chamado_posto.usuario_sac AS responsavel_solicitacao,
			tbl_tipo_solicitacao.descricao AS tipo_solicitacao,
			tbl_hd_chamado_extra.garantia AS produto_garantia,
			tbl_hd_chamado_extra.os AS ordem_de_servico,
			tbl_hd_chamado_extra.pedido AS pedido,
			tbl_hd_chamado_extra.array_campos_adicionais,
			tbl_hd_chamado_posto.hd_chamado_sac AS protocolo_atendimento,
			(tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto,
			tbl_hd_chamado_posto.nome_cliente AS cliente,
            tbl_hd_chamado_posto.peca_faltante AS pecas,
			TO_CHAR(tbl_hd_chamado.data_providencia, 'DD/MM/YYYY') AS data_providencia,
			(select admin from tbl_hd_chamado_item a where a.hd_chamado = tbl_hd_chamado.hd_chamado order by hd_chamado_item limit 1) as admin_abre,
			tbl_hd_chamado_extra.array_campos_adicionais,
			tbl_hd_chamado_extra.origem
		FROM tbl_hd_chamado
		INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
		INNER JOIN tbl_hd_chamado_posto ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_posto.hd_chamado
		INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_hd_chamado.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		INNER JOIN tbl_tipo_solicitacao ON tbl_tipo_solicitacao.tipo_solicitacao = tbl_hd_chamado.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica}
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = {$login_fabrica}
		WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		AND tbl_hd_chamado.hd_chamado = {$hd_chamado}
		
		{$whereLoginPosto}
	";
	$resAtendimento = pg_query($con, $sqlAtendimento);

	if (!pg_num_rows($resAtendimento)) {
		$atendimento_nao_encontrado = true;
	} else {
		$_RESULT = array(
			"status"                  => pg_fetch_result($resAtendimento, 0, "status"),
			"posto_codigo"            => pg_fetch_result($resAtendimento, 0, "posto_codigo"),
			"posto_nome"              => pg_fetch_result($resAtendimento, 0, "posto_nome"),
			"responsavel_solicitacao" => pg_fetch_result($resAtendimento, 0, "responsavel_solicitacao"),
			"tipo_solicitacao"        => pg_fetch_result($resAtendimento, 0, "tipo_solicitacao"),
			"produto_garantia"        => pg_fetch_result($resAtendimento, 0, "produto_garantia"),
			"ordem_de_servico"        => pg_fetch_result($resAtendimento, 0, "ordem_de_servico"),
			"pedido"                  => pg_fetch_result($resAtendimento, 0, "pedido"),
			"protocolo_atendimento"   => pg_fetch_result($resAtendimento, 0, "protocolo_atendimento"),
			"produto"                 => pg_fetch_result($resAtendimento, 0, "produto"),
			"cliente"                 => pg_fetch_result($resAtendimento, 0, "cliente"),
			"pecas"                   => pg_fetch_result($resAtendimento, 0, "pecas"),
			"data_providencia" => pg_fetch_result($resAtendimento, 0, "data_providencia"),
			"admin_abre"              => pg_fetch_result($resAtendimento, 0, "admin_abre"),
			"array_campos_adicionais" => pg_fetch_result($resAtendimento, 0, "array_campos_adicionais")
		);

		if (in_array($login_fabrica, [169,170])) {

			$_RESULT["origem"] = pg_fetch_result($resAtendimento, 0, "origem");

			$arrCamposJson = json_decode(pg_fetch_result($resAtendimento, 0, "array_campos_adicionais"), true);

			$_RESULT["providencia"] = $arrCamposJson["providencia"];
			$_RESULT["sub_item"]    = $arrCamposJson["sub_item"];
		}

		$informacoes_adicionais = array();

		if (!empty($_RESULT["ordem_de_servico"])) {
			$informacoes_adicionais[] = "ordem_de_servico";

			$sql = "SELECT sua_os FROM tbl_os WHERE os = ".$_RESULT["ordem_de_servico"]." AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				$_RESULT["sua_os"] = pg_fetch_result($res, 0, "sua_os");
			}

		}

		if (!empty($_RESULT["pedido"])) {
			$informacoes_adicionais[] = "pedido";
		}

		if (!empty($_RESULT["protocolo_atendimento"])) {
			$informacoes_adicionais[] = "protocolo_atendimento";
		}

		if (!empty($_RESULT["produto"])) {
			$informacoes_adicionais[] = "produto";
		}

		if (!empty($_RESULT["cliente"])) {
			$informacoes_adicionais[] = "cliente";
		}

		if (!empty($_RESULT["pecas"])) {
			$informacoes_adicionais[] = "pecas";
		}
		$campos_adicionais_new = json_decode($_RESULT['array_campos_adicionais'], true);
		if (!empty($campos_adicionais_new["ticket_atendimento"])) {
			$informacoes_adicionais[] = "ticket_atendimento";
			$informacoes_adicionais['ticket_atendimento'] = $campos_adicionais_new["ticket_atendimento"];
		}
		if (!empty($campos_adicionais_new["cod_localizador"])) {
			$informacoes_adicionais[] = "cod_localizador";
			$informacoes_adicionais['cod_localizador'] = $campos_adicionais_new["cod_localizador"];
		}
		if (!empty($campos_adicionais_new["pre_logistica"])) {
			$informacoes_adicionais[] = "pre_logistica";
			$informacoes_adicionais['pre_logistica'] = $campos_adicionais_new["pre_logistica"];
		}
	}
}

function verificaOsRevenda($os = ""){

	if(strlen($os) > 0){

		global $con, $login_fabrica, $login_posto;

		if(strstr($os, "-") == true){

			$sql = "SELECT os FROM tbl_os WHERE posto = {$login_posto} AND fabrica = {$login_fabrica} AND sua_os = '{$os}'";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				return pg_fetch_result($res, 0, "os");
			}else{
				throw new Exception("Nenhuma os foi encontrada vinculada a essa Os Revenda!");
			}

		}

	}

	return $os;
}

