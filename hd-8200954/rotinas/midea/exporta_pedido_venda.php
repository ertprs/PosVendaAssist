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
$status_final = 1;
$status_mensagem = 'Rotina Finalizada';
$nome_rotina = "Exporta Pedido Venda - Midea";
$dir = __DIR__."/entrada";
$dataAtual = date("Y-m-d-H-i");

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

function msg_log($msg){
    echo "\n".date('H:i:s')." - $msg";
}

try{

    $logClass           = new Log2();
    $logClassCliente    = new Log2();
    $logClassCliente->adicionaLog(array("titulo" => "Log - Exportação de pedidos de Venda BS - $dataAtual - Midea Carrier"));
    $logClass->adicionaLog(array("titulo" => "Log - Exportação de pedidos de Venda BS - $dataAtual - Midea Carrier"));

    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("lucas.carlos@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    }

    $logClassCliente->adicionaEmail("ldeidelwein@mideacarrier.com");

	msg_log('Inicia rotina de exportação de pedido de venda');

    $arrayLog[] = "Inicia rotina de exportação de pedido de venda"; 

	$routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Exporta Pedido de Venda");
    $routine_id = $arr[0]["routine"];

    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if (!strlen($routine_schedule_id)) {
        $arrayLog[] = "Agendamento da rotina não encontrado"; 
        throw new Exception("Agendamento da rotina não encontrado");
    }

    $routineScheduleLog = new Log();
    $oLogError          = new LogError();

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
        $routineScheduleLog->setStatusMessage(utf8_encode($status_mensagem));
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
	$urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/BlueService.asmx?WSDL";
} else {
	$urlWSDL = "http://ws.carrieronline.com.br/wsPSAtelecontrol/blueservice.asmx?WSDL";
}
  
    $sql_principal = "SELECT 
            tbl_pedido.pedido, 
            tbl_pedido.data, 
            tbl_pedido.posto, 
            tbl_pedido.total, 
            tbl_pedido.valores_adicionais, 
            tbl_pedido.finalizado, 
            tbl_tipo_pedido.codigo as codigo_tipo_pedido, 
            tbl_condicao.codigo_condicao
        FROM tbl_pedido 
        JOIN tbl_tipo_pedido on tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
        JOIN tbl_condicao on tbl_condicao.condicao = tbl_pedido.condicao
        WHERE tbl_pedido.fabrica = $login_fabrica 
        AND tbl_pedido.status_pedido = 1 
        AND tbl_tipo_pedido.pedido_faturado = true  
        AND tbl_pedido.exportado is null
        AND tbl_pedido.total > 0 
        AND tbl_pedido.finalizado  NOTNULL ";

    $res_principal = pg_query($con, $sql_principal);

    $request = array(); 
    
    for($i = 0; $i<pg_num_rows($res_principal); $i++){
    	$pedido = pg_fetch_result($res_principal, $i, 'pedido');
        $id_posto = pg_fetch_result($res_principal, $i, 'posto');
    	$data = substr(pg_fetch_result($res_principal, $i, 'data'), 0, 10);
    	$total = pg_fetch_result($res_principal, $i, 'total');
    	$finalizado = pg_fetch_result($res_principal, $i, 'finalizado');
    	$codigo_tipo_pedido = pg_fetch_result($res_principal, $i, 'codigo_tipo_pedido');
    	$codigo_condicao = pg_fetch_result($res_principal, $i, 'codigo_condicao');

    	$valores_adicionais = pg_fetch_result($res_principal, $i, 'valores_adicionais');
    	$valores_adicionais = json_decode($valores_adicionais , true);
    	$escritorio_venda = $valores_adicionais['escritorio_venda'];
    	$equipe_venda = $valores_adicionais['equipe_venda'];

        $sqlCodigoCliente = "select parametros_adicionais from tbl_posto_fabrica WHERE posto = $id_posto and fabrica = $login_fabrica "; 
        $resCodigoCliente = pg_query($con, $sqlCodigoCliente);

	    $codigo_cliente = ""; 
        if(pg_num_rows($resCodigoCliente)>0){
            $parametros_adicionais = json_decode(pg_fetch_result($resCodigoCliente, 0, 'parametros_adicionais'), true);
            $codigo_cliente = $parametros_adicionais['codigo_cliente'];

            if(strlen(trim($codigo_cliente)) == 0 ){
                $arr_posto_codigo_cliente[] = "$pedido";
                $arrayLog[] = "Pedido sem codigo de cliente: $pedido";
                continue; 
            }		
        }

        $itens_pedido = ""; 

        $arrayLog[] = "Exportando pedido $pedido "; 

        $cabecalho = '<CAB_PEDIDO>
                        <NR_PEDIDO>'.str_pad($pedido, 10, '0', STR_PAD_LEFT).'</NR_PEDIDO>
                        <DT_PEDIDO>'.str_replace("-", "", $data).'</DT_PEDIDO>
                        <BUKRS>B001</BUKRS>
                        <KUNNR>'.$codigo_cliente.'</KUNNR>
                        <TP_PEDIDO>'.$codigo_tipo_pedido.'</TP_PEDIDO>
                        <CD_PAGTO>'.$codigo_condicao.'</CD_PAGTO>
                        <MOTIVO></MOTIVO>
                        <INCOTERMS1>CIF</INCOTERMS1>
                        <INCOTERMS2>CIF</INCOTERMS2>
                        <ESC_VENDA>'.$escritorio_venda.'</ESC_VENDA>
                        <EQUIP_VENDA>'.$equipe_venda.'</EQUIP_VENDA>
                    </CAB_PEDIDO>';

		$sql_itens = "SELECT tbl_pedido_item.pedido_item, 
						tbl_pedido_item.peca,  
						tbl_pedido_item.qtde, 
						tbl_pedido_item.preco, 
						tbl_peca.referencia, 
						tbl_peca.descricao 
						FROM tbl_pedido_item 
						JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = $login_fabrica 
						WHERE pedido = $pedido 
                        order by pedido_item ";     	
		$res_itens = pg_query($con, $sql_itens);
		for($a=0; $a<pg_num_rows($res_itens); $a++){
			$pedido_item = pg_fetch_result($res_itens, $a, 'pedido_item');

			$peca = pg_fetch_result($res_itens, $a, 'peca');
			$qtde = pg_fetch_result($res_itens, $a, 'qtde');
			$preco = number_format(pg_fetch_result($res_itens, $a, 'preco'), 2, '.', '');
			$referencia = pg_fetch_result($res_itens, $a, 'referencia');
			$descricao = pg_fetch_result($res_itens, $a, 'descricao');	

            $itens_pedido .= "
            <ITE_PEDIDO>
                <NR_PEDIDO>".str_pad($pedido, 10, '0', STR_PAD_LEFT)."</NR_PEDIDO>
                <NR_ITEM>".($a+1)."</NR_ITEM>
                <MATNR>".$referencia."</MATNR>
                <QT_ITEM>".$qtde."</QT_ITEM>
                <VR_PRECO>".$preco."</VR_PRECO>
                <ID_IT_TELECONTROL>".str_pad($pedido_item, 12, '0', STR_PAD_LEFT)."</ID_IT_TELECONTROL>
            </ITE_PEDIDO>";

		}

        $xml = '<Z_CB_TC_GRAVA_PED_BLUE_SERVICE xmlns="http://tempuri.org/">
            <oXml>
                <Z_CB_TC_GRAVA_PED_BLUE_SERVICE xmlns="http://ws.carrieronline.com.br/PSA_WebService">
                    '.$cabecalho.'
                   '.$itens_pedido.'
                </Z_CB_TC_GRAVA_PED_BLUE_SERVICE>
            </oXml>
        </Z_CB_TC_GRAVA_PED_BLUE_SERVICE>';

        $arrayLog[] = "XML Enviado ";             
        $arrayLog[] =  json_encode(array("xml" => simplexml_load_string($xml) ));

        $client = new SoapClient($urlWSDL, array('trace' => 1));
        $params = new \SoapVar($xml, XSD_ANYXML);
        $result = $client->Z_CB_TC_GRAVA_PED_BLUE_SERVICE($params);

        $arquivo = $result->Z_CB_TC_GRAVA_PED_BLUE_SERVICEResult->any;

        $arquivo = simplexml_load_string($arquivo);

        $arrayLog[] = " Retorno recebido ";
        $arrayLog[] =  json_encode(array("xml_retorno" => $arquivo ));

        $dados = json_decode(json_encode((array)$arquivo), true);
        $message = "";        
        if(isset($dados['NewDataSet']['PE_MENSAGENS'])){

            if($dados['NewDataSet']['PE_MENSAGENS']["ID"] == "ZCB_TELECONTROL" and in_array($dados['NewDataSet']['PE_MENSAGENS']["NUMBER"], ["000","002"]) ){

                $msg_erro = ""; 
                pg_query($con, "BEGIN;");
               
                $message =  "<br>". $dados['NewDataSet']['PE_MENSAGENS']['MESSAGE'];
                $sql_exportado = "UPDATE tbl_pedido set obs = obs || '$message', status_pedido = 9 where pedido = $pedido";
                $res_exportado = pg_query($con, $sql_exportado);

                if(strlen(pg_last_error($con))>0){
                    $msg_erro = "\n Falha ao exportar pedido $pedido - ". pg_last_error($con); 
                }            

                if(strlen(trim($msg_erro))>0){
                    pg_query($con, "ROLLBACK;");
                    $arrayLog[] = $msg_erro; 
                }else{
                    pg_query($con, "COMMIT;");
                }
            }
        }
    }
    //Criar arquivo de log    
    $logClass->adicionaLog(implode("<br />", $arrayLog));
    $logClass->enviaEmails();

    $dadosSalvar = implode("\n", $arrayLog);
    $arq = $dir . '/retorno-exportacao-pedido-venda-'. date('Ymd_His'). '.txt';                
    $arq_log = fopen($arq, "w");
    fwrite($arq_log, $dadosSalvar);
    fclose($arq_log);

    if(count(array_filter($arr_posto_codigo_cliente))>0){
        $logClassCliente->adicionaLog('Pedido não exportado por falta de código do cliente');    
        $logClassCliente->adicionaLog(implode("<br />", $arr_posto_codigo_cliente));
        $logClassCliente->enviaEmails(); 
    }

    $phpCron->termino();

} catch (Exception $e) {
    msg_log("Erro: ".$e->getMessage());

    $status_final = 2;
    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    $routineScheduleLog->Update();
}
?>
