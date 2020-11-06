<?php

ini_set("memory_limit","1024M");
date_default_timezone_set("America/Sao_Paulo");
$no_pdo = true;

require __DIR__.'/../../dbconfig.php';
require __DIR__.'/../../includes/dbconnect-inc.php';

use Posvenda\Cockpit;
use Posvenda\Model\Produto;
use Posvenda\Model\Linha;


$debug   = true;
$fabrica = 158;





function retira_acentos($texto){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
    return str_replace( $array1, $array2, $texto );
}



    $cockpit = new Cockpit($fabrica);
      $sql = "
            SELECT
                tbl_hd_chamado_cockpit.hd_chamado_cockpit,
                tbl_hd_chamado_cockpit.dados,
                tbl_hd_chamado_cockpit.hd_chamado_cockpit_prioridade,
                tbl_hd_chamado_cockpit.motivo_erro,
                tbl_hd_chamado_cockpit.geolocalizacao,
                tbl_routine_schedule_log.file_name,
				tbl_hd_chamado_extra.os
            FROM tbl_hd_chamado_cockpit
            JOIN tbl_routine_schedule_log USING(routine_schedule_log)
            JOIN tbl_hd_chamado_cockpit_prioridade USING(hd_chamado_cockpit_prioridade)
join tbl_hd_chamado_extra using(hd_chamado)
left join tbl_tecnico_agenda on tbl_hd_chamado_extra.os = tbl_tecnico_agenda.os
            WHERE tbl_hd_chamado_cockpit.fabrica = 158
         and tbl_hd_chamado_extra.os notnull 
and tbl_tecnico_agenda.os isnull
            ORDER BY tbl_hd_chamado_cockpit.motivo_erro DESC;
        ";
	$res = pg_query($con,$sql);
	$semHD = pg_fetch_all($res);
echo $sql;
    $total_tickets           = count($semHD);
    $total_tickets_agendados = 0;

    foreach ($semHD as $json) {
        $dados = json_decode($json["dados"], true);
		echo $json['os'];
		echo "\n";
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
                }

                if (empty($valido["message"])) {
                    $valido["message"] = "Erro ao validar informações";
                }
				echo 'sfsdf';
                continue;
            }

            if ($debug) {
                echo "informações validadas\n";
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
                }
echo '1';
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
                }

echo '2';
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
                }
echo '3';
                continue;
            }

            if ($debug) {
                echo "auto agendamento habilitado\n";
            }

            /**
             * Buscar Localização do Cliente
             */
            if ($debug) {
                echo "buscando localização do cliente...\n";
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
                }

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
                    }

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
                }

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
                }
echo '4';
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
                    }

echo '5';
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
            }

            /**
             * Abrir Protocolo
             */
            if ($debug) {
                echo "abrindo atendimento...\n";
            }



            
            /**
             * Agendar Ordem de Serviço
             */
            if ($debug) {
                echo "agendando ordem de serviço...\n";
            }
			$os['os'] = $json['os'];
            $agendado = $cockpit->insereAgenda($tecnico["tecnico"], $os["os"], $tecnico["data"], 0, $con);

            if ($agendado) {
                $cockpit->UpdateOSTecnico($os["os"], $tecnico["tecnico"]);
            } else {
                if ($debug) {
                    echo "### ERRO ###\n";
                    echo "Erro ao agendar a Ordem de Serviço\n";
                }

echo '6';
                continue;
            }

            if ($debug) {
                echo "ordem de serviço agendada para {$tecnico["data"]}\n";
            }
            
            /**
             * Exportar para a Persys
             */
        }
    }





