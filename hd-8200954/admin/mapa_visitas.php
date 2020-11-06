<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';
if ($_GET["ajax_busca_posto_mapa"] == true) {

    $retorno          = array();
    $estado           = $_POST["estado"];
    $tipo_contato     = $_POST["tipo_contato"];
    $tipo             = $_POST["tipo"];
    $tipo_posto       = $_POST["tipo_posto"];
    $dataf            = date('Y-m-d');
    $datai6           = date('Y-m-d', strtotime('-6 months'));
    $datai12          = date($datai6, strtotime('-12 months'));
    $cond             = "";

    if (in_array($login_fabrica, [42])) {
        $str_cod_tipo = implode(",",array_filter($tipo_posto));
        $cond .= " AND tipo_posto IN ($str_cod_tipo) ";
    }
    if ($tipo_contato == "C") {
        $cond_doze      = " AND tbl_cliente.cpf NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro_posto.status <> 'OK' AND codigo IS NOT NULL AND data_inicio BETWEEN '$datai12' AND '$dataf')"; 
        $cond_seis_doze = " AND tbl_roteiro_posto.status = 'OK' AND data_inicio::date - INTERVAL '6 months' "; 
        $cond_seis      = " AND tbl_roteiro_posto.status = 'OK' AND data_inicio BETWEEN '$datai6' AND '$dataf'"; 
        $cond_agendada  = " AND tbl_roteiro_posto.status IN('AC','CF') and data_visita >= current_date "; 
        $joinConsumidor .= " JOIN tbl_roteiro_posto ON tbl_cliente.cpf = tbl_roteiro_posto.codigo
                             JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 
            //$joinRoteiroVisita .= " JOIN tbl_roteiro_visita ON tbl_roteiro_posto.roteiro_posto = tbl_roteiro_visita.roteiro_posto"; 

        if ($tipo == "consumidor") {
            
            $retorno["doze"]        = getConsumidorMapa($joinConsumidor, $cond_doze, $estado);
            $retorno["seis_doze"]   = getConsumidorMapa($joinConsumidor, $cond_seis_doze, $estado);
            $retorno["seis"]        = getConsumidorMapa($joinConsumidor, $cond_seis, $estado);
            $retorno["agendada"]    = getConsumidorMapa($joinConsumidor, $cond_agendada, $estado);
            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum consumidor encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "doze_nao_visitado") {

            $cond = " AND tbl_cliente.cpf NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro_posto.status <> 'OK' AND codigo IS NOT NULL AND data_inicio BETWEEN '$datai12' AND '$dataf')"; 

            $retorno["doze"]    = getConsumidorMapa($joinConsumidor, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_doze_visitado") {


            $setaData = new DateTime($datai6);
            $data612 = new DateTime('-12 month');

            $cond = " AND tbl_roteiro_posto.status = 'OK'  AND data_inicio BETWEEN '$datai6' AND '".$data612->format('Y-m-d')."'"; 

            $retorno["seis_doze"]    = getConsumidorMapa($joinConsumidor, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_visitado") {

            $cond  = " AND tbl_roteiro_posto.status = 'OK' AND data_inicio::date INTERVAL '-6 months'"; 

            $retorno["seis"] = getConsumidorMapa($joinConsumidor, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "visita_agendada") {
            $joinRoteiroVisita .= " JOIN tbl_roteiro_posto ON tbl_cliente.cpf = tbl_roteiro_posto.codigo AND status not in('OK', 'CC')
                                    JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 

            $cond  = " AND tbl_roteiro_posto.status NOT IN('AC','CF') and data_visita >= current_date "; 

            $retorno["agendada"] = getConsumidorMapa($joinRoteiroVisita, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } 

    } 

    if ($tipo_contato == "P") {

        if ($tipo == "doze_nao_visitado") {

            $cond .= " AND tbl_posto.cnpj NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro_posto.status <> 'OK' AND codigo IS NOT NULL AND data_inicio BETWEEN '$datai12' AND '$dataf')"; 

            $retorno["doze"]    = getPostoMapa($leftJoinRoteiro, $cond, false,$estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_doze_visitado") {
            $setaData = new DateTime($datai6);
            $data612 = new DateTime('-12 month');

            $cond .= " AND tbl_roteiro_posto.status = 'OK'  AND data_inicio BETWEEN '$datai6' AND '".$data612->format('Y-m-d')."'"; 

            $joinRoteiroVisita .= " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status IN('OK')
                                    JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 
            $joinRoteiroVisita .= " JOIN tbl_roteiro_visita ON tbl_roteiro_posto.roteiro_posto = tbl_roteiro_visita.roteiro_posto"; 

            $retorno["seis_doze"]    = getPostoMapa($joinRoteiroVisita, $cond, false, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_visitado") {

            $cond  .= " AND tbl_roteiro_posto.status = 'OK' AND data_inicio::date BETWEEN '$datai6' AND '$dataf'"; 

            $joinRoteiroVisita .= " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status IN('OK')
                                    JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 

            $joinRoteiroVisita .= " JOIN tbl_roteiro_visita ON tbl_roteiro_posto.roteiro_posto = tbl_roteiro_visita.roteiro_posto"; 

            $retorno["seis"] = getPostoMapa($joinRoteiroVisita, $cond, false, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "visita_agendada") {

            $cond  .= " AND tbl_roteiro_posto.status NOT IN('AC','CF') and data_visita >= current_date "; 
            $joinRoteiro     .= " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status NOT IN('CC', 'OK')
                                  JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 

            $retorno["agendada"] = getPostoMapa($joinRoteiro, $cond, false, $estado);
            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "posto") {

            $data612 = new DateTime('-12 month');
            $joinRoteiroAgendada    = $joinRoteiro . " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status NOT IN('AC', 'OK')
                                  JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 

            $joinRoteiroVisita .= " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status IN('OK')
                                    JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 
            $joinRoteiroVisita .= " JOIN tbl_roteiro_visita ON tbl_roteiro_posto.roteiro_posto = tbl_roteiro_visita.roteiro_posto"; 
            

            $cond_doze              = " AND tbl_posto.cnpj NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro_posto.status <> 'OK' AND codigo IS NOT NULL AND data_inicio::DATE < '".$data612->format('Y-m-d')."')"; 
            $cond_seis_doze         = " AND tbl_roteiro_posto.status = 'OK' AND data_inicio::date BETWEEN '".$data612->format('Y-m-d')."' and '$datai6'"; 
            $cond_seis              = " AND tbl_roteiro_posto.status = 'OK' AND data_inicio > '$datai6'"; 
            $cond_agendada          = " AND tbl_roteiro_posto.status NOT IN('AC') and data_visita >= current_date "; 

            $retorno["doze"]        = getPostoMapa($joinRoteiro, $cond_doze . $cond, false, $estado);
            $retorno["seis_doze"]   = getPostoMapa($joinRoteiroVisita, $cond_seis_doze . $cond, false, $estado);
            $retorno["seis"]        = getPostoMapa($joinRoteiroVisita, $cond_seis . $cond, false, $estado);
            $retorno["agendada"]    = getPostoMapa($joinRoteiroAgendada, $cond_agendada . $cond, false, $estado);
            if (count($retorno) == 0) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } else {
            $retorno = getPostoMapa('', '', true, $estado);
            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }
        }
    }

}

function getConsumidorMapa($joins = '', $where = '', $estado_postos = '') {
    global $login_fabrica, $con;

    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months'));
    $condEstado = "";

    if (strlen($estado_postos) > 0) {
        $condEstado  = " AND tbl_cidade.estado = '{$estado_postos}'";
    }

    if (!empty($joins)) {
        $cond = ",
                       (
                           SELECT (COUNT(os)/6)  
                             FROM tbl_os
                            WHERE fabrica = {$login_fabrica} 
                              AND status_checkpoint = 9
                              AND consumidor_cpf = tbl_cliente.cpf
                              AND data_digitacao::date BETWEEN '$data_seis_ant' AND '$data_hoje'
                       ) AS media_os";
    } else {
        $cond = "";
    }

    $sql = "SELECT tbl_cliente.*,
                   tbl_cidade.nome AS nome_cidade,
                   tbl_cidade.estado AS nome_estado
                   {$cond}
                 FROM tbl_cliente
            LEFT JOIN tbl_cidade USING(cidade)
                      {$joins}
                WHERE tbl_cliente.fabrica={$login_fabrica}
and latitude notnull
                      {$condEstado}
                      {$where} ;";

    //die(nl2br($sql));                      
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    $key = 0;
    while ($rows = pg_fetch_assoc($res)) {

        $retorno[$key]["cliente"]                   = $rows["cliente"];
        $retorno[$key]["latitude"]                = $rows["latitude"];
        $retorno[$key]["longitude"]               = $rows["longitude"];
        $retorno[$key]["nome"]                      = empty($rows["nome"]) ? "" : utf8_encode($rows["nome"]);
        $retorno[$key]["contato_endereco"]          = empty($rows["endereco"]) ? "" : utf8_encode($rows["endereco"]);
        $retorno[$key]["contato_numero"]            = empty($rows["numero"]) ? "" : utf8_encode($rows["numero"]);
        $retorno[$key]["contato_email"]             = empty($rows["email"]) ? "" : utf8_encode($rows["email"]);
        $retorno[$key]["contato_bairro"]            = empty($rows["bairro"]) ? "" : utf8_encode($rows["bairro"]);
        $retorno[$key]["contato_nome"]              = empty($rows["contato_nome"]) ? "" : utf8_encode($rows["contato_nome"]);
        $retorno[$key]["nome_fantasia"]             = empty($rows["nome_fantasia"]) ? "" : utf8_encode($rows["nome_fantasia"]);
        $retorno[$key]["contato_fone_comercial"]    = empty($rows["fone"]) ? "" : $rows["fone"];
        $retorno[$key]["contato_cidade"]            = empty($rows["nome_cidade"]) ? "" : utf8_encode($rows["nome_cidade"]);
        $retorno[$key]["contato_estado"]            = empty($rows["nome_estado"]) ? "" : utf8_encode($rows["nome_estado"]);
        $retorno[$key]["estado"]            = empty($rows["nome_estado"]) ? "" : utf8_encode($rows["nome_estado"]);
        $retorno[$key]["media_os_seis_meses"]       = empty($rows["media_os"]) ? 0 : $rows["media_os"];
        $retorno[$key]["treinamento_dois_anos"]     = 0;
        $retorno[$key]["media_compra_seis_meses"]   = 0;
        $retorno[$key]["desconto_pecas_eletricas"]  = "";
        $retorno[$key]["desconto_pecas_ope"]        = "";
        $retorno[$key]["desconto_pecas_lavadoras"]  = "";
        $key++;
    }

    return $retorno;

}
function getPostoMapa($joins = '', $where = '', $todos_postos = false, $estado_postos = '') {
    global $login_fabrica, $con;

    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months'));
    $cond  = "";
    if (strlen($estado_postos) > 0) {
        $cond  = " AND tbl_posto_fabrica.contato_estado = '{$estado_postos}'";      
    }

    if (in_array($login_fabrica, [42]) && count($estado_postos) > 0 ) {
        $str_array_estado = implode("','",array_filter($estado_postos));
        $cond = " AND tbl_posto_fabrica.contato_estado IN ('{$str_array_estado}') ";
    }  

    if ($todos_postos) {
        $sql = "SELECT UPPER(tbl_posto.nome) AS nome, 
                   tbl_posto.posto, 
                   tbl_posto_fabrica.contato_nome, 
                   tbl_posto_fabrica.contato_endereco, 
                   tbl_posto_fabrica.contato_numero,
                   tbl_posto_fabrica.contato_email,
                   tbl_posto_fabrica.nome_fantasia, 
                   tbl_posto_fabrica.contato_bairro, 
                   tbl_posto_fabrica.contato_fone_comercial, 
                   tbl_posto_fabrica.contato_cidade, 
                   tbl_posto_fabrica.latitude,
                   tbl_posto_fabrica.longitude,
                       0 AS media_os,
                       0 AS media_compra,
					   0 AS media_treinamento,
					a1_despec as desconto_pecas_eletricas,
					a1_despec2 as desconto_pecas_ope,
					a1_despec1 as desconto_pecas_lavadoras
                 FROM tbl_posto_fabrica
                 JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				 join makita_sa1_cliente ON tbl_posto.cnpj = a1_cgc
                WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO' AND tbl_posto.estado = '{$estado_postos}'";
    
    } else {


        $sql = "SELECT UPPER(tbl_posto.nome) AS nome, 
                       tbl_posto.posto, 
                       tbl_posto_fabrica.contato_nome, 
                       tbl_posto_fabrica.contato_endereco, 
                       tbl_posto_fabrica.contato_numero,
                       tbl_posto_fabrica.contato_email,
                       tbl_posto_fabrica.nome_fantasia, 
                       tbl_posto_fabrica.contato_bairro, 
                       tbl_posto_fabrica.contato_fone_comercial, 
                       tbl_posto_fabrica.contato_cidade, 
                       tbl_posto_fabrica.contato_estado, 
                       tbl_posto_fabrica.latitude,
					   tbl_posto_fabrica.longitude,
					a1_despec as desconto_pecas_eletricas,
					a1_despec2 as desconto_pecas_ope,
					a1_despec1 as desconto_pecas_lavadoras
                     FROM tbl_posto_fabrica
                     JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					join makita_sa1_cliente ON tbl_posto.cnpj = a1_cgc
                    {$joins}
                    WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					AND tbl_posto_fabrica.latitude notnull
                          {$cond}
                          {$where}";
    
    }
    
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    foreach (pg_fetch_all($res) as $key => $rows) {

        $retorno[$key]["posto"]                     = $rows["posto"];
        $retorno[$key]["latitude"]                  = $rows["latitude"];
        $retorno[$key]["longitude"]                 = $rows["longitude"];
        $retorno[$key]["nome"]                      = empty($rows["nome"])                      ? "" : utf8_encode($rows["nome"]);
        $retorno[$key]["contato_endereco"]          = empty($rows["contato_endereco"])          ? "" : utf8_encode($rows["contato_endereco"]);
        $retorno[$key]["contato_numero"]            = empty($rows["contato_numero"])            ? "" : utf8_encode($rows["contato_numero"]);
        $retorno[$key]["contato_email"]             = empty($rows["contato_email"])             ? "" : utf8_encode($rows["contato_email"]);
        $retorno[$key]["contato_bairro"]            = empty($rows["contato_bairro"])            ? "" : utf8_encode($rows["contato_bairro"]);
        $retorno[$key]["contato_nome"]              = empty($rows["contato_nome"])              ? "" : utf8_encode($rows["contato_nome"]);
        $retorno[$key]["nome_fantasia"]             = empty($rows["nome_fantasia"])             ? "" : utf8_encode($rows["nome_fantasia"]);
        $retorno[$key]["contato_fone_comercial"]    = empty($rows["contato_fone_comercial"])    ? "" : utf8_encode($rows["contato_fone_comercial"]);
        $retorno[$key]["contato_cidade"]            = empty($rows["contato_cidade"])            ? "" : utf8_encode($rows["contato_cidade"]);
        $retorno[$key]["contato_estado"]            = empty($rows["contato_estado"])            ? "" : utf8_encode($rows["contato_estado"]);
        $retorno[$key]["media_os_seis_meses"]       = getMediaOs($rows["posto"], $data_seis_ant, $data_hoje);
        $retorno[$key]["media_compra_seis_meses"]   = getMediaCompra($rows["posto"], $data_seis_ant, $data_hoje);
        $retorno[$key]["treinamento_dois_anos"]     = getMediaTreinamento($rows["posto"], $data_vinte_quatro_seis, $data_hoje);
        $retorno[$key]["desconto_pecas_eletricas"]  = $rows["desconto_pecas_eletricas"];
        $retorno[$key]["desconto_pecas_ope"]        = $rows["desconto_pecas_ope"];
        $retorno[$key]["desconto_pecas_lavadoras"]  = $rows["desconto_pecas_lavadoras"];
        
    }

    return $retorno;

}


function getMediaOs($posto, $data_seis_ant, $data_hoje) {

    global $login_fabrica, $con;

    $sql = "SELECT (COUNT(os)/6)  AS media_os
              FROM tbl_os
             WHERE fabrica = {$login_fabrica} 
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


function getMediaCompra($posto, $data_seis_ant, $data_hoje) {

    global $login_fabrica, $con;

    $sql = "SELECT (COUNT(pedido)/6) AS media_compra
             FROM tbl_pedido
            WHERE fabrica = {$login_fabrica} 
              AND status_pedido IN(4,5)
              AND posto = {$posto}
              AND data::date BETWEEN '$data_seis_ant' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_compra"];

}

function getMediaTreinamento($posto, $data_vinte_quatro_seis, $data_hoje) {

    global $login_fabrica, $con;

    $sql = "SELECT COUNT(tbl_treinamento_posto.posto) AS media_treinamento
             FROM tbl_treinamento_posto 
             JOIN tbl_treinamento USING(treinamento) 
            WHERE tbl_treinamento.fabrica = {$login_fabrica} 
              AND tbl_treinamento_posto.posto = {$posto}
              AND tbl_treinamento_posto.data_inscricao::date BETWEEN '$data_vinte_quatro_seis' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_treinamento"];
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

$layout_menu = "tecnica";
$title = "Mapa de Visitas";
include 'cabecalho_new.php';

$plugins = array(
    "multiselect",
    "shadowbox",
    "dataTable",
    "select2",
    "datepicker",
    "mask",
);

include("plugin_loader.php");
?>
<style>
<?php if ($login_fabrica == 42) {?>



#mapa_makita #frmdiv #makita_mapa_br{
    display: inline-block;
    width: 100%;
    height: 550px;
}
#mapa_makita #frmdiv #control_button{
    display: inline-block;
    width: 100%;
    text-align: center;
    color: #000;
    font-size: 12px;
    font-weight: bold;
}
#mapa_makita #frmdiv #control_button button{
    width: 100px;
    color: #000;
    font-size: 12px;
}

i.maker-blue{
     display: inline-block;
     width: 24px;
     height: 34px;
     background: url("imagens/marker-icon-blue.png");
     margin-right: 5px;
     margin-bottom: -16px;
}
i.maker-red{
     display: inline-block;
     width: 24px;
     height: 34px;
     background: url("imagens/marker-icon-red.png");
     margin-right: 5px;
     margin-bottom: -16px;
}
i.maker-yellow{
     display: inline-block;
     width: 24px;
     height: 34px;
     background: url("imagens/marker-icon-yellow.png");
     margin-right: 5px;
     margin-bottom: -16px;
}
i.maker-green{
     display: inline-block;
     width: 24px;
     height: 34px;
     background: url("imagens/marker-icon-green.png");
     margin-right: 5px;
     margin-bottom: -16px;
}
.maker-legendas{
    font-size: 13px;
    color: #000000;
    padding: 10px;
    font-weight: bold;
    min-height: 32px;
    letter-spacing: -0.7px;
}
#resultado-busca-mapa .titulo_tr{
    background-color: #596d9b;
    font:  11px "Arial" !important;
    color: #FFFFFF;
}
#resultado-busca-mapa .titulo_tr th{
    padding: 5px 10px ;            
}


.botao_mapa{
    cursor: pointer;
    background: #ffffff;
    border: solid 2px #eee;
}
.botao_mapa:hover{
    cursor: pointer;
    background: #eee;
    border: solid 2px #ccc;
}
.ativo{
    cursor: pointer;
    background: #eee;
    border: solid 2px #ccc;
}
.tal{
    text-align: left !important;
}

#retorno-mapa > tbody tr:nth-child(odd) {
    background-color:#fff;
}  
#retorno-mapa > tbody tr:nth-child(even) {
    background-color:#eee;
} 
#retorno-mapa > tbody tr td {
    padding: 5px;
    color: #222222;
    font-size: 11px;
}
<?php }?>
</style>

<link rel="stylesheet" href="plugins/leaflet/leaflet.css" />
<script src="plugins/leaflet/leaflet.js"></script>
<script src="plugins/leaflet/map.js"></script>

<script language="javascript">
    var map;
    var marca;
    $(function() {

        <? if($login_fabrica == 42) { ?>
            $("#estado_posto_makita").multiselect({
                selectedText: "selecionados # de #"
            });

            $("#tipo_posto").multiselect({
                selectedText: "selecionados # de #"
            });
        <? } ?>
        map = new Map('makita_mapa_br');
        marca = new Markers(map);
        
        $("#btn_carrega_mapa").click(function() {                
    
            var estado_posto = $("#estado_posto_makita").val();
            var tipo_mapa_makita = $("input[name=tipo_mapa_makita]:checked").val();

            if (tipo_mapa_makita == "posto" && $("#tipo_posto").length) { 
                var tipo_posto = $("#tipo_posto").val();
                if (tipo_posto == '' || tipo_posto == undefined) {
                    alert('Escolha um tipo de posto');
                    return false;
                }
            }
            
            if (estado_posto == '' || estado_posto == undefined) {
                alert('Escolha um Estado');
                return false;
            }
            
            if (tipo_mapa_makita == '' || tipo_mapa_makita == undefined) {
                alert('Escolha o tipo');
                return false;
            }

            $("#mapa_makita").show();
            if (tipo_mapa_makita == "posto") {
                $("#makita_mapa_br").show();
                $("#legenda_marcadores_posto").show();
                $("#legenda_marcadores_consumidor").hide();
                $("#mapabr").hide();
                setTimeout(function(){ 
                    getPostosMapa('posto', estado_posto);
                    map.load();
                }, 1000);
            } else if (tipo_mapa_makita == "consumidor") {
                $("#makita_mapa_br").show();
                $("#legenda_marcadores_consumidor").show();
                $("#legenda_marcadores_posto").hide();
                $("#mapabr").hide();
               
                setTimeout(function(){ 
                    getConsumidorMapa('consumidor', estado_posto);
                    map.load();                    
                }, 1000);
                
            } else {
                $("#legenda_marcadores_consumidor").hide();
                $("#legenda_marcadores_posto").hide();
                $("#makita_mapa_br").hide();
                $("#mapabr").show();
            }

        });

        <?php if (!in_array($login_fabrica, [42])) { ?>
            $(".botao_mapa").click(function() {
                var posicao = $(this).data("posicao");
                $(".botao_mapa").removeClass("ativo");
                $(this).addClass("ativo");

            });
        <?php } ?>

        $(".btn-ver-visitas").click(function() {
            var posto = $(this).data("posto");
            Shadowbox.open({
                content:    "listagem_visita.php?posto="+posto,
                player: "iframe",
                title:      "Visitas",
                width:  900,
                height: 500
            });
        });


        $('#mapa_makita map area').click(function() {
            $('#estado_posto_makita').val($(this).attr('name'));
        });

        <?php if (in_array($login_fabrica, [42])) { ?>
            setTimeout(function() {
                $('.botao_mapa').removeAttr('onClick');
                $('.botao_mapa').removeClass('botao_mapa');
            }, 1000)            
        <?php } ?>
        
    });
    function getConsumidorMapa(tipo, estado = "") {
        var paramentro =  {'tipo': tipo,'tipo_contato' : 'C', 'estado' : estado};
        if (tipo == "doze_nao_visitado") {
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();

                $.each(retorno.result.doze, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'red', '' , descricao, '')
                });
                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
            });
        } else if (tipo == "seis_doze_visitado") {
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();
                $.each(retorno.result.seis_doze, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'yellow', '' , descricao, 'extra_properties')
                });
                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
            });

        } else if (tipo == "seis_visitado") {
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();
                $.each(retorno.result.seis, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'green', '' , descricao, 'extra_properties');
                });
                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
               
            });

        } else if (tipo == "visita_agendada") {
            var conteudo = ""
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();
                $.each(retorno.result.agendada, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'blue', '' , descricao, 'extra_properties')
                });
                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
               
            });
        }  else if (tipo == "consumidor") {
            $("#resultado-busca-mapa").show();
            carrega_mapa(paramentro, function(retorno){
              
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
               
                if (retorno.result.doze.length > 0) {
                    $.each(retorno.result.doze, function(k, rows) {
                        let descricao = popDescricaoMapa(rows);
                        conteudo += getConteudoMapa(rows);
                        marca.add(rows.latitude, rows.longitude, 'red', '' , descricao, '')
                    });
                }
                if (retorno.result.seis_doze.length > 0) {
                    $.each(retorno.result.seis_doze, function(k, rows) {
                        let descricao = popDescricaoMapa(rows);
                        conteudo += getConteudoMapa(rows);
                        marca.add(rows.latitude, rows.longitude, 'yellow', '' , descricao, 'extra_properties')
                    });
                }
                if (retorno.result.seis.length > 0) {
                    $.each(retorno.result.seis, function(k, rows) {
                        let descricao = popDescricaoMapa(rows);
                        conteudo += getConteudoMapa(rows);
                        marca.add(rows.latitude, rows.longitude, 'green', '' , descricao, 'extra_properties')
                    });
                }

                if (retorno.result.agendada.length > 0) {
                    $.each(retorno.result.agendada, function(k, rows) {
                        let descricao = popDescricaoMapa(rows);
                        conteudo += getConteudoMapa(rows);
                        marca.add(rows.latitude, rows.longitude, 'blue', '' , descricao, 'extra_properties')
                    });
                }

                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
               
            });
        }
     }

    function getPostosMapa(tipo, est = "") {

	    var estado = $("#estado_posto_makita").val();

        var paramentro =  {'tipo': tipo,'tipo_contato' : 'P', 'estado' : estado};
        var conteudo = '';
        if (tipo == "doze_nao_visitado") {
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();
                $.each(retorno.result.doze, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'red', '' , descricao, 'extra_properties')
                });
                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
            });
        } else if (tipo == "seis_doze_visitado") {
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();
                $.each(retorno.result.seis_doze, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'green', '' , descricao, 'extra_properties')
                });
                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
            });

        } else if (tipo == "seis_visitado") {
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();
                $.each(retorno.result.seis, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'green', '' , descricao, 'extra_properties');
                });
                marca.render();
                $("#resultado-busca-mapa-conteudo").html(conteudo);
               
            });

        } else if (tipo == "visita_agendada") {
            var conteudo = ""
            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
                $("#resultado-busca-mapa").show();
                $.each(retorno.result.agendada, function(k, rows) {
                    let descricao = popDescricaoMapa(rows);
                    conteudo += getConteudoMapa(rows);
                    marca.add(rows.latitude, rows.longitude, 'blue', '' , descricao, 'extra_properties')
                });
                marca.render();
                $("#resultado-busca-mapa-conteudo").html(conteudo);
               
            });
        }  else if (tipo == "posto") {
            $("#resultado-busca-mapa").show();

            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $("#resultado-busca-mapa-conteudo").html('');
               
                if (retorno.result.doze.length > 0) {
                    $.each(retorno.result.doze, function(k, xrows) {
                        let descricao = popDescricaoMapa(xrows);
                        conteudo += getConteudoMapa(xrows);
                       marca.add(xrows.latitude, xrows.longitude, 'red', '' , descricao, 'extra_properties');
                    });
                }


                if (retorno.result.seis_doze.length > 0) {
                    $.each(retorno.result.seis_doze, function(k, rows) {
                        let descricao = popDescricaoMapa(rows);
                        conteudo += getConteudoMapa(rows);
                        marca.add(rows.latitude, rows.longitude, 'yellow', '' , descricao, 'extra_properties')
                    });
                }

                if (retorno.result.seis.length > 0) {
                    $.each(retorno.result.seis, function(k, rows) {
                        let descricao = popDescricaoMapa(rows);
                        conteudo += getConteudoMapa(rows);
                        marca.add(rows.latitude, rows.longitude, 'green', '' , descricao, 'extra_properties')
                    });
                }

                if (retorno.result.agendada.length > 0) {
                    $.each(retorno.result.agendada, function(k, rows) {
                        let descricao = popDescricaoMapa(rows);
                        conteudo += getConteudoMapa(rows);
                        marca.add(rows.latitude, rows.longitude, 'blue', '' , descricao, 'extra_properties')
                    });
                }

                $("#resultado-busca-mapa-conteudo").html(conteudo);
                marca.render();
               
            });
        } else {
            

            carrega_mapa(paramentro, function(retorno){
                marca.clear();
                marca.remove();
                $.each(retorno.result, function(k, rows) { 
                    var descricao = "<b>Endereço:</b> " + rows.contato_endereco + ", " + rows.contato_numero + " -  " + rows.contato_bairro + " - " + rows.contato_cidade + " <br/>" + "<b>Fone:</b> " + rows.contato_fone_comercial + "<br />" + "<b>E-mail:</b> " + rows.contato_email + "<br />";
                    marca.add(rows.latitude, rows.longitude, 'lightblue', rows.nome, descricao, '')
                });
                marca.render();
            })
            
        }

    }

    function getConteudoMapa(rows,bgcolor) {
        return "<tr data-posto='"+rows.posto+"'>\
                    <td  class='tal'>"+ rows.nome + "</td>\
                    <td  class='tal'>"+ rows.nome_fantasia + "</td>\
                    <td  class='tal'>"+ rows.contato_endereco + ", " + rows.contato_numero + " -  " + rows.contato_bairro + " - " + rows.contato_cidade + " / " + rows.contato_estado + "</td>\
                    <td  class='tal'>"+ rows.contato_nome + "</td>\
                    <td nowrap class='tal'>"+ rows.contato_fone_comercial + "</td>\
                    <td>"+ rows.treinamento_dois_anos + "</td>\
                    <td>"+ rows.media_os_seis_meses + "</td>\
                    <td>"+ rows.media_compra_seis_meses + "</td>\
                    <td>"+ rows.desconto_pecas_eletricas + "</td>\
                    <td>"+ rows.desconto_pecas_ope + "</td>\
                    <td>"+ rows.desconto_pecas_lavadoras + "</td>\
                    <td nowrap><a href='cadastro_roteiro_tecnico.php?externo=true&posto="+ rows.posto + "' target='_blank' class='btn btn-success'>Agendar Visita</a></td>\
                </tr>";
    }

    function popDescricaoMapa(rows) {
        return "<div style='font-size:13px'><b>Nome / Razão Social: </b> "+ rows.nome + "<br />"
                                +"<b>Nome Fantasia: </b> " + "<br />" 
                                +"<b>Endereço completo: </b> " + rows.contato_endereco + ", " + rows.contato_numero + " -  " + rows.contato_bairro + " - " + rows.contato_cidade + "<br />"
                                +"<b>Contato: </b> "+
                                +"<b>Telefone: </b> " + rows.contato_fone_comercial + "<br />" 
                                +"<b>E-mail: </b> "+ rows.contato_email + "<br />" 
                                +"<b>Treinamentos realizados nos últimos 2 anos: </b> "+ rows.treinamento_dois_anos + "<br />" 
                                +"<b>Média OS dos (últimos 6 meses): </b> "+ rows.media_os_seis_meses + "<br />" 
                                +"<b>Média de Compra(últimos 6 meses): </b> "+ rows.media_compra_seis_meses+"<br />" 
                                +"<b>Desconto de Peças Elétricas: </b> "+ rows.desconto_pecas_eletricas+ "<br />" 
                                +"<b>Desconto de Peças OPE: </b> "+ rows.desconto_pecas_ope +"<br />" 
                                +"<b>Desconto de Peças Lavadoras: </b> "+ rows.desconto_pecas_lavadoras+"<br />" 
                                +"<a href='cadastro_roteiro_tecnico.php?externo=true&posto="+ rows.posto + "' target='_blank'  class='btn_agendar'>Agendar Visita</a></div>";

    }

    function carrega_mapa(params, callback) {
        if ($("#tipo_posto").length) { 
            var tipo_posto = $("#tipo_posto").val();
        }
        $.ajax({
            url: "mapa_visitas.php?ajax_busca_posto_mapa=true",
            type: 'POST',
            dataType: 'JSON',
            data: {tipo:params.tipo, tipo_contato: params.tipo_contato, estado: params.estado, tipo_posto: tipo_posto},
            beforeSend: function () {
                $("#loading-block-2").show();
                $("#loading-2").show();
            }
        }).done(function(retorno){
            if (retorno.erro == true) {
                alert(retorno.msg);
                return false;
            }

            if(callback && typeof(callback) === "function") {
                callback(retorno);
            }
            setTimeout(function(){
                $('[data-posto]').each(function() {
                    if ($('[data-posto=' + $(this).data('posto') + ']').length > 1) {
                        this.remove();
                    }
                });
            }, 3000)
        })
        .always(function() {            
            $("#loading-block-2").hide();
            $("#loading-2").hide();
        });
    }
</script>


    <div class="row-fluid">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
    <form name='frm_relatorio' METHOD='POST' ACTION='mapa_visitas.php' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="pesquisa" value="true">
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='tipo_mapa_makita'>Tipo</label><br>
                    <input type="radio" name="tipo_mapa_makita" value="posto"> Posto
                    <input type="radio" name="tipo_mapa_makita" value="consumidor"> Consumidor <br>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label'>Estado</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <? if($login_fabrica == 42) { ?>
                                <select name="estado_posto_makita[]" class='span12' id="estado_posto_makita" multiple="multiple">
                            <? } else { ?>
                                    <select name="estado_posto_makita" class='span12 select2' id="estado_posto_makita">
                                        <option value="" selected="selected"> Selecione...</option>
                            <? } ?>
                                <?php 
                                    if($login_fabrica == 42){
                                        $selected_linha = array();
                                        foreach ($estados_BR as $rows) {
                                            $selected_linha[] = $rows;
                                            echo '<option value=' . $rows .'>' . $rows . '</option>';
                                        }
                                    } else {
                                        foreach ($estados_BR as $rows) {
                                            echo '<option value=' . $rows .'>' . $rows . '</option>';
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
         
            <div class='span2'></div>
        </div>
        <?php if (in_array($login_fabrica, [42])) { ?>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span6'>
                    <div class='control-group'>
                        <label class='control-label'>Tipo Posto</label>
                        <div class='controls controls-row'>
                            <div class='span11'>
                                <select name="tipo_posto[]" class='span12' id="tipo_posto" multiple="multiple">
                                    <?php 
                                     $sql = "SELECT *
                                            FROM   tbl_tipo_posto
                                            WHERE  tbl_tipo_posto.fabrica = $login_fabrica
                                            AND tbl_tipo_posto.ativo = 't'
                                            ORDER BY tbl_tipo_posto.descricao";
                                    $res = pg_query($con, $sql);
                                    $select_tipos_de_posto = pg_fetch_all($res);
                                    $selected_linha = array();
                                    foreach ($select_tipos_de_posto as $tipo_de_posto) {
                                        $selected_linha[] = $rows; ?>
                                        <option value="<?php echo $tipo_de_posto['tipo_posto'];?>"><?php echo $tipo_de_posto['descricao'];?></option>
                                    <?php }?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
             
                <div class='span2'></div>
            </div>
        <?php } ?>
     
        <p><br/>
            <button class='btn btn-primary' id="btn_carrega_mapa" type="button">Carregar Mapa</button>
        </p><br/>
    </form> <br />
</div> 

                  
<div class="container-fluid">
    <div id='mapa_makita'>
        <div id='frmdiv'>
            <fieldset>
                <div id='makita_mapa_br' style="display: none;"></div><br /><br />
                <div class="row-fluid" id="legenda_marcadores_posto" style="display: none;">
                    <div class="span3 botao_mapa btn_mapa_1" data-posicao="1" onclick="getPostosMapa('doze_nao_visitado');">
                        <div class="maker-legendas">
                            <i class="maker-red"></i> Postos não visitados nos últimos 12 meses
                        </div>
                    </div>
                    <div class="span3 botao_mapa btn_mapa_2" data-posicao="2" onclick="getPostosMapa('seis_doze_visitado');">
                        <div class="maker-legendas">
                            <i class="maker-yellow"></i> Postos visitados após 6 meses até 12 meses
                        </div>
                    </div>
                    <div class="span3 botao_mapa btn_mapa_3" data-posicao="3" onclick="getPostosMapa('seis_visitado');">
                        <div class="maker-legendas">
                            <i class="maker-green"></i> Postos visitados nos últimos 6 meses
                        </div>
                    </div>
                    <div class="span3 botao_mapa btn_mapa_4" data-posicao="4" onclick="getPostosMapa('visita_agendada');">
                        <div class="maker-legendas">
                            <i class="maker-blue"></i> Postos com visitas já agendada
                        </div>
                    </div>
                      
                </div>
                <div class="row-fluid" id="legenda_marcadores_consumidor" style="display: none;">
                    <div id="maker-legendas">
                        <div class="span3 botao_mapa btn_mapa_1" data-posicao="1" onclick="getConsumidorMapa('doze_nao_visitado', $('#estado_posto_makita').val());">
                            <div class="maker-legendas">
                                <i class="maker-red"></i> Consumidores não visitados nos últimos 12 meses
                            </div>
                        </div>
                        <div class="span3 botao_mapa btn_mapa_2" data-posicao="2" onclick="getConsumidorMapa('seis_doze_visitado', $('#estado_posto_makita').val());">
                            <div class="maker-legendas">
                                <i class="maker-yellow"></i> Consumidores visitados após 6 meses até 12 meses
                            </div>
                        </div>
                        <div class="span3 botao_mapa btn_mapa_3" data-posicao="3" onclick="getConsumidorMapa('seis_visitado', $('#estado_posto_makita').val());">
                            <div class="maker-legendas">
                                <i class="maker-green"></i> Consumidores visitados nos últimos 6 meses
                            </div>
                        </div>
                        <div class="span3 botao_mapa btn_mapa_4" data-posicao="4" onclick="getConsumidorMapa('visita_agendada', $('#estado_posto_makita').val());">
                            <div class="maker-legendas">
                                <i class="maker-blue"></i> Consumidores com visitas já agendada
                            </div>
                        </div>
                    </div>
                </div>
                <br /><br />
                <div id="resultado-busca-mapa" style="display: none;overflow-x: scroll;width: 100%;max-width: 100%">
                    <table border="1" id="retorno-mapa" style="border-color: #eee;max-width: 100%" cellpadding="1" cellspacing="0">
                        <thead>
                            <tr class="titulo_tr">
                                <th >Nome / Razão Social</th>
                                <th >Nome Fantasia</th>
                                <th >Endereço completo</th>
                                <th >Contato</th>
                                <th nowrap>Telefone</th>
                                <th nowrap><acronym title='Treinamentos realizados nos últimos 2 anos'>Treinamentos <br> realizados  (2 anos)</acronym></th>
                                <th nowrap><acronym title='Média OS dos (últimos 6 meses)'>Média OS <br> (6 meses)</acronym></th>
                                <th nowrap><acronym title='Média de Compra(últimos 6 meses)'>Média Compra <br> (6 meses)</acronym></th>
                                <th nowrap><acronym title='Desconto de Peças Elétricas'>Des. Pça.<br> Elé.</acronym></th>
                                <th nowrap><acronym title='Desconto de Peças OPE'>Des. Pça.<br> OPE</acronym></th>
                                <th nowrap><acronym title='Desconto de Peças Lavadoras'>Des. Pça.<br> Lav.</acronym></th>
                                <th nowrap>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="resultado-busca-mapa-conteudo"></tbody>
                    </table>
                </div>

            </fieldset>
        </div>
    </div>
</div>

    <br>
    <?php if (strlen($posto) > 0) {?>
        <button type="button" data-posto="<?php echo $posto;?>" class="btn-solicita btn-ver-visitas" title="Ver dados de visitas" >Ver dados de visitas</button><br><br>
    <?php }?>
  <div id="loading-block-2" style="z-index:10000 !important;width:100%;height:100%;position:fixed;left:0px;top:0px;text-align:center;vertical-align: middle;background-color:#000;opacity:0.3;display:none;" >
  </div>
  <div id="loading-2" style="display: none;" >
    <img src="imagens/loading_img.gif" style="z-index:1111111;top: 50%;z-index: 11111111; position: fixed;left: 50%;" />
    <input type="hidden" id="loading_action" value="f" />
    <div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;"></div>
  </div>  
  <?php if (in_array($login_fabrica, [42])) { ?>
    <script type="text/javascript">
        $('.select2').attr("multiple", "multiple");
        $('.select2').select2();
    </script>
  <?php } ?>
<?php include 'rodape.php';?>
