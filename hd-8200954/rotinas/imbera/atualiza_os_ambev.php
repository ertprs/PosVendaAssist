<?php 

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require __DIR__.'/../../funcoes.php';

use Posvenda\Cockpit;
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;
use Posvenda\Model\Produto;
use Posvenda\Model\Linha;

include __DIR__.'/../../class/tdocs.class.php';

$debug   = true;
$fabrica = 158;

$routine = new Routine();
$routine->setFactory($fabrica);

$arr = $routine->SelectRoutine("Atualiza OS Ambev");
$routine_id = $arr[0]["routine"];

$routineSchedule = new RoutineSchedule();
$routineSchedule->setRoutine($routine_id);
$routineSchedule->setWeekDay(date("w"));

$routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

if (!strlen($routine_schedule_id)) {
    throw new \Exception("Agendamento da rotina não encontrado");
}

$routineScheduleLog = new Log();

if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $_serverEnvironment == "production") {
    die("Rotina já está em execução");
} else {
    $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
    $routineScheduleLog->setDateStart(date("Y-m-d H:i"));

    if (!$routineScheduleLog->Insert()) {
        throw new \Exception("Erro ao gravar log da rotina");
    }

    $routine_schedule_log_id = $routineScheduleLog->SelectId();
    $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);
}

function formataDataAmbev($data){
	if(!empty($data)){
		$data = str_replace(" ","T", $data).'Z';
	}
	return $data; 
}

//pegar token
function gerarToken(){

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://login.microsoftonline.com/cef04b19-7776-4a94-b89b-375c77a8f936/oauth2/v2.0/token",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => "client_id=b720fbe5-8133-41e2-9b3c-e32d67ed57d4&scope=581143d2-13d8-4408-989a-8482a08c8d43%2F.default&grant_type=client_credentials&client_secret=-5ATfpj9xW2%3F-uP%5D1w%2F8yePmi4avDh0W",
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache",
	    "client_id: b720fbe5-8133-41e2-9b3c-e32d67ed57d4",
	    "client_secret: -5ATfpj9xW2?-uP]1w/8yePmi4avDh0W",
	    "content-type: application/x-www-form-urlencoded",
	    "grant_type: client_credentials",
	    "scope: 581143d2-13d8-4408-989a-8482a08c8d43/.default"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	$info = curl_getinfo($curl);

	curl_close($curl);	

	if ($err) {
	  	return array("erro" => true, "retorno" => $err, "statusCode" => $info['http_code']);
	} else {
		if($info['http_code'] == 200){
			return array("sucesso" => true, "retorno" => $response, "statusCode" => $info['http_code']);
		}else{
			return array("erro" => true, "retorno" => $response, "statusCode" => $info['http_code']);		
		}	  
	}
}

//API da Ambev
function enviarDadosToAmbev($dadosJson, $token){

	global $log; 
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://apim-dev.ambevdevs.com.br/icebev/maintenance/v2/os?api-version=v2",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $dadosJson,
	  CURLOPT_HTTPHEADER => array(
	    "authorization: bearer $token",
	    "cache-control: no-cache",
	    "content-type: application/json",
	    "ocp-apim-subscription-key: d17c39b997534909bc5be04686898dc7"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	$info = curl_getinfo($curl); 

	curl_close($curl);
	if ($err) {
	  	return array("erro" => true, "retorno" => $err, "statusCode" => $info['http_code']);
	} else {
		if($info['http_code'] == 200){
			return array("sucesso" => true, "retorno" => $response, "statusCode" => $info['http_code']);
		}else{
			return array("erro" => true, "retorno" => $response, "statusCode" => $info['http_code']);		
		}	  
	}
}


$log[] = "Iniciando atualização OS Ambev ".date("Y-m-d-H-i-s"). "\n";

// $ServiceStatusCode = array(
// 	'2' => 'Autorizado',
// 	'3' => 'Concluído',
// 	'4' => 'Agendado',
// 	'5' => 'Técnico a caminho',
// 	'6' =>  'Aguardando Peça',
// );

$sql = "SELECT 
		tbl_os.os, 
		tbl_os.finalizada, 
		tbl_os.os_posto, 
		tbl_os.data_conserto, 
		tbl_os_produto.os_produto, 
		tbl_tecnico_agenda.data_agendamento, 
		tbl_tecnico.nome as nome_tecnico, 
		tbl_os.serie,
		tbl_produto.referencia,
		tbl_produto.descricao,
		tbl_hd_chamado_cockpit.dados,
		tbl_os.data_modificacao as ultima_alteracao, tbl_tecnico_agenda.tecnico_agenda,
		(select os_item from tbl_os_item WHERE os_produto = tbl_os_produto.os_produto limit 1) as tem_peca,
		(select tbl_solucao.codigo from tbl_os_defeito_reclamado_constatado join tbl_solucao using(solucao) where os = tbl_os.os and solucao is not null order by defeito_constatado_reclamado desc limit 1 ) as codigo_solucao 
 		from tbl_os 
 		join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and tbl_os_campo_extra.fabrica = $fabrica
 		join tbl_hd_chamado_extra on tbl_hd_chamado_extra.os = tbl_os.os
 		join tbl_hd_chamado_cockpit on tbl_hd_chamado_cockpit.hd_chamado = tbl_hd_chamado_extra.hd_chamado and tbl_hd_chamado_cockpit.fabrica = $fabrica
		join tbl_os_produto on tbl_os_produto.os = tbl_os.os 
		left join tbl_tecnico_agenda on tbl_tecnico_agenda.os = tbl_os.os and tbl_tecnico_agenda.fabrica = $fabrica
		left join tbl_tecnico on tbl_tecnico_agenda.tecnico = tbl_tecnico.tecnico and tbl_tecnico.fabrica = $fabrica 
		join tbl_produto on tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = $fabrica 
where tbl_os.fabrica = $fabrica 
and tbl_os_campo_extra.campos_adicionais::JSON->>'unidadeNegocio' = '7300'
and (tbl_os.finalizada is null OR tbl_os.finalizada::date = current_date )
ORDER BY OS ";

$res = pg_query($con, $sql); 
$total_os = pg_num_rows($res);
if($total_os > 0 ){
	$retorno = gerarToken();
	if($retorno['erro'] == true){
		throw new \Exception("Falha ao gerar token");
	}

	$retorno = json_decode($retorno['retorno'], true); 	
	
	$token = $retorno['access_token']; 
	
	if(empty($token)){
		throw new \Exception("Falha ao gerar token");
	}

}
for($i=0; $i<$total_os; $i++){
	$arr_os = ""; 
	$error 	= ""; 
	$logError = ""; 

	$os = pg_fetch_result($res, $i, "os");
	$ultima_alteracao = pg_fetch_result($res, $i, "ultima_alteracao"); 
	$os_posto = pg_fetch_result($res, $i, "os_posto");

	$log[] = "OS Telecontrol: $os, OS Ambev: $os_posto \n\n";

	//$dados = pg_fetch_result($res, $i, "dados");
	$data_agendamento 		= pg_fetch_result($res, $i, 'data_agendamento'); 
	$nome_tecnico 			= pg_fetch_result($res, $i, 'nome_tecnico'); 
	$numero_serie 			= pg_fetch_result($res, $i, 'serie'); 
	$descricao 				= pg_fetch_result($res, $i, "descricao"); 
	$referencia 			= pg_fetch_result($res, $i, "referencia"); 
	$tem_peca 				= pg_fetch_result($res, $i, 'tem_peca');
	$codigo_solucao 		= pg_fetch_result($res, $i, 'codigo_solucao');
	$data_conserto 			= pg_fetch_result($res, $i, 'data_conserto');
	$tecnico_agenda 		= pg_fetch_result($res, $i, 'tecnico_agenda');
	$os_produto 			= pg_fetch_result($res, $i, 'os_produto');

	$sqlPecas = "SELECT tbl_peca.referencia 
				from tbl_os_item
				join tbl_peca on tbl_peca.peca = tbl_os_item.peca 
				where tbl_peca.fabrica = $fabrica
				and tbl_os_item.os_produto = $os_produto  "; 
	$resPecas = pg_query($con, $sqlPecas); 
	$arrPecas = ""; 
	for($pc=0; $pc<pg_num_rows($resPecas); $pc++){
		$referencia_p = pg_fetch_result($resPecas, $pc, 'referencia');
		$arrPecas[] = $referencia_p;
	}

	$sqlConstatado = "SELECT tbl_defeito_constatado.codigo 
					from tbl_os_defeito_reclamado_constatado 
					join tbl_defeito_constatado using(defeito_constatado) 
					where os = $os 
					and defeito_constatado is not null"; 
	$resConstatado = pg_query($con, $sqlConstatado); 
	$arrConstatado = "";
	for($dc=0; $dc<pg_num_rows($resConstatado); $dc++){
		$codigos_dc = pg_fetch_result($resConstatado, $dc, 'codigo');
		$arrConstatado[] = $codigos_dc;
	}

	$sqlSolucao = "SELECT tbl_solucao.codigo 
					from tbl_os_defeito_reclamado_constatado 
					join tbl_solucao using(solucao) 
					where os = $os 
					and solucao is not null";
	$resSolucao = pg_query($con, $sqlSolucao); 
	$arrSolucao = "";
	for($sl=0; $sl<pg_num_rows($resSolucao); $sl++){
		$codigos_s = pg_fetch_result($resSolucao, $sl, 'codigo');
		$arrSolucao[] = $codigos_s;
	}
	
	$conclusion_code = $codigo_solucao;
	$obs_tecnico = ""; 

	if(!empty($tecnico_agenda)){
		$status_situacao = 4;
	}
	if(!empty($tem_peca)){
		$status_situacao = 4;
	}
	if(!empty($data_conserto)){
		$status_situacao = 3;
	}

	$arr_os['update_os'] = array(
		"OS" => $os_posto,
		"update_date" => formataDataAmbev($ultima_alteracao),
		"update_info" => array(
			"partner_os_number" => $os_posto, 
			"service_status_code" => $status_situacao, // 
			"scheduled_date" => formataDataAmbev($data_agendamento),
			"STA" => array("name" => utf8_encode($nome_tecnico) ),
			"parts" => $arrPecas,
		    "faults" => $arrConstatado,
		    "repairs" => $arrSolucao,
		    "conclusion" => array(
		      "conclusion_date"=> formataDataAmbev($data_conserto),
		      "conclusion_code"=> $conclusion_code,
		      "conclusion_notes"=> $obs_tecnico
		    ),
		),
	); 

	$dadosJson = json_encode($arr_os);

	if(strlen(trim($dadosJson))==0){
		$log[] = "Falha ao gerar json da OS $os \n"; 

		$error[] = "Falha ao gerar json da OS $os \n";
	}else{
		$log[] = "Dados exportados ".$dadosJson; 
	}

	$enviar = enviarDadosToAmbev($dadosJson, $token); 

	if($enviar['erro'] == true){
		$error[] = "Error no envio da OS $os";
		$error[] = "statusCode: ".$enviar['statusCode'];
	}else{
		$log[] = "Dados de OS $os atualizados com sucesso";
		$total_atualizados++; 	
	}

	if(count(array_filter($error))>0){
		$logError = new \Posvenda\LogError();
		$logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
		$logError->setContents($enviar['retorno']);
		$logError->setErrorMessage(implode("|", $error));
		$logError->Insert();		
	}
}

$routineScheduleLog->setTotalRecord($total_os);
$routineScheduleLog->setTotalRecordProcessed($total_atualizados);
$routineScheduleLog->setStatus(1);
$routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");

$routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
$routineScheduleLog->Update();

?>