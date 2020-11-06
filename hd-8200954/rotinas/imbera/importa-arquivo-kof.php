<?php
$arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
$processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
$arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

$count_routine = 0;
foreach ($processos as $value) {
	if (preg_match("/(.*)php (.*)\/imbera\/{$arquivo_rotina}/", $value)) {
		$count_routine += 1;
	}
}

if ($count_routine > 2) {
	die("ja esta em execucao");
}

$no_pdo = true;
include dirname(__FILE__).'/../../dbconfig.php';
include dirname(__FILE__).'/../../includes/dbconnect-inc.php';
require dirname(__FILE__).'/../funcoes.php';
include dirname(__FILE__).'/../../Posvenda/Fabricas/_158/ImportaArquivo.php';
$path = dirname(__FILE__).'/entrada';

if ($_serverEnvironment == "production") {
	$pathProcessed = '/mnt/webuploads/imbera/processado';
} else {
	$pathProcessed = dirname(__FILE__).'/processado';
}

global $fabrica;
$fabrica = 158;

use Posvenda\Log;
use Posvenda\LogError;
use Posvenda\RoutineSchedule;

$fileColumns = array(
    'centroDistribuidor', 'branco', 'idCliente',
    'nomeCliente', 'enderecoCliente', 'bairroCliente',
    'cepCliente', 'cidadeCliente', 'estadoCliente',
    'paisCliente', 'telefoneCliente', 'telefoneCliente2',
    'numeroAtivo', 'modeloKof',  'patrimonioKof',  'osKof',
    'protocoloKof',   'grupoCatalogoKof', 'categoriaDefeito',
    'codDefeito', 'defeito', 'dataAbertura',   'horaAbertura',
    'apelidoContato',  'nomeContato', 'comentario',  'nomeFantasia',
    'tipoOrdem',  'descricaoTipo',   'classeAtividade', 'categoriaEquipamento',
    'garantia',    'numeroSerie', 'descricaoEquipamento', 'longitude', 'latitude'
);
$tableFields = array(
    'tbl_hd_chamado_cockpit' => array(
         'centroDistribuidor', 'branco', 'idCliente',
         'nomeCliente', 'enderecoCliente', 'bairroCliente',
         'cepCliente', 'cidadeCliente', 'estadoCliente',
         'paisCliente', 'telefoneCliente','telefoneCliente2',
         'numeroAtivo', 'modeloKof',  'patrimonioKof',  'osKof',
         'protocoloKof',   'grupoCatalogoKof', 'categoriaDefeito',
         'codDefeito', 'defeito', 'dataAbertura', 'apelidoContato',  
         'nomeContato', 'comentario',  'nomeFantasia', 'tipoOrdem',
         'descricaoTipo',   'classeAtividade', 'categoriaEquipamento',
         'garantia',    'numeroSerie', 'descricaoEquipamento', 'longitude', 'latitude'
    )
);

$dir = opendir($path);

if ($dir) {
    $fileExists = false;
    $oRoutineSchedule = new RoutineSchedule();

    $oRoutineSchedule->setWeekDay(date('w'));
    $oRoutineSchedule->setRoutine(2);
    $routine_schedule = $oRoutineSchedule->SelectRoutineSchedule();

    while(false !== ($file = readdir($dir))) {
        if (strtolower(end(explode(".", $file))) == 'txt') {
            $fileExists = true;

            $oLog = new Log();
            $oLog->setFileName($file);
            if (!$oLog->CheckFileProcessed()) {

                $separators = array(chr(hexdec("A6")), "|");

                $importFile = new \Posvenda\Fabricas\_158\ImportaArquivo($path."/".$file, $routine_schedule, $separators, $fileColumns, $tableFields, $fabrica);
                $importFile->readFile();
                $data = $importFile->getDataRows();
                $logId = $importFile->getLogId();
                $oLogError = new LogError();
                $totalRecordProcessed = $importFile->getTotalRecordProcessed();

                // $develApplicationKey = '519e67fe737c5de1c5656f1c08f9eac902c5eb25';
                // $productionApplicationKey = '701c59e0eb73d5ffe533183b253384bd52cd6973';
                // if($_serverEnvironment == 'development'){
                //     $applicationKey = $develApplicationKey;
                //     $accessEnv = 'DEVEL';
                // }else{
                //     $applicationKey= $productionApplicationKey;
                //     $accessEnv = 'PRODUCTION';
                // }

                if (count($data) > 0) {
                    
                    // $token = generateToken($applicationKey);
                    $erro = false;

                    foreach($data as $table => $data){

                        // if(!validateToken($applicationKey, $token)){
                        //     $token = generateToken($applicationKey);
                        // } else {
                            // $postResult = postData($data['tbl_hd_chamado_cockpit'], $applicationKey, $token, $accessEnv);
                            
                            //$cliente                             = $data['tbl_hd_chamado_cockpit']['idCliente'];
                            //$patrimonio                          = $data['tbl_hd_chamado_cockpit']['patrimonioKof'];
                            //list($data_abertura, $hora_abertura) = explode(" ", $data['tbl_hd_chamado_cockpit']['dataAbertura']);
                            //$data_abertura                       = str_replace("/", "\\\\\\\\/", $data_abertura);
                            //$tipo_atendimento                    = $data['tbl_hd_chamado_cockpit']['tipoOrdem'];

                            /*$sql = "
                                SELECT hd_chamado_cockpit
                                FROM tbl_hd_chamado_cockpit
                                WHERE fabrica = {$fabrica}
                                AND dados ~ '\"idCliente\":\"{$cliente}\"'
                                AND dados ~ '\"patrimonioKof\":\"{$patrimonio}\"'
                                AND dados ~ '\"dataAbertura\":\"{$data_abertura}'
                                AND dados ~ '\"tipoOrdem\":\"{$tipo_atendimento}\"'
                            ";
                            $res = pg_query($con, $sql);

                            if (strlen(pg_last_error()) > 0) {
                                $contents = $importFile->getContents($table);

                                $oLogError->setRoutineScheduleLog($logId);
                                $oLogError->setLineNumber($table);
                                $oLogError->setContents($contents);
                                $oLogError->setErrorMessage(utf8_encode('Erro ao validar ticket'));

                                $oLogError->Insert();
                                $totalRecordProcessed = $totalRecordProcessed - 1;

                                $erro = true;
                            } else if (pg_num_rows($res) > 0) {
                                $contents = $importFile->getContents($table);

                                $oLogError->setRoutineScheduleLog($logId);
                                $oLogError->setLineNumber($table);
                                $oLogError->setContents($contents);
                                $oLogError->setErrorMessage(utf8_encode('Ticket já processado pelo sistema'));

                                $oLogError->Insert();
                                $totalRecordProcessed = $totalRecordProcessed - 1;

                                $erro = true;
                            } else {*/
                				$postResult = postData($data['tbl_hd_chamado_cockpit'], $logId);
                				
                                if ($postResult[0]['hd_chamado_cockpit']) {

                                    $hdChamadoCockpit = $postResult[0]['hd_chamado_cockpit'];
                                    $dadosResult = json_decode($postResult[0]['dados'], true);
                                    $referenciaProduto = $dadosResult['modeloKof'];
                                    $idCliente = trim($dadosResult['idCliente']);

                                    $sql = "
                                        SELECT
                                            tbl_familia.familia,
                                            tbl_familia.descricao
                                        FROM tbl_familia
                                        JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$fabrica}
                                        WHERE tbl_familia.fabrica = {$fabrica}
                                        AND tbl_produto.referencia = '{$referenciaProduto}';
                                    ";
                                    $res = pg_query($con, $sql);
                                    $familiaDescricao = pg_fetch_result($res, 0, descricao);
                                    $familiaId        = pg_fetch_result($res, 0, familia);

                                    $sqlCli = "
                                        SELECT
                                            tbl_cliente.contrato
                                        FROM tbl_fabrica_cliente
                                        JOIN tbl_cliente USING(cliente)
                                        WHERE tbl_fabrica_cliente.fabrica = {$fabrica}
                                        AND tbl_cliente.codigo_cliente = '{$idCliente}';
                                    ";

                                    $resCli = pg_query($con, $sqlCli);
                                    $keyAccount = pg_fetch_result($resCli, 0, contrato);

                                    $prios = array(
                                         "REFRIGERADOR" => "Normal",
                                         "POST MIX" => "Alta",
                                         "MAQUINA DE CAFE" => "Alta",
                                         "CHOPEIRA" => "Alta",
                                     );

                                    if ($dadosResult["tipoOrdem"] == "ZKR6") {
                                       $prioridade = 'Baixa';
                                    } else {
                                        if (array_key_exists($familiaDescricao, $prios)) {
                                            $prioridade = $prios[$familiaDescricao];
                                        } else {
                                            $prioridade = 'Normal';
                                        }
                                    }

                                    if ($keyAccount == 't') {
                                        $prioridade .= ' KA';
                                    }

                                    $sqlPrioridade = "
                                        SELECT
                                            hd_chamado_cockpit_prioridade
                                        FROM tbl_hd_chamado_cockpit_prioridade
                                        WHERE fabrica = {$fabrica}
                                        AND descricao = '{$prioridade}';
                                    ";

                                    $resPrioridade = pg_query($con, $sqlPrioridade);
                                    $idPrioridade = pg_fetch_result($resPrioridade, 0, hd_chamado_cockpit_prioridade);

                                    $upCockpit = "
                                        UPDATE tbl_hd_chamado_cockpit SET 
                                            hd_chamado_cockpit_prioridade = {$idPrioridade},
                                            familia = {$familiaId}
                                        WHERE hd_chamado_cockpit = {$hdChamadoCockpit};
                                    ";
                                    $resUpCockpit = pg_query($con, $upCockpit);

                                } else {
                                    $contents = $importFile->getContents($table);

                                    $oLogError->setRoutineScheduleLog($logId);
                                    $oLogError->setLineNumber($table);
                                    $oLogError->setContents($contents);
                                    $oLogError->setErrorMessage(utf8_encode('Erro ao gravar ticket'));

                                    $oLogError->Insert();
                                    $totalRecordProcessed = $totalRecordProcessed - 1;

                                    $erro = true;
                                }
                            //}
                        // }
                    }
                }

                if ($erro == true) {
                    $oLog->setTotalRecordProcessed($totalRecordProcessed);
                    $oLog->setStatus(2);
                    $oLog->setStatusMessage('Processado Parcial');
                }

                $oLog->setRoutineScheduleLog($logId);
                $oLog->setDateFinish(date('Y-m-d H:i:s'));
                $oLog->Update();

                if (copy($path."/".$file, $pathProcessed."/".$file)) {
                    unlink($path."/".$file);
                }

            } else {
                $oLog->setRoutineSchedule($routine_schedule);
                $oLog->setDateStart(date('Y-m-d H:i:s'));
                $oLog->setDateFinish(date('Y-m-d H:i:s'));
                $oLog->setFileName($file);
                $oLog->setStatus(0);
                $oLog->setStatusMessage('Arquivo já importado');

                $oLog->Insert();

                if (copy($path."/".$file, $pathProcessed."/".$file)) {
                    unlink($path."/".$file);
                }
            }
        }
    }

    if (!$fileExists) {
        $oLog = new Log();
        $oLog->setRoutineSchedule($routine_schedule);
        $oLog->setDateStart(date('Y-m-d H:i:s'));
        $oLog->setDateFinish(date('Y-m-d H:i:s'));
        $oLog->setStatus(0);
        $oLog->setStatusMessage(utf8_encode('Nenhum arquivo encontrado'));

        $oLog->Insert();
    }

    closedir($dir);
}

function validateToken($applicationKey, $token){
    $application = 'CALLCENTER';
    $url = 'http://api2.telecontrol.com.br/AccessControl/validation' . '/token/' . $token . '/application-key/' . $applicationKey;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    $result = curl_exec($ch);

    if(!$result){
        return false;
    }
    curl_close($ch);

    $validationData = validateResponseReturningArray($result);

    if($validationData['status'] == 'VALID'){
        return true;
    }else{
        return false;
    }
}

function generateToken($applicationKey) {
    $application = 'CALLCENTER';
    $url = 'http://api2.telecontrol.com.br/AccessControl/token';

    $fields = array(
        'applicationKey' => $applicationKey,
        'application' => 'CALLCENTER'
    );
    $json = json_encode($fields);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json"
      ));

    $result = curl_exec($ch);
    if(!$result){
        return false;
    }
    curl_close($ch);

    $tokenData = validateResponseReturningArray($result);

    return $tokenData['token'];
}

function postData($dados, $log_id) {
	global $con, $fabrica;

	$dados = json_encode($dados);
	$dados = pg_escape_string($con, $dados);
	$sql = "
		INSERT INTO tbl_hd_chamado_cockpit
		(fabrica, dados, routine_schedule_log)
		VALUES
		({$fabrica}, '{$dados}', {$log_id})
		RETURNING hd_chamado_cockpit, dados
	";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
		return false;
	} else {
		return pg_fetch_all($res);
	}
}

/*function postData($dados, $applicationKey, $token, $accessEnv){
    global $fabrica;
    $application = 'CALLCENTER';
    $url = 'http://api2.telecontrol.com.br/Callcenter/IntegrationCockpit';

    $oLog = new Log();

    $fields = array(
        'dados' => $dados,
        'fabrica' => $fabrica,
        'routine_schedule_log' => $oLog->SelectId()
    );
    $json = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Access-Token: ".$token,
        "Access-Application-Key: " . $applicationKey,
        "Access-Env: " . $accessEnv
    ));

    $result = curl_exec($ch);

    if(!$result){
        return false;
    }

    curl_close($ch);

    return validateResponseReturningArray($result);
}*/

function validateResponseReturningArray($curlResult){
    $arrResult = json_decode($curlResult, true);
    if(array_key_exists('exception', $arrResult)){
        return false;
    } else {
        return $arrResult;
    }
}
