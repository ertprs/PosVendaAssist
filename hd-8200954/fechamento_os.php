<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
$areaAdmin = (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin) {
    $admin_privilegios = "call_center";
    include __DIR__."/admin/autentica_admin.php";
} else {
    include __DIR__."/autentica_usuario.php";
}

include 'funcoes.php';
include 'anexaNF_inc.php';
$xfechamentoOs = false;
if ($login_fabrica == 184) {

	$xfechamentoOs = true;
}


include __DIR__."/os_cadastro_unico/fabricas/regras.php";

if (file_exists(__DIR__."/os_cadastro_unico/fabricas/$login_fabrica/regras.php")) { 
    include __DIR__."/os_cadastro_unico/fabricas/$login_fabrica/regras.php";
}

$btn_acao = $_REQUEST['btn_acao'];
$listar = $_REQUEST['listar'];

if ($_POST["ajax"] == "gravaQtdeHoras" AND strlen($_POST["horas_trabalhadas"]) > 0){

	$hora = $_POST["horas_trabalhadas"];
	$os   = $_POST["os"];

	$sql = "SELECT os, campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");
		$campos_adicionais = json_decode($campos_adicionais, true);
		$campos_adicionais["horas_trabalhadas"] = "$horas_trabalhadas";
		$campos_adicionais = json_encode($campos_adicionais);

		$sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$campos_adicionais}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0){
			$error = true;
		}
	}else{
		$campos_adicionais["horas_trabalhadas"] = "$horas_trabalhadas";
		$campos_adicionais = json_encode($campos_adicionais);

		$sql = "
			INSERT INTO tbl_os_campo_extra(
				os,
				fabrica,
				campos_adicionais
			)VALUES(
				{$os},
				{$login_fabrica},
				'{$campos_adicionais}'
			)";
		$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error()) > 0){
			$error = true;
		}
	}
	
	if (!$error){
		$sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
            VALUES ({$os}, 6, 'auditoria de fechamento');
        ";
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $error = true;
        }
	}

	if ($error === true){
		exit(json_encode(array("error" => "sim")));
	}else{
		exit(json_encode(array("success" => "sim")));
	}
	exit;
}

if (isset($_POST['gravarDataconserto']) && isset($_POST['os'])) {
	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$dataTela = trim($_POST['gravarDataconserto']);

	$os = trim($_POST['os']);
	$erro = '';

	if (strlen($os) > 0) {
		if(strlen($gravarDataconserto ) > 0) {
			$data = $gravarDataconserto.":00";
			$aux_ano  = substr ($data,6,4);
			$aux_mes  = substr ($data,3,2);
			$aux_dia  = substr ($data,0,2);
			$aux_hora = substr ($data,11,5).":00";
			$gravarDataconserto = "'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";
		} else {
			$gravarDataconserto = 'null';
		}
		
		if ($gravarDataconserto != 'null'){
			$data_atual = date("Y-d-m H:i:s");
			if (strtotime($gravarDataconserto) > strtotime($data_atual)){
				$erro = traduz("data.de.conserto.nao.pode.ser.superior.a.data.atual", $con, $cook_idioma);
			}

			$sql = "SELECT $gravarDataconserto::timestamp";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res)==0){
				echo traduz("Informe uma data correta para o campo 'Data de conserto'");
				exit;
			}

			$sql = "SELECT $gravarDataconserto < tbl_os.data_abertura FROM tbl_os where os=$os";
			$res = pg_query($con,$sql);
			if (pg_fetch_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
			}
		}

		if (strlen($erro) == 0) {

			if($areaAdmin){
				$sql = "SELECT posto FROM tbl_os WHERE os = {$os}";
				$res = pg_query($con,$sql);
				$login_posto = pg_fetch_result($res,0,'posto');
			}

			$sql = "
				UPDATE tbl_os
				SET data_conserto = {$gravarDataconserto}
				WHERE os = {$os}
				AND fabrica = {$login_fabrica}
				AND posto = {$login_posto}";
			$res = pg_query($con,$sql);

			if ($login_fabrica == 178){
				$sql = "
					SELECT os 
					FROM tbl_os 
					JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
					WHERE tbl_os.os = {$os}
					AND tbl_tipo_atendimento.km_google IS TRUE
					AND tbl_tipo_atendimento.descricao ILIKE '%Garantia Domic%'";
				$res = pg_query($con, $sql);
				
				if (pg_num_rows($res) > 0){
					$sql = "
						UPDATE tbl_os
						SET status_checkpoint = 30
						WHERE os = {$os}
						AND fabrica = {$login_fabrica}
						AND posto = {$login_posto}";
					$res = pg_query($con,$sql);
				}
			}

			if(in_array($login_fabrica,array(186))){
				envia_email_consumidor_status_os();
			}
		} else {
			echo $erro;
		}
	}
	exit;
}

$fc_ordens = $_REQUEST['os'];

if ($btn_acao == 'fechar') {
	
	$fc_data_fechamento = $_REQUEST['data_fechamento'];
	$aux_fc_data_fechamento = formata_data($fc_data_fechamento);
	$msg_sucesso = array();

	if (empty($fc_data_fechamento)) {
		$msg_erro['msg'][] = traduz("Data de fechamento não pode ser vazia");
	}

	if (count($msg_erro['msg']) == 0) {
		if ($areaAdmin) {
			$ambiente = "../";
		}
		foreach($fc_ordens as $ordem) {
			if ($ordem['ativo'] == 't') {
				$fc_os_tipo_revenda    = $ordem['os_tipo_revenda'];
				$fc_os                 = $ordem['os'];
				$fc_sua_os             = $ordem['sua_os'];
				$fc_hd_chamado         = $ordem['hd_chamado'];
				$fc_consumidor_revenda = $ordem['consumidor_revenda'];
				$fc_os_cortesia        = $ordem['cortesia'];

				if (file_exists($ambiente."classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
					include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
					$className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
					$classOs = new $className($login_fabrica, $fc_os);
				} else {
					$classOs = new \Posvenda\Os($login_fabrica, $fc_os);
				}

				try {

					if(in_array($login_fabrica, array(178,183,190,195))){
						$classOs->validaInformacoesOs($fc_os, $fc_sua_os, $aux_fc_data_fechamento, $fc_os_tipo_revenda);
					}
					pg_query($con, "BEGIN;");


					/* validação anexo */
						$xfc_sua_os = explode('-', $fc_sua_os);
						$xfc_sua_os = $xfc_sua_os;

						$sql_tdocs  = "
							SELECT
								JSON_FIELD('typeId', obs) AS typeId 
		                    FROM tbl_tdocs  
		                    WHERE tbl_tdocs.fabrica          = {$login_fabrica}
		                    	AND tbl_tdocs.situacao       = 'ativo'
		                    	AND (tbl_tdocs.referencia_id = {$fc_os} 
		                    	OR tbl_tdocs.referencia_id   = {$xfc_sua_os[0]});";
		            	$res_tdocs  = pg_query($con, $sql_tdocs);
		            	$erro_anexo = array();

						if ($login_fabrica == 178) {
						#	if ($fc_os_tipo_revenda == 'f') {
								if (pg_num_rows($res_tdocs) > 0){
			                        $typeId = pg_fetch_all_columns($res_tdocs);
			                        if (!in_array('notafiscal', $typeId) && $fc_consumidor_revenda == 'C'){
			                            $erro_anexo[] = traduz("Para a OS {$fc_sua_os} é obrigatório o anexo da nota fiscal do produto");
			                        }
			                        if (!in_array('assinatura', $typeId) && ($fc_consumidor_revenda == 'C' || $fc_os_cortesia == 't')){
			                            $erro_anexo[] = traduz("Para a OS {$fc_sua_os} é obrigatório o anexo da OS assinada");
			                        }
			                    }else{
			                    	if ($fc_consumidor_revenda == 'C'){
			                    		$erro_anexo[] = traduz("Para a OS {$fc_sua_os} é obrigatório o anexo da nota fiscal do produto");
			                    		$erro_anexo[] = traduz("Para a OS {$fc_sua_os} é obrigatório o anexo da OS assinada");
			                    	}
			                    }
						#	}

							$intervencaoOsPrincipal = $classOs->verificaOsRevendaIntervencao($fc_os, $fc_os_tipo_revenda);

							if ($intervencaoOsPrincipal === true) {
								if ($fc_os_tipo_revenda == 'f') {
									throw new Exception("Impossível finalizar a OS {$fc_sua_os}, OS Principal em intervenção");
								} else {
									throw new Exception("A OS {$fc_sua_os} esta em intervenção, impossível finalizar");
								}
							}
						}

						if (in_array($login_fabrica, [198])) {
							if (pg_num_rows($res_tdocs) > 0) {
								$typeId = pg_fetch_all_columns($res_tdocs);
								
								if ( (!in_array('produto', $typeId) && !in_array('peca', $typeId)) && $fc_consumidor_revenda == 'C'){
		                            $erro_anexo[] = traduz("Para a OS {$fc_sua_os} é obrigatório o anexo do produto/peça");
		                        }

							} else {
								if ($fc_consumidor_revenda == 'C'){
		                    		$erro_anexo[] = traduz("Para a OS {$fc_sua_os} é obrigatório o anexo da nota fiscal do produto");
		                    	}
							}
						}

						if (count($erro_anexo) > 0) {
	                    	throw new Exception(implode("<br />", $erro_anexo));
	                    }


					if ($login_fabrica == 139) {
						$classOs->VerificaIntervencao();
					}

					if (in_array($login_fabrica, [193])) {
						if ($fc_os_tipo_revenda == 'f') {
							$sql_tdocs = "
								SELECT
									JSON_FIELD('typeId', obs) AS typeId 
	                            FROM tbl_tdocs 
	                            WHERE tbl_tdocs.fabrica = {$login_fabrica}
	                            AND tbl_tdocs.situacao = 'ativo'
	                            AND tbl_tdocs.referencia_id = {$fc_os};
	                        ";
	                    	$res_tdocs = pg_query($con, $sql_tdocs);
	                    	$erro_anexo = array();

	                    	if (pg_num_rows($res_tdocs) > 0){
	                    		$typeId = pg_fetch_all_columns($res_tdocs);
		                        if (!in_array('assinatura', $typeId) && ($fc_consumidor_revenda == 'C' || $fc_os_cortesia == 't')){
		                            $erro_anexo[] = traduz("Para a OS {$fc_sua_os} é obrigatório o anexo da OS assinada");
		                        }
	                    	}
	                    }
					}


					if (in_array($login_fabrica, [183,184,186,190,191,193,194,195,198,200])) {
						$classOs->calculaOs();
					}

					if (in_array($login_fabrica, [184,191,198,200]) && ($classOs->verificaOsServicoAjuste($con, $login_fabrica, $fc_sua_os) || $classOs->verificaOsSemPeca($con, $login_fabrica, $fc_sua_os))) {
						$classOs->insereAuditoriaDeFabrica($con, $fc_sua_os);
					}

					if ($fc_os_tipo_revenda == 't') {
						$classOs->finalizaOsRevenda($con, $aux_fc_data_fechamento);
					} else {
						$sqlF = "UPDATE tbl_os SET
									data_fechamento = '$aux_fc_data_fechamento'
								where os = $fc_os  ";
						$resF = pg_query($con, $sqlF);
						$classOs->finaliza($con);
					}

					if ($login_fabrica == 178 && !empty($fc_hd_chamado) && $fc_os_tipo_revenda == 't') {
						$classOs->finalizaAtendimento($fc_hd_chamado);
					}

					if ($login_fabrica == 190 && $ordem["finaliza_atendimento"] == true && strlen($ordem["hd_chamado"]) > 0) {
						$classOs->finalizaAtendimento($ordem["hd_chamado"], $_POST["justificativa_fechamento_atendimento"]);
					}

					pg_query($con, "COMMIT;");
					$msg_sucesso[] = "OS {$fc_sua_os} finalizada com sucesso";
					unset($data_fechamento, $fc_ordens);

					if ($login_fabrica == 178 AND $fc_os_tipo_revenda != "t"){
						$classOs->verificaOsPrincipal($con);
					}

					if ($login_fabrica == 183){
						$classOs->finalizaZendesk($fc_os);
					}

					if(in_array($login_fabrica,array(186))){
						$os = $fc_os;
						//envia_email_consumidor_status_os();
					}

				} catch(Exception $e) {
					$msg_erro['msg'][] = traduz($e->getMessage());
					pg_query($con, "ROLLBACK;");
				}
			}
		}
	}
}

if (isset($btn_acao) || !empty($listar)) {
	$sua_os = trim($_REQUEST['sua_os']);
	$sua_os_fechar = $_REQUEST['sua_os_fechar'];
	$status_check = $_REQUEST['status_check'];

	if ($btn_acao == 'todas') {
		unset($sua_os);
	}

	if ($areaAdmin) {
		$login_posto = $_REQUEST['posto'];
	}

	if (!empty($sua_os) || !empty($sua_os_fechar)) {
		if ($btn_acao == 'fechar') {
			$sua_os = $sua_os_fechar;
		}
		$whereOs = "AND tbl_os.sua_os ILIKE '{$sua_os}%'";
	}

	if (!empty($login_posto)) {
		$wherePosto = "AND tbl_os.posto = {$login_posto}";
	}

	if (strlen($status_check) > 0) {
		$whereStatus = "AND tbl_os.status_checkpoint = {$status_check}";
	}

	if ($login_fabrica == 190){
		$coluna_contrato = ", tbl_contrato.campo_extra ->> 'mao_obra_fixa' AS mao_obra_fixa";
		$join_contrato = " 
			LEFT JOIN tbl_contrato_os ON tbl_contrato_os.os = tbl_os.os
			LEFT JOIN tbl_contrato ON tbl_contrato.contrato = tbl_contrato_os.contrato AND tbl_contrato.fabrica = {$login_fabrica} 
		";
	}

	$sql = "
		SELECT DISTINCT ON(tbl_os.os)
			'f' AS os_tipo_revenda,
			tbl_os.os,
			tbl_os.sua_os,
			tbl_os_campo_extra.os_revenda,
			tbl_os_campo_extra.campos_adicionais,
			tbl_os.status_checkpoint,
			tbl_os.fabrica,
			tbl_os.serie,
			tbl_os.cortesia::TEXT,
			tbl_os.obs,
			tbl_posto_fabrica.codigo_posto,
			tbl_os.tipo_os_cortesia,
			tbl_os.nota_fiscal_saida,
			tbl_os.data_nf_saida,
            		tbl_produto.produto,
			tbl_produto.referencia,
            		tbl_produto.descricao,
			tbl_produto.nome_comercial,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
			tbl_os.data_abertura AS abertura,
			tbl_os.data_digitacao,
			TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto,
			CASE
			WHEN tbl_os.data_abertura + interval '90 days' <= current_date THEN
			'sim'
			ELSE
			'nao'
			END AS aberta_90_dias,
			tbl_os.consumidor_nome,
			tbl_os.consumidor_revenda,
			tbl_os.defeito_constatado,
			tbl_os.admin,
			tbl_os_extra.pac,
			TO_CHAR(tbl_os_extra.inicio_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS inicio_atendimento,
			TO_CHAR(tbl_os_extra.termino_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS termino_atendimento,
			tbl_os_extra.regulagem_peso_padrao,
			tbl_os.tipo_atendimento,
			CASE
			WHEN tbl_os.cortesia IS TRUE THEN
			'Cortesia'
			WHEN tbl_os.tipo_atendimento = 35 THEN
			'Troca cortesia'
			WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
			'Troca consumidor'
			WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
			'Troca de revenda'
			WHEN tbl_os.consumidor_revenda = 'R' THEN
			'Revenda'
			ELSE
			'Consumidor'
			END AS tipo_os,
			tbl_os.hd_chamado,
			tbl_revenda.nome as nome_revenda
			{$coluna_contrato}
		FROM tbl_os
		JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
		JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
		JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
		LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
   		LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
   		{$join_contrato}
		WHERE tbl_os.fabrica = {$login_fabrica}
        	AND tbl_os.data_fechamento IS NULL
		AND tbl_os.excluida IS NOT TRUE
		{$whereOs}
		{$wherePosto}
		{$whereStatus}
	";
	if ($login_fabrica == 178) {
		
		if (!empty($login_posto)) {
			$wherePosto = "AND tbl_os_revenda.posto = {$login_posto}";
		}

		if (!empty($sua_os)) {
			$whereOs = " AND tbl_os_revenda.os_revenda = {$sua_os}";
		}

		if (strlen($whereStatus) > 0) {
			$whereStatus = "AND tbl_os_revenda.os_revenda IS NULL";
		}

		$sql .= "
			UNION
			SELECT
				't' AS os_tipo_revenda,
				tbl_os_revenda.os_revenda AS os,
				tbl_os_revenda.os_revenda::TEXT AS sua_os,
				NULL AS os_revenda,
				'' as campos_adicionais,
				NULL AS status_checkpoint,
				tbl_os_revenda.fabrica,
				NULL AS serie,
				tbl_os_revenda.cortesia::TEXT,
				tbl_os_revenda.obs,
				tbl_posto_fabrica.codigo_posto,
				tbl_os_revenda.tipo_os_cortesia,
				'' AS nota_fiscal_saida,
				NULL AS data_nf_saida,
	            NULL AS produto,
				'' AS referencia,
	            '' AS descricao,
				'' AS nome_comercial,
				TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura,
				tbl_os_revenda.data_abertura AS abertura,
				tbl_os_revenda.digitacao AS data_digitacao,
				'' AS data_conserto,
				CASE
				WHEN tbl_os_revenda.data_abertura + interval '90 days' <= current_date THEN
				'sim'
				ELSE
				'nao'
				END AS aberta_90_dias,
				tbl_os_revenda.consumidor_nome,
				tbl_os_revenda.consumidor_revenda,
				NULL AS defeito_constatado,
				tbl_os_revenda.admin,
				'' AS pac,
				'' AS inicio_atendimento,
				'' AS termino_atendimento,
				NULL AS regulagem_peso_padrao,
				tbl_os_revenda.tipo_atendimento,
				CASE
				WHEN tbl_os_revenda.cortesia IS TRUE THEN
				'Cortesia'
				WHEN tbl_os_revenda.tipo_atendimento = 35 THEN
				'Troca cortesia'
				WHEN tbl_os_revenda.consumidor_revenda = 'C' AND (tbl_os_revenda.tipo_atendimento = 17 OR tbl_os_revenda.tipo_atendimento = 18) THEN
				'Troca consumidor'
				WHEN tbl_os_revenda.consumidor_revenda = 'R' AND (tbl_os_revenda.tipo_atendimento = 17 OR tbl_os_revenda.tipo_atendimento = 18) THEN
				'Troca de revenda'
				WHEN tbl_os_revenda.consumidor_revenda = 'R' THEN
				'Revenda'
				ELSE
				'Consumidor'
				END AS tipo_os,
				tbl_os_revenda.hd_chamado,
				'' as nome_revenda
			FROM tbl_os_revenda
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			WHERE tbl_os_revenda.fabrica = {$login_fabrica}
			AND tbl_os_revenda.data_fechamento IS NULL
			AND tbl_os_revenda.excluida IS NOT TRUE
			{$whereOs}
			{$wherePosto}
			{$whereStatus}
		";
	}
	
	$sql_exec = "SELECT * FROM ({$sql}) AS x ORDER BY sua_os, os LIMIT 500;";
	$resRelatorio = pg_query($con, $sql_exec);
	$count = pg_num_rows($resRelatorio);
}

$layout_menu = ($areaAdmin) ? "callcenter" : "os";
$title = "FECHAMENTO DE ORDEM DE SERVIÇO";

if ($areaAdmin) {
    include __DIR__."/admin/cabecalho_new.php";
} else {
    include __DIR__."/cabecalho_new.php";
}

$plugins = array(
	"lupa",
	"autocomplete",	
	"mask",
	"maskedinput",
	"alphanumeric",
	"dataTable",
	"shadowbox",
	"select2",
	"datetimepickerbs2",
	"datepicker",
);

include "plugin_loader.php";

if (count($msg_erro["msg"]) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php }

if (count($msg_sucesso) > 0) { ?>
	<div class="alert alert-success">
		<h4><?= implode("<br />", $msg_sucesso)?></h4>
	</div>
<?php }

if ($login_fabrica == 178) {
	$fraseAlerta = "Obs: Para realizar o fechamento de ordens de consumidor é necessário anexar a NF e a OS assinada pelo consumidor";
}

if (!empty($fraseAlerta)) { ?>
	<div class="alert alert-info">
		<h5><?= $fraseAlerta; ?></h5>
	</div>
<?php } ?>

<form name='frm_busca_os' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span5'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("sua_os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Nº da Ordem de Serviço</label>
				<div class='controls controls-row'>
					<div class='span6'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="sua_os" id="sua_os" size="12" maxlength="10" class='span12' value= "<?= $sua_os; ?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span3'></div>
	</div>
	<div class="row-fluid">
		<p class="tac" style="width:100%;">
			<button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p>
	</div>
	<div class="row-fluid">
		<p class="tac" style="width:100%;">
			<button class='btn' id="btn_todas" type="button" onclick="submitForm($(this).parents('form'), 'todas');">Listar todas as Ordens</button>
		</p>
	</div>
</form>
</div>
<div class='container-fluid'>
<?php if (isset($btn_acao) || isset($listar)) {
	if ($count > 0) {
		if ($count > 500) { ?>
			<div class='alert'>
				<h6>Em tela serã o mostrados no máximo 500 registros.</h6>
			</div>
		<?php }

		$condicao_status = '0,1,2,3,4,8';
		$mod = 5;

		if(in_array($login_fabrica, array(178))){
			$condicao_status .= ",30";
			$mod = 6;
		}

		if ($login_fabrica == 183) {
			$condicao_status = '0,1,2,3,8,9,28,30';
		}

		$sql_status = "SELECT status_checkpoint, descricao, cor FROM tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.");";

		$res_status = pg_query($con,$sql_status);
		$total_status = pg_num_rows($res_status); ?>
		<style>
			.status_checkpoint{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
			.status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;}
		</style>
		<div class='tal' style='padding-right: 5px !important;'>
			<br />
			<table class="table table-fixed">
					<?php
					$array_cor_status = array();
					for ($i = 0; $i < $total_status; $i++) {

						$id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
						$cor_status = pg_fetch_result($res_status,$i,'cor');
						$descricao_status = pg_fetch_result($res_status,$i,'descricao');

						$array_cor_status[$id_status] = $cor_status;

						if ($i % $mod == 0 && $i != 0) { ?>
							<tr>
						<?php } ?>
						<td nowrap>
							<span class="status_checkpoint" style="background-color:<?= $cor_status;?>">&nbsp;</span>
							<font size='1'>
								<b>
									<a href="javascript:void(0)" onclick="filtrar(<?= $id_status;?>);">
										<?= $descricao_status.'';?>
									</a>
								</b>
							</font>
						</td>
						<?php if ($i % $mod == 0 && $i != 0) { ?>
							</tr>
						<?php } ?>
					<?php } ?>
			</table>
		</div>

		<div class='tal' style='padding-right: 5px !important;'>
			<form name='frm_fechamento' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
				<input type="hidden" id="justificativa_fechamento_atendimento" name="justificativa_fechamento_atendimento" value="<?= $xjustificativa_fechamento_atendimento; ?>" />

				<table id="resultado_pesquisa" class='table table-bordered table-hover table-fixed'>
					<thead>
						<tr class="titulo_coluna">
							<td class="tac" colspan="7">
								Data de Fechamento: <input class="span3" type="text" id="data_fechamento" name="data_fechamento" value="<?= $data_fechamento; ?>" />
							</td>
						</tr>
						<tr class='titulo_coluna'>
							<?php if ($login_fabrica == 178 && !isset($status_check) && empty($sua_os)) { ?>
								<th></th>
							<?php } ?>
							<th class="tac"><input type='checkbox' class='frm' name='marcar' value='tudo' title='<?php fecho("selecione.ou.desmarque.todos", $con, $cook_idioma); ?>' style='cursor:pointer;'></th>
							<th class="tac"><?= traduz('OS'); ?></th>
							<th class="tac"><?= traduz('Data Abertura'); ?></th>
							<th class="tal"><?= traduz('Consumidor'); ?>/<?= traduz('Revenda'); ?></th>
							<th class="tal"><?= traduz('Produto'); ?></th>
							<th class="tac"><?= traduz('Data Conserto'); ?></th>
							<?php if ($iframeBoxUploader == 't') { ?>
								<th class="tac"><?= traduz('Anexos da OS'); ?></th>
							<?php } ?>
							<?php if ($login_fabrica == 190){ ?>
								<th class="tac"><?= traduz('Qtde Horas'); ?></th>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<?php for ($xi = 0; $xi < $count; $xi++) {
							$xfinaliza_atendimento = $_POST['finaliza_atendimento'];
							$xjustificativa_fechamento_atendimento = $_POST['justificativa_fechamento_atendimento'];
							$xos = pg_fetch_result($resRelatorio, $xi, 'os');
							$xsua_os = pg_fetch_result($resRelatorio, $xi, 'sua_os');
							$xos_revenda = pg_fetch_result($resRelatorio, $xi, 'os_revenda');
							$xnome_revenda = pg_fetch_result($resRelatorio, $xi, 'nome_revenda');
							$xhd_chamado = pg_fetch_result($resRelatorio, $xi, 'hd_chamado');
							$xos_tipo_revenda = pg_fetch_result($resRelatorio, $xi, 'os_tipo_revenda');
							$xconsumidor_revenda = pg_fetch_result($resRelatorio, $xi, 'consumidor_revenda');
							$xdata_abertura = pg_fetch_result($resRelatorio, $xi, 'data_abertura');
							$xdata_conserto = pg_fetch_result($resRelatorio, $xi, 'data_conserto');
							$xconsumidor_nome = substr(pg_fetch_result($resRelatorio, $xi, 'consumidor_nome'), 0, 15);
							$xstatus_checkpoint = pg_fetch_result($resRelatorio, $xi, 'status_checkpoint');
							$xproduto_referencia = pg_fetch_result($resRelatorio, $xi, 'referencia');
							$xproduto_descricao = pg_fetch_result($resRelatorio, $xi, 'descricao');
							$cor_status_os = '<span class="status_checkpoint" style="background-color:'.$array_cor_status[$xstatus_checkpoint].'">&nbsp;</span>';
							$checked_ativo = ($fc_ordens[$xi]['ativo'] == 't') ? "checked" : "";
							if ($login_fabrica == 178) {
								$visible = (empty($xos_revenda) || isset($status_check) || !empty($checked_ativo) || !empty($sua_os)) ? "" : "style='display:none;'";
								$os_pai = (!empty($xos_revenda)) ? "rel='pai_{$xos_revenda}'" : "";
								$iconMostrar = (!empty($checked_ativo)) ? "icon-minus" : "icon-plus";
								$backTr = (empty($xos_revenda)) ? "bgcolor='#F5F5F5'" : "";
							} 

							if ($login_fabrica == 190){
								$campos_adicionais = pg_fetch_result($resRelatorio, $xi, 'campos_adicionais');
								$campos_adicionais = json_decode($campos_adicionais, true);
								$xhoras_trabalhadas = $campos_adicionais['horas_trabalhadas'];

								$mao_obra_fixa = pg_fetch_result($resRelatorio, $xi, 'mao_obra_fixa');
								if (strlen(trim($mao_obra_fixa)) == 0) {
									$mao_obra_fixa = "nao";
								}

							}
						?>
							<tr <?= $backTr; ?> class="linha_ordens" <?= $os_pai.' '.$visible; ?>>
								<?php if ($login_fabrica == 178 && !isset($status_check) && empty($sua_os)) { ?>
									<td class="tac"><?= (empty($xos_revenda)) ? "<i class='{$iconMostrar} toogle-os' rel='{$xos}' style='cursor:pointer;' title='Mostrar/Esconder Ordens Produto' ></i>" : ""; ?></td>
								<?php } ?>
								<td class="tac">
									<input type="hidden" id="os_tipo_revenda_<?= $xi; ?>" name="os[<?= $xi; ?>][os_tipo_revenda]" value="<?= $xos_tipo_revenda; ?>" />
									<input type="hidden" id="consumidor_revenda_<?= $xi; ?>" name="os[<?= $xi; ?>][consumidor_revenda]" value="<?= $xconsumidor_revenda; ?>" />
									<input type="hidden" id="os_<?= $xi; ?>" name="os[<?= $xi; ?>][os]" value="<?= $xos; ?>" />
									<input type="hidden" id="sua_os_<?= $xi; ?>" name="os[<?= $xi; ?>][sua_os]" value="<?= $xsua_os; ?>" />
									<input type="hidden" id="hd_chamado_<?= $xi; ?>" name="os[<?= $xi; ?>][hd_chamado]" value="<?= $xhd_chamado; ?>" />
									<input type="hidden" id="finaliza_atendimento_<?= $xi; ?>" name="os[<?= $xi; ?>][finaliza_atendimento]" value="<?= $xfinaliza_atendimento; ?>" />
									<input rel="checkbox_os" numero="<?= $xi; ?>" type="checkbox" class="check_pai check os checkbox_<?= $xi; ?>" name="os[<?= $xi; ?>][ativo]" <?= $checked_ativo; ?> id="ativo" value="t" />
								</td>
								<td class="tal" nowrap>
									<?= ($xos_tipo_revenda == 'f') ? $cor_status_os : ''; ?>
									<a href='<?= ($xos_tipo_revenda == "t") ? "os_revenda_press.php?os_revenda=" : "os_press.php?os="; ?><?= $xos; ?>' target='_blank'><?= $xsua_os; ?></a>
								</td>
								<td class="tac"><?= $xdata_abertura; ?></td>
								<td class="tal"><?= ($xconsumidor_revenda == "R" && !empty($xnome_revenda)) ? $xnome_revenda : $xconsumidor_nome; ?></td>
								<td class="tal" nowrap><?= ($xos_tipo_revenda == 'f') ? $xproduto_referencia." - ".$xproduto_descricao : ''; ?></td>
								<td class="tac">
									<div id="data_conserto_picker_<?= $xi; ?>" class="input-append date control-group">
										<input class="span2" id="data_conserto_<?= $xi; ?>" data-linha="<?= $xi; ?>" data-format="dd/MM/yyyy hh:mm" name="os[<?= $xi; ?>][data_conserto]" alt="<?= $xos; ?>" rel="data_conserto" type="text" value="<?= $xdata_conserto; ?>" <?= (strlen($xdata_conserto) > 0) ? "disabled" : ""; ?> />
									    <span class="add-on">
									      <i class="icon-calendar icon-time"></i>
									    </span>
									</div>
								</td>
								<?php if ($iframeBoxUploader == 't') { ?>
									<td class="tac">
										<a class='btn btn-info iframe_box_uploader' data-os='<?= $xsua_os ?>'>Anexar</a>
									</td>
								<?php } ?>	
								<?php if ($login_fabrica == 190){ ?>
									<td class='tac'>
										<?php if ($mao_obra_fixa == "nao"){ ?>
											<input maxlength="2" class="span2 tac times" type="text" data-os="<?=$xos?>" name="os[<?=$xi;?>][horas_trabalhadas]"  value="<?= $xhoras_trabalhadas; ?>" <?= (strlen($xhoras_trabalhadas) > 0) ? "disabled" : ""; ?> placeholder="hh:mm" />
										<?php } ?>
									</td>
								<?php } ?>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				<br />
				<div class="row-fluid">
					<p class="tac" style="width:100%;">
						<input type='hidden' id="sua_os_fechar" name='sua_os_fechar' value='<?= $sua_os; ?>' />
						<button class='btn btn-info btn-large' id="btn_acao" type="button" onclick="confirmar($(this));">Fechar</button>
						<input type='hidden' id="btn_click" name='btn_acao' value='' />
					</p>
				</div>
			</form>
		</div>
		<br />
	<?php } else { ?>
		<div class="alert">
			<h4>Nenhum resultado encontrado para essa pesquisa.</h4>
		</div>
		<br />
	<?php }
} ?>
</div>
<script type="text/javascript" charset="utf-8">

$(function(){
	$(".numeric").numeric();
	Shadowbox.init();
	<?php
	if($login_fabrica == 186){
	?>
		$("#data_fechamento").datepicker({ maxDate: 0, minDate: "-3d", dateFormat: "dd/mm/yy" });
	<?php
	}else{
	?>
		$("#data_fechamento").datepicker({ dateFormat: 'dd/mm/yy' });
	<?php
	}
	?>

	$("#data_fechamento").datepicker('setDate', new Date());

	var data = new Date();

	<?php
	if($login_fabrica == 186){
	?>
		data.setDate(data.getDate() - 3);
	<?php
	}else{
	?>
		data.setDate(data.getDate() - 30);
	<?php
	}
	?>

	$("div[id^=data_conserto_picker_]").datetimepicker({  
        format: "dd/MM/yyyy hh:mm",
        maskInput: true, 
        startDate: data, 
        language: 'pt-BR'
    }).on("changeDate", function(e) {
		if (e.date != "") {
			valida_data_conserto($(this).find("input[rel=data_conserto]").val(), $(this).find("input[rel=data_conserto]"));
		}
	});

    <?php if ($login_fabrica == 190){ ?>
	$(".times").mask("99:99", {reverse: true});
	$(".times").mask("99:99", {reverse: true}).on("blur", function(){
        	let horas_trabalhadas = $(this).val();
       		if (horas_trabalhadas != "" && horas_trabalhadas != undefined){
	        	if (confirm('Deseja realmente gravar Qtde de Horas? Depois de gravada somente ADMIN da Fábrica poderar alterar esse campo')) {
					let campo = $(this);
			    	let os = $(this).data("os");
			    	let data_conserto = $(this).parents("tr").find("input[rel=data_conserto]").val();
			    	
			    	if (data_conserto != "" && data_conserto != undefined){
			    		$.ajax({
							url:"<?=$PHP_SELF?>",
							type:"POST",
							dataType:"JSON",
							data:{
								ajax:"gravaQtdeHoras",
								os:os,
								horas_trabalhadas:horas_trabalhadas
							}	
						}).done(function(data){
							if(data.error == "sim"){
								alert("Erro ao gravar qtde de horas");
							}
							
							$(campo).attr("disabled", true);
						}).fail(function(data){
							return false;
						});
			    	}else{
			    		alert("Preencha data conserto");
			    	}
				} else {
					$(this).val('');
					return false;
				}
			}
	    });
	<?php } ?>
});

function confirmar(obj){
	var xfabrica = <?php echo $login_fabrica;?>;
	
	if(xfabrica == 186){
		Shadowbox.open({
	        content: "<div><h5>O produto já foi retirado da assitência pelo consumidor?</h5><br><div style='margin-left:35%;'><button class='btn btn-confirm'onclick='submitForm($(\"form[name=frm_fechamento]\"), \"fechar\");'>Sim</button>&nbsp;<button class='btn btn-danger' onclick='Shadowbox.close();'>Não</button></div></div>",
	        player: "html",
	        height: 100,
	        width: 400,
	        
	    });

	} else if(xfabrica == 190){
		var tem_atendimento = false;
		$("#resultado_pesquisa > tbody > tr > td").each(function(indice,elemento) {
			if($("input[name='os["+indice+"][ativo]']").is(":checked")) {
            	if ($("input[name='os["+indice+"][hd_chamado]']") != '') {

					tem_atendimento = true;
					$("input[name='os["+indice+"][finaliza_atendimento]']").val('true');
            	}
        	}
        });
        if (tem_atendimento) {
        	if (confirm("Deseja fechar o Atendimento vinculado a O.S.?")) {
        		var motivo = prompt("Digite a Justificativa:");
        		if (motivo != '') {
        			$("#justificativa_fechamento_atendimento").val(motivo);
					submitForm($(obj).parents('form'), 'fechar');
        		}
        	} else {
        		$("#resultado_pesquisa > tbody > tr > td").each(function(indice,elemento) {
					if($("input[name='os["+indice+"][ativo]']").is(":checked")) {
		            	if ($("input[name='os["+indice+"][hd_chamado]']") != '') {
							$("input[name='os["+indice+"][finaliza_atendimento]']").val('');
		            	}
		        	}
		        });
		        submitForm($(obj).parents('form'), 'fechar');
        	}
        }
	}else{
		submitForm($(obj).parents('form'), 'fechar');
	}
}


<?php if ($iframeBoxUploader == 't') { ?>
	$(".iframe_box_uploader").on('click', function(){
		var os = $(this).data('os');
		var tr = $(this).closest('tr');
        tr.attr('hide_line', 'true');

        Shadowbox.init();

		Shadowbox.open({
	        content: "iframe_box_uploader.php?os="+os,
	        player: "iframe",
	        width: 1270,
	        height: 860,
	        title: "Ordem de Serviço "+os,
	        options: {
	            modal: false,	
	            enableKeys: false,
	            displayNav: false
	        }
	    });
	});
<?php } ?>

function filtrar(status){
	if(status >= 0){
		window.location.href = "<?= $PHP_SELF?>?&listar=todas&status_check="+status;
		if($("th input.frm").is(":checked")){
			$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked',false);
			$("tr[rel=status_"+status+"] > td > input[type=checkbox]").attr('checked','checked');
		}
	}else{
		window.location.href = "<?= $PHP_SELF?>?&listar=todas";
		if($("th input.frm").is(":checked")){
			$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked','checked');
		}
	}
	filtro_status = status;
}

$(document).on("click", ".toogle-os", function() {
	var numero_os = $(this).attr('rel');
	if ($("tr[rel=pai_"+numero_os).is(":visible")) {
		$(this).removeClass("icon-minus").addClass("icon-plus");
		$("tr[rel=pai_"+numero_os).hide(800);
	} else {
		$(this).removeClass("icon-plus").addClass("icon-minus");
		$("tr[rel=pai_"+numero_os).show(800);
	}
});

function valida_data_conserto(valor_campo, campo) {
	var data_fechamento = $("input[name=data_fechamento]").val();
	var linha = campo.data('linha');
	var numero_os = campo.attr("alt");
	var id_fabrica = <?= $login_fabrica ?>;
	var os_principal = $("input[name='os["+linha+"][os_tipo_revenda]'").val();

	if (os_principal == 't') {
		if (confirm('Todas as datas de conserto das ordens produtos serão alteradas para '+valor_campo+', tem certeza?')) {
			$("tr[rel=pai_"+numero_os+"] > td > div > input[type=text]").not(":disabled").val(valor_campo).trigger("change");
		} else {
			$(campo).val('');
			return false;
		}
	}

	$.post('<?= $PHP_SELF; ?>',
	{
		gravarDataconserto : valor_campo,
		os: campo.attr("alt"),
		data_fechamento : data_fechamento
	},
	function(resposta) {

		if (resposta.length > 0) {
			alert(resposta);
			campo.val('');
			$(campo).focus();
			var validado = false;
		} else {
			if(id_fabrica == 186){
				verificaGeraPedidoDevolucao(numero_os);
			}

			var validado = true;
		}
	});
}

function verificaGeraPedidoDevolucao(os) {                                                                                                                                                                

	$.ajax({
		url:"consulta_lite_new.php",
			type:"POST",
			dataType:"JSON",
			data:{
				ajax:"verificaGeraPedidoDevolucao",
				os:os
			}	
		})
		.done(function(data){
			if(data.erro == false){
				if(confirm(data.msg)) {
					geraPedidoDevolucao(os);
				} else {
					return false;
				}
			} else {
				return false;
			}
		})
		.fail(function(data){
			return false;
		});
}

function geraPedidoDevolucao(os) {
	$.ajax({
		async: false,
		url : "os_cadastro_unico/fabricas/<?=$login_fabrica?>/ajax_gerar_pedido_manual.php",
		type: "get",
		data: { gera_pedido_manual: true, os: os},
		success: function(data) {
			data = JSON.parse(data);
			if (data.erro) {
				alert(data.erro);
				return false;
			} else {
				alert("Pedido Gerado com Sucesso");
				location.reload();
			}
		}
	});
}

$(document).on("click", "input[name=marcar]", function () {
	if($(this).is(":checked")){
		$(".check").prop('checked', 'checked');
	}else{
		$(".check").prop("checked", false);
	}
});

$(document).on("click", ".check_pai", function () {
	var numero = $(this).attr("numero");
	var os_pai = $('#os_'+numero).val();
	if ($(this).is(":checked")) {
		$("tr[rel=pai_"+os_pai+"] > td > input[type=checkbox]").prop('checked', 'checked');
	} else {
		$("tr[rel=pai_"+os_pai+"] > td > input[type=checkbox]").prop('checked', false);
	}
});

<?php if ($count > 50) { ?>
	$.dataTableLoad({ table: "#resultado_pesquisa" });
<?php } ?>
</script>

<?php include 'rodape.php'; ?>
