<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/communicator.class.php';

$mail = new TcComm("smtp@posvenda");
$destinatarios = [
    'felipe.marttos@telecontrol.com.br'
];
$subject = "Erro Sync Roteiro Técnico -  Aplicativo Mobile";
//PRODUCTION
use Posvenda\TcMaps;

$oTcMaps = new TcMaps($fabrica, $con);


/* FUNÇÕES PARA CONSUMIR POSVENDA */

function getUltimoRoteiroPorTecnicoAtivo($tecnico) {

    global $con, $fabrica;

    $sql = "SELECT tbl_roteiro.roteiro 
              FROM tbl_roteiro 
              JOIN tbl_roteiro_tecnico USING(roteiro) 
             WHERE tbl_roteiro.fabrica={$fabrica} 
               AND tbl_roteiro.status_roteiro NOT IN (3,4)
               AND tbl_roteiro_tecnico.tecnico={$tecnico}
          ORDER BY tbl_roteiro.data_termino DESC LIMIT 1";
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return false;
    }
    return pg_fetch_result($res, 0, roteiro);
}

function getGeoCode($dados, $tabela, $id) {
    global $oTcMaps;

    $endereco       = empty($dados['endereco'])  ? "" : trim($dados['endereco']);
    $numero         = empty($dados['numero'])    ? "" : trim($dados['numero']);
    $bairro         = empty($dados['bairro'])    ? "" : trim($dados['bairro']);
    $cidade         = empty($dados['cidade'])    ? "" : trim($dados['cidade']);
    $cep            = empty($dados['cep'])       ? "" : trim($dados['cep']);
    $endereco       = str_replace("/","",$endereco);
    $estado         = empty($dados['estado'])    ? "" : trim($dados['estado']);
    $pais           = empty($dados['pais'])      ? "" : trim($dados['pais']);
    $response       = $oTcMaps->geocode($endereco, $numero, $bairro, $cidade, $estado, $pais, $cep);
    
    if (strlen($response["latitude"]) > 0) {
        if ($tabela == "tbl_cliente") {
            $retorno = atualizaLatLonCliente($response, $id);
            if ($retorno["sucesso"]) {
                return $response;
            }
        } elseif ($tabela == "tbl_posto_fabrica") {
            $retorno = atualizaLatLonPosto($response, $id);
            if ($retorno["sucesso"]) {
                return $response;
            }
        }
    }
    return [];
}

function getTecnico($usuario_id) {
    global $con, $fabrica;

    $sql = "SELECT tecnico, nome, codigo_externo
              FROM tbl_tecnico
             WHERE tbl_tecnico.ativo IS TRUE
               AND tipo_tecnico = 'TF'
               AND fabrica = {$fabrica} 
               AND codigo_externo = '{$usuario_id}'
               ";

    $res = pg_query($con, $sql);
    return pg_fetch_assoc($res);
}

function getRevenda($codigo) {
    global $con, $fabrica;
    if (empty($codigo)) {
        return [];
    }
    $sql = "SELECT revenda as id,
                   cnpj as codigo,
                   tbl_revenda.nome, 
                   '' AS latitude, 
                   '' AS longitude, 
                   tbl_revenda.cnpj,
                   endereco, 
                   numero, 
                   complemento, 
                   bairro, 
                   tbl_revenda.contato AS contato_nome, 
                   tbl_revenda.fone, 
                   tbl_revenda.cep, 
                   tbl_cidade.nome AS cidade, 
                   tbl_cidade.estado, 
                   email
            FROM tbl_revenda
       LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade
           WHERE tbl_revenda.cnpj = '$codigo'";
    $res = pg_query($con, $sql);

    $dadosContato = pg_fetch_assoc($res);
    if (pg_last_error() || empty($dadosContato)) {
        return [];
    }
    if (strlen($dadosContato["latitude"]) == 0 || strlen($dadosContato["longitude"]) == 0) {
        $dadosGeo = getGeoCode($dadosContato, "tbl_cliente", $dadosContato["id"]);
        if (strlen($dadosGeo["latitude"]) > 0) {
            $dadosContato["latitude"]  = $dadosGeo["latitude"];
            $dadosContato["longitude"] = $dadosGeo["longitude"];
        }
    }

    return $dadosContato;

}

function getCliente($codigo) {
    global $con, $fabrica;
    if (empty($codigo)) {
        return [];
    }
    $sql = "SELECT cliente AS id,
                   cpf AS codigo,
                   tbl_cliente.nome, 
                   latitude, 
                   longitude, 
                   endereco, 
                   numero, 
                   complemento, 
                   bairro, 
                   tbl_cliente.fone, 
                   tbl_cliente.nome AS contato_nome, 
                   tbl_cliente.cep, 
                   tbl_cidade.nome AS cidade, 
                   tbl_cidade.estado, 
                   email
              FROM tbl_cliente 
              LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
             WHERE cpf = '".trim($codigo)."' 
               AND fabrica = {$fabrica}";
    $res = pg_query($con, $sql);

    $dadosContato = pg_fetch_assoc($res);
    if (pg_last_error() || empty($dadosContato)) {
        return [];
    }

    if (strlen($dadosContato["latitude"]) == 0 || strlen($dadosContato["longitude"]) == 0) {
        $dadosGeo = getGeoCode($dadosContato, "tbl_cliente", $dadosContato["id"]);
        if (strlen($dadosGeo["latitude"]) > 0) {
            $dadosContato["latitude"]  = $dadosGeo["latitude"];
            $dadosContato["longitude"] = $dadosGeo["longitude"];
        }
    }
    return $dadosContato;

}

function getPosto($codigo) {
    global $con, $fabrica;
    if (empty($codigo)) {
        return [];
    }
    $sql = "SELECT  tbl_posto.posto AS id,
                    tbl_posto.cnpj AS codigo,
                    tbl_posto.nome,
                    tbl_posto_fabrica.latitude,
                    tbl_posto_fabrica.longitude,
                    tbl_posto_fabrica.contato_endereco AS endereco,
                    tbl_posto_fabrica.contato_numero AS numero,
                    tbl_posto_fabrica.contato_complemento AS complemento,
                    tbl_posto_fabrica.contato_bairro AS bairro,
                    tbl_posto_fabrica.contato_cep AS cep,
                    tbl_posto_fabrica.contato_nome AS contato_nome,
                    tbl_posto_fabrica.contato_cel AS fone,
                    tbl_posto_fabrica.contato_cidade AS cidade,
                    tbl_posto_fabrica.contato_estado AS estado,
                    tbl_posto_fabrica.contato_email AS email
            FROM tbl_posto_fabrica
            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
           WHERE tbl_posto.cnpj = '".trim($codigo)."'
             AND tbl_posto_fabrica.fabrica = {$fabrica}";

    $res = pg_query($con, $sql);

    $dadosContato = pg_fetch_assoc($res);
    if (pg_last_error() || empty($dadosContato)) {
        return [];
    }
    if (strlen($dadosContato["latitude"]) == 0 || strlen($dadosContato["longitude"]) == 0) {
        $dadosGeo = getGeoCode($dadosContato, "tbl_cliente", $dadosContato["id"]);
        if (strlen($dadosGeo["latitude"]) > 0) {
            $dadosContato["latitude"]  = $dadosGeo["latitude"];
            $dadosContato["longitude"] = $dadosGeo["longitude"];
        }
    }

    return $dadosContato;

}

function getIDExternal($external_id) {
    list($pref, $id) = explode("_", $external_id);
    return $id;
}

function atualizaStatusCompromissoPV($status_codigo, $external_id) {
    global $con, $fabrica;
    if ($status_codigo <> 'OK') {
      $status_codigo = getRevertStatusVisitaPV($status_codigo);
    }
    $sql = "UPDATE tbl_roteiro_posto SET status='".$status_codigo."' WHERE roteiro_posto = ".getIDExternal($external_id);
    $res = pg_query($con, $sql);
    if (pg_last_error($con)) {
        return false;
    }
    return true;
}


function getStatusCompromissoPV($roteiro_posto) {
    global $con, $fabrica;

    $sql = "SELECT status FROM tbl_roteiro_posto WHERE roteiro_posto = ".$roteiro_posto;
    $res = pg_query($con, $sql);
    if (pg_last_error($con) || pg_num_rows($res) == 0) {
        return [];
    }

    $status = pg_fetch_assoc($res);

     return getStatusVisitaPVSigla($status["status"]);
}


function retira_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
      return str_replace( $array1, $array2, $texto );
}

function atualizaVisitaCompromissoPV($dados_vista, $external_id) {
    global $con, $fabrica;

    $roteiro_posto = getIDExternal($external_id);
    $sqlRV = "SELECT * FROM tbl_roteiro_visita  WHERE roteiro_posto = ".$roteiro_posto;
    $resRV = pg_query($con, $sqlRV);

    $xaux_checkin  = new DateTime($dados_vista["dados_visita"]["check_in"]);
    $xaux_checkout = new DateTime($dados_vista["dados_visita"]["check_out"]);
    $diff = $xaux_checkin->diff($xaux_checkout);
    $tempo_visita = trataTime($diff);

    $descricao = str_replace(["'"], "", $dados_vista["dados_visita"]["descricao"]);
    if (pg_num_rows($resRV) > 0) {

        $sql = "UPDATE tbl_roteiro_visita 
                   SET descricao='".utf8_decode($descricao)."', 
                       tempo_visita='".$tempo_visita."', 
                       checkin='".$dados_vista["dados_visita"]["check_in"]."', 
                       checkout='".$dados_vista["dados_visita"]["check_out"]."' 
                 WHERE roteiro_posto = ".$roteiro_posto;
        $res = pg_query($con, $sql);
        if (pg_last_error($con)) {
            return false;
        }
        return true;

    } else {

        $sql = "INSERT INTO tbl_roteiro_visita (
                                                    tempo_visita, 
                                                    descricao, 
                                                    checkin, 
                                                    checkout, 
                                                    roteiro_posto
                                                ) VALUES (
                                                    '".$tempo_visita."',
                                                    '".utf8_decode($descricao)."', 
                                                    '".$dados_vista["dados_visita"]["check_in"]."', 
                                                    '".$dados_vista["dados_visita"]["check_out"]."', 
                                                    ".$roteiro_posto."
                                                )";



                                                
        $res = pg_query($con, $sql);
        if (pg_last_error()) {
            return false;
        }
        if (!empty($dados_vista["dados_anexos"])) {

            foreach ($dados_vista["dados_anexos"] as $key => $rows) {
                $dadosAnex = json_decode($rows["file"], 1);

                $dadosOBS["acao"]     = "anexar";
                $dadosOBS["filename"] = $dadosAnex["name"];
                $dadosOBS["filesize"] = $dadosAnex["size"];
                $dadosOBS["data"]     = date("Y-m-d H:i:s");
                $dadosOBS["fabrica"]  = $fabrica;

                $sql = "INSERT INTO tbl_tdocs (
                                                fabrica, 
                                                obs, 
                                                tdocs_id, 
                                                contexto, 
                                                situacao, 
                                                referencia, 
                                                referencia_id
                                            ) VALUES (
                                                ".$fabrica.",
                                                '".json_encode([$dadosOBS])."', 
                                                '".$rows["tdocs_id"]."', 
                                                'roteiro', 
                                                'ativo', 
                                                'roteiro', 
                                                '".$roteiro_posto."'
                                            )";
                $res = pg_query($con, $sql); 
            }

        }
        return true;
    }

}

function atualizaLatLonCliente($response, $id) {
    global $con, $fabrica;

    $sql = "UPDATE tbl_cliente
               SET latitude='".$response["latitude"]."', 
                   longitude='".$response["longitude"]."'
             WHERE cliente = {$id}
               AND fabrica = {$fabrica}";

    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        return ["sucesso" => false];
    }
    return ["sucesso" => true];
}

function atualizaLatLonPosto($response, $id) {
    global $con, $fabrica;

    $sql = "UPDATE tbl_posto_fabrica 
               SET latitude='".$response["latitude"]."', 
                   longitude='".$response["longitude"]."'
             WHERE posto = {$id}
               AND fabrica = {$fabrica}";

    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        return ["sucesso" => false];
    }
    return ["sucesso" => true];
}

function insereCompromissoPV($dados) {
    global $con, $fabrica;

    $tipoContato = checaContato($dados["dados_compromisso"]);
    $tecnico     = getTecnico($dados["dados_compromisso"]["usuario_id"]);
    $roteiro     = getUltimoRoteiroPorTecnicoAtivo($tecnico["tecnico"]);


    if (!$roteiro) {
        return false;
    }

    if (!$tipoContato) {
        return false;
    }
    $contato_documento = str_replace([".","-","/"," "], "", $dados["dados_compromisso"]["contato_documento"]);

    $sql = "INSERT INTO tbl_roteiro_posto ( roteiro,
                                            tipo_de_visita,
                                            tipo_de_local,
                                            codigo,
                                            status,
                                            data_visita                                           
                                           ) VALUES (
                                            '".$roteiro."',
                                            '".$dados["dados_compromisso"]["tipo_visita_codigo"]."',
                                            '".$tipoContato."',
                                            '".$contato_documento."',
                                            '".getRevertStatusVisitaPV($dados["dados_compromisso"]["status_visita_codigo"])."',
                                            '".geraDataBD($dados["dados_compromisso"]["data_visita"])."'
                                        ) RETURNING roteiro_posto;";
    $res = pg_query($con, $sql);




    $roteiro_posto = pg_fetch_result($res, 0, 0);
    if (!$roteiro_posto) {
        return false;
    } 

    if (isset($dados["dados_visita"]) && !empty($dados["dados_visita"])) {
      $xaux_checkin  = new DateTime($dados["dados_visita"]["check_in"]);
      $xaux_checkout = new DateTime($dados["dados_visita"]["check_out"]);
      $diff = $xaux_checkin->diff($xaux_checkout);
      $tempo_visita = trataTime($diff);
      $descricao = str_replace(["'"], "", $dados["dados_visita"]["descricao"]);

        $sql = "INSERT INTO tbl_roteiro_visita ( roteiro_posto,
                                                descricao,
                                                checkin,
                                                checkout,
                                                tempo_visita                                         
                                               ) VALUES (
                                                '".$roteiro_posto."',
                                                '".$descricao."',
                                                '".$dados["dados_visita"]["check_in"]."',
                                                '".$dados["dados_visita"]["check_out"]."',
                                                '".$tempo_visita."'
                                            )";

        $res = pg_query($con, $sql);
        if (pg_last_error()) {

            return false;
        } 
        if (!empty($dados["dados_anexos"])) {

            foreach ($dados["dados_anexos"] as $key => $rows) {
                $dadosAnex = json_decode($rows["file"], 1);

                $dadosOBS["acao"]     = "anexar";
                $dadosOBS["filename"] = $dadosAnex["name"];
                $dadosOBS["filesize"] = $dadosAnex["size"];
                $dadosOBS["data"]     = date("Y-m-d H:i:s");
                $dadosOBS["fabrica"]  = $fabrica;

                $sql = "INSERT INTO tbl_tdocs (
                                                fabrica, 
                                                obs, 
                                                tdocs_id, 
                                                contexto, 
                                                situacao, 
                                                referencia, 
                                                referencia_id
                                            ) VALUES (
                                                ".$fabrica.",
                                                '".json_encode([$dadosOBS])."', 
                                                '".$rows["tdocs_id"]."', 
                                                'roteiro', 
                                                'ativo', 
                                                'roteiro', 
                                                '".$roteiro_posto."'
                                            )";
                $res = pg_query($con, $sql); 
            }

        }
    }

    return ["id" => $dados["dados_compromisso"]["id"], "external_id" => "PV_".$roteiro_posto];
}

function checaContato($dados) {

    global $con, $fabrica;

    $contato_documento = str_replace([".","-","/"," "], "", $dados["contato_documento"]);
    if (strlen($contato_documento) == 14) {
        if (!empty(getRevenda($contato_documento))) {
        return "RV";
        } elseif (!empty(getCliente($contato_documento))) {
            return "CL";
        } else {
            if (insertCliente($dados)) {
                return "CL";
            } else {
                return false;
            }

        }    
    } else {

        if (!empty(getPosto($contato_documento))) {
            return "PA";
        } elseif (!empty(getCliente($contato_documento))) {
            return "CL";
        } else {

            if (insertCliente($dados)) {
                return "CL";
            } else {
                return false;
            }

        }
    }
}

function getMediaOs($posto, $revenda = null) {

    global $fabrica, $con;
    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));

    $sql = "SELECT (COUNT(os)/6)  AS media_os
              FROM tbl_os
             WHERE fabrica = {$fabrica} 
               AND status_checkpoint = 9
               AND posto = {$posto}
               AND data_digitacao::date BETWEEN '$data_seis_ant' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_os"];

}

function getMediaCompra($posto) {

    global $fabrica, $con;
    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));

    $sql = "SELECT (sum(total)/6) AS media_compra
             FROM tbl_pedido
            WHERE fabrica = {$fabrica} 
              AND status_pedido IN(4,5)
              AND posto = {$posto}
              AND data::date BETWEEN '$data_seis_ant' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return number_format($retorno["media_compra"], 2, ',', '.');

}

function getMediaTreinamento($posto) {

    global $fabrica, $con;
    $data_hoje     = date('Y-m-d');
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months'));
    $sql = "SELECT COUNT(tbl_treinamento_posto.posto) AS media_treinamento
             FROM tbl_treinamento_posto 
             JOIN tbl_treinamento USING(treinamento) 
            WHERE tbl_treinamento.fabrica = {$fabrica} 
              AND tbl_treinamento_posto.posto = {$posto}
              AND tbl_treinamento_posto.data_inscricao::date BETWEEN '$data_vinte_quatro_seis' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_treinamento"];
}

function getUltimasVisitas($codigo) {
    global $fabrica, $con;

    $sql = "SELECT tbl_roteiro_posto.roteiro_posto,tbl_roteiro_posto.data_visita, tbl_roteiro_posto.tipo_de_visita, tbl_roteiro_tecnico.tecnico
                 FROM tbl_roteiro
                 JOIN tbl_roteiro_posto ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro
                 JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto = tbl_roteiro_posto.roteiro_posto
                 JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro_posto.roteiro
                WHERE tbl_roteiro.fabrica = {$fabrica} 
                  AND tbl_roteiro_posto.codigo = '{$codigo}'
                  LIMIT 5
                ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return pg_fetch_all($res);
    }
    return array();
}

function getDescontoOPE($cnpj) {
    global $con;
    $sql = "SELECT * FROM makita_sa1_cliente WHERE a1_cgc = '$cnpj';";
    $res = pg_query($con, $sql);


    $cliente_despec_ele  = pg_fetch_result($res, 0, 'a1_despec');
    $cliente_despec_lav  = pg_fetch_result($res, 0, 'a1_despec1');
    $cliente_despec_ope  = pg_fetch_result($res, 0, 'a1_despec2');
    if (strlen($cliente_despec_ele) == 0) {
        $cliente_despec_ele = 0 ;
    } 
    if (strlen($cliente_despec_lav) == 0) {
        $cliente_despec_lav = 0 ;
    } 
    if (strlen($cliente_despec_ope) == 0) {
        $cliente_despec_ope = 0 ;
    } 
    $cliente_despec_ele = str_replace (',','.',$cliente_despec_ele);
    $cliente_despec_lav = str_replace (',','.',$cliente_despec_lav);
    $cliente_despec_ope = str_replace (',','.',$cliente_despec_ope);


    return ["desconto_lavadoras" => $cliente_despec_lav, "desconto_eletrica" => $cliente_despec_ele, "desconto_ope" => $cliente_despec_ope];
}

if ($fabrica == 42) {
    function getTipoVisitaPV($sigla = '') {
        $retorno = [
                    "VT" => "Visita Tecnica",
                    "VC" => "Visita Comercial",
                    "VA" => "Visita Administrativa",
                    "CM" => "Clinica Makita",
                    "FE" => "Feira/Evento",
                    "TN" => "Treinamento"
        ];
        if (strlen($sigla) > 0) {
            return $retorno[$sigla];
        } else {
            return $retorno;
        }
    }
    
} else {
    function getTipoVisitaPV($sigla = '') {
        $retorno = [
                    "VT" => "Visita Técnica",
                    "VC" => "Visita Comercial",
                    "VA" => "Visita Administrativa",
        ];
        if (strlen($sigla) > 0) {
            return $retorno[$sigla];
        } else {
            return $retorno;
        }
    }

}

function insertCliente($dados) {
    global $con, $fabrica;
    if (empty($dados["contato_documento"])) {
        return false;
    }

    //$dados_enredeco = 
    list($endereco, $dadosEx) = explode(",", $dados["endereco"]);
    list($numero, $complemento, $bairro, $cidade_uf) = explode("-", $dadosEx);

    list($cidade, $uf) = explode("/", $cidade_uf);

    $dados_cidade = getCidade($cidade);
    $contato_documento = str_replace([".","-","/"," "], "", $dados["contato_documento"]);

    $sql = "INSERT INTO tbl_cliente ( 
                                        nome,
                                        email,
                                        fone,
                                        cpf,
                                        latitude,
                                        longitude,
                                        endereco,
                                        numero,
                                        complemento,
                                        bairro,
                                        cidade,
                                        estado,
                                        fabrica                                           
                                    ) VALUES (
                                        '".trim($dados["contato_nome"])."',
                                        '".trim($dados["contato_email"])."',
                                        '".trim($dados["contato_telefone"])."',
                                        '".trim($contato_documento)."',
                                        '".trim($dados["latitude"])."',
                                        '".trim($dados["longitude"])."',
                                        '".trim($endereco)."',
                                        '".trim($numero)."',
                                        '".trim($complemento)."',
                                        '".trim($bairro)."',
                                        '".trim($dados_cidade["cidade"])."',
                                        '".trim($dados_cidade["estado"])."',
                                        '".trim($fabrica)."'
                                    )";

    $res = pg_query($con, $sql);

    if (pg_last_error()) {
        return false;
    } 
    return true;
}

function getCidade($cidade) {

    global $con, $fabrica;

    if (empty($cidade)) {
        return false;
    }

    $sql = "SELECT * FROM tbl_cidade 
                    WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('".trim(utf8_decode($cidade))."'))";
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        return false;
    } 
    return pg_fetch_assoc($res);

}

function getStatusVisitaPV() {
    $retorno["codigo"] = [
                "AC" => "CONFIRMADO", 
                "CF" => "ANDAMENTO", 
                "OK" => "FINALIZADO", 
                "CC" => "CANCELADO",
    ];
    $retorno["descricao"] = [
                "AC" => "Confirmado", 
                "CF" => "Em Andamento", 
                "OK" => "Finalizado", 
                "CC" => "Cancelado",
    ];
   
    return $retorno;
}

function getRevertStatusVisitaPV($status) {
    $retorno = [
                "CONFIRMADO" => "AC",
                "ANDAMENTO" => "CF",  
                "FINALIZADO" => "OK",
                "CANCELADO" => "CC", 
    ];
 
   
    return $retorno[$status];
}

function getStatusVisitaPVSigla($status) {
    $retorno = [
                "AC" => "CONFIRMADO", 
                "CF" => "ANDAMENTO", 
                "OK" => "FINALIZADO", 
                "CC" => "CANCELADO",
    ];
 
   
    return $retorno[$status];
}

function getTipoVisitaAPI($sigla, $dadosTipoVisita) {
   
    foreach ($dadosTipoVisita as $key => $rows) {
        if ($rows["codigo"] == $sigla) {
            return $rows["id"];
        }
    }

} 

function geraDataNormal($data) {
    $vetor = explode('-', $data);
    $dataTratada = $vetor[2] . '/' . $vetor[1] . '/' . $vetor[0];
    return $dataTratada;
}

function geraDataBD($data) {
    $vetor = explode('/', $data);
    $dataTratada = $vetor[2] . '-' . $vetor[1] . '-' . $vetor[0];
    return $dataTratada;
}

function trataTime($diff) {
    if (strlen($diff->h) == 1) {
        $hora = "0".$diff->h;
    } else {
        $hora = $diff->h;
    }
    if (strlen($diff->i) == 1) {
        $min = "0".$diff->i;
    } else {
        $min = $diff->i;
    }
    if (strlen($diff->s) == 1) {
        $seg = "0".$diff->s;
    } else {
        $seg = $diff->s;
    }

    if ($diff->d > 0) {
        $hora = ($diff->d*24);
    }
    return $hora.":".$min.":".$seg;

}
/* FUNÇÕES PARA CONSUMIR API */

function get($endpoint, $debug = false) {
    global $url_api;
    $curl = curl_init();



    curl_setopt_array($curl, array(
      CURLOPT_URL => $url_api . "/" . $endpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
                                //"access-application-key: daab51216c0d8c644f27bc233a960bf4367ce746",
                                "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
                                "access-env: PRODUCTION",
                                "cache-control: no-cache",
                                "content-type: application/json",
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($debug) {
      echo "<pre><<----".print_r($err,1)."</pre>";
      echo "<pre><<----".print_r($response,1)."</pre>";
    }

    if ($err) {
        return ["erro" => $err];
    } else {
        return json_decode($response, 1);
    }
}

function post($endpoint, $dados = [], $debug = '') {
    global $url_api;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url_api . "/" . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => array(
                                    //"access-application-key: daab51216c0d8c644f27bc233a960bf4367ce746",
                                    "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
    "access-env: PRODUCTION",
    "cache-control: no-cache",
    "content-type: application/json",
    "postman-token: a8e02a33-41f9-69da-7a6a-4a256b860751"
                            ),



    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    if ($debug) {
        echo "<pre>response ".print_r($response, 1)."</pre>";exit;
        echo "<pre>err ".print_r( $err, 1)."</pre>";exit;
    }
    curl_close($curl);
    if ($err) {
        return ["erro" => $err];
    } else {
        //return $response;
        return json_decode($response, 1);
    }
}

function put($endpoint, $dados = []) {
    global $url_api;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url_api . "/" . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => array(
                                    //"access-application-key: daab51216c0d8c644f27bc233a960bf4367ce746",
                                    "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
    "access-env: PRODUCTION",
    "cache-control: no-cache",
    "content-type: application/json",
    "postman-token: a8e02a33-41f9-69da-7a6a-4a256b860751"
                                    ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return ["erro" => $err];
    } else {
        return json_decode($response, 1);
    }

}

$url_api                  = "http://api2.telecontrol.com.br/checkin";
$api_port                 = "8787";
$fabrica                  = 42;
$nomeFabrica              = "Makita";
$log                      = [];
$dadosCompAPI             = [];
$condBusca                = "";
$i                        = 0;

/* VERIFICO SE EXISTE COMPANIA, CASO NAO TEM , CRIAMOS */
$dadosCompania = current(get("compania/codigo/" . $fabrica));
if (empty($dadosCompania )) {
    //INSERE COMPANIA
    $dadosSave = [
        "nome" => $nomeFabrica,
        "codigo" => $fabrica,
        "criado_em" => date("Y-m-d H:i:s") 
    ];
    $dadosCompania = post("compania/", $dadosSave);
    if (isset($dadosCompania["exception"]) || !isset($dadosCompania["id"])) {

        $log["erro"]["insercao_compania_na_api"][] = ["dados" => $dadosSave, "external_id" => ""];

    }
} 

/* VERIFICO SE EXISTE TIPO DE VISITA, CASO NAO TEM , CRIAMOS */
$dadosTipoVisita = get("tipoVisita/companiaId/" . $dadosCompania["id"]);
//$dadosTipoVisita = get("tipo-visita/companiaId/" . $dadosCompania["id"]);
if (empty($dadosTipoVisita)) {
    //INSERE TIPO DE VISITA
    $tipos = getTipoVisitaPV();
    foreach ($tipos as $codigo => $tipo) {
        $dadosSave = [
            "tipo" => $tipo,
            "codigo" => $codigo,
            "compania_id" => $dadosCompania["id"],
            "criado_em" => date("Y-m-d H:i:s") 
        ];
        $dadosTipoVisita = post("tipoVisita/", $dadosSave);
        //$dadosTipoVisita = post("tipo-visita/", $dadosSave);
        if (isset($dadosTipoVisita["exception"]) || !isset($dadosTipoVisita["id"])) {

            $log["erro"]["insercao_tipo_visita_na_api"][] = ["dados" => $dadosSave, "external_id" => ""];

        }
    }

} 
$dadosTipoVisita = get("tipoVisita/companiaId/" . $dadosCompania["id"]);
//$dadosTipoVisita = get("tipo-visita/companiaId/" . $dadosCompania["id"]);
/* VERIFICO SE EXISTE STATUS DE VISITA, CASO NAO TEM , CRIAMOS */
$dadosStatusVisita = get("statusVisita/companiaId/" . $dadosCompania["id"]);
//$dadosStatusVisita = get("status-visita/companiaId/" . $dadosCompania["id"]);
if (empty($dadosStatusVisita)) {
    //INSERE STATUS DE VISITA
    $status = getStatusVisitaPV();
    foreach ($status["codigo"] as $sigla => $codigo) {
        $dadosSave = [
            "status" => $status["descricao"][$sigla],
            "codigo" => $codigo,
            "compania_id" => $dadosCompania["id"],
            "criado_em" => date("Y-m-d H:i:s") 
        ];
        $dadosStatusVisita = post("statusVisita/", $dadosSave);
        //$dadosStatusVisita = post("status-visita/", $dadosSave);
        if (isset($dadosStatusVisita["exception"]) || !isset($dadosStatusVisita["id"])) {

            $log["erro"]["insercao_status_visita_na_api"][] = ["dados" => $dadosSave, "external_id" => ""];

        }
    }

} 
$dadosStatusVisita = get("statusVisita/companiaId/" . $dadosCompania["id"]);
//$dadosStatusVisita = get("status-visita/companiaId/" . $dadosCompania["id"]);

/* VERIFICO SE EXISTE AGENDAS, CASO SIM ATUALIZA STATUS E VISITAS, SE NAO JOGA NA VARIAVEL PARA INSERÇÃO NO POSVENDA */
$dadosCompromissos = get("compromisso/companiaId/" . $dadosCompania["id"]);

if (count($dadosCompromissos) > 0) {

    foreach ($dadosCompromissos as $key => $rows) {
        if (strlen($rows["dados_compromisso"]["external_id"]) > 0) {

            $id_pv    = getIDExternal($rows["dados_compromisso"]["external_id"]);
            $statusPV = getStatusCompromissoPV($id_pv);

            if ($statusPV == "CANCELADO") {
              continue;
            }

            $atualizaStatusCompromisso = atualizaStatusCompromissoPV($rows["dados_compromisso"]["status_visita_codigo"], $rows["dados_compromisso"]["external_id"]);

            if (!$atualizaStatusCompromisso) {
                $log["erro"]["atualizacao_status_compromisso"][] = ["dados" => $rows["dados_compromisso"]["status_visita_codigo"], "external_id" => $rows["dados_compromisso"]["external_id"]];
            }

            if (isset($rows["dados_visita"]) && !empty($rows["dados_visita"])) {
                $atualizaStatusCompromisso = atualizaStatusCompromissoPV('OK', $rows["dados_compromisso"]["external_id"]);
                $atualizaVisitaCompromisso = atualizaVisitaCompromissoPV($rows, $rows["dados_compromisso"]["external_id"]);
                if (!$atualizaVisitaCompromisso) {
                    $log["erro"]["atualizacao_vista_compromisso"][] = ["dados" => $rows["dados_visita"], "external_id" => $rows["dados_compromisso"]["external_id"]];
                }

            }
            $compromissos_ids[] = getIDExternal($rows["dados_compromisso"]["external_id"]);

        } else {
            $dadosCompromissosAvulsos[] = $rows;
        }

    }

}
/* BUSCO COMPROMISSOS NO POSVENDA QUE NÃO ESTÃO NA API */
if (count($compromissos_ids) > 0) {
    $condBusca = " AND tbl_roteiro_posto.roteiro_posto NOT IN(".implode(",", $compromissos_ids).")";
}

$sqlCompromissos = "SELECT tbl_roteiro.solicitante,
                           tbl_roteiro.excecoes,
                           tbl_roteiro_posto.roteiro_posto,
                           tbl_roteiro_posto.tipo_de_visita,
                           tbl_roteiro_posto.qtde_horas,
                           tbl_roteiro_posto.data_visita,
                           tbl_roteiro_posto.tipo_de_local,
                           tbl_roteiro_posto.codigo,
                           tbl_roteiro_tecnico.tecnico,
         tbl_roteiro_posto.status,
         tbl_tecnico.codigo_externo
                      FROM tbl_roteiro_posto 
                      JOIN tbl_roteiro USING(roteiro)
          JOIN tbl_roteiro_tecnico USING(roteiro)
          JOIN tbl_tecnico USING(tecnico)
                     WHERE tbl_roteiro.fabrica={$fabrica}
                       AND tbl_roteiro_posto.status NOT IN('CC', 'OK')
                       {$condBusca}";
$resCompromissos = pg_query($con, $sqlCompromissos);
$retorno = [];

while ($rows = pg_fetch_assoc($resCompromissos)) {
  if(empty($rows["codigo_externo"])) continue;

    $id_tipo_visita = getTipoVisitaAPI($rows["tipo_de_visita"], $dadosTipoVisita);


    if (empty($id_tipo_visita) || empty($rows["data_visita"])) {
        continue;
    }
    $dados_extras = [];

    if ($rows["tipo_de_local"] == "CL") {

        $dadosContato = getCliente($rows["codigo"]);
        if (empty($dadosContato)) {
            $log["erro"]["busca_contato_pv_compromisso"][] = ["dados" => $rows["codigo"], "external_id" => "", "motivo" => "Contato não encontrado"];
            continue;
        }

        $buscaContatoAPI = current(get("contato/companiaId/".$dadosCompania["id"]."/externalId/CL_".$dadosContato["id"]));

        if (empty($buscaContatoAPI) || (retira_acentos(utf8_decode($buscaContatoAPI)) == "Contato nao encontrado")) {
            $dadosContatoSave = [
                "nome" => $dadosContato["nome"],
                "documento" => $dadosContato["codigo"],
                "email" => $dadosContato["email"],
                "telefone" => $dadosContato["fone"],
                "external_id" => "CL_".$dadosContato["id"],
                "compania_id" => $dadosCompania["id"],
                "criado_em" => date("Y-m-d H:i:s"),
            ];
            $insereContatoAPI =  post("contato/", $dadosContatoSave);
            $buscaContatoAPI = $insereContatoAPI;

        }
        $dados_extras["ultimas_visitas"]   = getUltimasVisitas($dadosContato["codigo"]);
    } elseif ($rows["tipo_de_local"] == "PA") {

        $dadosContato = getPosto($rows["codigo"]);

        if (empty($dadosContato)) {
            $log["erro"]["busca_contato_pv_compromisso"][] = ["dados" => $rows["codigo"], "external_id" => "", "motivo" => "Contato não encontrado"];
            continue;
        }
        $buscaContatoAPI =  current(get("contato/companiaId/".$dadosCompania["id"]."/externalId/PA_".$dadosContato["id"]));

        if (empty($buscaContatoAPI) || (retira_acentos(utf8_decode($buscaContatoAPI)) == "Contato nao encontrado")) {
            $dadosEstabSave = [
                "nome" => $dadosContato["nome"],
                "documento" => $dadosContato["codigo"],
                "external_id" => "PA_".$dadosContato["id"],
                "criado_em" => date("Y-m-d H:i:s"),
            ];
            $insereEstabelecimentoAPI =  post("estabelecimento/", $dadosEstabSave);
            $dadosContatoSave = [
                "nome" => $dadosContato["nome"],
                "email" => $dadosContato["email"],
                "telefone" => $dadosContato["fone"],
                "documento" => $dadosContato["codigo"],
                "external_id" => "PA_".$dadosContato["id"],
                "estabelecimento_id" => $insereEstabelecimentoAPI["id"],
                "compania_id" => $dadosCompania["id"],
                "criado_em" => date("Y-m-d H:i:s"),
            ];

            $insereContatoAPI =  post("contato/", $dadosContatoSave);

            $buscaContatoAPI = $insereContatoAPI;
        }

        $dados_extras["media_os"]          = getMediaOs($dadosContato["id"]);
        $dados_extras["media_treinamento"] = getMediaTreinamento($dadosContato["id"]);
        $dados_extras["media_compra"]      = getMediaCompra($dadosContato["id"]);
        $dados_extras["ultimas_visitas"]   = getUltimasVisitas($dadosContato["id"]);
        
        $xdescontos = getDescontoOPE($dadosContato["id"]);
        $dados_extras["desconto_eletrica"]  = $xdescontos["desconto_eletrica"];
        $dados_extras["desconto_ope"]       = $xdescontos["desconto_ope"];
        $dados_extras["desconto_lavadoras"] = $xdescontos["desconto_lavadoras"];

    } elseif ($rows["tipo_de_local"] == "RV") {

        $dadosContato = getRevenda($rows["codigo"]);
        $buscaContatoAPI =  current(get("contato/companiaId/".$dadosCompania["id"]."/external_id/RV_".$dadosContato["id"]));

        if (empty($dadosContato)) {
            $log["erro"]["busca_contato_pv_compromisso"][] = ["dados" => $rows["codigo"], "external_id" => "", "motivo" => "Contato não encontrado"];
            continue;
        }

        if (empty($buscaContatoAPI) || (retira_acentos(utf8_decode($buscaContatoAPI)) == "Contato nao encontrado")) {
            $dadosEstabSave = [
                "nome" => $dadosContato["nome"],
                "documento" => $dadosContato["codigo"],
                "external_id" => "RV_".$dadosContato["id"],
                "criado_em" => date("Y-m-d H:i:s"),
            ];

            $insereEstabelecimentoAPI =  post("estabelecimento/", $dadosEstabSave);

            $dadosContatoSave = [
                "nome" => $dadosContato["nome"],
                "email" => $dadosContato["email"],
                "telefone" => $dadosContato["fone"],
                "documento" => $dadosContato["codigo"],
                "external_id" => "RV_".$dadosContato["id"],
                "estabelecimento_id" => $insereEstabelecimentoAPI["id"],
                "compania_id" => $dadosCompania["id"],
                "criado_em" => date("Y-m-d H:i:s"),
            ];
            $insereContatoAPI =  post("contato/", $dadosContatoSave);
            $buscaContatoAPI = $insereContatoAPI;
        }
        $dados_extras["ultimas_visitas"]   = getUltimasVisitas($dadosContato["codigo"]);
        
    }

    if (isset($buscaContatoAPI["exception"]) && strlen($buscaContatoAPI["exception"]) > 0) {
      continue;
    }

    $externaIDCompromisso = "PV_".$rows["roteiro_posto"];
    $statusDepara = getStatusVisitaPV();
    $retorno[$i]["dados_adicionais"]   = json_encode($dados_extras);
    $retorno[$i]["tipovisita_id"]   = $id_tipo_visita;
    $retorno[$i]["status"]          = $statusDepara["codigo"][$rows["status"]];
    $retorno[$i]["solicitante"]     = utf8_encode($rows["solicitante"]);
    $retorno[$i]["data_visita"]     = $rows["data_visita"];
    $retorno[$i]["observacao"]      = utf8_encode(trim($rows["excecoes"]));
    $retorno[$i]["tempo_visita"]    = $rows["qtde_horas"];
    $retorno[$i]["endereco"]        = utf8_encode(retira_acentos($dadosContato["endereco"]).', '. $dadosContato["numero"].' - '. $dadosContato["complemento"].' - '. $dadosContato["bairro"].' - '. $dadosContato["cidade"].'/'. $dadosContato["estado"]);
    $retorno[$i]["latitude"]        = $dadosContato["latitude"];
    $retorno[$i]["longitude"]       = $dadosContato["longitude"];
    $retorno[$i]["compania_id"]     = $dadosCompania["id"];
    $retorno[$i]["contato_id"]      = $buscaContatoAPI["id"];
    $retorno[$i]["external_id"]     = $externaIDCompromisso;
    $retorno[$i]["usuario_id"]      = $rows["codigo_externo"];
    $i++;
}



/* INSERE COMPROMISSO NA API */
if (!empty($retorno)) {

    foreach ($retorno as $key => $dados) {
        $insertCompromissoAPI = [];
        $insertCompromissoAPI = post("compromisso", $dados);
        if (isset($insertCompromissoAPI["exception"]) || !isset($insertCompromissoAPI["id"])) {

            $log["erro"]["insercao_compromisso_na_api"][] = ["dados" => $dados,"motivo" => $insertCompromissoAPI["exception"], "external_id" => $dados["external_id"]];

        }

    }

}
/* INSERE COMPROMISSO NO POS VENDA LANÇADO AVULSO NA API */
if (count($dadosCompromissosAvulsos) > 0) {

    foreach ($dadosCompromissosAvulsos as $key => $dados) {

        $insertCompromissoPV = insereCompromissoPV($dados);

        if ($insertCompromissoPV === false) {

            $log["erro"]["insercao_compromisso_no_posvenda"][] = ["dados" => $dados, "external_id" => "", "motivo" => $insertCompromissoPV];

        } else {

            $atualizaExternalIdAPI = put("compromisso", $insertCompromissoPV);

            if (isset($atualizaExternalIdAPI["exception"]) || !isset($atualizaExternalIdAPI["id"])) {

                $log["erro"]["atualiza_externalid_na_api"][] = ["dados" => $insertCompromissoPV, "external_id" => "", "motivo" => $atualizaExternalIdAPI["exception"]];

            }

        }

    }

}

/* PEGA STATUS Q ESTA NO POSVENDA E ATUALIZA NA API SOMENTE O STATUS */
//BUSCO TODOS COMPRIMISSOS NA API
//COM OS COMPROMISSOS NA API, BUSCO ELES NO POSVENDA
//PEGO STATUS ATUAL DELE NO POSVENDA
//E ATUALIZO NA API
$dadosCompromissos = get("compromisso/companiaId/" . $dadosCompania["id"]);
$dadosC = [];

foreach ($dadosCompromissos as $k => $row) {
  if (isset($row['dados_compromisso'])) {
    $dadosC[$row['dados_compromisso']['id']] = $row['dados_compromisso']['external_id'];
  }
}
if (count($dadosC) > 0) {

  foreach ($dadosC as $id_compromisso_api => $id_compromisso_pv) {
    $id_pv    = getIDExternal($id_compromisso_pv);
    $statusPV = getStatusCompromissoPV($id_pv);

    $statusCompVisita = get("compromissoStatusVisita/compromissoId/" . $id_compromisso_api);
 
    //$statusCompVisita = get("compromisso-status-visita/compromissoId/" . $id_compromisso_api);
    $statusCompVisita = current($statusCompVisita);


    $statusComp = get("statusVisita/companiaId/" . $dadosCompania["id"]."/codigo/" . $statusPV);
    //$statusComp = get("status-visita/companiaId/" . $dadosCompania["id"]."/codigo/" . $statusPV);
    $statusComp = current($statusComp);
   
    $atualizaDados = [
      "id" => $statusCompVisita["id"],
      "compromissoId" => $id_compromisso_api,
      "statusvisitaId" => $statusComp["id"],

    ];
    $atualizaStatusApi = put("compromissoStatusVisita", $atualizaDados);
  
    //$atualizaStatusApi = put("compromisso-status-visita", $atualizaDados);
    if (isset($atualizaStatusApi["exception"])) {
        $log["erro"]["atualiza_status_na_api"][] = ["dados" => $atualizaDados, "external_id" => "", "motivo" => $atualizaStatusApi["exception"]];
    }

  }
}


/* SE TIVER ERRO , ENVIA LOG PARA EMAIL */
if (count($log) > 0) {

    $body .= "<b>Horário:</b> " . date("d/m/Y H:i:s") . "<br />";
    $body .= "<table width='100%' border='1'>";
    foreach ($log["erro"] as $titulo => $valores) {
        $body .= "
                <thead> 
                    <tr>
                        <th bgcolor='#d90000' style='padding: 5px;color: #fff;font-family: Arial;text-align:center;'>".strtoupper($titulo)."</th>
                    </tr>
                </thead><tbody>";
        foreach ($valores as $rows) {
                
            $body .= "
                    <tr>
                        <td style='padding: 5px;font-family: Arial;text-align:left;'>
                        <b style='color: red;'>Motivo do erro:</b> ".$rows["motivo"]."<br>
                        <b>External_id:</b> ".$rows["external_id"]."<br>
                        <b>Dados Request:</b> <pre>".var_export($rows["dados"], true)."</pre><br>
                        </td>
                    </tr>
                    ";
        }
    }
    $body .= "</tbody></table>";

    $mail->sendMail(
        $destinatarios,
        $subject,
        $body,
        "noreply@telecontrol.com.br"
    );
   // echo "<pre>".print_r($body, 1)."</pre>";
}

