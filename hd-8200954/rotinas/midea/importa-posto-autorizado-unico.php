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
$tdocs = new TDocs($con, $login_fabrica, 'rotina');

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

function msg_log($msg){
    echo "\n".date('H:i:s')." - $msg";
}

$codigo_posto = $argv[1];

//ob_start();

try{
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

    $em_execucao = ($count_routine > 2) ? true : false;

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

    /* Limpando variáveis */
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

    if ($serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
    }

    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));

	try{

		$array_request = array('CodigoCentroTrabalho' => $codigo_posto);
		msg_log("Iniciando segunda requisção na API para o posto $codigo_posto");
		$result = $client->PesquisaInfoCentroTrab_DadosCentro($array_request);
		$dados_xml = $result->PesquisaInfoCentroTrab_DadosCentroResult->any;
		$dados_xml = substr($dados_xml,strpos($dados_xml,'<diffgr:diffgram'));
		$xml = simplexml_load_string($dados_xml);
		$xml = json_decode(json_encode((array)$xml), true);
		
		$dados_posto = $xml['NewDataSet']['ZCBSM_ATENDIMENTO_CENTROSTABLE'];
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
		
		$cod_ibge = explode(" ",$dados_posto['TXJCD']);
		$dados_posto['TXJCD'] = $cod_ibge[1];

		$emails = explode(" ",$dados_posto['EMAIL']);
		$emails = array_filter($emails);
		
		if(count($emails) > 0){
			$dados_posto['EMAIL'] = $emails[0];
		}else{
			$dados_posto['EMAIL'] = '';
		}

		$email_posto = $dados_posto['EMAIL'];
		$sql = "SELECT fn_valida_email('$email_posto',true)";
		$res = pg_query($con,$sql);

		$email_valido = pg_result($res,0,0);

		if($email_valido == 't') {
		        $email = $dados_posto['EMAIL'];
		} else {
		       $email = 'null';
		}

		unset($linha);
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
		$fantasia = str_replace("'", "", utf8_decode($dados_posto['CC085_NOMEFANTASIA']));

		$cidade_nome = utf8_decode($dados_posto['TEXT']);
		$cidade_nome = str_replace("'","",$cidade_nome);
		$sql = "SELECT cod_ibge FROM tbl_cidade WHERE upper(nome) = upper(fn_retira_especiais('{$cidade_nome}')) AND estado = '{$dados_posto['REGION']}'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
			throw new Exception("Cidade informada para o posto {$dados_posto['ARBPL']} não foi encontrada");
		}else{
			if($dados_posto['TXJCD'] != pg_fetch_result($res,0,'cod_ibge')){
				throw new Exception("Código IBGE informado para o posto {$dados_posto['ARBPL']} é diferente ao da cidade informada");
			}
		}

		msg_log("Verifiando se o posto {$dados_posto['ARBPL']} já pussui cadastro");
		$sql = "SELECT posto FROM tbl_posto WHERE cnpj = '{$dados_posto['STCD1']}'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
		
			msg_log("Cadastrando o posto {$dados_posto['ARBPL']}");

			$contato = substr($dados_posto['CC130_CONTATOGARANTIA'], 0, 30);

			 $sql = "INSERT INTO tbl_posto (
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
				)VALUES(
					'{$razao}',
					'{$fantasia}',
					'{$dados_posto['STCD1']}',
					'{$dados_posto['STCD3']}',
					'{$dados_posto['STREET']}',
					'{$dados_posto['HOUSE_NUM1']}',
					'{$dados_posto['CITY2']}',
					'{$dados_posto['POST_CODE1']}',
					fn_retira_especiais('{$cidade_nome}'),
					'{$dados_posto['REGION']}',
					'{$dados_posto['CC090_TELEFONEGARANTIA']}',
					'{$contato}',
					'{$dados_posto['TXJCD']}'
				) RETURNING posto";

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
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
		
			msg_log("Cadastrando o posto {$dados_posto['ARBPL']} para a {$fabrica_nome}");
			$sql = "INSERT INTO tbl_posto_fabrica (
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
					cod_ibge
				) VALUES (
					$posto,
					$login_fabrica,
					'*',
					643,
					TRIM('{$dados_posto['ARBPL']}'),
				        '{$dados_posto['CC065_COD_FORNEC']}',
					'{$dados_posto['WERKS']}',
					'$credenciamento',
					'{$dados_posto['CC090_TELEFONEGARANTIA']}',
					'{$dados_posto['STREET']}',
					'{$dados_posto['HOUSE_NUM1']}',
					(E'{$dados_posto['CITY2']}'),
					'{$dados_posto['POST_CODE1']}',
					fn_retira_especiais('{$cidade_nome}'),
					'{$dados_posto['REGION']}',
					'$email',
					(E'{$fantasia}'),
					(E'{$dados_posto['CC130_CONTATOGARANTIA']}'),
					{$dados_posto['TXJCD']}
				)";
			$res = pg_query($con,$sql);

			if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao tentar cadastrar o posto - {$dados_posto['ARBPL']} para a {$fabrica_nome}");
                        }

                        msg_log("Cadastro realizado com sucesso para o posto - {$dados_posto['ARBPL']} para a {$fabrica_nome}");
		}else{
			msg_log("Atualizando os dados do posto {$dados_posto['ARBPL']} para a {$fabrica_nome}");
			$sql = "UPDATE tbl_posto_fabrica SET
				codigo_posto = TRIM('{$dados_posto['ARBPL']}'),
				tipo_posto   = 643,
				conta_contabil = '{$dados_posto['CC065_COD_FORNEC']}',
                                centro_custo = '{$dados_posto['WERKS']}',
				contato_endereco = '{$dados_posto['STREET']}',
				contato_bairro = (E'{$dados_posto['CITY2']}'),
				contato_cep = '{$dados_posto['POST_CODE1']}',
				contato_cidade = fn_retira_especiais('{$cidade_nome}'),
				contato_estado = '{$dados_posto['REGION']}',
				nome_fantasia = (E'{$fantasia}'),
				contato_email = '{$email}',
				contato_nome = (E'{$dados_posto['CC130_CONTATOGARANTIA']}'),
				credenciamento = upper('{$credenciamento}'),
				cod_ibge = {$dados_posto['TXJCD']}
				WHERE tbl_posto_fabrica.posto = $posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao tentar atualizar os dados do posto - {$dados_posto['ARBPL']} para a {$fabrica_nome}");
                        }

                        msg_log("Cadastro atualizado com sucesso para o posto - {$dados_posto['ARBPL']} e para a {$fabrica_nome}");
		}

		msg_log("Consultado se o posto {$dados_posto['ARBPL']} possui registro na tbl_credenciamento para a {$fabrica_nome}");
		$sql = "SELECT status FROM tbl_credenciamento WHERE posto = {$posto} AND fabrica = {$login_fabrica} ORDER BY credenciamento DESC LIMIT 1";
		$res = pg_query($con,$sql);
		$status = pg_fetch_result($res,0,'status');
		
		if(pg_num_rows($res) == 0 OR $status <> $credenciamento){
			
			msg_log("Cadastrando registro na tbl_credenciamento para o posto {$dados_posto['ARBPL']} referente à {$fabrica_nome}");
			$sql = "INSERT INTO tbl_credenciamento(
					posto,
					fabrica,
					status,
					dias
				)VALUES(
					{$posto},
					{$login_fabrica},
					'{$credenciamento}',
					$dias
				)";
			$res = pg_query($con,$sql);

			if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao tentar cadastrar o status {$credenciamento} para o posto - {$dados_posto['ARBPL']}");
                        }

                        msg_log("O status {$credenciamento} foi cadastrado com sucesso para o posto - {$dados_posto['ARBPL']}");
		}

		if(count($linhas) > 0){

			foreach($linhas AS $k => $v){
	
				if($v == "Array") continue;
				$codigo_linha = utf8_decode($v);
				msg_log("Verificando se a linha {$v} existe no sistema");
				$sql = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND upper(fn_retira_especiais(codigo_linha)) = upper(fn_retira_especiais('{$codigo_linha}'))";
				$res = pg_query($con,$sql);
			
				if(pg_num_rows($res) > 0){
					
					
					$linha = pg_fetch_result($res,0,'linha');
					if(in_array($linha,array(1057,1058))) {

						$sql = "UPDATE tbl_posto_fabrica set tipo_posto = 644 where posto = $posto and fabrica = $login_fabrica";
                                         	$res = pg_query($con,$sql);

                                                if (strlen(pg_last_error()) > 0) {
                                                        throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} como revenda");
                                                }
                                                msg_log("O posto {$dados_posto['ARBPL']} foi marcado como revenda");

					}

					msg_log("Verificando se o posto {$dados_posto['ARBPL']} possui registro na tbl_posto_linha para a linha {$v}");
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = {$posto} AND linha = {$linha}";
					$res = pg_query($con,$sql);

					if(pg_num_rows($res) == 0){

						msg_log("Inserindo registro na tbl_posto_linha para o posto {$dados_posto['ARBPL']} e a linha {$v}");
						$sql = "INSERT INTO tbl_posto_linha(
								posto,
								tabela,
								linha
							)VALUES(
								{$posto},
								1090,
								{$linha}
							)";
						$res = pg_query($con,$sql);
						
						if (strlen(pg_last_error()) > 0) {
						    throw new Exception("Erro ao tentar cadastrar o posto - {$dados_posto['ARBPL']} para a linha [$v}");
						}

						msg_log("Cadastro realizado com sucesso para o posto - {$dados_posto['ARBPL']} e para a linha {$v}");
					}

					if(strpos($v,'Eletro') !== false){
						msg_log("Iniciando atualização do posto {$dados_posto['ARBPL']} para poder Digitar OS");
						$sql = "UPDATE tbl_posto_fabrica SET 
							digita_os = true 
							WHERE fabrica = {$login_fabrica} 
							AND posto = {$posto}";
						$res = pg_query($con,$sql);

						if (strlen(pg_last_error()) > 0) {
							throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} para Digitar OS");
						}
						msg_log("O posto {$dados_posto['ARBPL']} foi marcado para Digitar OS");
					}

				}else{
					msg_log("Linha {$v} não possui cadastro no sistema");
				}
			}
				if(empty($linha)) {

					$sql = "UPDATE tbl_posto_fabrica set tipo_posto = 644 where posto = $posto and fabrica = $login_fabrica";
					 $res = pg_query($con,$sql);

                                                if (strlen(pg_last_error()) > 0) {
                                                        throw new Exception("Erro ao marcar o posto {$dados_posto['ARBPL']} como revenda");
                                                }
                                                msg_log("O posto {$dados_posto['ARBPL']} foi marcado como revenda");

				}

		}

	}catch(Exception $e){
		
	    msg_log("Erro: ".$e->getMessage());
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
            $logError->setErrorMessage($e->getMessage());
            $logError->Insert();
	}
	//die;
/*    $nome_arquivo = 'rotina_importa_posto_'.date('Ymd').'.txt';
    $arquivo_log  = "/tmp/$nome_arquivo";

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

    if(!$tdocs->uploadFileS3($arquivo, $routine_id)){
        throw new Exception("Não foi possível enviar o arquivo de log para o Tdocs. Erro: ".$tdocs->error);
    }
*/
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
