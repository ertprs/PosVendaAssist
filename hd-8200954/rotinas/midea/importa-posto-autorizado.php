<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/tdocs.class.php';
require dirname(__FILE__) . '/../funcoes.php';

include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;

$login_fabrica = 169;
$fabrica_nome = "Midea / Carrier";
$tdocs = new TDocs($con, $login_fabrica, 'rotina');

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();
ob_start();
function msg_log($msg){
    echo "\n".date('H:i:s')." - $msg";
}

try {

    msg_log('Inicia rotina de importação de posto autorizado');

    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Dado Mestre - Posto Autorizado");
    $routine_id = $arr[0]["routine"];

    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if (!strlen($routine_schedule_id)) {
        throw new Exception("Agendamento da rotina não encontrado");
    }

    $routineScheduleLog = new Log();

    $arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

    $count_routine = 0;
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/midea\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }

    $em_execucao = ($count_routine > 4) ? true : false;

    if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $em_execucao == false) {

        $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
        $routine_schedule_log_stopped = $routineScheduleLog->GetRoutineWithoutFinish();

        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_stopped['routine_schedule_log']);
        $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage(utf8_encode('Rotina finalizada'));
        $routineScheduleLog->Update();
        msg_log('Finalizou rotina anterior Schedule anterior. Rotina cod: '.$routine_id);
    }

    $routineScheduleLog->setRoutineSchedule(null);
    $routineScheduleLog->setRoutineScheduleLog(null);
    $routineScheduleLog->setDateFinish(null);
    $routineScheduleLog->setStatus(null);
    $routineScheduleLog->setStatusMessage(null);

    if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $em_execucao == true) {
        throw new Exception("Rotina em execução");
    } else {

        $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
        $routineScheduleLog->setDateStart(date("Y-m-d H:i"));

        if (!$routineScheduleLog->Insert()) {
            throw new Exception("Erro ao gravar log da rotina");
        }

        $routine_schedule_log_id = $routineScheduleLog->SelectId();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);

    }

    if ($_serverEnvironment == 'development') {
		$urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }
	
    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));

    $xmlRequest = "
		<ns1:oXml>
            <Z_CB_TC_PESQ_INFO_CENTRO_TRAB xmlns='http://ws.carrieronline.com.br/PSA_WebService'>
				<P_WERKS>B111</P_WERKS>
  				<P_ARBPL></P_ARBPL>
  				<P_DADOS_CENTROS>X</P_DADOS_CENTROS>
  				<PI_DT_ENVIO></PI_DT_ENVIO>
  				<PT_SELOPT></PT_SELOPT>
            </Z_CB_TC_PESQ_INFO_CENTRO_TRAB>
        </ns1:oXml>
    ";
    $params   = new SoapVar($xmlRequest, XSD_ANYXML);
    $request  = array('oXml' => $params);

    $result = $client->Z_CB_TC_PESQ_INFO_CENTRO_TRAB($request);
    $dados_xml = $result->Z_CB_TC_PESQ_INFO_CENTRO_TRABResult->any;
    $dados_xml = substr($dados_xml,strpos($dados_xml,'<diffgr:diffgram'));
    $xml = simplexml_load_string($dados_xml);
    $xml = json_decode(json_encode((array)$xml), true);

    $array_ret = array();
    if (!empty($xml['NewDataSet']['ZCBSM_ATENDIMENTO_CENTROSTABLE']['ARBPL'])) {
    	$array_ret[] = $xml['NewDataSet']['ZCBSM_ATENDIMENTO_CENTROSTABLE'];
    } else {
    	$array_ret = $xml['NewDataSet']['ZCBSM_ATENDIMENTO_CENTROSTABLE'];
    }

    foreach($array_ret as $key => $value){

		try{
		
			$dados_posto = $value;
			$dados_posto['NAME1'] = (!is_array($dados_posto['NAME1'])) ? $dados_posto['NAME1'] : '';
			$dados_posto['CC085_NOMEFANTASIA'] = (!is_array($dados_posto['CC085_NOMEFANTASIA'])) ? $dados_posto['CC085_NOMEFANTASIA'] : '';
			$dados_posto['STCD1'] = (!is_array($dados_posto['STCD1'])) ? $dados_posto['STCD1'] : '';
			$dados_posto['STCD3'] = (!is_array($dados_posto['STCD3'])) ? $dados_posto['STCD3'] : '';
			$dados_posto['STREET'] = (!is_array($dados_posto['STREET'])) ? $dados_posto['STREET'] : '';
			$dados_posto['HOUSE_NUM1'] = (!is_array($dados_posto['HOUSE_NUM1'])) ? $dados_posto['HOUSE_NUM1'] : '';
			$dados_posto['CITY2'] = (!is_array($dados_posto['CITY2'])) ? $dados_posto['CITY2'] : '';
			$dados_posto['POST_CODE1'] = (!is_array($dados_posto['POST_CODE1'])) ? preg_replace("/\D/", "", $dados_posto['POST_CODE1']) : '';
			$dados_posto['TEXT'] = (!is_array($dados_posto['TEXT'])) ? $dados_posto['TEXT'] : '';
			$dados_posto['REGION'] = (!is_array($dados_posto['REGION'])) ? $dados_posto['REGION'] : '';
			$dados_posto['CC090_TELEFONEGARANTIA'] = (!is_array($dados_posto['CC090_TELEFONEGARANTIA'])) ? $dados_posto['CC090_TELEFONEGARANTIA'] : '';
			$dados_posto['CC130_CONTATOGARANTIA'] = (!is_array($dados_posto['CC130_CONTATOGARANTIA'])) ? $dados_posto['CC130_CONTATOGARANTIA'] : '';
			$dados_posto['TXJCD'] = (!is_array($dados_posto['TXJCD'])) ? $dados_posto['TXJCD'] : '';
			$dados_posto['CC055_COD_CLIENTE'] = (!is_array($dados_posto['CC055_COD_CLIENTE'])) ? $dados_posto['CC055_COD_CLIENTE'] : '';

			$parametros_adicionais = array();
			if (!empty($dados_posto['CC055_COD_CLIENTE'])) {
				$parametros_adicionais['codigo_cliente'] = $dados_posto['CC055_COD_CLIENTE'];
			}
			
			$cod_ibge = explode(" ",$dados_posto['TXJCD']);
			$dados_posto['TXJCD'] = $cod_ibge[1];

			$emails = explode(" ",$dados_posto['EMAIL']);
			$emails = array_filter($emails);
			
			if(count($emails) > 0){
				$dados_posto['EMAIL'] = $emails[0];
			}else{
				$dados_posto['EMAIL'] = '';
			}

			unset($linha);
			$email_posto = $dados_posto['EMAIL'];
			$sql = "SELECT fn_valida_email('$email_posto',true)";
			$res = pg_query($con,$sql);

			$email_valido = pg_result($res,0,0);

			if($email_valido == 't') {
				$email = $dados_posto['EMAIL'];
			} else {
				$email = 'null';
			}

			$linhas = array(
				0 => "{$dados_posto['CC170_CANAISDEATENDIMENTO0']}",
				1 => "{$dados_posto['CC170_CANAISDEATENDIMENTO1']}",
				2 => "{$dados_posto['CC170_CANAISDEATENDIMENTO2']}",
				3 => "{$dados_posto['CC170_CANAISDEATENDIMENTO3']}",
				4 => "{$dados_posto['CC170_CANAISDEATENDIMENTO4']}",
				5 => "{$dados_posto['CC170_CANAISDEATENDIMENTO5']}",
				6 => "{$dados_posto['CC170_CANAISDEATENDIMENTO6']}",
				7 => "{$dados_posto['CC170_CANAISDEATENDIMENTO7']}",
				8 => "{$dados_posto['CC170_CANAISDEATENDIMENTO8']}",
				9 => "{$dados_posto['CC170_CANAISDEATENDIMENTO9']}"
			);

			$linhas = array_filter($linhas);
	
			$razao = utf8_decode($dados_posto['NAME1']);
			$fantasia = utf8_decode($dados_posto['CC085_NOMEFANTASIA']);

			$cidade_nome = utf8_decode($dados_posto['TEXT']);
			$cidade_nome = str_replace("'","",$cidade_nome);
			$sql = "SELECT cod_ibge FROM tbl_cidade WHERE upper(nome) = upper(fn_retira_especiais('{$cidade_nome}')) AND estado = '{$dados_posto['REGION']}'";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0) {
				throw new Exception("Cidade informada para o posto {$dados_posto['ARBPL']} -  ".json_decode($dados_posto)." não foi encontrada");
			} else {
				if($dados_posto['TXJCD'] != pg_fetch_result($res,0,'cod_ibge')){
					throw new Exception("Código IBGE informado para o posto {$dados_posto['ARBPL']} - ".json_decode($dados_posto)." é diferente ao da cidade informada");
				}
			}

			msg_log("Verificando se o posto {$dados_posto['ARBPL']} já pussui cadastro");

			$sql = "SELECT posto FROM tbl_posto WHERE cnpj = '{$dados_posto['STCD1']}'";
			$res = pg_query($con,$sql);

			pg_query($con, "BEGIN;");

			if(pg_num_rows($res) == 0){

				msg_log("Cadastrando o posto {$dados_posto['ARBPL']}");

				$sql = "
					INSERT INTO tbl_posto (
						nome            ,
						nome_fantasia   ,
						cnpj            ,
						ie              ,
						endereco        ,
						numero          ,
						bairro          ,
						cep             ,
						cidade          ,
						estado          ,
						fone            ,
						contato         ,
						cod_ibge
					) VALUES (
						'{$razao}',
						'{$fantasia}',
						'{$dados_posto['STCD1']}',
						'{$dados_posto['STCD3']}',
						'{$dados_posto['STREET']}',
						'{$dados_posto['HOUSE_NUM1']}',
						'{$dados_posto['CITY2']}',
						'{$dados_posto['POST_CODE1']}',
						'{$cidade_nome}',
						'{$dados_posto['REGION']}',
						'{$dados_posto['CC090_TELEFONEGARANTIA']}',
						'{$dados_posto['CC130_CONTATOGARANTIA']}',
						'{$dados_posto['TXJCD']}'
					) RETURNING posto;
				";

				$res = pg_query($con,$sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao tentar cadastrar o posto - {$dados_posto['ARBPL']}");
				}

				msg_log("Cadastro realizado com sucesso para o posto - {$dados_posto['ARBPL']}");
			}
		
			$posto = pg_fetch_result($res,0,'posto');

			if($dados_posto['CC070_DT_DESCRED'] != "0000-00-00"){
				$sql = "SELECT '{$dados_posto['CC070_DT_DESCRED']}'::date - CURRENT_DATE AS dias";
				$res = pg_query($con,$sql);
				$dias = pg_fetch_result($res,0,'dias');
				$credenciamento = ($dias <= 0) ? "DESCREDENCIADO" : "EM DESCREDENCIAMENTO";
			}else{
				$credenciamento = "CREDENCIADO";
				$dias = 'null'; 
			}

			msg_log("Verificando se o posto {$dados_posto['ARBPL']} já está cadastrado para a {$fabrica_nome}");

			$sql = "SELECT posto, parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0) {
				msg_log("Cadastrando o posto {$dados_posto['ARBPL']} para a {$fabrica_nome}");

				$sql = "
					INSERT INTO tbl_posto_fabrica (
						posto,
						fabrica,
						senha,
						tipo_posto,
						codigo_posto,
						conta_contabil,
						centro_custo,
						credenciamento,
						contato_fone_comercial,
						contato_endereco ,
						contato_numero,
						contato_bairro,
						contato_cep,
						contato_cidade,
						contato_estado,
						contato_email,
						nome_fantasia,
						contato_nome,
						cod_ibge,
						parametros_adicionais
					) VALUES (
						$posto,
						$login_fabrica,
						'*',
						643,
						'{$dados_posto['ARBPL']}',
						'{$dados_posto['CC065_COD_FORNEC']}',
						'{$dados_posto['WERKS']}',
						'{$credenciamento}',
						'{$dados_posto['CC090_TELEFONEGARANTIA']}',
						'{$dados_posto['STREET']}',
						'{$dados_posto['HOUSE_NUM1']}',
						(E'{$dados_posto['CITY2']}'),
						'{$dados_posto['POST_CODE1']}',
						(E'{$cidade_nome}'),
						'{$dados_posto['REGION']}',
						'$email',
						(E'{$fantasia}'),
						(E'{$dados_posto['CC130_CONTATOGARANTIA']}'),
						{$dados_posto['TXJCD']},
						'{$parametros_adicionais}'
					);
				";

				msg_log("Cadastro realizado com sucesso para o posto - {$dados_posto['ARBPL']} para a {$fabrica_nome}");
			} else {
				$xparametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
				if (!empty($xparametros_adicionais)) {
					$xparametros_adicionais = json_decode($xparametros_adicionais, true);
				}

				$parametros_adicionais_merge = array();
				if (!empty($xparametros_adicionais) && count($parametros_adicionais) > 0) {
					$parametros_adicionais_merge = array_merge($xparametros_adicionais, $parametros_adicionais);
				} else if (!empty($xparametros_adicionais)) {
					$parametros_adicionais_merge = $xparametros_adicionais;
				} else if (count($parametros_adicionais) > 0) {
					$parametros_adicionais_merge = $parametros_adicionais;
				}

				if (!empty($parametros_adicionais_merge)) {
					$parametros_adicionais_merge = json_encode($parametros_adicionais_merge);
				}

				msg_log("Atualizando os dados do posto {$dados_posto['ARBPL']} para a {$fabrica_nome}");

				$sql = "
					UPDATE tbl_posto_fabrica SET
						codigo_posto = '{$dados_posto['ARBPL']}',
						conta_contabil = '{$dados_posto['CC065_COD_FORNEC']}',
						centro_custo = '{$dados_posto['WERKS']}',
						nome_fantasia = (E'{$fantasia}'),
						contato_email = '{$email}',
						contato_nome = (E'{$dados_posto['CC130_CONTATOGARANTIA']}'),
						credenciamento = UPPER('{$credenciamento}'),
						parametros_adicionais = '{$parametros_adicionais_merge}'
					WHERE tbl_posto_fabrica.posto = {$posto}
					AND tbl_posto_fabrica.fabrica = {$login_fabrica};
				";

				msg_log("Cadastro atualizado com sucesso para o posto - {$dados_posto['ARBPL']} e para a {$fabrica_nome}");
			}

			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao tentar cadastrar/atualizar o posto - {$dados_posto['ARBPL']} para a {$fabrica_nome}");
			}
		
			msg_log("Consultado se o posto {$dados_posto['ARBPL']} possui registro na tbl_credenciamento para a {$fabrica_nome}");
			$sql = "SELECT status FROM tbl_credenciamento WHERE posto = {$posto} AND fabrica = {$login_fabrica} ORDER BY credenciamento DESC LIMIT 1";
			$res = pg_query($con,$sql);
			$status = pg_fetch_result($res,0,'status');

			if (pg_num_rows($res) == 0 || $status != $credenciamento) {

				msg_log("Cadastrando registro na tbl_credenciamento para o posto {$dados_posto['ARBPL']} referente à {$fabrica_nome}");
				$sql = "
					INSERT INTO tbl_credenciamento(
						posto,
						fabrica,
						status,
						dias
					)VALUES(
						{$posto},
						{$login_fabrica},
						'{$credenciamento}',
						$dias
					);
				";
				$res = pg_query($con,$sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao tentar cadastrar o status {$credenciamento} para o posto - {$dados_posto['ARBPL']}");
				}

				msg_log("O status {$credenciamento} foi cadastrado com sucesso para o posto - {$dados_posto['ARBPL']}");
			}

			if (count($linhas) > 0) {

				foreach($linhas AS $k => $v) {
		
					if($v == "Array") {
						continue;
					}

					$codigo_linha = utf8_decode($v);

					if ($codigo_linha == 'E-Ticket') {
						msg_log("O posto {$dados_posto['ARBPL']} foi marcado como habilitado a receber E-Ticket");
						pg_query($con, "UPDATE tbl_posto_fabrica SET permite_envio_produto = TRUE WHERE fabrica = {$login_fabrica} AND posto = {$posto};");
						if (strlen(pg_last_error()) > 0) {
							throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} como recebe E-Ticket");
						}
						continue;
					}

					msg_log("Verificando se a linha {$v} existe no sistema");

					$sql = "
						SELECT
							*
						FROM (
							SELECT DISTINCT
								linha,
								nome,
								campos_adicionais->'revenda' AS revenda_linha,
								fn_retira_especiais(JSONB_ARRAY_ELEMENTS_TEXT(campos_adicionais->'linhaSAP')) AS linha_sap
							FROM tbl_linha
							WHERE fabrica = {$login_fabrica}
						) x
						WHERE linha_sap = fn_retira_especiais('{$codigo_linha}');
					";
					$res = pg_query($con, $sql);
					$count = pg_num_rows($res);
					$linhasTC = pg_fetch_all($res);

					msg_log("Removendo linhas cadastradas para o posto {$dados_posto['ARBPL']} ");
					$del = "DELETE FROM tbl_posto_linha WHERE posto = {$posto} AND linha IN (SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica});";
					pg_query($con, $del);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao remover linhas do posto {$dados_posto['ARBPL']}");
					}
				
					if($count > 0) {
						$posto_revenda = false;
						$divulga_consumidor = true;
						$nao_paga_mo = false;
						$sem_linha = true;
						foreach($linhasTC as $linhaTC) {
							$linhaId = $linhaTC['linha'];
							$linhaTCNome = $linhaTC['nome'];
							$linhaRevenda = $linhaTC['revenda_linha'];
							$linhaAuxSAP = $linhaTC['linha_sap'];

							if ($posto_revenda === false && $linhaRevenda == 't') {
								$posto_revenda = true;
								$sql = "UPDATE tbl_posto_fabrica SET tipo_posto = (SELECT tipo_posto FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_revenda IS TRUE) WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
								$res = pg_query($con,$sql);

								if (strlen(pg_last_error()) > 0) {
									throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} como revenda #001");
								}
								msg_log("O posto {$dados_posto['ARBPL']} foi marcado como revenda");

							}

							if ($posto_revenda === true) {
								if ($divulga_consumidor === true && $linhaAuxSAP == 'Varejo') {
									$divulga_consumidor = false;
									msg_log("O posto {$dados_posto['ARBPL']} foi desmarcado para divulgar para consumidor");
									pg_query($con, "UPDATE tbl_posto_fabrica SET divulgar_consumidor = FALSE WHERE fabrica = {$login_fabrica} AND posto = {$posto};");
									if (strlen(pg_last_error()) > 0) {
										throw new Exception("Erro ao desmarcar o posto {$dados_posto['ARBPL']} para divulgar para consumidor");
									}
								}

								if ($nao_paga_mo === false) {
									$nao_paga_mo = true;
									msg_log("O posto {$dados_posto['ARBPL']} foi marcado para não pagar MO");
									pg_query($con, "UPDATE tbl_posto_fabrica SET prestacao_servico_sem_mo = TRUE WHERE fabrica = {$login_fabrica} AND posto = {$posto};");
									if (strlen(pg_last_error()) > 0) {
										throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} para não pagar MO");
									}
								}
							}

							msg_log("Inserindo registro na tbl_posto_linha para o posto {$dados_posto['ARBPL']} e a linha {$v}");
							$sql = "
								INSERT INTO tbl_posto_linha(
									posto,
									tabela,
									linha
								)VALUES(
									{$posto},
									1090,
									{$linhaId}
								);
							";
							$res = pg_query($con,$sql);
							
							if (strlen(pg_last_error()) > 0) {
								throw new Exception("Erro ao tentar cadastrar o posto - {$dados_posto['ARBPL']} para a linha [$v}");
							}

							msg_log("Cadastro realizado com sucesso para o posto - {$dados_posto['ARBPL']} e para a linha {$v}");

							if (in_array($linha_sap, ['Assist Tecnica Eletro', 'Micro Ondas', 'Varejo'])) {
								msg_log("Iniciando atualização do posto {$dados_posto['ARBPL']} para poder Digitar OS");
								$sql = "
									UPDATE tbl_posto_fabrica SET 
										digita_os = true 
									WHERE fabrica = {$login_fabrica} 
									AND posto = {$posto};
								";
								$res = pg_query($con,$sql);

								if (strlen(pg_last_error()) > 0) {
									throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} para Digitar OS");
								}
								msg_log("O posto {$dados_posto['ARBPL']} foi marcado para Digitar OS");
							}
							$sem_linha = false;
						}
					}else{
						msg_log("Linha {$v} não possui cadastro no sistema");
					}
				}
				
				if ($sem_linha === true) {
					$sql = "
						UPDATE tbl_posto_fabrica
						SET tipo_posto = (
							SELECT tipo_posto
							FROM tbl_tipo_posto
							WHERE fabrica = {$login_fabrica}
							AND tipo_revenda IS TRUE
						)
						WHERE posto = {$posto}
						AND fabrica = {$login_fabrica};
					";
					$res = pg_query($con,$sql);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} como revenda #002");
					}
					msg_log("O posto {$dados_posto['ARBPL']} foi marcado como revenda");
				}
			}
			pg_query($con, "COMMIT;");
		} catch(Exception $e) {
			pg_query($con, "ROLLBACK;");
			msg_log("Erro: ".$e->getMessage());
			$logError = new \Posvenda\LogError();
			$logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
			$logError->setErrorMessage($e->getMessage());
			$logError->Insert();
		}
	}

    $nome_arquivo = 'rotina-importa-posto-'.date('Ymd').'.txt';
    //$arquivo_log  = "/tmp/$nome_arquivo";
    $arquivo_log  = "$nome_arquivo";

    msg_log("Enviando arquivo para o Tdocs: $nome_arquivo");

    $arquivo = array(
        'tmp_name' => $arquivo_log,
        'name'     => $nome_arquivo,
        'size'     => filesize($arquivo_log),
        'type'     => mime_content_type($arquivo_log),
        'error'    => null
    );

    if (!file_exists($arquivo_log)) {
        system("touch {$arquivo_log}");
    }

    $b = ob_get_contents();

    file_put_contents($arquivo_log, $b, FILE_APPEND);
    ob_end_flush();
    ob_clean();

    if (!$tdocs->uploadFileS3($arquivo, $routine_id)) {
        throw new Exception("Não foi possí­vel enviar o arquivo de log para o Tdocs. Erro: ".$tdocs->error);
    }

	msg_log("Arquivo enviado para o Tdocs.");
	
    if (!isset($status_final)) {
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage('Rotina finalizada');
        $routineScheduleLog->Update();
	}
	
} catch (Exception $e) {
    msg_log("Erro: ".$e->getMessage());
    $logError = new \Posvenda\LogError();
    $logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
    $logError->setErrorMessage($e->getMessage());
    $logError->Insert();

    $status_final = 2;

    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    $routineScheduleLog->Update();

}

$phpCron->termino();
?>

