<?php

ini_set("memory_limit","1024M");
date_default_timezone_set("America/Sao_Paulo");

require __DIR__.'/../../dbconfig.php';
require __DIR__.'/../../includes/dbconnect-inc.php';

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

$arr = $routine->SelectRoutine("Abertura de Tickets");
$routine_id = $arr[0]["routine"];

$routineSchedule = new RoutineSchedule();
$routineSchedule->setRoutine($routine_id);
$routineSchedule->setWeekDay(date("w"));

$routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

if (!strlen($routine_schedule_id)) {
    throw new \Exception("Agendamento da rotina n�o encontrado");
}

$routineScheduleLog = new Log();

if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $_serverEnvironment == "production") {
    die("Rotina j� est� em execu��o");
} else {
    $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
    $routineScheduleLog->setDateStart(date("Y-m-d H:i"));

    if (!$routineScheduleLog->Insert()) {
        throw new \Exception("Erro ao gravar log da rotina");
    }

    $routine_schedule_log_id = $routineScheduleLog->SelectId();
    $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);
}

function retira_acentos($texto){
    $array1 = array("�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�" , "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�","�","&","%","$","?","@" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
    return str_replace( $array1, $array2, $texto );
}

$arquivo_log = "/tmp/cockpit-atendimento-os-".date("YmdHi").".txt";
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
    $semHD   = $cockpit->getCockpitSemHD($fabrica);

    $total_tickets           = count($semHD);
    $total_tickets_agendados = 0;
    $i = 0;

    foreach ($semHD as $json) {
        $i++;

        $dados = json_decode($json["dados"], true);

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
                                  "rotina"=>"cockpit-atendimento-os.php",
                                  "erros"=>[]
                                 ];
            }

            $processado = false;

            /**
             * Valida as informa��es do ticket para saber se pode abrir um atendimento
             */
            if ($debug) {
                echo "validando informa��es...\n";
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
                    $valido["message"] = "Erro ao validar informa��es";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], utf8_encode($valido["message"]));
                continue;
            }

            if ($debug) {
                echo "informa��es validadas\n";
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
                    echo "Produto {$dados["modeloKof"]} n�o encontrado\n";
                    echo "\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Produto ".$dados["modeloKof"]." n�o encontrado";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Produto n�o encontrado");
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
                    echo "Linha do Produto {$produto["linha"]} n�o encontrada\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Linha do Produto ".$produto["linha"]." n�o encontrada";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Linha do Produto n�o encontrada");
                continue;
            }

            if ($debug) {
                echo "linha {$produto["linha"]} encontrada\n";
                echo "verificando se o auto agendamento est� habilitado para linha do produto e tipo de atendimento...\n";
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
             * Buscar Localiza��o do Cliente
             */
            if ($debug) {
                echo "buscando localiza��o do cliente...\n";
            }

            $endereco_cliente = explode("  ", $dados["enderecoCliente"]); //esse explode serve para remover n�mero ou complemento que � recebido com uma separa��o de 2 espa�os ap�s o endere�o
            $endereco_cliente = $endereco_cliente[0];

            $endereco            = "{$endereco_cliente}, {$dados['bairroCliente']}, {$dados['cidadeCliente']}, {$dados['estadoCliente']}, {$dados['paisCliente']}";
            $endereco_sem_bairro = "{$endereco_cliente}, {$dados['cidadeCliente']}, {$dados['estadoCliente']}, {$dados['paisCliente']}";
            $componentes         = "postal_code:{$dados['cepCliente']}";

            if ($debug) {
                echo "a busca da localiza��o ser� efetuada com os seguintes dados:\n";
                echo "endere�o: {$endereco}\n";
                echo "endere�o sem o bairro: {$endereco_sem_bairro}\n";
                echo "cep {$dados['cepCliente']}\n";
            }

            if (
                $motivo_erro == "N�o foi poss�vel buscar a localiza��o do cliente"
                && $geolocalizacao["endereco"] == $endereco
                && $geolocalizacao["endereco_sem_bairro"] == $endereco_sem_bairro
                && $geolocalizacao["componentes"] == $componentes
            ) {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "N�o foi poss�vel buscar a localiza��o do cliente\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "N�o foi poss�vel buscar a localiza��o do cliente";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "N�o foi poss�vel buscar a localiza��o do cliente");
                continue;
            }

            if (
                (!isset($geolocalizacao["lat"]) || !isset($geolocalizacao["lng"])) 
                || $geolocalizacao["endereco"] != $endereco
                || $geolocalizacao["endereco_sem_bairro"] != $endereco_sem_bairro
                || $geolocalizacao["componentes"] != $componentes
            ) {
                $geolocalizacao = array(
                    "endereco"            => $endereco,
                    "endereco_sem_bairro" => $endereco_sem_bairro,
                    "componentes"         => $componentes
                );

                $cockpit->gravaGeolocalizacao($json["hd_chamado_cockpit"], $geolocalizacao);

                $geocoding = $cockpit->geocoding($endereco, $componentes,$con);

                if (!array_key_exists("results", $geocoding) || empty($geocoding["results"])) {
                    $geocoding = $cockpit->geocoding($endereco_sem_bairro, $components,$con);

                    if (!array_key_exists("results", $geocoding) || empty($geocoding["results"])) {
                        if ($debug) {
                            echo "### ERRO ###\n";
                            echo "N�o foi poss�vel buscar a localiza��o do cliente\n";
                            flushLog();
                            $arq_email[$i]['erros'][] = "N�o foi poss�vel buscar a localiza��o do cliente";
                        }

                        $cockpit->gravaErro($json["hd_chamado_cockpit"], "N�o foi poss�vel buscar a localiza��o do cliente");
                        continue;
                    }
                }

                if (!array_key_exists("geometry", $geocoding["results"][0])) {
                    if ($debug) {
                        echo "### ERRO ###\n";
                        echo "N�o foi poss�vel buscar a localiza��o do cliente\n";
                        flushLog();
                        $arq_email[$i]['erros'][] = "N�o foi poss�vel buscar a localiza��o do cliente";
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "N�o foi poss�vel buscar a localiza��o do cliente");
                    continue;
                }

                if (!array_key_exists("location", $geocoding["results"][0]["geometry"])) {
                    if ($debug) {
                        echo "### ERRO ###\n";
                        echo "N�o foi poss�vel buscar a localiza��o do cliente\n";
                        flushLog();
                        $arq_email[$i]['erros'][] = "N�o foi poss�vel buscar a localiza��o do cliente";
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "N�o foi poss�vel buscar a localiza��o do cliente");
                    continue;
                }

                $location = $geocoding["results"][0]["geometry"]["location"];

                if (!array_key_exists("lat", $location) or !array_key_exists("lng", $location)) {
                    if ($debug) {
                        echo "### ERRO ###\n";
                        echo "N�o foi poss�vel buscar a localiza��o do cliente\n";
                        flushLog();
                        $arq_email[$i]['erros'][] = "N�o foi poss�vel buscar a localiza��o do cliente";
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "N�o foi poss�vel buscar a localiza��o do cliente");
                    continue;
                }

                $geocode_cidade_nome_completo;
                $geocode_cidade_nome_abreviado;
                $geocode_estado;

                $destino = $geocoding["results"][0]["address_components"];

                foreach ($destino as $key => $value) {
                    if (in_array("administrative_area_level_2", $value["types"])) {
                        $geocode_cidade_nome_completo  = str_replace("'", "", strtoupper(retira_acentos(utf8_decode($value["long_name"]))));
                        $geocode_cidade_nome_abreviado = str_replace("'", "", strtoupper(retira_acentos(utf8_decode($value["short_name"]))));
                    } else if (in_array("administrative_area_level_1", $value["types"])) {
                        $geocode_estado = strtoupper(retira_acentos(utf8_decode($value["short_name"])));
                    }
                }

                echo "localiza��o encontrada, verificando compatibilidade da cidade e estado\n";

                if (!(($geocode_cidade_nome_completo == $dados["cidadeCliente"] || $geocode_cidade_nome_abreviado == $dados["cidadeCliente"]) && $geocode_estado == $dados["estadoCliente"])) {
                    if ($debug) {
                        echo "### ERRO ###\n";
                        echo "Erro ao verificar compatibilidade da cidade e estado\n";
                        echo "Cidade usada como filtro {$dados["cidadeCliente"]}\n";
                        echo "Estado usado como filtro {$dados["estadoCliente"]}\n";
                        echo "Cidade retornada pelo google {$geocode_cidade_nome_completo}\n";
                        echo "Cidade retornada pelo google (abreviada) {$geocode_cidade_nome_abreviado}\n";
                        echo "Estado retornado pelo google {$geocode_estado}\n";
                        flushLog();
                        $arq_email[$i]['erros'][] = "Erro ao verificar compatibilidade da cidade e estado";
                    }

                    $cockpit->gravaErro($json["hd_chamado_cockpit"], "N�o foi poss�vel buscar a localiza��o do cliente");
                    continue;   
                }

                $cep = preg_replace("/\D/", "", $dados["cepCliente"]);

                if ($debug) {
                    echo "cidade e estado compativeis\n";
                    flushLog();
                }

                $geolocalizacao["lat"] = $location["lat"];
                $geolocalizacao["lng"] = $location["lng"];

                $cockpit->gravaGeolocalizacao($json["hd_chamado_cockpit"], $geolocalizacao);
            } else {
                if ($debug) {
                    echo "localiza��o encontrada, verificando compatibilidade da cidade e estado\n";
                    echo "cidade e estado compativeis\n";
                    flushLog();
                }

                $location = $geolocalizacao;

                $cep = preg_replace("/\D/", "", $dados["cepCliente"]);
            }


            /**
             * Buscar T�cnico
             */
            if ($debug) {
                echo "buscando t�cnico...\n";
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
                    echo "N�o foi poss�vel definir um t�cnico para o atendimento\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "N�o foi poss�vel definir um t�cnico para o atendimento";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "N�o foi poss�vel definir um t�cnico para o atendimento");
                continue;
            }

            $distancia = 0;

            $geocode_distancia = $cockpit->getTecnicoDistance(
                $location["lat"],
                $location["lng"],
                array("{$tecnico["latitude"]},{$tecnico["longitude"]}")
            );

            $distancia = $geocode_distancia->rows[0]->elements[0]->distance->value;

            if (!$tecnico['tecnico_proprio']) {
                $geocode_distancia = $cockpit->getTecnicoDistance(
                    $tecnico["latitude"],
                    $tecnico["longitude"],
                    array("{$location["lat"]},{$location["lng"]}")
                );

                $distancia += $geocode_distancia->rows[0]->elements[0]->distance->value;
            }

            $km = $distancia / 1000;

            if (!is_numeric($km)) {
                $km = 0;
            }

            $json["km"] = $km;

            if ($debug) {
                echo "t�cnico encontrado {$tecnico['nome']}\n";
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
             * Abrir Ordem de Servi�o
             */
            if ($debug) {
                echo "abrindo ordem de servi�o...\n";
            }

            $os = $cockpit->abreOS($hd["hd_chamado"], $dados["patrimonioKof"], $dados["osKof"], $location["lat"], $location["lng"]);

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
                    echo "Erro ao abrir a Ordem de Servi�o\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Erro ao abrir a Ordem de Servi�o";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Erro ao abrir a Ordem de Servi�o");
                continue;
            }

            $cockpit->updateHDChamadoOs($hd["hd_chamado"], $os["os"]);

            if ($debug) {
                echo "ordem de servi�o aberta {$os["os"]}\n";
                flushLog();
            }
            
            /**
             * Agendar Ordem de Servi�o
             */
            if ($debug) {
                echo "agendando ordem de servi�o...\n";
            }

            $agendado = $cockpit->insereAgenda($tecnico["tecnico"], $os["os"], $tecnico["data"], 0);

            if ($agendado) {
                $cockpit->UpdateOSTecnico($os["os"], $tecnico["tecnico"]);
            } else {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Erro ao agendar a Ordem de Servi�o\n";
                    flushLog();
                    $arq_email[$i]['erros'][] = "Erro ao agendar a Ordem de Servi�o";
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "Erro ao agendar a Ordem de Servi�o");
                continue;
            }

            if ($debug) {
                echo "ordem de servi�o agendada para {$tecnico["data"]}\n";
                flushLog();
            }
            
            /**
             * Exportar para a Persys
             */
            if ($tecnico["tecnico_proprio"] == true) {
                if ($debug) {
                    echo "exportando ordem de servi�o para o dispositivo mobile...\n";
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
                    echo "a ordem de servi�o n�o ser� exportado para o dispotivo mobile, pois o t�cnico selecionado � de um posto terceiro\n";

                    flushLog();
                }

                $cockpit->gravaErro($json["hd_chamado_cockpit"], "");
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
        flushLog();
        $routineSchedule->enviaEmail($fabrica,$arq_email);
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
        flushLog();
        $routineSchedule->enviaEmail($fabrica,$arq_email);
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
