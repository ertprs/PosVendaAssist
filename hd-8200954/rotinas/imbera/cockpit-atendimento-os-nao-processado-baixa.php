<?php

ini_set("memory_limit","1024M");
date_default_timezone_set("America/Sao_Paulo");
$no_pdo = true;

require __DIR__.'/../../dbconfig.php';
require __DIR__.'/../../includes/dbconnect-inc.php';

require __DIR__.'/../../funcoes.php';

use Posvenda\Cockpit;
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\Model\Produto;
use Posvenda\Model\Linha;

include __DIR__.'/../../class/tdocs.class.php';

$debug   = true;
$fabrica = 158;

$routine = new Routine();
$routine->setFactory($fabrica);

$arr = $routine->SelectRoutine("Abertura de Tickets não Processados Baixa-Normal");
$routine_id = $arr[0]["routine"];

$routineSchedule = new RoutineSchedule();
$routineSchedule->setRoutine($routine_id);
$routineSchedule->setWeekDay(date("w"));

$routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

if (!strlen($routine_schedule_id)) {
    die("Agendamento da rotina não encontrado");
}

$routineScheduleLog = new Log();

$arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
$processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
$arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

$count_routine = 0;
foreach ($processos as $value) {
    if (preg_match("/(.*)php (.*)\/imbera\/{$arquivo_rotina}/", $value)) {
        $count_routine += 1;
    }
}
$em_execucao = ($count_routine > 2) ? true : false;

if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $em_execucao == false) {
    
    $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
    $routine_schedule_log_stopped = $routineScheduleLog->GetRoutineWithoutFinish();

    $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_stopped['routine_schedule_log']);
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage(utf8_encode('Rotina finalizada'));
    $routineScheduleLog->Update();

}

/* Limpando variáveis */
$routineScheduleLog->setRoutineSchedule(null);
$routineScheduleLog->setRoutineScheduleLog(null);
$routineScheduleLog->setDateFinish(null);
$routineScheduleLog->setStatus(null);
$routineScheduleLog->setStatusMessage(null);

if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $em_execucao == true) {

    die('Rotina em execução');

} else {

    $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
    $routineScheduleLog->setDateStart(date("Y-m-d H:i"));

    if (!$routineScheduleLog->Insert()) {
        die("Erro ao gravar log da rotina");
    }

    $routine_schedule_log_id = $routineScheduleLog->SelectId();
    $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);

}

function retira_acentos($texto){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
    return str_replace( $array1, $array2, $texto );
}

function traduzDiaSemana($diaSemana){
    $diaSemana = mb_strtoupper($diaSemana);
    switch ($diaSemana) {
        case 'SEGUNDA-FEIRA':
             $diaSemana = "Monday";
        break;
        case 'TERÇA-FEIRA':
             $diaSemana = "Tuesday";
        break;
        case 'QUARTA-FEIRA':
             $diaSemana = "Wednesday";
        break;
        case 'QUINTA-FEIRA':
             $diaSemana = "Thursday";
        break;
        case 'SEXTA-FEIRA':
             $diaSemana = "Friday";
        break;
        case 'SABADO':
             $diaSemana = "Saturday";
        break;
        case 'DOMINGO':
             $diaSemana = "Sunday";
        break;
        default:
            $diaSemana = "";
            break;
    }
    return $diaSemana; 
}

function buscaProximaDataPorDiaSemana($diaSemana){

    if(strlen(trim($diaSemana))==0){
        $diaSemanaIngles = date('l');
    }else{
        $diaSemanaIngles = traduzDiaSemana($diaSemana);    
    }    
    return date('Y-m-d', strtotime("next $diaSemanaIngles"));
}

//SOMENTE PARA AMBEV
function postData($hd_chamado_cockpit, $log_id, $familia) {
    global $con, $fabrica;

    $dados = json_encode($dados);
    $dados = pg_escape_string($con, $dados);
    $sql = "
        UPDATE tbl_hd_chamado_cockpit SET routine_schedule_log = $log_id, familia = $familia WHERE hd_chamado_cockpit = $hd_chamado_cockpit ";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
        return false;
    } else {
        return true;
    }
}


$arquivo_log = "/tmp/cockpit-atendimento-os-nao-processado-baixa-".date("YmdHi").".txt";
$arq_email = [];

if (!file_exists($arquivo_log)) {
    system("touch {$arquivo_log}");
}

function flushLog() {
    global $arquivo_log;

    $b = ob_get_contents();

    file_put_contents($arquivo_log, $b, FILE_APPEND);
    ob_end_flush();
    ob_clean();
    ob_start();
}

try {
    if ($debug) {
        ob_start();
    }

    $cockpit = new Cockpit($fabrica);
    $semHD   = $cockpit->getCockpitSemHD(false, true, false);

    $total_tickets           = count($semHD);
    $total_tickets_agendados = 0;
    $i = 0;

    foreach ($semHD as $json) {
        $i++;

        $hd_chamado_cockpit = $json["hd_chamado_cockpit"]; 
        $dados = json_decode($json["dados"], true);

        $empresa = "";
        if($dados['os']['empresa'] == 'Ambev' OR $dados['empresa']){

            $dadosf = $cockpit->formataDadosCockpit($dados['os']);

            if(empty($dadosf['modeloKof'])){                
                postData($hd_chamado_cockpit, $routine_schedule_log_id, 5370); 
            }

            $empresa = "AMBEV";
            if($dadosf != false){
                $dados = $dadosf; 
                $jsonDados = json_encode($dadosf); 
                $retorno = $cockpit->atualizaJson($hd_chamado_cockpit, $jsonDados);
            }
        }

        if ($cockpit->verificaOsAgendada($json["hd_chamado_cockpit"]) == true) {
            continue;
        }

        if (!empty($json["geolocalizacao"])) {
            $geolocalizacao = json_decode($json["geolocalizacao"], true);
        } else {
            $geolocalizacao = array();
        }

        $motivo_erro = $json["motivo_erro"];

        if ($dados) {
            if ($debug) {
                echo "\n";
                echo "iniciando processamento...\n";
                echo "arquivo kof: {$json['file_name']}\n";
                echo "ticket: {$json['hd_chamado_cockpit']}\n";
                echo "os kof: {$dados['osKof']}\n";
                flushLog();
                $arq_email[$i] = ["os_kof"=>$dados['osKof'],
                                  "localizacao"=>$dados["cidadeCliente"]."/".$dados["estadoCliente"],
                                  "data_abertura"=>$dados["dataAbertura"],
                                  "rotina"=>"cockpit-atendimento-os-nao-processado-baixa.php",
                                  "erros"=>[]
                                 ];
            }

            $processado = false;

            /**
             * Valida as informações do ticket para saber se pode abrir um atendimento
             */
            if ($debug) {
                echo "validando informações...\n";
            }

            $valido = $cockpit->validaHD($dados);

            if (!$valido["valid"]) {
                if (is_array($valido["message"])) {
                    $valido["message"] = implode(", ", $valido["message"]);
                }
                
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo $valido["message"]."\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = $valido["message"];
                }

                if (empty($valido["message"])) {
                    $valido["message"] = "Erro ao validar informações";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], utf8_encode($valido["message"]));
                continue;
            }

            if ($debug) {
                echo "informações validadas\n";
                flushLog();
            }


            /**
             * Buscar Produto
             */
            if ($debug) {
                echo "buscando produto {$dados["modeloKof"]}...\n";
            }

            $produtoClass = new Produto();
            $produto = $produtoClass->getProdutoByRef($dados["modeloKof"], $fabrica);

            if (empty($produto)) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Produto {$dados["modeloKof"]} não encontrado\n";
                    echo "\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Produto ".$dados["modeloKof"]." não encontrado";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Produto não encontrado");
                continue;
            }

            if ($debug) {
                echo "produto encontrado\n";
                echo "buscando linha do produto {$produto["linha"]}...\n";
            }

            $linhaClass = new Linha();
            $linha = $linhaClass->getData($produto["linha"], $fabrica);

            if (!$linha) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Linha do Produto {$produto["linha"]} não encontrada\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Linha do Produto ".$produto["linha"]." não encontrada";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Linha do Produto não encontrada");
                continue;
            }

            if ($debug) {
                echo "linha {$produto["linha"]} encontrada\n";
                echo "verificando se o auto agendamento está habilitado para linha do produto e tipo de atendimento...\n";
            }

            if (!$linha["auto_agendamento"] && $dados["tipoOrdem"] == "ZKR3") {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Agendamento manual\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Agendamento manual";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Agendamento manual");
                continue;
            }

            if ($debug) {
                echo "auto agendamento habilitado\n";
                flushLog();
            }

            /**
             * Buscar Localização do Cliente
             */
            if ($debug) {
                echo "buscando localização do cliente...\n";
            }

            if($empresa == 'AMBEV'){                
                postData($hd_chamado_cockpit, $routine_schedule_log_id, $produto['familia']);
            }

            $endereco_cliente = explode("  ", $dados["enderecoCliente"]); //esse explode serve para remover número ou complemento que é recebido com uma separação de 2 espaços após o endereço
            $endereco_cliente = $endereco_cliente[0];

            $endereco = array(
                "endereco" => $endereco_cliente,
                "bairro"   => $dados["bairroCliente"],
                "cidade"   => $dados["cidadeCliente"],
                "estado"   => $dados["estadoCliente"],
                "pais"     => $dados["paisCliente"]
            );

            $endereco['cidade'] = utf8_decode($endereco['cidade']);

            if ($debug) {
                echo "a busca da localização será efetuada com os seguintes dados:\n";
                echo "endereço: ".implode(", ", $endereco)."\n";
            }

            if (
                $motivo_erro == "Não foi possível buscar a localização do cliente"
                && !count(array_diff($endereco, $geolocalizacao["endereco"]))
            ) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Não foi possível buscar a localização do cliente\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Não foi possível buscar a localização do cliente";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Não foi possível buscar a localização do cliente");
                continue;
            }

            if (
                (!isset($geolocalizacao["lat"]) || !isset($geolocalizacao["lng"])) 
                && (count(array_diff($endereco, $geolocalizacao["endereco"])) > 0 || !$geolocalizacao["endereco"])
            ) {
                $geolocalizacao = array(
                    "endereco" => $endereco
                );

                $geocoding = $cockpit->geocoding($endereco,null,$con);

                if (array_key_exists("error", $geocoding) || (empty($geocoding["latitude"])) || empty($geocoding["longitude"])) {
                    if ($debug) {
                        echo "### ERRO ###\n";
                        echo "Não foi possível buscar a localização do cliente\n";
                        flushLog();
                        $arq_email[$i]['erros'][] = "Não foi possível buscar a localização do cliente";
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "Não foi possível buscar a localização do cliente");
                    continue;
                }

                $geolocalizacao["lat"] = $geocoding["latitude"];
                $geolocalizacao["lng"] = $geocoding["longitude"];

                $location = $geolocalizacao;
                $cep = preg_replace("/\D/", "", $dados["cepCliente"]);

                $cockpit->gravaGeolocalizacao($json["hd_chamado_cockpit"], $geolocalizacao);
            } else {
                if ($debug) {
                    echo "localização encontrada\n";
                    flushLog();
                }

                $location = $geolocalizacao;
                $cep = preg_replace("/\D/", "", $dados["cepCliente"]);
            }


            /**
             * Buscar Técnico
             */
            if ($debug) {
                echo "buscando técnico...\n";
            }

            $tecnico = $cockpit->getTecnicoMaisProximo(
                $location["lat"],
                $location["lng"],
                $cep,
                0,
                $produto,
                $json["hd_chamado_cockpit_prioridade"],
                $dados["tipoOrdem"],
                $dados["garantia"],
                $dados["idCliente"],
                $dados["centroDistribuidor"],
                $dados["dataAbertura"]
            );


            if (!$tecnico) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Não foi possível definir um técnico para o atendimento\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Não foi possível definir um técnico para o atendimento";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Não foi possível definir um técnico para o atendimento");
                continue;
            }

            $distancia = 0;

            $geocode_distancia = $cockpit->getTecnicoDistance(
                $location["lat"],
                $location["lng"],
                "{$tecnico["latitude"]},{$tecnico["longitude"]}"
            );

            if (isset($geocode_distancia["error"])) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Não foi possível definir um técnico para o atendimento\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Não foi possível definir um técnico para o atendimento";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Não foi possível definir um técnico para o atendimento");
                continue;
            }

            $distancia = $geocode_distancia["total_km"];

            if (!$tecnico['tecnico_proprio']) {
                $geocode_distancia = $cockpit->getTecnicoDistance(
                    $tecnico["latitude"],
                    $tecnico["longitude"],
                    "{$location["lat"]},{$location["lng"]}"
                );

                if (isset($geocode_distancia["error"])) {
                    if ($debug) {
                        echo "### ERRO ###\n";
                        echo "Não foi possível definir um técnico para o atendimento\n";
                        flushLog();
                        $arq_email[$i]['erros'][] = "Não foi possível definir um técnico para o atendimento";
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "Não foi possível definir um técnico para o atendimento");
                    continue;
                }

                $distancia += $geocode_distancia["total_km"];
            }

            $km = $distancia;

            if (!is_numeric($km)) {
                $km = 0;
            }

            $json["km"] = $km;

            if ($debug) {
                echo "técnico encontrado {$tecnico['nome']}\n";
                flushLog();
            }

            /**
             * Abrir Protocolo
             */
            if ($debug) {
                echo "abrindo atendimento...\n";
            }

            $hd = $cockpit->abreHD($json["hd_chamado_cockpit"], $dados);

            if (!array_key_exists("hd_chamado", $hd) || empty($hd["hd_chamado"])) {
                if (array_key_exists("message", $hd)) {
                    $erro = utf8_decode($hd["message"]);
                } else {
                    $erro = json_encode($hd);
                }

                if ($debug) {
                    echo "### ERRO ###\n";
                    echo $erro."\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = $erro;
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], $erro);
                continue;
            }

            $cockpit->setPostoHD($hd["hd_chamado"], $tecnico["posto"]);

            if ($debug) {
                echo "atendimento aberto {$hd["hd_chamado"]}\n";
                flushLog();
            }
            
            /**
             * Abrir Ordem de Serviço
             */
            if ($debug) {
                echo "abrindo ordem de serviço...\n";
            }

            $os = $cockpit->abreOS($hd["hd_chamado"], $dados["patrimonioKof"], $dados["osKof"], $location["lat"], $location["lng"], $routine_schedule_log_id);

            if (array_key_exists("error", $os)) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo utf8_decode($os["error"])."\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = utf8_decode($os["error"]);
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], $os["error"]);
                continue;
            } else if (!array_key_exists("os", $os) || empty($os["os"])) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Erro ao abrir a Ordem de Serviço\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Erro ao abrir a Ordem de Serviço";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Erro ao abrir a Ordem de Serviço");
                continue;
            }

            $cockpit->updateHDChamadoOs($hd["hd_chamado"], $os["os"]);

            if ($debug) {
                echo "ordem de serviço aberta {$os["os"]}\n";
                flushLog();
            }
            
            /**
             * Agendar Ordem de Serviço
             */
            if ($debug) {
                echo "agendando ordem de serviço...\n";
            }

            if($empresa == "AMBEV"){
                $periodos = explode("|", $dados['periodo_atendimento'][0]);
                $data_agendamento = buscaProximaDataPorDiaSemana($periodos[0]); 
            }else{
                $data_agendamento = $tecnico["data"];     
            }

            $agendado = $cockpit->insereAgenda($tecnico["tecnico"], $os["os"], $data_agendamento, 0, $con);

            if ($agendado) {
                $cockpit->UpdateOSTecnico($os["os"], $tecnico["tecnico"]);
            } else {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Erro ao agendar a Ordem de Serviço\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Erro ao agendar a Ordem de Serviço";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Erro ao agendar a Ordem de Serviço");
                continue;
            }

            if ($debug) {
                echo "ordem de serviço agendada para {$tecnico["data"]}\n";
                flushLog();
            }

        if($empresa != "AMBEV"){
            /**
             * Exportar para a Persys
             */
            if ($tecnico["tecnico_proprio"] == true) {
                if ($debug) {
                    echo "exportando ordem de serviço para o dispositivo mobile...\n";
                }
                
                $exporta = $cockpit->exportaOs($os["os"]);

                if (empty($exporta)) {
                    if ($debug) {
                        echo "Erro ao enviar OS para o dispositivo mobile\n";

                        flushLog();
                        $arq_email[$i]['erros'][] = "Erro ao enviar OS para o dispositivo mobile";
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "Erro ao enviar OS para o dispositivo mobile");
                } else if ($exporta["error"]) {
                    if ($debug) {
                        echo $exporta["error"]["message"]."\n";

                        flushLog();
                        $arq_email[$i]['erros'][] = $exporta["error"]["message"];
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], $exporta["error"]["message"]);
                } else {
                    if ($debug) {
                        echo "OS exportada com sucesso\n";

                        flushLog();
                    }
                    
                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "");
                }
            } else {
                if ($debug) {
                    echo "a ordem de serviço não será exportado para o dispotivo mobile, pois o técnico selecionado é de um posto terceiro\n";

                    flushLog();
                    $arq_email[$i]['erros'][] = "a ordem de serviço não será exportado para o dispotivo mobile, pois o técnico selecionado é de um posto terceiro";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "");
            }
        }
            $total_tickets_agendados++;
            $processado = true;
        }
    }

    $routineScheduleLog->setTotalRecord($total_tickets);
    $routineScheduleLog->setTotalRecordProcessed($total_tickets_agendados);
    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");

    if ($debug) {
        echo "realizando upload do arquivo de log...\n";
        $routineScheduleLog->setFileName(basename($arquivo_log));

        $tdocs = new TDocs($con, $fabrica);
        $tdocs->setContext("fabrica", "log");
        $tdocs->uploadFileS3($arquivo_log, $routine_schedule_log_id, false);
        echo "upload do arquivo de log finalizado\n";
	    echo "processamento finalizado\n";
        $routineSchedule->enviaEmail($fabrica,$arq_email);
        flushLog();
    }

    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();

    ob_end_clean();
} catch(Exception $e) {
    if ($debug) {
        echo "### ERRO ROTINA PAROU ###";
        echo $e->getMessage()."\n";
        flushLog();

	    echo "realizando upload do arquivo de log...\n";
        $routineScheduleLog->setFileName(basename($arquivo_log));

        $tdocs = new TDocs($con, $fabrica);
        $tdocs->setContext("fabrica", "log");
        $tdocs->uploadFileS3($arquivo_log, $routine_schedule_log_id, false);
	    echo "upload do arquivo de log finalizado\n";
	    echo "processamento finalizado\n";
        $routineSchedule->enviaEmail($fabrica,$arq_email);
        flushLog();
    }

    if (isset($processado) && $processado == false) {
        $cockpit->gravaErro($json["hd_chamado_cockpit"], "Erro ao processar ticket");
    }

    $routineScheduleLog->setStatus(0);

    $routineScheduleLog->setTotalRecord($total_tickets);
    $routineScheduleLog->setTotalRecordProcessed($total_tickets_agendados);

    if (isset($processado) && $processado == false) {
        $routineScheduleLog->setStatusMessage("Erro ao executar a rotina, ticket: {$json['hd_chamado_cockpit']}, erro: ".$e->getMessage());
    } else {
        $routineScheduleLog->setStatusMessage("Erro ao executar a rotina, erro: ".$e->getMessage());
    }

    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();

    ob_end_clean();
}
